<?php
declare(strict_types=1);

require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/auth_roles.php';

require_login();

$pageTitle = 'Přístup odepřen';
include __DIR__ . '/inc/header.class.php';
include __DIR__ . '/inc/leve-meny.class.php';
?>
<main class="admin-content">
    <div class="page-header">
        <h1>Přístup odepřen</h1>
    </div>

    <div class="alert alert-danger">K této části administrace nemáte potřebné oprávnění.</div>
    <a class="btn btn-light" href="dashboard.php">Zpět na dashboard</a>
</main>
<?php include __DIR__ . '/inc/footer.class.php'; ?>
