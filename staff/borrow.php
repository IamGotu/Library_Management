<?php
session_start();

// Check if the user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'staff') {
    header("Location: ../login/login.php");
    exit();
}

// Include the database connection
include '../config/db.php';

// Get the resource ID from the URL parameter
$resourceID = $_GET['resourceID'] ?? null;

if ($resourceID) {
    // Fetch the resource details based on ResourceID
    $stmt = $pdo->prepare("SELECT * FROM libraryresources WHERE ResourceID = :resourceID");
    $stmt->bindParam(':resourceID', $resourceID, PDO::PARAM_INT);
    $stmt->execute();
    $resource = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if resource exists
    if (!$resource) {
        echo "Resource not found. Please check that ResourceID is correct.";
        exit();
    }

    // Fetch the borrow transaction details for this resource and user
    $stmt = $pdo->prepare("SELECT * FROM borrow_transactions WHERE user_id = :user_id AND resource_id = :resource_id AND status = 'borrowed'");
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindParam(':resource_id', $resourceID, PDO::PARAM_INT);
    $stmt->execute();
    $borrowTransaction = $stmt->fetch(PDO::FETCH_ASSOC);

} else {
    header("Location: search.php");
    exit();
}

// Handle the borrow process when the user submits the form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['accept_conditions']) && $_POST['accept_conditions'] == 'on') {
        if ($resource['AvailabilityStatus'] == 'Available') {
            $borrowDate = date('Y-m-d');
            $dueDate = date('Y-m-d', strtotime('+14 days'));

            // Insert the borrow transaction into the borrow_transactions table
            $stmt = $pdo->prepare("INSERT INTO borrow_transactions (user_id, resource_id, borrow_date, due_date, status) 
                                    VALUES (:user_id, :resource_id, :borrow_date, :due_date, 'borrowed')");
            $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->bindParam(':resource_id', $resourceID, PDO::PARAM_INT);
            $stmt->bindParam(':borrow_date', $borrowDate);
            $stmt->bindParam(':due_date', $dueDate);
            $stmt->execute();

            // Update the availability status of the resource in the LibraryResources table
            $stmt = $pdo->prepare("UPDATE libraryresources SET AvailabilityStatus = 'Checked Out' WHERE ResourceID = :resourceID");
            $stmt->bindParam(':resourceID', $resourceID, PDO::PARAM_INT);
            $stmt->execute();

            header("Location: search.php");
            exit();
        } else {
            echo "Sorry, this resource is currently unavailable.";
        }
    } else {
        echo "You must accept the borrowing terms and conditions before proceeding.";
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
                <label for="accept_conditions">
                    <input type="checkbox" name="accept_conditions" id="accept_conditions" required>
                    I accept the terms and conditions of borrowing the resource, including fines for overdue returns.
                </label>
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
    <a href="search.php" class="go-back-btn">Go Back to Search</a>
</div>

</body>
</html>
