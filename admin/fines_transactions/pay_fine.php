<?php
session_start();

// Check if the user is logged in and is a staff member
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
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

try {
    // Begin a transaction
    $pdo->beginTransaction();

    // Fetch the fine details
    $stmt = $pdo->prepare("SELECT * FROM fines WHERE ID = :fineID");
    $stmt->bindParam(':fineID', $fineID, PDO::PARAM_INT);
    $stmt->execute();
    $fine = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$fine) {
        throw new Exception("Fine not found.");
    }

    // Get the BorrowTransactionID and BorrowerID from the fine record
    $transactionID = $fine['BorrowTransactionID'];

    // Update the borrow transaction to set the return date
    $returnDate = date('Y-m-d'); // Set the current date as return date
    $stmt = $pdo->prepare("UPDATE borrow_transactions, libraryresources SET return_date = :returnDate, status = 'returned', AvailabilityStatus = 'Available'  WHERE ID = :transactionID");
    $stmt->bindParam(':returnDate', $returnDate);
    $stmt->bindParam(':transactionID', $transactionID, PDO::PARAM_INT);
    $stmt->execute();

    // Fetch the current staff details
    $staff_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT membership_id, first_name, middle_name, last_name, suffix FROM users WHERE membership_id = ?");
    $stmt->execute([$staff_id]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$staff) {
        throw new Exception("Staff details not found.");
    }

    // Update the fine status to 'paid' and log payment details
    $datePaid = date('Y-m-d H:i:s'); // Current timestamp
    $stmt = $pdo->prepare("
        UPDATE fines 
        SET 
            PaidStatus = 'paid',
            DatePaid = :datePaid,
            ApproverID = :approverID,
            Approver_first_name = :firstName,
            Approver_middle_name = :middleName,
            Approver_last_name = :lastName,
            Approver_suffix = :suffix
        WHERE ID = :fineID
    ");
    $stmt->bindParam(':datePaid', $datePaid);
    $stmt->bindParam(':approverID', $staff['membership_id']);
    $stmt->bindParam(':firstName', $staff['first_name']);
    $stmt->bindParam(':middleName', $staff['middle_name']);
    $stmt->bindParam(':lastName', $staff['last_name']);
    $stmt->bindParam(':suffix', $staff['suffix']);
    $stmt->bindParam(':fineID', $fineID, PDO::PARAM_INT);
    $stmt->execute();

    // Commit the transaction
    $pdo->commit();

    // Redirect to the fines page with a success message
    header("Location: fine.php?success=paid");
    exit();

} catch (Exception $e) {
    // Rollback the transaction on error
    $pdo->rollBack();
    echo "Error: " . $e->getMessage();
    exit();
}
?>