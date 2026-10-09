<?php
if (!isset($pageTitle)) {
    $pageTitle = 'RAC Administrace';
}
$headerUser = function_exists('current_user_row') ? current_user_row() : null;
?>
<!doctype html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($pageTitle) ?> | RAC Administrace</title>
    <link rel="stylesheet" href="/administrace/assets/css/admin.css?v=2026-10-09-02">
</head>
<body>
<div class="admin-app">
    <header class="admin-header">
        <div class="admin-brand">
            <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Přepnout menu">☰</button>
            <a href="/administrace/dashboard.php"><strong>RAC</strong> <span>Administrace</span></a>
        </div>

        <div class="admin-user">
            <?php if ($headerUser): ?>
                <div>
                    <strong><?= h($headerUser['username']) ?></strong>
                    <small><?= h(role_label((string)$headerUser['role'])) ?></small>
                </div>
                <a class="btn btn-light btn-sm" href="/administrace/logout.php">Odhlásit</a>
            <?php endif; ?>
        </div>
    </header>
