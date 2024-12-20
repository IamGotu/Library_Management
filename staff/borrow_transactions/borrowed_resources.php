<?php
session_start();

// Check if the user is logged in and is a staff member
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'staff') {
    header("Location: ../../login/login.php");
    exit();
}

// Include the database connection
include '../../config/db.php';

// Initialize search and filter parameters
$resourceType = isset($_GET['resource_type']) ? $_GET['resource_type'] : '';
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

// Function to get filtered active borrow transactions
function getActiveBorrowTransactions($resourceType, $searchTerm) {
    global $pdo;
    $sql = "SELECT bt.ID, lr.Title, bt.borrow_date, bt.due_date, bt.BorrowerID, bt.ApproverID, bt.AccessionNumber, bt.ResourceType, bt.status,
                   bt.Borrower_first_name, bt.Borrower_middle_name, bt.Borrower_last_name, bt.Borrower_suffix,
                   bt.Approver_first_name, bt.Approver_middle_name, bt.Approver_last_name, bt.Approver_suffix
            FROM borrow_transactions bt
            JOIN libraryresources lr ON bt.ResourceID = lr.ResourceID
            WHERE bt.status = 'borrowed'";

    // Add ResourceType filter
    if (!empty($resourceType)) {
        $sql .= " AND bt.ResourceType = :resourceType";
    }

    // Add search filter for Title, AccessionNumber, BorrowerID, or Borrower name
    if (!empty($searchTerm)) {
        $sql .= " AND (lr.Title LIKE :searchTerm 
                    OR bt.AccessionNumber LIKE :searchTerm 
                    OR bt.BorrowerID LIKE :searchTerm 
                    OR CONCAT(bt.Borrower_first_name, ' ', bt.Borrower_middle_name, ' ', bt.Borrower_last_name) LIKE :searchTerm)";
    }

    $sql .= " ORDER BY bt.borrow_date DESC";

    $stmt = $pdo->prepare($sql);

    // Bind parameters
    if (!empty($resourceType)) {
        $stmt->bindParam(':resourceType', $resourceType);
    }
    if (!empty($searchTerm)) {
        $searchParam = "%$searchTerm%";
        $stmt->bindParam(':searchTerm', $searchParam);
    }

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$transactions = getActiveBorrowTransactions($resourceType, $searchTerm);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrowed Resources</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../components/css/view.css">
    <link rel="icon" href="../../components/Image/book.png" type="image/x-icon">
</head>
<body>
    <!-- Navbar -->
    <?php include '../../staff/layout/navbar.php'; ?>

    <!-- Main Content -->
    <div class="content-wrapper">
        <div class="container">

            <!-- Header -->
            <div class="centered-heading mb-4">
                <h2>Borrowed Resources</h2>
            </div>

            <!-- Search and Filter Form -->
            <form method="GET" action="borrowed_resources.php" class="mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="resource_type" class="form-label">Resource Type:</label>
                        <select name="resource_type" id="resource_type" class="form-select">
                            <option value="">All Types</option>
                            <option value="Book" <?php echo ($resourceType == 'Book') ? 'selected' : ''; ?>>Book</option>
                            <option value="MediaResource" <?php echo ($resourceType == 'MediaResource') ? 'selected' : ''; ?>>Media Resource</option>
                            <option value="Periodical" <?php echo ($resourceType == 'Periodical') ? 'selected' : ''; ?>>Periodical</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="search" class="form-label">Search:</label>
                        <input type="text" name="search" id="search" class="form-control" placeholder="Accession Number | Title | Borrower ID | Borrower Name" value="<?php echo htmlspecialchars($searchTerm); ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Search</button>
                    </div>
                </div>
            </form>
            
            <!-- Table Container -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Transaction ID</th>
                            <th>Accession Number</th>
                            <th>Resource Type</th>
                            <th>Title</th>
                            <th>Borrower ID</th>
                            <th>Borrower Name</th>
                            <th>Approver ID</th>
                            <th>Approver Name</th>
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
                                    <td><?php echo htmlspecialchars($transaction['Title']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['BorrowerID']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['Borrower_first_name'] . ' ' . $transaction['Borrower_middle_name'] . ' ' . $transaction['Borrower_last_name'] . ' ' . $transaction['Borrower_suffix']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['ApproverID']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['Approver_first_name'] . ' ' . $transaction['Approver_middle_name'] . ' ' . $transaction['Approver_last_name'] . ' ' . $transaction['Approver_suffix']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['borrow_date']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['due_date']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['status']); ?></td>
                                    <td>
                                        <a href="return.php?transactionID=<?php echo $transaction['ID']; ?>" class="btn btn-primary">Return</a>
                                        <br> <br>
                                        <a href="overdue.php?transactionID=<?php echo $transaction['ID']; ?>" class="btn btn-danger">Overdue</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="12" class="text-center">No active borrow transactions found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Error Modal -->
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
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="redirectToBorrowedResources()">OK</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Success Modal -->
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
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="redirectToBorrowedResources()">OK</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        function redirectToBorrowedResources() {
            // Redirect to the same page, but without the 'success' or 'error' parameters
            window.history.replaceState({}, document.title, window.location.pathname);
            window.location.href = 'borrowed_resources.php'; // Redirect to borrowed_resources.php
        }

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