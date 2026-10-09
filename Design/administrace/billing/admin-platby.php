<?php
declare(strict_types=1);

/**
 * RAC Billing - administrace plateb
 * verze 2026-09-16-21.18
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

function bpay_e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function bpay_currency_decimals(string $currency): int
{
    $currency = strtoupper(trim($currency));

    $zeroDecimal = array(
        'BIF', 'CLP', 'DJF', 'GNF', 'JPY',
        'KMF', 'KRW', 'MGA', 'PYG', 'RWF',
        'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'
    );

    return in_array($currency, $zeroDecimal, true) ? 0 : 2;
}

function bpay_amount($minor, string $currency): string
{
    $minor = (int)$minor;
    $currency = strtoupper(trim($currency));
    $decimals = bpay_currency_decimals($currency);

    if ($decimals === 0) {
        return number_format($minor, 0, ',', ' ') . ' ' . $currency;
    }

    return number_format($minor / 100, 2, ',', ' ') . ' ' . $currency;
}

function bpay_date($value): string
{
    $value = trim((string)$value);

    if ($value === '' || $value === '0000-00-00 00:00:00') {
        return '—';
    }

    $time = strtotime($value);

    if (!$time) {
        return '—';
    }

    return date('j. n. Y H:i', $time);
}

function bpay_status_label(string $status): string
{
    switch (strtolower($status)) {
        case 'paid':
            return 'Zaplaceno';
        case 'failed':
            return 'Selhalo';
        case 'pending':
            return 'Čeká';
        case 'refunded':
            return 'Vráceno';
        case 'canceled':
        case 'cancelled':
            return 'Zrušeno';
        default:
            return $status !== '' ? ucfirst($status) : '—';
    }
}

function bpay_type_label(string $type): string
{
    return $type === 'subscription' ? 'Předplatné' : 'Jednorázová';
}

$errors = array();

$statusFilter = trim((string)($_GET['status'] ?? ''));
$typeFilter = trim((string)($_GET['type'] ?? ''));
$currencyFilter = strtoupper(trim((string)($_GET['currency'] ?? '')));
$search = trim((string)($_GET['q'] ?? ''));

$allowedStatuses = array('', 'paid', 'failed', 'pending', 'refunded', 'canceled');
$allowedTypes = array('', 'one_off', 'subscription');

if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = '';
}

if (!in_array($typeFilter, $allowedTypes, true)) {
    $typeFilter = '';
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = array();
$params = array();

if ($statusFilter !== '') {
    $where[] = 'bp.status = :status';
    $params[':status'] = $statusFilter;
}

if ($typeFilter !== '') {
    $where[] = 'bp.payment_type = :payment_type';
    $params[':payment_type'] = $typeFilter;
}

if ($currencyFilter !== '') {
    $where[] = 'UPPER(bp.currency) = :currency';
    $params[':currency'] = $currencyFilter;
}

if ($search !== '') {
    $where[] = '(
        bp.product_code LIKE :search
        OR bp.provider_invoice_id LIKE :search
        OR bp.provider_payment_intent_id LIKE :search
        OR bp.provider_checkout_session_id LIKE :search
        OR bp.provider_subscription_id LIKE :search
        OR CAST(bp.user_id AS CHAR) LIKE :search
        OR u.email LIKE :search
        OR u.username LIKE :search
    )';
    $params[':search'] = '%' . $search . '%';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    $countSql = "
        SELECT COUNT(*)
        FROM billing_payments bp
        LEFT JOIN users u ON u.id = bp.user_id
        {$whereSql}
    ";

    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $totalRows = (int)$stmt->fetchColumn();

    $totalPages = max(1, (int)ceil($totalRows / $perPage));

    if ($page > $totalPages) {
        $page = $totalPages;
        $offset = ($page - 1) * $perPage;
    }

    $sql = "
        SELECT
            bp.*,
            u.email AS user_email,
            u.username AS user_username
        FROM billing_payments bp
        LEFT JOIN users u ON u.id = bp.user_id
        {$whereSql}
        ORDER BY bp.id DESC
        LIMIT {$perPage} OFFSET {$offset}
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $payments = array();
    $totalRows = 0;
    $totalPages = 1;
    $errors[] = 'Platby se nepodařilo načíst.';
    error_log('RAC Billing payments admin error: ' . $e->getMessage());
}

try {
    $currencies = $pdo->query(
        "SELECT DISTINCT UPPER(currency) AS currency
         FROM billing_payments
         WHERE currency IS NOT NULL AND currency <> ''
         ORDER BY currency ASC"
    )->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
    $currencies = array();
}

$stats = array(
    'paid_count' => 0,
    'failed_count' => 0,
    'pending_count' => 0,
    'subscription_count' => 0,
);

try {
    $statsStmt = $pdo->query("
        SELECT
            SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) AS paid_count,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed_count,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
            SUM(CASE WHEN payment_type = 'subscription' THEN 1 ELSE 0 END) AS subscription_count
        FROM billing_payments
    ");

    $statsRow = $statsStmt->fetch(PDO::FETCH_ASSOC);

    if ($statsRow) {
        foreach ($stats as $key => $value) {
            $stats[$key] = (int)($statsRow[$key] ?? 0);
        }
    }
} catch (Throwable $e) {
    // Statistiky nejsou kritické pro zobrazení přehledu.
}

include __DIR__ . '/obsah/admin-platby.class.php';