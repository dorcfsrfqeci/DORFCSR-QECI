<?php
// ========== UNIFIED USER NOTIFICATION SYSTEM (Admin, Staff, Student) ==========

function ensureUserNotificationTable($conn)
{
    // Table already exists and is managed, returning true.
    return true;
}

function createUnifiedNotification($conn, $recipient_id, $recipient_role, $message, $type = 'status_update', $sender_id = null, $student_id = null, $student_name = null)
{
    $stmt = $conn->prepare("INSERT INTO user_notification 
        (user_id, recipient_role, sender_id, student_id, student_name, notification_type, message) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param('sssssss', $recipient_id, $recipient_role, $sender_id, $student_id, $student_name, $type, $message);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

function getUnreadNotificationCountByRole($conn, $user_id, $role)
{
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM user_notification WHERE user_id = ? AND recipient_role = ? AND is_read = FALSE");
    if (!$stmt) return 0;
    
    $stmt->bind_param('ss', $user_id, $role);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['count'] ?? 0;
}

function getNotificationsByRole($conn, $user_id, $role, $limit = 10)
{
    $stmt = $conn->prepare("SELECT * FROM user_notification WHERE user_id = ? AND recipient_role = ? ORDER BY created_at DESC LIMIT ?");
    if (!$stmt) return [];
    
    $stmt->bind_param('ssi', $user_id, $role, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
    }
    $stmt->close();
    return $notifications;
}

function deleteUnifiedNotification($conn, $notification_id)
{
    $stmt = $conn->prepare("DELETE FROM user_notification WHERE id = ?");
    if (!$stmt) return false;
    $stmt->bind_param('i', $notification_id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

// ========== LEGACY STAFF NOTIFICATIONS WRAPPERS ==========

function createNotification($conn, $staff_id, $student_id, $student_name, $type = 'form_submission', $message = null)
{
    $default_message = $type === 'form_submission' ? 'New form submission from ' . $student_name : $message;
    return createUnifiedNotification($conn, $staff_id, 'Staff', $default_message, $type, null, $student_id, $student_name);
}

function getUnreadNotificationCount($conn, $staff_id)
{
    return getUnreadNotificationCountByRole($conn, $staff_id, 'Staff');
}

function getNotifications($conn, $staff_id, $limit = 10)
{
    return getNotificationsByRole($conn, $staff_id, 'Staff', $limit);
}

function markNotificationAsRead($conn, $notification_id)
{
    $stmt = $conn->prepare("UPDATE user_notification SET is_read = TRUE WHERE id = ? AND recipient_role = 'Staff'");
    if (!$stmt) return false;
    $stmt->bind_param('i', $notification_id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

function sendEmailNotification($staff_email, $staff_name, $student_name, $student_id)
{
    // Mail sending logic...
    return true; // Simplified as this is just a dummy in most local setups
}

// ========== LEGACY STUDENT NOTIFICATIONS WRAPPERS ==========

function createStudentNotification($conn, $student_id, $type = 'status_update', $message = null)
{
    return createUnifiedNotification($conn, $student_id, 'Student', $message, $type, null, $student_id, null);
}

function getUnreadStudentNotificationCount($conn, $student_id)
{
    return getUnreadNotificationCountByRole($conn, $student_id, 'Student');
}

function getStudentNotifications($conn, $student_id, $limit = 10)
{
    return getNotificationsByRole($conn, $student_id, 'Student', $limit);
}

function markStudentNotificationAsRead($conn, $notification_id)
{
    $stmt = $conn->prepare("UPDATE user_notification SET is_read = TRUE WHERE id = ? AND recipient_role = 'Student'");
    if (!$stmt) return false;
    $stmt->bind_param('i', $notification_id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

// ========== LEGACY ADMIN NOTIFICATIONS WRAPPERS ==========

function getAdminUnreadCount($conn, $admin_id)
{
    return getUnreadNotificationCountByRole($conn, $admin_id, 'Admin');
}

function getAdminNotifications($conn, $admin_id, $limit = 10)
{
    return getNotificationsByRole($conn, $admin_id, 'Admin', $limit);
}

function markAdminNotificationAsRead($conn, $notification_id)
{
    $stmt = $conn->prepare("UPDATE user_notification SET is_read = TRUE WHERE id = ? AND recipient_role = 'Admin'");
    if (!$stmt) return false;
    $stmt->bind_param('i', $notification_id);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

function createAdminFormNotification($conn, $admin_id, $student_id, $student_name, $form_type = 'Clearance Form')
{
    $message = "New form submission: $student_name submitted a $form_type";
    return createUnifiedNotification($conn, $admin_id, 'Admin', $message, 'form_submission', null, $student_id, $student_name);
}

function createAdminStatusNotification($conn, $admin_id, $message, $student_id = null, $student_name = null)
{
    return createUnifiedNotification($conn, $admin_id, 'Admin', $message, 'status_update', null, $student_id, $student_name);
}

// ========== LEGACY UNIFIED FUNCTIONS EXPECTED BY CODE ==========

function createStaffNotificationUnified($conn, $staff_id, $student_id, $student_name, $type = 'form_submission', $message = null)
{
    $default_message = $type === 'form_submission' ? 'New form submission from ' . $student_name : $message;
    return createUnifiedNotification($conn, $staff_id, 'Staff', $default_message, $type, null, $student_id, $student_name);
}

function createStudentNotificationUnified($conn, $student_id, $type = 'status_update', $message = null)
{
    return createUnifiedNotification($conn, $student_id, 'Student', $message, $type, null, null, null);
}

function createStudentFormNotificationUnified($conn, $student_id, $form_type = 'Clearance Form', $status = 'submitted')
{
    $message = "Your $form_type has been $status";
    return createStudentNotificationUnified($conn, $student_id, 'form_submission', $message);
}

function createStudentPaymentNotificationUnified($conn, $student_id, $status = 'received', $amount = null)
{
    $message = $amount ? "Payment ₱" . number_format($amount) . " has been $status" : "Payment has been $status";
    return createStudentNotificationUnified($conn, $student_id, 'payment', $message);
}

function createStudentClearanceNotificationUnified($conn, $student_id, $status = 'updated')
{
    $message = "Your clearance status has been $status";
    return createStudentNotificationUnified($conn, $student_id, 'clearance_status', $message);
}

function createStaffFormNotificationUnified($conn, $staff_id, $student_id, $student_name, $form_type = 'Clearance Form')
{
    $message = "New form submission: $student_name submitted a $form_type";
    return createStaffNotificationUnified($conn, $staff_id, $student_id, $student_name, 'form_submission', $message);
}

// ========== HIGH-LEVEL NOTIFICATION TRIGGERS ==========

function createDocumentSubmissionNotification($conn, $student_id, $student_name, $document_type = 'Clearance Document')
{
    $success = true;
    
    $staffQuery = $conn->query("SELECT user_id FROM user_database WHERE role = 'Staff'");
    while ($staff = $staffQuery->fetch_assoc()) {
        $result = createStaffNotificationUnified($conn, $staff['user_id'], $student_id, $student_name, 'document_submission', "New $document_type submitted by $student_name");
        $success = $success && $result;
    }
    
    $adminQuery = $conn->query("SELECT user_id FROM user_database WHERE role = 'Admin'");
    while ($admin = $adminQuery->fetch_assoc()) {
        $result = createUnifiedNotification($conn, $admin['user_id'], 'Admin', "New $document_type submitted by $student_name", 'document_submission', null, $student_id, $student_name);
        $success = $success && $result;
    }
    
    $studentMsg = "Your $document_type has been submitted successfully";
    $result = createStudentNotificationUnified($conn, $student_id, 'document_submission', $studentMsg);
    $success = $success && $result;
    
    return $success;
}

function createPaymentNotification($conn, $student_id, $student_name, $amount, $status = 'received')
{
    $success = true;
    
    $adminQuery = $conn->query("SELECT user_id FROM user_database WHERE role = 'Admin'");
    while ($admin = $adminQuery->fetch_assoc()) {
        $result = createUnifiedNotification($conn, $admin['user_id'], 'Admin', "Payment ₱" . number_format($amount) . " $status from $student_name", 'payment', null, $student_id, $student_name);
        $success = $success && $result;
    }
    
    $staffQuery = $conn->query("SELECT user_id FROM user_database WHERE role = 'Staff'");
    while ($staff = $staffQuery->fetch_assoc()) {
        $result = createStaffNotificationUnified($conn, $staff['user_id'], $student_id, $student_name, 'payment', "Payment ₱" . number_format($amount) . " $status from $student_name");
        $success = $success && $result;
    }
    
    $result = createStudentPaymentNotificationUnified($conn, $student_id, $status, $amount);
    $success = $success && $result;
    
    return $success;
}

function createFormSubmissionNotification($conn, $student_id, $student_name, $form_type = 'Clearance Form')
{
    $success = true;
    
    $adminQuery = $conn->query("SELECT user_id FROM user_database WHERE role = 'Admin'");
    while ($admin = $adminQuery->fetch_assoc()) {
        $result = createAdminFormNotification($conn, $admin['user_id'], $student_id, $student_name, $form_type);
        $success = $success && $result;
    }
    
    $staffQuery = $conn->query("SELECT user_id FROM user_database WHERE role = 'Staff'");
    while ($staff = $staffQuery->fetch_assoc()) {
        $result = createStaffFormNotificationUnified($conn, $staff['user_id'], $student_id, $student_name, $form_type);
        $success = $success && $result;
    }
    
    $result = createStudentFormNotificationUnified($conn, $student_id, $form_type, 'submitted');
    $success = $success && $result;
    
    return $success;
}

function createClearanceStatusNotification($conn, $student_id, $student_name, $new_status = 'pending')
{
    $success = true;
    
    $adminQuery = $conn->query("SELECT user_id FROM user_database WHERE role = 'Admin'");
    while ($admin = $adminQuery->fetch_assoc()) {
        $result = createAdminStatusNotification($conn, $admin['user_id'], "Clearance status for $student_name changed to: $new_status", $student_id, $student_name);
        $success = $success && $result;
    }
    
    $staffQuery = $conn->query("SELECT user_id FROM user_database WHERE role = 'Staff'");
    while ($staff = $staffQuery->fetch_assoc()) {
        $result = createStaffNotificationUnified($conn, $staff['user_id'], $student_id, $student_name, 'clearance_status', "Clearance status for $student_name changed to: $new_status");
        $success = $success && $result;
    }
    
    $result = createStudentClearanceNotificationUnified($conn, $student_id, $new_status);
    $success = $success && $result;
    
    return $success;
}

// Stubs for table creation and migration, no longer needed
function ensureNotificationsTable($conn) { return true; }
function ensureStudentNotificationsTable($conn) { return true; }
function migrateAdminNotifications($conn) { return ['success' => true, 'migrated' => 0]; }
function migrateStaffNotifications($conn) { return ['success' => true, 'migrated' => 0]; }
function migrateStudentNotifications($conn) { return ['success' => true, 'migrated' => 0]; }
?>
