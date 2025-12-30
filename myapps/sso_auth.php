<?php
/**
 * Self SSO Authentication System
 * JWT-based authentication for MyApps KEDA
 * 
 * @author MyApps KEDA Enterprise
 * @version 2.0
 */

require_once 'db.php';
require_once 'JWT.php';

class SSOAuth {
    private $db;
    private $secret_key;
    private $issuer;
    private $audience;
    
    public function __construct($db) {
        $this->db = $db;
        $this->secret_key = JWT_SECRET_KEY;
        $this->issuer = APP_URL;
        $this->audience = APP_URL;
    }
    
    /**
     * Login user and generate JWT token
     */
    public function login($username, $password) {
        try {
            // Get user from database
            $sql = "SELECT * FROM users WHERE username = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }
            
            // Verify password
            if (!password_verify($password, $user['password'])) {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }
            
            // Check if account is active
            if (isset($user['status']) && $user['status'] != 'active') {
                return ['success' => false, 'message' => 'Account is not active'];
            }
            
            // Generate JWT token
            $token = $this->generateToken($user);
            
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'] ?? 'user';
            $_SESSION['sso_token'] = $token;
            
            // Update last login
            $updateSql = "UPDATE users SET last_login = NOW() WHERE id = ?";
            $updateStmt = $this->db->prepare($updateSql);
            $updateStmt->execute([$user['id']]);
            
            return [
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role'] ?? 'user'
                ]
            ];
            
        } catch (Exception $e) {
            error_log("SSO Login Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed'];
        }
    }
    
    /**
     * Generate JWT token
     */
    private function generateToken($user) {
        $issuedAt = time();
        $expirationTime = $issuedAt + 3600 * 8; // Token valid for 8 hours
        
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'data' => [
                'user_id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role'] ?? 'user',
                'email' => $user['email'] ?? ''
            ]
        ];
        
        return JWT::encode($payload, $this->secret_key, 'HS256');
    }
    
    /**
     * Verify JWT token
     */
    public function verifyToken($token) {
        try {
            $decoded = JWT::decode($token, $this->secret_key, 'HS256');
            return [
                'success' => true,
                'data' => $decoded->data
            ];
        } catch (Exception $e) {
            error_log("Token Verification Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Invalid or expired token'];
        }
    }
    
    /**
     * Refresh token
     */
    public function refreshToken($oldToken) {
        $verification = $this->verifyToken($oldToken);
        
        if (!$verification['success']) {
            return $verification;
        }
        
        // Get user data
        $userData = $verification['data'];
        
        // Generate new token
        $newToken = $this->generateToken([
            'id' => $userData->user_id,
            'username' => $userData->username,
            'role' => $userData->role,
            'email' => $userData->email
        ]);
        
        $_SESSION['sso_token'] = $newToken;
        
        return [
            'success' => true,
            'token' => $newToken
        ];
    }
    
    /**
     * Logout and invalidate token
     */
    public function logout() {
        // Clear session
        session_unset();
        session_destroy();
        
        // Start new session
        session_start();
        session_regenerate_id(true);
        
        return ['success' => true, 'message' => 'Logged out successfully'];
    }
    
    /**
     * Check if user is authenticated
     */
    public function isAuthenticated() {
        if (!isset($_SESSION['sso_token'])) {
            return false;
        }
        
        $verification = $this->verifyToken($_SESSION['sso_token']);
        return $verification['success'];
    }
    
    /**
     * Get current user from token
     */
    public function getCurrentUser() {
        if (!isset($_SESSION['sso_token'])) {
            return null;
        }
        
        $verification = $this->verifyToken($_SESSION['sso_token']);
        
        if (!$verification['success']) {
            return null;
        }
        
        return $verification['data'];
    }
}
?>

