<?php
// RAC detail knihy – verze 2026-10-22-UX-08 | sekce O knize nad detailem, puvodni dropdown | PHP 7.4+
// Zachovan vzhled Ogency, vyber formatu a jazyka, nakup i audio komponenta.
// Jazyk vydani nezavisi na prekladech webu; nevypisovat historicka vydani bez jazyka.
$variants = array_values(array_filter($variants ?? array(), static function($v): bool { return trim((string)($v['language_code'] ?? '')) !== ''; }));
// Orientacni prepocet cen z CZK: oficialni referencni kurzy ECB.
require_once dirname(__DIR__) . '/inc/rac-fx-display.php';
$racFxRates = racFxDisplayRates();
$racFxPrice = static function ($price, $currency) use ($racFxRates): string {
    if (strtoupper((string)$currency) !== 'CZK' || !$racFxRates) return '';
    $czk = (float)$price;
    if ($czk < 0 || !is_finite($czk)) return '';
    $eur = $czk / (float)$racFxRates['CZK'];
    $usd = $eur * (float)$racFxRates['USD'];
    return '(≈ ' . number_format($eur, 2, ',', ' ') . ' € | ≈ ' . number_format($usd, 2, ',', ' ') . ' $)';
};
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
.product-details .rac-product-heading #rac-variant-price .rac-fx-converted{font-size:inherit;font-weight:inherit;font-family:inherit;line-height:inherit;color:inherit;opacity:1;vertical-align:baseline;}
.product-details .rac-product-heading #rac-variant-price .rac-fx-converted:empty{display:none;}
.product-details .rac-product-buy-options {display:flex;flex-wrap:wrap;margin:20px -10px 0;align-items:flex-start;}
.product-details .rac-product-buy-options__col {width:50%;flex:0 0 50%;max-width:50%;padding:0 10px;min-width:0;}
.product-details .rac-product-buy-options__label {display:block;font-weight:700;margin:0 0 9px;}
.product-details .rac-product-buy-options .rac-product-variants {margin:0!important;}
.product-details .rac-product-buy-options .bootstrap-select {width:100%!important;max-width:100%;}
.product-details .rac-product-buy-options .bootstrap-select > .dropdown-toggle {width:100%;}
/* RAC: bile pismo ve vyberu vydani knihy. */
.product-details .rac-product-variants .bootstrap-select > .dropdown-toggle,
.product-details .rac-product-variants .bootstrap-select > .dropdown-toggle .filter-option-inner-inner {color:#fff!important;}
.product-details .rac-product-variants .bootstrap-select .dropdown-menu > li > a {color:#fff;}
.product-details .rac-product-buy-options .product-details__quantity {display:block;margin:0;}
.product-details .rac-product-buy-options .product-details__quantity-title {display:block;margin:0 0 9px;}
.product-details .rac-product-buy-options .quantity-box {max-width:100%;}
@media (max-width:575px) {
  .product-details .rac-product-buy-options {margin-left:-6px;margin-right:-6px;}
  .product-details .rac-product-buy-options__col {padding-left:6px;padding-right:6px;}
}

.product-details .rac-detail-actions{display:flex;gap:15px;flex-wrap:wrap;align-items:center}
.product-details .rac-detail-actions .ogency-btn{margin:0}
/* RAC-AUDIO-2026-10-13-C: animovany prvek z homepage v detailu knihy. */
.product-details .rac-product-audio-sample{margin-top:28px;display:flex;justify-content:flex-start;align-items:center;min-height:190px}
.product-details .rac-product-audio-sample .video-one__banner__curved-circle-box{display:block;position:relative!important;top:auto!important;right:auto!important;bottom:auto!important;left:auto!important;transform:none!important;margin:0;width:175px;height:175px;min-width:175px;border-radius:50%;background:var(--ogency-black2,#1d1d1d);z-index:1}
.product-details .rac-product-audio-sample .curved-circle{pointer-events:none;color:#fff!important;animation:textRotate 20s linear infinite!important}
.product-details .rac-product-audio-sample .video-popup{cursor:pointer;text-decoration:none;z-index:2;border:0;padding:0}
.product-details .rac-product-audio-sample .rac-audio-inactive{cursor:default;opacity:.75}
@media(max-width:575px){.product-details .rac-detail-actions{gap:12px}.product-details .rac-detail-actions .ogency-btn{width:100%;text-align:center}}
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
            <div class="product-details__description wow fadeInUp" data-wow-delay="300ms">
                <h3 class="product-details__description__title"><?= racShopH($product['title']) ?></h3>
                <?php if (!empty($product['description'])): ?>
                <p class="product-details__description__text"><?= nl2br(racShopH($product['description'])) ?></p>
                <?php endif; ?>
            </div>
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
                            <?php if ($variants): ?>
                                <?php $first = $variants[0]; $firstPrice = racShopPrice($first); ?>
                                <div class="product-details__price" id="rac-variant-price"><span class="rac-price-primary"><?= racShopMoney($firstPrice, $first['currency']) ?></span> <span class="rac-fx-converted" title="Orientační přepočet podle referenčního kurzu ECB"><?= racShopH($racFxPrice($firstPrice, $first['currency'])) ?></span></div>
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
                                    <div class="rac-product-variants checkout-page__input-box">
                                        <h3 class="product-details__quantity-title"><?= racShopH(__('product.detail.choose_edition')) ?></h3>
                                        <select id="rac-variant-select" class="selectpicker" data-live-search="false" data-width="100%" aria-label="Formát vydání">
                                            <?php foreach ($variants as $v): $price = racShopPrice($v); $sold = $v['kind'] === 'print' && $v['stock_quantity'] !== null && (int)$v['stock_quantity'] < 1; ?>
                                            <option value="<?= (int)$v['id'] ?>" data-language="<?= racShopH((string)($v['language_code'] ?? '')) ?>" data-price="<?= racShopH(racShopMoney($price, $v['currency'])) ?>" data-fx="<?= racShopH($racFxPrice($price, $v['currency'])) ?>" data-original="<?= (float)$price < (float)$v['price'] ? racShopH(racShopMoney((string)$v['price'], $v['currency'])) : '' ?>" <?= $sold ? 'disabled' : '' ?>><?= racShopH(__(['print'=>'product.kind.print','ebook'=>'product.kind.ebook','audio'=>'product.kind.audio'][$v['kind']] ?? 'product.kind.print')) ?> – <?= racShopMoney($price, $v['currency']) ?><?= $sold ? ' (' . racShopH(__('product.detail.sold_out')) . ')' : '' ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <h3 class="product-details__quantity-title" style="margin-top:16px"><?= racShopH(__('product.detail.edition_language')) ?></h3>
                                        <select id="rac-edition-language-select" class="selectpicker" data-live-search="false" data-width="100%" aria-label="<?= racShopH(__('product.detail.edition_language')) ?>">
                                            <?php $editionLanguages=[]; foreach($variants as $v) { $languageKey=(string)($v['language_code'] ?? ''); if(!array_key_exists($languageKey,$editionLanguages)) $editionLanguages[$languageKey]=racEditionLanguage($languageKey); } ?>
                                            <?php foreach ($editionLanguages as $languageKey=>$languageLabel): ?>
                                            <option value="<?= racShopH($languageKey) ?>"><?= racShopH($languageLabel) ?></option>
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
                                <div class="product-details__buttons rac-detail-actions">
                                    <button type="submit" class="ogency-btn" id="rac-cart-submit"><?= racShopH(__('action.add_to_cart')) ?></button>
                                    <a href="<?= racShopH(racUrl('knihy.php')) ?>" class="ogency-btn rac-back-to-shop"><?= racShopH(__('cart.back_to_shop')) ?></a>
                                </div>
                            </form>
                            <div class="rac-product-audio-sample">
                                <?php $racAudioSample = isset($product['audio_sample_url']) ? trim((string)$product['audio_sample_url']) : ''; ?>
                                <?php $racAudioReady = $racAudioSample !== '' && (strpos($racAudioSample, 'https://') === 0 || (strpos($racAudioSample, '/') === 0 && strpos($racAudioSample, '//') !== 0)); ?>
                                <div class="video-one__banner__curved-circle-box wow fadeInUp animated" data-wow-delay="400ms">
                                    <div class="curved-circle">
                                        <span class="curved-circle-item"><?= str_replace(' ', '&emsp;', racShopH(__('action.listen_audio_sample'))) ?></span>
                                    </div>
                                    <?php if ($racAudioReady): ?>
                                        <button type="button" class="video-popup" id="rac-audio-play" aria-label="<?= racShopH(__('action.listen_audio_sample')) ?>" title="<?= racShopH(__('action.listen_audio_sample')) ?>"><span class="fa fa-play"></span></button>
                                        <audio id="rac-audio-preview" preload="none" src="<?= racShopH($racAudioSample) ?>"></audio>
                                    <?php else: ?>
                                        <span class="video-popup rac-audio-inactive" role="img" aria-label="<?= racShopH(__('action.listen_audio_sample')) ?>" title="<?= racShopH(__('action.listen_audio_sample')) ?>"><span class="fa fa-play"></span></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?><p>Momentálně není dostupné žádné jazykové vydání této knihy.</p><?php endif; ?>
                    </div>
                </div>
            </div>
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

    var racAudioPlay = document.getElementById('rac-audio-play');
    var racAudioPreview = document.getElementById('rac-audio-preview');
    if (racAudioPlay && racAudioPreview) {
        racAudioPlay.addEventListener('click', function () {
            if (racAudioPreview.paused) {
                racAudioPreview.play().catch(function () {});
            } else {
                racAudioPreview.pause();
            }
        });
    }
    var select = document.getElementById('rac-variant-select');
    var languageSelect = document.getElementById('rac-edition-language-select');
    if (select && languageSelect) {
        var allEditions = Array.prototype.map.call(select.options, function (option) {
            return {value:option.value,language:option.getAttribute('data-language') || '',text:option.textContent,
                price:option.getAttribute('data-price') || '',fx:option.getAttribute('data-fx') || '',original:option.getAttribute('data-original') || '',sold:option.disabled};
        });
        // Variants are filtered in the browser only for display. The server verifies the selected variant_id.
        var syncEdition = function () {
            var selected = select.options[select.selectedIndex];
            var cartVariant = document.getElementById('rac-cart-variant');
            var cartSubmit = document.getElementById('rac-cart-submit');
            if (cartVariant) { cartVariant.value = selected ? selected.value : ''; }
            if (cartSubmit) { cartSubmit.disabled = !selected || selected.disabled; }
            var price = document.getElementById('rac-variant-price');
            var original = document.getElementById('rac-variant-original');
            if (price) {
                var primaryPrice = price.querySelector('.rac-price-primary');
                var fxPrice = price.querySelector('.rac-fx-converted');
                if (primaryPrice) { primaryPrice.textContent = selected ? selected.getAttribute('data-price') || '' : ''; }
                if (fxPrice) { fxPrice.textContent = selected ? selected.getAttribute('data-fx') || '' : ''; }
            }
            if (original) {
                original.textContent = '';
                var old = selected ? selected.getAttribute('data-original') : '';
                if (old) { var del = document.createElement('del'); del.textContent = old; original.appendChild(del); }
            }
        };
        var filterEditions = function () {
            var language = languageSelect.value;
            var previous = select.value;
            select.innerHTML = '';
            allEditions.forEach(function (item) {
                if (item.language !== language) return;
                var option = document.createElement('option');
                option.value = item.value;
                option.textContent = item.text;
                option.disabled = item.sold;
                option.setAttribute('data-price',item.price);
                option.setAttribute('data-fx',item.fx);
                option.setAttribute('data-original',item.original);
                option.setAttribute('data-language',item.language);
                select.appendChild(option);
            });
            var restored = Array.prototype.some.call(select.options,function(o){return o.value === previous && !o.disabled;});
            if (restored) { select.value = previous; }
            else { for (var i=0;i<select.options.length;i++){ if(!select.options[i].disabled){ select.selectedIndex=i; break; } } }
            if (window.jQuery && window.jQuery.fn.selectpicker) { window.jQuery(select).selectpicker('refresh'); }
            syncEdition();
        };
        var initial = allEditions.find(function(item){return !item.sold;}) || allEditions[0];
        if (initial) languageSelect.value = initial.language;
        languageSelect.addEventListener('change',filterEditions);
        select.addEventListener('change',syncEdition);
        filterEditions();
        if (window.jQuery && window.jQuery.fn.selectpicker) { window.jQuery(languageSelect).selectpicker('refresh'); }
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