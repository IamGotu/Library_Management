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
$fineID = $_GET['fineID'] ?? null;

if (!$fineID) {
    echo "Invalid fine ID.";
    exit();
}

// Fetch the fine details
$stmt = $pdo->prepare("SELECT * FROM fines WHERE ID = :fineID");
$stmt->bindParam(':fineID', $fineID, PDO::PARAM_INT);
$stmt->execute();
$fine = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$fine) {
    echo "Fine not found.";
    exit();
}

// Get the BorrowTransactionID and BorrowerID from the fine record
$transactionID = $fine['BorrowTransactionID'];
$borrowerID = $fine['BorrowerID'];

// Update the borrow transaction to set the return date
$returnDate = date('Y-m-d'); // Set the current date as return date
$stmt = $pdo->prepare("UPDATE borrow_transactions SET return_date = :returnDate, status = 'returned' WHERE ID = :transactionID");
$stmt->bindParam(':returnDate', $returnDate);
$stmt->bindParam(':transactionID', $transactionID, PDO::PARAM_INT);
$stmt->execute();

// Update the fine status to 'paid'
$stmt = $pdo->prepare("UPDATE fines SET PaidStatus = 'paid' WHERE ID = :fineID");
$stmt->bindParam(':fineID', $fineID, PDO::PARAM_INT);
$stmt->execute();

// Redirect to the fines page with a success message
header("Location: fine.php?success=paid");
exit();
?>