<?php
$esc=static function($x){return htmlspecialchars((string)$x,ENT_QUOTES,'UTF-8');};
$posted=static function($k,$default='')use($esc){return $esc($_POST[$k]??$default);};
$subtotal=0;foreach($racCheckoutLines as $l)$subtotal+=$l['total'];
?>
<section class="page-header"><div class="page-header__bg"></div><div class="page-header__overlay"></div><div class="container"><ul class="page-header__breadcrumb list-unstyled"><li><a href="<?= $esc(racUrl('kosik.php')) ?>"><?= $esc(__('cart.title')) ?></a></li><li><span><?= $esc(__('checkout.order')) ?></span></li></ul><h2 class="page-header__title"><?= $esc(__('checkout.complete_order')) ?></h2></div></section>
<style>
/* Pouze drobné doplňky, veškeré základní styly z ogency.css */
.checkout-page #rac-billing-fields[hidden], .checkout-page #rac-company-fields[hidden], .checkout-page #rac-pickup-area[hidden]{display:none!important}
.checkout-page .checkout-page__your-order{margin-top:45px}
.checkout-page .checkout-page__order-table{width:100%}
.checkout-page .checkout-page__order-table{table-layout:fixed}
.checkout-page .checkout-page__order-table th:first-child,.checkout-page .checkout-page__order-table td:first-child{width:58%;overflow-wrap:anywhere;padding-right:15px}
.checkout-page .checkout-page__order-table th:last-child,.checkout-page .checkout-page__order-table td:last-child{width:42%;white-space:nowrap;text-align:right}
.checkout-page .checkout-page__order-table .pro__price{text-align:right;white-space:nowrap;color:#fff!important}
.checkout-page .checkout-page__order-table .pro__title{color:#fff!important}
.checkout-page .checkout-page__order-table thead th{color:#fff!important}
.checkout-page .checkout-page__order-table .rac-order-book{color:var(--ogency-base)!important}
.checkout-page .checkout-page__order-table .rac-order-book-meta{color:#fff!important}
.checkout-page .rac-checkout-actions{display:flex;gap:15px;flex-wrap:wrap;justify-content:flex-end;margin-top:20px}
.checkout-page .rac-checkout-actions .ogency-btn{white-space:nowrap;text-align:center}
@media(max-width:575px){.checkout-page .rac-checkout-actions{flex-direction:column}.checkout-page .rac-checkout-actions .ogency-btn{width:100%;white-space:normal}.checkout-page .checkout-page__order-table th:first-child,.checkout-page .checkout-page__order-table td:first-child{width:50%}.checkout-page .checkout-page__order-table th:last-child,.checkout-page .checkout-page__order-table td:last-child{width:50%;font-size:14px}}
.checkout-page .checkout-page__input-box textarea{width:100%;min-height:115px;padding:20px 30px;background:var(--ogency-black2);border:0;color:#fff;font:inherit}
.checkout-page .checkout-page__input-box textarea::placeholder,.checkout-page .checkout-page__input-box input::placeholder{color:#fff;opacity:.8}
.checkout-page .checkout-page__input-box input[type="text"],.checkout-page .checkout-page__input-box input[type="email"],.checkout-page .checkout-page__input-box input[type="tel"]{color:#fff}
.checkout-page .checkout-page__input-box .bootstrap-select>.dropdown-toggle{color:#fff!important}
.checkout-page .checkout-page__input-box .bootstrap-select>.dropdown-toggle .filter-option-inner-inner{color:#fff}
.checkout-page .checkout-page__check-box{margin:20px 0}
.checkout-page .rac-shipping-option{display:flex;align-items:center;gap:14px;justify-content:space-between;padding:16px 20px;background:var(--ogency-black2);margin-bottom:10px;cursor:pointer}
.checkout-page .rac-shipping-option input[type="radio"]{appearance:auto;width:17px;height:17px;flex-shrink:0;accent-color:var(--ogency-base)}
.checkout-page .rac-shipping-option span{flex:1}
.checkout-page .rac-shipping-option:has(input:checked){outline:1px solid var(--ogency-base)}
@media(max-width:991px){.checkout-page .checkout-page__shipping-address{margin-top:35px}}
</style>
<section class="checkout-page"><div class="container">
<?php foreach($racCheckoutErrors as $error): ?><p class="alert alert-danger"><?= $esc($error) ?></p><?php endforeach; ?>
<?php if($racCheckoutSuccess): ?>
<div class="alert alert-success"><h3><?= $esc(__('checkout.received')) ?></h3><p><?= $esc(__('checkout.order_number')) ?> <strong><?= $esc($racCheckoutSuccess) ?></strong></p><p><?= $esc(__('checkout.pending_note')) ?></p></div>
<?php elseif($racCheckoutLines && $racPdo instanceof PDO): ?>
<form method="post" class="checkout-page__form" id="rac-checkout-form">
<input type="hidden" name="csrf" value="<?= $esc(racCartToken()) ?>">
<div class="row"><div class="col-xl-6 col-lg-6"><div class="checkout-page__billing-address"><h2 class="checkout-page__billing-address__title"><?= $esc(__('checkout.contact_details')) ?></h2>
<div class="row bs-gutter-x-20"><div class="col-md-6"><div class="checkout-page__input-box"><input type="text" name="first_name" maxlength="100" required placeholder="<?= $esc(__('checkout.first_name')) ?> *" value="<?= $posted('first_name') ?>"></div></div><div class="col-md-6"><div class="checkout-page__input-box"><input type="text" name="last_name" maxlength="100" required placeholder="<?= $esc(__('checkout.last_name')) ?> *" value="<?= $posted('last_name') ?>"></div></div></div>
<div class="row bs-gutter-x-20"><div class="col-md-6"><div class="checkout-page__input-box"><input type="email" name="email" maxlength="190" required placeholder="E-mail *" value="<?= $posted('email') ?>"></div></div><div class="col-md-6"><div class="checkout-page__input-box"><input type="tel" name="phone" maxlength="60" required placeholder="<?= $esc(__('contact.phone')) ?> *" value="<?= $posted('phone') ?>"></div></div></div>
<?php if($racCheckoutPhysical): ?>
<h2 class="checkout-page__billing-address__title"><?= $esc(__('checkout.shipping_address')) ?></h2>
<div class="checkout-page__input-box"><input type="text" name="shipping_name" maxlength="190" required placeholder="<?= $esc(__('checkout.recipient_name')) ?> *" value="<?= $posted('shipping_name') ?>"></div>
<div class="checkout-page__input-box"><input type="text" name="shipping_street" maxlength="190" required placeholder="<?= $esc(__('checkout.street')) ?> *" value="<?= $posted('shipping_street') ?>"></div>
<div class="row bs-gutter-x-20"><div class="col-md-6"><div class="checkout-page__input-box"><input type="text" name="shipping_city" maxlength="120" required placeholder="<?= $esc(__('checkout.city')) ?> *" value="<?= $posted('shipping_city') ?>"></div></div><div class="col-md-6"><div class="checkout-page__input-box"><input type="text" name="shipping_postal" maxlength="30" required placeholder="<?= $esc(__('checkout.postal_code')) ?> *" value="<?= $posted('shipping_postal') ?>"></div></div></div>
<div class="checkout-page__input-box">
<label for="shipping_country"><?= $esc(__('checkout.shipping_country')) ?></label>
<select class="selectpicker" data-live-search="true" name="shipping_country" id="shipping_country" required><?php foreach($racCheckoutCountries as $cc): ?><option value="<?= $esc($cc) ?>" <?= $cc===$country?'selected':'' ?>><?= $esc($cc) ?></option><?php endforeach; ?></select>
</div>
<div class="checkout-page__check-box"><input type="checkbox" id="rac-billing-same" name="billing_same" value="1" <?= !isset($_POST['billing_same']) && ($_SERVER['REQUEST_METHOD']??'GET')!=='POST'||isset($_POST['billing_same'])?'checked':'' ?> ><label for="rac-billing-same"><span></span><?= $esc(__('checkout.same_billing')) ?></label></div>
<?php else: ?><p><?= $esc(__('checkout.digital_no_shipping')) ?></p><?php endif; ?>
<div id="rac-billing-fields" <?= $racCheckoutPhysical && ((!isset($_POST['billing_same']) && ($_SERVER['REQUEST_METHOD']??'GET')!=='POST')||isset($_POST['billing_same']))?'hidden':'' ?>>
<h2 class="checkout-page__billing-address__title"><?= $esc(__('checkout.billing_address')) ?></h2>
<div class="checkout-page__input-box"><input type="text" name="billing_name" maxlength="190" placeholder="<?= $esc(__('checkout.billing_name')) ?> *" value="<?= $posted('billing_name') ?>"></div>
<div class="checkout-page__input-box"><input type="text" name="billing_street" maxlength="190" placeholder="<?= $esc(__('checkout.street')) ?> *" value="<?= $posted('billing_street') ?>"></div>
<div class="row bs-gutter-x-20"><div class="col-md-6"><div class="checkout-page__input-box"><input type="text" name="billing_city" maxlength="120" placeholder="<?= $esc(__('checkout.city')) ?> *" value="<?= $posted('billing_city') ?>"></div></div><div class="col-md-6"><div class="checkout-page__input-box"><input type="text" name="billing_postal" maxlength="30" placeholder="<?= $esc(__('checkout.postal_code')) ?> *" value="<?= $posted('billing_postal') ?>"></div></div></div>
<div class="checkout-page__input-box"><input type="text" name="billing_country" maxlength="2" placeholder="<?= $esc(__('checkout.billing_country')) ?> *" value="<?= $posted('billing_country','CZ') ?>"></div>
</div>
<div class="checkout-page__check-box" style="margin:15px 0 18px"><input type="checkbox" name="company_details_enabled" id="rac-company-details-enabled" value="1" <?= isset($_POST['company_details_enabled']) || trim((string)($_POST['billing_company'] ?? '')) !== '' || trim((string)($_POST['billing_company_id'] ?? '')) !== '' || trim((string)($_POST['billing_vat_id'] ?? '')) !== '' ? 'checked' : '' ?>><label for="rac-company-details-enabled"><span></span><?= $esc(__('checkout.company_details')) ?></label></div>
<div id="rac-company-fields" <?= isset($_POST['company_details_enabled']) || trim((string)($_POST['billing_company'] ?? '')) !== '' || trim((string)($_POST['billing_company_id'] ?? '')) !== '' || trim((string)($_POST['billing_vat_id'] ?? '')) !== '' ? '' : 'hidden' ?>><div class="checkout-page__input-box"><input type="text" name="billing_company" maxlength="190" placeholder="<?= $esc(__('checkout.company_name')) ?>" value="<?= $posted('billing_company') ?>"></div>
<div class="row bs-gutter-x-20"><div class="col-md-6"><div class="checkout-page__input-box"><input type="text" name="billing_company_id" maxlength="50" placeholder="<?= $esc(__('checkout.company_id')) ?>" value="<?= $posted('billing_company_id') ?>"></div></div><div class="col-md-6"><div class="checkout-page__input-box"><input type="text" name="billing_vat_id" maxlength="50" placeholder="<?= $esc(__('checkout.vat_id')) ?>" value="<?= $posted('billing_vat_id') ?>"></div></div></div>
</div>
<div class="checkout-page__input-box"><textarea name="note" maxlength="2000" placeholder="<?= $esc(__('checkout.note')) ?>"><?= $posted('note') ?></textarea></div>
</div></div><div class="col-xl-6 col-lg-6"><div class="checkout-page__shipping-address"><h2 class="checkout-page__shipping-address__title"><?= $esc(__('checkout.shipping_delivery')) ?></h2>
<?php if($racCheckoutPhysical): ?><h3 class="checkout-page__shipping-address__title"><?= $esc(__('checkout.select_shipping')) ?></h3><div id="rac-shipping-methods">
<?php foreach($racCheckoutMethods as $m): $price=(float)$m['price'];if($m['free_from']!==null && $subtotal>=(float)$m['free_from'])$price=0; ?>
<label class="rac-shipping-option"><input type="radio" name="shipping_method_id" value="<?= (int)$m['id'] ?>" data-type="<?= $esc($m['type']) ?>" data-price="<?= $esc($price) ?>" <?= (int)($_POST['shipping_method_id']??0)===(int)$m['id']?'checked':'' ?> required> <span><?= $esc($m['name']) ?></span><strong><?= $esc(racShopMoney((string)$price,$racCheckoutCurrency)) ?></strong></label>
<?php endforeach; ?>
</div><div id="rac-pickup-area" hidden><label><?= $esc(__('checkout.pickup_location')) ?> *</label><div class="checkout-page__input-box"><input type="text" name="pickup_point" maxlength="255" placeholder="<?= $esc(__('checkout.pickup_example')) ?>" value="<?= $posted('pickup_point') ?>"></div><p><?= $esc(__('checkout.pickup_map_future')) ?></p></div>
<?php else: ?><p><?= $esc(__('checkout.digital_free')) ?></p><?php endif; ?>
</div>
<div class="checkout-page__your-order">
<h2 class="checkout-page__your-order__title"><?= $esc(__('checkout.your_order')) ?></h2>
<table class="checkout-page__order-table"><colgroup><col style="width:58%"><col style="width:42%"></colgroup><thead class="order_table_head"><tr><th><?= $esc(__('checkout.product')) ?></th><th class="right"><?= $esc(__('cart.price')) ?></th></tr></thead><tbody>
<?php foreach($racCheckoutLines as $l): ?><tr><td class="pro__title"><span class="rac-order-book"><?= $esc($l['product']['title']) ?></span><span class="rac-order-book-meta"> (<?= $esc(__('product.kind.' . $l['variant']['kind'])) ?>) × <?= (int)$l['quantity'] ?></span></td><td class="pro__price"><?= $esc(racShopMoney((string)$l['total'],$racCheckoutCurrency)) ?></td></tr><?php endforeach; ?>
<tr><td class="pro__title"><?= $esc(__('cart.subtotal')) ?></td><td class="pro__price"><?= $esc(racShopMoney((string)$subtotal,$racCheckoutCurrency)) ?></td></tr>
<tr><td class="pro__title"><?= $esc(__('cart.shipping')) ?></td><td class="pro__price" id="rac-shipping-total"><?= $racCheckoutPhysical?$esc(__('checkout.as_selected')):$esc(__('checkout.free')) ?></td></tr>
<tr><td class="pro__title"><strong><?= $esc(__('cart.total')) ?></strong></td><td class="pro__price"><strong id="rac-price-total"><?= $esc(racShopMoney((string)$subtotal,$racCheckoutCurrency)) ?></strong></td></tr>
</tbody></table>
<div class="checkout-page__payment">
<div class="checkout-page__payment__item checkout-page__payment__item--active"><h3 class="checkout-page__payment__title"><?= $esc(__('checkout.payment')) ?></h3><div class="checkout-page__payment__content" style="display:block"><?= $esc(__('checkout.payment_future')) ?></div></div>
</div><div class="rac-checkout-actions"><a href="<?= $esc(racUrl('kosik.php')) ?>" class="ogency-btn"><?= $esc(__('checkout.back_to_cart')) ?></a><button type="submit" class="ogency-btn"><?= $esc(__('checkout.place_order')) ?></button></div></div></div>
</div>
</form>
<script>(function(){var same=document.getElementById('rac-billing-same'),fields=document.getElementById('rac-billing-fields'),country=document.getElementById('shipping_country'),radios=document.querySelectorAll('input[name="shipping_method_id"]'),pickup=document.getElementById('rac-pickup-area'),pickupInput=pickup&&pickup.querySelector('input'),total=document.getElementById('rac-price-total');function update(){if(same&&fields){fields.hidden=same.checked;fields.querySelectorAll('input').forEach(function(el){el.required=!same.checked;});}var selected=document.querySelector('input[name="shipping_method_id"]:checked');if(pickup){pickup.hidden=!(selected&&selected.dataset.type==='pickup_point');if(pickupInput)pickupInput.required=!pickup.hidden;}if(total){var amount=<?= json_encode((float)$subtotal) ?>+(selected?parseFloat(selected.dataset.price)||0:0);total.textContent=amount.toLocaleString(<?= json_encode(['cs'=>'cs-CZ','sk'=>'sk-SK','en'=>'en-US','de'=>'de-DE','fr'=>'fr-FR','pl'=>'pl-PL'][$currentLanguage] ?? 'cs-CZ') ?>,{minimumFractionDigits:2,maximumFractionDigits:2})+' <?= $esc($racCheckoutCurrency) ?>';var shippingTotal=document.getElementById('rac-shipping-total');if(shippingTotal){shippingTotal.textContent=selected?(parseFloat(selected.dataset.price)||0).toLocaleString('cs-CZ',{minimumFractionDigits:2,maximumFractionDigits:2})+' <?= $esc($racCheckoutCurrency) ?>' : <?= json_encode(__('checkout.as_selected'),JSON_UNESCAPED_UNICODE) ?>;}}}var companyToggle=document.getElementById('rac-company-details-enabled'),companyFields=document.getElementById('rac-company-fields');if(companyToggle&&companyFields){companyToggle.addEventListener('change',function(){companyFields.hidden=!companyToggle.checked;});}if(same)same.addEventListener('change',update);radios.forEach(function(el){el.addEventListener('change',update);});if(country)country.addEventListener('change',function(){var url=new URL(window.location.href);url.searchParams.set('country',country.value);window.location.href=url.toString();});update();})();</script>
<?php else: ?><p><a class="ogency-btn" href="<?= $esc(racUrl('kosik.php')) ?>"><?= $esc(__('checkout.back_to_cart')) ?></a></p><?php endif; ?>
</div></section>
