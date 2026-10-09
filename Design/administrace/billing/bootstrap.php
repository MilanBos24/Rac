<?php
declare(strict_types=1);

/**
 * RAC Billing - runtime bootstrap
 * Verze: 2026-09-16-21.05
 *
 * Nastaveni Stripe se nacita z billing_settings.
 * config.local.php uz tato verze nepouziva.
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/auth_roles.php';
require_once __DIR__ . '/settings.php';

require_min_role('admin');

$user = current_user_row();

if (!$user) {
    header('Location: /logout.php');
    exit;
}

/*
 * DOCASNE omezeni vyvojoveho Billingu.
 * Az budeme zapojovat tarify pro bezne uzivatele, tuto podminku zmenime.
 */
if (!can_access_min('admin')) {
    http_response_code(403);
    exit('K platebnímu modulu nemáte oprávnění.');
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    exit('Database connection is not available.');
}

if (!billing_settings_table_ready($pdo)) {
    http_response_code(500);
    exit('Billing settings table is not available.');
}

try {
    $billingEnabled = (string)billing_setting_get($pdo, 'billing_enabled', '0') === '1';
    $mode = strtolower(trim((string)billing_setting_get($pdo, 'mode', 'test')));
    $baseUrl = rtrim(trim((string)billing_setting_get($pdo, 'base_url', '')), '/');
    $defaultCurrency = strtoupper(trim((string)billing_setting_get($pdo, 'default_currency', 'CZK')));

    if (!in_array($mode, array('test', 'live'), true)) {
        throw new RuntimeException('Neplatný režim platebního systému.');
    }

    if ($baseUrl === '' || !filter_var($baseUrl, FILTER_VALIDATE_URL) || stripos($baseUrl, 'https://') !== 0) {
        throw new RuntimeException('Billing Base URL není správně nastavena.');
    }

    if ($mode === 'live') {
        $secretKey = trim((string)billing_setting_get($pdo, 'stripe_live_secret_key', ''));
        $publishableKey = trim((string)billing_setting_get($pdo, 'stripe_live_publishable_key', ''));
        $webhookSecret = trim((string)billing_setting_get($pdo, 'stripe_live_webhook_secret', ''));

        if (
            $secretKey === ''
            || (
                strpos($secretKey, 'rk_live_') !== 0
                && strpos($secretKey, 'sk_live_') !== 0
            )
        ) {
            throw new RuntimeException('Stripe Live API key není správně nastaven.');
        }

        if ($publishableKey !== '' && strpos($publishableKey, 'pk_live_') !== 0) {
            throw new RuntimeException('Stripe Live Publishable key není správně nastaven.');
        }
    } else {
        $secretKey = trim((string)billing_setting_get($pdo, 'stripe_test_secret_key', ''));
        $publishableKey = trim((string)billing_setting_get($pdo, 'stripe_test_publishable_key', ''));
        $webhookSecret = trim((string)billing_setting_get($pdo, 'stripe_test_webhook_secret', ''));

        if (
            $secretKey === ''
            || (
                strpos($secretKey, 'rk_test_') !== 0
                && strpos($secretKey, 'sk_test_') !== 0
            )
        ) {
            throw new RuntimeException('Stripe Sandbox API key není správně nastaven.');
        }

        if ($publishableKey !== '' && strpos($publishableKey, 'pk_test_') !== 0) {
            throw new RuntimeException('Stripe Sandbox Publishable key není správně nastaven.');
        }
    }

    if ($webhookSecret !== '' && strpos($webhookSecret, 'whsec_') !== 0) {
        throw new RuntimeException('Stripe Webhook signing secret není správně nastaven.');
    }

    /*
     * Kompatibilni pole pro pripadne starsi soubory Billingu.
     * Citlive udaje vznikaji pouze v pameti behem requestu.
     */
    $config = array(
        'mode' => $mode,
        'stripe_secret_key' => $secretKey,
        'stripe_publishable_key' => $publishableKey,
        'stripe_webhook_secret' => $webhookSecret,
        'base_url' => $baseUrl,
        'default_currency' => $defaultCurrency,
        'billing_enabled' => $billingEnabled,
    );
} catch (Throwable $e) {
    error_log('RAC Billing bootstrap configuration error: ' . $e->getMessage());
    http_response_code(500);
    exit('Platební systém není správně nakonfigurován.');
}