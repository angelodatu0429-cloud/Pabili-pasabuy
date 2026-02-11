<?php
/**
 * Firestore Database Initialization
 * Creates collections and sets up the database structure
 * Run this ONCE to initialize Firestore
 */

session_start();
require_once 'includes/db.php';

$output = [];

try {
    // Initialize collections with proper schema
    $collections = [
        'users' => [
            'admin' => [
                'id' => '1',
                'username' => 'admin',
                'password_hash' => '$2y$10$wVvJ54O9M8iCzcyHjjUzRe9udcPdCFh/kajfLBUr.zwBAmR1pWqAG',
                'email' => 'admin@delivery.app',
                'phone' => '+1234567890',
                'role' => 'admin',
                'status' => 'active',
                'created_at' => new \DateTime(),
                'updated_at' => new \DateTime(),
            ],
            'customer_1' => [
                'username' => 'john_customer',
                'email' => 'john@email.com',
                'phone' => '+1234567890',
                'role' => 'customer',
                'status' => 'active',
                'created_at' => new \DateTime(),
                'updated_at' => new \DateTime(),
            ],
            'customer_2' => [
                'username' => 'jane_customer',
                'email' => 'jane@email.com',
                'phone' => '+1234567891',
                'role' => 'customer',
                'status' => 'active',
                'created_at' => new \DateTime(),
                'updated_at' => new \DateTime(),
            ],
            'customer_3' => [
                'username' => 'bob_customer',
                'email' => 'bob@email.com',
                'phone' => '+1234567892',
                'role' => 'customer',
                'status' => 'active',
                'created_at' => new \DateTime(),
                'updated_at' => new \DateTime(),
            ],
            'driver_1' => [
                'username' => 'driver_mike',
                'email' => 'mike@email.com',
                'phone' => '+1234567893',
                'role' => 'driver',
                'status' => 'active',
                'created_at' => new \DateTime(),
                'updated_at' => new \DateTime(),
            ],
            'driver_2' => [
                'username' => 'driver_sarah',
                'email' => 'sarah@email.com',
                'phone' => '+1234567894',
                'role' => 'driver',
                'status' => 'active',
                'created_at' => new \DateTime(),
                'updated_at' => new \DateTime(),
            ],
            'driver_3' => [
                'username' => 'driver_alex',
                'email' => 'alex@email.com',
                'phone' => '+1234567895',
                'role' => 'driver',
                'status' => 'active',
                'created_at' => new \DateTime(),
                'updated_at' => new \DateTime(),
            ],
        ],
        'products' => [
            'pizza_margherita' => [
                'name' => 'Margherita Pizza',
                'description' => 'Classic pizza with tomato, mozzarella, and basil',
                'price' => 8.99,
                'category' => 'Food',
                'image_path' => null,
                'stock' => 25,
                'is_active' => true,
                'created_at' => new \DateTime(),
                'updated_at' => new \DateTime(),
            ],
            'caesar_salad' => [
                'name' => 'Caesar Salad',
                'description' => 'Fresh romaine lettuce with parmesan and croutons',
                'price' => 6.99,
                'category' => 'Food',
                'image_path' => null,
                'stock' => 30,
                'is_active' => true,
                'created_at' => new \DateTime(),
                'updated_at' => new \DateTime(),
            ],
            'orange_juice' => [
                'name' => 'Orange Juice',
                'description' => 'Fresh squeezed orange juice',
                'price' => 3.99,
                'category' => 'Beverages',
                'image_path' => null,
                'stock' => 50,
                'is_active' => true,
                'created_at' => new \DateTime(),
                'updated_at' => new \DateTime(),
            ],
            'apples' => [
                'name' => 'Apples (1 kg)',
                'description' => 'Fresh red apples',
                'price' => 4.99,
                'category' => 'Groceries',
                'image_path' => null,
                'stock' => 100,
                'is_active' => true,
                'created_at' => new \DateTime(),
                'updated_at' => new \DateTime(),
            ],
            'wheat_bread' => [
                'name' => 'Whole Wheat Bread',
                'description' => 'Organic whole wheat bread loaf',
                'price' => 2.99,
                'category' => 'Groceries',
                'image_path' => null,
                'stock' => 40,
                'is_active' => true,
                'created_at' => new \DateTime(),
                'updated_at' => new \DateTime(),
            ],
            'chocolate_cake' => [
                'name' => 'Chocolate Cake',
                'description' => 'Rich chocolate layer cake',
                'price' => 5.99,
                'category' => 'Food',
                'image_path' => null,
                'stock' => 15,
                'is_active' => true,
                'created_at' => new \DateTime(),
                'updated_at' => new \DateTime(),
            ],
            'mineral_water' => [
                'name' => 'Mineral Water Bottle',
                'description' => '500ml mineral water bottle',
                'price' => 1.99,
                'category' => 'Beverages',
                'image_path' => null,
                'stock' => 200,
                'is_active' => true,
                'created_at' => new \DateTime(),
                'updated_at' => new \DateTime(),
            ],
            'tomato_sauce' => [
                'name' => 'Tomato Sauce',
                'description' => 'Italian tomato pasta sauce',
                'price' => 3.49,
                'category' => 'Groceries',
                'image_path' => null,
                'stock' => 80,
                'is_active' => true,
                'created_at' => new \DateTime(),
                'updated_at' => new \DateTime(),
            ],
        ],
    ];
    
    // Create collections and documents
    foreach ($collections as $collectionName => $documents) {
        foreach ($documents as $docId => $docData) {
            try {
                $pdo->update($collectionName, $docId, $docData);
                $output[] = "✓ Created {$collectionName}/{$docId}";
            } catch (Exception $e) {
                $output[] = "✗ Failed to create {$collectionName}/{$docId}: " . $e->getMessage();
            }
        }
    }
    
    // Create empty orders and verifications collections by adding a placeholder
    try {
        $pdo->update('Orders', '_init', ['_init' => true]);
        $pdo->delete('Orders', '_init');
        $output[] = "✓ Created Orders collection";
    } catch (Exception $e) {
        $output[] = "✗ Failed to create Orders collection";
    }
    
    try {
        $pdo->update('verifications', '_init', ['_init' => true]);
        $pdo->delete('verifications', '_init');
        $output[] = "✓ Created verifications collection";
    } catch (Exception $e) {
        $output[] = "✗ Failed to create verifications collection";
    }
    
    $success = true;
    
} catch (Exception $e) {
    $success = false;
    $output[] = "✗ Error: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firestore Initialization</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            max-width: 600px;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 {
            color: #333;
            margin-bottom: 30px;
        }
        .output {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            border-left: 4px solid #667eea;
            margin-bottom: 20px;
            max-height: 400px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.6;
        }
        .output-line {
            margin: 5px 0;
        }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .btn {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔥 Firestore Initialization</h1>
        
        <div class="output">
            <?php foreach ($output as $line): ?>
                <div class="output-line <?php echo strpos($line, '✓') === 0 ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($line); ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success" role="alert">
                <h4 class="alert-heading">✓ Success!</h4>
                <p>Firestore has been initialized with all collections and sample data.</p>
                <hr>
                <p class="mb-0">
                    <strong>⚠️ IMPORTANT:</strong> Delete this file (init-firestore.php) for security.<br>
                    <a href="login.php" class="btn btn-primary mt-2">Go to Login</a>
                </p>
            </div>
        <?php else: ?>
            <div class="alert alert-danger" role="alert">
                <h4 class="alert-heading">✗ Error</h4>
                <p>Firestore initialization failed. Check your service account credentials.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
