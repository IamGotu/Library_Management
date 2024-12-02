<?php
session_start();

// Check if the user is logged in and is either a faculty or a student
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['faculty', 'student'])) {
    header("Location: ../login/login.php");
    exit();
}

// Include the database connection
include '../config/db.php';

// Get the user_id from session
$user_id = $_SESSION['user_id'];

// SQL query to fetch the borrowed resources for the user
$query = "
    SELECT bt.id AS transaction_id, bt.borrow_date, bt.due_date, bt.status AS transaction_status, 
           lr.Title, lr.Category, lr.AccessionNumber, lr.ResourceType, bt.late_fee,
           DATEDIFF(CURRENT_DATE, bt.due_date) AS overdue_days
    FROM borrow_transactions bt
    JOIN libraryresources lr ON bt.resource_id = lr.ResourceID
    WHERE bt.user_id = :user_id AND bt.status IN ('borrowed', 'overdue')
";

try {
    // Prepare and execute the query to fetch data
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    // Fetch the result
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Now, loop through the transactions and update late fee and status in the database if applicable
    foreach ($transactions as $transaction) {
        $transaction_id = $transaction['transaction_id'];
        $overdue_days = $transaction['overdue_days'];
        $transaction_status = $transaction['transaction_status'];
        $due_date = $transaction['due_date'];

        // Ensure the due_date exists before calculating late fee
        if ($due_date !== null && $overdue_days > 0) {
            // Calculate the late fee only if the due date exists and the resource is overdue
            $late_fee = 100; // Fixed fee of 100 if overdue
        } else {
            // No late fee if not overdue or no due date
            $late_fee = 0;
        }

        // Update the late fee in the database
        $updateQuery = "UPDATE borrow_transactions SET late_fee = :late_fee WHERE id = :transaction_id";
        $updateStmt = $pdo->prepare($updateQuery);
        $updateStmt->bindParam(':late_fee', $late_fee, PDO::PARAM_INT);
        $updateStmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
        $updateStmt->execute();

        // Update the status to 'overdue' if the due date has passed and the status is still 'borrowed'
        if ($overdue_days > 0 && $transaction_status != 'overdue') {
            $updateStatusQuery = "UPDATE borrow_transactions SET status = 'overdue' WHERE id = :transaction_id";
            $updateStatusStmt = $pdo->prepare($updateStatusQuery);
            $updateStatusStmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
            $updateStatusStmt->execute();
        }

        // If the transaction is not overdue and status is still borrowed, keep it 'borrowed'
        if ($overdue_days <= 0 && $transaction_status != 'borrowed') {
            $updateStatusQuery = "UPDATE borrow_transactions SET status = 'borrowed' WHERE id = :transaction_id";
            $updateStatusStmt = $pdo->prepare($updateStatusQuery);
            $updateStatusStmt->bindParam(':transaction_id', $transaction_id, PDO::PARAM_INT);
            $updateStatusStmt->execute();
        }
    }
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
    <title>Your Borrowed Books</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* General Styles */
        body {
            font-family: Arial, sans-serif;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
            color: #333;
        }

        header {
            background-color: #007BFF; /* Blue background */
            color: white;
            padding: 20px;
            text-align: center;
        }

        .container {
            width: 80%;
            margin: 0 auto;
            padding: 20px;
        }

        .transaction-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .transaction-table th, .transaction-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        .transaction-table th {
            background-color: #007BFF; /* Blue header */
            color: white;
        }

        .transaction-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .transaction-table tr:hover {
            background-color: #ddd;
        }

        .no-transactions {
            text-align: center;
            font-size: 18px;
            color: #555;
            margin-top: 50px;
        }

        .go-back-btn {
            display: inline-block;
            background-color: #007BFF;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            text-align: center;
        }

        .go-back-btn:hover {
            background-color: #0056b3;
        }

    </style>
</head>
<body>

<header>
    <h1>Your Current Borrowed Resources</h1>
</header>

<div class="container">

    <?php if ($transactions): ?>
        <table class="transaction-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Accession Number</th>
                    <th>Resource Type</th>
                    <th>Borrow Date</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Late Fee</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $transaction): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($transaction['Title']); ?></td>
                        <td><?php echo htmlspecialchars($transaction['Category']); ?></td>
                        <td><?php echo htmlspecialchars($transaction['AccessionNumber']); ?></td>
                        <td><?php echo htmlspecialchars($transaction['ResourceType']); ?></td>
                        <td><?php echo date('F j, Y', strtotime($transaction['borrow_date'])); ?></td>
                        <td><?php echo $transaction['due_date'] ? date('F j, Y', strtotime($transaction['due_date'])) : 'No Due Date'; ?></td>
                        <td>
                            <?php 
                                if ($transaction['transaction_status'] == 'borrowed') {
                                    echo '<span style="color: green;">Borrowed</span>';
                                } elseif ($transaction['transaction_status'] == 'overdue') {
                                    echo '<span style="color: red;">Overdue</span>';
                                } else {
                                    echo '<span style="color: gray;">Returned</span>';
                                }
                            ?>
                        </td>
                        <td>
                            <?php 
                                // Check if late_fee key exists and display it
                                echo isset($transaction['late_fee']) && $transaction['late_fee'] > 0 ? '₱' . number_format($transaction['late_fee'], 2) : '₱0.00';
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="no-transactions">You have no active borrow transactions at the moment.</p>
    <?php endif; ?>

    <!-- Go Back Button -->
    <a href="dashboard.php" class="go-back-btn">Go Back to Dashboard</a>

</div>

</body>
</html>
