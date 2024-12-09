<?php
session_start();

// Check if the user is logged in and is either a faculty or a student
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['faculty', 'student'])) {
    header("Location: ../../login/login.php");
    exit();
}

// Include the database connection
include '../../config/db.php';

// Fetch the logged-in user's ID from the session
$membership_id = $_SESSION['user_id'];

// Fetch unpaid fines with unprinted receipts for the logged-in user
$fines = $pdo->prepare("SELECT BorrowTransactionID, BorrowerID, Borrower_first_name, Borrower_middle_name, Borrower_last_name, Borrower_suffix, Amount, DateGenerated, PaidStatus, ID
                       FROM fines
                       WHERE BorrowerID = :membership_id");
$fines->execute(['membership_id' => $membership_id]);
$fines = $fines->fetchAll(PDO::FETCH_ASSOC);
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
    <?php include '../../borrower/layout/navbar.php'; ?>

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
                            <th>Transaction ID</th>
                            <th>Borrower ID</th>
                            <th>Borrower Name</th>
                            <th>Amount</th>
                            <th>Payment Generated Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- PHP Code to Populate Table -->
                        <?php if (count($fines) > 0): ?>
                            <?php foreach ($fines as $fine): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($fine['BorrowTransactionID']); ?></td>
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
                                    <td> ₱<?php echo htmlspecialchars($fine['Amount']); ?></td>
                                    <td><?php echo htmlspecialchars($fine['DateGenerated']); ?></td>
                                    <td><?php echo htmlspecialchars($fine['PaidStatus']); ?></td>
                                    <td>
                                        <?php if ($fine['PaidStatus'] === 'unpaid'): ?>
                                            <a href="" class="btn btn-primary" disable>Unpaid</a>
                                        <?php else: ?>
                                            <a href="print_receipt.php?fineID=<?php echo $fine['ID']; ?>" class="btn btn-secondary">Print Receipt</a>
                                        <?php endif; ?>
                                    </td>
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