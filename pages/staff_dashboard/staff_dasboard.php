<?php
ini_set('session.gc_maxlifetime', 31536000);
session_set_cookie_params(31536000);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Staff') {
    header('Location: /DORCFSRF/pages/login_form/index.php');
    exit;
}
include __DIR__ . '/../../db_connect.php';
include __DIR__ . '/../../api/notification_system.php';
$full_name = $_SESSION['full_name'] ?? $_SESSION['username'];
$staff_id = $_SESSION['user_id'];

// Get unread notification count
$unreadCount = getUnreadNotificationCount($conn, $staff_id);
$notifications = getNotifications($conn, $staff_id, 5);

// Fetch students who have submitted forms and their clearance progress
$dashboardStudentsQuery = "
    SELECT 
        sid.student_id, 
        CONCAT(sid.student_firstname, ' ', sid.student_lastname) as name, 
        MAX(sid.updated_at) as updated_at,
        scd.*
    FROM student_information_database sid
    LEFT JOIN student_clearance_database scd ON sid.student_id = scd.student_id
    GROUP BY sid.student_id
    ORDER BY updated_at DESC
    LIMIT 50
";
$dashboardStudentsResult = $conn->query($dashboardStudentsQuery);
$submittedStudents = [];
$progressStudents = [];

if ($dashboardStudentsResult && $dashboardStudentsResult->num_rows > 0) {
    while ($row = $dashboardStudentsResult->fetch_assoc()) {
        $student_id = $row['student_id'];
        
        // Profile image logic
        $profile_image = '/DORCFSRF/assets/student_profiles/' . $student_id . '_profile.jpg';
        $profile_image_alt = '/DORCFSRF/assets/student_profiles/' . $student_id . '_profile.png';
        $profile_image_alt2 = '/DORCFSRF/assets/student_profiles/' . $student_id . '_profile.gif';

        $student_profile_image = null;
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image)) {
            $student_profile_image = $profile_image;
        } elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image_alt)) {
            $student_profile_image = $profile_image_alt;
        } elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image_alt2)) {
            $student_profile_image = $profile_image_alt2;
        }

        $studentData = [
            'name' => htmlspecialchars($row['name']),
            'date' => date('F j, Y | g:ia', strtotime($row['updated_at'])),
            'id' => $row['student_id'],
            'profile_image' => $student_profile_image
        ];
        
        $submittedStudents[] = $studentData;

        // Clearance calculation
        $approved_count = 0;
        $total_departments = 13;
        
        $clearance_fields = [
            'records_officer_status', 'computer_lab_status', 'librarian_status', 
            'finance_officer_status', 'thesis_status', 'admission_officer_status', 
            'school_cashier_status', 'property_custodian_status', 'student_services_status', 
            'scholarship_coordinator_status', 'guidance_counselor_status', 'registrar_status', 'cbrc_status'
        ];
        
        foreach ($clearance_fields as $field) {
            if (isset($row[$field]) && (strcasecmp($row[$field], 'Approved') === 0 || strcasecmp($row[$field], 'Cleared') === 0)) {
                $approved_count++;
            }
        }
        
        $actual_progress = round(($approved_count / $total_departments) * 100, 0);
        $studentData['progress'] = $actual_progress;
        
        $progressStudents[] = $studentData;
    }
}

// Calculate total students, cleared, in-progress, and pending based on unit_stats to match the Clearance Status page
$statsQuery = "
SELECT 
    COUNT(sid.student_id) as total_students,
    SUM(CASE WHEN LOWER(COALESCE(scd.unit_stats, 'pending')) IN ('cleared', 'approved') THEN 1 ELSE 0 END) as cleared_students,
    SUM(CASE WHEN LOWER(COALESCE(scd.unit_stats, 'pending')) = 'in progress' THEN 1 ELSE 0 END) as in_progress_students,
    SUM(CASE WHEN LOWER(COALESCE(scd.unit_stats, 'pending')) NOT IN ('cleared', 'approved', 'in progress') THEN 1 ELSE 0 END) as pending_students
FROM student_information_database sid
LEFT JOIN student_clearance_database scd ON sid.student_id = scd.student_id
";

$statsResult = $conn->query($statsQuery);
if ($statsResult && $row = $statsResult->fetch_assoc()) {
    $totalStudents = $row['total_students'];
    $clearedStudents = $row['cleared_students'] ?? 0;
    $inProgressStudents = $row['in_progress_students'] ?? 0;
    $pendingStudents = $row['pending_students'] ?? 0;
} else {
    $totalStudents = 0;
    $clearedStudents = 0;
    $inProgressStudents = 0;
    $pendingStudents = 0;
}

// Get stored profile image
$profile_image = '/DORCFSRF/assets/staff_profiles/' . $staff_id . '_profile.jpg';
$profile_image_alt = '/DORCFSRF/assets/staff_profiles/' . $staff_id . '_profile.png';
$profile_image_alt2 = '/DORCFSRF/assets/staff_profiles/' . $staff_id . '_profile.gif';

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
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dashboard</title>
    <link rel="stylesheet" href="/DORCFSRF/css/dashboard.css" />
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>
    <div class="container">
        <aside class="sidebar">
            <img src="/DORCFSRF/assets/QECI LOGO.jpg" alt="qecilogo" />
            <nav>
                <a href="staff_dasboard.php" class="active"><i class="bx bxs-dashboard"> </i>
                    Dashboard</a>
                <a href="staff_form.php"><i class="bx bx-notepad"></i> Form</a>
                <a href="staff_clearance.php"><i class="bx bxs-graduation"></i> Clearance
                    Status</a>
                <a href="staff_payment_transaction.php"><i class="bx bx-coin-stack"></i> Payment
                    Transaction</a>
                <a href="staff_scholastic_record.php"><i class="bx bx-bar-chart-alt-2"></i>
                    Scholastic Record Status</a>
                <a href="staff_completion_report.php"><i class="bx bxs-calendar"></i> Completion
                    Report</a>
                <p>SETTINGS</p>
                <a href="staff_account_settings.php"><i class="bx bx-cog"></i> Account
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
                    <div class="notification-wrapper" id="notif-trigger"
                        style="position: relative; display: inline-block; cursor: pointer;">
                        <i class="bx bx-bell"></i>
                        <?php if ($unreadCount > 0): ?>
                            <span class="notification-badge"
                                style="position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; border-radius: 50%; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold;"><?php echo $unreadCount; ?></span>
                        <?php endif; ?>
                    </div>
                    <a href="/DORCFSRF/pages/click_button/aboutus.php"><i class="bx bx-globe bx-flip-vertical"></i></a>
                    <div class="user-menu-container">
                        <div class="profile-icon-wrapper" id="profile-toggle">
                            <?php if ($profile_image_url): ?>
                                <img src="<?php echo htmlspecialchars($profile_image_url); ?>?t=<?php echo time(); ?>"
                                    alt="Profile"
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
                                <p>STAFF</p>
                            </div>
                            <div class="card-actions">
                                <a href="staff_account_settings.php" class="action-btn action-btn-primary">Profile</a>
                                <a href="/DORCFSRF/pages/logout.php" class="action-btn action-btn-secondary">Sign
                                    Out</a>
                            </div>
                            <script src="/DORCFSRF/pages/script/dropdown.js"></script>
                        </div>
                        <!-- Notifications Dropdown -->
                        <div class="notification-dropdown" id="notification-dropdown"
                            style="position: absolute; top: 60px; right: 0; background: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); min-width: 350px; max-width: 400px; max-height: 500px; overflow-y: auto; z-index: 1000; display: none;">
                            <div style="padding: 15px; border-bottom: 1px solid #eee; background: #f8f9fa;">
                                <h4 style="margin: 0; font-size: 16px; color: #333;">Notifications</h4>
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
                                                    <?php echo htmlspecialchars($notif['student_name']); ?>
                                                </p>
                                                <p style="margin: 0 0 5px 0; color: #666; font-size: 13px;">
                                                    <?php echo htmlspecialchars($notif['message']); ?>
                                                </p>
                                                <p style="margin: 0; color: #999; font-size: 12px;">
                                                    <?php echo date('M d, Y g:ia', strtotime($notif['created_at'])); ?>
                                                </p>
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
                <div class="dashboard-grid">
                    <div class="background-overlay"></div>

                    <section class="metrics-row">
                        <div class="metric-card requested">
                            <div class="metric-info">
                                <p class="metric-title">Total Students Requested</p>
                                <p class="metric-value"><?php echo $totalStudents; ?></p>
                            </div>
                            <div class="metric-icon"><i class="fas fa-user-plus"></i></div>
                        </div>
                        <div class="metric-card cleared">
                            <div class="metric-info">
                                <p class="metric-title">Cleared Students</p>
                                <p class="metric-value"><?php echo $clearedStudents; ?></p>
                            </div>
                            <div class="metric-icon"><i class="fas fa-check-double"></i></div>
                        </div>
                        <div class="metric-card pending">
                            <div class="metric-info">
                                <p class="metric-title">Pending Clearances</p>
                                <p class="metric-value"><?php echo $pendingStudents; ?></p>
                            </div>
                            <div class="metric-icon"><i class="fas fa-book-open"></i></div>
                        </div>
                        <div class="metric-card inprogress">
                            <div class="metric-info">
                                <p class="metric-title">In Progress</p>
                                <p class="metric-value"><?php echo $inProgressStudents; ?></p>
                            </div>
                            <div class="metric-icon"><i class="fas fa-graduation-cap"></i></div>
                        </div>
                    </section>

                    <div class="content-main">

                        <section class="card students-list-card">
                            <header class="card-header">
                                <h3>List of Students</h3>
                                <a href="staff_clearance.php">See All</a>
                            </header>
                            <div class="card-content" style="max-height: 400px; overflow-y: auto;">
                                <?php if (!empty($submittedStudents)): ?>
                                    <?php foreach ($submittedStudents as $student): ?>
                                        <div class="student-item">
                                            <div class="student-details">
                                                <div class="avatar-placeholder">
                                                    <?php if ($student['profile_image']): ?>
                                                        <img src="<?php echo htmlspecialchars($student['profile_image']); ?>?t=<?php echo time(); ?>"
                                                            alt="<?php echo $student['name']; ?>"
                                                            style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;" />
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-details">
                                                    <p class="name"><?php echo $student['name']; ?></p>
                                                    <p class="date"><?php echo $student['date']; ?></p>
                                                </div>
                                            </div>
                                            <div class="status cleared-status">SUBMITTED</div>
                                            <button class="view-details"
                                                onclick="window.location.href='/DORCFSRF/pages/staff_dashboard/staff_clearance_requirments_dtls.php?student_id=<?php echo urlencode($student['id']); ?>&source=dashboard'">View
                                                Clearance</button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p style="text-align: center; padding: 20px; color: #999;">No students have submitted
                                        forms yet.</p>
                                <?php endif; ?>
                            </div>
                        </section>

                        <section class="card progress-card">
                            <header class="card-header">
                                <h3>Students' Clearance Record Progress</h3>
                            </header>
                            <div class="card-content">
                                <?php if (!empty($progressStudents)): ?>
                                    <?php foreach ($progressStudents as $student): ?>
                                        <div class="progress-item">
                                            <div class="student-details">
                                                <div class="avatar-placeholder">
                                                    <?php if ($student['profile_image']): ?>
                                                        <img src="<?php echo htmlspecialchars($student['profile_image']); ?>?t=<?php echo time(); ?>"
                                                            alt="<?php echo $student['name']; ?>"
                                                            style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;" />
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-details">
                                                    <p class="name"><?php echo $student['name']; ?></p>
                                                    <p class="date"><?php echo $student['date']; ?></p>
                                                </div>
                                            </div>
                                            <div class="progress-wrapper"
                                                style="display: flex; align-items: center; gap: 10px;">
                                                <div class="progress-bar-container" style="margin: 0;">
                                                    <div class="progress-bar <?php echo $student['progress'] >= 100 ? 'complete' : 'incomplete'; ?>"
                                                        style="width: <?php echo $student['progress']; ?>%;"></div>
                                                </div>
                                                <span
                                                    style="font-size: 0.75rem; font-weight: bold; color: var(--color-primary); min-width: 80px;"><?php echo $student['progress']; ?>%
                                                    Complete</span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p style="text-align: center; padding: 20px; color: #999;">No progress data available.
                                    </p>
                                <?php endif; ?>
                            </div>
                        </section>

                    </div>


                    <section class="card summary-report-card">
                        <header class="card-header">
                            <h3>Summary Report</h3>
                            <a href="#">See All</a>
                        </header>
                        <div class="card-content">
                            <?php if (!empty($submittedStudents)): ?>
                                <?php foreach ($submittedStudents as $student): ?>
                                    <div class="report-item">
                                        <p class="report-text">Student <?php echo htmlspecialchars($student['name']); ?>
                                            submitted Clearance Document</p>
                                        <p class="report-date"><?php echo htmlspecialchars($student['date']); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="text-align: center; padding: 20px; color: #999;">No submissions yet.</p>
                            <?php endif; ?>
                        </div>
                    </section>

                </div>
            </main>
        </div>

    </div>

    <script src="/DORCFSRF/pages/script/prevent-back-button.js"></script>
</body>

</html>

<script>
    // Notification dropdown toggle
    document.getElementById('notif-trigger').addEventListener('click', function (e) {
        e.stopPropagation();
        const dropdown = document.getElementById('notification-dropdown');
        dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function (e) {
        const dropdown = document.getElementById('notification-dropdown');
        const trigger = document.getElementById('notif-trigger');
        if (!dropdown.contains(e.target) && !trigger.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });

    // Auto-refresh notifications every 30 seconds
    function refreshNotifications() {
        fetch('/DORCFSRF/api/get_notifications.php')
            .then(response => response.json())
            .then(data => {
                const badge = document.querySelector('.notification-badge');
                const dropdown = document.getElementById('notification-dropdown');

                // Update badge count
                if (data.unreadCount > 0) {
                    if (!badge) {
                        const newBadge = document.createElement('span');
                        newBadge.className = 'notification-badge';
                        newBadge.textContent = data.unreadCount;
                        newBadge.style.cssText = 'position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; border-radius: 50%; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold;';
                        document.getElementById('notif-trigger').appendChild(newBadge);
                    } else {
                        badge.textContent = data.unreadCount;
                    }
                } else if (badge) {
                    badge.remove();
                }
            })
            .catch(err => console.error('Error refreshing notifications:', err));
    }

    // Refresh notifications on page load and every 30 seconds
    setInterval(refreshNotifications, 30000);
</script>