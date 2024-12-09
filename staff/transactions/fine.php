<?php
session_start();

// Check if the user is logged in and is a staff member
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'staff') {
    header("Location: ../../login/login.php");
    exit();
}

// Include the database connection
include '../../config/db.php';

// Fetch all fines (whether paid or unpaid)
$fines = $pdo->query("SELECT * FROM fines")->fetchAll(PDO::FETCH_ASSOC);
?>

<h2 class="mt-5">Fines Payment History</h2>
<table class="table table-bordered">
    <thead>
        <tr>
            <th>Transaction ID</th>
            <th>Borrower ID</th>
            <th>Amount</th>
            <th>Date Generated</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($fines as $fine): ?>
            <tr>
                <td><?php echo htmlspecialchars($fine['BorrowTransactionID']); ?></td>
                <td><?php echo htmlspecialchars($fine['BorrowerID']); ?></td>
                <td><?php echo htmlspecialchars($fine['Amount']); ?></td>
                <td><?php echo htmlspecialchars($fine['DateGenerated']); ?></td>
                <td><?php echo htmlspecialchars($fine['PaidStatus']); ?></td>
                <td>
                    <?php if ($fine['PaidStatus'] === 'unpaid'): ?>
                        <!-- Display Pay button for unpaid fines -->
                        <a href="pay_fine.php?fineID=<?php echo $fine['ID']; ?>" class="btn btn-primary">Pay</a>
                    <?php else: ?>
                        <!-- Display Print button for paid fines -->
                        <a href="print_receipt.php?fineID=<?php echo $fine['ID']; ?>" class="btn btn-secondary">Print Receipt</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>