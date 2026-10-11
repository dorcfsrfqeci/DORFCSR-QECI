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

// Database connection
include __DIR__ . '/../../db_connect.php';

// Get student clearance status
$clearance_total = 13;
$clearance_cleared = 0;
$clearance_id = null;
$stmt = $conn->prepare('SELECT * FROM student_clearance_database WHERE student_id = ? LIMIT 1');
$stmt->bind_param('s', $student_id);
$stmt->execute();
$result = $stmt->get_result();
function normalizeStudentClearanceStatus($dbValue, $fallbackValue = 'Pending')
{
  $value = trim(strtolower((string) $dbValue));
  if ($value === 'approved') {
    return 'Cleared';
  }
  if ($value === 'cleared' || $value === 'in progress' || $value === 'pending') {
    return ucwords($value);
  }
  if ($value !== '') {
    return ucwords($value);
  }
  return ucwords(strtolower($fallbackValue));
}

$thesis_status = 'Pending';
$librarian_status = 'Pending';
$finance_status = 'Pending';
$admission_status = 'Pending';
$lab_status = 'Pending';
$cashier_status = 'Pending';
$records_status = 'Pending';
$default_clearance_status = 'Pending';

if ($row = $result->fetch_assoc()) {
  // Store clearance ID
  $clearance_id = $row['clearance_id'] ?? null;

  // Count approved department statuses, but default to 0/13 if none are approved.
  $departmentStatusFields = [
    'records_officer_status',
    'computer_lab_status',
    'librarian_status',
    'finance_officer_status',
    'cbrc_status',
  ];

  foreach ($departmentStatusFields as $field) {
    if (!empty($row[$field]) && $row[$field] === 'Approved') {
      $clearance_cleared++;
    }
  }

  if (!empty($row['unit_stats'])) {
    $default_clearance_status = $row['unit_stats'];
  } elseif (!empty($row['clearance_status'] ?? '')) {
    $default_clearance_status = $row['clearance_status'];
  }

  $thesis_status = normalizeStudentClearanceStatus($row['clearance_status'] ?? null, $default_clearance_status);
  $librarian_status = normalizeStudentClearanceStatus($row['librarian_status'] ?? null, $default_clearance_status);
  $finance_status = normalizeStudentClearanceStatus($row['finance_officer_status'] ?? null, $default_clearance_status);
  $admission_status = normalizeStudentClearanceStatus($row['records_officer_status'] ?? null, $default_clearance_status);
  $lab_status = normalizeStudentClearanceStatus($row['computer_lab_status'] ?? null, $default_clearance_status);
  $cashier_status = normalizeStudentClearanceStatus($row['cbrc_status'] ?? null, $default_clearance_status);
  $records_status = normalizeStudentClearanceStatus($row['records_officer_status'] ?? null, $default_clearance_status);

  if (($row['clearance_status'] ?? '') === 'Cleared') {
    $clearance_cleared = $clearance_total;
  }
}
$stmt->close();

// Get payment status
$payment_status = 'UNPAID';
$stmt = $conn->prepare('SELECT 1 FROM payment_record_database WHERE student_id = ? LIMIT 1');
$stmt->bind_param('s', $student_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
  $payment_status = 'PAID';
}
$stmt->close();

// Get form submission status
$form_submitted = false;
$scholastic_status = 'Pending';
$stmt = $conn->prepare('SELECT scholastic_status FROM student_information_database WHERE student_id = ? LIMIT 1');
$stmt->bind_param('s', $student_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
  $form_submitted = true;
  $row_info = $result->fetch_assoc();
  if (!empty($row_info['scholastic_status'])) {
    $scholastic_status = $row_info['scholastic_status'];
  }
}
$stmt->close();

// Determine Completion Report Status
$completion_status = (($form_submitted) && ($clearance_cleared === $clearance_total)) ? 'Verified' : 'Not Yet';
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
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard</title>
  <link rel="stylesheet" href="/DORCFSRF/css/dashboard.css" />
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <script>
    // Check if token exists - if not, redirect to login
    if (!localStorage.getItem("userToken")) {
      window.location.href = "/DORCFSRF/pages/login_form/index.php";
    }

    // Prevent browser back button
    function preventBack() {
      window.history.forward();
    }

    // Push multiple states to prevent back navigation
    window.history.pushState(null, null, window.location.href);
    window.history.pushState(null, null, window.location.href);
    window.history.pushState(null, null, window.location.href);

    // Handle popstate (back button pressed)
    window.addEventListener('popstate', function () {
      if (!localStorage.getItem("userToken")) {
        window.location.href = "/DORCFSRF/pages/login_form/index.php";
      } else {
        window.history.pushState(null, null, window.location.href);
      }
    });

    // Prevent going back to login using back button
    window.addEventListener('pageshow', function (event) {
      // Check if page was loaded from cache (event.persisted)
      if (event.persisted || window.performance.navigation.type === 2) {
        // Re-check authentication; if not logged in, redirect to login
        if (!localStorage.getItem("userToken")) {
          window.location.href = "/DORCFSRF/pages/login_form/index.php";
        }
      }
    });
  </script>
</head>

<body>
  <div class="container">
    <aside class="sidebar">
      <img src="/DORCFSRF/assets/QECI LOGO.jpg" alt="qecilogo" />
      <nav>
        <a href="dashboard.php" class="active"><i class="bx bxs-dashboard"> </i> Dashboard</a>
        <a href="forms.php"><i class="bx bx-notepad"></i> Form</a>
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
        <div class="header-left">
          <div class="search-bar">
            <svg viewBox="0 0 24 24">
              <path d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 1 0 3 10.5a7.5 7.5 0 0 0 13.65 6.15z" stroke="#888"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none" />
            </svg>
          </div>
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
          <a href="/DORCFSRF/pages/click_button/aboutus.php" title="About"><i
              class="bx bx-globe bx-flip-vertical"></i></a>
          <div class="user-menu-container">
            <div class="profile-icon-wrapper" id="profile-toggle" title="Profile">
              <?php if ($profile_image_url): ?>
                <img src="<?php echo htmlspecialchars($profile_image_url); ?>?t=<?php echo time(); ?>" alt="Profile"
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
                <p class="profile-id"><?php echo htmlspecialchars($student_id); ?></p>
              </div>
              <div class="card-actions">
                <a href="settings.php" class="action-btn action-btn-primary">Profile</a>
                <a href="/DORCFSRF/pages/logout.php" class="action-btn action-btn-secondary">Sign Out</a>
              </div>
              <script src="/DORCFSRF/pages/script/dropdown.js"></script>
            </div>
          </div>
        </div>
      </header>

      <main class="main-content">
        <div class="background-overlay"></div>

        <section class="status-cards">
          <div class="card">
            <div class="icon">📄</div>
            <h3>Clearance Status</h3>
            <div class="main-number"><?php echo $clearance_cleared; ?>/<?php echo $clearance_total; ?></div>
            <div class="status-text" style="font-size: 18px; font-weight: 700">
              <?php echo ($clearance_cleared === $clearance_total) ? 'Cleared' : 'Cleared'; ?>
            </div>
          </div>
          <div class="card">
            <div class="icon">💳</div>
            <h3>Payment Transaction</h3>
            <div class="status-text" style="font-size: 20px; font-weight: 700">
              <?php echo strtoupper($payment_status); ?>
            </div>
          </div>
          <div class="card">
            <div class="icon">📚</div>
            <h3>Scholastic Record Status</h3>
            <div class="status-text" style="font-size: 18px; font-weight: 700"><?php echo $scholastic_status; ?></div>
          </div>
          <div class="card">
            <div class="icon">🗂️</div>
            <h3>Completion Report</h3>
            <div class="status-text" style="font-size: 18px; font-weight: 700"><?php echo $completion_status; ?></div>
          </div>
        </section>

        <div class="process-setup" id="openGuide">
          <i class='bx bx-cog'></i>
          <span style="color: #FFF; font-size: larger;">Request Process Guide</span>
        </div>

        <div class="guide-modal" id="guideModal">
          <div class="modal-content">
            <span class="close-btn">&times;</span>

            <div class="settings-guide-header">
              <i class="bx bx-file" style="font-size: 3rem; color: #3498db;"></i>
              <h2>Request Process Guide</h2>
              <p>Follow the steps below to complete your school records request.</p>
            </div>

            <div class="guide-steps">
              <div class="guide-card">
                <div class="step-number">Step 1</div>
                <i class="bx bx-folder-open"></i>
                <h3>Fill Out Form</h3>
              </div>
              <div class="guide-card">
                <div class="step-number">Step 2</div>
                <i class="bx bx-check-circle"></i>
                <h3>Check Status</h3>
              </div>
              <div class="guide-card">
                <div class="step-number">Step 3</div>
                <i class="bx bx-coin-stack"></i>
                <h3>Payment</h3>
              </div>
              <div class="guide-card">
                <div class="step-number">Step 4</div>
                <i class="bx bx-webcam"></i>
                <h3>Monitor</h3>
              </div>
              <div class="guide-card">
                <div class="step-number">Step 5</div>
                <i class="bx bx-archive-in"></i>
                <h3>Receive Report</h3>
              </div>
            </div>
          </div>
        </div>

        <section class="clearance-section">
          <h2>Clearance Department <a href="clearance.php" class="btn">See All</a></h2>
          <p class="section-description">These are the clearance units that review and verify your request. Each office
            listed below must clear the requirements that apply to your scholastic record release.</p>
          <div class="clearance-cards">
            <div class="clearance-card">
              <i class="bx bx-book card-icon"></i>
              <div class="card-title">Thesis</div>
              <div class="subtext"><?php echo htmlspecialchars($thesis_status); ?></div>
              <div class="person-info">
                <img src="https://randomuser.me/api/portraits/women/65.jpg" alt="Officer" />
                <span>Maritess V. Panal</span>
              </div>
              <button><a
                  href="/DORCFSRF/pages/Click_Button/clearance_details.php?item=thesis&clearance_id=<?php echo htmlspecialchars($clearance_id ?? ''); ?>"
                  style="text-decoration: none; color: inherit;">View Details</a></button>
            </div>

            <div class="clearance-card">
              <i class="bx bx-library card-icon"></i>
              <div class="card-title">Librarian</div>
              <div class="subtext"><?php echo htmlspecialchars($librarian_status); ?></div>
              <div class="person-info">
                <img src="https://randomuser.me/api/portraits/women/33.jpg" alt="Officer" />
                <span>Maritess V. Panal</span>
              </div>
              <button><a
                  href="/DORCFSRF/pages/Click_Button/clearance_details.php?item=librarian&clearance_id=<?php echo htmlspecialchars($clearance_id ?? ''); ?>"
                  style="text-decoration: none; color: inherit;">View Details</a></button>
            </div>

            <div class="clearance-card">
              <i class="bx bx-money card-icon"></i>
              <div class="card-title">Finance Officer</div>
              <div class="subtext"><?php echo htmlspecialchars($finance_status); ?></div>
              <div class="person-info">
                <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="Officer" />
                <span>Chin-Chin O. Devilles</span>
              </div>
              <button><a
                  href="/DORCFSRF/pages/Click_Button/clearance_details.php?item=finance&clearance_id=<?php echo htmlspecialchars($clearance_id ?? ''); ?>"
                  style="text-decoration: none; color: inherit;">View Details</a></button>
            </div>

            <div class="clearance-card hidden-card">
              <i class="bx bx-user-check card-icon"></i>
              <div class="card-title">Admission Officer</div>
              <div class="subtext"><?php echo htmlspecialchars($admission_status); ?></div>
              <div class="person-info">
                <img src="https://randomuser.me/api/portraits/men/42.jpg" alt="Officer" />
                <span>Azerelle M. Jalapan / Russel R. Ortiz</span>
              </div>
              <button><a
                  href="/DORCFSRF/pages/Click_Button/clearance_details.php?item=admission&clearance_id=<?php echo htmlspecialchars($clearance_id ?? ''); ?>"
                  style="text-decoration: none; color: inherit;">View Details</a></button>
            </div>

            <div class="clearance-card hidden-card">
              <i class="bx bx-test-tube card-icon"></i>
              <div class="card-title">Lab / ComLab / ID</div>
              <div class="subtext"><?php echo htmlspecialchars($lab_status); ?></div>
              <div class="person-info">
                <img src="https://randomuser.me/api/portraits/men/75.jpg" alt="Officer" />
                <span>Rey Niel T. Centeno</span>
              </div>
              <button><a
                  href="/DORCFSRF/pages/Click_Button/clearance_details.php?item=lab&clearance_id=<?php echo htmlspecialchars($clearance_id ?? ''); ?>"
                  style="text-decoration: none; color: inherit;">View Details</a></button>
            </div>

            <div class="clearance-card hidden-card">
              <i class="bx bx-money card-icon"></i>
              <div class="card-title">School Cashier</div>
              <div class="subtext"><?php echo htmlspecialchars($cashier_status); ?></div>
              <div class="person-info">
                <img src="https://randomuser.me/api/portraits/women/67.jpg" alt="Officer" />
                <span>Ma. Sharon C. Alvano</span>
              </div>
              <button><a
                  href="/DORCFSRF/pages/Click_Button/clearance_details.php?item=cashier&clearance_id=<?php echo htmlspecialchars($clearance_id ?? ''); ?>"
                  style="text-decoration: none; color: inherit;">View Details</a></button>
            </div>

            <div class="clearance-card hidden-card">
              <i class="bx bx-package card-icon"></i>
              <div class="card-title">Property Custodian</div>
              <div class="subtext">PENDING</div>
              <div class="person-info">
                <img src="https://randomuser.me/api/portraits/men/52.jpg" alt="Officer" />
                <span>Jeck A. Albilar</span>
              </div>
              <button><a
                  href="/DORCFSRF/pages/Click_Button/clearance_details.php?item=property&clearance_id=<?php echo htmlspecialchars($clearance_id ?? ''); ?>"
                  style="text-decoration: none; color: inherit;">View Details</a></button>
            </div>

            <div class="clearance-card hidden-card">
              <i class="bx bx-support card-icon"></i>
              <div class="card-title">Student Services Coordinator</div>
              <div class="subtext">PENDING</div>
              <div class="person-info">
                <img src="https://randomuser.me/api/portraits/women/51.jpg" alt="Officer" />
                <span>Kristine A. de la Cruz</span>
              </div>
              <button><a
                  href="/DORCFSRF/pages/Click_Button/clearance_details.php?item=student_services&clearance_id=<?php echo htmlspecialchars($clearance_id ?? ''); ?>"
                  style="text-decoration: none; color: inherit;">View Details</a></button>
            </div>

            <div class="clearance-card hidden-card">
              <i class="bx bx-graduation card-icon"></i>
              <div class="card-title">Scholarship Coordinator</div>
              <div class="subtext">PENDING</div>
              <div class="person-info">
                <img src="https://randomuser.me/api/portraits/men/58.jpg" alt="Officer" />
                <span>Mark Allan G. Tabalan</span>
              </div>
              <button><a
                  href="/DORCFSRF/pages/Click_Button/clearance_details.php?item=scholarship&clearance_id=<?php echo htmlspecialchars($clearance_id ?? ''); ?>"
                  style="text-decoration: none; color: inherit;">View Details</a></button>
            </div>

            <div class="clearance-card hidden-card">
              <i class="bx bx-heart card-icon"></i>
              <div class="card-title">Guidance Counselor</div>
              <div class="subtext">PENDING</div>
              <div class="person-info">
                <img src="https://randomuser.me/api/portraits/women/60.jpg" alt="Officer" />
                <span>Shirley Margarette O. Espiritu</span>
              </div>
              <button><a
                  href="/DORCFSRF/pages/Click_Button/clearance_details.php?item=guidance&clearance_id=<?php echo htmlspecialchars($clearance_id ?? ''); ?>"
                  style="text-decoration: none; color: inherit;">View Details</a></button>
            </div>

            <div class="clearance-card hidden-card">
              <i class="bx bx-file card-icon"></i>
              <div class="card-title">Records Officer</div>
              <div class="subtext"><?php echo htmlspecialchars($records_status); ?></div>
              <div class="person-info">
                <img src="https://randomuser.me/api/portraits/women/45.jpg" alt="Officer" />
                <span>Janie Ruth A. Lumbera</span>
              </div>
              <button><a
                  href="/DORCFSRF/pages/Click_Button/clearance_details.php?item=records_officer&clearance_id=<?php echo htmlspecialchars($clearance_id ?? ''); ?>"
                  style="text-decoration: none; color: inherit;">View Details</a></button>
            </div>

            <div class="clearance-card hidden-card">
              <i class="bx bx-library card-icon"></i>
              <div class="card-title">Registrar</div>
              <div class="subtext">PENDING</div>
              <div class="person-info">
                <img src="https://randomuser.me/api/portraits/women/64.jpg" alt="Officer" />
                <span>Carmencita L. Aragon</span>
              </div>
              <button><a
                  href="/DORCFSRF/pages/Click_Button/clearance_details.php?item=registrar&clearance_id=<?php echo htmlspecialchars($clearance_id ?? ''); ?>"
                  style="text-decoration: none; color: inherit;">View Details</a></button>
            </div>

            <div class="clearance-card hidden-card">
              <i class="bx bx-crown card-icon"></i>
              <div class="card-title">College Dean</div>
              <div class="subtext">PENDING</div>
              <div class="person-info">
                <img src="https://randomuser.me/api/portraits/women/69.jpg" alt="Officer" />
                <span>Carmencita L. Aragon</span>
              </div>
              <button><a
                  href="/DORCFSRF/pages/Click_Button/clearance_details.php?item=dean&clearance_id=<?php echo htmlspecialchars($clearance_id ?? ''); ?>"
                  style="text-decoration: none; color: inherit;">View Details</a></button>
            </div>
          </div>
          <div class="view-more-container">
            <button id="viewMoreBtn" class="view-more-btn">View More Departments</button>
          </div>
        </section>


      </main>
    </div>
  </div>

  <script>
    const modal = document.getElementById("guideModal");
    const btn = document.getElementById("openGuide");
    const span = document.getElementsByClassName("close-btn")[0];

    // Pag click sa floating button, lalabas ang modal
    btn.onclick = function () {
      modal.style.display = "flex";
    }

    // Pag click sa 'x', mawawala ang modal
    span.onclick = function () {
      modal.style.display = "none";
    }

    // Pag click sa labas ng modal, mawawala rin ito
    window.onclick = function (event) {
      if (event.target == modal) {
        modal.style.display = "none";
      }
    }

    // View More functionality for clearance cards
    const viewMoreBtn = document.getElementById('viewMoreBtn');
    const hiddenCards = document.querySelectorAll('.hidden-card');

    viewMoreBtn.addEventListener('click', function () {
      hiddenCards.forEach(card => {
        card.style.display = card.style.display === 'block' ? 'none' : 'block';
      });

      // Change button text
      this.textContent = this.textContent === 'View More Departments' ? 'View Less' : 'View More Departments';
    });

  </script>
  <script src="/DORCFSRF/pages/script/notifications.js"></script>
  <script src="/DORCFSRF/pages/script/prevent-back-button.js"></script>
</body>

</html>
