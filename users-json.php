<?php
// Debug endpoint: returns Users+Riders merged JSON for quick verification
require_once __DIR__ . '/includes/db.php';
header('Content-Type: application/json');
try {
    $allUsers = $pdo->getAllDocuments('Users') ?? [];
    $allRiders = $pdo->getAllDocuments('Riders') ?? [];

    $normalize = function($item, $collection, $role) {
        $item['_collection'] = $collection;
        $item['_role'] = $role;
        $item['username'] = $item['username'] ?? $item['name'] ?? $item['full_name'] ?? $item['displayName'] ?? '';
        $item['email'] = $item['email'] ?? $item['emailAddress'] ?? '';

        // Normalize phone/mobile (check several common keys)
        $phoneKeys = ['phone', 'mobile', 'mobileNumber', 'mobile_no', 'phoneNumber', 'contact', 'contact_number', 'telephone', 'msisdn'];
        $foundPhone = '';
        foreach ($phoneKeys as $k) {
            if (!empty($item[$k])) { $foundPhone = $item[$k]; break; }
        }
        $item['phone'] = $foundPhone;

        $item['address'] = $item['address'] ?? $item['location'] ?? $item['home_address'] ?? '';
        // Normalize status similar to users.php
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
        if (isset($item['created_at']) && !($item['created_at'] instanceof DateTime)) {
            try { $item['created_at'] = new DateTime($item['created_at']); } catch (Exception $e) { $item['created_at'] = null; }
        }
        return $item;
    };

    foreach ($allUsers as &$u) { $u = $normalize($u, 'Users', 'customer'); }
    foreach ($allRiders as &$r) { $r = $normalize($r, 'Riders', 'rider'); }
    unset($u, $r);

    $merged = array_merge($allUsers, $allRiders);
    echo json_encode(array_values($merged), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
