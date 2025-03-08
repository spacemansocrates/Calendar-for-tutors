<?php
// Start session
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['tutor_id'])) {
    header('Location: tutor-dashboard.php');
    exit;
}

// Database connection
require_once 'db_connect.php';

$error = '';
$success = '';

// Process signup form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate input
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all fields";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM tutors WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Email address is already registered";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new tutor
            $stmt = $conn->prepare("INSERT INTO tutors (name, email, password, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("sss", $name, $email, $hashed_password);
            
            if ($stmt->execute()) {
                $success = "Your account has been created. You can now log in.";
            } else {
                $error = "Registration failed: " . $conn->error;
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
    <title>Tutor Signup</title>
    <style>
        :root {
            --background: #ffffff;
            --foreground: #09090b;
            --card: #ffffff;
            --card-foreground: #09090b;
            --primary: #18181b;
            --primary-foreground: #ffffff;
            --secondary: #f4f4f5;
            --secondary-foreground: #18181b;
            --muted: #f4f4f5;
            --muted-foreground: #71717a;
            --accent: #f4f4f5;
            --accent-foreground: #18181b;
            --destructive: #ef4444;
            --destructive-foreground: #ffffff;
            --success: #22c55e;
            --success-foreground: #ffffff;
            --border: #e4e4e7;
            --input: #e4e4e7;
            --ring: #18181b;
            --radius: 0.5rem;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        }

        body {
            background-color: var(--background);
            color: var(--foreground);
            line-height: 1.5;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            padding: 0 1rem;
            margin: 0 auto;
        }

        .signup-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 80vh;
        }

        .card {
            border-radius: var(--radius);
            background-color: var(--card);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .signup-card {
            width: 100%;
            max-width: 450px;
            padding: 2rem;
            border: 1px solid var(--border);
        }

        .header {
            margin-bottom: 2rem;
            text-align: center;
        }

        .title {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--foreground);
            letter-spacing: -0.025em;
            margin-bottom: 0.5rem;
        }

        .subtitle {
            color: var(--muted-foreground);
            font-size: 0.95rem;
        }

        .error {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--destructive);
            padding: 0.75rem;
            border-radius: var(--radius);
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .success {
            background-color: rgba(34, 197, 94, 0.1);
            color: var(--success);
            padding: 0.75rem;
            border-radius: var(--radius);
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(34, 197, 94, 0.2);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--foreground);
        }

        input {
            width: 100%;
            padding: 0.75rem;
            font-size: 0.95rem;
            border-radius: var(--radius);
            border: 1px solid var(--input);
            background-color: transparent;
            color: var(--foreground);
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input:focus {
            border-color: var(--ring);
            box-shadow: 0 0 0 2px rgba(24, 24, 27, 0.2);
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius);
            font-size: 0.95rem;
            font-weight: 500;
            height: 2.75rem;
            padding: 0 1.25rem;
            border: 1px solid transparent;
            transition: background-color 0.2s, color 0.2s, border-color 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }

        .signup-button {
            width: 100%;
            background-color: var(--primary);
            color: var(--primary-foreground);
            margin-top: 0.5rem;
        }

        .signup-button:hover {
            background-color: #27272a;
        }

        .signup-footer {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.9rem;
            color: var(--muted-foreground);
        }

        .signup-footer p {
            margin: 0.5rem 0;
        }

        a {
            color: var(--foreground);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        a:hover {
            text-decoration: underline;
        }

        @media (max-width: 640px) {
            .signup-card {
                padding: 1.5rem;
            }
            
            .title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="signup-container">
            <div class="card signup-card">
                <div class="header">
                    <h1 class="title">Tutor Signup</h1>
                    <p class="subtitle">Create your account to get started</p>
                </div>
                
                <?php if (!empty($error)): ?>
                <div class="error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                <div class="success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form action="" method="post" class="signup-form">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="button signup-button">Create Account</button>
                </form>
                
                <div class="signup-footer">
                    <p>Already have an account? <a href="tutor-login.php">Login</a></p>
                    <p>By signing up, you agree to our <a href="terms.php">Terms of Service</a></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>