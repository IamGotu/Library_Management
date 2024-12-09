<?php
session_start();

// Check if the user is logged in and is a staff member
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'staff') {
    header("Location: ../../login/login.php");
    exit();
}

// Include the database connection
include '../../config/db.php';

// Function to get all active borrow transactions
function getActiveBorrowTransactions() {
    global $pdo;
    $sql = "SELECT bt.ID, lr.Title, bt.borrow_date, bt.due_date, bt.BorrowerID, bt.ApproverID, bt.AccessionNumber, bt.ResourceType, bt.status
            FROM borrow_transactions bt
            JOIN libraryresources lr ON bt.ResourceID = lr.ResourceID
            WHERE bt.status = 'borrowed'
            ORDER BY bt.borrow_date DESC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$transactions = getActiveBorrowTransactions();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrowed Resources</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Borrowed Resources</h1>
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>Transaction ID</th>
                    <th>Accession Number</th>
                    <th>Resource Type</th>
                    <th>Borrower ID</th>
                    <th>Approver ID</th>
                    <th>Title</th>
                    <th>Borrow Date</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($transactions): ?>
                    <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($transaction['ID']); ?></td>
                            <td><?php echo htmlspecialchars($transaction['AccessionNumber']); ?></td>
                            <td><?php echo htmlspecialchars($transaction['ResourceType']); ?></td>
                            <td><?php echo htmlspecialchars($transaction['BorrowerID']); ?></td>
                            <td><?php echo htmlspecialchars($transaction['ApproverID']); ?></td>
                            <td><?php echo htmlspecialchars($transaction['Title']); ?></td>
                            <td><?php echo htmlspecialchars($transaction['borrow_date']); ?></td>
                            <td><?php echo htmlspecialchars($transaction['due_date']); ?></td>
                            <td><?php echo htmlspecialchars($transaction['status']); ?></td>
                            <td>
                                <a href="return.php?transactionID=<?php echo $transaction['ID']; ?>" class="btn btn-primary">Return</a>
                                <a href="overdue.php?transactionID=<?php echo $transaction['ID']; ?>" class="btn btn-danger">Overdue</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="text-center">No active borrow transactions found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if (isset($_GET['error'])): ?>
    <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="errorModalLabel">Error</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php
                    switch ($_GET['error']) {
                        case 'overdue_return':
                            echo "Cannot return the resource. It is already overdue.";
                            break;
                        case 'not_due_yet':
                            echo "Cannot mark as overdue. The due date has not yet passed.";
                            break;
                        default:
                            echo "An unknown error occurred.";
                            break;
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['success'])): ?>
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="successModalLabel">Success</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php
                    switch ($_GET['success']) {
                        case 'return_success':
                            echo "Resource returned successfully.";
                            break;
                        case 'overdue_success':
                            echo "Resource marked as overdue. Fee incurred: PHP {$_GET['fee']}.";
                            break;
                        default:
                            echo "Action completed successfully.";
                            break;
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        <?php if (isset($_GET['error'])): ?>
        var errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
        errorModal.show();
        <?php endif; ?>
        <?php if (isset($_GET['success'])): ?>
        var successModal = new bootstrap.Modal(document.getElementById('successModal'));
        successModal.show();
        <?php endif; ?>
    });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>