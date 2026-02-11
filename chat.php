<?php
/**
 * Chat Support Page
 * Main chat interface for customers and riders
 */

session_start();

require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/chat-helper.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$conversations = getUserConversations($pdo, $userId);
$selectedConversationId = $_GET['conversation'] ?? null;
$selectedMessages = [];

if ($selectedConversationId) {
    markMessagesAsRead($pdo, $selectedConversationId, $userId);
    $selectedMessages = getConversationMessages($pdo, $selectedConversationId, 100);
}

$pageTitle = 'Chat Support';
require_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row h-100" style="min-height: 90vh;">
        <!-- Conversations List -->
        <div class="col-md-4 border-end" style="overflow-y: auto; max-height: 90vh;">
            <div class="p-3">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="mb-0">
                        <i class="fas fa-comments"></i> Messages
                    </h5>
                    <button class="btn btn-sm btn-outline-primary" id="newChatBtn" data-bs-toggle="modal" data-bs-target="#newChatModal">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>

                <!-- Search Conversations -->
                <div class="input-group input-group-sm mb-3">
                    <input type="text" class="form-control" id="conversationSearch" placeholder="Search conversations...">
                    <span class="input-group-text bg-transparent border-end-0">
                        <i class="fas fa-search text-muted"></i>
                    </span>
                </div>

                <!-- Conversations List -->
                <div id="conversationsList" class="list-group list-group-flush">
                    <?php if (!empty($conversations)): ?>
                        <?php foreach ($conversations as $conv): ?>
                            <?php
                            $otherUserInfo = getChatUserInfo($pdo, $conv['otherParticipantId']);
                            $isSelected = $selectedConversationId === $conv['id'];
                            ?>
                            <a href="?conversation=<?php echo urlencode($conv['id']); ?>" 
                               class="list-group-item list-group-item-action py-3 conversation-item <?php echo $isSelected ? 'active' : ''; ?>" 
                               data-conversation-id="<?php echo htmlspecialchars($conv['id']); ?>">
                                <div class="d-flex w-100 justify-content-between align-items-center">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">
                                            <?php echo htmlspecialchars($otherUserInfo['name'] ?? 'Unknown User'); ?>
                                            <?php if ($conv['unreadCount'] > 0): ?>
                                                <span class="badge bg-primary rounded-pill ms-2"><?php echo $conv['unreadCount']; ?></span>
                                            <?php endif; ?>
                                        </h6>
                                        <small class="text-muted text-truncate d-block" style="max-width: 100%;">
                                            <?php echo htmlspecialchars(substr($conv['lastMessage'] ?? '(No messages)', 0, 50)); ?>
                                            <?php if (strlen($conv['lastMessage'] ?? '') > 50): ?>...<?php endif; ?>
                                        </small>
                                    </div>
                                    <?php if ($conv['lastMessageTime']): ?>
                                        <small class="text-muted ms-2 text-nowrap">
                                            <?php echo formatRelativeTime($conv['lastMessageTime']); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <p class="text-muted small">
                                <i class="fas fa-inbox"></i><br>
                                No conversations yet
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Chat Window -->
        <div class="col-md-8 d-flex flex-column" style="max-height: 90vh;">
            <?php if ($selectedConversationId && !empty($selectedMessages)): ?>
                <?php
                $conversation = $pdo->getDocument('conversations', $selectedConversationId);
                $otherUserId = ($conversation['participant1'] === $userId) 
                    ? $conversation['participant2'] 
                    : $conversation['participant1'];
                $otherUserInfo = getChatUserInfo($pdo, $otherUserId);
                ?>
                
                <!-- Chat Header -->
                <div class="bg-light border-bottom p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="mb-0">
                                <?php echo htmlspecialchars($otherUserInfo['name'] ?? 'Unknown User'); ?>
                            </h6>
                            <small class="text-muted">
                                <?php 
                                echo ucfirst($otherUserInfo['role'] ?? 'User');
                                if ($otherUserInfo['status'] === 'online') {
                                    echo ' <span class="badge bg-success ms-1">Online</span>';
                                }
                                ?>
                            </small>
                        </div>
                        <div>
                            <button class="btn btn-sm btn-outline-secondary" title="Call">
                                <i class="fas fa-phone"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary ms-2" title="Video Call">
                                <i class="fas fa-video"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary ms-2" id="infoBtn" title="Info">
                                <i class="fas fa-info-circle"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Messages Area -->
                <div class="flex-grow-1 p-3" id="messagesContainer" style="overflow-y: auto; background-color: #f8f9fa;">
                    <?php foreach ($selectedMessages as $msg): ?>
                        <?php
                        $isSender = $msg['senderId'] === $userId;
                        ?>
                        <div class="mb-3 d-flex <?php echo $isSender ? 'justify-content-end' : 'justify-content-start'; ?>">
                            <div class="<?php echo $isSender ? 'bg-primary text-white' : 'bg-white border'; ?> rounded-lg p-2 px-3" style="max-width: 70%; margin: 0;">
                                <p class="mb-1 small">
                                    <?php echo htmlspecialchars($msg['message']); ?>
                                </p>
                                <small class="<?php echo $isSender ? 'text-white-50' : 'text-muted'; ?>">
                                    <?php 
                                    if (isset($msg['timestamp'])) {
                                        echo date('H:i', strtotime($msg['timestamp']));
                                    }
                                    ?>
                                </small>
                                <?php if ($isSender): ?>
                                    <small class="ms-2">
                                        <?php echo $msg['isRead'] ? '<i class="fas fa-check-double text-info"></i>' : '<i class="fas fa-check text-white-50"></i>'; ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Message Input -->
                <div class="border-top bg-white p-3">
                    <form id="sendMessageForm" onsubmit="sendMessage(event)">
                        <div class="input-group">
                            <input type="text" 
                                   class="form-control" 
                                   id="messageInput" 
                                   placeholder="Type a message..." 
                                   required>
                            <button class="btn btn-primary" type="submit" id="sendBtn">
                                <i class="fas fa-paper-plane"></i> Send
                            </button>
                        </div>
                        <input type="hidden" id="otherUserId" value="<?php echo htmlspecialchars($otherUserId); ?>">
                        <input type="hidden" id="conversationId" value="<?php echo htmlspecialchars($selectedConversationId); ?>">
                    </form>
                </div>
            <?php else: ?>
                <!-- Empty State -->
                <div class="d-flex flex-column align-items-center justify-content-center h-100 bg-light">
                    <i class="fas fa-comments display-4 text-muted mb-3"></i>
                    <h5 class="text-muted mb-2">No conversation selected</h5>
                    <p class="text-muted small">Select a conversation or start a new chat</p>
                    <button class="btn btn-primary btn-sm mt-3" data-bs-toggle="modal" data-bs-target="#newChatModal">
                        <i class="fas fa-plus me-1"></i> New Chat
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- New Chat Modal -->
<div class="modal fade" id="newChatModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Start New Chat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="userSelect" class="form-label">Select User</label>
                    <select class="form-select" id="userSelect" required>
                        <option value="">Choose a user...</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="startNewChat()">Start Chat</button>
            </div>
        </div>
    </div>
</div>

<style>
    .list-group-item:hover {
        background-color: #f8f9fa;
    }

    .conversation-item.active {
        background-color: #0d6efd !important;
        color: white;
    }

    .rounded-lg {
        border-radius: 12px;
    }

    #messagesContainer {
        padding: 20px;
    }

    #messageInput {
        border-radius: 20px;
        padding: 10px 20px;
    }

    .btn-outline-secondary:hover {
        color: #6c757d;
        border-color: #6c757d;
    }
</style>

<script>
const conversationId = document.getElementById('conversationId')?.value;
const otherUserId = document.getElementById('otherUserId')?.value;

/**
 * Send a message
 */
function sendMessage(event) {
    event.preventDefault();
    
    const messageInput = document.getElementById('messageInput');
    const message = messageInput.value.trim();
    const sendBtn = document.getElementById('sendBtn');
    
    if (!message || !otherUserId) {
        return;
    }

    sendBtn.disabled = true;
    
    fetch('chat-api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=send-message&otherUserId=' + encodeURIComponent(otherUserId) + 
              '&message=' + encodeURIComponent(message)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            messageInput.value = '';
            // Reload messages
            loadMessages();
        } else {
            alert('Error sending message: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to send message');
    })
    .finally(() => {
        sendBtn.disabled = false;
    });
}

/**
 * Load messages for current conversation
 */
function loadMessages() {
    if (!conversationId) return;
    
    fetch('chat-api.php?action=get-messages&conversationId=' + encodeURIComponent(conversationId))
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // In a real app, you'd update the DOM here
                // For now, just reload the page
                location.reload();
            }
        })
        .catch(error => console.error('Error loading messages:', error));
}

/**
 * Start a new chat
 */
function startNewChat() {
    const userSelect = document.getElementById('userSelect');
    const userId = userSelect.value;
    
    if (!userId) {
        alert('Please select a user');
        return;
    }
    
    // Redirect to chat with the selected user
    window.location.href = 'chat.php?user=' + encodeURIComponent(userId);
}

/**
 * Load available users for new chat
 */
document.addEventListener('DOMContentLoaded', function() {
    loadAvailableUsers();
    
    // Auto-refresh messages every 3 seconds
    if (conversationId) {
        setInterval(loadMessages, 3000);
    }
});

function loadAvailableUsers() {
    // This would fetch available users to start a chat with
    // For now, we'll use a static approach
    fetch('chat-api.php?action=get-available-users')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.users) {
                const userSelect = document.getElementById('userSelect');
                data.users.forEach(user => {
                    const option = document.createElement('option');
                    option.value = user.id;
                    option.textContent = user.name + ' (' + user.role + ')';
                    userSelect.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error loading users:', error));
}

// Search conversations
document.getElementById('conversationSearch')?.addEventListener('keyup', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    document.querySelectorAll('.conversation-item').forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(searchTerm) ? 'block' : 'none';
    });
});
</script>

<?php
require_once 'includes/footer.php';

/**
 * Format relative time (e.g., "2 hours ago")
 */
function formatRelativeTime($dateString) {
    $timestamp = strtotime($dateString);
    $now = time();
    $diff = $now - $timestamp;

    if ($diff < 60) {
        return 'now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . 'm ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . 'h ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . 'd ago';
    } else {
        return date('M d', $timestamp);
    }
}
?>
