<?php
ini_set('session.gc_maxlifetime', 31536000);
session_set_cookie_params(31536000);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    http_response_code(401);
    exit;
}

$student_id = $_SESSION['user_id'];

if (empty(trim($student_id))) {
    echo json_encode(['success' => false, 'message' => 'Invalid session']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data provided']);
    http_response_code(400);
    exit;
}

// Validate required fields
$required_fields = ['type_schdocs', 'num_copies', 'type_copy', 'doc_amount', 'tot_amount', 'payment_method', 'payment_date'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || trim($data[$field]) === '') {
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        http_response_code(400);
        exit;
    }
}

// Database connection
include __DIR__ . '/../db_connect.php';

try {
    // Check if the student has filled out their form in student_information_database
    $check_stmt = $conn->prepare("SELECT student_id FROM student_information_database WHERE student_id = ?");
    $check_stmt->bind_param("s", $student_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $has_form = $check_result->num_rows > 0;
    $check_stmt->close();

    if (!$has_form) {
        echo json_encode([
            'success' => false,
            'message' => 'Please fill out and submit your Request Form (in the Form tab) before proceeding with the payment transaction.'
        ]);
        exit;
    }

    // Generate order reference number
    $or_num = 'OR' . date('YmdHis') . mt_rand(1000, 9999);

    // Prepare the insert statement
    $stmt = $conn->prepare(
        "INSERT INTO payment_record_database 
        (or_num, student_id, type_schdocs, num_copies, type_copy, doc_amount, tot_amount, payment_method, payment_date) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    // Bind parameters
    $stmt->bind_param(
        'sssissdss',
        $or_num,
        $student_id,
        $data['type_schdocs'],
        $data['num_copies'],
        $data['type_copy'],
        $data['doc_amount'],
        $data['tot_amount'],
        $data['payment_method'],
        $data['payment_date']
    );

    // Execute the statement
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $stmt->close();

    // Create unified payment notification for all roles
    include __DIR__ . '/../api/notification_system.php';
    try {
        $student_name_stmt = $conn->prepare("SELECT CONCAT(student_firstname, ' ', student_lastname) as full_name FROM student_information_database WHERE student_id = ?");
        $student_name_stmt->bind_param('s', $student_id);
        $student_name_stmt->execute();
        $student_name_result = $student_name_stmt->get_result();
        $student_full_name = 'Unknown Student';
        if ($student_name_result->num_rows > 0) {
            $name_row = $student_name_result->fetch_assoc();
            $student_full_name = $name_row['full_name'];
        }
        $student_name_stmt->close();
        
        // Create notification for all roles (Admin, Staff, Student)
        createPaymentNotification($conn, $student_id, $student_full_name, $data['tot_amount'], 'submitted');
    } catch (Exception $e) {
        // Notification failed but payment was saved - don't block
        error_log("Payment notification failed: " . $e->getMessage());
    }

    // Fetch student's email for notification
    $email_stmt = $conn->prepare("SELECT email FROM user_database WHERE user_id = ?");
    $email_stmt->bind_param('s', $student_id);
    $email_stmt->execute();
    $email_result = $email_stmt->get_result();
    $student_email = null;
    if ($email_result->num_rows > 0) {
        $row = $email_result->fetch_assoc();
        $student_email = $row['email'];
    }
    $email_stmt->close();

    // Send email notification if email is available
    if ($student_email) {
        $phpmailer_path = $_SERVER['DOCUMENT_ROOT'] . '/DORCFSRF/vendor/PHPMailer/PHPMailer.php';
        $smtp_path = $_SERVER['DOCUMENT_ROOT'] . '/DORCFSRF/vendor/PHPMailer/SMTP.php';
        $exception_path = $_SERVER['DOCUMENT_ROOT'] . '/DORCFSRF/vendor/PHPMailer/Exception.php';

        if (file_exists($phpmailer_path) && file_exists($smtp_path) && file_exists($exception_path)) {
            require $phpmailer_path;
            require $smtp_path;
            require $exception_path;

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);

            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'margelsaavedra@gmail.com'; // Your Gmail address
                $mail->Password = 'rsvuqncvnoyrbkfw';    // Your Gmail app password
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Recipients
                $mail->setFrom('margelsaavedra@gmail.com', 'Quezonian Educational College');
                $mail->addAddress($student_email);

                // Content
                $mail->isHTML(false);
                $mail->Subject = 'Payment Confirmation - Quezonian Educational College';
                $mail->Body = "
Dear Student,

Your payment has been successfully recorded.

Transaction Details:
- Order Reference: $or_num
- Document: {$data['type_schdocs']}
- Number of Copies: {$data['num_copies']}
- Type: {$data['type_copy']}
- Total Amount: ₱{$data['tot_amount']}
- Payment Method: {$data['payment_method']}
- Date: {$data['payment_date']}

Please proceed with the payment process.

Thank you,
Quezonian Educational College
                ";

                $mail->send();
            } catch (Exception $e) {
                // Log error but don't fail the payment
                error_log("Email sending failed: " . $mail->ErrorInfo);
            }
        } else {
            error_log("PHPMailer not found — email notification skipped for $student_email");
        }
    }

    // Log payment
    include_once $_SERVER['DOCUMENT_ROOT'] . '/DORCFSRF/api/log_audit.php';
    $student_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Student';
    log_activity($conn, $student_id, $student_name, 'Student', 'Payment', 'Save Payment Record: ' . $or_num, 'Success', 'Payment', $or_num);

    $conn->close();

    echo json_encode([
        'success' => true,
        'message' => 'Payment record saved successfully',
        'transaction_id' => $or_num
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
    http_response_code(500);
}
?>