<?php
// Bez výstupu před tímto řádkem.
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

define('BASE_DIR', __DIR__);
require_once BASE_DIR . '/config/db.php';
require_once BASE_DIR . '/config/auth.php';
require_once BASE_DIR . '/config/auth_roles.php';
require_once BASE_DIR . '/google-analytics.class.php';

require_login();
$user = current_user_row();
$me = current_user_row();
$selfPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Reporting zobrazuje souhrnná data celého webu.
$role = strtolower((string)($me['role'] ?? ''));
if (!in_array($role, ['admin', 'superadmin'], true)) {
    $_SESSION['flash_err'] = ['K reportingu nemáte oprávnění.'];
    header('Location: dashboard.php');
    exit;
}

const GA4_PROPERTY_ID = '520846307';
$keyFile = BASE_DIR . '/private/google-analytics-key.json';

function reporting_valid_date($value)
{
    if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return false;
    }
    $parts = explode('-', $value);
    return count($parts) === 3 && checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0]);
}

function reporting_format_date_cs($date)
{
    $timestamp = strtotime((string)$date);
    return $timestamp ? date('d.m.Y', $timestamp) : (string)$date;
}

function reporting_previous_period($startDate, $endDate)
{
    $start = new DateTimeImmutable($startDate);
    $end = new DateTimeImmutable($endDate);
    $days = (int)$start->diff($end)->days + 1;
    $previousEnd = $start->modify('-1 day');
    $previousStart = $previousEnd->modify('-' . ($days - 1) . ' days');
    return [$previousStart->format('Y-m-d'), $previousEnd->format('Y-m-d')];
}

function reporting_metric_value(array $report, $rowIndex, $metricIndex)
{
    return isset($report['rows'][$rowIndex]['metricValues'][$metricIndex]['value'])
        ? (float)$report['rows'][$rowIndex]['metricValues'][$metricIndex]['value']
        : 0.0;
}

function reporting_dimension_value(array $row, $index)
{
    return isset($row['dimensionValues'][$index]['value'])
        ? (string)$row['dimensionValues'][$index]['value']
        : '';
}

function reporting_change_percent($current, $previous)
{
    $current = (float)$current;
    $previous = (float)$previous;
    if ($previous == 0.0) {
        return $current > 0 ? null : 0.0;
    }
    return (($current - $previous) / $previous) * 100;
}

function reporting_seconds_label($seconds)
{
    $seconds = max(0, (int)round((float)$seconds));
    $minutes = intdiv($seconds, 60);
    $rest = $seconds % 60;
    return $minutes . ':' . str_pad((string)$rest, 2, '0', STR_PAD_LEFT);
}

function reporting_number($value, $decimals = 0)
{
    return number_format((float)$value, (int)$decimals, ',', ' ');
}

$period = isset($_GET['obdobi']) ? (string)$_GET['obdobi'] : '30dni';
$allowedPeriods = ['7dni', '30dni', 'tento-mesic', 'minuly-mesic', 'vlastni'];
if (!in_array($period, $allowedPeriods, true)) {
    $period = '30dni';
}

$today = new DateTimeImmutable('today');
switch ($period) {
    case '7dni':
        $startDate = $today->modify('-6 days')->format('Y-m-d');
        $endDate = $today->format('Y-m-d');
        break;
    case 'tento-mesic':
        $startDate = $today->modify('first day of this month')->format('Y-m-d');
        $endDate = $today->format('Y-m-d');
        break;
    case 'minuly-mesic':
        $startDate = $today->modify('first day of last month')->format('Y-m-d');
        $endDate = $today->modify('last day of last month')->format('Y-m-d');
        break;
    case 'vlastni':
        $customStart = isset($_GET['od']) ? trim((string)$_GET['od']) : '';
        $customEnd = isset($_GET['do']) ? trim((string)$_GET['do']) : '';
        if (reporting_valid_date($customStart) && reporting_valid_date($customEnd) && $customStart <= $customEnd) {
            $startDate = $customStart;
            $endDate = $customEnd;
        } else {
            $period = '30dni';
            $startDate = $today->modify('-29 days')->format('Y-m-d');
            $endDate = $today->format('Y-m-d');
        }
        break;
    case '30dni':
    default:
        $startDate = $today->modify('-29 days')->format('Y-m-d');
        $endDate = $today->format('Y-m-d');
        break;
}

list($previousStartDate, $previousEndDate) = reporting_previous_period($startDate, $endDate);

$reportingError = null;
$summaryCurrent = ['activeUsers' => 0, 'newUsers' => 0, 'sessions' => 0, 'screenPageViews' => 0, 'averageSessionDuration' => 0];
$summaryPrevious = $summaryCurrent;
$dailyRows = [];
$pageRows = [];
$sourceRows = [];
$deviceRows = [];
$realtimeUsers = 0;

try {
    $analytics = new GoogleAnalyticsRestClient(GA4_PROPERTY_ID, $keyFile);

    $summaryReport = $analytics->runReport([
        'dateRanges' => [
            ['startDate' => $startDate, 'endDate' => $endDate],
            ['startDate' => $previousStartDate, 'endDate' => $previousEndDate],
        ],
        'metrics' => [
            ['name' => 'activeUsers'],
            ['name' => 'newUsers'],
            ['name' => 'sessions'],
            ['name' => 'screenPageViews'],
            ['name' => 'averageSessionDuration'],
        ],
    ]);

    $metricNames = array_keys($summaryCurrent);
    foreach ($metricNames as $metricIndex => $metricName) {
        $summaryCurrent[$metricName] = reporting_metric_value($summaryReport, 0, $metricIndex);
        $summaryPrevious[$metricName] = reporting_metric_value($summaryReport, 1, $metricIndex);
    }

    $dailyReport = $analytics->runReport([
        'dateRanges' => [['startDate' => $startDate, 'endDate' => $endDate]],
        'dimensions' => [['name' => 'date']],
        'metrics' => [
            ['name' => 'activeUsers'],
            ['name' => 'sessions'],
            ['name' => 'screenPageViews'],
        ],
        'orderBys' => [['dimension' => ['dimensionName' => 'date']]],
        'limit' => 400,
    ]);
    $dailyRows = isset($dailyReport['rows']) && is_array($dailyReport['rows']) ? $dailyReport['rows'] : [];

    $pagesReport = $analytics->runReport([
        'dateRanges' => [['startDate' => $startDate, 'endDate' => $endDate]],
        'dimensions' => [
            ['name' => 'pagePath'],
            ['name' => 'pageTitle'],
        ],
        'metrics' => [
            ['name' => 'screenPageViews'],
            ['name' => 'activeUsers'],
            ['name' => 'averageSessionDuration'],
        ],
        'orderBys' => [['metric' => ['metricName' => 'screenPageViews'], 'desc' => true]],
        'limit' => 10,
    ]);
    $pageRows = isset($pagesReport['rows']) && is_array($pagesReport['rows']) ? $pagesReport['rows'] : [];

    $sourcesReport = $analytics->runReport([
        'dateRanges' => [['startDate' => $startDate, 'endDate' => $endDate]],
        'dimensions' => [
            ['name' => 'sessionSource'],
            ['name' => 'sessionMedium'],
        ],
        'metrics' => [
            ['name' => 'sessions'],
            ['name' => 'activeUsers'],
        ],
        'orderBys' => [['metric' => ['metricName' => 'sessions'], 'desc' => true]],
        'limit' => 10,
    ]);
    $sourceRows = isset($sourcesReport['rows']) && is_array($sourcesReport['rows']) ? $sourcesReport['rows'] : [];

    $devicesReport = $analytics->runReport([
        'dateRanges' => [['startDate' => $startDate, 'endDate' => $endDate]],
        'dimensions' => [['name' => 'deviceCategory']],
        'metrics' => [['name' => 'activeUsers']],
        'orderBys' => [['metric' => ['metricName' => 'activeUsers'], 'desc' => true]],
        'limit' => 10,
    ]);
    $deviceRows = isset($devicesReport['rows']) && is_array($devicesReport['rows']) ? $devicesReport['rows'] : [];

    $realtimeReport = $analytics->runRealtimeReport([
        'metrics' => [['name' => 'activeUsers']],
    ]);
    $realtimeUsers = reporting_metric_value($realtimeReport, 0, 0);
} catch (Throwable $e) {
    $reportingError = $e->getMessage();
}

include 'inc/head.class.php';
?>
<body>
<section class="body">
<?php include 'inc/header.class.php'; ?>
<div class="inner-wrapper">
<?php include 'inc/leve-meny.class.php'; ?>
<?php include 'obsah/reporting.class.php'; ?>
<?php include 'inc/prave-meny.class.php'; ?>
</div>
</section>
<?php include 'inc/pata.class.php'; ?>
</body>
</html>
