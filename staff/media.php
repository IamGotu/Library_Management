<?php
session_start();

// Check if the user is logged in and is a staff member
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'staff') {
    header("Location: ../login/login.php");
    exit();
}

// Include the database connection
include '../config/db.php';

// Function to generate unique accession number
function generateAccessionNumber($resourceType) {
    global $pdo;
    // Fetch the current year
    $year = date('Y');
    
    // Get the last used accession number from the library resources
    $sql = "SELECT MAX(AccessionNumber) AS max_accession FROM LibraryResources WHERE AccessionNumber LIKE ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['R' . $year . '%']);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Extract the number and increment it
    $lastNumber = $result['max_accession'];
    $nextNumber = $lastNumber ? (intval(substr($lastNumber, -3)) + 1) : 1;
    
    // Format the new accession number as "R-Year-XXXX" (e.g., "R-2023-0001")
    $newAccessionNumber = 'R' . '-' . $year . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

    // Check if the generated accession number already exists
    $sqlCheck = "SELECT COUNT(*) FROM LibraryResources WHERE AccessionNumber = ?";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([$newAccessionNumber]);
    $count = $stmtCheck->fetchColumn();

    // If the number already exists, increment it until a unique number is found
    while ($count > 0) {
        $nextNumber++;
        $newAccessionNumber = 'R' . '-' . $year . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        $stmtCheck->execute([$newAccessionNumber]);
        $count = $stmtCheck->fetchColumn();
    }

    // Return the unique accession number
    return $newAccessionNumber;
}

// Function to get all media resources
function getMediaResources() {
    global $pdo;
    $sql = "
        SELECT 
            LR.ResourceID, 
            LR.Title, 
            LR.Category AS MediaType, 
            LR.AccessionNumber, 
            MR.Format, 
            MR.Runtime, 
            MR.MediaType
        FROM LibraryResources LR
        LEFT JOIN MediaResources MR ON LR.ResourceID = MR.ResourceID
        WHERE LR.ResourceType = 'MediaResource';
    ";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to add a new media resource
if (isset($_POST['add_media'])) {
    $title = $_POST['title'];
    $format = $_POST['format'];
    $runtime = $_POST['runtime'];
    $media_type = $_POST['media_type'];

    // If custom format or media type is added, replace the default with the custom one
    if ($_POST['custom_format']) {
        $format = $_POST['custom_format'];
    }
    if ($_POST['custom_media_type']) {
        $media_type = $_POST['custom_media_type'];
    }

    // Generate accession number
    $accession_number = generateAccessionNumber('Media');

    try {
        $pdo->beginTransaction();

        // Insert into LibraryResources
        $sqlLibrary = "INSERT INTO LibraryResources (Title, ResourceType, Category, AccessionNumber) 
                       VALUES (?, 'MediaResource', ?, ?)";
        $stmtLibrary = $pdo->prepare($sqlLibrary);
        $stmtLibrary->execute([$title, $media_type, $accession_number]);

        $resourceID = $pdo->lastInsertId(); // Get the ResourceID of the inserted media resource

        // Insert into MediaResources
        $sqlMedia = "INSERT INTO MediaResources (ResourceID, Format, Runtime, MediaType) 
                     VALUES (?, ?, ?, ?)";
        $stmtMedia = $pdo->prepare($sqlMedia);
        $stmtMedia->execute([$resourceID, $format, $runtime, $media_type]);

        $pdo->commit();
        echo "Media Resource added successfully with Accession Number: $accession_number";
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Error adding media resource: " . $e->getMessage();
    }
}

// Function to delete a media resource
if (isset($_GET['delete_media'])) {
    $resourceID = $_GET['delete_media'];
    $sql = "DELETE FROM LibraryResources WHERE ResourceID = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$resourceID]);
    echo "Media resource deleted successfully!";
}

// Function to update a media resource
if (isset($_POST['edit_media'])) {
    $resourceID = $_POST['resourceID'];
    $title = $_POST['title'];
    $format = $_POST['format'];
    $runtime = $_POST['runtime'];
    $media_type = $_POST['media_type'];

    // If custom format or media type is added, replace the default with the custom one
    if ($_POST['custom_format']) {
        $format = $_POST['custom_format'];
    }
    if ($_POST['custom_media_type']) {
        $media_type = $_POST['custom_media_type'];
    }

    try {
        $pdo->beginTransaction();

        // Update LibraryResources
        $sqlLibrary = "UPDATE LibraryResources SET Title = ?, Category = ? WHERE ResourceID = ?";
        $stmtLibrary = $pdo->prepare($sqlLibrary);
        $stmtLibrary->execute([$title, $media_type, $resourceID]);

        // Update MediaResources
        $sqlMedia = "UPDATE MediaResources SET Format = ?, Runtime = ?, MediaType = ? WHERE ResourceID = ?";
        $stmtMedia = $pdo->prepare($sqlMedia);
        $stmtMedia->execute([$format, $runtime, $media_type, $resourceID]);

        $pdo->commit();
        echo "Media resource updated successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Error updating media resource: " . $e->getMessage();
    }
}

$mediaResources = getMediaResources();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media Resource Management</title>
    <link rel="stylesheet" href="/style/style.css"> <!-- Add your CSS file -->
    <script>
        // JavaScript to show/hide the custom input field based on dropdown selection
        function toggleCustomField(field) {
            var customField = document.getElementById('custom_' + field);
            var dropdown = document.getElementById(field);

            if (dropdown.value === 'Other') {
                customField.style.display = 'block';
            } else {
                customField.style.display = 'none';
            }
        }
    </script>
</head>
<body>

<!-- Navbar -->
<div class="navbar">
    <h2>Library Media Resource Management</h2>
</div>

<!-- Container -->
<div class="container">

    <!-- Add New Media Resource Form -->
    <h3>Add Media Resource</h3>
    <form method="POST" action="media.php">
        <input type="text" name="title" placeholder="Title" required>
        
        <!-- Format Dropdown -->
        <label for="format">Format</label>
        <select name="format" id="format" onchange="toggleCustomField('format')" required>
            <option value="DVD">DVD</option>
            <option value="Blu-ray">Blu-ray</option>
            <option value="VHS">VHS</option>
            <option value="Digital">Digital</option>
            <option value="Other">Other</option>
        </select>
        <input type="text" name="custom_format" id="custom_format" placeholder="Enter custom format" style="display:none;">
        
        <!-- Runtime -->
        <input type="text" name="runtime" placeholder="Runtime">
        
        <!-- Media Type Dropdown -->
        <label for="media_type">Media Type</label>
        <select name="media_type" id="media_type" onchange="toggleCustomField('media_type')" required>
            <option value="Film">Film</option>
            <option value="Music">Music</option>
            <option value="AudioBook">Audio Book</option>
            <option value="Other">Other</option>
        </select>
        <input type="text" name="custom_media_type" id="custom_media_type" placeholder="Enter custom media type" style="display:none;">

        <button type="submit" name="add_media">Add Media Resource</button>
    </form>

    <!-- Media Resource List -->
    <h3>Media Resource List</h3>
    <table>
        <tr>
            <th>Title</th>
            <th>Format</th>
            <th>Runtime</th>
            <th>Media Type</th>
            <th>Accession Number</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($mediaResources as $media): ?>
            <tr>
                <td><?php echo htmlspecialchars($media['Title']); ?></td>
                <td><?php echo htmlspecialchars($media['Format']); ?></td>
                <td><?php echo htmlspecialchars($media['Runtime']); ?></td>
                <td><?php echo htmlspecialchars($media['MediaType']); ?></td>
                <td><?php echo htmlspecialchars($media['AccessionNumber']); ?></td>
                <td>
                    <a href="media.php?edit_media=<?php echo $media['ResourceID']; ?>">Edit</a>
                    <a href="media.php?delete_media=<?php echo $media['ResourceID']; ?>">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <!-- Go Back Button -->
    <a href="dashboard.php" class="go-back-btn">Go Back to Dashboard</a>

</div> <!-- End Container -->

</body>
</html>
