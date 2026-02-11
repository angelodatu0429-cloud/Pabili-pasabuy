# File Structure & Documentation

## Complete Directory Layout

```
admin/
│
├── 📄 index.php                    # Dashboard (statistics & recent orders)
├── 📄 login.php                    # Admin login page
├── 📄 logout.php                   # Session destruction
├── 📄 products.php                 # Product CRUD management
├── 📄 users.php                    # User & driver management
├── 📄 verifications.php            # ID verification approval system
├── 📄 completed-orders.php         # Completed orders with image gallery
│
├── 📁 includes/
│   ├── 📄 db.php                   # PDO database connection (EDIT WITH YOUR CREDENTIALS)
│   ├── 📄 functions.php            # Helper functions & utilities
│   ├── 📄 header.php               # Top navbar component
│   ├── 📄 sidebar.php              # Left navigation sidebar
│   └── 📄 footer.php               # Page footer & JS helpers
│
├── 📁 uploads/                     # User uploads folder (images)
│   └── 📄 .gitkeep                 # Ensures folder is tracked in git
│
├── 📁 assets/                      # Custom CSS/JS (if needed)
│
├── 📄 .htaccess                    # Apache security & optimization
├── 📄 .gitignore                   # Git ignore rules
├── 📄 README.md                    # Complete documentation
└── 📄 SETUP.md                     # Quick start guide

```

---

## File Descriptions

### Core Pages

#### `index.php` (Dashboard)
**Purpose:** Main dashboard showing admin overview  
**Features:**
- 6 statistic cards (Orders, Revenue, Drivers, Verifications, New Users, Total Completed)
- Latest completed orders table
- Links to other sections
- Database queries for real-time data

**Database Tables Used:** `orders`, `users`, `verifications`

---

#### `login.php` (Authentication)
**Purpose:** Admin login page  
**Features:**
- Username & password form
- CSRF token protection
- Password verification with `password_verify()`
- Session creation
- Role-based access control

**Database Tables Used:** `users`  
**Key Functions:** `generateCSRFToken()`, `verifyCSRFToken()`

---

#### `logout.php` (Session Termination)
**Purpose:** Destroy admin session and redirect  
**Features:**
- Session destruction
- Redirect to login page
- No user input (safe endpoint)

---

#### `products.php` (Product Management)
**Purpose:** Full CRUD for products  
**Features:**
- **C**reate: Modal form with image upload
- **R**ead: Searchable, filterable table
- **U**pdate: Edit modal with image replacement
- **D**elete: Confirmation modal

**Database Tables Used:** `products`  
**Upload Location:** `uploads/` folder  
**Key Functions:** `uploadFile()`, `deleteFile()`

**Form Fields:**
- Product Name (required)
- Description
- Price (required)
- Category (required)
- Stock Quantity
- Product Image (JPG/PNG/GIF)
- Active Checkbox

---

#### `users.php` (User Management)
**Purpose:** Manage customers and drivers  
**Features:**
- Filter by role (All/Customers/Drivers)
- Ban/unban functionality
- User detail modal
- Status indicators (Active/Banned/Inactive)

**Database Tables Used:** `users`  
**Key Functions:** `requireLogin()`

**Actions:**
- View user details
- Ban active users
- Unban banned users

---

#### `verifications.php` (ID Verification)
**Purpose:** Approve/reject user ID verifications  
**Features:**
- Pending verification list
- Image viewer (front/back/selfie)
- Approve action (instant)
- Reject action with reason textarea
- Status tracking

**Database Tables Used:** `verifications`, `users`  
**Image Types:** Front ID, Back ID, Selfie

**Workflow:**
1. User submits ID (stored in DB)
2. Admin views verification
3. Admin clicks Approve (status = approved) OR
4. Admin enters rejection reason & clicks Reject (status = rejected)

---

#### `completed-orders.php` (Order History)
**Purpose:** View completed orders with full details  
**Features:**
- Orders statistics (count, revenue, average)
- Searchable order table
- Order detail modal with:
  - Customer info
  - Driver info
  - Order items list
  - Delivery proof image gallery
  - Payment method badge

**Database Tables Used:** `orders`, `users`, `order_items`, `order_images`

**Image Gallery:**
- Click image to enlarge
- Image type labels (Delivered, Receipt, Location, etc.)
- Responsive grid layout

---

### Include Files (Components)

#### `includes/db.php` (Database Connection)
**Purpose:** PDO database connection  
**Config Variables:**
- `DB_HOST` - MySQL server address
- `DB_USER` - MySQL username
- `DB_PASS` - MySQL password
- `DB_NAME` - Database name

**Error Handling:** Displays connection error and dies

**Fetch Mode:** `PDO::FETCH_ASSOC` (returns associative arrays)

⚠️ **IMPORTANT:** Edit this file with your database credentials before first use!

---

#### `includes/functions.php` (Utility Functions)
**Authentication & Security:**
- `requireLogin()` - Redirect if not authenticated
- `generateCSRFToken()` - Create session CSRF token
- `verifyCSRFToken()` - Validate CSRF token
- `sanitize()` - XSS prevention (htmlspecialchars)

**Data Validation:**
- `isValidEmail()` - Email format validation

**Database Helpers:**
- `getUserById($pdo, $id)` - Fetch user
- `getProductById($pdo, $id)` - Fetch product
- `getPendingVerificationsCount($pdo)` - Count pending
- `getActiveDriversCount($pdo)` - Count active drivers
- `getTodayOrdersCount($pdo)` - Today's order count
- `getTodayRevenue($pdo)` - Today's revenue
- `getNewUsersThisWeek($pdo)` - New user count
- `getTotalCompletedOrders($pdo)` - Completed order count

**File Upload:**
- `uploadFile($file, $extensions)` - Upload & validate file
- `deleteFile($filepath)` - Delete file from server
- `getFileExtension($filename)` - Get file extension

**Formatting:**
- `formatCurrency($amount)` - Format as currency
- `formatDate($date)` - Format date/time

---

#### `includes/header.php` (Top Navigation)
**Purpose:** Navbar component  
**Features:**
- Logo & branding
- User profile dropdown
- Logout button
- Responsive on mobile
- CSS styling for all layout

**Includes:**
- Bootstrap 5.3 CDN
- Bootstrap Icons CDN
- Custom CSS for theme

---

#### `includes/sidebar.php` (Navigation Menu)
**Purpose:** Left sidebar navigation  
**Features:**
- Links to all main pages
- Active page highlighting
- Responsive (collapses on mobile)
- Hamburger menu toggle on mobile
- Badge for pending verifications count

**Menu Items:**
- Dashboard
- Products
- Users & Drivers
- ID Verifications (with pending count badge)
- Completed Orders
- Logout

**JavaScript Features:**
- Toggle sidebar on mobile
- Close sidebar when clicking menu item
- Close sidebar when clicking outside

---

#### `includes/footer.php` (Page Footer & Utilities)
**Purpose:** Closes layout & provides JS helpers  
**Features:**
- Toast notification system
- Confirmation modal helper
- Button disable during action
- Bootstrap 5 JS bundle

**JavaScript Functions:**
- `showToast(message, type, duration)` - Show notification
- `confirmAction(message, callback)` - Confirmation dialog
- `disableButtonDuring(btn, duration)` - Disable button temporarily

---

### Configuration Files

#### `.htaccess` (Apache Configuration)
**Features:**
- GZIP compression
- Browser caching headers
- Prevent direct file access
- Disable directory listing
- Security headers (X-Frame-Options, X-Content-Type-Options)

---

#### `.gitignore` (Git Ignore Rules)
**Ignored:**
- `uploads/*` - User uploaded files
- `config.php` - Sensitive configuration
- `.env` - Environment variables
- IDE folders (.vscode, .idea)
- OS files (.DS_Store, Thumbs.db)
- Temporary files (*.log, *.bak, *.tmp)

---

### Documentation Files

#### `README.md` (Complete Guide)
Comprehensive documentation covering:
- Features overview
- Installation & setup
- Database schema
- Security features
- API endpoints
- File explanations
- Customization guide
- Troubleshooting
- Best practices

#### `SETUP.md` (Quick Start)
Quick 5-minute setup guide covering:
- File placement
- Database creation
- Configuration
- Testing features
- Common issues
- Performance tips
- API integration examples

---

## Database Schema Reference

### Users Table
```sql
id              INT PRIMARY KEY
username        VARCHAR(100) UNIQUE
password_hash   VARCHAR(255)
email           VARCHAR(100)
phone           VARCHAR(20)
role            ENUM('admin', 'customer', 'driver')
status          ENUM('active', 'inactive', 'banned')
created_at      TIMESTAMP
```

### Products Table
```sql
id              INT PRIMARY KEY
name            VARCHAR(255)
description     TEXT
price           DECIMAL(10,2)
category        VARCHAR(100)
image_path      VARCHAR(255)
stock           INT
is_active       BOOLEAN
created_at      TIMESTAMP
```

### Orders Table
```sql
id              INT PRIMARY KEY
user_id         INT (FK → users)
driver_id       INT (FK → users)
total           DECIMAL(10,2)
address         TEXT
status          ENUM('pending', 'accepted', 'in_transit', 'completed', 'cancelled')
payment_status  ENUM('pending', 'completed', 'failed')
payment_method  VARCHAR(50)
created_at      TIMESTAMP
completed_at    TIMESTAMP
```

### Order Items Table
```sql
id              INT PRIMARY KEY
order_id        INT (FK → orders)
product_id      INT (FK → products)
quantity        INT
price           DECIMAL(10,2)
```

### Order Images Table
```sql
id              INT PRIMARY KEY
order_id        INT (FK → orders)
image_path      VARCHAR(255)
type            ENUM('delivered', 'receipt', 'location', 'signature', 'other')
uploaded_at     TIMESTAMP
```

### Verifications Table
```sql
id              INT PRIMARY KEY
user_id         INT (FK → users)
id_type         ENUM('driver_license', 'national_id', 'passport')
front_image     VARCHAR(255)
back_image      VARCHAR(255)
selfie          VARCHAR(255)
status          ENUM('pending', 'approved', 'rejected')
submitted_at    TIMESTAMP
reviewed_at     TIMESTAMP
admin_note      TEXT
```

---

## Key Features by Page

| Feature | Page | Tables | Security |
|---------|------|--------|----------|
| View Stats | index.php | orders, users, verifications | ✓ Auth required |
| Login | login.php | users | ✓ Password verified |
| Product CRUD | products.php | products | ✓ CSRF, XSS prevention |
| User Ban/Unban | users.php | users | ✓ CSRF token |
| Verify IDs | verifications.php | verifications | ✓ CSRF token |
| View Orders | completed-orders.php | orders, users, order_items, order_images | ✓ Auth required |

---

## Technology Stack

- **Backend:** PHP 8.0+
- **Database:** MySQL 5.7+ / MariaDB
- **Frontend:** HTML5, CSS3, Bootstrap 5.3
- **Icons:** Bootstrap Icons 1.11
- **JavaScript:** Vanilla JS (no frameworks)
- **Security:** PDO Prepared Statements, CSRF tokens, password_hash()

---

## Deployment Checklist

- [ ] Change database credentials
- [ ] Create database and import schema
- [ ] Set folder permissions (uploads: 755)
- [ ] Remove or restrict SETUP.md access
- [ ] Change demo admin password
- [ ] Enable HTTPS/SSL
- [ ] Configure firewall rules
- [ ] Set up database backups
- [ ] Enable query logging
- [ ] Test all CRUD operations
- [ ] Test login/logout
- [ ] Test image uploads
- [ ] Mobile responsive testing
- [ ] Browser compatibility testing

---

## File Modification Guide

**Easy to Modify:**
- Colors in `:root` CSS in header.php
- Menu items in sidebar.php
- Database queries in functions.php
- Form fields in products.php

**Advanced Modifications:**
- Add new database tables
- Create new pages with same structure
- Add custom JS/CSS files
- Integrate with external APIs
- Add email notifications

**Never Modify Without Care:**
- PDO connection logic
- Session handling
- Password hashing

---

## File Sizes (Approximate)

- `index.php` - 7 KB
- `products.php` - 12 KB
- `verifications.php` - 14 KB
- `completed-orders.php` - 13 KB
- `users.php` - 10 KB
- `includes/*.php` - 15 KB total
- `login.php` - 5 KB
- **Total Code:** ~76 KB (very lightweight!)

---

This admin panel is **production-ready**, **secure**, and **easy to maintain**. Good luck! 🚀
