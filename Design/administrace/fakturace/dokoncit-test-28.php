<?php
// RAC | one-time TEST order 28 finish | 2026-10-25-STEP08-DEBUG02 | PHP 7.4+
// DELETE THIS FILE FROM SERVER AFTER TEST. ONLY SUPERADMIN. POST + CSRF.
declare(strict_types=1);
require_once __DIR__.'/../config/session.php';
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';
require_once __DIR__.'/../config/auth_roles.php';
require_min_role('superadmin');
header('Cache-Control: private, no-store, max-age=0');
header('X-Robots-Tag: noindex, nofollow');
if (!isset($pdo) || !($pdo instanceof PDO)) { http_response_code(503); exit('Databáze není dostupná.'); }
$e=static function($x):string {return htmlspecialchars((string)$x, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');};
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
if (!isset($_SESSION['rac_test28_csrf'])) {$_SESSION['rac_test28_csrf']=bin2hex(random_bytes(32));}
$csrf=(string)$_SESSION['rac_test28_csrf'];
$orderId=28;
$messages=[];
$error='';
$invoice=null;
$pdfOk=false;
$mailStatus='';
try {
    require_once __DIR__.'/inc/rac-faktury-core.php';
    require_once __DIR__.'/inc/rac-faktury-attachment.php';
    $st=$pdo->prepare("SELECT o.id,o.order_number,o.status,p.environment,p.status AS payment_status,p.amount_minor,p.currency FROM shop_orders o JOIN shop_payments p ON p.order_id=o.id WHERE o.id=? AND p.status='paid' AND p.environment='test' ORDER BY p.id DESC LIMIT 1");
    $st->execute([$orderId]); $row=$st->fetch(PDO::FETCH_ASSOC);
    if (!$row || (string)$row['status']!=='paid' || (string)$row['currency']!=='CZK') {throw new RuntimeException('Objednávka 28 musí být uhrazena ve Stripe TEST/CZK.');}
    $getInvoice=static function(PDO $pdo,int $orderId):?array {
        $q=$pdo->prepare('SELECT id,cislo_dokladu,stav FROM fakturace_doklady WHERE order_id=? LIMIT 1');$q->execute([$orderId]);$data=$q->fetch(PDO::FETCH_ASSOC);return $data?:null;
    };
    $invoice=$getInvoice($pdo,$orderId);
    if ($invoice && strpos((string)$invoice['cislo_dokladu'],'TEST-')!==0) {throw new RuntimeException('Objednávka má doklad bez TEST označení. Test byl zastaven.');}
    if (($_SERVER['REQUEST_METHOD']??'')==='POST') {
        if (!hash_equals($csrf,(string)($_POST['csrf']??''))) {throw new RuntimeException('Neplatný bezpečnostní token. Obnov stránku.');}
        $action=(string)($_POST['action']??'');
        if ($action==='issue') {
            $id=racFakturaIssue($pdo,$orderId);
            $invoice=$getInvoice($pdo,$orderId);
            if (!$invoice || strpos((string)$invoice['cislo_dokladu'],'TEST-')!==0) {throw new RuntimeException('Vystavení nevrátilo testovací fakturu.');}
            $messages[]='TEST doklad uložen: ID '.$id.' / '.$invoice['cislo_dokladu'];
        } elseif ($action==='send') {
            if (!$invoice) {throw new RuntimeException('Nejprve je nutné vystavit testovací fakturu.');}
            $q=$pdo->prepare("SELECT COUNT(*) FROM shop_digital_mail_log WHERE order_id=? AND status='accepted'");$q->execute([$orderId]);
            if ((int)$q->fetchColumn()>0) {throw new RuntimeException('Zpráva už byla pro tuto objednávku přijata. Opakované odeslání je z bezpečnostních důvodů zablokováno.');}
            // Do not send if attachment renderer fails. This ensures the test email cannot omit PDF.
            $attachment=racFakturaPdfAttachment($pdo,$orderId);
            if (!$attachment || strncmp((string)$attachment['data'],'%PDF-',5)!==0) {throw new RuntimeException('PDF příloha není dostupná. E-mail nebude odeslán.');}
            require_once __DIR__.'/../inc/rac-digital.php';
            require_once __DIR__.'/../inc/rac-mail.php';
            if (!function_exists('racDigitalSmtpEmail')) {throw new RuntimeException('Chybí funkce pro doručovací e-mail.');}
            // Assert installed mail transport supports invoice attachment; fail rather than send without it.
            $mailPhp=(string)file_get_contents(__DIR__.'/../inc/rac-mail.php');
            if (strpos($mailPhp,'racFakturaPdfAttachment(')===false) {throw new RuntimeException('Aktuální rac-mail.php nepřipojuje PDF. E-mail nebyl odeslán.');}
            $wasSent=racDigitalSmtpEmail($pdo,$orderId,'manual');
            if (!$wasSent) {throw new RuntimeException('Odeslání e-mailu nebylo potvrzeno.');}
            $messages[]='Odeslání bylo přijato poštovním systémem; zkontroluj zákaznický e-mail, kopii provozovateli a PDF přílohu.';
        } else {throw new RuntimeException('Neplatná akce.');}
    }
    if ($invoice) {
        $attachment=racFakturaPdfAttachment($pdo,$orderId);
        $pdfOk=$attachment!==null && strncmp((string)$attachment['data'],'%PDF-',5)===0;
        if ($pdfOk) { $messages[]='PDF je v pořádku: '.$attachment['filename'].' ('.strlen($attachment['data']).' B).'; }
    }
    $q=$pdo->prepare('SELECT status,error_code,source,sent_at FROM shop_digital_mail_log WHERE order_id=? ORDER BY id DESC LIMIT 1');$q->execute([$orderId]);$log=$q->fetch(PDO::FETCH_ASSOC);
    $mailStatus=$log?((string)$log['status'].' / '.(string)$log['source'].' / '.(string)$log['sent_at']):'Zatím žádný záznam.';
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) {$pdo->rollBack();}
    $error=get_class($ex).': '.$ex->getMessage();
    error_log('RAC one-off TEST 28: '.$error);
}
header('Content-Type: text/html; charset=UTF-8');
?><!doctype html><html lang="cs"><head><meta charset="utf-8"><meta name="robots" content="noindex,nofollow"><title>RAC – dokončení testu faktury 28</title><style>body{font:16px/1.5 system-ui,sans-serif;max-width:850px;margin:40px auto;padding:0 20px;color:#222}button{padding:12px 18px;border:0;background:#292929;color:#fff;border-radius:5px;cursor:pointer}button[disabled]{opacity:.4;cursor:not-allowed}form{margin:18px 0}strong{font-weight:700}.error{padding:12px;border:1px solid #be123c;color:#991b1b;background:#fff1f2}.good{padding:9px;background:#f1f5f9}code{overflow-wrap:anywhere}</style></head><body><h1>RAC – objednávka 28 (Stripe TEST)</h1><p>Jednorázový test pouze pro superadministrátora. Při chybě zobrazí přesný důvod. <strong>Po dokončení soubor ze serveru smaž.</strong></p>
<?php if($error!==''):?><div class="error"><strong>CHYBA:</strong> <code><?= $e($error) ?></code></div><?php endif;?>
<?php foreach($messages as $message):?><p class="good"><?= $e($message) ?></p><?php endforeach;?>
<p><strong>Faktura:</strong> <?= $invoice?$e($invoice['cislo_dokladu']).' (ID '.$e($invoice['id']).')':'Nevystavena' ?></p><p><strong>PDF:</strong> <?= $pdfOk?'OK':'Zatím neověřeno' ?></p><p><strong>Odeslání:</strong> <?= $e($mailStatus) ?></p>
<form method="post"><input type="hidden" name="csrf" value="<?= $e($csrf) ?>"><button name="action" value="issue" type="submit" <?= $error!==''?'disabled':'' ?>>1. Vystavit testovací fakturu a ověřit PDF</button></form>
<form method="post"><input type="hidden" name="csrf" value="<?= $e($csrf) ?>"><button name="action" value="send" type="submit" <?= (!$invoice||!$pdfOk||$error!=='')?'disabled':'' ?>>2. Odeslat e-mail s PDF (pouze jednou)</button></form>
<p>Pokud se objeví chyba, pošli mi její přesné znění. Nepokračuj druhým tlačítkem, dokud nebude PDF označeno jako OK.</p></body></html>
