<?php
// Database connection
$servername = "127.0.0.1";
$username = "root"; // Assuming default username
$password = ""; // Assuming no password
$dbname = "bloom";
$port = 3306;

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$message = '';
$subjects = [];
$topics = [];
$selectedSubject = '';
$selectedYearLevel = '';
$yearLevels = ['Year 7', 'Year 8', 'Year 9', 'Year 10', 'Year 11', 'Year 12'];

// Fetch all subjects
$subjectQuery = "SELECT subject_id, subject_name FROM subjects ORDER BY subject_name";
$subjectResult = $conn->query($subjectQuery);
if ($subjectResult->num_rows > 0) {
    while ($row = $subjectResult->fetch_assoc()) {
        $subjects[] = $row;
    }
}

// Handle topic form submission
if (isset($_POST['add_topic'])) {
    $subjectId = $_POST['subject_id'];
    $yearLevel = $_POST['year_level'];
    $topicName = $_POST['topic_name'];
    $topicDescription = $_POST['topic_description'];
    $orderNumber = $_POST['order_number'];
    
    $sql = "INSERT INTO topics (subject_id, year_level, topic_name, topic_description, order_number) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssi", $subjectId, $yearLevel, $topicName, $topicDescription, $orderNumber);
    
    if ($stmt->execute()) {
        $message = "Topic added successfully!";
        $selectedSubject = $subjectId;
        $selectedYearLevel = $yearLevel;
    } else {
        $message = "Error: " . $stmt->error;
    }
    $stmt->close();
}

// Handle subtopic form submission
if (isset($_POST['add_subtopic'])) {
    $topicId = $_POST['topic_id'];
    $subtopicName = $_POST['subtopic_name'];
    $description = $_POST['description'];
    $difficultyLevel = $_POST['difficulty_level'];
    $estimatedTime = $_POST['estimated_time_minutes'];
    
    $sql = "INSERT INTO subtopics (topic_id, subtopic_name, description, difficulty_level, estimated_time_minutes) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssi", $topicId, $subtopicName, $description, $difficultyLevel, $estimatedTime);
    
    if ($stmt->execute()) {
        $message = "Subtopic added successfully!";
        
        // Get the topic's subject and year level for filtering
        $topicQuery = "SELECT t.subject_id, t.year_level FROM topics t WHERE t.topic_id = ?";
        $topicStmt = $conn->prepare($topicQuery);
        $topicStmt->bind_param("i", $topicId);
        $topicStmt->execute();
        $topicResult = $topicStmt->get_result();
        $topicData = $topicResult->fetch_assoc();
        $selectedSubject = $topicData['subject_id'];
        $selectedYearLevel = $topicData['year_level'];
        $topicStmt->close();
    } else {
        $message = "Error: " . $stmt->error;
    }
    $stmt->close();
}

// If subject and year level are selected, fetch topics
if (isset($_GET['subject_id']) && isset($_GET['year_level'])) {
    $selectedSubject = $_GET['subject_id'];
    $selectedYearLevel = $_GET['year_level'];
}

if ($selectedSubject && $selectedYearLevel) {
    $topicQuery = "SELECT topic_id, topic_name, topic_description, order_number 
                  FROM topics 
                  WHERE subject_id = ? AND year_level = ? 
                  ORDER BY order_number, topic_name";
    $stmt = $conn->prepare($topicQuery);
    $stmt->bind_param("is", $selectedSubject, $selectedYearLevel);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // Get subtopics for each topic
        $subtopicQuery = "SELECT subtopic_id, subtopic_name, description, difficulty_level, estimated_time_minutes 
                         FROM subtopics 
                         WHERE topic_id = ? 
                         ORDER BY subtopic_name";
        $subtopicStmt = $conn->prepare($subtopicQuery);
        $subtopicStmt->bind_param("i", $row['topic_id']);
        $subtopicStmt->execute();
        $subtopicResult = $subtopicStmt->get_result();
        
        $subtopics = [];
        while ($subtopicRow = $subtopicResult->fetch_assoc()) {
            $subtopics[] = $subtopicRow;
        }
        
        $row['subtopics'] = $subtopics;
        $topics[] = $row;
        
        $subtopicStmt->close();
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Topic and Subtopic Management</title>
</head>
<body>
    <h1>Topic and Subtopic Management</h1>
    
    <?php if ($message): ?>
        <p><strong><?php echo $message; ?></strong></p>
    <?php endif; ?>
    
    <h2>Filter Topics</h2>
    <form method="GET" action="">
        <div>
            <label for="subject_id">Subject:</label>
            <select name="subject_id" id="subject_id" required>
                <option value="">Select Subject</option>
                <?php foreach ($subjects as $subject): ?>
                    <option value="<?php echo $subject['subject_id']; ?>" <?php echo ($selectedSubject == $subject['subject_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($subject['subject_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="year_level">Year Level:</label>
            <select name="year_level" id="year_level" required>
                <option value="">Select Year Level</option>
                <?php foreach ($yearLevels as $year): ?>
                    <option value="<?php echo $year; ?>" <?php echo ($selectedYearLevel == $year) ? 'selected' : ''; ?>>
                        <?php echo $year; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit">Filter</button>
    </form>
    
    <h2>Add New Topic</h2>
    <form method="POST" action="">
        <div>
            <label for="add_subject_id">Subject:</label>
            <select name="subject_id" id="add_subject_id" required>
                <option value="">Select Subject</option>
                <?php foreach ($subjects as $subject): ?>
                    <option value="<?php echo $subject['subject_id']; ?>">
                        <?php echo htmlspecialchars($subject['subject_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="add_year_level">Year Level:</label>
            <select name="year_level" id="add_year_level" required>
                <option value="">Select Year Level</option>
                <?php foreach ($yearLevels as $year): ?>
                    <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="topic_name">Topic Name:</label>
            <input type="text" name="topic_name" id="topic_name" required>
        </div>
        <div>
            <label for="topic_description">Description:</label>
            <textarea name="topic_description" id="topic_description" rows="3"></textarea>
        </div>
        <div>
            <label for="order_number">Order Number:</label>
            <input type="number" name="order_number" id="order_number" value="1" min="1">
        </div>
        <button type="submit" name="add_topic">Add Topic</button>
    </form>
    
    <?php if (!empty($topics)): ?>
        <h2>Topics for <?php 
            foreach ($subjects as $subject) {
                if ($subject['subject_id'] == $selectedSubject) {
                    echo htmlspecialchars($subject['subject_name']);
                    break;
                }
            }
        ?> - <?php echo $selectedYearLevel; ?></h2>
        
        <?php foreach ($topics as $topic): ?>
            <div style="margin-bottom: 20px; border: 1px solid #ccc; padding: 10px;">
                <h3><?php echo htmlspecialchars($topic['topic_name']); ?> (Order: <?php echo $topic['order_number']; ?>)</h3>
                <p><?php echo htmlspecialchars($topic['topic_description']); ?></p>
                
                <h4>Add Subtopic to <?php echo htmlspecialchars($topic['topic_name']); ?></h4>
                <form method="POST" action="">
                    <input type="hidden" name="topic_id" value="<?php echo $topic['topic_id']; ?>">
                    <div>
                        <label for="subtopic_name_<?php echo $topic['topic_id']; ?>">Subtopic Name:</label>
                        <input type="text" name="subtopic_name" id="subtopic_name_<?php echo $topic['topic_id']; ?>" required>
                    </div>
                    <div>
                        <label for="description_<?php echo $topic['topic_id']; ?>">Description:</label>
                        <textarea name="description" id="description_<?php echo $topic['topic_id']; ?>" rows="2"></textarea>
                    </div>
                    <div>
                        <label for="difficulty_level_<?php echo $topic['topic_id']; ?>">Difficulty Level:</label>
                        <select name="difficulty_level" id="difficulty_level_<?php echo $topic['topic_id']; ?>" required>
                            <option value="Easy">Easy</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="Hard">Hard</option>
                        </select>
                    </div>
                    <div>
                        <label for="estimated_time_<?php echo $topic['topic_id']; ?>">Estimated Time (minutes):</label>
                        <input type="number" name="estimated_time_minutes" id="estimated_time_<?php echo $topic['topic_id']; ?>" value="30" min="1">
                    </div>
                    <button type="submit" name="add_subtopic">Add Subtopic</button>
                </form>
                
                <?php if (!empty($topic['subtopics'])): ?>
                    <h4>Subtopics:</h4>
                    <ul>
                        <?php foreach ($topic['subtopics'] as $subtopic): ?>
                            <li>
                                <strong><?php echo htmlspecialchars($subtopic['subtopic_name']); ?></strong> 
                                (<?php echo $subtopic['difficulty_level']; ?>, <?php echo $subtopic['estimated_time_minutes']; ?> min)
                                <p><?php echo htmlspecialchars($subtopic['description']); ?></p>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No subtopics added yet.</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php elseif ($selectedSubject && $selectedYearLevel): ?>
        <p>No topics found for the selected subject and year level. Add a topic using the form above.</p>
    <?php endif; ?>
    
    <?php if (empty($subjects)): ?>
        <div style="margin-top: 20px; padding: 10px; background-color: #ffeeee;">
            <h3>No Subjects Found</h3>
            <p>Please add subjects to the database first before adding topics and subtopics.</p>
        </div>
    <?php endif; ?>

    <script>
        // JavaScript to dynamically update the interface
        document.addEventListener('DOMContentLoaded', function() {
            // Automatically scroll to the topics section after form submission
            <?php if ($message && !empty($topics)): ?>
                document.querySelector('h2:nth-of-type(3)').scrollIntoView({ behavior: 'smooth' });
            <?php endif; ?>
        });
    </script>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>