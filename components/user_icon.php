<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$icon_full_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User';
$icon_user_id = $_SESSION['user_id'] ?? '';
$icon_role = $_SESSION['role'] ?? 'STUDENT';

// Get stored profile image depending on role
if (strtoupper($icon_role) === 'STAFF') {
    $icon_profile_base = '/DORCFSRF/assets/staff_profiles/' . $icon_user_id . '_profile';
} else if (strtoupper($icon_role) === 'ADMIN') {
    $icon_profile_base = '/DORCFSRF/assets/admin_profiles/' . $icon_user_id . '_profile';
} else {
    $icon_profile_base = '/DORCFSRF/assets/student_profiles/' . $icon_user_id . '_profile';
}

$icon_profile_jpg = $icon_profile_base . '.jpg';
$icon_profile_png = $icon_profile_base . '.png';
$icon_profile_gif = $icon_profile_base . '.gif';

// Check which image exists
if ($icon_user_id && file_exists($_SERVER['DOCUMENT_ROOT'] . $icon_profile_jpg)) {
    $icon_profile_url = $icon_profile_jpg . '?t=' . filemtime($_SERVER['DOCUMENT_ROOT'] . $icon_profile_jpg);
} elseif ($icon_user_id && file_exists($_SERVER['DOCUMENT_ROOT'] . $icon_profile_png)) {
    $icon_profile_url = $icon_profile_png . '?t=' . filemtime($_SERVER['DOCUMENT_ROOT'] . $icon_profile_png);
} elseif ($icon_user_id && file_exists($_SERVER['DOCUMENT_ROOT'] . $icon_profile_gif)) {
    $icon_profile_url = $icon_profile_gif . '?t=' . filemtime($_SERVER['DOCUMENT_ROOT'] . $icon_profile_gif);
} else {
    $icon_profile_url = null;
}

$settings_link = '#';
if (strtoupper($icon_role) === 'STUDENT') {
    $settings_link = '/DORCFSRF/pages/student_dashboard/settings.php';
} else if (strtoupper($icon_role) === 'STAFF') {
    $settings_link = '/DORCFSRF/pages/staff_dashboard/staff_account_settings.php';
} else if (strtoupper($icon_role) === 'ADMIN') {
    $settings_link = '/DORCFSRF/pages/admin_dashboard/settings.php';
}
?>

<div class="user-icon" style="display: flex; gap: 15px; align-items: center;">
    <div class="notification-container" id="notificationContainer">
        <i class="bx bx-bell" id="notificationBell" title="Notifications"
            style="font-size: 24px; cursor: pointer;"></i>
        <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
        <div class="notification-dropdown" id="notificationDropdown">
            <div class="notification-header">
                <h3>Notifications</h3>
            </div>
            <ul class="notification-list" id="notificationList">
                <li class="notification-item text-center">Loading...</li>
            </ul>
        </div>
    </div>
    <a href="/DORCFSRF/pages/click_button/aboutus.php"><i class="bx bx-globe bx-flip-vertical"></i></a>
    <div class="user-menu-container">
        <div class="profile-icon-wrapper" id="profile-toggle">
            <?php if ($icon_profile_url): ?>
                <img src="<?php echo htmlspecialchars($icon_profile_url); ?>" alt="Profile"
                    style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;" />
            <?php else: ?>
                <i class="bx bxs-user-circle"></i>
            <?php endif; ?>
        </div>
        <div class="profile-card" id="profile-dropdown">
            <div class="card-header-bg"></div>
            <div class="profile-img" id="profileImageContainer">
                <?php if ($icon_profile_url): ?>
                    <img src="<?php echo htmlspecialchars($icon_profile_url); ?>" alt="Profile"
                        style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;" />
                <?php else: ?>
                    <i class="bx bxs-user-circle" style="font-size: 70px; color: #fff;"></i>
                <?php endif; ?>
            </div>
            <div class="profile-info">
                <h3><?php echo htmlspecialchars($icon_full_name); ?></h3>
                <p><?php echo htmlspecialchars(strtoupper($icon_role)); ?></p>
            </div>
            <div class="card-actions">
                <a href="<?php echo htmlspecialchars($settings_link); ?>" class="action-btn" style="text-decoration:none; text-align:center;">Profile</a>
                <a href="/DORCFSRF/pages/logout.php" class="action-btn" style="text-decoration:none; text-align:center;">Sign Out</a>
            </div>
            <script src="/DORCFSRF/pages/script/dropdown.js"></script>
        </div>
    </div>
</div>
