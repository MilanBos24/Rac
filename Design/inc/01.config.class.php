<?php

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

if (!defined('DEFAULT_LANGUAGE')) {
    define('DEFAULT_LANGUAGE', 'cs');
}

if (!defined('SUPPORTED_LANGUAGES')) {
    define('SUPPORTED_LANGUAGES', ['cs', 'sk', 'pl', 'en', 'de']);
}

if (!defined('LANGUAGE_NAMES')) {
    define('LANGUAGE_NAMES', [
        'cs' => 'Čeština',
        'sk' => 'Slovenčina',
        'pl' => 'Polski',
        'en' => 'English',
        'de' => 'Deutsch',
    ]);
}
