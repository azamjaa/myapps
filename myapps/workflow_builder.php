<?php
/**
 * Workflow Builder - Logic-Driven No-Code Builder
 * Visual workflow designer dengan If-This-Then-That logic
 */
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Get kategori list
$kategoriList = [];
try {
    $kategoriList = $pdo->query("SELECT id_kategori, nama_kategori FROM kategori WHERE aktif = 1 ORDER BY id_kategori ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Ignore if table doesn't exist
}

require_once __DIR__ . '/header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
* { font-family: 'Inter', sans-serif; }

.workflow-builder-container {
    background: #f8fafc;
    min-height: calc(100vh - 100px);
    padding: 30px 20px;
}

.workflow-header {
    background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);
    color: white;
    padding: 40px;
    border-radius: 16px;
    margin-bottom: 30px;
    box-shadow: 0 10px 40px rgba(139, 92, 246, 0.3);
}

.workflow-header h1 {
    font-size: 32px;
    font-weight: 800;
    margin-bottom: 10px;
}

.workflow-header p {
    font-size: 16px;
    opacity: 0.95;
}

.workflow-steps {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.workflow-step {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    border-left: 4px solid #e5e7eb;
    transition: all 0.3s ease;
}

.workflow-step.active {
    border-left-color: #8b5cf6;
    box-shadow: 0 4px 20px rgba(139, 92, 246, 0.15);
}

.workflow-step-number {
    width: 32px;
    height: 32px;
    background: #f3f4f6;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    color: #6b7280;
    margin-bottom: 12px;
}

.workflow-step.active .workflow-step-number {
    background: #8b5cf6;
    color: white;
}

.workflow-step-title {
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 5px;
}

.workflow-step-desc {
    font-size: 13px;
    color: #6b7280;
}

.workflow-canvas {
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    min-height: 500px;
}

.workflow-node {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    position: relative;
    transition: all 0.3s ease;
}

.workflow-node:hover {
    border-color: #8b5cf6;
    box-shadow: 0 4px 20px rgba(139, 92, 246, 0.1);
}

.workflow-node-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 15px;
}

.workflow-node-type {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
    color: #1f2937;
}

.workflow-node-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 16px;
}

.node-trigger .workflow-node-icon { background: #10b981; }
.node-condition .workflow-node-icon { background: #f59e0b; }
.node-action .workflow-node-icon { background: #3b82f6; }

.workflow-node-remove {
    color: #ef4444;
    cursor: pointer;
    font-size: 18px;
    opacity: 0;
    transition: opacity 0.3s;
}

.workflow-node:hover .workflow-node-remove {
    opacity: 1;
}

.workflow-connector {
    text-align: center;
    color: #9ca3af;
    margin: 15px 0;
    position: relative;
}

.workflow-connector::before {
    content: '';
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    width: 2px;
    height: 30px;
    background: #e5e7eb;
    z-index: -1;
}

.btn-add-node {
    width: 100%;
    padding: 15px;
    border: 2px dashed #d1d5db;
    background: #f9fafb;
    color: #6b7280;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-add-node:hover {
    border-color: #8b5cf6;
    background: #f5f3ff;
    color: #8b5cf6;
}
</style>

<div class="workflow-builder-container">
    <div class="container-fluid">
        <div class="workflow-header">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h1><i class="fas fa-project-diagram me-2"></i> Workflow Builder</h1>
                    <p>Bina business logic dan automation dengan visual workflow designer</p>
                </div>
                <a href="nocode_hub.php" class="btn btn-light">
                    <i class="fas fa-arrow-left me-2"></i> Kembali
                </a>
            </div>
        </div>

        <div class="workflow-steps">
            <div class="workflow-step active">
                <div class="workflow-step-number">1</div>
                <div class="workflow-step-title">App Info</div>
                <div class="workflow-step-desc">Nama & kategori aplikasi</div>
            </div>
            <div class="workflow-step">
                <div class="workflow-step-number">2</div>
                <div class="workflow-step-title">Data Schema</div>
                <div class="workflow-step-desc">Define fields & types</div>
            </div>
            <div class="workflow-step">
                <div class="workflow-step-number">3</div>
                <div class="workflow-step-title">Workflows</div>
                <div class="workflow-step-desc">Build automation rules</div>
            </div>
            <div class="workflow-step">
                <div class="workflow-step-number">4</div>
                <div class="workflow-step-title">UI Pages</div>
                <div class="workflow-step-desc">Design interface</div>
            </div>
            <div class="workflow-step">
                <div class="workflow-step-number">5</div>
                <div class="workflow-step-title">Deploy</div>
                <div class="workflow-step-desc">Publish your app</div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="workflow-canvas">
                    <div class="alert alert-info border-0 mb-4" style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-info-circle me-3 mt-1" style="font-size: 20px; color: #1e40af;"></i>
                            <div>
                                <h6 class="fw-bold mb-2" style="color: #1e3a8a;">🚧 Coming Soon!</h6>
                                <p class="mb-0 small" style="color: #1e40af;">
                                    Visual Workflow Builder sedang dalam pembangunan. 
                                    Buat masa ini, gunakan <strong>Data-Driven</strong> atau <strong>Visual Builder</strong> untuk cipta aplikasi.
                                </p>
                            </div>
                        </div>
                    </div>

                    <h5 class="mb-4">Preview: Workflow Designer</h5>

                    <!-- Sample Workflow -->
                    <div class="workflow-node node-trigger">
                        <div class="workflow-node-header">
                            <div class="workflow-node-type">
                                <div class="workflow-node-icon">
                                    <i class="fas fa-bolt"></i>
                                </div>
                                <span>Trigger: Record Created</span>
                            </div>
                        </div>
                        <div class="small text-muted">
                            Workflow akan trigger bila rekod baru ditambah
                        </div>
                    </div>

                    <div class="workflow-connector">
                        <i class="fas fa-arrow-down"></i>
                    </div>

                    <div class="workflow-node node-condition">
                        <div class="workflow-node-header">
                            <div class="workflow-node-type">
                                <div class="workflow-node-icon">
                                    <i class="fas fa-question"></i>
                                </div>
                                <span>Condition: Check Field Value</span>
                            </div>
                        </div>
                        <div class="small text-muted">
                            IF <strong>Status</strong> equals <strong>"Pending"</strong>
                        </div>
                    </div>

                    <div class="workflow-connector">
                        <i class="fas fa-arrow-down"></i>
                    </div>

                    <div class="workflow-node node-action">
                        <div class="workflow-node-header">
                            <div class="workflow-node-type">
                                <div class="workflow-node-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <span>Action: Send Email</span>
                            </div>
                        </div>
                        <div class="small text-muted">
                            Send notification to <strong>admin@example.com</strong>
                        </div>
                    </div>

                    <div class="workflow-connector mt-4">
                        <i class="fas fa-plus"></i>
                    </div>

                    <button class="btn-add-node">
                        <i class="fas fa-plus-circle me-2"></i> Add Node
                    </button>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 pt-3">
                        <h6 class="mb-0 fw-bold">🎯 Workflow Capabilities</h6>
                    </div>
                    <div class="card-body">
                        <h6 class="text-muted small mb-3">TRIGGERS</h6>
                        <ul class="list-unstyled small mb-4">
                            <li class="mb-2"><i class="fas fa-bolt text-success me-2"></i> Record Created</li>
                            <li class="mb-2"><i class="fas fa-bolt text-success me-2"></i> Record Updated</li>
                            <li class="mb-2"><i class="fas fa-bolt text-success me-2"></i> Record Deleted</li>
                            <li class="mb-2"><i class="fas fa-clock text-warning me-2"></i> Scheduled (Cron)</li>
                            <li class="mb-2"><i class="fas fa-globe text-info me-2"></i> Webhook Received</li>
                        </ul>

                        <h6 class="text-muted small mb-3">CONDITIONS</h6>
                        <ul class="list-unstyled small mb-4">
                            <li class="mb-2"><i class="fas fa-equals text-warning me-2"></i> Field Equals Value</li>
                            <li class="mb-2"><i class="fas fa-greater-than text-warning me-2"></i> Greater Than / Less Than</li>
                            <li class="mb-2"><i class="fas fa-check-circle text-warning me-2"></i> Field Is Empty / Not Empty</li>
                            <li class="mb-2"><i class="fas fa-code-branch text-warning me-2"></i> AND / OR Logic</li>
                        </ul>

                        <h6 class="text-muted small mb-3">ACTIONS</h6>
                        <ul class="list-unstyled small mb-4">
                            <li class="mb-2"><i class="fas fa-envelope text-primary me-2"></i> Send Email</li>
                            <li class="mb-2"><i class="fas fa-database text-primary me-2"></i> Create/Update Record</li>
                            <li class="mb-2"><i class="fas fa-bell text-primary me-2"></i> Create Notification</li>
                            <li class="mb-2"><i class="fas fa-paper-plane text-primary me-2"></i> Send to API</li>
                            <li class="mb-2"><i class="fas fa-calculator text-primary me-2"></i> Calculate Value</li>
                        </ul>

                        <div class="alert alert-warning border-0 small mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Note:</strong> Untuk guna workflow sekarang, pilih <a href="wizard.php">Wizard Builder</a> dan configure di Step 4.
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-header bg-white border-0 pt-3">
                        <h6 class="mb-0 fw-bold">📚 Resources</h6>
                    </div>
                    <div class="card-body">
                        <a href="ARCHITECTURE_3IN1_BUILDER.md" target="_blank" class="btn btn-outline-primary btn-sm w-100 mb-2">
                            <i class="fas fa-book me-2"></i> Architecture Docs
                        </a>
                        <a href="nocode_hub.php" class="btn btn-outline-secondary btn-sm w-100">
                            <i class="fas fa-home me-2"></i> Builder Hub
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Placeholder for future workflow builder logic
console.log('Workflow Builder - Coming Soon!');
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
