<?php
// Start session
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin-login.php');
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

// Get admin information
$admin_id = $_SESSION['admin_id'];
$stmt = $conn->prepare("SELECT * FROM admins WHERE admin_id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin_result = $stmt->get_result();
$admin = $admin_result->fetch_assoc();
$stmt->close();

// Get all students
$stmt = $conn->prepare("SELECT * FROM students ORDER BY name ASC");
$stmt->execute();
$students_result = $stmt->get_result();
$students = $students_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get all tutors
$stmt = $conn->prepare("SELECT * FROM tutors ORDER BY name ASC");
$stmt->execute();
$tutors_result = $stmt->get_result();
$tutors = $tutors_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Process form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    if (
        empty($_POST['student_name']) ||
        empty($_POST['tutor_id']) ||
        empty($_POST['lesson_type']) ||
        empty($_POST['start_time']) ||
        empty($_POST['end_time'])
    ) {
        $error_message = "Please fill in all required fields.";
    } else {
        $student_name = $_POST['student_name'];
        $tutor_id = $_POST['tutor_id'];
        $lesson_type = $_POST['lesson_type'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $notes = $_POST['notes'] ?? '';
        
        // Check if single date or recurring
        $is_recurring = isset($_POST['is_recurring']) && $_POST['is_recurring'] == 1;
        
        if ($is_recurring) {
            // Recurring lessons
            $day_of_week = $_POST['day_of_week'];
            $repeat_until = $_POST['repeat_until'];
            
            // Get all dates between now and repeat_until for the specified day of week
            $start_date = new DateTime();
            $end_date = new DateTime($repeat_until);
            $interval = new DateInterval('P1D'); // 1 day interval
            $daterange = new DatePeriod($start_date, $interval, $end_date);
            
            $day_names = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            $target_day_index = array_search($day_of_week, $day_names);
            
            $success_count = 0;
            
            foreach ($daterange as $date) {
                if ($date->format('w') == $target_day_index) {
                    // This date matches our day of week
                    $lesson_date = $date->format('Y-m-d');
                    
                    $stmt = $conn->prepare("
                        INSERT INTO lessons 
                        (tutor_id, student_name, lesson_date, start_time, end_time, 
                        lesson_type, session_status, notes) 
                        VALUES (?, ?, ?, ?, ?, ?, 'Scheduled', ?)
                    ");
                    $stmt->bind_param(
                        "issssss",
                        $tutor_id,
                        $student_name,
                        $lesson_date,
                        $start_time,
                        $end_time,
                        $lesson_type,
                        $notes
                    );
                    
                    if ($stmt->execute()) {
                        $success_count++;
                    }
                    $stmt->close();
                }
            }
            
            if ($success_count > 0) {
                $success_message = "Successfully scheduled $success_count recurring lessons on $day_of_week.";
            } else {
                $error_message = "Failed to schedule recurring lessons.";
            }
            
        } else {
            // Single lesson
            $lesson_date = $_POST['lesson_date'];
            
            $stmt = $conn->prepare("
                INSERT INTO lessons 
                (tutor_id, student_name, lesson_date, start_time, end_time, 
                lesson_type, session_status, notes) 
                VALUES (?, ?, ?, ?, ?, ?, 'Scheduled', ?)
            ");
            $stmt->bind_param(
                "issssss",
                $tutor_id,
                $student_name,
                $lesson_date,
                $start_time,
                $end_time,
                $lesson_type,
                $notes
            );
            
            if ($stmt->execute()) {
                // Get tutor name for display message
                $tutor_name = "";
                foreach ($tutors as $tutor) {
                    if ($tutor['id'] == $tutor_id) {
                        $tutor_name = $tutor['name'];
                        break;
                    }
                }
                $success_message = "Lesson successfully scheduled for $student_name with $tutor_name on $lesson_date.";
            } else {
                $error_message = "Failed to schedule lesson: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Get the current date for min attribute of date inputs
$today = date('Y-m-d');
$max_date = date('Y-m-d', strtotime('+6 months'));

// Get recent lessons (for display in the recent schedules section)
$stmt = $conn->prepare("
    SELECT l.*, t.name as tutor_name 
    FROM lessons l 
    JOIN tutors t ON l.tutor_id = t.id 
    ORDER BY l.created_at DESC 
    LIMIT 10");
$stmt->execute();
$recent_lessons = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Lessons - Admin System</title>
    <link rel="stylesheet" href="scheduling.css">
</head>
<body>
    <header>
        <h1>Schedule Lessons</h1>
        <div class="user-info">
            <p>Logged in as: <?php echo htmlspecialchars($admin['username']); ?> (Admin)</p>
            <nav>
                <ul>
                    <li><a href="admin_dashboard.php">Dashboard</a></li>
                    <li><a href="manage_students.php">Manage Students</a></li>
                    <li><a href="manage_tutors.php">Manage Tutors</a></li>
                    <li><a href="admin_logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <?php if (!empty($success_message)): ?>
            <div class="success-message">
                <p><?php echo $success_message; ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <p><?php echo $error_message; ?></p>
            </div>
        <?php endif; ?>

        <section class="schedule-form-container">
            <h2>Create New Lesson</h2>
            <form id="lessonForm" method="post" action="lesson-schedule.php">
                <!-- Student Selection -->
                <div class="form-group">
                    <label for="student_name">Select Student: <span class="required">*</span></label>
                    <select id="student_name" name="student_name" required>
                        <option value="">-- Select a student --</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?php echo htmlspecialchars($student['name']); ?>">
                                <?php echo htmlspecialchars($student['name']); ?> 
                                <?php if (!empty($student['grade'])): ?>
                                    (Grade: <?php echo htmlspecialchars($student['grade']); ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Tutor Selection (New) -->
                <div class="form-group">
                    <label for="tutor_id">Select Tutor: <span class="required">*</span></label>
                    <select id="tutor_id" name="tutor_id" required>
                        <option value="">-- Select a tutor --</option>
                        <?php foreach ($tutors as $tutor): ?>
                            <option value="<?php echo $tutor['id']; ?>">
                                <?php echo htmlspecialchars($tutor['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Lesson Type -->
                <div class="form-group">
                    <label for="lesson_type">Lesson Type: <span class="required">*</span></label>
                    <select id="lesson_type" name="lesson_type" required>
                        <option value="Regular">Regular</option>
                        <option value="Demo">Demo</option>
                        <option value="Catchup">Catch-up</option>
                    </select>
                </div>

                <!-- Scheduling Type Toggle -->
                <div class="form-group">
                    <label>Scheduling Type:</label>
                    <div class="radio-group">
                        <input type="radio" id="single_lesson" name="is_recurring" value="0" checked>
                        <label for="single_lesson">Single Lesson</label>
                        
                        <input type="radio" id="recurring_lesson" name="is_recurring" value="1">
                        <label for="recurring_lesson">Recurring Weekly</label>
                    </div>
                </div>

                <!-- Single Lesson Date (shown by default) -->
                <div id="single_date_container" class="form-group">
                    <label for="lesson_date">Lesson Date: <span class="required">*</span></label>
                    <input type="date" id="lesson_date" name="lesson_date" min="<?php echo $today; ?>" required>
                </div>

                <!-- Recurring Lesson Options (hidden by default) -->
                <div id="recurring_options_container" class="form-group" style="display: none;">
                    <div class="form-group">
                        <label for="day_of_week">Day of Week: <span class="required">*</span></label>
                        <select id="day_of_week" name="day_of_week">
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                            <option value="Saturday">Saturday</option>
                            <option value="Sunday">Sunday</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="repeat_until">Repeat Until: <span class="required">*</span></label>
                        <input type="date" id="repeat_until" name="repeat_until" min="<?php echo $today; ?>" max="<?php echo $max_date; ?>">
                        <small>Maximum 6 months in advance</small>
                    </div>
                </div>

                <!-- Time Selection -->
                <div class="form-row">
                    <div class="form-group half">
                        <label for="start_time">Start Time: <span class="required">*</span></label>
                        <input type="time" id="start_time" name="start_time" required>
                    </div>
                    
                    <div class="form-group half">
                        <label for="end_time">End Time: <span class="required">*</span></label>
                        <input type="time" id="end_time" name="end_time" required>
                    </div>
                </div>

                <!-- Notes -->
                <div class="form-group">
                    <label for="notes">Lesson Notes:</label>
                    <textarea id="notes" name="notes" rows="4" placeholder="Add any details about this lesson..."></textarea>
                </div>

                <!-- Quick Time Selection -->
                <div class="form-group">
                    <label>Quick Duration:</label>
                    <div class="duration-buttons">
                        <button type="button" class="duration-btn" data-duration="30">30 min</button>
                        <button type="button" class="duration-btn" data-duration="45">45 min</button>
                        <button type="button" class="duration-btn" data-duration="60">1 hour</button>
                        <button type="button" class="duration-btn" data-duration="90">1.5 hours</button>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="form-group">
                    <button type="submit" class="submit-btn">Schedule Lesson</button>
                </div>
            </form>
        </section>

        <section class="recent-schedules">
            <h2>Recently Scheduled Lessons</h2>
            <?php if (empty($recent_lessons)): ?>
                <p>No lessons have been scheduled yet.</p>
            <?php else: ?>
                <table class="lessons-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Tutor</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Type</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_lessons as $lesson): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($lesson['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($lesson['tutor_name']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($lesson['lesson_date'])); ?></td>
                                <td><?php echo date('g:i A', strtotime($lesson['start_time'])); ?> - <?php echo date('g:i A', strtotime($lesson['end_time'])); ?></td>
                                <td><?php echo htmlspecialchars($lesson['lesson_type']); ?></td>
                                <td><?php echo htmlspecialchars($lesson['session_status']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </main>
    <style>
        /* Variables for consistent styling */
:root {
  --background: #ffffff;
  --foreground: #09090b;
  --muted: #f4f4f5;
  --muted-foreground: #71717a;
  --border: #e4e4e7;
  --input: #e4e4e7;
  --primary: #18181b;
  --primary-foreground: #fafafa;
  --secondary: #f4f4f5;
  --secondary-foreground: #18181b;
  --accent: #f4f4f5;
  --accent-foreground: #18181b;
  --destructive: #ef4444;
  --destructive-foreground: #fafafa;
  --ring: #71717a;
  --radius: 0.5rem;
}

/* Base styles */
.recent-schedules {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
  color: var(--foreground);
  margin: 2rem 0;
  background-color: var(--background);
  border-radius: var(--radius);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.recent-schedules h2 {
  font-size: 1.5rem;
  font-weight: 600;
  margin-bottom: 1.5rem;
  color: var(--foreground);
}

/* Table styling */
.lessons-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  margin-bottom: 1.5rem;
  overflow: hidden;
  border-radius: var(--radius);
  border: 1px solid var(--border);
}

.lessons-table thead tr {
  background-color: var(--secondary);
  border-bottom: 1px solid var(--border);
}

.lessons-table th {
  padding: 0.75rem 1rem;
  text-align: left;
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--muted-foreground);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.lessons-table tbody tr {
  border-bottom: 1px solid var(--border);
  transition: background-color 0.2s ease;
}

.lessons-table tbody tr:last-child {
  border-bottom: none;
}

.lessons-table tbody tr:hover {
  background-color: var(--accent);
}

.lessons-table td {
  padding: 1rem;
  font-size: 0.875rem;
  color: var(--foreground);
  vertical-align: middle;
}

/* Status styling */
.lessons-table td:last-child {
  font-weight: 500;
}

/* Add some responsive styling */
@media (max-width: 768px) {
  .lessons-table {
    display: block;
    overflow-x: auto;
  }

  .lessons-table th,
  .lessons-table td {
    padding: 0.75rem;
    font-size: 0.75rem;
  }

  .recent-schedules h2 {
    font-size: 1.25rem;
  }
}

/* Empty state styling */
.recent-schedules p {
  padding: 1.5rem;
  color: var(--muted-foreground);
  background-color: var(--secondary);
  border-radius: var(--radius);
  text-align: center;
  font-size: 0.875rem;
}

/* Additional shadcn touches */
.lessons-table td:nth-child(6) {
  position: relative;
}

/* Status indicators */
.lessons-table td:nth-child(6)::before {
  content: "";
  display: inline-block;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  margin-right: 0.5rem;
}

.lessons-table td:nth-child(6):contains("Completed")::before {
  background-color: #10b981; /* Green for completed */
}

.lessons-table td:nth-child(6):contains("Scheduled")::before {
  background-color: #3b82f6; /* Blue for scheduled */
}

.lessons-table td:nth-child(6):contains("Canceled")::before {
  background-color: var(--destructive); /* Red for canceled */
}

.lessons-table td:nth-child(6):contains("Pending")::before {
  background-color: #f59e0b; /* Amber for pending */
}

/* Since :contains is not standard CSS, you can use classes instead */
.status-completed::before {
  background-color: #10b981 !important;
}

.status-scheduled::before {
  background-color: #3b82f6 !important;
}

.status-canceled::before {
  background-color: var(--destructive) !important;
}

.status-pending::before {
  background-color: #f59e0b !important;
}
    </style>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Bloom Education System. All rights reserved.</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle between single and recurring lesson options
            const singleLesson = document.getElementById('single_lesson');
            const recurringLesson = document.getElementById('recurring_lesson');
            const singleDateContainer = document.getElementById('single_date_container');
            const recurringOptionsContainer = document.getElementById('recurring_options_container');
            
            singleLesson.addEventListener('change', function() {
                if (this.checked) {
                    singleDateContainer.style.display = 'block';
                    recurringOptionsContainer.style.display = 'none';
                    
                    // Update required attributes
                    document.getElementById('lesson_date').setAttribute('required', '');
                    document.getElementById('day_of_week').removeAttribute('required');
                    document.getElementById('repeat_until').removeAttribute('required');
                }
            });
            
            recurringLesson.addEventListener('change', function() {
                if (this.checked) {
                    singleDateContainer.style.display = 'none';
                    recurringOptionsContainer.style.display = 'block';
                    
                    // Update required attributes
                    document.getElementById('lesson_date').removeAttribute('required');
                    document.getElementById('day_of_week').setAttribute('required', '');
                    document.getElementById('repeat_until').setAttribute('required', '');
                }
            });
            
            // Quick duration buttons
            const durationButtons = document.querySelectorAll('.duration-btn');
            durationButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const duration = parseInt(this.getAttribute('data-duration'));
                    
                    // Get current start time or set a default
                    let startTime = document.getElementById('start_time').value;
                    if (!startTime) {
                        startTime = '15:00'; // Default to 3:00 PM if no time selected
                        document.getElementById('start_time').value = startTime;
                    }
                    
                    // Calculate end time
                    const startDate = new Date(`2000-01-01T${startTime}`);
                    const endDate = new Date(startDate.getTime() + duration * 60000);
                    
                    // Format the time as HH:MM
                    const endHours = endDate.getHours().toString().padStart(2, '0');
                    const endMinutes = endDate.getMinutes().toString().padStart(2, '0');
                    const endTimeString = `${endHours}:${endMinutes}`;
                    
                    document.getElementById('end_time').value = endTimeString;
                });
            });
            
            // Form validation
            document.getElementById('lessonForm').addEventListener('submit', function(e) {
                const startTime = document.getElementById('start_time').value;
                const endTime = document.getElementById('end_time').value;
                
                if (startTime >= endTime) {
                    e.preventDefault();
                    alert('End time must be after start time');
                }
            });
        });
    </script>
</body>
</html>