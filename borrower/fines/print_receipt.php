<?php
session_start();

// Check if the user is logged in and is either a faculty or a student
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['faculty', 'student'])) {
    header("Location: ../login/login.php");
    exit();
}

// Include the database connection
include '../../config/db.php';

// Get the fine ID from the URL
if (isset($_GET['fineID'])) {
    $fineID = $_GET['fineID'];

    // Fetch fine details including borrower, approver, and transaction details
    $fineQuery = $pdo->prepare("
        SELECT 
            f.BorrowTransactionID, 
            f.BorrowerID, 
            f.Borrower_first_name, 
            f.Borrower_middle_name, 
            f.Borrower_last_name, 
            f.Borrower_suffix, 
            f.Amount, 
            f.DateGenerated, 
            f.PaidStatus, 
            f.ApproverID, 
            f.DatePaid, 
            f.ReceiptPrinted, 
            b.first_name AS BorrowerFirst, 
            b.last_name AS BorrowerLast, 
            a.first_name AS ApproverFirst, 
            a.middle_name AS ApproverMiddle, 
            a.last_name AS ApproverLast, 
            a.suffix AS ApproverSuffix,
            bt.ResourceID,
            lr.Title, 
            lr.AccessionNumber, 
            lr.ResourceType
        FROM fines f
        LEFT JOIN users b ON f.BorrowerID = b.membership_id
        LEFT JOIN users a ON f.ApproverID = a.membership_id
        LEFT JOIN borrow_transactions bt ON f.BorrowTransactionID = bt.ID
        LEFT JOIN libraryresources lr ON bt.ResourceID = lr.ResourceID
        WHERE f.ID = :fineID
    ");
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
    <link rel="stylesheet" href="../../components/css/print.css">
    <link rel="icon" href="../../components/image/book.png" type="image/x-icon">
</head>
<body>
    <div class="container mt-5">
        <div class="receipt border p-4">
            <h3 class="text-center">Library Fine Payment Receipt</h2>
            <p><strong>Transaction ID:</strong> <?php echo htmlspecialchars($fine['BorrowTransactionID']); ?></p>
            
            <p><strong>Borrower Information:</strong></p>
            <p>Name: <?php echo htmlspecialchars($fine['Borrower_first_name'] . ' ' . $fine['Borrower_middle_name'] . ' ' . $fine['Borrower_last_name'] . ' ' . $fine['Borrower_suffix']); ?></p>
            <p>Borrower ID: <?php echo htmlspecialchars($fine['BorrowerID']); ?></p>

            <p><strong>Resource Information:</strong></p>
            <p>Title: <?php echo htmlspecialchars($fine['Title']); ?></p>
            <p>Accession Number: <?php echo htmlspecialchars($fine['AccessionNumber']); ?></p>
            <p>Resource Type: <?php echo htmlspecialchars($fine['ResourceType']); ?></p>
            
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

    <div class="text-center mt-3">
        <!-- Print Button -->
        <button onclick="window.print()" class="btn btn-primary">Print Receipt</button>
        <!-- Back Button -->
        <a href="fine_history.php" class="btn btn-secondary">Back</a>
    </div>

    <script>
        // Automatically trigger print dialog on page load
        window.onload = function() {
            window.print();
        };

        // Event listener to handle the after print action
        window.onafterprint = function() {
            // AJAX request to update the ReceiptPrinted status
            var fineID = <?php echo $fineID; ?>;  // Get the fineID from PHP

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "update_receipt_status.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onload = function() {
                if (xhr.status == 200) {
                    console.log("Receipt status updated successfully.");
                } else {
                    console.log("Error updating receipt status.");
                }
            };
            xhr.send("fineID=" + fineID + "&receiptPrinted=yes");
        };
    </script>
</body>
</html>