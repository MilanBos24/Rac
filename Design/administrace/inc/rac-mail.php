<?php
// RAC SMTP digital delivery; PHP 7.4; config validation fix 2026-10-16-12.04.
declare(strict_types=1);
const RAC_SMTP_MODULE_VERSION = '2026-10-16-UTF8-DIAG01';
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
function racMailMimeMessage(array $c,string $to,string $subject,string $body):array {
 $from=(string)$c['from_email'];
 $headers='From: '.racMailHeaderUtf8((string)$c['from_name'],'from_name').' <'.$from.">\r\n".
          'Reply-To: '.$from."\r\n".
          "MIME-Version: 1.0\r\n".
          "Content-Type: text/plain; charset=UTF-8\r\n".
          "Content-Transfer-Encoding: base64\r\n".
          "Auto-Submitted: auto-generated\r\n";
 $encodedBody=rtrim(chunk_split(base64_encode(str_replace(["\r\n","\r"],"\n",$body)),76,"\r\n"));
 if(preg_match('//u',$body)!==1)throw new RuntimeException('Neplatné UTF-8 v poli body. Zkontrolujte název souboru a data objednávky.');
 return [racMailHeaderUtf8($subject,'subject'),$headers,$encodedBody];
}
function racMailSend(array $c,string $to,string $subject,string $body):void {
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
  [$encodedSubject,$headers,$encodedBody]=racMailMimeMessage($c,$to,$subject,$body);
  $payload=$headers.'To: <'.$to.">\r\n".'Subject: '.$encodedSubject."\r\n\r\n".$encodedBody;
  $payload=preg_replace('/^\./m','..',$payload);
  if(fwrite($fp,$payload."\r\n.\r\n")===false)throw new RuntimeException('SMTP zprávu nepřijal.');
  racSmtpExpect($fp,[250]); @fwrite($fp,"QUIT\r\n");
 } finally {fclose($fp);}
}
function racPhpMailSend(array $c,string $to,string $subject,string $body):void {
 if(!function_exists('mail'))throw new RuntimeException('Funkce PHP mail() není na serveru dostupná.');
 if(!filter_var($to,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Neplatný e-mail zákazníka.');
 foreach([$to,(string)$c['from_email'],(string)$c['from_name'],$subject] as $value){
  if(preg_match('/[\r\n]/',(string)$value))throw new RuntimeException('Neplatné znaky v e-mailové hlavičce.');
 }
 [$encodedSubject,$headers,$encodedBody]=racMailMimeMessage($c,$to,$subject,$body);
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
 $s=$db->prepare("SELECT a.token_encrypted,f.original_filename FROM shop_digital_access a JOIN product_files f ON f.id=a.file_id WHERE a.order_id=? AND a.revoked_at IS NULL AND a.expires_at>NOW() AND f.purpose='paid' ORDER BY a.id");$s->execute([$orderId]);$rows=$s->fetchAll(PDO::FETCH_ASSOC);
 if(!$rows)throw new RuntimeException('K objednávce nejsou platné digitální přístupy.');
 $body="Dobrý den,\n\nděkujeme za objednávku č. ".$o['order_number'].".\n\nZakoupené digitální soubory si stáhnete zde:\n\n";
 foreach($rows as $r){$body.=$r['original_filename']."\n".$c['base_url'].'/digital-stahnout.php?token='.rawurlencode(racDigitalDecrypt($r['token_encrypted']))."\n\n";}
 $body.="Odkazy mají platnost 30 dní od vytvoření. Nesdílejte je s dalšími osobami.\n\nDěkujeme za váš nákup.\n";
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
  $subject='Odkazy ke stažení – objednávka '.$o['order_number'];
  if($c['transport']==='mail')racPhpMailSend($c,(string)$o['email'],$subject,$body);
  else racMailSend($c,(string)$o['email'],$subject,$body);
  $s=$db->prepare("UPDATE shop_digital_mail_log SET status='accepted',sent_at=NOW() WHERE id=?");$s->execute([$logId]);return true;
 }catch(Throwable $e){$s=$db->prepare("UPDATE shop_digital_mail_log SET status='failed',error_code=? WHERE id=?");$s->execute([substr($e->getMessage(),0,180),$logId]);throw $e;}
}
