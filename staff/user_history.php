<?php
session_start();

// Check if the user is logged in and is a staff member
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'staff') {
    header("Location: ../login/login.php");
    exit();
}

// Include any necessary files like database connection if needed
include '../config/db.php';

// Function to get a user's borrowing history
function getUserHistory($user_id) {
    global $pdo;
    // Corrected SQL query with no comment in the query string
    $sql = "SELECT books.Title, borrow_transactions.borrow_date, borrow_transactions.return_date, borrow_transactions.status 
            FROM borrow_transactions 
            JOIN books ON borrow_transactions.book_id = books.BookID
            WHERE borrow_transactions.user_id = ? AND borrow_transactions.status = 'returned'
            ORDER BY borrow_transactions.borrow_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Check if the user is viewing their history
if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];
    $history = getUserHistory($user_id);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Borrowing History</title>
    <link rel="stylesheet" href="/style/style.css">
</head>
<body>

<div class="navbar">
    <h2>User Borrowing History</h2>
</div>

<div class="container">
    <h3>Borrowing History</h3>
    <?php if ($history): ?>
        <table>
            <tr>
                <th>Book Title</th>
                <th>Borrow Date</th>
                <th>Return Date</th>
                <th>Status</th>
            </tr>
            <?php foreach ($history as $transaction): ?>
                <tr>
                    <td><?php echo htmlspecialchars($transaction['Title']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['borrow_date']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['return_date']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['status']); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>No borrowing history available.</p>
    <?php endif; ?>
</div>

</body>
</html>
