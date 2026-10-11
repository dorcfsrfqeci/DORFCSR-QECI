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
$full_name = $_SESSION['full_name'] ?? $_SESSION['username'];

// Database connection
include __DIR__ . '/../../db_connect.php';

// Get student info from database
$user_id = $_SESSION['user_id'];
$student_info = array(
    'name' => $full_name,
    'contact_num' => '',
    'email' => '',
    'course' => '',
    'year' => '',
    'gender' => '',
    'username' => $_SESSION['username'],
    'role' => $_SESSION['role'] ?? 'Student'
);

// Query student information from database
$stmt = $conn->prepare('SELECT full_name, email FROM user_database WHERE user_id = ?');
$stmt->bind_param('s', $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $student_info['name'] = $row['full_name'];
    $student_info['email'] = $row['email'];
}
$stmt->close();

// Query additional student information
$stmt = $conn->prepare('SELECT student_course, student_year, contact_num FROM student_information_database WHERE student_id = ? LIMIT 1');
$stmt->bind_param('s', $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $student_info['course'] = $row['student_course'];
    $student_info['year'] = $row['student_year'];
    $student_info['contact_num'] = $row['contact_num'];
}
$stmt->close();

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profileImage']) && !isset($_POST['action'])) {
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/DORCFSRF/assets/student_profiles/';

    // Create directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $file = $_FILES['profileImage'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_ext = array('jpg', 'jpeg', 'png', 'gif');

    if (in_array($file_ext, $allowed_ext) && $file['size'] <= 5000000) {
        $new_filename = $user_id . '_profile.' . $file_ext;
        $target_path = $upload_dir . $new_filename;

        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            // Save to session/database
            $_SESSION['profile_image'] = '/DORCFSRF/assets/student_profiles/' . $new_filename;
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
    $contact_num = $conn->real_escape_string($_POST['contact_num'] ?? '');
    $course = $conn->real_escape_string($_POST['course'] ?? '');
    $year = $conn->real_escape_string($_POST['year'] ?? '');

    // Handle image upload if present
    if (isset($_FILES['profileImage']) && $_FILES['profileImage']['size'] > 0) {
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/DORCFSRF/assets/student_profiles/';

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
                $old_file = $upload_dir . $user_id . '_profile.' . $format;
                if (file_exists($old_file)) {
                    unlink($old_file);
                }
            }

            $new_filename = $user_id . '_profile.' . $file_ext;
            $target_path = $upload_dir . $new_filename;

            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                $_SESSION['profile_image'] = '/DORCFSRF/assets/student_profiles/' . $new_filename . '?t=' . time();
            }
        }
    }

    // Update user_database with email
    $stmt = $conn->prepare('UPDATE user_database SET email = ? WHERE user_id = ?');
    $stmt->bind_param('ss', $email, $user_id);
    $stmt->execute();
    $stmt->close();

    // Update student_information_database with contact_num, course, year
    $stmt = $conn->prepare('UPDATE student_information_database SET contact_num = ?, student_course = ?, student_year = ? WHERE student_id = ?');
    $stmt->bind_param('ssss', $contact_num, $course, $year, $user_id);
    $stmt->execute();
    $stmt->close();

    echo 'success';
    exit;
}

// Get stored profile image
$profile_image = '/DORCFSRF/assets/student_profiles/' . $user_id . '_profile.jpg';
$profile_image_alt = '/DORCFSRF/assets/student_profiles/' . $user_id . '_profile.png';
$profile_image_alt2 = '/DORCFSRF/assets/student_profiles/' . $user_id . '_profile.gif';

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
    <title>Account Settings</title>

    <link rel="stylesheet" href="/DORCFSRF/css/dashboard.css" />
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
</head>

<body>
    <div class="container">
        <aside class="sidebar">
            <img src="/DORCFSRF/assets/QECI LOGO.jpg" alt="qecilogo" />
            <nav>
                <a href="dashboard.php"><i class="bx bxs-dashboard"> </i> Dashboard</a>
                <a href="forms.php"><i class="bx bx-notepad"></i> Form</a>
                <a href="clearance.php"><i class="bx bxs-graduation"></i> Clearance Status</a>
                <a href="payment.php"><i class="bx bx-coin-stack"></i> Payment Transaction</a>
                <a href="record.php"><i class="bx bx-bar-chart-alt-2"></i> Scholastic Record
                    Status</a>
                <a href="completion.php"><i class="bx bxs-calendar"></i> Completion Report</a>
                <p>SETTINGS</p>
                <a href="settings.php" class="active"><i class="bx bx-cog"></i> Account
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
                        <div class="profile-icon-wrapper" id="profile-toggle" title="Profile">
                            <?php if ($profile_image_url): ?>
                                <img src="<?php echo htmlspecialchars($profile_image_url); ?>" alt="Profile"
                                    style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;" />
                            <?php else: ?>
                                <i class="bx bxs-user-circle"></i>
                            <?php endif; ?>
                        </div>
                        <div class="profile-card" id="profile-dropdown">
                            <div class="card-header-bg"></div>
                            <div class="profile-img" id="profileImageContainer">
                                <?php if ($profile_image_url): ?>
                                    <img src="<?php echo htmlspecialchars($profile_image_url); ?>" alt="Profile"
                                        style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;" />
                                <?php else: ?>
                                    <i class="bx bxs-user-circle" style="font-size: 70px; color: #fff;"></i>
                                <?php endif; ?>
                            </div>
                            <div class="profile-info">
                                <h3><?php echo htmlspecialchars($full_name); ?></h3>
                                <p>STUDENT</p>
                                <p class="profile-id"><?php echo htmlspecialchars($_SESSION['user_id'] ?? ''); ?></p>
                            </div>
                            <div class="card-actions">
                                <a href="settings.php" class="action-btn action-btn-primary">Profile</a>
                                <a href="/DORCFSRF/pages/logout.php" class="action-btn action-btn-secondary">Sign Out</a>
                            </div>
                            <script src="/DORCFSRF/pages/script/dropdown.js"></script>
                        </div>
                    </div>
            </header>

            <main class="main-content">
                <div class="background-overlay"></div>
                <h2 class="breadcrumb">Home > Account Settings</h2>
                <div class="account-settings-container">
                    <div class="breadcrumbs">


                    </div>

                    <section class="account-settings-card">
                        <h3 class="section-title">BASIC INFO</h3>

                        <form id="imageUploadForm" method="POST" enctype="multipart/form-data" style="display: none;">
                            <input type="file" id="profileImageInput" name="profileImage" accept="image/*"
                                style="display:none;">
                        </form>

                        <div class="info-group profile-upload-section">
                            <div class="profile-image-box" id="settingsProfileBox"
                                style="cursor: pointer; position: relative;">
                                <?php if ($profile_image_url): ?>
                                    <img id="settingsProfilePreview" alt="Profile Preview"
                                        src="<?php echo htmlspecialchars($profile_image_url); ?>"
                                        style="width:100%; height:100%; object-fit:cover; border-radius:12px; position:absolute; inset:0;" />
                                <?php else: ?>
                                    <div id="settingsProfilePlaceholder" style="text-align: center;">
                                        <i class='bx bx-image-add' id="imageAddIcon"></i>
                                        <p id="settingsUploadHint">Click to upload image</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="upload-label">
                                Profile Image
                            </div>
                        </div>
                        <?php if (isset($success_msg)): ?>
                            <div
                                style="padding: 10px; background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; border-radius: 4px; margin-bottom: 15px;">
                                <?php echo $success_msg; ?>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($error_msg)): ?>
                            <div
                                style="padding: 10px; background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; border-radius: 4px; margin-bottom: 15px;">
                                <?php echo $error_msg; ?>
                            </div>
                        <?php endif; ?>

                        <div class="info-group">
                            <label for="name">Name:</label>
                            <input type="text" id="name" value="<?php echo htmlspecialchars($student_info['name']); ?>"
                                disabled>
                        </div>

                        <div class="info-group">
                            <label for="email">Email Address:</label>
                            <input type="email" id="email"
                                value="<?php echo htmlspecialchars($student_info['email']); ?>" disabled>
                        </div>

                        <div class="info-group">
                            <label for="contact">Contact Number:</label>
                            <input type="text" id="contact"
                                value="<?php echo htmlspecialchars($student_info['contact_num']); ?>" disabled>
                        </div>

                        <div class="info-group">
                            <label for="course">Course:</label>
                            <input type="text" id="course"
                                value="<?php echo htmlspecialchars($student_info['course']); ?>" disabled>
                        </div>

                        <div class="info-group">
                            <label for="year">Year:</label>
                            <input type="text" id="year" value="<?php echo htmlspecialchars($student_info['year']); ?>"
                                disabled>
                        </div>

                        <h3 class="section-title account-info-title">ACCOUNT INFO</h3>

                        <div class="info-group">
                            <label for="username">Username:</label>
                            <input type="text" id="username"
                                value="<?php echo htmlspecialchars($student_info['username']); ?>" disabled>
                        </div>

                        <div class="info-group">
                            <label for="role">Role:</label>
                            <input type="text" id="role" value="<?php echo htmlspecialchars($student_info['role']); ?>"
                                disabled>
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
                        <a href="/DORCFSRF/pages/click_button/guide.php">
                            <span>Guide to Set Up Your Account</span>
                        </a>
                    </div>
                </div>
            </main>
        </div>
        <script>
            // Handle profile image upload
            const profileImageBox = document.getElementById('settingsProfileBox');
            const profileImageInput = document.getElementById('profileImageInput');
            const settingsProfilePreview = document.getElementById('settingsProfilePreview');
            const settingsProfilePlaceholder = document.getElementById('settingsProfilePlaceholder');
            const imageAddIcon = document.getElementById('imageAddIcon');
            const settingsUploadHint = document.getElementById('settingsUploadHint');

            let imageChanged = false;
            let newImageFile = null;
            let isEditMode = false;

            // File input change handler
            if (profileImageInput) {
                profileImageInput.addEventListener('change', function (e) {
                    const file = e.target.files[0];
                    if (file) {
                        newImageFile = file;
                        imageChanged = true;

                        const reader = new FileReader();
                        reader.onload = function (event) {
                            const imageData = event.target.result;

                            // Remove placeholder if exists
                            if (settingsProfilePlaceholder) {
                                settingsProfilePlaceholder.style.display = 'none';
                            }

                            // Create or update image element
                            if (!settingsProfilePreview) {
                                const img = document.createElement('img');
                                img.id = 'settingsProfilePreview';
                                img.alt = 'Profile Preview';
                                img.style.cssText = 'width:100%; height:100%; object-fit:cover; border-radius:12px;';
                                profileImageBox.appendChild(img);
                            } else {
                                if (settingsProfilePreview.style.display === 'none') {
                                    settingsProfilePreview.style.display = 'block';
                                }
                            }

                            // Set image source
                            const img = document.getElementById('settingsProfilePreview');
                            if (img) {
                                img.src = imageData;
                            }
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }

            // Page load - initialize profile image display
            window.addEventListener('DOMContentLoaded', function () {
                // Initialize profile box as disabled
                if (profileImageBox) {
                    profileImageBox.style.opacity = '0.6';

                    // Set up click handler
                    profileImageBox.addEventListener('click', () => {
                        if (isEditMode && profileImageInput) {
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
                const contactInput = document.getElementById('contact');
                const courseInput = document.getElementById('course');
                const yearInput = document.getElementById('year');

                editBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    isEditMode = true;

                    // Enable inputs
                    emailInput.disabled = false;
                    contactInput.disabled = false;
                    courseInput.disabled = false;
                    yearInput.disabled = false;

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

                cancelBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    isEditMode = false;
                    imageChanged = false;
                    newImageFile = null;

                    // Disable inputs
                    emailInput.disabled = true;
                    contactInput.disabled = true;
                    courseInput.disabled = true;
                    yearInput.disabled = true;

                    // Disable profile image upload
                    if (profileImageBox) {
                        profileImageBox.style.opacity = '0.6';
                        profileImageBox.style.cursor = 'default';
                    }

                    // Reload values from database
                    emailInput.value = '<?php echo htmlspecialchars($student_info['email']); ?>';
                    contactInput.value = '<?php echo htmlspecialchars($student_info['contact_num']); ?>';
                    courseInput.value = '<?php echo htmlspecialchars($student_info['course']); ?>';
                    yearInput.value = '<?php echo htmlspecialchars($student_info['year']); ?>';

                    // Reset image to original
                    const profileImageUrl = '<?php echo $profile_image_url ? htmlspecialchars($profile_image_url) : ''; ?>';
                    const img = document.getElementById('settingsProfilePreview');
                    if (profileImageUrl && img) {
                        img.src = profileImageUrl;
                        img.style.display = 'block';
                        if (settingsProfilePlaceholder) settingsProfilePlaceholder.style.display = 'none';
                    } else if (img) {
                        img.style.display = 'none';
                        if (settingsProfilePlaceholder) settingsProfilePlaceholder.style.display = 'block';
                    }

                    // Reset image input
                    profileImageInput.value = '';

                    // Show/hide buttons
                    editBtn.style.display = 'inline-block';
                    deleteBtn.style.display = 'inline-block';
                    saveBtn.style.display = 'none';
                    cancelBtn.style.display = 'none';
                });

                saveBtn.addEventListener('click', function (e) {
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
                    formData.append('contact_num', contactInput.value);
                    formData.append('course', courseInput.value);
                    formData.append('year', yearInput.value);

                    // Add image if changed
                    if (imageChanged && newImageFile) {
                        console.log('Adding image file:', newImageFile.name, 'Size:', newImageFile.size);
                        formData.append('profileImage', newImageFile);
                    }

                    console.log('Sending form data with action:', 'update_profile', 'imageChanged:', imageChanged);

                    fetch('/DORCFSRF/pages/student_dashboard/settings.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => {
                            console.log('Response status:', response.status);
                            return response.text();
                        })
                        .then(data => {
                            console.log('Response data:', data);
                            if (data.trim() === 'success' || data.includes('success')) {
                                alert('Profile updated successfully!');

                                // Reload page after short delay to show updated image
                                setTimeout(() => {
                                    window.location.reload();
                                }, 500);
                            } else {
                                alert('Error updating profile. Response: ' + data);
                                console.error('Unexpected response:', data);
                            }
                        })
                        .catch(error => {
                            console.error('Fetch error:', error);
                            alert('Error updating profile: ' + error.message);
                        });
                });

                deleteBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
                        window.location.href = '/DORCFSRF/pages/click_button/delete.php';
                    }
                });
            });
        </script>
        <script src="/DORCFSRF/pages/script/dropdown.js"></script>
        <script src="/DORCFSRF/pages/script/notifications.js"></script>
        <script src="/DORCFSRF/pages/script/prevent-back-button.js"></script>
</body>

</html>
