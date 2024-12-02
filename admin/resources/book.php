<?php
session_start();

// Check if the user is logged in and is a staff member
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'staff') {
    header("Location: ../login/login.php");
    exit();
}

// Include the database connection
include '../config/db.php';

// Function to get all books
function getBooks() {
    global $pdo;
    $sql = "
        SELECT 
            LR.ResourceID, 
            LR.Title, 
            LR.Category AS Genre, 
            B.PublicationDate, 
            B.Author, 
            B.ISBN, 
            B.Publisher, 
            LR.AccessionNumber
        FROM LibraryResources LR
        LEFT JOIN Books B ON LR.ResourceID = B.BookID
        WHERE LR.ResourceType = 'Book';  // Only fetch books
    ";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get a single book for editing
function getBook($resourceID) {
    global $pdo;
    $sql = "
        SELECT 
            LR.ResourceID, 
            LR.Title, 
            LR.Category AS Genre, 
            B.Author, 
            B.ISBN, 
            B.Publisher, 
            B.PublicationDate   
        FROM LibraryResources LR
        LEFT JOIN Books B ON LR.ResourceID = B.BookID
        WHERE LR.ResourceID = ?  // Fetch only based on ResourceID (BookID)
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$resourceID]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to generate a unique Accession Number
function generateAccessionNumber($resourceType) {
    global $pdo;
    
    // Define type codes based on resource type
    $typeCode = '';
    switch ($resourceType) {
        case 'Book':
            $typeCode = 'B';
            break;
        case 'Periodical':
            $typeCode = 'P';
            break;
        case 'Media':
            $typeCode = 'R';
            break;
        default:
            $typeCode = 'U'; // Unknown or unclassified resources
    }

    // Get current year
    $year = date('Y');

    // Find the highest sequence number for the given type and year
    $sql = "SELECT MAX(CAST(SUBSTRING_INDEX(AccessionNumber, '-', -1) AS UNSIGNED)) AS MaxSeq 
            FROM LibraryResources 
            WHERE AccessionNumber LIKE ?"; // Only look for the relevant type and year
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["$typeCode-$year-%"]);
    $maxSeq = $stmt->fetchColumn();

    // Increment the sequence number
    $sequenceNumber = str_pad($maxSeq + 1, 3, '0', STR_PAD_LEFT);

    // Generate the accession number in format: [Type]-[Year]-[Seq]
    return "$typeCode-$year-$sequenceNumber";
}

// Function to add a new book
if (isset($_POST['add_book'])) {
    $title = $_POST['title'];
    $author = $_POST['author'];
    $isbn = $_POST['isbn'];
    $publisher = $_POST['publisher'];
    $genre = $_POST['genre'];
    $publication_date = $_POST['publication_date'];
    
    // Generate accession number based on resource type
    $accession_number = generateAccessionNumber('Book'); // 'Book' here could be dynamic based on resource type

    try {
        $pdo->beginTransaction();

        // Insert into LibraryResources (including AccessionNumber)
        $sqlLibrary = "INSERT INTO LibraryResources (Title, ResourceType, Category, AccessionNumber) 
                       VALUES (?, 'Book', ?, ?)";
        $stmtLibrary = $pdo->prepare($sqlLibrary);
        $stmtLibrary->execute([$title, $genre, $accession_number]);

        $resourceID = $pdo->lastInsertId(); // Get the ResourceID of the inserted book

        // Insert into Books (with PublicationDate)
        $sqlBook = "INSERT INTO Books (BookID, Author, ISBN, Publisher, PublicationDate) 
                    VALUES (?, ?, ?, ?, ?)";
        $stmtBook = $pdo->prepare($sqlBook);
        $stmtBook->execute([$resourceID, $author, $isbn, $publisher, $publication_date]);

        $pdo->commit();
        echo "Book added successfully with Accession Number: $accession_number";
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Error adding book: " . $e->getMessage();
    }
}

// Function to delete a book
if (isset($_GET['delete_book'])) {
    $resourceID = $_GET['delete_book'];
    $sql = "DELETE FROM LibraryResources WHERE ResourceID = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$resourceID]);
    echo "Book deleted successfully!";
}

// Function to update a book
if (isset($_POST['edit_book'])) {
    $resourceID = $_POST['resourceID'];
    $title = $_POST['title'];
    $author = $_POST['author'];
    $isbn = $_POST['isbn'];
    $publisher = $_POST['publisher'];
    $genre = $_POST['genre'];
    $publication_date = $_POST['publication_date'];

    try {
        $pdo->beginTransaction();

        // Update LibraryResources
        $sqlLibrary = "UPDATE LibraryResources SET Title = ?, Category = ? WHERE ResourceID = ?";
        $stmtLibrary = $pdo->prepare($sqlLibrary);
        $stmtLibrary->execute([$title, $genre, $resourceID]);

        // Update Books
        $sqlBook = "UPDATE Books SET Author = ?, ISBN = ?, Publisher = ?, PublicationDate = ? WHERE BookID = ?";
        $stmtBook = $pdo->prepare($sqlBook);
        $stmtBook->execute([$author, $isbn, $publisher, $publication_date, $resourceID]);

        $pdo->commit();
        echo "Book updated successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Error updating book: " . $e->getMessage();
    }
}

$books = getBooks();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Management</title>
    <link rel="stylesheet" href="/style/style.css"> <!-- Add your CSS file -->
</head>
<body>

<!-- Navbar -->
<div class="navbar">
    <h2>Library Book Management</h2>
</div>

<!-- Container -->
<div class="container">

    <!-- Add New Book Form -->
    <h3>Add Book</h3>
    <form method="POST" action="book.php">
        <input type="text" name="title" placeholder="Title" required>
        <input type="text" name="author" placeholder="Author" required>
        <input type="text" name="isbn" placeholder="ISBN" required>
        <input type="text" name="publisher" placeholder="Publisher">
        <input type="date" name="publication_date" placeholder="Publication Date">
        <select name="genre" required>
            <option value="Fiction">Fiction</option>
            <option value="Non-Fiction">Non-Fiction</option>
            <option value="Academic">Academic</option>
            <option value="Reference">Reference</option>
        </select>
        <button type="submit" name="add_book">Add Book</button>
    </form>

    <!-- Edit Book Form (Only appears if editing) -->
    <?php if (isset($_GET['edit_book'])): 
        $book = getBook($_GET['edit_book']);
    ?>
        <h3>Edit Book</h3>
        <form method="POST" action="book.php">
            <input type="hidden" name="resourceID" value="<?php echo $book['ResourceID']; ?>">
            <input type="text" name="title" value="<?php echo htmlspecialchars($book['Title']); ?>" required>
            <input type="text" name="author" value="<?php echo htmlspecialchars($book['Author']); ?>" required>
            <input type="text" name="isbn" value="<?php echo htmlspecialchars($book['ISBN']); ?>" required>
            <input type="text" name="publisher" value="<?php echo htmlspecialchars($book['Publisher']); ?>">
            <input type="date" name="publication_date" value="<?php echo htmlspecialchars($book['PublicationDate']); ?>">
            <select name="genre" required>
                <option value="Fiction" <?php echo ($book['Genre'] == 'Fiction') ? 'selected' : ''; ?>>Fiction</option>
                <option value="Non-Fiction" <?php echo ($book['Genre'] == 'Non-Fiction') ? 'selected' : ''; ?>>Non-Fiction</option>
                <option value="Academic" <?php echo ($book['Genre'] == 'Academic') ? 'selected' : ''; ?>>Academic</option>
                <option value="Reference" <?php echo ($book['Genre'] == 'Reference') ? 'selected' : ''; ?>>Reference</option>
            </select>
            <button type="submit" name="edit_book">Update Book</button>
        </form>
    <?php endif; ?>

    <!-- Book List -->
    <h3>Book List</h3>
    <table>
        <tr>
            <th>Title</th>
            <th>Author</th>
            <th>ISBN</th>
            <th>Publisher</th>
            <th>Genre</th>
            <th>Publication Date</th>
            <th>Accession Number</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($books as $book): ?>
            <tr>
                <td><?php echo htmlspecialchars($book['Title']); ?></td>
                <td><?php echo htmlspecialchars($book['Author']); ?></td>
                <td><?php echo htmlspecialchars($book['ISBN']); ?></td>
                <td><?php echo htmlspecialchars($book['Publisher']); ?></td>
                <td><?php echo htmlspecialchars($book['Genre']); ?></td>
                <td><?php echo htmlspecialchars($book['PublicationDate']); ?></td>
                <td><?php echo htmlspecialchars($book['AccessionNumber']); ?></td>
                <td>
                    <a href="book.php?edit_book=<?php echo $book['ResourceID']; ?>">Edit</a>
                    <a href="book.php?delete_book=<?php echo $book['ResourceID']; ?>">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <!-- Go Back Button -->
    <a href="dashboard.php" class="go-back-btn">Go Back to Dashboard</a>

</div> <!-- End Container -->

</body>
</html>
