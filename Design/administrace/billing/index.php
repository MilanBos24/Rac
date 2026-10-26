<?php
declare(strict_types=1);

/**
 * BOS24 Billing - katalog plateb a předplatného
 * Verze: 2026-09-11-20.32
 */

require_once __DIR__ . '/bootstrap.php';

if (empty($_SESSION['billing_csrf'])) {
    $_SESSION['billing_csrf'] = bin2hex(random_bytes(32));
}

$csrf = (string)$_SESSION['billing_csrf'];

$stmt = $pdo->query("
    SELECT
        code,
        name,
        description,
        amount_minor,
        currency,
        payment_type,
        billing_interval,
        billing_interval_count
    FROM billing_products
    WHERE active = 1
      AND provider = 'stripe'
    ORDER BY
        CASE WHEN payment_type = 'one_off' THEN 1 ELSE 2 END,
        sort_order ASC,
        id ASC
");

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$oneOff = [];
$subscriptions = [];

foreach ($products as $product) {
    if ((string)$product['payment_type'] === 'subscription') {
        $subscriptions[] = $product;
    } else {
        $oneOff[] = $product;
    }
}

function billing_product_price(int $amountMinor, string $currency): string
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

    if ($interval !== '' && $count > 0) {
        return $interval . ' × ' . $count;
    }

    return '';
}
?>
<!doctype html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BOS24 Billing DEV</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 980px; margin: 60px auto; padding: 0 20px; color: #222; }
        .badge { display:inline-block; padding:4px 8px; background:#fff3cd; border-radius:4px; font-size:13px; }
        .section { margin-top:38px; }
        .product { border:1px solid #ddd; border-radius:8px; padding:24px; margin:18px 0; }
        .product h2 { margin-top:0; }
        .price { font-size:22px; font-weight:700; margin:18px 0 5px; }
        .interval { margin-bottom:18px; font-weight:700; }
        .muted { color:#666; }
        button { padding:12px 20px; cursor:pointer; font-weight:700; }
    </style>
</head>
<body>
    <span class="badge">STRIPE SANDBOX / TEST</span>
    <h1>BOS24 Billing</h1>

    <?php if ($oneOff): ?>
        <div class="section">
            <h2>Jednorázové platby</h2>

            <?php foreach ($oneOff as $product): ?>
                <div class="product">
                    <h2><?= htmlspecialchars((string)$product['name'], ENT_QUOTES, 'UTF-8') ?></h2>

                    <?php if (!empty($product['description'])): ?>
                        <p class="muted"><?= htmlspecialchars((string)$product['description'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>

                    <div class="price">
                        <?= htmlspecialchars(
                            billing_product_price((int)$product['amount_minor'], (string)$product['currency']),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </div>

                    <form method="post" action="checkout.php">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="product_code" value="<?= htmlspecialchars((string)$product['code'], ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit">Zaplatit přes Stripe</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($subscriptions): ?>
        <div class="section">
            <h2>Předplatné</h2>

            <?php foreach ($subscriptions as $product): ?>
                <div class="product">
                    <h2><?= htmlspecialchars((string)$product['name'], ENT_QUOTES, 'UTF-8') ?></h2>

                    <?php if (!empty($product['description'])): ?>
                        <p class="muted"><?= htmlspecialchars((string)$product['description'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>

                    <div class="price">
                        <?= htmlspecialchars(
                            billing_product_price((int)$product['amount_minor'], (string)$product['currency']),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </div>

                    <div class="interval">
                        <?= htmlspecialchars(
                            billing_interval_label(
                                (string)$product['billing_interval'],
                                (int)$product['billing_interval_count']
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </div>

                    <form method="post" action="checkout.php">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="product_code" value="<?= htmlspecialchars((string)$product['code'], ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit">Předplatit přes Stripe</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</body>
</html>