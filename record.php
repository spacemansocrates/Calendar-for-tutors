<?php
// Start session for authentication (only if not already started)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Connect to database
require_once 'db_connect.php';

// Determine user type and ID based on which session variable exists
if (isset($_SESSION['tutor_id'])) {
    $user_type = 'tutor';
    $user_id = $_SESSION['tutor_id'];
} elseif (isset($_SESSION['student_id'])) {
    $user_type = 'student';
    $user_id = $_SESSION['student_id'];
} else {
    // If neither exists, redirect to login
    header("Location: login.php");
    exit();
}

// Pagination settings
$records_per_page = 5;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $records_per_page;

// Prepare filters with security measures
$date_filter = isset($_GET['date_filter']) ? mysqli_real_escape_string($conn, $_GET['date_filter']) : '';
$status_filter = isset($_GET['status_filter']) ? mysqli_real_escape_string($conn, $_GET['status_filter']) : '';
$type_filter = isset($_GET['type_filter']) ? mysqli_real_escape_string($conn, $_GET['type_filter']) : '';
$search_query = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Get valid lesson statuses and types from database for dropdown options
$status_query = "SELECT DISTINCT session_status FROM lessons ORDER BY session_status";
$status_result = $conn->query($status_query);
$valid_statuses = [];
while ($row = $status_result->fetch_assoc()) {
    $valid_statuses[] = $row['session_status'];
}

$type_query = "SELECT DISTINCT lesson_type FROM lessons ORDER BY lesson_type";
$type_result = $conn->query($type_query);
$valid_types = [];
while ($row = $type_result->fetch_assoc()) {
    $valid_types[] = $row['lesson_type'];
}

// Build query based on user type
if ($user_type == 'tutor') {
    $base_query = "SELECT l.*, 
                   s.name AS student_full_name, 
                   s.email AS student_email,
                   s.student_id AS student_id
                   FROM lessons l 
                   LEFT JOIN students s ON l.student_name = s.name 
                   WHERE l.tutor_id = ?";
    
    $count_query = "SELECT COUNT(*) AS total FROM lessons l 
                    LEFT JOIN students s ON l.student_name = s.name 
                    WHERE l.tutor_id = ?";
} else {
    // For student users
    $student_query = "SELECT name FROM students WHERE student_id = ?";
    $stmt = $conn->prepare($student_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $student_name = $row['name'];
        $base_query = "SELECT l.*, 
                      t.name AS tutor_name, 
                      t.email AS tutor_email,
                      ? AS student_id
                      FROM lessons l 
                      LEFT JOIN tutors t ON l.tutor_id = t.id 
                      WHERE l.student_name = ?";
        
        $count_query = "SELECT COUNT(*) AS total FROM lessons l 
                        LEFT JOIN tutors t ON l.tutor_id = t.id 
                        WHERE l.student_name = ?";
    } else {
        die("Error: Student not found");
    }
}

// Add filters to query if selected
$params = [];
$types = "";
$count_params = [];
$count_types = "";

if ($user_type == 'tutor') {
    $params[] = $user_id;
    $types .= "i";
    $count_params[] = $user_id;
    $count_types .= "i";
} else {
    $params[] = $user_id; // Add student_id for the JOIN
    $params[] = $student_name;
    $types .= "is";
    $count_params[] = $student_name;
    $count_types .= "s";
}

// Add search functionality
if (!empty($search_query)) {
    // Search in multiple fields
    $base_query .= " AND (l.notes LIKE ? OR l.lesson_date LIKE ? OR l.student_name LIKE ?)";
    $count_query .= " AND (l.notes LIKE ? OR l.lesson_date LIKE ? OR l.student_name LIKE ?)";
    
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
    
    $count_params[] = $search_param;
    $count_params[] = $search_param;
    $count_params[] = $search_param;
    $count_types .= "sss";
}

if (!empty($date_filter)) {
    $base_query .= " AND l.lesson_date = ?";
    $count_query .= " AND l.lesson_date = ?";
    $params[] = $date_filter;
    $types .= "s";
    $count_params[] = $date_filter;
    $count_types .= "s";
}

if (!empty($status_filter)) {
    $base_query .= " AND l.session_status = ?";
    $count_query .= " AND l.session_status = ?";
    $params[] = $status_filter;
    $types .= "s";
    $count_params[] = $status_filter;
    $count_types .= "s";
}

if (!empty($type_filter)) {
    $base_query .= " AND l.lesson_type = ?";
    $count_query .= " AND l.lesson_type = ?";
    $params[] = $type_filter;
    $types .= "s";
    $count_params[] = $type_filter;
    $count_types .= "s";
}

// Add order by and limit for pagination
$base_query .= " ORDER BY l.lesson_date DESC, l.start_time DESC LIMIT ?, ?";
$params[] = $offset;
$params[] = $records_per_page;
$types .= "ii";

// Get total records count for pagination
$count_stmt = $conn->prepare($count_query);
if (!empty($count_params)) {
    $count_stmt->bind_param($count_types, ...$count_params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$count_row = $count_result->fetch_assoc();
$total_records = $count_row['total'];
$total_pages = ceil($total_records / $records_per_page);

// Prepare and execute the main query
$stmt = $conn->prepare($base_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Function to get homework for a lesson
function getHomeworkForLesson($conn, $student_id, $tutor_id, $lesson_date) {
    $homework_query = "SELECT * FROM homework 
                      WHERE student_id = ? AND tutor_id = ? 
                      AND DATE(created_at) = ?
                      ORDER BY due_date ASC";
    
    $homework_stmt = $conn->prepare($homework_query);
    $homework_stmt->bind_param("iis", $student_id, $tutor_id, $lesson_date);
    $homework_stmt->execute();
    return $homework_stmt->get_result();
}

// Function to calculate completion time
function calculateLessonDuration($start_time, $end_time) {
    $start = new DateTime($start_time);
    $end = new DateTime($end_time);
    $diff = $start->diff($end);
    
    $hours = $diff->h;
    $minutes = $diff->i;
    
    if ($hours > 0) {
        return $hours . " hr " . ($minutes > 0 ? $minutes . " min" : "");
    } else {
        return $minutes . " min";
    }
}

// Get user details for the header
if ($user_type == 'tutor') {
    $user_query = "SELECT name FROM tutors WHERE id = ?";
} else {
    $user_query = "SELECT name FROM students WHERE student_id = ?";
}

$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$user_name = $user_data['name'] ?? 'User';

// Get counts for dashboard
$upcoming_query = "SELECT COUNT(*) as count FROM lessons 
                  WHERE " . ($user_type == 'tutor' ? "tutor_id = ?" : "student_name = ?") . "
                  AND lesson_date >= CURDATE() 
                  AND session_status = 'Scheduled'";

$delivered_query = "SELECT COUNT(*) as count FROM lessons 
                   WHERE " . ($user_type == 'tutor' ? "tutor_id = ?" : "student_name = ?") . "
                   AND session_status = 'Delivered'";

$upcoming_stmt = $conn->prepare($upcoming_query);
$param_type = ($user_type == 'tutor') ? "i" : "s";
$param_value = ($user_type == 'tutor') ? $user_id : $student_name;
$upcoming_stmt->bind_param($param_type, $param_value);
$upcoming_stmt->execute();
$upcoming_result = $upcoming_stmt->get_result();
$upcoming_count = $upcoming_result->fetch_assoc()['count'];

$delivered_stmt = $conn->prepare($delivered_query);
$param_type = ($user_type == 'tutor') ? "i" : "s";
$param_value = ($user_type == 'tutor') ? $user_id : $student_name;
$delivered_stmt->bind_param($param_type, $param_value);
$delivered_stmt->execute();
$delivered_result = $delivered_stmt->get_result();
$delivered_count = $delivered_result->fetch_assoc()['count'];

// Generate filter parameters for pagination links
function generateFilterParams($exclude = []) {
    $params = [];
    $allowed_params = ['date_filter', 'status_filter', 'type_filter', 'search'];
    
    foreach ($allowed_params as $param) {
        if (!in_array($param, $exclude) && isset($_GET[$param]) && $_GET[$param] !== '') {
            $params[] = $param . '=' . urlencode($_GET[$param]);
        }
    }
    
    return !empty($params) ? '&' . implode('&', $params) : '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lesson Recordings - TutorConnect</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #1cc88a;
            --danger-color: #e74a3b;
            --warning-color: #f6c23e;
            --info-color: #36b9cc;
            --dark-color: #5a5c69;
            --light-color: #f8f9fc;
            --border-color: #e3e6f0;
            --body-bg: #f8f9fc;
            --card-shadow: 0 .15rem 1.75rem 0 rgba(58,59,69,.15);
            --transition-speed: 0.3s;
        }
        
        body {
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: var(--body-bg);
            margin: 0;
            padding: 0;
            color: #333;
            line-height: 1.6;
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, var(--primary-color) 10%, #224abe 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: all var(--transition-speed);
            z-index: 100;
        }
        
        .sidebar-brand {
            padding: 1.5rem 1rem;
            text-align: center;
            font-size: 1.2rem;
            font-weight: 800;
            letter-spacing: 0.05rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-brand i {
            font-size: 1.75rem;
            margin-right: 0.5rem;
        }
        
        .sidebar-divider {
            border-top: 1px solid rgba(255,255,255,0.15);
            margin: 0 1rem;
        }
        
        .sidebar-heading {
            padding: 0.75rem 1rem;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            color: rgba(255,255,255,0.4);
        }
        
        .nav-item {
            position: relative;
        }
        
        .nav-link {
            display: block;
            padding: 0.75rem 1rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-weight: 700;
            transition: all var(--transition-speed);
        }
        
        .nav-link:hover, .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
            border-left: 4px solid white;
        }
        
        .nav-link i {
            margin-right: 0.5rem;
            width: 1.25rem;
            text-align: center;
        }
        
        .content-wrapper {
            flex: 1;
            margin-left: 250px;
            padding: 1.5rem;
            width: calc(100% - 250px);
            transition: all var(--transition-speed);
        }
        
        .topbar {
            background-color: white;
            box-shadow: 0 .15rem 1.75rem 0 rgba(58,59,69,.15);
            margin-bottom: 1.5rem;
            padding: 1rem;
            border-radius: 0.35rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .topbar-greeting {
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .dropdown {
            position: relative;
            display: inline-block;
        }
        
        .dropdown-toggle {
            background: transparent;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            font-weight: 500;
            color: var(--dark-color);
        }
        
        .dropdown-toggle img {
            height: 2rem;
            width: 2rem;
            border-radius: 50%;
            margin-right: 0.5rem;
        }
        
        .dropdown-menu {
            position: absolute;
            right: 0;
            top: 100%;
            background-color: white;
            box-shadow: var(--card-shadow);
            border-radius: 0.35rem;
            padding: 0.5rem 0;
            min-width: 10rem;
            display: none;
            z-index: 1000;
        }
        
        .dropdown-menu.show {
            display: block;
        }
        
        .dropdown-item {
            display: block;
            padding: 0.5rem 1.5rem;
            text-decoration: none;
            color: var(--dark-color);
        }
        
        .dropdown-item:hover {
            background-color: var(--light-color);
        }
        
        .dropdown-divider {
            height: 0;
            margin: 0.5rem 0;
            border-top: 1px solid var(--border-color);
        }
        
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .dashboard-card {
            background-color: white;
            border-radius: 0.35rem;
            box-shadow: var(--card-shadow);
            padding: 1.25rem;
            border-left: 0.25rem solid;
            position: relative;
            overflow: hidden;
        }
        
        .card-primary {
            border-left-color: var(--primary-color);
        }
        
        .card-success {
            border-left-color: var(--secondary-color);
        }
        
        .card-info {
            border-left-color: var(--info-color);
        }
        
        .card-warning {
            border-left-color: var(--warning-color);
        }
        
        .card-header {
            color: var(--dark-color);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.7rem;
            margin-bottom: 0.5rem;
        }
        
        .card-value {
            color: #5a5c69;
            font-size: 1.35rem;
            font-weight: 700;
            margin-bottom: 0;
        }
        
        .card-icon {
            position: absolute;
            top: 1.25rem;
            right: 1.25rem;
            color: rgba(58,59,69,.15);
            font-size: 2rem;
        }
        
        .container {
            background-color: white;
            border-radius: 0.35rem;
            box-shadow: var(--card-shadow);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .page-header {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .page-header h1 {
            font-size: 1.75rem;
            margin: 0;
            color: var(--dark-color);
            font-weight: 700;
        }
        
        .filters-section {
            background-color: var(--light-color);
            border-radius: 0.35rem;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
        }
        
        .filters-section h2 {
            font-size: 1.1rem;
            margin-top: 0;
            margin-bottom: 1rem;
            color: var(--dark-color);
        }
        
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: flex-end;
        }
        
        .filter-group {
            flex: 1;
            min-width: 150px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--dark-color);
        }
        
        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 0.375rem 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 0.35rem;
            background-color: white;
            font-size: 1rem;
        }
        
        .filter-form button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 0.35rem;
            padding: 0.375rem 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color var(--transition-speed);
        }
        
        .filter-form button:hover {
            background-color: #2e59d9;
        }
        
        .clear-filters-btn {
            background-color: var(--light-color);
            color: var(--dark-color);
            border: 1px solid var(--border-color);
            border-radius: 0.35rem;
            padding: 0.375rem 1rem;
            font-weight: 600;
            text-decoration: none;
            margin-left: 0.5rem;
            transition: background-color var(--transition-speed);
        }
        
        .clear-filters-btn:hover {
            background-color: #eaecf4;
        }
        
        .recordings-list {
            display: grid;
            gap: 1.5rem;
        }
        
        .recording-card {
            background-color: white;
            border-radius: 0.35rem;
            box-shadow: 0 0.1rem 0.5rem rgba(0,0,0,0.05);
            overflow: hidden;
            transition: transform var(--transition-speed), box-shadow var(--transition-speed);
        }
        
        .recording-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 0.2rem 1rem rgba(0,0,0,0.1);
        }
        
        .recording-header {
            padding: 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            border-bottom: 1px solid var(--border-color);
            background-color: var(--light-color);
        }
        
        .lesson-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--dark-color);
        }
        
        .lesson-time {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--dark-color);
            opacity: 0.8;
        }
        
        .lesson-badges {
            display: flex;
            gap: 0.5rem;
        }
        
        .lesson-type-badge,
        .lesson-status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 10rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
        }
        
        .badge-regular {
            background-color: rgba(78, 115, 223, 0.1);
            color: var(--primary-color);
        }
        
        .badge-demo {
            background-color: rgba(54, 185, 204, 0.1);
            color: var(--info-color);
        }
        
        .badge-catchup {
            background-color: rgba(246, 194, 62, 0.1);
            color: var(--warning-color);
        }
        
        .badge-scheduled {
            background-color: rgba(28, 200, 138, 0.1);
            color: var(--secondary-color);
        }
        
        .badge-delivered {
            background-color: rgba(78, 115, 223, 0.1);
            color: var(--primary-color);
        }
        
        .badge-no-show {
            background-color: rgba(231, 74, 59, 0.1);
            color: var(--danger-color);
        }
        
        .badge-cancelled {
            background-color: rgba(90, 92, 105, 0.1);
            color: var(--dark-color);
        }
        
        .badge-rescheduled {
            background-color: rgba(246, 194, 62, 0.1);
            color: var(--warning-color);
        }
        
        .video-container {
            position: relative;
            padding-top: 56.25%; /* 16:9 Aspect Ratio */
            background-color: #000;
        }
        
        .lesson-video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        
        .no-video-message {
            padding: 3rem;
            text-align: center;
            background-color: var(--light-color);
            color: var(--dark-color);
        }
        
        .no-video-message p {
            margin: 0;
            font-size: 1rem;
        }
        
        .lesson-details {
            padding: 1.25rem;
        }
        
        .participant-info {
            display: flex;
            gap: 2rem;
            margin-bottom: 1.25rem;
        }
        
        .student-info, 
        .tutor-info {
            flex: 1;
        }
        
        .student-info h4, 
        .tutor-info h4 {
            color: var(--dark-color);
            margin-top: 0;
            margin-bottom: 0.5rem;
            font-size: 1rem;
            font-weight: 700;
        }
        
        .student-info p, 
        .tutor-info p {
            margin: 0.25rem 0;
            color: var(--dark-color);
        }
        
        .collapsible {
            margin-bottom: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 0.35rem;
            overflow: hidden;
        }
        
        .collapsible-header {
            background-color: var(--light-color);
            padding: 0.75rem 1.25rem;
            margin: 0;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 1rem;
            font-weight: 700;
            color: var(--dark-color);
        }
        
        .toggle-icon {
            font-family: monospace;
            font-weight: bold;
            font-size: 1.2rem;
        }
        
        .collapsible-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height var(--transition-speed) ease;
            background-color: white;
        }
        
        .collapsible-content > div {
            padding: 1.25rem;
        }
        
        .formatted-notes {
            white-space: pre-line;
            color: var(--dark-color);
        }
        
        .no-notes-message, 
        .no-homework-message {
            color: #858796;
            font-style: italic;
        }
        
        .homework-item {
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 1rem;
            margin-bottom: 1rem;
        }
        
        .homework-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .homework-item h5 {
            margin-top: 0;
            margin-bottom: 0.5rem;
            font-size: 1rem;
            color: var(--dark-color);
        }
        
        .homework-description {
            margin-bottom: 1rem;
            white-space: pre-line;
        }
        
        .homework-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
        }
        
        .homework-meta p {
            margin: 0;
        }
        
        .due-date {
            font-weight: 600;
        }
        
        .status {
            padding: 0.2rem 0.5rem;
            border-radius: 0.25rem;
            font-weight: 600;
        }
        
        .assigned {
            background-color: rgba(78, 115, 223, 0.1);
            color: var(--primary-color);
        }
        
        .submitted {
            background-color: rgba(28, 200, 138, 0.1);
            color: var(--secondary-color);
        }
        
        .graded {
            background-color: rgba(54, 185, 204, 0.1);
            color: var(--info-color);
        }
        
        .overdue {
            background-color: rgba(231, 74, 59, 0.1);
            color: var(--danger-color);
        }
        
        .download-link {
            display: inline-flex;
            align-items: center;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }
        
        .download-link:hover {
            text-decoration: underline;
        }
        
        .download-link i {
            margin-right: 0.5rem;
        }
        
        .feedback-section {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px dashed var(--border-color);
        }
        
        .feedback-section h6 {
            font-size: 0.85rem;
            margin-top: 0;
            margin-bottom: 0.5rem;
            font-weight: 700;
            color: var(--dark-color);
        }
        
        .feedback-content {
            white-space: pre-line;
            font-size: 0.9rem;
            color: var(--dark-color);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
        }
        
        .pagination-item {
            padding: 0.5rem 0.75rem;
            margin: 0 0.pagination-item {
            padding: 0.5rem 0.75rem;
            margin: 0 0.25rem;
            border: 1px solid var(--border-color);
            border-radius: 0.35rem;
            color: var(--primary-color);
            background-color: white;
            text-decoration: none;
            transition: all var(--transition-speed);
        }

        .pagination-item:hover {
            background-color: var(--light-color);
        }

        .pagination-item.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .pagination-item.disabled {
            color: #858796;
            pointer-events: none;
        }

        .no-results {
            text-align: center;
            padding: 3rem;
            color: var(--dark-color);
            background-color: white;
            border-radius: 0.35rem;
            box-shadow: var(--card-shadow);
        }

        .no-results i {
            font-size: 3rem;
            color: #d1d3e2;
            margin-bottom: 1rem;
        }

        .no-results p {
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
        }

        .search-form {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .search-input {
            width: 100%;
            padding: 0.75rem 1rem;
            padding-right: 3rem;
            border: 1px solid var(--border-color);
            border-radius: 0.35rem;
            font-size: 1rem;
        }

        .search-button {
            position: absolute;
            right: 0;
            top: 0;
            height: 100%;
            width: 3rem;
            background: transparent;
            border: none;
            color: var(--dark-color);
            cursor: pointer;
        }

        /* Responsive adjustments */
        @media (max-width: 992px) {
            .sidebar {
                width: 6.5rem;
                overflow: visible;
            }
            
            .sidebar .sidebar-brand {
                padding: 1rem;
            }
            
            .sidebar .sidebar-brand span,
            .sidebar .nav-link span,
            .sidebar .sidebar-heading {
                display: none;
            }
            
            .sidebar .nav-item .nav-link {
                padding: 1rem;
                text-align: center;
            }
            
            .sidebar .nav-item .nav-link i {
                margin-right: 0;
                font-size: 1.25rem;
                width: auto;
            }
            
            .content-wrapper {
                margin-left: 6.5rem;
                width: calc(100% - 6.5rem);
            }
        }

        @media (max-width: 768px) {
            .dashboard-cards {
                grid-template-columns: 1fr;
            }
            
            .filter-form {
                flex-direction: column;
            }
            
            .filter-group {
                width: 100%;
            }
            
            .participant-info {
                flex-direction: column;
                gap: 1rem;
            }
        }

        @media (max-width: 576px) {
            .sidebar {
                width: 100%;
                position: relative;
                height: auto;
            }
            
            .sidebar-brand {
                display: flex;
                justify-content: center;
                align-items: center;
            }
            
            .sidebar-brand span {
                display: inline !important;
            }
            
            .content-wrapper {
                margin-left: 0;
                width: 100%;
            }
            
            .mobile-nav-toggle {
                display: block;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="sidebar-brand">
                <i class="fas fa-graduation-cap"></i>
                <span>TutorConnect</span>
            </div>
            <div class="sidebar-divider"></div>
            <div class="sidebar-heading">Core</div>
            <div class="nav-item">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </div>
            <div class="sidebar-divider"></div>
            <div class="sidebar-heading">Resources</div>
            <div class="nav-item">
                <a href="schedule.php" class="nav-link">
                    <i class="fas fa-fw fa-calendar"></i>
                    <span>Schedule</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="recording.php" class="nav-link active">
                    <i class="fas fa-fw fa-video"></i>
                    <span>Recordings</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="homework.php" class="nav-link">
                    <i class="fas fa-fw fa-book"></i>
                    <span>Homework</span>
                </a>
            </div>
            <?php if ($user_type == 'tutor'): ?>
            <div class="nav-item">
                <a href="students.php" class="nav-link">
                    <i class="fas fa-fw fa-user-graduate"></i>
                    <span>Students</span>
                </a>
            </div>
            <?php else: ?>
            <div class="nav-item">
                <a href="tutors.php" class="nav-link">
                    <i class="fas fa-fw fa-chalkboard-teacher"></i>
                    <span>My Tutors</span>
                </a>
            </div>
            <?php endif; ?>
            <div class="sidebar-divider"></div>
            <div class="sidebar-heading">Account</div>
            <div class="nav-item">
                <a href="profile.php" class="nav-link">
                    <i class="fas fa-fw fa-user"></i>
                    <span>Profile</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="settings.php" class="nav-link">
                    <i class="fas fa-fw fa-cog"></i>
                    <span>Settings</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="logout.php" class="nav-link">
                    <i class="fas fa-fw fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
        
        <div class="content-wrapper">
            <div class="topbar">
                <div class="topbar-greeting">
                    <h2>Welcome, <?php echo htmlspecialchars($user_name); ?>!</h2>
                </div>
                <div class="dropdown">
                    <button class="dropdown-toggle" id="userDropdown" onclick="toggleDropdown()">
                        <img src="img/undraw_profile.svg" alt="Profile">
                        <span><?php echo htmlspecialchars($user_name); ?></span>
                        <i class="fas fa-chevron-down fa-sm fa-fw ml-2"></i>
                    </button>
                    <div class="dropdown-menu" id="dropdownMenu">
                        <a class="dropdown-item" href="profile.php">
                            <i class="fas fa-user fa-sm fa-fw mr-2"></i> Profile
                        </a>
                        <a class="dropdown-item" href="settings.php">
                            <i class="fas fa-cogs fa-sm fa-fw mr-2"></i> Settings
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="logout.php">
                            <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="dashboard-cards">
                <div class="dashboard-card card-primary">
                    <div class="card-header">Total Sessions</div>
                    <div class="card-value"><?php echo $total_records; ?></div>
                    <div class="card-icon"><i class="fas fa-calendar"></i></div>
                </div>
                <div class="dashboard-card card-success">
                    <div class="card-header">Upcoming Sessions</div>
                    <div class="card-value"><?php echo $upcoming_count; ?></div>
                    <div class="card-icon"><i class="fas fa-clock"></i></div>
                </div>
                <div class="dashboard-card card-info">
                    <div class="card-header">Delivered Sessions</div>
                    <div class="card-value"><?php echo $delivered_count; ?></div>
                    <div class="card-icon"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
            
            <div class="container">
                <div class="page-header">
                    <h1>Lesson Recordings</h1>
                </div>
                
                <div class="filters-section">
                    <h2>Filter Lessons</h2>
                    <form class="filter-form" method="GET" action="">
                        <div class="filter-group">
                            <label for="search">Search:</label>
                            <input type="text" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($search_query); ?>" 
                                   placeholder="Search notes, dates, names...">
                        </div>
                        <div class="filter-group">
                            <label for="date_filter">Date:</label>
                            <input type="date" id="date_filter" name="date_filter" 
                                   value="<?php echo htmlspecialchars($date_filter); ?>">
                        </div>
                        <div class="filter-group">
                            <label for="status_filter">Status:</label>
                            <select id="status_filter" name="status_filter">
                                <option value="">Any Status</option>
                                <?php foreach($valid_statuses as $status): ?>
                                <option value="<?php echo htmlspecialchars($status); ?>" 
                                        <?php echo ($status_filter == $status) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($status); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="type_filter">Type:</label>
                            <select id="type_filter" name="type_filter">
                                <option value="">Any Type</option>
                                <?php foreach($valid_types as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>" 
                                        <?php echo ($type_filter == $type) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-actions">
                            <button type="submit">Apply Filters</button>
                            <a href="recording.php" class="clear-filters-btn">Clear Filters</a>
                        </div>
                    </form>
                </div>
                
                <?php if ($result->num_rows > 0): ?>
                <div class="recordings-list">
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="recording-card">
                        <div class="recording-header">
                            <div>
                                <h3 class="lesson-title">
                                    Lesson on <?php echo htmlspecialchars(date('F j, Y', strtotime($row['lesson_date']))); ?>
                                </h3>
                                <p class="lesson-time">
                                    <?php echo htmlspecialchars(date('g:i A', strtotime($row['start_time']))); ?> - 
                                    <?php echo htmlspecialchars(date('g:i A', strtotime($row['end_time']))); ?> 
                                    (<?php echo calculateLessonDuration($row['start_time'], $row['end_time']); ?>)
                                </p>
                            </div>
                            <div class="lesson-badges">
                                <span class="lesson-type-badge badge-<?php echo strtolower($row['lesson_type']); ?>">
                                    <?php echo htmlspecialchars($row['lesson_type']); ?>
                                </span>
                                <span class="lesson-status-badge badge-<?php echo strtolower(str_replace(' ', '-', $row['session_status'])); ?>">
                                    <?php echo htmlspecialchars($row['session_status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if (!empty($row['recording_url']) && $row['session_status'] == 'Delivered'): ?>
                        <div class="video-container">
                            <video class="lesson-video" controls>
                                <source src="<?php echo htmlspecialchars($row['recording_url']); ?>" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        </div>
                        <?php else: ?>
                        <div class="no-video-message">
                            <p>
                                <?php if ($row['session_status'] == 'Scheduled'): ?>
                                    <i class="fas fa-calendar-alt fa-3x mb-3"></i><br>
                                    This lesson is scheduled for the future. Recording will be available after the session.
                                <?php elseif ($row['session_status'] == 'No Show'): ?>
                                    <i class="fas fa-user-times fa-3x mb-3"></i><br>
                                    No recording available as this was marked as a no-show.
                                <?php elseif ($row['session_status'] == 'Cancelled'): ?>
                                    <i class="fas fa-ban fa-3x mb-3"></i><br>
                                    This lesson was cancelled. No recording available.
                                <?php else: ?>
                                    <i class="fas fa-video-slash fa-3x mb-3"></i><br>
                                    Recording not available for this lesson.
                                <?php endif; ?>
                            </p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="lesson-details">
                            <div class="participant-info">
                                <div class="student-info">
                                    <h4>Student</h4>
                                    <p><strong>Name:</strong> <?php echo htmlspecialchars($row['student_name']); ?></p>
                                    <?php if (isset($row['student_email'])): ?>
                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($row['student_email']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="tutor-info">
                                    <h4>Tutor</h4>
                                    <?php if ($user_type == 'student' && isset($row['tutor_name'])): ?>
                                    <p><strong>Name:</strong> <?php echo htmlspecialchars($row['tutor_name']); ?></p>
                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($row['tutor_email']); ?></p>
                                    <?php elseif ($user_type == 'tutor'): ?>
                                    <p><strong>ID:</strong> <?php echo htmlspecialchars($row['tutor_id']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="collapsible">
                                <div class="collapsible-header" onclick="toggleCollapsible(this)">
                                    Session Notes
                                    <span class="toggle-icon">+</span>
                                </div>
                                <div class="collapsible-content">
                                    <div>
                                        <?php if (!empty($row['notes'])): ?>
                                        <div class="formatted-notes">
                                            <?php echo nl2br(htmlspecialchars($row['notes'])); ?>
                                        </div>
                                        <?php else: ?>
                                        <p class="no-notes-message">No notes available for this session.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="collapsible">
                                <div class="collapsible-header" onclick="toggleCollapsible(this)">
                                    Homework
                                    <span class="toggle-icon">+</span>
                                </div>
                                <div class="collapsible-content">
                                    <div>
                                        <?php 
                                        $homework_results = getHomeworkForLesson($conn, $row['student_id'], $row['tutor_id'], $row['lesson_date']);
                                        if ($homework_results->num_rows > 0):
                                        ?>
                                            <?php while ($hw = $homework_results->fetch_assoc()): ?>
                                            <div class="homework-item">
                                                <h5><?php echo htmlspecialchars($hw['title']); ?></h5>
                                                <div class="homework-description">
                                                    <?php echo nl2br(htmlspecialchars($hw['description'])); ?>
                                                </div>
                                                <div class="homework-meta">
                                                    <p class="due-date">
                                                        <i class="fas fa-calendar-day"></i> Due: 
                                                        <?php echo date('F j, Y', strtotime($hw['due_date'])); ?>
                                                    </p>
                                                    <p>
                                                        <span class="status <?php echo strtolower($hw['status']); ?>">
                                                            <?php echo htmlspecialchars($hw['status']); ?>
                                                        </span>
                                                    </p>
                                                </div>
                                                <?php if (!empty($hw['file_path'])): ?>
                                                <a href="<?php echo htmlspecialchars($hw['file_path']); ?>" class="download-link" download>
                                                    <i class="fas fa-download"></i> Download Assignment
                                                </a>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($hw['feedback'])): ?>
                                                <div class="feedback-section">
                                                    <h6>Feedback</h6>
                                                    <div class="feedback-content">
                                                        <?php echo nl2br(htmlspecialchars($hw['feedback'])); ?>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <p class="no-homework-message">No homework was assigned for this session.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                    <a href="?page=1<?php echo generateFilterParams(); ?>" class="pagination-item">
                        <i class="fas fa-angle-double-left"></i>
                    </a>
                    <a href="?page=<?php echo $page - 1; ?><?php echo generateFilterParams(); ?>" class="pagination-item">
                        <i class="fas fa-angle-left"></i>
                    </a>
                    <?php else: ?>
                    <span class="pagination-item disabled">
                        <i class="fas fa-angle-double-left"></i>
                    </span>
                    <span class="pagination-item disabled">
                        <i class="fas fa-angle-left"></i>
                    </span>
                    <?php endif; ?>
                    
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                    <a href="?page=<?php echo $i; ?><?php echo generateFilterParams(); ?>" 
                       class="pagination-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo generateFilterParams(); ?>" class="pagination-item">
                        <i class="fas fa-angle-right"></i>
                    </a>
                    <a href="?page=<?php echo $total_pages; ?><?php echo generateFilterParams(); ?>" class="pagination-item">
                        <i class="fas fa-angle-double-right"></i>
                    </a>
                    <?php else: ?>
                    <span class="pagination-item disabled">
                        <i class="fas fa-angle-right"></i>
                    </span>
                    <span class="pagination-item disabled">
                        <i class="fas fa-angle-double-right"></i>
                    </span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php else: ?>
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <p>No lesson recordings found matching your criteria.</p>
                    <a href="recording.php" class="clear-filters-btn">Clear Filters</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Toggle dropdown menu
        function toggleDropdown() {
            document.getElementById("dropdownMenu").classList.toggle("show");
        }
        
        // Close dropdown if clicked outside
        window.onclick = function(event) {
            if (!event.target.matches('.dropdown-toggle') && !event.target.matches('.dropdown-toggle *')) {
                var dropdowns = document.getElementsByClassName("dropdown-menu");
                for (var i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        }
        
        // Toggle collapsible sections
        function toggleCollapsible(element) {
            // Toggle active class
            element.classList.toggle("active");
            
            // Toggle icon
            var icon = element.querySelector(".toggle-icon");
            icon.textContent = icon.textContent === "+" ? "-" : "+";
            
            // Toggle content visibility
            var content = element.nextElementSibling;
            if (content.style.maxHeight) {
                content.style.maxHeight = null;
            } else {
                content.style.maxHeight = content.scrollHeight + "px";
            }
        }
        
        // Automatically open the first collapsible on page load
        document.addEventListener("DOMContentLoaded", function() {
            var firstCollapsible = document.querySelector(".collapsible-header");
            if (firstCollapsible) {
                toggleCollapsible(firstCollapsible);
            }
        });
    </script>
</body>
</html>