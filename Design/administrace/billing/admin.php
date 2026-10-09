<?php
declare(strict_types=1);

/**
 * RAC Billing - administrace platebniho systemu
 * verze 2026-09-16-20.33
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

if (empty($_SESSION['csrf_billing_admin'])) {
    $_SESSION['csrf_billing_admin'] = bin2hex(random_bytes(32));
}

function billing_admin_e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function billing_admin_bool($value): bool
{
    return (string)$value === '1';
}

function billing_admin_redirect_saved(): void
{
    header('Location: admin.php?saved=1');
    exit;
}

$errors = array();
$success = isset($_GET['saved']) && (string)$_GET['saved'] === '1'
    ? 'Nastavení platebního systému bylo uloženo.'
    : '';

$tableReady = billing_settings_table_ready($pdo);

$values = array(
    'billing_enabled' => '0',
    'mode' => 'test',
    'base_url' => '',
    'default_currency' => 'CZK',
);

if ($tableReady) {
    try {
        $values['billing_enabled'] = (string)billing_setting_get($pdo, 'billing_enabled', '0');
        $values['mode'] = (string)billing_setting_get($pdo, 'mode', 'test');
        $values['base_url'] = (string)billing_setting_get($pdo, 'base_url', '');
        $values['default_currency'] = strtoupper((string)billing_setting_get($pdo, 'default_currency', 'CZK'));
    } catch (Throwable $e) {
        $errors[] = 'Některá nastavení se nepodařilo načíst: ' . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$tableReady) {
        $errors[] = 'Tabulka billing_settings neexistuje. Nejdříve importujte billing/database/billing-settings.sql.';
    }

    $csrf = isset($_POST['csrf']) ? (string)$_POST['csrf'] : '';
    if (!$csrf || !hash_equals((string)$_SESSION['csrf_billing_admin'], $csrf)) {
        $errors[] = 'Neplatný bezpečnostní token. Obnovte stránku a zkuste to znovu.';
    }

    $values['billing_enabled'] = !empty($_POST['billing_enabled']) ? '1' : '0';
    $values['mode'] = trim((string)($_POST['mode'] ?? 'test'));
    $values['base_url'] = rtrim(trim((string)($_POST['base_url'] ?? '')), '/');
    $values['default_currency'] = strtoupper(trim((string)($_POST['default_currency'] ?? 'CZK')));

    if (!in_array($values['mode'], array('test', 'live'), true)) {
        $errors[] = 'Režim musí být Sandbox nebo Live.';
    }

    if ($values['base_url'] === '' || !filter_var($values['base_url'], FILTER_VALIDATE_URL)) {
        $errors[] = 'Zadejte platnou Base URL, například https://domena.cz/billing.';
    } elseif (stripos($values['base_url'], 'https://') !== 0) {
        $errors[] = 'Base URL musí používat HTTPS.';
    }

    if (!preg_match('/^[A-Z]{3}$/', $values['default_currency'])) {
        $errors[] = 'Výchozí měna musí být třípísmenný kód, například CZK.';
    }

    $secretInputs = array(
        'stripe_test_secret_key' => trim((string)($_POST['stripe_test_secret_key'] ?? '')),
        'stripe_test_publishable_key' => trim((string)($_POST['stripe_test_publishable_key'] ?? '')),
        'stripe_test_webhook_secret' => trim((string)($_POST['stripe_test_webhook_secret'] ?? '')),
        'stripe_live_secret_key' => trim((string)($_POST['stripe_live_secret_key'] ?? '')),
        'stripe_live_publishable_key' => trim((string)($_POST['stripe_live_publishable_key'] ?? '')),
        'stripe_live_webhook_secret' => trim((string)($_POST['stripe_live_webhook_secret'] ?? '')),
    );

    if ($secretInputs['stripe_test_secret_key'] !== ''
        && strpos($secretInputs['stripe_test_secret_key'], 'rk_test_') !== 0
        && strpos($secretInputs['stripe_test_secret_key'], 'sk_test_') !== 0
    ) {
        $errors[] = 'Sandbox Secret/Restricted key musí začínat rk_test_ nebo sk_test_.';
    }

    if ($secretInputs['stripe_test_publishable_key'] !== ''
        && strpos($secretInputs['stripe_test_publishable_key'], 'pk_test_') !== 0
    ) {
        $errors[] = 'Sandbox Publishable key musí začínat pk_test_.';
    }

    if ($secretInputs['stripe_test_webhook_secret'] !== ''
        && strpos($secretInputs['stripe_test_webhook_secret'], 'whsec_') !== 0
    ) {
        $errors[] = 'Sandbox Webhook signing secret musí začínat whsec_.';
    }

    if ($secretInputs['stripe_live_secret_key'] !== ''
        && strpos($secretInputs['stripe_live_secret_key'], 'rk_live_') !== 0
        && strpos($secretInputs['stripe_live_secret_key'], 'sk_live_') !== 0
    ) {
        $errors[] = 'Live Secret/Restricted key musí začínat rk_live_ nebo sk_live_.';
    }

    if ($secretInputs['stripe_live_publishable_key'] !== ''
        && strpos($secretInputs['stripe_live_publishable_key'], 'pk_live_') !== 0
    ) {
        $errors[] = 'Live Publishable key musí začínat pk_live_.';
    }

    if ($secretInputs['stripe_live_webhook_secret'] !== ''
        && strpos($secretInputs['stripe_live_webhook_secret'], 'whsec_') !== 0
    ) {
        $errors[] = 'Live Webhook signing secret musí začínat whsec_.';
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            $updatedBy = isset($viewer['id']) ? (int)$viewer['id'] : null;

            billing_setting_set($pdo, 'billing_enabled', $values['billing_enabled'], false, $updatedBy);
            billing_setting_set($pdo, 'mode', $values['mode'], false, $updatedBy);
            billing_setting_set($pdo, 'base_url', $values['base_url'], false, $updatedBy);
            billing_setting_set($pdo, 'default_currency', $values['default_currency'], false, $updatedBy);

            foreach ($secretInputs as $key => $secretValue) {
                // Prazdne pole znamena "ponechat stavajici hodnotu".
                if ($secretValue !== '') {
                    billing_setting_set($pdo, $key, $secretValue, true, $updatedBy);
                }
            }

            $pdo->commit();
            billing_admin_redirect_saved();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'Nastavení se nepodařilo uložit: ' . $e->getMessage();
        }
    }
}

$maskedSecrets = array(
    'stripe_test_secret_key' => 'Nenastaveno',
    'stripe_test_publishable_key' => 'Nenastaveno',
    'stripe_test_webhook_secret' => 'Nenastaveno',
    'stripe_live_secret_key' => 'Nenastaveno',
    'stripe_live_publishable_key' => 'Nenastaveno',
    'stripe_live_webhook_secret' => 'Nenastaveno',
);

if ($tableReady) {
    foreach (array_keys($maskedSecrets) as $settingKey) {
        $maskedSecrets[$settingKey] = billing_setting_masked($pdo, $settingKey);
    }
}

$webhookUrl = $values['base_url'] !== ''
    ? rtrim($values['base_url'], '/') . '/webhook.php'
    : '';

include __DIR__ . '/obsah/admin.class.php';