-- ========================================
-- MYAPPS KEDA: SETUP ADMIN & USER ROLES
-- Password untuk semua: Noor@z@m1982
-- Admin: 820426025349
-- ========================================

-- Step 1: Tambah column 'role' dalam table login
ALTER TABLE `login` 
ADD COLUMN `role` ENUM('admin', 'user') NOT NULL DEFAULT 'user' AFTER `password_hash`;

-- Step 2: Delete semua login records lama (untuk reset)
TRUNCATE TABLE `login`;

-- Step 3: Create login records untuk SEMUA staf dengan password standard
INSERT INTO `login` (`id_staf`, `password_hash`, `role`, `created_at`, `updated_at`)
SELECT 
    id_staf,
    '$2y$12$6GVcyRuDLCyscSEweVLEg.eJgDDhK/VgjhUxaFDw1AR7b5yvusXQe', -- Password: Noor@z@m1982
    'user', -- Default role = user
    NOW(),
    NOW()
FROM `staf`
WHERE id_staf IS NOT NULL;

-- Step 4: Set No. K/P 820426025349 sebagai ADMIN
UPDATE `login` l
INNER JOIN `staf` s ON l.id_staf = s.id_staf
SET l.role = 'admin'
WHERE s.no_kp = '820426025349';

-- Step 5: Verify setup
SELECT 
    s.no_kp,
    s.nama,
    l.role,
    LEFT(l.password_hash, 30) as hash_preview,
    'LOGIN CREATED' as status
FROM login l
INNER JOIN staf s ON l.id_staf = s.id_staf
WHERE s.no_kp IN ('820426025349', '660403026023', '660528025403')
ORDER BY 
    FIELD(l.role, 'admin', 'user'),
    s.nama;

-- ========================================
-- SETUP COMPLETE!
-- Username: No. K/P (contoh: 820426025349)
-- Password: Noor@z@m1982
-- Admin: 820426025349
-- ========================================

