<?php
// sso/token.php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

require_once '../db.php';
require_once '../utils/JWTHandler.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// 1. Terima Data (Support JSON body & Form Data)
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$grant_type    = $input['grant_type'] ?? '';
$code          = $input['code'] ?? '';
$client_id     = $input['client_id'] ?? '';
$client_secret = $input['client_secret'] ?? '';

// 2. Validasi Input Asas
if ($grant_type !== 'authorization_code' || empty($code) || empty($client_id) || empty($client_secret)) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid_request', 'message' => 'Parameter tidak lengkap']);
    exit;
}

try {
    // 3. Validasi Client (App Key & Secret)
    // Gunakan $pdo atau $db mengikut fail db.php anda
    $conn = isset($pdo) ? $pdo : $db; 

    $stmt = $conn->prepare("SELECT * FROM aplikasi WHERE app_key = ? AND status = 1 LIMIT 1");
    $stmt->execute([$client_id]);
    $app = $stmt->fetch(PDO::FETCH_ASSOC);

    // Semak Secret (Bandingkan direct string sebab dalam DB anda secret tidak di-hash, jika hash guna password_verify)
    if (!$app || $app['app_secret'] !== $client_secret) {
        http_response_code(401);
        echo json_encode(['error' => 'invalid_client', 'message' => 'Kunci aplikasi tidak sah']);
        exit;
    }

    // 4. Validasi Auth Code
    $stmt = $conn->prepare("SELECT * FROM oauth_codes WHERE authorization_code = ? AND client_id = ? LIMIT 1");
    $stmt->execute([$code, $client_id]);
    $authCode = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$authCode) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_grant', 'message' => 'Kod tidak sah']);
        exit;
    }

    if (strtotime($authCode['expires_at']) < time()) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid_grant', 'message' => 'Kod telah tamat tempoh']);
        exit;
    }

    // 5. Dapatkan Info User & Role
    $stmtUser = $conn->prepare("SELECT id_user, nama FROM users WHERE id_user = ?");
    $stmtUser->execute([$authCode['user_id']]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    // Dapatkan Role (Contoh mudah: ambil role pertama jika ada)
    $stmtRole = $conn->prepare("SELECT r.name FROM user_roles ur JOIN roles r ON ur.id_role = r.id_role WHERE ur.id_user = ? AND ur.id_aplikasi = ? LIMIT 1");
    $stmtRole->execute([$authCode['user_id'], $app['id_aplikasi']]);
    $roleData = $stmtRole->fetch(PDO::FETCH_ASSOC);
    $userRole = $roleData['name'] ?? 'user';

    // 6. Jana JWT Token
    $payload = [
        'iss'  => 'MyApps KEDA',      // Issuer
        'sub'  => $user['id_user'],   // Subject (User ID)
        'aud'  => $client_id,         // Audience (PENTING: Siapa pemilik token ini)
        'name' => $user['nama'],      // Nama User
        'role' => $userRole,          // Role
        'iat'  => time(),             // Issued At
        'exp'  => time() + 3600       // Expire (1 jam)
    ];

    // Sign guna App Secret
    $token = JWTHandler::generate($payload, $app['app_secret']);

    // 7. Padam Auth Code (Supaya tak boleh guna semula)
    $conn->prepare("DELETE FROM oauth_codes WHERE id = ?")->execute([$authCode['id']]);

    // 8. Return Result
    echo json_encode([
        'access_token' => $token,
        'token_type'   => 'Bearer',
        'expires_in'   => 3600
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'server_error', 'message' => $e->getMessage()]);
}
?>