<?php
// RAC administrace objednávek, PHP 7.4+, verze 2026-10-20-10.30 | RBAC 5D
// Read-only: stav platby upravuje pouze ověřený Stripe webhook.
declare(strict_types=1);
require_once __DIR__.'/config/session.php';
require_once __DIR__.'/config/db.php';
require_once __DIR__.'/config/auth.php';
require_once __DIR__.'/config/auth_roles.php';
require_min_role('admin');
$viewer=current_user_row();
// Faze 5D: prava k navigacnim tlacitkum a testovacimu mazani.
$racCanProducts = rac_can($pdo, $viewer, 'products.view');
$racCanStock = rac_can($pdo, $viewer, 'stock.view');
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
$pageTitle='Objednávky';$title='Objednávky | RAC';
$q=trim((string)($_GET['q']??''));$status=trim((string)($_GET['status']??''));
$perPage=(int)($_GET['per_page']??12);
if(!in_array($perPage,[12,24,48,96],true))$perPage=12;
$page=max(1,(int)($_GET['page']??1));
$totalFiltered=0;$pageCount=1;$firstShown=0;$lastShown=0;
$orderBooks=[];
$ordersPageUrl=static function(int $requestedPage) use (&$q,&$status,$perPage):string {
    return 'eshop-objednavky.php?'.http_build_query(['q'=>$q,'status'=>$status,'per_page'=>$perPage,'page'=>$requestedPage], '', '&', PHP_QUERY_RFC3986);
};
if(mb_strlen($q)>120)$q=mb_substr($q,0,120);
$deletedMessage=(string)($_SESSION['rac_orders_deleted']??'');unset($_SESSION['rac_orders_deleted']);
$error=null;$orders=[];$testOrderIds=[];$summary=['total'=>0,'unpaid'=>0,'paid'=>0];$statuses=[];$fulfillmentByOrder=[];
try {
 if(!rac_orders_table($pdo,'shop_orders')) throw new RuntimeException('Databázová tabulka shop_orders zatím neexistuje. Nejprve je nutné nainstalovat objednávkovou část e-shopu.');
 $cols=rac_orders_cols($pdo,'shop_orders');
 foreach(['id','order_number','status','email','total','currency'] as $need) if(!in_array($need,$cols,true))throw new RuntimeException('Tabulka shop_orders nemá očekávaný sloupec '.$need.'.');
 $stats=$pdo->query("SELECT status, COUNT(*) n FROM shop_orders GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
 foreach($stats as $st){$statuses[]=(string)$st['status'];$summary['total']+=(int)$st['n'];if($st['status']==='paid')$summary['paid']+=(int)$st['n'];if($st['status']==='awaiting_payment')$summary['unpaid']+=(int)$st['n'];}
 $where=[];$args=[];
 if($status!=='' && in_array($status,$statuses,true)){$where[]='o.status=:status';$args[':status']=$status;}else{$status='';}
 if($q!==''){$where[]='(o.order_number LIKE :q1 OR o.email LIKE :q2)';$args[':q1']='%'.$q.'%';$args[':q2']='%'.$q.'%';}
  $condition=$where?' WHERE '.implode(' AND ',$where):'';
  $countStmt=$pdo->prepare('SELECT COUNT(*) FROM shop_orders o'.$condition);
  $countStmt->execute($args);
  $totalFiltered=(int)$countStmt->fetchColumn();
  $pageCount=max(1,(int)ceil($totalFiltered/$perPage));
  $page=min($page,$pageCount);
  $offset=($page-1)*$perPage;
  $sql='SELECT o.* FROM shop_orders o'.$condition.' ORDER BY o.id DESC LIMIT '.$perPage.' OFFSET '.$offset;
  $stmt=$pdo->prepare($sql);$stmt->execute($args);$orders=$stmt->fetchAll(PDO::FETCH_ASSOC);
  $firstShown=$totalFiltered>0?$offset+1:0;
  $lastShown=$totalFiltered>0?$offset+count($orders):0;
  // Jediný hromadný dotaz pro všechny knihy a varianty na aktuální stránce.
  // Nejprve použijeme historický název uložený v položce objednávky,
  // pokud existuje; u starších dat doplníme aktuální překlad produktu.
  if($orders && rac_orders_table($pdo,'shop_order_items')) {
      $itemCols=rac_orders_cols($pdo,'shop_order_items');
      if(in_array('order_id',$itemCols,true)) {
          $orderIds=array_map(static function(array $row):int{return (int)$row['id'];},$orders);
          $placeholders=implode(',',array_fill(0,count($orderIds),'?'));
          $hasVariantId=in_array('variant_id',$itemCols,true) && rac_orders_table($pdo,'product_variants');
          $hasProductId=in_array('product_id',$itemCols,true);
          $canTranslate=rac_orders_table($pdo,'product_translations') && rac_orders_table($pdo,'languages');
          $select='SELECT oi.*';
          $join='';
          if($hasVariantId) {
              $select.=', pv.kind AS rac_variant_kind, pv.product_id AS rac_variant_product_id';
              $join.=' LEFT JOIN product_variants pv ON pv.id=oi.variant_id';
          }
          $productExpr=$hasProductId?'oi.product_id':($hasVariantId?'pv.product_id':null);
          if($canTranslate && $productExpr!==null) {
              $select.=', pt.title AS rac_product_title';
              $join.=' LEFT JOIN languages lang ON lang.is_default=1';
              $join.=' LEFT JOIN product_translations pt ON pt.product_id='.$productExpr.' AND pt.language_id=lang.id';
          }
          $itemsStmt=$pdo->prepare($select.' FROM shop_order_items oi'.$join.' WHERE oi.order_id IN ('.$placeholders.') ORDER BY oi.order_id ASC');
          $itemsStmt->execute($orderIds);
          foreach($itemsStmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
              $itemOrder=(int)$item['order_id'];
              $name='';
              foreach(['product_title','book_title','title','product_name','name','title_snapshot','rac_product_title'] as $key) {
                  if(isset($item[$key]) && trim((string)$item[$key])!=='') {$name=trim((string)$item[$key]);break;}
              }
              if($name==='')$name='Kniha';
              $kind='';
              foreach(['variant_kind','kind','product_kind','rac_variant_kind'] as $key) {
                  if(isset($item[$key]) && trim((string)$item[$key])!=='') {$kind=strtolower(trim((string)$item[$key]));break;}
              }
              $kindLabel=['print'=>'Tištěná kniha','ebook'=>'E-kniha','audio'=>'Audiokniha','audiobook'=>'Audiokniha'][$kind]??($kind!==''?$kind:'');
              $qty=isset($item['quantity'])?(int)$item['quantity']:1;
              $orderBooks[$itemOrder][]=['title'=>$name,'variant'=>$kindLabel,'quantity'=>max(1,$qty)];
          }
      }
  }
 // Pouze objednávky s testovacími platbami a bez jediné live platby.
 if(rac_orders_table($pdo,'shop_payments')){
  $pc=rac_orders_cols($pdo,'shop_payments');
  if(in_array('order_id',$pc,true)&&in_array('environment',$pc,true)){
   $st=$pdo->query("SELECT order_id FROM shop_payments GROUP BY order_id HAVING SUM(CASE WHEN environment='test' THEN 1 ELSE 0 END)>0 AND SUM(CASE WHEN environment='live' THEN 1 ELSE 0 END)=0");
   $testOrderIds=array_fill_keys(array_map('intval',$st->fetchAll(PDO::FETCH_COLUMN)),true);
  }
 }

 $fulfillmentByOrder=[];
 if(rac_orders_table($pdo,'shop_order_fulfillment')){
  $f=$pdo->query('SELECT order_id,fulfillment_status FROM shop_order_fulfillment ORDER BY order_id DESC LIMIT 10000');
  foreach($f->fetchAll(PDO::FETCH_ASSOC) as $fr){$fulfillmentByOrder[(int)$fr['order_id']]=(string)$fr['fulfillment_status'];}
 }
} catch(Throwable $ex){error_log('RAC orders list: '.$ex->getMessage());$error=$ex instanceof RuntimeException?$ex->getMessage():'Objednávky nelze načíst. Ověřte databázové tabulky.';}
?>
<?php include __DIR__.'/inc/header.class.php'; include __DIR__.'/inc/leve-meny.class.php'; ?>
<section role="main" class="content-body content-body-modern mt-0">
<header class="page-header"><h2><?= $e($pageTitle) ?></h2></header>

<?php if ($racCanProducts || $racCanStock): ?>
<div class="row justify-content-center justify-content-sm-between pt-2 mb-4"><div class="col-sm-auto mb-2">
<?php if ($racCanProducts): ?><a class="btn btn-primary btn-md font-weight-semibold btn-py-2 px-4" href="eshop-produkty.php">Produkty</a><?php endif; ?>
<?php if ($racCanStock): ?> <a class="btn btn-primary btn-md font-weight-semibold btn-py-2 px-4" href="eshop-sklad.php">Sklad knih</a><?php endif; ?>
</div></div>
<?php endif; ?>
<?php if($deletedMessage!==''): ?><div class="alert alert-success"><?= $e($deletedMessage) ?></div><?php endif; ?>
<?php if($error): ?><div class="alert alert-warning"><?= $e($error) ?></div><?php endif; ?>
<div class="row mb-4">
<?php foreach([['Objednávky celkem',$summary['total'],'primary'],['Čekají na zaplacení',$summary['unpaid'],'warning'],['Zaplacené',$summary['paid'],'success']] as $stat): ?>
<div class="col-lg-4 col-md-6 mb-3"><section class="card card-featured-left card-featured-<?= $e($stat[2]) ?>"><div class="card-body"><div class="widget-summary"><div class="widget-summary-col"><div class="summary"><h4 class="title"><?= $e($stat[0]) ?></h4><div class="info"><strong class="amount"><?= (int)$stat[1] ?></strong></div></div></div></div></div></section></div>
<?php endforeach; ?></div>
<section class="card"><header class="card-header"><h2 class="card-title">Přehled objednávek</h2></header><div class="card-body">
<form method="get" class="row g-3 align-items-end mb-4"><div class="col-12 col-md-4"><label class="form-label">Číslo objednávky nebo e-mail</label><input type="search" name="q" maxlength="120" class="form-control" value="<?= $e($q) ?>"></div><div class="col-12 col-md-3"><label class="form-label">Stav</label><select name="status" class="form-control"><option value="">Všechny</option><?php foreach($statuses as $val): ?><option value="<?= $e($val) ?>" <?= $status===$val?'selected':'' ?>><?= $e(rac_orders_label($val)) ?></option><?php endforeach; ?></select></div><div class="col-12 col-md-2"><label class="form-label" for="per_page">Zobrazit</label><select id="per_page" name="per_page" class="form-select"><?php foreach([12,24,48,96] as $size): ?><option value="<?= $size ?>"<?= $perPage===$size?' selected':'' ?>><?= $size ?> položek</option><?php endforeach; ?></select></div><div class="col-12 col-md-3"><button type="submit" class="btn btn-primary">Filtrovat</button> <a class="btn btn-default" href="eshop-objednavky.php">Zrušit filtr</a></div></form>
<div class="table-responsive"><table class="table table-striped table-hover"><thead><tr><th>Kniha</th><th>Zákazník</th><th>Částka</th><th>Stav platby</th><th>Vyřízení</th><th>Datum</th><th></th></tr></thead><tbody>
<?php foreach($orders as $o): ?>
<tr><td>
<?php $books=$orderBooks[(int)$o['id']]??[]; ?>
<?php if($books): ?>
  <?php foreach($books as $book): ?>
    <div class="mb-1"><strong><?= $e($book['title']) ?></strong><?php if($book['variant']!==''): ?> <span class="text-muted small">(<?= $e($book['variant']) ?>)</span><?php endif; ?><?php if($book['quantity']>1): ?> <span class="small">× <?= (int)$book['quantity'] ?></span><?php endif; ?></div>
  <?php endforeach; ?>
<?php else: ?><div class="text-muted small">Položky objednávky nejsou dostupné.</div><?php endif; ?>
<div class="small text-muted mt-1"><?= $e($o['order_number']) ?></div>
</td><td><?= $e($o['email']) ?></td><td class="text-nowrap"><?= $e(rac_orders_money($o['total'],$o['currency'])) ?></td><td><span class="badge <?= $o['status']==='paid'?'bg-success':($o['status']==='awaiting_payment'?'bg-warning text-dark':'bg-secondary') ?>"><?= $e(rac_orders_label((string)$o['status'])) ?></span></td><td><span class="badge bg-info text-dark"><?= $e(['new'=>'Nová','processing'=>'Připravuje se','shipped'=>'Odeslána','completed'=>'Dokončena','cancelled'=>'Zrušena'][$fulfillmentByOrder[(int)$o['id']]??'new']??'Nová') ?></span></td><td class="text-nowrap"><?= $e($o['created_at']??$o['created']??'—') ?></td><td><a class="btn btn-default btn-sm mb-1 mt-1 me-1" href="eshop-objednavka.php?id=<?= (int)$o['id'] ?>" title="Zobrazit detail" aria-label="Zobrazit detail"><i class="fas fa-search"></i></a><?php if($racCanDeleteTest && isset($testOrderIds[(int)$o['id']])): ?> <a class="btn btn-default btn-sm mb-1 mt-1 me-1" href="eshop-objednavka.php?id=<?= (int)$o['id'] ?>#smazat-test" title="Smazat testovací objednávku" aria-label="Smazat testovací objednávku"><i class="fas fa-trash-alt text-danger"></i></a><?php endif; ?></td></tr>
<?php endforeach; ?>
<?php if(!$orders): ?><tr><td colspan="7" class="text-muted">Žádné objednávky k zobrazení.</td></tr><?php endif; ?>
</tbody></table></div>
<div class="row row-gutter-sm justify-content-between mt-3">
    <div class="col-lg-auto order-2 order-lg-1">
        <p class="text-center text-lg-left mb-0">Zobrazeno <?= (int)$firstShown ?>-<?= (int)$lastShown ?> z <?= (int)$totalFiltered ?> celkem</p>
    </div>
    <div class="col-lg-auto order-1 order-lg-2 mb-3 mb-lg-0">
        <nav aria-label="Stránkování objednávek">
            <ul class="pagination pagination-modern pagination-modern-spacing justify-content-center justify-content-lg-start mb-0">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link prev" href="<?= $page <= 1 ? '#' : $e($ordersPageUrl($page - 1)) ?>" aria-label="Předchozí"><span><i class="fas fa-chevron-left" aria-hidden="true"></i></span></a>
                </li>
                <?php
                $pagesToShow = [1];
                for ($n = $page - 1; $n <= $page + 1; $n++) {
                    if ($n > 1 && $n < $pageCount) $pagesToShow[] = $n;
                }
                if ($pageCount > 1) $pagesToShow[] = $pageCount;
                $pagesToShow = array_values(array_unique($pagesToShow));
                sort($pagesToShow);
                $previousPage = 0;
                foreach ($pagesToShow as $n):
                ?>
                    <?php if ($previousPage > 0 && $n > $previousPage + 1): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                    <li class="page-item <?= $n === $page ? 'active' : '' ?>">
                        <a class="page-link" href="<?= $e($ordersPageUrl((int)$n)) ?>"<?= $n === $page ? ' aria-current="page"' : '' ?>><?= (int)$n ?></a>
                    </li>
                    <?php $previousPage = $n; ?>
                <?php endforeach; ?>
                <li class="page-item <?= $page >= $pageCount ? 'disabled' : '' ?>">
                    <a class="page-link next" href="<?= $page >= $pageCount ? '#' : $e($ordersPageUrl($page + 1)) ?>" aria-label="Další"><span><i class="fas fa-chevron-right" aria-hidden="true"></i></span></a>
                </li>
            </ul>
        </nav>
    </div>
</div>
</div></section>
</section>
<?php include __DIR__.'/inc/footer.class.php'; ?>
