<?php
declare(strict_types=1);

require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

if (current_user_row() !== null) {
    header('Location: dashboard.php');
    exit;
}

$userCount = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

if ($userCount === 0) {
    header('Location: setup.php');
    exit;
}

$error = null;
$csrf = csrf_token('csrf_login');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim(isset($_POST['login']) ? (string)$_POST['login'] : '');
    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';
    $postedCsrf = isset($_POST['csrf']) ? (string)$_POST['csrf'] : '';

    if (!csrf_valid('csrf_login', $postedCsrf)) {
        $error = 'Neplatný bezpečnostní token. Zkuste to znovu.';
    }

    if ($error === null) {
        $stmt = $pdo->prepare(
            'SELECT id, username, email, heslo_hash, role, is_active
             FROM users
             WHERE email = :email OR username = :username
             LIMIT 1'
        );
        $stmt->execute(array(
            ':email' => $login,
            ':username' => $login,
        ));

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (
            !$user ||
            (int)$user['is_active'] !== 1 ||
            !password_verify($password, (string)$user['heslo_hash'])
        ) {
            usleep(250000);
            $error = 'Nesprávné přihlašovací údaje.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];

            try {
                $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = :id')
                    ->execute(array(':id' => (int)$user['id']));
            } catch (Throwable $e) {
                // Login neblokujeme kvůli logu posledního přihlášení.
            }

            unset($_SESSION['csrf_login']);
            header('Location: dashboard.php');
            exit;
        }
    }
}

$created = isset($_GET['created']) && $_GET['created'] === '1';
?>
<!doctype html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Přihlášení | RAC Administrace</title>
    <link rel="stylesheet" href="assets/css/admin.css?v=2026-10-07-01">
</head>
<body>
<div class="login-page">
    <div class="login-box">
        <div class="login-logo"><span>RAC</span> Administrace</div>

        <div class="login-card">
            <div class="login-title">Přihlášení</div>
            <div class="login-body">
                <?php if ($created): ?>
                    <div class="alert alert-success">První superadmin byl vytvořen. Nyní se můžete přihlásit.</div>
                <?php endif; ?>

                <?php if ($error !== null): ?>
                    <div class="alert alert-danger"><?= h($error) ?></div>
                <?php endif; ?>

                <form method="post" autocomplete="off">
                    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">

                    <div class="form-group">
                        <label>Uživatelské jméno nebo e-mail</label>
                        <input
                            class="form-control"
                            type="text"
                            name="login"
                            required
                            autofocus
                            value="<?= h(isset($_POST['login']) ? $_POST['login'] : '') ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label>Heslo</label>
                        <input class="form-control" type="password" name="password" required>
                    </div>

                    <div style="text-align:right;">
                        <button class="btn btn-primary" type="submit">Přihlásit se</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="login-footer">© 2026 RAC Administrace</div>
    </div>
</div>
</body>
</html>
