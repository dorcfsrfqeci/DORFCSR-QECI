<?php
ini_set('session.gc_maxlifetime', 31536000);
session_set_cookie_params(31536000);
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Student') {
  header('Location: /DORCFSRF/pages/login_form/index.php');
  exit;
}
$full_name = $_SESSION['full_name'] ?? $_SESSION['username'];
$student_id = $_SESSION['user_id'];

// Get stored profile image
$profile_image = '/DORCFSRF/assets/student_profiles/' . $student_id . '_profile.jpg';
$profile_image_alt = '/DORCFSRF/assets/student_profiles/' . $student_id . '_profile.png';
$profile_image_alt2 = '/DORCFSRF/assets/student_profiles/' . $student_id . '_profile.gif';

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

// Database connection
include __DIR__ . '/../../db_connect.php';

// Get student payment records
$payments = array();
$stmt = $conn->prepare('SELECT * FROM payment_record_database WHERE student_id = ? ORDER BY payment_date DESC');
$stmt->bind_param('s', $student_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $payments[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment</title>
  <link rel="stylesheet" href="/DORCFSRF/css/dashboard.css" />
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
</head>

<body>
  <div class="container">
    <aside class="sidebar">
      <img src="/DORCFSRF/assets/QECI LOGO.jpg" alt="qecilogo" />
      <nav>
        <a href="dashboard.php"><i class="bx bxs-dashboard"> </i> Dashboard</a>
        <a href="forms.php"><i class="bx bx-notepad"></i> Form</a>
        <a href="clearance.php"><i class="bx bxs-graduation"></i> Clearance Status</a>
        <a href="payment.php" class="active"><i class="bx bx-coin-stack"></i> Payment Transaction</a>
        <a href="record.php"><i class="bx bx-bar-chart-alt-2"></i> Scholastic Record Status</a>
        <a href="completion.php"><i class="bx bxs-calendar"></i> Completion Report</a>
        <p>SETTINGS</p>
        <a href="settings.php"><i class="bx bx-cog"></i> Account Settings</a>
      </nav>
    </aside>

    <div class="content-wrapper">
      <header class="header">
        <div></div>
        <div class="user-icon" style="display: flex; gap: 15px; align-items: center;">
          <div class="notification-container" id="notificationContainer">
            <i class="bx bx-bell" id="notificationBell" title="Notifications"
              style="font-size: 24px; cursor: pointer;"></i>
            <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
            <div class="notification-dropdown" id="notificationDropdown">
              <div class="notification-header">
                <h3>Notifications</h3>
              </div>
              <ul class="notification-list" id="notificationList">
                <li class="notification-item text-center">Loading...</li>
              </ul>
            </div>
          </div>
          <a href="/DORCFSRF/pages/click_button/aboutus.php"><i class="bx bx-globe bx-flip-vertical"></i></a>
          <div class="user-menu-container">
            <div class="profile-icon-wrapper" id="profile-toggle">
              <?php if ($profile_image_url): ?>
                <img src="<?php echo htmlspecialchars($profile_image_url); ?>" alt="Profile"
                  style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;" />
              <?php else: ?>
                <i class="bx bxs-user-circle"></i>
              <?php endif; ?>
            </div>
            <div class="profile-card" id="profile-dropdown">
              <div class="card-header-bg"></div>
              <div class="profile-img" id="profileImageContainer">
                <?php if ($profile_image_url): ?>
                  <img src="<?php echo htmlspecialchars($profile_image_url); ?>" alt="Profile"
                    style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;" />
                <?php else: ?>
                  <i class="bx bxs-user-circle" style="font-size: 70px; color: #fff;"></i>
                <?php endif; ?>
              </div>
              <div class="profile-info">
                <h3><?php echo htmlspecialchars($full_name); ?></h3>
                <p>STUDENT</p>
              </div>
              <div class="card-actions">
                <button class="action-btn">Profile</button>
                <button class="action-btn">Sign Out</button>
              </div>
              <script src="/DORCFSRF/pages/script/dropdown.js"></script>
            </div>
          </div>
        </div>
      </header>

      <main class="main-content">
        <div class="background-overlay"></div>

        <h2 class="breadcrumb">Home > Payment Transaction</h2>
        <section class="payment-container">
          <div class="payment-header">
            <h2>Payment Details</h2>
            <p>Please provide accurate payment information to complete your transaction securely.</p>
          </div>
          <form class="payment-form">
            <table class="documents-table">
              <thead>
                <tr>
                  <th class="th-check"></th>
                  <th class="th-doc">Scholastic Documents</th>
                  <th class="th-fees">Fees</th>
                  <th class="th-copy">Copy</th>
                  <th class="th-num">Number of Copies</th>
                  <th class="th-total">Total</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><input type="radio" name="document" id="doc1"></td>
                  <td><label for="doc1">College Diploma with Rector's (Gold) School Seal</label></td>
                  <td class="unit-price">150.00 Pesos</td>
                  <td><select>
                      <option>Original</option>
                      <option>Authenticated</option>
                    </select></td>
                  <td><input type="number" class="copy-input" data-id="doc1" value="0" min="0"></td>
                  <td class="row-total">0.00</td>
                </tr>
                <tr>
                  <td><input type="radio" name="document" id="doc2"></td>
                  <td><label for="doc2">Transcript of Records (TOR) with Documentary Stamp</label></td>
                  <td class="unit-price">170.00 Pesos</td>
                  <td><select>
                      <option>Original</option>
                      <option>Authenticated</option>
                    </select></td>
                  <td><input type="number" class="copy-input" data-id="doc2" value="0" min="0"></td>
                  <td class="row-total">0.00</td>
                </tr>
                <tr>
                  <td><input type="radio" name="document" id="doc3"></td>
                  <td><label for="doc3">Undergraduate TOR</label></td>
                  <td class="unit-price">150.00 Pesos</td>
                  <td><select>
                      <option>Original</option>
                      <option>Authenticated</option>
                    </select></td>
                  <td><input type="number" class="copy-input" data-id="doc3" value="0" min="0"></td>
                  <td class="row-total">0.00</td>
                </tr>
                <tr>
                  <td><input type="radio" name="document" id="doc4"></td>
                  <td><label for="doc4">College Graduate TOR</label></td>
                  <td class="unit-price">180.00 Pesos</td>
                  <td><select>
                      <option>Original</option>
                      <option>Authenticated</option>
                    </select></td>
                  <td><input type="number" class="copy-input" data-id="doc4" value="0" min="0"></td>
                  <td class="row-total">0.00</td>
                </tr>
                <tr>
                  <td><input type="radio" name="document" id="doc5"></td>
                  <td><label for="doc5">Uniting (only those Uniting Subjects at QECI)</label></td>
                  <td class="unit-price">100.00 Pesos</td>
                  <td><select>
                      <option>Original</option>
                      <option>Authenticated</option>
                    </select></td>
                  <td><input type="number" class="copy-input" data-id="doc5" value="0" min="0"></td>
                  <td class="row-total">0.00</td>
                </tr>
                <tr>
                  <td><input type="radio" name="document" id="doc7"></td>
                  <td><label for="doc7">Transfer Credential / Honorable Dismissal</label></td>
                  <td class="unit-price">200.00 Pesos</td>
                  <td><select>
                      <option>Original</option>
                      <option>Authenticated</option>
                    </select></td>
                  <td><input type="number" class="copy-input" data-id="doc7" value="0" min="0"></td>
                  <td class="row-total">0.00</td>
                </tr>
                <tr>
                  <td><input type="radio" name="document" id="doc8"></td>
                  <td><label for="doc8">Certification of Enrollment/Graduation and Good Moral Character</label></td>
                  <td class="unit-price">100.00 Pesos</td>
                  <td><select>
                      <option>Original</option>
                      <option>Authenticated</option>
                    </select></td>
                  <td><input type="number" class="copy-input" data-id="doc8" value="0" min="0"></td>
                  <td class="row-total">0.00</td>
                </tr>
              </tbody>
              <tfoot>
                <tr class="total-footer">
                  <td colspan="5" class="grand-total-label">Grand Total:</td>
                  <td id="grand-total">0.00</td>
                </tr>
              </tfoot>
            </table>
          </form>

          <div class="payment-footer" style="display:flex; justify-content:flex-end; margin-top:16px;">
            <div class="payment-actions">
              <button type="button" class="btn-pay" id="download-receipt">PAY NOW</button>
            </div>
          </div>
        </section>
      </main>
    </div>
  </div>

  <!-- ===== MODE OF PAYMENT MODAL ===== -->
  <div id="paymentModeModal" style="
    display:none; position:fixed; inset:0; z-index:9999;
    background:rgba(0,0,0,0.65); backdrop-filter:blur(5px);
    align-items:center; justify-content:center;
  ">
    <div style="
      background:#fff; border-radius:20px; padding:32px 28px 28px;
      width:min(480px,94vw); box-shadow:0 24px 64px rgba(0,0,0,0.35);
      animation:modalPop .25s cubic-bezier(.34,1.56,.64,1); position:relative;
    ">
      <button onclick="closePayModal()" style="
        position:absolute; top:14px; right:18px; background:none; border:none;
        font-size:26px; cursor:pointer; color:#aaa; line-height:1;
      ">&times;</button>

      <!-- Step 1: Choose method -->
      <div id="step-choose">
        <h2 style="margin:0 0 4px; font-size:1.2rem; color:#1a1a2e;">Select Mode of Payment</h2>
        <p style="margin:0 0 18px; color:#888; font-size:.85rem;">Choose your preferred payment method to continue.</p>
        <div style="display:flex; flex-direction:column; gap:10px;">
          <label class="pay-mode-option" onclick="showQR('gcash')">
            <img src="/DORCFSRF/assets/Gcash.png" alt="GCash" style="height:34px; object-fit:contain;">
            <span>GCash</span>
            <i class="bx bx-chevron-right" style="margin-left:auto; font-size:20px; color:#bbb;"></i>
          </label>
          <label class="pay-mode-option" onclick="showQR('maya')">
            <img src="/DORCFSRF/assets/Maya.jpg" alt="Maya" style="height:34px; object-fit:contain;">
            <span>Maya</span>
            <i class="bx bx-chevron-right" style="margin-left:auto; font-size:20px; color:#bbb;"></i>
          </label>
          <label class="pay-mode-option" onclick="showQR('counter')">
            <img src="/DORCFSRF/assets/Counter.png" alt="Counter" style="height:34px; object-fit:contain;">
            <span>Pay at the Counter</span>
            <i class="bx bx-chevron-right" style="margin-left:auto; font-size:20px; color:#bbb;"></i>
          </label>
        </div>
      </div>

      <!-- Step 2: QR / Instructions -->
      <div id="step-qr" style="display:none; text-align:center;">
        <button onclick="backToChoose()" style="
          background:none; border:none; cursor:pointer; color:#2563eb;
          font-size:.85rem; font-weight:600; margin-bottom:14px;
          display:flex; align-items:center; gap:4px;
        "><i class="bx bx-arrow-back"></i> Back</button>
        <p id="qr-label" style="font-weight:700; font-size:1.05rem; color:#1a1a2e; margin:0 0 10px;"></p>

        <!-- Amount to Pay -->
        <div style="
          background:#f0f7ff; border:2px solid #2563eb; border-radius:10px;
          padding:10px 20px; margin-bottom:14px; display:inline-block;
        ">
          <span style="font-size:.82rem; color:#555;">Amount to Pay:</span><br>
          <span id="modal-total-amount" style="font-size:1.6rem; font-weight:800; color:#2563eb;">₱0.00</span>
        </div>

        <!-- GCash QR -->
        <div id="gcash-qr" style="display:none;">
          <div style="background:#0070ff; border-radius:16px; padding:18px 18px 14px; display:inline-block;">
            <img src="/DORCFSRF/assets/gcashqrcode.jpg" alt="GCash QR Code"
              style="width:200px; height:200px; object-fit:contain; border-radius:10px; display:block;">
          </div>
          <p style="margin:10px 0 2px; font-weight:600; color:#0070ff; font-size:.95rem;">GE***L A.</p>
          <p style="margin:0; font-size:.8rem; color:#888;">Mobile No.: +63 909 080 .... &nbsp;|&nbsp; Scan to pay via
            GCash</p>
        </div>

        <!-- Maya QR -->
        <div id="maya-qr" style="display:none;">
          <div
            style="background:#f5f5f5; border-radius:16px; padding:18px; display:inline-block; border:2px solid #eee;">
            <img src="/DORCFSRF/assets/mayaqrcode.jpg" alt="Maya QR Code"
              style="width:200px; height:200px; object-fit:contain; border-radius:10px; display:block;">
          </div>
          <p style="margin:10px 0 0; font-size:.82rem; color:#555;">Scan using your Maya app to pay</p>
        </div>

        <!-- Counter -->
        <div id="counter-info" style="display:none; background:#f8f9fa; border-radius:12px; padding:24px;">
          <i class="bx bx-building" style="font-size:52px; color:#2563eb;"></i>
          <p style="margin:10px 0 0; font-weight:700; color:#333; font-size:1rem;">Cashier's Office</p>
          <p style="margin:6px 0 0; font-size:.83rem; color:#666;">Present your Student ID and transaction
            number.<br>Office Hours: Mon–Fri, 8:00AM – 5:00PM</p>
        </div>

        <button onclick="proceedPayment()" style="
          margin-top:20px; width:100%; padding:13px; border:none; border-radius:10px;
          background:#2563eb; color:#fff; font-size:.95rem; font-weight:700; cursor:pointer;
          transition:background .2s;
        " onmouseover="this.style.background='#1d4ed8'" onmouseout="this.style.background='#2563eb'">
          ✓ I've Paid — Confirm Transaction
        </button>
      </div>
    </div>
  </div>

  <style>
    .pay-mode-option {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 13px 16px;
      border: 2px solid #e5e7eb;
      border-radius: 10px;
      cursor: pointer;
      transition: border-color .2s, background .2s;
      user-select: none;
    }

    .pay-mode-option:hover {
      border-color: #2563eb;
      background: #eff6ff;
    }

    .pay-mode-option span {
      font-weight: 600;
      color: #1a1a2e;
      font-size: .95rem;
    }

    @keyframes modalPop {
      from {
        transform: scale(.88);
        opacity: 0;
      }

      to {
        transform: scale(1);
        opacity: 1;
      }
    }
  </style>

  <script>
    function updateGrandTotal() {
      let grandTotal = 0;
      const rows = document.querySelectorAll('tbody tr');

      rows.forEach(row => {
        const input = row.querySelector('.copy-input');
        const priceText = row.querySelector('.unit-price').textContent;
        const price = parseFloat(priceText.replace(/[^0-9.]/g, ''));
        const copies = parseInt(input.value) || 0;

        const total = price * copies;
        row.querySelector('.row-total').textContent = total.toFixed(2);
        grandTotal += total;

        // Save to memory so it stays after refresh
        localStorage.setItem(input.getAttribute('data-id'), copies);
      });

      document.getElementById('grand-total').textContent = grandTotal.toFixed(2);
      return grandTotal;
    }

    function getSelectedDocumentInfo() {
      const selectedRadio = document.querySelector('input[name="document"]:checked');
      if (!selectedRadio) return null;
      const row = selectedRadio.closest('tr');
      const docType = row.querySelector('label').textContent.trim();
      const copies = parseInt(row.querySelector('.copy-input').value) || 0;
      const priceText = row.querySelector('.unit-price').textContent;
      const unitPrice = parseFloat(priceText.replace(/[^0-9.]/g, ''));
      const total = unitPrice * copies;
      return {
        documentType: docType,
        copies,
        unitPrice: unitPrice.toFixed(2),
        total: total.toFixed(2),
        paymentMode: 'GCash'
      };
    }

    function saveReceiptDataAndNavigate() {
      const receiptInfo = getSelectedDocumentInfo();
      const grandTotal = updateGrandTotal();
      if (!receiptInfo || receiptInfo.copies === 0) {
        alert('Please choose a document and enter the number of copies before downloading the receipt.');
        return;
      }

      // Get the document row to get type_copy (Original/Authenticated)
      const selectedRadio = document.querySelector('input[name="document"]:checked');
      const row = selectedRadio.closest('tr');
      const typeCopySelect = row.querySelector('select');
      const typeCopy = typeCopySelect ? typeCopySelect.value : 'Original';

      // Save to database via API
      const paymentData = {
        type_schdocs: receiptInfo.documentType,
        num_copies: receiptInfo.copies,
        type_copy: typeCopy,
        doc_amount: receiptInfo.unitPrice,
        tot_amount: grandTotal.toFixed(2),
        payment_method: window._selectedPayMode || 'Pending',
        payment_date: new Date().toISOString().split('T')[0]
      };

      fetch('/DORCFSRF/api/save_payment_record.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(paymentData)
      })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            const receiptData = {
              studentName: '<?php echo htmlspecialchars($full_name); ?>',
              transactionId: data.transaction_id,
              paymentDate: new Date().toISOString(),
              paymentMethod: receiptInfo.paymentMode,
              documentType: receiptInfo.documentType,
              copies: receiptInfo.copies,
              totalAmount: grandTotal.toFixed(2)
            };

            // Clear form
            document.querySelectorAll('.copy-input').forEach(input => {
              localStorage.removeItem(input.getAttribute('data-id'));
              input.value = 0;
            });
            document.querySelectorAll('input[name="document"]').forEach(r => r.checked = false);
            updateGrandTotal();
            localStorage.removeItem('paymentReceiptData');

            if (window._selectedPayMode === 'Counter') {
              // Open printable receipt for counter payment
              const receiptWin = window.open('', '_blank', 'width=700,height=820');
              const payDate = new Date().toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' });
              const payTime = new Date().toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' });
              receiptWin.document.write(`
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Payment Receipt - ${data.transaction_id}</title>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: Arial, sans-serif; background:#fff; color:#222; padding:30px; }
    .receipt { max-width:580px; margin:0 auto; border:2px solid #1a1a2e; border-radius:8px; overflow:hidden; }
    .header { background:#1a1a2e; color:#fff; text-align:center; padding:24px 20px 18px; }
    .header img { height:70px; margin-bottom:10px; display:block; margin-left:auto; margin-right:auto; border-radius:50%; }
    .header h1 { font-size:1.15rem; letter-spacing:1px; margin-bottom:4px; }
    .header p { font-size:.78rem; opacity:.8; }
    .receipt-title { background:#f0f4ff; text-align:center; padding:12px; border-bottom:1px solid #dde; }
    .receipt-title h2 { font-size:1rem; color:#1a1a2e; letter-spacing:2px; text-transform:uppercase; }
    .receipt-title p { font-size:.78rem; color:#888; margin-top:2px; }
    .body { padding:24px; }
    .row { display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px dashed #e0e0e0; }
    .row:last-child { border:none; }
    .row .label { color:#666; font-size:.88rem; }
    .row .value { font-weight:600; font-size:.88rem; text-align:right; max-width:55%; }
    .total-row { background:#f0f7ff; border-radius:6px; padding:12px 16px; margin:16px 0; display:flex; justify-content:space-between; align-items:center; }
    .total-row .label { font-size:1rem; font-weight:700; color:#1a1a2e; }
    .total-row .value { font-size:1.4rem; font-weight:800; color:#2563eb; }
    .instruction { background:#fff8e1; border-left:4px solid #f59e0b; padding:12px 16px; border-radius:4px; margin-top:10px; }
    .instruction p { font-size:.82rem; color:#92400e; line-height:1.6; }
    .footer { text-align:center; padding:16px; border-top:1px solid #eee; font-size:.75rem; color:#999; }
    @media print { body { padding:0; } .no-print { display:none; } }
  </style>
</head>
<body>
  <div class="receipt">
    <div class="header">
      <img src="/DORCFSRF/assets/QECI LOGO.jpg" alt="QECI Logo">
      <h1>Quezonian Educational College, Inc.</h1>
      <p>Official Payment Receipt</p>
    </div>
    <div class="receipt-title">
      <h2>Payment Request Slip</h2>
      <p>Present this slip at the Cashier's Office to complete your payment.</p>
    </div>
    <div class="body">
      <div class="row"><span class="label">Reference No.:</span><span class="value">${data.transaction_id}</span></div>
      <div class="row"><span class="label">Student Name:</span><span class="value">${'<?php echo htmlspecialchars($full_name); ?>'}</span></div>
      <div class="row"><span class="label">Student ID:</span><span class="value">${'<?php echo htmlspecialchars($student_id); ?>'}</span></div>
      <div class="row"><span class="label">Document Requested:</span><span class="value">${paymentData.type_schdocs}</span></div>
      <div class="row"><span class="label">Type:</span><span class="value">${paymentData.type_copy}</span></div>
      <div class="row"><span class="label">No. of Copies:</span><span class="value">${paymentData.num_copies}</span></div>
      <div class="row"><span class="label">Unit Price:</span><span class="value">₱${parseFloat(paymentData.doc_amount).toFixed(2)}</span></div>
      <div class="row"><span class="label">Payment Method:</span><span class="value">Pay at Counter</span></div>
      <div class="row"><span class="label">Date:</span><span class="value">${payDate} ${payTime}</span></div>
      <div class="total-row">
        <span class="label">Total Amount Due:</span>
        <span class="value">₱${parseFloat(paymentData.tot_amount).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</span>
      </div>
      <div class="instruction">
        <p>📋 <strong>Instructions:</strong> Please proceed to the Cashier's Office and present this slip together with your <strong>Student ID</strong>. Pay the total amount indicated above. Office Hours: <strong>Mon–Fri, 8:00AM – 5:00PM</strong>.</p>
      </div>
    </div>
    <div class="footer">
      This is a system-generated slip. &nbsp;|&nbsp; Quezonian Educational College, Inc. &nbsp;|&nbsp; ${payDate}
    </div>
  </div>
  <br>
  <div class="no-print" style="text-align:center;">
    <button onclick="window.print()" style="padding:10px 28px;background:#1a1a2e;color:#fff;border:none;border-radius:6px;font-size:1rem;cursor:pointer;">🖨️ Print Receipt</button>
    &nbsp;
    <button onclick="window.close()" style="padding:10px 28px;background:#e5e7eb;color:#333;border:none;border-radius:6px;font-size:1rem;cursor:pointer;">Close</button>
  </div>
</body>
</html>`);
              receiptWin.document.close();
            } else {
              // GCash / Maya — also open receipt popup
              const receiptWin2 = window.open('', '_blank', 'width=700,height=820');
              const payDate2 = new Date().toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' });
              const payTime2 = new Date().toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' });
              const modeLabel = window._selectedPayMode || 'Online Payment';
              receiptWin2.document.write(`
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Payment Receipt - ${data.transaction_id}</title>
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: Arial, sans-serif; background:#f4f6fb; color:#222; padding:30px; }
    .receipt { max-width:580px; margin:0 auto; border:2px solid #1a1a2e; border-radius:10px; overflow:hidden; background:#fff; }
    .header { background:#1a1a2e; color:#fff; text-align:center; padding:24px 20px 18px; }
    .header img { height:72px; margin-bottom:10px; display:block; margin-left:auto; margin-right:auto; border-radius:50%; border:3px solid #fff; }
    .header h1 { font-size:1.1rem; letter-spacing:.5px; margin-bottom:3px; }
    .header p { font-size:.76rem; opacity:.75; }
    .receipt-title { background:#eef3ff; text-align:center; padding:12px; border-bottom:1px solid #d0d9f0; }
    .receipt-title h2 { font-size:.95rem; color:#1a1a2e; letter-spacing:2px; text-transform:uppercase; }
    .receipt-title p { font-size:.76rem; color:#888; margin-top:3px; }
    .badge { display:inline-block; background:#dcfce7; color:#15803d; border:1px solid #86efac; border-radius:20px; padding:4px 14px; font-size:.78rem; font-weight:700; margin:10px 0 0; }
    .body { padding:22px 24px; }
    .row { display:flex; justify-content:space-between; padding:7px 0; border-bottom:1px dashed #e8e8e8; }
    .row:last-child { border:none; }
    .row .label { color:#666; font-size:.85rem; }
    .row .value { font-weight:600; font-size:.85rem; text-align:right; max-width:58%; }
    .total-row { background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:12px 16px; margin:14px 0 10px; display:flex; justify-content:space-between; align-items:center; }
    .total-row .label { font-size:.95rem; font-weight:700; color:#1a1a2e; }
    .total-row .value { font-size:1.5rem; font-weight:800; color:#2563eb; }
    .note { background:#f0fdf4; border-left:4px solid #22c55e; padding:10px 14px; border-radius:4px; font-size:.8rem; color:#166534; line-height:1.6; }
    .footer { text-align:center; padding:14px; border-top:1px solid #eee; font-size:.72rem; color:#aaa; }
    @media print { body { background:#fff; padding:0; } .no-print { display:none; } }
  </style>
</head>
<body>
  <div class="receipt">
    <div class="header">
      <img src="/DORCFSRF/assets/QECI LOGO.jpg" alt="QECI Logo">
      <h1>Quezonian Educational College, Inc.</h1>
      <p>Official Payment Receipt</p>
    </div>
    <div class="receipt-title">
      <h2>Payment Confirmation</h2>
      <span class="badge">✅ Payment Recorded</span>
    </div>
    <div class="body">
      <div class="row"><span class="label">Reference No.:</span><span class="value">${data.transaction_id}</span></div>
      <div class="row"><span class="label">Student Name:</span><span class="value">${'<?php echo htmlspecialchars($full_name); ?>'}</span></div>
      <div class="row"><span class="label">Student ID:</span><span class="value">${'<?php echo htmlspecialchars($student_id); ?>'}</span></div>
      <div class="row"><span class="label">Document Requested:</span><span class="value">${paymentData.type_schdocs}</span></div>
      <div class="row"><span class="label">Type:</span><span class="value">${paymentData.type_copy}</span></div>
      <div class="row"><span class="label">No. of Copies:</span><span class="value">${paymentData.num_copies}</span></div>
      <div class="row"><span class="label">Unit Price:</span><span class="value">₱${parseFloat(paymentData.doc_amount).toFixed(2)}</span></div>
      <div class="row"><span class="label">Payment Method:</span><span class="value">${modeLabel}</span></div>
      <div class="row"><span class="label">Date &amp; Time:</span><span class="value">${payDate2} ${payTime2}</span></div>
      <div class="total-row">
        <span class="label">Total Amount Paid:</span>
        <span class="value">₱${parseFloat(paymentData.tot_amount).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</span>
      </div>
      <div class="note">
        ✔ Your payment has been recorded in the system. A confirmation email has been sent to your registered email address. Please keep this receipt for your records.
      </div>
    </div>
    <div class="footer">
      This is a system-generated receipt. &nbsp;|&nbsp; Quezonian Educational College, Inc. &nbsp;|&nbsp; ${payDate2}
    </div>
  </div>
  <br>
  <div class="no-print" style="text-align:center;">
    <button onclick="window.print()" style="padding:10px 28px;background:#1a1a2e;color:#fff;border:none;border-radius:6px;font-size:1rem;cursor:pointer;">🖨️ Print Receipt</button>
    &nbsp;
    <button onclick="window.close()" style="padding:10px 28px;background:#e5e7eb;color:#333;border:none;border-radius:6px;font-size:1rem;cursor:pointer;">Close</button>
  </div>
</body>
</html>`);
              receiptWin2.document.close();
            }
          } else {
            alert('Error saving payment record: ' + data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Error processing payment. Please try again.');
        });
    }

    // Load saved values and set initial state to 0 if empty
    window.addEventListener('DOMContentLoaded', () => {
      document.querySelectorAll('.copy-input').forEach(input => {
        const savedValue = localStorage.getItem(input.getAttribute('data-id'));
        input.value = savedValue !== null ? savedValue : 0;
      });
      updateGrandTotal();
      document.getElementById('download-receipt').addEventListener('click', () => {
        const receiptInfo = getSelectedDocumentInfo();
        if (!receiptInfo || receiptInfo.copies === 0) {
          alert('Please choose a document and enter the number of copies.');
          return;
        }
        document.getElementById('step-choose').style.display = 'block';
        document.getElementById('step-qr').style.display = 'none';
        document.getElementById('paymentModeModal').style.display = 'flex';
      });
    });

    function closePayModal() {
      document.getElementById('paymentModeModal').style.display = 'none';
    }
    function backToChoose() {
      document.getElementById('step-qr').style.display = 'none';
      document.getElementById('step-choose').style.display = 'block';
    }
    function showQR(method) {
      window._selectedPayMode = method === 'gcash' ? 'GCash' : method === 'maya' ? 'Maya' : 'Counter';
      document.getElementById('step-choose').style.display = 'none';
      document.getElementById('step-qr').style.display = 'block';
      ['gcash-qr', 'maya-qr', 'counter-info'].forEach(id => document.getElementById(id).style.display = 'none');
      const labels = { gcash: 'Scan to Pay via GCash', maya: 'Scan to Pay via Maya', counter: "Pay at the Cashier's Office" };
      document.getElementById('qr-label').textContent = labels[method];
      document.getElementById(method === 'counter' ? 'counter-info' : method + '-qr').style.display = 'block';
      // Show current grand total
      const total = document.getElementById('grand-total').textContent;
      document.getElementById('modal-total-amount').textContent = '₱' + parseFloat(total).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function proceedPayment() {
      closePayModal();
      saveReceiptDataAndNavigate();
    }
    document.getElementById('paymentModeModal').addEventListener('click', function (e) {
      if (e.target === this) closePayModal();
    });

    // Listen for changes
    document.querySelectorAll('.copy-input').forEach(input => {
      input.addEventListener('input', updateGrandTotal);
    });
  </script>
  <script src="/DORCFSRF/pages/script/notifications.js"></script>
  <script src="/DORCFSRF/pages/script/prevent-back-button.js"></script>
</body>

</html>