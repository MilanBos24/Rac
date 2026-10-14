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

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    racContactResponse(405, 'Nepovolený požadavek.');
}
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$token = isset($_POST['csrf']) && is_string($_POST['csrf']) ? $_POST['csrf'] : '';
if ($token === '' || !isset($_SESSION['rac_contact_csrf']) || !hash_equals((string)$_SESSION['rac_contact_csrf'], $token)) {
    racContactResponse(403, 'Platnost formuláře vypršela. Obnovte stránku a zkuste to znovu.');
}
if (trim((string)($_POST['website'] ?? '')) !== '') {
    racContactResponse(200, 'Děkujeme za zprávu.');
}
if (!empty($_SESSION['rac_contact_last_sent']) && time() - (int)$_SESSION['rac_contact_last_sent'] < 45) {
    racContactResponse(429, 'Další zprávu lze odeslat za chvíli.');
}
$name = trim((string)($_POST['name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$phone = trim((string)($_POST['phone'] ?? ''));
$subjectCode = trim((string)($_POST['subject'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));
$subjectLabels = array('1' => 'Možnost 1', '2' => 'Možnost 2', '3' => 'Možnost 3');
if ($name === '' || strlen($name) > 240 || preg_match('/[\r\n]/', $name)
    || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254
    || strlen($phone) > 60 || preg_match('/[\r\n]/', $phone)
    || !isset($subjectLabels[$subjectCode]) || $message === '' || strlen($message) > 12000) {
    racContactResponse(422, 'Zkontrolujte prosím povinná pole formuláře.');
}
$to = 'stefan.rac1@gmail.com';
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
    . "Zpráva:\n" . $message . "\n";
$headers = array(
    'From: RAC <' . $from . '>',
    'Reply-To: ' . $email,
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'X-Mailer: RAC contact'
);
if (!mail($to, $subject, $body, implode("\r\n", $headers))) {
    error_log('RAC contact: mail() failed to accept message');
    racContactResponse(500, 'Zprávu se nepodařilo odeslat. Zkuste to později.');
}
$_SESSION['rac_contact_last_sent'] = time();
racContactResponse(200, 'Děkujeme, zpráva byla odeslána.');
