<?php
// Try localhost dulu
$host = "localhost";
$user = "root";
$pass = "Noor@z@m1982";

echo "<h3>Testing Local MySQL Connection</h3>";

try {
    $pdo = new PDO('mysql:host=' . $host, $user, $pass);
    echo "✅ Connected to MySQL at localhost<br>";
    
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS mybooking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Database 'mybooking' created successfully<br>";
    
    // Connect to mybooking
    $db = new PDO('mysql:host=' . $host . ';dbname=mybooking', $user, $pass);
    echo "✅ Connected to 'mybooking' database<br>";
    
    // Create tables
    $db->exec("CREATE TABLE IF NOT EXISTS staf (
        id_staf INT PRIMARY KEY AUTO_INCREMENT,
        no_staf VARCHAR(20) UNIQUE,
        no_kp VARCHAR(12) UNIQUE,
        nama VARCHAR(100) NOT NULL,
        emel VARCHAR(100),
        telefon VARCHAR(15),
        id_jawatan INT,
        id_gred INT,
        id_bahagian INT,
        gambar VARCHAR(255),
        id_status INT DEFAULT 1,
        tarikh_daftar DATETIME DEFAULT CURRENT_TIMESTAMP,
        tarikh_kemaskini DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    $db->exec("CREATE TABLE IF NOT EXISTS role (
        id_role INT PRIMARY KEY AUTO_INCREMENT,
        nama_role VARCHAR(50) NOT NULL UNIQUE
    )");
    
    $db->exec("CREATE TABLE IF NOT EXISTS akses (
        id_akses INT PRIMARY KEY AUTO_INCREMENT,
        id_staf INT NOT NULL,
        id_role INT NOT NULL,
        FOREIGN KEY (id_staf) REFERENCES staf(id_staf) ON DELETE CASCADE,
        FOREIGN KEY (id_role) REFERENCES role(id_role) ON DELETE CASCADE,
        UNIQUE KEY unique_staf_role (id_staf, id_role)
    )");
    
    $db->exec("CREATE TABLE IF NOT EXISTS bilik (
        id_bilik INT PRIMARY KEY AUTO_INCREMENT,
        nama_bilik VARCHAR(100) NOT NULL,
        kapasiti INT DEFAULT 10,
        lokasi VARCHAR(100),
        kemudahan TEXT,
        status INT DEFAULT 1,
        tarikh_daftar DATETIME DEFAULT CURRENT_TIMESTAMP,
        tarikh_kemaskini DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    $db->exec("CREATE TABLE IF NOT EXISTS booking (
        id_booking INT PRIMARY KEY AUTO_INCREMENT,
        id_bilik INT NOT NULL,
        id_staf INT NOT NULL,
        tarikh_mula DATE NOT NULL,
        masa_mula TIME NOT NULL,
        masa_tamat TIME NOT NULL,
        tujuan TEXT,
        bilangan_peserta INT DEFAULT 1,
        status INT DEFAULT 0,
        nota TEXT,
        tarikh_tempahan DATETIME DEFAULT CURRENT_TIMESTAMP,
        tarikh_kemaskini DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (id_bilik) REFERENCES bilik(id_bilik) ON DELETE CASCADE,
        FOREIGN KEY (id_staf) REFERENCES staf(id_staf) ON DELETE CASCADE
    )");
    
    $db->exec("CREATE TABLE IF NOT EXISTS approval (
        id_approval INT PRIMARY KEY AUTO_INCREMENT,
        id_booking INT NOT NULL,
        id_approver INT NOT NULL,
        status INT DEFAULT 0,
        tarikh_approval DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_booking) REFERENCES booking(id_booking) ON DELETE CASCADE,
        FOREIGN KEY (id_approver) REFERENCES staf(id_staf) ON DELETE CASCADE
    )");
    
    $db->exec("CREATE TABLE IF NOT EXISTS login (
        id_login INT PRIMARY KEY AUTO_INCREMENT,
        id_staf INT NOT NULL,
        password_hash VARCHAR(255),
        last_login DATETIME,
        FOREIGN KEY (id_staf) REFERENCES staf(id_staf) ON DELETE CASCADE
    )");
    
    // Insert default roles
    $db->exec("INSERT IGNORE INTO role (id_role, nama_role) VALUES 
        (1, 'Staff'),
        (2, 'Manager'),
        (3, 'Admin')");
    
    echo "✅ All 7 tables created successfully<br>";
    echo "<p><strong>Next:</strong> Update db.php to use localhost, then run sync_staf.php</p>";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?>
