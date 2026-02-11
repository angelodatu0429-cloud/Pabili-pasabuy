<?php
/**
 * Debug: Browse ALL Firestore Collections and Data
 */
session_start();

require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

requireLogin();

// List of common collection names to try
$collectionNames = [
    'Users',
    'Riders',
    'verifications',
    'verification_ids',
    'Orders',
    'products',
    'deliveryFees',
    'settings',
    'transactions',
    'reviews',
    'notifications',
    'messages',
    'payments',
    'ratings',
    'admins',
    'support',
    'reports',
    'categories',
];

$collectionsData = [];

// Try to fetch each collection
foreach ($collectionNames as $collName) {
    try {
        $docs = $pdo->getAllDocuments($collName) ?? [];
        if (!empty($docs)) {
            $collectionsData[$collName] = [
                'count' => count($docs),
                'docs' => $docs
            ];
        }
    } catch (Exception $e) {
        // Collection may not exist, skip
    }
}

// Get selected collection to display (via GET parameter)
$selectedCollection = sanitize($_GET['collection'] ?? '');
$selectedDocs = [];
if ($selectedCollection && isset($collectionsData[$selectedCollection])) {
    $selectedDocs = $collectionsData[$selectedCollection]['docs'];
}

?>
<div class="main-container">
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Firestore Database Browser</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">Browse all Firestore collections and their documents.</p>
            
            <div class="row mb-4">
                <?php foreach ($collectionsData as $collName => $data): ?>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <a href="?collection=<?php echo urlencode($collName); ?>" class="card h-100 text-decoration-none <?php echo $selectedCollection === $collName ? 'border-primary bg-light' : ''; ?>">
                            <div class="card-body">
                                <h6 class="card-title"><?php echo htmlspecialchars($collName); ?></h6>
                                <p class="card-text display-6 fw-bold text-primary"><?php echo intval($data['count']); ?></p>
                                <small class="text-muted">documents</small>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($collectionsData)): ?>
                <div class="alert alert-warning">No collections found in Firestore.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Selected Collection Details -->
    <?php if ($selectedCollection && !empty($selectedDocs)): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-light">
                <h6 class="mb-0">Collection: <strong><?php echo htmlspecialchars($selectedCollection); ?></strong> (<?php echo count($selectedDocs); ?> documents)</h6>
            </div>
            <div class="card-body">
                <?php foreach ($selectedDocs as $idx => $doc): ?>
                    <div class="card mb-3 border">
                        <div class="card-header bg-light">
                            <strong>Document #<?php echo $idx + 1; ?></strong> 
                            <?php if (isset($doc['id'])): ?>
                                <code class="float-end"><?php echo htmlspecialchars(substr($doc['id'], 0, 30)); ?>...</code>
                            <?php endif; ?>
                        </div>
                        <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 30%;">Field</th>
                                        <th>Value (truncated)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($doc as $key => $val): ?>
                                        <tr>
                                            <td>
                                                <code><?php echo htmlspecialchars($key); ?></code>
                                            </td>
                                            <td>
                                                <?php
                                                    if (is_array($val)) {
                                                        echo '<pre style="margin:0; font-size:0.85rem;">' . htmlspecialchars(json_encode($val, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . '</pre>';
                                                    } elseif (is_bool($val)) {
                                                        echo '<span class="badge ' . ($val ? 'bg-success' : 'bg-secondary') . '">' . ($val ? 'true' : 'false') . '</span>';
                                                    } elseif (is_string($val) && strlen($val) > 150) {
                                                        $url = false;
                                                        if (strpos($val, 'http://') === 0 || strpos($val, 'https://') === 0) {
                                                            $url = true;
                                                        }
                                                        if ($url) {
                                                            echo '<a href="' . htmlspecialchars($val) . '" target="_blank" class="text-truncate d-inline-block" style="max-width:300px;" title="' . htmlspecialchars($val) . '">' . htmlspecialchars(substr($val, 0, 100)) . '...</a>';
                                                        } else {
                                                            echo '<code style="word-break:break-all;">' . htmlspecialchars(substr($val, 0, 150)) . '...</code>';
                                                        }
                                                    } else {
                                                        if (is_string($val) && (strpos($val, 'http://') === 0 || strpos($val, 'https://') === 0)) {
                                                            echo '<a href="' . htmlspecialchars($val) . '" target="_blank" class="text-truncate d-inline-block" style="max-width:300px;" title="' . htmlspecialchars($val) . '">' . htmlspecialchars(substr($val, 0, 100)) . '</a>';
                                                        } else {
                                                            echo '<code>' . htmlspecialchars(var_export($val, true)) . '</code>';
                                                        }
                                                    }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
