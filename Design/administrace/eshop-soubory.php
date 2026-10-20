<?php
// RAC / Porto BOS24 / digital file management / 2026-10-16 / PHP 7.4
// Existing product_files scheme: variant_id, purpose, media_type, storage_key, original_filename, mime_type, file_size.
declare(strict_types=1);
require_once __DIR__.'/config/session.php';
require_once __DIR__.'/config/db.php';
require_once __DIR__.'/config/auth.php';
require_once __DIR__.'/config/auth_roles.php';
require_once __DIR__.'/config/digital_storage.php';
require_min_role('admin');
$id=max(0,(int)($_GET['id']??0));
if(!$id){http_response_code(400);exit('Chybí ID produktu.');}
$st=$pdo->prepare('SELECT id,slug FROM products WHERE id=?');$st->execute([$id]);$product=$st->fetch(PDO::FETCH_ASSOC);
if(!$product){http_response_code(404);exit('Produkt nenalezen.');}
$st=$pdo->prepare("SELECT id,kind,active FROM product_variants WHERE product_id=? AND kind IN ('ebook','audio') ORDER BY kind");$st->execute([$id]);$variants=$st->fetchAll(PDO::FETCH_ASSOC);
$variantMap=[];foreach($variants as $v)$variantMap[(int)$v['id']]=$v['kind'];
$selectedKind=(string)($_GET['kind']??'');$selectedPurpose=(string)($_GET['purpose']??'paid');
if(!in_array($selectedKind,['ebook','audio'],true))$selectedKind='';
if(!in_array($selectedPurpose,['paid','preview'],true))$selectedPurpose='paid';
$selectedVariantId=0;foreach($variants as $v){if($selectedKind===$v['kind']){$selectedVariantId=(int)$v['id'];break;}}
$csrf=csrf_token('csrf_product_files');$errors=[];$ok='';
$types=['ebook'=>['pdf','epub'],'audio'=>['mp3','m4a']];
$mimes=['pdf'=>['application/pdf'],'epub'=>['application/epub+zip','application/zip','application/octet-stream'],'mp3'=>['audio/mpeg','audio/mp3','application/octet-stream'],'m4a'=>['audio/mp4','audio/x-m4a','video/mp4','application/octet-stream']];
if(($_SERVER['REQUEST_METHOD']??'')==='POST'){
 if(!csrf_valid('csrf_product_files',(string)($_POST['csrf']??'')))$errors[]='Neplatný bezpečnostní token.';
 else{
  $action=(string)($_POST['action']??'');
  if($action==='upload'){
   $vid=(int)($_POST['variant_id']??0);$purpose=(string)($_POST['purpose']??'');$media=(string)($_POST['media_type']??'');$f=$_FILES['digital_file']??null;
   if(!isset($variantMap[$vid])||!in_array($purpose,['paid','preview'],true)||!in_array($media,$types[$variantMap[$vid]]??[],true))$errors[]='Neplatná varianta, účel nebo formát souboru.';
   if(!is_array($f)||(int)($f['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK||!is_uploaded_file((string)($f['tmp_name']??'')))$errors[]='Nahrání selhalo. Zkontrolujte limity upload_max_filesize a post_max_size.';
   if(is_array($f)&&((int)($f['size']??0)<1||(int)$f['size']>500*1024*1024))$errors[]='Soubor musí mít velikost 1 B až 500 MiB.';
   if(!$errors){
    $name=basename(str_replace('\\','/',(string)$f['name']));
    if($name===''||strlen($name)>255||strtolower(pathinfo($name,PATHINFO_EXTENSION))!==$media)$errors[]='Neplatný název nebo přípona.';
    $mime=(string)(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if(!in_array($mime,$mimes[$media],true))$errors[]='Soubor neodpovídá zvolenému formátu.';
    $fh=fopen($f['tmp_name'],'rb');$sig=$fh?(string)fread($fh,12):'';if($fh)fclose($fh);
    if(($media==='pdf'&&substr($sig,0,5)!=='%PDF-')||($media==='epub'&&substr($sig,0,2)!=='PK')||($media==='mp3'&&substr($sig,0,3)!=='ID3'&&substr($sig,0,1)!=="\xFF")||($media==='m4a'&&substr($sig,4,4)!=='ftyp'))$errors[]='Soubor nemá očekávanou hlavičku.';
   }
   if(!$errors){
    $target=null;
    try{
     $dir=rac_private_storage_dir();$key=bin2hex(random_bytes(24));$target=$dir.DIRECTORY_SEPARATOR.$key;
     if(!move_uploaded_file($f['tmp_name'],$target))throw new RuntimeException('Nepodařilo se přesunout nahraný soubor.');
     @chmod($target,0600);
     $pdo->beginTransaction();$ins=$pdo->prepare('INSERT INTO product_files(variant_id,purpose,media_type,storage_key,original_filename,mime_type,file_size) VALUES (?,?,?,?,?,?,?)');
     $ins->execute([$vid,$purpose,$media,$key,$name,$mime,(int)$f['size']]);$pdo->commit();$ok='Soubor byl uložen do chráněného úložiště.';
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();if($target&&is_file($target))@unlink($target);error_log('RAC files upload: '.$e->getMessage());$errors[]='Nahrání se nepodařilo: '.$e->getMessage();}
   }
  }elseif($action==='delete'){
   $fileId=(int)($_POST['file_id']??0);
   $st=$pdo->prepare('SELECT f.id,f.storage_key FROM product_files f JOIN product_variants v ON v.id=f.variant_id WHERE f.id=? AND v.product_id=?');$st->execute([$fileId,$id]);$file=$st->fetch(PDO::FETCH_ASSOC);
   if(!$file)$errors[]='Soubor nebyl nalezen.';
   else{
    try{
     // Do not delete files already referenced by an existing digital access, to protect paid customers.
     $st=$pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='shop_digital_access'");$st->execute();$hasAccess=(int)$st->fetchColumn()>0;
     if($hasAccess){$st=$pdo->prepare('SELECT COUNT(*) FROM shop_digital_access WHERE file_id=?');$st->execute([$fileId]);if((int)$st->fetchColumn()>0)throw new RuntimeException('Soubor je navázán na zákaznické digitální přístupy. Nejprve je vyřešte.');}
     $dir=rac_private_storage_dir();$path=$dir.DIRECTORY_SEPARATOR.$file['storage_key'];$trash=$dir.DIRECTORY_SEPARATOR.bin2hex(random_bytes(24)).'.trash';
     if(!is_file($path)||!rename($path,$trash))throw new RuntimeException('Soubor není dostupný pro odstranění.');
     try{$pdo->beginTransaction();$st=$pdo->prepare('DELETE FROM product_files WHERE id=?');$st->execute([$fileId]);$pdo->commit();@unlink($trash);$ok='Soubor byl odstraněn.';}
     catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();@rename($trash,$path);throw $e;}
    }catch(Throwable $e){error_log('RAC files delete: '.$e->getMessage());$errors[]=$e->getMessage();}
   }
  }
 }
}
$st=$pdo->prepare('SELECT f.id,f.variant_id,f.purpose,f.media_type,f.original_filename,f.file_size,f.created,v.kind FROM product_files f JOIN product_variants v ON v.id=f.variant_id WHERE v.product_id=? ORDER BY f.created DESC,f.id DESC');$st->execute([$id]);$files=$st->fetchAll(PDO::FETCH_ASSOC);
$racUsePortoLayout=true;$base='/administrace';$assets=$base;$pageTitle='Digitální soubory knih';$title=$pageTitle.' | RAC';$viewer=current_user_row();$viewerName=(string)($viewer['username']??'Administrátor');
$e=static function($v):string{return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');};
$menuGroups=[['Přehled',[['Dashboard','/administrace/dashboard.php','bx bx-home-alt']]],['Administrace',[['Jazyky','/administrace/jazyky.php','icons icon-globe']]],['Obsah webu',[['Úvod','/administrace/obsah-uvod.php','icons icon-home']]],['E-shop',[['Produkty','/administrace/eshop-produkty.php','icons icon-basket'],['Doprava','/administrace/eshop-doprava.php','icons icon-drawer']]],['Platební brána',[['Nastavení Stripe','/administrace/billing/admin.php','el el-cogs']]]];
$currentPath='/administrace/eshop-produkty.php';$viewerRole=(string)($viewer['role']??'');$roleLabel=function_exists('role_label')?role_label($viewerRole):$viewerRole;
include __DIR__.'/inc/header.class.php';include __DIR__.'/inc/leve-meny.class.php';
?>
<section role="main" class="content-body content-body-modern mt-0">
<header class="page-header"><h2>Digitální soubory knih</h2></header>
<div class="row mt-4"><div class="col-12">
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3"><div><h4 class="mb-1"><?= $e($product['slug']) ?></h4><p class="text-muted mb-0">Plné soubory jsou určeny pro zaplacené objednávky. Ukázky se zatím veřejně neposkytují.</p></div><a href="eshop-produkt.php?id=<?= $id ?>" class="btn btn-default"><i class="fas fa-arrow-left me-1"></i> Zpět na produkt</a></div>
<?php foreach($errors as $err): ?><div class="alert alert-danger"><?= $e($err) ?></div><?php endforeach; ?>
<?php if($ok!==''): ?><div class="alert alert-success"><?= $e($ok) ?></div><?php endif; ?>
<section id="nahrat-soubor" class="card mb-4"><header class="card-header"><h2 class="card-title">Vložit nebo upravit digitální soubor</h2></header><div class="card-body">
<?php if(!$variants): ?><div class="alert alert-info">Nejdříve uložte u produktu e-knihu nebo audioknihu.</div><?php else: ?>
<form method="post" enctype="multipart/form-data" class="row g-3">
<input type="hidden" name="csrf" value="<?= $e($csrf) ?>"><input type="hidden" name="action" value="upload">
<div class="col-12 col-lg-4"><label class="form-label">Varianta knihy</label><select class="form-select" name="variant_id" id="rac-file-variant" required><?php foreach($variants as $v): ?><option data-kind="<?= $e($v['kind']) ?>" value="<?= (int)$v['id'] ?>" <?= $selectedVariantId===(int)$v['id']?'selected':'' ?>><?= $v['kind']==='ebook'?'E-kniha':'Audiokniha' ?><?= (int)$v['active']!==1?' (neaktivní)':'' ?></option><?php endforeach; ?></select></div>
<div class="col-12 col-lg-4"><label class="form-label">Účel</label><select class="form-select" name="purpose"><option value="paid" <?= $selectedPurpose==='paid'?'selected':'' ?>>Plná verze (placená)</option><option value="preview" <?= $selectedPurpose==='preview'?'selected':'' ?>>Ukázka</option></select></div>
<div class="col-12 col-lg-4"><label class="form-label">Formát</label><select class="form-select" name="media_type" id="rac-file-media"><option value="pdf">PDF</option><option value="epub">EPUB</option></select></div>
<div class="col-12"><label class="form-label">Soubor (max. 500 MiB, podle limitu hostingu)</label><input class="form-control" type="file" name="digital_file" required accept=".pdf,.epub,.mp3,.m4a"></div>
<div class="col-12"><div class="small text-muted mb-2">Při úpravě se nahraje nový soubor jako další verze. Již vydané odkazy na původní soubor zůstanou funkční; starší verzi lze odstranit, pokud není vázána na zákaznický přístup.</div><button class="btn btn-primary" type="submit"><i class="fas fa-upload me-1"></i> Nahrát soubor</button></div>
</form><?php endif; ?>
</div></section>
<section class="card mb-4"><header class="card-header"><h2 class="card-title">Nahrané soubory</h2></header><div class="card-body">
<?php if(!$files): ?><p class="text-muted mb-0">Dosud nebyly nahrány žádné soubory.</p><?php else: ?><div class="table-responsive"><table class="table table-bordered table-striped align-middle"><thead><tr><th>Varianta</th><th>Účel</th><th>Soubor</th><th>Velikost</th><th>Akce</th></tr></thead><tbody>
<?php foreach($files as $f): ?><tr><td><?= $f['kind']==='ebook'?'E-kniha':'Audiokniha' ?></td><td><?= $f['purpose']==='paid'?'Plná verze':'Ukázka' ?></td><td><?= $e($f['original_filename']) ?> <small class="text-muted">(<?= $e(strtoupper($f['media_type'])) ?>)</small></td><td><?= number_format((int)$f['file_size']/1048576,2,',',' ') ?> MiB</td><td><form method="post" onsubmit="return confirm('Opravdu trvale odstranit soubor?');"><input type="hidden" name="csrf" value="<?= $e($csrf) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="file_id" value="<?= (int)$f['id'] ?>"><button class="btn btn-sm btn-danger" type="submit"><i class="fas fa-trash"></i> Odstranit</button></form></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?></div></section>
<div class="alert alert-info">Plné soubory budou zákazníkům dostupné pouze přes odkazy digitálních přístupů po potvrzení platby. Ukázky nyní evidujeme, jejich veřejné zpřístupnění doplníme zvlášť.</div>
</div></div></section>
<script>(function(){var v=document.getElementById('rac-file-variant'),m=document.getElementById('rac-file-media');if(!v||!m)return;function update(){var opt=v.options[v.selectedIndex],kind=opt?opt.getAttribute('data-kind'):'ebook',formats=kind==='audio'?['mp3','m4a']:['pdf','epub'],previous=m.value;m.innerHTML='';formats.forEach(function(fmt){var o=document.createElement('option');o.value=fmt;o.textContent=fmt.toUpperCase();m.appendChild(o)});if(formats.indexOf(previous)!==-1)m.value=previous;}v.addEventListener('change',update);update();})();</script>
<?php include __DIR__.'/inc/footer.class.php'; ?>
