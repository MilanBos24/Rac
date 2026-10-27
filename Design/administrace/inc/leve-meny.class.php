<?php
// RAC Porto – sjednocene leve menu – 2026-10-25-STEP07-FIX01 / PHP 7.4+
// Kompletni navigace je definovana POUZE zde. $menuGroups z jednotlivych stranek se nepouziva.
// Zachovano RBAC, oddeleni superadmin nastaveni a legacy fallback.
if (empty($racUsePortoLayout)) {
    include __DIR__ . '/rac-legacy-leve-meny.class.php';
    return;
}
require_once __DIR__ . '/../config/permissions.php';

$racMenuUser = current_user_row();
$racIsSuper = $racMenuUser && strtolower((string)($racMenuUser['role'] ?? '')) === 'superadmin';
$racMenuAllowed = static function (string $permission) use ($pdo, $racMenuUser, $racIsSuper): bool {
    return $racIsSuper || ($racMenuUser !== null && rac_can($pdo, $racMenuUser, $permission));
};

// Jediny zdroj polozek: stranky nemusi sestavovat vlastni $menuGroups.
$racCentralMenuGroups = [
    ['Přehled', [
        ['Dashboard', '/administrace/dashboard.php', 'bx bx-home-alt'],
    ]],
    ['Administrace', [
        ['Vložit administrátora', '/administrace/sprava-admin.php', 'icons icon-user-follow'],
        ['Zobrazit administrátory', '/administrace/sprava-admin-zobrazit.php', 'icons icon-people'],
        ['Kontaktní formulář', '/administrace/kontaktni-formular.php', 'icons icon-envelope'],
        ['Jazyky', '/administrace/jazyky.php', 'icons icon-globe'],
    ]],
    ['Obsah webu', [
        ['Úvodní stránka', '/administrace/obsah-uvod.php', 'icons icon-home'],
        ['O autorovi', '/administrace/obsah-autor.php', 'icons icon-note'],
        ['Kontakt', '/administrace/obsah-kontakt.php', 'icons icon-envelope'],
    ]],
    ['E-shop', [
        ['Produkty', '/administrace/eshop-produkty.php', 'icons icon-basket'],
        ['Sklad knih', '/administrace/eshop-sklad.php', 'icons icon-layers'],
        ['Objednávky', '/administrace/eshop-objednavky.php', 'icons icon-list'],
        ['Doprava', '/administrace/eshop-doprava.php', 'icons icon-drawer'],
        ['Platby e-shopu', '/administrace/eshop-platby.php', 'el el-credit-card'],
        ['Digitální přístupy', '/administrace/eshop-digitalni-pristupy.php', 'icons icon-link'],
        ['Nastavení doručovacího e-mailu', '/administrace/eshop-dorucovaci-email.php', 'icons icon-envelope'],
        ['Faktury', '/administrace/fakturace/faktury.php', 'icons icon-doc'],
        ['Nastavení fakturace', '/administrace/fakturace/nastaveni.php', 'icons icon-settings'],
    ]],
    ['Platební brána', [
        ['Nastavení Stripe', '/administrace/billing/admin.php', 'el el-cogs'],
    ]],
];

if ($racIsSuper) {
    $racCentralMenuGroups[] = ['Nastavení systému', [
        ['Role a oprávnění', '/administrace/nastaveni-role.php', 'icons icon-lock'],
        ['SQL', '/administrace/nastaveni-sql.php', 'icons icon-layers'],
        ['Webhooky', '/administrace/billing/admin-webhooky.php', 'icons icon-shuffle'],
        ['Diagnostika', '/administrace/billing/admin-diagnostika.php', 'icons icon-wrench'],
    ]];
}

// Presne stejna oprávnění jako v puvodni sdilene komponente.
$racMenuPermissions = [
    '/administrace/dashboard.php' => 'dashboard.view',
    '/administrace/eshop-produkty.php' => 'products.view',
    '/administrace/eshop-produkt.php' => 'products.view',
    '/administrace/eshop-fotografie.php' => 'products.view',
    '/administrace/eshop-soubory.php' => 'products.view',
    '/administrace/eshop-sklad.php' => 'stock.view',
    '/administrace/eshop-objednavky.php' => 'orders.view',
    '/administrace/eshop-objednavka.php' => 'orders.view',
    '/administrace/eshop-platby.php' => 'payments.view',
    '/administrace/eshop-digitalni-pristupy.php' => 'digital.view',
    '/administrace/eshop-dorucovaci-email.php' => 'contact.manage',
    '/administrace/fakturace/faktury.php' => 'orders.view',
    '/administrace/fakturace/nastaveni.php' => 'orders.view',
    '/administrace/eshop-doprava.php' => 'shipping.manage',
    '/administrace/obsah-uvod.php' => 'content.manage',
    '/administrace/obsah-autor.php' => 'content.manage',
    '/administrace/obsah-kontakt.php' => 'content.manage',
    '/administrace/jazyky.php' => 'languages.manage',
    '/administrace/kontaktni-formular.php' => 'contact.manage',
    '/administrace/sprava-admin.php' => 'users.manage',
    '/administrace/sprava-admin-zobrazit.php' => 'users.manage',
    '/administrace/billing/admin.php' => 'billing.manage',
    '/administrace/billing/admin-webhooky.php' => 'billing.manage',
    '/administrace/billing/admin-diagnostika.php' => 'billing.manage',
    '/administrace/nastaveni-role.php' => 'system.roles',
    '/administrace/nastaveni-sql.php' => 'system.roles',
];

$racVisibleGroups = [];
foreach ($racCentralMenuGroups as $racGroup) {
    $racVisibleItems = [];
    foreach ($racGroup[1] as $racItem) {
        $racUrl = $racItem[1];
        $racPerm = $racMenuPermissions[$racUrl] ?? null;
        if ($racIsSuper || ($racPerm !== null && $racMenuAllowed($racPerm))) {
            $racVisibleItems[] = $racItem;
        }
    }
    if ($racVisibleItems) {
        $racVisibleGroups[] = [$racGroup[0], $racVisibleItems];
    }
}

// Bezpecne escapovani nezavisle na tom, co je definovano v konkretni strance.
$racMenuEscape = static function ($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
};
if (!isset($currentPath) || !is_string($currentPath)) {
    $currentPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '';
}
// Podstranky produktů a objednávek maji v menu zvyrazneneho rodice.
$racMenuActivePath = $currentPath;
if (in_array($currentPath, [
    '/administrace/eshop-produkt.php',
    '/administrace/eshop-fotografie.php',
    '/administrace/eshop-soubory.php',
    '/administrace/eshop-soubory-vydani.php',
], true)) {
    $racMenuActivePath = '/administrace/eshop-produkty.php';
} elseif ($currentPath === '/administrace/eshop-objednavka.php') {
    $racMenuActivePath = '/administrace/eshop-objednavky.php';
}
?>
<aside id="sidebar-left" class="sidebar-left">
  <div class="sidebar-header">
    <div class="sidebar-title">Navigace</div>
    <div class="sidebar-toggle d-none d-md-block" data-toggle-class="sidebar-left-collapsed" data-target="html" data-fire-event="sidebar-left-toggle" role="button" tabindex="0" aria-label="Sbalit nebo rozbalit menu"><i class="fas fa-bars"></i></div>
  </div>
  <div class="nano">
    <div class="nano-content">
      <nav id="menu" class="nav-main" role="navigation">
        <ul class="nav nav-main">
          <?php foreach ($racVisibleGroups as $racGroup): ?>
            <li style="font-size: 12px; font-size: 0.75rem; padding: 12px 25px; text-overflow: ellipsis; text-transform: uppercase; color: #CFA9D8;"><?= $racMenuEscape($racGroup[0]) ?></li>
            <?php foreach ($racGroup[1] as $racItem): ?>
              <?php $racActive = $racMenuActivePath === $racItem[1]; ?>
              <li<?= $racActive ? ' class="nav-active"' : '' ?>>
                <a class="nav-link<?= $racActive ? ' active' : '' ?>" href="<?= $racMenuEscape($racItem[1]) ?>"<?= $racActive ? ' aria-current="page"' : '' ?>>
                  <i class="<?= $racMenuEscape($racItem[2]) ?>" aria-hidden="true"></i><span><?= $racMenuEscape($racItem[0]) ?></span>
                </a>
              </li>
            <?php endforeach; ?>
          <?php endforeach; ?>
        </ul>
      </nav>
    </div>
  </div>
</aside>
