<?php
// RAC sklad knih / Porto BOS24 / PHP 7.4 / verze 2026-10-13
// Sklad je primarne product_variants.stock_quantity; pouze tiskova varianta.
declare(strict_types=1);
require_once __DIR__.'/config/session.php';
require_once __DIR__.'/config/db.php';
require_once __DIR__.'/config/auth.php';
require_once __DIR__.'/config/auth_roles.php';
require_min_role('admin');
$viewer=current_user_row();
// RAC RBAC 2026-10-20 / Faze 5B: oddeleni cteni, skladovych operaci a editace knih.
$racCanStockEdit = rac_can($pdo, $viewer, 'stock.edit');
$racCanProductsView = rac_can($pdo, $viewer, 'products.view');
$racCanProductsEdit = rac_can($pdo, $viewer, 'products.edit');
$errors=array();$ok=null;
$csrf=csrf_token('csrf_eshop_sklad');
if(isset($_SESSION['sklad_flash_ok'])){$ok=(string)$_SESSION['sklad_flash_ok'];unset($_SESSION['sklad_flash_ok']);}
if($_SERVER['REQUEST_METHOD']==='POST'){
 if (!$racCanStockEdit) { http_response_code(403); exit('Nemáte oprávnění upravovat skladové zásoby.'); }
 if(!csrf_valid('csrf_eshop_sklad',(string)($_POST['csrf']??''))){$errors[]='Neplatný bezpečnostní token.';}
 $variantId=filter_var($_POST['variant_id']??null,FILTER_VALIDATE_INT,array('options'=>array('min_range'=>1)));
 $action=(string)($_POST['action']??'');
 $qty=filter_var($_POST['quantity']??null,FILTER_VALIDATE_INT,array('options'=>array('min_range'=>1)));
 $note=trim((string)($_POST['note']??''));
 if(!$variantId || !in_array($action,array('in','out'),true)){$errors[]='Neplatná operace skladu.';}
 if(!$qty || $qty>1000000){$errors[]='Množství musí být kladné celé číslo do 1 000 000.';}
 if(mb_strlen($note)>500){$errors[]='Poznámka může mít nejvýše 500 znaků.';}
 if(!$errors){
  try{
   $pdo->beginTransaction();
   $st=$pdo->prepare("SELECT id,stock_quantity FROM product_variants WHERE id=? AND kind='print' AND active=1 FOR UPDATE");
   $st->execute(array($variantId));$variant=$st->fetch(PDO::FETCH_ASSOC);
   if(!$variant){throw new RuntimeException('Tištěná varianta knihy nebyla nalezena nebo je deaktivovaná.');}
   $before=(int)($variant['stock_quantity']??0);
   $delta=$action==='in'?(int)$qty:-(int)$qty;
   $after=$before+$delta;
   if($after<0){throw new RuntimeException('Nelze vydat více kusů, než je skladem.');}
   if($after>2147483647){throw new RuntimeException('Skladové množství přesahuje povolený limit.');}
   $st=$pdo->prepare('UPDATE product_variants SET stock_quantity=? WHERE id=?');
   $st->execute(array($after,$variantId));
   $st=$pdo->prepare('INSERT INTO eshop_stock_movements (variant_id,change_qty,balance_after,source,note,user_id,created_at) VALUES (?,?,?,?,?,?,NOW())');
   $st->execute(array($variantId,$delta,$after,$action==='in'?'receipt':'issue',$note!==''?$note:null,(int)($viewer['id']??0)?:null));
   $pdo->commit();
   $_SESSION['sklad_flash_ok']=$action==='in'?'Knihy byly naskladněny.':'Knihy byly vyskladněny.';
   header('Location: eshop-sklad.php');exit;
  }catch(Throwable $ex){if($pdo->inTransaction()){$pdo->rollBack();}$errors[]=$ex instanceof RuntimeException?$ex->getMessage():'Operaci se nepodařilo uložit. Ověřte databázovou migraci.';error_log('RAC stock: '.$ex->getMessage());}
 }
}
$q=trim((string)($_GET['q']??''));$view=(string)($_GET['view']??'all');
if(!in_array($view,array('all','low','out'),true)){$view='all';}
$params=array();
$sql="SELECT v.id variant_id,v.product_id,v.stock_quantity,v.minimum_stock,v.sku,v.isbn,p.slug,p.status,
 COALESCE(NULLIF(t.title,''),p.slug) title
 FROM product_variants v INNER JOIN products p ON p.id=v.product_id
 LEFT JOIN languages l ON l.is_default=1
 LEFT JOIN product_translations t ON t.product_id=p.id AND t.language_id=l.id
 WHERE v.kind='print' AND v.active=1";
if($q!==''){$sql.=' AND (p.slug LIKE :q1 OR t.title LIKE :q2 OR v.sku LIKE :q3 OR v.isbn LIKE :q4)';foreach(array('q1','q2','q3','q4') as $key){$params[':'.$key]='%'.$q.'%';}}
if($view==='low'){$sql.=' AND v.stock_quantity>0 AND v.stock_quantity<=COALESCE(v.minimum_stock,0)';}
if($view==='out'){$sql.=' AND COALESCE(v.stock_quantity,0)<=0';}
$sql.=' ORDER BY p.sort_order,p.id LIMIT 500';
$st=$pdo->prepare($sql);$st->execute($params);$books=$st->fetchAll(PDO::FETCH_ASSOC);
$stats=$pdo->query("SELECT COUNT(*) titles,COALESCE(SUM(COALESCE(stock_quantity,0)),0) pieces,
 COALESCE(SUM(CASE WHEN COALESCE(stock_quantity,0)<=0 THEN 1 ELSE 0 END),0) out_count,
 COALESCE(SUM(CASE WHEN stock_quantity>0 AND stock_quantity<=COALESCE(minimum_stock,0) THEN 1 ELSE 0 END),0) low_count
 FROM product_variants WHERE kind='print' AND active=1")->fetch(PDO::FETCH_ASSOC);
// Historie pouze z nainstalovane tabulky.
$history=array();$historyAvailable=true;
try{$history=$pdo->query("SELECT m.*,v.product_id,COALESCE(NULLIF(t.title,''),p.slug) title
 FROM eshop_stock_movements m LEFT JOIN product_variants v ON v.id=m.variant_id
 LEFT JOIN products p ON p.id=v.product_id
 LEFT JOIN languages l ON l.is_default=1
 LEFT JOIN product_translations t ON t.product_id=p.id AND t.language_id=l.id
 ORDER BY m.id DESC LIMIT 40")->fetchAll(PDO::FETCH_ASSOC);}catch(Throwable $ex){$historyAvailable=false;error_log('RAC stock history: '.$ex->getMessage());}
$pageTitle='Sklad knih';$racUsePortoLayout=true;
$base='/administrace';$assets=$base;$title='Sklad knih | RAC';
$viewerName=(string)($viewer['username']??'Administrátor');
$e=static function($value):string{return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');};
$menuGroups=[
 ['Přehled',[['Dashboard','/administrace/dashboard.php','bx bx-home-alt']]],
 ['Administrace',[['Vložit administrátora','/administrace/sprava-admin.php','icons icon-user-follow'],['Zobrazit administrátory','/administrace/sprava-admin-zobrazit.php','icons icon-people'],['Jazyky','/administrace/jazyky.php','icons icon-globe']]],
 ['Obsah webu',[['Úvodní stránka','/administrace/obsah-uvod.php','icons icon-home'],['O autorovi','/administrace/obsah-autor.php','icons icon-note'],['Kontakt','/administrace/obsah-kontakt.php','icons icon-envelope']]],
 ['E-shop',[['Produkty','/administrace/eshop-produkty.php','icons icon-basket'],['Sklad knih','/administrace/eshop-sklad.php','icons icon-layers'],['Doprava','/administrace/eshop-doprava.php','icons icon-drawer']]],
 ['Platební brána',[['Nastavení Stripe','/administrace/billing/admin.php','el el-cogs'],['Platby','/administrace/billing/admin-platby.php','el el-credit-card'],['Webhooky','/administrace/billing/admin-webhooky.php','icons icon-shuffle'],['Diagnostika','/administrace/billing/admin-diagnostika.php','icons icon-wrench']]]
];
$currentPath=parse_url((string)($_SERVER['REQUEST_URI']??''),PHP_URL_PATH)?:'/administrace/eshop-sklad.php';
$viewerRole=(string)($viewer['role']??'');$roleLabel=function_exists('role_label')?role_label($viewerRole):$viewerRole;
include __DIR__.'/inc/header.class.php';include __DIR__.'/inc/leve-meny.class.php';
?>
<section role="main" class="content-body content-body-modern mt-0">
<header class="page-header"><h2>Sklad knih</h2></header>
<div class="row justify-content-center justify-content-sm-between pt-2 mb-3"><div class="col-sm-auto text-center mb-2">
<?php if($racCanProductsEdit): ?><a href="eshop-produkt.php" class="btn btn-primary btn-md font-weight-semibold btn-py-2 px-4">+ Přidat knihu</a><?php endif; ?>
<?php if($racCanProductsView): ?><a href="eshop-produkty.php" class="btn btn-primary btn-md font-weight-semibold btn-py-2 px-4">Produkty</a><?php endif; ?></div></div>
<?php if($ok): ?><div class="alert alert-success"><?= $e($ok) ?></div><?php endif; ?>
<?php if($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $err): ?><li><?= $e($err) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<?php if(!$historyAvailable): ?><div class="alert alert-warning">Historie pohybů není dostupná. Nejdřív spusťte SQL migraci <code>database/010_eshop_stock_movements.sql</code>. Bez ní se příjem a výdej neuloží.</div><?php endif; ?>
<div class="row mb-4">
<?php foreach(array(array('Tištěné tituly',(int)$stats['titles'],'primary'),array('Kusů skladem',(int)$stats['pieces'],'success'),array('Nízké zásoby',(int)$stats['low_count'],'warning'),array('Vyprodáno',(int)$stats['out_count'],'danger')) as $box): ?>
<div class="col-lg-3 col-md-6 mb-3"><section class="card card-featured-left card-featured-<?= $e($box[2]) ?>"><div class="card-body"><div class="widget-summary"><div class="widget-summary-col"><div class="summary"><h4 class="title"><?= $e($box[0]) ?></h4><div class="info"><strong class="amount"><?= (int)$box[1] ?></strong></div></div></div></div></div></section></div>
<?php endforeach; ?></div>
<div class="tabs mb-4"><ul class="nav nav-tabs tabs-primary" role="tablist">
<li class="nav-item"><button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#sklad-seznam">Sklad</button></li>
<li class="nav-item"><button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#sklad-pohyby">Pohyby</button></li>
</ul><div class="tab-content">
<div class="tab-pane fade show active" id="sklad-seznam"><section class="card"><header class="card-header"><h2 class="card-title">Položky skladu</h2></header><div class="card-body">
<form method="get" class="row g-3 align-items-end mb-4"><div class="col-lg-5 col-md-6"><label class="form-label">Vyhledávání</label><input type="search" name="q" class="form-control" placeholder="Název, ISBN, SKU nebo URL" value="<?= $e($q) ?>"></div>
<div class="col-lg-3 col-md-3"><label class="form-label">Zobrazení</label><select name="view" class="form-control"><option value="all" <?= $view==='all'?'selected':'' ?>>Všechny knihy</option><option value="low" <?= $view==='low'?'selected':'' ?>>Nízké zásoby</option><option value="out" <?= $view==='out'?'selected':'' ?>>Vyprodáno</option></select></div>
<div class="col-lg-4 col-md-3"><button class="btn btn-primary" type="submit">Filtrovat</button> <a class="btn btn-default" href="eshop-sklad.php">Zrušit filtr</a></div></form>
<?php if(!$books): ?><div class="alert alert-info">Žádné odpovídající tištěné knihy.</div><?php endif; ?>
<?php foreach($books as $book): $qty=(int)($book['stock_quantity']??0);$min=(int)($book['minimum_stock']??0); ?>
<div class="card mb-3"><div class="card-body"><div class="row align-items-center gy-3">
<div class="col-lg-4"><strong><?= $e($book['title']) ?></strong><div class="text-muted small"><?= $e($book['sku']??$book['slug']) ?></div><?php if($racCanProductsEdit): ?><a href="eshop-produkt.php?id=<?= (int)$book['product_id'] ?>">Upravit produkt</a><?php endif; ?></div>
<div class="col-lg-2 col-md-4"><span class="text-muted">Skladem</span><div class="fs-5 fw-bold"><?= $qty ?> ks</div></div>
<div class="col-lg-2 col-md-4"><span class="text-muted">Minimum</span><div><?= $min ?> ks</div><?php if($qty<=0): ?><span class="badge bg-danger">Vyprodáno</span><?php elseif($qty<=$min): ?><span class="badge bg-warning text-dark">Nízké zásoby</span><?php else: ?><span class="badge bg-success">Dostupné</span><?php endif; ?></div>
<?php if($racCanStockEdit): ?><div class="col-lg-4 col-md-12"><form method="post" class="row g-2 align-items-end"><input type="hidden" name="csrf" value="<?= $e($csrf) ?>"><input type="hidden" name="variant_id" value="<?= (int)$book['variant_id'] ?>">
<div class="col-5"><label class="form-label small">Pohyb</label><select name="action" class="form-control"><option value="in">Příjem</option><option value="out">Výdej</option></select></div>
<div class="col-4"><label class="form-label small">Kusů</label><input type="number" class="form-control" name="quantity" min="1" max="1000000" required></div>
<div class="col-3"><button type="submit" class="btn btn-primary w-100">Uložit</button></div>
<div class="col-12"><input type="text" class="form-control" maxlength="500" name="note" placeholder="Poznámka k pohybu (nepovinné)"></div>
</form></div><?php endif; ?></div></div></div>
<?php endforeach; ?></div></section></div>
<div class="tab-pane fade" id="sklad-pohyby"><section class="card"><header class="card-header"><h2 class="card-title">Historie skladových pohybů</h2></header><div class="card-body"><div class="table-responsive"><table class="table table-striped"><thead><tr><th>Datum</th><th>Kniha</th><th>Pohyb</th><th>Změna</th><th>Stav po</th><th>Poznámka</th></tr></thead><tbody>
<?php foreach($history as $move): ?><tr><td><?= $e($move['created_at']) ?></td><td><?= $e($move['title']??('Varianta #'.$move['variant_id'])) ?></td><td><?= $e($move['source']) ?></td><td><?= (int)$move['change_qty']>0?'+':'' ?><?= (int)$move['change_qty'] ?></td><td><?= (int)$move['balance_after'] ?></td><td><?= $e($move['note']??'') ?></td></tr><?php endforeach; ?>
<?php if(!$history): ?><tr><td colspan="6" class="text-muted">Zatím nejsou evidovány žádné pohyby.</td></tr><?php endif; ?>
</tbody></table></div></div></section></div>
</div></div>
</section>
<?php include __DIR__.'/inc/footer.class.php'; ?>
