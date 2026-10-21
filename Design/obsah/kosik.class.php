<?php // RAC kosik – responzivni layout, verze 2026-10-21-019.02 (PHP 7.4) ?>
<style>
/* Styly pouze pro obsah kosiku; ostatni stranky nejsou ovlivneny. */
.rac-cart-grid {width:100%;}
.rac-cart-grid__head,.rac-cart-grid__item {display:grid;grid-template-columns:minmax(0, 2.4fr) minmax(0, 1fr) minmax(110px, 1.15fr) minmax(0, 1fr) minmax(120px, 1.15fr) 30px;column-gap:15px;align-items:center;}
.rac-cart-grid__head {padding:16px 0;border-bottom:1px solid var(--ogency-black3);font-weight:700;}
.rac-cart-grid__item {padding:24px 0;border-bottom:1px solid var(--ogency-black3);}
.rac-cart-grid__item > div {min-width:0;}
.rac-cart-grid__book {display:flex;gap:14px;align-items:center;min-width:0;}
.rac-cart-grid__image {flex:0 0 76px;max-width:76px;}
.rac-cart-grid__image img {width:100%;height:auto;display:block;}
.rac-cart-grid__book-title {margin:0;font-size:18px;overflow-wrap:anywhere;}
.rac-cart-grid__book-title small {display:block;margin-top:7px;font-size:13px;}
.rac-cart-grid__quantity .product-details__quantity {margin:0!important;}
.rac-cart-grid__quantity .quantity-box {max-width:100%;}
.rac-cart-grid__price,.rac-cart-grid__total {overflow-wrap:anywhere;}
.rac-cart-grid__update .ogency-btn {font-size:12px;padding:10px 14px;white-space:nowrap;}
.rac-cart-grid__remove button {display:inline-flex;align-items:center;justify-content:center;width:30px;height:40px;margin:0;padding:0;border:0;background:transparent;color:inherit;font:inherit;cursor:pointer;}
.rac-cart-grid__remove button:hover {color:var(--ogency-base);}
.rac-cart-grid__mobile-label {display:none;}
.rac-cart-actions {display:flex;gap:15px;flex-wrap:wrap;justify-content:flex-end;}
.rac-cart-actions .ogency-btn {white-space:nowrap;min-width:max-content;text-align:center;flex:0 0 auto;padding-left:18px;padding-right:18px;}
.rac-cart-actions .ogency-btn:first-child {flex-grow:1;}
.rac-cart-actions .ogency-btn.checkout {flex-grow:1;}
@media(min-width:992px) { .cart-page .row > .col-xl-4.col-lg-5 {width:auto;min-width:440px;max-width:100%;margin-left:auto;} }
@media(max-width:991px) {
 .rac-cart-grid__head {display:none;}
 .rac-cart-grid__item {grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:18px 15px;padding:22px 0;}
 .rac-cart-grid__book {grid-column:1/-1;}
 .rac-cart-grid__price,.rac-cart-grid__quantity,.rac-cart-grid__total {display:block;}
 .rac-cart-grid__mobile-label {display:block;margin-bottom:8px;font-size:13px;font-weight:700;}
 .rac-cart-grid__quantity {grid-column:2;grid-row:2 / span 2;}
 .rac-cart-grid__total {grid-column:1;grid-row:3;}
 .rac-cart-grid__update {grid-column:1;grid-row:4;}
 .rac-cart-grid__remove {grid-column:2;grid-row:4;display:flex;align-items:center;justify-content:flex-end;}
}
@media(max-width:575px) {
 .rac-cart-grid__book-title {font-size:16px;}
 .rac-cart-grid__image {flex-basis:64px;max-width:64px;}
 .rac-cart-actions {flex-direction:column;gap:12px;}
 .rac-cart-actions .ogency-btn {display:block;width:100%;min-width:0;flex:none;padding:15px 20px;white-space:normal;}
}
</style>
<div class="stricky-header stricked-menu main-menu"><div class="sticky-header__content"></div></div>
<section class="page-header"><div class="page-header__bg"></div><div class="page-header__overlay"></div><div class="container">
<ul class="page-header__breadcrumb list-unstyled"><li><a href="<?= racShopH(racUrl('index.php')) ?>"><?= racShopH(__('breadcrumb.home')) ?></a></li><li><span><?= racShopH(__('breadcrumb.eshop')) ?></span></li></ul>
<h2 class="page-header__title"><?= racShopH(__('cart.title')) ?></h2></div></section>
<section class="cart-page"><div class="container">
<?php if ($racCartError): ?><div role="alert" class="alert alert-danger"><?= racShopH($racCartError) ?></div><?php endif; ?>
<?php if (!$racCartLines): ?><p><?= racShopH(__('cart.empty')) ?></p><a href="<?= racShopH(racUrl('knihy.php')) ?>" class="ogency-btn"><?= racShopH(__('cart.browse_books')) ?></a>
<?php else: ?>
<div class="rac-cart-grid">
 <div class="rac-cart-grid__head" aria-hidden="true"><div><?= racShopH(__('cart.item')) ?></div><div><?= racShopH(__('cart.price')) ?></div><div><?= racShopH(__('cart.quantity')) ?></div><div><?= racShopH(__('cart.total')) ?></div><div></div><div></div></div>
<?php $total=0.0; $currency=null; $mixed=false; foreach ($racCartLines as $line):
$v=$line['variant'];$p=$line['product'];$total+=$line['total'];
if ($currency===null) {$currency=$v['currency'];} elseif ($currency!==$v['currency']) {$mixed=true;}
$formId='rac-cart-update-'.(int)$v['id'];
?>
 <div class="rac-cart-grid__item" id="cart-item-<?= (int)$v['id'] ?>" style="scroll-margin-top:120px">
  <div class="rac-cart-grid__book">
   <?php if ($line['image']!==''): ?><div class="rac-cart-grid__image"><img src="<?= racShopH($line['image']) ?>" alt="<?= racShopH($p['title']) ?>"></div><?php endif; ?>
   <h3 class="rac-cart-grid__book-title"><a href="<?= racShopH(racUrl(rawurlencode($p['slug']))) ?>"><?= racShopH($p['title']) ?></a><small><?= racShopH(__( 'product.kind.' . $v['kind'] )) ?> · <?= racShopH(racEditionLanguage(isset($v['language_code']) ? $v['language_code'] : null)) ?></small></h3>
  </div>
  <div class="rac-cart-grid__price"><span class="rac-cart-grid__mobile-label"><?= racShopH(__('cart.price')) ?></span><?= racShopMoney((string)$line['price'],$v['currency']) ?></div>
  <div class="rac-cart-grid__quantity"><span class="rac-cart-grid__mobile-label"><?= racShopH(__('cart.quantity')) ?></span>
   <form id="<?= $formId ?>" method="post" action="<?= racShopH(racUrl('kosik.php')) ?>">
    <input type="hidden" name="csrf" value="<?= racShopH(racCartToken()) ?>"><input type="hidden" name="action" value="update"><input type="hidden" name="variant_id" value="<?= (int)$v['id'] ?>">
    <div class="product-details__quantity"><div class="quantity-box"><button type="button" class="sub" aria-label="<?= racShopH(__('cart.decrease_quantity')) ?>"><i class="fa fa-minus"></i></button><input name="quantity" type="number" required min="1" max="<?= max(1,$line['max']) ?>" value="<?= (int)$line['quantity'] ?>" aria-label="<?= racShopH(__('product.detail.quantity')) ?>"><button type="button" class="add" aria-label="<?= racShopH(__('cart.increase_quantity')) ?>"><i class="fa fa-plus"></i></button></div></div>
    <?php if ($line['max']<$line['quantity']): ?><small><?= racShopH(__('cart.available_pieces', ['count' => (int)$line['max']])) ?></small><?php endif; ?>
   </form>
  </div>
  <div class="rac-cart-grid__total"><span class="rac-cart-grid__mobile-label"><?= racShopH(__('cart.total')) ?></span><?= racShopMoney((string)$line['total'],$v['currency']) ?></div>
  <div class="rac-cart-grid__update"><button type="submit" form="<?= $formId ?>" class="ogency-btn"><?= racShopH(__('cart.update')) ?></button></div>
  <div class="rac-cart-grid__remove"><form method="post" action="<?= racShopH(racUrl('kosik.php')) ?>"><input type="hidden" name="csrf" value="<?= racShopH(racCartToken()) ?>"><input type="hidden" name="action" value="remove"><input type="hidden" name="variant_id" value="<?= (int)$v['id'] ?>"><button type="submit" aria-label="<?= racShopH(__('cart.remove_item')) ?>" title="<?= racShopH(__('cart.remove_item')) ?>"><span class="icon-close"></span></button></form></div>
 </div>
<?php endforeach; ?>
</div>
<div class="row"><div class="col-xl-8 col-lg-7"></div><div class="col-xl-4 col-lg-5"><ul class="cart-page__cart-total list-unstyled">
<li><span><?= racShopH(__('cart.subtotal')) ?></span><span class="cart-page__cart-total-amount"><?= $mixed ? racShopH(__('cart.multiple_currencies')) : racShopMoney((string)$total,(string)$currency) ?></span></li>
</ul><div class="rac-cart-actions"><a href="<?= racShopH(racUrl('knihy.php')) ?>" class="ogency-btn"><?= racShopH(__('cart.back_to_shop')) ?></a><a class="ogency-btn checkout" href="<?= racShopH(racUrl('objednavka.php')) ?>"><?= racShopH(__('cart.continue')) ?></a></div>
</div></div><?php endif; ?></div></section>
