-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Dec 21, 2025 at 09:33 AM
-- Server version: 5.7.33
-- PHP Version: 8.4.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `myapps`
--

-- --------------------------------------------------------

--
-- Table structure for table `akses`
--

CREATE TABLE `akses` (
  `id_akses` int(11) NOT NULL,
  `id_staf` int(11) NOT NULL,
  `id_aplikasi` int(11) DEFAULT NULL,
  `id_level` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `akses`
--

INSERT INTO `akses` (`id_akses`, `id_staf`, `id_aplikasi`, `id_level`) VALUES
(1, 98, 12, 3),
(2, 11, 12, 3);

-- --------------------------------------------------------

--
-- Table structure for table `aplikasi`
--

CREATE TABLE `aplikasi` (
  `id_aplikasi` int(11) NOT NULL,
  `id_kategori` int(11) DEFAULT NULL,
  `nama_aplikasi` varchar(100) DEFAULT NULL,
  `tarikh_daftar` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `keterangan` text,
  `url` varchar(255) DEFAULT '#',
  `warna_bg` varchar(20) DEFAULT 'bg-white',
  `sso_comply` int(1) DEFAULT '1',
  `status` int(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `aplikasi`
--

INSERT INTO `aplikasi` (`id_aplikasi`, `id_kategori`, `nama_aplikasi`, `tarikh_daftar`, `keterangan`, `url`, `warna_bg`, `sso_comply`, `status`) VALUES
(1, 1, 'eMAS Care', '2025-12-10 06:11:00', 'Sistem Pengurusan Klinik Panel KEDA', 'https://clinic.emastpa.com.my', '#F39C12', 0, 1),
(2, 1, 'ePerjalanan', '2025-12-10 06:11:00', 'Sistem Pengurusan Perjalanan KEDA', 'https://eperjalanan.keda.gov.my', '#F39C12', 1, 1),
(3, 1, 'ePelawat', '2025-12-10 06:11:00', 'Sistem Pengurusan Pelawat KEDA', 'https://epelawat.keda.gov.my', '#F39C12', 1, 1),
(4, 1, 'eTanah', '2025-12-10 06:11:00', 'Sistem Pengurusan Tanah KEDA', 'https://etanah.keda.gov.my', '#F39C12', 1, 1),
(5, 1, 'eUtiliti', '2025-12-10 06:11:00', 'Sistem Pengurusan Bil-Bil KEDA', 'https://utiliti.keda.gov.my', '#F39C12', 1, 1),
(6, 1, 'eCare', '2025-12-11 01:50:00', 'Sistem Pengurusan Klinik Panel KEDA', 'https://ecare.keda.gov.my', '#F39C12', 1, 0),
(7, 1, 'HRMIS', '2025-12-10 06:11:00', 'Sistem Pengurusan Sumber Manusia', 'https://hrmis2.eghrmis.gov.my', '#F39C12', 0, 1),
(8, 1, 'MyGovUC', '2025-12-10 06:11:00', 'Sistem Pengurusan Email (Google Work Space)', 'https://www.mygovuc.gov.my', '#F39C12', 0, 1),
(9, 1, 'MyDaftar', '2025-12-10 06:11:00', 'Sistem Pendaftaran Program dan Acara KEDA', 'https://daftar.keda.gov.my', '#F39C12', 0, 1),
(10, 1, 'KEDAMap', '2025-12-10 06:11:00', 'Sistem Pengurusan Geospatial KEDA', 'https://mygos.mygeoportal.gov.my/KEDA', '#F39C12', 0, 1),
(11, 1, 'MyPPRS', '2025-12-10 09:41:00', 'Sistem Pengurusan Projek Perumahan Rakyat Sejahtera (PPRS) KEDA', 'https://mypprs.keda.gov.my/', '#F39C12', 1, 1),
(12, 1, 'MyApps', '2025-12-10 06:11:00', 'Sistem Pengurusan Aplikasi-Aplikasi KEDA', 'https://myapps.keda.gov.my', '#F39C12', 1, 1),
(13, 1, 'SAGA', '2025-12-10 06:11:00', 'Sistem Pengurusan Kewangan KEDA', 'https://saga.keda.gov.my', '#F39C12', 0, 1),
(14, 1, 'Staff Portal', '2025-12-10 06:11:00', 'Sistem Pengurusan Slip Gaji dan EC Form KEDA', 'https://staffportal.keda.gov.my', '#F39C12', 0, 1),
(15, 1, 'FER', '2025-12-10 06:11:00', 'Sistem Pengurusan Kaunter Kutipan KEDA', 'https://fer.keda.gov.my/fer/login.php', '#F39C12', 0, 1),
(16, 1, 'TMS', '2025-12-10 06:11:00', 'Sistem Pengurusan Sewaan KEDA', 'https://tms.keda.gov.my', '#F39C12', 0, 1),
(17, 1, 'SMP', '2025-12-10 06:11:00', 'Sistem Maklumat Pelajar Kolej KEDA', 'https://smp.kolejkeda.edu.my', '#F39C12', 0, 1),
(18, 1, 'gAsset', '2025-12-10 06:11:00', 'Sistem Pengurusan Aset Alih KEDA', 'https://spas.keda.gov.my', '#F39C12', 0, 1),
(19, 1, 'gStore', '2025-12-10 06:11:00', 'Sistem Pengurusan Stor KEDA', 'https://spas.keda.gov.my', '#F39C12', 0, 1),
(20, 1, 'gFixed', '2025-12-10 06:11:00', 'Sistem Pengurusan Aset Tak Alih KEDA', 'https://spas.keda.gov.my', '#F39C12', 0, 1),
(21, 1, 'gLive', '2025-12-10 06:11:00', 'Sistem Pengurusan Aset Hidup KEDA', 'https://spas.keda.gov.my', '#F39C12', 0, 1),
(22, 1, 'gIntan', '2025-12-10 06:11:00', 'Sistem Pengurusan Aset Tak Ketara KEDA', 'https://spas.keda.gov.my', '#F39C12', 0, 1),
(23, 2, 'eJawatan', '2025-12-10 06:11:00', 'Sistem Permohonan Jawatan Kosong KEDA', 'https://ejawatan.keda.gov.my', '#1ABC9C', 0, 1),
(24, 2, 'SPBK', '2025-12-10 06:11:00', 'Sistem Permohonan Bantuan KEDA', 'https://spbk.keda.gov.my', '#1ABC9C', 0, 1),
(25, 2, 'MyPremis', '2025-12-10 06:11:00', 'Portal Sewaan Premis dan Tanah KEDA', 'https://mypremis.keda.gov.my', '#1ABC9C', 0, 1),
(26, 2, 'KEDAPay', '2025-12-10 06:11:00', 'Portal Pengurusan Penyewa KEDA', 'https://kedapay.keda.gov.my', '#1ABC9C', 0, 1),
(27, 2, 'DaftarKolej', '2025-12-10 06:11:00', 'Sistem Permohonan Masuk Kolej KEDA', 'https://daftar.kolejkeda.edu.my', '#1ABC9C', 0, 1),
(28, 2, 'Portal KEDA', '2025-12-10 06:11:00', 'Portal Laman Web Rasmi KEDA', 'https://keda.gov.my', '#1ABC9C', 0, 1),
(29, 2, 'Portal Kolej KEDA', '2025-12-10 06:11:00', 'Portal Laman Web Rasmi Kolej KEDA', 'https://kolejkeda.edu.my', '#1ABC9C', 0, 1),
(30, 3, 'MyProjek', '2025-12-10 06:11:00', 'Sistem Pengurusan Projek Persekutuan (ICU)', 'https://myprojek.icu.gov.my', '#6C3483', 0, 1),
(31, 3, 'MyMesyuarat', '2025-12-10 06:11:00', 'Sistem Pengurusan Mesyuarat (JDN)', 'https://www.mymesyuarat.gov.my', '#6C3483', 0, 1),
(32, 3, 'eKasih', '2025-12-10 06:11:00', 'Portal Data Kemiskinan Nasional (ICU)', 'https://ekasih2.icu.gov.my', '#6C3483', 0, 1),
(33, 3, 'SISPAA', '2025-12-10 06:11:00', 'Sistem Pengurusan Aduan Awam (BPA)', 'https://rurallink.spab.gov.my', '#6C3483', 0, 1),
(34, 3, 'SBSys', '2025-12-10 06:11:00', 'Sistem Pengurusan Agensi Badan Berkanun Persekutuan (ICU)', 'https://sbsys.icu.gov.my', '#6C3483', 0, 1),
(35, 3, 'MySpike', '2025-12-10 06:11:00', 'Sistem Pengurusan Integrasi Kemahiran Malaysia (JPK)', 'https://www.myspike.my', '#6C3483', 0, 1),
(36, 3, 'ECOS', '2025-12-10 06:11:00', 'Sistem Pengurusan Suruhanjaya Tenaga (ST)', 'https://ecos.st.gov.my', '#6C3483', 0, 1),
(37, 3, 'SPKPN', '2025-12-10 06:11:00', 'Sistem Profil Kampung Peringkat Nasional (KKDW)', 'https://spkpn.ekonomi.gov.my', '#6C3483', 0, 1),
(38, 3, 'DDMS', '2025-12-10 06:11:00', 'Sistem Pengurusan Dokumen Digital (JDN)', 'https://ddms.malaysia.gov.my/', '#6C3483', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `audit`
--

CREATE TABLE `audit` (
  `id_audit` bigint(20) NOT NULL,
  `waktu` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_pengguna` int(11) DEFAULT NULL COMMENT 'id_staf yang lakukan perubahan (perlu di-set dari aplikasi)',
  `tindakan` varchar(10) NOT NULL COMMENT 'cth: INSERT, UPDATE, DELETE',
  `nama_jadual` varchar(50) NOT NULL COMMENT 'Jadual yang diubah, cth: staf',
  `id_rekod` int(11) DEFAULT NULL COMMENT 'Primary Key rekod yang diubah, cth: id_staf',
  `data_lama` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin COMMENT 'Data asal (sebelum diubah)',
  `data_baru` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin COMMENT 'Data baru (selepas diubah)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `audit`
--

INSERT INTO `audit` (`id_audit`, `waktu`, `id_pengguna`, `tindakan`, `nama_jadual`, `id_rekod`, `data_lama`, `data_baru`) VALUES
(1, '2025-11-19 06:34:13', NULL, 'UPDATE', 'staf', 98, '{\"no_staf\": \"006181\", \"no_kp\": \"820426025349\", \"nama\": \"MOHAMAD NOORAZAM BIN JAAFAR\", \"emel\": \"azam@keda.gov.my\", \"telefon\": null, \"id_jawatan\": 7, \"id_gred\": null, \"id_bahagian\": 29, \"id_status\": 1}', '{\"no_staf\": \"006181\", \"no_kp\": \"820426025349\", \"nama\": \"MOHAMAD NOORAZAM BIN JAAFAR\", \"emel\": \"azam@keda.gov.my\", \"telefon\": \"0195793994\", \"id_jawatan\": 7, \"id_gred\": 10, \"id_bahagian\": 29, \"id_status\": 1}'),
(2, '2025-11-19 06:45:36', NULL, 'UPDATE', 'staf', 9, '{\"no_staf\": \"004669\", \"no_kp\": \"690921025239\", \"nama\": \"ADNAN BIN DARUS\", \"emel\": \"adnan@keda.gov.my\", \"telefon\": null, \"id_jawatan\": 1, \"id_gred\": null, \"id_bahagian\": 4, \"id_status\": 1}', '{\"no_staf\": \"004669\", \"no_kp\": \"690921025239\", \"nama\": \"ADNAN BIN DARUS\", \"emel\": \"adnan@keda.gov.my\", \"telefon\": \"\", \"id_jawatan\": 1, \"id_gred\": 12, \"id_bahagian\": 4, \"id_status\": 1}'),
(3, '2025-11-19 06:47:37', NULL, 'UPDATE', 'staf', 9, '{\"no_staf\": \"004669\", \"no_kp\": \"690921025239\", \"nama\": \"ADNAN BIN DARUS\", \"emel\": \"adnan@keda.gov.my\", \"telefon\": \"\", \"id_jawatan\": 1, \"id_gred\": 12, \"id_bahagian\": 4, \"id_status\": 1}', '{\"no_staf\": \"004669\", \"no_kp\": \"690921025239\", \"nama\": \"ADNAN BIN DARUS\", \"emel\": \"adnan@keda.gov.my\", \"telefon\": null, \"id_jawatan\": 1, \"id_gred\": 12, \"id_bahagian\": 4, \"id_status\": 1}'),
(4, '2025-11-19 06:56:22', NULL, 'UPDATE', 'staf', 248, '{\"no_staf\": \"008044\", \"no_kp\": \"920131025243\", \"nama\": \"MOHD SYAFIQ BIN ABDUL RAZAK\", \"emel\": \"mohdsyafiqrazak@keda.gov.my\", \"telefon\": null, \"id_jawatan\": 45, \"id_gred\": null, \"id_bahagian\": 5, \"id_status\": 1}', '{\"no_staf\": \"008044\", \"no_kp\": \"920131025243\", \"nama\": \"MOHD SYAFIQ BIN ABDUL RAZAK\", \"emel\": \"mohdsyafiqrazak@keda.gov.my\", \"telefon\": null, \"id_jawatan\": null, \"id_gred\": null, \"id_bahagian\": 5, \"id_status\": 1}'),
(5, '2025-11-19 06:57:04', NULL, 'UPDATE', 'staf', 248, '{\"no_staf\": \"008044\", \"no_kp\": \"920131025243\", \"nama\": \"MOHD SYAFIQ BIN ABDUL RAZAK\", \"emel\": \"mohdsyafiqrazak@keda.gov.my\", \"telefon\": null, \"id_jawatan\": null, \"id_gred\": null, \"id_bahagian\": 5, \"id_status\": 1}', '{\"no_staf\": \"008044\", \"no_kp\": \"920131025243\", \"nama\": \"MOHD SYAFIQ BIN ABDUL RAZAK\", \"emel\": \"mohdsyafiqrazak@keda.gov.my\", \"telefon\": null, \"id_jawatan\": 44, \"id_gred\": null, \"id_bahagian\": 5, \"id_status\": 1}'),
(6, '2025-11-19 09:11:57', NULL, 'UPDATE', 'staf', 35, '{\"no_staf\": \"005177\", \"no_kp\": \"760526026064\", \"nama\": \"AFIFAH BINTI OMAR\", \"emel\": \"afifah@keda.gov.my\", \"telefon\": null, \"id_jawatan\": 10, \"id_gred\": null, \"id_bahagian\": 20, \"id_status\": 1}', '{\"no_staf\": \"005177\", \"no_kp\": \"760526026064\", \"nama\": \"AFIFAH BINTI OMAR\", \"emel\": \"afifah@keda.gov.my\", \"telefon\": null, \"id_jawatan\": 37, \"id_gred\": 15, \"id_bahagian\": 20, \"id_status\": 1}'),
(7, '2025-11-19 09:18:25', NULL, 'UPDATE', 'staf', 35, '{\"no_staf\": \"005177\", \"no_kp\": \"760526026064\", \"nama\": \"AFIFAH BINTI OMAR\", \"emel\": \"afifah@keda.gov.my\", \"telefon\": null, \"id_jawatan\": 37, \"id_gred\": 15, \"id_bahagian\": 20, \"id_status\": 1}', '{\"no_staf\": \"005177\", \"no_kp\": \"760526026064\", \"nama\": \"AFIFAH BINTI OMAR\", \"emel\": \"afifah@keda.gov.my\", \"telefon\": \"\", \"id_jawatan\": 37, \"id_gred\": 15, \"id_bahagian\": 18, \"id_status\": 1}'),
(8, '2025-11-23 08:01:14', NULL, 'UPDATE', 'staf', 132, '{\"no_staf\": \"006620\", \"no_kp\": \"880312025783\", \"nama\": \"ABDUL AZIZ BIN ABDUL KHONI\", \"emel\": \"azizkhoni@keda.gov.my\", \"telefon\": null, \"id_jawatan\": 26, \"id_gred\": null, \"id_bahagian\": 5, \"id_status\": 1}', '{\"no_staf\": \"006620\", \"no_kp\": \"880312025783\", \"nama\": \"ABDUL AZIZ BIN ABDUL KHONI\", \"emel\": \"azizkhoni@keda.gov.my\", \"telefon\": \"\", \"id_jawatan\": 26, \"id_gred\": 1, \"id_bahagian\": 6, \"id_status\": 1}'),
(9, '2025-11-23 08:01:41', NULL, 'UPDATE', 'staf', 132, '{\"no_staf\": \"006620\", \"no_kp\": \"880312025783\", \"nama\": \"ABDUL AZIZ BIN ABDUL KHONI\", \"emel\": \"azizkhoni@keda.gov.my\", \"telefon\": \"\", \"id_jawatan\": 26, \"id_gred\": 1, \"id_bahagian\": 6, \"id_status\": 1}', '{\"no_staf\": \"006620\", \"no_kp\": \"880312025783\", \"nama\": \"ABDUL AZIZ BIN ABDUL KHONI\", \"emel\": \"azizkhoni@keda.gov.my\", \"telefon\": \"\", \"id_jawatan\": 26, \"id_gred\": 1, \"id_bahagian\": 5, \"id_status\": 1}'),
(10, '2025-11-23 08:37:23', NULL, 'UPDATE', 'staf', 1, '{\"no_staf\": \"004278\", \"no_kp\": \"660403026023\", \"nama\": \"DZULKARNAIN BIN ABDUL MANAF\", \"emel\": \"dzulkarnain@keda.gov.my\", \"telefon\": null, \"id_jawatan\": 29, \"id_gred\": null, \"id_bahagian\": 23, \"id_status\": 1}', '{\"no_staf\": \"004278\", \"no_kp\": \"660403026023\", \"nama\": \"DZULKARNAIN BIN ABDUL MANAF\", \"emel\": \"dzulkarnain@keda.gov.my\", \"telefon\": \"\", \"id_jawatan\": 29, \"id_gred\": 8, \"id_bahagian\": 23, \"id_status\": 1}'),
(11, '2025-11-23 08:47:09', NULL, 'UPDATE', 'staf', 91, '{\"no_staf\": \"006106\", \"no_kp\": \"791130025411\", \"nama\": \"ABDUL AZIZ BIN SAAD\", \"emel\": \"abdaziz9003@gmail.com\", \"telefon\": null, \"id_jawatan\": 20, \"id_gred\": null, \"id_bahagian\": 24, \"id_status\": 1}', '{\"no_staf\": \"006106\", \"no_kp\": \"791130025411\", \"nama\": \"ABDUL AZIZ BIN SAAD\", \"emel\": \"abdaziz9003@gmail.com\", \"telefon\": \"\", \"id_jawatan\": 20, \"id_gred\": 1, \"id_bahagian\": 24, \"id_status\": 1}'),
(12, '2025-11-24 02:51:01', NULL, 'UPDATE', 'staf', 245, '{\"emel\": \"afiq.wahab@keda.gov.my\", \"nama\": \"MUHAMMAD AFIQ BIN A. WAHAB\", \"no_kp\": \"910301025627\", \"id_gred\": null, \"no_staf\": \"008013\", \"telefon\": null, \"id_status\": 1, \"id_jawatan\": 7, \"id_bahagian\": 29}', '{\"emel\": \"afiq.wahab@keda.gov.my\", \"nama\": \"MUHAMMAD AFIQ BIN A. WAHAB\", \"no_kp\": \"910301025627\", \"id_gred\": 9, \"no_staf\": \"008013\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 7, \"id_bahagian\": 29}'),
(13, '2025-12-01 04:06:18', NULL, 'UPDATE', 'staf', 1, '{\"emel\": \"dzulkarnain@keda.gov.my\", \"nama\": \"DZULKARNAIN BIN ABDUL MANAF\", \"no_kp\": \"660403026023\", \"id_gred\": 8, \"no_staf\": \"004278\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 29, \"id_bahagian\": 23}', '{\"emel\": \"dzulkarnain@keda.gov.my\", \"nama\": \"DZULKARNAIN BIN ABDUL MANAF\", \"no_kp\": \"660403026023\", \"id_gred\": 8, \"no_staf\": \"004278\", \"telefon\": null, \"id_status\": 1, \"id_jawatan\": 29, \"id_bahagian\": 23}'),
(14, '2025-12-10 09:57:08', NULL, 'UPDATE', 'staf', 97, '{\"emel\": \"hasmawati@keda.gov.my\", \"nama\": \"HASMAWATI BINTI AZIZ\", \"no_kp\": \"850527025820\", \"id_gred\": null, \"no_staf\": \"006165\", \"telefon\": null, \"id_status\": 1, \"id_jawatan\": 33, \"id_bahagian\": 29}', '{\"emel\": \"hasmawati@keda.gov.my\", \"nama\": \"HASMAWATI BINTI AZIZ\", \"no_kp\": \"850527025820\", \"id_gred\": 4, \"no_staf\": \"006165\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 33, \"id_bahagian\": 29}'),
(15, '2025-12-10 09:57:37', NULL, 'UPDATE', 'staf', 222, '{\"emel\": \"husainishukri@keda.gov.my\", \"nama\": \"MUHAMMAD HUSAINI BIN SHUKRI\", \"no_kp\": \"920928025953\", \"id_gred\": null, \"no_staf\": \"007757\", \"telefon\": null, \"id_status\": 1, \"id_jawatan\": 4, \"id_bahagian\": 29}', '{\"emel\": \"husainishukri@keda.gov.my\", \"nama\": \"MUHAMMAD HUSAINI BIN SHUKRI\", \"no_kp\": \"920928025953\", \"id_gred\": 1, \"no_staf\": \"007757\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 4, \"id_bahagian\": 29}'),
(16, '2025-12-10 09:58:09', NULL, 'UPDATE', 'staf', 61, '{\"emel\": \"zubir@keda.gov.my\", \"nama\": \"MUHAMMAD ZUBIR BIN ZAKARIA\", \"no_kp\": \"820427025703\", \"id_gred\": null, \"no_staf\": \"005576\", \"telefon\": null, \"id_status\": 1, \"id_jawatan\": 4, \"id_bahagian\": 29}', '{\"emel\": \"zubir@keda.gov.my\", \"nama\": \"MUHAMMAD ZUBIR BIN ZAKARIA\", \"no_kp\": \"820427025703\", \"id_gred\": 1, \"no_staf\": \"005576\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 4, \"id_bahagian\": 29}'),
(17, '2025-12-10 09:58:23', NULL, 'UPDATE', 'staf', 61, '{\"emel\": \"zubir@keda.gov.my\", \"nama\": \"MUHAMMAD ZUBIR BIN ZAKARIA\", \"no_kp\": \"820427025703\", \"id_gred\": 1, \"no_staf\": \"005576\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 4, \"id_bahagian\": 29}', '{\"emel\": \"zubir@keda.gov.my\", \"nama\": \"MUHAMMAD ZUBIR BIN ZAKARIA\", \"no_kp\": \"820427025703\", \"id_gred\": 2, \"no_staf\": \"005576\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 4, \"id_bahagian\": 29}'),
(18, '2025-12-10 09:58:42', NULL, 'UPDATE', 'staf', 97, '{\"emel\": \"hasmawati@keda.gov.my\", \"nama\": \"HASMAWATI BINTI AZIZ\", \"no_kp\": \"850527025820\", \"id_gred\": 4, \"no_staf\": \"006165\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 33, \"id_bahagian\": 29}', '{\"emel\": \"hasmawati@keda.gov.my\", \"nama\": \"HASMAWATI BINTI AZIZ\", \"no_kp\": \"850527025820\", \"id_gred\": 5, \"no_staf\": \"006165\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 33, \"id_bahagian\": 29}'),
(19, '2025-12-10 09:59:02', NULL, 'UPDATE', 'staf', 11, '{\"emel\": \"asmida@keda.gov.my\", \"nama\": \"NOR ASMIDA BINTI HASSAN\", \"no_kp\": \"740727025406\", \"id_gred\": null, \"no_staf\": \"004693\", \"telefon\": null, \"id_status\": 1, \"id_jawatan\": 33, \"id_bahagian\": 29}', '{\"emel\": \"asmida@keda.gov.my\", \"nama\": \"NOR ASMIDA BINTI HASSAN\", \"no_kp\": \"740727025406\", \"id_gred\": 6, \"no_staf\": \"004693\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 33, \"id_bahagian\": 29}'),
(20, '2025-12-10 09:59:31', NULL, 'UPDATE', 'staf', 388, '{\"emel\": \"irdina@keda.gov.my\", \"nama\": \"SITI IRDINA SAFFIYA BINTI KHAIRIL FAIZI\", \"no_kp\": \"000919100884\", \"id_gred\": null, \"no_staf\": \"008886\", \"telefon\": null, \"id_status\": 1, \"id_jawatan\": 26, \"id_bahagian\": 29}', '{\"emel\": \"irdina@keda.gov.my\", \"nama\": \"SITI IRDINA SAFFIYA BINTI KHAIRIL FAIZI\", \"no_kp\": \"000919100884\", \"id_gred\": 1, \"no_staf\": \"008886\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 26, \"id_bahagian\": 29}'),
(21, '2025-12-11 01:54:48', NULL, 'UPDATE', 'staf', 126, '{\"emel\": \"khairul@keda.gov.my\", \"nama\": \"MOHD KHAIRUL BIN AHMAD\", \"no_kp\": \"860619265345\", \"id_gred\": null, \"no_staf\": \"006552\", \"telefon\": null, \"id_status\": 1, \"id_jawatan\": 10, \"id_bahagian\": 5}', '{\"emel\": \"khairul@keda.gov.my\", \"nama\": \"MOHD KHAIRUL BIN AHMAD\", \"no_kp\": \"860619265345\", \"id_gred\": 10, \"no_staf\": \"006552\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 10, \"id_bahagian\": 5}'),
(22, '2025-12-11 01:55:05', NULL, 'UPDATE', 'staf', 45, '{\"emel\": \"shuhaila@keda.gov.my\", \"nama\": \"SHUHAILA BINTI HAMZAH\", \"no_kp\": \"820206025208\", \"id_gred\": null, \"no_staf\": \"005304\", \"telefon\": null, \"id_status\": 1, \"id_jawatan\": 10, \"id_bahagian\": 5}', '{\"emel\": \"shuhaila@keda.gov.my\", \"nama\": \"SHUHAILA BINTI HAMZAH\", \"no_kp\": \"820206025208\", \"id_gred\": 12, \"no_staf\": \"005304\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 10, \"id_bahagian\": 5}'),
(23, '2025-12-11 02:46:22', NULL, 'UPDATE', 'staf', 97, '{\"emel\": \"hasmawati@keda.gov.my\", \"nama\": \"HASMAWATI BINTI AZIZ\", \"no_kp\": \"850527025820\", \"id_gred\": 5, \"no_staf\": \"006165\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 33, \"id_bahagian\": 29}', '{\"emel\": \"hasmawati@keda.gov.my\", \"nama\": \"HASMAWATI BINTI AZIZ\", \"no_kp\": \"850527025820\", \"id_gred\": 5, \"no_staf\": \"006165\", \"telefon\": \"416\", \"id_status\": 1, \"id_jawatan\": 33, \"id_bahagian\": 29}'),
(24, '2025-12-11 03:30:26', NULL, 'UPDATE', 'staf', 347, '{\"emel\": \"maisarahzahari21@gmail.com\", \"nama\": \"MAISARAH BINTI ZAHARI\", \"no_kp\": \"970921026078\", \"id_gred\": null, \"no_staf\": \"008839\", \"telefon\": null, \"id_status\": 1, \"id_jawatan\": 24, \"id_bahagian\": 8}', '{\"emel\": \"maisarahzahari21@gmail.com\", \"nama\": \"MAISARAH BINTI ZAHARI\", \"no_kp\": \"970921026078\", \"id_gred\": 1, \"no_staf\": \"008839\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 24, \"id_bahagian\": 5}'),
(25, '2025-12-11 03:33:14', NULL, 'UPDATE', 'staf', 349, '{\"emel\": \"mohamadazrolmatrodzi@gmail.com\", \"nama\": \"MOHD AZROL BIN MAT RODZI\", \"no_kp\": \"901001026009\", \"id_gred\": null, \"no_staf\": \"008841\", \"telefon\": null, \"id_status\": 1, \"id_jawatan\": 20, \"id_bahagian\": 24}', '{\"emel\": \"mohamadazrolmatrodzi@gmail.com\", \"nama\": \"MOHD AZROL BIN MAT RODZI\", \"no_kp\": \"901001026009\", \"id_gred\": 1, \"no_staf\": \"008841\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 20, \"id_bahagian\": 10}'),
(26, '2025-12-11 03:34:29', NULL, 'UPDATE', 'staf', 313, '{\"emel\": \"aizarudinaizarudin@.gmail.com\", \"nama\": \"AIZARUDIN BIN ABDUL KHADIR\", \"no_kp\": \"920225025201\", \"id_gred\": null, \"no_staf\": \"008693\", \"telefon\": null, \"id_status\": 1, \"id_jawatan\": 20, \"id_bahagian\": 10}', '{\"emel\": \"aizarudinaizarudin@gmail.com\", \"nama\": \"AIZARUDIN BIN ABDUL KHADIR\", \"no_kp\": \"920225025201\", \"id_gred\": 1, \"no_staf\": \"008693\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 20, \"id_bahagian\": 24}'),
(27, '2025-12-11 03:35:09', NULL, 'UPDATE', 'staf', 36, '{\"emel\": \"haslia@keda.gov.my\", \"nama\": \"HASLIA BINTI ABU HASAN\", \"no_kp\": \"811119146218\", \"id_gred\": null, \"no_staf\": \"005193\", \"telefon\": null, \"id_status\": 1, \"id_jawatan\": 9, \"id_bahagian\": 7}', '{\"emel\": \"haslia@keda.gov.my\", \"nama\": \"HASLIA BINTI ABU HASAN\", \"no_kp\": \"811119146218\", \"id_gred\": 10, \"no_staf\": \"005193\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 9, \"id_bahagian\": 7}'),
(28, '2025-12-11 03:35:38', NULL, 'UPDATE', 'staf', 86, '{\"emel\": \"azmeer@keda.gov.my\", \"nama\": \"KHAIRUL AZMEER BIN ABU BAKAR\", \"no_kp\": \"840604095007\", \"id_gred\": null, \"no_staf\": \"005941\", \"telefon\": null, \"id_status\": 1, \"id_jawatan\": 10, \"id_bahagian\": 19}', '{\"emel\": \"azmeer@keda.gov.my\", \"nama\": \"KHAIRUL AZMEER BIN ABU BAKAR\", \"no_kp\": \"840604095007\", \"id_gred\": 14, \"no_staf\": \"005941\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 10, \"id_bahagian\": 19}'),
(29, '2025-12-11 03:36:24', NULL, 'UPDATE', 'staf', 219, '{\"emel\": \"khairulridhwan@keda.gov.my\", \"nama\": \"KHAIRUL RIDHWAN BIN ABD AZIZ @ ABD RAZAK\", \"no_kp\": \"901015025621\", \"id_gred\": null, \"no_staf\": \"007726\", \"telefon\": null, \"id_status\": 1, \"id_jawatan\": 9, \"id_bahagian\": 16}', '{\"emel\": \"khairulridhwan@keda.gov.my\", \"nama\": \"KHAIRUL RIDHWAN BIN ABD AZIZ @ ABD RAZAK\", \"no_kp\": \"901015025621\", \"id_gred\": 9, \"no_staf\": \"007726\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 9, \"id_bahagian\": 16}'),
(30, '2025-12-11 03:37:15', NULL, 'UPDATE', 'staf', 276, '{\"emel\": \"farzli@keda.gov.my\", \"nama\": \"MOHD NOOR FARZLI BIN ADENAN\", \"no_kp\": \"880311025319\", \"id_gred\": null, \"no_staf\": \"008327\", \"telefon\": null, \"id_status\": 1, \"id_jawatan\": 10, \"id_bahagian\": 6}', '{\"emel\": \"farzli@keda.gov.my\", \"nama\": \"MOHD NOOR FARZLI BIN ADENAN\", \"no_kp\": \"880311025319\", \"id_gred\": 9, \"no_staf\": \"008327\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 10, \"id_bahagian\": 6}'),
(31, '2025-12-11 03:55:56', NULL, 'UPDATE', 'staf', 66, '{\"emel\": \"suhaimiahmad7920@gmail.com\", \"nama\": \"SUHAIMI BIN AHMAD\", \"no_kp\": \"820304025447\", \"id_gred\": null, \"no_staf\": \"005631\", \"telefon\": null, \"id_status\": 1, \"id_jawatan\": 24, \"id_bahagian\": 20}', '{\"emel\": \"suhaimiahmad7920@gmail.com\", \"nama\": \"SUHAIMI BIN AHMAD\", \"no_kp\": \"820304025447\", \"id_gred\": 1, \"no_staf\": \"005631\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 24, \"id_bahagian\": 20}'),
(32, '2025-12-11 03:57:02', NULL, 'UPDATE', 'staf', 33, '{\"emel\": \"syikin@keda.gov.my\", \"nama\": \"NURASYIKIN BINTI AZIZAN\", \"no_kp\": \"810326025246\", \"id_gred\": null, \"no_staf\": \"005126\", \"telefon\": null, \"id_status\": 1, \"id_jawatan\": 33, \"id_bahagian\": 8}', '{\"emel\": \"syikin@keda.gov.my\", \"nama\": \"NURASYIKIN BINTI AZIZAN\", \"no_kp\": \"810326025246\", \"id_gred\": 6, \"no_staf\": \"005126\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 33, \"id_bahagian\": 8}'),
(33, '2025-12-11 05:07:20', NULL, 'UPDATE', 'staf', 293, '{\"emel\": \"hadi@keda.gov.my\", \"nama\": \"ABDUL HADI BIN ABDUL HALIM\", \"no_kp\": \"960228025123\", \"id_gred\": null, \"no_staf\": \"008495\", \"telefon\": null, \"id_status\": 1, \"id_jawatan\": 22, \"id_bahagian\": 9}', '{\"emel\": \"hadi@keda.gov.my\", \"nama\": \"ABDUL HADI BIN ABDUL HALIM\", \"no_kp\": \"960228025123\", \"id_gred\": 1, \"no_staf\": \"008495\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 22, \"id_bahagian\": 9}'),
(34, '2025-12-11 05:07:53', NULL, 'UPDATE', 'staf', 10, '{\"emel\": \"abdulhalim@keda.gov.my\", \"nama\": \"ABDUL HALIM BIN MIHAT\", \"no_kp\": \"710606026093\", \"id_gred\": null, \"no_staf\": \"004685\", \"telefon\": null, \"id_status\": 1, \"id_jawatan\": 30, \"id_bahagian\": 8}', '{\"emel\": \"abdulhalim@keda.gov.my\", \"nama\": \"ABDUL HALIM BIN MIHAT\", \"no_kp\": \"710606026093\", \"id_gred\": 6, \"no_staf\": \"004685\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 30, \"id_bahagian\": 8}'),
(35, '2025-12-11 05:08:53', NULL, 'UPDATE', 'staf', 354, '{\"emel\": \"abduljalilmohamedsidik@gmail.com\", \"nama\": \"ABDUL JALIL BIN MOHAMED SIDIK\", \"no_kp\": \"990607025315\", \"id_gred\": null, \"no_staf\": \"008846\", \"telefon\": null, \"id_status\": 1, \"id_jawatan\": 22, \"id_bahagian\": 10}', '{\"emel\": \"abduljalilmohamedsidik@gmail.com\", \"nama\": \"ABDUL JALIL BIN MOHAMED SIDIK\", \"no_kp\": \"990607025315\", \"id_gred\": 1, \"no_staf\": \"008846\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 22, \"id_bahagian\": 10}'),
(36, '2025-12-11 05:09:38', NULL, 'UPDATE', 'staf', 209, '{\"emel\": \"mu\'min@keda.gov.my\", \"nama\": \"ABDUL MU\'MIN BIN MOHD ROZI\", \"no_kp\": \"891230025775\", \"id_gred\": null, \"no_staf\": \"007573\", \"telefon\": null, \"id_status\": 1, \"id_jawatan\": 35, \"id_bahagian\": 30}', '{\"emel\": \"mu\'min@keda.gov.my\", \"nama\": \"ABDUL MU\'MIN BIN MOHD ROZI\", \"no_kp\": \"891230025775\", \"id_gred\": 5, \"no_staf\": \"007573\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 35, \"id_bahagian\": 30}'),
(37, '2025-12-11 05:09:51', NULL, 'UPDATE', 'staf', 152, '{\"emel\": \"mankd89@icloud.com\", \"nama\": \"ABDUL RAHMAN BIN ISMAIL\", \"no_kp\": \"891105025629\", \"id_gred\": null, \"no_staf\": \"006897\", \"telefon\": null, \"id_status\": 1, \"id_jawatan\": 21, \"id_bahagian\": 5}', '{\"emel\": \"mankd89@icloud.com\", \"nama\": \"ABDUL RAHMAN BIN ISMAIL\", \"no_kp\": \"891105025629\", \"id_gred\": 1, \"no_staf\": \"006897\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 21, \"id_bahagian\": 5}'),
(38, '2025-12-11 05:10:17', NULL, 'UPDATE', 'staf', 370, '{\"emel\": \"abdrazak@keda.gov.my\", \"nama\": \"ABDUL RAZAK BIN GHAZALI\", \"no_kp\": \"940226025981\", \"id_gred\": null, \"no_staf\": \"008865\", \"telefon\": null, \"id_status\": 1, \"id_jawatan\": 27, \"id_bahagian\": 2}', '{\"emel\": \"abdrazak@keda.gov.my\", \"nama\": \"ABDUL RAZAK BIN GHAZALI\", \"no_kp\": \"940226025981\", \"id_gred\": 5, \"no_staf\": \"008865\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 27, \"id_bahagian\": 2}'),
(39, '2025-12-11 05:10:42', NULL, 'UPDATE', 'staf', 375, '{\"emel\": \"faizzain@keda.gov.my\", \"nama\": \"ABDULLAH FAIZ BIN MOHD ZAIN\", \"no_kp\": \"970801115973\", \"id_gred\": null, \"no_staf\": \"008871\", \"telefon\": null, \"id_status\": 1, \"id_jawatan\": 22, \"id_bahagian\": 1}', '{\"emel\": \"faizzain@keda.gov.my\", \"nama\": \"ABDULLAH FAIZ BIN MOHD ZAIN\", \"no_kp\": \"970801115973\", \"id_gred\": 1, \"no_staf\": \"008871\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 22, \"id_bahagian\": 1}'),
(40, '2025-12-11 05:11:20', NULL, 'UPDATE', 'staf', 15, '{\"emel\": \"abu261426@gmail.com\", \"nama\": \"ABU BAKAR BIN AHMAD\", \"no_kp\": \"670305025579\", \"id_gred\": null, \"no_staf\": \"004839\", \"telefon\": null, \"id_status\": 1, \"id_jawatan\": 20, \"id_bahagian\": 12}', '{\"emel\": \"abu261426@gmail.com\", \"nama\": \"ABU BAKAR BIN AHMAD\", \"no_kp\": \"670305025579\", \"id_gred\": 1, \"no_staf\": \"004839\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 20, \"id_bahagian\": 12}'),
(41, '2025-12-11 05:11:32', NULL, 'UPDATE', 'staf', 68, '{\"emel\": \"jan94721@gmail.com\", \"nama\": \"AFNIZAN AZWAN BIN AZIZAN\", \"no_kp\": \"751210025853\", \"id_gred\": null, \"no_staf\": \"005657\", \"telefon\": null, \"id_status\": 1, \"id_jawatan\": 20, \"id_bahagian\": 10}', '{\"emel\": \"jan94721@gmail.com\", \"nama\": \"AFNIZAN AZWAN BIN AZIZAN\", \"no_kp\": \"751210025853\", \"id_gred\": 1, \"no_staf\": \"005657\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 20, \"id_bahagian\": 10}'),
(42, '2025-12-11 05:11:47', NULL, 'UPDATE', 'staf', 332, '{\"emel\": \"azzimzubedy@keda.gov.my\", \"nama\": \"AHMAD AZZIM ZUBEDY BIN SHAIKH SALIM\", \"no_kp\": \"941008025451\", \"id_gred\": null, \"no_staf\": \"008824\", \"telefon\": null, \"id_status\": 1, \"id_jawatan\": 25, \"id_bahagian\": 4}', '{\"emel\": \"azzimzubedy@keda.gov.my\", \"nama\": \"AHMAD AZZIM ZUBEDY BIN SHAIKH SALIM\", \"no_kp\": \"941008025451\", \"id_gred\": 1, \"no_staf\": \"008824\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 25, \"id_bahagian\": 4}'),
(43, '2025-12-11 05:12:15', NULL, 'UPDATE', 'staf', 5, '{\"emel\": \"ahmad@keda.gov.my\", \"nama\": \"AHMAD BIN ABDUL LATEH\", \"no_kp\": \"660826025727\", \"id_gred\": null, \"no_staf\": \"004571\", \"telefon\": null, \"id_status\": 1, \"id_jawatan\": 30, \"id_bahagian\": 8}', '{\"emel\": \"ahmad@keda.gov.my\", \"nama\": \"AHMAD BIN ABDUL LATEH\", \"no_kp\": \"660826025727\", \"id_gred\": 6, \"no_staf\": \"004571\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 30, \"id_bahagian\": 8}'),
(44, '2025-12-11 05:13:04', NULL, 'UPDATE', 'staf', 267, '{\"emel\": \"cord1987@gmail.com\", \"nama\": \"AHMAD HAFEZ BIN AHMAD HAMZAH\", \"no_kp\": \"870104025667\", \"id_gred\": null, \"no_staf\": \"008235\", \"telefon\": null, \"id_status\": 1, \"id_jawatan\": 12, \"id_bahagian\": 8}', '{\"emel\": \"cord1987@gmail.com\", \"nama\": \"AHMAD HAFEZ BIN AHMAD HAMZAH\", \"no_kp\": \"870104025667\", \"id_gred\": 1, \"no_staf\": \"008235\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 12, \"id_bahagian\": 8}'),
(45, '2025-12-11 05:13:21', NULL, 'UPDATE', 'staf', 360, '{\"emel\": \"a.musanif@keda.gov.my\", \"nama\": \"AHMAD MUSANIF BIN ABDULL MANAFF\", \"no_kp\": \"961202055011\", \"id_gred\": null, \"no_staf\": \"008852\", \"telefon\": null, \"id_status\": 1, \"id_jawatan\": 35, \"id_bahagian\": 8}', '{\"emel\": \"a.musanif@keda.gov.my\", \"nama\": \"AHMAD MUSANIF BIN ABDULL MANAFF\", \"no_kp\": \"961202055011\", \"id_gred\": 5, \"no_staf\": \"008852\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 35, \"id_bahagian\": 8}'),
(46, '2025-12-11 05:13:35', NULL, 'UPDATE', 'staf', 164, '{\"emel\": \"ahmadshaffren@gmail.com\", \"nama\": \"AHMAD SHAFFREN BIN ZAHER\", \"no_kp\": \"850116025105\", \"id_gred\": null, \"no_staf\": \"007030\", \"telefon\": null, \"id_status\": 1, \"id_jawatan\": 20, \"id_bahagian\": 9}', '{\"emel\": \"ahmadshaffren@gmail.com\", \"nama\": \"AHMAD SHAFFREN BIN ZAHER\", \"no_kp\": \"850116025105\", \"id_gred\": 1, \"no_staf\": \"007030\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 20, \"id_bahagian\": 9}'),
(47, '2025-12-11 05:14:42', NULL, 'UPDATE', 'staf', 211, '{\"emel\": \"tarmizi@keda.gov.my\", \"nama\": \"AHMAD TARMIZI BIN ABDUL AZIZ\", \"no_kp\": \"880812086793\", \"id_gred\": null, \"no_staf\": \"007610\", \"telefon\": null, \"id_status\": 1, \"id_jawatan\": 29, \"id_bahagian\": 13}', '{\"emel\": \"tarmizi@keda.gov.my\", \"nama\": \"AHMAD TARMIZI BIN ABDUL AZIZ\", \"no_kp\": \"880812086793\", \"id_gred\": 5, \"no_staf\": \"007610\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 29, \"id_bahagian\": 13}'),
(48, '2025-12-11 05:15:21', NULL, 'UPDATE', 'staf', 18, '{\"emel\": \"suzani@keda.gov.my\", \"nama\": \"AIDA SUZANI BINTI ISHAK\", \"no_kp\": \"761010016476\", \"id_gred\": null, \"no_staf\": \"004911\", \"telefon\": null, \"id_status\": 1, \"id_jawatan\": 22, \"id_bahagian\": 1}', '{\"emel\": \"suzani@keda.gov.my\", \"nama\": \"AIDA SUZANI BINTI ISHAK\", \"no_kp\": \"761010016476\", \"id_gred\": 2, \"no_staf\": \"004911\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 22, \"id_bahagian\": 1}'),
(49, '2025-12-11 05:15:37', NULL, 'UPDATE', 'staf', 337, '{\"emel\": \"aimankhadri@keda.gov.my\", \"nama\": \"AIMAN KHADRI BIN MOHAMAD KHOTAIB\", \"no_kp\": \"920402025051\", \"id_gred\": null, \"no_staf\": \"008829\", \"telefon\": null, \"id_status\": 1, \"id_jawatan\": 22, \"id_bahagian\": 12}', '{\"emel\": \"aimankhadri@keda.gov.my\", \"nama\": \"AIMAN KHADRI BIN MOHAMAD KHOTAIB\", \"no_kp\": \"920402025051\", \"id_gred\": 1, \"no_staf\": \"008829\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 22, \"id_bahagian\": 12}'),
(50, '2025-12-11 05:15:58', NULL, 'UPDATE', 'staf', 361, '{\"emel\": \"anadhia27@gmail.com\", \"nama\": \"AINA NADHIA BINTI MUHAMMAD ALI\", \"no_kp\": \"980727135536\", \"id_gred\": null, \"no_staf\": \"008855\", \"telefon\": null, \"id_status\": 1, \"id_jawatan\": 17, \"id_bahagian\": 8}', '{\"emel\": \"anadhia27@gmail.com\", \"nama\": \"AINA NADHIA BINTI MUHAMMAD ALI\", \"no_kp\": \"980727135536\", \"id_gred\": 1, \"no_staf\": \"008855\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 17, \"id_bahagian\": 8}'),
(51, '2025-12-11 05:17:34', NULL, 'UPDATE', 'staf', 194, '{\"emel\": \"ainieafida@keda.gov.my\", \"nama\": \"AINIE AFIDA BINTI MUAKTAR\", \"no_kp\": \"890308025296\", \"id_gred\": null, \"no_staf\": \"007399\", \"telefon\": null, \"id_status\": 1, \"id_jawatan\": 26, \"id_bahagian\": 21}', '{\"emel\": \"ainieafida@keda.gov.my\", \"nama\": \"AINIE AFIDA BINTI MUAKTAR\", \"no_kp\": \"890308025296\", \"id_gred\": 1, \"no_staf\": \"007399\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 26, \"id_bahagian\": 21}'),
(52, '2025-12-11 05:21:36', NULL, 'UPDATE', 'staf', 342, '{\"emel\": \"ameerulzafran@keda.gov.my\", \"nama\": \"AMEERUL ZAFRAN BIN SAIFUL AMIR\", \"no_kp\": \"990717055371\", \"id_gred\": null, \"no_staf\": \"008834\", \"telefon\": null, \"id_status\": 1, \"id_jawatan\": 26, \"id_bahagian\": 30}', '{\"emel\": \"ameerulzafran@keda.gov.my\", \"nama\": \"AMEERUL ZAFRAN BIN SAIFUL AMIR\", \"no_kp\": \"990717055371\", \"id_gred\": 1, \"no_staf\": \"008834\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 26, \"id_bahagian\": 30}'),
(53, '2025-12-11 05:22:09', NULL, 'UPDATE', 'staf', 88, '{\"emel\": \"aminahisa300@gmail.com\", \"nama\": \"AMINAH BINTI ISA\", \"no_kp\": \"840222025388\", \"id_gred\": null, \"no_staf\": \"006009\", \"telefon\": null, \"id_status\": 1, \"id_jawatan\": 21, \"id_bahagian\": 8}', '{\"emel\": \"aminahisa300@gmail.com\", \"nama\": \"AMINAH BINTI ISA\", \"no_kp\": \"840222025388\", \"id_gred\": 1, \"no_staf\": \"006009\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 21, \"id_bahagian\": 8}'),
(54, '2025-12-11 05:22:42', NULL, 'UPDATE', 'staf', 78, '{\"emel\": \"amiraisrorodin@keda.gov.my\", \"nama\": \"AMIRA BINTI ISRORODIN\", \"no_kp\": \"850207085904\", \"id_gred\": null, \"no_staf\": \"005819\", \"telefon\": null, \"id_status\": 1, \"id_jawatan\": 26, \"id_bahagian\": 26}', '{\"emel\": \"amiraisrorodin@keda.gov.my\", \"nama\": \"AMIRA BINTI ISRORODIN\", \"no_kp\": \"850207085904\", \"id_gred\": 2, \"no_staf\": \"005819\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 26, \"id_bahagian\": 26}'),
(55, '2025-12-11 05:25:18', NULL, 'UPDATE', 'staf', 144, '{\"emel\": \"amirah@keda.gov.my\", \"nama\": \"AMIRAH BINTI ADIMAT\", \"no_kp\": \"880306025592\", \"id_gred\": null, \"no_staf\": \"006811\", \"telefon\": null, \"id_status\": 1, \"id_jawatan\": 38, \"id_bahagian\": 4}', '{\"emel\": \"amirah@keda.gov.my\", \"nama\": \"AMIRAH BINTI ADIMAT\", \"no_kp\": \"880306025592\", \"id_gred\": 5, \"no_staf\": \"006811\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 38, \"id_bahagian\": 21}'),
(56, '2025-12-11 05:25:45', NULL, 'UPDATE', 'staf', 290, '{\"emel\": \"amirahahmad@keda.gov.my\", \"nama\": \"AMIRAH BINTI AHMAD\", \"no_kp\": \"950622025632\", \"id_gred\": null, \"no_staf\": \"008464\", \"telefon\": null, \"id_status\": 1, \"id_jawatan\": 22, \"id_bahagian\": 3}', '{\"emel\": \"amirahahmad@keda.gov.my\", \"nama\": \"AMIRAH BINTI AHMAD\", \"no_kp\": \"950622025632\", \"id_gred\": 1, \"no_staf\": \"008464\", \"telefon\": \"\", \"id_status\": 1, \"id_jawatan\": 22, \"id_bahagian\": 3}');

-- --------------------------------------------------------

--
-- Table structure for table `bahagian`
--

CREATE TABLE `bahagian` (
  `id_bahagian` int(11) NOT NULL,
  `bahagian` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `bahagian`
--

INSERT INTO `bahagian` (`id_bahagian`, `bahagian`) VALUES
(1, 'BAHAGIAN PEMBANGUNAN KOMUNITI'),
(2, 'BAHAGIAN HARTANAH DAN PRASARANA'),
(3, 'BAHAGIAN PEMBANGUNAN USAHAWAN'),
(4, 'BAHAGIAN KEWANGAN'),
(5, 'BAHAGIAN KHIDMAT PENGURUSAN'),
(6, 'BAHAGIAN NIAGATANI'),
(7, 'BAHAGIAN PERANCANGAN, PEMANTAUAN DAN PENILAIAN'),
(8, 'KOLEJ KEDA'),
(9, 'PEJABAT KEDA ZON 1 - LANGKAWI'),
(10, 'PEJABAT KEDA ZON 2 - PADANG TERAP'),
(11, 'PEJABAT KEDA ZON 3 - PENDANG/POKOK SENA'),
(12, 'PEJABAT KEDA ZON 4 - SIK'),
(13, 'PEJABAT KEDA ZON 5 - BALING'),
(14, 'PEJABAT KEDA ZON 6 - KULIM'),
(15, 'PEJABAT KEDA ZON 7 - KUALA MUDA/YAN'),
(16, 'PEJABAT KEDA ZON 8 - KUBANG PASU'),
(17, 'PEJABAT KEDA ZON 9 - BANDAR BAHARU'),
(18, 'PEJABAT PENGURUS BESAR'),
(19, 'PEJABAT TIMBALAN PENGURUS BESAR (OPERASI)'),
(20, 'PEJABAT TIMBALAN PENGURUS BESAR (PENGURUSAN)'),
(21, 'UNIT AUDIT DALAM'),
(22, 'UNIT INTEGRITI DAN PERUNDANGAN'),
(23, 'UNIT KUTIPAN HASIL'),
(24, 'UNIT LOGISTIK'),
(25, 'UNIT PELABURAN DAN KAWAL SELIA ANAK SYARIKAT'),
(26, 'UNIT PENGURUSAN SUMBER MANUSIA'),
(27, 'UNIT PEROLEHAN'),
(28, 'UNIT TUGAS-TUGAS KHAS'),
(29, 'UNIT TEKNOLOGI MAKLUMAT'),
(30, 'UNIT KOMUNIKASI KORPORAT');

-- --------------------------------------------------------

--
-- Table structure for table `chatbot_faq`
--

CREATE TABLE `chatbot_faq` (
  `id` int(11) NOT NULL,
  `keyword` varchar(255) NOT NULL,
  `jawapan` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `chatbot_faq`
--

INSERT INTO `chatbot_faq` (`id`, `keyword`, `jawapan`) VALUES
(1, 'salam', 'Waalaikumussalam! Saya Pembantu Maya KEDA. Boleh saya bantu anda?'),
(2, 'hai', 'Hai! Ada apa-apa yang boleh saya bantu mengenai sistem MyApps?'),
(3, 'lupa', 'Jika anda terlupa kata laluan, sila klik butang <b>Log Keluar</b>, kemudian klik pautan <b>Lupa Kata Laluan</b> di skrin log masuk.'),
(4, 'password', 'Kata laluan anda mestilah mempunyai sekurang-kurangnya 12 aksara dan mengandungi simbol.'),
(5, 'reset', 'Pautan reset kata laluan akan dihantar ke emel rasmi anda dan sah selama 24 jam.'),
(6, 'tempah', 'Untuk menempah bilik mesyuarat, sila klik ikon <b>MyTempahan</b> di Dashboard, kemudian tekan butang (+) Terapung.'),
(7, 'masalah', 'Jika sistem menghadapi masalah teknikal, sila hubungi Unit ICT di 04-7205300.'),
(8, 'siapa', 'Saya adalah bot pintar bertujuan untuk membantu anda!'),
(9, 'salam', 'Waalaikumussalam! Saya Maya, pembantu digital MyApps KEDA. Boleh saya bantu anda?'),
(10, 'hai', 'Hai! Saya Maya. Ada apa-apa yang boleh saya bantu mengenai sistem MyApps?'),
(11, 'siapa', 'Saya adalah <b>Maya</b>, pembantu digital yang dicipta khas untuk membantu warga kerja KEDA menggunakan portal MyApps.'),
(12, 'khabar', 'Saya hanyalah program komputer, tapi saya sedia berkhidmat untuk anda!'),
(13, 'terima kasih', 'Sama-sama! Gembira dapat membantu. 😊'),
(14, 'bantu', 'Saya boleh bantu anda tentang cara login, tukar password, tempah bilik, dan info sistem. Sila taip soalan anda.'),
(15, 'lupa', 'Jika anda terlupa kata laluan:<br>1. Klik butang <b>Log Keluar</b> dahulu.<br>2. Di skrin login, klik pautan <b>Lupa Kata Laluan?</b>.<br>3. Masukkan No. KP dan Emel rasmi.<br>4. Semak emel untuk pautan reset.'),
(16, 'password', 'Untuk keselamatan, kata laluan anda mestilah:<br>- Sekurang-kurangnya <b>12 aksara</b>.<br>- Mengandungi huruf dan <b>simbol</b> (contoh: @, #, $).<br>- Tidak boleh sama dengan kata laluan lama.'),
(17, 'tukar kata laluan', 'Anda boleh menukar kata laluan di menu sisi (Sidebar).<br>Klik pada butang <b>Tukar Kata Laluan</b> di bawah nama anda.'),
(18, 'gagal login', 'Jika gagal log masuk:<br>1. Pastikan No. KP dimasukkan tanpa sengkang.<br>2. Pastikan butang CAPS LOCK tidak tertekan.<br>3. Jika masih gagal, sila gunakan fungsi <b>Lupa Kata Laluan</b>.'),
(19, 'otp', 'Kod OTP (One-Time Password) akan dihantar ke emel rasmi anda setiap kali anda menukar kata laluan atau log masuk dari peranti baru. Kod ini sah selama 5 minit sahaja.'),
(20, 'tak dapat emel', 'Sila semak folder <b>Spam</b> atau <b>Junk</b> dalam emel anda. Jika masih tiada, sila hubungi Unit IT untuk semakan alamat emel dalam sistem.'),
(21, 'profil', 'Anda boleh melihat maklumat diri di menu <b>Direktori Staf</b> atau klik butang <b>Kemaskini Profil</b> di menu sisi.'),
(22, 'kemaskini', 'Anda hanya dibenarkan mengemaskini <b>No. Telefon</b>, <b>Emel</b>, <b>Gred</b> dan <b>Bahagian</b> sahaja. Untuk perubahan Nama atau No. KP, sila berurusan dengan Unit Sumber Manusia.'),
(23, 'salah nama', 'Jika terdapat kesilapan pada Nama atau No. KP anda, sila hubungi Unit Sumber Manusia atau Admin Sistem untuk pembetulan.'),
(24, 'gred', 'Anda boleh mengemaskini Gred terkini anda melalui menu <b>Kemaskini Profil</b>.'),
(25, 'tempah', 'Untuk menempah bilik mesyuarat:<br>1. Klik ikon <b>MyTempahan</b> di Dashboard.<br>2. Anda akan dibawa ke sistem tempahan.<br>3. Klik butang <b>(+)</b> atau <b>Tempah Bilik</b> untuk memohon.'),
(26, 'bilik', 'Senarai bilik yang boleh ditempah boleh dilihat dalam sistem <b>MyTempahan</b>. Anda boleh semak kekosongan melalui kalendar di sana.'),
(27, 'status tempahan', 'Status tempahan bilik boleh disemak melalui Dashboard <b>MyTempahan</b>. Anda juga akan menerima notifikasi emel jika tempahan diluluskan.'),
(28, 'kursus', 'Untuk memohon atau menyemak kursus, sila klik ikon <b>MyLearning</b> di Dashboard utama.'),
(29, 'latihan', 'Sistem <b>MyLearning</b> merekodkan kehadiran dan jam latihan anda. Sila pastikan anda mendaftar kehadiran semasa kursus berlangsung.'),
(30, 'sijil', 'Sijil penyertaan kursus boleh dimuat turun melalui sistem <b>MyLearning</b> selepas kursus tamat (tertakluk kepada penganjur).'),
(31, 'cari staf', 'Anda boleh mencari rakan setugas melalui menu <b>Direktori Staf</b>. Masukkan nama, bahagian, atau sambungan telefon di kotak carian.'),
(32, 'telefon', 'No. telefon dan sambungan staf boleh didapati di menu <b>Direktori Staf</b>.'),
(33, 'emel staf', 'Anda boleh menyemak alamat emel rakan setugas di menu <b>Direktori Staf</b>.'),
(34, 'lambat', 'Jika sistem terasa lambat, cuba bersihkan <i>cache</i> pelayar web anda atau pastikan sambungan internet anda stabil.'),
(35, 'error', 'Jika anda menerima mesej ralat (Error), sila tangkap layar (screenshot) dan emelkan kepada <b>admin@keda.gov.my</b> untuk tindakan lanjut.'),
(36, 'hubungi', 'Untuk bantuan teknikal lanjut, sila hubungi <b>Unit Teknologi Maklumat</b> di sambungan <b>1234</b> atau emel ke <b>helpdesk@keda.gov.my</b>.'),
(37, 'keluar', 'Untuk log keluar dengan selamat, sila klik pautan <b>Log Keluar</b> berwarna merah di bahagian bawah menu sisi.'),
(38, 'tambah staf', '<b>(Hanya Admin)</b> Untuk menambah staf baharu:<br>1. Masuk ke menu <b>Direktori Staf</b>.<br>2. Klik butang biru <b>+ Tambah Staf</b> di penjuru kanan.<br>3. Isi maklumat lengkap (No. KP, Nama, Jawatan).<br>4. Tekan Simpan. Kata laluan sementara adalah <b>123456</b>.'),
(39, 'edit profil', 'Untuk mengemaskini profil diri:<br>1. Klik butang <b>Kemaskini Profil</b> di menu sisi (bawah nama anda).<br>2. Anda boleh ubah <b>No. Telefon</b>, <b>Emel</b>, <b>Gred</b>, dan <b>Bahagian</b>.<br>3. Klik Simpan Perubahan.<br><i>Nota: Nama dan No. KP dikunci demi keselamatan.</i>'),
(40, 'hapus staf', '<b>(Hanya Admin)</b> MyApps tidak membenarkan penghapusan data secara terus untuk menjaga integriti audit. Jika staf berhenti atau bersara, sila kemaskini <b>Status</b> staf tersebut kepada \"Tidak Aktif\" atau \"Bersara\" melalui menu Edit.'),
(41, 'tukar gambar', 'Untuk menukar gambar profil:<br>1. Klik <b>Kemaskini Profil</b>.<br>2. Di bahagian atas, klik <b>Choose File</b>.<br>3. Pilih gambar berukuran pasport (Format JPG/PNG).<br>4. Klik Simpan. Gambar di sidebar akan bertukar serta-merta.'),
(42, 'buat tempahan', 'Cara menempah bilik mesyuarat:<br>1. Klik ikon <b>MyTempahan</b> di Dashboard utama.<br>2. Klik butang terapung <b>(+)</b> di bucu kanan bawah.<br>3. Isi tajuk mesyuarat, pilih bilik, dan tetapkan masa mula/tamat.<br>4. Klik Hantar.'),
(43, 'batal tempahan', 'Untuk membatalkan tempahan bilik, sila hubungi Pentadbir Sistem atau Unit Pentadbiran dengan segera. Pembatalan sendiri belum disokong bagi mengelakkan kekeliruan jadual.'),
(44, 'semak kekosongan', 'Anda boleh menyemak kekosongan bilik dengan melihat <b>Kalendar</b> di dalam sistem MyTempahan. Slot yang berwarna bermaksud sudah ditempah.'),
(45, 'double booking', 'Sistem MyTempahan dilengkapi ciri <i>Anti-Bertindih</i>. Anda tidak boleh menempah bilik yang sama pada waktu yang sama dengan orang lain. Sila pilih waktu atau bilik lain.'),
(46, 'senarai bilik', 'Antara fasiliti yang boleh ditempah adalah:<br>- Bilik Gerakan (Aras 3)<br>- Bilik Bincang Seri Pagi (Aras 1)<br>- Dewan Seminar KEDA (Blok B).'),
(47, 'daftar kursus', 'Untuk mendaftar kursus:<br>1. Masuk ke modul <b>MyLearning</b>.<br>2. Lihat senarai kursus yang dibuka.<br>3. Klik butang <b>Mohon</b> pada kursus yang diminati.'),
(48, 'rekod latihan', 'Anda boleh menyemak sejarah kursus yang telah dihadiri dengan menekan menu <b>Sejarah Latihan</b> di dalam MyLearning. Ia akan memaparkan jumlah jam berkursus tahunan anda.'),
(49, 'download excel', 'Anda boleh memuat turun senarai staf:<br>1. Pergi ke <b>Direktori Staf</b>.<br>2. Klik butang hijau <b>Export Excel</b>.<br>3. Fail akan dimuat turun dalam format .xls (Boleh dibuka dengan Microsoft Excel).'),
(50, 'statistik', 'Dashboard utama memaparkan statistik terkini jumlah staf, pecahan mengikut jabatan, dan graf jantina secara visual (Bar & Pie Chart).'),
(51, 'hari jadi', 'Sistem memaparkan kalendar hari lahir staf pada bulan semasa. Anda boleh:<br>1. Klik menu <b>Kalendar Hari Jadi</b>.<br>2. Klik pada nama staf untuk melihat umur.<br>3. Klik <b>Hantar Ucapan</b> untuk menghantar emel automatik.'),
(52, 'umur', 'Sistem mengira umur secara automatik berdasarkan No. Kad Pengenalan. Ia dikemaskini setiap tahun.'),
(53, 'tukar password', 'Demi keselamatan, sila tukar kata laluan setiap 90 hari. Klik butang <b>Tukar Kata Laluan</b> di menu sisi. Syarat: Minima 12 aksara dan ada simbol.'),
(54, 'lupa password', 'Jangan risau. Di skrin login, klik <b>Lupa Kata Laluan?</b>. Masukkan No. KP dan Emel. Pautan reset akan dihantar ke emel rasmi anda.'),
(55, 'otp tak sampai', 'Jika kod OTP tidak masuk ke Inbox:<br>1. Semak folder <b>Spam/Junk</b>.<br>2. Pastikan emel anda berdaftar dengan betul dalam sistem.<br>3. Tunggu 1-2 minit, kadang-kadang pelayan emel sibuk.'),
(56, 'siapa admin', 'Pentadbir Sistem (Admin) adalah Unit Teknologi Maklumat KEDA. Sila hubungi sambungan 1234 untuk bantuan teknikal kritikal.'),
(57, 'sso', 'MyApps menggunakan konsep <b>Single Sign-On (SSO)</b>. Anda hanya perlu log masuk sekali di sini untuk mengakses MyTempahan, MyLearning, dan sistem lain tanpa perlu login semula.');

-- --------------------------------------------------------

--
-- Table structure for table `gred`
--

CREATE TABLE `gred` (
  `id_gred` int(11) NOT NULL,
  `gred` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `gred`
--

INSERT INTO `gred` (`id_gred`, `gred`) VALUES
(1, '1'),
(2, '2'),
(3, '3'),
(4, '4'),
(5, '5'),
(6, '6'),
(7, '7'),
(8, '8'),
(9, '9'),
(10, '10'),
(11, '11'),
(12, '12'),
(13, '13'),
(14, '14'),
(15, 'C'),
(16, 'B');

-- --------------------------------------------------------

--
-- Table structure for table `jawatan`
--

CREATE TABLE `jawatan` (
  `id_jawatan` int(11) NOT NULL,
  `jawatan` varchar(255) DEFAULT NULL,
  `skim` varchar(5) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `jawatan`
--

INSERT INTO `jawatan` (`id_jawatan`, `jawatan`, `skim`) VALUES
(1, 'AKAUNTAN', 'WA'),
(2, 'JURUAUDIO VISUAL', 'N'),
(3, 'JURUAUDIT', 'W'),
(4, 'JURUTEKNIK KOMPUTER', 'FT'),
(5, 'JURUTERA (AWAM)', 'J'),
(6, 'PEGAWAI KHIDMAT PELANGGAN', 'N'),
(7, 'PEGAWAI TEKNOLOGI MAKLUMAT', 'F'),
(8, 'PEGAWAI PENERANGAN', 'S'),
(9, 'PEGAWAI PERTANIAN', 'G'),
(10, 'PEGAWAI TADBIR', 'N'),
(11, 'PELUKIS PELAN', 'JA'),
(12, 'PEMBANTU BELIA DAN SUKAN (KONTRAK)', 'S'),
(13, 'PEGAWAI UNDANG-UNDANG', 'U'),
(14, 'PEMBANTU EHWAL ISLAM (KONTRAK)', 'S'),
(15, 'PEMBANTU PEGAWAI LATIHAN', 'E'),
(16, 'PEMBANTU PEGAWAI LATIHAN VOKASIONAL', 'DV'),
(17, 'PEMBANTU PEGAWAI LATIHAN VOKASIONAL (KONTRAK)', 'DV'),
(20, 'PEMBANTU KHIDMAT AM (PEMANDU KENDERAAN)', 'H'),
(21, 'PEMBANTU KHIDMAT AM (PEMBANTU AWAM)', 'H'),
(22, 'PEMBANTU EHWAL EKONOMI', 'E'),
(23, 'PEMBANTU KEMAHIRAN', 'H'),
(24, 'PEMBANTU KHIDMAT AM (PEMBANTU OPERASI)', 'H'),
(25, 'PEMBANTU TADBIR (KEWANGAN)', 'W'),
(26, 'PEMBANTU TADBIR (PEKERANIAN/OPERASI)', 'N'),
(27, 'PENOLONG JURUTERA (AWAM)', 'JA'),
(28, 'PENOLONG JURUTERA (JENTERA)', 'JA'),
(29, 'PENOLONG PEGAWAI EHWAL EKONOMI', 'E'),
(30, 'PENOLONG PEGAWAI LATIHAN VOKASIONAL', 'DV'),
(31, 'PENOLONG PEGAWAI PERANCANG BANDAR & DESA', 'JA'),
(33, 'PENOLONG PEGAWAI TEKNOLOGI MAKLUMAT', 'FA'),
(34, 'PENOLONG PEGAWAI PENILAIAN', 'W'),
(35, 'PENOLONG PEGAWAI TADBIR', 'N'),
(36, 'PENGAWAL KESELAMATAN', 'KP'),
(37, 'PENGURUS BESAR', 'JUSA'),
(38, 'PENOLONG AKAUNTAN', 'W'),
(39, 'PENOLONG JURUUKUR', 'JA'),
(40, 'PENYELIA ASRAMA', 'N'),
(41, 'SETIAUSAHA PEJABAT', 'N'),
(42, 'PEMBANTU AKAUNTAN', 'W'),
(43, 'PEMBANTU SETIAUSAHA PEJABAT', 'N'),
(44, 'PENOLONG JURUTERA (ELEKTRIK)', 'JA');

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

CREATE TABLE `kategori` (
  `id_kategori` int(11) NOT NULL,
  `nama_kategori` varchar(100) NOT NULL,
  `deskripsi` text,
  `aktif` int(11) DEFAULT '1',
  `tarikh_buat` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `kategori`
--

INSERT INTO `kategori` (`id_kategori`, `nama_kategori`, `deskripsi`, `aktif`, `tarikh_buat`) VALUES
(1, 'Aplikasi Dalaman', 'Aplikasi yang digunakan dalaman organisasi', 1, '2025-12-10 00:36:24'),
(2, 'Aplikasi Luaran', 'Aplikasi yang diakses dari luar organisasi', 1, '2025-12-10 00:36:24'),
(3, 'Aplikasi Gunasama', 'Aplikasi yang digunakan bersama dengan pihak lain', 1, '2025-12-10 00:36:24');

-- --------------------------------------------------------

--
-- Table structure for table `level`
--

CREATE TABLE `level` (
  `id_level` int(11) NOT NULL,
  `nama_level` varchar(50) NOT NULL,
  `deskripsi` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `level`
--

INSERT INTO `level` (`id_level`, `nama_level`, `deskripsi`, `created_at`) VALUES
(1, 'Viewer', 'Hanya baca saja (Read-only access)', '2025-12-10 08:44:58'),
(2, 'Editor', 'Boleh ubah data (Modify access)', '2025-12-10 08:44:58'),
(3, 'Admin', 'Full control - buat, baca, ubah, padam', '2025-12-10 08:44:58');

-- --------------------------------------------------------

--
-- Table structure for table `login`
--

CREATE TABLE `login` (
  `id_login` int(11) NOT NULL,
  `id_staf` int(11) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `otp_code` varchar(6) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL,
  `reset_token` varchar(100) DEFAULT NULL,
  `reset_token_expiry` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `tarikh_tukar_katalaluan` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `login`
--

INSERT INTO `login` (`id_login`, `id_staf`, `password_hash`, `otp_code`, `otp_expiry`, `reset_token`, `reset_token_expiry`, `created_at`, `updated_at`, `tarikh_tukar_katalaluan`) VALUES
(1, 1, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(2, 2, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(3, 3, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(4, 4, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(5, 5, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(6, 6, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(7, 7, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(8, 8, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(9, 9, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(10, 10, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(11, 11, '$2y$12$BdhXXBHmweTkhiqeqTY2R.ghGWWF7ZG5zvMVuFWTp2Uv6.JwsjxQm', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-12-11 03:24:29', '2025-12-11 11:23:57'),
(12, 12, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(13, 13, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(14, 14, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(15, 15, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(16, 16, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(17, 17, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(18, 18, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(19, 19, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(20, 20, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(21, 21, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(22, 22, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(23, 23, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(24, 24, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(25, 25, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(26, 26, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(27, 27, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(28, 28, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(29, 29, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(30, 30, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(31, 31, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(32, 32, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(33, 33, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(34, 34, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(35, 35, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(36, 36, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(37, 37, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(38, 38, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(39, 39, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(40, 40, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(41, 41, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(42, 42, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(43, 43, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(44, 44, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(45, 45, '$2y$12$8flu2uQzObg0IPBVR25STOqSrs8mZKMksgKZiB1ul0l6CzWK6aNPi', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-12-11 03:09:54', '2025-12-11 11:09:34'),
(46, 46, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(47, 47, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(48, 48, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(49, 49, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(50, 50, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(51, 51, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(52, 52, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(53, 53, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(54, 54, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(55, 55, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(56, 56, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(57, 57, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(58, 58, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(59, 59, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(60, 60, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(61, 61, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(62, 62, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(63, 63, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(64, 64, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(65, 65, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(66, 66, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(67, 67, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(68, 68, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(69, 69, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(70, 70, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(71, 71, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(72, 72, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(73, 73, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(74, 74, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(75, 75, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(76, 76, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(77, 77, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(78, 78, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(79, 79, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(80, 80, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(81, 81, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(82, 82, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(83, 83, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(84, 84, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(85, 85, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(86, 86, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(87, 87, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(88, 88, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(89, 89, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(90, 90, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(91, 91, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(92, 92, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(93, 93, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(94, 94, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(95, 95, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(96, 96, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(97, 97, '$2y$12$Dqs.FQeEFayVEH9NjA3UROgH8zSgOVJx0zlvfPVXz/rdmWYi0R8hO', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-12-11 02:44:23', '2025-12-11 10:43:58'),
(98, 98, '$2y$12$PcEi7W95qsl/i4oETvbtR.K5GL.BYJ9v6nOs8FJi6RyrjE/wZeufm', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-12-11 02:41:51', '2025-12-11 10:41:35'),
(99, 99, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(100, 100, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(101, 101, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(102, 102, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(103, 103, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(104, 104, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(105, 105, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(106, 106, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(107, 107, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(108, 108, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(109, 109, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(110, 110, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(111, 111, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(112, 112, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(113, 113, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(114, 114, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(115, 115, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(116, 116, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(117, 117, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(118, 118, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(119, 119, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(120, 120, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(121, 121, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(122, 122, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(123, 123, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(124, 124, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(125, 125, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(126, 126, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(127, 127, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(128, 128, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(129, 129, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(130, 130, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(131, 131, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(132, 132, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(133, 133, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(134, 134, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(135, 135, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(136, 136, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(137, 137, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(138, 138, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(139, 139, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(140, 140, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(141, 141, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(142, 142, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(143, 143, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(144, 144, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(145, 145, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(146, 146, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(147, 147, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(148, 148, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(149, 149, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(150, 150, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(151, 151, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(152, 152, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(153, 153, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(154, 154, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(155, 155, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(156, 156, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(157, 157, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(158, 158, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(159, 159, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(160, 160, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(161, 161, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(162, 162, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(163, 163, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(164, 164, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(165, 165, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(166, 166, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(167, 167, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(168, 168, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(169, 169, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(170, 170, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(171, 171, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(172, 172, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(173, 173, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(174, 174, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(175, 175, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(176, 176, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(177, 177, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(178, 178, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(179, 179, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(180, 180, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(181, 181, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(182, 182, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(183, 183, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(184, 184, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(185, 185, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(186, 186, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(187, 187, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(188, 188, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(189, 189, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(190, 190, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(191, 191, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(192, 192, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(193, 193, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(194, 194, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(195, 195, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(196, 196, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(197, 197, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(198, 198, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(199, 199, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(200, 200, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(201, 201, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(202, 202, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(203, 203, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(204, 204, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(205, 205, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(206, 206, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(207, 207, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(208, 208, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(209, 209, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(210, 210, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(211, 211, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(212, 212, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(213, 213, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(214, 214, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(215, 215, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(216, 216, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(217, 217, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(218, 218, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(219, 219, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(220, 220, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(221, 221, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(222, 222, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(223, 223, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(224, 224, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(225, 225, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(226, 226, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(227, 227, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(228, 228, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(229, 229, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(230, 230, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(231, 231, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(232, 232, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(233, 233, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(234, 234, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(235, 235, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(236, 236, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(237, 237, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(238, 238, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(239, 239, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(240, 240, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(241, 241, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(242, 242, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(243, 243, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(244, 244, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(245, 245, '$2y$12$Azd4UDSk7bCNfRnXzOQMR.CYhiC.9xPAxCkGgxEMHxbu55ZA/meqG', NULL, NULL, '7ca81a4fd67fd278f4ad06f31442f50ff27d0725777414c4b3589be548c79a70', '2025-12-12 09:39:40', '2025-11-17 07:28:25', '2025-12-11 01:39:40', '2025-11-24 11:11:39'),
(246, 246, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(247, 247, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(248, 248, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(249, 249, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(250, 250, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(251, 251, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(252, 252, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(253, 253, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(254, 254, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(255, 255, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(256, 256, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(257, 257, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(258, 258, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(259, 259, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(260, 260, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(261, 261, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(262, 262, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(263, 263, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(264, 264, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(265, 265, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(266, 266, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(267, 267, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(268, 268, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(269, 269, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(270, 270, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(271, 271, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(272, 272, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(273, 273, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(274, 274, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(275, 275, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(276, 276, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(277, 277, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(278, 278, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(279, 279, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(280, 280, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(281, 281, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(282, 282, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(283, 283, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(284, 284, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(285, 285, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(286, 286, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(287, 287, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(288, 288, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(289, 289, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(290, 290, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(291, 291, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(292, 292, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(293, 293, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(294, 294, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(295, 295, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33');
INSERT INTO `login` (`id_login`, `id_staf`, `password_hash`, `otp_code`, `otp_expiry`, `reset_token`, `reset_token_expiry`, `created_at`, `updated_at`, `tarikh_tukar_katalaluan`) VALUES
(296, 296, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(297, 297, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(298, 298, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(299, 299, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(300, 300, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(301, 301, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(302, 302, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(303, 303, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(304, 304, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(305, 305, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(306, 306, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(307, 307, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(308, 308, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(309, 309, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(310, 310, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(311, 311, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(312, 312, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(313, 313, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(314, 314, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(315, 315, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(316, 316, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(317, 317, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(318, 318, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(319, 319, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(320, 320, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(321, 321, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(322, 322, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(323, 323, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(324, 324, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(325, 325, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(326, 326, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(327, 327, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(328, 328, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(329, 329, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(330, 330, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(331, 331, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(332, 332, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(333, 333, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(334, 334, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(335, 335, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(336, 336, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(337, 337, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(338, 338, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(339, 339, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(340, 340, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(341, 341, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(342, 342, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(343, 343, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(344, 344, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(345, 345, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(346, 346, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(347, 347, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(348, 348, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(349, 349, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(350, 350, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(351, 351, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(352, 352, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(353, 353, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(354, 354, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(355, 355, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(356, 356, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(357, 357, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(358, 358, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(359, 359, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(360, 360, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(361, 361, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(362, 362, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(363, 363, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(364, 364, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(365, 365, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(366, 366, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(367, 367, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(368, 368, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(369, 369, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(370, 370, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(371, 371, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(372, 372, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(373, 373, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(374, 374, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(375, 375, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(376, 376, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(377, 377, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(378, 378, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(379, 379, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(380, 380, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(381, 381, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(382, 382, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(383, 383, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(384, 384, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(385, 385, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(386, 386, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(387, 387, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(388, 388, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(389, 389, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(390, 390, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33'),
(391, 391, '[$2y$10$d2ZM14QC.TB2GDh.lgh0MOGeCAoeVSr/UjoGdpz3PI1KE4NSpMiiK]', NULL, NULL, NULL, NULL, '2025-11-17 07:28:25', '2025-11-17 07:28:25', '2025-11-23 10:18:33');

-- --------------------------------------------------------

--
-- Table structure for table `staf`
--

CREATE TABLE `staf` (
  `id_staf` int(11) NOT NULL,
  `no_staf` varchar(10) DEFAULT NULL,
  `no_kp` varchar(12) DEFAULT NULL,
  `nama` varchar(255) DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `emel` varchar(100) DEFAULT NULL,
  `telefon` varchar(20) DEFAULT NULL,
  `id_jawatan` int(11) DEFAULT NULL,
  `id_gred` int(11) DEFAULT NULL,
  `id_bahagian` int(11) DEFAULT NULL,
  `id_status` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `staf`
--

INSERT INTO `staf` (`id_staf`, `no_staf`, `no_kp`, `nama`, `gambar`, `emel`, `telefon`, `id_jawatan`, `id_gred`, `id_bahagian`, `id_status`) VALUES
(1, '004278', '660403026023', 'DZULKARNAIN BIN ABDUL MANAF', NULL, 'dzulkarnain@keda.gov.my', NULL, 29, 8, 23, 1),
(2, '004391', '660528025403', 'MOHAMMAD RADHI BIN SAHAN', NULL, 'radhisahan@keda.gov.my', NULL, 29, NULL, 15, 1),
(3, '004421', '680327025499', 'OTHMAN BIN HASHIM', NULL, 'othman@keda.gov.my', NULL, 29, NULL, 9, 1),
(4, '004561', '740226025694', 'HASHALIZA BINTI HASHIM', NULL, 'hashaliza@keda.gov.my', NULL, 38, NULL, 4, 1),
(5, '004571', '660826025727', 'AHMAD BIN ABDUL LATEH', NULL, 'ahmad@keda.gov.my', '', 30, 6, 8, 1),
(6, '004588', '771003025752', 'NUR AFZAN BINTI AHMAD', NULL, 'afzan@keda.gov.my', NULL, 15, NULL, 22, 1),
(7, '004596', '711004025231', 'KHAIRUL ANUAR BIN MAHAMUD', NULL, 'khairul7110@gmail.com', NULL, 40, NULL, 8, 1),
(8, '004618', '670427025585', 'SHABUDIN BIN HUSIN', NULL, 'shabudin@gmail.com', NULL, 21, NULL, 8, 1),
(9, '004669', '690921025239', 'ADNAN BIN DARUS', NULL, 'adnan@keda.gov.my', NULL, 1, 12, 4, 1),
(10, '004685', '710606026093', 'ABDUL HALIM BIN MIHAT', NULL, 'abdulhalim@keda.gov.my', '', 30, 6, 8, 1),
(11, '004693', '740727025406', 'NOR ASMIDA BINTI HASSAN', '693944695f385.jpg', 'asmida@keda.gov.my', '', 33, 6, 29, 1),
(12, '004758', '790811085597', 'MOHD SYAHRIZAN BIN ZAINOL', NULL, 'jan6919@icloud.com', NULL, 24, NULL, 11, 1),
(13, '004782', '760129025738', 'ZAHRONI BINTI AYOB', NULL, 'zahroni@keda.gov.my', NULL, 16, NULL, 8, 1),
(14, '004804', '700826025202', 'NOOR YATI BINTI DIN', NULL, 'yati@keda.gov.my', NULL, 26, NULL, 5, 1),
(15, '004839', '670305025579', 'ABU BAKAR BIN AHMAD', NULL, 'abu261426@gmail.com', '', 20, 1, 12, 1),
(16, '004871', '760701025262', 'ZAIDA BINTI IBRAHIM', NULL, 'zaida@keda.gov.my', NULL, 26, NULL, 26, 1),
(17, '004898', '791220025028', 'NORAINI BINTI MOHD JAWI', NULL, 'norainijawi@keda.gov.my', NULL, 22, NULL, 1, 1),
(18, '004911', '761010016476', 'AIDA SUZANI BINTI ISHAK', NULL, 'suzani@keda.gov.my', '', 22, 2, 1, 1),
(19, '004928', '680420025397', 'MOHD AZREN BIN ABDULLAH', NULL, 'mohdazren224@gmail.com', NULL, 20, NULL, 24, 1),
(20, '004936', '801106025004', 'SITI KHALIJAH BINTI ABDUL RAZAK', NULL, 'khalijah@keda.gov.my', NULL, 26, NULL, 12, 1),
(21, '004944', '800313025010', 'SITI ZURAIDA BINTI GHAZALI', NULL, 'zuraida@keda.gov.my', NULL, 15, NULL, 8, 1),
(22, '004952', '780901025572', 'MAZALINA BINTI ISMAIL', NULL, 'mazalina@keda.gov.my', NULL, 29, NULL, 1, 1),
(23, '004961', '740416026225', 'ZAIDI BIN AHMAD', NULL, 'zaidi@keda.gov.my', NULL, 29, NULL, 16, 1),
(24, '004979', '791219025311', 'ASLAN BIN CHE ISHAK', NULL, 'aslancheishak5311@gmail.com', NULL, 24, NULL, 5, 1),
(25, '004987', '810626025336', 'SURYATI BINTI SALLEH', NULL, 'suryati@keda.gov.my', NULL, 26, NULL, 6, 1),
(26, '004995', '790809025800', 'ROHAIZA BINTI HARON', NULL, 'rohaiza@keda.gov.my', NULL, 26, NULL, 6, 1),
(27, '005029', '831104025868', 'ASMAWIYAH BINTI ABDUL GHONI', NULL, 'asmaabdghoni@yahoo.com', NULL, 24, NULL, 1, 1),
(28, '005053', '781111025548', 'ROSIMAH BINTI DARUS', NULL, 'rosimah@keda.gov.my', NULL, 29, NULL, 13, 1),
(29, '005061', '760914025397', 'MOHD ZAHIR BIN HASAN', NULL, 'mohdzahir@keda.gov.my', NULL, 10, NULL, 2, 1),
(30, '005088', '760917025552', 'NORDIA BINTI HARUN', NULL, 'nordia@keda.gov.my', NULL, 29, NULL, 22, 1),
(31, '005096', '790805025256', 'SITI NORIAH BINTI TALIB', NULL, 'sitinoriah@keda.gov.my', NULL, 41, NULL, 20, 1),
(32, '005118', '700930025472', 'NORZAITUL BINTI ABDUL RASHID', NULL, 'norzaitul@keda.gov.my', NULL, 22, NULL, 6, 1),
(33, '005126', '810326025246', 'NURASYIKIN BINTI AZIZAN', NULL, 'syikin@keda.gov.my', '', 33, 6, 8, 1),
(34, '005151', '740602075282', 'AZIZAH BINTI BAKAR', NULL, 'azizah@keda.gov.my', NULL, 27, NULL, 2, 1),
(35, '005177', '760526026064', 'AFIFAH BINTI OMAR', NULL, 'afifah@keda.gov.my', '', 37, 15, 18, 1),
(36, '005193', '811119146218', 'HASLIA BINTI ABU HASAN', NULL, 'haslia@keda.gov.my', '', 9, 10, 7, 1),
(37, '005207', '810125025418', 'NOR JUHAINI BINTI JAMALUDIN', NULL, 'norjuhaini@keda.gov.my', NULL, 9, NULL, 6, 1),
(38, '005215', '830802025158', 'YUSNIDA BINTI YUSOFF', NULL, 'yusnida@keda.gov.my', NULL, 9, NULL, 1, 1),
(39, '005231', '820515025615', 'MUHAMMAD NOORHISHAM BIN TAIB', NULL, 'noorhisham@keda.gov.my', NULL, 29, NULL, 17, 1),
(40, '005258', '830113025638', 'MARDIANA BINTI JAMIL', NULL, 'mardiana@keda.gov.my', NULL, 29, NULL, 6, 1),
(41, '005266', '800809135432', 'SABTUYAH BINTI BUJANG', NULL, 'sabtuyah@keda.gov.my', NULL, 29, NULL, 1, 1),
(42, '005274', '821219025236', 'NOR IZANI BINTI MIDON', NULL, 'izani@keda.gov.my', NULL, 22, NULL, 15, 1),
(43, '005282', '840807025674', 'FADZLINA BINTI AHMAD FADZIL', NULL, 'fadzlina@keda.gov.my', NULL, 29, NULL, 6, 1),
(44, '005291', '800528025413', 'MUHAMMAD NASIR BIN ABDULLAH', NULL, 'muhdnasir@keda.gov.my', NULL, 22, NULL, 30, 1),
(45, '005304', '820206025208', 'SHUHAILA BINTI HAMZAH', '693a2479ba8c5.jpg', 'shuhaila@keda.gov.my', '', 10, 12, 5, 1),
(46, '005339', '731027025486', 'SARINA BINTI OTHMAN', NULL, 'sarina@keda.gov.my', NULL, 29, NULL, 12, 1),
(47, '005347', '841128025466', 'SITI BALQHIS BINTI AHMAD', NULL, 'balqhis@keda.gov.my', NULL, 29, NULL, 7, 1),
(48, '005355', '831114025386', 'NURRATUL AINI BINTI ELIAS', NULL, 'nurratul_aini@keda.gov.my', NULL, 22, NULL, 27, 1),
(49, '005371', '770220025471', 'KAMARUDIN BIN OTHMAN', NULL, 'kamarudin.othman@keda.gov.my', NULL, 16, NULL, 8, 1),
(50, '005381', '810803025493', 'ZUKARNAI DAUD BIN SAVUR ALI', NULL, 'zukarnaidaud@keda.gov.my', NULL, 30, NULL, 8, 1),
(51, '005398', '800722025003', 'MOHD RIDZUAN BIN ABU BAKAR', NULL, 'ridzuan.abubakar@keda.gov.my', NULL, 16, NULL, 8, 1),
(52, '005401', '820318025544', 'NOORRAZEAN BINTI HUSSEIN', NULL, 'noorrazean@keda.gov.my', NULL, 16, NULL, 8, 1),
(53, '005411', '831106025343', 'MUHAMMAD IZHAM BIN IBRAHIM', NULL, 'izham@keda.gov.my', NULL, 27, NULL, 2, 1),
(54, '005436', '770620026166', 'SARINA BINTI NOR', NULL, 'sarinanor@keda.gov.my', NULL, 27, NULL, 2, 1),
(55, '005452', '840420025864', 'AZLINA BINTI ABDUL HAMID', NULL, 'azlina@keda.gov.my', NULL, 11, NULL, 2, 1),
(56, '005461', '850607026006', 'ROSLINA BINTI MALIM @ HALIM', NULL, 'roslina@keda.gov.my', NULL, 11, NULL, 2, 1),
(57, '005495', '851212025468', 'FUDZLA SURIA BINTI ABDUL KARIM', NULL, 'fudzlasuria@keda.gov.my', NULL, 26, NULL, 26, 1),
(58, '005533', '810130025308', 'AZLAILY BINTI AHMAD', NULL, 'azlaily@keda.gov.my', NULL, 38, NULL, 21, 1),
(59, '005541', '810609025334', 'ROHAZNIZA BINTI MOHAMD YUSOF', NULL, 'rohazniza@keda.gov.my', NULL, 41, NULL, 2, 1),
(60, '005568', '811017026151', 'MUHAMMAD FADZLY BIN JAUHARI', NULL, 'mfadzly@keda.gov.my', NULL, 2, NULL, 30, 1),
(61, '005576', '820427025703', 'MUHAMMAD ZUBIR BIN ZAKARIA', '6939443193d68.jpg', 'zubir@keda.gov.my', '', 4, 2, 29, 1),
(62, '005584', '840709025574', 'AZITA BINTI ISHAK', NULL, 'azita@keda.gov.my', NULL, 35, NULL, 26, 1),
(63, '005592', '740701026216', 'SUNITA BINTI YAHAYA', NULL, 'sunitayahaya@keda.gov.my', NULL, 26, NULL, 2, 1),
(64, '005614', '860616265008', 'ROKIAH BINTI MOHD ROZALI', NULL, 'rokiah@keda.gov.my', NULL, 26, NULL, 8, 1),
(65, '005622', '860318265202', 'SITI HAJAR HASMA BINTI RAMLI', NULL, 'sitihajarhasma@gmail.com', NULL, 24, NULL, 12, 1),
(66, '005631', '820304025447', 'SUHAIMI BIN AHMAD', NULL, 'suhaimiahmad7920@gmail.com', '', 24, 1, 20, 1),
(67, '005649', '760621025385', 'SYAMSYURI BIN OSMAN', NULL, 'syamssaigon76@gmail.com', NULL, 20, NULL, 14, 1),
(68, '005657', '751210025853', 'AFNIZAN AZWAN BIN AZIZAN', NULL, 'jan94721@gmail.com', '', 20, 1, 10, 1),
(69, '005673', '761118025673', 'KAMARUN ZAMAN BIN ABDULLAH', NULL, 'kamarunzaman307@gmail.com', NULL, 20, NULL, 13, 1),
(70, '005711', '790518025675', 'MOHD ZAMBRI BIN ISHAK', NULL, 'mohdzambri5675@gmail.com', NULL, 20, NULL, 24, 1),
(71, '005721', '760531025699', 'MOHD SHAMSUL BIN MOHD SHERIFF', NULL, 'tashijrah@gmail.com', NULL, 20, NULL, 24, 1),
(72, '005738', '761214025841', 'MOHD NAZRI BIN SHARIF', NULL, 'm.nazri7819@gmail.com', NULL, 20, NULL, 24, 1),
(73, '005746', '840126025193', 'MOHAMAD AZIM BIN MANSOR', NULL, 'azImansor49@gmail.com', NULL, 20, NULL, 24, 1),
(74, '005754', '660516025879', 'MOHD NOOR ARDI BIN HAMID', NULL, 'mohamadfaridzul23@gmail.com', NULL, 20, NULL, 15, 1),
(75, '005771', '670718025905', 'MAT KHIR BIN HUSSAIN', NULL, 'matkhirhussain18@gmail.com', NULL, 20, NULL, 6, 1),
(76, '005789', '820417095249', 'SYARILMIZAM BIN ABD AZIZ', NULL, 'syarilmizan82@gmail.com', NULL, 20, NULL, 24, 1),
(77, '005801', '800923025384', 'NURJEHAN BINTI MOHD NOOR', NULL, 'nurjehan@keda.gov.my', NULL, 26, NULL, 3, 1),
(78, '005819', '850207085904', 'AMIRA BINTI ISRORODIN', NULL, 'amiraisrorodin@keda.gov.my', '', 26, 2, 26, 1),
(79, '005835', '820318025202', 'YUSLINA BINTI AHMAD', NULL, 'yuslina@keda.gov.my', NULL, 41, NULL, 5, 1),
(80, '005843', '850624025128', 'NOORZIATUL AIDA BINTI AHMAD RADZI', NULL, 'aida@keda.gov.my', NULL, 26, NULL, 5, 1),
(81, '005851', '841027025520', 'IMIRA BINTI ISMAIL', NULL, 'imira@keda.gov.my', NULL, 26, NULL, 1, 1),
(82, '005861', '861211025190', 'NURUL AIN BINTI MANSOR', NULL, 'nurulain@keda.gov.my', NULL, 26, NULL, 7, 1),
(83, '005878', '850719025382', 'NUWAIRAH BINTI MUZAMIL', NULL, 'nuwairah@keda.gov.my', NULL, 26, NULL, 1, 1),
(84, '005894', '810402025433', 'MOHD KAMAL BIN HALIM', NULL, 'kamalsue7101@gmail.com', NULL, 20, NULL, 24, 1),
(85, '005932', '840131025243', 'MOHD ELIAS BIN HASSAN', NULL, 'elias@keda.gov.my', NULL, 10, NULL, 7, 1),
(86, '005941', '840604095007', 'KHAIRUL AZMEER BIN ABU BAKAR', NULL, 'azmeer@keda.gov.my', '', 10, 14, 19, 1),
(87, '005975', '810813025075', 'SABRI BIN SAIDIN', NULL, 'sabrisaidin2010@gmail.com', NULL, 21, NULL, 8, 1),
(88, '006009', '840222025388', 'AMINAH BINTI ISA', NULL, 'aminahisa300@gmail.com', '', 21, 1, 8, 1),
(89, '006033', '740710025995', 'NORAZHAR BIN AHMAD', NULL, 'artwave15@gmail.com', NULL, 20, NULL, 8, 1),
(90, '006084', '770310025387', 'HAMZANIE BIN ABD HAMID', NULL, 'hamzaniehamid1426@gmail.com', NULL, 20, NULL, 24, 1),
(91, '006106', '791130025411', 'ABDUL AZIZ BIN SAAD', NULL, 'abdaziz9003@gmail.com', '', 20, 1, 24, 1),
(92, '006114', '830327025801', 'SHAHURIN BIN MD ISA', NULL, 'shahurin@keda.gov.my', NULL, 29, NULL, 10, 1),
(93, '006122', '840614025061', 'MOHD HAFIZ BIN AHMAD', NULL, 'hafiz@keda.gov.my', NULL, 10, NULL, 11, 1),
(94, '006131', '800726065387', 'MUHAMAD RAMLAN BIN JAAFAR', NULL, 'ramlan@keda.gov.my', NULL, 10, NULL, 1, 1),
(95, '006149', '820108025351', 'MOHD AZAM BIN OMAR', NULL, 'mohdazam@keda.gov.my', NULL, 10, NULL, 1, 1),
(96, '006157', '800513025197', 'MOHD SUFFIAN BIN ABU BAKAR', NULL, 'suffian@keda.gov.my', NULL, 29, NULL, 3, 1),
(97, '006165', '850527025820', 'HASMAWATI BINTI AZIZ', '693943f42dd0c.jpg', 'hasmawati@keda.gov.my', '416', 33, 5, 29, 1),
(98, '006181', '820426025349', 'MOHAMAD NOORAZAM BIN JAAFAR', '693a31c85a916.jpg', 'azam@keda.gov.my', '0195793994', 7, 10, 29, 1),
(99, '006191', '820415025546', 'NOOR SHAFIZA BINTI HUSSAIN', NULL, 'noorshafiza@keda.gov.my', NULL, 1, NULL, 4, 1),
(100, '006203', '811213025527', 'MOHD FIRDAUS BIN AZIZAN @ ALIAZAM', NULL, 'firdaus@keda.gov.my', NULL, 29, NULL, 2, 1),
(101, '006221', '850905025300', 'NUR AZMIRA BINTI HARON', NULL, 'nur_azmira@keda.gov.my', NULL, 41, NULL, 7, 1),
(102, '006236', '850804025010', 'TENGKU SARA BINTI TENGKU FARIDA', NULL, 'tengkusara@keda.gov.my', NULL, 41, NULL, 18, 1),
(103, '006244', '861209025466', 'SITI HAJAR BINTI CHE HAMDAN', NULL, 'sitihajar@keda.gov.my', NULL, 38, NULL, 4, 1),
(104, '006275', '860822355303', 'MOHD FARHAN BIN NURDIN', NULL, 'farhan@keda.gov.my', NULL, 35, NULL, 5, 1),
(105, '006303', '830421025920', 'SITI NURHAFIZA BINTI RADZUAN', NULL, 'nurhafiza0083@gmail.com', NULL, 21, NULL, 5, 1),
(106, '006316', '870620026391', 'MUHAMMAD IZHAM BIN ABIDIN', NULL, 'muhammadizhamabidin87@gmail.com', NULL, 21, NULL, 5, 1),
(107, '006323', '920219025133', 'MUHD ZULHANIF BIN MAT ZUKI', NULL, 'zulhanif@keda.gov.my', NULL, 24, NULL, 30, 1),
(108, '006330', '820815146179', 'ZULFAZLI BIN HAMZAH', NULL, 'zulfazli@keda.gov.my', NULL, 26, NULL, 16, 1),
(109, '006347', '910816025714', 'FARAH HUSNA BINTI ZAINOL RASHID', NULL, 'qiefarah91@gmail.com', NULL, 21, NULL, 30, 1),
(110, '006354', '850415025797', 'MOHD NAZRI BIN MOHD NOR', NULL, 'mr.nazri85@gmail.com', NULL, 21, NULL, 5, 1),
(111, '006361', '830322025693', 'CHE RIDZUAN BIN MOHAMAD ISA', NULL, 'ridzuan@keda.gov.my', NULL, 10, NULL, 8, 1),
(112, '006385', '860216025566', 'NOOR AMIRA BINTI AHMAD SUKKRI', NULL, 'amira@keda.gov.my', NULL, 26, NULL, 2, 1),
(113, '006408', '850123025269', 'NURI ANDRI BIN ABDUL RAZAK', NULL, 'nuriandri@keda.gov.my', NULL, 29, NULL, 5, 1),
(114, '006415', '891011025222', 'SITI FARIDAH BINTI ISMAIL', NULL, 'sitifaridah@keda.gov.my', NULL, 26, NULL, 6, 1),
(115, '006422', '891030025017', 'MUHD ASYRAF HAFIZUDDIN BIN ABD RASID', NULL, 'asyrafhafizuddin@keda.gov.my', NULL, 26, NULL, 17, 1),
(116, '006439', '880725035437', 'MOHD HAZIZUL BIN HASSIM', NULL, 'hazizul@keda.gov.my', NULL, 26, NULL, 4, 1),
(117, '006446', '880616025555', 'MOHD ZAIRIZAL BIN MOHD ZAINOL', NULL, 'zairizal168@gmail.com', NULL, 21, NULL, 27, 1),
(118, '006460', '891217025092', 'HASLIFAH BINTI ISMAIL', NULL, 'haslifah@keda.gov.my', NULL, 26, NULL, 13, 1),
(119, '006477', '840803025677', 'SHAHROL ISWAN BIN FUDZIL', NULL, 'shahroliswaneja@gmail.com', NULL, 21, NULL, 13, 1),
(120, '006491', '770318026229', 'AZHARI BIN AHMAD', NULL, 'azhari@keda.gov.my', NULL, 30, NULL, 8, 1),
(121, '006507', '770805025768', 'NORHASHIMAH BINTI HASHIM', NULL, 'norhashimahh57.gmail.com', NULL, 16, NULL, 8, 1),
(122, '006514', '851029025005', 'ROJANI BIN ROMELI', NULL, 'rojani@keda.gov.my', NULL, 10, NULL, 2, 1),
(123, '006521', '830321025835', 'MOHD ARIFF BIN MOHD RODZI', NULL, 'mohdariff@keda.gov.my', NULL, 10, NULL, 3, 1),
(124, '006538', '830927025215', 'ANUAR BIN OMAR', NULL, 'anuar@keda.gov.my', NULL, 10, NULL, 15, 1),
(125, '006545', '870426095051', 'HASRUL HASNIZAM BIN SAAD', NULL, 'hasrul@keda.gov.my', NULL, 10, NULL, 25, 1),
(126, '006552', '860619265345', 'MOHD KHAIRUL BIN AHMAD', '693a24682c899.jpg', 'khairul@keda.gov.my', '', 10, 10, 5, 1),
(127, '006569', '660207025429', 'AYOB BIN WAHAB', NULL, 'ayobwahab1966@gmail.com', NULL, 21, NULL, 12, 1),
(128, '006576', '830107025711', 'MOHAMAD NOOR BIN LONG NIK', NULL, 'mohamadnoor@keda.gov.my', NULL, 29, NULL, 16, 1),
(129, '006583', '790413025839', 'MOHAMAD KABIR BIN ABD GHANI', NULL, 'mkabir@keda.gov.my', NULL, 28, NULL, 24, 1),
(130, '006590', '850208086255', 'SULAIMAN BIN MOHD NOOR', NULL, 'sulaiman@keda.gov.my', NULL, 9, NULL, 6, 1),
(131, '006613', '900227025477', 'MUHD SYAFIQ ZULFAQAR BIN ABDUL RAHMAN', NULL, 'syafiqz@keda.gov.my', NULL, 29, NULL, 6, 1),
(132, '006620', '880312025783', 'ABDUL AZIZ BIN ABDUL KHONI', NULL, 'azizkhoni@keda.gov.my', '', 26, 1, 5, 1),
(133, '006637', '851010075600', 'NORHANIZA BINTI HAMIN', NULL, 'hempedubumi85@gmail.com', NULL, 26, NULL, 15, 1),
(134, '006644', '870107025507', 'MOHAMAD YUSRI BIN ABD MUIN', NULL, 'yusri@keda.gov.my', NULL, 29, NULL, 15, 1),
(135, '006668', '860602025580', 'NOOR MAZZIEYANE BINTI MAZLAN', NULL, 'mazzieyane@keda.gov.my', NULL, 27, NULL, 2, 1),
(136, '006675', '881001265223', 'MOHAMAD SAYUTI BIN JAMAIN', NULL, 'sayuti@keda.gov.my', NULL, 27, NULL, 8, 1),
(137, '006699', '891120026101', 'ZULKIFLI BIN JAAFAR', NULL, 'zulkifli@keda.gov.my', NULL, 27, NULL, 2, 1),
(138, '006705', '861027025385', 'AZLAN BIN MANSOR', NULL, 'azlan@keda.gov.my', NULL, 27, NULL, 2, 1),
(139, '006712', '860828355507', 'MUHAMMAD HANIFF BIN ZAHERAN', NULL, 'm.haniff@keda.gov.my', NULL, 26, NULL, 23, 1),
(140, '006736', '901027025469', 'MOHAMMAD KUZRIN BIN ABD KUDUS', NULL, 'kuzrin@keda.gov.my', NULL, 26, NULL, 13, 1),
(141, '006750', '870301026036', 'NORAZILA BINTI CHE AWANG', NULL, 'norazila@keda.gov.my', NULL, 26, NULL, 10, 1),
(142, '006767', '881102265097', 'MOHD SYARIL BIN IDRIS', NULL, 'syaril@keda.gov.my', NULL, 22, NULL, 12, 1),
(143, '006774', '900926025409', 'MUHAMAD HAFIZUL BIN AZMI', NULL, 'hafizul@keda.gov.my', NULL, 29, NULL, 3, 1),
(144, '006811', '880306025592', 'AMIRAH BINTI ADIMAT', NULL, 'amirah@keda.gov.my', '', 38, 5, 21, 1),
(145, '006828', '890626025590', 'NORIZAYANI BINTI MAT RODZI', NULL, 'norizayani@keda.gov.my', NULL, 38, NULL, 8, 1),
(146, '006835', '890724025498', 'NURUL NOR ASHIKIN BINTI AHMAD HILMI', NULL, 'ashikin@keda.gov.my', NULL, 38, NULL, 4, 1),
(147, '006842', '910410025159', 'MOHAMMAD NIZAR BIN MOHAMAD AYOB', NULL, 'nizar@keda.gov.my', NULL, 38, NULL, 4, 1),
(148, '006859', '901213086409', 'THANASHILAN A/L MHOGAWAN', NULL, 'ppz8@keda.gov.my', NULL, 26, NULL, 3, 1),
(149, '006866', '890420025843', 'MOHAMAD ZAHERUDIN BIN KHAIRUN', NULL, 'zaherudin_ed@yahoo.com', NULL, 21, NULL, 11, 1),
(150, '006873', '900127025517', 'SHAREH AMIRUL AZRIEN B SHAREH ABD RAHMAN', NULL, 'amexdirah@gmail.com', NULL, 21, NULL, 5, 1),
(151, '006880', '891001025173', 'MOHD ZULFADZLI BIN ABU BAKAR', NULL, 'faliburns89@gmail.com', NULL, 21, NULL, 5, 1),
(152, '006897', '891105025629', 'ABDUL RAHMAN BIN ISMAIL', NULL, 'mankd89@icloud.com', '', 21, 1, 5, 1),
(153, '006903', '890315025729', 'KHAIROL SHAZWAN BIN HAJI ISMAIL', NULL, 'khairolshazwan9@gmail.com', NULL, 21, NULL, 5, 1),
(154, '006910', '900123025683', 'MUHAMMAD AFFRINI ADHAM BIN ISHAK', NULL, 'afriniadham90@gmail.com', NULL, 21, NULL, 1, 1),
(155, '006941', '860513025917', 'MUKHAIRI BIN MOKHTAR', NULL, 'mukhairi@keda.gov.my', NULL, 26, NULL, 27, 1),
(156, '006958', '851130025173', 'MOHD AFENDI BIN ABDUL GHANI', NULL, 'afendi@keda.gov.my', NULL, 26, NULL, 23, 1),
(157, '006965', '860618095049', 'KHAIRUL IDZHAM BIN MOHAMAD', NULL, 'idzham@keda.gov.my', NULL, 26, NULL, 24, 1),
(158, '006972', '870930026012', 'NURUL JAZILAH BINTI HAJI ABDULLAH', NULL, 'nuruljazilah@keda.gov.my', NULL, 29, NULL, 3, 1),
(159, '006989', '890119025895', 'MOHAMMAD HAFIIZ BIN MOHD FARIT', NULL, 'mohammadhafiiz@keda.gov.my', NULL, 29, NULL, 11, 1),
(160, '006996', '870816025497', 'MOHD AZMIR BIN NOR', NULL, 'mohdazmir080@gmail.com', NULL, 20, NULL, 24, 1),
(161, '007009', '870424025199', 'MUAZ BIN AHMAD', NULL, 'muaz13keda@yahoo.com', NULL, 20, NULL, 17, 1),
(162, '007016', '871115145407', 'KHAIRUL RIDHA BIN OTHMAN', NULL, 'khairulridha4472@gmail.com', NULL, 20, NULL, 3, 1),
(163, '007023', '860523025347', 'SYAIFUL NIZAR BIN ROSLAN', NULL, 'syaifulnizar21@gmail.com', NULL, 20, NULL, 24, 1),
(164, '007030', '850116025105', 'AHMAD SHAFFREN BIN ZAHER', NULL, 'ahmadshaffren@gmail.com', '', 20, 1, 9, 1),
(165, '007047', '850720025195', 'AZMI BIN MOHD ARIFF', NULL, 'azmiariff223@gmail.com', NULL, 20, NULL, 24, 1),
(166, '007054', '841229025043', 'MUHAMMAD SIDQI BIN ABD WAHAB', NULL, 'sidqi5043@gmail.com', NULL, 20, NULL, 24, 1),
(167, '007061', '900811025249', 'MOHAMAD ZULFAHMIE BIN CHE MAT', NULL, 'mohamadzulfahmie90@gmail.com', NULL, 21, NULL, 30, 1),
(168, '007078', '901003025501', 'MOHD FIKRI BIN SOBERI', NULL, 'fikrisoberi@keda.gov.my', NULL, 26, NULL, 2, 1),
(169, '007085', '921003025155', 'MOHD YAZZI BIN MOHD ABIDIN', NULL, 'yazzi@keda.gov.my', NULL, 29, NULL, 17, 1),
(170, '007092', '891005025859', 'MOHD YUSUF BIN MANSOR', NULL, 'yusof@keda.gov.my', NULL, 27, NULL, 2, 1),
(171, '007108', '900330025328', 'NUR ZATUL AALIA BINTI ROSLAN', NULL, 'aalia@keda.gov.my', NULL, 26, NULL, 25, 1),
(172, '007115', '890516026031', 'NORAZMAN BIN AYOB', NULL, 'norazman@keda.gov.my', NULL, 29, NULL, 6, 1),
(173, '007122', '830628025522', 'NUR IDAWATI BINTI ABDUL WAHAB', NULL, 'idawati@keda.gov.my', NULL, 10, NULL, 2, 1),
(174, '007153', '770806025399', 'HOUZALL OTHMAN BIN MOHD HASHIM', NULL, 'houzall@keda.gov.my', NULL, 10, NULL, 7, 1),
(175, '007160', '920711025100', 'WAN NOOR ADIBAH BINTI WAN AB RANI', NULL, 'adibah@keda.gov.my', NULL, 38, NULL, 8, 1),
(176, '007177', '880726025329', 'KHALID BIN ABDUL RAHMAN', NULL, 'khalid@keda.gov.my', NULL, 38, NULL, 23, 1),
(177, '007184', '851101025078', 'NOR HAFIZA BINTI ABU HASSAN', NULL, 'norhafiza@keda.gov.my', NULL, 38, NULL, 4, 1),
(178, '007191', '930530025020', 'SITI NORJALILAH BINTI MUSA', NULL, 'norjalilah@keda.gov.my', NULL, 38, NULL, 4, 1),
(179, '007207', '900919025566', 'NOR ADILA BINTI MD DHOHIR', NULL, 'nor_adila@keda.gov.my', NULL, 41, NULL, 8, 1),
(180, '007214', '871003025910', 'NOR AZIMAH BINTI AZLAN', NULL, 'azimah@keda.gov.my', NULL, 38, NULL, 4, 1),
(181, '007221', '900323025688', 'NORHAFIZAH BINTI MASHOR', NULL, 'hafizah@keda.gov.my', NULL, 29, NULL, 10, 1),
(182, '007238', '920925025026', 'NOOR HALINDA BINTI AWANG', NULL, 'halinda@keda.gov.my', NULL, 29, NULL, 11, 1),
(183, '007252', '940622025975', 'MOHD ZAKI BIN CHE ZAKARIA', NULL, 'zaki@keda.gov.my', NULL, 29, NULL, 25, 1),
(184, '007269', '870203025267', 'MOHD FAIZAL BIN ABD RAZAK', NULL, 'faizal@keda.gov.my', NULL, 22, NULL, 12, 1),
(185, '007276', '860425265451', 'MOHD ZUL AZIMAN BIN ARIFFIN', NULL, 'ziman2504@gmail.com', NULL, 21, NULL, 26, 1),
(186, '007283', '850510025189', 'ZULKIFLI BIN HASSAN', NULL, 'zul5189@gmail.com', NULL, 20, NULL, 24, 1),
(187, '007306', '890508025397', 'MUHAMMAD RIDHWAN BIN MOHD SUIB', NULL, 'ridhwansuib89@gmail.com', NULL, 21, NULL, 13, 1),
(188, '007313', '841220025717', 'MOHD AZRUL AMIN BIN ABU BAKAR', NULL, 'amin150515gmail.com', NULL, 20, NULL, 24, 1),
(189, '007337', '901201075577', 'MOHAMAD KHAIRI BIN SULAIMAN', NULL, 'khairisulaiman@keda.gov.my', NULL, 26, NULL, 14, 1),
(190, '007344', '900608025489', 'MOHD ZAMRIE BIN DIN', NULL, 'zamrie@keda.gov.my', NULL, 26, NULL, 26, 1),
(191, '007351', '931006025387', 'MUHAMMAD ALFI BIN MOHD FAUZI', NULL, 'alfi@keda.gov.my', NULL, 26, NULL, 24, 1),
(192, '007375', '901119026127', 'MOHAMAD HAFIZ BIN AHMAD KAMAL', NULL, 'mohamadhafiz@keda.gov.my', NULL, 26, NULL, 27, 1),
(193, '007382', '920512025204', 'NOOR SHAZWANI BINTI ABDUL RASHID', NULL, 'shazwani@keda.gov.my', NULL, 26, NULL, 5, 1),
(194, '007399', '890308025296', 'AINIE AFIDA BINTI MUAKTAR', NULL, 'ainieafida@keda.gov.my', '', 26, 1, 21, 1),
(195, '007412', '911231055197', 'MUHAMAD RIDHWAN BIN ZULKAFLI', NULL, 'ridhwan@keda.gov.my', NULL, 26, NULL, 22, 1),
(196, '007429', '850919025547', 'MOHD SHORIZAN BIN ZAMRI', NULL, 'shorizan@keda.gov.my', NULL, 29, NULL, 1, 1),
(197, '007436', '870201026485', 'MOHD ASRI BIN ABDULLAH', NULL, 'asriabdullah@keda.gov.my', NULL, 29, NULL, 6, 1),
(198, '007450', '830724025389', 'KHAIRUL SYAIFULNIZAM BIN KHAIRUL ANUAR', NULL, 'ksyaifulnizam@gmail.com', NULL, 20, NULL, 24, 1),
(199, '007467', '931003095187', 'MOHD HAIRIE BIN MOHD HASHIM', NULL, 'hairie@keda.gov.my', NULL, 29, NULL, 17, 1),
(200, '007474', '881110086267', 'MUHAMMAD SYAFIQ BUKHAIRY BIN ABDUL SAMAD', NULL, 'syafiqbukh@keda.gov.my', NULL, 22, NULL, 2, 1),
(201, '007481', '901101025249', 'MUZAMMIR BIN MOKHTAR', NULL, 'muzammir@keda.gov.my', NULL, 26, NULL, 26, 1),
(202, '007498', '910819025517', 'MUHAMMAD HANIF BIN HASSAN', NULL, 'hanif.cgs@gmail.com', NULL, 21, NULL, 10, 1),
(203, '007504', '870301095015', 'MOHD REZA AZRIL BIN RAHMAT', NULL, 'rezaazril87@gmail.com', NULL, 21, NULL, 3, 1),
(204, '007511', '890119025473', 'MOHD FAKRUULROZI BIN ZAINON', NULL, 'fakruul.jmckedah@gmail.com', NULL, 21, NULL, 5, 1),
(205, '007528', '890702025016', 'NOR SYADILAH BINTI SUDIN', NULL, 'norsyadilah@keda.gov.my', NULL, 26, NULL, 14, 1),
(206, '007535', '840507025998', 'SITI HAZATUL BINTI KAMIS', NULL, 'hazatul@keda.gov.my', NULL, 26, NULL, 1, 1),
(207, '007542', '910628025645', 'MUHAMMAD TAUFIK BIN MOHD SAFORIDIN', NULL, 'taufik@keda.gov.my', NULL, 29, NULL, 12, 1),
(208, '007559', '921026025457', 'NUR AHMAD SYATIR BIN NOOR AZRI', NULL, 'syatir@keda.gov.my', NULL, 22, NULL, 11, 1),
(209, '007573', '891230025775', 'ABDUL MU\'MIN BIN MOHD ROZI', NULL, 'mu\'min@keda.gov.my', '', 35, 5, 30, 1),
(210, '007597', '880301265167', 'MUHAMMAD MUAZ MUMTAZ BIN MARZUKI', NULL, 'muazmumtaz@keda.gov.my', NULL, 26, NULL, 1, 1),
(211, '007610', '880812086793', 'AHMAD TARMIZI BIN ABDUL AZIZ', NULL, 'tarmizi@keda.gov.my', '', 29, 5, 13, 1),
(212, '007627', '850302025299', 'MOHD RAHIMI BIN AZMI', NULL, 'rahimi@keda.gov.my', NULL, 29, NULL, 3, 1),
(213, '007641', '930716025803', 'MUHAMMAD AFIF BIN SHOBRI', NULL, 'afifshobri@keda.gov.my', NULL, 22, NULL, 3, 1),
(214, '007665', '871204025183', 'MUHAMMAD SHAUQI BIN MD KASSIM', NULL, 'nisashauqi8791@gmail.com', NULL, 20, NULL, 24, 1),
(215, '007672', '861219025259', 'MUHAMAD FADLI BIN YAN @ EBAU', NULL, 'muhamadfadliyanebau@gmail.com', NULL, 20, NULL, 24, 1),
(216, '007689', '850417026275', 'MOHD FAZLIZAN BIN HASHIM', NULL, 'semoh2906@gmail.com', NULL, 21, NULL, 5, 1),
(217, '007696', '911118025383', 'MUHAMMAD SYAMIL BIN HAMBALI', NULL, 'muhdsyamil776@gmail.com', NULL, 24, NULL, 7, 1),
(218, '007719', '880131265251', 'KHAIRUL ANWAR BIN AZIZAN', NULL, 'khairulanwar@keda.gov.my', NULL, 10, NULL, 3, 1),
(219, '007726', '901015025621', 'KHAIRUL RIDHWAN BIN ABD AZIZ @ ABD RAZAK', NULL, 'khairulridhwan@keda.gov.my', '', 9, 9, 16, 1),
(220, '007733', '821206025130', 'NORYAKIN BINTI ABD RAHMAN', NULL, 'noryakin@keda.gov.my', NULL, 27, NULL, 2, 1),
(221, '007740', '901214025210', 'NADIA AMIRA BINTI ZAKARIA', NULL, 'nadiaamira@keda.gov.my', NULL, 35, NULL, 7, 1),
(222, '007757', '920928025953', 'MUHAMMAD HUSAINI BIN SHUKRI', '693944117a2c4.jpg', 'husainishukri@keda.gov.my', '', 4, 1, 29, 1),
(223, '007764', '910228025195', 'MOHD HAFIZUDDIN BIN MOHD ASRI', NULL, 'hafizuddinasri@keda.gov.my', NULL, 3, NULL, 21, 1),
(224, '007771', '890505115725', 'SYED MUHAMMAD ADAM BIN SYD ABDUL RAHMAN', NULL, 'syedadam@keda.gov.my', NULL, 10, NULL, 30, 1),
(225, '007788', '921012025683', 'MUHAMMAD ASHRAF BIN RADZUAN', NULL, 'ashraf@keda.gov.my', NULL, 27, NULL, 2, 1),
(226, '007795', '911210025271', 'MUHAMMAD HAKIM BIN AHMAD NADZIR', NULL, 'hakim@keda.gov.my', NULL, 9, NULL, 14, 1),
(227, '007801', '940628025415', 'MOHAMAD SHAFIQ BIN MD YAHAYA', NULL, 'shafiqmdyahaya@keda.gov.my', NULL, 39, NULL, 2, 1),
(228, '007818', '900224025279', 'MUHAMMAD RIFQI BIN ZULKIFFLI', NULL, 'rifqi@keda.gov.my', NULL, 10, NULL, 26, 1),
(229, '007825', '930327025965', 'MUHAMMAD SYAFIQ BIN SAIFURIZAL', NULL, 'syafiq@keda.gov.my', NULL, 35, NULL, 26, 1),
(230, '007832', '930405025937', 'MUHAMAD ZAMRI BIN MD REJAB', NULL, 'zamri@keda.gov.my', NULL, 27, NULL, 2, 1),
(231, '007849', '890405026346', 'ZATUN NAJAHAH BINTI MUHAMAD HUSIN', NULL, 'zatunnajahah@keda.gov.my', NULL, 41, NULL, 1, 1),
(232, '007856', '880114265153', 'MUHAMMAD SYAFIQ BIN A RASHID', NULL, 'syafiqrashid@gmail.com', NULL, 17, NULL, 8, 1),
(233, '007863', '850907075002', 'FARHANA BINTI MD YUSOF', NULL, 'farhana_mdyusof85@yahoo.com', NULL, 17, NULL, 8, 1),
(234, '007870', '840324025530', 'NORAMALINA BINTI MAT ZAIN', NULL, 'linkhairul84@gmail.com', NULL, 17, NULL, 8, 1),
(235, '007887', '891102025914', 'NAJAH SALAM BINTI IBRAHIM', NULL, 'najahibrahim89@gmail.com', NULL, 17, NULL, 8, 1),
(236, '007894', '881031265486', 'SITI FATIMAH BINTI ABD KARIM', NULL, 'fatimah265486@gmail.com', NULL, 17, NULL, 8, 1),
(237, '007900', '890718025467', 'MOHD YASSER BIN ISMAIL', NULL, 'yasserismail71@yahoo.com', NULL, 17, NULL, 8, 1),
(238, '007917', '891216026093', 'MUHAMMAD ZAIMRAN BIN ROMAT YARI', NULL, 'an893303@gmail.com', NULL, 17, NULL, 8, 1),
(239, '007924', '880809025084', 'NURHAYATI BINTI MOHD RADZI', NULL, 'radzieyatie.yr@gmail.com', NULL, 17, NULL, 8, 1),
(240, '007931', '850709025602', 'KHAIRUL WAZNI BINTI HJ. ABDULLAH', NULL, 'khairulwazni51@gmail.com', NULL, 17, NULL, 8, 1),
(241, '007979', '881104265066', 'SITI HANIAH BINTI NOOR SAMIRI', NULL, 'hanieysamiri88@gmail.com', NULL, 14, NULL, 8, 1),
(242, '007986', '880921086856', 'NURUL AZREEN BINTI AZMI', NULL, 'erienazmi88@gmail.com', NULL, 12, NULL, 8, 1),
(243, '007993', '920319025237', 'MOHD AMIRULASYRAF BIN ABDUL RAHMAN', NULL, 'amirul@keda.gov.my', NULL, 34, NULL, 2, 1),
(244, '008006', '931103025565', 'MUHAMMAD FAIZ HELMI BIN SHUKRI', NULL, 'faizhelmi@keda.gov.my', NULL, 5, NULL, 2, 1),
(245, '008013', '910301025627', 'MUHAMMAD AFIQ BIN A. WAHAB', '693a4c820f492.jpg', 'afiq.wahab@keda.gov.my', '', 7, 9, 29, 1),
(246, '008020', '910807025130', 'SITI ROHAYA BINTI ABDUL RAHMAN', NULL, 'rohaya@keda.gov.my', NULL, 26, NULL, 5, 1),
(247, '008037', '911208026080', 'MUNIRAH BINTI ABDUL RAHMAN', NULL, 'munirah@keda.gov.my', NULL, 26, NULL, 2, 1),
(248, '008044', '920131025243', 'MOHD SYAFIQ BIN ABDUL RAZAK', NULL, 'mohdsyafiqrazak@keda.gov.my', NULL, 44, NULL, 5, 1),
(249, '008051', '870929025645', 'MOHAMAD KAMEL BIN ALIYASAK', NULL, 'mohmad.kamel88@yahoo.com', NULL, 17, NULL, 8, 1),
(250, '008068', '930721025461', 'MUHAMMAD ZAFRUDDIN BIN ZAINUDIN', NULL, 'zafruddin90@gmail.com', NULL, 17, NULL, 8, 1),
(251, '008075', '900529025405', 'MOHD AMIRUL IZZAT BIN ZULKIFLI', NULL, 'amirul_izzat@keda.gov.my', NULL, 26, NULL, 5, 1),
(252, '008082', '920212025245', 'MOHD HAFIRULIZHAM BIN HASSIM', NULL, 'hafirulizham@keda.gov.my', NULL, 26, NULL, 22, 1),
(253, '008099', '910128025008', 'NOR AZILAH BINTI RAMLI', NULL, 'azilah@keda.gov.my', NULL, 26, NULL, 1, 1),
(254, '008105', '940929025339', 'HAZWAN BIN BADRUNSHAM', NULL, 'hazwan@keda.gov.my', NULL, 26, NULL, 26, 1),
(255, '008112', '930317025232', 'SITI HALIMAH BINTI AZMI', NULL, 'sitihalimah@keda.gov.my', NULL, 26, NULL, 7, 1),
(256, '008129', '821104025439', 'MUHAMAD NAJIB BIN SOID', NULL, 'najib@keda.gov.my', NULL, 26, NULL, 22, 1),
(257, '008136', '921119025974', 'NUR SYAHIIRAH BINTI SABERI', NULL, 'syahiirah@keda.gov.my', NULL, 26, NULL, 2, 1),
(258, '008143', '930901025514', 'FALIYATUN ATIQAH BINTI NORDIN', NULL, 'faliyatun@keda.gov.my', NULL, 26, NULL, 6, 1),
(259, '008150', '871020026173', 'MUHAMAD SAIFULLAH BIN AHMAD', NULL, 'msananditeguh@gmail.com', NULL, 21, NULL, 3, 1),
(260, '008167', '940312025988', 'SITI NUR FITRAH BINTI ABD MALIK', NULL, 'fitrah@keda.gov.my', NULL, 29, NULL, 13, 1),
(261, '008174', '930809025474', 'NURLINA BINTI SHAHABUDDIN', NULL, 'nurlina@keda.gov.my', NULL, 29, NULL, 1, 1),
(262, '008181', '950601026118', 'NURUL NABILAH BINTI MANSOR', NULL, 'nabilah@keda.gov.my', NULL, 25, NULL, 26, 1),
(263, '008198', '931113025454', 'NOOR HIDAYAH BINTI HASBULLAH', NULL, 'hidayah@keda.gov.my', NULL, 25, NULL, 27, 1),
(264, '008204', '941228025162', 'AZMIRA BINTI ABDUL JAMIL', NULL, 'azmira@keda.gov.my', NULL, 25, NULL, 5, 1),
(265, '008211', '840213025925', 'MUHAMMAD KHAIRUL AKMAL BIN SAAD', NULL, 'taiko5925@gmail.com', NULL, 17, NULL, 8, 1),
(266, '008228', '920208025779', 'MUHAMAD AZMI BIN RAJUDIN', NULL, 'azmihitammaneh92@gmail.com', NULL, 17, NULL, 8, 1),
(267, '008235', '870104025667', 'AHMAD HAFEZ BIN AHMAD HAMZAH', NULL, 'cord1987@gmail.com', '', 12, 1, 8, 1),
(268, '008242', '841110025206', 'NOR ROSYADA BINTI ABD HALIM', NULL, 'norrosyada@gmail.com', NULL, 24, NULL, 18, 1),
(269, '008259', '980713025715', 'MOHAMMAD FIRDAUS BIN MOHD PAEIZAL', NULL, 'fierdausjr13@gmail.com', NULL, 24, NULL, 6, 1),
(270, '008266', '880902265373', 'SHAHRIL AMRI BIN ABDUL RAMAN', NULL, 'shahril.awie@gmail.com', NULL, 20, NULL, 16, 1),
(271, '008273', '950903025137', 'MOHD HAFIZ AZIM BIN SAAD', NULL, 'mohdhafizazimsaad95@gmail.com', NULL, 20, NULL, 24, 1),
(272, '008280', '910516025763', 'MUHAMMAD HAZWAN BIN HASIZAN', NULL, 'haswanhasizan@gmail.com', NULL, 20, NULL, 8, 1),
(273, '008297', '920102025172', 'SITI HAWA BINTI MANSUR', NULL, 'sitihawa@keda.gov.my', NULL, 26, NULL, 1, 1),
(274, '008303', '941218025759', 'MUHAMAD SYAZWI AFIF BIN HALIM', NULL, 'syazwi@keda.gov.my', NULL, 25, NULL, 4, 1),
(275, '008310', '920714025309', 'MOHAMAD IZHAM BIN ISMAIL', NULL, 'izhamismail@keda.gov.my', NULL, 25, NULL, 23, 1),
(276, '008327', '880311025319', 'MOHD NOOR FARZLI BIN ADENAN', NULL, 'farzli@keda.gov.my', '', 10, 9, 6, 1),
(277, '008334', '930111026205', 'MUHAMAD FIKHRI BIN ZULKIFLI', NULL, 'fikhri@keda.gov.my', NULL, 10, NULL, 8, 1),
(278, '008341', '940903026165', 'MOHAMMAD ALIFF BIN MOHD NAZIR', NULL, 'aliffnazir@keda.gov.my', NULL, 22, NULL, 14, 1),
(279, '008358', '921102025708', 'NAZURAH HANIS BINTI ZAINI', NULL, 'nazurah@keda.gov.my', NULL, 22, NULL, 6, 1),
(280, '008365', '931103025506', 'FARAH SALWA BINTI ABD RAHMAN', NULL, 'farahsalwa@keda.gov.my', NULL, 22, NULL, 1, 1),
(281, '008372', '950803025834', 'NOORLAILA BINTI IBRAHIM', NULL, 'noorlaila1995@gmail.com', NULL, 22, NULL, 15, 1),
(282, '008389', '930602025135', 'MOHD ANAS AFIFI BIN MANSOR', NULL, 'anasafifimansor@gmail.com', NULL, 21, NULL, 8, 1),
(283, '008396', '970406025863', 'MUHAMMAD MUSYAIRI EMIR BIN MUHAMMAD FAUZI', NULL, 'musyairiemir@keda.gov.my', NULL, 22, NULL, 6, 1),
(284, '008402', '921013025095', 'MOHD NABIL BIN JAFFRI', NULL, 'nabil@keda.gov.my', NULL, 9, NULL, 6, 1),
(285, '008419', '910215025874', 'NOOR SYAFINA BINTI SHUIB', NULL, 'noorsyafina@keda.gov.my', NULL, 43, NULL, 6, 1),
(286, '008426', '890218265069', 'MUHAMMAD ADIB KHAIRI BIN AHMAD RADZI', NULL, 'adibkhairi@keda.gov.my', NULL, 29, NULL, 6, 1),
(287, '008433', '931021025799', 'MOHD ZARITH FAZWAN BIN ZAKARIA', NULL, 'zarithfazwan@keda.gov.my', NULL, 29, NULL, 3, 1),
(288, '008440', '970203025652', 'FARAH DINA BINTI RAZALI', NULL, 'farahdinarazali79@gmail.com', NULL, 22, NULL, 15, 1),
(289, '008457', '940403025633', 'MOHD AFZAINIZAM BIN AZAMI', NULL, 'afzainizam@keda.gov.my', NULL, 22, NULL, 6, 1),
(290, '008464', '950622025632', 'AMIRAH BINTI AHMAD', NULL, 'amirahahmad@keda.gov.my', '', 22, 1, 3, 1),
(291, '008471', '920927025046', 'NURUL AMALINA BINTI ARISHAD', NULL, 'amalina@keda.gov.my', NULL, 22, NULL, 16, 1),
(292, '008488', '960403025404', 'NUR AMANINA BINTI MOHAMAD BAKRI', NULL, 'amanina@keda.gov.my', NULL, 22, NULL, 6, 1),
(293, '008495', '960228025123', 'ABDUL HADI BIN ABDUL HALIM', NULL, 'hadi@keda.gov.my', '', 22, 1, 9, 1),
(294, '008501', '980716025796', 'NUR TAUHIDAH HAKIMI BINTI ROSLI', NULL, 'tauhidah@keda.gov.my', NULL, 22, NULL, 13, 1),
(295, '008518', '970503025291', 'MOHAMAD ABDUL KHALIL BIN ABDULLAH', NULL, 'khainventionfurniture@gmail.com', NULL, 22, NULL, 10, 1),
(296, '008525', '910203025084', 'SYAIDAH ROSLINA YOM BINTI OSLAN', NULL, 'roslinayom@keda.gov.my', NULL, 22, NULL, 14, 1),
(297, '008532', '941123025318', 'NUR INANI BINTI ZULKAFLI', NULL, 'nanikeda@keda.gov.my', NULL, 22, NULL, 11, 1),
(298, '008549', '950117025316', 'NURUL IZZATI BINTI DARUL \'ALUDIN', NULL, 'izzati@keda.gov.my', NULL, 42, NULL, 4, 1),
(299, '008556', '931004026124', 'THOHIBAH BINTI RAZALI', NULL, 'thohibah@keda.gov.my', NULL, 26, NULL, 26, 1),
(300, '008563', '930804025280', 'NOOR LIYANA BINTI KASHIM', NULL, 'liyana.kashim@keda.gov.my', NULL, 26, NULL, 7, 1),
(301, '008570', '890902025942', 'KHAIRUL NISAK BT MOHD YUSOF', NULL, 'khairulnisak@keda.gov.my', NULL, 26, NULL, 5, 1),
(302, '008587', '920303025590', 'NAZIRAHANIM BINTI A BARI', NULL, 'nazira@keda.gov.my', NULL, 26, NULL, 4, 1),
(303, '008594', '920320025226', 'NURUL IWANI BINTI MOHD SALLEH', NULL, 'iwani@keda.gov.my', NULL, 26, NULL, 2, 1),
(304, '008600', '950106025863', 'MUHAMAD ZAIEM BIN JOHARI', NULL, 'zaiem@keda.gov.my', NULL, 26, NULL, 12, 1),
(305, '008617', '950510025005', 'MOHD AMIRUL BIN MOHD ZAINAIM', NULL, 'amirulzainaim@keda.gov.my', NULL, 26, NULL, 27, 1),
(306, '008624', '921012025675', 'KHAIRUL IKHWAN BIN MOHD KASSIM', NULL, 'ikhwan@keda.gov.my', NULL, 26, NULL, 17, 1),
(307, '008631', '870720025652', 'KU NOOR FAIZAH BINTI KU MUSTAFA', NULL, 'faizah@keda.gov.my', NULL, 26, NULL, 30, 1),
(308, '008648', '870922026071', 'MOHD SYAHIR BIN SHARIF', NULL, 'ednida87@gmail.com', NULL, 17, NULL, 8, 1),
(309, '008655', '901214025739', 'MUHAMMAD SAIFUL BIN SAAD', NULL, 'epull146@gmail.com', NULL, 21, NULL, 12, 1),
(310, '008662', '891225026079', 'MOHD FAIZUL BIN MOHD FODZI', NULL, 'faizulerin891225@gmail.com', NULL, 21, NULL, 17, 1),
(311, '008679', '981108026458', 'SITI AINSHAH BT YUSUB', NULL, 'siti.ainshah@yahoo.com', NULL, 21, NULL, 8, 1),
(312, '008686', '920830025525', 'MOHD KHAIRUL AZMAN BIN ABDOL RAHAMAN', NULL, 'mkhazman8@gmail.com', NULL, 21, NULL, 5, 1),
(313, '008693', '920225025201', 'AIZARUDIN BIN ABDUL KHADIR', NULL, 'aizarudinaizarudin@gmail.com', '', 20, 1, 24, 1),
(314, '008709', '931013026361', 'MUHAMAD AFFAN BIN ZAINOL', NULL, 'affanzainol55@gmail.com', NULL, 20, NULL, 11, 1),
(315, '008716', '881107265213', 'MOHAMAD TAUFIK BIN RUSLI', NULL, 'mtaufik@keda.gov.my', NULL, 26, NULL, 3, 1),
(316, '008723', '950827025230', 'KHAIRUNNAJWA BINTI OTHMAN', NULL, 'najwa@keda.gov.my', NULL, 42, NULL, 4, 1),
(317, '008730', '891029026020', 'NURUL AQILAH BINTI KAMIS', NULL, 'nurulaqilah@keda.gov.my', NULL, 29, NULL, 8, 1),
(318, '008747', '940820055132', 'FATIN NABILAH BINTI CHE OSMAN', NULL, 'fatin@keda.gov.my', NULL, 38, NULL, 27, 1),
(319, '008754', '950718026074', 'NUR AFIFAH BINTI ROSLI', NULL, 'afifahrosli@keda.gov.my', NULL, 22, NULL, 16, 1),
(320, '008761', '940824025324', 'NOREDAWATI BINTI AZIZAN', NULL, 'noredawati@keda.gov.my', NULL, 26, NULL, 6, 1),
(321, '008785', '910829025510', 'NORLIDA BINTI RAZALI', NULL, 'norlida@keda.gov.my', NULL, 43, NULL, 3, 1),
(322, '008792', '900908025288', 'NOOR SHAHIDA BINTI JAMIL', NULL, 'shahida@keda.gov.my', NULL, 10, NULL, 8, 1),
(323, '008815', '941224146039', 'MOHAMAD AMIN BIN ABDUL RAHIM', NULL, 'aminrahim@keda.gov.my', NULL, 10, NULL, 7, 1),
(324, '008816', '940531086385', 'MOHAMAD YUSRAN AZALI BIN MOHAMAD FADZIL', NULL, 'mohamadyusran@keda.gov.my', NULL, 10, NULL, 8, 1),
(325, '008817', '860907025709', 'HAIRUL NIZAM BIN CHE DAN', NULL, 'hairul@keda.gov.my', NULL, 27, NULL, 2, 1),
(326, '008818', '901220025559', 'MOHAMAD AFIQ ASHRAF BIN ABD HALIM', NULL, 'm.afiqashraf@keda.gov.my', NULL, 27, NULL, 2, 1),
(327, '008819', '871010025169', 'MUHAMAD FIRDAUS BIN AZIZAN', NULL, 'firdaus.azizan@keda.gov.my', NULL, 27, NULL, 2, 1),
(328, '008820', '931121025294', 'SITI NORLIDA BINTI ANI', NULL, 'sitinorlida@keda.gov.my', NULL, 27, NULL, 2, 1),
(329, '008821', '961104385017', 'MUHAMMAD RAFIQ IZZAT BIN RADZUAN', NULL, 'm.rafiqizzat@keda.gov.my', NULL, 38, NULL, 4, 1),
(330, '008822', '871101025922', 'NORHAFIZAH BINTI MOHD ISA', NULL, 'norhafizah@keda.gov.my', NULL, 29, NULL, 7, 1),
(331, '008823', '940901025695', 'HARIZ HAZWAN BIN ANUAR', NULL, 'hariz_hazwan94@yahoo.com', NULL, 29, NULL, 6, 1),
(332, '008824', '941008025451', 'AHMAD AZZIM ZUBEDY BIN SHAIKH SALIM', NULL, 'azzimzubedy@keda.gov.my', '', 25, 1, 4, 1),
(333, '008825', '980930026028', 'SITI NAJIHAH BINTI MOHD RODZI', NULL, 'siti.najihah@keda.gov.my', NULL, 25, NULL, 4, 1),
(334, '008826', '920816026281', 'MUHAMAD FAKHRI BIN MD SAMAN @ OSMAN', NULL, 'm.fakhri@keda.gov.my', NULL, 22, NULL, 6, 1),
(335, '008827', '900726025199', 'MOHAMMAD FARIDZ RIDZUAN BIN JOHARI', NULL, 'm.farridzridzuan@keda.gov.my', NULL, 22, NULL, 3, 1),
(336, '008828', '970606025905', 'MIZHAL AKMAL BIN MOHAMAD YUSOFF', NULL, 'mizhalakmal@keda.gov.my', NULL, 22, NULL, 12, 1),
(337, '008829', '920402025051', 'AIMAN KHADRI BIN MOHAMAD KHOTAIB', NULL, 'aimankhadri@keda.gov.my', '', 22, 1, 12, 1),
(338, '008830', '950608025628', 'FATIN FARHANA BINTI MD ISA', NULL, 'fatinfarhana@keda.gov.my', NULL, 26, NULL, 1, 1),
(339, '008831', '970515025483', 'NAIM HAFIFI BIN ABDUL HALIM', NULL, 'naimhafifi@keda.gov.my', NULL, 26, NULL, 15, 1),
(340, '008832', '971008025846', 'NOR SYAWANI BINTI ZAINAL ABIDIN', NULL, 'norsyawani@keda.gov.my', NULL, 26, NULL, 30, 1),
(341, '008833', '920523025015', 'FARIS IMADI BIN GHAZALI', NULL, 'farisimadi@keda.gov.my', NULL, 26, NULL, 3, 1),
(342, '008834', '990717055371', 'AMEERUL ZAFRAN BIN SAIFUL AMIR', NULL, 'ameerulzafran@keda.gov.my', '', 26, 1, 30, 1),
(343, '008835', '960622025236', 'FAIZNUR BINTI MOHAMAD RADZI', NULL, 'faiznur@keda.gov.my', NULL, 26, NULL, 10, 1),
(344, '008836', '950817075752', 'NORSHUHADA BINTI SALLEH', NULL, 'norshuhada@keda.gov.my', NULL, 26, NULL, 2, 1),
(345, '008837', '970811025269', 'MUHAMMAD FIRDAUS BIN ZULKIFLI', NULL, 'firdauszulkifli728@gmail.com', NULL, 24, NULL, 30, 1),
(346, '008838', '970513085722', 'NURUL AQILAH BINTI ISHAK', NULL, 'ielaqiela97@gmail.com', NULL, 24, NULL, 25, 1),
(347, '008839', '970921026078', 'MAISARAH BINTI ZAHARI', NULL, 'maisarahzahari21@gmail.com', '', 24, 1, 5, 1),
(348, '008840', '980805026109', 'MOHAMAD ZUL FAHMI BIN MOHD SABRI', NULL, 'zulfahmi@keda.gov.my', NULL, 24, NULL, 5, 1),
(349, '008841', '901001026009', 'MOHD AZROL BIN MAT RODZI', NULL, 'mohamadazrolmatrodzi@gmail.com', '', 20, 1, 10, 1),
(350, '008842', '950130025919', 'MOHAMAD NAZRUL BIN MOHD NAFISAL', NULL, 'mohamadnazrulnafisal@gmail.com', NULL, 20, NULL, 24, 1),
(351, '008843', '911011025033', 'MOHD SYAZWAN BIN ZAINON', NULL, 'msyazzainon@gmail.com', NULL, 20, NULL, 24, 1),
(352, '008844', '970118025535', 'MOHD SAIFUL NAZRI BIN MD NASIR', NULL, 'saifulnsr97@gmail.com', NULL, 21, NULL, 15, 1),
(353, '008845', '900122025323', 'ANAS FARHAN BIN ABU HASAN', NULL, 'anas@keda.gov.my', NULL, 42, NULL, 4, 1),
(354, '008846', '990607025315', 'ABDUL JALIL BIN MOHAMED SIDIK', NULL, 'abduljalilmohamedsidik@gmail.com', '', 22, 1, 10, 1),
(355, '008847', '970918025576', 'NURUL ANIS SURAYA BINTI SURAIMI', NULL, 'suraya@keda.gov.my', NULL, 25, NULL, 4, 1),
(356, '008848', '991107025348', 'NUR AMILIA BINTI ISMADI', NULL, 'nur_amilia@keda.gov.my', NULL, 43, NULL, 19, 1),
(357, '008849', '930804066023', 'MUHAMMAD LUQMAN BIN NAZARI', NULL, 'm.luqman@keda.gov.my', NULL, 10, NULL, 3, 1),
(358, '008850', '930524025067', 'MOHAMAD NASRI BIN AHMAD SHUKRI', NULL, 'm.nasri@keda.gov.my', NULL, 10, NULL, 13, 1),
(359, '008851', '881224025606', 'NOORHIDAYAH BINTI ZAKARIA', NULL, 'noorhidayah@keda.gov.my', NULL, 10, NULL, 27, 1),
(360, '008852', '961202055011', 'AHMAD MUSANIF BIN ABDULL MANAFF', NULL, 'a.musanif@keda.gov.my', '', 35, 5, 8, 1),
(361, '008855', '980727135536', 'AINA NADHIA BINTI MUHAMMAD ALI', NULL, 'anadhia27@gmail.com', '', 17, 1, 8, 1),
(362, '008856', '881001265442', 'SAFIAH NAFISAH BINTI MD SHARIF', NULL, 'afiasafiah@gmail.com', NULL, 17, NULL, 8, 1),
(363, '008857', '900222026163', 'FAIZ BIN SABRI', NULL, 'faiz.sabri@keda.gov.my', NULL, 16, NULL, 8, 1),
(364, '008858', '891010025263', 'MASWARI BIN MOHAMMAD RADZHI', NULL, 'maswari@keda.gov.my', NULL, 29, NULL, 10, 1),
(365, '008859', '930720025435', 'MUHAMMAD YUSUF BIN ROSLAN', NULL, 'yusufroslan@keda.gov.my', NULL, 29, NULL, 8, 1),
(366, '008860', '920417025599', 'MOHD ZULKIFLY BIN ISMAIL', NULL, 'zulkifly@keda.gov.my', NULL, 25, NULL, 27, 1),
(367, '008861', '891229025604', 'NURUL NADIA BINTI ABDULLAH', NULL, 'nadia@keda.gov.my', NULL, 26, NULL, 11, 1),
(368, '008862', '960713026293', 'MOHD IZZUL IKHWAN BIN SHOKHIMI', NULL, 'izzulikhwan10@gmail.com', NULL, 17, NULL, 8, 1),
(369, '008864', '981013027167', 'MUHAMMAD SHAHRUL NAIM BIN ZAHIR', NULL, 'shahrulnaim82.sn@gmail.com', NULL, 24, NULL, 2, 1),
(370, '008865', '940226025981', 'ABDUL RAZAK BIN GHAZALI', NULL, 'abdrazak@keda.gov.my', '', 27, 5, 2, 1),
(371, '008866', '970128025993', 'ARIF AIMAN BIN AHMAD', NULL, 'arifaiman@keda.gov.my', NULL, 26, NULL, 7, 1),
(372, '008867', '920915146633', 'MUHAMMAD ABRAR BIN ABD RAZAK', NULL, 'amaniaga92@gmail.com', NULL, 17, NULL, 8, 1),
(373, '008869', '940506025492', 'EZRIN BINTI AHMAD TARMIZI', NULL, 'ezrin@keda.gov.my', NULL, 1, NULL, 4, 1),
(374, '008870', '860114025725', 'MOHAMAD HELDI BIN ABDUL RAUF', NULL, 'mohdheldi@keda.gov.my', NULL, 29, NULL, 2, 1),
(375, '008871', '970801115973', 'ABDULLAH FAIZ BIN MOHD ZAIN', NULL, 'faizzain@keda.gov.my', '', 22, 1, 1, 1),
(376, '008872', '970707026069', 'MUHAMMAD \'IZZUDDIN BIN ISMAIL', NULL, 'izzuddin@keda.gov.my', NULL, 22, NULL, 17, 1),
(377, '008873', '000916020256', 'NUR IZZATUL SYIFFA BINTI SHAMSHURI', NULL, 'izzatul@keda.gov.my', NULL, 22, NULL, 11, 1),
(378, '008874', '010529020191', 'MUHAMMAD HAIKAL SAFWAN BIN HUZAIMIE', NULL, 'haikalsafwan@keda.gov.my', NULL, 22, NULL, 15, 1),
(379, '008875', '980109026447', 'MUHAMMAD SHAHID BIN MOHD FADZIL', NULL, 'muhdshahid@keda.gov.my', NULL, 22, NULL, 12, 1),
(380, '008876', '951020025656', 'NUR FATINI BINTI RAMLI', NULL, 'fatini@keda.gov.my', NULL, 22, NULL, 9, 1),
(381, '008877', '030616020884', 'NURZULAIKHA BINTI MAD ZAHIR', NULL, 'zulaikha@keda.gov.my', NULL, 26, NULL, 9, 1),
(382, '008878', '991022025277', 'MUHAMMAD IMRAN BIN MOHAMMAD ISHAMUDIN', NULL, 'imranishamudin@keda.gov.my', NULL, 26, NULL, 2, 1),
(383, '008879', '910319025553', 'MUHAMMAD ABDUL MUHAIMIN BIN IBRAHIM', NULL, 'muhaimin@keda.gov.my', NULL, 26, NULL, 5, 1),
(384, '008880', '000830021100', 'NUR FARADILLA NATASHA BINTI MOHD SHARON NIZAM', NULL, 'faranatasha@keda.gov.my', NULL, 26, NULL, 8, 1),
(385, '008881', '930924025605', 'MUHAMMMAD SAYUTI BIN MOHD LAZIM', NULL, 'ibnuzaba@yahoo.com', NULL, 21, NULL, 14, 1),
(386, '008882', '930812146357', 'MUHAMMAD ZAMZURY BIN MD ZNAN', NULL, 'zamzurywanie@gmail.com', NULL, 21, NULL, 5, 1),
(387, '008883', '960830025003', 'MUHAMAD MUSHRIF BIN MUKHTAR', NULL, 'rifmus1996@gmail.com', NULL, 21, NULL, 6, 1),
(388, '008886', '000919100884', 'SITI IRDINA SAFFIYA BINTI KHAIRIL FAIZI', '693944833b8ab.jpeg', 'irdina@keda.gov.my', '', 26, 1, 29, 1),
(389, '008890', '890325025503', 'MUHD NUR AZZAHARI BIN MOHD AZUDIN', NULL, 'azzahari@keda.gov.my', NULL, 29, NULL, 17, 1),
(390, '008891', '000924020803', 'MUHAMAD YASIR BIN SANUSI', NULL, 'yasir@keda.gov.my', NULL, 22, NULL, 6, 1),
(391, '008893', '990723126172', 'NUHA EEVANA BINTI MD SUHKRI', NULL, 'nuhasuhkri@keda.gov.my', NULL, 13, NULL, 22, 1);

--
-- Triggers `staf`
--
DELIMITER $$
CREATE TRIGGER `trg_staf_after_delete` AFTER DELETE ON `staf` FOR EACH ROW BEGIN
    INSERT INTO `audit` (id_pengguna, tindakan, nama_jadual, id_rekod, data_lama, data_baru)
    VALUES (
        @CURRENT_USER_ID,
        'DELETE',
        'staf',
        OLD.id_staf,
        JSON_OBJECT(
            'no_staf', OLD.no_staf,
            'no_kp', OLD.no_kp,
            'nama', OLD.nama,
            'emel', OLD.emel,
            'telefon', OLD.telefon,
            'id_jawatan', OLD.id_jawatan,
            'id_gred', OLD.id_gred,
            'id_bahagian', OLD.id_bahagian,
            'id_status', OLD.id_status
        ),
        NULL
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_staf_after_insert` AFTER INSERT ON `staf` FOR EACH ROW BEGIN
    INSERT INTO `audit` (id_pengguna, tindakan, nama_jadual, id_rekod, data_lama, data_baru)
    VALUES (
        @CURRENT_USER_ID,  -- ID pengguna yang di-set oleh aplikasi (rujuk nota di bawah)
        'INSERT',
        'staf',
        NEW.id_staf,
        NULL,
        JSON_OBJECT(
            'no_staf', NEW.no_staf,
            'no_kp', NEW.no_kp,
            'nama', NEW.nama,
            'emel', NEW.emel,
            'telefon', NEW.telefon,
            'id_jawatan', NEW.id_jawatan,
            'id_gred', NEW.id_gred,
            'id_bahagian', NEW.id_bahagian,
            'id_status', NEW.id_status
        )
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_staf_after_update` AFTER UPDATE ON `staf` FOR EACH ROW BEGIN
    -- Hanya log jika ada data yang betul-betul berubah
    IF NOT (
        (NEW.no_staf <=> OLD.no_staf) AND
        (NEW.no_kp <=> OLD.no_kp) AND
        (NEW.nama <=> OLD.nama) AND
        (NEW.emel <=> OLD.emel) AND
        (NEW.telefon <=> OLD.telefon) AND
        (NEW.id_jawatan <=> OLD.id_jawatan) AND
        (NEW.id_gred <=> OLD.id_gred) AND
        (NEW.id_bahagian <=> OLD.id_bahagian) AND
        (NEW.id_status <=> OLD.id_status)
    ) THEN
        INSERT INTO `audit` (id_pengguna, tindakan, nama_jadual, id_rekod, data_lama, data_baru)
        VALUES (
            @CURRENT_USER_ID,
            'UPDATE',
            'staf',
            NEW.id_staf,
            JSON_OBJECT(
                'no_staf', OLD.no_staf,
                'no_kp', OLD.no_kp,
                'nama', OLD.nama,
                'emel', OLD.emel,
                'telefon', OLD.telefon,
                'id_jawatan', OLD.id_jawatan,
                'id_gred', OLD.id_gred,
                'id_bahagian', OLD.id_bahagian,
                'id_status', OLD.id_status
            ),
            JSON_OBJECT(
                'no_staf', NEW.no_staf,
                'no_kp', NEW.no_kp,
                'nama', NEW.nama,
                'emel', NEW.emel,
                'telefon', NEW.telefon,
                'id_jawatan', NEW.id_jawatan,
                'id_gred', NEW.id_gred,
                'id_bahagian', NEW.id_bahagian,
                'id_status', NEW.id_status
            )
        );
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `status`
--

CREATE TABLE `status` (
  `id_status` int(11) NOT NULL,
  `status` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `status`
--

INSERT INTO `status` (`id_status`, `status`) VALUES
(1, 'MASIH BEKERJA'),
(2, 'TELAH BERSARA'),
(3, 'TELAH BERHENTI');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `akses`
--
ALTER TABLE `akses`
  ADD PRIMARY KEY (`id_akses`),
  ADD KEY `fk_akses_ke_aplikasi` (`id_aplikasi`),
  ADD KEY `fk_akses_ke_staf` (`id_staf`),
  ADD KEY `fk_akses_level` (`id_level`);

--
-- Indexes for table `aplikasi`
--
ALTER TABLE `aplikasi`
  ADD PRIMARY KEY (`id_aplikasi`),
  ADD KEY `fk_aplikasi_kategori` (`id_kategori`);

--
-- Indexes for table `audit`
--
ALTER TABLE `audit`
  ADD PRIMARY KEY (`id_audit`),
  ADD KEY `idx_nama_jadual_id_rekod` (`nama_jadual`,`id_rekod`),
  ADD KEY `idx_id_pengguna` (`id_pengguna`),
  ADD KEY `idx_waktu` (`waktu`);

--
-- Indexes for table `bahagian`
--
ALTER TABLE `bahagian`
  ADD PRIMARY KEY (`id_bahagian`);

--
-- Indexes for table `chatbot_faq`
--
ALTER TABLE `chatbot_faq`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gred`
--
ALTER TABLE `gred`
  ADD PRIMARY KEY (`id_gred`);

--
-- Indexes for table `jawatan`
--
ALTER TABLE `jawatan`
  ADD PRIMARY KEY (`id_jawatan`);

--
-- Indexes for table `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id_kategori`),
  ADD UNIQUE KEY `nama_kategori` (`nama_kategori`);

--
-- Indexes for table `level`
--
ALTER TABLE `level`
  ADD PRIMARY KEY (`id_level`);

--
-- Indexes for table `login`
--
ALTER TABLE `login`
  ADD PRIMARY KEY (`id_login`),
  ADD UNIQUE KEY `idx_id_staf_unique` (`id_staf`);

--
-- Indexes for table `staf`
--
ALTER TABLE `staf`
  ADD PRIMARY KEY (`id_staf`),
  ADD UNIQUE KEY `idx_no_staf` (`no_staf`),
  ADD KEY `fk_staf_ke_bahagian` (`id_bahagian`),
  ADD KEY `fk_staf_ke_status` (`id_status`),
  ADD KEY `fk_staf_ke_gred` (`id_gred`),
  ADD KEY `fk_staf_ke_jawatan` (`id_jawatan`);

--
-- Indexes for table `status`
--
ALTER TABLE `status`
  ADD PRIMARY KEY (`id_status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `akses`
--
ALTER TABLE `akses`
  MODIFY `id_akses` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `aplikasi`
--
ALTER TABLE `aplikasi`
  MODIFY `id_aplikasi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `audit`
--
ALTER TABLE `audit`
  MODIFY `id_audit` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `bahagian`
--
ALTER TABLE `bahagian`
  MODIFY `id_bahagian` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `chatbot_faq`
--
ALTER TABLE `chatbot_faq`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `gred`
--
ALTER TABLE `gred`
  MODIFY `id_gred` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `jawatan`
--
ALTER TABLE `jawatan`
  MODIFY `id_jawatan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id_kategori` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `level`
--
ALTER TABLE `level`
  MODIFY `id_level` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `login`
--
ALTER TABLE `login`
  MODIFY `id_login` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=392;

--
-- AUTO_INCREMENT for table `staf`
--
ALTER TABLE `staf`
  MODIFY `id_staf` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=392;

--
-- AUTO_INCREMENT for table `status`
--
ALTER TABLE `status`
  MODIFY `id_status` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `akses`
--
ALTER TABLE `akses`
  ADD CONSTRAINT `fk_akses_ke_aplikasi` FOREIGN KEY (`id_aplikasi`) REFERENCES `aplikasi` (`id_aplikasi`),
  ADD CONSTRAINT `fk_akses_ke_staf` FOREIGN KEY (`id_staf`) REFERENCES `staf` (`id_staf`),
  ADD CONSTRAINT `fk_akses_level` FOREIGN KEY (`id_level`) REFERENCES `level` (`id_level`) ON UPDATE CASCADE;

--
-- Constraints for table `aplikasi`
--
ALTER TABLE `aplikasi`
  ADD CONSTRAINT `fk_aplikasi_kategori` FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`) ON DELETE SET NULL;

--
-- Constraints for table `login`
--
ALTER TABLE `login`
  ADD CONSTRAINT `fk_login_ke_staf` FOREIGN KEY (`id_staf`) REFERENCES `staf` (`id_staf`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `staf`
--
ALTER TABLE `staf`
  ADD CONSTRAINT `fk_staf_ke_bahagian` FOREIGN KEY (`id_bahagian`) REFERENCES `bahagian` (`id_bahagian`),
  ADD CONSTRAINT `fk_staf_ke_gred` FOREIGN KEY (`id_gred`) REFERENCES `gred` (`id_gred`),
  ADD CONSTRAINT `fk_staf_ke_jawatan` FOREIGN KEY (`id_jawatan`) REFERENCES `jawatan` (`id_jawatan`),
  ADD CONSTRAINT `fk_staf_ke_status` FOREIGN KEY (`id_status`) REFERENCES `status` (`id_status`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
