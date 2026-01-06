<?php
/**
 * MyApps KEDA - Audit Logging Functions
 * Track all important actions in the system
 */

/**
 * Log audit trail
 * 
 * @param PDO $db Database connection
 * @param string $action Action type (CREATE, UPDATE, DELETE, LOGIN, LOGOUT, etc)
 * @param string|null $table_affected Table name that was affected
 * @param int|null $record_id ID of the affected record
 * @param mixed|null $old_value Old value (will be JSON encoded)
 * @param mixed|null $new_value New value (will be JSON encoded)
 * @return bool Success status
 */
function logAudit($db, $action, $table_affected = null, $record_id = null, $old_value = null, $new_value = null) {
    try {
        // Check if audit_log table exists first
        $check = $db->query("SHOW TABLES LIKE 'audit_log'");
        if ($check->rowCount() == 0) {
            // Table doesn't exist yet - silent skip
            return false;
        }
        
        // Get user ID from session
        $user_id = $_SESSION['user_id'] ?? null;
        
        // Get IP address
        $ip_address = getUserIP();
        
        // Get user agent
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        // Convert values to JSON if not null
        $old_value_json = $old_value ? json_encode($old_value, JSON_UNESCAPED_UNICODE) : null;
        $new_value_json = $new_value ? json_encode($new_value, JSON_UNESCAPED_UNICODE) : null;
        
        // Insert audit log
        $sql = "INSERT INTO audit_log (user_id, action, table_affected, record_id, old_value, new_value, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $user_id,
            $action,
            $table_affected,
            $record_id,
            $old_value_json,
            $new_value_json,
            $ip_address,
            $user_agent
        ]);
        
        return true;
        
    } catch (Exception $e) {
        // Silent fail - don't break main operation if audit fails
        error_log("Audit Log Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user's real IP address (handles proxy/load balancer)
 * 
 * @return string IP address
 */
function getUserIP() {
    // Check for shared internet/proxy
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    // Check for proxy
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    // Direct connection
    else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    // Validate IP
    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
    }
    
    return '0.0.0.0';
}

/**
 * Log login attempt
 * 
 * @param PDO $db Database connection
 * @param int|null $user_id User ID (null if failed login)
 * @param bool $success Login success status
 * @param string|null $reason Failure reason
 */
function logLogin($db, $user_id = null, $success = true, $reason = null) {
    $action = $success ? 'LOGIN_SUCCESS' : 'LOGIN_FAILED';
    $details = [
        'success' => $success,
        'reason' => $reason,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    logAudit($db, $action, 'login', $user_id, null, $details);
}

/**
 * Log logout
 * 
 * @param PDO $db Database connection
 */
function logLogout($db) {
    $user_id = $_SESSION['user_id'] ?? null;
    logAudit($db, 'LOGOUT', 'login', $user_id);
}

/**
 * Log data creation
 * 
 * @param PDO $db Database connection
 * @param string $table Table name
 * @param int $record_id New record ID
 * @param array $data Data that was created
 */
function logCreate($db, $table, $record_id, $data) {
    logAudit($db, 'CREATE', $table, $record_id, null, $data);
}

/**
 * Log data update
 * 
 * @param PDO $db Database connection
 * @param string $table Table name
 * @param int $record_id Record ID
 * @param array $old_data Old data
 * @param array $new_data New data
 */
function logUpdate($db, $table, $record_id, $old_data, $new_data) {
    logAudit($db, 'UPDATE', $table, $record_id, $old_data, $new_data);
}

/**
 * Log data deletion
 * 
 * @param PDO $db Database connection
 * @param string $table Table name
 * @param int $record_id Record ID
 * @param array $deleted_data Data that was deleted
 */
function logDelete($db, $table, $record_id, $deleted_data) {
    logAudit($db, 'DELETE', $table, $record_id, $deleted_data, null);
}

/**
 * Log password change
 * 
 * @param PDO $db Database connection
 * @param int $user_id User ID
 * @param bool $forced Was it forced change?
 */
function logPasswordChange($db, $user_id, $forced = false) {
    $details = [
        'forced' => $forced,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    logAudit($db, 'PASSWORD_CHANGE', 'login', $user_id, null, $details);
}

/**
 * Get audit logs with filters
 * 
 * @param PDO $db Database connection
 * @param array $filters Filters (user_id, action, table, date_from, date_to)
 * @param int $limit Limit results
 * @param int $offset Offset for pagination
 * @return array Audit logs
 */
function getAuditLogs($db, $filters = [], $limit = 50, $offset = 0) {
    $sql = "SELECT a.*, s.nama 
            FROM audit_log a 
            LEFT JOIN users s ON a.user_id = s.id_user 
            WHERE 1=1";
    
    $params = [];
    
    if (!empty($filters['user_id'])) {
        $sql .= " AND a.user_id = ?";
        $params[] = $filters['user_id'];
    }
    
    if (!empty($filters['action'])) {
        $sql .= " AND a.action = ?";
        $params[] = $filters['action'];
    }
    
    if (!empty($filters['table'])) {
        $sql .= " AND a.table_affected = ?";
        $params[] = $filters['table'];
    }
    
    if (!empty($filters['date_from'])) {
        $sql .= " AND a.created_at >= ?";
        $params[] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $sql .= " AND a.created_at <= ?";
        $params[] = $filters['date_to'];
    }
    
    $sql .= " ORDER BY a.waktu DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Clean old audit logs (retention policy)
 * 
 * @param PDO $db Database connection
 * @param int $days Number of days to keep (default 90 days)
 * @return int Number of deleted records
 */
function cleanOldAuditLogs($db, $days = 90) {
    $sql = "DELETE FROM audit WHERE waktu < DATE_SUB(NOW(), INTERVAL ? DAY)";
    $stmt = $db->prepare($sql);
    $stmt->execute([$days]);
    
    return $stmt->rowCount();
}
?>

<?php if(isset($_SESSION['user_id']) && hasAccess($pdo, $_SESSION['user_id'], 1, 'view_audit')): ?>
<!-- Paparan audit log di sini -->
<?php endif; ?>

