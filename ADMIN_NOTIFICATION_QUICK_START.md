# Admin Notification System - Quick Start Guide

## System Status: ✅ ACTIVE AND READY

The admin notification system is now fully integrated with the `user_notification` database table.

## Quick Access

| Purpose | URL/Action |
|---------|-----------|
| **Setup** | http://localhost/DORCFSRF/admin_notification_setup.php |
| **Database Migration** | http://localhost/DORCFSRF/admin_notification_migrate_db.php |
| **Get Notifications** | http://localhost/DORCFSRF/api/admin_notifications.php?action=get |
| **Documentation** | ADMIN_NOTIFICATION_INTEGRATION_GUIDE.md |

## 30-Second Usage Examples

### 1. Get Admin Notifications (PHP)
```php
<?php
include 'db_connect.php';
include 'api/notification_system.php';

$admin_id = 'ADMIN001';
$unreadCount = getAdminUnreadCount($conn, $admin_id);
$notifications = getAdminNotifications($conn, $admin_id, 10);

foreach ($notifications as $notif) {
    echo $notif['message'] . "\n";
}
?>
```

### 2. Send Notification on Form Submission (PHP)
```php
<?php
// When student submits form
include 'db_connect.php';
include 'api/notification_system.php';

createAdminFormNotification(
    $conn,
    'ADMIN001',           // Admin ID
    'STU-001',           // Student ID  
    'John Doe',          // Student Name
    'Clearance Form'     // Form Type
);
?>
```

### 3. Get Notifications via API (JavaScript)
```javascript
// Get admin notifications
fetch('/DORCFSRF/api/admin_notifications.php?action=get&limit=5')
    .then(res => res.json())
    .then(data => {
        console.log('Unread:', data.unreadCount);
        console.log('Notifications:', data.notifications);
    });

// Mark as read
fetch('/DORCFSRF/api/admin_notifications.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        action: 'mark_read',
        notification_id: 1
    })
});
```

### 4. Create Notification on Event (PHP)
```php
<?php
// Payment received
include 'db_connect.php';
include 'api/notification_system.php';

createAdminStatusNotification(
    $conn,
    'ADMIN001',
    "Payment received: ₱5,000 from STU-001",
    'STU-001',
    'John Doe'
);
?>
```

## Database Table Structure

**Table**: `user_notification`

| Column | Type | Purpose |
|--------|------|---------|
| `id` | INT | Unique notification ID |
| `user_id` | VARCHAR(50) | Recipient (admin) ID |
| `recipient_role` | ENUM('Admin','Staff','Student') | User role filter |
| `sender_id` | VARCHAR(50) | Who created the notification |
| `student_id` | VARCHAR(50) | Related student (if any) |
| `student_name` | VARCHAR(255) | Student name for display |
| `notification_type` | VARCHAR(100) | Type: form_submission, status_update, etc. |
| `message` | TEXT | Notification message |
| `is_read` | BOOLEAN | Read status |
| `created_at` | TIMESTAMP | Creation time |
| `updated_at` | TIMESTAMP | Last update time |

## Core Functions

### Get Notifications
```php
// Get unread count
$count = getAdminUnreadCount($conn, $admin_id);

// Get recent notifications
$notifs = getAdminNotifications($conn, $admin_id, 10);

// Mark as read
markAdminNotificationAsRead($conn, $notif_id);

// Delete notification
deleteUnifiedNotification($conn, $notif_id);
```

### Create Notifications
```php
// Form submission
createAdminFormNotification($conn, $admin_id, $student_id, $student_name, $form_type);

// Status update
createAdminStatusNotification($conn, $admin_id, $message, $student_id, $student_name);

// Custom notification
createUnifiedNotification($conn, $admin_id, 'Admin', $message, $type, $sender_id, $student_id, $student_name);
```

## API Endpoints

All endpoints at `/api/admin_notifications.php`:

| Action | Method | Purpose |
|--------|--------|---------|
| `get` | GET | Get notifications |
| `mark_read` | POST | Mark as read |
| `create` | POST | Create notification |
| `delete` | POST | Delete notification |
| `init` | GET | Initialize table |
| `migrate` | GET | Migrate existing notifications |

## Integration Points

### Where to Add Notifications

1. **Form Submission** (`api/submit_clearance_documents.php`)
   ```php
   createAdminFormNotification($conn, 'ADMIN001', $student_id, $student_name, 'Clearance Form');
   ```

2. **Payment Processing** (`api/process_payment.php`)
   ```php
   createAdminStatusNotification($conn, 'ADMIN001', "Payment: ₱$amount from $student_name", $student_id, $student_name);
   ```

3. **Status Changes** (Any status update handler)
   ```php
   createAdminStatusNotification($conn, 'ADMIN001', "Status changed to: $new_status", $student_id, $student_name);
   ```

4. **Admin Actions** (Any admin action handler)
   ```php
   createAdminStatusNotification($conn, 'ADMIN001', "Admin action completed: $action_description");
   ```

## Display in Admin Dashboard

Update admin dashboard pages to show notifications:

```php
<?php
$admin_id = $_SESSION['user_id'];
$unreadCount = getAdminUnreadCount($conn, $admin_id);
$notifications = getAdminNotifications($conn, $admin_id, 5);
?>

<!-- Display notifications badge -->
<span class="notification-badge"><?php echo $unreadCount; ?></span>

<!-- Display notification list -->
<div class="notification-panel">
    <?php foreach ($notifications as $notif): ?>
        <div class="notification-item <?php echo $notif['is_read'] ? 'read' : 'unread'; ?>">
            <p><?php echo htmlspecialchars($notif['message']); ?></p>
            <small><?php echo $notif['created_at']; ?></small>
        </div>
    <?php endforeach; ?>
</div>
```

## Notification Types

| Type | Use Case |
|------|----------|
| `form_submission` | Student submitted a form |
| `status_update` | General status changed |
| `approval` | Requires approval |
| `payment` | Payment received/failed |
| `deadline` | Deadline approaching |
| `error` | System error occurred |
| `system` | System message |

## Testing

### Manual Test
```
1. Visit: http://localhost/DORCFSRF/admin_notification_setup.php
2. Should show: "Admin notification system initialized successfully!"
3. Check database: SELECT * FROM user_notification WHERE user_id = 'ADMIN001';
```

### API Test
```
1. GET http://localhost/DORCFSRF/api/admin_notifications.php?action=get
2. Should return JSON with notifications array
```

## Troubleshooting

| Issue | Solution |
|-------|----------|
| "Unknown column" | Run migration: `admin_notification_migrate_db.php` |
| No notifications showing | Check `recipient_role = 'Admin'` in database |
| "Foreign key error" | Verify admin user ID exists in `user_database` |
| "Unauthorized" | Ensure user role is 'Admin' in session |

## File Reference

| File | Purpose |
|------|---------|
| `api/notification_system.php` | Core functions (admin functions at end) |
| `api/admin_notifications.php` | Admin notification API endpoints |
| `admin_notification_setup.php` | Initialization & testing |
| `admin_notification_migrate_db.php` | Database schema migration |
| `ADMIN_NOTIFICATION_INTEGRATION_GUIDE.md` | Complete documentation |

## Next Steps

1. ✅ System is initialized
2. ✅ Database is configured
3. → Integrate into form submission handlers
4. → Update admin dashboard to display notifications
5. → Add notification listeners to key events

---

**Version**: 1.0  
**Status**: Production Ready  
**Last Updated**: May 6, 2026
