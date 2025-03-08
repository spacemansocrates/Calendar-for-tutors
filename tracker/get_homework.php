<?php
// Start session
session_start();

// Database connection
require_once 'db_connect.php';

// Authentication check
if (!isset($_SESSION['tutor_id'])) {
    // Return error as JSON
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Get current tutor information
$tutorId = $_SESSION['tutor_id'];

// Check if homework ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid homework ID']);
    exit;
}

$homeworkId = intval($_GET['id']);

// Get homework details
$stmt = $conn->prepare("
    SELECT * FROM homework 
    WHERE id = ? AND tutor_id = ?
");
$stmt->bind_param("ii", $homeworkId, $tutorId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Homework not found or access denied']);
    exit;
}

// Return homework details as JSON
$homework = $result->fetch_assoc();
header('Content-Type: application/json');
echo json_encode($homework);
?>