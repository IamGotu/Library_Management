<?php
session_start();

// Check if the user is logged in and is either a faculty or a student
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['faculty', 'student'])) {
    header("Location: ../login/login.php");
    exit();
}

// Include the database connection
include '../config/db.php';

// Get the logged-in user's ID from the session
$user_id = $_SESSION['user_id'];

// Function to get the logged-in user's borrowing history
function getUserHistory($user_id) {
    global $pdo;
    $sql = "
        SELECT 
            lr.Title, 
            bt.borrow_date, 
            bt.due_date, 
            bt.return_date,
            bt.status
        FROM borrow_transactions bt
        JOIN libraryresources lr ON bt.resource_id = lr.ResourceID
        WHERE bt.user_id = ?
        ORDER BY bt.borrow_date DESC
    ";
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
    <title>My Borrowing History</title>
    <style>
        table {
            width: 90%;
            margin: auto;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1 style="text-align: center;">My Borrowing History</h1>
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Borrow Date</th>
                <th>Due Date</th>
                <th>Return Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($history)): ?>
                <?php foreach ($history as $record): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($record['Title']); ?></td>
                        <td><?php echo htmlspecialchars($record['borrow_date']); ?></td>
                        <td><?php echo htmlspecialchars($record['due_date']); ?></td>
                        <td><?php echo htmlspecialchars($record['return_date'] ?? 'Not Returned'); ?></td>
                        <td>
                            <?php
                            switch ($record['status']) {
                                case 'borrowed':
                                    echo '<span style="color: blue;">Borrowed</span>';
                                    break;
                                case 'returned':
                                    echo '<span style="color: green;">Returned</span>';
                                    break;
                                case 'overdue':
                                    echo '<span style="color: red;">Overdue</span>';
                                    break;
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center;">No borrowing history found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <div style="text-align: center; margin-top: 20px;">
        <a href="dashboard.php">Back to Dashboard</a>
    </div>
</body>
</html>