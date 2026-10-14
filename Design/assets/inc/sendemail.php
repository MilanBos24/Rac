<?php
// RAC kontakt - PHP 7.4+
declare(strict_types=1);
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function racContactResponse(int $code, string $message): void
{
    http_response_code($code);
    $type = ($code === 200) ? 'success' : 'error';
    echo '<div class="inner ' . $type . '" data-contact-status="' . $type . '" role="status"><p class="' . $type . '">'
        . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p></div>';
    exit;
}

$racLang = strtolower((string)($_POST['lang'] ?? 'cs'));
if (!in_array($racLang, array('cs','sk','pl','en','de','fr'), true)) { $racLang = 'cs'; }
$racLangFile = dirname(__DIR__, 2) . '/lang/' . $racLang . '.php';
$racTranslations = is_file($racLangFile) ? (array)require $racLangFile : array();
function racContactText(string $key): string {
    global $racTranslations;
    return (string)($racTranslations[$key] ?? $key);
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    racContactResponse(405, racContactText('contact.status.invalid_request'));
}
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$token = isset($_POST['csrf']) && is_string($_POST['csrf']) ? $_POST['csrf'] : '';
if ($token === '' || !isset($_SESSION['rac_contact_csrf']) || !hash_equals((string)$_SESSION['rac_contact_csrf'], $token)) {
    racContactResponse(403, racContactText('contact.status.csrf'));
}
if (trim((string)($_POST['website'] ?? '')) !== '') {
    racContactResponse(200, racContactText('contact.status.success'));
}
if (!empty($_SESSION['rac_contact_last_sent']) && time() - (int)$_SESSION['rac_contact_last_sent'] < 45) {
    racContactResponse(429, racContactText('contact.status.rate_limit'));
}
$name = trim((string)($_POST['name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$phone = trim((string)($_POST['phone'] ?? ''));
$subjectCode = trim((string)($_POST['subject'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));
$subjectLabels = array('creation' => racContactText('contact.subject.creation'), 'other' => racContactText('contact.subject.other'));
$customSubjectId = null;
if (preg_match('/^custom:([1-9][0-9]*)$/', $subjectCode, $match)) {
    $customSubjectId = (int)$match[1];
}
if ($name === '' || strlen($name) > 240 || preg_match('/[\r\n]/', $name)
    || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254
    || strlen($phone) > 60 || preg_match('/[\r\n]/', $phone)
    || ($customSubjectId === null && !isset($subjectLabels[$subjectCode])) || $message === '' || strlen($message) > 12000) {
    racContactResponse(422, racContactText('contact.status.invalid'));
}
// Recipient and IP blocklist are managed in RAC administration.
try {
    require_once dirname(__DIR__, 2) . '/inc/01.config.class.php';
    $contactPdo = racFrontendPdo();
    if (!$contactPdo instanceof PDO) { throw new RuntimeException('No database connection'); }
    $to = (string)$contactPdo->query('SELECT recipient_email FROM contact_form_settings WHERE id = 1')->fetchColumn();
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) { throw new RuntimeException('Missing recipient email'); }
    if ($customSubjectId !== null) {
        $subjectColumn = 'label_' . $racLang;
        $subjectStmt = $contactPdo->prepare("SELECT label_cs, " . $subjectColumn . " AS translated FROM contact_form_subjects WHERE id = ? AND is_active = 1 LIMIT 1");
        $subjectStmt->execute(array($customSubjectId));
        $subjectRow = $subjectStmt->fetch(PDO::FETCH_ASSOC);
        if (!$subjectRow) { racContactResponse(422, racContactText('contact.status.invalid')); }
        $subjectLabels[$subjectCode] = trim((string)$subjectRow['translated']) !== '' ? (string)$subjectRow['translated'] : (string)$subjectRow['label_cs'];
    }
    $remoteIp = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    $ipStmt = $contactPdo->prepare('SELECT 1 FROM contact_blocked_ips WHERE ip_address = ? LIMIT 1');
    $ipStmt->execute(array($remoteIp));
    if ($ipStmt->fetchColumn()) { racContactResponse(403, racContactText('contact.status.blocked')); }
} catch (Throwable $e) {
    error_log('RAC contact configuration: ' . $e->getMessage());
    racContactResponse(503, racContactText('contact.status.unavailable'));
}
$host = strtolower((string)($_SERVER['HTTP_HOST'] ?? 'stefanrac.mil24.cz'));
$host = preg_replace('/:\\d+$/', '', $host);
if (!preg_match('/^[a-z0-9.-]+$/', $host) || strpos($host, '.') === false) {
    $host = 'stefanrac.mil24.cz';
}
$from = 'no-reply@' . $host;
$subject = 'RAC kontakt: ' . $subjectLabels[$subjectCode];
$body = "Nová zpráva z kontaktního formuláře RAC\n\n"
    . "Jméno: " . $name . "\n"
    . "E-mail: " . $email . "\n"
    . "Telefon: " . $phone . "\n"
    . "Předmět: " . $subjectLabels[$subjectCode] . "\n\n"
    . "Zpráva:\n" . $message . "\n\n"
    . "IP: " . (string)($_SERVER['REMOTE_ADDR'] ?? '') . "\n"
    . "Prohlížeč: " . substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 250) . "\n"
    . "Stránka: " . substr((string)($_SERVER['HTTP_REFERER'] ?? ''), 0, 300) . "\n"
    . "Čas: " . date('Y-m-d H:i:s') . "\n";
$headers = array(
    'From: RAC <' . $from . '>',
    'Reply-To: ' . $email,
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'X-Mailer: RAC contact'
);
if (!mail($to, $subject, $body, implode("\r\n", $headers))) {
    error_log('RAC contact: mail() failed to accept message');
    racContactResponse(500, racContactText('contact.status.failure'));
}
$_SESSION['rac_contact_last_sent'] = time();
racContactResponse(200, racContactText('contact.status.success'));
