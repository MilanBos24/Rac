<?php
declare(strict_types=1);

/**
 * RAC Billing - diagnostika platebniho systemu
 * verze 2026-09-16-22.02
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/auth_roles.php';
require_once __DIR__ . '/settings.php';

require_min_role('admin');

$viewer = current_user_row();
if (!$viewer) {
    header('Location: /logout.php');
    exit;
}

if (empty($_SESSION['csrf_billing_diagnostics'])) {
    $_SESSION['csrf_billing_diagnostics'] = bin2hex(random_bytes(32));
}

function bdiag_e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function bdiag_bool_label(bool $value): string
{
    return $value ? 'OK' : 'CHYBA';
}

function bdiag_table_exists(PDO $pdo, string $table): bool
{
    try {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM information_schema.tables
             WHERE table_schema = DATABASE()
               AND table_name = :table_name'
        );
        $stmt->execute(array(':table_name' => $table));

        return (int)$stmt->fetchColumn() > 0;
    } catch (Throwable $e) {
        error_log(
            'RAC Billing diagnostics table check error for '
            . $table
            . ': '
            . $e->getMessage()
        );

        return false;
    }
}

function bdiag_setting_present(PDO $pdo, string $key): bool
{
    try {
        return billing_setting_configured($pdo, $key);
    } catch (Throwable $e) {
        return false;
    }
}

function bdiag_masked_setting(PDO $pdo, string $key): string
{
    try {
        return billing_setting_masked($pdo, $key);
    } catch (Throwable $e) {
        return 'Nelze načíst';
    }
}

function bdiag_stripe_api_test(string $secretKey): array
{
    if (!function_exists('curl_init')) {
        return array(
            'ok' => false,
            'message' => 'PHP rozšíření cURL není dostupné.',
            'http_code' => 0,
        );
    }

    if ($secretKey === '') {
        return array(
            'ok' => false,
            'message' => 'Stripe API key není nastaven.',
            'http_code' => 0,
        );
    }

    /*
     * Používáme pouze READ požadavek na Payment Intents.
     * Pro restricted key máme tuto pravomoc již nastavenou.
     * Nevytváří se žádná platba ani jiný Stripe objekt.
     */
    $ch = curl_init('https://api.stripe.com/v1/payment_intents?limit=1');

    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_USERPWD => $secretKey . ':',
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => array(
            'Accept: application/json',
        ),
    ));

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        return array(
            'ok' => false,
            'message' => 'Spojení se Stripe selhalo: ' . $curlError,
            'http_code' => $httpCode,
        );
    }

    $decoded = json_decode((string)$response, true);

    if ($httpCode >= 200 && $httpCode < 300 && is_array($decoded)) {
        return array(
            'ok' => true,
            'message' => 'Stripe API odpovědělo správně.',
            'http_code' => $httpCode,
        );
    }

    $stripeMessage = '';
    if (
        is_array($decoded)
        && isset($decoded['error'])
        && is_array($decoded['error'])
        && isset($decoded['error']['message'])
    ) {
        $stripeMessage = trim((string)$decoded['error']['message']);
    }

    if ($stripeMessage === '') {
        $stripeMessage = 'Stripe API vrátilo neočekávanou odpověď.';
    }

    return array(
        'ok' => false,
        'message' => $stripeMessage,
        'http_code' => $httpCode,
    );
}

$errors = array();
$apiTestResult = null;

$tableReady = billing_settings_table_ready($pdo);

$mode = 'test';
$baseUrl = '';
$billingEnabled = false;
$defaultCurrency = 'CZK';
$testSecretKey = '';
$liveSecretKey = '';

if ($tableReady) {
    try {
        $mode = strtolower(trim((string)billing_setting_get($pdo, 'mode', 'test')));
        $baseUrl = rtrim(trim((string)billing_setting_get($pdo, 'base_url', '')), '/');
        $billingEnabled = (string)billing_setting_get($pdo, 'billing_enabled', '0') === '1';
        $defaultCurrency = strtoupper(trim((string)billing_setting_get($pdo, 'default_currency', 'CZK')));

        $testSecretKey = trim((string)billing_setting_get($pdo, 'stripe_test_secret_key', ''));
        $liveSecretKey = trim((string)billing_setting_get($pdo, 'stripe_live_secret_key', ''));
    } catch (Throwable $e) {
        $errors[] = 'Nastavení se nepodařilo načíst: ' . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = isset($_POST['csrf']) ? (string)$_POST['csrf'] : '';

    if (!$csrf || !hash_equals((string)$_SESSION['csrf_billing_diagnostics'], $csrf)) {
        $errors[] = 'Neplatný bezpečnostní token. Obnovte stránku a zkuste to znovu.';
    }

    $action = trim((string)($_POST['action'] ?? ''));

    if (!$errors && $action === 'test_stripe') {
        $secretKey = $mode === 'live' ? $liveSecretKey : $testSecretKey;
        $apiTestResult = bdiag_stripe_api_test($secretKey);
    }
}

$requiredTables = array(
    'billing_settings',
    'billing_products',
    'billing_customers',
    'billing_subscriptions',
    'billing_payments',
    'billing_webhook_events',
);

$tableChecks = array();

foreach ($requiredTables as $tableName) {
    $tableChecks[$tableName] = bdiag_table_exists($pdo, $tableName);
}

$opensslAvailable = function_exists('openssl_encrypt') && function_exists('openssl_decrypt');
$curlAvailable = function_exists('curl_init');
$masterKeyExists = billing_master_key_exists();

$masterKeyReadable = false;
if ($masterKeyExists) {
    try {
        billing_master_key(false);
        $masterKeyReadable = true;
    } catch (Throwable $e) {
        $masterKeyReadable = false;
    }
}

$baseUrlValid = (
    $baseUrl !== ''
    && filter_var($baseUrl, FILTER_VALIDATE_URL)
    && stripos($baseUrl, 'https://') === 0
);

$testApiKeyConfigured = bdiag_setting_present($pdo, 'stripe_test_secret_key');
$testWebhookConfigured = bdiag_setting_present($pdo, 'stripe_test_webhook_secret');
$liveApiKeyConfigured = bdiag_setting_present($pdo, 'stripe_live_secret_key');
$liveWebhookConfigured = bdiag_setting_present($pdo, 'stripe_live_webhook_secret');

$activeApiConfigured = $mode === 'live' ? $liveApiKeyConfigured : $testApiKeyConfigured;
$activeWebhookConfigured = $mode === 'live' ? $liveWebhookConfigured : $testWebhookConfigured;

$allTablesReady = !in_array(false, $tableChecks, true);

$systemReady = (
    $allTablesReady
    && $opensslAvailable
    && $curlAvailable
    && $masterKeyReadable
    && $baseUrlValid
    && $activeApiConfigured
    && $activeWebhookConfigured
);

$webhookUrl = $baseUrl !== '' ? $baseUrl . '/webhook.php' : '';

include __DIR__ . '/obsah/admin-diagnostika.class.php';