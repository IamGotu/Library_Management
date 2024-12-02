<?php
session_start();

// Check if the user is logged in and is a staff member
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'staff') {
    header("Location: ../../login/login.php");
    exit();
}

// Include the database connection
include '../../config/db.php';

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
        echo "Resource not found. Please check the ResourceID.";
        exit();
    }

    // Check if the resource is currently borrowed
    $stmt = $pdo->prepare("SELECT * FROM borrow_transactions WHERE ResourceID = :resourceID AND status = 'borrowed'");
    $stmt->bindParam(':resourceID', $resourceID, PDO::PARAM_INT);
    $stmt->execute();
    $borrowTransaction = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    header("Location: ../view.php");
    exit();
}

// Handle the borrow process
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate borrower ID exists and is either a student or faculty
    $stmt = $pdo->prepare("SELECT * FROM users WHERE membership_id = :borrowerID AND user_type IN ('student', 'faculty') AND status = 'active'");
    $stmt->bindParam(':borrowerID', $borrowerID, PDO::PARAM_INT);
    $stmt->execute();
    $borrower = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$borrower) {
        echo "Invalid borrower ID. Please ensure the user exists and is a student or faculty.";
        exit();
    }

    // Check how many active borrow transactions the user has
    $borrowLimit = ($borrower['user_type'] == 'student') ? 3 : 5;

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM borrow_transactions WHERE BorrowerID = :borrowerID AND status = 'borrowed'");
    $stmt->bindParam(':borrowerID', $borrowerID, PDO::PARAM_INT);
    $stmt->execute();
    $activeBorrows = $stmt->fetchColumn();

    // Check if the user has reached the borrow limit
    if ($activeBorrows >= $borrowLimit) {
        echo "Alread reached borrowing limit. Return resources before borrowing more.";
        exit();
    }

    // Proceed with the borrow process if the resource is available
    if ($resource['AvailabilityStatus'] == 'Available') {
        $borrowDate = date('Y-m-d');
        $dueDate = date('Y-m-d', strtotime('+14 days'));

        // Insert into borrow_transactions table
        $stmt = $pdo->prepare("INSERT INTO borrow_transactions 
            (BorrowerID, ApproverID, ResourceID, ResourceType, AccessionNumber, borrow_date, due_date, status) 
            VALUES (:borrower_id, :approver_id, :resource_id, :resource_type, :accession_number, :borrow_date, :due_date, 'borrowed')");
        $stmt->bindParam(':borrower_id', $borrowerID, PDO::PARAM_INT);
        $stmt->bindParam(':approver_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':resource_id', $resourceID, PDO::PARAM_INT);
        $stmt->bindParam(':resource_type', $resource['Category'], PDO::PARAM_STR);
        $stmt->bindParam(':accession_number', $resource['AccessionNumber'], PDO::PARAM_STR);
        $stmt->bindParam(':borrow_date', $borrowDate);
        $stmt->bindParam(':due_date', $dueDate);
        $stmt->execute();

        // Update resource availability status
        $stmt = $pdo->prepare("UPDATE libraryresources SET AvailabilityStatus = 'Checked Out' WHERE ResourceID = :resourceID");
        $stmt->bindParam(':resourceID', $resourceID, PDO::PARAM_INT);
        $stmt->execute();

        header("Location: ../view.php");
        exit();
    } else {
        echo "Sorry, this resource is currently unavailable.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrow Resource</title>
    <link rel="stylesheet" href="../style/style.css"> <!-- Add your CSS file -->
</head>
<body>

<!-- Navbar -->
<div class="navbar">
    <h2>Borrow Resource</h2>
</div>

<!-- Resource Information -->
<div class="container">
    <h3>Resource Details</h3>

    <?php if ($resource): ?>
        <table>
            <tr>
                <th>Title</th>
                <td><?php echo htmlspecialchars($resource['Title']); ?></td>
            </tr>
            <tr>
                <th>Category</th>
                <td><?php echo htmlspecialchars($resource['Category']); ?></td>
            </tr>
            <tr>
                <th>Accession Number</th>
                <td><?php echo htmlspecialchars($resource['AccessionNumber']); ?></td>
            </tr>
            <tr>
                <th>Availability</th>
                <td><?php echo htmlspecialchars($resource['AvailabilityStatus'] == 'Available' ? 'Available' : 'Checked Out'); ?></td>
            </tr>

            <!-- Display Borrow and Due Date if Borrowed -->
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
                <!-- Show Borrow Date and Due Date if Available -->
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

        <!-- Borrow Form (if resource is available and not already borrowed) -->
        <?php if ($resource['AvailabilityStatus'] == 'Available' && !$borrowTransaction): ?>
            <form method="POST">
                <label for="approver_id">Approver ID:</label>
                <input type="text" name="approver_id" id="approver_id" value="<?php echo $_SESSION['user_name']; ?>" readonly>
                <br>
                <label for="membership_id">Borrower ID:</label>
                <input type="number" name="membership_id" id="membership_id" required>
                <br>
                <button type="submit">Borrow This Resource</button>
            </form>
        <?php elseif ($borrowTransaction): ?>
            <p>You have already borrowed this resource. The borrow date is: <?php echo htmlspecialchars($borrowTransaction['borrow_date']); ?> and the due date is: <?php echo htmlspecialchars($borrowTransaction['due_date']); ?>.</p>
        <?php else: ?>
            <p>This resource is currently unavailable.</p>
        <?php endif; ?>
        
    <?php else: ?>
        <p>Resource details could not be found.</p>
    <?php endif; ?>

    <!-- Go Back Button -->
    <a href="./../view.php" class="go-back-btn">Go Back</a>
</div>
</body>
</html>
