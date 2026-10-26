<?php
// RAC / sprava publikovanych digitalnich souboru / 2026-10-25-UPLOAD-PRG-01 / PHP 7.4+ / chunk upload 8 MiB / diagnostika upload limitu | oprava Porto header $e
declare(strict_types=1);
require_once __DIR__.'/config/session.php';
require_once __DIR__.'/config/db.php';
require_once __DIR__.'/config/auth.php';
require_once __DIR__.'/config/auth_roles.php';
require_once __DIR__.'/config/digital_storage.php';
require_min_role('admin');
$viewer=current_user_row();
if (!$viewer || !rac_can($pdo,$viewer,'products.edit')) {http_response_code(403);exit('Nedostatecne opravneni.');}
$esc=static function($v):string{return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');};
$id=max(0,(int)($_GET['id']??0));$variantId=max(0,(int)($_GET['variant_id']??0));
$st=$pdo->prepare("SELECT v.id,v.kind,v.language_code,v.active,p.slug FROM product_variants v JOIN products p ON p.id=v.product_id WHERE p.id=? AND v.id=? AND v.kind IN ('ebook','audio') LIMIT 1");
$st->execute([$id,$variantId]);$variant=$st->fetch(PDO::FETCH_ASSOC);
if (!$variant){http_response_code(404);exit('Digitalni vydani nenalezeno.');}
$ready=(int)$pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='product_files' AND column_name='is_published'")->fetchColumn()===1;
$csrf=csrf_token('rac_publication');$errors=[];$success='';
// Post/Redirect/Get: success messages survive the redirect without repeating POST.
$racFlashKey='rac_edition_files_success_'.$id.'_'.$variantId;
if(($_SERVER['REQUEST_METHOD']??'GET')==='GET') {
 if(isset($_SESSION[$racFlashKey]) && is_string($_SESSION[$racFlashKey])) {
  $success=$_SESSION[$racFlashKey];
  unset($_SESSION[$racFlashKey]);
 } elseif(isset($_GET['uploaded']) && $_GET['uploaded']==='1') {
  $success='Soubor byl uspesne nahran. Aktualni stav je zobrazen v seznamu souboru.';
 }
} 
$allowed=['ebook'=>['pdf','epub'],'audio'=>['mp3','m4a']];
$mimeTypes=['pdf'=>['application/pdf'],'epub'=>['application/epub+zip','application/zip','application/octet-stream'],'mp3'=>['audio/mpeg','audio/mp3','application/octet-stream'],'m4a'=>['audio/mp4','audio/x-m4a','video/mp4','application/octet-stream']];
// Kdyz PHP zahodi telo velkeho POSTu, chybi take CSRF a puvodni chyba je zavadejici.
function racUploadIniBytes(string $value): int {
 $value=trim($value);
 if($value==='' || $value==='-1')return 0;
 if(!preg_match('/^([0-9]+(?:\.[0-9]+)?)\s*([KMGT]?)B?$/iD',$value,$m))return 0;
 $multiplier=[''=>1,'K'=>1024,'M'=>1048576,'G'=>1073741824,'T'=>1099511627776];
 return (int)floor((float)$m[1]*$multiplier[strtoupper($m[2])]);
}
$racPostLimit=racUploadIniBytes((string)ini_get('post_max_size'));
$racFileLimit=racUploadIniBytes((string)ini_get('upload_max_filesize'));
$racInputLength=filter_var($_SERVER['CONTENT_LENGTH']??null,FILTER_VALIDATE_INT);

if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
 try{
  if(!$ready)throw new RuntimeException('Nejdrive spustte SQL migraci 021.');
  // Pred CSRF testem osetrit telo requestu, ktere PHP pri prekroceni post_max_size cele odmitne.
  if($racPostLimit>0 && $racInputLength!==false && $racInputLength!==null && $racInputLength>$racPostLimit) {
   throw new RuntimeException('Soubor je prilis velky pro nastaveni serveru: odeslano '.number_format($racInputLength/1048576,1,',',' ').' MiB, limit post_max_size je '.ini_get('post_max_size').'. Zmente limit PHP nebo nahrajte mensi soubor.');
  }
  if(empty($_POST) && empty($_FILES)) {
   throw new RuntimeException('Server neprijal data formulare. Zkontrolujte limity post_max_size, upload_max_filesize a max_input_time. Aktualni limity: post_max_size='.ini_get('post_max_size').', upload_max_filesize='.ini_get('upload_max_filesize').'.');
  }
  if(!csrf_valid('rac_publication',(string)($_POST['csrf']??'')))throw new RuntimeException('Neplatny bezpecnostni token. Obnovte formular a zkuste odeslani znovu.');
  $action=(string)($_POST['action']??'');
  if($action==='upload'){
   $purpose=(string)($_POST['purpose']??'');$format=(string)($_POST['media_type']??'');$f=$_FILES['file']??null;
   if(!in_array($purpose,['paid','preview'],true)||!in_array($format,$allowed[$variant['kind']],true))throw new RuntimeException('Neplatny format nebo ucel.');
   $uploadError=is_array($f)?(int)($f['error']??UPLOAD_ERR_NO_FILE):UPLOAD_ERR_NO_FILE;
   if($uploadError===UPLOAD_ERR_INI_SIZE)throw new RuntimeException('Soubor presahuje upload_max_filesize ('.ini_get('upload_max_filesize').').');
   if($uploadError===UPLOAD_ERR_FORM_SIZE)throw new RuntimeException('Soubor presahuje limit velikosti formulare.');
   if($uploadError===UPLOAD_ERR_PARTIAL)throw new RuntimeException('Soubor se prenesl pouze castecne. Zkuste nahrani opakovat.');
   if($uploadError!==UPLOAD_ERR_OK || !is_array($f) || !is_uploaded_file((string)($f['tmp_name']??'')))throw new RuntimeException('Soubor se nepodarilo nahrat. Overte PHP limity a pripojeni.');
   if((int)$f['size']<1||(int)$f['size']>500*1024*1024)throw new RuntimeException('Velikost souboru musi byt 1 B az 500 MiB.');
   $filename=basename(str_replace('\\','/',(string)$f['name']));
   if($filename===''||strlen($filename)>255||strtolower(pathinfo($filename,PATHINFO_EXTENSION))!==$format)throw new RuntimeException('Neplatny nazev souboru.');
   $mime=(string)(new finfo(FILEINFO_MIME_TYPE))->file((string)$f['tmp_name']);
   if(!in_array($mime,$mimeTypes[$format],true))throw new RuntimeException('Typ souboru neodpovida formatu.');
   $handle=fopen((string)$f['tmp_name'],'rb');$sig=$handle?(string)fread($handle,12):'';if($handle)fclose($handle);
   if(($format==='pdf'&&substr($sig,0,5)!=='%PDF-')||($format==='epub'&&substr($sig,0,2)!=='PK')||($format==='mp3'&&substr($sig,0,3)!=='ID3'&&substr($sig,0,1)!=="\xFF")||($format==='m4a'&&substr($sig,4,4)!=='ftyp'))throw new RuntimeException('Neplatna hlavicka souboru.');
   $folder=rac_private_storage_dir();$key=bin2hex(random_bytes(24));$target=$folder.DIRECTORY_SEPARATOR.$key;
   if(!move_uploaded_file((string)$f['tmp_name'],$target))throw new RuntimeException('Nepodarilo se ulozit soubor.');
   @chmod($target,0600);
   try{
    $st=$pdo->prepare('INSERT INTO product_files(variant_id,purpose,is_published,media_type,storage_key,original_filename,mime_type,file_size) VALUES (?,?,0,?,?,?,?,?)');
    $st->execute([$variantId,$purpose,$format,$key,$filename,$mime,(int)$f['size']]);
   }catch(Throwable $e){@unlink($target);throw $e;}
   if($purpose==='preview') {
     // Jen jedna publikovana ukazka pro konkretni vydani, nikdy se nezverejni placeny soubor.
     $newId=(int)$pdo->lastInsertId();
     $pdo->beginTransaction();
     try {
      $pdo->prepare("UPDATE product_files SET is_published=0 WHERE variant_id=? AND purpose='preview'")->execute([$variantId]);
      $pdo->prepare("UPDATE product_files SET is_published=1 WHERE id=? AND variant_id=? AND purpose='preview'")->execute([$newId,$variantId]);
      $pdo->commit();
      $success='Ukazka byla nahrana a automaticky publikovana na detailu knihy.';
     } catch(Throwable $e) { if($pdo->inTransaction())$pdo->rollBack(); throw $e; }
    } else {
     $success='Plny soubor nahran. Pro prodej jej jeste publikujte pomoci tlacitka v seznamu souboru.';
    }
  }elseif($action==='publish'){
   $fileId=max(0,(int)($_POST['file_id']??0));
   $pdo->beginTransaction();
   try{
    $st=$pdo->prepare("SELECT id,storage_key,media_type FROM product_files WHERE id=? AND variant_id=? AND purpose='paid' FOR UPDATE");$st->execute([$fileId,$variantId]);$file=$st->fetch(PDO::FETCH_ASSOC);
    if(!$file)throw new RuntimeException('Pro toto vydani nebyl nalezen platny soubor.');
    if(!preg_match('/^[a-f0-9]{48}$/D',(string)$file['storage_key'])||!is_readable(rac_private_storage_dir().DIRECTORY_SEPARATOR.$file['storage_key']))throw new RuntimeException('Soubor na disku chybi nebo neni citelny.');
    $pdo->prepare("UPDATE product_files SET is_published=0 WHERE variant_id=? AND purpose='paid' AND media_type=?")->execute([$variantId,(string)$file['media_type']]);
    $pdo->prepare("UPDATE product_files SET is_published=1 WHERE id=? AND variant_id=? AND purpose='paid'")->execute([$fileId,$variantId]);
    $pdo->commit();$success='Publikovan aktualni soubor vybraneho formatu. Druhy format zustal zachovan.';
   }catch(Throwable $e){$pdo->rollBack();throw $e;}
  }elseif($action==='publish_preview' || $action==='unpublish_preview') {
   $fileId=max(0,(int)($_POST['file_id']??0));
   $pdo->beginTransaction();
   try {
    $st=$pdo->prepare("SELECT id,storage_key FROM product_files WHERE id=? AND variant_id=? AND purpose='preview' FOR UPDATE");
    $st->execute([$fileId,$variantId]);$file=$st->fetch(PDO::FETCH_ASSOC);
    if(!$file)throw new RuntimeException('Ukazka nebyla nalezena.');
    if($action==='publish_preview' && (!preg_match('/^[a-f0-9]{48}$/D',(string)$file['storage_key']) || !is_readable(rac_private_storage_dir().DIRECTORY_SEPARATOR.$file['storage_key'])))throw new RuntimeException('Ukazka neni na disku dostupna.');
    $pdo->prepare("UPDATE product_files SET is_published=0 WHERE variant_id=? AND purpose='preview'")->execute([$variantId]);
    if($action==='publish_preview')$pdo->prepare("UPDATE product_files SET is_published=1 WHERE id=? AND variant_id=? AND purpose='preview'")->execute([$fileId,$variantId]);
    $pdo->commit();
    $success=$action==='publish_preview'?'Ukazka je publikovana na detailu knihy.':'Ukazka byla skryta z webu.';
   }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
  }elseif($action==='unpublish'){
   // Prevent further digital orders when no file is published.
   $pdo->beginTransaction();try{
    $fileId=max(0,(int)($_POST['file_id']??0));
    $st=$pdo->prepare("SELECT id FROM product_files WHERE id=? AND variant_id=? AND purpose='paid' AND is_published=1 FOR UPDATE");
    $st->execute([$fileId,$variantId]);
    if(!$st->fetchColumn())throw new RuntimeException('Publikovany soubor nebyl nalezen.');
    $pdo->prepare("UPDATE product_files SET is_published=0 WHERE id=? AND variant_id=? AND purpose='paid'")->execute([$fileId,$variantId]);
    $check=$pdo->prepare("SELECT COUNT(*) FROM product_files WHERE variant_id=? AND purpose='paid' AND is_published=1");
    $check->execute([$variantId]);
    if((int)$check->fetchColumn()===0)$pdo->prepare('UPDATE product_variants SET active=0 WHERE id=?')->execute([$variantId]);
    $pdo->commit();$success='Format byl stazen z prodeje. Pokud nezbyl zadny publikovany plny soubor, bylo vydani deaktivovano. Drive vydane odkazy zustavaji platne.';
   }catch(Throwable $e){$pdo->rollBack();throw $e;}
  }else throw new RuntimeException('Neznama operace.');
  // Po uspesne akci nezobrazovat odpoved na POST; Firefox by pri reloadu
  // nabizel opakovani predchozi operace (publikovani/skryti souboru).
  $_SESSION[$racFlashKey]=$success;
  header('Location: eshop-soubory-vydani.php?id='.$id.'&variant_id='.$variantId, true, 303);
  exit;
 }catch(Throwable $e){error_log('RAC edition files: '.$e->getMessage());$errors[]=$e->getMessage();}
}
$files=[];
if($ready){$st=$pdo->prepare('SELECT id,purpose,is_published,media_type,storage_key,original_filename,file_size,created FROM product_files WHERE variant_id=? ORDER BY id DESC');$st->execute([$variantId]);$files=$st->fetchAll(PDO::FETCH_ASSOC);}
$racUsePortoLayout=true;$base='/administrace';$assets=$base;$pageTitle='Soubory jazykoveho vydani';$title=$pageTitle.' | RAC';
$viewerName=(string)($viewer['username']??'Administrator');$viewerRole=(string)($viewer['role']??'');$roleLabel=function_exists('role_label')?role_label($viewerRole):$viewerRole;
$currentPath='/administrace/eshop-produkty.php';$menuGroups=[];
// Sdilena Porto hlavicka pouziva $e($title) a dalsi volani.
// Porto header vyzaduje callable $e; definovat bezprostredne pred include.
$e=static function($value):string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); };
include __DIR__.'/inc/header.class.php';include __DIR__.'/inc/leve-meny.class.php';
?>
<section role="main" class="content-body content-body-modern mt-0"><header class="page-header"><h2>Digitální soubory – <?= $esc($variant['slug']) ?></h2></header><div class="row mt-4"><div class="col-12">
<div class="alert alert-info">U e-knihy můžete publikovat současně <strong>PDF i EPUB</strong>. Nová publikace nahradí pouze starší soubor stejného formátu. Zákazník při jediném nákupu obdrží oba publikované formáty.</div>
<p><a class="btn btn-default" href="eshop-produkt.php?id=<?= $id ?>">← Zpět na produkt</a></p>
<section class="card mb-4"><header class="card-header"><h2 class="card-title"><?= $variant['kind']==='ebook'?'E-kniha':'Audiokniha' ?> · jazyk <?= $esc($variant['language_code']??'neurčen') ?></h2></header><div class="card-body"><p class="text-muted">Ukázka se na produktu zobrazí jen u svého jazyka a formátu. Soubor je pevně spojen s ID jazykového vydání #<?= $variantId ?>. Jiné jazyky ani jiné formáty se nemíchají.</p>
<?php if(!$ready): ?><div class="alert alert-danger">Chybí SQL migrace 021. Nahrajte ji a spusťte před používáním správy souborů.</div><?php endif; ?>
<div class="alert alert-info">Limity serveru pro nahrávání: <strong>upload_max_filesize = <?= $esc((string)ini_get('upload_max_filesize')) ?></strong>, <strong>post_max_size = <?= $esc((string)ini_get('post_max_size')) ?></strong>. Nahrávání po 8MB částech umožňuje i velké audioknihy při těchto limitech.</div>
<?php foreach($errors as $error): ?><div class="alert alert-danger"><?= $esc($error) ?></div><?php endforeach; ?>
<?php if($success!==''): ?><div class="alert alert-success"><?= $esc($success) ?></div><?php endif; ?>
<?php if($ready): ?><form id="rac-digital-upload-form" method="post" enctype="multipart/form-data" class="row g-3"><input type="hidden" name="csrf" value="<?= $esc($csrf) ?>"><input type="hidden" name="action" value="upload">
<div class="col-md-4"><label class="form-label">Účel</label><select class="form-select" name="purpose"><option value="paid">Plná verze (placená)</option><option value="preview">Ukázka – veřejná na detailu produktu</option></select></div>
<div class="col-md-4"><label class="form-label">Formát</label><select class="form-select" name="media_type"><?php foreach($allowed[$variant['kind']] as $format): ?><option value="<?= $esc($format) ?>"><?= strtoupper($esc($format)) ?></option><?php endforeach; ?></select></div>
<div class="col-md-4"><label class="form-label">Soubor (max 500 MiB)</label><input type="file" name="file" class="form-control" required></div>
<div class="col-12"><button class="btn btn-primary" type="submit" id="rac-upload-button">Nahrát soubor</button>
<div id="rac-upload-status" class="small mt-2" role="status" aria-live="polite"></div>
<progress id="rac-upload-progress" value="0" max="100" style="display:none;width:100%;height:18px;margin-top:8px"></progress></div>
</form><?php endif; ?></div></section>
<section class="card mb-4"><header class="card-header"><h2 class="card-title">Nahrané soubory</h2></header><div class="card-body table-responsive"><table class="table table-striped"><thead><tr><th>Název</th><th>Typ</th><th>Stav</th><th>Akce</th></tr></thead><tbody>
<?php foreach($files as $file): $available=is_file(rac_private_storage_dir().DIRECTORY_SEPARATOR.$file['storage_key']); ?><tr><td><?= $esc($file['original_filename']) ?></td><td><?= $esc($file['purpose']) ?> / <?= $esc($file['media_type']) ?></td><td><?= !$available?'Na disku chybí':((int)$file['is_published']===1?'Publikovaná':'Nepublikovaná') ?></td><td>
<?php if($file['purpose']==='preview' && $available): ?><form method="post"><input type="hidden" name="csrf" value="<?= $esc($csrf) ?>"><input type="hidden" name="action" value="<?= (int)$file['is_published']===1?'unpublish_preview':'publish_preview' ?>"><input type="hidden" name="file_id" value="<?= (int)$file['id'] ?>"><button type="submit" class="btn btn-sm <?= (int)$file['is_published']===1?'btn-warning':'btn-success' ?>"><?= (int)$file['is_published']===1?'Skrýt ukázku':'Zveřejnit ukázku' ?></button></form><?php elseif($file['purpose']==='paid' && (int)$file['is_published']!==1 && $available): ?><form method="post"><input type="hidden" name="csrf" value="<?= $esc($csrf) ?>"><input type="hidden" name="action" value="publish"><input type="hidden" name="file_id" value="<?= (int)$file['id'] ?>"><button class="btn btn-success btn-sm">Publikovat tento formát</button></form><?php elseif($file['purpose']==='paid' && (int)$file['is_published']===1): ?><form method="post"><input type="hidden" name="csrf" value="<?= $esc($csrf) ?>"><input type="hidden" name="action" value="unpublish"><input type="hidden" name="file_id" value="<?= (int)$file['id'] ?>"><button class="btn btn-warning btn-sm">Skrýt tento formát</button></form><?php endif; ?></td></tr><?php endforeach; ?>
</tbody></table></div></section><div class="alert alert-info">Ukázky jsou veřejně přístupné všem návštěvníkům přes chráněný náhledový endpoint; nevkládejte sem plnou knihu. Starší placené soubory nemažeme, aby fungovaly již vydané odkazy. Nové objednávky smějí používat pouze jednu publikovanou plnou verzi odpovídající ID vydání.</div>
</div></div></section>

<script>
(function () {
 "use strict";
 var form=document.getElementById("rac-digital-upload-form");
 if(!form)return;
 var input=form.querySelector('input[type="file"]');
 var button=document.getElementById("rac-upload-button");
 var bar=document.getElementById("rac-upload-progress");
 var status=document.getElementById("rac-upload-status");
 var maxSize=500*1024*1024,chunkSize=8*1024*1024;
 var endpoint="eshop-chunk-upload.php?id=<?= (int)$id ?>&variant_id=<?= (int)$variantId ?>";
 function setStatus(message,error){status.textContent=message;status.style.color=error?"#c62a36":"";}
 async function send(fields,blob){
  var data=new FormData();
  Object.keys(fields).forEach(function(key){data.append(key,String(fields[key]));});
  data.append("csrf",form.querySelector('[name="csrf"]').value);
  if(blob)data.append("chunk",blob,"part.bin");
  var response=await fetch(endpoint,{method:"POST",body:data,credentials:"same-origin",cache:"no-store"});
  var result;
  try{result=await response.json();}catch(e){throw new Error("Server nevratil JSON (HTTP "+response.status+"). Zkontrolujte omezeni hostingu.");}
  if(!response.ok||!result.ok)throw new Error(result.error||"Nahravani selhalo.");
  return result;
 }
 form.addEventListener("submit",async function(event){
  if(!window.fetch||!window.FormData||!window.File||!File.prototype.slice)return; // standardni maly upload fallback
  event.preventDefault();
  var f=input.files&&input.files[0];
  if(!f){setStatus("Vyberte soubor.",true);return;}
  if(f.size<1||f.size>maxSize){setStatus("Soubor musi mit velikost od 1 B do 500 MiB.",true);return;}
  button.disabled=true;bar.style.display="block";bar.value=0;
  var token=null;
  try {
   setStatus("Zahajuji nahravani...",false);
   var start=await send({action:"start",filename:f.name,size:f.size,purpose:form.querySelector('[name="purpose"]').value,media_type:form.querySelector('[name="media_type"]').value});
   token=start.token;
   var count=Math.ceil(f.size/chunkSize);
   for(var i=0;i<count;i++){
    var begin=i*chunkSize,end=Math.min(begin+chunkSize,f.size);
    var part=f.slice(begin,end);
    await send({action:"chunk",token:token,index:i},part);
    bar.value=Math.round(end/f.size*100);
    setStatus("Odeslano "+bar.value+" % ("+(i+1)+"/"+count+" casti).",false);
   }
   setStatus("Overuji a ukladam kompletni soubor...",false);
   var done=await send({action:"finish",token:token});
   setStatus(done.message||"Soubor uspesne ulozen.",false);
   token=null;
   // Po chunk uploadu vzdy otevrit bezny GET, nikdy neopakovat predchozi POST.
   window.location.replace("eshop-soubory-vydani.php?id=<?= (int)$id ?>&variant_id=<?= (int)$variantId ?>&uploaded=1");
  }catch(e){
   setStatus(e.message||"Nahravani selhalo.",true);
   if(token){try{await send({action:"cancel",token:token});}catch(ignore){}}
  }finally{button.disabled=false;}
 });
})();
</script>

<?php include __DIR__.'/inc/footer.class.php'; ?>
