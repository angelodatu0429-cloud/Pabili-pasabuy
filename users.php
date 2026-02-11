<?php
/**
 * Users & Drivers Management Page
 * Manage customers and drivers with ban/unban functionality
 */

session_start();

require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

// Check authentication
requireLogin();

$pageTitle = 'Users & Drivers';
$message = '';
$messageType = '';

// Handle user status changes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'ban' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $userId = sanitize($_POST['user_id'] ?? '');
        $collection = sanitize($_POST['collection'] ?? 'User');
        try {
            $pdo->update($collection, $userId, ['status' => 'banned']);
            // Also try to disable the Firebase Auth user (do not delete credentials)
            try {
                require_once __DIR__ . '/vendor/autoload.php';
                $serviceAccount = __DIR__ . '/config/pabili-pasabuy-firebase-adminsdk-fbsvc-7ea41bf672.json';
                if (file_exists($serviceAccount)) {
                    $factory = (new \Kreait\Firebase\Factory())->withServiceAccount($serviceAccount);
                    $auth = $factory->createAuth();
                    try {
                        // Attempt to disable by UID
                        $auth->updateUser($userId, ['disabled' => true]);
                    } catch (Exception $e) {
                        // If UID not found, try by email from document
                        try {
                            $doc = $pdo->getDocument($collection, $userId);
                            $email = $doc['email'] ?? null;
                            if ($email) {
                                $u = $auth->getUserByEmail($email);
                                $auth->updateUser($u->uid, ['disabled' => true]);
                            }
                        } catch (Exception $ex) {
                            error_log('Firebase Auth disable fallback error: ' . $ex->getMessage());
                        }
                    }
                }
            } catch (Exception $e) {
                error_log('Firebase SDK error (disable): ' . $e->getMessage());
            }

            $message = 'User banned successfully.';
            $messageType = 'success';
        } catch (Exception $e) {
            error_log('Error banning user: ' . $e->getMessage());
            $message = 'Error banning user.';
            $messageType = 'danger';
        }
    } elseif ($action === 'unban' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $userId = sanitize($_POST['user_id'] ?? '');
        $collection = sanitize($_POST['collection'] ?? 'User');
        try {
            $pdo->update($collection, $userId, ['status' => 'active']);
            // Attempt to re-enable Firebase Auth user
            try {
                require_once __DIR__ . '/vendor/autoload.php';
                $serviceAccount = __DIR__ . '/config/pabili-pasabuy-firebase-adminsdk-fbsvc-7ea41bf672.json';
                if (file_exists($serviceAccount)) {
                    $factory = (new \Kreait\Firebase\Factory())->withServiceAccount($serviceAccount);
                    $auth = $factory->createAuth();
                    try {
                        $auth->updateUser($userId, ['disabled' => false]);
                    } catch (Exception $e) {
                        try {
                            $doc = $pdo->getDocument($collection, $userId);
                            $email = $doc['email'] ?? null;
                            if ($email) {
                                $u = $auth->getUserByEmail($email);
                                $auth->updateUser($u->uid, ['disabled' => false]);
                            }
                        } catch (Exception $ex) {
                            error_log('Firebase Auth enable fallback error: ' . $ex->getMessage());
                        }
                    }
                }
            } catch (Exception $e) {
                error_log('Firebase SDK error (enable): ' . $e->getMessage());
            }

            $message = 'User unbanned successfully.';
            $messageType = 'success';
        } catch (Exception $e) {
            error_log('Error unbanning user: ' . $e->getMessage());
            $message = 'Error unbanning user.';
            $messageType = 'danger';
        }
    }
    elseif ($action === 'delete' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $userId = sanitize($_POST['user_id'] ?? '');
        $collection = sanitize($_POST['collection'] ?? 'User');
        try {
            $deleted = $pdo->delete($collection, $userId);
            if ($deleted) {
                // Also attempt to delete the user from Firebase Authentication (if UID matches)
                try {
                    require_once __DIR__ . '/vendor/autoload.php';
                    $serviceAccount = __DIR__ . '/config/pabili-pasabuy-firebase-adminsdk-fbsvc-7ea41bf672.json';
                    if (file_exists($serviceAccount)) {
                        $factory = (new \Kreait\Firebase\Factory())->withServiceAccount($serviceAccount);
                        $auth = $factory->createAuth();
                        // Try delete by uid
                        try {
                            $auth->deleteUser($userId);
                        } catch (\Kreait\Firebase\Exception\Auth\UserNotFound $e) {
                            // If user not found by uid, try deleting by email if available
                            // fetch document to get email
                            $doc = $pdo->getDocument($collection, $userId);
                            $email = $doc['email'] ?? null;
                            if ($email) {
                                try {
                                    $u = $auth->getUserByEmail($email);
                                    $auth->deleteUser($u->uid);
                                } catch (Exception $ex) {
                                    // ignore
                                }
                            }
                        } catch (Exception $e) {
                            // Log but do not fail the overall deletion
                            error_log('Firebase Auth delete error: ' . $e->getMessage());
                        }
                    }
                } catch (Exception $e) {
                    error_log('Firebase SDK error: ' . $e->getMessage());
                }

                $message = 'User deleted successfully.';
                $messageType = 'success';
            } else {
                $message = 'Failed to delete user.';
                $messageType = 'danger';
            }
        } catch (Exception $e) {
            error_log('Error deleting user: ' . $e->getMessage());
            $message = 'Error deleting user.';
            $messageType = 'danger';
        }
    }
}

// Get filter
$filterRole = sanitize($_GET['role'] ?? '');

// Initialize variables
$allUsers = [];
$allRiders = [];
$users = [];
$roleCounts = ['customer' => 0, 'rider' => 0];

// Fetch all users from Firestore - Users and Riders collections
try {
    $allUsers = $pdo->getAllDocuments('Users') ?? [];
    $allRiders = $pdo->getAllDocuments('Riders') ?? [];
    
    // Add collection indicator, role, and normalize common fields
    $normalize = function($item, $collection, $role) {
        $item['_collection'] = $collection;
        $item['_role'] = $role;

        // Normalize name/username variations
        if (empty($item['username'])) {
            $item['username'] = $item['name'] ?? $item['full_name'] ?? $item['displayName'] ?? $item['username'] ?? '';
        }

        // Normalize email variations
        if (empty($item['email'])) {
            $item['email'] = $item['emailAddress'] ?? $item['email'] ?? '';
        }

        // Normalize phone/mobile (check multiple common variants)
        if (empty($item['phone'])) {
            $phoneKeys = ['phone', 'mobile', 'mobileNumber', 'mobile_no', 'phoneNumber', 'contact', 'contact_number', 'telephone', 'msisdn'];
            $foundPhone = '';
            foreach ($phoneKeys as $k) {
                if (!empty($item[$k])) { $foundPhone = $item[$k]; break; }
            }
            $item['phone'] = $foundPhone;
        }

        // Normalize address variants
        if (empty($item['address'])) {
            $item['address'] = $item['location'] ?? $item['home_address'] ?? $item['address'] ?? '';
        }

        // Normalize status: handle booleans and common variants
        $status = null;
        if (isset($item['status'])) {
            $status = $item['status'];
        } elseif (isset($item['account_status'])) {
            $status = $item['account_status'];
        } elseif (isset($item['is_active'])) {
            $status = $item['is_active'];
        } elseif (isset($item['active'])) {
            $status = $item['active'];
        } elseif (isset($item['banned'])) {
            $status = $item['banned'] ? 'banned' : 'active';
        }

        // Convert booleans and truthy values
        if (is_bool($status)) {
            $item['status'] = $status ? 'active' : 'inactive';
        } elseif (is_numeric($status)) {
            $item['status'] = ($status == 1) ? 'active' : 'inactive';
        } elseif (is_string($status)) {
            $lower = strtolower($status);
            if (in_array($lower, ['active', 'activated', 'enabled', 'true', 'yes'])) {
                $item['status'] = 'active';
            } elseif (in_array($lower, ['banned', 'blocked', 'suspended', 'disabled'])) {
                $item['status'] = 'banned';
            } else {
                $item['status'] = $lower;
            }
        } else {
            $item['status'] = 'inactive';
        }

        // Normalize created_at: keep DateTime if already one, else try parsing
        if (isset($item['created_at']) && !($item['created_at'] instanceof DateTime)) {
            try {
                $item['created_at'] = new DateTime($item['created_at']);
            } catch (Exception $e) {
                $item['created_at'] = null;
            }
        }

        return $item;
    };

    foreach ($allUsers as &$user) {
        $user = $normalize($user, 'Users', 'customer');
    }
    foreach ($allRiders as &$rider) {
        $rider = $normalize($rider, 'Riders', 'rider');
    }
    unset($user, $rider);

    $users = array_merge($allUsers, $allRiders);
    
    // Apply filter if specified
    if (!empty($filterRole)) {
        $users = array_filter($users, function($u) use ($filterRole) {
            return $u['_role'] === $filterRole;
        });
    }
    
    // Sort by collection then by created_at descending
    usort($users, function($a, $b) {
        $aTime = 0;
        $bTime = 0;
        
        if (isset($a['created_at'])) {
            $aTime = $a['created_at'] instanceof DateTime ? $a['created_at']->getTimestamp() : strtotime($a['created_at']);
        }
        if (isset($b['created_at'])) {
            $bTime = $b['created_at'] instanceof DateTime ? $b['created_at']->getTimestamp() : strtotime($b['created_at']);
        }
        
        return $bTime - $aTime; // Descending order
    });
    
    // Count by collection
    $roleCounts['customer'] = count($allUsers);
    $roleCounts['rider'] = count($allRiders);
} catch (Exception $e) {
    error_log('Error fetching users: ' . $e->getMessage());
}

$csrf_token = generateCSRFToken();

require_once 'includes/sidebar.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1><i class="bi bi-people-fill"></i> Users & Drivers</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active">Users & Drivers</li>
        </ol>
    </nav>
</div>

<!-- Main Content -->
<div class="main-container">
    <!-- Messages -->
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filter Buttons -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="btn-group" role="group">
                <a href="?role=" class="btn btn-outline-primary <?php echo empty($filterRole) ? 'active' : ''; ?>">
                    <i class="bi bi-people"></i> All Users
                    <span class="badge bg-primary ms-2">
                        <?php echo count($allUsers) + count($allRiders); ?>
                    </span>
                </a>
                <a href="?role=customer" class="btn btn-outline-primary <?php echo $filterRole === 'customer' ? 'active' : ''; ?>">
                    <i class="bi bi-person"></i> Customers
                    <span class="badge bg-primary ms-2">
                        <?php echo $roleCounts['customer'] ?? 0; ?>
                    </span>
                </a>
                <a href="?role=rider" class="btn btn-outline-primary <?php echo $filterRole === 'rider' ? 'active' : ''; ?>">
                    <i class="bi bi-truck"></i> Riders
                    <span class="badge bg-primary ms-2">
                        <?php echo $roleCounts['rider'] ?? 0; ?>
                    </span>
                </a>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">
                <i class="bi bi-list"></i> User List
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (!empty($users)): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Address</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div style="width: 40px; height: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                                <?php echo strtoupper(substr($user['username'] ?? 'U', 0, 1)); ?>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($user['username'] ?? 'N/A'); ?></strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($user['address'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if (($user['_role'] ?? '') === 'customer'): ?>
                                            <span class="badge bg-info"><i class="bi bi-person"></i> Customer</span>
                                        <?php elseif (($user['_role'] ?? '') === 'rider'): ?>
                                            <span class="badge bg-warning"><i class="bi bi-truck"></i> Rider</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars(ucfirst($user['_role'] ?? 'Unknown')); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (($user['status'] ?? '') === 'active'): ?>
                                            <span class="badge bg-success"><i class="bi bi-check-circle"></i> Active</span>
                                        <?php elseif (($user['status'] ?? '') === 'banned'): ?>
                                            <span class="badge bg-danger"><i class="bi bi-ban"></i> Banned</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars(ucfirst($user['status'] ?? 'Unknown')); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td>
                                        <?php
                                            $jsUser = [
                                                'id' => $user['id'] ?? '',
                                                'username' => $user['username'] ?? '',
                                                'email' => $user['email'] ?? '',
                                                'phone' => $user['phone'] ?? '',
                                                'address' => $user['address'] ?? '',
                                                '_role' => $user['_role'] ?? '',
                                                'status' => $user['status'] ?? '',
                                                '_collection' => $user['_collection'] ?? '',
                                            ];
                                        ?>
                                        <button class="btn btn-sm btn-outline-primary"
                                                onclick="viewUser(<?php echo htmlspecialchars(json_encode($jsUser, JSON_UNESCAPED_UNICODE)); ?>)"
                                                data-bs-toggle="modal"
                                                data-bs-target="#userModal">
                                            <i class="bi bi-eye"></i> View
                                        </button>

                                        <?php if (($user['status'] ?? '') !== 'banned'): ?>
                                            <button class="btn btn-sm btn-outline-danger"
                                                    onclick="banUser('<?php echo htmlspecialchars($user['id'] ?? ''); ?>', '<?php echo htmlspecialchars($user['username'] ?? ''); ?>', '<?php echo htmlspecialchars($user['_collection'] ?? ''); ?>')">
                                                <i class="bi bi-ban"></i> Ban
                                            </button>
                                        <?php elseif (($user['status'] ?? '') === 'banned'): ?>
                                            <button class="btn btn-sm btn-outline-success"
                                                    onclick="unbanUser('<?php echo htmlspecialchars($user['id'] ?? ''); ?>', '<?php echo htmlspecialchars($user['username'] ?? ''); ?>', '<?php echo htmlspecialchars($user['_collection'] ?? ''); ?>')">
                                                <i class="bi bi-check-circle"></i> Unban
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-outline-danger ms-1"
                                                onclick="deleteUser('<?php echo htmlspecialchars($user['id'] ?? ''); ?>', '<?php echo htmlspecialchars($user['username'] ?? ''); ?>', '<?php echo htmlspecialchars($user['_collection'] ?? ''); ?>')">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info m-3 mb-0">
                    <i class="bi bi-info-circle"></i> No users found.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- User Details Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">User Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="text-center mb-4">
                    <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; margin: 0 auto; display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem; font-weight: bold;" id="userInitials"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted">Username</label>
                    <p class="form-text fs-5" id="detailUsername"></p>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted">Email</label>
                    <p class="form-text fs-5" id="detailEmail"></p>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted">Phone</label>
                    <p class="form-text fs-5" id="detailPhone"></p>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted">Address</label>
                    <p class="form-text fs-5" id="detailAddress"></p>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted">Role</label>
                    <p class="form-text fs-5" id="detailRole"></p>
                </div>
            </div>

            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-danger d-none" id="modalBanBtn">Ban</button>
                <button type="button" class="btn btn-success d-none" id="modalUnbanBtn">Unban</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Ban/Unban Form (Hidden) -->
<form id="actionForm" method="POST" style="display: none;">
    <input type="hidden" name="action" id="actionType">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
    <input type="hidden" name="user_id" id="actionUserId">
    <input type="hidden" name="collection" id="actionCollection">
</form>

<style>
    .btn-group .btn {
        border-radius: 8px;
    }

    .badge {
        font-weight: 500;
    }

    .form-label {
        font-weight: 600;
        color: #6b7280;
    }

    .form-text {
        color: #1f2937;
    }
</style>

<script>
    function viewUser(user) {
        const username = user.username ?? 'N/A';
        document.getElementById('userInitials').textContent = ((user.username ?? 'U').charAt(0)).toUpperCase();
        document.getElementById('detailUsername').textContent = username;
        document.getElementById('detailEmail').textContent = user.email ?? 'N/A';
        document.getElementById('detailPhone').textContent = user.phone ?? 'N/A';
        document.getElementById('detailAddress').textContent = user.address ?? 'N/A';

        const role = user._role ?? 'unknown';
        const roleText = role === 'customer' ? 'Customer' : role === 'rider' ? 'Rider' : (role.charAt(0).toUpperCase() + role.slice(1));
        document.getElementById('detailRole').innerHTML = `<span class="badge ${role === 'customer' ? 'bg-info' : 'bg-warning'}">${roleText}</span>`;

        // Modal-level Ban/Unban buttons
        const modalBanBtn = document.getElementById('modalBanBtn');
        const modalUnbanBtn = document.getElementById('modalUnbanBtn');
        if (modalBanBtn && modalUnbanBtn) {
            // Clear previous handlers
            modalBanBtn.onclick = null;
            modalUnbanBtn.onclick = null;

            const status = user.status ?? 'inactive';
            if (status === 'banned') {
                modalUnbanBtn.classList.remove('d-none');
                modalBanBtn.classList.add('d-none');
                modalUnbanBtn.onclick = function() { unbanUser(user.id ?? '', user.username ?? '', user._collection ?? ''); };
            } else {
                // show Ban for any non-banned status (including inactive)
                modalBanBtn.classList.remove('d-none');
                modalUnbanBtn.classList.add('d-none');
                modalBanBtn.onclick = function() { banUser(user.id ?? '', user.username ?? '', user._collection ?? ''); };
            }
        }
    }

    function banUser(userId, userName, collection) {
        confirmAction(`Are you sure you want to ban "${userName}"? They will not be able to use the app.`, function() {
            document.getElementById('actionType').value = 'ban';
            document.getElementById('actionUserId').value = userId;
            document.getElementById('actionCollection').value = collection;
            document.getElementById('actionForm').submit();
        });
    }

    function unbanUser(userId, userName, collection) {
        confirmAction(`Are you sure you want to unban "${userName}"? They will be able to use the app again.`, function() {
            document.getElementById('actionType').value = 'unban';
            document.getElementById('actionUserId').value = userId;
            document.getElementById('actionCollection').value = collection;
            document.getElementById('actionForm').submit();
        });
    }

    function deleteUser(userId, userName, collection) {
        confirmAction(`Are you sure you want to delete "${userName}"? This action cannot be undone.`, function() {
            document.getElementById('actionType').value = 'delete';
            document.getElementById('actionUserId').value = userId;
            document.getElementById('actionCollection').value = collection;
            document.getElementById('actionForm').submit();
        });
    }
</script>

<?php require_once 'includes/footer.php'; ?>
