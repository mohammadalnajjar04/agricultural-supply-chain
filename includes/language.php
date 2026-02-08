<?php
/**
 * Language bootstrap (EN default)
 * - Persists choice in session
 * - Supports: en / ar only
 * - Provides helper: t('key')
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Switch language via URL: ?lang=ar or ?lang=en
if (isset($_GET['lang'])) {
    $pick = strtolower(trim((string)$_GET['lang']));
    $_SESSION['lang'] = ($pick === 'ar') ? 'ar' : 'en';
}

// Default language
$lang_code = $_SESSION['lang'] ?? 'en';
$lang_code = ($lang_code === 'ar') ? 'ar' : 'en';
$is_ar = ($lang_code === 'ar');

// Load dictionary
require __DIR__ . '/../languages/' . ($is_ar ? 'ar.php' : 'en.php');

/**
 * Translate
 */
function t(string $key, string $fallback = ''): string {
    global $lang;
    if (isset($lang[$key])) return (string)$lang[$key];
    return $fallback !== '' ? $fallback : $key;
}

/**
 * Build a URL that preserves current query params while setting lang.
 */
function url_with_lang(string $lang): string {
    $lang = ($lang === 'ar') ? 'ar' : 'en';
    $query = $_GET;
    $query['lang'] = $lang;
    $path = strtok($_SERVER['REQUEST_URI'] ?? '', '?');
    return $path . '?' . http_build_query($query);
}
