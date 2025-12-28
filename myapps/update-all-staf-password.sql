-- =====================================================
-- UPDATE PASSWORD UNTUK SEMUA STAF
-- =====================================================
-- Password Standard: Noor@z@m1982
-- Hash: $2y$12$6GVcyRuDLCyscSEweVLEg.eJgDDhK/VgjhUxaFDw1AR7b5yvusXQe
-- =====================================================

-- Step 1: Update semua existing login records
UPDATE `login` 
SET 
    password_hash = '$2y$12$6GVcyRuDLCyscSEweVLEg.eJgDDhK/VgjhUxaFDw1AR7b5yvusXQe',
    updated_at = NOW(),
    tarikh_tukar_katalaluan = NOW();

-- Step 2: Insert login records untuk staf yang belum ada
INSERT INTO `login` (`id_staf`, `password_hash`, `created_at`, `updated_at`, `tarikh_tukar_katalaluan`)
SELECT 
    s.id_staf,
    '$2y$12$6GVcyRuDLCyscSEweVLEg.eJgDDhK/VgjhUxaFDw1AR7b5yvusXQe',
    NOW(),
    NOW(),
    NOW()
FROM staf s
LEFT JOIN login l ON s.id_staf = l.id_staf
WHERE l.id_login IS NULL;

-- Step 3: Verify update
SELECT 
    COUNT(*) as total_login_records,
    'Password: Noor@z@m1982' as standard_password
FROM login;

-- Step 4: Show sample staf with login
SELECT 
    s.no_staf,
    s.no_kp,
    s.nama,
    l.id_login,
    DATE_FORMAT(l.tarikh_tukar_katalaluan, '%d/%m/%Y %H:%i:%s') as tarikh_update,
    'Noor@z@m1982' as password_standard
FROM staf s
INNER JOIN login l ON s.id_staf = l.id_staf
ORDER BY s.nama
LIMIT 10;

-- =====================================================
-- LOGIN CREDENTIALS SELEPAS UPDATE
-- =====================================================
-- Semua staf boleh login dengan:
--   Username: No. K/P (12 digit)
--   Password: Noor@z@m1982
-- 
-- Contoh:
--   No. K/P: 900101011234
--   Password: Noor@z@m1982
-- =====================================================

