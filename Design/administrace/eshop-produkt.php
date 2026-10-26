<?php
// RAC 2026-10-23-SALE-02 / prepinatele akci s dobrovolnymi terminy / PHP 7.4+ / ulozeni s navratem na tab a sbalovaci edice
// Vychazi z RAC 2026-10-22-01: nezavisle jazyky vydani, spolecne ceny a akce.
// RAC Eshop produkt – Porto/BOS24 – 2026-10-21-EDICE-01; PHP 7.4+; společná CZK cena dle formátu
// Původní existující rozdílné ceny jsou nabídnuty jako individuální výjimky.
declare(strict_types=1);
require_once __DIR__.'/config/session.php';
require_once __DIR__.'/config/db.php';
require_once __DIR__.'/config/auth.php';
require_once __DIR__.'/config/auth_roles.php';
require_min_role('admin');
$id=max(0,(int)($_GET['id']??0));
$errors=[];
$statuses=['draft'=>'Koncept','active'=>'Aktivní','hidden'=>'Skrytý','sold_out'=>'Vyprodáno'];
$kinds=['print'=>'Tištěná kniha','ebook'=>'E-kniha','audio'=>'Audiokniha'];
$languages=$pdo->query('SELECT id,code,native_name,is_default FROM languages WHERE active=1 ORDER BY is_default DESC,sort_order,id')->fetchAll(PDO::FETCH_ASSOC);
// Katalog jazyku knih je nezavisly na jazykovych mutacich webu (SQL 022).
try {
 $editionLanguageRows=$pdo->query('SELECT code,native_name FROM book_edition_languages WHERE active=1 ORDER BY sort_order,native_name,code')->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $ex) {
 http_response_code(503);exit('Chybí SQL migrace 022 – jazyky vydání knih. Nejprve ji spusťte v administraci SQL.');
}
// Migrace 023 je nutna pro oddeleny stav akce, jinak by deaktivace rusila cenu.
try {
 $racSaleColumn=$pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='product_variants' AND column_name='sale_enabled'");
 if((int)$racSaleColumn->fetchColumn()!==1) throw new RuntimeException('missing sale_enabled');
} catch(Throwable $ex) {
 http_response_code(503);exit('Chybí SQL migrace 023 – přepínač akčních cen. Spusťte ji nejprve v administraci SQL.');
}
$editionLanguages=[];
foreach($editionLanguageRows as $lang) $editionLanguages[(string)$lang['code']]=(string)$lang['native_name'];
$categories=$pdo->query("SELECT c.id,COALESCE(NULLIF(ct.name,''),c.code) AS name FROM product_categories c LEFT JOIN languages l ON l.is_default=1 LEFT JOIN product_category_translations ct ON ct.category_id=c.id AND ct.language_id=l.id WHERE c.active=1 ORDER BY c.sort_order,c.id")->fetchAll(PDO::FETCH_ASSOC);
$csrf=csrf_token('csrf_eshop_product');
function product_decimal($value,bool $required,array &$errors,string $label):?string {
 $text=str_replace(',','.',trim((string)$value));
 if($text===''){if($required)$errors[]='Vyplňte '.$label.'.';return null;}
 if(!preg_match('/^\d{1,10}(?:\.\d{1,2})?$/D',$text)){$errors[]='Neplatná hodnota: '.$label.'.';return null;}
 return number_format((float)$text,2,'.','');
}
function product_sale_datetime($value,array &$errors,string $label):?string {
 $value=str_replace('T',' ',trim((string)$value));if($value==='')return null;
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
function racPriceEqual($a,$b):bool {return $a!==null&&$b!==null && number_format((float)$a,2,'.','')===number_format((float)$b,2,'.','');}
$product=['slug'=>'','author'=>'','release_date'=>'','category_id'=>'','status'=>'draft','featured'=>0,'sort_order'=>0];
$translations=[];$variants=[];
if($id){
 $st=$pdo->prepare('SELECT * FROM products WHERE id=?');$st->execute([$id]);$p=$st->fetch(PDO::FETCH_ASSOC);
 if(!$p){http_response_code(404);exit('Produkt nenalezen.');}$product=$p;
 $st=$pdo->prepare('SELECT * FROM product_translations WHERE product_id=?');$st->execute([$id]);
 foreach($st->fetchAll(PDO::FETCH_ASSOC) as $r)$translations[(int)$r['language_id']]=$r;
 $st=$pdo->prepare('SELECT * FROM product_variants WHERE product_id=?');$st->execute([$id]);
 foreach($st->fetchAll(PDO::FETCH_ASSOC) as $r){
  // Staré záznamy bez jazyka zůstávají mimo formulář i mimo možnost reaktivace.
  if(!isset($r['language_code']) || trim((string)$r['language_code'])==='')continue;
  $variants[$r['kind'].'|'.$r['language_code']]=$r;
 }
}
// Jazyky jiz ulozenych vydani zachovame i pri deaktivaci jazyka v katalogu.
foreach($variants as $oldVariant) {
 $code=(string)($oldVariant['language_code']??'');
 if($code!=='' && !isset($editionLanguages[$code])) $editionLanguages[$code]=strtoupper($code).' (dříve použito)';
}
// RAC 021: vydání lze evidovat jako neaktivní, soubor je nutný k aktivaci digitálních variant.
$racPublicationReady=false;$racPublished=[];
try {
 $st=$pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='product_files' AND column_name='is_published'");
 $racPublicationReady=(int)$st->fetchColumn()===1;
 if($id && $racPublicationReady){
  $st=$pdo->prepare("SELECT f.variant_id,f.storage_key FROM product_files f JOIN product_variants v ON v.id=f.variant_id WHERE v.product_id=? AND f.purpose='paid' AND f.is_published=1");
  $st->execute([$id]);
  require_once __DIR__.'/config/digital_storage.php';
  foreach($st->fetchAll(PDO::FETCH_ASSOC) as $file){
   $path=rac_private_storage_dir().DIRECTORY_SEPARATOR.$file['storage_key'];
   if(is_file($path)&&is_readable($path))$racPublished[(int)$file['variant_id']]=true;
  }
 }
}catch(Throwable $e){error_log('RAC edition publication check: '.$e->getMessage());}
// RAC UX-03: Strucny stav souboru v hlavicce kazdeho jazykoveho vydani.
// Stav se nacita jednou za cely produkt, pri nove variante je prazdny.
$racFileOverview=[];
$racFileOverviewReady=false;
if ($id && $racPublicationReady) {
 try {
  require_once __DIR__.'/config/digital_storage.php';
  $racStorageRoot=rtrim((string)rac_private_storage_dir(),DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
  $racFs=$pdo->prepare("SELECT v.id AS variant_id,f.media_type,f.purpose,f.is_published,f.storage_key
    FROM product_variants v JOIN product_files f ON f.variant_id=v.id
    WHERE v.product_id=? AND f.purpose IN ('paid','preview')
    ORDER BY f.id DESC");
  $racFs->execute([$id]);
  foreach($racFs->fetchAll(PDO::FETCH_ASSOC) as $racF) {
   $racVariantId=(int)$racF['variant_id'];
   $racMedia=strtolower((string)$racF['media_type']);
   $racPurpose=(string)$racF['purpose'];
   if(!in_array($racMedia,['pdf','epub','mp3','m4a'],true))continue;
   $racKey=(string)$racF['storage_key'];
   if($racKey==='' || strpos($racKey,'/')!==false || strpos($racKey,'\\')!==false)continue;
   if(!is_file($racStorageRoot.$racKey) || !is_readable($racStorageRoot.$racKey))continue;
   if(!isset($racFileOverview[$racVariantId]))$racFileOverview[$racVariantId]=[];
   if(!isset($racFileOverview[$racVariantId][$racPurpose]))$racFileOverview[$racVariantId][$racPurpose]=[];
   $racStatus=((int)$racF['is_published']===1)?'published':'uploaded';
   // Prednost publikovanemu souboru, i pokud je nahrana novejsi nepublikovana verze.
   $racOldStatus=$racFileOverview[$racVariantId][$racPurpose][$racMedia]??'';
   if($racOldStatus!=='published')$racFileOverview[$racVariantId][$racPurpose][$racMedia]=$racStatus;
  }
  $racFileOverviewReady=true;
 } catch(Throwable $racFileException) {
  error_log('RAC product file overview: '.$racFileException->getMessage());
 }
}
// PHP 7.4: volani pouze pro aktualni (jiz ulozenou) variantu.
function racEditionFileBadge(string $format,string $status):string {
 $name=strtoupper($format);
 if($status==='published')return '<span class="badge bg-success" title="Publikovany soubor">'.$name.' ✓</span>';
 if($status==='uploaded')return '<span class="badge bg-warning text-dark" title="Soubor nahran, neni publikovan">'.$name.' · nepublikováno</span>';
 return '<span class="badge bg-light text-secondary border" title="Soubor neni nahran">'.$name.' · chybí</span>';
}
$kindBasePrices=[];$kindSales=[];
foreach($kinds as $kind=>$kindLabel){
 $pick=null;
 if(isset($variants[$kind.'|cs'])&&(int)($variants[$kind.'|cs']['active']??0)===1)$pick=$variants[$kind.'|cs'];
 if($pick===null)foreach($variants as $v){if(($v['kind']??'')===$kind && (int)($v['active']??0)===1){$pick=$v;break;}}
 $kindBasePrices[$kind]=$pick!==null?(string)$pick['price']:'';
 // Stavajici akci nacteme z aktivniho ceskeho vydani, pripadne prvniho aktivniho.
 // Pri nasledujicim ulozeni bude pro kazde vydani daneho formatu sjednocena.
 $kindSales[$kind]=[
  'sale_price'=>$pick!==null?(string)($pick['sale_price']??''):'',
  'sale_enabled'=>$pick!==null?(int)($pick['sale_enabled']??0):0,
  'sale_starts_at'=>$pick!==null?(string)($pick['sale_starts_at']??''):'',
  'sale_ends_at'=>$pick!==null?(string)($pick['sale_ends_at']??''):''
 ];
}
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
 if(!csrf_valid('csrf_eshop_product',(string)($_POST['csrf']??'')))$errors[]='Neplatný bezpečnostní token.';
 $posted=(array)($_POST['product']??[]);$slug=strtolower(trim((string)($posted['slug']??'')));
 if(!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',$slug)||strlen($slug)>190)$errors[]='Slug musí obsahovat jen malá písmena bez diakritiky, číslice a pomlčky.';
 $status=(string)($posted['status']??'draft');if(!isset($statuses[$status]))$errors[]='Neplatný stav.';
 $author=trim((string)($posted['author']??''));if(mb_strlen($author)>190)$errors[]='Autor může mít maximálně 190 znaků.';
 $date=trim((string)($posted['release_date']??''));
 if($date!==''&&(!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$date)||!($dt=DateTime::createFromFormat('!Y-m-d',$date))||$dt->format('Y-m-d')!==$date))$errors[]='Neplatné datum vydání.';
 $category=(int)($posted['category_id']??0);
 if($category>0){$st=$pdo->prepare('SELECT COUNT(*) FROM product_categories WHERE id=?');$st->execute([$category]);if(!(int)$st->fetchColumn())$errors[]='Kategorie neexistuje.';}
 $sortOrder=(int)($posted['sort_order']??0);
 $postedTranslations=(array)($_POST['translations']??[]);$defaultName='';
 foreach($languages as $lang){
  $lid=(int)$lang['id'];$t=isset($postedTranslations[$lid])&&is_array($postedTranslations[$lid])?$postedTranslations[$lid]:[];
  $translations[$lid]=['title'=>trim((string)($t['title']??'')),'short_description'=>trim((string)($t['short_description']??'')),'description'=>trim((string)($t['description']??'')),'seo_title'=>trim((string)($t['seo_title']??'')),'seo_description'=>trim((string)($t['seo_description']??''))];
  if((int)$lang['is_default']===1)$defaultName=$translations[$lid]['title'];
  if(mb_strlen($translations[$lid]['title'])>255||mb_strlen($translations[$lid]['short_description'])>300||mb_strlen($translations[$lid]['seo_title'])>255||mb_strlen($translations[$lid]['seo_description'])>320)$errors[]='Text je příliš dlouhý pro jazyk '.$lang['native_name'].'.';
 }
 if($defaultName==='')$errors[]='Název ve výchozím jazyce je povinný.';
 $postedVariants=(array)($_POST['variants']??[]);$postedPrices=(array)($_POST['kind_prices']??[]);$cleanVariants=[];
 $validLanguages=[];foreach($editionLanguages as $code=>$label)$validLanguages[(string)$code]=true;
 foreach($kinds as $kind=>$kindLabel){
  $input=isset($postedPrices[$kind])&&is_array($postedPrices[$kind])?$postedPrices[$kind]:[];
  $kindBasePrices[$kind]=trim((string)($input['price']??''));
  $kindSales[$kind]=[
   'sale_price'=>trim((string)($input['sale_price']??'')),
   'sale_enabled'=>((string)($input['sale_enabled']??'0'))==='1'?1:0,
   'sale_starts_at'=>trim((string)($input['sale_starts_at']??'')),
   'sale_ends_at'=>trim((string)($input['sale_ends_at']??''))
  ];
  $rows=isset($postedVariants[$kind])&&is_array($postedVariants[$kind])?$postedVariants[$kind]:[];
  foreach($rows as $langCode=>$v){
   if(!is_array($v))continue;
   $languageCode=(string)$langCode;
   if(!isset($validLanguages[$languageCode])){$errors[]='Neplatný jazyk vydání.';continue;}
   $key=$kind.'|'.$languageCode;$old=$variants[$key]??null;
   if(!isset($v['included']))continue;
   $label=$kindLabel.' / '.$languageCode;
   $own=isset($v['override_price']);
   // Pripravit vydani != nabizet zakaznikum. Databaze vyzaduje ciselne price,
   // proto rozpracovana neprodejna varianta dostane internich 0.00.
   $sellRequested=isset($v['enabled']);
   $priceRaw=trim((string)($own?($v['price']??''):$kindBasePrices[$kind]));
   // Pokud jeste neni vyplnena spolecna cena, nemenit platnou stavajici cenu vydani.
   if($priceRaw==='' && !$own && $old!==null && (float)($old['price']??0)>0){
    $priceRaw=(string)$old['price'];
   }
   $price=product_decimal($priceRaw,$sellRequested,$errors,$label.' – cena CZK');
   if($price===null && $priceRaw==='')$price='0.00';
   if($sellRequested && $price!==null && (float)$price<=0)$errors[]=$label.': pro nabízení zákazníkům nastavte cenu vyšší než 0 CZK.';
   $sale=product_decimal($kindSales[$kind]['sale_price'],false,$errors,$label.' – společná akční cena');
   $saleStart=product_sale_datetime($kindSales[$kind]['sale_starts_at'],$errors,$label.' – společný začátek akce');
   $saleEnd=product_sale_datetime($kindSales[$kind]['sale_ends_at'],$errors,$label.' – společný konec akce');
   // Smazani akcni ceny automaticky odstrani i datumy a akci vypne.
   // Datum od/do je volitelne: oboji prazdne = akce plati ihned bez omezeni.
   if($sale===null){$saleStart=null;$saleEnd=null;$kindSales[$kind]['sale_enabled']=0;}
   if($saleStart!==null&&$saleEnd!==null&&strcmp($saleStart,$saleEnd)>=0)$errors[]=$label.': konec akce musí být později než začátek.';
   // Nepovolit tiche ulozeni zapnute, ale jiz expirovane akce.
   if($kindSales[$kind]['sale_enabled'] && $saleEnd!==null && strtotime($saleEnd)<=time()){
    $expiredMessage=$kindLabel.': akce má již uplynulý konec. Vymažte termíny (spuštění ihned), nebo nastavte nové datum.';
    if(!in_array($expiredMessage,$errors,true))$errors[]=$expiredMessage;
   }
   if($kindSales[$kind]['sale_enabled'] && ($sale===null || (float)$sale<=0)) {
    $message=$kindLabel.': před aktivací akce zadejte kladnou společnou akční cenu.';
    if(!in_array($message,$errors,true))$errors[]=$message;
   }
   $vat=product_decimal($v['vat_rate']??'0',true,$errors,$label.' – DPH');
   if($kindSales[$kind]['sale_enabled']&&$sale!==null&&$price!==null&&(float)$price>0&&(float)$sale>=(float)$price)$errors[]=$label.': akční cena musí být nižší.';
   if($vat!==null&&(float)$vat>100)$errors[]=$label.': DPH přesahuje 100 %.';
   $sku=trim((string)($v['sku']??''));$isbn=trim((string)($v['isbn']??''));$ean=trim((string)($v['ean']??''));
   if(strlen($sku)>100||strlen($isbn)>32||strlen($ean)>32)$errors[]=$label.': neplatný identifikátor.';
   if($kind!=='print' && isset($v['enabled'])){
    if(!$racPublicationReady)$errors[]=$label.': nejprve je nutné provést SQL migraci 021.';
    elseif(!$old || empty($racPublished[(int)$old['id']]))$errors[]=$label.': nelze aktivovat prodej bez publikovaného plného souboru. Nahrajte jej po uložení vydání.';
   }
   $cleanVariants[$key]=['kind'=>$kind,'language_code'=>$languageCode,'id'=>$old['id']??null,
    'active'=>isset($v['enabled'])?1:0,'price'=>$price,'sale_price'=>$sale,'sale_enabled'=>$kindSales[$kind]['sale_enabled'],'sale_starts_at'=>$saleStart,'sale_ends_at'=>$saleEnd,'vat_rate'=>$vat,'currency'=>'CZK',
    'sku'=>$sku?:null,'isbn'=>$isbn?:null,'ean'=>$ean?:null,
    'stock_quantity'=>$kind==='print'?product_optional_int($v['stock_quantity']??'',$errors,$label.' sklad'):null,
    'minimum_stock'=>$kind==='print'?product_optional_int($v['minimum_stock']??'',$errors,$label.' minimum'):null,
    'weight_grams'=>$kind==='print'?product_optional_int($v['weight_grams']??'',$errors,$label.' hmotnost'):null];
  }
 }
 if(!$errors){try{
  $pdo->beginTransaction();
  if($id){$st=$pdo->prepare('UPDATE products SET slug=?,author=?,release_date=?,category_id=?,status=?,featured=?,sort_order=? WHERE id=?');$st->execute([$slug,$author?:null,$date?:null,$category?:null,$status,isset($posted['featured'])?1:0,$sortOrder,$id]);}
  else{$st=$pdo->prepare('INSERT INTO products(slug,author,release_date,category_id,status,featured,sort_order) VALUES(?,?,?,?,?,?,?)');$st->execute([$slug,$author?:null,$date?:null,$category?:null,$status,isset($posted['featured'])?1:0,$sortOrder]);$id=(int)$pdo->lastInsertId();}
  $st=$pdo->prepare('INSERT INTO product_translations(product_id,language_id,title,short_description,description,seo_title,seo_description) VALUES(?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE title=VALUES(title),short_description=VALUES(short_description),description=VALUES(description),seo_title=VALUES(seo_title),seo_description=VALUES(seo_description)');
  foreach($translations as $lid=>$t)$st->execute([$id,$lid,$t['title'],$t['short_description']?:null,$t['description']?:null,$t['seo_title']?:null,$t['seo_description']?:null]);
  $add=$pdo->prepare('INSERT INTO product_variants(product_id,kind,language_code,sku,isbn,ean,price,sale_price,sale_starts_at,sale_ends_at,currency,vat_rate,stock_quantity,minimum_stock,weight_grams,active,sale_enabled) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
  $update=$pdo->prepare('UPDATE product_variants SET sku=?,isbn=?,ean=?,price=?,sale_price=?,sale_starts_at=?,sale_ends_at=?,currency=?,vat_rate=?,stock_quantity=?,minimum_stock=?,weight_grams=?,active=?,sale_enabled=? WHERE id=? AND product_id=?');
  foreach($cleanVariants as $v){
   if($v['id'])$update->execute([$v['sku'],$v['isbn'],$v['ean'],$v['price'],$v['sale_price'],$v['sale_starts_at'],$v['sale_ends_at'],$v['currency'],$v['vat_rate'],$v['stock_quantity'],$v['minimum_stock'],$v['weight_grams'],$v['active'],$v['sale_enabled'],(int)$v['id'],$id]);
   else $add->execute([$id,$v['kind'],$v['language_code'],$v['sku'],$v['isbn'],$v['ean'],$v['price'],$v['sale_price'],$v['sale_starts_at'],$v['sale_ends_at'],$v['currency'],$v['vat_rate'],$v['stock_quantity'],$v['minimum_stock'],$v['weight_grams'],$v['active'],$v['sale_enabled']]);
  }
  // Neurčená vydání nesmí být znovu prodejná (ani pokud existovala před migrací).
  $pdo->prepare("UPDATE product_variants SET active=0 WHERE product_id=? AND (language_code IS NULL OR language_code='')")->execute([$id]);
  // Staré varianty se neodstraňují kvůli vazbám na objednávky.
  $off=$pdo->prepare('UPDATE product_variants SET active=0 WHERE product_id=? AND id=?');
  foreach($variants as $key=>$old)if(!isset($cleanVariants[$key]))$off->execute([$id,(int)$old['id']]);
  $pdo->commit();
  // Navrat na naposledy upravovanou oblast; hodnoty z POST nikdy nepouzivat jako URL bez overeni.
  $racContext=(string)($_POST['rac_return_area']??'');
  $racFocus=(string)($_POST['rac_return_focus']??'');
  $racTab=(string)($_POST['rac_return_tab']??'');
  $racParams=['id'=>$id,'saved'=>1];
  if(in_array($racContext,['basic','translation','edition'],true)) $racParams['area']=$racContext;
  if(in_array($racTab,['print','ebook','audio'],true)) $racParams['tab']=$racTab;
  if(preg_match('/^(?:edition-(?:print|ebook|audio)-[a-zA-Z0-9_-]{2,35}|rac-kind-(?:price|sale|sale-from|sale-to)-(?:print|ebook|audio)|rac-translation-[0-9]{1,9}|rac-product-basic)$/D',$racFocus)) $racParams['focus']=$racFocus;
  header('Location: eshop-produkt.php?'.http_build_query($racParams,'','&',PHP_QUERY_RFC3986));exit;
 }catch(Throwable $ex){if($pdo->inTransaction())$pdo->rollBack();$errors[]='Záznam se nepodařilo uložit. Podrobnosti najdete v serverovém error logu.';error_log('RAC eshop product save: '.$ex->getMessage());}}
 $product=array_merge($product,$posted);
 // Po neplatném formuláři zachováme všechna pole včetně nastavení výjimek.
 foreach($postedVariants as $kind=>$langRows){
  if(!is_array($langRows))continue;
  foreach($langRows as $code=>$v){
   if(!is_array($v))continue;
   $key=$kind.'|'.$code;
   $old=$variants[$key]??[];
   // V POST neprijdou nezaskrtnute checkboxy. Nesmime obnovit puvodni stav z databaze.
   unset($old['included'],$old['enabled'],$old['override_price']);
   $variants[$key]=array_merge($old,$v);
  }
 }
}
$pageTitle=$id?'Upravit produkt':'Nový produkt';
$racUsePortoLayout=true;$base='/administrace';$assets=$base;$title=$pageTitle.' | RAC';
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
<form method="post" id="rac-product-editor"><input type="hidden" name="csrf" value="<?= h($csrf) ?>">
<input type="hidden" id="rac-return-area" name="rac_return_area" value="<?= h((string)($_POST['rac_return_area']??'')) ?>">
<input type="hidden" id="rac-return-tab" name="rac_return_tab" value="<?= h((string)($_POST['rac_return_tab']??'')) ?>">
<input type="hidden" id="rac-return-focus" name="rac_return_focus" value="<?= h((string)($_POST['rac_return_focus']??'')) ?>">
<section class="card mb-4" id="rac-product-basic"><header class="card-header"><h2 class="card-title">Základní informace</h2></header><div class="card-body"><div class="row g-3">
<div class="col-12 col-lg-6"><label class="form-label">URL slug *</label><input class="form-control" name="product[slug]" maxlength="190" pattern="[a-z0-9]+(-[a-z0-9]+)*" required value="<?= h($product['slug']??'') ?>" placeholder="zivot-po-zivote"></div>
<div class="col-12 col-lg-6"><label class="form-label">Autor</label><input class="form-control" name="product[author]" maxlength="190" value="<?= h($product['author']??'') ?>"></div>
<div class="col-12 col-md-6 col-lg-3"><label class="form-label">Datum vydání</label><input class="form-control" type="date" name="product[release_date]" value="<?= h($product['release_date']??'') ?>"></div>
<div class="col-12 col-md-6 col-lg-3"><label class="form-label">Kategorie</label><select class="form-select" name="product[category_id]"><option value="">Bez kategorie</option><?php foreach($categories as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)($product['category_id']??0)===(int)$c['id']?'selected':'' ?>><?= h($c['name']) ?></option><?php endforeach; ?></select></div>
<div class="col-12 col-md-6 col-lg-3"><label class="form-label">Stav</label><select class="form-select" name="product[status]"><?php foreach($statuses as $key=>$label): ?><option value="<?= h($key) ?>" <?= ($product['status']??'draft')===$key?'selected':'' ?>><?= h($label) ?></option><?php endforeach; ?></select></div>
<div class="col-12 col-md-6 col-lg-3"><label class="form-label">Pořadí</label><input class="form-control" type="number" name="product[sort_order]" value="<?= (int)($product['sort_order']??0) ?>"></div>
<div class="col-12"><div class="form-check"><input class="form-check-input" id="product-featured" type="checkbox" name="product[featured]" value="1" <?= !empty($product['featured'])?'checked':'' ?>><label class="form-check-label" for="product-featured">Doporučený produkt</label></div></div>
</div></div></section>
<section class="card mb-4" id="rac-product-translations"><header class="card-header"><h2 class="card-title">Jazykové mutace</h2></header><div class="card-body">
<?php if(!$languages): ?><div class="alert alert-warning">Nejsou aktivní jazyky.</div><?php endif; ?>
<style>#product-lang-tabs .rac-lang-complete{color:#28a745;font-weight:700;margin-left:6px}#product-lang-tabs [data-rac-complete][hidden]{display:none!important}.rac-individual-price[hidden]{display:none!important}</style>
<ul class="nav nav-tabs" role="tablist" id="product-lang-tabs"><?php foreach($languages as $index=>$lang): $lid=(int)$lang['id']; $lt=$translations[$lid]??[]; $complete=trim((string)($lt['title']??''))!=='' && trim((string)($lt['short_description']??''))!=='' && trim((string)($lt['description']??''))!==''; ?><li class="nav-item"><button type="button" class="nav-link<?= $index===0?' active':'' ?>" data-rac-lang-tab="<?= $lid ?>" aria-selected="<?= $index===0?'true':'false' ?>"><?= h($lang['native_name']) ?><span class="rac-lang-complete" data-rac-complete="<?= $lid ?>" title="Název, krátký popis a popis jsou vyplněny" aria-label="Vyplněný překlad" <?= $complete?'':'hidden' ?>>*</span></button></li><?php endforeach; ?></ul>
<div class="border border-top-0 rounded-bottom p-3 p-lg-4"><?php foreach($languages as $index=>$lang): $lid=(int)$lang['id'];$t=$translations[$lid]??[]; ?><div id="rac-translation-<?= $lid ?>" data-rac-lang-panel="<?= $lid ?>" <?= $index!==0?'hidden':'' ?>>
<div class="mb-3"><label class="form-label">Název <?= (int)$lang['is_default']===1?'*':'' ?></label><input class="form-control" name="translations[<?= $lid ?>][title]" maxlength="255" value="<?= h($t['title']??'') ?>"></div>
<div class="mb-3"><label class="form-label">Krátký popis (max. 300 znaků)</label><textarea class="form-control" name="translations[<?= $lid ?>][short_description]" rows="3" maxlength="300"><?= h($t['short_description']??'') ?></textarea></div>
<div class="mb-3"><label class="form-label">Popis</label><textarea class="form-control" name="translations[<?= $lid ?>][description]" rows="6"><?= h($t['description']??'') ?></textarea></div>
<div class="row g-3"><div class="col-12 col-lg-6"><label class="form-label">SEO title</label><input class="form-control" type="text" maxlength="255" name="translations[<?= $lid ?>][seo_title]" value="<?= h($t['seo_title']??'') ?>"></div><div class="col-12 col-lg-6"><label class="form-label">SEO description</label><input class="form-control" type="text" maxlength="320" name="translations[<?= $lid ?>][seo_description]" value="<?= h($t['seo_description']??'') ?>"></div></div>
</div><?php endforeach; ?></div></div></section>
<section class="card mb-4" id="rac-product-editions"><header class="card-header"><h2 class="card-title">Prodejná vydání podle jazyka a formátu</h2></header><div class="card-body">
<p class="text-muted">Zaškrtnutí „Připravit vydání“ umožňuje vydání uložit i bez ceny a nahrát k němu soubory. Rozpracované vydání není nabízeno zákazníkům. Cenu vyšší než 0 CZK zadejte až před zapnutím „Nabízet zákazníkům“. Dříve založená neaktivní vydání zůstávají uložená a lze je znovu aktivovat. Digitální vydání lze nabízet až po nahrání a publikování plného souboru. Běžnou a akční cenu vyplňte pouze jednou pro každý formát; datumy jsou volitelné. Akci lze kdykoliv zapnout či vypnout bez vymazání ceny. Akční cena platí pro všechna jazyková vydání daného formátu. Individuální běžnou cenu lze nastavit u jednotlivých jazyků. Existující rozdílné ceny jsou automaticky označené jako výjimky. Měna zatím zůstává CZK.</p>
<div class="tabs mt-3" id="rac-edition-tabs">
<ul class="nav nav-tabs tabs-primary" role="tablist" aria-label="Formáty vydání knihy">
<?php foreach($kinds as $tabIndex=>$tabLabel): ?>
<li class="nav-item" role="presentation"><button type="button" class="nav-link<?= $tabIndex==='print'?' active':'' ?>" id="rac-edition-tab-<?= h($tabIndex) ?>" role="tab" data-rac-edition-tab="<?= h($tabIndex) ?>" aria-controls="rac-edition-panel-<?= h($tabIndex) ?>" aria-selected="<?= $tabIndex==='print'?'true':'false' ?>"><?= h($tabLabel) ?></button></li>
<?php endforeach; ?>
</ul>
<div class="tab-content border border-top-0 rounded-bottom p-3 p-lg-4">
<?php foreach($kinds as $kind=>$kindLabel): ?>
<div id="rac-edition-panel-<?= h($kind) ?>" class="tab-pane fade<?= $kind==='print'?' show active':'' ?>" role="tabpanel" aria-labelledby="rac-edition-tab-<?= h($kind) ?>" data-rac-edition-panel="<?= h($kind) ?>" <?= $kind==='print'?'':'hidden' ?>>
<h3 class="mb-3"><?= h($kindLabel) ?></h3>
<div class="row g-3 mb-3">
<div class="col-12 col-md-6 col-xl-3"><label class="form-label" for="rac-kind-price-<?= h($kind) ?>">Společná cena <?= h($kindLabel) ?> (CZK)</label><input class="form-control" id="rac-kind-price-<?= h($kind) ?>" type="text" inputmode="decimal" name="kind_prices[<?= h($kind) ?>][price]" value="<?= h($kindBasePrices[$kind]??'') ?>" placeholder="Např. 490.00"><small class="text-muted">Pro vydání bez vlastní ceny. Při pouhé přípravě vydání může zůstat prázdné.</small></div>
<div class="col-12 col-md-6 col-xl-3"><label class="form-label" for="rac-kind-sale-<?= h($kind) ?>">Společná akční cena (CZK)</label><input class="form-control" id="rac-kind-sale-<?= h($kind) ?>" type="text" inputmode="decimal" name="kind_prices[<?= h($kind) ?>][sale_price]" value="<?= h($kindSales[$kind]['sale_price']??'') ?>" placeholder="Volitelné"><small class="text-muted">Stejná pro všechny jazyky.</small></div>
<div class="col-12 col-md-6 col-xl-3"><label class="form-label" for="rac-kind-sale-from-<?= h($kind) ?>">Akce od</label><input class="form-control" id="rac-kind-sale-from-<?= h($kind) ?>" type="datetime-local" name="kind_prices[<?= h($kind) ?>][sale_starts_at]" value="<?= h(product_sale_input($kindSales[$kind]['sale_starts_at']??'')) ?>"></div>
<div class="col-12 col-md-6 col-xl-3"><label class="form-label" for="rac-kind-sale-to-<?= h($kind) ?>">Akce do</label><input class="form-control" id="rac-kind-sale-to-<?= h($kind) ?>" type="datetime-local" name="kind_prices[<?= h($kind) ?>][sale_ends_at]" value="<?= h(product_sale_input($kindSales[$kind]['sale_ends_at']??'')) ?>"></div>
</div>
<div class="d-flex flex-wrap gap-3 align-items-center mb-3">
 <input type="hidden" id="rac-sale-enabled-<?= h($kind) ?>" name="kind_prices[<?= h($kind) ?>][sale_enabled]" value="<?= !empty($kindSales[$kind]['sale_enabled'])?'1':'0' ?>">
 <button type="button" class="btn <?= !empty($kindSales[$kind]['sale_enabled'])?'btn-outline-danger':'btn-outline-success' ?> rac-sale-toggle" data-rac-sale-kind="<?= h($kind) ?>" aria-pressed="<?= !empty($kindSales[$kind]['sale_enabled'])?'true':'false' ?>"><?= !empty($kindSales[$kind]['sale_enabled'])?'Akční cenu deaktivovat':'Akční cenu aktivovat' ?></button>
 <small class="text-muted">Termíny nejsou povinné. Bez nich platí zapnutá akce ihned. Změnu potvrďte tlačítkem „Uložit produkt“.</small>
  <?php if(!empty($kindSales[$kind]['sale_enabled']) && !empty($kindSales[$kind]['sale_ends_at']) && strtotime((string)$kindSales[$kind]['sale_ends_at'])<=time()): ?>
  <span class="text-danger fw-semibold">Pozor: termín akce již uplynul. Sleva se nyní zákazníkům nezobrazí.</span>
  <?php endif; ?>
</div>
<p class="mb-3"><a class="btn btn-sm btn-outline-primary" href="eshop-jazyky-vydani.php"><i class="icons icon-globe me-1"></i> Spravovat jazyky vydání knih</a></p>
<style>
/* RAC UX-02: Porto-style folding editions. Hidden panels must stay hidden regardless of the theme. */
.rac-edition-details[hidden]{display:none!important}
.rac-edition-item{overflow:hidden}
.rac-edition-heading{background:var(--bs-light,#f7f7f7)}
.rac-edition-item.rac-edition-open .rac-edition-heading{border-bottom:1px solid #dee2e6}
.rac-edition-toggle i{transition:transform .18s ease}
.rac-edition-item.rac-edition-open .rac-edition-toggle i{transform:rotate(180deg)}
.rac-edition-toggle:focus-visible{outline:2px solid #2670be;outline-offset:2px}
.rac-edition-file-status{display:flex;gap:5px;align-items:center;flex-wrap:wrap}
.rac-edition-file-status .badge{font-size:.72rem;font-weight:600;white-space:nowrap}
</style>
<div class="rac-edition-accordion" id="edition-group-<?= h($kind) ?>">
<?php foreach($editionLanguages as $code=>$label): $key=$kind.'|'.$code;$v=$variants[$key]??[];$enabled=($_SERVER['REQUEST_METHOD']??'GET')==='POST'?isset($v['enabled']):(!empty($v['id'])&&(int)($v['active']??0)===1);$included=($_SERVER['REQUEST_METHOD']??'GET')==='POST'?isset($v['included']):!empty($v['id']);$canSell=$kind==='print'||(!empty($v['id'])&&!empty($racPublished[(int)$v['id']]));$field='variants['.$kind.']['.$code.']';$sectionId='edition-'.$kind.'-'.$code;
$individualPrice=($_SERVER['REQUEST_METHOD']??'GET')==='POST'?isset($v['override_price']):(!empty($v['id']) && $kindBasePrices[$kind]!=='' && !racPriceEqual($v['price']??null,$kindBasePrices[$kind])); ?>
<div class="rac-edition-item border rounded mb-2" data-rac-edition-item="<?= h($sectionId) ?>">
<div class="rac-edition-heading d-flex gap-3 justify-content-between align-items-center flex-wrap p-3">
<label class="form-check-label"><input class="form-check-input me-2" type="checkbox" name="<?= h($field) ?>[included]" value="1" <?= $included?'checked':'' ?>> <strong><?= h($label) ?></strong> – připravit vydání</label>
<div class="d-flex align-items-center gap-2 flex-wrap">
<?php if($kind!=='print'): ?>
<?php if(!$id || empty($v['id'])): ?>
<span class="text-muted small">Nejdříve uložte vydání pro nahrání souborů</span>
<?php elseif(!$racFileOverviewReady): ?>
<span class="badge bg-light text-secondary border">Stav souborů nelze načíst</span>
<?php else:
 $racPresent=$racFileOverview[(int)$v['id']]??[];
 $racPaid=$racPresent['paid']??[];
 $racPreviews=$racPresent['preview']??[];
 $racExpected=$kind==='ebook'?['pdf','epub']:['mp3','m4a'];
?>
<span class="rac-edition-file-status" aria-label="Dostupné formáty vydání">
<?php foreach($racExpected as $racFormat): ?><?= racEditionFileBadge($racFormat,$racPaid[$racFormat]??'') ?><?php endforeach; ?>
<?php
 $racPreviewFormats=[];
 foreach($racPreviews as $racPreviewFormat=>$racPreviewStatus){
  if($racPreviewStatus==='published')$racPreviewFormats[]=strtoupper($racPreviewFormat);
 }
?>
<?php if($racPreviewFormats): ?><span class="badge bg-info text-dark" title="Publikovaná ukázka">Ukázka: <?= h(implode(' + ',$racPreviewFormats)) ?></span><?php elseif(in_array('uploaded',$racPreviews,true)): ?><span class="badge bg-warning text-dark">Ukázka nepublikována</span><?php else: ?><span class="badge bg-light text-secondary border">Ukázka chybí</span><?php endif; ?>
</span>
<?php endif; ?>

<?php if($id && !empty($v['id'])): ?><a class="btn btn-sm btn-outline-primary" href="eshop-soubory-vydani.php?id=<?= (int)$id ?>&amp;variant_id=<?= (int)$v['id'] ?>">Nahrát / spravovat soubor</a><?php endif; ?>
<?php endif; ?>
<label class="form-check-label"><input class="form-check-input me-2" type="checkbox" name="<?= h($field) ?>[enabled]" value="1" <?= $enabled?'checked':'' ?> <?= !$canSell?'disabled':'' ?>> Nabízet zákazníkům</label>
<button type="button" class="btn btn-sm btn-default rac-edition-toggle" aria-controls="<?= h($sectionId) ?>" aria-expanded="false" data-rac-edition-toggle="<?= h($sectionId) ?>"><i class="fas fa-chevron-down me-1" aria-hidden="true"></i> <span data-rac-toggle-text>Rozbalit nastavení</span></button>
</div></div>
<div class="rac-edition-details" id="<?= h($sectionId) ?>" hidden><div class="row g-3 p-3 pt-1">
<div class="col-12"><label class="form-check-label"><input class="form-check-input me-2 rac-override-price" type="checkbox" name="<?= h($field) ?>[override_price]" value="1" <?= $individualPrice?'checked':'' ?>> Individuální cena pro <?= h($label) ?></label></div>
<div class="col-12 col-md-6 col-xl-4 rac-individual-price" <?= $individualPrice?'':'hidden' ?>><label class="form-label">Vlastní cena CZK</label><input class="form-control" type="text" inputmode="decimal" name="<?= h($field) ?>[price]" value="<?= h(isset($v['price']) && (float)$v['price']>0 ? $v['price'] : '') ?>"></div>
<?php foreach(['vat_rate'=>'DPH (%)','sku'=>'SKU','isbn'=>'ISBN','ean'=>'EAN'] as $fieldName=>$fieldLabel): ?>
<div class="col-12 col-md-6 col-xl-4"><label class="form-label"><?= h($fieldLabel) ?></label><input class="form-control" <?= in_array($fieldName,['sale_starts_at','sale_ends_at'],true)?'type="datetime-local"':'type="text"' ?> name="<?= h($field) ?>[<?= h($fieldName) ?>]" value="<?= h(in_array($fieldName,['sale_starts_at','sale_ends_at'],true)?product_sale_input($v[$fieldName]??''):($v[$fieldName]??($fieldName==='vat_rate'?'0':''))) ?>"></div>
<?php endforeach; ?>
<?php if($kind==='print'):foreach(['stock_quantity'=>'Počet skladem','minimum_stock'=>'Minimální sklad','weight_grams'=>'Hmotnost v gramech'] as $fieldName=>$fieldLabel): ?>
<div class="col-12 col-md-6 col-xl-4"><label class="form-label"><?= h($fieldLabel) ?></label><input class="form-control" type="number" min="0" name="<?= h($field) ?>[<?= h($fieldName) ?>]" value="<?= h($v[$fieldName]??'') ?>"></div>
<?php endforeach;endif; ?>
</div></div></div>
<?php endforeach; ?></div>
</div>
<?php endforeach; ?>
</div></div></div></section>
<?php if($id): ?><p><a class="btn btn-default" href="eshop-fotografie.php?id=<?= (int)$id ?>"><i class="fas fa-images me-1"></i> Spravovat fotografie produktu</a></p><?php endif; ?>
<div class="card mb-4"><div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-2"><div><strong>Produkt</strong><div class="text-muted small">Po uložení se vrátíte na tuto stránku.</div></div><button class="btn btn-primary" type="submit"><i class="fas fa-save me-1"></i> Uložit produkt</button></div></div>
</form></div></div></section>
<script>
document.querySelectorAll('.rac-override-price').forEach(function(toggle){var row=toggle.closest('.row'),priceField=row?row.querySelector('.rac-individual-price'):null;if(priceField)toggle.addEventListener('change',function(){priceField.hidden=!toggle.checked;});});
(function(){var tabs=document.querySelectorAll('[data-rac-lang-tab]'),panels=document.querySelectorAll('[data-rac-lang-panel]');function complete(panel){var id=panel.getAttribute('data-rac-lang-panel'),star=document.querySelector('[data-rac-complete="'+id+'"]'),fields=['title','short_description','description'];if(!star)return;star.hidden=!fields.every(function(field){var el=panel.querySelector('[name="translations['+id+']['+field+']"]');return el&&el.value.trim()!=='';});}tabs.forEach(function(tab){tab.addEventListener('click',function(){var id=tab.getAttribute('data-rac-lang-tab');tabs.forEach(function(t){var active=t===tab;t.classList.toggle('active',active);t.setAttribute('aria-selected',active?'true':'false')});panels.forEach(function(p){p.hidden=p.getAttribute('data-rac-lang-panel')!==id})})});panels.forEach(function(panel){complete(panel);panel.querySelectorAll('input,textarea').forEach(function(field){field.addEventListener('input',function(){complete(panel)})})})})();
</script>
<script>
// Kdyz vypneme pripravu vydani, vypneme take prodej; pri aktivaci prodeje zapneme pripravu.
document.querySelectorAll('input[name^="variants["][name$="[included]"]').forEach(function(prepared){
 var card=prepared.closest('.border.rounded');if(!card)return;
 var offer=card.querySelector('input[name$="[enabled]"]');
 if(!offer)return;
 prepared.addEventListener('change',function(){if(!prepared.checked)offer.checked=false;});
 offer.addEventListener('change',function(){if(offer.checked)prepared.checked=true;});
});
</script>
<script>
// Zalozky ve stylu Porto tabs-primary. Vsechny formulare vydani zustavaji v DOM,
// aby se pri ulozeni odeslaly varianty vsech tri formatu bez ohledu na aktivni tab.
(function(){
 var root=document.getElementById('rac-edition-tabs');
 if(!root)return;
 var tabs=Array.prototype.slice.call(root.querySelectorAll('[data-rac-edition-tab]'));
 var panels=Array.prototype.slice.call(root.querySelectorAll('[data-rac-edition-panel]'));
 function activate(kind,focus){
  tabs.forEach(function(tab){
   var on=tab.getAttribute('data-rac-edition-tab')===kind;
   tab.classList.toggle('active',on);
   tab.setAttribute('aria-selected',on?'true':'false');
   tab.setAttribute('tabindex',on?'0':'-1');
   if(on&&focus)tab.focus();
  });
  panels.forEach(function(panel){
   var on=panel.getAttribute('data-rac-edition-panel')===kind;
   panel.hidden=!on;
   panel.classList.toggle('show',on);
   panel.classList.toggle('active',on);
  });
 }
 tabs.forEach(function(tab,index){
  tab.addEventListener('click',function(){activate(tab.getAttribute('data-rac-edition-tab'),false);});
  tab.addEventListener('keydown',function(event){
   if(event.key!=='ArrowRight'&&event.key!=='ArrowLeft'&&event.key!=='Home'&&event.key!=='End')return;
   event.preventDefault();
   var next=event.key==='Home'?0:event.key==='End'?tabs.length-1:(index+(event.key==='ArrowRight'?1:-1)+tabs.length)%tabs.length;
   activate(tabs[next].getAttribute('data-rac-edition-tab'),true);
  });
 });
 activate('print',false);
})();
</script>
<script>
/* RAC 2026-10-22-UX-02: accordion + form return point, no jQuery/Bootstrap dependency. */
(function () {
 'use strict';
 var form=document.getElementById('rac-product-editor');
 if(!form)return;
 var areaField=document.getElementById('rac-return-area');
 var tabField=document.getElementById('rac-return-tab');
 var focusField=document.getElementById('rac-return-focus');
 var tabs=document.querySelectorAll('[data-rac-edition-tab]');
 var languageTabs=document.querySelectorAll('[data-rac-lang-tab]');
 function setContext(area,tab,focus){
   if(areaField)areaField.value=area||'';
   if(tabField)tabField.value=tab||'';
   if(focusField)focusField.value=focus||'';
 }
 function activeTab(){
   var tab=document.querySelector('[data-rac-edition-tab].active');
   return tab?tab.getAttribute('data-rac-edition-tab'):'print';
 }
 function expand(btn,open){
   var id=btn.getAttribute('data-rac-edition-toggle');
   var panel=document.getElementById(id);
   if(!panel)return;
   panel.hidden=!open;
   btn.setAttribute('aria-expanded',open?'true':'false');
   var label=btn.querySelector('[data-rac-toggle-text]');
   if(label)label.textContent=open?'Sbalit nastavení':'Rozbalit nastavení';
   var card=btn.closest('.rac-edition-item');
   if(card)card.classList.toggle('rac-edition-open',open);
 }
 // Default all panels collapsed; manual expand works even if Porto Bootstrap version differs.
 document.querySelectorAll('[data-rac-edition-toggle]').forEach(function(btn){
   expand(btn,false);
   btn.addEventListener('click',function(){
     var content=document.getElementById(btn.getAttribute('data-rac-edition-toggle'));
     var next=!!content&&content.hidden;
     // Only one opened panel per format, to avoid very long pages.
     if(next){
       var group=btn.closest('.rac-edition-accordion');
       if(group)group.querySelectorAll('[data-rac-edition-toggle]').forEach(function(other){if(other!==btn)expand(other,false);});
     }
     expand(btn,next);
     setContext('edition',activeTab(),btn.getAttribute('data-rac-edition-toggle'));
   });
 });
 // Remember which field the admin changed, not the final Save button at the bottom.
 form.addEventListener('focusin',function(e){
   var target=e.target;
   if(!target||!target.closest)return;
   var lang=target.closest('[data-rac-lang-panel]');
   if(lang){setContext('translation','',lang.id);return;}
   var edition=target.closest('.rac-edition-item');
   if(edition){setContext('edition',activeTab(),edition.getAttribute('data-rac-edition-item'));return;}
   var panel=target.closest('[data-rac-edition-panel]');
   if(panel){setContext('edition',panel.getAttribute('data-rac-edition-panel'),target.id||panel.id);return;}
   if(target.closest('#rac-product-basic'))setContext('basic','','rac-product-basic');
 });
 form.addEventListener('change',function(e){
   var target=e.target;
   if(!target||!target.closest)return;
   var edition=target.closest('.rac-edition-item');
   if(edition)setContext('edition',activeTab(),edition.getAttribute('data-rac-edition-item'));
 });
 Array.prototype.forEach.call(tabs,function(tab){tab.addEventListener('click',function(){setContext('edition',tab.getAttribute('data-rac-edition-tab'),'rac-product-editions');});});
 Array.prototype.forEach.call(languageTabs,function(tab){tab.addEventListener('click',function(){setContext('translation','', 'rac-translation-'+tab.getAttribute('data-rac-lang-tab'));});});
 var params=new URLSearchParams(window.location.search);
 var returnArea=params.get('area')||'';
 var returnTab=params.get('tab')||'';
 var returnFocus=params.get('focus')||'';
 // On invalid POST, server doesn't redirect: use preserved fields.
 if(!returnArea && document.querySelector('.alert-danger')){
   returnArea=areaField.value||'';returnTab=tabField.value||'';returnFocus=focusField.value||'';
 }
 if(returnArea==='edition'){
   if(/^(print|ebook|audio)$/.test(returnTab)){
     var t=document.querySelector('[data-rac-edition-tab="'+returnTab+'"]');
     if(t)t.click();
   }
   if(/^edition-(?:print|ebook|audio)-[a-zA-Z0-9_-]{2,35}$/.test(returnFocus)){
     var b=document.querySelector('[data-rac-edition-toggle="'+returnFocus+'"]');
     if(b)expand(b,true);
   }
 }else if(returnArea==='translation'){
   var lid=/^rac-translation-(\d+)$/.exec(returnFocus);
   if(lid){var langTab=document.querySelector('[data-rac-lang-tab="'+lid[1]+'"]');if(langTab)langTab.click();}
 }
 // Scroll after layout/reflow, not before inactive tab becomes visible.
 if(returnArea){
   var dest=document.getElementById(returnFocus);
   if(!dest || dest.hidden || dest.offsetParent===null){
     dest=document.getElementById(returnArea==='edition'?'rac-product-editions':returnArea==='translation'?'rac-product-translations':'rac-product-basic');
   }
   if(dest){window.addEventListener('load',function(){
     window.requestAnimationFrame(function(){
       window.requestAnimationFrame(function(){
         var y=dest.getBoundingClientRect().top+window.pageYOffset-105;
         window.scrollTo({top:Math.max(0,y),behavior:'auto'});
       });
     });
   });}
 }
})();
</script>
<?php include __DIR__.'/inc/footer.class.php'; ?>

<script>
/* RAC SALE-01: jeden prepinac akce pro kazdy format. Ulozeni az odeslanim produktu. */
(function(){
 document.querySelectorAll('[data-rac-sale-kind]').forEach(function(button){
  var kind=button.getAttribute('data-rac-sale-kind');
  var state=document.getElementById('rac-sale-enabled-'+kind);
  var price=document.getElementById('rac-kind-sale-'+kind);
  var from=document.getElementById('rac-kind-sale-from-'+kind);
  var to=document.getElementById('rac-kind-sale-to-'+kind);
  if(!state||!price)return;
  function update(){
   var enabled=state.value==='1';
   button.textContent=enabled?'Akční cenu deaktivovat':'Akční cenu aktivovat';
   button.classList.toggle('btn-outline-danger',enabled);
   button.classList.toggle('btn-outline-success',!enabled);
   button.setAttribute('aria-pressed',enabled?'true':'false');
  }
  button.addEventListener('click',function(){
   var activating=state.value!=='1';
   if(activating && (!price.value.trim() || !(parseFloat(price.value.replace(',','.'))>0))){
    price.focus();price.setCustomValidity('Před aktivací zadejte akční cenu vyšší než nula.');price.reportValidity();
    return;
   }
   // Pri opakovane aktivaci casto zustane stare, jiz uplynule obdobi.
   // Takova akce by byla sice zapnuta, ale na webu by se nezobrazila.
   // Pro okamzite spusteni vycistime jen UPLYNULY termin (nikdy budoucí plan).
   if(activating && to && to.value){
    var expires=new Date(to.value);
    if(!isNaN(expires.getTime()) && expires.getTime()<=Date.now()){
      if(window.confirm('Původní období akce již skončilo. Spustit akční cenu ihned bez časového omezení?')){
        if(from)from.value='';to.value='';
      } else {
        to.focus();return;
      }
    }
   }
   price.setCustomValidity('');state.value=activating?'1':'0';update();
  });
  price.addEventListener('input',function(){
   price.setCustomValidity('');
   if(!price.value.trim()){
    state.value='0';if(from)from.value='';if(to)to.value='';update();
   }
  });
  update();
 });
})();
</script>
