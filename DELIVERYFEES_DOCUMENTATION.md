# DeliveryFees Collection Documentation

## Overview
The `DeliveryFees` collection in Firestore stores platform-wide delivery fee settings including the average base fee and per-kilometer rate used for delivery calculations.

## Collection Structure

### Collection Name
```
DeliveryFees
```

### Main Document
```
DeliveryFees/rates
```

## Document Schema

```json
{
  "avg_base_fee": 50.00,
  "avg_per_km_rate": 10.00,
  "updated_at": "2026-02-09T14:30:00Z",
  "updated_by": "admin_user_id",
  "description": "Average delivery rates for the platform"
}
```

## Field Definitions

| Field | Type | Description |
|-------|------|-------------|
| `avg_base_fee` | Number | Fixed delivery fee (in PHP) applied as the base charge for all deliveries |
| `avg_per_km_rate` | Number | Additional charge (in PHP) per kilometer traveled |
| `updated_at` | Timestamp | When the rates were last updated |
| `updated_by` | String | User/Admin ID who last updated the rates |
| `description` | String | Description of the rates |

## Usage Examples

### PHP - Fetch Delivery Fees

```php
<?php
require_once 'includes/delivery-fees-helper.php';

// Get all delivery fees
$fees = getDeliveryFees();
echo "Base Fee: " . $fees['avg_base_fee'];
echo "Per KM Rate: " . $fees['avg_per_km_rate'];
```

### PHP - Calculate Delivery Fee

```php
<?php
require_once 'includes/delivery-fees-helper.php';

// Calculate fee for 5 km delivery
$distance = 5;
$fee = calculateDeliveryFee($distance);
echo "Delivery Fee for 5km: ₱" . number_format($fee, 2);
```

### PHP - Update Delivery Fees

```php
<?php
require_once 'includes/delivery-fees-helper.php';

// Update base fee to 60 and per-km rate to 12
$success = updateDeliveryFees(60, 12, 'admin_001');

if ($success) {
    echo "Rates updated successfully";
}
```

## Calculation Formula

```
Total Delivery Fee = Base Fee + (Distance in km × Per KM Rate)

Example:
Base Fee = ₱50
Per KM Rate = ₱10
Distance = 5 km

Total Fee = ₱50 + (5 × ₱10) = ₱100
```

## Access Control

- **View**: Available to all authenticated users (via queries)
- **Edit**: Restricted to admin users through `/admin/delivery-fees.php`
- **Collection**: `DeliveryFees` in Firestore Projects

## Integration Points

### Admin Panel
- **File**: `c:\xampp\htdocs\admin\delivery-fees.php`
- **Features**:
  - View current rates
  - Update base fee
  - Update per-km rate
  - Live preview of rates
  - Automatic Firestore sync

### Helper Functions
- **File**: `c:\xampp\htdocs\admin\includes\delivery-fees-helper.php`
- **Functions**:
  - `getDeliveryFees()` - Fetch fees from Firestore
  - `calculateDeliveryFee($distance, $fees)` - Calculate total delivery cost
  - `updateDeliveryFees($baseFee, $perKmRate, $updatedBy)` - Update fees
  - `getDeliveryFeesInfo()` - Get formatted fee string

## Firestore Security Rules

Recommended security rules for `DeliveryFees` collection:

```
rules_version = '2';
service cloud.firestore {
  match /databases/{database}/documents {
    // DeliveryFees collection - Read for all authenticated users, Write for admins only
    match /DeliveryFees/{document=**} {
      allow read: if request.auth != null;
      allow write: if request.auth != null && 
                      request.auth.token.is_admin == true;
    }
  }
}
```

## Data Flow

```
Admin UI
  ↓
delivery-fees.php (form submission)
  ↓
Firestore DeliveryFees/rates (update)
  ↓
Cached in memory
  ↓
Accessed via getDeliveryFees()
  ↓
Used in delivery-fees-helper.php functions
```

## Best Practices

1. **Single Document**: Keep all delivery fees in one document (`DeliveryFees/rates`) for quick access
2. **Timestamp Updates**: Always update `updated_at` when changing rates
3. **Admin Tracking**: Log which admin updated the rates with `updated_by`
4. **Caching**: Consider caching rates in memory for high-traffic scenarios
5. **Validation**: Validate that base fee and per-km rate are positive numbers
6. **Backup**: Regularly backup your Firestore data including DeliveryFees collection

## Example Variations

### Regional Rates (Future Enhancement)

If you want to support multiple regions or zones:

```
DeliveryFees/
  ├── rates (default rates)
  ├── zone_metro_manila (zone-specific rates)
  ├── zone_cebu (zone-specific rates)
  └── zone_davao (zone-specific rates)
```

### Pricing Tiers (Future Enhancement)

```
DeliveryFees/
  ├── standard_delivery
  ├── express_delivery
  └── overnight_delivery
```

## Troubleshooting

### Rates Not Saving
- Check Firestore security rules allow writes for admin
- Verify admin session is active
- Check browser console for errors

### Rates Show as Zero
- Check if `DeliveryFees/rates` document exists
- Verify fields `avg_base_fee` and `avg_per_km_rate` are populated
- Check Firestore database for the collection

### Helper Functions Not Working
- Ensure `delivery-fees-helper.php` is included in your PHP file
- Verify `$pdo` global variable is initialized (from `db.php`)
- Check error logs for database connection errors

## Related Files

- `/admin/delivery-fees.php` - Admin panel for managing rates
- `/admin/includes/delivery-fees-helper.php` - Helper functions
- `/admin/includes/db.php` - Firestore adapter
- `/admin/includes/functions.php` - Utility functions
