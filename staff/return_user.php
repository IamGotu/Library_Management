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

try {
    // Fetch borrow transactions for the specific user
    $stmt = $pdo->prepare("SELECT bt.id, bt.resource_id, bt.borrow_date, bt.due_date, bt.status, lr.Title 
                           FROM borrow_transactions bt
                           JOIN libraryresources lr ON bt.resource_id = lr.ResourceID
                           WHERE bt.user_id = :user_id AND bt.status = 'borrowed'");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['transaction_id'])) {
    // Handle the return process
    $transaction_id = $_POST['transaction_id'];

    try {
        // Update the borrow transaction status to 'returned'
        $stmt = $pdo->prepare("UPDATE borrow_transactions SET status = 'returned', return_date = NOW() WHERE id = :transaction_id");
        $stmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
        $stmt->execute();

        // Update the library resource availability to 'Available'
        $stmt = $pdo->prepare("UPDATE libraryresources SET AvailabilityStatus = 'Available' WHERE ResourceID = (SELECT resource_id FROM borrow_transactions WHERE id = :transaction_id LIMIT 1)");
        $stmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
        $stmt->execute();

        echo "Item returned successfully!";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return User's Borrowed Items</title>
</head>
<body>
    <h1>Borrowed Items for User ID: <?php echo $user_id; ?></h1>
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Borrow Date</th>
                <th>Due Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $transaction): ?>
                <tr>
                    <td><?php echo $transaction['Title']; ?></td>
                    <td><?php echo $transaction['borrow_date']; ?></td>
                    <td><?php echo $transaction['due_date']; ?></td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="transaction_id" value="<?php echo $transaction['id']; ?>">
                            <button type="submit">Return</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
        <!-- Go Back Button -->
        <a href="return.php" class="go-back-btn">Back</a>
</body>
</html>
