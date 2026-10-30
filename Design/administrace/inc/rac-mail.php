<?php
// RAC SMTP digital delivery; PHP 7.4; config validation fix 2026-10-16-12.04.
declare(strict_types=1);
const RAC_SMTP_MODULE_VERSION = '2026-10-25-INVOICE-PDF-STEP08-02';
function racMailConfig(): array {
 $path=dirname(__DIR__,2).'/rac-private/mail-config.php';
 if(!is_file($path)||!is_readable($path))throw new RuntimeException('Chybí chráněná konfigurace SMTP.');
 $c=require $path;
 if(!is_array($c))throw new RuntimeException('Neplatná konfigurace SMTP.');
 foreach(['from_email','from_name','base_url'] as $k){if(!isset($c[$k])||trim((string)$c[$k])==='')throw new RuntimeException('Chybí položka SMTP: '.$k);}
 $transport=strtolower(trim((string)($c['transport']??'smtp')));
 if(!in_array($transport,['smtp','mail'],true))throw new RuntimeException('Neplatný způsob odesílání (transport): smtp nebo mail.');
 $c['transport']=$transport;
 if($transport==='smtp'){
  foreach(['host','port','username','password'] as $k){if(!isset($c[$k])||trim((string)$c[$k])==='')throw new RuntimeException('Chybí položka SMTP: '.$k);}
  if(!preg_match('/^[a-z0-9.-]+$/iD',(string)$c['host'])||!in_array((int)$c['port'],[465,587],true))throw new RuntimeException('Neplatný SMTP server nebo port (465/587).');
 }
 // Validate independently to make troubleshooting possible without exposing credentials.
 $fromEmail=trim((string)$c['from_email']);
 if(!filter_var($fromEmail,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Neplatná e-mailová adresa odesílatele (from_email).');
 $baseUrl=trim((string)$c['base_url']);
 $urlParts=parse_url($baseUrl);
 if(!is_array($urlParts) || strtolower((string)($urlParts['scheme']??''))!=='https'
    || empty($urlParts['host']) || !preg_match('/^[a-z0-9.-]+$/iD',(string)$urlParts['host'])
    || isset($urlParts['user']) || isset($urlParts['pass'])
    || isset($urlParts['query']) || isset($urlParts['fragment'])
    || (isset($urlParts['port']) && ((int)$urlParts['port']<1 || (int)$urlParts['port']>65535))
    || (isset($urlParts['path']) && (strpos($urlParts['path'],'\\')!==false || preg_match('~/(?:\.|%2e){2}(?:/|$)~i',$urlParts['path'])))) {
  throw new RuntimeException('Neplatná veřejná adresa (base_url): očekává se https://domena.cz nebo https://domena.cz/podadresar.');
 }
 $c['from_email']=$fromEmail;
 $c['base_url']=rtrim($baseUrl,'/');
 return $c;
}
function racSmtpRead($fp): string {
 $out=''; do { $line=fgets($fp,4096); if($line===false)throw new RuntimeException('SMTP server předčasně ukončil odpověď.'); $out.=$line; if(strlen($out)>32768)throw new RuntimeException('Příliš dlouhá SMTP odpověď.'); } while(strlen($line)<4 || $line[3]==='-');
 return $out;
}
function racSmtpExpect($fp,array $codes): string {
 $response=racSmtpRead($fp); if(!in_array((int)substr($response,0,3),$codes,true))throw new RuntimeException('SMTP odmítl požadavek (kód '.substr($response,0,3).').'); return $response;
}
function racSmtpCmd($fp,string $cmd,array $codes): string {if(fwrite($fp,$cmd."\r\n")===false)throw new RuntimeException('Chyba zápisu na SMTP.');return racSmtpExpect($fp,$codes);}
/** MIME encoded-word pro české hlavičky, RFC 2047; zdroj souboru UTF-8 bez BOM. */
function racMailHeaderUtf8(string $value,string $field='hlavicka'):string {
 if(preg_match('/[\r\n]/',$value))throw new RuntimeException('Neplatný znak v hlavičce e-mailu.');
 // Jednotlivé encoded-word položky musí mít max. 75 ASCII znaků.
 $pieces=preg_split('//u',$value,-1,PREG_SPLIT_NO_EMPTY);
 if(!is_array($pieces))throw new RuntimeException('Neplatné UTF-8 v poli '.$field.'. Opravte zdrojový údaj v UTF-8; obsah není vypisován.');
 $out=[];$part='';
 foreach($pieces as $ch){
  if(strlen(base64_encode($part.$ch))>60 && $part!==''){$out[]='=?UTF-8?B?'.base64_encode($part).'?=';$part='';}
  $part.=$ch;
 }
 if($part!=='')$out[]='=?UTF-8?B?'.base64_encode($part).'?=';
 return implode("\r\n ",$out);
}
/** MIME hlavičky + tělo zakódované Base64 pro přenos bez poškození diakritiky. */
function racMailMimeMessage(array $c,string $to,string $subject,string $body,array $attachments=[]):array {
 $from=(string)$c['from_email'];
 if(preg_match('//u',$body)!==1)throw new RuntimeException('Neplatné UTF-8 v těle e-mailu.');
 $headers='From: '.racMailHeaderUtf8((string)$c['from_name'],'from_name').' <'.$from.">\r\n".
          'Reply-To: '.$from."\r\n".
          "MIME-Version: 1.0\r\n";
 $encodedBody=rtrim(chunk_split(base64_encode(str_replace(["\r\n","\r"],"\n",$body)),76,"\r\n"));
 if(!$attachments){
  $headers.="Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\nAuto-Submitted: auto-generated\r\n";
  return [racMailHeaderUtf8($subject,'subject'),$headers,$encodedBody];
 }
 if(count($attachments)!==1)throw new RuntimeException('Podporována je jediná PDF příloha.');
 $attachment=$attachments[0];
 $filename=(string)($attachment['filename']??'');$data=(string)($attachment['data']??'');
 if(!preg_match('/^[A-Za-z0-9_-]{1,90}\.pdf$/D',$filename)||strncmp($data,'%PDF-',5)!==0||strlen($data)>7*1024*1024)throw new RuntimeException('Neplatná nebo příliš velká PDF příloha.');
 $boundary='rac_'.bin2hex(random_bytes(16));
 $headers.='Content-Type: multipart/mixed; boundary="'.$boundary.'"'."\r\nAuto-Submitted: auto-generated\r\n";
 $parts='--'.$boundary."\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n".$encodedBody."\r\n";
 $parts.='--'.$boundary."\r\nContent-Type: application/pdf; name=\"".$filename."\"\r\nContent-Disposition: attachment; filename=\"".$filename."\"\r\nContent-Transfer-Encoding: base64\r\n\r\n";
 $parts.=rtrim(chunk_split(base64_encode($data),76,"\r\n"))."\r\n--".$boundary."--\r\n";
 return [racMailHeaderUtf8($subject,'subject'),$headers,$parts];
}
function racMailSend(array $c,string $to,string $subject,string $body,array $attachments=[]):void {
 if(!filter_var($to,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Neplatný e-mail zákazníka.');
 foreach([$to,(string)$c['from_email'],(string)$c['username']] as $s)if(preg_match('/[\r\n]/',$s))throw new RuntimeException('Neplatná hlavička.');
 $secure=(int)$c['port']===465;
 $context=stream_context_create(['ssl'=>['verify_peer'=>true,'verify_peer_name'=>true,'allow_self_signed'=>false]]);
 $fp=@stream_socket_client(($secure?'ssl://':'tcp://').$c['host'].':'.$c['port'],$errno,$errstr,15,STREAM_CLIENT_CONNECT,$context);
 if(!$fp)throw new RuntimeException('Nelze se připojit k SMTP serveru.');
 stream_set_timeout($fp,20);
 try {
  racSmtpExpect($fp,[220]);
  $hello=racSmtpCmd($fp,'EHLO rac.local',[250]);
  if(!$secure){
   if(stripos($hello,'STARTTLS')===false)throw new RuntimeException('SMTP nepodporuje vyžadované šifrování STARTTLS.');
   racSmtpCmd($fp,'STARTTLS',[220]);
   if(stream_socket_enable_crypto($fp,true,STREAM_CRYPTO_METHOD_TLS_CLIENT)!==true)throw new RuntimeException('Navázání TLS selhalo.');
   $hello=racSmtpCmd($fp,'EHLO rac.local',[250]);
  }
  if(stripos($hello,'AUTH')===false)throw new RuntimeException('SMTP nenabízí přihlášení.');
  racSmtpCmd($fp,'AUTH LOGIN',[334]); racSmtpCmd($fp,base64_encode((string)$c['username']),[334]);racSmtpCmd($fp,base64_encode((string)$c['password']),[235]);
  racSmtpCmd($fp,'MAIL FROM:<'.$c['from_email'].'>',[250]);racSmtpCmd($fp,'RCPT TO:<'.$to.'>',[250,251]);racSmtpCmd($fp,'DATA',[354]);
  [$encodedSubject,$headers,$encodedBody]=racMailMimeMessage($c,$to,$subject,$body,$attachments);
  $payload=$headers.'To: <'.$to.">\r\n".'Subject: '.$encodedSubject."\r\n\r\n".$encodedBody;
  $payload=preg_replace('/^\./m','..',$payload);
  if(fwrite($fp,$payload."\r\n.\r\n")===false)throw new RuntimeException('SMTP zprávu nepřijal.');
  racSmtpExpect($fp,[250]); @fwrite($fp,"QUIT\r\n");
 } finally {fclose($fp);}
}
function racPhpMailSend(array $c,string $to,string $subject,string $body,array $attachments=[]):void {
 if(!function_exists('mail'))throw new RuntimeException('Funkce PHP mail() není na serveru dostupná.');
 if(!filter_var($to,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Neplatný e-mail zákazníka.');
 foreach([$to,(string)$c['from_email'],(string)$c['from_name'],$subject] as $value){
  if(preg_match('/[\r\n]/',(string)$value))throw new RuntimeException('Neplatné znaky v e-mailové hlavičce.');
 }
 [$encodedSubject,$headers,$encodedBody]=racMailMimeMessage($c,$to,$subject,$body,$attachments);
 if(@mail($to,$encodedSubject,$encodedBody,$headers)!==true)throw new RuntimeException('PHP mail() odmítlo zprávu. Ověřte adresu odesílatele a pravidla hostingu.');
}
function racDigitalSmtpEmail(PDO $db,int $orderId,string $source='manual'):bool {
 if(!in_array($source,['manual','webhook'],true))throw new InvalidArgumentException('Neplatný zdroj.');
 if(!racDigitalPaid($db,$orderId))throw new RuntimeException('Objednávka není zaplacena.');
 if(!racDigitalTable($db,'shop_digital_mail_log'))throw new RuntimeException('Chybí SQL migrace 016 pro e-maily.');
 $c=racMailConfig();
 if($source==='webhook' && empty($c['automatic_enabled']))return false;
 $s=$db->prepare('SELECT email,order_number FROM shop_orders WHERE id=?');$s->execute([$orderId]);$o=$s->fetch(PDO::FETCH_ASSOC);
 if(!$o||!filter_var($o['email']??'',FILTER_VALIDATE_EMAIL))throw new RuntimeException('Chybí adresa zákazníka.');
 $s=$db->prepare("SELECT a.token_encrypted,f.id AS file_id,f.original_filename FROM shop_digital_access a JOIN product_files f ON f.id=a.file_id WHERE a.order_id=? AND a.revoked_at IS NULL AND a.expires_at>NOW() AND f.purpose='paid' ORDER BY a.id");
 $s->execute([$orderId]);$rows=$s->fetchAll(PDO::FETCH_ASSOC);
 if(!$rows)throw new RuntimeException('K objednávce nejsou platné digitální přístupy.');
 require_once __DIR__.'/rac-delivery-mail-template.php';
 $fileLabels=[];
 if(function_exists('racDeliveryEditionTitles')) {
  try{$fileLabels=racDeliveryEditionTitles($db,array_column($rows,'file_id'));}
  catch(Throwable $labelError){error_log('RAC delivery labels order '.$orderId.': '.$labelError->getMessage());}
 }
 $downloadLinks='';
 foreach($rows as $row){
  $label=(string)($fileLabels[(int)$row['file_id']]??$row['original_filename']);
  $downloadLinks.=$label."\n".$c['base_url'].'/digital-stahnout.php?token='.rawurlencode(racDigitalDecrypt((string)$row['token_encrypted']))."\n\n";
 }
 $template=racDeliveryMailTemplate($db);
 if(strpos((string)$template['body'],'{download_links}')===false)throw new RuntimeException('Doručovací šablona neobsahuje povinný zástupný text {download_links}.');
 $values=['order_number'=>(string)$o['order_number'],'validity_days'=>'30','download_links'=>$downloadLinks];
 $body=racDeliveryMailRender((string)$template['body'],$values);
 $subject=racDeliveryMailRender((string)$template['subject'],$values);
 if(trim($subject)==='')throw new RuntimeException('Předmět doručovacího e-mailu je prázdný.');
 // If an issued invoice exists, its valid PDF attachment is mandatory.
 // Orders without an invoice (e.g. outside current invoicing scope) keep the existing flow.
 $invoiceAttachments=[];
 $invoiceQuery=$db->prepare("SELECT id FROM fakturace_doklady WHERE order_id=? AND typ='faktura' AND stav='zaplaceno' LIMIT 1");
 $invoiceQuery->execute([$orderId]);
 $hasInvoice=(bool)$invoiceQuery->fetchColumn();
 if($hasInvoice){
  require_once __DIR__.'/../fakturace/inc/rac-faktury-attachment.php';
  $one=racFakturaPdfAttachment($db,$orderId);
  if(!is_array($one)
    || strncmp((string)($one['data']??''),'%PDF-',5)!==0
    || !preg_match('/^[A-Za-z0-9_-]{1,90}\.pdf$/D',(string)($one['filename']??''))){
   throw new RuntimeException('K vystavene fakture chybi platna PDF priloha; e-mail nebyl odeslan.');
  }
  $invoiceAttachments[]=$one;
 }
 // Claim webhook send only once; manual resend intentionally permitted.
 if($source==='webhook'){
  $db->beginTransaction();
  try{
   $s=$db->prepare("SELECT id,status FROM shop_digital_mail_log WHERE order_id=? AND source='webhook' ORDER BY id DESC LIMIT 1 FOR UPDATE");$s->execute([$orderId]);$prev=$s->fetch(PDO::FETCH_ASSOC);
   if($prev && in_array($prev['status'],['sending','accepted'],true)){$db->commit();return false;}
   $s=$db->prepare("INSERT INTO shop_digital_mail_log(order_id,recipient,source,status) VALUES (?,?,'webhook','sending')");$s->execute([$orderId,$o['email']]);$logId=(int)$db->lastInsertId();$db->commit();
  }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
 }else{$s=$db->prepare("INSERT INTO shop_digital_mail_log(order_id,recipient,source,status) VALUES (?,?,'manual','sending')");$s->execute([$orderId,$o['email']]);$logId=(int)$db->lastInsertId();}
 try{
  if($c['transport']==='mail')racPhpMailSend($c,(string)$o['email'],$subject,$body,$invoiceAttachments);
  else racMailSend($c,(string)$o['email'],$subject,$body,$invoiceAttachments);
  // Kopie pro provozovatele se posila az PO potvrzeni prijeti zpravy pro zakaznika.
  // Jeji selhani nesmi zpusobit opakovane zaslani zakaznickeho e-mailu.
  $s=$db->prepare("UPDATE shop_digital_mail_log SET status='accepted',sent_at=NOW() WHERE id=?");$s->execute([$logId]);
  try {
   $stCopy=$db->query('SELECT operator_copy_email FROM shop_delivery_email_templates WHERE id=1 LIMIT 1');
   $copyTo=trim((string)$stCopy->fetchColumn());
   if($copyTo!=='' && filter_var($copyTo,FILTER_VALIDATE_EMAIL) && strcasecmp($copyTo,(string)$o['email'])!==0){
    if($c['transport']==='mail')racPhpMailSend($c,$copyTo,$subject,$body,$invoiceAttachments);
    else racMailSend($c,$copyTo,$subject,$body,$invoiceAttachments);
   }
  } catch(Throwable $copyError) {
   error_log('RAC operator mail copy order '.$orderId.': '.$copyError->getMessage());
  }
  return true;
 }catch(Throwable $e){$s=$db->prepare("UPDATE shop_digital_mail_log SET status='failed',error_code=? WHERE id=?");$s->execute([substr($e->getMessage(),0,180),$logId]);throw $e;}
}
