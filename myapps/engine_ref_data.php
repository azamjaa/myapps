<?php
/**
 * API untuk Ref (Relational) dropdown – data untuk Select2 AJAX.
 * Ditarik secara dinamik dari aplikasi sumber; menyokong carian dan pagination.
 */
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

// Akses: sama seperti engine (boleh dibuka jika pengguna ada akses engine)
if (!isset($_SESSION['user_id']) && !isset($_SESSION['id_user'])) {
    echo json_encode(['results' => [], 'pagination' => ['more' => false]]);
    exit;
}

$app_id = (int) ($_GET['app_id'] ?? 0);
$display_field = trim($_GET['display_field'] ?? '');
$display_field = preg_replace('/[^a-zA-Z0-9_]/', '', $display_field) ?: 'id';
$q = trim($_GET['q'] ?? '');
$single_id = isset($_GET['id']) && $_GET['id'] !== '' ? (int) $_GET['id'] : 0;
$page = max(1, (int) ($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

if ($app_id <= 0) {
    echo json_encode(['results' => [], 'pagination' => ['more' => false]]);
    exit;
}

$custom_data_pk = 'id';
try {
    $pkStmt = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'custom_app_data' AND CONSTRAINT_NAME = 'PRIMARY' LIMIT 1");
    if ($pkStmt && ($row = $pkStmt->fetch(PDO::FETCH_ASSOC))) {
        $custom_data_pk = preg_replace('/[^a-zA-Z0-9_]/', '', $row['COLUMN_NAME']) ?: 'id';
    }
} catch (PDOException $e) { /* ignore */ }

$results = [];
try {
    $path = '$.' . $display_field;
    $params = [$app_id];
    $sql = "SELECT `{$custom_data_pk}` AS id, payload FROM custom_app_data WHERE id_custom = ?";
    if ($single_id > 0) {
        $sql .= " AND `{$custom_data_pk}` = ?";
        $params[] = $single_id;
    } elseif ($q !== '') {
        $sql .= " AND JSON_UNQUOTE(JSON_EXTRACT(payload, ?)) LIKE ?";
        $params[] = $path;
        $params[] = '%' . $q . '%';
    }
    $sql .= " ORDER BY `{$custom_data_pk}` DESC LIMIT " . ($single_id > 0 ? 1 : ($per_page + 1)) . " OFFSET " . ($single_id > 0 ? 0 : $offset);
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    $has_more = $single_id > 0 ? false : (count($rows) > $per_page);
    if ($has_more) {
        array_pop($rows);
    }
    foreach ($rows as $r) {
        $pl = [];
        if (!empty($r['payload'])) {
            $pl = json_decode($r['payload'], true);
            if (!is_array($pl)) $pl = [];
        }
        $text = isset($pl[$display_field]) ? $pl[$display_field] : ('Rekod #' . $r['id']);
        if (is_array($text)) $text = implode(', ', $text);
        $results[] = ['id' => (string) $r['id'], 'text' => (string) $text];
    }
} catch (PDOException $e) {
    $results = [];
    $has_more = false;
}

echo json_encode([
    'results' => $results,
    'pagination' => ['more' => $has_more]
], JSON_UNESCAPED_UNICODE);
