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

// Get unread notification count
$unreadCount = getUnreadNotificationCount($conn, $admin_id);
$notifications = getNotifications($conn, $admin_id, 5);

// Fetch students for scholastic records with real status and progress
$scholasticQuery = "
    SELECT 
        s.student_id,
        s.student_firstname,
        s.student_lastname,
        s.student_course,
        s.student_year,
        u.email,
        c.unit_stats,
        (SELECT COUNT(*) FROM clearance_approvals_log cal WHERE cal.student_id = s.student_id AND LOWER(cal.department_field) = 'records_officer_status' AND (cal.status = 'Approved' OR cal.status = 'Cleared')) AS records_approved,
        (SELECT COUNT(*) FROM clearance_approvals_log cal WHERE cal.student_id = s.student_id AND LOWER(cal.department_field) = 'computer_lab_status' AND (cal.status = 'Approved' OR cal.status = 'Cleared')) AS lab_approved,
        (SELECT COUNT(*) FROM clearance_approvals_log cal WHERE cal.student_id = s.student_id AND LOWER(cal.department_field) = 'librarian_status' AND (cal.status = 'Approved' OR cal.status = 'Cleared')) AS library_approved,
        (SELECT COUNT(*) FROM clearance_approvals_log cal WHERE cal.student_id = s.student_id AND LOWER(cal.department_field) = 'finance_officer_status' AND (cal.status = 'Approved' OR cal.status = 'Cleared')) AS finance_approved,
        (SELECT COUNT(*) FROM clearance_approvals_log cal WHERE cal.student_id = s.student_id AND LOWER(cal.department_field) = 'cbrc_status' AND (cal.status = 'Approved' OR cal.status = 'Cleared')) AS cbrc_approved,
        (SELECT COUNT(DISTINCT department_field) FROM clearance_approvals_log cal WHERE cal.student_id = s.student_id AND (cal.status = 'Approved' OR cal.status = 'Cleared')) AS total_approved_deps,
        IFNULL(p.completed_payments, 0) AS completed_payments,
        IFNULL(d.uploaded_docs, 0) AS uploaded_docs
    FROM student_information_database s
    LEFT JOIN user_database u ON s.student_id = u.user_id
    LEFT JOIN (
        SELECT sc1.*
        FROM student_clearance_database sc1
        INNER JOIN (
            SELECT student_id, MAX(clearance_id) AS max_clearance_id
            FROM student_clearance_database
            GROUP BY student_id
        ) latest ON sc1.student_id = latest.student_id AND sc1.clearance_id = latest.max_clearance_id
    ) c ON s.student_id = c.student_id
    LEFT JOIN (
        SELECT student_id, COUNT(*) AS completed_payments
        FROM payment_record_database
        GROUP BY student_id
    ) p ON s.student_id = p.student_id
    LEFT JOIN (
        SELECT student_id, COUNT(*) AS uploaded_docs
        FROM clearance_document_uploads
        GROUP BY student_id
    ) d ON s.student_id = d.student_id
    ORDER BY s.updated_at DESC
    LIMIT 20
";
$scholasticResult = $conn->query($scholasticQuery);
$scholasticData = [];
$counter = 1;
if ($scholasticResult && $scholasticResult->num_rows > 0) {
    while ($row = $scholasticResult->fetch_assoc()) {
        // Get student profile image
        $student_id = $row['student_id'];
        $profile_image = '/DORCFSRF/assets/student_profiles/' . $student_id . '_profile.jpg';
        $profile_image_alt = '/DORCFSRF/assets/student_profiles/' . $student_id . '_profile.png';
        $profile_image_alt2 = '/DORCFSRF/assets/student_profiles/' . $student_id . '_profile.gif';

        if (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image)) {
            $student_profile_image = $profile_image;
        } elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image_alt)) {
            $student_profile_image = $profile_image_alt;
        } elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image_alt2)) {
            $student_profile_image = $profile_image_alt2;
        } else {
            $student_profile_image = null;
        }

        $submissionDone = isset($row['unit_stats']) && trim($row['unit_stats']) !== '' && strtolower(trim($row['unit_stats'])) !== 'not submitted' && strtolower(trim($row['unit_stats'])) !== 'pending';
        $docsUploaded = intval($row['uploaded_docs']) > 0;
        $recordsApproved = intval($row['records_approved']) > 0;
        $labApproved = intval($row['lab_approved']) > 0;
        $librarianApproved = intval($row['library_approved']) > 0;
        $financeApproved = intval($row['finance_approved']) > 0;
        $cbrcApproved = intval($row['cbrc_approved']) > 0;
        $paymentCompleted = intval($row['completed_payments']) > 0;

        $taskCount = 7;
        $completedTasks = 0;
        $completedTasks += $submissionDone ? 1 : 0;
        $completedTasks += $recordsApproved ? 1 : 0;
        $completedTasks += $labApproved ? 1 : 0;
        $completedTasks += $librarianApproved ? 1 : 0;
        $completedTasks += $financeApproved ? 1 : 0;
        $completedTasks += $cbrcApproved ? 1 : 0;
        $completedTasks += $paymentCompleted ? 1 : 0;

        $progressPercent = $taskCount > 0 ? round(($completedTasks / $taskCount) * 100) : 0;
        if (intval($row['total_approved_deps']) >= 13) {
            $progressPercent = 100;
        }

        if ($progressPercent === 100) {
            $status = 'ready';
            $label = 'Ready for Release';
        } elseif ($progressPercent > 0) {
            $status = 'inprogress';
            $label = 'In Progress';
        } else {
            $status = 'pending';
            $label = 'Pending';
        }

        $scholasticData[] = [
            'or' => 'SR-' . str_pad($student_id, 5, '0', STR_PAD_LEFT) . '-' . date('Y'),
            'name' => htmlspecialchars(($row['student_firstname'] ?? '') . ' ' . ($row['student_lastname'] ?? '')),
            'email' => htmlspecialchars($row['email'] ?? 'N/A'),
            'doc' => 'Scholastic Record',
            'status' => $status,
            'label' => $label,
            'id' => $row['student_id'],
            'profile_image' => $student_profile_image,
            'progress' => $progressPercent,
            'submitted' => $submissionDone,
            'uploaded_docs' => intval($row['uploaded_docs']),
            'completed_payments' => intval($row['completed_payments'])
        ];
        $counter++;
    }
}
$scholasticJSON = json_encode($scholasticData);

// Get stored profile image
$profile_image = '/DORCFSRF/assets/admin_profiles/' . $admin_id . '_profile.jpg';
$profile_image_alt = '/DORCFSRF/assets/admin_profiles/' . $admin_id . '_profile.png';
$profile_image_alt2 = '/DORCFSRF/assets/admin_profiles/' . $admin_id . '_profile.gif';

// Check which image exists
if (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image)) {
    $profile_image_url = $profile_image;
} elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image_alt)) {
    $profile_image_url = $profile_image_alt;
} elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image_alt2)) {
    $profile_image_url = $profile_image_alt2;
} else {
    $profile_image_url = null;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scholastic Record Status</title>
    <link rel="stylesheet" href="/DORCFSRF/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
</head>

<body>
    <div class="container">
        <aside class="sidebar">
            <img src="/DORCFSRF/assets/QECI LOGO.jpg" alt="qecilogo" />
            <nav>
                <a href="dashboard.php"><i class="bx bxs-dashboard"> </i> Dashboard</a>
                <a href="form.php"><i class="bx bx-notepad"></i> Form</a>
                <a href="clearance_status.php"><i class="bx bxs-graduation"></i> Clearance
                    Status</a>
                <a href="payment_transaction.php"><i class="bx bx-coin-stack"></i> Payment
                    Transaction</a>
                <a href="scholastic_record_status.php" class="active"><i class="bx bx-bar-chart-alt-2"></i> Scholastic
                    Record Status</a>
                <a href="completion_report.php"><i class="bx bxs-calendar"></i> Completion
                    Report</a>
                <p>SETTINGS</p>
                <a href="/DORCFSRF/pages/admin_dashboard/audit_trail.php"><i class="bx bx-history"></i> Audit Trail</a>
        <a href="/DORCFSRF/pages/admin_dashboard/account_management.php"><i class='bx bx-briefcase-alt'></i>
                    Account Management</a>
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
                                <p>ADMIN</p>
                            </div>
                            <div class="card-actions">
                                <a href="account_settings.php" class="action-btn action-btn-primary">Profile</a>
                                <a href="/DORCFSRF/pages/logout.php" class="action-btn action-btn-secondary">Sign
                                    Out</a>
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
            </header>

            <main class="main-content" style="width: 100%; max-width: 1200px; margin: 0 auto; padding: 20px;">
                <div class="breadcrumb" style="margin-bottom: 10px; font-weight: 700; color: white;">
                    Home > Scholastic Record</div>
                <div class="main-container">
                    <div class="content-card">
                        <div class="controls-header">
                            <ul class="tabs-nav">
                                <li class="tab-item active" data-status="all">All Request</li>
                                <li class="tab-item" data-status="ready">Ready for Release</li>
                                <li class="tab-item" data-status="inprogress">In Progress</li>
                                <li class="tab-item" data-status="pending">Pending</li>
                            </ul>


                        </div>

                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>OR No.</th>
                                        <th>Students</th>
                                        <th>Email ID</th>
                                        <th>Type of Document/s</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="scholastic-table-body">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // 1. Data Array - Fetched from Database (via PHP)
        const scholasticData = <?php echo $scholasticJSON; ?>;

        const tableBody = document.getElementById('scholastic-table-body');
        const tabs = document.querySelectorAll('.tab-item');

        // 2. Render Function
        function renderTable(filter = 'all') {
            tableBody.innerHTML = '';

            const filteredData = filter === 'all'
                ? scholasticData
                : scholasticData.filter(item => item.status === filter);

            if (filteredData.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #999; padding: 30px;">No records found</td></tr>';
                return;
            }

            filteredData.forEach(item => {
                const cacheBreak = new Date().getTime();
                const profileImageHTML = item.profile_image
                    ? `<img src="${item.profile_image}?t=${cacheBreak}" alt="${item.name}" style="width: 35px; height: 35px; object-fit: cover; border-radius: 50%; margin-right: 10px;" />`
                    : '';

                const row = `
                    <tr>
                        <td><strong>${item.or}</strong></td>
                        <td>
                            <div class="student-profile" style="display: flex; align-items: center;">
                                ${profileImageHTML}
                                <span>${item.name}</span>
                            </div>
                        </td>
                        <td><a href="mailto:${item.email}" class="email-link">${item.email}</a></td>
                        <td>${item.doc}</td>
                        <td><span class="status-badge status-${item.status}">${item.label}</span></td>
                        <td>
                            <button class="view-btn" onclick="viewRecord('${item.id}')">View</button>
                        </td>
                    </tr>
                `;
                tableBody.innerHTML += row;
            });
        }

        // 3. Tab Click Event
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');

                const status = tab.getAttribute('data-status');
                renderTable(status);
            });
        });

        // 4. View Redirect Function
        function viewRecord(studentId) {
            window.location.href = `/DORCFSRF/pages/click_button/admin_scholastic_record.php?student_id=${studentId}`;
        }

        // Initial Load
        renderTable();
    </script>

    <!-- Notification Dropdown Toggle -->
    <script>
        document.getElementById('notif-trigger').addEventListener('click', function () {
            const dropdown = document.getElementById('notif-dropdown');
            dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        });

        // Close notification dropdown when clicking outside
        document.addEventListener('click', function (event) {
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