<?php
/* RAC shared include. Porto HTML is identical to BOS24 when $racUsePortoLayout=true. */
if (empty($racUsePortoLayout)) {
    include __DIR__ . '/rac-legacy-leve-meny.class.php';
    return;
}
?>
<?php
/** RAC Porto shared left navigation; 2026-10-12-04. */
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
          // Sklad knih je spolecna polozka napric vsemi Porto strankami.
          $racWarehouseUrl='/administrace/eshop-sklad.php';
          foreach($menuGroups as &$racWarehouseGroup){
              if($racWarehouseGroup[0]==='E-shop'){
                  $racWarehouseExists=false;
                  foreach($racWarehouseGroup[1] as $racWarehouseItem){if(($racWarehouseItem[1]??'')===$racWarehouseUrl){$racWarehouseExists=true;break;}}
                  if(!$racWarehouseExists){
                      $racWarehouseGroup[1][]=['Sklad knih',$racWarehouseUrl,'icons icon-layers'];
                  }
              }
          }
          unset($racWarehouseGroup);
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
          // Zachovat ostatní sekce i jejich položky; známé sekce seřadit podle zadání.
          usort($menuGroups, static function ($a, $b) use ($racSections) {
              $ia = array_search($a[0], $racSections, true);
              $ib = array_search($b[0], $racSections, true);
              $ia = $ia === false ? count($racSections) : $ia;
              $ib = $ib === false ? count($racSections) : $ib;
              return $ia <=> $ib;
          });
          ?>
          <?php foreach ($menuGroups as $group): ?>
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
