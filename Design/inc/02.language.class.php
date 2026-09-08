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

$translations = is_file($translationFile) ? require $translationFile : [];
$defaultTranslations = is_file($defaultTranslationFile) ? require $defaultTranslationFile : [];

function __(string $key, array $replace = []): string
{
    global $translations, $defaultTranslations;

    $text = $translations[$key] ?? $defaultTranslations[$key] ?? $key;

    foreach ($replace as $name => $value) {
        $text = str_replace('{' . $name . '}', (string) $value, $text);
    }

    return $text;
}
