<?php
// RAC katalog knih – integrace do původní šablony, verze 2026-10-08-13.45
// Soubor knihy.php zůstává beze změny.
require_once dirname(__DIR__) . '/inc/rac-products.php';
$racBooks = array();
$racBooksError = false;
$racPdo = racFrontendPdo();
if ($racPdo instanceof PDO) {
    try {
        $racBooks = racShopProducts($racPdo, $currentLanguage);
    } catch (Throwable $e) {
        error_log('RAC katalog knih: ' . $e->getMessage());
        $racBooksError = true;
    }
} else {
    $racBooksError = true;
}
?>
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
                <h2 class="page-header__title"><?= htmlspecialchars(__('page.books'), ENT_QUOTES, 'UTF-8'); ?></h2>
            </div>
        </section>

        <section class="blog-page">
            <div class="container">
                <div class="row">
                    <div class="col-lg-6 wow fadeInUp animated" data-wow-delay="200ms">
                        <div class="about-two__left">
                            <div class="section-title">
                                <h5 class="section-title__tagline section-title__tagline--has-dots"><?= htmlspecialchars(__('section.author_world'), ENT_QUOTES, 'UTF-8'); ?></h5>
                                <h2 class="section-title__title"><?= htmlspecialchars(__('section.books_projects'), ENT_QUOTES, 'UTF-8'); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 wow fadeInUp animated" data-wow-delay="400ms">
                        <div class="about-two__left">
                            <blockquote class="about-two__right--quote"><?= __('books.available_formats'); ?></blockquote>
                        </div>
                    </div>

                    <?php if ($racBooksError): ?>
                        <div class="col-12"><p role="status">Katalog knih je dočasně nedostupný.</p></div>
                    <?php elseif (!$racBooks): ?>
                        <div class="col-12"><p>Momentálně nejsou k dispozici žádné knihy.</p></div>
                    <?php else: ?>
                        <?php foreach ($racBooks as $book): ?>
                            <?php
                            if (trim((string)($book['title'] ?? '')) === '') {
                                continue;
                            }
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
                                    <div class="product-details__buttons">
                                        <a href="<?= htmlspecialchars($bookUrl, ENT_QUOTES, 'UTF-8'); ?>" class="ogency-btn wishlist"><?= htmlspecialchars(__('action.more_about_book'), ENT_QUOTES, 'UTF-8'); ?></a>
                                        <a href="<?= htmlspecialchars($bookUrl, ENT_QUOTES, 'UTF-8'); ?>" class="ogency-btn"><?= htmlspecialchars(__('action.buy'), ENT_QUOTES, 'UTF-8'); ?></a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>
