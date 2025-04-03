<?php
require 'db_connect.php'; // Include your database connection

// Get the lesson ID from the URL
$lesson_id = isset($_GET['lesson_id']) ? intval($_GET['lesson_id']) : 0;

// Fetch lesson details from the database
$sql = "SELECT * FROM lessons WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $lesson_id);
$stmt->execute();
$result = $stmt->get_result();
$lesson = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lesson Recording</title>
</head>
<body>
    <h2>Lesson Recording</h2>

    <?php if ($lesson): ?>
        <p><strong>Student:</strong> <?php echo htmlspecialchars($lesson['student_name']); ?></p>
        <p><strong>Lesson Date:</strong> <?php echo htmlspecialchars($lesson['lesson_date']); ?></p>
        
        <?php if (!empty($lesson['video_url'])): ?>
            <p>Recorded Lesson:</p>
            <video width="640" height="360" controls>
                <source src="<?php echo htmlspecialchars($lesson['video_url']); ?>" type="video/webm">
                Your browser does not support the video tag.
            </video>
        <?php else: ?>
            <p>No recording available for this lesson.</p>
        <?php endif; ?>
    <?php else: ?>
        <p>Lesson not found.</p>
    <?php endif; ?>
</body>
</html>
