<?php
session_start();
require_once 'db_connect.php';

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$admin_id = $_SESSION['admin_id'];

// Handle AJAX requests
if(isset($_GET['action']) || isset($_POST['action'])) {
    $action = isset($_GET['action']) ? $_GET['action'] : $_POST['action'];
    
    switch($action) {
        case 'get_students':
            getStudents();
            break;
        case 'search_students':
            searchStudents();
            break;
        case 'get_tutors':
            getTutors();
            break;
        case 'search_tutors':
            searchTutors();
            break;
        case 'get_admins':
            getAdmins();
            break;
        case 'change_password':
            changePassword();
            break;
        case 'get_recent_activity':
            getRecentActivity();
            break;
        case 'delete_student':
            deleteStudent();
            break;
        case 'delete_tutor':
            deleteTutor();
            break;
        case 'delete_admin':
            deleteAdmin();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No action specified']);
}

// Function to get students
function getStudents() {
    global $conn;
    
    $sql = "SELECT student_id, name, email, grade, parent_name, created_at FROM students ORDER BY name ASC";
    $result = $conn->query($sql);
    
    if($result->num_rows > 0) {
        echo '<table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Grade</th>
                        <th>Parent</th>
                        <th>Date Added</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>';
        
                while($row = $result->fetch_assoc()) {
                    echo '<tr>
                        <td>'.$row['student_id'].'</td>
                        <td>'.htmlspecialchars($row['name'] ?? '').'</td>
                        <td>'.htmlspecialchars($row['email'] ?? '').'</td>
                        <td>'.htmlspecialchars($row['grade'] ?? '').'</td>
                        <td>'.htmlspecialchars($row['parent_name'] ?? '').'</td>
                        <td>'.date('Y-m-d', strtotime($row['created_at'])).'</td>
                        <td>
                            <a href="admin_edit_student.php?id='.$row['student_id'].'" class="edit-btn">Edit</a>
                            <button class="delete-btn" data-id="'.$row['student_id'].'" data-type="student">Delete</button>
                        </td>
                    </tr>';
                }
        
        
        echo '</tbody></table>';
    } else {
        echo '<p>No students found.</p>';
    }
}

// Function to search students
function searchStudents() {
    global $conn;
    
    $term = sanitize_input($_GET['term']);
    
    $sql = "SELECT student_id, name, email, grade, parent_name, created_at FROM students 
            WHERE name LIKE '%$term%' OR email LIKE '%$term%' OR parent_name LIKE '%$term%' 
            ORDER BY name ASC";
    $result = $conn->query($sql);
    
    if($result->num_rows > 0) {
        echo '<table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Grade</th>
                        <th>Parent</th>
                        <th>Date Added</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>';
        
                while($row = $result->fetch_assoc()) {
                    echo '<tr>
                        <td>'.$row['student_id'].'</td>
                        <td>'.htmlspecialchars($row['name'] ?? '').'</td>
                        <td>'.htmlspecialchars($row['email'] ?? '').'</td>
                        <td>'.htmlspecialchars($row['grade'] ?? '').'</td>
                        <td>'.htmlspecialchars($row['parent_name'] ?? '').'</td>
                        <td>'.date('Y-m-d', strtotime($row['created_at'])).'</td>
                        <td>
                            <a href="admin_edit_student.php?id='.$row['student_id'].'" class="edit-btn">Edit</a>
                            <button class="delete-btn" data-id="'.$row['student_id'].'" data-type="student">Delete</button>
                        </td>
                    </tr>';
                }
        
        echo '</tbody></table>';
    } else {
        echo '<p>No students found matching "'.$term.'".</p>';
    }
}

// Function to get tutors
function getTutors() {
    global $conn;
    
    $sql = "SELECT id, name, email, created_at FROM tutors ORDER BY name ASC";
    $result = $conn->query($sql);
    
    if($result->num_rows > 0) {
        echo '<table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Date Added</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>';
        
        while($row = $result->fetch_assoc()) {
            echo '<tr>
                    <td>'.$row['id'].'</td>
                    <td>'.htmlspecialchars($row['name']).'</td>
                    <td>'.htmlspecialchars($row['email']).'</td>
                    <td>'.date('Y-m-d', strtotime($row['created_at'])).'</td>
                    <td>
                        <a href="admin_edit_tutor.php?id='.$row['id'].'" class="edit-btn">Edit</a>
                        <button class="delete-btn" data-id="'.$row['id'].'" data-type="tutor">Delete</button>
                    </td>
                </tr>';
        }
        
        echo '</tbody></table>';
    } else {
        echo '<p>No tutors found.</p>';
    }
}

// Function to search tutors
function searchTutors() {
    global $conn;
    
    $term = sanitize_input($_GET['term']);
    
    $sql = "SELECT id, name, email, created_at FROM tutors 
            WHERE name LIKE '%$term%' OR email LIKE '%$term%' 
            ORDER BY name ASC";
    $result = $conn->query($sql);
    
    if($result->num_rows > 0) {
        echo '<table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Date Added</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>';
        
        while($row = $result->fetch_assoc()) {
            echo '<tr>
                    <td>'.$row['id'].'</td>
                    <td>'.htmlspecialchars($row['name']).'</td>
                    <td>'.htmlspecialchars($row['email']).'</td>
                    <td>'.date('Y-m-d', strtotime($row['created_at'])).'</td>
                    <td>
                        <a href="admin_edit_tutor.php?id='.$row['id'].'" class="edit-btn">Edit</a>
                        <button class="delete-btn" data-id="'.$row['id'].'" data-type="tutor">Delete</button>
                    </td>
                </tr>';
        }
        
        echo '</tbody></table>';
    } else {
        echo '<p>No tutors found matching "'.$term.'".</p>';
    }
}

// Function to get admins
function getAdmins() {
    global $conn;
    global $admin_id;
    
    $sql = "SELECT admin_id, username, email, created_at FROM admins ORDER BY username ASC";
    $result = $conn->query($sql);
    
    if($result->num_rows > 0) {
        echo '<table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Date Added</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>';
        
        while($row = $result->fetch_assoc()) {
            $actions = '<a href="admin_edit_admin.php?id='.$row['admin_id'].'" class="edit-btn">Edit</a>';
            
            // Don't allow deleting your own account
            if($row['admin_id'] != $admin_id) {
                $actions .= ' <button class="delete-btn" data-id="'.$row['admin_id'].'" data-type="admin">Delete</button>';
            }
            
            echo '<tr>
                    <td>'.$row['admin_id'].'</td>
                    <td>'.htmlspecialchars($row['username']).'</td>
                    <td>'.htmlspecialchars($row['email']).'</td>
                    <td>'.date('Y-m-d', strtotime($row['created_at'])).'</td>
                    <td>'.$actions.'</td>
                </tr>';
        }
        
        echo '</tbody></table>';
    } else {
        echo '<p>No admin users found.</p>';
    }
}

// Function to change admin password
function changePassword() {
    global $conn;
    global $admin_id;
    
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    
    // Get current admin info
    $sql = "SELECT password_hash FROM admins WHERE admin_id = $admin_id";
    $result = $conn->query($sql);
    
    if($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        
        // Verify current password
        if(password_verify($current_password, $row['password_hash'])) {
            // Hash new password
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password
            $update_sql = "UPDATE admins SET password_hash = '$new_hash', updated_at = CURRENT_TIMESTAMP WHERE admin_id = $admin_id";
            if($conn->query($update_sql)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Admin not found']);
    }
}

// Function to get recent activity
function getRecentActivity() {
    global $conn;
    
    // Get recent lessons
    $lessons_sql = "SELECT l.id, l.student_name, l.lesson_date, l.lesson_type, l.session_status, t.name as tutor_name
                    FROM lessons l 
                    JOIN tutors t ON l.tutor_id = t.id
                    WHERE l.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
                    ORDER BY l.created_at DESC LIMIT 5";
    $lessons_result = $conn->query($lessons_sql);
    
    // Get recent homework
    $homework_sql = "SELECT h.homework_id, h.title, h.status, h.created_at, s.name as student_name
                    FROM homework h
                    JOIN students s ON h.student_id = s.student_id
                    WHERE h.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
                    ORDER BY h.created_at DESC LIMIT 5";
    $homework_result = $conn->query($homework_sql);
    
    echo '<div class="activity-section">';
    
    // Recent lessons
    echo '<h4>Recent Lessons</h4>';
    if($lessons_result->num_rows > 0) {
        echo '<ul class="activity-list">';
        while($row = $lessons_result->fetch_assoc()) {
            echo '<li>
                    <span class="date">'.date('M d', strtotime($row['lesson_date'])).'</span>
                    <span class="details">
                        '.htmlspecialchars($row['student_name']).' - '.htmlspecialchars($row['lesson_type']).' lesson with '.htmlspecialchars($row['tutor_name']).'
                        <span class="status '.$row['session_status'].'">'.$row['session_status'].'</span>
                    </span>
                </li>';
        }
        echo '</ul>';
    } else {
        echo '<p>No recent lessons.</p>';
    }
    
    // Recent homework
    echo '<h4>Recent Homework</h4>';
    if($homework_result->num_rows > 0) {
        echo '<ul class="activity-list">';
        while($row = $homework_result->fetch_assoc()) {
            echo '<li>
                    <span class="date">'.date('M d', strtotime($row['created_at'])).'</span>
                    <span class="details">
                        '.htmlspecialchars($row['student_name']).' - '.htmlspecialchars($row['title']).'
                        <span class="status '.$row['status'].'">'.$row['status'].'</span>
                    </span>
                </li>';
        }
        echo '</ul>';
    } else {
        echo '<p>No recent homework assignments.</p>';
    }
    
    echo '</div>';
}

// Function to delete a student
function deleteStudent() {
    global $conn;
    
    // Check if student ID is provided
    if(!isset($_POST['id'])) {
        echo json_encode(['success' => false, 'message' => 'Student ID is required']);
        return;
    }
    
    $student_id = (int)$_POST['id'];
    
    // Check if student exists and get their name for logging
    $check_sql = "SELECT name FROM students WHERE student_id = $student_id";
    $check_result = $conn->query($check_sql);
    
    if($check_result->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        return;
    }
    
    $student_name = $check_result->fetch_assoc()['name'];
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Delete related homework
        $delete_homework = "DELETE FROM homework WHERE student_id = $student_id";
        $conn->query($delete_homework);
        
        // Delete related lessons (if there's a direct relation)
        $delete_lessons = "DELETE FROM lessons WHERE student_id = $student_id";
        $conn->query($delete_lessons);
        
        // Finally delete the student
        $delete_student = "DELETE FROM students WHERE student_id = $student_id";
        $conn->query($delete_student);
        
        // Log the deletion action
        $admin_id = $_SESSION['admin_id'];
        $log_sql = "INSERT INTO admin_logs (admin_id, action, details) 
                   VALUES ($admin_id, 'DELETE', 'Deleted student: $student_name (ID: $student_id)')";
        $conn->query($log_sql);
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        // Rollback in case of error
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Function to delete a tutor
function deleteTutor() {
    global $conn;
    
    // Check if tutor ID is provided
    if(!isset($_POST['id'])) {
        echo json_encode(['success' => false, 'message' => 'Tutor ID is required']);
        return;
    }
    
    $tutor_id = (int)$_POST['id'];
    
    // Check if tutor exists and get their name for logging
    $check_sql = "SELECT name FROM tutors WHERE id = $tutor_id";
    $check_result = $conn->query($check_sql);
    
    if($check_result->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Tutor not found']);
        return;
    }
    
    $tutor_name = $check_result->fetch_assoc()['name'];
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Check if tutor has any lessons
        $lessons_check = "SELECT COUNT(*) as lesson_count FROM lessons WHERE tutor_id = $tutor_id";
        $lessons_result = $conn->query($lessons_check);
        $lesson_count = $lessons_result->fetch_assoc()['lesson_count'];
        
        if($lesson_count > 0) {
            // Update lessons to NULL or reassign them
            // For this example, we'll mark the tutor as NULL in lessons
            $update_lessons = "UPDATE lessons SET tutor_id = NULL WHERE tutor_id = $tutor_id";
            $conn->query($update_lessons);
        }
        
        // Finally delete the tutor
        $delete_tutor = "DELETE FROM tutors WHERE id = $tutor_id";
        $conn->query($delete_tutor);
        
        // Log the deletion action
        $admin_id = $_SESSION['admin_id'];
        $log_sql = "INSERT INTO admin_logs (admin_id, action, details) 
                   VALUES ($admin_id, 'DELETE', 'Deleted tutor: $tutor_name (ID: $tutor_id)')";
        $conn->query($log_sql);
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        // Rollback in case of error
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Function to delete an admin
function deleteAdmin() {
    global $conn;
    global $admin_id;
    
    // Check if admin ID is provided
    if(!isset($_POST['id'])) {
        echo json_encode(['success' => false, 'message' => 'Admin ID is required']);
        return;
    }
    
    $target_admin_id = (int)$_POST['id'];
    
    // Don't allow deleting your own account
    if($target_admin_id == $admin_id) {
        echo json_encode(['success' => false, 'message' => 'You cannot delete your own account']);
        return;
    }
    
    // Check if admin exists and get their username for logging
    $check_sql = "SELECT username FROM admins WHERE admin_id = $target_admin_id";
    $check_result = $conn->query($check_sql);
    
    if($check_result->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Admin not found']);
        return;
    }
    
    $admin_username = $check_result->fetch_assoc()['username'];
    
    // Check if this is the last admin account
    $count_sql = "SELECT COUNT(*) as admin_count FROM admins";
    $count_result = $conn->query($count_sql);
    $admin_count = $count_result->fetch_assoc()['admin_count'];
    
    if($admin_count <= 1) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete the last admin account']);
        return;
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Delete the admin
        $delete_admin = "DELETE FROM admins WHERE admin_id = $target_admin_id";
        $conn->query($delete_admin);
        
        // Log the deletion action
        $log_sql = "INSERT INTO admin_logs (admin_id, action, details) 
                   VALUES ($admin_id, 'DELETE', 'Deleted admin: $admin_username (ID: $target_admin_id)')";
        $conn->query($log_sql);
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        // Rollback in case of error
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// Helper function to sanitize input data
function sanitize_input($data) {
    global $conn;
    return $conn->real_escape_string(trim($data));
}
?>