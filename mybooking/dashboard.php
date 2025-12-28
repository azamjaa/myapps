<?php
require 'db.php';
include 'header.php';

// Get statistics
$totalBooking = $db->query("SELECT COUNT(*) as total FROM booking")->fetch()['total'];
$pendingBooking = $db->query("SELECT COUNT(*) as total FROM booking WHERE status = 0")->fetch()['total'];
$approvedBooking = $db->query("SELECT COUNT(*) as total FROM booking WHERE status = 1")->fetch()['total'];
$totalBilik = $db->query("SELECT COUNT(*) as total FROM bilik WHERE status = 1")->fetch()['total'];

// Get upcoming bookings
$upcomingBookings = $db->query("SELECT b.*, s.nama, bi.nama_bilik 
                                 FROM booking b
                                 JOIN staf s ON b.id_staf = s.id_staf
                                 JOIN bilik bi ON b.id_bilik = bi.id_bilik
                                 WHERE b.tarikh_mula >= CURDATE() AND b.status = 1
                                 ORDER BY b.tarikh_mula, b.masa_mula
                                 LIMIT 10")->fetchAll();
?>

<h3 class="mb-4 fw-bold text-dark">
    <i class="fas fa-tachometer-alt me-3 text-primary"></i> Dashboard MyBooking
</h3>

<!-- STATS CARDS -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted small mb-2">JUMLAH TEMPAHAN</p>
                        <h3 class="mb-0 text-primary fw-bold"><?php echo $totalBooking; ?></h3>
                    </div>
                    <i class="fas fa-calendar-check fa-2x text-primary opacity-25"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted small mb-2">TEMPAHAN PENDING</p>
                        <h3 class="mb-0 text-warning fw-bold"><?php echo $pendingBooking; ?></h3>
                    </div>
                    <i class="fas fa-hourglass-half fa-2x text-warning opacity-25"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted small mb-2">TEMPAHAN DISAHKAN</p>
                        <h3 class="mb-0 text-success fw-bold"><?php echo $approvedBooking; ?></h3>
                    </div>
                    <i class="fas fa-check-circle fa-2x text-success opacity-25"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted small mb-2">JUMLAH BILIK</p>
                        <h3 class="mb-0 text-info fw-bold"><?php echo $totalBilik; ?></h3>
                    </div>
                    <i class="fas fa-door-open fa-2x text-info opacity-25"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- UPCOMING BOOKINGS -->
<div class="row">
    <div class="col-md-12">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i> Tempahan Akan Datang (10 Seterusnya)</h5>
            </div>
            <div class="card-body p-0">
                <?php if (count($upcomingBookings) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="py-3">Tarikh & Masa</th>
                                    <th>Bilik</th>
                                    <th>Tujuan</th>
                                    <th>Pengurus</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcomingBookings as $booking): ?>
                                <tr>
                                    <td class="fw-bold">
                                        <?php echo formatTarikh($booking['tarikh_mula']); ?><br>
                                        <small class="text-muted"><?php echo date('H:i', strtotime($booking['masa_mula'])); ?> - <?php echo date('H:i', strtotime($booking['masa_tamat'])); ?></small>
                                    </td>
                                    <td><?php echo $booking['nama_bilik']; ?></td>
                                    <td><?php echo substr($booking['tujuan'], 0, 50); ?></td>
                                    <td><?php echo $booking['nama']; ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-<?php echo getStatusColor($booking['status']); ?>">
                                            <?php echo getStatusText($booking['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-5 text-center text-muted">
                        <i class="fas fa-calendar-times fa-3x opacity-25 mb-3 d-block"></i>
                        Tiada tempahan akan datang
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</div><!-- END MAIN CONTENT -->
</body>
</html>
