<?php
ini_set('session.gc_maxlifetime', 31536000);
session_set_cookie_params(31536000);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Student') {
    header('Location: /DORCFSRF/pages/login_form/index.php');
    exit;
}
include __DIR__ . '/../../db_connect.php';

$full_name = $_SESSION['full_name'] ?? $_SESSION['username'];
$user_id = $_SESSION['user_id'];

// Get stored profile image
$profile_image = '/DORCFSRF/assets/student_profiles/' . $user_id . '_profile.jpg';
$profile_image_alt = '/DORCFSRF/assets/student_profiles/' . $user_id . '_profile.png';
$profile_image_alt2 = '/DORCFSRF/assets/student_profiles/' . $user_id . '_profile.gif';

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
    <title>Forms - Submission Complete</title>

    <link rel="stylesheet" href="/DORCFSRF/css/dashboard.css" />
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="/DORCFSRF/css/forms.css" /> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body>
    <div class="container">
        <aside class="sidebar">
            <img src="/DORCFSRF/assets/QECI LOGO.jpg" alt="qecilogo" />
            <nav>
                <a href="/DORCFSRF/pages/dashboard.php"><i class="bx bxs-dashboard"> </i> Dashboard</a>
                <a href="/DORCFSRF/pages/forms.php"><i class="bx bx-notepad"></i> Forms</a>
                <a href="/DORCFSRF/pages/clearance.php"><i class="bx bxs-graduation"></i> Clearance Status</a>
                <a href="/DORCFSRF/pages/payment.php" ><i class="bx bx-coin-stack"></i> Payment Transaction</a>
                <a href="/DORCFSRF/pages/record.php"><i class="bx bx-bar-chart-alt-2"></i> Scholastic Record Status</a>
                <a href="/DORCFSRF/pages/completion.php" class="active"><i class="bx bxs-calendar"></i> Completion Report</a>
                <p>SETTINGS</p>
                <a href="/DORCFSRF/pages/settings.php"><i class="bx bx-cog"></i> Account Settings</a>
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
                    <i class="bx bx-bell"></i>
                    <i class="bx bx-globe bx-flip-vertical"></i>
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
                                <p>STUDENT</p>
                            </div>
                            <div class="card-actions">
                                <button class="action-btn">Profile</button>
                                <button class="action-btn">Sign Out</button>
                            </div>
                            <script src="/DORCFSRF/pages/script/dropdown.js"></script>
                        </div>
                    </div>
                </div>
            </header>

            <main class="main-content">
                <div class="submission-modal-overlay">
                    <div class="submission-modal">
                        <div class="submission-content">
                            <i class="fa-solid fa-spinner"></i>
                            <h2>Print Report in Progress</h2>
                            <p>Your completion report is now processing for print.
                                Please wait while the document loads.
                            </p>
                        </div>
                    </div>
                </div>
                </main>
        </div>
    </div>
</body>

</html>









