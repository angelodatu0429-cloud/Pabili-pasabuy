# Chat Support System Documentation

## Overview

The Chat Support System provides a comprehensive solution for real-time communication between customers, riders, and admin staff. It includes:

1. **Direct Messaging** - One-on-one private chat between users
2. **Support Tickets** - Structured support requests with categorization and tracking
3. **Real-time Updates** - Message polling for live conversation experience
4. **User Management** - Easy user discovery and conversation management

---

## Features

### 1. Direct Chat (chat.php)

#### User Interface
- **Conversations List** - View all active conversations with unread message counts
- **Chat Window** - Real-time messaging interface with message history
- **User Search** - Search through conversations for quick access
- **New Chat** - Start conversations with any user (customer, rider, admin)

#### Key Features
- Message history with timestamps
- Read/unread message indicators
- Conversation search functionality
- One-click access to user information
- Message notifications (unread count badges)
- Support for text messages (expandable for media)

#### How to Use

**For Customers/Riders:**
1. Click "Chat Support" in the sidebar
2. Select an existing conversation or click "New" to start a new chat
3. Type your message and click "Send"
4. Messages appear in real-time with timestamps

**For Admins:**
Access the same interface to manage customer and rider communications

---

### 2. Support Tickets (support-tickets.php)

#### User Interface
- **Ticket List** - View all support tickets with status badges
- **Ticket Detail** - Full ticket information with integrated chat
- **Filter Tabs** - Quick filtering (All, Open, Resolved)
- **New Ticket** - Create structured support requests

#### Ticket Properties
- **Subject** - Brief description of the issue
- **Category** - Issue classification:
  - Delivery Issue
  - Payment Problem
  - Order Issue
  - Account Problem
  - Refund Request
  - Rider Complaint
  - Bug Report
  - General Support

- **Priority** - Urgency level:
  - Low
  - Medium (default)
  - High
  - Urgent

- **Status** - Ticket state:
  - Open (new ticket)
  - In Progress (staff is working)
  - Resolved (issue fixed)
  - Closed (archived)

#### How to Use

**Creating a Support Ticket:**
1. Click "Support Tickets" in sidebar
2. Click "New" button
3. Fill in:
   - Subject (required)
   - Category (required)
   - Priority (optional, defaults to Medium)
   - Description (required)
4. Click "Create Ticket"

**Managing a Support Ticket:**
1. Click on a ticket from the list
2. View the description and ticket details
3. Type your message in the input footer
4. Click "Send" to add message to ticket
5. Support staff will respond in the same thread

**Filtering Tickets:**
- **All** - Show all tickets
- **Open** - Show open and in-progress tickets
- **Resolved** - Show completed and closed tickets

---

## Database Schema

### Collections Structure (Firestore)

#### `conversations` Collection
```
{
    id: "conversation_hash_based_on_userids",
    participant1: "user_id_1",
    participant2: "user_id_2",
    createdAt: DateTime,
    updatedAt: DateTime,
    lastMessage: "Last message text...",
    lastMessageTime: DateTime,
    lastSenderId: "user_id",
    participant1Unread: 5,
    participant2Unread: 0
}
```

#### `conversations/{id}/messages` Subcollection
```
{
    id: "msg_xxxxx_timestamp",
    senderId: "user_id",
    message: "Message content",
    type: "text",
    timestamp: DateTime,
    isRead: false,
    attachmentUrl: null (optional)
}
```

#### `support_tickets` Collection
```
{
    id: "ticket_timestamp_randomid",
    userId: "user_id",
    subject: "Ticket subject",
    description: "Detailed description",
    category: "delivery|payment|order|account|refund|rider|bug|general",
    priority: "low|medium|high|urgent",
    status: "open|in-progress|resolved|closed",
    createdAt: DateTime,
    updatedAt: DateTime,
    assignedTo: "admin_id" (optional),
    resolved: false,
    resolvedAt: null,
    lastMessage: "Last response..."
}
```

#### `support_tickets/{id}/messages` Subcollection
```
{
    id: "msg_xxxxx_timestamp",
    userId: "user_id",
    message: "Message content",
    timestamp: DateTime,
    isAdminReply: false
}
```

---

## API Endpoints

### Chat API (`chat-api.php`)

#### Send Message
```
POST /chat-api.php
action: send-message
otherUserId: string
message: string

Response:
{
    success: true,
    messageId: string,
    timestamp: string
}
```

#### Get Conversations
```
GET /chat-api.php?action=get-conversations

Response:
{
    success: true,
    conversations: [
        {
            id: string,
            otherUserId: string,
            otherUserInfo: {...},
            lastMessage: string,
            lastMessageTime: string,
            unreadCount: number
        }
    ]
}
```

#### Get Messages
```
GET /chat-api.php?action=get-messages&conversationId=string

Response:
{
    success: true,
    messages: [{...}]
}
```

#### Mark as Read
```
POST /chat-api.php
action: mark-read
conversationId: string

Response:
{
    success: true
}
```

#### Get Available Users
```
GET /chat-api.php?action=get-available-users

Response:
{
    success: true,
    users: [
        {
            id: string,
            name: string,
            role: string,
            avatar: string
        }
    ]
}
```

### Support API (`support-api.php`)

#### Create Ticket
```
POST /support-api.php
action: create-ticket
subject: string
category: string
priority: string
description: string

Response:
{
    success: true,
    ticketId: string
}
```

#### Add Message to Ticket
```
POST /support-api.php
action: add-message
ticketId: string
message: string

Response:
{
    success: true,
    messageId: string
}
```

#### Get Tickets
```
GET /support-api.php?action=get-tickets

Response:
{
    success: true,
    tickets: [{...}]
}
```

#### Get Single Ticket
```
GET /support-api.php?action=get-ticket&ticketId=string

Response:
{
    success: true,
    ticket: {...},
    messages: [{...}]
}
```

#### Update Status (Admin Only)
```
POST /support-api.php
action: update-status
ticketId: string
status: string

Response:
{
    success: true
}
```

---

## File Structure

### New Files Created

```
├── chat.php                          # Main chat interface
├── chat-api.php                      # Chat API endpoints
├── support-tickets.php               # Support tickets interface
├── support-api.php                   # Support API endpoints
│
└── includes/
    ├── chat-helper.php               # Chat helper functions
    └── support-helper.php            # Support helper functions
```

### Modified Files

- `includes/sidebar.php` - Added Chat Support and Support Tickets menu items

---

## Configuration

### Requirements

1. **Firestore Database** - Already configured in your project
2. **Authentication** - Uses existing session-based authentication
3. **PHP** - PHP 7.4+
4. **Bootstrap 5** - Already included in header

### Environment Variables

None required - uses existing Firestore connection

---

## Usage Examples

### Example 1: Customer Sends Message

```php
// User navigates to chat.php
// Selects a conversation with support staff
// Types message: "Where is my order?"
// Clicks Send

// Backend:
// 1. chat-api.php receives POST request
// 2. Message stored in conversations/{id}/messages collection
// 3. Conversation metadata updated
// 4. Response sent to JavaScript
// 5. UI updated with new message
```

### Example 2: Rider Creates Support Ticket

```php
// Rider clicks "Support Tickets" in sidebar
// Clicks "New" button
// Fills in form:
//   Subject: "Vehicle not showing in app"
//   Category: "Account Problem"
//   Priority: "High"
//   Description: "My vehicle stopped appearing..."
// Clicks "Create Ticket"

// Backend:
// 1. support-api.php creates new ticket
// 2. Ticket stored in support_tickets collection
// 3. User redirected to view the ticket
// 4. Can add messages to ticket immediately
```

### Example 3: Admin Responds to Support Ticket

```php
// Admin views support-tickets.php
// Selects a ticket from list
// Sees category, priority, and description
// Types response in message box
// Clicks Send

// Backend:
// 1. support-api.php adds message
// 2. Message stored in support_tickets/{id}/messages
// 3. Ticket status automatically updates if needed
// 4. Customer can see response immediately
```

---

## Advanced Features

### Future Enhancements

1. **Image Upload** - Share photos/screenshots in messages
2. **Attachment Support** - Documents, receipts, etc.
3. **Real-time WebSocket** - Replace polling with WebSockets for instant updates
4. **Message Reactions** - Emoji reactions to messages
5. **Typing Indicators** - See when someone is typing
6. **Video/Audio Calls** - Integrated calling within chat
7. **Chatbot Integration** - AI-powered first-response systems
8. **Analytics Dashboard** - Support metrics and trends
9. **Template Responses** - Quick replies for common issues
10. **Ticket Assignment** - Distribute tickets to team members

---

## Troubleshooting

### Common Issues

**Messages not appearing:**
- Check if conversation ID is correct
- Verify Firestore permissions in Firebase Console
- Check browser console for JavaScript errors

**Unread count not updating:**
- Ensure `mark-read` API is called when conversation is opened
- Check Firestore document structure

**Support ticket not created:**
- Verify all required fields are filled
- Check browser console for validation errors
- Verify Firestore database rules allow writes

**Conversations not loading:**
- Ensure user authentication is active
- Check session variables are set correctly
- Verify Firestore database has documents

---

## Security Considerations

1. **Authentication** - All endpoints require active session
2. **Authorization** - Users can only see their own conversations/tickets
3. **Input Sanitization** - All user inputs are sanitized using `htmlspecialchars()`
4. **XSS Protection** - Output is properly escaped
5. **CSRF Protection** - Can be enhanced with CSRF tokens
6. **Rate Limiting** - Should be implemented for production

---

## Performance Tips

1. **Message Pagination** - Limit messages fetched per conversation
2. **Lazy Loading** - Load conversations on-demand
3. **Caching** - Cache user info to reduce API calls
4. **Database Indexing** - Index on userId, status fields
5. **Archive Old Tickets** - Move resolved tickets to archive collection

---

## Support & Maintenance

For issues or feature requests:
1. Check this documentation
2. Review the in-code comments
3. Check browser console for errors
4. Review Firestore collection structure

---

## Version History

- **v1.0** - Initial release with direct chat and support tickets
  - Direct messaging between users
  - Support ticket system with categorization
  - Real-time message polling
  - User conversation management
  - Support ticket filtering and status tracking

---

