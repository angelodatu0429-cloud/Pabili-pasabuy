<?php
/**
 * Support Tickets Page
 * View and manage support tickets with integrated chat
 */

session_start();

require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/support-helper.php';
require_once 'includes/chat-helper.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userTickets = getUserSupportTickets($pdo, $userId);
$selectedTicketId = $_GET['ticket'] ?? null;
$selectedTicketMessages = [];
$selectedTicket = null;

if ($selectedTicketId) {
    $selectedTicket = getSupportTicket($pdo, $selectedTicketId);
    if ($selectedTicket) {
        $selectedTicketMessages = getTicketMessages($pdo, $selectedTicketId);
    }
}

$pageTitle = 'Support Tickets';
require_once 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row h-100" style="min-height: 85vh;">
        <!-- Tickets List -->
        <div class="col-md-4 border-end" style="overflow-y: auto; max-height: 85vh;">
            <div class="p-4">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h5 class="mb-0">
                        <i class="fas fa-ticket-alt"></i> Support Tickets
                    </h5>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newTicketModal">
                        <i class="fas fa-plus"></i> New
                    </button>
                </div>

                <!-- Filter Tabs -->
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all-tickets">
                            All
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="open-tab" data-bs-toggle="tab" data-bs-target="#open-tickets">
                            Open
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="resolved-tab" data-bs-toggle="tab" data-bs-target="#resolved-tickets">
                            Resolved
                        </button>
                    </li>
                </ul>

                <!-- Tickets List -->
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="all-tickets">
                        <?php if (!empty($userTickets)): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($userTickets as $ticket): ?>
                                    <?php
                                    $isSelected = $selectedTicketId === $ticket['id'];
                                    $statusColor = [
                                        'open' => 'primary',
                                        'in-progress' => 'warning',
                                        'resolved' => 'success',
                                        'closed' => 'secondary',
                                    ][$ticket['status']] ?? 'secondary';
                                    ?>
                                    <a href="?ticket=<?php echo urlencode($ticket['id']); ?>" 
                                       class="list-group-item list-group-item-action py-3 <?php echo $isSelected ? 'active' : ''; ?>">
                                        <div class="d-flex w-100 justify-content-between align-items-start mb-2">
                                            <h6 class="mb-0"><?php echo htmlspecialchars($ticket['subject']); ?></h6>
                                            <span class="badge bg-<?php echo $statusColor; ?>">
                                                <?php echo ucfirst($ticket['status']); ?>
                                            </span>
                                        </div>
                                        <small class="text-muted d-block mb-2">
                                            <strong><?php echo htmlspecialchars(getSupportCategories()[$ticket['category']] ?? $ticket['category']); ?></strong>
                                        </small>
                                        <p class="text-muted small mb-2">
                                            <?php echo htmlspecialchars(substr($ticket['description'], 0, 60)); ?>
                                            <?php if (strlen($ticket['description']) > 60): ?>...<?php endif; ?>
                                        </p>
                                        <small class="text-muted">
                                            <i class="fas fa-clock"></i>
                                            <?php echo formatRelativeTime($ticket['createdAt']); ?>
                                        </small>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <p class="text-muted small">
                                    <i class="fas fa-inbox"></i><br>
                                    No support tickets
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Open Tickets Tab -->
                    <div class="tab-pane fade" id="open-tickets">
                        <?php 
                        $openTickets = array_filter($userTickets, function($t) {
                            return in_array($t['status'], ['open', 'in-progress']);
                        });
                        ?>
                        <?php if (!empty($openTickets)): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($openTickets as $ticket): ?>
                                    <a href="?ticket=<?php echo urlencode($ticket['id']); ?>" 
                                       class="list-group-item list-group-item-action py-3">
                                        <h6 class="mb-2"><?php echo htmlspecialchars($ticket['subject']); ?></h6>
                                        <small class="text-muted d-block">
                                            <?php echo htmlspecialchars(substr($ticket['description'], 0, 50)); ?>
                                        </small>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info small">No open tickets</div>
                        <?php endif; ?>
                    </div>

                    <!-- Resolved Tickets Tab -->
                    <div class="tab-pane fade" id="resolved-tickets">
                        <?php 
                        $resolvedTickets = array_filter($userTickets, function($t) {
                            return in_array($t['status'], ['resolved', 'closed']);
                        });
                        ?>
                        <?php if (!empty($resolvedTickets)): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($resolvedTickets as $ticket): ?>
                                    <a href="?ticket=<?php echo urlencode($ticket['id']); ?>" 
                                       class="list-group-item list-group-item-action py-3">
                                        <h6 class="mb-2"><?php echo htmlspecialchars($ticket['subject']); ?></h6>
                                        <small class="text-muted d-block">
                                            Resolved: <?php echo formatDate($ticket['resolvedAt']); ?>
                                        </small>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info small">No resolved tickets</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ticket Detail -->
        <div class="col-md-8 d-flex flex-column" style="max-height: 85vh;">
            <?php if ($selectedTicket && $selectedTicketId): ?>
                <!-- Ticket Header -->
                <div class="bg-light border-bottom p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="mb-2"><?php echo htmlspecialchars($selectedTicket['subject']); ?></h5>
                            <p class="text-muted small mb-0">
                                Ticket ID: <code><?php echo htmlspecialchars($selectedTicketId); ?></code>
                            </p>
                        </div>
                        <span class="badge bg-<?php echo [
                            'open' => 'primary',
                            'in-progress' => 'warning',
                            'resolved' => 'success',
                            'closed' => 'secondary',
                        ][$selectedTicket['status']] ?? 'secondary'; ?>" style="font-size: 0.9rem;">
                            <?php echo ucfirst($selectedTicket['status']); ?>
                        </span>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <small class="text-muted d-block mb-1">Category</small>
                            <strong><?php echo htmlspecialchars(getSupportCategories()[$selectedTicket['category']] ?? $selectedTicket['category']); ?></strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block mb-1">Priority</small>
                            <span class="badge bg-<?php echo [
                                'low' => 'secondary',
                                'medium' => 'info',
                                'high' => 'warning',
                                'urgent' => 'danger',
                            ][$selectedTicket['priority']] ?? 'secondary'; ?>">
                                <?php echo ucfirst($selectedTicket['priority']); ?>
                            </span>
                        </div>
                    </div>

                    <hr class="my-3">

                    <p class="mb-0">
                        <strong>Description:</strong><br>
                        <span class="text-muted"><?php echo nl2br(htmlspecialchars($selectedTicket['description'])); ?></span>
                    </p>
                </div>

                <!-- Messages Area -->
                <div class="flex-grow-1 p-4" id="ticketMessagesContainer" style="overflow-y: auto; background-color: #f8f9fa;">
                    <?php foreach ($selectedTicketMessages as $msg): ?>
                        <?php
                        $isSender = $msg['userId'] === $userId;
                        $msgUser = getChatUserInfo($pdo, $msg['userId']);
                        ?>
                        <div class="mb-4 d-flex <?php echo $isSender ? 'justify-content-end' : 'justify-content-start'; ?>">
                            <div class="card <?php echo $isSender ? 'bg-primary text-white' : ''; ?>" style="max-width: 70%;">
                                <div class="card-body">
                                    <?php if (!$isSender): ?>
                                        <strong class="d-block mb-1"><?php echo htmlspecialchars($msgUser['name'] ?? 'Support Staff'); ?></strong>
                                    <?php endif; ?>
                                    <p class="card-text mb-2"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                                    <small class="<?php echo $isSender ? 'text-muted' : 'text-muted'; ?>">
                                        <?php echo date('M d, Y H:i', strtotime($msg['timestamp'])); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Message Input -->
                <?php if (!in_array($selectedTicket['status'], ['resolved', 'closed'])): ?>
                    <div class="border-top bg-white p-4">
                        <form id="sendTicketMessageForm" onsubmit="sendTicketMessage(event)">
                            <div class="input-group">
                                <textarea class="form-control" 
                                          id="ticketMessageInput" 
                                          placeholder="Type your message..." 
                                          rows="3" 
                                          required></textarea>
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-paper-plane"></i> Send
                                </button>
                            </div>
                            <input type="hidden" id="ticketId" value="<?php echo htmlspecialchars($selectedTicketId); ?>">
                        </form>
                    </div>
                <?php else: ?>
                    <div class="bg-light p-4 text-center">
                        <p class="text-muted small mb-0">
                            <i class="fas fa-check-circle"></i> This ticket is closed
                        </p>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <!-- Empty State -->
                <div class="d-flex flex-column align-items-center justify-content-center h-100 bg-light">
                    <i class="fas fa-ticket-alt display-4 text-muted mb-3"></i>
                    <h5 class="text-muted mb-2">No ticket selected</h5>
                    <p class="text-muted small">Select a ticket or create a new one</p>
                    <button class="btn btn-primary btn-sm mt-3" data-bs-toggle="modal" data-bs-target="#newTicketModal">
                        <i class="fas fa-plus me-1"></i> New Ticket
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- New Ticket Modal -->
<div class="modal fade" id="newTicketModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Support Ticket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="newTicketForm" onsubmit="createNewTicket(event)">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="ticketSubject" class="form-label">Subject *</label>
                        <input type="text" class="form-control" id="ticketSubject" required>
                    </div>
                    <div class="mb-3">
                        <label for="ticketCategory" class="form-label">Category *</label>
                        <select class="form-select" id="ticketCategory" required>
                            <option value="">Select category...</option>
                            <?php foreach (getSupportCategories() as $key => $label): ?>
                                <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="ticketPriority" class="form-label">Priority</label>
                        <select class="form-select" id="ticketPriority">
                            <?php foreach (getSupportPriorities() as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo $key === 'medium' ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="ticketDescription" class="form-label">Description *</label>
                        <textarea class="form-control" id="ticketDescription" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Ticket</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .card {
        border-radius: 12px;
    }

    .list-group-item:hover {
        background-color: #f8f9fa;
    }

    .list-group-item.active {
        background-color: #0d6efd;
        color: white;
    }

    code {
        background-color: #f0f0f0;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.85rem;
    }
</style>

<script>
/**
 * Send message in ticket
 */
function sendTicketMessage(event) {
    event.preventDefault();
    
    const messageInput = document.getElementById('ticketMessageInput');
    const ticketId = document.getElementById('ticketId').value;
    const message = messageInput.value.trim();
    
    if (!message || !ticketId) {
        return;
    }

    fetch('support-api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=add-message&ticketId=' + encodeURIComponent(ticketId) + 
              '&message=' + encodeURIComponent(message)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            messageInput.value = '';
            location.reload();
        } else {
            alert('Error sending message: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to send message');
    });
}

/**
 * Create new ticket
 */
function createNewTicket(event) {
    event.preventDefault();
    
    const subject = document.getElementById('ticketSubject').value;
    const category = document.getElementById('ticketCategory').value;
    const priority = document.getElementById('ticketPriority').value;
    const description = document.getElementById('ticketDescription').value;
    
    if (!subject || !category || !description) {
        alert('Please fill in all required fields');
        return;
    }

    fetch('support-api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=create-ticket&subject=' + encodeURIComponent(subject) + 
              '&category=' + encodeURIComponent(category) + 
              '&priority=' + encodeURIComponent(priority) + 
              '&description=' + encodeURIComponent(description)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error creating ticket: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to create ticket');
    });
}

// Auto-scroll to bottom of messages
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('ticketMessagesContainer');
    if (container) {
        container.scrollTop = container.scrollHeight;
    }
});
</script>

<?php
require_once 'includes/footer.php';

/**
 * Format relative time
 */
function formatRelativeTime($dateString) {
    $timestamp = strtotime($dateString);
    $now = time();
    $diff = $now - $timestamp;

    if ($diff < 60) {
        return 'just now';
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
