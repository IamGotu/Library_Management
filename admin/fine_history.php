<?php
session_start();

// Check if the user is logged in and is either a faculty or a student
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin'])) {
    header("Location: ../login/login.php");
    exit();
}

// Include the database connection
include '../config/db.php';

// Fetch unpaid fines with unprinted receipts
$fines = $pdo->query("SELECT BorrowTransactionID, BorrowerID, Borrower_first_name, Borrower_middle_name, Borrower_last_name, Borrower_suffix, ApproverID, Approver_first_name, Approver_middle_name, Approver_last_name, Approver_suffix, Amount, DatePaid, PaidStatus
                       FROM fines
                       WHERE PaidStatus = 'unpaid' OR PaidStatus = 'paid' OR ReceiptPrinted = 'no'")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Overdue Fines</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../components/css/view.css"> <!-- Link to your CSS file -->
    <link rel="icon" href="../../components/image/book.png" type="image/x-icon">
</head>
<body>
    <!-- Navbar -->
    <?php include '../admin/layout/navbar.php'; ?>

    <!-- Main Content -->
    <div class="content-wrapper">
        <div class="container">
            <div class="centered-heading">
                <h2>Overdue Fines</h2>
            </div>
            
            <!-- Table Section -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Borrower ID</th>
                            <th>Borrower Name</th>
                            <th>Approver ID</th>
                            <th>Approver Name</th>
                            <th>Amount</th>
                            <th>Date Paid</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- PHP Code to Populate Table -->
                        <?php if (count($fines) > 0): ?>
                            <?php foreach ($fines as $fine): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($fine['BorrowerID']); ?></td>
                                    <td>
                                        <?php 
                                            echo htmlspecialchars(
                                                $fine['Borrower_first_name'] . ' ' . 
                                                $fine['Borrower_middle_name'] . ' ' . 
                                                $fine['Borrower_last_name'] . ' ' . 
                                                $fine['Borrower_suffix']
                                            ); 
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($fine['ApproverID']); ?></td>
                                    <td>
                                        <?php 
                                            echo htmlspecialchars(
                                                $fine['Approver_first_name'] . ' ' . 
                                                $fine['Approver_middle_name'] . ' ' . 
                                                $fine['Approver_last_name'] . ' ' . 
                                                $fine['Approver_suffix']
                                            ); 
                                        ?>
                                    </td>
                                    <td> ₱<?php echo htmlspecialchars($fine['Amount']); ?></td>
                                    <td><?php echo htmlspecialchars($fine['DatePaid']); ?></td>
                                    <td><?php echo htmlspecialchars($fine['PaidStatus']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No overdue fine transactions found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Bootstrap Script -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>