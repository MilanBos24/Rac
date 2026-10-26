<?php
declare(strict_types=1);
/** RAC Billing – bezpečný test Stripe Sandbox API, verze 2026-10-14-14.22 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/auth_roles.php';
require_once __DIR__ . '/settings.php';

require_min_role('admin');
if (!current_user_row()) {
    header('Location: /logout.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method Not Allowed');
}

$csrf = (string)($_POST['csrf'] ?? '');
if ($csrf === '' || empty($_SESSION['csrf_billing_admin']) ||
    !hash_equals((string)$_SESSION['csrf_billing_admin'], $csrf)) {
    http_response_code(403);
    exit('Neplatný bezpečnostní token. Vraťte se do administrace a obnovte stránku.');
}

$result = array('ok' => false, 'message' => 'Spojení se Stripe se nepodařilo ověřit.');
try {
    if (!isset($pdo) || !($pdo instanceof PDO) || !billing_settings_table_ready($pdo)) {
        throw new RuntimeException('Tabulka billing_settings není dostupná.');
    }
    $key = trim((string)billing_setting_get($pdo, 'stripe_test_secret_key', ''));
    if ($key === '' || (strpos($key, 'sk_test_') !== 0 && strpos($key, 'rk_test_') !== 0)) {
        throw new RuntimeException('Sandbox API klíč není nastavený nebo nemá správný formát.');
    }
    if (!function_exists('curl_init')) {
        throw new RuntimeException('Na serveru chybí PHP rozšíření cURL.');
    }
    $ch = curl_init('https://api.stripe.com/v1/account');
    if ($ch === false) {
        throw new RuntimeException('Nelze inicializovat HTTP spojení.');
    }
    curl_setopt_array($ch, array(
        CURLOPT_HTTPGET => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_HTTPHEADER => array('Authorization: Bearer ' . $key, 'Accept: application/json'),
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_MAXREDIRS => 0,
    ));
    $response = curl_exec($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_errno($ch);
    curl_close($ch);
    if ($response === false || $curlError !== 0) {
        throw new RuntimeException('Nelze kontaktovat Stripe API. Zkontrolujte síťové připojení, cURL a certifikáty serveru.');
    }
    if ($http === 200) {
        $data = json_decode((string)$response, true);
        if (!is_array($data) || ($data['object'] ?? '') !== 'account') {
            throw new RuntimeException('Stripe vrátil neočekávanou odpověď.');
        }
        $result = array('ok' => true, 'message' => 'Spojení se Stripe Sandbox API bylo úspěšně ověřeno.');
    } elseif ($http === 401) {
        throw new RuntimeException('Stripe odmítl API klíč (HTTP 401). Ověřte uložený Sandbox Secret key.');
    } elseif ($http === 403) {
        throw new RuntimeException('Stripe odmítl přístup k informacím o účtu (HTTP 403). Restricted key možná nemá oprávnění pro tento test.');
    } elseif ($http === 429) {
        throw new RuntimeException('Stripe dočasně omezil požadavky (HTTP 429).');
    } else {
        throw new RuntimeException('Stripe API vrátilo HTTP ' . $http . '.');
    }
} catch (Throwable $e) {
    error_log('RAC Stripe sandbox connectivity test failed: ' . $e->getMessage());
    $result['message'] = $e->getMessage();
}
$_SESSION['rac_stripe_test_result'] = $result;
header('Location: admin.php?tab=stav', true, 303);
exit;
