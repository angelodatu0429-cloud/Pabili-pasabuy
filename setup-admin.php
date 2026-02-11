<?php
/**
 * Admin Account Setup Tool
 * Create the first admin account in Firestore
 * NOTE: Delete this file after creating your admin account!
 */

session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

$message = '';
$messageType = '';
$passwordHash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create' && isset($_POST['username']) && isset($_POST['password'])) {
        $username = sanitize($_POST['username']);
        $password = $_POST['password'];
        $email = sanitize($_POST['email'] ?? '');
        
        // Validate inputs
        if (strlen($username) < 3) {
            $message = 'Username must be at least 3 characters.';
            $messageType = 'danger';
        } elseif (strlen($password) < 6) {
            $message = 'Password must be at least 6 characters.';
            $messageType = 'danger';
        } else {
            try {
                // Hash password
                $passwordHash = password_hash($password, PASSWORD_BCRYPT);
                
                // Create admin document
                $adminData = [
                    'username' => $username,
                    'password_hash' => $passwordHash,
                    'email' => $email,
                    'status' => 'active',
                    'created_at' => new DateTime(),
                ];
                
                // Insert into 'admin' collection
                if ($pdo->insert('admin', $adminData)) {
                    $message = '✅ Admin account created successfully! You can now login with your credentials.';
                    $messageType = 'success';
                    $passwordHash = ''; // Clear display
                } else {
                    // Get more detailed error info from logs
                    $message = '❌ Error creating admin account. Check your Firestore "admin" collection exists and service account has write permissions.';
                    $messageType = 'danger';
                    error_log('Admin creation failed. Data: ' . json_encode($adminData));
                }
            } catch (Exception $e) {
                error_log('Admin setup error: ' . $e->getMessage());
                $message = '❌ Error: ' . htmlspecialchars($e->getMessage());
                $messageType = 'danger';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Account Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .setup-container {
            width: 100%;
            max-width: 500px;
            padding: 20px;
        }
        .setup-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        .setup-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .setup-header h1 {
            font-size: 1.8rem;
            margin: 0;
            font-weight: bold;
        }
        .setup-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }
        .form-control {
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            padding: 0.75rem;
            font-size: 1rem;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-create {
            width: 100%;
            padding: 0.75rem;
            font-weight: 600;
            border-radius: 8px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn-create:hover {
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
        }
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        .warning-box strong {
            color: #856404;
        }
        .info-box {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1.5rem;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="setup-card">
            <div class="setup-header">
                <h1><i class="bi bi-shield-lock"></i> Admin Setup</h1>
                <p>Create your first admin account</p>
            </div>
            
            <div class="card-body p-4">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="warning-box">
                    <strong><i class="bi bi-exclamation-triangle"></i> Important:</strong>
                    <br>Before using this form, make sure you've created a collection named <strong>"admin"</strong> in your Firestore database.
                </div>
                
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label" for="username">
                            <i class="bi bi-person"></i> Username
                        </label>
                        <input type="text" class="form-control" id="username" name="username" 
                               placeholder="Enter admin username" required minlength="3">
                        <small class="text-muted">Minimum 3 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="email">
                            <i class="bi bi-envelope"></i> Email (Optional)
                        </label>
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="admin@example.com">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="password">
                            <i class="bi bi-lock"></i> Password
                        </label>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Enter strong password" required minlength="6">
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                    
                    <input type="hidden" name="action" value="create">
                    
                    <button type="submit" class="btn btn-create">
                        <i class="bi bi-plus-circle"></i> Create Admin Account
                    </button>
                </form>
                
                <div class="info-box">
                    <strong><i class="bi bi-info-circle"></i> Setup Instructions:</strong>
                    <ol class="mb-0 mt-2">
                        <li>In Firebase Console, create a collection named <strong>"admin"</strong></li>
                        <li>Fill in your desired admin username and password above</li>
                        <li>Click "Create Admin Account"</li>
                        <li>Your account will be created with a secure password hash</li>
                        <li>Delete this file (setup-admin.php) from your server</li>
                        <li>Login at login.php with your credentials</li>
                    </ol>
                </div>
                
                <div class="info-box" style="margin-top: 1.5rem; border-color: #ff6b6b; background: #ffe0e0;">
                    <strong><i class="bi bi-exclamation-circle"></i> Troubleshooting:</strong>
                    <p class="mb-2 mt-2">If you get an error:</p>
                    <ol class="mb-0">
                        <li><strong>Check PHP logs:</strong> Look in <code>C:\xampp\apache\logs\error.log</code></li>
                        <li><strong>Verify collection exists:</strong> Go to Firebase Console → Firestore → Check "admin" collection exists</li>
                        <li><strong>Check permissions:</strong> Ensure service account has write access to Firestore</li>
                        <li><strong>Test connection:</strong> Try creating a document from another page first</li>
                    </ol>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <small class="text-white">
                <i class="bi bi-shield-check"></i> Secure Setup Tool
            </small>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
