<?php

use App\Models\Staf;
use Illuminate\Support\Facades\Hash;

// Test 1: Find staf by no_kp
echo "=== Test 1: Find Staf ===\n";
$staf = Staf::where('no_kp', '900101011234')->first();
if ($staf) {
    echo "✓ Staf found: {$staf->nama}\n";
    echo "  ID: {$staf->id_staf}\n";
    echo "  No KP: {$staf->no_kp}\n";
} else {
    echo "✗ Staf NOT found!\n";
    exit(1);
}

// Test 2: Check login record relationship
echo "\n=== Test 2: Check Login Record ===\n";
$loginRecord = $staf->loginRecord;
if ($loginRecord) {
    echo "✓ Login record found\n";
    echo "  ID Login: {$loginRecord->id_login}\n";
    echo "  Password Hash: " . substr($loginRecord->password_hash, 0, 20) . "...\n";
} else {
    echo "✗ Login record NOT found!\n";
    echo "  Attempting to load...\n";
    $staf->load('loginRecord');
    $loginRecord = $staf->loginRecord;
    if ($loginRecord) {
        echo "✓ Login record loaded on second attempt\n";
    } else {
        echo "✗ Still no login record. Check database!\n";
        exit(1);
    }
}

// Test 3: Verify password
echo "\n=== Test 3: Verify Password ===\n";
$password = 'password';
$hash = $loginRecord->password_hash;
$match = Hash::check($password, $hash);
if ($match) {
    echo "✓ Password 'password' matches hash!\n";
} else {
    echo "✗ Password does NOT match!\n";
    echo "  Testing alternate password 'Password'...\n";
    if (Hash::check('Password', $hash)) {
        echo "  ✓ 'Password' matches!\n";
    } else {
        echo "  ✗ Neither 'password' nor 'Password' works\n";
    }
}

// Test 4: Test getAuthPassword method
echo "\n=== Test 4: Test getAuthPassword() ===\n";
$authPassword = $staf->getAuthPassword();
if ($authPassword) {
    echo "✓ getAuthPassword() returned: " . substr($authPassword, 0, 20) . "...\n";
    echo "  Match with loginRecord? " . ($authPassword === $hash ? 'YES' : 'NO') . "\n";
} else {
    echo "✗ getAuthPassword() returned NULL!\n";
}

// Test 5: Test authentication
echo "\n=== Test 5: Test Auth Attempt ===\n";
$credentials = [
    'no_kp' => '900101011234',
    'password' => 'password'
];

try {
    $attempt = Auth::attempt($credentials);
    if ($attempt) {
        echo "✓ Authentication SUCCESS!\n";
        echo "  Logged in as: " . Auth::user()->nama . "\n";
        Auth::logout();
    } else {
        echo "✗ Authentication FAILED!\n";
    }
} catch (\Exception $e) {
    echo "✗ Authentication ERROR: {$e->getMessage()}\n";
}

echo "\n=== Tests Complete ===\n";

