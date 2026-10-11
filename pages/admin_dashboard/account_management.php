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
$full_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Admin';
$admin_id = $_SESSION['user_id'];

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

// Database connection
include __DIR__ . '/../../db_connect.php';
include __DIR__ . '/../../api/notification_system.php';

// Get unread notification count
$unreadCount = getUnreadNotificationCount($conn, $admin_id);
$notifications = getNotifications($conn, $admin_id, 5);

// Get all users
$users = [];
$stmt = $conn->prepare("SELECT user_id, full_name, email, role FROM user_database ORDER BY user_id DESC");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
$stmt->close();

// Get next sequential IDs for staff
$nextStaffId = 'STF001';
$staffResult = $conn->query("SELECT user_id FROM user_database WHERE role = 'Staff' ORDER BY user_id DESC LIMIT 1");
if ($staffResult && $staffResult->num_rows > 0) {
    $lastStaff = $staffResult->fetch_assoc()['user_id'];
    $lastNum = (int) substr($lastStaff, 3);
    $nextStaffId = 'STF' . str_pad($lastNum + 1, 3, '0', STR_PAD_LEFT);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completion Report</title>
    <link rel="stylesheet" href="/DORCFSRF/css/dashboard.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
</head>

<body>
    <div class="container">
        <aside class="sidebar">
            <img src="/DORCFSRF/assets/QECI LOGO.jpg" alt="qecilogo" />
            <nav>
                <a href="/DORCFSRF/pages/admin_dashboard/dashboard.php"><i class="bx bxs-dashboard"> </i> Dashboard</a>
                <a href="/DORCFSRF/pages/admin_dashboard/form.php"><i class="bx bx-notepad"></i> Form</a>
                <a href="/DORCFSRF/pages/admin_dashboard/clearance_status.php"><i class="bx bxs-graduation"></i>
                    Clearance Status</a>
                <a href="/DORCFSRF/pages/admin_dashboard/payment_transaction.php"><i class="bx bx-coin-stack"></i>
                    Payment Transaction</a>
                <a href="/DORCFSRF/pages/admin_dashboard/scholastic_record_status.php"><i
                        class="bx bx-bar-chart-alt-2"></i> Scholastic Record Status</a>
                <a href="/DORCFSRF/pages/admin_dashboard/completion_report.php"><i class="bx bxs-calendar"></i>
                    Completion Report</a>
                <p>SETTINGS</p>
                <a href="/DORCFSRF/pages/admin_dashboard/audit_trail.php"><i class="bx bx-history"></i> Audit Trail</a>
        <a href="/DORCFSRF/pages/admin_dashboard/account_management.php" class="active"><i
                        class='bx  bx-briefcase-alt'></i> Account Management</a>
                <a href="/DORCFSRF/pages/admin_dashboard/account_settings.php"><i class="bx bx-cog"></i> Account
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
                </div>
            </header>
            <main class="main-content">
                <div class="breadcrumb" style="margin-bottom: 10px; font-weight: 700; color: white;">
                    Home >Account Management</div>
                <div class="report-container">
                    <div class="report-header "
                        style="display: flex; justify-content: space-between; align-items: center;">
                        <div class="report-tab">All Accounts</div>
                        <button onclick="openAddAccountModal()"
                            style="background: #007bff; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer; font-weight: bold; display: flex; align-items: center; gap: 5px;">
                            <i class="bx bx-user-plus"></i> Add Account
                        </button>
                    </div>

                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>OR No.</th>
                                <th>Students <span class="sort-icons"><i class="fas fa-chevron-up"></i><i
                                            class="fas fa-chevron-down"></i></span></th>
                                <th>Email ID</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <?php
                                // Determine the profile folder based on role
                                $roleFolder = 'student_profiles';
                                if (strtolower($user['role']) === 'admin') {
                                    $roleFolder = 'admin_profiles';
                                } elseif (strtolower($user['role']) === 'staff') {
                                    $roleFolder = 'staff_profiles';
                                }

                                // Check for existing image extensions
                                $imgBase = '/DORCFSRF/assets/' . $roleFolder . '/' . $user['user_id'] . '_profile';
                                $imgUrl = null;
                                if (file_exists($_SERVER['DOCUMENT_ROOT'] . $imgBase . '.jpg')) {
                                    $imgUrl = $imgBase . '.jpg';
                                } elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . $imgBase . '.png')) {
                                    $imgUrl = $imgBase . '.png';
                                } elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . $imgBase . '.gif')) {
                                    $imgUrl = $imgBase . '.gif';
                                }
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                                    <td>
                                        <div class="student-profile">
                                            <div class="avatar"
                                                style="overflow: hidden; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background-color: #f0f0f0;">
                                                <?php if ($imgUrl): ?>
                                                    <img src="<?php echo htmlspecialchars($imgUrl); ?>" alt="Profile"
                                                        style="width: 100%; height: 100%; object-fit: cover;">
                                                <?php else: ?>
                                                    <i class="fa-solid fa-user" style="color: #888;"></i>
                                                <?php endif; ?>
                                            </div>
                                            <span
                                                class="student-name"><?php echo htmlspecialchars($user['full_name']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                                    <td><button
                                            class="<?php echo strtolower($user['role']); ?>"><?php echo htmlspecialchars($user['role']); ?></button>
                                    </td>
                                    <td><button class="manage-btn"><a
                                                href="account_settings.php?user_id=<?php echo urlencode($user['user_id']); ?>"
                                                style="text-decoration: none; color: inherit;">Manage Account</a></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Account Modal -->
    <div id="addAccountModal" class="qr-modal"
        style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center;">
        <div class="qr-modal-content"
            style="background: white; padding: 30px; border-radius: 10px; width: 100%; max-width: 500px; max-height: 90vh; overflow-y: auto; text-align: left; position: relative;">
            <button onclick="closeAddAccountModal()"
                style="position: absolute; right: 20px; top: 20px; background: none; border: none; font-size: 20px; cursor: pointer; color: #888;">&times;</button>
            <h2 style="margin-bottom: 20px; color: #333;">Add New Account</h2>

            <form id="addAccountForm" onsubmit="submitAddAccount(event)">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Role</label>
                    <select id="new_role" required onchange="updateUserIdByRole()"
                        style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;">
                        <option value="">Select Role</option>
                        <option value="Student">Student</option>
                        <option value="Staff">Staff</option>
                    </select>
                </div>

                <div id="unit_concern_container" style="display: none; margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Unit Concern</label>
                    <select id="new_unit_concern"
                        style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;">
                        <option value="">Select Unit Concern</option>
                        <option value="Thesis">Thesis</option>
                        <option value="Librarian">Librarian</option>
                        <option value="Finance Officer">Finance Officer</option>
                        <option value="Admission Officer">Admission Officer</option>
                        <option value="Lab./ComLab/ ID">Lab./ComLab/ ID</option>
                        <option value="School Cashier">School Cashier</option>
                        <option value="Property Custodian">Property Custodian</option>
                        <option value="Stud. Services Coordin.">Stud. Services Coordin.</option>
                        <option value="Scholarship Coordinat.">Scholarship Coordinat.</option>
                        <option value="Guidance Counselor">Guidance Counselor</option>
                        <option value="Records Officer">Records Officer</option>
                        <option value="Registrar">Registrar</option>
                        <option value="College Dean">College Dean</option>
                    </select>
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">User ID</label>
                    <input type="text" id="new_user_id" required placeholder="User ID"
                        style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;" disabled>
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Full Name</label>
                    <input type="text" id="new_full_name" required placeholder="Full Name"
                        style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Username</label>
                    <input type="text" id="new_username" required placeholder="Username"
                        style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Email</label>
                    <input type="email" id="new_email" required placeholder="Email Address"
                        style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Password</label>
                    <input type="password" id="new_password" required placeholder="Password"
                        style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;">
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Confirm Password</label>
                    <input type="password" id="new_confirm_password" required placeholder="Confirm Password"
                        style="width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc;">
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="closeAddAccountModal()"
                        style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer;">Cancel</button>
                    <button type="submit"
                        style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">Create
                        Account</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const nextStaffId = '<?php echo $nextStaffId; ?>';

        function openAddAccountModal() {
            document.getElementById('addAccountModal').style.display = 'flex';
        }

        function closeAddAccountModal() {
            document.getElementById('addAccountModal').style.display = 'none';
            document.getElementById('addAccountForm').reset();
            updateUserIdByRole();
        }

        function updateUserIdByRole() {
            const role = document.getElementById('new_role').value;
            const userIdInput = document.getElementById('new_user_id');
            const unitConcernContainer = document.getElementById('unit_concern_container');
            const unitConcernInput = document.getElementById('new_unit_concern');

            if (role === 'Staff') {
                userIdInput.value = nextStaffId;
                userIdInput.readOnly = true;
                userIdInput.disabled = false;
                userIdInput.placeholder = 'Staff ID';
                unitConcernContainer.style.display = 'block';
                unitConcernInput.required = true;
            } else if (role === 'Student') {
                userIdInput.value = '';
                userIdInput.readOnly = false;
                userIdInput.disabled = false;
                userIdInput.placeholder = 'Student ID';
                unitConcernContainer.style.display = 'none';
                unitConcernInput.required = false;
            } else {
                userIdInput.value = '';
                userIdInput.disabled = true;
                userIdInput.placeholder = 'User ID';
                unitConcernContainer.style.display = 'none';
                unitConcernInput.required = false;
            }
        }

        function submitAddAccount(e) {
            e.preventDefault();

            const data = {
                role: document.getElementById('new_role').value,
                user_id: document.getElementById('new_user_id').value,
                full_name: document.getElementById('new_full_name').value,
                username: document.getElementById('new_username').value,
                email: document.getElementById('new_email').value,
                password: document.getElementById('new_password').value,
                confirm_password: document.getElementById('new_confirm_password').value,
                unit_concern: document.getElementById('new_unit_concern').value
            };

            if (data.password !== data.confirm_password) {
                alert("Passwords do not match!");
                return;
            }

            fetch('/DORCFSRF/api/admin_add_account.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        alert('Account created successfully!');
                        window.location.reload();
                    } else {
                        alert('Error: ' + result.message);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('An error occurred while creating the account.');
                });
        }
    </script>
</body>
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
        if (trigger && dropdown && !trigger.contains(event.target) && !dropdown.contains(event.target)) {
            dropdown.style.display = 'none';
        }
    });
</script>
<script src="/DORCFSRF/pages/script/prevent-back-button.js"></script>

</html>