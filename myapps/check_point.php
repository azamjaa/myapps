<?php
require 'utils/reverse_geocode.php';
require 'db.php';

$lng = 100.47735015511;
$lat = 5.9930265614255;

$bounds = [
    'minLat' => 5.0,
    'maxLat' => 6.5,
    'minLng' => 99.5,
    'maxLng' => 101.0
];

echo "Koordinat: $lng, $lat\n";
echo "Dalam bounds Kedah: " . (isWithinKedahBounds($lng, $lat, $bounds) ? 'Ya' : 'Tidak') . "\n";

$info = reverseGeocode($lng, $lat, $db);
echo "DAERAH: " . ($info['DAERAH'] ?? 'Tiada') . "\n";
echo "PARLIMEN: " . ($info['PARLIMEN'] ?? 'Tiada') . "\n";
echo "DUN: " . ($info['DUN'] ?? 'Tiada') . "\n";
