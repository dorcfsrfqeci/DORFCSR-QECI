<?php
ini_set('session.gc_maxlifetime', 31536000);
session_set_cookie_params(31536000);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

// Check if user is staff or admin
$role = $_SESSION['role'] ?? '';
if (!isset($_SESSION['user_id']) || ($role !== 'Staff' && $role !== 'Admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Only staff or admin can modify clearance status.']);
    exit;
}

$staff_id = $_SESSION['user_id'];
$staff_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Staff';

include __DIR__ . '/../db_connect.php';

// Ensure clearance_approvals_log has the remarks column (per-staff private remarks)
@$conn->query("ALTER TABLE clearance_approvals_log ADD COLUMN IF NOT EXISTS remarks TEXT DEFAULT NULL");

// Detect whether clearance_approvals_log has a staff_id column in this environment
$hasStaffColumn = false;
$tblChk = $conn->query("SHOW TABLES LIKE 'clearance_approvals_log'");
if ($tblChk && $tblChk->num_rows > 0) {
    $colChk = $conn->query("SHOW COLUMNS FROM clearance_approvals_log LIKE 'staff_id'");
    if ($colChk && $colChk->num_rows > 0) {
        $hasStaffColumn = true;
    }
}
// Detect whether clearance_approvals_log has a department_field column
$hasDeptColumn = false;
if ($tblChk && $tblChk->num_rows > 0) {
    $colDeptChk = $conn->query("SHOW COLUMNS FROM clearance_approvals_log LIKE 'department_field'");
    if ($colDeptChk && $colDeptChk->num_rows > 0) {
        $hasDeptColumn = true;
    }
}

// Detect whether clearance_approvals_log has a staff_name column
$hasStaffNameColumn = false;
if ($tblChk && $tblChk->num_rows > 0) {
    $colNameChk = $conn->query("SHOW COLUMNS FROM clearance_approvals_log LIKE 'staff_name'");
    if ($colNameChk && $colNameChk->num_rows > 0) {
        $hasStaffNameColumn = true;
    }
}

// Detect whether clearance_approvals_log has a status column and a remarks column
$hasStatusColumn = false;
$hasRemarksColumn = false;
if ($tblChk && $tblChk->num_rows > 0) {
    $colStatusChk = $conn->query("SHOW COLUMNS FROM clearance_approvals_log LIKE 'status'");
    if ($colStatusChk && $colStatusChk->num_rows > 0) { $hasStatusColumn = true; }
    $colRemarksChk = $conn->query("SHOW COLUMNS FROM clearance_approvals_log LIKE 'remarks'");
    if ($colRemarksChk && $colRemarksChk->num_rows > 0) { $hasRemarksColumn = true; }
}

// Detect whether clearance_approvals_log has an updated_at column
$hasUpdatedAtColumn = false;
if ($tblChk && $tblChk->num_rows > 0) {
    $colUpdatedChk2 = $conn->query("SHOW COLUMNS FROM clearance_approvals_log LIKE 'updated_at'");
    if ($colUpdatedChk2 && $colUpdatedChk2->num_rows > 0) { $hasUpdatedAtColumn = true; }
}

// Helper: insert or update clearance_approvals_log safely depending on available columns
// Detect whether clearance_approvals_log has an or_num column and whether it's nullable
$hasOrNumColumn = false;
$orNumNullable = true;
if ($tblChk && $tblChk->num_rows > 0) {
    $colOrNumChk = $conn->query("SHOW COLUMNS FROM clearance_approvals_log LIKE 'or_num'");
    if ($colOrNumChk && $colOrNumChk->num_rows > 0) {
        $hasOrNumColumn = true;
        $colInfo = $colOrNumChk->fetch_assoc();
        $orNumNullable = (strtoupper($colInfo['Null'] ?? 'YES') === 'YES');
    }
}

function insert_clearance_log($conn, $student_id, $department_field, $staff_id, $staff_name, $statusVal, $remarks, $hasStaffColumn, $hasDeptColumn, $hasStaffNameColumn, $hasStatusColumn, $hasRemarksColumn, $hasUpdatedAtColumn, $hasOrNumColumn, $orNumValue) {
    $cols = [];
    $placeholders = [];
    $values = [];
    $updates = [];

    // student_id always
    $cols[] = 'student_id'; $placeholders[] = '?'; $values[] = $student_id;

    if ($hasDeptColumn) { $cols[] = 'department_field'; $placeholders[] = '?'; $values[] = $department_field; $updates[] = "department_field=VALUES(department_field)"; }
    if ($hasStaffColumn) { $cols[] = 'staff_id'; $placeholders[] = '?'; $values[] = $staff_id; $updates[] = "staff_id=VALUES(staff_id)"; }
    if ($hasStaffNameColumn) { $cols[] = 'staff_name'; $placeholders[] = '?'; $values[] = $staff_name; $updates[] = "staff_name=VALUES(staff_name)"; }

    // or_num: include if exists; use provided $orNumValue (can be NULL)
    if ($hasOrNumColumn) {
        if (is_null($orNumValue)) {
            $cols[] = 'or_num'; $placeholders[] = 'NULL'; $updates[] = "or_num=VALUES(or_num)";
        } else {
            $cols[] = 'or_num'; $placeholders[] = '?'; $values[] = $orNumValue; $updates[] = "or_num=VALUES(or_num)";
        }
    }

    // status and remarks — only include if the columns exist
    if ($hasStatusColumn) { $cols[] = 'status'; $placeholders[] = '?'; $values[] = $statusVal; $updates[] = "status=VALUES(status)"; }
    if ($hasRemarksColumn) { $cols[] = 'remarks'; $placeholders[] = '?'; $values[] = $remarks; $updates[] = "remarks=VALUES(remarks)"; }

    $colList = implode(', ', $cols);
    $phList = implode(', ', $placeholders);
    $updateList = implode(', ', $updates);
    $sql = "INSERT INTO clearance_approvals_log ($colList) VALUES ($phList) ON DUPLICATE KEY UPDATE $updateList";
    if ($hasUpdatedAtColumn) {
        $sql .= ", updated_at=CURRENT_TIMESTAMP";
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;

    // bind params dynamically
    $types = str_repeat('s', count($values));
    $bind_names = [];
    $bind_names[] = & $types;
    for ($i = 0; $i < count($values); $i++) {
        $bind_names[] = & $values[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_names);
    $res = $stmt->execute();
    $stmt->close();
    return $res;
}

$student_id = isset($_POST['student_id']) ? trim($_POST['student_id']) : null;
$department_field = isset($_POST['department_field']) ? trim($_POST['department_field']) : null;
$status = isset($_POST['status']) ? trim(strtolower($_POST['status'])) : null;
$remarks = isset($_POST['remarks']) ? trim($_POST['remarks']) : null;

// Validate inputs
if (empty($student_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Student ID is required and cannot be empty']);
    exit;
}

if (empty($department_field)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Department field is required and cannot be empty']);
    exit;
}

if (empty($status)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Status is required and cannot be empty']);
    exit;
}

// Validate status value
$valid_statuses = ['cleared', 'pending', 'in progress'];
$normalized_status = strtolower($status);
if (!in_array($normalized_status, $valid_statuses, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status value. Must be: cleared, pending, or in progress']);
    exit;
}

// Map UI statuses to ENUM-safe values for dept columns (records_officer_status etc.)
// ENUM only supports: Pending, Approved, Rejected
$statusMap = [
    'pending' => 'Pending',
    'in progress' => 'Pending',   // ENUM can't store 'In Progress' — keep as Pending
    'cleared' => 'Approved'   // ENUM uses 'Approved' for cleared
];
$deptStatus = $statusMap[$normalized_status]; // Safe for ENUM dept columns
$realStatus = ucfirst($normalized_status);    // Real display value: 'Pending', 'In progress', 'Cleared'
$unitStatsStatus = $realStatus;                // Written to unit_stats (VARCHAR — no constraint)

// Validate department field
$valid_departments = [
    'records_officer_status',
    'computer_lab_status',
    'librarian_status',
    'finance_officer_status',
    'cbrc_status',
    'admission_officer_status',
    'registrar_status',
    'school_cashier_status',
    'property_custodian_status',
    'scholarship_coordinator_status',
    'guidance_counselor_status',
    'student_services_status',
    'thesis_status'
];
if (!in_array($department_field, $valid_departments)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid department field']);
    exit;
}

try {
    // Validation to prevent duplicate records for this specific student is handled by
    // checking if record exists before insert.

    // First, ensure the student_id exists in the system
    $checkStudentSql = "SELECT user_id, full_name FROM user_database WHERE user_id = ? LIMIT 1";
    $checkStmt = $conn->prepare($checkStudentSql);

    if (!$checkStmt) {
        throw new Exception("Student validation prepare failed: " . $conn->error);
    }

    $checkStmt->bind_param('s', $student_id);
    $checkStmt->execute();
    $studentResult = $checkStmt->get_result();
    $studentRow = $studentResult->fetch_assoc();
    $checkStmt->close();

    if (!$studentRow) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Student not found in system']);
        error_log("ERROR: Student not found: $student_id");
        exit;
    }

    $student_name = $studentRow['full_name'];

    // Check if record already exists (after cleanup)
    $checkExistsSql = "SELECT clearance_id FROM student_clearance_database WHERE student_id = ? LIMIT 1";
    $checkExistsStmt = $conn->prepare($checkExistsSql);
    $checkExistsStmt->bind_param('s', $student_id);
    $checkExistsStmt->execute();
    $existsResult = $checkExistsStmt->get_result();
    $recordExists = $existsResult->num_rows > 0;
    $checkExistsStmt->close();

    error_log("DEBUG: student_id=$student_id, department_field=$department_field, recordExists=$recordExists, status=$status");

    if ($recordExists) {
        // Update existing record - update overall unit status
        $updateSql = "UPDATE student_clearance_database 
                      SET unit_stats = ? 
                      WHERE student_id = ?";
        $updateStmt = $conn->prepare($updateSql);
        if (!$updateStmt) {
            throw new Exception("Update prepare failed: " . $conn->error);
        }
        $updateStmt->bind_param('ss', $unitStatsStatus, $student_id);
        if (!$updateStmt->execute()) {
            throw new Exception("Update execute failed: " . $updateStmt->error);
        }
        $updateStmt->close();

        // Delete stale log entry if this staff previously saved under a different dept_field
        if ($hasStaffColumn) {
            if ($hasDeptColumn) {
                $delStale = $conn->prepare("DELETE FROM clearance_approvals_log WHERE student_id = ? AND staff_id = ? AND department_field != ?");
                if ($delStale) { $delStale->bind_param('sss', $student_id, $staff_id, $department_field); $delStale->execute(); $delStale->close(); }
            } else {
                $delStale = $conn->prepare("DELETE FROM clearance_approvals_log WHERE student_id = ? AND staff_id = ?");
                if ($delStale) { $delStale->bind_param('ss', $student_id, $staff_id); $delStale->execute(); $delStale->close(); }
            }
        } else {
            // no staff_id column
            if ($hasDeptColumn) {
                $delStale = $conn->prepare("DELETE FROM clearance_approvals_log WHERE student_id = ? AND department_field != ?");
                if ($delStale) { $delStale->bind_param('ss', $student_id, $department_field); $delStale->execute(); $delStale->close(); }
            } else {
                $delStale = $conn->prepare("DELETE FROM clearance_approvals_log WHERE student_id = ?");
                if ($delStale) { $delStale->bind_param('s', $student_id); $delStale->execute(); $delStale->close(); }
            }
        }

        // Insert/update per-staff clearance remarks safely based on available columns
        // Determine a valid OR number for this student if the log table expects one
        $orNumToInsert = null;
        if (isset($hasOrNumColumn) && $hasOrNumColumn) {
            $orStmt = $conn->prepare("SELECT or_num FROM payment_record_database WHERE student_id = ? AND COALESCE(or_num,'') <> '' ORDER BY payment_date DESC LIMIT 1");
            if ($orStmt) {
                $orStmt->bind_param('s', $student_id);
                $orStmt->execute();
                $orRes = $orStmt->get_result();
                if ($orRow = $orRes->fetch_assoc()) { $orNumToInsert = $orRow['or_num']; }
                $orStmt->close();
            }
        }

        insert_clearance_log($conn, $student_id, $department_field, $staff_id, $staff_name, $realStatus, $remarks, $hasStaffColumn, $hasDeptColumn, $hasStaffNameColumn, $hasStatusColumn, $hasRemarksColumn, $hasUpdatedAtColumn, $hasOrNumColumn, $orNumToInsert);

        $operation = "updated";
    } else {
        $clearance_id = 'CLR-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 7));
        // Fetch student info to populate clearance database
        $stuInfoSql = "SELECT student_lastname, student_firstname, student_middlename, student_year, student_course, student_section, permanent_address FROM student_information_database WHERE student_id = ? LIMIT 1";
        $stuStmt = $conn->prepare($stuInfoSql);
        $stuStmt->bind_param('s', $student_id);
        $stuStmt->execute();
        $stuRes = $stuStmt->get_result()->fetch_assoc();
        $stuStmt->close();
        
        $s_last = $stuRes['student_lastname'] ?? null;
        $s_first = $stuRes['student_firstname'] ?? null;
        $s_mid = $stuRes['student_middlename'] ?? null;
        $s_yr = $stuRes['student_year'] ?? null;
        $s_course = $stuRes['student_course'] ?? null;
        $s_sec = $stuRes['student_section'] ?? null;
        $s_addr = $stuRes['permanent_address'] ?? null;

        // Create new record
        $insertSql = "INSERT INTO student_clearance_database (
                        clearance_id, student_id, unit_stats,
                        student_lastname, student_firstname, student_middlename,
                        student_year, student_course, student_section, school_year, student_address
                      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertSql);
        if (!$insertStmt) {
            throw new Exception("Insert prepare failed: " . $conn->error);
        }
        $insertStmt->bind_param('sssssssssss', $clearance_id, $student_id, $unitStatsStatus, $s_last, $s_first, $s_mid, $s_yr, $s_course, $s_sec, $s_yr, $s_addr);
        if (!$insertStmt->execute()) {
            throw new Exception("Insert execute failed: " . $insertStmt->error);
        }
        $insertStmt->close();

        // Department status is stored per-staff in log

        // Delete stale log entry if this staff previously saved under a different dept_field
        if ($hasStaffColumn) {
            if ($hasDeptColumn) {
                $delStale2 = $conn->prepare("DELETE FROM clearance_approvals_log WHERE student_id = ? AND staff_id = ? AND department_field != ?");
                if ($delStale2) { $delStale2->bind_param('sss', $student_id, $staff_id, $department_field); $delStale2->execute(); $delStale2->close(); }
            } else {
                $delStale2 = $conn->prepare("DELETE FROM clearance_approvals_log WHERE student_id = ? AND staff_id = ?");
                if ($delStale2) { $delStale2->bind_param('ss', $student_id, $staff_id); $delStale2->execute(); $delStale2->close(); }
            }
        } else {
            if ($hasDeptColumn) {
                $delStale2 = $conn->prepare("DELETE FROM clearance_approvals_log WHERE student_id = ? AND department_field != ?");
                if ($delStale2) { $delStale2->bind_param('ss', $student_id, $department_field); $delStale2->execute(); $delStale2->close(); }
            } else {
                $delStale2 = $conn->prepare("DELETE FROM clearance_approvals_log WHERE student_id = ?");
                if ($delStale2) { $delStale2->bind_param('s', $student_id); $delStale2->execute(); $delStale2->close(); }
            }
        }

        // Insert/update per-staff clearance remarks safely based on available columns
        $orNumToInsert = null;
        if (isset($hasOrNumColumn) && $hasOrNumColumn) {
            $orStmt = $conn->prepare("SELECT or_num FROM payment_record_database WHERE student_id = ? AND COALESCE(or_num,'') <> '' ORDER BY payment_date DESC LIMIT 1");
            if ($orStmt) {
                $orStmt->bind_param('s', $student_id);
                $orStmt->execute();
                $orRes = $orStmt->get_result();
                if ($orRow = $orRes->fetch_assoc()) { $orNumToInsert = $orRow['or_num']; }
                $orStmt->close();
            }
        }
        insert_clearance_log($conn, $student_id, $department_field, $staff_id, $staff_name, $realStatus, $remarks, $hasStaffColumn, $hasDeptColumn, $hasStaffNameColumn, $hasStatusColumn, $hasRemarksColumn, $hasUpdatedAtColumn, $hasOrNumColumn, $orNumToInsert);

        $operation = "created";
    }

    // Verify the data was saved by fetching it back
    $verifySql = "SELECT clearance_id FROM student_clearance_database WHERE student_id = ? LIMIT 1";
    $verifyStmt = $conn->prepare($verifySql);
    $verifyStmt->bind_param('s', $student_id);
    $verifyStmt->execute();
    $verifyResult = $verifyStmt->get_result();
    $verifyRow = $verifyResult->fetch_assoc();
    $verifyStmt->close();

    if ($verifyRow) {
        error_log("SUCCESS: $operation $department_field status for $student_id ($student_name) to $deptStatus.");

        // Notify all roles (Admin, Staff, and Student) about clearance status change
        require_once $_SERVER['DOCUMENT_ROOT'] . '/DORCFSRF/api/notification_system.php';
        $prettyDept = ucwords(str_replace('_status', '', str_replace('_', ' ', $department_field)));
        $displayStatus = ucwords($normalized_status);
        createClearanceStatusNotification($conn, $student_id, $student_name, $displayStatus . ' - ' . $prettyDept);

        include_once $_SERVER['DOCUMENT_ROOT'] . '/DORCFSRF/api/log_audit.php';
        log_activity($conn, $staff_id, $staff_name, $role, 'Clearance', "Updated $prettyDept status to $displayStatus for student ($student_id)", 'Success', 'Clearance', $verifyRow['clearance_id']);

        echo json_encode([
            'success' => true,
            'message' => "Clearance status $operation successfully",
            'operation' => $operation,
            'student_id' => $student_id,
            'student_name' => $student_name,
            'status' => $status,
            'department' => $department_field,
            'db_status' => $deptStatus,
            'unit_status' => $unitStatsStatus,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        throw new Exception("Verification failed - status mismatch or record not found");
    }

} catch (Exception $e) {
    error_log("Save clearance error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>