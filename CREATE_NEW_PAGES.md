# Creating New Pages with Firestore

This guide shows you how to create new admin pages that work with Firestore data.

## Basic Template for a New Page

```php
<?php
/**
 * Page Title
 * Brief description of what this page does
 */

session_start();

// Load dependencies
require_once 'includes/db.php';        // Firestore connection as $pdo
require_once 'includes/functions.php'; // Helper functions
require_once 'includes/header.php';    // Authentication & layout

// Check user is logged in and is admin
requireLogin();

$pageTitle = 'Page Title Here';
$message = '';
$messageType = '';

// ============================================================
// HANDLE FORM SUBMISSIONS (POST)
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        // Handle add/create operation
        try {
            $newData = [
                'field1' => sanitize($_POST['field1'] ?? ''),
                'field2' => (float)($_POST['field2'] ?? 0),
                'created_at' => new DateTime(),
            ];
            
            $pdo->insert('collection_name', $newData);
            $message = 'Record added successfully!';
            $messageType = 'success';
        } catch (Exception $e) {
            error_log('Error adding record: ' . $e->getMessage());
            $message = 'Error adding record.';
            $messageType = 'danger';
        }
    } elseif ($action === 'edit' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        // Handle edit/update operation
        try {
            $recordId = sanitize($_POST['record_id'] ?? '');
            $updateData = [
                'field1' => sanitize($_POST['field1'] ?? ''),
                'field2' => (float)($_POST['field2'] ?? 0),
                'updated_at' => new DateTime(),
            ];
            
            $pdo->update('collection_name', $recordId, $updateData);
            $message = 'Record updated successfully!';
            $messageType = 'success';
        } catch (Exception $e) {
            error_log('Error updating record: ' . $e->getMessage());
            $message = 'Error updating record.';
            $messageType = 'danger';
        }
    } elseif ($action === 'delete' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        // Handle delete operation
        try {
            $recordId = sanitize($_POST['record_id'] ?? '');
            $pdo->delete('collection_name', $recordId);
            $message = 'Record deleted successfully!';
            $messageType = 'success';
        } catch (Exception $e) {
            error_log('Error deleting record: ' . $e->getMessage());
            $message = 'Error deleting record.';
            $messageType = 'danger';
        }
    }
}

// ============================================================
// FETCH AND PROCESS DATA (GET)
// ============================================================

try {
    // Get filter parameters from GET
    $filterParam = sanitize($_GET['filter'] ?? '');
    $searchParam = sanitize($_GET['search'] ?? '');
    
    // Fetch all documents from Firestore
    $allRecords = $pdo->getAllDocuments('collection_name');
    $records = [];
    
    // Filter and search
    foreach ($allRecords as $record) {
        // Apply search filter
        if (!empty($searchParam)) {
            $searchLower = strtolower($searchParam);
            $nameLower = strtolower($record['name'] ?? '');
            if (strpos($nameLower, $searchLower) === false) {
                continue; // Skip if doesn't match search
            }
        }
        
        // Apply other filters
        if (!empty($filterParam)) {
            if (($record['status'] ?? '') !== $filterParam) {
                continue; // Skip if doesn't match filter
            }
        }
        
        $records[] = $record;
    }
    
    // Sort records
    usort($records, function($a, $b) {
        // Example: Sort by created_at descending
        $aTime = $a['created_at'] instanceof DateTime 
            ? $a['created_at']->getTimestamp() 
            : strtotime($a['created_at'] ?? '0');
        $bTime = $b['created_at'] instanceof DateTime 
            ? $b['created_at']->getTimestamp() 
            : strtotime($b['created_at'] ?? '0');
        return $bTime - $aTime;
    });
    
} catch (Exception $e) {
    error_log('Error fetching records: ' . $e->getMessage());
    $records = [];
}

// Generate CSRF token for forms
$csrf_token = generateCSRFToken();

// Include sidebar navigation
require_once 'includes/sidebar.php';
?>

<!-- PAGE HEADER -->
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-icon-name"></i> Page Title</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item active">Page Title</li>
                </ol>
            </nav>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-circle"></i> Add New
        </button>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="main-container">
    
    <!-- SUCCESS/ERROR MESSAGES -->
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- FILTERS & SEARCH -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <input 
                        type="text" 
                        name="search" 
                        class="form-control" 
                        placeholder="Search..."
                        value="<?php echo htmlspecialchars($searchParam); ?>"
                    >
                </div>
                <div class="col-md-6">
                    <select name="filter" class="form-select">
                        <option value="">All</option>
                        <option value="active" <?php echo $filterParam === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $filterParam === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- DATA TABLE -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">
                <i class="bi bi-list"></i> Records
                <span class="badge bg-primary ms-2"><?php echo count($records); ?></span>
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (!empty($records)): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Column 1</th>
                                <th>Column 2</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $record): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($record['name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($record['description'] ?? ''); ?></td>
                                    <td>
                                        <span class="badge bg-success">
                                            <?php echo htmlspecialchars($record['status'] ?? ''); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                            echo formatDate($record['created_at'] ?? '');
                                        ?>
                                    </td>
                                    <td>
                                        <!-- Edit Button -->
                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal" 
                                            onclick="loadEditForm('<?php echo htmlspecialchars($record['id']); ?>', '<?php echo htmlspecialchars($record['name'] ?? ''); ?>')">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        
                                        <!-- Delete Button -->
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="record_id" value="<?php echo htmlspecialchars($record['id']); ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="p-4 text-center text-muted">
                    <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                    <p class="mt-2">No records found</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ADD MODAL -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label for="field1" class="form-label">Field 1</label>
                        <input type="text" class="form-control" id="field1" name="field1" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="field2" class="form-label">Field 2</label>
                        <input type="number" class="form-control" id="field2" name="field2" step="0.01" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" id="record_id" name="record_id" value="">
                    
                    <div class="mb-3">
                        <label for="edit_field1" class="form-label">Field 1</label>
                        <input type="text" class="form-control" id="edit_field1" name="field1" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_field2" class="form-label">Field 2</label>
                        <input type="number" class="form-control" id="edit_field2" name="field2" step="0.01" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function loadEditForm(recordId, recordName) {
    document.getElementById('record_id').value = recordId;
    document.getElementById('edit_field1').value = recordName;
    // Load other fields here
}
</script>

<?php require_once 'includes/footer.php'; ?>
```

## Pattern for Working with Related Data (Joins)

```php
<?php
// Example: Showing orders with customer information

try {
    // Fetch both collections
    $allOrders = $pdo->getAllDocuments('orders');
    $allUsers = $pdo->getAllDocuments('users');
    
    // Create lookup map for users
    $userMap = [];
    foreach ($allUsers as $user) {
        $userMap[$user['id']] = $user;
    }
    
    // Attach user data to orders
    $orders = [];
    foreach ($allOrders as $order) {
        // Attach customer info
        if (isset($order['user_id']) && isset($userMap[$order['user_id']])) {
            $order['customer_name'] = $userMap[$order['user_id']]['username'];
            $order['customer_email'] = $userMap[$order['user_id']]['email'];
        } else {
            $order['customer_name'] = 'Unknown';
            $order['customer_email'] = '';
        }
        
        $orders[] = $order;
    }
    
} catch (Exception $e) {
    error_log('Error: ' . $e->getMessage());
    $orders = [];
}
?>
```

## Pattern for Statistics

```php
<?php
// Count records by status
try {
    $allRecords = $pdo->getAllDocuments('collection_name');
    
    $statuses = [];
    foreach ($allRecords as $record) {
        $status = $record['status'] ?? 'unknown';
        $statuses[$status] = ($statuses[$status] ?? 0) + 1;
    }
    
} catch (Exception $e) {
    error_log('Error: ' . $e->getMessage());
    $statuses = [];
}

// In HTML:
<?php foreach ($statuses as $status => $count): ?>
    <div class="badge bg-primary">
        <?php echo htmlspecialchars($status); ?>: <?php echo $count; ?>
    </div>
<?php endforeach; ?>
```

## Key Rules

1. **Always require authentication**:
   ```php
   requireLogin();  // Must be after header.php
   ```

2. **Always sanitize user input**:
   ```php
   $name = sanitize($_POST['name'] ?? '');
   ```

3. **Always verify CSRF tokens**:
   ```php
   if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
       // Process form
   }
   ```

4. **Always use try-catch for Firestore**:
   ```php
   try {
       $pdo->update(...);
   } catch (Exception $e) {
       error_log('Error: ' . $e->getMessage());
   }
   ```

5. **Always handle DateTime properly**:
   ```php
   if ($record['date'] instanceof DateTime) {
       echo $record['date']->format('Y-m-d');
   } else {
       echo $record['date']; // String
   }
   ```

6. **Always check if fields exist**:
   ```php
   echo $record['field'] ?? 'default_value';
   ```

7. **Always include footer**:
   ```php
   <?php require_once 'includes/footer.php'; ?>
   ```

## Testing Your New Page

1. Create the PHP file in admin folder
2. Access it: `http://localhost/admin/yourpage.php`
3. Check it loads (if not, check error logs)
4. Test form submission (add, edit, delete)
5. Verify data appears in Firestore
6. Check all messages display correctly

## Common Mistakes to Avoid

❌ **Wrong**: Using `$_POST` values directly in HTML
```php
echo $_POST['name']; // Can cause XSS
```

✓ **Right**: Sanitize first
```php
echo htmlspecialchars(sanitize($_POST['name']));
```

---

❌ **Wrong**: Accessing array fields without checking
```php
echo $record['field']; // Might be undefined
```

✓ **Right**: Use null coalescing
```php
echo $record['field'] ?? 'N/A';
```

---

❌ **Wrong**: Forgetting CSRF tokens
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process without checking token
}
```

✓ **Right**: Always verify
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken(...)) {
    // Safe to process
}
```

---

❌ **Wrong**: Not handling DateTime
```php
$date = $record['created_at']; // Might be DateTime or string
```

✓ **Right**: Check type first
```php
if ($record['created_at'] instanceof DateTime) {
    $dateStr = $record['created_at']->format('Y-m-d');
} else {
    $dateStr = $record['created_at'];
}
```

## References

- Copy code from existing pages as templates
- See `FIRESTORE_EXAMPLES.md` for real patterns
- Check `FIRESTORE_QUICK_REFERENCE.md` for syntax
- Review `includes/functions.php` for helper functions
