<?php
session_start();

// Check if the user is logged in and is a staff member
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'staff') {
    header("Location: ../login/login.php");
    exit();
}

// Include the database connection
include '../config/db.php';

// Function to search and filter books, media, and periodicals
function searchLibraryResources($resourceType, $searchTerm = '', $filterCategory = '', $availableOnly = false) {
    global $pdo;
    
    // Start building the base SQL query
    $sql = "
        SELECT 
            LR.ResourceID, 
            LR.Title, 
            LR.Category AS Genre, 
            LR.AccessionNumber,
            LR.AvailabilityStatus,
            CASE
                WHEN LR.ResourceType = 'Book' THEN B.PublicationDate
                WHEN LR.ResourceType = 'Periodical' THEN P.PublicationDate
                WHEN LR.ResourceType = 'MediaResource' THEN MR.Format
                ELSE NULL
            END AS ExtraInfo, 
            CASE
                WHEN LR.ResourceType = 'Book' THEN B.Author
                WHEN LR.ResourceType = 'MediaResource' THEN MR.MediaType
                WHEN LR.ResourceType = 'Periodical' THEN P.Publisher
                ELSE NULL
            END AS AuthorOrType
        FROM LibraryResources LR
        LEFT JOIN Books B ON LR.ResourceID = B.BookID AND LR.ResourceType = 'Book'
        LEFT JOIN Periodicals P ON LR.ResourceID = P.ResourceID AND LR.ResourceType = 'Periodical'
        LEFT JOIN MediaResources MR ON LR.ResourceID = MR.ResourceID AND LR.ResourceType = 'MediaResource'
        WHERE LR.ResourceType = :resourceType
    ";

    // Apply search term filtering based on resource type
    if (!empty($searchTerm)) {
        if ($resourceType == 'Book') {
            $sql .= " AND (LR.Title LIKE :searchTerm OR B.Author LIKE :searchTerm OR B.ISBN LIKE :searchTerm OR LR.AccessionNumber LIKE :searchTerm)";
        } elseif ($resourceType == 'MediaResource') {
            $sql .= " AND (LR.Title LIKE :searchTerm OR MR.MediaType LIKE :searchTerm OR MR.Format LIKE :searchTerm OR LR.AccessionNumber LIKE :searchTerm)";
        } elseif ($resourceType == 'Periodical') {
            $sql .= " AND (LR.Title LIKE :searchTerm OR P.Publisher LIKE :searchTerm OR LR.AccessionNumber LIKE :searchTerm)";
        }
    }

    // Apply category filter if specified
    if (!empty($filterCategory)) {
        $sql .= " AND LR.Category = :filterCategory";
    }

    // Apply availability filter if selected
    if ($availableOnly) {
        $sql .= " AND LR.AvailabilityStatus = 'Available'";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':resourceType', $resourceType);

    // Bind parameters based on provided filters
    if (!empty($searchTerm)) {
        $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%');
    }
    if (!empty($filterCategory)) {
        $stmt->bindValue(':filterCategory', $filterCategory);
    }

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get categories based on resource type
function getCategoriesByResourceType($resourceType) {
    global $pdo;
    if ($resourceType == 'Book') {
        $sql = "SELECT DISTINCT Category FROM LibraryResources WHERE ResourceType = 'Book'";
    } elseif ($resourceType == 'Periodical') {
        $sql = "SELECT DISTINCT Category FROM LibraryResources WHERE ResourceType = 'Periodical'";
    } elseif ($resourceType == 'MediaResource') {
        $sql = "SELECT DISTINCT Category FROM LibraryResources WHERE ResourceType = 'MediaResource'";
    } else {
        $sql = "SELECT DISTINCT Category FROM LibraryResources";  // All categories if no specific type
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle search form submission
$searchTerm = $_GET['search'] ?? '';
$filterCategory = $_GET['category'] ?? '';
$availableOnly = isset($_GET['available_only']);
$resourceType = $_GET['resource_type'] ?? 'Book'; // Default to 'Book' if no resource type is selected

// Get the search results
$resources = searchLibraryResources($resourceType, $searchTerm, $filterCategory, $availableOnly);

// Get the list of categories based on resource type
$categories = getCategoriesByResourceType($resourceType);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Library Resources</title>
    <link rel="stylesheet" href="/style/style.css"> <!-- Add your CSS file -->
</head>
<body>

<!-- Navbar -->
<div class="navbar">
    <h2>Library Resource Search</h2>
</div>

<!-- Search and Filter Form -->
<div class="container">
    <h3>Search for a Library Resource</h3>
    <form method="GET" action="book.php">
        <label for="resource_type">Select Resource Type:</label>
        <select name="resource_type" id="resource_type" onchange="this.form.submit()">
            <option value="Book" <?php echo ($resourceType == 'Book') ? 'selected' : ''; ?>>Book</option>
            <option value="MediaResource" <?php echo ($resourceType == 'MediaResource') ? 'selected' : ''; ?>>Media</option>
            <option value="Periodical" <?php echo ($resourceType == 'Periodical') ? 'selected' : ''; ?>>Periodical</option>
        </select>

        <input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($searchTerm); ?>">
        
        <select name="category">
            <option value="">All Categories</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?php echo htmlspecialchars($category['Category']); ?>" <?php echo ($filterCategory == $category['Category']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($category['Category']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <label>
            <input type="checkbox" name="available_only" <?php echo ($availableOnly) ? 'checked' : ''; ?>>
            Available Only
        </label>
        
        <button type="submit">Search</button>
    </form>

    <!-- Resource List -->
    <h3>Search Results</h3>
    <table>
        <tr>
            <th>Title</th>
            <th>Genre</th>
            <th>Accession Number</th>
            <th>Availability</th>
            <th>Author/Type</th>
            <th>Extra Information</th>
        </tr>
        <?php if (!empty($resources)): ?>
            <?php foreach ($resources as $resource): ?>
                <tr>
                    <td><?php echo htmlspecialchars($resource['Title']); ?></td>
                    <td><?php echo htmlspecialchars($resource['Genre']); ?></td>
                    <td><?php echo htmlspecialchars($resource['AccessionNumber']); ?></td>
                    <td><?php echo htmlspecialchars($resource['AvailabilityStatus'] == 'Available' ? 'Available' : 'Checked Out'); ?></td>
                    <td><?php echo htmlspecialchars($resource['AuthorOrType']); ?></td>
                    <td><?php echo htmlspecialchars($resource['ExtraInfo']); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="6">No resources found.</td></tr>
        <?php endif; ?>
    </table>

    <!-- Go Back Button -->
    <a href="dashboard.php" class="go-back-btn">Go Back to Dashboard</a>

</div> <!-- End Container -->

</body>
</html>
