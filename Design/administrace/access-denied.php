<?php
declare(strict_types=1);
require_once __DIR__.'/config/session.php';
require_once __DIR__.'/config/db.php';
require_once __DIR__.'/config/auth.php';
require_once __DIR__.'/config/auth_roles.php';
require_login();
// Stejné společné soubory a výchozí proměnné jako ve funkčním Billingu.
$racUsePortoLayout = true;
$title = 'Přístup odepřen | RAC Administrace';
$e = static function ($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
$viewer = function_exists('current_user_row') ? current_user_row() : null;
$viewerName = isset($viewer['username']) ? (string)$viewer['username'] : 'Administrátor';
$viewerRole = isset($viewer['role']) ? (string)$viewer['role'] : '';
$roleLabel = function_exists('role_label') ? role_label($viewerRole) : $viewerRole;
$currentPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '/administrace/access-denied.php';
$menuGroups = [
    ['Přehled', [['Dashboard','/administrace/dashboard.php','bx bx-home-alt']]],
    ['Obsah webu', [
        ['Úvodní stránka','/administrace/obsah-uvod.php','icons icon-home'],
        ['O autorovi','/administrace/obsah-autor.php','icons icon-note'],
        ['Kontakt','/administrace/obsah-kontakt.php','icons icon-envelope'],
        ['Jazyky','/administrace/jazyky.php','icons icon-globe'],
    ]],
    ['E-shop', [['Doprava','/administrace/eshop-doprava.php','icons icon-drawer']]],
    ['Billing', [
        ['Nastavení Stripe','/administrace/billing/admin.php','el el-cogs'],
        ['Produkty','/administrace/billing/admin-produkty.php','icons icon-basket'],
        ['Platby','/administrace/billing/admin-platby.php','el el-credit-card'],
        ['Předplatné','/administrace/billing/admin-predplatne.php','icons icon-refresh'],
        ['Webhooky','/administrace/billing/admin-webhooky.php','icons icon-shuffle'],
        ['Diagnostika','/administrace/billing/admin-diagnostika.php','icons icon-wrench'],
    ]],
    ['Administrace', [
        ['Vložit administrátora','/administrace/sprava-admin.php','icons icon-user-follow'],
        ['Zobrazit administrátory','/administrace/sprava-admin-zobrazit.php','icons icon-people'],
    ]],
];
include __DIR__.'/inc/header.class.php';
include __DIR__.'/inc/leve-meny.class.php';
?>
<section role="main" class="content-body content-body-modern mt-0">
<header class="page-header"><h2>Přístup odepřen</h2><div class="right-wrapper text-end"><ol class="breadcrumbs"><li><a href="/administrace/dashboard.php"><i class="bx bx-home-alt"></i></a></li><li><span>Přístup odepřen</span></li></ol></div></header>
<div class="alert alert-danger mt-4">K této části administrace nemáte potřebné oprávnění.</div>
<a class="btn btn-light" href="dashboard.php">Zpět na dashboard</a>
</section>
<?php include __DIR__.'/inc/footer.class.php'; ?>
