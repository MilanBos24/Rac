<?php
declare(strict_types=1);

require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/auth_roles.php';

require_login();

$totalAdmins = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('admin','superadmin')")->fetchColumn();
$totalSuperadmins = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'superadmin'")->fetchColumn();
$totalActive = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('admin','superadmin') AND is_active = 1")->fetchColumn();

$pageTitle = 'Dashboard';
include __DIR__ . '/inc/header.class.php';
include __DIR__ . '/inc/leve-meny.class.php';
?>
<main class="admin-content">
    <div class="page-header">
        <h1>Dashboard</h1>
        <p>Základ administrace RAC</p>
    </div>

    <div class="grid grid-3">
        <div class="stat-card">
            <div class="label">Administrátoři celkem</div>
            <div class="value"><?= $totalAdmins ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Superadmini</div>
            <div class="value"><?= $totalSuperadmins ?></div>
        </div>
        <div class="stat-card">
            <div class="label">Aktivní účty</div>
            <div class="value"><?= $totalActive ?></div>
        </div>
    </div>

    <div class="card" style="margin-top:22px;">
        <div class="card-header"><h2>Administrace</h2></div>
        <div class="card-body">
            <div class="inline-actions">
                <a class="btn btn-primary" href="sprava-admin.php">Vložit administrátora</a>
                <a class="btn btn-light" href="sprava-admin-zobrazit.php">Zobrazit administrátory</a>
            </div>
        </div>
    </div>
</main>
<?php include __DIR__ . '/inc/footer.class.php'; ?>
