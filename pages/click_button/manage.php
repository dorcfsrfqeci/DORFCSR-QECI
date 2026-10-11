<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clerance Status</title>

    <link rel="stylesheet" href="/DORCFSRF/css/dashboard.css" />
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
</head>

<body>
    <div class="container">
        <aside class="sidebar">
            <img src="/DORCFSRF/assets/QECI LOGO.jpg" alt="qecilogo" />
           <nav>
                <a href="/DORCFSRF/pages/dashboard.php"><i class="bx bxs-dashboard"> </i> Dashboard</a>
                <a href="/DORCFSRF/pages/forms.php"><i class="bx bx-notepad"></i> Form</a>
                <a href="/DORCFSRF/pages/clearance.php"><i class="bx bxs-graduation"></i> Clearance Status</a>
                <a href="/DORCFSRF/pages/payment.php"><i class="bx bx-coin-stack"></i> Payment Transaction</a>
                <a href="/DORCFSRF/pages/record.php"><i class="bx bx-bar-chart-alt-2"></i> Scholastic Record Status</a>
                <a href="/DORCFSRF/pages/completion.php"><i class="bx bxs-calendar"></i> Completion Report</a>
                <p>SETTINGS</p>
                <a href="/DORCFSRF/pages/admin_dashboard/account_management.php"class="active"><i class='bx  bx-briefcase-alt'></i>  Account Management</a>
                <a href="/DORCFSRF/pages/settings.php" ><i class="bx bx-cog"></i> Account Settings</a>

            </nav>
        </aside>
        <div class="content-wrapper">
            <header class="header" style="display: flex; align-items: center; justify-content: flex-end;">
                <div class="user-icon">
                    <i class="bx bx-bell"></i>
                    <a href="aboutus.php"><i class="bx bx-globe bx-flip-vertical"></i></a>
                    <i class="bx bxs-user-circle"></i>
                </div>
            </header>

            <main class="main-content">
                <div class="background-overlay"></div>
                <h2 class="breadcrumb"><b>Home</b> > Clearance Status</h2>
                <div class="account-settings-container">
                    <div class="breadcrumbs">


                    </div>

                    <section class="account-settings-card">
                        <h3 class="section-title">BASIC INFO</h3>

                        <div class="info-group profile-upload-section">
                            <div class="profile-image-box">
                                <i class='bx bx-image-add'></i>
                                <p>Drag or browse<br> PNG, JPEG (clear file size)</p>
                            </div>
                            <div class="upload-label">
                                Profile Image
                            </div>
                        </div>

                        <div class="info-group">
                            <label for="name">Name:</label>
                            <input type="text" id="name" value="Dela Cruz, Juan Merado" required>
                        </div>

                        <div class="info-group">
                            <label for="dob">Date of Birth:</label>
                            <select id="dob" required>
                                <option>11/02/2000</option>
                            </select>
                        </div>

                        <div class="info-group">
                            <label for="email">Email Address:</label>
                            <input type="email" id="email" value="delacruzjuan@gmail.com" required>
                        </div>

                        <div class="info-group">
                            <label for="course">Course Major:</label>
                            <select id="course" required>
                                <option>Bachelor of Science in Computer Science 4</option>
                            </select>
                        </div>

                        <div class="info-group">
                            <label for="gender">Gender:</label>
                            <select id="gender" required>
                                <option>Male</option>
                                <option>Female</option>
                                <option>Others</option>
                            </select>
                        </div>

                        <h3 class="section-title account-info-title">ACCOUNT INFO</h3>

                        <div class="info-group">
                            <label for="username">Username:</label>
                            <input type="text" id="username" value="Juan M. Dela Cruz" required>
                        </div>

                        <div class="info-group">
                            <label for="password">Password:</label>
                            <input type="password" id="password" value="*******" required>
                        </div>

                        <div class="account-actions">
                            <a href="/DORCFSRF/pages/student_dashboard/settings.php">  
                            <button class="btn-action btn-save">Save</button>
                            </a>
                            <a href="delete.php">
                            <button class="btn-action btn-delete">Delete Account</button>
                            </a>
                        </div>

                    </section>

                    <div class="guide-setup">
                        <i class='bx bx-cog'></i>
                        <span>Guide to Set Up Your Account</span>
                    </div>
                </div>
            </main>
        </div>
</body>
</html>









