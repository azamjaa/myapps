<?php
/**
 * Migration Script: Add sso_ready column to custom_apps table
 * Run this once to update existing database
 */

require_once __DIR__ . '/db.php';

try {
    echo "🔄 Starting migration: Add sso_ready column to custom_apps...\n\n";
    
    // Check if column already exists
    $check = $pdo->query("SHOW COLUMNS FROM custom_apps LIKE 'sso_ready'")->fetch();
    
    if ($check) {
        echo "✅ Column 'sso_ready' already exists in custom_apps table.\n";
        echo "   No migration needed.\n";
    } else {
        echo "📝 Adding 'sso_ready' column to custom_apps table...\n";
        
        // Add column with default value 1 (SSO Ready)
        $pdo->exec("ALTER TABLE custom_apps 
                    ADD COLUMN sso_ready TINYINT(1) DEFAULT 1 
                    COMMENT 'SSO Ready status: 1=Yes (default for no-code apps), 0=No' 
                    AFTER id_kategori");
        
        echo "✅ Column added successfully!\n\n";
        
        // Update existing records to have sso_ready = 1
        $result = $pdo->exec("UPDATE custom_apps SET sso_ready = 1 WHERE sso_ready IS NULL");
        echo "✅ Updated $result existing records to sso_ready = 1\n\n";
    }
    
    // Verify the change
    $columns = $pdo->query("SHOW COLUMNS FROM custom_apps")->fetchAll(PDO::FETCH_ASSOC);
    echo "📊 Current custom_apps table structure:\n";
    echo str_repeat("-", 80) . "\n";
    printf("%-20s %-30s %-10s %-10s\n", "Field", "Type", "Null", "Default");
    echo str_repeat("-", 80) . "\n";
    foreach ($columns as $col) {
        printf("%-20s %-30s %-10s %-10s\n", 
            $col['Field'], 
            $col['Type'], 
            $col['Null'], 
            $col['Default'] ?? 'NULL'
        );
    }
    echo str_repeat("-", 80) . "\n\n";
    
    echo "✅ Migration completed successfully!\n";
    echo "\n📝 Summary:\n";
    echo "   - All no-code apps now have SSO Ready status by default\n";
    echo "   - New apps created via builder will automatically be SSO Ready\n";
    echo "   - SSO badge will display on dashboard for no-code apps\n\n";
    
} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "\nPlease check:\n";
    echo "1. Database connection is working\n";
    echo "2. You have ALTER TABLE permissions\n";
    echo "3. The custom_apps table exists\n";
    exit(1);
}
