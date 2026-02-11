# Developer Quick Reference

## File Locations & Purposes

| File | Purpose | Lines |
|------|---------|-------|
| `login.php` | Authentication & session start | ~170 |
| `index.php` | Dashboard with statistics | ~350 |
| `products.php` | Product CRUD with images | ~450 |
| `users.php` | User management & ban/unban | ~380 |
| `verifications.php` | ID verification approval | ~400 |
| `completed-orders.php` | Order history & image gallery | ~420 |
| `includes/db.php` | Database connection | ~30 |
| `includes/functions.php` | Helper utilities | ~200 |
| `includes/header.php` | Top navbar & page head | ~150 |
| `includes/sidebar.php` | Navigation sidebar | ~200 |
| `includes/footer.php` | Page footer & JS helpers | ~150 |

---

## Common Code Snippets

### Require Login
```php
require_once 'includes/db.php';
require_once 'includes/functions.php';
requireLogin();  // Redirect if not authenticated
```

### Database Query
```php
$stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
$stmt->execute([$productId]);
$product = $stmt->fetch();
```

### Get Multiple Records
```php
$stmt = $pdo->query('SELECT * FROM users WHERE role = "customer" ORDER BY created_at DESC');
$users = $stmt->fetchAll();
```

### Insert Record
```php
$stmt = $pdo->prepare('INSERT INTO products (name, price, created_at) VALUES (?, ?, NOW())');
$stmt->execute([$name, $price]);
$id = $pdo->lastInsertId();
```

### Update Record
```php
$stmt = $pdo->prepare('UPDATE users SET status = ? WHERE id = ?');
$stmt->execute(['active', $userId]);
```

### Delete Record
```php
$stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
$stmt->execute([$productId]);
```

### File Upload
```php
$imagePath = uploadFile($_FILES['image']);
if ($imagePath) {
    // File uploaded successfully
}
```

### Sanitize Input
```php
$name = sanitize($_POST['name']);  // Prevents XSS
$email = sanitize($_GET['email']);
```

### Check CSRF Token
```php
if (!verifyCSRFToken($_POST['csrf_token'])) {
    die('Invalid token');
}
```

### Generate CSRF Token in Form
```php
<input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
```

### Format Output
```php
echo formatCurrency($price);      // $9.99
echo formatDate($timestamp);      // Jan 15, 2024 10:30 AM
echo htmlspecialchars($text);     // XSS prevention
```

### Show Error Message
```php
if (!empty($error)) {
    echo '<div class="alert alert-danger">' . htmlspecialchars($error) . '</div>';
}
```

### Show Toast Notification (JavaScript)
```javascript
showToast('Operation successful!', 'success', 3000);
showToast('Error occurred', 'danger');
showToast('Warning message', 'warning');
```

### Show Confirmation Dialog
```javascript
confirmAction('Are you sure?', function() {
    // Do something
});
```

### Modal Form Submission
```php
<form method="POST">
    <input type="hidden" name="action" value="add">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    <!-- form fields -->
</form>
```

---

## Database Schema Quick Reference

### Users
```sql
SELECT * FROM users WHERE role = 'admin';
SELECT COUNT(*) FROM users WHERE status = 'active';
UPDATE users SET status = 'banned' WHERE id = ?;
```

### Products
```sql
SELECT * FROM products WHERE is_active = 1;
SELECT * FROM products WHERE category = ? ORDER BY name;
INSERT INTO products (name, price, category, stock, is_active) VALUES (?, ?, ?, ?, 1);
DELETE FROM products WHERE id = ?;
```

### Orders
```sql
SELECT * FROM orders WHERE status = 'completed' ORDER BY completed_at DESC;
SELECT SUM(total) FROM orders WHERE DATE(completed_at) = CURDATE();
SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE();
```

### Order Items
```sql
SELECT oi.*, p.name FROM order_items oi 
LEFT JOIN products p ON oi.product_id = p.id 
WHERE oi.order_id = ?;
```

### Order Images
```sql
SELECT * FROM order_images WHERE order_id = ?;
INSERT INTO order_images (order_id, image_path, type) VALUES (?, ?, ?);
```

### Verifications
```sql
SELECT * FROM verifications WHERE status = 'pending';
UPDATE verifications SET status = 'approved' WHERE id = ?;
UPDATE verifications SET status = 'rejected', admin_note = ? WHERE id = ?;
```

---

## Common Patterns

### Add New Page
1. Create `newpage.php`
2. Start with:
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
<!-- Content here -->
<?php require_once 'includes/footer.php'; ?>
```

3. Add menu item in `includes/sidebar.php`:
```php
<a href="newpage.php" class="nav-item">
    <i class="bi bi-icon-name"></i>
    <span>New Page</span>
</a>
```

### Form with Modal
```html
<!-- Button to open modal -->
<button data-bs-toggle="modal" data-bs-target="#myModal">Open</button>

<!-- Modal -->
<div class="modal fade" id="myModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Title</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <div class="modal-body">
                    <!-- Form fields -->
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Submit</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
```

### Data Table with Actions
```html
<table class="table">
    <thead class="bg-light">
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?php echo htmlspecialchars($user['username']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="viewUser(<?php echo $user['id']; ?>)">View</button>
                    <button class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $user['id']; ?>)">Delete</button>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
```

### Search & Filter
```php
// In page header
$search = sanitize($_GET['search'] ?? '');
$category = sanitize($_GET['category'] ?? '');

// Build query
$query = 'SELECT * FROM products WHERE 1=1';
$params = [];

if (!empty($search)) {
    $query .= ' AND (name LIKE ? OR description LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

if (!empty($category)) {
    $query .= ' AND category = ?';
    $params[] = $category;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$results = $stmt->fetchAll();
```

### Status Badge
```php
<?php if ($user['status'] === 'active'): ?>
    <span class="badge bg-success">Active</span>
<?php elseif ($user['status'] === 'banned'): ?>
    <span class="badge bg-danger">Banned</span>
<?php else: ?>
    <span class="badge bg-secondary">Inactive</span>
<?php endif; ?>
```

---

## Bootstrap Classes Reference

### Colors
```html
<div class="alert alert-primary">Primary</div>
<div class="alert alert-success">Success</div>
<div class="alert alert-danger">Danger</div>
<div class="alert alert-warning">Warning</div>
<div class="alert alert-info">Info</div>
```

### Buttons
```html
<button class="btn btn-primary">Primary</button>
<button class="btn btn-outline-primary">Outline</button>
<button class="btn btn-sm">Small</button>
<button class="btn btn-lg">Large</button>
```

### Cards
```html
<div class="card">
    <div class="card-header">Header</div>
    <div class="card-body">Content</div>
    <div class="card-footer">Footer</div>
</div>
```

### Grid
```html
<div class="row">
    <div class="col-md-6">50% on medium+</div>
    <div class="col-md-6">50% on medium+</div>
</div>
```

### Typography
```html
<h1>Heading 1</h1>
<p class="text-muted">Muted text</p>
<span class="badge bg-primary">Badge</span>
<small>Small text</small>
```

---

## JavaScript Reference

### Show Toast
```javascript
showToast('Success!', 'success');
showToast('Error!', 'danger');
showToast('Warning!', 'warning');
```

### Confirm Action
```javascript
confirmAction('Delete this?', function() {
    document.getElementById('deleteForm').submit();
});
```

### Fetch API
```javascript
fetch('page.php?action=getData&id=123')
    .then(r => r.json())
    .then(data => {
        console.log(data);
    })
    .catch(e => console.error(e));
```

### Form Submission
```javascript
document.getElementById('form').addEventListener('submit', function(e) {
    e.preventDefault();
    // Handle form
    this.submit();
});
```

---

## Debug Tips

### Check Database Connection
```php
<?php
try {
    require_once 'includes/db.php';
    echo "Connected!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
```

### Log SQL Queries
```php
$query = 'SELECT * FROM products WHERE id = ?';
error_log("Query: " . $query);
$stmt = $pdo->prepare($query);
$stmt->execute([$id]);
```

### Print Variable
```php
echo '<pre>';
var_dump($variable);
echo '</pre>';
```

### Check Session
```php
<?php
session_start();
echo '<pre>';
print_r($_SESSION);
echo '</pre>';
?>
```

---

## Performance Tips

1. **Use Prepared Statements** (prevents SQL injection)
2. **Add Database Indexes** on frequently queried columns
3. **Limit Results** with LIMIT clause
4. **Cache Database Calls** for repeated queries
5. **Compress Images** before uploading
6. **Minify CSS/JS** in production

---

## Security Checklist

- [ ] Use `sanitize()` for user input
- [ ] Use `htmlspecialchars()` in output
- [ ] Use prepared statements everywhere
- [ ] Verify CSRF tokens on POST
- [ ] Check `requireLogin()` on protected pages
- [ ] Validate file uploads
- [ ] Set proper permissions on `uploads/` folder
- [ ] Use `password_hash()` for passwords

---

## Common Errors & Solutions

| Error | Cause | Solution |
|-------|-------|----------|
| "Call to undefined function" | Missing require | Add `require_once 'path/to/file.php'` |
| "Database connection failed" | Wrong credentials | Check `includes/db.php` |
| "Undefined index" | Missing POST/GET variable | Use `$_POST['key'] ?? ''` |
| "File not uploaded" | Permission denied | `chmod 755 uploads/` |
| "Session already started" | Multiple `session_start()` | Remove duplicate session_start() |
| "Headers already sent" | Output before header() | Ensure no whitespace before `<?php` |

---

## Useful Links

- Bootstrap 5 Docs: https://getbootstrap.com/docs/5.0/
- Bootstrap Icons: https://icons.getbootstrap.com/
- PHP Manual: https://www.php.net/manual/
- MySQL Docs: https://dev.mysql.com/doc/
- PDO Tutorial: https://www.php.net/manual/en/book.pdo.php

---

**Happy coding! 🚀**
