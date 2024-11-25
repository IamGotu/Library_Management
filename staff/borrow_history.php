<?php
session_start();

// Check if the user is logged in and is a staff member
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'staff') {
    header("Location: ../login/login.php");
    exit();
}

// Include the database connection
include '../config/db.php';

if (!isset($_GET['user_id'])) {
    echo "User ID not specified.";
    exit();
}

$user_id = $_GET['user_id'];

// Function to get a user's borrowing history
function getUserHistory($user_id) {
    global $pdo;
    $sql = "SELECT lr.Title, bt.borrow_date, bt.return_date 
            FROM borrow_transactions bt
            JOIN libraryresources lr ON bt.resource_id = lr.ResourceID
            WHERE bt.user_id = ? AND bt.status = 'returned'
            ORDER BY bt.borrow_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$history = getUserHistory($user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrowing History</title>
</head>
<body>
    <h1>Borrowing History for User ID: <?php echo htmlspecialchars($user_id); ?></h1>
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Borrow Date</th>
                <th>Return Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($history as $record): ?>
                <tr>
                    <td><?php echo htmlspecialchars($record['Title']); ?></td>
                    <td><?php echo htmlspecialchars($record['borrow_date']); ?></td>
                    <td><?php echo htmlspecialchars($record['return_date']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <a href="user_manage.php">Back to User Management</a>
</body>
</html>
