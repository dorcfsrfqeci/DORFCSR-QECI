<?php
ini_set('session.gc_maxlifetime', 31536000);
session_set_cookie_params(31536000);
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
include __DIR__ . '/../../db_connect.php';

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

$sql = "SELECT * FROM student_clearance_database WHERE student_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $student_id);
$stmt->execute();
$result = $stmt->get_result();
$clearances = [];
while ($row = $result->fetch_assoc()) {
  $clearances[] = $row;
}
$stmt->close();

// Get latest clearance record — SELECT * already includes all dept-specific columns
$latestClearance = !empty($clearances) ? end($clearances) : null;

// --- PRIMARY STATUS SOURCE: clearance_approvals_log (keyed by staff_id) ---
// This is the most accurate source: it stores exactly which staff set which status
// and works for ANY staff_id regardless of name format (e.g. STF006).
$logByStaff = []; // [staff_id => status_value]
$logTableCheck = $conn->query("SHOW TABLES LIKE 'clearance_approvals_log'");
if ($logTableCheck && $logTableCheck->num_rows > 0) {
  // Prefer staff_id column when present
  $colCheck = $conn->query("SHOW COLUMNS FROM clearance_approvals_log LIKE 'staff_id'");
  if ($colCheck && $colCheck->num_rows > 0) {
    $logSql = "SELECT staff_id, status FROM clearance_approvals_log WHERE student_id = ?";
    $logStmt = $conn->prepare($logSql);
    if ($logStmt) {
      $logStmt->bind_param('s', $student_id);
      $logStmt->execute();
      $logResult = $logStmt->get_result();
      while ($lr = $logResult->fetch_assoc()) {
        if (!empty($lr['staff_id'])) {
          $logByStaff[$lr['staff_id']] = $lr['status'];
        }
      }
      $logStmt->close();
    }
  } else {
    // Fallback: if staff_name column exists, try to map names to user IDs
    $colCheckName = $conn->query("SHOW COLUMNS FROM clearance_approvals_log LIKE 'staff_name'");
    if ($colCheckName && $colCheckName->num_rows > 0) {
      $logSql = "SELECT staff_name, status FROM clearance_approvals_log WHERE student_id = ?";
      $logStmt = $conn->prepare($logSql);
      if ($logStmt) {
        $logStmt->bind_param('s', $student_id);
        $logStmt->execute();
        $logResult = $logStmt->get_result();
        while ($lr = $logResult->fetch_assoc()) {
          $staff_name = trim($lr['staff_name'] ?? '');
          $status = $lr['status'] ?? null;
          if (empty($staff_name)) continue;

          // Try exact match then LIKE match on user_database.full_name
          $findStmt = $conn->prepare("SELECT user_id FROM user_database WHERE LOWER(TRIM(full_name)) = LOWER(TRIM(?)) LIMIT 1");
          if ($findStmt) {
            $findStmt->bind_param('s', $staff_name);
            $findStmt->execute();
            $res = $findStmt->get_result();
            if ($r = $res->fetch_assoc()) {
              $logByStaff[$r['user_id']] = $status;
              $findStmt->close();
              continue;
            }
            $findStmt->close();
          }

          $findStmt2 = $conn->prepare("SELECT user_id FROM user_database WHERE LOWER(full_name) LIKE LOWER(CONCAT('%', ?, '%')) LIMIT 1");
          if ($findStmt2) {
            $findStmt2->bind_param('s', $staff_name);
            $findStmt2->execute();
            $res2 = $findStmt2->get_result();
            if ($r2 = $res2->fetch_assoc()) {
              $logByStaff[$r2['user_id']] = $status;
              $findStmt2->close();
              continue;
            }
            $findStmt2->close();
          }

          // If still not matched, store by name key so we can display or inspect it
          $logByStaff[$staff_name] = $status;
        }
        $logStmt->close();
      }
    }
  }
}

// Map staff name keywords → dept status column (mirrors staff_clearance_requirments_dtls.php)
$staff_dept_col_map = [
  'janie'      => 'records_officer_status',
  'lumbera'    => 'records_officer_status',
  'barenno'    => 'records_officer_status',
  'reyniel'    => 'computer_lab_status',
  'centeno'    => 'computer_lab_status',
  'maritess'   => 'librarian_status',
  'panal'      => 'librarian_status',
  'chin-chin'  => 'finance_officer_status',
  'devilles'   => 'finance_officer_status',
  'kristine'   => 'cbrc_status',
  'dela cruz'  => 'cbrc_status',
  'delacruz'   => 'cbrc_status',
  'azerelle'   => 'admission_officer_status',
  'jalapan'    => 'admission_officer_status',
  'russel'     => 'admission_officer_status',
  'ortiz'      => 'admission_officer_status',
  'carmencita' => 'registrar_status',
  'aragon'     => 'registrar_status',
  'sharon'     => 'school_cashier_status',
  'alvano'     => 'school_cashier_status',
  'jeck'       => 'property_custodian_status',
  'albilar'    => 'property_custodian_status',
  'mark'       => 'scholarship_coordinator_status',
  'tabalan'    => 'scholarship_coordinator_status',
  'shirley'    => 'guidance_counselor_status',
  'espiritu'   => 'guidance_counselor_status',
];

function getStaffDeptColumn($full_name, $map) {
  $lower = strtolower($full_name);
  foreach ($map as $keyword => $col) {
    if (strpos($lower, $keyword) !== false) return $col;
  }
  return null;
}

function normalizeClearanceStatus($raw) {
  $v = strtolower(trim($raw ?? ''));
  if ($v === 'in progress') return 'In Progress';
  if (in_array($v, ['cleared', 'approved', 'completed'])) return 'Cleared';
  return 'Pending';
}

$staffAccounts = [];
$staffSql = "SELECT user_id, full_name, email FROM user_database WHERE role = 'Staff' ORDER BY full_name";
$staffStmt = $conn->prepare($staffSql);
$staffStmt->execute();
$staffResult = $staffStmt->get_result();
while ($staffRow = $staffResult->fetch_assoc()) {
  $staffAccounts[] = $staffRow;
}
$staffStmt->close();

// Function to get staff profile image
function getStaffProfileImage($staff_id)
{
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

// Officer details mapping - complete list of clearing officers
$officers = [
  'Thesis' => ['name' => 'Mrs. Maritess V. Panal', 'email' => 'mrs.panalib@gmail.com'],
  'Librarian' => ['name' => 'Mrs. Maritess V. Panal', 'email' => 'mrs.panalib@gmail.com'],
  'Finance Officer' => ['name' => 'Ms. Chin-Chin O. Devilles', 'email' => 'mrs.devillesfo@gmail.com'],
  'Admission Officer' => ['name' => 'Azerelle M. Jalapan / Russel R. Ortiz', 'email' => ''],
  'Lab./ComLab/ID' => ['name' => 'Mr. Rey Niel T. Centeno', 'email' => 'mr.reynielc@gmail.com'],
  'Computer Laboratory' => ['name' => 'Mr. Rey Niel T. Centeno', 'email' => 'mr.reynielc@gmail.com'],
  'School Cashier' => ['name' => 'Ma. Sharon C. Alvano', 'email' => ''],
  'Property Custodian' => ['name' => 'Jeck A. Albilar', 'email' => ''],
  'Student Services Coordinator' => ['name' => 'Ms. Kristine A. De La Cruz', 'email' => 'ms.delacruzcbcr@gmail.com'],
  'Scholarship Coordinator' => ['name' => 'Mark Allan G. Tabalan', 'email' => ''],
  'Guidance Counselor' => ['name' => 'Shirley Margarette O. Espiritu', 'email' => ''],
  'Records Officer' => ['name' => 'Mrs. Janie Ruth A. Lumbera', 'email' => 'ms.janierc@gmail.com'],
  'Registrar' => ['name' => 'Carmencita L. Aragon', 'email' => ''],
  'College Dean' => ['name' => 'Carmencita L. Aragon', 'email' => ''],
];

// Item mapping for links
$item_map = [
  'Thesis' => 'thesis',
  'Librarian' => 'librarian',
  'Finance Officer' => 'finance',
  'Admission Officer' => 'admission',
  'Lab./ComLab/ID' => 'computer_lab',
  'Computer Laboratory' => 'computer_lab',
  'School Cashier' => 'cashier',
  'Property Custodian' => 'property',
  'Student Services Coordinator' => 'services',
  'Scholarship Coordinator' => 'scholarship',
  'Guidance Counselor' => 'guidance',
  'Records Officer' => 'records',
  'Registrar' => 'registrar',
  'College Dean' => 'dean',
];

// Function to get unit concerned based on staff name
function getUnitConcernedByName($full_name)
{
  if (!$full_name)
    return 'N/A';

  $name_lower = strtolower($full_name);

  // Map staff names to their units concerned (matches actual user_database full_name values)
  $staff_unit_mapping = [
    'janie'       => 'Records Officer',
    'lumbera'     => 'Records Officer',
    'barenno'     => 'Records Officer',
    'reyniel'     => 'Computer Laboratory',
    'centeno'     => 'Computer Laboratory',
    'maritess'    => 'Librarian',
    'panal'       => 'Librarian',
    'chin-chin'   => 'Finance Officer',
    'devilles'    => 'Finance Officer',
    'kristine'    => 'Student Services Coordinator',
    'dela cruz'   => 'Student Services Coordinator',
    'delacruz'    => 'Student Services Coordinator',
    'azerelle'    => 'Admission Officer',
    'jalapan'     => 'Admission Officer',
    'russel'      => 'Admission Officer',
    'ortiz'       => 'Admission Officer',
    'carmencita'  => 'Registrar',
    'aragon'      => 'Registrar',
    'sharon'      => 'School Cashier',
    'alvano'      => 'School Cashier',
    'jeck'        => 'Property Custodian',
    'albilar'     => 'Property Custodian',
    'mark'        => 'Scholarship Coordinator',
    'tabalan'     => 'Scholarship Coordinator',
    'shirley'     => 'Guidance Counselor',
    'espiritu'    => 'Guidance Counselor',
  ];

  foreach ($staff_unit_mapping as $key => $unit) {
    if (strpos($name_lower, $key) !== false) {
      return $unit;
    }
  }

  return 'Staff Account';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Clerance Status</title>

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
        <a href="clearance.php" class="active"><i class="bx bxs-graduation"></i> Clearance Status</a>
        <a href="payment.php"><i class="bx bx-coin-stack"></i> Payment Transaction</a>
        <a href="record.php"><i class="bx bx-bar-chart-alt-2"></i> Scholastic Record Status</a>
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

      <!-- MAIN CARD -->
      <main class="main-content">
        <div class="background-overlay"></div>
        <h2 class="breadcrumb"><b>Home</b> > Clearance Status</h2>
        <table class="status-table">

          <thead>
            <tr>
              <th>Clearing Officers</th>
              <th>Email ID</th>
              <th>Unit Concerned</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($clearances as $clearance): ?>
              <?php
              // Try to get staff profile image if officer info available
              $unit_con = $clearance['unit_con'] ?? '';
              $officer_name = $officers[$unit_con]['name'] ?? $unit_con;
              $officer_email = $officers[$unit_con]['email'] ?? null;
              $officer_profile = null;
              $staff_user_id = null;

              // Find staff account — substring match (handles title prefixes like "Mrs."/"Mr.")
              foreach ($staffAccounts as $staff) {
                $sname_lower = strtolower($staff['full_name']);
                $oname_lower = strtolower($officer_name);
                if ($sname_lower === $oname_lower
                    || strpos($sname_lower, $oname_lower) !== false
                    || strpos($oname_lower, $sname_lower) !== false) {
                  $officer_profile = getStaffProfileImage($staff['user_id']);
                  $staff_user_id = $staff['user_id'];
                  if (empty($officer_email) || strtoupper(trim($officer_email)) === 'N/A') {
                    $officer_email = $staff['email'];
                  }
                  break;
                }
              }
              // Fallback: use clearance_approvals_log to find staff_id by dept field
              if (empty($staff_user_id)) {
                $dept_col = getStaffDeptColumn($officer_name, $staff_dept_col_map);
                if ($dept_col) {
                  foreach ($logByStaff as $lid => $lstatus) {
                    // $logByStaff is keyed by staff_id — check if any staff maps to this dept
                    foreach ($staffAccounts as $staff) {
                      if ($staff['user_id'] === $lid) {
                        $test_col = getStaffDeptColumn($staff['full_name'], $staff_dept_col_map);
                        if ($test_col === $dept_col) {
                          $staff_user_id = $lid;
                          $officer_profile = getStaffProfileImage($lid);
                          if (empty($officer_email) || strtoupper(trim($officer_email)) === 'N/A') {
                            $officer_email = $staff['email'];
                          }
                          break 2;
                        }
                      }
                    }
                  }
                }
              }
              // Skip clearance entries where email is still missing or N/A
              if (empty($officer_email) || strtoupper(trim($officer_email)) === 'N/A') {
                continue;
              }
              ?>
              <tr>
                <td>
                  <div style="display: flex; align-items: center; gap: 10px;">
                    <?php if ($officer_profile): ?>
                      <img src="<?php echo htmlspecialchars($officer_profile); ?>"
                        alt="<?php echo htmlspecialchars($officer_name); ?>"
                        style="width: 35px; height: 35px; object-fit: cover; border-radius: 50%;" />
                    <?php else: ?>
                      <i class="bx bxs-user-circle" style="font-size: 35px;"></i>
                    <?php endif; ?>
                    <span><?php echo htmlspecialchars($officer_name); ?></span>
                  </div>
                </td>
                <td><?php echo htmlspecialchars($officer_email); ?></td>
                <td><?php echo htmlspecialchars($unit_con); ?></td>
                <td><span
                    class="badge <?php echo strtolower(str_replace(' ', '-', $clearance['unit_stats'])); ?>"><?php echo htmlspecialchars($clearance['unit_stats']); ?></span>
                </td>
                <td><a
                    href="/DORCFSRF/pages/click_button/clearance_details.php?item=<?php echo htmlspecialchars($item_map[$unit_con] ?? ''); ?>&staff_id=<?php echo htmlspecialchars($staff_user_id ?? ''); ?>&clearance_id=<?php echo htmlspecialchars($clearance['clearance_id'] ?? ''); ?>"
                    class="view-btn">View Details</a></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!empty($staffAccounts)): ?>
              <?php foreach ($staffAccounts as $staff): ?>
                <?php if (empty($staff['email']) || strtoupper(trim($staff['email'])) === 'N/A')
                  continue; ?>
                <?php
                  $staff_profile_image = getStaffProfileImage($staff['user_id']);
                  $unit_label = getUnitConcernedByName($staff['full_name']);

                  // PRIMARY: look up by staff_id in clearance_approvals_log
                  // FALLBACK: name-based dept column lookup
                  if (isset($logByStaff[$staff['user_id']])) {
                    $raw_val = $logByStaff[$staff['user_id']];
                  } else {
                    $dept_col = getStaffDeptColumn($staff['full_name'], $staff_dept_col_map);
                    $raw_val  = ($dept_col && $latestClearance) ? ($latestClearance[$dept_col] ?? null) : null;
                  }

                  $disp_status = normalizeClearanceStatus($raw_val);
                  $badge_cls   = strtolower(str_replace(' ', '-', $disp_status));
                  $item_key    = $item_map[$unit_label] ?? '';
                  $cl_id       = $latestClearance['clearance_id'] ?? '';
                ?>
                <tr>
                  <td>
                    <div style="display: flex; align-items: center; gap: 10px;">
                      <?php if ($staff_profile_image): ?>
                        <img src="<?php echo htmlspecialchars($staff_profile_image); ?>"
                          alt="<?php echo htmlspecialchars($staff['full_name']); ?>"
                          style="width: 35px; height: 35px; object-fit: cover; border-radius: 50%;" />
                      <?php else: ?>
                        <i class="bx bxs-user-circle" style="font-size: 35px;"></i>
                      <?php endif; ?>
                      <span><?php echo htmlspecialchars($staff['full_name']); ?></span>
                    </div>
                  </td>
                  <td><?php echo htmlspecialchars($staff['email']); ?></td>
                  <td><?php echo htmlspecialchars($unit_label); ?></td>
                  <td><span class="badge <?php echo htmlspecialchars($badge_cls); ?>"><?php echo htmlspecialchars($disp_status); ?></span></td>
                  <td><a
                      href="/DORCFSRF/pages/click_button/clearance_details.php?item=<?php echo htmlspecialchars($item_key); ?>&staff_id=<?php echo htmlspecialchars($staff['user_id']); ?>&clearance_id=<?php echo htmlspecialchars($cl_id); ?>"
                      class="view-btn">View Details</a></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </main>
    </div>
    <script src="/DORCFSRF/pages/script/notifications.js"></script>
    <script src="/DORCFSRF/pages/script/prevent-back-button.js"></script>
</body>

</html>