        <div class="stricky-header stricked-menu main-menu">
            <div class="sticky-header__content"></div><!-- /.sticky-header__content -->
        </div><!-- /.stricky-header -->
        <section class="page-header">
            <div class="page-header__bg"></div>
            <div class="page-header__overlay"></div>
            <div class="container">
                <ul class="page-header__breadcrumb list-unstyled">
                    <li><a href="<?= htmlspecialchars(racUrl('index.php'), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars(__('breadcrumb.home'), ENT_QUOTES, 'UTF-8'); ?></a></li>
                    <li><span><?= htmlspecialchars(__('breadcrumb.portfolio'), ENT_QUOTES, 'UTF-8'); ?></span></li>
                </ul>
                <h2 class="page-header__title">Poznej sám sebe. Pak pochopíš všechno.</h2>
            </div>
        </section>

        <section class="project-details">
            <div class="container">
                <div class="project-details__image wow fadeInUp animated" data-wow-delay="200ms"><img src="assets/images/project/klasicka-verze.jpg" alt="Poznej sám sebe. Pak pochopíš všechno."></div>
                <div class="row">
                    <div class="col-xl-8 col-lg-7 wow fadeInLeft animated" data-wow-delay="300ms">
                        <div class="project-details__content">
                            <h3 class="project-details__content__title">Poznej sám sebe. Pak pochopíš všechno.</h3>
                            <p class="project-details__content__text">
                                Klasická verze knihy o sebepoznání, životě a porozumění sobě i druhým.
                            </p>
                            <h4 class="project-details__content__title-two">
                                Lorem ipsum dolor sit amet, consectetuer adipiscing elit.
                            </h4>
                            <p class="project-details__content__text">
                                Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Praesent vitae arcu tempor neque lacinia pretium. Vivamus porttitor turpis ac leo. Suspendisse nisl. Vivamus ac leo pretium faucibus. Mauris suscipit,
                                ligula sit amet pharetra semper, nibh ante cursus purus, vel sagittis velit mauris vel metus. Etiam sapien elit, consequat eget, tristique non, venenatis quis, ante. Fusce tellus odio, dapibus id fermentum quis, suscipit id erat.
                                Curabitur bibendum justo non orci. Quisque porta. Integer tempor. Nulla est. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Duis viverra diam non justo.
                            </p>
                            <p class="project-details__content__text">
                                Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Praesent vitae arcu tempor neque lacinia pretium. Vivamus porttitor turpis ac leo. Suspendisse nisl. Vivamus ac leo pretium faucibus. Mauris suscipit,
                                ligula sit amet pharetra semper, nibh ante cursus purus, vel sagittis velit mauris vel metus. Etiam sapien elit, consequat eget, tristique non, venenatis quis, ante. Fusce tellus odio, dapibus id fermentum quis, suscipit id erat.
                                Curabitur bibendum justo non orci. Quisque porta. Integer tempor. Nulla est. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Duis viverra diam non justo.
                            </p>
                            <h4 class="project-details__content__title-three">Praesent vitae arcu</h4>
                            <ul class="project-details__content__lists list-unstyled">
                                <li><span class="fa fa-check"></span>Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Duis viverra diam non justo.</li>
                                <li><span class="fa fa-check"></span>Nulla est. Pellentesque habitant morbi tristique senectus</li>
                                <li><span class="fa fa-check"></span>Accusamus et iusto odio dignissimos ducimus</li>
                            </ul>
                            <p class="project-details__content__text">
                                Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Praesent vitae arcu tempor neque lacinia pretium. Vivamus porttitor turpis ac leo. Suspendisse nisl. Vivamus ac leo pretium faucibus. Mauris suscipit,
                                ligula sit amet pharetra semper
                            </p>
                        </div>
                    </div>
                    <div class="col-xl-4 col-lg-5 wow fadeInRight animated" data-wow-delay="400ms">
                        <div class="project-details__right">
                            <ul class="project-details__info-list list-unstyled">
                                <li><span><?= htmlspecialchars(__('product.release_date'), ENT_QUOTES, 'UTF-8'); ?></span>????</li>
                                <li><span><?= htmlspecialchars(__('product.author'), ENT_QUOTES, 'UTF-8'); ?></span>Štefan Rác</li>
                                <li><span><?= htmlspecialchars(__('product.version'), ENT_QUOTES, 'UTF-8'); ?></span><?= htmlspecialchars(__('product.version.audiobook'), ENT_QUOTES, 'UTF-8'); ?></li>
                            </ul>
                            <div class="project-details__socials">
                                <a href="https://twitter.com/"><i class="fab fa-twitter"></i></a>
                                <a href="https://www.facebook.com/"><i class="fab fa-facebook"></i></a>
                                <a href="https://www.pinterest.com/"><i class="fab fa-pinterest-p"></i></a>
                                <a href="https://www.instagram.com/"><i class="fab fa-instagram"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="project-details__pagination wow fadeInUp animated" data-wow-delay="400ms">
                    <a class="project-details__pagination__previous" href="#" aria-label="<?= htmlspecialchars(__('action.previous'), ENT_QUOTES, 'UTF-8'); ?>"><span class="icon-left-arrow"></span><?= htmlspecialchars(__('action.previous'), ENT_QUOTES, 'UTF-8'); ?></a>
                    <a class="project-details__pagination__next" href="#" aria-label="<?= htmlspecialchars(__('action.next'), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars(__('action.next'), ENT_QUOTES, 'UTF-8'); ?><span class="icon-right-arrow"></span></a>
                </div>

                <div class="section-title text-center wow fadeInUp animated" data-wow-delay="400ms">
                    <h5 class="section-title__tagline section-title__tagline--has-dots"><?= htmlspecialchars(__('section.my_work'), ENT_QUOTES, 'UTF-8'); ?></h5>
                    <h2 class="section-title__title"><?= htmlspecialchars(__('section.books_projects'), ENT_QUOTES, 'UTF-8'); ?></h2>
                </div>

                <?php
                $relatedBooks = [
                    ['image' => 'klasicka.jpg', 'title' => 'Poznej sám sebe. Pak pochopíš všechno.'],
                    ['image' => 'komunikace.jpg', 'title' => 'Poznej sám sebe. Pak pochopíš všechno. — Komunikace se sebou'],
                    ['image' => 'rozsirena.jpg', 'title' => 'Poznej sám sebe. Pak pochopíš všechno. — Rozšířená verze'],
                ];
                ?>
                <div class="row">
                    <?php foreach ($relatedBooks as $book): ?>
                        <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="200ms">
                            <div class="blog-one__item">
                                <div class="blog-one__item__image">
                                    <img src="assets/images/knihy/<?= htmlspecialchars($book['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <a href="<?= htmlspecialchars(racUrl('poznej-sam-sebe-pak-pochopis-vsechno-klasicka-verze.php'), ENT_QUOTES, 'UTF-8'); ?>"></a>
                                    <span><?= htmlspecialchars(__('books.status.on_sale'), ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                                <div class="blog-one__item__content">
                                    <h3 class="blog-one__item__title">
                                        <a href="<?= htmlspecialchars(racUrl('poznej-sam-sebe-pak-pochopis-vsechno-klasicka-verze.php'), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($book['title'], ENT_QUOTES, 'UTF-8'); ?></a>
                                    </h3>
                                </div>
                                <div class="product-details__buttons">
                                    <a href="<?= htmlspecialchars(racUrl('poznej-sam-sebe-pak-pochopis-vsechno-klasicka-verze.php'), ENT_QUOTES, 'UTF-8'); ?>" class="ogency-btn wishlist"><?= htmlspecialchars(__('action.more_about_book'), ENT_QUOTES, 'UTF-8'); ?></a>
                                    <a href="<?= htmlspecialchars(racUrl('kosik.php'), ENT_QUOTES, 'UTF-8'); ?>" class="ogency-btn"><?= htmlspecialchars(__('action.buy'), ENT_QUOTES, 'UTF-8'); ?></a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
