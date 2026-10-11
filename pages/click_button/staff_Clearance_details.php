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
$user_id = $_SESSION['user_id'];

// Get unread notification count
$unreadCount = getUnreadNotificationCount($conn, $user_id);
$notifications = getNotifications($conn, $user_id, 5);

// Get stored profile image
$profile_image = '/DORCFSRF/assets/staff_profiles/' . $user_id . '_profile.jpg';
$profile_image_alt = '/DORCFSRF/assets/staff_profiles/' . $user_id . '_profile.png';
$profile_image_alt2 = '/DORCFSRF/assets/staff_profiles/' . $user_id . '_profile.gif';

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
                <a href="/DORCFSRF/pages/staff_dashboard/staff_dasboard.php"><i class="bx bxs-dashboard"> </i> Dashboard</a>
                <a href="/DORCFSRF/pages/staff_dashboard/staff_form.php"><i class="bx bx-notepad"></i> Form</a>
                <a href="/DORCFSRF/pages/staff_dashboard/staff_clearance.php" class="active"><i class="bx bxs-graduation"></i>
                    Clearance Status</a>
                <a href="/DORCFSRF/pages/staff_dashboard/staff_payment_transaction.php"><i class="bx bx-coin-stack"></i> Payment
                    Transaction</a>
                <a href="/DORCFSRF/pages/staff_dashboard/staff_scholastic_record.php"><i class="bx bx-bar-chart-alt-2"></i>
                    Scholastic Record Status</a>
                <a href="/DORCFSRF/pages/staff_dashboard/staff_completion_report.php"><i class="bx bxs-calendar"></i> Completion
                    Report</a>
                <p>SETTINGS</p>
                <a href="/DORCFSRF/pages/staff_dashboard/staff_account_settings.php"><i class="bx bx-cog"></i> Account
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
                                <button class="action-btn">Profile</button>
                                <button class="action-btn">Sign Out</button>
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

                    <script>
                        const notifTrigger = document.getElementById('notif-trigger');
                        const notifDropdown = document.getElementById('notif-dropdown');
                        
                        if (notifTrigger) {
                            notifTrigger.addEventListener('click', (e) => {
                                e.stopPropagation();
                                notifDropdown.style.display = notifDropdown.style.display === 'none' ? 'block' : 'none';
                            });
                        }
                        
                        window.addEventListener('click', () => {
                            if (notifDropdown) notifDropdown.style.display = 'none';
                        });
                    </script>
                </div>
            </header>
            <main class="main-content">
                <div class="background-mock">
                    <div class="modal-overlay">

                        <div class="success-notification" id="saveSuccessToast"
                            style="display: flex; justify-content: center;">
                            <p>Save Successfully</p>
                            <button class="toast-close-button"><i class="fas fa-times"></i></button>
                        </div>
                        <div class="modal-content">

                            <div class="modal-header"
                                style="display: flex ; justify-content: center; flex-direction: column-reverse; ">
                                <h2>Clearance Details - Thesis</h2>
                                <button class="close-button"><a
                                        href="/DORCFSRF/pages/staff_dashboard/staff_dasboard.php"><i
                                            class="fas fa-times"></i></a></button>
                            </div>
                            <p class="modal-subtitle">Review your requirements, status, and submission updates.</p>

                            <div class="status-box">
                                <div class="status-info">
                                    <i class="fas fa-bars status-icon"></i>
                                    <div class="text-group">
                                        <p class="title">Thesis Clearance</p>
                                        <p class="subtitle" id="studentName">Juan Mercado Dela Cruz</p>
                                        <p class="date">Last Updated: September 2, 2025 | 8:00am</p>
                                    </div>
                                </div>

                                <div class="status-indicators-wrapper">
                                    <button class="status-main cleared" id="statusDropdownToggle" aria-expanded="false">
                                        CLEARED
                                        <i class="fas fa-caret-down status-dropdown-icon"></i>
                                    </button>

                                    <div class="dropdown-options" id="statusOptions" style="display: none;">
                                        <div class="status-label in-progress" data-status="IN PROGRESS">IN PROGRESS
                                        </div>
                                        <div class="status-label pending" data-status="PENDING">PENDING</div>
                                        <div class="status-label cleared" data-status="CLEARED">CLEARED</div>
                                    </div>
                                </div>
                            </div>

                            <div class="main-grid">

                                <div class="card activity-log">
                                    <div class="card-header">
                                        <i class="fas fa-arrow-right card-icon"></i>
                                        <p>Activity Log</p>
                                    </div>
                                    <div class="card-content">
                                        <div class="log-item">
                                            <i class="fas fa-check-circle log-check"></i>
                                            <div class="log-text">
                                                <p class="log-title">Updated: Thesis Cleared</p>
                                                <p class="log-date">September 2, 2025 | 8:00am</p>
                                            </div>
                                        </div>
                                        <div class="log-item">
                                            <i class="fas fa-check-circle log-check"></i>
                                            <div class="log-text">
                                                <p class="log-title">Reviewed Turn In Report</p>
                                                <p class="log-date">September 1, 2025 | 4:00pm</p>
                                            </div>
                                        </div>
                                        <div class="log-item">
                                            <i class="fas fa-check-circle log-check"></i>
                                            <div class="log-text">
                                                <p class="log-title">Thesis Manuscript Reviewed</p>
                                                <p class="log-date">August 30, 2025 | 1:00pm</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card remarks-card">
                                    <div class="card-header remarks-header">
                                        <i class="fas fa-exclamation-circle remarks-icon"></i>
                                        <h3>Remarks from Officer</h3>
                                    </div>
                                    <div class="remarks-content">
                                        <p class="remark-text" id="remarkText" contenteditable="false">
                                            Please ensure your Turn it in Similarity Report is included. Submit before
                                            September 3rd.
                                        </p>
                                    </div>
                                    <div class="card-actions">
                                        <button class="action-button edit-button" id="editButton">Edit</button>
                                        <button class="action-button save-button" id="saveButton">Save</button>
                                    </div>
                                </div>

                            </div>

                        </div>
                    </div>
                </div>
            </main>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    // Get student name from URL parameter
                    const urlParams = new URLSearchParams(window.location.search);
                    const studentName = urlParams.get('user');
                    if (studentName) {
                        document.getElementById('studentName').textContent = decodeURIComponent(studentName);
                    }

                    const toggleButton = document.getElementById('statusDropdownToggle');
                    const optionsContainer = document.getElementById('statusOptions');
                    const options = optionsContainer.querySelectorAll('.status-label');

                    // NEW: Elements for Save Success Toast
                    const saveButton = document.getElementById('saveButton');
                    const successToast = document.getElementById('saveSuccessToast');
                    const toastCloseButton = successToast.querySelector('.toast-close-button');

                    // Function to toggle the dropdown
                    function toggleDropdown() {
                        if (optionsContainer.style.display === 'block') {
                            optionsContainer.style.display = 'none';
                            toggleButton.setAttribute('aria-expanded', 'false');
                        } else {
                            optionsContainer.style.display = 'block';
                            toggleButton.setAttribute('aria-expanded', 'true');
                        }
                    }

                    // NEW: Function to show and hide the success toast
                    function showSuccessToast() {
                        // 1. Show the toast
                        successToast.classList.add('show');

                        // 2. Automatically hide the toast after 3 seconds
                        setTimeout(() => {
                            successToast.classList.remove('show');
                        }, 3000);
                    }

                    // --- DROPDOWN LOGIC ---
                    // 1. Event listener to toggle the dropdown on click
                    toggleButton.addEventListener('click', function (event) {
                        event.stopPropagation();
                        toggleDropdown();
                    });

                    // 2. Event listener to close the dropdown when clicking outside
                    document.addEventListener('click', function (event) {
                        if (!optionsContainer.contains(event.target) && event.target !== toggleButton) {
                            optionsContainer.style.display = 'none';
                            toggleButton.setAttribute('aria-expanded', 'false');
                        }
                    });

                    // 3. Event listener for selecting an option (handles status update)
                    options.forEach(option => {
                        option.addEventListener('click', function () {
                            const newStatus = option.getAttribute('data-status');

                            // Update the main button text and color class
                            toggleButton.textContent = newStatus;

                            // Clear existing color classes and apply new one
                            toggleButton.className = 'status-main';
                            if (newStatus === 'IN PROGRESS') {
                                toggleButton.classList.add('in-progress');
                            } else if (newStatus === 'PENDING') {
                                toggleButton.classList.add('pending');
                            } else if (newStatus === 'CLEARED') {
                                toggleButton.classList.add('cleared');
                            }

                            // Re-add the icon
                            const icon = document.createElement('i');
                            icon.className = 'fas fa-caret-down status-dropdown-icon';
                            toggleButton.appendChild(icon);

                            // Close the dropdown after selection
                            toggleDropdown();

                            console.log(`Status selected: ${newStatus}`);
                        });
                    });

                    // --- NEW SAVE BUTTON LOGIC ---
                    saveButton.addEventListener('click', function () {
                        // Simulate saving data (In a real app, this is where your AJAX call goes)
                        console.log('Attempting to save changes...');

                        // Show the success toast
                        showSuccessToast();
                    });

                    // NEW: Close button for the toast itself
                    toastCloseButton.addEventListener('click', function () {
                        successToast.classList.remove('show');
                    });
                });

                document.addEventListener('DOMContentLoaded', () => {
                    const remarkText = document.getElementById('remarkText');
                    const editBtn = document.getElementById('editButton');
                    const saveBtn = document.getElementById('saveButton');

                    // --- EDIT FUNCTION ---
                    editBtn.addEventListener('click', () => {
                        remarkText.contentEditable = "true";
                        remarkText.focus();

                        // Visual cues
                        editBtn.style.opacity = "0.5";
                        editBtn.disabled = true;
                        saveBtn.style.display = "inline-block";
                    });

                    // --- SAVE FUNCTION ---
                    saveBtn.addEventListener('click', () => {
                        // 1. Lock the text
                        remarkText.contentEditable = "false";

                        // 2. Clean the input (prevents overflow from hidden HTML tags)
                        const finalValue = remarkText.innerText.trim();

                        // 3. UI Reset
                        saveBtn.style.display = "none";
                        editBtn.style.opacity = "1";
                        editBtn.disabled = false;

                        console.log("Data ready for server:", finalValue);

                        // Logic to send to database goes here
                        // performSave(finalValue);
                    });

                    // --- PREVENT OVERFLOW ON PASTE ---
                    // This stops users from pasting large images or formatted tables into the box
                    remarkText.addEventListener('paste', (e) => {
                        e.preventDefault();
                        const text = e.clipboardData.getData('text/plain');
                        document.execCommand('insertText', false, text);
                    });
                });
            </script>
</body>

</html>









