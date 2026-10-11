<?php
ini_set('session.gc_maxlifetime', 31536000);
session_set_cookie_params(31536000);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') {
    header('Location: /DORCFSRF/pages/login_form/index.php');
    exit;
}
$full_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Admin';
$admin_id = $_SESSION['user_id'];

// Get stored profile image
$profile_image = '/DORCFSRF/assets/admin_profiles/' . $admin_id . '_profile.jpg';
$profile_image_alt = '/DORCFSRF/assets/admin_profiles/' . $admin_id . '_profile.png';
$profile_image_alt2 = '/DORCFSRF/assets/admin_profiles/' . $admin_id . '_profile.gif';

// Check which image exists
if (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image)) {
    $profile_image_url = $profile_image;
} elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image_alt)) {
    $profile_image_url = $profile_image_alt;
} elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image_alt2)) {
    $profile_image_url = $profile_image_alt2;
} else {
    $profile_image_url = null;
}
?>
    <?php
    // Fetch payment and student info based on query params
    include_once __DIR__ . '/../../db_connect.php';

    $orNum = trim($_GET['or'] ?? '');
    $studentIdParam = trim($_GET['student_id'] ?? '');
    $studentName = '';
    $studentId = '';
    $paymentMethod = '';
    $paymentDate = '';
    $amount = '';
    $or_display = '';

    if ($orNum !== '') {
        $stmt = $conn->prepare("SELECT pr.or_num, pr.student_id, pr.tot_amount, pr.payment_method, pr.payment_date, pr.type_schdocs, pr.num_copies, pr.type_copy, si.student_firstname, si.student_middlename, si.student_lastname, ud.full_name FROM payment_record_database pr LEFT JOIN student_information_database si ON pr.student_id = si.student_id LEFT JOIN user_database ud ON pr.student_id = ud.user_id WHERE pr.or_num = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $orNum);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();
            if ($row) {
                $studentId = $row['student_id'];
                if (!empty($row['full_name'])) {
                    $studentName = $row['full_name'];
                } else {
                    $studentName = trim(($row['student_firstname'] ?? '') . ' ' . ($row['student_middlename'] ?? '') . ' ' . ($row['student_lastname'] ?? ''));
                }
                $paymentMethod = $row['payment_method'] ?? '';
                $paymentDate = $row['payment_date'] ?? '';
                $amount = isset($row['tot_amount']) ? number_format((float)$row['tot_amount'],2) : '';
                $docName = $row['type_schdocs'] ?? '';
                $docCopies = $row['num_copies'] ?? '';
                $docCopyType = $row['type_copy'] ?? '';
                $or_display = $row['or_num'];
            }
            $stmt->close();
        }
    } elseif ($studentIdParam !== '') {
        // Get latest payment for student
        $stmt = $conn->prepare("SELECT pr.or_num, pr.student_id, pr.tot_amount, pr.payment_method, pr.payment_date, pr.type_schdocs, pr.num_copies, pr.type_copy, si.student_firstname, si.student_middlename, si.student_lastname, ud.full_name FROM payment_record_database pr LEFT JOIN student_information_database si ON pr.student_id = si.student_id LEFT JOIN user_database ud ON pr.student_id = ud.user_id WHERE pr.student_id = ? ORDER BY pr.payment_date DESC LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $studentIdParam);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();
            if ($row) {
                $studentId = $row['student_id'];
                if (!empty($row['full_name'])) {
                    $studentName = $row['full_name'];
                } else {
                    $studentName = trim(($row['student_firstname'] ?? '') . ' ' . ($row['student_middlename'] ?? '') . ' ' . ($row['student_lastname'] ?? ''));
                }
                $paymentMethod = $row['payment_method'] ?? '';
                $paymentDate = $row['payment_date'] ?? '';
                $amount = isset($row['tot_amount']) ? number_format((float)$row['tot_amount'],2) : '';
                $docName = $row['type_schdocs'] ?? '';
                $docCopies = $row['num_copies'] ?? '';
                $docCopyType = $row['type_copy'] ?? '';
                $or_display = $row['or_num'];
            }
            $stmt->close();
        }
    }

    // Format payment date
    if (!empty($paymentDate)) {
        $dt = date_create($paymentDate);
        if ($dt) $paymentDateFormatted = date_format($dt, 'F j, Y, g:i A');
        else $paymentDateFormatted = $paymentDate;
    } else {
        $paymentDateFormatted = date('F j, Y');
    }
    ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Transaction - Receipt</title>

    <link rel="stylesheet" href="/DORCFSRF/css/dashboard.css" />
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
</head>

<body>
    <div class="container">
    <aside class="sidebar">
      <img src="/DORCFSRF/assets/QECI LOGO.jpg" alt="qecilogo" />
      <nav>
        <a href="/DORCFSRF/pages/admin_dashboard/dashboard.php"><i class="bx bxs-dashboard"> </i> Dashboard</a>
        <a href="/DORCFSRF/pages/admin_dashboard/form.php"><i class="bx bx-notepad"></i> Form</a>
        <a href="/DORCFSRF/pages/admin_dashboard/clearance_status.php"><i class="bx bxs-graduation"></i> Clearance Status</a>
        <a href="/DORCFSRF/pages/admin_dashboard/payment_transaction.php" class="active"><i class="bx bx-coin-stack"></i> Payment Transaction</a>
        <a href="/DORCFSRF/pages/admin_dashboard/scholastic_record_status.php"><i class="bx bx-bar-chart-alt-2"></i> Scholastic Record Status</a>
        <a href="/DORCFSRF/pages/admin_dashboard/completion_report.php"><i class="bx bxs-calendar"></i> Completion Report</a>
        <p>SETTINGS</p>
        <a href="/DORCFSRF/pages/admin_dashboard/account_settings.php"><i class="bx bx-cog"></i> Account Settings</a>
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
                    <i class="bx bx-bell"></i>
                    <a href="/DORCFSRF/pages/click_button/aboutus.php"><i class="bx bx-globe bx-flip-vertical"></i></a>
                    <div class="profile-icon-wrapper">
                        <?php if ($profile_image_url): ?>
                            <img src="<?php echo htmlspecialchars($profile_image_url); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;" />
                        <?php else: ?>
                            <i class="bx bxs-user-circle"></i>
                        <?php endif; ?>
                    </div>
                </div>
            </header>

            <main class="main-content">
                <div class="page-content">
                     <h3 class="breadcrumb2"
                     style="font-size: 20px; margin-bottom: 10px;"><b>Home</b> >Payment Transaction</h3>
                     <button onclick="window.location.href='payment_transaction.php'" style="margin-bottom: 16px; background: #4b2c71; color: #fff; border: none; border-radius: 4px; padding: 6px 18px; font-size: 1rem; cursor: pointer;">
                        &#8592; Back to Payment Transaction
                    </button>

                    <div class="receipt-card">
                        <div class="receipt-header">
                            <h2>Online Request for Clearance and Scholastic Record</h2>
                            <h1>Payment Receipt</h1>
                        </div>
                        <div class="receipt-body">
                            <div class="info-section1 student-info-grid">
                                <h3>Student Information</h3>
                                <div class="info-row">
                                        <span>Name:</span>
                                        <strong id="student-name"><?php echo htmlspecialchars($studentName ?: $full_name); ?></strong>
                                    </div>
                                    <div class="info-row">
                                        <span>Student ID:</span>
                                        <strong id="student-or"><?php echo htmlspecialchars($studentId ?: $studentIdParam ?: 'N/A'); ?></strong>
                                    </div>
                                    <div class="info-row">
                                        <span>Date:</span>
                                        <strong><?php echo htmlspecialchars($paymentDateFormatted); ?></strong>
                                    </div>
                                    <div class="info-row">
                                        <span>Payment Method:</span>
                                        <strong><?php echo htmlspecialchars($paymentMethod ?: 'Pay at the Counter'); ?></strong>
                                    </div>
                            </div>

                            <div class="info-section document-details">
                                <h3>Document Details</h3>
                                <div class="table-container">
                                    <div class="table-header">
                                        <span>Documents</span>
                                        <span>Copy</span>
                                        <span>Qty</span>
                                        <span class="align-right">Amount</span>
                                    </div>
                                    <div class="table-row">
                                        <span>Payment (OR: <?php echo htmlspecialchars($or_display ?: $orNum ?: 'N/A'); ?>)</span>
                                        <span>&nbsp;</span>
                                        <span>1</span>
                                        <span class="align-right"><?php echo ($amount !== '') ? htmlspecialchars($amount . ' Php') : '0.00 Php'; ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="receipt-total">
                                <span>TOTAL</span>
                                <span class="total-amount"><?php echo ($amount !== '') ? htmlspecialchars($amount . ' Php') : '0.00 Php'; ?></span>
                            </div>

                        </div>

                        <div class="receipt-footer">
                            <button class="download-btn">
                                <i class='bx bxs-download'></i> DOWNLOAD
                            </button>
                            <script>
                            // Get student name and OR number from query parameters
                            const params = new URLSearchParams(window.location.search);
                            const stuName = params.get('user');
                            const orNum = params.get('or');
                            if (stuName) {
                                document.getElementById('student-name').innerText = decodeURIComponent(stuName);
                            }
                            if (orNum) {
                                document.getElementById('student-or').innerText = orNum;
                            }
                            </script>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
<script src="/DORCFSRF/pages/script/prevent-back-button.js"></script>
</body>

</html>









