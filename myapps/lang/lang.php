<?php
/**
 * Language System for MyApps KEDA
 * Supports Bahasa Melayu (ms) and English (en)
 * 
 * @version 2.0
 */

// Get current language from session, cookie, or URL parameter
function getCurrentLanguage() {
    // Check URL parameter
    if (isset($_GET['lang']) && in_array($_GET['lang'], ['ms', 'en'])) {
        $lang = $_GET['lang'];
        $_SESSION['lang'] = $lang;
        setcookie('lang', $lang, time() + (86400 * 365), '/'); // 1 year
        return $lang;
    }
    
    // Check session
    if (isset($_SESSION['lang'])) {
        return $_SESSION['lang'];
    }
    
    // Check cookie
    if (isset($_COOKIE['lang'])) {
        return $_COOKIE['lang'];
    }
    
    // Default to Malay
    return 'ms';
}

// Load translation file
function getTranslations($lang = 'ms') {
    $file = __DIR__ . '/' . $lang . '.php';
    
    if (file_exists($file)) {
        return require $file;
    }
    
    // Fallback to Malay
    return require __DIR__ . '/ms.php';
}

// Translation helper function
function t($key, $lang = null) {
    if ($lang === null) {
        $lang = getCurrentLanguage();
    }
    
    $translations = getTranslations($lang);
    return $translations[$key] ?? $key;
}

// Format date according to language
function formatDate($date, $lang = null) {
    if ($lang === null) {
        $lang = getCurrentLanguage();
    }
    
    $timestamp = strtotime($date);
    
    if ($lang === 'ms') {
        $months = [
            1 => 'Januari', 'Februari', 'Mac', 'April', 'Mei', 'Jun',
            'Julai', 'Ogos', 'September', 'Oktober', 'November', 'Disember'
        ];
        $day = date('j', $timestamp);
        $month = $months[date('n', $timestamp)];
        $year = date('Y', $timestamp);
        return "$day $month $year";
    } else {
        return date('F j, Y', $timestamp);
    }
}
?>

