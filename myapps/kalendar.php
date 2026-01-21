<?php
require 'db.php';

// Embed mode: hide sidebar/header when ?embed=1
$isEmbed = isset($_GET['embed']);

if (!$isEmbed) {
    include 'header.php';
} else {
    echo '<!DOCTYPE html><html><head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Kalendar Hari Lahir</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    </head><body class="embed-mode" style="background: transparent; padding: 0; margin: 0;">';
}

$events = [];
$current_year = date('Y');

// Generate events for years 2000 to 15 years in future
$year_range_start = 2000;
$year_range_end = $current_year + 15;

// 1. DATA QUERY (Penting: Ambil id_user)
$sql = "SELECT u.id_user, u.nama, u.no_kp, u.emel, u.telefon, 
               j.jawatan, b.bahagian, g.gred
        FROM users u 
        LEFT JOIN jawatan j ON u.id_jawatan = j.id_jawatan 
        LEFT JOIN bahagian b ON u.id_bahagian = b.id_bahagian
        LEFT JOIN gred g ON u.id_gred = g.id_gred
        WHERE u.id_status_staf = 1";

try {
    $stmt = $db->query($sql);
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $bulan = substr($row['no_kp'], 2, 2);
        $hari = substr($row['no_kp'], 4, 2);
        $birth_year = substr($row['no_kp'], 0, 2);
        
        // Determine birth century (00-24 = 2000s, 25-99 = 1900s)
        $birth_year = ($birth_year <= 24) ? '20' . $birth_year : '19' . $birth_year;
        
        // Handle data kosong
        $jawatan = $row['jawatan'] ?? '-';
        $bahagian = $row['bahagian'] ?? '-';
        $gred = $row['gred'] ?? '-';
        $emel = $row['emel'] ?? '-';
        $telefon = $row['telefon'] ?? '-';
    
        // Add birthday for all years
        for ($year = $year_range_start; $year <= $year_range_end; $year++) {
            $events[] = [
                'title' => $row['nama'],
                'start' => "$year-$bulan-$hari", 
                'extendedProps' => [ 
                    'id_user' => $row['id_user'], // ID User (Penting)
                    'jawatan' => $jawatan, 
                    'bahagian' => $bahagian,
                    'gred' => $gred,
                    'emel' => $emel,
                    'telefon' => $telefon,
                    'dob' => getTarikhLahir($row['no_kp']),
                    'birth_year' => $birth_year,
                    'birth_month' => $bulan,
                    'birth_day' => $hari
                ]
            ];
        }
    }
} catch (Exception $e) {
    // Silent error
}
?>

<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>

<style>
    .fc-event { cursor: pointer; }
    .modal { z-index: 1055 !important; } 
    .modal-backdrop { z-index: 1050 !important; }
    .fc-button-primary { background-color: #d32f2f !important; border-color: #b71c1c !important; }
    .fc-button-primary:hover { background-color: #b71c1c !important; }
    .fc-button-active { background-color: #a00000 !important; }
    .fc-col-header-cell.fc-day-sun { color: red; }
    
    /* MOBILE RESPONSIVE */
    @media (max-width: 768px) {
        .fc-toolbar {
            flex-wrap: wrap;
            gap: 8px;
            padding: 10px 5px;
        }
        
        .fc-toolbar-chunk {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            justify-content: center;
            width: 100%;
        }
        
        .fc-button {
            padding: 6px 10px !important;
            font-size: 12px !important;
            margin: 2px !important;
        }
        
        .fc-button-group {
            display: flex;
            gap: 2px;
            flex-wrap: wrap;
        }
        
        .fc {
            font-size: 13px;
        }
        
        .fc-daygrid-day-number {
            padding: 4px 2px !important;
            font-size: 12px;
        }
        
        .fc-daygrid-day-frame {
            min-height: 60px;
        }
        
        .fc-col-header-cell {
            padding: 4px 2px !important;
            font-size: 11px;
        }
        
        .fc-daygrid-day {
            padding: 0 !important;
        }
    }
    
    @media (max-width: 480px) {
        .fc-button {
            padding: 4px 8px !important;
            font-size: 11px !important;
        }
        
        .fc-toolbar-title {
            font-size: 16px !important;
        }
        
        .fc-daygrid-day-frame {
            min-height: 50px;
        }
    }
    /* Embed mode: remove padding/background/frame */
    body.embed-mode {
        background: transparent;
        padding: 0;
        margin: 0;
    }
    body.embed-mode .container-fluid {
        padding: 0;
        margin: 0;
    }
    body.embed-mode #calendar {
        margin: 0;
    }
    body.embed-mode .fc-theme-standard .fc-scrollgrid {
        border: none;
    }
    body.embed-mode .fc-theme-standard td,
    body.embed-mode .fc-theme-standard th {
        border: 1px solid #dcdcdc;
    }
</style>

<div class="container-fluid">
    <?php if (!$isEmbed): ?>
    <h3 class="mb-4 fw-bold text-dark"><i class="fas fa-calendar-alt me-3 text-primary"></i>Kalendar Hari Lahir</h3>
    <?php endif; ?>
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <div id='calendar'></div>
        </div>
    </div>
</div>

<div class="modal fade" id="eventModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #d32f2f;">
                <h3 class="modal-title"><i class="fas fa-user-tag me-3"></i>Profil Staf</h3>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4 text-center border-end d-flex flex-column justify-content-center align-items-center">
                        <div class="mb-3 text-secondary"><i class="fas fa-user-circle fa-6x"></i></div>
                        <h5 id="modalNama" class="fw-bold text-dark text-uppercase mb-2"></h5>
                        <span id="modalGredBadge" class="badge bg-success mb-3"></span>
                        <p class="text-muted small mb-0">Umur: <span id="modalUmur" class="fw-bold text-dark"></span> Tahun</p>
                        <p class="text-muted small">Tarikh Lahir: <span id="modalDob" class="fw-bold text-dark"></span></p>
                    </div>

                    <div class="col-md-8">
                        <table class="table table-sm table-borderless mt-2">
                            <tr><td width="30%" class="text-muted fw-bold">Jawatan</td><td width="5%">:</td><td id="modalJawatan" class="fw-bold"></td></tr>
                            <tr><td class="text-muted fw-bold">Bahagian</td><td>:</td><td id="modalBahagian"></td></tr>
                            <tr><td class="text-muted fw-bold">Gred</td><td>:</td><td id="modalGred"></td></tr>
                            <tr><td colspan="3"><hr class="my-2"></td></tr>
                            <tr><td class="text-muted fw-bold"><i class="fas fa-envelope me-2"></i>Emel</td><td>:</td><td id="modalEmel" class="text-primary"></td></tr>
                            <tr><td class="text-muted fw-bold"><i class="fas fa-phone me-2"></i>Telefon</td><td>:</td><td id="modalTelefon"></td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <form method="POST" action="proses_staf.php" class="w-100 d-flex justify-content-end">
                    <?php echo getCsrfTokenField(); // CSRF Protection ?>
                    <input type="hidden" name="id_user_wish" id="inputIdUserWish">
                    <?php if ($_SESSION['role'] === 'super_admin'): ?>
                        <button type="submit" name="send_wish" class="btn btn-success btn-sm">
                            <i class="fas fa-birthday-cake me-2"></i> Hantar Ucapan
                        </button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-secondary btn-sm ms-2" data-bs-dismiss="modal">Tutup</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if ($isEmbed): ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'ms',
        events: <?php echo json_encode($events); ?>,
        headerToolbar: { 
            left: 'prev,next today', 
            center: 'title', 
            right: 'dayGridMonth,listMonth' 
        },
        height: 'auto',
        contentHeight: 'auto',
        buttonText: { 
            today: 'Hari Ini', 
            month: 'Bulan', 
            list: 'Senarai',
            prev: '←',
            next: '→'
        },
        dayCellClassNames: function(arg) {
            // Highlight Sundays in red
            if(arg.date.getDay() === 0) return 'fc-col-header-cell-sunday';
        },
        dayHeaderFormat: { weekday: 'short' }, // Sun, Mon, Tue, etc.
        dayCellContent: function(arg) {
            return arg.dayNumberText;
        },
        monthNames: ['Januari', 'Februari', 'Mac', 'April', 'Mei', 'Jun', 'Julai', 'Ogos', 'September', 'Oktober', 'November', 'Disember'],
        monthNamesShort: ['Jan', 'Feb', 'Mac', 'Apr', 'Mei', 'Jun', 'Jul', 'Ogo', 'Sep', 'Okt', 'Nov', 'Dis'],
        dayNames: ['Ahad', 'Isnin', 'Selasa', 'Rabu', 'Khamis', 'Jumaat', 'Sabtu'],
        dayNamesShort: ['Ahad', 'Isn', 'Sel', 'Rab', 'Kha', 'Jum', 'Sab'],
        contentHeight: 'auto', eventDisplay: 'block', eventColor: '#3788d8',

        eventClick: function(info) {
            info.jsEvent.preventDefault(); 
            var p = info.event.extendedProps;
            
            // Calculate age based on clicked year
            var event_date = info.event.start;
            var clicked_year = new Date(event_date).getFullYear();
            var birth_year = parseInt(p.birth_year);
            var age = clicked_year - birth_year;

            document.getElementById('modalNama').textContent = info.event.title;
            document.getElementById('modalJawatan').textContent = p.jawatan;
            document.getElementById('modalBahagian').textContent = p.bahagian;
            document.getElementById('modalGred').textContent = p.gred;
            document.getElementById('modalGredBadge').textContent = "Gred " + p.gred;
            document.getElementById('modalEmel').textContent = p.emel;
            document.getElementById('modalTelefon').textContent = p.telefon;
            document.getElementById('modalDob').textContent = p.dob;
            document.getElementById('modalUmur').textContent = age;
            
            // MASUKKAN ID KE DALAM FORM
            document.getElementById('inputIdUserWish').value = p.id_user;

            var myModal = new bootstrap.Modal(document.getElementById('eventModal'));
            myModal.show();
        }
    });
    calendar.render();
});
</script>
<?php if ($isEmbed): ?>
</body>
</html>
<?php endif; ?>

<?php if(hasAccess($pdo, $_SESSION['user_id'], 1, 'export_data')): ?>
<!-- Butang Export Excel di sini -->
<?php endif; ?>
