<?php
// Database configuration
$servername = "127.0.0.1";
$username = "root"; // Update with your database username
$password = ""; // Update with your database password
$dbname = "bloom";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to sanitize input data
function sanitize($data) {
    global $conn;
    return $conn->real_escape_string(trim($data));
}

// Handle user operations
$message = "";
$userType = isset($_POST['userType']) ? $_POST['userType'] : (isset($_GET['userType']) ? $_GET['userType'] : 'students');

// Handle Add/Edit User
if (isset($_POST['saveUser'])) {
    $userType = sanitize($_POST['userType']);
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    
    // Generate a password hash if new password is provided
    $passwordField = "";
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $passwordField = ", password = '$password'";
    }
    
    if (isset($_POST['userId']) && !empty($_POST['userId'])) {
        // Update existing user
        $userId = (int)$_POST['userId'];
        $table = $userType === 'tutors' ? 'tutors' : 'students';
        $idField = $userType === 'tutors' ? 'id' : 'student_id';
        
        // Additional fields for students
        $additionalFields = "";
        if ($userType === 'students') {
            $parent_name = sanitize($_POST['parent_name'] ?? '');
            $parent_email = sanitize($_POST['parent_email'] ?? '');
            $phone = sanitize($_POST['phone'] ?? '');
            $grade = sanitize($_POST['grade'] ?? '');
            $subjects = sanitize($_POST['subjects'] ?? '');
            $notes = sanitize($_POST['notes'] ?? '');
            
            $additionalFields = ", parent_name = '$parent_name', parent_email = '$parent_email', 
                                  phone = '$phone', grade = '$grade', subjects = '$subjects', 
                                  notes = '$notes'";
        } else if ($userType === 'tutors') {
            $link = sanitize($_POST['link'] ?? '');
            $additionalFields = ", link = '$link'";
        }
        
        $sql = "UPDATE $table SET name = '$name', email = '$email'$passwordField$additionalFields WHERE $idField = $userId";
        
        if ($conn->query($sql) === TRUE) {
            $message = "User updated successfully!";
        } else {
            $message = "Error updating user: " . $conn->error;
        }
    } else {
        // Add new user
        if (empty($_POST['password'])) {
            $message = "Password is required for new users.";
        } else {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            
            if ($userType === 'students') {
                $parent_name = sanitize($_POST['parent_name'] ?? '');
                $parent_email = sanitize($_POST['parent_email'] ?? '');
                $phone = sanitize($_POST['phone'] ?? '');
                $grade = sanitize($_POST['grade'] ?? '');
                $subjects = sanitize($_POST['subjects'] ?? '');
                $notes = sanitize($_POST['notes'] ?? '');
                
                $sql = "INSERT INTO students (name, email, password, parent_name, parent_email, phone, grade, subjects, notes)
                        VALUES ('$name', '$email', '$password', '$parent_name', '$parent_email', '$phone', '$grade', '$subjects', '$notes')";
            } else {
                $link = sanitize($_POST['link'] ?? '');
                
                $sql = "INSERT INTO tutors (name, email, password, link)
                        VALUES ('$name', '$email', '$password', '$link')";
            }
            
            if ($conn->query($sql) === TRUE) {
                $message = "New user created successfully!";
            } else {
                $message = "Error: " . $sql . "<br>" . $conn->error;
            }
        }
    }
}

// Handle Delete User
if (isset($_GET['delete'])) {
    $userId = (int)$_GET['delete'];
    $userType = sanitize($_GET['userType']);
    $table = $userType === 'tutors' ? 'tutors' : 'students';
    $idField = $userType === 'tutors' ? 'id' : 'student_id';
    
    $sql = "DELETE FROM $table WHERE $idField = $userId";
    
    if ($conn->query($sql) === TRUE) {
        $message = "User deleted successfully!";
    } else {
        $message = "Error deleting user: " . $conn->error;
    }
}

// Get user data for editing
$userData = null;
if (isset($_GET['edit'])) {
    $userId = (int)$_GET['edit'];
    $userType = sanitize($_GET['userType']);
    $table = $userType === 'tutors' ? 'tutors' : 'students';
    $idField = $userType === 'tutors' ? 'id' : 'student_id';
    
    $sql = "SELECT * FROM $table WHERE $idField = $userId";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $userData = $result->fetch_assoc();
    }
}

// Get all users
function getUsers($type) {
    global $conn;
    $table = $type === 'tutors' ? 'tutors' : 'students';
    $idField = $type === 'tutors' ? 'id' : 'student_id';
    
    $sql = "SELECT * FROM $table ORDER BY name";
    $result = $conn->query($sql);
    
    $users = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
    
    return $users;
}

$students = getUsers('students');
$tutors = getUsers('tutors');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bloom - User Management</title>
    <script>
        // JavaScript to enhance the user experience
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle between user types
            const userTypeButtons = document.querySelectorAll('.userTypeBtn');
            userTypeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const userType = this.getAttribute('data-type');
                    document.getElementById('studentSection').style.display = userType === 'students' ? 'block' : 'none';
                    document.getElementById('tutorSection').style.display = userType === 'tutors' ? 'block' : 'none';
                    document.getElementById('userTypeInput').value = userType;
                    
                    // Update form fields based on user type
                    updateFormFields(userType);
                    
                    // Update active button
                    userTypeButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                });
            });
            
            // User Form Toggle
            document.getElementById('showFormBtn').addEventListener('click', function() {
                const formContainer = document.getElementById('userFormContainer');
                formContainer.style.display = formContainer.style.display === 'none' ? 'block' : 'none';
                this.innerText = formContainer.style.display === 'none' ? 'Add New User' : 'Hide Form';
                
                // Clear form when showing it
                if (formContainer.style.display === 'block') {
                    document.getElementById('userForm').reset();
                    document.getElementById('userId').value = '';
                    document.getElementById('formTitle').innerText = 'Add New User';
                }
            });
            
            // Delete confirmation
            document.querySelectorAll('.deleteBtn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                        e.preventDefault();
                    }
                });
            });
            
            // Function to update form fields based on user type
            function updateFormFields(userType) {
                const studentFields = document.getElementById('studentSpecificFields');
                const tutorFields = document.getElementById('tutorSpecificFields');
                
                studentFields.style.display = userType === 'students' ? 'block' : 'none';
                tutorFields.style.display = userType === 'tutors' ? 'block' : 'none';
            }
            
            // Filter tables functionality
            document.getElementById('studentSearchInput').addEventListener('keyup', function() {
                filterTable('studentTable', this.value);
            });
            
            document.getElementById('tutorSearchInput').addEventListener('keyup', function() {
                filterTable('tutorTable', this.value);
            });
            
            function filterTable(tableId, query) {
                const table = document.getElementById(tableId);
                const tr = table.getElementsByTagName('tr');
                
                for (let i = 1; i < tr.length; i++) { // Start at 1 to skip header row
                    let visible = false;
                    const td = tr[i].getElementsByTagName('td');
                    
                    for (let j = 0; j < td.length; j++) {
                        if (td[j]) {
                            const txtValue = td[j].textContent || td[j].innerText;
                            if (txtValue.toLowerCase().indexOf(query.toLowerCase()) > -1) {
                                visible = true;
                                break;
                            }
                        }
                    }
                    
                    tr[i].style.display = visible ? '' : 'none';
                }
            }
            
            // Initialize form fields based on current user type
            updateFormFields(document.getElementById('userTypeInput').value);
            
            // Show appropriate section based on user type in URL or default
            const currentUserType = '<?php echo $userType; ?>';
            document.querySelector(`.userTypeBtn[data-type="${currentUserType}"]`).click();
            
            // Display message if it exists
            const message = '<?php echo $message; ?>';
            if (message) {
                const messageDiv = document.getElementById('message');
                messageDiv.innerText = message;
                messageDiv.style.display = 'block';
                
                // Hide message after 5 seconds
                setTimeout(() => {
                    messageDiv.style.display = 'none';
                }, 5000);
            }
        });
        
        // Function to edit user
        function editUser(userId, userType) {
            // Get user data from the row
            const userForm = document.getElementById('userForm');
            const formTitle = document.getElementById('formTitle');
            const formContainer = document.getElementById('userFormContainer');
            
            // Show the form
            formContainer.style.display = 'block';
            document.getElementById('showFormBtn').innerText = 'Hide Form';
            
            // Set form title
            formTitle.innerText = 'Edit User';
            
            // Populate hidden fields
            document.getElementById('userId').value = userId;
            document.getElementById('userTypeInput').value = userType;
            
            // Update active user type button
            document.querySelectorAll('.userTypeBtn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.getAttribute('data-type') === userType) {
                    btn.classList.add('active');
                }
            });
            
            // Update form fields visibility
            const studentFields = document.getElementById('studentSpecificFields');
            const tutorFields = document.getElementById('tutorSpecificFields');
            
            studentFields.style.display = userType === 'students' ? 'block' : 'none';
            tutorFields.style.display = userType === 'tutors' ? 'block' : 'none';
            
            // Make an AJAX request to get user data
            const xhr = new XMLHttpRequest();
            xhr.open('GET', `<?php echo $_SERVER['PHP_SELF']; ?>?getUserData=true&userId=${userId}&userType=${userType}`, true);
            
            xhr.onload = function() {
                if (this.status === 200) {
                    const response = JSON.parse(this.responseText);
                    
                    // Populate form fields
                    document.getElementById('name').value = response.name;
                    document.getElementById('email').value = response.email;
                    document.getElementById('password').value = ''; // Don't populate password
                    
                    if (userType === 'students') {
                        document.getElementById('parent_name').value = response.parent_name || '';
                        document.getElementById('parent_email').value = response.parent_email || '';
                        document.getElementById('phone').value = response.phone || '';
                        document.getElementById('grade').value = response.grade || '';
                        document.getElementById('subjects').value = response.subjects || '';
                        document.getElementById('notes').value = response.notes || '';
                    } else if (userType === 'tutors') {
                        document.getElementById('link').value = response.link || '';
                    }
                    
                    // Scroll to form
                    formContainer.scrollIntoView({ behavior: 'smooth' });
                }
            };
            
            xhr.send();
        }
    </script>
</head>
<body>
    <h1>Bloom User Management</h1>
    
    <div id="message" style="display: none; padding: 10px; margin: 10px 0; background-color: #f8f9fa; border: 1px solid #ddd;"></div>
    
    <div style="margin-bottom: 20px;">
        <button class="userTypeBtn active" data-type="students">Manage Students</button>
        <button class="userTypeBtn" data-type="tutors">Manage Tutors</button>
        <button id="showFormBtn" style="margin-left: 20px;">Add New User</button>
    </div>
    
    <!-- User Form -->
    <div id="userFormContainer" style="display: none; margin-bottom: 30px; padding: 20px; border: 1px solid #ddd;">
        <h2 id="formTitle">Add New User</h2>
        
        <form id="userForm" method="post" action="">
            <input type="hidden" id="userId" name="userId" value="">
            <input type="hidden" id="userTypeInput" name="userType" value="students">
            
            <div style="margin-bottom: 15px;">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required style="width: 300px;">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required style="width: 300px;">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" style="width: 300px;">
                <small><?php echo isset($_GET['edit']) ? '(Leave blank to keep current password)' : '(Required for new users)'; ?></small>
            </div>
            
            <!-- Student-specific fields -->
            <div id="studentSpecificFields" style="display: none;">
                <div style="margin-bottom: 15px;">
                    <label for="parent_name">Parent Name:</label>
                    <input type="text" id="parent_name" name="parent_name" style="width: 300px;">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label for="parent_email">Parent Email:</label>
                    <input type="email" id="parent_email" name="parent_email" style="width: 300px;">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label for="phone">Phone:</label>
                    <input type="text" id="phone" name="phone" style="width: 300px;">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label for="grade">Grade:</label>
                    <input type="text" id="grade" name="grade" style="width: 300px;">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label for="subjects">Subjects:</label>
                    <input type="text" id="subjects" name="subjects" style="width: 300px;">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label for="notes">Notes:</label>
                    <textarea id="notes" name="notes" rows="4" style="width: 300px;"></textarea>
                </div>
            </div>
            
            <!-- Tutor-specific fields -->
            <div id="tutorSpecificFields" style="display: none;">
                <div style="margin-bottom: 15px;">
                    <label for="link">Meeting Link:</label>
                    <input type="text" id="link" name="link" style="width: 300px;">
                </div>
            </div>
            
            <div>
                <button type="submit" name="saveUser">Save User</button>
                <button type="button" onclick="document.getElementById('userFormContainer').style.display='none'; document.getElementById('showFormBtn').innerText='Add New User';">Cancel</button>
            </div>
        </form>
    </div>
    
    <!-- Students Section -->
    <div id="studentSection" style="display: block;">
        <h2>Students</h2>
        <input type="text" id="studentSearchInput" placeholder="Search students..." style="margin-bottom: 10px; width: 300px;">
        
        <table id="studentTable" border="1" style="width: 100%; border-collapse: collapse;">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Parent</th>
                <th>Grade</th>
                <th>Subjects</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($students as $student): ?>
            <tr>
                <td><?php echo $student['student_id']; ?></td>
                <td><?php echo htmlspecialchars($student['name']); ?></td>
                <td><?php echo htmlspecialchars($student['email']); ?></td>
                <td><?php echo htmlspecialchars($student['parent_name'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($student['grade'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($student['subjects'] ?? ''); ?></td>
                <td><?php echo date('Y-m-d', strtotime($student['created_at'])); ?></td>
                <td>
                    <button onclick="editUser(<?php echo $student['student_id']; ?>, 'students')">Edit</button>
                    <a href="?delete=<?php echo $student['student_id']; ?>&userType=students" class="deleteBtn">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($students)): ?>
            <tr>
                <td colspan="8" style="text-align: center;">No students found.</td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
    
    <!-- Tutors Section -->
    <div id="tutorSection" style="display: none;">
        <h2>Tutors</h2>
        <input type="text" id="tutorSearchInput" placeholder="Search tutors..." style="margin-bottom: 10px; width: 300px;">
        
        <table id="tutorTable" border="1" style="width: 100%; border-collapse: collapse;">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Meeting Link</th>
                <th>Created</th>
                <th>Updated</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($tutors as $tutor): ?>
            <tr>
                <td><?php echo $tutor['id']; ?></td>
                <td><?php echo htmlspecialchars($tutor['name']); ?></td>
                <td><?php echo htmlspecialchars($tutor['email']); ?></td>
                <td><?php echo htmlspecialchars($tutor['link'] ?? ''); ?></td>
                <td><?php echo date('Y-m-d', strtotime($tutor['created_at'])); ?></td>
                <td><?php echo date('Y-m-d', strtotime($tutor['updated_at'])); ?></td>
                <td>
                    <button onclick="editUser(<?php echo $tutor['id']; ?>, 'tutors')">Edit</button>
                    <a href="?delete=<?php echo $tutor['id']; ?>&userType=tutors" class="deleteBtn">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($tutors)): ?>
            <tr>
                <td colspan="7" style="text-align: center;">No tutors found.</td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
    
    <?php
    // AJAX endpoint to get user data
    if (isset($_GET['getUserData'])) {
        header('Content-Type: application/json');
        
        $userId = (int)$_GET['userId'];
        $userType = $_GET['userType'];
        $table = $userType === 'tutors' ? 'tutors' : 'students';
        $idField = $userType === 'tutors' ? 'id' : 'student_id';
        
        $sql = "SELECT * FROM $table WHERE $idField = $userId";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            echo json_encode($result->fetch_assoc());
        } else {
            echo json_encode(['error' => 'User not found']);
        }
        
        $conn->close();
        exit;
    }
    
    // Close the database connection
    $conn->close();
    ?>
</body>
</html>