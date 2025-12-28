-- =====================================================
-- INSERT LOGIN RECORDS FOR EXISTING STAFF
-- =====================================================
-- Password: password (hashed with bcrypt)
-- Hash: $2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyYjW4p8KD8.
-- =====================================================

-- Insert login for admin test user
INSERT INTO `login` (`id_staf`, `password_hash`, `created_at`, `updated_at`) 
VALUES 
(
    (SELECT id_staf FROM staf WHERE no_kp = '900101011234' LIMIT 1),
    '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyYjW4p8KD8.',
    NOW(),
    NOW()
)
ON DUPLICATE KEY UPDATE 
    password_hash = '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyYjW4p8KD8.',
    updated_at = NOW();

-- =====================================================
-- CREATE DEFAULT LOGIN RECORDS FOR ALL STAFF
-- =====================================================
-- This will create login records for all staff that don't have one
-- Default password: password123
-- Hash: $2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
-- =====================================================

INSERT INTO `login` (`id_staf`, `password_hash`, `created_at`, `updated_at`)
SELECT 
    s.id_staf,
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    NOW(),
    NOW()
FROM staf s
LEFT JOIN login l ON s.id_staf = l.id_staf
WHERE l.id_login IS NULL;

-- =====================================================
-- VERIFY INSERTION
-- =====================================================
SELECT 
    s.no_kp,
    s.nama,
    l.password_hash,
    l.created_at,
    CASE 
        WHEN l.password_hash = '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyYjW4p8KD8.' 
        THEN 'password' 
        ELSE 'password123' 
    END as default_password
FROM staf s
INNER JOIN login l ON s.id_staf = l.id_staf
ORDER BY s.nama
LIMIT 10;

-- =====================================================
-- LOGIN CREDENTIALS
-- =====================================================
-- Test User:
--   No K/P: 900101011234
--   Password: password
--
-- Other Staff (if created):
--   No K/P: [their IC number]
--   Password: password123
-- =====================================================

