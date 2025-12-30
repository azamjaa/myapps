<?php
require 'db.php';
include 'header.php';

// Get language
$lang = isset($_GET['lang']) ? $_GET['lang'] : (isset($_SESSION['lang']) ? $_SESSION['lang'] : 'ms');
?>

<style>
    /* Tab Styling */
    .nav-pills .nav-link {
        border-radius: 8px;
        background-color: #f0f0f0;
        color: #333;
        font-weight: 500;
        transition: all 0.3s ease;
        padding: 10px 15px;
        font-size: 14px;
    }
    
    .nav-pills .nav-link:hover {
        background-color: #e0e0e0;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .nav-pills .nav-link.active {
        background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }
    
    /* Responsive tabs */
    @media (max-width: 768px) {
        .nav-pills .nav-link {
            padding: 8px 12px;
            font-size: 13px;
        }
        
        .nav-pills {
            gap: 6px !important;
        }
    }
</style>

<div class="container-fluid">
    <h3 class="mb-4 fw-bold text-dark"><i class="fas fa-book-open me-3 text-primary"></i>Manual Pengguna</h3>

    <!-- Content Sections -->
    <div class="row">
        <div class="col-lg-8">
            <!-- Tab Navigation -->
            <div class="mb-4">
                <ul class="nav nav-pills" id="manualTabs" role="tablist" style="flex-wrap: wrap; gap: 8px;">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#home" type="button" role="tab">
                            <i class="fas fa-home me-2"></i>Pendahuluan
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="dashboard-tab" data-bs-toggle="tab" data-bs-target="#dashboard" type="button" role="tab">
                            <i class="fas fa-chart-line me-2"></i>Dashboard
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="direktori-tab" data-bs-toggle="tab" data-bs-target="#direktori" type="button" role="tab">
                            <i class="fas fa-list me-2"></i>Direktori
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="calendar-tab" data-bs-toggle="tab" data-bs-target="#calendar" type="button" role="tab">
                            <i class="fas fa-calendar me-2"></i>Kalendar
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="chat-tab" data-bs-toggle="tab" data-bs-target="#chat" type="button" role="tab">
                            <i class="fas fa-comments me-2"></i>Chat
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Tab Content -->
            <div class="tab-content" id="manualTabContent">
                
                <!-- PENDAHULUAN -->
                <div class="tab-pane fade show active" id="home" role="tabpanel">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Selamat Datang ke MyApps KEDA</h5>
                            <p class="card-text">MyApps KEDA adalah sistem direktori aplikasi dan staf yang dirancang untuk memudahkan pengurusan maklumat organisasi anda.</p>
                            
                            <h6 class="mt-4 mb-2">Fitur Utama:</h6>
                            <ul>
                                <li>📊 Dashboard - Paparan ringkas statistik aplikasi dan staf</li>
                                <li>📋 Direktori - Carian lengkap aplikasi dan maklumat staf</li>
                                <li>📅 Kalendar - Jadual hari lahir staf</li>
                                <li>💬 Chat - Bantuan digital melalui bot Mawar</li>
                                <li>📱 PWA - Aplikasi dapat dipasang di peranti mudah alih</li>
                            </ul>

                            <h6 class="mt-4 mb-2">Untuk Memulakan:</h6>
                            <ol>
                                <li>Login dengan akaun anda</li>
                                <li>Pilih menu sesuai keperluan</li>
                                <li>Gunakan carian untuk cari maklumat</li>
                                <li>Hubungi staf melalui maklumat hubungan yang disediakan</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <!-- DASHBOARD -->
                <div class="tab-pane fade" id="dashboard" role="tabpanel">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Dashboard Aplikasi & Staf</h5>
                            
                            <h6 class="mt-3 mb-2">Elemen Dashboard:</h6>
                            <ul>
                                <li><strong>Kad Statistik:</strong> Menunjukkan jumlah aplikasi dan staf mengikut kategori</li>
                                <li><strong>Carta:</strong> Visualisasi data dalam bentuk graf</li>
                                <li><strong>Senarai Aplikasi:</strong> Aplikasi dalaman, luaran dan gunasama</li>
                                <li><strong>Senarai Staf:</strong> Maklumat pekerja mengikut bahagian</li>
                            </ul>

                            <h6 class="mt-3 mb-2">Cara Menggunakan:</h6>
                            <ol>
                                <li>Klik pada kad statistik untuk tapis data</li>
                                <li>Hover pada carta untuk lihat detail</li>
                                <li>Gunakan scroll untuk lihat lebih banyak aplikasi/staf</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <!-- DIREKTORI -->
                <div class="tab-pane fade" id="direktori" role="tabpanel">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Direktori Aplikasi & Staf</h5>
                            
                            <h6 class="mt-3 mb-2">Direktori Aplikasi:</h6>
                            <ul>
                                <li>Senarai lengkap semua aplikasi yang ada</li>
                                <li>Cari menggunakan nama atau kata kunci</li>
                                <li>Lihat maklumat terperinci seperti deskripsi dan kategori</li>
                                <li>Gunakan filter untuk tapis aplikasi mengikut jenis</li>
                            </ul>

                            <h6 class="mt-3 mb-2">Direktori Staf:</h6>
                            <ul>
                                <li>Panduan lengkap maklumat pekerja</li>
                                <li>Cari staf mengikut nama, no. kp atau jawatan</li>
                                <li>Hubungi staf melalui emel atau telefon</li>
                                <li>Filter mengikut bahagian atau gred</li>
                            </ul>

                            <h6 class="mt-3 mb-2">Tips Pencarian:</h6>
                            <ul>
                                <li>Gunakan kata kunci pendek untuk hasil lebih banyak</li>
                                <li>Imbas barcode di kad staf untuk hubungi dengan cepat</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- KALENDAR -->
                <div class="tab-pane fade" id="calendar" role="tabpanel">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Kalendar Hari Lahir</h5>
                            
                            <h6 class="mt-3 mb-2">Fungsi Kalendar:</h6>
                            <ul>
                                <li>Paparan hari lahir semua staf dalam bentuk kalendar</li>
                                <li>Lihat nama staf yang berulang tahun pada hari tertentu</li>
                                <li>Klik pada nama staf untuk lihat maklumat terperinci</li>
                                <li>Berlaku untuk 15 tahun ke hadapan</li>
                            </ul>

                            <h6 class="mt-3 mb-2">Cara Menggunakan:</h6>
                            <ol>
                                <li>Navigasi bulan menggunakan tombol panah</li>
                                <li>Klik pada tarikh untuk lihat staf yang berulang tahun</li>
                                <li>Klik nama staf untuk lihat profil lengkap</li>
                                <li>Gunakan untuk perancangan majlis atau ucapan selamat</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <!-- CHAT -->
                <div class="tab-pane fade" id="chat" role="tabpanel">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Bantuan melalui Chat Bot</h5>
                            
                            <h6 class="mt-3 mb-2">Tentang Bot Mawar:</h6>
                            <ul>
                                <li>Bot bantu digital yang siap membantu 24/7</li>
                                <li>Boleh bertanya perkara mengenai MyApps</li>
                                <li>Dapat menjawab soalan umum dengan cepat</li>
                            </ul>

                            <h6 class="mt-3 mb-2">Cara Menggunakan Chat:</h6>
                            <ol>
                                <li>Klik ikon Mawar di sudut kanan bawah</li>
                                <li>Buka tetingkap chat</li>
                                <li>Taip soalan anda di kotak input</li>
                                <li>Tekan Enter atau klik butang kirim</li>
                                <li>Tunggu respons dari bot</li>
                            </ol>

                            <h6 class="mt-3 mb-2">Contoh Soalan:</h6>
                            <ul>
                                <li>"Apa itu direktori?"</li>
                                <li>"Bagaimana cara cari staf?"</li>
                                <li>"Berapa aplikasi ada?"</li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- SIDEBAR - FAQ -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-question-circle me-2"></i>Soalan Lazim (FAQ)</h6>
                </div>
                <div class="card-body">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    Bagaimana cara login?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Gunakan no. KP dan kata laluan anda untuk login di halaman awal.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    Lupa kata laluan, apa buat?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Klik "Lupa Kata Laluan" di halaman login, kemudian ikut langkah reset melalui emel.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    Boleh pasang PWA di telefon?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Ya! Klik butang "Install App" di bahagian atas, atau gunakan menu "Add to Home Screen" di browser telefon.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                    Apa fungsi direktori?
                                </button>
                            </h2>
                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Direktori memudahkan anda mencari maklumat aplikasi dan staf dengan pantas melalui carian atau filter.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                    Boleh tukar profil saya?
                                </button>
                            </h2>
                            <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Ya, klik butang "Profil" untuk mengemaskini maklumat peribadi anda.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Support -->
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-body">
                    <h6 class="card-title mb-3"><i class="fas fa-headset me-2 text-info"></i>Hubungi Sokongan</h6>
                    <p class="small mb-2"><strong>Emel:</strong> <a href="mailto:support@keda.gov.my">support@keda.gov.my</a></p>
                    <p class="small mb-2"><strong>Telefon:</strong> <a href="tel:+60123456789">+60 1 2345 6789</a></p>
                    <p class="small"><strong>Jam Operasi:</strong> Isnin - Jumaat, 8:00 AM - 5:00 PM</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
</script>

<?php 
if(file_exists('footer.php')) { 
    include 'footer.php'; 
} 
?>
