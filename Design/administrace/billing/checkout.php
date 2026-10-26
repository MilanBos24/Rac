<?php
declare(strict_types=1);

/**
 * BOS24 Billing - Stripe Checkout
 * Verze: 2026-09-11-20.32
 *
 * Podporuje:
 * - one_off
 * - subscription
 */

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method Not Allowed');
}

$postedCsrf = (string)($_POST['csrf'] ?? '');
$sessionCsrf = (string)($_SESSION['billing_csrf'] ?? '');

if ($sessionCsrf === '' || $postedCsrf === '' || !hash_equals($sessionCsrf, $postedCsrf)) {
    http_response_code(403);
    exit('Neplatný CSRF token.');
}

$productCode = trim((string)($_POST['product_code'] ?? ''));

if ($productCode === '' || !preg_match('/^[a-zA-Z0-9_-]{1,100}$/', $productCode)) {
    http_response_code(400);
    exit('Neplatný produkt.');
}

$stmt = $pdo->prepare("
    SELECT *
    FROM billing_products
    WHERE code = :code
      AND active = 1
      AND provider = 'stripe'
    LIMIT 1
");

$stmt->execute([':code' => $productCode]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    http_response_code(404);
    exit('Produkt nebyl nalezen nebo není aktivní.');
}

$paymentType = (string)$product['payment_type'];

if (!in_array($paymentType, ['one_off', 'subscription'], true)) {
    http_response_code(400);
    exit('Nepodporovaný typ platby.');
}

$priceId = trim((string)$product['provider_price_id']);

if ($priceId === '' || strpos($priceId, 'price_') !== 0) {
    http_response_code(500);
    exit('Produkt nemá platný Stripe Price ID.');
}

if (!function_exists('curl_init')) {
    http_response_code(500);
    exit('Na serveru není dostupné PHP rozšíření cURL.');
}

$userId = current_user_id();
$user = current_user_row();

$postData = [
    'mode' => $paymentType === 'subscription' ? 'subscription' : 'payment',
    'success_url' => $baseUrl . '/success.php?session_id={CHECKOUT_SESSION_ID}',
    'cancel_url' => $baseUrl . '/cancel.php',
    'line_items[0][price]' => $priceId,
    'line_items[0][quantity]' => '1',
    'locale' => 'cs',
    'client_reference_id' => 'bos24-user-' . (string)$userId,
    'metadata[integration]' => 'bos24-billing',
    'metadata[user_id]' => (string)$userId,
    'metadata[product_code]' => $productCode,
];

if ($paymentType === 'subscription') {
    // Metadata musí být i přímo na Subscription objektu,
    // aby je obsahovaly customer.subscription.* webhooky.
    $postData['subscription_data[metadata][integration]'] = 'bos24-billing';
    $postData['subscription_data[metadata][user_id]'] = (string)$userId;
    $postData['subscription_data[metadata][product_code]'] = $productCode;

    // Pokud už má uživatel Stripe Customer, znovu jej použijeme.
    $stmt = $pdo->prepare("
        SELECT provider_customer_id
        FROM billing_customers
        WHERE provider = 'stripe'
          AND user_id = :user_id
        LIMIT 1
    ");
    $stmt->execute([':user_id' => $userId]);
    $customerId = trim((string)($stmt->fetchColumn() ?: ''));

    if ($customerId !== '') {
        $postData['customer'] = $customerId;
    } elseif (!empty($user['email'])) {
        $postData['customer_email'] = (string)$user['email'];
    }
}

$ch = curl_init('https://api.stripe.com/v1/checkout/sessions');

curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $secretKey,
        'Content-Type: application/x-www-form-urlencoded',
    ],
    CURLOPT_POSTFIELDS => http_build_query($postData, '', '&', PHP_QUERY_RFC3986),
]);

$response = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false) {
    error_log('BOS24 Stripe cURL error: ' . $curlError);
    http_response_code(502);
    exit('Nepodařilo se spojit se Stripe API.');
}

$data = json_decode($response, true);

if (!is_array($data)) {
    http_response_code(502);
    exit('Stripe API vrátilo neplatnou odpověď.');
}

if ($httpCode < 200 || $httpCode >= 300) {
    $stripeMessage = (string)($data['error']['message'] ?? 'Neznámá chyba Stripe API.');
    $stripeType = (string)($data['error']['type'] ?? 'stripe_error');

    error_log('BOS24 Stripe error [' . $stripeType . ']: ' . $stripeMessage);

    http_response_code(502);
    echo '<h1>Stripe API chyba</h1>';
    echo '<p>' . htmlspecialchars($stripeMessage, ENT_QUOTES, 'UTF-8') . '</p>';
    echo '<p><a href="index.php">Zpět</a></p>';
    exit;
}

$checkoutUrl = (string)($data['url'] ?? '');

if ($checkoutUrl === '' || stripos($checkoutUrl, 'https://checkout.stripe.com/') !== 0) {
    http_response_code(502);
    exit('Stripe nevygeneroval platnou Checkout URL.');
}

unset($_SESSION['billing_csrf']);

header('Location: ' . $checkoutUrl, true, 303);
exit;