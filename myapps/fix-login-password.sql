-- =====================================================
-- FIX LOGIN PASSWORD HASH
-- =====================================================
-- This will DELETE old login record and create FRESH one
-- Password: password
-- Fresh Hash: $2y$12$kFSJOuKInEhIZkvYUbu1I.7TzmD0JQ1ZhEOrDNVym22ns/MP73i4m
-- =====================================================

-- Step 1: Delete old login records
DELETE FROM login WHERE id_staf = 392;

-- Step 2: Insert fresh login with NEW hash
INSERT INTO login (id_staf, password_hash, created_at, updated_at)
VALUES (
    392,
    '$2y$12$kFSJOuKInEhIZkvYUbu1I.7TzmD0JQ1ZhEOrDNVym22ns/MP73i4m',
    NOW(),
    NOW()
);

-- Step 3: Verify insertion
SELECT 
    l.id_login,
    l.id_staf,
    LEFT(l.password_hash, 30) as hash_preview,
    l.created_at,
    s.no_kp,
    s.nama,
    'FRESH HASH - Password: password' as note
FROM login l
INNER JOIN staf s ON l.id_staf = s.id_staf
WHERE s.no_kp = '900101011234';

-- =====================================================
-- LOGIN CREDENTIALS (AFTER RUNNING THIS SQL)
-- =====================================================
-- No K/P: 900101011234
-- Password: password
-- =====================================================

