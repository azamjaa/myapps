-- Check if staf record exists
SELECT * FROM staf WHERE no_kp = '900101011234';

-- Check if login record exists with proper foreign key
SELECT 
    l.*,
    s.no_kp,
    s.nama
FROM login l
LEFT JOIN staf s ON l.id_staf = s.id_staf
WHERE s.no_kp = '900101011234';

-- Verify password hash
SELECT 
    s.no_kp,
    s.nama,
    l.password_hash,
    CASE 
        WHEN l.password_hash = '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5GyYjW4p8KD8.' 
        THEN 'password (correct hash)' 
        ELSE 'other hash' 
    END as password_type
FROM staf s
LEFT JOIN login l ON s.id_staf = l.id_staf
WHERE s.no_kp = '900101011234';

