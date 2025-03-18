<?php
// Start the session at the very beginning of the file
session_start();

require_once 'db_connect.php';

// Check if tutor is logged in
if (!isset($_SESSION['tutor_id'])) {
    // If not logged in, redirect to login page or set a default/test value
    // For testing purposes:
    $tutor_id = 1; // Use a default tutor ID for testing
} else {
    $tutor_id = $_SESSION['tutor_id'];
}

// Get all unique students this tutor has lessons with
$students_query = "SELECT DISTINCT s.student_id, s.name 
                  FROM students s
                  JOIN lessons l ON s.name = l.student_name
                  WHERE l.tutor_id = ?
                  ORDER BY s.name";
$stmt = $conn->prepare($students_query);
$stmt->bind_param("i", $tutor_id);
$stmt->execute();
$students_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Student for Topic Progress</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <style>
        /* Base styles and variables */
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

h3 {
  font-size: 1.125rem;
  font-weight: 600;
  margin-bottom: 0.75rem;
  color: var(--foreground);
  letter-spacing: -0.025em;
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
}

.btn-secondary:hover {
  background-color: #e2e8f0;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

/* Form actions */
.form-actions {
  margin-top: 2rem;
  display: flex;
  justify-content: flex-end;
}

/* Messages */
.info-message {
  padding: 1rem;
  border-radius: var(--radius);
  margin-bottom: 1.5rem;
  font-size: 0.875rem;
  display: flex;
  align-items: center;
  background-color: rgba(59, 130, 246, 0.1);
  color: var(--info);
  border: 1px solid rgba(59, 130, 246, 0.2);
}

.info-message::before {
  content: "ℹ";
  font-weight: bold;
  margin-right: 0.5rem;
}

/* Student Selection Specific Styles */
.student-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
  margin-top: 1.5rem;
}

.student-card {
  background-color: var(--card);
  border-radius: var(--radius);
  padding: 1.5rem;
  border: 1px solid var(--border);
  transition: all 0.2s ease;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  position: relative;
  overflow: hidden;
}

.student-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 4px;
  background-color: var(--primary);
  opacity: 0;
  transition: opacity 0.2s ease;
}

.student-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 15px rgba(0, 0, 0, 0.05);
}

.student-card:hover::before {
  opacity: 1;
}

.student-card h3 {
  font-weight: 600;
  margin-bottom: 1rem;
}

.student-card .btn {
  width: 100%;
}

/* Animations */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.container {
  animation: fadeIn 0.3s ease-out;
}

.student-card {
  animation: fadeIn 0.3s ease-out;
  animation-fill-mode: both;
}

.student-card:nth-child(2) { animation-delay: 0.1s; }
.student-card:nth-child(3) { animation-delay: 0.15s; }
.student-card:nth-child(4) { animation-delay: 0.2s; }
.student-card:nth-child(5) { animation-delay: 0.25s; }
.student-card:nth-child(6) { animation-delay: 0.3s; }
.student-card:nth-child(7) { animation-delay: 0.35s; }
.student-card:nth-child(8) { animation-delay: 0.4s; }

/* Responsive design */
@media (max-width: 768px) {
  body {
    padding: 1rem;
  }
  
  .container {
    padding: 1.5rem 1rem;
  }
  
  .student-list {
    grid-template-columns: 1fr;
  }
}

/* Empty state styling */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 1rem;
  text-align: center;
}

.empty-state-icon {
  font-size: 3rem;
  color: var(--muted);
  margin-bottom: 1rem;
}

.empty-state-message {
  color: var(--muted);
  font-size: 1rem;
  max-width: 400px;
  margin: 0 auto 1.5rem;
}
    </style>
    <div class="container">
        <h1>Select Student for Topic Progress</h1>
        
        <?php if ($students_result->num_rows === 0): ?>
            <div class="info-message">You don't have any students assigned to you yet.</div>
            <div class="form-actions">
                <a href="tutor-dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        <?php else: ?>
            <div class="student-list">
                <?php while ($student = $students_result->fetch_assoc()): ?>
                    <div class="student-card">
                        <h3><?php echo htmlspecialchars($student['name']); ?></h3>
                        <a href="topics.php?student_id=<?php echo $student['student_id']; ?>" class="btn btn-primary">View Topic Progress</a>
                    </div>
                <?php endwhile; ?>
            </div>
            <div class="form-actions">
                <a href="tutor-dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>