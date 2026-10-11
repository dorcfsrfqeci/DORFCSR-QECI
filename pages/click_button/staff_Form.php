<?php
ini_set('session.gc_maxlifetime', 31536000);
session_set_cookie_params(31536000);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . '/../../db_connect.php';

$role = $_SESSION['role'] ?? 'Staff';
$user_id = $_SESSION['user_id'] ?? '';
$full_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? '';

// URLs based on role
$dashUrl = $role === 'Admin' ? '/DORCFSRF/pages/admin_dashboard/dashboard.php' : '/DORCFSRF/pages/staff_dashboard/staff_dasboard.php';
$formUrl = $role === 'Admin' ? '/DORCFSRF/pages/admin_dashboard/form.php' : '/DORCFSRF/pages/staff_dashboard/staff_form.php';
$clearanceUrl = $role === 'Admin' ? '/DORCFSRF/pages/admin_dashboard/clearance_status.php' : '/DORCFSRF/pages/staff_dashboard/staff_clearance.php';
$paymentUrl = $role === 'Admin' ? '/DORCFSRF/pages/admin_dashboard/payment_transaction.php' : '/DORCFSRF/pages/staff_dashboard/staff_payment_transaction.php';
$scholasticUrl = $role === 'Admin' ? '/DORCFSRF/pages/admin_dashboard/scholastic_record_status.php' : '/DORCFSRF/pages/staff_dashboard/staff_scholastic_record.php';
$completionUrl = $role === 'Admin' ? '/DORCFSRF/pages/admin_dashboard/completion_report.php' : '/DORCFSRF/pages/staff_dashboard/staff_completion_report.php';
$settingsUrl = $role === 'Admin' ? '/DORCFSRF/pages/admin_dashboard/account_settings.php' : '/DORCFSRF/pages/staff_dashboard/staff_account_settings.php';

// Notifications and profile image
include __DIR__ . '/../../api/notification_system.php';
$unreadCount = getUnreadNotificationCount($conn, $user_id);
$notifications = getNotifications($conn, $user_id, 5);

$profile_folder = $role === 'Admin' ? 'admin_profiles' : 'staff_profiles';
$profile_image = "/assets/$profile_folder/" . $user_id . '_profile.jpg';
$profile_image_alt = "/assets/$profile_folder/" . $user_id . '_profile.png';
$profile_image_alt2 = "/assets/$profile_folder/" . $user_id . '_profile.gif';

$profile_image_url = null;
if (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image)) {
    $profile_image_url = $profile_image;
} elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image_alt)) {
    $profile_image_url = $profile_image_alt;
} elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image_alt2)) {
    $profile_image_url = $profile_image_alt2;
}

// Handle form update
$updateSuccess = false;
$updateError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id'])) {
    $studentId = $conn->real_escape_string($_POST['student_id']);
    $firstName = $conn->real_escape_string($_POST['first_name']);
    $middleName = $conn->real_escape_string($_POST['middle_name']);
    $lastName = $conn->real_escape_string($_POST['last_name']);
    $course = $conn->real_escape_string($_POST['course']);
    $year = $conn->real_escape_string($_POST['year']);
    $section = $conn->real_escape_string($_POST['section']);
    $semester = $conn->real_escape_string($_POST['semester']);
    $contactNum = $conn->real_escape_string($_POST['contact_num']);
    $permanentAddress = $conn->real_escape_string($_POST['permanent_address']);
    $reasonClearance = $conn->real_escape_string($_POST['reason_clearance']);
    $elementarySchool = $conn->real_escape_string($_POST['elementary_school']);
    $jhsSchool = $conn->real_escape_string($_POST['jhs_school']);
    $shsSchool = $conn->real_escape_string($_POST['shs_school']);
    $collegeComm = $conn->real_escape_string($_POST['college_community']);
    $scholasticDoc = $conn->real_escape_string($_POST['scholastic_document']);

    $updateQuery = "UPDATE student_information_database SET 
        student_firstname = '$firstName',
        student_middlename = '$middleName',
        student_lastname = '$lastName',
        student_course = '$course',
        student_year = '$year',
        student_section = '$section',
        student_sem = '$semester',
        contact_num = '$contactNum',
        permanent_address = '$permanentAddress',
        reason_clearance = '$reasonClearance',
        elementary_school = '$elementarySchool',
        jhs_school = '$jhsSchool',
        shs_school = '$shsSchool',
        college_community = '$collegeComm',
        scholastic_document = '$scholasticDoc'
        WHERE student_id = '$studentId'";

    if ($conn->query($updateQuery)) {
        $updateSuccess = true;
        include_once $_SERVER['DOCUMENT_ROOT'] . '/DORCFSRF/api/log_audit.php';
        log_activity($conn, $user_id, $full_name, $role, 'Forms', "Updated student form ($studentId)", 'Success', 'Forms', $studentId);
    } else {
        $updateError = 'Failed to update student information.';
    }
}

// Initialize student data
$studentData = null;

// Check if id parameter is provided
if (isset($_GET['id'])) {
    $studentId = $conn->real_escape_string($_GET['id']);
    $query = "SELECT * FROM student_information_database WHERE student_id = '$studentId'";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        $studentData = $result->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forms - Submission Complete</title>

    <link rel="stylesheet" href="/DORCFSRF/css/dashboard.css" />
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="/DORCFSRF/css/forms.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body>
    <div class="container">
        <aside class="sidebar">
            <img src="/DORCFSRF/assets/QECI LOGO.jpg" alt="qecilogo" />
            <nav>
                <a href="<?php echo $dashUrl; ?>"><i class="bx bxs-dashboard"> </i> Dashboard</a>
                <a href="<?php echo $formUrl; ?>" class="active"><i class="bx bx-notepad"></i> Form</a>
                <a href="<?php echo $clearanceUrl; ?>"><i class="bx bxs-graduation"></i> Clearance Status</a>
                <a href="<?php echo $paymentUrl; ?>"><i class="bx bx-coin-stack"></i> Payment Transaction</a>
                <a href="<?php echo $scholasticUrl; ?>"><i class="bx bx-bar-chart-alt-2"></i> Scholastic Record
                    Status</a>
                <a href="<?php echo $completionUrl; ?>"><i class="bx bxs-calendar"></i> Completion Report</a>
                <p>SETTINGS</p>
                <a href="<?php echo $settingsUrl; ?>"><i class="bx bx-cog"></i> Account Settings</a>
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
                    <div class="notification-wrapper" id="notif-trigger"
                        style="position: relative; display: inline-block; cursor: pointer;">
                        <i class="bx bx-bell"></i>
                        <?php if ($unreadCount > 0): ?>
                            <span class="notification-badge"
                                style="position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; border-radius: 50%; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold;"><?php echo $unreadCount; ?></span>
                        <?php endif; ?>
                    </div>
                    <a href="/DORCFSRF/pages/click_button/aboutus.php"><i class="bx bx-globe bx-flip-vertical"
                            style="color: #333;"></i></a>
                    <div class="user-menu-container">
                        <div class="profile-icon-wrapper" id="profile-toggle" style="cursor: pointer;">
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
                                <p><?php echo strtoupper($role); ?></p>
                            </div>
                            <div class="card-actions">
                                <a href="<?php echo $settingsUrl; ?>" class="action-btn action-btn-primary"
                                    style="text-decoration: none; color: white;">Profile</a>
                                <a href="/DORCFSRF/pages/logout.php" class="action-btn action-btn-secondary"
                                    style="text-decoration: none; color: white;">Sign Out</a>
                            </div>
                            <script src="/DORCFSRF/pages/script/dropdown.js"></script>
                        </div>
                    </div>

                    <!-- Notification Dropdown -->
                    <div class="notification-dropdown" id="notif-dropdown"
                        style="display: none; position: absolute; top: 60px; right: 80px; width: 350px; background: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 1000; max-height: 400px; overflow-y: auto;">
                        <div
                            style="padding: 15px 20px; border-bottom: 1px solid #eee; background: #f8f9fa; border-radius: 8px 8px 0 0;">
                            <h4 style="margin: 0; color: #333; font-size: 16px;">Notifications</h4>
                        </div>
                        <?php if (empty($notifications)): ?>
                            <div style="padding: 30px 15px; text-align: center; color: #999;">
                                <p>No notifications yet</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($notifications as $notif): ?>
                                <div class="notification-item" data-notif-id="<?php echo $notif['id']; ?>"
                                    style="padding: 15px; border-bottom: 1px solid #eee; cursor: pointer; background: <?php echo $notif['is_read'] ? '#fff' : '#f0f7ff'; ?>; transition: background 0.2s;"
                                    onmouseover="this.style.background='#f5f5f5'"
                                    onmouseout="this.style.background='<?php echo $notif['is_read'] ? '#fff' : '#f0f7ff'; ?>'">
                                    <div style="display: flex; justify-content: space-between; align-items: start;">
                                        <div style="flex: 1;">
                                            <p style="margin: 0 0 5px 0; font-weight: 600; color: #333;">
                                                <?php echo htmlspecialchars($notif['student_name']); ?></p>
                                            <p style="margin: 0 0 5px 0; color: #666; font-size: 13px;">
                                                <?php echo htmlspecialchars($notif['message']); ?></p>
                                            <p style="margin: 0; color: #999; font-size: 12px;">
                                                <?php echo date('M d, Y g:ia', strtotime($notif['created_at'])); ?></p>
                                        </div>
                                        <?php if (!$notif['is_read']): ?>
                                            <div
                                                style="width: 10px; height: 10px; background: #007bff; border-radius: 50%; margin-left: 10px; margin-top: 5px;">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <script>
                                    document.querySelector('[data-notif-id="<?php echo $notif['id']; ?>"]').addEventListener('click', function () {
                                        fetch('/DORCFSRF/api/mark_notification_read.php?id=<?php echo $notif['id']; ?>');
                                    });
                                </script>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </header>

            <main class="main-content">
                <div class="modal-container">
                    <div class="modal-body">
                        <?php if ($updateSuccess): ?>
                            <div
                                style="background: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 5px; padding: 12px; margin-bottom: 20px;">
                                ✅ Student information updated successfully!
                            </div>
                        <?php elseif ($updateError): ?>
                            <div
                                style="background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px; padding: 12px; margin-bottom: 20px;">
                                ❌ <?php echo htmlspecialchars($updateError); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <input type="hidden" name="student_id"
                                value="<?php echo htmlspecialchars($studentData['student_id'] ?? ''); ?>">

                            <button type="button" class="back-button"
                                onclick="window.location.href='<?php echo $formUrl; ?>'"
                                style="margin-bottom: 20px; padding: 10px 18px; border: 1px solid #ccc; border-radius: 8px; background: #fff; color: #333; cursor: pointer;">
                                <i class="fas fa-arrow-left" style="margin-right: 8px;"></i>Back to Staff Form
                            </button>
                            <div class="form-grid" style="display: grid; grid-template-columns: 300px 1fr; gap: 30px;">
                                <div style="display: flex; flex-direction: column;">
                                    <div class="thumbnail-section"
                                        style="background: #f5f5f5; border: 1px solid #ddd; border-radius: 8px; padding: <?php echo !empty($studentData['form_submission_image']) ? '0' : '20px'; ?>; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 220px; overflow: hidden;">
                                        <?php if (!empty($studentData['form_submission_image'])): ?>
                                            <img id="thumbnailImage"
                                                src="<?php echo htmlspecialchars($studentData['form_submission_image']); ?>"
                                                style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;"
                                                alt="Student Profile Image">
                                        <?php else: ?>
                                            <label style="margin-bottom: 15px; font-weight: 500; color: #333;">Student
                                                Profile</label>
                                            <i class="bx bx-user" style="font-size: 60px; color: #999;"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div style="margin-top: 30px;"></div>
                                </div>

                                <div class="main-form-section"
                                    style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; align-content: start;">
                                    <div style="grid-column: 1 / -1;">
                                        <label
                                            style="display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px;">Name:
                                            (Required)</label>
                                        <div style="display: grid; grid-template-columns: 1fr 2fr 1fr; gap: 10px;">
                                            <div>
                                                <label
                                                    style="font-size: 0.85em; color: #666; display: block; margin-bottom: 4px;">Last
                                                    Name</label>
                                                <input id="lastNameInput" type="text" name="last_name"
                                                    class="readonly-input"
                                                    value="<?php echo htmlspecialchars($studentData['student_lastname'] ?? 'HITA'); ?>"
                                                    style="width: 100%;">
                                            </div>
                                            <div>
                                                <label
                                                    style="font-size: 0.85em; color: #666; display: block; margin-bottom: 4px;">First
                                                    Name</label>
                                                <input id="firstNameInput" type="text" name="first_name"
                                                    class="readonly-input"
                                                    value="<?php echo htmlspecialchars($studentData['student_firstname'] ?? 'ISABEL MARYNIZA'); ?>"
                                                    style="width: 100%;">
                                            </div>
                                            <div>
                                                <label
                                                    style="font-size: 0.85em; color: #666; display: block; margin-bottom: 4px;">Middle
                                                    Name</label>
                                                <input id="middleNameInput" type="text" name="middle_name"
                                                    class="readonly-input"
                                                    value="<?php echo htmlspecialchars($studentData['student_middlename'] ?? 'ALVA'); ?>"
                                                    style="width: 100%;">
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <label
                                            style="display: block; margin-bottom: 4px; font-weight: 500; font-size: 14px;">Student
                                            Number:</label>
                                        <input id="studentNumber" type="text" class="readonly-input"
                                            value="<?php echo htmlspecialchars($studentData['student_id'] ?? 'CS-22-078'); ?>"
                                            readonly style="width: 100%;">
                                    </div>
                                    <div>
                                        <label
                                            style="display: block; margin-bottom: 4px; font-weight: 500; font-size: 14px;">Course
                                            Major:</label>
                                        <input id="courseMajor" type="text" name="course" class="readonly-input"
                                            value="<?php echo htmlspecialchars($studentData['student_course'] ?? 'BSCS'); ?>"
                                            style="width: 100%;">
                                    </div>

                                    <div>
                                        <label
                                            style="display: block; margin-bottom: 4px; font-weight: 500; font-size: 14px;">Year:</label>
                                        <input id="year" type="text" name="year" class="readonly-input"
                                            value="<?php echo htmlspecialchars($studentData['student_year'] ?? '4'); ?>"
                                            style="width: 100%;">
                                    </div>
                                    <div>
                                        <label
                                            style="display: block; margin-bottom: 4px; font-weight: 500; font-size: 14px;">Section:</label>
                                        <input id="section" type="text" name="section" class="readonly-input"
                                            value="<?php echo htmlspecialchars($studentData['student_section'] ?? 'B'); ?>"
                                            style="width: 100%;">
                                    </div>

                                    <div>
                                        <label
                                            style="display: block; margin-bottom: 4px; font-weight: 500; font-size: 14px;">Semester:</label>
                                        <input id="sem" type="text" name="semester" class="readonly-input"
                                            value="<?php echo htmlspecialchars($studentData['student_sem'] ?? '2nd'); ?>"
                                            style="width: 100%;">
                                    </div>
                                    <div>
                                        <label
                                            style="display: block; margin-bottom: 4px; font-weight: 500; font-size: 14px;">Contact
                                            Number:</label>
                                        <input id="contactNumber" type="text" name="contact_num" class="readonly-input"
                                            value="<?php echo htmlspecialchars($studentData['contact_num'] ?? '0992311898'); ?>"
                                            style="width: 100%;">
                                    </div>

                                    <div style="grid-column: 1 / -1;">
                                        <label
                                            style="display: block; margin-bottom: 4px; font-weight: 500; font-size: 14px;">Permanent
                                            Address:</label>
                                        <input id="permanentAddress" type="text" name="permanent_address"
                                            class="readonly-input full-width-input"
                                            value="<?php echo htmlspecialchars($studentData['permanent_address'] ?? 'BRGY. CARIDAD IBABA, ATIMONAN QUEZON'); ?>"
                                            style="width: 100%;">
                                    </div>

                                    <div style="grid-column: 1 / -1;">
                                        <label
                                            style="display: block; margin-bottom: 4px; font-weight: 500; font-size: 14px;">Reason
                                            for Clearance: (Required)</label>
                                        <input id="reason" type="text" name="reason_clearance"
                                            class="readonly-input full-width-input"
                                            value="<?php echo htmlspecialchars($studentData['reason_clearance'] ?? 'Employement'); ?>"
                                            style="width: 100%;">
                                    </div>
                                </div>
                            </div>

                            <div class="section-title">Specify the name of the following schools:</div>
                            <div class="school-row">
                                <input id="school1Name" type="text" name="elementary_school"
                                    class="readonly-input school-name"
                                    value="<?php echo htmlspecialchars($studentData['elementary_school'] ?? 'Concepcion Elementary School'); ?>">
                                <input id="school1Year" type="text" class="readonly-input school-year"
                                    value="2010-2016">
                            </div>
                            <div class="school-row">
                                <input id="school2Name" type="text" name="jhs_school" class="readonly-input school-name"
                                    value="<?php echo htmlspecialchars($studentData['jhs_school'] ?? 'Concepcion National High School'); ?>">
                                <input id="school2Year" type="text" class="readonly-input school-year"
                                    value="2016-2020">
                            </div>
                            <div class="school-row">
                                <input id="school3Name" type="text" name="shs_school" class="readonly-input school-name"
                                    value="<?php echo htmlspecialchars($studentData['shs_school'] ?? 'Leon Guinto Memorial College, Inc.'); ?>">
                                <input id="school3Year" type="text" class="readonly-input school-year"
                                    value="2020-2022">
                            </div>
                            <div class="school-row" style="margin-bottom: 25px;">
                                <input id="school4Name" type="text" name="college_community"
                                    class="readonly-input school-name"
                                    value="<?php echo htmlspecialchars($studentData['college_community'] ?? 'Quezonian Educational College, Inc.'); ?>">
                                <input id="school4Year" type="text" class="readonly-input school-year"
                                    value="2022-2026">
                            </div>

                            <div class="form-group">
                                <label>Scholastic Document/s</label>
                                <input id="docType" type="text" name="scholastic_document"
                                    class="readonly-input full-width-input"
                                    value="<?php echo htmlspecialchars($studentData['scholastic_document'] ?? 'Diploma'); ?>">
                            </div>

                            <div style="display: flex; gap: 10px; margin-top: 25px;">
                                <button type="submit"
                                    style="background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: 500;">
                                    <i class="fas fa-save" style="margin-right: 8px;"></i>Save Changes
                                </button>
                                <button type="button" onclick="window.location.href='<?php echo $formUrl; ?>'"
                                    style="background: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: 500;">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Dropdown logic for notifications
            const notifTrigger = document.getElementById('notif-trigger');
            if (notifTrigger) {
                notifTrigger.addEventListener('click', function () {
                    const dropdown = document.getElementById('notif-dropdown');
                    dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
                });
            }

            // Close notification dropdown when clicking outside
            document.addEventListener('click', function (event) {
                const trigger = document.getElementById('notif-trigger');
                const dropdown = document.getElementById('notif-dropdown');
                if (trigger && dropdown && !trigger.contains(event.target) && !dropdown.contains(event.target)) {
                    dropdown.style.display = 'none';
                }
            });
        });
    </script>
</body>

</html>