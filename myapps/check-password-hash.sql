-- Check current password hash
SELECT 
    l.id_login,
    l.id_staf,
    l.password_hash,
    s.no_kp,
    s.nama
FROM login l
INNER JOIN staf s ON l.id_staf = s.id_staf
WHERE s.no_kp = '900101011234';

-- Test if it's the correct hash for 'password'
-- The correct hash should be:
-- $2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyYjW4p8KD8.

