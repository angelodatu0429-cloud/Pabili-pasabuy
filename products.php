<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

// Ensure user is logged in
requireLogin();

$pageTitle = 'Products Management';
$message = '';
$error = '';

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'add') {
                // Add New Product
                $name = sanitize($_POST['name']);
                $type = sanitize($_POST['type']);
                $isActive = isset($_POST['is_active']) ? true : false;
                
                $variants = [];
                if (isset($_POST['variant_weight'])) {
                    for ($i = 0; $i < count($_POST['variant_weight']); $i++) {
                        $variants[] = [
                            'weight' => sanitize($_POST['variant_weight'][$i]),
                            'brand' => sanitize($_POST['variant_brand'][$i]),
                            'price' => (float) $_POST['variant_price'][$i],
                            'stock' => (int) $_POST['variant_stock'][$i]
                        ];
                    }
                }
                
                $newProduct = [
                    'name' => $name,
                    'type' => $type,
                    'variants' => $variants,
                    'is_active' => $isActive,
                    'created_at' => new DateTime(),
                    'updated_at' => new DateTime()
                ];

                // Firestore Insert (Collection 'Products' with capital P)
                $pdo->insert('Products', $newProduct);
                $message = 'Product added successfully!';

            } elseif ($_POST['action'] === 'edit') {
                // Update Product
                $id = $_POST['id'];
                $name = sanitize($_POST['name']);
                $type = sanitize($_POST['type']);
                $isActive = isset($_POST['is_active']) ? true : false;

                $variants = [];
                if (isset($_POST['variant_weight'])) {
                    for ($i = 0; $i < count($_POST['variant_weight']); $i++) {
                        $variants[] = [
                            'weight' => sanitize($_POST['variant_weight'][$i]),
                            'brand' => sanitize($_POST['variant_brand'][$i]),
                            'price' => (float) $_POST['variant_price'][$i],
                            'stock' => (int) $_POST['variant_stock'][$i]
                        ];
                    }
                }

                $updateData = [
                    'name' => $name,
                    'type' => $type,
                    'variants' => $variants,
                    'is_active' => $isActive,
                    'updated_at' => new DateTime()
                ];

                // Firestore Update
                $pdo->update('Products', $id, $updateData);
                $message = 'Product updated successfully!';

            } elseif ($_POST['action'] === 'delete') {
                // Delete Product
                $id = $_POST['id'];
                // Firestore Delete
                $pdo->delete('Products', $id);
                $message = 'Product deleted successfully!';
            }
        }
    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

// Fetch All Products from Firestore
try {
    $products = $pdo->getAllDocuments('Products');
} catch (Exception $e) {
    $error = 'Failed to fetch products: ' . $e->getMessage();
    $products = [];
}

// Filter Logic (Client-side simulation in PHP)
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$typeFilter = isset($_GET['type']) ? $_GET['type'] : '';

// Add price and stock from first variant for display purposes
foreach ($products as &$p) {
    if (!isset($p['price']) && !empty($p['variants'])) {
        $p['price'] = $p['variants'][0]['price'] ?? 0;
    }
    if (!isset($p['stock']) && !empty($p['variants'])) {
        $p['stock'] = $p['variants'][0]['stock'] ?? 0;
    }
}
unset($p);

if ($search || $typeFilter) {
    $products = array_filter($products, function($p) use ($search, $typeFilter) {
        $matchesSearch = true;
        $matchesType = true;

        if ($search) {
            $term = strtolower($search);
            $name = strtolower($p['name'] ?? '');
            // Search in variants too
            $variantsStr = json_encode($p['variants'] ?? []);
            if (strpos($name, $term) === false && strpos($variantsStr, $term) === false) {
                $matchesSearch = false;
            }
        }

        if ($typeFilter) {
            if (($p['type'] ?? $p['category'] ?? '') !== $typeFilter) {
                $matchesType = false;
            }
        }

        return $matchesSearch && $matchesType;
    });
}

// Defined Types
$productTypes = ['Meat', 'Vegetable', 'Frozen', 'Fruits', 'SeaFoods'];

require_once 'includes/sidebar.php';
?>

<!-- HTML Content (Bootstrap 5) -->
<div class="container-fluid p-4">
    <!-- Header & Actions -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Products</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
            <i class="bi bi-plus-lg"></i> Add Product
        </button>
    </div>

    <!-- Alerts -->
    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        <?php foreach ($productTypes as $t): ?>
                            <option value="<?php echo htmlspecialchars($t); ?>" <?php echo $typeFilter === $t ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($t); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary w-100">Filter</button>
                </div>
                <?php if ($search || $typeFilter): ?>
                <div class="col-md-2">
                    <a href="products.php" class="btn btn-outline-secondary w-100">Reset</a>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Products Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr><td colspan="7" class="text-center py-4">No products found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                            <i class="bi bi-box2 text-muted"></i>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?php echo htmlspecialchars($product['name'] ?? 'Unnamed'); ?></div>
                                        <small class="text-muted"><?php echo count($product['variants'] ?? []) . ' option(s)'; ?></small>
                                    </td>
                                    <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($product['type'] ?? 'Uncategorized'); ?></span></td>
                                    <td>₱<?php echo number_format($product['price'] ?? 0, 2); ?></td>
                                    <td>
                                        <?php 
                                            $stock = $product['stock'] ?? 0;
                                            $badgeClass = $stock > 10 ? 'bg-success' : ($stock > 0 ? 'bg-warning' : 'bg-danger');
                                        ?>
                                        <span class="badge <?php echo $badgeClass; ?>"><?php echo $stock; ?></span>
                                    </td>
                                    <td>
                                        <?php if (!empty($product['is_active'])): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary me-1 edit-btn" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editProductModal"
                                                data-id="<?php echo $product['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($product['name'] ?? ''); ?>"
                                                data-type="<?php echo htmlspecialchars($product['type'] ?? ''); ?>"
                                                data-variants="<?php echo htmlspecialchars(json_encode($product['variants'] ?? [])); ?>"
                                                data-active="<?php echo !empty($product['is_active']) ? 1 : 0; ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger delete-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteProductModal"
                                                data-id="<?php echo $product['id']; ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select" required>
                            <option value="">Select Type</option>
                            <?php foreach ($productTypes as $t): ?>
                                <option value="<?php echo htmlspecialchars($t); ?>"><?php echo htmlspecialchars($t); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label d-flex justify-content-between">
                            <span>Options (Weight, Brand, Price)</span>
                            <button type="button" class="btn btn-sm btn-success" onclick="addVariantRow('addVariantsContainer')"><i class="bi bi-plus"></i> Add Option</button>
                        </label>
                        <div id="addVariantsContainer" class="border p-2 rounded bg-light">
                            <!-- Dynamic Rows -->
                        </div>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" checked>
                        <label class="form-check-label">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Product</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select name="type" id="edit_type" class="form-select" required>
                            <option value="">Select Type</option>
                            <?php foreach ($productTypes as $t): ?>
                                <option value="<?php echo htmlspecialchars($t); ?>"><?php echo htmlspecialchars($t); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label d-flex justify-content-between">
                            <span>Options (Weight, Brand, Price)</span>
                            <button type="button" class="btn btn-sm btn-success" onclick="addVariantRow('editVariantsContainer')"><i class="bi bi-plus"></i> Add Option</button>
                        </label>
                        <div id="editVariantsContainer" class="border p-2 rounded bg-light">
                            <!-- Dynamic Rows -->
                        </div>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" id="edit_active">
                        <label class="form-check-label">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Product</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Delete Product Modal -->
<div class="modal fade" id="deleteProductModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="delete_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this product? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
function addVariantRow(containerId, data = {}) {
    const container = document.getElementById(containerId);
    const html = `
    <div class="row g-2 mb-2 variant-row align-items-center">
        <div class="col-md-3">
            <input type="text" name="variant_weight[]" class="form-control form-control-sm" placeholder="Weight (e.g. 1kg)" value="${data.weight || ''}" required>
        </div>
        <div class="col-md-3">
            <input type="text" name="variant_brand[]" class="form-control form-control-sm" placeholder="Brand (Optional)" value="${data.brand || ''}">
        </div>
        <div class="col-md-3">
            <div class="input-group input-group-sm">
                <span class="input-group-text">₱</span>
                <input type="number" name="variant_price[]" class="form-control" placeholder="Price" step="0.01" value="${data.price || ''}" required>
            </div>
        </div>
        <div class="col-md-2">
            <input type="number" name="variant_stock[]" class="form-control form-control-sm" placeholder="Stock" value="${data.stock || ''}" required>
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="this.closest('.variant-row').remove()"><i class="bi bi-trash"></i></button>
        </div>
    </div>`;
    container.insertAdjacentHTML('beforeend', html);
}

// Handle Edit Modal Data Population
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Add Modal with one row
    addVariantRow('addVariantsContainer');

    var editModal = document.getElementById('editProductModal');
    editModal.addEventListener('show.bs.modal', function(event) {
        var button = event.relatedTarget;
        
        document.getElementById('edit_id').value = button.getAttribute('data-id');
        document.getElementById('edit_name').value = button.getAttribute('data-name');
        document.getElementById('edit_type').value = button.getAttribute('data-type');
        document.getElementById('edit_active').checked = button.getAttribute('data-active') == '1';

        // Populate Variants
        const container = document.getElementById('editVariantsContainer');
        container.innerHTML = '';
        const variants = JSON.parse(button.getAttribute('data-variants') || '[]');
        
        variants.forEach(v => {
            addVariantRow('editVariantsContainer', v);
        });
        
        if (variants.length === 0) {
            addVariantRow('editVariantsContainer');
        }
    });

    var deleteModal = document.getElementById('deleteProductModal');
    deleteModal.addEventListener('show.bs.modal', function(event) {
        var button = event.relatedTarget;
        document.getElementById('delete_id').value = button.getAttribute('data-id');
    });
});
</script>