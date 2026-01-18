<?php
// Test script untuk debug API endpoint
$url = 'http://localhost/myapps/api_get_geojson_file.php?file=bantuan+_usahawan.geojson';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$response = curl_exec($ch);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $header_size);
$body = substr($response, $header_size);
curl_close($ch);

echo "=== HEADERS ===\n";
echo $headers . "\n\n";
echo "=== BODY (first 1000 chars) ===\n";
echo substr($body, 0, 1000) . "\n\n";
echo "=== JSON VALID? ===\n";
$json = json_decode($body, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "Valid JSON\n";
    echo "Type: " . ($json['type'] ?? 'N/A') . "\n";
    echo "Features count: " . (isset($json['features']) ? count($json['features']) : 0) . "\n";
    if (isset($json['error'])) {
        echo "Error: " . $json['error'] . "\n";
    }
} else {
    echo "Invalid JSON: " . json_last_error_msg() . "\n";
    echo "First 200 chars: " . substr($body, 0, 200) . "\n";
}
?>
