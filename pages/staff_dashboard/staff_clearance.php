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

// Determine staff department based on staff_id or full_name
$staff_department = '';
$staff_department_field = '';
$full_name = $_SESSION['full_name'] ?? $_SESSION['username'];
if (stripos($full_name, 'Janie') !== false || $staff_id === 'STF001') {
    $staff_department = 'Records Officer';
    $staff_department_field = 'records_officer_status';
} elseif (stripos($full_name, 'Reyniel') !== false || $staff_id === 'STF002') {
    $staff_department = 'Computer Lab';
    $staff_department_field = 'computer_lab_status';
} elseif (stripos($full_name, 'Maritess') !== false || $staff_id === 'STF003') {
    $staff_department = 'Librarian';
    $staff_department_field = 'librarian_status';
} elseif (stripos($full_name, 'Chin-Chin') !== false || stripos($full_name, 'Devilles') !== false || $staff_id === 'STF004' || $staff_id === 'STF0018') {
    $staff_department = 'Finance Officer';
    $staff_department_field = 'finance_officer_status';
} elseif (stripos($full_name, 'Kristine') !== false || $staff_id === 'STF005') {
    $staff_department = 'CBRC';
    $staff_department_field = 'cbrc_status';
} elseif (stripos($full_name, 'Azerelle') !== false || stripos($full_name, 'Russel') !== false || $staff_id === 'STF006' || $staff_id === 'STF007' || $staff_id === 'CS-26-0001') {
    $staff_department = 'Admission Officer';
    $staff_department_field = 'admission_officer_status';
} elseif (stripos($full_name, 'Carmencita') !== false || $staff_id === 'STF008') {
    $staff_department = 'Registrar';
    $staff_department_field = 'registrar_status';
} elseif (stripos($full_name, 'Sharon') !== false || $staff_id === 'STF009') {
    $staff_department = 'School Cashier';
    $staff_department_field = 'school_cashier_status';
} elseif (stripos($full_name, 'Jeck') !== false || $staff_id === 'STF010') {
    $staff_department = 'Property Custodian';
    $staff_department_field = 'property_custodian_status';
} elseif (stripos($full_name, 'Mark') !== false || $staff_id === 'STF011') {
    $staff_department = 'Scholarship Coordinator';
    $staff_department_field = 'scholarship_coordinator_status';
} elseif (stripos($full_name, 'Shirley') !== false || $staff_id === 'STF012') {
    $staff_department = 'Guidance Counselor';
    $staff_department_field = 'guidance_counselor_status';
}

// Fallback to a valid department field if not recognized
if (empty($staff_department_field)) {
    $staff_department = 'Records Officer';
    $staff_department_field = 'records_officer_status';
    error_log("WARNING: Unrecognized staff member $staff_id/$full_name, defaulting department field to $staff_department_field");
}

// Get unread notification count
$unreadCount = getUnreadNotificationCount($conn, $staff_id);
$notifications = getNotifications($conn, $staff_id, 5);

// Fetch students who have submitted forms with clearance status - filter by staff's department
$clearanceQuery = "SELECT 
    s.student_id, 
    CONCAT(s.student_firstname, ' ', s.student_lastname) as name, 
    CONCAT(LOWER(REPLACE(CONCAT(s.student_firstname, s.student_lastname), ' ', '')), '@gmail.com') as email,
    COALESCE(sc.unit_stats, 'pending') as department_status,
    CASE 
        WHEN LOWER(COALESCE(sc.unit_stats, 'pending')) IN ('cleared', 'approved') THEN 'cleared'
        WHEN LOWER(COALESCE(sc.unit_stats, 'pending')) = 'in progress' THEN 'in-progress'
        ELSE 'pending'
    END as status
FROM student_information_database s
LEFT JOIN student_clearance_database sc ON s.student_id = sc.student_id
ORDER BY s.updated_at DESC";
$clearanceResult = $conn->query($clearanceQuery);
$clearanceRows = [];
if ($clearanceResult && $clearanceResult->num_rows > 0) {
    while ($row = $clearanceResult->fetch_assoc()) {
        error_log("DEBUG: student_id=" . $row['student_id'] . " department_status='" . $row['department_status'] . "' mapped_status=" . $row['status']);
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

        $clearanceRows[] = [
            'name' => htmlspecialchars($row['name']),
            'student_id' => htmlspecialchars($row['student_id']),
            'email' => htmlspecialchars($row['email'] ?? ''),
            'status' => $row['status'],
            'id' => $row['student_id'],
            'profile_image' => $student_profile_image
        ];
    }
}
// No duplication - use original list
$fullList = $clearanceRows;
$clearanceJSON = json_encode($fullList);

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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clearance Status</title>
    <link rel="stylesheet" href="/DORCFSRF/css/dashboard.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
</head>

<body>
    <div class="container">
        <aside class="sidebar">
            <img src="/DORCFSRF/assets/QECI LOGO.jpg" alt="qecilogo" />
            <nav>
                <a href="staff_dasboard.php"><i class="bx bxs-dashboard"></i> Dashboard</a>
                <a href="staff_form.php"><i class="bx bx-notepad"></i> Form</a>
                <a href="staff_clearance.php" class="active"><i class="bx bxs-graduation"></i>
                    Clearance Status</a>
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
                                <p>STAFF</p>
                            </div>
                            <div class="card-actions">
                                <a href="staff_account_settings.php" class="action-btn action-btn-primary">Profile</a>
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

            <main class="main-content">
                <div class="dashboard-container" style="width: 100%; max-width: 1200px; margin: 0 auto; padding: 20px;">
                    <div class="breadcrumb" style="margin-bottom: 10px; font-weight: 700; color: white;">
                        Home > Clearance Status
                    </div>

                    <div class="card1">
                        <div class="card-fixed-top">
                            <div class="tabs" style="gap: 60px;">
                                <div class="tab active" data-status="all">Overall Status</div>
                                <div class="tab" data-status="cleared">Cleared</div>
                                <div class="tab" data-status="pending">Pending</div>
                                <div class="tab" data-status="in-progress">In Progress</div>


                            </div>

                            <div class="table-header">
                                <div>Students</div>
                                <div>Email ID</div>
                                <div>Unit Concerned</div>
                                <div>Status</div>
                                <div>Actions</div>
                            </div>
                        </div>

                        <div class="table-body" id="table-data"></div>

                        <script>
                            let fullList = <?php echo $clearanceJSON; ?>;
                            const tableContainer = document.getElementById('table-data');
                            const tabs = document.querySelectorAll('.tab');
                            let autoRefreshTimer = null;

                            // ✅ Fetch fresh student list from API
                            async function loadFreshStudentList() {
                                try {
                                    console.log('📥 Fetching fresh student list from API...');
                                    const response = await fetch('/DORCFSRF/api/get_clearance_list.php');
                                    const data = await response.json();

                                    if (data.success && data.students) {
                                        fullList = data.students;
                                        console.log('✓ Loaded ' + data.count + ' students from API at ' + data.timestamp);
                                        return true;
                                    } else {
                                        console.warn('API returned error:', data.error);
                                        return false;
                                    }
                                } catch (error) {
                                    console.error('Failed to load fresh list:', error);
                                    return false;
                                }
                            }

                            // Function to render the table based on filter
                            function renderTable(filterStatus = 'all') {
                                tableContainer.innerHTML = ''; // Clear current table

                                const filteredList = filterStatus === 'all'
                                    ? fullList
                                    : fullList.filter(item => item.status === filterStatus);

                                if (filteredList.length === 0) {
                                    tableContainer.innerHTML = '<div style="padding: 20px; text-align: center; color: #999;">No students found</div>';
                                    return;
                                }

                                filteredList.forEach(item => {
                                    const targetLink = `/DORCFSRF/pages/staff_dashboard/staff_clearance_requirments_dtls.php?student_id=${item.student_id}&staff_id=${'<?php echo $staff_id; ?>' || ''}`;
                                    const cacheBreak = new Date().getTime();
                                    const profileImageHTML = item.profile_image
                                        ? `<img src="${item.profile_image}?t=${cacheBreak}" alt="${item.name}" style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%;" />`
                                        : '<div class="avatar"></div>';

                                    const rowHTML = `
                                        <div class="table-row">
                                            <div class="student-cell">${profileImageHTML}${item.name}</div>
                                            <div style="color:#7f8c8d">${item.student_id} ${item.email}</div>
                                            <div><?php echo htmlspecialchars($staff_department); ?></div>
                                            <div class="status ${item.status}">
                                                <span class="dot"></span> 
                                                ${{ 'cleared': 'CLEARED', 'pending': 'PENDING', 'in-progress': 'IN PROGRESS' }[item.status] || item.status.toUpperCase()}
                                            </div>
                                            <div>
                                                <a href="${targetLink}" class="btn-link">
                                                    View & Modify
                                                </a>
                                            </div>
                                        </div>
                                    `;
                                    tableContainer.innerHTML += rowHTML;
                                });
                            }

                            // Add click events to tabs
                            tabs.forEach(tab => {
                                tab.addEventListener('click', () => {
                                    tabs.forEach(t => t.classList.remove('active'));
                                    tab.classList.add('active');
                                    const status = tab.getAttribute('data-status');
                                    renderTable(status);
                                });
                            });


                            // ✅ Initialize page - load fresh data and render
                            async function initializePage() {
                                console.log('🚀 Initializing clearance list...');

                                // Check if returning from save
                                const params = new URLSearchParams(window.location.search);
                                const isReturningFromSave = params.has('t');

                                if (isReturningFromSave) {
                                    console.log('📢 Detected return from save - loading fresh data...');
                                }

                                // Load fresh data from API
                                const success = await loadFreshStudentList();

                                if (success) {
                                    console.log('✅ Page initialized with fresh data');
                                } else {
                                    console.warn('⚠️ Could not load fresh data, using cached data');
                                }

                                // Render the table
                                renderTable('all');

                                // Clean up URL
                                window.history.replaceState({}, document.title, window.location.pathname);
                            }

                            // ✅ Start periodic auto-refresh (every 15 seconds)
                            function startAutoRefresh() {
                                if (autoRefreshTimer) {
                                    clearInterval(autoRefreshTimer);
                                }

                                autoRefreshTimer = setInterval(async function () {
                                    console.log('🔄 Auto-refresh: Checking for updates...');
                                    const success = await loadFreshStudentList();

                                    if (success) {
                                        // Re-render with current filter
                                        const currentTab = document.querySelector('.tab.active');
                                        const currentStatus = currentTab ? currentTab.getAttribute('data-status') : 'all';
                                        renderTable(currentStatus);
                                        console.log('✓ Auto-refresh complete');
                                    }
                                }, 15000); // Refresh every 15 seconds
                            }

                            // ✅ Initialize when DOM is ready
                            if (document.readyState === 'loading') {
                                document.addEventListener('DOMContentLoaded', function () {
                                    initializePage();
                                    startAutoRefresh();
                                });
                            } else {
                                initializePage();
                                startAutoRefresh();
                            }
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
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="/DORCFSRF/pages/script/prevent-back-button.js"></script>
</body>

</html>