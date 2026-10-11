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
$student_id = $_SESSION['user_id'];

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

// Database connection
include __DIR__ . '/../../db_connect.php';

// Fetch student information from database
$student_info = null;
$stmt = $conn->prepare('SELECT student_firstname, student_middlename, student_lastname, student_year, student_course, student_section, student_sem, reason_clearance, year_graduated, scholastic_status, scholastic_last_updated, system_remarks FROM student_information_database WHERE student_id = ? LIMIT 1');
$stmt->bind_param('s', $student_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
  $student_info = $result->fetch_assoc();
} else {
  // If no student info found, create a basic record
  $insert_stmt = $conn->prepare('INSERT INTO student_information_database (student_id) VALUES (?)');
  $insert_stmt->bind_param('s', $student_id);
  @$insert_stmt->execute(); // @ to suppress warning if already exists
  $insert_stmt->close();
}
$stmt->close();

// Get student scholastic record submission status
$record_submitted = false;
$stmt = $conn->prepare('SELECT 1 FROM student_information_database WHERE student_id = ? LIMIT 1');
$stmt->bind_param('s', $student_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
  $record_submitted = true;
}
$stmt->close();

// Get student clearance status for record
$total_departments = 13;
$approved_count = 0;
$pending_count = 13; // default
$in_process_count = 0;

$stmt = $conn->prepare('SELECT status FROM clearance_approvals_log WHERE student_id = ?');
$stmt->bind_param('s', $student_id);
$stmt->execute();
$result = $stmt->get_result();
$clearance_count = $result->num_rows;

if ($result) {
  $approved_count = 0;
  $in_process_count = 0;
  $log_entries = 0;
  while ($row = $result->fetch_assoc()) {
    $status = $row['status'];
    $log_entries++;
    if (strcasecmp($status, 'Approved') === 0 || strcasecmp($status, 'Cleared') === 0) {
      $approved_count++;
    } elseif (strcasecmp($status, 'In Process') === 0) {
      $in_process_count++;
    }
  }
  $pending_count = 13 - $approved_count - $in_process_count;
  if ($pending_count < 0) $pending_count = 0;
}
$stmt->close();

// Calculate percentages
$percent_cleared = round(($approved_count / $total_departments) * 100, 0);
$percent_pending = round(($pending_count / $total_departments) * 100, 0);
$percent_inprocess = round(($in_process_count / $total_departments) * 100, 0);

// Determine student type based on year and graduation status
$student_type = 'Student'; // Default
$year_graduated = $student_info['year_graduated'] ?? null;
$student_year = $student_info['student_year'] ?? null;

if ($year_graduated) {
  $student_type = 'Alumni';
} elseif ($student_year && ($student_year === '4th Year' || $student_year === 'Fourth Year' || $student_year === '4')) {
  $student_type = 'Graduating Student';
} else {
  $student_type = 'Student';
}

// Format student full name
$student_full_name = '';
if ($student_info) {
  $student_full_name = trim($student_info['student_lastname'] . ', ' . $student_info['student_firstname'] . ' ' . $student_info['student_middlename']);
}
$student_program = $student_info['student_course'] ?? 'N/A';
$student_section = $student_info['student_section'] ?? 'N/A';
$last_semester = $student_info['student_sem'] ?? 'N/A';

$scholastic_status = $student_info['scholastic_status'] ?? 'Pending';
$scholastic_last_updated = $student_info['scholastic_last_updated'] ?? date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Record</title>

  <link rel="stylesheet" href="/DORCFSRF/css/dashboard.css" />
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
</head>

<body>
  <div class="container">
    <div class="container">
      <aside class="sidebar">
        <img src="/DORCFSRF/assets/QECI LOGO.jpg" alt="qecilogo" />
        <nav>
          <a href="dashboard.php"><i class="bx bxs-dashboard"> </i> Dashboard</a>
          <a href="forms.php"><i class="bx bx-notepad"></i> Form</a>
          <a href="clearance.php"><i class="bx bxs-graduation"></i> Clearance Status</a>
          <a href="payment.php"><i class="bx bx-coin-stack"></i> Payment Transaction</a>
          <a href="record.php" class="active"><i class="bx bx-bar-chart-alt-2"></i> Scholastic Record Status</a>
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
          </div>
        </header>
        <main class="main-content">
          <div class="background-overlay"></div>

          <h2 class="breadcrumb"><b>Home</b> > Scholastic Record Status</h2>
          <h1>Scholastic Record Request</h1>


          <div class="record-layout">

            <div class="main-column">

              <div class="card">
                <div class="info-grid">
                  <div>
                    <label>Request Number:</label>
                    <span><?php echo 'REQ-' . htmlspecialchars($student_id) . '-' . date('Y'); ?></span>
                  </div>
                  <div>
                    <label>Date of Request:</label>
                    <span><?php echo date('m/d/Y'); ?></span>
                  </div>
                  <div>
                    <label>Type:</label>
                    <span><?php echo $student_info ? 'Scholastic Record Request' : 'Pending'; ?></span>
                  </div>
                  <div>
                    <label>Purpose:</label>
                    <span><?php echo $student_info && $student_info['reason_clearance'] ? htmlspecialchars($student_info['reason_clearance']) : 'Not specified'; ?></span>
                  </div>
                </div>
              </div>

              <div class="card">
                <h3>Student Information</h3>
                <div class="info-grid">
                  <div>
                    <label>Name:</label>
                    <span><?php echo $student_full_name ? htmlspecialchars($student_full_name) : 'Not available'; ?></span>
                  </div>
                  <div>
                    <label>Student ID:</label>
                    <span><?php echo htmlspecialchars($student_id); ?></span>
                  </div>
                  <div>
                    <label>Program:</label>
                    <span><?php echo htmlspecialchars($student_program); ?></span>
                  </div>
                  <div>
                    <label>Last Attended:</label>
                    <span><?php echo $last_semester ? htmlspecialchars($last_semester) : 'Not available'; ?></span>
                  </div>
                </div>
              </div>

              <div class="card">
                <h3>Document Status</h3>
                <div class="progress-section">
                  <label>Transcript of Records</label>
                  <span><?php echo $percent_cleared; ?>%</span>
                </div>
                <div class="progress-bar-container">
                  <div class="progress-bar" style="width: <?php echo $percent_cleared; ?>%;"></div>
                </div>
              </div>

              <div class="card">
                <h3>Overall Request Status</h3>
                <div class="overall-status">
                  <p>
                    <?php if ($scholastic_status === 'Ready for Release'): ?>
                      <i class="bx bx-check-circle" style="color: #16a34a;"></i> <strong>Current Stage:</strong> Ready for Release
                    <?php elseif ($scholastic_status === 'In Progress'): ?>
                      <i class="bx bx-loader" style="color: #2563eb;"></i> <strong>Current Stage:</strong> In Progress
                    <?php else: ?>
                      <i class="bx bx-time-five" style="color: #ca8a04;"></i> <strong>Current Stage:</strong> Pending
                    <?php endif; ?>
                  </p>
                  <p><i class='bx bx-calendar'></i> <strong>Date of Release:</strong> <?php echo date('m/d/Y', strtotime($scholastic_last_updated)); ?></p>
                  <p><i class='bx bx-info-circle'></i> <strong>Remarks:</strong>
                    <?php 
                      $sys_rmk = $student_info['system_remarks'] ?? '';
                      if (!empty($sys_rmk)) {
                        echo htmlspecialchars($sys_rmk);
                      } elseif ($scholastic_status === 'Ready for Release') {
                        echo 'Please claim your requested document at school during office hours';
                      } elseif ($scholastic_status === 'In Progress') {
                        echo 'Your request is currently being processed by the administrator.';
                      } else {
                        echo 'Please complete your form submission and clear all requirements.';
                      }
                    ?>
                  </p>
                </div>
              </div>

            </div>
            <div class="side-column">

              <div class="card widget">
                <div class="widget-header">
                  <h3>Clearance Status</h3>
                  <button class="kebab-menu">&#8942;</button>
                </div>
                <div class="chart-legend">
                  <div class="legend-item">
                    <span class="dot" style="background-color: #f39c12;"></span>
                    Pending
                    <span class="percent"><?php echo $percent_pending; ?>%</span>
                  </div>
                  <div class="legend-item">
                    <span class="dot" style="background-color: #3498db;"></span>
                    Cleared
                    <span class="percent"><?php echo $percent_cleared; ?>%</span>
                  </div>
                  <div class="legend-item">
                    <span class="dot" style="background-color: #1abc9c;"></span>
                    In Process
                    <span class="percent"><?php echo $percent_inprocess; ?>%</span>
                  </div>
                </div>
              </div>

              <!-- "Types of Student Requested" widget removed -->
            </div>
          </div>
        </main>
      </div>
      <script src="/DORCFSRF/pages/script/notifications.js"></script>
      <script src="/DORCFSRF/pages/script/prevent-back-button.js"></script>
</body>

</html>
