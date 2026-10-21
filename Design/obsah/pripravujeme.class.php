<?php
// RAC - Připravujeme: knihy ve stavu koncept (draft), 2026-10-12.
// Stejné karty jako stránka Knihy, ale bez odkazu na nákup.
require_once dirname(__DIR__) . '/inc/rac-products.php';
$racUpcomingBooks = array();
$racUpcomingError = false;
$racUpcomingPdo = racFrontendPdo();
if ($racUpcomingPdo instanceof PDO) {
    try {
        list($racLangId, $racDefaultLangId, $racCzechLangId) = racShopLanguageIds($racUpcomingPdo, (string)$currentLanguage);
        $racUpcomingQuery = $racUpcomingPdo->prepare(
            "SELECT p.id, p.slug,
                COALESCE(NULLIF(t1.title, ''), NULLIF(t2.title, ''), NULLIF(t3.title, '')) AS title,
                COALESCE(NULLIF(t1.short_description, ''), NULLIF(t2.short_description, ''), NULLIF(t3.short_description, ''), '') AS short_description,
                (SELECT i.file_path FROM product_images i WHERE i.product_id = p.id
                 ORDER BY i.is_primary DESC, i.sort_order ASC, i.id ASC LIMIT 1) AS image
             FROM products p
             LEFT JOIN product_translations t1 ON t1.product_id = p.id AND t1.language_id = ?
             LEFT JOIN product_translations t2 ON t2.product_id = p.id AND t2.language_id = ?
             LEFT JOIN product_translations t3 ON t3.product_id = p.id AND t3.language_id = ?
             WHERE p.status = 'draft'
             ORDER BY p.sort_order ASC, p.id DESC"
        );
        $racUpcomingQuery->execute(array($racLangId, $racDefaultLangId, $racCzechLangId));
        $racUpcomingBooks = $racUpcomingQuery->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('RAC upcoming books: ' . $e->getMessage());
        $racUpcomingError = true;
    }
} else {
    $racUpcomingError = true;
}
?>
<div class="stricky-header stricked-menu main-menu">
    <div class="sticky-header__content"></div>
</div>
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
            <?php if ($racUpcomingError): ?>
                <div class="col-12"><p role="status"><?= htmlspecialchars(__('upcoming.unavailable') !== 'upcoming.unavailable' ? __('upcoming.unavailable') : 'Připravované knihy se nyní nepodařilo načíst.', ENT_QUOTES, 'UTF-8'); ?></p></div>
            <?php elseif (!$racUpcomingBooks): ?>
                <div class="col-12"><p><?= htmlspecialchars(__('upcoming.empty') !== 'upcoming.empty' ? __('upcoming.empty') : 'Momentálně nejsou evidovány žádné připravované knihy.', ENT_QUOTES, 'UTF-8'); ?></p></div>
            <?php else: ?>
                <?php foreach ($racUpcomingBooks as $book): ?>
                    <?php
                    $bookTitle = trim((string)($book['title'] ?? ''));
                    if ($bookTitle === '') { continue; }
                    $bookImage = racShopImageUrl($book['image'] ?? null);
                    ?>
                    <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="200ms">
                        <div class="blog-one__item">
                            <div class="blog-one__item__image">
                                <?php if ($bookImage !== ''): ?>
                                    <img loading="lazy" src="<?= htmlspecialchars($bookImage, ENT_QUOTES, 'UTF-8'); ?>" alt="<?= htmlspecialchars($bookTitle, ENT_QUOTES, 'UTF-8'); ?>">
                                <?php else: ?>
                                    <div class="rac-book-no-image"><?= htmlspecialchars(__('books.no_image') !== 'books.no_image' ? __('books.no_image') : 'Bez fotografie', ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endif; ?>
                                <span><?= htmlspecialchars(__('books.status.in_preparation'), ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                            <div class="blog-one__item__content">
                                <h3 class="blog-one__item__title"><?= htmlspecialchars($bookTitle, ENT_QUOTES, 'UTF-8'); ?></h3>
                                <?php if (!empty($book['short_description'])): ?>
                                    <p><?= htmlspecialchars((string)$book['short_description'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>
