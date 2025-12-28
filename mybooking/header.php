<?php
require 'db.php';

// Check if user logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Verify user still active
$stmtCheck = $db->prepare("SELECT id_staf FROM staf WHERE id_staf = ? AND id_status = 1");
$stmtCheck->execute([$_SESSION['user_id']]);
if (!$stmtCheck->fetch()) {
    session_destroy();
    header("Location: index.php?error=akses_ditolak");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyBooking - Sistem Tempahan Bilik Mesyuarat KEDA</title>
    <link rel="icon" type="image/png" href="image/keda.png?v=2">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        body { background-color: #f8f9fa; overflow-x: hidden; }
        
        /* SIDEBAR STYLING */
        .sidebar { min-height: 100vh; width: 260px; background: #2c3e50; color: white; 
                   transition: all 0.3s ease; position: fixed; z-index: 1000; top: 0; left: 0; }
        .sidebar a.nav-link { color: #bdc3c7; text-decoration: none; display: block; 
                              padding: 15px 25px; border-left: 4px solid transparent; }
        .sidebar a.nav-link:hover, .sidebar a.nav-link.active { 
            background-color: rgba(255,255,255,0.1); color: white; border-left-color: #3498db; }
        .sidebar-header { padding: 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header h4 { margin: 0; font-size: 1.3rem; }
        
        /* MAIN CONTENT */
        .main-content { margin-left: 260px; padding: 20px; transition: all 0.3s ease; }
        @media (max-width: 768px) {
            .sidebar { width: 100%; height: auto; position: relative; }
            .main-content { margin-left: 0; }
        }
        
        /* NAVBAR */
        .top-navbar { background: white; border-bottom: 1px solid #ddd; padding: 15px 20px; 
                      display: flex; justify-content: space-between; align-items: center; }
        .top-navbar h5 { margin: 0; color: #2c3e50; }
        .top-navbar .user-info { display: flex; align-items: center; gap: 15px; }
        
        /* CARDS */
        .card { border: 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .card-header { background: #3498db; color: white; border: 0; }
        
        /* BUTTONS */
        .btn-primary { background: #3498db; border: 0; }
        .btn-primary:hover { background: #2980b9; }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div style="margin-bottom: 10px;">
            <img src="image/keda.png" style="width: 40px; margin-bottom: 10px;">
        </div>
        <h4>MyBooking</h4>
        <small style="color: #bdc3c7;">Tempahan Bilik</small>
    </div>
    
    <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='dashboard.php'?'active':''; ?>">
        <i class="fas fa-chart-line me-3"></i> Dashboard
    </a>
    <a href="booking_list.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='booking_list.php'?'active':''; ?>">
        <i class="fas fa-list me-3"></i> Senarai Tempahan
    </a>
    <a href="booking_calendar.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='booking_calendar.php'?'active':''; ?>">
        <i class="fas fa-calendar-alt me-3"></i> Kalendar
    </a>
    <a href="booking_add.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='booking_add.php'?'active':''; ?>">
        <i class="fas fa-plus-circle me-3"></i> Tempahan Baru
    </a>
    
    <?php if (canApprove($_SESSION['user_id'])): ?>
    <hr style="border-color: rgba(255,255,255,0.1); margin: 10px 0;">
    <a href="approval_list.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='approval_list.php'?'active':''; ?>">
        <i class="fas fa-check-circle me-3"></i> Persetujuan
    </a>
    <?php endif; ?>
    
    <?php if (isAdmin($_SESSION['user_id'])): ?>
    <hr style="border-color: rgba(255,255,255,0.1); margin: 10px 0;">
    <a href="bilik_list.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='bilik_list.php'?'active':''; ?>">
        <i class="fas fa-door-open me-3"></i> Pengurusan Bilik
    </a>
    <a href="staf_list.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='staf_list.php'?'active':''; ?>">
        <i class="fas fa-users me-3"></i> Pengurusan Staf
    </a>
    <?php endif; ?>
    
    <hr style="border-color: rgba(255,255,255,0.1); margin: 10px 0;">
    <a href="logout.php" class="nav-link" style="color: #e74c3c;">
        <i class="fas fa-sign-out-alt me-3"></i> Keluar
    </a>
</div>

<!-- TOP NAVBAR -->
<div class="top-navbar">
    <h5>MyBooking KEDA</h5>
    <div class="user-info">
        <span><?php echo $_SESSION['nama'] ?? 'User'; ?></span>
        <a href="logout.php" class="btn btn-sm btn-danger"><i class="fas fa-sign-out-alt"></i></a>
    </div>
</div>

<!-- MAIN CONTENT WRAPPER -->
<div class="main-content">
