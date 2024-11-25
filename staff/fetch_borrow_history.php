<?php
session_start();

// Check if the user is logged in and is a staff member
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'staff') {
    header("Location: ../login/login.php");
    exit();
}

// Include the database connection
include '../config/db.php';

// Function to generate borrowing history report for a specific user
function getBorrowHistory($user_id) {
    global $pdo;
    $sql = "SELECT bt.*, u.first_name, u.last_name
            FROM borrow_transactions bt
            JOIN users u ON bt.user_id = u.id
            WHERE bt.user_id = :user_id
            ORDER BY bt.borrow_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get popular books based on borrowing frequency
function getPopularBooks() {
    global $pdo;
    $sql = "SELECT bt.resource_type, COUNT(bt.id) as borrow_count
            FROM borrow_transactions bt
            WHERE bt.status = 'returned'
            GROUP BY bt.resource_type
            ORDER BY borrow_count DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to list overdue books with fines
function getOverdueBooks() {
    global $pdo;
    $sql = "SELECT bt.*, u.first_name, u.last_name, f.fine_amount
            FROM borrow_transactions bt
            JOIN users u ON bt.user_id = u.id
            LEFT JOIN fines f ON bt.id = f.transaction_id
            WHERE bt.due_date < CURDATE() AND bt.status = 'borrowed'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to create inventory summary by category and availability
function getInventorySummary() {
    global $pdo;
    $sql = "SELECT bt.resource_type, COUNT(bt.resource_id) as total_books
            FROM borrow_transactions bt
            GROUP BY bt.resource_type";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get all users for the user selection
function getUsers() {
    global $pdo;
    $sql = "SELECT id, first_name, last_name FROM users WHERE user_type = 'user'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Example Usage (You can replace the user_id dynamically or via form/input)
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : 5; // Sample user_id for borrow history report (you can replace with actual user input)
$borrow_history = getBorrowHistory($user_id);
$popular_books = getPopularBooks();
$overdue_books = getOverdueBooks();
$inventory_summary = getInventorySummary();
$users = getUsers(); // Fetch users for the user selection dropdown
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Staff Report</title>
    <link rel="stylesheet" href="../css/styles.css"> <!-- Include your CSS file if needed -->
</head>
<body>

  
    

    <!-- Borrow History Report -->
    <h3>Borrow History for User ID: <?php echo $user_id; ?></h3>
    <table border="1">
        <thead>
            <tr>
                <th>Resource Type</th>
                <th>Borrow Date</th>
                <th>Due Date</th>
                <th>Return Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($borrow_history as $transaction): ?>
                <tr>
                    <td><?php echo htmlspecialchars($transaction['resource_type']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['borrow_date']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['due_date']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['return_date']); ?></td>
                    <td><?php echo htmlspecialchars($transaction['status']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

   
</body>
</html>
