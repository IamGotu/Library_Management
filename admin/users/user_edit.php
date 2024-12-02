<?php
session_start();

// Check if the user is logged in and is a staff member
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login/login.php");
    exit();
}

// Include any necessary files like database connection if needed
include '../config/db.php';

// Initialize $history to avoid undefined variable warning
$history = [];

function getUserById($user_id) {
    global $pdo;
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to get a user's borrowing history
function getUserHistory($user_id) {
    global $pdo;
    $sql = "SELECT * FROM borrow_transactions WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Check if the user is editing
if (isset($_GET['edit_user'])) {
    $user_id = $_GET['edit_user'];
    $user = getUserById($user_id);
    $history = getUserHistory($user_id);  // Fetch the history only if editing
}

// Function to update the user's details
if (isset($_POST['edit_user'])) {
    $user_id = $_POST['user_id'];
    $first_name = $_POST['first_name'];
    $middle_name = $_POST['middle_name'];
    $last_name = $_POST['last_name'];
    $suffix = $_POST['suffix'];
    $email = $_POST['email'];
    $user_type = $_POST['user_type'];

    // address
    $street = $_POST['street'] ?? null;
    $purok = $_POST['purok'] ?? null;
    $barangay = $_POST['barangay'] ?? null;
    $city = $_POST['city'] ?? null;
    
    $phone_number = $_POST['phone_number'] ?? null;
    $date_of_birth = $_POST['date_of_birth'] ?? null;

    // Update user in the database
    $sql = "UPDATE users SET 
                first_name = ?, middle_name = ?, last_name = ?, suffix = ?, email = ?, user_type = ?, street = ?, purok = ?, barangay = ?, city = ?, 
                phone_number = ?, date_of_birth = ?
            WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $first_name, $middle_name, $last_name, $suffix, $email, $user_type, $street, $purok, $barangay, $city, 
        $phone_number, $date_of_birth, $user_id
    ]);

    // Fetch the updated user data to display
    $user = getUserById($user_id);
    $history = getUserHistory($user_id);  // Re-fetch the history after update
    $successMessage = "User updated successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <style>
        /* Global Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
        }

        body {
            background-color: #f4f6f9;
            color: #333;
            font-size: 14px;
            line-height: 1.6;
            padding-top: 40px;
        }

        h2, h3 {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
        }

        h3 {
            color: #1e88e5;
        }

        /* Container */
        .container {
            width: 90%;
            margin: 0 auto;
        }

        /* Forms */
        form {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        form input, form select, form button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ddd;
            font-size: 14px;
        }

        form button {
            background-color: #1e88e5;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }

        form button:hover {
            background-color: #1565c0;
        }

        /* Table */
        table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }

        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        table th {
            background-color: #1e88e5;
            color: white;
        }

        table td {
            background-color: #f9f9f9;
        }

        table tr:hover {
            background-color: #f1f1f1;
        }

        /* Button Styling */
        button {
            background-color: #e74c3c;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background-color: #c0392b;
        }

        /* Success Message Styling */
        .success-message {
            background-color: #28a745;
            color: white;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        /* Go Back Button */
        .go-back-btn {
            background-color: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
        }

        .go-back-btn:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Edit User</h2>

    <!-- Success message after update -->
    <?php if (isset($successMessage)): ?>
        <div class="success-message"><?php echo $successMessage; ?></div>
    <?php endif; ?>

    <!-- Edit User Form -->
    <form method="POST" action="user_edit.php" enctype="multipart/form-data">
    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
    
    <!-- Name -->
    <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" placeholder="First Name" required>
    <input type="text" name="middle_name" value="<?php echo htmlspecialchars($user['middle_name']); ?>" placeholder="Middle Name (Optional)">
    <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" placeholder="Last Name" required>
    <input type="text" name="suffix" value="<?php echo htmlspecialchars($user['suffix']); ?>" placeholder="Suffix (Optional)">
    
    <!-- Email -->
    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" placeholder="Email" required>
    
    <select name="user_type">
        <option value="student" <?php echo $user['user_type'] == 'student' ? 'selected' : ''; ?>>Student</option>
        <option value="faculty" <?php echo $user['user_type'] == 'faculty' ? 'selected' : ''; ?>>Faculty</option>
        <option value="staff" <?php echo $user['user_type'] == 'staff' ? 'selected' : ''; ?>>Staff</option>
    </select>
    
    <!-- Address -->
    <input type="text" name="street" value="<?php echo htmlspecialchars($user['street']); ?>" placeholder="Street (Optional)">
        <input type="text" name="purok" value="<?php echo htmlspecialchars($user['purok']); ?>" placeholder="Purok (Optional)">
        <input type="text" name="barangay" value="<?php echo htmlspecialchars($user['barangay']); ?>" placeholder="Barangay" required>
        <input type="text" name="city" value="<?php echo htmlspecialchars($user['city']); ?>" placeholder="City" required>
        <input type="text" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number']); ?>" placeholder="Phone Number" required>
        <input type="date" name="date_of_birth" value="<?php echo htmlspecialchars($user['date_of_birth']); ?>" placeholder="Date of Birth" required>
        
        <button type="submit" name="edit_user">Update User</button>
    </form>

    <h3>Borrowing History</h3>
    <?php if ($history): ?>
        <table>
            <tr>
                <th>Book ID</th>
                <th>Borrow Date</th>
                <th>Due Date</th>
                <th>Status</th>
            </tr>
            <?php foreach ($history as $transaction): ?>
                <tr>
                    <td><?php echo htmlspecialchars($transaction['book_id']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['borrow_date']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['due_date']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['status']); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>No borrowing history available.</p>
    <?php endif; ?>

    <!-- Go Back Button -->
    <a href="user_manage.php" class="go-back-btn">Go Back to User List</a>
</div>

</body>
</html>