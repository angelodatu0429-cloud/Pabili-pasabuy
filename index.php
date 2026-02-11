<?php
/**
 * Dashboard Page
 * Main admin dashboard with key statistics and recent orders
 */

session_start();

require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

// Check authentication
requireLogin();

$pageTitle = 'Dashboard';

// Fetch statistics
$totalCustomers = getTotalCustomersCount($pdo);
$totalRiders = getTotalRidersCount($pdo);
$todayRevenue = getTodayRevenue($pdo);
$activeDrivers = getActiveDriversCount($pdo);
$totalCompleted = getTotalCompletedOrders($pdo);

// Fetch pending verifications (Logic synced with verifications.php)
$pendingVerifications = 0;
try {
    // This logic is a robust, streamlined version of verifications.php to ensure the count is identical.

    // 1. Fetch all raw data sources
    $allFirestoreVerifications = $pdo->getAllDocuments('verifications') ?? [];
    $allVerificationIds = $pdo->getAllDocuments('verification_ids') ?? [];
    $allUsers = $pdo->getAllDocuments('Users') ?? [];
    $legacyUsers = $pdo->getAllDocuments('users') ?? [];
    $allUsers = array_merge($allUsers, $legacyUsers);
    $allRiders = $pdo->getAllDocuments('Riders') ?? [];

    // 2. Process all potential verifications into a unified list
    $allVerifications = array_merge($allFirestoreVerifications, $allVerificationIds);

    // Check for any verification-related field to identify potential embedded verifications
    $verificationFields = [
        'validIdSelfieUrl', 'selfie', 'profileImagePath', 'profile_picture', 'profilePhotoUrl', 'profilePictureUrl',
        'validIdFrontUrl', 'front_image', 'driverLicenseFrontUrl', 'licensesFrontUrl', 'licenseIdFrontUrl',
        'validIdBackUrl', 'back_image', 'driverLicenseBackUrl', 'licensesBackUrl', 'licenseIdBackUrl',
        'vehicleORCRFrontUrl', 'vehicleRegistrationFront', 'vehicleORFront', 'carRegistrationFront', 'vehicleORFrontUrl',
        'vehicleORCRBackUrl', 'vehicleRegistrationBack', 'vehicleORBack', 'carRegistrationBack', 'vehicleORBackUrl'
    ];
    $checkEmbedded = function($doc) use ($verificationFields) {
        foreach ($verificationFields as $field) {
            if (isset($doc[$field]) && !empty($doc[$field])) return true;
        }
        if (isset($doc['id_verified']) && filter_var($doc['id_verified'], FILTER_VALIDATE_BOOLEAN)) return true;
        if (isset($doc['verificationStatus']) && strtoupper($doc['verificationStatus']) === 'PENDING') return true;
        return false;
    };

    // Extract from Users
    foreach ($allUsers as $user) {
        if ($checkEmbedded($user)) {
            $status = ($user['id_verified'] ?? false) ? 'approved' : 'pending';
            $allVerifications[] = [
                'user_id' => $user['id'],
                'status' => $status,
                'submitted_at' => $user['created_at'] ?? null,
                'username' => $user['name'] ?? $user['username'] ?? 'Unknown'
            ];
        }
    }

    // Extract from Riders
    foreach ($allRiders as $rider) {
        if ($checkEmbedded($rider)) {
            $status = 'pending';
            if (isset($rider['id_verified']) && filter_var($rider['id_verified'], FILTER_VALIDATE_BOOLEAN)) {
                $status = 'approved';
            } elseif (isset($rider['verificationStatus'])) {
                $vStatus = strtoupper($rider['verificationStatus']);
                if ($vStatus === 'APPROVED' || $vStatus === 'VERIFIED') $status = 'approved';
                elseif ($vStatus === 'REJECTED') $status = 'rejected';
            }
            $allVerifications[] = [
                'user_id' => $rider['id'],
                'status' => $status,
                'submitted_at' => $rider['created_at'] ?? null,
                'username' => $rider['fullName'] ?? $rider['name'] ?? 'Unknown'
            ];
        }
    }

    // 3. Attach usernames to non-embedded verifications for filtering
    $userMap = array_column($allUsers, null, 'id');
    $riderMap = array_column($allRiders, null, 'id');
    foreach ($allVerifications as &$v) {
        if ((empty($v['username']) || $v['username'] === 'Unknown') && !empty($v['user_id'])) {
            $userId = $v['user_id'];
            if (isset($userMap[$userId])) {
                $v['username'] = $userMap[$userId]['name'] ?? $userMap[$userId]['username'] ?? 'Unknown';
            } elseif (isset($riderMap[$userId])) {
                $v['username'] = $riderMap[$userId]['fullName'] ?? $riderMap[$userId]['name'] ?? 'Unknown';
            }
        }
    }
    unset($v);

    // 4. Deduplicate by user_id, keeping the most relevant one (pending > other)
    $groupedVerifications = [];
    foreach ($allVerifications as $v) {
        if (empty($v['user_id'])) continue;
        $uid = $v['user_id'];
        $groupedVerifications[$uid][] = $v;
    }

    $finalVerifications = [];
    foreach ($groupedVerifications as $group) {
        if (count($group) === 1) {
            $finalVerifications[] = $group[0];
        } else {
            usort($group, function($a, $b) {
                $statusA = $a['status'] ?? '';
                $statusB = $b['status'] ?? '';
                if ($statusA === 'pending' && $statusB !== 'pending') return -1;
                if ($statusB === 'pending' && $statusA !== 'pending') return 1;
                $tsA = ($a['submitted_at'] ?? 0) instanceof DateTime ? ($a['submitted_at'])->getTimestamp() : strtotime($a['submitted_at'] ?? 'now');
                $tsB = ($b['submitted_at'] ?? 0) instanceof DateTime ? ($b['submitted_at'])->getTimestamp() : strtotime($b['submitted_at'] ?? 'now');
                return $tsB - $tsA;
            });
            $finalVerifications[] = $group[0];
        }
    }

    // 5. Filter out blank/invalid users
    $finalVerifications = array_filter($finalVerifications, function($v) {
        $name = $v['username'] ?? '';
        return !empty($name) && $name !== 'Unknown' && $name !== 'N/A';
    });

    // 6. Count pending
    foreach ($finalVerifications as $v) {
        if (isset($v['status']) && $v['status'] === 'pending') {
            $pendingVerifications++;
        }
    }
} catch (Exception $e) {
    error_log('Error fetching pending verifications: ' . $e->getMessage());
}

// Fetch latest completed orders
$recentOrders = [];
try {
    // Use the shared function from functions.php to ensure consistency with completed-orders.php
    $allCompletedOrders = fetchCompletedOrders($pdo);
    
    // Limit to 8 most recent
    $recentOrders = array_slice($allCompletedOrders, 0, 8);
} catch (Exception $e) {
    error_log('Error fetching recent orders: ' . $e->getMessage());
}

require_once 'includes/sidebar.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1><i class="bi bi-speedometer2"></i> Dashboard</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item active">Home</li>
        </ol>
    </nav>
</div>

<!-- Main Content -->
<div class="main-container">
    <!-- Statistics Cards -->
    <div class="row mb-4">
            <!-- Overall Total Customer -->
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="stat-card">
                    <div class="stat-icon bg-primary">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div class="stat-content">
                        <h6 class="stat-label">Overall Total Customer</h6>
                        <p class="stat-value"><?php echo $totalCustomers; ?></p>
                    </div>
                </div>
            </div>

            <!-- Overall Total Riders -->
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="stat-card">
                    <div class="stat-icon bg-secondary">
                        <i class="bi bi-truck-front-fill"></i>
                    </div>
                    <div class="stat-content">
                        <h6 class="stat-label">Overall Total Riders</h6>
                        <p class="stat-value"><?php echo $totalRiders; ?></p>
                    </div>
                </div>
            </div>

        <!-- Today's Revenue -->
        <div class="col-md-6 col-lg-4 mb-3">
            <div class="stat-card">
                <div class="stat-icon bg-success">
                    <i class="bi bi-currency-dollar"></i>
                </div>
                <div class="stat-content">
                    <h6 class="stat-label">Today's Revenue</h6>
                    <p class="stat-value"><?php echo formatCurrency($todayRevenue); ?></p>
                </div>
            </div>
        </div>

        <!-- Active Drivers -->
        <div class="col-md-6 col-lg-4 mb-3">
            <div class="stat-card">
                <div class="stat-icon bg-info">
                    <i class="bi bi-person-check-fill"></i>
                </div>
                <div class="stat-content">
                    <h6 class="stat-label">Active Drivers</h6>
                    <p class="stat-value"><?php echo $activeDrivers; ?></p>
                </div>
            </div>
        </div>

        <!-- Pending Verifications -->
        <div class="col-md-6 col-lg-4 mb-3">
            <div class="stat-card <?php echo $pendingVerifications > 0 ? 'border-warning' : ''; ?>">
                <div class="stat-icon bg-warning">
                    <i class="bi bi-file-earmark-check"></i>
                </div>
                <div class="stat-content">
                    <h6 class="stat-label">Pending Verifications</h6>
                    <p class="stat-value"><?php echo $pendingVerifications; ?></p>
                </div>
            </div>
        </div>

        <!-- Total Completed Orders -->
        <div class="col-md-6 col-lg-4 mb-3">
            <div class="stat-card">
                <div class="stat-icon bg-success">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="stat-content">
                    <h6 class="stat-label">Total Completed</h6>
                    <p class="stat-value"><?php echo $totalCompleted; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Orders Section -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history"></i> Latest Completed Orders
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($recentOrders)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Driver</th>
                                        <th>Amount</th>
                                        <th>Completed Time</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentOrders as $order): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-light text-dark">#<?php echo htmlspecialchars($order['id']); ?></span>
                                            </td>
                                            <td><?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($order['driver_name'] ?? 'N/A'); ?></td>
                                            <td>
                                                <strong><?php echo formatCurrency($order['total']); ?></strong>
                                            </td>
                                            <td>
                                                <small><?php echo formatDate($order['completed_at']); ?></small>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary view-order-btn" 
                                                        data-order-id="<?php echo htmlspecialchars($order['id']); ?>">
                                                    <i class="bi bi-eye"></i> View
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info m-3 mb-0">
                            <i class="bi bi-info-circle"></i> No completed orders yet.
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-light">
                    <a href="completed-orders.php" class="btn btn-sm btn-primary">
                        <i class="bi bi-arrow-right"></i> View All Orders
                    </a>
                </div>
            </div>
        </div>
    </div>

</div> <!-- main-container -->

<style>
    /* Statistics Cards */
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1.5rem;
        border: 1px solid #e5e7eb;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .stat-card:hover {
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
    }

    .stat-card.border-warning {
        border-left: 4px solid #f59e0b;
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
    }

    .stat-icon.bg-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .stat-icon.bg-success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }

    .stat-icon.bg-info {
        background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
    }

    .stat-icon.bg-warning {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    }

    .stat-label {
        font-size: 0.875rem;
        color: #6b7280;
        margin: 0;
        font-weight: 500;
    }

    .stat-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: #1f2937;
        margin: 0.5rem 0 0 0;
    }

    /* Cards */
    .card {
        border-radius: 12px;
        overflow: hidden;
    }

    .card-header {
        padding: 1.5rem;
    }

    .card-header h5 {
        color: #1f2937;
        font-weight: 600;
    }

    .table-hover tbody tr:hover {
        background-color: #f9fafb;
    }

    .btn {
        border-radius: 8px;
        font-weight: 500;
        padding: 0.5rem 1rem;
    }

    .btn-outline-primary {
        color: #667eea;
        border-color: #667eea;
    }

    .btn-outline-primary:hover {
        background-color: #667eea;
        border-color: #667eea;
    }
</style>

<!-- Order Details Modal (Dashboard Version) -->
<div class="modal fade" id="dashboardOrderModal" tabindex="-1" size="xl">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Order Details - <span id="modalOrderId"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-4">
                    <!-- Customer Info -->
                    <div class="col-md-6 mb-3">
                        <h6 class="text-muted mb-2">Customer Information</h6>
                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <p class="mb-1"><strong>Name:</strong> <span id="customerName"></span></p>
                                <p class="mb-1"><strong>Email:</strong> <span id="customerEmail"></span></p>
                                <p class="mb-1"><strong>Phone:</strong> <span id="customerPhone"></span></p>
                                <div class="mt-2">
                                    <strong>Delivery Address:</strong>
                                    <p id="deliveryAddress" class="mb-0 mt-1 text-muted" style="white-space: pre-wrap;"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Driver Info -->
                    <div class="col-md-6 mb-3">
                        <h6 class="text-muted mb-2">Driver Information</h6>
                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <p class="mb-1"><strong>Name:</strong> <span id="driverName"></span></p>
                                <p class="mb-1"><strong>Phone:</strong> <span id="driverPhone"></span></p>
                                <p class="mb-1"><strong>Completed:</strong> <span id="completedAt"></span></p>
                                <p class="mb-1"><strong>Payment:</strong> <span id="paymentBadge"></span></p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Order Items -->
                <h6 class="text-muted mb-2">Order Items</h6>
                <div class="card border-0 mb-4">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Price</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody id="orderItemsTable"></tbody>
                                <tfoot class="bg-light">
                                    <tr>
                                        <th colspan="3">Total Amount</th>
                                        <th id="orderTotal"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- Delivery Proof Images -->
                <h6 class="text-muted mb-3">Delivery Proof Photos</h6>
                <div id="imageGallery" class="row g-3 mb-4"></div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Image Enlargement Modal -->
<div class="modal fade" id="dashboardImageModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delivery Proof Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="enlargedImage" src="" alt="Delivery Proof" class="img-fluid" style="max-height: 600px;">
            </div>
        </div>
    </div>
</div>

<style>
    .gallery-item { position: relative; overflow: hidden; border-radius: 8px; background: #f0f0f0; cursor: pointer; }
    .gallery-item img { width: 100%; height: 150px; object-fit: cover; transition: all 0.3s ease; }
    .gallery-item:hover img { transform: scale(1.05); opacity: 0.8; }
    .gallery-label { position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.7); color: white; padding: 0.5rem; font-size: 0.85rem; font-weight: 500; }
</style>

<script>
    const recentOrdersData = {};
    <?php foreach ($recentOrders as $order): ?>
    recentOrdersData[<?php echo json_encode((string)$order['id']); ?>] = <?php echo json_encode($order, JSON_UNESCAPED_UNICODE); ?>;
    <?php endforeach; ?>

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.view-order-btn');
        if (btn) {
            const orderId = btn.getAttribute('data-order-id');
            const order = recentOrdersData[orderId];
            if (order) {
                viewOrder(order);
                const modal = new bootstrap.Modal(document.getElementById('dashboardOrderModal'));
                modal.show();
            }
        }
    });

    function viewOrder(order) {
        document.getElementById('modalOrderId').textContent = '#' + (order.id || 'N/A');
        document.getElementById('customerName').textContent = order.customer_name || 'Not Available';
        document.getElementById('customerEmail').textContent = order.email || 'Not Available';
        document.getElementById('customerPhone').textContent = order.phone || 'Not Available';
        document.getElementById('deliveryAddress').textContent = order.address || 'Not Available';
        document.getElementById('driverName').textContent = order.driver_name || 'Not Assigned';
        document.getElementById('driverPhone').textContent = order.driver_phone || 'Not Available';
        
        if (order.completed_at) {
            try {
                let dateString = order.completed_at;
                if (typeof dateString === 'object' && dateString.date) dateString = dateString.date;
                const date = new Date(dateString);
                document.getElementById('completedAt').textContent = !isNaN(date.getTime()) ? date.toLocaleString() : dateString;
            } catch (e) { document.getElementById('completedAt').textContent = order.completed_at; }
        } else { document.getElementById('completedAt').textContent = 'Not Available'; }

        const paymentMethod = order.payment_method || order.paymentMethod || 'N/A';
        document.getElementById('paymentBadge').innerHTML = '<span class="badge bg-success">' + paymentMethod.toUpperCase() + '</span>';
        document.getElementById('orderTotal').textContent = '₱' + (parseFloat(order.total) || 0).toFixed(2);

        displayOrderItems(order);
        displayDeliveryImages(order);
    }

    function displayOrderItems(order) {
        const tbody = document.getElementById('orderItemsTable');
        tbody.innerHTML = '';
        const items = order.items || order.orderItems || [];
        if (!items || items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">No items found</td></tr>';
            return;
        }
        items.forEach(item => {
            const quantity = parseFloat(item.quantity) || 1;
            let price = parseFloat(item.price) || 0;
            if (price === 0 && item.product && item.product.price) price = parseFloat(item.product.price);
            let productName = 'Unknown Product';
            if (item.product) {
                productName = (typeof item.product === 'string') ? item.product : (item.product.name || 'Unknown');
            } else if (item.product_name) productName = item.product_name;
            else if (item.name) productName = item.name;
            
            const variant = item.variant || item.variant_name || '';
            const displayName = variant ? htmlEscape(productName) + ' (' + htmlEscape(variant) + ')' : htmlEscape(productName);
            
            tbody.innerHTML += `<tr><td>${displayName}</td><td>${quantity}</td><td>₱${price.toFixed(2)}</td><td>₱${(quantity * price).toFixed(2)}</td></tr>`;
        });
    }

    function displayDeliveryImages(order) {
        const gallery = document.getElementById('imageGallery');
        gallery.innerHTML = '';
        let images = order.delivery_images || order.deliveryImages || order.proofImages || [];
        if (!images || images.length === 0) {
            if (order.orderVerificationUrl1) images.push(order.orderVerificationUrl1);
            if (order.orderVerificationUrl2) images.push(order.orderVerificationUrl2);
        }
        let imageArray = [];
        if (typeof images === 'string') imageArray.push({ image_path: images, type: 'Proof' });
        else if (Array.isArray(images)) {
            images.forEach(img => imageArray.push(typeof img === 'string' ? { image_path: img, type: 'Proof' } : img));
        }
        if (imageArray.length === 0) {
            gallery.innerHTML = '<div class="alert alert-info col-12 mb-0">No delivery proof images available.</div>';
            return;
        }
        imageArray.forEach((img, index) => {
            const imgUrl = img.image_path || img.imagePath || img.url || '';
            const imgType = img.type || 'Proof ' + (index + 1);
            if (imgUrl) {
                gallery.innerHTML += `<div class="col-sm-6 col-md-4"><div class="gallery-item" onclick="openImageModal('${escapeQuotes(imgUrl)}')"><img src="${escapeQuotes(imgUrl)}" onerror="this.src='/uploads/placeholder.jpg'"><div class="gallery-label">${htmlEscape(imgType)}</div></div></div>`;
            }
        });
    }

    function openImageModal(src) {
        document.getElementById('enlargedImage').src = src;
        new bootstrap.Modal(document.getElementById('dashboardImageModal')).show();
    }

    function htmlEscape(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    function escapeQuotes(text) { return text.replace(/"/g, '&quot;').replace(/'/g, '&#39;'); }
</script>

<?php require_once 'includes/footer.php'; ?>
