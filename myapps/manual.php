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
                            <a class="nav-link" id="dashboard-tab" data-bs-toggle="tab" href="#dashboard" role="tab">
                                <i class="fas fa-chart-line fa-lg text-primary me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="pengurusan-tab" data-bs-toggle="tab" href="#pengurusan" role="tab">
                                <i class="fas fa-cogs fa-lg text-warning me-2"></i>Pengurusan
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="faq-tab" data-bs-toggle="tab" href="#faq" role="tab">
                                <i class="fas fa-question-circle fa-lg text-info me-2"></i>Soalan Lazim (FAQ)
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="contact-tab" data-bs-toggle="tab" href="#contact" role="tab">
                                <i class="fas fa-headset fa-lg text-danger me-2"></i>Khidmat Sokongan
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
                                <li><strong>📊 Dashboard Aplikasi</strong> - Statistik dan senarai aplikasi (Dalaman, Luaran, Gunasama)</li>
                                <li><strong>👥 Dashboard Perjawatan</strong> - Statistik staf, direktori staf, dan kalendar hari lahir</li>
                                <li><strong>🗺️ Dashboard Pencapaian</strong> - Visualisasi data geospatial dengan peta dan graf</li>
                                <li><strong>📁 Pengurusan Rekod Dashboard</strong> - Upload/download data GeoJSON/Excel dan semak GPS</li>
                                <li><strong>🔐 Pengurusan RBAC</strong> - Pengurusan akses pengguna dan peranan (Admin sahaja)</li>
                                <li><strong>💬 Chat Bot Mawar</strong> - Bantuan digital 24/7</li>
                                <li><strong>📱 MyApps Mobile</strong> - Aplikasi dapat dipasang di peranti mudah alih (PWA)</li>
                            </ul>

                            <h6 class="mt-4 mb-2">Untuk Memulakan:</h6>
                            <ol>
                                <li>Login dengan No. KP dan kata laluan anda</li>
                                <li>Pilih menu dashboard mengikut keperluan</li>
                                <li>Klik pada kad statistik untuk lihat detail</li>
                                <li>Gunakan carian dan filter untuk cari maklumat</li>
                                <li>Export data ke Excel jika perlu</li>
                            </ol>
                            
                            <div class="alert alert-info mt-4">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Nota:</strong> Sesetengah fungsi hanya boleh diakses oleh Admin. Jika anda tidak dapat mengakses sesuatu menu, sila hubungi Unit Teknologi Maklumat.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- DASHBOARD -->
                <div class="tab-pane fade" id="dashboard" role="tabpanel">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body pt-4">
                            <h5 class="card-title mb-4"><i class="fas fa-chart-line text-primary me-2"></i>Dashboard MyApps KEDA</h5>
                            
                            <div class="mb-4">
                                <h6 class="mb-3"><i class="fas fa-mobile-alt text-info me-2"></i>1. Dashboard Aplikasi</h6>
                                <p>Paparan statistik dan senarai aplikasi KEDA mengikut kategori.</p>
                                <ul>
                                    <li><strong>Kad Statistik:</strong> Jumlah aplikasi mengikut kategori (Semua, Dalaman, Luaran, Gunasama)</li>
                                    <li><strong>Senarai Aplikasi:</strong> Klik kad "Senarai Aplikasi" untuk lihat senarai lengkap</li>
                                    <li><strong>Fungsi:</strong>
                                        <ul>
                                            <li>Tab filter: Semua, Dalaman, Luaran, Gunasama</li>
                                            <li>Carian: Cari aplikasi mengikut nama atau keterangan</li>
                                            <li>Sort: Susun mengikut kolum (BIL, APLIKASI, KATEGORI, dll)</li>
                                            <li>Export Excel: Muat turun senarai aplikasi</li>
                                            <li>Tambah Aplikasi: Admin boleh tambah aplikasi baharu</li>
                                            <li>Padam: Admin boleh padam aplikasi</li>
                                        </ul>
                                    </li>
                                </ul>
                            </div>
                            
                            <div class="mb-4">
                                <h6 class="mb-3"><i class="fas fa-users text-success me-2"></i>2. Dashboard Perjawatan</h6>
                                <p>Statistik staf, direktori staf, dan kalendar hari lahir.</p>
                                <ul>
                                    <li><strong>Kad Statistik:</strong>
                                        <ul>
                                            <li>STAF - Jumlah keseluruhan staf</li>
                                            <li>JAWATAN - Bilangan jawatan</li>
                                            <li>BAHAGIAN - Bilangan bahagian</li>
                                            <li>HARI LAHIR (BULAN INI) - Staf yang berulang tahun bulan ini</li>
                                            <li>PENGURUSAN TERTINGGI - Staf gred ≥15</li>
                                            <li>PENGURUSAN & PROFESIONAL - Staf gred 9-14</li>
                                            <li>SOKONGAN 1 - Staf gred 5-8</li>
                                            <li>SOKONGAN 2 - Staf gred 1-4</li>
                                        </ul>
                                    </li>
                                    <li><strong>Graf Bahagian:</strong> Klik kad "Bahagian" untuk lihat graf bar dan donut</li>
                                    <li><strong>Graf Jawatan:</strong> Klik kad "Jawatan" untuk lihat graf bar dan donut</li>
                                    <li><strong>Direktori Staf:</strong> Klik kad "Staf" untuk lihat senarai lengkap staf
                                        <ul>
                                            <li>Tab: Masih Bekerja, Bersara, Berhenti (dengan count)</li>
                                            <li>Carian: Cari mengikut nama, jawatan, atau bahagian</li>
                                            <li>Sort: Susun mengikut kolum</li>
                                            <li>Export Excel: Muat turun senarai staf (filtered by tab)</li>
                                            <li>Klik nama: Lihat detail staf dengan gambar</li>
                                        </ul>
                                    </li>
                                    <li><strong>Kalendar Hari Lahir:</strong> Klik kad "Hari Lahir (Bulan Ini)" untuk lihat kalendar</li>
                                    <li><strong>Graf Kategori:</strong> Klik kad kategori (Tertinggi, Pengurusan, Sokongan 1/2) untuk lihat graf Bahagian dan Jawatan</li>
                                </ul>
                            </div>
                            
                            <div class="mb-4">
                                <h6 class="mb-3"><i class="fas fa-map-marked-alt text-danger me-2"></i>3. Dashboard Pencapaian</h6>
                                <p>Visualisasi data geospatial dengan peta interaktif dan graf.</p>
                                <ul>
                                    <li><strong>Kad Statistik:</strong> 10 kategori data (Desa KEDA, Bantuan Pertanian, Bantuan Komuniti, dll)</li>
                                    <li><strong>Peta Interaktif:</strong>
                                        <ul>
                                            <li>Zoom in/out dengan butang +/-</li>
                                            <li>Reset untuk kembali ke view asal</li>
                                            <li>Fullscreen untuk paparan penuh</li>
                                            <li>Klik marker untuk lihat maklumat rekod</li>
                                        </ul>
                                    </li>
                                    <li><strong>Graf Donut:</strong>
                                        <ul>
                                            <li>Pilih jenis: Daerah, Parlimen, atau DUN</li>
                                            <li>Klik segment untuk filter rekod</li>
                                            <li>Fullscreen untuk paparan penuh</li>
                                            <li>Legend menunjukkan jumlah dan peratusan</li>
                                        </ul>
                                    </li>
                                    <li><strong>Senarai Rekod Detail:</strong>
                                        <ul>
                                            <li>Pagination: 20 rekod per halaman</li>
                                            <li>Sort: Klik header kolum untuk susun</li>
                                            <li>Filter: Cari mengikut mana-mana field</li>
                                            <li>Export Excel: Muat turun rekod yang dipaparkan</li>
                                        </ul>
                                    </li>
                                </ul>
                            </div>
                            
                            <div class="alert alert-warning mt-4">
                                <i class="fas fa-lightbulb me-2"></i>
                                <strong>Tips:</strong> Klik pada mana-mana kad statistik untuk melihat detail dan graf. Gunakan butang fullscreen untuk paparan yang lebih besar.
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- PENGURUSAN -->
                <div class="tab-pane fade" id="pengurusan" role="tabpanel">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body pt-4">
                            <h5 class="card-title mb-4"><i class="fas fa-cogs text-warning me-2"></i>Pengurusan & Pentadbiran</h5>
                            
                            <div class="mb-4">
                                <h6 class="mb-3"><i class="fas fa-file-alt text-primary me-2"></i>1. Pengurusan Rekod Dashboard</h6>
                                <p class="text-muted"><strong>Akses:</strong> Admin sahaja</p>
                                <p>Menguruskan data geospatial untuk Dashboard Pencapaian.</p>
                                
                                <h6 class="mt-3 mb-2">Muat Turun Rekod:</h6>
                                <ol>
                                    <li>Pilih Nama Dashboard (salah satu daripada 10 kategori)</li>
                                    <li>Pilih Format (GeoJSON atau Excel)</li>
                                    <li>Klik "Muat Turun File"</li>
                                    <li>File akan dimuat turun dengan nama kategori</li>
                                </ol>
                                
                                <h6 class="mt-3 mb-2">Muat Naik Rekod:</h6>
                                <ol>
                                    <li>Pilih Nama Dashboard (wajib)</li>
                                    <li>Pilih File (GeoJSON atau Excel)</li>
                                    <li>Klik "Upload File"</li>
                                    <li>Sistem akan import rekod baharu (skip duplikat)</li>
                                </ol>
                                
                                <h6 class="mt-3 mb-2">Format File:</h6>
                                <ul>
                                    <li><strong>Excel (.xlsx, .xls):</strong> Disyorkan untuk user biasa
                                        <ul>
                                            <li>Kolum wajib: <code>Name</code> dan <code>WKT</code></li>
                                            <li>Kolum tambahan akan disimpan sebagai properties</li>
                                            <li>Format WKT: <code>POINT(100.5 5.5)</code> atau <code>POLYGON((100 5, 101 5, 101 6, 100 6, 100 5))</code></li>
                                        </ul>
                                    </li>
                                    <li><strong>GeoJSON (.geojson, .json):</strong> Untuk advanced users
                                        <ul>
                                            <li>Format standard FeatureCollection</li>
                                            <li>Setiap feature mesti ada geometry dan properties</li>
                                        </ul>
                                    </li>
                                </ul>
                                
                                <h6 class="mt-3 mb-2">Semak & Betulkan Rekod GPS:</h6>
                                <ul>
                                    <li>Pilih kategori (pilihan) atau biarkan kosong untuk semak semua</li>
                                    <li>Tandakan "Termasuk rekod tanpa GPS" jika perlu</li>
                                    <li>Klik "Semak Rekod" untuk cari rekod luar sempadan Kedah</li>
                                    <li>Pilih rekod dan gunakan tindakan:
                                        <ul>
                                            <li><strong>Padam:</strong> Padam rekod terpilih</li>
                                            <li><strong>Tandakan Tidak Sah:</strong> Tandakan rekod sebagai tidak sah</li>
                                            <li><strong>Betulkan GPS dari Alamat:</strong> Geocode alamat untuk dapatkan GPS baru</li>
                                        </ul>
                                    </li>
                                    <li>Klik "Semak Tiada Rekod Alamat" untuk cari rekod tiada DAERAH/PARLIMEN/DUN</li>
                                    <li>Gunakan "Betulkan Rekod Terpilih" untuk isi maklumat lokasi menggunakan reverse geocoding</li>
                                </ul>
                            </div>
                            
                            <div class="mb-4">
                                <h6 class="mb-3"><i class="fas fa-user-shield text-danger me-2"></i>2. Pengurusan RBAC</h6>
                                <p class="text-muted"><strong>Akses:</strong> Admin sahaja</p>
                                <p>Menguruskan akses pengguna, peranan (roles), dan kebenaran (permissions).</p>
                                
                                <h6 class="mt-3 mb-2">Fungsi Utama:</h6>
                                <ul>
                                    <li><strong>Pengurusan Peranan:</strong> Tambah/edit/padam peranan (Role)</li>
                                    <li><strong>Pengurusan Kebenaran:</strong> Tambah/edit/padam kebenaran (Permission)</li>
                                    <li><strong>Pengurusan Akses Pengguna:</strong> Berikan peranan kepada pengguna</li>
                                    <li><strong>Pengurusan Akses Aplikasi:</strong> Tentukan aplikasi yang boleh diakses oleh peranan</li>
                                </ul>
                                
                                <h6 class="mt-3 mb-2">Cara Menggunakan:</h6>
                                <ol>
                                    <li>Pilih tab yang sesuai (Roles, Permissions, User Roles, Application Access)</li>
                                    <li>Gunakan butang "Tambah" untuk tambah rekod baharu</li>
                                    <li>Klik ikon edit untuk kemaskini rekod</li>
                                    <li>Klik ikon padam untuk padam rekod (dengan pengesahan)</li>
                                </ol>
                            </div>
                            
                            <div class="alert alert-danger mt-4">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Penting:</strong> Fungsi pengurusan hanya boleh diakses oleh Admin. Pastikan anda memahami kesan setiap perubahan sebelum menyimpan.
                            </div>
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
                                    Ya, klik butang "Profil" di header untuk mengemaskini maklumat peribadi anda seperti emel, telefon, dan gambar profil.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
                                    Bagaimana cara export data ke Excel?
                                </button>
                            </h2>
                            <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <strong>Dashboard Aplikasi:</strong> Klik kad "Senarai Aplikasi", kemudian klik butang "Export Excel". File akan dimuat turun dengan data mengikut tab yang aktif (Semua, Dalaman, Luaran, atau Gunasama).<br><br>
                                    <strong>Dashboard Perjawatan:</strong> Klik kad "Staf", pilih tab status (Masih Bekerja, Bersara, atau Berhenti), kemudian klik butang "Export Excel". File akan dimuat turun dengan data mengikut tab yang dipilih.<br><br>
                                    <strong>Dashboard Pencapaian:</strong> Di bahagian "Rekod Detail", klik butang "Export Excel" untuk muat turun rekod yang sedang dipaparkan.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq7">
                                    Kenapa graf tidak keluar selepas klik kad?
                                </button>
                            </h2>
                            <div id="faq7" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Pastikan anda klik pada kad statistik dengan betul. Kad akan membesar dan ada shadow apabila aktif. Jika graf masih tidak keluar, cuba refresh halaman atau semak console browser (F12) untuk ralat.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq8">
                                    Bagaimana cara tambah/edit aplikasi atau staf?
                                </button>
                            </h2>
                            <div id="faq8" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <strong>Aplikasi:</strong> Hanya Admin boleh tambah/edit. Klik kad "Senarai Aplikasi" di Dashboard Aplikasi, kemudian klik butang "Tambah Aplikasi". Untuk edit, klik ikon edit di kolum TINDAKAN.<br><br>
                                    <strong>Staf:</strong> Hanya Admin boleh tambah staf baharu. Klik kad "Staf" di Dashboard Perjawatan, kemudian klik butang "Tambah Staf". Untuk edit, klik ikon edit di kolum TINDAKAN. User biasa hanya boleh edit profil sendiri dengan akses terhad.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq9">
                                    Apa maksud "Tiada Data" dalam graf Dashboard Pencapaian?
                                </button>
                            </h2>
                            <div id="faq9" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    "Tiada Data" bermaksud rekod tersebut tidak mempunyai maklumat lokasi (DAERAH, PARLIMEN, atau DUN). Untuk mengisi data ini, Admin boleh:
                                    <ol>
                                        <li>Pergi ke "Pengurusan Rekod Dashboard"</li>
                                        <li>Klik "Semak Tiada Rekod Alamat"</li>
                                        <li>Pilih rekod yang ada GPS</li>
                                        <li>Klik "Betulkan Rekod Terpilih" untuk isi maklumat lokasi menggunakan reverse geocoding</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq10">
                                    Bagaimana cara upload data ke Dashboard Pencapaian?
                                </button>
                            </h2>
                            <div id="faq10" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <ol>
                                        <li>Pergi ke menu "Pengurusan Rekod Dashboard" (Admin sahaja)</li>
                                        <li>Pilih Nama Dashboard (contoh: "Desa KEDA")</li>
                                        <li>Pilih File (Excel atau GeoJSON)</li>
                                        <li>Klik "Upload File"</li>
                                        <li>Sistem akan import rekod baharu (skip duplikat secara automatik)</li>
                                    </ol>
                                    <strong>Format Excel:</strong> Kolum wajib adalah <code>Name</code> dan <code>WKT</code>. Kolum tambahan akan disimpan sebagai properties.
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
