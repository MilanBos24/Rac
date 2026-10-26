<?php
declare(strict_types=1);

/**
 * BOS24 Billing - správa předplatného
 * Verze: 2026-09-11-20.42
 *
 * Podporované akce:
 * - cancel  => cancel_at_period_end=true
 * - resume  => cancel_at_period_end=false
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

$localSubscriptionId = isset($_POST['subscription_id'])
    ? (int)$_POST['subscription_id']
    : 0;

$action = trim((string)($_POST['action'] ?? ''));

if ($localSubscriptionId <= 0 || !in_array($action, ['cancel', 'resume'], true)) {
    http_response_code(400);
    exit('Neplatný požadavek.');
}

$userId = current_user_id();

$stmt = $pdo->prepare("
    SELECT
        id,
        provider_subscription_id,
        status,
        cancel_at_period_end
    FROM billing_subscriptions
    WHERE id = :id
      AND provider = 'stripe'
      AND user_id = :user_id
    LIMIT 1
");

$stmt->execute([
    ':id' => $localSubscriptionId,
    ':user_id' => $userId,
]);

$subscription = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$subscription) {
    http_response_code(404);
    exit('Předplatné nebylo nalezeno.');
}

$status = strtolower((string)$subscription['status']);

if (!in_array($status, ['active', 'trialing', 'past_due'], true)) {
    header(
        'Location: moje-predplatne.php?error='
        . rawurlencode('Toto předplatné již nelze tímto způsobem upravit.')
    );
    exit;
}

$stripeSubscriptionId = trim((string)$subscription['provider_subscription_id']);

if ($stripeSubscriptionId === '' || strpos($stripeSubscriptionId, 'sub_') !== 0) {
    http_response_code(500);
    exit('Chybí platné Stripe Subscription ID.');
}

$cancelAtPeriodEnd = $action === 'cancel' ? 'true' : 'false';

$ch = curl_init(
    'https://api.stripe.com/v1/subscriptions/'
    . rawurlencode($stripeSubscriptionId)
);

curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $secretKey,
        'Content-Type: application/x-www-form-urlencoded',
    ],
    CURLOPT_POSTFIELDS => http_build_query(
        ['cancel_at_period_end' => $cancelAtPeriodEnd],
        '',
        '&',
        PHP_QUERY_RFC3986
    ),
]);

$response = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false) {
    error_log('BOS24 Stripe subscription update cURL error: ' . $curlError);

    header(
        'Location: moje-predplatne.php?error='
        . rawurlencode('Nepodařilo se spojit se Stripe.')
    );
    exit;
}

$data = json_decode($response, true);

if (!is_array($data)) {
    header(
        'Location: moje-predplatne.php?error='
        . rawurlencode('Stripe vrátil neplatnou odpověď.')
    );
    exit;
}

if ($httpCode < 200 || $httpCode >= 300) {
    $stripeMessage = trim((string)($data['error']['message'] ?? 'Stripe API chyba.'));

    error_log(
        'BOS24 Stripe subscription update error HTTP '
        . $httpCode
        . ': '
        . $stripeMessage
    );

    header(
        'Location: moje-predplatne.php?error='
        . rawurlencode($stripeMessage)
    );
    exit;
}

$returnedSubscriptionId = trim((string)($data['id'] ?? ''));

if ($returnedSubscriptionId !== $stripeSubscriptionId) {
    http_response_code(502);
    exit('Stripe vrátil neočekávané předplatné.');
}

$remoteCancelAtPeriodEnd = !empty($data['cancel_at_period_end']) ? 1 : 0;

$periodEnd = null;

if (isset($data['current_period_end']) && is_numeric($data['current_period_end'])) {
    $periodEnd = date('Y-m-d H:i:s', (int)$data['current_period_end']);
} elseif (
    isset($data['items']['data'][0]['current_period_end'])
    && is_numeric($data['items']['data'][0]['current_period_end'])
) {
    $periodEnd = date(
        'Y-m-d H:i:s',
        (int)$data['items']['data'][0]['current_period_end']
    );
}

$stmt = $pdo->prepare("
    UPDATE billing_subscriptions
    SET cancel_at_period_end = :cancel_at_period_end,
        current_period_end = COALESCE(:period_end, current_period_end),
        updated_at = NOW()
    WHERE id = :id
      AND provider = 'stripe'
      AND user_id = :user_id
    LIMIT 1
");

$stmt->execute([
    ':cancel_at_period_end' => $remoteCancelAtPeriodEnd,
    ':period_end' => $periodEnd,
    ':id' => $localSubscriptionId,
    ':user_id' => $userId,
]);

if ($action === 'cancel') {
    $message = 'Automatické prodlužování bylo zrušeno. Předplatné zůstává aktivní do konce zaplaceného období.';
} else {
    $message = 'Automatické prodlužování předplatného bylo obnoveno.';
}

header(
    'Location: moje-predplatne.php?message='
    . rawurlencode($message)
);
exit;