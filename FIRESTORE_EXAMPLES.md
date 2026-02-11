# Firestore Implementation Examples

This document shows real code examples from your updated pages.

## Example 1: Fetching and Filtering Users (users.php)

```php
<?php
// Fetch all users from Firestore
$allUsers = $pdo->getAllDocuments('users');
$users = [];

// Filter out admins and apply role filter if specified
$filterRole = sanitize($_GET['role'] ?? '');
foreach ($allUsers as $user) {
    if (isset($user['role']) && $user['role'] !== 'admin') {
        if (empty($filterRole) || $user['role'] === $filterRole) {
            $users[] = $user;
        }
    }
}

// Sort by role and created_at
usort($users, function($a, $b) {
    if ($a['role'] !== $b['role']) {
        return strcmp($a['role'], $b['role']);
    }
    $aTime = $a['created_at'] instanceof DateTime ? $a['created_at']->getTimestamp() : strtotime($a['created_at'] ?? '0');
    $bTime = $b['created_at'] instanceof DateTime ? $b['created_at']->getTimestamp() : strtotime($b['created_at'] ?? '0');
    return $bTime - $aTime;
});

// Count by role for statistics
$roleCounts = [];
foreach ($allUsers as $user) {
    if (isset($user['role']) && $user['role'] !== 'admin') {
        $role = $user['role'];
        $roleCounts[$role] = ($roleCounts[$role] ?? 0) + 1;
    }
}
?>
```

**Key Techniques:**
- Load all documents: `getAllDocuments()`
- Filter in PHP with conditions
- Sort with `usort()` and callbacks
- Count with associative arrays

## Example 2: Banning/Unbanning Users

```php
<?php
// Handle POST request to ban/unban
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'ban' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $userId = sanitize($_POST['user_id'] ?? '');
        try {
            // Update document status
            $pdo->update('users', $userId, ['status' => 'banned']);
            $message = 'User banned successfully.';
            $messageType = 'success';
        } catch (Exception $e) {
            error_log('Error banning user: ' . $e->getMessage());
            $message = 'Error banning user.';
            $messageType = 'danger';
        }
    }
}
?>
```

**Key Techniques:**
- Get document ID: `$userId` is the document ID
- Use `update()` to modify: `$pdo->update(collection, docId, fieldsToUpdate)`
- Always wrap in try-catch for error handling
- Use `error_log()` for debugging

## Example 3: Fetching Orders with Related User Data (completed-orders.php)

```php
<?php
try {
    // Get all orders and users
    $allOrders = $pdo->getAllDocuments('orders');
    $allUsers = $pdo->getAllDocuments('users');
    
    // Create a lookup map for quick user lookup
    $userMap = [];
    foreach ($allUsers as $user) {
        $userMap[$user['id']] = $user;
    }
    
    // Attach user data to orders
    $orders = [];
    foreach ($allOrders as $order) {
        if (isset($order['status']) && $order['status'] === 'completed') {
            // Attach customer info
            if (isset($order['user_id'])) {
                $customer = $userMap[$order['user_id']] ?? null;
                if ($customer) {
                    $order['customer_name'] = $customer['username'] ?? 'Unknown';
                    $order['email'] = $customer['email'] ?? '';
                    $order['phone'] = $customer['phone'] ?? '';
                }
            }
            
            // Attach driver info
            if (isset($order['driver_id'])) {
                $driver = $userMap[$order['driver_id']] ?? null;
                if ($driver) {
                    $order['driver_name'] = $driver['username'] ?? 'Unknown';
                    $order['driver_phone'] = $driver['phone'] ?? '';
                }
            }
            
            $orders[] = $order;
        }
    }
    
    // Sort by date descending
    usort($orders, function($a, $b) {
        $aTime = $a['completed_at'] instanceof DateTime 
            ? $a['completed_at']->getTimestamp() 
            : strtotime($a['completed_at'] ?? '0');
        $bTime = $b['completed_at'] instanceof DateTime 
            ? $b['completed_at']->getTimestamp() 
            : strtotime($b['completed_at'] ?? '0');
        return $bTime - $aTime;
    });
    
    // Limit results
    $orders = array_slice($orders, 0, 50);
    
} catch (Exception $e) {
    error_log('Error fetching completed orders: ' . $e->getMessage());
    $orders = [];
}
?>
```

**Key Techniques:**
- **Manual Join**: Create a lookup map instead of SQL JOIN
- Filter by status: Check `$order['status'] === 'completed'`
- Null checking: Use `??` operator for defaults
- DateTime handling: Check `instanceof DateTime`
- Error handling: Wrap in try-catch, return empty array on failure

## Example 4: Searching and Filtering Products (products.php)

```php
<?php
try {
    // Get all products from Firestore
    $allProducts = $pdo->getAllDocuments('Products');
    $products = [];
    
    // Get search and filter parameters
    $search = sanitize($_GET['search'] ?? '');
    $categoryFilter = sanitize($_GET['category'] ?? '');
    
    // Filter and search in PHP
    foreach ($allProducts as $product) {
        $matchesSearch = true;
        $matchesCategory = true;
        
        // Search filter - check name and description
        if (!empty($search)) {
            $searchLower = strtolower($search);
            $nameLower = strtolower($product['name'] ?? '');
            $matchesSearch = strpos($nameLower, $searchLower) !== false;
        }
        
        // Category filter - exact match
        if (!empty($categoryFilter)) {
            $matchesCategory = ($product['category'] ?? '') === $categoryFilter;
        }
        
        // Include if matches all filters
        if ($matchesSearch && $matchesCategory) {
            $products[] = $product;
        }
    }
    
    // Sort by name alphabetically
    usort($products, function($a, $b) {
        return strcmp($a['name'] ?? '', $b['name'] ?? '');
    });
    
} catch (Exception $e) {
    error_log('Error fetching products: ' . $e->getMessage());
    $products = [];
}
?>
```

**Key Techniques:**
- Case-insensitive search: Convert to lowercase with `strtolower()`
- Substring search: Use `strpos()` to find text within string
- Multiple filter conditions: Use boolean flags and combine with &&
- Safe property access: Use `??` for default values

## Example 5: Verifications with User Data (verifications.php)

```php
<?php
try {
    // Fetch all verifications and users
    $allFirestoreVerifications = $pdo->getAllDocuments('verifications');
    $allUsers = $pdo->getAllDocuments('users');
    
    // Create user lookup map
    $userMap = [];
    foreach ($allUsers as $user) {
        $userMap[$user['id']] = $user;
    }
    
    // Get role filter
    $filterRole = sanitize($_GET['role'] ?? '');
    
    // Attach user info to verifications
    $verifications = [];
    foreach ($allFirestoreVerifications as $verification) {
        // Attach user details
        if (isset($verification['user_id']) && isset($userMap[$verification['user_id']])) {
            $user = $userMap[$verification['user_id']];
            $verification['username'] = $user['username'] ?? 'Unknown';
            $verification['email'] = $user['email'] ?? '';
            $verification['phone'] = $user['phone'] ?? '';
            $verification['role'] = $user['role'] ?? '';
        }
        
        // Apply role filter if specified
        if (empty($filterRole) || $verification['role'] === $filterRole) {
            $verifications[] = $verification;
        }
    }
    
    // Sort: pending first, then by date descending
    usort($verifications, function($a, $b) {
        // Pending status first
        $aPending = ($a['status'] ?? '') === 'pending' ? 0 : 1;
        $bPending = ($b['status'] ?? '') === 'pending' ? 0 : 1;
        if ($aPending !== $bPending) {
            return $aPending - $bPending;
        }
        
        // Then by submitted_at descending
        $aTime = $a['submitted_at'] instanceof DateTime 
            ? $a['submitted_at']->getTimestamp() 
            : strtotime($a['submitted_at'] ?? '0');
        $bTime = $b['submitted_at'] instanceof DateTime 
            ? $b['submitted_at']->getTimestamp() 
            : strtotime($b['submitted_at'] ?? '0');
        return $bTime - $aTime;
    });
    
} catch (Exception $e) {
    error_log('Error fetching verifications: ' . $e->getMessage());
    $verifications = [];
}
?>
```

**Key Techniques:**
- Complex sorting: Multiple sort criteria with priority
- Status grouping: Sort by status first, then by date
- Conditional field attachment: Only add fields if they exist
- Safe lookups: Check if key exists in map before accessing

## Example 6: Dashboard with Statistics (index.php)

```php
<?php
// Fetch recent completed orders with user data
try {
    // Get all orders and create user map
    $allOrders = $pdo->getAllDocuments('orders');
    $allUsers = $pdo->getAllDocuments('users');
    
    $userMap = [];
    foreach ($allUsers as $user) {
        $userMap[$user['id']] = $user;
    }
    
    // Get completed orders only
    $recentOrders = [];
    foreach ($allOrders as $order) {
        if (isset($order['status']) && $order['status'] === 'completed') {
            // Attach user names
            if (isset($order['user_id']) && isset($userMap[$order['user_id']])) {
                $order['customer_name'] = $userMap[$order['user_id']]['username'] ?? 'Unknown';
            }
            if (isset($order['driver_id']) && isset($userMap[$order['driver_id']])) {
                $order['driver_name'] = $userMap[$order['driver_id']]['username'] ?? 'Unknown';
            }
            $recentOrders[] = $order;
        }
    }
    
    // Sort and limit
    usort($recentOrders, function($a, $b) {
        $aTime = $a['completed_at'] instanceof DateTime 
            ? $a['completed_at']->getTimestamp() 
            : strtotime($a['completed_at'] ?? '0');
        $bTime = $b['completed_at'] instanceof DateTime 
            ? $b['completed_at']->getTimestamp() 
            : strtotime($b['completed_at'] ?? '0');
        return $bTime - $aTime;
    });
    
    $recentOrders = array_slice($recentOrders, 0, 8);
    
} catch (Exception $e) {
    error_log('Error fetching recent orders: ' . $e->getMessage());
    $recentOrders = [];
}

// Statistics are already calculated by helper functions in functions.php
$totalCustomers = getTotalCustomersCount($pdo);
$totalRiders = getTotalRidersCount($pdo);
$todayRevenue = getTodayRevenue($pdo);
?>
```

**Key Techniques:**
- Reuse helper functions for statistics
- Manual join for getting order details with user info
- Limit results with `array_slice()`
- Safe DateTime conversion

## Common Patterns Summary

| Operation | Code |
|-----------|------|
| Get all documents | `$pdo->getAllDocuments('collection')` |
| Get one document | `$pdo->getDocument('collection', 'docId')` |
| Create document | `$pdo->insert('collection', ['field' => 'value'])` |
| Update document | `$pdo->update('collection', 'docId', ['field' => 'newValue'])` |
| Delete document | `$pdo->delete('collection', 'docId')` |
| Filter array | `array_filter($array, function($item) { return condition; })` |
| Sort array | `usort($array, function($a, $b) { return comparison; })` |
| Map for join | `$map[$doc['id']] = $doc;` |
| Slice results | `array_slice($array, $offset, $limit)` |
| Count items | `count($array)` |
| Safe access | `$array['key'] ?? 'default'` |
| Null check | `isset($array['key'])` |
| Type check | `$value instanceof DateTime` |

## Next Steps

1. Test each page in your browser:
   - `/users.php` - User management
   - `/products.php` - Product management
   - `/completed-orders.php` - Order history
   - `/verifications.php` - ID verifications
   - `/index.php` - Dashboard

2. Check browser console and server logs for errors

3. Verify data appears correctly from Firestore

4. Create new pages following these patterns
