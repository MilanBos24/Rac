<?php
declare(strict_types=1);

/**
 * BOS24 Billing - Moje předplatné
 * Verze: 2026-09-11-20.42
 */

require_once __DIR__ . '/bootstrap.php';

if (empty($_SESSION['billing_csrf'])) {
    $_SESSION['billing_csrf'] = bin2hex(random_bytes(32));
}

$csrf = (string)$_SESSION['billing_csrf'];
$userId = current_user_id();

$stmt = $pdo->prepare("
    SELECT
        s.id,
        s.product_code,
        s.provider_subscription_id,
        s.status,
        s.amount_minor,
        s.currency,
        s.billing_interval,
        s.billing_interval_count,
        s.current_period_start,
        s.current_period_end,
        s.cancel_at_period_end,
        s.cancel_at,
        s.canceled_at,
        s.ended_at,
        p.name AS product_name,
        p.description AS product_description
    FROM billing_subscriptions s
    LEFT JOIN billing_products p ON p.id = s.billing_product_id
    WHERE s.provider = 'stripe'
      AND s.user_id = :user_id
    ORDER BY s.created_at DESC, s.id DESC
");

$stmt->execute([':user_id' => $userId]);
$subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

function billing_subscription_amount(int $amountMinor, string $currency): string
{
    return number_format($amountMinor / 100, 2, ',', ' ') . ' ' . strtoupper($currency);
}

function billing_subscription_interval(?string $interval, ?int $count): string
{
    $interval = (string)$interval;
    $count = (int)$count;

    if ($interval === 'month' && $count === 1) {
        return 'měsíc';
    }

    if ($interval === 'month' && $count === 6) {
        return '6 měsíců';
    }

    if ($interval === 'year' && $count === 1) {
        return 'rok';
    }

    return $count > 0 ? $count . ' × ' . $interval : $interval;
}

function billing_subscription_status_label(string $status): string
{
    switch (strtolower($status)) {
        case 'active':
            return 'Aktivní';
        case 'trialing':
            return 'Zkušební období';
        case 'past_due':
            return 'Platba po splatnosti';
        case 'unpaid':
            return 'Nezaplacené';
        case 'canceled':
            return 'Zrušené';
        case 'incomplete':
            return 'Nedokončené';
        case 'incomplete_expired':
            return 'Nedokončené – vypršelo';
        case 'paused':
            return 'Pozastavené';
        default:
            return $status !== '' ? $status : 'Neznámý stav';
    }
}

$message = trim((string)($_GET['message'] ?? ''));
$error = trim((string)($_GET['error'] ?? ''));
?>
<!doctype html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Moje předplatné</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 980px;
            margin: 50px auto;
            padding: 0 20px;
            color: #222;
        }

        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 28px;
        }

        .card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 24px;
            margin: 18px 0;
        }

        .card h2 {
            margin-top: 0;
        }

        .badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 4px;
            background: #f1f1f1;
            font-size: 13px;
            font-weight: 700;
        }

        .active {
            background: #d1e7dd;
        }

        .warning {
            background: #fff3cd;
        }

        .inactive {
            background: #f8d7da;
        }

        .detail {
            margin: 18px 0;
        }

        .detail div {
            margin: 7px 0;
        }

        .notice {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .notice-ok {
            background: #d1e7dd;
        }

        .notice-error {
            background: #f8d7da;
        }

        button {
            padding: 11px 16px;
            cursor: pointer;
            font-weight: 700;
        }

        .muted {
            color: #666;
        }

        .danger-text {
            color: #8a1c1c;
        }
    </style>
</head>
<body>

<div class="toolbar">
    <div>
        <h1>Moje předplatné</h1>
        <div class="muted">Přehled Stripe předplatného evidovaného v BOS24.</div>
    </div>

    <div>
        <a href="index.php">Zpět na Billing</a>
    </div>
</div>

<?php if ($message !== ''): ?>
    <div class="notice notice-ok">
        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <div class="notice notice-error">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if (!$subscriptions): ?>
    <p>Nemáte žádné předplatné.</p>
<?php endif; ?>

<?php foreach ($subscriptions as $subscription): ?>
    <?php
        $status = strtolower((string)$subscription['status']);
        $cancelAtPeriodEnd = (int)$subscription['cancel_at_period_end'] === 1;

        $badgeClass = 'warning';

        if (in_array($status, ['active', 'trialing'], true)) {
            $badgeClass = 'active';
        }

        if (in_array($status, ['canceled', 'unpaid', 'incomplete_expired'], true)) {
            $badgeClass = 'inactive';
        }

        $canManage = in_array(
            $status,
            ['active', 'trialing', 'past_due'],
            true
        );
    ?>

    <div class="card">
        <span class="badge <?= htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars(billing_subscription_status_label($status), ENT_QUOTES, 'UTF-8') ?>
        </span>

        <h2>
            <?= htmlspecialchars(
                (string)($subscription['product_name'] ?: $subscription['product_code']),
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </h2>

        <?php if (!empty($subscription['product_description'])): ?>
            <p class="muted">
                <?= htmlspecialchars((string)$subscription['product_description'], ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <div class="detail">
            <div>
                Cena:
                <strong>
                    <?= htmlspecialchars(
                        billing_subscription_amount(
                            (int)$subscription['amount_minor'],
                            (string)$subscription['currency']
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                    /
                    <?= htmlspecialchars(
                        billing_subscription_interval(
                            (string)$subscription['billing_interval'],
                            (int)$subscription['billing_interval_count']
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </strong>
            </div>

            <div>
                Automatické obnovení:
                <strong><?= $cancelAtPeriodEnd ? 'Ne' : 'Ano' ?></strong>
            </div>

            <?php if (!empty($subscription['current_period_end'])): ?>
                <div>
                    Aktuální období do:
                    <strong><?= htmlspecialchars((string)$subscription['current_period_end'], ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
            <?php endif; ?>

            <?php if ($cancelAtPeriodEnd): ?>
                <div class="danger-text">
                    Předplatné je nastaveno ke zrušení na konci aktuálního zaplaceného období.
                </div>
            <?php endif; ?>
        </div>

        <?php if ($canManage): ?>
            <form method="post" action="predplatne-akce.php">
                <input type="hidden"
                       name="csrf"
                       value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

                <input type="hidden"
                       name="subscription_id"
                       value="<?= (int)$subscription['id'] ?>">

                <?php if ($cancelAtPeriodEnd): ?>
                    <input type="hidden" name="action" value="resume">
                    <button type="submit">
                        Obnovit automatické prodlužování
                    </button>
                <?php else: ?>
                    <input type="hidden" name="action" value="cancel">
                    <button type="submit"
                            onclick="return confirm('Opravdu chcete zrušit automatické prodlužování? Předplatné zůstane aktivní do konce již zaplaceného období.');">
                        Zrušit na konci období
                    </button>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

</body>
</html>