<?php
session_start();
$conn = new mysqli("localhost", "root", "", "bloom");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$lesson_id = $_POST['lesson_id'];

// Update started_at time to now
$sql = "UPDATE lessons SET started_at = NOW(), session_status = 'Scheduled' WHERE id = $lesson_id";

if ($conn->query($sql) === TRUE) {
    echo "Lesson started successfully!";
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?>
