<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    if (headers_sent($file, $line)) {
        die('Session nelze spustit, výstup už byl odeslán v souboru ' . $file . ' na řádku ' . $line . '.');
    }

    session_name('RACADMINSESSID');

    session_set_cookie_params(array(
        'lifetime' => 0,
        'path' => '/administrace/',
        'domain' => '',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ));

    if (!session_start()) {
        die('Nepodařilo se spustit session.');
    }
}
