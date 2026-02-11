<?php
/**
 * Debug: Scan Users and Riders documents for field names and image-like values
 * Shows which fields may contain uploaded ID images.
 */
session_start();

require_once 'includes/db.php';
require_once 'includes/functions.php';

requireLogin();

$allUsers = $pdo->getAllDocuments('Users') ?? [];
$allRiders = $pdo->getAllDocuments('Riders') ?? [];

$collections = ['Users' => $allUsers, 'Riders' => $allRiders];

$fieldStats = [];

foreach ($collections as $colName => $docs) {
    foreach ($docs as $doc) {
        foreach ($doc as $k => $v) {
            if ($k === 'id') continue; // skip id key
            if (!isset($fieldStats[$k])) {
                $fieldStats[$k] = ['count' => 0, 'samples' => [], 'cols' => []];
            }
            $fieldStats[$k]['count']++;
            if (count($fieldStats[$k]['samples']) < 5) {
                $fieldStats[$k]['samples'][] = is_string($v) ? $v : json_encode($v);
            }
            $fieldStats[$k]['cols'][$colName] = ($fieldStats[$k]['cols'][$colName] ?? 0) + 1;
        }
    }
}

// Detect fields that look like image URLs
$imageLike = [];
foreach ($fieldStats as $k => $s) {
    $looks = false;
    foreach ($s['samples'] as $sample) {
        if (stripos($sample, 'http://') !== false || stripos($sample, 'https://') !== false || stripos($sample, 'uploads/') !== false) {
            $looks = true;
            break;
        }
    }
    if ($looks) $imageLike[$k] = $s;
}

?>
<?php require_once 'includes/header.php'; require_once 'includes/sidebar.php'; ?>
<div class="main-container">
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <h5>Debug: Verification Field Scan</h5>
            <p class="text-muted">This lists all fields found in `Users` and `Riders` documents and highlights fields that contain image-like values.</p>
            <table class="table table-sm table-bordered">
                <thead class="table-light"><tr><th>Field</th><th>Count</th><th>Collections</th><th>Samples</th></tr></thead>
                <tbody>
                <?php foreach ($fieldStats as $field => $s): ?>
                    <tr <?php echo isset($imageLike[$field]) ? 'class="table-warning"' : ''; ?>>
                        <td><?php echo htmlspecialchars($field); ?></td>
                        <td><?php echo intval($s['count']); ?></td>
                        <td>
                            <?php foreach ($s['cols'] as $col => $c): ?>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($col . ': ' . $c); ?></span>
                            <?php endforeach; ?>
                        </td>
                        <td>
                            <?php foreach ($s['samples'] as $sample): ?>
                                <div><small><?php echo htmlspecialchars($sample); ?></small></div>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <?php if (!empty($imageLike)): ?>
                <h6>Fields that look like images/URLs</h6>
                <ul>
                <?php foreach ($imageLike as $f => $s): ?>
                    <li><?php echo htmlspecialchars($f); ?> — present in <?php echo intval($s['count']); ?> docs</li>
                <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="alert alert-info">No image-like fields detected in Users or Riders documents.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
