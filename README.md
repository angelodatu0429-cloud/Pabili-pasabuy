# Delivery App Admin Panel

A complete, production-ready admin panel for managing a delivery application built with **PHP 8+**, **MySQL/MariaDB**, **Bootstrap 5.3**, and **vanilla JavaScript**.

## Features

✅ **Secure Admin Login** - Password hashing with `password_hash()` & `password_verify()`  
✅ **Responsive Sidebar** - Collapsible navigation, mobile-friendly  
✅ **Dashboard** - 6 statistic cards + recent orders table  
✅ **Products CRUD** - Full management with image uploads  
✅ **Users Management** - Filter by role, ban/unban functionality  
✅ **ID Verifications** - Approve/reject with image gallery and notes  
✅ **Completed Orders** - View orders with delivery proof images  
✅ **Session-based Security** - CSRF token validation  
✅ **Prepared Statements** - All queries use PDO prepared statements  
✅ **Bootstrap 5 Design** - Modern, clean UI with smooth interactions  

---

## Installation

### 1. Database Setup

Create the database and import the following tables:

```sql
-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    role ENUM('admin', 'customer', 'driver') DEFAULT 'customer',
    status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    category VARCHAR(100),
    image_path VARCHAR(255),
    stock INT DEFAULT 0,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category)
);

-- Orders table
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    driver_id INT,
    total DECIMAL(10, 2) NOT NULL,
    address TEXT,
    status ENUM('pending', 'accepted', 'in_transit', 'completed', 'cancelled') DEFAULT 'pending',
    payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    payment_method VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (driver_id) REFERENCES users(id),
    INDEX idx_status (status),
    INDEX idx_completed (completed_at)
);

-- Order items table
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT,
    quantity INT DEFAULT 1,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Order images (delivery proof)
CREATE TABLE order_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    type ENUM('delivered', 'receipt', 'location', 'signature', 'other') DEFAULT 'other',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Verifications table
CREATE TABLE verifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    id_type ENUM('driver_license', 'national_id', 'passport') NOT NULL,
    front_image VARCHAR(255),
    back_image VARCHAR(255),
    selfie VARCHAR(255),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    admin_note TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_status (status)
);

-- Insert demo admin user
-- Username: admin
-- Password: password123
INSERT INTO users (username, password_hash, email, phone, role, status) 
VALUES ('admin', '$2y$10$vvCOAzL9sPUGKZOB9vkNSOP0EzNONx.XOvfSTcWR2M0KqzePxSLhC', 'admin@delivery.app', '+1234567890', 'admin', 'active');
```

### 2. Configure Database Connection

Edit `includes/db.php` with your database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'delivery_app');
```

### 3. Create Uploads Folder

The `uploads/` folder is created automatically on first file upload. Make sure the web server has write permissions.

### 4. Set Up Web Server

Place the `admin/` folder in your web root (e.g., `/var/www/html/admin`).

Access the admin panel:
```
http://localhost/admin/login.php
```

**Demo Credentials:**
- Username: `admin`
- Password: `password123`

---

## Folder Structure

```
admin/
├── index.php               # Dashboard
├── login.php              # Login page
├── logout.php             # Logout handler
├── products.php           # Product CRUD
├── users.php              # User management
├── verifications.php      # ID verification approval
├── completed-orders.php   # Completed orders view
├── includes/
│   ├── db.php            # PDO database connection
│   ├── functions.php     # Helper functions
│   ├── header.php        # Top navbar
│   ├── sidebar.php       # Navigation sidebar
│   └── footer.php        # Page footer + JS helpers
├── uploads/              # User uploads (images)
└── assets/               # Custom CSS/JS (optional)
```

---

## Security Features

🔒 **Password Security**
- Passwords hashed with `password_hash()` (bcrypt)
- Verified with `password_verify()`
- Never stored in plain text

🔒 **CSRF Protection**
- Token generation and validation
- Tokens embedded in forms
- Verified on POST requests

🔒 **Prepared Statements**
- PDO prepared statements everywhere
- Protection against SQL injection
- Parameterized queries

🔒 **Session Management**
- Server-side sessions with `$_SESSION`
- Required login checks on all pages
- Logout clears all session data

🔒 **Input Sanitization**
- `sanitize()` function for user input
- `htmlspecialchars()` in output
- File upload validation (extension & type)

---

## API Endpoints (AJAX)

### Get Order Items
```
GET ?action=get_order_items&order_id=1
Response: JSON array of order items
```

### Get Delivery Images
```
GET ?action=get_delivery_images&order_id=1
Response: JSON array of images with types
```

---

## Key Files Explained

### `includes/db.php`
PDO database connection with error handling. Modify credentials here.

### `includes/functions.php`
Reusable utility functions:
- `requireLogin()` - Check if user is authenticated admin
- `sanitize()` - XSS prevention
- `uploadFile()` - Handle file uploads to `/uploads`
- `formatCurrency()` / `formatDate()` - Formatting helpers
- Statistics functions (dashboard data)

### `login.php`
Secure login with session management. Validates username/password and checks admin role.

### `index.php` (Dashboard)
Shows 6 stat cards and latest orders. All data comes from database functions.

### `products.php`
Complete CRUD:
- **Create**: Modal form for adding products
- **Read**: Table with search & category filter
- **Update**: Edit modal with image replacement
- **Delete**: Confirmation modal, cascades to delete images

### `verifications.php`
Approve/reject ID verifications with:
- Front/back/selfie images (side-by-side)
- Rejection reason textarea
- Status tracking (pending/approved/rejected)

### `completed-orders.php`
View completed orders with:
- Customer & driver info
- Order items list
- Delivery proof image gallery (click to enlarge)
- Total revenue calculations

### `users.php`
User management with:
- Tab/button filter by role (All/Customers/Drivers)
- Ban/unban actions
- User detail modal
- Status indicators

---

## Customization

### Change Primary Color
Edit the `:root` CSS variables in header.php:
```css
:root {
    --primary-color: #667eea;
    --secondary-color: #764ba2;
    --success-color: #10b981;
    --danger-color: #ef4444;
    --warning-color: #f59e0b;
}
```

### Add More Pages
1. Create `newpage.php`
2. Include header, sidebar, footer at top/bottom
3. Call `requireLogin()` after session start
4. Add nav item to sidebar

### Add More User Roles
1. Update `users` table enum
2. Modify `requireLogin()` function if needed
3. Update role checks in relevant pages

---

## Database Queries Reference

```php
// Get user by ID
$user = getUserById($pdo, $userId);

// Get product by ID
$product = getProductById($pdo, $productId);

// Get all products
$stmt = $pdo->query('SELECT * FROM products ORDER BY created_at DESC');
$products = $stmt->fetchAll();

// Search products
$stmt = $pdo->prepare('SELECT * FROM products WHERE name LIKE ? OR description LIKE ?');
$stmt->execute(['%search%', '%search%']);
$results = $stmt->fetchAll();

// Get pending verifications
$stmt = $pdo->query('SELECT * FROM verifications WHERE status = "pending"');
$pending = $stmt->fetchAll();

// Get completed orders for a date
$stmt = $pdo->prepare('SELECT * FROM orders WHERE DATE(completed_at) = ?');
$stmt->execute(['2024-01-15']);
$orders = $stmt->fetchAll();

// Get user statistics
$count = $pdo->query('SELECT COUNT(*) as count FROM users WHERE role = "customer"')->fetch()['count'];
```

---

## Troubleshooting

**Error: "Database connection failed"**
- Check MySQL service is running
- Verify credentials in `db.php`
- Ensure database exists

**Login shows "Invalid username or password"**
- Demo user might not exist, insert it manually
- Check if user role is "admin"
- Check if user status is "active"

**File uploads not working**
- Check `/uploads` folder permissions (should be 755)
- Check PHP `upload_max_filesize` and `post_max_size`
- Ensure image files are JPG/PNG/GIF

**Sidebar not showing on mobile**
- Check browser dev tools mobile responsive mode
- Sidebar should toggle with hamburger menu
- Ensure Bootstrap CDN is loading

---

## Best Practices Used

✅ Consistent naming conventions (snake_case for DB, camelCase for JS)  
✅ DRY principle (helper functions, reusable components)  
✅ Semantic HTML5 (proper form labels, fieldsets, etc.)  
✅ Progressive enhancement (works without JS)  
✅ Accessibility (ARIA labels, semantic structure)  
✅ Error handling (try-catch, user feedback)  
✅ Code comments (self-documenting code)  

---

## License

Free to use and modify for your projects.

---

## Support

For issues or questions, ensure:
1. PHP 8.0+ is installed
2. MySQL 5.7+ or MariaDB is running
3. Web server has write permissions for `/uploads`
4. Database tables are created with correct schema

---

**Happy coding! 🚀**
