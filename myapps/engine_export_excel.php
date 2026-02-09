<?php
/**
 * Eksport data aplikasi no-code ke Excel (XLSX).
 * GET: app_slug
 * Menggunakan PhpSpreadsheet; data dari custom_app_data.payload dipetakan ke kolum mengikut metadata fields.
 */
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$app_slug = trim($_GET['app_slug'] ?? '');
if ($app_slug === '') {
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Parameter app_slug diperlukan.';
    exit;
}

// Dapatkan PK custom_apps
$custom_app_pk = 'id';
try {
    $pkStmt = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'custom_apps' AND CONSTRAINT_NAME = 'PRIMARY' LIMIT 1");
    if ($pkStmt && ($row = $pkStmt->fetch(PDO::FETCH_ASSOC))) {
        $custom_app_pk = preg_replace('/[^a-zA-Z0-9_]/', '', $row['COLUMN_NAME']) ?: 'id';
    }
} catch (PDOException $e) { /* ignore */ }

$custom_data_pk = 'id';
try {
    $pkDataStmt = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'custom_app_data' AND CONSTRAINT_NAME = 'PRIMARY' LIMIT 1");
    if ($pkDataStmt && ($row = $pkDataStmt->fetch(PDO::FETCH_ASSOC))) {
        $custom_data_pk = preg_replace('/[^a-zA-Z0-9_]/', '', $row['COLUMN_NAME']) ?: 'id';
    }
} catch (PDOException $e) { /* ignore */ }

$stmt = $pdo->prepare("SELECT * FROM custom_apps WHERE app_slug = ? LIMIT 1");
$stmt->execute([$app_slug]);
$app_row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$app_row) {
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Aplikasi tidak dijumpai.';
    exit;
}

$id_custom = (int) ($app_row[$custom_app_pk] ?? 0);
$meta_raw = $app_row['metadata'] ?? $app_row['meta'] ?? $app_row['form_config'] ?? null;
$form_fields = [];
if (!empty($meta_raw)) {
    $decoded = json_decode($meta_raw, true);
    if (is_array($decoded) && isset($decoded['fields']) && is_array($decoded['fields'])) {
        $form_fields = $decoded['fields'];
    } elseif (is_array($decoded)) {
        $form_fields = $decoded;
    }
}

$stList = $pdo->prepare("SELECT `{$custom_data_pk}` AS id, payload, created_at FROM custom_app_data WHERE id_custom = ? ORDER BY `{$custom_data_pk}` DESC");
$stList->execute([$id_custom]);
$list_data = $stList->fetchAll(PDO::FETCH_ASSOC);

if (!is_dir(__DIR__ . '/vendor') || !file_exists(__DIR__ . '/vendor/autoload.php')) {
    header('Content-Type: text/plain; charset=utf-8');
    echo 'PhpSpreadsheet tidak dipasang. Sila jalankan: composer install';
    exit;
}
require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$app_title = $app_row['name'] ?? $app_row['nama'] ?? $app_row['app_slug'] ?? $app_slug;
$sheet->setTitle(substr(preg_replace('/[^\pL\pN\s\-]/u', '', $app_title), 0, 31));

$col = 1;
$sheet->setCellValueByColumnAndRow($col++, 1, 'Bil');
foreach ($form_fields as $f) {
    $sheet->setCellValueByColumnAndRow($col++, 1, $f['label'] ?? $f['name'] ?? '');
}
$sheet->setCellValueByColumnAndRow($col, 1, 'Tarikh');
$lastCol = $col;
$rowNum = 2;
foreach ($list_data as $idx => $row) {
    $pl = [];
    if (!empty($row['payload'])) {
        $pl = json_decode($row['payload'], true);
        if (!is_array($pl)) $pl = [];
    }
    $c = 1;
    $sheet->setCellValueByColumnAndRow($c++, $rowNum, $idx + 1);
    foreach ($form_fields as $f) {
        $nm = $f['name'] ?? $f['key'] ?? '';
        $v = $pl[$nm] ?? '';
        if (is_array($v)) {
            $v = json_encode($v);
        }
        $sheet->setCellValueByColumnAndRow($c++, $rowNum, $v);
    }
    $sheet->setCellValueByColumnAndRow($c, $rowNum, $row['created_at'] ?? '');
    $rowNum++;
}

$headerStyle = [
    'font' => ['bold' => true],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
];
$sheet->getStyle('A1:' . Coordinate::stringFromColumnIndex($lastCol) . '1')->applyFromArray($headerStyle);

// Auto-size columns (format kemas)
for ($c = 1; $c <= $lastCol; $c++) {
    $colLetter = Coordinate::stringFromColumnIndex($c);
    $sheet->getColumnDimension($colLetter)->setAutoSize(true);
}

$filename = 'eksport-' . preg_replace('/[^a-z0-9_-]/i', '-', $app_slug) . '-' . date('Y-m-d-His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
