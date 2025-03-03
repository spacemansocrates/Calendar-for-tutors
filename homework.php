<?php
// Start session for login functionality
session_start();

// Include database connection
require_once 'db_connect.php';

// Check if user is logged in as tutor
if (!isset($_SESSION['tutor_id']) || $_SESSION['user_type'] !== 'tutor') {
    header('Location: tutor-login.php');
    exit;
}

// Initialize variables
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$message = '';
$homework_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$tutor_id = $_SESSION['tutor_id'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_homework'])) {
        // Create new homework
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $due_date = mysqli_real_escape_string($conn, $_POST['due_date']);
        $student_ids = isset($_POST['student_ids']) ? $_POST['student_ids'] : [];
        
        // Handle file upload
        $file_path = '';
        if (isset($_FILES['homework_file']) && $_FILES['homework_file']['error'] == 0) {
            $upload_dir = 'uploads/homework/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = time() . '_' . basename($_FILES['homework_file']['name']);
            $target_file = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['homework_file']['tmp_name'], $target_file)) {
                $file_path = $target_file;
            }
        }
        
        // Insert homework record
        $query = "INSERT INTO homework (tutor_id, title, description, due_date, file_path, created_at) 
                  VALUES ($tutor_id, '$title', '$description', '$due_date', '$file_path', NOW())";
        
        if (mysqli_query($conn, $query)) {
            $new_homework_id = mysqli_insert_id($conn);
            
            // Assign homework to selected students
            if (!empty($student_ids)) {
                foreach ($student_ids as $student_id) {
                    $student_id = intval($student_id);
                    $assign_query = "INSERT INTO homework_assignments (homework_id, student_id, status, assigned_at) 
                                    VALUES ($new_homework_id, $student_id, 'assigned', NOW())";
                    mysqli_query($conn, $assign_query);
                }
            }
            
            $message = "Homework assigned successfully!";
            $action = 'list';
        } else {
            $message = "Error: " . mysqli_error($conn);
        }
    } elseif (isset($_POST['update_homework'])) {
        // Update existing homework
        $homework_id = intval($_POST['homework_id']);
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $due_date = mysqli_real_escape_string($conn, $_POST['due_date']);
        $student_ids = isset($_POST['student_ids']) ? $_POST['student_ids'] : [];
        
        // Verify the homework belongs to this tutor
        $verify_query = "SELECT * FROM homework WHERE homework_id = $homework_id AND tutor_id = $tutor_id";
        $verify_result = mysqli_query($conn, $verify_query);
        
        if (mysqli_num_rows($verify_result) > 0) {
            $query = "UPDATE homework SET 
                     title = '$title', 
                     description = '$description', 
                     due_date = '$due_date' 
                     WHERE homework_id = $homework_id AND tutor_id = $tutor_id";
            
            if (mysqli_query($conn, $query)) {
                // Clear existing assignments
                mysqli_query($conn, "DELETE FROM homework_assignments WHERE homework_id = $homework_id");
                
                // Add new assignments
                if (!empty($student_ids)) {
                    foreach ($student_ids as $student_id) {
                        $student_id = intval($student_id);
                        $assign_query = "INSERT INTO homework_assignments (homework_id, student_id, status, assigned_at) 
                                        VALUES ($homework_id, $student_id, 'assigned', NOW())";
                        mysqli_query($conn, $assign_query);
                    }
                }
                
                $message = "Homework updated successfully!";
                $action = 'list';
            } else {
                $message = "Error: " . mysqli_error($conn);
            }
        } else {
            $message = "You don't have permission to update this homework.";
        }
    } elseif (isset($_POST['grade_submission'])) {
        // Grade a homework submission
        $assignment_id = intval($_POST['assignment_id']);
        $feedback = mysqli_real_escape_string($conn, $_POST['feedback']);
        $grade = mysqli_real_escape_string($conn, $_POST['grade']);
        
        $query = "UPDATE homework_assignments SET 
                 feedback = '$feedback', 
                 grade = '$grade',
                 status = 'graded',
                 graded_at = NOW()
                 WHERE assignment_id = $assignment_id";
        
        if (mysqli_query($conn, $query)) {
            $message = "Submission graded successfully!";
            $action = 'submissions';
        } else {
            $message = "Error: " . mysqli_error($conn);
        }
    }
}

// Delete homework
if ($action === 'delete' && $homework_id > 0) {
    // Verify the homework belongs to this tutor
    $verify_query = "SELECT * FROM homework WHERE homework_id = $homework_id AND tutor_id = $tutor_id";
    $verify_result = mysqli_query($conn, $verify_query);
    
    if (mysqli_num_rows($verify_result) > 0) {
        // Delete assignments first
        mysqli_query($conn, "DELETE FROM homework_assignments WHERE homework_id = $homework_id");
        
        // Then delete the homework
        $query = "DELETE FROM homework WHERE homework_id = $homework_id AND tutor_id = $tutor_id";
        if (mysqli_query($conn, $query)) {
            $message = "Homework deleted successfully!";
        } else {
            $message = "Error: " . mysqli_error($conn);
        }
    } else {
        $message = "You don't have permission to delete this homework.";
    }
    $action = 'list';
}

// Get students that this tutor teaches
$students = [];
$students_query = "SELECT s.user_id, s.firstname, s.lastname 
                   FROM users s 
                   JOIN tutor_students ts ON s.user_id = ts.student_id 
                   WHERE ts.tutor_id = $tutor_id AND s.user_type = 'student'";
$students_result = mysqli_query($conn, $students_query);
if ($students_result) {
    while ($row = mysqli_fetch_assoc($students_result)) {
        $students[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutor Homework Management</title>
</head>
<body>
    <div class="container">
        <header>
            <h1>Tutor Homework Management</h1>
            <div class="user-info">
                Welcome, <?php echo htmlspecialchars($_SESSION['firstname'] . ' ' . $_SESSION['lastname']); ?> | 
                <a href="logout.php">Logout</a>
            </div>
            <nav>
                <ul>
                    <li><a href="?action=list">View All Homework</a></li>
                    <li><a href="?action=new">Assign New Homework</a></li>
                    <li><a href="?action=submissions">Review Submissions</a></li>
                    <li><a href="dashboard.php">Back to Dashboard</a></li>
                </ul>
            </nav>
        </header>

        <?php if (!empty($message)): ?>
            <div class="message">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <main>
            <?php if ($action === 'list'): ?>
                <!-- List all homework -->
                <section class="homework-list">
                    <h2>All Assigned Homework</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Due Date</th>
                                <th>Assigned To</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT h.*, COUNT(ha.assignment_id) as student_count 
                                      FROM homework h 
                                      LEFT JOIN homework_assignments ha ON h.homework_id = ha.homework_id
                                      WHERE h.tutor_id = $tutor_id
                                      GROUP BY h.homework_id
                                      ORDER BY h.due_date DESC";
                            $result = mysqli_query($conn, $query);
                            
                            if ($result && mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['due_date']) . "</td>";
                                    echo "<td>" . $row['student_count'] . " students</td>";
                                    
                                    // Get overall status
                                    $status_query = "SELECT 
                                                    SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) as assigned,
                                                    SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as submitted,
                                                    SUM(CASE WHEN status = 'graded' THEN 1 ELSE 0 END) as graded
                                                    FROM homework_assignments 
                                                    WHERE homework_id = " . $row['homework_id'];
                                    $status_result = mysqli_query($conn, $status_query);
                                    $status_data = mysqli_fetch_assoc($status_result);
                                    
                                    $status = "Active";
                                    if ($status_data['submitted'] > 0) {
                                        $status = $status_data['submitted'] . " submitted";
                                    }
                                    if ($status_data['graded'] > 0) {
                                        $status = $status_data['graded'] . " graded";
                                    }
                                    
                                    echo "<td>" . $status . "</td>";
                                    echo "<td class='actions'>";
                                    echo "<a href='?action=view&id=" . $row['homework_id'] . "'>View</a> | ";
                                    echo "<a href='?action=edit&id=" . $row['homework_id'] . "'>Edit</a> | ";
                                    echo "<a href='?action=delete&id=" . $row['homework_id'] . "' onclick='return confirm(\"Are you sure?\")'>Delete</a>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5' class='no-data'>No homework assignments found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </section>

            <?php elseif ($action === 'new'): ?>
                <!-- Create new homework form -->
                <section class="homework-form">
                    <h2>Assign New Homework</h2>
                    <form method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="title">Homework Title:</label>
                            <input type="text" name="title" id="title" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description:</label>
                            <textarea name="description" id="description" rows="5" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="due_date">Due Date:</label>
                            <input type="datetime-local" name="due_date" id="due_date" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="homework_file">Attachment (optional):</label>
                            <input type="file" name="homework_file" id="homework_file">
                        </div>
                        
                        <div class="form-group">
                            <label>Assign to Students:</label>
                            <?php if (!empty($students)): ?>
                                <div class="student-list">
                                    <?php foreach ($students as $student): ?>
                                        <div class="student-checkbox">
                                            <input type="checkbox" name="student_ids[]" id="student_<?php echo $student['user_id']; ?>" value="<?php echo $student['user_id']; ?>">
                                            <label for="student_<?php echo $student['user_id']; ?>"><?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" id="select-all-students">Select All Students</button>
                            <?php else: ?>
                                <p>No students found. <a href="manage_students.php">Add students</a> to your roster first.</p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" name="create_homework" <?php if (empty($students)) echo 'disabled'; ?>>Assign Homework</button>
                        </div>
                    </form>
                </section>

            <?php elseif ($action === 'edit' && $homework_id > 0): ?>
                <!-- Edit homework form -->
                <?php
                $query = "SELECT * FROM homework WHERE homework_id = $homework_id AND tutor_id = $tutor_id";
                $result = mysqli_query($conn, $query);
                $homework = $result ? mysqli_fetch_assoc($result) : null;
                
                if ($homework):
                    // Get currently assigned students
                    $assigned_query = "SELECT student_id FROM homework_assignments WHERE homework_id = $homework_id";
                    $assigned_result = mysqli_query($conn, $assigned_query);
                    $assigned_students = [];
                    
                    if ($assigned_result) {
                        while ($row = mysqli_fetch_assoc($assigned_result)) {
                            $assigned_students[] = $row['student_id'];
                        }
                    }
                ?>
                <section class="homework-form">
                    <h2>Edit Homework</h2>
                    <form method="post">
                        <input type="hidden" name="homework_id" value="<?php echo $homework_id; ?>">
                        
                        <div class="form-group">
                            <label for="title">Homework Title:</label>
                            <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($homework['title']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description:</label>
                            <textarea name="description" id="description" rows="5" required><?php echo htmlspecialchars($homework['description']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="due_date">Due Date:</label>
                            <input type="datetime-local" name="due_date" id="due_date" value="<?php echo str_replace(' ', 'T', $homework['due_date']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Assign to Students:</label>
                            <?php if (!empty($students)): ?>
                                <div class="student-list">
                                    <?php foreach ($students as $student): ?>
                                        <div class="student-checkbox">
                                            <input type="checkbox" name="student_ids[]" id="student_<?php echo $student['user_id']; ?>" value="<?php echo $student['user_id']; ?>"
                                                <?php if (in_array($student['user_id'], $assigned_students)) echo 'checked'; ?>>
                                            <label for="student_<?php echo $student['user_id']; ?>"><?php echo htmlspecialchars($student['firstname'] . ' ' . $student['lastname']); ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" id="select-all-students">Select All Students</button>
                            <?php else: ?>
                                <p>No students found.</p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" name="update_homework">Update Homework</button>
                        </div>
                    </form>
                </section>
                <?php else: ?>
                <p>Homework not found or you don't have permission to edit it.</p>
                <?php endif; ?>

            <?php elseif ($action === 'view' && $homework_id > 0): ?>
                <!-- View homework details -->
                <?php
                $query = "SELECT * FROM homework WHERE homework_id = $homework_id AND tutor_id = $tutor_id";
                $result = mysqli_query($conn, $query);
                $homework = $result ? mysqli_fetch_assoc($result) : null;
                
                if ($homework):
                ?>
                <section class="homework-details">
                    <h2><?php echo htmlspecialchars($homework['title']); ?></h2>
                    
                    <div class="details-grid">
                        <div class="detail-row">
                            <span class="label">Due Date:</span>
                            <span class="value"><?php echo htmlspecialchars($homework['due_date']); ?></span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="label">Description:</span>
                            <div class="value description">
                                <?php echo nl2br(htmlspecialchars($homework['description'])); ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($homework['file_path'])): ?>
                        <div class="detail-row">
                            <span class="label">Attachment:</span>
                            <span class="value">
                                <a href="<?php echo htmlspecialchars($homework['file_path']); ?>" download>Download</a>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <h3>Assigned Students</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Status</th>
                                <th>Submission Date</th>
                                <th>Grade</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $assignments_query = "SELECT ha.*, u.firstname, u.lastname 
                                                FROM homework_assignments ha
                                                JOIN users u ON ha.student_id = u.user_id
                                                WHERE ha.homework_id = $homework_id";
                            $assignments_result = mysqli_query($conn, $assignments_query);
                            
                            if ($assignments_result && mysqli_num_rows($assignments_result) > 0) {
                                while ($assignment = mysqli_fetch_assoc($assignments_result)) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($assignment['firstname'] . ' ' . $assignment['lastname']) . "</td>";
                                    echo "<td>" . htmlspecialchars($assignment['status']) . "</td>";
                                    echo "<td>" . (($assignment['submitted_at']) ? htmlspecialchars($assignment['submitted_at']) : "Not submitted") . "</td>";
                                    echo "<td>" . (($assignment['grade']) ? htmlspecialchars($assignment['grade']) : "Not graded") . "</td>";
                                    echo "<td class='actions'>";
                                    
                                    if ($assignment['status'] === 'submitted') {
                                        echo "<a href='?action=grade_submission&id=" . $assignment['assignment_id'] . "'>Grade</a>";
                                    } elseif ($assignment['status'] === 'graded') {
                                        echo "<a href='?action=view_submission&id=" . $assignment['assignment_id'] . "'>View Submission</a>";
                                    } else {
                                        echo "Waiting for submission";
                                    }
                                    
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5' class='no-data'>No students assigned to this homework</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                    
                    <div class="action-buttons">
                        <a href="?action=edit&id=<?php echo $homework_id; ?>" class="button">Edit</a>
                        <a href="?action=list" class="button">Back to List</a>
                    </div>
                </section>
                <?php else: ?>
                <p>Homework not found or you don't have permission to view it.</p>
                <?php endif; ?>

            <?php elseif ($action === 'grade_submission'): ?>
                <!-- Grade homework submission form -->
                <?php
                $assignment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
                $query = "SELECT ha.*, h.title, u.firstname, u.lastname 
                          FROM homework_assignments ha
                          JOIN homework h ON ha.homework_id = h.homework_id
                          JOIN users u ON ha.student_id = u.user_id
                          WHERE ha.assignment_id = $assignment_id AND h.tutor_id = $tutor_id";
                $result = mysqli_query($conn, $query);
                $submission = $result ? mysqli_fetch_assoc($result) : null;
                
                if ($submission && $submission['status'] === 'submitted'):
                ?>
                <section class="homework-grading">
                    <h2>Grade Submission</h2>
                    <h3>Homework: <?php echo htmlspecialchars($submission['title']); ?></h3>
                    <p>Student: <?php echo htmlspecialchars($submission['firstname'] . ' ' . $submission['lastname']); ?></p>
                    
                    <div class="submission-preview">
                        <h4>Student Submission</h4>
                        <?php if (!empty($submission['submission_notes'])): ?>
                        <div class="submission-notes">
                            <h5>Student Notes:</h5>
                            <p><?php echo nl2br(htmlspecialchars($submission['submission_notes'])); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($submission['submission_file'])): ?>
                        <p>
                            <a href="<?php echo htmlspecialchars($submission['submission_file']); ?>" download class="button">Download Submission File</a>
                        </p>
                        <?php endif; ?>
                    </div>
                    
                    <form method="post">
                        <input type="hidden" name="assignment_id" value="<?php echo $assignment_id; ?>">
                        
                        <div class="form-group">
                            <label for="feedback">Feedback:</label>
                            <textarea name="feedback" id="feedback" rows="5" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="grade">Grade:</label>
                            <input type="text" name="grade" id="grade" required>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" name="grade_submission">Submit Grade</button>
                        </div>
                    </form>
                </section>
                <?php else: ?>
                <p>Submission not found or already graded.</p>
                <?php endif; ?>

            <?php elseif ($action === 'view_submission'): ?>
                <!-- View graded submission details -->
                <?php
                $assignment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
                $query = "SELECT ha.*, h.title, u.firstname, u.lastname 
                          FROM homework_assignments ha
                          JOIN homework h ON ha.homework_id = h.homework_id
                          JOIN users u ON ha.student_id = u.user_id
                          WHERE ha.assignment_id = $assignment_id AND h.tutor_id = $tutor_id";
                $result = mysqli_query($conn, $query);
                $submission = $result ? mysqli_fetch_assoc($result) : null;
                
                if ($submission):
                ?>
                <section class="submission-details">
                    <h2>Submission Details</h2>
                    <h3>Homework: <?php echo htmlspecialchars($submission['title']); ?></h3>
                    <p>Student: <?php echo htmlspecialchars($submission['firstname'] . ' ' . $submission['lastname']); ?></p>
                    
                    <div class="details-grid">
                        <div class="detail-row">
                            <span class="label">Status:</span>
                            <span class="value"><?php echo htmlspecialchars($submission['status']); ?></span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="label">Submitted:</span>
                            <span class="value"><?php echo htmlspecialchars($submission['submitted_at']); ?></span>
                        </div>
                        
                        <?php if (!empty($submission['submission_notes'])): ?>
                        <div class="detail-row">
                            <span class="label">Notes:</span>
                            <div class="value">
                                <?php echo nl2br(htmlspecialchars($submission['submission_notes'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($submission['submission_file'])): ?>
                        <div class="detail-row">
                            <span class="label">File:</span>
                            <span class="value">
                                <a href="<?php echo htmlspecialchars($submission['submission_file']); ?>" download>Download Submission</a>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($submission['status'] === 'graded'): ?>
                        <div class="detail-row">
                            <span class="label">Grade:</span>
                            <span class="value grade"><?php echo htmlspecialchars($submission['grade']); ?></span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="label">Feedback:</span>
                            <div class="value feedback">
                                <?php echo nl2br(htmlspecialchars($submission['feedback'])); ?>
                            </div>
                        </div>
                        
                        <div class="detail-row">
                            <span class="label">Graded on:</span>
                            <span class="value"><?php echo htmlspecialchars($submission['graded_at']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="action-buttons">
                        <?php if ($submission['status'] === 'submitted'): ?>
                        <a href="?action=grade_submission&id=<?php echo $assignment_id; ?>" class="button">Grade Submission</a>
                        <?php elseif ($submission['status'] === 'graded'): ?>
                        <a href="?action=edit_grade&id=<?php echo $assignment_id; ?>" class="button">Edit Grade</a>
                        <?php endif; ?>
                        <a href="?action=view&id=<?php echo $submission['homework_id']; ?>" class="button">Back to Homework</a>
                    </div>
                </section>
                <?php else: ?>
                <p>Submission not found or you don't have permission to view it.</p>
                <?php endif; ?>

            <?php elseif ($action === 'submissions'): ?>
                <!-- View all submitted homework -->
                <section class="submissions-list">
  <h2>Homework Submissions</h2>
  <table>
    <thead>
      <tr>
        <th>Homework</th>
        <th>Student</th>
        <th>Submitted</th>
        <th>Status</th>
        <th>Grade</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php
    $query = "SELECT ha.*, h.title, u.firstname, u.lastname FROM homework_assignments ha 
              JOIN homework h ON ha.homework_id = h.homework_id 
              JOIN users u ON ha.student_id = u.user_id 
              WHERE h.tutor_id = $tutor_id AND ha.status IN ('submitted', 'graded') 
              ORDER BY ha.status ASC, ha.submitted_at DESC";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
      while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
        echo "<td>" . htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) . "</td>";
        echo "<td>" . htmlspecialchars($row['submitted_at']) . "</td>";
        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
        echo "<td>" . (($row['grade']) ? htmlspecialchars($row['grade']) : "Not graded") . "</td>";
        echo "<td class='actions'>";
        if ($row['status'] === 'submitted') {
          echo "<a href='?action=grade_submission&id=" . $row['assignment_id'] . "'>Grade</a>";
        } else {
          echo "<a href='?action=view_submission&id=" . $row['assignment_id'] . "'>View</a>";
        }
        echo "</td>";
        echo "</tr>";
      }
    } else {
      echo "<tr><td colspan='6' class='no-data'>No submissions found.</td></tr>";
    }
    ?>
    </tbody>
  </table>
</section>
            <?php endif; ?>
        </main>
    </div>