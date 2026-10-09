<?php
declare(strict_types=1);

/**
 * RAC Billing - ulozeni a sifrovani nastaveni
 * verze 2026-09-16-20.33
 *
 * PHP 7.4 kompatibilni.
 */

function billing_settings_table_ready(PDO $pdo): bool
{
    try {
        $result = $pdo->query('SELECT 1 FROM billing_settings LIMIT 1');
        return $result !== false;
    } catch (Throwable $e) {
        return false;
    }
}

function billing_master_key_path(): string
{
    return __DIR__ . '/storage/.billing.key';
}

function billing_master_key_exists(): bool
{
    return is_file(billing_master_key_path());
}

function billing_master_key(bool $create = false): string
{
    $path = billing_master_key_path();
    $dir = dirname($path);

    if (!is_dir($dir)) {
        if (!$create || !@mkdir($dir, 0750, true)) {
            throw new RuntimeException('Složka billing/storage neexistuje a nepodařilo se ji vytvořit.');
        }
    }

    if (!is_file($path)) {
        if (!$create) {
            throw new RuntimeException('Chybí šifrovací klíč billing/storage/.billing.key.');
        }

        $raw = random_bytes(32);
        $payload = base64_encode($raw) . PHP_EOL;

        if (@file_put_contents($path, $payload, LOCK_EX) === false) {
            throw new RuntimeException('Nepodařilo se vytvořit billing/storage/.billing.key. Zkontrolujte práva zápisu.');
        }

        @chmod($path, 0600);
    }

    $encoded = trim((string)@file_get_contents($path));
    $key = base64_decode($encoded, true);

    if ($key === false || strlen($key) !== 32) {
        throw new RuntimeException('Soubor billing/storage/.billing.key neobsahuje platný 32B klíč.');
    }

    return $key;
}

function billing_encrypt_secret(string $plainText): string
{
    if ($plainText === '') {
        return '';
    }

    if (!function_exists('openssl_encrypt')) {
        throw new RuntimeException('PHP rozšíření OpenSSL není dostupné.');
    }

    $cipher = 'aes-256-gcm';
    $ivLength = openssl_cipher_iv_length($cipher);

    if ($ivLength <= 0) {
        throw new RuntimeException('AES-256-GCM není na serveru dostupné.');
    }

    $key = billing_master_key(true);
    $iv = random_bytes($ivLength);
    $tag = '';

    $cipherText = openssl_encrypt(
        $plainText,
        $cipher,
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag,
        '',
        16
    );

    if ($cipherText === false || strlen($tag) !== 16) {
        throw new RuntimeException('Citlivý údaj se nepodařilo zašifrovat.');
    }

    return 'v1:' . base64_encode($iv . $tag . $cipherText);
}

function billing_decrypt_secret(string $storedValue): string
{
    if ($storedValue === '') {
        return '';
    }

    if (strpos($storedValue, 'v1:') !== 0) {
        throw new RuntimeException('Citlivý údaj má neznámý formát šifrování.');
    }

    if (!function_exists('openssl_decrypt')) {
        throw new RuntimeException('PHP rozšíření OpenSSL není dostupné.');
    }

    $binary = base64_decode(substr($storedValue, 3), true);
    if ($binary === false) {
        throw new RuntimeException('Citlivý údaj nelze dekódovat.');
    }

    $cipher = 'aes-256-gcm';
    $ivLength = openssl_cipher_iv_length($cipher);

    if ($ivLength <= 0 || strlen($binary) <= ($ivLength + 16)) {
        throw new RuntimeException('Citlivý údaj je poškozený.');
    }

    $iv = substr($binary, 0, $ivLength);
    $tag = substr($binary, $ivLength, 16);
    $cipherText = substr($binary, $ivLength + 16);
    $key = billing_master_key(false);

    $plainText = openssl_decrypt(
        $cipherText,
        $cipher,
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag,
        ''
    );

    if ($plainText === false) {
        throw new RuntimeException('Citlivý údaj se nepodařilo dešifrovat. Zkontrolujte .billing.key.');
    }

    return $plainText;
}

function billing_setting_row(PDO $pdo, string $key): ?array
{
    $stmt = $pdo->prepare(
        'SELECT setting_key, setting_value, is_secret, updated_by, created_at, updated_at
         FROM billing_settings
         WHERE setting_key = :setting_key
         LIMIT 1'
    );
    $stmt->execute(array(':setting_key' => $key));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function billing_setting_get(PDO $pdo, string $key, $default = null)
{
    $row = billing_setting_row($pdo, $key);
    if (!$row) {
        return $default;
    }

    $value = (string)$row['setting_value'];

    if ((int)$row['is_secret'] === 1) {
        return billing_decrypt_secret($value);
    }

    return $value;
}

function billing_setting_set(
    PDO $pdo,
    string $key,
    string $value,
    bool $isSecret,
    ?int $updatedBy = null
): void {
    if ($isSecret) {
        $value = billing_encrypt_secret($value);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO billing_settings
            (setting_key, setting_value, is_secret, updated_by, created_at, updated_at)
         VALUES
            (:setting_key, :setting_value, :is_secret, :updated_by, NOW(), NOW())
         ON DUPLICATE KEY UPDATE
            setting_value = VALUES(setting_value),
            is_secret = VALUES(is_secret),
            updated_by = VALUES(updated_by),
            updated_at = NOW()'
    );

    $stmt->execute(array(
        ':setting_key' => $key,
        ':setting_value' => $value,
        ':is_secret' => $isSecret ? 1 : 0,
        ':updated_by' => $updatedBy,
    ));
}

function billing_setting_configured(PDO $pdo, string $key): bool
{
    $row = billing_setting_row($pdo, $key);

    return $row !== null && trim((string)$row['setting_value']) !== '';
}

function billing_mask_secret_value(string $value): string
{
    $length = strlen($value);

    if ($length === 0) {
        return 'Nenastaveno';
    }

    if ($length <= 10) {
        return '••••••••';
    }

    $prefixLength = min(8, max(3, $length - 4));
    return substr($value, 0, $prefixLength) . '••••••••' . substr($value, -4);
}

function billing_setting_masked(PDO $pdo, string $key): string
{
    if (!billing_setting_configured($pdo, $key)) {
        return 'Nenastaveno';
    }

    try {
        return billing_mask_secret_value((string)billing_setting_get($pdo, $key, ''));
    } catch (Throwable $e) {
        return 'Nastaveno – nelze dešifrovat';
    }
}