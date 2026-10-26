<?php
// RAC-ADMIN-DELIVERY-EMAIL-2026-10-25-04 / PHP 7.4+
declare(strict_types=1);
require_once __DIR__.'/config/session.php';
require_once __DIR__.'/config/db.php';
require_once __DIR__.'/config/auth.php';
require_once __DIR__.'/config/auth_roles.php';
require_min_role('admin');
require_once __DIR__.'/config/permissions.php';
$racUser=current_user_row();
$racSuper=$racUser && strtolower((string)($racUser['role']??''))==='superadmin';
if (!$racSuper && (!$racUser || !rac_can($pdo,$racUser,'contact.manage'))) {http_response_code(403);exit('Nemáte oprávnění spravovat e-mailové šablony.');}
require_once __DIR__.'/inc/rac-delivery-mail-template.php';
function racDeliveryH($v): string {return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
$errors=[];$success=isset($_GET['saved']) && $_GET['saved']==='1';
$default=racDeliveryMailDefaults();
$subject=$default['subject'];$body=$default['body'];$operatorCopyEmail='';
try {$loaded=racDeliveryMailTemplate($pdo);$subject=$loaded['subject'];$body=$loaded['body'];}
catch(Throwable $e){error_log('RAC delivery template: '.$e->getMessage());$errors[]='Nepodařilo se načíst nastavení e-mailu.';}
try {$opStmt=$pdo->query('SELECT operator_copy_email FROM shop_delivery_email_templates WHERE id=1 LIMIT 1');$operatorCopyEmail=trim((string)$opStmt->fetchColumn());}
catch(Throwable $e){error_log('RAC delivery copy settings: '.$e->getMessage());$errors[]='Nelze načíst adresu provozovatele. Ověřte SQL migraci 025.';}
$csrf=csrf_token('rac_delivery_mail_template');
if (($_SERVER['REQUEST_METHOD']??'')==='POST') {
 $operatorCopyEmail=trim((string)($_POST['operator_copy_email']??''));
 $subject=trim((string)($_POST['email_subject']??''));
 $body=str_replace(["\r\n","\r"],"\n",(string)($_POST['email_body']??''));
 if (!csrf_valid('rac_delivery_mail_template',(string)($_POST['csrf']??''))) $errors[]='Neplatný bezpečnostní token.';
 if ($operatorCopyEmail!=='' && (!filter_var($operatorCopyEmail,FILTER_VALIDATE_EMAIL) || strlen($operatorCopyEmail)>254 || preg_match('/[\r\n]/',$operatorCopyEmail))) $errors[]='Zadejte platnou adresu provozovatele, nebo pole ponechte prázdné.';
 if ($subject==='' || mb_strlen($subject)>255 || preg_match('/[\r\n]/',$subject)) $errors[]='Předmět musí mít 1–255 znaků a nesmí obsahovat nový řádek.';
 if (trim($body)==='' || strlen($body)>50000) $errors[]='Text e-mailu je prázdný nebo příliš dlouhý (max. 50 000 bajtů).';
 if (strpos($body,'{download_links}')===false) $errors[]='V textu musí zůstat {download_links}; jinak zákazník nedostane odkazy ke stažení.';
 if (preg_match('/\{[^\r\n{}]{1,100}\}/u',$subject.'\n'.$body,$matches)) {
  preg_match_all('/\{[^\r\n{}]{1,100}\}/u',$subject.'\n'.$body,$all);
  foreach(array_unique($all[0]) as $token) if(!in_array($token,['{order_number}','{download_links}','{validity_days}'],true)) $errors[]='Neznámá proměnná: '.racDeliveryH($token);
 }
 if (!$errors) {
  try {
   $stmt=$pdo->prepare('INSERT INTO shop_delivery_email_templates (id,email_subject,email_body,operator_copy_email) VALUES (1,?,?,?) ON DUPLICATE KEY UPDATE email_subject=VALUES(email_subject),email_body=VALUES(email_body),operator_copy_email=VALUES(operator_copy_email)');
   $stmt->execute([$subject,$body,$operatorCopyEmail!==''?$operatorCopyEmail:null]);
   header('Location: eshop-dorucovaci-email.php?saved=1',true,303);exit;
  } catch(Throwable $e){error_log('RAC delivery template save: '.$e->getMessage());$errors[]='Uložení se nezdařilo. Zkontrolujte SQL migrace 024 a 025.';}
 }
}
$example=racDeliveryMailRender($body,[
 'order_number'=>'RAC-UKAZKA-001',
 'validity_days'=>'30',
 'download_links'=>"Poznaj sám seba (EPUB)\n[bezpečný odkaz k EPUB]\n\nPoznaj sám seba (PDF)\n[bezpečný odkaz k PDF]\n\n"
]);
$racUsePortoLayout=true;$base='/administrace';$assets=$base;
$title='Nastavení doručovacího e-mailu | RAC';$pageTitle='Nastavení doručovacího e-mailu';
$currentPath='/administrace/eshop-dorucovaci-email.php';
$viewer=current_user_row();$viewerName=(string)($viewer['username']??'Administrátor');
$viewerRole=(string)($viewer['role']??'');$roleLabel=function_exists('role_label')?role_label($viewerRole):$viewerRole;
$menuGroups=[];
// Sdilena Porto hlavicka vyzaduje $e pro escapovani HTML (vcetne <title>).
$e=static function($value): string {return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');};
include __DIR__.'/inc/header.class.php';include __DIR__.'/inc/leve-meny.class.php';
?>
<section role="main" class="content-body content-body-modern mt-0">
<header class="page-header"><h2>Nastavení doručovacího e-mailu</h2></header>
<div class="row pt-3 mb-4"><div class="col-12">
<?php if($success): ?><div class="alert alert-success">Nastavení doručovacího e-mailu bylo uloženo.</div><?php endif; ?>
<?php foreach($errors as $error): ?><div class="alert alert-danger"><?= racDeliveryH($error) ?></div><?php endforeach; ?>
<div class="alert alert-info">Nastavení zprávy, která se odesílá zákazníkovi při doručení digitálních knih. <strong>Odkazy ke stažení vytváří systém automaticky.</strong> Změny se projeví až v nově odesílaných e-mailech.</div>
<div class="row g-3">
<div class="col-lg-7"><section class="card"><header class="card-header"><h2 class="card-title">Obsah doručovacího e-mailu</h2></header><div class="card-body">
<form method="post" action="eshop-dorucovaci-email.php" accept-charset="UTF-8">
<input type="hidden" name="csrf" value="<?= racDeliveryH($csrf) ?>">
<div class="mb-3"><label for="rac-mail-subject" class="form-label">Předmět zprávy</label><input id="rac-mail-subject" name="email_subject" class="form-control" maxlength="255" required value="<?= racDeliveryH($subject) ?>"></div>
<div class="mb-3"><label for="rac-mail-copy" class="form-label">Kopie e-mailu provozovateli e-shopu (nepovinné)</label><input id="rac-mail-copy" type="email" name="operator_copy_email" class="form-control" maxlength="254" autocomplete="email" placeholder="napr. objednavky@domena.cz" value="<?= racDeliveryH($operatorCopyEmail) ?>"><div class="form-text">Po odeslání zákazníkovi přijde stejná zpráva také na tuto adresu, včetně odkazů ke stažení. Prázdné pole = kopie vypnutá.</div></div>
<div class="mb-3"><label for="rac-mail-body" class="form-label">Text e-mailu</label><textarea id="rac-mail-body" name="email_body" class="form-control" rows="16" required style="font-family:ui-monospace,SFMono-Regular,Consolas,monospace;white-space:pre-wrap"><?= racDeliveryH($body) ?></textarea></div>
<div class="d-flex gap-2 flex-wrap"><button class="btn btn-primary" type="submit"><i class="fas fa-save me-1"></i> Uložit nastavení</button><button class="btn btn-default" type="button" id="rac-mail-load-default">Načíst doporučenou dvojjazyčnou šablonu</button></div>
</form></div></section></div>
<div class="col-lg-5"><section class="card mb-3"><header class="card-header"><h2 class="card-title">Dostupné proměnné</h2></header><div class="card-body">
<p class="text-muted">Popisky jednotlivých souborů se tvoří automaticky podle názvu knihy v jazyce vydání a formátu (PDF / EPUB / MP3…). Stejný blok odkazů lze do e-mailu vložit dvakrát – pro češtinu i angličtinu.</p><p><code>{order_number}</code> – číslo objednávky</p><p><code>{validity_days}</code> – doba platnosti odkazu ve dnech</p><p><code>{download_links}</code> – zabezpečené odkazy ke stažení <strong>(povinné)</strong></p>
<p class="text-muted mb-0">Doručovací zpráva je prostý text. HTML se nevykonává. Náhled je ukázkový a neobsahuje skutečné přístupové odkazy.</p>
</div></section>
<section class="card"><header class="card-header"><h2 class="card-title">Náhled posledního načtení stránky</h2></header><div class="card-body"><p class="fw-bold"><?= racDeliveryH(racDeliveryMailRender($subject,['order_number'=>'RAC-UKAZKA-001'])) ?></p><pre style="white-space:pre-wrap;overflow-wrap:anywhere;font-family:inherit"><?= racDeliveryH($example) ?></pre><p class="text-muted mb-0">Po úpravě textu náhled aktualizuješ uložením stránky.</p></div></section></div>
</div></div></div></section>
<script>document.getElementById('rac-mail-load-default').addEventListener('click',function(){if(!confirm('Nahradit text ve formuláři doporučenou dvojjazyčnou šablonou? Uložené nastavení se změní až po kliknutí na Uložit.'))return;document.getElementById('rac-mail-subject').value="Digitální knihy k objednávce {order_number}";document.getElementById('rac-mail-body').value="Dobrý den,\n\nděkujeme za objednávku č. {order_number}.\n\nZakoupené digitální soubory si stáhnete zde:\n\n{download_links}Odkazy mají platnost {validity_days} dní od vytvoření. Nesdílejte je s dalšími osobami.\n\nDěkujeme za váš nákup.\n\n----------------------------------------\n\nHello,\n\nthank you for your order no. {order_number}.\n\nYou can download your purchased digital files here:\n\n{download_links}The download links are valid for {validity_days} days from their creation. Please do not share them with anyone else.\n\nThank you for your purchase.\n";});</script>
<?php include __DIR__.'/inc/footer.class.php'; ?>
