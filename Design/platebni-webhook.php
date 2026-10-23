<?php
// RAC Stripe customer-order webhook / digital fulfilment extension / PHP 7.4+ / verze 2026-10-16-16.00
declare(strict_types=1);
require_once __DIR__.'/inc/01.config.class.php';
require_once __DIR__.'/administrace/billing/settings.php';
require_once __DIR__.'/administrace/inc/rac-digital.php';
require_once __DIR__.'/administrace/inc/rac-mail.php';
$pdo=racFrontendPdo();
if(($_SERVER['REQUEST_METHOD']??'')!=='POST'){http_response_code(405);exit;}
if(!$pdo instanceof PDO){http_response_code(503);exit;}
$payload=file_get_contents('php://input');
if(!is_string($payload)||$payload===''||strlen($payload)>1048576){http_response_code(400);exit;}
$header=(string)($_SERVER['HTTP_STRIPE_SIGNATURE']??'');$ts=0;$signatures=[];
foreach(explode(',',$header) as $part){$kv=explode('=',trim($part),2);if(count($kv)!==2)continue;if($kv[0]==='t'&&ctype_digit($kv[1]))$ts=(int)$kv[1];if($kv[0]==='v1')$signatures[]=$kv[1];}
if(!$ts||abs(time()-$ts)>300||!$signatures){http_response_code(400);exit;}
try{
 $secrets=['test'=>(string)billing_setting_get($pdo,'stripe_test_webhook_secret',''),'live'=>(string)billing_setting_get($pdo,'stripe_live_webhook_secret','')];$verifiedMode=null;
 foreach($secrets as $mode=>$secret){if(strpos($secret,'whsec_')!==0)continue;$hash=hash_hmac('sha256',$ts.'.'.$payload,$secret);foreach($signatures as $given){if(hash_equals($hash,$given)){$verifiedMode=$mode;break 2;}}}
 if($verifiedMode===null){http_response_code(400);exit;}
 $event=json_decode($payload,true);if(!is_array($event)||empty($event['id'])||empty($event['type'])){http_response_code(400);exit;}
 $mode=empty($event['livemode'])?'test':'live';if($mode!==$verifiedMode){http_response_code(400);exit;}
 $type=(string)$event['type'];$object=$event['data']['object']??[];if(!is_array($object)){http_response_code(400);exit;}
 if(!in_array($type,['checkout.session.completed','checkout.session.async_payment_succeeded','checkout.session.async_payment_failed','checkout.session.expired'],true)){http_response_code(200);exit('ignored');}
 $metadata=is_array($object['metadata']??null)?$object['metadata']:[];if(($metadata['integration']??'')!=='rac-shop'){http_response_code(200);exit('ignored');}
 $sessionId=(string)($object['id']??'');if(strpos($sessionId,'cs_')!==0){http_response_code(400);exit;}
 $orderId=(int)($metadata['shop_order_id']??0);if($orderId<=0){http_response_code(400);exit;}
 $pdo->beginTransaction();
 $st=$pdo->prepare('SELECT p.id,p.order_id,p.amount_minor,p.currency,p.environment,p.status,o.order_number,o.status AS order_status FROM shop_payments p JOIN shop_orders o ON o.id=p.order_id WHERE p.checkout_session_id=? AND p.order_id=? FOR UPDATE');
 $st->execute([$sessionId,$orderId]);$payment=$st->fetch(PDO::FETCH_ASSOC);
 if(!$payment){$pdo->rollBack();http_response_code(503);exit('pending record');}
 if($payment['environment']!==$mode || (string)$payment['order_number']!==(string)($metadata['order_number']??'') || (string)($object['client_reference_id']??'')!==(string)$payment['order_number']){$pdo->rollBack();http_response_code(400);exit;}
 $paid=($type==='checkout.session.completed'&&($object['payment_status']??'')==='paid')||$type==='checkout.session.async_payment_succeeded';
 if($paid&&((int)($object['amount_total']??-1)!==(int)$payment['amount_minor']||strtoupper((string)($object['currency']??''))!==$payment['currency'])){$pdo->rollBack();http_response_code(400);exit('amount mismatch');}
 $st=$pdo->prepare('INSERT IGNORE INTO shop_payment_events(stripe_event_id,event_type,environment,payment_id) VALUES (?,?,?,?)');
 $st->execute([(string)$event['id'],$type,$mode,(int)$payment['id']]);$newEvent=$st->rowCount()===1;
 if($newEvent){
  if($paid){
   $intent=is_string($object['payment_intent']??null)?$object['payment_intent']:null;
   $st=$pdo->prepare("UPDATE shop_payments SET status='paid',payment_intent_id=COALESCE(?,payment_intent_id) WHERE id=? AND status<>'paid'");$st->execute([$intent,(int)$payment['id']]);
   $st=$pdo->prepare("UPDATE shop_orders SET status='paid' WHERE id=? AND status='awaiting_payment'");$st->execute([$orderId]);
  }elseif($type==='checkout.session.async_payment_failed'){$st=$pdo->prepare("UPDATE shop_payments SET status='failed' WHERE id=? AND status<>'paid'");$st->execute([(int)$payment['id']]);}
  elseif($type==='checkout.session.expired'){$st=$pdo->prepare("UPDATE shop_payments SET status='expired' WHERE id=? AND status='pending'");$st->execute([(int)$payment['id']]);}
 }
 $pdo->commit();
 // A verified payment is committed even when fulfilment cannot be performed (e.g. missing files).
 // Duplicated events may retry incomplete grants; UNIQUE(order_id,file_id) prevents duplication.
 if($paid&&racDigitalPaid($pdo,$orderId)){
  try{
   $pdo->beginTransaction();$grant=racDigitalGrant($pdo,$orderId);$pdo->commit();
   // Posilat e-mail jen pri skutecne vytvorenych pristupech.
   if((int)($grant['created']??0)>0){
    try{racDigitalSmtpEmail($pdo,$orderId,'webhook');}
    catch(Throwable $mailErr){error_log('RAC digital mail order '.$orderId.': '.$mailErr->getMessage());}
   }
  }catch(Throwable $digitalErr){
   if($pdo->inTransaction())$pdo->rollBack();
   error_log('RAC digital access for paid order '.$orderId.': '.$digitalErr->getMessage());
   // Stripe muze bezpecne zopakovat doruceni webhooku. Platba uz je potvrzena.
   http_response_code(500);echo 'digital fulfilment temporarily unavailable';exit;
  }
 }
 http_response_code(200);echo 'ok';
}catch(Throwable $ex){if($pdo instanceof PDO&&$pdo->inTransaction())$pdo->rollBack();error_log('RAC Stripe shop webhook: '.$ex->getMessage());http_response_code(500);echo 'error';}
