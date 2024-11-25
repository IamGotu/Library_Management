<?php
session_start();

// Check if the user is logged in and is a staff member
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'staff') {
    header("Location: ../login/login.php");
    exit();
}

// Include the database connection
include '../config/db.php';

// Function to generate a unique accession number for resources
function generateAccessionNumber($type) {
    // Create a prefix based on the type (Media, Periodical, etc.)
    $prefix = strtoupper(substr($type, 0, 3)); // 'Med' for Media, 'Per' for Periodical, etc.

    // Get the current year
    $year = date('Y');

    // Generate a random number for uniqueness
    $random_number = mt_rand(1000, 9999); // You can adjust the range as needed

    // Combine them to form the accession number
    return $prefix . '-' . $year . '-' . $random_number;
}

// Function to get all periodicals
function getPeriodicals() {
    global $pdo;
    $sql = "
        SELECT 
            LR.ResourceID, 
            LR.Title, 
            LR.Category AS Genre, 
            P.ISSN, 
            P.Volume, 
            P.Issue, 
            LR.AccessionNumber
        FROM LibraryResources LR
        LEFT JOIN Periodicals P ON LR.ResourceID = P.ResourceID
        WHERE LR.ResourceType = 'Periodical';
    ";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to add a new periodical
if (isset($_POST['add_periodical'])) {
    $title = $_POST['title'];
    $issn = $_POST['issn'];
    $volume = $_POST['volume'];
    $issue = $_POST['issue'];
    $genre = $_POST['genre'];

    // Generate accession number
    $accession_number = generateAccessionNumber('Periodical');

    try {
        $pdo->beginTransaction();

        // Insert into LibraryResources
        $sqlLibrary = "INSERT INTO LibraryResources (Title, ResourceType, Category, AccessionNumber) 
                       VALUES (?, 'Periodical', ?, ?)";
        $stmtLibrary = $pdo->prepare($sqlLibrary);
        $stmtLibrary->execute([$title, $genre, $accession_number]);

        $resourceID = $pdo->lastInsertId(); // Get the ResourceID of the inserted periodical

        // Insert into Periodicals
        $sqlPeriodical = "INSERT INTO Periodicals (ResourceID, ISSN, Volume, Issue) 
                          VALUES (?, ?, ?, ?)";
        $stmtPeriodical = $pdo->prepare($sqlPeriodical);
        $stmtPeriodical->execute([$resourceID, $issn, $volume, $issue]);

        $pdo->commit();
        echo "Periodical added successfully with Accession Number: $accession_number";
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Error adding periodical: " . $e->getMessage();
    }
}

// Function to delete a periodical
if (isset($_GET['delete_periodical'])) {
    $resourceID = $_GET['delete_periodical'];
    $sql = "DELETE FROM LibraryResources WHERE ResourceID = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$resourceID]);
    echo "Periodical deleted successfully!";
}

// Function to update a periodical
if (isset($_POST['edit_periodical'])) {
    $resourceID = $_POST['resourceID'];
    $title = $_POST['title'];
    $issn = $_POST['issn'];
    $volume = $_POST['volume'];
    $issue = $_POST['issue'];
    $genre = $_POST['genre'];

    try {
        $pdo->beginTransaction();

        // Update LibraryResources
        $sqlLibrary = "UPDATE LibraryResources SET Title = ?, Category = ? WHERE ResourceID = ?";
        $stmtLibrary = $pdo->prepare($sqlLibrary);
        $stmtLibrary->execute([$title, $genre, $resourceID]);

        // Update Periodicals
        $sqlPeriodical = "UPDATE Periodicals SET ISSN = ?, Volume = ?, Issue = ? WHERE ResourceID = ?";
        $stmtPeriodical = $pdo->prepare($sqlPeriodical);
        $stmtPeriodical->execute([$issn, $volume, $issue, $resourceID]);

        $pdo->commit();
        echo "Periodical updated successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Error updating periodical: " . $e->getMessage();
    }
}

$periodicals = getPeriodicals();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Periodical Management</title>
    <link rel="stylesheet" href="/style/style.css"> <!-- Add your CSS file -->
</head>
<body>

<!-- Navbar -->
<div class="navbar">
    <h2>Library Periodical Management</h2>
</div>

<!-- Container -->
<div class="container">

    <!-- Add New Periodical Form -->
    <h3>Add Periodical</h3>
    <form method="POST" action="periodic.php">
        <input type="text" name="title" placeholder="Title" required>
        <input type="text" name="issn" placeholder="ISSN" required>
        <input type="text" name="volume" placeholder="Volume" required>
        <input type="text" name="issue" placeholder="Issue">
        <select name="genre" required>
            <option value="Science">Science</option>
            <option value="Literature">Literature</option>
            <option value="Business">Business</option>
            <option value="History">History</option>
        </select>
        <button type="submit" name="add_periodical">Add Periodical</button>
    </form>

    <!-- Edit Periodical Form (Only appears if editing) -->
    <?php if (isset($_GET['edit_periodical'])): 
        $periodical = getPeriodicals($_GET['edit_periodical']);
    ?>
        <h3>Edit Periodical</h3>
        <form method="POST" action="periodic.php">
            <input type="hidden" name="resourceID" value="<?php echo $periodical['ResourceID']; ?>">
            <input type="text" name="title" value="<?php echo htmlspecialchars($periodical['Title']); ?>" required>
            <input type="text" name="issn" value="<?php echo htmlspecialchars($periodical['ISSN']); ?>" required>
            <input type="text" name="volume" value="<?php echo htmlspecialchars($periodical['Volume']); ?>" required>
            <input type="text" name="issue" value="<?php echo htmlspecialchars($periodical['Issue']); ?>">
            <select name="genre" required>
                <option value="Science" <?php echo ($periodical['Genre'] == 'Science') ? 'selected' : ''; ?>>Science</option>
                <option value="Literature" <?php echo ($periodical['Genre'] == 'Literature') ? 'selected' : ''; ?>>Literature</option>
                <option value="Business" <?php echo ($periodical['Genre'] == 'Business') ? 'selected' : ''; ?>>Business</option>
                <option value="History" <?php echo ($periodical['Genre'] == 'History') ? 'selected' : ''; ?>>History</option>
            </select>
            <button type="submit" name="edit_periodical">Update Periodical</button>
        </form>
    <?php endif; ?>

    <!-- Periodical List -->
    <h3>Periodical List</h3>
    <table>
        <tr>
            <th>Title</th>
            <th>ISSN</th>
            <th>Volume</th>
            <th>Issue</th>
            <th>Accession Number</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($periodicals as $periodical): ?>
            <tr>
                <td><?php echo htmlspecialchars($periodical['Title']); ?></td>
                <td><?php echo htmlspecialchars($periodical['ISSN']); ?></td>
                <td><?php echo htmlspecialchars($periodical['Volume']); ?></td>
                <td><?php echo htmlspecialchars($periodical['Issue']); ?></td>
                <td><?php echo htmlspecialchars($periodical['AccessionNumber']); ?></td>
                <td>
                    <a href="periodic.php?edit_periodical=<?php echo $periodical['ResourceID']; ?>">Edit</a>
                    <a href="periodic.php?delete_periodical=<?php echo $periodical['ResourceID']; ?>">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <!-- Go Back Button -->
    <a href="dashboard.php" class="go-back-btn">Go Back to Dashboard</a>

</div> <!-- End Container -->

</body>
</html>
            