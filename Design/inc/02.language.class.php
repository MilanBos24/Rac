<?php

declare(strict_types=1);

if (!defined('BASE_PATH')) {
    require_once __DIR__ . '/01.config.class.php';
}

function racDetectLanguage(): string
{
    $supported = SUPPORTED_LANGUAGES;

    if (isset($_GET['lang']) && is_string($_GET['lang'])) {
        $requested = strtolower(trim($_GET['lang']));

        if (in_array($requested, $supported, true)) {
            return $requested;
        }
    }

    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);

    if (is_string($path) && $path !== '') {
        $segments = array_values(array_filter(explode('/', trim($path, '/')), 'strlen'));

        if (!empty($segments)) {
            $candidate = strtolower($segments[0]);

            if (in_array($candidate, $supported, true)) {
                return $candidate;
            }
        }
    }

    return DEFAULT_LANGUAGE;
}

$currentLanguage = racDetectLanguage();

$translationFile = BASE_PATH . '/lang/' . $currentLanguage . '.php';
$defaultTranslationFile = BASE_PATH . '/lang/' . DEFAULT_LANGUAGE . '.php';
$czechFallbackFile = BASE_PATH . '/lang/cs.php';

$translations = is_file($translationFile)
    ? (array)require $translationFile
    : [];

$defaultLanguageTranslations = is_file($defaultTranslationFile)
    ? (array)require $defaultTranslationFile
    : [];

/*
 * Čeština zůstává poslední systémová pojistka.
 * Je důležitá hlavně ve chvíli, kdy v administraci přidáme nový jazyk,
 * ale jeho lang/<code>.php zatím ještě neexistuje.
 */
$czechFallbackTranslations = is_file($czechFallbackFile)
    ? (array)require $czechFallbackFile
    : [];

$defaultTranslations = array_replace(
    $czechFallbackTranslations,
    $defaultLanguageTranslations
);

function __(string $key, array $replace = []): string
{
    global $translations, $defaultTranslations;

    $text = $translations[$key] ?? $defaultTranslations[$key] ?? $key;

    foreach ($replace as $name => $value) {
        $text = str_replace('{' . $name . '}', (string)$value, $text);
    }

    return $text;
}

function racUrl(string $path): string
{
    global $currentLanguage;

    if ($currentLanguage === DEFAULT_LANGUAGE) {
        return $path;
    }

    $separator = strpos($path, '?') === false ? '?' : '&';

    return $path . $separator . 'lang=' . rawurlencode($currentLanguage);
}

function racLanguageUrl(string $language): string
{
    if (!in_array($language, SUPPORTED_LANGUAGES, true)) {
        $language = DEFAULT_LANGUAGE;
    }

    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($requestUri, PHP_URL_PATH);
    $query = parse_url($requestUri, PHP_URL_QUERY);

    if (!is_string($path) || $path === '') {
        $path = '/';
    }

    $params = [];

    if (is_string($query) && $query !== '') {
        parse_str($query, $params);
    }

    /*
     * Výchozí jazyk má čistou URL bez ?lang=cs / ?lang=en.
     */
    if ($language === DEFAULT_LANGUAGE) {
        unset($params['lang']);
    } else {
        $params['lang'] = $language;
    }

    $queryString = http_build_query($params);

    return $path . ($queryString !== '' ? '?' . $queryString : '');
}
