<?php
// Start session
session_start();

// Database connection
require_once 'db_connect.php';

$error = '';

// Check if user is already logged in
if (isset($_SESSION['student_id'])) {
    header("Location: student-dashboard.php");
    exit;
}

// Check if form submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Basic validation
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password";
    } else {
        // Check user credentials
        $stmt = $conn->prepare("SELECT student_id, name, password FROM students WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['student_id'] = $user['student_id'];
                $_SESSION['student_name'] = $user['name'];
                
                // Redirect to dashboard
                header("Location: student-dashboard.php");
                exit;
            } else {
                $error = "Invalid password";
            }
        } else {
            $error = "Email not found";
        }
    }
}

// For testing purposes only - auto login
if (isset($_GET['test'])) {
    $_SESSION['student_id'] = 1;  // Set to an existing student ID
    $_SESSION['student_name'] = "Test Student";
    header("Location: student-dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        
        .container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
            width: 100%;
            max-width: 400px;
        }
        
        h1 {
            margin-top: 0;
            color: #333;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        .btn {
            display: inline-block;
            background-color: #4285F4;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        
        .btn:hover {
            background-color: #3367D6;
        }
        
        .error {
            color: #F44336;
            margin-bottom: 15px;
        }
        
        .signup-link {
            text-align: center;
            margin-top: 15px;
        }
        
        .test-login {
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .test-login a {
            color: #4285F4;
            text-decoration: none;
        }
        
        .test-login a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Student Login</h1>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn">Log In</button>
        </form>
        
        <div class="signup-link">
            Don't have an account? <a href="student-signup.php">Sign Up</a>
        </div>
        
        <div class="test-login">
            <a href="?test=1">Quick Test Login</a>
            <p style="font-size: 12px; color: #777;">For testing the dashboard only</p>
        </div>
    </div>
</body>
</html>