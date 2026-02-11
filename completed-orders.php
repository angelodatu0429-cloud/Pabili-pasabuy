<?php
/**
 * Completed Orders Page
 * View completed orders with delivery proof images and order details
 */

session_start();

require_once 'includes/db.php';
require_once 'includes/functions.php';

// Check authentication
requireLogin();

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $orders = fetchCompletedOrders($pdo);
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="completed_orders_' . date('Y-m-d_H-i') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputs($output, "\xEF\xBB\xBF"); // BOM for Excel
    
    fputcsv($output, ['Order ID', 'Customer', 'Phone', 'Driver', 'Items', 'Total', 'Payment', 'Date', 'Address']);
    
    foreach ($orders as $order) {
        $date = '';
        if (isset($order['completed_at'])) {
            $date = ($order['completed_at'] instanceof DateTime) ? $order['completed_at']->format('Y-m-d H:i') : (string)$order['completed_at'];
        }
        
        fputcsv($output, [
            $order['id'] ?? '',
            $order['customer_name'] ?? '',
            $order['phone'] ?? '',
            $order['driver_name'] ?? '',
            count($order['items'] ?? []),
            $order['total'] ?? 0,
            $order['payment_method'] ?? '',
            $date,
            $order['address'] ?? ''
        ]);
    }
    fclose($output);
    exit;
}

require_once 'includes/header.php';

$pageTitle = 'Completed Orders';

// Initialize variables
$orders = [];
$debugInfo = []; // For debugging

// Fetch completed orders from Firestore
$orders = fetchCompletedOrders($pdo);

require_once 'includes/sidebar.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1><i class="bi bi-check-circle-fill"></i> Completed Orders</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active">Completed Orders</li>
        </ol>
    </nav>
</div>

<!-- Main Content -->
<div class="main-container">
    <!-- Info Alert -->
    <div class="alert alert-info mb-4" role="alert">
        <i class="bi bi-info-circle"></i> 
        <strong>Total Completed Orders: <?php echo count($orders); ?></strong>
        - Displaying all orders with "completed" status from your Orders collection
    </div>
    
    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon bg-success">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h6 class="stat-label">Total Completed</h6>
                    <p class="stat-value"><?php echo count($orders); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon bg-primary">
                    <i class="bi bi-currency-dollar"></i>
                </div>
                <div class="stat-content">
                    <h6 class="stat-label">Total Revenue</h6>
                    <p class="stat-value"><?php 
                        $totals = array_column($orders, 'total');
                        $total = array_sum($totals);
                        echo formatCurrency($total); 
                    ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon bg-info">
                    <i class="bi bi-graph-up"></i>
                </div>
                <div class="stat-content">
                    <h6 class="stat-label">Average Order</h6>
                    <p class="stat-value">
                        <?php 
                        $totals = array_column($orders, 'total');
                        $total = array_sum($totals);
                        $average = !empty($orders) ? $total / count($orders) : 0;
                        echo formatCurrency($average); 
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0">
                        <i class="bi bi-list"></i> Orders List
                    </h5>
                </div>
                <div class="col-md-6">
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="?export=csv" class="btn btn-sm btn-success text-nowrap">
                            <i class="bi bi-download"></i> Export CSV
                        </a>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" class="form-control border-start-0" id="orderSearch" placeholder="Search...">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if (!empty($orders)): ?>
                        <div class="table-responsive">
                    <table class="table table-hover mb-0" id="ordersTable">
                        <thead class="bg-light">
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Driver</th>
                                <th>Items</th>
                                <th>Address</th>
                                <th>Total</th>
                                <th>Payment</th>
                                <th>Completed</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="ordersBody">
                            <?php foreach ($orders as $order): ?>
                                <tr class="order-row" data-search="<?php echo strtolower(htmlspecialchars(($order['id'] ?? '') . ' ' . ($order['customer_name'] ?? '') . ' ' . ($order['phone'] ?? ''))); ?>">
                                    <td>
                                        <span class="badge bg-light text-dark fw-bold">#<?php echo htmlspecialchars($order['id'] ?? 'N/A'); ?></span>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($order['email'] ?? ''); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($order['driver_name'] ?? 'Not Assigned'); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($order['driver_phone'] ?? ''); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo count($order['items'] ?? []) ?? 0; ?> items
                                        </span>
                                    </td>
                                    <td>
                                        <small><?php 
                                            $address = $order['address'] ?? 'N/A';
                                            echo htmlspecialchars(strlen($address) > 40 ? substr($address, 0, 40) . '...' : $address); 
                                        ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo formatCurrency($order['total'] ?? 0); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">
                                            <?php echo htmlspecialchars(ucfirst($order['payment_method'] ?? 'N/A')); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?php echo isset($order['completed_at']) ? formatDate($order['completed_at']) : 'N/A'; ?></small>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary view-order-btn"
                                                data-order-id="<?php echo htmlspecialchars($order['id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                            <i class="bi bi-eye"></i> View
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-warning m-3 mb-0">
                    <i class="bi bi-exclamation-triangle"></i> No completed orders found in your database.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderModal" tabindex="-1" size="xl">
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
                                <tbody id="orderItemsTable">
                                </tbody>
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
                <div id="imageGallery" class="row g-3 mb-4">
                    <!-- Images will be loaded here -->
                </div>

                <!-- Full Data View -->
                <div class="accordion" id="accordionData">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingData">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseData">
                                <i class="bi bi-database me-2"></i> View Full Order Data
                            </button>
                        </h2>
                        <div id="collapseData" class="accordion-collapse collapse" data-bs-parent="#accordionData">
                            <div class="accordion-body p-0">
                                <div class="table-responsive" style="max-height: 400px;">
                                    <table class="table table-sm table-striped mb-0">
                                        <tbody id="allDataBody"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Image Enlargement Modal -->
<div class="modal fade" id="imageModal" tabindex="-1">
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
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1.5rem;
        border: 1px solid #e5e7eb;
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

    .stat-icon.bg-success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }

    .stat-icon.bg-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .stat-icon.bg-info {
        background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
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

    .gallery-item {
        position: relative;
        overflow: hidden;
        border-radius: 8px;
        background: #f0f0f0;
        cursor: pointer;
    }

    .gallery-item img {
        width: 100%;
        height: 150px;
        object-fit: cover;
        transition: all 0.3s ease;
    }

    .gallery-item:hover img {
        transform: scale(1.05);
        opacity: 0.8;
    }

    .gallery-label {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(0,0,0,0.7);
        color: white;
        padding: 0.5rem;
        font-size: 0.85rem;
        font-weight: 500;
    }

    /* Ensure view button is clickable */
    .view-order-btn {
        cursor: pointer !important;
        pointer-events: auto !important;
    }
</style>

<script>
    // Store all orders in JavaScript for easy access
    const ordersData = {};
    
    <?php foreach ($orders as $order): ?>
    ordersData[<?php echo json_encode((string)($order['id'] ?? '')); ?>] = <?php echo json_encode($order, JSON_UNESCAPED_UNICODE); ?>;
    <?php endforeach; ?>
    
    console.log('Orders loaded:', Object.keys(ordersData).length, 'orders');
    console.log('Orders data:', ordersData);
    
    // Setup event listeners after DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        // Search/Filter functionality
        const orderSearch = document.getElementById('orderSearch');
        if (orderSearch) {
            orderSearch.addEventListener('keyup', function(e) {
                const searchTerm = e.target.value.toLowerCase();
                const rows = document.querySelectorAll('.order-row');
                let visibleCount = 0;
                
                rows.forEach(row => {
                    const searchData = row.getAttribute('data-search');
                    if (searchData.includes(searchTerm)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });
                
                // Show "no results" message if needed
                const tbody = document.getElementById('ordersBody');
                let noResultsRow = document.getElementById('noResultsRow');
                
                if (visibleCount === 0 && searchTerm !== '') {
                    if (!noResultsRow) {
                        noResultsRow = document.createElement('tr');
                        noResultsRow.id = 'noResultsRow';
                        noResultsRow.innerHTML = '<td colspan="9" class="text-center text-muted py-3">No orders match your search</td>';
                        tbody.appendChild(noResultsRow);
                    }
                } else if (noResultsRow) {
                    noResultsRow.remove();
                }
            });
        }
    });

    function viewOrder(order) {
        if (!order) {
            alert('Invalid order data');
            return;
        }

        // Set order header
        document.getElementById('modalOrderId').textContent = '#' + (order.id || order.order_id || '');

        // Set customer info
        document.getElementById('customerName').textContent = order.customer_name || 'Not Available';
        document.getElementById('customerEmail').textContent = order.email || 'Not Available';
        document.getElementById('customerPhone').textContent = order.phone || 'Not Available';
        
        const addressText = order.address || 'Not Available';
        document.getElementById('deliveryAddress').textContent = addressText;

        // Set driver info
        document.getElementById('driverName').textContent = order.driver_name || 'Not Assigned';
        document.getElementById('driverPhone').textContent = order.driver_phone || 'Not Available';
        
        // Format completion date
        let completedText = 'Not Available';
        if (order.completed_at) {
            try {
                // Handle both string date and DateTime object
                let dateString = order.completed_at;
                if (typeof dateString === 'object' && dateString.date) {
                    dateString = dateString.date; // Firestore timestamp object
                }
                const date = new Date(dateString);
                if (!isNaN(date.getTime())) {
                    completedText = date.toLocaleString();
                } else {
                    completedText = String(dateString);
                }
            } catch (e) {
                completedText = String(order.completed_at);
            }
        }
        document.getElementById('completedAt').textContent = completedText;

        // Set payment badge
        const paymentBadge = document.getElementById('paymentBadge');
        const paymentMethod = order.payment_method || order.paymentMethod || 'N/A';
        paymentBadge.innerHTML = '<span class="badge bg-success">' + paymentMethod.toUpperCase() + '</span>';

        // Set total - ensure it's formatted as currency
        const total = parseFloat(order.total) || 0;
        document.getElementById('orderTotal').textContent = '₱' + total.toFixed(2);

        // Fetch and display order items
        displayOrderItems(order);

        // Fetch and display delivery images
        displayDeliveryImages(order);

        // Populate Full Data Table
        const allDataBody = document.getElementById('allDataBody');
        if (allDataBody) {
            allDataBody.innerHTML = '';
            Object.keys(order).sort().forEach(key => {
                let val = order[key];
                if (typeof val === 'object' && val !== null) {
                    val = '<pre class="mb-0" style="font-size:0.75rem">' + htmlEscape(JSON.stringify(val, null, 2)) + '</pre>';
                } else {
                    val = htmlEscape(String(val));
                }
                allDataBody.innerHTML += `<tr><th class="text-nowrap" style="width:1%; font-size:0.8rem">${htmlEscape(key)}</th><td style="font-size:0.85rem; word-break:break-all;">${val}</td></tr>`;
            });
        }
    }

    function displayOrderItems(order) {
        const tbody = document.getElementById('orderItemsTable');
        tbody.innerHTML = '';
        
        // Get items from order object
        const items = order.items || order.orderItems || [];
        
        if (!items || items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">No items found</td></tr>';
            return;
        }
        
        let subtotal = 0;
        items.forEach(item => {
            const quantity = parseFloat(item.quantity) || 1;
            
            // Get price from item or from nested product object
            let price = parseFloat(item.price) || 0;
            if (price === 0 && item.product && item.product.price) {
                price = parseFloat(item.product.price);
            }
            
            const itemSubtotal = quantity * price;
            subtotal += itemSubtotal;
            
            // Get product name - handle nested product object
            let productName = 'Unknown Product';
            if (item.product) {
                if (typeof item.product === 'string') {
                    productName = item.product;
                } else if (item.product.name) {
                    productName = item.product.name;
                }
            } else if (item.product_name) {
                productName = item.product_name;
            } else if (item.name) {
                productName = item.name;
            } else if (item.productName) {
                productName = item.productName;
            }
            
            // Add variant info if available
            const variant = item.variant || item.variant_name || '';
            const displayName = variant ? htmlEscape(productName) + ' (' + htmlEscape(variant) + ')' : htmlEscape(productName);
            
            const row = `
                <tr>
                    <td>${displayName}</td>
                    <td>${quantity}</td>
                    <td>₱${price.toFixed(2)}</td>
                    <td>₱${itemSubtotal.toFixed(2)}</td>
                </tr>
            `;
            tbody.innerHTML += row;
        });
    }

    function displayDeliveryImages(order) {
        const gallery = document.getElementById('imageGallery');
        gallery.innerHTML = '';

        // Get delivery images from various possible field names
        let images = order.delivery_images || order.deliveryImages || order.proofImages || order.images || order.order_verification || order.orderVerification || [];
        
        // If no images found, check for orderVerificationUrl1 and orderVerificationUrl2
        if (!images || images.length === 0) {
            images = [];
            if (order.orderVerificationUrl1) {
                images.push(order.orderVerificationUrl1);
            }
            if (order.orderVerificationUrl2) {
                images.push(order.orderVerificationUrl2);
            }
        }

        // Normalize images to array of objects {image_path: 'url', type: 'label'}
        let imageArray = [];

        if (typeof images === 'string') {
            imageArray.push({ image_path: images, type: 'Proof' });
        } else if (Array.isArray(images)) {
            images.forEach(img => {
                if (typeof img === 'string') {
                    imageArray.push({ image_path: img, type: 'Proof' });
                } else {
                    imageArray.push(img);
                }
            });
        } else if (typeof images === 'object' && images !== null) {
            // Check if it's a single image object with known keys
            if (images.image_path || images.imagePath || images.url || images.downloadUrl) {
                imageArray.push(images);
            } else {
                // Assume it's a map of key -> url (common in Firestore for multiple images)
                Object.keys(images).forEach(key => {
                    const val = images[key];
                    if (typeof val === 'string' && (val.startsWith('http') || val.startsWith('gs://'))) {
                        imageArray.push({ image_path: val, type: key });
                    }
                });
            }
        }

        if (imageArray.length === 0) {
            gallery.innerHTML = '<div class="alert alert-info col-12 mb-0">No delivery proof images available.</div>';
            return;
        }

        imageArray.forEach((img, index) => {
            // Handle different image object formats
            const imgUrl = img.image_path || img.imagePath || img.url || img.downloadUrl || '';
            const imgType = img.type || img.label || 'Proof ' + (index + 1);
            
            if (imgUrl) {
                const col = document.createElement('div');
                col.className = 'col-sm-6 col-md-4';
                col.innerHTML = `
                    <div class="gallery-item" onclick="openImageModal('${escapeQuotes(imgUrl)}')"> 
                        <img src="${escapeQuotes(imgUrl)}" 
                             alt="${escapeQuotes(imgType)}" 
                             onerror="this.src='/uploads/placeholder.jpg'"
                             style="width:100%; height:150px; object-fit:cover;">
                        <div class="gallery-label">${htmlEscape(imgType)}</div>
                    </div>
                `;
                gallery.appendChild(col);
            }
        });
    }

    function openImageModal(src) {
        const enlargedImage = document.getElementById('enlargedImage');
        enlargedImage.src = src;
        enlargedImage.onerror = function() {
            this.src = '/uploads/placeholder.jpg';
        };
        new bootstrap.Modal(document.getElementById('imageModal')).show();
    }

    // Helper functions for XSS prevention
    function htmlEscape(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function escapeQuotes(text) {
        return text.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
</script>

<?php require_once 'includes/footer.php'; ?>

<script>
    // Event listeners that use Bootstrap - must run AFTER Bootstrap is loaded in footer
    
    // Auto-open modal if view parameter is present
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const viewId = urlParams.get('view');
        
        if (viewId) {
            const btn = document.querySelector(`.view-order-btn[data-order-id="${viewId.replace(/"/g, '\\"')}"]`);
            if (btn) {
                btn.click();
            }
        }
    });

    // Handle view button click via event delegation (synced with dashboard approach)
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.view-order-btn');
        if (btn) {
            const orderId = btn.getAttribute('data-order-id');
            console.log('=== VIEW BUTTON CLICKED ===');
            console.log('Order ID from button:', orderId);
            console.log('Orders in memory:', Object.keys(ordersData));
            
            const order = ordersData[orderId];
            console.log('Order retrieved:', order);
            
            if (order) {
                console.log('Calling viewOrder function...');
                viewOrder(order);
                console.log('Showing modal...');
                const modal = new bootstrap.Modal(document.getElementById('orderModal'));
                modal.show();
                console.log('Modal shown');
            } else {
                console.error('Order NOT found. Available IDs:', Object.keys(ordersData));
            }
        }
    });
</script>

<?php
// Footer already included above
?>
