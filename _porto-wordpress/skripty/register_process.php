<?php
// skripty/register_process.php – zpracování registrace

require 'db-pristup.php';

$email = $_POST['email'] ?? '';
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if (!$email || !$username || !$password || $password !== $confirm) {
    die("Chyba: Nevyplnená pole nebo hesla se neshodují.");
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$is_superadmin = ($email === 'info@mil24.cz') ? 1 : 0;

$stmt = $pdo->prepare("INSERT INTO users (username, email, password, is_active, is_admin, is_superadmin) VALUES (?, ?, ?, 0, 0, ?)");
$stmt->execute([$username, $email, $hash, $is_superadmin]);

echo "Registrace úspešná. Úcet bude aktivován pozdeji.";
?>
