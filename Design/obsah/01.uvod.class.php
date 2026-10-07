<div class="stricky-header stricked-menu main-menu">
            <div class="sticky-header__content"></div><!-- /.sticky-header__content -->
        </div><!-- /.stricky-header -->
        <!--Main Slider Start-->
        <section class="main-slider">
            <div class="main-slider__one ogency-owl__carousel owl-carousel" data-owl-options='{
		"loop": true,
		"animateOut": "fadeOut",
		"animateIn": "fadeIn",
		"items": 1,
		"autoplay": 6000,
		"autoplayTimeout": 7000,
		"smartSpeed": 500,
		"nav": false,
		"dots": false,
		"margin": 0
	    }'>
                <div class="item">
                    <!-- slider item start -->
                    <div class="main-slider__one-item">
                        <!-- bg image start -->
                        <div class="main-slider__one-bg" style="background-image: url(assets/images/backgrounds/slider-1-bg-1.png);"></div>
                        <!-- bg image end -->
                        <!-- image-layer start -->
                        <div class="main-slider__one-item__shape-1">
                            <img src="assets/images/backgrounds/slider-2.jpg" alt="">
                        </div>
                        <!-- image-layer end -->
                        <div class="container">
                            <div class="row">
                                <div class="col-xl-8">
                                    <div class="main-slider__one-item__content">
                                        <h2>Štefan<br> <span>Rác</h2>
                                        <a href="<?= htmlspecialchars(racUrl('knihy.php'), ENT_QUOTES, 'UTF-8'); ?>" class="main-slider__one-item__content-curved-circle-box">
                                            <div class="curved-circle">
                                                <!-- curved-circle start-->
                                                <span class="curved-circle--item">
                                                    <?= str_replace(' ', '&emsp;&emsp;', htmlspecialchars(__('home.discover_work'), ENT_QUOTES, 'UTF-8')); ?>
                                                </span>
                                            </div><!-- curved-circle end-->
                                            <div class="main-slider__one-item__content-arrow-down">
                                                <span class="icon-down-right"></span>
                                            </div><!-- curved-circle icon -->
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- slider item end -->
            </div>
            <!-- social start -->
            <div class="main-slider__socails">
                <a href="https://twitter.com/" target="_blank"><i class="fab fa-twitter"></i></a>
                <a href="https://www.facebook.com/" target="_blank"><i class="fab fa-facebook"></i></a>
                <a href="https://www.pinterest.com/" target="_blank"><i class="fab fa-pinterest-p"></i></a>
                <a href="https://www.instagram.com/" target="_blank"><i class="fab fa-instagram"></i></a>
            </div>
            <!-- social end -->
            <!-- phone start -->
            <div class="main-slider__phone"><a href="mailto:stefan.rac1@gmail.com">stefan.rac1@gmail.com</a></div>
            <!-- phone end -->
        </section>
        <!--Main Slider End-->

        <section class="about-two" style="background-image: url(assets/images/backgrounds/about-bg-1.png);">
            <div class="container">
                <div class="row">
                    <div class="col-lg-8 wow fadeInUp animated" data-wow-delay="200ms">
                        <div class="about-two__left">
                            <!-- about content left -->
                            <div class="section-title">
                                <h5 class="section-title__tagline section-title__tagline--has-dots">Myšlenka, která všechno spojuje</h5>
                                <h2 class="section-title__title">Nejdelší cesta nevede přes svět. <br />Vede do člověka.</h2>
                            </div><!-- section-title -->
                            <img src="assets/images/resources/rac-podpis.png" alt="Štefan Rác" width="223" />
                        </div><!-- about content left -->
                    </div>
                    <div class="col-lg-4 wow fadeInUp animated" data-wow-delay="400ms">
                        <div class="about-two__left">
                            <!-- about content right-->
                            <blockquote class="about-two__right--quote">Každá kniha otevírá jinou kapitolu lidského života. Sebepoznání, láska, rodina, společnost i odvaha podívat se pravdě do očí. Jednotlivé příběhy. Jedna společná cesta.</blockquote>
                        </div><!-- about content right-->
                    </div>
                </div>
            </div>
        </section>

        <section class="blog-page">
            <div class="container">
                <div class="row">
                    <div class="col-lg-6 wow fadeInUp animated" data-wow-delay="200ms">
                        <div class="about-two__left">
                            <!-- about content left -->
                            <div class="section-title">
                                <h5 class="section-title__tagline section-title__tagline--has-dots"><?= htmlspecialchars(__('section.author_world'), ENT_QUOTES, 'UTF-8'); ?></h5>
                                <h2 class="section-title__title"><?= htmlspecialchars(__('section.books_projects'), ENT_QUOTES, 'UTF-8'); ?></h2>
                            </div><!-- section-title -->
                        </div><!-- about content left -->
                    </div>
                    <div class="col-lg-6 wow fadeInUp animated" data-wow-delay="400ms">
                        <div class="about-two__left">
                            <!-- about content right-->
                            <blockquote class="about-two__right--quote"><?= __('books.available_formats'); ?></blockquote>
                        </div><!-- about content right-->
                    </div>

                    <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="200ms">
                        <div class="blog-one__item">
                            <div class="blog-one__item__image">
                                <img src="assets/images/knihy/klasicka.jpg" alt="Poznej sám sebe. Pak pochopíš všechno.">
                                <a href="<?= htmlspecialchars(racUrl('poznej-sam-sebe-pak-pochopis-vsechno-klasicka-verze.php'), ENT_QUOTES, 'UTF-8'); ?>"></a>
                                <span><?= htmlspecialchars(__('books.status.new'), ENT_QUOTES, 'UTF-8'); ?></span>
                            </div><!-- /.blog-image -->
                            <div class="blog-one__item__content">
                                <h3 class="blog-one__item__title">
                                    <a href="<?= htmlspecialchars(racUrl('poznej-sam-sebe-pak-pochopis-vsechno-klasicka-verze.php'), ENT_QUOTES, 'UTF-8'); ?>">Poznej sám sebe. Pak pochopíš všechno.</a>
                                </h3><!-- /.blog-title -->
                            </div><!-- /.qty-btn -->
                        </div><!-- /.blog-content -->
                    </div>

                    <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="200ms">
                        <div class="blog-one__item">
                            <div class="blog-one__item__image">
                                <img src="assets/images/knihy/komunikace.jpg" alt="Poznej sám sebe. Pak pochopíš všechno. — Komunikace se sebou">
                                <a href="<?= htmlspecialchars(racUrl('poznej-sam-sebe-pak-pochopis-vsechno-klasicka-verze.php'), ENT_QUOTES, 'UTF-8'); ?>"></a>
                                <span><?= htmlspecialchars(__('books.status.new'), ENT_QUOTES, 'UTF-8'); ?></span>
                            </div><!-- /.blog-image -->
                            <div class="blog-one__item__content">
                                <h3 class="blog-one__item__title">
                                    <a href="<?= htmlspecialchars(racUrl('poznej-sam-sebe-pak-pochopis-vsechno-klasicka-verze.php'), ENT_QUOTES, 'UTF-8'); ?>">Poznej sám sebe. Pak pochopíš všechno. — Komunikace se sebou</a>
                                </h3><!-- /.blog-title -->
                            </div><!-- /.qty-btn -->
                        </div><!-- /.blog-content -->
                    </div>

                    <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="200ms">
                        <div class="blog-one__item">
                            <div class="blog-one__item__image">
                                <img src="assets/images/knihy/rozsirena.jpg" alt="Poznej sám sebe. Pak pochopíš všechno. — Rozšířená verze">
                                <a href="<?= htmlspecialchars(racUrl('poznej-sam-sebe-pak-pochopis-vsechno-klasicka-verze.php'), ENT_QUOTES, 'UTF-8'); ?>"></a>
                                <span><?= htmlspecialchars(__('books.status.new'), ENT_QUOTES, 'UTF-8'); ?></span>
                            </div><!-- /.blog-image -->
                            <div class="blog-one__item__content">
                                <h3 class="blog-one__item__title">
                                    <a href="<?= htmlspecialchars(racUrl('poznej-sam-sebe-pak-pochopis-vsechno-klasicka-verze.php'), ENT_QUOTES, 'UTF-8'); ?>">Poznej sám sebe. Pak pochopíš všechno. — Rozšířená verze</a>
                                </h3><!-- /.blog-title -->
                            </div><!-- /.qty-btn -->
                        </div><!-- /.blog-content -->
                    </div>

                    <div class="col-md-12 text-end">
                        <a href="<?= htmlspecialchars(racUrl('knihy.php'), ENT_QUOTES, 'UTF-8'); ?>" class="ogency-btn"><?= htmlspecialchars(__('action.view_all_work'), ENT_QUOTES, 'UTF-8'); ?></a><!-- section-btn -->
                    </div>
                </div>
            </div>
        </section>

        <!-- Feature Start -->
        <!-- Feature End -->

        <!-- About Start -->
        <section class="about-one">
            <div class="container">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="about-one__thumb wow fadeInLeft animated" data-wow-delay="300ms">
                            <!-- about thumb start -->
                            <div class="about-one__thumb__round--top"></div>
                            <div class="about-one__thumb__img">
                                <img src="assets/images/resources/rac-inicialy-color.jpg" alt="O autorovi">
                            </div>
                            <div class="about-one__thumb__round--bottom"></div>
                        </div><!-- about thumb end -->
                    </div>
                    <div class="col-lg-6">
                        <div class="about-one__content">
                            <!-- about content start-->
                            <div class="section-title">
                                <h5 class="section-title__tagline section-title__tagline--has-dots">O autorovi</h5>
                                <h2 class="section-title__title">Slova mají smysl, když v nich poznáš život.</h2>
                            </div><!-- section-title -->
                            <p class="about-one__content__text-one">Štefan Rác je český autor, který ve své tvorbě zkoumá člověka, lidské vztahy a otázky, na které neexistují pohodlné odpovědi.</p>
                            <p class="about-one__content__text-two">Jeho autorský svět propojuje sebepoznání, lásku, rodinu i kritický pohled na společnost. Každý rukopis stojí na přesvědčení, že silný příběh nezačíná efektem, ale opravdovostí.</p>
                            <a href="<?= htmlspecialchars(racUrl('kontakt.php'), ENT_QUOTES, 'UTF-8'); ?>" class="ogency-btn"><?= htmlspecialchars(__('action.publisher_offers'), ENT_QUOTES, 'UTF-8'); ?></a>
                        </div><!-- about content end-->
                    </div>
                </div>
            </div>
        </section>

        <section class="blog-page">
            <div class="container">
                <div class="row">
                    <div class="col-lg-6 wow fadeInUp animated" data-wow-delay="200ms">
                        <div class="about-two__left">
                            <!-- about content left -->
                            <div class="section-title">
                                <h5 class="section-title__tagline section-title__tagline--has-dots"><?= htmlspecialchars(__('section.next_chapters'), ENT_QUOTES, 'UTF-8'); ?></h5>
                                <h2 class="section-title__title"><?= htmlspecialchars(__('section.work_in_progress'), ENT_QUOTES, 'UTF-8'); ?></h2>
                            </div><!-- section-title -->
                        </div><!-- about content left -->
                    </div>
                    <div class="col-lg-6 wow fadeInUp animated" data-wow-delay="400ms">
                        <div class="about-two__left">
                            <!-- about content right-->
                            <blockquote class="about-two__right--quote"><?= htmlspecialchars(__('upcoming.quote'), ENT_QUOTES, 'UTF-8'); ?></blockquote>
                        </div><!-- about content right-->
                    </div>

                    <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="200ms">
                        <div class="blog-one__item">
                            <div class="blog-one__item__image">
                                <img src="assets/images/knihy/laska-2.jpg" alt="Láska — první díl: Kóma">
                                <a href="<?= htmlspecialchars(racUrl('poznej-sam-sebe-pak-pochopis-vsechno-klasicka-verze.php'), ENT_QUOTES, 'UTF-8'); ?>"></a>
                                <span><?= htmlspecialchars(__('books.status.first_trilogy'), ENT_QUOTES, 'UTF-8'); ?></span>
                            </div><!-- /.blog-image -->
                            <div class="blog-one__item__content">
                                <h3 class="blog-one__item__title">
                                    <a href="<?= htmlspecialchars(racUrl('poznej-sam-sebe-pak-pochopis-vsechno-klasicka-verze.php'), ENT_QUOTES, 'UTF-8'); ?>">Láska — první díl: Kóma</a>
                                </h3><!-- /.blog-title -->
                            </div><!-- /.qty-btn -->
                            <div class="product-details__buttons">
                                <a href="<?= htmlspecialchars(racUrl('poznej-sam-sebe-pak-pochopis-vsechno-klasicka-verze.php'), ENT_QUOTES, 'UTF-8'); ?>" class="ogency-btn wishlist"><?= htmlspecialchars(__('action.more_about_book'), ENT_QUOTES, 'UTF-8'); ?></a>
                            </div>
                        </div><!-- /.blog-content -->
                    </div>

                    <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="200ms">
                        <div class="blog-one__item">
                            <div class="blog-one__item__image">
                                <img src="assets/images/knihy/blank.jpg" alt="Civilióza">
                                <a href="<?= htmlspecialchars(racUrl('poznej-sam-sebe-pak-pochopis-vsechno-klasicka-verze.php'), ENT_QUOTES, 'UTF-8'); ?>"></a>
                                <span><?= htmlspecialchars(__('books.status.in_preparation'), ENT_QUOTES, 'UTF-8'); ?></span>
                            </div><!-- /.blog-image -->
                            <div class="blog-one__item__content">
                                <h3 class="blog-one__item__title">
                                    <a href="<?= htmlspecialchars(racUrl('poznej-sam-sebe-pak-pochopis-vsechno-klasicka-verze.php'), ENT_QUOTES, 'UTF-8'); ?>">Civilióza</a>
                                </h3><!-- /.blog-title -->
                            </div><!-- /.qty-btn -->
                            <div class="product-details__buttons">
                                <a href="<?= htmlspecialchars(racUrl('poznej-sam-sebe-pak-pochopis-vsechno-klasicka-verze.php'), ENT_QUOTES, 'UTF-8'); ?>" class="ogency-btn wishlist"><?= htmlspecialchars(__('action.more_about_book'), ENT_QUOTES, 'UTF-8'); ?></a>
                            </div>
                        </div><!-- /.blog-content -->
                    </div>

                    <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="200ms">
                        <div class="blog-one__item">
                            <div class="blog-one__item__image">
                                <img src="assets/images/knihy/blank.jpg" alt="Stupně vědomí">
                                <a href="<?= htmlspecialchars(racUrl('poznej-sam-sebe-pak-pochopis-vsechno-klasicka-verze.php'), ENT_QUOTES, 'UTF-8'); ?>"></a>
                                <span><?= htmlspecialchars(__('books.status.in_preparation'), ENT_QUOTES, 'UTF-8'); ?></span>
                            </div><!-- /.blog-image -->
                            <div class="blog-one__item__content">
                                <h3 class="blog-one__item__title">
                                    <a href="<?= htmlspecialchars(racUrl('poznej-sam-sebe-pak-pochopis-vsechno-klasicka-verze.php'), ENT_QUOTES, 'UTF-8'); ?>">Stupně vědomí</a>
                                </h3><!-- /.blog-title -->
                            </div><!-- /.qty-btn -->
                            <div class="product-details__buttons">
                                <a href="<?= htmlspecialchars(racUrl('poznej-sam-sebe-pak-pochopis-vsechno-klasicka-verze.php'), ENT_QUOTES, 'UTF-8'); ?>" class="ogency-btn wishlist"><?= htmlspecialchars(__('action.more_about_book'), ENT_QUOTES, 'UTF-8'); ?></a>
                            </div>
                        </div><!-- /.blog-content -->
                    </div>
                </div>
            </div>
        </section>

        <section class="project-one @@extraClassName">
            <div class="container">
                <div class="row">
                    <div class="col-md-8">
                        <div class="section-title">
                            <h5 class="section-title__tagline section-title__tagline--has-dots">Některé věci začínají jednou zprávou.</h5>
                            <h2 class="section-title__title">Pro vydavatelské nabídky, spolupráci a dotazy ke knihám můžeš autora kontaktovat přímo e-mailem.</h2>
                        </div><!-- section-title -->
                    </div>
                    <div class="col-md-4 text-end">
                        <a href="<?= htmlspecialchars(racUrl('kontakt.php'), ENT_QUOTES, 'UTF-8'); ?>" class="ogency-btn"><?= htmlspecialchars(__('action.contact'), ENT_QUOTES, 'UTF-8'); ?></a><!-- section-btn -->
                    </div>
                </div>
            </div>
        </section>

        <section class="video-one">
            <div class="container">
                <div class="video-one__banner wow fadeInUp animated animated" data-wow-delay="100ms">
                    <img src="assets/images/backgrounds/audio.jpg" alt="Audioukázka">
                    <div class="video-one__banner__shape wow fadeInRight animated animated" data-wow-delay="300ms">
                        <img src="assets/images/backgrounds/video-bg-shape-1-1.png" alt="">
                    </div>
                    <!-- curved-circle start-->
                    <div class="video-one__banner__curved-circle-box wow fadeInUp animated animated" data-wow-delay="400ms">
                        <div class="curved-circle">
                            <span class="curved-circle-item">
                                <?= str_replace(' ', '&emsp;', htmlspecialchars(__('action.listen_audio_sample'), ENT_QUOTES, 'UTF-8')); ?>
                            </span>
                        </div>
                        <!-- video btn start -->
                        <a href="#" class="video-popup" aria-label="<?= htmlspecialchars(__('action.listen_audio_sample'), ENT_QUOTES, 'UTF-8'); ?>">
                            <span class="fa fa-play"></span>
                        </a>
                        <!-- video btn end -->
                    </div>
                    <!-- curved-circle end-->
                </div>
            </div>
        </section>
        <!-- Video Start-->

