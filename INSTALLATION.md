# 🚀 Installation Guide - Step by Step

## Prerequisites

- **PHP 8.0+** installed and running
- **MySQL 5.7+** or **MariaDB 10.2+**
- **Web Server** (Apache, Nginx, or PHP built-in server)
- **Localhost** or domain name to access the admin panel

---

## Installation Steps

### Step 1️⃣: Download/Copy Files

Copy the entire `admin/` folder to your web server's document root:

**For Apache (XAMPP/WAMP):**
```
C:\xampp\htdocs\admin\
or
C:/wamp/www/admin/
```

**For Linux/Mac (Apache):**
```
/var/www/html/admin/
or
/usr/local/var/www/admin/
```

**For Nginx:**
```
/var/www/html/admin/
or
/usr/share/nginx/html/admin/
```

---

### Step 2️⃣: Create Database

Open **phpMyAdmin** or MySQL client and run this:

```sql
CREATE DATABASE IF NOT EXISTS delivery_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Then import the database schema:

**Option A: Via phpMyAdmin**
1. Go to `http://localhost/phpmyadmin/`
2. Select **delivery_app** database
3. Click **Import**
4. Choose `database-schema.sql` from the admin folder
5. Click **Import**

**Option B: Via MySQL Command Line**
```bash
mysql -u root -p delivery_app < database-schema.sql
```

**Option C: Manually Run SQL**
1. Copy content of `database-schema.sql`
2. Open phpMyAdmin → New tab
3. Paste SQL and click **Execute**

---

### Step 3️⃣: Configure Database Connection

Edit the file: `admin/includes/db.php`

```php
<?php
define('DB_HOST', 'localhost');        // ← MySQL server
define('DB_USER', 'root');             // ← MySQL username
define('DB_PASS', '');                 // ← MySQL password (empty if no password)
define('DB_NAME', 'delivery_app');     // ← Database name
// ... rest of file
```

**Common Configurations:**

**XAMPP (Windows):**
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // XAMPP has no password by default
define('DB_NAME', 'delivery_app');
```

**WAMP (Windows):**
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // WAMP has no password by default
define('DB_NAME', 'delivery_app');
```

**Linux with Password:**
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'admin');
define('DB_PASS', 'your_password_here');
define('DB_NAME', 'delivery_app');
```

**Remote Server:**
```php
define('DB_HOST', '192.168.1.100');    // Server IP or domain
define('DB_USER', 'db_user');
define('DB_PASS', 'secure_password');
define('DB_NAME', 'delivery_app');
```

💾 **Save the file after editing!**

---

### Step 4️⃣: Set Folder Permissions

**Linux/Mac:**
```bash
cd admin/
chmod 755 uploads/
chmod 644 includes/*.php
chmod 644 *.php
```

**Windows (XAMPP):**
- Right-click `uploads` folder → Properties
- Check if "Read-only" is unchecked
- Apply to all files

**Via phpMyAdmin:**
- Admin should be able to write to `uploads/` folder

---

### Step 5️⃣: Start Services

**For XAMPP (Windows):**
1. Open XAMPP Control Panel
2. Start **Apache** (click Start next to Apache)
3. Start **MySQL** (click Start next to MySQL)

**For WAMP (Windows):**
1. Click WAMP icon in system tray
2. Ensure all services show green

**For Linux:**
```bash
sudo systemctl start apache2
sudo systemctl start mysql
```

**For Built-in PHP Server:**
```bash
cd admin/
php -S localhost:8000
```

---

### Step 6️⃣: Test Installation

Open your browser and navigate to:

```
http://localhost/admin/login.php
```

or if using PHP built-in server:

```
http://localhost:8000/login.php
```

You should see the **login page**.

---

## Login to Admin Panel

### Demo Credentials:
- **Username:** `admin`
- **Password:** `password123`

### After First Login:
1. ✅ Verify Dashboard loads with 0 statistics
2. ✅ Check Products page
3. ✅ Check Users & Drivers page
4. ✅ Check ID Verifications page
5. ✅ Check Completed Orders page

---

## Troubleshooting Installation

### "Database connection failed"

**Cause:** MySQL credentials are wrong or MySQL is not running

**Solution:**
1. Start MySQL service
2. Verify credentials in `includes/db.php`
3. Test connection:
```php
// Add this to db.php temporarily
echo "Host: " . DB_HOST . "<br>";
echo "User: " . DB_USER . "<br>";
echo "DB: " . DB_NAME . "<br>";
```

---

### "Access Denied for user 'root'@'localhost'"

**Cause:** MySQL password is incorrect

**Solution:**
1. Verify password in `includes/db.php`
2. Test MySQL connection:
```bash
mysql -h localhost -u root -p
```
3. If you forgot password, reset it (search "MySQL reset password")

---

### "Database 'delivery_app' doesn't exist"

**Cause:** Database not created

**Solution:**
1. Open phpMyAdmin
2. Click **New** → Create database
3. Name it `delivery_app`
4. Click **Create**
5. Select it and click **Import**
6. Choose `database-schema.sql`

---

### "File uploads not working"

**Cause:** `uploads/` folder doesn't have write permissions

**Solution:**

**Windows (XAMPP):**
- Right-click `uploads` folder → Properties
- Uncheck "Read-only"
- Click Apply

**Linux:**
```bash
chmod 755 uploads/
sudo chown www-data:www-data uploads/
```

---

### "White screen / page not loading"

**Cause:** PHP error or missing include files

**Solution:**
1. Check PHP error log:
```bash
# Linux
tail -f /var/log/apache2/error.log

# Windows XAMPP
C:\xampp\apache\logs\error.log
```

2. Enable error reporting in PHP:
   - Edit `php.ini`
   - Set `display_errors = On`
   - Restart Apache

3. Verify all include files exist:
   - ✓ `includes/db.php`
   - ✓ `includes/functions.php`
   - ✓ `includes/header.php`
   - ✓ `includes/sidebar.php`
   - ✓ `includes/footer.php`

---

### "Login says invalid username/password"

**Cause:** Admin user not created in database

**Solution:**
1. Go to phpMyAdmin
2. Select `delivery_app` database
3. Click `users` table
4. Click **Insert**
5. Add:
   - username: `admin`
   - password_hash: `$2y$10$vvCOAzL9sPUGKZOB9vkNSOP0EzNONx.XOvfSTcWR2M0KqzePxSLhC`
   - email: `admin@delivery.app`
   - role: `admin`
   - status: `active`

Or use the SQL:
```sql
INSERT INTO users (username, password_hash, email, role, status) 
VALUES ('admin', '$2y$10$vvCOAzL9sPUGKZOB9vkNSOP0EzNONx.XOvfSTcWR2M0KqzePxSLhC', 'admin@delivery.app', 'admin', 'active');
```

---

### "Sidebar not showing on mobile"

**Cause:** CSS not loading or Bootstrap CDN failed

**Solution:**
1. Open browser DevTools (F12)
2. Check Console for errors
3. Check if Bootstrap CDN is loading:
   - Go to Network tab
   - Refresh page
   - Look for Bootstrap CSS/JS files
4. If CDN not loading, check internet connection

---

## Post-Installation Setup

### 1. Change Admin Password

1. Login to admin panel
2. Go to phpMyAdmin
3. Select `users` table
4. Edit admin user
5. Change `password_hash` field:
```bash
# Generate new hash
php -r "echo password_hash('newpassword', PASSWORD_BCRYPT);"
```
6. Copy output and paste in database

### 2. Create Additional Admin Users

```sql
INSERT INTO users (username, password_hash, email, role, status) 
VALUES (
    'admin2',
    '$2y$10$vvCOAzL9sPUGKZOB9vkNSOP0EzNONx.XOvfSTcWR2M0KqzePxSLhC',  -- password123
    'admin2@delivery.app',
    'admin',
    'active'
);
```

### 3. Add Sample Products

1. Login to admin panel
2. Go to **Products**
3. Click **Add Product**
4. Fill in form and submit

Or via SQL:
```sql
INSERT INTO products (name, description, price, category, stock, is_active) VALUES
('Product Name', 'Description', 9.99, 'Category', 50, 1);
```

### 4. Set Up SSL/HTTPS

For production:
1. Get SSL certificate (Let's Encrypt = free)
2. Configure in Apache/Nginx
3. Uncomment HTTPS redirect in `.htaccess`:
```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 5. Enable Query Logging

In `includes/db.php`, add after connection:
```php
$pdo->query("SET GLOBAL log_queries_not_using_indexes=ON");
```

---

## Security Checklist

Before going LIVE:

- [ ] Change demo admin password
- [ ] Delete or restrict `database-schema.sql` file
- [ ] Delete or restrict `README.md`, `SETUP.md` files
- [ ] Set `display_errors = Off` in `php.ini`
- [ ] Enable HTTPS/SSL certificate
- [ ] Set strong database password
- [ ] Configure firewall rules
- [ ] Set proper file permissions
- [ ] Enable database backups
- [ ] Test all features thoroughly

---

## Verification Checklist

After installation, verify:

- [ ] Admin login works
- [ ] Dashboard shows 6 statistic cards
- [ ] Product CRUD works (create, read, update, delete)
- [ ] Image upload works for products
- [ ] Users filter works (All/Customers/Drivers)
- [ ] Ban/Unban functionality works
- [ ] ID Verification approve/reject works
- [ ] Completed orders display correctly
- [ ] Modals open and close properly
- [ ] Forms submit without errors
- [ ] Database updates when actions performed

---

## Getting Help

### Check Logs
```bash
# PHP Error Log
tail -f /var/log/php-fpm.log

# MySQL Error Log
tail -f /var/log/mysql/error.log

# Apache Error Log
tail -f /var/log/apache2/error.log
```

### Test Database Connection
```php
<?php
require_once 'includes/db.php';
echo "Connection successful!";
?>
```

### Enable Debug Mode
Add to top of any page:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

---

## Next Steps

1. ✅ Complete installation
2. ✅ Verify all pages work
3. ✅ Add sample data via Products page
4. ✅ Test file uploads
5. ✅ Set up SSL certificate
6. ✅ Configure email notifications (optional)
7. ✅ Set up database backups
8. ✅ Deploy to production

---

## File References

- **Database Schema:** `database-schema.sql`
- **Configuration:** `includes/db.php`
- **Documentation:** `README.md`, `SETUP.md`, `FILE_STRUCTURE.md`
- **Demo Credentials:** Username `admin`, Password `password123`

---

**Congratulations! Your admin panel is now installed and ready to use!** 🎉

Need help? Check the `README.md` or `FILE_STRUCTURE.md` for more information.
