<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt</title>
    <link rel="stylesheet" href="/DORCFSRF/css/dashboard.css" />
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    
    <style>
        /* Widened the receipt container */
        .receipt-card {
            background: white;
            max-width: 900px; /* Increased from 600px */
            margin: 40px auto;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-top: 8px solid #003366;
        }

        .receipt-header {
            text-align: center;
            border-bottom: 2px dashed #eee;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .receipt-logo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-bottom: 10px;
        }

        .receipt-grid {
            display: grid;
            grid-template-columns: 1fr 1fr; /* Two columns for a wider look */
            gap: 20px;
            margin-bottom: 30px;
        }

        .receipt-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .receipt-item label {
            color: #888;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .receipt-item span {
            color: #333;
            font-weight: 600;
            font-size: 16px;
        }

        .total-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
        }

        .total-label {
            font-size: 18px;
            font-weight: 700;
            color: #333;
        }

        .total-amount {
            color: #003366;
            font-size: 28px;
            font-weight: 800;
        }

        .status-badge {
            display: inline-block;
            background: #d4edda;
            color: #155724;
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 600;
            margin-top: 20px;
        }

        .receipt-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }

        /* Fixed buttons */
        .btn-print, .btn-back {
            padding: 12px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            border: none;
            transition: 0.3s;
        }

        .btn-print {
            background: #003366;
            color: white;
        }

        .btn-back {
            background: #6c757d;
            color: white;
        }

        .btn-print:hover, .btn-back:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        @media print {
            .sidebar, .header, .breadcrumb, .receipt-actions { display: none !important; }
            .content-wrapper { margin: 0 !important; padding: 0 !important; }
            .receipt-card { box-shadow: none; border: none; margin: 0; max-width: 100%; }
        }
    </style>
</head>

<body>
    <div class="container">
        <aside class="sidebar">
            <img src="/DORCFSRF/assets/QECI LOGO.jpg" alt="qecilogo" />
            <nav>
                <a href="/DORCFSRF/pages/student_dashboard/dashboard.php"><i class="bx bxs-dashboard"></i> Dashboard</a>
                <a href="/DORCFSRF/pages/student_dashboard/forms.php"><i class="bx bx-notepad"></i> Form</a>
                <a href="/DORCFSRF/pages/student_dashboard/clearance.php"><i class="bx bxs-graduation"></i> Clearance Status</a>
                <a href="/DORCFSRF/pages/student_dashboard/payment.php" class="active"><i class="bx bx-coin-stack"></i> Payment Transaction</a>
                <a href="/DORCFSRF/pages/student_dashboard/completion.php"><i class="bx bxs-calendar"></i> Completion Report</a>
                <p>SETTINGS</p>
                <a href="/DORCFSRF/pages/student_dashboard/settings.php"><i class="bx bx-cog"></i> Account Settings</a>
            </nav>
        </aside>

        <div class="content-wrapper">
            <header class="header">
                <div></div> 
                
                <?php include __DIR__ . '/../../components/user_icon.php'; ?>
            </header>

            <main class="main-content">
                <h2 class="breadcrumb">Home > Payment > Receipt</h2>

                <div class="receipt-card">
                    <div class="receipt-header">
                        <img src="/DORCFSRF/assets/QECI LOGO.jpg" alt="Logo" class="receipt-logo">
                        <h3>Quezon Executive College, Inc.</h3>
                        <p>Official Electronic Receipt</p>
                        <div class="status-badge">PAYMENT SUCCESSFUL</div>
                    </div>

                    <div class="receipt-grid">
                        <div class="receipt-item">
                            <label>Student Name</label>
                            <span id="studentName">Gemwil M. Alegre</span>
                        </div>
                        <div class="receipt-item">
                            <label>Transaction Date</label>
                            <span id="currentDate"></span>
                        </div>
                        <div class="receipt-item">
                            <label>Transaction ID</label>
                            <span id="transactionId">#QECI-2026-992834</span>
                        </div>
                        <div class="receipt-item">
                            <label>Payment Mode</label>
                            <span id="paymentMode">GCash</span>
                        </div>
                        <div class="receipt-item">
                            <label>Document Type</label>
                            <span id="documentType">Honorable Dismissal</span>
                        </div>
                        <div class="receipt-item">
                            <label>No. of Copies</label>
                            <span id="copyCount">2</span>
                        </div>
                    </div>

                    <div class="total-section">
                        <span class="total-label">GRAND TOTAL</span>
                        <span class="total-amount" id="totalAmount">₱ 200.00</span>
                    </div>
                </div>

                <div class="receipt-actions">
                    <button class="btn-print" onclick="window.print()">
                        <i class='bx bx-printer'></i> PRINT RECEIPT
                    </button>
                    <a href="/DORCFSRF/pages/student_dashboard/payment.php" class="btn-back">
                        <i class='bx bx-arrow-back'></i> BACK TO PAYMENT
                    </a>
                </div>
            </main>
        </div>
    </div>

    <script>
        const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };

        function loadReceiptData() {
            const rawData = localStorage.getItem('paymentReceiptData');
            if (!rawData) {
                document.getElementById('studentName').textContent = 'No receipt data available';
                document.getElementById('currentDate').textContent = new Date().toLocaleDateString('en-US', options);
                document.getElementById('transactionId').textContent = '#N/A';
                document.getElementById('paymentMode').textContent = 'N/A';
                document.getElementById('documentType').textContent = 'N/A';
                document.getElementById('copyCount').textContent = '0';
                document.getElementById('totalAmount').textContent = '₱ 0.00';
                return;
            }

            const receiptData = JSON.parse(rawData);
            document.getElementById('studentName').textContent = receiptData.studentName || 'Student';
            document.getElementById('currentDate').textContent = new Date(receiptData.paymentDate).toLocaleDateString('en-US', options);
            document.getElementById('transactionId').textContent = receiptData.transactionId || '#N/A';
            document.getElementById('paymentMode').textContent = receiptData.paymentMode || 'N/A';
            document.getElementById('documentType').textContent = receiptData.documentType || 'N/A';
            document.getElementById('copyCount').textContent = receiptData.copies || '0';
            document.getElementById('totalAmount').textContent = `₱ ${receiptData.totalAmount || '0.00'}`;
        }

        loadReceiptData();
    </script>
</body>
</html>









