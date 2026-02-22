<?php
/**
 * MyApps KEDA - Configuration File
 * Load environment variables from .env file
 */

// Function to load .env file
function loadEnv($path = '.env') {
    if (!file_exists($path)) {
        return false;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            $value = trim($value, '"\'');
            
            // Set environment variable
            if (!getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
    
    return true;
}

// Load .env file (optional - fallback to defaults if not exists)
$env_loaded = loadEnv(__DIR__ . '/.env');

// If .env not loaded, throw error in production
if (!$env_loaded) {
    if (env('APP_ENV', 'production') === 'production') {
        throw new Exception('.env file is required in production environment');
    }
    // Development fallback only
    putenv("APP_NAME=MyApps KEDA");
    putenv("APP_ENV=development");
    putenv("APP_DEBUG=true");
}

// Helper function to get environment variable with fallback
function env($key, $default = null) {
    $value = getenv($key);
    
    if ($value === false) {
        return $default;
    }
    
    // Convert string booleans
    switch (strtolower($value)) {
        case 'true':
        case '(true)':
            return true;
        case 'false':
        case '(false)':
            return false;
        case 'null':
        case '(null)':
            return null;
    }
    
    return $value;
}

// Configuration array
return [
    'app' => [
        'name' => env('APP_NAME', 'MyApps KEDA'),
        'env' => env('APP_ENV', 'production'),
        'debug' => env('APP_DEBUG', false),
        'url' => env('APP_URL', 'http://localhost'),
        'timezone' => env('APP_TIMEZONE', 'Asia/Kuala_Lumpur'),
    ],
    
    'database' => [
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'myapps'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
    ],
    
    'mail' => [
        'host' => env('MAIL_HOST', 'smtp.gmail.com'),
        'port' => env('MAIL_PORT', 587),
        'username' => env('MAIL_USERNAME'),
        'password' => env('MAIL_PASSWORD'),
        'encryption' => env('MAIL_ENCRYPTION', 'tls'),
        'from' => [
            'address' => env('MAIL_FROM_ADDRESS', 'noreply@keda.gov.my'),
            'name' => env('MAIL_FROM_NAME', 'MyApps KEDA'),
        ],
    ],
    
    'session' => [
        'lifetime' => env('SESSION_LIFETIME', 120),
        'secure_cookie' => env('SESSION_SECURE_COOKIE', false),
        'http_only' => env('SESSION_HTTP_ONLY', true),
    ],
    
    'security' => [
        'csrf_token_expiry' => env('CSRF_TOKEN_EXPIRY', 7200),
    ],
    
    'chatbot' => [
        'ollama_url' => env('OLLAMA_URL', 'http://localhost:11434/api/generate'),
        'ollama_model' => env('OLLAMA_MODEL', 'qwen2.5'),
        'rate_limit' => env('CHATBOT_RATE_LIMIT', 15),
        'rate_window' => env('CHATBOT_RATE_WINDOW', 60),
    ],
    
    'upload' => [
        'max_size' => env('UPLOAD_MAX_SIZE', 2097152), // 2MB in bytes
        'allowed_types' => explode(',', env('UPLOAD_ALLOWED_TYPES', 'jpg,jpeg,png')),
        'path' => env('UPLOAD_PATH', 'uploads/'),
    ],
];

