<?php
session_start();

// Check if the user is logged in and is either a faculty or a student
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin'])) {
    header("Location: ../login/login.php");
    exit();
}

// Include the database connection
include '../config/db.php';

// Function to get all borrowing history (only completed transactions with a return date)
function getAllBorrowingHistory() {
    global $pdo;
    $sql = "SELECT lr.Title, bt.AccessionNumber, bt.BorrowerID, bt.Borrower_first_name, bt.Borrower_middle_name, bt.Borrower_last_name, bt.Borrower_suffix, bt.ApproverID, bt.Approver_first_name, bt.Approver_middle_name, bt.Approver_last_name, bt.Approver_suffix, bt.borrow_date, bt.due_date, bt.return_date, bt.status
            FROM borrow_transactions bt
            JOIN libraryresources lr ON bt.ResourceID = lr.ResourceID
            WHERE bt.status = 'returned' OR bt.status = 'borrowed' OR bt.status = 'overdue'
            ORDER BY bt.borrow_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$history = getAllBorrowingHistory();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrowing History</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../components/css/view.css">
    <link rel="icon" href="../components/image/book.png" type="image/x-icon">
</head>
<body>
    <!-- Navbar -->
    <?php include './layout/navbar.php'; ?>

    <!-- Main Content -->
    <div class="content-wrapper">
        <div class="container">
            <div class="centered-heading">
                <h2>Borrowing History</h2>
            </div>

            <!-- Borrowing History Table -->
            <div class="table-container">
                <?php if (empty($history)): ?>
                    <p class="text-center text-white">No borrowing history found for completed transactions.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Accession Number</th>
                                <th>Borrower ID</th>
                                <th>Borrower Name</th>
                                <th>Approver ID</th>
                                <th>Approver Name</th>
                                <th>Status</th>
                                <th>Borrow Date</th>
                                <th>Due Date</th>
                                <th>Return Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $record): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($record['Title']); ?></td>
                                    <td><?php echo htmlspecialchars($record['AccessionNumber']); ?></td>
                                    <td><?php echo htmlspecialchars($record['BorrowerID']); ?></td>
                                    <td><?php echo htmlspecialchars($record['Borrower_first_name'] . ' ' . $record['Borrower_middle_name'] . ' ' . $record['Borrower_last_name'] . ' ' . $record['Borrower_suffix']); ?></td>
                                    <td><?php echo htmlspecialchars($record['ApproverID']); ?></td>
                                    <td><?php echo htmlspecialchars($record['Approver_first_name'] . ' ' . $record['Approver_middle_name'] . ' ' . $record['Approver_last_name'] . ' ' . $record['Approver_suffix']); ?></td>
                                    <td><?php echo htmlspecialchars($record['status']); ?></td>
                                    <td><?php echo htmlspecialchars($record['borrow_date']); ?></td>
                                    <td><?php echo htmlspecialchars($record['due_date']); ?></td>
                                    <td><?php echo htmlspecialchars($record['return_date']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap Script -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>