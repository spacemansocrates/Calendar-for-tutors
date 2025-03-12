<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bloom";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process form submission
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["csvFile"])) {
    $file = $_FILES["csvFile"];
    
    // Check if the file is a CSV
    $file_ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    if ($file_ext != "csv") {
        $message = "Error: Only CSV files are allowed.";
    } else if ($file["error"] > 0) {
        $message = "Error: " . $file["error"];
    } else {
        // Process the CSV file
        $handle = fopen($file["tmp_name"], "r");
        $firstRow = true;
        $successCount = 0;
        $topicOrderNumber = 1; // Initialize order number counter
        
        // Get subject_id and year number from form
        $subject_id = $_POST["subject_id"];
        $year_number = $_POST["year_number"];
        
        // Format year level as "Year X"
        $year_level = "Year " . $year_number;
        
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Skip header row if exists
            if ($firstRow && isset($_POST["has_header"])) {
                $firstRow = false;
                continue;
            }
            
            $topic_name = trim($data[0]);
            $subtopic_name = trim($data[1]);
            
            // Only process rows where we have a topic or subtopic
            if (empty($topic_name) && empty($subtopic_name)) {
                continue;
            }
            
            // If we have a topic name, insert it and get the ID
            if (!empty($topic_name)) {
                $stmt = $conn->prepare("INSERT INTO topics (subject_id, year_level, topic_name, order_number) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("issi", $subject_id, $year_level, $topic_name, $topicOrderNumber);
                $stmt->execute();
                $current_topic_id = $conn->insert_id;
                $stmt->close();
                
                // Increment order number for next topic
                $topicOrderNumber++;
            }
            
            // If we have a subtopic, insert it using the current topic ID
            if (!empty($subtopic_name)) {
                $stmt = $conn->prepare("INSERT INTO subtopics (topic_id, subtopic_name) VALUES (?, ?)");
                $stmt->bind_param("is", $current_topic_id, $subtopic_name);
                $stmt->execute();
                $stmt->close();
                $successCount++;
            }
        }
        
        fclose($handle);
        $message = "Import completed! $successCount records were imported successfully.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>CSV Import for Topics and Subtopics</title>
    <script>
        function validateForm() {
            var fileInput = document.getElementById('csvFile');
            var subjectId = document.getElementById('subject_id').value;
            var yearNumber = document.getElementById('year_number').value;
            
            if (fileInput.files.length === 0) {
                alert('Please select a CSV file to upload.');
                return false;
            }
            
            if (subjectId === '') {
                alert('Please enter a subject ID.');
                return false;
            }
            
            if (yearNumber === '') {
                alert('Please enter a year number.');
                return false;
            }
            
            return true;
        }
    </script>
</head>
<body>
    <h1>Import Topics and Subtopics from CSV</h1>
    
    <?php if (!empty($message)): ?>
        <div style="padding: 10px; margin-bottom: 20px; background-color: <?php echo strpos($message, "Error") === 0 ? "#ffdddd" : "#ddffdd"; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <form method="post" enctype="multipart/form-data" onsubmit="return validateForm()">
        <div>
            <label for="subject_id">Subject ID:</label>
            <input type="number" id="subject_id" name="subject_id" required>
        </div>
        
        <div>
            <label for="year_number">Year Number:</label>
            <input type="number" id="year_number" name="year_number" required>
            <span>(Will be formatted as "Year X")</span>
        </div>
        
        <div>
            <label for="csvFile">Select CSV file:</label>
            <input type="file" id="csvFile" name="csvFile" accept=".csv">
        </div>
        
        <div>
            <input type="checkbox" id="has_header" name="has_header" checked>
            <label for="has_header">CSV has header row</label>
        </div>
        
        <div>
            <button type="submit">Import Data</button>
        </div>
    </form>
    
    <h2>CSV Format Instructions</h2>
    <p>Your CSV file should have two columns:</p>
    <ol>
        <li>Topic name (leave blank if continuing with subtopics for the same topic)</li>
        <li>Subtopic name</li>
    </ol>
    
    <h3>Example:</h3>
    <pre>
    Linear Programming,Graphing an inequality
    ,Graphing more than one inequality
    ,Linear Programming word Problem
    Other Types of Graph,1. Quadratic Graphs
    ,2. Cubic Graphs
    </pre>
    
    <p>Note:</p>
    <ul>
        <li>For each row with a topic name, a new topic will be created</li>
        <li>Topics will be automatically assigned order numbers starting from 1</li>
        <li>Rows with empty topic names will use the most recently created topic</li>
        <li>Year level will be formatted as "Year X" where X is the year number you enter</li>
    </ul>
</body>
</html>