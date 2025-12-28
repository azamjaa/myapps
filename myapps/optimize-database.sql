-- ============================================
-- MYAPPS KEDA: PERFORMANCE OPTIMIZATION
-- Add database indexes for faster queries
-- ============================================

-- Index untuk table staf (filtering & searching)
ALTER TABLE `staf` ADD INDEX `idx_status` (`id_status`);
ALTER TABLE `staf` ADD INDEX `idx_bahagian` (`id_bahagian`);
ALTER TABLE `staf` ADD INDEX `idx_jawatan` (`id_jawatan`);
ALTER TABLE `staf` ADD INDEX `idx_no_kp` (`no_kp`);
ALTER TABLE `staf` ADD INDEX `idx_nama` (`nama`);

-- Index untuk table aplikasi (dashboard widgets)
ALTER TABLE `aplikasi` ADD INDEX `idx_status` (`status`);
ALTER TABLE `aplikasi` ADD INDEX `idx_kategori` (`id_kategori`);
ALTER TABLE `aplikasi` ADD INDEX `idx_sso` (`sso_comply`);

-- Index untuk table login (authentication)
ALTER TABLE `login` ADD INDEX `idx_staf` (`id_staf`);
ALTER TABLE `login` ADD INDEX `idx_role` (`role`);

-- Index untuk table audit (activity feed)
ALTER TABLE `audit` ADD INDEX `idx_pengguna` (`id_pengguna`);
ALTER TABLE `audit` ADD INDEX `idx_created` (`created_at`);

-- Index untuk table akses (permissions)
ALTER TABLE `akses` ADD INDEX `idx_staf_aplikasi` (`id_staf`, `id_aplikasi`);

-- ============================================
-- VERIFY INDEXES
-- ============================================
SHOW INDEXES FROM staf;
SHOW INDEXES FROM aplikasi;
SHOW INDEXES FROM login;

