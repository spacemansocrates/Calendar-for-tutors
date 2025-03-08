<?php
// Require the League/CSV library
require 'vendor/autoload.php';

use League\Csv\Reader;

// Process the uploaded file
function processLessonData($filePath) {
    $result = [
        'total_demos' => 0,
        'demos_no_show' => 0,
        'total_lessons' => 0, // Regular + catchup lessons
        'lessons_no_show' => 0,
        'total_hours' => 0,
        'delivered_hours' => 0,
        'cancelled_hours' => 0,
        'by_tutor' => []
    ];
    
    try {
        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0); // First row contains headers
        $headers = $csv->getHeader(); // Get the CSV headers
        
        // Map expected columns to their positions
        $columnMap = [];
        foreach ($headers as $index => $header) {
            $header = trim($header);
            if (stripos($header, 'lesson type') !== false) {
                $columnMap['lesson_type'] = $header;
            } else if (stripos($header, 'session status') !== false) {
                $columnMap['session_status'] = $header;
            } else if (stripos($header, 'tutor') !== false && stripos($header, 'name') !== false) {
                $columnMap['tutor_name'] = $header;
            }
        }
        
        // Process records
        $records = $csv->getRecords();
        foreach ($records as $record) {
            $lessonType = isset($columnMap['lesson_type']) ? $record[$columnMap['lesson_type']] : '';
            $sessionStatus = isset($columnMap['session_status']) ? $record[$columnMap['session_status']] : '';
            $tutorName = isset($columnMap['tutor_name']) ? $record[$columnMap['tutor_name']] : 'Unknown';
            
            // Skip empty rows
            if (empty($lessonType)) {
                continue;
            }
            
            // Initialize tutor stats if new
            if (!isset($result['by_tutor'][$tutorName])) {
                $result['by_tutor'][$tutorName] = [
                    'total_demos' => 0,
                    'demos_no_show' => 0,
                    'total_lessons' => 0,
                    'lessons_no_show' => 0,
                    'total_hours' => 0
                ];
            }
            
            // Process based on lesson type and session status
            if (strtolower(trim($lessonType)) == 'demo') {
                $result['total_demos']++;
                $result['by_tutor'][$tutorName]['total_demos']++;
                
                // All demos are half hour regardless of status
                $result['total_hours'] += 0.5;
                $result['by_tutor'][$tutorName]['total_hours'] += 0.5;
                
                if (stripos($sessionStatus, 'no show') !== false) {
                    $result['demos_no_show']++;
                    $result['by_tutor'][$tutorName]['demos_no_show']++;
                }
            } 
            else { // Regular or catchup lessons
                $result['total_lessons']++;
                $result['by_tutor'][$tutorName]['total_lessons']++;
                
                // Calculate hours based on session status
                $hours = 0;
                $sessionStatus = strtolower(trim($sessionStatus));
                
                if ($sessionStatus == 'delivered') {
                    $hours = 1;
                    $result['delivered_hours'] += 1;
                }
                else if (stripos($sessionStatus, 'no show') !== false) {
                    $hours = 0.5;
                    $result['lessons_no_show']++;
                    $result['by_tutor'][$tutorName]['lessons_no_show']++;
                }
                else if (stripos($sessionStatus, 'cancelled~4') !== false) {
                    $hours = 0.5;
                    $result['cancelled_hours'] += 0.5;
                }
                else if (stripos($sessionStatus, 'rescheduled') !== false) {
                    $hours = 0;
                }
                
                $result['total_hours'] += $hours;
                $result['by_tutor'][$tutorName]['total_hours'] += $hours;
            }
        }
        
        return $result;
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

// HTML starts here - only display if we're not processing a file
if (!isset($_POST['submit'])):
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lesson Data Analyzer</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="title">Lesson Data Analyzer</h1>
            <p class="subtitle">Upload your CSV export to analyze tutoring session data</p>
        </div>
        
        <div class="card">
            <div class="alert">
                <div class="alert-title">How to prepare your data</div>
                <div class="alert-content">
                    <p>Please save your Google Sheet as a CSV file before uploading:</p>
                    <ol>
                        <li>In Google Sheets, go to File > Download > Comma-separated values (.csv)</li>
                        <li>Upload the downloaded CSV file using the form below</li>
                    </ol>
                </div>
            </div>
            
            <form action="" method="post" enctype="multipart/form-data" class="form">
                <div class="file-input">
                    <label class="file-input-label">
                        Choose CSV file
                        <input type="file" name="csv_file" accept=".csv" required>
                    </label>
                </div>
                <button type="submit" name="submit" class="button">Analyze Data</button>
            </form>
        </div>
    </div>
</body>
</html>

<?php
else:
    // Process the uploaded file and display results
    if ($_FILES['csv_file']['error'] == 0) {
        $filePath = $_FILES['csv_file']['tmp_name'];
        $result = processLessonData($filePath);
        
        // Start HTML output
        echo '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Lesson Data Analysis Results</title>
            <link rel="stylesheet" href="styles.css">
        </head>
        <body>
            <div class="container">';
        
        echo '<div class="header">
                <h1 class="title">Analysis Results</h1>
                <p class="subtitle">Summary of your uploaded tutoring data</p>
              </div>';
        
        if (!isset($result['error'])) {
            // Display overall results
            echo '<div class="card">
                    <h2>Summary Statistics</h2>
                    <div class="result-grid">';
            
            echo '<div class="stat-card">
                    <div class="stat-title">Total Demos</div>
                    <div class="stat-value">' . $result['total_demos'] . '</div>
                  </div>';
            
            echo '<div class="stat-card">
                    <div class="stat-title">Demos No Show</div>
                    <div class="stat-value">' . $result['demos_no_show'] . '</div>
                  </div>';
            
            echo '<div class="stat-card">
                    <div class="stat-title">Total Lessons</div>
                    <div class="stat-value">' . $result['total_lessons'] . '</div>
                  </div>';
            
            echo '<div class="stat-card">
                    <div class="stat-title">Lessons No Show</div>
                    <div class="stat-value">' . $result['lessons_no_show'] . '</div>
                  </div>';
            
            echo '<div class="stat-card">
                    <div class="stat-title">Total Hours</div>
                    <div class="stat-value">' . $result['total_hours'] . '</div>
                  </div>';
            
            echo '<div class="stat-card">
                    <div class="stat-title">Delivered Hours</div>
                    <div class="stat-value">' . $result['delivered_hours'] . '</div>
                  </div>';
            
            echo '</div>
                 </div>';
            
            // Display tutor-specific results
            echo '<div class="card">
                    <h2>Results by Tutor</h2>
                    <div class="table-container">
                      <table>
                        <thead>
                          <tr>
                            <th>Tutor Name</th>
                            <th>Total Demos</th>
                            <th>Demos No Show</th>
                            <th>Total Lessons</th>
                            <th>Lessons No Show</th>
                            <th>Total Hours</th>
                          </tr>
                        </thead>
                        <tbody>';
            
            foreach ($result['by_tutor'] as $tutor => $stats) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($tutor) . '</td>';
                echo '<td>' . $stats['total_demos'] . '</td>';
                echo '<td>' . $stats['demos_no_show'] . '</td>';
                echo '<td>' . $stats['total_lessons'] . '</td>';
                echo '<td>' . $stats['lessons_no_show'] . '</td>';
                echo '<td>' . $stats['total_hours'] . '</td>';
                echo '</tr>';
            }
            
            echo '</tbody>
                  </table>
                 </div>
                </div>';
        } else {
            echo '<div class="error"><strong>Error:</strong> ' . htmlspecialchars($result['error']) . '</div>';
        }
        
        echo '<a href="' . $_SERVER['PHP_SELF'] . '" class="button back-button">Upload Another File</a>';
        echo '</div></body></html>';
    } else {
        echo '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Upload Error</title>
            <link rel="stylesheet" href="styles.css">
        </head>
        <body>
            <div class="container">
                <div class="error">Error uploading file. Please try again.</div>
                <a href="' . $_SERVER['PHP_SELF'] . '" class="button back-button">Try Again</a>
            </div>
        </body>
        </html>';
    }
endif;
?>