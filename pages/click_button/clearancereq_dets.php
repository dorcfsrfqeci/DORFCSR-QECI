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
$staff_id = $_GET['staff_id'] ?? null;
$clearance_id = $_GET['clearance_id'] ?? null;
$from = $_GET['from'] ?? 'clearance'; // Default to clearance, can be 'dashboard' or 'clearance'

// Function to get staff profile image
function getStaffProfileImage($staff_id) {
    $profile_image = '/DORCFSRF/assets/staff_profiles/' . $staff_id . '_profile.jpg';
    $profile_image_alt = '/DORCFSRF/assets/staff_profiles/' . $staff_id . '_profile.png';
    $profile_image_alt2 = '/DORCFSRF/assets/staff_profiles/' . $staff_id . '_profile.gif';
    
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image)) {
        return $profile_image;
    } elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image_alt)) {
        return $profile_image_alt;
    } elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image_alt2)) {
        return $profile_image_alt2;
    }
    return null;
}

// Fetch staff member information if staff_id is provided
$staff_info = null;
if ($staff_id) {
    $staffSql = "SELECT user_id, full_name, email FROM user_database WHERE user_id = ? AND role = 'Staff'";
    $staffStmt = $conn->prepare($staffSql);
    $staffStmt->bind_param('s', $staff_id);
    $staffStmt->execute();
    $staffResult = $staffStmt->get_result();
    $staff_info = $staffResult->fetch_assoc();
    $staffStmt->close();
}

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

// Map item to database field
$item = $_GET['item'] ?? 'records';
$department_field = '';
switch($item) {
    case 'records': $department_field = 'records_officer_status'; break;
    case 'computer_lab': $department_field = 'computer_lab_status'; break;
    case 'librarian': $department_field = 'librarian_status'; break;
    case 'finance': $department_field = 'finance_officer_status'; break;
    case 'cbrc': $department_field = 'cbrc_status'; break;
}

// Fetch real clearance status
$clearance_data = null;
if ($clearance_id) {
    $stmt = $conn->prepare("SELECT * FROM student_clearance_database WHERE clearance_id = ? AND student_id = ?");
    $stmt->bind_param('ss', $clearance_id, $user_id);
    $stmt->execute();
    $clearance_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} else {
    $stmt = $conn->prepare("SELECT * FROM student_clearance_database WHERE student_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param('s', $user_id);
    $stmt->execute();
    $clearance_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$current_status = 'Pending';
$updated_date = '-';
$db_clearance_id = 'N/A';
if ($clearance_data) {
    $current_status = $clearance_data[$department_field] ?? 'Pending';
    if ($current_status === 'Approved') $current_status = 'Cleared';
    $updated_date = date('M j, Y', strtotime($clearance_data['updated_at']));
    $db_clearance_id = $clearance_data['clearance_id'];
}

// Fetch uploaded documents for this staff/department
$uploaded_docs = [];
if ($staff_id) {
    $stmt = $conn->prepare("SELECT original_name, uploaded_at, file_path FROM clearance_document_uploads WHERE student_id = ? AND staff_id = ? ORDER BY uploaded_at DESC");
    $stmt->bind_param('ss', $user_id, $staff_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()) {
        $uploaded_docs[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clearance Requirement Details</title>

    <link rel="stylesheet" href="/DORCFSRF/css/dashboard.css" />
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="/DORCFSRF/css/clearance-details.css" />
</head>

<body>
    <div class="container">
        <aside class="sidebar">
            <img src="/DORCFSRF/assets/QECI LOGO.jpg" alt="qecilogo" />
            <nav>
                <a href="/DORCFSRF/pages/student_dashboard/dashboard.php"><i class="bx bxs-dashboard"> </i> Dashboard</a>
                <a href="/DORCFSRF/pages/student_dashboard/forms.php"><i class="bx bx-notepad"></i> Form</a>
                <a href="/DORCFSRF/pages/student_dashboard/clearance.php"><i class="bx bxs-graduation"></i> Clearance Status</a>
                <a href="/DORCFSRF/pages/student_dashboard/payment.php"><i class="bx bx-coin-stack"></i> Payment Transaction</a>
                <a href="/DORCFSRF/pages/student_dashboard/completion.php"><i class="bx bxs-calendar"></i> Completion Report</a>
                <p>SETTINGS</p>
                <a href="/DORCFSRF/pages/student_dashboard/settings.php"><i class="bx bx-cog"></i> Account Settings</a>
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
                    <div class="clearance-details-overlay" style="display: flex; justify-content: center; align-items: center; min-height: calc(100vh - 140px); padding: 2rem 1rem;">
                        <div class="clearance-details-modal" style="margin: auto;">
                            <div class="modal-header" style="position: relative; padding: 1.5rem 1rem 1rem 1rem;">
                                <h2 style="margin: 0;">Clearance Requirement Details</h2>
                                <button class="close-button" style="position: absolute; top: 1rem; right: 1rem; background: none; border: none; font-size: 24px; cursor: pointer; color: #333;" onclick="window.location.href='/DORCFSRF/pages/student_dashboard/clearance.php'"><i class="fas fa-times"></i></button>
                            </div>
                            <div class="modal-body">
                                <div class="info-card">
                                    <div class="card-title-icon">
                                        <i class="bx bxs-user-detail"></i>
                                        <h3>Clearing Officer Information</h3>
                                    </div>
                                    <div class="info-grid">
                                        <div class="info-item">
                                            <span>Officer Name:</span>
                                            <strong id="officer-name"><?php echo htmlspecialchars($staff_info['full_name'] ?? 'N/A'); ?></strong>
                                        </div>
                                        <div class="info-item">
                                            <span>Email Id:</span>
                                            <strong id="officer-email"><?php echo htmlspecialchars($staff_info['email'] ?? 'N/A'); ?></strong>
                                        </div>
                                        <div class="info-item">
                                            <span>Unit Concerned:</span>
                                            <strong id="unit-concerned">N/A</strong>
                                        </div>
                                        <div class="info-item">
                                            <span>Status:</span>
                                            <?php $badge_class = strtolower(str_replace(' ', '-', $current_status)); ?>
                                            <strong id="status-text" class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($current_status); ?></strong>
                                        </div>
                                         <div class="info-item">
                                            <span> Clearance ID</span>
                                            <strong id="clearance-id"><?php echo htmlspecialchars($db_clearance_id); ?></strong>
                                        </div>
                                        <?php if ($staff_info): ?>
                                        <div class="info-item" style="grid-column: 1 / -1; margin-top: 10px; padding-top: 10px; border-top: 1px solid #eee;">
                                            <span>Staff Profile:</span>
                                            <div style="display: flex; align-items: center; gap: 10px; margin-top: 8px;">
                                                <?php 
                                                    $staff_profile = getStaffProfileImage($staff_info['user_id']);
                                                    if ($staff_profile):
                                                ?>
                                                    <img src="<?php echo htmlspecialchars($staff_profile); ?>" alt="<?php echo htmlspecialchars($staff_info['full_name']); ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;" />
                                                <?php else: ?>
                                                    <i class="bx bxs-user-circle" style="font-size: 40px;"></i>
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($staff_info['full_name']); ?></strong><br>
                                                    <small style="color: #666;">User ID: <?php echo htmlspecialchars($staff_info['user_id']); ?></small>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="info-card">
                                    <div class="card-title-icon">
                                        <i class="bx bxs-file-doc"></i>
                                        <h3>Requirements (Uploaded Documents)</h3>
                                    </div>
                                    <div class="requirements-table">
                                        <div class="table-header2">
                                            <span>Documents</span>
                                            <span>Status</span>
                                            <span>Updated</span>
                                        </div>
                                        <?php if (count($uploaded_docs) > 0): ?>
                                            <?php foreach ($uploaded_docs as $doc): ?>
                                                <div class="table-row2">
                                                    <span>
                                                        <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" style="text-decoration: none; color: #1565c0;">
                                                            <?php echo htmlspecialchars($doc['original_name']); ?>
                                                        </a>
                                                    </span>
                                                    <span class="status-submitted" style="color: #2e7d32; font-weight: bold; font-size: 12px; background: #e8f5e9; padding: 4px 8px; border-radius: 4px;">SUBMITTED</span>
                                                    <span style="color: #666; font-size: 14px;"><?php echo date('M j, Y g:i A', strtotime($doc['uploaded_at'])); ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="table-row2" style="justify-content: center; color: #888;">
                                                <span>No documents submitted yet.</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                                                <div class="info-card remarks-card" style="margin-top: 20px;">
                                    <div class="card-title-icon">
                                        <i class="fas fa-exclamation-circle" style="color: #d32f2f; font-size: 24px;"></i>
                                        <h3 style="color: #d32f2f;">Remarks from Officer</h3>
                                    </div>
                                    <div class="remarks-content" style="padding: 15px; background: #ffebee; border-left: 4px solid #d32f2f; border-radius: 4px; margin-top: 15px;">
                                        <p id="remarks-text" style="margin: 0; color: #b71c1c; font-weight: 500;"><?php echo htmlspecialchars($clearance_data['remarks'] ?? 'No remarks provided.'); ?></p>
                                    </div>
                                </div>

                                <div class="upload-section" id="main-container">
                                    <p>Upload Required Documents (JPG, PNG, PDF, DOCX)</p>

                                    <div class="drop-zone" id="drop-zone"
                                        style="border: 2px dashed #ccc; padding: 20px; text-align: center; cursor: pointer;">
                                        <i class="bx bx-cloud-upload" style="font-size: 40px;"></i>
                                        <p>Click or Drop files here</p>
                                        <input type="file" id="file-input" hidden multiple
                                            accept=".jpg,.jpeg,.png,.pdf,.docx">
                                    </div>

                                    <div id="file-list" class="file-list" style="margin-top: 20px;"></div>

                                    <div id="view-modal" class="modal" onclick="this.style.display='none'"
                                        style="display:none; position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000; justify-content:center; align-items:center;">
                                        <span class="close-modal"
                                            style="position:absolute; top:20px; right:30px; color:white; font-size:40px; cursor:pointer;">&times;</span>
                                        <img id="modal-img" src="" alt="Full View" style="width: 80%; height: 80%;border-radius: 10px; ">
                                    </div>
                                </div>

                                <div class="modal-footer" style="margin-top: 20px; display: flex; gap: 10px;">
                                    <a href="/DORCFSRF/pages/student_dashboard/clearance.php">
                                        <button class="cancel-btn">Cancel</button>
                                    </a>
                                    <button id="final-submit-btn" class="submit-btn">Submit Documents</button>
                                </div>
                            </div>
                        </div>
                </main>
            </div>
        </div>
        <script>
                const fileInput = document.getElementById('file-input');
                const fileList = document.getElementById('file-list');
                const dropZone = document.getElementById('drop-zone');
                const submitBtn = document.getElementById('final-submit-btn');
                const modal = document.getElementById('view-modal');
                const modalImg = document.getElementById('modal-img');

                let uploadedFiles = []; // Keeps track of files in memory

                // Click to trigger file selection
                dropZone.onclick = () => {
                    if (!submitBtn.disabled) fileInput.click();
                };

                const allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'docx'];

                fileInput.addEventListener('change', function () {
                    const files = Array.from(this.files);
                    files.forEach(file => {
                        const fileName = file.name.toLowerCase();
                        const fileExt = fileName.split('.').pop();
                        
                        if (allowedExtensions.includes(fileExt)) {
                            // Check if file is already added to prevent duplicates
                            if (!uploadedFiles.some(f => f.name === file.name && f.size === file.size)) {
                                uploadedFiles.push(file);
                                renderFile(file);
                            }
                        } else {
                            alert('Invalid file type: ' + file.name + '\nOnly JPG, PNG, PDF, and DOCX files are allowed.');
                        }
                    });
                    // Reset input so the same file can be selected again if it was removed
                    this.value = '';
                });

                // Drag and drop functionality
                dropZone.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    if (!submitBtn.disabled) {
                        dropZone.style.backgroundColor = 'rgba(0, 0, 0, 0.05)';
                        dropZone.style.borderColor = '#007bff';
                    }
                });

                dropZone.addEventListener('dragleave', (e) => {
                    e.preventDefault();
                    dropZone.style.backgroundColor = '';
                    dropZone.style.borderColor = '#ccc';
                });

                dropZone.addEventListener('drop', (e) => {
                    e.preventDefault();
                    dropZone.style.backgroundColor = '';
                    dropZone.style.borderColor = '#ccc';
                    
                    if (submitBtn.disabled) return;

                    if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                        const files = Array.from(e.dataTransfer.files);
                        files.forEach(file => {
                            const fileName = file.name.toLowerCase();
                            const fileExt = fileName.split('.').pop();
                            
                            if (allowedExtensions.includes(fileExt)) {
                                if (!uploadedFiles.some(f => f.name === file.name && f.size === file.size)) {
                                    uploadedFiles.push(file);
                                    renderFile(file);
                                }
                            } else {
                                alert('Invalid file type: ' + file.name + '\nOnly JPG, PNG, PDF, and DOCX files are allowed.');
                            }
                        });
                    }
                });

                function renderFile(file) {
                    const fileId = Math.random().toString(36).substr(2, 9);
                    const item = document.createElement('div');
                    item.className = 'file-item';
                    item.setAttribute('data-id', fileId);
                    item.style = "display: flex; align-items: center; justify-content: space-between; padding: 10px; border: 1px solid #eee; margin-bottom: 5px; border-radius: 5px; background: #fdfdfd;";

                    const isImage = file.type.startsWith('image/');
                    const fileURL = URL.createObjectURL(file);

                    item.innerHTML = `
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class='bx ${isImage ? 'bx-image' : 'bx-file'}'></i>
                    <span style="font-size: 14px;">${file.name}</span>
                </div>
                <div class="actions">
                    <button type="button" class="view-btn" style="cursor:pointer; padding: 2px 8px; background:#007bff; color:white; border:none; border-radius:3px;">View</button>
                    <button type="button" class="remove-btn" style="cursor:pointer; padding: 2px 8px; background:#dc3545; color:white; border:none; border-radius:3px; margin-left:5px;">&times;</button>
                </div>
            `;

                    item.querySelector('.view-btn').onclick = (e) => {
                        e.stopPropagation();
                        if (isImage) {
                            modal.style.display = "flex";
                            modalImg.src = fileURL;
                        } else {
                            window.open(fileURL, '_blank'); // Opens PDF/Docs in new tab
                        }
                    };

                
                    item.querySelector('.remove-btn').onclick = (e) => {
                        e.stopPropagation();
                        uploadedFiles = uploadedFiles.filter(f => f !== file);
                        item.remove();
                    };

                    fileList.appendChild(item);
                }

            
                submitBtn.onclick = () => {
                    if (uploadedFiles.length === 0) {
                        alert("Please upload at least one document.");
                        return;
                    }

                    if (confirm("Are you sure? Once submitted, you cannot edit your uploads.")) {
                        submitBtn.disabled = true;
                        submitBtn.textContent = "Submitting...";

                        // Create FormData to send files
                        const formData = new FormData();
                        uploadedFiles.forEach(file => {
                            formData.append('files[]', file);
                        });
                        
                        // Add metadata
                        formData.append('staff_id', new URLSearchParams(window.location.search).get('staff_id') || '');
                        formData.append('clearance_id', new URLSearchParams(window.location.search).get('clearance_id') || '');

                        console.log('Submitting files:', uploadedFiles);
                        console.log('Staff ID:', new URLSearchParams(window.location.search).get('staff_id'));
                        console.log('Clearance ID:', new URLSearchParams(window.location.search).get('clearance_id'));

                        // Submit files to server
                        fetch('/DORCFSRF/api/submit_clearance_documents.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => {
                            console.log('Response status:', response.status);
                            return response.json();
                        })
                        .then(data => {
                            console.log('Response data:', data);
                            if (data.success) {
                                alert("Documents submitted successfully!");
                                
                                // Disable upload zone
                                dropZone.style.display = "none";
                                
                                // Hide remove buttons
                                document.querySelectorAll('.remove-btn').forEach(btn => btn.remove());
                                document.querySelectorAll('.view-btn').forEach(btn => btn.remove());
                                
                                // Redirect after 2 seconds
                                setTimeout(() => {
                                    window.location.href = "/DORCFSRF/pages/student_dashboard/clearance.php";
                                }, 2000);
                            } else {
                                alert("Error: " + (data.message || "Failed to submit documents"));
                                submitBtn.disabled = false;
                                submitBtn.textContent = "Submit Documents";
                            }
                        })
                        .catch(error => {
                            console.error('Fetch error:', error);
                            alert("Error submitting files. Please try again. Check console for details.");
                            submitBtn.disabled = false;
                            submitBtn.textContent = "Submit Documents";
                        });
                    }
                };
        </script>
        <script src="/DORCFSRF/pages/script/notifications.js"></script>
        <script>
            (function() {
                const params = new URLSearchParams(window.location.search);
                const item = params.get('item') || 'records';
                const items = {
                    records: { unit: 'Records Officer' },
                    computer_lab: { unit: 'Computer Laboratory' },
                    librarian: { unit: 'Librarian' },
                    finance: { unit: 'Finance Officer' },
                    cbrc: { unit: 'Student Services Coordinator' }
                };

                const data = items[item] || items.records;
                document.getElementById('unit-concerned').textContent = data.unit;
                
            })();
        </script>

</body>

</html>









