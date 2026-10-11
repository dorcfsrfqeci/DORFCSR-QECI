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

// Fetch students who have submitted payment records
$paymentQuery = "
    SELECT 
        p.or_num,
        p.student_id,
        p.type_schdocs,
        p.num_copies,
        p.type_copy,
        p.tot_amount,
        p.payment_method,
        p.payment_date,
        s.student_firstname,
        s.student_lastname,
        u.email
    FROM payment_record_database p
    LEFT JOIN student_information_database s ON p.student_id = s.student_id
    LEFT JOIN user_database u ON p.student_id = u.user_id
    ORDER BY p.payment_date DESC 
    LIMIT 20
";
$paymentResult = $conn->query($paymentQuery);
$paymentData = [];
$counter = 1;
if ($paymentResult && $paymentResult->num_rows > 0) {
    while ($row = $paymentResult->fetch_assoc()) {
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
        
        // Determine status based on payment method and date
        $payment_status = 'pending';
        $payment_method = $row['payment_method'] ?? 'Pending';
        if ($payment_method && $payment_method !== 'Pending') {
            $payment_status = 'paid';
        }
        
        $paymentData[] = [
            'or' => $row['or_num'] ?? 'OR-' . str_pad($counter, 4, '0', STR_PAD_LEFT) . '-' . date('mY'),
            'name' => htmlspecialchars(($row['student_firstname'] ?? '') . ' ' . ($row['student_lastname'] ?? '')),
            'email' => htmlspecialchars($row['email'] ?? 'N/A'),
            'doc' => htmlspecialchars($row['type_schdocs'] ?? 'TOR'),
            'copies' => $row['num_copies'] ?? 1,
            'copy_type' => htmlspecialchars($row['type_copy'] ?? 'Original'),
            'price' => number_format(floatval($row['tot_amount'] ?? '0.00'), 2),
            'method' => htmlspecialchars($payment_method),
            'date' => $row['payment_date'] ? date('m/d/Y', strtotime($row['payment_date'])) : 'N/A',
            'status' => $payment_status,
            'id' => $row['student_id'],
            'profile_image' => $student_profile_image
        ];
        $counter++;
    }
}
$paymentJSON = json_encode($paymentData);

// Get stored profile image
$profile_image = '/DORCFSRF/assets/staff_profiles/' . $staff_id . '_profile.jpg';
$profile_image_alt = '/DORCFSRF/assets/staff_profiles/' . $staff_id . '_profile.png';
$profile_image_alt2 = '/DORCFSRF/assets/staff_profiles/' . $staff_id . '_profile.gif';

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
    <title>Payment Transaction</title>
    <link rel="stylesheet" href="/DORCFSRF/css/dashboard.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
</head>

<body>
    <div class="container">
        <aside class="sidebar">
            <img src="/DORCFSRF/assets/QECI LOGO.jpg" alt="qecilogo" />
            <nav>
                <a href="staff_dasboard.php"><i class="bx bxs-dashboard"> </i> Dashboard</a>
                <a href="staff_form.php"><i class="bx bx-notepad"></i> Form</a>
                <a href="staff_clearance.php"><i class="bx bxs-graduation"></i> Clearance
                    Status</a>
                <a href="staff_payment_transaction.php" class="active"><i
                        class="bx bx-coin-stack"></i> Payment Transaction</a>
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
                                <p>STAFF</p>
                            </div>
                            <div class="card-actions">
                                <a href="staff_account_settings.php" class="action-btn action-btn-primary">Profile</a>
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
                        <?php if (empty($notifications)): ?>
                            <div style="padding: 30px 15px; text-align: center; color: #999;">
                                <p>No notifications yet</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($notifications as $notif): ?>
                                <div class="notification-item" data-notif-id="<?php echo $notif['id']; ?>" style="padding: 15px; border-bottom: 1px solid #eee; cursor: pointer; background: <?php echo $notif['is_read'] ? '#fff' : '#f0f7ff'; ?>; transition: background 0.2s;" onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='<?php echo $notif['is_read'] ? '#fff' : '#f0f7ff'; ?>'">
                                    <div style="display: flex; justify-content: space-between; align-items: start;">
                                        <div style="flex: 1;">
                                            <p style="margin: 0 0 5px 0; font-weight: 600; color: #333;"><?php echo htmlspecialchars($notif['student_name']); ?></p>
                                            <p style="margin: 0 0 5px 0; color: #666; font-size: 13px;"><?php echo htmlspecialchars($notif['message']); ?></p>
                                            <p style="margin: 0; color: #999; font-size: 12px;"><?php echo date('M d, Y g:ia', strtotime($notif['created_at'])); ?></p>
                                        </div>
                                        <?php if (!$notif['is_read']): ?>
                                            <div style="width: 10px; height: 10px; background: #007bff; border-radius: 50%; margin-left: 10px; margin-top: 5px;"></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <script>
                                    document.querySelector('[data-notif-id="<?php echo $notif['id']; ?>"]').addEventListener('click', function() {
                                        fetch('/DORCFSRF/api/mark_notification_read.php?id=<?php echo $notif['id']; ?>');
                                    });
                                </script>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
            </header>
            <main class="main-content" style="width: 100%; max-width: 1200px; margin: 0 auto; padding: 20px;">

                <div class="breadcrumb" style="margin-bottom: 10px; font-weight: 700;color: white;">
                    Home > Payment Transaction</div>
                <div class="payment-card">
                    <div class="card-header">
                        <div class="tabs">
                            <div class="tab-item active" data-filter="all">All Payments</div>
                            <div class="tab-item" data-filter="paid">Paid</div>
                            <div class="tab-item" data-filter="pending">Pending</div>
                            <div class="tab-item" data-filter="inprogress">In Progress</div>
                        </div>
                    </div>

                    <div class="table-wrapper">
                        <table class="payment-table">
                            <thead>
                                <tr>
                                    <th>OR No.</th>
                                    <th>Students</th>
                                    <th>Email ID</th>
                                    <th>Type of Document/s</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="payment-body">
                            </tbody>
                        </table>
                    </div>
                </div>

            </main>
        </div>
    </div>
    <script>
        const paymentData = <?php echo $paymentJSON; ?>;

        const tableBody = document.getElementById('payment-body');
        const tabs = document.querySelectorAll('.tab-item');

        // Function to draw the table
        function renderTable(filter = 'all') {
            // Clear current rows
            tableBody.innerHTML = '';

            // Filter data
            const filteredData = filter === 'all'
                ? paymentData
                : paymentData.filter(item => item.status === filter);

            // Build rows
            filteredData.forEach(item => {
                const statusLabel = item.status === 'inprogress' ? 'In Progress' : item.status.charAt(0).toUpperCase() + item.status.slice(1);
                const cacheBreak = new Date().getTime();
                const profileImageHTML = item.profile_image 
                    ? `<img src="${item.profile_image}?t=${cacheBreak}" alt="${item.name}" style="width: 35px; height: 35px; object-fit: cover; border-radius: 50%;" />` 
                    : '<div class="avatar"></div>';

                const docDetails = `${item.doc} (${item.copies}x ${item.copy_type})`;
                const methodBadge = `<span style="font-size: 11px; padding: 3px 8px; background: #e8f4f8; color: #0277bd; border-radius: 12px;">${item.method}</span>`;

                tableBody.innerHTML += `
                <tr>
                    <td style="color:#7f8c8d"><strong>${item.or}</strong></td>
                    <td>
                        <div class="student-info">
                            ${profileImageHTML}
                            <span class="student-name">${item.name}</span>
                        </div>
                    </td>
                    <td style="color:#007bff">${item.email}</td>
                    <td>
                        <div style="font-size: 13px;">
                            <div>${item.doc}</div>
                            <div style="color: #999; font-size: 11px;">${item.copies}x ${item.copy_type}</div>
                        </div>
                    </td>
                    <td><strong>₱${item.price}</strong></td>
                    <td>
                        <span class="status-pill ${item.status}">
                            <span class="dot"></span> ${statusLabel}
                        </span>
                    </td>
                    <td>
                        <button class="view-btn" onclick="viewDetails('${item.name}', '${item.or}', '${item.id}')">View</button>
                    </td>
                </tr>
            `;
            });

            if (filteredData.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: #999; padding: 30px;">No payment records found</td></tr>';
            }
        }

        // Add click event listeners to tabs
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                // 1. Remove active class from all tabs
                tabs.forEach(t => t.classList.remove('active'));
                // 2. Add active class to clicked tab
                tab.classList.add('active');
                // 3. Filter the table
                const filterValue = tab.getAttribute('data-filter');
                renderTable(filterValue);
            });
        });

        // View Details Function
        function viewDetails(name, orNumber, studentId) {
            const slug = encodeURIComponent(name);
            window.location.href = `payment_reciep.php?user=${slug}&or=${orNumber}&student_id=${studentId}`;
        }

        // Initial render on page load
        renderTable();
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









