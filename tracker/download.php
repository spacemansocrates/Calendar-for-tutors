
<?php
session_start();

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    die("Unauthorized access");
}

// Validate and sanitize file path
$file_path = isset($_GET['file']) ? $_GET['file'] : null;

if (!$file_path) {
    die("No file specified");
}

// Additional security checks
$file_path = realpath($file_path);
$allowed_dir = realpath('uploads/homework'); // Adjust this to your actual upload directory

// Ensure the file is within the allowed directory
if (strpos($file_path, $allowed_dir) !== 0) {
    die("Invalid file path");
}

// Check if file exists
if (!file_exists($file_path)) {
    die("File not found");
}

// Get file information
$file_name = basename($file_path);
$file_size = filesize($file_path);

// Send download headers
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $file_name . '"');
header('Content-Length: ' . $file_size);
header('Pragma: no-cache');
header('Expires: 0');

// Output file contents
readfile($file_path);
exit();

?>