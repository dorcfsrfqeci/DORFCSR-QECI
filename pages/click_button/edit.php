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
                <a href="/DORCFSRF/pages/settings.php" class="active"><i class="bx bx-cog"></i> Account Settings</a>
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

            <main class="main-content">
                <div class="background-overlay"></div>
                <h2 class="breadcrumb"><b>Home</b> > Clearance Status</h2>
                <div class="account-settings-container">
                    <div class="breadcrumbs">


                    </div>

                    <section class="account-settings-card">
                        <h3 class="section-title">BASIC INFO</h3>

                        <div class="info-group profile-upload-section">
                            <div class="profile-image-box" id="profileBox">
                                <i class='bx bx-image-add'></i>
                                <p id="uploadHint">Drag or browse<br> PNG, JPEG (clear file size)</p>
                                <img id="profilePreview" alt="Profile Preview" style="display:none; width:100%; height:100%; object-fit:cover; border-radius:12px; position:absolute; inset:0;" />
                                <input type="file" id="profileImageInput" accept="image/png, image/jpeg, image/jpg" style="display:none;" />
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
                            <button type="button" id="saveProfile" class="btn-action btn-save">Save</button>
                            <a href="delete.php">
                            <button class="btn-action btn-delete">Delete Account</button>
                            </a>
                        </div>

                    </section>

                    <div class="guide-setup">
                        <i class='bx bx-cog'></i>
                        <span><a href="guide.php">Guide to Set Up Your Account</a></span>
                    </div>
                </div>
            </main>
        </div>
    <script>
        const profileBox = document.getElementById('profileBox');
        const profileInput = document.getElementById('profileImageInput');
        const profilePreview = document.getElementById('profilePreview');
        const uploadHint = document.getElementById('uploadHint');

        function showPreview(src) {
            profilePreview.src = src;
            profilePreview.style.display = 'block';
            uploadHint.style.display = 'none';
            profileBox.querySelector('i').style.display = 'none';
        }

        profileBox.addEventListener('click', () => {
            profileInput.click();
        });

        profileInput.addEventListener('change', event => {
            const file = event.target.files[0];
            if (!file) return;
            const validTypes = ['image/png', 'image/jpeg', 'image/jpg'];
            if (!validTypes.includes(file.type)) {
                alert('Only PNG and JPG images are allowed.');
                profileInput.value = '';
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                alert('Image must be smaller than 5MB.');
                profileInput.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = () => {
                showPreview(reader.result);
                localStorage.setItem('studentProfileImage', reader.result);
            };
            reader.readAsDataURL(file);
        });

        document.getElementById('saveProfile').addEventListener('click', () => {
            const studentProfileData = {
                name: document.getElementById('name').value,
                dob: document.getElementById('dob').value,
                email: document.getElementById('email').value,
                course: document.getElementById('course').value,
                gender: document.getElementById('gender').value,
                username: document.getElementById('username').value
            };
            localStorage.setItem('studentProfileData', JSON.stringify(studentProfileData));
            window.location.href = '/DORCFSRF/pages/student_dashboard/settings.php';
        });

        window.addEventListener('DOMContentLoaded', () => {
            const savedImage = localStorage.getItem('studentProfileImage');
            if (savedImage) {
                showPreview(savedImage);
            }
            const savedData = localStorage.getItem('studentProfileData');
            if (savedData) {
                const data = JSON.parse(savedData);
                document.getElementById('name').value = data.name || document.getElementById('name').value;
                document.getElementById('dob').value = data.dob || document.getElementById('dob').value;
                document.getElementById('email').value = data.email || document.getElementById('email').value;
                document.getElementById('course').value = data.course || document.getElementById('course').value;
                document.getElementById('gender').value = data.gender || document.getElementById('gender').value;
                document.getElementById('username').value = data.username || document.getElementById('username').value;
            }
        });
    </script>
</body>
</html>









