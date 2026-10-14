<?php
/** RAC Billing / BOS24 Porto test layout, 2026-10-12. */
declare(strict_types=1);

// Render original form in isolation, preserving every PHP variable and handler.
ob_start();
include __DIR__ . '/admin.class.php';
$legacyHtml = ob_get_clean();

// Extract only the billing module content. Discard RAC header/sidebar/footer.
$startTag = '<div class="billing-content">';
$start = strpos($legacyHtml, $startTag);
$end = strrpos($legacyHtml, '</main>');
if ($start === false || $end === false || $end <= $start) {
    // Fail visibly rather than emitting an incomplete page or losing the form.
    throw new RuntimeException('Nepodařilo se vyčlenit obsah Billing ze stávající šablony.');
}
$moduleHtml = substr($legacyHtml, $start + strlen($startTag), $end - ($start + strlen($startTag)));
// Only remove the terminal closing div of .billing-content.
$moduleHtml = preg_replace('~</div>\s*$~', '', $moduleHtml, 1);

$racUsePortoLayout = true;
$base = '/administrace';
$assets = $base;
$title = 'Billing – nastavení | RAC';
$viewerName = isset($viewer['username']) ? (string)$viewer['username'] : 'Administrátor';
$e = static function ($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
$menuGroups = [
    ['Přehled', [
        ['Dashboard', '/administrace/dashboard.php', 'bx bx-home-alt'],
    ]],
    ['Obsah webu', [
        ['Úvodní stránka', '/administrace/obsah-uvod.php', 'icons icon-home'],
        ['O autorovi', '/administrace/obsah-autor.php', 'icons icon-note'],
        ['Kontakt', '/administrace/obsah-kontakt.php', 'icons icon-envelope'],
        ['Jazyky', '/administrace/jazyky.php', 'icons icon-globe'],
    ]],
    ['E-shop', [
        ['Doprava', '/administrace/eshop-doprava.php', 'icons icon-drawer'],
    ]],
    ['Billing', [
        ['Nastavení Stripe', '/administrace/billing/admin.php', 'el el-cogs'],
        ['Produkty', '/administrace/billing/admin-produkty.php', 'icons icon-basket'],
        ['Platby', '/administrace/billing/admin-platby.php', 'el el-credit-card'],
        ['Předplatné', '/administrace/billing/admin-predplatne.php', 'icons icon-refresh'],
        ['Webhooky', '/administrace/billing/admin-webhooky.php', 'icons icon-shuffle'],
        ['Diagnostika', '/administrace/billing/admin-diagnostika.php', 'icons icon-wrench'],
    ]],
    ['Administrace', [
        ['Vložit administrátora', '/administrace/sprava-admin.php', 'icons icon-user-follow'],
        ['Zobrazit administrátory', '/administrace/sprava-admin-zobrazit.php', 'icons icon-people'],
    ]],
];
$currentPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '/administrace/billing/admin.php';
$viewerRole = isset($viewer['role']) ? (string)$viewer['role'] : '';
$roleLabel = function_exists('role_label') ? role_label($viewerRole) : $viewerRole;
?>
<?php include __DIR__ . '/../../inc/header.class.php'; ?>
<?php include __DIR__ . '/../../inc/leve-meny.class.php'; ?>
<section role="main" class="content-body content-body-modern mt-0">
<div class="billing-content">
<?= $moduleHtml ?>
</div>
<?php include __DIR__ . '/../../inc/footer.class.php'; ?>
