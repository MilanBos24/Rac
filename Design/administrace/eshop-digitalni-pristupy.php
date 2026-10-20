<?php
// RAC digitalní distribuce - administrace / PHP 7.4+
declare(strict_types=1);
require_once __DIR__.'/config/session.php';
require_once __DIR__.'/config/db.php';
require_once __DIR__.'/config/auth.php';
require_once __DIR__.'/config/auth_roles.php';
require_once __DIR__.'/inc/rac-digital.php';
require_min_role('admin');
$viewer=current_user_row();
$e=static function($v):string{return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');};
$orderId=filter_var($_GET['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
$notice='';$error='';$grants=[];$order=null;$recentOrders=[];$diagnostics=[];
$csrf=csrf_token('rac_digital_admin');
try {
 if(!$orderId){
  // Stránka může být otevřena z navigace bez parametru id. Nabídneme bezpečný výběr objednávky.
  $st=$pdo->query("SELECT o.id,o.order_number,o.email,o.status,
     (SELECT COUNT(*) FROM shop_payments p WHERE p.order_id=o.id AND p.status='paid') AS paid_count
     FROM shop_orders o ORDER BY o.id DESC LIMIT 100");
  $recentOrders=$st->fetchAll(PDO::FETCH_ASSOC);
 } else {
  $st=$pdo->prepare('SELECT id,order_number,email,status FROM shop_orders WHERE id=?');$st->execute([$orderId]);$order=$st->fetch(PDO::FETCH_ASSOC);
  if(!$order)throw new RuntimeException('Objednávka nebyla nalezena.');
 }
 if($orderId && ($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
  if(!csrf_valid('rac_digital_admin',(string)($_POST['csrf']??'')))throw new RuntimeException('Neplatný CSRF token.');
  if((int)($_POST['order_id']??0)!==$orderId)throw new RuntimeException('Neplatná objednávka.');
  $action=(string)($_POST['action']??'');
  if($action==='grant'){
   $pdo->beginTransaction();try{$result=racDigitalGrant($pdo,(int)$orderId);$pdo->commit();$notice='Zpracováno: '.$result['digital'].' digitálních variant, vytvořeno '.$result['created'].' nových odkazů.';}catch(Throwable $ex){$pdo->rollBack();throw $ex;}
  }elseif($action==='resend'){
   if(!racDigitalPaid($pdo,(int)$orderId))throw new RuntimeException('Objednávka nemá potvrzenou platbu.');
   if(!racDigitalEmail($pdo,(int)$orderId))throw new RuntimeException('E-mail nelze odeslat: ověřte konfiguraci nebo funkci mail().');
   $notice='Požadavek na odeslání e-mailu byl přijat poštovním serverem (doručení se tím negarantuje).';
  }elseif($action==='revoke'){
   $accessId=filter_var($_POST['access_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
   if(!$accessId)throw new RuntimeException('Neplatný odkaz.');
   $st=$pdo->prepare('UPDATE shop_digital_access SET revoked_at=NOW() WHERE id=? AND order_id=? AND revoked_at IS NULL');$st->execute([$accessId,$orderId]);
   $notice=$st->rowCount()===1?'Odkaz byl zneplatněn.':'Odkaz již není aktivní.';
  }else throw new RuntimeException('Neznámá operace.');
 }
 if($orderId){
  // Diagnostics are read-only; don't expose encryption secrets or storage paths.
  $diagnostics[]=['Potvrzená platba',racDigitalPaid($pdo,(int)$orderId)?'Ano':'Ne'];
  $diagnostics[]=['Tabulka expedice',racDigitalTable($pdo,'shop_order_fulfillment')?'Připravena':'CHYBÍ – migrace 014'];
  $diagnostics[]=['Tabulka digitálních přístupů',racDigitalTable($pdo,'shop_digital_access')?'Připravena':'CHYBÍ – migrace 015'];
  try {
   $racVerifiedKey = racDigitalKey(); // stejná metoda jako při generování odkazů
   $racKeyReady = strlen($racVerifiedKey) === 32;
  } catch (Throwable $racKeyError) { $racKeyReady = false; }
  unset($racVerifiedKey);
  $diagnostics[]=['Šifrovací klíč',$racKeyReady?'Nastaven':'NENÍ NASTAVEN'];
  $diagnostics[]=['Privátní úložiště',is_file(__DIR__.'/config/digital_storage.php')?'Konfigurace existuje':'KONFIGURACE CHYBÍ'];
  if(racDigitalTable($pdo,'shop_order_items') && racDigitalColumn($pdo,'shop_order_items','variant_id')){
   $diag=$pdo->prepare("SELECT v.kind, COUNT(DISTINCT i.variant_id) AS qty FROM shop_order_items i JOIN product_variants v ON v.id=i.variant_id WHERE i.order_id=? GROUP BY v.kind");
   $diag->execute([(int)$orderId]);$kinds=[];
   foreach($diag->fetchAll(PDO::FETCH_ASSOC) as $kind){$kinds[]=$kind['kind'].' × '.$kind['qty'];}
   $diagnostics[]=['Zakoupené varianty',$kinds?implode(', ',$kinds):'NENALEZENY'];
  }else{$diagnostics[]=['Položky objednávky','Chybí tabulka nebo sloupec variant_id'];}
  if(racDigitalTable($pdo,'shop_order_fulfillment')){
   $status=$pdo->prepare('SELECT fulfillment_status FROM shop_order_fulfillment WHERE order_id=?');$status->execute([(int)$orderId]);
   $diagnostics[]=['Stav vyřízení',(string)($status->fetchColumn()?:'new (výchozí)')];
  }
 }
 if($orderId){
  if(!racDigitalTable($pdo,'shop_digital_access'))throw new RuntimeException('Nejprve spusťte SQL migraci 015_rac_digital_access.sql.');
  $st=$pdo->prepare('SELECT a.id,a.token_encrypted,a.expires_at,a.revoked_at,a.download_count,a.created_at,f.original_filename FROM shop_digital_access a JOIN product_files f ON f.id=a.file_id WHERE a.order_id=? ORDER BY a.id DESC');$st->execute([$orderId]);$grants=$st->fetchAll(PDO::FETCH_ASSOC);
 }
} catch(Throwable $ex){error_log('RAC digital admin: '.$ex->getMessage());$error=$ex->getMessage();}
$racUsePortoLayout=true;$base='/administrace';$assets=$base;$pageTitle='Digitální přístupy';$title='Digitální přístupy | RAC';
$viewerName=(string)($viewer['username']??'Administrátor');$viewerRole=(string)($viewer['role']??'');$roleLabel=role_label($viewerRole);
$menuGroups=[
 ['Přehled',[['Dashboard','/administrace/dashboard.php','bx bx-home-alt']]],
 ['Administrace',[['Vložit administrátora','/administrace/sprava-admin.php','icons icon-user-follow'],['Zobrazit administrátory','/administrace/sprava-admin-zobrazit.php','icons icon-people'],['Jazyky','/administrace/jazyky.php','icons icon-globe']]],
 ['Obsah webu',[['Úvodní stránka','/administrace/obsah-uvod.php','icons icon-home'],['O autorovi','/administrace/obsah-autor.php','icons icon-note'],['Kontakt','/administrace/obsah-kontakt.php','icons icon-envelope']]],
 ['E-shop',[['Produkty','/administrace/eshop-produkty.php','icons icon-basket'],['Sklad knih','/administrace/eshop-sklad.php','icons icon-layers'],['Objednávky','/administrace/eshop-objednavky.php','icons icon-list'],['Platby e-shopu','/administrace/eshop-platby.php','el el-credit-card'],['Digitální přístupy','/administrace/eshop-digitalni-pristupy.php','icons icon-link'],['Doprava','/administrace/eshop-doprava.php','icons icon-drawer']]],
 ['Platební brána',[['Nastavení Stripe','/administrace/billing/admin.php','el el-cogs'],['Platby','/administrace/billing/admin-platby.php','el el-credit-card'],['Webhooky','/administrace/billing/admin-webhooky.php','icons icon-shuffle'],['Diagnostika','/administrace/billing/admin-diagnostika.php','icons icon-wrench']]]
];
$currentPath='/administrace/eshop-digitalni-pristupy.php';
include __DIR__.'/inc/header.class.php';include __DIR__.'/inc/leve-meny.class.php';
?>
<section role="main" class="content-body content-body-modern mt-0">
<header class="page-header"><h2>Digitální přístupy objednávky</h2></header>
<div class="row pt-2 mb-4"><div class="col-12"><a class="btn btn-primary" href="<?= $orderId ? 'eshop-objednavka.php?id='.(int)$orderId : 'eshop-objednavky.php' ?>">← <?= $orderId ? 'Detail objednávky' : 'Přehled objednávek' ?></a></div></div>
<?php if($notice!==''): ?><div class="alert alert-success"><?= $e($notice) ?></div><?php endif; ?>
<?php if($error!==''): ?><div class="alert alert-warning"><?= $e($error) ?></div><?php endif; ?>
<?php if(!$orderId && !$error): ?>
<section class="card mb-4"><header class="card-header"><h2 class="card-title">Vyberte objednávku</h2></header><div class="card-body">
<p class="text-muted">Digitální přístupy se spravují pro konkrétní objednávku. Pro ověření testovacího nákupu vyberte zaplacenou objednávku níže.</p>
<div class="table-responsive"><table class="table table-hover"><thead><tr><th>Objednávka</th><th>Zákazník</th><th>Stav</th><th>Akce</th></tr></thead><tbody>
<?php foreach($recentOrders as $r): ?><tr><td><?= $e($r['order_number']) ?></td><td><?= $e($r['email']) ?></td><td><?= (int)$r['paid_count']>0 ? 'Zaplaceno (Stripe)' : $e($r['status']) ?></td><td><a class="btn btn-primary btn-sm" href="eshop-digitalni-pristupy.php?id=<?= (int)$r['id'] ?>">Digitální přístupy</a></td></tr><?php endforeach; ?>
<?php if(!$recentOrders): ?><tr><td colspan="4">Zatím nejsou uložené objednávky.</td></tr><?php endif; ?>
</tbody></table></div></div></section>
<?php endif; ?>
<?php if($order): ?>
<section class="card mb-4"><header class="card-header"><h2 class="card-title">Objednávka <?= $e($order['order_number']) ?></h2></header><div class="card-body">
<p>Zákazník: <?= $e($order['email']) ?>. Platba: <?= racDigitalPaid($pdo,(int)$orderId)?'ověřena':'nepotvrzena' ?>.</p>
<div class="d-flex flex-wrap gap-2">
<form method="post"><input type="hidden" name="csrf" value="<?= $e($csrf) ?>"><input type="hidden" name="order_id" value="<?= (int)$orderId ?>"><input type="hidden" name="action" value="grant"><button class="btn btn-primary" type="submit">Vytvořit chybějící odkazy</button></form>
<form method="post"><input type="hidden" name="csrf" value="<?= $e($csrf) ?>"><input type="hidden" name="order_id" value="<?= (int)$orderId ?>"><input type="hidden" name="action" value="resend"><button class="btn btn-primary" type="submit">Znovu odeslat e-mail</button></form>
</div>
<p class="text-muted small mt-3">Odkazy se tvoří pouze pro ověřeně zaplacenou objednávku. E-mail se odešle pouze při výslovně povolené konfiguraci. Testovací platby mohou mít skutečný zákaznický e-mail – testujte s vlastní adresou.</p>
</div></section>
<?php if($diagnostics): ?><section class="card mb-4"><header class="card-header"><h2 class="card-title">Kontrola automatického vyřízení</h2></header><div class="card-body"><p class="text-muted">Tato kontrola pomáhá určit, proč je zaplacená objednávka stále nová. Neukazuje žádné tajné klíče.</p><div class="table-responsive"><table class="table table-sm"><tbody><?php foreach($diagnostics as $d): ?><tr><th><?= $e($d[0]) ?></th><td><?= $e($d[1]) ?></td></tr><?php endforeach; ?></tbody></table></div><p class="small text-muted">Pokud jsou všechny podmínky splněné, použijte „Vytvořit chybějící odkazy“. Chyba se zobrazí v horní části stránky.</p></div></section><?php endif; ?>
<section class="card mb-4"><header class="card-header"><h2 class="card-title">Vydané odkazy</h2></header><div class="card-body"><div class="table-responsive"><table class="table"><thead><tr><th>Soubor</th><th>Platnost</th><th>Stažení</th><th>Stav</th><th>Akce</th></tr></thead><tbody>
<?php foreach($grants as $g): ?><tr><td><?= $e($g['original_filename']) ?></td><td><?= $e($g['expires_at']) ?></td><td><?= (int)$g['download_count'] ?></td><td><?= $g['revoked_at']===null?'Platný (do expirace)':'Zneplatněn' ?></td><td><?php if($g['revoked_at']===null): ?><form method="post" onsubmit="return confirm('Zneplatnit odkaz?');"><input type="hidden" name="csrf" value="<?= $e($csrf) ?>"><input type="hidden" name="order_id" value="<?= (int)$orderId ?>"><input type="hidden" name="action" value="revoke"><input type="hidden" name="access_id" value="<?= (int)$g['id'] ?>"><button class="btn btn-danger btn-sm">Zneplatnit</button></form><?php endif; ?></td></tr><?php endforeach; ?>
<?php if(!$grants): ?><tr><td colspan="5">Zatím nejsou vygenerované žádné odkazy.</td></tr><?php endif; ?></tbody></table></div></div></section>
<?php endif; ?>
</section><?php include __DIR__.'/inc/footer.class.php'; ?>
