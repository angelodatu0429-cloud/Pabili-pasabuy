# Firestore Integration Guide

## Overview

Your website is now fully integrated with Google Cloud Firestore. The admin panel uses Firestore as the primary database instead of traditional SQL databases.

## Database Structure

Your Firestore project is named: **pabili-pasabuy**

### Collections and Document Structure

#### 1. **users** Collection
Stores user account information for customers, drivers, and admins.

```
users/{userId}
├── id (string) - Document ID
├── username (string) - Unique username
├── password_hash (string) - Hashed password
├── email (string) - Email address
├── phone (string) - Phone number
├── role (string) - "admin", "customer", or "driver"
├── status (string) - "active", "inactive", or "banned"
├── created_at (timestamp) - Account creation time
└── updated_at (timestamp) - Last update time
```

#### 2. **orders** Collection
Stores all orders placed by customers.

```
orders/{orderId}
├── id (string) - Document ID
├── user_id (string) - Reference to customer user ID
├── driver_id (string) - Reference to assigned driver user ID
├── total (number) - Order total amount
├── address (string) - Delivery address
├── status (string) - "pending", "accepted", "in_transit", "completed", "cancelled"
├── payment_status (string) - "pending", "completed", "failed"
├── payment_method (string) - Payment method used
├── created_at (timestamp) - Order creation time
└── completed_at (timestamp) - Order completion time
```

#### 3. **Products** Collection
Stores product information.

```
Products/{productId}
├── id (string) - Document ID
├── name (string) - Product name
├── description (string) - Product description
├── price (number) - Product price
├── category (string) - Product category
├── image_path (string) - Path to product image
├── stock (number) - Stock quantity
├── is_active (boolean) - Whether product is available
├── created_at (timestamp) - Creation time
└── updated_at (timestamp) - Last update time
```

#### 4. **verifications** Collection
Stores ID verification requests from users.

```
verifications/{verificationId}
├── id (string) - Document ID
├── user_id (string) - Reference to user ID
├── id_type (string) - Type of ID submitted
├── id_image_path (string) - Path to ID image
├── status (string) - "pending", "approved", "rejected"
├── submitted_at (timestamp) - Submission time
├── reviewed_at (timestamp) - Review time
└── admin_note (string) - Admin notes/rejection reason
```

## How to Fetch Data from Firestore

### Basic Methods Available

The `FirestoreAdapter` class provides these methods:

#### Get All Documents
```php
$users = $pdo->getAllDocuments('users');
// Returns array of all documents in the collection
```

#### Get Single Document
```php
$user = $pdo->getDocument('users', 'userId123');
// Returns single document by ID, or null if not found
```

#### Query with Filtering
```php
$activeUsers = $pdo->query('users', 'status', '==', 'active')->fetchAll();
// Supports operators: ==, !=, <, >, <=, >=
```

#### Update Document
```php
$pdo->update('users', 'userId123', [
    'status' => 'banned',
    'updated_at' => new DateTime()
]);
```

#### Delete Document
```php
$pdo->delete('users', 'userId123');
```

#### Insert Document
```php
$pdo->insert('collection', ['field' => 'value', 'another' => 'data']);
$newDocId = $pdo->lastInsertId(); // Get auto-generated document ID
```

## Common Patterns

### Fetching with Joins (Firestore has no native joins)

Since Firestore doesn't support SQL-like joins, you need to manually join data:

```php
// Get all orders with customer and driver information
$allOrders = $pdo->getAllDocuments('orders');
$allUsers = $pdo->getAllDocuments('users');

// Create lookup map for users
$userMap = [];
foreach ($allUsers as $user) {
    $userMap[$user['id']] = $user;
}

// Attach user info to orders
foreach ($allOrders as &$order) {
    if (isset($order['user_id']) && isset($userMap[$order['user_id']])) {
        $order['customer_name'] = $userMap[$order['user_id']]['username'];
        $order['customer_email'] = $userMap[$order['user_id']]['email'];
    }
    
    if (isset($order['driver_id']) && isset($userMap[$order['driver_id']])) {
        $order['driver_name'] = $userMap[$order['driver_id']]['username'];
        $order['driver_phone'] = $userMap[$order['driver_id']]['phone'];
    }
}
```

### Filtering in PHP

```php
// Get all active drivers
$allUsers = $pdo->getAllDocuments('users');
$activeDrive = array_filter($allUsers, function($user) {
    return $user['role'] === 'driver' && $user['status'] === 'active';
});
```

### Sorting in PHP

```php
// Sort users by username
usort($users, function($a, $b) {
    return strcmp($a['username'], $b['username']);
});

// Sort orders by date (newest first)
usort($orders, function($a, $b) {
    $aTime = $a['created_at'] instanceof DateTime ? $a['created_at']->getTimestamp() : strtotime($a['created_at']);
    $bTime = $b['created_at'] instanceof DateTime ? $b['created_at']->getTimestamp() : strtotime($b['created_at']);
    return $bTime - $aTime;
});
```

## Updated Pages

The following pages have been updated to use Firestore:

### 1. **users.php** - User & Driver Management
- Fetches all users from Firestore
- Filters by role (customer/driver)
- Ban/unban functionality
- Counts users by role

### 2. **products.php** - Product Management
- Fetches all products from Firestore
- Search and category filtering
- CRUD operations (Create, Read, Update, Delete)
- Category management

### 3. **completed-orders.php** - Completed Orders
- Fetches completed orders with customer/driver info
- Displays order statistics
- Shows delivery proof images

### 4. **verifications.php** - ID Verification Management
- Fetches pending and completed verifications
- Links to user accounts
- Approve/reject functionality
- Filter by user role

### 5. **index.php** - Dashboard
- Displays key statistics using Firestore data
- Shows recent completed orders
- Uses helper functions for counts and calculations

## Helper Functions Available

In `includes/functions.php`, these functions are available:

```php
// User statistics
getTotalCustomersCount($pdo)        // Count of all customers
getTotalRidersCount($pdo)            // Count of all drivers
getActiveDriversCount($pdo)          // Count of active drivers
getNewUsersThisWeek($pdo)            // New signups in last 7 days

// Order statistics
getTodayOrdersCount($pdo)            // Orders placed today
getTodayRevenue($pdo)                // Revenue from completed orders today
getTotalCompletedOrders($pdo)        // Total completed orders

// Verification statistics
getPendingVerificationsCount($pdo)   // Pending ID verifications

// Utilities
formatCurrency($amount)              // Format as currency (₱)
formatDate($date)                    // Format date for display
sanitize($input)                     // Sanitize user input
verifyCSRFToken($token)              // Verify CSRF token
generateCSRFToken()                  // Generate CSRF token
```

## Data Types

Firestore automatically converts PHP types:

- **String** → `stringValue`
- **Number (int)** → `integerValue`
- **Number (float)** → `doubleValue`
- **Boolean** → `booleanValue`
- **DateTime** → `timestampValue`
- **Array** → `arrayValue`
- **Object** → `mapValue`

When retrieving data, the `FirestoreAdapter` automatically converts back to PHP types.

## Error Handling

Always wrap Firestore operations in try-catch:

```php
try {
    $users = $pdo->getAllDocuments('users');
} catch (Exception $e) {
    error_log('Error fetching users: ' . $e->getMessage());
    $users = [];
}
```

## Performance Tips

1. **Cache frequently accessed data**: Store user maps in variables
2. **Limit document fetches**: Use `array_slice()` to limit results after fetching
3. **Filter in PHP**: It's often faster to filter in PHP than fetch everything and filter
4. **Avoid N+1 queries**: Batch fetch related documents (like the user map pattern)

## Creating New Pages with Firestore

When creating new admin pages:

1. **Import dependencies**:
```php
session_start();
require_once 'includes/db.php';      // Gets Firestore adapter as $pdo
require_once 'includes/functions.php'; // Helper functions
require_once 'includes/header.php';   // Auth & layout
```

2. **Check authentication**:
```php
requireLogin(); // Redirects to login if not authenticated
```

3. **Fetch data**:
```php
$documents = $pdo->getAllDocuments('collection_name');
```

4. **Include sidebar**:
```php
require_once 'includes/sidebar.php'; // Navigation menu
```

## Security Notes

- All user inputs are sanitized with `sanitize()` function
- CSRF tokens required for all POST requests
- User roles validated before admin operations
- Passwords are hashed with `password_hash()` in PHP
- Service account key securely stored in `config/` folder

## Troubleshooting

### "Firestore Error: Service account JSON not found"
- Ensure `config/pabili-pasabuy-firebase-adminsdk-fbsvc-7ea41bf672.json` exists
- Check file permissions

### "Failed to get access token"
- Check Firebase project credentials
- Verify internet connectivity for OAuth token fetch

### Empty results when fetching data
- Verify collection names are correct (case-sensitive)
- Check Firestore console for actual data
- Use `error_log()` to debug queries

### DateTime handling
- Always check if value is DateTime instance before calling methods
- Convert to timestamp: `$date->getTimestamp()`
- Convert to string: `$date->format('Y-m-d H:i:s')`

## Support

For Firebase/Firestore documentation:
- https://firebase.google.com/docs/firestore
- https://cloud.google.com/firestore/docs

For this project:
- Check `database-schema.sql` for expected data structure
- Review existing pages as examples of proper Firestore usage
- Check `includes/firestore.php` for class documentation
