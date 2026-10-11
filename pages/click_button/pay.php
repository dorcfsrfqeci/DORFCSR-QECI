<?php
ini_set('session.gc_maxlifetime', 31536000);
session_set_cookie_params(31536000);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forms - Submission Complete</title>

    <link rel="stylesheet" href="/DORCFSRF/css/dashboard.css" />
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    <style>
        .qr-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .qr-modal.show {
            display: flex;
        }

        .qr-modal-content {
            background-color: white;
            padding: 40px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 90%;
        }

        .qr-modal-content h2 {
            margin-bottom: 20px;
            color: #333;
            font-size: 24px;
        }

        .qr-code-image {
            max-width: 100%;
            height: auto;
            margin: 20px auto;
            border-radius: 8px;
            display: block;
        }

        .qr-instructions {
            margin: 20px 0;
            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }

        .back-button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 20px;
            transition: background-color 0.3s;
        }

        .back-button:hover {
            background-color: #0056b3;
        }

        .payment-methods {
            cursor: pointer;
        }

        .payment-methods img {
            transition: transform 0.2s;
        }

        .payment-methods img:hover {
            transform: scale(1.05);
        }
    </style>
</head>

<body>
    <div class="container">
        <aside class="sidebar">
            <img src="/DORCFSRF/assets/QECI LOGO.jpg" alt="qecilogo" />
            <nav>
                <a href="/DORCFSRF/pages/student_dashboard/dashboard.php"><i class="bx bxs-dashboard"> </i>
                    Dashboard</a>
                <a href="/DORCFSRF/pages/student_dashboard/forms.php"><i class="bx bx-notepad"></i> Form</a>
                <a href="/DORCFSRF/pages/student_dashboard/clearance.php"><i class="bx bxs-graduation"></i> Clearance
                    Status</a>
                <a href="/DORCFSRF/pages/student_dashboard/payment.php"><i class="bx bx-coin-stack"></i> Payment
                    Transaction</a>
                <a href="/DORCFSRF/pages/student_dashboard/completion.php"><i class="bx bxs-calendar"></i> Completion
                    Report</a>
                <p>SETTINGS</p>
                <a href="/DORCFSRF/pages/student_dashboard/settings.php"><i class="bx bx-cog"></i> Account Settings</a>
            </nav>
        </aside>
        <div class="content-wrapper">
            <header class="header">
                <div class="">
                    <svg viewBox="0 0 24 24">
                        <path d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 1 0 3 10.5a7.5 7.5 0 0 0 13.65 6.15z" stroke="#888"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none" />
                    </svg>
                </div>
                <?php include __DIR__ . '/../../components/user_icon.php'; ?>
            </header>

            <main class="main-content" style="padding: 20px;">
                <h2 class="breadcrumb">Home > Payment Transaction</h2>
                <div
                    style="background: white; padding: 40px; border-radius: 10px; text-align: center; max-width: 600px; margin: 40px auto; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                    <h3 style="margin-bottom: 30px; color: #333; font-size: 24px;">Select your mode of Payment</h3>
                    <div class="payment-methods"
                        style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">
                        <div onclick="showQRCode('gcash')" style="cursor: pointer; transition: transform 0.2s;"
                            onmouseover="this.style.transform='scale(1.05)'"
                            onmouseout="this.style.transform='scale(1)'">
                            <img src="/DORCFSRF/assets/Gcash.png" alt="GCash"
                                style="border-radius: 50%; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width: 120px; height: 120px; object-fit: cover;">
                        </div>
                        <div onclick="showQRCode('maya')" style="cursor: pointer; transition: transform 0.2s;"
                            onmouseover="this.style.transform='scale(1.05)'"
                            onmouseout="this.style.transform='scale(1)'">
                            <img src="/DORCFSRF/assets/Maya.jpg" alt="Maya"
                                style="border-radius: 50%; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width: 120px; height: 120px; object-fit: cover;">
                        </div>
                        <div style="cursor: pointer; transition: transform 0.2s;"
                            onmouseover="this.style.transform='scale(1.05)'"
                            onmouseout="this.style.transform='scale(1)'">
                            <img src="/DORCFSRF/assets/Counter.png" alt="Pay at the Counter"
                                style="border-radius: 50%; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width: 120px; height: 120px; object-fit: cover;">
                        </div>
                    </div>
                </div>
            </main>

            <!-- GCash QR Modal -->
            <div id="gcashModal" class="qr-modal">
                <div class="qr-modal-content">
                    <h2>GCash Payment</h2>
                    <p class="qr-instructions">Scan this QR code with your GCash app to complete your payment</p>
                    <img src="/DORCFSRF/assets/gcashqrcode.jpg" alt="GCash QR Code" class="qr-code-image">
                    <p class="qr-instructions">Reference number will be sent to your registered mobile number</p>
                    <p id="paymentAmount" class="qr-instructions"
                        style="font-weight: bold; color: #333; font-size: 16px;"></p>
                    <button class="back-button" onclick="completePayment('GCash')">Payment Completed</button>
                    <button class="back-button" onclick="closeQRCode()" style="background-color: #6c757d;">Back</button>
                </div>
            </div>

            <!-- PayMaya QR Modal -->
            <div id="mayaModal" class="qr-modal">
                <div class="qr-modal-content">
                    <h2>PayMaya Payment</h2>
                    <p class="qr-instructions">Scan this QR code with your PayMaya app to complete your payment</p>
                    <img src="/DORCFSRF/assets/mayaqrcode.jpg" alt="PayMaya QR Code" class="qr-code-image">
                    <p class="qr-instructions">Reference number will be sent to your registered mobile number</p>
                    <p id="paymentAmount2" class="qr-instructions"
                        style="font-weight: bold; color: #333; font-size: 16px;"></p>
                    <button class="back-button" onclick="completePayment('PayMaya')">Payment Completed</button>
                    <button class="back-button" onclick="closeQRCode()" style="background-color: #6c757d;">Back</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Get payment data from localStorage
        function displayPaymentAmount() {
            const receiptData = JSON.parse(localStorage.getItem('paymentReceiptData') || '{}');
            if (receiptData.totalAmount) {
                const amountText = `Amount to Pay: ₱${receiptData.totalAmount}`;
                document.getElementById('paymentAmount').textContent = amountText;
                document.getElementById('paymentAmount2').textContent = amountText;
            }
        }

        function showQRCode(method) {
            if (method === 'gcash') {
                document.getElementById('gcashModal').classList.add('show');
            } else if (method === 'maya') {
                document.getElementById('mayaModal').classList.add('show');
            }
        }

        function closeQRCode() {
            document.getElementById('gcashModal').classList.remove('show');
            document.getElementById('mayaModal').classList.remove('show');
        }

        function completePayment(method) {
            const receiptData = JSON.parse(localStorage.getItem('paymentReceiptData') || '{}');

            // Update payment status in database with transaction ID
            const updateData = {
                payment_method: method,
                transaction_id: receiptData.transactionId  // Include the transaction ID
            };

            fetch('/DORCFSRF/api/update_payment_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(updateData)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Payment completed successfully!' + '\n' + data.payment_method);
                        localStorage.removeItem('paymentReceiptData');
                        window.location.href = '/DORCFSRF/pages/student_dashboard/payment.php';
                    } else {
                        alert('Payment recorded: ' + data.message);
                        localStorage.removeItem('paymentReceiptData');
                        window.location.href = '/DORCFSRF/pages/student_dashboard/payment.php';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Payment completed! Returning to payment page.');
                    localStorage.removeItem('paymentReceiptData');
                    window.location.href = '/DORCFSRF/pages/student_dashboard/payment.php';
                });
        }

        // Close modals when clicking outside
        window.onclick = function (event) {
            const gcashModal = document.getElementById('gcashModal');
            const mayaModal = document.getElementById('mayaModal');
            if (event.target === gcashModal) {
                gcashModal.classList.remove('show');
            }
            if (event.target === mayaModal) {
                mayaModal.classList.remove('show');
            }
        }

        // Display payment amount on page load
        window.addEventListener('DOMContentLoaded', displayPaymentAmount);
    </script>
</body>

</html>