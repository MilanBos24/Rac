<?php
// RAC Dashboard – Porto/BOS24; verze 2026-10-12-23.05; PHP 7.4+
declare(strict_types=1);

require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/auth_roles.php';

require_login();

// Dashboard neuvadi ani nezapocitava superadminy do ukazatelu pro administratory.
$totalAdmins = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$totalBooks = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalActive = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1")->fetchColumn();

// Aktivace stejného společného Porto layoutu jako u ostatních stránek RAC.
$racUsePortoLayout = true;
$base = '/administrace';
$assets = $base;
$pageTitle = 'Dashboard';
$title = 'Dashboard | RAC';
$viewer = current_user_row();
$viewerName = isset($viewer['username']) ? (string)$viewer['username'] : 'Administrátor';
$e = static function ($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
$menuGroups = [
    ['Přehled', [
        ['Dashboard', '/administrace/dashboard.php', 'bx bx-home-alt'],
    ]],
    ['Administrace', [
        ['Vložit administrátora', '/administrace/sprava-admin.php', 'icons icon-user-follow'],
        ['Zobrazit administrátory', '/administrace/sprava-admin-zobrazit.php', 'icons icon-people'],
        ['Jazyky', '/administrace/jazyky.php', 'icons icon-globe'],
    ]],
    ['Obsah webu', [
        ['Úvodní stránka', '/administrace/obsah-uvod.php', 'icons icon-home'],
        ['O autorovi', '/administrace/obsah-autor.php', 'icons icon-note'],
        ['Kontakt', '/administrace/obsah-kontakt.php', 'icons icon-envelope'],
    ]],
    ['E-shop', [
        ['Produkty', '/administrace/eshop-produkty.php', 'icons icon-basket'],
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
];
$currentPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '/administrace/dashboard.php';
$viewerRole = isset($viewer['role']) ? (string)$viewer['role'] : '';
$roleLabel = function_exists('role_label') ? role_label($viewerRole) : $viewerRole;

include __DIR__ . '/inc/header.class.php';
include __DIR__ . '/inc/leve-meny.class.php';
?>
<section role="main" class="content-body content-body-modern mt-0">
    <header class="page-header">
        <h2>Dashboard</h2>
        <div class="right-wrapper text-end">
            <ol class="breadcrumbs">
                <li><a href="/administrace/dashboard.php"><i class="bx bx-home-alt"></i></a></li>
                <li><span>Přehled</span></li>
                <li><span>Dashboard</span></li>
            </ol>
        </div>
    </header>

    <!-- Akční navigace podle fakturace/fakturace_dashboard.php. -->
    <div class="row justify-content-center justify-content-sm-between pt-2">
        <div class="col-sm-auto text-center text-sm-start mb-4 mb-sm-0 mt-2 mt-sm-0 d-flex flex-wrap justify-content-center justify-content-sm-start gap-2">
            <a href="/administrace/sprava-admin.php" class="btn btn-primary btn-md font-weight-semibold btn-py-2 px-4">+ Přidat administrátora</a>
            <a href="/administrace/eshop-produkt.php" class="btn btn-primary btn-md font-weight-semibold btn-py-2 px-4">+ Přidat knihu</a>
            <a href="/administrace/eshop-sklad.php" class="btn btn-primary btn-md font-weight-semibold btn-py-2 px-4">Sklad knih</a>
            <a href="/administrace/eshop-doprava.php" class="btn btn-primary btn-md font-weight-semibold btn-py-2 px-4">Doprava</a>
        </div>
    </div>

    <!-- Shodný widget-summary a barevné levé orámování jako ve fakturačním dashboardu. -->
    <div class="row mb-4">
        <div class="col-lg-4 col-md-6 mb-3">
            <section class="card card-featured-left card-featured-primary h-100">
                <div class="card-body">
                    <div class="widget-summary">
                        <div class="widget-summary-col">
                            <div class="summary">
                                <h4 class="title">Administrátoři celkem</h4>
                                <div class="info"><strong class="amount"><?= $totalAdmins ?></strong></div>
                            </div>
                            <div class="summary-footer"><a href="/administrace/sprava-admin-zobrazit.php" class="text-muted text-uppercase">Zobrazit</a></div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <div class="col-lg-4 col-md-6 mb-3">
            <section class="card card-featured-left card-featured-warning h-100">
                <div class="card-body">
                    <div class="widget-summary">
                        <div class="widget-summary-col">
                            <div class="summary">
                                <h4 class="title">Knihy celkem</h4>
                                <div class="info"><strong class="amount"><?= $totalBooks ?></strong></div>
                            </div>
                            <div class="summary-footer"><a href="/administrace/eshop-produkty.php" class="text-muted text-uppercase">Zobrazit produkty</a></div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <div class="col-lg-4 col-md-6 mb-3">
            <section class="card card-featured-left card-featured-success h-100">
                <div class="card-body">
                    <div class="widget-summary">
                        <div class="widget-summary-col">
                            <div class="summary">
                                <h4 class="title">Aktivní účty</h4>
                                <div class="info"><strong class="amount"><?= $totalActive ?></strong></div>
                            </div>
                            <div class="summary-footer"><a href="/administrace/sprava-admin-zobrazit.php" class="text-muted text-uppercase">Zobrazit</a></div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</section>
<?php include __DIR__ . '/inc/footer.class.php'; ?>
