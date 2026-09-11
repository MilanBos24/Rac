 <div class="stricky-header stricked-menu main-menu">
            <div class="sticky-header__content"></div><!-- /.sticky-header__content -->
        </div><!-- /.stricky-header -->
        <section class="page-header">
            <div class="page-header__bg"></div>
            <div class="page-header__overlay"></div>
            <div class="container">
                <ul class="page-header__breadcrumb list-unstyled">
                    <li><a href="<?= htmlspecialchars(racUrl('index.php'), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars(__('breadcrumb.home'), ENT_QUOTES, 'UTF-8'); ?></a></li>
                    <li><span><?= htmlspecialchars(__('breadcrumb.author_work'), ENT_QUOTES, 'UTF-8'); ?></span></li>
                </ul>
                <h2 class="page-header__title"><?= htmlspecialchars(__('page.upcoming'), ENT_QUOTES, 'UTF-8'); ?></h2>
            </div>
        </section>

        <section class="blog-page">
            <div class="container">
                <div class="row">
                    <div class="col-lg-6 wow fadeInUp animated" data-wow-delay="200ms">
                        <div class="about-two__left">
                            <div class="section-title">
                                <h5 class="section-title__tagline section-title__tagline--has-dots"><?= htmlspecialchars(__('section.next_chapters'), ENT_QUOTES, 'UTF-8'); ?></h5>
                                <h2 class="section-title__title"><?= htmlspecialchars(__('section.work_in_progress'), ENT_QUOTES, 'UTF-8'); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 wow fadeInUp animated" data-wow-delay="400ms">
                        <div class="about-two__left">
                            <blockquote class="about-two__right--quote"><?= htmlspecialchars(__('upcoming.quote'), ENT_QUOTES, 'UTF-8'); ?></blockquote>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="200ms">
                        <div class="blog-one__item">
                            <div class="blog-one__item__image">
                                <img src="assets/images/knihy/laska-2.jpg" alt="Láska — první díl: Kóma">
                                <a href="<?= htmlspecialchars(racUrl('poznej-sam-sebe-pak-pochopis-vsechno-klasicka-verze.php'), ENT_QUOTES, 'UTF-8'); ?>"></a>
                                <span><?= htmlspecialchars(__('books.status.first_trilogy'), ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                            <div class="blog-one__item__content">
                                <h3 class="blog-one__item__title">
                                    <a href="<?= htmlspecialchars(racUrl('poznej-sam-sebe-pak-pochopis-vsechno-klasicka-verze.php'), ENT_QUOTES, 'UTF-8'); ?>">Láska — první díl: Kóma</a>
                                </h3>
                            </div>
                            <div class="product-details__buttons">
                                <a href="<?= htmlspecialchars(racUrl('poznej-sam-sebe-pak-pochopis-vsechno-klasicka-verze.php'), ENT_QUOTES, 'UTF-8'); ?>" class="ogency-btn wishlist"><?= htmlspecialchars(__('action.more_about_book'), ENT_QUOTES, 'UTF-8'); ?></a>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="200ms">
                        <div class="blog-one__item">
                            <div class="blog-one__item__image">
                                <img src="assets/images/knihy/blank.jpg" alt="Civilióza">
                                <a href="<?= htmlspecialchars(racUrl('poznej-sam-sebe-pak-pochopis-vsechno-klasicka-verze.php'), ENT_QUOTES, 'UTF-8'); ?>"></a>
                                <span><?= htmlspecialchars(__('books.status.in_preparation'), ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                            <div class="blog-one__item__content">
                                <h3 class="blog-one__item__title">
                                    <a href="<?= htmlspecialchars(racUrl('poznej-sam-sebe-pak-pochopis-vsechno-klasicka-verze.php'), ENT_QUOTES, 'UTF-8'); ?>">Civilióza</a>
                                </h3>
                            </div>
                            <div class="product-details__buttons">
                                <a href="<?= htmlspecialchars(racUrl('poznej-sam-sebe-pak-pochopis-vsechno-klasicka-verze.php'), ENT_QUOTES, 'UTF-8'); ?>" class="ogency-btn wishlist"><?= htmlspecialchars(__('action.more_about_book'), ENT_QUOTES, 'UTF-8'); ?></a>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="200ms">
                        <div class="blog-one__item">
                            <div class="blog-one__item__image">
                                <img src="assets/images/knihy/blank.jpg" alt="Stupně vědomí">
                                <a href="<?= htmlspecialchars(racUrl('poznej-sam-sebe-pak-pochopis-vsechno-klasicka-verze.php'), ENT_QUOTES, 'UTF-8'); ?>"></a>
                                <span><?= htmlspecialchars(__('books.status.in_preparation'), ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                            <div class="blog-one__item__content">
                                <h3 class="blog-one__item__title">
                                    <a href="<?= htmlspecialchars(racUrl('poznej-sam-sebe-pak-pochopis-vsechno-klasicka-verze.php'), ENT_QUOTES, 'UTF-8'); ?>">Stupně vědomí</a>
                                </h3>
                            </div>
                            <div class="product-details__buttons">
                                <a href="<?= htmlspecialchars(racUrl('poznej-sam-sebe-pak-pochopis-vsechno-klasicka-verze.php'), ENT_QUOTES, 'UTF-8'); ?>" class="ogency-btn wishlist"><?= htmlspecialchars(__('action.more_about_book'), ENT_QUOTES, 'UTF-8'); ?></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
