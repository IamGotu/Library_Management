<?php
session_start();

// Check if the user is logged in and is a staff member
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'staff') {
    header("Location: ../login/login.php");
    exit();
}

// Include any necessary files like database connection if needed
include '../config/db.php';

// Function to get current transactions for a user
function getCurrentTransactions($user_id) {
    global $pdo;
    // Corrected SQL query using 'BookID' instead of 'id'
    $sql = "SELECT books.Title, borrow_transactions.borrow_date, borrow_transactions.due_date, borrow_transactions.status 
            FROM borrow_transactions 
            JOIN books ON borrow_transactions.book_id = books.BookID
            WHERE borrow_transactions.user_id = ? AND borrow_transactions.status = 'borrowed'
            ORDER BY borrow_transactions.borrow_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Check if the user is viewing their current transactions
if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];
    $transactions = getCurrentTransactions($user_id);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Current Transactions</title>
    <link rel="stylesheet" href="/style/style.css">
</head>
<body>

<div class="navbar">
    <h2>User Current Transactions</h2>
</div>

<div class="container">
    <h3>Current Borrowed Books</h3>
    <?php if ($transactions): ?>
        <table>
            <tr>
                <th>Book Title</th>
                <th>Borrow Date</th>
                <th>Due Date</th>
                <th>Status</th>
            </tr>
            <?php foreach ($transactions as $transaction): ?>
                <tr>
                    <td><?php echo htmlspecialchars($transaction['Title']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['borrow_date']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['due_date']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['status']); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>No current transactions available.</p>
    <?php endif; ?>
</div>

</body>
</html>
