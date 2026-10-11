<?php
include 'db_connect.php';

$depts = [
    'records_officer_status',
    'computer_lab_status',
    'librarian_status',
    'finance_officer_status',
    'thesis_status',
    'admission_officer_status',
    'school_cashier_status',
    'property_custodian_status',
    'student_services_status',
    'scholarship_coordinator_status',
    'guidance_counselor_status',
    'registrar_status',
    'cbrc_status'
];

$res = $conn->query("SELECT * FROM student_clearance_database_bk_20260507_000807");
$count = 0;
while($row = $res->fetch_assoc()) {
    $student_id = $row['student_id'];
    foreach($depts as $dept) {
        $status = $row[$dept];
        if(!empty($status) && $status !== 'Pending') {
            $stmt = $conn->prepare("INSERT INTO clearance_approvals_log (student_id, department_field, status) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE status=VALUES(status)");
            if($stmt) {
                $stmt->bind_param('sss', $student_id, $dept, $status);
                $stmt->execute();
                $count++;
            }
        }
    }
}
echo "Inserted $count log entries from backup.";
?>
