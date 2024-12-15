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
    header("Location: borrowed_resources.php?error=invalid_transaction");
    exit();
}

// Fetch the borrow transaction details
$stmt = $pdo->prepare("
    SELECT ID, BorrowerID, Borrower_first_name, Borrower_middle_name, Borrower_last_name, Borrower_suffix, due_date 
    FROM borrow_transactions 
    WHERE ID = :transactionID
");
$stmt->bindParam(':transactionID', $transactionID, PDO::PARAM_INT);
$stmt->execute();
$transaction = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$transaction) {
    header("Location: borrowed_resources.php?error=transaction_not_found");
    exit();
}

// Extract details
$dueDate = new DateTime($transaction['due_date']);
$currentDate = new DateTime();
$borrowerID = $transaction['BorrowerID'];

// If the due date has not passed
if ($currentDate <= $dueDate) {
    header("Location: borrowed_resources.php?error=not_due_yet");
    exit();
}

// Calculate the fee
$daysOverdue = $currentDate->diff($dueDate)->days;
$fee = $daysOverdue * 50;

// Log the fee in the fines table
$stmt = $pdo->prepare("
    INSERT INTO fines (BorrowTransactionID, BorrowerID, Borrower_first_name, Borrower_middle_name, Borrower_last_name, Borrower_suffix, Amount) 
    VALUES (:transactionID, :borrowerID, :firstName, :middleName, :lastName, :suffix, :fee)
");
$stmt->bindParam(':transactionID', $transactionID, PDO::PARAM_INT);
$stmt->bindParam(':borrowerID', $borrowerID, PDO::PARAM_INT);
$stmt->bindParam(':firstName', $transaction['Borrower_first_name']);
$stmt->bindParam(':middleName', $transaction['Borrower_middle_name']);
$stmt->bindParam(':lastName', $transaction['Borrower_last_name']);
$stmt->bindParam(':suffix', $transaction['Borrower_suffix']);
$stmt->bindParam(':fee', $fee);
$stmt->execute();

// Mark the resource as overdue
$stmt = $pdo->prepare("
    UPDATE borrow_transactions 
    SET status = 'overdue' 
    WHERE ID = :transactionID
");
$stmt->bindParam(':transactionID', $transactionID, PDO::PARAM_INT);
$stmt->execute();

header("Location: borrowed_resources.php?success=overdue_success&fee={$fee}");
exit();
?>