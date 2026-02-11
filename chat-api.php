<?php
/**
 * Chat API Endpoint
 * Handles sending messages, fetching conversations, and chat operations
 */

session_start();

require_once 'includes/db.php';
require_once 'includes/chat-helper.php';

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
        case 'send-message':
            $response = handleSendMessage();
            break;
            
        case 'get-conversations':
            $response = handleGetConversations();
            break;
            
        case 'get-messages':
            $response = handleGetMessages();
            break;
            
        case 'mark-read':
            $response = handleMarkRead();
            break;
            
        case 'get-conversation':
            $response = handleGetConversation();
            break;
            
        case 'delete-message':
            $response = handleDeleteMessage();
            break;
            
        case 'get-available-users':
            $response = handleGetAvailableUsers();
            break;
            
        default:
            $response = ['success' => false, 'message' => 'Unknown action'];
    }
} catch (Exception $e) {
    error_log('Chat API Error: ' . $e->getMessage());
    $response = ['success' => false, 'message' => 'Server error: ' . $e->getMessage()];
}

header('Content-Type: application/json');
echo json_encode($response);
exit;

/**
 * Send a new message
 */
function handleSendMessage() {
    global $pdo;
    
    $senderId = $_SESSION['user_id'];
    $otherUserId = $_POST['otherUserId'] ?? null;
    $message = $_POST['message'] ?? null;
    
    if (!$otherUserId || !$message) {
        return ['success' => false, 'message' => 'Missing required fields'];
    }
    
    $message = htmlspecialchars(trim($message), ENT_QUOTES, 'UTF-8');
    
    if (empty($message)) {
        return ['success' => false, 'message' => 'Message cannot be empty'];
    }
    
    $conversationId = generateConversationId($senderId, $otherUserId);
    getOrCreateConversation($pdo, $senderId, $otherUserId);
    
    $messageId = sendMessage($pdo, $conversationId, $senderId, $message, 'text');
    
    if ($messageId) {
        return [
            'success' => true,
            'message' => 'Message sent',
            'messageId' => $messageId,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    return ['success' => false, 'message' => 'Failed to send message'];
}

/**
 * Get all conversations for the current user
 */
function handleGetConversations() {
    global $pdo;
    
    $userId = $_SESSION['user_id'];
    $conversations = getUserConversations($pdo, $userId);
    
    $result = [];
    foreach ($conversations as $conv) {
        $otherUserInfo = getChatUserInfo($pdo, $conv['otherParticipantId']);
        
        $result[] = [
            'id' => $conv['id'],
            'otherUserId' => $conv['otherParticipantId'],
            'otherUserInfo' => $otherUserInfo,
            'lastMessage' => $conv['lastMessage'] ?? '',
            'lastMessageTime' => $conv['lastMessageTime'] ?? null,
            'unreadCount' => $conv['unreadCount'] ?? 0,
        ];
    }
    
    return ['success' => true, 'conversations' => $result];
}

/**
 * Get messages from a conversation
 */
function handleGetMessages() {
    global $pdo;
    
    $conversationId = $_GET['conversationId'] ?? null;
    
    if (!$conversationId) {
        return ['success' => false, 'message' => 'Conversation ID required'];
    }
    
    $messages = getConversationMessages($pdo, $conversationId, 100);
    
    return [
        'success' => true,
        'messages' => $messages ?: []
    ];
}

/**
 * Mark messages as read
 */
function handleMarkRead() {
    global $pdo;
    
    $conversationId = $_POST['conversationId'] ?? null;
    $userId = $_SESSION['user_id'];
    
    if (!$conversationId) {
        return ['success' => false, 'message' => 'Conversation ID required'];
    }
    
    markMessagesAsRead($pdo, $conversationId, $userId);
    
    return ['success' => true];
}

/**
 * Get a specific conversation with metadata
 */
function handleGetConversation() {
    global $pdo;
    
    $conversationId = $_GET['conversationId'] ?? null;
    
    if (!$conversationId) {
        return ['success' => false, 'message' => 'Conversation ID required'];
    }
    
    $conversation = $pdo->getDocument('conversations', $conversationId);
    
    if (!$conversation) {
        return ['success' => false, 'message' => 'Conversation not found'];
    }
    
    $otherUserId = ($_SESSION['user_id'] === $conversation['participant1']) 
        ? $conversation['participant2'] 
        : $conversation['participant1'];
    
    $otherUserInfo = getChatUserInfo($pdo, $otherUserId);
    
    return [
        'success' => true,
        'conversation' => [
            'id' => $conversationId,
            'otherUserInfo' => $otherUserInfo,
            'createdAt' => $conversation['createdAt'] ?? null,
        ]
    ];
}

/**
 * Delete a message
 */
function handleDeleteMessage() {
    global $pdo;
    
    $conversationId = $_POST['conversationId'] ?? null;
    $messageId = $_POST['messageId'] ?? null;
    
    if (!$conversationId || !$messageId) {
        return ['success' => false, 'message' => 'Missing required fields'];
    }
    
    deleteMessage($pdo, $conversationId, $messageId);
    
    return ['success' => true, 'message' => 'Message deleted'];
}

/**
 * Get available users for new chat
 */
function handleGetAvailableUsers() {
    global $pdo;
    
    $userId = $_SESSION['user_id'];
    $users = [];
    
    try {
        // Get all users from different collections
        $allUsers = $pdo->getAllDocuments('Users') ?? [];
        $allRiders = $pdo->getAllDocuments('Riders') ?? [];
        $allLegacyUsers = $pdo->getAllDocuments('users') ?? [];
        
        // Combine all users
        $allUsersData = array_merge($allUsers, $allRiders, $allLegacyUsers);
        
        foreach ($allUsersData as $id => $user) {
            if ($id !== $userId && isset($user['name'])) {
                $users[] = [
                    'id' => $id,
                    'name' => $user['name'] ?? $user['username'] ?? 'Unknown',
                    'role' => $user['role'] ?? 'customer',
                    'avatar' => $user['profileImagePath'] ?? $user['profilePictureUrl'] ?? null,
                ];
            }
        }
        
        return [
            'success' => true,
            'users' => $users
        ];
    } catch (Exception $e) {
        error_log('Error in handleGetAvailableUsers: ' . $e->getMessage());
        return ['success' => false, 'users' => []];;
    }
}

?>
