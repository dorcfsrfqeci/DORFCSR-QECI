# Admin Notification System - Complete Integration Guide

## Overview

The Admin Notification System provides a unified notification infrastructure for admin users, connecting admin notifications to the centralized `user_notification` database. This system maintains backward compatibility with existing staff and student notifications while adding robust admin-specific features.

## Key Features

✅ **Unified Database**: All admin notifications stored in `user_notification` table with role-based filtering  
✅ **Admin-Specific Functions**: Dedicated functions for admin notification operations  
✅ **Role-Based Access**: Automatic role filtering (Admin/Staff/Student)  
✅ **Backward Compatible**: Existing staff/student notification systems remain functional  
✅ **Migration Support**: Automatic migration of existing admin notifications  
✅ **API Endpoints**: RESTful API for notification management  

## Database Schema

### user_notification Table

```sql
CREATE TABLE user_notification (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_id VARCHAR(50) NOT NULL,           -- Admin ID
    recipient_role ENUM('Admin','Staff','Student') DEFAULT 'Staff',
    sender_id VARCHAR(50),                       -- Who sent the notification
    student_id VARCHAR(50),                      -- Related student (if applicable)
    student_name VARCHAR(255),                   -- Student name for display
    notification_type VARCHAR(100) DEFAULT 'status_update',
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    KEY idx_recipient (recipient_id, recipient_role),
    KEY idx_created (created_at),
    KEY idx_read_status (is_read),
    FOREIGN KEY (recipient_id) REFERENCES user_database(user_id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES user_database(user_id) ON DELETE SET NULL,
    FOREIGN KEY (student_id) REFERENCES user_database(user_id) ON DELETE CASCADE
)
```

## Core Functions

### Table Initialization

```php
ensureUserNotificationTable($conn);
```
Ensures the `user_notification` table exists, creating it if necessary.

### Creating Admin Notifications

#### Basic Creation
```php
createUnifiedNotification(
    $conn,
    $admin_id,              // Recipient admin ID
    'Admin',               // Role (always 'Admin')
    $message,              // Notification message
    'status_update',       // Type: form_submission, status_update, system, etc.
    $sender_id,            // Optional: who created the notification
    $student_id,           // Optional: related student
    $student_name          // Optional: student name for display
);
```

#### Form Submission Notification
```php
createAdminFormNotification(
    $conn,
    $admin_id,
    $student_id,
    $student_name,
    'Clearance Form'  // Form type
);
```

#### Status Update Notification
```php
createAdminStatusNotification(
    $conn,
    $admin_id,
    'Payment received from student ID: STU-001',
    $student_id,
    $student_name
);
```

### Retrieving Admin Notifications

#### Get Unread Count
```php
$unreadCount = getAdminUnreadCount($conn, $admin_id);
echo "Unread notifications: " . $unreadCount;
```

#### Get Recent Notifications
```php
$notifications = getAdminNotifications($conn, $admin_id, 10);
foreach ($notifications as $notif) {
    echo $notif['message'];
    echo $notif['created_at'];
}
```

#### Using Generic Role-Based Functions
```php
// For any role
$count = getUnreadNotificationCountByRole($conn, $user_id, 'Admin');
$notifs = getNotificationsByRole($conn, $user_id, 'Admin', 10);
```

### Managing Notifications

#### Mark as Read
```php
markAdminNotificationAsRead($conn, $notification_id);
```

#### Delete Notification
```php
deleteUnifiedNotification($conn, $notification_id);
```

## API Endpoints

### Base URL
```
/api/admin_notifications.php
```

### Endpoints

#### 1. Get Admin Notifications
```
GET /api/admin_notifications.php?action=get&limit=10
```

**Response:**
```json
{
  "success": true,
  "unreadCount": 5,
  "notifications": [
    {
      "id": 1,
      "student_name": "John Doe",
      "message": "New form submission",
      "notification_type": "form_submission",
      "created_at": "May 06, 2026 02:30pm",
      "is_read": false
    }
  ]
}
```

#### 2. Mark as Read
```
POST /api/admin_notifications.php

Body: {
  "action": "mark_read",
  "notification_id": 1
}
```

#### 3. Create Notification (System)
```
POST /api/admin_notifications.php

Body: {
  "action": "create",
  "message": "Payment received",
  "type": "status_update",
  "student_id": "STU-001",
  "student_name": "John Doe"
}
```

#### 4. Delete Notification
```
POST /api/admin_notifications.php

Body: {
  "action": "delete",
  "notification_id": 1
}
```

#### 5. Initialize System
```
GET /api/admin_notifications.php?action=init
```

#### 6. Migrate Notifications
```
GET /api/admin_notifications.php?action=migrate
```

## Setup Instructions

### Step 1: Run Setup Script
```
http://localhost/DORCFSRF/admin_notification_setup.php
```

This script will:
- Create the `user_notification` table
- Verify table structure
- Check for admin users
- Migrate existing admin notifications
- Create a test notification

### Step 2: Verify Database
The setup script provides detailed output about each step:
- ✓ Table creation/verification
- ✓ Column count
- ✓ Admin user count
- ✓ Migration status
- ✓ Test notification created

### Step 3: Update Admin Dashboard

Update admin dashboard pages to use new functions. Example:

```php
<?php
include __DIR__ . '/../../db_connect.php';

$admin_id = $_SESSION['user_id'];

// Get notifications
$unreadCount = getAdminUnreadCount($conn, $admin_id);
$notifications = getAdminNotifications($conn, $admin_id, 5);
?>

<!-- Display unread count -->
<span class="badge"><?php echo $unreadCount; ?></span>

<!-- Display notifications -->
<?php foreach ($notifications as $notif): ?>
  <div class="notification <?php echo $notif['is_read'] ? 'read' : 'unread'; ?>">
    <p><?php echo htmlspecialchars($notif['message']); ?></p>
    <small><?php echo $notif['created_at']; ?></small>
  </div>
<?php endforeach; ?>
```

## Integration Examples

### Example 1: Send Notification on Form Submission
```php
// When a student submits a form
if ($student_submitted_form) {
    $admin_id = 'ADMIN-001';  // Get actual admin ID
    
    createAdminFormNotification(
        $conn,
        $admin_id,
        $student_id,
        $student_name,
        'Clearance Form'
    );
}
```

### Example 2: Send Status Update
```php
// When payment is processed
if ($payment_successful) {
    $admin_id = 'ADMIN-001';
    
    createAdminStatusNotification(
        $conn,
        $admin_id,
        "Payment received: ₱" . $amount . " from $student_name",
        $student_id,
        $student_name
    );
}
```

### Example 3: Batch Notification
```php
// Send notification to all admins
$admins = $conn->query("SELECT user_id FROM user_database WHERE role = 'Admin'");
while ($admin = $admins->fetch_assoc()) {
    createAdminStatusNotification(
        $conn,
        $admin['user_id'],
        "System maintenance scheduled for tonight",
        null,
        'System'
    );
}
```

## Notification Types

The system supports these notification types:

| Type | Usage | Example |
|------|-------|---------|
| `form_submission` | New form submitted by student | Student submitted clearance form |
| `status_update` | General status changes | Payment processed, deadline approaching |
| `approval` | Approval required | Clearance requires admin approval |
| `error` | System errors | Database backup failed |
| `system` | System messages | Maintenance scheduled |

## Backward Compatibility

The system maintains full backward compatibility:

- **Staff notifications**: Still use `staff_notifications` table by default
- **Student notifications**: Still use `student_notifications` table by default
- **Admin notifications**: Now unified in `user_notification` table with role filtering

Existing staff and student functions remain unchanged:
- `createNotification()` → Staff notifications
- `createStudentNotification()` → Student notifications
- `createAdminFormNotification()` → Admin notifications (NEW)

## Migration from Old System

### Manual Migration
```php
// Run migration function
$result = migrateAdminNotifications($conn);
if ($result['success']) {
    echo $result['migrated'] . " admin notifications migrated";
}
```

### API Migration
```
GET /api/admin_notifications.php?action=migrate
```

## Security Considerations

✅ **Role-Based Filtering**: Only admins can access admin notifications  
✅ **SQL Injection Prevention**: All queries use prepared statements  
✅ **Session Validation**: API endpoints verify session and role  
✅ **Data Sanitization**: HTML special characters escaped in output  
✅ **Foreign Key Constraints**: Database enforces referential integrity  

## Troubleshooting

### Table Not Creating
- Check database permissions
- Verify user_database table exists
- Check error logs for MySQL errors

### Notifications Not Showing
- Verify admin role in user_database
- Check recipient_role is 'Admin'
- Confirm recipient_id matches user_id

### Migration Issues
- Ensure staff_notifications table exists
- Check user_database for Admin role entries
- Verify foreign key references

## File References

- **Core Functions**: `api/notification_system.php`
- **Admin API**: `api/admin_notifications.php`
- **Setup Script**: `admin_notification_setup.php`
- **Database Connection**: `db_connect.php`

## Next Steps

1. ✅ Run `admin_notification_setup.php` to initialize
2. ✅ Test with `api/admin_notifications.php?action=get`
3. ✅ Update admin dashboard pages to use new functions
4. ✅ Integrate notifications into form submission handlers
5. ✅ Monitor notifications in admin interface

---

**Version**: 1.0  
**Last Updated**: May 6, 2026  
**Status**: Production Ready
