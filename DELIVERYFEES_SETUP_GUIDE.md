# DeliveryFees Collection Setup Guide

## What Was Created

### 1. Firestore Collection
**Collection Name**: `DeliveryFees`
**Document ID**: `rates`

This is where your average base fee and per-kilometer delivery rates are stored.

### 2. Admin Interface
**File**: `c:\xampp\htdocs\admin\delivery-fees.php`

Features:
- ✅ Input forms for Average Base Fee and Average Per KM Rate
- ✅ Live preview of rates
- ✅ Save directly to Firestore `DeliveryFees/rates` document
- ✅ Displays collection information in the UI
- ✅ CSRF token protection
- ✅ Admin tracking (records who updated the rates)

### 3. Helper Functions
**File**: `c:\xampp\htdocs\admin\includes\delivery-fees-helper.php`

Available functions:
```php
getDeliveryFees()              // Fetch fees from Firestore
calculateDeliveryFee($km)      // Calculate total delivery cost
updateDeliveryFees($base, $km) // Update fees programmatically
getDeliveryFeesInfo()          // Get formatted fee string
```

### 4. Documentation
**Files Created**:
- `DELIVERYFEES_DOCUMENTATION.md` - Complete documentation
- `DELIVERYFEES_SCHEMA_SETUP.md` - Schema and Firestore setup

## Bidirectional Connection Overview

```
Admin Panel (delivery-fees.php)
    ↓ (User inputs rates)
    ↓
Firestore DeliveryFees Collection
    ↓ (Stores: base_fee, per_km_rate, timestamp, admin_id)
    ↓
Helper Functions (delivery-fees-helper.php)
    ↓ (Fetch & calculate)
    ↓
Any Page in Your App
    ↓ (Use rates for delivery calculations)
    ↓
Database Updates Reflected Everywhere
```

## How to Use in Your Pages

### Step 1: Include the Helper
```php
<?php
require_once 'includes/delivery-fees-helper.php';

// Now you have access to all delivery fee functions
?>
```

### Step 2: Use the Functions

#### Get Current Rates
```php
$fees = getDeliveryFees();
echo "Base Fee: ₱" . $fees['avg_base_fee'];
echo "Per KM: ₱" . $fees['avg_per_km_rate'];
```

#### Calculate Delivery Cost
```php
$deliveryDistance = 5.5; // kilometers
$deliveryFee = calculateDeliveryFee($deliveryDistance);
echo "Delivery costs ₱" . number_format($deliveryFee, 2);
```

#### Update Rates Programmatically
```php
$success = updateDeliveryFees(65, 12, 'admin_123');
if ($success) {
    echo "Rates updated!";
}
```

## Admin Workflow

1. Go to **Admin Panel** → **Delivery Rates**
2. Enter **Average Base Fee** (e.g., ₱50)
3. Enter **Average Per KM Rate** (e.g., ₱10)
4. See **Live Preview** update
5. Click **Save to Firestore**
6. Success message confirms data saved to `DeliveryFees/rates`

## Data Stored in Firestore

When you save rates, this is stored in `DeliveryFees/rates`:

```json
{
  "avg_base_fee": 50,
  "avg_per_km_rate": 10,
  "updated_at": Timestamp = "2026-02-09 14:30:00",
  "updated_by": "admin_user_id",
  "description": "Average delivery rates for the platform"
}
```

## Feature Checklist

- ✅ Dedicated `DeliveryFees` collection in Firestore
- ✅ Admin UI for managing rates (live preview)
- ✅ Automatic Firestore synchronization
- ✅ Helper functions for quick access
- ✅ Delivery fee calculation function
- ✅ Timestamp tracking of updates
- ✅ Admin ID logging
- ✅ Error handling and logging
- ✅ Complete documentation

## Integration Example

Here's how to use this in your orders system:

```php
<?php
require_once 'includes/delivery-fees-helper.php';

// User enters delivery distance
$distance = 3.2; // km

// Get delivery fee
$deliveryFee = calculateDeliveryFee($distance);

// Use in order calculation
$subtotal = 1000;
$total = $subtotal + $deliveryFee;

echo "Subtotal: ₱" . number_format($subtotal, 2);
echo "Delivery Fee: ₱" . number_format($deliveryFee, 2);
echo "Total: ₱" . number_format($total, 2);
?>
```

## Troubleshooting

### "Data not saving"
- Check Firestore console → `DeliveryFees` collection exists
- Verify `rates` document is being created/updated
- Check browser console for errors

### "Helper functions not found"
- Make sure you included the helper file: `require_once 'includes/delivery-fees-helper.php';`
- Check file path is correct relative to your current file

### "Getting zero rates"
- Navigate to Admin Panel → Delivery Rates
- Check if rates are showing
- If empty, enter values and save

## Next Steps

1. **Test in Admin Panel**: Go to `/admin/delivery-fees.php` and update some rates
2. **Check Firestore**: View the data in your Firestore console
3. **Integrate into Pages**: Use helper functions in your order/delivery pages
4. **Set Security Rules**: Configure Firestore rules for read/write access

## File Summary

| File | Purpose |
|------|---------|
| `delivery-fees.php` | Admin UI for managing fees |
| `delivery-fees-helper.php` | Functions to fetch/calculate fees |
| `DELIVERYFEES_DOCUMENTATION.md` | Full technical documentation |
| `DELIVERYFEES_SCHEMA_SETUP.md` | Firestore schema details |
