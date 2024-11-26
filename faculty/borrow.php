<?php
session_start();

// Check if the user is logged in and is a faculty
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'faculty') {
    header("Location: ../login/login.php");
    exit();
}

// Include the database connection
include '../config/db.php';

// Get the resource ID from the URL parameter
$resourceID = $_GET['resourceID'] ?? null;

if ($resourceID) {
    // Fetch the resource details
    $stmt = $pdo->prepare("SELECT * FROM libraryresources WHERE ResourceID = :resourceID");
    $stmt->bindParam(':resourceID', $resourceID, PDO::PARAM_INT);
    $stmt->execute();
    $resource = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$resource) {
        echo "Resource not found.";
        exit();
    }

    // Fetch existing borrow transaction
    $stmt = $pdo->prepare("SELECT * FROM borrow_transactions WHERE user_id = :user_id AND resource_id = :resource_id AND status = 'borrowed'");
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindParam(':resource_id', $resourceID, PDO::PARAM_INT);
    $stmt->execute();
    $borrowTransaction = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    header("Location: view.php");
    exit();
}

// Handle borrow request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['accept_conditions']) && $_POST['accept_conditions'] == 'on') {
        if ($resource['AvailabilityStatus'] == 'Available') {
            $borrowDate = date('Y-m-d');
            $dueDate = date('Y-m-d', strtotime('+14 days'));

            // Record the transaction
            $stmt = $pdo->prepare("INSERT INTO borrow_transactions (user_id, resource_id, borrow_date, due_date, status) 
                                    VALUES (:user_id, :resource_id, :borrow_date, :due_date, 'borrowed')");
            $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->bindParam(':resource_id', $resourceID, PDO::PARAM_INT);
            $stmt->bindParam(':borrow_date', $borrowDate);
            $stmt->bindParam(':due_date', $dueDate);
            $stmt->execute();

            // Update resource availability
            $stmt = $pdo->prepare("UPDATE libraryresources SET AvailabilityStatus = 'Checked Out' WHERE ResourceID = :resourceID");
            $stmt->bindParam(':resourceID', $resourceID, PDO::PARAM_INT);
            $stmt->execute();

            header("Location: view.php");
            exit();
        } else {
            $errorMessage = "This resource is unavailable.";
        }
    } else {
        $errorMessage = "You must accept the terms and conditions.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrow Resource</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../components/css/faculty.css">
    <link rel="stylesheet" href="../components/css/sidebar.css">
</head>
<body>

<?php include 'navbar.php'; ?>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <div class="container">
            <h2>Borrow Resource</h2>

            <!-- Resource Details -->
            <div class="card-header">
                <h4>Resource Information</h4>
            </div>
            <div class="card-body">
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

                        <?php if ($borrowTransaction): ?>
                            <tr>
                                <th>Borrow Date</th>
                                <td><?php echo htmlspecialchars($borrowTransaction['borrow_date']); ?></td>
                            </tr>
                            <tr>
                                <th>Due Date</th>
                                <td><?php echo htmlspecialchars($borrowTransaction['due_date']); ?></td>
                            </tr>
                        <?php endif; ?>
                    </table>
                <?php else: ?>
                    <p>Resource details not available.</p>
                <?php endif; ?>
            </div>

            <br>

            <!-- Borrow Form -->
            <?php if ($resource['AvailabilityStatus'] == 'Available' && !$borrowTransaction): ?>
                <div class="card-header">
                    <h4>Borrow This Resource</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($errorMessage)): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="accept_conditions" name="accept_conditions" required>
                            <label for="accept_conditions" class="form-check-label">
                                I accept the terms and conditions of borrowing.
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Borrow</button>
                    </form>
                </div>
            <?php elseif ($borrowTransaction): ?>
                <div class="alert alert-info">
                    You have already borrowed this resource. Borrowed on: <strong><?php echo htmlspecialchars($borrowTransaction['borrow_date']); ?></strong>, Due date: <strong><?php echo htmlspecialchars($borrowTransaction['due_date']); ?></strong>.
                </div>
            <?php else: ?>
                <div class="alert alert-warning">This resource is currently unavailable.</div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>