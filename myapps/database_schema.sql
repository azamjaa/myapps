-- ============================================================================
-- MyApps No-Code Builder - Database Schema
-- ============================================================================
-- Version: 1.0.0
-- Last Updated: 2026-02-09
-- Description: Complete database schema for No-Code Builder system
-- ============================================================================

-- ----------------------------------------------------------------------------
-- Table: custom_apps
-- Description: Stores application metadata including fields, pages, workflows
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `custom_apps` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `app_slug` VARCHAR(255) NOT NULL UNIQUE,
  `app_name` VARCHAR(255) NOT NULL,
  `metadata` TEXT DEFAULT NULL COMMENT 'JSON: fields, pages, workflows, dashboard_cards, settings',
  `id_user_owner` INT(11) DEFAULT NULL,
  `id_kategori` INT(11) DEFAULT NULL COMMENT '1=Dalaman, 2=Luaran, 3=Gunasama',
  `sso_ready` TINYINT(1) DEFAULT 1 COMMENT 'SSO Ready status: 1=Yes (default for no-code apps), 0=No',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_app_slug` (`app_slug`),
  KEY `idx_user_owner` (`id_user_owner`),
  KEY `idx_kategori` (`id_kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: custom_app_data
-- Description: Stores actual application data records as JSON payload
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `custom_app_data` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `id_custom` INT(11) NOT NULL COMMENT 'References custom_apps.id',
  `payload` TEXT DEFAULT NULL COMMENT 'JSON: All record data as key-value pairs',
  `created_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_custom` (`id_custom`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_custom_app_data_apps` 
    FOREIGN KEY (`id_custom`) 
    REFERENCES `custom_apps` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: workflow_logs (auto-created by workflow_processor.php)
-- Description: Logs workflow execution history for debugging and auditing
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `workflow_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `id_custom` INT(11) NOT NULL,
  `record_id` INT(11) NOT NULL,
  `workflow_index` INT(11) NOT NULL COMMENT 'Index of workflow in metadata.workflows array',
  `trigger_type` VARCHAR(50) DEFAULT NULL COMMENT 'created or updated',
  `condition_met` TINYINT(1) DEFAULT 0,
  `action_success` TINYINT(1) DEFAULT 0,
  `executed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_custom` (`id_custom`),
  KEY `idx_record` (`record_id`),
  KEY `idx_executed_at` (`executed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Table: kategori
-- Description: Application categories (Dalaman/Luaran/Gunasama)
-- Note: If this table doesn't exist, wizard.php will use fallback values
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `kategori` (
  `id_kategori` INT(11) NOT NULL AUTO_INCREMENT,
  `nama_kategori` VARCHAR(100) NOT NULL,
  `aktif` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default categories if table is empty
INSERT INTO `kategori` (`id_kategori`, `nama_kategori`, `aktif`) VALUES
(1, 'Dalaman', 1),
(2, 'Luaran', 1),
(3, 'Gunasama', 1)
ON DUPLICATE KEY UPDATE `nama_kategori` = VALUES(`nama_kategori`);

-- ----------------------------------------------------------------------------
-- Sample Data: Example Application
-- Description: Sample "Sistem Aduan" application for testing
-- ----------------------------------------------------------------------------
-- Uncomment below to insert sample data

/*
INSERT INTO `custom_apps` (
  `app_slug`, 
  `app_name`, 
  `metadata`, 
  `id_user_owner`, 
  `id_kategori`
) VALUES (
  'sistem-aduan-contoh',
  'Sistem Aduan (Contoh)',
  '{
    "fields": [
      {"name": "nama_pengadu", "label": "Nama Pengadu", "type": "text", "required": true},
      {"name": "no_telefon", "label": "No. Telefon", "type": "text", "required": true},
      {"name": "jenis_aduan", "label": "Jenis Aduan", "type": "select", "options": ["Infrastruktur", "Perkhidmatan", "Lain-lain"]},
      {"name": "tarikh_kejadian", "label": "Tarikh Kejadian", "type": "date", "required": false},
      {"name": "keterangan", "label": "Keterangan", "type": "text", "required": true},
      {"name": "status", "label": "Status", "type": "select", "options": ["Baru", "Dalam Tindakan", "Selesai"]}
    ],
    "pages": [
      {"id": "senarai", "type": "list", "label": "Senarai Aduan", "icon": "fas fa-list", "config": {"layout_type": "card_view"}},
      {"id": "borang", "type": "form", "label": "Borang Aduan", "icon": "fas fa-plus-circle"},
      {"id": "dashboard", "type": "dashboard", "label": "Dashboard", "icon": "fas fa-chart-pie"}
    ],
    "layout_type": "card_view",
    "settings": {
      "enable_dashboard": true,
      "enable_search": true,
      "enable_export_excel": true,
      "enable_edit_delete": true
    },
    "dashboard_cards": [
      {"title": "Jumlah Aduan", "field": "status", "aggregation": "count"},
      {"title": "Aduan Baru", "field": "status", "aggregation": "count", "filter": {"field": "status", "value": "Baru"}},
      {"title": "Aduan Selesai", "field": "status", "aggregation": "count", "filter": {"field": "status", "value": "Selesai"}}
    ],
    "workflows": [
      {
        "trigger": "created",
        "condition_field": "status",
        "condition_operator": "==",
        "condition_value": "Baru",
        "action_email": "admin@example.com"
      }
    ]
  }',
  1,
  2
);

-- Sample data records for the example app
INSERT INTO `custom_app_data` (`id_custom`, `created_by`, `payload`) VALUES
(
  (SELECT id FROM custom_apps WHERE app_slug = 'sistem-aduan-contoh' LIMIT 1),
  1,
  '{"nama_pengadu": "Ahmad bin Abu", "no_telefon": "0123456789", "jenis_aduan": "Infrastruktur", "tarikh_kejadian": "2026-02-01", "keterangan": "Jalan berlubang di depan pejabat", "status": "Baru"}'
),
(
  (SELECT id FROM custom_apps WHERE app_slug = 'sistem-aduan-contoh' LIMIT 1),
  1,
  '{"nama_pengadu": "Siti binti Hassan", "no_telefon": "0129876543", "jenis_aduan": "Perkhidmatan", "tarikh_kejadian": "2026-02-05", "keterangan": "Lampu jalan tidak berfungsi", "status": "Dalam Tindakan"}'
);
*/

-- ----------------------------------------------------------------------------
-- Indexes for Performance Optimization
-- ----------------------------------------------------------------------------
-- Already included in CREATE TABLE statements above
-- Additional indexes can be added based on usage patterns:

-- Optimize workflow_logs queries by trigger_type
-- CREATE INDEX idx_workflow_trigger ON workflow_logs(id_custom, trigger_type, executed_at);

-- Optimize custom_app_data payload searches (requires GENERATED columns for MySQL 5.7+)
-- ALTER TABLE custom_app_data ADD COLUMN status_generated VARCHAR(100) 
--   AS (JSON_UNQUOTE(JSON_EXTRACT(payload, '$.status'))) STORED;
-- CREATE INDEX idx_status ON custom_app_data(status_generated);

-- ----------------------------------------------------------------------------
-- Views for Reporting (Optional)
-- ----------------------------------------------------------------------------

-- View: App Statistics
CREATE OR REPLACE VIEW v_app_statistics AS
SELECT 
  ca.id,
  ca.app_slug,
  ca.app_name,
  ca.id_kategori,
  k.nama_kategori,
  COUNT(cad.id) as total_records,
  MAX(cad.created_at) as last_record_date,
  ca.created_at as app_created_at
FROM custom_apps ca
LEFT JOIN custom_app_data cad ON ca.id = cad.id_custom
LEFT JOIN kategori k ON ca.id_kategori = k.id_kategori
GROUP BY ca.id, ca.app_slug, ca.app_name, ca.id_kategori, k.nama_kategori, ca.created_at;

-- View: Workflow Execution Summary
CREATE OR REPLACE VIEW v_workflow_summary AS
SELECT 
  wl.id_custom,
  ca.app_name,
  wl.workflow_index,
  wl.trigger_type,
  COUNT(*) as total_executions,
  SUM(wl.condition_met) as times_triggered,
  SUM(wl.action_success) as successful_actions,
  MAX(wl.executed_at) as last_execution
FROM workflow_logs wl
JOIN custom_apps ca ON wl.id_custom = ca.id
GROUP BY wl.id_custom, ca.app_name, wl.workflow_index, wl.trigger_type;

-- ----------------------------------------------------------------------------
-- Stored Procedures (Optional)
-- ----------------------------------------------------------------------------

-- Procedure: Clean old workflow logs (optional maintenance)
DELIMITER $$
CREATE PROCEDURE IF NOT EXISTS sp_cleanup_workflow_logs(IN days_to_keep INT)
BEGIN
  DELETE FROM workflow_logs 
  WHERE executed_at < DATE_SUB(NOW(), INTERVAL days_to_keep DAY);
  
  SELECT CONCAT('Deleted logs older than ', days_to_keep, ' days') as message;
END$$
DELIMITER ;

-- Usage: CALL sp_cleanup_workflow_logs(90);  -- Keep last 90 days

-- ----------------------------------------------------------------------------
-- Permissions & Security (Adjust based on your setup)
-- ----------------------------------------------------------------------------
-- Example: Create a dedicated database user for MyApps
-- CREATE USER 'myapps_user'@'localhost' IDENTIFIED BY 'secure_password_here';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON myapps.* TO 'myapps_user'@'localhost';
-- FLUSH PRIVILEGES;

-- ----------------------------------------------------------------------------
-- Verification Queries
-- ----------------------------------------------------------------------------
-- Run these after setup to verify everything is working:

-- 1. Check all tables exist
SELECT 
  TABLE_NAME, 
  TABLE_ROWS, 
  CREATE_TIME, 
  UPDATE_TIME 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME IN ('custom_apps', 'custom_app_data', 'workflow_logs', 'kategori')
ORDER BY TABLE_NAME;

-- 2. Check if sample data was inserted
SELECT COUNT(*) as total_apps FROM custom_apps;
SELECT COUNT(*) as total_data_records FROM custom_app_data;
SELECT COUNT(*) as total_categories FROM kategori;

-- 3. Test the views
SELECT * FROM v_app_statistics;
SELECT * FROM v_workflow_summary;

-- ============================================================================
-- End of Schema
-- ============================================================================
-- Notes:
-- - All tables use utf8mb4 charset for full Unicode support
-- - Foreign keys ensure referential integrity
-- - Timestamps track creation and updates automatically
-- - JSON columns store flexible, schema-less data
-- - Indexes optimize common query patterns
-- 
-- For more information, see:
-- - NOCODE_BUILDER_DOCUMENTATION.md
-- - README_NOCODE_BUILDER.md
-- ============================================================================
