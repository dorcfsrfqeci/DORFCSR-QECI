<?php
ini_set('session.gc_maxlifetime', 31536000);
session_set_cookie_params(31536000);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') {
    header('Location: /DORCFSRF/pages/login_form/index.php');
    exit;
}
include __DIR__ . '/../../db_connect.php';
include __DIR__ . '/../../api/notification_system.php';

$full_name = $_SESSION['full_name'] ?? $_SESSION['username'];
$admin_id = $_SESSION['user_id'];
$student_id = $_GET['student_id'] ?? null;

// Determine staff department based on staff_id or full_name
$staff_department = '';
$staff_department_field = '';
if (stripos($full_name, 'Janie') !== false || $admin_id === 'STF001') {
    $staff_department = 'Records Officer';
    $staff_department_field = 'records_officer_status';
} elseif (stripos($full_name, 'Reyniel') !== false || $admin_id === 'STF002') {
    $staff_department = 'Computer Lab';
    $staff_department_field = 'computer_lab_status';
} elseif (stripos($full_name, 'Maritess') !== false || $admin_id === 'STF003') {
    $staff_department = 'Librarian';
    $staff_department_field = 'librarian_status';
} elseif (stripos($full_name, 'Chin-Chin') !== false || $admin_id === 'STF004') {
    $staff_department = 'Finance Officer';
    $staff_department_field = 'finance_officer_status';
} elseif (stripos($full_name, 'Kristine') !== false || $admin_id === 'STF005') {
    $staff_department = 'CBRC';
    $staff_department_field = 'cbrc_status';
}

// Ensure a valid department field exists before querying
$allowedDepartmentFields = [
    'records_officer_status',
    'computer_lab_status',
    'librarian_status',
    'finance_officer_status',
    'cbrc_status'
];
if (!in_array($staff_department_field, $allowedDepartmentFields, true)) {
    $staff_department = 'Records Officer';
    $staff_department_field = 'records_officer_status';
    error_log("WARNING: Invalid staff department field for staff_id=$admin_id; defaulting to $staff_department_field");
}

// Get unread notification count
$unreadCount = getUnreadNotificationCount($conn, $admin_id);

// Fetch student information
$student_info = null;
$submitted_documents = [];
$current_status = 'pending'; // Default status
if ($student_id) {
    $studentSql = "SELECT student_id, student_firstname, student_lastname, CONCAT(LOWER(REPLACE(CONCAT(student_firstname, student_lastname), ' ', '')), '@gmail.com') as email FROM student_information_database WHERE student_id = ? LIMIT 1";
    $studentStmt = $conn->prepare($studentSql);
    $studentStmt->bind_param('s', $student_id);
    $studentStmt->execute();
    $studentResult = $studentStmt->get_result();
    $student_info = $studentResult->fetch_assoc();
    $studentStmt->close();

    // Fetch clearance status from database - use unit_stats for the current status label
    $statusSql = "SELECT unit_stats, remarks FROM student_clearance_database WHERE student_id = ? LIMIT 1";
    $statusStmt = $conn->prepare($statusSql);
    $statusStmt->bind_param('s', $student_id);
    $statusStmt->execute();
    $statusResult = $statusStmt->get_result();
    if ($statusResult && $statusResult->num_rows > 0) {
        $statusRow = $statusResult->fetch_assoc();
        $dbUnitStatus = strtolower($statusRow['unit_stats'] ?? 'pending');
        if (in_array($dbUnitStatus, ['pending', 'in progress', 'cleared'], true)) {
            $current_status = $dbUnitStatus;
        } else {
            $current_status = 'pending';
        }
    }
    $statusStmt->close();
    
    // Debug logging
    error_log("DEBUG: Staff $admin_id ($staff_department) loaded status for student_id=$student_id: current_status=$current_status");

    // Fetch submitted documents
    $docsSql = "SELECT original_name, uploaded_at, file_path FROM clearance_document_uploads WHERE student_id = ? ORDER BY uploaded_at DESC";
    $docsStmt = $conn->prepare($docsSql);
    $docsStmt->bind_param('s', $student_id);
    $docsStmt->execute();
    $docsResult = $docsStmt->get_result();
    while ($doc = $docsResult->fetch_assoc()) {
        $submitted_documents[] = $doc;
    }
    $docsStmt->close();
}

// Get stored profile image
$profile_image = '/DORCFSRF/assets/admin_profiles/' . $admin_id . '_profile.jpg';
$profile_image_alt = '/DORCFSRF/assets/admin_profiles/' . $admin_id . '_profile.png';
$profile_image_alt2 = '/DORCFSRF/assets/admin_profiles/' . $admin_id . '_profile.gif';

// Check which image exists
if (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image)) {
    $profile_image_url = $profile_image . '?t=' . filemtime($_SERVER['DOCUMENT_ROOT'] . $profile_image);
} elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image_alt)) {
    $profile_image_url = $profile_image_alt . '?t=' . filemtime($_SERVER['DOCUMENT_ROOT'] . $profile_image_alt);
} elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image_alt2)) {
    $profile_image_url = $profile_image_alt2 . '?t=' . filemtime($_SERVER['DOCUMENT_ROOT'] . $profile_image_alt2);
} else {
    $profile_image_url = null;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clearance Requirement Details</title>
    <link rel="stylesheet" href="/DORCFSRF/css/dashboard.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
</head>

<body>
    <div class="container">
        <aside class="sidebar">
            <img src="/DORCFSRF/assets/QECI LOGO.jpg" alt="qecilogo" />
            <nav>
                <a href="dashboard.php"><i class="bx bxs-dashboard"> </i> Dashboard</a>
                <a href="form.php"><i class="bx bx-notepad"></i> Form</a>
                <a href="clearance_status.php" class="active"><i class="bx bxs-graduation"></i>
                    Clearance Status</a>
                <a href="payment_transaction.php"><i class="bx bx-coin-stack"></i> Payment
                    Transaction</a>
                <a href="scholastic_record_status.php"><i class="bx bx-bar-chart-alt-2"></i>
                    Scholastic Record Status</a>
                <a href="completion_report.php"><i class="bx bxs-calendar"></i> Completion
                    Report</a>
                <p>SETTINGS</p>
          <a href="/DORCFSRF/pages/admin_dashboard/audit_trail.php"><i class="bx bx-history"></i> Audit Trail</a>
        <a href="/DORCFSRF/pages/admin_dashboard/account_management.php"><i class='bx bx-briefcase-alt'></i> Account Management</a>
                <a href="account_settings.php"><i class="bx bx-cog"></i> Account
                    Settings</a>
            </nav>
        </aside>

        <div class="content-wrapper">
            <header class="header">
                <div class="">
                    <svg viewBox="0 0 24 24">
                        <path d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 1 0 3 10.5a7.5 7.5 0 0 0 13.65 6.15z" stroke="#888"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none" />
                    </svg>
                </div>
                <div class="user-icon">
                    <div class="notification-wrapper" id="notif-trigger" style="position: relative; display: inline-block; cursor: pointer;">
                        <i class="bx bx-bell"></i>
                        <?php if ($unreadCount > 0): ?>
                            <span class="notification-badge" style="position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; border-radius: 50%; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold;"><?php echo $unreadCount; ?></span>
                        <?php endif; ?>
                    </div>
                    <a href="/DORCFSRF/pages/click_button/aboutus.php"><i class="bx bx-globe bx-flip-vertical"></i></a>
                    <div class="user-menu-container">
                        <div class="profile-icon-wrapper" id="profile-toggle">
                            <?php if ($profile_image_url): ?>
                                <img src="<?php echo htmlspecialchars($profile_image_url); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;" />
                            <?php else: ?>
                                <i class="bx bxs-user-circle"></i>
                            <?php endif; ?>
                        </div>
                        <div class="profile-card" id="profile-dropdown">
                            <div class="card-header-bg"></div>
                            <div class="profile-img">
                                <?php if ($profile_image_url): ?>
                                    <img src="<?php echo htmlspecialchars($profile_image_url); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;" />
                                <?php else: ?>
                                    <i class="bx bxs-user-circle" style="font-size: 70px; color: #fff;"></i>
                                <?php endif; ?>
                            </div>
                            <div class="profile-info">
                                <h3><?php echo htmlspecialchars($full_name); ?></h3>
                                <p>ADMIN</p>
                            </div>
                            <div class="card-actions">
                                <a href="account_settings.php" class="action-btn action-btn-primary">Profile</a>
                                <a href="/DORCFSRF/pages/logout.php" class="action-btn action-btn-secondary">Sign Out</a>
                            </div>
                            <script src="/DORCFSRF/pages/script/dropdown.js"></script>
                        </div>
                    </div>

                    <!-- Notification Dropdown -->
                    <div class="notification-dropdown" id="notif-dropdown" style="display: none; position: absolute; top: 60px; right: 80px; width: 350px; background: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 1000; max-height: 400px; overflow-y: auto;">
                        <div style="padding: 15px 20px; border-bottom: 1px solid #eee; background: #f8f9fa; border-radius: 8px 8px 0 0;">
                            <h4 style="margin: 0; color: #333; font-size: 16px;">Notifications</h4>
                        </div>
                        <div style="padding: 30px 15px; text-align: center; color: #999;">
                            <p>Check notifications on main dashboard</p>
                        </div>
                    </div>
            </header>
            <main class="main-content">
                <div class="details-card" style="max-width: 1000px; margin: 0 auto; padding: 40px 20px;">
                    <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 20px; width: 100%;">
                        <?php 
                            $source = $_GET['source'] ?? '';
                            $back_url = ($source === 'dashboard') ? '/DORCFSRF/pages/admin_dashboard/dashboard.php' : '/DORCFSRF/pages/admin_dashboard/clearance_status.php';
                        ?>
                        <button onclick="window.location.href='<?php echo $back_url; ?>'" style="background: #6c757d; color: white; padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; display: flex; align-items: center; gap: 5px;">
                            <i class='bx bx-arrow-back'></i> Back
                        </button>
                        <h2 style="margin: 0;">Clearance Requirement Details</h2>
                    </div>

                    <div class="info-section">
                        <div style="font-size: 2rem; color: #4b2c71;">👤</div>
                        <div class="info-content">
                            <div class="info-header">Student Information</div>
                            <div class="grid-info">
                                <div>Name:</div>
                                <div id="stu-name" style="font-style: italic; font-weight: bold;"><?php echo $student_info ? htmlspecialchars($student_info['student_firstname'] . ' ' . $student_info['student_lastname']) : 'Unknown'; ?></div>
                                <div>Student ID:</div>
                                <div style="font-style: italic;"><?php echo $student_info ? htmlspecialchars($student_info['student_id']) : 'N/A'; ?></div>
                                <div>Unit Concerned:</div>
                                <div style="font-weight: bold;">Thesis</div>
                                <div><?php echo $staff_department; ?> Status:</div>
                                <div>
                                    <div class="dropdown">
                                        <button onclick="toggleDrop('main-status')" class="dropbtn btn-<?php echo ($current_status === 'in progress') ? 'inprogress' : $current_status; ?>" id="main-btn">
                                            <?php echo strtoupper(ucfirst($current_status)); ?> ▾
                                        </button>
                                        <div id="main-status" class="dropdown-content">
                                            <button class="text-inprogress"
                                                onclick="updateStatus('main', 'In Progress', 'btn-inprogress')">IN
                                                PROGRESS</button>
                                            <button class="text-pending"
                                                onclick="updateStatus('main', 'Pending', 'btn-pending')">PENDING</button>
                                            <button class="text-cleared"
                                                onclick="updateStatus('main', 'Cleared', 'btn-cleared')">CLEARED</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="info-section" style="flex-direction: column;">
                        <div class="info-header">
                            <i class="bx bxs-file-doc" style="font-size:1.3rem; vertical-align:middle; margin-right:6px;"></i>
                            Requirements (Uploaded Documents)
                        </div>

                        <!-- Header row -->
                        <div style="display:grid; grid-template-columns:1fr 140px 180px; gap:12px;
                                    padding:10px 14px; margin-top:12px;
                                    background:#f5f5f5; border-radius:6px;
                                    font-weight:700; font-size:12px; color:#555; text-transform:uppercase; letter-spacing:.5px;">
                            <span>Documents</span>
                            <span style="text-align:center;">Status</span>
                            <span style="text-align:right;">Updated</span>
                        </div>

                        <?php if (!empty($submitted_documents)): ?>
                            <?php foreach ($submitted_documents as $doc): ?>
                                <div style="display:grid; grid-template-columns:1fr 140px 180px; gap:12px;
                                            padding:12px 14px; border-bottom:1px solid #eee; align-items:center;">
                                    <span>
                                        <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank"
                                           style="text-decoration:none; color:#1565c0; font-weight:500;">
                                            <?php echo htmlspecialchars($doc['original_name']); ?>
                                        </a>
                                    </span>
                                    <span style="text-align:center;">
                                        <span style="color:#2e7d32; font-weight:700; font-size:12px;
                                                     background:#e8f5e9; padding:4px 10px;
                                                     border-radius:4px; white-space:nowrap;">
                                            SUBMITTED
                                        </span>
                                    </span>
                                    <span style="color:#666; font-size:13px; white-space:nowrap; text-align:right;">
                                        <?php echo date('M j, Y g:i A', strtotime($doc['uploaded_at'])); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="padding:20px 14px; color:#888; font-style:italic; text-align:center;">
                                No documents submitted yet.
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="info-section" style="flex-direction: column;">
                        <div class="info-header">📤 Student Submitted Documents</div>
                        <?php if (!empty($submitted_documents)): ?>
                            <table class="req-table">
                                <thead>
                                    <tr>
                                        <th align="left">Document Name</th>
                                        <th align="center">Uploaded Date</th>
                                        <th align="right">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($submitted_documents as $doc): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($doc['original_name']); ?></td>
                                            <td align="center"><?php echo date('M j, Y H:i', strtotime($doc['uploaded_at'])); ?></td>
                                            <td align="right">
                                                <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" style="color: #007bff; text-decoration: none; font-weight: bold;">Download</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p style="color: #999; font-style: italic;">No documents submitted yet.</p>
                        <?php endif; ?>
                    </div>

                    <div class="info-section" style="flex-direction: column; margin-top: 20px;">
                        <div class="info-header" style="color: #d32f2f;">
                            <i class="fas fa-exclamation-circle"></i> Clearance Remarks
                        </div>
                        <textarea id="officer-remarks" rows="4" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-family: inherit; margin-top: 10px;" placeholder="Add remarks for the student (e.g., missing documents, fees)..."><?php echo htmlspecialchars($statusRow['remarks'] ?? ''); ?></textarea>
                    </div>

                    <div class="footer-actions" style="display: flex; gap: 10px; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                        <button class="cancel-btn" style="padding: 10px 20px; background: #ccc; color: #333; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;" onclick="cancelChanges()">Cancel</button>
                        <button class="save-btn" style="padding: 10px 20px; background: #4b2c71; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;" onclick="saveChanges()">Save Changes</button>
                    </div>
                    
                </div>
            </main>
        </div>
    </div>
    <script>
        // Dropdown Logic
        function toggleDrop(id) {
            // Close other dropdowns
            var dropdowns = document.getElementsByClassName("dropdown-content");
            for (var i = 0; i < dropdowns.length; i++) {
                if (dropdowns[i].id !== id) dropdowns[i].classList.remove('show');
            }
            document.getElementById(id).classList.toggle("show");
        }

        // Close on click outside
        window.onclick = function (event) {
            if (!event.target.matches('.dropbtn')) {
                var dropdowns = document.getElementsByClassName("dropdown-content");
                for (var i = 0; i < dropdowns.length; i++) {
                    dropdowns[i].classList.remove('show');
                }
            }
        }

        // Update Status Logic
        function updateStatus(targetId, text, className) {
            let btn = (targetId === 'main') ? document.getElementById('main-btn') : document.getElementById('btn-' + targetId);
            btn.innerHTML = text.toUpperCase() + ' ▾';
            btn.className = "dropbtn " + className;
        }

        function cancelChanges() {
            if (confirm("Are you sure? Any unsaved changes will be lost.")) {
                window.location.href = '<?php echo $back_url; ?>';
            }
        }

        // Save changes to database
        function saveChanges() {
            // Get student_id from URL parameter
            const params = new URLSearchParams(window.location.search);
            const studentId = params.get('student_id');
            
            // Validate student_id
            if (!studentId || studentId.trim() === '') {
                alert("Error: Student ID is missing from URL");
                console.error("Student ID is null or empty");
                return;
            }
            
            // Get the status values from the buttons
            const departmentStatus = document.getElementById('main-btn').textContent.trim().replace(' ▾', '').toLowerCase();
            const remarks = document.getElementById('officer-remarks') ? document.getElementById('officer-remarks').value : '';
            
            // Validate status
            if (!departmentStatus || departmentStatus.trim() === '') {
                alert("Error: Please select a status");
                return;
            }

            console.log("Sending: student_id=" + studentId + ", department=" + "<?php echo $staff_department_field; ?>" + ", status=" + departmentStatus);

            const formData = new FormData();
            formData.append('student_id', studentId.trim());
            formData.append('department_field', '<?php echo $staff_department_field; ?>');
            formData.append('status', departmentStatus);
            formData.append('remarks', remarks);

            fetch('/DORCFSRF/api/save_clearance_status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log("Response:", data);
                if (data.success) {
                    alert("✅ Changes saved successfully!\n\nStudent: " + data.student_name + "\nStatus: " + data.status.toUpperCase());
                    
                    // Wait 500ms to ensure database commit, then redirect
                    setTimeout(() => {
                        window.location.href = '/DORCFSRF/pages/admin_dashboard/clearance_status.php?t=' + new Date().getTime();
                    }, 500);
                } else {
                    alert("Error: " + (data.message || "Failed to save changes"));
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert("Error saving changes. Please try again.");
            });
        }
    </script>

    <!-- Notification Dropdown Toggle -->
    <script>
        document.getElementById('notif-trigger').addEventListener('click', function() {
            const dropdown = document.getElementById('notif-dropdown');
            dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        });

        // Close notification dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const trigger = document.getElementById('notif-trigger');
            const dropdown = document.getElementById('notif-dropdown');
            if (!trigger.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.style.display = 'none';
            }
        });
    </script>
<script src="/DORCFSRF/pages/script/prevent-back-button.js"></script>
</body>

</html>









