<?php
// RAC Eshop produkt – Porto/BOS24 – 2026-10-12; PHP 7.4+
declare(strict_types=1);
require_once __DIR__.'/config/session.php';
require_once __DIR__.'/config/db.php';
require_once __DIR__.'/config/auth.php';
require_once __DIR__.'/config/auth_roles.php';
require_min_role('admin');
$id=max(0,(int)($_GET['id']??0));
$errors=array();
$statuses=array('draft'=>'Koncept','active'=>'Aktivní','hidden'=>'Skrytý','sold_out'=>'Vyprodáno');
$kinds=array('print'=>'Tištěná kniha','ebook'=>'E-kniha','audio'=>'Audiokniha');
$languages=$pdo->query('SELECT id,code,native_name,is_default FROM languages WHERE active=1 ORDER BY is_default DESC,sort_order,id')->fetchAll(PDO::FETCH_ASSOC);
$categories=$pdo->query("SELECT c.id,COALESCE(NULLIF(ct.name,''),c.code) AS name FROM product_categories c LEFT JOIN languages l ON l.is_default=1 LEFT JOIN product_category_translations ct ON ct.category_id=c.id AND ct.language_id=l.id WHERE c.active=1 ORDER BY c.sort_order,c.id")->fetchAll(PDO::FETCH_ASSOC);
$csrf=csrf_token('csrf_eshop_product');
$viewerStock = current_user_row();
function product_decimal($value,bool $required,array &$errors,string $label):?string {
 $text=str_replace(',','.',trim((string)$value));
 if ($text===''){if($required)$errors[]='Vyplňte '.$label.'.';return null;}
 if(!preg_match('/^\d{1,10}(?:\.\d{1,2})?$/',$text)){$errors[]='Neplatná hodnota: '.$label.'.';return null;}
 return number_format((float)$text,2,'.','');
}
function product_sale_datetime($value,array &$errors,string $label):?string {
 $value=trim((string)$value);if($value==='')return null;
 $value=str_replace('T',' ',$value);
 $dt=DateTimeImmutable::createFromFormat('!Y-m-d H:i',$value,new DateTimeZone('Europe/Prague'));
 if(!$dt||$dt->format('Y-m-d H:i')!==$value){$errors[]='Neplatné datum a čas: '.$label.'.';return null;}
 return $value.':00';
}
function product_sale_input($value):string {return !$value?'':str_replace(' ','T',substr((string)$value,0,16));}
function product_optional_int($value,array &$errors,string $label):?int {
 $text=trim((string)$value);if($text==='')return null;
 if(!ctype_digit($text)||strlen($text)>9){$errors[]='Neplatná hodnota: '.$label.'.';return null;}
 return (int)$text;
}
$product=array('slug'=>'','author'=>'','release_date'=>'','category_id'=>'','status'=>'draft','featured'=>0,'sort_order'=>0);
$translations=array();$variants=array();
if($id){
 $st=$pdo->prepare('SELECT * FROM products WHERE id=?');$st->execute(array($id));$p=$st->fetch(PDO::FETCH_ASSOC);
 if(!$p){http_response_code(404);exit('Produkt nenalezen.');}$product=$p;
 $st=$pdo->prepare('SELECT * FROM product_translations WHERE product_id=?');$st->execute(array($id));
 foreach($st->fetchAll(PDO::FETCH_ASSOC) as $r)$translations[(int)$r['language_id']]=$r;
 $st=$pdo->prepare('SELECT * FROM product_variants WHERE product_id=?');$st->execute(array($id));
 foreach($st->fetchAll(PDO::FETCH_ASSOC) as $r)$variants[$r['kind']]=$r;
}
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!csrf_valid('csrf_eshop_product',(string)($_POST['csrf']??'')))$errors[]='Neplatný bezpečnostní token.';
 $posted=(array)($_POST['product']??array());$slug=strtolower(trim((string)($posted['slug']??'')));
 if(!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/',$slug)||strlen($slug)>190)$errors[]='Slug musí obsahovat jen malá písmena bez diakritiky, číslice a pomlčky.';
 $status=(string)($posted['status']??'draft');if(!isset($statuses[$status]))$errors[]='Neplatný stav.';
 $author=trim((string)($posted['author']??''));if(mb_strlen($author)>190)$errors[]='Autor může mít maximálně 190 znaků.';
 $date=trim((string)($posted['release_date']??''));
 if($date!==''&&(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)||!($dt=DateTime::createFromFormat('!Y-m-d',$date))||$dt->format('Y-m-d')!==$date))$errors[]='Neplatné datum vydání.';
 $category=(int)($posted['category_id']??0);
 if($category>0){$st=$pdo->prepare('SELECT COUNT(*) FROM product_categories WHERE id=?');$st->execute(array($category));if(!(int)$st->fetchColumn())$errors[]='Kategorie neexistuje.';}
 $order=(int)($posted['sort_order']??0);
 $postedTranslations=(array)($_POST['translations']??array());$defaultName='';
 foreach($languages as $lang){
  $lid=(int)$lang['id'];$t=isset($postedTranslations[$lid])&&is_array($postedTranslations[$lid])?$postedTranslations[$lid]:array();
  $translations[$lid]=array('title'=>trim((string)($t['title']??'')),'short_description'=>trim((string)($t['short_description']??'')),'description'=>trim((string)($t['description']??'')),'seo_title'=>trim((string)($t['seo_title']??'')),'seo_description'=>trim((string)($t['seo_description']??'')));
  if((int)$lang['is_default']===1)$defaultName=$translations[$lid]['title'];
  if(mb_strlen($translations[$lid]['title'])>255||mb_strlen($translations[$lid]['short_description'])>300||mb_strlen($translations[$lid]['seo_title'])>255||mb_strlen($translations[$lid]['seo_description'])>320)$errors[]='Text je příliš dlouhý pro jazyk '.$lang['native_name'].'.';
 }
 if($defaultName==='')$errors[]='Název ve výchozím jazyce je povinný.';
 $postedVariants=(array)($_POST['variants']??array());$cleanVariants=array();
 foreach($kinds as $kind=>$kindName){
  $v=isset($postedVariants[$kind])&&is_array($postedVariants[$kind])?$postedVariants[$kind]:array();
  if(!isset($v['enabled']))continue;
  $price=product_decimal($v['price']??'',true,$errors,$kindName.' – cena');
  $sale=product_decimal($v['sale_price']??'',false,$errors,$kindName.' – akční cena');
  $saleStart=product_sale_datetime($v['sale_starts_at']??'',$errors,$kindName.' – začátek akce');
  $saleEnd=product_sale_datetime($v['sale_ends_at']??'',$errors,$kindName.' – konec akce');
  if($sale!==null&&($saleStart===null||$saleEnd===null))$errors[]=$kindName.': u akční ceny vyplňte začátek i konec platnosti.';
  if($sale===null&&($saleStart!==null||$saleEnd!==null))$errors[]=$kindName.': datum akce lze zadat pouze s akční cenou.';
  if($saleStart!==null&&$saleEnd!==null&&strcmp($saleStart,$saleEnd)>=0)$errors[]=$kindName.': konec akce musí být po začátku.';
  $vat=product_decimal($v['vat_rate']??'0',true,$errors,$kindName.' – DPH');
  if($sale!==null&&$price!==null&&(float)$sale>=(float)$price)$errors[]=$kindName.': akční cena musí být nižší než běžná.';
  if($vat!==null&&(float)$vat>100)$errors[]=$kindName.': sazba DPH nesmí přesáhnout 100 %.';
  $sku=trim((string)($v['sku']??''));$isbn=trim((string)($v['isbn']??''));$ean=trim((string)($v['ean']??''));
  $currency=strtoupper(trim((string)($v['currency']??'CZK')));
  if(!preg_match('/^[A-Z]{3}$/',$currency)||strlen($sku)>100||strlen($isbn)>32||strlen($ean)>32)$errors[]=$kindName.': neplatná měna nebo identifikátor.';
  $cleanVariants[$kind]=array('price'=>$price,'sale_price'=>$sale,'sale_starts_at'=>$saleStart,'sale_ends_at'=>$saleEnd,'vat_rate'=>$vat,'currency'=>$currency,'sku'=>$sku?:null,'isbn'=>$isbn?:null,'ean'=>$ean?:null,'stock_quantity'=>$kind==='print'?product_optional_int($v['stock_quantity']??'',$errors,'sklad'):null,'minimum_stock'=>$kind==='print'?product_optional_int($v['minimum_stock']??'',$errors,'minimální sklad'):null,'weight_grams'=>$kind==='print'?product_optional_int($v['weight_grams']??'',$errors,'hmotnost'):null);
 }
 if(!$errors){try{
  $pdo->beginTransaction();
  // Snapshot pred zmenou skladu; zamceno po celou transakci.
  $stockBefore=array();
  if($id>0){
   $lock=$pdo->prepare("SELECT id,stock_quantity FROM product_variants WHERE product_id=? AND kind='print' FOR UPDATE");
   $lock->execute(array($id));
   foreach($lock->fetchAll(PDO::FETCH_ASSOC) as $oldPrint){$stockBefore[(int)$oldPrint['id']]=(int)($oldPrint['stock_quantity']??0);}
  }
  if($id){$st=$pdo->prepare('UPDATE products SET slug=?,author=?,release_date=?,category_id=?,status=?,featured=?,sort_order=? WHERE id=?');$st->execute(array($slug,$author?:null,$date?:null,$category?:null,$status,isset($posted['featured'])?1:0,$order,$id));}
  else{$st=$pdo->prepare('INSERT INTO products(slug,author,release_date,category_id,status,featured,sort_order) VALUES(?,?,?,?,?,?,?)');$st->execute(array($slug,$author?:null,$date?:null,$category?:null,$status,isset($posted['featured'])?1:0,$order));$id=(int)$pdo->lastInsertId();}
  $st=$pdo->prepare('INSERT INTO product_translations(product_id,language_id,title,short_description,description,seo_title,seo_description) VALUES(?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE title=VALUES(title),short_description=VALUES(short_description),description=VALUES(description),seo_title=VALUES(seo_title),seo_description=VALUES(seo_description)');
  foreach($translations as $lid=>$t)$st->execute(array($id,$lid,$t['title'],$t['short_description']?:null,$t['description']?:null,$t['seo_title']?:null,$t['seo_description']?:null));
  $st=$pdo->prepare('INSERT INTO product_variants(product_id,kind,sku,isbn,ean,price,sale_price,sale_starts_at,sale_ends_at,currency,vat_rate,stock_quantity,minimum_stock,weight_grams,active) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,1) ON DUPLICATE KEY UPDATE sku=VALUES(sku),isbn=VALUES(isbn),ean=VALUES(ean),price=VALUES(price),sale_price=VALUES(sale_price),sale_starts_at=VALUES(sale_starts_at),sale_ends_at=VALUES(sale_ends_at),currency=VALUES(currency),vat_rate=VALUES(vat_rate),stock_quantity=VALUES(stock_quantity),minimum_stock=VALUES(minimum_stock),weight_grams=VALUES(weight_grams),active=1');
  foreach($cleanVariants as $kind=>$v)$st->execute(array($id,$kind,$v['sku'],$v['isbn'],$v['ean'],$v['price'],$v['sale_price'],$v['sale_starts_at'],$v['sale_ends_at'],$v['currency'],$v['vat_rate'],$v['stock_quantity'],$v['minimum_stock'],$v['weight_grams']));
  $st=$pdo->prepare('UPDATE product_variants SET active=0 WHERE product_id=? AND kind=?');
  foreach($kinds as $kind=>$label)if(!isset($cleanVariants[$kind]))$st->execute(array($id,$kind));
  // Prvotni vlozeni i rucni upravy poctu kusu se zapisuji do historie.
  $stockQuery=$pdo->prepare("SELECT id,stock_quantity FROM product_variants WHERE product_id=? AND kind='print' FOR UPDATE");
  $stockQuery->execute(array($id));
  $movement=$pdo->prepare('INSERT INTO eshop_stock_movements (variant_id,change_qty,balance_after,source,note,user_id,created_at) VALUES (?,?,?,?,?,?,NOW())');
  foreach($stockQuery->fetchAll(PDO::FETCH_ASSOC) as $newPrint){
   $variantId=(int)$newPrint['id'];$after=(int)($newPrint['stock_quantity']??0);$before=$stockBefore[$variantId]??0;
   if($after!==$before){$movement->execute(array($variantId,$after-$before,$after,'product','Změna zásoby v administraci produktu',(int)($viewerStock['id']??0)?:null));}
  }
  $pdo->commit();header('Location: eshop-produkt.php?id='.$id.'&saved=1');exit;
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$errors[]='Záznam se nepodařilo uložit. Zkontrolujte unikátnost URL a SKU. Podrobnosti najdete v serverovém error logu.';error_log('RAC eshop product save: '.$e->getMessage());}}
 $product=array_merge($product,$posted);$variants=$postedVariants;
}
$pageTitle=$id?'Upravit produkt':'Nový produkt';
$racUsePortoLayout=true;
$base='/administrace';$assets=$base;$title=$pageTitle.' | RAC';
$viewer=current_user_row();$viewerName=(string)($viewer['username']??'Administrátor');
$e=static function($value):string{return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');};
$menuGroups=[
 ['Přehled',[['Dashboard','/administrace/dashboard.php','bx bx-home-alt']]],
 ['Obsah webu',[['Úvodní stránka','/administrace/obsah-uvod.php','icons icon-home'],['O autorovi','/administrace/obsah-autor.php','icons icon-note'],['Kontakt','/administrace/obsah-kontakt.php','icons icon-envelope'],['Jazyky','/administrace/jazyky.php','icons icon-globe']]],
 ['E-shop',[['Produkty','/administrace/eshop-produkty.php','icons icon-basket'],['Doprava','/administrace/eshop-doprava.php','icons icon-drawer']]],
 ['Billing',[['Nastavení Stripe','/administrace/billing/admin.php','el el-cogs'],['Produkty','/administrace/billing/admin-produkty.php','icons icon-basket'],['Platby','/administrace/billing/admin-platby.php','el el-credit-card'],['Předplatné','/administrace/billing/admin-predplatne.php','icons icon-refresh'],['Webhooky','/administrace/billing/admin-webhooky.php','icons icon-shuffle'],['Diagnostika','/administrace/billing/admin-diagnostika.php','icons icon-wrench']]],
 ['Administrace',[['Vložit administrátora','/administrace/sprava-admin.php','icons icon-user-follow'],['Zobrazit administrátory','/administrace/sprava-admin-zobrazit.php','icons icon-people']]],
];
$currentPath=parse_url((string)($_SERVER['REQUEST_URI']??''),PHP_URL_PATH)?:'/administrace/eshop-produkt.php';
$viewerRole=(string)($viewer['role']??'');$roleLabel=function_exists('role_label')?role_label($viewerRole):$viewerRole;
include __DIR__.'/inc/header.class.php';
include __DIR__.'/inc/leve-meny.class.php';
?>
<section role="main" class="content-body content-body-modern mt-0">
<header class="page-header"><h2><?= h($pageTitle) ?></h2><div class="right-wrapper text-end"><ol class="breadcrumbs"><li><a href="/administrace/dashboard.php"><i class="bx bx-home-alt"></i></a></li><li><span>E-shop</span></li><li><a href="eshop-produkty.php">Produkty</a></li></ol></div></header>
<div class="row mt-4"><div class="col-12">
<div class="d-flex justify-content-between flex-wrap align-items-center gap-2 mb-3"><p class="text-muted mb-0">Základní údaje, jazykové mutace a prodejní varianty</p><a class="btn btn-default" href="eshop-produkty.php"><i class="fas fa-arrow-left me-1"></i> Zpět na produkty</a></div>
<?php if(isset($_GET['saved'])): ?><div class="alert alert-success">Produkt byl úspěšně uložen.</div><?php endif; ?>
<?php if($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $error): ?><li><?= h($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?= h($csrf) ?>">
<section class="card mb-4"><header class="card-header"><h2 class="card-title">Základní informace</h2></header><div class="card-body"><div class="row g-3">
<div class="col-12 col-lg-6"><label class="form-label">URL slug *</label><input class="form-control" name="product[slug]" maxlength="190" pattern="[a-z0-9]+(-[a-z0-9]+)*" required value="<?= h($product['slug']??'') ?>" placeholder="zivot-po-zivote"></div>
<div class="col-12 col-lg-6"><label class="form-label">Autor</label><input class="form-control" name="product[author]" maxlength="190" value="<?= h($product['author']??'') ?>"></div>
<div class="col-12 col-md-6 col-lg-3"><label class="form-label">Datum vydání</label><input class="form-control" type="date" name="product[release_date]" value="<?= h($product['release_date']??'') ?>"></div>
<div class="col-12 col-md-6 col-lg-3"><label class="form-label">Kategorie</label><select class="form-select" name="product[category_id]"><option value="">Bez kategorie</option><?php foreach($categories as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)($product['category_id']??0)===(int)$c['id']?'selected':'' ?>><?= h($c['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-12 col-md-6 col-lg-3"><label class="form-label">Stav</label><select class="form-select" name="product[status]"><?php foreach($statuses as $key=>$label): ?><option value="<?= h($key) ?>" <?= ($product['status']??'draft')===$key?'selected':'' ?>><?= h($label) ?></option><?php endforeach; ?></select></div>
<div class="col-12 col-md-6 col-lg-3"><label class="form-label">Pořadí</label><input class="form-control" type="number" name="product[sort_order]" value="<?= (int)($product['sort_order']??0) ?>"></div>
<div class="col-12"><div class="form-check"><input class="form-check-input" id="product-featured" type="checkbox" name="product[featured]" value="1" <?= !empty($product['featured'])?'checked':'' ?>><label class="form-check-label" for="product-featured">Doporučený produkt</label></div></div>
</div></div></section>
<section class="card mb-4"><header class="card-header"><h2 class="card-title">Jazykové mutace</h2></header><div class="card-body">
<?php if(!$languages): ?><div class="alert alert-warning">Nejsou aktivní jazyky.</div><?php endif; ?>
<ul class="nav nav-tabs" role="tablist" id="product-lang-tabs"><?php foreach($languages as $index=>$lang): $lid=(int)$lang['id']; ?><li class="nav-item"><button type="button" class="nav-link<?= $index===0?' active':'' ?>" data-rac-lang-tab="<?= $lid ?>" aria-selected="<?= $index===0?'true':'false' ?>"><?= h($lang['native_name']) ?><?= (int)$lang['is_default']===1?' *':'' ?></button></li><?php endforeach; ?></ul>
<div class="border border-top-0 rounded-bottom p-3 p-lg-4"><?php foreach($languages as $index=>$lang): $lid=(int)$lang['id'];$t=$translations[$lid]??array(); ?><div data-rac-lang-panel="<?= $lid ?>" <?= $index!==0?'hidden':'' ?>>
<div class="mb-3"><label class="form-label">Název <?= (int)$lang['is_default']===1?'*':'' ?></label><input class="form-control" name="translations[<?= $lid ?>][title]" maxlength="255" value="<?= h($t['title']??'') ?>"></div>
<div class="mb-3"><label class="form-label">Krátký popis (max. 300 znaků)</label><textarea class="form-control" name="translations[<?= $lid ?>][short_description]" rows="3" maxlength="300"><?= h($t['short_description']??'') ?></textarea></div>
<div class="mb-3"><label class="form-label">Popis</label><textarea class="form-control" name="translations[<?= $lid ?>][description]" rows="6"><?= h($t['description']??'') ?></textarea></div>
<div class="row g-3"><div class="col-12 col-lg-6"><label class="form-label">SEO title</label><input class="form-control" maxlength="255" name="translations[<?= $lid ?>][seo_title]" value="<?= h($t['seo_title']??'') ?>"></div><div class="col-12 col-lg-6"><label class="form-label">SEO description</label><textarea class="form-control" rows="2" maxlength="320" name="translations[<?= $lid ?>][seo_description]"><?= h($t['seo_description']??'') ?></textarea></div></div>
</div><?php endforeach; ?></div></div></section>
<?php foreach($kinds as $kind=>$kindLabel): $v=$variants[$kind]??array();$enabled=$_SERVER['REQUEST_METHOD']==='POST'?isset($v['enabled']):(!empty($v['id'])&&(int)($v['active']??0)===1); ?>
<section class="card mb-4"><header class="card-header"><h2 class="card-title"><?= h($kindLabel) ?></h2></header><div class="card-body">
<div class="form-check mb-3"><input class="form-check-input" type="checkbox" id="enable-<?= h($kind) ?>" name="variants[<?= h($kind) ?>][enabled]" value="1" <?= $enabled?'checked':'' ?>><label class="form-check-label" for="enable-<?= h($kind) ?>">Nabízet tuto variantu</label></div>
<div class="row g-3">
<?php foreach(array('price'=>'Cena','sale_price'=>'Akční cena','sale_starts_at'=>'Akce od (datum a čas)','sale_ends_at'=>'Akce do (datum a čas)','currency'=>'Měna','vat_rate'=>'Sazba DPH (%)','sku'=>'SKU','isbn'=>'ISBN','ean'=>'EAN') as $field=>$label): ?>
<div class="col-12 col-md-6 col-xl-4"><label class="form-label"><?= h($label) ?></label><input class="form-control" <?= strpos($field,'sale_')===0&&$field!=='sale_price'?'type="datetime-local"':'type="text"' ?> name="variants[<?= h($kind) ?>][<?= h($field) ?>]" <?= $field==='currency'?'maxlength="3"':($field==='sku'?'maxlength="100"':(in_array($field,array('isbn','ean'),true)?'maxlength="32"':'')) ?> value="<?= h(in_array($field,array('sale_starts_at','sale_ends_at'),true)?product_sale_input($v[$field]??''):($v[$field]??($field==='currency'?'CZK':($field==='vat_rate'?'0':'')))) ?>"></div>
<?php endforeach; ?>
<?php if($kind==='print'): ?><?php foreach(array('stock_quantity'=>'Počet skladem','minimum_stock'=>'Minimální zásoba','weight_grams'=>'Hmotnost (g)') as $field=>$label): ?><div class="col-12 col-md-6 col-xl-4"><label class="form-label"><?= h($label) ?></label><input class="form-control" type="number" min="0" name="variants[print][<?= h($field) ?>]" value="<?= h($v[$field]??'') ?>"></div><?php endforeach; ?><?php endif; ?>
</div></div></section>
<?php endforeach; ?>
<?php if($id): ?><p><a class="btn btn-default" href="eshop-fotografie.php?id=<?= (int)$id ?>"><i class="fas fa-images me-1"></i> Spravovat fotografie produktu</a></p><?php endif; ?>
<div class="card mb-4"><div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-2"><div><strong>Produkt</strong><div class="text-muted small">Po uložení se vrátíte na tuto stránku.</div></div><button class="btn btn-primary" type="submit"><i class="fas fa-save me-1"></i> Uložit produkt</button></div></div>
</form></div></div>
</section>
<script>(function(){var tabs=document.querySelectorAll('[data-rac-lang-tab]'),panels=document.querySelectorAll('[data-rac-lang-panel]');tabs.forEach(function(tab){tab.addEventListener('click',function(){var id=tab.getAttribute('data-rac-lang-tab');tabs.forEach(function(t){var active=t===tab;t.classList.toggle('active',active);t.setAttribute('aria-selected',active?'true':'false')});panels.forEach(function(p){p.hidden=p.getAttribute('data-rac-lang-panel')!==id})})})})();</script>
<?php include __DIR__.'/inc/footer.class.php'; ?>
