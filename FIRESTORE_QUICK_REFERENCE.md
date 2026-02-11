# Firestore Quick Reference

## Connection
```php
// Already connected in includes/db.php
// $pdo is a FirestoreAdapter instance
require_once 'includes/db.php';
```

## Common Operations

### Fetch All Documents
```php
$users = $pdo->getAllDocuments('users');
foreach ($users as $user) {
    echo $user['username'];  // Access properties directly
}
```

### Fetch Single Document
```php
$user = $pdo->getDocument('users', 'user123');
if ($user) {
    echo $user['email'];
}
```

### Create Document
```php
$pdo->insert('users', [
    'username' => 'john_doe',
    'email' => 'john@example.com',
    'role' => 'customer',
    'status' => 'active',
    'created_at' => new DateTime()
]);
$newId = $pdo->lastInsertId();
```

### Update Document
```php
$pdo->update('users', 'user123', [
    'status' => 'banned',
    'updated_at' => new DateTime()
]);
```

### Delete Document
```php
$pdo->delete('users', 'user123');
```

### Query with Filter
```php
// Get all active users
$activeUsers = $pdo->query('users', 'status', '==', 'active')->fetchAll();

// Get users banned
$bannedUsers = $pdo->query('users', 'status', '==', 'banned')->fetchAll();
```

## Working with Dates

```php
// Store date
$pdo->update('users', 'user123', [
    'updated_at' => new DateTime()
]);

// Retrieve and check date
$user = $pdo->getDocument('users', 'user123');
if ($user['updated_at'] instanceof DateTime) {
    echo $user['updated_at']->format('Y-m-d H:i:s');
}
```

## Working with Arrays

### Retrieve Array
```php
$user = $pdo->getDocument('users', 'user123');
if (is_array($user['tags'])) {
    foreach ($user['tags'] as $tag) {
        echo $tag;
    }
}
```

### Store Array
```php
$pdo->update('users', 'user123', [
    'tags' => ['premium', 'verified'],
    'preferences' => ['notifications' => true, 'language' => 'en']
]);
```

## Error Handling

```php
try {
    $users = $pdo->getAllDocuments('users');
} catch (Exception $e) {
    error_log('Firestore Error: ' . $e->getMessage());
    $users = [];
}
```

## Filtering in PHP

### Simple Filter
```php
$allUsers = $pdo->getAllDocuments('users');
$drivers = array_filter($allUsers, function($user) {
    return $user['role'] === 'driver';
});
```

### Multiple Conditions
```php
$activeDrivers = array_filter($allUsers, function($user) {
    return $user['role'] === 'driver' && $user['status'] === 'active';
});
```

## Sorting in PHP

### Sort by String Field
```php
usort($users, function($a, $b) {
    return strcmp($a['username'] ?? '', $b['username'] ?? '');
});
```

### Sort by Date (Newest First)
```php
usort($orders, function($a, $b) {
    $aTime = $a['created_at'] instanceof DateTime 
        ? $a['created_at']->getTimestamp() 
        : strtotime($a['created_at'] ?? '0');
    $bTime = $b['created_at'] instanceof DateTime 
        ? $b['created_at']->getTimestamp() 
        : strtotime($b['created_at'] ?? '0');
    return $bTime - $aTime;
});
```

## Pagination

```php
$allUsers = $pdo->getAllDocuments('users');
$page = $_GET['page'] ?? 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$paginatedUsers = array_slice($allUsers, $offset, $perPage);
$totalPages = ceil(count($allUsers) / $perPage);
```

## Search

```php
$search = $_GET['search'] ?? '';
$users = pdo->getAllDocuments('users');

$results = array_filter($users, function($user) use ($search) {
    if (empty($search)) return true;
    
    $searchLower = strtolower($search);
    $usernameLower = strtolower($user['username'] ?? '');
    $emailLower = strtolower($user['email'] ?? '');
    
    return strpos($usernameLower, $searchLower) !== false ||
           strpos($emailLower, $searchLower) !== false;
});
```

## Count Operations

```php
// Count all documents
$allUsers = $pdo->getAllDocuments('users');
$totalUsers = count($allUsers);

// Count with condition
$drivers = array_filter($allUsers, fn($u) => $u['role'] === 'driver');
$driverCount = count($drivers);

// Count by grouping
$countByRole = array_reduce($allUsers, function($carry, $user) {
    $role = $user['role'] ?? 'unknown';
    $carry[$role] = ($carry[$role] ?? 0) + 1;
    return $carry;
}, []);
```

## Common Patterns

### Join Pattern (Manual)
```php
// Get orders with customer details
$orders = $pdo->getAllDocuments('orders');
$users = $pdo->getAllDocuments('users');

// Create user lookup
$userMap = [];
foreach ($users as $user) {
    $userMap[$user['id']] = $user;
}

// Attach user data to orders
foreach ($orders as &$order) {
    if (isset($order['user_id'])) {
        $order['customer'] = $userMap[$order['user_id']] ?? null;
    }
}
```

### Update Multiple Documents
```php
// Ban all users with email from domain
$allUsers = $pdo->getAllDocuments('users');
foreach ($allUsers as $user) {
    if (str_ends_with($user['email'], '@spam.com')) {
        $pdo->update('users', $user['id'], ['status' => 'banned']);
    }
}
```

## Collection Names (Case Sensitive)
- `users` - User accounts
- `orders` - Orders
- `Products` - Products (note the capital P)
- `verifications` - ID verifications
- `order_images` - Order delivery images
- `order_items` - Individual items in orders

## Field Types
- String: `'text'` → stored as-is
- Number: `123` or `45.67` → stored as number
- Boolean: `true` or `false` → stored as boolean
- Date: `new DateTime()` → stored as timestamp
- Array: `[1, 2, 3]` → stored as array
- Object: `['key' => 'value']` → stored as map

## Debugging

```php
// Log data structure
error_log(json_encode($user, JSON_PRETTY_PRINT));

// Check if field exists
if (isset($user['email'])) {
    // Field exists
}

// Check data type
if ($user['created_at'] instanceof DateTime) {
    // Is DateTime object
}

// Pretty print in development
echo '<pre>';
var_dump($user);
echo '</pre>';
```
