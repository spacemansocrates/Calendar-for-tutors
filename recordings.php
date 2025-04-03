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

// Rest of the code remains the same...
// Prepare filters
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : '';
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';
$type_filter = isset($_GET['type_filter']) ? $_GET['type_filter'] : '';

// Build query based on user type
if ($user_type == 'tutor') {
    $base_query = "SELECT l.*, 
                   s.name AS student_full_name, 
                   s.email AS student_email 
                   FROM lessons l 
                   LEFT JOIN students s ON l.student_name = s.name 
                   WHERE l.tutor_id = ?";
} else {
    // Assuming student
    $student_query = "SELECT name FROM students WHERE student_id = ?";
    $stmt = $conn->prepare($student_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $student_name = $row['name'];
        $base_query = "SELECT l.*, 
                      t.name AS tutor_name, 
                      t.email AS tutor_email 
                      FROM lessons l 
                      LEFT JOIN tutors t ON l.tutor_id = t.id 
                      WHERE l.student_name = ?";
    } else {
        die("Error: Student not found");
    }
}

// Add filters to query if selected
$params = [];
$types = "";

if ($user_type == 'tutor') {
    $params[] = $user_id;
    $types .= "i";
} else {
    $params[] = $student_name;
    $types .= "s";
}

if (!empty($date_filter)) {
    $base_query .= " AND l.lesson_date = ?";
    $params[] = $date_filter;
    $types .= "s";
}

if (!empty($status_filter)) {
    $base_query .= " AND l.session_status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($type_filter)) {
    $base_query .= " AND l.lesson_type = ?";
    $params[] = $type_filter;
    $types .= "s";
}

// Add order by
$base_query .= " ORDER BY l.lesson_date DESC, l.start_time DESC";

// Prepare and execute the query
$stmt = $conn->prepare($base_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lesson Recordings</title>
    <link rel="stylesheet" href="recording.css">
</head>
<body>
    <div class="container" id="recordings-container">
        <header class="page-header">
            <h1>Lesson Recordings</h1>
        </header>
        
        <section class="filters-section" id="lessons-filters">
            <h2>Filter Recordings</h2>
            <form action="" method="GET" class="filter-form">
                <div class="filter-group">
                    <label for="date_filter">Date:</label>
                    <input type="date" id="date_filter" name="date_filter" value="<?php echo $date_filter; ?>">
                </div>
                
                <div class="filter-group">
                    <label for="status_filter">Status:</label>
                    <select id="status_filter" name="status_filter">
                        <option value="">All Statuses</option>
                        <option value="Scheduled" <?php if($status_filter == 'Scheduled') echo 'selected'; ?>>Scheduled</option>
                        <option value="Delivered" <?php if($status_filter == 'Delivered') echo 'selected'; ?>>Delivered</option>
                        <option value="No Show" <?php if($status_filter == 'No Show') echo 'selected'; ?>>No Show</option>
                        <option value="Cancelled" <?php if($status_filter == 'Cancelled') echo 'selected'; ?>>Cancelled</option>
                        <option value="Rescheduled" <?php if($status_filter == 'Rescheduled') echo 'selected'; ?>>Rescheduled</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="type_filter">Lesson Type:</label>
                    <select id="type_filter" name="type_filter">
                        <option value="">All Types</option>
                        <option value="Regular" <?php if($type_filter == 'Regular') echo 'selected'; ?>>Regular</option>
                        <option value="Demo" <?php if($type_filter == 'Demo') echo 'selected'; ?>>Demo</option>
                        <option value="Catchup" <?php if($type_filter == 'Catchup') echo 'selected'; ?>>Catchup</option>
                    </select>
                </div>
                
                <button type="submit" class="filter-submit-btn">Apply Filters</button>
                <a href="lesson-recordings.php" class="clear-filters-btn">Clear Filters</a>
            </form>
        </section>
        
        <section class="recordings-list" id="recordings-list">
            <?php if ($result->num_rows > 0): ?>
                <?php while($lesson = $result->fetch_assoc()): 
                   // Get associated homework for this lesson
$homework_query = "SELECT * FROM homework 
WHERE student_id = ? AND tutor_id = ? 
AND DATE(created_at) = ?";

// Initialize homework_result as null
$homework_result = null;

if ($user_type == 'tutor') {
$student_id_query = "SELECT student_id FROM students WHERE name = ?";
$student_stmt = $conn->prepare($student_id_query);
$student_stmt->bind_param("s", $lesson['student_name']);
$student_stmt->execute();
$student_result = $student_stmt->get_result();

if ($student_row = $student_result->fetch_assoc()) {
$student_id = $student_row['student_id'];
$lesson_date = $lesson['lesson_date'];

// Only execute if we have all parameters
if ($student_id && $lesson['tutor_id'] && $lesson_date) {
$homework_stmt = $conn->prepare($homework_query);
$homework_stmt->bind_param("iis", $student_id, $lesson['tutor_id'], $lesson_date);
$homework_stmt->execute();
$homework_result = $homework_stmt->get_result();
}
}
} else {
// Student user - using the current user_id
$lesson_date = $lesson['lesson_date'];

// Only execute if we have all parameters
if ($user_id && $lesson['tutor_id'] && $lesson_date) {
$homework_stmt = $conn->prepare($homework_query);
$homework_stmt->bind_param("iis", $user_id, $lesson['tutor_id'], $lesson_date);
$homework_stmt->execute();
$homework_result = $homework_stmt->get_result();
}
}
                ?>
                    <div class="recording-card" id="lesson-<?php echo $lesson['id']; ?>">
                        <div class="recording-header">
                            <h3 class="lesson-title">
                                Lesson on <?php echo date('F j, Y', strtotime($lesson['lesson_date'])); ?>
                                <span class="lesson-time">
                                    (<?php echo date('g:i A', strtotime($lesson['start_time'])); ?> - 
                                    <?php echo date('g:i A', strtotime($lesson['end_time'])); ?>)
                                </span>
                            </h3>
                            <div class="lesson-badges">
                                <span class="lesson-type-badge badge-<?php echo strtolower($lesson['lesson_type']); ?>">
                                    <?php echo $lesson['lesson_type']; ?>
                                </span>
                                <span class="lesson-status-badge badge-<?php echo strtolower(str_replace(' ', '-', $lesson['session_status'])); ?>">
                                    <?php echo $lesson['session_status']; ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if ($lesson['video_url']): ?>
                        <div class="video-container">
                            <video class="lesson-video" id="video-<?php echo $lesson['id']; ?>" controls>
                                <source src="<?php echo htmlspecialchars($lesson['video_url']); ?>" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        </div>
                        <?php else: ?>
                        <div class="no-video-message">
                            <p>No recording available for this lesson.</p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="lesson-details">
                            <div class="participant-info">
                                <?php if ($user_type == 'tutor'): ?>
                                <div class="student-info">
                                    <h4>Student:</h4>
                                    <p><?php echo htmlspecialchars($lesson['student_name']); ?></p>
                                    <p><?php echo isset($lesson['student_email']) ? htmlspecialchars($lesson['student_email']) : 'Email not available'; ?></p>
                                </div>
                                <?php else: ?>
                                <div class="tutor-info">
                                    <h4>Tutor:</h4>
                                    <p><?php echo isset($lesson['tutor_name']) ? htmlspecialchars($lesson['tutor_name']) : 'Name not available'; ?></p>
                                    <p><?php echo isset($lesson['tutor_email']) ? htmlspecialchars($lesson['tutor_email']) : 'Email not available'; ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="notes-section collapsible">
                                <h4 class="collapsible-header">Session Notes <span class="toggle-icon">+</span></h4>
                                <div class="collapsible-content">
                                    <?php if (!empty($lesson['notes'])): ?>
                                        <div class="formatted-notes">
                                            <?php echo nl2br(htmlspecialchars($lesson['notes'])); ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="no-notes-message">No session notes available.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="homework-section collapsible">
                                <h4 class="collapsible-header">Related Homework <span class="toggle-icon">+</span></h4>
                                <div class="collapsible-content">
                                    <?php if ($homework_result && $homework_result->num_rows > 0): ?>
                                        <?php while($homework = $homework_result->fetch_assoc()): ?>
                                            <div class="homework-item">
                                                <h5><?php echo htmlspecialchars($homework['title']); ?></h5>
                                                <p class="homework-description">
                                                    <?php echo nl2br(htmlspecialchars($homework['description'])); ?>
                                                </p>
                                                <div class="homework-meta">
                                                    <p class="due-date">
                                                        Due: <?php echo date('F j, Y', strtotime($homework['due_date'])); ?>
                                                    </p>
                                                    <p class="status <?php echo strtolower($homework['status']); ?>">
                                                        Status: <?php echo $homework['status']; ?>
                                                    </p>
                                                    <?php if (!empty($homework['grade'])): ?>
                                                        <p class="grade">Grade: <?php echo htmlspecialchars($homework['grade']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <?php if (!empty($homework['file_path'])): ?>
                                                <div class="homework-file">
                                                    <a href="<?php echo htmlspecialchars($homework['file_path']); ?>" class="download-link" download>
                                                        Download Assignment
                                                    </a>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($homework['submitted_file_path'])): ?>
                                                <div class="submission-file">
                                                    <a href="<?php echo htmlspecialchars($homework['submitted_file_path']); ?>" class="download-link" download>
                                                        Download Submission
                                                    </a>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($homework['feedback'])): ?>
                                                <div class="feedback-section">
                                                    <h6>Feedback:</h6>
                                                    <div class="feedback-content">
                                                        <?php echo nl2br(htmlspecialchars($homework['feedback'])); ?>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <p class="no-homework-message">No homework associated with this lesson.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-recordings-message">
                    <p>No lesson recordings found. Please adjust your filters or check back later.</p>
                </div>
            <?php endif; ?>
        </section>
    </div>
    
    <script>
        // Simple JavaScript for collapsible sections
        document.addEventListener('DOMContentLoaded', function() {
            const collapsibles = document.querySelectorAll('.collapsible-header');
            
            collapsibles.forEach(header => {
                header.addEventListener('click', function() {
                    this.classList.toggle('active');
                    
                    const content = this.nextElementSibling;
                    const toggleIcon = this.querySelector('.toggle-icon');
                    
                    if (content.style.maxHeight) {
                        content.style.maxHeight = null;
                        toggleIcon.textContent = '+';
                    } else {
                        content.style.maxHeight = content.scrollHeight + 'px';
                        toggleIcon.textContent = '-';
                    }
                });
            });
        });
    </script>
    <script src="recording.js"></script>
</body>
</html>