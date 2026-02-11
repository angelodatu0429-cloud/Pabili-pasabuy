<?php
/**
 * Support API Endpoint
 * Handles support ticket operations
 */

session_start();

require_once 'includes/db.php';
require_once 'includes/support-helper.php';

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? null;
$response = ['success' => false, 'message' => 'Invalid action'];

try {
    switch ($action) {
        case 'create-ticket':
            $response = handleCreateTicket();
            break;
            
        case 'add-message':
            $response = handleAddMessage();
            break;
            
        case 'get-tickets':
            $response = handleGetTickets();
            break;
            
        case 'get-ticket':
            $response = handleGetTicket();
            break;
            
        case 'update-status':
            $response = handleUpdateStatus();
            break;
            
        default:
            $response = ['success' => false, 'message' => 'Unknown action'];
    }
} catch (Exception $e) {
    error_log('Support API Error: ' . $e->getMessage());
    $response = ['success' => false, 'message' => 'Server error: ' . $e->getMessage()];
}

header('Content-Type: application/json');
echo json_encode($response);
exit;

/**
 * Create a new support ticket
 */
function handleCreateTicket() {
    global $pdo;
    
    $userId = $_SESSION['user_id'];
    $subject = $_POST['subject'] ?? null;
    $category = $_POST['category'] ?? null;
    $priority = $_POST['priority'] ?? 'medium';
    $description = $_POST['description'] ?? null;
    
    if (!$subject || !$category || !$description) {
        return ['success' => false, 'message' => 'Missing required fields'];
    }
    
    $subject = htmlspecialchars(trim($subject), ENT_QUOTES, 'UTF-8');
    $description = htmlspecialchars(trim($description), ENT_QUOTES, 'UTF-8');
    $category = htmlspecialchars(trim($category), ENT_QUOTES, 'UTF-8');
    $priority = htmlspecialchars(trim($priority), ENT_QUOTES, 'UTF-8');
    
    $ticketId = createSupportTicket($pdo, $userId, $subject, $description, $category, $priority);
    
    if ($ticketId) {
        return [
            'success' => true,
            'message' => 'Ticket created successfully',
            'ticketId' => $ticketId
        ];
    }
    
    return ['success' => false, 'message' => 'Failed to create ticket'];
}

/**
 * Add message to ticket
 */
function handleAddMessage() {
    global $pdo;
    
    $userId = $_SESSION['user_id'];
    $ticketId = $_POST['ticketId'] ?? null;
    $message = $_POST['message'] ?? null;
    
    if (!$ticketId || !$message) {
        return ['success' => false, 'message' => 'Missing required fields'];
    }
    
    // Verify ownership
    $ticket = getSupportTicket($pdo, $ticketId);
    if (!$ticket || $ticket['userId'] !== $userId) {
        return ['success' => false, 'message' => 'Unauthorized'];
    }
    
    $message = htmlspecialchars(trim($message), ENT_QUOTES, 'UTF-8');
    
    if (empty($message)) {
        return ['success' => false, 'message' => 'Message cannot be empty'];
    }
    
    $messageId = addTicketMessage($pdo, $ticketId, $userId, $message);
    
    if ($messageId) {
        return [
            'success' => true,
            'message' => 'Message added',
            'messageId' => $messageId
        ];
    }
    
    return ['success' => false, 'message' => 'Failed to add message'];
}

/**
 * Get all tickets for user
 */
function handleGetTickets() {
    global $pdo;
    
    $userId = $_SESSION['user_id'];
    $tickets = getUserSupportTickets($pdo, $userId);
    
    return [
        'success' => true,
        'tickets' => $tickets
    ];
}

/**
 * Get a specific ticket
 */
function handleGetTicket() {
    global $pdo;
    
    $userId = $_SESSION['user_id'];
    $ticketId = $_GET['ticketId'] ?? null;
    
    if (!$ticketId) {
        return ['success' => false, 'message' => 'Ticket ID required'];
    }
    
    $ticket = getSupportTicket($pdo, $ticketId);
    
    if (!$ticket) {
        return ['success' => false, 'message' => 'Ticket not found'];
    }
    
    // Verify ownership or admin access
    if ($ticket['userId'] !== $userId && $_SESSION['role'] !== 'admin') {
        return ['success' => false, 'message' => 'Unauthorized'];
    }
    
    $messages = getTicketMessages($pdo, $ticketId);
    
    return [
        'success' => true,
        'ticket' => $ticket,
        'messages' => $messages
    ];
}

/**
 * Update ticket status (admin only)
 */
function handleUpdateStatus() {
    global $pdo;
    
    if ($_SESSION['role'] !== 'admin') {
        return ['success' => false, 'message' => 'Unauthorized'];
    }
    
    $ticketId = $_POST['ticketId'] ?? null;
    $status = $_POST['status'] ?? null;
    
    if (!$ticketId || !$status) {
        return ['success' => false, 'message' => 'Missing required fields'];
    }
    
    $success = updateTicketStatus($pdo, $ticketId, $status);
    
    if ($success) {
        return ['success' => true, 'message' => 'Ticket status updated'];
    }
    
    return ['success' => false, 'message' => 'Failed to update status'];
}

?>
