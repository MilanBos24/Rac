<?php
declare(strict_types=1);

require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/auth_roles.php';

require_min_role('admin');

$currentUser = current_user_row();
$isSuperadmin = $currentUser && strtolower((string)$currentUser['role']) === 'superadmin';

$errors = array();
$ok = null;
$in = array(
    'email' => '',
    'username' => '',
    'jmeno' => '',
    'prijmeni' => '',
    'role' => 'admin',
);

$csrf = csrf_token('csrf_admin_add');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $in['email'] = trim(isset($_POST['email']) ? (string)$_POST['email'] : '');
    $in['username'] = trim(isset($_POST['username']) ? (string)$_POST['username'] : '');
    $in['jmeno'] = trim(isset($_POST['jmeno']) ? (string)$_POST['jmeno'] : '');
    $in['prijmeni'] = trim(isset($_POST['prijmeni']) ? (string)$_POST['prijmeni'] : '');
    $in['role'] = trim(isset($_POST['role']) ? (string)$_POST['role'] : 'admin');
    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';
    $password2 = isset($_POST['password2']) ? (string)$_POST['password2'] : '';
    $postedCsrf = isset($_POST['csrf']) ? (string)$_POST['csrf'] : '';

    if (!csrf_valid('csrf_admin_add', $postedCsrf)) {
        $errors[] = 'Neplatný bezpečnostní token.';
    }

    if ($in['email'] === '' || !filter_var($in['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Zadejte platný e-mail.';
    }

    if ($in['username'] === '' || strlen($in['username']) > 100) {
        $errors[] = 'Zadejte uživatelské jméno (max. 100 znaků).';
    }

    if (!in_array($in['role'], array('admin', 'superadmin'), true)) {
        $errors[] = 'Neplatná role.';
    }

    if ($in['role'] === 'superadmin' && !$isSuperadmin) {
        $errors[] = 'Pouze superadmin může vytvořit dalšího superadmina.';
    }

    if (strlen($password) < 10) {
        $errors[] = 'Heslo musí mít alespoň 10 znaků.';
    } elseif ($password !== $password2) {
        $errors[] = 'Hesla se neshodují.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'SELECT id FROM users WHERE email = :email OR username = :username LIMIT 1'
        );
        $stmt->execute(array(
            ':email' => $in['email'],
            ':username' => $in['username'],
        ));

        if ($stmt->fetch()) {
            $errors[] = 'Administrátor s tímto e-mailem nebo uživatelským jménem už existuje.';
        }
    }

    $photo = null;

    if (!$errors && isset($_FILES['foto']) && is_array($_FILES['foto'])) {
        $photo = upload_user_photo($_FILES['foto'], $errors);
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            'INSERT INTO users
             (username, jmeno, prijmeni, email, foto, heslo_hash, role, is_active, created)
             VALUES
             (:username, :jmeno, :prijmeni, :email, :foto, :heslo_hash, :role, 1, NOW())'
        );

        $stmt->execute(array(
            ':username' => $in['username'],
            ':jmeno' => $in['jmeno'] !== '' ? $in['jmeno'] : null,
            ':prijmeni' => $in['prijmeni'] !== '' ? $in['prijmeni'] : null,
            ':email' => $in['email'],
            ':foto' => $photo,
            ':heslo_hash' => $hash,
            ':role' => $in['role'],
        ));

        $ok = 'Administrátor byl úspěšně vytvořen.';
        $in = array(
            'email' => '',
            'username' => '',
            'jmeno' => '',
            'prijmeni' => '',
            'role' => 'admin',
        );

        unset($_SESSION['csrf_admin_add']);
        $csrf = csrf_token('csrf_admin_add');
    }
}

$pageTitle = 'Vložit administrátora';
include __DIR__ . '/inc/header.class.php';
include __DIR__ . '/inc/leve-meny.class.php';
?>
<main class="admin-content">
    <div class="page-header">
        <h1>Vložit administrátora</h1>
        <p>Role systému: Superadmin a Administrátor</p>
    </div>

    <section class="card">
        <div class="card-header">
            <h2>Nový administrátor</h2>
        </div>

        <form method="post" enctype="multipart/form-data" autocomplete="off">
            <div class="card-body">
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

                <input type="hidden" name="csrf" value="<?= h($csrf) ?>">

                <div class="grid grid-2">
                    <div class="form-group">
                        <label>E-mail</label>
                        <input class="form-control" type="email" name="email" required value="<?= h($in['email']) ?>">
                    </div>

                    <div class="form-group">
                        <label>Uživatelské jméno</label>
                        <input class="form-control" type="text" name="username" required value="<?= h($in['username']) ?>">
                    </div>
                </div>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label>Jméno</label>
                        <input class="form-control" type="text" name="jmeno" value="<?= h($in['jmeno']) ?>">
                    </div>

                    <div class="form-group">
                        <label>Příjmení</label>
                        <input class="form-control" type="text" name="prijmeni" value="<?= h($in['prijmeni']) ?>">
                    </div>
                </div>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label>Role</label>
                        <select class="form-control" name="role">
                            <option value="admin" <?= $in['role'] === 'admin' ? 'selected' : '' ?>>Administrátor</option>
                            <?php if ($isSuperadmin): ?>
                                <option value="superadmin" <?= $in['role'] === 'superadmin' ? 'selected' : '' ?>>Superadmin</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Profilová fotografie</label>
                        <input class="form-control" type="file" name="foto" accept="image/jpeg,image/png,image/gif,image/webp">
                        <div class="form-help">JPG, PNG, GIF nebo WEBP; max. 3 MB.</div>
                    </div>
                </div>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label>Heslo</label>
                        <input class="form-control" type="password" name="password" minlength="10" required>
                    </div>

                    <div class="form-group">
                        <label>Heslo znovu</label>
                        <input class="form-control" type="password" name="password2" minlength="10" required>
                    </div>
                </div>
            </div>

            <div class="card-footer" style="text-align:right;">
                <button class="btn btn-primary" type="submit">Vytvořit administrátora</button>
            </div>
        </form>
    </section>
</main>
<?php include __DIR__ . '/inc/footer.class.php'; ?>
