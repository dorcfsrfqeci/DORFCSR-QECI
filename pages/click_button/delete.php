<?php
session_start();
include __DIR__ . '/../../db_connect.php';
include __DIR__ . '/../../api/log_audit.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /DORCFSRF/pages/login_form/index.php');
    exit;
}

$is_admin = ($_SESSION['role'] === 'Admin');
$target_user_id = $_GET['user_id'] ?? $_SESSION['user_id'];

// Security check
if ($target_user_id !== $_SESSION['user_id'] && !$is_admin) {
    die("Unauthorized action.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    $conn->begin_transaction();
    try {
        // Remove dependent rows in related tables to satisfy foreign key constraints
        // Delete notifications where the user appears in any notification field
        $delNotif = $conn->prepare("DELETE FROM user_notification WHERE user_id = ? OR recipient_id = ? OR sender_id = ? OR related_user_id = ? OR student_id = ?");
        if ($delNotif) {
            $delNotif->bind_param("sssss", $target_user_id, $target_user_id, $target_user_id, $target_user_id, $target_user_id);
            $delNotif->execute();
            $delNotif->close();
        }

        // Delete staff_notifications if the table exists
        $checkStaffTable = $conn->prepare("SELECT COUNT(*) as cnt FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'staff_notifications'");
        if ($checkStaffTable) {
            $checkStaffTable->execute();
            $rr = $checkStaffTable->get_result()->fetch_assoc();
            $checkStaffTable->close();
            if (!empty($rr['cnt'])) {
                $delStaffNotif = $conn->prepare("DELETE FROM staff_notifications WHERE staff_id = ? OR student_id = ?");
                if ($delStaffNotif) {
                    $delStaffNotif->bind_param("ss", $target_user_id, $target_user_id);
                    $delStaffNotif->execute();
                    $delStaffNotif->close();
                }
            }
        }

        // Also delete from student_information_database if present (may be cascaded)
        $stmt2 = $conn->prepare("DELETE FROM student_information_database WHERE student_id = ?");
        $stmt2->bind_param("s", $target_user_id);
        $stmt2->execute();

        // Finally delete the user record
        $stmt = $conn->prepare("DELETE FROM user_database WHERE user_id = ?");
        $stmt->bind_param("s", $target_user_id);
        $stmt->execute();

        $conn->commit();
        
        // Log the activity
        $action_desc = ($target_user_id === $_SESSION['user_id']) ? "Deleted Own Account" : "Deleted User Account";
        log_activity($conn, $_SESSION['user_id'], $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Admin', $_SESSION['role'], 'Users', $action_desc, 'Success', 'User', $target_user_id);

        // Check if user deleted themselves
        if ($target_user_id === $_SESSION['user_id']) {
            header("Location: /DORCFSRF/pages/logout.php");
        } else {
            header("Location: /DORCFSRF/pages/admin_dashboard/account_management.php");
        }
        // Attempt to delete Firebase user if service account configured
        try {
            include_once __DIR__ . '/../../config/firebase-config.php';
            if (defined('FIREBASE_SERVICE_ACCOUNT_KEY') && file_exists(__DIR__ . '/../../' . ltrim(FIREBASE_SERVICE_ACCOUNT_KEY, '/'))) {
                $saPath = realpath(__DIR__ . '/../../' . ltrim(FIREBASE_SERVICE_ACCOUNT_KEY, '/'));
                // Load service account
                $sa = json_decode(file_get_contents($saPath), true);
                if (!empty($sa['client_email']) && !empty($sa['private_key']) && !empty($sa['token_uri']) && defined('FIREBASE_PROJECT_ID')) {
                    // Create JWT and exchange for access token
                    $now = time();
                    $jwtHeader = str_replace('=', '', strtr(base64_encode(json_encode(['alg' => 'RS256','typ' => 'JWT'])), '+/', '-_'));
                    $claims = [
                        'iss' => $sa['client_email'],
                        'scope' => 'https://www.googleapis.com/auth/identitytoolkit',
                        'aud' => $sa['token_uri'],
                        'exp' => $now + 3600,
                        'iat' => $now
                    ];
                    $jwtClaim = str_replace('=', '', strtr(base64_encode(json_encode($claims)), '+/', '-_'));
                    $unsigned = $jwtHeader . '.' . $jwtClaim;
                    $sig = '';
                    openssl_sign($unsigned, $sig, $sa['private_key'], OPENSSL_ALGO_SHA256);
                    $jwtSig = str_replace('=', '', strtr(base64_encode($sig), '+/', '-_'));
                    $jwt = $unsigned . '.' . $jwtSig;
                    $tokenResponse = null;
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $sa['token_uri']);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer','assertion' => $jwt]));
                    $resp = curl_exec($ch);
                    curl_close($ch);
                    if ($resp) {
                        $tokenResponse = json_decode($resp, true);
                    }
                    if (!empty($tokenResponse['access_token'])) {
                        $accessToken = $tokenResponse['access_token'];
                        // Lookup user by email to get localId
                        // We need the email for the user; fetch from user_database
                        $eStmt = $conn->prepare('SELECT email FROM user_database WHERE user_id = ? LIMIT 1');
                        if ($eStmt) {
                            $eStmt->bind_param('s', $target_user_id);
                            $eStmt->execute();
                            $eres = $eStmt->get_result();
                            $erow = $eres->fetch_assoc();
                            $eStmt->close();
                        }
                        $emailForLookup = $erow['email'] ?? null;
                        if ($emailForLookup) {
                            $lookupUrl = 'https://identitytoolkit.googleapis.com/v1/projects/' . FIREBASE_PROJECT_ID . '/accounts:lookup';
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $lookupUrl);
                            curl_setopt($ch, CURLOPT_POST, true);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json','Authorization: Bearer ' . $accessToken]);
                            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['email' => [$emailForLookup]]));
                            $lookupResp = curl_exec($ch);
                            curl_close($ch);
                            $lookup = $lookupResp ? json_decode($lookupResp, true) : null;
                            $localId = null;
                            if (!empty($lookup['users']) && is_array($lookup['users'])) {
                                $localId = $lookup['users'][0]['localId'] ?? null;
                            }
                            if ($localId) {
                                $deleteUrl = 'https://identitytoolkit.googleapis.com/v1/projects/' . FIREBASE_PROJECT_ID . '/accounts:delete';
                                $ch = curl_init();
                                curl_setopt($ch, CURLOPT_URL, $deleteUrl);
                                curl_setopt($ch, CURLOPT_POST, true);
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json','Authorization: Bearer ' . $accessToken]);
                                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['localId' => $localId]));
                                $delResp = curl_exec($ch);
                                curl_close($ch);
                                log_activity($conn, $_SESSION['user_id'], $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Admin', $_SESSION['role'], 'Users', 'Firebase Delete localId=' . $localId, 'Success', 'User', $target_user_id);
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            log_activity($conn, $_SESSION['user_id'], $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Admin', $_SESSION['role'], 'Users', 'Firebase Delete Failed: ' . $e->getMessage(), 'Failed', 'User', $target_user_id);
        }
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        die("Error deleting account: " . $e->getMessage());
    }
}

// Where to go if they cancel
$cancel_url = '/DORCFSRF/pages/login_form/index.php'; // Default
if ($is_admin && $target_user_id !== $_SESSION['user_id']) {
    $cancel_url = '/DORCFSRF/pages/admin_dashboard/account_management.php';
} elseif ($_SESSION['role'] === 'Student') {
    $cancel_url = '/DORCFSRF/pages/student_dashboard/settings.php';
} elseif ($_SESSION['role'] === 'Staff') {
    $cancel_url = '/DORCFSRF/pages/staff_dashboard/staff_account_settings.php';
} elseif ($_SESSION['role'] === 'Admin') {
    $cancel_url = '/DORCFSRF/pages/admin_dashboard/account_settings.php';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Account Confirmation</title>

    <link rel="stylesheet" href="/DORCFSRF/css/dashboard.css" />
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    <style>
        .modal-backdrop {
            display: flex;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .delete-confirmation-box {
            background: white;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            max-width: 400px;
        }

        .btn-confirm {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-cancel {
            background-color: #f8f9fa;
            color: #333;
            border: 1px solid #ccc;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }

        .modal-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="container"
        style="display:flex; justify-content:center; align-items:center; height:100vh; background:#f4f7f6;">
        <div id="deleteModal" class="modal-backdrop">
            <div class="delete-confirmation-box">
                <i class="bx bx-user-x delete-icon" style="font-size: 50px; color: #dc3545; margin-bottom: 15px;"></i>
                <h3 class="modal-title">Confirm Account Deletion</h3>
                <p class="modal-text">
                    Are you sure you want to remove
                    <?php echo ($target_user_id === $_SESSION['user_id']) ? 'your' : 'this'; ?> account and all
                    associated information? This action cannot be undone.
                </p>
                <div style="background-color: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 13px; text-align: left; border-left: 4px solid #ffeeba;">
                    <strong>Note:</strong> This will delete the user from the SQL database completely. Because the Firebase Admin SDK is not installed on this server, you must manually delete their authentication record from your Firebase Console.
                </div>
                <form method="POST" action="delete.php?user_id=<?php echo urlencode($target_user_id); ?>" class="modal-actions">
                    <input type="hidden" name="confirm_delete" value="1">
                    <button type="submit" class="btn-confirm">Yes, Delete</button>
                    <a href="<?php echo htmlspecialchars($cancel_url); ?>" style="text-decoration:none;">
                        <button type="button" class="btn-cancel">Cancel</button>
                    </a>
                </form>
            </div>
        </div>
    </div>
</body>

</html>