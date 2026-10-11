<?php
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

// Get profile image like in dashboard.php
$profile_image = '/DORCFSRF/assets/admin_profiles/' . $admin_id . '_profile.jpg';
$profile_image_alt = '/DORCFSRF/assets/admin_profiles/' . $admin_id . '_profile.png';
$profile_image_alt2 = '/DORCFSRF/assets/admin_profiles/' . $admin_id . '_profile.gif';

if (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image)) {
    $profile_image_url = $profile_image . '?t=' . filemtime($_SERVER['DOCUMENT_ROOT'] . $profile_image);
} elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image_alt)) {
    $profile_image_url = $profile_image_alt . '?t=' . filemtime($_SERVER['DOCUMENT_ROOT'] . $profile_image_alt);
} elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image_alt2)) {
    $profile_image_url = $profile_image_alt2 . '?t=' . filemtime($_SERVER['DOCUMENT_ROOT'] . $profile_image_alt2);
} else {
    $profile_image_url = null;
}

// Fetch total metrics
$total_logs = 0;
$failed_logs = 0;
$active_users = 0; // Distinct users in last 24h or total users

// Get basic metrics from audit_logs
$queryMetrics = "SELECT 
    COUNT(*) as total, 
    SUM(CASE WHEN status = 'Failed' THEN 1 ELSE 0 END) as failed
FROM audit_logs";
$resultMetrics = $conn->query($queryMetrics);
if ($resultMetrics && $row = $resultMetrics->fetch_assoc()) {
    $total_logs = $row['total'] ?? 0;
    $failed_logs = $row['failed'] ?? 0;
}

// Get Active Users from user_database (those with an active session)
$activeUsersQuery = "SELECT COUNT(*) as active_count FROM user_database WHERE session_token IS NOT NULL OR last_active > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
$activeRes = $conn->query($activeUsersQuery);
if ($activeRes && $row = $activeRes->fetch_assoc()) {
    $active_users = $row['active_count'] ?? 0;
}

// Fetch logs with optional filtering
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';
$module_filter = $_GET['module'] ?? '';
$status_filter = $_GET['status'] ?? '';

$sql = "SELECT * FROM audit_logs WHERE 1=1";
$params = [];
$types = "";

if ($search !== '') {
    $sql .= " AND (user_name LIKE ? OR action LIKE ? OR module LIKE ? OR audit_id LIKE ?)";
    $searchParam = "%$search%";
    array_push($params, $searchParam, $searchParam, $searchParam, $searchParam);
    $types .= "ssss";
}
if ($role_filter !== '') {
    $sql .= " AND role = ?";
    array_push($params, $role_filter);
    $types .= "s";
}
if ($module_filter !== '') {
    $sql .= " AND module = ?";
    array_push($params, $module_filter);
    $types .= "s";
}
if ($status_filter !== '') {
    $sql .= " AND status = ?";
    array_push($params, $status_filter);
    $types .= "s";
}

$sql .= " ORDER BY created_at DESC LIMIT 100"; // Added limit for performance
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Audit Trail - Admin Dashboard</title>
    <link rel="stylesheet" href="/DORCFSRF/css/dashboard.css" />
    <link rel="stylesheet" href="/DORCFSRF/css/audit_trail.css" />
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
    <div class="container">
        <!-- Reusing Dashboard Sidebar -->
        <aside class="sidebar">
            <img src="/DORCFSRF/assets/QECI LOGO.jpg" alt="qecilogo" />
            <nav>
                <a href="dashboard.php"><i class="bx bxs-dashboard"> </i> Dashboard</a>
                <a href="form.php"><i class="bx bx-notepad"></i> Form</a>
                <a href="clearance_status.php"><i class="bx bxs-graduation"></i> Clearance Status</a>
                <a href="payment_transaction.php"><i class="bx bx-coin-stack"></i> Payment Transaction</a>
                <a href="scholastic_record_status.php"><i class="bx bx-bar-chart-alt-2"></i> Scholastic Record
                    Status</a>
                <a href="completion_report.php"><i class="bx bxs-calendar"></i> Completion Report</a>
                <p>SETTINGS</p>
                <a href="audit_trail.php" class="active"><i class="bx bx-history"></i> Audit Trail</a>
                <a href="/DORCFSRF/pages/admin_dashboard/account_management.php"><i class='bx bx-briefcase-alt'></i>
                    Account Management</a>
                <a href="account_settings.php"><i class="bx bx-cog"></i> Account Settings</a>
            </nav>
        </aside>

        <div class="content-wrapper">
            <!-- Header (similar to dashboard) -->
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
                </div>
            </header>

            <!-- Main Audit Trail Content -->
            <main class="main-content">
                <div class="audit-container">
                    <div class="audit-header">
                        <h2 style="color: white;">Audit Trail</h2>
                        <p style="color: white;">Monitor and review administrative activities in the system.</p>
                    </div>

                    <form method="GET" action="audit_trail.php" class="audit-filters">
                        <div class="search-bar">
                            <input type="text" name="search" placeholder="Search by user, action, module, target ID..."
                                value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <select class="filter-select" name="role">
                            <option value="">Role</option>
                            <option value="Staff" <?php echo $role_filter === 'Staff' ? 'selected' : ''; ?>>Staff</option>
                            <option value="Student" <?php echo $role_filter === 'Student' ? 'selected' : ''; ?>>Student
                            </option>
                        </select>
                        <select class="filter-select" name="module" id="module-filter">
                            <option value="">Module</option>
                            <option value="Forms" <?php echo $module_filter === 'Forms' ? 'selected' : ''; ?>>Forms
                            </option>
                            <option value="Clearance" <?php echo $module_filter === 'Clearance' ? 'selected' : ''; ?>>
                                Clearance</option>
                            <?php if ($role_filter !== 'Staff'): ?>
                                <option value="Payment" <?php echo $module_filter === 'Payment' ? 'selected' : ''; ?>>Payment
                                </option>
                                <option value="Scholastic Record" <?php echo $module_filter === 'Scholastic Record' ? 'selected' : ''; ?>>Scholastic Record</option>
                            <?php endif; ?>
                        </select>
                        <select class="filter-select" name="status">
                            <option value="">Status</option>
                            <option value="Success" <?php echo $status_filter === 'Success' ? 'selected' : ''; ?>>Success
                            </option>
                        </select>
                        <button type="submit" style="display:none;"></button>
                        <button type="button" class="btn-export"><i class="bx bx-download"></i> Export Logs</button>
                    </form>

                    <div class="audit-summary">
                        <div class="summary-card">
                            <h4>Total Logs</h4>
                            <p class="value"><?php echo number_format($total_logs); ?></p>
                        </div>

                        <div class="summary-card">
                            <h4>Active Users</h4>
                            <p class="value"><?php echo number_format($active_users); ?></p>
                        </div>
                    </div>

                    <div class="audit-table-wrapper">
                        <table class="audit-table">
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>User</th>
                                    <th>Role</th>
                                    <th>Module</th>
                                    <th>Action</th>
                                    <th>Status</th>
                                    <th>View</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($logs)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 30px;">No audit logs found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($logs as $log):
                                        $logJson = htmlspecialchars(json_encode($log), ENT_QUOTES, 'UTF-8');
                                        $date = date('m/d/Y h:i A', strtotime($log['created_at']));
                                        $badgeClass = $log['status'] === 'Success' ? 'badge-success' : 'badge-failed';
                                        ?>
                                        <tr>
                                            <td><?php echo $date; ?></td>
                                            <td><?php echo htmlspecialchars($log['user_name']); ?></td>
                                            <td><?php echo htmlspecialchars($log['role']); ?></td>
                                            <td><?php echo htmlspecialchars($log['module']); ?></td>
                                            <td><?php echo htmlspecialchars($log['action']); ?></td>
                                            <td><span
                                                    class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($log['status']); ?></span>
                                            </td>
                                            <td><button class="btn-view"
                                                    onclick="viewLogDetails(<?php echo $logJson; ?>)">View</button></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Side Panel Overlay -->
    <div class="panel-overlay" id="panelOverlay" onclick="closeSidePanel()"></div>

    <!-- Side Panel Content -->
    <div class="audit-sidepanel" id="auditSidePanel">
        <div class="panel-header">
            <h3>Audit Log Details</h3>
            <button class="close-panel" onclick="closeSidePanel()">&times;</button>
        </div>

        <div class="detail-section">
            <h4>Log Info</h4>
            <div class="detail-row">
                <span class="detail-label">Audit ID:</span>
                <span class="detail-value" id="dtl-audit-id"></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Timestamp:</span>
                <span class="detail-value" id="dtl-timestamp"></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">User:</span>
                <span class="detail-value" id="dtl-user"></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Role:</span>
                <span class="detail-value" id="dtl-role"></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Module:</span>
                <span class="detail-value" id="dtl-module"></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Action:</span>
                <span class="detail-value" id="dtl-action"></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status:</span>
                <span class="detail-value" id="dtl-status" style="font-weight:600;"></span>
            </div>
        </div>

        <div class="detail-section">
            <h4>Affected Record</h4>
            <div class="detail-row">
                <span class="detail-label">Type:</span>
                <span class="detail-value" id="dtl-record-type"></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">ID:</span>
                <span class="detail-value" id="dtl-record-id"></span>
            </div>
        </div>

        <div class="detail-section">
            <h4>Technical Info</h4>
            <div class="detail-row">
                <span class="detail-label">IP Address:</span>
                <span class="detail-value" id="dtl-ip"></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Device Info:</span>
                <span class="detail-value" id="dtl-device"></span>
            </div>
        </div>
    </div>

    <!-- jsPDF and autoTable libraries for PDF Export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

    <script>
        document.querySelector('.btn-export').addEventListener('click', function () {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('landscape'); // Landscape for better fit

            // Title
            doc.setFontSize(18);
            doc.setTextColor(40);
            doc.text('System Audit Trail Report', 14, 22);

            // Metadata
            doc.setFontSize(10);
            doc.setTextColor(100);

            let roleSelect = document.querySelector('select[name="role"]');
            let moduleSelect = document.querySelector('select[name="module"]');
            let statusSelect = document.querySelector('select[name="status"]');

            let roleFilter = roleSelect.options[roleSelect.selectedIndex].text;
            let moduleFilter = moduleSelect.options[moduleSelect.selectedIndex].text;
            let statusFilter = statusSelect.options[statusSelect.selectedIndex].text;
            let dateStr = new Date().toLocaleString();

            doc.text(`Generated: ${dateStr}`, 14, 30);
            doc.text(`Filters -> Role: ${roleFilter} | Module: ${moduleFilter} | Status: ${statusFilter}`, 14, 36);

            // Extract Table Data
            const table = document.querySelector('.audit-table');
            const rows = Array.from(table.querySelectorAll('tbody tr'));

            if (rows.length === 1 && rows[0].innerText.includes('No audit logs found')) {
                alert('No data available to export.');
                return;
            }

            const data = rows.map(row => {
                const cells = row.querySelectorAll('td');
                return [
                    cells[0].innerText.trim(), // Timestamp
                    cells[1].innerText.trim(), // User
                    cells[2].innerText.trim(), // Role
                    cells[3].innerText.trim(), // Module
                    cells[4].innerText.trim(), // Action
                    cells[5].innerText.trim()  // Status
                ];
            });

            // Generate Table
            doc.autoTable({
                head: [['Timestamp', 'User', 'Role', 'Module', 'Action', 'Status']],
                body: data,
                startY: 42,
                styles: { fontSize: 9, cellPadding: 4 },
                headStyles: { fillColor: [41, 128, 185], textColor: 255, fontStyle: 'bold' },
                alternateRowStyles: { fillColor: [245, 247, 250] },
                columnStyles: {
                    0: { cellWidth: 35 },
                    1: { cellWidth: 40 },
                    2: { cellWidth: 25 },
                    3: { cellWidth: 30 },
                    4: { cellWidth: 'auto' }, // Action takes remaining
                    5: { cellWidth: 25 }
                }
            });

            // Save PDF
            doc.save('QECI_Audit_Logs.pdf');
        });

        function viewLogDetails(log) {
            // Format date safely handling potential parsing differences
            let d = new Date(log.created_at);
            let formattedDate = d.toLocaleString('en-US', {
                month: 'short', day: 'numeric', year: 'numeric',
                hour: '2-digit', minute: '2-digit', hour12: true
            });
            if (formattedDate === 'Invalid Date') {
                formattedDate = log.created_at; // Fallback
            }

            document.getElementById('dtl-audit-id').innerText = log.audit_id;
            document.getElementById('dtl-timestamp').innerText = formattedDate;
            document.getElementById('dtl-user').innerText = log.user_name;
            document.getElementById('dtl-role').innerText = log.role;
            document.getElementById('dtl-module').innerText = log.module;
            document.getElementById('dtl-action').innerText = log.action;

            let statusElem = document.getElementById('dtl-status');
            statusElem.innerText = log.status;
            statusElem.style.color = log.status === 'Success' ? '#166534' : '#991b1b';

            document.getElementById('dtl-record-type').innerText = log.record_type || 'N/A';
            document.getElementById('dtl-record-id').innerText = log.record_id || 'N/A';

            document.getElementById('dtl-ip').innerText = log.ip_address || 'N/A';
            document.getElementById('dtl-device').innerText = log.device_info || 'N/A';

            document.getElementById('auditSidePanel').classList.add('open');
            document.getElementById('panelOverlay').classList.add('show');
        }

        function closeSidePanel() {
            document.getElementById('auditSidePanel').classList.remove('open');
            document.getElementById('panelOverlay').classList.remove('show');
        }

        // Auto submit form when dropdowns change
        document.querySelectorAll('.filter-select').forEach(select => {
            select.addEventListener('change', () => {
                select.form.submit();
            });
        });

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
</body>

</html>