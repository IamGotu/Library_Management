<?php
session_start();

// Check if the user is logged in and is a staff member
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'staff') {
    header("Location: ../login/login.php");
    exit();
}

// Include the database connection
include '../config/db.php';

try {
    // Query to fetch all users (faculty and students) who have borrow transactions
    $query = "
        SELECT DISTINCT
            u.id,
            u.first_name,
            u.last_name,
            u.user_type
        FROM users u
        INNER JOIN borrow_transactions bt ON u.id = bt.user_id
        WHERE u.user_type IN ('student', 'faculty')
        ORDER BY u.last_name, u.first_name
    ";

    // Prepare and execute the statement
    $stmt = $pdo->prepare($query);
    $stmt->execute();

    // Fetch all results
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return Books</title>
</head>
<body>
    <h1>All Users</h1>
    <table border="1">
        <thead>
            <tr>
                <th>Name</th>
                <th>User Type</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($users)): ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst($user['user_type'])); ?></td>
                        <td><a href="return_user.php?user_id=<?php echo $user['id']; ?>">View Borrowed Items</a></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3">No users found with borrow transactions.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
        <!-- Go Back Button -->
        <a href="dashboard.php" class="go-back-btn">Go Back to Dashboard</a>

</body>
</html>