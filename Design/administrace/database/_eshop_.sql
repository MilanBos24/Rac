-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Počítač: m20961
-- Vytvořeno: Úte 27. říj 2026, 19:32
-- Verze serveru: 5.6.45
-- Verze PHP: 7.4.23

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Databáze: `rac`
--

-- --------------------------------------------------------

--
-- Struktura tabulky `admin_permissions`
--

CREATE TABLE `admin_permissions` (
  `permission_key` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `section_name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Vypisuji data pro tabulku `admin_permissions`
--

INSERT INTO `admin_permissions` (`permission_key`, `label`, `section_name`) VALUES
('billing.manage', 'Platební brána – konfigurace', 'Platební brána'),
('contact.manage', 'Kontaktní formulář – nastavení', 'Administrace'),
('content.manage', 'Obsah webu – správa', 'Obsah webu'),
('dashboard.view', 'Dashboard', 'Přehled'),
('digital.manage', 'Digitální přístupy – správa', 'E-shop'),
('digital.view', 'Digitální přístupy – zobrazení', 'E-shop'),
('languages.manage', 'Jazyky – správa', 'Administrace'),
('orders.delete_test', 'Objednávky – mazání testovacích', 'E-shop'),
('orders.fulfill', 'Objednávky – vyřízení', 'E-shop'),
('orders.view', 'Objednávky – zobrazení', 'E-shop'),
('payments.manage', 'Platby e-shopu – skrytí a obnovení testovacích plateb', 'E-shop'),
('payments.view', 'Platby e-shopu – zobrazení', 'E-shop'),
('products.edit', 'Produkty – úpravy', 'E-shop'),
('products.view', 'Produkty – zobrazení', 'E-shop'),
('shipping.manage', 'Doprava – správa', 'E-shop'),
('stock.edit', 'Sklad – pohyby', 'E-shop'),
('stock.view', 'Sklad – zobrazení', 'E-shop'),
('system.roles', 'Role a oprávnění', 'Nastavení systému'),
('users.manage', 'Administrátoři – správa', 'Administrace');

-- --------------------------------------------------------

--
-- Struktura tabulky `admin_roles`
--

CREATE TABLE `admin_roles` (
  `role_key` varchar(48) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Vypisuji data pro tabulku `admin_roles`
--

INSERT INTO `admin_roles` (`role_key`, `role_name`, `description`, `is_system`, `created_at`) VALUES
('admin', 'Administrátor e-shopu', 'Výchozí přístup k administraci e-shopu', 1, '2026-10-20 10:00:21'),
('skladnik', 'Skladník', 'Spravuje sklad eshopu', 0, '2026-10-20 10:24:55'),
('superadmin', 'Superadmin', 'Plný přístup do systému', 1, '2026-10-20 10:00:21');

-- --------------------------------------------------------

--
-- Struktura tabulky `admin_role_permissions`
--

CREATE TABLE `admin_role_permissions` (
  `role_key` varchar(48) COLLATE utf8mb4_unicode_ci NOT NULL,
  `permission_key` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Vypisuji data pro tabulku `admin_role_permissions`
--

INSERT INTO `admin_role_permissions` (`role_key`, `permission_key`) VALUES
('admin', 'dashboard.view'),
('skladnik', 'dashboard.view'),
('admin', 'digital.manage'),
('admin', 'digital.view'),
('admin', 'orders.fulfill'),
('admin', 'orders.view'),
('admin', 'payments.view'),
('admin', 'products.edit'),
('admin', 'products.view'),
('admin', 'shipping.manage'),
('skladnik', 'shipping.manage'),
('admin', 'stock.edit'),
('skladnik', 'stock.edit'),
('admin', 'stock.view'),
('skladnik', 'stock.view');

-- --------------------------------------------------------

--
-- Struktura tabulky `admin_user_roles`
--

CREATE TABLE `admin_user_roles` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `role_key` varchar(48) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Vypisuji data pro tabulku `admin_user_roles`
--

INSERT INTO `admin_user_roles` (`user_id`, `role_key`) VALUES
(2, 'skladnik');

-- --------------------------------------------------------

--
-- Struktura tabulky `billing_customers`
--

CREATE TABLE `billing_customers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `provider` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'stripe',
  `user_id` int(11) NOT NULL,
  `provider_customer_id` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `billing_payments`
--

CREATE TABLE `billing_payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
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
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `billing_products`
--

CREATE TABLE `billing_products` (
  `id` bigint(20) UNSIGNED NOT NULL,
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
  `billing_interval_count` int(10) UNSIGNED DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `billing_settings`
--

CREATE TABLE `billing_settings` (
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_secret` tinyint(1) NOT NULL DEFAULT '0',
  `updated_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Vypisuji data pro tabulku `billing_settings`
--

INSERT INTO `billing_settings` (`setting_key`, `setting_value`, `is_secret`, `updated_by`, `created_at`, `updated_at`) VALUES
('base_url', 'https://pavlakovalovska.eu/administrace/billing', 0, 1, '2026-10-09 13:21:54', '2026-10-27 00:33:28'),
('billing_enabled', '1', 0, 1, '2026-10-09 13:21:54', '2026-10-27 00:33:28'),
('default_currency', 'CZK', 0, 1, '2026-10-09 13:21:54', '2026-10-27 00:33:28'),
('mode', 'live', 0, 1, '2026-10-09 13:21:54', '2026-10-27 00:33:28'),
('stripe_live_secret_key', 'v1:An0MWmNthbocBNa80gaL9u44z3P1hCYcJXaJtYjP1a8Au3a3itGc3boTj3c3WC57eHZbfb766H3WOe1Jy/V1ETXRoHeFQPh9dq441GaOQxDJI6328wUeTZei9bp3X8ANIzuOZufOd8HF55qQnhrpwIgwGZVDvJQy6RXzZzjPpIGom5hF2XiL', 1, 1, '2026-10-27 00:21:09', '2026-10-27 00:21:09'),
('stripe_live_webhook_secret', 'v1:95CnAF901Hlh44Vdq5fX7MALmL5Tu6Dr3xuuv09paJNUL454mwc2NfHjq9G3+9j0yS1j1omEz4lcjTXrckxUoI+F', 1, 1, '2026-10-27 00:28:35', '2026-10-27 00:28:35'),
('stripe_test_publishable_key', 'v1:UmoDl+Voxgi6Wj+a+JAOLc/2O8w3kHAVGxyZwH1oegp54FujHvpokpdGuRpTGlROOzk8f/vzzlJvDLHV0tTUwyGS79rC1Mgx8mH5ziG7wCJGj/idQIM0QvWwmgLGgs/6wiVggxzrA9UO/Tff2Mx0NAZHQkEfjtD70DXecXqCKbtag3NL9nrL', 1, 1, '2026-10-15 08:02:57', '2026-10-15 08:02:57'),
('stripe_test_secret_key', 'v1:ShCCbxYdsvqjz8Qhkoj9jNOamxUOwHHT7OQAXOqBdNsAT4zVQQEU/Jus1qC6B0IASwIYoKrYUWiEHBloA8iszpc7285LsKPf1kNdwCZ6dJZELZBIffrvuJqROaVLwWHpJQAxHbAkqq+kNtUSnpAGwGnCkUMUdGBgOqieriQj6jdQjf42d9Pc', 1, 1, '2026-10-15 08:02:57', '2026-10-15 08:02:57'),
('stripe_test_webhook_secret', 'v1:LyhhxaZjK8R7+xAx2uOf7jdnicqzEmTA1hbQsgZkFCI4qp37S9A7/yWor+Fsn/DIqtbcBDmD1IUrK6O3ggPgYsE3', 1, 1, '2026-10-15 10:04:57', '2026-10-15 10:04:57');

-- --------------------------------------------------------

--
-- Struktura tabulky `billing_subscriptions`
--

CREATE TABLE `billing_subscriptions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `provider` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'stripe',
  `user_id` int(11) NOT NULL,
  `billing_product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `product_code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_customer_id` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_subscription_id` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider_price_id` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `checkout_session_id` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `amount_minor` bigint(20) NOT NULL DEFAULT '0',
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_interval` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_interval_count` int(10) UNSIGNED DEFAULT NULL,
  `current_period_start` datetime DEFAULT NULL,
  `current_period_end` datetime DEFAULT NULL,
  `cancel_at_period_end` tinyint(1) NOT NULL DEFAULT '0',
  `cancel_at` datetime DEFAULT NULL,
  `canceled_at` datetime DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `billing_webhook_events`
--

CREATE TABLE `billing_webhook_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `provider` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'stripe',
  `event_id` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_type` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `livemode` tinyint(1) NOT NULL DEFAULT '0',
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'received',
  `object_id` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `received_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `book_edition_languages`
--

CREATE TABLE `book_edition_languages` (
  `code` varchar(16) CHARACTER SET ascii NOT NULL,
  `native_name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT '100',
  `active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Vypisuji data pro tabulku `book_edition_languages`
--

INSERT INTO `book_edition_languages` (`code`, `native_name`, `sort_order`, `active`) VALUES
('ar', 'العربية', 90, 1),
('cs', 'Čeština', 10, 1),
('de', 'Deutsch', 50, 1),
('en', 'English', 40, 1),
('es', 'Español', 80, 1),
('fr', 'Français', 60, 1),
('it', 'Italiano', 70, 1),
('pl', 'Polski', 30, 1),
('pt', 'Português', 100, 1),
('ru', 'Русский', 110, 1),
('sk', 'Slovenčina', 20, 1),
('uk', 'Українська', 120, 1);

-- --------------------------------------------------------

--
-- Struktura tabulky `contact_blocked_ips`
--

CREATE TABLE `contact_blocked_ips` (
  `id` int(10) UNSIGNED NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Struktura tabulky `contact_form_settings`
--

CREATE TABLE `contact_form_settings` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `recipient_email` varchar(254) NOT NULL DEFAULT '',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sender_email` varchar(254) DEFAULT NULL,
  `sender_name` varchar(120) NOT NULL DEFAULT 'RAC – kontaktní formulář',
  `email_subject` varchar(180) NOT NULL DEFAULT 'Zpráva z kontaktního formuláře'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Vypisuji data pro tabulku `contact_form_settings`
--

INSERT INTO `contact_form_settings` (`id`, `recipient_email`, `updated_at`, `sender_email`, `sender_name`, `email_subject`) VALUES
(1, 'kovalovska.pavla@seznam.cz', '2026-10-21 20:57:43', NULL, 'Mgr. Pavla Kovalovská', 'Zpráva z kontaktního formuláře');

-- --------------------------------------------------------

--
-- Struktura tabulky `contact_form_subjects`
--

CREATE TABLE `contact_form_subjects` (
  `id` int(10) UNSIGNED NOT NULL,
  `label_cs` varchar(150) NOT NULL,
  `label_sk` varchar(150) NOT NULL DEFAULT '',
  `label_pl` varchar(150) NOT NULL DEFAULT '',
  `label_en` varchar(150) NOT NULL DEFAULT '',
  `label_de` varchar(150) NOT NULL DEFAULT '',
  `label_fr` varchar(150) NOT NULL DEFAULT '',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int(11) NOT NULL DEFAULT '100',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Struktura tabulky `eshop_stock_movements`
--

CREATE TABLE `eshop_stock_movements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `variant_id` bigint(20) UNSIGNED NOT NULL,
  `change_qty` int(11) NOT NULL,
  `balance_after` int(11) NOT NULL,
  `source` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `note` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Vypisuji data pro tabulku `eshop_stock_movements`
--

INSERT INTO `eshop_stock_movements` (`id`, `variant_id`, `change_qty`, `balance_after`, `source`, `note`, `user_id`, `created_at`) VALUES
(1, 41, 20, 30, 'receipt', NULL, 1, '2026-10-13 12:55:43'),
(2, 41, -10, 20, 'issue', NULL, 2, '2026-10-20 11:34:14');

-- --------------------------------------------------------

--
-- Struktura tabulky `fakturace_doklady`
--

CREATE TABLE `fakturace_doklady` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `cislo_dokladu` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `typ` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'faktura',
  `stav` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'zaplaceno',
  `datum_vystaveni` date NOT NULL,
  `datum_zaplaceni` date NOT NULL,
  `datum_zdanitelneho_plneni` date DEFAULT NULL,
  `mena` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CZK',
  `zpusob_uhrady` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'stripe',
  `stripe_payment_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order_number` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dodavatel_nazev` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dodavatel_ico` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dodavatel_dic` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dodavatel_ulice` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dodavatel_mesto` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dodavatel_psc` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dodavatel_stat` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dodavatel_email` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dodavatel_telefon` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dodavatel_web` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dodavatel_cislo_uctu` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dodavatel_nazev_uctu` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dodavatel_typ_uctu` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dodavatel_kod_banky` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dodavatel_mena_uctu` char(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dodavatel_iban` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dodavatel_bic` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dodavatel_platce_dph` tinyint(1) NOT NULL DEFAULT '0',
  `odberatel_nazev` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `odberatel_firma` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `odberatel_ico` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `odberatel_dic` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `odberatel_ulice` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `odberatel_mesto` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `odberatel_psc` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `odberatel_stat` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `odberatel_email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `castka_bez_dph` decimal(14,2) NOT NULL DEFAULT '0.00',
  `castka_dph` decimal(14,2) NOT NULL DEFAULT '0.00',
  `castka_celkem` decimal(14,2) NOT NULL DEFAULT '0.00',
  `castka_zaplaceno` decimal(14,2) NOT NULL DEFAULT '0.00',
  `poznamka` text COLLATE utf8mb4_unicode_ci,
  `pdf_sha256` char(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_odeslan_dne` datetime DEFAULT NULL,
  `vytvoreno` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Vypisuji data pro tabulku `fakturace_doklady`
--

INSERT INTO `fakturace_doklady` (`id`, `order_id`, `cislo_dokladu`, `typ`, `stav`, `datum_vystaveni`, `datum_zaplaceni`, `datum_zdanitelneho_plneni`, `mena`, `zpusob_uhrady`, `stripe_payment_id`, `order_number`, `dodavatel_nazev`, `dodavatel_ico`, `dodavatel_dic`, `dodavatel_ulice`, `dodavatel_mesto`, `dodavatel_psc`, `dodavatel_stat`, `dodavatel_email`, `dodavatel_telefon`, `dodavatel_web`, `dodavatel_cislo_uctu`, `dodavatel_nazev_uctu`, `dodavatel_typ_uctu`, `dodavatel_kod_banky`, `dodavatel_mena_uctu`, `dodavatel_iban`, `dodavatel_bic`, `dodavatel_platce_dph`, `odberatel_nazev`, `odberatel_firma`, `odberatel_ico`, `odberatel_dic`, `odberatel_ulice`, `odberatel_mesto`, `odberatel_psc`, `odberatel_stat`, `odberatel_email`, `castka_bez_dph`, `castka_dph`, `castka_celkem`, `castka_zaplaceno`, `poznamka`, `pdf_sha256`, `email_odeslan_dne`, `vytvoreno`) VALUES
(1, 28, 'TEST-28', 'faktura', 'zaplaceno', '2026-10-25', '2026-10-25', NULL, 'CZK', 'stripe', 'pi_3UUYIkRrdttDzBlz1m8DVmpn', 'RAC-261025-4A2C702497', 'Mgr. Pavla Kovalovská', NULL, NULL, 'Hranice, Včelařská 2003/19', 'Karviná', '73301', 'Česká republika', 'kovalovska.pavla@seznam.cz', NULL, 'https://pavlakovalovska.eu', '2600042537', 'Hlavní účet', 'tuzemsky', '2010', 'CZK', 'CZ3620100000002600042537', 'FIOBCZPPXXX', 0, 'Milan Smieško', NULL, NULL, NULL, 'Ve finských 1892', 'Petřvald', '73541', 'CZ', 'milan.smiesko@seznam.cz', 329.00, 0.00, 329.00, 329.00, 'TESTOVACÍ DOKLAD – NEJDE O ÚČETNÍ ANI DAŇOVÝ DOKLAD. Stripe TEST, bez skutečné úhrady.', NULL, NULL, '2026-10-25 21:58:56'),
(2, 29, 'TEST-29', 'faktura', 'zaplaceno', '2026-10-25', '2026-10-25', NULL, 'CZK', 'stripe', 'pi_3UUYqyRrdttDzBlz0jRVGuAU', 'RAC-261025-71F45354A0', 'Mgr. Pavla Kovalovská', NULL, NULL, 'Hranice, Včelařská 2003/19', 'Karviná', '73301', 'Česká republika', 'kovalovska.pavla@seznam.cz', NULL, 'https://pavlakovalovska.eu', '2600042537', 'Hlavní účet', 'tuzemsky', '2010', 'CZK', 'CZ3620100000002600042537', 'FIOBCZPPXXX', 0, 'Milan Smieško', NULL, NULL, NULL, 'Ve finských 1892', 'Petřvald', '73541', 'CZ', 'milan.smiesko@seznam.cz', 329.00, 0.00, 329.00, 329.00, 'TESTOVACÍ DOKLAD – NEJDE O ÚČETNÍ ANI DAŇOVÝ DOKLAD. Stripe TEST, bez skutečné úhrady.', NULL, NULL, '2026-10-25 22:33:03'),
(3, 30, 'TEST-30', 'faktura', 'zaplaceno', '2026-10-25', '2026-10-25', NULL, 'CZK', 'stripe', 'pi_3UUZCURrdttDzBlz1mHGKWUe', 'RAC-261025-1B04EA616E', 'Mgr. Pavla Kovalovská', NULL, NULL, 'Hranice, Včelařská 2003/19', 'Karviná', '73301', 'Česká republika', 'kovalovska.pavla@seznam.cz', NULL, 'https://pavlakovalovska.eu', '2600042537', 'Hlavní účet', 'tuzemsky', '2010', 'CZK', 'CZ3620100000002600042537', 'FIOBCZPPXXX', 0, 'Milan Smieško', NULL, NULL, NULL, 'Ve finských 1892', 'Ostrava', '785421', 'CZ', 'milan.smiesko@seznam.cz', 190.00, 0.00, 190.00, 190.00, 'TESTOVACÍ DOKLAD – NEJDE O ÚČETNÍ ANI DAŇOVÝ DOKLAD. Stripe TEST, bez skutečné úhrady.', NULL, NULL, '2026-10-25 23:06:08'),
(4, 31, 'TEST-31', 'faktura', 'zaplaceno', '2026-10-25', '2026-10-25', NULL, 'CZK', 'stripe', 'pi_3UUZb4RrdttDzBlz1DuCSr3E', 'RAC-261025-396A318777', 'Mgr. Pavla Kovalovská', NULL, NULL, 'Hranice, Včelařská 2003/19', 'Karviná', '73301', 'Česká republika', 'kovalovska.pavla@seznam.cz', NULL, 'https://pavlakovalovska.eu', '2600042537', 'Hlavní účet', 'tuzemsky', '2010', 'CZK', 'CZ3620100000002600042537', 'FIOBCZPPXXX', 0, 'Milan Smieško', NULL, NULL, NULL, 'Ve finských 1892', 'Petřvald', '73541', 'CZ', 'milan.smiesko@seznam.cz', 270.00, 0.00, 270.00, 270.00, 'TESTOVACÍ DOKLAD – NEJDE O ÚČETNÍ ANI DAŇOVÝ DOKLAD. Stripe TEST, bez skutečné úhrady.', NULL, NULL, '2026-10-25 23:09:00'),
(5, 32, 'TEST-32', 'faktura', 'zaplaceno', '2026-10-26', '2026-10-26', NULL, 'CZK', 'stripe', 'pi_3UUswmRrdttDzBlz1BDPFQVK', 'RAC-261026-F095B70FCB', 'Mgr. Pavla Kovalovská', NULL, NULL, 'Hranice, Včelařská 2003/19', 'Karviná', '73301', 'Česká republika', 'kovalovska.pavla@seznam.cz', NULL, 'https://pavlakovalovska.eu', '2600042537', 'Hlavní účet', 'tuzemsky', '2010', 'CZK', 'CZ3620100000002600042537', 'FIOBCZPPXXX', 0, 'Ing. Milan Smieško', NULL, NULL, NULL, 'Ve Finských 1826', 'Petřvald', '735 41', 'PL', 'milan.smiesko@seznam.cz', 270.00, 0.00, 270.00, 270.00, 'TESTOVACÍ DOKLAD – NEJDE O ÚČETNÍ ANI DAŇOVÝ DOKLAD. Stripe TEST, bez skutečné úhrady.', NULL, NULL, '2026-10-26 19:49:39'),
(6, 37, 'TEST-37', 'faktura', 'zaplaceno', '2026-10-26', '2026-10-26', NULL, 'CZK', 'stripe', 'pi_3UUtpZRrdttDzBlz0Q5jeHNq', 'RAC-261026-13A01C04D3', 'Mgr. Pavla Kovalovská', NULL, NULL, 'Hranice, Včelařská 2003/19', 'Karviná', '73301', 'Česká republika', 'kovalovska.pavla@seznam.cz', NULL, 'https://pavlakovalovska.eu', '2600042537', 'Hlavní účet', 'tuzemsky', '2010', 'CZK', 'CZ3620100000002600042537', 'FIOBCZPPXXX', 0, 'Aleš Tvarda', NULL, NULL, NULL, 'Ve finských 1892', 'Ostrava', '70030', 'CZ', 'milan.smiesko@seznam.cz', 199.00, 0.00, 199.00, 199.00, 'TESTOVACÍ DOKLAD – NEJDE O ÚČETNÍ ANI DAŇOVÝ DOKLAD. Stripe TEST, bez skutečné úhrady.', NULL, NULL, '2026-10-26 20:45:18'),
(7, 35, 'TEST-35', 'faktura', 'zaplaceno', '2026-10-26', '2026-10-26', NULL, 'CZK', 'stripe', 'pi_3UUtN6RrdttDzBlz0OpttsxV', 'RAC-261026-ED231CC26F', 'Mgr. Pavla Kovalovská', NULL, NULL, 'Hranice, Včelařská 2003/19', 'Karviná', '73301', 'Česká republika', 'kovalovska.pavla@seznam.cz', NULL, 'https://pavlakovalovska.eu', '2600042537', 'Hlavní účet', 'tuzemsky', '2010', 'CZK', 'CZ3620100000002600042537', 'FIOBCZPPXXX', 0, 'Michal Smieško', NULL, NULL, NULL, 'Ve Finských 1826', 'Petřvald', '735 41', 'CZ', 'milan.smiesko@seznam.cz', 329.00, 0.00, 329.00, 329.00, 'TESTOVACÍ DOKLAD – NEJDE O ÚČETNÍ ANI DAŇOVÝ DOKLAD. Stripe TEST, bez skutečné úhrady.', NULL, NULL, '2026-10-26 22:31:44'),
(8, 36, 'TEST-36', 'faktura', 'zaplaceno', '2026-10-26', '2026-10-26', NULL, 'CZK', 'stripe', 'pi_3UUtYBRrdttDzBlz1TqbgEUM', 'RAC-261026-BC3FEBF8C7', 'Mgr. Pavla Kovalovská', NULL, NULL, 'Hranice, Včelařská 2003/19', 'Karviná', '73301', 'Česká republika', 'kovalovska.pavla@seznam.cz', NULL, 'https://pavlakovalovska.eu', '2600042537', 'Hlavní účet', 'tuzemsky', '2010', 'CZK', 'CZ3620100000002600042537', 'FIOBCZPPXXX', 0, 'milan smieško', NULL, NULL, NULL, 'Ve Finských 1826', 'Petřvald', '735 41', 'CZ', 'milan.smiesko@seznam.cz', 329.00, 0.00, 329.00, 329.00, 'TESTOVACÍ DOKLAD – NEJDE O ÚČETNÍ ANI DAŇOVÝ DOKLAD. Stripe TEST, bez skutečné úhrady.', NULL, NULL, '2026-10-26 23:26:21'),
(9, 44, 'TEST-44', 'faktura', 'zaplaceno', '2026-10-26', '2026-10-26', NULL, 'CZK', 'stripe', 'pi_3UUwIqRrdttDzBlz1F3eeSsD', 'RAC-261026-897FA33FD3', 'Mgr. Pavla Kovalovská', NULL, NULL, 'Hranice, Včelařská 2003/19', 'Karviná', '73301', 'Česká republika', 'kovalovska.pavla@seznam.cz', NULL, 'https://pavlakovalovska.eu', '2600042537', 'Hlavní účet', 'tuzemsky', '2010', 'CZK', 'CZ3620100000002600042537', 'FIOBCZPPXXX', 0, 'Milan Lytasy', NULL, NULL, NULL, 'Doma 23', 'Petřvald', '73541', 'CZ', 'milan.smiesko@seznam.cz', 270.00, 0.00, 270.00, 270.00, 'TESTOVACÍ DOKLAD – NEJDE O ÚČETNÍ ANI DAŇOVÝ DOKLAD. Stripe TEST, bez skutečné úhrady.', NULL, NULL, '2026-10-26 23:42:36'),
(10, 43, 'TEST-43', 'faktura', 'zaplaceno', '2026-10-26', '2026-10-26', NULL, 'CZK', 'stripe', 'pi_3UUvv3RrdttDzBlz0ucrorbt', 'RAC-261026-EA5058DEA8', 'Mgr. Pavla Kovalovská', NULL, NULL, 'Hranice, Včelařská 2003/19', 'Karviná', '73301', 'Česká republika', 'kovalovska.pavla@seznam.cz', NULL, 'https://pavlakovalovska.eu', '2600042537', 'Hlavní účet', 'tuzemsky', '2010', 'CZK', 'CZ3620100000002600042537', 'FIOBCZPPXXX', 0, 'ing. Milan Smieško', NULL, NULL, NULL, 'Doma 23', 'Ostrava', '70070', 'CZ', 'milan.smiesko@seznam.cz', 199.00, 0.00, 199.00, 199.00, 'TESTOVACÍ DOKLAD – NEJDE O ÚČETNÍ ANI DAŇOVÝ DOKLAD. Stripe TEST, bez skutečné úhrady.', NULL, NULL, '2026-10-26 23:42:37'),
(11, 42, 'TEST-42', 'faktura', 'zaplaceno', '2026-10-26', '2026-10-26', NULL, 'CZK', 'stripe', 'pi_3UUvqIRrdttDzBlz0fZv8Qoe', 'RAC-261026-8A2B000D41', 'Mgr. Pavla Kovalovská', NULL, NULL, 'Hranice, Včelařská 2003/19', 'Karviná', '73301', 'Česká republika', 'kovalovska.pavla@seznam.cz', NULL, 'https://pavlakovalovska.eu', '2600042537', 'Hlavní účet', 'tuzemsky', '2010', 'CZK', 'CZ3620100000002600042537', 'FIOBCZPPXXX', 0, 'Milan Smieško', NULL, NULL, NULL, 'Lytasy 10', 'Ostrava', '77845', 'CZ', 'milan.smiesko@seznam.cz', 199.00, 0.00, 199.00, 199.00, 'TESTOVACÍ DOKLAD – NEJDE O ÚČETNÍ ANI DAŇOVÝ DOKLAD. Stripe TEST, bez skutečné úhrady.', NULL, NULL, '2026-10-26 23:42:38'),
(12, 41, 'TEST-41', 'faktura', 'zaplaceno', '2026-10-26', '2026-10-26', NULL, 'CZK', 'stripe', 'pi_3UUvV2RrdttDzBlz0gQNCsvG', 'RAC-261026-9228FD8A46', 'Mgr. Pavla Kovalovská', NULL, NULL, 'Hranice, Včelařská 2003/19', 'Karviná', '73301', 'Česká republika', 'kovalovska.pavla@seznam.cz', NULL, 'https://pavlakovalovska.eu', '2600042537', 'Hlavní účet', 'tuzemsky', '2010', 'CZK', 'CZ3620100000002600042537', 'FIOBCZPPXXX', 0, 'Aleš Lytasy', NULL, NULL, NULL, 'Lytasy 10', 'Lytasy', '12345', 'CZ', 'milan.smiesko@seznam.cz', 270.00, 0.00, 270.00, 270.00, 'TESTOVACÍ DOKLAD – NEJDE O ÚČETNÍ ANI DAŇOVÝ DOKLAD. Stripe TEST, bez skutečné úhrady.', NULL, NULL, '2026-10-26 23:42:39'),
(13, 40, 'TEST-40', 'faktura', 'zaplaceno', '2026-10-26', '2026-10-26', NULL, 'CZK', 'stripe', 'pi_3UUvRmRrdttDzBlz0ybh02O5', 'RAC-261026-BAE77FB53B', 'Mgr. Pavla Kovalovská', NULL, NULL, 'Hranice, Včelařská 2003/19', 'Karviná', '73301', 'Česká republika', 'kovalovska.pavla@seznam.cz', NULL, 'https://pavlakovalovska.eu', '2600042537', 'Hlavní účet', 'tuzemsky', '2010', 'CZK', 'CZ3620100000002600042537', 'FIOBCZPPXXX', 0, 'Tomáš Rybka', NULL, NULL, NULL, 'Lesní 10', 'Krásné Pole', '77845', 'CZ', 'milan.smiesko@seznam.cz', 270.00, 0.00, 270.00, 270.00, 'TESTOVACÍ DOKLAD – NEJDE O ÚČETNÍ ANI DAŇOVÝ DOKLAD. Stripe TEST, bez skutečné úhrady.', NULL, NULL, '2026-10-26 23:42:40'),
(14, 39, 'TEST-39', 'faktura', 'zaplaceno', '2026-10-26', '2026-10-26', NULL, 'CZK', 'stripe', 'pi_3UUvPTRrdttDzBlz0oaIIF5o', 'RAC-261026-E41B513440', 'Mgr. Pavla Kovalovská', NULL, NULL, 'Hranice, Včelařská 2003/19', 'Karviná', '73301', 'Česká republika', 'kovalovska.pavla@seznam.cz', NULL, 'https://pavlakovalovska.eu', '2600042537', 'Hlavní účet', 'tuzemsky', '2010', 'CZK', 'CZ3620100000002600042537', 'FIOBCZPPXXX', 0, 'milan vondrák', NULL, NULL, NULL, 'Ve finských 1892', 'Praha', '11150', 'CZ', 'milan.smiesko@seznam.cz', 270.00, 0.00, 270.00, 270.00, 'TESTOVACÍ DOKLAD – NEJDE O ÚČETNÍ ANI DAŇOVÝ DOKLAD. Stripe TEST, bez skutečné úhrady.', NULL, NULL, '2026-10-26 23:42:41'),
(15, 38, 'TEST-38', 'faktura', 'zaplaceno', '2026-10-26', '2026-10-26', NULL, 'CZK', 'stripe', 'pi_3UUu24RrdttDzBlz01IP8eCL', 'RAC-261026-DFE30ECEBB', 'Mgr. Pavla Kovalovská', NULL, NULL, 'Hranice, Včelařská 2003/19', 'Karviná', '73301', 'Česká republika', 'kovalovska.pavla@seznam.cz', NULL, 'https://pavlakovalovska.eu', '2600042537', 'Hlavní účet', 'tuzemsky', '2010', 'CZK', 'CZ3620100000002600042537', 'FIOBCZPPXXX', 0, 'Matyáš Smieško', NULL, NULL, NULL, 'Doma 23', 'Brno', '70070', 'CZ', 'milan.smiesko@seznam.cz', 199.00, 0.00, 199.00, 199.00, 'TESTOVACÍ DOKLAD – NEJDE O ÚČETNÍ ANI DAŇOVÝ DOKLAD. Stripe TEST, bez skutečné úhrady.', NULL, NULL, '2026-10-26 23:42:42'),
(16, 45, 'TEST-45', 'faktura', 'zaplaceno', '2026-10-26', '2026-10-26', NULL, 'CZK', 'stripe', 'pi_3UUwm7RrdttDzBlz0CtUKOxL', 'RAC-261026-E4B53CC468', 'Mgr. Pavla Kovalovská', NULL, NULL, 'Hranice, Včelařská 2003/19', 'Karviná', '73301', 'Česká republika', 'kovalovska.pavla@seznam.cz', NULL, 'https://pavlakovalovska.eu', '2600042537', 'Hlavní účet', 'tuzemsky', '2010', 'CZK', 'CZ3620100000002600042537', 'FIOBCZPPXXX', 0, 'Milan Lytasy', NULL, NULL, NULL, 'Lesní 10', 'Ostrava', '12345', 'CZ', 'milan.smiesko@seznam.cz', 270.00, 0.00, 270.00, 270.00, 'TESTOVACÍ DOKLAD – NEJDE O ÚČETNÍ ANI DAŇOVÝ DOKLAD. Stripe TEST, bez skutečné úhrady.', NULL, NULL, '2026-10-26 23:53:56');

-- --------------------------------------------------------

--
-- Struktura tabulky `fakturace_nastaveni`
--

CREATE TABLE `fakturace_nastaveni` (
  `id` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `nazev_dodavatele` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `ico` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dic` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ulice` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mesto` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `psc` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stat` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Česká republika',
  `email` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefon` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `web` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `platce_dph` tinyint(1) NOT NULL DEFAULT '0',
  `vychozi_sazba_dph` decimal(5,2) NOT NULL DEFAULT '0.00',
  `mena` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CZK',
  `nazev_uctu` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `typ_uctu` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'tuzemsky',
  `cislo_uctu` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kod_banky` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mena_uctu` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CZK',
  `iban` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bic` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rada_faktura_prefix` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'FV',
  `rada_faktura_rok` smallint(5) UNSIGNED NOT NULL DEFAULT '2026',
  `rada_faktura_dalsi_cislo` int(10) UNSIGNED NOT NULL DEFAULT '1',
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vytvoreno` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `upraveno` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Vypisuji data pro tabulku `fakturace_nastaveni`
--

INSERT INTO `fakturace_nastaveni` (`id`, `nazev_dodavatele`, `ico`, `dic`, `ulice`, `mesto`, `psc`, `stat`, `email`, `telefon`, `web`, `platce_dph`, `vychozi_sazba_dph`, `mena`, `nazev_uctu`, `typ_uctu`, `cislo_uctu`, `kod_banky`, `mena_uctu`, `iban`, `bic`, `rada_faktura_prefix`, `rada_faktura_rok`, `rada_faktura_dalsi_cislo`, `logo`, `vytvoreno`, `upraveno`) VALUES
(1, 'Mgr. Pavla Kovalovská', NULL, NULL, 'Hranice, Včelařská 2003/19', 'Karviná', '73301', 'Česká republika', 'kovalovska.pavla@seznam.cz', NULL, 'https://pavlakovalovska.eu', 0, 0.00, 'CZK', 'Hlavní účet', 'tuzemsky', '2600042537', '2010', 'CZK', 'CZ3620100000002600042537', 'FIOBCZPPXXX', 'RAC', 2026, 1, NULL, '2026-10-25 19:42:33', '2026-10-25 19:53:36');

-- --------------------------------------------------------

--
-- Struktura tabulky `fakturace_polozky`
--

CREATE TABLE `fakturace_polozky` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `doklad_id` bigint(20) UNSIGNED NOT NULL,
  `nazev` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `popis` text COLLATE utf8mb4_unicode_ci,
  `mnozstvi` decimal(12,3) NOT NULL DEFAULT '1.000',
  `jednotka` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ks',
  `cena_za_jednotku` decimal(14,2) NOT NULL DEFAULT '0.00',
  `sleva_procent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `sazba_dph` decimal(5,2) NOT NULL DEFAULT '0.00',
  `castka_bez_dph` decimal(14,2) NOT NULL DEFAULT '0.00',
  `castka_dph` decimal(14,2) NOT NULL DEFAULT '0.00',
  `castka_celkem` decimal(14,2) NOT NULL DEFAULT '0.00',
  `poradi` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Vypisuji data pro tabulku `fakturace_polozky`
--

INSERT INTO `fakturace_polozky` (`id`, `doklad_id`, `nazev`, `popis`, `mnozstvi`, `jednotka`, `cena_za_jednotku`, `sleva_procent`, `sazba_dph`, `castka_bez_dph`, `castka_dph`, `castka_celkem`, `poradi`) VALUES
(1, 1, 'Dva životy jedné lásky I - Kóma', 'ebook · de', 1.000, 'ks', 329.00, 0.00, 0.00, 329.00, 0.00, 329.00, 1),
(2, 2, 'Dva životy jedné lásky I - Kóma', 'ebook · cs', 1.000, 'ks', 329.00, 0.00, 0.00, 329.00, 0.00, 329.00, 1),
(3, 3, 'Dva životy jedné lásky II - Probuzení', 'ebook · cs', 1.000, 'ks', 190.00, 0.00, 0.00, 190.00, 0.00, 190.00, 1),
(4, 4, 'Když domov bolí', 'ebook · cs', 1.000, 'ks', 270.00, 0.00, 0.00, 270.00, 0.00, 270.00, 1),
(5, 5, 'Když domov bolí', 'ebook · en', 1.000, 'ks', 270.00, 0.00, 0.00, 270.00, 0.00, 270.00, 1),
(6, 6, 'Dva životy jedné lásky III - Skutečnost', 'ebook · cs', 1.000, 'ks', 199.00, 0.00, 0.00, 199.00, 0.00, 199.00, 1),
(7, 7, 'Dva životy jedné lásky I - Kóma', 'ebook · cs', 1.000, 'ks', 329.00, 0.00, 0.00, 329.00, 0.00, 329.00, 1),
(8, 8, 'Dva životy jedné lásky I - Kóma', 'ebook · cs', 1.000, 'ks', 329.00, 0.00, 0.00, 329.00, 0.00, 329.00, 1),
(9, 9, 'Když domov bolí', 'ebook · cs', 1.000, 'ks', 270.00, 0.00, 0.00, 270.00, 0.00, 270.00, 1),
(10, 10, 'Dva životy jedné lásky III - Skutečnost', 'ebook · cs', 1.000, 'ks', 199.00, 0.00, 0.00, 199.00, 0.00, 199.00, 1),
(11, 11, 'Poznej sám sebe. Pak pochopíš všechno.', 'ebook · cs', 1.000, 'ks', 199.00, 0.00, 0.00, 199.00, 0.00, 199.00, 1),
(12, 12, 'Když domov bolí', 'ebook · cs', 1.000, 'ks', 270.00, 0.00, 0.00, 270.00, 0.00, 270.00, 1),
(13, 13, 'Když domov bolí', 'ebook · cs', 1.000, 'ks', 270.00, 0.00, 0.00, 270.00, 0.00, 270.00, 1),
(14, 14, 'Když domov bolí', 'ebook · cs', 1.000, 'ks', 270.00, 0.00, 0.00, 270.00, 0.00, 270.00, 1),
(15, 15, 'Dva životy jedné lásky III - Skutečnost', 'ebook · cs', 1.000, 'ks', 199.00, 0.00, 0.00, 199.00, 0.00, 199.00, 1),
(16, 16, 'Když domov bolí', 'ebook · cs', 1.000, 'ks', 270.00, 0.00, 0.00, 270.00, 0.00, 270.00, 1);

-- --------------------------------------------------------

--
-- Struktura tabulky `languages`
--

CREATE TABLE `languages` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `native_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `locale` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flag_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Vypisuji data pro tabulku `languages`
--

INSERT INTO `languages` (`id`, `code`, `name`, `native_name`, `locale`, `flag_path`, `is_default`, `active`, `sort_order`, `created`, `updated`) VALUES
(1, 'cs', 'Čeština', 'Čeština', 'cs_CZ', NULL, 1, 1, 10, '2026-10-07 11:52:04', '2026-10-07 11:52:04'),
(2, 'sk', 'Slovenština', 'Slovenčina', 'sk_SK', NULL, 0, 1, 20, '2026-10-07 11:52:04', '2026-10-07 11:52:04'),
(3, 'pl', 'Polština', 'Polski', 'pl_PL', NULL, 0, 1, 30, '2026-10-07 11:52:04', '2026-10-07 11:52:04'),
(4, 'en', 'Angličtina', 'English', 'en_GB', NULL, 0, 1, 40, '2026-10-07 11:52:04', '2026-10-07 11:52:04'),
(5, 'de', 'Němčina', 'Deutsch', 'de_DE', NULL, 0, 1, 50, '2026-10-07 11:52:04', '2026-10-07 11:52:04'),
(6, 'fr', 'Francouzština', 'Français', 'fr_FR', 'uploads/languages/lang_5ec9ccd45b926d61ef2547e5.webp', 0, 1, 60, '2026-10-07 11:57:17', '2026-10-07 12:37:38');

-- --------------------------------------------------------

--
-- Struktura tabulky `pages`
--

CREATE TABLE `pages` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `template` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin_label` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Vypisuji data pro tabulku `pages`
--

INSERT INTO `pages` (`id`, `code`, `template`, `admin_label`, `active`, `sort_order`, `created`, `updated`) VALUES
(1, 'home', 'index.php', 'Úvodní stránka', 1, 10, '2026-10-07 13:28:24', '2026-10-07 13:28:24'),
(2, 'author', 'o-autorovi.php', 'O autorovi', 1, 20, '2026-10-07 13:40:38', '2026-10-07 13:40:38'),
(3, 'contact', 'kontakt.php', 'Kontakt', 1, 30, '2026-10-07 13:55:20', '2026-10-07 13:55:20');

-- --------------------------------------------------------

--
-- Struktura tabulky `page_sections`
--

CREATE TABLE `page_sections` (
  `id` int(10) UNSIGNED NOT NULL,
  `page_id` int(10) UNSIGNED NOT NULL,
  `code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `admin_label` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Vypisuji data pro tabulku `page_sections`
--

INSERT INTO `page_sections` (`id`, `page_id`, `code`, `admin_label`, `active`, `sort_order`, `created`, `updated`) VALUES
(1, 1, 'intro', 'Myšlenka, která všechno spojuje', 1, 10, '2026-10-07 13:28:24', '2026-10-07 13:28:24'),
(2, 1, 'books_intro', 'Knihy & projekty', 1, 20, '2026-10-07 13:28:24', '2026-10-07 13:28:24'),
(3, 1, 'author', 'O autorovi', 1, 30, '2026-10-07 13:28:24', '2026-10-07 13:28:24'),
(4, 1, 'upcoming_intro', 'Připravujeme', 1, 40, '2026-10-07 13:28:24', '2026-10-07 13:28:24'),
(5, 1, 'contact', 'Kontakt', 1, 50, '2026-10-07 13:28:24', '2026-10-07 13:28:24'),
(6, 1, 'audio', 'Audioukázka', 1, 60, '2026-10-07 13:28:24', '2026-10-07 13:28:24'),
(7, 2, 'header', 'Hlavička stránky', 1, 10, '2026-10-07 13:40:38', '2026-10-07 13:40:38'),
(8, 2, 'content', 'Obsah stránky', 1, 20, '2026-10-07 13:40:38', '2026-10-07 13:40:38'),
(9, 3, 'header', 'Hlavička stránky', 1, 10, '2026-10-07 13:55:20', '2026-10-07 13:55:20'),
(10, 3, 'form_intro', 'Úvod kontaktního formuláře', 1, 20, '2026-10-07 13:55:20', '2026-10-07 13:55:20'),
(11, 3, 'contact_info', 'Kontaktní údaje', 1, 30, '2026-10-07 13:55:20', '2026-10-07 13:55:20');

-- --------------------------------------------------------

--
-- Struktura tabulky `page_section_fields`
--

CREATE TABLE `page_section_fields` (
  `id` int(10) UNSIGNED NOT NULL,
  `section_id` int(10) UNSIGNED NOT NULL,
  `code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `admin_label` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `field_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Vypisuji data pro tabulku `page_section_fields`
--

INSERT INTO `page_section_fields` (`id`, `section_id`, `code`, `admin_label`, `field_type`, `required`, `sort_order`, `created`, `updated`) VALUES
(1, 1, 'tagline', 'Malý nadpis', 'text', 1, 10, '2026-10-07 13:28:24', '2026-10-07 13:28:24'),
(2, 1, 'title', 'Hlavní nadpis', 'textarea', 1, 20, '2026-10-07 13:28:24', '2026-10-07 13:28:24'),
(3, 1, 'quote', 'Text / citát', 'textarea', 1, 30, '2026-10-07 13:28:24', '2026-10-07 13:28:24'),
(4, 2, 'tagline', 'Malý nadpis', 'text', 1, 10, '2026-10-07 13:28:24', '2026-10-07 13:28:24'),
(5, 2, 'title', 'Hlavní nadpis', 'text', 1, 20, '2026-10-07 13:28:24', '2026-10-07 13:28:24'),
(6, 2, 'formats', 'Dostupné formáty', 'textarea', 1, 30, '2026-10-07 13:28:24', '2026-10-07 13:28:24'),
(7, 3, 'tagline', 'Malý nadpis', 'text', 1, 10, '2026-10-07 13:28:24', '2026-10-07 13:28:24'),
(8, 3, 'title', 'Hlavní nadpis', 'text', 1, 20, '2026-10-07 13:28:24', '2026-10-07 13:28:24'),
(9, 3, 'text_one', 'První odstavec', 'textarea', 1, 30, '2026-10-07 13:28:24', '2026-10-07 13:28:24'),
(10, 3, 'text_two', 'Druhý odstavec', 'textarea', 1, 40, '2026-10-07 13:28:24', '2026-10-07 13:28:24'),
(11, 4, 'tagline', 'Malý nadpis', 'text', 1, 10, '2026-10-07 13:28:24', '2026-10-07 13:28:24'),
(12, 4, 'title', 'Hlavní nadpis', 'text', 1, 20, '2026-10-07 13:28:24', '2026-10-07 13:28:24'),
(13, 4, 'quote', 'Text / citát', 'textarea', 1, 30, '2026-10-07 13:28:24', '2026-10-07 13:28:24'),
(14, 5, 'tagline', 'Malý nadpis', 'text', 1, 10, '2026-10-07 13:28:24', '2026-10-07 13:28:24'),
(15, 5, 'title', 'Hlavní nadpis', 'textarea', 1, 20, '2026-10-07 13:28:24', '2026-10-07 13:28:24'),
(16, 6, 'image_alt', 'Alternativní text obrázku', 'text', 0, 10, '2026-10-07 13:28:24', '2026-10-07 13:28:24'),
(17, 7, 'title', 'Nadpis stránky', 'text', 1, 10, '2026-10-07 13:40:38', '2026-10-07 13:40:38'),
(18, 8, 'heading', 'Hlavní nadpis', 'text', 1, 10, '2026-10-07 13:40:38', '2026-10-07 13:40:38'),
(19, 8, 'text', 'Text O autorovi', 'textarea', 0, 20, '2026-10-07 13:40:39', '2026-10-07 13:40:39'),
(20, 9, 'title', 'Nadpis stránky', 'text', 1, 10, '2026-10-07 13:55:20', '2026-10-07 13:55:20'),
(21, 10, 'tagline', 'Malý nadpis', 'text', 1, 10, '2026-10-07 13:55:20', '2026-10-07 13:55:20'),
(22, 10, 'title', 'Hlavní nadpis', 'textarea', 1, 20, '2026-10-07 13:55:20', '2026-10-07 13:55:20'),
(23, 11, 'address', 'Adresa', 'textarea', 0, 10, '2026-10-07 13:55:20', '2026-10-07 13:55:20'),
(24, 11, 'email', 'E-mail', 'text', 0, 20, '2026-10-07 13:55:20', '2026-10-07 13:55:20'),
(25, 11, 'phone', 'Telefon', 'text', 0, 30, '2026-10-07 13:55:20', '2026-10-07 13:55:20'),
(26, 11, 'company_info', 'IČ / DIČ / další údaje', 'textarea', 0, 40, '2026-10-07 13:55:20', '2026-10-07 13:55:20');

-- --------------------------------------------------------

--
-- Struktura tabulky `page_section_field_translations`
--

CREATE TABLE `page_section_field_translations` (
  `id` int(10) UNSIGNED NOT NULL,
  `field_id` int(10) UNSIGNED NOT NULL,
  `language_id` int(10) UNSIGNED NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Vypisuji data pro tabulku `page_section_field_translations`
--

INSERT INTO `page_section_field_translations` (`id`, `field_id`, `language_id`, `value`, `created`, `updated`) VALUES
(1, 16, 1, 'Audioukázka', '2026-10-07 13:28:24', '2026-10-27 17:32:55'),
(2, 7, 1, 'O autorovi', '2026-10-07 13:28:24', '2026-10-27 17:32:55'),
(3, 9, 1, 'Pavla Kovalovská je česká autorkyně, která ve své tvorbě zkoumá člověka, lidské vztahy a otázky, na které neexistují pohodlné odpovědi.', '2026-10-07 13:28:24', '2026-10-27 17:32:55'),
(4, 10, 1, 'Její autorský svět propojuje sebepoznání, lásku, rodinu i kritický pohled na společnost. Každý rukopis stojí na přesvědčení, že silný příběh nezačíná efektem, ale opravdovostí.', '2026-10-07 13:28:24', '2026-10-27 17:32:55'),
(5, 8, 1, 'Slova mají smysl, když v nich poznáš život.', '2026-10-07 13:28:24', '2026-10-27 17:32:55'),
(6, 6, 1, 'Knihy jsou dostupné v těchto podobách:\r\nElektronická kniha / Tištěná kniha / Audiokniha', '2026-10-07 13:28:24', '2026-10-27 17:32:55'),
(7, 4, 1, 'Autorský svět', '2026-10-07 13:28:24', '2026-10-27 17:32:55'),
(8, 5, 1, 'Knihy & projekty', '2026-10-07 13:28:24', '2026-10-27 17:32:55'),
(9, 14, 1, 'Některé věci začínají jednou zprávou.', '2026-10-07 13:28:24', '2026-10-27 17:32:55'),
(10, 15, 1, 'Pro vydavatelské nabídky, spolupráci a dotazy ke knihám můžeš autora kontaktovat přímo e-mailem.', '2026-10-07 13:28:24', '2026-10-27 17:32:55'),
(11, 3, 1, 'Každá kniha otevírá jinou kapitolu lidského života. Sebepoznání, láska, rodina, společnost i odvaha podívat se pravdě do očí. Jednotlivé příběhy. Jedna společná cesta.', '2026-10-07 13:28:24', '2026-10-27 17:32:55'),
(12, 1, 1, 'Myšlenka, která všechno spojuje', '2026-10-07 13:28:24', '2026-10-27 17:32:55'),
(13, 2, 1, 'Nejdelší cesta nevede přes svět.\r\nVede do člověka.', '2026-10-07 13:28:24', '2026-10-27 17:32:55'),
(14, 13, 1, 'Pavla Kovalovská', '2026-10-07 13:28:24', '2026-10-27 17:32:55'),
(15, 11, 1, 'Další kapitoly', '2026-10-07 13:28:24', '2026-10-27 17:32:55'),
(16, 12, 1, 'Na čem se pracuje', '2026-10-07 13:28:24', '2026-10-27 17:32:55'),
(33, 1, 2, 'Myšlienka, ktorá všetko spája', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(34, 1, 3, 'Myśl, która łączy wszystko', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(35, 1, 4, 'The idea that connects everything', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(36, 1, 5, 'Der Gedanke, der alles verbindet', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(37, 1, 6, 'L’idée qui relie tout', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(39, 2, 2, 'Najdlhšia cesta nevedie cez svet.\r\nVedie do človeka.', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(40, 2, 3, 'Najdłuższa droga nie prowadzi przez świat.\r\nProwadzi do człowieka.', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(41, 2, 4, 'The longest journey does not lead through the world.\r\nIt leads into a person.', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(42, 2, 5, 'Der längste Weg führt nicht durch die Welt.\r\nEr führt in den Menschen.', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(43, 2, 6, 'Le plus long voyage ne mène pas à travers le monde.\r\nIl mène au cœur de l’être humain.', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(45, 3, 2, 'Každá kniha otvára inú kapitolu ľudského života. Sebapoznanie, láska, rodina, spoločnosť aj odvaha pozrieť sa pravde do očí. Jednotlivé príbehy. Jedna spoločná cesta.', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(46, 3, 3, 'Każda książka otwiera inny rozdział ludzkiego życia. Samopoznanie, miłość, rodzina, społeczeństwo i odwaga, by spojrzeć prawdzie w oczy. Różne historie. Jedna wspólna droga.', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(47, 3, 4, 'Every book opens a different chapter of human life. Self-discovery, love, family, society, and the courage to face the truth. Different stories. One shared journey.', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(48, 3, 5, 'Jedes Buch öffnet ein anderes Kapitel des menschlichen Lebens. Selbsterkenntnis, Liebe, Familie, Gesellschaft und der Mut, der Wahrheit ins Auge zu sehen. Unterschiedliche Geschichten. Ein gemeinsamer Weg.', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(49, 3, 6, 'Chaque livre ouvre un chapitre différent de la vie humaine. La connaissance de soi, l’amour, la famille, la société et le courage de regarder la vérité en face. Des histoires différentes. Un chemin commun.', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(51, 4, 2, 'Autorský svet', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(52, 4, 3, 'Świat autora', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(53, 4, 4, 'The Author’s World', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(54, 4, 5, 'Die Welt des Autors', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(55, 4, 6, 'L’univers de l’auteur', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(57, 5, 2, 'Knihy & projekty', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(58, 5, 3, 'Książki i projekty', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(59, 5, 4, 'Books & Projects', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(60, 5, 5, 'Bücher & Projekte', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(61, 5, 6, 'Livres & projets', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(63, 6, 2, 'Knihy sú dostupné v týchto podobách:\r\nElektronická kniha / Tlačená kniha / Audiokniha', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(64, 6, 3, 'Książki są dostępne w następujących formatach:\r\nE-book / Książka drukowana / Audiobook', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(65, 6, 4, 'Books are available in the following formats:\r\nE-book / Printed book / Audiobook', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(66, 6, 5, 'Die Bücher sind in folgenden Formaten erhältlich:\r\nE-Book / Gedrucktes Buch / Hörbuch', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(67, 6, 6, 'Les livres sont disponibles dans les formats suivants :\r\nLivre numérique / Livre imprimé / Livre audio', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(69, 7, 2, 'O autorovi', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(70, 7, 3, 'O autorze', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(71, 7, 4, 'About the Author', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(72, 7, 5, 'Über den Autor', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(73, 7, 6, 'À propos de l’auteur', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(75, 8, 2, 'Slová majú zmysel, keď v nich spoznáš život.', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(76, 8, 3, 'Słowa mają sens, gdy odnajdujesz w nich życie.', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(77, 8, 4, 'Words have meaning when you recognize life in them.', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(78, 8, 5, 'Worte haben Bedeutung, wenn du in ihnen das Leben erkennst.', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(79, 8, 6, 'Les mots prennent tout leur sens lorsque tu y reconnais la vie.', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(81, 9, 2, 'Pavla Kovalovská je česká autorka, ktorá vo svojej tvorbe skúma človeka, medziľudské vzťahy a otázky, na ktoré neexistujú jednoduché odpovede.', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(82, 9, 3, 'Pavla Kovalovská jest czeską autorką, która w swojej twórczości zgłębia ludzką naturę, relacje międzyludzkie oraz pytania, na które nie ma łatwych odpowiedzi.', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(83, 9, 4, 'Pavla Kovalovská is a Czech author whose work explores human nature, relationships, and questions that have no easy answers.', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(84, 9, 5, 'Pavla Kovalovská ist eine tschechische Autorin, die sich in ihren Werken mit der menschlichen Natur, zwischenmenschlichen Beziehungen und Fragen beschäftigt, auf die es keine einfachen Antworten gibt.', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(85, 9, 6, 'Pavla Kovalovská est une auteure tchèque qui explore dans ses œuvres la nature humaine, les relations entre les individus et les questions auxquelles il n\'existe pas de réponses faciles.', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(87, 10, 2, 'Jej autorský svet prepája sebapoznanie, lásku, rodinu aj kritický pohľad na spoločnosť. Každý rukopis vychádza z presvedčenia, že silný príbeh sa nezačína efektom, ale autentickosťou.', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(88, 10, 3, 'Jej świat literacki łączy samopoznanie, miłość, rodzinę oraz krytyczne spojrzenie na społeczeństwo. Każdy rękopis opiera się na przekonaniu, że siła opowieści nie tkwi w efektowności, lecz w autentyczności.', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(89, 10, 4, 'Her literary world brings together self-discovery, love, family, and a critical perspective on society. Every manuscript is rooted in the belief that a powerful story begins not with spectacle, but with authenticity.', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(90, 10, 5, 'Ihre literarische Welt verbindet Selbsterkenntnis, Liebe, Familie und einen kritischen Blick auf die Gesellschaft. Jedem Manuskript liegt die Überzeugung zugrunde, dass eine bewegende Geschichte nicht mit Effekthascherei beginnt, sondern mit Authentizität.', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(91, 10, 6, 'Son univers littéraire mêle la connaissance de soi, l\'amour, la famille et un regard critique sur la société. Chaque manuscrit repose sur la conviction qu\'une histoire marquante ne naît pas d\'effets spectaculaires, mais de l\'authenticité.', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(93, 11, 2, 'Ďalšie kapitoly', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(94, 11, 3, 'Kolejne rozdziały', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(95, 11, 4, 'Next Chapters', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(96, 11, 5, 'Weitere Kapitel', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(97, 11, 6, 'Prochains chapitres', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(99, 12, 2, 'Na čom sa pracuje', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(100, 12, 3, 'Nad czym trwają prace', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(101, 12, 4, 'What’s in Progress', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(102, 12, 5, 'Woran gearbeitet wird', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(103, 12, 6, 'Ce qui est en cours', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(105, 13, 2, 'Pavla Kovalovská', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(106, 13, 3, 'Pavla Kovalovská', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(107, 13, 4, 'Pavla Kovalovská', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(108, 13, 5, 'Pavla Kovalovská', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(109, 13, 6, 'Pavla Kovalovská', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(111, 14, 2, 'Některé věci začínají jednou zprávou.', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(112, 14, 3, 'Niektóre rzeczy zaczynają się od jednej wiadomości.', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(113, 14, 4, 'Some things begin with a single message.', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(114, 14, 5, 'Manche Dinge beginnen mit einer einzigen Nachricht.', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(115, 14, 6, 'Certaines choses commencent par un simple message.', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(117, 15, 2, 'V prípade vydavateľských ponúk, spolupráce a otázok ku knihám môžeš autora kontaktovať priamo e-mailom.', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(118, 15, 3, 'W sprawie ofert wydawniczych, współpracy i pytań dotyczących książek możesz skontaktować się z autorem bezpośrednio przez e-mail.', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(119, 15, 4, 'For publishing offers, collaborations, and questions about the books, you can contact the author directly by email.', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(120, 15, 5, 'Für Verlagsangebote, Kooperationen und Fragen zu den Büchern kannst du den Autor direkt per E-Mail kontaktieren.', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(121, 15, 6, 'Pour les propositions d’édition, les collaborations et les questions concernant les livres, tu peux contacter l’auteur directement par e-mail.', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(123, 16, 2, 'Audioukážka', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(124, 16, 3, 'Fragment audiobooka', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(125, 16, 4, 'Audio Sample', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(126, 16, 5, 'Hörprobe', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(127, 16, 6, 'Extrait audio', '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(224, 18, 1, 'Připravujeme...', '2026-10-07 13:40:39', '2026-10-21 22:57:10'),
(225, 19, 1, NULL, '2026-10-07 13:40:39', '2026-10-21 22:57:10'),
(226, 17, 1, 'Mgr. Pavla Kovalovská', '2026-10-07 13:40:39', '2026-10-21 22:57:10'),
(324, 17, 2, 'Mgr. Pavla Kovalovská', '2026-10-07 13:41:36', '2026-10-21 22:57:10'),
(325, 17, 3, 'Mgr. Pavla Kovalovská', '2026-10-07 13:41:36', '2026-10-21 22:57:10'),
(326, 17, 4, 'Mgr. Pavla Kovalovská', '2026-10-07 13:41:36', '2026-10-21 22:57:10'),
(327, 17, 5, 'Mgr. Pavla Kovalovská', '2026-10-07 13:41:36', '2026-10-21 22:57:10'),
(328, 17, 6, 'Mgr. Pavla Kovalovská', '2026-10-07 13:41:36', '2026-10-21 22:57:10'),
(330, 18, 2, 'Pripravujeme...', '2026-10-07 13:41:36', '2026-10-21 22:57:10'),
(331, 18, 3, 'W przygotowaniu...', '2026-10-07 13:41:36', '2026-10-21 22:57:10'),
(332, 18, 4, 'Coming soon...', '2026-10-07 13:41:36', '2026-10-21 22:57:10'),
(333, 18, 5, 'In Vorbereitung...', '2026-10-07 13:41:36', '2026-10-21 22:57:10'),
(334, 18, 6, 'À venir...', '2026-10-07 13:41:36', '2026-10-21 22:57:10'),
(336, 19, 2, NULL, '2026-10-07 13:41:36', '2026-10-21 22:57:10'),
(337, 19, 3, NULL, '2026-10-07 13:41:36', '2026-10-21 22:57:10'),
(338, 19, 4, NULL, '2026-10-07 13:41:36', '2026-10-21 22:57:10'),
(339, 19, 5, NULL, '2026-10-07 13:41:36', '2026-10-21 22:57:10'),
(340, 19, 6, NULL, '2026-10-07 13:41:36', '2026-10-21 22:57:10'),
(395, 23, 1, 'Včelařská 2003/19\r\n733 01, Karviná', '2026-10-07 13:55:20', '2026-10-23 14:57:32'),
(396, 26, 1, NULL, '2026-10-07 13:55:20', '2026-10-23 14:57:32'),
(397, 24, 1, 'kovalovska.pavla@seznam.cz', '2026-10-07 13:55:20', '2026-10-23 14:57:32'),
(398, 25, 1, '+420 608 472 587', '2026-10-07 13:55:20', '2026-10-23 14:57:32'),
(399, 21, 1, 'Kontaktní formulář', '2026-10-07 13:55:20', '2026-10-23 14:57:32'),
(400, 22, 1, 'Pro vydavatelské nabídky, spolupráci a dotazy ke knihám můžeš autora kontaktovat.', '2026-10-07 13:55:20', '2026-10-23 14:57:32'),
(401, 20, 1, 'Kontaktní informace', '2026-10-07 13:55:20', '2026-10-23 14:57:32'),
(421, 20, 2, 'Kontaktné informácie', '2026-10-07 13:59:04', '2026-10-23 14:57:32'),
(422, 20, 3, 'Informacje kontaktowe', '2026-10-07 13:59:04', '2026-10-23 14:57:32'),
(423, 20, 4, 'Contact Information', '2026-10-07 13:59:04', '2026-10-23 14:57:32'),
(424, 20, 5, 'Kontaktinformationen', '2026-10-07 13:59:04', '2026-10-23 14:57:32'),
(425, 20, 6, 'Coordonnées', '2026-10-07 13:59:04', '2026-10-23 14:57:32'),
(427, 21, 2, 'Kontaktný formulár', '2026-10-07 13:59:04', '2026-10-23 14:57:32'),
(428, 21, 3, 'Formularz kontaktowy', '2026-10-07 13:59:04', '2026-10-23 14:57:32'),
(429, 21, 4, 'Contact Form', '2026-10-07 13:59:04', '2026-10-23 14:57:32'),
(430, 21, 5, 'Kontaktformular', '2026-10-07 13:59:04', '2026-10-23 14:57:32'),
(431, 21, 6, 'Formulaire de contact', '2026-10-07 13:59:04', '2026-10-23 14:57:32'),
(433, 22, 2, 'V prípade vydavateľských ponúk, spolupráce a otázok ku knihám môžeš autora kontaktovať.', '2026-10-07 13:59:04', '2026-10-23 14:57:32'),
(434, 22, 3, 'W sprawie ofert wydawniczych, współpracy i pytań dotyczących książek możesz skontaktować się z autorem.', '2026-10-07 13:59:04', '2026-10-23 14:57:32'),
(435, 22, 4, 'For publishing offers, collaborations, and questions about the books, you can contact the author.', '2026-10-07 13:59:04', '2026-10-23 14:57:32'),
(436, 22, 5, 'Für Verlagsangebote, Kooperationen und Fragen zu den Büchern kannst du den Autor kontaktieren.', '2026-10-07 13:59:04', '2026-10-23 14:57:32'),
(437, 22, 6, 'Pour les propositions d’édition, les collaborations et les questions concernant les livres, tu peux contacter l’auteur.', '2026-10-07 13:59:04', '2026-10-23 14:57:32'),
(439, 23, 2, NULL, '2026-10-07 13:59:04', '2026-10-23 14:57:32'),
(440, 23, 3, NULL, '2026-10-07 13:59:04', '2026-10-23 14:57:32'),
(441, 23, 4, NULL, '2026-10-07 13:59:04', '2026-10-23 14:57:32'),
(442, 23, 5, NULL, '2026-10-07 13:59:04', '2026-10-23 14:57:32'),
(443, 23, 6, NULL, '2026-10-07 13:59:04', '2026-10-23 14:57:32'),
(445, 24, 2, 'kovalovska.pavla@seznam.cz', '2026-10-07 13:59:04', '2026-10-23 14:57:32'),
(446, 24, 3, 'kovalovska.pavla@seznam.cz', '2026-10-07 13:59:04', '2026-10-23 14:57:32'),
(447, 24, 4, 'kovalovska.pavla@seznam.cz', '2026-10-07 13:59:04', '2026-10-23 14:57:32'),
(448, 24, 5, 'kovalovska.pavla@seznam.cz', '2026-10-07 13:59:04', '2026-10-23 14:57:32'),
(449, 24, 6, 'kovalovska.pavla@seznam.cz', '2026-10-07 13:59:04', '2026-10-23 14:57:32'),
(451, 25, 2, NULL, '2026-10-07 13:59:04', '2026-10-23 14:57:32'),
(452, 25, 3, NULL, '2026-10-07 13:59:04', '2026-10-23 14:57:32'),
(453, 25, 4, NULL, '2026-10-07 13:59:04', '2026-10-23 14:57:32'),
(454, 25, 5, NULL, '2026-10-07 13:59:04', '2026-10-23 14:57:32'),
(455, 25, 6, NULL, '2026-10-07 13:59:04', '2026-10-23 14:57:32'),
(457, 26, 2, NULL, '2026-10-07 13:59:04', '2026-10-23 14:57:32'),
(458, 26, 3, NULL, '2026-10-07 13:59:04', '2026-10-23 14:57:32'),
(459, 26, 4, NULL, '2026-10-07 13:59:04', '2026-10-23 14:57:32'),
(460, 26, 5, NULL, '2026-10-07 13:59:04', '2026-10-23 14:57:32'),
(461, 26, 6, NULL, '2026-10-07 13:59:04', '2026-10-23 14:57:32');

-- --------------------------------------------------------

--
-- Struktura tabulky `page_translations`
--

CREATE TABLE `page_translations` (
  `id` int(10) UNSIGNED NOT NULL,
  `page_id` int(10) UNSIGNED NOT NULL,
  `language_id` int(10) UNSIGNED NOT NULL,
  `page_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slug` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` text COLLATE utf8mb4_unicode_ci,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Vypisuji data pro tabulku `page_translations`
--

INSERT INTO `page_translations` (`id`, `page_id`, `language_id`, `page_title`, `slug`, `meta_title`, `meta_description`, `created`, `updated`) VALUES
(1, 1, 1, 'Úvodní stránka', NULL, 'Mgr. Pavla Kovalovská', 'Knihy a audioknihy', '2026-10-07 13:28:24', '2026-10-27 17:32:55'),
(3, 1, 2, NULL, NULL, NULL, NULL, '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(4, 1, 3, NULL, NULL, NULL, NULL, '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(5, 1, 4, NULL, NULL, NULL, NULL, '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(6, 1, 5, NULL, NULL, NULL, NULL, '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(7, 1, 6, NULL, NULL, NULL, NULL, '2026-10-07 13:33:15', '2026-10-27 17:32:55'),
(14, 2, 1, 'O autorovi', 'o-autorovi', 'Mgr. Pavla Kovalovská | O autorovi', 'Informace o autorce Mgr. Pavle Kovalovská a jeho tvorbě.', '2026-10-07 13:40:38', '2026-10-21 22:57:10'),
(22, 2, 2, 'O autorovi', NULL, 'O autorovi', 'Informácie o autorovi Štefanovi Rácovi a jeho tvorbe.', '2026-10-07 13:41:36', '2026-10-21 22:57:10'),
(23, 2, 3, 'O autorze', NULL, 'O autorze', 'Informacje o autorze Štefanie Rácu i jego twórczości.', '2026-10-07 13:41:36', '2026-10-21 22:57:10'),
(24, 2, 4, 'About the Author', NULL, 'About the Author', 'Information about the author Štefan Rác and his work.', '2026-10-07 13:41:36', '2026-10-21 22:57:10'),
(25, 2, 5, 'Über den Autor', NULL, 'Über den Autor', 'Informationen über den Autor Štefan Rác und sein Werk.', '2026-10-07 13:41:36', '2026-10-21 22:57:10'),
(26, 2, 6, 'À propos de l’auteur', NULL, 'À propos de l’auteur', 'Informations sur l’auteur Štefan Rác et son œuvre.', '2026-10-07 13:41:36', '2026-10-21 22:57:10'),
(45, 3, 1, 'Kontaktné informácie', 'kontakt', 'Mgr. Pavla Kovalovská  | Kontakt', 'Kontaktní informace a formulář pro spojení s autorem Štefanem Rácem.', '2026-10-07 13:55:20', '2026-10-23 14:57:32'),
(53, 3, 2, 'Kontaktné informácie', NULL, 'Kontaktné informácie', 'Kontaktné informácie', '2026-10-07 13:59:04', '2026-10-23 14:57:32'),
(54, 3, 3, 'Informacje kontaktowe', NULL, 'Informacje kontaktowe', 'Informacje kontaktowe', '2026-10-07 13:59:04', '2026-10-23 14:57:32'),
(55, 3, 4, 'Contact Information', NULL, 'Contact Information', 'Contact Information', '2026-10-07 13:59:04', '2026-10-23 14:57:32'),
(56, 3, 5, 'Kontaktinformationen', NULL, 'Kontaktinformationen', 'Kontaktinformationen', '2026-10-07 13:59:04', '2026-10-23 14:57:32'),
(57, 3, 6, 'Coordonnées', NULL, 'Coordonnées', 'Coordonnées', '2026-10-07 13:59:04', '2026-10-23 14:57:32');

-- --------------------------------------------------------

--
-- Struktura tabulky `products`
--

CREATE TABLE `products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `slug` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `author` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `release_date` date DEFAULT NULL,
  `category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('draft','active','hidden','sold_out') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `featured` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Vypisuji data pro tabulku `products`
--

INSERT INTO `products` (`id`, `slug`, `author`, `release_date`, `category_id`, `status`, `featured`, `sort_order`, `created`, `updated`) VALUES
(1, 'poznej-sam-sebe-pak-pochopis-vsechno', 'Mgr. Pavla Kovalovská', '2026-10-01', NULL, 'active', 0, 1, '2026-10-08 09:26:23', '2026-10-21 10:12:46'),
(2, 'poznej-sam-sebe-pak-pochopis-vsechno-komunikace-se-sebou', 'Mgr. Pavla Kovalovská', '2026-10-01', NULL, 'draft', 0, 2, '2026-10-09 07:20:24', '2026-10-21 10:07:51'),
(3, 'poznej-sam-sebe-pak-pochopis-vsechno-rozsirena-verze', 'Mgr. Pavla Kovalovská', '2026-09-11', NULL, 'draft', 0, 3, '2026-10-09 10:21:09', '2026-10-21 10:08:01'),
(4, 'laska', 'Mgr. Pavla Kovalovská', NULL, NULL, 'draft', 0, 0, '2026-10-12 06:55:52', '2026-10-21 10:07:34'),
(5, 'zivot', 'Mgr. Pavla Kovalovská', NULL, NULL, 'draft', 0, 0, '2026-10-12 06:59:33', '2026-10-21 10:07:25'),
(6, 'laska-prvni-dil-koma', 'Mgr. Pavla Kovalovská', NULL, NULL, 'draft', 0, 0, '2026-10-12 07:02:10', '2026-10-16 18:20:03'),
(7, 'civilioza', 'Mgr. Pavla Kovalovská', NULL, NULL, 'draft', 0, 0, '2026-10-12 07:08:55', '2026-10-16 18:19:56'),
(8, 'stupne-vedomi', 'Mgr. Pavla Kovalovská', NULL, NULL, 'draft', 0, 0, '2026-10-12 07:09:51', '2026-10-16 18:19:47'),
(9, 'mezi-psychopaty', 'Mgr. Pavla Kovalovská', NULL, NULL, 'draft', 0, 0, '2026-10-12 07:11:26', '2026-10-21 10:07:16'),
(10, 'dva-zivoty-jedne-lasky-3-skutecnost', 'Mgr. Pavla Kovalovská', '2026-10-23', NULL, 'active', 0, 0, '2026-10-23 11:06:50', '2026-10-23 11:06:50'),
(11, 'dva-zivoty-jedne-lasky-2-probuzeni', 'Mgr. Pavla Kovalovská', '2026-10-23', NULL, 'active', 0, 0, '2026-10-23 11:26:17', '2026-10-23 11:26:17'),
(12, 'dva-zivoty-jedne-lasky-1-koma', 'Mgr. Pavla Kovalovská', NULL, NULL, 'active', 0, 0, '2026-10-23 11:50:54', '2026-10-23 13:09:01'),
(13, 'kdyz-domov-boli', 'Mgr. Pavla Kovalovská', '2026-10-25', NULL, 'active', 0, 0, '2026-10-25 12:05:23', '2026-10-25 13:14:22');

-- --------------------------------------------------------

--
-- Struktura tabulky `product_categories`
--

CREATE TABLE `product_categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `product_category_translations`
--

CREATE TABLE `product_category_translations` (
  `category_id` bigint(20) UNSIGNED NOT NULL,
  `language_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `product_files`
--

CREATE TABLE `product_files` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `variant_id` bigint(20) UNSIGNED NOT NULL,
  `purpose` enum('paid','preview') COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT '0',
  `media_type` enum('pdf','epub','mp3','m4a') COLLATE utf8mb4_unicode_ci NOT NULL,
  `storage_key` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` bigint(20) UNSIGNED NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Vypisuji data pro tabulku `product_files`
--

INSERT INTO `product_files` (`id`, `variant_id`, `purpose`, `is_published`, `media_type`, `storage_key`, `original_filename`, `mime_type`, `file_size`, `created`) VALUES
(1, 42, 'paid', 0, 'pdf', '9e81a7715c612ac3921b70c25758815ad13286b8789ce62d', 'testovaci_ebook.pdf', 'application/pdf', 2801, '2026-10-16 07:51:43'),
(2, 24, 'paid', 0, 'mp3', '1036f50d040cb38ece79aaaab3a6df2db663a2fe671428d5', 'testovaci_audiokniha.mp3', 'audio/mpeg', 16602, '2026-10-16 07:51:58'),
(3, 42, 'preview', 0, 'pdf', '2bd90fc5df429ea8ee59f949e26e2857685c75364ada2a00', 'testovaci_ebook -ukazka.pdf', 'application/pdf', 2801, '2026-10-16 07:52:13'),
(4, 182, 'paid', 1, 'pdf', 'b50af4fbdbfe6a333adaf40a53d27002c9d96e736dd11565', 'Poznej_sam_sebe_Rozhovor_se_sebou_EKNIHA.pdf', 'application/pdf', 169533403, '2026-10-21 19:18:39'),
(5, 183, 'paid', 1, 'pdf', 'b6f8f2ce8ec4160cf0c66e6004c538d13a2b4774f379f569', 'Know_Yourself_English_Complete_Reading.pdf', 'application/pdf', 33008526, '2026-10-21 20:18:18'),
(6, 183, 'preview', 1, 'pdf', '76ca4d9c7e04e05f51dbba8d6830983929c001730a3adedd', 'Ukazka_EN_kapitoly_1_a_7.pdf', 'application/pdf', 5782010, '2026-10-21 20:39:34'),
(7, 181, 'paid', 1, 'pdf', '445f807e3b65fb59661c170631e481586def949757752b07', 'Poznaj_Samego_Siebie_PL_Cala_Ksiazka.pdf', 'application/pdf', 34898663, '2026-10-22 07:55:15'),
(8, 184, 'paid', 1, 'pdf', '78d0704b69b92057a069078f74d991fefdf66c35f5bef365', 'Conocete_A_Ti_Mismo_ES_Libro_Completo-ESP.pdf', 'application/pdf', 55159449, '2026-10-22 08:20:02'),
(9, 185, 'paid', 1, 'pdf', '6f970c2a9293505114404e25f2837d776531d5f54e7f67ff', 'Iraf_Nafsak_AR_AlKitab_AlKamil.pdf', 'application/pdf', 38621912, '2026-10-22 08:26:32'),
(10, 186, 'paid', 1, 'pdf', '18069e7d21614ee7c96bb64185bb2929c2f7d09c19428416', 'Conosci_Te_Stesso_IT_Libro_Completo.pdf', 'application/pdf', 34872577, '2026-10-22 09:07:52'),
(11, 187, 'paid', 1, 'pdf', '2cd2f4c1de954675f2b23d7e77549ff928aab33114c01954', 'Erkenne_Dich_Selbst_DE_Gesamtes_Buch.pdf', 'application/pdf', 33006240, '2026-10-22 09:12:50'),
(12, 188, 'paid', 1, 'pdf', '9a21253b6c87971cdc9377936a7ac7d3759d63c884038f92', 'Conheca_A_Si_Mesmo_PT_BR_Livro_Completo.pdf', 'application/pdf', 36719222, '2026-10-22 09:21:36'),
(13, 189, 'paid', 1, 'pdf', '14ff8aaff6657ffc061905b473f8bd5625bb9b960e039760', 'Poznai_Sebya_RU_Polnaya_Kniga.pdf', 'application/pdf', 38669921, '2026-10-22 09:28:37'),
(14, 185, 'paid', 1, 'epub', 'bac99dc30719d150e70c3e07c0c96258ee218701457572bd', 'Poznej_sam_sebe_ARABSTINA_EMAIL.epub', 'application/epub+zip', 10201312, '2026-10-22 10:39:33'),
(15, 190, 'paid', 1, 'pdf', '66b978d9bfad268b0483f71853274b638449c8599893dc12', 'Poznaj_Sam_Seba_SK_Cela_Kniha.pdf', 'application/pdf', 34858889, '2026-10-22 10:49:29'),
(16, 190, 'paid', 1, 'epub', '8248150845ac43918b40a85b1976fdd2fcef90712a5b059f', 'Poznaj_Sam_Seba_SK.epub', 'application/epub+zip', 32422010, '2026-10-22 10:50:21'),
(17, 187, 'paid', 1, 'epub', '4e0e743d9ea7a46e6986a9348d34d7638782aef57e85dbb2', 'Erkenne_Dich_Selbst_DE.epub', 'application/epub+zip', 31012855, '2026-10-22 10:58:49'),
(18, 189, 'paid', 1, 'epub', '46f2bccd6b7ad2efee369b28c1018219b63cbcae1db4ceee', 'Poznai_Sebya_RU.epub', 'application/epub+zip', 35328618, '2026-10-22 11:00:40'),
(19, 188, 'paid', 1, 'epub', 'c3d2b56217fb375517531b51850abc4f7dd93aa22a489f42', 'Conheca_A_Si_Mesmo_PT_BR.epub', 'application/epub+zip', 33900132, '2026-10-22 11:02:04'),
(20, 186, 'paid', 1, 'epub', '0d743f1fc69a4dc983735d3c89fa21530a2ffa84f5002f02', 'Conosci_Te_Stesso_IT.epub', 'application/epub+zip', 32435869, '2026-10-22 11:03:10'),
(21, 191, 'preview', 1, 'pdf', 'f351328906e1d2d6ec8058ea08e73cecca13366257aa721e', 'Connais_toi_toi_meme_FR_Ukazka_1_a_7.pdf', 'application/pdf', 11540337, '2026-10-22 17:21:59'),
(22, 184, 'paid', 1, 'epub', '0b40187568a4b6b0836a9d1948eabeddae5180a1783b645e', 'Conocete_A_Ti_Mismo_ES.epub', 'application/epub+zip', 33206202, '2026-10-22 17:31:39'),
(23, 192, 'paid', 1, 'pdf', '2e201a976b4735b8bdec8f352d48a17a8a130165cd35854e', 'Piznai_Sebe_UK_Povna_Knyha.pdf', 'application/pdf', 38659353, '2026-10-22 17:45:38'),
(24, 192, 'paid', 1, 'epub', '1345f64fc5c3bd5180a68bb254bd01d02fd8968aba6611f9', 'Piznai_Sebe_UK.epub', 'application/epub+zip', 35309592, '2026-10-22 17:47:21'),
(25, 181, 'preview', 1, 'pdf', '2be41b7dc25d0e8a789219783f92cb000e4648eccfdf9a56', 'Ukazka_PL_kapitoly_1_a_7.pdf', 'application/pdf', 5786947, '2026-10-22 17:49:22'),
(26, 184, 'preview', 1, 'pdf', '2426dc015ae7f77f933411279da68fd14ca83354760b7a0a', 'Ukazka_ES_kapitoly_1_a_7.pdf', 'application/pdf', 5776980, '2026-10-22 17:50:23'),
(27, 188, 'preview', 1, 'pdf', '1f9a0833829dd2339675414b2d318459364b608c7881a221', 'Ukazka_PT_BR_kapitoly_1_a_7.pdf', 'application/pdf', 5771969, '2026-10-22 17:51:27'),
(28, 187, 'preview', 1, 'pdf', 'f2196a5e0d41e32695ae0164b37faff6c644521687652740', 'Ukazka_DE_kapitoly_1_a_7.pdf', 'application/pdf', 5760232, '2026-10-22 17:52:25'),
(29, 190, 'preview', 1, 'pdf', 'de0088c90b9051d8b2f0f27f55a126c2b154fbf3ea9c9caf', 'Ukazka_SK_kapitoly_1_a_7.pdf', 'application/pdf', 5770445, '2026-10-22 17:53:09'),
(30, 186, 'preview', 1, 'pdf', '284cdcbf0eb208d021845039be23864cc6d3d86878d72813', 'Ukazka_IT_kapitoly_1_a_7.pdf', 'application/pdf', 5765388, '2026-10-22 17:53:58'),
(31, 185, 'preview', 1, 'pdf', '352fde5155f91efbd42b0d3c7f11f2c7edb674e3b411d073', 'Ukazka_AR_kapitoly_1_a_7.pdf', 'application/pdf', 5224713, '2026-10-22 17:54:47'),
(32, 192, 'preview', 1, 'pdf', '9e8ec9844a53d36adbc06476638c56987b0d5bbb6ed252bc', 'Ukazka_UK_kapitoly_1_a_7.pdf', 'application/pdf', 5779319, '2026-10-22 17:55:29'),
(33, 189, 'preview', 1, 'pdf', '35da6716237525b398ae0e8ef0faa56e52c06f222a6ccccd', 'Ukazka_RU_kapitoly_1_a_7.pdf', 'application/pdf', 5822719, '2026-10-22 17:56:12'),
(34, 193, 'paid', 1, 'pdf', '52f65d4a4b57ae5ea5bf047d5b9274ceadcf9ac779244b8e', 'Skutecnost_III_elektronicka_kniha.pdf', 'application/pdf', 19805726, '2026-10-23 11:11:15'),
(35, 193, 'paid', 0, 'pdf', 'ff1e08ccb551530015559c2d67de76fab8db2fbd6e1d8a56', 'Probuzeni_II_elektronicka_kniha.pdf', 'application/pdf', 21304570, '2026-10-23 11:29:30'),
(36, 194, 'paid', 1, 'pdf', '67688f1259c28cf69642d80bd21b4e97bd9fed3dabb9fc7f', 'Probuzeni_II_elektronicka_kniha.pdf', 'application/pdf', 21304570, '2026-10-23 11:38:48'),
(37, 195, 'paid', 1, 'pdf', '6a6ea107ab087660842f9e312ae200412c635c810493f559', 'Koma_I_elektronicka_kniha.pdf', 'application/pdf', 20783826, '2026-10-23 11:52:41'),
(38, 197, 'paid', 1, 'pdf', '95f4ffcdaccfcce8d41a76168fcda49d7c9e6184b9e639d4', 'Kdyz_domov_boli.pdf', 'application/pdf', 34516737, '2026-10-25 12:29:56'),
(39, 197, 'preview', 1, 'pdf', '20efb9baf98f01b95f5d681c5216293a915d3c21052e3c71', 'Kdyz_domov_boli_UKAZKA_CS.pdf', 'application/pdf', 3466289, '2026-10-25 12:30:35'),
(40, 198, 'paid', 1, 'pdf', '22a738422ab2e0c3bc7d5ca77480b8044bb5b06c89cc4190', 'Kdyz_domov_boli_SK.pdf', 'application/pdf', 6567803, '2026-10-25 12:32:02'),
(41, 199, 'preview', 1, 'pdf', 'e2635b1d4de8dea657acfe7576c07bc3b6de12488cb0fe4d', 'Kdyz_domov_boli_UKAZKA_PL.pdf', 'application/pdf', 2741589, '2026-10-25 12:32:50'),
(42, 200, 'paid', 1, 'pdf', '65c52030f3df24989c063f6540d66f40001618291e4db3c2', 'Kdyz_domov_boli_EN.pdf', 'application/pdf', 6546935, '2026-10-25 12:33:28'),
(43, 200, 'preview', 1, 'pdf', '3c05a5ddaf84917951cafd99816183c104d8434aca80cd57', 'Kdyz_domov_boli_UKAZKA_EN.pdf', 'application/pdf', 2724770, '2026-10-25 12:33:51'),
(44, 201, 'paid', 1, 'pdf', '22fc11a35a91fd3681f4491e615f58dc59a5d01de6e01d54', 'Kdyz_domov_boli_DE.pdf', 'application/pdf', 6657873, '2026-10-25 12:34:48'),
(45, 201, 'preview', 1, 'pdf', '032e2d62be2d92d516396414aaccf42fd75c767ff4d2773a', 'Kdyz_domov_boli_UKAZKA_DE.pdf', 'application/pdf', 2727693, '2026-10-25 12:35:23'),
(46, 202, 'paid', 1, 'pdf', '34c47670b489e8e3ad12e91f51914a25e34107e5cd720649', 'Kdyz_domov_boli_FR.pdf', 'application/pdf', 6647359, '2026-10-25 12:36:07'),
(47, 202, 'preview', 1, 'pdf', '2f49f6f4b42020b5acda7be067f39bafc471317581caf863', 'Kdyz_domov_boli_UKAZKA_FR.pdf', 'application/pdf', 2729748, '2026-10-25 12:36:24'),
(48, 203, 'paid', 1, 'pdf', '5a3058f5084bcc48b058b364da1b1150cfc7b7fb9ec94371', 'Kdyz_domov_boli_IT.pdf', 'application/pdf', 6610209, '2026-10-25 12:46:17'),
(49, 203, 'preview', 1, 'pdf', 'cba8aca72940c8643a0a9d80684fbf10ce706f1f28159eb0', 'Kdyz_domov_boli_UKAZKA_IT.pdf', 'application/pdf', 2726504, '2026-10-25 12:46:36'),
(50, 204, 'preview', 1, 'pdf', '1cceb6c6ce61c1e4fe45f73d9f7bd4a005150888677e2c3f', 'Kdyz_domov_boli_UKAZKA_ES.pdf', 'application/pdf', 2727510, '2026-10-25 12:47:02'),
(51, 204, 'paid', 1, 'pdf', 'ce99853d0b6e67960b830598fc84550f75e1fe4919c0da16', 'Kdyz_domov_boli_ES.pdf', 'application/pdf', 6607640, '2026-10-25 12:47:31'),
(52, 205, 'paid', 1, 'pdf', '9005879ccc2949f60b15a119433066a349dfc20c87b200dd', 'Kdyz_domov_boli_AR.pdf', 'application/pdf', 6825591, '2026-10-25 12:48:16'),
(53, 205, 'preview', 1, 'pdf', '5194cda3dc55155082f7e0a34cdb84c05966d9cba316de51', 'Kdyz_domov_boli_UKAZKA_ES.pdf', 'application/pdf', 2727510, '2026-10-25 12:48:38'),
(54, 206, 'paid', 1, 'pdf', 'ba5af4c8cce15c5f6580a69feca925109f993d48905efc72', 'Kdyz_domov_boli_PT_BR.pdf', 'application/pdf', 6612911, '2026-10-25 12:49:16'),
(55, 207, 'paid', 1, 'pdf', '726757c1a588f67232c783987866fe4210cd48cc4e969038', 'Kdyz_domov_boli_RU.pdf', 'application/pdf', 6630830, '2026-10-25 12:50:09'),
(56, 207, 'preview', 1, 'pdf', 'a8254241a18cad17051835082dedc30818ae112aeb6fc125', 'Kdyz_domov_boli_UKAZKA_RU.pdf', 'application/pdf', 2775933, '2026-10-25 12:50:31'),
(57, 208, 'paid', 1, 'pdf', 'da624dfa189c53d1035a0bc3bd0edb5deced8df8e9968e40', 'Kdyz_domov_boli_UK.pdf', 'application/pdf', 6614325, '2026-10-25 12:51:08'),
(58, 208, 'preview', 1, 'pdf', '79e5b4d07017bcba966767b1ac9c36057778ca79ae96d6ab', 'Kdyz_domov_boli_UK.pdf', 'application/pdf', 6614325, '2026-10-25 12:51:35'),
(59, 209, 'preview', 1, 'pdf', 'e87ef6c5ce110b90ea18e090bec80703c6ceec22ad759a61', 'Koma_I_ukazka_sk.pdf', 'application/pdf', 8713054, '2026-10-25 13:31:08'),
(60, 210, 'preview', 1, 'pdf', 'b1a7ddc7bdab02ce7acb0571ee48f9b4f23029f1b5e04f6e', 'Koma_I_ukazka_pl.pdf', 'application/pdf', 4745577, '2026-10-25 13:31:41'),
(61, 211, 'preview', 1, 'pdf', '54c99b015d743e05cf80679dc9c7728176e2b2f16715bf14', 'Koma_I_ukazka_en.pdf', 'application/pdf', 4742649, '2026-10-25 13:32:18'),
(62, 212, 'preview', 1, 'pdf', '59e67d3d1e3162cd7ebab5ca0c5248b7ca7568afa00ae71c', 'Koma_I_ukazka_de.pdf', 'application/pdf', 7443756, '2026-10-25 13:32:56'),
(63, 213, 'preview', 1, 'pdf', '5302d2e816344ba771776c0c4ee1ea83be129947b4b41157', 'Koma_I_ukazka_fr.pdf', 'application/pdf', 7444279, '2026-10-25 13:33:33'),
(64, 214, 'preview', 1, 'pdf', '39889731ce48badc8fd621ccaf4d4f1fd430e409fceb01bc', 'Koma_I_ukazka_it.pdf', 'application/pdf', 7441797, '2026-10-25 13:34:05'),
(65, 215, 'preview', 1, 'pdf', 'd03561f5fb48ad73625f22bf1bbaf82bb33e65993989c25d', 'Koma_I_ukazka_es.pdf', 'application/pdf', 7442978, '2026-10-25 13:34:40'),
(66, 216, 'preview', 1, 'pdf', 'ae06f5d10b838cde478aab53609e6e6bf2b30d5807683910', 'Koma_I_ukazka_ar.pdf', 'application/pdf', 5123257, '2026-10-25 13:35:12'),
(67, 217, 'preview', 1, 'pdf', '41487ef50ec090a81639727322874628c4264d9edd524ff1', 'Koma_I_ukazka_pt-BR.pdf', 'application/pdf', 7441415, '2026-10-25 13:35:44'),
(68, 218, 'preview', 1, 'pdf', 'dbab84a203685f2cbdaa779c230847090f41b1923a892af0', 'Koma_I_ukazka_ru.pdf', 'application/pdf', 4740224, '2026-10-25 13:36:11'),
(69, 219, 'preview', 1, 'pdf', '7a41e83493ab18166974e5345ba1a24bfa6a7dbd16e155cc', 'Koma_I_ukazka_uk.pdf', 'application/pdf', 6102305, '2026-10-25 13:36:40'),
(70, 211, 'paid', 1, 'pdf', 'd5038500da440bccc69eb6e7b72264c6dcb7e64c171eaec2', 'Koma_I_en_elektronicka_kniha.pdf', 'application/pdf', 20703827, '2026-10-25 13:39:48'),
(71, 211, 'paid', 1, 'epub', '25f09acb8573a5cdc8e6e2e06fb86b6700db1aae5589f380', 'Koma_I_en.epub', 'application/epub+zip', 30467871, '2026-10-25 13:41:44'),
(72, 212, 'paid', 1, 'epub', '4773a0d373ad73a86d0536932947748d10ccb114b9d641f0', 'Koma_I_de.epub', 'application/epub+zip', 30514532, '2026-10-25 13:45:31'),
(73, 212, 'paid', 1, 'pdf', '945702da0a170fdf0065f564bb3a95aeea3978c17a7a6a5f', 'Koma_I_de_elektronicka_kniha.pdf', 'application/pdf', 20278981, '2026-10-25 16:46:49'),
(74, 210, 'paid', 1, 'pdf', '55a4886362dfe4a2bb0ec56149b875adda0519b4b16e23b1', 'Koma_I_pl_elektronicka_kniha.pdf', 'application/pdf', 20305712, '2026-10-26 11:08:49'),
(75, 210, 'paid', 1, 'epub', 'e740b1f11dc6a8defc41b43280be67dee08f30cf25673b90', 'Koma_I_pl.epub', 'application/epub+zip', 30520479, '2026-10-26 11:09:58'),
(76, 195, 'preview', 1, 'pdf', '93a4256fa8d218c6d0b5d5b860200744c24b234fb7fd1b7c', 'Koma_I_ukazka_cs.pdf', 'application/pdf', 3009624, '2026-10-26 11:11:18'),
(77, 209, 'paid', 1, 'pdf', '1feb25b0f7a5c16e6c88fe704d0b7abbfb77204a22a9f306', 'Koma_I_sk_elektronicka_kniha.pdf', 'application/pdf', 19987647, '2026-10-26 11:17:37'),
(78, 209, 'paid', 1, 'epub', '820dab01ff4e3e4c2ef9da6ae7165fdfdaf9d0ff7495a8ce', 'Koma_I_sk.epub', 'application/epub+zip', 30485656, '2026-10-26 11:18:42'),
(79, 215, 'paid', 1, 'pdf', '697080a13338e1e4df25b125bbe8e881a1779fc62ec0deb6', 'Koma_I_es_elektronicka_kniha.pdf', 'application/pdf', 19716205, '2026-10-26 11:19:58'),
(80, 215, 'paid', 1, 'epub', 'a95b50651f2d788b406a854c587745aed9555af1e0bacc82', 'Koma_I_es.epub', 'application/epub+zip', 30494610, '2026-10-26 11:21:03'),
(81, 217, 'paid', 1, 'pdf', '7232172ae6290e2d93a43056bf61bbe0925e3e7050a19ae8', 'Koma_I_pt-BR_elektronicka_kniha.pdf', 'application/pdf', 20063841, '2026-10-26 11:22:11'),
(82, 217, 'paid', 1, 'epub', '54fc9f9f526d2bbe088ba3ecd459c2c120871a117aa95e75', 'Koma_I_pt-BR.epub', 'application/epub+zip', 30500287, '2026-10-26 11:23:33'),
(83, 213, 'paid', 1, 'pdf', 'b77850fe6f8a51860d78a81c53c590f9655cf798ee7cc117', 'Koma_I_fr_elektronicka_kniha.pdf', 'application/pdf', 20166864, '2026-10-26 11:32:37'),
(84, 213, 'paid', 1, 'epub', '2fe394937ed195d8e6d8ada54dcf4cee70d60413db036836', 'Koma_I_fr.epub', 'application/epub+zip', 30504306, '2026-10-26 11:33:45'),
(85, 214, 'paid', 1, 'pdf', '21404c82cf72f885be8e9a5c767fba2b38a50af7dbc2a457', 'Koma_I_it_elektronicka_kniha.pdf', 'application/pdf', 20757897, '2026-10-26 11:35:38'),
(86, 214, 'paid', 1, 'epub', '6ff3f9349e3175ef544d554d2e713ee1be831aab6ff2540f', 'Koma_I_it.epub', 'application/epub+zip', 30490137, '2026-10-26 11:37:05'),
(87, 216, 'paid', 1, 'pdf', '75d422d49b86c3f5a056898437057bcc0fbe65ff3f30cb12', 'Koma_I_ar_elektronicka_kniha.pdf', 'application/pdf', 34679352, '2026-10-26 11:39:53'),
(88, 216, 'paid', 1, 'epub', '4b607d7b52a74d6e1fe506126b5d1d7b18d99522666d8ec7', 'Koma_I_ar.epub', 'application/epub+zip', 31224479, '2026-10-26 11:42:51'),
(89, 218, 'paid', 1, 'pdf', '076abb5ef4b58d9c828d366b0856b8539bd88c9453d92560', 'Koma_I_ru_elektronicka_kniha.pdf', 'application/pdf', 18875484, '2026-10-26 11:43:52'),
(90, 218, 'paid', 1, 'epub', '3a20846a7c1ded14ecf49ef45db3067c7a1a6e6c7db895ec', 'Koma_I_ru.epub', 'application/epub+zip', 30539876, '2026-10-26 11:45:09'),
(91, 219, 'paid', 1, 'pdf', 'cdb956a468d1499dc1a52a031a171bc50e2344cd18fd3ab6', 'Koma_I_uk_elektronicka_kniha.pdf', 'application/pdf', 19382310, '2026-10-26 11:46:10'),
(92, 219, 'paid', 1, 'epub', 'db40120158d147406a86a44ba4c99b3cbb027a87eb32c372', 'Koma_I_uk.epub', 'application/epub+zip', 30726980, '2026-10-26 11:49:13');

-- --------------------------------------------------------

--
-- Struktura tabulky `product_images`
--

CREATE TABLE `product_images` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `language_code` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alt_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `sort_order` int(11) NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Vypisuji data pro tabulku `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `language_code`, `file_path`, `alt_text`, `is_primary`, `sort_order`, `created`) VALUES
(2, 2, NULL, 'uploads/products/e20c799037dbcb77cb65b2c511d80535.webp', NULL, 1, 10, '2026-10-09 07:22:53'),
(3, 2, NULL, 'uploads/products/1561674ce3144fdbb0fbdef320b80fc2.webp', NULL, 0, 20, '2026-10-09 07:33:29'),
(4, 3, NULL, 'uploads/products/75037ab96d5e9a0001d921e9decc6882.webp', NULL, 1, 10, '2026-10-09 10:21:47'),
(5, 4, NULL, 'uploads/products/9f2228c8af21a0b780971bc7aa5cfc63.webp', NULL, 1, 10, '2026-10-12 06:57:07'),
(6, 5, NULL, 'uploads/products/cd0864208f2815e109bc5e951be8d22a.webp', NULL, 1, 10, '2026-10-12 07:00:04'),
(7, 6, NULL, 'uploads/products/a8ba246d753d971b32fe039f7dc04657.webp', NULL, 1, 10, '2026-10-12 07:02:25'),
(8, 9, NULL, 'uploads/products/3d6f46dbafa967913e68090d8211f03f.webp', NULL, 1, 10, '2026-10-12 07:11:42'),
(9, 8, NULL, 'uploads/products/9e2936fe9b261504f07ba9ee1b719e40.webp', NULL, 1, 10, '2026-10-12 09:11:41'),
(10, 7, NULL, 'uploads/products/e0ff2559e49b7707ec0b2ed257ed792e.webp', NULL, 1, 10, '2026-10-12 09:13:49'),
(11, 1, NULL, 'uploads/products/e644efe965415308a3f0742942d93d0f.webp', 'Poznej sám sebe, pak pochopíš všechno. Rozhovor sám se sebou', 1, 1, '2026-10-21 10:37:02'),
(12, 1, 'en', 'uploads/products/067f3d6ffb09b5919f7eae90eff7a8ce.webp', NULL, 1, 2, '2026-10-21 20:11:25'),
(13, 1, 'pl', 'uploads/products/b9d53953288a78d097868e775981ff92.webp', NULL, 1, 1, '2026-10-22 07:58:09'),
(14, 1, 'de', 'uploads/products/7993dbd01c42cf640da3b6be578b24db.webp', NULL, 1, 10, '2026-10-22 18:03:45'),
(15, 10, NULL, 'uploads/products/4ab53f27ea995fc47b721889106c5bd5.webp', NULL, 1, 10, '2026-10-23 11:12:15'),
(16, 11, NULL, 'uploads/products/cbcee8277df80872273c48afb4c953a5.webp', NULL, 1, 10, '2026-10-23 11:26:36'),
(17, 12, NULL, 'uploads/products/6afaa8c481613914b99f62a5edcb4569.webp', NULL, 1, 10, '2026-10-23 11:54:47'),
(18, 13, NULL, 'uploads/products/df2646220672332ed956ce363872e08c.webp', NULL, 1, 10, '2026-10-25 12:25:03'),
(19, 13, 'en', 'uploads/products/bcd6288058d47c2c1579c69d7a3e852a.webp', NULL, 1, 10, '2026-10-25 12:25:34'),
(20, 13, 'de', 'uploads/products/1aa9bcd4e6d034fb86531682242632f6.webp', NULL, 1, 10, '2026-10-25 12:25:45'),
(21, 13, 'pl', 'uploads/products/c82f5f793f0491025191a4d3266ae301.webp', NULL, 1, 10, '2026-10-25 12:25:52'),
(22, 13, 'fr', 'uploads/products/4551bf547231beb844de0f1d4ed80070.webp', NULL, 1, 10, '2026-10-25 12:26:02'),
(25, 1, 'sk', 'uploads/products/9ca90bddd67d83322636e580a75354db.webp', NULL, 1, 10, '2026-10-25 13:10:59'),
(26, 12, 'en', 'uploads/products/1b34a49c48e08321d82b06b610e9758a.webp', NULL, 1, 10, '2026-10-25 16:42:34'),
(27, 12, 'pl', 'uploads/products/8bd580245b07b6b551215a43e5c18f43.webp', NULL, 1, 10, '2026-10-26 11:07:01'),
(28, 12, 'de', 'uploads/products/b04c1f7aad5dbc8f1f4e2b61cc734f22.webp', NULL, 1, 10, '2026-10-26 11:14:23'),
(29, 12, 'sk', 'uploads/products/a5934ecf5fe58bc6b585089223260706.webp', NULL, 1, 10, '2026-10-26 11:16:30'),
(30, 12, 'fr', 'uploads/products/5ce7a8662be801203a351b963a572b32.webp', NULL, 1, 10, '2026-10-26 11:31:31');

-- --------------------------------------------------------

--
-- Struktura tabulky `product_translations`
--

CREATE TABLE `product_translations` (
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `language_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `short_description` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` mediumtext COLLATE utf8mb4_unicode_ci,
  `seo_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `seo_description` varchar(320) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Vypisuji data pro tabulku `product_translations`
--

INSERT INTO `product_translations` (`product_id`, `language_id`, `title`, `short_description`, `description`, `seo_title`, `seo_description`, `updated`) VALUES
(1, 1, 'Poznej sám sebe. Pak pochopíš všechno.', 'Klasická verze knihy o sebepoznání, životě a porozumění sobě i druhým.', 'Kniha Poznej sám sebe. Pak pochopíš všechno. Rozhovor se sebou je pozváním k tichému a pravdivému rozhovoru se sebou samým. Pomáhá čtenáři zastavit se, podívat se do vlastního nitra a lépe porozumět svým myšlenkám, emocím, vztahům i životní cestě.', 'Poznej sám sebe. Pak pochopíš všechno.', 'Poznej sám sebe. Pak pochopíš všechno.', '2026-10-22 17:40:46'),
(1, 2, 'Spoznaj sám seba. Potom pochopíš všetko.', 'Klasická verzia knihy o sebapoznaní, živote a porozumení sebe aj druhým.', 'Kniha Spoznaj sám seba. Potom pochopíš všetko. Rozhovor so sebou je pozvaním k tichému a úprimnému rozhovoru so sebou samým. Pomáha čitateľovi zastaviť sa, nahliadnuť do svojho vnútra a lepšie porozumieť svojim myšlienkam, emóciám, vzťahom aj životnej ceste.', NULL, NULL, '2026-10-22 17:40:46'),
(1, 3, 'Poznaj samego siebie. Wtedy zrozumiesz wszystko.', 'Klasyczna wersja książki o samopoznaniu, życiu oraz zrozumieniu siebie i innych.', 'Książka Poznaj samego siebie. Wtedy zrozumiesz wszystko. Rozmowa z samym sobą jest zaproszeniem do cichego i szczerego dialogu z samym sobą. Pomaga czytelnikowi zatrzymać się, zajrzeć w głąb siebie i lepiej zrozumieć własne myśli, emocje, relacje oraz swoją drogę życiową.', NULL, NULL, '2026-10-22 17:40:46'),
(1, 4, 'Know Yourself. Then You\'ll Understand Everything.', 'The classic edition of a book about self-discovery, life, and understanding yourself and others.', 'The book Know Yourself. Then You\'ll Understand Everything. A Conversation with Yourself is an invitation to a quiet and honest dialogue with oneself. It encourages readers to pause, look within, and gain a deeper understanding of their thoughts, emotions, relationships, and life journey.', NULL, NULL, '2026-10-22 17:40:46'),
(1, 5, 'Erkenne dich selbst. Dann wirst du alles verstehen.', 'Die klassische Ausgabe eines Buches über Selbsterkenntnis, das Leben und das Verständnis für sich selbst und andere.', 'Das Buch Erkenne dich selbst. Dann wirst du alles verstehen. Ein Gespräch mit dir selbst ist eine Einladung zu einem stillen und ehrlichen Dialog mit sich selbst. Es hilft den Lesern, innezuhalten, in ihr Inneres zu blicken und ihre Gedanken, Gefühle, Beziehungen und ihren Lebensweg besser zu verstehen.', NULL, NULL, '2026-10-22 17:40:46'),
(1, 6, 'Connais-toi toi-même. Alors tu comprendras tout.', 'La version classique d\'un livre sur la connaissance de soi, la vie et la compréhension de soi-même et des autres.', 'Le livre Connais-toi toi-même. Alors tu comprendras tout. Un dialogue avec soi-même est une invitation à un dialogue intérieur calme et sincère. Il aide le lecteur à faire une pause, à explorer son monde intérieur et à mieux comprendre ses pensées, ses émotions, ses relations ainsi que son parcours de vie.', NULL, NULL, '2026-10-22 17:40:46'),
(2, 1, 'Poznej sám sebe. Pak pochopíš všechno. — Komunikace se sebou', 'Samostatná verze zaměřená na vnitřní rozhovor, naslouchání sobě a opravdové otázky.', 'Další informace, ukázky a odkazy na nákup budou doplněny, jakmile budou k dispozici.', 'Poznej sám sebe. Pak pochopíš všechno. — Komunikace se sebou', 'Poznej sám sebe. Pak pochopíš všechno. — Komunikace se sebou', '2026-10-12 09:49:48'),
(2, 2, 'Spoznaj sám seba. Potom pochopíš všetko. — Komunikácia so sebo', 'Samostatná verzia zameraná na vnútorný rozhovor, načúvanie sebe samému a skutočné otázky.', 'Ďalšie informácie, ukážky a odkazy na nákup budú doplnené, hneď ako budú k dispozícii.', NULL, NULL, '2026-10-12 09:49:48'),
(2, 3, 'Poznaj samego siebie. Wtedy zrozumiesz wszystko. — Komunikacja z samym sobą', 'Samodzielna wersja skupiająca się na wewnętrznym dialogu, wsłuchiwaniu się w siebie i zadawaniu istotnych pytań.', 'Dodatkowe informacje, fragmenty książki oraz linki do zakupu zostaną dodane, gdy tylko będą dostępne.', NULL, NULL, '2026-10-12 09:49:48'),
(2, 4, 'Know Yourself. Then You\'ll Understand Everything. — Communicating with Yourself', 'A standalone edition focused on inner dialogue, listening to yourself, and asking meaningful questions.', 'Additional information, previews, and purchase links will be added as soon as they become available.', NULL, NULL, '2026-10-12 09:49:48'),
(2, 5, 'Erkenne dich selbst. Dann wirst du alles verstehen. — Kommunikation mit sich selbst', 'Eine eigenständige Ausgabe, die sich auf den inneren Dialog, das Hören auf sich selbst und auf wesentliche Fragen konzentriert.', 'Weitere Informationen, Leseproben und Kauflinks werden ergänzt, sobald sie verfügbar sind.', NULL, NULL, '2026-10-12 09:49:48'),
(2, 6, 'Connais-toi toi-même. Alors tu comprendras tout. — Communiquer avec soi-même', 'Une édition indépendante consacrée au dialogue intérieur, à l\'écoute de soi et aux questions essentielles.', 'Des informations complémentaires, des extraits et des liens d\'achat seront ajoutés dès qu\'ils seront disponibles.', NULL, NULL, '2026-10-12 09:49:48'),
(3, 1, 'Poznej sám sebe. Pak pochopíš všechno. — Rozšířená verze', 'Rozsáhlá podoba knihy, která jde ještě hlouběji do člověka, jeho života a vztahů.', 'Další informace, ukázky a odkazy na nákup budou doplněny, jakmile budou k dispozici.', 'Poznej sám sebe. Pak pochopíš všechno. — Rozšířená verze', 'Poznej sám sebe. Pak pochopíš všechno. — Rozšířená verze', '2026-10-12 09:47:41'),
(3, 2, 'Spoznaj sám seba. Potom pochopíš všetko. — Rozšírená verzia', 'Rozšírená podoba knihy, ktorá preniká ešte hlbšie do človeka, jeho života a vzťahov.', 'Ďalšie informácie, ukážky a odkazy na nákup budú doplnené, hneď ako budú k dispozícii.', NULL, NULL, '2026-10-12 09:47:41'),
(3, 3, 'Poznaj samego siebie. Wtedy zrozumiesz wszystko. — Wersja rozszerzona', 'Rozszerzona wersja książki, która jeszcze głębiej zgłębia ludzką naturę, życie i relacje międzyludzkie.', 'Dodatkowe informacje, fragmenty książki oraz linki do zakupu zostaną dodane, gdy tylko będą dostępne.', NULL, NULL, '2026-10-12 09:47:41'),
(3, 4, 'Know Yourself. Then You\'ll Understand Everything. — Expanded Edition', 'An expanded edition of the book that delves even deeper into human nature, life, and relationships.', 'Additional information, previews, and purchase links will be added as soon as they become available.', NULL, NULL, '2026-10-12 09:47:41'),
(3, 5, 'Erkenne dich selbst. Dann wirst du alles verstehen. — Erweiterte Ausgabe', 'Eine erweiterte Fassung des Buches, die noch tiefer in die menschliche Natur, das Leben und die zwischenmenschlichen Beziehungen eintaucht.', 'Weitere Informationen, Leseproben und Kauflinks werden ergänzt, sobald sie verfügbar sind.', NULL, NULL, '2026-10-12 09:47:41'),
(3, 6, 'Connais-toi toi-même. Alors tu comprendras tout. — Édition augmentée', 'Une version enrichie du livre qui explore encore plus profondément la nature humaine, la vie et les relations humaines.', 'Des informations complémentaires, des extraits et des liens d\'achat seront ajoutés dès qu\'ils seront disponibles.', NULL, NULL, '2026-10-12 09:47:41'),
(4, 1, 'Láska', 'Kniha o rodině, dvojčatech a lidských osudech, které se postupně propojují.', 'Další informace, ukázky a odkazy na nákup budou doplněny, jakmile budou k dispozici.', 'Láska', 'Láska', '2026-10-12 06:55:52'),
(4, 2, 'Láska', 'Kniha o rodine, dvojčatách a ľudských osudoch, ktoré sa postupne prepájajú.', 'Ďalšie informácie, ukážky a odkazy na nákup budú doplnené, hneď ako budú k dispozícii.', NULL, NULL, '2026-10-12 09:53:18'),
(4, 3, 'Miłość', 'Książka o rodzinie, bliźniętach i ludzkich losach, które stopniowo się splatają.', 'Dodatkowe informacje, przykłady oraz linki do zakupu zostaną uzupełnione, gdy tylko będą dostępne.', NULL, NULL, '2026-10-12 09:53:18'),
(4, 4, 'Love', 'A book about family, twins, and human lives whose paths gradually intertwine.', 'Additional information, previews, and purchase links will be added as soon as they become available.', NULL, NULL, '2026-10-12 09:53:18'),
(4, 5, 'Liebe', 'Ein Buch über Familie, Zwillinge und menschliche Schicksale, die sich nach und nach miteinander verweben.', 'Weitere Informationen, Leseproben und Kauflinks werden ergänzt, sobald sie verfügbar sind.', NULL, NULL, '2026-10-12 09:53:18'),
(4, 6, 'L\'Amour', 'Un livre sur la famille, les jumeaux et les destins humains qui s\'entrelacent peu à peu.', 'Des informations complémentaires, des extraits et des liens d\'achat seront ajoutés dès qu\'ils seront disponibles.', NULL, NULL, '2026-10-12 09:53:18'),
(5, 1, 'Život', 'Kniha o rodině, dvojčatech a lidských osudech, které se postupně propojují.', 'Další informace, ukázky a odkazy na nákup budou doplněny, jakmile budou k dispozici.', 'Život', 'Život', '2026-10-12 06:59:33'),
(5, 2, 'Život', 'Kniha o rodine, dvojčatách a ľudských osudoch, ktoré sa postupne prepájajú.', 'Ďalšie informácie, ukážky a odkazy na nákup budú doplnené, hneď ako budú k dispozícii.', NULL, NULL, '2026-10-12 09:42:57'),
(5, 3, 'Życie', 'Książka o rodzinie, bliźniętach i ludzkich losach, które stopniowo się splatają.', 'Dodatkowe informacje, przykłady oraz linki do zakupu zostaną uzupełnione, gdy tylko będą dostępne.', NULL, NULL, '2026-10-12 09:42:17'),
(5, 4, 'Life', 'A book about family, twins, and human lives whose paths gradually intertwine.', 'Additional information, previews, and purchase links will be added as soon as they become available.', NULL, NULL, '2026-10-12 09:42:57'),
(5, 5, 'Leben', 'Ein Buch über Familie, Zwillinge und menschliche Schicksale, die sich nach und nach miteinander verweben.', 'Weitere Informationen, Leseproben und Kauflinks werden ergänzt, sobald sie verfügbar sind.', NULL, NULL, '2026-10-12 09:42:57'),
(5, 6, 'La Vie', 'Un livre sur la famille, les jumeaux et les destins humains qui s\'entrelacent peu à peu.', 'Des informations complémentaires, des extraits et des liens d\'achat seront ajoutés dès qu\'ils seront disponibles.', NULL, NULL, '2026-10-12 09:42:57'),
(6, 1, 'Láska — první díl: Kóma', 'První část trilogie o lásce, v níž se skutečnost a vnitřní svět začnou nečekaně prolínat.', 'První část trilogie o lásce, v níž se skutečnost a vnitřní svět začnou nečekaně prolínat.', 'Láska — první díl: Kóma', 'Láska — první díl: Kóma', '2026-10-12 07:02:10'),
(6, 2, 'Láska — prvý diel: Kóma', 'Prvá časť trilógie o láske, v ktorej sa skutočnosť a vnútorný svet začnú nečakane prelínať.', NULL, NULL, NULL, '2026-10-12 09:40:15'),
(6, 3, 'Miłość — część pierwsza: Śpiączka', 'Pierwsza część trylogii o miłości, w której rzeczywistość i świat wewnętrzny zaczynają się niespodziewanie przenikać.', 'Dodatkowe informacje, przykłady oraz linki do zakupu zostaną uzupełnione, gdy tylko będą dostępne.', NULL, NULL, '2026-10-12 09:40:15'),
(6, 4, 'Love — Part One: Coma', 'The first part of a trilogy about love, in which reality and the inner world begin to intertwine in unexpected ways.', NULL, NULL, NULL, '2026-10-12 09:40:15'),
(6, 5, 'Liebe — Teil Eins: Koma', 'Der erste Teil einer Trilogie über die Liebe, in der Realität und innere Welt auf unerwartete Weise miteinander verschmelzen.', NULL, NULL, NULL, '2026-10-12 09:40:15'),
(6, 6, 'L\'Amour — Première partie : Coma', 'La première partie d\'une trilogie sur l\'amour, dans laquelle la réalité et le monde intérieur commencent à s\'entremêler de manière inattendue.', NULL, NULL, NULL, '2026-10-12 09:40:15'),
(7, 1, 'Civilióza', 'Pohled na společnost, její pravidla a cenu, kterou za ně člověk platí.', NULL, NULL, NULL, '2026-10-12 07:08:55'),
(7, 2, '', 'Pohľad na spoločnosť, jej pravidlá a cenu, ktorú za ne človek platí.', NULL, NULL, NULL, '2026-10-12 09:38:08'),
(7, 3, '', 'Spojrzenie na społeczeństwo, jego zasady i cenę, jaką człowiek za nie płaci.', 'Dodatkowe informacje, przykłady oraz linki do zakupu zostaną uzupełnione, gdy tylko będą dostępne.', NULL, NULL, '2026-10-12 09:38:08'),
(7, 4, '', 'A look at society, its rules, and the price individuals pay for them.', NULL, NULL, NULL, '2026-10-12 09:38:08'),
(7, 5, '', 'Ein Blick auf die Gesellschaft, ihre Regeln und den Preis, den der Mensch dafür zahlt.', NULL, NULL, NULL, '2026-10-12 09:38:08'),
(7, 6, '', 'Un regard sur la société, ses règles et le prix que l\'on paie pour les respecter.', NULL, NULL, NULL, '2026-10-12 09:38:08'),
(8, 1, 'Stupně vědomí', 'Kniha věnovaná lidskému vnímání, vnitřní proměně a porozumění.', NULL, 'Civilióza', 'Civilióza', '2026-10-12 09:36:13'),
(8, 2, 'Stupne vedomia', 'Kniha venovaná ľudskému vnímaniu, vnútornej premene a porozumeniu.', NULL, NULL, NULL, '2026-10-12 09:37:04'),
(8, 3, 'Poziomy świadomości', 'Książka poświęcona ludzkiemu postrzeganiu, wewnętrznej przemianie i zrozumieniu.', 'Dodatkowe informacje, przykłady oraz linki do zakupu zostaną uzupełnione, gdy tylko będą dostępne.', NULL, NULL, '2026-10-12 09:37:04'),
(8, 4, 'Levels of Consciousness', 'A book dedicated to human perception, inner transformation, and understanding.', NULL, NULL, NULL, '2026-10-12 09:37:04'),
(8, 5, 'Bewusstseinsstufen', 'Ein Buch über die menschliche Wahrnehmung, innere Wandlung und Verständnis.', NULL, NULL, NULL, '2026-10-12 09:37:04'),
(8, 6, 'Les niveaux de conscience', 'Un livre consacré à la perception humaine, à la transformation intérieure et à la compréhension.', NULL, NULL, NULL, '2026-10-12 09:37:04'),
(9, 1, 'Mezi psychopaty', 'Román o partnerských vztazích, manipulaci, lidské odvaze a psychopatech mezi námi.', 'Román o partnerských vztazích, manipulaci, lidské odvaze a psychopatech mezi námi.', 'Mezi psychopaty', 'Mezi psychopaty', '2026-10-12 07:11:26'),
(9, 2, 'Medzi psychopatmi', 'Román o partnerských vzťahoch, manipulácii, ľudskej odvahe a psychopatoch medzi nami.', NULL, NULL, NULL, '2026-10-12 09:33:15'),
(9, 3, 'Wśród psychopatów', 'Powieść o związkach partnerskich, manipulacji, ludzkiej odwadze i psychopatach wśród nas.', 'Dodatkowe informacje, przykłady oraz linki do zakupu zostaną uzupełnione, gdy tylko będą dostępne.', NULL, NULL, '2026-10-12 09:33:15'),
(9, 4, 'Among Psychopaths', 'A novel about romantic relationships, manipulation, human courage, and the psychopaths among us.', NULL, NULL, NULL, '2026-10-12 09:33:15'),
(9, 5, 'Unter Psychopathen', 'Ein Roman über Partnerschaften, Manipulation, menschlichen Mut und Psychopathen unter uns.', NULL, NULL, NULL, '2026-10-12 09:33:15'),
(9, 6, 'Parmi les psychopathes', 'Un roman sur les relations amoureuses, la manipulation, le courage humain et les psychopathes parmi nous.', NULL, NULL, NULL, '2026-10-12 09:33:15'),
(10, 1, 'Dva životy jedné lásky III - Skutečnost', 'Třetí část trilogie', 'Marek se po probuzení učí žít s tím, co ztratil. Nina se brání lidem, kteří chtějí získat její firmu. Stará listina s číslem 771 spojí jejich soukromý život s případem, jehož dosah překročí hranice. Když násilí přeruší večer, na který oba čekali, musí se rozhodovat v několika vteřinách. Až nebezpečí pomine, zůstane před nimi další cesta: naučit se znovu důvěřovat, vrátit se k práci a vytvořit domov, ve kterém budou mít místo oba.', 'Dva životy jedné lásky III Skutečnost', 'Dva životy jedné lásky III Skutečnost', '2026-10-23 11:42:08'),
(10, 2, 'Dva životy jednej lásky III – Skutočnosť', 'Tretia časť trilógie', 'Marek sa po prebudení učí žiť s tým, čo stratil. Nina sa bráni ľuďom, ktorí chcú získať jej firmu. Stará listina s číslom 771 prepojí ich súkromný život s prípadom, ktorého dosah prekročí hranice. Keď násilie preruší večer, na ktorý obaja čakali, musia sa rozhodovať v priebehu niekoľkých sekúnd. Keď nebezpečenstvo pominie, čaká ich ďalšia cesta: naučiť sa znovu dôverovať, vrátiť sa k práci a vytvoriť domov, v ktorom budú mať miesto obaja.', 'Dva životy jednej lásky III – Skutočnosť', 'Dva životy jednej lásky III – Skutočnosť', '2026-10-23 11:42:08'),
(10, 3, 'Dwa życia jednej miłości III – Rzeczywistość', 'Trzecia część trylogii', 'Po przebudzeniu Marek uczy się żyć z tym, co stracił. Nina broni się przed ludźmi, którzy chcą przejąć jej firmę. Stary dokument oznaczony numerem 771 łączy ich życie prywatne ze sprawą, której zasięg przekroczy granice państw. Kiedy przemoc przerywa wieczór, na który oboje czekali, muszą podejmować decyzje w ciągu kilku sekund. Gdy niebezpieczeństwo minie, czeka ich kolejna droga: nauczyć się na nowo ufać, wrócić do pracy i stworzyć dom, w którym oboje znajdą swoje miejsce.', 'Dwa życia jednej miłości III – Rzeczywistość', 'Dwa życia jednej miłości III – Rzeczywistość', '2026-10-23 11:08:34'),
(10, 4, 'Two Lives of One Love III – Reality', 'The Third Part of the Trilogy', 'After waking up, Marek learns to live with what he has lost. Nina fights back against those who want to take over her company. An old document marked with the number 771 connects their private lives to a case whose implications reach beyond national borders. When violence interrupts an evening they have both been looking forward to, they must make decisions in a matter of seconds. Once the danger has passed, another journey lies ahead: learning to trust again, returning to work, and building a home where they both belong.', 'Two Lives of One Love III – Reality', 'Two Lives of One Love III – Reality', '2026-10-23 11:08:34'),
(10, 5, 'Zwei Leben einer Liebe III – Wirklichkeit', 'Der dritte Teil der Trilogie', 'Nach dem Erwachen lernt Marek, mit dem zu leben, was er verloren hat. Nina wehrt sich gegen Menschen, die ihre Firma übernehmen wollen. Ein altes Dokument mit der Nummer 771 verbindet ihr Privatleben mit einem Fall, dessen Auswirkungen über die Landesgrenzen hinausreichen. Als Gewalt einen Abend unterbricht, auf den sich beide gefreut haben, müssen sie innerhalb weniger Sekunden Entscheidungen treffen. Sobald die Gefahr vorüber ist, liegt ein weiterer Weg vor ihnen: wieder Vertrauen zu lernen, an die Arbeit zurückzukehren und ein Zuhause zu schaffen, in dem beide ihren Platz finden.', 'Zwei Leben einer Liebe III – Wirklichkeit', 'Zwei Leben einer Liebe III – Wirklichkeit', '2026-10-23 11:08:34'),
(10, 6, 'Deux vies d\'un même amour III – La réalité', 'La troisième partie de la trilogie', 'Après son réveil, Marek apprend à vivre avec ce qu\'il a perdu. Nina se défend contre ceux qui cherchent à prendre le contrôle de son entreprise. Un ancien document portant le numéro 771 relie leur vie privée à une affaire dont les répercussions dépassent les frontières nationales. Lorsque la violence interrompt une soirée qu\'ils attendaient tous les deux avec impatience, ils doivent prendre des décisions en quelques secondes. Une fois le danger écarté, un autre chemin les attend : réapprendre à faire confiance, reprendre le travail et construire un foyer où chacun trouvera sa place.', 'Deux vies d\'un même amour III – La réalité', 'Deux vies d\'un même amour III – La réalité', '2026-10-23 11:08:34'),
(11, 1, 'Dva životy jedné lásky II - Probuzení', 'Druhý díl trilogie', 'Čtyři měsíce v kómatu. Celý život ve vzpomínkách. Marek Vrána se probouzí přesvědčený, že má ženu a dceru. Nina však jejich společný život nezná. To, co je pro něj nejhlubší pravdou, je pro ni záhadou. Znovu se k sobě přibližují a učí se důvěřovat tomu, co mezi nimi vzniká. Do jejich blízkosti ale vstupují cizí zájmy, tajemství a hrozba, kterou už nelze přehlížet.', 'Dva životy jedné lásky II Probuzení', 'Dva životy jedné lásky II Probuzení', '2026-10-23 11:40:57'),
(11, 2, 'Dva životy jednej lásky II – Prebudenie', 'Druhý diel trilógie', 'Štyri mesiace v kóme. Celý život v spomienkach. Marek Vrána sa prebúdza presvedčený, že má manželku a dcéru. Nina však ich spoločný život nepozná. To, čo je pre neho najhlbšou pravdou, je pre ňu záhadou. Znovu sa k sebe približujú a učia sa dôverovať tomu, čo medzi nimi vzniká. Do ich blízkosti však vstupujú cudzie záujmy, tajomstvá a hrozba, ktorú už nemožno prehliadať.', 'Dva životy jednej lásky II – Prebudenie', 'Dva životy jednej lásky II – Prebudenie', '2026-10-23 11:26:17'),
(11, 3, 'Dwa życia jednej miłości II – Przebudzenie', 'Druga część trylogii', 'Cztery miesiące w śpiączce. Całe życie we wspomnieniach. Marek Vrána budzi się przekonany, że ma żonę i córkę. Nina jednak nie zna ich wspólnego życia. To, co dla niego jest najgłębszą prawdą, dla niej pozostaje zagadką. Na nowo zbliżają się do siebie i uczą się ufać temu, co rodzi się między nimi. Jednak w ich życie wkraczają cudze interesy, tajemnice i zagrożenie, którego nie można już ignorować.', 'Dwa życia jednej miłości II – Przebudzenie', 'Dwa życia jednej miłości II – Przebudzenie', '2026-10-23 11:26:17'),
(11, 4, 'Two Lives of One Love II – Awakening', 'The Second Part of the Trilogy', 'Four months in a coma. An entire lifetime in his memories. Marek Vrána awakens convinced that he has a wife and daughter. But Nina has no memory of the life they supposedly shared. What he knows to be his deepest truth remains a mystery to her. Slowly, they grow close again, learning to trust the bond developing between them. Yet outside interests, secrets, and a threat that can no longer be ignored begin to intrude upon their lives.', 'Two Lives of One Love II – Awakening', 'Two Lives of One Love II – Awakening', '2026-10-23 11:26:17'),
(11, 5, 'Zwei Leben einer Liebe II – Erwachen', 'Der zweite Teil der Trilogie', 'Vier Monate im Koma. Ein ganzes Leben in Erinnerungen. Marek Vrána erwacht in der festen Überzeugung, eine Frau und eine Tochter zu haben. Doch Nina kennt ihr gemeinsames Leben nicht. Was für ihn die tiefste Wahrheit ist, bleibt für sie ein Rätsel. Langsam kommen sie sich wieder näher und lernen, dem zu vertrauen, was zwischen ihnen entsteht. Doch fremde Interessen, Geheimnisse und eine Bedrohung, die sich nicht länger ignorieren lässt, dringen in ihr Leben ein.', 'Zwei Leben einer Liebe II – Erwachen', 'Zwei Leben einer Liebe II – Erwachen', '2026-10-23 11:26:17'),
(11, 6, 'Deux vies d\'un même amour II – Le Réveil', 'La deuxième partie de la trilogie', 'Quatre mois dans le coma. Toute une vie dans ses souvenirs. Marek Vrána se réveille convaincu d\'avoir une femme et une fille. Pourtant, Nina ne connaît rien de leur vie commune. Ce qui représente pour lui la vérité la plus profonde reste pour elle une énigme. Peu à peu, ils se rapprochent à nouveau et apprennent à faire confiance à ce qui naît entre eux. Mais des intérêts extérieurs, des secrets et une menace qu\'il n\'est plus possible d\'ignorer viennent troubler leur intimité.', 'Deux vies d\'un même amour II – Le Réveil', 'Deux vies d\'un même amour II – Le Réveil', '2026-10-23 11:26:17'),
(12, 1, 'Dva životy jedné lásky I - Kóma', 'První díl trilogie', 'Marek Vrána a Nina Valenová se neznají. Jejich životy spojí jediný okamžik a starý případ označený číslem 771. Když se Marek ocitne v kómatu, čas se pro něj začne odvíjet jinak. Zatímco Nina zůstává u jeho lůžka, on prožívá život, jehož hranice nelze snadno oddělit od skutečnosti. První část trilogie o paměti, blízkosti a životě, který může být skutečný i tehdy, když jej člověk prožívá jen uvnitř sebe.', 'Dva životy jedné lásky I - Kóma', 'Dva životy jedné lásky I - Kóma', '2026-10-23 11:50:54'),
(12, 2, 'Dva životy jednej lásky I – Kóma', 'Prvý diel trilógie', 'Marek Vrána a Nina Valenová se neznají. Jejich životy spojí jediný okamžik a starý případ označený číslem 771. Když se Marek ocitne v kómatu, čas se pro něj začne odvíjet jinak. Zatímco Nina zůstává u jeho lůžka, on prožívá život, jehož hranice nelze snadno oddělit od skutečnosti. První část trilogie o paměti, blízkosti a životě, který může být skutečný i tehdy, když jej člověk prožívá jen uvnitř sebe.', 'Dva životy jednej lásky I – Kóma', 'Dva životy jednej lásky I – Kóma', '2026-10-23 11:50:54'),
(12, 3, 'Dwa życia jednej miłości I – Śpiączka', 'Pierwsza część trylogii', 'Marek Vrána i Nina Valenová nie znają się. Ich losy splatają się za sprawą jednej chwili i dawnej sprawy oznaczonej numerem 771. Kiedy Marek zapada w śpiączkę, czas zaczyna płynąć dla niego inaczej. Podczas gdy Nina czuwa przy jego łóżku, on przeżywa życie, którego granice trudno oddzielić od rzeczywistości. Pierwsza część trylogii o pamięci, bliskości i życiu, które może być prawdziwe nawet wtedy, gdy człowiek przeżywa je wyłącznie we własnym wnętrzu.', 'Dwa życia jednej miłości I – Śpiączka', 'Dwa życia jednej miłości I – Śpiączka', '2026-10-23 11:50:54'),
(12, 4, 'Two Lives of One Love I – Coma', 'The First Part of the Trilogy', 'Marek Vrána and Nina Valenová are strangers. Their lives become intertwined through a single moment and an old case marked with the number 771. When Marek falls into a coma, time begins to unfold differently for him. While Nina remains at his bedside, he experiences an entire life whose boundaries are difficult to distinguish from reality. The first part of a trilogy about memory, human connection, and a life that can be real even when it exists only within oneself.', 'Two Lives of One Love I – Coma', 'Two Lives of One Love I – Coma', '2026-10-23 11:50:54'),
(12, 5, 'Zwei Leben einer Liebe I – Koma', 'Der erste Teil der Trilogie', 'Marek Vrána und Nina Valenová kennen sich nicht. Ein einziger Augenblick und ein alter Fall mit der Nummer 771 verbinden ihre Schicksale. Als Marek ins Koma fällt, beginnt die Zeit für ihn anders zu verlaufen. Während Nina an seinem Krankenbett bleibt, erlebt er ein Leben, dessen Grenzen sich nur schwer von der Wirklichkeit unterscheiden lassen. Der erste Teil einer Trilogie über Erinnerung, Nähe und ein Leben, das auch dann wirklich sein kann, wenn man es nur in seinem Inneren erlebt.', 'Zwei Leben einer Liebe I – Koma', 'Zwei Leben einer Liebe I – Koma', '2026-10-23 11:50:54'),
(12, 6, 'Deux vies d\'un même amour I – Le Coma', 'La première partie de la trilogie', 'Marek Vrána et Nina Valenová ne se connaissent pas. Leurs destins se croisent à la suite d\'un seul instant et d\'une ancienne affaire portant le numéro 771. Lorsque Marek tombe dans le coma, le temps commence à s\'écouler différemment pour lui. Tandis que Nina reste à son chevet, il vit une existence dont les frontières avec la réalité deviennent difficiles à distinguer. Le premier volet d\'une trilogie sur la mémoire, la proximité humaine et une vie qui peut être réelle même lorsqu\'on ne la vit qu\'au plus profond de soi.', 'Deux vies d\'un même amour I – Le Coma', 'Deux vies d\'un même amour I – Le Coma', '2026-10-23 11:50:54'),
(13, 1, 'Když domov bolí', 'Domov má znamenat bezpečí. Eva se v něm naučila bát.', 'Vybudovala dům, vychovala děti a věřila muži, se kterým chtěla zestárnout. Teď se bojí chvíle, kdy v zámku zarachotí klíče. Navenek zvládá práci i podnikání. Doma přestává věřit vlastnímu úsudku. Steven do jejího života přinese blízkost a klid. Jenže i tenhle mohutný muž zná strach, o kterém se těžko mluví. \r\nA potom zmizí. Telefon mlčí. Když se konečně ozve, Eva slyší jeho hlas. Nedokáže jí však říct, kde je. Když domov bolí je román o tom, co může zůstávat skryté za zavřenými dveřmi. O lásce, manipulaci a hledání cesty zpátky k sobě. Vychází z autorčiných životních zkušeností a skutečných událostí.', 'Když domov bolí', 'Když domov bolí od Mgr. Pavly Kovalovské. Román o lásce, manipulaci a ztracené důvěře. Otevřete si zdarma ukázku z českého vydání.', '2026-10-25 12:11:29'),
(13, 2, 'Keď domov bolí', 'Domov má znamenať bezpečie. Eva sa v ňom naučila báť.', 'Postavila dom, vychovala deti a verila mužovi, s ktorým chcela zostarnúť. Teraz sa bojí chvíle, keď v zámke zaštrngocú kľúče. Navonok zvláda prácu aj podnikanie. Doma prestáva veriť vlastnému úsudku. Steven jej do života prinesie blízkosť a pokoj. Lenže aj tento mohutný muž pozná strach, o ktorom sa ťažko hovorí. A potom zmizne. Telefón mlčí. Keď sa konečne ozve, Eva počuje jeho hlas. Nedokáže jej však povedať, kde je. Keď domov bolí je román o tom, čo môže zostať skryté za zatvorenými dverami. O láske, manipulácii a hľadaní cesty späť k sebe. Vychádza z autorkiných životných skúseností a skutočných udalostí.', 'Keď domov bolí', 'Keď domov bolí od Mgr. Pavly Kovalovskej. Román o láske, manipulácii a stratenej dôvere. Prečítajte si zadarmo ukážku z českého vydania.', '2026-10-25 12:11:29'),
(13, 3, 'Kiedy dom boli', 'Dom powinien oznaczać bezpieczeństwo. Eva nauczyła się w nim bać.', 'Zbudowała dom, wychowała dzieci i ufała mężczyźnie, z którym chciała się zestarzeć. Teraz boi się chwili, gdy w zamku zazgrzytają klucze. Na zewnątrz radzi sobie z pracą i prowadzeniem firmy. W domu przestaje ufać własnemu osądowi. Steven wnosi do jej życia bliskość i spokój. Jednak nawet ten potężnie zbudowany mężczyzna zna strach, o którym trudno mówić. A potem znika. Telefon milczy. Kiedy wreszcie się odzywa, Eva słyszy jego głos. Nie potrafi jej jednak powiedzieć, gdzie jest. Kiedy dom boli to powieść o tym, co może pozostawać ukryte za zamkniętymi drzwiami. O miłości, manipulacji i poszukiwaniu drogi powrotnej do samej siebie. Powstała na podstawie życiowych doświadczeń autorki i prawdziwych wydarzeń.', 'Kiedy dom boli', 'Kiedy dom boli autorstwa Mgr Pavly Kovalovskiej. Powieść o miłości, manipulacji i utraconym zaufaniu. Przeczytaj bezpłatny fragment czeskiego wydania.', '2026-10-25 12:11:29'),
(13, 4, 'When Home Hurts', 'Home is supposed to be a place of safety. Eva learned to be afraid there.', 'She built a home, raised her children, and trusted the man she wanted to grow old with. Now she dreads the moment she hears keys rattling in the lock. To the outside world, she manages her job and business with confidence. At home, she is losing faith in her own judgment. Steven brings warmth, closeness, and peace into her life. Yet even this powerfully built man knows a fear that is difficult to put into words. And then he disappears. His phone falls silent. When he finally calls, Eva hears his voice. But he cannot tell her where he is. When Home Hurts is a novel about what can remain hidden behind closed doors. About love, manipulation, and finding the way back to oneself. Inspired by the author\'s personal experiences and real events.', 'When Home Hurts', 'When Home Hurts by Mgr. Pavla Kovalovská. A novel about love, manipulation, and lost trust. Read a free excerpt from the Czech edition.', '2026-10-25 12:11:29'),
(13, 5, 'Wenn das Zuhause schmerzt', 'Ein Zuhause sollte Geborgenheit bedeuten. Eva hat dort gelernt, Angst zu haben.', 'Sie hat ein Haus gebaut, Kinder großgezogen und dem Mann vertraut, mit dem sie alt werden wollte. Jetzt fürchtet sie den Moment, in dem die Schlüssel im Schloss klirren. Nach außen hin meistert sie ihre Arbeit und ihre Selbstständigkeit. Zu Hause verliert sie zunehmend das Vertrauen in ihr eigenes Urteilsvermögen. Steven bringt Nähe und Ruhe in ihr Leben. Doch auch dieser kräftig gebaute Mann kennt eine Angst, über die nur schwer zu sprechen ist. Und dann verschwindet er. Das Telefon bleibt stumm. Als er sich endlich meldet, hört Eva seine Stimme. Doch er kann ihr nicht sagen, wo er ist. Wenn das Zuhause schmerzt ist ein Roman darüber, was hinter verschlossenen Türen verborgen bleiben kann. Über Liebe, Manipulation und die Suche nach dem Weg zurück zu sich selbst. Er basiert auf den Lebenserfahrungen der Autorin und wahren Begebenheiten.', 'Wenn das Zuhause schmerzt', 'Wenn das Zuhause schmerzt von Mgr. Pavla Kovalovská. Ein Roman über Liebe, Manipulation und verlorenes Vertrauen. Lesen Sie kostenlos eine Leseprobe aus der tschechischen Ausgabe.', '2026-10-25 12:11:29'),
(13, 6, 'Quand le foyer fait mal', 'Un foyer devrait être un lieu de sécurité. Eva y a appris à avoir peur.', 'Elle a construit une maison, élevé ses enfants et fait confiance à l\'homme avec lequel elle voulait vieillir. Aujourd\'hui, elle redoute le moment où les clés tintent dans la serrure. Aux yeux du monde, elle parvient à gérer son travail et son entreprise. Mais chez elle, elle commence à douter de son propre jugement. Steven apporte de la tendresse, de la proximité et de la sérénité dans sa vie. Pourtant, même cet homme à la carrure imposante connaît une peur dont il est difficile de parler. Puis il disparaît. Le téléphone reste silencieux. Lorsqu\'il donne enfin de ses nouvelles, Eva entend sa voix. Mais il est incapable de lui dire où il se trouve. Quand le foyer fait mal est un roman sur ce qui peut rester caché derrière des portes closes. Sur l\'amour, la manipulation et la quête du chemin qui mène à soi-même. Il s\'inspire des expériences personnelles de l\'auteure et de faits réels.', 'Quand le foyer fait mal', 'Quand le foyer fait mal de Mgr. Pavla Kovalovská. Un roman sur l\'amour, la manipulation et la confiance perdue. Découvrez gratuitement un extrait de l\'édition tchèque.', '2026-10-25 12:11:29');

-- --------------------------------------------------------

--
-- Struktura tabulky `product_variants`
--

CREATE TABLE `product_variants` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `kind` enum('print','ebook','audio') COLLATE utf8mb4_unicode_ci NOT NULL,
  `language_code` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sku` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `isbn` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ean` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` decimal(12,2) NOT NULL,
  `sale_price` decimal(12,2) DEFAULT NULL,
  `sale_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `sale_starts_at` datetime DEFAULT NULL,
  `sale_ends_at` datetime DEFAULT NULL,
  `currency` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CZK',
  `vat_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `stock_quantity` int(10) UNSIGNED DEFAULT NULL,
  `minimum_stock` int(10) UNSIGNED DEFAULT NULL,
  `weight_grams` int(10) UNSIGNED DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Vypisuji data pro tabulku `product_variants`
--

INSERT INTO `product_variants` (`id`, `product_id`, `kind`, `language_code`, `sku`, `isbn`, `ean`, `price`, `sale_price`, `sale_enabled`, `sale_starts_at`, `sale_ends_at`, `currency`, `vat_rate`, `stock_quantity`, `minimum_stock`, `weight_grams`, `active`, `created`, `updated`) VALUES
(1, 1, 'print', NULL, NULL, NULL, NULL, 300.00, NULL, 0, NULL, NULL, 'CZK', 0.00, 10, 1, 400, 0, '2026-10-08 09:26:23', '2026-10-21 10:12:46'),
(2, 1, 'ebook', NULL, NULL, NULL, NULL, 199.00, 159.00, 1, '2026-12-14 00:00:00', '2026-12-31 23:59:00', 'CZK', 0.00, NULL, NULL, NULL, 0, '2026-10-08 09:26:23', '2026-10-23 13:22:09'),
(3, 1, 'audio', NULL, NULL, NULL, NULL, 300.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 0, '2026-10-08 09:26:23', '2026-10-21 10:12:46'),
(7, 2, 'ebook', NULL, NULL, NULL, NULL, 300.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 0, '2026-10-09 07:20:24', '2026-10-21 12:24:26'),
(8, 2, 'audio', NULL, NULL, NULL, NULL, 300.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 0, '2026-10-09 07:20:24', '2026-10-21 12:24:26'),
(13, 3, 'print', NULL, NULL, NULL, NULL, 300.00, NULL, 0, NULL, NULL, 'CZK', 0.00, 30, NULL, NULL, 1, '2026-10-09 10:21:09', '2026-10-09 10:21:09'),
(14, 3, 'ebook', NULL, NULL, NULL, NULL, 300.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 0, '2026-10-09 10:21:09', '2026-10-21 12:24:26'),
(15, 3, 'audio', NULL, NULL, NULL, NULL, 300.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 0, '2026-10-09 10:21:09', '2026-10-21 12:24:26'),
(24, 4, 'audio', NULL, NULL, NULL, NULL, 300.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 0, '2026-10-12 06:55:52', '2026-10-21 12:24:26'),
(27, 5, 'ebook', NULL, NULL, NULL, NULL, 300.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 0, '2026-10-12 06:59:33', '2026-10-21 12:24:26'),
(28, 6, 'print', NULL, NULL, NULL, NULL, 300.00, NULL, 0, NULL, NULL, 'CZK', 0.00, 10, NULL, NULL, 1, '2026-10-12 07:02:10', '2026-10-12 07:02:10'),
(29, 6, 'ebook', NULL, NULL, NULL, NULL, 300.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 0, '2026-10-12 07:02:10', '2026-10-21 12:24:26'),
(30, 6, 'audio', NULL, NULL, NULL, NULL, 300.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 0, '2026-10-12 07:02:10', '2026-10-21 12:24:26'),
(35, 5, 'print', NULL, NULL, NULL, NULL, 300.00, NULL, 0, NULL, NULL, 'CZK', 0.00, 10, NULL, NULL, 1, '2026-10-12 07:04:17', '2026-10-12 07:04:17'),
(37, 5, 'audio', NULL, NULL, NULL, NULL, 300.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 0, '2026-10-12 07:04:17', '2026-10-21 12:24:26'),
(41, 4, 'print', NULL, NULL, NULL, NULL, 300.00, NULL, 0, NULL, NULL, 'CZK', 0.00, 20, NULL, NULL, 1, '2026-10-12 07:04:51', '2026-10-20 09:34:14'),
(42, 4, 'ebook', NULL, NULL, NULL, NULL, 300.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 0, '2026-10-12 07:04:51', '2026-10-21 12:24:26'),
(67, 7, 'print', NULL, NULL, NULL, NULL, 300.00, NULL, 0, NULL, NULL, 'CZK', 0.00, 10, NULL, NULL, 1, '2026-10-12 07:08:55', '2026-10-12 07:08:55'),
(68, 7, 'ebook', NULL, NULL, NULL, NULL, 300.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 0, '2026-10-12 07:08:55', '2026-10-21 12:24:26'),
(69, 7, 'audio', NULL, NULL, NULL, NULL, 300.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 0, '2026-10-12 07:08:55', '2026-10-21 12:24:26'),
(70, 8, 'print', NULL, NULL, NULL, NULL, 300.00, NULL, 0, NULL, NULL, 'CZK', 0.00, 10, NULL, NULL, 1, '2026-10-12 07:09:51', '2026-10-12 07:09:51'),
(71, 8, 'ebook', NULL, NULL, NULL, NULL, 300.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 0, '2026-10-12 07:09:51', '2026-10-21 12:24:26'),
(72, 8, 'audio', NULL, NULL, NULL, NULL, 300.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 0, '2026-10-12 07:09:51', '2026-10-21 12:24:26'),
(73, 9, 'print', NULL, NULL, NULL, NULL, 295.00, NULL, 0, NULL, NULL, 'CZK', 0.00, 10, NULL, NULL, 1, '2026-10-12 07:11:26', '2026-10-12 07:11:26'),
(74, 9, 'ebook', NULL, NULL, NULL, NULL, 295.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 0, '2026-10-12 07:11:26', '2026-10-16 18:02:33'),
(77, 9, 'audio', NULL, NULL, NULL, NULL, 295.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 0, '2026-10-12 07:13:58', '2026-10-16 18:02:33'),
(78, 2, 'print', NULL, NULL, NULL, NULL, 300.00, NULL, 0, NULL, NULL, 'CZK', 0.00, 10, NULL, NULL, 1, '2026-10-12 07:14:11', '2026-10-12 07:14:11'),
(179, 1, 'print', 'cs', NULL, NULL, NULL, 199.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 0, '2026-10-21 11:26:42', '2026-10-21 11:32:21'),
(180, 1, 'print', 'pl', NULL, NULL, NULL, 199.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 0, '2026-10-21 11:26:42', '2026-10-21 11:32:21'),
(181, 1, 'ebook', 'pl', NULL, NULL, NULL, 270.00, 199.00, 1, '2026-10-21 20:47:00', '2026-12-31 23:53:00', 'CZK', 0.00, NULL, NULL, NULL, 1, '2026-10-21 11:32:21', '2026-10-23 13:22:09'),
(182, 1, 'ebook', 'cs', NULL, NULL, NULL, 270.00, 199.00, 1, '2026-10-21 20:47:00', '2026-12-31 23:53:00', 'CZK', 0.00, NULL, NULL, NULL, 1, '2026-10-21 11:33:05', '2026-10-23 13:22:09'),
(183, 1, 'ebook', 'en', NULL, NULL, NULL, 270.00, 199.00, 1, '2026-10-21 20:47:00', '2026-12-31 23:53:00', 'CZK', 0.00, NULL, NULL, NULL, 1, '2026-10-21 20:14:47', '2026-10-23 13:22:09'),
(184, 1, 'ebook', 'es', NULL, NULL, NULL, 270.00, 199.00, 1, '2026-10-21 20:47:00', '2026-12-31 23:53:00', 'CZK', 0.00, NULL, NULL, NULL, 1, '2026-10-22 08:18:47', '2026-10-23 13:22:09'),
(185, 1, 'ebook', 'ar', NULL, NULL, NULL, 270.00, 199.00, 1, '2026-10-21 20:47:00', '2026-12-31 23:53:00', 'CZK', 0.00, NULL, NULL, NULL, 1, '2026-10-22 08:25:27', '2026-10-23 13:22:09'),
(186, 1, 'ebook', 'it', NULL, NULL, NULL, 270.00, 199.00, 1, '2026-10-21 20:47:00', '2026-12-31 23:53:00', 'CZK', 0.00, NULL, NULL, NULL, 1, '2026-10-22 09:06:51', '2026-10-23 13:22:09'),
(187, 1, 'ebook', 'de', NULL, NULL, NULL, 270.00, 199.00, 1, '2026-10-21 20:47:00', '2026-12-31 23:53:00', 'CZK', 0.00, NULL, NULL, NULL, 1, '2026-10-22 09:09:06', '2026-10-23 13:22:09'),
(188, 1, 'ebook', 'pt', NULL, NULL, NULL, 270.00, 199.00, 1, '2026-10-21 20:47:00', '2026-12-31 23:53:00', 'CZK', 0.00, NULL, NULL, NULL, 1, '2026-10-22 09:20:27', '2026-10-23 13:22:09'),
(189, 1, 'ebook', 'ru', NULL, NULL, NULL, 270.00, 199.00, 1, '2026-10-21 20:47:00', '2026-12-31 23:53:00', 'CZK', 0.00, NULL, NULL, NULL, 1, '2026-10-22 09:22:47', '2026-10-23 13:22:09'),
(190, 1, 'ebook', 'sk', NULL, NULL, NULL, 270.00, 199.00, 1, '2026-10-21 20:47:00', '2026-12-31 23:53:00', 'CZK', 0.00, NULL, NULL, NULL, 1, '2026-10-22 10:48:24', '2026-10-23 13:22:09'),
(191, 1, 'ebook', 'fr', NULL, NULL, NULL, 270.00, 199.00, 1, '2026-10-21 20:47:00', '2026-12-31 23:53:00', 'CZK', 0.00, NULL, NULL, NULL, 0, '2026-10-22 17:21:08', '2026-10-23 13:22:09'),
(192, 1, 'ebook', 'uk', NULL, NULL, NULL, 270.00, 199.00, 1, '2026-10-21 20:47:00', '2026-12-31 23:53:00', 'CZK', 0.00, NULL, NULL, NULL, 1, '2026-10-22 17:36:32', '2026-10-23 13:22:09'),
(193, 10, 'ebook', 'cs', NULL, NULL, NULL, 329.00, 199.00, 1, '2026-10-22 23:59:00', '2026-12-31 23:59:00', 'CZK', 0.00, NULL, NULL, NULL, 1, '2026-10-23 11:09:56', '2026-10-23 13:32:43'),
(194, 11, 'ebook', 'cs', NULL, NULL, NULL, 329.00, 190.00, 1, '2026-10-23 13:00:00', '2026-12-31 23:59:00', 'CZK', 0.00, NULL, NULL, NULL, 1, '2026-10-23 11:37:19', '2026-10-23 13:22:09'),
(195, 12, 'ebook', 'cs', NULL, NULL, NULL, 329.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 1, '2026-10-23 11:51:30', '2026-10-23 13:13:40'),
(196, 10, 'ebook', 'sk', NULL, NULL, NULL, 329.00, 199.00, 1, '2026-10-22 23:59:00', '2026-12-31 23:59:00', 'CZK', 0.00, NULL, NULL, NULL, 0, '2026-10-23 12:59:06', '2026-10-23 13:32:43'),
(197, 13, 'ebook', 'cs', NULL, NULL, NULL, 270.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 1, '2026-10-25 12:27:53', '2026-10-25 13:15:10'),
(198, 13, 'ebook', 'sk', NULL, NULL, NULL, 270.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 1, '2026-10-25 12:31:13', '2026-10-25 13:15:10'),
(199, 13, 'ebook', 'pl', NULL, NULL, NULL, 270.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 0, '2026-10-25 12:31:13', '2026-10-25 13:15:10'),
(200, 13, 'ebook', 'en', NULL, NULL, NULL, 270.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 1, '2026-10-25 12:31:13', '2026-10-25 13:15:10'),
(201, 13, 'ebook', 'de', NULL, NULL, NULL, 270.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 1, '2026-10-25 12:31:13', '2026-10-25 13:15:10'),
(202, 13, 'ebook', 'fr', NULL, NULL, NULL, 270.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 1, '2026-10-25 12:31:13', '2026-10-25 13:15:10'),
(203, 13, 'ebook', 'it', NULL, NULL, NULL, 270.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 1, '2026-10-25 12:31:13', '2026-10-25 13:15:10'),
(204, 13, 'ebook', 'es', NULL, NULL, NULL, 270.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 1, '2026-10-25 12:31:13', '2026-10-25 13:15:10'),
(205, 13, 'ebook', 'ar', NULL, NULL, NULL, 270.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 1, '2026-10-25 12:31:13', '2026-10-25 13:15:10'),
(206, 13, 'ebook', 'pt', NULL, NULL, NULL, 270.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 1, '2026-10-25 12:31:13', '2026-10-25 13:15:10'),
(207, 13, 'ebook', 'ru', NULL, NULL, NULL, 270.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 1, '2026-10-25 12:31:13', '2026-10-25 13:15:10'),
(208, 13, 'ebook', 'uk', NULL, NULL, NULL, 270.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 1, '2026-10-25 12:31:13', '2026-10-25 13:15:10'),
(209, 12, 'ebook', 'sk', NULL, NULL, NULL, 329.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 1, '2026-10-25 13:30:25', '2026-10-26 11:18:59'),
(210, 12, 'ebook', 'pl', NULL, NULL, NULL, 329.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 1, '2026-10-25 13:30:25', '2026-10-26 11:10:12'),
(211, 12, 'ebook', 'en', NULL, NULL, NULL, 329.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 1, '2026-10-25 13:30:25', '2026-10-25 16:47:48'),
(212, 12, 'ebook', 'de', NULL, NULL, NULL, 329.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 1, '2026-10-25 13:30:25', '2026-10-25 16:47:48'),
(213, 12, 'ebook', 'fr', NULL, NULL, NULL, 329.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 1, '2026-10-25 13:30:25', '2026-10-26 11:51:45'),
(214, 12, 'ebook', 'it', NULL, NULL, NULL, 329.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 1, '2026-10-25 13:30:25', '2026-10-26 11:51:45'),
(215, 12, 'ebook', 'es', NULL, NULL, NULL, 329.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 1, '2026-10-25 13:30:25', '2026-10-26 11:21:16'),
(216, 12, 'ebook', 'ar', NULL, NULL, NULL, 329.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 1, '2026-10-25 13:30:25', '2026-10-26 11:51:45'),
(217, 12, 'ebook', 'pt', NULL, NULL, NULL, 329.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 1, '2026-10-25 13:30:25', '2026-10-26 11:23:44'),
(218, 12, 'ebook', 'ru', NULL, NULL, NULL, 329.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 1, '2026-10-25 13:30:25', '2026-10-26 11:51:45'),
(219, 12, 'ebook', 'uk', NULL, NULL, NULL, 329.00, NULL, 0, NULL, NULL, 'CZK', 0.00, NULL, NULL, NULL, 1, '2026-10-25 13:30:25', '2026-10-26 11:51:45');

-- --------------------------------------------------------

--
-- Struktura tabulky `rac_digital_admin_hidden_orders`
--

CREATE TABLE `rac_digital_admin_hidden_orders` (
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `hidden_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Vypisuji data pro tabulku `rac_digital_admin_hidden_orders`
--

INSERT INTO `rac_digital_admin_hidden_orders` (`order_id`, `hidden_at`) VALUES
(17, '2026-10-16 21:44:56');

-- --------------------------------------------------------

--
-- Struktura tabulky `rac_hidden_test_payments`
--

CREATE TABLE `rac_hidden_test_payments` (
  `payment_id` bigint(20) UNSIGNED NOT NULL,
  `hidden_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Vypisuji data pro tabulku `rac_hidden_test_payments`
--

INSERT INTO `rac_hidden_test_payments` (`payment_id`, `hidden_at`) VALUES
(1, '2026-10-20 11:27:41'),
(2, '2026-10-20 11:27:46'),
(3, '2026-10-20 11:27:49'),
(4, '2026-10-20 11:27:52'),
(5, '2026-10-20 11:27:55'),
(6, '2026-10-20 11:27:58'),
(7, '2026-10-20 11:28:01'),
(8, '2026-10-20 11:28:04'),
(9, '2026-10-20 11:28:07'),
(10, '2026-10-20 11:28:09');

-- --------------------------------------------------------

--
-- Struktura tabulky `rac_legal_documents`
--

CREATE TABLE `rac_legal_documents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `document_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Vypisuji data pro tabulku `rac_legal_documents`
--

INSERT INTO `rac_legal_documents` (`id`, `document_key`, `title`, `description`, `is_enabled`, `created_at`, `updated_at`) VALUES
(1, 'gdpr', 'GDPR', 'Zásady ochrany osobních údajů', 1, '2026-10-21 08:32:40', '2026-10-21 08:32:40'),
(2, 'cookies', 'Cookies', 'Cookies', 1, '2026-10-27 17:45:59', '2026-10-27 17:45:59'),
(3, 'obchodni-podminky', 'Obchodní podmínky', 'Obchodní podmíky eshoput pavlakovalovska.eu', 1, '2026-10-27 18:32:59', '2026-10-27 18:32:59');

-- --------------------------------------------------------

--
-- Struktura tabulky `rac_legal_document_versions`
--

CREATE TABLE `rac_legal_document_versions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `document_id` bigint(20) UNSIGNED NOT NULL,
  `language_code` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cs',
  `version_number` int(10) UNSIGNED NOT NULL,
  `version_label` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_html` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `content_sha256` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('draft','published','archived') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `effective_from` datetime DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `published_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Vypisuji data pro tabulku `rac_legal_document_versions`
--

INSERT INTO `rac_legal_document_versions` (`id`, `document_id`, `language_code`, `version_number`, `version_label`, `title`, `content_html`, `content_sha256`, `status`, `effective_from`, `published_at`, `published_by`, `created_by`, `created_at`) VALUES
(1, 1, 'cs', 1, '1.0', 'GDPR', '1. Správce osobních údajů\r\nSprávcem osobních údajů je:\r\nJméno / obchodní firma: Mgr. Pavla Kovalovská\r\nSídlo / adresa podnikání: Včelařská 2003/19, 73301 Karviná.\r\nKontaktní e-mail: kovalovska.pavla@seznam.cz\r\nSprávce zpracovává osobní údaje v souladu s nařízením Evropského parlamentu a Rady (EU) 2016/679 (GDPR) a souvisejícími právními předpisy.\r\n\r\n2. Jaké osobní údaje zpracováváme\r\nV souvislosti s provozem webových stránek a internetového obchodu můžeme zpracovávat:\r\nJméno a příjmení zákazníka.\r\nE-mailovou adresu a telefonní číslo.\r\nFakturační a doručovací adresu.\r\nInformace o objednávkách, zakoupených produktech a jejich cenách.\r\nÚdaje o stavu platby a transakční identifikátory.\r\nInformace poskytnuté prostřednictvím kontaktního formuláře.\r\nTechnické údaje související s provozem webu a zabezpečením.\r\nÚdaje o návštěvnosti získané prostřednictvím Google Analytics, pokud k analytickému měření udělíte souhlas.\r\n\r\n3. Účely a právní základy zpracování\r\nOsobní údaje zpracováváme pro následující účely:\r\nVyřízení objednávek a dodání produktů\r\nÚdaje využíváme k přijetí objednávky, zpracování platby, doručení tištěných knih, zpřístupnění elektronických knih a audioknih a komunikaci o objednávce.\r\nPrávním základem je plnění smlouvy podle čl. 6 odst. 1 písm. b) GDPR.\r\nPlnění zákonných povinností\r\nÚdaje zpracováváme v rozsahu nezbytném pro vedení účetní a daňové evidence a plnění dalších zákonných povinností.\r\nPrávním základem je čl. 6 odst. 1 písm. c) GDPR.\r\nZákaznická komunikace\r\nÚdaje získané prostřednictvím kontaktního formuláře využíváme pro zodpovězení dotazů a vyřízení požadavků. Právním základem je podle povahy komunikace plnění smlouvy, provedení opatření před jejím uzavřením nebo oprávněný zájem správce.\r\nOchrana práv a zabezpečení webu\r\nNezbytné údaje můžeme zpracovávat za účelem prevence zneužití webových stránek, řešení bezpečnostních incidentů a ochrany právních nároků.\r\nPrávním základem je oprávněný zájem podle čl. 6 odst. 1 písm. f) GDPR.\r\nAnalýza návštěvnosti\r\nPro statistické vyhodnocování návštěvnosti využíváme Google Analytics 4. Analytické cookies jsou používány pouze na základě souhlasu návštěvníka.\r\nPrávním základem zpracování údajů prostřednictvím analytických cookies je souhlas podle čl. 6 odst. 1 písm. a) GDPR.\r\n\r\n4. Platby a doručování\r\nPlatby v internetovém obchodě jsou zpracovávány prostřednictvím služby Stripe.\r\nZa účelem provedení platby mohou být poskytovateli platebních služeb předány nezbytné údaje o objednávce a platbě. Údaje o platební kartě jsou zpracovávány prostřednictvím zabezpečeného platebního prostředí poskytovatele platebních služeb.\r\nPři objednání tištěných knih jsou potřebné doručovací údaje předávány příslušnému dopravci v rozsahu nezbytném pro doručení zásilky.\r\n\r\n5. Google Analytics a cookies\r\nWebové stránky využívají Google Analytics 4, službu společnosti Google, pro vyhodnocování návštěvnosti a zlepšování obsahu webu.\r\nTato služba může zpracovávat údaje o navštívených stránkách, interakcích, typu zařízení, prohlížeči, přibližné geografické poloze a identifikátorech spojených s analytickými cookies.\r\nAnalytické cookies se aktivují pouze po udělení souhlasu návštěvníka.\r\nOdmítnutí analytických cookies nijak neomezuje možnost prohlížení webových stránek nebo nákupu produktů.\r\nUdělený souhlas je možné kdykoli odvolat prostřednictvím odkazu „Nastavení cookies“ dostupného na webových stránkách.\r\nNezbytné cookies mohou být používány bez souhlasu, pokud jsou skutečně potřebné pro fungování webu, například košíku nebo zabezpečení.\r\n\r\n6. Komu mohou být údaje zpřístupněny\r\nOsobní údaje mohou být v nezbytném rozsahu zpřístupněny:\r\nPoskytovateli webhostingu a technické správy.\r\nPoskytovateli platebních služeb Stripe.\r\nDopravcům zajišťujícím doručení zásilek.\r\nPoskytovateli služby Google Analytics.\r\nPoskytovatelům e-mailových a dalších nezbytných technických služeb.\r\nÚčetním nebo jiným oprávněným osobám, pokud je to potřebné.\r\nOrgánům veřejné moci, pokud tak stanoví právní předpis.\r\n\r\n7. Předávání údajů mimo Evropský hospodářský prostor\r\nV souvislosti s využíváním služeb Google a Stripe může docházet k předávání některých osobních údajů do zemí mimo Evropský hospodářský prostor, včetně Spojených států amerických.\r\nPředávání se uskutečňuje pouze při splnění příslušných podmínek GDPR, například na základě rozhodnutí o odpovídající ochraně nebo jiných použitelných záruk.\r\nKonkrétní mechanismy předávání odpovídají aktuálním smluvním podmínkám a nastavení využívaných služeb.\r\n\r\n8. Doba uchovávání údajů\r\nOsobní údaje uchováváme pouze po dobu nezbytnou pro příslušný účel:\r\nÚdaje o objednávkách po dobu potřebnou pro plnění smluvních a zákonných povinností a ochranu právních nároků.\r\nÚčetní a daňové doklady po dobu stanovenou příslušnými právními předpisy.\r\nÚdaje z kontaktního formuláře po dobu potřebnou k vyřízení požadavku a případné ochraně právních nároků.\r\nAnalytické údaje podle nastavení uchovávání v Google Analytics a doby platnosti jednotlivých cookies.\r\nKonkrétní nastavení doby uchovávání analytických údajů bude uvedeno po ověření konfigurace služby.\r\n\r\n9. Práva subjektů údajů\r\nV souladu s GDPR máte za příslušných podmínek právo:\r\nPožadovat přístup ke svým osobním údajům.\r\nPožadovat opravu nepřesných nebo neúplných údajů.\r\nPožadovat výmaz osobních údajů.\r\nPožadovat omezení zpracování.\r\nVznést námitku proti zpracování založenému na oprávněném zájmu.\r\nPožadovat přenositelnost údajů v případech stanovených GDPR.\r\nKdykoli odvolat souhlas, pokud je zpracování založeno na souhlasu.\r\nPro uplatnění svých práv můžete kontaktovat správce prostřednictvím výše uvedeného e-mailu.\r\nMáte rovněž právo podat stížnost u Úřadu pro ochranu osobních údajů (www.uoou.gov.cz).\r\n\r\n10. Zabezpečení osobních údajů\r\nSprávce přijímá přiměřená technická a organizační opatření k ochraně osobních údajů před neoprávněným přístupem, ztrátou, zneužitím nebo neoprávněnou změnou.\r\nPřístup k údajům je omezen na oprávněné osoby a poskytovatele služeb, kteří je potřebují pro plnění svých úkolů.\r\n\r\n11. Změny těchto zásad\r\nSprávce může tyto zásady aktualizovat, zejména v souvislosti se změnami právních předpisů, poskytovaných služeb nebo způsobů zpracování osobních údajů.\r\nAktuální verze dokumentu bude dostupná na webových stránkách. Dřívější verze mohou být uchovávány pro účely doložení informací platných v konkrétním období.', '80c89cc8c842b2d103f26a2e6ea70c4b398847632ef53226499314b09ea65121', 'archived', '2026-10-21 09:29:51', '2026-10-21 09:29:51', 1, 1, '2026-10-21 08:34:58'),
(2, 1, 'cs', 2, NULL, 'GDPR', '<p></p><h3>1. Správce osobních údajů</h3>Správcem osobních údajů je:<br>Jméno / obchodní firma: Mgr. Pavla Kovalovská<br>Sídlo / adresa podnikání: Včelařská 2003/19, 73301 Karviná.<br>Kontaktní e-mail: kovalovska.pavla@seznam.cz<br>Správce zpracovává osobní údaje v souladu s nařízením Evropského parlamentu a Rady (EU) 2016/679 (GDPR) a souvisejícími právními předpisy.<p></p><p></p><h2>2. Jaké osobní údaje zpracováváme</h2>V souvislosti s provozem webových stránek a internetového obchodu můžeme zpracovávat:<br>Jméno a příjmení zákazníka.<br>E-mailovou adresu a telefonní číslo.<br>Fakturační a doručovací adresu.<br>Informace o objednávkách, zakoupených produktech a jejich cenách.<br>Údaje o stavu platby a transakční identifikátory.<br>Informace poskytnuté prostřednictvím kontaktního formuláře.<br>Technické údaje související s provozem webu a zabezpečením.<br>Údaje o návštěvnosti získané prostřednictvím Google Analytics, pokud k analytickému měření udělíte souhlas.<p></p><p></p><h2>3. Účely a právní základy zpracování</h2>Osobní údaje zpracováváme pro následující účely:<br>Vyřízení objednávek a dodání produktů<br>Údaje využíváme k přijetí objednávky, zpracování platby, doručení tištěných knih, zpřístupnění elektronických knih a audioknih a komunikaci o objednávce.<br>Právním základem je plnění smlouvy podle čl. 6 odst. 1 písm. b) GDPR.<br>Plnění zákonných povinností<br>Údaje zpracováváme v rozsahu nezbytném pro vedení účetní a daňové evidence a plnění dalších zákonných povinností.<br>Právním základem je čl. 6 odst. 1 písm. c) GDPR.<br>Zákaznická komunikace<br>Údaje získané prostřednictvím kontaktního formuláře využíváme pro zodpovězení dotazů a vyřízení požadavků. Právním základem je podle povahy komunikace plnění smlouvy, provedení opatření před jejím uzavřením nebo oprávněný zájem správce.<br>Ochrana práv a zabezpečení webu<br>Nezbytné údaje můžeme zpracovávat za účelem prevence zneužití webových stránek, řešení bezpečnostních incidentů a ochrany právních nároků.<br>Právním základem je oprávněný zájem podle čl. 6 odst. 1 písm. f) GDPR.<br>Analýza návštěvnosti<br>Pro statistické vyhodnocování návštěvnosti využíváme Google Analytics 4. Analytické cookies jsou používány pouze na základě souhlasu návštěvníka.<br>Právním základem zpracování údajů prostřednictvím analytických cookies je souhlas podle čl. 6 odst. 1 písm. a) GDPR.<p></p><p></p><h2>4. Platby a doručování</h2>Platby v internetovém obchodě jsou zpracovávány prostřednictvím služby Stripe.<br>Za účelem provedení platby mohou být poskytovateli platebních služeb předány nezbytné údaje o objednávce a platbě. Údaje o platební kartě jsou zpracovávány prostřednictvím zabezpečeného platebního prostředí poskytovatele platebních služeb.<br>Při objednání tištěných knih jsou potřebné doručovací údaje předávány příslušnému dopravci v rozsahu nezbytném pro doručení zásilky.<p></p><p></p><h2>5. Google Analytics a cookies</h2>Webové stránky využívají Google Analytics 4, službu společnosti Google, pro vyhodnocování návštěvnosti a zlepšování obsahu webu.<br>Tato služba může zpracovávat údaje o navštívených stránkách, interakcích, typu zařízení, prohlížeči, přibližné geografické poloze a identifikátorech spojených s analytickými cookies.<br>Analytické cookies se aktivují pouze po udělení souhlasu návštěvníka.<br>Odmítnutí analytických cookies nijak neomezuje možnost prohlížení webových stránek nebo nákupu produktů.<br>Udělený souhlas je možné kdykoli odvolat prostřednictvím odkazu „Nastavení cookies“ dostupného na webových stránkách.<br>Nezbytné cookies mohou být používány bez souhlasu, pokud jsou skutečně potřebné pro fungování webu, například košíku nebo zabezpečení.<p></p><p></p><h2>6. Komu mohou být údaje zpřístupněny</h2>Osobní údaje mohou být v nezbytném rozsahu zpřístupněny:<br>Poskytovateli webhostingu a technické správy.<br>Poskytovateli platebních služeb Stripe.<br>Dopravcům zajišťujícím doručení zásilek.<br>Poskytovateli služby Google Analytics.<br>Poskytovatelům e-mailových a dalších nezbytných technických služeb.<br>Účetním nebo jiným oprávněným osobám, pokud je to potřebné.<br>Orgánům veřejné moci, pokud tak stanoví právní předpis.<p></p><p></p><h2>7. Předávání údajů mimo Evropský hospodářský prostor</h2>V souvislosti s využíváním služeb Google a Stripe může docházet k předávání některých osobních údajů do zemí mimo Evropský hospodářský prostor, včetně Spojených států amerických.<br>Předávání se uskutečňuje pouze při splnění příslušných podmínek GDPR, například na základě rozhodnutí o odpovídající ochraně nebo jiných použitelných záruk.<br>Konkrétní mechanismy předávání odpovídají aktuálním smluvním podmínkám a nastavení využívaných služeb.<p></p><p></p><h2>8. Doba uchovávání údajů</h2>Osobní údaje uchováváme pouze po dobu nezbytnou pro příslušný účel:<br>Údaje o objednávkách po dobu potřebnou pro plnění smluvních a zákonných povinností a ochranu právních nároků.<br>Účetní a daňové doklady po dobu stanovenou příslušnými právními předpisy.<br>Údaje z kontaktního formuláře po dobu potřebnou k vyřízení požadavku a případné ochraně právních nároků.<br>Analytické údaje podle nastavení uchovávání v Google Analytics a doby platnosti jednotlivých cookies.<br>Konkrétní nastavení doby uchovávání analytických údajů bude uvedeno po ověření konfigurace služby.<p></p><p></p><h2>9. Práva subjektů údajů</h2>V souladu s GDPR máte za příslušných podmínek právo:<br>Požadovat přístup ke svým osobním údajům.<br>Požadovat opravu nepřesných nebo neúplných údajů.<br>Požadovat výmaz osobních údajů.<br>Požadovat omezení zpracování.<br>Vznést námitku proti zpracování založenému na oprávněném zájmu.<br>Požadovat přenositelnost údajů v případech stanovených GDPR.<br>Kdykoli odvolat souhlas, pokud je zpracování založeno na souhlasu.<br>Pro uplatnění svých práv můžete kontaktovat správce prostřednictvím výše uvedeného e-mailu.<br>Máte rovněž právo podat stížnost u Úřadu pro ochranu osobních údajů (www.uoou.gov.cz).<p></p><p></p><h2>10. Zabezpečení osobních údajů</h2>Správce přijímá přiměřená technická a organizační opatření k ochraně osobních údajů před neoprávněným přístupem, ztrátou, zneužitím nebo neoprávněnou změnou.<br>Přístup k údajům je omezen na oprávněné osoby a poskytovatele služeb, kteří je potřebují pro plnění svých úkolů.<p></p><p></p><h2>11. Změny těchto zásad</h2>Správce může tyto zásady aktualizovat, zejména v souvislosti se změnami právních předpisů, poskytovaných služeb nebo způsobů zpracování osobních údajů.<br>Aktuální verze dokumentu bude dostupná na webových stránkách. Dřívější verze mohou být uchovávány pro účely doložení informací platných v konkrétním období.<p></p>', '23e8814b5fad5bfed4399a102ca2b24956bf1873271cb7d217d88979963f0bc2', 'archived', '2026-10-21 10:36:45', '2026-10-21 10:36:45', 1, 1, '2026-10-21 09:29:55'),
(3, 1, 'cs', 3, NULL, 'GDPR', '<p></p><h3>1. Správce osobních údajů</h3>Správcem osobních údajů je:<br>Jméno / obchodní firma: <mark>Mgr. Pavla Kovalovská</mark><br>Sídlo / adresa podnikání: Včelařská 2003/19, 73301 Karviná.<br>Kontaktní e-mail: kovalovska.pavla@seznam.cz<br>Správce zpracovává osobní údaje v souladu s nařízením Evropského parlamentu a Rady (EU) 2016/679 (GDPR) a souvisejícími právními předpisy.<p></p><p></p><h3>2. Jaké osobní údaje zpracováváme</h3>V souvislosti s provozem webových stránek a internetového obchodu můžeme zpracovávat:<br>Jméno a příjmení zákazníka.<br>E-mailovou adresu a telefonní číslo.<br>Fakturační a doručovací adresu.<br>Informace o objednávkách, zakoupených produktech a jejich cenách.<br>Údaje o stavu platby a transakční identifikátory.<br>Informace poskytnuté prostřednictvím kontaktního formuláře.<br>Technické údaje související s provozem webu a zabezpečením.<br>Údaje o návštěvnosti získané prostřednictvím Google Analytics, pokud k analytickému měření udělíte souhlas.<p></p><p></p><h3>3. Účely a právní základy zpracování</h3>Osobní údaje zpracováváme pro následující účely:<br>Vyřízení objednávek a dodání produktů<br>Údaje využíváme k přijetí objednávky, zpracování platby, doručení tištěných knih, zpřístupnění elektronických knih a audioknih a komunikaci o objednávce.<br>Právním základem je plnění smlouvy podle čl. 6 odst. 1 písm. b) GDPR.<br>Plnění zákonných povinností<br>Údaje zpracováváme v rozsahu nezbytném pro vedení účetní a daňové evidence a plnění dalších zákonných povinností.<br>Právním základem je čl. 6 odst. 1 písm. c) GDPR.<br>Zákaznická komunikace<br>Údaje získané prostřednictvím kontaktního formuláře využíváme pro zodpovězení dotazů a vyřízení požadavků. Právním základem je podle povahy komunikace plnění smlouvy, provedení opatření před jejím uzavřením nebo oprávněný zájem správce.<br>Ochrana práv a zabezpečení webu<br>Nezbytné údaje můžeme zpracovávat za účelem prevence zneužití webových stránek, řešení bezpečnostních incidentů a ochrany právních nároků.<br>Právním základem je oprávněný zájem podle čl. 6 odst. 1 písm. f) GDPR.<br>Analýza návštěvnosti<br>Pro statistické vyhodnocování návštěvnosti využíváme Google Analytics 4. Analytické cookies jsou používány pouze na základě souhlasu návštěvníka.<br>Právním základem zpracování údajů prostřednictvím analytických cookies je souhlas podle čl. 6 odst. 1 písm. a) GDPR.<p></p><p></p><h3>4. Platby a doručování</h3>Platby v internetovém obchodě jsou zpracovávány prostřednictvím služby Stripe.<br>Za účelem provedení platby mohou být poskytovateli platebních služeb předány nezbytné údaje o objednávce a platbě. Údaje o platební kartě jsou zpracovávány prostřednictvím zabezpečeného platebního prostředí poskytovatele platebních služeb.<br>Při objednání tištěných knih jsou potřebné doručovací údaje předávány příslušnému dopravci v rozsahu nezbytném pro doručení zásilky.<p></p><p></p><h3>5. Google Analytics a cookies</h3>Webové stránky využívají Google Analytics 4, službu společnosti Google, pro vyhodnocování návštěvnosti a zlepšování obsahu webu.<br>Tato služba může zpracovávat údaje o navštívených stránkách, interakcích, typu zařízení, prohlížeči, přibližné geografické poloze a identifikátorech spojených s analytickými cookies.<br>Analytické cookies se aktivují pouze po udělení souhlasu návštěvníka.<br>Odmítnutí analytických cookies nijak neomezuje možnost prohlížení webových stránek nebo nákupu produktů.<br>Udělený souhlas je možné kdykoli odvolat prostřednictvím odkazu „Nastavení cookies“ dostupného na webových stránkách.<br>Nezbytné cookies mohou být používány bez souhlasu, pokud jsou skutečně potřebné pro fungování webu, například košíku nebo zabezpečení.<p></p><p></p><h3>6. Komu mohou být údaje zpřístupněny</h3>Osobní údaje mohou být v nezbytném rozsahu zpřístupněny:<br>Poskytovateli webhostingu a technické správy.<br>Poskytovateli platebních služeb Stripe.<br>Dopravcům zajišťujícím doručení zásilek.<br>Poskytovateli služby Google Analytics.<br>Poskytovatelům e-mailových a dalších nezbytných technických služeb.<br>Účetním nebo jiným oprávněným osobám, pokud je to potřebné.<br>Orgánům veřejné moci, pokud tak stanoví právní předpis.<p></p><p></p><h3>7. Předávání údajů mimo Evropský hospodářský prostor</h3>V souvislosti s využíváním služeb Google a Stripe může docházet k předávání některých osobních údajů do zemí mimo Evropský hospodářský prostor, včetně Spojených států amerických.<br>Předávání se uskutečňuje pouze při splnění příslušných podmínek GDPR, například na základě rozhodnutí o odpovídající ochraně nebo jiných použitelných záruk.<br>Konkrétní mechanismy předávání odpovídají aktuálním smluvním podmínkám a nastavení využívaných služeb.<p></p><p></p><h3>8. Doba uchovávání údajů</h3>Osobní údaje uchováváme pouze po dobu nezbytnou pro příslušný účel:<br>Údaje o objednávkách po dobu potřebnou pro plnění smluvních a zákonných povinností a ochranu právních nároků.<br>Účetní a daňové doklady po dobu stanovenou příslušnými právními předpisy.<br>Údaje z kontaktního formuláře po dobu potřebnou k vyřízení požadavku a případné ochraně právních nároků.<br>Analytické údaje podle nastavení uchovávání v Google Analytics a doby platnosti jednotlivých cookies.<br>Konkrétní nastavení doby uchovávání analytických údajů bude uvedeno po ověření konfigurace služby.<p></p><p></p><h3>9. Práva subjektů údajů</h3>V souladu s GDPR máte za příslušných podmínek právo:<br>Požadovat přístup ke svým osobním údajům.<br>Požadovat opravu nepřesných nebo neúplných údajů.<br>Požadovat výmaz osobních údajů.<br>Požadovat omezení zpracování.<br>Vznést námitku proti zpracování založenému na oprávněném zájmu.<br>Požadovat přenositelnost údajů v případech stanovených GDPR.<br>Kdykoli odvolat souhlas, pokud je zpracování založeno na souhlasu.<br>Pro uplatnění svých práv můžete kontaktovat správce prostřednictvím výše uvedeného e-mailu.<br>Máte rovněž právo podat stížnost u Úřadu pro ochranu osobních údajů (www.uoou.gov.cz).<p></p><p></p><h3>10. Zabezpečení osobních údajů</h3>Správce přijímá přiměřená technická a organizační opatření k ochraně osobních údajů před neoprávněným přístupem, ztrátou, zneužitím nebo neoprávněnou změnou.<br>Přístup k údajům je omezen na oprávněné osoby a poskytovatele služeb, kteří je potřebují pro plnění svých úkolů.<p></p><p></p><h3>11. Změny těchto zásad</h3>Správce může tyto zásady aktualizovat, zejména v souvislosti se změnami právních předpisů, poskytovaných služeb nebo způsobů zpracování osobních údajů.<br>Aktuální verze dokumentu bude dostupná na webových stránkách. Dřívější verze mohou být uchovávány pro účely doložení informací platných v konkrétním období.<p></p>', 'eaf4d420e8da8e62639f10487c74590feae041a37adf53860c640358bffe8b5c', 'archived', '2026-10-21 10:42:16', '2026-10-21 10:42:16', 1, 1, '2026-10-21 10:40:41'),
(4, 1, 'cs', 4, NULL, 'GDPR', '<p></p><h3>1. Správce osobních údajů</h3>Správcem osobních údajů je:<br>Jméno / obchodní firma: <b><span class=\"rac-text-orange\">Mgr. Pavla Kovalovská</span></b><br>Sídlo / adresa podnikání: Včelařská 2003/19, 73301 Karviná.<br>Kontaktní e-mail: kovalovska.pavla@seznam.cz<br>Správce zpracovává osobní údaje v souladu s nařízením Evropského parlamentu a Rady (EU) 2016/679 (GDPR) a souvisejícími právními předpisy.<p></p><p></p><h3>2. Jaké osobní údaje zpracováváme</h3>V souvislosti s provozem webových stránek a internetového obchodu můžeme zpracovávat:<br>Jméno a příjmení zákazníka.<br>E-mailovou adresu a telefonní číslo.<br>Fakturační a doručovací adresu.<br>Informace o objednávkách, zakoupených produktech a jejich cenách.<br>Údaje o stavu platby a transakční identifikátory.<br>Informace poskytnuté prostřednictvím kontaktního formuláře.<br>Technické údaje související s provozem webu a zabezpečením.<br>Údaje o návštěvnosti získané prostřednictvím Google Analytics, pokud k analytickému měření udělíte souhlas.<p></p><p></p><h3>3. Účely a právní základy zpracování</h3>Osobní údaje zpracováváme pro následující účely:<br>Vyřízení objednávek a dodání produktů<br>Údaje využíváme k přijetí objednávky, zpracování platby, doručení tištěných knih, zpřístupnění elektronických knih a audioknih a komunikaci o objednávce.<br>Právním základem je plnění smlouvy podle čl. 6 odst. 1 písm. b) GDPR.<br>Plnění zákonných povinností<br>Údaje zpracováváme v rozsahu nezbytném pro vedení účetní a daňové evidence a plnění dalších zákonných povinností.<br>Právním základem je čl. 6 odst. 1 písm. c) GDPR.<br>Zákaznická komunikace<br>Údaje získané prostřednictvím kontaktního formuláře využíváme pro zodpovězení dotazů a vyřízení požadavků. Právním základem je podle povahy komunikace plnění smlouvy, provedení opatření před jejím uzavřením nebo oprávněný zájem správce.<br>Ochrana práv a zabezpečení webu<br>Nezbytné údaje můžeme zpracovávat za účelem prevence zneužití webových stránek, řešení bezpečnostních incidentů a ochrany právních nároků.<br>Právním základem je oprávněný zájem podle čl. 6 odst. 1 písm. f) GDPR.<br>Analýza návštěvnosti<br>Pro statistické vyhodnocování návštěvnosti využíváme Google Analytics 4. Analytické cookies jsou používány pouze na základě souhlasu návštěvníka.<br>Právním základem zpracování údajů prostřednictvím analytických cookies je souhlas podle čl. 6 odst. 1 písm. a) GDPR.<p></p><p></p><h3>4. Platby a doručování</h3>Platby v internetovém obchodě jsou zpracovávány prostřednictvím služby Stripe.<br>Za účelem provedení platby mohou být poskytovateli platebních služeb předány nezbytné údaje o objednávce a platbě. Údaje o platební kartě jsou zpracovávány prostřednictvím zabezpečeného platebního prostředí poskytovatele platebních služeb.<br>Při objednání tištěných knih jsou potřebné doručovací údaje předávány příslušnému dopravci v rozsahu nezbytném pro doručení zásilky.<p></p><p></p><h3>5. Google Analytics a cookies</h3>Webové stránky využívají Google Analytics 4, službu společnosti Google, pro vyhodnocování návštěvnosti a zlepšování obsahu webu.<br>Tato služba může zpracovávat údaje o navštívených stránkách, interakcích, typu zařízení, prohlížeči, přibližné geografické poloze a identifikátorech spojených s analytickými cookies.<br>Analytické cookies se aktivují pouze po udělení souhlasu návštěvníka.<br>Odmítnutí analytických cookies nijak neomezuje možnost prohlížení webových stránek nebo nákupu produktů.<br>Udělený souhlas je možné kdykoli odvolat prostřednictvím odkazu „Nastavení cookies“ dostupného na webových stránkách.<br>Nezbytné cookies mohou být používány bez souhlasu, pokud jsou skutečně potřebné pro fungování webu, například košíku nebo zabezpečení.<p></p><p></p><h3>6. Komu mohou být údaje zpřístupněny</h3>Osobní údaje mohou být v nezbytném rozsahu zpřístupněny:<br>Poskytovateli webhostingu a technické správy.<br>Poskytovateli platebních služeb Stripe.<br>Dopravcům zajišťujícím doručení zásilek.<br>Poskytovateli služby Google Analytics.<br>Poskytovatelům e-mailových a dalších nezbytných technických služeb.<br>Účetním nebo jiným oprávněným osobám, pokud je to potřebné.<br>Orgánům veřejné moci, pokud tak stanoví právní předpis.<p></p><p></p><h3>7. Předávání údajů mimo Evropský hospodářský prostor</h3>V souvislosti s využíváním služeb Google a Stripe může docházet k předávání některých osobních údajů do zemí mimo Evropský hospodářský prostor, včetně Spojených států amerických.<br>Předávání se uskutečňuje pouze při splnění příslušných podmínek GDPR, například na základě rozhodnutí o odpovídající ochraně nebo jiných použitelných záruk.<br>Konkrétní mechanismy předávání odpovídají aktuálním smluvním podmínkám a nastavení využívaných služeb.<p></p><p></p><h3>8. Doba uchovávání údajů</h3>Osobní údaje uchováváme pouze po dobu nezbytnou pro příslušný účel:<br>Údaje o objednávkách po dobu potřebnou pro plnění smluvních a zákonných povinností a ochranu právních nároků.<br>Účetní a daňové doklady po dobu stanovenou příslušnými právními předpisy.<br>Údaje z kontaktního formuláře po dobu potřebnou k vyřízení požadavku a případné ochraně právních nároků.<br>Analytické údaje podle nastavení uchovávání v Google Analytics a doby platnosti jednotlivých cookies.<br>Konkrétní nastavení doby uchovávání analytických údajů bude uvedeno po ověření konfigurace služby.<p></p><p></p><h3>9. Práva subjektů údajů</h3>V souladu s GDPR máte za příslušných podmínek právo:<br>Požadovat přístup ke svým osobním údajům.<br>Požadovat opravu nepřesných nebo neúplných údajů.<br>Požadovat výmaz osobních údajů.<br>Požadovat omezení zpracování.<br>Vznést námitku proti zpracování založenému na oprávněném zájmu.<br>Požadovat přenositelnost údajů v případech stanovených GDPR.<br>Kdykoli odvolat souhlas, pokud je zpracování založeno na souhlasu.<br>Pro uplatnění svých práv můžete kontaktovat správce prostřednictvím výše uvedeného e-mailu.<br>Máte rovněž právo podat stížnost u Úřadu pro ochranu osobních údajů (www.uoou.gov.cz).<p></p><p></p><h3>10. Zabezpečení osobních údajů</h3>Správce přijímá přiměřená technická a organizační opatření k ochraně osobních údajů před neoprávněným přístupem, ztrátou, zneužitím nebo neoprávněnou změnou.<br>Přístup k údajům je omezen na oprávněné osoby a poskytovatele služeb, kteří je potřebují pro plnění svých úkolů.<p></p><p></p><h3>11. Změny těchto zásad</h3>Správce může tyto zásady aktualizovat, zejména v souvislosti se změnami právních předpisů, poskytovaných služeb nebo způsobů zpracování osobních údajů.<br>Aktuální verze dokumentu bude dostupná na webových stránkách. Dřívější verze mohou být uchovávány pro účely doložení informací platných v konkrétním období.<p></p>', 'fb1f6bbb0062b2d878ba82a1c2f12ca6604e0da4169a140ec76e5d3b934d2244', 'archived', '2026-10-21 10:46:00', '2026-10-21 10:46:00', 1, 1, '2026-10-21 10:45:09'),
(5, 1, 'cs', 5, NULL, 'GDPR', '<p></p><h3>1. Správce osobních údajů</h3>Správcem osobních údajů je:<br>Jméno / obchodní firma: <b><span class=\"rac-text-orange\">Mgr. Pavla Kovalovská</span></b><br>Sídlo / adresa podnikání: Včelařská 2003/19, 73301 Karviná.<br>Kontaktní e-mail: <span class=\"rac-text-orange\"><b>kovalovska.pavla@seznam.cz</b></span><br>Správce zpracovává osobní údaje v souladu s nařízením Evropského parlamentu a Rady (EU) 2016/679 (GDPR) a souvisejícími právními předpisy.<p></p><p></p><h3>2. Jaké osobní údaje zpracováváme</h3>V souvislosti s provozem webových stránek a internetového obchodu můžeme zpracovávat:<br><ul><li>Jméno a příjmení zákazníka.</li><li>E-mailovou adresu a telefonní číslo.</li><li>Fakturační a doručovací adresu.</li><li>Informace o objednávkách, zakoupených produktech a jejich cenách.</li><li>Údaje o stavu platby a transakční identifikátory.</li><li>Informace poskytnuté prostřednictvím kontaktního formuláře.</li><li>Technické údaje související s provozem webu a zabezpečením.</li><li>Údaje o návštěvnosti získané prostřednictvím Google Analytics, pokud k analytickému měření udělíte souhlas.</li></ul><p></p><p></p><h3>3. Účely a právní základy zpracování</h3>Osobní údaje zpracováváme pro následující účely:<p><b>Vyřízení objednávek a dodání produktů</b>:&nbsp;Údaje využíváme k přijetí objednávky, zpracování platby, doručení tištěných knih, zpřístupnění elektronických knih a audioknih a komunikaci o objednávce.<br>Právním základem je plnění smlouvy podle čl. 6 odst. 1 písm. b) GDPR.<b>Plnění zákonných povinností</b> :&nbsp;Údaje zpracováváme v rozsahu nezbytném pro vedení účetní a daňové evidence a plnění dalších zákonných povinností.<br>Právním základem je čl. 6 odst. 1 písm. c) GDPR.<br><b>Zákaznická komunikace</b>: Údaje získané prostřednictvím kontaktního formuláře využíváme pro zodpovězení dotazů a vyřízení požadavků. Právním základem je podle povahy komunikace plnění smlouvy, provedení opatření před jejím uzavřením nebo oprávněný zájem správce.<p><b>Ochrana práv a zabezpečení webu</b>:&nbsp;Nezbytné údaje můžeme zpracovávat za účelem prevence zneužití webových stránek, řešení bezpečnostních incidentů a ochrany právních nároků.<br>Právním základem je oprávněný zájem podle čl. 6 odst. 1 písm. f) GDPR.<br><b>Analýza návštěvnosti</b>: Pro statistické vyhodnocování návštěvnosti využíváme Google Analytics 4. Analytické cookies jsou používány pouze na základě souhlasu návštěvníka.<br>Právním základem zpracování údajů prostřednictvím analytických cookies je souhlas podle čl. 6 odst. 1 písm. a) GDPR.<p></p><p></p><h3>4. Platby a doručování</h3>Platby v internetovém obchodě jsou zpracovávány prostřednictvím služby Stripe.<br>Za účelem provedení platby mohou být poskytovateli platebních služeb předány nezbytné údaje o objednávce a platbě. Údaje o platební kartě jsou zpracovávány prostřednictvím zabezpečeného platebního prostředí poskytovatele platebních služeb.<br>Při objednání tištěných knih jsou potřebné doručovací údaje předávány příslušnému dopravci v rozsahu nezbytném pro doručení zásilky.<p></p><p></p><h3>5. Google Analytics a cookies</h3>Webové stránky využívají Google Analytics 4, službu společnosti Google, pro vyhodnocování návštěvnosti a zlepšování obsahu webu.<br>Tato služba může zpracovávat údaje o navštívených stránkách, interakcích, typu zařízení, prohlížeči, přibližné geografické poloze a identifikátorech spojených s analytickými cookies.<br>Analytické cookies se aktivují pouze po udělení souhlasu návštěvníka.<br>Odmítnutí analytických cookies nijak neomezuje možnost prohlížení webových stránek nebo nákupu produktů.<br>Udělený souhlas je možné kdykoli odvolat prostřednictvím odkazu „Nastavení cookies“ dostupného na webových stránkách.<br>Nezbytné cookies mohou být používány bez souhlasu, pokud jsou skutečně potřebné pro fungování webu, například košíku nebo zabezpečení.<p></p><p></p><h3>6. Komu mohou být údaje zpřístupněny</h3>Osobní údaje mohou být v nezbytném rozsahu zpřístupněny:<br><ul><li>Poskytovateli webhostingu a technické správy.</li><li>Poskytovateli platebních služeb Stripe.</li><li>Dopravcům zajišťujícím doručení zásilek.</li><li>Poskytovateli služby Google Analytics.</li><li>Poskytovatelům e-mailových a dalších nezbytných technických služeb.</li><li>Účetním nebo jiným oprávněným osobám, pokud je to potřebné.</li><li>Orgánům veřejné moci, pokud tak stanoví právní předpis.</li></ul><p></p><p></p><h3>7. Předávání údajů mimo Evropský hospodářský prostor</h3>V souvislosti s využíváním služeb Google a Stripe může docházet k předávání některých osobních údajů do zemí mimo Evropský hospodářský prostor, včetně Spojených států amerických.<br>Předávání se uskutečňuje pouze při splnění příslušných podmínek GDPR, například na základě rozhodnutí o odpovídající ochraně nebo jiných použitelných záruk.<br>Konkrétní mechanismy předávání odpovídají aktuálním smluvním podmínkám a nastavení využívaných služeb.<p></p><p></p><h3>8. Doba uchovávání údajů</h3>Osobní údaje uchováváme pouze po dobu nezbytnou pro příslušný účel:<br><ul><li>Údaje o objednávkách po dobu potřebnou pro plnění smluvních a zákonných povinností a ochranu právních nároků.</li><li>Účetní a daňové doklady po dobu stanovenou příslušnými právními předpisy.</li><li>Údaje z kontaktního formuláře po dobu potřebnou k vyřízení požadavku a případné ochraně právních nároků.</li><li>Analytické údaje podle nastavení uchovávání v Google Analytics a doby platnosti jednotlivých cookies.</li></ul>Konkrétní nastavení doby uchovávání analytických údajů bude uvedeno po ověření konfigurace služby.<p></p><p></p><h3>9. Práva subjektů údajů</h3>V souladu s GDPR máte za příslušných podmínek právo:<br><ul><li>Požadovat přístup ke svým osobním údajům.</li><li>Požadovat opravu nepřesných nebo neúplných údajů.</li><li>Požadovat výmaz osobních údajů.</li><li>Požadovat omezení zpracování.</li><li>Vznést námitku proti zpracování založenému na oprávněném zájmu.</li><li>Požadovat přenositelnost údajů v případech stanovených GDPR.</li><li>Kdykoli odvolat souhlas, pokud je zpracování založeno na souhlasu.</li><li>Pro uplatnění svých práv můžete kontaktovat správce prostřednictvím výše uvedeného e-mailu.</li><li>Máte rovněž právo podat stížnost u Úřadu pro ochranu osobních údajů (www.uoou.gov.cz).</li></ul><p></p><p></p><h3>10. Zabezpečení osobních údajů</h3>Správce přijímá přiměřená technická a organizační opatření k ochraně osobních údajů před neoprávněným přístupem, ztrátou, zneužitím nebo neoprávněnou změnou.<br>Přístup k údajům je omezen na oprávněné osoby a poskytovatele služeb, kteří je potřebují pro plnění svých úkolů.<p></p><p></p><h3>11. Změny těchto zásad</h3>Správce může tyto zásady aktualizovat, zejména v souvislosti se změnami právních předpisů, poskytovaných služeb nebo způsobů zpracování osobních údajů.<br>Aktuální verze dokumentu bude dostupná na webových stránkách. Dřívější verze mohou být uchovávány pro účely doložení informací platných v konkrétním období.<p></p></p></p>', '8d258f29c21c8ddc1529de6ba456c6de8d537614d7c9e1dc0feff4e558abb911', 'archived', '2026-10-21 10:50:26', '2026-10-21 10:50:26', 1, 1, '2026-10-21 10:46:41'),
(6, 1, 'cs', 6, NULL, 'GDPR', '<p></p><h3>1. Správce osobních údajů</h3>Správcem osobních údajů je:<br>Jméno / obchodní firma: <b><span class=\"rac-text-orange\">Mgr. Pavla Kovalovská</span></b><br>Sídlo / adresa podnikání: Včelařská 2003/19, 73301 Karviná.<br>Kontaktní e-mail: <span class=\"rac-text-orange\"><b>kovalovska.pavla@seznam.cz</b></span><br>Správce zpracovává osobní údaje v souladu s nařízením Evropského parlamentu a Rady (EU) 2016/679 (GDPR) a souvisejícími právními předpisy.<p></p><p></p><h3>2. Jaké osobní údaje zpracováváme</h3>V souvislosti s provozem webových stránek a internetového obchodu můžeme zpracovávat:<br><ul><li>Jméno a příjmení zákazníka.</li><li>E-mailovou adresu a telefonní číslo.</li><li>Fakturační a doručovací adresu.</li><li>Informace o objednávkách, zakoupených produktech a jejich cenách.</li><li>Údaje o stavu platby a transakční identifikátory.</li><li>Informace poskytnuté prostřednictvím kontaktního formuláře.</li><li>Technické údaje související s provozem webu a zabezpečením.</li><li>Údaje o návštěvnosti získané prostřednictvím Google Analytics, pokud k analytickému měření udělíte souhlas.</li></ul><p></p><p></p><h3>3. Účely a právní základy zpracování</h3>Osobní údaje zpracováváme pro následující účely:<p><b>Vyřízení objednávek a dodání produktů</b>:&nbsp;Údaje využíváme k přijetí objednávky, zpracování platby, doručení tištěných knih, zpřístupnění elektronických knih a audioknih a komunikaci o objednávce.<br>Právním základem je plnění smlouvy podle čl. 6 odst. 1 písm. b) GDPR.<b>Plnění zákonných povinností</b> :&nbsp;Údaje zpracováváme v rozsahu nezbytném pro vedení účetní a daňové evidence a plnění dalších zákonných povinností.<br>Právním základem je čl. 6 odst. 1 písm. c) GDPR.<br><b>Zákaznická komunikace</b>: Údaje získané prostřednictvím kontaktního formuláře využíváme pro zodpovězení dotazů a vyřízení požadavků. Právním základem je podle povahy komunikace plnění smlouvy, provedení opatření před jejím uzavřením nebo oprávněný zájem správce.<p><b>Ochrana práv a zabezpečení webu</b>:&nbsp;Nezbytné údaje můžeme zpracovávat za účelem prevence zneužití webových stránek, řešení bezpečnostních incidentů a ochrany právních nároků.<br>Právním základem je oprávněný zájem podle čl. 6 odst. 1 písm. f) GDPR.<br><b>Analýza návštěvnosti</b>: Pro statistické vyhodnocování návštěvnosti využíváme Google Analytics 4. Analytické cookies jsou používány pouze na základě souhlasu návštěvníka.<br>Právním základem zpracování údajů prostřednictvím analytických cookies je souhlas podle čl. 6 odst. 1 písm. a) GDPR.<p></p><p></p><h3>4. Platby a doručování</h3>Platby v internetovém obchodě jsou zpracovávány prostřednictvím služby Stripe.<br>Za účelem provedení platby mohou být poskytovateli platebních služeb předány nezbytné údaje o objednávce a platbě. Údaje o platební kartě jsou zpracovávány prostřednictvím zabezpečeného platebního prostředí poskytovatele platebních služeb.<br>Při objednání tištěných knih jsou potřebné doručovací údaje předávány příslušnému dopravci v rozsahu nezbytném pro doručení zásilky.<p></p><p></p><h3>5. Google Analytics a cookies</h3>Webové stránky využívají Google Analytics 4, službu společnosti Google, pro vyhodnocování návštěvnosti a zlepšování obsahu webu.<br>Tato služba může zpracovávat údaje o navštívených stránkách, interakcích, typu zařízení, prohlížeči, přibližné geografické poloze a identifikátorech spojených s analytickými cookies.<br>Analytické cookies se aktivují pouze po udělení souhlasu návštěvníka.<br>Odmítnutí analytických cookies nijak neomezuje možnost prohlížení webových stránek nebo nákupu produktů.<br>Udělený souhlas je možné kdykoli odvolat prostřednictvím odkazu „Nastavení cookies“ dostupného na webových stránkách.<br>Nezbytné cookies mohou být používány bez souhlasu, pokud jsou skutečně potřebné pro fungování webu, například košíku nebo zabezpečení.<p></p><p></p><h3>6. Komu mohou být údaje zpřístupněny</h3>Osobní údaje mohou být v nezbytném rozsahu zpřístupněny:<br><ul><li>Poskytovateli webhostingu a technické správy.</li><li>Poskytovateli platebních služeb Stripe.</li><li>Dopravcům zajišťujícím doručení zásilek.</li><li>Poskytovateli služby Google Analytics.</li><li>Poskytovatelům e-mailových a dalších nezbytných technických služeb.</li><li>Účetním nebo jiným oprávněným osobám, pokud je to potřebné.</li><li>Orgánům veřejné moci, pokud tak stanoví právní předpis.</li></ul><p></p><p></p><h3>7. Předávání údajů mimo Evropský hospodářský prostor</h3>V souvislosti s využíváním služeb Google a Stripe může docházet k předávání některých osobních údajů do zemí mimo Evropský hospodářský prostor, včetně Spojených států amerických.<br>Předávání se uskutečňuje pouze při splnění příslušných podmínek GDPR, například na základě rozhodnutí o odpovídající ochraně nebo jiných použitelných záruk.<br>Konkrétní mechanismy předávání odpovídají aktuálním smluvním podmínkám a nastavení využívaných služeb.<p></p><p></p><h3>8. Doba uchovávání údajů</h3>Osobní údaje uchováváme pouze po dobu nezbytnou pro příslušný účel:<br><ul><li>Údaje o objednávkách po dobu potřebnou pro plnění smluvních a zákonných povinností a ochranu právních nároků.</li><li>Účetní a daňové doklady po dobu stanovenou příslušnými právními předpisy.</li><li>Údaje z kontaktního formuláře po dobu potřebnou k vyřízení požadavku a případné ochraně právních nároků.</li><li>Analytické údaje podle nastavení uchovávání v Google Analytics a doby platnosti jednotlivých cookies.</li></ul>Konkrétní nastavení doby uchovávání analytických údajů bude uvedeno po ověření konfigurace služby.<p></p><p></p><h3>9. Práva subjektů údajů</h3>V souladu s GDPR máte za příslušných podmínek právo:<br><ul><li>Požadovat přístup ke svým osobním údajům.</li><li>Požadovat opravu nepřesných nebo neúplných údajů.</li><li>Požadovat výmaz osobních údajů.</li><li>Požadovat omezení zpracování.</li><li>Vznést námitku proti zpracování založenému na oprávněném zájmu.</li><li>Požadovat přenositelnost údajů v případech stanovených GDPR.</li><li>Kdykoli odvolat souhlas, pokud je zpracování založeno na souhlasu.</li><li>Pro uplatnění svých práv můžete kontaktovat správce prostřednictvím výše uvedeného e-mailu.</li><li>Máte rovněž právo podat stížnost u Úřadu pro ochranu osobních údajů (www.uoou.gov.cz).</li></ul><p></p><p></p><h3>10. Zabezpečení osobních údajů</h3>Správce přijímá přiměřená technická a organizační opatření k ochraně osobních údajů před neoprávněným přístupem, ztrátou, zneužitím nebo neoprávněnou změnou.<br>Přístup k údajům je omezen na oprávněné osoby a poskytovatele služeb, kteří je potřebují pro plnění svých úkolů.<p></p><p></p><h3>11. Změny těchto zásad</h3>Správce může tyto zásady aktualizovat, zejména v souvislosti se změnami právních předpisů, poskytovaných služeb nebo způsobů zpracování osobních údajů.<br>Aktuální verze dokumentu bude dostupná na webových stránkách. Dřívější verze mohou být uchovávány pro účely doložení informací platných v konkrétním období.<p></p></p></p>', '8d258f29c21c8ddc1529de6ba456c6de8d537614d7c9e1dc0feff4e558abb911', 'archived', '2026-10-21 11:09:43', '2026-10-21 11:09:43', 1, 1, '2026-10-21 11:07:00'),
(7, 1, 'cs', 7, NULL, 'GDPR', '<p></p><h3>1. Správce osobních údajů</h3>Správcem osobních údajů je:<br>Jméno / obchodní firma: <b><span class=\"rac-text-orange\">Mgr. Pavla Kovalovská</span></b><br>Sídlo / adresa podnikání: Včelařská 2003/19, 73301 Karviná.<br>Kontaktní e-mail: <span class=\"rac-text-orange\"><b>kovalovska.pavla@seznam.cz</b></span><br>Správce zpracovává osobní údaje v souladu s nařízením Evropského parlamentu a Rady (EU) 2016/679 (GDPR) a souvisejícími právními předpisy.<p></p><p></p><h3>2. Jaké osobní údaje zpracováváme</h3>V souvislosti s provozem webových stránek a internetového obchodu můžeme zpracovávat:<br><ul><li>Jméno a příjmení zákazníka.</li><li>E-mailovou adresu a telefonní číslo.</li><li>Fakturační a doručovací adresu.</li><li>Informace o objednávkách, zakoupených produktech a jejich cenách.</li><li>Údaje o stavu platby a transakční identifikátory.</li><li>Informace poskytnuté prostřednictvím kontaktního formuláře.</li><li>Technické údaje související s provozem webu a zabezpečením.</li><li>Údaje o návštěvnosti získané prostřednictvím Google Analytics, pokud k analytickému měření udělíte souhlas.</li></ul><p></p><p></p><h3>3. Účely a právní základy zpracování</h3>Osobní údaje zpracováváme pro následující účely:<p><b>Vyřízení objednávek a dodání produktů</b>:&nbsp;Údaje využíváme k přijetí objednávky, zpracování platby, doručení tištěných knih, zpřístupnění elektronických knih a audioknih a komunikaci o objednávce.<br>Právním základem je plnění smlouvy podle čl. 6 odst. 1 písm. b) GDPR.<b>Plnění zákonných povinností</b> :&nbsp;Údaje zpracováváme v rozsahu nezbytném pro vedení účetní a daňové evidence a plnění dalších zákonných povinností.<br>Právním základem je čl. 6 odst. 1 písm. c) GDPR.<br><b>Zákaznická komunikace</b>: Údaje získané prostřednictvím kontaktního formuláře využíváme pro zodpovězení dotazů a vyřízení požadavků. Právním základem je podle povahy komunikace plnění smlouvy, provedení opatření před jejím uzavřením nebo oprávněný zájem správce.<p><b>Ochrana práv a zabezpečení webu</b>:&nbsp;Nezbytné údaje můžeme zpracovávat za účelem prevence zneužití webových stránek, řešení bezpečnostních incidentů a ochrany právních nároků.<br>Právním základem je oprávněný zájem podle čl. 6 odst. 1 písm. f) GDPR.<br><b>Analýza návštěvnosti</b>: Pro statistické vyhodnocování návštěvnosti využíváme Google Analytics 4. Analytické cookies jsou používány pouze na základě souhlasu návštěvníka.<br>Právním základem zpracování údajů prostřednictvím analytických cookies je souhlas podle čl. 6 odst. 1 písm. a) GDPR.<p></p><p></p><h3>4. Platby a doručování</h3>Platby v internetovém obchodě jsou zpracovávány prostřednictvím služby Stripe.<br>Za účelem provedení platby mohou být poskytovateli platebních služeb předány nezbytné údaje o objednávce a platbě. Údaje o platební kartě jsou zpracovávány prostřednictvím zabezpečeného platebního prostředí poskytovatele platebních služeb.<br>Při objednání tištěných knih jsou potřebné doručovací údaje předávány příslušnému dopravci v rozsahu nezbytném pro doručení zásilky.<p></p><p></p><h3>5. Google Analytics a cookies</h3>Webové stránky využívají Google Analytics 4, službu společnosti Google, pro vyhodnocování návštěvnosti a zlepšování obsahu webu.<br>Tato služba může zpracovávat údaje o navštívených stránkách, interakcích, typu zařízení, prohlížeči, přibližné geografické poloze a identifikátorech spojených s analytickými cookies.<br>Analytické cookies se aktivují pouze po udělení souhlasu návštěvníka.<br>Odmítnutí analytických cookies nijak neomezuje možnost prohlížení webových stránek nebo nákupu produktů.<br>Udělený souhlas je možné kdykoli odvolat prostřednictvím odkazu „Nastavení cookies“ dostupného na webových stránkách.<br>Nezbytné cookies mohou být používány bez souhlasu, pokud jsou skutečně potřebné pro fungování webu, například košíku nebo zabezpečení.<p></p><p></p><h3>6. Komu mohou být údaje zpřístupněny</h3>Osobní údaje mohou být v nezbytném rozsahu zpřístupněny:<br><ul><li>Poskytovateli webhostingu a technické správy.</li><li>Poskytovateli platebních služeb Stripe.</li><li>Dopravcům zajišťujícím doručení zásilek.</li><li>Poskytovateli služby Google Analytics.</li><li>Poskytovatelům e-mailových a dalších nezbytných technických služeb.</li><li>Účetním nebo jiným oprávněným osobám, pokud je to potřebné.</li><li>Orgánům veřejné moci, pokud tak stanoví právní předpis.</li></ul><p></p><p></p><h3>7. Předávání údajů mimo Evropský hospodářský prostor</h3>V souvislosti s využíváním služeb Google a Stripe může docházet k předávání některých osobních údajů do zemí mimo Evropský hospodářský prostor, včetně Spojených států amerických.<br>Předávání se uskutečňuje pouze při splnění příslušných podmínek GDPR, například na základě rozhodnutí o odpovídající ochraně nebo jiných použitelných záruk.<br>Konkrétní mechanismy předávání odpovídají aktuálním smluvním podmínkám a nastavení využívaných služeb.<p></p><p></p><h3>8. Doba uchovávání údajů</h3>Osobní údaje uchováváme pouze po dobu nezbytnou pro příslušný účel:<br><ul><li>Údaje o objednávkách po dobu potřebnou pro plnění smluvních a zákonných povinností a ochranu právních nároků.</li><li>Účetní a daňové doklady po dobu stanovenou příslušnými právními předpisy.</li><li>Údaje z kontaktního formuláře po dobu potřebnou k vyřízení požadavku a případné ochraně právních nároků.</li><li>Analytické údaje podle nastavení uchovávání v Google Analytics a doby platnosti jednotlivých cookies.</li></ul>Konkrétní nastavení doby uchovávání analytických údajů bude uvedeno po ověření konfigurace služby.<p></p><p></p><h3>9. Práva subjektů údajů</h3>V souladu s GDPR máte za příslušných podmínek právo:<br><ul><li>Požadovat přístup ke svým osobním údajům.</li><li>Požadovat opravu nepřesných nebo neúplných údajů.</li><li>Požadovat výmaz osobních údajů.</li><li>Požadovat omezení zpracování.</li><li>Vznést námitku proti zpracování založenému na oprávněném zájmu.</li><li>Požadovat přenositelnost údajů v případech stanovených GDPR.</li><li>Kdykoli odvolat souhlas, pokud je zpracování založeno na souhlasu.</li><li>Pro uplatnění svých práv můžete kontaktovat správce prostřednictvím výše uvedeného e-mailu.</li><li>Máte rovněž právo podat stížnost u Úřadu pro ochranu osobních údajů (www.uoou.gov.cz).</li></ul><p></p><p></p><h3>10. Zabezpečení osobních údajů</h3>Správce přijímá přiměřená technická a organizační opatření k ochraně osobních údajů před neoprávněným přístupem, ztrátou, zneužitím nebo neoprávněnou změnou.<br>Přístup k údajům je omezen na oprávněné osoby a poskytovatele služeb, kteří je potřebují pro plnění svých úkolů.<p></p><p></p><h3>11. Změny těchto zásad</h3>Správce může tyto zásady aktualizovat, zejména v souvislosti se změnami právních předpisů, poskytovaných služeb nebo způsobů zpracování osobních údajů.<br>Aktuální verze dokumentu bude dostupná na webových stránkách. Dřívější verze mohou být uchovávány pro účely doložení informací platných v konkrétním období.<p></p></p></p>', '8d258f29c21c8ddc1529de6ba456c6de8d537614d7c9e1dc0feff4e558abb911', 'archived', '2026-10-21 11:23:31', '2026-10-21 11:23:31', 1, 1, '2026-10-21 11:12:23');
INSERT INTO `rac_legal_document_versions` (`id`, `document_id`, `language_code`, `version_number`, `version_label`, `title`, `content_html`, `content_sha256`, `status`, `effective_from`, `published_at`, `published_by`, `created_by`, `created_at`) VALUES
(8, 1, 'en', 1, NULL, 'Privacy Policy', '<h3>1. Data Controller</h3><p>The controller of personal data is:</p><p>Name / Business Name:<span class=\"rac-text-orange\"> Mgr. Pavla Kovalovská</span></p><p>Registered Office / Business Address: Včelařská 2003/19, 733 01 Karviná, Czech Republic</p><p>Contact Email: <span class=\"rac-text-orange\">kovalovska.pavla@seznam.cz</span></p><p>The Data Controller processes personal data in accordance with Regulation (EU) 2016/679 of the European Parliament and of the Council (General Data Protection Regulation – GDPR) and other applicable legislation.</p><p><br></p><p>2. What Personal Data We Process</p><p>In connection with the operation of our website and online store, we may process the following personal data:</p><p></p><ul><li>Customer\'s first and last name.</li><li>Email address and telephone number.</li><li>Billing and delivery addresses.</li><li>Information about orders, purchased products, and their prices.</li><li>Payment status information and transaction identifiers.</li><li>Information submitted through the contact form.</li><li>Technical data related to website operation and security.</li><li>Website traffic data collected through Google Analytics, provided that you have consented to analytics tracking.</li></ul><p></p><p>3. Purposes and Legal Bases for Processing</p><p>We process personal data for the following purposes:</p><p><b>Order Processing and Product Delivery</b>: We use personal data to receive and process orders, process payments, deliver printed books, provide access to e-books and audiobooks, and communicate with customers regarding their orders.</p><p>The legal basis for this processing is the performance of a contract pursuant to Article 6(1)(b) of the GDPR.</p><p><b>Compliance with Legal Obligations</b>: We process personal data to the extent necessary to maintain accounting and tax records and fulfil other statutory obligations.</p><p>The legal basis for this processing is Article 6(1)(c) of the GDPR.</p><p><b>Customer Communication</b>: We use information submitted through the contact form to respond to enquiries and handle requests.</p><p>Depending on the nature of the communication, the legal basis is the performance of a contract, taking steps prior to entering into a contract, or the legitimate interests of the Data Controller.</p><p><b>Protection of Rights and Website Security</b>: We may process necessary personal data to prevent website misuse, address security incidents, and protect legal claims.</p><p>The legal basis for this processing is legitimate interests pursuant to Article 6(1)(f) of the GDPR.</p><p><b>Website Traffic Analysis</b>: We use Google Analytics 4 to statistically evaluate website traffic. Analytics cookies are used only with the visitor\'s consent.</p><p>The legal basis for processing personal data through analytics cookies is consent pursuant to Article 6(1)(a) of the GDPR.</p><p><br></p><p>4. Payments and Delivery</p><p>Payments made through the online store are processed using Stripe.</p><p>For payment processing purposes, necessary order and payment information may be shared with the payment service provider.</p><p>Payment card details are processed through the payment service provider\'s secure payment environment.</p><p>When printed books are ordered, the necessary delivery information is provided to the relevant shipping carrier to the extent required for delivery.</p><p><br></p><p>5. Google Analytics and Cookies</p><p>Our website uses Google Analytics 4, a service provided by Google, to analyse website traffic and improve website content.</p><p></p><ul><li>This service may process information about visited pages, user interactions, device types, browsers, approximate geographical locations, and identifiers associated with analytics cookies.</li><li>Analytics cookies are activated only after the visitor has given consent.</li><li>Refusing analytics cookies does not restrict the ability to browse the website or purchase products.</li><li>Consent may be withdrawn at any time through the \"Cookie Settings\" link available on the website.</li><li>Strictly necessary cookies may be used without consent where they are genuinely required for website functionality, such as shopping cart operations or security.</li></ul><p></p><p><br></p><p>6. Who May Have Access to Personal Data</p><p>Personal data may be disclosed, to the extent necessary, to:</p><p></p><ul><li>Website hosting and technical administration providers.</li><li>Stripe, our payment service provider.</li><li>Shipping carriers responsible for delivering orders.</li><li>Google Analytics service providers.</li><li>Email service providers and other necessary technical service providers.</li><li>Accountants or other authorised persons, where required.</li><li>Public authorities where disclosure is required by law.</li></ul><p></p><p><br></p><h3>7. Transfers of Personal Data Outside the European Economic Area</h3><p>In connection with the use of Google and Stripe services, certain personal data may be transferred to countries outside the European Economic Area (EEA), including the United States of America.</p><p>Such transfers take place only where the applicable GDPR requirements are met, for example, on the basis of an adequacy decision or other appropriate safeguards.</p><p>The specific transfer mechanisms depend on the current contractual terms and configuration of the services used.</p><p><br></p><h3>8. Data Retention Periods</h3><p>We retain personal data only for as long as necessary for the relevant purpose:</p><p></p><ul><li>Order information: For the period necessary to fulfil contractual and legal obligations and protect legal claims.</li><li>Accounting and tax documents: For the period required by applicable legislation.</li><li>Contact form submissions: For the period necessary to handle the request and, where applicable, protect legal claims.</li><li>Analytics data: In accordance with the data retention settings in Google Analytics and the lifespan of individual cookies.</li><li>The specific retention period for analytics data will be stated once the service configuration has been verified.</li></ul><p></p><p><br></p><h3>9. Rights of Data Subjects</h3><p>In accordance with the GDPR, and subject to the applicable conditions, you have the right to:</p><p></p><ul><li>Request access to your personal data.</li><li>Request the correction of inaccurate or incomplete personal data.</li><li>Request the erasure of your personal data.</li><li>Request restriction of processing.</li><li>Object to processing based on legitimate interests.</li><li>Request data portability in cases provided for by the GDPR.</li><li>Withdraw your consent at any time where processing is based on consent.</li><li>To exercise your rights, you may contact the Data Controller using the email address provided above.</li></ul><p></p><p>You also have the right to lodge a complaint with the Czech Office for Personal Data Protection (Úřad pro ochranu osobních údajů) at www.uoou.gov.cz.</p><p><br></p><h3>10. Personal Data Security</h3><p>The Data Controller implements appropriate technical and organisational measures to protect personal data against unauthorised access, loss, misuse, or unauthorised modification.</p><p>Access to personal data is restricted to authorised persons and service providers who require such access to perform their duties.</p><p><br></p><h3>11. Changes to This Privacy Policy</h3><p>The Data Controller may update this Privacy Policy, particularly in response to changes in legislation, services provided, or personal data processing practices.</p><p>The current version of this document will be available on the website.</p><p>Previous versions may be retained for the purpose of demonstrating which information was applicable during a particular period.</p>', '9920313a095419d952486af1193535124c35b3580906d921c970e1d589ac2897', 'archived', '2026-10-21 11:23:56', '2026-10-21 11:23:56', 1, 1, '2026-10-21 11:23:03'),
(9, 1, 'en', 2, NULL, 'Privacy Policy', '<h3>1. Data Controller</h3><p>The controller of personal data is:<br>Name / Business Name:<span class=\"rac-text-orange\"> Mgr. Pavla Kovalovská</span><br>Registered Office / Business Address: Včelařská 2003/19, 733 01 Karviná, Czech Republic<br>Contact Email: <span class=\"rac-text-orange\">kovalovska.pavla@seznam.cz</span><br>The Data Controller processes personal data in accordance with Regulation (EU) 2016/679 of the European Parliament and of the Council (General Data Protection Regulation – GDPR) and other applicable legislation.</p><p><br></p><p>2. What Personal Data We Process</p><p>In connection with the operation of our website and online store, we may process the following personal data:</p><p></p><ul><li>Customer\'s first and last name.</li><li>Email address and telephone number.</li><li>Billing and delivery addresses.</li><li>Information about orders, purchased products, and their prices.</li><li>Payment status information and transaction identifiers.</li><li>Information submitted through the contact form.</li><li>Technical data related to website operation and security.</li><li>Website traffic data collected through Google Analytics, provided that you have consented to analytics tracking.</li></ul><p></p><p>3. Purposes and Legal Bases for Processing</p><p>We process personal data for the following purposes:</p><p><b>Order Processing and Product Delivery</b>: We use personal data to receive and process orders, process payments, deliver printed books, provide access to e-books and audiobooks, and communicate with customers regarding their orders.</p><p>The legal basis for this processing is the performance of a contract pursuant to Article 6(1)(b) of the GDPR.</p><p><b>Compliance with Legal Obligations</b>: We process personal data to the extent necessary to maintain accounting and tax records and fulfil other statutory obligations.</p><p>The legal basis for this processing is Article 6(1)(c) of the GDPR.</p><p><b>Customer Communication</b>: We use information submitted through the contact form to respond to enquiries and handle requests.</p><p>Depending on the nature of the communication, the legal basis is the performance of a contract, taking steps prior to entering into a contract, or the legitimate interests of the Data Controller.</p><p><b>Protection of Rights and Website Security</b>: We may process necessary personal data to prevent website misuse, address security incidents, and protect legal claims.</p><p>The legal basis for this processing is legitimate interests pursuant to Article 6(1)(f) of the GDPR.</p><p><b>Website Traffic Analysis</b>: We use Google Analytics 4 to statistically evaluate website traffic. Analytics cookies are used only with the visitor\'s consent.</p><p>The legal basis for processing personal data through analytics cookies is consent pursuant to Article 6(1)(a) of the GDPR.</p><p><br></p><p>4. Payments and Delivery</p><p>Payments made through the online store are processed using Stripe.</p><p>For payment processing purposes, necessary order and payment information may be shared with the payment service provider.</p><p>Payment card details are processed through the payment service provider\'s secure payment environment.</p><p>When printed books are ordered, the necessary delivery information is provided to the relevant shipping carrier to the extent required for delivery.</p><p><br></p><p>5. Google Analytics and Cookies</p><p>Our website uses Google Analytics 4, a service provided by Google, to analyse website traffic and improve website content.</p><p></p><ul><li>This service may process information about visited pages, user interactions, device types, browsers, approximate geographical locations, and identifiers associated with analytics cookies.</li><li>Analytics cookies are activated only after the visitor has given consent.</li><li>Refusing analytics cookies does not restrict the ability to browse the website or purchase products.</li><li>Consent may be withdrawn at any time through the \"Cookie Settings\" link available on the website.</li><li>Strictly necessary cookies may be used without consent where they are genuinely required for website functionality, such as shopping cart operations or security.</li></ul><p></p><p><br></p><p>6. Who May Have Access to Personal Data</p><p>Personal data may be disclosed, to the extent necessary, to:</p><p></p><ul><li>Website hosting and technical administration providers.</li><li>Stripe, our payment service provider.</li><li>Shipping carriers responsible for delivering orders.</li><li>Google Analytics service providers.</li><li>Email service providers and other necessary technical service providers.</li><li>Accountants or other authorised persons, where required.</li><li>Public authorities where disclosure is required by law.</li></ul><p></p><p><br></p><h3>7. Transfers of Personal Data Outside the European Economic Area</h3><p>In connection with the use of Google and Stripe services, certain personal data may be transferred to countries outside the European Economic Area (EEA), including the United States of America.</p><p>Such transfers take place only where the applicable GDPR requirements are met, for example, on the basis of an adequacy decision or other appropriate safeguards.</p><p>The specific transfer mechanisms depend on the current contractual terms and configuration of the services used.</p><p><br></p><h3>8. Data Retention Periods</h3><p>We retain personal data only for as long as necessary for the relevant purpose:</p><p></p><ul><li>Order information: For the period necessary to fulfil contractual and legal obligations and protect legal claims.</li><li>Accounting and tax documents: For the period required by applicable legislation.</li><li>Contact form submissions: For the period necessary to handle the request and, where applicable, protect legal claims.</li><li>Analytics data: In accordance with the data retention settings in Google Analytics and the lifespan of individual cookies.</li><li>The specific retention period for analytics data will be stated once the service configuration has been verified.</li></ul><p></p><p><br></p><h3>9. Rights of Data Subjects</h3><p>In accordance with the GDPR, and subject to the applicable conditions, you have the right to:</p><p></p><ul><li>Request access to your personal data.</li><li>Request the correction of inaccurate or incomplete personal data.</li><li>Request the erasure of your personal data.</li><li>Request restriction of processing.</li><li>Object to processing based on legitimate interests.</li><li>Request data portability in cases provided for by the GDPR.</li><li>Withdraw your consent at any time where processing is based on consent.</li><li>To exercise your rights, you may contact the Data Controller using the email address provided above.</li></ul><p></p><p>You also have the right to lodge a complaint with the Czech Office for Personal Data Protection (Úřad pro ochranu osobních údajů) at www.uoou.gov.cz.</p><p><br></p><h3>10. Personal Data Security</h3><p>The Data Controller implements appropriate technical and organisational measures to protect personal data against unauthorised access, loss, misuse, or unauthorised modification.</p><p>Access to personal data is restricted to authorised persons and service providers who require such access to perform their duties.</p><p><br></p><h3>11. Changes to This Privacy Policy</h3><p>The Data Controller may update this Privacy Policy, particularly in response to changes in legislation, services provided, or personal data processing practices.</p><p>The current version of this document will be available on the website.</p><p>Previous versions may be retained for the purpose of demonstrating which information was applicable during a particular period.</p>', '8589ff2fefc1ab8fdc18a558c783dbd0aa09081ba9251dba03545844a2f7013b', 'archived', '2026-10-21 11:26:20', '2026-10-21 11:26:20', 1, 1, '2026-10-21 11:24:47'),
(10, 1, 'en', 3, NULL, 'Privacy Policy', '<h3>1. Data Controller</h3><p>The controller of personal data is:<br>Name / Business Name:<span class=\"rac-text-orange\"> Mgr. Pavla Kovalovská</span><br>Registered Office / Business Address: Včelařská 2003/19, 733 01 Karviná, Czech Republic<br>Contact Email: <span class=\"rac-text-orange\">kovalovska.pavla@seznam.cz</span><br>The Data Controller processes personal data in accordance with Regulation (EU) 2016/679 of the European Parliament and of the Council (General Data Protection Regulation – GDPR) and other applicable legislation.</p><p><br></p><h3>2. What Personal Data We Process</h3><p>In connection with the operation of our website and online store, we may process the following personal data:</p><p></p><ul><li>Customer\'s first and last name.</li><li>Email address and telephone number.</li><li>Billing and delivery addresses.</li><li>Information about orders, purchased products, and their prices.</li><li>Payment status information and transaction identifiers.</li><li>Information submitted through the contact form.</li><li>Technical data related to website operation and security.</li><li>Website traffic data collected through Google Analytics, provided that you have consented to analytics tracking.</li></ul><p></p><p>3. Purposes and Legal Bases for Processing</p><p>We process personal data for the following purposes:</p><p><b>Order Processing and Product Delivery</b>: We use personal data to receive and process orders, process payments, deliver printed books, provide access to e-books and audiobooks, and communicate with customers regarding their orders.</p><p>The legal basis for this processing is the performance of a contract pursuant to Article 6(1)(b) of the GDPR.</p><p><b>Compliance with Legal Obligations</b>: We process personal data to the extent necessary to maintain accounting and tax records and fulfil other statutory obligations.</p><p>The legal basis for this processing is Article 6(1)(c) of the GDPR.</p><p><b>Customer Communication</b>: We use information submitted through the contact form to respond to enquiries and handle requests.</p><p>Depending on the nature of the communication, the legal basis is the performance of a contract, taking steps prior to entering into a contract, or the legitimate interests of the Data Controller.</p><p><b>Protection of Rights and Website Security</b>: We may process necessary personal data to prevent website misuse, address security incidents, and protect legal claims.</p><p>The legal basis for this processing is legitimate interests pursuant to Article 6(1)(f) of the GDPR.</p><p><b>Website Traffic Analysis</b>: We use Google Analytics 4 to statistically evaluate website traffic. Analytics cookies are used only with the visitor\'s consent.</p><p>The legal basis for processing personal data through analytics cookies is consent pursuant to Article 6(1)(a) of the GDPR.</p><p><br></p><p>4. Payments and Delivery</p><p>Payments made through the online store are processed using Stripe.</p><p>For payment processing purposes, necessary order and payment information may be shared with the payment service provider.</p><p>Payment card details are processed through the payment service provider\'s secure payment environment.</p><p>When printed books are ordered, the necessary delivery information is provided to the relevant shipping carrier to the extent required for delivery.</p><p><br></p><p>5. Google Analytics and Cookies</p><p>Our website uses Google Analytics 4, a service provided by Google, to analyse website traffic and improve website content.</p><p></p><ul><li>This service may process information about visited pages, user interactions, device types, browsers, approximate geographical locations, and identifiers associated with analytics cookies.</li><li>Analytics cookies are activated only after the visitor has given consent.</li><li>Refusing analytics cookies does not restrict the ability to browse the website or purchase products.</li><li>Consent may be withdrawn at any time through the \"Cookie Settings\" link available on the website.</li><li>Strictly necessary cookies may be used without consent where they are genuinely required for website functionality, such as shopping cart operations or security.</li></ul><p></p><p><br></p><p>6. Who May Have Access to Personal Data</p><p>Personal data may be disclosed, to the extent necessary, to:</p><p></p><ul><li>Website hosting and technical administration providers.</li><li>Stripe, our payment service provider.</li><li>Shipping carriers responsible for delivering orders.</li><li>Google Analytics service providers.</li><li>Email service providers and other necessary technical service providers.</li><li>Accountants or other authorised persons, where required.</li><li>Public authorities where disclosure is required by law.</li></ul><p></p><p><br></p><h3>7. Transfers of Personal Data Outside the European Economic Area</h3><p>In connection with the use of Google and Stripe services, certain personal data may be transferred to countries outside the European Economic Area (EEA), including the United States of America.</p><p>Such transfers take place only where the applicable GDPR requirements are met, for example, on the basis of an adequacy decision or other appropriate safeguards.</p><p>The specific transfer mechanisms depend on the current contractual terms and configuration of the services used.</p><p><br></p><h3>8. Data Retention Periods</h3><p>We retain personal data only for as long as necessary for the relevant purpose:</p><p></p><ul><li>Order information: For the period necessary to fulfil contractual and legal obligations and protect legal claims.</li><li>Accounting and tax documents: For the period required by applicable legislation.</li><li>Contact form submissions: For the period necessary to handle the request and, where applicable, protect legal claims.</li><li>Analytics data: In accordance with the data retention settings in Google Analytics and the lifespan of individual cookies.</li><li>The specific retention period for analytics data will be stated once the service configuration has been verified.</li></ul><p></p><p><br></p><h3>9. Rights of Data Subjects</h3><p>In accordance with the GDPR, and subject to the applicable conditions, you have the right to:</p><p></p><ul><li>Request access to your personal data.</li><li>Request the correction of inaccurate or incomplete personal data.</li><li>Request the erasure of your personal data.</li><li>Request restriction of processing.</li><li>Object to processing based on legitimate interests.</li><li>Request data portability in cases provided for by the GDPR.</li><li>Withdraw your consent at any time where processing is based on consent.</li><li>To exercise your rights, you may contact the Data Controller using the email address provided above.</li></ul><p></p><p>You also have the right to lodge a complaint with the Czech Office for Personal Data Protection (Úřad pro ochranu osobních údajů) at www.uoou.gov.cz.</p><p><br></p><h3>10. Personal Data Security</h3><p>The Data Controller implements appropriate technical and organisational measures to protect personal data against unauthorised access, loss, misuse, or unauthorised modification.</p><p>Access to personal data is restricted to authorised persons and service providers who require such access to perform their duties.</p><p><br></p><h3>11. Changes to This Privacy Policy</h3><p>The Data Controller may update this Privacy Policy, particularly in response to changes in legislation, services provided, or personal data processing practices.</p><p>The current version of this document will be available on the website.</p><p>Previous versions may be retained for the purpose of demonstrating which information was applicable during a particular period.</p>', 'aa2264c2757055b510d460bbb0f82f3e757b97ad820c2989b4540d76720c1120', 'archived', '2026-10-21 11:27:19', '2026-10-21 11:27:19', 1, 1, '2026-10-21 11:26:43'),
(11, 1, 'en', 4, NULL, 'Privacy Policy', '<h3>1. Data Controller</h3><p>The controller of personal data is:<br>Name / Business Name:<span class=\"rac-text-orange\"> Mgr. Pavla Kovalovská</span><br>Registered Office / Business Address: Včelařská 2003/19, 733 01 Karviná, Czech Republic<br>Contact Email: <span class=\"rac-text-orange\">kovalovska.pavla@seznam.cz</span><br>The Data Controller processes personal data in accordance with Regulation (EU) 2016/679 of the European Parliament and of the Council (General Data Protection Regulation – GDPR) and other applicable legislation.</p><p><br></p><h3>2. What Personal Data We Process</h3><p>In connection with the operation of our website and online store, we may process the following personal data:</p><p></p><ul><li>Customer\'s first and last name.</li><li>Email address and telephone number.</li><li>Billing and delivery addresses.</li><li>Information about orders, purchased products, and their prices.</li><li>Payment status information and transaction identifiers.</li><li>Information submitted through the contact form.</li><li>Technical data related to website operation and security.</li><li>Website traffic data collected through Google Analytics, provided that you have consented to analytics tracking.</li></ul><p></p><h3>3. Purposes and Legal Bases for Processing</h3><p>We process personal data for the following purposes:</p><p><b>Order Processing and Product Delivery</b>: We use personal data to receive and process orders, process payments, deliver printed books, provide access to e-books and audiobooks, and communicate with customers regarding their orders.</p><p>The legal basis for this processing is the performance of a contract pursuant to Article 6(1)(b) of the GDPR.</p><p><b>Compliance with Legal Obligations</b>: We process personal data to the extent necessary to maintain accounting and tax records and fulfil other statutory obligations.</p><p>The legal basis for this processing is Article 6(1)(c) of the GDPR.</p><p><b>Customer Communication</b>: We use information submitted through the contact form to respond to enquiries and handle requests.</p><p>Depending on the nature of the communication, the legal basis is the performance of a contract, taking steps prior to entering into a contract, or the legitimate interests of the Data Controller.</p><p><b>Protection of Rights and Website Security</b>: We may process necessary personal data to prevent website misuse, address security incidents, and protect legal claims.</p><p>The legal basis for this processing is legitimate interests pursuant to Article 6(1)(f) of the GDPR.</p><p><b>Website Traffic Analysis</b>: We use Google Analytics 4 to statistically evaluate website traffic. Analytics cookies are used only with the visitor\'s consent.</p><p>The legal basis for processing personal data through analytics cookies is consent pursuant to Article 6(1)(a) of the GDPR.</p><p><br></p><h3>4. Payments and Delivery</h3><p>Payments made through the online store are processed using Stripe.</p><p>For payment processing purposes, necessary order and payment information may be shared with the payment service provider.</p><p>Payment card details are processed through the payment service provider\'s secure payment environment.</p><p>When printed books are ordered, the necessary delivery information is provided to the relevant shipping carrier to the extent required for delivery.</p><p><br></p><h3>5. Google Analytics and Cookies</h3><p>Our website uses Google Analytics 4, a service provided by Google, to analyse website traffic and improve website content.</p><p></p><ul><li>This service may process information about visited pages, user interactions, device types, browsers, approximate geographical locations, and identifiers associated with analytics cookies.</li><li>Analytics cookies are activated only after the visitor has given consent.</li><li>Refusing analytics cookies does not restrict the ability to browse the website or purchase products.</li><li>Consent may be withdrawn at any time through the \"Cookie Settings\" link available on the website.</li><li>Strictly necessary cookies may be used without consent where they are genuinely required for website functionality, such as shopping cart operations or security.</li></ul><p></p><p><br></p><h3>6. Who May Have Access to Personal Data</h3><p>Personal data may be disclosed, to the extent necessary, to:</p><p></p><ul><li>Website hosting and technical administration providers.</li><li>Stripe, our payment service provider.</li><li>Shipping carriers responsible for delivering orders.</li><li>Google Analytics service providers.</li><li>Email service providers and other necessary technical service providers.</li><li>Accountants or other authorised persons, where required.</li><li>Public authorities where disclosure is required by law.</li></ul><p></p><p><br></p><h3>7. Transfers of Personal Data Outside the European Economic Area</h3><p>In connection with the use of Google and Stripe services, certain personal data may be transferred to countries outside the European Economic Area (EEA), including the United States of America.</p><p>Such transfers take place only where the applicable GDPR requirements are met, for example, on the basis of an adequacy decision or other appropriate safeguards.</p><p>The specific transfer mechanisms depend on the current contractual terms and configuration of the services used.</p><p><br></p><h3>8. Data Retention Periods</h3><p>We retain personal data only for as long as necessary for the relevant purpose:</p><p></p><ul><li>Order information: For the period necessary to fulfil contractual and legal obligations and protect legal claims.</li><li>Accounting and tax documents: For the period required by applicable legislation.</li><li>Contact form submissions: For the period necessary to handle the request and, where applicable, protect legal claims.</li><li>Analytics data: In accordance with the data retention settings in Google Analytics and the lifespan of individual cookies.</li><li>The specific retention period for analytics data will be stated once the service configuration has been verified.</li></ul><p></p><p><br></p><h3>9. Rights of Data Subjects</h3><p>In accordance with the GDPR, and subject to the applicable conditions, you have the right to:</p><p></p><ul><li>Request access to your personal data.</li><li>Request the correction of inaccurate or incomplete personal data.</li><li>Request the erasure of your personal data.</li><li>Request restriction of processing.</li><li>Object to processing based on legitimate interests.</li><li>Request data portability in cases provided for by the GDPR.</li><li>Withdraw your consent at any time where processing is based on consent.</li><li>To exercise your rights, you may contact the Data Controller using the email address provided above.</li></ul><p></p><p>You also have the right to lodge a complaint with the Czech Office for Personal Data Protection (Úřad pro ochranu osobních údajů) at www.uoou.gov.cz.</p><p><br></p><h3>10. Personal Data Security</h3><p>The Data Controller implements appropriate technical and organisational measures to protect personal data against unauthorised access, loss, misuse, or unauthorised modification.</p><p>Access to personal data is restricted to authorised persons and service providers who require such access to perform their duties.</p><p><br></p><h3>11. Changes to This Privacy Policy</h3><p>The Data Controller may update this Privacy Policy, particularly in response to changes in legislation, services provided, or personal data processing practices.</p><p>The current version of this document will be available on the website.</p><p>Previous versions may be retained for the purpose of demonstrating which information was applicable during a particular period.</p>', '3bf8aab057e99382673755f95e64f683a235082838c07c992f797eb61711c594', 'published', '2026-10-21 11:28:38', '2026-10-21 11:28:38', 1, 1, '2026-10-21 11:27:32'),
(14, 1, 'cs', 8, NULL, 'GDPR', '<p></p><h3>1. Správce osobních údajů</h3>Správcem osobních údajů je:<br>Jméno:&nbsp;&nbsp;<b><span class=\"rac-text-orange\">Mgr. Pavla Kovalovská</span></b><br>Sídlo: Včelařská 2003/19, 73301 Karviná.<br>Kontaktní e-mail: <span class=\"rac-text-orange\"><b>kovalovska.pavla@seznam.cz</b></span><br>Správce zpracovává osobní údaje v souladu s nařízením Evropského parlamentu a Rady (EU) 2016/679 (GDPR) a souvisejícími právními předpisy.<p></p><p></p><h3>2. Jaké osobní údaje zpracováváme</h3>V souvislosti s provozem webových stránek a internetového obchodu můžeme zpracovávat:<br><ul><li>Jméno a příjmení zákazníka.</li><li>E-mailovou adresu a telefonní číslo.</li><li>Fakturační a doručovací adresu.</li><li>Informace o objednávkách, zakoupených produktech a jejich cenách.</li><li>Údaje o stavu platby a transakční identifikátory.</li><li>Informace poskytnuté prostřednictvím kontaktního formuláře.</li><li>Technické údaje související s provozem webu a zabezpečením.</li><li>Údaje o návštěvnosti získané prostřednictvím Google Analytics, pokud k analytickému měření udělíte souhlas.</li></ul><p></p><p></p><h3>3. Účely a právní základy zpracování</h3>Osobní údaje zpracováváme pro následující účely:<p><b>Vyřízení objednávek a dodání produktů</b>:&nbsp;Údaje využíváme k přijetí objednávky, zpracování platby, doručení tištěných knih, zpřístupnění elektronických knih a audioknih a komunikaci o objednávce.<br>Právním základem je plnění smlouvy podle čl. 6 odst. 1 písm. b) GDPR.<b>Plnění zákonných povinností</b> :&nbsp;Údaje zpracováváme v rozsahu nezbytném pro vedení účetní a daňové evidence a plnění dalších zákonných povinností.<br>Právním základem je čl. 6 odst. 1 písm. c) GDPR.<br><b>Zákaznická komunikace</b>: Údaje získané prostřednictvím kontaktního formuláře využíváme pro zodpovězení dotazů a vyřízení požadavků. Právním základem je podle povahy komunikace plnění smlouvy, provedení opatření před jejím uzavřením nebo oprávněný zájem správce.</p><p><b>Ochrana práv a zabezpečení webu</b>:&nbsp;Nezbytné údaje můžeme zpracovávat za účelem prevence zneužití webových stránek, řešení bezpečnostních incidentů a ochrany právních nároků.<br>Právním základem je oprávněný zájem podle čl. 6 odst. 1 písm. f) GDPR.<br><b>Analýza návštěvnosti</b>: Pro statistické vyhodnocování návštěvnosti využíváme Google Analytics 4. Analytické cookies jsou používány pouze na základě souhlasu návštěvníka.<br>Právním základem zpracování údajů prostřednictvím analytických cookies je souhlas podle čl. 6 odst. 1 písm. a) GDPR.</p><p></p><p></p><h3>4. Platby a doručování</h3>Platby v internetovém obchodě jsou zpracovávány prostřednictvím služby Stripe.<br>Za účelem provedení platby mohou být poskytovateli platebních služeb předány nezbytné údaje o objednávce a platbě. Údaje o platební kartě jsou zpracovávány prostřednictvím zabezpečeného platebního prostředí poskytovatele platebních služeb.<br>Při objednání tištěných knih jsou potřebné doručovací údaje předávány příslušnému dopravci v rozsahu nezbytném pro doručení zásilky.<p></p><p></p><h3>5. Google Analytics a cookies</h3>Webové stránky využívají Google Analytics 4, službu společnosti Google, pro vyhodnocování návštěvnosti a zlepšování obsahu webu.<br>Tato služba může zpracovávat údaje o navštívených stránkách, interakcích, typu zařízení, prohlížeči, přibližné geografické poloze a identifikátorech spojených s analytickými cookies.<br>Analytické cookies se aktivují pouze po udělení souhlasu návštěvníka.<br>Odmítnutí analytických cookies nijak neomezuje možnost prohlížení webových stránek nebo nákupu produktů.<br>Udělený souhlas je možné kdykoli odvolat prostřednictvím odkazu „Nastavení cookies“ dostupného na webových stránkách.<br>Nezbytné cookies mohou být používány bez souhlasu, pokud jsou skutečně potřebné pro fungování webu, například košíku nebo zabezpečení.<p></p><p></p><h3>6. Komu mohou být údaje zpřístupněny</h3>Osobní údaje mohou být v nezbytném rozsahu zpřístupněny:<br><ul><li>Poskytovateli webhostingu a technické správy.</li><li>Poskytovateli platebních služeb Stripe.</li><li>Dopravcům zajišťujícím doručení zásilek.</li><li>Poskytovateli služby Google Analytics.</li><li>Poskytovatelům e-mailových a dalších nezbytných technických služeb.</li><li>Účetním nebo jiným oprávněným osobám, pokud je to potřebné.</li><li>Orgánům veřejné moci, pokud tak stanoví právní předpis.</li></ul><p></p><p></p><h3>7. Předávání údajů mimo Evropský hospodářský prostor</h3>V souvislosti s využíváním služeb Google a Stripe může docházet k předávání některých osobních údajů do zemí mimo Evropský hospodářský prostor, včetně Spojených států amerických.<br>Předávání se uskutečňuje pouze při splnění příslušných podmínek GDPR, například na základě rozhodnutí o odpovídající ochraně nebo jiných použitelných záruk.<br>Konkrétní mechanismy předávání odpovídají aktuálním smluvním podmínkám a nastavení využívaných služeb.<p></p><p></p><h3>8. Doba uchovávání údajů</h3>Osobní údaje uchováváme pouze po dobu nezbytnou pro příslušný účel:<br><ul><li>Údaje o objednávkách po dobu potřebnou pro plnění smluvních a zákonných povinností a ochranu právních nároků.</li><li>Účetní a daňové doklady po dobu stanovenou příslušnými právními předpisy.</li><li>Údaje z kontaktního formuláře po dobu potřebnou k vyřízení požadavku a případné ochraně právních nároků.</li><li>Analytické údaje podle nastavení uchovávání v Google Analytics a doby platnosti jednotlivých cookies.</li></ul>Konkrétní nastavení doby uchovávání analytických údajů bude uvedeno po ověření konfigurace služby.<p></p><p></p><h3>9. Práva subjektů údajů</h3>V souladu s GDPR máte za příslušných podmínek právo:<br><ul><li>Požadovat přístup ke svým osobním údajům.</li><li>Požadovat opravu nepřesných nebo neúplných údajů.</li><li>Požadovat výmaz osobních údajů.</li><li>Požadovat omezení zpracování.</li><li>Vznést námitku proti zpracování založenému na oprávněném zájmu.</li><li>Požadovat přenositelnost údajů v případech stanovených GDPR.</li><li>Kdykoli odvolat souhlas, pokud je zpracování založeno na souhlasu.</li><li>Pro uplatnění svých práv můžete kontaktovat správce prostřednictvím výše uvedeného e-mailu.</li><li>Máte rovněž právo podat stížnost u Úřadu pro ochranu osobních údajů (www.uoou.gov.cz).</li></ul><p></p><p></p><h3>10. Zabezpečení osobních údajů</h3>Správce přijímá přiměřená technická a organizační opatření k ochraně osobních údajů před neoprávněným přístupem, ztrátou, zneužitím nebo neoprávněnou změnou.<br>Přístup k údajům je omezen na oprávněné osoby a poskytovatele služeb, kteří je potřebují pro plnění svých úkolů.<p></p><p></p><h3>11. Změny těchto zásad</h3>Správce může tyto zásady aktualizovat, zejména v souvislosti se změnami právních předpisů, poskytovaných služeb nebo způsobů zpracování osobních údajů.<br>Aktuální verze dokumentu bude dostupná na webových stránkách. Dřívější verze mohou být uchovávány pro účely doložení informací platných v konkrétním období.<p></p><p></p><p></p>', '336dc96806a6910ab95a4c2fa74aa2f4e7b94448c7f5cc8c56ff04ec23036d25', 'archived', '2026-10-27 17:50:16', '2026-10-27 17:50:16', 1, 1, '2026-10-27 17:49:11'),
(16, 2, 'cs', 1, NULL, 'Cookies', '<h4>Zásady používání souborů cookies E-shopu pavlakovalovska.eu</h4>Účinnost od: 27. 10. 2026<br><h4>1. Úvodní ustanovení</h4>Tyto zásady vysvětlují, jakým způsobem internetový obchod RAC (dále jen „e-shop“) používá soubory cookies a obdobné technologie, k jakým účelům slouží a jak může návštěvník jejich používání ovlivnit.<br>Ochrana soukromí našich zákazníků a návštěvníků je pro nás důležitá. Používáme proto pouze takové technologie, které jsou potřebné pro provoz e-shopu nebo jejichž používání návštěvník výslovně povolil.<br><br>Provozovatelem e-shopu a správcem případných osobních údajů zpracovávaných prostřednictvím cookies je:<br>Jméno:&nbsp;<span class=\"rac-text-orange\"> Mgr. Pavla Kovalovská</span><br>Sídlo: Včelařská 2003/19, 73301 Karviná.<br>Kontaktní e-mail: <span class=\"rac-text-orange\">kovalovska.pavla@seznam.cz</span><br><h4>2. Co jsou soubory cookies?</h4>Cookies jsou malé textové soubory, které webové stránky ukládají prostřednictvím internetového prohlížeče do zařízení návštěvníka, například počítače, tabletu nebo mobilního telefonu.<br>Pomáhají zajistit správné fungování internetových stránek, uchovávat některá uživatelská nastavení nebo získávat informace o způsobu využívání webu.<br>Vedle cookies mohou být využívány také obdobné technologie, například místní úložiště prohlížeče (localStorage).<br><h4>3. Jaké druhy cookies používáme?</h4><b>3.1 Nezbytné (technické) cookies</b><br>Tyto cookies jsou potřebné pro správné fungování e-shopu a jeho základních funkcí.<br>Mohou zajišťovat zejména:<br><ul><li>uchování obsahu nákupního košíku;</li><li>fungování objednávkového procesu;</li><li>zabezpečení webových stránek;</li><li>správu uživatelské relace;</li><li>uchování jazykového nastavení;</li><li>zapamatování nastavení souhlasu s cookies.</li></ul>Pro používání nezbytných cookies není vyžadován předchozí souhlas návštěvníka, pokud jsou skutečně využívány pouze pro nezbytné technické účely.<br><br><b>3.2 Analytické cookies</b><br>Analytické cookies umožňují získávat informace o návštěvnosti e-shopu a způsobu, jakým návštěvníci používají jeho jednotlivé stránky.<br>Pomáhají nám například vyhodnocovat návštěvnost, rozpoznávat technické problémy a zlepšovat přehlednost a použitelnost e-shopu.<br>Pokud používáme analytické cookies, které nejsou technicky nezbytné, aktivujeme je pouze na základě předchozího souhlasu návštěvníka.<br><br><b>3.3 Marketingové cookies</b><br>Marketingové cookies mohou sloužit k měření účinnosti reklamních kampaní, vyhodnocování zájmu návštěvníků o nabízené produkty a případně k zobrazování relevantní reklamy.<br>Tyto cookies mohou být poskytovány také externími reklamními nebo marketingovými službami.<br>Marketingové cookies používáme pouze tehdy, pokud jsou na e-shopu příslušné služby aktivovány a návštěvník s jejich používáním předem souhlasil.<br><h4>4. Právní základ používání cookies</h4>Nezbytné technické cookies používáme pro zajištění provozu a základních funkcí e-shopu v souladu se zákonem č. 127/2005 Sb., o elektronických komunikacích.<br>Pokud prostřednictvím těchto cookies dochází ke zpracování osobních údajů, je právním základem zpravidla oprávněný zájem provozovatele na bezpečném a funkčním provozu e-shopu, případně nezbytnost zpracování pro plnění smlouvy.<br>Analytické, marketingové a jiné technicky nezbytné cookies používáme pouze po získání souhlasu návštěvníka. Pokud jejich prostřednictvím zpracováváme osobní údaje na základě souhlasu, postupujeme podle čl. 6 odst. 1 písm. a) GDPR.<br><h4>5. Správa a nastavení souhlasu</h4>Při návštěvě e-shopu může být návštěvníkovi zobrazena informační lišta umožňující rozhodnout o používání volitelných cookies.<br>Návštěvník může:<br><ul><li>přijmout všechny nabízené kategorie cookies;</li><li>odmítnout všechny volitelné cookies;</li><li>upravit souhlas podle jednotlivých kategorií.</li><li>Nezbytné technické cookies zůstávají aktivní, protože umožňují základní fungování e-shopu.</li><li>Odmítnutí volitelných cookies nemá vliv na možnost prohlížet nabídku, vkládat produkty do nákupního košíku ani dokončit objednávku.</li><li>Souhlas je dobrovolný a lze jej kdykoliv změnit nebo odvolat prostřednictvím odkazu Nastavení cookies, který je dostupný na stránkách e-shopu.</li><li>Odvolání souhlasu nemá vliv na zákonnost zpracování provedeného před jeho odvoláním.</li></ul><h4>6. Doba uchovávání cookies</h4>Doba uchovávání cookies závisí na jejich účelu a technickém nastavení.<br>Relační cookies se zpravidla uchovávají pouze po dobu návštěvy webových stránek nebo trvání příslušné relace.<br>Trvalé cookies mohou být uchovávány po předem stanovenou dobu nebo do jejich odstranění uživatelem.<br>Konkrétní přehled používaných cookies, jejich poskytovatelů, účelů a doby uchovávání je uveden v aktuálním seznamu cookies zveřejněném na e-shopu.<br>Cookies nepoužíváme po dobu delší, než je potřebná pro jejich stanovený účel.<br><h4>7. Cookies třetích stran</h4>Některé funkce e-shopu mohou využívat služby externích poskytovatelů, například provozovatelů platebních, analytických nebo bezpečnostních technologií.<br>Pokud tyto služby používají soubory cookies, může za jejich prostřednictvím prováděné zpracování údajů odpovídat také příslušný poskytovatel.<br>Informace o skutečně zapojených poskytovatelích a jejich cookies jsou uvedeny v přehledu používaných cookies.<br>Případné předávání osobních údajů mimo Evropský hospodářský prostor probíhá pouze při splnění požadavků platných právních předpisů o ochraně osobních údajů.<br><h4>8. Jak odstranit nebo zablokovat cookies?</h4>Návštěvník může soubory cookies spravovat také prostřednictvím nastavení svého internetového prohlížeče.<br>Většina prohlížečů umožňuje cookies zobrazit, odstranit nebo jejich ukládání omezit.<br>Je však třeba upozornit, že úplné zablokování nezbytných technických cookies může způsobit nesprávné fungování některých funkcí e-shopu, zejména nákupního košíku a objednávkového procesu.<br><h4>9. Ochrana osobních údajů a práva návštěvníků</h4>Pokud při používání cookies dochází ke zpracování osobních údajů, má návštěvník práva stanovená obecným nařízením o ochraně osobních údajů (GDPR).<br>Za podmínek stanovených GDPR se může jednat zejména o právo na přístup k osobním údajům, jejich opravu, výmaz, omezení zpracování, přenositelnost údajů a právo vznést námitku proti zpracování.<br>Návštěvník může rovněž kdykoliv odvolat udělený souhlas a má právo podat stížnost u Úřadu pro ochranu osobních údajů (www.uoou.gov.cz).<br>Podrobnější informace o zpracování osobních údajů jsou uvedeny v samostatném dokumentu Zásady ochrany osobních údajů, dostupném na stránkách e-shopu.<br><h4>10. Změny těchto zásad</h4>Provozovatel si vyhrazuje právo tyto zásady přiměřeně aktualizovat, zejména v případě změn používaných technologií, poskytovatelů služeb nebo právních předpisů.<br>Aktuální znění je vždy zveřejněno na stránkách e-shopu.<br>Pokud změna ovlivní rozsah nebo účel použití cookies vyžadujících souhlas, bude si provozovatel před jejich použitím vyžadovat nový souhlas návštěvníka, je-li to právně nezbytné.<br><br>Poslední aktualizace: <span class=\"rac-text-orange\">26. 10. 2026</span><p></p>', '8fb988d0d7560555ed1b20fad7a102f5c3950ecadbdb33c7d4c22202ffd7e94c', 'published', '2026-10-27 17:56:31', '2026-10-27 17:56:31', 1, 1, '2026-10-27 17:54:49');
INSERT INTO `rac_legal_document_versions` (`id`, `document_id`, `language_code`, `version_number`, `version_label`, `title`, `content_html`, `content_sha256`, `status`, `effective_from`, `published_at`, `published_by`, `created_by`, `created_at`) VALUES
(17, 1, 'cs', 9, NULL, 'GDPR', '<p></p><h3>1. Správce osobních údajů</h3>Správcem osobních údajů je:<br>Jméno:<b><span class=\"rac-text-orange\">Mgr. Pavla Kovalovská</span></b><br>Sídlo: Včelařská 2003/19, 73301 Karviná.<br>Kontaktní e-mail: <span class=\"rac-text-orange\"><b>kovalovska.pavla@seznam.cz</b></span><br>Správce zpracovává osobní údaje v souladu s nařízením Evropského parlamentu a Rady (EU) 2016/679 (GDPR) a souvisejícími právními předpisy.<p></p><p></p><h3>2. Jaké osobní údaje zpracováváme</h3>V souvislosti s provozem webových stránek a internetového obchodu můžeme zpracovávat:<br><ul><li>Jméno a příjmení zákazníka.</li><li>E-mailovou adresu a telefonní číslo.</li><li>Fakturační a doručovací adresu.</li><li>Informace o objednávkách, zakoupených produktech a jejich cenách.</li><li>Údaje o stavu platby a transakční identifikátory.</li><li>Informace poskytnuté prostřednictvím kontaktního formuláře.</li><li>Technické údaje související s provozem webu a zabezpečením.</li><li>Údaje o návštěvnosti získané prostřednictvím Google Analytics, pokud k analytickému měření udělíte souhlas.</li></ul><p></p><p></p><h3>3. Účely a právní základy zpracování</h3>Osobní údaje zpracováváme pro následující účely:<p><b>Vyřízení objednávek a dodání produktů</b>:&nbsp;Údaje využíváme k přijetí objednávky, zpracování platby, doručení tištěných knih, zpřístupnění elektronických knih a audioknih a komunikaci o objednávce.<br>Právním základem je plnění smlouvy podle čl. 6 odst. 1 písm. b) GDPR.<b>Plnění zákonných povinností</b> :&nbsp;Údaje zpracováváme v rozsahu nezbytném pro vedení účetní a daňové evidence a plnění dalších zákonných povinností.<br>Právním základem je čl. 6 odst. 1 písm. c) GDPR.<br><b>Zákaznická komunikace</b>: Údaje získané prostřednictvím kontaktního formuláře využíváme pro zodpovězení dotazů a vyřízení požadavků. Právním základem je podle povahy komunikace plnění smlouvy, provedení opatření před jejím uzavřením nebo oprávněný zájem správce.</p><p><b>Ochrana práv a zabezpečení webu</b>:&nbsp;Nezbytné údaje můžeme zpracovávat za účelem prevence zneužití webových stránek, řešení bezpečnostních incidentů a ochrany právních nároků.<br>Právním základem je oprávněný zájem podle čl. 6 odst. 1 písm. f) GDPR.<br><b>Analýza návštěvnosti</b>: Pro statistické vyhodnocování návštěvnosti využíváme Google Analytics 4. Analytické cookies jsou používány pouze na základě souhlasu návštěvníka.<br>Právním základem zpracování údajů prostřednictvím analytických cookies je souhlas podle čl. 6 odst. 1 písm. a) GDPR.</p><p></p><p></p><h3>4. Platby a doručování</h3>Platby v internetovém obchodě jsou zpracovávány prostřednictvím služby Stripe.<br>Za účelem provedení platby mohou být poskytovateli platebních služeb předány nezbytné údaje o objednávce a platbě. Údaje o platební kartě jsou zpracovávány prostřednictvím zabezpečeného platebního prostředí poskytovatele platebních služeb.<br>Při objednání tištěných knih jsou potřebné doručovací údaje předávány příslušnému dopravci v rozsahu nezbytném pro doručení zásilky.<p></p><p></p><h3>5. Google Analytics a cookies</h3>Webové stránky využívají Google Analytics 4, službu společnosti Google, pro vyhodnocování návštěvnosti a zlepšování obsahu webu.<br>Tato služba může zpracovávat údaje o navštívených stránkách, interakcích, typu zařízení, prohlížeči, přibližné geografické poloze a identifikátorech spojených s analytickými cookies.<br>Analytické cookies se aktivují pouze po udělení souhlasu návštěvníka.<br>Odmítnutí analytických cookies nijak neomezuje možnost prohlížení webových stránek nebo nákupu produktů.<br>Udělený souhlas je možné kdykoli odvolat prostřednictvím odkazu „Nastavení cookies“ dostupného na webových stránkách.<br>Nezbytné cookies mohou být používány bez souhlasu, pokud jsou skutečně potřebné pro fungování webu, například košíku nebo zabezpečení.<p></p><p></p><h3>6. Komu mohou být údaje zpřístupněny</h3>Osobní údaje mohou být v nezbytném rozsahu zpřístupněny:<br><ul><li>Poskytovateli webhostingu a technické správy.</li><li>Poskytovateli platebních služeb Stripe.</li><li>Dopravcům zajišťujícím doručení zásilek.</li><li>Poskytovateli služby Google Analytics.</li><li>Poskytovatelům e-mailových a dalších nezbytných technických služeb.</li><li>Účetním nebo jiným oprávněným osobám, pokud je to potřebné.</li><li>Orgánům veřejné moci, pokud tak stanoví právní předpis.</li></ul><p></p><p></p><h3>7. Předávání údajů mimo Evropský hospodářský prostor</h3>V souvislosti s využíváním služeb Google a Stripe může docházet k předávání některých osobních údajů do zemí mimo Evropský hospodářský prostor, včetně Spojených států amerických.<br>Předávání se uskutečňuje pouze při splnění příslušných podmínek GDPR, například na základě rozhodnutí o odpovídající ochraně nebo jiných použitelných záruk.<br>Konkrétní mechanismy předávání odpovídají aktuálním smluvním podmínkám a nastavení využívaných služeb.<p></p><p></p><h3>8. Doba uchovávání údajů</h3>Osobní údaje uchováváme pouze po dobu nezbytnou pro příslušný účel:<br><ul><li>Údaje o objednávkách po dobu potřebnou pro plnění smluvních a zákonných povinností a ochranu právních nároků.</li><li>Účetní a daňové doklady po dobu stanovenou příslušnými právními předpisy.</li><li>Údaje z kontaktního formuláře po dobu potřebnou k vyřízení požadavku a případné ochraně právních nároků.</li><li>Analytické údaje podle nastavení uchovávání v Google Analytics a doby platnosti jednotlivých cookies.</li></ul>Konkrétní nastavení doby uchovávání analytických údajů bude uvedeno po ověření konfigurace služby.<p></p><p></p><h3>9. Práva subjektů údajů</h3>V souladu s GDPR máte za příslušných podmínek právo:<br><ul><li>Požadovat přístup ke svým osobním údajům.</li><li>Požadovat opravu nepřesných nebo neúplných údajů.</li><li>Požadovat výmaz osobních údajů.</li><li>Požadovat omezení zpracování.</li><li>Vznést námitku proti zpracování založenému na oprávněném zájmu.</li><li>Požadovat přenositelnost údajů v případech stanovených GDPR.</li><li>Kdykoli odvolat souhlas, pokud je zpracování založeno na souhlasu.</li><li>Pro uplatnění svých práv můžete kontaktovat správce prostřednictvím výše uvedeného e-mailu.</li><li>Máte rovněž právo podat stížnost u Úřadu pro ochranu osobních údajů (www.uoou.gov.cz).</li></ul><p></p><p></p><h3>10. Zabezpečení osobních údajů</h3>Správce přijímá přiměřená technická a organizační opatření k ochraně osobních údajů před neoprávněným přístupem, ztrátou, zneužitím nebo neoprávněnou změnou.<br>Přístup k údajům je omezen na oprávněné osoby a poskytovatele služeb, kteří je potřebují pro plnění svých úkolů.<p></p><p></p><h3>11. Změny těchto zásad</h3>Správce může tyto zásady aktualizovat, zejména v souvislosti se změnami právních předpisů, poskytovaných služeb nebo způsobů zpracování osobních údajů.<br>Aktuální verze dokumentu bude dostupná na webových stránkách. Dřívější verze mohou být uchovávány pro účely doložení informací platných v konkrétním období.<p></p><p></p><p></p>', 'a9221ac107248d2f1d1ab67422a36959802595d005908419b8d22a74b50dfd5c', 'archived', '2026-10-27 18:05:31', '2026-10-27 18:05:31', 1, 1, '2026-10-27 18:04:37'),
(18, 1, 'cs', 10, NULL, 'GDPR', '<p></p><h3>1. Správce osobních údajů</h3>Správcem osobních údajů je:<br>Jméno:&nbsp;<b><span class=\"rac-text-orange\">Mgr. Pavla Kovalovská</span></b><br>Sídlo: Včelařská 2003/19, 73301 Karviná.<br>Kontaktní e-mail: <span class=\"rac-text-orange\"><b>kovalovska.pavla@seznam.cz</b></span><br>Správce zpracovává osobní údaje v souladu s nařízením Evropského parlamentu a Rady (EU) 2016/679 (GDPR) a souvisejícími právními předpisy.<p></p><p></p><h3>2. Jaké osobní údaje zpracováváme</h3>V souvislosti s provozem webových stránek a internetového obchodu můžeme zpracovávat:<br><ul><li>Jméno a příjmení zákazníka.</li><li>E-mailovou adresu a telefonní číslo.</li><li>Fakturační a doručovací adresu.</li><li>Informace o objednávkách, zakoupených produktech a jejich cenách.</li><li>Údaje o stavu platby a transakční identifikátory.</li><li>Informace poskytnuté prostřednictvím kontaktního formuláře.</li><li>Technické údaje související s provozem webu a zabezpečením.</li><li>Údaje o návštěvnosti získané prostřednictvím Google Analytics, pokud k analytickému měření udělíte souhlas.</li></ul><p></p><p></p><h3>3. Účely a právní základy zpracování</h3>Osobní údaje zpracováváme pro následující účely:<p><b>Vyřízení objednávek a dodání produktů</b>:&nbsp;Údaje využíváme k přijetí objednávky, zpracování platby, doručení tištěných knih, zpřístupnění elektronických knih a audioknih a komunikaci o objednávce.<br>Právním základem je plnění smlouvy podle čl. 6 odst. 1 písm. b) GDPR.<b>Plnění zákonných povinností</b> :&nbsp;Údaje zpracováváme v rozsahu nezbytném pro vedení účetní a daňové evidence a plnění dalších zákonných povinností.<br>Právním základem je čl. 6 odst. 1 písm. c) GDPR.<br><b>Zákaznická komunikace</b>: Údaje získané prostřednictvím kontaktního formuláře využíváme pro zodpovězení dotazů a vyřízení požadavků. Právním základem je podle povahy komunikace plnění smlouvy, provedení opatření před jejím uzavřením nebo oprávněný zájem správce.</p><p><b>Ochrana práv a zabezpečení webu</b>:&nbsp;Nezbytné údaje můžeme zpracovávat za účelem prevence zneužití webových stránek, řešení bezpečnostních incidentů a ochrany právních nároků.<br>Právním základem je oprávněný zájem podle čl. 6 odst. 1 písm. f) GDPR.<br><b>Analýza návštěvnosti</b>: Pro statistické vyhodnocování návštěvnosti využíváme Google Analytics 4. Analytické cookies jsou používány pouze na základě souhlasu návštěvníka.<br>Právním základem zpracování údajů prostřednictvím analytických cookies je souhlas podle čl. 6 odst. 1 písm. a) GDPR.</p><p></p><p></p><h3>4. Platby a doručování</h3>Platby v internetovém obchodě jsou zpracovávány prostřednictvím služby Stripe.<br>Za účelem provedení platby mohou být poskytovateli platebních služeb předány nezbytné údaje o objednávce a platbě. Údaje o platební kartě jsou zpracovávány prostřednictvím zabezpečeného platebního prostředí poskytovatele platebních služeb.<br>Při objednání tištěných knih jsou potřebné doručovací údaje předávány příslušnému dopravci v rozsahu nezbytném pro doručení zásilky.<p></p><p></p><h3>5. Google Analytics a cookies</h3>Webové stránky využívají Google Analytics 4, službu společnosti Google, pro vyhodnocování návštěvnosti a zlepšování obsahu webu.<br>Tato služba může zpracovávat údaje o navštívených stránkách, interakcích, typu zařízení, prohlížeči, přibližné geografické poloze a identifikátorech spojených s analytickými cookies.<br>Analytické cookies se aktivují pouze po udělení souhlasu návštěvníka.<br>Odmítnutí analytických cookies nijak neomezuje možnost prohlížení webových stránek nebo nákupu produktů.<br>Udělený souhlas je možné kdykoli odvolat prostřednictvím odkazu „Nastavení cookies“ dostupného na webových stránkách.<br>Nezbytné cookies mohou být používány bez souhlasu, pokud jsou skutečně potřebné pro fungování webu, například košíku nebo zabezpečení.<p></p><p></p><h3>6. Komu mohou být údaje zpřístupněny</h3>Osobní údaje mohou být v nezbytném rozsahu zpřístupněny:<br><ul><li>Poskytovateli webhostingu a technické správy.</li><li>Poskytovateli platebních služeb Stripe.</li><li>Dopravcům zajišťujícím doručení zásilek.</li><li>Poskytovateli služby Google Analytics.</li><li>Poskytovatelům e-mailových a dalších nezbytných technických služeb.</li><li>Účetním nebo jiným oprávněným osobám, pokud je to potřebné.</li><li>Orgánům veřejné moci, pokud tak stanoví právní předpis.</li></ul><p></p><p></p><h3>7. Předávání údajů mimo Evropský hospodářský prostor</h3>V souvislosti s využíváním služeb Google a Stripe může docházet k předávání některých osobních údajů do zemí mimo Evropský hospodářský prostor, včetně Spojených států amerických.<br>Předávání se uskutečňuje pouze při splnění příslušných podmínek GDPR, například na základě rozhodnutí o odpovídající ochraně nebo jiných použitelných záruk.<br>Konkrétní mechanismy předávání odpovídají aktuálním smluvním podmínkám a nastavení využívaných služeb.<p></p><p></p><h3>8. Doba uchovávání údajů</h3>Osobní údaje uchováváme pouze po dobu nezbytnou pro příslušný účel:<br><ul><li>Údaje o objednávkách po dobu potřebnou pro plnění smluvních a zákonných povinností a ochranu právních nároků.</li><li>Účetní a daňové doklady po dobu stanovenou příslušnými právními předpisy.</li><li>Údaje z kontaktního formuláře po dobu potřebnou k vyřízení požadavku a případné ochraně právních nároků.</li><li>Analytické údaje podle nastavení uchovávání v Google Analytics a doby platnosti jednotlivých cookies.</li></ul>Konkrétní nastavení doby uchovávání analytických údajů bude uvedeno po ověření konfigurace služby.<p></p><p></p><h3>9. Práva subjektů údajů</h3>V souladu s GDPR máte za příslušných podmínek právo:<br><ul><li>Požadovat přístup ke svým osobním údajům.</li><li>Požadovat opravu nepřesných nebo neúplných údajů.</li><li>Požadovat výmaz osobních údajů.</li><li>Požadovat omezení zpracování.</li><li>Vznést námitku proti zpracování založenému na oprávněném zájmu.</li><li>Požadovat přenositelnost údajů v případech stanovených GDPR.</li><li>Kdykoli odvolat souhlas, pokud je zpracování založeno na souhlasu.</li><li>Pro uplatnění svých práv můžete kontaktovat správce prostřednictvím výše uvedeného e-mailu.</li><li>Máte rovněž právo podat stížnost u Úřadu pro ochranu osobních údajů (www.uoou.gov.cz).</li></ul><p></p><p></p><h3>10. Zabezpečení osobních údajů</h3>Správce přijímá přiměřená technická a organizační opatření k ochraně osobních údajů před neoprávněným přístupem, ztrátou, zneužitím nebo neoprávněnou změnou.<br>Přístup k údajům je omezen na oprávněné osoby a poskytovatele služeb, kteří je potřebují pro plnění svých úkolů.<p></p><p></p><h3>11. Změny těchto zásad</h3>Správce může tyto zásady aktualizovat, zejména v souvislosti se změnami právních předpisů, poskytovaných služeb nebo způsobů zpracování osobních údajů.<br>Aktuální verze dokumentu bude dostupná na webových stránkách. Dřívější verze mohou být uchovávány pro účely doložení informací platných v konkrétním období.<p></p><p></p><p></p>', 'b62f2a3d7b79a4e33b1f6a71bbcf7077f02d0e0c43f4cb06aefd716653aab8a3', 'published', '2026-10-27 18:05:51', '2026-10-27 18:05:51', 1, 1, '2026-10-27 18:05:39'),
(19, 3, 'cs', 1, NULL, 'Obchodní podmínky', '<h2>OBCHODNÍ PODMÍNKY E-SHOPU <span class=\"rac-text-orange\">pavlakovalovska.eu</span></h2><h4>1. Provozovatel a kontaktní údaje</h4><p>Prodávajícím je: </p><p>Jméno: <span class=\"rac-text-orange\"> Mgr. Pavla Kovalovská</span></p>Sídlo: Včelařská 2003/19, 73301 Karviná.<br>Kontaktní e-mail: <span class=\"rac-text-orange\">kovalovska.pavla@seznam.cz</span><br><h4>2. Nabízené produkty a jazyková vydání</h4>E-shop nabízí tištěné knihy, elektronické knihy a audioknihy. Jednotlivá jazyková vydání a formáty jsou samostatné varianty produktu. Závazné jsou informace o konkrétním vydání, jazyku, ceně a dostupnosti uvedené u produktu před vložením do košíku.<br><h4>3. Objednávka a uzavření smlouvy</h4>Zákazník vybírá konkrétní jazykové vydání, počet kusů a případnou dopravu. Před odesláním objednávky má možnost zkontrolovat zvolený obsah a celkovou cenu. [Doplňte přesné pojmenování tlačítka a okamžik vzniku kupní smlouvy podle skutečného procesu e-shopu.] Přijetí objednávky potvrdíme e-mailem.<br><h4>4. Ceny a platby</h4>Ceny variant jsou stanovovány v CZK včetně platných daní, pokud jsou uplatňovány. Platba probíhá přes platební bránu Stripe. V případě nabídky platby v jiné měně se výsledná částka zobrazí před potvrzením platby v platební bráně; případné přepočty, poplatky a zúčtovací měna se řídí podmínkami konkrétní platby. [Ověřte a doplňte skutečné nastavení Stripe.]<br><h4>5. Doručení tištěných knih</h4>Dostupné způsoby dopravy, cílové země, ceny a případný nárok na dopravu zdarma se zobrazí během objednávky. [Doplňte používané dopravce, obvyklé dodací lhůty a postup při nemožnosti dodání.]<br><h4>6. Dodání e-knih a audioknih</h4>Digitální obsah je zákazníkovi zpřístupněn způsobem popsaným v nabídce a potvrzení objednávky. [Doplňte konkrétní způsob: odkaz ke stažení, zabezpečená sekce, e-mail, dobu dostupnosti, podporované formáty, technická omezení a případná ochranná opatření.] Případný souhlas s okamžitým poskytnutím digitálního obsahu je vyžadován samostatně v objednávce v souladu s právními předpisy.<br><h4>7. Odstoupení od smlouvy</h4>Spotřebitel má v zákonem stanovených případech právo odstoupit od smlouvy uzavřené na dálku zpravidla do 14 dnů. U tištěných knih se uplatní příslušná pravidla pro vrácení zboží a případné zákonné výjimky. U digitálního obsahu poskytovaného bez hmotného nosiče může dojít ke ztrátě práva odstoupit pouze při splnění všech příslušných zákonných podmínek, včetně požadovaného výslovného souhlasu a poučení. [Doplňte adresu pro odstoupení, modelový formulář a způsob vrácení plateb.]<br><h4>8. Práva z vadného plnění a reklamace</h4>Práva spotřebitele z vadného plnění se řídí platnými právními předpisy. Reklamaci lze uplatnit prostřednictvím [kontaktní e-mail / adresa]; zákazník uvede číslo objednávky, popis vady a požadovaný způsob vyřízení. Pro digitální obsah se uplatní zvláštní zákonná pravidla týkající se souladu a aktualizací. [Doplňte reklamační proces a zákonné poučení.]<br><h4>9. Autorská práva k digitálnímu obsahu</h4>Zakoupením digitálního vydání nezískává zákazník autorská majetková práva k dílu. Obsah smí používat v rozsahu platné licence a právních předpisů. [Doplňte konkrétní licenční podmínky a povolené užití.]<br><h4>10. Ochrana osobních údajů a cookies</h4>Informace o zpracování osobních údajů jsou zveřejněny na stránce GDPR a informace o cookies na stránce Cookies.<br><h4>11. Mimosoudní řešení spotřebitelských sporů</h4>Spotřebitel se může v zákonem stanovených případech obrátit na Českou obchodní inspekci (ČOI), případně další příslušný orgán. [Ověřte příslušný orgán a doplňte přesné poučení.]<br><h4>12. Závěrečná ustanovení</h4>Tyto podmínky jsou účinné od [datum]. Pro smluvní vztah je rozhodné znění podmínek poskytnuté zákazníkovi při uzavření smlouvy, při respektování kogentních ustanovení příslušných právních předpisů.<br><br><p></p>', '9f2dd17b0abab2147a5cab3bf7f171bb9905da665e9fe25c2532165b14a303a1', 'published', '2026-10-27 18:46:05', '2026-10-27 18:46:05', 1, 1, '2026-10-27 18:46:02');

-- --------------------------------------------------------

--
-- Struktura tabulky `rac_order_legal_acceptances`
--

CREATE TABLE `rac_order_legal_acceptances` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `legal_version_id` bigint(20) UNSIGNED NOT NULL,
  `acceptance_type` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `language_code` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `accepted` tinyint(1) NOT NULL DEFAULT '1',
  `displayed_text` text COLLATE utf8mb4_unicode_ci,
  `accepted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `shipping_methods`
--

CREATE TABLE `shipping_methods` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('pickup_point','address','personal') COLLATE utf8mb4_unicode_ci NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int(11) NOT NULL DEFAULT '100',
  `max_weight_grams` int(10) UNSIGNED DEFAULT NULL,
  `logo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Vypisuji data pro tabulku `shipping_methods`
--

INSERT INTO `shipping_methods` (`id`, `code`, `type`, `active`, `sort_order`, `max_weight_grams`, `logo_path`, `created_at`, `updated_at`) VALUES
(1, 'na_adresu', 'address', 1, 1, NULL, NULL, '2026-10-09 07:40:22', '2026-10-09 07:46:32'),
(2, 'zasilkovna', 'pickup_point', 0, 2, NULL, NULL, '2026-10-09 07:47:20', '2026-10-14 09:41:11'),
(3, 'osobni_odber', 'personal', 1, 3, NULL, NULL, '2026-10-09 07:47:43', '2026-10-09 09:15:12');

-- --------------------------------------------------------

--
-- Struktura tabulky `shipping_method_translations`
--

CREATE TABLE `shipping_method_translations` (
  `method_id` bigint(20) UNSIGNED NOT NULL,
  `language_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Vypisuji data pro tabulku `shipping_method_translations`
--

INSERT INTO `shipping_method_translations` (`method_id`, `language_id`, `name`, `description`) VALUES
(1, 1, 'Doručení na adresu', NULL),
(2, 1, 'Zásilkovna', NULL),
(3, 1, 'Osobní odběr', NULL);

-- --------------------------------------------------------

--
-- Struktura tabulky `shipping_rates`
--

CREATE TABLE `shipping_rates` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `method_id` bigint(20) UNSIGNED NOT NULL,
  `country_code` char(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `currency` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CZK',
  `price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `free_from` decimal(12,2) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Vypisuji data pro tabulku `shipping_rates`
--

INSERT INTO `shipping_rates` (`id`, `method_id`, `country_code`, `currency`, `price`, `free_from`, `active`) VALUES
(1, 1, 'CZ', 'CZK', 90.00, NULL, 1),
(3, 2, 'CZ', 'CZK', 100.00, NULL, 1),
(5, 3, 'CZ', 'CZK', 0.00, NULL, 1);

-- --------------------------------------------------------

--
-- Struktura tabulky `shop_currency_rates`
--

CREATE TABLE `shop_currency_rates` (
  `currency` char(3) CHARACTER SET ascii NOT NULL,
  `czk_per_unit` decimal(18,8) NOT NULL COMMENT 'Kolik CZK stoji 1 jednotka meny',
  `source` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ECB',
  `source_date` date NOT NULL,
  `fetched_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `shop_delivery_email_templates`
--

CREATE TABLE `shop_delivery_email_templates` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `email_subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `operator_copy_email` varchar(254) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Vypisuji data pro tabulku `shop_delivery_email_templates`
--

INSERT INTO `shop_delivery_email_templates` (`id`, `email_subject`, `email_body`, `operator_copy_email`, `updated_at`) VALUES
(1, 'Digitální knihy k objednávce {order_number}', 'Dobrý den,\nděkujeme za objednávku č. {order_number}.\nZakoupené digitální soubory si stáhnete zde:\n\n{download_links}Odkazy mají platnost {validity_days} dní od vytvoření. Nesdílejte je s dalšími osobami.\nDěkujeme za váš nákup.\n\n----------------------------------------\n\nHello,\nthank you for your order no. {order_number}.\nYou can download your purchased digital files here:\n\n{download_links}The download links are valid for {validity_days} days from their creation. Please do not share them with anyone else.\nThank you for your purchase.\n', 'kovalovska.pavla@seznam.cz', '2026-10-25 17:36:47');

-- --------------------------------------------------------

--
-- Struktura tabulky `shop_digital_access`
--

CREATE TABLE `shop_digital_access` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `file_id` bigint(20) UNSIGNED NOT NULL,
  `token_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token_encrypted` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `revoked_at` datetime DEFAULT NULL,
  `download_count` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `last_download_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Vypisuji data pro tabulku `shop_digital_access`
--

INSERT INTO `shop_digital_access` (`id`, `order_id`, `file_id`, `token_hash`, `token_encrypted`, `expires_at`, `revoked_at`, `download_count`, `last_download_at`, `created_at`) VALUES
(1, 5, 1, '8c6e056d97e66cabecdbc175bebd9be90c8b057779da5c5d4533eb683fc31f17', 'qsLHF2bXtEMYznLiJsO6I2a0s97hk7UeBmz82IQHk+5ZaR+bGRfJLK1pdRjlVeu9lX61b8UDSeEFQb9/39wlTbEkf1a7osfOEFkb3/rPncplDLRXSsGBd8cWHMM=', '2026-11-15 10:00:39', NULL, 2, '2026-10-16 10:07:34', '2026-10-16 08:00:39'),
(2, 6, 2, '00e1bd762140382560f629061f1d7f6fc91a15aed471fae5ea266c241471408d', 'wZRcFZNPf+PmDVDjfgioUauNapXXzi/fZ3dMLy8acSz12B1MMNl+bUaLShIHcjh3LwFJQPHvdX1v/arQCdBNsEWPbThDz8P0UfyJjw4U1ZJpwmjKzhHRKcqzoyw=', '2026-11-15 12:00:01', NULL, 3, '2026-10-16 15:24:51', '2026-10-16 10:00:01'),
(3, 10, 2, '2aa3b5691da61bbc5eebeb17d960eec644b166ca020fd481f4e4707d194624f3', '0BGJcLry6S6w4GXR7onuerUoR/AWAYxPPgtiJWxuwh+RN3LgGMWpJoA3oSRLuCeIvwhTRBs9elD9DZm0m9kjhPmHeks5c0NTI+4XL6xsrLjgrvjL+we3EzecnDg=', '2026-11-15 15:50:09', NULL, 0, NULL, '2026-10-16 13:50:09'),
(4, 12, 1, 'a9bdfa241a70853fca39182ebbd860a83f41e3a82378406da0c426a63bcefdca', 'wbGM5R1YhMzU0+3SvE4Hp+aAaezZPsdHCDkwZsa7UOagBFI2Bff4EwoDBPtoONube8WxO8mvxOh/Niy7U1CgIvah8lrvT02kCTQ2fw/xFU+2C0+R6KGTKva8yzY=', '2026-11-15 17:43:43', NULL, 2, '2026-10-16 18:27:26', '2026-10-16 15:43:43'),
(5, 20, 4, '54496dc7aa3a6e20776cb61248164aa95abbd4722314f82ced148ce98ad963ef', 'Up3PcNW4MWfrXBUW78EXcQPxeIjUhq2TAuYe8KLOQ/q2jKHp3l9UxfE0IgXi7FW/NmQWhE8zK83cucnjVIlgFXcG56TtgMACjh2HZQUR3ABvr5kvbyQudUd+/5A=', '2026-11-20 21:36:45', NULL, 2, '2026-10-21 21:38:48', '2026-10-21 19:36:45'),
(6, 21, 16, '585d1372908767d5612f0b0863836f7159dc09ed25bd7345a728a43e456ec754', '8FfxZm8IIlwgZDfDCM4Dl9Ha/Jl4PNA2pC0+Pjgzk1AkJbr9WKMVHt67ipn075EV3/uCbkdUGMHX32hphuezEBqHWRha4ZSrfSGTCXSw2JyggOS4yVJCUM5umCE=', '2026-11-24 14:07:12', NULL, 0, NULL, '2026-10-25 13:07:12'),
(7, 21, 15, 'a2eecb6fb37889b17b1774727d3868f57510445a9a6bef42356259c1d7554831', 'Fttgd8dC+4ty6rGE+B/7FEWuhgiBosNazFFZG5n9FlIR04ZKah2tI1OlslePCkhba52Cw1zOMGi76wx5LAFxoRLPSO0oNFCQAIZhSat3G6k2bGKgQ5inf9D5Mvw=', '2026-11-24 14:07:12', NULL, 2, '2026-10-25 14:07:47', '2026-10-25 13:07:12'),
(8, 22, 38, '132e591961e2b2e593ff4a804c349b10fab9397b9c468ef57721276216bcf410', 'BEdW9FZLbYrd42dTIR/1Dr9d7WbeAHZ/G3bXFo5638MVv0GSLkaUO+NieCtdOOEUy1/NCAT/wqv21E8RVNVcedPwLjhIQvzuxWlvwrb8wYD+xfhvCxmp9EpXBq4=', '2026-11-24 20:47:03', NULL, 0, NULL, '2026-10-25 19:47:03'),
(9, 24, 38, '671b7a7703c083c8b9687f9f9eb3c138feea909a4a6ffca84741b74208ce1e85', '7jjn2GVJqZVNN16EaGBUaIJD9AVCFxK0yQKRD8ALcPiBMSo94q8YZoIiZyljsVekUtfegTDGaqXfDky+liRBiOkfWz2+Sf6+hGpzaIDgLLoGef8FhVcfiagSz7M=', '2026-11-24 21:02:10', NULL, 0, NULL, '2026-10-25 20:02:10'),
(10, 25, 4, '362f71fbdc8b27dbfc704ffc0e27f7510ae2322193c9bee9e9a3638709809647', 'jya/QzM0e+neZX0n2X6kABZJLIautv32iPNDFogq9LMxw3Gg5Su0/fa2sNZ5HZlDifdPviTQnlFW+KVaZoo94ZI7vopHBUI/yi1kjdTELnd+B0u1OdJYJxB1XYY=', '2026-11-24 21:10:25', NULL, 0, NULL, '2026-10-25 20:10:25'),
(11, 26, 34, '29c744743a54dd851d00845b0f520cfb0c6170a1fff54836c246b8496017bacd', 'XwSEXNxSudROFmBNcCZ1KcjU88CBs6AZvcTG36UV41WKr+Op2tTaB/wS6fWA/RZxWMcue0IpqBJy1pTsLbQ8+MQtIHM5NkMsDcD5qbEn6H5I6RUsOywRKQrHTr8=', '2026-11-24 21:31:25', NULL, 0, NULL, '2026-10-25 20:31:25'),
(12, 27, 38, 'b6ae1fc551c4e274c8b830fcb90d26c9a7e0581b1f24af62f8e6954fb09d685f', '/DU36ddJCMjIvmc8u7nLb82O33qNQSopEov1RYIgx2pXeWw5QgI/nZjgBOxrrQZ/3w2nNEvRCNr9Kw7Sa11sNtzP6bkMNLukc4rF1SvQISOFViaM28hPz231+T4=', '2026-11-24 21:43:02', NULL, 0, NULL, '2026-10-25 20:43:02'),
(13, 28, 73, 'abd8c6de23e0c18e85521d81d6030b5c199c31a1c24ed798f9eda2f9e9da11c4', 'A0mMx2Ww8fcU/ItCLS5K0UPklZ1x5xnZFEI3J5ayj+OWkyHgbV9LVSbe7aha58R1r3AI8DUQOmPPS6Etb3EYasn8bL4dO9Pu4aE4PCmKe88WneG64ErWJhtMYZ4=', '2026-11-24 22:06:57', NULL, 2, '2026-10-25 22:09:05', '2026-10-25 21:06:57'),
(14, 28, 72, 'b0d5e29ed7c952cc8d4714da3bbaf4a46d68dd94af35e07d477d6a84bb7b63c7', 'zJ6xlQqTH18F/IxZIGc+JGUkDTgXDSA+hRyhiAM4ZcTXwLbyAqMyBhUo+KYWZKPj60oGfFJhOGrhBmA1jeQwIJIBg/YU7cf6PHO4fsa7XFBl7rtxDKvXjMw6chI=', '2026-11-24 22:06:57', NULL, 0, NULL, '2026-10-25 21:06:57'),
(15, 29, 37, 'f0ea0bb970f3421e504fb3944c44ca17439f75a98fd35e2331d3105a1fe75ee8', '70OtvZ5zMykn7+cY5U+X7fqis92D6ciZiJkR9ISglLw2ewF1Cc7lyyQKssIxUXmBl8J7VUWBQnsUeE+53TgAnZfXE36bq1lninAYyBJkla0YxxIMr961kybTcAY=', '2026-11-24 22:21:22', NULL, 0, NULL, '2026-10-25 21:21:22'),
(16, 30, 36, '2650c03b92f584d0d84a1cd3305fcb4c4fe22bb9daeae24d1a130dd315b06f59', 'G6Zi2zeUDzrmHewvQrJ7UDsEc2o8KqJ8ttBdKdXw9/ehZMB/9ALfJMlDeBTqoLOuclTiu5H+T5xsN4q7gm72y+9iSZgRh+7Lu0brXog4UzZV7LNjgIrMIWuPK2I=', '2026-11-24 22:43:35', NULL, 0, NULL, '2026-10-25 21:43:35'),
(17, 31, 38, '076182cd60fc90e14e552a1cacd76a341c3965115802cbe77b578d4fc0ccda26', 'mufB85bEiFNAxleqElbLdxcM0adlcm9s+Eu2BwJQYKHQ5hd9Rg44y8wqp96wGrK3qWHR74Y5efSF7vYZfKpRSGsJKXLJ8vY9CZyO2qwFWY2L7qITVxHzHUgVZK4=', '2026-11-24 23:09:00', NULL, 0, NULL, '2026-10-25 22:09:00'),
(18, 32, 42, 'df9a50a7495cec6cf03556ea5cdb9b4094f3348442a12fc6517b0af00b580c93', 'Zg1rISG3cbTcElNgo5YT7WEI+0EifnCJIGNe2sUEPbNsdDmzv60lwwmG5a84PVRoLTC9nVpm/WOwjIU0f6PrcKi7ue6DHUOU82W+0xXRKixZeSn827LmdC8tLl8=', '2026-11-25 19:49:39', NULL, 0, NULL, '2026-10-26 18:49:39'),
(19, 37, 34, '7dc6e68115f5cae7d2bbe7bd25738a7e4e5892239434345b0b78ba167b1a4e53', 'h2LRlwkhty+AVFcfJRFgd+txyGmwHetMt5Whmp12ZWB6PsOK+uRMAIjVtF87i5qIHfgaCaaSo+dZwaimKi9r7okr/GWSX0/j4mX9ue9RhNDf1dA1uztMfyBuLGA=', '2026-11-25 20:45:19', NULL, 2, '2026-10-26 23:16:35', '2026-10-26 19:45:19'),
(20, 35, 37, 'deaab00a525e89308fb335431f0b414755529d1c22b004c662662706ddfb6637', 'la7lOXsYKtMCl2QjYoXJqlV1A+9UHhn+CmYXdeu2UNRxiz9Gni1fDAx7WeUm+IuBFiWXthVMd2dIt0J/YH9nygPiMLXcIpDBTyCOYx0Nhv7J3GnIfwyjGh7s/Lw=', '2026-11-25 22:31:45', NULL, 0, NULL, '2026-10-26 21:31:45'),
(21, 36, 37, '336aea60c991518986b3f74ea31c2dbdf60596b128cf04a09df15a7113270eaf', '9ESaa9l1DT2n9UUHydmRwIYbgoFrIHbLFzBuY3fdagYQFmlkdhdpP8XCgd48YnsvkbnE0dGEJAQQsBulMfQO1+A1sJtg75eteig4ZFX0zlOSqac2rsiil1yyU7I=', '2026-11-25 23:26:22', NULL, 0, NULL, '2026-10-26 22:26:22'),
(22, 44, 38, 'e1dc06cd9d51969051f819f0cccce040949d295acf61e9f69e52d1b753809c75', '+A2/SFO6XuYsb5lUp3STviavAinWdumSb3lrDbE6zzeFDQUsWtsrUX6/GFju20Ht/k6bHtgkxAQLGvIYw0OK4LXEi4eAv2Fv4+0iMFzRYbf+tuPdi71JoJtm/Mw=', '2026-11-25 23:42:36', NULL, 0, NULL, '2026-10-26 22:42:36'),
(23, 43, 34, 'd633f8b3fb2e37f2f41f4203c7b09fedffe0ed7d18ae1e39fb649569c463bbe0', '0OZbZwPRvRvRM6vvWxvOgH4fx0TXve7XB6g3tTMM+82PCpWU+Nqg5Mr5iEn07wBJOhmLqrm7h5i2DDEIYxTVe32pY/a4yt7KojoTKaSJwF1VB7FtX0Svc9Xrqlw=', '2026-11-25 23:42:37', NULL, 0, NULL, '2026-10-26 22:42:37'),
(24, 42, 4, '520a706bb747c4b06697349808e2e8e822368d16884212a72200bc28dd237c0c', 'r8cI6ykaMXtrqPP8bxMb1Du1gHYwhEd3sWHff8y/0Xj42JL0UsgF+9tREmKTE9n8Vh9ced2g5Vgs1YDEWKyJA41rTnUOtHn5pgYjcZ3xQg2TIoCrwvOi1I4LHd0=', '2026-11-25 23:42:38', NULL, 0, NULL, '2026-10-26 22:42:38'),
(25, 41, 38, '82354cea0c7f24597d06add23fe216969f254f570f1a2dc2228a29f74f02ba6b', 'iK4CysLljB0sv4Xh3E/WgzcdhzblInnDpdmqAzuiimzluhfQvuAlROj0SQyM4qUixcAgK4XAVlV59T+Hjq/j42IBAKLnJXh6qZZ8FVG/+mtFjvWKOEGrDHEYwyY=', '2026-11-25 23:42:39', NULL, 0, NULL, '2026-10-26 22:42:39'),
(26, 40, 38, '415b299904c6400350471b108b1df87f3b03323524d2b6c679fa480eb87f204a', 'NVImiTYFNc/Dq3hP/MH8UoSZ05AhrQmV+Fv0Q1odDGw4Ze0gl49pSZEgGCnQ6rT20xagiqEjq76Yd8AsO4+A4F/VBjd3my8ARTgLosnR2Ry4uNMSSzRvHOo/L9A=', '2026-11-25 23:42:40', NULL, 0, NULL, '2026-10-26 22:42:40'),
(27, 39, 38, '7b7553dcc31b03c03a3edf3cf3cc4eabf4ff27ba06042fb2e204510129b56842', 'ps5hVoXwDJbknUGnslYEIk1iROgy48KGXcqnyA1IgEzcANZ2grOVcBVEpRl0Z0tBVC7X3uwoVGxBZQamSUPftbRR1DR9bnncDddjSyICpD6UsH3JhWMLNGejQMg=', '2026-11-25 23:42:41', NULL, 0, NULL, '2026-10-26 22:42:41'),
(28, 38, 34, 'eac6dce7c3aebdfde6207d65d3e7d1138d3ee60aa949e85d6a8de10f18b16f38', 'orJbkbXJLKBp7Tefej6+A0gH6XH2nLafDi3v4nA4LjBniEUylydcqbG4nX8AFMhE4MNk3eo/cuGbssJD7pi6qKX/uKKKne/9uAxv7NiqFCuUmnB0eLIhpLKi1Z4=', '2026-11-25 23:42:42', NULL, 0, NULL, '2026-10-26 22:42:42'),
(29, 45, 38, 'c91e0ec5acd48996dfc339b6481a6f334952666e4f8a7609c571d894428683d9', 'ypTyOlOGnK7ZfqUYcerG7a1k+jSRfFWRIRAy2DO472lQKamVTLp2vESxx0AARfq/NuDPUfzWu4nlb5wlho3P4gxIT8gfp5yCkoPXHp58ZCqvXX8e3dbL9iY+b04=', '2026-11-25 23:53:57', NULL, 2, '2026-10-26 23:55:17', '2026-10-26 22:53:57');

-- --------------------------------------------------------

--
-- Struktura tabulky `shop_digital_mail_log`
--

CREATE TABLE `shop_digital_mail_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `recipient` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source` enum('manual','webhook') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('sending','accepted','failed') COLLATE utf8mb4_unicode_ci NOT NULL,
  `error_code` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sent_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Vypisuji data pro tabulku `shop_digital_mail_log`
--

INSERT INTO `shop_digital_mail_log` (`id`, `order_id`, `recipient`, `source`, `status`, `error_code`, `created_at`, `sent_at`) VALUES
(1, 6, 'milan.smiesko@seznam.cz', 'manual', 'failed', 'Nelze se připojit k SMTP serveru.', '2026-10-16 10:31:17', NULL),
(2, 6, 'milan.smiesko@seznam.cz', 'manual', 'failed', 'Nelze se připojit k SMTP serveru.', '2026-10-16 10:34:39', NULL),
(3, 6, 'milan.smiesko@seznam.cz', 'manual', 'failed', 'Nelze se připojit k SMTP serveru.', '2026-10-16 10:35:03', NULL),
(4, 6, 'milan.smiesko@seznam.cz', 'manual', 'accepted', NULL, '2026-10-16 12:50:39', '2026-10-16 14:50:39'),
(5, 6, 'milan.smiesko@seznam.cz', 'manual', 'accepted', NULL, '2026-10-16 13:03:25', '2026-10-16 15:03:25'),
(6, 6, 'milan.smiesko@seznam.cz', 'manual', 'accepted', NULL, '2026-10-16 13:24:14', '2026-10-16 15:24:14'),
(7, 12, 'milan.smiesko@seznam.cz', 'webhook', 'failed', 'Neplatný UTF-8 text e-mailu.', '2026-10-16 15:43:43', NULL),
(8, 12, 'milan.smiesko@seznam.cz', 'manual', 'failed', 'Neplatné UTF-8 v poli from_name. Opravte zdrojový údaj v UTF-8; obsah není vypisován.', '2026-10-16 15:54:28', NULL),
(9, 12, 'milan.smiesko@seznam.cz', 'manual', 'accepted', NULL, '2026-10-16 15:57:33', '2026-10-16 17:57:33'),
(10, 12, 'milan.smiesko@seznam.cz', 'webhook', 'accepted', NULL, '2026-10-16 16:25:24', '2026-10-16 18:25:25'),
(11, 20, 'milan.smiesko@seznam.cz', 'webhook', 'accepted', NULL, '2026-10-21 19:36:45', '2026-10-21 21:36:46'),
(12, 21, 'milan.smiesko@seznam.cz', 'webhook', 'accepted', NULL, '2026-10-25 13:07:12', '2026-10-25 14:07:13'),
(13, 22, 'milan.smiesko@seznam.cz', 'webhook', 'accepted', NULL, '2026-10-25 19:47:03', '2026-10-25 20:47:04'),
(14, 24, 'milan.smiesko@seznam.cz', 'webhook', 'accepted', NULL, '2026-10-25 20:02:10', '2026-10-25 21:02:10'),
(15, 25, 'milan.smiesko@seznam.cz', 'webhook', 'accepted', NULL, '2026-10-25 20:10:25', '2026-10-25 21:10:26'),
(16, 26, 'milan.smiesko@seznam.cz', 'webhook', 'accepted', NULL, '2026-10-25 20:31:25', '2026-10-25 21:31:25'),
(17, 27, 'milan.smiesko@seznam.cz', 'webhook', 'accepted', NULL, '2026-10-25 20:43:02', '2026-10-25 21:43:02'),
(18, 28, 'milan.smiesko@seznam.cz', 'manual', 'accepted', NULL, '2026-10-25 21:07:59', '2026-10-25 22:07:59'),
(19, 29, 'milan.smiesko@seznam.cz', 'webhook', 'accepted', NULL, '2026-10-25 21:21:22', '2026-10-25 22:21:22'),
(20, 30, 'milan.smiesko@seznam.cz', 'webhook', 'accepted', NULL, '2026-10-25 21:43:35', '2026-10-25 22:43:35'),
(21, 28, 'milan.smiesko@seznam.cz', 'webhook', 'accepted', NULL, '2026-10-25 21:45:34', '2026-10-25 22:45:34'),
(22, 31, 'milan.smiesko@seznam.cz', 'webhook', 'accepted', NULL, '2026-10-25 22:09:00', '2026-10-25 23:09:00'),
(23, 32, 'milan.smiesko@seznam.cz', 'webhook', 'accepted', NULL, '2026-10-26 18:49:39', '2026-10-26 19:49:40'),
(24, 37, 'milan.smiesko@seznam.cz', 'webhook', 'accepted', NULL, '2026-10-26 19:45:19', '2026-10-26 20:45:19'),
(25, 35, 'milan.smiesko@seznam.cz', 'webhook', 'accepted', NULL, '2026-10-26 21:31:45', '2026-10-26 22:31:45'),
(26, 36, 'milan.smiesko@seznam.cz', 'webhook', 'accepted', NULL, '2026-10-26 22:26:22', '2026-10-26 23:26:22'),
(27, 44, 'milan.smiesko@seznam.cz', 'webhook', 'accepted', NULL, '2026-10-26 22:42:36', '2026-10-26 23:42:36'),
(28, 43, 'milan.smiesko@seznam.cz', 'webhook', 'accepted', NULL, '2026-10-26 22:42:37', '2026-10-26 23:42:37'),
(29, 42, 'milan.smiesko@seznam.cz', 'webhook', 'accepted', NULL, '2026-10-26 22:42:38', '2026-10-26 23:42:38'),
(30, 41, 'milan.smiesko@seznam.cz', 'webhook', 'accepted', NULL, '2026-10-26 22:42:39', '2026-10-26 23:42:39'),
(31, 40, 'milan.smiesko@seznam.cz', 'webhook', 'accepted', NULL, '2026-10-26 22:42:40', '2026-10-26 23:42:40'),
(32, 39, 'milan.smiesko@seznam.cz', 'webhook', 'accepted', NULL, '2026-10-26 22:42:41', '2026-10-26 23:42:41'),
(33, 38, 'milan.smiesko@seznam.cz', 'webhook', 'accepted', NULL, '2026-10-26 22:42:42', '2026-10-26 23:42:42'),
(34, 45, 'milan.smiesko@seznam.cz', 'webhook', 'accepted', NULL, '2026-10-26 22:53:57', '2026-10-26 23:53:57');

-- --------------------------------------------------------

--
-- Struktura tabulky `shop_digital_retry_log`
--

CREATE TABLE `shop_digital_retry_log` (
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `attempts` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `last_result` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_error` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_attempt_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `shop_orders`
--

CREATE TABLE `shop_orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_number` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'awaiting_payment',
  `email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `shipping_country` char(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shipping_street` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shipping_city` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shipping_postal` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_same_as_shipping` tinyint(1) NOT NULL DEFAULT '1',
  `billing_name` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `billing_street` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `billing_city` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `billing_postal` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `billing_country` char(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `billing_company` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_company_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_vat_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shipping_method_id` bigint(20) UNSIGNED DEFAULT NULL,
  `shipping_method_name` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shipping_type` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pickup_point` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shipping_price` decimal(12,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(12,2) NOT NULL,
  `total` decimal(12,2) NOT NULL,
  `currency` char(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Vypisuji data pro tabulku `shop_orders`
--

INSERT INTO `shop_orders` (`id`, `order_number`, `status`, `email`, `phone`, `customer_first_name`, `customer_last_name`, `shipping_country`, `shipping_street`, `shipping_city`, `shipping_postal`, `billing_same_as_shipping`, `billing_name`, `billing_street`, `billing_city`, `billing_postal`, `billing_country`, `billing_company`, `billing_company_id`, `billing_vat_id`, `shipping_method_id`, `shipping_method_name`, `shipping_type`, `pickup_point`, `shipping_price`, `subtotal`, `total`, `currency`, `customer_note`, `created_at`) VALUES
(3, 'RAC-261015-09191698DD', 'paid', 'milan.smiesko@seznam.cz', '774361633', 'nn', 'nn', NULL, NULL, NULL, NULL, 0, 'nnn', 'Ve Fisnkých 1826', 'Petřvald', '73541', 'CZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 900.00, 900.00, 'CZK', NULL, '2026-10-15 08:15:04'),
(4, 'RAC-261015-8BBA117771', 'paid', 'michal.wagner@seznam.cz', '778778778', 'Michal', 'Wágner', NULL, NULL, NULL, NULL, 0, 'Michal Wágner', 'ulice 18', 'Ostrava', '73000', 'CZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 295.00, 295.00, 'CZK', NULL, '2026-10-15 11:23:44'),
(5, 'RAC-261016-F602D289F3', 'paid', 'jindra@seznam.cz', '123456789', 'Jindra', 'Hudeček', NULL, NULL, NULL, NULL, 0, 'Jindřich Hudeček', 'doma 45', 'Brno', '90050', 'CZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 300.00, 300.00, 'CZK', NULL, '2026-10-16 07:59:26'),
(6, 'RAC-261016-597358DD6F', 'paid', 'milan.smiesko@seznam.cz', '777777888', 'Milan', 'Smieško', NULL, NULL, NULL, NULL, 0, 'Milan  Smieško', 'Ve Finských 1826', 'Petřvald', '73541', 'CZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 300.00, 300.00, 'CZK', NULL, '2026-10-16 09:57:30'),
(7, 'RAC-261016-024F3CD034', 'paid', 'milan.smiesko@seznam.cz', '728024400', 'Jindřich', 'Hudeček', NULL, NULL, NULL, NULL, 0, 'Milan  Smieško', '325', 'Makrvartovice', '747 14', 'CZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 300.00, 300.00, 'CZK', NULL, '2026-10-16 13:22:12'),
(8, 'RAC-261016-5A47D7B374', 'paid', 'milan.smiesko@seznam.cz', '728024400', 'Aleš', 'Hradil', NULL, NULL, NULL, NULL, 0, 'Aleš Hradil', '325', 'Makrvartovice', '747 14', 'CZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 300.00, 300.00, 'CZK', NULL, '2026-10-16 13:30:16'),
(9, 'RAC-261016-4017259C73', 'paid', 'milan.smiesko@seznam.cz', '728024400', 'Jindřich', 'Hudeček', NULL, NULL, NULL, NULL, 0, 'Testovací nákup', '325', 'Makrvartovice', '747 14', 'CZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 300.00, 300.00, 'CZK', NULL, '2026-10-16 13:37:19'),
(10, 'RAC-261016-856DAE2410', 'paid', 'serp24.cz@gmail.com', '777898989', 'Marcel', 'Jonas', NULL, NULL, NULL, NULL, 0, 'Gmail Pokus', 'Jaselská 154/2', 'Opava Předměstí', '74601', 'CZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 300.00, 300.00, 'CZK', NULL, '2026-10-16 13:41:41'),
(11, 'RAC-261016-7AD2B38A1B', 'paid', 'milan.smiesko@seznam.cz', '728024400', 'Jindřich', 'Hudeček', NULL, NULL, NULL, NULL, 0, 'Milan  Smieško2', '325', 'Makrvartovice', '747 14', 'CZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 1500.00, 1500.00, 'CZK', NULL, '2026-10-16 13:58:59'),
(12, 'RAC-261016-692F124876', 'paid', 'milan.smiesko@seznam.cz', '7774558558', 'Karin', 'Malá', NULL, NULL, NULL, NULL, 0, 'Karin Malá', 'doma 45', 'ostrava', '70030', 'CZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 600.00, 600.00, 'CZK', NULL, '2026-10-16 15:24:51'),
(13, 'RAC-261016-4412599095', 'awaiting_payment', 'milan.smiesko@seznam.cz', '728024400', 'Jindřich', 'Hudeček', NULL, NULL, NULL, NULL, 0, 'Aleš Hradil', '325', 'Makrvartovice', '747 14', 'CZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 900.00, 900.00, 'CZK', NULL, '2026-10-16 16:00:35'),
(14, 'RAC-261016-1FD0BB2E8D', 'awaiting_payment', 'milan.smiesko@seznam.cz', '728024400', 'Jindřich', 'Hudeček', 'CZ', '325', 'Makrvartovice', '747 14', 1, 'safa', '325', 'Makrvartovice', '747 14', 'CZ', NULL, NULL, NULL, 3, 'Osobní odběr', 'personal', NULL, 0.00, 600.00, 600.00, 'CZK', NULL, '2026-10-16 16:02:24'),
(15, 'RAC-261016-F5E5AA4A6D', 'awaiting_payment', 'milan.smiesko@seznam.cz', '789456122', 'Marcel', 'Jonas', NULL, NULL, NULL, NULL, 0, 'Testovací nákup3', 'Jaselská 154/2', 'Opava Předměstí', '74601', 'CZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 300.00, 300.00, 'CZK', NULL, '2026-10-16 16:09:37'),
(16, 'RAC-261016-611E836358', 'paid', 'milan.smiesko@seznam.cz', '728024400', 'Jindřich', 'Hudeček', 'CZ', '325', 'Makrvartovice', '747 14', 1, '98', '325', 'Makrvartovice', '747 14', 'CZ', NULL, NULL, NULL, 3, 'Osobní odběr', 'personal', NULL, 0.00, 300.00, 300.00, 'CZK', NULL, '2026-10-16 16:24:31'),
(17, 'RAC-261016-D72F6159FF', 'awaiting_payment', 'milan.smiesko@seznam.cz', '789456321', 'Marcel', 'Jonas', NULL, NULL, NULL, NULL, 0, 'ddd', 'Jaselská 154/2', 'Opava Předměstí', '74601', 'CZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 300.00, 300.00, 'CZK', NULL, '2026-10-16 16:25:56'),
(18, 'RAC-261019-C1EE6F55C3', 'awaiting_payment', 'serp24.cz@gmail.com', '774361636', 'Milan', 'Smieško', 'CZ', 'Ve Finských 1826', 'Petřvald', '735 41', 1, 'Milan Smieško', 'Ve Finských 1826', 'Petřvald', '735 41', 'CZ', NULL, NULL, NULL, 3, 'Osobní odběr', 'personal', NULL, 0.00, 300.00, 300.00, 'CZK', NULL, '2026-10-19 11:14:43'),
(19, 'RAC-261020-5914672D51', 'awaiting_payment', 'milan.smiesko@seznam.cz', '777445123', 'Milan', 'Smieško', NULL, NULL, NULL, NULL, 0, 'Milan Smieško', 'Ve finských 1826', 'Petřvald', '73541', 'CZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 300.00, 300.00, 'CZK', NULL, '2026-10-20 11:08:57'),
(20, 'RAC-261021-E002679F5B', 'paid', 'milan.smiesko@seznam.cz', '774163616', 'Milan', 'Smieško', NULL, NULL, NULL, NULL, 0, 'ing. Milan Smieško', 'Ve finských 1892', 'Petřvald', '70032', 'CZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 199.00, 199.00, 'CZK', NULL, '2026-10-21 19:35:42'),
(21, 'RAC-261025-867CF70B58', 'paid', 'milan.smiesko@seznam.cz', '774361636', 'Milan', 'Smieško', NULL, NULL, NULL, NULL, 0, 'Milan Smieško', 'Ve finských 1892', 'Petřvald', '73541', 'CZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 199.00, 199.00, 'CZK', NULL, '2026-10-25 13:06:32'),
(22, 'RAC-261025-CC74E1197E', 'paid', 'milan.smiesko@seznam.cz', '777456123', 'Milan', 'Smieško', NULL, NULL, NULL, NULL, 0, 'ing. Milan Smieško', 'Ve finských 1892', 'Petřvald', '70032', 'CZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 270.00, 270.00, 'CZK', NULL, '2026-10-25 19:46:37'),
(23, 'RAC-261025-694112F915', 'paid', 'milan.smiesko@seznam.cz', '777456123', 'Milan', 'Smieško', NULL, NULL, NULL, NULL, 0, 'Milan Smieško', 'Ve finských 1892', 'Petřvald', '73541', 'CZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 329.00, 329.00, 'CZK', NULL, '2026-10-25 19:56:26'),
(24, 'RAC-261025-E0F0CD6C39', 'paid', 'milan.smiesko@seznam.cz', '777456123', 'Milan', 'Smieško', NULL, NULL, NULL, NULL, 0, 'Milan Smieško', 'Ve finských 1892', 'Petřvald', '73541', 'CZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 270.00, 270.00, 'CZK', NULL, '2026-10-25 20:01:48'),
(25, 'RAC-261025-EF78DF2086', 'paid', 'milan.smiesko@seznam.cz', '774361636', 'Milan', 'Smieško', NULL, NULL, NULL, NULL, 0, 'Milan Smieško', 'Ve finských 1892', 'Petřvald', '70032', 'CZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 199.00, 199.00, 'CZK', NULL, '2026-10-25 20:10:06'),
(26, 'RAC-261025-D5CD1B0700', 'paid', 'milan.smiesko@seznam.cz', '777456123', 'Milan', 'Smieško', NULL, NULL, NULL, NULL, 0, 'Milan Smieško', 'Ve finských 1892', 'Petřvald', '73541', 'CZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 199.00, 199.00, 'CZK', NULL, '2026-10-25 20:31:03'),
(27, 'RAC-261025-A20949070C', 'paid', 'milan.smiesko@seznam.cz', '774163616', 'Milan', 'Smieško', NULL, NULL, NULL, NULL, 0, 'Milan Smieško', 'Ve finských 1892', 'Petřvald', '70032', 'CZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 270.00, 270.00, 'CZK', NULL, '2026-10-25 20:42:36'),
(28, 'RAC-261025-4A2C702497', 'paid', 'milan.smiesko@seznam.cz', '777456123', 'Milan', 'Smieško', NULL, NULL, NULL, NULL, 0, 'Milan Smieško', 'Ve finských 1892', 'Petřvald', '73541', 'CZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 329.00, 329.00, 'CZK', NULL, '2026-10-25 20:45:44'),
(29, 'RAC-261025-71F45354A0', 'paid', 'milan.smiesko@seznam.cz', '777456123', 'Milan', 'Smieško', NULL, NULL, NULL, NULL, 0, 'Milan Smieško', 'Ve finských 1892', 'Petřvald', '73541', 'CZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 329.00, 329.00, 'CZK', NULL, '2026-10-25 21:21:01'),
(30, 'RAC-261025-1B04EA616E', 'paid', 'milan.smiesko@seznam.cz', '774163616', 'Milan', 'Smieško', NULL, NULL, NULL, NULL, 0, 'Milan Smieško', 'Ve finských 1892', 'Ostrava', '785421', 'CZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 190.00, 190.00, 'CZK', NULL, '2026-10-25 21:43:14'),
(31, 'RAC-261025-396A318777', 'paid', 'milan.smiesko@seznam.cz', '774163616', 'Milan', 'Smieško', NULL, NULL, NULL, NULL, 0, 'Milan Smieško', 'Ve finských 1892', 'Petřvald', '73541', 'CZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 270.00, 270.00, 'CZK', NULL, '2026-10-25 22:08:31'),
(32, 'RAC-261026-F095B70FCB', 'paid', 'milan.smiesko@seznam.cz', '774361636', 'Milan', 'Smieško', NULL, NULL, NULL, NULL, 0, 'Ing. Milan Smieško', 'Ve Finských 1826', 'Petřvald', '735 41', 'PL', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 270.00, 270.00, 'CZK', 'Testovací platba (zahraničí)', '2026-10-26 18:48:06'),
(33, 'RAC-261026-5A5EF6457D', 'awaiting_payment', 'serp24.cz@gmail.com', '774361636', 'Milan', 'Smieško', NULL, NULL, NULL, NULL, 0, 'Mianek', 'Ve Finských 1826', 'Petřvald', '735 41', 'CZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 270.00, 270.00, 'CZK', NULL, '2026-10-26 18:55:29'),
(34, 'RAC-261026-524AEBFCD6', 'awaiting_payment', 'serp24.cz@gmail.com', '774361636', 'Milan', 'Smieško', NULL, NULL, NULL, NULL, 0, 'Mianek', 'Ve Finských 1826', 'Petřvald', '735 41', 'CZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 270.00, 270.00, 'CZK', NULL, '2026-10-26 19:01:51'),
(35, 'RAC-261026-ED231CC26F', 'paid', 'milan.smiesko@seznam.cz', '774361636', 'Michal', 'Smieško', NULL, NULL, NULL, NULL, 0, 'Michal Smieško', 'Ve Finských 1826', 'Petřvald', '735 41', 'CZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 329.00, 329.00, 'CZK', 'Test č.2', '2026-10-26 19:14:31'),
(36, 'RAC-261026-BC3FEBF8C7', 'paid', 'milan.smiesko@seznam.cz', '777555666', 'milan', 'smieško', NULL, NULL, NULL, NULL, 0, 'milan smieško', 'Ve Finských 1826', 'Petřvald', '735 41', 'CZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 329.00, 329.00, 'CZK', 'testovací objednávka č.3', '2026-10-26 19:27:02'),
(37, 'RAC-261026-13A01C04D3', 'paid', 'milan.smiesko@seznam.cz', '774361636', 'Aleš', 'Tvarda', NULL, NULL, NULL, NULL, 0, 'Aleš Tvarda', 'Ve finských 1892', 'Ostrava', '70030', 'CZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 199.00, 199.00, 'CZK', NULL, '2026-10-26 19:44:58'),
(38, 'RAC-261026-DFE30ECEBB', 'paid', 'milan.smiesko@seznam.cz', '777444111', 'Matyáš', 'Smieško', NULL, NULL, NULL, NULL, 0, 'Matyáš Smieško', 'Doma 23', 'Brno', '70070', 'CZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 199.00, 199.00, 'CZK', 'Poslední test', '2026-10-26 19:57:52'),
(39, 'RAC-261026-E41B513440', 'paid', 'milan.smiesko@seznam.cz', '774163616', 'milan', 'vondrák', NULL, NULL, NULL, NULL, 0, 'milan vondrák', 'Ve finských 1892', 'Praha', '11150', 'CZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 270.00, 270.00, 'CZK', NULL, '2026-10-26 21:26:07'),
(40, 'RAC-261026-BAE77FB53B', 'paid', 'milan.smiesko@seznam.cz', '774163616', 'Tomáš', 'Rybka', NULL, NULL, NULL, NULL, 0, 'Tomáš Rybka', 'Lesní 10', 'Krásné Pole', '77845', 'CZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 270.00, 270.00, 'CZK', NULL, '2026-10-26 21:28:30'),
(41, 'RAC-261026-9228FD8A46', 'paid', 'milan.smiesko@seznam.cz', '774163616', 'Aleš', 'Lytasy', NULL, NULL, NULL, NULL, 0, 'Aleš Lytasy', 'Lytasy 10', 'Lytasy', '12345', 'CZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 270.00, 270.00, 'CZK', NULL, '2026-10-26 21:31:42'),
(42, 'RAC-261026-8A2B000D41', 'paid', 'milan.smiesko@seznam.cz', '774163616', 'Milan', 'Smieško', NULL, NULL, NULL, NULL, 0, 'Milan Smieško', 'Lytasy 10', 'Ostrava', '77845', 'CZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 199.00, 199.00, 'CZK', NULL, '2026-10-26 21:53:45'),
(43, 'RAC-261026-EA5058DEA8', 'paid', 'milan.smiesko@seznam.cz', '774163616', 'Milan', 'Kovalovská', NULL, NULL, NULL, NULL, 0, 'ing. Milan Smieško', 'Doma 23', 'Ostrava', '70070', 'CZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 199.00, 199.00, 'CZK', NULL, '2026-10-26 21:58:37'),
(44, 'RAC-261026-897FA33FD3', 'paid', 'milan.smiesko@seznam.cz', '774163616', 'Milan', 'Lytasy', NULL, NULL, NULL, NULL, 0, 'Milan Lytasy', 'Doma 23', 'Petřvald', '73541', 'CZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 270.00, 270.00, 'CZK', '1', '2026-10-26 22:23:19'),
(45, 'RAC-261026-E4B53CC468', 'paid', 'milan.smiesko@seznam.cz', '774163616', 'Milan', 'Lytasy', NULL, NULL, NULL, NULL, 0, 'Milan Lytasy', 'Lesní 10', 'Ostrava', '12345', 'CZ', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 270.00, 270.00, 'CZK', NULL, '2026-10-26 22:53:34');

-- --------------------------------------------------------

--
-- Struktura tabulky `shop_order_digital_consents`
--

CREATE TABLE `shop_order_digital_consents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `consent_given` tinyint(1) NOT NULL DEFAULT '1',
  `consent_version` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `consent_language` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `consent_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `consented_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Vypisuji data pro tabulku `shop_order_digital_consents`
--

INSERT INTO `shop_order_digital_consents` (`id`, `order_id`, `consent_given`, `consent_version`, `consent_language`, `consent_text`, `consented_at`) VALUES
(1, 20, 1, '2026-10-20-v1', 'cs', 'Výslovně souhlasím se zpřístupněním zakoupeného digitálního obsahu před uplynutím 14 dnů od uzavření smlouvy. Beru na vědomí, že zahájením jeho zpřístupnění za těchto podmínek ztrácím právo odstoupit od smlouvy ve 14denní lhůtě.', '2026-10-21 21:35:42'),
(2, 21, 1, '2026-10-20-v1', 'cs', 'Výslovně souhlasím se zpřístupněním zakoupeného digitálního obsahu před uplynutím 14 dnů od uzavření smlouvy. Beru na vědomí, že zahájením jeho zpřístupnění za těchto podmínek ztrácím právo odstoupit od smlouvy ve 14denní lhůtě.', '2026-10-25 14:06:32'),
(3, 22, 1, '2026-10-20-v1', 'cs', 'Výslovně souhlasím se zpřístupněním zakoupeného digitálního obsahu před uplynutím 14 dnů od uzavření smlouvy. Beru na vědomí, že zahájením jeho zpřístupnění za těchto podmínek ztrácím právo odstoupit od smlouvy ve 14denní lhůtě.', '2026-10-25 20:46:37'),
(4, 23, 1, '2026-10-20-v1', 'cs', 'Výslovně souhlasím se zpřístupněním zakoupeného digitálního obsahu před uplynutím 14 dnů od uzavření smlouvy. Beru na vědomí, že zahájením jeho zpřístupnění za těchto podmínek ztrácím právo odstoupit od smlouvy ve 14denní lhůtě.', '2026-10-25 20:56:26'),
(5, 24, 1, '2026-10-20-v1', 'cs', 'Výslovně souhlasím se zpřístupněním zakoupeného digitálního obsahu před uplynutím 14 dnů od uzavření smlouvy. Beru na vědomí, že zahájením jeho zpřístupnění za těchto podmínek ztrácím právo odstoupit od smlouvy ve 14denní lhůtě.', '2026-10-25 21:01:48'),
(6, 25, 1, '2026-10-20-v1', 'cs', 'Výslovně souhlasím se zpřístupněním zakoupeného digitálního obsahu před uplynutím 14 dnů od uzavření smlouvy. Beru na vědomí, že zahájením jeho zpřístupnění za těchto podmínek ztrácím právo odstoupit od smlouvy ve 14denní lhůtě.', '2026-10-25 21:10:06'),
(7, 26, 1, '2026-10-20-v1', 'cs', 'Výslovně souhlasím se zpřístupněním zakoupeného digitálního obsahu před uplynutím 14 dnů od uzavření smlouvy. Beru na vědomí, že zahájením jeho zpřístupnění za těchto podmínek ztrácím právo odstoupit od smlouvy ve 14denní lhůtě.', '2026-10-25 21:31:03'),
(8, 27, 1, '2026-10-20-v1', 'cs', 'Výslovně souhlasím se zpřístupněním zakoupeného digitálního obsahu před uplynutím 14 dnů od uzavření smlouvy. Beru na vědomí, že zahájením jeho zpřístupnění za těchto podmínek ztrácím právo odstoupit od smlouvy ve 14denní lhůtě.', '2026-10-25 21:42:36'),
(9, 28, 1, '2026-10-20-v1', 'cs', 'Výslovně souhlasím se zpřístupněním zakoupeného digitálního obsahu před uplynutím 14 dnů od uzavření smlouvy. Beru na vědomí, že zahájením jeho zpřístupnění za těchto podmínek ztrácím právo odstoupit od smlouvy ve 14denní lhůtě.', '2026-10-25 21:45:44'),
(10, 29, 1, '2026-10-20-v1', 'cs', 'Výslovně souhlasím se zpřístupněním zakoupeného digitálního obsahu před uplynutím 14 dnů od uzavření smlouvy. Beru na vědomí, že zahájením jeho zpřístupnění za těchto podmínek ztrácím právo odstoupit od smlouvy ve 14denní lhůtě.', '2026-10-25 22:21:01'),
(11, 30, 1, '2026-10-20-v1', 'cs', 'Výslovně souhlasím se zpřístupněním zakoupeného digitálního obsahu před uplynutím 14 dnů od uzavření smlouvy. Beru na vědomí, že zahájením jeho zpřístupnění za těchto podmínek ztrácím právo odstoupit od smlouvy ve 14denní lhůtě.', '2026-10-25 22:43:14'),
(12, 31, 1, '2026-10-20-v1', 'cs', 'Výslovně souhlasím se zpřístupněním zakoupeného digitálního obsahu před uplynutím 14 dnů od uzavření smlouvy. Beru na vědomí, že zahájením jeho zpřístupnění za těchto podmínek ztrácím právo odstoupit od smlouvy ve 14denní lhůtě.', '2026-10-25 23:08:31'),
(13, 32, 1, '2026-10-20-v1', 'cs', 'Výslovně souhlasím se zpřístupněním zakoupeného digitálního obsahu před uplynutím 14 dnů od uzavření smlouvy. Beru na vědomí, že zahájením jeho zpřístupnění za těchto podmínek ztrácím právo odstoupit od smlouvy ve 14denní lhůtě.', '2026-10-26 19:48:06'),
(14, 33, 1, '2026-10-20-v1', 'cs', 'Výslovně souhlasím se zpřístupněním zakoupeného digitálního obsahu před uplynutím 14 dnů od uzavření smlouvy. Beru na vědomí, že zahájením jeho zpřístupnění za těchto podmínek ztrácím právo odstoupit od smlouvy ve 14denní lhůtě.', '2026-10-26 19:55:29'),
(15, 34, 1, '2026-10-20-v1', 'cs', 'Výslovně souhlasím se zpřístupněním zakoupeného digitálního obsahu před uplynutím 14 dnů od uzavření smlouvy. Beru na vědomí, že zahájením jeho zpřístupnění za těchto podmínek ztrácím právo odstoupit od smlouvy ve 14denní lhůtě.', '2026-10-26 20:01:51'),
(16, 35, 1, '2026-10-20-v1', 'cs', 'Výslovně souhlasím se zpřístupněním zakoupeného digitálního obsahu před uplynutím 14 dnů od uzavření smlouvy. Beru na vědomí, že zahájením jeho zpřístupnění za těchto podmínek ztrácím právo odstoupit od smlouvy ve 14denní lhůtě.', '2026-10-26 20:14:31'),
(17, 36, 1, '2026-10-20-v1', 'cs', 'Výslovně souhlasím se zpřístupněním zakoupeného digitálního obsahu před uplynutím 14 dnů od uzavření smlouvy. Beru na vědomí, že zahájením jeho zpřístupnění za těchto podmínek ztrácím právo odstoupit od smlouvy ve 14denní lhůtě.', '2026-10-26 20:27:02'),
(18, 37, 1, '2026-10-20-v1', 'cs', 'Výslovně souhlasím se zpřístupněním zakoupeného digitálního obsahu před uplynutím 14 dnů od uzavření smlouvy. Beru na vědomí, že zahájením jeho zpřístupnění za těchto podmínek ztrácím právo odstoupit od smlouvy ve 14denní lhůtě.', '2026-10-26 20:44:58'),
(19, 38, 1, '2026-10-20-v1', 'cs', 'Výslovně souhlasím se zpřístupněním zakoupeného digitálního obsahu před uplynutím 14 dnů od uzavření smlouvy. Beru na vědomí, že zahájením jeho zpřístupnění za těchto podmínek ztrácím právo odstoupit od smlouvy ve 14denní lhůtě.', '2026-10-26 20:57:52'),
(20, 39, 1, '2026-10-20-v1', 'cs', 'Výslovně souhlasím se zpřístupněním zakoupeného digitálního obsahu před uplynutím 14 dnů od uzavření smlouvy. Beru na vědomí, že zahájením jeho zpřístupnění za těchto podmínek ztrácím právo odstoupit od smlouvy ve 14denní lhůtě.', '2026-10-26 22:26:07'),
(21, 40, 1, '2026-10-20-v1', 'cs', 'Výslovně souhlasím se zpřístupněním zakoupeného digitálního obsahu před uplynutím 14 dnů od uzavření smlouvy. Beru na vědomí, že zahájením jeho zpřístupnění za těchto podmínek ztrácím právo odstoupit od smlouvy ve 14denní lhůtě.', '2026-10-26 22:28:30'),
(22, 41, 1, '2026-10-20-v1', 'cs', 'Výslovně souhlasím se zpřístupněním zakoupeného digitálního obsahu před uplynutím 14 dnů od uzavření smlouvy. Beru na vědomí, že zahájením jeho zpřístupnění za těchto podmínek ztrácím právo odstoupit od smlouvy ve 14denní lhůtě.', '2026-10-26 22:31:42'),
(23, 42, 1, '2026-10-20-v1', 'cs', 'Výslovně souhlasím se zpřístupněním zakoupeného digitálního obsahu před uplynutím 14 dnů od uzavření smlouvy. Beru na vědomí, že zahájením jeho zpřístupnění za těchto podmínek ztrácím právo odstoupit od smlouvy ve 14denní lhůtě.', '2026-10-26 22:53:45'),
(24, 43, 1, '2026-10-20-v1', 'cs', 'Výslovně souhlasím se zpřístupněním zakoupeného digitálního obsahu před uplynutím 14 dnů od uzavření smlouvy. Beru na vědomí, že zahájením jeho zpřístupnění za těchto podmínek ztrácím právo odstoupit od smlouvy ve 14denní lhůtě.', '2026-10-26 22:58:37'),
(25, 44, 1, '2026-10-20-v1', 'cs', 'Výslovně souhlasím se zpřístupněním zakoupeného digitálního obsahu před uplynutím 14 dnů od uzavření smlouvy. Beru na vědomí, že zahájením jeho zpřístupnění za těchto podmínek ztrácím právo odstoupit od smlouvy ve 14denní lhůtě.', '2026-10-26 23:23:19'),
(26, 45, 1, '2026-10-20-v1', 'cs', 'Výslovně souhlasím se zpřístupněním zakoupeného digitálního obsahu před uplynutím 14 dnů od uzavření smlouvy. Beru na vědomí, že zahájením jeho zpřístupnění za těchto podmínek ztrácím právo odstoupit od smlouvy ve 14denní lhůtě.', '2026-10-26 23:53:34');

-- --------------------------------------------------------

--
-- Struktura tabulky `shop_order_fulfillment`
--

CREATE TABLE `shop_order_fulfillment` (
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `fulfillment_status` enum('new','processing','shipped','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new',
  `carrier` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tracking_number` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin_note` text COLLATE utf8mb4_unicode_ci,
  `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
  `shipped_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Vypisuji data pro tabulku `shop_order_fulfillment`
--

INSERT INTO `shop_order_fulfillment` (`order_id`, `fulfillment_status`, `carrier`, `tracking_number`, `admin_note`, `updated_by`, `shipped_at`, `completed_at`, `created_at`, `updated_at`) VALUES
(5, 'completed', NULL, NULL, NULL, NULL, NULL, '2026-10-16 10:00:39', '2026-10-16 08:00:39', '2026-10-16 08:00:39'),
(6, 'completed', NULL, NULL, NULL, NULL, NULL, '2026-10-16 12:00:01', '2026-10-16 10:00:01', '2026-10-16 10:00:01'),
(10, 'completed', NULL, NULL, NULL, NULL, NULL, '2026-10-16 15:50:09', '2026-10-16 13:50:09', '2026-10-16 13:50:09'),
(12, 'completed', NULL, NULL, NULL, NULL, NULL, '2026-10-16 17:43:43', '2026-10-16 15:43:43', '2026-10-16 15:43:43'),
(20, 'completed', NULL, NULL, NULL, NULL, NULL, '2026-10-21 21:36:45', '2026-10-21 19:36:45', '2026-10-21 19:36:45'),
(21, 'completed', NULL, NULL, NULL, NULL, NULL, '2026-10-25 14:07:12', '2026-10-25 13:07:12', '2026-10-25 13:07:12'),
(22, 'completed', NULL, NULL, NULL, NULL, NULL, '2026-10-25 20:47:03', '2026-10-25 19:47:03', '2026-10-25 19:47:03'),
(24, 'completed', NULL, NULL, NULL, NULL, NULL, '2026-10-25 21:02:10', '2026-10-25 20:02:10', '2026-10-25 20:02:10'),
(25, 'completed', NULL, NULL, NULL, NULL, NULL, '2026-10-25 21:10:25', '2026-10-25 20:10:25', '2026-10-25 20:10:25'),
(26, 'completed', NULL, NULL, NULL, NULL, NULL, '2026-10-25 21:31:25', '2026-10-25 20:31:25', '2026-10-25 20:31:25'),
(27, 'completed', NULL, NULL, NULL, NULL, NULL, '2026-10-25 21:43:02', '2026-10-25 20:43:02', '2026-10-25 20:43:02'),
(28, 'completed', NULL, NULL, NULL, NULL, NULL, '2026-10-25 22:06:57', '2026-10-25 21:06:57', '2026-10-25 21:06:57'),
(29, 'completed', NULL, NULL, NULL, NULL, NULL, '2026-10-25 22:21:22', '2026-10-25 21:21:22', '2026-10-25 21:21:22'),
(30, 'completed', NULL, NULL, NULL, NULL, NULL, '2026-10-25 22:43:35', '2026-10-25 21:43:35', '2026-10-25 21:43:35'),
(31, 'completed', NULL, NULL, NULL, NULL, NULL, '2026-10-25 23:09:00', '2026-10-25 22:09:00', '2026-10-25 22:09:00'),
(32, 'completed', NULL, NULL, NULL, NULL, NULL, '2026-10-26 19:49:39', '2026-10-26 18:49:39', '2026-10-26 18:49:39'),
(35, 'completed', NULL, NULL, NULL, NULL, NULL, '2026-10-26 22:31:45', '2026-10-26 21:31:45', '2026-10-26 21:31:45'),
(36, 'completed', NULL, NULL, NULL, NULL, NULL, '2026-10-26 23:26:22', '2026-10-26 22:26:22', '2026-10-26 22:26:22'),
(37, 'completed', NULL, NULL, NULL, NULL, NULL, '2026-10-26 20:45:19', '2026-10-26 19:45:19', '2026-10-26 19:45:19'),
(38, 'completed', NULL, NULL, NULL, NULL, NULL, '2026-10-26 23:42:42', '2026-10-26 22:42:42', '2026-10-26 22:42:42'),
(39, 'completed', NULL, NULL, NULL, NULL, NULL, '2026-10-26 23:42:41', '2026-10-26 22:42:41', '2026-10-26 22:42:41'),
(40, 'completed', NULL, NULL, NULL, NULL, NULL, '2026-10-26 23:42:40', '2026-10-26 22:42:40', '2026-10-26 22:42:40'),
(41, 'completed', NULL, NULL, NULL, NULL, NULL, '2026-10-26 23:42:39', '2026-10-26 22:42:39', '2026-10-26 22:42:39'),
(42, 'completed', NULL, NULL, NULL, NULL, NULL, '2026-10-26 23:42:38', '2026-10-26 22:42:38', '2026-10-26 22:42:38'),
(43, 'completed', NULL, NULL, NULL, NULL, NULL, '2026-10-26 23:42:37', '2026-10-26 22:42:37', '2026-10-26 22:42:37'),
(44, 'completed', NULL, NULL, NULL, NULL, NULL, '2026-10-26 23:42:36', '2026-10-26 22:42:36', '2026-10-26 22:42:36'),
(45, 'completed', NULL, NULL, NULL, NULL, NULL, '2026-10-26 23:53:57', '2026-10-26 22:53:57', '2026-10-26 22:53:57');

-- --------------------------------------------------------

--
-- Struktura tabulky `shop_order_items`
--

CREATE TABLE `shop_order_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `variant_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `variant_kind` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `variant_language_code` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` int(10) UNSIGNED NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `line_total` decimal(12,2) NOT NULL,
  `currency` char(3) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Vypisuji data pro tabulku `shop_order_items`
--

INSERT INTO `shop_order_items` (`id`, `order_id`, `product_id`, `variant_id`, `title`, `variant_kind`, `variant_language_code`, `quantity`, `unit_price`, `line_total`, `currency`) VALUES
(4, 3, 2, 7, 'Poznej sám sebe. Pak pochopíš všechno. — Komunikace se sebou', 'ebook', NULL, 2, 300.00, 600.00, 'CZK'),
(5, 3, 5, 27, 'Život', 'ebook', NULL, 1, 300.00, 300.00, 'CZK'),
(6, 4, 9, 74, 'Mezi psychopaty', 'ebook', NULL, 1, 295.00, 295.00, 'CZK'),
(7, 5, 4, 42, 'Láska', 'ebook', NULL, 1, 300.00, 300.00, 'CZK'),
(8, 6, 4, 24, 'Láska', 'audio', NULL, 1, 300.00, 300.00, 'CZK'),
(9, 7, 4, 42, 'Láska', 'ebook', NULL, 1, 300.00, 300.00, 'CZK'),
(10, 8, 4, 42, 'Láska', 'ebook', NULL, 1, 300.00, 300.00, 'CZK'),
(11, 9, 4, 42, 'Láska', 'ebook', NULL, 1, 300.00, 300.00, 'CZK'),
(12, 10, 4, 24, 'Láska', 'audio', NULL, 1, 300.00, 300.00, 'CZK'),
(13, 11, 4, 42, 'Láska', 'ebook', NULL, 5, 300.00, 1500.00, 'CZK'),
(14, 12, 4, 42, 'Láska', 'ebook', NULL, 2, 300.00, 600.00, 'CZK'),
(15, 13, 4, 24, 'Láska', 'audio', NULL, 3, 300.00, 900.00, 'CZK'),
(16, 14, 4, 41, 'Láska', 'print', NULL, 2, 300.00, 600.00, 'CZK'),
(17, 15, 4, 24, 'Láska', 'audio', NULL, 1, 300.00, 300.00, 'CZK'),
(18, 16, 4, 41, 'Láska', 'print', NULL, 1, 300.00, 300.00, 'CZK'),
(19, 17, 4, 42, 'Láska', 'ebook', NULL, 1, 300.00, 300.00, 'CZK'),
(20, 18, 5, 35, 'Život', 'print', NULL, 1, 300.00, 300.00, 'CZK'),
(21, 19, 4, 42, 'Láska', 'ebook', NULL, 1, 300.00, 300.00, 'CZK'),
(22, 20, 1, 182, 'Poznej sám sebe. Pak pochopíš všechno.', 'ebook', 'cs', 1, 199.00, 199.00, 'CZK'),
(23, 21, 1, 190, 'Poznej sám sebe. Pak pochopíš všechno.', 'ebook', 'sk', 1, 199.00, 199.00, 'CZK'),
(24, 22, 13, 197, 'Když domov bolí', 'ebook', 'cs', 1, 270.00, 270.00, 'CZK'),
(25, 23, 12, 211, 'Dva životy jedné lásky I - Kóma', 'ebook', 'en', 1, 329.00, 329.00, 'CZK'),
(26, 24, 13, 197, 'Když domov bolí', 'ebook', 'cs', 1, 270.00, 270.00, 'CZK'),
(27, 25, 1, 182, 'Poznej sám sebe. Pak pochopíš všechno.', 'ebook', 'cs', 1, 199.00, 199.00, 'CZK'),
(28, 26, 10, 193, 'Dva životy jedné lásky III - Skutečnost', 'ebook', 'cs', 1, 199.00, 199.00, 'CZK'),
(29, 27, 13, 197, 'Když domov bolí', 'ebook', 'cs', 1, 270.00, 270.00, 'CZK'),
(30, 28, 12, 212, 'Dva životy jedné lásky I - Kóma', 'ebook', 'de', 1, 329.00, 329.00, 'CZK'),
(31, 29, 12, 195, 'Dva životy jedné lásky I - Kóma', 'ebook', 'cs', 1, 329.00, 329.00, 'CZK'),
(32, 30, 11, 194, 'Dva životy jedné lásky II - Probuzení', 'ebook', 'cs', 1, 190.00, 190.00, 'CZK'),
(33, 31, 13, 197, 'Když domov bolí', 'ebook', 'cs', 1, 270.00, 270.00, 'CZK'),
(34, 32, 13, 200, 'Když domov bolí', 'ebook', 'en', 1, 270.00, 270.00, 'CZK'),
(35, 33, 13, 197, 'Když domov bolí', 'ebook', 'cs', 1, 270.00, 270.00, 'CZK'),
(36, 34, 13, 197, 'Když domov bolí', 'ebook', 'cs', 1, 270.00, 270.00, 'CZK'),
(37, 35, 12, 195, 'Dva životy jedné lásky I - Kóma', 'ebook', 'cs', 1, 329.00, 329.00, 'CZK'),
(38, 36, 12, 195, 'Dva životy jedné lásky I - Kóma', 'ebook', 'cs', 1, 329.00, 329.00, 'CZK'),
(39, 37, 10, 193, 'Dva životy jedné lásky III - Skutečnost', 'ebook', 'cs', 1, 199.00, 199.00, 'CZK'),
(40, 38, 10, 193, 'Dva životy jedné lásky III - Skutečnost', 'ebook', 'cs', 1, 199.00, 199.00, 'CZK'),
(41, 39, 13, 197, 'Když domov bolí', 'ebook', 'cs', 1, 270.00, 270.00, 'CZK'),
(42, 40, 13, 197, 'Když domov bolí', 'ebook', 'cs', 1, 270.00, 270.00, 'CZK'),
(43, 41, 13, 197, 'Když domov bolí', 'ebook', 'cs', 1, 270.00, 270.00, 'CZK'),
(44, 42, 1, 182, 'Poznej sám sebe. Pak pochopíš všechno.', 'ebook', 'cs', 1, 199.00, 199.00, 'CZK'),
(45, 43, 10, 193, 'Dva životy jedné lásky III - Skutečnost', 'ebook', 'cs', 1, 199.00, 199.00, 'CZK'),
(46, 44, 13, 197, 'Když domov bolí', 'ebook', 'cs', 1, 270.00, 270.00, 'CZK'),
(47, 45, 13, 197, 'Když domov bolí', 'ebook', 'cs', 1, 270.00, 270.00, 'CZK');

-- --------------------------------------------------------

--
-- Struktura tabulky `shop_payments`
--

CREATE TABLE `shop_payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `provider` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'stripe',
  `environment` enum('test','live') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `checkout_session_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_intent_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount_minor` bigint(20) UNSIGNED NOT NULL,
  `currency` char(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Vypisuji data pro tabulku `shop_payments`
--

INSERT INTO `shop_payments` (`id`, `order_id`, `provider`, `environment`, `status`, `checkout_session_id`, `payment_intent_id`, `amount_minor`, `currency`, `created_at`, `updated_at`) VALUES
(1, 3, 'stripe', 'test', 'paid', 'cs_test_a1OLdDSDgcbDrTGajQky0npoWD8J5bJXa8ZxbqDqzlsdFqgu1gk1bIlJTS', 'pi_3UQk67RrdttDzBlz1tddaksN', 90000, 'CZK', '2026-10-15 08:31:20', '2026-10-15 08:33:13'),
(2, 4, 'stripe', 'test', 'paid', 'cs_test_a1VsCziknO0GLMPio6wjONYOTivTJ2ngIbaFed4fN2ftf7xCqtluv7zZmA', 'pi_3UQmmJRrdttDzBlz1SDrgpvV', 29500, 'CZK', '2026-10-15 11:23:51', '2026-10-15 11:24:57'),
(3, 5, 'stripe', 'test', 'paid', 'cs_test_a1EDnitoMQOvk3p6h8u9JDTmCdUwvC2I2SzRWcs2ChubPiVLe6aT4vuuCi', 'pi_3UR63fRrdttDzBlz0GKqt2zs', 30000, 'CZK', '2026-10-16 07:59:33', '2026-10-16 08:00:09'),
(4, 6, 'stripe', 'test', 'paid', 'cs_test_a1Ar1kCmTJJ33C7Ttzma0WWLQdvDHzch5w3Dhyxk4VE4usgPiyKUkS2ZrU', 'pi_3UR7uiRrdttDzBlz1LfDP7EI', 30000, 'CZK', '2026-10-16 09:57:33', '2026-10-16 09:59:03'),
(5, 7, 'stripe', 'test', 'paid', 'cs_test_a1t8xOwzWGPnbskDb9GB2qbFX0acpioo1ZKjkvweCGs1QfqsHWkhBWuLG6', 'pi_3URB5dRrdttDzBlz0akH6nSU', 30000, 'CZK', '2026-10-16 13:22:17', '2026-10-16 13:22:31'),
(6, 8, 'stripe', 'test', 'paid', 'cs_test_a1jmbI9rhPxltqGVGP7RW7ML49rMrkA7fsSbNJZVbWNi5rTv1zVNuiOisK', 'pi_3URBDkRrdttDzBlz1fIUztL9', 30000, 'CZK', '2026-10-16 13:30:26', '2026-10-16 13:30:54'),
(7, 9, 'stripe', 'test', 'paid', 'cs_test_a1TYwyjF0G9DVjnatJoZFRhbmPl20flry8nDNeg9rzO3jQi8Hf8RIrWALM', 'pi_3URBKKRrdttDzBlz0ePtzcGX', 30000, 'CZK', '2026-10-16 13:37:23', '2026-10-16 13:37:42'),
(8, 10, 'stripe', 'test', 'paid', 'cs_test_a1Pm1zD30mB5UuOd3VNNxqZpNS0q4ULxbKE6GRU0xLMpjB46lkbm2eJ8sp', 'pi_3URBObRrdttDzBlz1pDIqINJ', 30000, 'CZK', '2026-10-16 13:41:44', '2026-10-16 13:42:07'),
(9, 11, 'stripe', 'test', 'paid', 'cs_test_a1mDChdhOOH0zPVFDd7XmqhARB6nW5dZR6mdPLYhxtp2vZ8Aq5lMazxCLq', 'pi_3URBfURrdttDzBlz0KfdnR7c', 150000, 'CZK', '2026-10-16 13:59:10', '2026-10-16 13:59:33'),
(10, 12, 'stripe', 'test', 'paid', 'cs_test_a1pLipaeLNbJ3jzFV2EV7LGY5tVBETqvNJbBscurzRHH6AjtodM0D2UxUq', 'pi_3URD0HRrdttDzBlz1cxGVkql', 60000, 'CZK', '2026-10-16 15:24:54', '2026-10-16 15:25:07'),
(11, 16, 'stripe', 'test', 'paid', 'cs_test_a1KFXkCQYzF7esDBZFPsosl9icFyDZZJ7QQHwk2vBSghRb7gqPsQLLZJeO', 'pi_3URDw4RrdttDzBlz16g0s2sC', 30000, 'CZK', '2026-10-16 16:24:39', '2026-10-16 16:24:50'),
(12, 17, 'stripe', 'test', 'expired', 'cs_test_a138rDrlgUNZjgPihS1RVnZWEzmhpZ8gB86VagFHKGuV0zoiLmEBa0MuX5', NULL, 30000, 'CZK', '2026-10-16 16:26:01', '2026-10-17 16:26:02'),
(13, 20, 'stripe', 'test', 'paid', 'cs_test_a1ShgOAeTJtsV4XDhby3RdLlZ23yJfayv9PN9xfo8dqWs79goL2hcoNxGA', 'pi_3UT5JXRrdttDzBlz0vqPEoVu', 19900, 'CZK', '2026-10-21 19:35:46', '2026-10-21 19:36:45'),
(14, 21, 'stripe', 'test', 'paid', 'cs_test_a1Eerb6hTYrUckj8Mx9JBaixuzjiTkB8ntLCdyff5c8oOqbPKk0UbYmhch', 'pi_3UUR8lRrdttDzBlz12TOYQfS', 19900, 'CZK', '2026-10-25 13:06:39', '2026-10-25 13:07:12'),
(15, 22, 'stripe', 'test', 'paid', 'cs_test_a1fLUIE6qbPoNMbdcVeXzEw1TB1MK1QWRzKnfnkdKQIj0t5eLqIOYBnAVN', 'pi_3UUXNhRrdttDzBlz00gPGSqY', 27000, 'CZK', '2026-10-25 19:46:40', '2026-10-25 19:47:03'),
(16, 23, 'stripe', 'test', 'paid', 'cs_test_a1zC2iAo2AQQjiM5yb62jNboWtLMJOqw0Fzz4TpyMwD84HAYdZCTMlhxW8', 'pi_3UUXXARrdttDzBlz0UdZA9tH', 32900, 'CZK', '2026-10-25 19:56:29', '2026-10-25 19:56:50'),
(17, 24, 'stripe', 'test', 'paid', 'cs_test_a1ltW0K0RQbdJKTn8VLVy6B2CNxr6KN4hxtrA8cdm4q8gZy7S60DGBeduE', 'pi_3UUXcKRrdttDzBlz0NFG9OR6', 27000, 'CZK', '2026-10-25 20:01:50', '2026-10-25 20:02:10'),
(18, 25, 'stripe', 'test', 'paid', 'cs_test_a1tv0VJMsMqXXkRymwMf1YvglQsIjZ6Qs4MdpkU5rp7W8LsQwzQsHZ9HOr', 'pi_3UUXkKRrdttDzBlz0WxUarge', 19900, 'CZK', '2026-10-25 20:10:08', '2026-10-25 20:10:25'),
(19, 26, 'stripe', 'test', 'paid', 'cs_test_a1dImffwVQ8RGsA31yq09nO4EWrkInXwJrOX1D5KImdbfssXPY6AsXuR37', 'pi_3UUY4dRrdttDzBlz1eKI9IYB', 19900, 'CZK', '2026-10-25 20:31:06', '2026-10-25 20:31:25'),
(20, 27, 'stripe', 'test', 'paid', 'cs_test_a1dq3JLTJhB1tPn6ZbDRcBiOlst65n3h1r4dM56IvuQFkcXVyz0PLbWdmi', 'pi_3UUYFsRrdttDzBlz14Q3Y9Wk', 27000, 'CZK', '2026-10-25 20:42:38', '2026-10-25 20:43:02'),
(21, 28, 'stripe', 'test', 'paid', 'cs_test_a17crC5mtsxeaiFKrSP9XiPLEKNenC6acpON0sfgvEOphZ7hAGvtZOqSlI', 'pi_3UUYIkRrdttDzBlz1m8DVmpn', 32900, 'CZK', '2026-10-25 20:45:46', '2026-10-25 20:46:00'),
(22, 29, 'stripe', 'test', 'paid', 'cs_test_a1BxyfrU69j62FSIGPXPGh8dhniPENVccq7j4q07II4QkXlwnchjIf5tBJ', 'pi_3UUYqyRrdttDzBlz0jRVGuAU', 32900, 'CZK', '2026-10-25 21:21:04', '2026-10-25 21:21:22'),
(23, 30, 'stripe', 'test', 'paid', 'cs_test_a1iVnlgN0aJtS5JVkmrkxxH7d5M9NHAwKZjdsJfHEgDIXtYEvKhopM34M9', 'pi_3UUZCURrdttDzBlz1mHGKWUe', 19000, 'CZK', '2026-10-25 21:43:16', '2026-10-25 21:43:35'),
(24, 31, 'stripe', 'test', 'paid', 'cs_test_a15KXkorvyUTYZ6CJyjYD8rkizwHPvHRutfu34LyFUAmxH4svasicKXQA3', 'pi_3UUZb4RrdttDzBlz1DuCSr3E', 27000, 'CZK', '2026-10-25 22:08:34', '2026-10-25 22:09:00'),
(25, 32, 'stripe', 'test', 'paid', 'cs_test_a1KVwqAnistsKuGnQCipKnEY2RJ4jvbnx3fC9Zu1n2upwNY3E1pdFQSCp8', 'pi_3UUswmRrdttDzBlz1BDPFQVK', 27000, 'CZK', '2026-10-26 18:48:13', '2026-10-26 18:49:39'),
(26, 35, 'stripe', 'test', 'paid', 'cs_test_a1VPsK7fqdn2Ru2835jQY9d3a1LPB97MfxvUHoJs9DQY2Ry4rEJaInWMEP', 'pi_3UUtN6RrdttDzBlz0OpttsxV', 32900, 'CZK', '2026-10-26 19:14:50', '2026-10-26 21:31:44'),
(27, 36, 'stripe', 'test', 'paid', 'cs_test_a1OoobJamYZ1UPZlyqpUymaU3XeJlpgsVEtU1f8Gr3xJE16fe17GZK6QaQ', 'pi_3UUtYBRrdttDzBlz1TqbgEUM', 32900, 'CZK', '2026-10-26 19:27:03', '2026-10-26 22:26:21'),
(28, 37, 'stripe', 'test', 'paid', 'cs_test_a1u8tBz5GoX9dYtpgyrQASok1OXNjhQq2IX39iP1563RThwCxaICdg6xnn', 'pi_3UUtpZRrdttDzBlz0Q5jeHNq', 19900, 'CZK', '2026-10-26 19:44:59', '2026-10-26 19:45:18'),
(29, 38, 'stripe', 'test', 'paid', 'cs_test_a1AEM1WRbfbQygTJWppKJguDLdnpw1V4HYAbSgnFEMrqDUPPNm1e00T25O', 'pi_3UUu24RrdttDzBlz01IP8eCL', 19900, 'CZK', '2026-10-26 19:57:52', '2026-10-26 22:42:42'),
(30, 39, 'stripe', 'test', 'paid', 'cs_test_a1kq0TBPf1nTKFQZNTtSXhEo7yK3i9rYLM9f2jLUnr7DGfJDkYCM7nbjz7', 'pi_3UUvPTRrdttDzBlz0oaIIF5o', 27000, 'CZK', '2026-10-26 21:26:10', '2026-10-26 22:42:41'),
(31, 40, 'stripe', 'test', 'paid', 'cs_test_a1QsOPjAZpn6eNohGiH7bChJWzlxj7umf1WgQvzK3T5uH5EOeGuQBu6T6k', 'pi_3UUvRmRrdttDzBlz0ybh02O5', 27000, 'CZK', '2026-10-26 21:28:32', '2026-10-26 22:42:40'),
(32, 41, 'stripe', 'test', 'paid', 'cs_test_a1VOVe0DofOE4NypNvm4SwBH3FXTG6kl2w1XTcvbzEVhOeW86isIYrVpAy', 'pi_3UUvV2RrdttDzBlz0gQNCsvG', 27000, 'CZK', '2026-10-26 21:31:46', '2026-10-26 22:42:39'),
(33, 42, 'stripe', 'test', 'paid', 'cs_test_a1id4gRY3lSYK3NcFLdNX3YI0nQ75u5RTztBbVG1pbtUsLAByfWOCx1W1T', 'pi_3UUvqIRrdttDzBlz0fZv8Qoe', 19900, 'CZK', '2026-10-26 21:53:48', '2026-10-26 22:42:38'),
(34, 43, 'stripe', 'test', 'paid', 'cs_test_a19xwKG82G1ywdh3I66zRRpSIFnmI6koj6Ihoopa2uxeZ8CRfQcxkjOLEu', 'pi_3UUvv3RrdttDzBlz0ucrorbt', 19900, 'CZK', '2026-10-26 21:58:42', '2026-10-26 22:42:37'),
(35, 44, 'stripe', 'test', 'paid', 'cs_test_a1GSigONMEy2XetT40ht2k1rX636f80SYj56Uy9427gi2O8tOB0qcQHHA0', 'pi_3UUwIqRrdttDzBlz1F3eeSsD', 27000, 'CZK', '2026-10-26 22:23:19', '2026-10-26 22:42:36'),
(36, 45, 'stripe', 'test', 'paid', 'cs_test_a1pwfwKY7TRCMZAHrRedXrIuITMIWjkUmAHtac7cZnQd6SIcz2HY7cE8kP', 'pi_3UUwm7RrdttDzBlz0CtUKOxL', 27000, 'CZK', '2026-10-26 22:53:35', '2026-10-26 22:53:56');

-- --------------------------------------------------------

--
-- Struktura tabulky `shop_payment_events`
--

CREATE TABLE `shop_payment_events` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `stripe_event_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `environment` enum('test','live') COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_id` bigint(20) UNSIGNED DEFAULT NULL,
  `processed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `shop_variant_currency_prices`
--

CREATE TABLE `shop_variant_currency_prices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `variant_id` bigint(20) UNSIGNED NOT NULL,
  `currency` char(3) CHARACTER SET ascii NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `sale_price` decimal(12,2) DEFAULT NULL,
  `source_price_czk` decimal(12,2) NOT NULL,
  `exchange_rate_czk_per_unit` decimal(18,8) NOT NULL,
  `rate_date` date NOT NULL,
  `generated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `locked_until` datetime DEFAULT NULL COMMENT 'Pro jiz vytvorenou objednavku pouzijte snapshot ceny v objednavce'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jmeno` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prijmeni` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `foto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `heslo_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('superadmin','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admin',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Vypisuji data pro tabulku `users`
--

INSERT INTO `users` (`id`, `username`, `jmeno`, `prijmeni`, `email`, `foto`, `heslo_hash`, `role`, `is_active`, `created`, `updated`, `last_login`) VALUES
(1, 'milan smieško', 'Milan', 'Smieško', 'serp24.cz@gmail.com', NULL, '$2y$10$3xH3G0YUwUYSTNGlYuo0B.NxqYv8yhwQRBkYT8K4K910omas.OqQC', 'superadmin', 1, '2026-10-07 11:37:46', '2026-10-27 16:01:02', '2026-10-27 16:01:02'),
(2, 'Štefan Rác', 'Štefan', 'Rac', 'stefan.rac1@gmail.com', NULL, '$2y$10$iAN9mhJdiMjU1Vxu7ZPUyOHRZJIYng8RNB9dIGjkNRyshE5PF/A8W', 'admin', 1, '2026-10-13 16:34:20', '2026-10-20 11:33:38', '2026-10-20 11:33:38');

--
-- Indexy pro exportované tabulky
--

--
-- Indexy pro tabulku `admin_permissions`
--
ALTER TABLE `admin_permissions`
  ADD PRIMARY KEY (`permission_key`);

--
-- Indexy pro tabulku `admin_roles`
--
ALTER TABLE `admin_roles`
  ADD PRIMARY KEY (`role_key`);

--
-- Indexy pro tabulku `admin_role_permissions`
--
ALTER TABLE `admin_role_permissions`
  ADD PRIMARY KEY (`role_key`,`permission_key`),
  ADD KEY `fk_rac_rp_perm` (`permission_key`);

--
-- Indexy pro tabulku `admin_user_roles`
--
ALTER TABLE `admin_user_roles`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `fk_rac_ur_role` (`role_key`);

--
-- Indexy pro tabulku `billing_customers`
--
ALTER TABLE `billing_customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_billing_customer_provider_user` (`provider`,`user_id`),
  ADD UNIQUE KEY `uq_billing_customer_provider_customer` (`provider`,`provider_customer_id`);

--
-- Indexy pro tabulku `billing_payments`
--
ALTER TABLE `billing_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_billing_payment_provider_session` (`provider`,`provider_checkout_session_id`),
  ADD UNIQUE KEY `uq_billing_payment_provider_invoice` (`provider`,`provider_invoice_id`),
  ADD KEY `idx_billing_payment_user` (`user_id`),
  ADD KEY `idx_billing_payment_status` (`status`),
  ADD KEY `idx_billing_payment_intent` (`provider_payment_intent_id`),
  ADD KEY `idx_billing_payment_subscription` (`provider_subscription_id`),
  ADD KEY `idx_billing_payment_product_code` (`product_code`);

--
-- Indexy pro tabulku `billing_products`
--
ALTER TABLE `billing_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_billing_products_code` (`code`),
  ADD KEY `idx_billing_products_active` (`active`),
  ADD KEY `idx_billing_products_type` (`payment_type`);

--
-- Indexy pro tabulku `billing_settings`
--
ALTER TABLE `billing_settings`
  ADD PRIMARY KEY (`setting_key`),
  ADD KEY `idx_billing_settings_secret` (`is_secret`),
  ADD KEY `idx_billing_settings_updated_by` (`updated_by`);

--
-- Indexy pro tabulku `billing_subscriptions`
--
ALTER TABLE `billing_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_billing_subscription_provider_id` (`provider`,`provider_subscription_id`),
  ADD KEY `idx_billing_subscription_user` (`user_id`),
  ADD KEY `idx_billing_subscription_status` (`status`),
  ADD KEY `idx_billing_subscription_checkout` (`checkout_session_id`),
  ADD KEY `idx_billing_subscription_product` (`product_code`);

--
-- Indexy pro tabulku `billing_webhook_events`
--
ALTER TABLE `billing_webhook_events`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_billing_webhook_provider_event` (`provider`,`event_id`),
  ADD KEY `idx_billing_webhook_type` (`event_type`),
  ADD KEY `idx_billing_webhook_status` (`status`);

--
-- Indexy pro tabulku `book_edition_languages`
--
ALTER TABLE `book_edition_languages`
  ADD PRIMARY KEY (`code`),
  ADD KEY `idx_active_sort` (`active`,`sort_order`,`code`);

--
-- Indexy pro tabulku `contact_blocked_ips`
--
ALTER TABLE `contact_blocked_ips`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_contact_ip` (`ip_address`);

--
-- Indexy pro tabulku `contact_form_settings`
--
ALTER TABLE `contact_form_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexy pro tabulku `contact_form_subjects`
--
ALTER TABLE `contact_form_subjects`
  ADD PRIMARY KEY (`id`);

--
-- Indexy pro tabulku `eshop_stock_movements`
--
ALTER TABLE `eshop_stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_variant_created` (`variant_id`,`created_at`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexy pro tabulku `fakturace_doklady`
--
ALTER TABLE `fakturace_doklady`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_rac_faktura_order` (`order_id`),
  ADD UNIQUE KEY `uq_rac_faktura_cislo` (`cislo_dokladu`),
  ADD KEY `ix_rac_faktura_vystaveni` (`datum_vystaveni`),
  ADD KEY `ix_rac_faktura_odberatel` (`odberatel_email`);

--
-- Indexy pro tabulku `fakturace_nastaveni`
--
ALTER TABLE `fakturace_nastaveni`
  ADD PRIMARY KEY (`id`);

--
-- Indexy pro tabulku `fakturace_polozky`
--
ALTER TABLE `fakturace_polozky`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_rac_faktura_polozky` (`doklad_id`,`poradi`);

--
-- Indexy pro tabulku `languages`
--
ALTER TABLE `languages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_languages_code` (`code`),
  ADD KEY `idx_languages_active_sort` (`active`,`sort_order`),
  ADD KEY `idx_languages_default` (`is_default`);

--
-- Indexy pro tabulku `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_pages_code` (`code`),
  ADD KEY `idx_pages_active_sort` (`active`,`sort_order`);

--
-- Indexy pro tabulku `page_sections`
--
ALTER TABLE `page_sections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_page_section` (`page_id`,`code`),
  ADD KEY `idx_page_sections_sort` (`page_id`,`active`,`sort_order`);

--
-- Indexy pro tabulku `page_section_fields`
--
ALTER TABLE `page_section_fields`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_section_field` (`section_id`,`code`),
  ADD KEY `idx_section_fields_sort` (`section_id`,`sort_order`);

--
-- Indexy pro tabulku `page_section_field_translations`
--
ALTER TABLE `page_section_field_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_field_translation` (`field_id`,`language_id`),
  ADD KEY `idx_field_translation_language` (`language_id`);

--
-- Indexy pro tabulku `page_translations`
--
ALTER TABLE `page_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_page_translation` (`page_id`,`language_id`),
  ADD KEY `idx_page_translation_language` (`language_id`);

--
-- Indexy pro tabulku `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_products_slug` (`slug`),
  ADD KEY `idx_products_status` (`status`,`sort_order`);

--
-- Indexy pro tabulku `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_category_code` (`code`);

--
-- Indexy pro tabulku `product_category_translations`
--
ALTER TABLE `product_category_translations`
  ADD PRIMARY KEY (`category_id`,`language_id`),
  ADD KEY `fk_pct_language` (`language_id`);

--
-- Indexy pro tabulku `product_files`
--
ALTER TABLE `product_files`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_product_files_key` (`storage_key`),
  ADD KEY `idx_product_files_variant` (`variant_id`,`purpose`),
  ADD KEY `idx_rac_product_file_publication` (`variant_id`,`purpose`,`is_published`);

--
-- Indexy pro tabulku `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_images` (`product_id`,`is_primary`,`sort_order`),
  ADD KEY `idx_pi_product_language` (`product_id`,`language_code`,`is_primary`,`sort_order`);

--
-- Indexy pro tabulku `product_translations`
--
ALTER TABLE `product_translations`
  ADD PRIMARY KEY (`product_id`,`language_id`),
  ADD KEY `fk_pt_language` (`language_id`);

--
-- Indexy pro tabulku `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_variant_sku` (`sku`),
  ADD UNIQUE KEY `uq_variant_product_kind_language` (`product_id`,`kind`,`language_code`);

--
-- Indexy pro tabulku `rac_digital_admin_hidden_orders`
--
ALTER TABLE `rac_digital_admin_hidden_orders`
  ADD PRIMARY KEY (`order_id`);

--
-- Indexy pro tabulku `rac_hidden_test_payments`
--
ALTER TABLE `rac_hidden_test_payments`
  ADD PRIMARY KEY (`payment_id`);

--
-- Indexy pro tabulku `rac_legal_documents`
--
ALTER TABLE `rac_legal_documents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_rac_legal_document_key` (`document_key`);

--
-- Indexy pro tabulku `rac_legal_document_versions`
--
ALTER TABLE `rac_legal_document_versions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_rac_legal_version` (`document_id`,`language_code`,`version_number`),
  ADD KEY `idx_rac_legal_public` (`document_id`,`language_code`,`status`,`effective_from`);

--
-- Indexy pro tabulku `rac_order_legal_acceptances`
--
ALTER TABLE `rac_order_legal_acceptances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_rac_order_legal_acceptance` (`order_id`,`legal_version_id`,`acceptance_type`),
  ADD KEY `idx_rac_legal_acceptance_order` (`order_id`),
  ADD KEY `fk_rac_legal_acceptance_version` (`legal_version_id`);

--
-- Indexy pro tabulku `shipping_methods`
--
ALTER TABLE `shipping_methods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_shipping_code` (`code`);

--
-- Indexy pro tabulku `shipping_method_translations`
--
ALTER TABLE `shipping_method_translations`
  ADD PRIMARY KEY (`method_id`,`language_id`),
  ADD KEY `fk_smt_language` (`language_id`);

--
-- Indexy pro tabulku `shipping_rates`
--
ALTER TABLE `shipping_rates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_shipping_rate` (`method_id`,`country_code`,`currency`);

--
-- Indexy pro tabulku `shop_currency_rates`
--
ALTER TABLE `shop_currency_rates`
  ADD PRIMARY KEY (`currency`);

--
-- Indexy pro tabulku `shop_delivery_email_templates`
--
ALTER TABLE `shop_delivery_email_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexy pro tabulku `shop_digital_access`
--
ALTER TABLE `shop_digital_access`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_order_file` (`order_id`,`file_id`),
  ADD UNIQUE KEY `uq_token_hash` (`token_hash`),
  ADD KEY `idx_order_access` (`order_id`),
  ADD KEY `fk_access_file` (`file_id`);

--
-- Indexy pro tabulku `shop_digital_mail_log`
--
ALTER TABLE `shop_digital_mail_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rac_digital_mail_order` (`order_id`,`created_at`);

--
-- Indexy pro tabulku `shop_digital_retry_log`
--
ALTER TABLE `shop_digital_retry_log`
  ADD PRIMARY KEY (`order_id`);

--
-- Indexy pro tabulku `shop_orders`
--
ALTER TABLE `shop_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_shop_orders_created` (`created_at`),
  ADD KEY `idx_shop_orders_email` (`email`);

--
-- Indexy pro tabulku `shop_order_digital_consents`
--
ALTER TABLE `shop_order_digital_consents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_rac_digital_consent_order` (`order_id`),
  ADD KEY `idx_rac_digital_consent_at` (`consented_at`);

--
-- Indexy pro tabulku `shop_order_fulfillment`
--
ALTER TABLE `shop_order_fulfillment`
  ADD PRIMARY KEY (`order_id`);

--
-- Indexy pro tabulku `shop_order_items`
--
ALTER TABLE `shop_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_soi_order` (`order_id`),
  ADD KEY `idx_soi_variant` (`variant_id`);

--
-- Indexy pro tabulku `shop_payments`
--
ALTER TABLE `shop_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_shop_checkout_session` (`checkout_session_id`),
  ADD UNIQUE KEY `uq_shop_payment_intent` (`payment_intent_id`),
  ADD KEY `idx_shop_payments_order` (`order_id`);

--
-- Indexy pro tabulku `shop_payment_events`
--
ALTER TABLE `shop_payment_events`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_shop_stripe_event` (`stripe_event_id`),
  ADD KEY `idx_shop_event_payment` (`payment_id`);

--
-- Indexy pro tabulku `shop_variant_currency_prices`
--
ALTER TABLE `shop_variant_currency_prices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_variant_currency` (`variant_id`,`currency`),
  ADD KEY `idx_currency` (`currency`);

--
-- Indexy pro tabulku `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_username` (`username`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `idx_users_active` (`is_active`);

--
-- AUTO_INCREMENT pro tabulky
--

--
-- AUTO_INCREMENT pro tabulku `billing_customers`
--
ALTER TABLE `billing_customers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `billing_payments`
--
ALTER TABLE `billing_payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `billing_products`
--
ALTER TABLE `billing_products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `billing_subscriptions`
--
ALTER TABLE `billing_subscriptions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `billing_webhook_events`
--
ALTER TABLE `billing_webhook_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `contact_blocked_ips`
--
ALTER TABLE `contact_blocked_ips`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `contact_form_subjects`
--
ALTER TABLE `contact_form_subjects`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `eshop_stock_movements`
--
ALTER TABLE `eshop_stock_movements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pro tabulku `fakturace_doklady`
--
ALTER TABLE `fakturace_doklady`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT pro tabulku `fakturace_polozky`
--
ALTER TABLE `fakturace_polozky`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT pro tabulku `languages`
--
ALTER TABLE `languages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pro tabulku `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pro tabulku `page_sections`
--
ALTER TABLE `page_sections`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pro tabulku `page_section_fields`
--
ALTER TABLE `page_section_fields`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT pro tabulku `page_section_field_translations`
--
ALTER TABLE `page_section_field_translations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1998;

--
-- AUTO_INCREMENT pro tabulku `page_translations`
--
ALTER TABLE `page_translations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=196;

--
-- AUTO_INCREMENT pro tabulku `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT pro tabulku `product_categories`
--
ALTER TABLE `product_categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `product_files`
--
ALTER TABLE `product_files`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- AUTO_INCREMENT pro tabulku `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT pro tabulku `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=220;

--
-- AUTO_INCREMENT pro tabulku `rac_legal_documents`
--
ALTER TABLE `rac_legal_documents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pro tabulku `rac_legal_document_versions`
--
ALTER TABLE `rac_legal_document_versions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT pro tabulku `rac_order_legal_acceptances`
--
ALTER TABLE `rac_order_legal_acceptances`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `shipping_methods`
--
ALTER TABLE `shipping_methods`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pro tabulku `shipping_rates`
--
ALTER TABLE `shipping_rates`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pro tabulku `shop_digital_access`
--
ALTER TABLE `shop_digital_access`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT pro tabulku `shop_digital_mail_log`
--
ALTER TABLE `shop_digital_mail_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT pro tabulku `shop_orders`
--
ALTER TABLE `shop_orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT pro tabulku `shop_order_digital_consents`
--
ALTER TABLE `shop_order_digital_consents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT pro tabulku `shop_order_items`
--
ALTER TABLE `shop_order_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT pro tabulku `shop_payments`
--
ALTER TABLE `shop_payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT pro tabulku `shop_payment_events`
--
ALTER TABLE `shop_payment_events`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `shop_variant_currency_prices`
--
ALTER TABLE `shop_variant_currency_prices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pro tabulku `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Omezení pro exportované tabulky
--

--
-- Omezení pro tabulku `admin_role_permissions`
--
ALTER TABLE `admin_role_permissions`
  ADD CONSTRAINT `fk_rac_rp_perm` FOREIGN KEY (`permission_key`) REFERENCES `admin_permissions` (`permission_key`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_rac_rp_role` FOREIGN KEY (`role_key`) REFERENCES `admin_roles` (`role_key`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `admin_user_roles`
--
ALTER TABLE `admin_user_roles`
  ADD CONSTRAINT `fk_rac_ur_role` FOREIGN KEY (`role_key`) REFERENCES `admin_roles` (`role_key`),
  ADD CONSTRAINT `fk_rac_ur_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `fakturace_polozky`
--
ALTER TABLE `fakturace_polozky`
  ADD CONSTRAINT `fk_rac_faktura_polozky_doklad` FOREIGN KEY (`doklad_id`) REFERENCES `fakturace_doklady` (`id`);

--
-- Omezení pro tabulku `page_sections`
--
ALTER TABLE `page_sections`
  ADD CONSTRAINT `fk_page_sections_page` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `page_section_fields`
--
ALTER TABLE `page_section_fields`
  ADD CONSTRAINT `fk_section_fields_section` FOREIGN KEY (`section_id`) REFERENCES `page_sections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `page_section_field_translations`
--
ALTER TABLE `page_section_field_translations`
  ADD CONSTRAINT `fk_field_translations_field` FOREIGN KEY (`field_id`) REFERENCES `page_section_fields` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_field_translations_language` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON UPDATE CASCADE;

--
-- Omezení pro tabulku `page_translations`
--
ALTER TABLE `page_translations`
  ADD CONSTRAINT `fk_page_translations_language` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_page_translations_page` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Omezení pro tabulku `product_category_translations`
--
ALTER TABLE `product_category_translations`
  ADD CONSTRAINT `fk_pct_category` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pct_language` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`);

--
-- Omezení pro tabulku `product_files`
--
ALTER TABLE `product_files`
  ADD CONSTRAINT `fk_pf_variant` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `fk_pi_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `product_translations`
--
ALTER TABLE `product_translations`
  ADD CONSTRAINT `fk_pt_language` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`),
  ADD CONSTRAINT `fk_pt_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `fk_pv_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `rac_legal_document_versions`
--
ALTER TABLE `rac_legal_document_versions`
  ADD CONSTRAINT `fk_rac_legal_version_document` FOREIGN KEY (`document_id`) REFERENCES `rac_legal_documents` (`id`) ON UPDATE CASCADE;

--
-- Omezení pro tabulku `rac_order_legal_acceptances`
--
ALTER TABLE `rac_order_legal_acceptances`
  ADD CONSTRAINT `fk_rac_legal_acceptance_version` FOREIGN KEY (`legal_version_id`) REFERENCES `rac_legal_document_versions` (`id`) ON UPDATE CASCADE;

--
-- Omezení pro tabulku `shipping_method_translations`
--
ALTER TABLE `shipping_method_translations`
  ADD CONSTRAINT `fk_smt_language` FOREIGN KEY (`language_id`) REFERENCES `languages` (`id`),
  ADD CONSTRAINT `fk_smt_method` FOREIGN KEY (`method_id`) REFERENCES `shipping_methods` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `shipping_rates`
--
ALTER TABLE `shipping_rates`
  ADD CONSTRAINT `fk_sr_method` FOREIGN KEY (`method_id`) REFERENCES `shipping_methods` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `shop_digital_access`
--
ALTER TABLE `shop_digital_access`
  ADD CONSTRAINT `fk_access_file` FOREIGN KEY (`file_id`) REFERENCES `product_files` (`id`),
  ADD CONSTRAINT `fk_access_order` FOREIGN KEY (`order_id`) REFERENCES `shop_orders` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `shop_digital_mail_log`
--
ALTER TABLE `shop_digital_mail_log`
  ADD CONSTRAINT `fk_rac_digital_mail_order` FOREIGN KEY (`order_id`) REFERENCES `shop_orders` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `shop_digital_retry_log`
--
ALTER TABLE `shop_digital_retry_log`
  ADD CONSTRAINT `fk_rac_retry_order` FOREIGN KEY (`order_id`) REFERENCES `shop_orders` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `shop_order_fulfillment`
--
ALTER TABLE `shop_order_fulfillment`
  ADD CONSTRAINT `fk_rac_fulfillment_order` FOREIGN KEY (`order_id`) REFERENCES `shop_orders` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `shop_order_items`
--
ALTER TABLE `shop_order_items`
  ADD CONSTRAINT `fk_soi_order` FOREIGN KEY (`order_id`) REFERENCES `shop_orders` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `shop_payments`
--
ALTER TABLE `shop_payments`
  ADD CONSTRAINT `fk_shop_payments_order` FOREIGN KEY (`order_id`) REFERENCES `shop_orders` (`id`);

--
-- Omezení pro tabulku `shop_payment_events`
--
ALTER TABLE `shop_payment_events`
  ADD CONSTRAINT `fk_shop_event_payment` FOREIGN KEY (`payment_id`) REFERENCES `shop_payments` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
