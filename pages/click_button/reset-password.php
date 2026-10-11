<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reset Your Password</title>
    <link rel="stylesheet" href="/DORCFSRF/css/style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <style>
        .firebase-message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: none;
        }
        .firebase-message.success {
            display: block;
            color: #0d7377;
            background: #cffafe;
            border: 1px solid #06b6d4;
        }
        .firebase-message.error {
            display: block;
            color: #b71c1c;
            background: #fdecea;
            border: 1px solid #ef5350;
        }
        .firebase-message.loading {
            display: block;
            color: #1565c0;
            background: #e3f2fd;
            border: 1px solid #42a5f5;
        }
        .firebase-message.warning {
            display: block;
            color: #e65100;
            background: #fff3e0;
            border: 1px solid #ffb74d;
        }
        .loader {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 8px;
            vertical-align: middle;
        }
        .loader.show {
            display: inline-block;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .password-requirements {
            font-size: 14px;
            color: #666;
            margin-top: 12px;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 4px;
        }
        .requirement {
            margin: 5px 0;
            display: flex;
            align-items: center;
        }
        .requirement.met {
            color: #0d7377;
        }
        .requirement.unmet {
            color: #b71c1c;
        }
        .requirement-icon {
            margin-right: 8px;
            width: 16px;
        }
        .wrapper {
            position: relative;
            margin-bottom: 16px;
        }
        .show-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #757575;
            font-size: 18px;
        }
        #verifyingCode {
            display: none;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="logoqeci">
            <a href="index.php">
                <img src="/DORCFSRF/assets/QECI LOGO.jpg" alt="School Logo" class="logo" />
            </a>
        </div>

        <h1>Reset Your Password</h1>

        <div id="message" class="firebase-message">
            <span class="loader" id="loader"></span>
            <span id="messageText"></span>
        </div>

        <!-- Step 1: Verifying Code -->
        <div id="verifyingCode" class="firebase-message loading">
            <span class="loader show"></span>
            <span>Verifying your reset link...</span>
        </div>

        <!-- Step 2: Reset Password Form -->
        <form id="resetPasswordForm" style="display: none;">
            <div>
                <label for="userEmail">Email Address</label>
                <input
                    type="email"
                    id="userEmail"
                    readonly
                    style="background-color: #f5f5f5; cursor: not-allowed;"
                />
            </div>

            <div class="wrapper">
                <label for="newPassword">New Password</label>
                <input
                    type="password"
                    id="newPassword"
                    placeholder="Enter new password"
                    required
                    minlength="8"
                />
                <span class="show-btn" id="togglePassword1">
                    <i class="fas fa-eye"></i>
                </span>
            </div>

            <div class="wrapper">
                <label for="confirmPassword">Confirm Password</label>
                <input
                    type="password"
                    id="confirmPassword"
                    placeholder="Confirm your password"
                    required
                    minlength="8"
                />
                <span class="show-btn" id="togglePassword2">
                    <i class="fas fa-eye"></i>
                </span>
            </div>

            <div class="password-requirements">
                <strong>Password must meet these requirements:</strong><br>
                <div class="requirement" id="lengthReq">
                    <span class="requirement-icon">○</span>
                    <span>At least 8 characters</span>
                </div>
                <div class="requirement" id="uppercaseReq">
                    <span class="requirement-icon">○</span>
                    <span>Contains uppercase letter (A-Z)</span>
                </div>
                <div class="requirement" id="lowercaseReq">
                    <span class="requirement-icon">○</span>
                    <span>Contains lowercase letter (a-z)</span>
                </div>
                <div class="requirement" id="numberReq">
                    <span class="requirement-icon">○</span>
                    <span>Contains number (0-9)</span>
                </div>
            </div>

            <button type="submit" id="submitBtn" disabled>Reset Password</button>
        </form>

        <!-- Success Message -->
        <div id="successMessage" style="display: none; text-align: center;">
            <h2 style="color: #0d7377; margin-bottom: 20px;">✓ Password Reset Successfully!</h2>
            <p style="margin-bottom: 20px;">Your password has been reset. You can now log in with your new password.</p>
            <a href="/DORCFSRF/pages/login_form/student.php" class="btn" style="display: inline-block; padding: 10px 20px; background: #0d7377; color: white; border-radius: 4px; text-decoration: none;">
                Go to Login
            </a>
        </div>

        <div class="extra">
            <p><a href="/DORCFSRF/pages/login_form/student.php">Back to Login</a></p>
        </div>
    </div>

    <!-- Firebase SDK - Using compat version for easier syntax -->
    <script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-auth-compat.js"></script>

    <script>
        // Firebase Configuration
        const firebaseConfig = {
            apiKey: "AIzaSyAz1GXAfn2zj3GqUqSBIcN3XnUuSXGCJdc",
            authDomain: "dorfcsrfqeci.firebaseapp.com",
            projectId: "dorfcsrfqeci",
            storageBucket: "dorfcsrfqeci.firebasestorage.app",
            messagingSenderId: "822546813632",
            appId: "1:822546813632:web:35a54d6daf0a6fdb9b5325"
        };

        // Initialize Firebase
        firebase.initializeApp(firebaseConfig);
        const auth = firebase.auth();
        auth.languageCode = 'en';

        // Get DOM elements
        const resetPasswordForm = document.getElementById('resetPasswordForm');
        const newPasswordInput = document.getElementById('newPassword');
        const confirmPasswordInput = document.getElementById('confirmPassword');
        const userEmailInput = document.getElementById('userEmail');
        const submitBtn = document.getElementById('submitBtn');
        const messageDiv = document.getElementById('message');
        const messageText = document.getElementById('messageText');
        const loader = document.getElementById('loader');
        const verifyingCode = document.getElementById('verifyingCode');
        const successMessage = document.getElementById('successMessage');

        // Password requirement elements
        const lengthReq = document.getElementById('lengthReq');
        const uppercaseReq = document.getElementById('uppercaseReq');
        const lowercaseReq = document.getElementById('lowercaseReq');
        const numberReq = document.getElementById('numberReq');

        // Get reset code from URL
        const urlParams = new URLSearchParams(window.location.search);
        const oobCode = urlParams.get('oobCode');
        const mode = urlParams.get('mode');

        // Verify the code on page load
        if (oobCode && mode === 'resetPassword') {
            verifyResetCode();
        } else {
            showMessage('Invalid or missing reset link. Please request a new password reset.', 'error');
            resetPasswordForm.style.display = 'none';
        }

        // Verify the reset code is valid
        async function verifyResetCode() {
            try {
                const accountEmail = await auth.verifyPasswordResetCode(oobCode);
                userEmailInput.value = accountEmail;
                resetPasswordForm.style.display = 'block';
                verifyingCode.style.display = 'none';
                showMessage('', '');
            } catch (error) {
                console.error('Error verifying code:', error);
                let errorMessage = 'The reset link is invalid or has expired.';

                if (error.code === 'auth/expired-action-code') {
                    errorMessage = 'The reset link has expired. Please request a new one.';
                } else if (error.code === 'auth/invalid-action-code') {
                    errorMessage = 'The reset link is invalid. Please request a new one.';
                }

                showMessage('✗ ' + errorMessage, 'error');
                verifyingCode.style.display = 'none';
                resetPasswordForm.style.display = 'none';
            }
        }

        // Validate password requirements
        newPasswordInput.addEventListener('input', validatePassword);
        confirmPasswordInput.addEventListener('input', validatePassword);

        function validatePassword() {
            const password = newPasswordInput.value;
            const confirmPassword = confirmPasswordInput.value;

            // Check length requirement (8+ characters)
            const lengthMet = password.length >= 8;
            updateRequirement(lengthReq, lengthMet);

            // Check uppercase requirement
            const uppercaseMet = /[A-Z]/.test(password);
            updateRequirement(uppercaseReq, uppercaseMet);

            // Check lowercase requirement
            const lowercaseMet = /[a-z]/.test(password);
            updateRequirement(lowercaseReq, lowercaseMet);

            // Check number requirement
            const numberMet = /[0-9]/.test(password);
            updateRequirement(numberReq, numberMet);

            // Enable submit button if all requirements met and passwords match
            const allRequirementsMet = lengthMet && uppercaseMet && lowercaseMet && numberMet;
            const passwordsMatch = password === confirmPassword && password.length > 0;

            submitBtn.disabled = !(allRequirementsMet && passwordsMatch);
        }

        function updateRequirement(element, isMet) {
            if (isMet) {
                element.classList.remove('unmet');
                element.classList.add('met');
                element.querySelector('.requirement-icon').textContent = '✓';
            } else {
                element.classList.remove('met');
                element.classList.add('unmet');
                element.querySelector('.requirement-icon').textContent = '○';
            }
        }

        // Handle password visibility toggle
        document.getElementById('togglePassword1').addEventListener('click', function() {
            togglePasswordVisibility(newPasswordInput, this);
        });

        document.getElementById('togglePassword2').addEventListener('click', function() {
            togglePasswordVisibility(confirmPasswordInput, this);
        });

        function togglePasswordVisibility(input, button) {
            if (input.type === 'password') {
                input.type = 'text';
                button.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                input.type = 'password';
                button.innerHTML = '<i class="fas fa-eye"></i>';
            }
        }

        // Handle form submission
        resetPasswordForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const newPassword = newPasswordInput.value;
            const confirmPassword = confirmPasswordInput.value;

            if (newPassword !== confirmPassword) {
                showMessage('✗ Passwords do not match.', 'error');
                return;
            }

            // Show loading state
            submitBtn.disabled = true;
            submitBtn.textContent = 'Resetting...';
            loader.classList.add('show');
            showMessage('Resetting your password...', 'loading');

            try {
                // Confirm password reset
                await auth.confirmPasswordReset(oobCode, newPassword);

                // Success
                resetPasswordForm.style.display = 'none';
                messageDiv.style.display = 'none';
                successMessage.style.display = 'block';

            } catch (error) {
                console.error('Error resetting password:', error);
                let errorMessage = 'An error occurred while resetting your password.';

                if (error.code === 'auth/weak-password') {
                    errorMessage = 'Password is too weak. Please use a stronger password.';
                } else if (error.code === 'auth/expired-action-code') {
                    errorMessage = 'The reset link has expired. Please request a new one.';
                } else if (error.code === 'auth/invalid-action-code') {
                    errorMessage = 'The reset link is invalid.';
                }

                showMessage('✗ ' + errorMessage, 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Reset Password';
                loader.classList.remove('show');
            }
        });

        // Helper function to show messages
        function showMessage(message, type) {
            messageText.textContent = message;
            if (message) {
                messageDiv.className = 'firebase-message ' + type;
            } else {
                messageDiv.className = 'firebase-message';
                messageDiv.style.display = 'none';
            }
        }
    </script>
</body>
</html>

