<?php
session_start();

// Check if user logged in via MyApps
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] <= 0) {
    header("Location: index.php?akses_ditolak=1");
    exit;
}

// Redirect ke MyBooking dashboard
header("Location: dashboard.php");
exit;
?>
