<?php
// Start session
session_start();

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    header('Location: student-login.php');
    exit();
}

// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'bloom';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get student information
$student_id = $_SESSION['student_id'];
$stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student_result = $stmt->get_result();
$student = $student_result->fetch_assoc();
$stmt->close();

// Get today's date and week boundaries
$today = new DateTime();
$week_start = clone $today;
$week_start->modify('this week monday');
$week_end = clone $week_start;
$week_end->modify('+6 days');

// For calendar navigation
if (isset($_GET['week'])) {
    $offset = intval($_GET['week']);
    $week_start->modify($offset . ' weeks');
    $week_end = clone $week_start;
    $week_end->modify('+6 days');
}

// Format dates for SQL query
$week_start_str = $week_start->format('Y-m-d');
$week_end_str = $week_end->format('Y-m-d');

// Get lessons for the week
$stmt = $conn->prepare("
    SELECT l.*, t.name AS tutor_name, t.email AS tutor_email 
    FROM lessons l
    JOIN tutors t ON l.tutor_id = t.id
    WHERE l.student_name = ? 
    AND l.lesson_date BETWEEN ? AND ?
    AND l.session_status IN ('Scheduled', 'Rescheduled')
    ORDER BY l.lesson_date ASC, l.start_time ASC
");
$stmt->bind_param("sss", $student['name'], $week_start_str, $week_end_str);
$stmt->execute();
$week_lessons = $stmt->get_result();
$stmt->close();

// Get upcoming lessons (limited to 5)
$stmt = $conn->prepare("
    SELECT l.*, t.name AS tutor_name, t.email AS tutor_email 
    FROM lessons l
    JOIN tutors t ON l.tutor_id = t.id
    WHERE l.student_name = ? 
    AND l.lesson_date >= CURDATE()
    AND l.session_status IN ('Scheduled', 'Rescheduled')
    ORDER BY l.lesson_date ASC, l.start_time ASC
    LIMIT 5
");
$stmt->bind_param("s", $student['name']);
$stmt->execute();
$upcoming_lessons = $stmt->get_result();
$stmt->close();

// Get homework assignments
// Get homework assignments
$stmt = $conn->prepare("
    SELECT h.*
    FROM homework h
    WHERE h.student_id = ?
    ORDER BY h.due_date ASC 
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$homework_result = $stmt->get_result();
$stmt->close();

// Process any form submissions
// Modify the file upload section in the POST handling
if (isset($_POST['submit_homework'])) {
    $homework_id = $_POST['homework_id'];
    
    // File upload handling
    if (isset($_FILES['homework_file']) && $_FILES['homework_file']['error'] == 0) {
        $upload_dir = 'uploads/student_homework/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Create a unique filename
        $file_name = $student_id . '_' . $homework_id . '_' . time() . '_' . $_FILES['homework_file']['name'];
        $file_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['homework_file']['tmp_name'], $file_path)) {
            // Update homework status in database
            $update_stmt = $conn->prepare("
                UPDATE homework 
                SET 
                    status = 'Submitted', 
                    submitted_file_path = ?, 
                    submission_date = NOW()
                WHERE homework_id = ?
            ");
            $update_stmt->bind_param("si", $file_path, $homework_id);
            $update_stmt->execute();
            $update_stmt->close();
            
            // Redirect to refresh the page
            header('Location: student-dashboard.php?success=1');
            exit();
        } else {
            $error_message = "Failed to upload file";
        }
    }
}

// Helper function to generate Google Meet link
function generateMeetLink($lesson_id, $student_name) {
    // In a real application, you might integrate with Google Meet API
    // For now, we'll create a simple meet link with a unique ID
    
    return "https://meet.google.com/qbn-zfsj-zxa/" ;
}

// Check if a lesson is about to start (within 15 minutes)
function isLessonStartingSoon($lesson_date, $start_time) {
    $lesson_datetime = new DateTime($lesson_date . ' ' . $start_time);
    $now = new DateTime();
    $interval = $now->diff($lesson_datetime);
    
    // If lesson is today and starting within 15 minutes
    if ($interval->days == 0 && $interval->h == 0 && $interval->i <= 15 && $lesson_datetime > $now) {
        return true;
    }
    return false;
}

// Format time for display
function formatTime($time) {
    return date("g:i A", strtotime($time));
}

// Helper function to organize lessons by day for the calendar
function organizeLessonsByDay($lessons) {
    $days = [];
    
    // Initialize all days of the week
    for ($i = 0; $i < 7; $i++) {
        $days[$i] = [];
    }
    
    // Add lessons to their respective days
    while ($lesson = $lessons->fetch_assoc()) {
        $lesson_date = new DateTime($lesson['lesson_date']);
        $day_of_week = (int)$lesson_date->format('w'); // 0 (Sunday) to 6 (Saturday)
        
        $days[$day_of_week][] = $lesson;
    }
    
    return $days;
}

// Organize weekly lessons by day
$lessons_by_day = organizeLessonsByDay($week_lessons);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Learning Journey</title>
    <link rel="stylesheet" href="student.css">
</head>
<body>
    <header>
        <h1>My Learning Dashboard</h1>
        <div class="user-info">
            <p>Welcome, <?php echo htmlspecialchars($student['name']); ?>!</p>
            <nav>
                <ul>
                    <li><a href="#calendar">Weekly Calendar</a></li>
                    <li><a href="#upcoming">Upcoming Lessons</a></li>
                    <li><a href="#homework">Homework</a></li>
                    <li><a href="profile.php">My Profile</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">
                <p>Homework submitted successfully!</p>
            </div>
        <?php endif; ?>

        <section id="calendar" class="dashboard-card">
            <h2>My Weekly Schedule</h2>
            
            <div class="calendar-nav">
                <?php 
                $prev_week = isset($_GET['week']) ? intval($_GET['week']) - 1 : -1;
                $next_week = isset($_GET['week']) ? intval($_GET['week']) + 1 : 1;
                $current_week = isset($_GET['week']) ? intval($_GET['week']) : 0;
                ?>
                <a href="?week=<?php echo $prev_week; ?>" class="calendar-nav-btn">&laquo; Previous Week</a>
                <h3>
                    <?php echo $week_start->format('M d') . ' - ' . $week_end->format('M d, Y'); ?>
                    <?php if ($current_week == 0): ?>
                        <span class="current-week-badge">Current Week</span>
                    <?php endif; ?>
                </h3>
                <a href="?week=<?php echo $next_week; ?>" class="calendar-nav-btn">Next Week &raquo;</a>
            </div>
            
            <div class="weekly-calendar">
                <?php
                $days_of_week = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                $current_date = new DateTime();
                
                foreach ($days_of_week as $index => $day_name):
                    $day_date = clone $week_start;
                    $day_date->modify('+' . ($index == 0 ? 6 : $index - 1) . ' days'); // Adjust for Monday start
                    $is_today = $day_date->format('Y-m-d') === $current_date->format('Y-m-d');
                ?>
                    <div class="calendar-day <?php echo $is_today ? 'today' : ''; ?>">
                        <div class="day-header">
                            <span class="day-name"><?php echo $day_name; ?></span>
                            <span class="day-date"><?php echo $day_date->format('M d'); ?></span>
                        </div>
                        <div class="day-content">
                            <?php 
                            // Day index in PHP's date format (0 = Sunday, 6 = Saturday)
                            $php_day_index = $index;
                            
                            if (empty($lessons_by_day[$php_day_index])): 
                            ?>
                                <div class="no-lessons">No lessons</div>
                            <?php else: ?>
                                <?php foreach ($lessons_by_day[$php_day_index] as $lesson): ?>
                                    <?php $meet_link = generateMeetLink($lesson['id'], $lesson['student_name']); ?>
                                    <div class="calendar-lesson">
                                        <div class="lesson-time">
                                            <?php echo formatTime($lesson['start_time']); ?> - <?php echo formatTime($lesson['end_time']); ?>
                                        </div>
                                        <div class="lesson-info">
                                            <span class="lesson-type"><?php echo htmlspecialchars($lesson['lesson_type']); ?></span>
                                            <span class="tutor-name">with <?php echo htmlspecialchars($lesson['tutor_name']); ?></span>
                                        </div>
                                        <div class="lesson-action">
                                            <a href="<?php echo $meet_link; ?>" target="_blank" class="calendar-join-btn">
                                                Join
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="today" class="dashboard-card">
            <h2>Today's Learning</h2>
            <?php
            $has_lesson_today = false;
            $upcoming_lessons->data_seek(0);
            while ($lesson = $upcoming_lessons->fetch_assoc()) {
                if ($lesson['lesson_date'] == date('Y-m-d')) {
                    $has_lesson_today = true;
                    $meet_link = generateMeetLink($lesson['id'], $lesson['student_name']);
                    $is_starting_soon = isLessonStartingSoon($lesson['lesson_date'], $lesson['start_time']);
                    ?>
                    <div class="lesson-card <?php echo $is_starting_soon ? 'starting-soon' : ''; ?>">
                        <div class="lesson-time">
                            <span><?php echo formatTime($lesson['start_time']); ?> - <?php echo formatTime($lesson['end_time']); ?></span>
                            <?php if ($is_starting_soon): ?>
                                <span class="starting-soon-badge">Starting Soon!</span>
                            <?php endif; ?>
                        </div>
                        <div class="lesson-details">
                            <h3><?php echo htmlspecialchars($lesson['lesson_type']); ?> Lesson</h3>
                            <p>With: <?php echo htmlspecialchars($lesson['tutor_name']); ?></p>
                            <?php if ($lesson['notes']): ?>
                                <p class="lesson-notes"><?php echo htmlspecialchars($lesson['notes']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="lesson-actions">
                            <a href="<?php echo $meet_link; ?>" target="_blank" class="btn join-btn">
                                Join Google Meet
                            </a>
                        </div>
                    </div>
                    <?php
                }
            }
            if (!$has_lesson_today) {
                echo "<p>No lessons scheduled for today.</p>";
            }
            ?>
        </section>

        <section id="upcoming" class="dashboard-card">
            <h2>Upcoming Lessons</h2>
            <?php
            $upcoming_lessons->data_seek(0);
            $has_upcoming = false;
            while ($lesson = $upcoming_lessons->fetch_assoc()) {
                if ($lesson['lesson_date'] > date('Y-m-d')) {
                    $has_upcoming = true;
                    $lesson_date = new DateTime($lesson['lesson_date']);
                    ?>
                    <div class="lesson-card">
                        <div class="lesson-date">
                            <span class="day"><?php echo $lesson_date->format('d'); ?></span>
                            <span class="month"><?php echo $lesson_date->format('M'); ?></span>
                        </div>
                        <div class="lesson-time">
                            <span><?php echo formatTime($lesson['start_time']); ?> - <?php echo formatTime($lesson['end_time']); ?></span>
                        </div>
                        <div class="lesson-details">
                            <h3><?php echo htmlspecialchars($lesson['lesson_type']); ?> Lesson</h3>
                            <p>With: <?php echo htmlspecialchars($lesson['tutor_name']); ?></p>
                            <?php if ($lesson['notes']): ?>
                                <p class="lesson-notes"><?php echo htmlspecialchars($lesson['notes']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="lesson-actions">
                            <button class="btn reminder-btn" data-lesson-id="<?php echo $lesson['id']; ?>">
                                Set Reminder
                            </button>
                        </div>
                    </div>
                    <?php
                }
            }
            if (!$has_upcoming) {
                echo "<p>No upcoming lessons scheduled.</p>";
            }
            ?>
            <div class="view-more">
                <a href="all_lessons.php">View All Lessons</a>
            </div>
        </section>

        
<section id="homework" class="dashboard-card">
    <h2>Homework & Assignments</h2>
    <div class="homework-tabs">
        <button class="tab-btn active" data-tab="pending">Pending</button>
        <button class="tab-btn" data-tab="completed">Completed</button>
    </div>
    
    <div id="pending-homework" class="homework-tab-content active">
        <?php
        $homework_result->data_seek(0);
        $pending_count = 0;
        while ($homework = $homework_result->fetch_assoc()) {
            if ($homework['status'] == 'Assigned') {
                $pending_count++;
                $due_date = new DateTime($homework['due_date']);
                $today = new DateTime();
                $days_left = $today->diff($due_date)->days;
                $is_overdue = $due_date < $today;
                ?>
                <div class="homework-card <?php echo $is_overdue ? 'overdue' : ''; ?>">
                    <div class="homework-status">
                        <?php if ($is_overdue): ?>
                            <span class="status-badge overdue">Overdue</span>
                        <?php else: ?>
                            <span class="status-badge pending">Due in <?php echo $days_left; ?> days</span>
                        <?php endif; ?>
                    </div>
                    <div class="homework-details">
                        <h3><?php echo htmlspecialchars($homework['title']); ?></h3>
                        <p class="due-date">Due: <?php echo $due_date->format('M d, Y'); ?></p>
                        <?php if ($homework['description']): ?>
                            <p class="description"><?php echo htmlspecialchars($homework['description']); ?></p>
                        <?php endif; ?>
                        
                        <!-- New Download Button for Homework File -->
                        <?php if ($homework['file_path'] && file_exists($homework['file_path'])): ?>
                            <div class="homework-file">
                                <a href="download.php?file=<?php echo urlencode($homework['file_path']); ?>" class="btn download-btn">
                                    Download Homework File
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="homework-actions">
                        <form method="post" action="student-dashboard.php" enctype="multipart/form-data">
                            <input type="hidden" name="homework_id" value="<?php echo $homework['homework_id']; ?>">
                            <div class="file-upload">
                                <input type="file" name="homework_file" id="file_<?php echo $homework['homework_id']; ?>" required>
                                <label for="file_<?php echo $homework['homework_id']; ?>" class="file-label">Choose File</label>
                            </div>
                            <button type="submit" name="submit_homework" class="btn submit-btn">Submit</button>
                        </form>
                    </div>
                </div>
                <?php
            }
        }
        if ($pending_count == 0) {
            echo "<p>No pending homework assignments. Good job!</p>";
        }
        ?>
    </div>
    
    <!-- Completed Homework Section remains the same -->
    <div id="completed-homework" class="homework-tab-content">
        <?php
        $homework_result->data_seek(0);
        $completed_count = 0;
        while ($homework = $homework_result->fetch_assoc()) {
            if ($homework['status'] == 'Submitted' || $homework['status'] == 'Graded') {
                $completed_count++;
                ?>
                <div class="homework-card">
                    <div class="homework-status">
                        <span class="status-badge <?php echo strtolower($homework['status']); ?>">
                            <?php echo $homework['status']; ?>
                        </span>
                    </div>
                    <div class="homework-details">
                        <h3><?php echo htmlspecialchars($homework['title']); ?></h3>
                        <?php if ($homework['grade']): ?>
                            <p class="grade">Grade: <?php echo htmlspecialchars($homework['grade']); ?></p>
                        <?php endif; ?>
                        <?php if ($homework['feedback']): ?>
                            <div class="feedback">
                                <h4>Feedback:</h4>
                                <p><?php echo htmlspecialchars($homework['feedback']); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
            }
        }
        if ($completed_count == 0) {
            echo "<p>No completed homework assignments yet.</p>";
        }
        ?>
    </div>
</section>


<section id="progress" class="dashboard-card">
    <h2>My Learning Progress</h2>
    <div class="progress-summary">
        <?php
        // Database connection
        require_once 'db_connect.php'; // Assume this file handles your database connection

        // Get the current student ID (assuming you have a session with student_id)
        $student_id = $_SESSION['student_id'];
        
        // 1. Count completed lessons
        $query_lessons = "SELECT COUNT(*) as completed_lessons 
                         FROM lessons 
                         WHERE student_name = (SELECT name FROM students WHERE student_id = ?) 
                         AND session_status = 'Delivered'";
        $stmt = $conn->prepare($query_lessons);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result_lessons = $stmt->get_result();
        $lessons_data = $result_lessons->fetch_assoc();
        $completed_lessons = $lessons_data['completed_lessons'];
        
        // 2. Calculate homework completion rate
        $query_homework = "SELECT 
                            COUNT(*) as total_homework,
                            SUM(CASE WHEN status IN ('Submitted', 'Graded') THEN 1 ELSE 0 END) as completed_homework
                          FROM homework
                          WHERE student_id = ?";
        $stmt = $conn->prepare($query_homework);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result_homework = $stmt->get_result();
        $homework_data = $result_homework->fetch_assoc();
        
        $completion_rate = 0;
        if ($homework_data['total_homework'] > 0) {
            $completion_rate = round(($homework_data['completed_homework'] / $homework_data['total_homework']) * 100);
        }
        
        // 3. Calculate average grade
        $query_grades = "SELECT AVG(
                            CASE 
                                WHEN grade = 'A+' THEN 4.3
                                WHEN grade = 'A' THEN 4.0
                                WHEN grade = 'A-' THEN 3.7
                                WHEN grade = 'B+' THEN 3.3
                                WHEN grade = 'B' THEN 3.0
                                WHEN grade = 'B-' THEN 2.7
                                WHEN grade = 'C+' THEN 2.3
                                WHEN grade = 'C' THEN 2.0
                                WHEN grade = 'C-' THEN 1.7
                                WHEN grade = 'D+' THEN 1.3
                                WHEN grade = 'D' THEN 1.0
                                WHEN grade = 'F' THEN 0.0
                                ELSE NULL
                            END) as avg_grade_point
                        FROM homework
                        WHERE student_id = ? AND grade IS NOT NULL";
        $stmt = $conn->prepare($query_grades);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result_grades = $stmt->get_result();
        $grade_data = $result_grades->fetch_assoc();
        
        // Convert GPA back to letter grade
        $avg_gpa = $grade_data['avg_grade_point'];
        $letter_grade = 'N/A';
        
        if ($avg_gpa !== null) {
            if ($avg_gpa >= 4.3) $letter_grade = 'A+';
            else if ($avg_gpa >= 4.0) $letter_grade = 'A';
            else if ($avg_gpa >= 3.7) $letter_grade = 'A-';
            else if ($avg_gpa >= 3.3) $letter_grade = 'B+';
            else if ($avg_gpa >= 3.0) $letter_grade = 'B';
            else if ($avg_gpa >= 2.7) $letter_grade = 'B-';
            else if ($avg_gpa >= 2.3) $letter_grade = 'C+';
            else if ($avg_gpa >= 2.0) $letter_grade = 'C';
            else if ($avg_gpa >= 1.7) $letter_grade = 'C-';
            else if ($avg_gpa >= 1.3) $letter_grade = 'D+';
            else if ($avg_gpa >= 1.0) $letter_grade = 'D';
            else $letter_grade = 'F';
        }
        ?>
        
        <div class="progress-item">
            <span class="progress-label">Lessons Completed</span>
            <div class="progress-value">
                <?php echo $completed_lessons; ?>
            </div>
        </div>
        <div class="progress-item">
            <span class="progress-label">Homework Completion Rate</span>
            <div class="progress-value">
                <?php echo $completion_rate . '%'; ?>
            </div>
        </div>
        <div class="progress-item">
            <span class="progress-label">Average Grade</span>
            <div class="progress-value">
                <?php echo $letter_grade; ?>
            </div>
        </div>
    </div>
    <div class="progress-cta">
        <a href="learning_progress.php" class="btn">View Detailed Progress</a>
    </div>
</section>
<section id="progress" class="dashboard-card">
    <h2>My Learning Topics</h2>
    <style>/* Base styles and variables - maintaining same design system */
:root {
  --background: #f8fafc;
  --foreground: #0f172a;
  --card: #ffffff;
  --card-foreground: #0f172a;
  --border: #e2e8f0;
  --input: #e2e8f0;
  --primary: #8b5cf6;
  --primary-foreground: #ffffff;
  --secondary: #f1f5f9;
  --secondary-foreground: #1e293b;
  --accent: #f1f5f9;
  --accent-foreground: #1e293b;
  --destructive: #ef4444;
  --destructive-foreground: #ffffff;
  --ring: #8b5cf6;
  --radius: 0.5rem;
  --success: #10b981;
  --info: #3b82f6;
  --warning: #f59e0b;
  --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
  --muted: #64748b;
}

/* Dashboard Card */
.dashboard-card {
  background-color: var(--card);
  border-radius: var(--radius);
  padding: 1.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  margin-bottom: 1.5rem;
  overflow: hidden;
  transition: all 0.2s ease;
}

.dashboard-card:hover {
  box-shadow: 0 10px 15px rgba(0, 0, 0, 0.05);
}

.dashboard-card h2 {
  font-size: 1.25rem;
  font-weight: 600;
  margin-bottom: 1.5rem;
  color: var(--foreground);
  letter-spacing: -0.025em;
  display: flex;
  align-items: center;
}

.dashboard-card h2::before {
 
  margin-right: 0.75rem;
  font-size: 1.5rem;
}

.dashboard-card h3 {
  font-size: 1.125rem;
  font-weight: 600;
  margin-bottom: 1rem;
  color: var(--foreground);
  letter-spacing: -0.025em;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid var(--border);
}

/* Subject Topics Styling */
.subject-topics {
  animation: fadeIn 0.3s ease-out;
  padding: 0.5rem 0;
  margin-bottom: 1rem;
}

/* Topics List */
.topics-list {
  margin-bottom: 1.5rem;
}

.topic-item {
  display: flex;
  align-items: center;
  padding: 0.75rem 1rem;
  border-radius: var(--radius);
  margin-bottom: 0.5rem;
  background-color: var(--secondary);
  transition: all 0.2s ease;
}

.topic-item:hover {
  transform: translateX(5px);
}

.topic-item.completed {
  background-color: rgba(16, 185, 129, 0.1);
  border-left: 3px solid var(--success);
}

.topic-item.pending {
  background-color: var(--secondary);
  border-left: 3px solid var(--muted);
}

.topic-status {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  margin-right: 0.75rem;
  font-size: 0.875rem;
}

.topic-item.completed .topic-status {
  color: var(--success);
  font-weight: bold;
}

.topic-item.pending .topic-status {
  color: var(--muted);
}

.topic-name {
  flex: 1;
  font-size: 0.875rem;
  font-weight: 500;
}

.completion-date {
  font-size: 0.75rem;
  color: var(--muted);
  margin-left: auto;
}

/* Progress Bar */
.topic-progress-bar {
  height: 8px;
  background-color: var(--secondary);
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 0.5rem;
}

.progress-fill {
  height: 100%;
  background-color: var(--primary);
  border-radius: 4px;
  transition: width 0.5s ease-out;
}

.topic-progress-stats {
  display: flex;
  justify-content: space-between;
  font-size: 0.75rem;
  color: var(--muted);
  margin-bottom: 1rem;
}

/* Subject Navigation */
.subject-navigation {
  display: flex;
  justify-content: space-between;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid var(--border);
}

.prev-subject, .next-subject {
  background-color: var(--secondary);
  color: var(--secondary-foreground);
  border: none;
  padding: 0.5rem 1rem;
  border-radius: var(--radius);
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.prev-subject:hover, .next-subject:hover {
  background-color: var(--primary);
  color: var(--primary-foreground);
}

.next-subject {
  margin-left: auto;
}

/* Call to Action */
.topics-cta {
  margin-top: 1.5rem;
  text-align: center;
}

.topics-cta .btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background-color: var(--primary);
  color: var(--primary-foreground);
  padding: 0.5rem 1.5rem;
  border-radius: var(--radius);
  font-weight: 500;
  text-decoration: none;
  transition: all 0.2s ease;
}

.topics-cta .btn:hover {
  background-color: #7c3aed;
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.topics-cta .btn::after {
  content: ' →';
  margin-left: 0.25rem;
  transition: margin-left 0.2s ease;
}

.topics-cta .btn:hover::after {
  margin-left: 0.5rem;
}

/* Empty State */
.subject-topics p {
  color: var(--muted);
  text-align: center;
  padding: 2rem 0;
}

/* Animations */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes slideIn {
  from { transform: translateX(20px); opacity: 0; }
  to { transform: translateX(0); opacity: 1; }
}

/* Responsive Design */
@media (max-width: 768px) {
  .dashboard-card {
    padding: 1rem;
  }
  
  .subject-navigation {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .prev-subject, .next-subject {
    width: 100%;
  }
  
  .next-subject {
    margin-left: 0;
  }
  
  .topic-item {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .topic-status {
    margin-bottom: 0.5rem;
  }
  
  .completion-date {
    margin-left: 0;
    margin-top: 0.5rem;
  }
}

/* Additional Enhancements */
/* Subject indicator dots for carousel */
.subject-indicators {
  display: flex;
  justify-content: center;
  margin: 1rem 0;
}

.subject-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background-color: var(--border);
  margin: 0 4px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.subject-dot.active {
  background-color: var(--primary);
  transform: scale(1.2);
}

/* Topic item hover effect */
.topic-item::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  width: 0;
  height: 100%;
  background-color: rgba(139, 92, 246, 0.05);
  transition: width 0.2s ease;
  z-index: -1;
}

.topic-item:hover::before {
  width: 100%;
}</style>
    <?php
    // Database connection
    require_once 'db_connect.php';
    
    // Get the current student ID
    $student_id = $_SESSION['student_id'];
    
    // Get the student's enrolled subjects with year levels
    $query_subjects = "SELECT ss.subject_id, s.subject_name, ss.year_level 
                      FROM student_subjects ss
                      JOIN subjects s ON ss.subject_id = s.subject_id
                      WHERE ss.student_id = ?
                      ORDER BY s.subject_name";
    $stmt = $conn->prepare($query_subjects);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result_subjects = $stmt->get_result();
    
    if ($result_subjects->num_rows > 0) {
        // Initialize subject counter for carousel
        $subject_count = 0;
        
        while ($subject = $result_subjects->fetch_assoc()) {
            $subject_id = $subject['subject_id'];
            $subject_name = $subject['subject_name'];
            $year_level = $subject['year_level'];
            
            // Only display the first subject by default, hide others
            $display_style = ($subject_count == 0) ? 'block' : 'none';
            
            echo "<div class='subject-topics' id='subject-{$subject_id}' style='display: {$display_style};'>";
            echo "<h3>{$subject_name} - {$year_level}</h3>";
            
            // Get topics for this subject and year level
            $query_topics = "SELECT t.topic_id, t.topic_name, t.topic_description, 
                                  IFNULL(stp.is_completed, 0) as is_completed,
                                  stp.completed_date
                              FROM topics t
                              LEFT JOIN student_topic_progress stp ON t.topic_id = stp.topic_id AND stp.student_id = ?
                              WHERE t.subject_id = ? AND t.year_level = ?
                              ORDER BY t.order_number
                              LIMIT 5";  // Only get 5 for dashboard
                              
            $stmt = $conn->prepare($query_topics);
            $stmt->bind_param("iis", $student_id, $subject_id, $year_level);
            $stmt->execute();
            $result_topics = $stmt->get_result();
            
            if ($result_topics->num_rows > 0) {
                echo "<div class='topics-list'>";
                while ($topic = $result_topics->fetch_assoc()) {
                    $status_class = $topic['is_completed'] ? 'completed' : 'pending';
                    $status_icon = $topic['is_completed'] ? 'Done' : '○';
                    $completed_date = $topic['completed_date'] ? date('M d, Y', strtotime($topic['completed_date'])) : '';
                    
                    echo "<div class='topic-item {$status_class}'>";
                    echo "<span class='topic-status'>{$status_icon}</span>";
                    echo "<span class='topic-name'>{$topic['topic_name']}</span>";
                    if ($topic['is_completed']) {
                        echo "<span class='completion-date'>{$completed_date}</span>";
                    }
                    echo "</div>";
                }
                echo "</div>";
                
                // Get total topics and completed topics count
                $query_progress = "SELECT 
                                      COUNT(t.topic_id) as total_topics,
                                      SUM(CASE WHEN stp.is_completed = 1 THEN 1 ELSE 0 END) as completed_topics
                                  FROM topics t
                                  LEFT JOIN student_topic_progress stp ON t.topic_id = stp.topic_id AND stp.student_id = ?
                                  WHERE t.subject_id = ? AND t.year_level = ?";
                                  
                $stmt = $conn->prepare($query_progress);
                $stmt->bind_param("iis", $student_id, $subject_id, $year_level);
                $stmt->execute();
                $progress = $stmt->get_result()->fetch_assoc();
                
                $total_topics = $progress['total_topics'];
                $completed_topics = $progress['completed_topics'] ?: 0;
                $completion_percentage = ($total_topics > 0) ? round(($completed_topics / $total_topics) * 100) : 0;
                
                echo "<div class='topic-progress-bar'>";
                echo "<div class='progress-fill' style='width: {$completion_percentage}%'></div>";
                echo "</div>";
                echo "<div class='topic-progress-stats'>";
                echo "<span>{$completed_topics} of {$total_topics} topics completed ({$completion_percentage}%)</span>";
                echo "</div>";
            } else {
                echo "<p>No topics found for this subject.</p>";
            }
            
            echo "</div>"; // End subject-topics div
            $subject_count++;
        }
        
        // Add navigation buttons if there are multiple subjects
        if ($subject_count > 1) {
            echo "<div class='subject-navigation'>";
            echo "<button class='prev-subject' onclick='changeSubject(-1)'>Previous Subject</button>";
            echo "<button class='next-subject' onclick='changeSubject(1)'>Next Subject</button>";
            echo "</div>";
            
            // Add JavaScript for subject navigation
            echo "<script>
                let currentSubjectIndex = 0;
                const totalSubjects = {$subject_count};
                const subjectElements = document.querySelectorAll('.subject-topics');
                
                function changeSubject(direction) {
                    // Hide current subject
                    subjectElements[currentSubjectIndex].style.display = 'none';
                    
                    // Calculate new index
                    currentSubjectIndex = (currentSubjectIndex + direction + totalSubjects) % totalSubjects;
                    
                    // Show new subject
                    subjectElements[currentSubjectIndex].style.display = 'block';
                }
            </script>";
        }
    } else {
        echo "<p>You are not enrolled in any subjects yet.</p>";
    }
    ?>
    <div class="topics-cta">
        <a href="topics_progress.php" class="btn">View All Topics</a>
    </div>
</section>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Tutor System. All rights reserved.</p>
    </footer>

    <script>
        // Basic JavaScript for tab switching
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.homework-tab-content');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons and contents
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));
                    
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    // Show corresponding content
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(tabId + '-homework').classList.add('active');
                });
            });
        });
    </script>