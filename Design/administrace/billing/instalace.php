<?php
declare(strict_types=1);

/**
 * RAC Billing - instalace a dokumentace
 * verze 2026-10-07-10.13
 */

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/auth_roles.php';

require_min_role('admin');

$viewer = current_user_row();

if (!$viewer) {
    header('Location: /logout.php');
    exit;
}

function bi_e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$installSql = <<<'SQL'
-- RAC Billing - kompletni instalacni schema
-- verze 2026-10-07-10.13
-- MySQL 5.6+ / PHP 7.4+
--
-- Cista instalace platebniho modulu.
-- Neobsahuje konkretni Stripe Product/Price ID.
-- Zamerne bez cizich klicu na tabulku users, aby byl modul prenositelny.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `billing_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` mediumtext NOT NULL,
  `is_secret` tinyint(1) NOT NULL DEFAULT '0',
  `updated_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`setting_key`),
  KEY `idx_billing_settings_secret` (`is_secret`),
  KEY `idx_billing_settings_updated_by` (`updated_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `billing_settings`
    (`setting_key`, `setting_value`, `is_secret`, `updated_by`, `created_at`, `updated_at`)
VALUES
    ('billing_enabled', '0', 0, NULL, NOW(), NOW()),
    ('mode', 'test', 0, NULL, NOW(), NOW()),
    ('base_url', '', 0, NULL, NOW(), NOW()),
    ('default_currency', 'CZK', 0, NULL, NOW(), NOW());


CREATE TABLE IF NOT EXISTS `billing_products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `provider` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'stripe',
  `provider_product_id` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_price_id` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'one_off',
  `amount_minor` bigint(20) NOT NULL DEFAULT '0',
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CZK',
  `billing_interval` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_interval_count` int(10) unsigned DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_billing_products_code` (`code`),
  KEY `idx_billing_products_active` (`active`),
  KEY `idx_billing_products_type` (`payment_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `billing_customers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `provider` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'stripe',
  `user_id` int(11) NOT NULL,
  `provider_customer_id` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_billing_customer_provider_user` (`provider`,`user_id`),
  UNIQUE KEY `uq_billing_customer_provider_customer` (`provider`,`provider_customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `billing_subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `provider` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'stripe',
  `user_id` int(11) NOT NULL,
  `billing_product_id` bigint(20) unsigned DEFAULT NULL,
  `product_code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_customer_id` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_subscription_id` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider_price_id` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `checkout_session_id` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `amount_minor` bigint(20) NOT NULL DEFAULT '0',
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_interval` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_interval_count` int(10) unsigned DEFAULT NULL,
  `current_period_start` datetime DEFAULT NULL,
  `current_period_end` datetime DEFAULT NULL,
  `cancel_at_period_end` tinyint(1) NOT NULL DEFAULT '0',
  `cancel_at` datetime DEFAULT NULL,
  `canceled_at` datetime DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_billing_subscription_provider_id` (`provider`,`provider_subscription_id`),
  KEY `idx_billing_subscription_user` (`user_id`),
  KEY `idx_billing_subscription_status` (`status`),
  KEY `idx_billing_subscription_checkout` (`checkout_session_id`),
  KEY `idx_billing_subscription_product` (`product_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `billing_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `provider` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'stripe',
  `payment_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'one_off',
  `user_id` int(11) DEFAULT NULL,
  `provider_checkout_session_id` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_payment_intent_id` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_invoice_id` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_subscription_id` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_customer_id` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `amount_minor` bigint(20) NOT NULL DEFAULT '0',
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `client_reference_id` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `integration_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `product_code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_billing_payment_provider_session` (`provider`,`provider_checkout_session_id`),
  UNIQUE KEY `uq_billing_payment_provider_invoice` (`provider`,`provider_invoice_id`),
  KEY `idx_billing_payment_user` (`user_id`),
  KEY `idx_billing_payment_status` (`status`),
  KEY `idx_billing_payment_intent` (`provider_payment_intent_id`),
  KEY `idx_billing_payment_subscription` (`provider_subscription_id`),
  KEY `idx_billing_payment_product_code` (`product_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `billing_webhook_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `provider` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'stripe',
  `event_id` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_type` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `livemode` tinyint(1) NOT NULL DEFAULT '0',
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'received',
  `object_id` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `received_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_billing_webhook_provider_event` (`provider`,`event_id`),
  KEY `idx_billing_webhook_type` (`event_type`),
  KEY `idx_billing_webhook_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Po importu:
-- 1) otevrit /billing/admin.php,
-- 2) nastavit Base URL + Stripe Sandbox,
-- 3) ulozit - pri prvnim ulozeni citliveho udaje vznikne /billing/storage/.billing.key,
-- 4) produkty zadat pres /billing/admin-produkty.php.

SQL;

$webhookEvents = array(
    'checkout.session.completed',
    'checkout.session.async_payment_succeeded',
    'checkout.session.async_payment_failed',
    'customer.subscription.created',
    'customer.subscription.updated',
    'customer.subscription.deleted',
    'invoice.paid',
    'invoice.payment_failed',
);

$requiredFiles = array(
    '/billing/bootstrap.php',
    '/billing/settings.php',
    '/billing/index.php',
    '/billing/checkout.php',
    '/billing/success.php',
    '/billing/cancel.php',
    '/billing/webhook.php',
    '/billing/moje-predplatne.php',
    '/billing/predplatne-akce.php',
    '/billing/admin.php',
    '/billing/admin-produkty.php',
    '/billing/admin-platby.php',
    '/billing/admin-predplatne.php',
    '/billing/admin-webhooky.php',
    '/billing/admin-diagnostika.php',
    '/billing/instalace.php',
    '/billing/obsah/',
    '/billing/storage/.htaccess',
    '/billing/storage/.gitignore',
    '/billing/database/billing-install.sql',
);

$integrationExample = <<<'PHP'
<?php
require_once __DIR__ . '/config/session.php';

// Stejny session mechanism, jaky pouziva billing/bootstrap.php.
if (empty($_SESSION['billing_csrf'])) {
    $_SESSION['billing_csrf'] = bin2hex(random_bytes(32));
}

$csrf = (string)$_SESSION['billing_csrf'];
?>

<form method="post" action="/billing/checkout.php">
    <input type="hidden"
           name="csrf"
           value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

    <input type="hidden"
           name="product_code"
           value="eshop_ebook_001">

    <button type="submit">Zaplatit</button>
</form>
PHP;
?>
<!doctype html><html lang="cs"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>RAC – Instalace Billing</title><link rel="stylesheet" href="../assets/css/admin.css?v=2026-10-09-billing"></head><body><div class="admin-app"><header class="admin-header"><div class="admin-brand"><a href="../dashboard.php"><strong>RAC</strong> Administrace</a></div></header><?php include __DIR__.'/../inc/leve-meny.class.php'; ?><main class="admin-content"><section role="main" class="content-body content-body-modern mt-0 billing-docs">
    <header class="page-header">
        <h2>Platby – instalace a dokumentace</h2>
        <div class="right-wrapper text-end">
            <ol class="breadcrumbs">
                <li><a href="/billing/admin.php"><i class="bx bx-home-alt"></i></a></li>
                <li><span>Platby</span></li>
                <li><span>Instalace</span></li>
            </ol>
            <a class="sidebar-right-toggle" data-open="sidebar-right"><i class="fas fa-chevron-left"></i></a>
        </div>
    </header>

    <div class="row align-items-center pt-2 mb-3">
        <div class="col-12 d-flex flex-wrap">
            <a href="/billing/admin.php" class="btn btn-default me-2 mb-2">
                <i class="fas fa-cog me-1"></i> Nastavení
            </a>
            <a href="/billing/admin-produkty.php" class="btn btn-default me-2 mb-2">
                <i class="fas fa-box me-1"></i> Produkty
            </a>
            <a href="/billing/admin-platby.php" class="btn btn-default me-2 mb-2">
                <i class="fas fa-credit-card me-1"></i> Platby
            </a>
            <a href="/billing/admin-predplatne.php" class="btn btn-default me-2 mb-2">
                <i class="fas fa-sync-alt me-1"></i> Předplatná
            </a>
            <a href="/billing/admin-webhooky.php" class="btn btn-default me-2 mb-2">
                <i class="fas fa-random me-1"></i> Webhooky
            </a>
            <a href="/billing/admin-diagnostika.php" class="btn btn-default me-2 mb-2">
                <i class="fas fa-stethoscope me-1"></i> Diagnostika
            </a>
            <a href="/billing/instalace.php" class="btn btn-primary me-2 mb-2">
                <i class="fas fa-book me-1"></i> Instalace
            </a>
        </div>
    </div>

    <div class="alert alert-info">
        <strong>Účel stránky:</strong> kompletní provozní dokumentace RAC Billing pro novou instalaci,
        implementaci do jiného e-shopu a opětovné nastavení Stripe. Dokumentace odpovídá stavu modulu
        k 7. 10. 2026. Stripe může časem přejmenovat položky Dashboardu; v novějším rozhraní se
        „Developers / Webhooks“ může zobrazovat jako „Workbench / Webhooks“ nebo „Event destinations“.
    </div>

    <div class="tabs">
        <ul class="nav nav-tabs tabs-primary">
            <li class="nav-item">
                <button class="nav-link active"
                        data-bs-target="#tab-implementace"
                        data-bs-toggle="tab"
                        type="button">
                    A) Implementace do e-shopu
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link"
                        data-bs-target="#tab-stripe"
                        data-bs-toggle="tab"
                        type="button">
                    B) Nastavení Stripe
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link"
                        data-bs-target="#tab-sql"
                        data-bs-toggle="tab"
                        type="button">
                    C) SQL instalace
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <div id="tab-implementace" class="tab-pane fade show active">
                <section class="card mb-0">
                    <header class="card-header">
                        <h2 class="card-title">Implementace RAC Billing do e-shopu</h2>
                    </header>

                    <div class="card-body">
                        <div class="alert alert-warning">
                            <strong>Důležité:</strong> současná verze Billingu počítá s přihlášeným uživatelem.
                            <code>bootstrap.php</code> volá <code>require_login()</code> a Checkout zapisuje
                            <code>current_user_id()</code>. Pro anonymní/guest objednávky je nutné integrační vrstvu upravit.
                        </div>

                        <div class="doc-step" id="impl-1">
                            <h4>1. Požadavky serveru</h4>
                            <ul class="checklist">
                                <li>PHP 7.4 nebo novější kompatibilní verze.</li>
                                <li>MySQL 5.6+ / MariaDB s InnoDB a utf8mb4.</li>
                                <li>PHP PDO MySQL.</li>
                                <li>PHP cURL pro komunikaci se Stripe REST API.</li>
                                <li>PHP OpenSSL pro šifrování Stripe klíčů.</li>
                                <li>HTTPS na veřejné doméně.</li>
                                <li>Funkční PHP session.</li>
                            </ul>
                        </div>

                        <div class="doc-step" id="impl-2">
                            <h4>2. Nahrajte billing modul</h4>
                            <p>Celou složku <code>/billing/</code> nahrajte do kořene aplikace/e-shopu. Minimální sada:</p>

                            <div class="row">
                                <?php foreach ($requiredFiles as $file): ?>
                                    <div class="col-lg-6">
                                        <code><?= bi_e($file) ?></code>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="alert alert-secondary mt-3 mb-0">
                                <code>/billing/storage/.billing.key</code> se do distribučního balíčku nekopíruje.
                                Na nové instalaci se vytvoří nový klíč při prvním uložení citlivého Stripe údaje.
                            </div>
                        </div>

                        <div class="doc-step" id="impl-3">
                            <h4>3. Připojte Billing k autentizaci e-shopu</h4>
                            <p>
                                <code>billing/bootstrap.php</code> očekává následující soubory a funkce:
                            </p>
                            <ul class="checklist">
                                <li><code>/config/session.php</code> – spuštění session.</li>
                                <li><code>/config/db.php</code> – musí vytvořit PDO objekt <code>$pdo</code>.</li>
                                <li><code>/config/auth.php</code> – <code>require_login()</code>, <code>current_user_id()</code>, <code>current_user_row()</code>.</li>
                                <li><code>/config/auth_roles.php</code> – v BOS24 funkce pro kontrolu rolí.</li>
                            </ul>

                            <p>
                                V jiném e-shopu je možné tyto názvy změnit, ale musí se tomu upravit
                                <code>bootstrap.php</code>, administrační stránky a správa předplatného.
                            </p>

                            <div class="alert alert-warning mb-0">
                                Administrace používá tabulku <code>users</code> a při přehledech očekává zejména
                                <code>id</code>, <code>email</code> a volitelně <code>username</code>.
                                Pokud má cílový e-shop jinou tabulku uživatelů, upravte JOINy v administračních stránkách.
                            </div>
                        </div>

                        <div class="doc-step" id="impl-4">
                            <h4>4. Importujte databázi</h4>
                            <p>
                                Importujte <code>/billing/database/billing-install.sql</code>.
                                Vznikne šest tabulek:
                            </p>
                            <ul class="checklist">
                                <li><code>billing_settings</code> – konfigurace a šifrovaná tajemství.</li>
                                <li><code>billing_products</code> – lokální katalog a mapování na Stripe Price.</li>
                                <li><code>billing_customers</code> – propojení uživatele s Stripe Customer.</li>
                                <li><code>billing_subscriptions</code> – lokální stav předplatného.</li>
                                <li><code>billing_payments</code> – jednorázové i opakované platby.</li>
                                <li><code>billing_webhook_events</code> – idempotence a historie webhooků.</li>
                            </ul>
                        </div>

                        <div class="doc-step" id="impl-5">
                            <h4>5. Zabezpečte <code>/billing/storage/</code></h4>
                            <p>
                                Soubor <code>.billing.key</code> je jediný klíč, kterým lze dešifrovat citlivé hodnoty
                                v <code>billing_settings</code>. Bez něj jsou uložené Stripe klíče nepoužitelné.
                            </p>
                            <ul class="checklist">
                                <li>Ponechte <code>/billing/storage/.htaccess</code> s blokováním přístupu z webu.</li>
                                <li>Ponechte <code>.billing.key</code> mimo GitHub a jiné veřejné repozitáře.</li>
                                <li>Klíč bezpečně zálohujte odděleně od databáze.</li>
                                <li>Při migraci serveru přeneste databázi i původní <code>.billing.key</code>, nebo Stripe údaje zadejte znovu.</li>
                            </ul>
                            <div class="alert alert-info mb-0">
                                Pokud hosting dovolí ukládat tajný klíč mimo document root, je to bezpečnější varianta.
                                Současná implementace BOS24 používá <code>/billing/storage/.billing.key</code>.
                            </div>
                        </div>

                        <div class="doc-step" id="impl-6">
                            <h4>6. Nastavte Billing v administraci</h4>
                            <p>Otevřete <code>/billing/admin.php</code> jako superadmin a vyplňte:</p>
                            <ul class="checklist">
                                <li><strong>Platební systém:</strong> Aktivní.</li>
                                <li><strong>Aktivní režim:</strong> nejdříve Sandbox / Test.</li>
                                <li><strong>Výchozí měna:</strong> např. CZK.</li>
                                <li><strong>Base URL:</strong> např. <code>https://eshop.cz/billing</code>, bez koncového lomítka.</li>
                                <li><strong>Sandbox API key:</strong> <code>rk_test_…</code> nebo <code>sk_test_…</code>.</li>
                                <li><strong>Sandbox webhook secret:</strong> <code>whsec_…</code>.</li>
                            </ul>

                            <p class="mb-0">
                                Publishable key <code>pk_…</code> současný Stripe-hosted Checkout nepoužívá;
                                pole je připravené pro případné budoucí Stripe.js/Elements.
                            </p>
                        </div>

                        <div class="doc-step" id="impl-7">
                            <h4>7. Přidejte produkty do BOS24</h4>
                            <p>
                                Ve Stripe nejdříve vytvořte Product + Price. Potom otevřete
                                <code>/billing/admin-produkty.php</code> a založte lokální produkt.
                            </p>
                            <ul class="checklist">
                                <li><code>code</code> – stabilní interní identifikátor, např. <code>eshop_ebook_001</code>.</li>
                                <li><code>provider_product_id</code> – Stripe <code>prod_…</code>.</li>
                                <li><code>provider_price_id</code> – Stripe <code>price_…</code>.</li>
                                <li><code>payment_type</code> – <code>one_off</code> nebo <code>subscription</code>.</li>
                                <li>Cena a měna v BOS24 musí odpovídat Stripe Price.</li>
                            </ul>

                            <div class="alert alert-danger mb-0">
                                <strong>Sandbox vs Live:</strong> Stripe Product/Price ID jsou v Sandboxu a Live jiné.
                                Současná V1 tabulka <code>billing_products</code> uchovává jen jednu sadu ID.
                                Před přepnutím do Live proto u každého produktu nahraďte Sandbox
                                <code>prod_… / price_…</code> jeho Live hodnotami. Při návratu do Sandboxu by bylo
                                nutné vrátit testovací ID.
                            </div>
                        </div>

                        <div class="doc-step" id="impl-8">
                            <h4>8. Napojte tlačítko „Zaplatit“ v e-shopu</h4>
                            <p>
                                Prohlížeč nesmí posílat cenu ani Stripe Price ID. Odesílá pouze interní
                                <code>product_code</code>; cenu a <code>price_…</code> načte server z databáze.
                            </p>

                            <div class="copy-wrap">
                                <button type="button"
                                        class="btn btn-default btn-sm copy-btn"
                                        data-copy-target="code-checkout-example">
                                    Kopírovat
                                </button>
                                <pre id="code-checkout-example"><code><?= bi_e($integrationExample) ?></code></pre>
                            </div>
                        </div>

                        <div class="doc-step" id="impl-9">
                            <h4>9. Princip Checkoutu</h4>
                            <ol class="checklist">
                                <li>E-shop pošle <code>product_code</code> do <code>/billing/checkout.php</code>.</li>
                                <li>Checkout ověří CSRF a přihlášeného uživatele.</li>
                                <li>Server načte aktivní produkt z <code>billing_products</code>.</li>
                                <li>Server vytvoří Stripe Checkout Session přes cURL.</li>
                                <li>Uživatel je přesměrován na Stripe-hosted Checkout.</li>
                                <li>Po platbě se vrátí na <code>success.php</code>; při zrušení na <code>cancel.php</code>.</li>
                                <li>Skutečný stav platby potvrzuje pouze <code>webhook.php</code>.</li>
                            </ol>

                            <div class="alert alert-warning mb-0">
                                <strong>Nikdy neaktivujte objednávku jen podle návratu na success.php.</strong>
                                Uživatel může návratovou URL otevřít ručně. Autoritativním zdrojem je webhook
                                a stav uložený v databázi.
                            </div>
                        </div>

                        <div class="doc-step" id="impl-10">
                            <h4>10. Napojení na objednávky a digitální obsah</h4>
                            <p>
                                Billing je platební vrstva. Cílový e-shop musí po zpracovaném webhooku provést vlastní
                                fulfillment – například označit objednávku jako zaplacenou, aktivovat modul nebo
                                vytvořit zabezpečený odkaz na e-book/audioknihu.
                            </p>
                            <ul class="checklist">
                                <li>Jednorázová platba: kontrolujte <code>billing_payments.status = 'paid'</code>.</li>
                                <li>Identifikaci produktu použijte přes <code>product_code</code>.</li>
                                <li>Předplatné: běžný přístup typicky pro <code>active</code> / <code>trialing</code>.</li>
                                <li>Při <code>cancel_at_period_end = 1</code> neodebírejte přístup okamžitě; předplatné běží do <code>current_period_end</code>.</li>
                                <li>Při finálním <code>canceled</code> / <code>ended_at</code> přístup ukončete.</li>
                                <li>Pro <code>past_due</code> stanovte obchodní grace period podle pravidel e-shopu.</li>
                            </ul>
                        </div>

                        <div class="doc-step" id="impl-11">
                            <h4>11. Správa předplatného zákazníkem</h4>
                            <p>
                                Stránka <code>/billing/moje-predplatne.php</code> zobrazuje předplatná přihlášeného
                                uživatele. <code>/billing/predplatne-akce.php</code> umí:
                            </p>
                            <ul class="checklist">
                                <li>zrušit automatické prodlužování pomocí <code>cancel_at_period_end=true</code>,</li>
                                <li>obnovit automatické prodlužování pomocí <code>cancel_at_period_end=false</code>.</li>
                            </ul>
                        </div>

                        <div class="doc-step" id="impl-12">
                            <h4>12. Kontrola po instalaci</h4>
                            <ol class="checklist">
                                <li>Otevřete <code>/billing/admin-diagnostika.php</code>.</li>
                                <li>Všechny DB tabulky musí být <strong>OK</strong>.</li>
                                <li>OpenSSL, cURL a master key musí být <strong>OK</strong>.</li>
                                <li>Klikněte <strong>Otestovat Stripe připojení</strong> – očekává se HTTP 200.</li>
                                <li>Proveďte jednu Sandbox jednorázovou platbu.</li>
                                <li>V <code>billing_webhook_events</code> musí být nový <code>processed</code> event.</li>
                                <li>V <code>billing_payments</code> musí být stav <code>paid</code>.</li>
                                <li>U předplatného ověřte také <code>billing_subscriptions</code>.</li>
                            </ol>
                        </div>
                    </div>
                </section>
            </div>

            <div id="tab-stripe" class="tab-pane fade">
                <section class="card mb-0">
                    <header class="card-header">
                        <h2 class="card-title">Stripe – nastavení krok za krokem</h2>
                    </header>

                    <div class="card-body">
                        <div class="doc-step">
                            <h4>1. Stripe účet, ověření a 2FA</h4>
                            <ol class="checklist">
                                <li>Přihlaste se do Stripe Dashboardu.</li>
                                <li>Dokončete údaje firmy / podnikatele a bankovní účet pro budoucí Live provoz.</li>
                                <li>Zapněte dvoufázové ověření účtu.</li>
                                <li>Pro vývoj vytvořte nebo otevřete <strong>Sandbox</strong>.</li>
                            </ol>
                        </div>

                        <div class="doc-step">
                            <h4>2. Billing model</h4>
                            <p>
                                Pro BOS24 používáme jednoduchý <strong>flat-rate</strong> model. Jednorázové produkty mají
                                jednorázový Price; předplatné má recurring Price. Jeden Stripe Product může mít více Price,
                                např. měsíční, půlroční a roční.
                            </p>
                        </div>

                        <div class="doc-step">
                            <h4>3. Vytvoření Restricted API key – Sandbox</h4>
                            <ol class="checklist">
                                <li>V Sandboxu otevřete <strong>Developers / Workbench → API keys</strong>.</li>
                                <li>Zvolte <strong>Create restricted key</strong>.</li>
                                <li>Název například <code>RAC Billing DEV</code>.</li>
                                <li>Všechny nepotřebné přístupy ponechte <strong>None</strong>.</li>
                                <li>Pro <strong>Checkout Sessions</strong> nastavte <strong>Write</strong>.</li>
                                <li>Pro <strong>Payment Intents</strong> nastavte <strong>Read</strong>.</li>
                                <li>Pro <strong>Charges</strong> nastavte <strong>Read</strong>.</li>
                                <li>Pro <strong>Subscriptions</strong> nastavte <strong>Write</strong>.</li>
                                <li>Veškerá oprávnění <strong>Connect</strong> ponechte <strong>None</strong>.</li>
                                <li>Dokončete 2FA ověření a zkopírujte klíč <code>rk_test_…</code>.</li>
                                <li>Klíč vložte pouze do <code>/billing/admin.php → Stripe Sandbox</code>.</li>
                            </ol>

                            <div class="alert alert-danger mb-0">
                                Restricted/Secret key nikdy nevkládejte do JavaScriptu, HTML, GitHubu, dokumentace
                                ani do veřejně dostupného souboru. Administrace jej ukládá šifrovaně.
                            </div>
                        </div>

                        <div class="doc-step">
                            <h4>4. Jednorázový produkt ve Stripe</h4>
                            <ol class="checklist">
                                <li>Otevřete <strong>Product catalog</strong>.</li>
                                <li>Klikněte <strong>Create product / Add product</strong>.</li>
                                <li>Zadejte název a případně popis.</li>
                                <li>Vyberte <strong>One-time</strong> cenu.</li>
                                <li>Nastavte částku a měnu.</li>
                                <li>Produkt uložte.</li>
                                <li>Zkopírujte <code>prod_…</code> a příslušné <code>price_…</code>.</li>
                                <li>Obě ID vložte do <code>/billing/admin-produkty.php</code>.</li>
                            </ol>
                        </div>

                        <div class="doc-step">
                            <h4>5. Předplatné ve Stripe</h4>
                            <ol class="checklist">
                                <li>V <strong>Product catalog</strong> vytvořte produkt služby/tarifu.</li>
                                <li>Pricing type nastavte na <strong>Recurring</strong>.</li>
                                <li>Pricing model: <strong>Flat rate</strong>.</li>
                                <li>Pro měsíční tarif nastavte interval <strong>Monthly / 1 month</strong>.</li>
                                <li>Pro půlroční tarif přidejte další Price a nastavte <strong>6 months</strong> (v některých UI „Month × 6“ / custom interval).</li>
                                <li>Pro roční tarif přidejte další Price a nastavte <strong>Yearly / 1 year</strong>.</li>
                                <li>Každý Price má vlastní <code>price_…</code>; Stripe Product může být společný.</li>
                                <li>Každý tarif založte v BOS24 jako samostatný <code>billing_products.code</code>.</li>
                            </ol>
                        </div>

                        <div class="doc-step">
                            <h4>6. Platební metody, Apple Pay a Google Pay</h4>
                            <ol class="checklist">
                                <li>V Dashboardu otevřete <strong>Settings → Payment methods</strong>.</li>
                                <li>Ověřte, že jsou aktivní karty a metody, které chcete používat.</li>
                                <li>Současná implementace používá <strong>Stripe-hosted Checkout</strong>.</li>
                                <li>Apple Pay a Google Pay Stripe zobrazí na hosted Checkout automaticky, pokud jsou pro zákazníka, zařízení, měnu a účet způsobilé.</li>
                            </ol>

                            <div class="alert alert-info mb-0">
                                Pro současný redirect na <code>checkout.stripe.com</code> není potřeba přidávat
                                Stripe.js ani vlastní Apple Pay tlačítko. Registrace vlastní domény je relevantní
                                hlavně při přechodu na vlastní/embedded payment UI nebo Stripe custom domain.
                            </div>
                        </div>

                        <div class="doc-step">
                            <h4>7. Webhook – Sandbox</h4>
                            <ol class="checklist">
                                <li>Otevřete <strong>Developers / Workbench → Webhooks</strong>. V novém UI může být položka označena <strong>Event destinations</strong>.</li>
                                <li>Zvolte <strong>Add endpoint / Create destination</strong>.</li>
                                <li>Scope nastavte na <strong>Events on your account</strong>, ne Connected accounts.</li>
                                <li>Endpoint URL: <code>https://VASE-DOMENA/billing/webhook.php</code>.</li>
                                <li>Vyberte pouze eventy uvedené níže.</li>
                                <li>Endpoint uložte.</li>
                                <li>Otevřete detail endpointu a odhalte <strong>Signing secret</strong> <code>whsec_…</code>.</li>
                                <li>Vložte jej do <code>/billing/admin.php → Stripe Sandbox → Webhook signing secret</code>.</li>
                            </ol>

                            <div class="row">
                                <?php foreach ($webhookEvents as $event): ?>
                                    <div class="col-lg-6">
                                        <code><?= bi_e($event) ?></code>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="alert alert-secondary mt-3 mb-0">
                                Webhook <strong>nesmí vyžadovat přihlášení</strong>. Stripe na něj posílá veřejné POST
                                požadavky; pravost se ověřuje podpisem pomocí <code>whsec_…</code>.
                            </div>
                        </div>

                        <div class="doc-step">
                            <h4>8. Nastavení Sandbox údajů v BOS24</h4>
                            <p>V <code>/billing/admin.php</code> nastavte:</p>
                            <ul class="checklist">
                                <li>Billing aktivní.</li>
                                <li>Režim <strong>Sandbox / Test</strong>.</li>
                                <li>Base URL <code>https://domena.cz/billing</code>.</li>
                                <li>Výchozí měnu.</li>
                                <li><code>rk_test_…</code> (nebo <code>sk_test_…</code>).</li>
                                <li><code>whsec_…</code> Sandbox webhooku.</li>
                                <li>Volitelně <code>pk_test_…</code>; současný hosted Checkout jej nepotřebuje.</li>
                            </ul>
                        </div>

                        <div class="doc-step">
                            <h4>9. Test jednorázové platby</h4>
                            <ol class="checklist">
                                <li>V BOS24 spusťte aktivní jednorázový produkt.</li>
                                <li>Pro úspěšný test lze použít Visa <code>4242 4242 4242 4242</code> nebo Mastercard <code>5555 5555 5555 4444</code>.</li>
                                <li>Datum expirace: libovolné budoucí datum; CVC: libovolné platné testovací číslo.</li>
                                <li>Po návratu ověřte <code>billing_payments.status = paid</code>.</li>
                                <li>V <code>/billing/admin-webhooky.php</code> musí být <code>checkout.session.completed</code> se stavem <strong>Zpracováno</strong>.</li>
                            </ol>
                        </div>

                        <div class="doc-step">
                            <h4>10. Test předplatného a obnovy</h4>
                            <ol class="checklist">
                                <li>Vytvořte Sandbox předplatné přes BOS24.</li>
                                <li>Ve Stripe otevřete detail předplatného.</li>
                                <li>Pokud je dostupné, zvolte <strong>Run simulation</strong> / Test Clock.</li>
                                <li>Posuňte čas za další fakturační období.</li>
                                <li>Ověřte nový <code>invoice.paid</code> a nový řádek v <code>billing_payments</code>.</li>
                                <li>Ověřte posun <code>current_period_start/end</code> v <code>billing_subscriptions</code>.</li>
                            </ol>
                        </div>

                        <div class="doc-step">
                            <h4>11. Test neúspěšné obnovy a následné úhrady</h4>
                            <ol class="checklist">
                                <li>Pro scénář „karta se připojí, ale následná platba selže“ lze v Sandboxu použít <code>4000 0000 0000 0341</code>.</li>
                                <li>Posuňte Test Clock na další obnovu.</li>
                                <li>Očekává se <code>invoice.payment_failed</code>.</li>
                                <li>V BOS24 se platba uloží jako <code>failed</code>; Stripe předplatné může přejít do stavu podle nastavení retry politiky.</li>
                                <li>Změňte testovací kartu zpět na úspěšnou a ve Stripe proveďte opětovné zpoplatnění / Charge customer.</li>
                                <li>Po <code>invoice.paid</code> musí BOS24 aktualizovat platbu na <code>paid</code>.</li>
                            </ol>
                        </div>

                        <div class="doc-step">
                            <h4>12. Test zrušení předplatného</h4>
                            <ol class="checklist">
                                <li>Na <code>/billing/moje-predplatne.php</code> zvolte „Zrušit na konci období“.</li>
                                <li>Stripe Subscription musí mít <code>cancel_at_period_end = true</code>.</li>
                                <li>Přístup zákazníka ponechte do <code>current_period_end</code>.</li>
                                <li>Test Clock posuňte za konec období.</li>
                                <li>Očekává se <code>customer.subscription.deleted</code> a lokální stav <code>canceled</code>.</li>
                            </ol>
                        </div>

                        <div class="doc-step">
                            <h4>13. Live API key</h4>
                            <ol class="checklist">
                                <li>Přepněte Stripe do Live prostředí.</li>
                                <li>Otevřete <strong>Developers / Workbench → API keys</strong>.</li>
                                <li>Vytvořte nový Restricted API key, např. <code>RAC Billing LIVE</code>.</li>
                                <li>Nastavte stejná minimální oprávnění jako v Sandboxu.</li>
                                <li>Zkopírujte <code>rk_live_…</code> a vložte jej do <code>/billing/admin.php → Stripe Live</code>.</li>
                            </ol>
                        </div>

                        <div class="doc-step">
                            <h4>14. Live produkty a ceny</h4>
                            <ol class="checklist">
                                <li>V Live prostředí vytvořte skutečné produkty a ceny.</li>
                                <li>Sandbox <code>prod_…</code> a <code>price_…</code> v Live nefungují.</li>
                                <li>V <code>/billing/admin-produkty.php</code> nahraďte testovací ID Live ID.</li>
                                <li>Pečlivě ověřte částky, měny a intervaly.</li>
                            </ol>
                        </div>

                        <div class="doc-step">
                            <h4>15. Live webhook</h4>
                            <ol class="checklist">
                                <li>V Live prostředí vytvořte nový webhook/event destination.</li>
                                <li>Může používat stejnou URL <code>https://domena.cz/billing/webhook.php</code>.</li>
                                <li>Zvolte stejných 8 eventů jako v Sandboxu.</li>
                                <li>Zkopírujte nový Live signing secret <code>whsec_…</code>.</li>
                                <li>Vložte jej do <code>/billing/admin.php → Stripe Live</code>.</li>
                            </ol>

                            <div class="alert alert-info mb-0">
                                Sandbox a Live webhook mají rozdílné <code>whsec_…</code>. Současný BOS24
                                <code>webhook.php</code> umí na stejné URL ověřit obě prostředí podle
                                <code>event.livemode</code>.
                            </div>
                        </div>

                        <div class="doc-step">
                            <h4>16. Přechod do ostrého provozu</h4>
                            <ol class="checklist">
                                <li>Zkontrolujte firemní a bankovní ověření Stripe účtu.</li>
                                <li>Zkontrolujte Live API key a Live webhook secret.</li>
                                <li>Zkontrolujte Live <code>prod_… / price_…</code> v <code>billing_products</code>.</li>
                                <li>Spusťte <code>/billing/admin-diagnostika.php</code> a otestujte Stripe připojení.</li>
                                <li>V <code>/billing/admin.php</code> přepněte režim na <strong>Live</strong>.</li>
                                <li>Proveďte jednu malou reálnou platbu a ověřte Stripe, webhook a databázi.</li>
                                <li>Teprve poté zpřístupněte placení zákazníkům.</li>
                            </ol>
                        </div>

                        <div class="alert alert-secondary mb-0">
                            <strong>Bezpečnostní pravidlo:</strong> produktová cena se nikdy nepřebírá z POST/GET od
                            zákazníka. Klient posílá pouze <code>product_code</code>; server načte důvěryhodný
                            Stripe <code>price_…</code> z databáze.
                        </div>
                    </div>
                </section>
            </div>

            <div id="tab-sql" class="tab-pane fade">
                <section class="card mb-0">
                    <header class="card-header">
                        <h2 class="card-title">Kompletní SQL pro čistou instalaci</h2>
                    </header>

                    <div class="card-body">
                        <div class="alert alert-info">
                            Tento SQL je určen pro <strong>novou instalaci</strong>. Nahrazuje původní postupné soubory
                            <code>billing.sql</code>, <code>billing-products.sql</code>,
                            <code>billing-subscriptions.sql</code> a <code>billing-settings.sql</code>.
                            Na již existující instalaci jej znovu neimportujte bez kontroly.
                        </div>

                        <p>
                            SQL vytváří finální strukturu všech šesti billing tabulek a základní nastavení.
                            Záměrně nevkládá žádné konkrétní <code>prod_…</code> nebo <code>price_…</code>, protože
                            ty patří ke konkrétnímu Stripe účtu.
                        </p>

                        <div class="copy-wrap">
                            <button type="button"
                                    class="btn btn-default btn-sm copy-btn"
                                    data-copy-target="billing-install-sql">
                                Kopírovat SQL
                            </button>
                            <pre id="billing-install-sql"><code><?= bi_e($installSql) ?></code></pre>
                        </div>

                        <div class="alert alert-warning mb-0">
                            Po importu nejdříve nastavte <code>/billing/admin.php</code>, potom Stripe webhook a až
                            následně produkty. Master key se generuje při prvním uložení citlivého údaje.
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <section class="card mt-4">
        <header class="card-header">
            <h2 class="card-title">Provozní zásady</h2>
        </header>
        <div class="card-body">
            <ul class="checklist mb-0">
                <li><strong>Webhook je autorita:</strong> success redirect není potvrzení úhrady.</li>
                <li><strong>Idempotence:</strong> Stripe event ID je unikátní v <code>billing_webhook_events</code>.</li>
                <li><strong>Secrets:</strong> nikdy nelogovat ani nezobrazovat celé <code>rk_</code>, <code>sk_</code> nebo <code>whsec_</code>.</li>
                <li><strong>Master key:</strong> zálohovat bezpečně mimo databázi a GitHub.</li>
                <li><strong>Sandbox/Live:</strong> mají oddělené klíče, webhook secrets a Product/Price objekty.</li>
                <li><strong>Connect:</strong> tato implementace ho nepoužívá; Connect permissions mají zůstat None.</li>
            </ul>
        </div>
    </section>
</section>
</div>

</div></div></main></div><script src="../assets/js/admin.js"></script></body></html>