<?php
// RAC bezpecne nahravani po castech | verze 2026-10-22-01 | PHP 7.4+
declare(strict_types=1);
require_once __DIR__.'/config/session.php';
require_once __DIR__.'/config/db.php';
require_once __DIR__.'/config/auth.php';
require_once __DIR__.'/config/auth_roles.php';
require_once __DIR__.'/config/digital_storage.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
function racChunkReply(bool $ok,array $values=[],int $status=200):void {
 http_response_code($status);
 echo json_encode(array_merge(['ok'=>$ok],$values),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
 exit;
}
function racChunkFail(string $message,int $status=400):void {racChunkReply(false,['error'=>$message],$status);}
function racChunkStorage():string {
 $dir=rac_private_storage_dir();
 if(!is_dir($dir)||!is_writable($dir))throw new RuntimeException('Privatni uloziste neni dostupne pro zapis.');
 return $dir;
}
function racChunkFileName(string $name,string $format):string {
 $name=basename(str_replace('\\','/',$name));
 if($name===''||strlen($name)>255||strtolower(pathinfo($name,PATHINFO_EXTENSION))!==$format||preg_match('/[\x00-\x1f\x7f]/',$name))throw new RuntimeException('Neplatny nazev souboru.');
 return $name;
}
function racChunkVerifyFile(string $path,string $format):string {
 $mime=(string)(new finfo(FILEINFO_MIME_TYPE))->file($path);
 $mimes=[
  'pdf'=>['application/pdf'],'epub'=>['application/epub+zip','application/zip','application/octet-stream'],
  'mp3'=>['audio/mpeg','audio/mp3','application/octet-stream'],
  'm4a'=>['audio/mp4','audio/x-m4a','video/mp4','application/octet-stream']
 ];
 if(!in_array($mime,$mimes[$format]??[],true))throw new RuntimeException('Typ souboru neodpovida zvolenemu formatu ('.$mime.').');
 $fd=fopen($path,'rb');if(!$fd)throw new RuntimeException('Nelze zkontrolovat soubor.');
 $head=(string)fread($fd,12);fclose($fd);
 if(($format==='pdf'&&substr($head,0,5)!=='%PDF-')||
    ($format==='epub'&&substr($head,0,2)!=='PK')||
    ($format==='mp3'&&substr($head,0,3)!=='ID3'&&substr($head,0,1)!=="\xFF")||
    ($format==='m4a'&&substr($head,4,4)!=='ftyp'))throw new RuntimeException('Soubor nema platnou hlavicku.');
 return $mime;
}
try {
 if(($_SERVER['REQUEST_METHOD']??'')!=='POST')racChunkFail('Povolena je pouze metoda POST.',405);
 require_min_role('admin');
 $viewer=current_user_row();
 if(!$viewer||!rac_can($pdo,$viewer,'products.edit'))racChunkFail('Nedostatecne opravneni.',403);
 if(!csrf_valid('rac_publication',(string)($_POST['csrf']??'')))racChunkFail('Neplatny bezpecnostni token.',403);
 $productId=(int)($_GET['id']??0);$variantId=(int)($_GET['variant_id']??0);
 if($productId<1||$variantId<1)racChunkFail('Chybi produkt nebo vydani.');
 $st=$pdo->prepare("SELECT v.id,v.kind,v.language_code FROM product_variants v WHERE v.product_id=? AND v.id=? AND v.kind IN ('ebook','audio') LIMIT 1");
 $st->execute([$productId,$variantId]);$variant=$st->fetch(PDO::FETCH_ASSOC);
 if(!$variant || trim((string)($variant['language_code']??''))==='')racChunkFail('Jazykove digitalni vydani neexistuje.',404);
 $st=$pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='product_files' AND column_name='is_published'");
 if((int)$st->fetchColumn()!==1)racChunkFail('Chybi migrace 021.',503);
 if(session_status()!==PHP_SESSION_ACTIVE)session_start();
 $action=(string)($_POST['action']??'');
 $uploads=&$_SESSION['rac_digital_chunk_uploads'];
 if(!is_array($uploads))$uploads=[];
 // Release abandoned files for this admin after 24 hours.
 foreach($uploads as $oldToken=>$old){
  if(!is_array($old)||time()-(int)($old['started']??0)>86400){
   if(is_array($old)&&!empty($old['path'])&&is_string($old['path'])&&strpos(basename($old['path']),'.racpart-')===0)@unlink($old['path']);
   unset($uploads[$oldToken]);
  }
 }
 $storage=racChunkStorage();
 if($action==='start'){
  if(count($uploads)>=3)racChunkFail('Nejprve dokoncete nebo zrušte rozpracovana nahravani.');
  $purpose=(string)($_POST['purpose']??'');$format=(string)($_POST['media_type']??'');
  $formats=['ebook'=>['pdf','epub'],'audio'=>['mp3','m4a']];
  $size=filter_var($_POST['size']??null,FILTER_VALIDATE_INT);
  if(!in_array($purpose,['paid','preview'],true)||!in_array($format,$formats[$variant['kind']],true)||$size===false||$size<1||$size>500*1048576)racChunkFail('Neplatny format, ucel nebo velikost (max. 500 MiB).');
  $filename=racChunkFileName((string)($_POST['filename']??''),$format);
  $token=bin2hex(random_bytes(24));$key=bin2hex(random_bytes(24));
  $path=$storage.DIRECTORY_SEPARATOR.'.racpart-'.$key;
  $f=fopen($path,'xb');if(!$f)throw new RuntimeException('Nelze vytvorit rozpracovany soubor.');
  fclose($f);@chmod($path,0600);
  $uploads[$token]=['product_id'=>$productId,'variant_id'=>$variantId,'purpose'=>$purpose,'format'=>$format,'filename'=>$filename,
    'size'=>$size,'path'=>$path,'key'=>$key,'next'=>0,'received'=>0,'started'=>time()];
  racChunkReply(true,['token'=>$token,'chunk_size'=>8*1048576]);
 }
 $token=(string)($_POST['token']??'');
 if(!preg_match('/^[a-f0-9]{48}$/D',$token)||!isset($uploads[$token]))racChunkFail('Relace nahravani nebyla nalezena. Zacnete znovu.',404);
 $u=&$uploads[$token];
 if((int)$u['product_id']!==$productId||(int)$u['variant_id']!==$variantId)racChunkFail('Neodpovidajici produkt nebo jazykove vydani.',403);
 $path=(string)$u['path'];
 if(!is_file($path))racChunkFail('Rozpracovany soubor byl odstranen. Zacnete znovu.',410);
 if($action==='cancel'){
  @unlink($path);unset($uploads[$token]);racChunkReply(true,['message'=>'Nahravani zruseno.']);
 }
 if($action==='chunk'){
  $index=filter_var($_POST['index']??null,FILTER_VALIDATE_INT);
  if($index===false||$index!==(int)$u['next'])racChunkFail('Spatne poradi casti, spustte nahravani znovu.');
  $f=$_FILES['chunk']??null;
  if(!is_array($f)||(int)($f['error']??-1)!==UPLOAD_ERR_OK||!is_uploaded_file((string)($f['tmp_name']??'')))racChunkFail('Chybi blok souboru nebo prekrocen limit PHP.');
  $size=(int)$f['size'];
  $remaining=(int)$u['size']-(int)$u['received'];
  if($size<1||$size>8*1048576||$size> $remaining)racChunkFail('Neplatna velikost bloku.');
  if($remaining>8*1048576 && $size!==8*1048576)racChunkFail('Neuplny blok, opakujte nahravani.');
  $src=fopen((string)$f['tmp_name'],'rb');$dst=fopen($path,'ab');
  if(!$src||!$dst)throw new RuntimeException('Nelze zapisovat cast souboru.');
  $written=stream_copy_to_stream($src,$dst);fflush($dst);fclose($src);fclose($dst);
  if($written!==$size){@unlink($path);unset($uploads[$token]);racChunkFail('Nepodarilo se ulozit celou cast. Zacnete znovu.',500);}
  $u['received']+=(int)$written;$u['next']++;
  racChunkReply(true,['received'=>$u['received'],'size'=>$u['size']]);
 }
 if($action==='finish'){
  if((int)$u['received']!==(int)$u['size']||filesize($path)!==(int)$u['size'])racChunkFail('Soubor neni kompletni.');
  $mime=racChunkVerifyFile($path,(string)$u['format']);
  $final=$storage.DIRECTORY_SEPARATOR.$u['key'];
  if(file_exists($final)||!rename($path,$final))throw new RuntimeException('Nelze dokoncit ulozeni souboru.');
  @chmod($final,0600);
  try {
   $pdo->beginTransaction();
   $ins=$pdo->prepare('INSERT INTO product_files(variant_id,purpose,is_published,media_type,storage_key,original_filename,mime_type,file_size) VALUES (?,?,0,?,?,?,?,?)');
   $ins->execute([$variantId,$u['purpose'],$u['format'],$u['key'],$u['filename'],$mime,$u['size']]);
   $fileId=(int)$pdo->lastInsertId();
   if($u['purpose']==='preview'){
    $pdo->prepare("UPDATE product_files SET is_published=0 WHERE variant_id=? AND purpose='preview'")->execute([$variantId]);
    $pdo->prepare("UPDATE product_files SET is_published=1 WHERE id=? AND variant_id=? AND purpose='preview'")->execute([$fileId,$variantId]);
   }
   $pdo->commit();
  } catch(Throwable $e){
   if($pdo->inTransaction())$pdo->rollBack();
   @unlink($final);
   throw $e;
  }
  $purpose=$u['purpose'];unset($uploads[$token]);
  racChunkReply(true,['message'=>$purpose==='preview'?'Ukazka nahrana a publikovana.':'Plna verze nahrana. Nyni ji publikujte v seznamu souboru.']);
 }
 racChunkFail('Neznama operace.');
} catch(Throwable $ex) {
 error_log('RAC chunk upload: '.$ex->getMessage());
 racChunkFail($ex->getMessage(),500);
}
