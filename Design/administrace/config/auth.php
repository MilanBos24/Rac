<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function current_user_id(): ?int
{
    $id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    return $id !== null ? (int)$id : null;
}

function current_user_row(): ?array
{
    $id = current_user_id();
    if ($id === null || $id <= 0) {
        return null;
    }

    global $pdo;

    $stmt = $pdo->prepare(
        'SELECT id, username, jmeno, prijmeni, email, foto, role, is_active, created, last_login
         FROM users
         WHERE id = :id
         LIMIT 1'
    );
    $stmt->execute(array(':id' => $id));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || (int)$row['is_active'] !== 1) {
        return null;
    }

    return $row;
}

function require_login(): void
{
    if (current_user_row() === null) {
        $_SESSION = array();
        header('Location: index.php');
        exit;
    }
}

function logout_user(): void
{
    $_SESSION = array();

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            isset($params['path']) ? $params['path'] : '/administrace/',
            isset($params['domain']) ? $params['domain'] : '',
            isset($params['secure']) ? (bool)$params['secure'] : false,
            isset($params['httponly']) ? (bool)$params['httponly'] : true
        );
    }

    session_destroy();
}

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function csrf_token(string $key): string
{
    if (empty($_SESSION[$key])) {
        $_SESSION[$key] = bin2hex(random_bytes(32));
    }

    return (string)$_SESSION[$key];
}

function csrf_valid(string $key, string $value): bool
{
    return isset($_SESSION[$key]) && is_string($_SESSION[$key]) && hash_equals($_SESSION[$key], $value);
}

function upload_user_photo(array $file, array &$errors): ?string
{
    $error = isset($file['error']) ? (int)$file['error'] : UPLOAD_ERR_NO_FILE;

    if ($error === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($error !== UPLOAD_ERR_OK) {
        $errors[] = 'Nahrávání fotografie se nepodařilo.';
        return null;
    }

    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        $errors[] = 'Nahraný soubor není platný upload.';
        return null;
    }

    $size = isset($file['size']) ? (int)$file['size'] : 0;
    if ($size <= 0 || $size > 3 * 1024 * 1024) {
        $errors[] = 'Fotografie může mít maximálně 3 MB.';
        return null;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file($file['tmp_name']);

    $allowed = array(
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    );

    if (!isset($allowed[$mime])) {
        $errors[] = 'Povolené formáty fotografie jsou JPG, PNG, GIF a WEBP.';
        return null;
    }

    $dir = dirname(__DIR__) . '/uploads/users';
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
        $errors[] = 'Nepodařilo se vytvořit složku uploads/users.';
        return null;
    }

    $filename = 'u_' . bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
    $absolute = $dir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $absolute)) {
        $errors[] = 'Fotografii se nepodařilo uložit.';
        return null;
    }

    @chmod($absolute, 0644);

    return 'uploads/users/' . $filename;
}

function delete_user_photo(?string $path): void
{
    $path = trim((string)$path);

    if ($path === '' || strpos($path, 'uploads/users/') !== 0) {
        return;
    }

    $absolute = dirname(__DIR__) . '/' . $path;

    if (is_file($absolute)) {
        @unlink($absolute);
    }
}
