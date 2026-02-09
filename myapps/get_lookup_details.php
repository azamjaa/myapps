<?php
/**
 * API Auto-Fill Lookup: kembalikan payload JSON bagi satu rekod.
 * GET: id (rekod), app_id (id_custom aplikasi sumber).
 * Digunakan apabila user pilih nilai dalam dropdown lookup untuk auto-fill field lain.
 */
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) && !isset($_SESSION['id_user'])) {
    echo json_encode(['success' => false, 'payload' => null]);
    exit;
}

$record_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$app_id = isset($_GET['app_id']) ? (int) $_GET['app_id'] : 0;

if ($record_id <= 0 || $app_id <= 0) {
    echo json_encode(['success' => false, 'payload' => null]);
    exit;
}

$custom_data_pk = 'id';
try {
    $pkStmt = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'custom_app_data' AND CONSTRAINT_NAME = 'PRIMARY' LIMIT 1");
    if ($pkStmt && ($row = $pkStmt->fetch(PDO::FETCH_ASSOC))) {
        $custom_data_pk = preg_replace('/[^a-zA-Z0-9_]/', '', $row['COLUMN_NAME']) ?: 'id';
    }
} catch (PDOException $e) { /* ignore */ }

try {
    $st = $pdo->prepare("SELECT payload FROM custom_app_data WHERE `{$custom_data_pk}` = ? AND id_custom = ? LIMIT 1");
    $st->execute([$record_id, $app_id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row || $row['payload'] === null) {
        echo json_encode(['success' => false, 'payload' => null]);
        exit;
    }
    $payload = json_decode($row['payload'], true);
    if (!is_array($payload)) {
        $payload = [];
    }
    echo json_encode(['success' => true, 'payload' => $payload], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'payload' => null]);
}
