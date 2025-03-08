<?php
// Initialize the session
session_start();

// Check if already logged in
if (isset($_SESSION['admin_id'])) {
    header("Location: user-management.php");
    exit;
}

// Database connection
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "bloom";

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = "";

// Process login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Input validation
    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        // Prepare SQL statement to prevent SQL injection
        $sql = "SELECT admin_id, username, password FROM admins WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            // Verify the password
            if (password_verify($password, $row['password'])) {
                // Password is correct, so start a new session
                session_start();
                
                // Store data in session variables
                $_SESSION['admin_id'] = $row['admin_id'];
                $_SESSION['admin_username'] = $row['username'];
                
                // Update last login timestamp
                $update_sql = "UPDATE admins SET last_login = NOW() WHERE admin_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("i", $row['admin_id']);
                $update_stmt->execute();
                $update_stmt->close();
                
                // Log the successful login
                $log_sql = "INSERT INTO admin_logs (admin_id, action, ip_address) VALUES (?, 'login', ?)";
                $log_stmt = $conn->prepare($log_sql);
                $ip = $_SERVER['REMOTE_ADDR'];
                $log_stmt->bind_param("is", $row['admin_id'], $ip);
                $log_stmt->execute();
                $log_stmt->close();
                
                // Redirect to user management page
                header("Location: user-management.php");
                exit;
            } else {
                // Password is not valid
                $error = "Invalid username or password.";
                
                // Log the failed attempt
                $log_sql = "INSERT INTO login_attempts (username, ip_address, success) VALUES (?, ?, 0)";
                $log_stmt = $conn->prepare($log_sql);
                $ip = $_SERVER['REMOTE_ADDR'];
                $log_stmt->bind_param("ss", $username, $ip);
                $log_stmt->execute();
                $log_stmt->close();
            }
        } else {
            // Username doesn't exist
            $error = "Invalid username or password.";
            
            // Log the failed attempt
            $log_sql = "INSERT INTO login_attempts (username, ip_address, success) VALUES (?, ?, 0)";
            $log_stmt = $conn->prepare($log_sql);
            $ip = $_SERVER['REMOTE_ADDR'];
            $log_stmt->bind_param("ss", $username, $ip);
            $log_stmt->execute();
            $log_stmt->close();
        }
        
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bloom Admin Login</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .login-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            padding: 2rem;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo h1 {
            color: #3498db;
            font-size: 2rem;
        }
        
        .logo p {
            color: #7f8c8d;
            font-size: 1rem;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 0.75rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        
        form div {
            margin-bottom: 1.5rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e1e1e1;
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: #3498db;
            outline: none;
        }
        
        button {
            width: 100%;
            padding: 0.75rem;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s;
        }
        
        button:hover {
            background-color: #2980b9;
        }
        
        .forgot-password {
            text-align: center;
            margin-top: 1rem;
        }
        
        .forgot-password a {
            color: #7f8c8d;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s;
        }
        
        .forgot-password a:hover {
            color: #3498db;
        }
        
        @media (max-width: 480px) {
            .login-container {
                max-width: 90%;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>Bloom</h1>
            <p>Admin Portal</p>
        </div>
        
        <?php if(!empty($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div>
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div>
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit">Login</button>
        </form>
        
        <div class="forgot-password">
            <a href="admin_forgot_password.php">Forgot password?</a>
        </div>
    </div>

    <script>
        // Simple client-side validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (username === '' || password === '') {
                e.preventDefault();
                alert('Please enter both username and password.');
            }
        });
        
        // Auto-focus on the username field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });
    </script>
</body>
</html>