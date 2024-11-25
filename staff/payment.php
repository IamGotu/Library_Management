<?php
session_start();

// Check if the user is logged in and is a staff member
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'staff') {
    header("Location: ../login/login.php");
    exit();
}

// Include the database connection
include '../config/db.php';

// Update fees if staff modifies or marks fees as paid
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['transaction_id'])) {
        $transactionId = $_POST['transaction_id'];  // Use the correct field from the form
        $action = $_POST['action'];

        try {
            if ($action === 'modify') {
                $newFee = $_POST['new_fee'];
                // Update the fee for the specific transaction, if it's overdue
                $query = "UPDATE borrow_transactions SET late_fee = ? WHERE id = ? AND status = 'overdue'";
                $stmt = $pdo->prepare($query);
                $stmt->execute([$newFee, $transactionId]);
            } elseif ($action === 'paid') {
                // Mark the specific transaction as paid, update the return_date and change status to returned
                $query = "UPDATE borrow_transactions SET status = 'returned', return_date = NOW(), late_fee = 0 WHERE id = ? AND status = 'overdue'";
                $stmt = $pdo->prepare($query);
                $stmt->execute([$transactionId]);
            }
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
            exit();
        }
    } else {
        echo "Transaction ID is missing!";
    }
}

// Fetch all users with overdue books and their late fees
try {
    $query = "
        SELECT 
            u.id AS user_id, 
            u.first_name, 
            u.last_name, 
            u.email, 
            u.phone_number, 
            bt.id AS transaction_id,  -- Use the correct column for the transaction
            bt.resource_id,
            bt.due_date,
            bt.return_date,
            IF(bt.return_date > bt.due_date, DATEDIFF(bt.return_date, bt.due_date) * 1.00, 0) AS late_fee
        FROM 
            users u
        JOIN 
            borrow_transactions bt ON u.id = bt.user_id
        WHERE
            bt.status = 'overdue'
    ";
    $stmt = $pdo->query($query);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Payments</title>
    <link rel="stylesheet" href="../book_manage/bookstyle.css">
</head>
<body>
    <h1>Payment Management</h1>
    
    <table border="1">
        <thead>
            <tr>
                <th>User ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Transaction ID</th>
                <th>Resource ID</th>
                <th>Due Date</th>
                <th>Return Date</th>
                <th>Late Fee</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                <tr>
                    <td><?php echo $row['user_id']; ?></td>
                    <td><?php echo $row['first_name'] . " " . $row['last_name']; ?></td>
                    <td><?php echo $row['email']; ?></td>
                    <td><?php echo $row['phone_number']; ?></td>
                    <td><?php echo $row['transaction_id']; ?></td>
                    <td><?php echo $row['resource_id']; ?></td>
                    <td><?php echo $row['due_date']; ?></td>
                    <td><?php echo $row['return_date'] ? $row['return_date'] : 'N/A'; ?></td>
                    <td><?php echo number_format($row['late_fee'], 2); ?></td>
                    <td>
                        <form method="post">
                            <!-- Pass the specific transaction ID and user ID -->
                            <input type="hidden" name="transaction_id" value="<?php echo $row['transaction_id']; ?>">
                            <input type="hidden" name="user_id" value="<?php echo $row['user_id']; ?>">
                            <input type="number" name="new_fee" step="0.01" min="0" placeholder="Enter new fee" value="<?php echo $row['late_fee']; ?>">
                            <button type="submit" name="action" value="modify">Update Fee</button>
                            <button type="submit" name="action" value="paid">Mark as Paid</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
