<?php
// RAC 2026-10-21-UKAZKY-06 | Public preview only, PHP 7.4+
declare(strict_types=1);
require_once __DIR__.'/inc/01.config.class.php';
// Storage configuration must not require a logged-in admin.
require_once __DIR__.'/administrace/config/digital_storage.php';
// Omezeny verejny pristup POUZE ke zverejnenym ukazkam. Parametr download=1 vynuti stazeni.
$download=isset($_GET['download']) && (string)$_GET['download']==='1';
if(!in_array(($_SERVER['REQUEST_METHOD']??'GET'),['GET','HEAD'],true)){http_response_code(405);exit;}
$id=filter_var($_GET['variant_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
if(!$id){http_response_code(404);exit;}
try {
 $db=racFrontendPdo();
 if(!$db instanceof PDO)throw new RuntimeException('DB unavailable');
 $q=$db->prepare("SELECT f.storage_key,f.media_type,f.file_size,f.original_filename FROM product_files f JOIN product_variants v ON v.id=f.variant_id JOIN products p ON p.id=v.product_id WHERE f.variant_id=? AND f.purpose='preview' AND f.is_published=1 AND v.active=1 AND p.status='active' AND v.kind IN ('ebook','audio') ORDER BY f.id DESC LIMIT 1");
 $q->execute([(int)$id]);$file=$q->fetch(PDO::FETCH_ASSOC);
 if(!$file) {http_response_code(404);exit;}
 $key=(string)$file['storage_key'];
 if(!preg_match('/^[a-f0-9]{48}$/D',$key))throw new RuntimeException('Invalid storage key');
 $dir=realpath(rac_private_storage_dir());
 if(!$dir)throw new RuntimeException('Storage missing');
 $path=realpath($dir.DIRECTORY_SEPARATOR.$key);
 if(!$path || dirname($path)!==$dir || !is_file($path) || !is_readable($path)) {http_response_code(404);exit;}
 $types=['pdf'=>'application/pdf','epub'=>'application/epub+zip','mp3'=>'audio/mpeg','m4a'=>'audio/mp4'];
 $media=(string)$file['media_type'];
 if(!isset($types[$media])) {http_response_code(404);exit;}
 $size=(int)filesize($path);
 if($size<=0) {http_response_code(404);exit;}
 $start=0;$end=$size-1;$status=200;
 $range=(string)($_SERVER['HTTP_RANGE']??'');
 if($range!==''){
  if(!preg_match('/^bytes=(\d*)-(\d*)$/D',$range,$m)||($m[1]==='' && $m[2]==='')){header('Content-Range: bytes */'.$size);http_response_code(416);exit;}
  if($m[1]===''){$suffix=(int)$m[2];if($suffix<1){header('Content-Range: bytes */'.$size);http_response_code(416);exit;}$start=max(0,$size-$suffix);}
  else{$start=(int)$m[1];$end=$m[2]===''?$size-1:min($size-1,(int)$m[2]);}
  if($start>=$size||$start>$end){header('Content-Range: bytes */'.$size);http_response_code(416);exit;}
  $status=206;
 }
 if(session_status()===PHP_SESSION_ACTIVE)session_write_close();
 while(ob_get_level()>0)ob_end_clean();
 http_response_code($status);
 header('X-Content-Type-Options: nosniff');
 header("Content-Security-Policy: frame-ancestors 'self'");
 header('X-Frame-Options: SAMEORIGIN');
 header('Cache-Control: public, max-age=300');
 header('Accept-Ranges: bytes');
 header('Content-Type: '.$types[$media]);
 header('Content-Disposition: '.(($download||$media==='epub')?'attachment':'inline').'; filename="ukazka.'.$media.'"');
 header('Content-Length: '.($end-$start+1));
 if($status===206)header('Content-Range: bytes '.$start.'-'.$end.'/'.$size);
 if(($_SERVER['REQUEST_METHOD']??'GET')==='HEAD')exit;
 $fp=fopen($path,'rb');if(!$fp)exit;
 fseek($fp,$start);$remaining=$end-$start+1;
 while($remaining>0 && !feof($fp) && !connection_aborted()){$chunk=fread($fp,min(65536,$remaining));if($chunk===false||$chunk==='')break;echo $chunk;$remaining-=strlen($chunk);flush();}
 fclose($fp);
} catch(Throwable $e){error_log('RAC public preview: '.$e->getMessage());if(!headers_sent())http_response_code(404);}
