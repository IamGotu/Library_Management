<?php
session_start();

// Check if the user is logged in and is a staff member
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
        header("Location: ../login/login.php");
        exit();
}

// Include the database connection
include '../../config/db.php';

if (isset($_GET['membership_id'])) {
    $user_id = $_GET['membership_id'];

    // Fetch user details from the database
    $sql = "SELECT * FROM users WHERE membership_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);

    // Fetch the user data
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Return user details as JSON if found, else return an error
    if ($user) {
        echo json_encode($user);
    } else {
        echo json_encode(['error' => 'User not found']);
    }
} else {
    echo json_encode(['error' => 'No user ID provided']);
}

// Function to delete a user
if (isset($_GET['delete_user'])) {
    $user_id = $_GET['delete_user'];
    $sql = "DELETE FROM users WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    echo "User deleted successfully!";
}

// Function to generate a unique Membership ID
function generateMembershipID($userType) {
    global $pdo;
    
    // Define prefix based on user type
    $prefix = '';
    switch ($userType) {
        case 'student':
            $prefix = '400';
            break;
        case 'faculty':
            $prefix = '300';
            break;
        case 'staff':
            $prefix = '200';
            break;
        case 'admin':
            $prefix = '100';
            break;  
    }
    
    // Generate a unique 7-digit membership ID
    do {
        $membershipID = $prefix . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        
        // Check if this membership ID already exists
        $sql = "SELECT COUNT(*) FROM users WHERE membership_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$membershipID]);
        $exists = $stmt->fetchColumn();
    } while ($exists > 0); // Regenerate if ID already exists
    
    return $membershipID;
}

if (isset($_POST['add_user'])) {
    $first_name = $_POST['first_name'];
    $middle_name = $_POST['middle_name'] ?? null;
    $last_name = $_POST['last_name'];
    $suffix = $_POST['suffix'] ?? null;
    $email = $_POST['email'];
    $user_type = $_POST['user_type'];
    $street = $_POST['street'] ?? null;
    $purok = $_POST['purok'] ?? null;
    $barangay = $_POST['barangay'] ?? null;
    $city = $_POST['city'] ?? null;
    $phone_number = $_POST['phone_number'] ?? null;
    $date_of_birth = $_POST['date_of_birth'] ?? null;

    // Check if the email already exists
    $sql_check = "SELECT * FROM users WHERE email = ?";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([$email]);

    if ($stmt_check->rowCount() > 0) {
        echo "The email address is already in use. Please use a different one.";
    } else {
        // Generate membership ID
        $membership_id = generateMembershipID($user_type);

        // Generate password: last_name + membership_id
        $password = $last_name . $membership_id;

        // Hash the password for security
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert the new user with membership ID and password
        $sql = "INSERT INTO users (first_name, middle_name, last_name, suffix, email, user_type, membership_id, password, 
                street, purok, barangay, city, phone_number, date_of_birth) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$first_name, $middle_name, $last_name, $suffix, $email, $user_type, $membership_id, $hashed_password, 
                        $street, $purok, $barangay, $city, $phone_number, $date_of_birth]);
        echo "User added successfully with Membership ID: $membership_id. Password: $password";
    }
}

function getUsers($searchTerm = '') {
    global $pdo;
    
    $sql = "SELECT * FROM users";
    if ($searchTerm) {
        $sql .= " WHERE name LIKE ? OR membership_id LIKE ?";
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($searchTerm ? ["%$searchTerm%", "%$searchTerm%"] : []);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Check for search input from admin
$searchTerm = '';
if (isset($_POST['search']) && isset($_POST['user_type']) && $_POST['user_type'] == 'admin') {
    $searchTerm = $_POST['search'];
}

$users = getUsers($searchTerm);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../components/css/view.css">
    <link rel="icon" href="/components/Image/book.png" type="image/x-icon">
</head>
<body>

    <!-- Navbar -->
    <?php include '../layout/navbar.php'; ?>

    <!-- Main Content -->
    <div class="content-wrapper">
        <div class="container">
            <div class="centered-heading">
            <h2>User Management</h2>
            </div>

            <!-- Search Users Form -->
            <form method="POST" action="user_manage.php" class="mb-4">
                <div class="row g-3">
                    <div class="mt-3 col-12">
                        <label for="search" class="form-label">Search:</label>
                        <input type="text" name="search" id="search" class="form-control mt-3" placeholder="Enter Memembership ID or Name" value="<?php echo htmlspecialchars($searchTerm); ?>">
                        <button type="submit" class="btn btn-primary form-control mt-3">Search</button>
                        <!-- Add User Modal -->
                        <button type="button" class="btn btn-primary mt-3 w-100" data-bs-toggle="modal" data-bs-target="#addModal">
                            Add User
                        </button>
                    </div>
                </div>
            </form>

            <!-- Add User Modal -->
            <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addModalLabel" style="color: black;">Add New User</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Add New User Form -->
                            <form method="POST" action="user_manage.php">
                                <div class="row">
                                    <div class="col-md-6">
                                        <input type="text" name="first_name" class="form-control mb-3" placeholder="First Name" required>

                                        <input type="text" name="last_name" class="form-control mb-3" placeholder="Last Name" required>
                                        <input type="email" name="email" class="form-control mb-3" placeholder="Email" required>
                                        <input type="text" name="street" class="form-control mb-3" placeholder="Street (Optional)">
                                        <input type="text" name="barangay" class="form-control mb-3" placeholder="Barangay" required>
                                        <input type="text" name="city" class="form-control mb-3" placeholder="City" required>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" name="middle_name" class="form-control mb-3" placeholder="Middle Name (Optional)">
                                        <input type="text" name="suffix" class="form-control mb-3" placeholder="Suffix (Optional)">
                                        <input type="text" name="phone_number" class="form-control mb-3" placeholder="Phone Number" required>
                                        <input type="date" name="date_of_birth" class="form-control mb-3" required>
                                        <select name="user_type" class="form-select mb-3" required>
                                            <option value="admin">Admin</option>
                                            <option value="staff">Staff</option>
                                            <option value="faculty">Faculty</option>
                                            <option value="student">Student</option>
                                        </select>
                                        <button type="submit" name="add_user" class="btn btn-success w-100">Add User</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit User Modal -->
            <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" style="color: black;" id="editModalLabel">Edit User</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Edit User Form -->
                            <form method="POST" action="user_manage.php">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                        <input type="text" name="membership_id" class="form-control mb-3" value="<?php echo $user['membership_id']; ?>" placeholder="Membership ID" required>
                                        <input type="text" name="first_name" class="form-control mb-3" value="<?php echo $user['first_name']; ?>" placeholder="First Name" required>
                                        <input type="text" name="last_name" class="form-control mb-3" value="<?php echo $user['last_name']; ?>" placeholder="Last Name" required>
                                        <input type="email" name="email" class="form-control mb-3" value="<?php echo $user['email']; ?>" placeholder="Email" required>
                                        <input type="text" name="street" class="form-control mb-3" value="<?php echo $user['street']; ?>" placeholder="Street (Optional)">
                                        <input type="text" name="barangay" class="form-control mb-3" value="<?php echo $user['barangay']; ?>" placeholder="Barangay" required>
                                        <input type="text" name="city" class="form-control mb-3" value="<?php echo $user['city']; ?>" placeholder="City" required>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" name="middle_name" class="form-control mb-3" value="<?php echo $user['middle_name']; ?>" placeholder="Middle Name (Optional)">
                                        <input type="text" name="suffix" class="form-control mb-3" value="<?php echo $user['suffix']; ?>" placeholder="Suffix (Optional)">
                                        <input type="text" name="phone_number" class="form-control mb-3" value="<?php echo $user['phone_number']; ?>" placeholder="Phone Number" required>
                                        <input type="date" name="date_of_birth" class="form-control mb-3" value="<?php echo $user['date_of_birth']; ?>" required>
                                        <select name="user_type" class="form-select" required>
                                            <option value="admin" <?php echo ($user['user_type'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                            <option value="staff" <?php echo ($user['user_type'] == 'staff') ? 'selected' : ''; ?>>Staff</option>
                                            <option value="faculty" <?php echo ($user['user_type'] == 'faculty') ? 'selected' : ''; ?>>Faculty</option>
                                            <option value="student" <?php echo ($user['user_type'] == 'student') ? 'selected' : ''; ?>>Student</option>
                                        </select>
                                        <button type="submit" name="edit_user" class="btn btn-warning w-100 mt-3">Save Changes</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users List Table -->
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Membership ID</th>
                            <th>User Type</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Address</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['membership_id']); ?></td>
                                <td><?php echo htmlspecialchars($user['user_type']); ?></td>
                                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name'] . ' ' . $user['suffix']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['street'] . ' ' . $user['barangay'] . ' ' . $user['city']); ?></td>
                                <td>
                                    <!-- Edit User Modal -->
                                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editModal" data-id="<?php echo $user['membership_id']; ?>">Edit User</button>

                                    <a href="user_manage.php?delete_user=<?php echo $user['membership_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
        // When the edit button is clicked, fetch the user data and populate the modal
        $('#editModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget); // Button that triggered the modal
            var userId = button.data('id'); // Extract the user ID from the data-id attribute

            // Send an AJAX request to fetch the user details based on the userId
            $.ajax({
                url: 'fetch_user.php',  // This file will handle the database fetch
                method: 'GET',
                data: { user_id: userId },
                success: function(response) {
                    var user = JSON.parse(response);

                    if (user.error) {
                        alert(user.error);
                    } else {
                        // Populate the modal fields with the user data
                        $('input[name="first_name"]').val(user.first_name);
                        $('input[name="last_name"]').val(user.last_name);
                        $('input[name="email"]').val(user.email);
                        $('input[name="street"]').val(user.street);
                        $('input[name="barangay"]').val(user.barangay);
                        $('input[name="city"]').val(user.city);
                        $('input[name="middle_name"]').val(user.middle_name);
                        $('input[name="suffix"]').val(user.suffix);
                        $('input[name="phone_number"]').val(user.phone_number);
                        $('input[name="date_of_birth"]').val(user.date_of_birth);
                        $('select[name="user_type"]').val(user.user_type); // Select the user type option
                    }
                },
                error: function() {
                    alert('Error fetching user data');
                }
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>