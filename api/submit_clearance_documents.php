<?php
ini_set('session.gc_maxlifetime', 31536000);
session_set_cookie_params(31536000);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Student') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include __DIR__ . '/../db_connect.php';
include __DIR__ . '/../api/notification_system.php';

header('Content-Type: application/json');

$student_id = $_SESSION['user_id'];
$clearance_id = $_POST['clearance_id'] ?? null;
$staff_id = $_POST['staff_id'] ?? null;

try {
    // Create uploads directory if it doesn't exist
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/DORCFSRF/assets/clearance_submissions/' . $student_id;
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            throw new Exception('Failed to create upload directory');
        }
    }

    $uploaded_files = [];
    $errors = [];

    // Handle file uploads
    if (!isset($_FILES['files'])) {
        throw new Exception('No files provided');
    }

    $files = $_FILES['files'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    $max_size = 10 * 1024 * 1024; // 10MB

    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            $errors[] = "Error uploading " . $files['name'][$i] . " (Error code: " . $files['error'][$i] . ")";
            continue;
        }

        // Validate file type
        if (!in_array($files['type'][$i], $allowed_types)) {
            $errors[] = "File type not allowed for " . $files['name'][$i] . " (Type: " . $files['type'][$i] . ")";
            continue;
        }

        // Validate file size
        if ($files['size'][$i] > $max_size) {
            $errors[] = "File too large: " . $files['name'][$i] . " (Size: " . $files['size'][$i] . " bytes)";
            continue;
        }

        // Generate unique filename
        $file_ext = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
        $unique_filename = time() . '_' . uniqid() . '.' . $file_ext;
        $file_path = $upload_dir . '/' . $unique_filename;

        // Move uploaded file
        if (move_uploaded_file($files['tmp_name'][$i], $file_path)) {
            $uploaded_files[] = [
                'original_name' => $files['name'][$i],
                'stored_name' => $unique_filename,
                'path' => '/DORCFSRF/assets/clearance_submissions/' . $student_id . '/' . $unique_filename
            ];
        } else {
            $errors[] = "Failed to save " . $files['name'][$i];
        }
    }

    if (empty($uploaded_files)) {
        throw new Exception('No files were successfully uploaded. ' . implode('; ', $errors));
    }

    // Save to clearance_document_uploads table
    $insert_sql = "INSERT INTO clearance_document_uploads (student_id, staff_id, clearance_id, original_name, stored_name, file_path) VALUES (?, ?, ?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);

    if (!$insert_stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    foreach ($uploaded_files as $file) {
        $insert_stmt->bind_param('ssssss', $student_id, $staff_id, $clearance_id, $file['original_name'], $file['stored_name'], $file['path']);
        if (!$insert_stmt->execute()) {
            throw new Exception('Execute failed: ' . $insert_stmt->error);
        }
    }
    $insert_stmt->close();

    // Update student clearance status to 'Submitted'
    if ($clearance_id) {
        $update_sql = "UPDATE student_clearance_database SET unit_stats = 'Submitted' WHERE student_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        if ($update_stmt) {
            $update_stmt->bind_param('s', $student_id);
            $update_stmt->execute();
            $update_stmt->close();
        }
    }

    // Create unified notifications for all roles about document submission
    $student_name_sql = "SELECT CONCAT(student_firstname, ' ', student_lastname) as full_name FROM student_information_database WHERE student_id = ?";
    $student_stmt = $conn->prepare($student_name_sql);
    $student_name = 'Unknown Student';
    if ($student_stmt) {
        $student_stmt->bind_param('s', $student_id);
        $student_stmt->execute();
        $student_result = $student_stmt->get_result();
        if ($student_row = $student_result->fetch_assoc()) {
            $student_name = $student_row['full_name'];
        }
        $student_stmt->close();
    }

    // Use new unified broadcast function that notifies all admins, staff, and student
    createDocumentSubmissionNotification($conn, $student_id, $student_name, 'Clearance Document');

    include_once $_SERVER['DOCUMENT_ROOT'] . '/DORCFSRF/api/log_audit.php';
    $student_full_name = $student_name ?? $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Student';
    $num_files = count($uploaded_files);
    log_activity($conn, $student_id, $student_full_name, 'Student', 'Clearance', "Submitted $num_files clearance requirement document(s)", 'Success', 'Clearance', $clearance_id ?? 'N/A');

    echo json_encode([
        'success' => true,
        'message' => 'Files submitted successfully',
        'files' => $uploaded_files,
        'errors' => $errors
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>




