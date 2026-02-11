# DeliveryFees - Quick Reference & Usage Guide

## 30-Second Overview

**What**: Delivery rates stored in Firestore `DeliveryFees` collection  
**Where**: Admin panel at `/admin/delivery-fees.php`  
**How**: Use helper functions from `includes/delivery-fees-helper.php`  
**Use Case**: Calculate delivery costs based on distance  

## Quick Start (Copy-Paste Ready)

### 1. Include the Helper File
```php
<?php
require_once 'includes/delivery-fees-helper.php';
?>
```

### 2. Get Current Rates
```php
$fees = getDeliveryFees();
echo $fees['avg_base_fee'];      // e.g., 50
echo $fees['avg_per_km_rate'];   // e.g., 10
```

### 3. Calculate Delivery Fee
```php
$distance = 5;  // kilometers
$fee = calculateDeliveryFee($distance);
echo "₱" . number_format($fee, 2);  // ₱100.00
```

### 4. Display Fee Info
```php
echo getDeliveryFeesInfo();  // "Base: ₱50.00 + ₱10.00/km"
```

### 5. Update Rates (Admin Only)
```php
updateDeliveryFees(65, 12, 'admin_001');
```

## Function Reference

### getDeliveryFees()
Fetches delivery fees from Firestore.

**Returns**: Array with keys:
- `avg_base_fee` - Base fee amount
- `avg_per_km_rate` - Per-kilometer rate
- `updated_at` - Last update timestamp
- `updated_by` - Admin ID who updated

**Example**:
```php
$fees = getDeliveryFees();
if ($fees['avg_base_fee'] > 0) {
    echo "Rates are set";
}
```

**Returns on Error**: Default rates (all zeros)

---

### calculateDeliveryFee($distance, $fees = null)
Calculates total delivery fee for given distance.

**Parameters**:
- `$distance` (float) - Distance in kilometers
- `$fees` (array, optional) - Pre-fetched fees from getDeliveryFees()

**Returns**: Float - Total delivery fee rounded to 2 decimals

**Formula**: Base Fee + (Distance × Per KM Rate)

**Example**:
```php
// Simple usage
$fee = calculateDeliveryFee(3.5);

// Optimized - fetch fees once, use many times
$fees = getDeliveryFees();
$fee1 = calculateDeliveryFee(2.0, $fees);
$fee2 = calculateDeliveryFee(5.0, $fees);
$fee3 = calculateDeliveryFee(8.0, $fees);
```

---

### updateDeliveryFees($baseFee, $perKmRate, $updatedBy = null)
Updates delivery fees in Firestore (Admin only).

**Parameters**:
- `$baseFee` (float) - New base fee
- `$perKmRate` (float) - New per-km rate
- `$updatedBy` (string, optional) - Admin ID/name

**Returns**: Boolean - True if successful, false on error

**Example**:
```php
if (updateDeliveryFees(60, 11, $_SESSION['user_id'])) {
    echo "Rates updated!";
} else {
    echo "Update failed!";
}
```

---

### getDeliveryFeesInfo()
Gets formatted delivery fee string.

**Returns**: String formatted as "Base: ₱XX.XX + ₱XX.XX/km"

**Example**:
```php
echo getDeliveryFeesInfo();  // "Base: ₱50.00 + ₱10.00/km"
```

---

## Real-World Examples

### Example 1: Order Summary Page
```php
<?php
require_once 'includes/delivery-fees-helper.php';

$distance = 4.2;  // User's delivery distance
$subtotal = 1200; // Order subtotal

// Calculate delivery fee
$deliveryFee = calculateDeliveryFee($distance);
$total = $subtotal + $deliveryFee;

echo "Subtotal: ₱" . number_format($subtotal, 2);
echo "Delivery: ₱" . number_format($deliveryFee, 2);
echo "Total: ₱" . number_format($total, 2);
?>
```

### Example 2: Estimate Delivery Cost
```php
<?php
require_once 'includes/delivery-fees-helper.php';

// Get fees once
$fees = getDeliveryFees();

// Show estimates for different distances
$distances = [1, 3, 5, 10];
foreach ($distances as $km) {
    $fee = calculateDeliveryFee($km, $fees);
    echo "$km km: ₱" . number_format($fee, 2) . "\n";
}
?>
```

### Example 3: Admin Update
```php
<?php
require_once 'includes/delivery-fees-helper.php';

if ($_POST['action'] === 'update') {
    $base = (float)$_POST['base_fee'];
    $perKm = (float)$_POST['per_km'];
    $admin = $_SESSION['user_id'];
    
    if (updateDeliveryFees($base, $perKm, $admin)) {
        echo "Rates updated to Base: ₱$base, Per KM: ₱$perKm";
    } else {
        echo "Failed to update rates";
    }
}
?>
```

### Example 4: Dynamic Pricing Display
```php
<?php
require_once 'includes/delivery-fees-helper.php';

$info = getDeliveryFeesInfo();  // "Base: ₱50.00 + ₱10.00/km"
?>
<div class="delivery-info">
    <p>Delivery rates: <?php echo $info; ?></p>
</div>
```

## Common Use Cases

### Calculate fee for checkout
```php
$distance = $_POST['delivery_distance'];
$deliveryFee = calculateDeliveryFee($distance);
```

### Show delivery estimate before order
```php
$fees = getDeliveryFees();
$minFee = $fees['avg_base_fee'];
$perKm = $fees['avg_per_km_rate'];
echo "Delivery starts at ₱{$minFee} + ₱{$perKm} per km";
```

### Create order with delivery charge
```php
$deliveryFee = calculateDeliveryFee($distance);
$orderData = [
    'items_total' => $itemsTotal,
    'delivery_fee' => $deliveryFee,
    'total' => $itemsTotal + $deliveryFee
];
// Save order...
```

### Admin dashboard stats
```php
$fees = getDeliveryFees();
echo "Current base fee: ₱" . $fees['avg_base_fee'];
echo "Last updated: " . $fees['updated_at'];
echo "Updated by: " . $fees['updated_by'];
```

## Performance Tips

### Tip 1: Cache Fees
```php
// Fetch once
$fees = getDeliveryFees();

// Use multiple times
for ($i = 0; $i < 100; $i++) {
    calculateDeliveryFee($distances[$i], $fees);
}
```

### Tip 2: Check Before Calling
```php
// Don't repeatedly fetch
getDeliveryFees();  // ✗ Bad if called many times
getDeliveryFees();
getDeliveryFees();

// Do this instead
$fees = getDeliveryFees();  // ✓ Good - fetch once
```

### Tip 3: Use in Queries/Loops
```php
// Get rates once at page load
$fees = getDeliveryFees();

// Then calculate for multiple orders
foreach ($orders as $order) {
    $order['delivery_fee'] = calculateDeliveryFee(
        $order['distance'], 
        $fees  // Pass cached fees
    );
}
```

## Troubleshooting

### Issue: "Function not found"
**Solution**: Add at top of file:
```php
require_once 'includes/delivery-fees-helper.php';
```

### Issue: "Getting zeros"
**Solution**: Check if rates are set in admin panel `/admin/delivery-fees.php`

### Issue: "Connection error"
**Solution**: Check Firestore is running and Firestore SDK is installed

### Issue: "Wrong calculation"
**Solution**: Numbers might be strings. Cast to float:
```php
$distance = (float)$_POST['distance'];
```

## Integration Points in Admin

**File**: `/admin/delivery-fees.php`

Access from Admin Panel:
1. Click menu → System Settings (or similar)
2. Click → Delivery Rates
3. Enter rates and save

Data flows to: `DeliveryFees/rates` Firestore document

## File Locations

| Purpose | Location |
|---------|----------|
| Admin Interface | `/admin/delivery-fees.php` |
| Helper Functions | `/admin/includes/delivery-fees-helper.php` |
| Documentation | `/admin/DELIVERYFEES_DOCUMENTATION.md` |
| Schema Guide | `/admin/DELIVERYFEES_SCHEMA_SETUP.md` |
| Setup Guide | `/admin/DELIVERYFEES_SETUP_GUIDE.md` |

## Firestore Location

**Collection**: `DeliveryFees`  
**Document**: `rates`  
**Fields**:
- `avg_base_fee` (number)
- `avg_per_km_rate` (number)
- `updated_at` (timestamp)
- `updated_by` (string)
- `description` (string)

## Related Functions (from functions.php)

```php
formatCurrency($value)  // Formats number as ₱XX.XX
```

## Advanced: Direct Firestore Access

```php
<?php
require_once 'includes/db.php';

// Direct access (not recommended - use helper instead)
$rates = $pdo->getDocument('DeliveryFees', 'rates');
$rate = $rates['avg_base_fee'] ?? 0;
?>
```

## Security Notes

- Only admins can update rates (handled by Firestore rules)
- All users can read rates (needed for calculations)
- Updates are tracked (who changed, when)
- Helper functions include error handling

## Version Info

- **Created**: February 9, 2026
- **Firestore Collections**: 1 (DeliveryFees)
- **PHP Files**: 1 (delivery-fees-helper.php)
- **Admin Pages**: 1 (delivery-fees.php)
- **Documentation**: 3 files
