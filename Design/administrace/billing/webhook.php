<?php
declare(strict_types=1);

/**
 * RAC Billing - Stripe webhook
 * Verze: 2026-09-16-21.05
 *
 * Podporuje jednorázové platby i subscriptions.
 * Stripe Webhook secrets se nacitaji z billing_settings.
 * Sandbox i Live podpis lze overit na stejne URL.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/settings.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    exit('Database connection is not available.');
}

if (!billing_settings_table_ready($pdo)) {
    http_response_code(500);
    exit('Billing settings table is not available.');
}

try {
    $testWebhookSecret = trim((string)billing_setting_get($pdo, 'stripe_test_webhook_secret', ''));
    $liveWebhookSecret = trim((string)billing_setting_get($pdo, 'stripe_live_webhook_secret', ''));
} catch (Throwable $e) {
    error_log('RAC Stripe webhook configuration error: ' . $e->getMessage());
    http_response_code(500);
    exit('Webhook configuration is not available.');
}

if (
    ($testWebhookSecret === '' || strpos($testWebhookSecret, 'whsec_') !== 0)
    && ($liveWebhookSecret === '' || strpos($liveWebhookSecret, 'whsec_') !== 0)
) {
    http_response_code(500);
    exit('Webhook secret is not configured.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method Not Allowed');
}

$payload = file_get_contents('php://input');

if ($payload === false || $payload === '') {
    http_response_code(400);
    exit('Empty payload.');
}

$signatureHeader = (string)($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '');

if ($signatureHeader === '') {
    http_response_code(400);
    exit('Missing Stripe-Signature header.');
}

$timestamp = null;
$v1Signatures = array();

foreach (explode(',', $signatureHeader) as $part) {
    $pair = explode('=', trim($part), 2);

    if (count($pair) !== 2) {
        continue;
    }

    $key = trim($pair[0]);
    $value = trim($pair[1]);

    if ($key === 't' && ctype_digit($value)) {
        $timestamp = (int)$value;
    } elseif ($key === 'v1' && $value !== '') {
        $v1Signatures[] = $value;
    }
}

if ($timestamp === null || empty($v1Signatures)) {
    http_response_code(400);
    exit('Invalid Stripe-Signature header.');
}

if (abs(time() - $timestamp) > 300) {
    http_response_code(400);
    exit('Webhook timestamp outside tolerance.');
}

function billing_webhook_signature_valid(
    string $payload,
    int $timestamp,
    array $v1Signatures,
    string $secret
): bool {
    if ($secret === '' || strpos($secret, 'whsec_') !== 0) {
        return false;
    }

    $expectedSignature = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

    foreach ($v1Signatures as $signature) {
        if (hash_equals($expectedSignature, (string)$signature)) {
            return true;
        }
    }

    return false;
}

$testSignatureValid = billing_webhook_signature_valid(
    $payload,
    $timestamp,
    $v1Signatures,
    $testWebhookSecret
);

$liveSignatureValid = billing_webhook_signature_valid(
    $payload,
    $timestamp,
    $v1Signatures,
    $liveWebhookSecret
);

if (!$testSignatureValid && !$liveSignatureValid) {
    http_response_code(400);
    exit('Invalid webhook signature.');
}

$event = json_decode($payload, true);

if (!is_array($event) || empty($event['id']) || empty($event['type'])) {
    http_response_code(400);
    exit('Invalid event payload.');
}

$eventId = (string)$event['id'];
$eventType = (string)$event['type'];
$livemode = !empty($event['livemode']) ? 1 : 0;

/*
 * Podpis musi odpovidat prostredi dane udalosti.
 * Tim se zabrani tomu, aby testovaci secret overil Live event nebo naopak.
 */
if (($livemode === 1 && !$liveSignatureValid) || ($livemode === 0 && !$testSignatureValid)) {
    http_response_code(400);
    exit('Webhook environment mismatch.');
}

$object = $event['data']['object'] ?? array();
if (!is_array($object)) {
    $object = array();
}

$objectId = trim((string)($object['id'] ?? ''));

function billing_stripe_id($value): ?string
{
    if (is_string($value)) {
        $value = trim($value);
        return $value !== '' ? $value : null;
    }

    if (is_array($value) && isset($value['id']) && is_string($value['id'])) {
        $id = trim($value['id']);
        return $id !== '' ? $id : null;
    }

    return null;
}

function billing_unix_datetime($value): ?string
{
    if (!is_numeric($value) || (int)$value <= 0) {
        return null;
    }

    return date('Y-m-d H:i:s', (int)$value);
}

function billing_metadata(array $object): array
{
    $metadata = $object['metadata'] ?? array();
    return is_array($metadata) ? $metadata : array();
}

function billing_user_id_from_metadata(array $object): ?int
{
    $metadata = billing_metadata($object);

    if (isset($metadata['user_id']) && ctype_digit((string)$metadata['user_id'])) {
        return (int)$metadata['user_id'];
    }

    return null;
}

function billing_upsert_customer(PDO $pdo, int $userId, ?string $customerId, ?string $email = null): void
{
    if (!$customerId) {
        return;
    }

    $stmt = $pdo->prepare("
        INSERT INTO billing_customers (
            provider, user_id, provider_customer_id, email, created_at, updated_at
        ) VALUES (
            'stripe', :user_id, :customer_id, :email, NOW(), NOW()
        )
        ON DUPLICATE KEY UPDATE
            provider_customer_id = VALUES(provider_customer_id),
            email = COALESCE(VALUES(email), email),
            updated_at = NOW()
    ");

    $stmt->execute(array(
        ':user_id' => $userId,
        ':customer_id' => $customerId,
        ':email' => $email,
    ));
}

function billing_product_by_code(PDO $pdo, ?string $productCode): ?array
{
    if (!$productCode) {
        return null;
    }

    $stmt = $pdo->prepare("
        SELECT *
        FROM billing_products
        WHERE code = :code
        LIMIT 1
    ");
    $stmt->execute(array(':code' => $productCode));

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function billing_subscription_price_data(array $subscription): array
{
    $items = $subscription['items']['data'] ?? array();

    if (!is_array($items) || empty($items[0]) || !is_array($items[0])) {
        return array(
            'price_id' => null,
            'amount_minor' => 0,
            'currency' => null,
            'interval' => null,
            'interval_count' => null,
        );
    }

    $price = $items[0]['price'] ?? array();
    if (!is_array($price)) {
        $price = array();
    }

    $recurring = $price['recurring'] ?? array();
    if (!is_array($recurring)) {
        $recurring = array();
    }

    return array(
        'price_id' => billing_stripe_id($price),
        'amount_minor' => isset($price['unit_amount']) ? (int)$price['unit_amount'] : 0,
        'currency' => isset($price['currency']) ? strtoupper((string)$price['currency']) : null,
        'interval' => isset($recurring['interval']) ? (string)$recurring['interval'] : null,
        'interval_count' => isset($recurring['interval_count']) ? (int)$recurring['interval_count'] : null,
    );
}

function billing_subscription_period_data(array $subscription): array
{
    $start = $subscription['current_period_start'] ?? null;
    $end = $subscription['current_period_end'] ?? null;

    $items = $subscription['items']['data'] ?? array();

    if (is_array($items) && !empty($items[0]) && is_array($items[0])) {
        if (!$start && isset($items[0]['current_period_start'])) {
            $start = $items[0]['current_period_start'];
        }

        if (!$end && isset($items[0]['current_period_end'])) {
            $end = $items[0]['current_period_end'];
        }
    }

    return array(
        'start' => billing_unix_datetime($start),
        'end' => billing_unix_datetime($end),
    );
}

function billing_upsert_subscription_from_checkout(PDO $pdo, array $session): void
{
    $metadata = billing_metadata($session);

    $userId = null;
    if (isset($metadata['user_id']) && ctype_digit((string)$metadata['user_id'])) {
        $userId = (int)$metadata['user_id'];
    }

    $subscriptionId = billing_stripe_id($session['subscription'] ?? null);

    if (!$userId || !$subscriptionId) {
        return;
    }

    $productCode = trim((string)($metadata['product_code'] ?? ''));
    $product = billing_product_by_code($pdo, $productCode !== '' ? $productCode : null);

    $customerId = billing_stripe_id($session['customer'] ?? null);
    $customerEmail = null;

    if (isset($session['customer_details']['email'])) {
        $customerEmail = trim((string)$session['customer_details']['email']);
    }

    billing_upsert_customer($pdo, $userId, $customerId, $customerEmail ?: null);

    $status = strtolower((string)($session['payment_status'] ?? '')) === 'paid'
        ? 'active'
        : 'pending';

    $stmt = $pdo->prepare("
        INSERT INTO billing_subscriptions (
            provider,
            user_id,
            billing_product_id,
            product_code,
            provider_customer_id,
            provider_subscription_id,
            provider_price_id,
            checkout_session_id,
            status,
            amount_minor,
            currency,
            billing_interval,
            billing_interval_count,
            created_at,
            updated_at
        ) VALUES (
            'stripe',
            :user_id,
            :billing_product_id,
            :product_code,
            :customer_id,
            :subscription_id,
            :price_id,
            :checkout_session_id,
            :status,
            :amount_minor,
            :currency,
            :billing_interval,
            :billing_interval_count,
            NOW(),
            NOW()
        )
        ON DUPLICATE KEY UPDATE
            user_id = VALUES(user_id),
            billing_product_id = VALUES(billing_product_id),
            product_code = VALUES(product_code),
            provider_customer_id = VALUES(provider_customer_id),
            provider_price_id = VALUES(provider_price_id),
            checkout_session_id = COALESCE(VALUES(checkout_session_id), checkout_session_id),
            status = VALUES(status),
            amount_minor = VALUES(amount_minor),
            currency = VALUES(currency),
            billing_interval = VALUES(billing_interval),
            billing_interval_count = VALUES(billing_interval_count),
            updated_at = NOW()
    ");

    $stmt->execute(array(
        ':user_id' => $userId,
        ':billing_product_id' => $product ? (int)$product['id'] : null,
        ':product_code' => $productCode !== '' ? $productCode : null,
        ':customer_id' => $customerId,
        ':subscription_id' => $subscriptionId,
        ':price_id' => $product ? (string)$product['provider_price_id'] : null,
        ':checkout_session_id' => (string)($session['id'] ?? ''),
        ':status' => $status,
        ':amount_minor' => $product ? (int)$product['amount_minor'] : (int)($session['amount_total'] ?? 0),
        ':currency' => $product ? (string)$product['currency'] : strtoupper((string)($session['currency'] ?? '')),
        ':billing_interval' => $product ? (string)$product['billing_interval'] : null,
        ':billing_interval_count' => $product ? (int)$product['billing_interval_count'] : null,
    ));
}

function billing_upsert_subscription_object(PDO $pdo, array $subscription): void
{
    $subscriptionId = trim((string)($subscription['id'] ?? ''));
    $userId = billing_user_id_from_metadata($subscription);

    if ($subscriptionId === '' || !$userId) {
        return;
    }

    $metadata = billing_metadata($subscription);
    $productCode = trim((string)($metadata['product_code'] ?? ''));
    $product = billing_product_by_code($pdo, $productCode !== '' ? $productCode : null);

    $customerId = billing_stripe_id($subscription['customer'] ?? null);
    billing_upsert_customer($pdo, $userId, $customerId);

    $priceData = billing_subscription_price_data($subscription);
    $periodData = billing_subscription_period_data($subscription);

    $status = trim((string)($subscription['status'] ?? 'pending'));
    $cancelAtPeriodEnd = !empty($subscription['cancel_at_period_end']) ? 1 : 0;

    $stmt = $pdo->prepare("
        INSERT INTO billing_subscriptions (
            provider,
            user_id,
            billing_product_id,
            product_code,
            provider_customer_id,
            provider_subscription_id,
            provider_price_id,
            status,
            amount_minor,
            currency,
            billing_interval,
            billing_interval_count,
            current_period_start,
            current_period_end,
            cancel_at_period_end,
            cancel_at,
            canceled_at,
            ended_at,
            created_at,
            updated_at
        ) VALUES (
            'stripe',
            :user_id,
            :billing_product_id,
            :product_code,
            :customer_id,
            :subscription_id,
            :price_id,
            :status,
            :amount_minor,
            :currency,
            :billing_interval,
            :billing_interval_count,
            :period_start,
            :period_end,
            :cancel_at_period_end,
            :cancel_at,
            :canceled_at,
            :ended_at,
            NOW(),
            NOW()
        )
        ON DUPLICATE KEY UPDATE
            user_id = VALUES(user_id),
            billing_product_id = VALUES(billing_product_id),
            product_code = VALUES(product_code),
            provider_customer_id = VALUES(provider_customer_id),
            provider_price_id = VALUES(provider_price_id),
            status = VALUES(status),
            amount_minor = VALUES(amount_minor),
            currency = VALUES(currency),
            billing_interval = VALUES(billing_interval),
            billing_interval_count = VALUES(billing_interval_count),
            current_period_start = VALUES(current_period_start),
            current_period_end = VALUES(current_period_end),
            cancel_at_period_end = VALUES(cancel_at_period_end),
            cancel_at = VALUES(cancel_at),
            canceled_at = VALUES(canceled_at),
            ended_at = VALUES(ended_at),
            updated_at = NOW()
    ");

    $stmt->execute(array(
        ':user_id' => $userId,
        ':billing_product_id' => $product ? (int)$product['id'] : null,
        ':product_code' => $productCode !== '' ? $productCode : null,
        ':customer_id' => $customerId,
        ':subscription_id' => $subscriptionId,
        ':price_id' => $priceData['price_id'],
        ':status' => $status !== '' ? $status : 'pending',
        ':amount_minor' => (int)$priceData['amount_minor'],
        ':currency' => $priceData['currency'],
        ':billing_interval' => $priceData['interval'],
        ':billing_interval_count' => $priceData['interval_count'],
        ':period_start' => $periodData['start'],
        ':period_end' => $periodData['end'],
        ':cancel_at_period_end' => $cancelAtPeriodEnd,
        ':cancel_at' => billing_unix_datetime($subscription['cancel_at'] ?? null),
        ':canceled_at' => billing_unix_datetime($subscription['canceled_at'] ?? null),
        ':ended_at' => billing_unix_datetime($subscription['ended_at'] ?? null),
    ));
}

function billing_invoice_subscription_id(array $invoice): ?string
{
    $id = billing_stripe_id($invoice['subscription'] ?? null);

    if ($id) {
        return $id;
    }

    $parent = $invoice['parent'] ?? array();
    if (!is_array($parent)) {
        return null;
    }

    $details = $parent['subscription_details'] ?? array();
    if (!is_array($details)) {
        return null;
    }

    return billing_stripe_id($details['subscription'] ?? null);
}

function billing_save_invoice_payment(PDO $pdo, array $invoice, string $status): void
{
    $invoiceId = trim((string)($invoice['id'] ?? ''));

    if ($invoiceId === '') {
        throw new RuntimeException('Invoice ID is missing.');
    }

    $subscriptionId = billing_invoice_subscription_id($invoice);
    $customerId = billing_stripe_id($invoice['customer'] ?? null);

    $userId = null;
    $productCode = null;

    if ($subscriptionId) {
        $stmt = $pdo->prepare("
            SELECT user_id, product_code
            FROM billing_subscriptions
            WHERE provider = 'stripe'
              AND provider_subscription_id = :subscription_id
            LIMIT 1
        ");
        $stmt->execute(array(':subscription_id' => $subscriptionId));

        $subscriptionRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($subscriptionRow) {
            $userId = (int)$subscriptionRow['user_id'];
            $productCode = $subscriptionRow['product_code'] ?: null;
        }
    }

    $amountMinor = $status === 'paid'
        ? (int)($invoice['amount_paid'] ?? 0)
        : (int)($invoice['amount_due'] ?? 0);

    $currency = strtoupper(trim((string)($invoice['currency'] ?? '')));
    $paymentIntentId = billing_stripe_id($invoice['payment_intent'] ?? null);
    $paidAt = $status === 'paid' ? date('Y-m-d H:i:s') : null;

    $stmt = $pdo->prepare("
        INSERT INTO billing_payments (
            provider,
            payment_type,
            user_id,
            provider_checkout_session_id,
            provider_payment_intent_id,
            provider_invoice_id,
            provider_subscription_id,
            provider_customer_id,
            status,
            amount_minor,
            currency,
            client_reference_id,
            integration_id,
            product_code,
            paid_at,
            created_at,
            updated_at
        ) VALUES (
            'stripe',
            'subscription',
            :user_id,
            NULL,
            :payment_intent_id,
            :invoice_id,
            :subscription_id,
            :customer_id,
            :status,
            :amount_minor,
            :currency,
            NULL,
            'bos24-billing',
            :product_code,
            :paid_at,
            NOW(),
            NOW()
        )
        ON DUPLICATE KEY UPDATE
            user_id = VALUES(user_id),
            provider_payment_intent_id = VALUES(provider_payment_intent_id),
            provider_subscription_id = VALUES(provider_subscription_id),
            provider_customer_id = VALUES(provider_customer_id),
            status = VALUES(status),
            amount_minor = VALUES(amount_minor),
            currency = VALUES(currency),
            product_code = VALUES(product_code),
            paid_at = CASE
                WHEN VALUES(status) = 'paid' THEN COALESCE(paid_at, VALUES(paid_at))
                ELSE paid_at
            END,
            updated_at = NOW()
    ");

    $stmt->execute(array(
        ':user_id' => $userId,
        ':payment_intent_id' => $paymentIntentId,
        ':invoice_id' => $invoiceId,
        ':subscription_id' => $subscriptionId,
        ':customer_id' => $customerId,
        ':status' => $status,
        ':amount_minor' => $amountMinor,
        ':currency' => $currency !== '' ? $currency : 'CZK',
        ':product_code' => $productCode,
        ':paid_at' => $paidAt,
    ));
}

function billing_save_one_off_checkout(PDO $pdo, array $session, string $status): void
{
    $sessionId = trim((string)($session['id'] ?? ''));

    if ($sessionId === '') {
        throw new RuntimeException('Checkout Session ID is missing.');
    }

    $metadata = billing_metadata($session);

    $userId = null;
    if (isset($metadata['user_id']) && ctype_digit((string)$metadata['user_id'])) {
        $userId = (int)$metadata['user_id'];
    }

    $productCode = trim((string)($metadata['product_code'] ?? ''));
    $integrationId = trim((string)($metadata['integration'] ?? ''));
    $clientReferenceId = trim((string)($session['client_reference_id'] ?? ''));

    $amountMinor = (int)($session['amount_total'] ?? 0);
    $currency = strtoupper(trim((string)($session['currency'] ?? 'CZK')));

    $paymentIntentId = billing_stripe_id($session['payment_intent'] ?? null);
    $customerId = billing_stripe_id($session['customer'] ?? null);
    $paidAt = $status === 'paid' ? date('Y-m-d H:i:s') : null;

    $stmt = $pdo->prepare("
        INSERT INTO billing_payments (
            provider,
            payment_type,
            user_id,
            provider_checkout_session_id,
            provider_payment_intent_id,
            provider_customer_id,
            status,
            amount_minor,
            currency,
            client_reference_id,
            integration_id,
            product_code,
            paid_at,
            created_at,
            updated_at
        ) VALUES (
            'stripe',
            'one_off',
            :user_id,
            :session_id,
            :payment_intent_id,
            :customer_id,
            :status,
            :amount_minor,
            :currency,
            :client_reference_id,
            :integration_id,
            :product_code,
            :paid_at,
            NOW(),
            NOW()
        )
        ON DUPLICATE KEY UPDATE
            status = VALUES(status),
            provider_payment_intent_id = VALUES(provider_payment_intent_id),
            provider_customer_id = VALUES(provider_customer_id),
            amount_minor = VALUES(amount_minor),
            currency = VALUES(currency),
            product_code = VALUES(product_code),
            paid_at = CASE
                WHEN VALUES(status) = 'paid' THEN COALESCE(paid_at, VALUES(paid_at))
                ELSE paid_at
            END,
            updated_at = NOW()
    ");

    $stmt->execute(array(
        ':user_id' => $userId,
        ':session_id' => $sessionId,
        ':payment_intent_id' => $paymentIntentId,
        ':customer_id' => $customerId,
        ':status' => $status,
        ':amount_minor' => $amountMinor,
        ':currency' => $currency,
        ':client_reference_id' => $clientReferenceId !== '' ? $clientReferenceId : null,
        ':integration_id' => $integrationId !== '' ? $integrationId : null,
        ':product_code' => $productCode !== '' ? $productCode : null,
        ':paid_at' => $paidAt,
    ));
}

try {
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare("
            INSERT INTO billing_webhook_events (
                provider, event_id, event_type, livemode,
                status, object_id, received_at
            ) VALUES (
                'stripe', :event_id, :event_type, :livemode,
                'received', :object_id, NOW()
            )
        ");

        $stmt->execute(array(
            ':event_id' => $eventId,
            ':event_type' => $eventType,
            ':livemode' => $livemode,
            ':object_id' => $objectId !== '' ? $objectId : null,
        ));
    } catch (PDOException $e) {
        if ((string)$e->getCode() === '23000') {
            $pdo->rollBack();

            http_response_code(200);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Already received';
            exit;
        }

        throw $e;
    }

    $eventStatus = 'ignored';

    switch ($eventType) {
        case 'checkout.session.completed':
            $mode = strtolower(trim((string)($object['mode'] ?? '')));

            if ($mode === 'subscription') {
                billing_upsert_subscription_from_checkout($pdo, $object);
            } else {
                $paymentStatus = strtolower(trim((string)($object['payment_status'] ?? '')));
                billing_save_one_off_checkout(
                    $pdo,
                    $object,
                    $paymentStatus === 'paid' ? 'paid' : 'pending'
                );
            }

            $eventStatus = 'processed';
            break;

        case 'checkout.session.async_payment_succeeded':
            billing_save_one_off_checkout($pdo, $object, 'paid');
            $eventStatus = 'processed';
            break;

        case 'checkout.session.async_payment_failed':
            billing_save_one_off_checkout($pdo, $object, 'failed');
            $eventStatus = 'processed';
            break;

        case 'customer.subscription.created':
        case 'customer.subscription.updated':
        case 'customer.subscription.deleted':
            billing_upsert_subscription_object($pdo, $object);
            $eventStatus = 'processed';
            break;

        case 'invoice.paid':
            billing_save_invoice_payment($pdo, $object, 'paid');
            $eventStatus = 'processed';
            break;

        case 'invoice.payment_failed':
            billing_save_invoice_payment($pdo, $object, 'failed');
            $eventStatus = 'processed';
            break;
    }

    $stmt = $pdo->prepare("
        UPDATE billing_webhook_events
        SET status = :status,
            processed_at = NOW()
        WHERE provider = 'stripe'
          AND event_id = :event_id
        LIMIT 1
    ");

    $stmt->execute(array(
        ':status' => $eventStatus,
        ':event_id' => $eventId,
    ));

    $pdo->commit();

    http_response_code(200);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'OK';
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        'RAC Stripe webhook error: '
        . $e->getMessage()
        . ' event=' . $eventId
        . ' type=' . $eventType
    );

    http_response_code(500);
    exit('Webhook processing failed.');
}