<?php
// Simple verification script to list all MyBooking files created
$files = [
    'index.php' => 'SSO login redirect',
    'logout.php' => 'Session cleanup',
    'db.php' => 'Database connection & helper functions',
    'header.php' => 'Navigation & layout',
    'dashboard.php' => 'Statistics dashboard',
    'create_database.php' => 'Database setup (execute once)',
    'sync_staf.php' => 'Staff synchronization (execute periodically)',
    'booking_list.php' => 'Booking list with search & filter',
    'booking_add.php' => 'Create new booking',
    'booking_edit.php' => 'Edit pending booking',
    'booking_calendar.php' => 'Calendar view of approved bookings',
    'approval_list.php' => 'Manager approval workflow',
    'bilik_list.php' => 'Room management (admin)',
    'bilik_add.php' => 'Add new meeting room (admin)',
    'bilik_edit.php' => 'Edit room details (admin)',
    'staf_list.php' => 'Staff role management (admin)'
];

echo "<!DOCTYPE html>
<html>
<head>
    <title>MyBooking File Verification</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='bg-light p-5'>
<div class='container'>
    <h1 class='mb-4'>✅ MyBooking Files Created Successfully</h1>
    <div class='card shadow'>
        <div class='card-body'>
            <table class='table table-striped'>
                <thead class='table-dark'>
                    <tr>
                        <th>File</th>
                        <th>Description</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>";

$base_path = dirname(__FILE__);
foreach ($files as $filename => $description) {
    $file_path = $base_path . '/' . $filename;
    $exists = file_exists($file_path);
    $status = $exists ? '✅ Created' : '❌ Missing';
    $class = $exists ? 'table-success' : 'table-danger';
    
    echo "<tr class='$class'>
            <td><code>$filename</code></td>
            <td>$description</td>
            <td>$status</td>
        </tr>";
}

echo "</tbody>
            </table>
        </div>
    </div>
    
    <div class='alert alert-info mt-4'>
        <h5>Next Steps:</h5>
        <ol>
            <li>Run <code>sync_staf.php</code> to import staff from MyApps</li>
            <li>Update header.php navigation to include admin pages (bilik_list.php, staf_list.php) for admins</li>
            <li>Test the booking workflow (Add → Approve → View in Calendar)</li>
            <li>Test room management & staff role assignments</li>
        </ol>
    </div>
</div>
</body>
</html>";
?>
