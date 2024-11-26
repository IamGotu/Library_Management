<?php 
session_start();

// Check if the user is logged in and is a faculty
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'faculty') {
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
            LR.Category AS Category, 
            LR.AccessionNumber,
            LR.AvailabilityStatus,
            CASE
                WHEN LR.ResourceType = 'Book' THEN B.PublicationDate
                WHEN LR.ResourceType = 'Periodical' THEN P.PublicationDate
                WHEN LR.ResourceType = 'MediaResource' THEN MR.RunTime
                ELSE NULL
            END AS ExtraInfo, 
            CASE
                WHEN LR.ResourceType = 'Book' THEN B.Author
                WHEN LR.ResourceType = 'MediaResource' THEN MR.Format
                WHEN LR.ResourceType = 'Periodical' THEN P.Volume
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

// Handle borrow action
if (isset($_GET['borrow_id'])) {
    $resourceID = $_GET['borrow_id'];
    $userID = $_SESSION['user_id'];

    // Check faculty member's borrowed items count
    $stmt = $pdo->prepare("SELECT COUNT(*) AS borrowed_count FROM borrow_transactions WHERE user_id = :userID AND return_date IS NULL");
    $stmt->bindValue(':userID', $userID);
    $stmt->execute();
    $borrowedCount = $stmt->fetch(PDO::FETCH_ASSOC)['borrowed_count'];

    if ($borrowedCount >= 5) {
        echo "Please return previously borrowed items before borrowing a new one.";
    } else {
        $stmt = $pdo->prepare("SELECT AvailabilityStatus FROM LibraryResources WHERE ResourceID = :resourceID");
        $stmt->bindValue(':resourceID', $resourceID);
        $stmt->execute();
        $resource = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($resource['AvailabilityStatus'] == 'Available') {
            header("Location: borrow.php?resourceID=$resourceID&resourceType=$resourceType");
            exit();
        } else {
            echo "This resource is currently unavailable.";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Library Resources</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../components/css/faculty.css">
    <link rel="icon" href="../components/image/book.png" type="image/x-icon">
</head>
<body>
    <!-- Navbar -->
    <?php include 'navbar.php'; ?>

    <!-- Main Content -->
    <div class="content-wrapper">
        <div class="container">
            <h2>Library Resource Search</h2>

            <!-- Search and Filter Form -->
            <form method="GET" action="view.php">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="resource_type" class="form-label">Select Resource Type:</label>
                        <select name="resource_type" id="resource_type" class="form-select" onchange="this.form.submit()">
                            <option value="Book" <?php echo ($resourceType == 'Book') ? 'selected' : ''; ?>>Book</option>
                            <option value="MediaResource" <?php echo ($resourceType == 'MediaResource') ? 'selected' : ''; ?>>Media</option>
                            <option value="Periodical" <?php echo ($resourceType == 'Periodical') ? 'selected' : ''; ?>>Periodical</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="search" class="form-label">Search:</label>
                        <input type="text" name="search" id="search" class="form-control" placeholder="Enter keyword" value="<?php echo htmlspecialchars($searchTerm); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="category" class="form-label">Category:</label>
                        <select name="category" id="category" class="form-select">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['Category']); ?>" <?php echo ($filterCategory == $category['Category']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['Category']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-check mt-3">
                    <input type="checkbox" class="form-check-input" id="available_only" name="available_only" <?php echo ($availableOnly) ? 'checked' : ''; ?>>
                    <label for="available_only" class="form-check-label">Available Only</label>
                </div>
                <button type="submit" class="btn btn-primary mt-3 w-100">Search</button>
            </form>

            <!-- Search Results Table -->
            <h3>Search Results</h3>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Accession Number</th>
                        <th>Availability</th>
                        <?php if ($resourceType == 'Book'): ?>
                            <th>Author</th>
                            <th>Publication Date</th>
                        <?php elseif ($resourceType == 'MediaResource'): ?>
                            <th>Run Time</th>
                            <th>Media Type</th>
                        <?php elseif ($resourceType == 'Periodical'): ?>
                            <th>Volume</th>
                            <th>Publication Date</th>
                        <?php endif; ?>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($resources)): ?>
                        <?php foreach ($resources as $resource): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($resource['Title']); ?></td>
                                <td><?php echo htmlspecialchars($resource['Category']); ?></td>
                                <td><?php echo htmlspecialchars($resource['AccessionNumber']); ?></td>
                                <td><?php echo htmlspecialchars($resource['AvailabilityStatus'] == 'Available' ? 'Available' : 'Checked Out'); ?></td>
                                <?php if ($resourceType == 'Book'): ?>
                                    <td><?php echo htmlspecialchars($resource['AuthorOrType'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($resource['ExtraInfo'] ?? 'N/A'); ?></td>
                                <?php elseif ($resourceType == 'MediaResource'): ?>
                                    <td><?php echo htmlspecialchars($resource['ExtraInfo'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($resource['AuthorOrType'] ?? 'N/A'); ?></td>
                                <?php elseif ($resourceType == 'Periodical'): ?>
                                    <td><?php echo htmlspecialchars($resource['AuthorOrType'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($resource['ExtraInfo'] ?? 'N/A'); ?></td>
                                <?php endif; ?>
                                <td>
                                    <?php if ($resource['AvailabilityStatus'] == 'Available'): ?>
                                        <a href="view.php?borrow_id=<?php echo $resource['ResourceID']; ?>" class="btn btn-success btn-sm">Borrow</a>
                                    <?php else: ?>
                                        <span>Unavailable</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7">No resources found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
