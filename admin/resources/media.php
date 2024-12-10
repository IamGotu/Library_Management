<?php
session_start();

// Check if the user is logged in and is either a faculty or a student
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin'])) {
    header("Location: ../login/login.php");
    exit();
}


// Include the database connection
include '../../config/db.php';

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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../components/css/resources.css"> <!-- Reference CSS -->
    <link rel="icon" href="../../components/image/book.png" type="image/x-icon">
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

    <!-- Container -->
    <div class="container">

        <!-- Add New Media Resource Form -->
        <h3 class="text-center">Add Media Resource</h3>
        <form method="POST" action="media.php">
            <input type="text" name="title" placeholder="Title" required>
            
            <!-- Format Dropdown -->
            <select name="format" id="format" onchange="toggleCustomField('format')" required>
                <option value="">Format</option>
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
            <select name="media_type" id="media_type" onchange="toggleCustomField('media_type')" required>
                <option value="">Media Type</option>
                <option value="Film">Film</option>
                <option value="Music">Music</option>
                <option value="AudioBook">Audio Book</option>
                <option value="Other">Other</option>
            </select>
            <input type="text" name="custom_media_type" id="custom_media_type" placeholder="Enter custom media type" style="display:none;">

            <button type="submit" name="add_media" class="rounded mt-3 w-100">Add Media Resource</button>
        </form>

    <?php if (isset($_GET['edit_media'])): 
        $mediaResources = getMediaResources();  // Use the correct function here
        $media = array_filter($mediaResources, function($media) {
            return $media['ResourceID'] == $_GET['edit_media'];
        });
        $media = array_shift($media);  // Get the media resource with the specified ID
    ?>

    <h3 class="text-center">Edit Media Resource</h3>
    <form method="POST" action="media.php">
        <input type="hidden" name="resourceID" value="<?php echo $media['ResourceID']; ?>">
        <input type="text" name="title" value="<?php echo htmlspecialchars($media['Title']); ?>" required>
        
        <!-- Format Dropdown -->
        <select name="format" id="format" onchange="toggleCustomField('format')" required>
            <option value="DVD" <?php echo ($media['Format'] == 'DVD') ? 'selected' : ''; ?>>DVD</option>
            <option value="Blu-ray" <?php echo ($media['Format'] == 'Blu-ray') ? 'selected' : ''; ?>>Blu-ray</option>
            <option value="VHS" <?php echo ($media['Format'] == 'VHS') ? 'selected' : ''; ?>>VHS</option>
            <option value="Digital" <?php echo ($media['Format'] == 'Digital') ? 'selected' : ''; ?>>Digital</option>
            <option value="Other">Other</option>
        </select>
        <input type="text" name="custom_format" id="custom_format" placeholder="Enter custom format" style="display:none;">

        <!-- Runtime -->
        <input type="text" name="runtime" value="<?php echo htmlspecialchars($media['Runtime']); ?>" placeholder="Runtime">

        <!-- Media Type Dropdown -->
        <select name="media_type" id="media_type" onchange="toggleCustomField('media_type')" required>
            <option value="Film" <?php echo ($media['MediaType'] == 'Film') ? 'selected' : ''; ?>>Film</option>
            <option value="Music" <?php echo ($media['MediaType'] == 'Music') ? 'selected' : ''; ?>>Music</option>
            <option value="AudioBook" <?php echo ($media['MediaType'] == 'AudioBook') ? 'selected' : ''; ?>>Audio Book</option>
            <option value="Other">Other</option>
        </select>
        <input type="text" name="custom_media_type" id="custom_media_type" placeholder="Enter custom media type" style="display:none;">

        <button type="submit" class="rounded mt-3 w-100" name="edit_media">Update Media Resource</button>
    </form>
<?php endif; ?>


    <!-- Media Resource List -->
    <h3 class="text-center">Media Resource List</h3>
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Title</th>
                <th>Format</th>
                <th>Runtime</th>
                <th>Media Type</th>
                <th>Accession Number</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($mediaResources as $media): ?>
                <tr>
                    <td><?php echo htmlspecialchars($media['Title']); ?></td>
                    <td><?php echo htmlspecialchars($media['Format']); ?></td>
                    <td><?php echo htmlspecialchars($media['Runtime']); ?></td>
                    <td><?php echo htmlspecialchars($media['MediaType']); ?></td>
                    <td><?php echo htmlspecialchars($media['AccessionNumber']); ?></td>
                    <td>
                        <a href="media.php?edit_media=<?php echo $media['ResourceID']; ?>" class="btn btn-warning btn-sm">Edit</a>
                        <a href="media.php?delete_media=<?php echo $media['ResourceID']; ?>" class="btn btn-danger btn-sm">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Go Back Button -->
    <a href="../view.php" class="text-center go-back-btn mt-3 w-100">Go Back to Dashboard</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>