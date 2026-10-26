<?php
// RAC FAKTURACE STEP05-01 | rucni vystaveni po kontrole | PHP 7.4+
declare(strict_types=1);
require_once __DIR__.'/../config/session.php';
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';
require_once __DIR__.'/../config/auth_roles.php';
require_once __DIR__.'/../config/permissions.php';
require_once __DIR__.'/inc/rac-faktury-core.php';
require_min_role('admin');
$viewer=current_user_row();
$isSuper=$viewer && strtolower((string)($viewer['role']??''))==='superadmin';
if (!$viewer || (!$isSuper && !rac_can($pdo,$viewer,'orders.view'))) {http_response_code(403);exit('Nemáte oprávnění.');}
if (session_status()!==PHP_SESSION_ACTIVE) {http_response_code(503);exit('Relace není aktivní.');}
if (empty($_SESSION['rac_invoice_issue_csrf']))$_SESSION['rac_invoice_issue_csrf']=bin2hex(random_bytes(24));
$csrf=(string)$_SESSION['rac_invoice_issue_csrf'];
$id=filter_var($_POST['order_id']??$_GET['order_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
if(!$id){http_response_code(400);exit('Neplatné ID objednávky.');}
$errors=[];$bundle=null;$existingId=0;
try {
    $bundle=racFakturaOrder($pdo,(int)$id);
    $stmt=$pdo->prepare('SELECT id FROM fakturace_doklady WHERE order_id=?');$stmt->execute([(int)$id]);$existingId=(int)$stmt->fetchColumn();
    if(!$existingId)racFakturaCheckForIssue($bundle);
} catch(Throwable $e){$errors[]=$e->getMessage();}
if (($_SERVER['REQUEST_METHOD']??'')==='POST') {
    if(!hash_equals($csrf,(string)($_POST['csrf']??'')))$errors[]='Neplatný bezpečnostní token.';
    if(!$errors && !$existingId){
        try { $newId=racFakturaIssue($pdo,(int)$id);header('Location: faktura-pdf.php?id='.$newId);exit; }
        catch(Throwable $e){error_log('RAC invoice issue: '.$e->getMessage());$errors[]=$e->getMessage();}
    }
}
$racUsePortoLayout=true;$base='/administrace';$assets=$base;
$title='Vystavit fakturu | RAC';$pageTitle=$title;
$currentPath='/administrace/fakturace/faktury.php';
$viewerName=(string)($viewer['username']??'Administrátor');$viewerRole=(string)($viewer['role']??'');
$roleLabel=function_exists('role_label')?role_label($viewerRole):$viewerRole;
$e=static function($v):string{return racFakturaH($v);};$menuGroups=[];
include __DIR__.'/../inc/header.class.php';include __DIR__.'/../inc/leve-meny.class.php';
?>
<section role="main" class="content-body content-body-modern mt-0">
<header class="page-header"><h2>Kontrola a vystavení faktury</h2></header>
<div class="row pt-3"><div class="col-12">
<div class="mb-3"><a class="btn btn-default" href="faktury.php">← Zpět na faktury</a></div>
<?php foreach($errors as $error): ?><div class="alert alert-warning"><?= $e($error) ?></div><?php endforeach; ?>
<?php if($existingId): ?><div class="alert alert-info">K této objednávce již existuje faktura. <a href="faktura-pdf.php?id=<?= $existingId ?>">Otevřít PDF</a>.</div><?php endif; ?>
<?php if($bundle): $o=$bundle['order'];$p=$bundle['payment'];$settings=$bundle['config']; ?>
<section class="card mb-4"><header class="card-header"><h2 class="card-title">Objednávka <?= $e($o['order_number']) ?></h2></header><div class="card-body">
<div class="row mb-3"><div class="col-lg-6"><strong>Dodavatel</strong><p><?= $e($settings['nazev_dodavatele']) ?><br>IČO: <?= $e($settings['ico']??'') ?><br>DIČ: <?= $e($settings['dic']??'') ?></p></div><div class="col-lg-6"><strong>Odběratel</strong><p><?= $e($o['billing_name']) ?><br><?= $e($o['billing_company']??'') ?><br><?= $e($o['billing_street']??'') ?>, <?= $e($o['billing_postal']??'') ?> <?= $e($o['billing_city']??'') ?><br><?= $e($o['email']) ?></p></div></div>
<div class="table-responsive"><table class="table table-striped"><thead><tr><th>Položka</th><th>Vydání</th><th class="text-end">Počet</th><th class="text-end">Celkem</th></tr></thead><tbody>
<?php foreach($bundle['items'] as $it): ?><tr><td><?= $e($it['title']) ?></td><td><?= $e($it['variant_kind']) ?> / <?= $e($it['variant_language_code']) ?></td><td class="text-end"><?= (int)$it['quantity'] ?></td><td class="text-end"><?= $e(racFakturaMoney($it['line_total'],'CZK')) ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<p class="text-end"><strong>Uhrazeno: <?= $e(racFakturaMoney($o['total'],(string)$o['currency'])) ?></strong></p>
<p class="text-muted small">Platba je ověřena v tabulce shop_payments. Faktura převezme údaje z objednávky jako neměnný záznam. E-mail nebude odeslán.</p>
<a class="btn btn-default me-2" target="_blank" rel="noopener" href="nahled-pdf.php?order_id=<?= (int)$id ?>">Náhled PDF bez vystavení</a>
<?php if(!$errors && !$existingId): ?><form method="post" onsubmit="return confirm('Opravdu vystavit nevratnou fakturu k této objednávce?');"><input type="hidden" name="csrf" value="<?= $e($csrf) ?>"><input type="hidden" name="order_id" value="<?= (int)$id ?>"><button class="btn btn-primary" type="submit">Vystavit fakturu a otevřít PDF</button></form><?php endif; ?>
</div></section>
<?php endif; ?>
</div></div></section>
<?php include __DIR__.'/../inc/footer.class.php'; ?>
