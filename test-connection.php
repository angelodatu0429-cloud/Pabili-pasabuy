<?php
/**
 * Firestore Connection Test Page
 * Test if data is being retrieved from Firestore collections
 */

session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

$testResults = [];

// Test 1: Check "Users" collection
try {
    $users = $pdo->getAllDocuments('Users') ?? [];
    $testResults['Users'] = [
        'status' => 'success',
        'count' => count($users),
        'data' => $users
    ];
} catch (Exception $e) {
    $testResults['Users'] = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
}

// Test 2: Check "Riders" collection
try {
    $riders = $pdo->getAllDocuments('Riders') ?? [];
    $testResults['Riders'] = [
        'status' => 'success',
        'count' => count($riders),
        'data' => $riders
    ];
} catch (Exception $e) {
    $testResults['Riders'] = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
}

// Test 3: Check "admin" collection
try {
    $admins = $pdo->getAllDocuments('admin') ?? [];
    $testResults['admin'] = [
        'status' => 'success',
        'count' => count($admins),
        'data' => $admins
    ];
} catch (Exception $e) {
    $testResults['admin'] = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
}

// Test 4: Check "orders" collection
try {
    $orders = $pdo->getAllDocuments('orders') ?? [];
    $testResults['orders'] = [
        'status' => 'success',
        'count' => count($orders),
        'data' => $orders
    ];
} catch (Exception $e) {
    $testResults['orders'] = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firestore Connection Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: #f5f5f5;
            padding: 2rem;
        }
        .test-card {
            border-radius: 10px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .test-header {
            padding: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .test-body {
            padding: 1rem;
            background: white;
        }
        .badge-count {
            font-size: 1.2rem;
            font-weight: bold;
        }
        code {
            background: #f4f4f4;
            padding: 0.5rem;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        .data-preview {
            background: #f9f9f9;
            padding: 1rem;
            border-radius: 5px;
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            font-size: 0.85rem;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="mb-4">
            <h1><i class="bi bi-cloud-check"></i> Firestore Connection Test</h1>
            <p class="text-muted">Testing connections to all Firestore collections</p>
        </div>

        <?php foreach ($testResults as $collection => $result): ?>
            <div class="card test-card">
                <div class="test-header <?php echo $result['status'] === 'success' ? 'success' : 'error'; ?>">
                    <i class="bi <?php echo $result['status'] === 'success' ? 'bi-check-circle' : 'bi-x-circle'; ?>"></i>
                    <strong><?php echo htmlspecialchars($collection); ?></strong>
                    <?php if ($result['status'] === 'success'): ?>
                        <span class="badge bg-success badge-count ms-auto">
                            <?php echo $result['count']; ?> documents
                        </span>
                    <?php else: ?>
                        <span class="badge bg-danger ms-auto">Error</span>
                    <?php endif; ?>
                </div>
                
                <div class="test-body">
                    <?php if ($result['status'] === 'success'): ?>
                        <p><strong>Status:</strong> <span class="badge bg-success">Connected</span></p>
                        <p><strong>Document Count:</strong> <code><?php echo $result['count']; ?></code></p>
                        
                        <?php if (!empty($result['data'])): ?>
                            <p><strong>Sample Data (First Document):</strong></p>
                            <div class="data-preview">
                                <?php echo htmlspecialchars(json_encode($result['data'][0], JSON_PRETTY_PRINT)); ?>
                            </div>
                        <?php else: ?>
                            <p class="text-warning">
                                <i class="bi bi-exclamation-triangle"></i>
                                Collection is empty
                            </p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p><strong>Status:</strong> <span class="badge bg-danger">Connection Failed</span></p>
                        <p><strong>Error Message:</strong></p>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($result['message']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-info-circle"></i> Test Summary</h5>
                <p>All collections are being tested. Check the results above to ensure your Firestore database is properly configured.</p>
                <p class="mb-0">
                    <strong>Note:</strong> If you see "empty collection" messages, you may need to add sample data to your collections.
                </p>
            </div>
        </div>

        <div class="mt-4">
            <a href="index.php" class="btn btn-primary">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
            <a href="test-connection.php" class="btn btn-secondary">
                <i class="bi bi-arrow-clockwise"></i> Refresh Test
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
