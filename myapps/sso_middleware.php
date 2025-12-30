<?php
/**
 * SSO Middleware - Protect pages with JWT authentication
 * Include this file at the top of protected pages
 */

require_once 'db.php';
require_once 'JWT.php';
require_once 'sso_auth.php';

$ssoAuth = new SSOAuth($db);

// Check if user is authenticated
if (!$ssoAuth->isAuthenticated()) {
    // Token invalid or expired, redirect to login
    header("Location: index.php?expired=1");
    exit();
}

// Optional: Refresh token if it's about to expire (last 1 hour)
if (isset($_SESSION['sso_token'])) {
    try {
        $decoded = JWT::decode($_SESSION['sso_token'], JWT_SECRET_KEY, 'HS256');
        
        // If token expires in less than 1 hour, refresh it
        if (($decoded->exp - time()) < 3600) {
            $refresh = $ssoAuth->refreshToken($_SESSION['sso_token']);
            if ($refresh['success']) {
                $_SESSION['sso_token'] = $refresh['token'];
            }
        }
    } catch (Exception $e) {
        error_log("Token decode error in middleware: " . $e->getMessage());
    }
}

// User is authenticated, continue
?>

