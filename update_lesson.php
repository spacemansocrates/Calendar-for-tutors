<?php
// Start session
session_start();

// Database connection
require_once 'db_connect.php';

// Authentication check
if (!isset($_SESSION['tutor_id'])) {
    header('Location: tutor-login.php');
    exit;
}

// Handle lesson update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_lesson'])) {
    $lessonId = $_POST['lesson_id'];
    $sessionStatus = $_POST['session_status'];
    $notes = $_POST['notes'];
    $tutorId = $_SESSION['tutor_id'];
    
    // Make sure the tutor owns this lesson
    $stmt = $conn->prepare("SELECT * FROM lessons WHERE id = ? AND tutor_id = ?");
    $stmt->bind_param("ii", $lessonId, $tutorId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header('Location: tutor-dashboard.php?error=unauthorized');
        exit;
    }
    
    // Update the lesson
    $stmt = $conn->prepare("UPDATE lessons SET session_status = ?, notes = ? WHERE id = ?");
    $stmt->bind_param("ssi", $sessionStatus, $notes, $lessonId);
    
    if ($stmt->execute()) {
        // Redirect back to dashboard
        header('Location: tutor-dashboard.php?success=updated');
        exit;
    } else {
        header('Location: tutor-dashboard.php?error=update_failed');
        exit;
    }
} else {
    // Invalid request
    header('Location: tutor-dashboard.php');
    exit;
}
?>