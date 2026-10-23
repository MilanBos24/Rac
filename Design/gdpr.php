<?php
// RAC | Veřejná stránka GDPR | verze 2026-10-21-04 | PHP 7.4+
// Používá veřejný layout RAC a poslední publikovanou jazykovou verzi; fallback do češtiny.
require_once __DIR__ . '/inc/01.config.class.php';
require_once __DIR__ . '/inc/02.language.class.php';
require_once __DIR__ . '/inc/rac-products.php'; // Shodný frontendový PDO helper jako v produkt.php.
require_once __DIR__ . '/inc/content.class.php';

function racGdprEsc($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Renderuje pouze povolené značky bez atributů. Všechno ostatní zůstane textem. */
function racGdprSafeHtml(string $html): string {
    // Starší verze uložené jako prostý text zobrazíme v odstavcích.
    if (!preg_match('~<\s*/?\s*(?:p|br|strong|b|em|i|u|h2|h3|h4|ul|ol|li|blockquote|mark|span)\b~i', $html)) {
        $parts = preg_split('/\R\s*\R/u', trim($html));
        return implode('', array_map(static function ($part) {
            return '<p>' . nl2br(htmlspecialchars(trim($part), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) . '</p>';
        }, $parts ?: []));
    }
    $escaped = htmlspecialchars($html, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $escaped = str_replace('&lt;span class=&quot;rac-text-orange&quot;&gt;', 'RAC_ORANGE_SPAN_OPEN_TOKEN', $escaped);
    $out = preg_replace_callback(
        '~&lt;(/?)(p|br|strong|b|em|i|u|h2|h3|h4|ul|ol|li|blockquote|mark|span)\s*/?&gt;~i',
        static function ($m): string {
            $tag = strtolower($m[2]);
            if ($tag === 'br') { return '<br>'; }
            return '<' . ($m[1] === '/' ? '/' : '') . $tag . '>';
        },
        $escaped
    );
    if ($out === null) { return nl2br($escaped); }
    return str_replace('RAC_ORANGE_SPAN_OPEN_TOKEN', '<span class="rac-text-orange">', $out);
}

$allowedLanguages = ['cs', 'en', 'de', 'pl', 'fr'];
$requestedLanguage = isset($currentLanguage) ? strtolower((string)$currentLanguage) : 'cs';
if (!in_array($requestedLanguage, $allowedLanguages, true)) {
    $requestedLanguage = 'cs';
}

$ui = [
    'cs' => ['home' => 'Úvod', 'heading' => 'Zásady ochrany osobních údajů', 'version' => 'Verze', 'effective' => 'Účinné od', 'unavailable' => 'Zásady ochrany osobních údajů zatím nejsou k dispozici.'],
    'en' => ['home' => 'Home', 'heading' => 'Privacy Policy', 'version' => 'Version', 'effective' => 'Effective from', 'unavailable' => 'The Privacy Policy is not available yet.'],
    'de' => ['home' => 'Startseite', 'heading' => 'Datenschutzerklärung', 'version' => 'Version', 'effective' => 'Gültig ab', 'unavailable' => 'Die Datenschutzerklärung ist noch nicht verfügbar.'],
    'pl' => ['home' => 'Strona główna', 'heading' => 'Polityka prywatności', 'version' => 'Wersja', 'effective' => 'Obowiązuje od', 'unavailable' => 'Polityka prywatności nie jest jeszcze dostępna.'],
    'fr' => ['home' => 'Accueil', 'heading' => 'Politique de confidentialité', 'version' => 'Version', 'effective' => 'En vigueur depuis le', 'unavailable' => 'La politique de confidentialité n’est pas encore disponible.'],
];

$gdpr = null;
$displayLanguage = $requestedLanguage;
try {
    $pdo = racFrontendPdo();
    if ($pdo instanceof PDO) {
        $stmt = $pdo->prepare("SELECT v.id, v.language_code, v.title, v.content_html, v.version_number, v.version_label, v.effective_from, v.published_at
            FROM rac_legal_documents AS d
            INNER JOIN rac_legal_document_versions AS v ON v.document_id = d.id
            WHERE d.document_key = :document_key AND d.is_enabled = 1
              AND v.status = 'published' AND v.language_code = :language_code
              AND (v.effective_from IS NULL OR v.effective_from <= NOW())
            ORDER BY v.version_number DESC LIMIT 1");
        foreach (array_values(array_unique([$requestedLanguage, 'cs'])) as $candidate) {
            $stmt->execute([':document_key' => 'gdpr', ':language_code' => $candidate]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $gdpr = $result;
                $displayLanguage = $candidate;
                break;
            }
        }
    }
} catch (Throwable $ex) {
    error_log('[RAC GDPR public] ' . $ex->getMessage());
}
if (!$gdpr) { http_response_code(404); }

$labels = $ui[$displayLanguage];
$pageHeading = $gdpr && trim((string)$gdpr['title']) !== '' ? (string)$gdpr['title'] : $labels['heading'];
$racCmsPageContent = [
    'meta' => [
        'meta_title' => $pageHeading . ' | RAC',
        'meta_description' => $labels['heading'],
    ],
    'sections' => [],
];
include __DIR__ . '/inc/03.head.class.php';
include __DIR__ . '/inc/04.header.class.php';
?>
<style>
/* GDPR: barvy a typografie odpovídají tmavému veřejnému webu RAC. */
.rac-gdpr-page .page-header__title, .rac-gdpr-page .rac-gdpr-content,
.rac-gdpr-page .rac-gdpr-content p, .rac-gdpr-page .rac-gdpr-content li,
.rac-gdpr-page .rac-gdpr-content h2, .rac-gdpr-page .rac-gdpr-content h3,
.rac-gdpr-page .rac-gdpr-content h4, .rac-gdpr-page .rac-gdpr-content blockquote { color:#fff; }
.rac-gdpr-page .rac-gdpr-content .rac-text-orange { color:#f5900d !important; }
.rac-gdpr-page .rac-gdpr-content mark { background:#f5900d; color:#171717; padding:0 .12em; border-radius:2px; }
.rac-gdpr-page .rac-gdpr-content a { color:#ffc107; text-decoration:underline; }
.rac-gdpr-page .rac-gdpr-content a:hover { color:#fff; }
.rac-gdpr-page .blog-details { padding-top:70px; padding-bottom:90px; }
.rac-gdpr-page .rac-gdpr-content { font-size:16px; line-height:1.85; overflow-wrap:anywhere; }
.rac-gdpr-page .rac-gdpr-content p { margin:0 0 20px; }
.rac-gdpr-page .rac-gdpr-content h2 { font-size:25px; margin:38px 0 15px; }
.rac-gdpr-page .rac-gdpr-content h3 { font-size:21px; margin:30px 0 13px; }
.rac-gdpr-page .rac-gdpr-content h4 { font-size:18px; margin:25px 0 11px; }
.rac-gdpr-page .rac-gdpr-content ul, .rac-gdpr-page .rac-gdpr-content ol { padding-left:28px; margin-bottom:23px; }
.rac-gdpr-page .rac-gdpr-content li { padding-bottom:5px; }
.rac-gdpr-page .rac-gdpr-content blockquote { border-left:3px solid #ffc107; padding:8px 0 8px 20px; }
.rac-gdpr-page .rac-gdpr-meta { color:#fff; opacity:.85; margin:0 0 26px; }
@media(max-width:767px){.rac-gdpr-page .blog-details{padding-top:40px;padding-bottom:55px}.rac-gdpr-page .rac-gdpr-content h2{font-size:22px}}
</style>
<div class="rac-gdpr-page">
    <section class="page-header">
        <div class="page-header__bg"></div>
        <div class="page-header__overlay"></div>
        <div class="container">
            <ul class="page-header__breadcrumb list-unstyled">
                <li><a href="/"><?= racGdprEsc($labels['home']) ?></a></li>
                <li><span><?= racGdprEsc($labels['heading']) ?></span></li>
            </ul>
            <h1 class="page-header__title"><?= racGdprEsc($pageHeading) ?></h1>
        </div>
    </section>
    <section class="blog-details">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-11 col-xl-10">
                    <?php if ($gdpr): ?>
                        <div class="rac-gdpr-meta">
                            <?php if (!empty($gdpr['effective_from'])): ?>
                                <span><?= racGdprEsc($labels['effective']) ?> <?= racGdprEsc(date('d.m.Y', strtotime((string)$gdpr['effective_from']))) ?></span>
                            <?php endif; ?>
                        </div>
                        <article class="blog-details__content rac-gdpr-content" lang="<?= racGdprEsc($displayLanguage) ?>">
                            <?= racGdprSafeHtml((string)$gdpr['content_html']) ?>
                        </article>
                    <?php else: ?>
                        <p class="rac-gdpr-content"><?= racGdprEsc($labels['unavailable']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>
<?php
include __DIR__ . '/inc/05.footer.class.php';
include __DIR__ . '/inc/06.mobile-nav.class.php';
include __DIR__ . '/inc/07.scripts.class.php';
