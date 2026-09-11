
        <div class="stricky-header stricked-menu main-menu">
            <div class="sticky-header__content"></div><!-- /.sticky-header__content -->
        </div><!-- /.stricky-header -->
        <section class="page-header">
            <div class="page-header__bg"></div>
            <!-- /.page-header__bg -->
            <div class="page-header__overlay"></div>
            <!-- /.page-header__bg -->
            <div class="container">
                <ul class="page-header__breadcrumb list-unstyled">
                    <li><a href="<?= htmlspecialchars(racUrl('index.php'), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars(__('breadcrumb.home'), ENT_QUOTES, 'UTF-8'); ?></a></li>
                    <li><span><?= htmlspecialchars(__('breadcrumb.eshop'), ENT_QUOTES, 'UTF-8'); ?></span></li>
                </ul><!-- /.page-breadcrumb list-unstyled -->
                <h2 class="page-header__title"><?= htmlspecialchars(__('page.cart'), ENT_QUOTES, 'UTF-8'); ?></h2><!-- /.page-title -->
            </div><!-- /.container -->
        </section><!-- /.page-header -->
        <!-- Cart Start -->
        <section class="cart-page">
            <div class="container">
                <div class="table-responsive">
                    <table class="table cart-page__table">
                        <thead>
                            <tr>
                                <th><?= htmlspecialchars(__('cart.item'), ENT_QUOTES, 'UTF-8'); ?></th>
                                <th><?= htmlspecialchars(__('cart.price'), ENT_QUOTES, 'UTF-8'); ?></th>
                                <th><?= htmlspecialchars(__('cart.quantity'), ENT_QUOTES, 'UTF-8'); ?></th>
                                <th><?= htmlspecialchars(__('cart.total'), ENT_QUOTES, 'UTF-8'); ?></th>
                                <th><?= htmlspecialchars(__('cart.delete'), ENT_QUOTES, 'UTF-8'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="cart-page__table__meta">
                                        <div class="cart-page__table__meta-img">
                                            <img src="assets/images/product/laska.jpg" alt="Láska">
                                        </div>
                                        <h3 class="cart-page__table__meta-title"><a href="#">Láska</a>
                                        </h3>
                                    </div>
                                </td>
                                <td>$10.99</td>
                                <td>
                                    <div class="product-details__quantity">
                                        <div class="quantity-box">
                                            <button type="button" class="sub"><i class="fa fa-minus"></i></button>
                                            <input type="number" value="1">
                                            <button type="button" class="add"><i class="fa fa-plus"></i></button>
                                        </div>
                                    </div>
                                </td>
                                <td>$10.99</td>
                                <td>
                                    <a href="<?= htmlspecialchars(racUrl('kosik.php'), ENT_QUOTES, 'UTF-8'); ?>" class="table cart-page__table__remove"><span class="icon-close"></span></a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="cart-page__table__meta">
                                        <div class="cart-page__table__meta-img">
                                            <img src="assets/images/product/laska.jpg" alt="Život">
                                        </div>
                                        <h3 class="cart-page__table__meta-title"><a href="#">Život</a>
                                        </h3>
                                    </div>
                                </td>
                                <td>$10.99</td>
                                <td>
                                    <div class="product-details__quantity">
                                        <div class="quantity-box">
                                            <button type="button" class="sub"><i class="fa fa-minus"></i></button>
                                            <input type="number" value="1">
                                            <button type="button" class="add"><i class="fa fa-plus"></i></button>
                                        </div>
                                    </div>
                                </td>
                                <td>$10.99</td>
                                <td><a href="<?= htmlspecialchars(racUrl('kosik.php'), ENT_QUOTES, 'UTF-8'); ?>" class="table cart-page__table__remove"><span class="icon-close"></span></a></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="row">
                    <div class="col-xl-8 col-lg-7">
                    </div>
                    <div class="col-xl-4 col-lg-5">
                        <ul class="cart-page__cart-total list-unstyled">
                            <li><span><?= htmlspecialchars(__('cart.items'), ENT_QUOTES, 'UTF-8'); ?></span><span class="cart-page__cart-total-amount">600,- Kč</span></li>
                            <li><span><?= htmlspecialchars(__('cart.shipping'), ENT_QUOTES, 'UTF-8'); ?></span><span class="cart-page__cart-total-amount">200,- Kč</span></li>
                            <li><span><?= htmlspecialchars(__('cart.total'), ENT_QUOTES, 'UTF-8'); ?></span><span class="cart-page__cart-total-amount">800,- Kč</span></li>
                        </ul>
                        <div class="cart-page__buttons">
                            <a href="<?= htmlspecialchars(racUrl('knihy.php'), ENT_QUOTES, 'UTF-8'); ?>" class="ogency-btn"><?= htmlspecialchars(__('cart.back_to_shop'), ENT_QUOTES, 'UTF-8'); ?></a>
                            <a href="#" class="ogency-btn checkout"><?= htmlspecialchars(__('cart.continue'), ENT_QUOTES, 'UTF-8'); ?></a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- Cart End -->
