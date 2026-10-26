<?php
/** RAC / Porto / Role a oprávnění / 2026-10-20-01 */
declare(strict_types=1);
require_once __DIR__.'/config/session.php';
require_once __DIR__.'/config/db.php';
require_once __DIR__.'/config/auth.php';
require_once __DIR__.'/config/auth_roles.php';
require_once __DIR__.'/config/permissions.php';
require_login();
$viewer = current_user_row();
// Fáze 1: řízení rolí pouze superadminem, nezávisle na nové tabulce.
if (!$viewer || (string)$viewer['role']!=='superadmin') { http_response_code(403); exit('Přístup pouze pro superadmina.'); }
$errors=array(); $notice='';
$csrf=csrf_token('rac_rbac_admin');
try {
    $pdo->query('SELECT 1 FROM admin_roles LIMIT 1');
    $pdo->query('SELECT 1 FROM admin_user_roles LIMIT 1');
} catch (Throwable $ex) {
    http_response_code(503);
    exit('Nejdříve spusťte migraci administrace/database/016_rac_roles.sql.');
}
if (($_SERVER['REQUEST_METHOD']??'GET')==='POST') {
    if (!csrf_valid('rac_rbac_admin',(string)($_POST['csrf']??''))) $errors[]='Neplatný bezpečnostní token.';
    $action=(string)($_POST['action']??'');
    if (!$errors) {
        try {
            if ($action==='create_role') {
                $key=strtolower(trim((string)($_POST['role_key']??'')));
                $name=trim((string)($_POST['role_name']??''));
                $description=trim((string)($_POST['description']??''));
                $clone=(string)($_POST['clone_role']??'admin');
                if (!preg_match('/^[a-z][a-z0-9_]{2,47}$/',$key) || in_array($key,array('admin','superadmin'),true)) throw new RuntimeException('Klíč role musí mít 3–48 znaků: malá písmena, čísla nebo _; nesmí být systémový.');
                if ($name==='' || mb_strlen($name)>120 || mb_strlen($description)>255) throw new RuntimeException('Zkontrolujte název a popis role.');
                $pdo->beginTransaction();
                $st=$pdo->prepare('INSERT INTO admin_roles(role_key,role_name,description,is_system) VALUES (?,?,?,0)');
                $st->execute(array($key,$name,$description?:null));
                $st=$pdo->prepare('INSERT INTO admin_role_permissions(role_key,permission_key) SELECT ?,permission_key FROM admin_role_permissions WHERE role_key=?');
                $st->execute(array($key,$clone));
                $pdo->commit(); $notice='Role byla vytvořena.';
            } elseif ($action==='save_permissions') {
                $key=(string)($_POST['role_key']??'');
                $role=$pdo->prepare('SELECT role_key FROM admin_roles WHERE role_key=?');
                $role->execute(array($key));
                if (!$role->fetchColumn() || $key==='superadmin') throw new RuntimeException('Tuto roli nelze upravit.');
                $given=$_POST['permissions']??array();
                if (!is_array($given)) throw new RuntimeException('Nesprávný formát oprávnění.');
                $valid=$pdo->query('SELECT permission_key FROM admin_permissions')->fetchAll(PDO::FETCH_COLUMN);
                $keys=array_values(array_intersect($valid,array_map('strval',$given)));
                $pdo->beginTransaction();
                $st=$pdo->prepare('DELETE FROM admin_role_permissions WHERE role_key=?');$st->execute(array($key));
                $st=$pdo->prepare('INSERT INTO admin_role_permissions(role_key,permission_key) VALUES (?,?)');
                foreach ($keys as $perm) $st->execute(array($key,$perm));
                $pdo->commit();$notice='Oprávnění role byla uložena.';
            } elseif ($action==='assign_user') {
                $uid=filter_var($_POST['user_id']??null,FILTER_VALIDATE_INT);
                $key=(string)($_POST['role_key']??'admin');
                if (!$uid) throw new RuntimeException('Neplatný uživatel.');
                $st=$pdo->prepare("SELECT id,role FROM users WHERE id=? AND is_active=1 LIMIT 1");$st->execute(array($uid));$user=$st->fetch(PDO::FETCH_ASSOC);
                if (!$user || $user['role']==='superadmin') throw new RuntimeException('Superadmina nebo neaktivní účet nelze takto upravovat.');
                $st=$pdo->prepare('SELECT role_key FROM admin_roles WHERE role_key=? AND role_key<>?');$st->execute(array($key,'superadmin'));
                if (!$st->fetchColumn()) throw new RuntimeException('Neznámá role.');
                $st=$pdo->prepare('INSERT INTO admin_user_roles(user_id,role_key) VALUES (?,?) ON DUPLICATE KEY UPDATE role_key=VALUES(role_key)');
                $st->execute(array($uid,$key));$notice='Role uživateli byla přiřazena.';
            } elseif ($action==='delete_role') {
                $key=(string)($_POST['role_key']??'');
                $st=$pdo->prepare('DELETE FROM admin_roles WHERE role_key=? AND is_system=0');
                $st->execute(array($key));
                if (!$st->rowCount()) throw new RuntimeException('Roli nelze odstranit. Může být systémová nebo přiřazená uživateli.');
                $notice='Role byla odstraněna.';
            } else throw new RuntimeException('Neznámá akce.');
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[]=$ex instanceof RuntimeException?$ex->getMessage():'Uložení se nepodařilo. Zkontrolujte duplicity a databázové vazby.';
            error_log('RAC RBAC admin: '.$ex->getMessage());
        }
    }
}
$roles=$pdo->query('SELECT role_key,role_name,description,is_system FROM admin_roles ORDER BY is_system DESC,role_name')->fetchAll(PDO::FETCH_ASSOC);
$permissions=$pdo->query('SELECT permission_key,label,section_name FROM admin_permissions ORDER BY section_name,label')->fetchAll(PDO::FETCH_ASSOC);
$assigned=$pdo->query('SELECT role_key,permission_key FROM admin_role_permissions')->fetchAll(PDO::FETCH_ASSOC);
$byRole=array();foreach($assigned as $row) $byRole[$row['role_key']][$row['permission_key']]=true;
$users=$pdo->query("SELECT u.id,u.username,u.role,ur.role_key AS custom_role FROM users u LEFT JOIN admin_user_roles ur ON ur.user_id=u.id WHERE u.is_active=1 ORDER BY u.username")->fetchAll(PDO::FETCH_ASSOC);
$selected=(string)($_GET['role']??'admin');if(!in_array($selected,array_column($roles,'role_key'),true))$selected='admin';
$racUsePortoLayout=true;$base='/administrace';$assets=$base;$title='Role a oprávnění | RAC';$pageTitle='Role a oprávnění';
$viewerName=(string)($viewer['username']??'Superadmin');$viewerRole='superadmin';$roleLabel='Superadmin';
$e=static function($v):string{return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');};
$currentPath='/administrace/nastaveni-role.php';
$menuGroups=array(
 ['Přehled',array(array('Dashboard','/administrace/dashboard.php','bx bx-home-alt'))],
 ['Administrace',array(array('Vložit administrátora','/administrace/sprava-admin.php','icons icon-user-follow'),array('Zobrazit administrátory','/administrace/sprava-admin-zobrazit.php','icons icon-people'))],
 ['Obsah webu',array(array('Úvodní stránka','/administrace/obsah-uvod.php','icons icon-home'),array('O autorovi','/administrace/obsah-autor.php','icons icon-note'),array('Kontakt','/administrace/obsah-kontakt.php','icons icon-envelope'))],
 ['E-shop',array(array('Produkty','/administrace/eshop-produkty.php','icons icon-basket'),array('Sklad knih','/administrace/eshop-sklad.php','icons icon-layers'),array('Objednávky','/administrace/eshop-objednavky.php','icons icon-list'),array('Platby e-shopu','/administrace/eshop-platby.php','icons icon-credit-card'),array('Digitální přístupy','/administrace/eshop-digitalni-pristupy.php','icons icon-cloud-download'),array('Doprava','/administrace/eshop-doprava.php','icons icon-drawer'))],
 ['Platební brána',array(array('Nastavení Stripe','/administrace/billing/admin.php','el el-cogs'),array('Platby','/administrace/billing/admin-platby.php','el el-credit-card'),array('Webhooky','/administrace/billing/admin-webhooky.php','icons icon-shuffle'),array('Diagnostika','/administrace/billing/admin-diagnostika.php','icons icon-wrench'))],
 ['Nastavení systému',array(array('Role a oprávnění','/administrace/nastaveni-role.php','icons icon-lock'))]
);
include __DIR__.'/inc/header.class.php'; include __DIR__.'/inc/leve-meny.class.php';
?>
<section role="main" class="content-body content-body-modern mt-0">
<header class="page-header"><h2>Role a oprávnění</h2></header>
<div class="row mt-4"><div class="col-12">
<?php foreach($errors as $err): ?><div class="alert alert-danger"><?= $e($err) ?></div><?php endforeach; ?>
<?php if($notice): ?><div class="alert alert-success"><?= $e($notice) ?></div><?php endif; ?>
<div class="alert alert-info">Fáze 1: nastavení práv se ukládá, ale ochranu jednotlivých stránek zapojíme až v další fázi. Superadmin má vždy plný přístup.</div>
<div class="row g-3">
 <div class="col-lg-4"><section class="card"><header class="card-header"><h2 class="card-title">Role</h2></header><div class="card-body">
 <?php foreach($roles as $role): ?><p class="mb-2"><a href="?role=<?= urlencode($role['role_key']) ?>"><?= $e($role['role_name']) ?></a> <small class="text-muted">(<?= $e($role['role_key']) ?>)</small></p><?php endforeach; ?>
 <hr><h4>Vytvořit roli</h4><form method="post"><input type="hidden" name="csrf" value="<?= $e($csrf) ?>"><input type="hidden" name="action" value="create_role">
 <label class="form-label">Klíč</label><input class="form-control mb-2" name="role_key" placeholder="napr. skladnik" required pattern="[a-z][a-z0-9_]{2,47}">
 <label class="form-label">Název role</label><input class="form-control mb-2" name="role_name" required maxlength="120">
 <label class="form-label">Popis</label><input class="form-control mb-2" name="description" maxlength="255">
 <label class="form-label">Kopírovat práva z</label><select class="form-select mb-3" name="clone_role"><?php foreach($roles as $role): if($role['role_key']==='superadmin')continue; ?><option value="<?= $e($role['role_key']) ?>"><?= $e($role['role_name']) ?></option><?php endforeach; ?></select>
 <button class="btn btn-primary" type="submit">+ Vytvořit roli</button></form></div></section></div>
 <div class="col-lg-8"><section class="card"><header class="card-header"><h2 class="card-title">Oprávnění: <?= $e($selected) ?></h2></header><div class="card-body">
 <?php if($selected==='superadmin'): ?><div class="alert alert-info">Systémová role s neomezenými právy; nelze upravit.</div><?php else: ?>
 <form method="post"><input type="hidden" name="csrf" value="<?= $e($csrf) ?>"><input type="hidden" name="action" value="save_permissions"><input type="hidden" name="role_key" value="<?= $e($selected) ?>">
 <?php $last='';foreach($permissions as $perm): if($last!==$perm['section_name']):$last=$perm['section_name'];?><h5 class="mt-3 mb-2"><?= $e($last) ?></h5><?php endif; ?>
 <label class="d-block mb-2"><input type="checkbox" name="permissions[]" value="<?= $e($perm['permission_key']) ?>" <?= !empty($byRole[$selected][$perm['permission_key']])?'checked':'' ?>> <?= $e($perm['label']) ?></label>
 <?php endforeach; ?><button class="btn btn-primary mt-3" type="submit">Uložit oprávnění</button></form>
 <?php $r=null;foreach($roles as $rr)if($rr['role_key']===$selected)$r=$rr; ?><?php if($r && !(int)$r['is_system']): ?><form method="post" class="mt-4" onsubmit="return confirm('Opravdu odstranit tuto roli?');"><input type="hidden" name="csrf" value="<?= $e($csrf) ?>"><input type="hidden" name="action" value="delete_role"><input type="hidden" name="role_key" value="<?= $e($selected) ?>"><button class="btn btn-danger" type="submit">Smazat roli</button></form><?php endif; ?>
 <?php endif; ?></div></section></div>
</div>
<section class="card mt-4"><header class="card-header"><h2 class="card-title">Přiřazení rolí administrátorům</h2></header><div class="card-body table-responsive"><table class="table table-hover"><thead><tr><th>Administrátor</th><th>Současná role</th><th>Přiřadit roli</th></tr></thead><tbody>
<?php foreach($users as $user): ?><tr><td><?= $e($user['username']) ?></td><td><?= $e($user['role']==='superadmin'?'Superadmin':($user['custom_role']?:'admin')) ?></td><td><?php if($user['role']==='superadmin'): ?>Systémová role<?php else: ?><form method="post" class="d-flex gap-2"><input type="hidden" name="csrf" value="<?= $e($csrf) ?>"><input type="hidden" name="action" value="assign_user"><input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>"><select class="form-select" name="role_key"><?php foreach($roles as $role):if($role['role_key']==='superadmin')continue; ?><option value="<?= $e($role['role_key']) ?>" <?= ($user['custom_role']?:'admin')===$role['role_key']?'selected':'' ?>><?= $e($role['role_name']) ?></option><?php endforeach; ?></select><button class="btn btn-primary" type="submit">Uložit</button></form><?php endif; ?></td></tr><?php endforeach; ?>
</tbody></table></div></section>
</div></div></section>
<?php include __DIR__.'/inc/footer.class.php'; ?>
