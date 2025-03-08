<?php
require_once 'db_connect.php';

// Check if a form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_topics'])) {
    // Process topic updates
    $student_id = $_POST['student_id'];
    $tutor_id = $_SESSION['tutor_id'];
    
    // Get all topic IDs from the form
    foreach ($_POST['topic_status'] as $topic_id => $status) {
        // Check if a record already exists
        $check_query = "SELECT id FROM student_topic_progress WHERE student_id = ? AND topic_id = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("ii", $student_id, $topic_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $is_completed = ($status == 'completed') ? 1 : 0;
        $completed_date = ($status == 'completed') ? date('Y-m-d H:i:s') : NULL;
        
        if ($result->num_rows > 0) {
            // Update existing record
            $update_query = "UPDATE student_topic_progress 
                            SET is_completed = ?, 
                                completed_date = ?, 
                                tutor_id = ?,
                                updated_at = CURRENT_TIMESTAMP
                            WHERE student_id = ? AND topic_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("isiii", $is_completed, $completed_date, $tutor_id, $student_id, $topic_id);
        } else {
            // Insert new record
            $insert_query = "INSERT INTO student_topic_progress 
                            (student_id, topic_id, is_completed, completed_date, tutor_id) 
                            VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("iiisi", $student_id, $topic_id, $is_completed, $completed_date, $tutor_id);
        }
        
        $stmt->execute();
    }
    
    // Set success message
    $_SESSION['success_message'] = "Topic progress updated successfully!";
    
    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF'] . "?student_id=" . $student_id);
    exit;
}

// Get student ID from URL
$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;

// Get student information
$student_query = "SELECT name FROM students WHERE student_id = ?";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student_result = $stmt->get_result();

if ($student_result->num_rows === 0) {
    echo "<div class='error-message'>Student not found.</div>";
    exit;
}

$student = $student_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Topic Progress - <?php echo htmlspecialchars($student['name']); ?></title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <style>/* Base styles and variables */
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

/* Global reset and base styles */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: var(--font-sans);
  background-color: var(--background);
  color: var(--foreground);
  line-height: 1.6;
  padding: 2rem;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
  background-color: var(--card);
  border-radius: var(--radius);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

/* Typography */
h1 {
  font-size: 1.875rem;
  font-weight: 600;
  margin-bottom: 1.5rem;
  color: var(--foreground);
  letter-spacing: -0.025em;
}

h2 {
  font-size: 1.25rem;
  font-weight: 600;
  margin: 1.5rem 0 1rem;
  color: var(--foreground);
  letter-spacing: -0.025em;
}

p {
  margin-bottom: 1rem;
  color: var(--muted);
}

/* Buttons */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: var(--radius);
  font-weight: 500;
  font-size: 0.875rem;
  padding: 0.5rem 1rem;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
  border: none;
}

.btn-primary {
  background-color: var(--primary);
  color: var(--primary-foreground);
}

.btn-primary:hover {
  background-color: #7c3aed;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.btn-secondary {
  background-color: var(--secondary);
  color: var(--secondary-foreground);
  margin-bottom: 1rem;
}

.btn-secondary:hover {
  background-color: #e2e8f0;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

/* Tables */
.topics-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  margin-bottom: 2rem;
  border-radius: var(--radius);
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.topics-table thead {
  background-color: var(--secondary);
}

.topics-table th {
  text-align: left;
  padding: 0.75rem 1rem;
  font-weight: 500;
  color: var(--secondary-foreground);
  border-bottom: 1px solid var(--border);
  font-size: 0.875rem;
}

.topics-table td {
  padding: 0.75rem 1rem;
  border-bottom: 1px solid var(--border);
  font-size: 0.875rem;
  vertical-align: middle;
}

.topics-table tbody tr:last-child td {
  border-bottom: none;
}

.topics-table tbody tr:hover {
  background-color: var(--accent);
}

.checkbox-cell {
  text-align: center;
  width: 100px;
}

/* Subject sections */
.subject-section {
  margin-bottom: 2rem;
  padding: 1.5rem;
  background-color: white;
  border-radius: var(--radius);
  border: 1px solid var(--border);
  transition: all 0.2s ease;
}

.subject-section:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  transform: translateY(-2px);
}

.subject-section h2 {
  display: flex;
  align-items: center;
  margin-top: 0;
}

.subject-section h2::before {
  content: '';
  display: inline-block;
  width: 10px;
  height: 10px;
  background-color: var(--primary);
  border-radius: 50%;
  margin-right: 0.75rem;
}

/* Switch/Toggle styles */
.switch {
  position: relative;
  display: inline-block;
  width: 48px;
  height: 24px;
}

.switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #e2e8f0;
  transition: .4s;
}

.slider:before {
  position: absolute;
  content: "";
  height: 18px;
  width: 18px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: .4s;
}

input:checked + .slider {
  background-color: var(--success);
}

input:focus + .slider {
  box-shadow: 0 0 1px var(--success);
}

input:checked + .slider:before {
  transform: translateX(24px);
}

.slider.round {
  border-radius: 24px;
}

.slider.round:before {
  border-radius: 50%;
}

/* Form actions */
.form-actions {
  margin-top: 2rem;
  display: flex;
  justify-content: flex-end;
}

/* Messages */
.success-message, .error-message, .info-message {
  padding: 1rem;
  border-radius: var(--radius);
  margin-bottom: 1.5rem;
  font-size: 0.875rem;
  display: flex;
  align-items: center;
}

.success-message {
  background-color: rgba(16, 185, 129, 0.1);
  color: var(--success);
  border: 1px solid rgba(16, 185, 129, 0.2);
}

.success-message::before {
  content: "✓";
  font-weight: bold;
  margin-right: 0.5rem;
}

.error-message {
  background-color: rgba(239, 68, 68, 0.1);
  color: var(--destructive);
  border: 1px solid rgba(239, 68, 68, 0.2);
}

.error-message::before {
  content: "✕";
  font-weight: bold;
  margin-right: 0.5rem;
}

.info-message {
  background-color: rgba(59, 130, 246, 0.1);
  color: var(--info);
  border: 1px solid rgba(59, 130, 246, 0.2);
}

.info-message::before {
  content: "ℹ";
  font-weight: bold;
  margin-right: 0.5rem;
}

/* Creative additions */
/* Progress indicator at the top */
.progress-summary {
  display: flex;
  justify-content: space-between;
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--border);
}

.progress-card {
  background-color: var(--card);
  border-radius: var(--radius);
  padding: 1rem;
  flex: 1;
  margin-right: 1rem;
  border: 1px solid var(--border);
  text-align: center;
  transition: all 0.2s ease;
}

.progress-card:last-child {
  margin-right: 0;
}

.progress-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 12px rgba(0, 0, 0, 0.05);
}

.progress-number {
  font-size: 2rem;
  font-weight: 700;
  color: var(--primary);
  margin-bottom: 0.5rem;
  line-height: 1;
}

.progress-label {
  font-size: 0.75rem;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

/* Responsive design */
@media (max-width: 768px) {
  body {
    padding: 1rem;
  }
  
  .container {
    padding: 1rem;
  }
  
  .progress-summary {
    flex-direction: column;
  }
  
  .progress-card {
    margin-right: 0;
    margin-bottom: 1rem;
  }
  
  .topics-table, .topics-table thead, .topics-table tbody, .topics-table th, .topics-table td, .topics-table tr {
    display: block;
  }
  
  .topics-table thead tr {
    position: absolute;
    top: -9999px;
    left: -9999px;
  }
  
  .topics-table tr {
    border: 1px solid var(--border);
    margin-bottom: 1rem;
    border-radius: var(--radius);
  }
  
  .topics-table td {
    border: none;
    border-bottom: 1px solid var(--border);
    position: relative;
    padding-left: 50%;
  }
  
  .topics-table td:last-child {
    border-bottom: 0;
  }
  
  .topics-table td:before {
    position: absolute;
    top: 12px;
    left: 12px;
    width: 45%;
    padding-right: 10px;
    white-space: nowrap;
    font-weight: 500;
  }
  
  .topics-table td:nth-of-type(1):before { content: "Topic"; }
  .topics-table td:nth-of-type(2):before { content: "Description"; }
  .topics-table td:nth-of-type(3):before { content: "Status"; }
  .topics-table td:nth-of-type(4):before { content: "Completed Date"; }
}

/* Animations */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.container {
  animation: fadeIn 0.3s ease-out;
}

.subject-section {
  animation: fadeIn 0.3s ease-out;
  animation-fill-mode: both;
}

.subject-section:nth-child(2) { animation-delay: 0.1s; }
.subject-section:nth-child(3) { animation-delay: 0.2s; }
.subject-section:nth-child(4) { animation-delay: 0.3s; }
.subject-section:nth-child(5) { animation-delay: 0.4s; }</style>
<a href="tutor-dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    
    <div class="container">
        <h1>Topic Progress for <?php echo htmlspecialchars($student['name']); ?></h1>
        
        <?php
        // Display success message if any
        if (isset($_SESSION['success_message'])) {
            echo "<div class='success-message'>" . $_SESSION['success_message'] . "</div>";
            unset($_SESSION['success_message']);
        }
        
        // Get student's subjects
        $subjects_query = "SELECT ss.subject_id, s.subject_name, ss.year_level 
                          FROM student_subjects ss
                          JOIN subjects s ON ss.subject_id = s.subject_id
                          WHERE ss.student_id = ?
                          ORDER BY s.subject_name";
        $stmt = $conn->prepare($subjects_query);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $subjects_result = $stmt->get_result();
        
        if ($subjects_result->num_rows === 0) {
            echo "<div class='info-message'>This student is not enrolled in any subjects yet.</div>";
        } else {
            // Create form for updating topics
            echo "<form method='POST' action='" . $_SERVER['PHP_SELF'] . "'>";
            echo "<input type='hidden' name='student_id' value='{$student_id}'>";
            
            // Loop through each subject
            while ($subject = $subjects_result->fetch_assoc()) {
                $subject_id = $subject['subject_id'];
                $subject_name = $subject['subject_name'];
                $year_level = $subject['year_level'];
                
                echo "<div class='subject-section'>";
                echo "<h2>{$subject_name} - {$year_level}</h2>";
                
                // Get topics for this subject and year level
                $topics_query = "SELECT t.topic_id, t.topic_name, t.topic_description, 
                                     IFNULL(stp.is_completed, 0) as is_completed,
                                     stp.completed_date, stp.tutor_id
                                 FROM topics t
                                 LEFT JOIN student_topic_progress stp ON t.topic_id = stp.topic_id AND stp.student_id = ?
                                 WHERE t.subject_id = ? AND t.year_level = ?
                                 ORDER BY t.order_number";
                $stmt = $conn->prepare($topics_query);
                $stmt->bind_param("iis", $student_id, $subject_id, $year_level);
                $stmt->execute();
                $topics_result = $stmt->get_result();
                
                if ($topics_result->num_rows > 0) {
                    echo "<table class='topics-table'>";
                    echo "<thead><tr>";
                    echo "<th>Topic</th>";
                    echo "<th>Description</th>";
                    echo "<th>Status</th>";
                    echo "<th>Completed Date</th>";
                    echo "</tr></thead>";
                    echo "<tbody>";
                    
                    while ($topic = $topics_result->fetch_assoc()) {
                        $topic_id = $topic['topic_id'];
                        $is_checked = $topic['is_completed'] ? 'checked' : '';
                        $completed_date = $topic['completed_date'] ? date('M d, Y', strtotime($topic['completed_date'])) : '-';
                        
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($topic['topic_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($topic['topic_description']) . "</td>";
                        echo "<td class='checkbox-cell'>";
                        echo "<label class='switch'>";
                        echo "<input type='checkbox' name='topic_status[{$topic_id}]' value='completed' {$is_checked}>";
                        echo "<span class='slider round'></span>";
                        echo "</label>";
                        echo "</td>";
                        echo "<td>{$completed_date}</td>";
                        echo "</tr>";
                    }
                    
                    echo "</tbody></table>";
                } else {
                    echo "<p>No topics found for this subject and year level.</p>";
                }
                
                echo "</div>"; // End subject-section
            }
            
            echo "<div class='form-actions'>";
            echo "<button type='submit' name='update_topics' class='btn btn-primary'>Save Changes</button>";
            echo "</div>";
            echo "</form>";
        }
        ?>
    </div>
