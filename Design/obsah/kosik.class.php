<!-- RAC kosik, verze 2026-10-08-cart-layout-03 -->
<div class="stricky-header stricked-menu main-menu"><div class="sticky-header__content"></div></div>
<section class="page-header"><div class="page-header__bg"></div><div class="page-header__overlay"></div><div class="container">
<ul class="page-header__breadcrumb list-unstyled"><li><a href="<?= racShopH(racUrl('index.php')) ?>">Úvod</a></li><li><span>E-shop</span></li></ul>
<h2 class="page-header__title">Košík</h2></div></section>
<section class="cart-page"><div class="container">
<?php if ($racCartError): ?><div role="alert" class="alert alert-danger"><?= racShopH($racCartError) ?></div><?php endif; ?>
<?php if (!$racCartLines): ?><p>Košík je zatím prázdný.</p><a href="<?= racShopH(racUrl('knihy.php')) ?>" class="ogency-btn">Prohlédnout knihy</a>
<?php else: ?>
<div class="table-responsive"><table class="table cart-page__table"><thead><tr><th>Položka</th><th>Cena</th><th>Množství</th><th>Celkem</th><th>Smazat</th></tr></thead><tbody>
<?php $total=0.0; $currency=null; $mixed=false; foreach ($racCartLines as $line):
$v=$line['variant'];$p=$line['product'];$total+=$line['total'];
if ($currency===null) {$currency=$v['currency'];} elseif ($currency!==$v['currency']) {$mixed=true;}
?>
<tr id="cart-item-<?= (int)$v['id'] ?>" style="scroll-margin-top:120px"><td><div class="cart-page__table__meta">
<?php if ($line['image']!==''): ?><div class="cart-page__table__meta-img"><img src="<?= racShopH($line['image']) ?>" alt="<?= racShopH($p['title']) ?>"></div><?php endif; ?>
<h3 class="cart-page__table__meta-title"><a href="<?= racShopH(racUrl(rawurlencode($p['slug']))) ?>"><?= racShopH($p['title']) ?></a><small style="display:block;margin-top:7px"><?= racShopH(racShopKind($v['kind'])) ?></small></h3></div></td>
<td><?= racShopMoney((string)$line['price'],$v['currency']) ?></td>
<td><form method="post" action="<?= racShopH(racUrl('kosik.php')) ?>"><input type="hidden" name="csrf" value="<?= racShopH(racCartToken()) ?>"><input type="hidden" name="action" value="update"><input type="hidden" name="variant_id" value="<?= (int)$v['id'] ?>">
<div class="product-details__quantity" style="margin:0 0 10px">
<div class="quantity-box">
<button type="button" class="sub" aria-label="Snížit počet"><i class="fa fa-minus"></i></button>
<input name="quantity" type="number" required min="1" max="<?= max(1,$line['max']) ?>" value="<?= (int)$line['quantity'] ?>" aria-label="Počet kusů">
<button type="button" class="add" aria-label="Zvýšit počet"><i class="fa fa-plus"></i></button>
</div></div>
<button type="submit" class="ogency-btn" style="padding:10px 16px;font-size:12px">Aktualizovat</button>
<?php if ($line['max']<$line['quantity']): ?><small>Dostupných kusů: <?= (int)$line['max'] ?></small><?php endif; ?></form></td>
<td><?= racShopMoney((string)$line['total'],$v['currency']) ?></td>
<td><form method="post" action="<?= racShopH(racUrl('kosik.php')) ?>"><input type="hidden" name="csrf" value="<?= racShopH(racCartToken()) ?>"><input type="hidden" name="action" value="remove"><input type="hidden" name="variant_id" value="<?= (int)$v['id'] ?>"><button type="submit" class="table cart-page__table__remove" aria-label="Odebrat položku" title="Odebrat položku" style="display:block;width:auto;margin:0;padding:0;border:0;background:transparent;font:inherit;cursor:pointer;"><span class="icon-close"></span></button></form></td>
</tr><?php endforeach; ?></tbody></table></div>
<div class="row"><div class="col-xl-8 col-lg-7"></div><div class="col-xl-4 col-lg-5"><ul class="cart-page__cart-total list-unstyled">
<li><span>Mezisoučet</span><span class="cart-page__cart-total-amount"><?= $mixed ? 'Více měn' : racShopMoney((string)$total,(string)$currency) ?></span></li>

</ul><div class="cart-page__buttons"><a href="<?= racShopH(racUrl('knihy.php')) ?>" class="ogency-btn" style="white-space:nowrap;min-width:215px;padding-left:24px;padding-right:24px;text-align:center">Zpět k nákupu</a><a class="ogency-btn checkout" href="<?= racShopH(racUrl('objednavka.php')) ?>">Pokračovat</a></div>
</div></div><?php endif; ?></div></section>
