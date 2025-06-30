# Communication Features Documentation

## Overview

This document describes the new communication features implemented in the Aureus Alliance Holdings platform:

1. **Contact Form System** - Allows users to send messages to admins
2. **Live Chat System** - Real-time chat between users and admins
3. **Admin User Management** - Role-based admin system with chat status
4. **Offline Message System** - Messages when no admin is available

## Enhanced Features (Latest Update)

### 🔐 Admin User Management
- **Role-Based Access Control**: Three admin roles with different permissions
  - `super_admin`: Full system access, can manage all admins
  - `admin`: Can manage chat support users and system settings
  - `chat_support`: Can only handle chat sessions and view their own profile
- **Live/Offline Status Toggle**: Admins can set their availability for chat
- **Multiple Admin Support**: Create and manage multiple admin users
- **Activity Tracking**: Track last activity and online status

### 📨 Offline Message System
- **No Admin Available Detection**: Automatically detects when no admin is online
- **Guest Message Submission**: Anonymous users can leave messages with email
- **Admin Reply System**: Admins can reply to offline messages
- **Status Management**: Track message status (unread/read/replied)
- **Email Integration**: Ready for email notifications (implementation depends on email system)

### 💬 Enhanced Chat System
- **Admin Availability Check**: Chat creation checks for online admins
- **Graceful Fallback**: Redirects to offline message form when no admin available
- **Improved User Experience**: Clear messaging about wait times and availability

## Features Implemented

### 📧 Contact Form System

#### User Side (Dashboard)
- **Contact Form**: Users can submit support requests with subject and message
- **Message History**: Users can view all their previous messages and admin replies
- **Status Tracking**: Messages show status (Unread, Read, Replied)
- **Real-time Updates**: Message list refreshes when new messages are sent

#### Admin Side (Admin Dashboard)
- **Message Management**: View all contact messages from users
- **Status Filtering**: Filter by Unread, Read, or Replied messages
- **Reply System**: Admins can reply directly to user messages
- **Mark as Read**: Admins can mark messages as read
- **User Information**: See username and email for each message

### 💬 Live Chat System

#### User Side (Frontend)
- **Live Chat Widget**: Floating chat button in bottom-left corner **on ALL pages**
- **Guest Support**: Non-logged-in users can chat by providing email and name
- **User Support**: Logged-in users can chat directly without additional info
- **Session Management**: Automatic session creation and management
- **Real-time Messaging**: Send and receive messages instantly
- **Status Indicators**: Shows if waiting for admin or actively chatting
- **Unread Counter**: Shows number of unread messages from admin
- **Auto-scroll**: Messages automatically scroll to bottom
- **Minimizable**: Chat can be minimized while keeping session active

#### Admin Side (Admin Dashboard)
- **Session Overview**: View all chat sessions (waiting, active, closed)
- **Take Chats**: Admins can assign themselves to waiting sessions
- **Real-time Chat**: Send and receive messages in real-time
- **Session Management**: Close chat sessions when complete
- **Status Filtering**: Filter sessions by status
- **Auto-refresh**: Sessions and messages update automatically

## Database Tables Created

### Contact Messages
- `contact_messages`: Stores user contact form submissions
  - `id`, `user_id`, `subject`, `message`, `status`, `admin_reply`
  - `created_at`, `updated_at`

### Live Chat
- `chat_sessions`: Manages chat sessions between users and admins
  - `id`, `user_id`, `admin_id`, `status` (waiting/active/closed)
  - `created_at`, `updated_at`

- `chat_messages`: Stores individual chat messages
  - `id`, `session_id`, `sender_type`, `sender_id`, `message`, `is_read`
  - `created_at`

### Admin User Management
- `admin_users`: Enhanced admin user management with roles and chat status
  - `id`, `username`, `password_hash`, `email`, `full_name`
  - `role` (super_admin/admin/chat_support), `is_active`, `chat_status` (online/offline/busy)
  - `last_activity`, `created_at`, `updated_at`

### Offline Messages
- `offline_messages`: Stores messages when no admin is available
  - `id`, `guest_name`, `guest_email`, `subject`, `message`
  - `status` (unread/read/replied), `admin_reply`, `replied_by`, `replied_at`
  - `created_at`, `updated_at`

## API Endpoints

### Contact Messages
- `POST /api/contact/messages.php`
  - Actions: `submit`, `reply`, `mark_read`
- `GET /api/contact/messages.php`
  - Parameters: `user_id`, `admin_view`, `status`, `limit`, `offset`

### Chat Sessions
- `POST /api/chat/sessions.php`
  - Actions: `create` (for logged-in users), `create_guest` (for guest users), `assign`, `close`
- `GET /api/chat/sessions.php`
  - Parameters: `user_id`, `admin_view`, `status`, `limit`, `offset`
  - Returns both user and guest sessions with proper identification

### Chat Messages
- `POST /api/chat/messages.php`
  - Actions: `send` (supports both user and guest senders), `mark_read`
- `GET /api/chat/messages.php`
  - Parameters: `session_id`, `limit`, `offset`, `since`
  - Returns messages with proper sender identification for both users and guests

### Admin Management
- `POST /api/admin/manage-admins.php`
  - Actions: `create`, `update`, `delete`, `update_chat_status`
- `GET /api/admin/manage-admins.php`
  - Parameters: `current_admin_id`
  - Returns admin users list with role-based filtering

### Offline Messages
- `POST /api/chat/offline-messages.php`
  - Actions: `submit`, `reply`, `mark_read`
- `GET /api/chat/offline-messages.php`
  - Parameters: `admin_id`, `status`, `limit`, `offset`
  - Returns offline messages with pagination

## How to Use

### For Users

1. **Contact Form**:
   - Go to your Dashboard
   - Scroll down to "Contact Support" section
   - Fill in subject and message
   - Click "Send Message"
   - View replies in "Your Messages" section

2. **Live Chat**:
   - Click the "Live Chat" button in bottom-left corner
   - Type your message and press Enter or click Send
   - Wait for an admin to join the chat
   - Continue conversation in real-time

### For Admins

1. **Contact Messages**:
   - Go to Admin Dashboard
   - Click "Contact Messages" tab
   - View all messages, filter by status
   - Click "Reply" to respond to messages
   - Type reply and click "Send Reply"

2. **Live Chat**:
   - Go to Admin Dashboard
   - Click "Live Chat" tab
   - See waiting chat sessions in left panel
   - Click "Take Chat" to assign yourself to a session
   - Chat in real-time in right panel
   - Click "Close" to end the session

## Technical Features

### Real-time Updates
- **Polling System**: Both user and admin interfaces poll for new messages every 3-5 seconds
- **Auto-refresh**: Message lists and session lists update automatically
- **Unread Counters**: Show number of unread messages

### Security Features
- **User Authentication**: All features require user/admin login
- **Input Validation**: Message length limits and sanitization
- **Session Management**: Proper session handling and cleanup

### User Experience
- **Responsive Design**: Works on desktop and mobile
- **Visual Feedback**: Loading states, success/error messages
- **Intuitive Interface**: Clear status indicators and easy navigation
- **Auto-scroll**: Messages automatically scroll to show latest

## Database Setup

The new tables are automatically created when the database connection is established. Make sure to run the database setup if you haven't already:

```bash
# Run the database setup
./setup-database.bat
```

Or manually execute the SQL in `database/init.sql`.

## Testing

### ✅ Backend APIs Fixed
All API endpoints are now working correctly:
- Fixed function name conflicts that were causing PHP errors
- Fixed SQL syntax issues with LIMIT/OFFSET parameters
- Fixed UUID primary key handling for proper ID retrieval
- All endpoints now return proper JSON responses

### 🧪 Testing Steps

#### For Logged-in Users:
1. **Create a user account** and log in to the dashboard
2. **Test contact form** by sending a message
3. **Test live chat** by clicking the chat button (available on all pages)

#### For Guest Users:
1. **Visit any page** without logging in
2. **Click the "Live Chat" button** in bottom-left corner
3. **Enter your name and email** when prompted
4. **Start chatting** - your messages will be visible to admins

#### For Admins:
1. **Log in as admin** (username: admin, password: Underdog8406155100085@123!@#)
2. **Test admin features** by replying to messages and taking chat sessions
3. **View both user and guest sessions** in the Live Chat tab

### 🌐 Live Chat Location & Features
- **Available on ALL pages** (Index, Investment, Dashboard, etc.)
- **Works for both logged-in and guest users**
- **Guest users provide email/name for identification**
- **Floating chat button appears in bottom-left corner**
- **Seamless experience across the entire website**

## Future Enhancements

Potential improvements for the future:
- WebSocket integration for true real-time messaging
- File upload support in chat
- Chat history export
- Email notifications for new messages
- Mobile app integration
- Advanced admin analytics and reporting
