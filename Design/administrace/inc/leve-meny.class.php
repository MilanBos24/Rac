<?php
$currentScript = basename(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '');
$isBillingPage = strpos((string)($_SERVER['SCRIPT_NAME'] ?? ''), '/billing/') !== false;
$adminPrefix = $isBillingPage ? '../' : '';
$billingPrefix = $isBillingPage ? '' : 'billing/';
?>
<aside class="admin-sidebar" id="adminSidebar">
    <nav>
        <div class="nav-heading">Přehled</div>
        <a class="<?= $currentScript === 'dashboard.php' ? 'active' : '' ?>" href="<?= $adminPrefix ?>dashboard.php">
            <span class="nav-icon">⌂</span><span>Dashboard</span>
        </a>

        <div class="nav-heading">Obsah webu</div>
        <a class="<?= $currentScript === 'obsah-uvod.php' ? 'active' : '' ?>" href="<?= $adminPrefix ?>obsah-uvod.php">
            <span class="nav-icon">⌂</span><span>Úvodní stránka</span>
        </a>
        <a class="<?= $currentScript === 'obsah-autor.php' ? 'active' : '' ?>" href="<?= $adminPrefix ?>obsah-autor.php">
            <span class="nav-icon">✎</span><span>O autorovi</span>
        </a>
        <a class="<?= $currentScript === 'obsah-kontakt.php' ? 'active' : '' ?>" href="<?= $adminPrefix ?>obsah-kontakt.php">
            <span class="nav-icon">✉</span><span>Kontakt</span>
        </a>
        <a class="<?= $currentScript === 'jazyky.php' ? 'active' : '' ?>" href="<?= $adminPrefix ?>jazyky.php">
            <span class="nav-icon">文</span><span>Jazyky</span>
        </a>

        <div class="nav-heading">E-shop</div>
        <a class="<?= $currentScript === 'eshop-doprava.php' ? 'active' : '' ?>" href="<?= $adminPrefix ?>eshop-doprava.php">
            <span class="nav-icon">▣</span><span>Doprava</span>
        </a>

        <div class="nav-heading">Billing</div>
        <a class="<?= $isBillingPage && $currentScript === 'admin.php' ? 'active' : '' ?>" href="<?= $billingPrefix ?>admin.php">
            <span class="nav-icon">⚙</span><span>Nastavení Stripe</span>
        </a>
        <a class="<?= $isBillingPage && $currentScript === 'admin-platby.php' ? 'active' : '' ?>" href="<?= $billingPrefix ?>admin-platby.php">
            <span class="nav-icon">◈</span><span>Platby</span>
        </a>
        <a class="<?= $isBillingPage && $currentScript === 'admin-webhooky.php' ? 'active' : '' ?>" href="<?= $billingPrefix ?>admin-webhooky.php">
            <span class="nav-icon">↔</span><span>Webhooky</span>
        </a>
        <a class="<?= $isBillingPage && $currentScript === 'admin-diagnostika.php' ? 'active' : '' ?>" href="<?= $billingPrefix ?>admin-diagnostika.php">
            <span class="nav-icon">⊙</span><span>Diagnostika</span>
        </a>

        <div class="nav-heading">Administrace</div>
        <a class="<?= $currentScript === 'sprava-admin.php' ? 'active' : '' ?>" href="<?= $adminPrefix ?>sprava-admin.php">
            <span class="nav-icon">＋</span><span>Vložit administrátora</span>
        </a>
        <a class="<?= $currentScript === 'sprava-admin-zobrazit.php' ? 'active' : '' ?>" href="<?= $adminPrefix ?>sprava-admin-zobrazit.php">
            <span class="nav-icon">☷</span><span>Zobrazit administrátory</span>
        </a>
    </nav>
</aside>