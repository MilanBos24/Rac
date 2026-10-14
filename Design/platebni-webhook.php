<?php
// RAC Stripe customer-order webhook; configure this URL in Stripe separately.
declare(strict_types=1);
require_once __DIR__.'/inc/01.config.class.php';
require_once __DIR__.'/administrace/billing/settings.php';
$pdo=racFrontendPdo();
if(($_SERVER['REQUEST_METHOD']??'')!=='POST'){http_response_code(405);exit;}
if(!$pdo instanceof PDO){http_response_code(503);exit;}
$payload=file_get_contents('php://input');
if(!is_string($payload)||$payload===''||strlen($payload)>1048576){http_response_code(400);exit;}
$header=(string)($_SERVER['HTTP_STRIPE_SIGNATURE']??'');
$ts=0;$signatures=array();
foreach(explode(',',$header) as $part){$kv=explode('=',trim($part),2);if(count($kv)!==2)continue;if($kv[0]==='t'&&ctype_digit($kv[1]))$ts=(int)$kv[1];if($kv[0]==='v1')$signatures[]=$kv[1];}
if(!$ts||abs(time()-$ts)>300||!$signatures){http_response_code(400);exit;}
try{
 $secrets=array('test'=>(string)billing_setting_get($pdo,'stripe_test_webhook_secret',''),'live'=>(string)billing_setting_get($pdo,'stripe_live_webhook_secret',''));
 $verifiedMode=null;
 foreach($secrets as $mode=>$secret){
  if(strpos($secret,'whsec_')!==0)continue;
  $hash=hash_hmac('sha256',$ts.'.'.$payload,$secret);
  foreach($signatures as $given){if(hash_equals($hash,$given)){$verifiedMode=$mode;break 2;}}
 }
 if($verifiedMode===null){http_response_code(400);exit;}
 $event=json_decode($payload,true);
 if(!is_array($event)||empty($event['id'])||empty($event['type'])){http_response_code(400);exit;}
 $mode=empty($event['livemode'])?'test':'live';
 if($mode!==$verifiedMode){http_response_code(400);exit;}
 $type=(string)$event['type'];
 $object=$event['data']['object']??array();
 if(!is_array($object)){http_response_code(400);exit;}
 // Only Stripe Checkout sessions created specifically for the RAC shop.
 if(!in_array($type,array('checkout.session.completed','checkout.session.async_payment_succeeded','checkout.session.async_payment_failed','checkout.session.expired'),true)){http_response_code(200);exit('ignored');}
 $metadata=is_array($object['metadata']??null)?$object['metadata']:array();
 if(($metadata['integration']??'')!=='rac-shop'){http_response_code(200);exit('ignored');}
 $sessionId=(string)($object['id']??'');
 if(strpos($sessionId,'cs_')!==0){http_response_code(400);exit;}
 $orderId=(int)($metadata['shop_order_id']??0);
 if($orderId<=0){http_response_code(400);exit;}
 $pdo->beginTransaction();
 $st=$pdo->prepare('SELECT p.id,p.order_id,p.amount_minor,p.currency,p.environment,p.status,o.order_number,o.status AS order_status FROM shop_payments p JOIN shop_orders o ON o.id=p.order_id WHERE p.checkout_session_id=? AND p.order_id=? FOR UPDATE');
 $st->execute(array($sessionId,$orderId));$payment=$st->fetch(PDO::FETCH_ASSOC);
 if(!$payment){$pdo->rollBack();http_response_code(503);exit('pending record');}
 if($payment['environment']!==$mode || (string)$payment['order_number']!==(string)($metadata['order_number']??'') || (string)($object['client_reference_id']??'')!==(string)$payment['order_number']){
  $pdo->rollBack();http_response_code(400);exit;
 }
 $paid=($type==='checkout.session.completed' && ($object['payment_status']??'')==='paid') || $type==='checkout.session.async_payment_succeeded';
 if($paid && ((int)($object['amount_total']??-1)!==(int)$payment['amount_minor'] || strtoupper((string)($object['currency']??''))!==$payment['currency'])){
  $pdo->rollBack();http_response_code(400);exit('amount mismatch');
 }
 $stmt=$pdo->prepare('INSERT IGNORE INTO shop_payment_events(stripe_event_id,event_type,environment,payment_id) VALUES (?,?,?,?)');
 $stmt->execute(array((string)$event['id'],$type,$mode,(int)$payment['id']));
 if($stmt->rowCount()===0){$pdo->commit();http_response_code(200);exit('duplicate');}
 if($paid){
  $stmt=$pdo->prepare("UPDATE shop_payments SET status='paid',payment_intent_id=COALESCE(?,payment_intent_id) WHERE id=? AND status<>'paid'");
  $intent=is_string($object['payment_intent']??null)?$object['payment_intent']:null;
  $stmt->execute(array($intent,(int)$payment['id']));
  $stmt=$pdo->prepare("UPDATE shop_orders SET status='paid' WHERE id=? AND status='awaiting_payment'");
  $stmt->execute(array($orderId));
 }elseif($type==='checkout.session.async_payment_failed'){
  $stmt=$pdo->prepare("UPDATE shop_payments SET status='failed' WHERE id=? AND status<>'paid'");$stmt->execute(array((int)$payment['id']));
 }elseif($type==='checkout.session.expired'){
  $stmt=$pdo->prepare("UPDATE shop_payments SET status='expired' WHERE id=? AND status='pending'");$stmt->execute(array((int)$payment['id']));
 }
 $pdo->commit();http_response_code(200);echo 'ok';
}catch(Throwable $ex){if($pdo instanceof PDO&&$pdo->inTransaction())$pdo->rollBack();error_log('RAC Stripe shop webhook: '.$ex->getMessage());http_response_code(500);echo 'error';}
