<?php
// RAC customer Stripe payment step. PHP 7.4+
declare(strict_types=1);
require_once __DIR__.'/inc/01.config.class.php';
require_once __DIR__.'/inc/02.language.class.php';
if (session_status()!==PHP_SESSION_ACTIVE) session_start();
$pdo=racFrontendPdo();
$h=static function($x):string{return htmlspecialchars((string)$x,ENT_QUOTES,'UTF-8');};
$orderNumber=(string)($_SESSION['rac_payment_order']??'');
$nonce=(string)($_SESSION['rac_payment_nonce']??'');
$order=null;$errors=array();$paymentReady=false;$stripeMode='test';
if($pdo instanceof PDO && $orderNumber!==''){
 try{
  $st=$pdo->prepare('SELECT id,order_number,status,email,total,currency FROM shop_orders WHERE order_number=? LIMIT 1');
  $st->execute(array($orderNumber));$order=$st->fetch(PDO::FETCH_ASSOC)?:null;
  if($order){
   require_once __DIR__.'/administrace/billing/settings.php';
   $paymentReady=billing_settings_table_ready($pdo)
    && billing_setting_get($pdo,'billing_enabled','0')==='1';
   $stripeMode=strtolower((string)billing_setting_get($pdo,'mode','test'));
  }
 }catch(Throwable $ex){error_log('RAC pay load: '.$ex->getMessage());$errors[]=__('payment.unavailable');}
}
if(!$order){$errors[]=__('payment.order_missing');}
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
 if(!$order||$nonce===''||!hash_equals($nonce,(string)($_POST['csrf']??''))){$errors[]=__('payment.invalid_request');}
 elseif($order['status']!=='awaiting_payment'){$errors[]=__('payment.invalid_request');}
 elseif(!$paymentReady){$errors[]=__('payment.unavailable');}
 else{
  try{
   $secretKey=trim((string)billing_setting_get($pdo,$stripeMode==='live'?'stripe_live_secret_key':'stripe_test_secret_key',''));
   if(!preg_match('/^(sk|rk)_(test|live)_/', $secretKey)||!function_exists('curl_init'))throw new RuntimeException('Missing Stripe key or cURL');
   $amount=(int)round((float)$order['total']*100);
   if($amount<50 || $amount>100000000)throw new RuntimeException('Invalid Stripe amount');
   $currency=strtolower((string)$order['currency']);
   if(!preg_match('/^[a-z]{3}$/',$currency))throw new RuntimeException('Unsupported currency');
   $origin=((!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https://':'http://').(string)($_SERVER['HTTP_HOST']??'');
   if(strpos($origin,'https://')!==0||!preg_match('~^https://[a-z0-9.-]+(?::[0-9]+)?$~i',$origin))throw new RuntimeException('HTTPS required');
   $basePath=rtrim(str_replace('\\','/',dirname((string)($_SERVER['SCRIPT_NAME']??'/platba.php'))),'/');
   $success=$origin.$basePath.'/platba.php?return=success&session_id={CHECKOUT_SESSION_ID}';
   $cancel=$origin.$basePath.'/platba.php?return=cancel';
   $params=array(
    'mode'=>'payment','success_url'=>$success,'cancel_url'=>$cancel,
    'line_items[0][price_data][currency]'=>$currency,
    'line_items[0][price_data][unit_amount]'=>(string)$amount,
    'line_items[0][price_data][product_data][name]'=>'RAC '.$orderNumber,
    'line_items[0][quantity]'=>'1',
    'customer_email'=>(string)$order['email'],
    'client_reference_id'=>$orderNumber,
    'metadata[integration]'=>'rac-shop',
    'metadata[shop_order_id]'=>(string)$order['id'],
    'metadata[order_number]'=>$orderNumber,
    'payment_intent_data[metadata][shop_order_id]'=>(string)$order['id'],
   );
   $ch=curl_init('https://api.stripe.com/v1/checkout/sessions');
   curl_setopt_array($ch,array(CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>30,CURLOPT_HTTPHEADER=>array('Authorization: Bearer '.$secretKey,'Content-Type: application/x-www-form-urlencoded'),CURLOPT_POSTFIELDS=>http_build_query($params,'','&',PHP_QUERY_RFC3986)));
   $resp=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
   $stripe=is_string($resp)?json_decode($resp,true):null;
   if($code<200||$code>=300||!is_array($stripe)||empty($stripe['id'])||empty($stripe['url'])||strpos((string)$stripe['url'],'https://checkout.stripe.com/')!==0)throw new RuntimeException('Stripe Checkout session failed');
   $stmt=$pdo->prepare('INSERT INTO shop_payments (order_id,provider,environment,status,checkout_session_id,amount_minor,currency) VALUES (?,?,?,?,?,?,?)');
   $stmt->execute(array((int)$order['id'],'stripe',$stripeMode,'pending',(string)$stripe['id'],$amount,strtoupper($currency)));
   header('Location: '.$stripe['url'],true,303);exit;
  }catch(Throwable $ex){error_log('RAC Stripe checkout: '.$ex->getMessage());$errors[]=__('payment.unavailable');}
 }
}
include __DIR__.'/inc/03.head.class.php';include __DIR__.'/inc/04.header.class.php';
?>
<section class="page-header"><div class="page-header__bg"></div><div class="page-header__overlay"></div><div class="container">
<ul class="page-header__breadcrumb list-unstyled"><li><a href="<?= $h(racUrl('index.php')) ?>"><?= $h(__('breadcrumb.home')) ?></a></li><li><span><?= $h(__('payment.title')) ?></span></li></ul>
<h2 class="page-header__title"><?= $h(__('payment.title')) ?></h2></div></section>
<section class="checkout-page"><div class="container">
<style>.rac-pay-card{background:var(--ogency-black2);padding:30px;max-width:780px;margin:0 auto 50px;color:#fff}.rac-pay-card h2{color:#fff}.rac-pay-line{display:flex;gap:15px;justify-content:space-between;border-bottom:1px solid #ffffff28;padding:12px 0}.rac-pay-line strong{white-space:nowrap}.rac-pay-actions{display:flex;gap:15px;flex-wrap:wrap;margin-top:25px}@media(max-width:575px){.rac-pay-actions{flex-direction:column}.rac-pay-actions .ogency-btn{width:100%;text-align:center}}</style>
<div class="rac-pay-card">
<?php foreach($errors as $error): ?><div class="alert alert-danger" role="alert"><?= $h($error) ?></div><?php endforeach; ?>
<?php if($order): ?>
<h2><?= $h(__('payment.summary')) ?></h2>
<div class="rac-pay-line"><span><?= $h(__('checkout.order_number')) ?></span><strong><?= $h($orderNumber) ?></strong></div>
<div class="rac-pay-line"><span><?= $h(__('cart.total')) ?></span><strong><?= $h(number_format((float)$order['total'],2,',',' ')) ?> <?= $h($order['currency']) ?></strong></div>
<?php if(($_GET['return']??'')==='success'): ?><p class="alert alert-info"><?= $h(__('payment.pending_confirmation')) ?></p><?php endif; ?>
<?php if(($_GET['return']??'')==='cancel'): ?><p class="alert alert-warning"><?= $h(__('payment.cancelled')) ?></p><?php endif; ?>
<p><?= $h(__('payment.stripe_description')) ?></p>
<div class="rac-pay-actions">
<?php if($paymentReady && $order['status']==='awaiting_payment'): ?><form method="post"><input type="hidden" name="csrf" value="<?= $h($nonce) ?>"><button class="ogency-btn" type="submit"><?= $h(__('payment.pay_now')) ?></button></form><?php endif; ?>
<a href="<?= $h(racUrl('index.php')) ?>" class="ogency-btn"><?= $h(__('breadcrumb.home')) ?></a>
</div>
<?php endif; ?></div></div></section>
<?php include __DIR__.'/inc/05.footer.class.php';include __DIR__.'/inc/06.mobile-nav.class.php';include __DIR__.'/inc/07.scripts.class.php'; ?>
