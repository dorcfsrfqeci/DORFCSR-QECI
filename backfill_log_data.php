<?php
include 'db_connect.php';

$query = "UPDATE student_clearance_database sc
          JOIN clearance_approvals_log log ON sc.student_id = log.student_id
          SET 
            sc.studentrecord_id = log.studentrecord_id,
            sc.or_num = log.or_num,
            sc.contact_num = log.contact_num,
            sc.release_date = log.release_date,
            sc.remarks = log.remarks,
            sc.received_by = log.received_by,
            sc.scholasticrec_stat = log.scholasticrec_stat";

if ($conn->query($query) === TRUE) {
    echo "Existing records successfully updated with log data.\n";
} else {
    echo "Error updating records: " . $conn->error . "\n";
}
?>
