<?php
declare(strict_types=1);

/**
 * RAC Billing - administrace predplatnych
 * verze 2026-09-16-21.35
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

function bsub_e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function bsub_date($value): string
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

function bsub_status_label(string $status): string
{
    switch (strtolower($status)) {
        case 'active':
            return 'Aktivní';
        case 'trialing':
            return 'Zkušební';
        case 'past_due':
            return 'Po splatnosti';
        case 'unpaid':
            return 'Nezaplaceno';
        case 'canceled':
            return 'Zrušeno';
        case 'incomplete':
            return 'Nedokončeno';
        case 'incomplete_expired':
            return 'Nedokončeno – vypršelo';
        case 'paused':
            return 'Pozastaveno';
        case 'pending':
            return 'Čeká';
        default:
            return $status !== '' ? ucfirst($status) : '—';
    }
}

function bsub_amount($minor, string $currency): string
{
    $minor = (int)$minor;
    $currency = strtoupper(trim($currency));

    $zeroDecimal = array(
        'BIF', 'CLP', 'DJF', 'GNF', 'JPY',
        'KMF', 'KRW', 'MGA', 'PYG', 'RWF',
        'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'
    );

    if (in_array($currency, $zeroDecimal, true)) {
        return number_format($minor, 0, ',', ' ') . ' ' . $currency;
    }

    return number_format($minor / 100, 2, ',', ' ') . ' ' . $currency;
}

function bsub_interval_label(array $row): string
{
    $interval = trim((string)($row['billing_interval'] ?? ''));
    $count = max(1, (int)($row['billing_interval_count'] ?? 1));

    if ($interval === 'month') {
        return $count === 1 ? 'měsíčně' : 'každých ' . $count . ' měsíců';
    }

    if ($interval === 'year') {
        return $count === 1 ? 'ročně' : 'každé ' . $count . ' roky';
    }

    if ($interval === 'week') {
        return $count === 1 ? 'týdně' : 'každých ' . $count . ' týdnů';
    }

    if ($interval === 'day') {
        return $count === 1 ? 'denně' : 'každých ' . $count . ' dní';
    }

    return '—';
}

$errors = array();

$statusFilter = trim((string)($_GET['status'] ?? ''));
$productFilter = trim((string)($_GET['product_code'] ?? ''));
$renewalFilter = trim((string)($_GET['renewal'] ?? ''));
$search = trim((string)($_GET['q'] ?? ''));

$allowedStatuses = array(
    '',
    'active',
    'trialing',
    'past_due',
    'unpaid',
    'canceled',
    'incomplete',
    'incomplete_expired',
    'paused',
    'pending'
);

if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = '';
}

if (!in_array($renewalFilter, array('', 'yes', 'no'), true)) {
    $renewalFilter = '';
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = array();
$params = array();

if ($statusFilter !== '') {
    $where[] = 'bs.status = :status';
    $params[':status'] = $statusFilter;
}

if ($productFilter !== '') {
    $where[] = 'bs.product_code = :product_code';
    $params[':product_code'] = $productFilter;
}

if ($renewalFilter === 'yes') {
    $where[] = 'bs.cancel_at_period_end = 0';
} elseif ($renewalFilter === 'no') {
    $where[] = 'bs.cancel_at_period_end = 1';
}

if ($search !== '') {
    $where[] = '(
        bs.provider_subscription_id LIKE :search
        OR bs.provider_customer_id LIKE :search
        OR bs.provider_price_id LIKE :search
        OR bs.product_code LIKE :search
        OR u.email LIKE :search
        OR u.username LIKE :search
        OR CAST(bs.user_id AS CHAR) LIKE :search
    )';
    $params[':search'] = '%' . $search . '%';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    $countSql = "
        SELECT COUNT(*)
        FROM billing_subscriptions bs
        LEFT JOIN users u ON u.id = bs.user_id
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
            bs.*,
            u.email AS user_email,
            u.username AS user_username,
            bp.name AS product_name
        FROM billing_subscriptions bs
        LEFT JOIN users u ON u.id = bs.user_id
        LEFT JOIN billing_products bp ON bp.id = bs.billing_product_id
        {$whereSql}
        ORDER BY bs.id DESC
        LIMIT {$perPage} OFFSET {$offset}
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $subscriptions = array();
    $totalRows = 0;
    $totalPages = 1;
    $errors[] = 'Předplatná se nepodařilo načíst.';
    error_log('RAC Billing subscriptions admin error: ' . $e->getMessage());
}

try {
    $productCodes = $pdo->query(
        "SELECT DISTINCT product_code
         FROM billing_subscriptions
         WHERE product_code IS NOT NULL AND product_code <> ''
         ORDER BY product_code ASC"
    )->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
    $productCodes = array();
}

$stats = array(
    'active' => 0,
    'past_due' => 0,
    'cancel_scheduled' => 0,
    'canceled' => 0,
);

try {
    $statsStmt = $pdo->query("
        SELECT
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active,
            SUM(CASE WHEN status = 'past_due' THEN 1 ELSE 0 END) AS past_due,
            SUM(CASE WHEN cancel_at_period_end = 1 AND status <> 'canceled' THEN 1 ELSE 0 END) AS cancel_scheduled,
            SUM(CASE WHEN status = 'canceled' THEN 1 ELSE 0 END) AS canceled
        FROM billing_subscriptions
    ");

    $statsRow = $statsStmt->fetch(PDO::FETCH_ASSOC);

    if ($statsRow) {
        foreach ($stats as $key => $value) {
            $stats[$key] = (int)($statsRow[$key] ?? 0);
        }
    }
} catch (Throwable $e) {
    // Statistiky nejsou kritické.
}

include __DIR__ . '/obsah/admin-predplatne.class.php';