<?php
// Database connection parameters
$db_host = 'localhost';
$db_user = 'root';
$db_pass = ''; // In production, use environment variables
$db_name = 'bloom';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>