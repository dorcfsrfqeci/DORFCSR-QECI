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
$staff_id = $_SESSION['user_id'];
$unreadCount = getUnreadNotificationCount($conn, $staff_id);
$notifications = getNotifications($conn, $staff_id, 5);

$studentId = $_GET['student_id'] ?? null;
$studentData = null;
$profileImage = null;

if ($studentId) {
  // Fetch complete student data from database
  $query = "SELECT student_id, student_firstname, student_middlename, student_lastname, student_course, student_section, student_sem, student_year, year_graduated, system_remarks, CONCAT(LOWER(REPLACE(CONCAT(student_firstname, student_lastname), ' ', '')), '@gmail.com') as email FROM student_information_database WHERE student_id = ?";
  $stmt = $conn->prepare($query);
  $stmt->bind_param("s", $studentId);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result && $result->num_rows > 0) {
    $studentData = $result->fetch_assoc();

    // Check for profile image
    $profile_image = '/DORCFSRF/assets/student_profiles/' . $studentId . '_profile.jpg';
    $profile_image_alt = '/DORCFSRF/assets/student_profiles/' . $studentId . '_profile.png';
    $profile_image_alt2 = '/DORCFSRF/assets/student_profiles/' . $studentId . '_profile.gif';

    if (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image)) {
      $profileImage = $profile_image;
    } elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image_alt)) {
      $profileImage = $profile_image_alt;
    } elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image_alt2)) {
      $profileImage = $profile_image_alt2;
    }
  }
  $stmt->close();
}

$studentName = $studentData ? htmlspecialchars($studentData['student_lastname'] . ', ' . $studentData['student_firstname'] . ' ' . $studentData['student_middlename']) : 'N/A';
$studentEmail = $studentData ? htmlspecialchars($studentData['email']) : 'N/A';
$studentIdDisplay = $studentData ? htmlspecialchars($studentData['student_id']) : 'N/A';
$studentProgram = $studentData ? htmlspecialchars($studentData['student_course']) : 'N/A';
$studentSemester = $studentData ? htmlspecialchars($studentData['student_sem']) : 'N/A';

// Get student-level clearance and request progress data
$clearanceRow = null;
$paymentCompleted = 0;
$docsUploaded = 0;
$progressSteps = [];
$percent_cleared = 0;
$percent_pending = 0;
$percent_inprocess = 0;

if ($studentId) {
  $clearanceStmt = $conn->prepare('SELECT unit_stats FROM student_clearance_database WHERE student_id = ? ORDER BY clearance_id DESC LIMIT 1');
  $clearanceStmt->bind_param('s', $studentId);
  $clearanceStmt->execute();
  $clearanceResult = $clearanceStmt->get_result();

  if ($clearanceResult && $clearanceResult->num_rows > 0) {
    $clearanceRow = $clearanceResult->fetch_assoc();
  }
  $clearanceStmt->close();

  // Get department approvals from log
  $logStmt = $conn->prepare("SELECT department_field, status FROM clearance_approvals_log WHERE student_id = ? AND (status = 'Approved' OR status = 'Cleared')");
  $logStmt->bind_param('s', $studentId);
  $logStmt->execute();
  $logRes = $logStmt->get_result();
  $approvedDeps = [];
  while($logRow = $logRes->fetch_assoc()) {
      $approvedDeps[strtolower($logRow['department_field'])] = true;
  }
  $logStmt->close();

  $paymentStmt = $conn->prepare('SELECT COUNT(*) AS completed_count FROM payment_record_database WHERE student_id = ?');
  $paymentStmt->bind_param('s', $studentId);
  $paymentStmt->execute();
  $paymentResult = $paymentStmt->get_result();
  if ($paymentResult && $paymentRow = $paymentResult->fetch_assoc()) {
    $paymentCompleted = intval($paymentRow['completed_count']);
  }
  $paymentStmt->close();

  $docsStmt = $conn->prepare('SELECT COUNT(*) AS uploaded_count FROM clearance_document_uploads WHERE student_id = ?');
  $docsStmt->bind_param('s', $studentId);
  $docsStmt->execute();
  $docsResult = $docsStmt->get_result();
  if ($docsResult && $docsRow = $docsResult->fetch_assoc()) {
    $docsUploaded = intval($docsRow['uploaded_count']);
  }
  $docsStmt->close();

  $submissionDone = $clearanceRow && isset($clearanceRow['unit_stats']) && trim($clearanceRow['unit_stats']) !== '' && strtolower(trim($clearanceRow['unit_stats'])) !== 'not submitted' && strtolower(trim($clearanceRow['unit_stats'])) !== 'pending';
  $recordsApproved = isset($approvedDeps['records_officer_status']);
  $labApproved = isset($approvedDeps['computer_lab_status']);
  $librarianApproved = isset($approvedDeps['librarian_status']);
  $financeApproved = isset($approvedDeps['finance_officer_status']);
  $cbrcApproved = isset($approvedDeps['cbrc_status']);
  $overallCleared = count($approvedDeps) >= 13;

  $progressSteps = [
    'Requirements Submitted' => $submissionDone,
    'Documents Uploaded' => $docsUploaded > 0,
    'Records Officer Approved' => $recordsApproved,
    'Computer Lab Approved' => $labApproved,
    'Librarian Approved' => $librarianApproved,
    'Finance Officer Approved' => $financeApproved,
    'CBRC Approved' => $cbrcApproved,
    'Payment Completed' => $paymentCompleted > 0,
  ];

  $taskCount = count($progressSteps);
  $completedTasks = count(array_filter($progressSteps));
  $percent_cleared = $taskCount > 0 ? round(($completedTasks / $taskCount) * 100, 1) : 0;
  if ($overallCleared) {
    $percent_cleared = 100;
  }
  $percent_pending = $taskCount > 0 ? round(((($taskCount - $completedTasks) / $taskCount) * 100), 1) : 0;
  $percent_inprocess = ($percent_cleared > 0 && $percent_cleared < 100) ? $percent_pending : 0;
}

// Determine student type based on year and graduation status
$student_type = 'Student'; // Default
$year_graduated = $studentData['year_graduated'] ?? null;
$student_year = $studentData['student_year'] ?? null;

if ($year_graduated) {
  $student_type = 'Alumni';
} elseif ($student_year && ($student_year === '4th Year' || $student_year === 'Fourth Year' || $student_year === '4')) {
  $student_type = 'Graduating Student';
} else {
  $student_type = 'Student';
}

// Get staff profile image for header
$staff_id = $_SESSION['user_id'] ?? null;
$staff_profile_image = null;
if ($staff_id) {
  $profile_image = '/DORCFSRF/assets/staff_profiles/' . $staff_id . '_profile.jpg';
  $profile_image_alt = '/DORCFSRF/assets/staff_profiles/' . $staff_id . '_profile.png';
  $profile_image_alt2 = '/DORCFSRF/assets/staff_profiles/' . $staff_id . '_profile.gif';

  if (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image)) {
    $staff_profile_image = $profile_image;
  } elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image_alt)) {
    $staff_profile_image = $profile_image_alt;
  } elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image_alt2)) {
    $staff_profile_image = $profile_image_alt2;
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Scholastic Record - <?php echo $studentName; ?></title>

  <link rel="stylesheet" href="/DORCFSRF/css/dashboard.css" />
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>
  <div class="container">
    <div class="container">
      <aside class="sidebar">
        <img src="/DORCFSRF/assets/QECI LOGO.jpg" alt="qecilogo" />
        <nav>
          <a href="/DORCFSRF/pages/staff_dashboard/staff_dasboard.php"><i class="bx bxs-dashboard"> </i> Dashboard</a>
          <a href="/DORCFSRF/pages/staff_dashboard/staff_form.php"><i class="bx bx-notepad"></i> Form</a>
          <a href="/DORCFSRF/pages/staff_dashboard/staff_clearance.php"><i class="bx bxs-graduation"></i> Clearance Status</a>
          <a href="/DORCFSRF/pages/staff_dashboard/staff_payment_transaction.php"><i class="bx bx-coin-stack"></i> Payment
            Transaction</a>
          <a href="/DORCFSRF/pages/staff_dashboard/staff_scholastic_record.php" class="active"><i
              class="bx bx-bar-chart-alt-2"></i> Scholastic
            Record Status</a>
          <a href="/DORCFSRF/pages/staff_dashboard/staff_completion_report.php"><i class="bx bxs-calendar"></i> Completion
            Report</a>
          <p>SETTINGS</p>
          <a href="/DORCFSRF/pages/staff_dashboard/staff_account_settings.php"><i class="bx bx-cog"></i> Account Settings</a>
        </nav>
      </aside>
      <div class="content-wrapper">
        <header class="header">
          <div class="header-left">
            <div class="search-bar">
              <svg viewBox="0 0 24 24">
                <path d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 1 0 3 10.5a7.5 7.5 0 0 0 13.65 6.15z" stroke="#888"
                  stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none" />
              </svg>
            </div>
          </div>
          <div class="user-icon" style="display: flex; gap: 15px; align-items: center;">
            <i class="bx bx-bell" id="notificationBell" title="Notifications" style="font-size: 24px; cursor: pointer;"></i>
            <a href="/DORCFSRF/pages/click_button/aboutus.php" title="About"><i class="bx bx-globe bx-flip-vertical"></i></a>
            <div class="user-menu-container">
              <div class="profile-icon-wrapper" id="profile-toggle" title="Profile">
                <?php if ($staff_profile_image): ?>
                  <img src="<?php echo htmlspecialchars($staff_profile_image); ?>?t=<?php echo time(); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;" />
                <?php else: ?>
                  <i class="bx bxs-user-circle"></i>
                <?php endif; ?>
              </div>
              <div class="profile-card" id="profile-dropdown">
                <div class="card-header-bg"></div>
                <div class="profile-img" id="profileImageContainer">
                  <?php if ($staff_profile_image): ?>
                    <img src="<?php echo htmlspecialchars($staff_profile_image); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;" />
                  <?php else: ?>
                    <i class="bx bxs-user-circle" style="font-size: 70px; color: #fff;"></i>
                  <?php endif; ?>
                </div>
                <div class="profile-info">
                  <h3><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></h3>
                  <p>STAFF</p>
                </div>
                <div class="card-actions">
                  <a href="/DORCFSRF/pages/staff_dashboard/staff_account_settings.php" class="action-btn action-btn-primary">Profile</a>
                  <a href="/DORCFSRF/pages/logout.php" class="action-btn action-btn-secondary">Sign Out</a>
                </div>
                <script src="/DORCFSRF/pages/script/dropdown.js"></script>
              </div>
            </div>
          </div>
        </header>

        <!-- Notification Dropdown -->
            <div class="notification-dropdown" id="notif-dropdown"
                style="display: none; position: absolute; top: 60px; right: 80px; width: 350px; background: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 1000; max-height: 400px; overflow-y: auto;">
                <div style="padding: 15px 20px; border-bottom: 1px solid #eee; background: #f8f9fa; border-radius: 8px 8px 0 0;">
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
                                        <?php echo htmlspecialchars($notif['student_name']); ?></p>
                                    <p style="margin: 0 0 5px 0; color: #666; font-size: 13px;">
                                        <?php echo htmlspecialchars($notif['message']); ?></p>
                                    <p style="margin: 0; color: #999; font-size: 12px;">
                                        <?php echo date('M d, Y g:ia', strtotime($notif['created_at'])); ?></p>
                                </div>
                                <?php if (!$notif['is_read']): ?>
                                    <div style="width: 10px; height: 10px; background: #007bff; border-radius: 50%; margin-left: 10px; margin-top: 5px;"></div>
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
        </header>
        <main class="main-content">
          <div class="background-overlay"></div>
          <div style="margin-bottom: 14px;">
            <button onclick="window.location.href='/DORCFSRF/pages/staff_dashboard/staff_scholastic_record.php'"
              style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 16px; border: 1px solid #ccc; border-radius: 8px; background: #fff; color: #333; cursor: pointer; font-weight: 600;">
              <i class="fas fa-arrow-left"></i> Back to Scholastic Record
            </button>
          </div>

          <h2 class="breadcrumb" style="color: white;"><b>Home</b> > Scholastic Record Status</h2>
          <h1 style="color: white;">Scholastic Record Request</h1>


          <div class="record-layout">

            <div class="main-column">

              <div class="card">
                <div class="info-grid">
                  <div>
                    <label>Request Number:</label>
                    <span>#<?php echo date('Y') . '-' . str_pad($studentId ?? 0, 5, '0', STR_PAD_LEFT); ?></span>
                  </div>
                  <div>
                    <label>Date of Request:</label>
                    <span><?php echo date('m/d/Y'); ?></span>
                  </div>
                  <div>
                    <label>Type:</label>
                    <span>Transcript of Records</span>
                  </div>
                  <div>
                    <label>Purpose:</label>
                    <span>Enrollment</span>
                  </div>
                </div>
              </div>

              <div class="card">
                <h3>Student Information</h3>
                <div class="info-grid">
                  <div>
                    <label>Name:</label>
                    <span><?php echo $studentName; ?></span>
                  </div>
                  <div>
                    <label>Student ID:</label>
                    <span><?php echo $studentIdDisplay; ?></span>
                  </div>
                  <div>
                    <label>Program:</label>
                    <span><?php echo $studentProgram; ?></span>
                  </div>
                  <div>
                    <label>Last Attended:</label>
                    <span><?php echo $studentSemester; ?></span>
                  </div>
                </div>
              </div>

              <div class="card">
                <h3>Document Status</h3>
                <div class="progress-section">
                  <label>Overall Completion</label>
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
                    <?php 
                    if ($percent_cleared == 100) {
                        echo '<i class="bx bx-check-circle" style="color:#2ecc71"></i> <strong>Current Stage:</strong> Ready for Release';
                    } elseif ($percent_cleared > 0) {
                        echo '<i class="bx bx-loader" style="color:#3498db"></i> <strong>Current Stage:</strong> In Progress';
                    } else {
                        echo '<i class="bx bx-time-five" style="color:#f39c12"></i> <strong>Current Stage:</strong> Pending';
                    }
                    ?>
                  </p>
                  <p><i class='bx bx-calendar'></i> <strong>Date of Release:</strong> <?php echo date('m/d/Y'); ?></p>
                  <p><i class='bx bx-info-circle'></i> <strong>Remarks:</strong>
                    <?php echo !empty($studentData['system_remarks']) ? htmlspecialchars($studentData['system_remarks']) : (($submissionDone && $percent_cleared >= 100) ? 'Please claim your requested document at school during office hours' : 'Please complete your form submission and clear all requirements'); ?>
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
    </div>
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
</html>
