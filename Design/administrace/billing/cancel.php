<?php
declare(strict_types=1);

/**
 * BOS24 Billing
 * Verze: 2026-09-11-17.36
 *
 * Návratová stránka po zrušení Stripe Checkout.
 */

require_once __DIR__ . '/bootstrap.php';
?>
<!doctype html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Platba zrušena</title>
</head>
<body style="font-family:Arial,sans-serif;max-width:760px;margin:60px auto;padding:0 20px">
    <h1>Platba byla zrušena</h1>
    <p>Nedošlo k dokončení Stripe Checkout.</p>
    <p><a href="index.php">Zkusit znovu</a></p>
</body>
</html>