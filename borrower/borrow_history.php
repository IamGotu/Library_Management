<?php
session_start();

// Check if the user is logged in and is either a faculty or a student
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['faculty', 'student'])) {
    header("Location: ../login/login.php");
    exit();
}

// Include the database connection
include '../config/db.php';

// Function to get borrowing history for the logged-in user
function getUserBorrowingHistory($searchTerm = '', $status = '', $userId) {
    global $pdo;
    $sql = "SELECT lr.Title, bt.AccessionNumber, bt.BorrowerID, bt.Borrower_first_name, bt.Borrower_middle_name, bt.Borrower_last_name, bt.Borrower_suffix, bt.ApproverID, bt.Approver_first_name, bt.Approver_middle_name, bt.Approver_last_name, bt.Approver_suffix, bt.borrow_date, bt.due_date, bt.return_date, bt.status
            FROM borrow_transactions bt
            JOIN libraryresources lr ON bt.ResourceID = lr.ResourceID
            WHERE bt.BorrowerID = :userId";

    // Add search condition if a search term is provided
    if ($searchTerm) {
        $sql .= " AND (lr.Title LIKE :searchTerm 
                        OR bt.AccessionNumber LIKE :searchTerm
                        OR bt.ApproverID LIKE :searchTerm
                        OR CONCAT(bt.Approver_first_name, ' ', bt.Approver_middle_name, ' ', bt.Approver_last_name, ' ', bt.Approver_suffix) LIKE :searchTerm)";
    }

    // Add status filter if a status is selected
    if ($status) {
        $sql .= " AND bt.status = :status";
    }

    // Order by borrow date descending
    $sql .= " ORDER BY bt.borrow_date DESC";

    // Prepare the statement
    $stmt = $pdo->prepare($sql);

    // Bind the parameters
    $stmt->bindValue(':userId', $userId);
    if ($searchTerm) {
        $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%');
    }
    if ($status) {
        $stmt->bindValue(':status', $status);
    }

    // Execute the statement
    $stmt->execute();

    // Return the result
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get the search term and status from the URL
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Get the borrowing history for the logged-in user
$history = getUserBorrowingHistory($searchTerm, $status, $_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrowing History</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/../components/css/view.css">
    <link rel="icon" href="../components/image/book.png" type="image/x-icon">
</head>
<body>
    <!-- Navbar -->
    <?php include '../borrower/layout/navbar.php'; ?>

    <!-- Main Content -->
    <div class="content-wrapper">
        <div class="container">
            <div class="centered-heading">
                <h2>Borrowing History</h2>
            </div>

            <!-- Search and Filter Form -->
            <form method="GET" action="borrow_history.php" class="mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label for="search" class="form-label">Search:</label>
                        <input type="text" name="search" id="search" class="form-control" placeholder="Accession Number | Title | Approver ID | Approver Name"
                            value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="status" class="form-label">Filter by Status:</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="borrowed" <?php echo (isset($_GET['status']) && $_GET['status'] == 'borrowed') ? 'selected' : ''; ?>>Borrowed</option>
                            <option value="returned" <?php echo (isset($_GET['status']) && $_GET['status'] == 'returned') ? 'selected' : ''; ?>>Returned</option>
                            <option value="overdue" <?php echo (isset($_GET['status']) && $_GET['status'] == 'overdue') ? 'selected' : ''; ?>>Overdue</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Search</button>
                    </div>
                </div>
            </form>

            <!-- Borrowing History Table -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Accession Number</th>
                            <th>Borrower ID</th>
                            <th>Borrower Name</th>
                            <th>Approver ID</th>
                            <th>Approver Name</th>
                            <th>Status</th>
                            <th>Borrow Date</th>
                            <th>Due Date</th>
                            <th>Return Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $record): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['Title']); ?></td>
                                <td><?php echo htmlspecialchars($record['AccessionNumber']); ?></td>
                                <td><?php echo htmlspecialchars($record['BorrowerID']); ?></td>
                                <td><?php echo htmlspecialchars($record['Borrower_first_name'] . ' ' . $record['Borrower_middle_name'] . ' ' . $record['Borrower_last_name'] . ' ' . $record['Borrower_suffix']); ?></td>
                                <td><?php echo htmlspecialchars($record['ApproverID']); ?></td>
                                <td><?php echo htmlspecialchars($record['Approver_first_name'] . ' ' . $record['Approver_middle_name'] . ' ' . $record['Approver_last_name'] . ' ' . $record['Approver_suffix']); ?></td>
                                <td><?php echo htmlspecialchars($record['status']); ?></td>
                                <td><?php echo htmlspecialchars($record['borrow_date']); ?></td>
                                <td><?php echo htmlspecialchars($record['due_date']); ?></td>
                                <td><?php echo htmlspecialchars($record['return_date']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if (empty($history)): ?>
                    <p class="text-center text-white">No borrowing history found for completed transactions.</p>
                <?php else: ?>

                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap Script -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>