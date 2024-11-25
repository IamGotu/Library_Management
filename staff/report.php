<?php
session_start();

// Check if the user is logged in and is a staff member
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'staff') {
    header("Location: ../login/login.php");
    exit();
}

// Include the database connection
include '../config/db.php';

// Function to generate borrowing history report for a specific user
function getBorrowHistory($user_id) {
    global $pdo;
    $sql = "SELECT bt.*, br.Title, br.Author, br.ISBN, br.Publisher
            FROM borrow_transactions bt
            JOIN libraryresources br ON bt.resource_id = br.ResourceID
            WHERE bt.user_id = :user_id
            ORDER BY bt.borrow_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get popular books based on borrowing frequency
function getPopularBooks() {
    global $pdo;
    $sql = "SELECT br.Title, COUNT(bt.id) as borrow_count
            FROM borrow_transactions bt
            JOIN libraryresources br ON bt.resource_id = br.ResourceID
            WHERE bt.status = 'returned'
            GROUP BY br.Title
            ORDER BY borrow_count DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to list overdue books with fines
function getOverdueBooks() {
    global $pdo;
    $sql = "SELECT bt.*, u.first_name, u.last_name, f.fine_amount
            FROM borrow_transactions bt
            JOIN users u ON bt.user_id = u.id
            LEFT JOIN fines f ON bt.id = f.transaction_id
            WHERE bt.due_date < CURDATE() AND bt.status = 'borrowed'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to create inventory summary by category, availability, and Accession Number
function getInventorySummary() {
    global $pdo;
    $sql = "
        SELECT 
            lr.Category, 
            lr.AvailabilityStatus, 
            COUNT(lr.ResourceID) as total_books,
            GROUP_CONCAT(lr.AccessionNumber) as AccessionNumbers
        FROM 
            libraryresources lr
        GROUP BY 
            lr.Category, lr.AvailabilityStatus
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// Function to get all users for search
function getAllUsers() {
    global $pdo;
    $sql = "SELECT id, CONCAT(first_name, ' ', last_name) as full_name FROM users";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch all users for search options
$users = getAllUsers();

// Example Usage (User ID is dynamically retrieved from the form input)
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null; // Default to null if not set
$borrow_history = $user_id ? getBorrowHistory($user_id) : [];
$popular_books = getPopularBooks();
$overdue_books = getOverdueBooks();
$inventory_summary = getInventorySummary();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Staff Report</title>
    <link rel="stylesheet" href="../css/styles.css"> <!-- Include your CSS file if needed -->
    <script>
        function showBorrowHistory(userId) {
            var historyDiv = document.getElementById('borrow-history');
            historyDiv.innerHTML = '<p>Loading...</p>';

            // Create an XMLHttpRequest object to fetch the user's borrow history
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'fetch_borrow_history.php?user_id=' + userId, true);
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    historyDiv.innerHTML = xhr.responseText;
                }
            };
            xhr.send();
        }
    </script>
</head>
<body>

    <h1>Library Staff Reports</h1>

    <!-- User Search and Borrow History -->
    <h3>Select User to View Borrowing History:</h3>
    <table border="1">
        <thead>
            <tr>
                <th>User ID</th>
                <th>User Name</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['id']); ?></td>
                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                    <td><button onclick="showBorrowHistory(<?php echo $user['id']; ?>)">View Borrowing History</button></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Borrow History Display -->
    <div id="borrow-history"></div>

    <!-- Popular Books Report -->
    <h3>Popular Books Based on Borrowing Frequency</h3>
    <table border="1">
        <thead>
            <tr>
                <th>Book Title</th>
                <th>Times Borrowed</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($popular_books as $book): ?>
                <tr>
                    <td><?php echo htmlspecialchars($book['Title']); ?></td>
                    <td><?php echo htmlspecialchars($book['borrow_count']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Overdue Books Report -->
    <h3>Overdue Books</h3>
    <table border="1">
        <thead>
            <tr>
                <th>Book Title</th>
                <th>User Name</th>
                <th>Fine Amount</th>
                <th>Due Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($overdue_books as $overdue): ?>
                <tr>
                    <td><?php echo htmlspecialchars($overdue['Title']); ?></td>
                    <td><?php echo htmlspecialchars($overdue['first_name']) . ' ' . htmlspecialchars($overdue['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($overdue['fine_amount']); ?></td>
                    <td><?php echo htmlspecialchars($overdue['due_date']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Inventory Summary Report -->
    <h3>Inventory Summary</h3>
    <table border="1">
        <thead>
            <tr>
                <th>Category</th>
                <th>Availability Status</th>
                <th>Total Books</th>
                <th>Accession Number</th>
            </tr>
        </thead>
        <tbody>
    <?php foreach ($inventory_summary as $inventory): ?>
        <tr>
            <td><?php echo htmlspecialchars($inventory['Category']); ?></td>
            <td><?php echo htmlspecialchars($inventory['AvailabilityStatus']); ?></td>
            <td><?php echo htmlspecialchars($inventory['total_books']); ?></td>
            <td>
                <?php 
                // Check if AccessionNumber exists and is not empty
                if (isset($inventory['AccessionNumber']) && !empty($inventory['AccessionNumber'])) {
                    echo htmlspecialchars($inventory['AccessionNumber']);
                } else {
                    echo 'None available'; // You can customize this message as needed
                }
                ?>
            </td>
        </tr>
    <?php endforeach; ?>
</tbody>

    </table>
 <!-- Go Back Button -->
 <a href="dashboard.php" class="go-back-btn">Go Back to Dashboard</a>

</body>
</html>
