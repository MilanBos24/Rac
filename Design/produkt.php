<?php
// RAC detail knihy – verze 2026-10-08-URL01
require_once __DIR__ . '/inc/01.config.class.php';
require_once __DIR__ . '/inc/02.language.class.php';
require_once __DIR__ . '/inc/rac-products.php';

$slug = isset($_GET['slug']) && is_string($_GET['slug']) ? $_GET['slug'] : '';
$product = null;
$variants = array();
$images = array();
if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $slug)) {
    $pdo = racFrontendPdo();
    if ($pdo instanceof PDO) {
        try {
            $product = racShopProduct($pdo, $slug, $currentLanguage);
            if ($product) {
                $variants = racShopVariants($pdo, (int)$product['id']);
                $images = racShopImages($pdo, (int)$product['id']);
            }
        } catch (Throwable $e) {
            error_log('RAC product load failed: ' . $e->getMessage());
            $product = null;
        }
    }
}

// Presmerovani pouze pri primem pozadavku na produkt.php?slug=...
if ($product && ($_SERVER['REQUEST_METHOD'] ?? '') === 'GET') {
    $requestedPath = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    if (basename($requestedPath) === 'produkt.php') {
        $basePath = rtrim(str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/produkt.php'))), '/');
        if ($basePath === '.' || $basePath === '/') { $basePath = ''; }
        $newUrl = $basePath . '/' . rawurlencode($slug);
        if ($currentLanguage !== DEFAULT_LANGUAGE) {
            $newUrl .= '?lang=' . rawurlencode($currentLanguage);
        }
        header('Location: ' . $newUrl, true, 301);
        exit;
    }
}
if (!$product) { http_response_code(404); }
require_once __DIR__ . '/inc/content.class.php';
$racCmsPageContent = array('meta' => array(
    'meta_title' => $product ? ((string)$product['seo_title'] ?: (string)$product['title']) : 'Kniha nenalezena',
    'meta_description' => $product ? ((string)$product['seo_description'] ?: (string)$product['short_description']) : ''
), 'sections' => array());
include __DIR__ . '/inc/03.head.class.php';
include __DIR__ . '/inc/04.header.class.php';
include __DIR__ . '/obsah/produkt.class.php';
include __DIR__ . '/inc/05.footer.class.php';
include __DIR__ . '/inc/06.mobile-nav.class.php';
include __DIR__ . '/inc/07.scripts.class.php';
