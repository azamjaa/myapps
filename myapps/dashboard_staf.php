<?php
require 'db.php';
include 'header.php';

// Statistik
$cntStaf = $db->query("SELECT COUNT(*) FROM users WHERE id_status_staf = 1")->fetchColumn();
$cntJawatan = $db->query("SELECT COUNT(DISTINCT id_jawatan) FROM users WHERE id_status_staf = 1")->fetchColumn();
$cntBahagian = $db->query("SELECT COUNT(DISTINCT id_bahagian) FROM users WHERE id_status_staf = 1")->fetchColumn();
$bulanIni = date('m');
// Fixed: Use prepared statement instead of string concatenation
$stmtBirthday = $db->prepare("SELECT COUNT(*) FROM users WHERE SUBSTRING(no_kp, 3, 2) = ? AND id_status_staf = 1");
$stmtBirthday->execute([$bulanIni]);
$cntBirthday = $stmtBirthday->fetchColumn();

// Data Chart
$chartBahagian = $db->query("SELECT b.bahagian, COUNT(u.id_user) as total FROM users u JOIN bahagian b ON u.id_bahagian = b.id_bahagian WHERE u.id_status_staf = 1 GROUP BY b.bahagian ORDER BY total DESC")->fetchAll();
$chartJawatan = $db->query("SELECT j.jawatan, COUNT(u.id_user) as total FROM users u JOIN jawatan j ON u.id_jawatan = j.id_jawatan WHERE u.id_status_staf = 1 GROUP BY j.jawatan ORDER BY total DESC LIMIT 5")->fetchAll();
?>

<div class="container-fluid">
    <h3 class="mb-4 fw-bold text-dark"><i class="fas fa-tachometer-alt me-3 text-primary"></i>Dashboard Staf</h3>

    <?php if(hasAccess($pdo, $_SESSION['user_id'], 1, 'view_dashboard')): ?>
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left: 5px solid #10B981 !important; background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%) !important;">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted text-uppercase">Jumlah Staf</h6>
                        <h2 class="fw-bold mb-0" style="background: linear-gradient(135deg, #10B981 0%, #059669 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"><?php echo $cntStaf; ?></h2>
                    </div>
                    <div class="d-flex align-items-center justify-content-center rounded-circle" style="width: 70px; height: 70px; background: linear-gradient(135deg, #10B981 0%, #059669 100%); flex-shrink: 0;">
                        <i class="fas fa-users fa-2x text-white"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left: 5px solid #F59E0B !important; background: linear-gradient(135deg, #ffffff 0%, #fffbeb 100%) !important;">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted text-uppercase">Jawatan</h6>
                        <h2 class="fw-bold mb-0" style="background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"><?php echo $cntJawatan; ?></h2>
                    </div>
                    <div class="d-flex align-items-center justify-content-center rounded-circle" style="width: 70px; height: 70px; background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%); flex-shrink: 0;">
                        <i class="fas fa-briefcase fa-2x text-white"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left: 5px solid #EF4444 !important; background: linear-gradient(135deg, #ffffff 0%, #fef2f2 100%) !important;">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted text-uppercase">Bahagian</h6>
                        <h2 class="fw-bold mb-0" style="background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"><?php echo $cntBahagian; ?></h2>
                    </div>
                    <div class="d-flex align-items-center justify-content-center rounded-circle" style="width: 70px; height: 70px; background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%); flex-shrink: 0;">
                        <i class="fas fa-building-columns fa-2x text-white"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left: 5px solid #4169E1 !important; background: linear-gradient(135deg, #ffffff 0%, #eff6ff 100%) !important;">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-muted text-uppercase">Hari Lahir (Bulan Ini)</h6>
                        <h2 class="fw-bold mb-0" style="background: linear-gradient(135deg, #4169E1 0%, #1E40AF 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"><?php echo $cntBirthday; ?></h2>
                    </div>
                    <div class="d-flex align-items-center justify-content-center rounded-circle" style="width: 70px; height: 70px; background: linear-gradient(135deg, #4169E1 0%, #1E40AF 100%); flex-shrink: 0;">
                        <i class="fas fa-cake-candles fa-2x text-white"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3"><h6 class="m-0 font-weight-bold text-primary">Bilangan Staf Mengikut Bahagian</h6></div>
                <div class="card-body">
                    <div style="height: 350px;"><canvas id="barChart"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3"><h6 class="m-0 font-weight-bold text-primary">Top 5 Jawatan</h6></div>
                <div class="card-body">
                    <div style="height: 350px;"><canvas id="pieChart"></canvas></div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Set default font for all charts
Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
Chart.defaults.font.size = 12;

const ctxBar = document.getElementById('barChart').getContext('2d');
const colorPalette = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796', '#2e59a7', '#17a2b8', '#20c997', '#ffc107', '#6610f2', '#e83e8c', '#fd7e14', '#6f42c1', '#00d4ff', '#92e7e8', '#ff6b6b', '#4ecdc4', '#45b7d1', '#96ceb4', '#dda15e', '#bc6c25', '#ffd60a', '#003566', '#606c38', '#283618', '#f8b195', '#f67280', '#c06c84', '#6c567b', '#d4af37'];
const numBahagian = <?php echo count($chartBahagian); ?>;
const chartColors = colorPalette.slice(0, numBahagian);

// Convert labels to uppercase
const bahagianiLabels = <?php echo json_encode(array_map('strtoupper', array_column($chartBahagian, 'bahagian'))); ?>;

// Create gradient colors for bar chart
const gradientColors = chartColors.map(color => {
    const gradient = ctxBar.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, color);
    gradient.addColorStop(1, color + 'cc'); // Add transparency at bottom
    return gradient;
});

new Chart(ctxBar, {
    type: 'bar',
    data: {
        labels: bahagianiLabels,
        datasets: [{ label: 'Staf', data: <?php echo json_encode(array_column($chartBahagian, 'total')); ?>, backgroundColor: gradientColors, borderRadius: 8, borderWidth: 0 }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
});

const ctxPie = document.getElementById('pieChart').getContext('2d');
const jawatanLabels = <?php echo json_encode(array_map('strtoupper', array_column($chartJawatan, 'jawatan'))); ?>;

// Create gradient colors for pie chart
const pieGradients = [
    (() => {
        const g = ctxPie.createLinearGradient(0, 0, 0, 400);
        g.addColorStop(0, '#4169E1');
        g.addColorStop(1, '#1E40AF');
        return g;
    })(),
    (() => {
        const g = ctxPie.createLinearGradient(0, 0, 0, 400);
        g.addColorStop(0, '#10B981');
        g.addColorStop(1, '#059669');
        return g;
    })(),
    (() => {
        const g = ctxPie.createLinearGradient(0, 0, 0, 400);
        g.addColorStop(0, '#06B6D4');
        g.addColorStop(1, '#0891B2');
        return g;
    })(),
    (() => {
        const g = ctxPie.createLinearGradient(0, 0, 0, 400);
        g.addColorStop(0, '#F59E0B');
        g.addColorStop(1, '#D97706');
        return g;
    })(),
    (() => {
        const g = ctxPie.createLinearGradient(0, 0, 0, 400);
        g.addColorStop(0, '#EF4444');
        g.addColorStop(1, '#DC2626');
        return g;
    })()
];

new Chart(ctxPie, {
    type: 'doughnut',
    data: {
        labels: jawatanLabels,
        datasets: [{ 
            data: <?php echo json_encode(array_column($chartJawatan, 'total')); ?>, 
            backgroundColor: pieGradients,
            borderWidth: 3,
            borderColor: '#fff'
        }]
    },
    options: { 
        responsive: true, 
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    font: { size: 11 }
                }
            }
        }
    }
});
</script>
</div></body></html>
