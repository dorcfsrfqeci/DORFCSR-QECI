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
include $_SERVER['DOCUMENT_ROOT'] . '/DORCFSRF/db_connect.php';
include $_SERVER['DOCUMENT_ROOT'] . '/DORCFSRF/api/notification_system.php';
$submitMessage = '';
$student_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'] ?? $_SESSION['username'];

// Get stored profile image
$profile_image = '/DORCFSRF/assets/student_profiles/' . $student_id . '_profile.jpg';
$profile_image_alt = '/DORCFSRF/assets/student_profiles/' . $student_id . '_profile.png';
$profile_image_alt2 = '/DORCFSRF/assets/student_profiles/' . $student_id . '_profile.gif';

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

// Fetch existing student information to pre-populate form
$existing_student_data = array();
$fetch_stmt = $conn->prepare('SELECT student_lastname, student_firstname, student_middlename, student_year, student_course, student_section, student_sem, contact_num, permanent_address, date_of_birth, year_graduated, elementary_school, jhs_school, shs_school, college_community, reason_clearance, scholastic_document, form_submission_image, elem_year, jhs_year, shs_year, college_year FROM student_information_database WHERE student_id = ? LIMIT 1');
$fetch_stmt->bind_param('s', $student_id);
$fetch_stmt->execute();
$fetch_result = $fetch_stmt->get_result();
if ($fetch_result && $fetch_result->num_rows > 0) {
  $existing_student_data = $fetch_result->fetch_assoc();
}
$fetch_stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Validate required fields
  if (
    empty($_POST['student_lastname'] ?? '') || empty($_POST['student_firstname'] ?? '') ||
    empty($_POST['student_year'] ?? '') || empty($_POST['student_course'] ?? '') ||
    empty($_POST['student_section'] ?? '') || empty($_POST['student_sem'] ?? '')
  ) {
    $submitMessage = '<div class="submit-error" style="padding: 16px 20px; margin: 0; border-radius: 8px; background: #fdecea; color: #7a1f1f; font-weight: 600;">Error: Please fill out all required fields.</div>';
  } elseif (empty(trim($student_id))) {
    $submitMessage = '<div class="submit-error" style="padding: 16px 20px; margin: 0; border-radius: 8px; background: #fdecea; color: #7a1f1f; font-weight: 600;">Error: Invalid user session. Please log in again.</div>';
  } else {
    try {
      // Check if student record already exists and validate 3-day rule
      $checkStmt = $conn->prepare("SELECT student_id, DATE(updated_at) as last_submission FROM student_information_database WHERE student_id = ?");
      if (!$checkStmt) {
        throw new Exception("Database error: " . $conn->error);
      }

      $checkStmt->bind_param('s', $student_id);
      $checkStmt->execute();
      $checkResult = $checkStmt->get_result();
      $recordExists = $checkResult->num_rows > 0;
      $lastSubmission = null;

      if ($recordExists) {
        $row = $checkResult->fetch_assoc();
        $lastSubmission = $row['last_submission'];
      }
      $checkStmt->close();

      // Check if 3 days have passed
      $canSubmit = true;
      if ($recordExists && $lastSubmission) {
        $lastSubmissionTime = strtotime($lastSubmission);
        $currentTime = time();
        $daysDifference = floor(($currentTime - $lastSubmissionTime) / (60 * 60 * 24));

        if ($daysDifference < 3) {
          $canSubmit = false;
          $daysRemaining = 3 - $daysDifference;
          $nextSubmissionTime = $lastSubmissionTime + (3 * 24 * 60 * 60);
          $nextSubmissionDate = date('M d, Y', $nextSubmissionTime);
          $submitMessage = '<div class="submit-error" style="padding: 16px 20px; margin: 0; border-radius: 8px; background: #fdecea; color: #7a1f1f; font-weight: 600;">⏳ You can only resubmit after 3 days. Next submission available on <strong>' . $nextSubmissionDate . '</strong> (' . $daysRemaining . ' day(s) remaining).</div>';
        }
      }

      if ($canSubmit) {
        $student_lastname = $conn->real_escape_string($_POST['student_lastname'] ?? '');
        $student_firstname = $conn->real_escape_string($_POST['student_firstname'] ?? '');
        $student_middlename = $conn->real_escape_string($_POST['student_middlename'] ?? '');
        $student_year = $conn->real_escape_string($_POST['student_year'] ?? '');
        $student_course = $conn->real_escape_string($_POST['student_course'] ?? '');
        $student_section = $_POST['student_section'] ?? '';
        if ($student_section === 'Others') {
          $student_section = $_POST['student_section_other'] ?? '';
        }
        $student_section = $conn->real_escape_string($student_section);
        $student_sem = $conn->real_escape_string($_POST['student_sem'] ?? '');
        $raw_contact = $_POST['contact_num'] ?? '';
        if (!empty($raw_contact)) {
          // Remove leading 0 if user still typed it
          if (strpos($raw_contact, '0') === 0) {
            $raw_contact = substr($raw_contact, 1);
          }
          // Prepend +63
          $raw_contact = '+63 ' . trim($raw_contact);
        }
        $contact_num = $conn->real_escape_string($raw_contact);
        $permanent_address = $conn->real_escape_string($_POST['permanent_address'] ?? '');
        $date_of_birth = $conn->real_escape_string($_POST['date_of_birth'] ?? '');
        $year_graduated = $conn->real_escape_string($_POST['year_graduated'] ?? '');
        $elementary_school = $conn->real_escape_string($_POST['elementary_school'] ?? '');
        $jhs_school = $conn->real_escape_string($_POST['jhs_school'] ?? '');
        $shs_school = $conn->real_escape_string($_POST['shs_school'] ?? '');
        $college_community = $conn->real_escape_string($_POST['college_community'] ?? '');
        $elem_year = $conn->real_escape_string($_POST['elem_year'] ?? '');
        $jhs_year = $conn->real_escape_string($_POST['jhs_year'] ?? '');
        $shs_year = $conn->real_escape_string($_POST['shs_year'] ?? '');
        $college_year = $conn->real_escape_string($_POST['college_year'] ?? '');
        $reason_clearance = $conn->real_escape_string($_POST['reason_clearance'] ?? '');
        $scholastic_document = $conn->real_escape_string($_POST['scholastic_document'] ?? '');

        // Handle Image Upload
        $form_submission_image = $existing_student_data['form_submission_image'] ?? '';
        if (isset($_FILES['student_image']) && $_FILES['student_image']['error'] === UPLOAD_ERR_OK) {
          $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/DORCFSRF/assets/form_uploads/';
          if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
          }
          $fileExtension = pathinfo($_FILES['student_image']['name'], PATHINFO_EXTENSION);
          $fileName = $student_id . '_form_image_' . time() . '.' . $fileExtension;
          $uploadPath = $uploadDir . $fileName;

          if (move_uploaded_file($_FILES['student_image']['tmp_name'], $uploadPath)) {
            $form_submission_image = '/DORCFSRF/assets/form_uploads/' . $fileName;
          }
        }

        if (empty($form_submission_image) && empty($existing_student_data['form_submission_image'])) {
          throw new Exception("Thumbnail Image is required.");
        }

        if ($recordExists) {
          // Update existing record
          $stmt = $conn->prepare("UPDATE student_information_database SET student_lastname = ?, student_firstname = ?, student_middlename = ?, student_year = ?, student_course = ?, student_section = ?, student_sem = ?, contact_num = ?, permanent_address = ?, date_of_birth = ?, year_graduated = ?, elementary_school = ?, jhs_school = ?, shs_school = ?, college_community = ?, reason_clearance = ?, scholastic_document = ?, form_submission_image = ?, elem_year = ?, jhs_year = ?, shs_year = ?, college_year = ? WHERE student_id = ?");
          if (!$stmt) {
            throw new Exception("Database prepare error: " . $conn->error);
          }
          $stmt->bind_param('sssssssssssssssssssssss', $student_lastname, $student_firstname, $student_middlename, $student_year, $student_course, $student_section, $student_sem, $contact_num, $permanent_address, $date_of_birth, $year_graduated, $elementary_school, $jhs_school, $shs_school, $college_community, $reason_clearance, $scholastic_document, $form_submission_image, $elem_year, $jhs_year, $shs_year, $college_year, $student_id);
        } else {
          // Insert new record
          $stmt = $conn->prepare("INSERT INTO student_information_database (student_id, student_lastname, student_firstname, student_middlename, student_year, student_course, student_section, student_sem, contact_num, permanent_address, date_of_birth, year_graduated, elementary_school, jhs_school, shs_school, college_community, reason_clearance, scholastic_document, form_submission_image, elem_year, jhs_year, shs_year, college_year) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
          if (!$stmt) {
            throw new Exception("Database prepare error: " . $conn->error);
          }
          $stmt->bind_param('sssssssssssssssssssssss', $student_id, $student_lastname, $student_firstname, $student_middlename, $student_year, $student_course, $student_section, $student_sem, $contact_num, $permanent_address, $date_of_birth, $year_graduated, $elementary_school, $jhs_school, $shs_school, $college_community, $reason_clearance, $scholastic_document, $form_submission_image, $elem_year, $jhs_year, $shs_year, $college_year);
        }

        if ($stmt->execute()) {
          // Create or update clearance record with all department statuses set to "Pending"
          $clearanceCheckStmt = $conn->prepare("SELECT clearance_id FROM student_clearance_database WHERE student_id = ? LIMIT 1");
          $clearanceCheckStmt->bind_param('s', $student_id);
          $clearanceCheckStmt->execute();
          $clearanceResult = $clearanceCheckStmt->get_result();
          $clearanceExists = $clearanceResult->num_rows > 0;
          $clearanceCheckStmt->close();

          if (!$clearanceExists) {
            $clearance_id = 'CLR-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 7));
            // Create new clearance record with all departments set to "Pending"
            $insertClearanceSql = "INSERT INTO student_clearance_database (
                            clearance_id, student_id, unit_stats,
                            student_lastname, student_firstname, student_middlename,
                            student_year, student_course, student_section, school_year, student_address
                        ) VALUES (?, ?, 'Pending', ?, ?, ?, ?, ?, ?, ?, ?)";
            $insertClearanceStmt = $conn->prepare($insertClearanceSql);
            $insertClearanceStmt->bind_param('ssssssssss', $clearance_id, $student_id, $student_lastname, $student_firstname, $student_middlename, $student_year, $student_course, $student_section, $student_year, $permanent_address);
            $insertClearanceStmt->execute();
            $insertClearanceStmt->close();
          } else {
            // Update existing record to reset all department statuses to "Pending"
            $updateClearanceSql = "UPDATE student_clearance_database SET 
                            unit_stats = 'Pending',
                            student_lastname = ?, student_firstname = ?, student_middlename = ?,
                            student_year = ?, student_course = ?, student_section = ?, school_year = ?, student_address = ?
                            WHERE student_id = ?";
            $updateClearanceStmt = $conn->prepare($updateClearanceSql);
            $updateClearanceStmt->bind_param('sssssssss', $student_lastname, $student_firstname, $student_middlename, $student_year, $student_course, $student_section, $student_year, $permanent_address, $student_id);
            $updateClearanceStmt->execute();
            $updateClearanceStmt->close();
          }

          // Create unified notifications for all roles (Admin, Staff, Student)
          try {
            $student_full_name = $student_firstname . ' ' . $student_lastname;
            
            // Use new unified broadcast function that notifies all admins, staff, and student
            createFormSubmissionNotification($conn, $student_id, $student_full_name, 'Student Form Submission');
          } catch (Exception $e) {
            // Notification failed but form was submitted successfully
            // Continue without showing error
          }

          include_once $_SERVER['DOCUMENT_ROOT'] . '/DORCFSRF/api/log_audit.php';

          // Sync submission to requestor_database (non-blocking)
          try {
            // Detect requestor_database schema to decide which columns to write
            $colsRes = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'requestor_database'");
            $cols = [];
            if ($colsRes) {
              while ($c = $colsRes->fetch_assoc()) {
                $cols[] = $c['COLUMN_NAME'];
              }
            }
            $using_new_schema = in_array('student_year', $cols);

            if ($using_new_schema) {
              $syncCheck = $conn->prepare("SELECT requestor_id FROM requestor_database WHERE student_id = ? LIMIT 1");
              if ($syncCheck) {
                $syncCheck->bind_param('s', $student_id);
                $syncCheck->execute();
                $syncRes = $syncCheck->get_result();
                if ($syncRes && $syncRes->num_rows > 0) {
                  $syncUpdate = $conn->prepare("UPDATE requestor_database SET student_lastname = ?, student_firstname = ?, student_middlename = ?, student_year = ?, student_course = ?, student_section = ?, contact_num = ?, permanent_address = ?, request_type = 'FormSubmission', status = 'Pending', updated_at = CURRENT_TIMESTAMP WHERE student_id = ?");
                  if ($syncUpdate) {
                    $syncUpdate->bind_param('sssssssss', $student_lastname, $student_firstname, $student_middlename, $student_year, $student_course, $student_section, $contact_num, $permanent_address, $student_id);
                    $syncUpdate->execute();
                    $syncUpdate->close();
                  }
                } else {
                  $syncInsert = $conn->prepare("INSERT INTO requestor_database (student_id, student_lastname, student_firstname, student_middlename, student_year, student_course, student_section, contact_num, permanent_address, request_type, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'FormSubmission', 'Pending')");
                  if ($syncInsert) {
                    $syncInsert->bind_param('sssssssss', $student_id, $student_lastname, $student_firstname, $student_middlename, $student_year, $student_course, $student_section, $contact_num, $permanent_address);
                    $syncInsert->execute();
                    $syncInsert->close();
                  }
                }
                $syncCheck->close();
              }
            } else {
              // Legacy schema: write type_schdocs and purpose columns from the form
              $syncCheck = $conn->prepare("SELECT request_num FROM requestor_database WHERE student_id = ? LIMIT 1");
              if ($syncCheck) {
                $syncCheck->bind_param('s', $student_id);
                $syncCheck->execute();
                $syncRes = $syncCheck->get_result();
                if ($syncRes && $syncRes->num_rows > 0) {
                  $syncUpdate = $conn->prepare("UPDATE requestor_database SET student_lastname = ?, student_firstname = ?, student_middlename = ?, student_course = ?, last_attended = ?, type_schdocs = ?, purpose = ?, current_stage = 'Pending', date_request = CURDATE() WHERE student_id = ?");
                  if ($syncUpdate) {
                    $syncUpdate->bind_param('ssssssss', $student_lastname, $student_firstname, $student_middlename, $student_course, $student_year, $scholastic_document, $reason_clearance, $student_id);
                    $syncUpdate->execute();
                    $syncUpdate->close();
                  }
                } else {
                  // insert legacy row; generate request_num
                  $request_num = 'REQ-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 10));
                  $or_num_val = '';
                  $syncInsert = $conn->prepare("INSERT INTO requestor_database (request_num, student_id, or_num, student_lastname, student_firstname, student_middlename, student_course, last_attended, type_schdocs, date_request, purpose, current_stage) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, 'Pending')");
                  if ($syncInsert) {
                    $syncInsert->bind_param('sssssssss', $request_num, $student_id, $or_num_val, $student_lastname, $student_firstname, $student_middlename, $student_course, $student_year, $scholastic_document, $reason_clearance);
                    $syncInsert->execute();
                    $syncInsert->close();
                  }
                }
                $syncCheck->close();
              }
            }
          } catch (Exception $syncEx) {
            // Log sync failure but don't interrupt the user flow
            if (function_exists('log_activity')) {
              log_activity($conn, $student_id, $full_name, 'System', 'RequestorSync', 'Failed', 'Requestor sync failed: ' . $syncEx->getMessage(), 'RequestorSync', $student_id);
            }
          }

          log_activity($conn, $student_id, $full_name, 'Student', 'Forms', 'Submit Clearance/Scholastic Form', 'Success', 'Forms', $student_id);

          $submitMessage = '<div class="submit-success" style="padding: 16px 20px; margin: 0; border-radius: 8px; background: #e8f8ed; color: #1f6d2a; font-weight: 600;">✓ Submission saved successfully. Staff have been notified. You can resubmit after 3 days.</div>';
          $submissionSuccess = true;
        } else {
          throw new Exception("Execute error: " . $stmt->error);
        }
        $stmt->close();
      }
    } catch (Exception $e) {
      $submitMessage = '<div class="submit-error" style="padding: 16px 20px; margin: 0; border-radius: 8px; background: #fdecea; color: #7a1f1f; font-weight: 600;">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Form</title>
  <link rel="stylesheet" href="/DORCFSRF/css/dashboard.css" />
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />

</head>

<body>
  <div class="container">
    <aside class="sidebar">
      <img src="/DORCFSRF/assets/QECI LOGO.jpg" alt="qecilogo" />
      <nav>
        <a href="dashboard.php"><i class="bx bxs-dashboard"> </i> Dashboard</a>
        <a href="forms.php" class="active"><i class="bx bx-notepad"></i> Form</a>
        <a href="clearance.php"><i class="bx bxs-graduation"></i> Clearance Status</a>
        <a href="payment.php"><i class="bx bx-coin-stack"></i> Payment Transaction</a>
        <a href="record.php"><i class="bx bx-bar-chart-alt-2"></i> Scholastic Record
          Status</a>
        <a href="completion.php"><i class="bx bxs-calendar"></i> Completion Report</a>
        <p>SETTINGS</p>
        <a href="settings.php"><i class="bx bx-cog"></i> Account Settings</a>
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
              </div>
              <div class="card-actions">
                <button class="action-btn">Profile</button>
                <button class="action-btn">Sign Out</button>
              </div>
              <script src="/DORCFSRF/pages/script/dropdown.js"></script>
            </div>
          </div>
      </header>
      <main class="main-content">
        <div class="form-header">
          <h3 class="breadcrumb2"><b>Home</b> >Scholastic Form</h3>
          <div class="form-stepper">
            <div class="step active" id="stepper-1">
              <span>&bigcirc;</span> Student Clearance Form <span class="arrow">&longrightarrow;</span>
            </div>
            <div class="step" id="stepper-2">
              <span>&bigcirc;</span> Scholastic Record Form
            </div>
          </div>
          <button class="save-draft-btn">Save As Draft</button>
        </div>

        <form class="form-container" method="POST" action="/DORCFSRF/pages/student_dashboard/forms.php" id="studentForm"
          enctype="multipart/form-data" novalidate>
          <div id="messageContainer"
            style="position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 9999; max-width: 90%; min-width: 320px; box-shadow: 0 4px 16px rgba(0,0,0,0.15);">
            <?php echo $submitMessage; ?>
          </div>
          <h2>Student Clearance and Scholastic Record Form <i class='bx bx-info-circle'></i></h2>

          <div id="step-1">
            <div class="form-grid-step1">
              <div class="form-group-upload">
                <label>Thumbnail Image <span class="required">(Required)</span></label>
                <div class="upload-box" id="upload-box-element">
                  <div class="upload-preview-placeholder">
                    <i class='bx bx-image-add'></i>
                    <p>Drag or Browse</p>
                    <span>PNG, JPG, SVG MAX 5MB</span>
                  </div>
                  <div class="upload-preview-container">
                    <?php if (!empty($existing_student_data['form_submission_image'])): ?>
                      <img src="<?php echo htmlspecialchars($existing_student_data['form_submission_image']); ?>"
                        style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;" />
                      <script>document.querySelector('.upload-preview-placeholder').style.display = 'none';</script>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <div class="form-right">
                <div class="form-group span-2">
                  <label>Name <span class="required">(Required)</span></label>
                  <div class="name-fields">
                    <input type="text" id="step2-lastname" name="student_lastname" placeholder="Last Name"
                      value="<?php echo htmlspecialchars($existing_student_data['student_lastname'] ?? ''); ?>"
                      required>
                    <input type="text" id="step2-firstname" name="student_firstname" placeholder="First Name"
                      value="<?php echo htmlspecialchars($existing_student_data['student_firstname'] ?? ''); ?>"
                      required>
                    <input type="text" id="step2-middlename" name="student_middlename" placeholder="Middle Name"
                      value="<?php echo htmlspecialchars($existing_student_data['student_middlename'] ?? ''); ?>">
                  </div>
                </div>
                <div class="form-group span-2">
                  <label style="display: flex; justify-content: space-between; align-items: center;">
                    <span>Permanent Address</span>
                    <button type="button" id="detect-location-btn"
                      style="background: none; border: none; color: #3b82f6; cursor: pointer; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 4px;">
                      <i class='bx bx-current-location'></i> Detect Location
                    </button>
                  </label>
                  <input type="text" id="step2-address" name="permanent_address" placeholder="Enter Permanent Address"
                    value="<?php echo htmlspecialchars($existing_student_data['permanent_address'] ?? ''); ?>">
                  <small style="color: #64748b; font-size: 12px; margin-top: 5px; display: block;">
                    <i class='bx bx-info-circle'></i> If you are not currently at your permanent residence, you can type
                    your address manually.
                  </small>
                </div>
                <div class="form-group span-2">
                  <label>Contact Number</label>
                  <div style="display: flex; align-items: center; gap: 10px;">
                    <span
                      style="background-color: #f1f5f9; border: 1px solid #cbd5e1; padding: 12px 16px; border-radius: 8px; color: #475569; font-weight: 600; font-size: 14px;">+63</span>
                    <input type="text" name="contact_num" placeholder="9XX XXX XXXX" style="flex: 1; margin: 0;" value="<?php
                    $contact = $existing_student_data['contact_num'] ?? '';
                    // Remove +63 or 0 if it exists at the start to prevent duplicates
                    if (strpos($contact, '+63') === 0) {
                      $contact = trim(substr($contact, 3));
                    } elseif (strpos($contact, '0') === 0) {
                      $contact = trim(substr($contact, 1));
                    }
                    echo htmlspecialchars($contact);
                    ?>">
                  </div>
                </div>
                <div class="form-group">
                  <label>Date of Birth</label>
                  <input type="date" name="date_of_birth" placeholder="Enter date of birth"
                    value="<?php echo htmlspecialchars($existing_student_data['date_of_birth'] ?? ''); ?>">
                </div>
                <div class="form-group">
                  <label>Year Graduated</label>
                  <input type="number" name="year_graduated" placeholder="Enter year graduated" min="1900" max="2099"
                    value="<?php echo htmlspecialchars($existing_student_data['year_graduated'] ?? ''); ?>">
                </div>

                <div class="form-group">
                  <label>School Year <span class="required">(Required)</span></label>
                  <input type="text" name="student_year" placeholder="Enter School Year (e.g., 2024-2025)"
                    value="<?php echo htmlspecialchars($existing_student_data['student_year'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                  <label>Course Major <span class="required">(Required)</span></label>
                  <select name="student_course" required>
                    <option value="">Select course major</option>
                    <option value="Bachelor of Science in Computer Science" <?php echo ($existing_student_data['student_course'] ?? '') === 'Bachelor of Science in Computer Science' ? 'selected' : ''; ?>>Bachelor of Science in Computer Science</option>
                    <option value="Bachelor of Science in Business Administration major in FM" <?php echo ($existing_student_data['student_course'] ?? '') === 'Bachelor of Science in Business Administration major in FM' ? 'selected' : ''; ?>>Bachelor of Science in Business Administration major in FM
                    </option>
                    <option value="Bachelor of Science in Business Administration major in MM" <?php echo ($existing_student_data['student_course'] ?? '') === 'Bachelor of Science in Business Administration major in MM' ? 'selected' : ''; ?>>Bachelor of Science in Business Administration major in MM
                    </option>
                    <option value="Bachelor of Science in Business Administration major in HRDM" <?php echo ($existing_student_data['student_course'] ?? '') === 'Bachelor of Science in Business Administration major in HRDM' ? 'selected' : ''; ?>>Bachelor of Science in Business Administration major in HRDM
                    </option>
                    <option value="Bachelor of Secondary Education major in English" <?php echo ($existing_student_data['student_course'] ?? '') === 'Bachelor of Secondary Education major in English' ? 'selected' : ''; ?>>Bachelor of Secondary Education major in English</option>
                    <option value="Bachelor of Secondary Education major in Filipino" <?php echo ($existing_student_data['student_course'] ?? '') === 'Bachelor of Secondary Education major in Filipino' ? 'selected' : ''; ?>>Bachelor of Secondary Education major in Filipino</option>
                    <option value="Bachelor of Secondary Education major in Math" <?php echo ($existing_student_data['student_course'] ?? '') === 'Bachelor of Secondary Education major in Math' ? 'selected' : ''; ?>>Bachelor of Secondary Education major in Math</option>
                    <option value="Bachelor of Elementary Education" <?php echo ($existing_student_data['student_course'] ?? '') === 'Bachelor of Elementary Education' ? 'selected' : ''; ?>>Bachelor of Elementary Education</option>
                    <option value="Bachelor of Science in Criminology" <?php echo ($existing_student_data['student_course'] ?? '') === 'Bachelor of Science in Criminology' ? 'selected' : ''; ?>>Bachelor of Science in Criminology</option>
                  </select>
                </div>
                <div class="form-group">
                  <label>Year & Section <span class="required">(Required)</span></label>
                  <?php
                  $current_section = $existing_student_data['student_section'] ?? '';
                  $predefined_sections = ['1st Year - A', '1st Year - B', '2nd Year - A', '2nd Year - B', '3rd Year - A', '3rd Year - B', '4A', '4B'];
                  $is_other_section = !empty($current_section) && !in_array($current_section, $predefined_sections);
                  ?>
                  <select name="student_section" id="student_section" required onchange="toggleOtherSection()">
                    <option value="">Select Final and Section</option>
                    <option value="1st Year - A" <?php echo $current_section === '1st Year - A' ? 'selected' : ''; ?>>1st
                      Year - A</option>
                    <option value="1st Year - B" <?php echo $current_section === '1st Year - B' ? 'selected' : ''; ?>>1st
                      Year - B</option>
                    <option value="2nd Year - A" <?php echo $current_section === '2nd Year - A' ? 'selected' : ''; ?>>2nd
                      Year - A</option>
                    <option value="2nd Year - B" <?php echo $current_section === '2nd Year - B' ? 'selected' : ''; ?>>2nd
                      Year - B</option>
                    <option value="3rd Year - A" <?php echo $current_section === '3rd Year - A' ? 'selected' : ''; ?>>3rd
                      Year - A</option>
                    <option value="3rd Year - B" <?php echo $current_section === '3rd Year - B' ? 'selected' : ''; ?>>3rd
                      Year - B</option>
                    <option value="4A" <?php echo $current_section === '4A' ? 'selected' : ''; ?>>4A</option>
                    <option value="4B" <?php echo $current_section === '4B' ? 'selected' : ''; ?>>4B</option>
                    <option value="Others" <?php echo $is_other_section ? 'selected' : ''; ?>>Others</option>
                  </select>
                  <input type="text" name="student_section_other" id="student_section_other"
                    placeholder="Enter Year & Section manually"
                    value="<?php echo $is_other_section ? htmlspecialchars($current_section) : ''; ?>"
                    style="margin-top: 10px; display: <?php echo $is_other_section ? 'block' : 'none'; ?>;">

                  <script>
                    function toggleOtherSection() {
                      var select = document.getElementById('student_section');
                      var otherInput = document.getElementById('student_section_other');
                      if (select.value === 'Others') {
                        otherInput.style.display = 'block';
                        otherInput.required = true;
                      } else {
                        otherInput.style.display = 'none';
                        otherInput.required = false;
                      }
                    }
                  </script>
                </div>
                <div class="form-group">
                  <label>Semester <span class="required">(Required)</span></label>
                  <select name="student_sem" required>
                    <option value="">Select Semester</option>
                    <option value="1st Semester" <?php echo ($existing_student_data['student_sem'] ?? '') === '1st Semester' ? 'selected' : ''; ?>>1st Semester</option>
                    <option value="2nd Semester" <?php echo ($existing_student_data['student_sem'] ?? '') === '2nd Semester' ? 'selected' : ''; ?>>2nd Semester</option>
                  </select>
                </div>
              </div>
            </div>
            <div class="form-actions">
              <button type="button" class="btn-continue" id="continue-btn">Continue</button>
            </div>
          </div>

          <div id="step-2" style="display: none;">
            <div class="form-grid-step2">
              <div class="form-group span-2">
                <label>Name <span class="required">(Required)</span></label>
                <div class="name-fields">
                  <input type="text" id="form2-lastname" placeholder="Last Name" readonly
                    value="<?php echo htmlspecialchars($existing_student_data['student_lastname'] ?? ''); ?>">
                  <input type="text" id="form2-firstname" placeholder="First Name" readonly
                    value="<?php echo htmlspecialchars($existing_student_data['student_firstname'] ?? ''); ?>">
                  <input type="text" id="form2-middlename" placeholder="Middle Name" readonly
                    value="<?php echo htmlspecialchars($existing_student_data['student_middlename'] ?? ''); ?>">
                </div>
              </div>
              <div class="form-group span-2">
                <label>Permanent Address</label>
                <input type="text" id="form2-address" placeholder="Enter Permanent Address" readonly
                  value="<?php echo htmlspecialchars($existing_student_data['permanent_address'] ?? ''); ?>">
              </div>

              <div class="form-group span-2">
                <label>Specify the name of the following schools</label>
                <div class="school-grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px;">
                  <!-- Elementary -->
                  <div class="school-input-group">
                    <label for="elem" style="font-size:13px; color:#555;">Elementary School Name</label>
                    <input type="text" id="elem" name="elementary_school" placeholder="Enter school name"
                      value="<?php echo htmlspecialchars($existing_student_data['elementary_school'] ?? ''); ?>">
                  </div>
                  <div class="school-input-group">
                    <label for="elem_year" style="font-size:13px; color:#555;">Year Graduated</label>
                    <input type="number" id="elem_year" name="elem_year" placeholder="Year" min="1900" max="2099"
                      value="<?php echo htmlspecialchars($existing_student_data['elem_year'] ?? ''); ?>">
                  </div>

                  <!-- JHS -->
                  <div class="school-input-group">
                    <label for="jhs" style="font-size:13px; color:#555;">Junior High School Name</label>
                    <input type="text" id="jhs" name="jhs_school" placeholder="Enter school name"
                      value="<?php echo htmlspecialchars($existing_student_data['jhs_school'] ?? ''); ?>">
                  </div>
                  <div class="school-input-group">
                    <label for="jhs_year" style="font-size:13px; color:#555;">Year Graduated</label>
                    <input type="number" id="jhs_year" name="jhs_year" placeholder="Year" min="1900" max="2099"
                      value="<?php echo htmlspecialchars($existing_student_data['jhs_year'] ?? ''); ?>">
                  </div>

                  <!-- SHS -->
                  <div class="school-input-group">
                    <label for="shs" style="font-size:13px; color:#555;">Senior High School Name</label>
                    <input type="text" id="shs" name="shs_school" placeholder="Enter school name"
                      value="<?php echo htmlspecialchars($existing_student_data['shs_school'] ?? ''); ?>">
                  </div>
                  <div class="school-input-group">
                    <label for="shs_year" style="font-size:13px; color:#555;">Year Graduated</label>
                    <input type="number" id="shs_year" name="shs_year" placeholder="Year" min="1900" max="2099"
                      value="<?php echo htmlspecialchars($existing_student_data['shs_year'] ?? ''); ?>">
                  </div>

                  <!-- College -->
                  <div class="school-input-group">
                    <label for="college" style="font-size:13px; color:#555;">College/University Name</label>
                    <input type="text" id="college" name="college_community" placeholder="Enter college name"
                      value="<?php echo htmlspecialchars($existing_student_data['college_community'] ?? ''); ?>">
                  </div>
                  <div class="school-input-group">
                    <label for="college_year" style="font-size:13px; color:#555;">Year Graduated/Last Attended</label>
                    <input type="number" id="college_year" name="college_year" placeholder="Year" min="1900" max="2099"
                      value="<?php echo htmlspecialchars($existing_student_data['college_year'] ?? ''); ?>">
                  </div>
                </div>
              </div>

              <div class="form-group span-2">
                <label>Scholastic Document/s</label>
                <select name="scholastic_document">
                  <option value="">Select type of document applying for</option>
                  <option value="Transcript of Records (TOR)" <?php echo ($existing_student_data['scholastic_document'] ?? '') === 'Transcript of Records (TOR)' ? 'selected' : ''; ?>>Transcript of Records (TOR)</option>
                  <option value="Diploma" <?php echo ($existing_student_data['scholastic_document'] ?? '') === 'Diploma' ? 'selected' : ''; ?>>Diploma</option>
                  <option value="Certificate of Good Moral" <?php echo ($existing_student_data['scholastic_document'] ?? '') === 'Certificate of Good Moral' ? 'selected' : ''; ?>>Certificate of Good Moral</option>
                </select>
              </div>
              <div class="form-group span-2">
                <label>Reason for Clearance</label>
                <input type="text" name="reason_clearance" id="reason_clearance"
                  placeholder="Enter reason for clearance"
                  value="<?php echo htmlspecialchars($existing_student_data['reason_clearance'] ?? ''); ?>">
              </div>
            </div>
            <div class="form-actions">
              <button type="button" class="btn-back" id="back-btn">Back</button>
              <button type="submit" class="btn-submit">Submit</button>
            </div>
          </div>

        </form>
      </main>

    </div>
  </div>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const form = document.getElementById('studentForm');
      const step1 = document.getElementById('step-1');
      const step2 = document.getElementById('step-2');
      const continueBtn = document.getElementById('continue-btn');
      const backBtn = document.getElementById('back-btn');
      const stepper1 = document.getElementById('stepper-1');
      const stepper2 = document.getElementById('stepper-2');
      const stepper1Arrow = stepper1.querySelector('.arrow');
      let submitInProgress = false;

      // Function to update the stepper appearance
      function updateStepper(step) {
        if (step === 1) {
          stepper1.classList.add('active');
          stepper2.classList.remove('active');
          stepper1Arrow.style.display = 'inline';
          stepper2.innerHTML = '<span>&bigcirc;</span> Scholastic Record Form';
        } else if (step === 2) {
          stepper1.classList.remove('active');
          stepper2.classList.add('active');
          stepper1Arrow.style.display = 'none';
          stepper2.innerHTML = '<span>&check;</span> Scholastic Record Form';
        }
      }

      // --- VALIDATION FUNCTION ---
      function validateStep1() {
        let isValid = true;
        const requiredFields = step1.querySelectorAll('[required]');

        requiredFields.forEach(field => {
          if (field.value.trim() === '' || (field.tagName === 'SELECT' && field.value === '')) {
            isValid = false;
            field.style.border = '2px solid #e74c3c';
            field.style.backgroundColor = '#fadbd8';
          } else {
            field.style.border = '';
            field.style.backgroundColor = '';
          }
        });

        const imageInput = document.querySelector('input[name="student_image"]');
        const uploadBox = document.getElementById('upload-box-element');
        const hasExistingImage = <?php echo !empty($existing_student_data['form_submission_image']) ? 'true' : 'false'; ?>;

        if (!hasExistingImage && (!imageInput || !imageInput.files || imageInput.files.length === 0)) {
          isValid = false;
          uploadBox.style.border = '2px dashed #e74c3c';
          uploadBox.style.backgroundColor = '#fadbd8';
        } else {
          uploadBox.style.border = '';
          uploadBox.style.backgroundColor = '';
        }

        return isValid;
      }
      // --- END OF VALIDATION FUNCTION ---

      // Initial state setup
      updateStepper(1);

      // Save Draft Functionality - USER SPECIFIC
      const currentStudentId = '<?php echo strcasecmp($_SESSION['user_id'], '') === 0 ? '' : $_SESSION['user_id']; ?>';
      const draftStorageKey = 'studentFormDraft_' + currentStudentId;

      const draftBtn = document.querySelector('.save-draft-btn');
      if (draftBtn) {
        draftBtn.addEventListener('click', function (e) {
          e.preventDefault();
          const formData = {
            userId: currentStudentId,
            timestamp: Date.now()
          };
          const inputs = form.querySelectorAll('input, select, textarea');
          inputs.forEach(input => {
            if (input.name) {
              formData[input.name] = input.value;
            }
          });
          localStorage.setItem(draftStorageKey, JSON.stringify(formData));
          alert('✓ Form saved as draft! You can continue later.');
        });
      }

      // Load Draft on Page Load - WITH USER VERIFICATION
      function loadDraft() {
        const draft = localStorage.getItem(draftStorageKey);

        // Clear old drafts from different users
        clearOldUserDrafts();

        if (draft) {
          try {
            const data = JSON.parse(draft);

            // Verify that the draft belongs to the current user
            if (data.userId && data.userId === currentStudentId) {
              Object.keys(data).forEach(key => {
                if (key !== 'userId' && key !== 'timestamp') {
                  const field = form.querySelector(`[name="${key}"]`);
                  if (field) {
                    field.value = data[key];
                  }
                }
              });
            } else {
              // Draft belongs to a different user - remove it
              localStorage.removeItem(draftStorageKey);
            }
          } catch (e) {
            console.error('Error parsing draft:', e);
            localStorage.removeItem(draftStorageKey);
          }
        }
      }

      // Function to clear drafts from other users
      function clearOldUserDrafts() {
        for (let i = 0; i < localStorage.length; i++) {
          const key = localStorage.key(i);
          if (key && key.startsWith('studentFormDraft_') && key !== draftStorageKey) {
            try {
              const data = JSON.parse(localStorage.getItem(key));
              if (data.userId && data.userId !== currentStudentId) {
                localStorage.removeItem(key);
              }
            } catch (e) {
              localStorage.removeItem(key);
            }
          }
        }
      }

      loadDraft();

      continueBtn.addEventListener('click', function () {
        if (validateStep1()) {
          const lastName = step1.querySelector('input[name="student_lastname"]').value;
          const firstName = step1.querySelector('input[name="student_firstname"]').value;
          const middleName = step1.querySelector('input[name="student_middlename"]').value;
          const address = step1.querySelector('input[name="permanent_address"]').value;

          document.getElementById('form2-lastname').value = lastName;
          document.getElementById('form2-firstname').value = firstName;
          document.getElementById('form2-middlename').value = middleName;
          document.getElementById('form2-address').value = address;

          step1.style.display = 'none';
          step2.style.display = 'block';
          updateStepper(2);
          window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
          alert('⚠️ Please fill out all required fields marked with (Required) before continuing.');
          window.scrollTo({ top: 0, behavior: 'smooth' });
        }
      });

      backBtn.addEventListener('click', function () {
        step1.style.display = 'block';
        step2.style.display = 'none';
        updateStepper(1);
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });

      // Handle form submission - SIMPLE DIRECT SUBMIT
      form.addEventListener('submit', function (e) {
        e.preventDefault();

        if (submitInProgress) {
          return;
        }

        const submitBtn = form.querySelector('.btn-submit');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Submitting...';
        submitInProgress = true;

        window.scrollTo({ top: 0, behavior: 'smooth' });

        // Simple direct form submission
        setTimeout(() => {
          form.method = 'POST';
          form.action = '/DORCFSRF/pages/student_dashboard/forms.php';
          form.submit();
        }, 300);
      });

      const messageContainer = document.getElementById('messageContainer');
      if (messageContainer && messageContainer.innerHTML.trim() !== '') {
        window.scrollTo({ top: 0, behavior: 'smooth' });
        messageContainer.style.animation = 'pulse 0.5s ease-in-out';
      }

      // Geolocation - Permanent Address
      const detectLocationBtn = document.getElementById('detect-location-btn');
      const addressInput = document.getElementById('step2-address');

      if (detectLocationBtn && addressInput) {
        detectLocationBtn.addEventListener('click', function () {
          if (!navigator.geolocation) {
            alert('Geolocation is not supported by your browser.');
            return;
          }

          detectLocationBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Detecting...';
          detectLocationBtn.disabled = true;

          navigator.geolocation.getCurrentPosition(
            function (position) {
              const lat = position.coords.latitude;
              const lon = position.coords.longitude;

              // Use Nominatim API for reverse geocoding (Free OpenStreetMap API)
              fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&zoom=18&addressdetails=1`)
                .then(response => response.json())
                .then(data => {
                  if (data && data.display_name) {
                    addressInput.value = data.display_name;
                    addressInput.dispatchEvent(new Event('input')); // Trigger for drafts
                  } else {
                    alert('Could not determine address from your location.');
                  }
                })
                .catch(error => {
                  console.error('Error fetching address:', error);
                  alert('Error fetching address data.');
                })
                .finally(() => {
                  detectLocationBtn.innerHTML = '<i class="bx bx-current-location"></i> Detect Location';
                  detectLocationBtn.disabled = false;
                });
            },
            function (error) {
              console.error('Geolocation error:', error);
              alert('Unable to retrieve your location. Please ensure location permissions are granted.');
              detectLocationBtn.innerHTML = '<i class="bx bx-current-location"></i> Detect Location';
              detectLocationBtn.disabled = false;
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
          );
        });
      }
    });

    // Image upload handler
    document.addEventListener('DOMContentLoaded', () => {
      const uploadBox = document.querySelector('.upload-box');
      if (!uploadBox) return;

      const previewContainer = uploadBox.querySelector('.upload-preview-container');
      const placeholder = uploadBox.querySelector('.upload-preview-placeholder');

      const hiddenInput = document.createElement('input');
      hiddenInput.type = 'file';
      hiddenInput.accept = 'image/png, image/jpeg, image/jpg, image/svg+xml';
      hiddenInput.style.display = 'none';
      hiddenInput.name = 'student_image';

      uploadBox.appendChild(hiddenInput);

      const showPreview = (src) => {
        previewContainer.innerHTML = `<img src="${src}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;" />`;
        if (placeholder) placeholder.style.display = 'none';
      };

      uploadBox.addEventListener('click', () => {
        hiddenInput.click();
      });

      hiddenInput.addEventListener('change', (event) => {
        const file = event.target.files[0];

        if (!file) {
          return;
        }

        if (!file.type.startsWith('image/')) {
          alert('Please select an image file.');
          return;
        }

        if (file.size > 5 * 1024 * 1024) {
          alert('File size must be less than 5MB.');
          return;
        }

        const reader = new FileReader();
        reader.onload = (event) => {
          showPreview(event.target.result);
        };
        reader.readAsDataURL(file);
      });

      uploadBox.addEventListener('dragover', (event) => {
        event.preventDefault();
        uploadBox.classList.add('upload-box-dragover');
      });

      uploadBox.addEventListener('dragleave', () => {
        uploadBox.classList.remove('upload-box-dragover');
      });

      uploadBox.addEventListener('drop', (event) => {
        event.preventDefault();
        uploadBox.classList.remove('upload-box-dragover');
        const file = event.dataTransfer.files[0];
        if (file) {
          hiddenInput.files = event.dataTransfer.files;
          hiddenInput.dispatchEvent(new Event('change'));
        }
      });
    });

    const style = document.createElement('style');
    style.innerHTML = `
      @keyframes pulse {
        0% { opacity: 1; transform: translateX(-50%) scale(1); }
        50% { opacity: 0.9; transform: translateX(-50%) scale(1.02); }
        100% { opacity: 1; transform: translateX(-50%) scale(1); }
      }
      #messageContainer {
        transition: all 0.3s ease;
        animation: pulse 0.6s ease-in-out;
      }
      #messageContainer:empty {
        display: none;
      }
      .btn-submit:disabled {
        opacity: 0.7;
        cursor: not-allowed;
      }
      @media (max-width: 768px) {
        #messageContainer {
          left: 10px !important;
          right: 10px !important;
          transform: none !important;
          max-width: none !important;
        }
      }
    `;
    document.head.appendChild(style);
  </script>
  <script src="/DORCFSRF/pages/script/notifications.js"></script>
  <script src="/DORCFSRF/pages/script/prevent-back-button.js"></script>
</body>

</html>