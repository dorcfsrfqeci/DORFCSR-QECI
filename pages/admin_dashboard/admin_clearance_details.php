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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clearance Details (Admin)</title>
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
                <a href="clearance_status.php"><i class="bx bxs-graduation"></i> Clearance Status</a>
                <a href="payment_transaction.php"><i class="bx bx-coin-stack"></i> Payment Transaction</a>
                <a href="scholastic_record_status.php"><i class="bx bx-bar-chart-alt-2"></i> Scholastic Record Status</a>
                <a href="completion_report.php"><i class="bx bxs-calendar"></i> Completion Report</a>
                <p>SETTINGS</p>
                <a href="/DORCFSRF/pages/admin_dashboard/audit_trail.php"><i class="bx bx-history"></i> Audit Trail</a>
        <a href="account_management.php"><i class='bx  bx-briefcase-alt'></i>  Account Management</a>
                <a href="account_settings.php"><i class="bx bx-cog"></i> Account Settings</a>
            </nav>
        </aside>
        <div class="content-wrapper">
            <header class="header">
                <div class="user-icon">
                    <i class="bx bx-bell"></i>
                    <a href="/DORCFSRF/pages/click_button/aboutus.php"><i class="bx bx-globe bx-flip-vertical"></i></a>
                    <div class="profile-icon-wrapper">
                        <?php if ($profile_image_url): ?>
                            <img src="<?php echo htmlspecialchars($profile_image_url); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;" />
                        <?php else: ?>
                            <i class="bx bxs-user-circle"></i>
                        <?php endif; ?>
                    </div>
                </div>
            </header>
            <main class="main-content">
                <div class="details-card">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                        <h2 style="margin-top: 0;">Clearance Details (Admin)</h2>
                        <div style="margin-top: -18px;">
                            <button class="btn btn-cancel" onclick="window.location.href='dashboard.php'" title="Back to Dashboard" style="font-size: 1.5rem; padding: 2px 12px;">&#10005;</button>
                        </div>
                    </div>
                    <div class="info-section">
                        <div style="font-size: 2rem; color: #4b2c71;">👤</div>
                        <div class="info-content">
                            <div class="info-header">Student Information</div>
                            <div class="grid-info">
                                <div><strong>Name:</strong> <span id="student-name">Juan Mercado Dela Cruz</span></div>
                                <div><strong>ID:</strong> CS-22-0059</div>
                                <div><strong>Email:</strong> juan.delacruz@email.com</div>
                                <div><strong>Course:</strong> BS Computer Science</div>
                            </div>
                        </div>
                    </div>
                    <div class="info-section" style="flex-direction: column;">
                        <div class="info-header">📄 Requirements</div>
                        <table class="req-table">
                            <tr><th>Requirement</th><th>Status</th></tr>
                            <tr><td>Thesis Manuscript</td><td>Submitted</td></tr>
                            <tr><td>Library Clearance</td><td>Pending</td></tr>
                        </table>
                    </div>
                    <div class="footer-actions">
                        <button class="btn btn-edit">Edit</button>
                        <button class="btn btn-submit">Submit</button>
                        <button class="btn btn-cancel" onclick="window.location.href='dashboard.php'">Cancel</button>
                    </div>
                    <script>
                    // Get student name from query parameter and update the details
                    const params = new URLSearchParams(window.location.search);
                    const stuName = params.get('user');
                    if (stuName) {
                        document.getElementById('student-name').innerText = decodeURIComponent(stuName);
                    }
                    </script>
                </div>
            </main>
        </div>
    </div>
<script src="/DORCFSRF/pages/script/prevent-back-button.js"></script>
</body>
</html>









