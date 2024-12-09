<?php
session_start();

// Check if the user is logged in and is a staff member
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'staff') {
    header("Location: ../../login/login.php");
    exit();
}

// Include the database connection
include '../../config/db.php';

// Get the fine ID from the URL
if (isset($_GET['fineID'])) {
    $fineID = $_GET['fineID'];

    // Fetch fine details including borrower and approver information
    $fineQuery = $pdo->prepare("SELECT f.BorrowTransactionID, f.BorrowerID, f.Borrower_first_name, f.Borrower_middle_name, f.Borrower_last_name, f.Borrower_suffix, f.Amount, f.DateGenerated, f.PaidStatus, f.ApproverID, f.DatePaid, b.first_name AS BorrowerFirst, b.last_name AS BorrowerLast, a.first_name AS ApproverFirst, a.middle_name AS ApproverMiddle, a.last_name AS ApproverLast, a.suffix AS ApproverSuffix
                                FROM fines f
                                LEFT JOIN users b ON f.BorrowerID = b.membership_id
                                LEFT JOIN users a ON f.ApproverID = a.membership_id
                                WHERE f.ID = :fineID");
    $fineQuery->execute(['fineID' => $fineID]);

    $fine = $fineQuery->fetch(PDO::FETCH_ASSOC);

    if (!$fine) {
        echo "Fine record not found.";
        exit();
    }
} else {
    echo "Fine ID is missing.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../components/css/print.css"> <!-- Link to your CSS file for print styling -->
    <link rel="icon" href="../components/image/book.png" type="image/x-icon">
</head>
<body>
    <div class="container">
        <div class="receipt">
            <h2>Library Fine Payment Receipt</h2>
            <p><strong>Transaction ID:</strong> <?php echo htmlspecialchars($fine['BorrowTransactionID']); ?></p>
            <p><strong>Borrower Information:</strong></p>
            <p>Name: <?php echo htmlspecialchars($fine['Borrower_first_name'] . ' ' . $fine['Borrower_middle_name'] . ' ' . $fine['Borrower_last_name'] . ' ' . $fine['Borrower_suffix']); ?></p>
            <p>Borrower ID: <?php echo htmlspecialchars($fine['BorrowerID']); ?></p>
            
            <p><strong>Approver Information:</strong></p>
            <p>Name: <?php echo htmlspecialchars($fine['ApproverFirst'] . ' ' . $fine['ApproverMiddle'] . ' ' . $fine['ApproverLast'] . ' ' . $fine['ApproverSuffix']); ?></p>
            <p>Approver ID: <?php echo htmlspecialchars($fine['ApproverID']); ?></p>
            
            <p><strong>Amount:</strong> ₱<?php echo number_format($fine['Amount'], 2); ?></p>
            <p><strong>Date Generated:</strong> <?php echo htmlspecialchars($fine['DateGenerated']); ?></p>
            <p><strong>Date Paid:</strong> <?php echo $fine['DatePaid'] ? htmlspecialchars($fine['DatePaid']) : 'Not Paid Yet'; ?></p>
            <p><strong>Status:</strong> <?php echo htmlspecialchars($fine['PaidStatus']); ?></p>

            <hr>
            <p class="text-center"><strong>Thank you for your payment!</strong></p>
        </div>
    </div>

    <!-- Print Button -->
    <div class="text-center mt-3">
        <button onclick="window.print()" class="btn btn-primary">Print Receipt</button>
        <a href="fine.php" class="btn btn-secondary">Back</a>
    </div>

    <script>
        // Automatically trigger print dialog on page load
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>