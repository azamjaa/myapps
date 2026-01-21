<?php
/**
 * Security Helper Functions
 * @version 2.0
 */

/**
 * Validate and sanitize input
 */
function validateInput($data, $type = 'text') {
    if (is_array($data)) {
        return array_map(fn($item) => validateInput($item, $type), $data);
    }
    
    $data = trim($data);
    
    switch($type) {
        case 'email':
            return filter_var($data, FILTER_VALIDATE_EMAIL);
        case 'url':
            return filter_var($data, FILTER_VALIDATE_URL);
        case 'int':
            return filter_var($data, FILTER_VALIDATE_INT);
        case 'text':
        default:
            return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Sanitize HTML output
 */
function sanitizeOutput($data) {
    if (is_array($data)) {
        return array_map('sanitizeOutput', $data);
    }
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Check if request is AJAX
 */
function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Secure redirect
 */
function secureRedirect($location, $allowed_paths = []) {
    // Default allowed paths
    if (empty($allowed_paths)) {
        $allowed_paths = [
            'dashboard_aplikasi.php',
            'dashboard_perjawatan.php',
            'kalendar.php',
            'index.php',
            'logout.php'
        ];
    }
    
    $location = basename($location);
    
    if (in_array($location, $allowed_paths)) {
        header("Location: " . $location);
        exit();
    }
    
    // Fallback to dashboard
    header("Location: dashboard_aplikasi.php");
    exit();
}

/**
 * Get client IP address safely
 */
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    // Validate IP
    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
    }
    
    return '0.0.0.0';
}

/**
 * Generate secure random token
 */
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Log security event
 */
function logSecurityEvent($event_type, $details = '') {
    $log_file = __DIR__ . '/logs/security.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = getClientIP();
    $user_id = $_SESSION['user_id'] ?? 'unknown';
    
    $log_entry = "[$timestamp] IP: $ip | User: $user_id | Event: $event_type | Details: $details\n";
    
    // Create logs directory if it doesn't exist
    $log_dir = dirname($log_file);
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    error_log($log_entry, 3, $log_file);
}

?>
