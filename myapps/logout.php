<?php
session_start();

// Log logout before destroying session
require_once 'db.php';
require_once 'audit_functions.php';
logLogout($db);

session_destroy();
header("Location: index.php");
?>
