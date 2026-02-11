# Quick Start Guide

## 5-Minute Setup

### Step 1: Copy Files
Place the `admin/` folder in your web root:
```
/var/www/html/admin/
or
C:\xampp\htdocs\admin\
```

### Step 2: Create Database
Run this SQL in your MySQL client:

```sql
CREATE DATABASE IF NOT EXISTS delivery_app;
USE delivery_app;

-- Copy-paste the full schema from README.md
```

### Step 3: Update Database Credentials
Edit `includes/db.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_mysql_user');
define('DB_PASS', 'your_mysql_password');
define('DB_NAME', 'delivery_app');
```

### Step 4: Set Folder Permissions
```bash
chmod 755 uploads/
chmod 644 includes/*.php
```

### Step 5: Login
Navigate to: `http://localhost/admin/login.php`

**Default Credentials:**
- Username: `admin`
- Password: `password123`

---

## What's Included?

| File | Purpose |
|------|---------|
| `login.php` | Admin login page |
| `index.php` | Dashboard with statistics |
| `products.php` | Product management (CRUD) |
| `users.php` | User & driver management |
| `verifications.php` | ID verification approval |
| `completed-orders.php` | Order history & images |
| `includes/db.php` | Database connection |
| `includes/functions.php` | Helper functions |
| `includes/header.php` | Top navbar |
| `includes/sidebar.php` | Left navigation menu |
| `includes/footer.php` | Page footer & modals |

---

## Testing the Features

### 1. Dashboard
- View 6 statistic cards
- See latest completed orders
- All data pulls from database

### 2. Products
- Click "Add Product" to create new
- Upload product image
- Edit by clicking Edit button
- Delete with confirmation

### 3. Users & Drivers
- Filter by role (All/Customers/Drivers)
- Ban/unban users
- View user details

### 4. ID Verifications
- View pending verifications
- Click "View" to see ID images
- Approve or reject with reason

### 5. Completed Orders
- See all finished orders
- Click "View" for details
- See delivery proof images
- View order items list

---

## Database Schema Visualization

```
users (1) ──── (N) orders ──── (N) order_items ──── (1) products
                    │
                    ├──────── (N) order_images
                    │
                    └── (1) driver_id (users)

users (1) ──── (N) verifications
```

---

## Browser Support

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

Mobile responsive with Bootstrap 5.3 CDN.

---

## Security Checklist

Before going to production:

- [ ] Change demo credentials
- [ ] Set `display_errors = Off` in php.ini
- [ ] Enable HTTPS/SSL certificate
- [ ] Set database password (strong)
- [ ] Change database name from "delivery_app"
- [ ] Delete or restrict access to README.md
- [ ] Set proper file permissions (644 for files, 755 for folders)
- [ ] Configure firewall rules
- [ ] Enable query logging for audit trail

---

## Performance Tips

1. **Add Database Indexes**
   ```sql
   CREATE INDEX idx_user_id ON orders(user_id);
   CREATE INDEX idx_driver_id ON orders(driver_id);
   CREATE INDEX idx_created_at ON users(created_at);
   ```

2. **Enable Query Caching** in MySQL config

3. **Compress Images** before uploading

4. **Use CDN** for Bootstrap/Bootstrap Icons

5. **Minify** custom CSS/JS if added

---

## Common Issues & Solutions

**"Access Denied" on login**
- Check database user has SELECT/INSERT/UPDATE/DELETE on `delivery_app`
- Verify user is created in database

**Images not uploading**
- Check `/uploads` folder permissions (755)
- Check PHP `upload_max_filesize` (default 2MB)
- Check file format is JPG/PNG/GIF

**Sidebar not visible on mobile**
- Open mobile view in browser dev tools
- Click hamburger menu to toggle sidebar
- Check Bootstrap CDN is loading

**Session expires too fast**
- Edit `session.gc_maxlifetime` in php.ini
- Default is 1440 seconds (24 minutes)

---

## API Integration

To connect your delivery app:

### Create Order
```php
$stmt = $pdo->prepare('
    INSERT INTO orders (user_id, driver_id, total, address, status, payment_method, created_at)
    VALUES (?, ?, ?, ?, "pending", ?, NOW())
');
$stmt->execute([$userId, $driverId, $total, $address, $paymentMethod]);
```

### Add Order Item
```php
$stmt = $pdo->prepare('
    INSERT INTO order_items (order_id, product_id, quantity, price)
    VALUES (?, ?, ?, ?)
');
$stmt->execute([$orderId, $productId, $qty, $price]);
```

### Upload Delivery Proof
```php
$stmt = $pdo->prepare('
    INSERT INTO order_images (order_id, image_path, type, uploaded_at)
    VALUES (?, ?, ?, NOW())
');
$stmt->execute([$orderId, $imagePath, $type]);
```

### Submit Verification
```php
$stmt = $pdo->prepare('
    INSERT INTO verifications (user_id, id_type, front_image, back_image, selfie, submitted_at)
    VALUES (?, ?, ?, ?, ?, NOW())
');
$stmt->execute([$userId, $idType, $frontImg, $backImg, $selfie]);
```

---

## Adding New Features

### Add New Menu Item
Edit `includes/sidebar.php`:
```php
<a href="newpage.php" class="nav-item <?php echo $currentPage === 'newpage.php' ? 'active' : ''; ?>">
    <i class="bi bi-icon-name"></i>
    <span>New Page</span>
</a>
```

### Create New Page
```php
<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

requireLogin();

$pageTitle = 'New Page';

require_once 'includes/sidebar.php';
?>

<!-- Page content here -->

<?php require_once 'includes/footer.php'; ?>
```

---

## Next Steps

1. Customize colors in `includes/header.php`
2. Add your company logo to navbar
3. Create admin user accounts
4. Add sample products
5. Test all CRUD operations
6. Deploy to production server

**Documentation:** See README.md for detailed reference

**Issues?** Check README.md > Troubleshooting section
