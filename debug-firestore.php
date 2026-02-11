<?php
/**
 * Debug Firestore Connection and Insert
 */

require_once 'includes/db.php';

echo "<pre>";
echo "=== FIRESTORE DEBUG ===\n\n";

// Test 1: Check if Firestore adapter is initialized
echo "1. Firestore Adapter Status:\n";
var_dump(get_class($pdo));
echo "\n";

// Test 2: Try to get existing collections
echo "2. Checking existing collections:\n";
try {
    $users = $pdo->getAllDocuments('Users');
    echo "✓ Users collection: " . count($users) . " documents\n";
} catch (Exception $e) {
    echo "✗ Users error: " . $e->getMessage() . "\n";
}

try {
    $products = $pdo->getAllDocuments('Products');
    echo "✓ Products collection: " . count($products) . " documents\n";
} catch (Exception $e) {
    echo "✗ Products error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 3: Try a test insert to Products
echo "3. Testing Product Insert:\n";
try {
    $testProduct = [
        'name' => 'Test Product ' . time(),
        'type' => 'Vegetable',
        'variants' => [
            [
                'weight' => '1kg',
                'brand' => 'Test Brand',
                'price' => 150.00,
                'stock' => 50
            ]
        ],
        'is_active' => true,
        'created_at' => new DateTime(),
        'updated_at' => new DateTime()
    ];
    
    echo "Inserting test product: " . json_encode($testProduct, JSON_PRETTY_PRINT) . "\n\n";
    
    $result = $pdo->insert('Products', $testProduct);
    echo "✓ Product inserted successfully!\n";
    echo "Document ID: " . $pdo->lastInsertId() . "\n";
    
} catch (Exception $e) {
    echo "✗ Insert failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n";

// Test 4: Verify the product was saved
echo "4. Verifying Products collection:\n";
try {
    $products = $pdo->getAllDocuments('Products');
    echo "✓ Products in database: " . count($products) . "\n";
    if (count($products) > 0) {
        echo "\nLatest product:\n";
        var_dump($products[count($products) - 1]);
    }
} catch (Exception $e) {
    echo "✗ Error fetching products: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
