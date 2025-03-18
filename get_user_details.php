<?php
// Database connection
require_once 'db_connect.php'; // Assuming you have a config file with database credentials

// Initialize response array
$response = [];

// Check if type and id parameters are set
if (isset($_GET['type']) && isset($_GET['id'])) {
    $type = $_GET['type'];
    $id = intval($_GET['id']);
    
    // Connect to database
    try {
        $conn = new PDO("mysql:host={$config['db_host']};dbname={$config['db_name']}", $config['db_user'], $config['db_pass']);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Get user details based on type
        if ($type === 'student') {
            $stmt = $conn->prepare("
                SELECT 
                    s.id AS student_id,
                    s.name,
                    s.email,
                    s.parent_name,
                    s.parent_email,
                    s.phone,
                    s.grade,
                    s.subjects,
                    s.created_at,
                    u.last_login
                FROM 
                    students s
                LEFT JOIN 
                    users u ON s.user_id = u.id
                WHERE 
                    s.id = :id
            ");
            $stmt->execute(['id' => $id]);
            $response = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Format dates for display
            if ($response) {
                // Additional processing if needed
                
                // Get additional statistics
                $stmt = $conn->prepare("
                    SELECT 
                        COUNT(DISTINCT l.id) AS total_lessons,
                        COUNT(DISTINCT h.id) AS total_homework,
                        COUNT(DISTINCT CASE WHEN h.status = 'completed' THEN h.id END) AS completed_homework
                    FROM 
                        students s
                    LEFT JOIN 
                        lessons l ON s.id = l.student_id
                    LEFT JOIN 
                        homework h ON s.id = h.student_id
                    WHERE 
                        s.id = :id
                ");
                $stmt->execute(['id' => $id]);
                $stats = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($stats) {
                    $response = array_merge($response, $stats);
                }
            }
        } elseif ($type === 'tutor') {
            $stmt = $conn->prepare("
                SELECT 
                    t.id,
                    t.name,
                    t.email,
                    t.link,
                    t.created_at,
                    u.last_login,
                    COUNT(DISTINCT l.id) AS total_lessons,
                    COUNT(DISTINCT s.id) AS total_students
                FROM 
                    tutors t
                LEFT JOIN 
                    users u ON t.user_id = u.id
                LEFT JOIN 
                    lessons l ON t.id = l.tutor_id
                LEFT JOIN 
                    tutor_student_map ts ON t.id = ts.tutor_id
                LEFT JOIN 
                    students s ON ts.student_id = s.id
                WHERE 
                    t.id = :id
                GROUP BY 
                    t.id
            ");
            $stmt->execute(['id' => $id]);
            $response = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get specializations/subjects
            if ($response) {
                $stmt = $conn->prepare("
                    SELECT 
                        subject
                    FROM 
                        tutor_subjects
                    WHERE 
                        tutor_id = :id
                ");
                $stmt->execute(['id' => $id]);
                $subjects = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                $response['subjects'] = implode(', ', $subjects);
                
                // Get availability schedule
                $stmt = $conn->prepare("
                    SELECT 
                        day_of_week,
                        start_time,
                        end_time
                    FROM 
                        tutor_availability
                    WHERE 
                        tutor_id = :id
                    ORDER BY 
                        FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')
                ");
                $stmt->execute(['id' => $id]);
                $availability = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $response['availability'] = $availability;
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid user type']);
            exit;
        }
        
        if (!$response) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            exit;
        }
        
    } catch (PDOException $e) {
        // Log error (in production, don't expose error details to client)
        error_log("Database error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
        exit;
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>