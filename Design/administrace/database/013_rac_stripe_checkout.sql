-- RAC customer order payments - requires shop_orders (checkout migration)
CREATE TABLE IF NOT EXISTS shop_payments (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 order_id BIGINT UNSIGNED NOT NULL,
 provider VARCHAR(20) NOT NULL DEFAULT 'stripe',
 environment ENUM('test','live') NOT NULL,
 status VARCHAR(32) NOT NULL DEFAULT 'pending',
 checkout_session_id VARCHAR(255) NULL,
 payment_intent_id VARCHAR(255) NULL,
 amount_minor BIGINT UNSIGNED NOT NULL,
 currency CHAR(3) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_shop_checkout_session (checkout_session_id),
 UNIQUE KEY uq_shop_payment_intent (payment_intent_id),
 KEY idx_shop_payments_order(order_id),
 CONSTRAINT fk_shop_payments_order FOREIGN KEY(order_id) REFERENCES shop_orders(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS shop_payment_events (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 stripe_event_id VARCHAR(255) NOT NULL,
 event_type VARCHAR(100) NOT NULL,
 environment ENUM('test','live') NOT NULL,
 payment_id BIGINT UNSIGNED NULL,
 processed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_shop_stripe_event(stripe_event_id),
 KEY idx_shop_event_payment(payment_id),
 CONSTRAINT fk_shop_event_payment FOREIGN KEY(payment_id) REFERENCES shop_payments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
