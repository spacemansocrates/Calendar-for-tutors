<?php
session_start();
$conn = new mysqli("localhost", "root", "", "bloom");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get lesson ID and sanitize
$lesson_id = isset($_POST['lesson_id']) ? (int)$_POST['lesson_id'] : 0;

if ($lesson_id <= 0) {
    echo "Invalid lesson ID";
    exit;
}

// Use prepared statement to prevent SQL injection
$stmt = $conn->prepare("UPDATE lessons SET started_at = NOW(), session_status = 'Scheduled' WHERE id = ?");
$stmt->bind_param("i", $lesson_id);

if ($stmt->execute()) {
    echo "Lesson started successfully!";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>