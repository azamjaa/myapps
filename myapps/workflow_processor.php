<?php
/**
 * Workflow Processor - Execute If-This-Then-That automation rules
 * Dipanggil selepas insert/update rekod dalam custom_app_data
 */

require_once __DIR__ . '/db.php';

/**
 * Process workflows for a specific app and record
 * @param PDO $pdo Database connection
 * @param int $id_custom App ID
 * @param int $record_id Record ID yang baru ditambah/dikemaskini
 * @param string $trigger 'created' atau 'updated'
 * @param array $payload Data rekod (dari JSON payload column)
 * @return array Results of workflow execution
 */
function process_workflows($pdo, $id_custom, $record_id, $trigger, $payload) {
    $results = [];
    
    try {
        // Dapatkan metadata aplikasi (termasuk workflows)
        $stmt = $pdo->prepare("SELECT app_name, metadata FROM custom_apps WHERE id = ? LIMIT 1");
        $stmt->execute([$id_custom]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$app) {
            return ['error' => 'App not found'];
        }
        
        $app_name = $app['app_name'] ?? 'Aplikasi';
        $metadata = json_decode($app['metadata'] ?? '{}', true);
        $workflows = $metadata['workflows'] ?? [];
        
        if (empty($workflows)) {
            return ['message' => 'No workflows configured'];
        }
        
        // Proses setiap workflow
        foreach ($workflows as $idx => $workflow) {
            // Skip jika trigger tidak sepadan
            if (($workflow['trigger'] ?? '') !== $trigger) {
                continue;
            }
            
            $condition_field = $workflow['condition_field'] ?? '';
            $condition_operator = $workflow['condition_operator'] ?? '==';
            $condition_value = $workflow['condition_value'] ?? '';
            $action_email = $workflow['action_email'] ?? '';
            
            // Validasi workflow configuration
            if (empty($condition_field) || empty($action_email)) {
                $results[] = ['workflow' => $idx, 'status' => 'skipped', 'reason' => 'Invalid configuration'];
                continue;
            }
            
            // Dapatkan nilai field dari payload
            $field_value = $payload[$condition_field] ?? null;
            
            // Evaluate condition
            $condition_met = false;
            switch ($condition_operator) {
                case '==':
                    $condition_met = ($field_value == $condition_value);
                    break;
                case '!=':
                    $condition_met = ($field_value != $condition_value);
                    break;
                case '>':
                    $condition_met = (is_numeric($field_value) && is_numeric($condition_value) && $field_value > $condition_value);
                    break;
                case '<':
                    $condition_met = (is_numeric($field_value) && is_numeric($condition_value) && $field_value < $condition_value);
                    break;
                case '>=':
                    $condition_met = (is_numeric($field_value) && is_numeric($condition_value) && $field_value >= $condition_value);
                    break;
                case '<=':
                    $condition_met = (is_numeric($field_value) && is_numeric($condition_value) && $field_value <= $condition_value);
                    break;
                case 'contains':
                    $condition_met = (stripos((string)$field_value, $condition_value) !== false);
                    break;
                default:
                    $condition_met = false;
            }
            
            // Jika condition tidak dipenuhi, skip
            if (!$condition_met) {
                $results[] = [
                    'workflow' => $idx, 
                    'status' => 'condition_not_met',
                    'field' => $condition_field,
                    'value' => $field_value,
                    'expected' => $condition_value,
                    'operator' => $condition_operator
                ];
                continue;
            }
            
            // Execute action: Hantar emel
            $email_sent = send_workflow_email(
                $action_email,
                $app_name,
                $trigger,
                $condition_field,
                $field_value,
                $record_id,
                $payload
            );
            
            $results[] = [
                'workflow' => $idx,
                'status' => $email_sent ? 'executed' : 'failed',
                'action' => 'send_email',
                'email' => $action_email,
                'condition_met' => true
            ];
            
            // Log workflow execution
            log_workflow_execution($pdo, $id_custom, $record_id, $idx, $trigger, $condition_met, $email_sent);
        }
        
    } catch (Exception $e) {
        error_log('Workflow processor error: ' . $e->getMessage());
        return ['error' => $e->getMessage()];
    }
    
    return $results;
}

/**
 * Send notification email for workflow
 */
function send_workflow_email($to_email, $app_name, $trigger, $field_name, $field_value, $record_id, $payload) {
    if (empty($to_email) || !filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    $trigger_text = ($trigger === 'created') ? 'ditambah' : 'dikemaskini';
    $subject = "[MyApps] Notifikasi: Rekod $trigger_text - $app_name";
    
    $message = "Tuan/Puan,\r\n\r\n";
    $message .= "Satu rekod telah $trigger_text dalam aplikasi: $app_name\r\n\r\n";
    $message .= "Butiran Workflow:\r\n";
    $message .= "- Field: $field_name\r\n";
    $message .= "- Nilai: $field_value\r\n";
    $message .= "- ID Rekod: #$record_id\r\n\r\n";
    $message .= "Data Rekod:\r\n";
    foreach ($payload as $key => $value) {
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        $message .= "- " . ucfirst(str_replace('_', ' ', $key)) . ": $value\r\n";
    }
    $message .= "\r\nTerima kasih.\r\n";
    $message .= "Ini adalah notifikasi automatik dari MyApps KEDA.\r\n";
    
    $from_name = 'MyApps KEDA';
    $headers = [
        'From: ' . $from_name . ' <noreply@' . ($_SERVER['SERVER_NAME'] ?? 'localhost') . '>',
        'Reply-To: ' . $to_email,
        'X-Mailer: PHP/' . PHP_VERSION,
        'Content-Type: text/plain; charset=UTF-8',
        'MIME-Version: 1.0'
    ];
    
    // Try PHPMailer jika ada
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        try {
            require_once __DIR__ . '/vendor/autoload.php';
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            $mail->setFrom('noreply@' . ($_SERVER['SERVER_NAME'] ?? 'localhost'), $from_name);
            $mail->addAddress($to_email);
            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->isHTML(false);
            return $mail->send();
        } catch (Exception $e) {
            error_log('PHPMailer error: ' . $e->getMessage());
            // Fallback to mail()
            return @mail($to_email, $subject, $message, implode("\r\n", $headers));
        }
    }
    
    return @mail($to_email, $subject, $message, implode("\r\n", $headers));
}

/**
 * Log workflow execution to database (optional)
 */
function log_workflow_execution($pdo, $id_custom, $record_id, $workflow_idx, $trigger, $condition_met, $action_success) {
    try {
        // Check if workflow_logs table exists, create if not
        $check = $pdo->query("SHOW TABLES LIKE 'workflow_logs'");
        if ($check->rowCount() === 0) {
            $pdo->exec("CREATE TABLE IF NOT EXISTS workflow_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                id_custom INT NOT NULL,
                record_id INT NOT NULL,
                workflow_index INT NOT NULL,
                trigger_type VARCHAR(50),
                condition_met BOOLEAN,
                action_success BOOLEAN,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_custom (id_custom),
                INDEX idx_record (record_id)
            )");
        }
        
        $stmt = $pdo->prepare("INSERT INTO workflow_logs (id_custom, record_id, workflow_index, trigger_type, condition_met, action_success) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id_custom, $record_id, $workflow_idx, $trigger, $condition_met ? 1 : 0, $action_success ? 1 : 0]);
    } catch (PDOException $e) {
        error_log('Workflow log error: ' . $e->getMessage());
    }
}
