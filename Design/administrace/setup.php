<?php
declare(strict_types=1);

require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

$count = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

if ($count > 0) {
    header('Location: index.php');
    exit;
}

$errors = array();
$in = array(
    'username' => '',
    'jmeno' => '',
    'prijmeni' => '',
    'email' => '',
);

$csrf = csrf_token('csrf_setup');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $in['username'] = trim(isset($_POST['username']) ? (string)$_POST['username'] : '');
    $in['jmeno'] = trim(isset($_POST['jmeno']) ? (string)$_POST['jmeno'] : '');
    $in['prijmeni'] = trim(isset($_POST['prijmeni']) ? (string)$_POST['prijmeni'] : '');
    $in['email'] = trim(isset($_POST['email']) ? (string)$_POST['email'] : '');
    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';
    $password2 = isset($_POST['password2']) ? (string)$_POST['password2'] : '';
    $postedCsrf = isset($_POST['csrf']) ? (string)$_POST['csrf'] : '';

    if (!csrf_valid('csrf_setup', $postedCsrf)) {
        $errors[] = 'Neplatný bezpečnostní token.';
    }

    if ($in['username'] === '' || strlen($in['username']) > 100) {
        $errors[] = 'Zadejte uživatelské jméno (max. 100 znaků).';
    }

    if ($in['email'] === '' || !filter_var($in['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Zadejte platný e-mail.';
    }

    if (strlen($password) < 10) {
        $errors[] = 'Heslo musí mít alespoň 10 znaků.';
    } elseif ($password !== $password2) {
        $errors[] = 'Hesla se neshodují.';
    }

    if (!$errors) {
        $count = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

        if ($count > 0) {
            $errors[] = 'První administrátor už byl vytvořen.';
        }
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            'INSERT INTO users
             (username, jmeno, prijmeni, email, heslo_hash, role, is_active, created)
             VALUES
             (:username, :jmeno, :prijmeni, :email, :heslo_hash, "superadmin", 1, NOW())'
        );

        $stmt->execute(array(
            ':username' => $in['username'],
            ':jmeno' => $in['jmeno'] !== '' ? $in['jmeno'] : null,
            ':prijmeni' => $in['prijmeni'] !== '' ? $in['prijmeni'] : null,
            ':email' => $in['email'],
            ':heslo_hash' => $hash,
        ));

        unset($_SESSION['csrf_setup']);
        header('Location: index.php?created=1');
        exit;
    }
}
?>
<!doctype html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>První nastavení | RAC Administrace</title>
    <link rel="stylesheet" href="assets/css/admin.css?v=2026-10-07-01">
</head>
<body>
<div class="login-page">
    <div class="login-box">
        <div class="login-logo"><span>RAC</span> Administrace</div>
        <div class="login-card">
            <div class="login-title">První superadmin</div>
            <div class="login-body">
                <p>V databázi nejsou žádní administrátoři. Vytvořte první účet superadmina. Po jeho vytvoření se tato stránka automaticky zablokuje.</p>

                <?php if ($errors): ?>
                    <div class="alert alert-danger">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?= h($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" autocomplete="off">
                    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">

                    <div class="form-group">
                        <label>Uživatelské jméno</label>
                        <input class="form-control" type="text" name="username" required value="<?= h($in['username']) ?>">
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

                    <div class="form-group">
                        <label>E-mail</label>
                        <input class="form-control" type="email" name="email" required value="<?= h($in['email']) ?>">
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

                    <button class="btn btn-primary" type="submit">Vytvořit superadmina</button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
