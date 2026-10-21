<?php
// RAC-FOTO-FIX-20261021-V6 | kompletni PHP blok | PHP 7.4+
declare(strict_types=1);
require_once __DIR__.'/config/session.php';
require_once __DIR__.'/config/db.php';
require_once __DIR__.'/config/auth.php';
require_once __DIR__.'/config/auth_roles.php';
require_min_role('admin');
$id=max(0,(int)($_GET['id']??0));
$stmt=$pdo->prepare('SELECT id,slug FROM products WHERE id=?');$stmt->execute(array($id));$product=$stmt->fetch(PDO::FETCH_ASSOC);
if (!$product){http_response_code(404);exit('Produkt nenalezen.');}
$errors=array();$ok='';$csrf=csrf_token('csrf_product_images');
$langs=['shared'=>'Společné (původní)', 'cs'=>'Čeština', 'en'=>'English', 'de'=>'Deutsch', 'pl'=>'Polski', 'fr'=>'Français'];
$tab=(string)($_POST['gallery_lang']??$_GET['lang']??'shared');
if (!isset($langs[$tab])) {$tab='shared';}
$code=$tab==='shared' ? null : $tab;
// Požadovaná migrace 018: nevytváříme tichou nekompatibilitu se starou DB.
try {$pdo->query('SELECT language_code FROM product_images LIMIT 0');}
catch (Throwable $ex) {http_response_code(503);exit('Nejprve spusťte migraci 018_product_images_languages.sql.');}
$filterSql=$code===null ? 'language_code IS NULL' : 'language_code=?';
$filterArgs=$code===null ? [$id] : [$id,$code];
$base=__DIR__.'/uploads/products';
$relativeBase='uploads/products/';
function rac_img_delete(string $path): void {
    if (!preg_match('~^uploads/products/[a-f0-9]{32}\.(?:webp|png|jpg)$~D',$path)){return;}
    $p=__DIR__.'/'.$path;
    if (is_file($p)){@unlink($p);}
}
// Veškeré zpracování formulářů zůstává uvnitř tohoto PHP bloku.
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!csrf_valid('csrf_product_images',(string)($_POST['csrf']??''))){$errors[]='Neplatný bezpečnostní token.';}
    if (!$errors){
        $action=(string)($_POST['action']??'');
        if($action==='upload'){
            $file=$_FILES['image']??null;
            if(!is_array($file)||(int)($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK){$errors[]='Nahrajte platný obrázek.';}
            elseif((int)($file['size']??0)<1||(int)$file['size']>5*1024*1024){$errors[]='Soubor musí mít maximálně 5 MB.';}
            elseif(!is_uploaded_file((string)$file['tmp_name'])){$errors[]='Neplatný upload.';}
            $mime='';$type='';
            if(!$errors){
                $mime=(string)(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
                $allowed=array('image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp');
                if(!isset($allowed[$mime])||@getimagesize($file['tmp_name'])===false){$errors[]='Povolené formáty jsou JPG, PNG a WEBP.';}
                else{$type=$allowed[$mime];}
            }
            if(!$errors){
                $st=$pdo->prepare('SELECT COUNT(*) FROM product_images WHERE product_id=? AND '.$filterSql);$st->execute($filterArgs);
                if((int)$st->fetchColumn()>=10){$errors[]='Maximálně 10 fotografií na produkt.';}
            }
            if(!$errors){
                if(!is_dir($base)&&!mkdir($base,0775,true)){$errors[]='Nelze vytvořit uploads/products.';}
                $name=bin2hex(random_bytes(16));$destination='';
                if(!$errors){
                    // Convert to WEBP when GD supports this source type; otherwise retain original format.
                    $img=null;
                    if(function_exists('imagewebp')){
                        if($mime==='image/jpeg'&&function_exists('imagecreatefromjpeg')){$img=@imagecreatefromjpeg($file['tmp_name']);}
                        if($mime==='image/png'&&function_exists('imagecreatefrompng')){$img=@imagecreatefrompng($file['tmp_name']);}
                        if($mime==='image/webp'&&function_exists('imagecreatefromwebp')){$img=@imagecreatefromwebp($file['tmp_name']);}
                    }
                    if($img){
                        if($mime==='image/png') {imagepalettetotruecolor($img);imagealphablending($img,false);imagesavealpha($img,true);}
                        $destination=$relativeBase.$name.'.webp';
                        $saved=@imagewebp($img,__DIR__.'/'.$destination,82);
                        imagedestroy($img);
                    }else{
                        $destination=$relativeBase.$name.'.'.$type;
                        $saved=move_uploaded_file($file['tmp_name'],__DIR__.'/'.$destination);
                    }
                    if(!$saved){$errors[]='Soubor se nepodařilo uložit.';}
                    else {
                        @chmod(__DIR__.'/'.$destination,0644);
                        try{
                            $pdo->beginTransaction();
                            $st=$pdo->prepare('SELECT COUNT(*) FROM product_images WHERE product_id=? AND '.$filterSql);$st->execute($filterArgs);$count=(int)$st->fetchColumn();
                            // Recheck inside transaction, only one uploader expected per product.
                            if($count>=10){throw new RuntimeException('Limit fotografií byl dosažen.');}
                            $st=$pdo->prepare('SELECT COALESCE(MAX(sort_order),0)+10 FROM product_images WHERE product_id=? AND '.$filterSql);$st->execute($filterArgs);$order=(int)$st->fetchColumn();
                            $st=$pdo->prepare('INSERT INTO product_images(product_id,language_code,file_path,alt_text,is_primary,sort_order) VALUES(?,?,?,NULL,?,?)');
                            $st->execute(array($id,$code,$destination,$count===0?1:0,$order));
                            $pdo->commit();$ok='Fotografie byla nahrána.';
                        }catch(Throwable $e){if($pdo->inTransaction()){$pdo->rollBack();}rac_img_delete($destination);$errors[]='Fotografii se nepodařilo zapsat do databáze.';error_log($e->getMessage());}
                    }
                }
            }
        }elseif(in_array($action,array('delete','primary','save'),true)){
            $imageId=(int)($_POST['image_id']??0);
            try{
                $pdo->beginTransaction();
                if($action==='save'){
                    $rows=(array)($_POST['images']??array());
                    $st=$pdo->prepare('UPDATE product_images SET sort_order=?,alt_text=? WHERE id=? AND product_id=? AND '.$filterSql);
                    foreach($rows as $imageKey=>$v){
                        if(!ctype_digit((string)$imageKey)||!is_array($v)){continue;}
                        $order=filter_var($v['sort_order']??null,FILTER_VALIDATE_INT);
                        $alt=trim((string)($v['alt_text']??''));
                        if($order===false||mb_strlen($alt)>255){throw new RuntimeException('Nesprávné pořadí či alternativní text.');}
                        $st->execute(array_merge(array($order,$alt!==''?$alt:null,(int)$imageKey,$id), $code===null ? [] : [$code]));
                    }
                }else{
                    $st=$pdo->prepare('SELECT * FROM product_images WHERE id=? AND product_id=? AND '.$filterSql);$st->execute(array_merge([$imageId,$id],$code===null?[]:[$code]));$img=$st->fetch(PDO::FETCH_ASSOC);
                    if(!$img){throw new RuntimeException('Fotografie nebyla nalezena.');}
                    if($action==='primary'){
                        $st=$pdo->prepare('UPDATE product_images SET is_primary=0 WHERE product_id=? AND '.$filterSql);$st->execute($filterArgs);
                        $st=$pdo->prepare('UPDATE product_images SET is_primary=1 WHERE id=? AND product_id=? AND '.$filterSql);$st->execute(array_merge([$imageId,$id],$code===null?[]:[$code]));
                    }else{
                        $st=$pdo->prepare('DELETE FROM product_images WHERE id=? AND product_id=? AND '.$filterSql);$st->execute(array_merge([$imageId,$id],$code===null?[]:[$code]));
                        $st=$pdo->prepare('SELECT id FROM product_images WHERE product_id=? AND '.$filterSql.' ORDER BY is_primary DESC,sort_order,id LIMIT 1');$st->execute($filterArgs);$first=$st->fetchColumn();
                        if($first){$st=$pdo->prepare('UPDATE product_images SET is_primary=1 WHERE id=?');$st->execute(array($first));}
                    }
                }
                $pdo->commit();
                if($action==='delete'){rac_img_delete((string)$img['file_path']);}
                $ok='Změna byla uložena.';
            }catch(Throwable $e){if($pdo->inTransaction()){$pdo->rollBack();}$errors[]='Změna nebyla uložena: '.$e->getMessage();}
        }
    }
}
$st=$pdo->prepare('SELECT * FROM product_images WHERE product_id=? AND '.$filterSql.' ORDER BY is_primary DESC,sort_order,id');$st->execute($filterArgs);$images=$st->fetchAll(PDO::FETCH_ASSOC);
$racUsePortoLayout=true;
$base='/administrace';$assets=$base;
$title='Fotografie produktu | RAC';$pageTitle='Fotografie produktu';
$currentPath='/administrace/eshop-fotografie.php';
$viewer=current_user_row();
$viewerName=(string)($viewer['username']??'Administrátor');
$viewerRole=(string)($viewer['role']??'');
$roleLabel=function_exists('role_label')?role_label($viewerRole):$viewerRole;
$menuGroups=[]; // Levé menu sestavuje sdílená komponenta RAC.
$e=static function($v): string{return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');};
// Pro sablonu pouzivame pojmenovanou funkci, kterou include nemuze prepsat.
function racImageH($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
include __DIR__.'/inc/header.class.php';include __DIR__.'/inc/leve-meny.class.php';
?>
<section role="main" class="content-body content-body-modern mt-0">
<header class="page-header"><h2>Fotografie produktu <small style="font-size:11px;opacity:.55">RAC-FOTO-V6</small></h2></header>
<div class="row pt-3 mb-4"><div class="col-12">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><p class="mb-0 text-muted"><?= racImageH($product['slug']) ?> · <?= count($images) ?> / 10 fotografií v galerii</p><a class="btn btn-default" href="eshop-produkt.php?id=<?= $id ?>">← Zpět na produkt</a></div>
<ul class="nav nav-tabs tabs-primary mb-3" role="tablist">
<?php foreach($langs as $key=>$label): ?>
<li class="nav-item"><a class="nav-link <?= $tab===$key?'active':'' ?>" href="?id=<?= $id ?>&amp;lang=<?= racImageH($key) ?>"><?= racImageH($label) ?></a></li>
<?php endforeach; ?>
</ul>
<?php if($tab!=='shared' && !$images): ?><div class="alert alert-info">Pro tento jazyk zatím není galerie nahraná. Na webu se použijí české fotografie, případně původní společná galerie.</div><?php endif; ?>
<?php if($tab==='shared'): ?><div class="alert alert-info">Původní společné fotografie zůstávají zachované. Samostatné jazykové galerie mají přednost.</div><?php endif; ?>
<?php foreach($errors as $message): ?><div class="alert alert-danger"><?= racImageH($message) ?></div><?php endforeach; ?>
<?php if($ok): ?><div class="alert alert-success"><?= racImageH($ok) ?></div><?php endif; ?>
<section class="card mb-4"><header class="card-header"><h2 class="card-title">Nahrát fotografii – <?= racImageH($langs[$tab]) ?></h2></header><div class="card-body">
<form method="post" enctype="multipart/form-data">
<input type="hidden" name="csrf" value="<?= racImageH($csrf) ?>"><input type="hidden" name="action" value="upload"><input type="hidden" name="gallery_lang" value="<?= racImageH($tab) ?>">
<div class="row g-3 align-items-end"><div class="col-md-9"><label class="form-label">JPG / PNG / WEBP (max. 5 MB)</label><input class="form-control" type="file" name="image" accept="image/jpeg,image/png,image/webp" required></div><div class="col-md-3"><button class="btn btn-primary w-100" type="submit">Nahrát fotografii</button></div></div>
</form></div></section>
<section class="card"><header class="card-header"><h2 class="card-title">Galerie – <?= racImageH($langs[$tab]) ?></h2></header><div class="card-body">
<?php if(!$images): ?><p>V této galerii zatím nejsou fotografie.</p><?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?= racImageH($csrf) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="gallery_lang" value="<?= racImageH($tab) ?>">
<?php foreach($images as $im): $imId=(int)$im['id']; ?>
<div class="row g-3 align-items-center border-bottom py-3">
<div class="col-sm-3 col-lg-2"><img src="<?= racImageH($im['file_path']) ?>" alt="" style="max-width:135px;width:100%;height:135px;object-fit:contain"></div>
<div class="col-sm-9 col-lg-7"><div class="mb-2"><strong><?= (int)$im['is_primary']===1?'Hlavní fotografie':'Fotografie #'.$imId ?></strong></div>
<div class="row g-2"><div class="col-sm-3"><label class="form-label">Pořadí</label><input class="form-control" type="number" name="images[<?= $imId ?>][sort_order]" value="<?= (int)$im['sort_order'] ?>"></div><div class="col-sm-9"><label class="form-label">Alternativní text</label><input class="form-control" maxlength="255" name="images[<?= $imId ?>][alt_text]" value="<?= racImageH($im['alt_text']??'') ?>"></div></div></div>
<div class="col-12 col-lg-3"><div class="d-flex flex-wrap gap-2 justify-content-lg-end"><button class="btn btn-default btn-sm" type="submit" form="primary-<?= $imId ?>" <?= (int)$im['is_primary']===1?'disabled':'' ?>>Nastavit hlavní</button><button class="btn btn-danger btn-sm" type="submit" form="delete-<?= $imId ?>" onclick="return confirm('Opravdu odstranit fotografii?');">Odstranit</button></div></div>
</div>
<?php endforeach; ?>
<?php if($images): ?><div class="text-end pt-3"><button class="btn btn-primary" type="submit">Uložit pořadí a popisky</button></div><?php endif; ?></form>
<?php foreach($images as $im): $imId=(int)$im['id']; ?>
<form id="primary-<?= $imId ?>" method="post"><input type="hidden" name="csrf" value="<?= racImageH($csrf) ?>"><input type="hidden" name="gallery_lang" value="<?= racImageH($tab) ?>"><input type="hidden" name="action" value="primary"><input type="hidden" name="image_id" value="<?= $imId ?>"></form>
<form id="delete-<?= $imId ?>" method="post"><input type="hidden" name="csrf" value="<?= racImageH($csrf) ?>"><input type="hidden" name="gallery_lang" value="<?= racImageH($tab) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="image_id" value="<?= $imId ?>"></form>
<?php endforeach; ?>
</div></section></div></div></section>
<?php include __DIR__.'/inc/footer.class.php'; ?>
