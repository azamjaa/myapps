<?php
/**
 * No-Code Builder Hub - Unified Entry Point
 * User pilih builder mode: Data-Driven, Visual-Driven, atau Logic-Driven
 */
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

<style>
* { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }

.hub-container {
    min-height: calc(100vh - 120px);
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 60px 20px;
}

.hub-header {
    text-align: center;
    color: white;
    margin-bottom: 50px;
}

.hub-header h1 {
    font-size: 48px;
    font-weight: 800;
    margin-bottom: 15px;
    text-shadow: 0 2px 10px rgba(0,0,0,0.2);
}

.hub-header p {
    font-size: 20px;
    opacity: 0.95;
    font-weight: 400;
}

.builder-modes {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 30px;
    max-width: 1200px;
    margin: 0 auto;
}

.mode-card {
    background: white;
    border-radius: 20px;
    padding: 40px 30px;
    text-align: center;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    transition: all 0.3s ease;
    cursor: pointer;
    text-decoration: none;
    color: inherit;
    display: block;
    position: relative;
    overflow: hidden;
}

.mode-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 6px;
    background: var(--mode-color);
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.mode-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 60px rgba(0,0,0,0.2);
}

.mode-card:hover::before {
    transform: scaleX(1);
}

.mode-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 25px;
    background: var(--mode-color);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 36px;
    color: white;
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}

.mode-title {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 12px;
    color: #1f2937;
}

.mode-subtitle {
    font-size: 14px;
    font-weight: 600;
    color: var(--mode-color);
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 15px;
}

.mode-description {
    font-size: 15px;
    color: #6b7280;
    line-height: 1.6;
    margin-bottom: 20px;
}

.mode-features {
    list-style: none;
    padding: 0;
    margin: 20px 0;
    text-align: left;
}

.mode-features li {
    font-size: 14px;
    color: #4b5563;
    padding: 8px 0;
    padding-left: 28px;
    position: relative;
}

.mode-features li::before {
    content: '✓';
    position: absolute;
    left: 0;
    color: var(--mode-color);
    font-weight: bold;
    font-size: 16px;
}

.mode-badge {
    display: inline-block;
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    margin-top: 15px;
}

.badge-easy {
    background: #d1fae5;
    color: #065f46;
}

.badge-advanced {
    background: #fef3c7;
    color: #92400e;
}

.badge-powerful {
    background: #ddd6fe;
    color: #5b21b6;
}

.badge-quick {
    background: #dbeafe;
    color: #1e40af;
}

/* Mode-specific colors */
.mode-data { --mode-color: #10b981; }
.mode-visual { --mode-color: #3b82f6; }
.mode-logic { --mode-color: #8b5cf6; }
.mode-import { --mode-color: #f59e0b; }

.hub-footer {
    text-align: center;
    margin-top: 60px;
    color: white;
    opacity: 0.9;
}

.hub-footer a {
    color: white;
    text-decoration: underline;
    margin: 0 10px;
}

@media (max-width: 768px) {
    .hub-header h1 {
        font-size: 32px;
    }
    
    .hub-header p {
        font-size: 16px;
    }
    
    .builder-modes {
        grid-template-columns: 1fr;
    }
}

/* Animation */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.mode-card {
    animation: fadeInUp 0.6s ease forwards;
}

.mode-card:nth-child(1) { animation-delay: 0.1s; }
.mode-card:nth-child(2) { animation-delay: 0.2s; }
.mode-card:nth-child(3) { animation-delay: 0.3s; }
.mode-card:nth-child(4) { animation-delay: 0.4s; }
</style>

<div class="hub-container">
    <div class="hub-header">
        <h1>🎯 No-Code Builder</h1>
        <p>Pilih gaya pembinaan aplikasi anda</p>
    </div>

    <div class="builder-modes">
        <!-- DATA-DRIVEN MODE -->
        <a href="wizard.php" class="mode-card mode-data">
            <div class="mode-icon">
                <i class="fas fa-table"></i>
            </div>
            <div class="mode-subtitle">Data-Driven</div>
            <h3 class="mode-title">Mula dengan Data</h3>
            <p class="mode-description">
                Upload Excel atau CSV, sistem auto-generate borang dan interface. 
                Sesuai untuk digitize borang sedia ada.
            </p>
            <ul class="mode-features">
                <li>Upload Excel/CSV instant</li>
                <li>Auto-detect field types</li>
                <li>Bulk import data</li>
                <li>Quick setup (5 minit)</li>
            </ul>
            <span class="mode-badge badge-easy">Mudah & Pantas</span>
        </a>

        <!-- VISUAL-DRIVEN MODE -->
        <a href="builder.php" class="mode-card mode-visual">
            <div class="mode-icon">
                <i class="fas fa-paint-brush"></i>
            </div>
            <div class="mode-subtitle">Visual-Driven</div>
            <h3 class="mode-title">Design Interface Dulu</h3>
            <p class="mode-description">
                Drag & drop components, atur layout, customize styling. 
                Full control atas UI/UX aplikasi anda.
            </p>
            <ul class="mode-features">
                <li>Drag & drop builder</li>
                <li>Custom layout & theme</li>
                <li>Multiple page types</li>
                <li>Real-time preview</li>
            </ul>
            <span class="mode-badge badge-powerful">Kawalan Penuh</span>
        </a>

        <!-- LOGIC-DRIVEN MODE -->
        <a href="workflow_builder.php" class="mode-card mode-logic">
            <div class="mode-icon">
                <i class="fas fa-project-diagram"></i>
            </div>
            <div class="mode-subtitle">Logic-Driven</div>
            <h3 class="mode-title">Workflow & Automasi</h3>
            <p class="mode-description">
                Bina business logic kompleks, automation rules, approval flows. 
                Untuk aplikasi dengan proses kerja yang kompleks.
            </p>
            <ul class="mode-features">
                <li>Visual workflow designer</li>
                <li>If-This-Then-That rules</li>
                <li>Email notifications</li>
                <li>Multi-step approvals</li>
            </ul>
            <span class="mode-badge badge-advanced">Advanced</span>
        </a>

        <!-- IMPORT FROM TEMPLATE -->
        <a href="#" class="mode-card mode-import" onclick="alert('Template library coming soon!'); return false;">
            <div class="mode-icon">
                <i class="fas fa-download"></i>
            </div>
            <div class="mode-subtitle">Import Template</div>
            <h3 class="mode-title">Guna Template Siap</h3>
            <p class="mode-description">
                Pilih dari library template siap (CRM, Inventory, HR, etc). 
                Modify ikut keperluan anda.
            </p>
            <ul class="mode-features">
                <li>Pre-built templates</li>
                <li>Industry-specific</li>
                <li>Fully customizable</li>
                <li>Start in seconds</li>
            </ul>
            <span class="mode-badge badge-quick">Paling Cepat</span>
        </a>
    </div>

    <div class="hub-footer">
        <p>
            <strong>💡 Tip:</strong> Tak pasti mana satu? Cuba <a href="wizard.php">Data-Driven</a> untuk start cepat!
        </p>
        <p style="margin-top: 15px; font-size: 14px; opacity: 0.8;">
            <a href="dashboard_aplikasi.php">← Kembali ke Dashboard</a> | 
            <a href="ARCHITECTURE_3IN1_BUILDER.md" target="_blank">📖 Documentation</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
