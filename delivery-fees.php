<?php
/**
 * Delivery Rates Settings
 * Simplified page: only store average base fee and average per-km rate
 */

session_start();

require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

// Check authentication
requireLogin();

$pageTitle = 'Delivery Rates';
$message = '';
$messageType = '';

// CSRF token
$csrf_token = generateCSRFToken();

// Firestore collection for delivery fees
$feesCollection = 'DeliveryFees';
$feesDocId = 'rates';

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $avg_base = (float)($_POST['avg_base_fee'] ?? 0);
        $avg_km = (float)($_POST['avg_per_km_rate'] ?? 0);

        try {
            $data = [
                'avg_base_fee' => $avg_base,
                'avg_per_km_rate' => $avg_km,
                'updated_at' => new DateTime(),
                'updated_by' => $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 'admin',
                'description' => 'Average delivery rates for the platform'
            ];

            // Use set() to upsert (create if doesn't exist, update if exists)
            $pdo->set($feesCollection, $feesDocId, $data);

            $message = 'Delivery rates saved successfully to DeliveryFees collection.';
            $messageType = 'success';
        } catch (Exception $e) {
            error_log('Error saving delivery rates to Firestore: ' . $e->getMessage());
            $message = 'Failed to save delivery rates.';
            $messageType = 'danger';
        }
    } else {
        $message = 'Invalid CSRF token.';
        $messageType = 'danger';
    }
}

// Load current delivery fees from Firestore
$settings = [];
try {
    $settings = $pdo->getDocument($feesCollection, $feesDocId) ?? [];
    if (empty($settings)) {
        // Initialize with default values if collection doesn't exist
        $settings = ['avg_base_fee' => 0, 'avg_per_km_rate' => 0];
    }
} catch (Exception $e) {
    error_log('Error fetching delivery fees from Firestore: ' . $e->getMessage());
    $settings = ['avg_base_fee' => 0, 'avg_per_km_rate' => 0];
}

require_once 'includes/sidebar.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1><i class="bi bi-truck-front"></i> Delivery Rates</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active">Delivery Rates</li>
        </ol>
    </nav>
</div>

<div class="main-container">
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-primary text-white py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0">Delivery Fees Configuration</h5>
                        <span class="badge bg-light text-dark">DeliveryFees Collection</span>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex align-items-start gap-4">
                        <div class="flex-grow-1">
                            <h4 class="mb-1">Set Average Delivery Rates</h4>
                            <p class="text-muted mb-3"><i class="bi bi-info-circle"></i> These values are stored in your Firestore <code>DeliveryFees</code> collection and retrieved across your platform for delivery calculations.</p>

                            <form method="POST" class="row g-3" id="ratesForm">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                                <div class="col-md-6">
                                    <label class="form-label small text-uppercase text-muted">Average Base Fee</label>
                                    <div class="input-group input-group-lg mb-2">
                                        <span class="input-group-text">₱</span>
                                        <input type="number" step="0.01" name="avg_base_fee" id="avgBase" class="form-control form-control-lg" value="<?php echo htmlspecialchars($settings['avg_base_fee'] ?? 0); ?>" required>
                                    </div>
                                    <small class="text-muted">Fixed delivery fee applied to most orders.</small>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small text-uppercase text-muted">Average Per KM Rate</label>
                                    <div class="input-group input-group-lg mb-2">
                                        <span class="input-group-text">₱</span>
                                        <input type="number" step="0.01" name="avg_per_km_rate" id="avgKm" class="form-control form-control-lg" value="<?php echo htmlspecialchars($settings['avg_per_km_rate'] ?? 0); ?>" required>
                                    </div>
                                    <small class="text-muted">Additional fee per kilometer.</small>
                                </div>

                                <div class="col-12">
                                    <div class="alert alert-info mb-3" role="alert">
                                        <i class="bi bi-info-circle"></i> <strong>Collection Info:</strong> Data is persisted in the <code>DeliveryFees/rates</code> document in Firestore and updated with timestamp and admin information.
                                    </div>
                                </div>

                                <div class="col-12 text-end">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="bi bi-save"></i> Save to Firestore
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div style="width: 220px;">
                            <div class="p-3 rounded-3 text-center" style="background: linear-gradient(180deg,#f8fafc,#ffffff); border:1px solid #eef2f7;">
                                <h6 class="text-muted mb-2">Current Preview</h6>
                                <div class="mb-2">
                                    <div class="fw-semibold text-success" id="previewBase" style="font-size:1.35rem"><?php echo formatCurrency($settings['avg_base_fee'] ?? 0); ?></div>
                                    <small class="text-muted">Base Fee</small>
                                </div>
                                <div>
                                    <div class="fw-semibold text-primary" id="previewKm" style="font-size:1.15rem"><?php echo formatCurrency($settings['avg_per_km_rate'] ?? 0); ?>/km</div>
                                    <small class="text-muted">Per KM</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Tidy spacing and visual emphasis for the delivery rates card */
    .card-body h4 { font-weight: 700; }
    .form-control-lg { border-radius: 10px; }
    .input-group-text { background: #f8fafc; border-right: 0; }
    @media (max-width: 767px) {
        #previewBase, #previewKm { font-size: 1rem !important; }
    }
</style>

<script>
    // Live preview update
    document.addEventListener('DOMContentLoaded', function(){
        const base = document.getElementById('avgBase');
        const km = document.getElementById('avgKm');
        const pBase = document.getElementById('previewBase');
        const pKm = document.getElementById('previewKm');

        function fmt(v){
            const n = Number(v) || 0;
            return '₱' + n.toFixed(2);
        }

        function update(){
            pBase.textContent = fmt(base.value);
            pKm.textContent = fmt(km.value) + '/km';
        }

        base.addEventListener('input', update);
        km.addEventListener('input', update);
    });
</script>

<?php require_once 'includes/footer.php'; ?>
