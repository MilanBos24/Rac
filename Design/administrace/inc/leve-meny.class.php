<?php
$currentScript = basename(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '');
?>
<aside class="admin-sidebar" id="adminSidebar">
    <nav>
        <div class="nav-heading">Přehled</div>
        <a class="<?= $currentScript === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
            <span class="nav-icon">⌂</span><span>Dashboard</span>
        </a>

        <div class="nav-heading">Obsah webu</div>
        <a class="<?= $currentScript === 'obsah-uvod.php' ? 'active' : '' ?>" href="obsah-uvod.php">
            <span class="nav-icon">⌂</span><span>Úvodní stránka</span>
        </a>
        <a class="<?= $currentScript === 'obsah-autor.php' ? 'active' : '' ?>" href="obsah-autor.php">
            <span class="nav-icon">✎</span><span>O autorovi</span>
        </a>
        <a class="<?= $currentScript === 'obsah-kontakt.php' ? 'active' : '' ?>" href="obsah-kontakt.php">
            <span class="nav-icon">✉</span><span>Kontakt</span>
        </a>
        <a class="<?= $currentScript === 'jazyky.php' ? 'active' : '' ?>" href="jazyky.php">
            <span class="nav-icon">文</span><span>Jazyky</span>
        </a>

        <div class="nav-heading">Administrace</div>
        <a class="<?= $currentScript === 'sprava-admin.php' ? 'active' : '' ?>" href="sprava-admin.php">
            <span class="nav-icon">＋</span><span>Vložit administrátora</span>
        </a>
        <a class="<?= $currentScript === 'sprava-admin-zobrazit.php' ? 'active' : '' ?>" href="sprava-admin-zobrazit.php">
            <span class="nav-icon">☷</span><span>Zobrazit administrátory</span>
        </a>
    </nav>
</aside>