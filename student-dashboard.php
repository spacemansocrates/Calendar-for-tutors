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
$stmt = $conn->prepare("
    SELECT h.*, l.lesson_date, l.student_name 
    FROM homework h
    JOIN lessons l ON h.lesson_id = l.id
    WHERE l.student_name = ?
    ORDER BY h.due_date ASC
");
$stmt->bind_param("s", $student['name']);
$stmt->execute();
$homework_result = $stmt->get_result();
$stmt->close();

// Process any form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle homework submission
    if (isset($_POST['submit_homework'])) {
        $homework_id = $_POST['homework_id'];
        
        // File upload handling
        if (isset($_FILES['homework_file']) && $_FILES['homework_file']['error'] == 0) {
            $upload_dir = 'uploads/homework/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = time() . '_' . $_FILES['homework_file']['name'];
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['homework_file']['tmp_name'], $file_path)) {
                // Update homework status in database
                $update_stmt = $conn->prepare("
                    UPDATE homework 
                    SET status = 'Submitted', submission_path = ? 
                    WHERE homework_id = ?
                ");
                $update_stmt->bind_param("si", $file_path, $homework_id);
                $update_stmt->execute();
                $update_stmt->close();
                
                // Redirect to refresh the page
                header('Location: dashboard.php?success=1');
                exit();
            } else {
                $error_message = "Failed to upload file";
            }
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
                            </div>
                            <div class="homework-actions">
                                <form method="post" action="dashboard.php" enctype="multipart/form-data">
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
                <div class="progress-item">
                    <span class="progress-label">Lessons Completed</span>
                    <div class="progress-value">
                        <?php
                        // In a real application, you would calculate these values from the database
                        echo "12"; // Placeholder value
                        ?>
                    </div>
                </div>
                <div class="progress-item">
                    <span class="progress-label">Homework Completion Rate</span>
                    <div class="progress-value">
                        <?php
                        echo "85%"; // Placeholder value
                        ?>
                    </div>
                </div>
                <div class="progress-item">
                    <span class="progress-label">Average Grade</span>
                    <div class="progress-value">
                        <?php
                        echo "A-"; // Placeholder value
                        ?>
                    </div>
                </div>
            </div>
            <div class="progress-cta">
                <a href="learning_progress.php" class="btn">View Detailed Progress</a>
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