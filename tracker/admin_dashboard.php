<?php
session_start();
require_once 'db_connect.php';

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Get admin information
$admin_id = $_SESSION['admin_id'];
$admin_username = $_SESSION['admin_username'];

// Get basic statistics
$stats = array();

// Count students
$sql_students = "SELECT COUNT(*) as total_students FROM students";
$result_students = $conn->query($sql_students);
$stats['students'] = $result_students->fetch_assoc()['total_students'];

// Count tutors
$sql_tutors = "SELECT COUNT(*) as total_tutors FROM tutors";
$result_tutors = $conn->query($sql_tutors);
$stats['tutors'] = $result_tutors->fetch_assoc()['total_tutors'];

// Count upcoming lessons (scheduled within next 7 days)
$sql_lessons = "SELECT COUNT(*) as upcoming_lessons FROM lessons 
                WHERE lesson_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                AND session_status = 'Scheduled'";
$result_lessons = $conn->query($sql_lessons);
$stats['upcoming_lessons'] = $result_lessons->fetch_assoc()['upcoming_lessons'];

// Count pending homework
$sql_homework = "SELECT COUNT(*) as pending_homework FROM homework 
                WHERE status = 'Assigned' AND due_date >= CURDATE()";
$result_homework = $conn->query($sql_homework);
$stats['pending_homework'] = $result_homework->fetch_assoc()['pending_homework'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Bloom</title>
    <style>/* Base Variables */
:root {
  --background: #ffffff;
  --foreground: #0f172a;
  --card: #ffffff;
  --card-foreground: #0f172a;
  --popover: #ffffff;
  --popover-foreground: #0f172a;
  --primary: #6366f1;
  --primary-foreground: #ffffff;
  --secondary: #f1f5f9;
  --secondary-foreground: #0f172a;
  --muted: #f1f5f9;
  --muted-foreground: #64748b;
  --accent: #f1f5f9;
  --accent-foreground: #0f172a;
  --destructive: #ef4444;
  --destructive-foreground: #ffffff;
  --border: #e2e8f0;
  --input: #e2e8f0;
  --ring: #6366f1;
  --radius: 0.5rem;
}

/* Global Styles */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
}

body {
  background-color: var(--secondary);
  color: var(--foreground);
  font-size: 16px;
  line-height: 1.5;
}

.container {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 1rem;
}

/* Header Styles */
header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem 2rem;
  background-color: var(--background);
  border-bottom: 1px solid var(--border);
  margin-bottom: 1rem;
  border-radius: var(--radius);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  margin-top: 1rem;
}

header h1 {
  font-size: 1.8rem;
  font-weight: 600;
  color: var(--foreground);
}

.user-info {
  font-size: 0.9rem;
  color: var(--muted-foreground);
}

.user-info a {
  color: var(--primary);
  text-decoration: none;
  font-weight: 500;
  transition: color 0.2s ease;
}

.user-info a:hover {
  color: var(--primary);
  text-decoration: underline;
}

/* Navigation Styles */
nav {
  background-color: var(--background);
  border-radius: var(--radius);
  margin-bottom: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

nav ul {
  display: flex;
  list-style: none;
  overflow-x: auto;
  padding: 0 1rem;
}

nav li {
  margin-right: 0.5rem;
}

nav a {
  display: block;
  padding: 1rem 1.2rem;
  color: var(--muted-foreground);
  text-decoration: none;
  font-weight: 500;
  font-size: 0.95rem;
  border-bottom: 2px solid transparent;
  transition: all 0.2s ease;
}

nav a:hover {
  color: var(--foreground);
}

nav a.active {
  color: var(--primary);
  border-bottom: 2px solid var(--primary);
}

/* Main Content Area */
main {
  flex: 1;
  padding: 0;
}

.content-section {
  display: none;
  background-color: var(--background);
  border-radius: var(--radius);
  padding: 2rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  margin-bottom: 2rem;
}

.content-section.active {
  display: block;
}

.content-section h2 {
  font-size: 1.5rem;
  font-weight: 600;
  margin-bottom: 1.5rem;
  color: var(--foreground);
}

.content-section h3 {
  font-size: 1.2rem;
  font-weight: 600;
  margin-bottom: 1rem;
  color: var(--foreground);
}

/* Dashboard Stats */
.stats-container {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-box {
  background-color: var(--background);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-box:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.stat-box h3 {
  font-size: 1rem;
  color: var(--muted-foreground);
  margin-bottom: 0.5rem;
}

.stat-number {
  font-size: 2.2rem;
  font-weight: 600;
  color: var(--primary);
}

/* Recent Activity */
.recent-activity {
  background-color: var(--background);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

/* Action Buttons */
.action-buttons {
  margin-bottom: 1.5rem;
}

button {
  background-color: var(--primary);
  color: var(--primary-foreground);
  border: none;
  border-radius: var(--radius);
  padding: 0.6rem 1.2rem;
  font-size: 0.95rem;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

button:hover {
  background-color: color-mix(in srgb, var(--primary) 90%, black);
}

/* Search Container */
.search-container {
  margin-bottom: 1.5rem;
}

input[type="text"], 
input[type="password"] {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 0.95rem;
  color: var(--foreground);
  background-color: var(--background);
  transition: border-color 0.2s ease;
}

input[type="text"]:focus,
input[type="password"]:focus {
  outline: none;
  border-color: var(--ring);
  box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.1);
}

/* Form Styles */
.form-group {
  margin-bottom: 1.25rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-size: 0.95rem;
  font-weight: 500;
  color: var(--foreground);
}

/* Admin settings section */
.admin-list,
.password-change {
  margin-bottom: 2rem;
  background-color: var(--background);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 1.5rem;
}

/* Tables (for lists) */
table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 1rem;
}

table th {
  text-align: left;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid var(--border);
  font-weight: 500;
  color: var(--muted-foreground);
  font-size: 0.9rem;
}

table td {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid var(--border);
  color: var(--foreground);
  font-size: 0.95rem;
}

table tr:hover {
  background-color: var(--muted);
}

/* Responsive adjustments */
@media (max-width: 768px) {
  header {
    flex-direction: column;
    align-items: flex-start;
  }
  
  header h1 {
    margin-bottom: 0.5rem;
  }
  
  .stats-container {
    grid-template-columns: 1fr;
  }
  
  nav ul {
    flex-wrap: nowrap;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    padding-bottom: 0.5rem;
  }
  
  nav li {
    flex-shrink: 0;
  }
}</style>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
</head>
<body>
    <div class="container">
        <header>
            <h1>Admin Dashboard</h1>
            <div class="user-info">
                Welcome, <?php echo htmlspecialchars($admin_username); ?> | 
                <a href="admin_logout.php">Logout</a>
                <li><a href="lesson-schedule.php">Schedule</a></li>
            </div>
        </header>
        
        <nav>
            <ul>
                <li><a href="#" class="active" data-section="dashboard">Dashboard</a></li>
                <li><a href="#" data-section="students">Students</a></li>
                <li><a href="#" data-section="tutors">Tutors</a></li>
                <li><a href="#" data-section="lessons">Lessons</a></li>
                <li><a href="#" data-section="subjects">Subjects</a></li>
                
                <li><a href="#" data-section="admin-settings">Admin Settings</a></li>
            </ul>
        </nav>
        
        <main>
            <!-- Dashboard Section -->
            <section id="dashboard" class="content-section active">
                <h2>Overview</h2>
                <div class="stats-container">
                    <div class="stat-box">
                        <h3>Students</h3>
                        <p class="stat-number"><?php echo $stats['students']; ?></p>
                    </div>
                    <div class="stat-box">
                        <h3>Tutors</h3>
                        <p class="stat-number"><?php echo $stats['tutors']; ?></p>
                    </div>
                    <div class="stat-box">
                        <h3>Upcoming Lessons</h3>
                        <p class="stat-number"><?php echo $stats['upcoming_lessons']; ?></p>
                    </div>
                    <div class="stat-box">
                        <h3>Pending Homework</h3>
                        <p class="stat-number"><?php echo $stats['pending_homework']; ?></p>
                    </div>
                </div>
                <div class="recent-activity">
                    <h3>Recent Activity</h3>
                    <!-- This would be populated dynamically -->
                    <p>Loading recent activity...</p>
                </div>
            </section>
            
            <!-- Students Section -->
            <section id="students" class="content-section">
                <h2>Student Management</h2>
                <div class="action-buttons">
                    <button id="add-student-btn">Add New Student</button>
                </div>
                <div class="search-container">
                    <input type="text" id="student-search" placeholder="Search students...">
                </div>
                <div id="students-list">
                    <p>Loading students...</p>
                </div>
            </section>
            
            <!-- Tutors Section -->
            <section id="tutors" class="content-section">
                <h2>Tutor Management</h2>
                <div class="action-buttons">
                    <button id="add-tutor-btn">Add New Tutor</button>
                </div>
                <div class="search-container">
                    <input type="text" id="tutor-search" placeholder="Search tutors...">
                </div>
                <div id="tutors-list">
                    <p>Loading tutors...</p>
                </div>
            </section>
            
            <!-- Admin Settings Section -->
            <section id="admin-settings" class="content-section">
                <h2>Admin Settings</h2>
                <div class="admin-list">
                    <h3>Admin Users</h3>
                    <div class="action-buttons">
                        <button id="add-admin-btn">Add New Admin</button>
                    </div>
                    <div id="admins-list">
                        <p>Loading admins...</p>
                    </div>
                </div>
                <div class="password-change">
                    <h3>Change Password</h3>
                    <form id="change-password-form">
                        <div class="form-group">
                            <label for="current-password">Current Password:</label>
                            <input type="password" id="current-password" name="current_password" required>
                        </div>
                        <div class="form-group">
                            <label for="new-password">New Password:</label>
                            <input type="password" id="new-password" name="new_password" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm-password">Confirm New Password:</label>
                            <input type="password" id="confirm-password" name="confirm_password" required>
                        </div>
                        <button type="submit">Update Password</button>
                    </form>
                </div>
            </section>
        </main>
    </div>

    <script>
    $(document).ready(function() {
        // Navigation handling
        $('nav a').click(function(e) {
            e.preventDefault();
            const section = $(this).data('section');
            
            // Update active navigation link
            $('nav a').removeClass('active');
            $(this).addClass('active');
            
            // Show selected section, hide others
            $('.content-section').removeClass('active');
            $('#' + section).addClass('active');
            
            // Load section data if needed
            if (section === 'students') {
                loadStudents();
            } else if (section === 'tutors') {
                loadTutors();
            } else if (section === 'admin-settings') {
                loadAdmins();
            }
        });
        
        // Load students data
        function loadStudents() {
            $.ajax({
                url: 'admin_ajax.php',
                type: 'GET',
                data: {action: 'get_students'},
                success: function(response) {
                    $('#students-list').html(response);
                },
                error: function() {
                    $('#students-list').html('<p>Error loading students data.</p>');
                }
            });
        }
        
        // Load tutors data
        function loadTutors() {
            $.ajax({
                url: 'admin_ajax.php',
                type: 'GET',
                data: {action: 'get_tutors'},
                success: function(response) {
                    $('#tutors-list').html(response);
                },
                error: function() {
                    $('#tutors-list').html('<p>Error loading tutors data.</p>');
                }
            });
        }
        
        // Load admins data
        function loadAdmins() {
            $.ajax({
                url: 'admin_ajax.php',
                type: 'GET',
                data: {action: 'get_admins'},
                success: function(response) {
                    $('#admins-list').html(response);
                },
                error: function() {
                    $('#admins-list').html('<p>Error loading admins data.</p>');
                }
            });
        }
        
        // Student search
        $('#student-search').on('keyup', function() {
            const searchTerm = $(this).val();
            if (searchTerm.length >= 2) {
                $.ajax({
                    url: 'admin_ajax.php',
                    type: 'GET',
                    data: {
                        action: 'search_students',
                        term: searchTerm
                    },
                    success: function(response) {
                        $('#students-list').html(response);
                    }
                });
            } else if (searchTerm.length === 0) {
                loadStudents();
            }
        });
        
        // Tutor search
        $('#tutor-search').on('keyup', function() {
            const searchTerm = $(this).val();
            if (searchTerm.length >= 2) {
                $.ajax({
                    url: 'admin_ajax.php',
                    type: 'GET',
                    data: {
                        action: 'search_tutors',
                        term: searchTerm
                    },
                    success: function(response) {
                        $('#tutors-list').html(response);
                    }
                });
            } else if (searchTerm.length === 0) {
                loadTutors();
            }
        });
        
        // Add new student button
        $('#add-student-btn').click(function() {
            window.location.href = 'create-admin.php';
        });
        
        // Add new tutor button
        $('#add-tutor-btn').click(function() {
            window.location.href = 'admin_add_tutor.php';
        });
        
        // Add new admin button
        $('#add-admin-btn').click(function() {
            window.location.href = 'create-admin.php';
        });
        
        // Change password form submission
        $('#change-password-form').submit(function(e) {
            e.preventDefault();
            
            const currentPassword = $('#current-password').val();
            const newPassword = $('#new-password').val();
            const confirmPassword = $('#confirm-password').val();
            
            if (newPassword !== confirmPassword) {
                alert("New passwords do not match!");
                return;
            }
            
            $.ajax({
                url: 'admin_ajax.php',
                type: 'POST',
                data: {
                    action: 'change_password',
                    current_password: currentPassword,
                    new_password: newPassword
                },
                success: function(response) {
                    const result = JSON.parse(response);
                    if (result.success) {
                        alert("Password changed successfully!");
                        $('#change-password-form')[0].reset();
                    } else {
                        alert("Error: " + result.message);
                    }
                },
                error: function() {
                    alert("An error occurred. Please try again.");
                }
            });
        });
        
        // Load recent activity on dashboard
        function loadRecentActivity() {
            $.ajax({
                url: 'admin_ajax.php',
                type: 'GET',
                data: {action: 'get_recent_activity'},
                success: function(response) {
                    $('.recent-activity').html('<h3>Recent Activity</h3>' + response);
                },
                error: function() {
                    $('.recent-activity').html('<h3>Recent Activity</h3><p>Error loading recent activity.</p>');
                }
            });
        }
        
        // Initial load
        loadRecentActivity();
    });
    </script>
</body>
</html>