<?php
include __DIR__ . '/../../db_connect.php';

$success = '';
$error = '';

// Get next sequential IDs for each role
$nextStaffId = 'STF001';

$staffResult = $conn->query("SELECT user_id FROM user_database WHERE role = 'Staff' ORDER BY user_id DESC LIMIT 1");
if ($staffResult && $staffResult->num_rows > 0) {
    $lastStaff = $staffResult->fetch_assoc()['user_id'];
    $lastNum = (int)substr($lastStaff, 3);
    $nextStaffId = 'STF' . str_pad($lastNum + 1, 3, '0', STR_PAD_LEFT);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = trim($_POST['user_id'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $allowed_roles = ['Student', 'Staff'];

    if ($user_id === '' || $full_name === '' || $username === '' || $email === '' || $role === '' || $password === '' || $confirm_password === '') {
        $error = 'All fields are required.';
    } elseif (!in_array($role, $allowed_roles, true)) {
        $error = 'Invalid role selected.';
    } elseif ($role === 'Staff' && !preg_match('/^STF\d{3}$/', $user_id)) {
        $error = 'Staff ID must be in format: STF001, STF002, etc.';
    } elseif ($role === 'Student' && trim($user_id) === '') {
        $error = 'Student ID is required.';
    } elseif (!preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $username)) {
        $error = 'Username must be 3 to 30 characters and may contain letters, numbers, dot, underscore, or hyphen.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        $check = $conn->prepare('SELECT account_id FROM user_database WHERE user_id = ? OR username = ? OR email = ?');
        $check->bind_param('sss', $user_id, $username, $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = 'User ID, username, or email already exists.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('INSERT INTO user_database (user_id, full_name, role, email, username, password) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('ssssss', $user_id, $full_name, $role, $email, $username, $hashed_password);

            if ($stmt->execute()) {
                if ($role === 'Staff') {
                    $unit_concern = trim($_POST['unit_concern'] ?? '');
                    if (!empty($unit_concern)) {
                        $unitStmt = $conn->prepare('INSERT INTO units_concerned (unit_name, staff_id) VALUES (?, ?)');
                        $unitStmt->bind_param('ss', $unit_concern, $user_id);
                        $unitStmt->execute();
                        $unitStmt->close();
                    }
                }
                $success = 'Registration successful. You can now log in.';
                $_POST = [];
            } else {
                $error = 'Registration failed: ' . $stmt->error;
            }
            $stmt->close();
        }
        $check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Account</title>
    <link rel="stylesheet" href="/DORCFSRF/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <h2>Create Account</h2>

        <?php if ($error): ?>
            <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <select name="role" required>
                <option value="">Select Role</option>
                <option value="Student" <?php echo (($_POST['role'] ?? '') === 'Student') ? 'selected' : ''; ?>>Student</option>
                <option value="Staff" <?php echo (($_POST['role'] ?? '') === 'Staff') ? 'selected' : ''; ?>>Staff</option>
            </select>

            <select name="unit_concern" id="unit_concern" style="display: none; margin-top: 15px;">
                <option value="">Select Unit Concern</option>
                <option value="Thesis">Thesis</option>
                <option value="Librarian">Librarian</option>
                <option value="Finance Officer">Finance Officer</option>
                <option value="Admission Officer">Admission Officer</option>
                <option value="Lab./ComLab/ ID">Lab./ComLab/ ID</option>
                <option value="School Cashier">School Cashier</option>
                <option value="Property Custodian">Property Custodian</option>
                <option value="Stud. Services Coordin.">Stud. Services Coordin.</option>
                <option value="Scholarship Coordinat.">Scholarship Coordinat.</option>
                <option value="Guidance Counselor">Guidance Counselor</option>
                <option value="Records Officer">Records Officer</option>
                <option value="Registrar">Registrar</option>
                <option value="College Dean">College Dean</option>
            </select>

            <input type="text" name="user_id" placeholder="User ID" required value="<?php echo htmlspecialchars($_POST['user_id'] ?? ''); ?>">
            <input type="text" name="full_name" placeholder="Full Name" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
            <input type="text" name="username" placeholder="Username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            <input type="email" name="email" placeholder="Email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">

            <div class="wrapper">
                <input type="password" name="password" id="password" placeholder="Password" required>
                <span class="show-btn" onclick="togglePassword('password')"><i class="fas fa-eye"></i></span>
            </div>

            <div class="wrapper">
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
                <span class="show-btn" onclick="togglePassword('confirm_password')"><i class="fas fa-eye"></i></span>
            </div>

            <button type="submit">Register</button>
        </form>

        <div class="extra">
            Already have an account? <a href="index.php">Go to Login</a>
        </div>
    </div>

    <script>
        const nextIds = {
            'Staff': '<?php echo $nextStaffId; ?>'
        };

        function togglePassword(id) {
            const input = document.getElementById(id);
            input.type = input.type === 'password' ? 'text' : 'password';
        }

        function updateUserIdByRole() {
            const roleSelect = document.querySelector('select[name="role"]');
            const userIdInput = document.querySelector('input[name="user_id"]');
            const unitConcernSelect = document.getElementById('unit_concern');
            
            if (roleSelect.value === 'Staff') {
                // Staff gets auto-generated ID
                userIdInput.value = nextIds['Staff'];
                userIdInput.readOnly = true;
                userIdInput.disabled = false;
                userIdInput.placeholder = 'Staff ID';
                unitConcernSelect.style.display = 'block';
                unitConcernSelect.required = true;
            } else if (roleSelect.value === 'Student') {
                // Student enters their own ID
                userIdInput.value = '';
                userIdInput.readOnly = false;
                userIdInput.disabled = false;
                userIdInput.placeholder = 'Student ID';
                unitConcernSelect.style.display = 'none';
                unitConcernSelect.required = false;
            } else {
                userIdInput.value = '';
                userIdInput.readOnly = false;
                userIdInput.disabled = true;
                userIdInput.placeholder = 'User ID';
                unitConcernSelect.style.display = 'none';
                unitConcernSelect.required = false;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const roleSelect = document.querySelector('select[name="role"]');
            const userIdInput = document.querySelector('input[name="user_id"]');
            
            // Initially disable the User ID field
            userIdInput.disabled = true;
            userIdInput.value = '';
            
            roleSelect.addEventListener('change', updateUserIdByRole);
        });
    </script>
</body>
</html>

