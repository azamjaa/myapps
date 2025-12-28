<?php
require 'db.php';
include 'header.php';

$my_id = $_SESSION['user_id'];
$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Validate month/year
if ($month < 1 || $month > 12) $month = date('m');
if ($year < 2020 || $year > 2100) $year = date('Y');

// Get all approved bookings for this month
$first_day = "$year-$month-01";
$last_day = date('Y-m-t', strtotime($first_day));

$bookings = $db->prepare("SELECT b.*, s.nama, bi.nama_bilik, bi.id_bilik
                          FROM booking b
                          JOIN staf s ON b.id_staf = s.id_staf
                          JOIN bilik bi ON b.id_bilik = bi.id_bilik
                          WHERE b.status = 1 
                          AND DATE(b.tarikh_mula) BETWEEN ? AND ?
                          ORDER BY b.tarikh_mula, b.masa_mula")->execute([$first_day, $last_day])->fetchAll();

// Group bookings by date
$bookings_by_date = [];
foreach ($bookings as $booking) {
    $date = $booking['tarikh_mula'];
    if (!isset($bookings_by_date[$date])) {
        $bookings_by_date[$date] = [];
    }
    $bookings_by_date[$date][] = $booking;
}

// Generate calendar
$first_date = new DateTime($first_day);
$start_day = intval($first_date->format('w')); // 0 = Sunday
$days_in_month = intval($first_date->format('t'));
$week_rows = ceil(($start_day + $days_in_month) / 7);

$days_of_week = ['Ahad', 'Isnin', 'Selasa', 'Rabu', 'Khamis', 'Jumaat', 'Sabtu'];
?>

<style>
.calendar-day {
    min-height: 120px;
    border: 1px solid #dee2e6;
    padding: 8px;
    background: white;
    position: relative;
}
.calendar-day.other-month {
    background: #f8f9fa;
    color: #999;
}
.calendar-day.today {
    background: #fff3cd;
}
.calendar-day .day-number {
    font-weight: bold;
    margin-bottom: 5px;
}
.calendar-booking {
    background: #e3f2fd;
    border-left: 3px solid #2196f3;
    padding: 4px 6px;
    margin-bottom: 3px;
    font-size: 0.75rem;
    border-radius: 2px;
    cursor: pointer;
    transition: all 0.2s;
}
.calendar-booking:hover {
    background: #bbdefb;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.calendar-booking.room-a { border-left-color: #f44336; background: #ffebee; }
.calendar-booking.room-b { border-left-color: #2196f3; background: #e3f2fd; }
.calendar-booking.room-c { border-left-color: #4caf50; background: #e8f5e9; }
.calendar-booking.room-d { border-left-color: #ff9800; background: #fff3e0; }
</style>

<h3 class="mb-4 fw-bold text-dark">
    <i class="fas fa-calendar-alt me-3 text-primary"></i> Kalendar Tempahan
</h3>

<!-- MONTH NAVIGATION -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0">
        <strong><?php echo strftime('%B %Y', strtotime("$year-$month-01")); ?></strong>
    </h5>
    <div>
        <a href="?month=<?php echo ($month == 1) ? 12 : $month - 1; ?>&year=<?php echo ($month == 1) ? $year - 1 : $year; ?>" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-chevron-left"></i> Bulan Sebelumnya
        </a>
        <a href="?month=<?php echo date('m'); ?>&year=<?php echo date('Y'); ?>" class="btn btn-sm btn-outline-primary">
            Hari Ini
        </a>
        <a href="?month=<?php echo ($month == 12) ? 1 : $month + 1; ?>&year=<?php echo ($month == 12) ? $year + 1 : $year; ?>" class="btn btn-sm btn-outline-secondary">
            Bulan Depan <i class="fas fa-chevron-right"></i>
        </a>
    </div>
</div>

<!-- CALENDAR -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead class="bg-primary text-white">
                    <tr>
                        <?php foreach ($days_of_week as $day): ?>
                        <th class="text-center py-3"><?php echo $day; ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $day_counter = 1;
                    for ($week = 0; $week < $week_rows; $week++):
                    ?>
                    <tr>
                        <?php for ($dow = 0; $dow < 7; $dow++): ?>
                        <td class="calendar-day <?php 
                            if ($day_counter > $days_in_month) {
                                echo 'other-month';
                            } else {
                                $current_date = sprintf("%04d-%02d-%02d", $year, $month, $day_counter);
                                if ($current_date == date('Y-m-d')) echo 'today';
                            }
                        ?>">
                            <?php
                            if ($day_counter <= $days_in_month):
                                $current_date = sprintf("%04d-%02d-%02d", $year, $month, $day_counter);
                                echo '<div class="day-number">' . $day_counter . '</div>';
                                
                                if (isset($bookings_by_date[$current_date])):
                                    foreach ($bookings_by_date[$current_date] as $booking):
                                        $room_class = 'room-' . chr(96 + ($booking['id_bilik'] % 4));
                                ?>
                                <div class="calendar-booking <?php echo $room_class; ?>" 
                                     data-bs-toggle="modal" data-bs-target="#detailModal"
                                     onclick="viewDetail(<?php echo htmlspecialchars(json_encode($booking)); ?>)"
                                     title="<?php echo htmlspecialchars($booking['nama_bilik']); ?>">
                                    <strong><?php echo substr($booking['nama_bilik'], 0, 10); ?></strong><br>
                                    <small><?php echo date('H:i', strtotime($booking['masa_mula'])); ?></small>
                                </div>
                                <?php
                                    endforeach;
                                endif;
                                $day_counter++;
                            ?>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <?php endfor; ?>
                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- LEGEND -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-light border-bottom">
        <h6 class="mb-0 fw-bold">Catatan</h6>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p class="mb-0"><i class="fas fa-info-circle text-info me-2"></i> Klik pada tempahan untuk melihat butiran lengkap</p>
            </div>
            <div class="col-md-6">
                <p class="mb-0"><i class="fas fa-info-circle text-warning me-2"></i> Hanya tempahan yang telah diluluskan ditampilkan (Status: Approved)</p>
            </div>
        </div>
    </div>
</div>

<!-- DETAIL MODAL -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Detail Tempahan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="detailContent"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <a id="editLink" href="#" class="btn btn-warning">Edit</a>
            </div>
        </div>
    </div>
</div>

<script>
function viewDetail(booking) {
    let html = `
        <div class="row mb-3">
            <div class="col-md-6">
                <strong>Bilik:</strong><br> ${booking.nama_bilik}
            </div>
            <div class="col-md-6">
                <strong>Pengurus:</strong><br> ${booking.nama}
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <strong>Tarikh:</strong><br> ${new Date(booking.tarikh_mula).toLocaleDateString('ms-MY')}
            </div>
            <div class="col-md-6">
                <strong>Masa:</strong><br> ${booking.masa_mula} - ${booking.masa_tamat}
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <strong>Bilangan Peserta:</strong><br> ${booking.bilangan_peserta}
            </div>
            <div class="col-md-6">
                <strong>Status:</strong><br> <span class="badge bg-success">Diluluskan</span>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-12">
                <strong>Tujuan:</strong><br> ${booking.tujuan}
            </div>
        </div>
    `;
    document.getElementById('detailContent').innerHTML = html;
    document.getElementById('editLink').href = 'booking_edit.php?id=' + booking.id_booking;
}
</script>

</div><!-- END MAIN CONTENT -->
</body>
</html>
