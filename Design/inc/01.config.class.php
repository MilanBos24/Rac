<?php

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

/*
 * Nouzový fallback.
 * Použije se pouze pokud databáze není dostupná nebo tabulka languages neexistuje.
 */
const RAC_FALLBACK_DEFAULT_LANGUAGE = 'cs';

const RAC_FALLBACK_LANGUAGE_NAMES = [
    'cs' => 'Čeština',
    'sk' => 'Slovenčina',
    'pl' => 'Polski',
    'en' => 'English',
    'de' => 'Deutsch',
];

/**
 * Najde lokální databázovou konfiguraci administrace.
 *
 * Podporuje:
 * 1) veřejný web v BASE_PATH a /administrace vedle něj
 * 2) repozitářovou strukturu /Design + /administrace
 */
function racDatabaseConfigFile(): ?string
{
    $candidates = [
        BASE_PATH . '/administrace/config/db.local.php',
        dirname(BASE_PATH) . '/administrace/config/db.local.php',
    ];

    foreach ($candidates as $candidate) {
        if (is_file($candidate) && is_readable($candidate)) {
            return $candidate;
        }
    }

    return null;
}

/**
 * Veřejná část používá stejné DB připojení jako administrace,
 * ale při chybě web nespadne - pouze použije fallback jazyky.
 */
function racFrontendPdo(): ?PDO
{
    static $resolved = false;
    static $pdo = null;

    if ($resolved) {
        return $pdo;
    }

    $resolved = true;

    $configFile = racDatabaseConfigFile();

    if ($configFile === null) {
        return null;
    }

    try {
        $db = require $configFile;

        if (!is_array($db)) {
            return null;
        }

        $host = isset($db['host']) ? (string)$db['host'] : 'localhost';
        $port = isset($db['port']) ? (int)$db['port'] : 3306;
        $name = isset($db['dbname']) ? (string)$db['dbname'] : 'rac';
        $user = isset($db['user']) ? (string)$db['user'] : '';
        $pass = isset($db['password']) ? (string)$db['password'] : '';
        $charset = isset($db['charset']) ? (string)$db['charset'] : 'utf8mb4';

        $dsn = 'mysql:host=' . $host
            . ';port=' . $port
            . ';dbname=' . $name
            . ';charset=' . $charset;

        $pdo = new PDO(
            $dsn,
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        return $pdo;
    } catch (Throwable $e) {
        $pdo = null;
        return null;
    }
}

/**
 * Načte aktivní jazyky z DB.
 *
 * Vrací:
 * - default: kód výchozího jazyka
 * - codes: aktivní jazyky v pořadí z administrace
 * - names: názvy zobrazované návštěvníkovi (native_name)
 */
function racLoadLanguageSettings(): array
{
    $fallback = [
        'default' => RAC_FALLBACK_DEFAULT_LANGUAGE,
        'codes' => array_keys(RAC_FALLBACK_LANGUAGE_NAMES),
        'names' => RAC_FALLBACK_LANGUAGE_NAMES,
        'flags' => [],
    ];

    $pdo = racFrontendPdo();

    if (!$pdo instanceof PDO) {
        return $fallback;
    }

    try {
        $rows = $pdo->query(
            'SELECT code, native_name, flag_path, is_default
             FROM languages
             WHERE active = 1
             ORDER BY sort_order ASC, id ASC'
        )->fetchAll(PDO::FETCH_ASSOC);

        if (!$rows) {
            return $fallback;
        }

        $codes = [];
        $names = [];
        $flags = [];
        $default = null;

        foreach ($rows as $row) {
            $code = strtolower(trim((string)($row['code'] ?? '')));

            if ($code === '') {
                continue;
            }

            $codes[] = $code;

            $nativeName = trim((string)($row['native_name'] ?? ''));
            $names[$code] = $nativeName !== '' ? $nativeName : strtoupper($code);

            $flagPath = trim((string)($row['flag_path'] ?? ''));

            if ($flagPath !== '') {
                if (
                    strpos($flagPath, 'http://') === 0 ||
                    strpos($flagPath, 'https://') === 0 ||
                    strpos($flagPath, '/') === 0
                ) {
                    $flags[$code] = $flagPath;
                } else {
                    $scriptName = isset($_SERVER['SCRIPT_NAME']) ? (string)$_SERVER['SCRIPT_NAME'] : '/index.php';
                    $publicBase = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

                    if ($publicBase === '.' || $publicBase === '/') {
                        $publicBase = '';
                    }

                    $flags[$code] = $publicBase . '/administrace/' . ltrim($flagPath, '/');
                }
            }

            if ((int)($row['is_default'] ?? 0) === 1) {
                $default = $code;
            }
        }

        if (!$codes) {
            return $fallback;
        }

        /*
         * Kdyby v DB omylem nebyl označen žádný výchozí jazyk,
         * použije se první aktivní jazyk.
         */
        if ($default === null || !in_array($default, $codes, true)) {
            $default = $codes[0];
        }

        return [
            'default' => $default,
            'codes' => array_values(array_unique($codes)),
            'names' => $names,
            'flags' => $flags,
        ];
    } catch (Throwable $e) {
        return $fallback;
    }
}

$racLanguageSettings = racLoadLanguageSettings();

if (!defined('DEFAULT_LANGUAGE')) {
    define('DEFAULT_LANGUAGE', $racLanguageSettings['default']);
}

if (!defined('SUPPORTED_LANGUAGES')) {
    define('SUPPORTED_LANGUAGES', $racLanguageSettings['codes']);
}

if (!defined('LANGUAGE_NAMES')) {
    define('LANGUAGE_NAMES', $racLanguageSettings['names']);
}


if (!defined('LANGUAGE_FLAGS')) {
    define('LANGUAGE_FLAGS', isset($racLanguageSettings['flags']) ? $racLanguageSettings['flags'] : []);
}