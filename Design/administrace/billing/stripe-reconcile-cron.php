<?php
declare(strict_types=1);
/**
 * RAC Stripe reconciliation safety net / 2026-10-26-03-RECENT-ONLY / PHP 7.4+
 * Supports authenticated HTTPS URL CRON (SvětHosting) and PHP CLI.
 * Dry-run: ?key=...&run=0 ; live recovery: ?key=...&run=1 (Stripe TEST only).
 * Requires existing Stripe test settings and RAC order/fulfilment modules.
 */
// Safety fence: only orders created during the current October 2026 test wave.
// Earlier legacy/test orders (e.g. 3–12) are never eligible for automatic recovery.
const RAC_RECONCILE_MIN_ORDER_ID = 38;
const RAC_RECONCILE_FROM_DATE = '2026-10-26 00:00:00';
// The panel invokes a URL. Authenticate BEFORE loading any database/config.
// Store only SHA-256 of the high-entropy token in PHP; keep token in private operator instructions.
$racReconcileTokenHash = '748117f759b716de035e3d346cad14930d64de2e1f18d95c071f1770453c5746';
$racCli = (PHP_SAPI === 'cli');
if ($racCli) {
    $options = array_slice($argv ?? [], 1);
    if (array_diff($options, ['--run'])) {
        fwrite(STDERR, "Usage: php stripe-reconcile-cron.php [--run]\n");
        exit(2);
    }
    $execute = in_array('--run', $options, true);
} else {
    header('Cache-Control: no-store, private');
    header('X-Robots-Tag: noindex, nofollow, noarchive');
    header('Content-Type: text/plain; charset=UTF-8');
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
        http_response_code(404);
        exit('Not Found');
    }
    $given = $_GET['key'] ?? null;
    if (!is_string($given) || !preg_match('/^[a-f0-9]{64}$/D', $given)
        || !hash_equals($racReconcileTokenHash, hash('sha256', $given))) {
        http_response_code(404);
        exit('Not Found');
    }
    // 0: observational / no database changes; 1: process verified payments.
    $runParam = $_GET['run'] ?? '0';
    if (!is_string($runParam) || !in_array($runParam, ['0', '1'], true)) {
        http_response_code(400);
        exit('Invalid mode');
    }
    $execute = ($runParam === '1');
    // Bound work and suppress accidental website errors for authenticated CRON requests.
    @set_time_limit(180);
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/../inc/rac-digital.php';
require_once __DIR__ . '/../inc/rac-mail.php';
require_once __DIR__ . '/../fakturace/inc/rac-faktury-core.php';
require_once __DIR__ . '/../fakturace/inc/rac-faktury-auto.php';
require_once __DIR__ . '/../fakturace/inc/rac-faktury-attachment.php';

if (!isset($pdo) || !$pdo instanceof PDO || !billing_settings_table_ready($pdo)) {
    error_log("RAC Stripe reconcile: database or billing settings unavailable");
    if ($racCli) { fwrite(STDERR, "RAC: database or billing settings unavailable\n"); }
    else { http_response_code(503); echo "RAC reconcile unavailable\n"; }
    exit(1);
}

function racReconcileStripeSession(string $secret, string $sessionId): array
{
    if (!preg_match('/^cs_test_[a-zA-Z0-9_]+$/D', $sessionId)) {
        throw new RuntimeException('Invalid sandbox checkout session ID');
    }
    if (!function_exists('curl_init')) {
        throw new RuntimeException('cURL unavailable');
    }
    $ch = curl_init('https://api.stripe.com/v1/checkout/sessions/' . rawurlencode($sessionId));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $secret],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $body = curl_exec($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    $json = is_string($body) ? json_decode($body, true) : null;
    if ($http !== 200 || !is_array($json)) {
        throw new RuntimeException('Stripe API error HTTP ' . $http . ($err ? ' / cURL error' : ''));
    }
    return $json;
}

function racReconcileFulfil(PDO $pdo, int $orderId): void
{
    if (!racDigitalPaid($pdo, $orderId)) {
        throw new RuntimeException('Confirmed paid payment not found');
    }
    $bundle = racFakturaOrder($pdo, $orderId);
    $o = $bundle['order'];
    $p = $bundle['payment'];
    $cfg = $bundle['config'];
    $supported = (int)$cfg['platce_dph'] === 0
        && strtoupper((string)$o['currency']) === 'CZK'
        && strtoupper((string)$p['currency']) === 'CZK'
        && racFakturaSupportedEuCountry((string)($o['billing_country'] ?? ''));
    if ($supported) {
        racFakturaCheckForIssue($bundle);
        racFakturaAutoOnPayment($pdo, $orderId);
        $s = $pdo->prepare("SELECT id FROM fakturace_doklady WHERE order_id=? AND typ='faktura' AND stav='zaplaceno' LIMIT 1");
        $s->execute([$orderId]);
        if (!$s->fetchColumn()) {
            throw new RuntimeException('Invoice missing');
        }
        $attachment = racFakturaPdfAttachment($pdo, $orderId);
        if (!is_array($attachment) || strncmp((string)($attachment['data'] ?? ''), '%PDF-', 5) !== 0) {
            throw new RuntimeException('Invoice PDF unavailable');
        }
        unset($attachment);
    } else {
        error_log('RAC Stripe reconciliation: invoice outside pilot scope for order ' . $orderId);
    }

    $s = $pdo->prepare("SELECT COUNT(*) FROM shop_order_items WHERE order_id=? AND variant_kind IN ('ebook','audio')");
    $s->execute([$orderId]);
    if ((int)$s->fetchColumn() === 0) {
        return;
    }
    $pdo->beginTransaction();
    try {
        $lock = $pdo->prepare('SELECT id FROM shop_orders WHERE id=? FOR UPDATE');
        $lock->execute([$orderId]);
        if (!$lock->fetchColumn()) {
            throw new RuntimeException('Order missing during digital fulfilment');
        }
        $grant = racDigitalGrant($pdo, $orderId);
        $pdo->commit();
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $ex;
    }
    if ((int)$grant['digital'] > 0) {
        // Exactly the same source used by webhook; mail-log guards duplicate sends.
        $mailLockName = 'rac_shop_digital_mail_' . $orderId;
        $lockStatement = $pdo->prepare('SELECT GET_LOCK(?, 5)');
        $lockStatement->execute([$mailLockName]);
        if ((int)$lockStatement->fetchColumn() !== 1) {
            throw new RuntimeException('Could not obtain digital email lock');
        }
        try {
            racDigitalSmtpEmail($pdo, $orderId, 'webhook');
        } finally {
            $releaseStatement = $pdo->prepare('SELECT RELEASE_LOCK(?)');
            $releaseStatement->execute([$mailLockName]);
        }
    }
}

try {
    $mode = strtolower(trim((string)billing_setting_get($pdo, 'mode', 'test')));
    if ($mode !== 'test') {
        throw new RuntimeException('This CRON version is sandbox-only; refusing to run outside test mode');
    }
    $secret = trim((string)billing_setting_get($pdo, 'stripe_test_secret_key', ''));
    if (!preg_match('/^(sk|rk)_test_[a-zA-Z0-9]+$/D', $secret)) {
        throw new RuntimeException('Sandbox Stripe API secret key not configured');
    }
    // Pending sessions take priority; already paid orders are revisited only when
    // invoice or digital delivery is missing. Keep work bounded per run.
    $base = "SELECT p.id payment_id,p.order_id,p.status payment_status,p.environment,p.checkout_session_id,
                   p.amount_minor,p.currency,p.payment_intent_id,o.order_number,o.status order_status,
                   o.total,o.currency order_currency
            FROM shop_payments p JOIN shop_orders o ON o.id=p.order_id
            WHERE p.provider='stripe' AND p.environment='test'
              AND p.checkout_session_id IS NOT NULL
              AND o.id >= " . RAC_RECONCILE_MIN_ORDER_ID . "
              AND o.created_at >= '" . RAC_RECONCILE_FROM_DATE . "' ";
    $pending = $pdo->query($base . " AND p.status IN ('pending','failed') ORDER BY p.id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    $needsFulfilment = $pdo->query($base . " AND p.status='paid' AND o.status='paid'
       AND (NOT EXISTS (SELECT 1 FROM fakturace_doklady d WHERE d.order_id=o.id AND d.typ='faktura' AND d.stav='zaplaceno')
          OR (EXISTS (SELECT 1 FROM shop_order_items i WHERE i.order_id=o.id AND i.variant_kind IN ('ebook','audio'))
              AND NOT EXISTS (SELECT 1 FROM shop_digital_mail_log m WHERE m.order_id=o.id AND m.source='webhook' AND m.status IN ('accepted','sending'))))
       ORDER BY p.id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    $rows = array_merge($pending, $needsFulfilment);
    echo 'RAC reconcile scope: order_id >= ' . RAC_RECONCILE_MIN_ORDER_ID . ' and created_at >= ' . RAC_RECONCILE_FROM_DATE . PHP_EOL;
    $checked = 0;
    $recovered = 0;
    $problems = 0;
    foreach ($rows as $row) {
        $oid = (int)$row['order_id'];
        $pid = (int)$row['payment_id'];
        $lockName = 'rac_shop_reconcile_order_' . $oid;
        $s = $pdo->prepare('SELECT GET_LOCK(?,0)');
        $s->execute([$lockName]);
        if ((int)$s->fetchColumn() !== 1) {
            continue;
        }
        try {
            $stripe = racReconcileStripeSession($secret, (string)$row['checkout_session_id']);
            ++$checked;
            $meta = is_array($stripe['metadata'] ?? null) ? $stripe['metadata'] : [];
            $valid = ($stripe['object'] ?? '') === 'checkout.session'
                && ($stripe['id'] ?? '') === $row['checkout_session_id']
                && empty($stripe['livemode'])
                && ($stripe['mode'] ?? '') === 'payment'
                && ($stripe['client_reference_id'] ?? '') === $row['order_number']
                && ($meta['integration'] ?? '') === 'rac-shop'
                && (string)($meta['shop_order_id'] ?? '') === (string)$oid
                && ($meta['order_number'] ?? '') === $row['order_number']
                && strtolower((string)($stripe['currency'] ?? '')) === strtolower((string)$row['currency'])
                && strtoupper((string)$row['currency']) === strtoupper((string)$row['order_currency'])
                && (int)($stripe['amount_total'] ?? -1) === (int)$row['amount_minor']
                && (int)$row['amount_minor'] === (int)round((float)$row['total'] * 100);
            if (!$valid) {
                throw new RuntimeException('Stripe session does not match the stored RAC order/amount');
            }
            if (($stripe['payment_status'] ?? '') !== 'paid') {
                continue;
            }
            $intent = $stripe['payment_intent'] ?? null;
            if (is_array($intent)) {
                $intent = $intent['id'] ?? null;
            }
            if (!is_string($intent) || strpos($intent, 'pi_') !== 0) {
                throw new RuntimeException('Stripe payment intent missing');
            }
            if ($execute) {
                $pdo->beginTransaction();
                try {
                    $s = $pdo->prepare('SELECT p.status payment_status,o.status order_status FROM shop_payments p JOIN shop_orders o ON o.id=p.order_id WHERE p.id=? AND o.id=? FOR UPDATE');
                    $s->execute([$pid, $oid]);
                    $current = $s->fetch(PDO::FETCH_ASSOC);
                    if (!$current || !in_array((string)$current['order_status'], ['awaiting_payment', 'paid'], true)) {
                        throw new RuntimeException('Order changed to incompatible state');
                    }
                    if ((string)$current['payment_status'] !== 'paid') {
                        $u = $pdo->prepare("UPDATE shop_payments SET status='paid',payment_intent_id=? WHERE id=? AND environment='test'");
                        $u->execute([$intent, $pid]);
                    }
                    $u = $pdo->prepare("UPDATE shop_orders SET status='paid' WHERE id=? AND status='awaiting_payment'");
                    $u->execute([$oid]);
                    $pdo->commit();
                } catch (Throwable $ex) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    throw $ex;
                }
                racReconcileFulfil($pdo, $oid);
                ++$recovered;
            } else {
                echo 'DRY RUN: Stripe confirms paid session for order ID ' . $oid . PHP_EOL;
            }
        } catch (Throwable $ex) {
            ++$problems;
            error_log('RAC Stripe reconcile order ' . $oid . ': ' . $ex->getMessage());
            if ($racCli) fwrite(STDERR, 'Order ID ' . $oid . ': ' . $ex->getMessage() . PHP_EOL);
        } finally {
            $s = $pdo->prepare('SELECT RELEASE_LOCK(?)');
            $s->execute([$lockName]);
        }
    }
    echo 'RAC reconcile: checked=' . $checked . ' processed=' . $recovered . ' errors=' . $problems . ' mode=' . ($execute ? 'run' : 'dry-run') . PHP_EOL;
    if (!$racCli && $problems > 0) http_response_code(503);
    exit($problems > 0 ? 1 : 0);
} catch (Throwable $ex) {
    error_log('RAC Stripe reconcile fatal: ' . $ex->getMessage());
    if ($racCli) fwrite(STDERR, 'RAC Stripe reconcile: ' . $ex->getMessage() . PHP_EOL); else http_response_code(503);
    exit(1);
}
