<?php
// RAC Eshop produkt – Porto/BOS24 – 2026-10-21-019.01; PHP 7.4+
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
 foreach($st->fetchAll(PDO::FETCH_ASSOC) as $r)$variants[$r['kind'].'|'.($r['language_code']??'legacy')]=$r;
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
 $validLanguages=[];foreach($languages as $entry){$validLanguages[(string)$entry['code']]=true;}
 foreach($postedVariants as $kind=>$langRows){
  if(!isset($kinds[$kind]) || !is_array($langRows)){ $errors[]='Neplatny format vydani.';continue; }
  foreach($langRows as $langCode=>$v){
   if(!is_array($v)){continue;}
   $languageCode=(string)$langCode;
   if($languageCode!=='legacy'&&!isset($validLanguages[$languageCode])){$errors[]='Neplatny jazyk vydani.';continue;}
   $key=$kind.'|'.$languageCode;
   $old=$variants[$key]??null;
   if($languageCode==='legacy'&&!$old){continue;}
   if(!isset($v['enabled']))continue;
   $label=$kinds[$kind].' / '.($languageCode==='legacy'?'Jazyk neprirazen':$languageCode);
   $price=product_decimal($v['price']??'',true,$errors,$label.' – cena');
   $sale=product_decimal($v['sale_price']??'',false,$errors,$label.' – akční cena');
   $saleStart=product_sale_datetime($v['sale_starts_at']??'',$errors,$label.' – začátek akce');
   $saleEnd=product_sale_datetime($v['sale_ends_at']??'',$errors,$label.' – konec akce');
   if($sale!==null&&($saleStart===null||$saleEnd===null))$errors[]=$label.': vyplňte začátek a konec akce.';
   if($sale===null&&($saleStart!==null||$saleEnd!==null))$errors[]=$label.': akční datum vyžaduje akční cenu.';
   if($saleStart!==null&&$saleEnd!==null&&strcmp($saleStart,$saleEnd)>=0)$errors[]=$label.': konec akce musí být později.';
   $vat=product_decimal($v['vat_rate']??'0',true,$errors,$label.' – DPH');
   if($sale!==null&&$price!==null&&(float)$sale>=(float)$price)$errors[]=$label.': akční cena musí být nižší.';
   if($vat!==null&&(float)$vat>100)$errors[]=$label.': DPH přesahuje 100 %.';
   $sku=trim((string)($v['sku']??''));$isbn=trim((string)($v['isbn']??''));$ean=trim((string)($v['ean']??''));
   if(strlen($sku)>100||strlen($isbn)>32||strlen($ean)>32)$errors[]=$label.': neplatný identifikátor.';
   $cleanVariants[$key]=['kind'=>$kind,'language_code'=>$languageCode==='legacy'?null:$languageCode,'id'=>$old['id']??null,
    'price'=>$price,'sale_price'=>$sale,'sale_starts_at'=>$saleStart,'sale_ends_at'=>$saleEnd,'vat_rate'=>$vat,'currency'=>'CZK',
    'sku'=>$sku?:null,'isbn'=>$isbn?:null,'ean'=>$ean?:null,
    'stock_quantity'=>$kind==='print'?product_optional_int($v['stock_quantity']??'',$errors,$label.' sklad'):null,
    'minimum_stock'=>$kind==='print'?product_optional_int($v['minimum_stock']??'',$errors,$label.' minimum'):null,
    'weight_grams'=>$kind==='print'?product_optional_int($v['weight_grams']??'',$errors,$label.' hmotnost'):null];
  }
 }
 if(!$errors){try{
  $pdo->beginTransaction();
  if($id){$st=$pdo->prepare('UPDATE products SET slug=?,author=?,release_date=?,category_id=?,status=?,featured=?,sort_order=? WHERE id=?');$st->execute(array($slug,$author?:null,$date?:null,$category?:null,$status,isset($posted['featured'])?1:0,$order,$id));}
  else{$st=$pdo->prepare('INSERT INTO products(slug,author,release_date,category_id,status,featured,sort_order) VALUES(?,?,?,?,?,?,?)');$st->execute(array($slug,$author?:null,$date?:null,$category?:null,$status,isset($posted['featured'])?1:0,$order));$id=(int)$pdo->lastInsertId();}
  $st=$pdo->prepare('INSERT INTO product_translations(product_id,language_id,title,short_description,description,seo_title,seo_description) VALUES(?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE title=VALUES(title),short_description=VALUES(short_description),description=VALUES(description),seo_title=VALUES(seo_title),seo_description=VALUES(seo_description)');
  foreach($translations as $lid=>$t)$st->execute(array($id,$lid,$t['title'],$t['short_description']?:null,$t['description']?:null,$t['seo_title']?:null,$t['seo_description']?:null));
   $add=$pdo->prepare('INSERT INTO product_variants(product_id,kind,language_code,sku,isbn,ean,price,sale_price,sale_starts_at,sale_ends_at,currency,vat_rate,stock_quantity,minimum_stock,weight_grams,active) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1)');
   $update=$pdo->prepare('UPDATE product_variants SET sku=?,isbn=?,ean=?,price=?,sale_price=?,sale_starts_at=?,sale_ends_at=?,currency=?,vat_rate=?,stock_quantity=?,minimum_stock=?,weight_grams=?,active=1 WHERE id=? AND product_id=?');
   foreach($cleanVariants as $key=>$v){
    if($v['id']) $update->execute([$v['sku'],$v['isbn'],$v['ean'],$v['price'],$v['sale_price'],$v['sale_starts_at'],$v['sale_ends_at'],$v['currency'],$v['vat_rate'],$v['stock_quantity'],$v['minimum_stock'],$v['weight_grams'],(int)$v['id'],$id]);
    else $add->execute([$id,$v['kind'],$v['language_code'],$v['sku'],$v['isbn'],$v['ean'],$v['price'],$v['sale_price'],$v['sale_starts_at'],$v['sale_ends_at'],$v['currency'],$v['vat_rate'],$v['stock_quantity'],$v['minimum_stock'],$v['weight_grams']]);
   }
   // Nepovolena vydani deaktivujeme, nikdy je nemazeme (kvuli starym objednavkam).
   $off=$pdo->prepare('UPDATE product_variants SET active=0 WHERE product_id=? AND id=?');
   foreach($variants as $key=>$old)if(!isset($cleanVariants[$key]))$off->execute([$id,(int)$old['id']]);
  $pdo->commit();header('Location: eshop-produkt.php?id='.$id.'&saved=1');exit;
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$errors[]='Záznam se nepodařilo uložit. Zkontrolujte unikátnost URL a SKU. Podrobnosti najdete v serverovém error logu.';error_log('RAC eshop product save: '.$e->getMessage());}}
 $product=array_merge($product,$posted);
  // Při validační chybě zobrazíme odeslané formuláře, existující ID zůstávají zachována.
  foreach($postedVariants as $kind=>$langRows)if(is_array($langRows))foreach($langRows as $langCode=>$v)if(is_array($v))$variants[$kind.'|'.$langCode]=array_merge($variants[$kind.'|'.$langCode]??[],$v);

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
<div class="row pt-2 mb-4"><div class="col-12"><a class="btn btn-primary btn-md font-weight-semibold btn-py-2 px-4 me-2 mb-2" href="eshop-produkty.php"><i class="fas fa-arrow-left me-1"></i> Zpět na produkty</a></div></div>
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
<style>#product-lang-tabs .rac-lang-complete{color:#28a745;font-weight:700;margin-left:6px}#product-lang-tabs [data-rac-complete][hidden]{display:none!important}</style>
<ul class="nav nav-tabs" role="tablist" id="product-lang-tabs"><?php foreach($languages as $index=>$lang): $lid=(int)$lang['id']; $lt=$translations[$lid]??array(); $complete=trim((string)($lt['title']??''))!=='' && trim((string)($lt['short_description']??''))!=='' && trim((string)($lt['description']??''))!==''; ?><li class="nav-item"><button type="button" class="nav-link<?= $index===0?' active':'' ?>" data-rac-lang-tab="<?= $lid ?>" aria-selected="<?= $index===0?'true':'false' ?>"><?= h($lang['native_name']) ?><span class="rac-lang-complete" data-rac-complete="<?= $lid ?>" title="Název, krátký popis a popis jsou vyplněny" aria-label="Vyplněný překlad" <?= $complete?'':'hidden' ?>>*</span></button></li><?php endforeach; ?></ul>
<div class="border border-top-0 rounded-bottom p-3 p-lg-4"><?php foreach($languages as $index=>$lang): $lid=(int)$lang['id'];$t=$translations[$lid]??array(); ?><div data-rac-lang-panel="<?= $lid ?>" <?= $index!==0?'hidden':'' ?>>
<div class="mb-3"><label class="form-label">Název <?= (int)$lang['is_default']===1?'*':'' ?></label><input class="form-control" name="translations[<?= $lid ?>][title]" maxlength="255" value="<?= h($t['title']??'') ?>"></div>
<div class="mb-3"><label class="form-label">Krátký popis (max. 300 znaků)</label><textarea class="form-control" name="translations[<?= $lid ?>][short_description]" rows="3" maxlength="300"><?= h($t['short_description']??'') ?></textarea></div>
<div class="mb-3"><label class="form-label">Popis</label><textarea class="form-control" name="translations[<?= $lid ?>][description]" rows="6"><?= h($t['description']??'') ?></textarea></div>
<div class="row g-3"><div class="col-12 col-lg-6"><label class="form-label">SEO title</label><input class="form-control" type="text" maxlength="255" name="translations[<?= $lid ?>][seo_title]" value="<?= h($t['seo_title']??'') ?>"></div><div class="col-12 col-lg-6"><label class="form-label">SEO description</label><input class="form-control" type="text" maxlength="320" name="translations[<?= $lid ?>][seo_description]" value="<?= h($t['seo_description']??'') ?>"></div></div>
</div><?php endforeach; ?></div></div></section>
<section class="card mb-4"><header class="card-header"><h2 class="card-title">Prodejná vydání podle jazyka a formátu</h2></header><div class="card-body">
<p class="text-muted">U každé kombinace zaškrtněte, zda ji nabízíte. Cena se zadává v CZK. Vydání bez určeného jazyka jsou historická; jejich jazyk přidělíme až po ověření, aby se nezměnily stávající objednávky.</p>
<?php foreach($kinds as $kind=>$kindLabel): ?>
<h3 class="mt-4 mb-3"><?= h($kindLabel) ?></h3>
<?php $editionLanguages=[];foreach($languages as $lang)$editionLanguages[(string)$lang['code']]=(string)$lang['native_name'];if(isset($variants[$kind.'|legacy']))$editionLanguages=['legacy'=>'Jazyk dosud neurčen (původní vydání)']+$editionLanguages; ?>
<div class="accordion" id="edition-group-<?= h($kind) ?>">
<?php foreach($editionLanguages as $code=>$label): $key=$kind.'|'.$code;$v=$variants[$key]??[];$enabled=$_SERVER['REQUEST_METHOD']==='POST'?isset($v['enabled']):(!empty($v['id'])&&(int)($v['active']??0)===1);$field='variants['.$kind.']['.$code.']';$sectionId='edition-'.$kind.'-'.$code; ?>
<div class="border rounded mb-2 p-3">
<div class="d-flex gap-3 justify-content-between align-items-center flex-wrap"><label class="form-check-label"><input class="form-check-input me-2" type="checkbox" name="<?= h($field) ?>[enabled]" value="1" <?= $enabled?'checked':'' ?>> <strong><?= h($label) ?></strong></label><button type="button" class="btn btn-sm btn-default" data-bs-toggle="collapse" data-bs-target="#<?= h($sectionId) ?>" aria-controls="<?= h($sectionId) ?>">Nastavení ceny a vydání</button></div>
<div class="collapse<?= $enabled?' show':'' ?>" id="<?= h($sectionId) ?>"><div class="row g-3 mt-2">
<?php foreach(['price'=>'Cena CZK','sale_price'=>'Akční cena CZK','sale_starts_at'=>'Akce od','sale_ends_at'=>'Akce do','vat_rate'=>'DPH (%)','sku'=>'SKU','isbn'=>'ISBN','ean'=>'EAN'] as $fieldName=>$fieldLabel): ?>
<div class="col-12 col-md-6 col-xl-4"><label class="form-label"><?= h($fieldLabel) ?></label><input class="form-control" <?= in_array($fieldName,['sale_starts_at','sale_ends_at'],true)?'type="datetime-local"':'type="text"' ?> name="<?= h($field) ?>[<?= h($fieldName) ?>]" value="<?= h(in_array($fieldName,['sale_starts_at','sale_ends_at'],true)?product_sale_input($v[$fieldName]??''):($v[$fieldName]??($fieldName==='vat_rate'?'0':''))) ?>"></div>
<?php endforeach; ?>
<?php if($kind==='print'):foreach(['stock_quantity'=>'Počet skladem','minimum_stock'=>'Minimální sklad','weight_grams'=>'Hmotnost v gramech'] as $fieldName=>$fieldLabel): ?>
<div class="col-12 col-md-6 col-xl-4"><label class="form-label"><?= h($fieldLabel) ?></label><input class="form-control" type="number" min="0" name="<?= h($field) ?>[<?= h($fieldName) ?>]" value="<?= h($v[$fieldName]??'') ?>"></div>
<?php endforeach;endif; ?>
</div></div></div>
<?php endforeach; ?></div>
<?php endforeach; ?></div></section>
<?php if($id): ?><p><a class="btn btn-default" href="eshop-fotografie.php?id=<?= (int)$id ?>"><i class="fas fa-images me-1"></i> Spravovat fotografie produktu</a></p><?php endif; ?>
<div class="card mb-4"><div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-2"><div><strong>Produkt</strong><div class="text-muted small">Po uložení se vrátíte na tuto stránku.</div></div><button class="btn btn-primary" type="submit"><i class="fas fa-save me-1"></i> Uložit produkt</button></div></div>
</form></div></div>
</section>
<script>(function(){var tabs=document.querySelectorAll('[data-rac-lang-tab]'),panels=document.querySelectorAll('[data-rac-lang-panel]');function complete(panel){var id=panel.getAttribute('data-rac-lang-panel'),star=document.querySelector('[data-rac-complete="'+id+'"]'),fields=['title','short_description','description'];if(!star)return;star.hidden=!fields.every(function(field){var el=panel.querySelector('[name="translations['+id+']['+field+']"]');return el&&el.value.trim()!=='';});}tabs.forEach(function(tab){tab.addEventListener('click',function(){var id=tab.getAttribute('data-rac-lang-tab');tabs.forEach(function(t){var active=t===tab;t.classList.toggle('active',active);t.setAttribute('aria-selected',active?'true':'false')});panels.forEach(function(p){p.hidden=p.getAttribute('data-rac-lang-panel')!==id})})});panels.forEach(function(panel){complete(panel);panel.querySelectorAll('input,textarea').forEach(function(field){field.addEventListener('input',function(){complete(panel)})})})})();</script>
<?php include __DIR__.'/inc/footer.class.php'; ?>
