# Firestore Setup Verification Checklist

## Pre-Flight Checks

### Firestore Configuration ✓
- [x] Service account JSON exists: `config/pabili-pasabuy-firebase-adminsdk-fbsvc-7ea41bf672.json`
- [x] Project ID is: `pabili-pasabuy`
- [x] `includes/firestore.php` contains `FirestoreAdapter` class
- [x] `includes/db.php` initializes Firestore connection

### Collections Verified ✓
- [x] `users` collection has documents with: id, username, email, phone, role, status
- [x] `orders` collection has documents with: id, user_id, driver_id, total, status, completed_at
- [x] `Products` collection (capital P) has documents with: id, name, price, category
- [x] `verifications` collection has documents with: id, user_id, status, id_image_path

### File Updates Completed ✓
- [x] `users.php` - Updated to use Firestore (ban/unban, filtering)
- [x] `completed-orders.php` - Updated to fetch orders with user data
- [x] `verifications.php` - Updated to fetch verifications with user data
- [x] `index.php` - Updated to fetch recent orders from Firestore
- [x] `products.php` - Already compatible with Firestore

### Helper Functions Available ✓
- [x] `getTotalCustomersCount($pdo)` - In functions.php
- [x] `getTotalRidersCount($pdo)` - In functions.php
- [x] `getActiveDriversCount($pdo)` - In functions.php
- [x] `getTodayRevenue($pdo)` - In functions.php
- [x] `getPendingVerificationsCount($pdo)` - In functions.php
- [x] `getTotalCompletedOrders($pdo)` - In functions.php
- [x] `getNewUsersThisWeek($pdo)` - In functions.php

## Testing Steps

### Step 1: Verify Connection
```bash
# Check if Firestore adapter can connect
# Go to http://localhost/admin/index.php
# Should display dashboard with statistics from Firestore
```
- [ ] Dashboard loads without errors
- [ ] Statistics show non-zero counts
- [ ] Recent orders appear with customer names
- [ ] No error messages in browser console

### Step 2: Test Users Page
```bash
# Go to http://localhost/admin/users.php
```
- [ ] Page loads
- [ ] User list appears from Firestore
- [ ] Filter by role works (Customers/Drivers)
- [ ] Ban button appears on users
- [ ] Unban button appears on banned users

**Try banning a user:**
- [ ] Click ban button
- [ ] See success message
- [ ] User status changes in Firestore
- [ ] Refresh page - user shows as banned

### Step 3: Test Products Page
```bash
# Go to http://localhost/admin/products.php
```
- [ ] Products list appears from Firestore
- [ ] Search functionality works
- [ ] Category filter works
- [ ] Can add new product (creates in Firestore)
- [ ] Can edit product (updates in Firestore)
- [ ] Can delete product (removes from Firestore)

### Step 4: Test Completed Orders
```bash
# Go to http://localhost/admin/completed-orders.php
```
- [ ] Completed orders appear from Firestore
- [ ] Customer names are displayed (joined from users)
- [ ] Driver names are displayed (joined from users)
- [ ] Order totals are shown
- [ ] Statistics display correct counts

### Step 5: Test Verifications
```bash
# Go to http://localhost/admin/verifications.php
```
- [ ] Verifications appear from Firestore
- [ ] Pending verifications show first
- [ ] Can approve verification
- [ ] Can reject verification with note
- [ ] User data is attached (name, email, role)
- [ ] Filter by role works

## Data Integrity Checks

### Check Users Collection
```php
// In browser console or temp script
$users = $pdo->getAllDocuments('users');
echo count($users); // Should be > 0
```
- [ ] Users have `id` field
- [ ] Users have `username` field
- [ ] Users have `role` field (admin/customer/driver)
- [ ] Users have `status` field (active/banned/inactive)
- [ ] Users have timestamps (created_at, updated_at)

### Check Orders Collection
```php
$orders = $pdo->getAllDocuments('orders');
echo count($orders); // Should be > 0
```
- [ ] Orders have `id` field
- [ ] Orders reference `user_id` (customer)
- [ ] Orders reference `driver_id` (assigned driver)
- [ ] Orders have `status` field
- [ ] Orders have `total` amount
- [ ] Orders have `created_at` timestamp

### Check Products Collection
```php
$products = $pdo->getAllDocuments('Products'); // Note: Capital P
echo count($products); // Should be > 0
```
- [ ] Products have `id` field
- [ ] Products have `name` field
- [ ] Products have `price` field
- [ ] Products have `category` field
- [ ] Products are searchable by name

### Check Verifications Collection
```php
$verifications = $pdo->getAllDocuments('verifications');
echo count($verifications); // Can be 0 initially
```
- [ ] Verifications have `id` field
- [ ] Verifications reference `user_id`
- [ ] Verifications have `status` field
- [ ] Verifications have `id_image_path`
- [ ] Verifications have `submitted_at` timestamp

## Common Error Checks

### Check Server Logs
```bash
# In XAMPP, check: C:\xampp\apache\logs\error.log
# Or: C:\xampp\php\logs\php_errors.log
```
- [ ] No "Service account not found" errors
- [ ] No "Failed to get access token" errors
- [ ] No "Collection not found" errors
- [ ] No exception stack traces

### Check Browser Console (F12)
- [ ] No JavaScript errors
- [ ] No 404 errors for API calls
- [ ] Network requests to Google endpoints succeed
- [ ] No CORS errors

### Check Firestore Operations
When you perform an action (ban user, add product):
- [ ] No error popup appears
- [ ] Success message shows
- [ ] Check Firestore console - data is updated
- [ ] Refresh page - changes persist

## Performance Baseline

These are expected with Firestore REST API:

| Operation | Expected Time |
|-----------|---------------|
| Load users page | 1-3 seconds |
| Load dashboard | 2-4 seconds |
| Ban/unban user | 1-2 seconds |
| Add product | 1-2 seconds |
| Filter products | < 1 second (in-memory) |
| Search products | < 1 second (in-memory) |

- [ ] Pages load within expected timeframes
- [ ] No long loading times (> 5 seconds)
- [ ] No timeouts when fetching data

## Security Verification

- [ ] CSRF tokens present on all forms
- [ ] Ban/unban actions require CSRF token
- [ ] User input is sanitized with `sanitize()`
- [ ] Passwords are hashed (never stored plain)
- [ ] Error messages don't reveal sensitive info
- [ ] Admin-only pages check `requireLogin()`

## Documentation Review

- [ ] Read `FIRESTORE_INTEGRATION.md` for full guide
- [ ] Review `FIRESTORE_QUICK_REFERENCE.md` for syntax
- [ ] Check `FIRESTORE_EXAMPLES.md` for code patterns
- [ ] Understand `FIRESTORE_CONNECTION_SUMMARY.md` for overview

## Ready for Production?

Before deploying to production, ensure:

- [ ] All tests above pass ✓
- [ ] No errors in server logs ✓
- [ ] Data is correctly synced from Firestore ✓
- [ ] All CRUD operations work (Create, Read, Update, Delete) ✓
- [ ] Performance is acceptable ✓
- [ ] Security measures in place ✓
- [ ] Team trained on Firestore patterns ✓

## Troubleshooting Guide

### If Dashboard Shows No Statistics
1. Check if you have actual data in Firestore collections
2. Open browser DevTools (F12) → Console
3. Check for JavaScript errors
4. Check server logs for PHP errors
5. Verify credentials are correct

### If Pages Load Slowly
1. This is normal with Firestore - it requires internet
2. Try caching frequently accessed data
3. Limit the number of documents fetched
4. Check internet connection speed

### If Data Doesn't Update
1. Firestore may have eventual consistency (rare)
2. Press Ctrl+F5 to do a hard refresh
3. Check Firestore console to verify data was written
4. Wait 1-2 seconds and refresh

### If Ban/Add Product Doesn't Work
1. Check for error message on page
2. Check browser console for JavaScript errors
3. Check server error logs
4. Verify CSRF token is correct
5. Check Firestore document structure matches

## Next Steps After Verification

1. **Create Backup**
   - Backup your code
   - Backup Firestore data

2. **Set Up Monitoring**
   - Monitor error logs
   - Track response times
   - Monitor Firestore usage

3. **Train Team**
   - Share documentation
   - Show common patterns
   - Review examples together

4. **Plan Enhancements**
   - Add more features
   - Optimize queries
   - Implement caching

---

**Verification Date**: _______________  
**Verified By**: _______________  
**Status**: 🟢 Ready / 🟡 In Progress / 🔴 Issues Found  

**Notes**: 
