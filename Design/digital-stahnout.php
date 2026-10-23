<?php
// RAC / controlled private digital delivery, PHP 7.4+
declare(strict_types=1);
require_once __DIR__.'/inc/01.config.class.php';
require_once __DIR__.'/administrace/inc/rac-digital.php';
require_once __DIR__.'/administrace/config/digital_storage.php';
$pdo=racFrontendPdo();
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
$token=(string)($_GET['token']??'');
if(!$pdo instanceof PDO || !preg_match('/^[a-f0-9]{64}$/D',$token)){http_response_code(404);exit('Odkaz není platný.');}
try {
 $stmt=$pdo->prepare("SELECT a.id,a.order_id,a.file_id,a.download_count,a.expires_at,f.storage_key,f.original_filename,f.mime_type
 FROM shop_digital_access a JOIN product_files f ON f.id=a.file_id
 WHERE a.token_hash=? AND a.revoked_at IS NULL AND a.expires_at>NOW() AND f.purpose='paid' LIMIT 1");
 $stmt->execute([hash('sha256',$token)]);$grant=$stmt->fetch(PDO::FETCH_ASSOC);
 if(!$grant || !racDigitalPaid($pdo,(int)$grant['order_id'])){http_response_code(404);exit('Odkaz není platný nebo již vypršel.');}
 $storageKey=(string)$grant['storage_key'];
 if(!preg_match('/^[a-f0-9]{48}$/D',$storageKey))throw new RuntimeException('Invalid storage key');
 $base=rac_private_storage_dir();$path=$base.DIRECTORY_SEPARATOR.$storageKey;
 if(!is_file($path)||!is_readable($path))throw new RuntimeException('Missing purchased file');
 $size=filesize($path);if($size===false)throw new RuntimeException('File size unavailable');
 $fh=fopen($path,'rb');if(!$fh)throw new RuntimeException('Cannot open file');
 $up=$pdo->prepare('UPDATE shop_digital_access SET download_count=download_count+1,last_download_at=NOW() WHERE id=? AND revoked_at IS NULL AND expires_at>NOW()');
 $up->execute([(int)$grant['id']]);if($up->rowCount()!==1){fclose($fh);http_response_code(404);exit;}
 $filename=str_replace(["\r","\n","\"","/","\\"],'_',(string)$grant['original_filename']);
 $fallback=preg_replace('/[^A-Za-z0-9_.-]/','_',$filename);if($fallback==='')$fallback='soubor';
 header('Content-Type: application/octet-stream');
 header('Content-Length: '.$size);
 header('Content-Disposition: attachment; filename="'.$fallback.'"; filename*=UTF-8\'\''.rawurlencode($filename));
 header('X-Content-Type-Options: nosniff');
 while(!feof($fh)){echo fread($fh,65536);if(connection_aborted())break;}
 fclose($fh);
} catch(Throwable $e) {error_log('RAC digital download: '.$e->getMessage());if(!headers_sent())http_response_code(503);exit('Soubor není nyní dostupný. Kontaktujte podporu.');}
