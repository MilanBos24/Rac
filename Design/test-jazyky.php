<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/01.config.class.php';
require_once __DIR__ . '/inc/02.language.class.php';

header('Content-Type: text/html; charset=UTF-8');

echo '<h1>RAC – diagnostika jazyků</h1>';

echo '<h2>Konfigurace</h2>';
echo '<pre>';
echo 'BASE_PATH: ' . htmlspecialchars(BASE_PATH, ENT_QUOTES, 'UTF-8') . "\n";
echo 'DB config: ' . htmlspecialchars((string)(racDatabaseConfigFile() ?? 'NENALEZEN'), ENT_QUOTES, 'UTF-8') . "\n";
echo 'DEFAULT_LANGUAGE: ' . htmlspecialchars(DEFAULT_LANGUAGE, ENT_QUOTES, 'UTF-8') . "\n";
echo 'SUPPORTED_LANGUAGES: ' . htmlspecialchars(print_r(SUPPORTED_LANGUAGES, true), ENT_QUOTES, 'UTF-8') . "\n";
echo 'LANGUAGE_NAMES: ' . htmlspecialchars(print_r(LANGUAGE_NAMES, true), ENT_QUOTES, 'UTF-8') . "\n";
echo 'currentLanguage: ' . htmlspecialchars($currentLanguage, ENT_QUOTES, 'UTF-8') . "\n";
echo '</pre>';

echo '<h2>Databáze</h2>';

$pdo = racFrontendPdo();

if (!$pdo instanceof PDO) {
    echo '<p style="color:red;"><strong>Frontend se k databázi nepřipojil.</strong></p>';
} else {
    echo '<p style="color:green;"><strong>PDO připojení funguje.</strong></p>';

    try {
        $rows = $pdo->query(
            'SELECT id, code, name, native_name, locale, is_default, active, sort_order
             FROM languages
             ORDER BY sort_order ASC, id ASC'
        )->fetchAll(PDO::FETCH_ASSOC);

        echo '<pre>';
        echo htmlspecialchars(print_r($rows, true), ENT_QUOTES, 'UTF-8');
        echo '</pre>';
    } catch (Throwable $e) {
        echo '<p style="color:red;">Dotaz do tabulky languages selhal: '
            . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
            . '</p>';
    }
}
