<?php
session_start();

// Check if the user is logged in and is either a faculty or a student
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin'])) {
    header("Location: ../login/login.php");
    exit();
}

// Include the database connection
include '../../config/db.php';

// Function to generate a unique accession number for resources
function generateAccessionNumber($resourceType) {
    global $pdo;
    $year = date('Y');
    
    $sql = "SELECT MAX(AccessionNumber) AS max_accession FROM LibraryResources WHERE AccessionNumber LIKE ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['P' . $year . '%']);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $lastNumber = $result['max_accession'];
    $nextNumber = $lastNumber ? (intval(substr($lastNumber, -3)) + 1) : 1;
    
    $newAccessionNumber = 'P' . '-' . $year . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

    $sqlCheck = "SELECT COUNT(*) FROM LibraryResources WHERE AccessionNumber = ?";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([$newAccessionNumber]);
    $count = $stmtCheck->fetchColumn();

    while ($count > 0) {
        $nextNumber++;
        $newAccessionNumber = 'P' . '-' . $year . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        $stmtCheck->execute([$newAccessionNumber]);
        $count = $stmtCheck->fetchColumn();
    }

    return $newAccessionNumber;
}

// Get all periodicals
function getPeriodicals($resourceID = null) {
    global $pdo;
    $sql = "
        SELECT 
            LR.ResourceID, 
            LR.Title, 
            LR.Category AS Type, 
            P.ISSN, 
            P.Volume, 
            P.Issue,
            P.PublicationDate,
            LR.AccessionNumber
        FROM LibraryResources LR
        LEFT JOIN Periodicals P ON LR.ResourceID = P.ResourceID
        WHERE LR.ResourceType = 'Periodical'
    ";
    if ($resourceID) {
        $sql .= " AND LR.ResourceID = :resourceID";
    }
    $stmt = $pdo->prepare($sql);
    if ($resourceID) {
        $stmt->bindParam(':resourceID', $resourceID, PDO::PARAM_INT);
    }
    $stmt->execute();
    return $resourceID ? $stmt->fetch(PDO::FETCH_ASSOC) : $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Add periodical
if (isset($_POST['add_periodical'])) {
    $title = $_POST['title'];
    $issn = $_POST['issn'];
    $volume = $_POST['volume'];
    $issue = $_POST['issue'];
    $publicationDate = $_POST['publicationDate'];
    $type = $_POST['type'];

    $accession_number = generateAccessionNumber('Periodical');

    try {
        $pdo->beginTransaction();

        $sqlLibrary = "INSERT INTO LibraryResources (Title, ResourceType, Category, AccessionNumber) 
                       VALUES (?, 'Periodical', ?, ?)";
        $stmtLibrary = $pdo->prepare($sqlLibrary);
        $stmtLibrary->execute([$title, $type, $accession_number]);

        $resourceID = $pdo->lastInsertId();

        $sqlPeriodical = "INSERT INTO Periodicals (ResourceID, ISSN, Volume, Issue, PublicationDate) 
                          VALUES (?, ?, ?, ?, ?)";
        $stmtPeriodical = $pdo->prepare($sqlPeriodical);
        $stmtPeriodical->execute([$resourceID, $issn, $volume, $issue, $publicationDate]);

        $pdo->commit();
        $_SESSION['message'] = "Periodical added successfully with Accession Number: $accession_number";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error adding periodical: " . $e->getMessage();
    }
}

// Delete periodical
if (isset($_GET['delete_periodical'])) {
    $resourceID = $_GET['delete_periodical'];
    $sql = "DELETE FROM LibraryResources WHERE ResourceID = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$resourceID]);
    $_SESSION['message'] = "Periodical deleted successfully!";
}

// Update periodical
if (isset($_POST['edit_periodical'])) {
    $resourceID = $_POST['resourceID'];
    $title = $_POST['title'];
    $issn = $_POST['issn'];
    $volume = $_POST['volume'];
    $issue = $_POST['issue'];
    $publicationDate = $_POST['publicationDate'];
    $type = $_POST['type'];

    try {
        $pdo->beginTransaction();

        $sqlLibrary = "UPDATE LibraryResources SET Title = ?, Category = ? WHERE ResourceID = ?";
        $stmtLibrary = $pdo->prepare($sqlLibrary);
        $stmtLibrary->execute([$title, $type, $resourceID]);

        $sqlPeriodical = "UPDATE Periodicals SET ISSN = ?, Volume = ?, Issue = ?, PublicationDate = ? WHERE ResourceID = ?";
        $stmtPeriodical = $pdo->prepare($sqlPeriodical);
        $stmtPeriodical->execute([$issn, $volume, $issue, $publicationDate, $resourceID]);

        $pdo->commit();
        $_SESSION['message'] = "Periodical updated successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error updating periodical: " . $e->getMessage();
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../components/css/view.css">
    <link rel="icon" href="../../components/image/book.png" type="image/x-icon">
</head>
<body>
<!-- Navbar -->
<?php include '../layout/navbar.php'; ?>

<!-- Main Content -->
<div class="content-wrapper">
    <div class="container">
        <!-- Add Periodical Form -->
        <div class="centered-heading">
            <h2 class="text-center">Add New Periodical</h2>
        </div>
        <form method="POST" action="periodic.php" class="mb-5">
            <div class="d-flex justify-content-center">
                <input type="text" name="title" placeholder="Title" required>
                <input type="text" name="issn" placeholder="ISSN" required>
                <input type="text" name="volume" placeholder="Volume" required>
                <input type="text" name="issue" placeholder="Issue">
                <input type="date" name="publicationDate" placeholder="Publication Date">
                <select name="type" required>
                    <option value="Newspaper">Newspaper</option>
                    <option value="Newsletter">Newsletter</option>
                    <option value="Magazine">Magazine</option>
                    <option value="Journal">Journal</option>
                    <option value="Bulletin">Bulletin</option>
                    <option value="Annual">Annual</option>
                </select>
            </div>
            <button type="submit" class="bt btn-secondary rounded mt-3 w-100" name="add_periodical">Add Periodical</button>
        </form>

        <!-- Edit Periodical Form -->
        <?php if (isset($_GET['edit_periodical'])): 
            $periodical = getPeriodicals($_GET['edit_periodical']);
        ?>

        <div class="centered-heading">
            <h2 class="text-center">Edit Periodical</h2>
        </div>
        <form method="POST" action="periodic.php">
            <div class="d-flex justify-content-center">
                <input type="hidden" name="resourceID" value="<?php echo $periodical['ResourceID']; ?>">
                <input type="text" name="title" value="<?php echo htmlspecialchars($periodical['Title']); ?>" required>
                <input type="text" name="issn" value="<?php echo htmlspecialchars($periodical['ISSN']); ?>" required>
                <input type="text" name="volume" value="<?php echo htmlspecialchars($periodical['Volume']); ?>" required>
                <input type="text" name="issue" value="<?php echo htmlspecialchars($periodical['Issue']); ?>">
                <input type="date" name="publicationDate" value="<?php echo htmlspecialchars($periodical['PublicationDate']); ?>">
                <select name="type" required>
                    <option value="Newspaper" <?php echo ($periodical['Type'] == 'Newspaper') ? 'selected' : ''; ?>>Newspaper</option>
                    <option value="Newsletter" <?php echo ($periodical['Type'] == 'Newsletter') ? 'selected' : ''; ?>>Newsletter</option>
                    <option value="Magazine" <?php echo ($periodical['Type'] == 'Magazine') ? 'selected' : ''; ?>>Magazine</option>
                    <option value="Journal" <?php echo ($periodical['Type'] == 'Journal') ? 'selected' : ''; ?>>Journal</option>
                    <option value="Bulletin" <?php echo ($periodical['Type'] == 'Bulletin') ? 'selected' : ''; ?>>Bulletin</option>
                    <option value="Annual" <?php echo ($periodical['Type'] == 'Annual') ? 'selected' : ''; ?>>Annual</option>
                </select>
            </div>
            <button type="submit" class="bt btn-secondary rounded mt-3 w-100" name="edit_periodical">Update Periodical</button>
        </form>
        <?php endif; ?>

        <!-- Periodical List -->
        <div class="centered-heading">
            <h2 class="text-center">Periodical List</h2>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>ISSN</th>
                        <th>Volume</th>
                        <th>Issue</th>
                        <th>Publication Date</th>
                        <th>Type</th>
                        <th>Accession Number</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($periodicals as $periodical): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($periodical['Title']); ?></td>
                            <td><?php echo htmlspecialchars($periodical['ISSN']); ?></td>
                            <td><?php echo htmlspecialchars($periodical['Volume']); ?></td>
                            <td><?php echo htmlspecialchars($periodical['Issue']); ?></td>
                            <td><?php echo htmlspecialchars($periodical['PublicationDate']); ?></td>
                            <td><?php echo htmlspecialchars($periodical['Type']); ?></td>
                            <td><?php echo htmlspecialchars($periodical['AccessionNumber']); ?></td>
                            <td>
                                <a href="periodic.php?edit_periodical=<?php echo $periodical['ResourceID']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                <a href="periodic.php?delete_periodical=<?php echo $periodical['ResourceID']; ?>" class="btn btn-danger btn-sm">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Trigger modal with success or error message based on PHP flag
    <?php if (isset($message)): ?>
        var messageModal = new bootstrap.Modal(document.getElementById('messageModal'));
        document.getElementById('modalBody').innerHTML = '<?php echo addslashes($message); ?>';
        messageModal.show();
    <?php endif; ?>
</script>

</body>
</html>