<?php
// verze 2026-10-08-13.30 / RAC eshop fotografie / PHP 7.4
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
$base=__DIR__.'/uploads/products';
$relativeBase='uploads/products/';
function rac_img_delete(string $path): void {
    if (!preg_match('~^uploads/products/[a-f0-9]{32}\.(?:webp|png|jpg)$~D',$path)){return;}
    $p=__DIR__.'/'.$path;
    if (is_file($p)){@unlink($p);}
}
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
                $st=$pdo->prepare('SELECT COUNT(*) FROM product_images WHERE product_id=?');$st->execute(array($id));
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
                            $st=$pdo->prepare('SELECT COUNT(*) FROM product_images WHERE product_id=?');$st->execute(array($id));$count=(int)$st->fetchColumn();
                            // Recheck inside transaction, only one uploader expected per product.
                            if($count>=10){throw new RuntimeException('Limit fotografií byl dosažen.');}
                            $st=$pdo->prepare('SELECT COALESCE(MAX(sort_order),0)+10 FROM product_images WHERE product_id=?');$st->execute(array($id));$order=(int)$st->fetchColumn();
                            $st=$pdo->prepare('INSERT INTO product_images(product_id,file_path,alt_text,is_primary,sort_order) VALUES(?,?,NULL,?,?)');
                            $st->execute(array($id,$destination,$count===0?1:0,$order));
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
                    $st=$pdo->prepare('UPDATE product_images SET sort_order=?,alt_text=? WHERE id=? AND product_id=?');
                    foreach($rows as $imageKey=>$v){
                        if(!ctype_digit((string)$imageKey)||!is_array($v)){continue;}
                        $order=filter_var($v['sort_order']??null,FILTER_VALIDATE_INT);
                        $alt=trim((string)($v['alt_text']??''));
                        if($order===false||mb_strlen($alt)>255){throw new RuntimeException('Nesprávné pořadí či alternativní text.');}
                        $st->execute(array($order,$alt!==''?$alt:null,(int)$imageKey,$id));
                    }
                }else{
                    $st=$pdo->prepare('SELECT * FROM product_images WHERE id=? AND product_id=?');$st->execute(array($imageId,$id));$img=$st->fetch(PDO::FETCH_ASSOC);
                    if(!$img){throw new RuntimeException('Fotografie nebyla nalezena.');}
                    if($action==='primary'){
                        $st=$pdo->prepare('UPDATE product_images SET is_primary=0 WHERE product_id=?');$st->execute(array($id));
                        $st=$pdo->prepare('UPDATE product_images SET is_primary=1 WHERE id=? AND product_id=?');$st->execute(array($imageId,$id));
                    }else{
                        $st=$pdo->prepare('DELETE FROM product_images WHERE id=? AND product_id=?');$st->execute(array($imageId,$id));
                        $st=$pdo->prepare('SELECT id FROM product_images WHERE product_id=? ORDER BY is_primary DESC,sort_order,id LIMIT 1');$st->execute(array($id));$first=$st->fetchColumn();
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
$st=$pdo->prepare('SELECT * FROM product_images WHERE product_id=? ORDER BY is_primary DESC,sort_order,id');$st->execute(array($id));$images=$st->fetchAll(PDO::FETCH_ASSOC);
$pageTitle='Fotografie produktu';include __DIR__.'/inc/header.class.php';include __DIR__.'/inc/leve-meny.class.php';
?>
<main class="admin-content"><div class="page-header"><h1>Fotografie produktu</h1><p><?= h($product['slug']) ?> · <?= count($images) ?> / 10 fotografií</p></div>
<p><a class="btn btn-light" href="eshop-produkt.php?id=<?= $id ?>">← Zpět na produkt</a></p>
<?php if($errors): ?><div class="alert alert-danger"><?php foreach($errors as $e): ?><p><?= h($e) ?></p><?php endforeach; ?></div><?php endif; ?>
<?php if($ok): ?><div class="alert alert-success"><?= h($ok) ?></div><?php endif; ?>
<section class="card"><div class="card-header"><h2>Nahrát fotografii</h2></div><div class="card-body">
<form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="action" value="upload"><div class="form-group"><label>JPG / PNG / WEBP, max. 5 MB</label><input class="form-control" type="file" name="image" accept="image/jpeg,image/png,image/webp" required></div><button class="btn btn-primary" type="submit">Nahrát fotografii</button></form>
</div></section>
<section class="card" style="margin-top:20px;"><div class="card-header"><h2>Galerie</h2></div><div class="card-body">
<?php if(!$images): ?><p>Zatím nebyly nahrány žádné fotografie.</p><?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="action" value="save">
<?php foreach($images as $im): ?>
<div style="display:flex;gap:20px;align-items:center;border-bottom:1px solid #ddd;padding:16px 0;flex-wrap:wrap;">
<img src="<?= h($im['file_path']) ?>" alt="" style="max-width:135px;max-height:135px;object-fit:contain;">
<div style="flex:1;min-width:240px;">
<p><strong><?= (int)$im['is_primary']===1?'Hlavní fotografie':'Fotografie #'.(int)$im['id'] ?></strong></p>
<div class="form-group"><label>Pořadí</label><input class="form-control" type="number" name="images[<?= (int)$im['id'] ?>][sort_order]" value="<?= (int)$im['sort_order'] ?>"></div>
<div class="form-group"><label>Alternativní text</label><input class="form-control" maxlength="255" name="images[<?= (int)$im['id'] ?>][alt_text]" value="<?= h($im['alt_text']??'') ?>"></div>
</div>
<div style="display:flex;flex-direction:column;gap:6px;">
<button class="btn btn-light" type="submit" form="primary-<?= (int)$im['id'] ?>" <?= (int)$im['is_primary']===1?'disabled':'' ?>>Nastavit hlavní</button>
<button class="btn btn-light" type="submit" form="delete-<?= (int)$im['id'] ?>" onclick="return confirm('Opravdu odstranit fotografii?');">Odstranit</button>
</div></div>
<?php endforeach; ?>
<?php if($images): ?><p><button class="btn btn-primary" type="submit">Uložit pořadí a popisky</button></p><?php endif; ?></form>
<?php foreach($images as $im): ?>
<form id="primary-<?= (int)$im['id'] ?>" method="post"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="action" value="primary"><input type="hidden" name="image_id" value="<?= (int)$im['id'] ?>"></form>
<form id="delete-<?= (int)$im['id'] ?>" method="post"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="image_id" value="<?= (int)$im['id'] ?>"></form>
<?php endforeach; ?>
</div></section></main>
<?php include __DIR__.'/inc/footer.class.php'; ?>
