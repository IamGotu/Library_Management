<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin'])) {
    header("Location: ../../login/login.php");
    exit();
}

// Include the database connection
include '../../config/db.php';

// Fetch Popular Books Based on Borrowing Frequency
$popular_books_query = "
    SELECT b.Title AS book_title, b.Author, COUNT(bt.ID) AS borrow_count
    FROM borrow_transactions bt
    JOIN books b ON bt.ResourceID = b.BookID
    GROUP BY b.BookID, b.Title, b.Author
    ORDER BY borrow_count DESC
    LIMIT 10
";

// Fetch Inventory Summary by Category
$inventory_summary_query = "
    SELECT Category, 
           SUM(CASE WHEN AvailabilityStatus = 'Available' THEN 1 ELSE 0 END) AS available_books,
           SUM(CASE WHEN AvailabilityStatus = 'Checked Out' THEN 1 ELSE 0 END) AS checked_out_books
    FROM libraryresources
    GROUP BY Category
";

try {
    // Fetch Popular Books
    $pdo_stmt = $pdo->prepare($popular_books_query);
    $pdo_stmt->execute();
    $popular_books_result = $pdo_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Inventory Summary
    $pdo_stmt = $pdo->prepare($inventory_summary_query);
    $pdo_stmt->execute();
    $inventory_summary_result = $pdo_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Reports</title>
    <link rel="stylesheet" href="styles.css"> <!-- Link to your external stylesheet -->
</head>
<body>
    <header>
        <h1>Library System Reports</h1>
    </header>

    <!-- Popular Books Report -->
    <section id="popular-books">
        <h2>Popular Books Based on Borrowing Frequency</h2>
        <table>
            <thead>
                <tr>
                    <th>Book Title</th>
                    <th>Author</th>
                    <th>Number of Borrowings</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($popular_books_result)) {
                    foreach ($popular_books_result as $row) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['book_title']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['Author']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['borrow_count']) . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='3'>No popular books found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </section>

    <!-- Inventory Summary -->
    <section id="inventory-summary">
        <h2>Inventory Summary</h2>
        <table>
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Available Books</th>
                    <th>Checked Out Books</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($inventory_summary_result)) {
                    foreach ($inventory_summary_result as $row) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['Category']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['available_books']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['checked_out_books']) . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='3'>No inventory data available.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </section>
</body>
</html>

<?php
// Close the database connection
$pdo = null;
?>