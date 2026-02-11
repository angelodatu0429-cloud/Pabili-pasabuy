<?php
/**
 * Debug: Show ALL fields in Users and Riders documents
 */
session_start();

require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

requireLogin();

$allUsers = $pdo->getAllDocuments('Users') ?? [];
$allRiders = $pdo->getAllDocuments('Riders') ?? [];

?>
<div class="main-container">
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h5>Debug: ALL Fields in Users & Riders</h5>
            <p class="text-muted">Complete dump of all documents and their fields to locate ID verification data.</p>
        </div>
    </div>

    <!-- Users -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light"><strong>Users (<?php echo count($allUsers); ?> documents)</strong></div>
        <div class="card-body">
            <?php foreach ($allUsers as $idx => $user): ?>
                <div class="card mb-3 border">
                    <div class="card-header bg-light"><strong>User #<?php echo $idx + 1; ?></strong> (ID: <?php echo htmlspecialchars($user['id']); ?>)</div>
                    <div class="card-body">
                        <table class="table table-sm table-bordered">
                            <thead><tr><th style="width:30%">Field</th><th>Value (truncated)</th></tr></thead>
                            <tbody>
                                <?php foreach ($user as $key => $val): ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($key); ?></code></td>
                                        <td>
                                            <?php
                                                if (is_array($val)) {
                                                    echo '<pre>' . htmlspecialchars(json_encode($val, JSON_PRETTY_PRINT)) . '</pre>';
                                                } elseif (is_string($val) && strlen($val) > 200) {
                                                    echo '<code>' . htmlspecialchars(substr($val, 0, 200)) . '...</code>';
                                                } else {
                                                    echo '<code>' . htmlspecialchars(var_export($val, true)) . '</code>';
                                                }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Riders -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light"><strong>Riders (<?php echo count($allRiders); ?> documents)</strong></div>
        <div class="card-body">
            <?php foreach ($allRiders as $idx => $rider): ?>
                <div class="card mb-3 border">
                    <div class="card-header bg-light"><strong>Rider #<?php echo $idx + 1; ?></strong> (ID: <?php echo htmlspecialchars($rider['id']); ?>)</div>
                    <div class="card-body">
                        <table class="table table-sm table-bordered">
                            <thead><tr><th style="width:30%">Field</th><th>Value (truncated)</th></tr></thead>
                            <tbody>
                                <?php foreach ($rider as $key => $val): ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($key); ?></code></td>
                                        <td>
                                            <?php
                                                if (is_array($val)) {
                                                    echo '<pre>' . htmlspecialchars(json_encode($val, JSON_PRETTY_PRINT)) . '</pre>';
                                                } elseif (is_string($val) && strlen($val) > 200) {
                                                    echo '<code>' . htmlspecialchars(substr($val, 0, 200)) . '...</code>';
                                                } else {
                                                    echo '<code>' . htmlspecialchars(var_export($val, true)) . '</code>';
                                                }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
