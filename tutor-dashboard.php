<?php
// Start session
// Start session
session_start();

// Database connection
require_once 'db_connect.php';

// Authentication check
if (!isset($_SESSION['tutor_id'])) {
    // Redirect to login page if not authenticated
    header('Location: tutor-login.php');
    exit;
}

// Get current tutor information
$tutorId = $_SESSION['tutor_id'];
$tutorName = $_SESSION['tutor_name'];

// Initialize tutorLink variable
$tutorLink = "";

// Fetch the tutor's link from the database
$sql = "SELECT link FROM tutors WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tutorId);
$stmt->execute();
$stmt->bind_result($tutorLink);
$stmt->fetch();
$stmt->close();


// Check if a link was found
// if (!empty($tutorLink)) {
//     echo "Tutor Link: <a href='$tutorLink'>$tutorLink</a>";
// } else {
//     echo "No link available.";
// }

// Get current month and year
$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Ensure month is valid
if ($month < 1) {
    $month = 12;
    $year--;
} elseif ($month > 12) {
    $month = 1;
    $year++;
}

// Get the first day of the month
$firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
$numberDays = date('t', $firstDayOfMonth);
$dateComponents = getdate($firstDayOfMonth);
$monthName = $dateComponents['month'];
$dayOfWeek = $dateComponents['wday'];

// Get lessons for this tutor for the current month
$stmt = $conn->prepare("
    SELECT * FROM lessons 
    WHERE tutor_id = ? 
    AND MONTH(lesson_date) = ? 
    AND YEAR(lesson_date) = ?
    ORDER BY lesson_date, start_time
");
$stmt->bind_param("iii", $tutorId, $month, $year);
$stmt->execute();
$result = $stmt->get_result();

// Organize lessons by date
$lessons = [];
while ($row = $result->fetch_assoc()) {
    $lessonDate = date('j', strtotime($row['lesson_date']));
    if (!isset($lessons[$lessonDate])) {
        $lessons[$lessonDate] = [];
    }
    $lessons[$lessonDate][] = $row;
}

// Handle form submission for adding a new lesson
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_lesson'])) {
    $date = $_POST['lesson_date'];
    $startTime = $_POST['start_time'];
    $endTime = $_POST['end_time'];
    $studentName = $_POST['student_name'];
    $lessonType = $_POST['lesson_type'];
    $sessionStatus = $_POST['session_status'];
    $notes = $_POST['notes'];
    
    $stmt = $conn->prepare("
        INSERT INTO lessons (
            tutor_id, student_name, lesson_date, start_time, end_time, 
            lesson_type, session_status, notes, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->bind_param(
        "isssssss", 
        $tutorId, $studentName, $date, $startTime, $endTime, 
        $lessonType, $sessionStatus, $notes
    );
    
    if ($stmt->execute()) {
        // Redirect to avoid form resubmission
        header("Location: tutor-dashboard.php?month=$month&year=$year&success=1");
        exit;
    } else {
        $error = "Error adding lesson: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutor dashboard</title>
    <link rel="stylesheet" href="stile.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="title">Tutor Dashboard</h1>
            <div class="user-info">
                <p>Welcome, <?php echo htmlspecialchars($tutorName); ?></p>
                <a href="logout.php" class="button-link">Log Out</a>
                
                <a href="select_student.php" class="button-link">topics</a>
                <a href="homework.php" class="button-link">homeworks</a>
                <a href="tutordash.php" class="button-link">dash</a>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
        <div class="success-alert">
            <p>Lesson added successfully!</p>
        </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="error">
            <p><?php echo $error; ?></p>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="calendar-header">
                <h2><?php echo $monthName . " " . $year; ?></h2>
                <div class="calendar-nav">
                    <a href="?month=<?php echo $month-1; ?>&year=<?php echo $year; ?>" class="nav-button">&lt; Prev</a>
                    <a href="?month=<?php echo date('m'); ?>&year=<?php echo date('Y'); ?>" class="nav-button">Today</a>
                    <a href="?month=<?php echo $month+1; ?>&year=<?php echo $year; ?>" class="nav-button">Next &gt;</a>
                </div>
            </div>

            <div class="calendar">
                <div class="calendar-weekdays">
                    <div>Sun</div>
                    <div>Mon</div>
                    <div>Tue</div>
                    <div>Wed</div>
                    <div>Thu</div>
                    <div>Fri</div>
                    <div>Sat</div>
                </div>

                <div class="calendar-days">
    <?php
    // Fill in blank days until the first day of the month
    for ($i = 0; $i < $dayOfWeek; $i++) {
        echo '<div class="calendar-day empty"></div>';
    }
    
    // Display all days of the month
    for ($day = 1; $day <= $numberDays; $day++) {
        $currentDate = sprintf("%04d-%02d-%02d", $year, $month, $day);
        $isToday = ($day == date('j') && $month == date('m') && $year == date('Y'));
        $dayClass = $isToday ? 'calendar-day today' : 'calendar-day';
        
        echo '<div class="' . $dayClass . '">';
        echo '<div class="day-header">';
        echo '<span class="day-number">' . $day . '</span>';
        echo '<button type="button" class="add-lesson-btn" onclick="openLessonModal(\'' . $currentDate . '\')">+</button>';
        echo '</div>';
        
        // Display lessons for this day
        if (isset($lessons[$day]) && !empty($lessons[$day])) {
            echo '<div class="day-lessons">';
            foreach ($lessons[$day] as $lesson) {
                $startTime = date('g:ia', strtotime($lesson['start_time']));
                $statusClass = strtolower(str_replace(' ', '-', $lesson['session_status']));
                
                echo '<div class="lesson-item ' . $statusClass . '">';
                echo '<div class="lesson-time">' . $startTime . '</div>';
                echo '<div class="lesson-student">' . htmlspecialchars($lesson['student_name'] ?? '') . '</div>';
                echo '<div class="lesson-type">' . htmlspecialchars($lesson['lesson_type'] ?? '') . '</div>';

                
                // Add Google Meet button for scheduled lessons
                $currentTime = time();
                $startedAt = !empty($lesson['started_at']) ? strtotime($lesson['started_at']) : null;
                $elapsedTime = $startedAt ? $currentTime - $startedAt : null;
                
                echo '<div class="lesson-actions">';
                if ($lesson['session_status'] == 'Scheduled' && ($startedAt === null || $elapsedTime < 300)) {
                    // Make sure to include the tutorLink parameter, possibly from the $lesson array
                    echo '<button class="meet-button" onclick="startLesson(' . $lesson['id'] . ', \'' . htmlspecialchars($tutorLink, ENT_QUOTES) . '\')">Join Meet</button>';
                } else if ($lesson['session_status'] == 'Delivered') {
                    echo '<button class="update-button" onclick="openUpdateModal(' . $lesson['id'] . ')">Update</button>';
                }
                echo '</div>'; // Close lesson-actions
                
                echo '</div>'; // Close lesson-item
            }
            echo '</div>'; // Close day-lessons
        }
        
        echo '</div>'; // Close calendar-day
    }
    
    // Fill in remaining days of the week
    $remainingDays = 7 - (($dayOfWeek + $numberDays) % 7);
    if ($remainingDays < 7) {
        for ($i = 0; $i < $remainingDays; $i++) {
            echo '<div class="calendar-day empty"></div>';
        }
    }
    ?>
</div>
            </div>
        </div>
        <style>
.lesson-actions {
    margin-top: 5px;
    display: flex;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.2s ease;
    position: absolute;
    bottom: 2px;
    left: 0;
    right: 0;
}

.lesson-item {
    position: relative;
    padding-bottom: 26px; /* Add extra padding to make room for the hidden buttons */
}

.lesson-item:hover .lesson-actions {
    opacity: 1;
}

.meet-button, .update-button {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 500;
    height: 24px;
    width: 90%;
    padding: 0 12px;
    border: none;
    transition: all 0.2s ease;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.meet-button {
    background-color: #4285F4;
    color: white;
}

.update-button {
    background-color: #10b981;
    color: white;
}

.meet-button:hover, .update-button:hover {
    filter: brightness(1.05);
    transform: translateY(-1px);
}

.meet-button:active, .update-button:active {
    filter: brightness(0.95);
    transform: translateY(0);
}

.meet-button:focus, .update-button:focus {
    outline: 2px solid rgba(66, 133, 244, 0.3);
    outline-offset: 2px;
}

.update-button:focus {
    outline: 2px solid rgba(16, 185, 129, 0.3);
}
        </style>

        <!-- Lesson Stats Card -->
        <div class="card">
            <h2>Monthly Statistics</h2>
            <div class="result-grid">
                <?php
                // Calculate statistics
                $totalLessons = 0;
                $totalDelivered = 0;
                $totalNoShow = 0;
                $totalHours = 0;
                $totalDemos = 0;
                
                $stmt = $conn->prepare("
                    SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN session_status = 'Delivered' THEN 1 ELSE 0 END) as delivered,
                        SUM(CASE WHEN session_status LIKE '%No Show%' THEN 1 ELSE 0 END) as no_show,
                        SUM(CASE WHEN lesson_type = 'Demo' THEN 1 ELSE 0 END) as demos,
                        SUM(CASE 
                            WHEN lesson_type = 'Demo' THEN 0.5
                            WHEN session_status = 'Delivered' THEN TIMESTAMPDIFF(MINUTE, start_time, end_time) / 60
                            WHEN session_status LIKE '%No Show%' THEN 0.5
                            WHEN session_status LIKE '%Cancelled%' THEN 0.5
                            ELSE 0
                        END) as total_hours
                    FROM lessons 
                    WHERE tutor_id = ? 
                    AND MONTH(lesson_date) = ? 
                    AND YEAR(lesson_date) = ?
                ");
                $stmt->bind_param("iii", $tutorId, $month, $year);
                $stmt->execute();
                $stats = $stmt->get_result()->fetch_assoc();
                
                if ($stats) {
                    $totalLessons = $stats['total'];
                    $totalDelivered = $stats['delivered'];
                    $totalNoShow = $stats['no_show'];
                    $totalDemos = $stats['demos'];
                    $totalHours = $stats['total_hours'];
                }
                ?>
                
                <div class="stat-card">
                    <div class="stat-title">Total Lessons</div>
                    <div class="stat-value"><?php echo $totalLessons; ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-title">Demos</div>
                    <div class="stat-value"><?php echo $totalDemos; ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-title">Delivered</div>
                    <div class="stat-value"><?php echo $totalDelivered; ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-title">No Shows</div>
                    <div class="stat-value"><?php echo $totalNoShow; ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-title">Total Hours</div>
                    <div class="stat-value"><?php echo number_format($totalHours, 1); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-title">Completion Rate</div>
                    <div class="stat-value"><?php echo $totalLessons > 0 ? number_format(($totalDelivered / $totalLessons) * 100, 1) . '%' : '0%'; ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
    <h2>Videos</h2>
    <video id="lessonVideo" controls style="width: 100%; max-width: 800px; display: none;">
    <source src="" type="video/webm">
    Your browser does not support the video tag.
</video>
    </div>
    <!-- Add Lesson Modal -->
    <div id="lessonModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <h2>Add New Lesson</h2>
            
            <form action="" method="post" class="lesson-form">
                <input type="hidden" id="lesson_date" name="lesson_date">
                
                <div class="form-group">
                    <label for="student_name">Student Name</label>
                    <input type="text" id="student_name" name="student_name" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="start_time">Start Time</label>
                        <input type="time" id="start_time" name="start_time" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="end_time">End Time</label>
                        <input type="time" id="end_time" name="end_time" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="lesson_type">Lesson Type</label>
                        <select id="lesson_type" name="lesson_type" required>
                            <option value="Regular">Regular</option>
                            <option value="Demo">Demo</option>
                            <option value="Catchup">Catchup</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="session_status">Session Status</label>
                        <select id="session_status" name="session_status" required>
                            <option value="Scheduled">Scheduled</option>
                            <option value="Delivered">Delivered</option>
                            <option value="No Show">No Show</option>
                            <option value="Cancelled">Cancelled</option>
                            <option value="Rescheduled">Rescheduled</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="3"></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" onclick="closeModal()" class="button-secondary">Cancel</button>
                    <button type="submit" name="add_lesson" class="button">Save Lesson</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Update Lesson Modal -->
<div id="updateModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeUpdateModal()">&times;</span>
        <h2>Update Lesson</h2>
        
        <form action="update_lesson.php" method="post" class="lesson-form">
            <input type="hidden" id="update_lesson_id" name="lesson_id">
            
            <div class="form-group">
                <label for="update_session_status">Session Status</label>
                <select id="update_session_status" name="session_status" required>
                    <option value="Scheduled">Scheduled</option>
                    <option value="Delivered">Delivered</option>
                    <option value="No Show">No Show</option>
                    <option value="Cancelled">Cancelled</option>
                    <option value="Rescheduled">Rescheduled</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="update_notes">Session Notes</label>
                <textarea id="update_notes" name="notes" rows="5"></textarea>
            </div>
            
            <div class="form-actions">
                <button type="button" onclick="closeUpdateModal()" class="button-secondary">Cancel</button>
                <button type="submit" name="update_lesson" class="button">Save Changes</button>
            </div>
        </form>
    </div>
</div>
<script>
// Modal functionality
const modal = document.getElementById('lessonModal');
const lessonDateInput = document.getElementById('lesson_date');

function openLessonModal(date) {
    lessonDateInput.value = date;
    modal.style.display = 'flex';
    // Auto set times to common lesson times
    const now = new Date();
    const hours = now.getHours();
    const startHour = (hours < 12) ? '09:00' : '16:00';
    const endHour = (hours < 12) ? '10:00' : '17:00';
    document.getElementById('start_time').value = startHour;
    document.getElementById('end_time').value = endHour;
}

function closeModal() {
    modal.style.display = 'none';
}

// Close the modal if user clicks outside the modal content
window.addEventListener('click', function(event) {
    if (event.target == modal) {
        closeModal();
    }
});

// Set end time automatically when start time changes (1 hour later)
document.getElementById('start_time').addEventListener('change', function() {
    const startTime = this.value;
    if (startTime) {
        const [hours, minutes] = startTime.split(':').map(Number);
        const endHour = (hours + 1) % 24;
        document.getElementById('end_time').value = 
            `${endHour.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;
    }
});

// Handle lesson type change - set default durations
document.getElementById('lesson_type').addEventListener('change', function() {
    const lessonType = this.value;
    const startTime = document.getElementById('start_time').value;
    
    if (startTime && lessonType === 'Demo') {
        // Demos are 30 minutes
        const [hours, minutes] = startTime.split(':').map(Number);
        let endMinutes = minutes + 30;
        let endHour = hours;
        
        if (endMinutes >= 60) {
            endMinutes -= 60;
            endHour = (endHour + 1) % 24;
        }
        
        document.getElementById('end_time').value = 
            `${endHour.toString().padStart(2, '0')}:${endMinutes.toString().padStart(2, '0')}`;
    } else if (startTime) {
        // Regular lessons are 1 hour
        const [hours, minutes] = startTime.split(':').map(Number);
        const endHour = (hours + 1) % 24;
        document.getElementById('end_time').value = 
            `${endHour.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;
    }
});

// Update modal functionality
const updateModal = document.getElementById('updateModal');
const updateLessonIdInput = document.getElementById('update_lesson_id');

let mediaRecorder;
let recordedChunks = [];

function startLesson(lessonId, tutorLink) {
    if (tutorLink && !tutorLink.startsWith('http://') && !tutorLink.startsWith('https://')) {
        tutorLink = 'https://' + tutorLink;
    }
    
    // Guard against empty links
    if (!tutorLink) {
        alert("Error: Meeting link is not available.");
        return;
    }
    
    // Open tutor's specific Google Meet link in a new tab
    const meetTab = window.open(tutorLink, '_blank');
    // Show instructions to the tutor
    alert("Please click 'Start Recording' after joining the Google Meet session. When finished, click 'Stop Recording' to save and upload.");
    
    // Add recording controls to the original page
    const controlsDiv = document.createElement('div');
    controlsDiv.innerHTML = `
        <div class="recording-controls" style="position: fixed; top: 10px; right: 10px; background: #f0f0f0; padding: 10px; border-radius: 5px; z-index: 9999;">
            <button id="startRecording" class="btn btn-danger">Start Recording</button>
            <button id="stopRecording" class="btn btn-secondary" disabled>Stop Recording</button>
            <div id="recordingStatus">Not recording</div>
        </div>
    `;
    document.body.appendChild(controlsDiv);
    
    // Set up recording button handlers
    document.getElementById('startRecording').addEventListener('click', () => {
        startScreenRecording(lessonId);
        document.getElementById('startRecording').disabled = true;
        document.getElementById('stopRecording').disabled = false;
        document.getElementById('recordingStatus').textContent = "Recording...";
    });
    
    document.getElementById('stopRecording').addEventListener('click', () => {
        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            mediaRecorder.stop();
        }
        document.getElementById('startRecording').disabled = false;
        document.getElementById('stopRecording').disabled = true;
        document.getElementById('recordingStatus').textContent = "Processing...";
    });
    
    // Send request to update lesson start time
    fetch('start_lesson.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'lesson_id=' + lessonId
    }).then(response => response.text()).then(data => {
        console.log(data);
    });

    // Change button to "Update" after lesson starts (5 minutes delay)
    setTimeout(() => {
        // Use escaped tutorLink inside querySelector
        const button = document.querySelector(`button[onclick="startLesson(${lessonId}, '${tutorLink.replace(/'/g, "\\'")}')"]`);
        
        if (button) {
            button.textContent = "Update";
            button.onclick = function() {
                openUpdateModal(lessonId);
            };
            button.classList.remove('meet-button');
            button.classList.add('update-button');
        }
    }, 5 * 60 * 1000);
}

function startScreenRecording(lessonId) {
    // Reset recorded chunks
    recordedChunks = [];
    
    // Request screen capture with audio
    navigator.mediaDevices.getDisplayMedia({ 
        video: { 
            displaySurface: "browser",
            cursor: "always"
        }, 
        audio: true 
    }).then(stream => {
        console.log("Screen capture started");
        
        // Create media recorder
        mediaRecorder = new MediaRecorder(stream, {mimeType: 'video/webm;codecs=vp9'});
        
        // Handle data available event
        mediaRecorder.ondataavailable = event => {
            if (event.data.size > 0) {
                recordedChunks.push(event.data);
            }
        };
        
        // Handle recording stop
        mediaRecorder.onstop = () => {
            console.log("Recording stopped, preparing to upload");
            document.getElementById('recordingStatus').textContent = "Uploading...";
            
            // Combine recorded chunks into a single blob
            const blob = new Blob(recordedChunks, { type: 'video/webm' });
            const file = new File([blob], `lesson_${lessonId}_${Date.now()}.webm`, { type: "video/webm" });
            
            // Upload the recording
            uploadRecording(file, lessonId);
            
            // Stop all tracks
            stream.getTracks().forEach(track => track.stop());
        };
        
        // Start recording
        mediaRecorder.start(1000); // Capture in 1-second chunks
        console.log("Recording started");
        
    }).catch(error => {
        console.error("Screen recording failed:", error);
        alert("Failed to start recording: " + error.message);
        document.getElementById('startRecording').disabled = false;
        document.getElementById('stopRecording').disabled = true;
        document.getElementById('recordingStatus').textContent = "Recording failed";
    });
}

function uploadRecording(file, lessonId) {
    let formData = new FormData();
    formData.append("video", file);
    formData.append("lesson_id", lessonId);
    
    fetch('upload_recording.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        console.log("Upload Response:", data);
        document.getElementById('recordingStatus').textContent = "Upload complete";
        
        // Optionally refresh the page or video display
        if (document.getElementById('lessonVideo')) {
            // If there's a video player on the page, update its source
            const videoPlayer = document.getElementById('lessonVideo');
            videoPlayer.style.display = 'block';
            videoPlayer.src = data.includes('http') ? data : 'recordings/lesson_' + lessonId + '_' + Date.now() + '.webm';
        }
    })
    .catch(error => {
        console.error("Upload failed:", error);
        document.getElementById('recordingStatus').textContent = "Upload failed";
        alert("Failed to upload recording: " + error.message);
    });
}

function openUpdateModal(lessonId) {
    updateLessonIdInput.value = lessonId;
    updateModal.style.display = 'flex';
    
    // You can fetch the current lesson data with AJAX if needed
    // For now, we'll just open the modal with empty fields
}

function closeUpdateModal() {
    updateModal.style.display = 'none';
}

// Close the update modal if clicked outside
window.addEventListener('click', function(event) {
    if (event.target == updateModal) {
        closeUpdateModal();
    }
});
</script>
</body>