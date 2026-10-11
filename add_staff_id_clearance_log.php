<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Add staff_id to clearance_approvals_log and map existing staff_name</h2>";
echo "<pre>";

include __DIR__ . '/db_connect.php';

// Ensure table exists
$tbl = $conn->query("SHOW TABLES LIKE 'clearance_approvals_log'");
if (!($tbl && $tbl->num_rows > 0)) {
    echo "Table 'clearance_approvals_log' does not exist. Nothing to do.\n";
    exit;
}

// Backup table if no backup exists
$bkCheck = $conn->query("SHOW TABLES LIKE 'clearance_approvals_log_backup'");
if (!($bkCheck && $bkCheck->num_rows > 0)) {
    $bq = "CREATE TABLE clearance_approvals_log_backup AS SELECT * FROM clearance_approvals_log";
    if ($conn->query($bq) === TRUE) {
        echo "✓ Backup table 'clearance_approvals_log_backup' created.\n";
    } else {
        echo "⚠ Failed to create backup: " . $conn->error . "\n";
    }
} else {
    echo "✓ Backup table already exists.\n";
}

// Add staff_id column if missing
$colRes = $conn->query("SHOW COLUMNS FROM clearance_approvals_log LIKE 'staff_id'");
if (!($colRes && $colRes->num_rows > 0)) {
    $alter = "ALTER TABLE clearance_approvals_log ADD COLUMN staff_id VARCHAR(50) DEFAULT NULL";
    if ($conn->query($alter) === TRUE) {
        echo "✓ Column 'staff_id' added to clearance_approvals_log.\n";
    } else {
        echo "⚠ Failed to add column 'staff_id': " . $conn->error . "\n";
    }
} else {
    echo "✓ Column 'staff_id' already exists.\n";
}

// Ensure the column `staff_name` exists before attempting to map it
$colChk = $conn->query("SHOW COLUMNS FROM clearance_approvals_log LIKE 'staff_name'");
if (!($colChk && $colChk->num_rows > 0)) {
    echo "Table 'clearance_approvals_log' does not have a 'staff_name' column — nothing to map.\n";
    exit;
}

// Fetch distinct staff_name values that are unmapped
$namesRes = $conn->query("SELECT DISTINCT TRIM(staff_name) AS staff_name FROM clearance_approvals_log WHERE (staff_id IS NULL OR staff_id = '') AND COALESCE(staff_name,'') <> ''");
if (!($namesRes)) {
    echo "⚠ Failed to query distinct staff_name: " . $conn->error . "\n";
    exit;
}

$mapped = 0; $unmapped = 0;
while ($nr = $namesRes->fetch_assoc()) {
    $sname = $nr['staff_name'];
    if (empty($sname)) continue;

    // 1) Exact match (case-insensitive)
    $stmt = $conn->prepare("SELECT user_id FROM user_database WHERE LOWER(TRIM(full_name)) = LOWER(TRIM(?)) LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('s', $sname);
        $stmt->execute();
        $r = $stmt->get_result();
        if ($row = $r->fetch_assoc()) {
            $uid = $row['user_id'];
            $stmt->close();
            $ustmt = $conn->prepare("UPDATE clearance_approvals_log SET staff_id = ? WHERE TRIM(staff_name) = ?");
            if ($ustmt) { $ustmt->bind_param('ss', $uid, $sname); $ustmt->execute(); $ustmt->close(); }
            echo "Mapped '$sname' -> $uid (exact match)\n";
            $mapped++;
            continue;
        }
        $stmt->close();
    }

    // 2) LIKE match (full_name contains staff_name)
    $stmt2 = $conn->prepare("SELECT user_id FROM user_database WHERE LOWER(full_name) LIKE LOWER(CONCAT('%', ?, '%')) LIMIT 1");
    if ($stmt2) {
        $stmt2->bind_param('s', $sname);
        $stmt2->execute();
        $r2 = $stmt2->get_result();
        if ($row2 = $r2->fetch_assoc()) {
            $uid = $row2['user_id'];
            $stmt2->close();
            $ustmt = $conn->prepare("UPDATE clearance_approvals_log SET staff_id = ? WHERE TRIM(staff_name) = ?");
            if ($ustmt) { $ustmt->bind_param('ss', $uid, $sname); $ustmt->execute(); $ustmt->close(); }
            echo "Mapped '$sname' -> $uid (LIKE match)\n";
            $mapped++;
            continue;
        }
        $stmt2->close();
    }

    // 3) Try last name token
    $parts = preg_split('/\s+/', $sname);
    $last = end($parts);
    if ($last) {
        $stmt3 = $conn->prepare("SELECT user_id, full_name FROM user_database WHERE LOWER(full_name) LIKE LOWER(CONCAT('%', ?, '%')) LIMIT 1");
        if ($stmt3) {
            $stmt3->bind_param('s', $last);
            $stmt3->execute();
            $r3 = $stmt3->get_result();
            if ($row3 = $r3->fetch_assoc()) {
                $uid = $row3['user_id'];
                $matched_full = $row3['full_name'];
                $stmt3->close();
                $ustmt = $conn->prepare("UPDATE clearance_approvals_log SET staff_id = ? WHERE TRIM(staff_name) = ?");
                if ($ustmt) { $ustmt->bind_param('ss', $uid, $sname); $ustmt->execute(); $ustmt->close(); }
                echo "Mapped '$sname' -> $uid (last-name '$last' matched '$matched_full')\n";
                $mapped++;
                continue;
            }
            $stmt3->close();
        }
    }

    // Not mapped
    echo "Could not map '$sname' -> (no matching staff user)\n";
    $unmapped++;
}

echo "\nSummary: Mapped = $mapped, Unmapped = $unmapped\n";

// Also update rows where staff_id is still empty but staff_name is empty - leave null

echo "\nDone.\n";
echo "</pre>";
?>
