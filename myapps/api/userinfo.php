<?php
// api/userinfo.php
// api/userinfo.php - SSOT Endpoint
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../db.php';
require_once '../db.php';
require_once '../utils/JWTHandler.php';

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Robust function untuk dapatkan Bearer Token dari berbagai sources
function getBearerToken() {
    // Try 1: $_SERVER['HTTP_AUTHORIZATION'] (Apache dengan SetEnvIf)
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        if (preg_match('/Bearer\s+(\S+)/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            return $matches[1];
        }
    }
    // Try 2: $_SERVER['Authorization'] (beberapa server)
    if (isset($_SERVER['Authorization'])) {
        if (preg_match('/Bearer\s+(\S+)/i', $_SERVER['Authorization'], $matches)) {
            return $matches[1];
        }
    }
    // Try 3: apache_request_headers()
    if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        if (isset($headers['Authorization'])) {
            if (preg_match('/Bearer\s+(\S+)/i', $headers['Authorization'], $matches)) {
                return $matches[1];
            }
        }
    }
    // Try 4: getallheaders() (PHP 7.3+)
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        foreach ($headers as $name => $value) {
            if (strtolower($name) === 'authorization') {
                if (preg_match('/Bearer\s+(\S+)/i', $value, $matches)) {
                    return $matches[1];
                }
            }
        }
    }
    return null;
}

$token = getBearerToken();

if (!$token) {
    http_response_code(401);
    echo json_encode(['error' => 'Tiada Token', 'message' => 'Sila sertakan Bearer Token']);
    exit;
}

try {
    // 1. "Intip" isi Token tanpa verify signature dahulu untuk dapatkan Client ID (aud)
    $parts = explode('.', $token);
    if (count($parts) != 3) {
        throw new Exception("Format token rosak.");
    }
    
    // Decode payload (bahagian tengah)
    $rawPayload = base64_decode(strtr($parts[1], '-_', '+/'));
    $payloadData = json_decode($rawPayload, true);

    if (!$payloadData || !isset($payloadData['aud'])) {
        throw new Exception("Token tiada maklumat 'aud' (Client ID).");
    }

    $client_id = $payloadData['aud'];

    // 2. Cari Secret Key dalam DB berdasarkan Client ID tersebut
    $conn = isset($pdo) ? $pdo : $db; 
    $stmt = $conn->prepare("SELECT app_secret FROM aplikasi WHERE app_key = ? LIMIT 1");
    $stmt->execute([$client_id]);
    $app = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$app) {
        throw new Exception("Aplikasi (aud) dalam token tidak wujud.");
    }

    // 3. Validasi Token Sebenar (Verify Signature guna Secret DB)
    $validPayload = JWTHandler::validate($token, $app['app_secret']);

    if (!$validPayload) {
        http_response_code(401);
        echo json_encode(['error' => 'Token Tak Sah', 'message' => 'Signature salah atau token tamat tempoh']);
        exit;
    }

    // 4. Token Sah! Ambil Data User (SSOT)
    $user_id = $validPayload['sub'];

    $sql = "SELECT 
                u.nama, u.no_kp, u.no_staf, u.emel, u.telefon, u.gambar,
                j.jawatan as nama_jawatan,
                b.bahagian as nama_bahagian,
                g.gred as kod_gred
            FROM users u
            LEFT JOIN jawatan j ON u.id_jawatan = j.id_jawatan
            LEFT JOIN bahagian b ON u.id_bahagian = b.id_bahagian
            LEFT JOIN gred g ON u.id_gred = g.id_gred
            WHERE u.id_user = ?";

    $stmtUser = $conn->prepare($sql);
    $stmtUser->execute([$user_id]);
    $userProfile = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$userProfile) {
        http_response_code(404);
        echo json_encode(['error' => 'User Not Found', 'message' => 'ID pengguna dalam token tiada dalam DB']);
        exit;
    }

    // Berjaya!
    echo json_encode($userProfile);

} catch (Exception $e) {
    http_response_code(401); // Atau 500
    echo json_encode([
        'error' => 'Ralat Proses', 
        'message' => $e->getMessage(),
        'debug_hint' => 'Semak logik JWTHandler atau Secret Key'
    ]);
}
?>