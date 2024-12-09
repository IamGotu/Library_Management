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

// Fetch the borrower details from the 'users' table
$stmt = $pdo->prepare("SELECT * FROM users WHERE membership_id = :borrowerID");
$stmt->bindParam(':borrowerID', $borrowerID, PDO::PARAM_INT);
$stmt->execute();
$borrower = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$borrower) {
    echo "Borrower not found.";
    exit();
}

// Display the receipt
echo "<h1>Receipt</h1>";
echo "<p><strong>Transaction ID:</strong> " . htmlspecialchars($fine['BorrowTransactionID']) . "</p>";
echo "<p><strong>Borrower Name:</strong> " . htmlspecialchars($borrower['first_name'] . ' ' . $borrower['middle_name'] . ' ' . $borrower['last_name'] . ' ' . $borrower['suffix']) . "</p>";
echo "<p><strong>Borrower Email:</strong> " . htmlspecialchars($borrower['email']) . "</p>";
echo "<p><strong>Amount:</strong> " . htmlspecialchars($fine['Amount']) . "</p>";
echo "<p><strong>Date Generated:</strong> " . htmlspecialchars($fine['DateGenerated']) . "</p>";
echo "<p><strong>Paid Status:</strong> Paid</p>";
echo "<p><strong>Return Date:</strong> " . htmlspecialchars($fine['DateGenerated']) . "</p>";
echo "<p><strong>Receipt Date:</strong> " . date('Y-m-d') . "</p>";
echo "<p><a href='fine.php' class='btn btn-primary'>Back to Fines</a></p>";
?>