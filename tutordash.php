<?php
// Start session
session_start();

// Database connection
require_once 'db_connect.php';

// Authentication check
if (!isset($_SESSION['tutor_id'])) {
    // Redirect to login page if not authenticated
    header('Location: tutor-login.php');
    exit;
}

// Get current tutor information
$tutor_id = $_SESSION['tutor_id'];
$tutorName = $_SESSION['tutor_name'];
// $conn variable should already be available from the included file

// Function to get tutor details
function getTutorDetails($tutor_id) {
    global $conn;
    $query = "SELECT name, email FROM tutors WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $tutor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Function to get upcoming lessons
function getUpcomingLessons($conn, $tutor_id, $limit = 5) {
    $query = "SELECT id, student_name, lesson_date, start_time, end_time, lesson_type, session_status 
              FROM lessons 
              WHERE tutor_id = ? AND lesson_date >= CURDATE() AND session_status IN ('Scheduled', 'Rescheduled')
              ORDER BY lesson_date ASC, start_time ASC 
              LIMIT ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $tutor_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $lessons = [];
    while ($row = $result->fetch_assoc()) {
        $lessons[] = $row;
    }
    return $lessons;
}

// Function to get student list with progress summary
function getStudentsWithProgress($conn, $tutor_id) {
    // This is a complex join query to get students and their progress
    $query = "SELECT DISTINCT s.student_id, s.name, s.grade, s.email, s.parent_name, s.parent_email,
              (SELECT COUNT(*) FROM student_topic_progress stp WHERE stp.student_id = s.student_id AND stp.is_completed = 1) as completed_topics,
              (SELECT COUNT(*) FROM topics t 
               INNER JOIN subjects subj ON t.subject_id = subj.subject_id
               INNER JOIN student_subjects ss ON subj.subject_id = ss.subject_id AND t.year_level = ss.year_level
               WHERE ss.student_id = s.student_id) as total_topics,
              (SELECT COUNT(*) FROM homework h WHERE h.student_id = s.student_id AND h.tutor_id = ? AND h.status = 'Assigned') as pending_homework
              FROM students s
              INNER JOIN student_subjects ss ON s.student_id = ss.student_id
              INNER JOIN lessons l ON s.name = l.student_name
              WHERE l.tutor_id = ?
              GROUP BY s.student_id";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $tutor_id, $tutor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $students = [];
    while ($row = $result->fetch_assoc()) {
        // Calculate progress percentage
        $row['progress_percentage'] = $row['total_topics'] > 0 ? 
            round(($row['completed_topics'] / $row['total_topics']) * 100) : 0;
        $students[] = $row;
    }
    return $students;
}

// Function to get homework statistics
function getHomeworkStats($conn, $tutor_id) {
    $query = "SELECT status, COUNT(*) as count FROM homework 
              WHERE tutor_id = ? 
              GROUP BY status";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $tutor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $stats = [
        'Assigned' => 0,
        'Submitted' => 0,
        'Graded' => 0,
        'Late' => 0
    ];
    
    while ($row = $result->fetch_assoc()) {
        $stats[$row['status']] = (int)$row['count'];
    }
    
    return $stats;
}

// Function to get recent homework submissions
function getRecentSubmissions($conn, $tutor_id, $limit = 5) {
    $query = "SELECT h.homework_id, h.title, h.student_id, s.name as student_name, h.submission_date, h.status
              FROM homework h
              INNER JOIN students s ON h.student_id = s.student_id
              WHERE h.tutor_id = ? AND h.status IN ('Submitted', 'Late')
              ORDER BY h.submission_date DESC
              LIMIT ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $tutor_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $submissions = [];
    while ($row = $result->fetch_assoc()) {
        $submissions[] = $row;
    }
    return $submissions;
}

// Function to get lesson statistics by month (for charts)
function getLessonStatsByMonth($conn, $tutor_id, $months = 6) {
    $query = "SELECT 
                DATE_FORMAT(lesson_date, '%Y-%m') as month, 
                COUNT(*) as total_lessons,
                SUM(CASE WHEN session_status = 'Delivered' THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN session_status = 'No Show' THEN 1 ELSE 0 END) as no_show,
                SUM(CASE WHEN session_status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN session_status = 'Rescheduled' THEN 1 ELSE 0 END) as rescheduled
              FROM lessons
              WHERE tutor_id = ? AND lesson_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
              GROUP BY DATE_FORMAT(lesson_date, '%Y-%m')
              ORDER BY month ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $tutor_id, $months);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $stats = [];
    while ($row = $result->fetch_assoc()) {
        $stats[] = $row;
    }
    return $stats;
}

// Function to get topic completion rates by subject
function getTopicCompletionBySubject($conn, $tutor_id) {
    $query = "SELECT 
                subj.subject_name,
                COUNT(DISTINCT tp.topic_id) as total_topics,
                COUNT(DISTINCT CASE WHEN stp.is_completed = 1 THEN stp.topic_id ELSE NULL END) as completed_topics
              FROM subjects subj
              INNER JOIN topics tp ON subj.subject_id = tp.subject_id
              LEFT JOIN student_topic_progress stp ON tp.topic_id = stp.topic_id AND stp.tutor_id = ?
              GROUP BY subj.subject_name";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $tutor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $stats = [];
    while ($row = $result->fetch_assoc()) {
        $row['completion_rate'] = $row['total_topics'] > 0 ? 
            round(($row['completed_topics'] / $row['total_topics']) * 100) : 0;
        $stats[] = $row;
    }
    return $stats;
}

// Function to get lesson count by weekday (for heat map chart)
function getLessonsByWeekday($conn, $tutor_id) {
    $query = "SELECT 
                DAYOFWEEK(lesson_date) as weekday,
                HOUR(start_time) as hour,
                COUNT(*) as lesson_count
              FROM lessons
              WHERE tutor_id = ? AND lesson_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
              GROUP BY DAYOFWEEK(lesson_date), HOUR(start_time)
              ORDER BY weekday, hour";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $tutor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $heatmap = [];
    while ($row = $result->fetch_assoc()) {
        $heatmap[] = $row;
    }
    return $heatmap;
}

// Function to get student progress over time (for line chart)
function getStudentProgressOverTime($conn, $tutor_id) {
    $query = "SELECT 
                s.student_id,
                s.name as student_name,
                DATE_FORMAT(stp.completed_date, '%Y-%m') as month,
                COUNT(*) as topics_completed
              FROM students s
              INNER JOIN student_topic_progress stp ON s.student_id = stp.student_id
              WHERE stp.tutor_id = ? AND stp.is_completed = 1 AND stp.completed_date IS NOT NULL
              GROUP BY s.student_id, s.name, DATE_FORMAT(stp.completed_date, '%Y-%m')
              ORDER BY s.name, month";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $tutor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $progress = [];
    while ($row = $result->fetch_assoc()) {
        if (!isset($progress[$row['student_id']])) {
            $progress[$row['student_id']] = [
                'student_name' => $row['student_name'],
                'data' => []
            ];
        }
        $progress[$row['student_id']]['data'][$row['month']] = (int)$row['topics_completed'];
    }
    return $progress;
}

// Fetch all required data
$tutorDetails = getTutorDetails($tutor_id);
$upcomingLessons = getUpcomingLessons($conn, $tutor_id);
$studentsWithProgress = getStudentsWithProgress($conn, $tutor_id);
$homeworkStats = getHomeworkStats($conn, $tutor_id);
$recentSubmissions = getRecentSubmissions($conn, $tutor_id);
$lessonStatsByMonth = getLessonStatsByMonth($conn, $tutor_id);
$topicCompletionBySubject = getTopicCompletionBySubject($conn, $tutor_id);
$lessonsByWeekday = getLessonsByWeekday($conn, $tutor_id);
$studentProgressOverTime = getStudentProgressOverTime($conn, $tutor_id);

// Calculate summary statistics
$totalStudents = count($studentsWithProgress);
$totalUpcomingLessons = count($upcomingLessons);
$totalPendingHomework = array_sum(array_column($studentsWithProgress, 'pending_homework'));
$averageStudentProgress = $totalStudents > 0 ? 
    array_sum(array_column($studentsWithProgress, 'progress_percentage')) / $totalStudents : 0;

// Prepare data for JSON encoding (for JavaScript charts)
$chartsData = [
    'homeworkStats' => $homeworkStats,
    'lessonStatsByMonth' => $lessonStatsByMonth,
    'topicCompletionBySubject' => $topicCompletionBySubject,
    'lessonsByWeekday' => $lessonsByWeekday,
    'studentProgressOverTime' => $studentProgressOverTime
];
$chartsDataJSON = json_encode($chartsData);

// Close database connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutor Dashboard</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
</head>
<body>
    <style>
        :root {
  /* Color palette */
  --primary: #0284c7;
  --primary-light: #0ea5e9;
  --primary-dark: #0369a1;
  --secondary: #8b5cf6;
  --accent: #f59e0b;
  --success: #10b981;
  --warning: #f59e0b;
  --danger: #ef4444;
  --gray-50: #f9fafb;
  --gray-100: #f3f4f6;
  --gray-200: #e5e7eb;
  --gray-300: #d1d5db;
  --gray-400: #9ca3af;
  --gray-500: #6b7280;
  --gray-600: #4b5563;
  --gray-700: #374151;
  --gray-800: #1f2937;
  --gray-900: #111827;
  
  /* Typography */
  --font-sans: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  
  /* Shadows */
  --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
  --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
  --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
  --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
  
  /* Border radius */
  --radius-sm: 0.125rem;
  --radius-md: 0.375rem;
  --radius-lg: 0.5rem;
  --radius-xl: 0.75rem;
  --radius-2xl: 1rem;
  
  /* Animations */
  --transition: 150ms cubic-bezier(0.4, 0, 0.2, 1);
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: var(--font-sans);
  background-color: var(--gray-50);
  color: var(--gray-800);
  line-height: 1.5;
  min-height: 100vh;
  padding-bottom: 2rem;
}

header {
  background-color: white;
  padding: 1.5rem 2rem;
  box-shadow: var(--shadow);
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  border-bottom: 1px solid var(--gray-200);
}

header h1 {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--gray-900);
}

.profile-info {
  text-align: right;
}

.profile-info p {
  margin: 0;
}

.profile-info p:first-child {
  font-weight: 600;
}

.profile-info p:last-child {
  color: var(--gray-500);
  font-size: 0.875rem;
}

/* Dashboard summary cards */
.dashboard-summary {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
  margin: 0 2rem 1.5rem;
}

.summary-card {
  background-color: white;
  border-radius: var(--radius-lg);
  padding: 1.25rem;
  box-shadow: var(--shadow);
  transition: transform var(--transition), box-shadow var(--transition);
  border: 1px solid var(--gray-200);
}

.summary-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.summary-card h3 {
  color: var(--gray-600);
  font-size: 0.875rem;
  font-weight: 500;
  margin-bottom: 0.5rem;
}

.summary-value {
  font-size: 2rem;
  font-weight: 700;
  color: var(--gray-900);
}

/* Dashboard grid layout */
.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(12, 1fr);
  gap: 1.5rem;
  padding: 0 2rem;
}

.dashboard-section {
  background-color: white;
  border-radius: var(--radius-lg);
  padding: 1.5rem;
  box-shadow: var(--shadow);
  transition: box-shadow var(--transition);
  border: 1px solid var(--gray-200);
}

.dashboard-section:hover {
  box-shadow: var(--shadow-md);
}

.dashboard-section h2 {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--gray-800);
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid var(--gray-200);
}

.dashboard-section h3 {
  font-size: 1rem;
  font-weight: 500;
  color: var(--gray-700);
  margin-bottom: 0.75rem;
}

/* Section sizes */
.upcoming-lessons {
  grid-column: span 8;
}

.lesson-stats {
  grid-column: span 4;
}

.homework-stats {
  grid-column: span 4;
}

.student-progress {
  grid-column: span 8;
}

.topic-completion {
  grid-column: span 6;
}

.lesson-heatmap {
  grid-column: span 6;
}

.student-progress-over-time {
  grid-column: span 12;
}

/* Table styling */
.table-container {
  overflow-x: auto;
  margin-bottom: 1rem;
}

table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

thead {
  background-color: var(--gray-100);
}

th {
  padding: 0.75rem 1rem;
  text-align: left;
  font-weight: 500;
  color: var(--gray-700);
  border-bottom: 1px solid var(--gray-300);
}

td {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid var(--gray-200);
  color: var(--gray-800);
}

tbody tr:hover {
  background-color: var(--gray-50);
}

.text-center {
  text-align: center;
}

/* Button styles */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 500;
  font-size: 0.875rem;
  padding: 0.5rem 1rem;
  border-radius: var(--radius-md);
  transition: all var(--transition);
  cursor: pointer;
  text-decoration: none;
}

.btn-primary {
  background-color: var(--primary);
  color: white;
  border: none;
}

.btn-primary:hover {
  background-color: var(--primary-dark);
}

.btn-outline {
  background-color: transparent;
  color: var(--primary);
  border: 1px solid var(--gray-300);
}

.btn-outline:hover {
  background-color: var(--gray-100);
  border-color: var(--gray-400);
}

/* View all links */
.view-all-link {
  display: block;
  text-align: center;
  color: var(--primary);
  font-weight: 500;
  font-size: 0.875rem;
  margin-top: 0.5rem;
  text-decoration: none;
}

.view-all-link:hover {
  text-decoration: underline;
  color: var(--primary-dark);
}

/* Chart containers */
.chart-container {
  width: 100%;
  height: 300px;
  position: relative;
}

/* Progress bar */
.progress-bar {
  width: 100%;
  height: 0.5rem;
  background-color: var(--gray-200);
  border-radius: 9999px;
  overflow: hidden;
  margin-bottom: 0.25rem;
}

.progress-bar-fill {
  height: 100%;
  background-color: var(--primary);
  border-radius: 9999px;
  transition: width 0.5s ease;
}

.progress-text {
  font-size: 0.75rem;
  color: var(--gray-600);
}

/* Submission list */
.submission-list {
  list-style-type: none;
  padding: 0;
}

.submission-list li {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 0;
  border-bottom: 1px solid var(--gray-200);
}

.submission-list li:last-child {
  border-bottom: none;
}

.submission-info {
  flex: 1;
}

.student-name {
  font-weight: 500;
  margin-bottom: 0.25rem;
}

.submission-title {
  font-size: 0.875rem;
  color: var(--gray-700);
  margin-bottom: 0.25rem;
}

.submission-date {
  font-size: 0.75rem;
  color: var(--gray-500);
}

.submission-actions {
  margin-left: 1rem;
}

/* Responsive adjustments */
@media (max-width: 1200px) {
  .dashboard-summary {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .upcoming-lessons,
  .lesson-stats,
  .homework-stats,
  .student-progress,
  .topic-completion,
  .lesson-heatmap,
  .student-progress-over-time {
    grid-column: span 12;
  }
}

@media (max-width: 768px) {
  .dashboard-summary {
    grid-template-columns: 1fr;
  }
  
  header {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .profile-info {
    text-align: left;
    margin-top: 1rem;
  }
  
  .dashboard-grid,
  .dashboard-summary {
    padding: 0 1rem;
  }
}

/* Status colors */
[class*="session-status-"] {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: var(--radius-md);
  font-size: 0.75rem;
  font-weight: 500;
}

.session-status-scheduled {
  background-color: rgba(33, 150, 243, 0.1);
  color: #2196F3;
}

.session-status-completed {
  background-color: rgba(76, 175, 80, 0.1);
  color: #4CAF50;
}

.session-status-cancelled {
  background-color: rgba(244, 67, 54, 0.1);
  color: #F44336;
}

.session-status-no-show {
  background-color: rgba(255, 152, 0, 0.1);
  color: #FF9800;
}

/* Animations */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.dashboard-section {
  animation: fadeIn 0.3s ease-out forwards;
}

.dashboard-section:nth-child(1) { animation-delay: 0.1s; }
.dashboard-section:nth-child(2) { animation-delay: 0.2s; }
.dashboard-section:nth-child(3) { animation-delay: 0.3s; }
.dashboard-section:nth-child(4) { animation-delay: 0.4s; }
.dashboard-section:nth-child(5) { animation-delay: 0.5s; }
.dashboard-section:nth-child(6) { animation-delay: 0.6s; }

/* Custom Chart Styles */
.chart-container canvas {
  border-radius: var(--radius-md);
}
/* Additional Chart Animations and Styles */
.chart-container {
  position: relative;
  opacity: 0;
}

.chart-container.animated {
  opacity: 1;
}

@keyframes fadeIn {
  from { 
    opacity: 0;
    transform: translateY(10px);
  }
  to { 
    opacity: 1;
    transform: translateY(0);
  }
}

/* Chart legend styles */
.chart-container ul li {
  margin-bottom: 8px !important;
}

.chart-container canvas {
  padding: 10px;
}

/* Chart tooltip customization */
#chartjs-tooltip {
  background: var(--gray-800);
  border-radius: var(--radius-lg);
  color: #fff;
  opacity: 0;
  pointer-events: none;
  position: absolute;
  transform: translate(-50%, 0);
  transition: all .1s ease;
  z-index: 100;
}

/* Highlight for chart hover */
.highlight-region {
  position: absolute;
  background: rgba(255, 255, 255, 0.05);
  border-radius: var(--radius-lg);
  pointer-events: none;
  z-index: 1;
  box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.1);
  transition: all 0.3s ease;
}

/* Custom scrollbar for chart containers */
.chart-container::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}

.chart-container::-webkit-scrollbar-track {
  background: rgba(0, 0, 0, 0.05);
  border-radius: 10px;
}

.chart-container::-webkit-scrollbar-thumb {
  background: var(--primary-light);
  border-radius: 10px;
}

/* Additional responsive adjustments for charts */
@media (max-width: 768px) {
  .chart-container {
    height: 250px !important;
  }
}
    </style>
    <header>
        <h1>Tutor Dashboard</h1>
        <div class="profile-info">
            <p>Welcome, <?php echo htmlspecialchars($tutorDetails['name']); ?></p>
            <p><?php echo htmlspecialchars($tutorDetails['email']); ?></p>
        </div>
    </header>

    <section class="dashboard-summary">
        <div class="summary-card">
            <h3>Students</h3>
            <p class="summary-value"><?php echo $totalStudents; ?></p>
        </div>
        <div class="summary-card">
            <h3>Upcoming Lessons</h3>
            <p class="summary-value"><?php echo $totalUpcomingLessons; ?></p>
        </div>
        <div class="summary-card">
            <h3>Pending Homework</h3>
            <p class="summary-value"><?php echo $totalPendingHomework; ?></p>
        </div>
        <div class="summary-card">
            <h3>Avg. Student Progress</h3>
            <p class="summary-value"><?php echo round($averageStudentProgress); ?>%</p>
        </div>
    </section>

    <div class="dashboard-grid">
        <section class="dashboard-section upcoming-lessons">
            <h2>Upcoming Lessons</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcomingLessons as $lesson): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($lesson['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($lesson['lesson_date']); ?></td>
                            <td><?php echo htmlspecialchars($lesson['start_time']) . ' - ' . htmlspecialchars($lesson['end_time']); ?></td>
                            <td><?php echo htmlspecialchars($lesson['lesson_type']); ?></td>
                            <td><?php echo htmlspecialchars($lesson['session_status']); ?></td>
                            <td>
                                <a href="lesson_details.php?id=<?php echo $lesson['id']; ?>" class="btn btn-outline">View</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (count($upcomingLessons) === 0): ?>
                        <tr>
                            <td colspan="6" class="text-center">No upcoming lessons scheduled.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <a href="lessons.php" class="view-all-link">View All Lessons</a>
        </section>

        <section class="dashboard-section lesson-stats">
            <h2>Lesson Statistics</h2>
            <div class="chart-container">
                <canvas id="lessonStatsChart"></canvas>
            </div>
        </section>

        <section class="dashboard-section homework-stats">
            <h2>Homework Overview</h2>
            <div class="chart-container">
                <canvas id="homeworkStatsChart"></canvas>
            </div>
            <div class="recent-submissions">
                <h3>Recent Submissions</h3>
                <ul class="submission-list">
                    <?php foreach ($recentSubmissions as $submission): ?>
                    <li>
                        <div class="submission-info">
                            <p class="student-name"><?php echo htmlspecialchars($submission['student_name']); ?></p>
                            <p class="submission-title"><?php echo htmlspecialchars($submission['title']); ?></p>
                            <p class="submission-date">Submitted: <?php echo htmlspecialchars($submission['submission_date']); ?></p>
                        </div>
                        <div class="submission-actions">
                            <a href="grade_homework.php?id=<?php echo $submission['homework_id']; ?>" class="btn btn-primary">Grade</a>
                        </div>
                    </li>
                    <?php endforeach; ?>
                    <?php if (count($recentSubmissions) === 0): ?>
                    <li class="text-center">No recent submissions.</li>
                    <?php endif; ?>
                </ul>
                <a href="homework.php" class="view-all-link">View All Homework</a>
            </div>
        </section>

        <section class="dashboard-section student-progress">
            <h2>Student Progress</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Grade</th>
                            <th>Progress</th>
                            <th>Pending Homework</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($studentsWithProgress as $student): ?>
                        <tr>
                        <td><?php echo isset($student['name']) ? htmlspecialchars($student['name']) : 'Unknown'; ?></td>
                        <td><?php echo isset($student['grade']) ? htmlspecialchars($student['grade']) : 'N/A'; ?></td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress-bar-fill" style="width: <?php echo $student['progress_percentage']; ?>%"></div>
                                </div>
                                <span class="progress-text"><?php echo $student['progress_percentage']; ?>% (<?php echo $student['completed_topics']; ?>/<?php echo $student['total_topics']; ?> topics)</span>
                            </td>
                            <td><?php echo $student['pending_homework']; ?></td>
                            <td>
                                <a href="student_details.php?id=<?php echo $student['student_id']; ?>" class="btn btn-outline">View</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (count($studentsWithProgress) === 0): ?>
                        <tr>
                            <td colspan="5" class="text-center">No students assigned.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <a href="students.php" class="view-all-link">View All Students</a>
        </section>

        <section class="dashboard-section topic-completion">
            <h2>Topic Completion by Subject</h2>
            <div class="chart-container">
                <canvas id="topicCompletionChart"></canvas>
            </div>
        </section>

        <section class="dashboard-section lesson-heatmap">
            <h2>Lesson Schedule Heatmap</h2>
            <div class="chart-container">
                <canvas id="lessonHeatmapChart"></canvas>
            </div>
        </section>

        <section class="dashboard-section student-progress-over-time">
            <h2>Student Progress Over Time</h2>
            <div class="chart-container">
                <canvas id="progressOverTimeChart"></canvas>
            </div>
        </section>
    </div>

    <script>
      // Enhanced chart configuration with modern styling
document.addEventListener('DOMContentLoaded', () => {
    // Custom chart theme
    Chart.defaults.font.family = "var(--font-sans)";
    Chart.defaults.font.size = 12;
    Chart.defaults.color = "var(--gray-600)";
    Chart.defaults.plugins.tooltip.backgroundColor = "var(--gray-800)";
    Chart.defaults.plugins.tooltip.titleColor = "white";
    Chart.defaults.plugins.tooltip.bodyColor = "white";
    Chart.defaults.plugins.tooltip.padding = 12;
    Chart.defaults.plugins.tooltip.cornerRadius = 6;
    Chart.defaults.plugins.tooltip.displayColors = true;
    Chart.defaults.plugins.tooltip.boxPadding = 6;
    Chart.defaults.plugins.tooltip.usePointStyle = true;
    Chart.defaults.plugins.title.font = {
        size: 14,
        weight: '500'
    };
    
    // Color palette
    const chartColors = {
        primary: '#0284c7',
        secondary: '#8b5cf6',
        success: '#10b981',
        warning: '#f59e0b',
        danger: '#ef4444',
        info: '#06b6d4',
        gray: '#9ca3af',
        pastelColors: [
            '#60a5fa', // blue
            '#34d399', // green
            '#a78bfa', // purple
            '#fbbf24', // yellow
            '#f87171', // red
            '#2dd4bf', // teal
            '#fb923c', // orange
            '#e879f9'  // pink
        ]
    };
    
    // Apply smooth animation
    const animationOptions = {
        animation: {
            duration: 1500,
            easing: 'easeOutQuart'
        },
        transitions: {
            active: {
                animation: {
                    duration: 400
                }
            }
        }
    };
    
    // Enhanced Lesson Statistics Chart (Bar chart)
    const setupLessonStatsChart = () => {
        const ctx = document.getElementById('lessonStatsChart').getContext('2d');
        const months = chartsData.lessonStatsByMonth.map(item => moment(item.month + '-01').format('MMM YYYY'));
        const delivered = chartsData.lessonStatsByMonth.map(item => parseInt(item.delivered));
        const noShow = chartsData.lessonStatsByMonth.map(item => parseInt(item.no_show));
        const cancelled = chartsData.lessonStatsByMonth.map(item => parseInt(item.cancelled));
        const rescheduled = chartsData.lessonStatsByMonth.map(item => parseInt(item.rescheduled));
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [
                    {
                        label: 'Delivered',
                        data: delivered,
                        backgroundColor: chartColors.success,
                        borderRadius: 4,
                        borderWidth: 0
                    },
                    {
                        label: 'No Show',
                        data: noShow,
                        backgroundColor: chartColors.danger,
                        borderRadius: 4,
                        borderWidth: 0
                    },
                    {
                        label: 'Cancelled',
                        data: cancelled,
                        backgroundColor: chartColors.warning,
                        borderRadius: 4,
                        borderWidth: 0
                    },
                    {
                        label: 'Rescheduled',
                        data: rescheduled,
                        backgroundColor: chartColors.primary,
                        borderRadius: 4,
                        borderWidth: 0
                    }
                ]
            },
            options: {
                ...animationOptions,
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 15,
                            boxWidth: 10,
                            boxHeight: 10
                        }
                    },
                    title: {
                        display: true,
                        text: 'Lesson Status by Month',
                        padding: {
                            bottom: 15
                        }
                    },
                    tooltip: {
                        callbacks: {
                            title: (tooltipItems) => {
                                return tooltipItems[0].label;
                            },
                            label: (context) => {
                                return `${context.dataset.label}: ${context.raw} lessons`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        stacked: true,
                        grid: {
                            display: false
                        },
                        border: {
                            display: false
                        }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        border: {
                            display: false
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            stepSize: 5,
                            callback: (value) => {
                                return value
                            }
                        }
                    }
                }
            }
        });
    };

    // Enhanced Homework Stats Chart (Doughnut chart instead of Pie)
    const setupHomeworkStatsChart = () => {
        const ctx = document.getElementById('homeworkStatsChart').getContext('2d');
        const labels = Object.keys(chartsData.homeworkStats);
        const data = Object.values(chartsData.homeworkStats);
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: [
                        chartColors.warning,    // Assigned
                        chartColors.primary,    // Submitted
                        chartColors.success,    // Graded
                        chartColors.danger      // Late
                    ],
                    borderWidth: 0,
                    borderRadius: 4,
                    spacing: 3,
                    hoverOffset: 8
                }]
            },
            options: {
                ...animationOptions,
                cutout: '65%',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 15,
                            boxWidth: 8,
                            boxHeight: 8
                        }
                    },
                    title: {
                        display: true,
                        text: 'Homework Status Distribution',
                        padding: {
                            bottom: 15
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((context.raw / total) * 100);
                                return `${context.label}: ${context.raw} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    };

    // Enhanced Topic Completion Chart (Horizontal Bar chart)
    const setupTopicCompletionChart = () => {
        const ctx = document.getElementById('topicCompletionChart').getContext('2d');
        const subjects = chartsData.topicCompletionBySubject.map(item => item.subject_name);
        const completionRates = chartsData.topicCompletionBySubject.map(item => item.completion_rate);
        
        // Create a gradient
        const createGradient = (ctx, rate) => {
            const gradient = ctx.createLinearGradient(0, 0, 200, 0);
            gradient.addColorStop(0, 'rgba(2, 132, 199, 0.8)');
            gradient.addColorStop(1, 'rgba(14, 165, 233, 0.6)');
            return gradient;
        };
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: subjects,
                datasets: [{
                    label: 'Completion Rate (%)',
                    data: completionRates,
                    backgroundColor: function(context) {
                        const chart = context.chart;
                        const {ctx, chartArea} = chart;
                        if (!chartArea) {
                            return null;
                        }
                        return createGradient(ctx);
                    },
                    borderRadius: 6,
                    borderWidth: 0,
                    barPercentage: 0.7,
                    categoryPercentage: 0.8
                }]
            },
            options: {
                ...animationOptions,
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false,
                    },
                    title: {
                        display: true,
                        text: 'Topic Completion Rate by Subject',
                        padding: {
                            bottom: 15
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                return `${context.raw}% completed`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 100,
                        border: {
                            display: false
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            callback: (value) => {
                                return value + '%';
                            }
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        },
                        border: {
                            display: false
                        }
                    }
                }
            }
        });
    };

    // Enhanced Lesson Heatmap
    const setupLessonHeatmapChart = () => {
        const ctx = document.getElementById('lessonHeatmapChart').getContext('2d');
        
        // Process the data for heatmap
        const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        
        // Create data array for heatmap
        let data = [];
        chartsData.lessonsByWeekday.forEach(item => {
            data.push({
                x: days[parseInt(item.weekday) - 1], // Adjusted index
                y: parseInt(item.hour),
                v: parseInt(item.lesson_count)
            });
        });
        
        new Chart(ctx, {
            type: 'scatter',
            data: {
                datasets: [{
                    label: 'Lesson Frequency',
                    data: data,
                    backgroundColor: (context) => {
                        const value = context.dataset.data[context.dataIndex].v;
                        // Create a more vibrant color scale
                        const opacity = Math.min(0.2 + (value / 8), 1);
                        return `rgba(2, 132, 199, ${opacity})`;
                    },
                    pointRadius: 12,
                    pointHoverRadius: 14,
                    hoverBorderWidth: 2,
                    hoverBorderColor: 'rgba(255, 255, 255, 0.8)'
                }]
            },
            options: {
                ...animationOptions,
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Lesson Frequency by Day and Hour',
                        padding: {
                            bottom: 15
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                const point = context.dataset.data[context.dataIndex];
                                return `${point.x}, ${point.y}:00 - ${point.v} lessons`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        type: 'category',
                        labels: days,
                        title: {
                            display: true,
                            text: 'Day of Week',
                            padding: {
                                top: 10
                            }
                        },
                        grid: {
                            display: false
                        },
                        border: {
                            display: false
                        }
                    },
                    y: {
                        reverse: false,
                        min: 6,
                        max: 22,
                        title: {
                            display: true,
                            text: 'Hour of Day',
                            padding: {
                                bottom: 10
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.03)'
                        },
                        border: {
                            display: false
                        },
                        ticks: {
                            stepSize: 2,
                            callback: (value) => {
                                return `${value}:00`;
                            }
                        }
                    }
                }
            }
        });
    };

    // Enhanced Student Progress Over Time Chart (Line chart)
    const setupProgressOverTimeChart = () => {
        const ctx = document.getElementById('progressOverTimeChart').getContext('2d');
        
        // Process the data for line chart
        const studentIds = Object.keys(chartsData.studentProgressOverTime);
        const datasets = studentIds.map((id, index) => {
            const student = chartsData.studentProgressOverTime[id];
            const data = [];
            const months = Object.keys(student.data).sort();
            
            // Calculate cumulative progress
            let cumulativeTopics = 0;
            months.forEach(month => {
                cumulativeTopics += student.data[month];
                data.push({
                    x: moment(month + '-01').format('MMM YYYY'),
                    y: cumulativeTopics
                });
            });
            
            return {
                label: student.student_name,
                data: data,
                borderColor: chartColors.pastelColors[index % chartColors.pastelColors.length],
                backgroundColor: `${chartColors.pastelColors[index % chartColors.pastelColors.length]}20`,
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: chartColors.pastelColors[index % chartColors.pastelColors.length],
                pointBorderColor: '#fff',
                pointBorderWidth: 1,
                pointRadius: 4,
                pointHoverRadius: 6
            };
        });
        
        new Chart(ctx, {
            type: 'line',
            data: {
                datasets: datasets
            },
            options: {
                ...animationOptions,
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 15,
                            boxWidth: 8,
                            boxHeight: 8
                        }
                    },
                    title: {
                        display: true,
                        text: 'Cumulative Topics Completed Over Time',
                        padding: {
                            bottom: 15
                        }
                    }
                },
                scales: {
                    x: {
                        type: 'category',
                        title: {
                            display: true,
                            text: 'Month',
                            padding: {
                                top: 10
                            }
                        },
                        grid: {
                            display: false
                        },
                        border: {
                            display: false
                        },
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Total Topics Completed',
                            padding: {
                                bottom: 10
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        border: {
                            display: false
                        }
                    }
                }
            }
        });
    };

    // Initialize all charts
    setupLessonStatsChart();
    setupHomeworkStatsChart();
    setupTopicCompletionChart();
    setupLessonHeatmapChart();
    setupProgressOverTimeChart();
});

// Add chart animations on scroll for better visual experience
const addChartAnimations = () => {
    const chartContainers = document.querySelectorAll('.chart-container');
    
    const isInViewport = (element) => {
        const rect = element.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    };
    
    const handleScroll = () => {
        chartContainers.forEach(container => {
            if (isInViewport(container) && !container.classList.contains('animated')) {
                container.classList.add('animated');
                // Add a subtle fade-in animation
                container.style.animation = 'fadeIn 0.6s ease-out forwards';
            }
        });
    };
    
    // Initial check
    handleScroll();
    
    // Add scroll listener
    window.addEventListener('scroll', handleScroll);
};

// Initialize animations when DOM is loaded
document.addEventListener('DOMContentLoaded', addChartAnimations);
    </script>
</body>
</html>