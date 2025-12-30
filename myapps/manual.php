<?php
require 'db.php';
include 'header.php';
?>

<div class="container-fluid">
    <h3 class="mb-4 fw-bold text-dark">
        <i class="fas fa-book-open me-3 text-primary"></i>Manual Pengguna
    </h3>

    <!-- Tabs Navigation -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="umum-tab" data-bs-toggle="tab" href="#umum" role="tab">Umum</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="login-tab" data-bs-toggle="tab" href="#login" role="tab">Log Masuk & Akses</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="aplikasi-tab" data-bs-toggle="tab" href="#aplikasi" role="tab">Aplikasi</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="direktori-tab" data-bs-toggle="tab" href="#direktori" role="tab">Direktori</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="dashboard-tab" data-bs-toggle="tab" href="#dashboard" role="tab">Dashboard</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="fitur-tab" data-bs-toggle="tab" href="#fitur" role="tab">Fungsi Lain</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="sokongan-tab" data-bs-toggle="tab" href="#sokongan" role="tab">Sokongan</a>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content">
        <!-- UMUM -->
        <div class="tab-pane fade show active" id="umum" role="tabpanel">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Apa itu MyApps?</h5>
                </div>
                <div class="card-body">
                    <p>MyApps adalah portal aplikasi terintegrasi KEDA yang memudahkan staf mengakses semua aplikasi yang diperlukan dalam satu tempat. Dengan MyApps, anda tidak perlu lagi mengingat pelbagai URL dan kata laluan untuk aplikasi yang berbeza.</p>
                    <h6 class="fw-bold mt-3">Kelebihan MyApps:</h6>
                    <ul>
                        <li>✓ Akses semua aplikasi dari satu portal</li>
                        <li>✓ Sistem login yang selamat dan mudah</li>
                        <li>✓ Sokongan SSO (Single Sign-On) untuk aplikasi tertentu</li>
                        <li>✓ Dashboard untuk analisis penggunaan</li>
                        <li>✓ Direktori lengkap aplikasi dan staf</li>
                        <li>✓ Chatbot Mawar untuk bantuan 24/7</li>
                    </ul>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-rocket me-2"></i>Memulai dengan MyApps</h5>
                </div>
                <div class="card-body">
                    <ol>
                        <li>Buka halaman login MyApps</li>
                        <li>Masukkan <strong>No. Kad Pengenalan</strong> anda <span class="text-danger">(TANPA "-")</span></li>
                        <li>Masukkan <strong>Kata Laluan</strong> anda</li>
                        <li>Klik <strong>"Log Masuk"</strong></li>
                        <li>Anda akan diarahkan ke Dashboard Aplikasi</li>
                    </ol>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-globe me-2"></i>Browser yang Disokong</h5>
                </div>
                <div class="card-body">
                    <p>MyApps boleh digunakan di semua browser modern:</p>
                    <div class="row">
                        <div class="col-md-6">
                            <p><i class="fab fa-chrome text-warning"></i> <strong>Google Chrome</strong> (versi 90+)</p>
                            <p><i class="fab fa-firefox text-danger"></i> <strong>Mozilla Firefox</strong> (versi 88+)</p>
                        </div>
                        <div class="col-md-6">
                            <p><i class="fab fa-safari text-info"></i> <strong>Safari</strong> (versi 14+)</p>
                            <p><i class="fab fa-edge text-primary"></i> <strong>Microsoft Edge</strong> (versi 90+)</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- LOG MASUK & AKSES -->
        <div class="tab-pane fade" id="login" role="tabpanel">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-sign-in-alt me-2"></i>Cara Log Masuk</h5>
                </div>
                <div class="card-body">
                    <ol>
                        <li>Masukkan <strong>No. Kad Pengenalan (IC)</strong> anda <span class="text-danger">(TANPA "-")</span></li>
                        <li>Masukkan <strong>Kata Laluan</strong> anda</li>
                        <li>Klik butang <strong>"Log Masuk"</strong></li>
                    </ol>
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-lightbulb me-2"></i> <strong>Tip:</strong> Untuk kali pertama Log Masuk, sila klik pada Pertama Kali Log Masuk
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-lock me-2"></i>Lupa Kata Laluan?</h5>
                </div>
                <div class="card-body">
                    <ol>
                        <li>Klik <strong>"Lupa Kata Laluan?"</strong> di halaman Log Masuk</li>
                        <li>Masukkan <strong>No. Kad Pengenalan</strong> anda <span class="text-danger">(TANPA "-")</span></li>
                        <li>Sistem akan menghantar <strong>OTP (One-Time Password)</strong> ke e-mel anda</li>
                        <li>Masukkan OTP yang diterima</li>
                        <li>Tetapkan <strong>kata laluan baru</strong> anda</li>
                        <li>Klik <strong>"Tetapkan"</strong> dan login dengan kata laluan baru</li>
                    </ol>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-key me-2"></i>Tukar Kata Laluan</h5>
                </div>
                <div class="card-body">
                    <ol>
                        <li>Selepas Log Masuk, cari menu <strong>"Tukar Kata Laluan"</strong></li>
                        <li>Masukkan <strong>kata laluan lama</strong> anda</li>
                        <li>Masukkan <strong>kata laluan baru</strong> anda</li>
                        <li>Sahkan <strong>kata laluan baru</strong> sekali lagi</li>
                        <li>Klik <strong>"Simpan"</strong></li>
                    </ol>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-sign-out-alt me-2"></i>Logout</h5>
                </div>
                <div class="card-body">
                    <ol>
                        <li>Klik <strong>ikon profil</strong> di sudut kanan atas</li>
                        <li>Pilih <strong>"Log Keluar"</strong> dari menu</li>
                        <li>Anda akan dikembalikan ke halaman login</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- APLIKASI -->
        <div class="tab-pane fade" id="aplikasi" role="tabpanel">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-folder-open me-2"></i>Direktori Aplikasi</h5>
                </div>
                <div class="card-body">
                    <p>Direktori Aplikasi menunjukkan semua aplikasi yang tersedia dalam MyApps dengan penerangan lengkap, kategori, dan akses.</p>
                    <h6 class="fw-bold mt-3">Cara Membuka:</h6>
                    <ol>
                        <li>Klik <strong>"Direktori Aplikasi"</strong> di menu sidebar</li>
                        <li>Lihat senarai semua aplikasi yang tersedia</li>
                    </ol>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-search me-2"></i>Mencari Aplikasi</h5>
                </div>
                <div class="card-body">
                    <h6 class="fw-bold">Kaedah 1: Carian Teks</h6>
                    <ol>
                        <li>Masukkan nama aplikasi di kotak carian</li>
                        <li>Sistem akan menapis senarai secara real-time</li>
                    </ol>
                    <h6 class="fw-bold mt-3">Kaedah 2: Filter Kategori</h6>
                    <ol>
                        <li>Klik butang kategori untuk menapis</li>
                        <li>Senarai akan ditapis mengikut kategori yang dipilih</li>
                    </ol>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-network-wired me-2"></i>Apa itu SSO?</h5>
                </div>
                <div class="card-body">
                    <p><strong>SSO (Single Sign-On)</strong> membolehkan anda login sekali ke MyApps dan mengakses pelbagai aplikasi tanpa perlu login berulang kali.</p>
                    <p>Lihat Direktori Aplikasi untuk melihat aplikasi mana yang mempunyai badge <span class="badge bg-success">✓ SSO</span>.</p>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-external-link-alt me-2"></i>Membuka Aplikasi</h5>
                </div>
                <div class="card-body">
                    <ol>
                        <li>Cari aplikasi di Direktori Aplikasi</li>
                        <li>Klik butang <strong>"Buka"</strong> untuk aplikasi aktif</li>
                        <li>Aplikasi akan membuka dalam tab baru</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- DIREKTORI -->
        <div class="tab-pane fade" id="direktori" role="tabpanel">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0"><i class="fas fa-users me-2"></i>Direktori Staf</h5>
                </div>
                <div class="card-body">
                    <p>Direktori Staf menunjukkan maklumat semua kakitangan KEDA dengan nama, jawatan, gred, dan bahagian.</p>
                    <h6 class="fw-bold mt-3">Cara Menggunakan:</h6>
                    <ol>
                        <li>Klik <strong>"Direktori Staf"</strong> di menu sidebar</li>
                        <li>Gunakan kotak carian untuk mencari staf</li>
                        <li>Klik butang edit untuk mengubah maklumat (jika anda admin)</li>
                    </ol>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0"><i class="fas fa-file-export me-2"></i>Mengeksport Data</h5>
                </div>
                <div class="card-body">
                    <ol>
                        <li>Buka Direktori Aplikasi atau Direktori Staf</li>
                        <li>Klik butang <strong>"Export Excel"</strong> (berwarna hijau)</li>
                        <li>File Excel akan dimuat turun secara automatik</li>
                    </ol>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0"><i class="fas fa-sort me-2"></i>Menyusun Data</h5>
                </div>
                <div class="card-body">
                    <ol>
                        <li>Klik pada tajuk lajur untuk menyusun</li>
                        <li>Klik sekali untuk A-Z atau angka kecil ke besar</li>
                        <li>Klik dua kali untuk Z-A atau angka besar ke kecil</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- DASHBOARD -->
        <div class="tab-pane fade" id="dashboard" role="tabpanel">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Dashboard Aplikasi</h5>
                </div>
                <div class="card-body">
                    <p>Dashboard Aplikasi menunjukkan statistik dan analisis penggunaan aplikasi.</p>
                    <h6 class="fw-bold mt-3">Informasi yang Ditampilkan:</h6>
                    <ul>
                        <li><strong>Jumlah Aplikasi:</strong> Jumlah total aplikasi aktif</li>
                        <li><strong>Aplikasi Dalaman:</strong> Aplikasi akses dalaman sahaja</li>
                        <li><strong>Aplikasi Luaran:</strong> Aplikasi akses luar rangkaian</li>
                        <li><strong>Aplikasi Gunasama:</strong> Aplikasi berkongsi dengan organisasi lain</li>
                        <li><strong>Grafik Kategori:</strong> Visualisasi pengagihan aplikasi</li>
                        <li><strong>Peratus Kategori:</strong> Peratusan setiap kategori</li>
                    </ul>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Dashboard Staf</h5>
                </div>
                <div class="card-body">
                    <p>Dashboard Staf menunjukkan statistik kakitangan KEDA dengan grafik dan analisis.</p>
                </div>
            </div>
        </div>

        <!-- FITUR LAIN -->
        <div class="tab-pane fade" id="fitur" role="tabpanel">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Kalendar Hari Jadi</h5>
                </div>
                <div class="card-body">
                    <ol>
                        <li>Klik <strong>"Kalendar Hari Jadi"</strong> di menu sidebar</li>
                        <li>Lihat senarai staf yang berhari jadi dalam bulan ini</li>
                    </ol>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-robot me-2"></i>Chatbot Mawar</h5>
                </div>
                <div class="card-body">
                    <p>Mawar adalah chatbot AI yang tersedia 24/7 untuk menjawab pertanyaan tentang MyApps.</p>
                    <h6 class="fw-bold mt-3">Cara Menggunakan:</h6>
                    <ol>
                        <li>Klik butang Mawar di sudut bawah kanan layar</li>
                        <li>Tanya pertanyaan anda <strong>dalam Bahasa Melayu</strong></li>
                        <li>Mawar akan memberikan jawapan berdasarkan FAQ database</li>
                    </ol>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-user-circle me-2"></i>Profil Pengguna</h5>
                </div>
                <div class="card-body">
                    <ol>
                        <li>Klik ikon profil di sudut kanan atas</li>
                        <li>Lihat atau ubah maklumat profil anda</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- SOKONGAN -->
        <div class="tab-pane fade" id="sokongan" role="tabpanel">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-life-ring me-2"></i>Dapatkan Bantuan</h5>
                </div>
                <div class="card-body">
                    <h6 class="fw-bold">Kaedah 1: Tanya Chatbot Mawar</h6>
                    <p>Tanya pertanyaan kepada Mawar di chat window untuk jawapan segera.</p>
                    <h6 class="fw-bold mt-3">Kaedah 2: Hubungi Unit Teknologi Maklumat (UTM)</h6>
                    <ul>
                        <li><strong>Email:</strong> utm@keda.gov.my</li>
                        <li><strong>Telefon:</strong> 04-7205300</li>
                        <li><strong>Jam Operasi:</strong> Ahad - Khamis, 9:00 AM - 5:00 PM</li>
                    </ul>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-bug me-2"></i>Lapor Masalah</h5>
                </div>
                <div class="card-body">
                    <p>Jika anda ada sebarang masalah, sila hubungi Unit Teknologi Maklumat (UTM) dengan menyatakan :</p>
                    <ul>
                        <li>Perihal masalah</li>
                        <li>Langkah-langkah penghasilan</li>
                        <li>Screenshot (jika ada)</li>
                        <li>Browser yang digunakan</li>
                    </ul>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Keselamatan Data</h5>
                </div>
                <div class="card-body">
                    <h6 class="fw-bold">Tips Keselamatan:</h6>
                    <ul>
                        <li>✓ Jangan berkongsi kata laluan dengan orang lain</li>
                        <li>✓ Gunakan kata laluan yang kuat</li>
                        <li>✓ Tukar kata laluan setiap 90 hari</li>
                        <li>✓ Logout dari aplikasi apabila selesai</li>
                        <li>✓ Berhati-hati dengan email penggodaman</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .nav-tabs .nav-link.active {
        background-color: #f8f9fa;
        border-bottom: 3px solid #0d6efd;
    }
    .card {
        border-radius: 10px;
    }
    .card-header {
        border-radius: 10px 10px 0 0;
    }
</style>

</body>
</html>
