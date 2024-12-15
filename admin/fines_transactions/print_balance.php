<?php
session_start();
include '../../config/db.php';

// Check if the user is logged in and is a staff member
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../../login/login.php");
    exit();
}

// Validate fine ID
if (!isset($_GET['fineID']) || !is_numeric($_GET['fineID'])) {
    echo "Invalid fine ID.";
    exit();
}

$fineID = $_GET['fineID'];

// Fetch fine details
$query = $pdo->prepare("
    SELECT f.BorrowTransactionID, f.Borrower_first_name, f.Borrower_middle_name, 
           f.Borrower_last_name, f.Borrower_suffix, f.Amount, f.DateGenerated, 
           f.PaidStatus, b.ResourceID, r.Title, r.Category, r.AccessionNumber
    FROM fines f
    LEFT JOIN borrow_transactions b ON f.BorrowTransactionID = b.ID
    LEFT JOIN libraryresources r ON b.ResourceID = r.ResourceID
    WHERE f.ID = :fineID
");
$query->execute(['fineID' => $fineID]);
$fine = $query->fetch(PDO::FETCH_ASSOC);

if (!$fine) {
    echo "Fine not found.";
    exit();
}

// Print Balance Content
$borrowerName = htmlspecialchars(
    $fine['Borrower_first_name'] . ' ' .
    $fine['Borrower_middle_name'] . ' ' .
    $fine['Borrower_last_name'] . ' ' .
    $fine['Borrower_suffix']
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Balance</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../components/css/print.css">
    <link rel="icon" href="../../components/image/book.png" type="image/x-icon">
</head>
<body>
    <div class="container mt-5">
        <div class="receipt border p-4">
            <h3 class="text-center">Balance Statement</h3>
            <hr>

            <!-- Borrower Details -->
            <p><strong>Borrower Details:</strong></p>
            <p>Borrower Name: <?php echo $borrowerName; ?></p>
            <p>Transaction ID: <?php echo htmlspecialchars($fine['BorrowTransactionID']); ?></p>
            <p>Date Generated: <?php echo htmlspecialchars($fine['DateGenerated']); ?></p>
            <p>Balance Due: ₱<?php echo htmlspecialchars($fine['Amount']); ?></p>

            <br>

            <!-- Resource Details -->
            <p><strong>Borrowed Resource Details:</strong></p>
            <p>Title: <?php echo htmlspecialchars($fine['Title']); ?></p>
            <p>Category: <?php echo htmlspecialchars($fine['Category']); ?></p>
            <p>Accession Number: <?php echo htmlspecialchars($fine['AccessionNumber']); ?></p>

            <p class="text-danger mt-3">This fine is unpaid. Please settle the balance.</p>
            <hr>
            <p class="text-center"><strong>Thank you!</strong></p>
        </div>
    </div>

    <div class="text-center mt-3">
        <!-- Print Button -->
        <button onclick="window.print()" class="btn btn-primary">Print Receipt</button>
        <!-- Back Button -->
        <a href="fine.php" class="btn btn-secondary">Back</a>
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