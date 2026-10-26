<?php
// RAC Digital fulfillment | 2026-10-25-MULTIFORMAT-FIX01 | PHP 7.4+ | all published paid files per language variant.
// Use only after VERIFIED Stripe payment.
declare(strict_types=1);
function racDigitalColumn(PDO $db,string $table,string $col):bool {
 $s=$db->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name=?');$s->execute([$table,$col]);return (int)$s->fetchColumn()>0;
}
function racDigitalTable(PDO $db,string $table):bool {
 $s=$db->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?');$s->execute([$table]);return (int)$s->fetchColumn()>0;
}
function racDigitalKey():string {
 // Prefer hosting environment. Fallback to locally generated protected configuration.
 $hex=trim((string)getenv('RAC_DIGITAL_TOKEN_KEY'));
 if ($hex==='') {
  $config=dirname(__DIR__,2).'/rac-private/digital-config.php';
  if (is_file($config) && is_readable($config)) {
   $saved=require $config;
   if (is_array($saved) && isset($saved['RAC_DIGITAL_TOKEN_KEY']) && is_string($saved['RAC_DIGITAL_TOKEN_KEY'])) {
    $hex=$saved['RAC_DIGITAL_TOKEN_KEY'];
   }
  }
 }
 if (!preg_match('/^[a-f0-9]{64}$/iD',$hex)) {
  throw new RuntimeException('RAC_DIGITAL_TOKEN_KEY není nastaven. Spusťte jednorázové nastavení klíče.');
 }
 return hex2bin($hex);
}

function racDigitalEncrypt(string $token):string {
 $iv=random_bytes(12);$tag='';$ct=openssl_encrypt($token,'aes-256-gcm',racDigitalKey(),OPENSSL_RAW_DATA,$iv,$tag);
 if($ct===false)throw new RuntimeException('Šifrování tokenu selhalo.');return base64_encode($iv.$tag.$ct);
}
function racDigitalDecrypt(string $sealed):string {
 $b=base64_decode($sealed,true);if($b===false||strlen($b)<29)throw new RuntimeException('Poškozený token.');
 $out=openssl_decrypt(substr($b,28),'aes-256-gcm',racDigitalKey(),OPENSSL_RAW_DATA,substr($b,0,12),substr($b,12,16));
 if($out===false)throw new RuntimeException('Token nelze dešifrovat.');return $out;
}
function racDigitalPaid(PDO $db,int $orderId):bool {
 $s=$db->prepare("SELECT COUNT(*) FROM shop_payments WHERE order_id=? AND status='paid'");$s->execute([$orderId]);return (int)$s->fetchColumn()>0;
}
function racDigitalGrant(PDO $db,int $orderId):array {
 // Caller holds transaction and verifies payment first. Guard against missing deployment/schema.
 if(!racDigitalTable($db,'shop_order_items')||!racDigitalTable($db,'shop_digital_access')||!racDigitalTable($db,'product_files'))throw new RuntimeException('Chybí databázová struktura digitální distribuce.');
 if(!racDigitalColumn($db,'shop_order_items','variant_id'))throw new RuntimeException('Položky objednávky nemají variant_id: nutné ověřit schéma checkoutu.');
 if(!racDigitalColumn($db,'product_files','is_published'))throw new RuntimeException('Chybí product_files.is_published (migrace 021).');
 // Confirm all required dependencies before any automatic fulfilment.
 racDigitalKey();
 if(!function_exists('rac_private_storage_dir')){
  $storageConfig=dirname(__DIR__).'/config/digital_storage.php';
  if(!is_file($storageConfig))throw new RuntimeException('Chybí konfigurace privátního úložiště digital_storage.php.');
  require_once $storageConfig;
 }
 if(!racDigitalTable($db,'shop_order_fulfillment'))throw new RuntimeException('Chybí tabulka shop_order_fulfillment (SQL migrace 014).');
 if(!racDigitalPaid($db,$orderId))throw new RuntimeException('Platba ještě není potvrzená.');
 $s=$db->prepare('SELECT DISTINCT v.id,v.kind FROM shop_order_items i JOIN product_variants v ON v.id=i.variant_id WHERE i.order_id=?');$s->execute([$orderId]);$variants=$s->fetchAll(PDO::FETCH_ASSOC);
 if(!$variants)throw new RuntimeException('Objednávka nemá ověřené položky.');
 $hasPrint=false;$digital=0;$created=0;
 foreach($variants as $v){
  if($v['kind']==='print'){$hasPrint=true;continue;}
  if(!in_array($v['kind'],['ebook','audio'],true))throw new RuntimeException('Neznámá varianta objednávky.');
  ++$digital;
  // Variant ID identifikuje konkretni format i jazyk vydani; preview ani archivni plne soubory nevydavat.
  $st=$db->prepare("SELECT id FROM product_files WHERE variant_id=? AND purpose='paid' AND is_published=1 ORDER BY id DESC");
  $st->execute([(int)$v['id']]);$files=$st->fetchAll(PDO::FETCH_COLUMN);
  // Multiple published paid formats (for example EPUB and PDF) are intentionally delivered together.
  if(!$files)throw new RuntimeException('Pro některou zakoupenou digitální variantu chybí placený soubor.');
  foreach($files as $fileId){
   $fileSt=$db->prepare("SELECT storage_key FROM product_files WHERE id=? AND variant_id=? AND purpose='paid' AND is_published=1");$fileSt->execute([(int)$fileId,(int)$v['id']]);$storageKey=(string)$fileSt->fetchColumn();
   if(!preg_match('/^[a-f0-9]{48}$/D',$storageKey))throw new RuntimeException('Digitální soubor má neplatný identifikátor úložiště.');
   $filePath=rac_private_storage_dir().DIRECTORY_SEPARATOR.$storageKey;
   if(!is_file($filePath)||!is_readable($filePath))throw new RuntimeException('Digitální soubor není fyzicky dostupný v privátním úložišti (ID souboru '.$fileId.').');
   $check=$db->prepare('SELECT id,revoked_at,expires_at FROM shop_digital_access WHERE order_id=? AND file_id=? FOR UPDATE');$check->execute([$orderId,$fileId]);$previous=$check->fetch(PDO::FETCH_ASSOC);
   if($previous && $previous['revoked_at']===null && strtotime((string)$previous['expires_at'])>time())continue;
   $raw=bin2hex(random_bytes(32));$hash=hash('sha256',$raw);$sealed=racDigitalEncrypt($raw);
   if($previous){
    $update=$db->prepare('UPDATE shop_digital_access SET token_hash=?,token_encrypted=?,expires_at=DATE_ADD(NOW(),INTERVAL 30 DAY),revoked_at=NULL,download_count=0,last_download_at=NULL WHERE id=?');
    $update->execute([$hash,$sealed,(int)$previous['id']]);
   }else{
    $insert=$db->prepare('INSERT INTO shop_digital_access (order_id,file_id,token_hash,token_encrypted,expires_at) VALUES (?,?,?,?,DATE_ADD(NOW(),INTERVAL 30 DAY))');
    $insert->execute([$orderId,(int)$fileId,$hash,$sealed]);
   }
   ++$created;
  }
 }
 if($digital===0)return ['digital'=>0,'has_print'=>$hasPrint,'created'=>0];
 if(!$hasPrint&&racDigitalTable($db,'shop_order_fulfillment')){
  $st=$db->prepare("INSERT INTO shop_order_fulfillment(order_id,fulfillment_status,completed_at) VALUES (?,'completed',NOW()) ON DUPLICATE KEY UPDATE fulfillment_status=IF(fulfillment_status='cancelled',fulfillment_status,'completed'),completed_at=IF(fulfillment_status='cancelled',completed_at,COALESCE(completed_at,NOW()))");$st->execute([$orderId]);
 }
 return ['digital'=>$digital,'has_print'=>$hasPrint,'created'=>$created];
}

function racDigitalEmail(PDO $db,int $orderId):bool {
 // Deliberately opt-in email delivery on staging. Setting key and base URL required.
 if(getenv('RAC_DIGITAL_EMAIL_ENABLED')!=='1')return false;
 $base=rtrim((string)getenv('RAC_PUBLIC_BASE_URL'),'/');
 if(!preg_match('~^https://[a-z0-9.-]+(?::[0-9]+)?$~i',$base))throw new RuntimeException('Neplatná RAC_PUBLIC_BASE_URL.');
 $s=$db->prepare('SELECT email,order_number FROM shop_orders WHERE id=?');$s->execute([$orderId]);$order=$s->fetch(PDO::FETCH_ASSOC);
 if(!$order||!filter_var($order['email'],FILTER_VALIDATE_EMAIL))throw new RuntimeException('E-mail zákazníka chybí.');
 $st=$db->prepare('SELECT a.token_encrypted,f.id AS file_id,f.original_filename FROM shop_digital_access a JOIN product_files f ON f.id=a.file_id WHERE a.order_id=? AND a.revoked_at IS NULL AND a.expires_at>NOW() ORDER BY a.id');
 $st->execute([$orderId]);$rows=$st->fetchAll(PDO::FETCH_ASSOC);if(!$rows)throw new RuntimeException('Žádné platné odkazy.');
 require_once __DIR__.'/rac-delivery-mail-template.php';
 $downloadLinks='';
 $fileLabels=racDeliveryEditionTitles($db,array_column($rows,'file_id'));
 foreach($rows as $row){
  $fileId=(int)$row['file_id'];
  $label=$fileLabels[$fileId]??(string)$row['original_filename'];
  $downloadLinks.=$label."\n".$base.'/digital-stahnout.php?token='.racDigitalDecrypt((string)$row['token_encrypted'])."\n\n";
 }
 $mailTemplate=racDeliveryMailTemplate($db);
 if (strpos($mailTemplate['body'],'{download_links}')===false) throw new RuntimeException('Doručovací e-mail postrádá povinné odkazy ke stažení.');
 $mailValues=['order_number'=>(string)$order['order_number'],'validity_days'=>'30','download_links'=>$downloadLinks];
 $message=racDeliveryMailRender($mailTemplate['body'],$mailValues);
 $from=(string)getenv('RAC_DIGITAL_FROM_EMAIL');if(!filter_var($from,FILTER_VALIDATE_EMAIL))throw new RuntimeException('RAC_DIGITAL_FROM_EMAIL není platný.');
 $subject=preg_replace('/[\r\n]/','',racDeliveryMailRender($mailTemplate['subject'],$mailValues));
 $encodedSubject='=?UTF-8?B?'.base64_encode($subject).'?=';
 $headers='From: '.$from."\r\nContent-Type: text/plain; charset=UTF-8\r\nX-Auto-Response-Suppress: All\r\n";
 // Nejprve zákazník; případná chyba odeslání kopie neovlivní jeho doručení.
 $customerSent=mail((string)$order['email'],$encodedSubject,$message,$headers);
 if (!$customerSent) return false;
 try {
  $copySt=$db->query('SELECT operator_copy_email FROM shop_delivery_email_templates WHERE id=1 LIMIT 1');
  $copyAddress=trim((string)$copySt->fetchColumn());
  if ($copyAddress!=='' && filter_var($copyAddress,FILTER_VALIDATE_EMAIL) && strcasecmp($copyAddress,(string)$order['email'])!==0) {
   if (!mail($copyAddress,$encodedSubject,$message,$headers)) {
    error_log('RAC delivery mail: kopii provozovateli nelze odeslat, objednávka '.$orderId);
   }
  }
 } catch (Throwable $e) {
  error_log('RAC delivery mail: chyba kopie provozovateli u objednávky '.$orderId.': '.$e->getMessage());
 }
 return true;
}
