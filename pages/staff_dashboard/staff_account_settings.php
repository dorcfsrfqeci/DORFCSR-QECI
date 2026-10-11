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
$full_name = $_SESSION['full_name'] ?? $_SESSION['username'];
$staff_id = $_SESSION['user_id'];

// Database connection
include __DIR__ . '/../../db_connect.php';
include __DIR__ . '/../../api/notification_system.php';

// Get unread notification count
$unreadCount = getUnreadNotificationCount($conn, $staff_id);
$notifications = getNotifications($conn, $staff_id, 5);

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

// Get staff information from database
$staff_info = array(
    'name' => $full_name,
    'email' => '',
    'username' => $_SESSION['username'],
    'role' => $_SESSION['role'] ?? 'Staff'
);

// Query staff information from database
$stmt = $conn->prepare('SELECT email FROM user_database WHERE user_id = ?');
$stmt->bind_param('s', $staff_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $staff_info['email'] = $row['email'];
}
$stmt->close();

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profileImage']) && !isset($_POST['action'])) {
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/DORCFSRF/assets/staff_profiles/';
    
    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file = $_FILES['profileImage'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_ext = array('jpg', 'jpeg', 'png', 'gif');
    
    if (in_array($file_ext, $allowed_ext) && $file['size'] <= 5000000) {
        // Delete old profile images with different extensions
        $old_formats = array('jpg', 'jpeg', 'png', 'gif');
        foreach ($old_formats as $format) {
            $old_file = $upload_dir . $staff_id . '_profile.' . $format;
            if (file_exists($old_file)) {
                unlink($old_file);
            }
        }
        
        $new_filename = $staff_id . '_profile.' . $file_ext;
        $target_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            $_SESSION['profile_image'] = '/DORCFSRF/assets/staff_profiles/' . $new_filename . '?t=' . time();
            $success_msg = 'Image uploaded successfully!';
        } else {
            $error_msg = 'Failed to upload image.';
        }
    } else {
        $error_msg = 'Invalid file type or size. Please use JPG, PNG, or GIF (max 5MB).';
    }
}

// Handle profile update via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $email = $conn->real_escape_string($_POST['email'] ?? '');
    
    // Handle image upload if present
    if (isset($_FILES['profileImage']) && $_FILES['profileImage']['size'] > 0) {
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/DORCFSRF/assets/staff_profiles/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file = $_FILES['profileImage'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_ext = array('jpg', 'jpeg', 'png', 'gif');
        
        if (in_array($file_ext, $allowed_ext) && $file['size'] <= 5000000) {
            // Delete old profile images with different extensions
            $old_formats = array('jpg', 'jpeg', 'png', 'gif');
            foreach ($old_formats as $format) {
                $old_file = $upload_dir . $staff_id . '_profile.' . $format;
                if (file_exists($old_file)) {
                    unlink($old_file);
                }
            }
            
            $new_filename = $staff_id . '_profile.' . $file_ext;
            $target_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                $_SESSION['profile_image'] = '/DORCFSRF/assets/staff_profiles/' . $new_filename . '?t=' . time();
            }
        }
    }
    
    // Update user_database with email
    $stmt = $conn->prepare('UPDATE user_database SET email = ? WHERE user_id = ?');
    $stmt->bind_param('ss', $email, $staff_id);
    $stmt->execute();
    $stmt->close();
    
    echo 'success';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings</title>

    <link rel="stylesheet" href="/DORCFSRF/css/dashboard.css" />
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
                    <a href="staff_payment_transaction.php"><i class="bx bx-coin-stack"></i>
                        Payment Transaction</a>
                    <a href="staff_scholastic_record.php"><i class="bx bx-bar-chart-alt-2"></i>
                        Scholastic Record Status</a>
                    <a href="staff_completion_report.php"><i class="bx bxs-calendar"></i>
                        Completion Report</a>
                    <p>SETTINGS</p>
                    <a href="staff_account_settings.php" class="active"><i
                            class="bx bx-cog"></i> Account Settings</a>
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

                <main class="main-content">
                    <div class="breadcrumb" style="margin-bottom: 10px; font-weight: 700; color: white;">
                        Home > Account Settings</div>
                    <section class="account-settings-card">
                        <h3 class="section-title">BASIC INFO</h3>

                        <form id="imageUploadForm" method="POST" enctype="multipart/form-data" style="display: none;">
                            <input type="file" id="profileImageInput" name="profileImage" accept="image/*" style="display:none;">
                        </form>

                        <div class="info-group profile-upload-section">
                            <div class="profile-image-box" id="settingsProfileBox" style="cursor: pointer; position: relative;">
                                <?php if ($profile_image_url): ?>
                                    <img id="settingsProfilePreview" alt="Profile Preview" src="<?php echo htmlspecialchars($profile_image_url); ?>" style="width:100%; height:100%; object-fit:cover; border-radius:12px; position:absolute; inset:0;" />
                                <?php else: ?>
                                    <img id="settingsProfilePreview" alt="Profile Preview" style="display:none; width:100%; height:100%; object-fit:cover; border-radius:12px; position:absolute; inset:0;" />
                                    <i class='bx bx-image-add' id="imageAddIcon"></i>
                                    <p id="settingsUploadHint">Click to upload image</p>
                                <?php endif; ?>
                            </div>
                            <div class="upload-label">
                                Profile Image
                            </div>
                        </div>
                        <?php if (isset($success_msg)): ?>
                            <div style="padding: 10px; background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; border-radius: 4px; margin-bottom: 15px;">
                                <?php echo $success_msg; ?>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($error_msg)): ?>
                            <div style="padding: 10px; background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; border-radius: 4px; margin-bottom: 15px;">
                                <?php echo $error_msg; ?>
                            </div>
                        <?php endif; ?>

                        <div class="info-group">
                            <label for="name">Name:</label>
                            <input type="text" id="name" value="<?php echo htmlspecialchars($staff_info['name']); ?>" disabled>
                        </div>

                        <div class="info-group">
                            <label for="email">Email Address:</label>
                            <input type="email" id="email" value="<?php echo htmlspecialchars($staff_info['email']); ?>" disabled>
                        </div>

                        <h3 class="section-title account-info-title">ACCOUNT INFO</h3>

                        <div class="info-group">
                            <label for="username">Username:</label>
                            <input type="text" id="username" value="<?php echo htmlspecialchars($staff_info['username']); ?>" disabled>
                        </div>

                        <div class="info-group">
                            <label for="role">Role:</label>
                            <input type="text" id="role" value="<?php echo htmlspecialchars($staff_info['role']); ?>" disabled>
                        </div>

                        <div class="info-group">
                            <label for="password">Password:</label>
                            <input type="password" id="password" value="*******" disabled>
                        </div>

                        <div class="account-actions">
                            <button class="btn-action btn-edit" id="editBtn">Edit</button>
                            <button class="btn-action btn-save" id="saveBtn" style="display:none;">Save Changes</button>
                            <button class="btn-action btn-cancel" id="cancelBtn" style="display:none;">Cancel</button>
                            <button class="btn-action btn-delete" id="deleteBtn">Delete Account</button>
                        </div>

                    </section>

                    <div class="guide-setup">
                        <i class='bx bx-cog'></i>
                        <span><a href="/DORCFSRF/pages/click_button/guide.php">Guide to Set Up Your Account</a></span>
                    </div>
            </div>
            </main>

        </div>
    </div>
    <script>
        // Handle profile image upload
        const profileImageBox = document.getElementById('settingsProfileBox');
        const profileImageInput = document.getElementById('profileImageInput');
        const settingsProfilePreview = document.getElementById('settingsProfilePreview');
        const imageAddIcon = document.getElementById('imageAddIcon');
        const settingsUploadHint = document.getElementById('settingsUploadHint');
        
        let imageChanged = false;
        let newImageFile = null;
        let isEditMode = false;
        
        if (profileImageInput) {
            profileImageInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    newImageFile = file;
                    imageChanged = true;
                    
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        const imageData = event.target.result;
                        
                        // Display in settings preview
                        if (settingsProfilePreview) {
                            settingsProfilePreview.src = imageData;
                            settingsProfilePreview.style.display = 'block';
                        }
                        if (imageAddIcon) imageAddIcon.style.display = 'none';
                        if (settingsUploadHint) settingsUploadHint.style.display = 'none';
                        
                        // Display in profile card header
                        const profileImg = document.querySelector('.profile-card .profile-img');
                        if (profileImg) {
                            profileImg.innerHTML = '<img src="' + imageData + '" style="width:100%; height:100%; object-fit:cover; border-radius:12px;">';
                        }
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        // Load profile image on page load
        window.addEventListener('DOMContentLoaded', function() {
            const profileImageUrl = '<?php echo $profile_image_url ? htmlspecialchars($profile_image_url) : ''; ?>';
            if (profileImageUrl && settingsProfilePreview) {
                settingsProfilePreview.src = profileImageUrl;
                
                // Update profile card
                const profileImg = document.querySelector('.profile-card .profile-img');
                if (profileImg) {
                    profileImg.innerHTML = '<img src="' + profileImageUrl + '" style="width:100%; height:100%; object-fit:cover; border-radius:12px;">';
                }
            }
            
            // Initialize profile box as disabled
            if (profileImageBox) {
                profileImageBox.style.opacity = '0.6';
                profileImageBox.style.cursor = 'default';
                
                // Set up click handler
                profileImageBox.addEventListener('click', () => {
                    if (isEditMode) {
                        profileImageInput.click();
                    }
                });
            }

            // Edit Mode Functionality
            const editBtn = document.getElementById('editBtn');
            const saveBtn = document.getElementById('saveBtn');
            const cancelBtn = document.getElementById('cancelBtn');
            const deleteBtn = document.getElementById('deleteBtn');
            
            const emailInput = document.getElementById('email');

            editBtn.addEventListener('click', function(e) {
                e.preventDefault();
                isEditMode = true;
                
                // Enable inputs
                emailInput.disabled = false;
                
                // Enable profile image upload
                if (profileImageBox) {
                    profileImageBox.style.opacity = '1';
                    profileImageBox.style.cursor = 'pointer';
                }
                
                // Show/hide buttons
                editBtn.style.display = 'none';
                deleteBtn.style.display = 'none';
                saveBtn.style.display = 'inline-block';
                cancelBtn.style.display = 'inline-block';
                
                // Focus on first editable field
                emailInput.focus();
            });

            cancelBtn.addEventListener('click', function(e) {
                e.preventDefault();
                isEditMode = false;
                imageChanged = false;
                newImageFile = null;
                
                // Disable inputs
                emailInput.disabled = true;
                
                // Disable profile image upload
                if (profileImageBox) {
                    profileImageBox.style.opacity = '0.6';
                    profileImageBox.style.cursor = 'default';
                }
                
                // Reload values from database
                emailInput.value = '<?php echo htmlspecialchars($staff_info['email']); ?>';
                
                // Reload profile image
                const profileImageUrl = '<?php echo $profile_image_url ? htmlspecialchars($profile_image_url) : ''; ?>';
                if (profileImageUrl && settingsProfilePreview) {
                    settingsProfilePreview.src = profileImageUrl;
                    settingsProfilePreview.style.display = 'block';
                    if (imageAddIcon) imageAddIcon.style.display = 'none';
                    if (settingsUploadHint) settingsUploadHint.style.display = 'none';
                } else {
                    // No image, show placeholder
                    if (settingsProfilePreview) settingsProfilePreview.style.display = 'none';
                    if (imageAddIcon) imageAddIcon.style.display = 'block';
                    if (settingsUploadHint) settingsUploadHint.style.display = 'block';
                }
                
                // Reset image input
                profileImageInput.value = '';
                
                // Show/hide buttons
                editBtn.style.display = 'inline-block';
                deleteBtn.style.display = 'inline-block';
                saveBtn.style.display = 'none';
                cancelBtn.style.display = 'none';
            });

            saveBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Validate inputs
                if (!emailInput.value.trim()) {
                    alert('Email is required');
                    return;
                }
                
                // Send data via AJAX
                const formData = new FormData();
                formData.append('action', 'update_profile');
                formData.append('email', emailInput.value);
                
                // Add image if changed
                if (imageChanged && newImageFile) {
                    formData.append('profileImage', newImageFile);
                }
                
                fetch('/DORCFSRF/pages/staff_dashboard/staff_account_settings.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    if (data.includes('success')) {
                        alert('Profile updated successfully!');
                        // Reload page to refresh everything
                        window.location.reload();
                    } else {
                        alert('Error updating profile. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating profile');
                });
            });

            deleteBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
                    window.location.href = '/DORCFSRF/pages/click_button/delete.php';
                }
            });
        });
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

    <script src="/DORCFSRF/pages/script/dropdown.js"></script>
    <script src="/DORCFSRF/pages/script/prevent-back-button.js"></script>
</body>

</html>







