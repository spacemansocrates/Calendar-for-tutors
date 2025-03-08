<?php
// Start session
session_start();

// Database connection
require_once 'db_connect.php';

// Authentication check (simplified for this example)
// In a real system, you'd have proper login logic
$tutor_id = 1; // Hardcoded for demonstration

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$recordsPerPage = 10; // Number of homework items per page
$offset = ($page - 1) * $recordsPerPage;

// Modify the count query to use the full filter conditions
$count_query = "
    SELECT COUNT(*) as total
    FROM homework h
    JOIN students s ON h.student_id = s.student_id
    WHERE h.tutor_id = $tutor_id
";

// Add existing filters to count query
if (!empty($studentFilter)) {
    $count_query .= " AND s.name = '$studentFilter'";
}

if (!empty($statusFilter)) {
    $count_query .= " AND h.status = '$statusFilter'";
}

// Execute count query
$count_result = mysqli_query($conn, $count_query);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $recordsPerPage);

// Modify main query to include pagination
$query = "
    SELECT h.*, s.name as student_name 
    FROM homework h
    JOIN students s ON h.student_id = s.student_id
    WHERE h.tutor_id = $tutor_id
";

// Add filters
if (!empty($studentFilter)) {
    $query .= " AND s.name = '$studentFilter'";
}

if (!empty($statusFilter)) {
    $query .= " AND h.status = '$statusFilter'";
}

// Add sorting and pagination
$query .= " ORDER BY h.due_date ASC LIMIT $recordsPerPage OFFSET $offset";

// Execute query
$result = mysqli_query($conn, $query);
$homeworks = [];
while ($row = mysqli_fetch_assoc($result)) {
    $homeworks[] = $row;
}

// Filtering options
$studentFilter = isset($_GET['student']) ? mysqli_real_escape_string($conn, $_GET['student']) : '';
$statusFilter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

// Handle homework assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_homework'])) {
    // Sanitize inputs
    $student_name = mysqli_real_escape_string($conn, $_POST['student_name']);
    $homework_title = mysqli_real_escape_string($conn, $_POST['homework_title']);
    $homework_description = mysqli_real_escape_string($conn, $_POST['homework_description']);
    $due_date = mysqli_real_escape_string($conn, $_POST['due_date']);
    $topic = isset($_POST['topic']) ? mysqli_real_escape_string($conn, $_POST['topic']) : null;

    // Find student ID
    $student_query = "SELECT student_id FROM students WHERE name = '$student_name'";
    $student_result = mysqli_query($conn, $student_query);
    $student = mysqli_fetch_assoc($student_result);

    if (!$student) {
        $error = "Student not found";
    } else {
        // File upload handling
        $file_path = null;
        if (!empty($_FILES['homework_file']['name'])) {
            $upload_dir = 'uploads/homework/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_name = uniqid() . '_' . basename($_FILES['homework_file']['name']);
            $file_path = $upload_dir . $file_name;
            move_uploaded_file($_FILES['homework_file']['tmp_name'], $file_path);
        }

        // Insert homework
        $insert_query = "
            INSERT INTO homework 
            (title, description, due_date, student_id, tutor_id, topic, file_path, status) 
            VALUES 
            ('$homework_title', '$homework_description', '$due_date', 
             {$student['student_id']}, $tutor_id, " . 
            ($topic ? "'$topic'" : "NULL") . 
            ", " . 
            ($file_path ? "'$file_path'" : "NULL") . 
            ", 'Assigned')
        ";

        if (mysqli_query($conn, $insert_query)) {
            header("Location: homework.php?success=1");
            exit;
        } else {
            $error = "Error adding homework: " . mysqli_error($conn);
        }
    }
}
function handleStudentFileSubmission($homework_id, $file) {
    global $conn;

    // Check file upload
    if (!empty($file['name'])) {
        $upload_dir = 'uploads/submissions/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Generate unique filename
        $file_name = uniqid() . '_' . basename($file['name']);
        $file_path = $upload_dir . $file_name;

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            // Update homework record with submission path and status
            $update_query = "
                UPDATE homework 
                SET submitted_file_path = '" . mysqli_real_escape_string($conn, $file_path) . "', 
                    status = 'Submitted',
                    submission_date = NOW()
                WHERE homework_id = " . intval($homework_id);
            
            return mysqli_query($conn, $update_query);
        }
    }
    return false;
}

// Handle homework status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $homework_id = mysqli_real_escape_string($conn, $_POST['homework_id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $feedback = isset($_POST['feedback']) ? mysqli_real_escape_string($conn, $_POST['feedback']) : '';
    $grade = isset($_POST['grade']) ? mysqli_real_escape_string($conn, $_POST['grade']) : '';

    $update_query = "
        UPDATE homework 
        SET status = '$status', 
            feedback = " . ($feedback ? "'$feedback'" : "NULL") . ", 
            grade = " . ($grade ? "'$grade'" : "NULL") . "
        WHERE homework_id = $homework_id AND tutor_id = $tutor_id
    ";

    if (mysqli_query($conn, $update_query)) {
        header("Location: homework.php?success=2");
        exit;
    } else {
        $error = "Error updating homework: " . mysqli_error($conn);
    }
}

// Fetch students for dropdown
$students_query = "SELECT name FROM students";
$students_result = mysqli_query($conn, $students_query);
$students = [];
while ($row = mysqli_fetch_assoc($students_result)) {
    $students[] = $row['name'];
}

// Build homework query with filters
$query = "
    SELECT h.*, s.name as student_name, 
    CASE 
        WHEN h.submitted_file_path IS NOT NULL THEN h.submitted_file_path 
        ELSE NULL 
    END as student_submission_file
    FROM homework h
    JOIN students s ON h.student_id = s.student_id
    WHERE h.tutor_id = $tutor_id
";

if (!empty($studentFilter)) {
    $query .= " AND s.name = '$studentFilter'";
}

if (!empty($statusFilter)) {
    $query .= " AND h.status = '$statusFilter'";
}

// Count total records
$count_query = str_replace("SELECT h.*, s.name as student_name", "SELECT COUNT(*)", $query);
$count_result = mysqli_query($conn, $count_query);
$total_records = mysqli_fetch_assoc($count_result)['COUNT(*)'];
$total_pages = ceil($total_records / $recordsPerPage);

// Add pagination
$query .= " ORDER BY h.due_date ASC LIMIT $recordsPerPage OFFSET $offset";

// Execute final query
$result = mysqli_query($conn, $query);
$homeworks = [];
while ($row = mysqli_fetch_assoc($result)) {
    $homeworks[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Homework Management</title>
    <link rel="stylesheet" href="homework.css">
</head>
<body>

    <div class="container">
    <div style="display: flex;">
    <div class="homework-assign">
 

    <a href="tutor-dashboard.php" class="back-button">Back to Dashboard</a>
    <style>
        /* Back to Dashboard Button */
.back-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    text-decoration: none;
    padding: 0.625rem 1.25rem;
    background-color: #44403c;
    color: #fafaf9;
    border: none;
    border-radius: 0.375rem;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.back-button:hover {
    background-color: #57534e;
}

.back-button::before {
    content: '';
    display: inline-block;
    width: 1.25rem;
    height: 1.25rem;
    background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-arrow-left"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>');
    background-size: contain;
    background-repeat: no-repeat;
    margin-right: 0.5rem;
}

.back-button:active {
    transform: scale(0.98);
    background-color: #3f3f46;
}
    </style>
</div>

<div class="homework-list">

            <h2>Assign Homework</h2>
            
            <?php if (isset($error)): ?>
                <p style="color: red;"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="assign_homework" value="1">
                
                <div>
                    <label>Student Name:</label>
                    <select name="student_name" required>
                        <option value="">Select Student</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?= htmlspecialchars($student) ?>">
                                <?= htmlspecialchars($student) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label>Homework Title:</label>
                    <input type="text" name="homework_title" required>
                </div>
                
                <div>
                    <label>Description:</label>
                    <textarea name="homework_description" required></textarea>
                </div>
                
                <div>
                    <label>Topic:</label>
                    <input type="text" name="topic">
                </div>
                
                <div>
                    <label>Due Date:</label>
                    <input type="date" name="due_date" required>
                </div>
                
                <div>
                    <label>Homework File (Optional):</label>
                    <input type="file" name="homework_file">
                </div>
                
                <button type="submit">Assign Homework</button>
            </form>
        </div>

</div>
<div class="homework-list">

            <h2>Homework List</h2>
            
            <!-- Filters -->
            <div class="filters">
            <form method="GET">
                <select name="student">
                    <option value="">All Students</option>
                    <?php foreach ($students as $student): ?>
                        <option value="<?= htmlspecialchars($student) ?>"
                            <?= $studentFilter === $student ? 'selected' : '' ?>>
                            <?= htmlspecialchars($student) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <select name="status">
                    <option value="">All Statuses</option>
                    <option value="Assigned" <?= $statusFilter === 'Assigned' ? 'selected' : '' ?>>Assigned</option>
                    <option value="Submitted" <?= $statusFilter === 'Submitted' ? 'selected' : '' ?>>Submitted</option>
                    <option value="Graded" <?= $statusFilter === 'Graded' ? 'selected' : '' ?>>Graded</option>
                </select>
                
                <button type="submit">Filter</button>
            </form>
                    </div>
            
            <?php if (empty($homeworks)): ?>
                <p>No homework found.</p>
            <?php else: ?>
                <?php foreach ($homeworks as $homework): ?>
                    <div class="homework-item">
                        <h3><?= htmlspecialchars($homework['title']) ?></h3>
                        <p>Student: <?= htmlspecialchars($homework['student_name']) ?></p>
                        <p>Status: <?= htmlspecialchars($homework['status']) ?></p>
                        <p>Due Date: <?= htmlspecialchars($homework['due_date']) ?></p>
                        
                        <?php if ($homework['student_submission_file']): ?>
    <a href="<?= htmlspecialchars($homework['student_submission_file']) ?>" class="file-link">
        View Student Submission
    </a>
<?php endif; ?>
                        
                        <?php if ($homework['status'] == 'Submitted'): ?>
                            <form method="POST">
                                <input type="hidden" name="update_status" value="1">
                                <input type="hidden" name="homework_id" value="<?= $homework['homework_id'] ?>">
                                
                                <div>
                                    <label>Grade:</label>
                                    <input type="text" name="grade" placeholder="Enter grade">
                                </div>
                                
                                <div>
                                    <label>Feedback:</label>
                                    <textarea name="feedback" placeholder="Enter feedback"></textarea>
                                </div>
                                
                                <select name="status">
                                    <option value="Graded">Graded</option>
                                    <option value="Submitted">Requires Revision</option>
                                </select>
                                
                                <button type="submit">Update Homework</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                
                <!-- Pagination -->
                <div class="pagination">
    <?php if ($total_pages > 1): ?>
        <!-- First Page -->
        <?php if ($page > 1): ?>
            <a href="?page=1&student=<?= urlencode($studentFilter) ?>&status=<?= urlencode($statusFilter) ?>">First</a>
        <?php endif; ?>

        <!-- Previous Page -->
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>&student=<?= urlencode($studentFilter) ?>&status=<?= urlencode($statusFilter) ?>">Previous</a>
        <?php endif; ?>

        <!-- Page Numbers -->
        <?php 
        $start = max(1, $page - 2);
        $end = min($total_pages, $page + 2);
        
        for ($i = $start; $i <= $end; $i++): ?>
            <a href="?page=<?= $i ?>&student=<?= urlencode($studentFilter) ?>&status=<?= urlencode($statusFilter) ?>"
               class="<?= $i == $page ? 'active' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>

        <!-- Next Page -->
        <?php if ($page < $total_pages): ?>
            <a href="?page=<?= $page + 1 ?>&student=<?= urlencode($studentFilter) ?>&status=<?= urlencode($statusFilter) ?>">Next</a>
        <?php endif; ?>

        <!-- Last Page -->
        <?php if ($page < $total_pages): ?>
            <a href="?page=<?= $total_pages ?>&student=<?= urlencode($studentFilter) ?>&status=<?= urlencode($statusFilter) ?>">Last</a>
        <?php endif; ?>
    <?php endif; ?>
</div>
            <?php endif; ?>
        </div>
    <!-- Homework list content -->
</div>
                
       
    </div>
    </div>
    
</body>
</html>
<?php
// Close the database connection
mysqli_close($conn);
?>