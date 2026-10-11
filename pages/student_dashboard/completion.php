<?php
ini_set('session.gc_maxlifetime', 31536000);
session_set_cookie_params(31536000);
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Check if staff or admin is accessing from their completion reports
$from_staff = (isset($_GET['from']) && ($_GET['from'] === 'staff_completion_report' || $_GET['from'] === 'admin_completion_report')) ? true : false;
$target_student_id = null;

if ($from_staff && isset($_GET['student_id'])) {
  // Staff/Admin accessing a student's record
  $role = $_SESSION['role'] ?? '';
  if (!isset($_SESSION['user_id']) || ($role !== 'Staff' && $role !== 'Admin')) {
    header('Location: /DORCFSRF/pages/login_form/index.php');
    exit;
  }
  $target_student_id = $_GET['student_id'];
} else {
  // Student accessing their own record
  if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Student') {
    header('Location: /DORCFSRF/pages/login_form/index.php');
    exit;
  }
  $target_student_id = $_SESSION['user_id'];
}

$back_url = '/DORCFSRF/pages/staff_dashboard/staff_completion_report.php';
if ($from_staff && ($_SESSION['role'] ?? '') === 'Admin') {
    $back_url = '/DORCFSRF/pages/admin_dashboard/completion_report.php';
}

$full_name = $_SESSION['full_name'] ?? $_SESSION['username'];
$student_id = $target_student_id; // Use target_student_id for the completion form

// Get stored profile image - display student's image in profile card, but current user's in header
$current_user_id = $_SESSION['user_id'];
$display_user_id = $from_staff ? $target_student_id : $current_user_id;

$profile_image_base = '/DORCFSRF/assets/student_profiles/' . $display_user_id . '_profile.jpg';
$profile_image_alt = (str_replace('_profile.jpg', '_profile.png', $profile_image_base));
$profile_image_alt2 = (str_replace('_profile.jpg', '_profile.gif', $profile_image_base));

// Check which image exists for profile card
if (file_exists(__DIR__ . '/../../' . $profile_image_base)) {
  $profile_image_url = $profile_image_base . '?t=' . filemtime($_SERVER['DOCUMENT_ROOT'] . $profile_image_base);
} elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image_alt)) {
  $profile_image_url = $profile_image_alt . '?t=' . filemtime($_SERVER['DOCUMENT_ROOT'] . $profile_image_alt);
} elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image_alt2)) {
  $profile_image_url = $profile_image_alt2 . '?t=' . filemtime($_SERVER['DOCUMENT_ROOT'] . $profile_image_alt2);
} else {
  $profile_image_url = null;
}

// Get header profile image (current user's image for dropdown)
$role = $_SESSION['role'] ?? '';
$header_base = '/DORCFSRF/assets/student_profiles/' . $current_user_id . '_profile.jpg';
if ($role === 'Staff') {
    $header_base = '/DORCFSRF/assets/staff_profiles/' . $current_user_id . '_profile.jpg';
} else if ($role === 'Admin') {
    $header_base = '/DORCFSRF/assets/admin_profiles/' . $current_user_id . '_profile.jpg';
}
$header_alt = (str_replace('_profile.jpg', '_profile.png', $header_base));
$header_alt2 = (str_replace('_profile.jpg', '_profile.gif', $header_base));

if (file_exists($_SERVER['DOCUMENT_ROOT'] . $header_base)) {
  $header_profile_image_url = $header_base . '?t=' . filemtime($_SERVER['DOCUMENT_ROOT'] . $header_base);
} elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . $header_alt)) {
  $header_profile_image_url = $header_alt . '?t=' . filemtime($_SERVER['DOCUMENT_ROOT'] . $header_alt);
} elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . $header_alt2)) {
  $header_profile_image_url = $header_alt2 . '?t=' . filemtime($_SERVER['DOCUMENT_ROOT'] . $header_alt2);
} else {
  $header_profile_image_url = null;
}

$sidebarLinks = [];
if ($from_staff) {
  if (($_SESSION['role'] ?? '') === 'Admin') {
    $sidebarLinks = [
      ['href' => '/DORCFSRF/pages/admin_dashboard/dashboard.php', 'icon' => 'bx bxs-dashboard', 'label' => 'Dashboard'],
      ['href' => '/DORCFSRF/pages/admin_dashboard/form.php', 'icon' => 'bx bx-notepad', 'label' => 'Form'],
      ['href' => '/DORCFSRF/pages/admin_dashboard/clearance_status.php', 'icon' => 'bx bxs-graduation', 'label' => 'Clearance Status'],
      ['href' => '/DORCFSRF/pages/admin_dashboard/payment_transaction.php', 'icon' => 'bx bx-coin-stack', 'label' => 'Payment Transaction'],
      ['href' => '/DORCFSRF/pages/admin_dashboard/scholastic_record_status.php', 'icon' => 'bx bx-bar-chart-alt-2', 'label' => 'Scholastic Record Status'],
      ['href' => '/DORCFSRF/pages/admin_dashboard/completion_report.php', 'icon' => 'bx bxs-calendar', 'label' => 'Completion Report', 'active' => true],
      ['href' => '/DORCFSRF/pages/admin_dashboard/account_settings.php', 'icon' => 'bx bx-cog', 'label' => 'Account Settings'],
    ];
    $profileSettingsLink = '/DORCFSRF/pages/admin_dashboard/account_settings.php';
  } else {
    $sidebarLinks = [
      ['href' => '/DORCFSRF/pages/staff_dashboard/staff_dasboard.php', 'icon' => 'bx bxs-dashboard', 'label' => 'Dashboard'],
      ['href' => '/DORCFSRF/pages/staff_dashboard/staff_form.php', 'icon' => 'bx bx-notepad', 'label' => 'Form'],
      ['href' => '/DORCFSRF/pages/staff_dashboard/staff_clearance.php', 'icon' => 'bx bxs-graduation', 'label' => 'Clearance Status'],
      ['href' => '/DORCFSRF/pages/staff_dashboard/staff_payment_transaction.php', 'icon' => 'bx bx-coin-stack', 'label' => 'Payment Transaction'],
      ['href' => '/DORCFSRF/pages/staff_dashboard/staff_scholastic_record.php', 'icon' => 'bx bx-bar-chart-alt-2', 'label' => 'Scholastic Record Status'],
      ['href' => '/DORCFSRF/pages/staff_dashboard/staff_completion_report.php', 'icon' => 'bx bxs-calendar', 'label' => 'Completion Report', 'active' => true],
      ['href' => '/DORCFSRF/pages/staff_dashboard/staff_account_settings.php', 'icon' => 'bx bx-cog', 'label' => 'Account Settings'],
    ];
    $profileSettingsLink = '/DORCFSRF/pages/staff_dashboard/staff_account_settings.php';
  }
} else {
  $sidebarLinks = [
    ['href' => '/DORCFSRF/pages/student_dashboard/dashboard.php', 'icon' => 'bx bxs-dashboard', 'label' => 'Dashboard'],
    ['href' => '/DORCFSRF/pages/student_dashboard/forms.php', 'icon' => 'bx bx-notepad', 'label' => 'Form'],
    ['href' => '/DORCFSRF/pages/student_dashboard/clearance.php', 'icon' => 'bx bxs-graduation', 'label' => 'Clearance Status'],
    ['href' => '/DORCFSRF/pages/student_dashboard/payment.php', 'icon' => 'bx bx-coin-stack', 'label' => 'Payment Transaction'],
    ['href' => '/DORCFSRF/pages/student_dashboard/record.php', 'icon' => 'bx bx-bar-chart-alt-2', 'label' => 'Scholastic Record Status'],
    ['href' => '/DORCFSRF/pages/student_dashboard/completion.php', 'icon' => 'bx bxs-calendar', 'label' => 'Completion Report', 'active' => true],
    ['href' => '/DORCFSRF/pages/student_dashboard/settings.php', 'icon' => 'bx bx-cog', 'label' => 'Account Settings'],
  ];
  $profileSettingsLink = '/DORCFSRF/pages/student_dashboard/settings.php';
}

// Database connection
include $_SERVER['DOCUMENT_ROOT'] . '/DORCFSRF/db_connect.php';

// Get student information
$student_info = array(
  'full_name' => $full_name,
  'student_course' => '',
  'student_year' => '',
  'contact_num' => '',
  'permanent_address' => ''
);
$stmt = $conn->prepare('SELECT full_name FROM user_database WHERE user_id = ?');
$stmt->bind_param('s', $student_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
  $row = $result->fetch_assoc();
  $student_info['full_name'] = $row['full_name'];
}
$stmt->close();

// Get student information from student_information_database
$stmt = $conn->prepare('SELECT student_course, student_year, contact_num, permanent_address FROM student_information_database WHERE student_id = ? LIMIT 1');
$stmt->bind_param('s', $student_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
  $row = $result->fetch_assoc();
  $student_info['student_course'] = $row['student_course'] ?? '';
  $student_info['student_year'] = $row['student_year'] ?? '';
  $student_info['contact_num'] = $row['contact_num'] ?? '';
  $student_info['permanent_address'] = $row['permanent_address'] ?? '';
}
$stmt->close();

// Get student completion/clearance information
$clearance_info = array();
$stmt = $conn->prepare('SELECT * FROM student_clearance_database WHERE student_id = ? LIMIT 1');
$stmt->bind_param('s', $student_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
  $clearance_info = $result->fetch_assoc();
}
$stmt->close();

// Get all clearance records for verification table (verified/accepted offices)
$all_clearances = array();
$stmt = $conn->prepare('SELECT * FROM student_clearance_database WHERE student_id = ?');
$stmt->bind_param('s', $student_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
  $offices = [
    'Records Officer' => 'records_officer_status',
    'Computer Lab' => 'computer_lab_status',
    'Librarian' => 'librarian_status',
    'Finance Officer' => 'finance_officer_status',
    'Thesis' => 'thesis_status',
    'Admission Officer' => 'admission_officer_status',
    'School Cashier' => 'school_cashier_status',
    'Property Custodian' => 'property_custodian_status',
    'Student Services' => 'student_services_status',
    'Scholarship Coordinator' => 'scholarship_coordinator_status',
    'Guidance Counselor' => 'guidance_counselor_status',
    'Registrar' => 'registrar_status',
    'CBRC' => 'cbrc_status'
  ];

  $staff_mapping = [
    'Records Officer' => 'Janie Lumbera',
    'Computer Lab' => 'Reyniel Centeno',
    'Librarian' => 'Maritess Panal',
    'Finance Officer' => 'Chin-Chin Devilles',
    'CBRC' => 'Kristine De La Cruz',
    'School Cashier' => 'Ma. Sharon Alvano',
    'Registrar' => 'Carmencita Aragon',
    'Admission Officer' => 'Azerelle Jalapan',
    'Property Custodian' => 'Jeck Albilar',
    'Student Services' => 'Kristine De La Cruz',
    'Scholarship Coordinator' => 'Mark Allan Tabalan',
    'Guidance Counselor' => 'Shirley Espiritu',
    'Thesis' => 'Maritess Panal'
  ];

  foreach ($offices as $office_name => $col) {
    $status = $row[$col] ?? 'Pending';
    // Only show offices that have verified/accepted (not Pending)
    if (strcasecmp($status, 'Pending') !== 0 && !empty($status)) {
      $staff_name = (strcasecmp($status, 'Approved') === 0) ? ($staff_mapping[$office_name] ?? 'Staff') : 'N/A';
      $all_clearances[] = [
        'office' => $office_name,
        'status' => $status,
        'date' => $row['updated_at'] ?? 'N/A',
        'staff' => $staff_name
      ];
    }
  }
}
$stmt->close();

// Get payment information
$payment_info = array();
$stmt = $conn->prepare('SELECT * FROM payment_record_database WHERE student_id = ? ORDER BY payment_date DESC LIMIT 1');
$stmt->bind_param('s', $student_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
  $payment_info = $result->fetch_assoc();
}
$stmt->close();

// Get all payment records for statistics
$all_payments = array();
$total_amount_paid = 0;
$stmt = $conn->prepare('SELECT * FROM payment_record_database WHERE student_id = ? ORDER BY payment_date DESC');
$stmt->bind_param('s', $student_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $all_payments[] = $row;
  $total_amount_paid += (floatval($row['tot_amount']) ?? 0);
}
$stmt->close();

// Calculate payment status
$total_payments = count($all_payments);
$payment_status = ($total_payments > 0) ? 'PAID' : 'UNPAID';
$has_payment = $total_payments > 0;

// Get completion report information from completion_report_database
$completion_report_info = array();
$stmt = $conn->prepare('SELECT * FROM completion_report_database WHERE student_id = ? LIMIT 1');
$stmt->bind_param('s', $student_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
  $completion_report_info = $result->fetch_assoc();
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Completion Report</title>

  <link rel="stylesheet" href="/DORCFSRF/css/dashboard.css" />
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/docx@8.5.0/build/index.js"></script>
</head>

<body>
  <div class="container">
    <aside class="sidebar">
      <img src="/DORCFSRF/assets/QECI LOGO.jpg" alt="qecilogo" />
      <nav>
        <?php foreach ($sidebarLinks as $link): ?>
          <?php if ($link['label'] === 'Account Settings'): ?>
            <p>SETTINGS</p>
          <?php endif; ?>
          <a href="<?php echo $link['href']; ?>" <?php echo isset($link['active']) && $link['active'] ? 'class="active"' : ''; ?>>
            <i class="<?php echo $link['icon']; ?>"></i> <?php echo $link['label']; ?>
          </a>
        <?php endforeach; ?>
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
              <?php if ($header_profile_image_url): ?>
                <img src="<?php echo htmlspecialchars($header_profile_image_url); ?>" alt="Profile"
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
                <p><?php echo $from_staff ? 'STAFF' : 'STUDENT'; ?></p>
              </div>
              <div class="card-actions">
                <a href="<?php echo $profileSettingsLink; ?>" class="action-btn action-btn-primary">Profile</a>
                <a href="/DORCFSRF/pages/logout.php" class="action-btn action-btn-secondary">Sign Out</a>
              </div>
              <script src="/DORCFSRF/pages/script/dropdown.js"></script>
            </div>
          </div>
        </div>
      </header>
      <main class="main-content">
        <div class="background-overlay"></div>
        <h2 class="breadcrumb" style="color: white;"><b>Home</b> > Completion Report</h2>
        <div id="staff-back-button"
          style="<?php echo $from_staff ? 'display: block;' : 'display: none;'; ?> margin: 0 0 16px;">
          <button onclick="window.location.href='<?php echo $back_url; ?>'"
            style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 16px; border: 1px solid rgba(0,0,0,0.12); border-radius: 10px; background: #fff; color: #333; cursor: pointer; font-weight: 600;">
            ← Back to Completion Report
          </button>
        </div>

        <div class="report-container">
          <div class="report-header">
            <h2>Quezonian Educational College, Inc.</h2>
            <p>Online Request for Clearance and Scholastic Record System</p>
            <hr />
          </div>

          <section class="report-section">
            <h3>Student Information</h3>
            <div class="info-list">
              <div class="info-item"><label>Name:</label><span
                  id="studentName"><?php echo htmlspecialchars($student_info['full_name']); ?></span></div>
              <div class="info-item"><label>Student I.D.</label><span
                  id="studentId"><?php echo htmlspecialchars($student_id); ?></span></div>
              <div class="info-item"><label>Course/Year:</label><span
                  id="courseYear"><?php echo htmlspecialchars($student_info['student_course'] . ' ' . $student_info['student_year']); ?></span>
              </div>
              <div class="info-item"><label>Request Type:</label><span id="requestType">Transcript of Records</span>
              </div>
              <div class="info-item"><label>Address:</label><span
                  id="address"><?php echo htmlspecialchars($student_info['permanent_address']); ?></span></div>
              <div class="info-item"><label>Contact No:</label><span
                  id="contactNo"><?php echo htmlspecialchars($student_info['contact_num']); ?></span></div>
              <div class="info-item"><label>Student Record ID:</label><span
                  id="studentRecordId">RS-2026-<?php echo str_pad(substr($student_id, -4), 4, '0', STR_PAD_LEFT); ?></span>
              </div>
            </div>
          </section>

          <section class="report-section">
            <h3>Clearance & Payment Verification</h3>
            <table class="clearance-table">
              <thead>
                <tr>
                  <th>Office</th>
                  <th>Verified By</th>
                  <th>Date</th>
                  <th>Remarks</th>
                </tr>
              </thead>
              <tbody>
                <?php if (count($all_clearances) > 0): ?>
                  <?php foreach ($all_clearances as $clearance): ?>
                    <tr>
                      <td><?php echo htmlspecialchars($clearance['office'] ?? $clearance['office_name'] ?? 'N/A'); ?></td>
                      <td>
                        <?php echo htmlspecialchars($clearance['staff'] ?? $clearance['processed_by'] ?? $clearance['verified_by'] ?? 'N/A'); ?>
                      </td>
                      <td><?php
                      $date_col = isset($clearance['clearance_date']) ? 'clearance_date' : (isset($clearance['date']) ? 'date' : null);
                      echo ($date_col && $clearance[$date_col]) ? date('m/d/Y', strtotime($clearance[$date_col])) : 'N/A';
                      ?></td>
                      <td>
                        <?php echo htmlspecialchars($clearance['status'] ?? $clearance['clearance_status'] ?? 'Pending'); ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="4" style="text-align: center; color: #999;">No clearance records found</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </section>

          <section class="report-section">
            <h3>Document Status</h3>
            <div class="status-box">
              <?php
              $status_col = isset($clearance_info['clearance_status']) ? 'clearance_status' : (isset($clearance_info['status']) ? 'status' : null);
              $status_val = ($status_col && isset($clearance_info[$status_col])) ? $clearance_info[$status_col] : 'PENDING';
              $date_col = isset($clearance_info['clearance_date']) ? 'clearance_date' : (isset($clearance_info['date']) ? 'date' : null);
              $date_val = ($date_col && isset($clearance_info[$date_col])) ? date('m/d/Y', strtotime($clearance_info[$date_col])) : 'N/A';
              $staff_col = isset($clearance_info['processed_by']) ? 'processed_by' : (isset($clearance_info['staff']) ? 'staff' : null);
              $staff_val = ($staff_col && isset($clearance_info[$staff_col])) ? htmlspecialchars($clearance_info[$staff_col]) : 'Pending';
              ?>
              <p><strong>Clearance Status:</strong> <span
                  class="status-<?php echo strtolower($status_val); ?>"><?php echo strtoupper($status_val); ?></span> -
                <?php echo $date_val; ?> Processed by Admin Staff (<?php echo $staff_val; ?>)
              </p>
              <p><strong>Payment Status:</strong> <span
                  style="color: <?php echo $has_payment ? '#27ae60' : '#e74c3c'; ?>; font-weight: bold;"><?php echo $has_payment ? '✓ PAID' : '✕ UNPAID'; ?></span>
              </p>
              <p><strong>Requirements:</strong> <?php
              $clearance_ok = (strtolower($status_val) === 'cleared');
              $payment_ok = $has_payment;

              if ($clearance_ok && $payment_ok) {
                echo 'All requirements completed. Ready for document release.';
              } elseif ($clearance_ok && !$payment_ok) {
                echo 'Clearance complete but payment pending. Please complete payment to finalize.';
              } elseif (!$clearance_ok && $payment_ok) {
                echo 'Payment received. Waiting for clearance completion.';
              } else {
                echo 'Clearance and payment are pending. Please complete all requirements.';
              }
              ?></p>
              <p><strong>Record Status:</strong> <?php 
              if (!empty($completion_report_info)) {
                $unit_stats = $completion_report_info['unit_stats'] ?? 'Pending';
                $scholastic_stat = $completion_report_info['scholasticrec_stat'] ?? 'Not Set';
                echo htmlspecialchars($unit_stats) . ' - Scholastic: ' . htmlspecialchars($scholastic_stat);
              } else {
                echo 'Not yet processed';
              }
              ?></p>
              <p><strong>Student Record ID:</strong> <?php 
              if (!empty($completion_report_info)) {
                echo htmlspecialchars($completion_report_info['studentrecord_id'] ?? 'N/A');
              } else {
                echo 'Not assigned';
              }
              ?></p>
              <p style="display: flex; flex-direction: column;"><strong>Final Remarks:</strong> 
              <?php if ($from_staff): ?>
                <textarea id="completion-remarks" rows="3" style="width: 100%; margin-top: 5px; padding: 8px; border-radius: 4px; border: 1px solid #ccc; font-family: inherit; resize: vertical;" placeholder="Add final remarks for the student..."><?php echo htmlspecialchars($clearance_info['completion_remarks'] ?? ''); ?></textarea>
              <?php else: ?>
                <span style="margin-top: 5px;">
                <?php
                if (!empty($completion_report_info['remarks'])) {
                  echo nl2br(htmlspecialchars($completion_report_info['remarks']));
                } elseif (!empty($clearance_info['completion_remarks'])) {
                  echo nl2br(htmlspecialchars($clearance_info['completion_remarks']));
                } elseif ($clearance_ok && $payment_ok) {
                  echo 'Released to student upon presentation of valid ID.';
                } else {
                  echo 'Your request is being processed. Please check back later.';
                }
                ?>
                </span>
              <?php endif; ?>
              </p>
            </div>
          </section>

          <section class="report-section">
            <h3>Acknowledgement Receipt</h3>
            <div class="info-list">
              <?php
              // Use completion_report_database data if available, otherwise use clearance_info
              $received_by = !empty($completion_report_info) ? ($completion_report_info['received_by'] ?? 'Pending') : ($clearance_info['processed_by'] ?? ($clearance_info['staff'] ?? 'Pending'));
              $release_date = !empty($completion_report_info) && !empty($completion_report_info['release_date']) ? date('m/d/Y', strtotime($completion_report_info['release_date'])) : 'N/A';
              $status_val_receipt = !empty($completion_report_info) ? ($completion_report_info['unit_stats'] ?? 'pending') : $status_val;
              ?>
              <div class="info-item"><label>Received By:</label><span><?php echo htmlspecialchars($received_by); ?></span></div>
              <div class="info-item">
                <label>Signature:</label>
                <span
                  style="color: #999;"><?php echo (strtolower($status_val_receipt) === 'cleared') ? '[Digital Signature]' : 'N/A'; ?></span>
              </div>
              <div class="info-item"><label>Release Date:</label><span><?php echo $release_date; ?></span></div>
            </div>
          </section>

          <section class="report-section">
            <h3>Payment Receipt Details</h3>
            <div class="info-list">
              <?php
              if ($has_payment && count($all_payments) > 0) {
                $latest = $all_payments[0];
                $or_num = $latest['or_num'] ?? 'N/A';
                $amount = $latest['tot_amount'] ?? '0.00';
                $pay_date = $latest['payment_date'] ?? null;
                $payment_method = $latest['payment_method'] ?? 'N/A';
                ?>
                <div class="info-item"><label>OR Number:</label><span><?php echo htmlspecialchars($or_num); ?></span>
                </div>
                <div class="info-item"><label>Amount
                    Paid:</label><span>P<?php echo number_format(floatval($amount), 2); ?></span></div>
                <div class="info-item"><label>Payment
                    Method:</label><span><?php echo htmlspecialchars($payment_method); ?></span></div>
                <div class="info-item"><label>Date of
                    Payment:</label><span><?php echo ($pay_date) ? date('m/d/Y', strtotime($pay_date)) : 'N/A'; ?></span>
                </div>
                <div class="info-item"><label>Payment Status:</label><span style="color: #27ae60; font-weight: bold;">✓
                    PAID</span></div>
                <div class="info-item"><label>Total Payments:</label><span><?php echo $total_payments; ?>
                    transaction(s)</span></div>
                <div class="info-item"><label>Total
                    Amount:</label><span>P<?php echo number_format($total_amount_paid, 2); ?></span></div>
              <?php } else { ?>
                <div class="info-item"><label>OR Number:</label><span>N/A</span></div>
                <div class="info-item"><label>Amount Paid:</label><span>P0.00</span></div>
                <div class="info-item"><label>Date of Payment:</label><span>N/A</span></div>
                <div class="info-item"><label>Payment Status:</label><span style="color: #e74c3c; font-weight: bold;">✕
                    UNPAID</span></div>
                <div class="info-item"><label>Remarks:</label><span>No payment records found. Please proceed to Payment
                    page.</span></div>
              <?php } ?>
            </div>
          </section>

          <div class="report-actions">
            <button class="btn-print" onclick="printReport()"><i class="bx bx-printer"></i> Print Report</button>
            <button class="btn-download" onclick="downloadReport()"><i class="bx bx-download"></i> Download</button>
            <?php if ($from_staff): ?>
              <button id="staff-back-button" class="btn-cancel" onclick="goBackToCompletion()"><i
                  class="bx bx-arrow-back"></i> Cancel</button>
              <button class="btn-save" onclick="saveChanges()"><i class="bx bx-check"></i> Save Changes</button>
            <?php endif; ?>
          </div>
          <div id="download-notification"
            style="margin-top: 0.75rem; min-height: 1.2rem; color: #2ecc71; font-size: 0.95rem;"></div>
        </div>
      </main>
    </div>
  </div>

  <script>
    function printReport() {
      window.print();
    }

    function goBackToCompletion() {
      window.location.href = '<?php echo $back_url; ?>';
    }

    function saveChanges() {
      const remarks = document.getElementById('completion-remarks').value;
      const params = new URLSearchParams(window.location.search);
      const studentId = params.get('student_id');
      
      if (!studentId) {
        alert("Error: Student ID is missing from URL");
        return;
      }

      const formData = new FormData();
      formData.append('student_id', studentId);
      formData.append('completion_remarks', remarks);

      fetch('/DORCFSRF/api/save_completion_remarks.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert('Changes saved successfully!');
          window.location.href = '<?php echo $back_url; ?>';
        } else {
          alert("Error: " + (data.message || "Failed to save changes"));
        }
      })
      .catch(error => {
        console.error('Fetch error:', error);
        alert("Error saving changes. Please try again.");
      });
    }

    function downloadReport() {
      const reportElement = document.querySelector('.report-container');
      if (!reportElement) {
        showDownloadNotification('Unable to find the report to download.', false);
        return;
      }

      const opt = {
        margin: 0.4,
        filename: 'completion-report.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
      };

      html2pdf().set(opt).from(reportElement).save()
        .then(() => {
          showDownloadNotification('Download successful. The file has been saved to your device.');
        })
        .catch(error => {
          console.error('Download failed:', error);
          showDownloadNotification('Download failed. Please try again.', false);
        });
    }

    function showDownloadNotification(message, success = true) {
      const notification = document.getElementById('download-notification');
      if (notification) {
        notification.textContent = message;
        notification.style.color = success ? '#2ecc71' : '#e74c3c';
        setTimeout(() => {
          notification.textContent = '';
        }, 3500);
      } else {
        alert(message);
      }
    }

    function showStaffBackButton() {
      const params = new URLSearchParams(window.location.search);
      if (params.get('from') === 'staff_completion_report' || params.get('admin') === '1') {
        const backButton = document.getElementById('staff-back-button');
        if (backButton) {
          backButton.style.display = 'block';
        }
      }
    }

    function setStudentInfo() {
      const params = new URLSearchParams(window.location.search);
      const studentKey = params.get('student');
      if (!studentKey) {
        return;
      }

      const students = {
        hita_isabel: {
          name: 'Hita, Isabel Maryniza A.',
          id: 'CS-19-0111',
          courseYear: 'BSCS 3',
          requestType: 'Transcript of Records',
          address: 'Brgy. San Jose',
          contactNo: '0917-123-4567',
          studentRecordId: 'RS-2025-0021'
        },
        formaran_christian: {
          name: 'Formaran, Christian A.',
          id: 'CS-19-0112',
          courseYear: 'BSCS 2',
          requestType: 'Transcript of Records',
          address: 'Brgy. Sta. Cruz',
          contactNo: '0918-234-5678',
          studentRecordId: 'RS-2025-0022'
        },
        vargas_ian: {
          name: 'Vargas, Ian A.',
          id: 'CS-19-0113',
          courseYear: 'BSCS 4',
          requestType: 'Transcript of Records',
          address: 'Brgy. San Miguel',
          contactNo: '0919-345-6789',
          studentRecordId: 'RS-2025-0023'
        },
        saavedra_margel: {
          name: 'Saavedra, Margel B.',
          id: 'CS-19-0114',
          courseYear: 'BSCS 1',
          requestType: 'Transcript of Records',
          address: 'Brgy. Malinis',
          contactNo: '0920-456-7890',
          studentRecordId: 'RS-2025-0024'
        },
        alegre_gemwil: {
          name: 'Alegre, Gemwil A.',
          id: 'CS-19-0115',
          courseYear: 'BSCS 4',
          requestType: 'Transcript of Records',
          address: 'Brgy. Habingan',
          contactNo: '0936-564-3219',
          studentRecordId: 'RS-2026-0001'
        },
        manalo_john: {
          name: 'Manalo, John Carl Louis A.',
          id: 'CS-19-0116',
          courseYear: 'BSCS 3',
          requestType: 'Transcript of Records',
          address: 'Brgy. San Pedro',
          contactNo: '0921-567-8901',
          studentRecordId: 'RS-2025-0025'
        },
        villamena_kristyl: {
          name: 'Villamena, Kristyl Anne O.',
          id: 'CS-19-0117',
          courseYear: 'BSCS 2',
          requestType: 'Transcript of Records',
          address: 'Brgy. Green Meadows',
          contactNo: '0922-678-9012',
          studentRecordId: 'RS-2025-0026'
        }
      };

      const student = students[studentKey];
      if (!student) {
        return;
      }

      const assign = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
      };

      assign('studentName', student.name);
      assign('studentId', student.id);
      assign('courseYear', student.courseYear);
      assign('requestType', student.requestType);
      assign('address', student.address);
      assign('contactNo', student.contactNo);
      assign('studentRecordId', student.studentRecordId);
    }

    showStaffBackButton();
    setStudentInfo();
  </script>
  <script src="/DORCFSRF/pages/script/notifications.js"></script>
  <script src="/DORCFSRF/pages/script/prevent-back-button.js"></script>
</body>

</html>