<?php
session_start();

// Check if the user is logged in and is a staff member
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
        header("Location: ../login/login.php");
        exit();
}

// Include the database connection
include '../config/db.php';

// Function to get all users


// Function to get a user's borrowing history
function getUserHistory($user_id) {
    global $pdo;
    $sql = "SELECT books.title, borrow_transactions.borrow_date, borrow_transactions.return_date 
            FROM borrow_transactions 
            JOIN books ON borrow_transactions.book_id = books.id
            WHERE borrow_transactions.user_id = ? AND borrow_transactions.status = 'returned'
            ORDER BY borrow_transactions.borrow_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get current transactions for a user
function getCurrentTransactions($user_id) {
    global $pdo;
    $sql = "SELECT books.title, borrow_transactions.borrow_date, borrow_transactions.due_date 
            FROM borrow_transactions 
            JOIN books ON borrow_transactions.book_id = books.id
            WHERE borrow_transactions.user_id = ? AND borrow_transactions.status = 'borrowed'
            ORDER BY borrow_transactions.borrow_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

// Check for search input from staff
$searchTerm = '';
if (isset($_POST['search']) && $_POST['user_type'] == 'staff') {
    $searchTerm = $_POST['search'];
}

$users = getUsers($searchTerm);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library User Management</title>
    <link rel="stylesheet" href="/style/style.css">
</head>
<body>

<!-- Navbar -->
<div class="navbar">
    <h2>Library User Management</h2>
</div>

<!-- Container -->
<div class="container">

    <!-- Add New User Form -->
    <h3>Add User</h3>
    <form method="POST" action="user_manage.php">
        <input type="text" name="first_name" placeholder="First Name" required>
        <input type="text" name="middle_name" placeholder="Middle Name (Optional)">
        <input type="text" name="last_name" placeholder="Last Name" required>
        <input type="text" name="suffix" placeholder="Suffix (Optional)">
        <input type="text" name="street" placeholder="Street (Optional)">
        <input type="text" name="purok" placeholder="Purok (Optional)">
        <input type="text" name="barangay" placeholder="Barangay" required>
        <input type="text" name="city" placeholder="City" required>
        <input type="text" name="phone_number" placeholder="Phone Number" required>
        <input type="date" name="date_of_birth" placeholder="Date of Birth" required>
        <input type="email" name="email" placeholder="Email" required>
        <select name="user_type">
            <option value="admin">Admin</option>
            <option value="staff">Staff</option>
            <option value="faculty">Faculty</option>
            <option value="student">Student</option>
        </select>
        <button type="submit" name="add_user">Add User</button>
    </form>
    <!-- Search and Filter Form for Staff -->
    <?php if (isset($_POST['user_type']) && $_POST['user_type'] == 'staff'): ?>

        <h3>Search Users</h3>
        <form method="POST" action="user_manage.php">
            <input type="text" name="search" placeholder="Search by Name or Membership ID">
            <button type="submit">Search</button>
        </form>
    <?php endif; ?>

    <!-- Users List -->
    <h3>Users List</h3>
    <table>
        <tr>
            <th>Membership ID</th>
            <th>User Type</th>
            <th>Name</th>
            <th>Email</th>
            <th>Address</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?php echo htmlspecialchars($user['membership_id']); ?></td>
                <td><?php echo htmlspecialchars($user['user_type']); ?></td>
                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name'] . ' ' . $user['suffix']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td><?php echo htmlspecialchars($user['street'] . ' ' . $user['purok'] . ' ' . $user['barangay'] . ' ' . $user['city']); ?></td>
                <td>
    <a href="borrow_history.php?user_id=<?php echo $user['id']; ?>">View Borrowing History</a> |
    <a href="transact_user.php?user_id=<?php echo $user['id']; ?>">View Current Transactions</a> |
    <a href="user_edit.php?edit_user=<?php echo $user['id']; ?>">Edit</a> |
    <a href="user_manage.php?delete_user=<?php echo $user['id']; ?>">Delete</a>
</td>
            </tr>
        <?php endforeach; ?>
    </table>
</div> <!-- End Container -->

</body>
</html>