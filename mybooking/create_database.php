<?php
// Database credentials (sama dengan MyApps)
$host = "10.141.80.32";
$user = "root";
$pass = "Noor@z@m1982";

try {
    // Create connection ke MySQL (tanpa specify database dulu)
    $conn = new PDO(
        'mysql:host=' . $host,
        $user,
        $pass,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
    
    // Create database
    $conn->exec("CREATE DATABASE IF NOT EXISTS mybooking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // Now connect to mybooking database
    $db = new PDO(
        'mysql:host=' . $host . ';dbname=mybooking',
        $user,
        $pass,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
    
    // Create staf table
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
    
    // Create role table
    $db->exec("CREATE TABLE IF NOT EXISTS role (
        id_role INT PRIMARY KEY AUTO_INCREMENT,
        nama_role VARCHAR(50) NOT NULL UNIQUE
    )");
    
    // Create akses table
    $db->exec("CREATE TABLE IF NOT EXISTS akses (
        id_akses INT PRIMARY KEY AUTO_INCREMENT,
        id_staf INT NOT NULL,
        id_role INT NOT NULL,
        FOREIGN KEY (id_staf) REFERENCES staf(id_staf) ON DELETE CASCADE,
        FOREIGN KEY (id_role) REFERENCES role(id_role) ON DELETE CASCADE,
        UNIQUE KEY unique_staf_role (id_staf, id_role)
    )");
    
    // Create bilik table
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
    
    // Create booking table
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
    
    // Create approval table
    $db->exec("CREATE TABLE IF NOT EXISTS approval (
        id_approval INT PRIMARY KEY AUTO_INCREMENT,
        id_booking INT NOT NULL,
        id_approver INT NOT NULL,
        status INT DEFAULT 0,
        tarikh_approval DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_booking) REFERENCES booking(id_booking) ON DELETE CASCADE,
        FOREIGN KEY (id_approver) REFERENCES staf(id_staf) ON DELETE CASCADE
    )");
    
    // Create login table
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
    
    echo "<h2>✅ DATABASE CREATION SUCCESSFUL</h2>";
    echo "<p>Database 'mybooking' created with 7 tables:</p>";
    echo "<ul>
        <li>staf (staff data)</li>
        <li>role (roles definition)</li>
        <li>akses (role assignments)</li>
        <li>bilik (meeting rooms)</li>
        <li>booking (reservations)</li>
        <li>approval (approvals)</li>
        <li>login (session tracking)</li>
    </ul>";
    echo "<p>Default roles inserted: Staff, Manager, Admin</p>";
    echo "<p><strong>Next step:</strong> Run <a href='sync_staf.php'>sync_staf.php</a> to import staff from MyApps</p>";
    
} catch (Exception $e) {
    echo "<h2>❌ ERROR</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
