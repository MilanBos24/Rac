<?php
/* RAC shared include. Porto HTML is identical to BOS24 when $racUsePortoLayout=true. */
if (empty($racUsePortoLayout)) {
    include __DIR__ . '/rac-legacy-header.class.php';
    return;
}
?>
<?php
/** RAC Porto header – 2026-10-20-09-12. Top navigation + yellow active/hover styles. */
?>
<?php
// RAC RBAC navigace, 2026-10-20-faze3. Fail closed pro neznama prava.
require_once __DIR__ . '/../config/permissions.php';
$racMenuUser = current_user_row();
$racIsSuper = $racMenuUser && strtolower((string)($racMenuUser['role'] ?? '')) === 'superadmin';
$racMenuAllowed = static function (string $permission) use ($pdo, $racMenuUser, $racIsSuper): bool {
    return $racIsSuper || ($racMenuUser !== null && rac_can($pdo, $racMenuUser, $permission));
};
?>
<!doctype html>
<html class="fixed header-dark" lang="cs">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $e($title) ?></title>
<?php include __DIR__ . "/links.class.php"; ?>
<?php include __DIR__ . "/scripts-top.class.php"; ?>
<style id="rac-yellow-navigation">
/* RAC Porto – sjednocena zluta pro horní i levé menu (2026-10-20). */
.header #mainNav .nav-link:hover,
.header #mainNav .nav-link:focus-visible,
.header #mainNav .nav-link.active,
.header #mainNav .nav-link.active:hover,
.header #mainNav .nav-link.active:focus-visible {
  color: #ffc107 !important;
}
.header #mainNav .nav-link:hover i,
.header #mainNav .nav-link:focus-visible i,
.header #mainNav .nav-link.active i,
.header #mainNav .nav-link.active:hover i { color: #ffc107 !important; }
/* Sidebar: zachovat pozadi a chovani Porto, menit jen barvu textu a ikon. */
#sidebar-left .nav-main li > a.nav-link:hover,
#sidebar-left .nav-main li > a.nav-link:focus-visible,
#sidebar-left .nav-main li.nav-active > a.nav-link,
#sidebar-left .nav-main li > a.nav-link.active {
  color: #ffc107 !important;
}
#sidebar-left .nav-main li > a.nav-link:hover i,
#sidebar-left .nav-main li > a.nav-link:focus-visible i,
#sidebar-left .nav-main li.nav-active > a.nav-link i,
#sidebar-left .nav-main li > a.nav-link.active i { color: #ffc107 !important; }
</style>

</head>
<body>
<section class="body">
<header class="header header-nav-menu header-nav-links">
  <div class="logo-container">
    <a href="/administrace/dashboard.php" class="logo" style="color: #ffc107;"><strong style="color: #ffc107;">RAC</strong><span style="color: #ffc107;"> Administrace</span></a>
    <div class="d-md-none toggle-sidebar-left" data-toggle-class="sidebar-left-opened" data-target="html" data-fire-event="sidebar-left-opened" title="Levé menu" aria-label="Levé menu">
      <i class="fas fa-bars" aria-hidden="true"></i>
    </div>
    <button class="btn header-btn-collapse-nav d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#racTopNavigation" aria-controls="racTopNavigation" aria-expanded="false" aria-label="Horní navigace" title="Horní navigace"><i class="fas fa-ellipsis-v" aria-hidden="true"></i></button>
    <div class="header-nav collapse" id="racTopNavigation">
      <div class="header-nav-main header-nav-main-effect-1 header-nav-main-sub-effect-1 header-nav-main-square">
        <nav aria-label="Hlavní navigace">
          <?php
          $racNavPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '';
          $racTopItems = [
            ['Dashboard', '/administrace/dashboard.php', 'bx bx-home-alt', ['dashboard.php']],
            ['Produkty', '/administrace/eshop-produkty.php', 'icons icon-basket', ['eshop-produkty.php', 'eshop-produkt.php', 'eshop-fotografie.php', 'eshop-soubory.php']],
            ['Sklad', '/administrace/eshop-sklad.php', 'icons icon-layers', ['eshop-sklad.php']],
            ['Objednávky', '/administrace/eshop-objednavky.php', 'icons icon-list', ['eshop-objednavky.php', 'eshop-objednavka.php']],
          ];
          $racNavFile = basename($racNavPath);
          ?>
          <ul class="nav nav-pills" id="mainNav">
            <?php foreach ($racTopItems as $racTopItem): ?>
              <?php
                $racTopPermission = [
                  '/administrace/dashboard.php'=>'dashboard.view',
                  '/administrace/eshop-produkty.php'=>'products.view',
                  '/administrace/eshop-sklad.php'=>'stock.view',
                  '/administrace/eshop-objednavky.php'=>'orders.view',
                ][$racTopItem[1]] ?? null;
                if ($racTopPermission === null || !$racMenuAllowed($racTopPermission)) continue;
              ?>
              <?php $racNavActive = in_array($racNavFile, $racTopItem[3], true); ?>
              <li><a class="nav-link<?= $racNavActive ? ' active' : '' ?>" href="<?= $e($racTopItem[1]) ?>"<?= $racNavActive ? ' aria-current="page"' : '' ?>>
                <i class="<?= $e($racTopItem[2]) ?>" aria-hidden="true"></i><span><?= $e($racTopItem[0]) ?></span>
              </a></li>
            <?php endforeach; ?>
          </ul>
        </nav>
      </div>
    </div>
  </div>
  <div class="header-right">
    <div id="userbox" class="userbox">
      <a href="#" data-bs-toggle="dropdown" aria-expanded="false">
        <figure class="profile-picture"><i class="bx bx-user-circle" aria-hidden="true"></i></figure>
        <div class="profile-info">
          <span class="name"><?= $e($viewerName) ?></span>
          <span class="role"><?= $e($roleLabel) ?></span>
        </div>
        <i class="fa custom-caret"></i>
      </a>
      <div class="dropdown-menu">
        <ul class="list-unstyled mb-2">
          <li><a href="/administrace/logout.php"><i class="bx bx-power-off"></i> Odhlásit</a></li>
        </ul>
      </div>
    </div>
  </div>
</header>
<div class="inner-wrapper">
