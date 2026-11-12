<?php
// verze 2026-11-03-13.31
// RAC Reporting – Google Analytics Data API přes OAuth 2.0 | PHP 7.4+
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/session.php';
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/config/auth.php';
require_once dirname(__DIR__) . '/config/auth_roles.php';
require_once __DIR__ . '/google-analytics-oauth.class.php';
require_once __DIR__ . '/google-analytics-data.class.php';

$viewer = current_user_row();
if (!$viewer) {
    header('Location: /administrace/index.php');
    exit;
}
if (role_rank((string)($viewer['role'] ?? '')) < role_rank('admin')) {
    header('Location: /administrace/access-denied.php');
    exit;
}

function reporting_valid_date($value): bool
{
    if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) return false;
    $parts = explode('-', $value);
    return count($parts) === 3 && checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0]);
}
function reporting_format_date_cs($date): string
{
    $timestamp = strtotime((string)$date);
    return $timestamp ? date('d.m.Y', $timestamp) : (string)$date;
}
function reporting_previous_period($startDate, $endDate): array
{
    $start = new DateTimeImmutable($startDate);
    $end = new DateTimeImmutable($endDate);
    $days = (int)$start->diff($end)->days + 1;
    $previousEnd = $start->modify('-1 day');
    $previousStart = $previousEnd->modify('-' . ($days - 1) . ' days');
    return array($previousStart->format('Y-m-d'), $previousEnd->format('Y-m-d'));
}
function reporting_metric_value(array $report, $rowIndex, $metricIndex)
{
    return isset($report['rows'][$rowIndex]['metricValues'][$metricIndex]['value'])
        ? (float)$report['rows'][$rowIndex]['metricValues'][$metricIndex]['value'] : 0.0;
}
function reporting_dimension_value(array $row, $index): string
{
    return isset($row['dimensionValues'][$index]['value']) ? (string)$row['dimensionValues'][$index]['value'] : '';
}
function reporting_change_percent($current, $previous)
{
    $current = (float)$current; $previous = (float)$previous;
    if ($previous == 0.0) return $current > 0 ? null : 0.0;
    return (($current - $previous) / $previous) * 100;
}
function reporting_seconds_label($seconds): string
{
    $seconds = max(0, (int)round((float)$seconds));
    return intdiv($seconds, 60) . ':' . str_pad((string)($seconds % 60), 2, '0', STR_PAD_LEFT);
}
function reporting_number($value, $decimals = 0): string
{
    return number_format((float)$value, (int)$decimals, ',', ' ');
}
function reporting_connection(PDO $pdo): ?array
{
    $stmt = $pdo->query('SELECT * FROM reporting_google_analytics WHERE id=1 LIMIT 1');
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return is_array($row) ? $row : null;
}
function reporting_access_token(PDO $pdo, GoogleAnalyticsOAuth $oauth, array $connection): string
{
    $token = isset($_SESSION['rac_ga_access_token']) ? (string)$_SESSION['rac_ga_access_token'] : '';
    $expires = (int)($_SESSION['rac_ga_access_token_expires'] ?? 0);
    if ($token !== '' && time() < ($expires - 60)) return $token;
    if (empty($connection['refresh_token'])) throw new RuntimeException('Chybí refresh token Google Analytics.');
    $refreshed = $oauth->refreshAccessToken($oauth->decrypt((string)$connection['refresh_token']));
    $token = isset($refreshed['access_token']) ? trim((string)$refreshed['access_token']) : '';
    if ($token === '') throw new RuntimeException('Google nevrátil nový access token.');
    $_SESSION['rac_ga_access_token'] = $token;
    $_SESSION['rac_ga_access_token_expires'] = time() + max(60, (int)($refreshed['expires_in'] ?? 3600));
    return $token;
}

$period = isset($_GET['obdobi']) ? (string)$_GET['obdobi'] : '30dni';
$allowedPeriods = array('7dni', '30dni', 'tento-mesic', 'minuly-mesic', 'vlastni');
if (!in_array($period, $allowedPeriods, true)) $period = '30dni';
$today = new DateTimeImmutable('today');
switch ($period) {
    case '7dni': $startDate=$today->modify('-6 days')->format('Y-m-d'); $endDate=$today->format('Y-m-d'); break;
    case 'tento-mesic': $startDate=$today->modify('first day of this month')->format('Y-m-d'); $endDate=$today->format('Y-m-d'); break;
    case 'minuly-mesic': $startDate=$today->modify('first day of last month')->format('Y-m-d'); $endDate=$today->modify('last day of last month')->format('Y-m-d'); break;
    case 'vlastni':
        $customStart=trim((string)($_GET['od']??'')); $customEnd=trim((string)($_GET['do']??''));
        if (reporting_valid_date($customStart) && reporting_valid_date($customEnd) && $customStart <= $customEnd) { $startDate=$customStart; $endDate=$customEnd; }
        else { $period='30dni'; $startDate=$today->modify('-29 days')->format('Y-m-d'); $endDate=$today->format('Y-m-d'); }
        break;
    default: $startDate=$today->modify('-29 days')->format('Y-m-d'); $endDate=$today->format('Y-m-d');
}
list($previousStartDate,$previousEndDate)=reporting_previous_period($startDate,$endDate);

$summaryCurrent=array('activeUsers'=>0,'newUsers'=>0,'sessions'=>0,'screenPageViews'=>0,'averageSessionDuration'=>0);
$summaryPrevious=$summaryCurrent;
$dailyRows=array(); $pageRows=array(); $sourceRows=array(); $deviceRows=array(); $realtimeUsers=0;
$reportingError=null; $setupMessage=''; $gaReady=false; $connection=null;

try {
    $connection = reporting_connection($pdo);
} catch (Throwable $e) {
    $setupMessage = 'Reporting ještě není nainstalován v databázi. Spusťte SQL soubor 027_reporting_google_analytics.sql.';
}

$configFile = __DIR__ . '/private/google-oauth-config.php';
if ($setupMessage === '' && (!is_file($configFile) || !is_readable($configFile))) {
    $setupMessage = 'Reporting ještě nemá nastavenou OAuth aplikaci Google.';
}
if ($setupMessage === '' && (!$connection || empty($connection['refresh_token']))) {
    $setupMessage = 'Google Analytics zatím není připojeno.';
}
if ($setupMessage === '' && empty($connection['property_id'])) {
    $setupMessage = 'Google účet je připojený, ale ještě není vybrána GA4 služba.';
}

if ($setupMessage === '') {
    try {
        $config = require $configFile;
        if (!is_array($config)) throw new RuntimeException('OAuth konfigurace nemá platný formát.');
        $oauth = new GoogleAnalyticsOAuth($config);
        $accessToken = reporting_access_token($pdo, $oauth, $connection);
        $analytics = new GoogleAnalyticsDataClient((string)$connection['property_id'], $accessToken);
        $gaReady = true;

        $metrics = array(
            array('name'=>'activeUsers'), array('name'=>'newUsers'), array('name'=>'sessions'),
            array('name'=>'screenPageViews'), array('name'=>'averageSessionDuration')
        );
        $currentReport=$analytics->runReport(array('dateRanges'=>array(array('startDate'=>$startDate,'endDate'=>$endDate)),'metrics'=>$metrics));
        $previousReport=$analytics->runReport(array('dateRanges'=>array(array('startDate'=>$previousStartDate,'endDate'=>$previousEndDate)),'metrics'=>$metrics));
        foreach (array_keys($summaryCurrent) as $i=>$name) {
            $summaryCurrent[$name]=reporting_metric_value($currentReport,0,$i);
            $summaryPrevious[$name]=reporting_metric_value($previousReport,0,$i);
        }

        $dailyReport=$analytics->runReport(array(
            'dateRanges'=>array(array('startDate'=>$startDate,'endDate'=>$endDate)),
            'dimensions'=>array(array('name'=>'date')),
            'metrics'=>array(array('name'=>'activeUsers'),array('name'=>'sessions'),array('name'=>'screenPageViews')),
            'orderBys'=>array(array('dimension'=>array('dimensionName'=>'date'))),'limit'=>400
        ));
        $dailyRows=isset($dailyReport['rows'])&&is_array($dailyReport['rows'])?$dailyReport['rows']:array();

        $pagesReport=$analytics->runReport(array(
            'dateRanges'=>array(array('startDate'=>$startDate,'endDate'=>$endDate)),
            'dimensions'=>array(array('name'=>'pagePath'),array('name'=>'pageTitle')),
            'metrics'=>array(array('name'=>'screenPageViews'),array('name'=>'activeUsers')),
            'orderBys'=>array(array('metric'=>array('metricName'=>'screenPageViews'),'desc'=>true)),'limit'=>10
        ));
        $pageRows=isset($pagesReport['rows'])&&is_array($pagesReport['rows'])?$pagesReport['rows']:array();

        $sourcesReport=$analytics->runReport(array(
            'dateRanges'=>array(array('startDate'=>$startDate,'endDate'=>$endDate)),
            'dimensions'=>array(array('name'=>'sessionSource'),array('name'=>'sessionMedium')),
            'metrics'=>array(array('name'=>'sessions'),array('name'=>'activeUsers')),
            'orderBys'=>array(array('metric'=>array('metricName'=>'sessions'),'desc'=>true)),'limit'=>10
        ));
        $sourceRows=isset($sourcesReport['rows'])&&is_array($sourcesReport['rows'])?$sourcesReport['rows']:array();

        $devicesReport=$analytics->runReport(array(
            'dateRanges'=>array(array('startDate'=>$startDate,'endDate'=>$endDate)),
            'dimensions'=>array(array('name'=>'deviceCategory')),'metrics'=>array(array('name'=>'activeUsers')),
            'orderBys'=>array(array('metric'=>array('metricName'=>'activeUsers'),'desc'=>true)),'limit'=>10
        ));
        $deviceRows=isset($devicesReport['rows'])&&is_array($devicesReport['rows'])?$devicesReport['rows']:array();

        $realtimeReport=$analytics->runRealtimeReport(array('metrics'=>array(array('name'=>'activeUsers'))));
        $realtimeUsers=reporting_metric_value($realtimeReport,0,0);
    } catch (Throwable $e) {
        $reportingError=$e->getMessage();
    }
}

$racUsePortoLayout=true; $base='/administrace'; $assets=$base;
$title='Reporting | RAC'; $pageTitle='Reporting'; $currentPath='/administrace/reporting/reporting.php';
$viewerName=(string)($viewer['username']??'Administrátor'); $viewerRole=(string)($viewer['role']??'');
$roleLabel=function_exists('role_label')?role_label($viewerRole):$viewerRole;
$e=static function($value):string{return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');};
$menuGroups=array(
    array('Přehled',array(array('Dashboard','/administrace/dashboard.php','bx bx-home-alt'))),
    array('E-shop',array(array('Produkty','/administrace/eshop-produkty.php','icons icon-basket'))),
);
$selfPath='/administrace/reporting/reporting.php';
include dirname(__DIR__) . '/inc/header.class.php';
include dirname(__DIR__) . '/inc/leve-meny.class.php';
include __DIR__ . '/obsah/reporting.class.php';
include dirname(__DIR__) . '/inc/footer.class.php';
