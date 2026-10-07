<?php
declare(strict_types=1);

require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/auth_roles.php';

require_min_role('admin');

$currentUser = current_user_row();
$currentUserId = (int)$currentUser['id'];
$isSuperadmin = strtolower((string)$currentUser['role']) === 'superadmin';

$errors = array();
$ok = null;

$csrfUpdate = csrf_token('csrf_admin_update');
$csrfPassword = csrf_token('csrf_admin_password');
$csrfDelete = csrf_token('csrf_admin_delete');

function load_admin(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        "SELECT id, username, jmeno, prijmeni, email, foto, role, is_active, created, last_login
         FROM users
         WHERE id = :id AND role IN ('admin','superadmin')
         LIMIT 1"
    );
    $stmt->execute(array(':id' => $id));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function active_superadmin_count(PDO $pdo): int
{
    return (int)$pdo->query(
        "SELECT COUNT(*) FROM users WHERE role = 'superadmin' AND is_active = 1"
    )->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formType = isset($_POST['form_type']) ? (string)$_POST['form_type'] : '';
    $targetId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $target = $targetId > 0 ? load_admin($pdo, $targetId) : null;

    if (!$target) {
        $errors[] = 'Administrátorský účet nebyl nalezen.';
    } elseif (!can_manage_user($target)) {
        $errors[] = 'Nemáte oprávnění upravovat tento účet.';
    } elseif ($formType === 'update_user') {
        $postedCsrf = isset($_POST['csrf']) ? (string)$_POST['csrf'] : '';

        if (!csrf_valid('csrf_admin_update', $postedCsrf)) {
            $errors[] = 'Neplatný bezpečnostní token.';
        }

        $username = trim(isset($_POST['username']) ? (string)$_POST['username'] : '');
        $jmeno = trim(isset($_POST['jmeno']) ? (string)$_POST['jmeno'] : '');
        $prijmeni = trim(isset($_POST['prijmeni']) ? (string)$_POST['prijmeni'] : '');
        $email = trim(isset($_POST['email']) ? (string)$_POST['email'] : '');
        $role = trim(isset($_POST['role']) ? (string)$_POST['role'] : (string)$target['role']);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($username === '' || strlen($username) > 100) {
            $errors[] = 'Zadejte uživatelské jméno.';
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Zadejte platný e-mail.';
        }

        if (!in_array($role, array('admin', 'superadmin'), true)) {
            $errors[] = 'Neplatná role.';
        }

        if (!$isSuperadmin && $role === 'superadmin') {
            $errors[] = 'Administrátor nemůže přidělit roli superadmin.';
        }

        if ($targetId === $currentUserId) {
            $role = (string)$target['role'];
            $isActive = 1;
        }

        if (
            strtolower((string)$target['role']) === 'superadmin' &&
            (int)$target['is_active'] === 1 &&
            ($role !== 'superadmin' || $isActive !== 1) &&
            active_superadmin_count($pdo) <= 1
        ) {
            $errors[] = 'Nelze deaktivovat nebo změnit roli poslednímu aktivnímu superadminovi.';
        }

        if (!$errors) {
            $stmt = $pdo->prepare(
                'SELECT id FROM users
                 WHERE id != :id AND (email = :email OR username = :username)
                 LIMIT 1'
            );
            $stmt->execute(array(
                ':id' => $targetId,
                ':email' => $email,
                ':username' => $username,
            ));

            if ($stmt->fetch()) {
                $errors[] = 'Jiný účet už používá tento e-mail nebo uživatelské jméno.';
            }
        }

        $newPhoto = null;
        if (!$errors && isset($_FILES['foto']) && is_array($_FILES['foto'])) {
            $newPhoto = upload_user_photo($_FILES['foto'], $errors);
        }

        if (!$errors) {
            $photo = $newPhoto !== null ? $newPhoto : $target['foto'];

            $stmt = $pdo->prepare(
                'UPDATE users
                 SET username = :username,
                     jmeno = :jmeno,
                     prijmeni = :prijmeni,
                     email = :email,
                     role = :role,
                     is_active = :is_active,
                     foto = :foto
                 WHERE id = :id
                 LIMIT 1'
            );
            $stmt->execute(array(
                ':username' => $username,
                ':jmeno' => $jmeno !== '' ? $jmeno : null,
                ':prijmeni' => $prijmeni !== '' ? $prijmeni : null,
                ':email' => $email,
                ':role' => $role,
                ':is_active' => $isActive,
                ':foto' => $photo,
                ':id' => $targetId,
            ));

            if ($newPhoto !== null && !empty($target['foto'])) {
                delete_user_photo((string)$target['foto']);
            }

            $ok = 'Administrátor byl upraven.';
        }
    } elseif ($formType === 'update_password') {
        $postedCsrf = isset($_POST['csrf']) ? (string)$_POST['csrf'] : '';

        if (!csrf_valid('csrf_admin_password', $postedCsrf)) {
            $errors[] = 'Neplatný bezpečnostní token.';
        }

        $password = isset($_POST['password']) ? (string)$_POST['password'] : '';
        $password2 = isset($_POST['password2']) ? (string)$_POST['password2'] : '';

        if (strlen($password) < 10) {
            $errors[] = 'Nové heslo musí mít alespoň 10 znaků.';
        } elseif ($password !== $password2) {
            $errors[] = 'Nová hesla se neshodují.';
        }

        if (!$errors) {
            $stmt = $pdo->prepare(
                'UPDATE users SET heslo_hash = :hash WHERE id = :id LIMIT 1'
            );
            $stmt->execute(array(
                ':hash' => password_hash($password, PASSWORD_DEFAULT),
                ':id' => $targetId,
            ));

            $ok = 'Heslo bylo změněno.';
        }
    } elseif ($formType === 'delete_user') {
        $postedCsrf = isset($_POST['csrf']) ? (string)$_POST['csrf'] : '';

        if (!csrf_valid('csrf_admin_delete', $postedCsrf)) {
            $errors[] = 'Neplatný bezpečnostní token.';
        }

        if ($targetId === $currentUserId) {
            $errors[] = 'Nemůžete smazat sami sebe.';
        }

        if (
            strtolower((string)$target['role']) === 'superadmin' &&
            (int)$target['is_active'] === 1 &&
            active_superadmin_count($pdo) <= 1
        ) {
            $errors[] = 'Nelze smazat posledního aktivního superadmina.';
        }

        if (!$errors) {
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id LIMIT 1');
            $stmt->execute(array(':id' => $targetId));

            delete_user_photo(isset($target['foto']) ? (string)$target['foto'] : null);
            $ok = 'Administrátor byl smazán.';
        }
    }
}

$search = trim(isset($_GET['q']) ? (string)$_GET['q'] : '');
$roleFilter = trim(isset($_GET['role']) ? (string)$_GET['role'] : '');

$sql = "SELECT id, username, jmeno, prijmeni, email, foto, role, is_active, created, last_login
        FROM users
        WHERE role IN ('admin','superadmin')";
$params = array();

if ($search !== '') {
    $sql .= ' AND (username LIKE :q1 OR jmeno LIKE :q2 OR prijmeni LIKE :q3 OR email LIKE :q4)';
    $like = '%' . $search . '%';
    $params[':q1'] = $like;
    $params[':q2'] = $like;
    $params[':q3'] = $like;
    $params[':q4'] = $like;
}

if (in_array($roleFilter, array('admin', 'superadmin'), true)) {
    $sql .= ' AND role = :role';
    $params[':role'] = $roleFilter;
}

$sql .= ' ORDER BY CASE WHEN role = "superadmin" THEN 0 ELSE 1 END, username ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Administrátoři';
include __DIR__ . '/inc/header.class.php';
include __DIR__ . '/inc/leve-meny.class.php';
?>
<main class="admin-content">
    <div class="page-header">
        <h1>Administrátoři</h1>
        <p>Správa účtů superadmin a admin</p>
    </div>

    <?php if ($ok): ?>
        <div class="alert alert-success"><?= h($ok) ?></div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= h($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="toolbar">
        <a class="btn btn-primary" href="sprava-admin.php">+ Přidat administrátora</a>

        <form method="get">
            <div class="form-group">
                <label>Role</label>
                <select class="form-control" name="role">
                    <option value="">Všechny</option>
                    <option value="superadmin" <?= $roleFilter === 'superadmin' ? 'selected' : '' ?>>Superadmin</option>
                    <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Administrátor</option>
                </select>
            </div>

            <div class="form-group">
                <label>Hledat</label>
                <input class="form-control" type="text" name="q" value="<?= h($search) ?>" placeholder="Jméno, e-mail...">
            </div>

            <button class="btn btn-light" type="submit">Filtrovat</button>
            <?php if ($search !== '' || $roleFilter !== ''): ?>
                <a class="btn btn-light" href="sprava-admin-zobrazit.php">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (!$admins): ?>
        <div class="alert alert-info">Nebyl nalezen žádný administrátor.</div>
    <?php else: ?>
        <div class="grid grid-2">
            <?php foreach ($admins as $admin): ?>
                <?php
                    $fullName = trim((string)$admin['jmeno'] . ' ' . (string)$admin['prijmeni']);
                    $initials = '';
                    if ($fullName !== '') {
                        $initials = strtoupper(substr((string)$admin['jmeno'], 0, 1) . substr((string)$admin['prijmeni'], 0, 1));
                    }
                    if ($initials === '') {
                        $initials = strtoupper(substr((string)$admin['username'], 0, 2));
                    }

                    $mayManage = can_manage_user($admin);
                    $isSelf = (int)$admin['id'] === $currentUserId;
                ?>
                <section class="card">
                    <div class="card-body">
                        <div class="user-card">
                            <div class="avatar">
                                <?php if (!empty($admin['foto'])): ?>
                                    <img src="<?= h($admin['foto']) ?>" alt="">
                                <?php else: ?>
                                    <?= h($initials) ?>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h3><?= h($admin['username']) ?> <?= $isSelf ? '(vy)' : '' ?></h3>
                                <p><?= h($fullName !== '' ? $fullName : $admin['email']) ?></p>
                                <span class="badge badge-primary"><?= h(role_label((string)$admin['role'])) ?></span>
                                <?php if ((int)$admin['is_active'] === 1): ?>
                                    <span class="badge badge-success">Aktivní</span>
                                <?php else: ?>
                                    <span class="badge badge-muted">Neaktivní</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="details-box">
                            <p><strong>E-mail:</strong> <?= h($admin['email']) ?></p>
                            <p><strong>Vytvořen:</strong> <?= h($admin['created']) ?></p>
                            <p><strong>Poslední přihlášení:</strong> <?= h($admin['last_login'] ?: 'zatím nikdy') ?></p>
                        </div>

                        <?php if ($mayManage): ?>
                            <details>
                                <summary>Upravit účet</summary>
                                <form method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="form_type" value="update_user">
                                    <input type="hidden" name="user_id" value="<?= (int)$admin['id'] ?>">
                                    <input type="hidden" name="csrf" value="<?= h($csrfUpdate) ?>">

                                    <div class="grid grid-2">
                                        <div class="form-group">
                                            <label>Uživatelské jméno</label>
                                            <input class="form-control" type="text" name="username" required value="<?= h($admin['username']) ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>E-mail</label>
                                            <input class="form-control" type="email" name="email" required value="<?= h($admin['email']) ?>">
                                        </div>
                                    </div>

                                    <div class="grid grid-2">
                                        <div class="form-group">
                                            <label>Jméno</label>
                                            <input class="form-control" type="text" name="jmeno" value="<?= h($admin['jmeno']) ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>Příjmení</label>
                                            <input class="form-control" type="text" name="prijmeni" value="<?= h($admin['prijmeni']) ?>">
                                        </div>
                                    </div>

                                    <div class="grid grid-2">
                                        <div class="form-group">
                                            <label>Role</label>
                                            <select class="form-control" name="role" <?= $isSelf ? 'disabled' : '' ?>>
                                                <option value="admin" <?= $admin['role'] === 'admin' ? 'selected' : '' ?>>Administrátor</option>
                                                <?php if ($isSuperadmin): ?>
                                                    <option value="superadmin" <?= $admin['role'] === 'superadmin' ? 'selected' : '' ?>>Superadmin</option>
                                                <?php endif; ?>
                                            </select>
                                            <?php if ($isSelf): ?>
                                                <input type="hidden" name="role" value="<?= h($admin['role']) ?>">
                                            <?php endif; ?>
                                        </div>

                                        <div class="form-group">
                                            <label>Nová profilová fotografie</label>
                                            <input class="form-control" type="file" name="foto" accept="image/jpeg,image/png,image/gif,image/webp">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label>
                                            <input type="checkbox" name="is_active" value="1" <?= (int)$admin['is_active'] === 1 ? 'checked' : '' ?> <?= $isSelf ? 'disabled' : '' ?>>
                                            Aktivní účet
                                        </label>
                                        <?php if ($isSelf): ?>
                                            <input type="hidden" name="is_active" value="1">
                                        <?php endif; ?>
                                    </div>

                                    <button class="btn btn-primary" type="submit">Uložit změny</button>
                                </form>
                            </details>

                            <details>
                                <summary>Změnit heslo</summary>
                                <form method="post">
                                    <input type="hidden" name="form_type" value="update_password">
                                    <input type="hidden" name="user_id" value="<?= (int)$admin['id'] ?>">
                                    <input type="hidden" name="csrf" value="<?= h($csrfPassword) ?>">

                                    <div class="grid grid-2">
                                        <div class="form-group">
                                            <label>Nové heslo</label>
                                            <input class="form-control" type="password" name="password" minlength="10" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Nové heslo znovu</label>
                                            <input class="form-control" type="password" name="password2" minlength="10" required>
                                        </div>
                                    </div>

                                    <button class="btn btn-primary" type="submit">Změnit heslo</button>
                                </form>
                            </details>

                            <?php if (!$isSelf): ?>
                                <div class="details-box">
                                    <form method="post" onsubmit="return confirm('Opravdu chcete tohoto administrátora smazat?');">
                                        <input type="hidden" name="form_type" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?= (int)$admin['id'] ?>">
                                        <input type="hidden" name="csrf" value="<?= h($csrfDelete) ?>">
                                        <button class="btn btn-danger" type="submit">Smazat administrátora</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
<?php include __DIR__ . '/inc/footer.class.php'; ?>
