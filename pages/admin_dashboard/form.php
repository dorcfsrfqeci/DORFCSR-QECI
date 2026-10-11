<?php
ini_set('session.gc_maxlifetime', 31536000);
session_set_cookie_params(31536000);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') {
    header('Location: /DORCFSRF/pages/login_form/index.php');
    exit;
}
include __DIR__ . '/../../db_connect.php';
include __DIR__ . '/../../api/notification_system.php';
$full_name = $_SESSION['full_name'] ?? $_SESSION['username'];
$admin_id = $_SESSION['user_id'];

// Get unread notification count
$unreadCount = getUnreadNotificationCount($conn, $admin_id);
$notifications = getNotifications($conn, $admin_id, 5);

// Fetch all students who have submitted forms
$studentsQuery = "SELECT student_id, CONCAT(student_firstname, ' ', student_lastname) as name, CONCAT(LOWER(REPLACE(CONCAT(student_firstname, student_lastname), ' ', '')), '@gmail.com') as email, updated_at, 'Pending' as status FROM student_information_database ORDER BY updated_at DESC";
$studentsResult = $conn->query($studentsQuery);
$allStudents = [];
if ($studentsResult && $studentsResult->num_rows > 0) {
    while ($row = $studentsResult->fetch_assoc()) {
        $allStudents[] = [
            'id' => $row['student_id'],
            'name' => htmlspecialchars($row['name']),
            'email' => htmlspecialchars($row['email']),
            'date' => date('M d, Y', strtotime($row['updated_at'])),
            'status' => $row['status']
        ];
    }
}

// Fetch individual student details if viewing
$studentDetails = null;
if (isset($_GET['view_id'])) {
    $viewId = $conn->real_escape_string($_GET['view_id']);
    $detailQuery = "SELECT * FROM student_information_database WHERE student_id = '$viewId'";
    $detailResult = $conn->query($detailQuery);
    if ($detailResult && $detailResult->num_rows > 0) {
        $studentDetails = $detailResult->fetch_assoc();
    }
}

// Paginate: 5 students per page
$studentsPerPage = 5;
$paginatedStudents = [];
for ($i = 0; $i < count($allStudents); $i += $studentsPerPage) {
    $pageNum = floor($i / $studentsPerPage) + 1;
    $paginatedStudents[$pageNum] = array_slice($allStudents, $i, $studentsPerPage);
}
// Convert to JSON for JavaScript
$studentsJSON = json_encode($paginatedStudents);

// Get stored profile image
$profile_image = '/DORCFSRF/assets/admin_profiles/' . $admin_id . '_profile.jpg';
$profile_image_alt = '/DORCFSRF/assets/admin_profiles/' . $admin_id . '_profile.png';
$profile_image_alt2 = '/DORCFSRF/assets/admin_profiles/' . $admin_id . '_profile.gif';

// Check which image exists
if (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image)) {
    $profile_image_url = $profile_image;
} elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image_alt)) {
    $profile_image_url = $profile_image_alt;
} elseif (file_exists($_SERVER['DOCUMENT_ROOT'] . $profile_image_alt2)) {
    $profile_image_url = $profile_image_alt2;
} else {
    $profile_image_url = null;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dashboard</title>
    <link rel="stylesheet" href="/DORCFSRF/css/dashboard.css" />
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">    
</head>

<body>
    <div class="container">
        <aside class="sidebar">
        <img src="/DORCFSRF/assets/QECI LOGO.jpg" alt="qecilogo" />
           <nav>
                <a href="dashboard.php"><i class="bx bxs-dashboard"> </i> Dashboard</a>
                <a href="form.php" class="active"><i class="bx bx-notepad"></i> Form</a>
                <a href="clearance_status.php"><i class="bx bxs-graduation"></i> Clearance Status</a>
                <a href="payment_transaction.php"><i class="bx bx-coin-stack"></i> Payment Transaction</a>
                <a href="scholastic_record_status.php"><i class="bx bx-bar-chart-alt-2"></i> Scholastic Record Status</a>
                <a href="completion_report.php"><i class="bx bxs-calendar"></i> Completion Report</a>
                <p>SETTINGS</p>
          <a href="/DORCFSRF/pages/admin_dashboard/audit_trail.php"><i class="bx bx-history"></i> Audit Trail</a>
        <a href="/DORCFSRF/pages/admin_dashboard/account_management.php"><i class='bx bx-briefcase-alt'></i> Account Management</a>
                <a href="account_settings.php"><i class="bx bx-cog"></i> Account Settings</a>
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
                <div class="user-icon">
                    <div class="notification-wrapper" id="notif-trigger" style="position: relative; display: inline-block; cursor: pointer;">
                        <i class="bx bx-bell"></i>
                        <?php if ($unreadCount > 0): ?>
                            <span class="notification-badge" style="position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; border-radius: 50%; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold;"><?php echo $unreadCount; ?></span>
                        <?php endif; ?>
                    </div>
                    <a href="/DORCFSRF/pages/click_button/aboutus.php"><i class="bx bx-globe bx-flip-vertical"></i></a>
                   <div class="user-menu-container">
                        <div class="profile-icon-wrapper" id="profile-toggle">
                            <?php if ($profile_image_url): ?>
                                <img src="<?php echo htmlspecialchars($profile_image_url); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;" />
                            <?php else: ?>
                                <i class="bx bxs-user-circle"></i>
                            <?php endif; ?>
                        </div>
                        <div class="profile-card" id="profile-dropdown">
                            <div class="card-header-bg"></div>
                            <div class="profile-img">
                                <?php if ($profile_image_url): ?>
                                    <img src="<?php echo htmlspecialchars($profile_image_url); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;" />
                                <?php else: ?>
                                    <i class="bx bxs-user-circle" style="font-size: 70px; color: #fff;"></i>
                                <?php endif; ?>
                            </div>
                            <div class="profile-info">
                                <h3><?php echo htmlspecialchars($full_name); ?></h3>
                                <p>ADMIN</p>
                            </div>
                            <div class="card-actions">
                                <a href="account_settings.php" class="action-btn action-btn-primary">Profile</a>
                                <a href="/DORCFSRF/pages/logout.php" class="action-btn action-btn-secondary">Sign Out</a>
                            </div>
                            <script src="/DORCFSRF/pages/script/dropdown.js"></script>
                        </div>
                    </div>

                    <!-- Notification Dropdown -->
                    <div class="notification-dropdown" id="notif-dropdown" style="display: none; position: absolute; top: 60px; right: 80px; width: 350px; background: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 1000; max-height: 400px; overflow-y: auto;">
                        <div style="padding: 15px 20px; border-bottom: 1px solid #eee; background: #f8f9fa; border-radius: 8px 8px 0 0;">
                            <h4 style="margin: 0; color: #333; font-size: 16px;">Notifications</h4>
                        </div>
                        <?php if (empty($notifications)): ?>
                            <div style="padding: 30px 15px; text-align: center; color: #999;">
                                <p>No notifications yet</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($notifications as $notif): ?>
                                <div class="notification-item" data-notif-id="<?php echo $notif['id']; ?>" style="padding: 15px; border-bottom: 1px solid #eee; cursor: pointer; background: <?php echo $notif['is_read'] ? '#fff' : '#f0f7ff'; ?>; transition: background 0.2s;" onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='<?php echo $notif['is_read'] ? '#fff' : '#f0f7ff'; ?>'">
                                    <div style="display: flex; justify-content: space-between; align-items: start;">
                                        <div style="flex: 1;">
                                            <p style="margin: 0 0 5px 0; font-weight: 600; color: #333;"><?php echo htmlspecialchars($notif['student_name']); ?></p>
                                            <p style="margin: 0 0 5px 0; color: #666; font-size: 13px;"><?php echo htmlspecialchars($notif['message']); ?></p>
                                            <p style="margin: 0; color: #999; font-size: 12px;"><?php echo date('M d, Y g:ia', strtotime($notif['created_at'])); ?></p>
                                        </div>
                                        <?php if (!$notif['is_read']): ?>
                                            <div style="width: 10px; height: 10px; background: #007bff; border-radius: 50%; margin-left: 10px; margin-top: 5px;"></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <script>
                                    document.querySelector('[data-notif-id="<?php echo $notif['id']; ?>"]').addEventListener('click', function() {
                                        fetch('/DORCFSRF/api/mark_notification_read.php?id=<?php echo $notif['id']; ?>');
                                    });
                                </script>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
            </header>
            <main class="main-content">
                <main class="page-content-wrapper" style="width: 100%; max-width: 1200px; margin: 0 auto; padding: 20px;">
                    <div class="page-background-mock">

                        <div class="breadcrumbs" style="color: white;">
                            <h4><span>Home ></span>
                            <span class="current">Forms</span></h4>
                        </div>

                        <div class="forms-card">

                            <header class="forms-card-header">
                                <h2>Student Clearance and Scholastic Record Forms</h2>
                                <i class="fas fa-info-circle info-icon"></i>
                            </header>

                            <?php if ($studentDetails): ?>
                                <div style="background: #f0f4f8; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                        <h3><?php echo htmlspecialchars($studentDetails['student_firstname'] . ' ' . $studentDetails['student_lastname']); ?></h3>
                                        <a href="form.php" style="background: #007bff; color: white; padding: 8px 16px; border-radius: 5px; text-decoration: none;">Back to List</a>
                                    </div>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                        <div><strong>Student ID:</strong> <?php echo htmlspecialchars($studentDetails['student_id']); ?></div>
                                        <div><strong>Name:</strong> <?php echo htmlspecialchars($studentDetails['student_firstname'] . ' ' . $studentDetails['student_lastname']); ?></div>
                                        <div><strong>Year:</strong> <?php echo htmlspecialchars($studentDetails['student_year']); ?></div>
                                        <div><strong>Course:</strong> <?php echo htmlspecialchars($studentDetails['student_course']); ?></div>
                                        <div><strong>Section:</strong> <?php echo htmlspecialchars($studentDetails['student_section']); ?></div>
                                        <div><strong>Semester:</strong> <?php echo htmlspecialchars($studentDetails['student_sem']); ?></div>
                                        <div><strong>Contact:</strong> <?php echo htmlspecialchars($studentDetails['contact_num']); ?></div>
                                        <div><strong>Date of Birth:</strong> <?php echo htmlspecialchars($studentDetails['date_of_birth']); ?></div>
                                        <div><strong>Permanent Address:</strong> <?php echo htmlspecialchars($studentDetails['permanent_address']); ?></div>
                                        <div><strong>Year Graduated:</strong> <?php echo htmlspecialchars($studentDetails['year_graduated']); ?></div>
                                        <div><strong>Elementary School:</strong> <?php echo htmlspecialchars($studentDetails['elementary_school']); ?></div>
                                        <div><strong>Junior HS:</strong> <?php echo htmlspecialchars($studentDetails['jhs_school']); ?></div>
                                        <div><strong>Senior HS:</strong> <?php echo htmlspecialchars($studentDetails['shs_school']); ?></div>
                                        <div><strong>College Community:</strong> <?php echo htmlspecialchars($studentDetails['college_community']); ?></div>
                                        <div><strong>Reason for Clearance:</strong> <?php echo htmlspecialchars($studentDetails['reason_clearance']); ?></div>
                                        <div><strong>Scholastic Document:</strong> <?php echo htmlspecialchars($studentDetails['scholastic_document']); ?></div>
                                        <div><strong>Submitted:</strong> <?php echo date('M d, Y g:ia', strtotime($studentDetails['updated_at'])); ?></div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="search-filter-row">
                                <div class="search-dropdown-container">
                                    <input type="text" placeholder="Search by name or I.D">
                                </div>
                                <button class="search-button">Search</button>
                            </div>
                            <div class="students-list-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>STUDENTS</th>
                                            <th>SUBMISSION DATE</th>
                                            <th>FORM</th>
                                        </tr>
                                    </thead>
                                    <tbody id="studentListBody">
                                    </tbody>
                                </table>
                            </div>
                            <div class="pagination-controls">
                                <i class="fas fa-angle-left page-arrow"></i>
                                <button class="page-number active-page">1</button>
                                <button class="page-number">2</button>
                                <button class="page-number">3</button>
                                <button class="page-number">4</button>
                                <i class="fas fa-angle-right page-arrow"></i>
                            </div>

                        </div>
                    </div>
                </main>
        </div>
    </div>
    </main>
   <<script>
    document.addEventListener('DOMContentLoaded', function() {
        const pageNumbers = document.querySelectorAll('.page-number');
        const studentListBody = document.getElementById('studentListBody');
        
        // Get the search elements
        const searchInput = document.querySelector('.search-dropdown-container input');
        const searchButton = document.querySelector('.search-button');

        // ------------------------------------
        // --- FETCHED DATA FROM DATABASE (via PHP) ---
        const allStudentData = <?php echo $studentsJSON; ?>;
        const masterStudentList = Object.values(allStudentData).flat();
        // ------------------------------------
        
        /**
         * Finds the student ID and redirects to view the details on this page.
         */
        function handleViewClick(studentId) {
            const detailsPageUrl = '/DORCFSRF/pages/click_button/staff_Form.php'; 
            const fullUrl = `${detailsPageUrl}?id=${encodeURIComponent(studentId)}`;
            window.location.href = fullUrl;
        }

        /**
         * Renders the students list into the table body based on the provided list of students.
         */
        function renderStudents(studentsToRender) {
            
            if (!studentsToRender || studentsToRender.length === 0) {
                studentListBody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 20px;">No student records found.</td></tr>';
                return;
            }

            // Generate HTML rows
            let rowsHtml = studentsToRender.map(student => {
                return `
                    <tr>
                        <td class="student-name">${student.name}</td>
                        <td>${student.date}</td>
                        <td><button class="view-button" data-student-id="${student.id}">View</button></td>
                    </tr>
                `;
            }).join('');

            studentListBody.innerHTML = rowsHtml;
        }

        // --- Event Delegation for 'View' Button ---
        studentListBody.addEventListener('click', function(event) {
            const clickedElement = event.target;
            
            // Check if the clicked element is a button with the class 'view-button'
            if (clickedElement.classList.contains('view-button')) {
                const studentId = clickedElement.getAttribute('data-student-id');
                if (studentId) {
                    handleViewClick(studentId);
                }
            }
        });

        
        /**
         * Resets the search state, re-enables pagination, and re-renders the current page.
         */
        function handleSearchReset() {
            // 1. Clear search input and reset button text
            searchInput.value = '';
            searchButton.textContent = 'Search';
            searchButton.removeEventListener('click', handleSearchReset);
            searchButton.addEventListener('click', handleSearchClick);

            // 2. Re-enable pagination buttons
            pageNumbers.forEach(btn => btn.disabled = false);

            // 3. Render the current active page list
            const currentPage = document.querySelector('.page-number.active-page').textContent;
            renderStudents(allStudentData[currentPage]);
        }
        
        /**
         * Performs the search operation.
         */
        function searchStudents(query) {
            const lowerCaseQuery = query.toLowerCase().trim();
            
            if (lowerCaseQuery === '') {
                if (searchButton.textContent !== 'Search') {
                    handleSearchReset();
                } else {
                    alert("Please enter a name or ID to search.");
                }
                return;
            }

            // Filter the master list
            const searchResults = masterStudentList.filter(student => 
                student.name.toLowerCase().includes(lowerCaseQuery) ||
                student.id.toLowerCase().includes(lowerCaseQuery)
            );
            
            // Render the results
            renderStudents(searchResults);
            
            // Disable pagination buttons and change search button to "Reset"
            pageNumbers.forEach(btn => btn.disabled = true);
            searchButton.textContent = 'Reset'; 
            searchButton.removeEventListener('click', handleSearchClick);
            searchButton.addEventListener('click', handleSearchReset);
        }

        /**
         * Handles the click event for the initial search button.
         */
        function handleSearchClick() {
            searchStudents(searchInput.value);
        }


        /**
         * Handles the click event for pagination buttons.
         */
        function handlePageClick(clickedButton) {
            const page = clickedButton.textContent;
            
            // 1. Update active class visually
            pageNumbers.forEach(btn => btn.classList.remove('active-page'));
            clickedButton.classList.add('active-page');
            
            // 2. Reset search state and render the new page list
            handleSearchReset(); 
            
            // Re-render the content for the newly active page
            renderStudents(allStudentData[page]); 

            console.log(`Showing students list for page ${page}`);
        }

        // --- Initialization and Event Listeners Setup ---
        
        // 1. Attach the initial click handler to the Search button
        searchButton.addEventListener('click', handleSearchClick);

        // 2. Allow search on Enter key press in the input field
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault(); 
                searchStudents(searchInput.value);
            }
        });

        // 3. Attach click handlers to all page buttons
        pageNumbers.forEach(button => {
            button.addEventListener('click', function() {
                handlePageClick(this);
            });
        });

        // 4. Initial load: Render the content for the default active page (Page 1)
        const initialPage = document.querySelector('.page-number.active-page');
        if (initialPage) {
            renderStudents(allStudentData[initialPage.textContent]);
        }
    });
</script>

<!-- Notification Dropdown Toggle -->
<script>
    document.getElementById('notif-trigger').addEventListener('click', function() {
        const dropdown = document.getElementById('notif-dropdown');
        dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
    });

    // Close notification dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const trigger = document.getElementById('notif-trigger');
        const dropdown = document.getElementById('notif-dropdown');
        if (!trigger.contains(event.target) && !dropdown.contains(event.target)) {
            dropdown.style.display = 'none';
        }
    });
</script>

<script src="/DORCFSRF/pages/script/dropdown.js"></script>
<script src="/DORCFSRF/pages/script/prevent-back-button.js"></script>
</body>

</html>









