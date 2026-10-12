<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/01.config.class.php';
require_once __DIR__ . '/inc/02.language.class.php';

header('Content-Type: text/html; charset=UTF-8');

echo '<h1>RAC – diagnostika vlajek</h1>';

echo '<h2>LANGUAGE_FLAGS</h2>';
echo '<pre>' . htmlspecialchars(print_r(defined('LANGUAGE_FLAGS') ? LANGUAGE_FLAGS : 'KONSTANTA NENÍ DEFINOVÁNA', true), ENT_QUOTES, 'UTF-8') . '</pre>';

$pdo = racFrontendPdo();

echo '<h2>Databáze</h2>';

if (!$pdo instanceof PDO) {
    echo '<p style="color:red"><strong>PDO není dostupné.</strong></p>';
    exit;
}

try {
    $rows = $pdo->query(
        "SELECT code, native_name, flag_path, active
         FROM languages
         ORDER BY sort_order ASC, id ASC"
    )->fetchAll(PDO::FETCH_ASSOC);

    echo '<table border="1" cellpadding="8" cellspacing="0">';
    echo '<tr><th>Kód</th><th>Název</th><th>flag_path v DB</th><th>URL z configu</th><th>Náhled</th></tr>';

    foreach ($rows as $row) {
        $code = strtolower((string)$row['code']);
        $flag = defined('LANGUAGE_FLAGS') && isset(LANGUAGE_FLAGS[$code])
            ? (string)LANGUAGE_FLAGS[$code]
            : '';

        echo '<tr>';
        echo '<td>' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars((string)$row['native_name'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars((string)$row['flag_path'], ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>' . htmlspecialchars($flag, ENT_QUOTES, 'UTF-8') . '</td>';
        echo '<td>';
        if ($flag !== '') {
            echo '<img src="' . htmlspecialchars($flag, ENT_QUOTES, 'UTF-8') . '" style="width:90px;height:60px;object-fit:cover;border:1px solid #ccc" alt="">';
        } else {
            echo '—';
        }
        echo '</td>';
        echo '</tr>';
    }

    echo '</table>';
} catch (Throwable $e) {
    echo '<p style="color:red">' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
}
