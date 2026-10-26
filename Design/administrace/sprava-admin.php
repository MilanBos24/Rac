<?php
declare(strict_types=1);
/** RAC / BOS24 Porto – sprava-admin.php – 2026-10-12 */
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/auth_roles.php';
require_min_role('admin');
$currentUser = current_user_row();
$isSuperadmin = $currentUser && strtolower((string)$currentUser['role']) === 'superadmin';
// Role RBAC z databáze; systémový superadmin je oddělen od vlastních rolí.
$racRoles = $pdo->query("SELECT role_key,role_name FROM admin_roles WHERE role_key <> 'superadmin' ORDER BY is_system DESC,role_name")->fetchAll(PDO::FETCH_ASSOC);
$racRoleKeys = array_column($racRoles, 'role_key');
$errors = array();
$ok = null;
$in = array('email'=>'', 'username'=>'', 'jmeno'=>'', 'prijmeni'=>'', 'role'=>'admin', 'custom_role'=>'admin');
$csrf = csrf_token('csrf_admin_add');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array('email','username','jmeno','prijmeni','role','custom_role') as $name) {
        $in[$name] = trim(isset($_POST[$name]) ? (string)$_POST[$name] : '');
    }
    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';
    $password2 = isset($_POST['password2']) ? (string)$_POST['password2'] : '';
    $postedCsrf = isset($_POST['csrf']) ? (string)$_POST['csrf'] : '';
    if (!csrf_valid('csrf_admin_add', $postedCsrf)) $errors[] = 'Neplatný bezpečnostní token.';
    if ($in['email'] === '' || !filter_var($in['email'], FILTER_VALIDATE_EMAIL) || strlen($in['email']) > 191) $errors[] = 'Zadejte platný e-mail (max. 191 znaků).';
    if ($in['username'] === '' || mb_strlen($in['username']) > 100) $errors[] = 'Zadejte uživatelské jméno (max. 100 znaků).';
    if (!in_array($in['role'], array('admin','superadmin'), true)) $errors[] = 'Neplatná role.';
    if ($in['role'] === 'superadmin' && !$isSuperadmin) $errors[] = 'Pouze superadmin může vytvořit dalšího superadmina.';
    if (!$isSuperadmin) { $in['role']='admin'; $in['custom_role']='admin'; }
    if ($in['role']==='admin' && !in_array($in['custom_role'],$racRoleKeys,true)) $errors[]='Neplatná přístupová role.';
    if (strlen($password) < 10) $errors[] = 'Heslo musí mít alespoň 10 znaků.';
    if ($password !== $password2) $errors[] = 'Hesla se neshodují.';
    if (!$errors) {
        try {
            $stmt=$pdo->prepare('SELECT id FROM users WHERE email=:email OR username=:username LIMIT 1');
            $stmt->execute(array(':email'=>$in['email'], ':username'=>$in['username']));
            if ($stmt->fetch()) $errors[] = 'Administrátor s tímto e-mailem nebo uživatelským jménem už existuje.';
        } catch (PDOException $ex) { $errors[] = 'Kontrola uživatelského účtu se nezdařila.'; }
    }
    $photo = null;
    if (!$errors && isset($_FILES['foto']) && is_array($_FILES['foto'])) {
        $photo = upload_user_photo($_FILES['foto'], $errors);
    }
    if (!$errors) {
        try {
            $pdo->beginTransaction();
            $stmt=$pdo->prepare('INSERT INTO users (username, jmeno, prijmeni, email, foto, heslo_hash, role, is_active, created) VALUES (:username,:jmeno,:prijmeni,:email,:foto,:heslo_hash,:role,1,NOW())');
            $stmt->execute(array(
                ':username'=>$in['username'], ':jmeno'=>$in['jmeno']!==''?$in['jmeno']:null,
                ':prijmeni'=>$in['prijmeni']!==''?$in['prijmeni']:null, ':email'=>$in['email'],
                ':foto'=>$photo, ':heslo_hash'=>password_hash($password,PASSWORD_DEFAULT), ':role'=>$in['role']
            ));
            if ($in['role']==='admin') {
                $newUserId=(int)$pdo->lastInsertId();
                $st=$pdo->prepare('INSERT INTO admin_user_roles(user_id,role_key) VALUES (?,?)');
                $st->execute(array($newUserId,$in['custom_role']));
            }
            $pdo->commit();
            $ok='Administrátor byl úspěšně vytvořen.';
            $in=array('email'=>'','username'=>'','jmeno'=>'','prijmeni'=>'','role'=>'admin','custom_role'=>'admin');
            unset($_SESSION['csrf_admin_add']);
            $csrf=csrf_token('csrf_admin_add');
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('RAC admin create RBAC: '.$ex->getMessage());
            if ($photo !== null) delete_user_photo($photo);
            $errors[]='Administrátora se nepodařilo vytvořit.';
        }
    } elseif ($photo !== null) { delete_user_photo($photo); }
}
$pageTitle='Vložit administrátora';

$racUsePortoLayout = true;
$base = '/administrace';
$assets = $base;
$title = 'Vložit administrátora | RAC';
$viewer = current_user_row();
$viewerName = isset($viewer['username']) ? (string)$viewer['username'] : 'Administrátor';
$e = static function ($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); };
$menuGroups = [
    ['Přehled', [
        ['Dashboard', '/administrace/dashboard.php', 'bx bx-home-alt'],
    ]],
    ['Obsah webu', [
        ['Úvodní stránka', '/administrace/obsah-uvod.php', 'icons icon-home'],
        ['O autorovi', '/administrace/obsah-autor.php', 'icons icon-note'],
        ['Kontakt', '/administrace/obsah-kontakt.php', 'icons icon-envelope'],
        ['Jazyky', '/administrace/jazyky.php', 'icons icon-globe'],
    ]],
    ['E-shop', [
        ['Doprava', '/administrace/eshop-doprava.php', 'icons icon-drawer'],
    ]],
    ['Billing', [
        ['Nastavení Stripe', '/administrace/billing/admin.php', 'el el-cogs'],
        ['Produkty', '/administrace/billing/admin-produkty.php', 'icons icon-basket'],
        ['Platby', '/administrace/billing/admin-platby.php', 'el el-credit-card'],
        ['Předplatné', '/administrace/billing/admin-predplatne.php', 'icons icon-refresh'],
        ['Webhooky', '/administrace/billing/admin-webhooky.php', 'icons icon-shuffle'],
        ['Diagnostika', '/administrace/billing/admin-diagnostika.php', 'icons icon-wrench'],
    ]],
    ['Administrace', [
        ['Vložit administrátora', '/administrace/sprava-admin.php', 'icons icon-user-follow'],
        ['Zobrazit administrátory', '/administrace/sprava-admin-zobrazit.php', 'icons icon-people'],
    ]],
];
$currentPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '/administrace/sprava-admin.php';
$viewerRole = isset($viewer['role']) ? (string)$viewer['role'] : '';
$roleLabel = function_exists('role_label') ? role_label($viewerRole) : $viewerRole;

?>
<?php include __DIR__ . '/inc/header.class.php'; ?>
<?php include __DIR__ . '/inc/leve-meny.class.php'; ?>
<section role="main" class="content-body content-body-modern mt-0">
<header class="page-header">
  <h2>Vložit administrátora</h2>
  <div class="right-wrapper text-end"><ol class="breadcrumbs"><li><a href="/administrace/dashboard.php"><i class="bx bx-home-alt"></i></a></li><li><span>Administrace</span></li><li><span>Nový administrátor</span></li></ol></div>
</header>
<div class="row mt-4"><div class="col-12"><section class="card">
<header class="card-header"><h2 class="card-title">Nový administrátor</h2></header>
<form method="post" action="" enctype="multipart/form-data" autocomplete="off">
<div class="card-body">
<?php if ($ok): ?><div class="alert alert-success"><?= h($ok) ?></div><?php endif; ?>
<?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= h($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<input type="hidden" name="csrf" value="<?= h($csrf) ?>">
<div class="row g-3 pb-3">
<div class="col-12 col-md-6 col-xl-4"><label class="form-label" for="rac_admin_email">Email</label><input class="form-control" id="rac_admin_email" type="email" name="email" required maxlength="191" value="<?= h($in['email']) ?>"></div>
<div class="col-12 col-md-6 col-xl-4"><label class="form-label" for="rac_admin_username">Uživatelské jméno</label><input class="form-control" id="rac_admin_username" type="text" name="username" required maxlength="100" value="<?= h($in['username']) ?>"></div>
<div class="col-12 col-md-6 col-xl-4"><label class="form-label" for="rac_admin_role">Role</label><select class="form-select" id="rac_admin_role" name="role" required><option value="admin" <?= $in['role']==='admin'?'selected':'' ?>>Administrátor</option><?php if ($isSuperadmin): ?><option value="superadmin" <?= $in['role']==='superadmin'?'selected':'' ?>>Superadmin</option><?php endif; ?></select></div>
<div class="col-12 col-md-6 col-xl-4"><label class="form-label" for="rac_admin_custom_role">Přístupová role</label><select class="form-select" id="rac_admin_custom_role" name="custom_role" <?= !$isSuperadmin?'disabled':'' ?>><?php foreach($racRoles as $rr): ?><option value="<?= h($rr['role_key']) ?>" <?= $in['custom_role']===$rr['role_key']?'selected':'' ?>><?= h($rr['role_name']) ?></option><?php endforeach; ?></select><small class="form-text text-muted">Platí pouze pro Admin. Superadmin má vždy všechna práva.</small></div>
<div class="col-12 col-md-6 col-xl-4"><label class="form-label" for="rac_admin_jmeno">Jméno</label><input class="form-control" id="rac_admin_jmeno" type="text" name="jmeno" value="<?= h($in['jmeno']) ?>"></div>
<div class="col-12 col-md-6 col-xl-4"><label class="form-label" for="rac_admin_prijmeni">Příjmení</label><input class="form-control" id="rac_admin_prijmeni" type="text" name="prijmeni" value="<?= h($in['prijmeni']) ?>"></div>
<div class="col-12 col-md-6 col-xl-4"><label class="form-label" for="rac_admin_foto">Profilová fotografie</label><input class="form-control" id="rac_admin_foto" type="file" name="foto" accept="image/jpeg,image/png,image/gif,image/webp"><small class="form-text text-muted">JPG, PNG, GIF nebo WEBP; max. 3 MB</small></div>
<div class="col-12 col-md-6 col-xl-4"><label class="form-label" for="rac_admin_pass">Heslo</label><input class="form-control" id="rac_admin_pass" type="password" name="password" minlength="10" required autocomplete="new-password"></div>
<div class="col-12 col-md-6 col-xl-4"><label class="form-label" for="rac_admin_pass2">Heslo znovu</label><input class="form-control" id="rac_admin_pass2" type="password" name="password2" minlength="10" required autocomplete="new-password"></div>
</div></div>
<footer class="card-footer text-end"><button class="btn btn-primary" type="submit"><i class="fas fa-user-plus me-1"></i> Vytvořit administrátora</button></footer>
</form></section></div></div>
<?php include __DIR__ . '/inc/footer.class.php'; ?>
