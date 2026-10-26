<?php
declare(strict_types=1);

/**
 * RAC E-shop - Stripe Checkout webhook (SANDBOX ONLY)
 * Version 2026-10-25-SHOP-INVOICE-EU-01 / PHP 7.4+
 *
 * Dedicated endpoint for shop_orders + shop_payments.
 * Does NOT modify the existing billing/webhook.php.
 * After confirmed payment issues eligible invoice, verifies PDF, grants digital access, and emails.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/../inc/rac-digital.php';
require_once __DIR__ . '/../inc/rac-mail.php';

header('Content-Type: text/plain; charset=utf-8');

function racShopWebhookReply(int $code, string $message): void
{
    http_response_code($code);
    exit($message);
}

function racShopWebhookSignatureValid(string $body, string $header, string $secret): bool
{
    $timestamp = null;
    $signatures = array();
    foreach (explode(',', $header) as $part) {
        $pieces = explode('=', trim($part), 2);
        if (count($pieces) !== 2) {
            continue;
        }
        if ($pieces[0] === 't' && ctype_digit($pieces[1])) {
            $timestamp = (int)$pieces[1];
        } elseif ($pieces[0] === 'v1' && preg_match('/^[a-f0-9]{64}$/i', $pieces[1])) {
            $signatures[] = strtolower($pieces[1]);
        }
    }
    if ($timestamp === null || abs(time() - $timestamp) > 300 || !$signatures) {
        return false;
    }
    $expected = hash_hmac('sha256', (string)$timestamp . '.' . $body, $secret);
    foreach ($signatures as $actual) {
        if (hash_equals($expected, $actual)) {
            return true;
        }
    }
    return false;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    racShopWebhookReply(405, 'Method Not Allowed');
}
if (!isset($pdo) || !($pdo instanceof PDO)) {
    racShopWebhookReply(500, 'Database unavailable');
}
if (!billing_settings_table_ready($pdo)) {
    racShopWebhookReply(500, 'Billing settings unavailable');
}

try {
    $secret = trim((string)billing_setting_get($pdo, 'stripe_test_webhook_secret', ''));
} catch (Throwable $e) {
    error_log('RAC shop webhook config load failed');
    racShopWebhookReply(500, 'Webhook configuration unavailable');
}
if (strpos($secret, 'whsec_') !== 0) {
    racShopWebhookReply(500, 'Sandbox webhook secret is not configured');
}

$body = file_get_contents('php://input');
$signature = (string)($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '');
if (!is_string($body) || $body === '' || !racShopWebhookSignatureValid($body, $signature, $secret)) {
    racShopWebhookReply(400, 'Invalid Stripe signature');
}
$event = json_decode($body, true);
if (!is_array($event) || empty($event['id']) || empty($event['type']) || !empty($event['livemode'])) {
    racShopWebhookReply(400, 'Invalid or non-sandbox event');
}
$eventType = (string)$event['type'];
$allowed = array(
    'checkout.session.completed',
    'checkout.session.async_payment_succeeded',
    'checkout.session.async_payment_failed',
    'checkout.session.expired',
);
if (!in_array($eventType, $allowed, true)) {
    racShopWebhookReply(200, 'Ignored event');
}
$session = $event['data']['object'] ?? null;
if (!is_array($session) || (string)($session['object'] ?? '') !== 'checkout.session') {
    racShopWebhookReply(400, 'Invalid checkout session');
}
$sessionId = trim((string)($session['id'] ?? ''));
if (strpos($sessionId, 'cs_test_') !== 0) {
    racShopWebhookReply(400, 'Invalid sandbox session ID');
}

$metadata = isset($session['metadata']) && is_array($session['metadata']) ? $session['metadata'] : array();
// Events unrelated to RAC shop must never alter shop orders.
if ((string)($metadata['integration'] ?? '') !== 'rac-shop') {
    racShopWebhookReply(200, 'Unrelated checkout session');
}
$shopOrderId = (string)($metadata['shop_order_id'] ?? '');
$orderNumber = trim((string)($metadata['order_number'] ?? ''));
$clientReference = trim((string)($session['client_reference_id'] ?? ''));
if (!ctype_digit($shopOrderId) || (int)$shopOrderId < 1 || $orderNumber === '' || $clientReference !== $orderNumber) {
    racShopWebhookReply(400, 'Inconsistent order metadata');
}

$paymentIntentId = null;
if (is_string($session['payment_intent'] ?? null)) {
    $paymentIntentId = $session['payment_intent'];
} elseif (is_array($session['payment_intent'] ?? null)) {
    $paymentIntentId = isset($session['payment_intent']['id']) ? (string)$session['payment_intent']['id'] : null;
}

// Only confirmed payments can mark an order as paid.
$paymentStatus = strtolower(trim((string)($session['payment_status'] ?? '')));
$transition = 'pending';
if ($eventType === 'checkout.session.completed' && $paymentStatus === 'paid') {
    $transition = 'paid';
} elseif ($eventType === 'checkout.session.async_payment_succeeded' && $paymentStatus === 'paid') {
    $transition = 'paid';
} elseif ($eventType === 'checkout.session.async_payment_failed') {
    $transition = 'failed';
} elseif ($eventType === 'checkout.session.expired') {
    $transition = 'expired';
}

try {
    $pdo->beginTransaction();
    // Stripe Checkout Session ID is stored by Design/platba.php before redirect.
    $stmt = $pdo->prepare(
        'SELECT p.id AS payment_id, p.order_id, p.status AS payment_status, p.environment, '
        . 'o.id AS shop_order_id, o.order_number, o.status AS order_status '
        . 'FROM shop_payments p JOIN shop_orders o ON o.id = p.order_id '
        . 'WHERE p.checkout_session_id = :session_id AND p.provider = :provider '
        . 'LIMIT 1 FOR UPDATE'
    );
    $stmt->execute(array(':session_id' => $sessionId, ':provider' => 'stripe'));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        throw new RuntimeException('No matching shop payment for Checkout Session');
    }
    if ((string)$row['environment'] !== 'test'
        || (int)$row['shop_order_id'] !== (int)$shopOrderId
        || (string)$row['order_number'] !== $orderNumber) {
        throw new RuntimeException('Checkout Session mismatches stored shop order');
    }
    $paymentId = (int)$row['payment_id'];
    $currentPaymentStatus = (string)$row['payment_status'];

    // Never downgrade a paid payment after delayed/repeated events.
    if ($currentPaymentStatus !== 'paid' && $transition !== 'pending') {
        $up = $pdo->prepare(
            'UPDATE shop_payments SET status = :status, payment_intent_id = COALESCE(:intent_id, payment_intent_id) '
            . 'WHERE id = :id'
        );
        $up->execute(array(':status' => $transition, ':intent_id' => $paymentIntentId, ':id' => $paymentId));
    }
    if ($transition === 'paid') {
        // Do not convert refunded/cancelled orders back to paid via a delayed event.
        $up = $pdo->prepare(
            "UPDATE shop_orders SET status = 'paid' "
            . "WHERE id = :id AND status = 'awaiting_payment'"
        );
        $up->execute(array(':id' => (int)$shopOrderId));
    }
    $pdo->commit();

    // Payment and order status were committed. Digital fulfilment can be retried
    // independently if an error occurs after that commit.
    if ($transition === 'paid') {
        $orderId = (int)$shopOrderId;
        if (!racDigitalPaid($pdo, $orderId)) {
            throw new RuntimeException('Paid Stripe event did not produce a paid shop payment');
        }

        // Issue the invoice BEFORE digital delivery. This is the actual Stripe shop endpoint.
        // Non-EU, non-CZK or VAT-registered sales remain outside the pilot invoicing scope.
        $invoiceRequired = false;
        $invoiceReady = false;
        require_once __DIR__ . '/../fakturace/inc/rac-faktury-core.php';
        require_once __DIR__ . '/../fakturace/inc/rac-faktury-auto.php';
        $invoiceBundle = racFakturaOrder($pdo, $orderId);
        $invoiceOrder = $invoiceBundle['order'];
        $invoicePayment = $invoiceBundle['payment'];
        $invoiceConfig = $invoiceBundle['config'];
        $invoiceUnsupported = (
            (int)$invoiceConfig['platce_dph'] !== 0
            || strtoupper((string)$invoiceOrder['currency']) !== 'CZK'
            || strtoupper((string)$invoicePayment['currency']) !== 'CZK'
            || !racFakturaSupportedEuCountry((string)($invoiceOrder['billing_country'] ?? ''))
        );
        if (!$invoiceUnsupported) {
            $invoiceRequired = true;
            racFakturaCheckForIssue($invoiceBundle);
            racFakturaAutoOnPayment($pdo, $orderId);
            $invoiceLookup = $pdo->prepare(
                "SELECT id FROM fakturace_doklady WHERE order_id = ? AND typ = 'faktura' AND stav = 'zaplaceno' LIMIT 1"
            );
            $invoiceLookup->execute(array($orderId));
            $invoiceReady = (bool)$invoiceLookup->fetchColumn();
            if (!$invoiceReady) {
                throw new RuntimeException('Invoice missing after automatic issuance, order ' . $orderId);
            }
            require_once __DIR__ . '/../fakturace/inc/rac-faktury-attachment.php';
            $invoiceAttachment = racFakturaPdfAttachment($pdo, $orderId);
            if (!is_array($invoiceAttachment)
                || strncmp((string)($invoiceAttachment['data'] ?? ''), '%PDF-', 5) !== 0) {
                throw new RuntimeException('Invoice PDF unavailable for order ' . $orderId);
            }
            unset($invoiceAttachment);
        } else {
            error_log('RAC shop invoice outside supported pilot scope; order=' . $orderId);
        }

        // Physical-only orders do not need digital fulfilment or digital config.
        $check = $pdo->prepare(
            "SELECT COUNT(*) FROM shop_order_items i "
            . "JOIN product_variants v ON v.id = i.variant_id "
            . "WHERE i.order_id = ? AND v.kind IN ('ebook','audio')"
        );
        $check->execute(array($orderId));
        if ((int)$check->fetchColumn() > 0) {
            // Serialize digital grants for the same order, including Stripe retries.
            $pdo->beginTransaction();
            try {
                $lock = $pdo->prepare('SELECT id FROM shop_orders WHERE id = ? FOR UPDATE');
                $lock->execute(array($orderId));
                if (!$lock->fetchColumn()) {
                    throw new RuntimeException('Shop order is missing during fulfilment');
                }
                $grant = racDigitalGrant($pdo, $orderId);
                $pdo->commit();
            } catch (Throwable $grantError) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $grantError;
            }

            if ((int)$grant['digital'] > 0) {
                // Advisory lock covers checking + claiming the mail log entry
                // against concurrent Stripe requests. racDigitalSmtpEmail itself
                // skips already accepted or in-progress webhook email attempts.
                $mailLockName = 'rac_shop_digital_mail_' . $orderId;
                $lockStatement = $pdo->prepare('SELECT GET_LOCK(?, 5)');
                $lockStatement->execute(array($mailLockName));
                if ((int)$lockStatement->fetchColumn() !== 1) {
                    throw new RuntimeException('Could not obtain digital email lock');
                }
                try {
                    racDigitalSmtpEmail($pdo, $orderId, 'webhook');
                } finally {
                    $release = $pdo->prepare('SELECT RELEASE_LOCK(?)');
                    $release->execute(array($mailLockName));
                }
            }
        }
    }

    racShopWebhookReply(200, 'OK');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('RAC shop webhook failed; event=' . (string)$event['id'] . '; reason=' . $e->getMessage());
    racShopWebhookReply(500, 'Webhook processing failed');
}
