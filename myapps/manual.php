<?php
require 'db.php';
include 'header.php';

// Get language
$lang = isset($_GET['lang']) ? $_GET['lang'] : (isset($_SESSION['lang']) ? $_SESSION['lang'] : 'ms');
?>

<style>
    /* Tab Styling - Sepadankan dengan RBAC Management */
    .nav-tabs .nav-link {
        color: #000000;
        border: none;
        border-bottom: 3px solid transparent;
        padding: 12px 16px;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .nav-tabs .nav-link:hover {
        color: #0d6efd;
        border-bottom-color: #0d6efd;
    }
    
    .nav-tabs .nav-link.active {
        color: #0d6efd;
        border-bottom-color: #0d6efd;
        background-color: transparent;
    }
</style>

<div class="container-fluid">
    <h3 class="mb-4 fw-bold text-dark"><i class="fas fa-book-open me-3 text-primary"></i>Manual Pengguna</h3>

    <!-- Content Sections -->
    <div class="row">
        <div class="col-12">
            <!-- Tab Navigation -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <ul class="nav nav-tabs" id="manualTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" id="home-tab" data-bs-toggle="tab" href="#home" role="tab">
                                <i class="fas fa-home fa-lg text-success me-2"></i>Pendahuluan
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="faq-tab" data-bs-toggle="tab" href="#faq" role="tab">
                                <i class="fas fa-question-circle fa-lg text-warning me-2"></i>Soalan Lazim (FAQ)
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="dashboard-tab" data-bs-toggle="tab" href="#dashboard" role="tab">
                                <i class="fas fa-chart-line fa-lg text-primary me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="direktori-tab" data-bs-toggle="tab" href="#direktori" role="tab">
                                <i class="fas fa-list fa-lg text-info me-2"></i>Direktori
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="calendar-tab" data-bs-toggle="tab" href="#calendar" role="tab">
                                <i class="fas fa-calendar fa-lg text-danger me-2"></i>Kalendar
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="chat-tab" data-bs-toggle="tab" href="#chat" role="tab">
                                <i class="fas fa-comments fa-lg text-success me-2"></i>Chat
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="contact-tab" data-bs-toggle="tab" href="#contact" role="tab">
                                <i class="fas fa-headset fa-lg text-primary me-2"></i>Khidmat Sokongan
                            </a>
                        </li>
                    </ul>

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
                                <li>📱 MyApps Mobile - Aplikasi dapat dipasang di peranti mudah alih</li>
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
                        <div class="card-body pt-4">
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
                                <li>Boleh pilih paparan ikut bulan, hari atau senarai hari lahir</li>
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

                <!-- FAQ TAB -->
                <div class="tab-pane fade" id="faq" role="tabpanel">
                    <div class="card border-0 shadow-sm pt-4">
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
                                    Cara pasang MyApps Mobile di telefon
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <b>Android (Google Chrome):</b><br>
                                    1. Buka laman MyApps di Google Chrome.<br>
                                    2. Tekan ikon menu (tiga titik di penjuru atas kanan).<br>
                                    3. Pilih <b>Add to Home screen</b> atau <b>Install app</b>.<br>
                                    4. Ikon MyApps akan muncul di skrin utama telefon anda.<br><br>
                                    <b>iPhone (Safari):</b><br>
                                    1. Buka laman MyApps di Safari.<br>
                                    2. Tekan ikon <b>Share</b> (petak dengan anak panah ke atas) di bawah.<br>
                                    3. Pilih <b>Add to Home Screen</b>.<br>
                                    4. Ikon MyApps akan muncul di skrin utama iPhone anda.<br><br>
                                    <i>Nota: Pastikan anda menggunakan browser yang disokong (Chrome untuk Android, Safari untuk iPhone).</i>
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
                </div>

                <!-- CONTACT TAB -->
                <div class="tab-pane fade" id="contact" role="tabpanel">
                    <div class="card border-0 shadow-sm pt-4">
                        <div class="card-body">
                            <h5 class="card-title mb-4"><i class="fas fa-headset me-2 text-info"></i>Khidmat Sokongan</h5>
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <p class="text-dark mb-3">Jika ada sebarang masalah, sila hubungi Unit Teknologi Maklumat (UTM) melalui cara berikut :</p>
                                    <p class="small mb-2"><strong>Emel:</strong></p>
                                    <p class="small mb-3"><a href="mailto:support@keda.gov.my">utm@keda.gov.my</a></p>
                                    <p class="small mb-2"><strong>Telefon:</strong></p>
                                    <p class="small mb-4"><a href="tel:+60123456789">+604-7205300</a></p>
                                    <p class="small mb-2"><strong>Waktu Operasi :</strong></p>
                                    <p class="small mb-1"><strong>Ahad - Rabu</strong></p>
                                    <p class="small">8:30 AM - 5:00 PM</p>
                                    <p class="small mb-1"><strong>Khamis</strong></p>
                                    <p class="small">8:30 AM - 3:30 PM</p>
                                    <p class="small mb-1"><strong>Jumaat dan Sabtu</strong></p>
                                    <p class="small">Cuti</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php 
if(file_exists('footer.php')) { 
    include 'footer.php'; 
} 
?>
