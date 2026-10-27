<?php
// RAC servisni stranka Billing | Porto/BOS24 | 2026-10-20-11.35 | PHP 7.4+
declare(strict_types=1);
require_once __DIR__.'/../config/session.php';
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/auth.php';
require_once __DIR__.'/../config/auth_roles.php';
require_login();
$viewer=current_user_row();
if (!$viewer || strtolower((string)($viewer['role']??'')) !== 'superadmin') {
    http_response_code(403);
    exit('Přístup odepřen. Stránka je dostupná pouze superadminovi.');
}
$e=static function($v):string{return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');};
$racUsePortoLayout=true;$base='/administrace';$assets=$base;
$viewerName=(string)($viewer['username']??'Superadmin');
$viewerRole=(string)($viewer['role']??'superadmin');
$roleLabel=function_exists('role_label')?role_label($viewerRole):'Superadmin';
$menuGroups = [
    ['Přehled', [
        ['Dashboard', '/administrace/dashboard.php', 'bx bx-home-alt'],
    ]],
    ['Administrace', [
        ['Vložit administrátora', '/administrace/sprava-admin.php', 'icons icon-user-follow'],
        ['Zobrazit administrátory', '/administrace/sprava-admin-zobrazit.php', 'icons icon-people'],
        ['Jazyky', '/administrace/jazyky.php', 'icons icon-globe'],
    ]],
    ['Obsah webu', [
        ['Úvodní stránka', '/administrace/obsah-uvod.php', 'icons icon-home'],
        ['O autorovi', '/administrace/obsah-autor.php', 'icons icon-note'],
        ['Kontakt', '/administrace/obsah-kontakt.php', 'icons icon-envelope'],
    ]],
    ['E-shop', [
        ['Produkty', '/administrace/eshop-produkty.php', 'icons icon-basket'],
        ['Doprava', '/administrace/eshop-doprava.php', 'icons icon-drawer'],
    ]],
    ['Billing', [
        ['Nastavení Stripe', '/administrace/billing/admin.php', 'el el-cogs'],
        ['Produkty', '/administrace/billing/admin-produkty.php', 'icons icon-basket'],
        ['Platby', '/administrace/billing/admin-platby.php', 'el el-credit-card'],
        ['Předplatné', '/administrace/billing/admin-predplatne.php', 'icons icon-refresh'],
        ['Webhooky', '/administrace/billing/admin-webhooky.php', 'icons icon-shuffle'],
        ['Diagnostika', '/administrace/billing/admin-diagnostika.php', 'icons icon-wrench'],
    ]],
];
$currentPath='/administrace/billing/admin-diagnostika.php';
$pageTitle='Diagnostika';$title='Diagnostika | RAC';
require_once __DIR__.'/settings.php';
$checks=[];$mode='test';$baseUrl='';$billingEnabled=false;$apiResult=null;$errors=[];
try{
 $mode=(string)billing_setting_get($pdo,'mode','test');$baseUrl=(string)billing_setting_get($pdo,'base_url','');$billingEnabled=(string)billing_setting_get($pdo,'billing_enabled','0')==='1';
 $checks=[
  ['Nastavení Billing',billing_settings_table_ready($pdo)],
  ['OpenSSL',function_exists('openssl_encrypt')],
  ['PHP cURL',function_exists('curl_init')],
  ['HTTPS Base URL',$baseUrl!=='' && filter_var($baseUrl,FILTER_VALIDATE_URL) && strpos($baseUrl,'https://')===0],
  ['Stripe testovací klíč',billing_setting_configured($pdo,'stripe_test_secret_key')],
  ['Stripe live klíč',billing_setting_configured($pdo,'stripe_live_secret_key')],
  ['Testovací webhook secret',billing_setting_configured($pdo,'stripe_test_webhook_secret')],
  ['Live webhook secret',billing_setting_configured($pdo,'stripe_live_webhook_secret')],
 ];
}catch(Throwable $ex){error_log('RAC Billing service diagnostics: '.$ex->getMessage());$errors[]='Diagnostiku Billingu se nepodařilo načíst.';}
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
 if(!csrf_valid('rac_billing_service_diagnostic',(string)($_POST['csrf']??''))){$errors[]='Neplatný bezpečnostní token.';}
 elseif(($_POST['action']??'')==='test_stripe'){
  try{
   $secret=(string)billing_setting_get($pdo,$mode==='live'?'stripe_live_secret_key':'stripe_test_secret_key','');
   if($secret===''||!function_exists('curl_init'))throw new RuntimeException('Chybí Stripe klíč nebo PHP cURL.');
   $ch=curl_init('https://api.stripe.com/v1/payment_intents?limit=1');
   curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPAUTH=>CURLAUTH_BASIC,CURLOPT_USERPWD=>$secret.':',CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>20]);
   $response=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
   $apiResult=($response!==false && $code>=200 && $code<300)?'Spojení se Stripe API funguje (HTTP '.$code.').':'Stripe API neodpovědělo úspěšně (HTTP '.$code.').';
  }catch(Throwable $ex){$apiResult=$ex->getMessage();}
 }
}
$csrf=csrf_token('rac_billing_service_diagnostic');
include __DIR__.'/../inc/header.class.php';include __DIR__.'/../inc/leve-meny.class.php';
?>
<section role="main" class="content-body content-body-modern mt-0"><header class="page-header"><h2>Diagnostika – servis Billing</h2></header>
<div class="row mt-4"><div class="col-12"><p class="text-muted">Kontrola konfigurace původního platebního modulu Billing. Nejde o samostatný test webhooku e-shopu.</p></div></div>
<?php foreach($errors as $err):?><div class="alert alert-warning"><?= $e($err) ?></div><?php endforeach;?>
<?php if($apiResult!==null):?><div class="alert alert-info"><?= $e($apiResult) ?></div><?php endif;?>
<div class="row mb-4"><div class="col-lg-4 mb-3"><section class="card card-featured-left card-featured-primary"><div class="card-body"><div class="widget-summary"><div class="widget-summary-col"><div class="summary"><h4 class="title">Režim Stripe</h4><div class="info"><strong class="amount"><?= $e(strtoupper($mode)) ?></strong></div></div></div></div></div></section></div><div class="col-lg-4 mb-3"><section class="card card-featured-left card-featured-info"><div class="card-body"><div class="widget-summary"><div class="widget-summary-col"><div class="summary"><h4 class="title">Billing</h4><div class="info"><strong class="amount"><?= $billingEnabled?'Aktivní':'Neaktivní' ?></strong></div></div></div></div></div></section></div><div class="col-lg-4 mb-3"><section class="card card-featured-left card-featured-warning"><div class="card-body"><div class="widget-summary"><div class="widget-summary-col"><div class="summary"><h4 class="title">Base URL</h4><div class="info text-break"><span><?= $e($baseUrl?:'Nenastavena') ?></span></div></div></div></div></div></section></div></div>
<section class="card mb-4"><header class="card-header"><h2 class="card-title">Kontrola konfigurace</h2></header><div class="card-body"><div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th>Kontrola</th><th>Stav</th></tr></thead><tbody><?php foreach($checks as $check):?><tr><td><?= $e($check[0]) ?></td><td><span class="badge <?= $check[1]?'bg-success':'bg-warning text-dark' ?>"><?= $check[1]?'OK':'Vyžaduje kontrolu' ?></span></td></tr><?php endforeach;?></tbody></table></div></div></section>
<section class="card mb-4"><header class="card-header"><h2 class="card-title">Spojení se Stripe</h2></header><div class="card-body"><p class="text-muted">Test provádí pouze čtení platebních záměrů přes Stripe API. Nevytváří platbu.</p><form method="post"><input type="hidden" name="csrf" value="<?= $e($csrf) ?>"><input type="hidden" name="action" value="test_stripe"><button type="submit" class="btn btn-primary">Otestovat Stripe API</button></form></div></section></section><?php include __DIR__.'/../inc/footer.class.php'; ?>
