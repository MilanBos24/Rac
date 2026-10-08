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
                    <div class="main-slider__one-item">
                        <div class="main-slider__one-bg" style="background-image: url(assets/images/backgrounds/slider-1-bg-1.png);"></div>
                        <div class="main-slider__one-item__shape-1">
                            <img src="assets/images/backgrounds/slider-2.jpg" alt="">
                        </div>
                        <div class="container">
                            <div class="row">
                                <div class="col-xl-8">
                                    <div class="main-slider__one-item__content">
                                        <h2>Štefan<br> <span>Rác</h2>
                                        <a href="<?= htmlspecialchars(racUrl('knihy.php'), ENT_QUOTES, 'UTF-8'); ?>" class="main-slider__one-item__content-curved-circle-box">
                                            <div class="curved-circle">
                                                <span class="curved-circle--item">
                                                    <?= str_replace(' ', '&emsp;&emsp;', htmlspecialchars(__('home.discover_work'), ENT_QUOTES, 'UTF-8')); ?>
                                                </span>
                                            </div>
                                            <div class="main-slider__one-item__content-arrow-down">
                                                <span class="icon-down-right"></span>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="main-slider__socails">
                <a href="https://twitter.com/" target="_blank"><i class="fab fa-twitter"></i></a>
                <a href="https://www.facebook.com/" target="_blank"><i class="fab fa-facebook"></i></a>
                <a href="https://www.pinterest.com/" target="_blank"><i class="fab fa-pinterest-p"></i></a>
                <a href="https://www.instagram.com/" target="_blank"><i class="fab fa-instagram"></i></a>
            </div>
            <div class="main-slider__phone"><a href="mailto:stefan.rac1@gmail.com">stefan.rac1@gmail.com</a></div>
        </section>
        <!--Main Slider End-->

        <section class="about-two" style="background-image: url(assets/images/backgrounds/about-bg-1.png);">
            <div class="container">
                <div class="row">
                    <div class="col-lg-8 wow fadeInUp animated" data-wow-delay="200ms">
                        <div class="about-two__left">
                            <div class="section-title">
                                <h5 class="section-title__tagline section-title__tagline--has-dots"><?= htmlspecialchars(racCmsValue('intro', 'tagline', 'Myšlenka, která všechno spojuje'), ENT_QUOTES, 'UTF-8'); ?></h5>
                                <h2 class="section-title__title"><?= nl2br(htmlspecialchars(racCmsValue('intro', 'title', "Nejdelší cesta nevede přes svět.\nVede do člověka."), ENT_QUOTES, 'UTF-8'), false); ?></h2>
                            </div>
                            <img src="assets/images/resources/rac-podpis.png" alt="Štefan Rác" width="223" />
                        </div>
                    </div>
                    <div class="col-lg-4 wow fadeInUp animated" data-wow-delay="400ms">
                        <div class="about-two__left">
                            <blockquote class="about-two__right--quote"><?= nl2br(htmlspecialchars(racCmsValue('intro', 'quote', 'Každá kniha otevírá jinou kapitolu lidského života. Sebepoznání, láska, rodina, společnost i odvaha podívat se pravdě do očí. Jednotlivé příběhy. Jedna společná cesta.'), ENT_QUOTES, 'UTF-8'), false); ?></blockquote>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="blog-page">
            <div class="container">
                <div class="row">
                    <div class="col-lg-6 wow fadeInUp animated" data-wow-delay="200ms">
                        <div class="about-two__left">
                            <div class="section-title">
                                <h5 class="section-title__tagline section-title__tagline--has-dots"><?= htmlspecialchars(racCmsValue('books_intro', 'tagline', __('section.author_world')), ENT_QUOTES, 'UTF-8'); ?></h5>
                                <h2 class="section-title__title"><?= htmlspecialchars(racCmsValue('books_intro', 'title', __('section.books_projects')), ENT_QUOTES, 'UTF-8'); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 wow fadeInUp animated" data-wow-delay="400ms">
                        <div class="about-two__left">
                            <blockquote class="about-two__right--quote"><?= nl2br(htmlspecialchars(racCmsValue('books_intro', 'formats', 'Knihy jsou dostupné v těchto podobách:' . "\n" . 'Elektronická kniha / Tištěná kniha / Audiokniha'), ENT_QUOTES, 'UTF-8'), false); ?></blockquote>
                        </div>
                    </div>

                    <?php
                    // RAC home: posledni tri aktivni knihy podle data vlozeni.
                    $racLatestBooks = array();
                    $racBooksError = false;
                    try {
                        $racProductsHelper = __DIR__ . '/../inc/rac-products.php';
                        if (!is_file($racProductsHelper)) {
                            throw new RuntimeException('Chybi rac-products.php');
                        }
                        require_once $racProductsHelper;
                        $racPdo = racFrontendPdo();
                        if (!$racPdo instanceof PDO) {
                            throw new RuntimeException('Databaze neni dostupna');
                        }
                        $racLatestBooks = array_values(array_filter(
                            racShopProducts($racPdo, $currentLanguage),
                            static function ($row) {
                                return trim((string)($row['title'] ?? '')) !== '';
                            }
                        ));
                        usort($racLatestBooks, static function ($a, $b) {
                            return (int)$b['id'] <=> (int)$a['id'];
                        });
                        $racLatestBooks = array_slice($racLatestBooks, 0, 3);
                    } catch (Throwable $e) {
                        error_log('RAC homepage books: ' . $e->getMessage());
                        $racBooksError = true;
                    }
                    ?>
                    <?php if ($racBooksError): ?>
                        <div class="col-12"><p>Knihy se nyní nepodařilo načíst.</p></div>
                    <?php elseif (!$racLatestBooks): ?>
                        <div class="col-12"><p>Momentálně nejsou k dispozici žádné knihy.</p></div>
                    <?php else: ?>
                        <?php foreach ($racLatestBooks as $book): ?>
                            <?php
                            $bookTitle = (string)$book['title'];
                            $bookUrl = racUrl(rawurlencode((string)$book['slug']));
                            $bookImage = racShopImageUrl($book['image'] ?? null);
                            ?>
                            <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="200ms">
                                <div class="blog-one__item">
                                    <div class="blog-one__item__image">
                                        <?php if ($bookImage !== ''): ?>
                                            <img loading="lazy" src="<?= htmlspecialchars($bookImage, ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($bookTitle, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php else: ?>
                                            <div class="rac-book-no-image">Bez fotografie</div>
                                        <?php endif; ?>
                                        <a href="<?= htmlspecialchars($bookUrl, ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?= htmlspecialchars($bookTitle, ENT_QUOTES, 'UTF-8'); ?>"></a>
                                        <span><?= htmlspecialchars(__('books.status.on_sale'), ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                    <div class="blog-one__item__content">
                                        <h3 class="blog-one__item__title">
                                            <a href="<?= htmlspecialchars($bookUrl, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($bookTitle, ENT_QUOTES, 'UTF-8'); ?></a>
                                        </h3>
                                        <?php if (!empty($book['short_description'])): ?>
                                            <p><?= htmlspecialchars((string)$book['short_description'], ENT_QUOTES, 'UTF-8'); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <div class="col-md-12 text-end">
                        <a href="<?= htmlspecialchars(racUrl('knihy.php'), ENT_QUOTES, 'UTF-8'); ?>" class="ogency-btn"><?= htmlspecialchars(__('action.view_all_work'), ENT_QUOTES, 'UTF-8'); ?></a>
                    </div>
                </div>
            </div>
        </section>

        <section class="about-one">
            <div class="container">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="about-one__thumb wow fadeInLeft animated" data-wow-delay="300ms">
                            <div class="about-one__thumb__round--top"></div>
                            <div class="about-one__thumb__img">
                                <img src="assets/images/resources/rac-inicialy-color.jpg" alt="O autorovi">
                            </div>
                            <div class="about-one__thumb__round--bottom"></div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="about-one__content">
                            <div class="section-title">
                                <h5 class="section-title__tagline section-title__tagline--has-dots"><?= htmlspecialchars(racCmsValue('author', 'tagline', 'O autorovi'), ENT_QUOTES, 'UTF-8'); ?></h5>
                                <h2 class="section-title__title"><?= htmlspecialchars(racCmsValue('author', 'title', 'Slova mají smysl, když v nich poznáš život.'), ENT_QUOTES, 'UTF-8'); ?></h2>
                            </div>
                            <p class="about-one__content__text-one"><?= nl2br(htmlspecialchars(racCmsValue('author', 'text_one', 'Štefan Rác je český autor, který ve své tvorbě zkoumá člověka, lidské vztahy a otázky, na které neexistují pohodlné odpovědi.'), ENT_QUOTES, 'UTF-8'), false); ?></p>
                            <p class="about-one__content__text-two"><?= nl2br(htmlspecialchars(racCmsValue('author', 'text_two', 'Jeho autorský svět propojuje sebepoznání, lásku, rodinu i kritický pohled na společnost. Každý rukopis stojí na přesvědčení, že silný příběh nezačíná efektem, ale opravdovostí.'), ENT_QUOTES, 'UTF-8'), false); ?></p>
                            <a href="<?= htmlspecialchars(racUrl('kontakt.php'), ENT_QUOTES, 'UTF-8'); ?>" class="ogency-btn"><?= htmlspecialchars(__('action.publisher_offers'), ENT_QUOTES, 'UTF-8'); ?></a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="blog-page">
            <div class="container">
                <div class="row">
                    <div class="col-lg-6 wow fadeInUp animated" data-wow-delay="200ms">
                        <div class="about-two__left">
                            <div class="section-title">
                                <h5 class="section-title__tagline section-title__tagline--has-dots"><?= htmlspecialchars(racCmsValue('upcoming_intro', 'tagline', __('section.next_chapters')), ENT_QUOTES, 'UTF-8'); ?></h5>
                                <h2 class="section-title__title"><?= htmlspecialchars(racCmsValue('upcoming_intro', 'title', __('section.work_in_progress')), ENT_QUOTES, 'UTF-8'); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 wow fadeInUp animated" data-wow-delay="400ms">
                        <div class="about-two__left">
                            <blockquote class="about-two__right--quote"><?= nl2br(htmlspecialchars(racCmsValue('upcoming_intro', 'quote', __('upcoming.quote')), ENT_QUOTES, 'UTF-8'), false); ?></blockquote>
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

        <section class="project-one @@extraClassName">
            <div class="container">
                <div class="row">
                    <div class="col-md-8">
                        <div class="section-title">
                            <h5 class="section-title__tagline section-title__tagline--has-dots"><?= htmlspecialchars(racCmsValue('contact', 'tagline', 'Některé věci začínají jednou zprávou.'), ENT_QUOTES, 'UTF-8'); ?></h5>
                            <h2 class="section-title__title"><?= nl2br(htmlspecialchars(racCmsValue('contact', 'title', 'Pro vydavatelské nabídky, spolupráci a dotazy ke knihám můžeš autora kontaktovat přímo e-mailem.'), ENT_QUOTES, 'UTF-8'), false); ?></h2>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <a href="<?= htmlspecialchars(racUrl('kontakt.php'), ENT_QUOTES, 'UTF-8'); ?>" class="ogency-btn"><?= htmlspecialchars(__('action.contact'), ENT_QUOTES, 'UTF-8'); ?></a>
                    </div>
                </div>
            </div>
        </section>

        <section class="video-one">
            <div class="container">
                <div class="video-one__banner wow fadeInUp animated animated" data-wow-delay="100ms">
                    <img src="assets/images/backgrounds/audio.jpg" alt="<?= htmlspecialchars(racCmsValue('audio', 'image_alt', 'Audioukázka'), ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="video-one__banner__shape wow fadeInRight animated animated" data-wow-delay="300ms">
                        <img src="assets/images/backgrounds/video-bg-shape-1-1.png" alt="">
                    </div>
                    <div class="video-one__banner__curved-circle-box wow fadeInUp animated animated" data-wow-delay="400ms">
                        <div class="curved-circle">
                            <span class="curved-circle-item">
                                <?= str_replace(' ', '&emsp;', htmlspecialchars(__('action.listen_audio_sample'), ENT_QUOTES, 'UTF-8')); ?>
                            </span>
                        </div>
                        <a href="#" class="video-popup" aria-label="<?= htmlspecialchars(__('action.listen_audio_sample'), ENT_QUOTES, 'UTF-8'); ?>">
                            <span class="fa fa-play"></span>
                        </a>
                    </div>
                </div>
            </div>
        </section>