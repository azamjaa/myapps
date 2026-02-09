<!DOCTYPE html>
<html>
<head>
    <title>Migration: Add SSO Ready to No-Code Apps</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>
    <h1>🔄 Migration: Add SSO Ready Status to No-Code Apps</h1>
    
    <?php
    require_once __DIR__ . '/db.php';
    
    try {
        echo '<div class="info">📝 Starting migration process...</div>';
        
        // Check if column already exists
        $check = $pdo->query("SHOW COLUMNS FROM custom_apps LIKE 'sso_ready'")->fetch();
        
        if ($check) {
            echo '<div class="success">✅ Column <code>sso_ready</code> already exists in custom_apps table.</div>';
            echo '<div class="info">No migration needed - column is already present.</div>';
        } else {
            echo '<div class="info">📝 Adding <code>sso_ready</code> column to custom_apps table...</div>';
            
            // Add column with default value 1 (SSO Ready)
            $pdo->exec("ALTER TABLE custom_apps 
                        ADD COLUMN sso_ready TINYINT(1) DEFAULT 1 
                        COMMENT 'SSO Ready status: 1=Yes (default for no-code apps), 0=No' 
                        AFTER id_kategori");
            
            echo '<div class="success">✅ Column added successfully!</div>';
            
            // Update existing records to have sso_ready = 1
            $result = $pdo->exec("UPDATE custom_apps SET sso_ready = 1 WHERE sso_ready IS NULL");
            echo '<div class="success">✅ Updated ' . $result . ' existing records to sso_ready = 1</div>';
        }
        
        // Show current table structure
        $columns = $pdo->query("SHOW COLUMNS FROM custom_apps")->fetchAll(PDO::FETCH_ASSOC);
        echo '<h2>📊 Current custom_apps Table Structure:</h2>';
        echo '<table>';
        echo '<thead><tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th><th>Extra</th></tr></thead>';
        echo '<tbody>';
        foreach ($columns as $col) {
            $highlight = ($col['Field'] === 'sso_ready') ? ' style="background: #fff3cd;"' : '';
            echo '<tr' . $highlight . '>';
            echo '<td><strong>' . htmlspecialchars($col['Field']) . '</strong></td>';
            echo '<td>' . htmlspecialchars($col['Type']) . '</td>';
            echo '<td>' . htmlspecialchars($col['Null']) . '</td>';
            echo '<td>' . htmlspecialchars($col['Default'] ?? 'NULL') . '</td>';
            echo '<td>' . htmlspecialchars($col['Extra'] ?? '') . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        
        // Show current no-code apps
        $apps = $pdo->query("SELECT id, app_slug, app_name, id_kategori, sso_ready, created_at FROM custom_apps ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($apps) > 0) {
            echo '<h2>📱 Current No-Code Apps (SSO Status):</h2>';
            echo '<table>';
            echo '<thead><tr><th>ID</th><th>App Name</th><th>Slug</th><th>Category</th><th>SSO Ready</th><th>Created</th></tr></thead>';
            echo '<tbody>';
            foreach ($apps as $app) {
                $kategori = ['', 'Dalaman', 'Luaran', 'Gunasama'][$app['id_kategori']] ?? 'Unknown';
                $sso_badge = $app['sso_ready'] == 1 ? '<span style="color: green;">✓ SSO Ready</span>' : '<span style="color: red;">✗ Not Ready</span>';
                echo '<tr>';
                echo '<td>' . $app['id'] . '</td>';
                echo '<td><strong>' . htmlspecialchars($app['app_name']) . '</strong></td>';
                echo '<td><code>' . htmlspecialchars($app['app_slug']) . '</code></td>';
                echo '<td>' . $kategori . '</td>';
                echo '<td>' . $sso_badge . '</td>';
                echo '<td>' . date('d/m/Y H:i', strtotime($app['created_at'])) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<div class="info">ℹ️ No no-code apps found yet. Create one using the No-Code Builder!</div>';
        }
        
        echo '<div class="success">';
        echo '<h3>✅ Migration Completed Successfully!</h3>';
        echo '<p><strong>What changed:</strong></p>';
        echo '<ul>';
        echo '<li>Added <code>sso_ready</code> column to <code>custom_apps</code> table</li>';
        echo '<li>All existing no-code apps now have SSO Ready status (sso_ready = 1)</li>';
        echo '<li>New apps created via builder will automatically be SSO Ready</li>';
        echo '<li>SSO badge will display on dashboard for no-code apps</li>';
        echo '</ul>';
        echo '<p><a href="dashboard_aplikasi.php">← Back to Dashboard Aplikasi</a></p>';
        echo '</div>';
        
    } catch (PDOException $e) {
        echo '<div class="error">';
        echo '<h3>❌ Migration Failed</h3>';
        echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<p><strong>Please check:</strong></p>';
        echo '<ul>';
        echo '<li>Database connection is working</li>';
        echo '<li>You have ALTER TABLE permissions</li>';
        echo '<li>The custom_apps table exists</li>';
        echo '</ul>';
        echo '</div>';
    }
    ?>
    
    <hr>
    <p style="text-align: center; color: #666; font-size: 12px;">
        Migration Script | MyApps No-Code Builder | <?php echo date('Y-m-d H:i:s'); ?>
    </p>
</body>
</html>
