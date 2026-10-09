<?php
declare(strict_types=1);

/**
 * RAC Billing - administrace webhooku
 * verze 2026-09-16-21.48
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

function bwh_e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function bwh_date($value): string
{
    $value = trim((string)$value);

    if ($value === '' || $value === '0000-00-00 00:00:00') {
        return '—';
    }

    $time = strtotime($value);

    if (!$time) {
        return '—';
    }

    return date('j. n. Y H:i:s', $time);
}

function bwh_status_label(string $status): string
{
    switch (strtolower($status)) {
        case 'processed':
            return 'Zpracováno';
        case 'received':
            return 'Přijato';
        case 'ignored':
            return 'Ignorováno';
        case 'error':
        case 'failed':
            return 'Chyba';
        default:
            return $status !== '' ? ucfirst($status) : '—';
    }
}

$errors = array();

$statusFilter = trim((string)($_GET['status'] ?? ''));
$typeFilter = trim((string)($_GET['event_type'] ?? ''));
$modeFilter = trim((string)($_GET['mode'] ?? ''));
$search = trim((string)($_GET['q'] ?? ''));

$allowedStatuses = array('', 'processed', 'received', 'ignored', 'error', 'failed');
$allowedModes = array('', 'test', 'live');

if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = '';
}

if (!in_array($modeFilter, $allowedModes, true)) {
    $modeFilter = '';
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;
$offset = ($page - 1) * $perPage;

$where = array();
$params = array();

if ($statusFilter !== '') {
    $where[] = 'bwe.status = :status';
    $params[':status'] = $statusFilter;
}

if ($typeFilter !== '') {
    $where[] = 'bwe.event_type = :event_type';
    $params[':event_type'] = $typeFilter;
}

if ($modeFilter === 'test') {
    $where[] = 'bwe.livemode = 0';
} elseif ($modeFilter === 'live') {
    $where[] = 'bwe.livemode = 1';
}

if ($search !== '') {
    $where[] = '(
        bwe.event_id LIKE :search
        OR bwe.event_type LIKE :search
        OR bwe.object_id LIKE :search
        OR bwe.error_message LIKE :search
    )';
    $params[':search'] = '%' . $search . '%';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    $countSql = "
        SELECT COUNT(*)
        FROM billing_webhook_events bwe
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
            bwe.id,
            bwe.provider,
            bwe.event_id,
            bwe.event_type,
            bwe.livemode,
            bwe.status,
            bwe.object_id,
            bwe.error_message,
            bwe.received_at,
            bwe.processed_at
        FROM billing_webhook_events bwe
        {$whereSql}
        ORDER BY bwe.id DESC
        LIMIT {$perPage} OFFSET {$offset}
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $events = array();
    $totalRows = 0;
    $totalPages = 1;
    $errors[] = 'Webhook události se nepodařilo načíst.';
    error_log('RAC Billing webhook admin error: ' . $e->getMessage());
}

try {
    $eventTypes = $pdo->query(
        "SELECT DISTINCT event_type
         FROM billing_webhook_events
         WHERE event_type IS NOT NULL AND event_type <> ''
         ORDER BY event_type ASC"
    )->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
    $eventTypes = array();
}

$stats = array(
    'processed' => 0,
    'received' => 0,
    'ignored' => 0,
    'errors' => 0,
);

try {
    $statsStmt = $pdo->query("
        SELECT
            SUM(CASE WHEN status = 'processed' THEN 1 ELSE 0 END) AS processed,
            SUM(CASE WHEN status = 'received' THEN 1 ELSE 0 END) AS received,
            SUM(CASE WHEN status = 'ignored' THEN 1 ELSE 0 END) AS ignored,
            SUM(CASE WHEN status IN ('error', 'failed') OR (error_message IS NOT NULL AND error_message <> '') THEN 1 ELSE 0 END) AS errors
        FROM billing_webhook_events
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

include __DIR__ . '/obsah/admin-webhooky.class.php';