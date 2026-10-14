<?php
declare(strict_types=1);
/** RAC / BOS24 Porto – sprava-admin-zobrazit.php – 2026-10-12 */
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/auth_roles.php';
require_min_role('admin');
$currentUser = current_user_row();
$currentUserId = (int)$currentUser['id'];
$isSuperadmin = strtolower((string)$currentUser['role']) === 'superadmin';
$errors = array(); $ok = null;
$csrfUpdate = csrf_token('csrf_admin_update');
$csrfPassword = csrf_token('csrf_admin_password');
$csrfDelete = csrf_token('csrf_admin_delete');
function load_admin(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare("SELECT id, username, jmeno, prijmeni, email, foto, role, is_active, created, last_login FROM users WHERE id = :id AND role IN ('admin','superadmin') LIMIT 1");
    $stmt->execute(array(':id'=>$id));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}
function active_superadmin_count(PDO $pdo): int {
    return (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='superadmin' AND is_active=1")->fetchColumn();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formType = isset($_POST['form_type']) ? (string)$_POST['form_type'] : '';
    $targetId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $target = $targetId > 0 ? load_admin($pdo,$targetId) : null;
    if (!$target) $errors[] = 'Administrátorský účet nebyl nalezen.';
    elseif (!can_manage_user($target)) $errors[] = 'Nemáte oprávnění upravovat tento účet.';
    elseif ($formType === 'update_user') {
        if (!csrf_valid('csrf_admin_update',(string)($_POST['csrf'] ?? ''))) $errors[]='Neplatný bezpečnostní token.';
        $username=trim((string)($_POST['username']??''));
        $jmeno=trim((string)($_POST['jmeno']??''));
        $prijmeni=trim((string)($_POST['prijmeni']??''));
        $email=trim((string)($_POST['email']??''));
        $role=trim((string)($_POST['role']??$target['role']));
        $isActive=isset($_POST['is_active'])?1:0;
        if ($username==='' || mb_strlen($username)>100) $errors[]='Zadejte uživatelské jméno (max. 100 znaků).';
        if ($email==='' || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>191) $errors[]='Zadejte platný e-mail.';
        if (!in_array($role,array('admin','superadmin'),true)) $errors[]='Neplatná role.';
        if (!$isSuperadmin && $role==='superadmin') $errors[]='Administrátor nemůže přidělit roli superadmin.';
        if ($targetId === $currentUserId) { $role=(string)$target['role']; $isActive=1; }
        if ($target['role']==='superadmin' && (int)$target['is_active']===1 && ($role!=='superadmin'||$isActive!==1) && active_superadmin_count($pdo)<=1) $errors[]='Nelze deaktivovat nebo změnit roli poslednímu aktivnímu superadminovi.';
        if (!$errors) {
            $stmt=$pdo->prepare('SELECT id FROM users WHERE id != :id AND (email=:email OR username=:username) LIMIT 1');
            $stmt->execute(array(':id'=>$targetId,':email'=>$email,':username'=>$username));
            if($stmt->fetch()) $errors[]='Jiný účet už používá tento e-mail nebo uživatelské jméno.';
        }
        $newPhoto=null;
        if (!$errors && isset($_FILES['foto']) && is_array($_FILES['foto'])) $newPhoto=upload_user_photo($_FILES['foto'],$errors);
        if (!$errors) {
            try {
                $stmt=$pdo->prepare('UPDATE users SET username=:username,jmeno=:jmeno,prijmeni=:prijmeni,email=:email,role=:role,is_active=:is_active,foto=:foto WHERE id=:id LIMIT 1');
                $stmt->execute(array(':username'=>$username,':jmeno'=>$jmeno!==''?$jmeno:null,':prijmeni'=>$prijmeni!==''?$prijmeni:null,':email'=>$email,':role'=>$role,':is_active'=>$isActive,':foto'=>$newPhoto!==null?$newPhoto:$target['foto'],':id'=>$targetId));
                if($newPhoto!==null && !empty($target['foto'])) delete_user_photo((string)$target['foto']);
                $ok='Administrátor byl upraven.';
            } catch (Throwable $e) { if($newPhoto!==null) delete_user_photo($newPhoto); $errors[]='Úpravu účtu se nepodařilo uložit.'; }
        } elseif ($newPhoto!==null) delete_user_photo($newPhoto);
    } elseif ($formType === 'update_password') {
        if (!csrf_valid('csrf_admin_password',(string)($_POST['csrf']??''))) $errors[]='Neplatný bezpečnostní token.';
        $password=(string)($_POST['password']??''); $password2=(string)($_POST['password2']??'');
        if(strlen($password)<10) $errors[]='Nové heslo musí mít alespoň 10 znaků.';
        elseif($password!==$password2) $errors[]='Nová hesla se neshodují.';
        if(!$errors) {
            try { $stmt=$pdo->prepare('UPDATE users SET heslo_hash=:hash WHERE id=:id LIMIT 1'); $stmt->execute(array(':hash'=>password_hash($password,PASSWORD_DEFAULT),':id'=>$targetId)); $ok='Heslo bylo změněno.'; }
            catch(Throwable $e){$errors[]='Heslo se nepodařilo změnit.';}
        }
    } elseif ($formType === 'delete_user') {
        if(!csrf_valid('csrf_admin_delete',(string)($_POST['csrf']??''))) $errors[]='Neplatný bezpečnostní token.';
        if($targetId===$currentUserId) $errors[]='Nemůžete smazat sami sebe.';
        if($target['role']==='superadmin' && (int)$target['is_active']===1 && active_superadmin_count($pdo)<=1) $errors[]='Nelze smazat posledního aktivního superadmina.';
        if(!$errors) {
            try { $stmt=$pdo->prepare('DELETE FROM users WHERE id=:id LIMIT 1'); $stmt->execute(array(':id'=>$targetId)); delete_user_photo(isset($target['foto'])?(string)$target['foto']:null); $ok='Administrátor byl smazán.'; }
            catch(Throwable $e) { $errors[]='Administrátora se nepodařilo smazat (může mít navázané záznamy).'; }
        }
    } else $errors[]='Neznámá akce.';
}
$search=trim((string)($_GET['q']??''));
$roleFilter=trim((string)($_GET['role']??''));
$sql="SELECT id,username,jmeno,prijmeni,email,foto,role,is_active,created,last_login FROM users WHERE role IN ('admin','superadmin')";
$params=array();
if($search!=='') { $sql.=' AND (username LIKE :q1 OR jmeno LIKE :q2 OR prijmeni LIKE :q3 OR email LIKE :q4)'; $like='%'.$search.'%'; foreach(array(':q1',':q2',':q3',':q4') as $key)$params[$key]=$like; }
if(in_array($roleFilter,array('admin','superadmin'),true)) {$sql.=' AND role=:role';$params[':role']=$roleFilter;}
$sql.=' ORDER BY CASE WHEN role = "superadmin" THEN 0 ELSE 1 END, username ASC';
$stmt=$pdo->prepare($sql);$stmt->execute($params);$admins=$stmt->fetchAll(PDO::FETCH_ASSOC);
$pageTitle='Administrátoři';

$racUsePortoLayout = true;
$base = '/administrace';
$assets = $base;
$title = 'Administrátoři | RAC';
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
$currentPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '/administrace/sprava-admin-zobrazit.php';
$viewerRole = isset($viewer['role']) ? (string)$viewer['role'] : '';
$roleLabel = function_exists('role_label') ? role_label($viewerRole) : $viewerRole;

?>
<?php include __DIR__ . '/inc/header.class.php'; ?>
<?php include __DIR__ . '/inc/leve-meny.class.php'; ?>
<section role="main" class="content-body content-body-modern mt-0">
<header class="page-header"><h2>Administrátoři</h2><div class="right-wrapper text-end"><ol class="breadcrumbs"><li><a href="/administrace/dashboard.php"><i class="bx bx-home-alt"></i></a></li><li><span>Administrace</span></li><li><span>Přehled</span></li></ol></div></header>
<div class="row mt-4"><div class="col-12">
<?php if($ok): ?><div class="alert alert-success"><?= h($ok) ?></div><?php endif; ?>
<?php if($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<!-- Akční tlačítko: stejný odstup před kartou jako v akce-vlozit.php. -->
<div class="row justify-content-center justify-content-sm-between pt-2 mb-4">
    <div class="col-sm-auto text-center text-sm-start mt-2 mt-sm-0 d-flex flex-wrap justify-content-center justify-content-sm-start gap-2">
        <a href="/administrace/sprava-admin.php" class="btn btn-primary btn-md font-weight-semibold btn-py-2 px-4">+ Přidat administrátora</a>
    </div>
</div>
<section class="card mb-4"><header class="card-header"><h2 class="card-title">Správa administrátorů</h2></header></section>
<?php if(!$admins): ?><div class="alert alert-info">Nebyl nalezen žádný administrátor.</div><?php else: ?>
<div class="row g-3">
<?php foreach($admins as $admin): $mayManage=can_manage_user($admin);$isSelf=(int)$admin['id']===$currentUserId;$fullName=trim((string)$admin['jmeno'].' '.(string)$admin['prijmeni']); ?>
<div class="col-12 col-xl-6"><section class="card h-100"><header class="card-header"><h2 class="card-title"><?= h($admin['username']) ?><?= $isSelf?' (vy)':'' ?></h2></header><div class="card-body">
<div class="d-flex align-items-center gap-3 mb-3">
<?php if(!empty($admin['foto'])): ?><img src="<?= h($admin['foto']) ?>" alt="" width="56" height="56" class="rounded-circle" style="object-fit:cover"><?php else: ?><span class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center" style="width:56px;height:56px"><i class="bx bx-user" style="font-size:30px"></i></span><?php endif; ?>
<div><div class="fw-semibold"><?= h($fullName!==''?$fullName:$admin['email']) ?></div><span class="badge bg-primary"><?= h(role_label((string)$admin['role'])) ?></span> <span class="badge <?= (int)$admin['is_active']===1?'bg-success':'bg-secondary' ?>"><?= (int)$admin['is_active']===1?'Aktivní':'Neaktivní' ?></span></div>
</div><p class="mb-1"><strong>E-mail:</strong> <?= h($admin['email']) ?></p><p class="mb-1"><strong>Vytvořen:</strong> <?= h($admin['created']) ?></p><p class="mb-3"><strong>Poslední přihlášení:</strong> <?= h($admin['last_login']?:'zatím nikdy') ?></p>
<?php if($mayManage): ?>
<details class="mb-3 border rounded p-3"><summary class="fw-semibold">Upravit účet</summary>
<form method="post" enctype="multipart/form-data" class="mt-3">
<input type="hidden" name="form_type" value="update_user"><input type="hidden" name="user_id" value="<?= (int)$admin['id'] ?>"><input type="hidden" name="csrf" value="<?= h($csrfUpdate) ?>">
<div class="row g-3 mb-3">
<div class="col-12 col-md-6"><label class="form-label">Uživatelské jméno</label><input class="form-control" name="username" required maxlength="100" value="<?= h($admin['username']) ?>"></div>
<div class="col-12 col-md-6"><label class="form-label">E-mail</label><input class="form-control" type="email" name="email" required value="<?= h($admin['email']) ?>"></div>
<div class="col-12 col-md-6"><label class="form-label">Jméno</label><input class="form-control" name="jmeno" value="<?= h($admin['jmeno']) ?>"></div>
<div class="col-12 col-md-6"><label class="form-label">Příjmení</label><input class="form-control" name="prijmeni" value="<?= h($admin['prijmeni']) ?>"></div>
<div class="col-12 col-md-6"><label class="form-label">Role</label><select class="form-select" name="role" <?= $isSelf?'disabled':'' ?>><option value="admin" <?= $admin['role']==='admin'?'selected':'' ?>>Administrátor</option><?php if($isSuperadmin): ?><option value="superadmin" <?= $admin['role']==='superadmin'?'selected':'' ?>>Superadmin</option><?php endif; ?></select><?php if($isSelf): ?><input type="hidden" name="role" value="<?= h($admin['role']) ?>"><?php endif; ?></div>
<div class="col-12 col-md-6"><label class="form-label">Nová fotografie</label><input class="form-control" type="file" name="foto" accept="image/jpeg,image/png,image/gif,image/webp"></div>
<div class="col-12"><label class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" <?= (int)$admin['is_active']===1?'checked':'' ?> <?= $isSelf?'disabled':'' ?>><span class="form-check-label"> Aktivní účet</span></label><?php if($isSelf): ?><input type="hidden" name="is_active" value="1"><?php endif; ?></div>
</div><button class="btn btn-primary" type="submit">Uložit změny</button></form></details>
<details class="mb-3 border rounded p-3"><summary class="fw-semibold">Změnit heslo</summary><form method="post" class="mt-3"><input type="hidden" name="form_type" value="update_password"><input type="hidden" name="user_id" value="<?= (int)$admin['id'] ?>"><input type="hidden" name="csrf" value="<?= h($csrfPassword) ?>"><div class="row g-3 mb-3"><div class="col-12 col-md-6"><label class="form-label">Nové heslo</label><input class="form-control" type="password" name="password" minlength="10" required autocomplete="new-password"></div><div class="col-12 col-md-6"><label class="form-label">Nové heslo znovu</label><input class="form-control" type="password" name="password2" minlength="10" required autocomplete="new-password"></div></div><button class="btn btn-primary" type="submit">Změnit heslo</button></form></details>
<?php if(!$isSelf): ?><form method="post" onsubmit="return confirm('Opravdu chcete tohoto administrátora smazat?');"><input type="hidden" name="form_type" value="delete_user"><input type="hidden" name="user_id" value="<?= (int)$admin['id'] ?>"><input type="hidden" name="csrf" value="<?= h($csrfDelete) ?>"><button class="btn btn-danger" type="submit"><i class="fas fa-trash-alt me-1"></i> Smazat administrátora</button></form><?php endif; ?>
<?php endif; ?></div></section></div>
<?php endforeach; ?></div><?php endif; ?></div></div>
<?php include __DIR__ . '/inc/footer.class.php'; ?>
