<?php
// RAC layout detailu knihy – verze 2026-10-12-09.45 (PHP 7.4)
// Cena pod nazvem; vyber vydani jako v objednavce; vydani + mnozstvi 50/50.
$detailImages = array();
foreach ($images as $image) {
    $url = racShopImageUrl(isset($image['file_path']) ? $image['file_path'] : null);
    if ($url !== '') {
        $detailImages[] = array('url' => $url, 'alt' => !empty($image['alt_text']) ? $image['alt_text'] : ($product['title'] ?? 'Kniha'));
    }
}
?>
<style>
/* RAC – pouze layout detailu produktu, bez zmeny globalni sablony. */
.product-details .rac-product-heading {display:block;}
.product-details .rac-product-heading .product-details__title {display:block;width:100%;margin-bottom:12px;}
.product-details .rac-product-heading .product-details__price {display:block;width:100%;margin:0;}
.product-details .rac-product-heading #rac-variant-original {display:block;margin-top:6px;margin-bottom:12px;}
.product-details .rac-product-buy-options {display:flex;flex-wrap:wrap;margin:20px -10px 0;align-items:flex-start;}
.product-details .rac-product-buy-options__col {width:50%;flex:0 0 50%;max-width:50%;padding:0 10px;min-width:0;}
.product-details .rac-product-buy-options__label {display:block;font-weight:700;margin:0 0 9px;}
.product-details .rac-product-buy-options .rac-product-variants {margin:0!important;}
.product-details .rac-product-buy-options .bootstrap-select {width:100%!important;max-width:100%;}
.product-details .rac-product-buy-options .bootstrap-select > .dropdown-toggle {width:100%;}
.product-details .rac-product-buy-options .product-details__quantity {display:block;margin:0;}
.product-details .rac-product-buy-options .product-details__quantity-title {display:block;margin:0 0 9px;}
.product-details .rac-product-buy-options .quantity-box {max-width:100%;}
@media (max-width:575px) {
  .product-details .rac-product-buy-options {margin-left:-6px;margin-right:-6px;}
  .product-details .rac-product-buy-options__col {padding-left:6px;padding-right:6px;}
}
</style>
<div class="stricky-header stricked-menu main-menu"><div class="sticky-header__content"></div></div>
<section class="page-header">
    <div class="page-header__bg"></div><div class="page-header__overlay"></div>
    <div class="container">
        <ul class="page-header__breadcrumb list-unstyled">
            <li><a href="<?= racShopH(racUrl('index.php')) ?>"><?= racShopH(__('breadcrumb.home')) ?></a></li>
            <li><a href="<?= racShopH(racUrl('knihy.php')) ?>"><?= racShopH(__('page.books')) ?></a></li>
            <?php if ($product): ?><li><span><?= racShopH($product['title']) ?></span></li><?php endif; ?>
        </ul>
        <h2 class="page-header__title"><?= $product ? racShopH($product['title']) : 'Kniha nenalezena' ?></h2>
    </div>
</section>
<section class="product-details">
    <div class="container">
        <?php if (!$product): ?>
            <p>Požadovaná kniha není dostupná.</p>
            <a class="ogency-btn" href="<?= racShopH(racUrl('knihy.php')) ?>">Zpět na knihy</a>
        <?php else: ?>
            <div class="row">
                <div class="col-lg-6 col-xl-6 wow fadeInLeft" data-wow-delay="200ms">
                    <?php if ($detailImages): ?>
                    <div class="product-details__img">
                        <img id="rac-product-main-photo" src="<?= racShopH($detailImages[0]['url']) ?>" alt="<?= racShopH($detailImages[0]['alt']) ?>">
                        <div class="product-details__img-search"><a id="rac-product-main-popup" class="img-popup" href="<?= racShopH($detailImages[0]['url']) ?>" aria-label="Zvětšit obrázek"><span class="icon-magnifying-glass"></span></a></div>
                    </div>
                    <?php if (count($detailImages) > 1): ?>
                        <div class="rac-product-thumbnails" style="display:flex;flex-wrap:wrap;gap:10px;margin-top:15px">
                        <?php foreach ($detailImages as $photo): ?>
                            <button type="button" class="rac-photo-thumb" data-photo="<?= racShopH($photo['url']) ?>" data-alt="<?= racShopH($photo['alt']) ?>" style="padding:0;border:1px solid #ddd;background:transparent;cursor:pointer">
                                <img src="<?= racShopH($photo['url']) ?>" alt="<?= racShopH($photo['alt']) ?>" width="82" height="100" loading="lazy" style="object-fit:cover">
                            </button>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php else: ?><div class="product-details__img"><div style="padding:60px;text-align:center;background:#f3f3f3">Fotografie se připravuje</div></div><?php endif; ?>
                </div>
                <div class="col-lg-6 col-xl-6 wow fadeInRight" data-wow-delay="300ms">
                    <div class="product-details__content">
                        <div class="product-details__top rac-product-heading">
                            <h3 class="product-details__title"><?= racShopH($product['title']) ?></h3>
                            <?php if ($variants): ?>
                                <?php $first = $variants[0]; $firstPrice = racShopPrice($first); ?>
                                <div class="product-details__price" id="rac-variant-price"><?= racShopMoney($firstPrice, $first['currency']) ?></div>
                                <div id="rac-variant-original"><?php if ((float)$firstPrice < (float)$first['price']): ?><del><?= racShopMoney((string)$first['price'], $first['currency']) ?></del><?php endif; ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="product-details__divider"></div>
                        <div class="product-details__excerpt">
                            <?php if (!empty($product['author'])): ?><p><?= racShopH(__('product.detail.author')) ?> <?= racShopH($product['author']) ?></p><?php endif; ?>
                            <?php if (!empty($product['short_description'])): ?><p class="product-details__excerpt-text1"><?= nl2br(racShopH($product['short_description'])) ?></p><?php endif; ?>
                        </div>
                        <?php if ($variants): ?>
                            <div class="rac-product-buy-options">
                                <div class="rac-product-buy-options__col">
                                    <div class="rac-product-variants">
                                        <h3 class="product-details__quantity-title"><?= racShopH(__('product.detail.choose_edition')) ?></h3>
                                        <select id="rac-variant-select" class="selectpicker" data-width="100%">
                                            <?php foreach ($variants as $v): $price = racShopPrice($v); $sold = $v['kind'] === 'print' && $v['stock_quantity'] !== null && (int)$v['stock_quantity'] < 1; ?>
                                            <option value="<?= (int)$v['id'] ?>" data-price="<?= racShopH(racShopMoney($price, $v['currency'])) ?>" data-original="<?= (float)$price < (float)$v['price'] ? racShopH(racShopMoney((string)$v['price'], $v['currency'])) : '' ?>" <?= $sold ? 'disabled' : '' ?>><?= racShopH(__(['print' => 'product.kind.print', 'ebook' => 'product.kind.ebook', 'audio' => 'product.kind.audio'][$v['kind']] ?? 'product.kind.print')) ?> – <?= racShopMoney($price, $v['currency']) ?><?= $sold ? ' (' . racShopH(__('product.detail.sold_out')) . ')' : '' ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="rac-product-buy-options__col">
                                    <div class="product-details__quantity">
                                        <h3 class="product-details__quantity-title"><?= racShopH(__('product.detail.quantity')) ?></h3>
                                        <div class="quantity-box"><button type="button" class="sub" aria-label="Snížit počet"><i class="fa fa-minus"></i></button><input type="number" id="rac-detail-quantity" min="1" max="99" value="1"><button type="button" class="add" aria-label="Zvýšit počet"><i class="fa fa-plus"></i></button></div>
                                    </div>
                                </div>
                            </div>
                            <form action="<?= racShopH(racUrl('kosik.php')) ?>" method="post" style="margin-top:20px">
                                <input type="hidden" name="csrf" value="<?= racShopH(racCartToken()) ?>">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="variant_id" id="rac-cart-variant" value="<?= (int)$variants[0]['id'] ?>">
                                <input type="hidden" name="quantity" id="rac-cart-quantity" value="1">
                                <div class="product-details__buttons"><button type="submit" class="ogency-btn" id="rac-cart-submit">Vložit do košíku</button></div>
                            </form>
                        <?php else: ?><p>Momentálně není dostupné žádné vydání této knihy.</p><?php endif; ?>
                    </div>
                </div>
            </div>
            <?php if (!empty($product['description'])): ?>
            <div class="product-details__description wow fadeInUp" data-wow-delay="300ms">
                <h3 class="product-details__description__title"><?= racShopH(__('product.detail.about')) ?></h3>
                <p class="product-details__description__text"><?= nl2br(racShopH($product['description'])) ?></p>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
<script>
(function () {
    var photo = document.getElementById('rac-product-main-photo');
    var popup = document.getElementById('rac-product-main-popup');
    document.querySelectorAll('.rac-photo-thumb').forEach(function(btn) {
        btn.addEventListener('click', function() {
            if (photo) { photo.src = btn.getAttribute('data-photo'); photo.alt = btn.getAttribute('data-alt') || ''; }
            if (popup) { popup.href = btn.getAttribute('data-photo'); }
        });
    });
    var select = document.getElementById('rac-variant-select');
    if (select) {
        var update = function() {
            var selected = select.options[select.selectedIndex];
            if (!selected) { return; }
            var cartVariant = document.getElementById('rac-cart-variant');
            var cartSubmit = document.getElementById('rac-cart-submit');
            if (cartVariant) { cartVariant.value = selected.value; }
            if (cartSubmit) { cartSubmit.disabled = !!selected.disabled; }
            var price = document.getElementById('rac-variant-price');
            var original = document.getElementById('rac-variant-original');
            if (price) { price.textContent = selected.getAttribute('data-price') || ''; }
            if (original) {
                original.textContent = '';
                var old = selected.getAttribute('data-original');
                if (old) { var del = document.createElement('del'); del.textContent = old; original.appendChild(del); }
            }
        };
        select.addEventListener('change', update);
        if (select.selectedIndex >= 0 && select.options[select.selectedIndex].disabled) {
            for (var i = 0; i < select.options.length; i++) { if (!select.options[i].disabled) { select.selectedIndex = i; break; } }
        }
        update();
        if (window.jQuery && window.jQuery.fn.selectpicker) { window.jQuery(select).selectpicker('refresh'); }
    }
    var quantity = document.getElementById('rac-detail-quantity');
    if (quantity) {
        var syncQuantity = function() { var v = document.getElementById('rac-cart-quantity'); if (v) { v.value = quantity.value; } };
        // Tlacitka +/- uz obsluhuje JavaScript sablony Ogency.
        // Nepridavat dalsi handler, jinak se pocet meni dvakrat.
        quantity.addEventListener('input', syncQuantity);
        quantity.addEventListener('change', syncQuantity);
        var cartForm = document.getElementById('rac-cart-submit');
        if (cartForm && cartForm.form) { cartForm.form.addEventListener('submit', syncQuantity); }
    }
})();
</script>
