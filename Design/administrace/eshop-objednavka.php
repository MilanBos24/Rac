<?php
// RAC administrace objednávek, PHP 7.4+, verze 2026-10-20-5D
// Read-only: stav platby upravuje pouze ověřený Stripe webhook.
declare(strict_types=1);
require_once __DIR__.'/config/session.php';
require_once __DIR__.'/config/db.php';
require_once __DIR__.'/config/auth.php';
require_once __DIR__.'/config/auth_roles.php';
require_min_role('admin');
$viewer=current_user_row();
$racCanFulfill = rac_can($pdo, $viewer, 'orders.fulfill');
$racCanDeleteTest = rac_can($pdo, $viewer, 'orders.delete_test');
$e=static function($v):string{return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');};
function rac_orders_table(PDO $pdo,string $name):bool {
    $s=$pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?');
    $s->execute([$name]);return (int)$s->fetchColumn()>0;
}
function rac_orders_cols(PDO $pdo,string $name):array {
    $st=$pdo->prepare('SELECT column_name FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? ORDER BY ordinal_position');
    $st->execute([$name]);return $st->fetchAll(PDO::FETCH_COLUMN);
}
function rac_orders_label(string $s):string {return ['awaiting_payment'=>'Čeká na platbu','paid'=>'Zaplaceno','cancelled'=>'Zrušeno','canceled'=>'Zrušeno','pending'=>'Čeká','failed'=>'Neúspěšná','expired'=>'Vypršela','refunded'=>'Vráceno','fulfilled'=>'Vyřízeno','processing'=>'Zpracovává se','shipped'=>'Odesláno'][$s]??$s;}
function rac_orders_money($n,$c):string{return number_format((float)$n,2,',',' ').' '.(string)$c;}
$racUsePortoLayout=true;
$base='/administrace';$assets=$base;
$viewerName=(string)($viewer['username']??'Administrátor');
$menuGroups=[
 ['Přehled',[['Dashboard','/administrace/dashboard.php','bx bx-home-alt']]],
 ['Administrace',[['Vložit administrátora','/administrace/sprava-admin.php','icons icon-user-follow'],['Zobrazit administrátory','/administrace/sprava-admin-zobrazit.php','icons icon-people'],['Jazyky','/administrace/jazyky.php','icons icon-globe']]],
 ['Obsah webu',[['Úvodní stránka','/administrace/obsah-uvod.php','icons icon-home'],['O autorovi','/administrace/obsah-autor.php','icons icon-note'],['Kontakt','/administrace/obsah-kontakt.php','icons icon-envelope']]],
 ['E-shop',[['Produkty','/administrace/eshop-produkty.php','icons icon-basket'],['Sklad knih','/administrace/eshop-sklad.php','icons icon-layers'],['Objednávky','/administrace/eshop-objednavky.php','icons icon-list'],['Doprava','/administrace/eshop-doprava.php','icons icon-drawer']]],
 ['Platební brána',[['Nastavení Stripe','/administrace/billing/admin.php','el el-cogs'],['Platby','/administrace/billing/admin-platby.php','el el-credit-card'],['Webhooky','/administrace/billing/admin-webhooky.php','icons icon-shuffle'],['Diagnostika','/administrace/billing/admin-diagnostika.php','icons icon-wrench']]]
];
$currentPath='/administrace/eshop-objednavky.php';
$viewerRole=(string)($viewer['role']??'');$roleLabel=role_label($viewerRole);
$pageTitle='Detail objednávky';$title='Detail objednávky | RAC';
$id=filter_var($_GET['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
$error=null;$order=null;$lines=[];$paymentRows=[];$itemTable='';$isTestOrder=false;
$deleteCsrf=csrf_token('csrf_eshop_order_delete');
try {
 if(!$id)throw new RuntimeException('Neplatné ID objednávky.');
 if(!rac_orders_table($pdo,'shop_orders'))throw new RuntimeException('Tabulka shop_orders neexistuje.');
 $st=$pdo->prepare('SELECT * FROM shop_orders WHERE id=? LIMIT 1');$st->execute([$id]);$order=$st->fetch(PDO::FETCH_ASSOC);
 if(!$order)throw new RuntimeException('Objednávka nebyla nalezena.');
 foreach(['shop_order_items','shop_order_lines'] as $candidate){
  if(!rac_orders_table($pdo,$candidate))continue;
  $c=rac_orders_cols($pdo,$candidate);if(!in_array('order_id',$c,true))continue;
  $itemTable=$candidate;$st=$pdo->prepare('SELECT * FROM `'.$candidate.'` WHERE order_id=? ORDER BY id ASC LIMIT 300');$st->execute([$id]);$lines=$st->fetchAll(PDO::FETCH_ASSOC);break;
 }
 if(rac_orders_table($pdo,'shop_payments')){
  $c=rac_orders_cols($pdo,'shop_payments');if(in_array('order_id',$c,true)){
   $st=$pdo->prepare('SELECT * FROM shop_payments WHERE order_id=? ORDER BY id DESC LIMIT 20');$st->execute([$id]);$paymentRows=$st->fetchAll(PDO::FETCH_ASSOC);
   $isTestOrder=count($paymentRows)>0 && count(array_filter($paymentRows,static function($p){return ($p['environment']??'')!=='test';}))===0;
  }
 }
} catch(Throwable $ex){error_log('RAC order detail: '.$ex->getMessage());$error=$ex instanceof RuntimeException?$ex->getMessage():'Detail nelze načíst.';}
// Samostatna evidence vyrizeni objednavky. Platebni stavy a sklad zustavaji nedotcene.
$fulfillmentReady = rac_orders_table($pdo,'shop_order_fulfillment');
$fulfillment = null;
$fulfillmentOk = (string)($_SESSION['rac_fulfillment_ok'] ?? '');
unset($_SESSION['rac_fulfillment_ok']);
$fulfillmentCsrf = csrf_token('csrf_rac_fulfillment');
$fulfillmentStates = ['new'=>'Nová', 'processing'=>'Připravuje se', 'shipped'=>'Odeslána', 'completed'=>'Dokončena', 'cancelled'=>'Zrušena'];
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST' && ($_POST['action']??'')==='save_fulfillment'){
 if(!$racCanFulfill){http_response_code(403);exit('Přístup odepřen: chybí oprávnění orders.fulfill.');}
 if(!$fulfillmentReady){$error='Nejprve spusťte SQL migraci 014_eshop_order_fulfillment.sql.';}
 elseif(!$id || !$order){$error='Objednávka nebyla nalezena.';}
 elseif(!csrf_valid('csrf_rac_fulfillment',(string)($_POST['csrf']??''))){$error='Neplatný bezpečnostní token.';}
 elseif((int)($_POST['order_id']??0)!==(int)$id){$error='Neplatné ID objednávky.';}
 else{
  $state=(string)($_POST['fulfillment_status']??'new');
  $carrier=trim((string)($_POST['carrier']??''));
  $tracking=trim((string)($_POST['tracking_number']??''));
  $note=trim((string)($_POST['admin_note']??''));
  if(!isset($fulfillmentStates[$state]))$error='Neplatný stav vyřízení.';
  elseif(mb_strlen($carrier)>120 || mb_strlen($tracking)>190 || mb_strlen($note)>2000)$error='Některý údaj přesahuje povolenou délku.';
  else{
   try{
    $pdo->beginTransaction();
    $check=$pdo->prepare('SELECT id FROM shop_orders WHERE id=? FOR UPDATE');$check->execute([$id]);
    if(!$check->fetchColumn())throw new RuntimeException('Objednávka už neexistuje.');
    $stmt=$pdo->prepare("INSERT INTO shop_order_fulfillment (order_id,fulfillment_status,carrier,tracking_number,admin_note,updated_by,shipped_at,completed_at) VALUES (?,?,?,?,?,?,IF(?='shipped',NOW(),NULL),IF(?='completed',NOW(),NULL)) ON DUPLICATE KEY UPDATE fulfillment_status=VALUES(fulfillment_status),carrier=VALUES(carrier),tracking_number=VALUES(tracking_number),admin_note=VALUES(admin_note),updated_by=VALUES(updated_by),shipped_at=IF(VALUES(fulfillment_status)='shipped',COALESCE(shipped_at,NOW()),shipped_at),completed_at=IF(VALUES(fulfillment_status)='completed',COALESCE(completed_at,NOW()),completed_at)");
    $stmt->execute([$id,$state,$carrier?:null,$tracking?:null,$note?:null,(int)($viewer['id']??0)?:null,$state,$state]);
    $pdo->commit();
    $_SESSION['rac_fulfillment_ok']='Stav vyřízení objednávky byl uložen.';
    header('Location: eshop-objednavka.php?id='.$id,true,303);exit;
   }catch(Throwable $ex){if($pdo->inTransaction())$pdo->rollBack();error_log('RAC fulfillment save: '.$ex->getMessage());$error='Vyřízení se nepodařilo uložit.';}
  }
 }
}
if($fulfillmentReady && $id && $order){
 try{$st=$pdo->prepare('SELECT * FROM shop_order_fulfillment WHERE order_id=? LIMIT 1');$st->execute([$id]);$fulfillment=$st->fetch(PDO::FETCH_ASSOC)?:null;}
 catch(Throwable $ex){error_log('RAC fulfillment load: '.$ex->getMessage());$error='Záznam vyřízení nelze načíst.';}
}
// Trvalé odstranění pouze testovacích objednávek – žádná změna stavu platby ani skladových zásob.
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST' && ($_POST['action']??'')==='delete_test_order'){
 if(!$racCanDeleteTest){http_response_code(403);exit('Přístup odepřen: chybí oprávnění orders.delete_test.');}
 if(!csrf_valid('csrf_eshop_order_delete',(string)($_POST['csrf']??''))){$error='Neplatný bezpečnostní token.';}
 elseif(!$id||!$isTestOrder){$error='Smazat lze pouze objednávku s výhradně testovacími platbami Stripe.';}
 elseif((int)($_POST['order_id']??0)!==(int)$id){$error='Neplatná objednávka.';}
 else{
  try{
   $pdo->beginTransaction();
   $lock=$pdo->prepare('SELECT id FROM shop_orders WHERE id=? FOR UPDATE');$lock->execute([$id]);
   if(!$lock->fetchColumn())throw new RuntimeException('Objednávka již neexistuje.');
   $lock=$pdo->prepare('SELECT id,environment FROM shop_payments WHERE order_id=? FOR UPDATE');$lock->execute([$id]);
   $lockedPayments=$lock->fetchAll(PDO::FETCH_ASSOC);
   if(!$lockedPayments)throw new RuntimeException('Objednávka nemá testovací platbu.');
   foreach($lockedPayments as $payment){if(($payment['environment']??'')!=='test')throw new RuntimeException('Objednávka má produkční platbu – smazání zamítnuto.');}
   // Bezpečnost: při dalších připojených tabulkách neprovádět částečné/nekontrolované mazání.
   $fk=$pdo->prepare("SELECT table_name,column_name,referenced_table_name FROM information_schema.key_column_usage WHERE referenced_table_schema=DATABASE() AND referenced_table_name IN ('shop_orders','shop_payments') AND table_schema=DATABASE() AND referenced_table_name IS NOT NULL");
   $fk->execute();
   foreach($fk->fetchAll(PDO::FETCH_ASSOC) as $ref){
    $table=(string)$ref['table_name'];$col=(string)$ref['column_name'];$parent=(string)$ref['referenced_table_name'];
    $allowed=($parent==='shop_orders' && $col==='order_id' && in_array($table,['shop_payments','shop_order_items','shop_order_lines'],true))
     ||($parent==='shop_payments' && $table==='shop_payment_events' && $col==='payment_id')
     ||($parent==='shop_orders' && $table==='shop_order_fulfillment' && $col==='order_id');
    if(!$allowed)throw new RuntimeException('Objednávka má další návaznosti ('.$table.'); kvůli bezpečnosti ji nelze automaticky smazat.');
   }
   if(rac_orders_table($pdo,'shop_payment_events')){
    $st=$pdo->prepare('DELETE e FROM shop_payment_events e INNER JOIN shop_payments p ON p.id=e.payment_id WHERE p.order_id=?');$st->execute([$id]);
   }
   $st=$pdo->prepare('DELETE FROM shop_payments WHERE order_id=?');$st->execute([$id]);
   foreach(['shop_order_items','shop_order_lines'] as $table){
    if(!rac_orders_table($pdo,$table))continue;
    if(!in_array('order_id',rac_orders_cols($pdo,$table),true))continue;
    $st=$pdo->prepare('DELETE FROM `'.$table.'` WHERE order_id=?');$st->execute([$id]);
   }
   $st=$pdo->prepare('DELETE FROM shop_orders WHERE id=?');$st->execute([$id]);
   $pdo->commit();
   $_SESSION['rac_orders_deleted']='Testovací objednávka byla trvale odstraněna.';
   header('Location: eshop-objednavky.php',true,303);exit;
  }catch(Throwable $ex){if($pdo->inTransaction())$pdo->rollBack();error_log('RAC delete test order: '.$ex->getMessage());$error='Objednávku se nepodařilo bezpečně smazat: '.$ex->getMessage();}
 }
}
// Jen bezpečné zákaznické a obchodní údaje; žádné API klíče ani interní tokeny.
$fieldNames=['order_number'=>'Číslo objednávky','status'=>'Stav','email'=>'E-mail','first_name'=>'Jméno','last_name'=>'Příjmení','phone'=>'Telefon','billing_name'=>'Fakturační jméno','billing_company'=>'Firma','billing_street'=>'Fakturační ulice','billing_address'=>'Fakturační adresa','billing_city'=>'Fakturační město','billing_zip'=>'Fakturační PSČ','billing_postal_code'=>'Fakturační PSČ','billing_country'=>'Fakturační země','shipping_name'=>'Příjemce','shipping_street'=>'Doručovací ulice','shipping_address'=>'Doručovací adresa','shipping_city'=>'Doručovací město','shipping_zip'=>'Doručovací PSČ','shipping_postal_code'=>'Doručovací PSČ','shipping_country'=>'Doručovací země','shipping_method'=>'Doprava','shipping_method_name'=>'Způsob dopravy','subtotal'=>'Mezisoučet','shipping_total'=>'Doprava celkem','total'=>'Celkem','currency'=>'Měna','note'=>'Poznámka','customer_note'=>'Poznámka zákazníka','created_at'=>'Vytvořeno','created'=>'Vytvořeno'];
?>
<?php include __DIR__.'/inc/header.class.php'; include __DIR__.'/inc/leve-meny.class.php'; ?>
<section role="main" class="content-body content-body-modern mt-0">
<header class="page-header"><h2><?= $e($pageTitle) ?></h2></header>

<div class="row pt-2 mb-4"><div class="col-12"><a class="btn btn-primary btn-md font-weight-semibold btn-py-2 px-4" href="eshop-objednavky.php">← Přehled objednávek</a></div></div>
<?php if($error): ?><div class="alert alert-warning"><?= $e($error) ?></div><?php endif; ?>
<?php if($fulfillmentOk!==''): ?><div class="alert alert-success"><?= $e($fulfillmentOk) ?></div><?php endif; ?>
<?php if($order): ?>
<section class="card mb-4"><header class="card-header"><h2 class="card-title">Vyřízení a expedice</h2></header><div class="card-body">
<?php if(!$fulfillmentReady): ?><div class="alert alert-warning">Evidence vyřízení zatím není nainstalována. Spusťte SQL migraci <code>014_eshop_order_fulfillment.sql</code>.</div><?php elseif($racCanFulfill): ?>
<form method="post" autocomplete="off"><input type="hidden" name="action" value="save_fulfillment"><input type="hidden" name="csrf" value="<?= $e($fulfillmentCsrf) ?>"><input type="hidden" name="order_id" value="<?= (int)$id ?>">
<div class="row form-group pb-3"><div class="col-lg-4 mb-3"><label class="form-label" for="fulfillment_status">Stav vyřízení</label><select class="form-control" name="fulfillment_status" id="fulfillment_status"><?php foreach($fulfillmentStates as $key=>$label): ?><option value="<?= $e($key) ?>" <?= ($fulfillment['fulfillment_status']??'new')===$key?'selected':'' ?>><?= $e($label) ?></option><?php endforeach; ?></select></div>
<div class="col-lg-4 mb-3"><label class="form-label" for="carrier">Dopravce</label><input id="carrier" name="carrier" maxlength="120" class="form-control" value="<?= $e($fulfillment['carrier']??'') ?>" placeholder="Např. Zásilkovna"></div>
<div class="col-lg-4 mb-3"><label class="form-label" for="tracking_number">Sledovací číslo</label><input id="tracking_number" name="tracking_number" maxlength="190" class="form-control" value="<?= $e($fulfillment['tracking_number']??'') ?>"></div></div>
<div class="row"><div class="col-12 mb-3"><label class="form-label" for="admin_note">Interní poznámka</label><textarea id="admin_note" name="admin_note" class="form-control" maxlength="2000" rows="3"><?= $e($fulfillment['admin_note']??'') ?></textarea></div></div>
<div class="text-end"><button type="submit" class="btn btn-primary">Uložit vyřízení</button></div>
</form><p class="text-muted small mt-3 mb-0">Změna vyřízení neoznačuje platbu jako zaplacenou, neodesílá e-maily ani nemění skladové zásoby. Tyto procesy napojíme po otestování objednávek.</p>
<?php else: ?>
<div class="row g-3"><div class="col-md-4"><strong>Stav vyřízení:</strong> <?= $e($fulfillmentStates[$fulfillment['fulfillment_status']??'new']??'Nová') ?></div><div class="col-md-4"><strong>Dopravce:</strong> <?= $e($fulfillment['carrier']??'—') ?></div><div class="col-md-4"><strong>Sledovací číslo:</strong> <?= $e($fulfillment['tracking_number']??'—') ?></div></div>
<?php if(!empty($fulfillment['admin_note'])): ?><div class="mt-3"><strong>Interní poznámka:</strong> <?= nl2br($e($fulfillment['admin_note'])) ?></div><?php endif; ?>
<?php endif; ?></div></section>

<?php if($isTestOrder && $racCanDeleteTest): ?>
<section class="card mb-4" id="smazat-test"><header class="card-header"><h2 class="card-title">Testovací objednávka</h2></header><div class="card-body"><p class="text-muted">Tuto objednávku lze trvale odstranit včetně jejích testovacích platebních záznamů a položek. Záznamy ve Stripe zůstanou zachované. Skladové zásoby se touto operací nepřepočítají.</p>
<form method="post" onsubmit="return confirm('Opravdu trvale smazat testovací objednávku a její platební záznamy? Tato operace je nevratná.');"><input type="hidden" name="action" value="delete_test_order"><input type="hidden" name="order_id" value="<?= (int)$id ?>"><input type="hidden" name="csrf" value="<?= $e($deleteCsrf) ?>"><button class="btn btn-danger" type="submit">Smazat testovací objednávku</button></form></div></section>
<?php endif; ?>
<div class="row mb-4"><div class="col-lg-6 mb-3"><section class="card card-featured-left card-featured-primary"><div class="card-body"><div class="widget-summary"><div class="widget-summary-col"><div class="summary"><h4 class="title">Objednávka</h4><div class="info"><strong class="amount"><?= $e($order['order_number']??('#'.$id)) ?></strong><?php if($isTestOrder): ?><div class="small text-warning fw-semibold">Testovací platba</div><?php endif; ?></div></div></div></div></div></section></div><div class="col-lg-6 mb-3"><section class="card card-featured-left card-featured-success"><div class="card-body"><div class="widget-summary"><div class="widget-summary-col"><div class="summary"><h4 class="title">Částka</h4><div class="info"><strong class="amount"><?= $e(rac_orders_money($order['total']??0,$order['currency']??'CZK')) ?></strong></div></div></div></div></div></section></div></div>
<div class="row"><div class="col-xl-7 mb-4"><section class="card"><header class="card-header"><h2 class="card-title">Údaje objednávky</h2></header><div class="card-body"><div class="table-responsive"><table class="table"><tbody>
<?php foreach($fieldNames as $key=>$label): ?><?php if(array_key_exists($key,$order) && $order[$key]!==null && $order[$key]!==''): ?><tr><th style="width:38%"><?= $e($label) ?></th><td><?= $e($key==='status'?rac_orders_label((string)$order[$key]):(string)$order[$key]) ?></td></tr><?php endif; ?><?php endforeach; ?>
</tbody></table></div></div></section></div>
<div class="col-xl-5 mb-4"><section class="card"><header class="card-header"><h2 class="card-title">Platební záznamy</h2></header><div class="card-body">
<?php if(!$paymentRows): ?><div class="alert alert-info mb-0">Zatím bez platebních záznamů.</div><?php endif; ?>
<?php foreach($paymentRows as $pay): ?><div class="border-bottom pb-3 mb-3"><strong><?= $e(rac_orders_label((string)($pay['status']??'pending'))) ?></strong><div class="text-muted small">Poskytovatel: <?= $e($pay['provider']??'stripe') ?> | Režim: <?= $e($pay['environment']??'—') ?></div><?php if(isset($pay['amount_minor'])): ?><div><?= $e(rac_orders_money((int)$pay['amount_minor']/100,$pay['currency']??'CZK')) ?></div><?php endif; ?><?php if(!empty($pay['created_at'])): ?><div class="small text-muted"><?= $e($pay['created_at']) ?></div><?php endif; ?></div><?php endforeach; ?>
<p class="text-muted small mb-0">Tato stránka neoznačuje platby jako zaplacené. Potvrzení přichází přes Stripe webhook.</p>
</div></section></div></div>
<section class="card mb-4"><header class="card-header"><h2 class="card-title">Položky objednávky</h2></header><div class="card-body">
<?php if(!$itemTable): ?><div class="alert alert-warning">Tabulka položek objednávky nebyla rozpoznána. Je nutné ověřit schéma checkoutu.</div><?php elseif(!$lines): ?><div class="alert alert-info">Objednávka nemá uložené položky.</div><?php else: ?><div class="table-responsive"><table class="table table-striped"><thead><tr><th>Produkt</th><th>Varianta</th><th>Množství</th><th>Cena</th><th>Celkem</th></tr></thead><tbody><?php foreach($lines as $line): ?>
<?php $name=$line['product_name']??$line['title']??$line['name']??$line['product_title']??('Produkt #'.($line['product_id']??'—'));$kind=$line['variant_kind']??$line['kind']??$line['variant_id']??'—';$qty=$line['quantity']??$line['qty']??'—';$unit=$line['unit_price']??$line['price']??null;$sum=$line['line_total']??$line['total']??$line['subtotal']??null; ?>
<tr><td><?= $e($name) ?></td><td><?= $e($kind) ?></td><td><?= $e($qty) ?></td><td><?= $unit===null?'—':$e(rac_orders_money($unit,$order['currency']??'CZK')) ?></td><td><?= $sum===null?'—':$e(rac_orders_money($sum,$order['currency']??'CZK')) ?></td></tr>
<?php endforeach; ?></tbody></table></div><?php endif; ?>
</div></section>
<?php endif; ?>
</section>
<?php include __DIR__.'/inc/footer.class.php'; ?>
