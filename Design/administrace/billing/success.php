<?php
declare(strict_types=1);

/**
 * BOS24 Billing - návrat po Stripe Checkout
 * Verze: 2026-09-11-20.32
 *
 * Podporuje jednorázovou platbu i předplatné.
 * Stav se bere pouze z databáze naplněné ověřeným webhookem.
 */

require_once __DIR__ . '/bootstrap.php';

$sessionId = trim((string)($_GET['session_id'] ?? ''));
$check = isset($_GET['check']) ? max(0, (int)$_GET['check']) : 0;

if ($sessionId === '' || strpos($sessionId, 'cs_') !== 0 || strlen($sessionId) > 255) {
    http_response_code(400);
    exit('Neplatné ID Checkout Session.');
}

$userId = current_user_id();

$stmt = $pdo->prepare("
    SELECT
        id,
        status,
        amount_minor,
        currency,
        paid_at,
        product_code
    FROM billing_payments
    WHERE provider = 'stripe'
      AND provider_checkout_session_id = :session_id
      AND user_id = :user_id
    LIMIT 1
");

$stmt->execute([
    ':session_id' => $sessionId,
    ':user_id' => $userId,
]);

$payment = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

$stmt = $pdo->prepare("
    SELECT
        s.id,
        s.status,
        s.amount_minor,
        s.currency,
        s.billing_interval,
        s.billing_interval_count,
        s.current_period_end,
        s.product_code,
        p.name AS product_name
    FROM billing_subscriptions s
    LEFT JOIN billing_products p ON p.id = s.billing_product_id
    WHERE s.provider = 'stripe'
      AND s.checkout_session_id = :session_id
      AND s.user_id = :user_id
    LIMIT 1
");

$stmt->execute([
    ':session_id' => $sessionId,
    ':user_id' => $userId,
]);

$subscription = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

function billing_format_amount(int $amountMinor, string $currency): string
{
    return number_format($amountMinor / 100, 2, ',', ' ') . ' ' . strtoupper($currency);
}

function billing_interval_label(?string $interval, ?int $count): string
{
    $interval = (string)$interval;
    $count = (int)$count;

    if ($interval === 'month' && $count === 1) {
        return 'měsíčně';
    }

    if ($interval === 'month' && $count === 6) {
        return 'každých 6 měsíců';
    }

    if ($interval === 'year' && $count === 1) {
        return 'ročně';
    }

    return trim($interval . ' × ' . $count);
}

$found = $payment || $subscription;
$shouldRefresh = !$found && $check < 5;
$nextCheck = $check + 1;

$headline = 'Platbu ověřujeme';
$message = 'Čekáme na potvrzení ze Stripe.';
$badge = 'OVĚŘOVÁNÍ';
$badgeClass = 'waiting';

if ($subscription) {
    $status = strtolower((string)$subscription['status']);

    if (in_array($status, ['active', 'trialing'], true)) {
        $headline = 'Předplatné bylo aktivováno';
        $message = 'Stripe předplatné potvrdil a BOS24 jej bezpečně zaevidoval.';
        $badge = 'AKTIVNÍ';
        $badgeClass = 'ok';
    } elseif (in_array($status, ['canceled', 'unpaid', 'incomplete_expired'], true)) {
        $headline = 'Předplatné není aktivní';
        $message = 'Aktuální stav předplatného neumožňuje jeho používání.';
        $badge = strtoupper($status);
        $badgeClass = 'error';
    } else {
        $headline = 'Předplatné se zpracovává';
        $message = 'Stripe ještě dokončuje aktivaci předplatného.';
        $badge = strtoupper($status ?: 'PENDING');
    }
} elseif ($payment) {
    $status = strtolower((string)$payment['status']);

    if ($status === 'paid') {
        $headline = 'Platba byla úspěšně potvrzena';
        $message = 'Stripe platbu potvrdil a BOS24 ji bezpečně zaevidoval.';
        $badge = 'ZAPLACENO';
        $badgeClass = 'ok';
    } elseif ($status === 'failed') {
        $headline = 'Platba nebyla dokončena';
        $message = 'Stripe oznámil, že platba nebyla úspěšná.';
        $badge = 'NEÚSPĚŠNÉ';
        $badgeClass = 'error';
    }
}
?>
<!doctype html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($headline, ENT_QUOTES, 'UTF-8') ?></title>

    <?php if ($shouldRefresh): ?>
        <meta http-equiv="refresh"
              content="2;url=success.php?session_id=<?= rawurlencode($sessionId) ?>&check=<?= $nextCheck ?>">
    <?php endif; ?>

    <style>
        body { font-family:Arial,sans-serif; max-width:760px; margin:60px auto; padding:0 20px; color:#222; }
        .box { border:1px solid #ddd; border-radius:8px; padding:28px; }
        .status { display:inline-block; padding:5px 10px; border-radius:4px; background:#f1f1f1; font-size:13px; font-weight:700; }
        .ok { background:#d1e7dd; }
        .error { background:#f8d7da; }
        .waiting { background:#fff3cd; }
        .detail { margin-top:24px; padding-top:18px; border-top:1px solid #eee; }
        .detail div { margin:7px 0; }
        code { background:#f5f5f5; padding:2px 5px; }
    </style>
</head>
<body>
<div class="box">
    <span class="status <?= htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8') ?>">
        <?= htmlspecialchars($badge, ENT_QUOTES, 'UTF-8') ?>
    </span>

    <h1><?= htmlspecialchars($headline, ENT_QUOTES, 'UTF-8') ?></h1>
    <p><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>

    <?php if ($subscription): ?>
        <div class="detail">
            <?php if (!empty($subscription['product_name'])): ?>
                <div>Produkt:
                    <strong><?= htmlspecialchars((string)$subscription['product_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
            <?php endif; ?>

            <div>Cena:
                <strong><?= htmlspecialchars(
                    billing_format_amount((int)$subscription['amount_minor'], (string)$subscription['currency']),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?></strong>
            </div>

            <div>Interval:
                <strong><?= htmlspecialchars(
                    billing_interval_label(
                        (string)$subscription['billing_interval'],
                        (int)$subscription['billing_interval_count']
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?></strong>
            </div>

            <div>Stav:
                <strong><?= htmlspecialchars((string)$subscription['status'], ENT_QUOTES, 'UTF-8') ?></strong>
            </div>

            <?php if (!empty($subscription['current_period_end'])): ?>
                <div>Aktuální období do:
                    <strong><?= htmlspecialchars((string)$subscription['current_period_end'], ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
            <?php endif; ?>

            <div>Checkout Session:
                <code><?= htmlspecialchars($sessionId, ENT_QUOTES, 'UTF-8') ?></code>
            </div>
        </div>

    <?php elseif ($payment): ?>
        <div class="detail">
            <div>Částka:
                <strong><?= htmlspecialchars(
                    billing_format_amount((int)$payment['amount_minor'], (string)$payment['currency']),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?></strong>
            </div>

            <div>Stav:
                <strong><?= htmlspecialchars((string)$payment['status'], ENT_QUOTES, 'UTF-8') ?></strong>
            </div>

            <?php if (!empty($payment['paid_at'])): ?>
                <div>Potvrzeno:
                    <strong><?= htmlspecialchars((string)$payment['paid_at'], ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
            <?php endif; ?>

            <div>Checkout Session:
                <code><?= htmlspecialchars($sessionId, ENT_QUOTES, 'UTF-8') ?></code>
            </div>
        </div>

    <?php elseif ($shouldRefresh): ?>
        <p>Probíhá automatická kontrola potvrzení…</p>
    <?php else: ?>
        <p>Potvrzení zatím nebylo nalezeno. Zkontrolujte doručení webhooku ve Stripe.</p>
    <?php endif; ?>

    <p style="margin-top:28px"><a href="index.php">Zpět na Billing</a></p>
</div>
</body>
</html>