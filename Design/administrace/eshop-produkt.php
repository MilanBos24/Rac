<?php
// verze 2026-10-08-13.30 / RAC eshop produkt: metadata, jazyky, varianty
// PHP 7.4

declare(strict_types=1);
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/auth_roles.php';
require_min_role('admin');

$id = max(0, (int)($_GET['id'] ?? 0));
$errors = array();
$statuses = array('draft'=>'Koncept','active'=>'Aktivní','hidden'=>'Skrytý','sold_out'=>'Vyprodáno');
$kinds = array('print'=>'Tištěná kniha','ebook'=>'E-kniha','audio'=>'Audiokniha');
$languages = $pdo->query('SELECT id, code, native_name, is_default FROM languages WHERE active = 1 ORDER BY is_default DESC, sort_order, id')->fetchAll(PDO::FETCH_ASSOC);
$categories = $pdo->query('SELECT c.id, COALESCE(NULLIF(ct.name,\'\'), c.code) AS name FROM product_categories c LEFT JOIN languages l ON l.is_default=1 LEFT JOIN product_category_translations ct ON ct.category_id=c.id AND ct.language_id=l.id WHERE c.active=1 ORDER BY c.sort_order,c.id')->fetchAll(PDO::FETCH_ASSOC);
$csrf = csrf_token('csrf_eshop_product');
function product_decimal($value, bool $required, array &$errors, string $label): ?string {
    $text = str_replace(',', '.', trim((string)$value));
    if ($text === '') { if ($required) { $errors[] = 'Vyplňte ' . $label . '.'; } return null; }
    if (!preg_match('/^\d{1,10}(?:\.\d{1,2})?$/', $text)) { $errors[] = 'Neplatná hodnota: ' . $label . '.'; return null; }
    return number_format((float)$text, 2, '.', '');
}
function product_sale_datetime($value, array &$errors, string $label): ?string {
    $value = trim((string)$value);
    if ($value === '') { return null; }
    $value = str_replace('T', ' ', $value);
    $dt = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $value, new DateTimeZone('Europe/Prague'));
    if (!$dt || $dt->format('Y-m-d H:i') !== $value) { $errors[] = 'Neplatné datum a čas: ' . $label . '.'; return null; }
    return $value . ':00';
}
function product_sale_input($value): string {
    if (!$value) { return ''; }
    return str_replace(' ', 'T', substr((string)$value, 0, 16));
}
function product_optional_int($value, array &$errors, string $label): ?int {
    $text = trim((string)$value);
    if ($text === '') { return null; }
    if (!ctype_digit($text) || strlen($text)>9) { $errors[]='Neplatná hodnota: '.$label.'.'; return null; }
    return (int)$text;
}
$product = array('slug'=>'','author'=>'','release_date'=>'','category_id'=>'','status'=>'draft','featured'=>0,'sort_order'=>0);
$translations = array(); $variants = array();
if ($id) {
    $stmt=$pdo->prepare('SELECT * FROM products WHERE id=?');$stmt->execute(array($id));$dbProduct=$stmt->fetch(PDO::FETCH_ASSOC);
    if (!$dbProduct) { http_response_code(404); exit('Produkt nenalezen.'); }
    $product=$dbProduct;
    $stmt=$pdo->prepare('SELECT * FROM product_translations WHERE product_id=?');$stmt->execute(array($id));
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) { $translations[(int)$r['language_id']]=$r; }
    $stmt=$pdo->prepare('SELECT * FROM product_variants WHERE product_id=?');$stmt->execute(array($id));
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) { $variants[$r['kind']]=$r; }
}
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!csrf_valid('csrf_eshop_product',(string)($_POST['csrf']??''))) { $errors[]='Neplatný bezpečnostní token.'; }
    $posted=(array)($_POST['product']??array());
    $slug=strtolower(trim((string)($posted['slug']??'')));
    if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) || strlen($slug)>190) { $errors[]='Slug musí obsahovat jen malá písmena bez diakritiky, číslice a pomlčky.'; }
    $status=(string)($posted['status']??'draft');if (!isset($statuses[$status])) {$errors[]='Neplatný stav.';}
    $author=trim((string)($posted['author']??''));if (mb_strlen($author)>190) {$errors[]='Autor může mít maximálně 190 znaků.';}
    $date=trim((string)($posted['release_date']??''));
    if ($date!=='' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date) || !($dt=DateTime::createFromFormat('!Y-m-d',$date)) || $dt->format('Y-m-d')!==$date)) { $errors[]='Neplatné datum vydání.'; }
    $category=(int)($posted['category_id']??0);
    if ($category>0) { $st=$pdo->prepare('SELECT COUNT(*) FROM product_categories WHERE id=?');$st->execute(array($category));if (!(int)$st->fetchColumn()) {$errors[]='Kategorie neexistuje.';} }
    $order=(int)($posted['sort_order']??0);
    $postedTranslations=(array)($_POST['translations']??array());
    $defaultName='';
    foreach ($languages as $lang) {
        $lid=(int)$lang['id'];$t=isset($postedTranslations[$lid]) && is_array($postedTranslations[$lid])?$postedTranslations[$lid]:array();
        $translations[$lid]=array('title'=>trim((string)($t['title']??'')),'short_description'=>trim((string)($t['short_description']??'')),'description'=>trim((string)($t['description']??'')),'seo_title'=>trim((string)($t['seo_title']??'')),'seo_description'=>trim((string)($t['seo_description']??'')));
        if ((int)$lang['is_default']===1) { $defaultName=$translations[$lid]['title']; }
        if (mb_strlen($translations[$lid]['title'])>255 || mb_strlen($translations[$lid]['short_description'])>300 || mb_strlen($translations[$lid]['seo_title'])>255 || mb_strlen($translations[$lid]['seo_description'])>320) {$errors[]='Text je příliš dlouhý pro jazyk '.$lang['native_name'].'.';}
    }
    if ($defaultName==='') { $errors[]='Název ve výchozím jazyce je povinný.'; }
    $postedVariants=(array)($_POST['variants']??array());$cleanVariants=array();
    foreach ($kinds as $kind=>$kindName) {
        $v=isset($postedVariants[$kind]) && is_array($postedVariants[$kind])?$postedVariants[$kind]:array();
        $isEnabled=isset($v['enabled']);
        if (!$isEnabled) { continue; }
        $price=product_decimal($v['price']??'',true,$errors,$kindName.' – cena');
        $sale=product_decimal($v['sale_price']??'',false,$errors,$kindName.' – akční cena');
        $saleStart=product_sale_datetime($v['sale_starts_at']??'', $errors, $kindName.' – začátek akce');
        $saleEnd=product_sale_datetime($v['sale_ends_at']??'', $errors, $kindName.' – konec akce');
        if ($sale !== null && ($saleStart === null || $saleEnd === null)) { $errors[]=$kindName.': u akční ceny vyplňte začátek i konec platnosti.'; }
        if ($sale === null && ($saleStart !== null || $saleEnd !== null)) { $errors[]=$kindName.': datum akce lze zadat pouze s akční cenou.'; }
        if ($saleStart !== null && $saleEnd !== null && strcmp($saleStart,$saleEnd) >= 0) { $errors[]=$kindName.': konec akce musí být po začátku.'; }
        $vat=product_decimal($v['vat_rate']??'0',true,$errors,$kindName.' – DPH');
        if ($sale!==null && $price!==null && (float)$sale >= (float)$price) {$errors[]=$kindName.': akční cena musí být nižší než běžná.';}
        if ($vat!==null && (float)$vat>100) {$errors[]=$kindName.': sazba DPH nesmí přesáhnout 100 %.';}
        $sku=trim((string)($v['sku']??''));$isbn=trim((string)($v['isbn']??''));$ean=trim((string)($v['ean']??''));
        $currency=strtoupper(trim((string)($v['currency']??'CZK')));
        if (!preg_match('/^[A-Z]{3}$/',$currency) || strlen($sku)>100 || strlen($isbn)>32 || strlen($ean)>32) {$errors[]=$kindName.': neplatná měna nebo identifikátor.';}
        $cleanVariants[$kind]=array('price'=>$price,'sale_price'=>$sale,'sale_starts_at'=>$saleStart,'sale_ends_at'=>$saleEnd,'vat_rate'=>$vat,'currency'=>$currency,'sku'=>$sku?:null,'isbn'=>$isbn?:null,'ean'=>$ean?:null,'stock_quantity'=>$kind==='print'?product_optional_int($v['stock_quantity']??'', $errors, 'sklad'):null,'minimum_stock'=>$kind==='print'?product_optional_int($v['minimum_stock']??'', $errors, 'minimální sklad'):null,'weight_grams'=>$kind==='print'?product_optional_int($v['weight_grams']??'', $errors, 'hmotnost'):null);
    }
    // A missing checkbox does not delete an existing variant, so attached files and orders cannot be lost.
    if (!$errors) {
        try {
            $pdo->beginTransaction();
            if ($id) {
                $st=$pdo->prepare('UPDATE products SET slug=?,author=?,release_date=?,category_id=?,status=?,featured=?,sort_order=? WHERE id=?');
                $st->execute(array($slug,$author?:null,$date?:null,$category?:null,$status,isset($posted['featured'])?1:0,$order,$id));
            } else {
                $st=$pdo->prepare('INSERT INTO products(slug,author,release_date,category_id,status,featured,sort_order) VALUES(?,?,?,?,?,?,?)');
                $st->execute(array($slug,$author?:null,$date?:null,$category?:null,$status,isset($posted['featured'])?1:0,$order));
                $id=(int)$pdo->lastInsertId();
            }
            $st=$pdo->prepare('INSERT INTO product_translations(product_id,language_id,title,short_description,description,seo_title,seo_description) VALUES(?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE title=VALUES(title),short_description=VALUES(short_description),description=VALUES(description),seo_title=VALUES(seo_title),seo_description=VALUES(seo_description)');
            foreach ($translations as $lid=>$t) {
                $st->execute(array($id,$lid,$t['title'],$t['short_description']?:null,$t['description']?:null,$t['seo_title']?:null,$t['seo_description']?:null));
            }
            $st=$pdo->prepare('INSERT INTO product_variants(product_id,kind,sku,isbn,ean,price,sale_price,sale_starts_at,sale_ends_at,currency,vat_rate,stock_quantity,minimum_stock,weight_grams,active) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,1) ON DUPLICATE KEY UPDATE sku=VALUES(sku),isbn=VALUES(isbn),ean=VALUES(ean),price=VALUES(price),sale_price=VALUES(sale_price),sale_starts_at=VALUES(sale_starts_at),sale_ends_at=VALUES(sale_ends_at),currency=VALUES(currency),vat_rate=VALUES(vat_rate),stock_quantity=VALUES(stock_quantity),minimum_stock=VALUES(minimum_stock),weight_grams=VALUES(weight_grams),active=1');
            foreach ($cleanVariants as $kind=>$v) { $st->execute(array($id,$kind,$v['sku'],$v['isbn'],$v['ean'],$v['price'],$v['sale_price'],$v['sale_starts_at'],$v['sale_ends_at'],$v['currency'],$v['vat_rate'],$v['stock_quantity'],$v['minimum_stock'],$v['weight_grams'])); }
            // For disabled variants, keep the record and its files, but mark it inactive.
            $st=$pdo->prepare('UPDATE product_variants SET active=0 WHERE product_id=? AND kind=?');
            foreach ($kinds as $kind=>$label) {if (!isset($cleanVariants[$kind])) {$st->execute(array($id,$kind));}}
            $pdo->commit();header('Location: eshop-produkt.php?id='.$id.'&saved=1');exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {$pdo->rollBack();}
            $errors[]='Záznam se nepodařilo uložit. Zkontrolujte unikátnost URL a SKU. Podrobnosti najdete v serverovém error logu.';
            error_log('RAC eshop product save: '.$e->getMessage());
        }
    }
    $product=array_merge($product,$posted);
    $variants=$postedVariants;
}
$pageTitle=$id?'Upravit produkt':'Nový produkt';
include __DIR__ . '/inc/header.class.php';
include __DIR__ . '/inc/leve-meny.class.php';
?>
<main class="admin-content"><div class="page-header"><h1><?= h($pageTitle) ?></h1><p>Základní údaje, jazykové mutace a prodejní varianty</p></div>
<p><a class="btn btn-light" href="eshop-produkty.php">← Zpět na produkty</a></p>
<?php if (isset($_GET['saved'])): ?><div class="alert alert-success">Produkt byl úspěšně uložen.</div><?php endif; ?>
<?php if ($errors): ?><div class="alert alert-danger"><ul><?php foreach ($errors as $error): ?><li><?= h($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?= h($csrf) ?>">
<section class="card"><div class="card-header"><h2>Základní informace</h2></div><div class="card-body">
<div class="grid grid-2">
<div class="form-group"><label>URL slug *</label><input class="form-control" name="product[slug]" maxlength="190" pattern="[a-z0-9]+(-[a-z0-9]+)*" required value="<?= h($product['slug']??'') ?>" placeholder="zivot-po-zivote"></div>
<div class="form-group"><label>Autor</label><input class="form-control" name="product[author]" maxlength="190" value="<?= h($product['author']??'') ?>"></div>
<div class="form-group"><label>Datum vydání</label><input class="form-control" type="date" name="product[release_date]" value="<?= h($product['release_date']??'') ?>"></div>
<div class="form-group"><label>Kategorie</label><select class="form-control" name="product[category_id]"><option value="">Bez kategorie</option><?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)($product['category_id']??0)===(int)$c['id']?'selected':'' ?>><?= h($c['name']) ?></option><?php endforeach; ?></select></div>
<div class="form-group"><label>Stav</label><select class="form-control" name="product[status]"><?php foreach($statuses as $key=>$label): ?><option value="<?= h($key) ?>" <?= ($product['status']??'draft')===$key?'selected':'' ?>><?= h($label) ?></option><?php endforeach; ?></select></div>
<div class="form-group"><label>Pořadí</label><input class="form-control" type="number" name="product[sort_order]" value="<?= (int)($product['sort_order']??0) ?>"></div>
</div><label><input type="checkbox" name="product[featured]" value="1" <?= !empty($product['featured'])?'checked':'' ?>> Doporučený produkt</label>
</div></section>
<section class="card" style="margin-top:20px;"><div class="card-header"><h2>Jazykové mutace</h2></div><div class="card-body">
<?php if (!$languages): ?><p>Nejsou aktivní jazyky.</p><?php endif; ?>
<div class="cms-language-tabs"><?php foreach ($languages as $index=>$lang): ?><button type="button" class="cms-language-tab<?= $index===0?' is-active':'' ?>" data-lang-tab="<?= (int)$lang['id'] ?>"><?= h($lang['native_name']) ?><?= (int)$lang['is_default']===1?' *':'' ?></button><?php endforeach; ?></div>
<?php foreach ($languages as $index=>$lang): $lid=(int)$lang['id'];$t=$translations[$lid]??array(); ?>
<div class="cms-language-panel<?= $index===0?' is-active':'' ?>" data-lang-panel="<?= $lid ?>">
<div class="form-group"><label>Název <?= (int)$lang['is_default']===1?'*':'' ?></label><input class="form-control" name="translations[<?= $lid ?>][title]" maxlength="255" value="<?= h($t['title']??'') ?>"></div>
<div class="form-group"><label>Krátký popis (max. 300 znaků)</label><textarea class="form-control" name="translations[<?= $lid ?>][short_description]" rows="3" maxlength="300"><?= h($t['short_description']??'') ?></textarea></div>
<div class="form-group"><label>Popis</label><textarea class="form-control" name="translations[<?= $lid ?>][description]" rows="7"><?= h($t['description']??'') ?></textarea></div>
<div class="grid grid-2"><div class="form-group"><label>SEO title</label><input class="form-control" maxlength="255" name="translations[<?= $lid ?>][seo_title]" value="<?= h($t['seo_title']??'') ?>"></div><div class="form-group"><label>SEO description</label><textarea class="form-control" rows="2" maxlength="320" name="translations[<?= $lid ?>][seo_description]"><?= h($t['seo_description']??'') ?></textarea></div></div>
</div><?php endforeach; ?>
</div></section>
<?php foreach ($kinds as $kind=>$kindLabel): $v=$variants[$kind]??array(); $enabled = $_SERVER['REQUEST_METHOD']==='POST' ? isset($v['enabled']) : (!empty($v['id']) && (int)($v['active']??0)===1); ?>
<section class="card" style="margin-top:20px;"><div class="card-header"><h2><?= h($kindLabel) ?></h2></div><div class="card-body">
<label><input type="checkbox" name="variants[<?= h($kind) ?>][enabled]" value="1" <?= $enabled?'checked':'' ?>> Nabízet tuto variantu</label>
<div class="grid grid-3" style="margin-top:16px;">
<div class="form-group"><label>Cena</label><input class="form-control" name="variants[<?= h($kind) ?>][price]" inputmode="decimal" value="<?= h($v['price']??'') ?>"></div>
<div class="form-group"><label>Akční cena</label><input class="form-control" name="variants[<?= h($kind) ?>][sale_price]" inputmode="decimal" value="<?= h($v['sale_price']??'') ?>"></div>
<div class="form-group"><label>Akce od (datum a čas)</label><input class="form-control" type="datetime-local" name="variants[<?= h($kind) ?>][sale_starts_at]" value="<?= h(product_sale_input($v['sale_starts_at']??'')) ?>"></div>
<div class="form-group"><label>Akce do (datum a čas)</label><input class="form-control" type="datetime-local" name="variants[<?= h($kind) ?>][sale_ends_at]" value="<?= h(product_sale_input($v['sale_ends_at']??'')) ?>"></div>
<div class="form-group"><label>Měna</label><input class="form-control" name="variants[<?= h($kind) ?>][currency]" maxlength="3" value="<?= h($v['currency']??'CZK') ?>"></div>
<div class="form-group"><label>Sazba DPH (%)</label><input class="form-control" name="variants[<?= h($kind) ?>][vat_rate]" inputmode="decimal" value="<?= h($v['vat_rate']??'0') ?>"></div>
<div class="form-group"><label>SKU</label><input class="form-control" name="variants[<?= h($kind) ?>][sku]" maxlength="100" value="<?= h($v['sku']??'') ?>"></div>
<div class="form-group"><label>ISBN</label><input class="form-control" name="variants[<?= h($kind) ?>][isbn]" maxlength="32" value="<?= h($v['isbn']??'') ?>"></div>
<div class="form-group"><label>EAN</label><input class="form-control" name="variants[<?= h($kind) ?>][ean]" maxlength="32" value="<?= h($v['ean']??'') ?>"></div>
<?php if ($kind==='print'): ?><div class="form-group"><label>Počet skladem</label><input class="form-control" type="number" min="0" name="variants[print][stock_quantity]" value="<?= h($v['stock_quantity']??'') ?>"></div>
<div class="form-group"><label>Minimální zásoba</label><input class="form-control" type="number" min="0" name="variants[print][minimum_stock]" value="<?= h($v['minimum_stock']??'') ?>"></div>
<div class="form-group"><label>Hmotnost (g)</label><input class="form-control" type="number" min="0" name="variants[print][weight_grams]" value="<?= h($v['weight_grams']??'') ?>"></div><?php endif; ?>
</div></div></section>
<?php endforeach; ?>
<?php if ($id): ?><p style="margin-top:20px;"><a class="btn btn-light" href="eshop-fotografie.php?id=<?= (int)$id ?>">Spravovat fotografie produktu →</a></p><?php endif; ?>
<div class="cms-savebar"><div><strong>Produkt</strong><span>Po uložení se vrátíte na tuto stránku.</span></div><button class="btn btn-primary" type="submit">Uložit produkt</button></div>
</form></main>
<script>(function(){var tabs=document.querySelectorAll('[data-lang-tab]'),panels=document.querySelectorAll('[data-lang-panel]');tabs.forEach(function(tab){tab.addEventListener('click',function(){var id=tab.getAttribute('data-lang-tab');tabs.forEach(function(t){t.classList.toggle('is-active',t===tab)});panels.forEach(function(p){p.classList.toggle('is-active',p.getAttribute('data-lang-panel')===id)})})})})();</script>
<?php include __DIR__ . '/inc/footer.class.php'; ?>
