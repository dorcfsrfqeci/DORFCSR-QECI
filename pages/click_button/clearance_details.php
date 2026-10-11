<?php
ini_set('session.gc_maxlifetime', 31536000);
session_set_cookie_params(31536000);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Student') {
    header('Location: /DORCFSRF/pages/login_form/index.php');
    exit;
}
include __DIR__ . '/../../db_connect.php';

$full_name = $_SESSION['full_name'] ?? $_SESSION['username'];
$user_id = $_SESSION['user_id'];
$staff_id = $_GET['staff_id'] ?? null;
$clearance_id = $_GET['clearance_id'] ?? null;
$from = $_GET['from'] ?? 'dashboard'; // Default to dashboard, can be 'dashboard' or 'clearance'

// Debug: Log the incoming parameters
error_log("DEBUG clearance_details.php - staff_id: " . ($staff_id ?? 'NULL') . ", clearance_id: " . ($clearance_id ?? 'NULL'));

// Function to get staff profile image
function getStaffProfileImage($staff_id)
{
    $profile_image = '/DORCFSRF/assets/staff_profiles/' . $staff_id . '_profile.jpg';
    $profile_image_alt = '/DORCFSRF/assets/staff_profiles/' . $staff_id . '_profile.png';
    $profile_image_alt2 = '/DORCFSRF/assets/staff_profiles/' . $staff_id . '_profile.gif';

    if (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image)) {
        return $profile_image;
    } elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image_alt)) {
        return $profile_image_alt;
    } elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image_alt2)) {
        return $profile_image_alt2;
    }
    return null;
}

// Fetch staff member information if staff_id is provided
$staff_info = null;
if ($staff_id) {
    $staffSql = "SELECT user_id, full_name, email FROM user_database WHERE user_id = ? AND role = 'Staff'";
    $staffStmt = $conn->prepare($staffSql);
    if (!$staffStmt) {
        error_log("Prepare failed: " . $conn->error);
    } else {
        $staffStmt->bind_param('s', $staff_id);
        $staffStmt->execute();
        $staffResult = $staffStmt->get_result();
        $staff_info = $staffResult->fetch_assoc();

        // Debug: Log what was retrieved
        error_log("DEBUG staff_info: " . json_encode($staff_info));

        $staffStmt->close();
    }
} else {
    // Fallback: If no staff_id, try to get staff info from the unit_con in clearance data
    // This will be populated after we fetch clearance data below
}

// Get stored profile image
$profile_image = '/DORCFSRF/assets/student_profiles/' . $user_id . '_profile.jpg';
$profile_image_alt = '/DORCFSRF/assets/student_profiles/' . $user_id . '_profile.png';
$profile_image_alt2 = '/DORCFSRF/assets/student_profiles/' . $user_id . '_profile.gif';

if (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image)) {
    $profile_image_url = $profile_image;
} elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image_alt)) {
    $profile_image_url = $profile_image_alt;
} elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image_alt2)) {
    $profile_image_url = $profile_image_alt2;
} else {
    $profile_image_url = null;
}

// Map item to database field and officer info
$item = $_GET['item'] ?? 'records';
$department_field = '';
$unit_concerned = '';

// Map of item to database field, unit name, and staff search term
$item_mapping = [
    'records' => ['field' => 'records_officer_status', 'unit' => 'Records Officer', 'staff' => 'Janie'],
    'records_officer' => ['field' => 'records_officer_status', 'unit' => 'Records Officer', 'staff' => 'Janie'],
    'computer_lab' => ['field' => 'computer_lab_status', 'unit' => 'Computer Laboratory', 'staff' => 'Centeno'],
    'lab' => ['field' => 'computer_lab_status', 'unit' => 'Computer Laboratory', 'staff' => 'Centeno'],
    'librarian' => ['field' => 'librarian_status', 'unit' => 'Librarian', 'staff' => 'Maritess'],
    'thesis' => ['field' => 'thesis_status', 'unit' => 'Thesis', 'staff' => 'Maritess'],
    'finance' => ['field' => 'finance_officer_status', 'unit' => 'Finance Officer', 'staff' => 'Chin-Chin'],
    'cbrc' => ['field' => 'cbrc_status', 'unit' => 'Student Services Coordinator', 'staff' => 'Kristine'],
    'admission' => ['field' => 'admission_officer_status', 'unit' => 'Admission Officer', 'staff' => 'Azerelle'],
    'cashier' => ['field' => 'school_cashier_status', 'unit' => 'School Cashier', 'staff' => 'Sharon'],
    'property' => ['field' => 'property_custodian_status', 'unit' => 'Property Custodian', 'staff' => 'Jeck'],
    'services' => ['field' => 'student_services_status', 'unit' => 'Student Services Coordinator', 'staff' => 'Kristine'],
    'student_services' => ['field' => 'student_services_status', 'unit' => 'Student Services Coordinator', 'staff' => 'Kristine'],
    'scholarship' => ['field' => 'scholarship_coordinator_status', 'unit' => 'Scholarship Coordinator', 'staff' => 'Tabalan'],
    'guidance' => ['field' => 'guidance_counselor_status', 'unit' => 'Guidance Counselor', 'staff' => 'Shirley'],
    'registrar' => ['field' => 'registrar_status', 'unit' => 'Registrar', 'staff' => 'Carmencita'],
    'dean' => ['field' => 'registrar_status', 'unit' => 'College Dean', 'staff' => 'Carmencita'],
];

if (isset($item_mapping[$item])) {
    $department_field = $item_mapping[$item]['field'];
    $unit_concerned = $item_mapping[$item]['unit'];

    // Try to fetch staff member if not found by staff_id
    if (!$staff_info) {
        $search_term = $item_mapping[$item]['staff'];
        $staffSql = "SELECT user_id, full_name, email FROM user_database WHERE role = 'Staff' AND full_name LIKE ? LIMIT 1";
        $staffStmt = $conn->prepare($staffSql);
        if ($staffStmt) {
            $search_pattern = '%' . $search_term . '%';
            $staffStmt->bind_param('s', $search_pattern);
            $staffStmt->execute();
            $staffResult = $staffStmt->get_result();
            $staff_info = $staffResult->fetch_assoc();
            $staffStmt->close();
        }
    }
} else {
    $department_field = 'records_officer_status';
    $unit_concerned = 'Records Officer';
}

// Fetch real clearance status
$clearance_data = null;
if ($clearance_id) {
    $stmt = $conn->prepare("SELECT * FROM student_clearance_database WHERE clearance_id = ? AND student_id = ?");
    $stmt->bind_param('ss', $clearance_id, $user_id);
    $stmt->execute();
    $clearance_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} else {
    $stmt = $conn->prepare("SELECT * FROM student_clearance_database WHERE student_id = ? ORDER BY clearance_id DESC LIMIT 1");
    $stmt->bind_param('s', $user_id);
    $stmt->execute();
    $clearance_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$current_status = 'Pending';
$updated_date = '-';
$db_clearance_id = 'N/A';

// --- PRIMARY: check clearance_approvals_log by staff_id (most accurate) ---
$logStatus = null;
// Determine a safe ORDER BY fragment for clearance_approvals_log queries
$logOrderBy = "";
$logTableCheckForOrder = $conn->query("SHOW TABLES LIKE 'clearance_approvals_log'");
if ($logTableCheckForOrder && $logTableCheckForOrder->num_rows > 0) {
    $colUpdated = $conn->query("SHOW COLUMNS FROM clearance_approvals_log LIKE 'updated_at'");
    if ($colUpdated && $colUpdated->num_rows > 0) {
        $logOrderBy = " ORDER BY updated_at DESC";
    } else {
        $colCreated = $conn->query("SHOW COLUMNS FROM clearance_approvals_log LIKE 'created_at'");
        if ($colCreated && $colCreated->num_rows > 0) {
            $logOrderBy = " ORDER BY created_at DESC";
        } else {
            $colId = $conn->query("SHOW COLUMNS FROM clearance_approvals_log LIKE 'id'");
            if ($colId && $colId->num_rows > 0) {
                $logOrderBy = " ORDER BY id DESC";
            } else {
                $logOrderBy = ""; // no reliable ordering column
            }
        }
    }
}
if ($staff_id) {
    $logTableCheck2 = $conn->query("SHOW TABLES LIKE 'clearance_approvals_log'");
    if ($logTableCheck2 && $logTableCheck2->num_rows > 0) {
            // Only select `status` if that column exists in the log table
            $colStatusChk = $conn->query("SHOW COLUMNS FROM clearance_approvals_log LIKE 'status'");
            if ($colStatusChk && $colStatusChk->num_rows > 0) {
                $logSql2 = "SELECT status FROM clearance_approvals_log WHERE student_id = ? AND staff_id = ?" . $logOrderBy . " LIMIT 1";
                $logStmt2 = $conn->prepare($logSql2);
                if ($logStmt2) {
                    $logStmt2->bind_param('ss', $user_id, $staff_id);
                    $logStmt2->execute();
                    $logResult2 = $logStmt2->get_result();
                    $logRow2 = $logResult2->fetch_assoc();
                    if ($logRow2)
                        $logStatus = $logRow2['status'];
                    $logStmt2->close();
                }
            } else {
                // No `status` column present in the log table; skip log-based status fallback
                $logStatus = null;
            }
    }
}

if ($clearance_data) {
    if ($logStatus !== null) {
        // Use log status (most accurate — stored by staff_id)
        $raw = strtolower(trim($logStatus));
    } else {
        // Fallback: read from dept-specific column
        $raw = strtolower(trim($clearance_data[$department_field] ?? ''));
    }

    // Normalize to display label
    if ($raw === 'in progress')
        $current_status = 'In Progress';
    elseif (in_array($raw, ['approved', 'cleared', 'completed']))
        $current_status = 'Cleared';
    elseif ($raw === 'pending' || $raw === '')
        $current_status = 'Pending';
    else
        $current_status = ucfirst($raw);

    $updated_date = '-';
    $db_clearance_id = $clearance_data['clearance_id'];
} elseif ($logStatus !== null) {
    // No clearance record, but log has a status
    $raw = strtolower(trim($logStatus));
    if ($raw === 'in progress')
        $current_status = 'In Progress';
    elseif (in_array($raw, ['approved', 'cleared', 'completed']))
        $current_status = 'Cleared';
    else
        $current_status = 'Pending';
}

// Determine back URL based on where user came from
$back_url = '/DORCFSRF/pages/student_dashboard/dashboard.php';
if ($from === 'clearance') {
    $back_url = '/DORCFSRF/pages/student_dashboard/clearance.php';
}

// If still no staff_info but we have clearance_data, try to fetch staff by unit mapping
if (!$staff_info && $clearance_data) {
    $unit_con = $clearance_data['unit_con'] ?? '';

    // Map unit_con to likely staff query
    $unit_search_terms = [
        'Thesis' => 'Maritess',
        'Librarian' => 'Maritess',
        'Finance Officer' => 'Chin-Chin',
        'Admission Officer' => 'Azerelle',
        'Lab./ComLab/ID' => 'Centeno',
        'Computer Laboratory' => 'Centeno',
        'School Cashier' => 'Sharon',
        'Property Custodian' => 'Jeck',
        'Student Services Coordinator' => 'Kristine',
        'Scholarship Coordinator' => 'Tabalan',
        'Guidance Counselor' => 'Shirley',
        'Records Officer' => 'Janie'
    ];

    if (isset($unit_search_terms[$unit_con])) {
        $search_term = $unit_search_terms[$unit_con];
        $staffSql = "SELECT user_id, full_name, email FROM user_database WHERE role = 'Staff' AND full_name LIKE ? LIMIT 1";
        $staffStmt = $conn->prepare($staffSql);
        if ($staffStmt) {
            $search_pattern = '%' . $search_term . '%';
            $staffStmt->bind_param('s', $search_pattern);
            $staffStmt->execute();
            $staffResult = $staffStmt->get_result();
            $staff_info = $staffResult->fetch_assoc();
            error_log("DEBUG fallback staff_info from unit: " . json_encode($staff_info));
            $staffStmt->close();
        }
    }
}

// Fetch uploaded documents for this staff/department
$uploaded_docs = [];
// Load per-staff private remarks for this student
$staff_remarks_for_student = 'No remarks provided.';
if ($staff_id) {
    // Documents
    $stmt = $conn->prepare("SELECT original_name, uploaded_at, file_path FROM clearance_document_uploads WHERE student_id = ? AND staff_id = ? ORDER BY uploaded_at DESC");
    $stmt->bind_param('ss', $user_id, $staff_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $uploaded_docs[] = $row;
    }
    $stmt->close();

    // Per-staff private remarks from clearance_approvals_log (primary: by staff_id)
    $logChkDtls = $conn->query("SHOW TABLES LIKE 'clearance_approvals_log'");
    if ($logChkDtls && $logChkDtls->num_rows > 0) {
        // Prefer staff_id if that column exists
        $colStaff = $conn->query("SHOW COLUMNS FROM clearance_approvals_log LIKE 'staff_id'");
        if ($colStaff && $colStaff->num_rows > 0) {
            $rmkStmt = $conn->prepare("SELECT remarks FROM clearance_approvals_log WHERE student_id = ? AND staff_id = ?" . $logOrderBy . " LIMIT 1");
            if ($rmkStmt) {
                $rmkStmt->bind_param('ss', $user_id, $staff_id);
                $rmkStmt->execute();
                $rmkRow = $rmkStmt->get_result()->fetch_assoc();
                if (!empty($rmkRow['remarks'])) {
                    $staff_remarks_for_student = $rmkRow['remarks'];
                }
                $rmkStmt->close();
            }
        } else {
            // staff_id column missing — try staff_name if available and we have staff_info
            $colName = $conn->query("SHOW COLUMNS FROM clearance_approvals_log LIKE 'staff_name'");
            if ($colName && $colName->num_rows > 0 && !empty($staff_info['full_name'])) {
                $rmkStmt = $conn->prepare("SELECT remarks FROM clearance_approvals_log WHERE student_id = ? AND staff_name = ?" . $logOrderBy . " LIMIT 1");
                if ($rmkStmt) {
                    $staff_full = $staff_info['full_name'];
                    $rmkStmt->bind_param('ss', $user_id, $staff_full);
                    $rmkStmt->execute();
                    $rmkRow = $rmkStmt->get_result()->fetch_assoc();
                    if (!empty($rmkRow['remarks'])) {
                        $staff_remarks_for_student = $rmkRow['remarks'];
                    }
                    $rmkStmt->close();
                }
            }
            // if still not found, we'll fallback later when staff_id is absent
        }
    }
} else {
    // Fallback: staff_id missing from URL — look up by department_field
    $logChkDtls2 = $conn->query("SHOW TABLES LIKE 'clearance_approvals_log'");
    if ($logChkDtls2 && $logChkDtls2->num_rows > 0) {
        // Check whether the clearance_approvals_log table has a department_field column
        $colDept = $conn->query("SHOW COLUMNS FROM clearance_approvals_log LIKE 'department_field'");
        $colStaff2 = $conn->query("SHOW COLUMNS FROM clearance_approvals_log LIKE 'staff_id'");

        if ($colDept && $colDept->num_rows > 0 && !empty($department_field)) {
            // We can filter by department_field
            if ($colStaff2 && $colStaff2->num_rows > 0) {
                $rmkStmt2 = $conn->prepare("SELECT remarks, staff_id FROM clearance_approvals_log WHERE student_id = ? AND department_field = ?" . $logOrderBy . " LIMIT 1");
            } else {
                $rmkStmt2 = $conn->prepare("SELECT remarks FROM clearance_approvals_log WHERE student_id = ? AND department_field = ?" . $logOrderBy . " LIMIT 1");
            }
            if ($rmkStmt2) {
                $rmkStmt2->bind_param('ss', $user_id, $department_field);
                $rmkStmt2->execute();
                $rmkRow2 = $rmkStmt2->get_result()->fetch_assoc();
                if (!empty($rmkRow2['remarks'])) {
                    $staff_remarks_for_student = $rmkRow2['remarks'];
                }
                $rmkStmt2->close();
            }
        } else {
            // department_field does not exist or is empty — fall back to most recent remarks by student only
            $stmtFallback = $conn->prepare("SELECT remarks FROM clearance_approvals_log WHERE student_id = ?" . $logOrderBy . " LIMIT 1");
            if ($stmtFallback) {
                $stmtFallback->bind_param('s', $user_id);
                $stmtFallback->execute();
                $fbRow = $stmtFallback->get_result()->fetch_assoc();
                if (!empty($fbRow['remarks'])) {
                    $staff_remarks_for_student = $fbRow['remarks'];
                }
                $stmtFallback->close();
            }
        }
    }
    // Also try to fetch documents by student only (no staff filter when staff_id missing)
    $stmt = $conn->prepare("SELECT original_name, uploaded_at, file_path FROM clearance_document_uploads WHERE student_id = ? ORDER BY uploaded_at DESC");
    $stmt->bind_param('s', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $uploaded_docs[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clearance Requirement Details</title>

    <link rel="stylesheet" href="/DORCFSRF/css/dashboard.css" />
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="/DORCFSRF/css/clearance-details.css" />
</head>

<body>
    <div class="container">
        <aside class="sidebar">
            <img src="/DORCFSRF/assets/QECI LOGO.jpg" alt="qecilogo" />
            <nav>
                <a href="/DORCFSRF/pages/student_dashboard/dashboard.php"><i class="bx bxs-dashboard"> </i>
                    Dashboard</a>
                <a href="/DORCFSRF/pages/student_dashboard/forms.php"><i class="bx bx-notepad"></i> Form</a>
                <a href="/DORCFSRF/pages/student_dashboard/clearance.php"><i class="bx bxs-graduation"></i> Clearance
                    Status</a>
                <a href="/DORCFSRF/pages/student_dashboard/payment.php"><i class="bx bx-coin-stack"></i> Payment
                    Transaction</a>
                <a href="/DORCFSRF/pages/student_dashboard/completion.php"><i class="bx bxs-calendar"></i> Completion
                    Report</a>
                <p>SETTINGS</p>
                <a href="/DORCFSRF/pages/student_dashboard/settings.php"><i class="bx bx-cog"></i> Account Settings</a>
        </aside>
        <div class="content-wrapper">
            <header class="header">
                <div class="">
                    <svg viewBox="0 0 24 24">
                        <path d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 1 0 3 10.5a7.5 7.5 0 0 0 13.65 6.15z" stroke="#888"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none" />
                    </svg>
                </div>
                <div class="user-icon" style="display: flex; gap: 15px; align-items: center;">
                    <div class="notification-container" id="notificationContainer">
                        <i class="bx bx-bell" id="notificationBell" title="Notifications"
                            style="font-size: 24px; cursor: pointer;"></i>
                        <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
                        <div class="notification-dropdown" id="notificationDropdown">
                            <div class="notification-header">
                                <h3>Notifications</h3>
                            </div>
                            <ul class="notification-list" id="notificationList">
                                <li class="notification-item text-center">Loading...</li>
                            </ul>
                        </div>
                    </div>
                    <a href="/DORCFSRF/pages/click_button/aboutus.php"><i class="bx bx-globe bx-flip-vertical"></i></a>
                    <div class="user-menu-container">
                        <div class="profile-icon-wrapper" id="profile-toggle">
                            <?php if ($profile_image_url): ?>
                                <img src="<?php echo htmlspecialchars($profile_image_url); ?>" alt="Profile"
                                    style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;" />
                            <?php else: ?>
                                <i class="bx bxs-user-circle"></i>
                            <?php endif; ?>
                        </div>
                        <div class="profile-card" id="profile-dropdown">
                            <div class="card-header-bg"></div>
                            <div class="profile-img">
                                <?php if ($profile_image_url): ?>
                                    <img src="<?php echo htmlspecialchars($profile_image_url); ?>" alt="Profile"
                                        style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;" />
                                <?php else: ?>
                                    <i class="bx bxs-user-circle" style="font-size: 70px; color: #fff;"></i>
                                <?php endif; ?>
                            </div>
                            <div class="profile-info">
                                <h3><?php echo htmlspecialchars($full_name); ?></h3>
                                <p>STUDENT</p>
                            </div>
                            <div class="card-actions">
                                <button class="action-btn">Profile</button>
                                <button class="action-btn">Sign Out</button>
                            </div>
                            <script src="/DORCFSRF/pages/script/dropdown.js"></script>
                        </div>
                    </div>
                </div>
            </header>

            <main class="main-content">
                <div class="clearance-details-overlay"
                    style="display: flex; justify-content: center; align-items: center; min-height: calc(100vh - 140px); padding: 2rem 1rem;">
                    <div class="clearance-details-modal" style="margin: auto;">
                        <div class="modal-header" style="position: relative; padding: 1.5rem 1rem 1rem 1rem;">
                            <h2 style="margin: 0;">Clearance Requirement Details</h2>
                            <button class="close-button"
                                style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; font-size: 24px; cursor: pointer; color: #333;"
                                onclick="window.location.href='<?php echo htmlspecialchars($back_url); ?>'"><i
                                    class="fas fa-times"></i></button>
                        </div>
                        <div class="modal-body">
                            <div class="info-card">
                                <div class="card-title-icon">
                                    <i class="bx bxs-user-detail"></i>
                                    <h3>Clearing Officer Information</h3>
                                </div>
                                <div class="info-grid">
                                    <div class="info-item">
                                        <span>Officer Name:</span>
                                        <strong
                                            id="officer-name"><?php echo htmlspecialchars($staff_info['full_name'] ?? 'N/A'); ?></strong>
                                    </div>
                                    <div class="info-item">
                                        <span>Email Id:</span>
                                        <strong
                                            id="officer-email"><?php echo htmlspecialchars($staff_info['email'] ?? 'N/A'); ?></strong>
                                    </div>
                                    <div class="info-item">
                                        <span>Unit Concerned:</span>
                                        <strong
                                            id="unit-concerned"><?php echo htmlspecialchars($unit_concerned ?? 'N/A'); ?></strong>
                                    </div>
                                    <div class="info-item">
                                        <span>Status:</span>
                                        <?php $badge_class = strtolower(str_replace(' ', '-', $current_status)); ?>
                                        <strong id="status-text"
                                            class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($current_status); ?></strong>
                                    </div>
                                    <div class="info-item">
                                        <span> Clearance ID</span>
                                        <strong
                                            id="clearance-id"><?php echo htmlspecialchars($db_clearance_id); ?></strong>
                                    </div>
                                    <?php if ($staff_info): ?>
                                        <div class="info-item"
                                            style="grid-column: 1 / -1; margin-top: 10px; padding-top: 10px; border-top: 1px solid #eee;">
                                            <span>Staff Profile:</span>
                                            <div style="display: flex; align-items: center; gap: 10px; margin-top: 8px;">
                                                <?php
                                                $staff_profile = getStaffProfileImage($staff_info['user_id']);
                                                if ($staff_profile):
                                                    ?>
                                                    <img src="<?php echo htmlspecialchars($staff_profile); ?>"
                                                        alt="<?php echo htmlspecialchars($staff_info['full_name']); ?>"
                                                        style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;" />
                                                <?php else: ?>
                                                    <i class="bx bxs-user-circle" style="font-size: 40px;"></i>
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($staff_info['full_name']); ?></strong><br>
                                                    <small style="color: #666;">User ID:
                                                        <?php echo htmlspecialchars($staff_info['user_id']); ?></small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="info-card">
                                <div class="card-title-icon">
                                    <i class="bx bxs-file-doc"></i>
                                    <h3>Requirements (Uploaded Documents)</h3>
                                </div>
                                <div class="requirements-table">
                                    <div class="table-header2">
                                        <span>Documents</span>
                                        <span>Status</span>
                                        <span>Updated</span>
                                    </div>
                                    <?php if (count($uploaded_docs) > 0): ?>
                                        <?php foreach ($uploaded_docs as $doc): ?>
                                            <div class="table-row2">
                                                <span>
                                                    <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank"
                                                        style="text-decoration: none; color: #1565c0;">
                                                        <?php echo htmlspecialchars($doc['original_name']); ?>
                                                    </a>
                                                </span>
                                                <span class="status-submitted"
                                                    style="color: #2e7d32; font-weight: bold; font-size: 12px; background: #e8f5e9; padding: 4px 8px; border-radius: 4px;">SUBMITTED</span>
                                                <span
                                                    style="color: #666; font-size: 14px;"><?php echo date('M j, Y g:i A', strtotime($doc['uploaded_at'])); ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="table-row2" style="justify-content: center; color: #888;">
                                            <span>No documents submitted yet.</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="info-card remarks-card" style="margin-top: 20px;">
                                <div class="card-title-icon">
                                    <i class="fas fa-exclamation-circle" style="color: #d32f2f; font-size: 24px;"></i>
                                    <h3 style="color: #d32f2f;">Remarks from Officer</h3>
                                </div>
                                <div class="remarks-content"
                                    style="padding: 15px; background: #ffebee; border-left: 4px solid #d32f2f; border-radius: 4px; margin-top: 15px;">
                                    <p id="remarks-text" style="margin: 0; color: #b71c1c; font-weight: 500;">
                                        <?php echo htmlspecialchars($staff_remarks_for_student); ?>
                                    </p>
                                </div>
                            </div>

                            <div class="upload-section" id="main-container">
                                <p>Upload Required Documents (JPG, PNG, PDF, DOCX)</p>

                                <div class="drop-zone" id="drop-zone"
                                    style="border: 2px dashed #ccc; padding: 20px; text-align: center; cursor: pointer;">
                                    <i class="bx bx-cloud-upload" style="font-size: 40px;"></i>
                                    <p>Click or Drop files here</p>
                                    <input type="file" id="file-input" hidden multiple
                                        accept=".jpg,.jpeg,.png,.pdf,.docx">
                                </div>

                                <div id="file-list" class="file-list" style="margin-top: 20px;"></div>

                                <div id="view-modal" class="modal" onclick="this.style.display='none'"
                                    style="display:none; position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000; justify-content:center; align-items:center;">
                                    <span class="close-modal"
                                        style="position:absolute; top:20px; right:30px; color:white; font-size:40px; cursor:pointer;">&times;</span>
                                    <img id="modal-img" src="" alt="Full View"
                                        style="width: 80%; height: 80%;border-radius: 10px; ">
                                </div>
                            </div>

                            <div class="modal-footer" style="margin-top: 20px; display: flex; gap: 10px;">
                                <a href="/DORCFSRF/pages/student_dashboard/clearance.php">
                                    <button class="cancel-btn">Cancel</button>
                                </a>
                                <button id="final-submit-btn" class="submit-btn">Submit Documents</button>
                            </div>
                        </div>
                    </div>
            </main>
        </div>
    </div>
    <script>
        const fileInput = document.getElementById('file-input');
        const fileList = document.getElementById('file-list');
        const dropZone = document.getElementById('drop-zone');
        const submitBtn = document.getElementById('final-submit-btn');
        const modal = document.getElementById('view-modal');
        const modalImg = document.getElementById('modal-img');

        let uploadedFiles = []; // Keeps track of files in memory

        // Click to trigger file selection
        dropZone.onclick = () => {
            if (!submitBtn.disabled) fileInput.click();
        };

        const allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'docx'];

        fileInput.addEventListener('change', function () {
            const files = Array.from(this.files);
            files.forEach(file => {
                const fileName = file.name.toLowerCase();
                const fileExt = fileName.split('.').pop();

                if (allowedExtensions.includes(fileExt)) {
                    // Check if file is already added to prevent duplicates
                    if (!uploadedFiles.some(f => f.name === file.name && f.size === file.size)) {
                        uploadedFiles.push(file);
                        renderFile(file);
                    }
                } else {
                    alert('Invalid file type: ' + file.name + '\nOnly JPG, PNG, PDF, and DOCX files are allowed.');
                }
            });
            // Reset input so the same file can be selected again if it was removed
            this.value = '';
        });

        // Drag and drop functionality
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            if (!submitBtn.disabled) {
                dropZone.style.backgroundColor = 'rgba(0, 0, 0, 0.05)';
                dropZone.style.borderColor = '#007bff';
            }
        });

        dropZone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            dropZone.style.backgroundColor = '';
            dropZone.style.borderColor = '#ccc';
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.style.backgroundColor = '';
            dropZone.style.borderColor = '#ccc';

            if (submitBtn.disabled) return;

            if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                const files = Array.from(e.dataTransfer.files);
                files.forEach(file => {
                    const fileName = file.name.toLowerCase();
                    const fileExt = fileName.split('.').pop();

                    if (allowedExtensions.includes(fileExt)) {
                        if (!uploadedFiles.some(f => f.name === file.name && f.size === file.size)) {
                            uploadedFiles.push(file);
                            renderFile(file);
                        }
                    } else {
                        alert('Invalid file type: ' + file.name + '\nOnly JPG, PNG, PDF, and DOCX files are allowed.');
                    }
                });
            }
        });

        function renderFile(file) {
            const fileId = Math.random().toString(36).substr(2, 9);
            const item = document.createElement('div');
            item.className = 'file-item';
            item.setAttribute('data-id', fileId);
            item.style = "display: flex; align-items: center; justify-content: space-between; padding: 10px; border: 1px solid #eee; margin-bottom: 5px; border-radius: 5px; background: #fdfdfd;";

            const isImage = file.type.startsWith('image/');
            const fileURL = URL.createObjectURL(file);

            item.innerHTML = `
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class='bx ${isImage ? 'bx-image' : 'bx-file'}'></i>
                    <span style="font-size: 14px;">${file.name}</span>
                </div>
                <div class="actions">
                    <button type="button" class="view-btn" style="cursor:pointer; padding: 2px 8px; background:#007bff; color:white; border:none; border-radius:3px;">View</button>
                    <button type="button" class="remove-btn" style="cursor:pointer; padding: 2px 8px; background:#dc3545; color:white; border:none; border-radius:3px; margin-left:5px;">&times;</button>
                </div>
            `;

            item.querySelector('.view-btn').onclick = (e) => {
                e.stopPropagation();
                if (isImage) {
                    modal.style.display = "flex";
                    modalImg.src = fileURL;
                } else {
                    window.open(fileURL, '_blank'); // Opens PDF/Docs in new tab
                }
            };


            item.querySelector('.remove-btn').onclick = (e) => {
                e.stopPropagation();
                uploadedFiles = uploadedFiles.filter(f => f !== file);
                item.remove();
            };

            fileList.appendChild(item);
        }


        submitBtn.onclick = () => {
            if (uploadedFiles.length === 0) {
                alert("Please upload at least one document.");
                return;
            }

            if (confirm("Are you sure? Once submitted, you cannot edit your uploads.")) {
                submitBtn.disabled = true;
                submitBtn.textContent = "Submitting...";

                // Create FormData to send files
                const formData = new FormData();
                uploadedFiles.forEach(file => {
                    formData.append('files[]', file);
                });

                // Add metadata
                formData.append('staff_id', new URLSearchParams(window.location.search).get('staff_id') || '');
                formData.append('clearance_id', new URLSearchParams(window.location.search).get('clearance_id') || '');

                console.log('Submitting files:', uploadedFiles);
                console.log('Staff ID:', new URLSearchParams(window.location.search).get('staff_id'));
                console.log('Clearance ID:', new URLSearchParams(window.location.search).get('clearance_id'));

                // Submit files to server
                fetch('/DORCFSRF/api/submit_clearance_documents.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => {
                        console.log('Response status:', response.status);
                        return response.json();
                    })
                    .then(data => {
                        console.log('Response data:', data);
                        if (data.success) {
                            alert("Documents submitted successfully!");

                            // Disable upload zone
                            dropZone.style.display = "none";

                            // Hide remove buttons
                            document.querySelectorAll('.remove-btn').forEach(btn => btn.remove());
                            document.querySelectorAll('.view-btn').forEach(btn => btn.remove());

                            // Redirect after 2 seconds
                            setTimeout(() => {
                                window.location.href = "/DORCFSRF/pages/student_dashboard/clearance.php";
                            }, 2000);
                        } else {
                            alert("Error: " + (data.message || "Failed to submit documents"));
                            submitBtn.disabled = false;
                            submitBtn.textContent = "Submit Documents";
                        }
                    })
                    .catch(error => {
                        console.error('Fetch error:', error);
                        alert("Error submitting files. Please try again. Check console for details.");
                        submitBtn.disabled = false;
                        submitBtn.textContent = "Submit Documents";
                    });
            }
        };
    </script>
    <script src="/DORCFSRF/pages/script/notifications.js"></script>
    <script>
        (function () {
            const params = new URLSearchParams(window.location.search);
            const item = params.get('item') || 'records';
            const items = {
                records: { unit: 'Records Officer' },
                computer_lab: { unit: 'Computer Laboratory' },
                librarian: { unit: 'Librarian' },
                finance: { unit: 'Finance Officer' },
                cbrc: { unit: 'Student Services Coordinator' },
                thesis: { unit: 'Thesis' },
                admission: { unit: 'Admission Officer' },
                lab: { unit: 'Lab / ComLab / ID' },
                cashier: { unit: 'School Cashier' },
                property: { unit: 'Property Custodian' },
                services: { unit: 'Student Services Coordinator' },
                student_services: { unit: 'Student Services Coordinator' },
                scholarship: { unit: 'Scholarship Coordinator' },
                guidance: { unit: 'Guidance Counselor' },
                registrar: { unit: 'Registrar' },
                dean: { unit: 'College Dean' }
            };

            const data = items[item] || items.records;
            document.getElementById('unit-concerned').textContent = data.unit;

        })();
    </script>

</body>

</html>









</script>

</body>

</html>