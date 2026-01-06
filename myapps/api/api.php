<?php
/**
 * API Base Class - SSOT (Single Source of Truth)
 * All data access goes through this API layer
 * 
 * @version 2.0
 */

require_once '../db.php';
require_once '../JWT.php';

class API {
    protected $db;
    protected $requestMethod;
    protected $requestData;
    protected $headers;
    protected $userId;
    
    public function __construct() {
        global $db;
        $this->db = $db;
        $this->requestMethod = $_SERVER['REQUEST_METHOD'];
        $this->headers = getallheaders();
        
        // Set JSON header
        header('Content-Type: application/json; charset=utf-8');
        
        // CORS - Restrict to allowed origins only
        $allowed_origins = explode(',', getenv('CORS_ALLOWED_ORIGINS') ?: 'http://127.0.0.1');
        $request_origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        if (in_array($request_origin, $allowed_origins)) {
            header('Access-Control-Allow-Origin: ' . $request_origin);
            header('Access-Control-Allow-Credentials: true');
        }
        
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Max-Age: 3600');
        
        // Rate limiting check
        $this->checkRateLimit();
        
        // Handle OPTIONS request (CORS preflight)
        if ($this->requestMethod === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
        
        // Parse request data
        $this->parseRequestData();
        
        // Verify authentication
        $this->verifyAuth();
    }
    
    /**
     * Parse incoming request data
     */
    private function parseRequestData() {
        $input = file_get_contents('php://input');
        
        if ($this->requestMethod === 'POST' || $this->requestMethod === 'PUT') {
            // Try to parse JSON
            $decoded = json_decode($input, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->requestData = $decoded;
            } else {
                // Fallback to $_POST
                $this->requestData = $_POST;
            }
        } else if ($this->requestMethod === 'GET') {
            $this->requestData = $_GET;
        } else if ($this->requestMethod === 'DELETE') {
            parse_str($input, $this->requestData);
        }
    }
    
    /**
     * Verify JWT authentication
     */
    private function verifyAuth() {
        // Check for token in Authorization header
        if (isset($this->headers['Authorization'])) {
            $authHeader = $this->headers['Authorization'];
            list($type, $token) = explode(' ', $authHeader, 2);
            
            if (strcasecmp($type, 'Bearer') === 0) {
                try {
                    $decoded = JWT::decode($token, JWT_SECRET_KEY, 'HS256');
                    $this->userId = $decoded->data->user_id;
                    return true;
                } catch (Exception $e) {
                    // Token invalid, but continue (some endpoints may not require auth)
                }
            }
        }
        
        // Fallback to session
        if (isset($_SESSION['user_id'])) {
            $this->userId = $_SESSION['user_id'];
            return true;
        }
        
        return false;
    }
    
    /**
     * Require authentication
     */
    protected function requireAuth() {
        if (!$this->userId) {
            $this->sendResponse(401, false, 'Authentication required');
        }
    }
    
    /**
     * Check rate limiting
     */
    protected function checkRateLimit() {
        if (!getenv('RATE_LIMIT_ENABLED')) {
            return true;
        }
        
        $ip = $_SERVER['REMOTE_ADDR'];
        $limit_key = 'rate_limit_' . $ip;
        $max_attempts = (int)getenv('RATE_LIMIT_ATTEMPTS') ?: 5;
        $window = (int)getenv('RATE_LIMIT_WINDOW') ?: 900; // 15 minutes
        
        // Check if request exceeds limit
        if (isset($_SESSION[$limit_key])) {
            if (time() - $_SESSION[$limit_key]['timestamp'] < $window) {
                $_SESSION[$limit_key]['count']++;
                if ($_SESSION[$limit_key]['count'] > $max_attempts) {
                    $this->sendResponse(429, false, 'Too many requests. Please try again later.');
                }
            } else {
                // Reset counter after window expires
                $_SESSION[$limit_key] = ['count' => 1, 'timestamp' => time()];
            }
        } else {
            $_SESSION[$limit_key] = ['count' => 1, 'timestamp' => time()];
        }
    }
    
    /**
     * Send JSON response
     */
    protected function sendResponse($statusCode, $success, $message, $data = null) {
        http_response_code($statusCode);
        
        $response = [
            'success' => $success,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    /**
     * Validate required fields
     */
    protected function validateRequired($fields) {
        $missing = [];
        
        foreach ($fields as $field) {
            if (!isset($this->requestData[$field]) || empty($this->requestData[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            $this->sendResponse(400, false, 'Missing required fields: ' . implode(', ', $missing));
        }
    }
    
    /**
     * Sanitize input
     */
    protected function sanitize($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }
        return htmlspecialchars(strip_tags($data), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Get request data
     */
    protected function getData($key = null, $default = null) {
        if ($key === null) {
            return $this->requestData;
        }
        
        return $this->requestData[$key] ?? $default;
    }
    
    /**
     * Log API activity
     */
    protected function logActivity($action, $details = null) {
        try {
            $sql = "INSERT INTO audit (id_pengguna, tindakan, nama_jadual, data_baru, waktu) 
                    VALUES (?, ?, 'api', ?, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $this->userId,
                $action,
                json_encode($details)
                $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
                $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN'
            ]);
        } catch (Exception $e) {
            // Log error but don't fail the request
            error_log("API Log Error: " . $e->getMessage());
        }
    }
}
?>

