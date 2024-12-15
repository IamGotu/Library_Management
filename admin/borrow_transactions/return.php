<?php
session_start();

// Check if the user is logged in and is a staff member
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../../login/login.php");
    exit();
}

// Include the database connection
include '../../config/db.php';

// Get the transaction ID
$transactionID = $_GET['transactionID'] ?? null;

if (!$transactionID) {
    echo "Invalid transaction ID.";
    exit();
}

// Fetch the borrow transaction details
$stmt = $pdo->prepare("SELECT * FROM borrow_transactions WHERE ID = :transactionID");
$stmt->bindParam(':transactionID', $transactionID, PDO::PARAM_INT);
$stmt->execute();
$transaction = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$transaction) {
    echo "Transaction not found.";
    exit();
}

// Check if the due date has passed
$dueDate = new DateTime($transaction['due_date']);
$currentDate = new DateTime();

// If the resource is overdue
if ($currentDate > $dueDate) {
    header("Location: borrowed_resources.php?error=overdue_return");
    exit();
}

// Update the transaction to 'returned' and set the return date
$returnDate = date('Y-m-d');
$stmt = $pdo->prepare("UPDATE borrow_transactions SET status = 'returned', return_date = :returnDate WHERE ID = :transactionID");
$stmt->bindParam(':returnDate', $returnDate);
$stmt->bindParam(':transactionID', $transactionID, PDO::PARAM_INT);
$stmt->execute();

// Update the resource availability
$stmt = $pdo->prepare("UPDATE libraryresources SET AvailabilityStatus = 'Available' WHERE ResourceID = :resourceID");
$stmt->bindParam(':resourceID', $transaction['ResourceID'], PDO::PARAM_INT);
$stmt->execute();

// On successful return, redirect to `borrowed_resources.php` with success
header("Location: borrowed_resources.php?success=return_success");
exit();
?>