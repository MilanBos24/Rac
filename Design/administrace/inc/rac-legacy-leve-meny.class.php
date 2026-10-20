<?php
$currentScript = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
$isBillingPage = strpos((string)($_SERVER['SCRIPT_NAME'] ?? ''), '/billing/') !== false;
$adminPrefix = $isBillingPage ? '../' : '';
$billingPrefix = $isBillingPage ? '' : 'billing/';
$legacyItems = [
 ['Přehled',[['Dashboard','dashboard.php','⌂']]],
 ['Obsah webu',[['Úvodní stránka','obsah-uvod.php','⌂'],['O autorovi','obsah-autor.php','✎'],['Kontakt','obsah-kontakt.php','✉'],['Jazyky','jazyky.php','文']]],
 ['E-shop',[['Doprava','eshop-doprava.php','▣']]],
 ['Billing',[['Nastavení Stripe','admin.php','⚙'],['Platby','admin-platby.php','◈'],['Webhooky','admin-webhooky.php','↔'],['Diagnostika','admin-diagnostika.php','⊙']]],
 ['Administrace',[['Vložit administrátora','sprava-admin.php','＋'],['Zobrazit administrátory','sprava-admin-zobrazit.php','☷']]]
];
?>
<aside class="admin-sidebar" id="adminSidebar"><nav>
<?php foreach($legacyItems as $group): ?>
<div class="nav-heading"><?= htmlspecialchars($group[0],ENT_QUOTES,'UTF-8') ?></div>
<?php foreach($group[1] as $item):
 $billing= in_array($item[1], ['admin.php','admin-platby.php','admin-webhooky.php','admin-diagnostika.php'],true);
 $prefix=$billing?$billingPrefix:$adminPrefix;
 $active=$currentScript===$item[1]&&(!$billing||$isBillingPage);
 ?>
<a class="<?= $active?'active':'' ?>" href="<?= $prefix.htmlspecialchars($item[1],ENT_QUOTES,'UTF-8') ?>"><span class="nav-icon"><?= $item[2] ?></span><span><?= htmlspecialchars($item[0],ENT_QUOTES,'UTF-8') ?></span></a>
<?php endforeach; endforeach; ?>
</nav></aside>
