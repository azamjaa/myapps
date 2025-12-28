<?php
$host = "10.141.80.32";
$user = "root";
$pass = "Noor@z@m1982";

echo "<h3>Testing MySQL Connection</h3>";

// Test 1: Connect to MySQL server
try {
    $pdo = new PDO('mysql:host=' . $host, $user, $pass);
    echo "✅ Connected to MySQL server<br>";
} catch (PDOException $e) {
    echo "❌ Cannot connect to MySQL: " . $e->getMessage() . "<br>";
    exit;
}

// Test 2: Check if mybooking database exists
try {
    $result = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = 'mybooking'");
    $exists = $result->fetch();
    
    if ($exists) {
        echo "✅ Database 'mybooking' exists<br>";
    } else {
        echo "❌ Database 'mybooking' does NOT exist<br>";
        echo "Creating database...<br>";
        $pdo->exec("CREATE DATABASE IF NOT EXISTS mybooking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✅ Database created<br>";
    }
} catch (PDOException $e) {
    echo "❌ Error checking database: " . $e->getMessage() . "<br>";
}

// Test 3: Try to connect to mybooking
try {
    $db = new PDO('mysql:host=' . $host . ';dbname=mybooking', $user, $pass);
    echo "✅ Connected to 'mybooking' database<br>";
    
    // Check tables
    $tables = $db->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'mybooking'")->fetchAll();
    echo "📊 Tables in mybooking: " . count($tables) . "<br>";
    foreach ($tables as $table) {
        echo "  - " . $table['TABLE_NAME'] . "<br>";
    }
} catch (PDOException $e) {
    echo "❌ Cannot connect to 'mybooking': " . $e->getMessage() . "<br>";
}
?>
