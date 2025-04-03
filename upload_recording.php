<?php
require 'db_connect.php'; // Include your DB connection

if ($_FILES['video']['error'] === UPLOAD_ERR_OK) {
    $lesson_id = $_POST['lesson_id']; 
    $uploadDir = "recordings/";
    $fileName = "lesson_" . $lesson_id . "_" . time() . ".webm";
    $filePath = $uploadDir . $fileName;
    $fileUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/' . $filePath;

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    if (move_uploaded_file($_FILES['video']['tmp_name'], $filePath)) {
        // Update the database with video URL
        $stmt = $conn->prepare("UPDATE lessons SET video_url = ? WHERE id = ?");
        $stmt->bind_param("si", $fileUrl, $lesson_id);
        $stmt->execute();
        $stmt->close();

        echo $fileUrl; // Return the full URL
    } else {
        echo "Failed to save recording.";
    }
} else {
    echo "Upload error: " . $_FILES['video']['error'];
}
?>