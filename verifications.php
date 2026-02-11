<?php
/**
 * ID Verifications Management Page
 * Manage pending ID verifications with approve/reject functionality
 */

session_start();

require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

// Check authentication
requireLogin();

$pageTitle = 'ID Verifications';
$message = '';
$messageType = '';

// Handle verification approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'approve' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $verificationId = sanitize($_POST['verification_id'] ?? '');
        try {
            // Determine User ID (handle embedded vs separate document)
            $existingVerification = $pdo->getDocument('verifications', $verificationId);
            $userId = $existingVerification['user_id'] ?? null;
            
            // If not in verification doc, assume verificationId IS the userId (embedded case)
            if (!$userId) {
                if ($pdo->getDocument('Users', $verificationId) || $pdo->getDocument('Riders', $verificationId)) {
                    $userId = $verificationId;
                }
            }

            $verUpdateData = [
                'status' => 'approved',
                'reviewed_at' => new DateTime()
            ];
            if ($userId) $verUpdateData['user_id'] = $userId;

            $pdo->set('verifications', $verificationId, $verUpdateData);
            // Fetch the verification document to create a record in verification_ids
            $verification = $pdo->getDocument('verifications', $verificationId) ?? [];

            // Prepare a verification_ids record
            try {
                $newVerificationId = 'verified_' . $verificationId . '_' . time();
                $verificationRecord = [
                    'original_verification_id' => $verificationId,
                    'user_id' => $verification['user_id'] ?? null,
                    'id_type' => $verification['id_type'] ?? null,
                    'front_image' => $verification['front_image'] ?? null,
                    'back_image' => $verification['back_image'] ?? null,
                    'selfie' => $verification['selfie'] ?? null,
                    'status' => 'approved',
                    'reviewed_at' => new DateTime(),
                    'created_at' => new DateTime(),
                    'approved_by' => $_SESSION['admin_id'] ?? ($_SESSION['user_id'] ?? 'admin')
                ];

                $pdo->set('verification_ids', $newVerificationId, $verificationRecord);

                // Update the user's document in Users or Riders collection
                if ($userId) {
                    $idType = $verification['id_type'] ?? 'ID';
                    
                    $userDoc = $pdo->getDocument('Users', $userId);
                    $legacyUserDoc = (!$userDoc) ? $pdo->getDocument('users', $userId) : null;
                    
                    if ($userDoc || $legacyUserDoc) {
                        $collection = $userDoc ? 'Users' : 'users';
                        // Customer: use verification_ids storage path
                        $storagePath = 'verification_ids/' . $userId . '/' . strtolower($idType) . '/';
                        $pdo->update($collection, $userId, [
                            'id_verified' => true,
                            'verificationStatus' => 'Verified',
                            'verification_id' => $newVerificationId,
                            'verificationIdStoragePath' => $storagePath,
                            'id_type' => $idType
                        ]);
                    } else {
                        $riderDoc = $pdo->getDocument('Riders', $userId);
                        if ($riderDoc) {
                            // Rider: use valid_ids storage path
                            $storagePath = 'valid_ids/' . $userId . '/' . strtolower($idType) . '/';
                            $pdo->update('Riders', $userId, [
                                'id_verified' => true,
                                'verificationStatus' => 'Verified',
                                'verification_id' => $newVerificationId,
                                'validIdStoragePath' => $storagePath,
                                'id_type' => $idType
                            ]);
                        }
                    }
                }
            } catch (Exception $e) {
                error_log('Error creating verification_ids record: ' . $e->getMessage());
            }

            $message = 'Verification approved successfully!';
            $messageType = 'success';
        } catch (Exception $e) {
            error_log('Error approving verification: ' . $e->getMessage());
            $message = 'Error approving verification.';
            $messageType = 'danger';
        }
    } elseif ($action === 'reject' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $verificationId = sanitize($_POST['verification_id'] ?? '');
        $adminNote = sanitize($_POST['admin_note'] ?? '');

        if (empty($adminNote)) {
            $message = 'Please provide a reason for rejection.';
            $messageType = 'danger';
        } else {
            try {
                // Determine User ID for rejection as well
                $existingVerification = $pdo->getDocument('verifications', $verificationId);
                $userId = $existingVerification['user_id'] ?? null;
                if (!$userId && ($pdo->getDocument('Users', $verificationId) || $pdo->getDocument('Riders', $verificationId))) {
                    $userId = $verificationId;
                }

                $verUpdateData = [
                    'status' => 'rejected',
                    'reviewed_at' => new DateTime(),
                    'admin_note' => $adminNote
                ];
                if ($userId) $verUpdateData['user_id'] = $userId;

                $pdo->set('verifications', $verificationId, $verUpdateData);
                // Also record rejection in verification_ids and mark user as not verified
                $verification = $pdo->getDocument('verifications', $verificationId) ?? [];
                try {
                    $newVerificationId = 'rejected_' . $verificationId . '_' . time();
                    $verificationRecord = [
                        'original_verification_id' => $verificationId,
                        'user_id' => $verification['user_id'] ?? null,
                        'id_type' => $verification['id_type'] ?? null,
                        'front_image' => $verification['front_image'] ?? null,
                        'back_image' => $verification['back_image'] ?? null,
                        'selfie' => $verification['selfie'] ?? null,
                        'status' => 'rejected',
                        'reviewed_at' => new DateTime(),
                        'admin_note' => $adminNote,
                        'created_at' => new DateTime(),
                        'rejected_by' => $_SESSION['admin_id'] ?? ($_SESSION['user_id'] ?? 'admin')
                    ];

                    $pdo->set('verification_ids', $newVerificationId, $verificationRecord);

                    // Update Users or Riders to explicitly mark not verified (optional)
                    if ($userId) {
                        $idType = $verification['id_type'] ?? 'ID';
                        
                        $userDoc = $pdo->getDocument('Users', $userId);
                        $legacyUserDoc = (!$userDoc) ? $pdo->getDocument('users', $userId) : null;
                        
                        if ($userDoc || $legacyUserDoc) {
                            $collection = $userDoc ? 'Users' : 'users';
                            // Customer: use verification_ids storage path
                            $storagePath = 'verification_ids/' . $userId . '/' . strtolower($idType) . '/';
                            $pdo->update($collection, $userId, [
                                'id_verified' => false,
                                'verificationStatus' => 'Rejected',
                                'verification_id' => $newVerificationId,
                                'verificationIdStoragePath' => $storagePath,
                                'id_type' => $idType
                            ]);
                        } else {
                            $riderDoc = $pdo->getDocument('Riders', $userId);
                            if ($riderDoc) {
                                // Rider: use valid_ids storage path
                                $storagePath = 'valid_ids/' . $userId . '/' . strtolower($idType) . '/';
                                $pdo->update('Riders', $userId, [
                                    'id_verified' => false,
                                    'verificationStatus' => 'Rejected',
                                    'verification_id' => $newVerificationId,
                                    'validIdStoragePath' => $storagePath,
                                    'id_type' => $idType
                                ]);
                            }
                        }
                    }
                } catch (Exception $e) {
                    error_log('Error creating verification_ids record on rejection: ' . $e->getMessage());
                }

                $message = 'Verification rejected and user notified.';
                $messageType = 'success';
            } catch (Exception $e) {
                error_log('Error rejecting verification: ' . $e->getMessage());
                $message = 'Error rejecting verification.';
                $messageType = 'danger';
            }
        }
    } elseif ($action === 'unverify' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $verificationId = sanitize($_POST['verification_id'] ?? '');
        $adminNote = sanitize($_POST['admin_note'] ?? '');

        if (empty($adminNote)) {
            $message = 'Please provide a reason for unverifying.';
            $messageType = 'danger';
        } else {
            try {
                // Find user ID from any verification record
                $existingVerification = $pdo->getDocument('verifications', $verificationId) ?? $pdo->getDocument('verification_ids', $verificationId) ?? [];
                $userId = $existingVerification['user_id'] ?? null;
                if (!$userId && ($pdo->getDocument('Users', $verificationId) || $pdo->getDocument('Riders', $verificationId))) {
                    $userId = $verificationId;
                }

                // 1. Update the original verification document to 'pending' to put it back in the queue
                $pdo->set('verifications', $verificationId, [
                    'status' => 'pending',
                    'admin_note' => $adminNote,
                    'reviewed_at' => new DateTime(), // Mark it as re-reviewed
                    'id_verified' => false
                ]);

                // 2. Create a new log in verification_ids
                $verification = $pdo->getDocument('verifications', $verificationId) ?? [];
                $newVerificationId = 'unverified_' . $verificationId . '_' . time();
                $pdo->set('verification_ids', $newVerificationId, [
                    'original_verification_id' => $verificationId,
                    'user_id' => $userId,
                    'id_type' => $verification['id_type'] ?? null,
                    'status' => 'rejected', // Log it as a rejection for audit
                    'reviewed_at' => new DateTime(),
                    'admin_note' => $adminNote,
                    'created_at' => new DateTime(),
                    'rejected_by' => $_SESSION['admin_id'] ?? ($_SESSION['user_id'] ?? 'admin')
                ]);

                // 3. Update the User/Rider document
                if ($userId) {
                    if ($pdo->getDocument('Users', $userId)) {
                        $pdo->update('Users', $userId, ['id_verified' => false, 'verificationStatus' => 'Unverified']);
                    } elseif ($pdo->getDocument('users', $userId)) {
                        $pdo->update('users', $userId, ['id_verified' => false, 'verificationStatus' => 'Unverified']);
                    } elseif ($pdo->getDocument('Riders', $userId)) {
                        $pdo->update('Riders', $userId, ['id_verified' => false, 'verificationStatus' => 'Unverified']);
                    }
                }

                $message = 'User has been unverified and returned to the pending queue.';
                $messageType = 'success';
            } catch (Exception $e) {
                error_log('Error unverifying user: ' . $e->getMessage());
                $message = 'Error unverifying user.';
                $messageType = 'danger';
            }
        }
    }
}

// Role filter (optional)
$filterRole = sanitize($_GET['role'] ?? '');

// Initialize variables
$verifications = [];
$allProcessedVerifications = []; // unfiltered list used for global counts
$roleCounts = ['customer' => 0, 'rider' => 0];
// Debug container for troubleshooting
$debugCounts = null;

// Fetch verifications from Firestore with user info
try {
    $allFirestoreVerifications = $pdo->getAllDocuments('verifications') ?? [];
    $allVerificationIds = $pdo->getAllDocuments('verification_ids') ?? [];

    // If documents exist in `verification_ids`, include them as well so admin can see uploads
    if (!empty($allVerificationIds)) {
        // Merge but prefer existing `verifications` entries first
        $allFirestoreVerifications = array_merge($allFirestoreVerifications, $allVerificationIds);
    }
    $allUsers = $pdo->getAllDocuments('Users') ?? [];
    // Add support for legacy 'users' collection
    $legacyUsers = $pdo->getAllDocuments('users') ?? [];
    foreach ($allUsers as &$u) { $u['_real_collection'] = 'Users'; }
    foreach ($legacyUsers as &$u) { $u['_real_collection'] = 'users'; }
    if (!empty($legacyUsers)) {
        $allUsers = array_merge($allUsers, $legacyUsers);
    }
    
    $allRiders = $pdo->getAllDocuments('Riders') ?? [];

    // Extract embedded verification data from Users and Riders documents
    // Look for: validIdFrontUrl, validIdBackUrl, profileImageUrl, etc.
    $embeddedVerifications = [];
    
    foreach ($allUsers as $user) {
        $hasVerificationData = false;
        $verData = ['user_id' => $user['id'], 'id' => $user['id'], '_source' => $user['_real_collection'] ?? 'Users'];
        
        // Check for ID verification fields - Maps to verification_ids storage folder
        // Your Picture (Selfie)
        $yourPictureFields = ['profileImageUrl', 'validIdSelfieUrl', 'selfie', 'profileImagePath', 'profile_picture', 'profilePhotoUrl', 'profilePictureUrl', 'selfieUrl', 'selfie_url', 'idSelfieUrl', 'id_selfie_url'];
        foreach ($yourPictureFields as $field) {
            if (isset($user[$field]) && !empty($user[$field])) {
                $verData['selfie'] = $user[$field];
                // Don't set hasVerificationData = true just for selfie/profile pic to avoid false positives
                break;
            }
        }
        
        // Driver's License Front
        $licFrontFields = ['seniorIdFrontUrl', 'validIdFrontUrl', 'front_image', 'driverLicenseFrontUrl', 'licensesFrontUrl', 'licenseIdFrontUrl', 'idFrontUrl', 'id_front_url', 'verificationFront', 'verification_front', 'frontUrl', 'front_url'];
        foreach ($licFrontFields as $field) {
            if (isset($user[$field]) && !empty($user[$field])) {
                $verData['front_image'] = $user[$field];
                $hasVerificationData = true;
                if ($field === 'seniorIdFrontUrl') {
                    $verData['id_type'] = 'Senior ID';
                    $verData['seniorIdFrontUrl'] = $user[$field];
                }
                if ($field === 'validIdFrontUrl') {
                    $verData['id_type'] = 'Valid ID';
                    $verData['validIdFrontUrl'] = $user[$field];
                }
                break;
            }
        }
        
        // Driver's License Back
        $licBackFields = ['seniorIdBackUrl', 'validIdBackUrl', 'back_image', 'driverLicenseBackUrl', 'licensesBackUrl', 'licenseIdBackUrl', 'idBackUrl', 'id_back_url', 'verificationBack', 'verification_back', 'backUrl', 'back_url'];
        foreach ($licBackFields as $field) {
            if (isset($user[$field]) && !empty($user[$field])) {
                $verData['back_image'] = $user[$field];
                $hasVerificationData = true;
                if ($field === 'seniorIdBackUrl') {
                    $verData['seniorIdBackUrl'] = $user[$field];
                }
                if ($field === 'validIdBackUrl') {
                    $verData['validIdBackUrl'] = $user[$field];
                }
                break;
            }
        }
        
        // ID Type
        if (isset($user['id_type'])) {
            $verData['id_type'] = $user['id_type'];
        }
        
        // If user is already verified, include them
        if (isset($user['id_verified']) && filter_var($user['id_verified'], FILTER_VALIDATE_BOOLEAN)) {
            $hasVerificationData = true;
        }

        if ($hasVerificationData) {
            $verData['status'] = ($user['id_verified'] ?? false) ? 'approved' : 'pending';
            $verData['submitted_at'] = $user['created_at'] ?? new DateTime();
            $verData['username'] = $user['name'] ?? $user['username'] ?? 'Unknown';
            $verData['email'] = $user['email'] ?? '';
            $verData['phone'] = $user['mobileNumber'] ?? $user['phone'] ?? '';
            $verData['address'] = $user['address'] ?? '';
            $verData['profilePictureUrl'] = $user['profileImagePath'] ?? $user['profilePictureUrl'] ?? '';
            $verData['user_status'] = $user['status'] ?? 'active';
            $verData['user_role'] = 'customer';
            // Include verification metadata stored on user with verification_ids storage folder
            if (isset($user['verificationIdStoragePath'])) {
                $verData['storage_path'] = $user['verificationIdStoragePath'];
            } else {
                // Generate storage path for customers: verification_ids/{customerId}/{idType}/
                $verData['storage_path'] = 'verification_ids/' . $user['id'] . '/' . ($verData['id_type'] ?? 'id') . '/';
            }
            if (isset($user['id_verified'])) $verData['id_verified'] = $user['id_verified'];

            $embeddedVerifications[] = $verData;
        }
    }
    
    foreach ($allRiders as $rider) {
        $hasVerificationData = false;
        $verData = ['user_id' => $rider['id'], 'id' => $rider['id'], '_source' => 'Riders', '_real_collection' => 'Riders'];
        
        // Check for ID verification fields - Maps to valid_ids storage folder
        // Your Picture (Selfie)
        $yourPictureFields = ['profileImageUrl', 'verification_picture', 'validIdSelfieUrl', 'selfie', 'profileImagePath', 'profile_picture', 'profilePhotoUrl', 'profilePictureUrl', 'selfieUrl', 'selfie_url', 'idSelfieUrl', 'id_selfie_url'];
        foreach ($yourPictureFields as $field) {
            if (isset($rider[$field]) && !empty($rider[$field])) {
                $verData['selfie'] = $rider[$field];
                // Don't set hasVerificationData = true just for selfie/profile pic to avoid false positives
                break;
            }
        }
        
        // Driver's License Front
        $licFrontFields = ['seniorIdFrontUrl', 'verification_license_front', 'validIdFrontUrl', 'front_image', 'driverLicenseFrontUrl', 'licensesFrontUrl', 'licenseIdFrontUrl', 'idFrontUrl', 'id_front_url', 'verificationFront', 'verification_front', 'frontUrl', 'front_url'];
        foreach ($licFrontFields as $field) {
            if (isset($rider[$field]) && !empty($rider[$field])) {
                $verData['front_image'] = $rider[$field];
                $hasVerificationData = true;
                break;
            }
        }
        
        // Driver's License Back
        $licBackFields = ['seniorIdBackUrl', 'verification_license_back', 'validIdBackUrl', 'back_image', 'driverLicenseBackUrl', 'licensesBackUrl', 'licenseIdBackUrl', 'idBackUrl', 'id_back_url', 'verificationBack', 'verification_back', 'backUrl', 'back_url'];
        foreach ($licBackFields as $field) {
            if (isset($rider[$field]) && !empty($rider[$field])) {
                $verData['back_image'] = $rider[$field];
                $hasVerificationData = true;
                break;
            }
        }
        
        // Vehicle OR/CR Front
        $vehicleORFrontFields = ['verification_orcr1', 'vehicleORCRFrontUrl', 'vehicleRegistrationFront', 'vehicleORFront', 'carRegistrationFront', 'vehicleORFrontUrl'];
        foreach ($vehicleORFrontFields as $field) {
            if (isset($rider[$field]) && !empty($rider[$field])) {
                $verData['vehicleORCRFrontUrl'] = $rider[$field];
                $hasVerificationData = true;
                break;
            }
        }
        
        // Vehicle OR/CR Back
        $vehicleORBackFields = ['verification_orcr2', 'vehicleORCRBackUrl', 'vehicleRegistrationBack', 'vehicleORBack', 'carRegistrationBack', 'vehicleORBackUrl'];
        foreach ($vehicleORBackFields as $field) {
            if (isset($rider[$field]) && !empty($rider[$field])) {
                $verData['vehicleORCRBackUrl'] = $rider[$field];
                $hasVerificationData = true;
                break;
            }
        }
        
        // ID Type
        if (isset($rider['id_type'])) {
            $verData['id_type'] = $rider['id_type'];
        }
        
        // If rider is already verified, include them
        if (isset($rider['id_verified']) && filter_var($rider['id_verified'], FILTER_VALIDATE_BOOLEAN)) {
            $hasVerificationData = true;
        } elseif (isset($rider['verificationStatus']) && strtoupper($rider['verificationStatus']) === 'PENDING') {
            $hasVerificationData = true;
        }

        if ($hasVerificationData) {
            $status = 'pending';
            if (isset($rider['id_verified']) && filter_var($rider['id_verified'], FILTER_VALIDATE_BOOLEAN)) {
                $status = 'approved';
            } elseif (isset($rider['verificationStatus'])) {
                $vStatus = strtoupper($rider['verificationStatus']);
                if ($vStatus === 'APPROVED' || $vStatus === 'VERIFIED') {
                    $status = 'approved';
                } elseif ($vStatus === 'REJECTED') {
                    $status = 'rejected';
                }
            }
            $verData['status'] = $status;
            $verData['submitted_at'] = $rider['created_at'] ?? new DateTime();
            $verData['username'] = $rider['fullName'] ?? $rider['name'] ?? 'Unknown';
            $verData['email'] = $rider['email'] ?? '';
            $verData['phone'] = $rider['contactNumber'] ?? $rider['mobileNumber'] ?? '';
            $verData['address'] = $rider['address'] ?? '';
            $verData['profilePictureUrl'] = $rider['profileImagePath'] ?? $rider['profilePictureUrl'] ?? '';
            $verData['user_status'] = $rider['status'] ?? 'active';
            $verData['user_role'] = 'rider';
            // Include verification metadata stored on rider with valid_ids storage folder
            if (isset($rider['validIdStoragePath'])) {
                $verData['storage_path'] = $rider['validIdStoragePath'];
            } else {
                // Generate storage path for riders: valid_ids/{riderId}/{idType}/
                $verData['storage_path'] = 'valid_ids/' . $rider['id'] . '/' . ($verData['id_type'] ?? 'id') . '/';
            }
            if (isset($rider['id_verified'])) $verData['id_verified'] = $rider['id_verified'];
            // Attach rider-specific profile fields into embedded verification
            if (isset($rider['vehicleType'])) $verData['vehicleType'] = $rider['vehicleType'];
            if (isset($rider['vehicle_type'])) $verData['vehicleType'] = $rider['vehicle_type'];
            if (isset($rider['licensePlate'])) $verData['licensePlate'] = $rider['licensePlate'];
            if (isset($rider['license_plate'])) $verData['licensePlate'] = $rider['license_plate'];
            if (isset($rider['plateNumber'])) $verData['licensePlate'] = $rider['plateNumber'];
            if (isset($rider['rating'])) $verData['rating'] = $rider['rating'];
            if (isset($rider['totalTrips'])) $verData['totalTrips'] = $rider['totalTrips'];
            if (isset($rider['total_trips'])) $verData['totalTrips'] = $rider['total_trips'];
            if (isset($rider['completedRides'])) $verData['totalTrips'] = $rider['completedRides'];

            $embeddedVerifications[] = $verData;
        }
    }
    
    // Merge embedded verifications with collection verifications
    if (!empty($embeddedVerifications)) {
        $allFirestoreVerifications = array_merge($allFirestoreVerifications, $embeddedVerifications);
    }

    // Debug info: counts to help locate where uploaded verifications are stored
    $debugCounts = [
        'verifications' => count($pdo->getAllDocuments('verifications') ?? []),
        'verification_ids' => count($allVerificationIds),
        'embedded_in_users_riders' => count($embeddedVerifications),
        'Users' => count($allUsers),
        'Riders' => count($allRiders),
    ];

    // Extra debug details: samples and missing field counts
    $debugDetails = [];
    // Riders without validIdStoragePath
    $missingValidId = array_filter($allRiders, function($r) { return empty($r['validIdStoragePath']); });
    $debugDetails['riders_missing_validIdStoragePath_count'] = count($missingValidId);
    $debugDetails['riders_missing_validIdStoragePath_sample'] = array_slice(array_map(function($r){ return ['id'=>$r['id'] ?? null,'email'=>$r['email'] ?? null]; }, $missingValidId), 0, 5);
    // Verifications without storage_path
    $withoutStorage = array_filter($allFirestoreVerifications, function($v){ return empty($v['storage_path']) && empty($v['_source']); });
    $debugDetails['verifications_missing_storage_path_count'] = count($withoutStorage);
    $debugDetails['verifications_missing_storage_path_sample'] = array_slice(array_map(function($v){ return ['id'=>$v['id'] ?? ($v['user_id'] ?? null),'user_id'=>$v['user_id'] ?? null]; }, $withoutStorage), 0, 5);
    
    // Create lookup maps for both Users and Riders collections
    $userMap = [];
    $riderMap = []; // Separate map for riders to avoid overwriting customers
    foreach ($allUsers as $user) {
        $user['_collection'] = $user['_real_collection'] ?? 'Users';
        $user['_role'] = 'customer';
        $userMap[$user['id']] = $user;
    }
    foreach ($allRiders as $rider) {
        $rider['_collection'] = 'Riders';
        $rider['_role'] = 'rider';
        $riderMap[$rider['id']] = $rider; // Separate map to preserve customer data
    }
    
    $verifications = [];
    foreach ($allFirestoreVerifications as $verification) {
        // Ensure keys exist to prevent warnings
        $verification['id_type'] = $verification['id_type'] ?? 'ID';
        $verification['submitted_at'] = $verification['submitted_at'] ?? ($verification['created_at'] ?? null);

        // Attach user info - only if not already set (for embedded verifications)
        if (isset($verification['user_id'])) {
            // First check if it's a customer (Users collection)
            if (isset($userMap[$verification['user_id']])) {
                $user = $userMap[$verification['user_id']];
                // Only set if not already populated from embedded data
                if (!isset($verification['username']) || $verification['username'] === 'Unknown') {
                    $verification['username'] = $user['name'] ?? $user['username'] ?? 'Unknown';
                }
                if (!isset($verification['email'])) {
                    $verification['email'] = $user['email'] ?? '';
                }
                if (!isset($verification['phone'])) {
                    $verification['phone'] = $user['phone'] ?? $user['contactNumber'] ?? $user['mobileNumber'] ?? '';
                }
                if (!isset($verification['address'])) {
                    $verification['address'] = $user['address'] ?? '';
                }
                if (!isset($verification['user_role'])) {
                    $verification['user_role'] = $user['_role'] ?? '';
                }
                if (!isset($verification['user_status'])) {
                    $verification['user_status'] = $user['status'] ?? 'active';
                }
            }
            // Otherwise check if it's a rider (Riders collection)
            elseif (isset($riderMap[$verification['user_id']])) {
                $user = $riderMap[$verification['user_id']];
                // Only set if not already populated from embedded data
                if (!isset($verification['username']) || $verification['username'] === 'Unknown') {
                    $verification['username'] = $user['fullName'] ?? $user['name'] ?? $user['username'] ?? 'Unknown';
                }
                if (!isset($verification['email'])) {
                    $verification['email'] = $user['email'] ?? '';
                }
                if (!isset($verification['phone'])) {
                    $verification['phone'] = $user['contactNumber'] ?? $user['mobileNumber'] ?? $user['phone'] ?? '';
                }
                if (!isset($verification['address'])) {
                    $verification['address'] = $user['address'] ?? '';
                }
                if (!isset($verification['user_role'])) {
                    $verification['user_role'] = $user['_role'] ?? '';
                }
                if (!isset($verification['user_status'])) {
                    $verification['user_status'] = $user['status'] ?? 'active';
                }
                
                // Attach rider-specific fields
                if (!isset($verification['vehicleType'])) $verification['vehicleType'] = $user['vehicleType'] ?? $user['vehicle_type'] ?? '';
                if (!isset($verification['licensePlate'])) $verification['licensePlate'] = $user['licensePlate'] ?? $user['license_plate'] ?? $user['plateNumber'] ?? '';
                if (!isset($verification['rating'])) $verification['rating'] = $user['rating'] ?? 0;
                if (!isset($verification['totalTrips'])) $verification['totalTrips'] = $user['totalTrips'] ?? $user['total_trips'] ?? $user['completedRides'] ?? 0;
                if (!isset($verification['id_verified'])) $verification['id_verified'] = $user['id_verified'] ?? false;
                if (!isset($verification['validIdStoragePath'])) $verification['validIdStoragePath'] = $user['validIdStoragePath'] ?? '';
            }
        }
        
        // Add storage path based on user role if not already set
        if (!isset($verification['storage_path'])) {
            $idType = $verification['id_type'] ?? 'ID';
            if (isset($verification['user_role'])) {
                if ($verification['user_role'] === 'customer') {
                    // Customer: verification_ids/{customerId}/{idType}/
                    $verification['storage_path'] = 'verification_ids/' . ($verification['user_id'] ?? '') . '/' . strtolower($idType) . '/';
                } elseif ($verification['user_role'] === 'rider') {
                    // Rider: valid_ids/{riderId}/{idType}/
                    $verification['storage_path'] = 'valid_ids/' . ($verification['user_id'] ?? '') . '/' . strtolower($idType) . '/';
                }
            }
        }
        
        // For embedded verifications from Users/Riders, set user_role from _source
        if (!isset($verification['user_role']) && isset($verification['_source'])) {
            $verification['user_role'] = (in_array($verification['_source'], ['Users', 'users'])) ? 'customer' : 'rider';
            
            // Add storage path for embedded verifications
            if (!isset($verification['storage_path'])) {
                $idType = $verification['id_type'] ?? 'ID';
                if (in_array($verification['_source'], ['Users', 'users'])) {
                    $verification['storage_path'] = 'verification_ids/' . $verification['user_id'] . '/' . strtolower($idType) . '/';
                } elseif ($verification['_source'] === 'Riders') {
                    $verification['storage_path'] = 'valid_ids/' . $verification['user_id'] . '/' . strtolower($idType) . '/';
                }
            }
        }
        
        // Ensure submitted_at is set (fallback to created_at)
        if (!isset($verification['submitted_at'])) {
            $verification['submitted_at'] = $verification['created_at'] ?? null;
        }

        // Collect processed verification (unfiltered)
        $allProcessedVerifications[] = $verification;
    }

    // Deduplicate verifications by user_id (prioritize Pending, then latest)
    $groupedVerifications = [];
    foreach ($allProcessedVerifications as $v) {
        $uid = $v['user_id'] ?? uniqid();
        $groupedVerifications[$uid][] = $v;
    }

    $allProcessedVerifications = [];
    foreach ($groupedVerifications as $group) {
        if (count($group) === 1) {
            $allProcessedVerifications[] = $group[0];
        } else {
            // Sort: Pending first, then by date descending
            usort($group, function($a, $b) {
                $statusA = $a['status'] ?? '';
                $statusB = $b['status'] ?? '';
                if ($statusA === 'pending' && $statusB !== 'pending') return -1;
                if ($statusB === 'pending' && $statusA !== 'pending') return 1;

                $dateA = $a['submitted_at'] ?? 0;
                $dateB = $b['submitted_at'] ?? 0;
                $tsA = ($dateA instanceof DateTime) ? $dateA->getTimestamp() : (is_string($dateA) ? strtotime($dateA) : 0);
                $tsB = ($dateB instanceof DateTime) ? $dateB->getTimestamp() : (is_string($dateB) ? strtotime($dateB) : 0);
                
                return $tsB - $tsA;
            });
            $allProcessedVerifications[] = $group[0];
        }
    }

    // Filter out blank users (orphaned verifications or missing user data)
    $allProcessedVerifications = array_filter($allProcessedVerifications, function($v) {
        $name = $v['username'] ?? '';
        return !empty($name) && $name !== 'Unknown' && $name !== 'N/A';
    });

    // Compute counts by role from the full unfiltered set (so badges always show totals)
    $roleCounts = ['customer' => 0, 'rider' => 0];
    foreach ($allProcessedVerifications as $v) {
        $userRole = $v['user_role'] ?? null;
        if ($userRole && isset($roleCounts[$userRole])) {
            $roleCounts[$userRole]++;
        }
    }

    // Apply role filter to produce the displayed list
    if (empty($filterRole)) {
        $verifications = $allProcessedVerifications;
    } else {
        $verifications = array_values(array_filter($allProcessedVerifications, function($v) use ($filterRole) {
            return ($v['user_role'] ?? '') === $filterRole;
        }));
    }

    // Sort: pending first, then by submitted_at descending
    usort($verifications, function($a, $b) {
        // Pending status first
        $aPending = ($a['status'] ?? '') === 'pending' ? 0 : 1;
        $bPending = ($b['status'] ?? '') === 'pending' ? 0 : 1;
        if ($aPending !== $bPending) {
            return $aPending - $bPending;
        }

        // Then by submitted_at
        $aTime = 0;
        $bTime = 0;

        $subA = $a['submitted_at'] ?? null;
        if (!empty($subA)) {
            $aTime = $subA instanceof DateTime ? $subA->getTimestamp() : strtotime((string)$subA);
        }

        $subB = $b['submitted_at'] ?? null;
        if (!empty($subB)) {
            $bTime = $subB instanceof DateTime ? $subB->getTimestamp() : strtotime((string)$subB);
        }

        return $bTime - $aTime;
    });
} catch (Exception $e) {
    error_log('Error fetching verifications: ' . $e->getMessage());
}

$csrf_token = generateCSRFToken();

require_once 'includes/sidebar.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1><i class="bi bi-file-earmark-check"></i> ID Verifications</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active">ID Verifications</li>
        </ol>
    </nav>
</div>

<!-- Main Content -->
<div class="main-container">
    <!-- Role Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="btn-group" role="group">
                <a href="verifications.php" class="btn btn-outline-primary <?php echo empty($filterRole) ? 'active' : ''; ?>">
                    <i class="bi bi-people"></i> All
                    <span class="badge bg-primary ms-2"><?php echo count($allProcessedVerifications ?? []); ?></span>
                </a>
                <a href="verifications.php?role=customer" class="btn btn-outline-primary <?php echo $filterRole === 'customer' ? 'active' : ''; ?>">
                    <i class="bi bi-person"></i> Customers
                    <span class="badge bg-primary ms-2"><?php echo $roleCounts['customer'] ?? 0; ?></span>
                </a>
                <a href="verifications.php?role=rider" class="btn btn-outline-primary <?php echo $filterRole === 'rider' ? 'active' : ''; ?>">
                    <i class="bi bi-truck"></i> Riders
                    <span class="badge bg-primary ms-2"><?php echo $roleCounts['rider'] ?? 0; ?></span>
                </a>
            </div>
        </div>
    </div>
    <!-- Messages -->
    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon bg-warning">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div class="stat-content">
                    <h6 class="stat-label">Pending</h6>
                    <p class="stat-value">
                        <?php echo count(array_filter($verifications, fn($v) => $v['status'] === 'pending')); ?>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon bg-success">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h6 class="stat-label">Approved</h6>
                    <p class="stat-value">
                        <?php echo count(array_filter($verifications, fn($v) => $v['status'] === 'approved')); ?>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon bg-danger">
                    <i class="bi bi-x-circle"></i>
                </div>
                <div class="stat-content">
                    <h6 class="stat-label">Rejected</h6>
                    <p class="stat-value">
                        <?php echo count(array_filter($verifications, fn($v) => $v['status'] === 'rejected')); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($_GET['debug']) && $_GET['debug'] == '1'): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h6 class="mb-2">Debug Info</h6>
                <pre style="font-size:0.85rem; white-space:pre-wrap;"><?php echo htmlspecialchars(json_encode(['counts'=>$debugCounts,'details'=>$debugDetails], JSON_PRETTY_PRINT)); ?></pre>
            </div>
        </div>
    <?php endif; ?>

    <!-- Verifications Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">
                <i class="bi bi-list"></i> Verification List
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (!empty($verifications)): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>User Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>ID Type</th>
                                <th>Submitted</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($verifications as $verification): ?>
                                <tr class="<?php echo $verification['status'] === 'pending' ? 'table-light' : ''; ?>">
                                    <td>
                                        <strong><?php echo htmlspecialchars($verification['username'] ?? 'N/A'); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($verification['email'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php $role = $verification['user_role'] ?? 'N/A'; ?>
                                        <?php if ($role === 'customer'): ?>
                                            <span class="badge bg-info"><i class="bi bi-person"></i> Customer</span>
                                        <?php elseif ($role === 'rider'): ?>
                                            <span class="badge bg-warning"><i class="bi bi-truck"></i> Rider</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars(ucfirst($role)); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo htmlspecialchars($verification['id_type'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?php echo !empty($verification['submitted_at']) ? formatDate($verification['submitted_at']) : 'N/A'; ?></small>
                                    </td>
                                    <td>
                                        <?php if ($verification['status'] === 'pending'): ?>
                                            <span class="badge bg-warning"><i class="bi bi-hourglass-split"></i> Pending</span>
                                        <?php elseif ($verification['status'] === 'approved'): ?>
                                            <span class="badge bg-success"><i class="bi bi-check-circle"></i> Approved</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Rejected</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary"
                                                onclick="viewVerification(<?php echo htmlspecialchars(json_encode($verification)); ?>)"
                                                data-bs-toggle="modal"
                                                data-bs-target="#verificationModal">
                                            <i class="bi bi-eye"></i> View
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info m-3 mb-0">
                    <i class="bi bi-info-circle"></i> No verifications found.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Verification Details Modal -->
<div class="modal fade" id="verificationModal" tabindex="-1" size="xl">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">ID Verification Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <!-- User Info -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">User Information</h6>
                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <p><strong>Name:</strong> <span id="verName"></span></p>
                                <p><strong>Email:</strong> <span id="verEmail"></span></p>
                                <p><strong>Phone:</strong> <span id="verPhone"></span></p>
                                <p><strong>Address:</strong> <span id="verAddress"></span></p>
                                <p><strong>Role:</strong> <span id="verRole"></span></p>
                                <p><strong>User Status:</strong> <span id="verUserStatus"></span></p>
                                <p><strong>ID Type:</strong> <span id="verIdType" class="badge bg-info"></span></p>
                                <p><strong>Submitted:</strong> <span id="verSubmitted"></span></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">Review Information</h6>
                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <p><strong>Status:</strong> <span id="verStatus"></span></p>
                                <p><strong>Reviewed:</strong> <span id="verReviewed">-</span></p>
                                <p id="storagePathSection" style="display: none;"><strong>Storage Path:</strong> <code id="verStoragePath" style="font-size: 0.85rem; word-break: break-all;">-</code>
                                    &nbsp; <a id="verStorageFolderLink" href="#" target="_blank" style="display:none; font-size:0.9rem;">Open storage folder</a>
                                </p>
                                <p><strong>Admin Note:</strong></p>
                                <textarea id="verAdminNote" class="form-control" rows="3" disabled></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rider Verification Documents -->
                <h6 class="text-muted mb-3">Verification Documents</h6>

                <!-- Your Picture -->
                <div id="sectionYourPicture" class="card border-0 bg-light mb-4">
                    <div class="card-header bg-white border-bottom">
                        <h6 class="mb-0"><i class="bi bi-person-circle"></i> Your Picture</h6>
                    </div>
                    <div class="card-body text-center">
                        <div class="img-container" onclick="openImageModal(this.querySelector('img').src)" style="cursor: pointer;">
                            <img id="verYourPicture" src="" alt="Your Picture" class="img-fluid border rounded shadow-sm" style="max-height: 300px; width: auto; min-height: 150px; background-color: #f8f9fa;" onerror="this.onerror=null;this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiB2aWV3Qm94PSIwIDAgMTAwIDEwMCI+PHJlY3Qgd2lkdGg9IjEwMCIgaGVpZ2h0PSIxMDAiIGZpbGw9IiNlZWVlZWUiLz48dGV4dCB4PSI1MCUiIHk9IjUwJSIgZG9taW5hbnQtYmFzZWxpbmU9Im1pZGRsZSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZm9udC1mYW1pbHk9InNhbnMtc2VyaWYiIGZvbnQtc2l6ZT0iMTIiIGZpbGw9IiNhYWFhYWEiPk5vIEltYWdlPC90ZXh0Pjwvc3ZnPg==';">
                        </div>
                        <small class="text-muted d-block mt-2">Click to enlarge</small>
                    </div>
                </div>

                <!-- Driver's License -->
                <div id="sectionIdDocument" class="card border-0 bg-light mb-4">
                    <div class="card-header bg-white border-bottom">
                        <h6 id="headerIdDocument" class="mb-0"><i class="bi bi-card-heading"></i> Driver's License</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <h6 class="small mb-2">Front</h6>
                                <div class="img-container" onclick="openImageModal(this.querySelector('img').src)" style="cursor: pointer;">
                                    <img id="verLicenseFront" src="" alt="License Front" class="img-fluid border rounded shadow-sm" style="max-height: 250px; width: 100%; object-fit: contain; min-height: 150px; background-color: #f8f9fa;" onerror="this.onerror=null;this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiB2aWV3Qm94PSIwIDAgMTAwIDEwMCI+PHJlY3Qgd2lkdGg9IjEwMCIgaGVpZ2h0PSIxMDAiIGZpbGw9IiNlZWVlZWUiLz48dGV4dCB4PSI1MCUiIHk9IjUwJSIgZG9taW5hbnQtYmFzZWxpbmU9Im1pZGRsZSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZm9udC1mYW1pbHk9InNhbnMtc2VyaWYiIGZvbnQtc2l6ZT0iMTIiIGZpbGw9IiNhYWFhYWEiPk5vIEltYWdlPC90ZXh0Pjwvc3ZnPg==';">
                                </div>
                                <small class="text-muted d-block mt-2">Click to enlarge</small>
                            </div>
                            <div class="col-md-6">
                                <h6 class="small mb-2">Back</h6>
                                <div class="img-container" onclick="openImageModal(this.querySelector('img').src)" style="cursor: pointer;">
                                    <img id="verLicenseBack" src="" alt="License Back" class="img-fluid border rounded shadow-sm" style="max-height: 250px; width: 100%; object-fit: contain; min-height: 150px; background-color: #f8f9fa;" onerror="this.onerror=null;this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiB2aWV3Qm94PSIwIDAgMTAwIDEwMCI+PHJlY3Qgd2lkdGg9IjEwMCIgaGVpZ2h0PSIxMDAiIGZpbGw9IiNlZWVlZWUiLz48dGV4dCB4PSI1MCUiIHk9IjUwJSIgZG9taW5hbnQtYmFzZWxpbmU9Im1pZGRsZSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZm9udC1mYW1pbHk9InNhbnMtc2VyaWYiIGZvbnQtc2l6ZT0iMTIiIGZpbGw9IiNhYWFhYWEiPk5vIEltYWdlPC90ZXh0Pjwvc3ZnPg==';">
                                </div>
                                <small class="text-muted d-block mt-2">Click to enlarge</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Vehicle OR/CR -->
                <div id="sectionVehicleORCR" class="card border-0 bg-light mb-4">
                    <div class="card-header bg-white border-bottom">
                        <h6 class="mb-0"><i class="bi bi-file-text"></i> Vehicle OR/CR</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <h6 class="small mb-2">Front</h6>
                                <div class="img-container" onclick="openImageModal(this.querySelector('img').src)" style="cursor: pointer;">
                                    <img id="verVehicleORCRFront" src="" alt="Vehicle OR/CR Front" class="img-fluid border rounded shadow-sm" style="max-height: 250px; width: 100%; object-fit: contain; min-height: 150px; background-color: #f8f9fa;" onerror="this.onerror=null;this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiB2aWV3Qm94PSIwIDAgMTAwIDEwMCI+PHJlY3Qgd2lkdGg9IjEwMCIgaGVpZ2h0PSIxMDAiIGZpbGw9IiNlZWVlZWUiLz48dGV4dCB4PSI1MCUiIHk9IjUwJSIgZG9taW5hbnQtYmFzZWxpbmU9Im1pZGRsZSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZm9udC1mYW1pbHk9InNhbnMtc2VyaWYiIGZvbnQtc2l6ZT0iMTIiIGZpbGw9IiNhYWFhYWEiPk5vIEltYWdlPC90ZXh0Pjwvc3ZnPg==';">
                                </div>
                                <small class="text-muted d-block mt-2">Click to enlarge</small>
                            </div>
                            <div class="col-md-6">
                                <h6 class="small mb-2">Back</h6>
                                <div class="img-container" onclick="openImageModal(this.querySelector('img').src)" style="cursor: pointer;">
                                    <img id="verVehicleORCRBack" src="" alt="Vehicle OR/CR Back" class="img-fluid border rounded shadow-sm" style="max-height: 250px; width: 100%; object-fit: contain; min-height: 150px; background-color: #f8f9fa;" onerror="this.onerror=null;this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiB2aWV3Qm94PSIwIDAgMTAwIDEwMCI+PHJlY3Qgd2lkdGg9IjEwMCIgaGVpZ2h0PSIxMDAiIGZpbGw9IiNlZWVlZWUiLz48dGV4dCB4PSI1MCUiIHk9IjUwJSIgZG9taW5hbnQtYmFzZWxpbmU9Im1pZGRsZSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZm9udC1mYW1pbHk9InNhbnMtc2VyaWYiIGZvbnQtc2l6ZT0iMTIiIGZpbGw9IiNhYWFhYWEiPk5vIEltYWdlPC90ZXh0Pjwvc3ZnPg==';">
                                </div>
                                <small class="text-muted d-block mt-2">Click to enlarge</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rider-Specific Details (shown only for riders) -->
                <div id="riderDetailsSection" style="display: none;" class="card border-info mt-4 mb-4">
                    <div class="card-header bg-info bg-opacity-10 border-info">
                        <h6 class="mb-0 text-info"><i class="bi bi-truck"></i> Rider Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Vehicle Type:</strong> <span id="verVehicleType">-</span></p>
                                <p><strong>License Plate:</strong> <span id="verLicensePlate">-</span></p>
                                <p><strong>Rating:</strong> <span id="verRating">-</span></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Total Trips:</strong> <span id="verTotalTrips">-</span></p>
                                <p><strong>Account Status:</strong> <span id="verAccountStatus">-</span></p>
                                <p><strong>ID Verification Status:</strong> <span id="verIdVerificationStatus">-</span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rejection Reason (if rejected) -->
                <div id="rejectionSection" style="display: none;" class="alert alert-danger mt-4">
                    <h6>Rejection Reason</h6>
                    <p id="rejectionReason"></p>
                </div>

                <!-- Action Buttons -->
                <div id="actionButtons" class="mt-4 pt-4 border-top">
                    <form method="POST" id="verificationForm">
                        <input type="hidden" name="verification_id" id="actionVerificationId">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                        <div id="noteSection" style="display: none;" class="mb-3">
                            <label for="adminNote" id="adminNoteLabel" class="form-label">Reason *</label>
                            <textarea id="adminNote" name="admin_note" class="form-control" rows="3" placeholder="Explain the reason..."></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-success btn-lg" id="approveBtn" style="display: none;" onclick="approveVerification()">
                                <i class="bi bi-check-circle"></i> Approve
                            </button>
                            <button type="button" class="btn btn-danger btn-lg" id="rejectBtn" style="display: none;" onclick="toggleRejectForm()">
                                <i class="bi bi-x-circle"></i> Reject
                            </button>
                            <button type="button" class="btn btn-warning btn-lg" id="unverifyBtn" style="display: none;" onclick="toggleUnverifyForm()">
                                <i class="bi bi-arrow-counterclockwise"></i> Unverify
                            </button>
                            <button type="submit" class="btn btn-danger" id="submitRejectBtn" style="display: none;" name="action" value="reject">
                                <i class="bi bi-check"></i> Confirm Rejection
                            </button>
                            <button type="submit" class="btn btn-warning" id="submitUnverifyBtn" style="display: none;" name="action" value="unverify">
                                <i class="bi bi-check"></i> Confirm Unverify
                            </button>
                            <button type="button" class="btn btn-secondary" id="cancelRejectBtn" style="display: none;" onclick="toggleRejectForm()">
                                <i class="bi bi-x"></i> Cancel
                            </button>
                            <button type="button" class="btn btn-secondary" id="cancelUnverifyBtn" style="display: none;" onclick="toggleUnverifyForm()">
                                <i class="bi bi-x"></i> Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Image Enlargement Modal -->
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Document Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="enlargedImage" src="" alt="Document" class="img-fluid" style="max-height: 600px;">
            </div>
        </div>
    </div>
</div>

<style>
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1.5rem;
        border: 1px solid #e5e7eb;
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
    }

    .stat-icon.bg-warning {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    }

    .stat-icon.bg-success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }

    .stat-icon.bg-danger {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    }

    .stat-label {
        font-size: 0.875rem;
        color: #6b7280;
        margin: 0;
        font-weight: 500;
    }

    .stat-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: #1f2937;
        margin: 0.5rem 0 0 0;
    }

    .img-container {
        position: relative;
        overflow: hidden;
        border-radius: 8px;
        background: #f0f0f0;
    }

    .img-container:hover img {
        opacity: 0.8;
    }
</style>

<script>
    let currentVerification = null;

    function viewVerification(verification) {
        currentVerification = verification;

        document.getElementById('verName').textContent = verification.username || verification.name || 'N/A';
        document.getElementById('verEmail').textContent = verification.email || 'N/A';
        document.getElementById('verPhone').textContent = verification.phone || 'N/A';
        document.getElementById('verAddress').textContent = verification.address || 'N/A';
        // Role (customer / rider)
        document.getElementById('verRole').innerHTML = verification.user_role ? (verification.user_role === 'customer' ? '<span class="badge bg-info"><i class="bi bi-person"></i> Customer</span>' : (verification.user_role === 'rider' ? '<span class="badge bg-warning"><i class="bi bi-truck"></i> Rider</span>' : '<span class="badge bg-secondary">' + verification.user_role + '</span>')) : 'N/A';
        // User Status
        const userStatusEl = document.getElementById('verUserStatus');
        if (verification.user_status === 'active') {
            userStatusEl.innerHTML = '<span class="badge bg-success">Active</span>';
        } else if (verification.user_status === 'banned') {
            userStatusEl.innerHTML = '<span class="badge bg-danger">Banned</span>';
        } else {
            userStatusEl.textContent = verification.user_status || 'N/A';
        }
        
        document.getElementById('verIdType').textContent = verification.id_type || 'ID';
        
        if (verification.submitted_at) {
            const date = new Date(verification.submitted_at);
            document.getElementById('verSubmitted').textContent = isNaN(date.getTime()) ? 'N/A' : date.toLocaleString();
        } else {
            document.getElementById('verSubmitted').textContent = 'N/A';
        }
        document.getElementById('actionVerificationId').value = verification.id;

        // Set status badge
        const statusBadge = document.getElementById('verStatus');
        if (verification.status === 'pending') {
            statusBadge.innerHTML = '<span class="badge bg-warning"><i class="bi bi-hourglass-split"></i> Pending</span>';
        } else if (verification.status === 'approved') {
            statusBadge.innerHTML = '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Approved</span>';
        } else {
            statusBadge.innerHTML = '<span class="badge bg-danger"><i class="bi bi-x-circle"></i> Rejected</span>';
        }

        // Set reviewed date and admin note
        if (verification.reviewed_at) {
            const date = new Date(verification.reviewed_at);
            document.getElementById('verReviewed').textContent = isNaN(date.getTime()) ? 'N/A' : date.toLocaleString();
        }
        document.getElementById('verAdminNote').value = verification.admin_note || '';

        // Set storage path for both customers and riders
        const storagePathSection = document.getElementById('storagePathSection');
        if ((verification.user_role === 'customer' || verification.user_role === 'rider') && verification.storage_path) {
            storagePathSection.style.display = 'block';
            document.getElementById('verStoragePath').textContent = verification.storage_path;
            // Set storage folder link for quick access to Cloud Console
            const folderLink = document.getElementById('verStorageFolderLink');
            try {
                const bucket = 'pabili-pasabuy.appspot.com';
                const folder = (verification.storage_path || '').replace(/\/$/, '');
                const consoleUrl = 'https://console.cloud.google.com/storage/browser/_details/' + bucket + '/' + encodeURIComponent(folder) + '?project=pabili-pasabuy';
                folderLink.href = consoleUrl;
                folderLink.style.display = 'inline-block';
            } catch (e) {
                folderLink.style.display = 'none';
            }
        } else {
            storagePathSection.style.display = 'none';
            const folderLink = document.getElementById('verStorageFolderLink');
            if (folderLink) folderLink.style.display = 'none';
        }

        // Set images
        // Your Picture (Selfie)
        document.getElementById('verYourPicture').src = verification.selfie 
            || verification.validIdSelfieUrl 
            || verification.profilePhoto 
            || verification.profile_picture 
            || '';
        document.getElementById('verYourPicture').src = verification.selfie || '';

        // Driver's License
        document.getElementById('verLicenseFront').src = verification.seniorIdFrontUrl 
            || verification.validIdFrontUrl 
            || verification.front_image 
            || verification.driverLicenseFrontUrl 
            || verification.licensesFrontUrl 
            || '';
        
        document.getElementById('verLicenseBack').src = verification.seniorIdBackUrl 
            || verification.validIdBackUrl 
            || verification.back_image 
            || verification.driverLicenseBackUrl 
            || verification.licensesBackUrl 
            || '';

        // Vehicle OR/CR
        document.getElementById('verVehicleORCRFront').src = verification.vehicleORCRFrontUrl 
            || verification.vehicleRegistrationFront 
            || verification.vehicleORFront 
            || verification.carRegistrationFront 
            || '';
        
        document.getElementById('verVehicleORCRBack').src = verification.vehicleORCRBackUrl 
            || verification.vehicleRegistrationBack 
            || verification.vehicleORBack 
            || verification.carRegistrationBack 
            || '';
        document.getElementById('verVehicleORCRFront').src = verification.vehicleORCRFrontUrl || '';
        document.getElementById('verVehicleORCRBack').src = verification.vehicleORCRBackUrl || '';

        // Handle Senior ID specific view for customers
        const sectionYourPicture = document.getElementById('sectionYourPicture');
        const sectionIdDocument = document.getElementById('sectionIdDocument');
        const headerIdDocument = document.getElementById('headerIdDocument');
        const sectionVehicleORCR = document.getElementById('sectionVehicleORCR');

        if (verification.user_role === 'customer' && (verification.id_type === 'Senior ID' || verification.id_type === 'Valid ID')) {
            sectionYourPicture.style.display = 'none';
            sectionVehicleORCR.style.display = 'none';
            headerIdDocument.innerHTML = '<i class="bi bi-card-heading"></i> ' + verification.id_type;
        } else {
            sectionYourPicture.style.display = 'block';
            sectionVehicleORCR.style.display = 'block';
            headerIdDocument.innerHTML = '<i class="bi bi-card-heading"></i> Driver\'s License';
        }

        // Show/hide rider details section based on user role
        const riderDetailsSection = document.getElementById('riderDetailsSection');
        if (verification.user_role === 'rider') {
            riderDetailsSection.style.display = 'block';
            // Populate rider-specific fields
            document.getElementById('verVehicleType').textContent = verification.vehicleType || verification.vehicle_type || 'N/A';
            document.getElementById('verLicensePlate').textContent = verification.licensePlate || verification.license_plate || verification.plateNumber || 'N/A';
            document.getElementById('verRating').textContent = verification.rating ? verification.rating.toFixed(1) + ' ⭐' : 'N/A';
            document.getElementById('verTotalTrips').textContent = verification.totalTrips || verification.total_trips || verification.completedRides || 'N/A';
            const accountStatusEl = document.getElementById('verAccountStatus');
            accountStatusEl.innerHTML = verification.user_status === 'active' ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-warning">Pending</span>';
            const idVerStatusEl = document.getElementById('verIdVerificationStatus');
            idVerStatusEl.innerHTML = (verification.id_verified === true || verification.id_verified === 'true') ? '<span class="badge bg-success">Verified</span>' : '<span class="badge bg-warning">Unverified</span>';
        } else {
            riderDetailsSection.style.display = 'none';
        }

        // Show/hide action buttons based on status
        const actionButtons = document.getElementById('actionButtons');
        const approveBtn = document.getElementById('approveBtn');
        const rejectBtn = document.getElementById('rejectBtn');
        const unverifyBtn = document.getElementById('unverifyBtn');
        const noteSection = document.getElementById('noteSection');
        const rejectionSection = document.getElementById('rejectionSection');
        const submitRejectBtn = document.getElementById('submitRejectBtn');
        const cancelRejectBtn = document.getElementById('cancelRejectBtn');
        const submitUnverifyBtn = document.getElementById('submitUnverifyBtn');
        const cancelUnverifyBtn = document.getElementById('cancelUnverifyBtn');

        // Reset all buttons and sections first
        approveBtn.style.display = 'none';
        rejectBtn.style.display = 'none';
        unverifyBtn.style.display = 'none';
        noteSection.style.display = 'none';
        rejectionSection.style.display = 'none';
        submitRejectBtn.style.display = 'none';
        cancelRejectBtn.style.display = 'none';
        submitUnverifyBtn.style.display = 'none';
        cancelUnverifyBtn.style.display = 'none';

        if (verification.status === 'pending') {
            actionButtons.style.display = 'block';
            approveBtn.style.display = 'inline-block';
            rejectBtn.style.display = 'inline-block';
        } else if (verification.status === 'approved') {
            actionButtons.style.display = 'block';
            unverifyBtn.style.display = 'inline-block';
        } else if (verification.status === 'rejected') {
            actionButtons.style.display = 'block';
            rejectionSection.style.display = 'block';
            document.getElementById('rejectionReason').textContent = verification.admin_note;
        } else {
            actionButtons.style.display = 'none';
        }
    }

    function openImageModal(src) {
        document.getElementById('enlargedImage').src = src;
        new bootstrap.Modal(document.getElementById('imageModal')).show();
    }

    function toggleRejectForm() {
        const form = document.getElementById('noteSection');
        const label = document.getElementById('adminNoteLabel');
        const textarea = document.getElementById('adminNote');
        const approveBtn = document.getElementById('approveBtn');
        const rejectBtn = document.getElementById('rejectBtn');
        const submitBtn = document.getElementById('submitRejectBtn');
        const cancelBtn = document.getElementById('cancelRejectBtn');

        if (form.style.display === 'none') {
            label.textContent = 'Rejection Reason *';
            textarea.placeholder = 'Explain why this verification is being rejected...';
            form.style.display = 'block';
            approveBtn.style.display = 'none';
            rejectBtn.style.display = 'none';
            submitBtn.style.display = 'inline-block';
            cancelBtn.style.display = 'inline-block';
        } else {
            form.style.display = 'none';
            approveBtn.style.display = 'inline-block';
            rejectBtn.style.display = 'inline-block';
            submitBtn.style.display = 'none';
            cancelBtn.style.display = 'none';
            document.getElementById('adminNote').value = '';
        }
    }

    function toggleUnverifyForm() {
        const form = document.getElementById('noteSection');
        const label = document.getElementById('adminNoteLabel');
        const textarea = document.getElementById('adminNote');
        const unverifyBtn = document.getElementById('unverifyBtn');
        const submitBtn = document.getElementById('submitUnverifyBtn');
        const cancelBtn = document.getElementById('cancelUnverifyBtn');

        if (form.style.display === 'none') {
            label.textContent = 'Reason for Unverifying *';
            textarea.placeholder = 'Explain why this user is being unverified...';
            form.style.display = 'block';
            unverifyBtn.style.display = 'none';
            submitBtn.style.display = 'inline-block';
            cancelBtn.style.display = 'inline-block';
        } else {
            form.style.display = 'none';
            unverifyBtn.style.display = 'inline-block';
            submitBtn.style.display = 'none';
            cancelBtn.style.display = 'none';
            textarea.value = '';
        }
    }

    function approveVerification() {
        const form = document.getElementById('verificationForm');
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'action';
        input.value = 'approve';
        form.appendChild(input);
        form.submit();
    }
</script>

<?php require_once 'includes/footer.php'; ?>
