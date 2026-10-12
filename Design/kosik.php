<?php
// RAC kosik, verze 2026-10-08-14.23-scroll-01
require_once __DIR__ . '/inc/01.config.class.php';
require_once __DIR__ . '/inc/02.language.class.php';
require_once __DIR__ . '/inc/rac-products.php';
require_once __DIR__ . '/inc/rac-cart.php';
$racCartError = null;
$racPdo = racFrontendPdo();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!$racPdo instanceof PDO) { $racCartError='Databáze momentálně není dostupná.'; }
    else {
        try { $racCartError=racCartHandlePost($racPdo); }
        catch (Throwable $e) { error_log('RAC cart: '.$e->getMessage()); $racCartError='Košík se nepodařilo upravit.'; }
        if ($racCartError === null) {
            $returnUrl = racUrl('kosik.php');
            $action = isset($_POST['action']) ? (string)$_POST['action'] : '';
            $variantId = filter_var($_POST['variant_id'] ?? null, FILTER_VALIDATE_INT, array('options'=>array('min_range'=>1)));
            if ($action === 'update' && $variantId !== false && $variantId !== null) {
                $returnUrl .= '#cart-item-' . (int)$variantId;
            }
            header('Location: ' . $returnUrl, true, 303);
            exit;
        }
    }
}
$racCartLines=array();
if ($racPdo instanceof PDO) {
    try { $racCartLines=racCartLines($racPdo,$currentLanguage); }
    catch (Throwable $e) { error_log('RAC cart load: '.$e->getMessage()); $racCartError='Košík není dostupný.'; }
}
include __DIR__ . '/inc/03.head.class.php';
include __DIR__ . '/inc/04.header.class.php';
include __DIR__ . '/obsah/kosik.class.php';
include __DIR__ . '/inc/05.footer.class.php';
include __DIR__ . '/inc/06.mobile-nav.class.php';
include __DIR__ . '/inc/07.scripts.class.php';
