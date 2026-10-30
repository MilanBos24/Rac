<?php
/* RAC shared include. Porto HTML is identical to BOS24 when $racUsePortoLayout=true. */
if (empty($racUsePortoLayout)) {
    include __DIR__ . '/rac-legacy-leve-meny.class.php';
    return;
}
?>
<?php
/** RAC Porto shared left navigation; 2026-10-27-16.25 – právní dokumenty. */
?>
<?php
// RAC RBAC – viditelnost navigace odpovida serverove kontrole faze 2.
require_once __DIR__ . '/../config/permissions.php';
$racMenuUser = current_user_row();
$racIsSuper = $racMenuUser && strtolower((string)($racMenuUser['role'] ?? '')) === 'superadmin';
$racMenuAllowed = static function (string $permission) use ($pdo, $racMenuUser, $racIsSuper): bool {
    return $racIsSuper || ($racMenuUser !== null && rac_can($pdo, $racMenuUser, $permission));
};
// Shodne se seznamem v auth_roles.php (faze 2).
$racMenuPermissions = [
 '/administrace/dashboard.php'=>'dashboard.view',
 '/administrace/eshop-produkty.php'=>'products.view',
 '/administrace/eshop-produkt.php'=>'products.view',
 '/administrace/eshop-fotografie.php'=>'products.view',
 '/administrace/eshop-soubory.php'=>'products.view',
 '/administrace/eshop-sklad.php'=>'stock.view',
 '/administrace/eshop-objednavky.php'=>'orders.view',
 '/administrace/eshop-objednavka.php'=>'orders.view',
 '/administrace/eshop-platby.php'=>'payments.view',
 '/administrace/eshop-digitalni-pristupy.php'=>'digital.view',
 '/administrace/eshop-doprava.php'=>'shipping.manage',
 '/administrace/obsah-uvod.php'=>'content.manage',
 '/administrace/obsah-autor.php'=>'content.manage',
 '/administrace/obsah-kontakt.php'=>'content.manage',
 '/administrace/pravni-dokumenty.php'=>'content.manage',
 '/administrace/eshop-dorucovaci-email.php'=>'contact.manage',
 '/administrace/fakturace/faktury.php'=>'orders.view',
 '/administrace/fakturace/nastaveni.php'=>'billing.manage',
 '/administrace/jazyky.php'=>'languages.manage',
 '/administrace/kontaktni-formular.php'=>'contact.manage',
 '/administrace/sprava-admin.php'=>'users.manage',
 '/administrace/sprava-admin-zobrazit.php'=>'users.manage',
 '/administrace/billing/admin.php'=>'billing.manage',
 '/administrace/billing/admin-platby.php'=>'billing.manage',
 '/administrace/billing/admin-webhooky.php'=>'billing.manage',
 '/administrace/billing/admin-diagnostika.php'=>'billing.manage',
 '/administrace/nastaveni-role.php'=>'system.roles',
 '/administrace/nastaveni-sql.php'=>'system.roles',
];
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
          <?php
          // Jednotná položka E-shop / Produkty i na starších stránkách s vlastním $menuGroups.
          // Nepřepisuje definici menu na žádné stránce a nevytváří duplicity.
          $racProductsUrl = '/administrace/eshop-produkty.php';
          $racHasProducts = false;
          foreach ($menuGroups as $racGroup) {
              foreach ($racGroup[1] as $racItem) {
                  if ($racItem[1] === $racProductsUrl) { $racHasProducts = true; break 2; }
              }
          }
          if (!$racHasProducts) {
              $racAdded = false;
              foreach ($menuGroups as &$racGroup) {
                  if ($racGroup[0] === 'E-shop') {
                      array_unshift($racGroup[1], ['Produkty', $racProductsUrl, 'icons icon-basket']);
                      $racAdded = true;
                      break;
                  }
              }
              unset($racGroup);
              if (!$racAdded) $menuGroups[] = ['E-shop', [['Produkty', $racProductsUrl, 'icons icon-basket']]];
          }
          ?>
          <?php
          // Sklad, objednavky, platby a digitalni pristupy eshopu centralne bez duplicit i na starsich strankach.
          foreach ($menuGroups as &$racShopGroup) {
              if (($racShopGroup[0] ?? '') !== 'E-shop') { continue; }
              foreach ([['Sklad knih', '/administrace/eshop-sklad.php', 'icons icon-layers'],
                        ['Objednávky', '/administrace/eshop-objednavky.php', 'icons icon-list'],
                        ['Platby e-shopu', '/administrace/eshop-platby.php', 'el el-credit-card'],
                        ['Digitální přístupy', '/administrace/eshop-digitalni-pristupy.php', 'icons icon-link']] as $racShopEntry) {
                  $racFound = false;
                  foreach ($racShopGroup[1] as $racExisting) {
                      if (($racExisting[1] ?? '') === $racShopEntry[1]) { $racFound = true; break; }
                  }
                  if (!$racFound) { $racShopGroup[1][] = $racShopEntry; }
              }
          }
          unset($racShopGroup);
          // Faktury: pouze jedna položka v E-shopu, ihned za Objednávkami.
          $racInvoicesUrl = '/administrace/fakturace/faktury.php';
          $racInvoicesItem = ['Faktury', $racInvoicesUrl, 'icons icon-doc'];
          foreach ($menuGroups as &$racInvoicesGroup) {
              $racInvoicesGroup[1] = array_values(array_filter($racInvoicesGroup[1], static function ($item) use ($racInvoicesUrl) {
                  return ($item[1] ?? '') !== $racInvoicesUrl;
              }));
              if (($racInvoicesGroup[0] ?? '') === 'E-shop') {
                  $racInsertAt = count($racInvoicesGroup[1]);
                  foreach ($racInvoicesGroup[1] as $racIdx => $racEntry) {
                      if (($racEntry[1] ?? '') === '/administrace/eshop-objednavky.php') {
                          $racInsertAt = $racIdx + 1;
                          break;
                      }
                  }
                  array_splice($racInvoicesGroup[1], $racInsertAt, 0, [$racInvoicesItem]);
              }
          }
          unset($racInvoicesGroup);
          // Právní dokumenty: jednotná položka v sekci E-shop i na starších stránkách.
          $racLegalUrl = '/administrace/pravni-dokumenty.php';
          $racLegalItem = ['Právní dokumenty', $racLegalUrl, 'icons icon-note'];
          $racLegalGroupFound = false;
          foreach ($menuGroups as &$racGroup) {
              $racGroup[1] = array_values(array_filter($racGroup[1], static function ($racItem) use ($racLegalUrl) {
                  return ($racItem[1] ?? '') !== $racLegalUrl;
              }));
              if ($racGroup[0] === 'E-shop') {
                  $racGroup[1][] = $racLegalItem;
                  $racLegalGroupFound = true;
              }
          }
          unset($racGroup);
          if (!$racLegalGroupFound) {
              $menuGroups[] = ['E-shop', [$racLegalItem]];
          }
          // Zachovat i Kontaktní formulář z aktuálního menu RAC v Administraci.
          $racContactUrl = '/administrace/kontaktni-formular.php';
          foreach ($menuGroups as &$racGroup) {
              $racGroup[1] = array_values(array_filter($racGroup[1], static function ($i) use ($racContactUrl) {
                  return ($i[1] ?? '') !== $racContactUrl;
              }));
          }
          unset($racGroup);
          foreach ($menuGroups as &$racGroup) {
              if ($racGroup[0] === 'Administrace') {
                  $racGroup[1][] = ['Kontaktní formulář', $racContactUrl, 'icons icon-envelope'];
                  break;
              }
          }
          unset($racGroup);
          // Jednotné pořadí sekcí a přesun Jazyků do Administrace.
          // Funguje také u starších stránek, které mají vlastní definici $menuGroups.
          $racSections = ['Přehled', 'Administrace', 'Obsah webu', 'E-shop', 'Platební brána'];
          $racLanguagesUrl = '/administrace/jazyky.php';
          $racLanguagesItem = null;
          foreach ($menuGroups as &$racGroup) {
              $racGroup[1] = array_values(array_filter($racGroup[1], static function ($racItem) use (&$racLanguagesItem, $racLanguagesUrl) {
                  if (($racItem[1] ?? '') === $racLanguagesUrl) {
                      $racLanguagesItem = $racItem;
                      return false;
                  }
                  return true;
              }));
          }
          unset($racGroup);
          if ($racLanguagesItem === null) {
              $racLanguagesItem = ['Jazyky', $racLanguagesUrl, 'icons icon-globe'];
          }
          $racAdminIndex = null;
          foreach ($menuGroups as $racIndex => $racGroup) {
              if ($racGroup[0] === 'Administrace') { $racAdminIndex = $racIndex; break; }
          }
          if ($racAdminIndex === null) {
              $menuGroups[] = ['Administrace', [$racLanguagesItem]];
          } else {
              $menuGroups[$racAdminIndex][1][] = $racLanguagesItem;
          }
          // RAC servis 2026-10-20: starší Billing stránky přesunuty mimo Platební bránu.
          $racBillingRetired = [
              '/administrace/billing/admin-platby.php',
              '/administrace/billing/admin-webhooky.php',
              '/administrace/billing/admin-diagnostika.php',
          ];
          foreach ($menuGroups as &$racBillingGroup) {
              $racBillingGroup[1] = array_values(array_filter($racBillingGroup[1], static function ($racItem) use ($racBillingRetired) {
                  return !in_array((string)($racItem[1] ?? ''), $racBillingRetired, true);
              }));
          }
          unset($racBillingGroup);
          // Přejmenovat Billing a skrýt pouze billingové Produkty a Předplatné.
          // Položku E-shop / Produkty zachovat.
          foreach ($menuGroups as &$racGroup) {
              if ($racGroup[0] === 'Billing' || $racGroup[0] === 'Platební brána') {
                  $racGroup[0] = 'Platební brána';
                  $racGroup[1] = array_values(array_filter($racGroup[1], static function ($racItem) {
                      $racUrl = (string)($racItem[1] ?? '');
                      $racLabel = (string)($racItem[0] ?? '');
                      return !in_array($racUrl, [
                          '/administrace/billing/admin-produkty.php',
                          '/administrace/billing/admin-predplatne.php',
                      ], true) && !in_array($racLabel, ['Produkty', 'Předplatné'], true);
                  }));
              }
          }
          unset($racGroup);
          // Systémová nastavení nesmí zůstat v E-shopu, Administraci ani jiných sekcích.
          $racSystemSettingsUrls = [
              '/administrace/eshop-dorucovaci-email.php',
              '/administrace/fakturace/nastaveni.php',
          ];
          foreach ($menuGroups as &$racSettingsGroup) {
              $racSettingsGroup[1] = array_values(array_filter($racSettingsGroup[1], static function ($racItem) use ($racSystemSettingsUrls) {
                  return !in_array((string)($racItem[1] ?? ''), $racSystemSettingsUrls, true);
              }));
          }
          unset($racSettingsGroup);
          // Zachovat ostatní sekce i jejich položky; známé sekce seřadit podle zadání.
          usort($menuGroups, static function ($a, $b) use ($racSections) {
              $ia = array_search($a[0], $racSections, true);
              $ib = array_search($b[0], $racSections, true);
              $ia = $ia === false ? count($racSections) : $ia;
              $ib = $ib === false ? count($racSections) : $ib;
              return $ia <=> $ib;
          });
          ?>
          <?php
          // Odstranit staré lokální definice Nastavení systému, aby nevznikaly duplicitní sekce.
          // Finální systémové menu sestavujeme centrálně níže pouze pro superadmina.
          $menuGroups = array_values(array_filter($menuGroups, static function ($group) {
              return ($group[0] ?? '') !== 'Nastavení systému';
          }));
          // Nastaveni systemu neni soucasti bezne administrace.
          if ($racIsSuper) {
              $menuGroups[] = ['Nastavení systému', [
                  ['Nastavení doručovacího e-mailu', '/administrace/eshop-dorucovaci-email.php', 'icons icon-envelope'],
                  ['Nastavení fakturace', '/administrace/fakturace/nastaveni.php', 'icons icon-doc'],
                  ['Role a oprávnění', '/administrace/nastaveni-role.php', 'icons icon-lock'],
                  ['SQL', '/administrace/nastaveni-sql.php', 'icons icon-layers'],
                  ['Webhooky', '/administrace/billing/admin-webhooky.php', 'icons icon-shuffle'],
                  ['Diagnostika', '/administrace/billing/admin-diagnostika.php', 'icons icon-wrench'],
              ]];
          }
          // Skrývat nedostupné URL; neznámé položky neukazovat běžným rolím.
          $racVisibleGroups = [];
          foreach ($menuGroups as $racVisibleGroup) {
              $racVisibleItems = [];
              foreach (($racVisibleGroup[1] ?? []) as $racVisibleItem) {
                  $racUrl = (string)($racVisibleItem[1] ?? '');
                  $racPerm = $racMenuPermissions[$racUrl] ?? null;
                  if ($racIsSuper || ($racPerm !== null && $racMenuAllowed($racPerm))) {
                      $racVisibleItems[] = $racVisibleItem;
                  }
              }
              if ($racVisibleItems) {
                  $racVisibleGroups[] = [$racVisibleGroup[0], $racVisibleItems];
              }
          }
          ?>
          <?php foreach ($racVisibleGroups as $group): ?>
            <li style="font-size: 12px; font-size: 0.75rem; padding: 12px 25px; text-overflow: ellipsis; text-transform: uppercase; color: #CFA9D8;"><?= $e($group[0]) ?></li>
            <?php foreach ($group[1] as $item): ?>
              <?php $active = $currentPath === $item[1]; ?>
              <li<?= $active ? ' class="nav-active"' : '' ?>>
                <a class="nav-link<?= $active ? ' active' : '' ?>" href="<?= $e($item[1]) ?>"<?= $active ? ' aria-current="page"' : '' ?>>
                  <i class="<?= $e($item[2]) ?>" aria-hidden="true"></i><span><?= $e($item[0]) ?></span>
                </a>
              </li>
            <?php endforeach; ?>
          <?php endforeach; ?>
        </ul>
      </nav>
    </div>
  </div>
</aside>
