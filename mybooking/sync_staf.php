<?php
// Connect to MyApps database (remote)
$myapps_host = "10.141.80.32";
$myapps_user = "root";
$myapps_pass = "Noor@z@m1982";

// Connect to MyBooking database (local)
$mybooking_host = "localhost";
$mybooking_user = "root";
$mybooking_pass = "Noor@z@m1982";

try {
    // Connect to MyApps
    $myapps_db = new PDO(
        'mysql:host=' . $myapps_host . ';dbname=myapps',
        $myapps_user,
        $myapps_pass,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
    
    // Connect to MyBooking
    $mybooking_db = new PDO(
        'mysql:host=' . $mybooking_host . ';dbname=mybooking',
        $mybooking_user,
        $mybooking_pass,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
    
    // Get semua staff dari MyApps
    $stmt = $myapps_db->query("SELECT * FROM staf ORDER BY id_staf");
    $staffs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $synced = 0;
    $errors = 0;
    
    foreach ($staffs as $staff) {
        try {
            // Check if staff already exists
            $check = $mybooking_db->prepare("SELECT id_staf FROM staf WHERE id_staf = ?");
            $check->execute([$staff['id_staf']]);
            $exists = $check->fetch();
            
            if ($exists) {
                // Update existing
                $update = $mybooking_db->prepare("
                    UPDATE staf SET 
                    no_staf = ?, no_kp = ?, nama = ?, emel = ?, telefon = ?, 
                    id_jawatan = ?, id_gred = ?, id_bahagian = ?, gambar = ?, 
                    id_status = ?, tarikh_kemaskini = NOW()
                    WHERE id_staf = ?
                ");
                $update->execute([
                    $staff['no_staf'], $staff['no_kp'], $staff['nama'], $staff['emel'],
                    $staff['telefon'], $staff['id_jawatan'], $staff['id_gred'],
                    $staff['id_bahagian'], $staff['gambar'], $staff['id_status'],
                    $staff['id_staf']
                ]);
            } else {
                // Insert new
                $insert = $mybooking_db->prepare("
                    INSERT INTO staf (id_staf, no_staf, no_kp, nama, emel, telefon, 
                    id_jawatan, id_gred, id_bahagian, gambar, id_status, tarikh_daftar)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $insert->execute([
                    $staff['id_staf'], $staff['no_staf'], $staff['no_kp'], $staff['nama'],
                    $staff['emel'], $staff['telefon'], $staff['id_jawatan'],
                    $staff['id_gred'], $staff['id_bahagian'], $staff['gambar'],
                    $staff['id_status']
                ]);
                
                // Create login record for new staff
                $login = $mybooking_db->prepare("
                    INSERT INTO login (id_staf, password_hash, last_login)
                    VALUES (?, ?, NULL)
                ");
                $login->execute([$staff['id_staf'], 'sso_only']);
                
                // Assign default Staff role
                $role = $mybooking_db->prepare("
                    INSERT INTO akses (id_staf, id_role)
                    VALUES (?, 1)
                ");
                $role->execute([$staff['id_staf']]);
            }
            
            $synced++;
        } catch (Exception $e) {
            $errors++;
        }
    }
    
    echo "<h3>✅ SYNC SELESAI</h3>";
    echo "<p>Synced: <strong>$synced staff</strong></p>";
    if ($errors > 0) {
        echo "<p style='color:red'>Errors: $errors</p>";
    }
    echo "<p><strong>Next:</strong> Open <a href='dashboard.php'>dashboard.php</a></p>";
    
} catch (Exception $e) {
    echo "<h3>❌ ERROR</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
