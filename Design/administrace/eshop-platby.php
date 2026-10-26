<?php
// RAC E-shop payments – Porto / 2026-10-16-21.45 / PHP 7.4+ / pagination, titles, safe test hide
declare(strict_types=1);
require_once __DIR__.'/config/session.php';
require_once __DIR__.'/config/db.php';
require_once __DIR__.'/config/auth.php';
require_once __DIR__.'/config/auth_roles.php';
require_min_role('admin');
$viewer=current_user_row();
// RAC RBAC 5F (2026-10-20): cteni plateb vs. skryti/obnoveni testovacich plateb.
$racCanManagePayments = rac_can($pdo, $viewer, 'payments.manage');
$racCanViewOrders = rac_can($pdo, $viewer, 'orders.view');
$racCanManageBilling = rac_can($pdo, $viewer, 'billing.manage');
$e=static function($v):string{return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');};
$racUsePortoLayout=true;$base='/administrace';$assets=$base;
$viewerName=(string)($viewer['username']??'Administrátor');
$viewerRole=(string)($viewer['role']??'');$roleLabel=role_label($viewerRole);
$menuGroups=[
 ['Přehled',[['Dashboard','/administrace/dashboard.php','bx bx-home-alt']]],
 ['Administrace',[['Vložit administrátora','/administrace/sprava-admin.php','icons icon-user-follow'],['Zobrazit administrátory','/administrace/sprava-admin-zobrazit.php','icons icon-people'],['Jazyky','/administrace/jazyky.php','icons icon-globe']]],
 ['Obsah webu',[['Úvodní stránka','/administrace/obsah-uvod.php','icons icon-home'],['O autorovi','/administrace/obsah-autor.php','icons icon-note'],['Kontakt','/administrace/obsah-kontakt.php','icons icon-envelope']]],
 ['E-shop',[['Produkty','/administrace/eshop-produkty.php','icons icon-basket'],['Sklad knih','/administrace/eshop-sklad.php','icons icon-layers'],['Objednávky','/administrace/eshop-objednavky.php','icons icon-list'],['Platby e-shopu','/administrace/eshop-platby.php','el el-credit-card'],['Doprava','/administrace/eshop-doprava.php','icons icon-drawer']]],
 ['Platební brána',[['Nastavení Stripe','/administrace/billing/admin.php','el el-cogs'],['Platby','/administrace/billing/admin-platby.php','el el-credit-card'],['Webhooky','/administrace/billing/admin-webhooky.php','icons icon-shuffle'],['Diagnostika','/administrace/billing/admin-diagnostika.php','icons icon-wrench']]]
];
$currentPath='/administrace/eshop-platby.php';$pageTitle='Platby e-shopu';$title='Platby e-shopu | RAC';
function rac_payment_table_exists(PDO $pdo,string $name):bool{$s=$pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?');$s->execute([$name]);return (int)$s->fetchColumn()>0;}
function rac_payment_status_label(string $status):string {return ['paid'=>'Zaplaceno','pending'=>'Čeká na platbu','failed'=>'Neúspěšná','expired'=>'Vypršela','refunded'=>'Vráceno','cancelled'=>'Zrušena','canceled'=>'Zrušena'][$status]??$status;}
function rac_payment_amount($minor,$currency):string{return number_format(((int)$minor)/100,2,',',' ').' '.(string)$currency;}
$environment=(string)($_GET['environment']??'');if(!in_array($environment,['','test','live'],true))$environment='';
$status=(string)($_GET['status']??'');if(!in_array($status,['','pending','paid','failed','expired','refunded'],true))$status='';
$q=trim((string)($_GET['q']??''));if(mb_strlen($q)>100)$q=mb_substr($q,0,100);
$allowedPerPage=[12,24,48,96];
$perPage=(int)($_GET['per_page']??12);if(!in_array($perPage,$allowedPerPage,true))$perPage=12;
$page=max(1,(int)($_GET['page']??1));
$showHidden=(string)($_GET['show_hidden']??'')==='1';
$paymentCsrf=csrf_token('rac_payment_hide');
$flash='';$totalFiltered=0;$totalPages=1;$pageFrom=0;$pageTo=0;$bookTitles=[];$hiddenIds=[];
function rac_payment_page_url(int $p,int $perPage):string{
 $params=$_GET;unset($params['id']);$params['page']=$p;$params['per_page']=$perPage;
 return 'eshop-platby.php?'.http_build_query($params).'#payment-list';
}
$selectedId=filter_var($_GET['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:0;
$errors=[];$rows=[];$selected=null;$events=[];$totals=['all'=>0,'paid'=>0,'pending'=>0,'test'=>0];
try{
 if(!rac_payment_table_exists($pdo,'shop_payments')||!rac_payment_table_exists($pdo,'shop_orders'))throw new RuntimeException('Chybí tabulky shop_payments nebo shop_orders. Ověřte instalaci objednávkového systému a migraci 013_rac_stripe_checkout.sql.');
 $required=['id','order_id','environment','status','amount_minor','currency','provider','checkout_session_id','payment_intent_id','created_at'];
 $st=$pdo->prepare('SELECT column_name FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=?');$st->execute(['shop_payments']);$columns=$st->fetchAll(PDO::FETCH_COLUMN);
 foreach($required as $column){if(!in_array($column,$columns,true))throw new RuntimeException('V shop_payments chybí sloupec '.$column.'.');}
  // Pomocna tabulka skrytych TEST plateb. Nezasahuje do Stripe ani shop_payments.
  $pdo->exec('CREATE TABLE IF NOT EXISTS rac_hidden_test_payments (payment_id BIGINT UNSIGNED NOT NULL PRIMARY KEY, hidden_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
  if(($_SERVER['REQUEST_METHOD']??'GET')==='POST' && isset($_POST['payment_action'])){
    if (!$racCanManagePayments) { http_response_code(403); exit('Přístup odepřen: nemáte oprávnění spravovat platby.'); }
   if(!csrf_valid('rac_payment_hide',(string)($_POST['csrf']??'')))throw new RuntimeException('Neplatný bezpečnostní token.');
   $target=filter_var($_POST['payment_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
   if(!$target)throw new RuntimeException('Neplatné ID platby.');
   $action=(string)$_POST['payment_action'];
   $lookup=$pdo->prepare('SELECT environment FROM shop_payments WHERE id=? LIMIT 1');$lookup->execute([$target]);
   if($lookup->fetchColumn()!=='test')throw new RuntimeException('Skrývat lze pouze testovací platby.');
   if($action==='hide'){$up=$pdo->prepare('INSERT IGNORE INTO rac_hidden_test_payments (payment_id) VALUES (?)');$up->execute([$target]);}
   elseif($action==='restore'){$up=$pdo->prepare('DELETE FROM rac_hidden_test_payments WHERE payment_id=?');$up->execute([$target]);}
   else throw new RuntimeException('Neznámá operace.');
   $redirect=$_GET;unset($redirect['id']);$redirect['show_hidden']=$action==='hide'?'0':'1';
   header('Location: eshop-platby.php?'.http_build_query($redirect).'#payment-list',true,303);exit;
  }
 $stats=$pdo->query("SELECT COUNT(*) AS all_count, COALESCE(SUM(status='paid'),0) AS paid_count, COALESCE(SUM(status='pending'),0) AS pending_count, COALESCE(SUM(environment='test'),0) AS test_count FROM shop_payments")->fetch(PDO::FETCH_ASSOC);
 $totals=['all'=>(int)$stats['all_count'],'paid'=>(int)$stats['paid_count'],'pending'=>(int)$stats['pending_count'],'test'=>(int)$stats['test_count']];
 $conds=[];$params=[];
 if($environment!==''){$conds[]='p.environment=:environment';$params[':environment']=$environment;}
 if($status!==''){$conds[]='p.status=:status';$params[':status']=$status;}
 if($showHidden){$conds[]='p.environment=\'test\' AND h.payment_id IS NOT NULL';}else{$conds[]='h.payment_id IS NULL';}
  if($q!==''){$conds[]='(o.order_number LIKE :q1 OR o.email LIKE :q2 OR p.checkout_session_id LIKE :q3 OR p.payment_intent_id LIKE :q4)';foreach(['q1','q2','q3','q4'] as $k)$params[':'.$k]='%'.$q.'%';}
  $from=' FROM shop_payments p INNER JOIN shop_orders o ON o.id=p.order_id LEFT JOIN rac_hidden_test_payments h ON h.payment_id=p.id';
  $where=$conds?' WHERE '.implode(' AND ',$conds):'';
  $st=$pdo->prepare('SELECT COUNT(*)'.$from.$where);$st->execute($params);$totalFiltered=(int)$st->fetchColumn();
  $totalPages=max(1,(int)ceil($totalFiltered/$perPage));$page=min($page,$totalPages);
  $offset=($page-1)*$perPage;
  $pageFrom=$totalFiltered?$offset+1:0;$pageTo=min($offset+$perPage,$totalFiltered);
  $sql='SELECT p.id,p.order_id,p.provider,p.environment,p.status,p.amount_minor,p.currency,p.checkout_session_id,p.payment_intent_id,p.created_at,p.updated_at,o.order_number,o.email,o.status AS order_status'.$from.$where.' ORDER BY p.id DESC LIMIT '.(int)$perPage.' OFFSET '.(int)$offset;
  $st=$pdo->prepare($sql);$st->execute($params);$rows=$st->fetchAll(PDO::FETCH_ASSOC);
  // Tituly nacteme davkove a jen pro objednavky aktualni stranky; historicke varianty zustavaji pristupne.
  if($rows && rac_payment_table_exists($pdo,'shop_order_items') && rac_payment_table_exists($pdo,'product_variants') && rac_payment_table_exists($pdo,'product_translations')){
   $ids=array_values(array_unique(array_map('intval',array_column($rows,'order_id'))));
   $placeholders=implode(',',array_fill(0,count($ids),'?'));
   $itemsSql='SELECT i.order_id,v.kind,COALESCE(NULLIF(t.title,\'\'),pr.slug,\'Kniha\') book_title FROM shop_order_items i JOIN product_variants v ON v.id=i.variant_id JOIN products pr ON pr.id=v.product_id LEFT JOIN languages l ON l.is_default=1 LEFT JOIN product_translations t ON t.product_id=pr.id AND t.language_id=l.id WHERE i.order_id IN ('.$placeholders.') ORDER BY i.order_id,i.id';
   try{$it=$pdo->prepare($itemsSql);$it->execute($ids);foreach($it->fetchAll(PDO::FETCH_ASSOC) as $item){$key=(int)$item['order_id'];$type=['print'=>'Tištěná kniha','ebook'=>'E-kniha','audio'=>'Audiokniha'][(string)$item['kind']]??(string)$item['kind'];$label=(string)$item['book_title'].' ('.$type.')';$bookTitles[$key][$label]=$label;}}
   catch(Throwable $itemEx){error_log('RAC payment item labels: '.$itemEx->getMessage());}
  }
 if($selectedId){
  $st=$pdo->prepare('SELECT p.*,o.order_number,o.email,o.status AS order_status FROM shop_payments p INNER JOIN shop_orders o ON o.id=p.order_id WHERE p.id=? LIMIT 1');$st->execute([$selectedId]);$selected=$st->fetch(PDO::FETCH_ASSOC)?:null;
  if(!$selected)throw new RuntimeException('Požadovaná platba nebyla nalezena.');
  if(rac_payment_table_exists($pdo,'shop_payment_events')){$st=$pdo->prepare('SELECT stripe_event_id,event_type,environment,processed_at FROM shop_payment_events WHERE payment_id=? ORDER BY id DESC LIMIT 100');$st->execute([$selectedId]);$events=$st->fetchAll(PDO::FETCH_ASSOC);}
 }
}catch(Throwable $ex){error_log('RAC admin shop payments: '.$ex->getMessage());$errors[]=$ex instanceof RuntimeException?$ex->getMessage():'Platební data se nepodařilo načíst. Podrobnosti jsou v serverovém logu.';}
?>
<?php include __DIR__.'/inc/header.class.php';include __DIR__.'/inc/leve-meny.class.php'; ?>
<section role="main" class="content-body content-body-modern mt-0">
<header class="page-header"><h2>Platby e-shopu</h2></header>
<div class="row pt-2 mb-4"><div class="col-12 d-flex flex-wrap gap-2"><?php if ($racCanViewOrders): ?><a class="btn btn-primary btn-md font-weight-semibold btn-py-2 px-4" href="eshop-objednavky.php">Objednávky</a><?php endif; ?><?php if ($racCanManageBilling): ?><a class="btn btn-primary btn-md font-weight-semibold btn-py-2 px-4" href="billing/admin.php">Nastavení Stripe</a><?php endif; ?></div></div>
<?php foreach($errors as $err): ?><div class="alert alert-warning"><?= $e($err) ?></div><?php endforeach; ?>
<div class="row mb-4">
<?php foreach([['Plateb celkem',$totals['all'],'primary'],['Zaplacené',$totals['paid'],'success'],['Čekají na zaplacení',$totals['pending'],'warning'],['Testovací platby',$totals['test'],'info']] as $stat): ?>
<div class="col-lg-3 col-md-6 mb-3"><section class="card card-featured-left card-featured-<?= $e($stat[2]) ?>"><div class="card-body"><div class="widget-summary"><div class="widget-summary-col"><div class="summary"><h4 class="title"><?= $e($stat[0]) ?></h4><div class="info"><strong class="amount"><?= (int)$stat[1] ?></strong></div></div></div></div></div></section></div>
<?php endforeach; ?>
</div>
<?php if($selected): ?>
<section class="card mb-4" id="detail"><header class="card-header"><h2 class="card-title">Detail platby #<?= (int)$selected['id'] ?></h2></header><div class="card-body"><div class="row"><div class="col-md-6"><table class="table table-sm"><tbody>
<tr><th>Objednávka</th><td><?php foreach($bookTitles[(int)$selected['order_id']]??[] as $bookLabel): ?><div><?= $e($bookLabel) ?></div><?php endforeach; ?><?php if ($racCanViewOrders): ?><a href="eshop-objednavka.php?id=<?= (int)$selected['order_id'] ?>"><?= $e($selected['order_number']) ?></a><?php else: ?><?= $e($selected['order_number']) ?><?php endif; ?></td></tr>
<tr><th>Zákazník</th><td><?= $e($selected['email']) ?></td></tr>
<tr><th>Stav</th><td><?= $e(rac_payment_status_label((string)$selected['status'])) ?></td></tr>
<tr><th>Prostředí</th><td><span class="badge <?= $selected['environment']==='test'?'bg-warning text-dark':'bg-success' ?>"><?= $selected['environment']==='test'?'TEST':'LIVE' ?></span></td></tr>
<tr><th>Částka</th><td><?= $e(rac_payment_amount($selected['amount_minor'],$selected['currency'])) ?></td></tr>
<tr><th>Poskytovatel</th><td><?= $e($selected['provider']) ?></td></tr>
</tbody></table></div><div class="col-md-6"><table class="table table-sm"><tbody>
<tr><th>Checkout Session ID</th><td class="text-break"><?= $e($selected['checkout_session_id']??'—') ?></td></tr>
<tr><th>Payment Intent ID</th><td class="text-break"><?= $e($selected['payment_intent_id']??'—') ?></td></tr>
<tr><th>Vytvořeno</th><td><?= $e($selected['created_at']) ?></td></tr>
<tr><th>Aktualizováno</th><td><?= $e($selected['updated_at']??'—') ?></td></tr>
</tbody></table></div></div>
<h3 class="mt-3">Události Stripe</h3><?php if(!$events): ?><p class="text-muted">K této platbě nejsou evidované webhookové události.</p><?php else: ?><div class="table-responsive"><table class="table table-striped"><thead><tr><th>Událost</th><th>Event ID</th><th>Režim</th><th>Zpracováno</th></tr></thead><tbody><?php foreach($events as $ev): ?><tr><td><?= $e($ev['event_type']) ?></td><td class="text-break"><?= $e($ev['stripe_event_id']) ?></td><td><?= $e($ev['environment']) ?></td><td><?= $e($ev['processed_at']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
<a href="eshop-platby.php" class="btn btn-light mt-2">Zavřít detail</a></div></section>
<?php endif; ?>
<section class="card mb-4" id="payment-list"><header class="card-header"><h2 class="card-title">Přehled plateb</h2></header><div class="card-body">
<form method="get" class="row g-3 mb-4"><div class="col-lg-4 col-md-6"><label class="form-label">Vyhledávání</label><input class="form-control" type="search" name="q" value="<?= $e($q) ?>" placeholder="Objednávka, e-mail, Stripe ID"></div><div class="col-lg-2 col-md-3"><label class="form-label">Prostředí</label><select class="form-control" name="environment"><option value="">Všechny</option><option value="test"<?= $environment==='test'?' selected':'' ?>>Testovací</option><option value="live"<?= $environment==='live'?' selected':'' ?>>Produkční</option></select></div><div class="col-lg-2 col-md-3"><label class="form-label">Stav</label><select class="form-control" name="status"><option value="">Všechny</option><?php foreach(['pending','paid','failed','expired','refunded'] as $st): ?><option value="<?= $e($st) ?>"<?= $status===$st?' selected':'' ?>><?= $e(rac_payment_status_label($st)) ?></option><?php endforeach; ?></select></div><div class="col-lg-2 col-md-3"><label class="form-label">Počet záznamů</label><select class="form-control" name="per_page"><?php foreach($allowedPerPage as $count): ?><option value="<?= $count ?>"<?= $perPage===$count?' selected':'' ?>><?= $count ?></option><?php endforeach; ?></select></div><div class="col-lg-2 col-md-12 d-flex align-items-end gap-2"><button class="btn btn-primary" type="submit">Filtrovat</button><a class="btn btn-light" href="eshop-platby.php">Reset</a></div><div class="col-12"><label class="form-check"><input class="form-check-input" type="checkbox" name="show_hidden" value="1"<?= $showHidden?' checked':'' ?>> <span class="form-check-label">Zobrazit skryté testovací platby</span></label></div></form>
<?php if(!$rows): ?><div class="alert alert-info mb-0">Žádné platby neodpovídají zvolenému filtru.</div><?php else: ?><div class="table-responsive"><table class="table table-striped align-middle"><thead><tr><th>Objednávka</th><th>Zákazník</th><th>Částka</th><th>Stav</th><th>Prostředí</th><th>Datum</th><th></th></tr></thead><tbody>
<?php foreach($rows as $p): ?><tr><td><?php foreach($bookTitles[(int)$p['order_id']]??[] as $bookLabel): ?><div><strong><?= $e($bookLabel) ?></strong></div><?php endforeach; ?><div class="small text-muted"><?= $e($p['order_number']) ?></div></td><td class="text-break"><?= $e($p['email']) ?></td><td class="text-nowrap"><?= $e(rac_payment_amount($p['amount_minor'],$p['currency'])) ?></td><td><?= $e(rac_payment_status_label((string)$p['status'])) ?></td><td><span class="badge <?= $p['environment']==='test'?'bg-warning text-dark':'bg-success' ?>"><?= $p['environment']==='test'?'TEST':'LIVE' ?></span></td><td><?= $e($p['created_at']) ?></td><td><div class="d-flex flex-wrap gap-1"><a class="btn btn-default btn-sm" title="Detail platby" aria-label="Detail platby" href="eshop-platby.php?<?= $e(http_build_query(array_merge($_GET,['id'=>(int)$p['id']]))) ?>#detail"><i class="fas fa-search"></i></a><?php if($racCanManagePayments && $p['environment']==='test'): ?><form method="post" action="eshop-platby.php?<?= $e(http_build_query($_GET)) ?>" class="d-inline" onsubmit="return confirm('<?= $showHidden?'Obnovit platbu v přehledu?':'Skrýt testovací platbu z přehledu? Originální platba a Stripe data zůstanou zachovány.' ?>');"><input type="hidden" name="csrf" value="<?= $e($paymentCsrf) ?>"><input type="hidden" name="payment_id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="payment_action" value="<?= $showHidden?'restore':'hide' ?>"><button class="btn btn-default btn-sm" type="submit" title="<?= $showHidden?'Obnovit':'Skrýt z přehledu' ?>" aria-label="<?= $showHidden?'Obnovit':'Skrýt z přehledu' ?>"><i class="fas <?= $showHidden?'fa-undo text-success':'fa-trash-alt text-danger' ?>"></i></button></form><?php endif; ?></div></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?>
<div class="row row-gutter-sm justify-content-between mt-3 align-items-center"><div class="col-lg-auto order-2 order-lg-1"><p class="text-center text-lg-left mb-0">Zobrazeno <?= (int)$pageFrom ?>–<?= (int)$pageTo ?> z <?= (int)$totalFiltered ?> celkem</p></div><div class="col-lg-auto order-1 order-lg-2 mb-3 mb-lg-0"><nav aria-label="Stránkování plateb"><ul class="pagination pagination-modern pagination-modern-spacing justify-content-center justify-content-lg-start mb-0"><li class="page-item <?= $page<=1?'disabled':'' ?>"><a class="page-link prev" href="<?= $page<=1?'#payment-list':$e(rac_payment_page_url($page-1,$perPage)) ?>" aria-label="Předchozí"><span><i class="fas fa-chevron-left"></i></span></a></li><?php $pages=[1];for($pi=$page-1;$pi<=$page+1;$pi++)if($pi>1&&$pi<$totalPages)$pages[]=$pi;if($totalPages>1)$pages[]=$totalPages;$pages=array_values(array_unique($pages));sort($pages);$prev=0;foreach($pages as $pi): ?><?php if($prev>0&&$pi>$prev+1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?><li class="page-item <?= $pi===$page?'active':'' ?>"><a class="page-link" href="<?= $e(rac_payment_page_url($pi,$perPage)) ?>"><?= $pi ?></a></li><?php $prev=$pi;endforeach; ?><li class="page-item <?= $page>=$totalPages?'disabled':'' ?>"><a class="page-link next" href="<?= $page>=$totalPages?'#payment-list':$e(rac_payment_page_url($page+1,$perPage)) ?>" aria-label="Další"><span><i class="fas fa-chevron-right"></i></span></a></li></ul></nav></div></div><p class="text-muted small mt-3 mb-0">Platby spravuje ověřený Stripe webhook. Skrytí testovací platby upravuje pouze tento administrativní přehled.</p></div></section>
</section>
<?php include __DIR__.'/inc/footer.class.php'; ?>
