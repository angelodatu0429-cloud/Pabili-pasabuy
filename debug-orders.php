<?php
/**
 * Debug Orders Page
 * Shows all orders and their status values to help troubleshoot
 */

session_start();

require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

// Check authentication
requireLogin();

$pageTitle = 'Debug Orders';

$allOrders = [];
$statusSummary = [];

try {
    $allOrders = $pdo->getAllDocuments('Orders') ?? [];
    
    // Analyze all statuses
    foreach ($allOrders as $orderId => $order) {
        $status = $order['status'] ?? $order['orderStatus'] ?? $order['order_status'] ?? 'NO STATUS FIELD';
        $statusKey = (string)$status;
        
        if (!isset($statusSummary[$statusKey])) {
            $statusSummary[$statusKey] = 0;
        }
        $statusSummary[$statusKey]++;
    }
    
} catch (Exception $e) {
    error_log('Error fetching orders for debug: ' . $e->getMessage());
}

require_once 'includes/sidebar.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1><i class="bi bi-bug"></i> Debug Orders Database</h1>
</div>

<!-- Main Content -->
<div class="main-container">
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-info">
                <strong>Total Orders in Database:</strong> <?php echo count($allOrders); ?>
            </div>
        </div>
    </div>

    <!-- Status Summary -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">Status Distribution</h5>
        </div>
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead class="bg-light">
                    <tr>
                        <th>Status Value</th>
                        <th>Count</th>
                        <th>Type</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($statusSummary as $status => $count): ?>
                        <tr>
                            <td>
                                <code><?php echo htmlspecialchars($status); ?></code>
                            </td>
                            <td>
                                <strong><?php echo $count; ?></strong>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <?php 
                                    if (strtolower(trim($status)) === 'completed' || strtolower(trim($status)) === 'delivered') {
                                        echo '<span class="badge bg-success">✓ MATCHES COMPLETED</span>';
                                    } else {
                                        echo 'Other Status';
                                    }
                                    ?>
                                </small>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- All Orders Detail -->
    <div class="card">
        <div class="card-header bg-warning">
            <h5 class="mb-0">All Orders Details</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Order ID</th>
                            <th>Status Field Value</th>
                            <th>User ID</th>
                            <th>Driver ID</th>
                            <th>Total</th>
                            <th>Has Items?</th>
                            <th>Has Images?</th>
                            <th>Created/Completed At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($allOrders)): ?>
                            <?php foreach ($allOrders as $orderId => $order): ?>
                                <?php
                                $status = $order['status'] ?? $order['orderStatus'] ?? $order['order_status'] ?? 'N/A';
                                $userId = $order['user_id'] ?? $order['userId'] ?? 'N/A';
                                $driverId = $order['driver_id'] ?? $order['driverId'] ?? 'N/A';
                                $total = $order['total'] ?? $order['totalAmount'] ?? 0;
                                $items = $order['items'] ?? $order['orderItems'] ?? [];
                                $images = $order['delivery_images'] ?? $order['deliveryImages'] ?? [];
                                $completedAt = $order['completed_at'] ?? $order['completedAt'] ?? $order['deliveredAt'] ?? 'N/A';
                                ?>
                                <tr>
                                    <td>
                                        <code><?php echo htmlspecialchars($orderId); ?></code>
                                    </td>
                                    <td>
                                        <code><?php echo htmlspecialchars((string)$status); ?></code>
                                        <?php if (in_array(strtolower(trim((string)$status)), ['completed', 'delivered'])): ?>
                                            <br><span class="badge bg-success">Matches</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><small><?php echo htmlspecialchars((string)$userId); ?></small></td>
                                    <td><small><?php echo htmlspecialchars((string)$driverId); ?></small></td>
                                    <td><?php echo htmlspecialchars((string)$total); ?></td>
                                    <td>
                                        <?php if (is_array($items) && count($items) > 0): ?>
                                            <span class="badge bg-success"><?php echo count($items); ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">No</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (is_array($images) && count($images) > 0): ?>
                                            <span class="badge bg-success"><?php echo count($images); ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">No</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small>
                                            <?php 
                                            if (is_object($completedAt)) {
                                                echo ($completedAt instanceof DateTime) ? $completedAt->format('Y-m-d H:i') : 'Object';
                                            } else {
                                                echo htmlspecialchars((string)$completedAt);
                                            }
                                            ?>
                                        </small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-3">
                                    <strong>No orders found in database</strong>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Raw JSON Data -->
    <div class="card mt-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">Raw JSON Data (First 3 Orders)</h5>
        </div>
        <div class="card-body" style="background-color: #f8f9fa;">
            <pre style="background: white; padding: 15px; border-radius: 5px; overflow-x: auto;"><code><?php 
                $firstThree = array_slice($allOrders, 0, 3, true);
                echo json_encode($firstThree, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            ?></code></pre>
        </div>
    </div>

    <!-- Troubleshooting Guide -->
    <div class="card mt-4 border-warning">
        <div class="card-header bg-warning">
            <h5 class="mb-0">Troubleshooting Guide</h5>
        </div>
        <div class="card-body">
            <h6>Why am I not seeing completed orders?</h6>
            <ol>
                <li>
                    <strong>No orders in database:</strong><br>
                    Check if "Total Orders in Database" is 0. If yes, you have no orders yet.
                </li>
                <li>
                    <strong>No completed status values:</strong><br>
                    Look at the "Status Distribution" table. Do you see any status that looks like "completed"?
                    <br><br>
                    <strong>Common variations:</strong>
                    <ul>
                        <li><code>completed</code> ✓ (will work)</li>
                        <li><code>Completed</code> ✓ (will work)</li>
                        <li><code>COMPLETED</code> ✓ (will work)</li>
                        <li><code>delivered</code> ✓ (will work)</li>
                        <li><code>complete</code> ✓ (will work)</li>
                        <li><code>pending</code> ✗ (won't match)</li>
                        <li><code>in_progress</code> ✗ (won't match)</li>
                    </ul>
                </li>
                <li>
                    <strong>Wrong field name:</strong><br>
                    Check if the status field is named something other than:
                    <ul>
                        <li><code>status</code></li>
                        <li><code>orderStatus</code></li>
                        <li><code>order_status</code></li>
                    </ul>
                    If you find a different field name, let me know and I'll update the code.
                </li>
                <li>
                    <strong>Check raw JSON data:</strong><br>
                    Scroll down to see the raw JSON from your first 3 orders. Look for the status field.
                </li>
            </ol>

            <hr>

            <h6>Next Steps:</h6>
            <ul>
                <li>
                    <strong>If you see completed orders above:</strong><br>
                    Go back to <a href="completed-orders.php">Completed Orders</a> - it should now display all orders
                </li>
                <li>
                    <strong>If status field is different:</strong><br>
                    Tell me the exact field name and value, and I'll update completed-orders.php
                </li>
                <li>
                    <strong>If no orders exist:</strong><br>
                    You need to create some test orders with completed status first
                </li>
            </ul>
        </div>
    </div>

</div>

<style>
    code {
        background-color: #f0f0f0;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.9em;
    }

    pre {
        max-height: 400px;
        font-size: 0.85em;
    }
</style>

<?php
require_once 'includes/footer.php';
?>
