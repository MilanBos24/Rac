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