<?php
declare(strict_types=1);
// RAC 2026-10-22-01 | produkty a vydani ve vsech jazycich | PHP 7.4+
function racShopH($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function racShopLanguageIds(PDO $pdo, string $code): array {
 $default=defined('DEFAULT_LANGUAGE')?DEFAULT_LANGUAGE:'cs';
 $stmt=$pdo->prepare('SELECT code,id FROM languages WHERE code IN (?,?,?)');
 $stmt->execute([$code,$default,'cs']);
 $ids=[];foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $r)$ids[$r['code']]=(int)$r['id'];
 return [$ids[$code]??0,$ids[$default]??0,$ids['cs']??0];
}
function racShopProducts(PDO $pdo,string $lang): array {
 [$a,$b,$c]=racShopLanguageIds($pdo,$lang);
 $q=$pdo->prepare("SELECT p.id,p.slug,p.author,p.release_date,p.featured,
 COALESCE(NULLIF(t1.title,''),NULLIF(t2.title,''),NULLIF(t3.title,'')) AS title,
 COALESCE(NULLIF(t1.short_description,''),NULLIF(t2.short_description,''),NULLIF(t3.short_description,''),'') AS short_description,
 (SELECT file_path FROM product_images i
 WHERE i.product_id=p.id AND (i.language_code=? OR i.language_code='cs' OR i.language_code IS NULL)
 ORDER BY CASE WHEN i.language_code=? THEN 0 WHEN i.language_code='cs' THEN 1 ELSE 2 END,
 i.is_primary DESC,i.sort_order ASC,i.id ASC LIMIT 1) AS image
 FROM products p LEFT JOIN product_translations t1 ON t1.product_id=p.id AND t1.language_id=?
 LEFT JOIN product_translations t2 ON t2.product_id=p.id AND t2.language_id=?
 LEFT JOIN product_translations t3 ON t3.product_id=p.id AND t3.language_id=?
 WHERE p.status='active' ORDER BY p.sort_order ASC,p.id DESC");
 $q->execute([$lang,$lang,$a,$b,$c]);return $q->fetchAll(PDO::FETCH_ASSOC);
}
function racShopProduct(PDO $pdo,string $slug,string $lang): ?array {
 [$a,$b,$c]=racShopLanguageIds($pdo,$lang);
 $q=$pdo->prepare("SELECT p.*,
 COALESCE(NULLIF(t1.title,''),NULLIF(t2.title,''),NULLIF(t3.title,'')) AS title,
 COALESCE(NULLIF(t1.short_description,''),NULLIF(t2.short_description,''),NULLIF(t3.short_description,''),'') AS short_description,
 COALESCE(NULLIF(t1.description,''),NULLIF(t2.description,''),NULLIF(t3.description,''),'') AS description,
 COALESCE(NULLIF(t1.seo_title,''),NULLIF(t2.seo_title,''),NULLIF(t3.seo_title,''),'') AS seo_title,
 COALESCE(NULLIF(t1.seo_description,''),NULLIF(t2.seo_description,''),NULLIF(t3.seo_description,''),'') AS seo_description
 FROM products p LEFT JOIN product_translations t1 ON t1.product_id=p.id AND t1.language_id=?
 LEFT JOIN product_translations t2 ON t2.product_id=p.id AND t2.language_id=?
 LEFT JOIN product_translations t3 ON t3.product_id=p.id AND t3.language_id=?
 WHERE p.slug=? AND p.status='active' LIMIT 1");
 $q->execute([$a,$b,$c,$slug]);$row=$q->fetch(PDO::FETCH_ASSOC);return $row?:null;
}
function racShopVariants(PDO $pdo,int $id): array {
 // Vydani jsou nezavisla na mutacich webu. Nazev jazyka je z katalogu vydani.
 $q=$pdo->prepare("SELECT v.id,v.kind,v.language_code,v.price,v.sale_price,v.sale_starts_at,v.sale_ends_at,
 v.currency,v.stock_quantity,v.active,
 COALESCE(NULLIF(el.native_name,''),v.language_code) AS edition_language_name
 FROM product_variants v LEFT JOIN book_edition_languages el ON el.code=v.language_code
 WHERE v.product_id=? AND v.active=1 AND v.language_code IS NOT NULL AND v.language_code<>''
 ORDER BY FIELD(v.kind,'print','ebook','audio'),v.language_code,v.id");
 $q->execute([$id]);$rows=$q->fetchAll(PDO::FETCH_ASSOC);
 $GLOBALS['racEditionLanguageNames']=[];
 foreach($rows as $row) $GLOBALS['racEditionLanguageNames'][(string)$row['language_code']]=(string)$row['edition_language_name'];
 return $rows;
}
function racShopImages(PDO $pdo,int $id,string $lang='cs'): array {
 $q=$pdo->prepare("SELECT file_path,alt_text,language_code FROM product_images
 WHERE product_id=? AND (language_code=? OR language_code='cs' OR language_code IS NULL)
 ORDER BY is_primary DESC,sort_order ASC,id ASC");
 $q->execute([$id,$lang]);$groups=['selected'=>[],'cs'=>[],'legacy'=>[]];
 foreach($q->fetchAll(PDO::FETCH_ASSOC) as $row){
  $code=$row['language_code'];$key=$code===$lang?'selected':($code==='cs'?'cs':'legacy');
  $groups[$key][]=['file_path'=>$row['file_path'],'alt_text'=>$row['alt_text']];
 }
 foreach(['selected','cs','legacy'] as $key)if($groups[$key])return $groups[$key];
 return [];
}
function racShopImageUrl(?string $path): string {
 if(!$path || !preg_match('~^uploads/products/[a-f0-9]{32}\.(?:webp|png|jpg)$~D',$path))return '';
 return 'administrace/'.$path;
}
function racShopPrice(array $v): string {
 $price=(string)$v['price'];
 if($v['sale_price']===null || empty($v['sale_starts_at']) || empty($v['sale_ends_at']))return $price;
 $tz=new DateTimeZone('Europe/Prague');$now=new DateTimeImmutable('now',$tz);
 $start=new DateTimeImmutable((string)$v['sale_starts_at'],$tz);
 $end=new DateTimeImmutable((string)$v['sale_ends_at'],$tz);
 return $now >= $start && $now < $end && (float)$v['sale_price']<(float)$price ? (string)$v['sale_price']:$price;
}
function racShopMoney(string $amount,string $currency): string {return number_format((float)$amount,2,',',' ').' '.racShopH($currency);}
function racShopKind(string $kind): string {return ['print'=>'Tištěná kniha','ebook'=>'E-kniha','audio'=>'Audiokniha'][$kind]??$kind;}
function racEditionLanguage(?string $code): string {
 $names=['cs'=>'Čeština','en'=>'English','de'=>'Deutsch','pl'=>'Polski','fr'=>'Français','sk'=>'Slovenčina'];
 if($code===null||$code==='')return 'Jazyk neurčen';
 $dynamic=$GLOBALS['racEditionLanguageNames']??[];
 return $dynamic[$code]??($names[$code]??strtoupper($code));
}
