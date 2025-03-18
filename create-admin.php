<?php
session_start();
require_once 'db_connect.php';

// Check if admin is already logged in and has permissions
if(!isset($_SESSION['admin_id'])) {
    header("Location: admin-login.php");
    exit();
}

// Only allow super admins to create other admins
$admin_id = $_SESSION['admin_id'];
$check_sql = "SELECT is_super_admin FROM admins WHERE admin_id = ?";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin_data = $result->fetch_assoc();

if(!$admin_data || $admin_data['is_super_admin'] != 1) {
    header("Location: admin_dashboard.php?error=unauthorized");
    exit();
}

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Basic validation
    if(empty($username) || empty($password) || empty($confirm_password)) {
        $error_message = "All fields are required";
    } elseif($password !== $confirm_password) {
        $error_message = "Passwords do not match";
    } else {
        // Check if username already exists
        $check_sql = "SELECT admin_id FROM admins WHERE username = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            $error_message = "Username already exists";
        } else {
            // Hash the password
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            
            // Insert new admin
            $insert_sql = "INSERT INTO admins (username, password_hash, created_at, updated_at, is_super_admin) 
                          VALUES (?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, 0)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("ss", $username, $password_hash);
            
            if ($stmt->execute()) {
                $success_message = "New admin created successfully";
            } else {
                $error_message = "Error creating admin: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin - Bloom</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .success-message {
            color: green;
            background-color: #e7f3e8;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: A15px;
        }
        .error-message {
            color: red;
            background-color: #f8d7da;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .back-link {
            display: inline-block;
            margin-top: 15px;
            color: #666;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Create New Admin</h1>
        
        <?php if(!empty($success_message)): ?>
            <div class="success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if(!empty($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <form id="create-admin-form" method="POST" action="">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit" id="create-btn">Create Admin</button>
        </form>
        
        <a href="admin_dashboard.php" class="back-link">← Back to Dashboard</a>
    </div>
    
    <script>
    $(document).ready(function() {
        // Client-side form validation
        $("#create-admin-form").submit(function(e) {
            let username = $("#username").val().trim();
            let password = $("#password").val().trim();
            let confirm_password = $("#confirm_password").val().trim();
            
            if (username === "" || password === "" || confirm_password === "") {
                e.preventDefault();
                alert("Please fill in all fields");
                return false;
            }
            
            if (password !== confirm_password) {
                e.preventDefault();
                alert("Passwords do not match");
                return false;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                alert("Password must be at least 8 characters long");
                return false;
            }
            
            return true;
        });
    });
    </script>
</body>
</html>