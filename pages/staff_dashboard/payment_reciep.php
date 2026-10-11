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
$full_name = $_SESSION['full_name'] ?? $_SESSION['username'];
$staff_id = $_SESSION['user_id'];

// Get student_id from URL parameters
$student_id = $_GET['student_id'] ?? null;
$or_number = $_GET['or'] ?? null;

// Initialize default values
$student_data = [
    'name' => 'N/A',
    'student_id' => 'N/A',
    'payment_date' => date('F j, Y'),
    'payment_method' => 'Pay at the Counter',
    'document_type' => 'Summary of Grades',
    'copy_type' => 'Original',
    'num_copies' => 1,
    'total_amount' => '100.00'
];

// Fetch student payment data if student_id is provided
if ($student_id) {
    $stmt = $conn->prepare("
        SELECT 
            p.or_num,
            p.student_id,
            p.type_schdocs,
            p.num_copies,
            p.type_copy,
            p.tot_amount,
            p.payment_method,
            p.payment_date,
            CONCAT(s.student_firstname, ' ', s.student_lastname) as full_name
        FROM payment_record_database p
        LEFT JOIN student_information_database s ON p.student_id = s.student_id
        WHERE p.student_id = ? AND (p.or_num = ? OR ? IS NULL)
        ORDER BY p.payment_date DESC
        LIMIT 1
    ");
    $stmt->bind_param('sss', $student_id, $or_number, $or_number);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $student_data = [
            'name' => htmlspecialchars($row['full_name'] ?? 'N/A'),
            'student_id' => htmlspecialchars($row['student_id'] ?? 'N/A'),
            'payment_date' => $row['payment_date'] ? date('F j, Y', strtotime($row['payment_date'])) : date('F j, Y'),
            'payment_method' => htmlspecialchars($row['payment_method'] ?? 'Pay at the Counter'),
            'document_type' => htmlspecialchars($row['type_schdocs'] ?? 'Summary of Grades'),
            'copy_type' => htmlspecialchars($row['type_copy'] ?? 'Original'),
            'num_copies' => intval($row['num_copies'] ?? 1),
            'total_amount' => number_format(floatval($row['tot_amount'] ?? '100.00'), 2)
        ];
    }
    $stmt->close();
}

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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Transaction - Receipt</title>

    <link rel="stylesheet" href="/DORCFSRF/css/dashboard.css" />
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
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
                <a href="staff_payment_transaction.php" class="active"><i
                        class="bx bx-coin-stack"></i> Payment Transaction</a>
                <a href="staff_scholastic_record.php"><i class="bx bx-bar-chart-alt-2"></i>
                    Scholastic Record Status</a>
                <a href="staff_completion_report.php"><i class="bx bxs-calendar"></i> Completion
                    Report</a>
                <p>SETTINGS</p>
                <a href="staff_account_settings.php"><i class="bx bx-cog"></i> Account
                    Settings</a>
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
                <div class="page-content">
                    <h3 class="breadcrumb2" style="font-size: 20px; margin-bottom: 10px; color: white;"><b>Home</b> >Payment
                        Transaction</h3>

                    <div class="receipt-card" style="position: relative;">
                        <button class="close-button" style="position: absolute; top: 20px; right: 20px; background: none; border: none; font-size: 24px; cursor: pointer; color: #333;"><a href="staff_payment_transaction.php" style="text-decoration: none; color: #333;"><i class="fas fa-times"></i></a></button>
                        <div class="receipt-header">
                            <img src="/DORCFSRF/assets/QECI LOGO.jpg" alt="QECI Logo" style="display: block; margin: 0 auto 18px; max-width: 140px;" />
                            <h2>Online Request for Clearance and Scholastic Record</h2>
                            <h1>Payment Receipt</h1>
                        </div>
                        <div class="receipt-body">
                            <div class="info-section1 student-info-grid">
                                <h3>Student Information</h3>
                                <div class="info-row">
                                    <span>Name:</span>
                                    <strong id="studentNameReceipt"><?php echo $student_data['name']; ?></strong>
                                </div>
                                <div class="info-row">
                                    <span>Student ID:</span>
                                    <strong><?php echo $student_data['student_id']; ?></strong>
                                </div>
                                <div class="info-row">
                                    <span>OR Number:</span>
                                    <strong><?php echo htmlspecialchars($or_number ?? 'N/A'); ?></strong>
                                </div>
                                <div class="info-row">
                                    <span>Date:</span>
                                    <strong><?php echo $student_data['payment_date']; ?></strong>
                                </div>
                                <div class="info-row">
                                    <span>Payment Method:</span>
                                    <strong><?php echo $student_data['payment_method']; ?></strong>
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
                                        <span><?php echo $student_data['document_type']; ?></span>
                                        <span><?php echo $student_data['copy_type']; ?></span>
                                        <span><?php echo $student_data['num_copies']; ?></span>
                                        <span class="align-right"><?php echo $student_data['total_amount']; ?> Php</span>
                                    </div>
                                </div>
                            </div>

                            <div class="receipt-total">
                                <span>TOTAL</span>
                                <span class="total-amount" id="totalAmount"><?php echo $student_data['total_amount']; ?> Php</span>
                            </div>

                        </div>

                        <div class="receipt-footer">
                            <button class="download-btn" id="downloadBtn">
                                <i class='bx bxs-download'></i> DOWNLOAD
                            </button>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script>
        // No need for JavaScript to set student name anymore - it's done server-side
    </script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        const downloadButton = document.getElementById('downloadBtn');
        if (downloadButton) {
            downloadButton.addEventListener('click', function () {
                const receiptElement = document.querySelector('.receipt-card');
                if (!receiptElement) {
                    alert('Receipt not found');
                    return;
                }

                const orNumber = '<?php echo htmlspecialchars($or_number ?? 'receipt'); ?>'.replace(/[^A-Za-z0-9_-]/g, '') || 'receipt';
                const filename = `payment-receipt-${orNumber}.pdf`;

                // Create a new window with the receipt content
                const printWindow = window.open('', '_blank', 'width=800,height=600');
                if (!printWindow) {
                    alert('Please allow popups for this site to download the receipt.');
                    return;
                }

                // Get the receipt HTML and remove buttons that should not appear in the downloaded file
                const receiptHTML = receiptElement.outerHTML;
                const closeButton = receiptElement.querySelector('.close-button');
                const downloadBtn = receiptElement.querySelector('.download-btn');
                let cleanHTML = receiptHTML;
                if (closeButton) {
                    cleanHTML = cleanHTML.replace(closeButton.outerHTML, '');
                }
                if (downloadBtn) {
                    cleanHTML = cleanHTML.replace(downloadBtn.closest('.receipt-footer')?.outerHTML || downloadBtn.outerHTML, '');
                }

                // Create the print page HTML
                const printHTML = `
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Payment Receipt</title>
                        <link rel="stylesheet" href="/DORCFSRF/css/dashboard.css" />
                        <style>
                            body { margin: 0; padding: 20px; background: white; }
                            .receipt-card { box-shadow: none; border: none; margin: 0 auto; max-width: 900px; }
                            .close-button { display: none !important; }
                        </style>
                    </head>
                    <body>
                        ${cleanHTML}
                    </body>
                    </html>
                `;

                printWindow.document.write(printHTML);
                printWindow.document.close();

                // Wait for the window to load, then generate PDF
                printWindow.onload = function() {
                    const element = printWindow.document.querySelector('.receipt-card');
                    if (element) {
                        const opt = {
                            margin: [0.2, 0.2, 0.2, 0.2],
                            filename: filename,
                            image: { type: 'jpeg', quality: 1 },
                            html2canvas: {
                                scale: 3,
                                useCORS: true,
                                scrollY: 0
                            },
                            jsPDF: {
                                unit: 'in',
                                format: 'a4',
                                orientation: 'portrait'
                            }
                        };

                        html2pdf().set(opt).from(element).save().then(() => {
                            printWindow.close();
                        }).catch((error) => {
                            console.error('PDF generation failed:', error);
                            printWindow.close();
                        });
                    } else {
                        printWindow.close();
                    }
                };
            });
        }
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
<script src="/DORCFSRF/pages/script/prevent-back-button.js"></script>
</body>

</html>









