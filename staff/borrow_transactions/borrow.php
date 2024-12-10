<?php
session_start();

// Check if the user is logged in and is a staff member
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'staff') {
    header("Location: ../../login/login.php");
    exit();
}

// Include the database connection
include '../../config/db.php';

// Error message array
$errorMessages = [];

// Get the resource ID and borrower ID from the form submission or URL parameter
$resourceID = $_GET['resourceID'] ?? null;
$borrowerID = $_POST['membership_id'] ?? null;

// Initialize the $borrowTransaction variable
$borrowTransaction = null;

// Fetch resource details if a valid resource ID is provided
if ($resourceID) {
    $stmt = $pdo->prepare("SELECT * FROM libraryresources WHERE ResourceID = :resourceID");
    $stmt->bindParam(':resourceID', $resourceID, PDO::PARAM_INT);
    $stmt->execute();
    $resource = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$resource) {
        $errorMessages[] = "Resource not found. Please check the ResourceID.";
    } else {
        // Check if the resource is currently borrowed
        $stmt = $pdo->prepare("SELECT * FROM borrow_transactions WHERE ResourceID = :resourceID AND status = 'borrowed'");
        $stmt->bindParam(':resourceID', $resourceID, PDO::PARAM_INT);
        $stmt->execute();
        $borrowTransaction = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} else {
    header("Location: ../view.php");
    exit();
}

// Retrieve the current logged-in staff's membership_id from the database
$stmt = $pdo->prepare("SELECT membership_id, first_name, middle_name, last_name, suffix FROM users WHERE membership_id = :user_id AND user_type = 'staff'");
$stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$staff = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if staff record is found
if (!$staff) {
    $errorMessages[] = "Staff record not found. Please check the login session.";
} else {
    $approverID = $staff['membership_id']; // This is the staff's membership_id
    $approverFirstName = $staff['first_name'];
    $approverMiddleName = $staff['middle_name'];
    $approverLastName = $staff['last_name'];
    $approverSuffix = $staff['suffix'];
}

// Handle the borrow process
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (empty($borrowerID)) {
        $errorMessages[] = "Borrower ID is required.";
    } else {
        // Validate borrower ID exists and is either a student or faculty
        $stmt = $pdo->prepare("SELECT * FROM users WHERE membership_id = :borrowerID AND user_type IN ('student', 'faculty') AND status = 'active'");
        $stmt->bindParam(':borrowerID', $borrowerID, PDO::PARAM_INT);
        $stmt->execute();
        $borrower = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$borrower) {
            $errorMessages[] = "Invalid borrower ID. Please ensure the user exists and is a student or faculty.";
        } else {
            // Get the borrower's name details
            $borrowerFirstName = $borrower['first_name'];
            $borrowerMiddleName = $borrower['middle_name'];
            $borrowerLastName = $borrower['last_name'];
            $borrowerSuffix = $borrower['suffix'];

            // Check how many active borrow transactions the user has
            $borrowLimit = ($borrower['user_type'] == 'student') ? 3 : 5;

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM borrow_transactions WHERE BorrowerID = :borrowerID AND status = 'borrowed'");
            $stmt->bindParam(':borrowerID', $borrowerID, PDO::PARAM_INT);
            $stmt->execute();
            $activeBorrows = $stmt->fetchColumn();

            if ($activeBorrows >= $borrowLimit) {
                $errorMessages[] = "Borrowing limit reached. Return resources before borrowing more.";
            } elseif ($resource['AvailabilityStatus'] != 'Available') {
                $errorMessages[] = "This resource is currently unavailable.";
            } else {
                // Proceed with borrowing
                $borrowDate = date('Y-m-d');
                $dueDate = date('Y-m-d', strtotime('+14 days'));

                // Insert into borrow_transactions including names
                $stmt = $pdo->prepare("INSERT INTO borrow_transactions 
                    (BorrowerID, Borrower_first_name, Borrower_middle_name, Borrower_last_name, Borrower_suffix, 
                    ApproverID, Approver_first_name, Approver_middle_name, Approver_last_name, Approver_suffix, 
                    ResourceID, ResourceType, AccessionNumber, borrow_date, due_date, status) 
                    VALUES (:borrower_id, :borrower_first_name, :borrower_middle_name, :borrower_last_name, :borrower_suffix, 
                            :approver_id, :approver_first_name, :approver_middle_name, :approver_last_name, :approver_suffix, 
                            :resource_id, :resource_type, :accession_number, :borrow_date, :due_date, 'borrowed')");
                
                $stmt->bindParam(':borrower_id', $borrowerID, PDO::PARAM_INT);
                $stmt->bindParam(':borrower_first_name', $borrowerFirstName, PDO::PARAM_STR);
                $stmt->bindParam(':borrower_middle_name', $borrowerMiddleName, PDO::PARAM_STR);
                $stmt->bindParam(':borrower_last_name', $borrowerLastName, PDO::PARAM_STR);
                $stmt->bindParam(':borrower_suffix', $borrowerSuffix, PDO::PARAM_STR);
                
                $stmt->bindParam(':approver_id', $approverID, PDO::PARAM_INT);
                $stmt->bindParam(':approver_first_name', $approverFirstName, PDO::PARAM_STR);
                $stmt->bindParam(':approver_middle_name', $approverMiddleName, PDO::PARAM_STR);
                $stmt->bindParam(':approver_last_name', $approverLastName, PDO::PARAM_STR);
                $stmt->bindParam(':approver_suffix', $approverSuffix, PDO::PARAM_STR);
                
                $stmt->bindParam(':resource_id', $resourceID, PDO::PARAM_INT);
                $stmt->bindParam(':resource_type', $resource['ResourceType'], PDO::PARAM_STR);
                $stmt->bindParam(':accession_number', $resource['AccessionNumber'], PDO::PARAM_STR);
                $stmt->bindParam(':borrow_date', $borrowDate);
                $stmt->bindParam(':due_date', $dueDate);
                $stmt->execute();

                // Update resource status
                $stmt = $pdo->prepare("UPDATE libraryresources SET AvailabilityStatus = 'Checked Out' WHERE ResourceID = :resourceID");
                $stmt->bindParam(':resourceID', $resourceID, PDO::PARAM_INT);
                $stmt->execute();

                header("Location: ../view.php");
                exit();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrow Resource</title>
    <link rel="stylesheet" href="../../components/css/borrow.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="icon" href="../../components/Image/book.png" type="image/x-icon">
</head>
<body>
    <!-- Main Content Wrapper -->
    <div class="container mt-4">
        <div class="card shadow-lg">
            <div class="card-header text-center">
                <h3>Borrow Resource</h3>
            </div>
            <div class="card-body">

                <?php if ($resource): ?>
                    <table class="table table-striped">
                        <tr>
                            <th>Resource ID</th>
                            <td><?php echo htmlspecialchars($resource['ResourceID']); ?></td>
                        </tr>
                        <tr>
                            <th>Resource Type</th>
                            <td><?php echo htmlspecialchars($resource['ResourceType']); ?></td>
                        </tr>
                        <tr>
                            <th>Accession Number</th>
                            <td><?php echo htmlspecialchars($resource['AccessionNumber']); ?></td>
                        </tr>
                        <tr>
                            <th>Title</th>
                            <td><?php echo htmlspecialchars($resource['Title']); ?></td>
                        </tr>
                        <tr>
                            <th>Category</th>
                            <td><?php echo htmlspecialchars($resource['Category']); ?></td>
                        </tr>
                        <tr>
                            <th>Availability</th>
                            <td><?php echo htmlspecialchars($resource['AvailabilityStatus'] == 'Available' ? 'Available' : 'Checked Out'); ?></td>
                        </tr>

                        <?php if ($borrowTransaction): ?>
                            <tr>
                                <th>Borrow Date</th>
                                <td><?php echo htmlspecialchars($borrowTransaction['borrow_date']); ?></td>
                            </tr>
                            <tr>
                                <th>Due Date</th>
                                <td><?php echo htmlspecialchars($borrowTransaction['due_date']); ?></td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <th>Borrow Date</th>
                                <td><?php echo date('Y-m-d'); ?></td>
                            </tr>
                            <tr>
                                <th>Due Date</th>
                                <td><?php echo date('Y-m-d', strtotime('+14 days')); ?></td>
                            </tr>
                        <?php endif; ?>
                    </table>

                    <?php if ($resource['AvailabilityStatus'] == 'Available' && !$borrowTransaction): ?>
                        <form method="POST" class="mt-3">
                            <div class="mb-3">
                                <label for="approver_id" class="form-label">Approver By:</label>
                                <input type="text" class="form-control" name="approver_id" id="approver_id" value="<?php echo $_SESSION['user_name']; ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="membership_id" class="form-label">Borrower ID:</label>
                                <input type="number" class="form-control" name="membership_id" id="membership_id" required>
                            </div>
                            <button type="submit" class="btn btn-success w-100">Borrow This Resource</button>

                            <!-- Go Back Button -->
                            <a href="./../view.php" class="btn btn-secondary mt-3 w-100">Go Back</a>
                        </form>
                    <?php elseif ($borrowTransaction): ?>
                        <p class="mt-3 text-warning">You have already borrowed this resource. Borrow date: <?php echo htmlspecialchars($borrowTransaction['borrow_date']); ?>, Due date: <?php echo htmlspecialchars($borrowTransaction['due_date']); ?>.</p>
                    <?php else: ?>
                        <p class="mt-3 text-danger">This resource is currently unavailable.</p>
                    <?php endif; ?>

                <?php else: ?>
                    <p class="text-danger">Resource details could not be found.</p>
                <?php endif; ?>

            </div>
        </div>
    </div>

     <!-- Modal for error messages -->
     <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger" id="errorModalLabel">Error</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <ul>
                        <?php foreach ($errorMessages as $message): ?>
                            <li><?php echo htmlspecialchars($message); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Bootstrap Script -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show the modal if there are errors
        <?php if (!empty($errorMessages)): ?>
            var errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
            errorModal.show();
        <?php endif; ?>
    </script>
</body>
</html>