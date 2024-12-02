<?php 
session_start();

// Check if the user is logged in and is either a faculty or a student
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['staff'])) {
    header("Location: ../login/login.php");
    exit();
}

// Include the database connection
include '../config/db.php';

// Function to search and filter books, media, and periodicals
function searchLibraryResources($resourceType, $searchTerm = '', $filterCategory = '') {
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
            $sql .= " AND (LR.Title LIKE :searchTerm OR P.Volume LIKE :searchTerm OR LR.AccessionNumber LIKE :searchTerm)";
        }
    }

    // Apply category filter if specified
    if (!empty($filterCategory)) {
        $sql .= " AND LR.Category = :filterCategory";
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
$resourceType = $_GET['resource_type'] ?? 'Book'; // Default to 'Book' if no resource type is selected

// Get the search results
$resources = searchLibraryResources($resourceType, $searchTerm, $filterCategory);

// Get the list of categories based on resource type
$categories = getCategoriesByResourceType($resourceType);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Resources</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../components/css/view.css">
    <link rel="icon" href="../components/image/book.png" type="image/x-icon">
</head>
<body>
    <!-- Navbar -->
    <?php include './layout/navbar.php'; ?>

    <!-- Main Content -->
    <div class="content-wrapper">
        <div class="container">
            <div class="centered-heading">
                <h2>Library Resource</h2>
            </div>

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
                        <input type="text" name="search" id="search" class="form-control" placeholder="Accession Number | Title | Author | Media Type" value="<?php echo htmlspecialchars($searchTerm); ?>">
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
                <button type="submit" class="btn btn-primary mt-3 w-100">Search</button>
                <button 
                    type="button" 
                    class="btn btn-secondary mt-3 w-100" 
                    data-bs-toggle="modal" 
                    data-bs-target="#borrowTransactionModal"
                    data-resource-type="Book" 
                    data-accession-number="ACC12345" 
                    data-borrow-id="123"
                    data-resource-id="456"> <!-- Ensure you add the resource-id here -->
                    Borrow
                </button>
            </form>

            <!-- Search Results Table -->
            <div class="table-container">
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
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($resources)): ?>
                            <?php foreach ($resources as $resource): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($resource['Title']); ?></td>
                                    <td><?php echo htmlspecialchars($resource['Category']); ?></td>
                                    <td><?php echo htmlspecialchars($resource['AccessionNumber']); ?></td>
                                    <td><?php echo htmlspecialchars($resource['AvailabilityStatus'] == 'Available' ? 'Available' : 'Not Available'); ?></td>
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
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7">No resources found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Borrow Transaction Modal -->
            <div class="modal" id="borrowTransactionModal" tabindex="-1" aria-labelledby="borrowTransactionModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="borrowTransactionModalLabel">Borrow Transaction Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="borrowTransactionForm">
                                <!-- Borrower Details -->
                                <div class="mb-3">
                                    <label for="borrowerId" class="form-label">Borrower</label>
                                    <input type="text" class="form-control" id="borrowerId" name="borrower_id">
                                    <div id="borrowerError" class="text-danger"></div>
                                </div>

                                <!-- Approver Details -->
                                <div class="mb-3">
                                    <label for="approverId" class="form-label">Approver</label>
                                    <input type="text" class="form-control" id="approverId" name="approver_id" readonly>
                                </div>

                                <!-- Resource Type -->
                                <div class="mb-3">
                                    <label for="resourceType" class="form-label">Resource Type</label>
                                    <select class="form-select" id="resourceType" name="resource_type">
                                        <option value="Book">Book</option>
                                        <option value="MediaResource">Media Resource</option>
                                        <option value="Periodical">Periodical</option>
                                    </select>
                                </div>

                                <!-- Accession Number -->
                                <div class="mb-3">
                                    <label for="accessionNumber" class="form-label">Accession Number</label>
                                    <input type="text" class="form-control" id="accessionNumber" name="accession_number">
                                </div>

                                <!-- Borrow and Due Dates -->
                                <div class="mb-3">
                                    <label for="borrowDate" class="form-label">Borrow Date</label>
                                    <input type="date" class="form-control" id="borrowDate" name="borrow_date" readonly>
                                </div>

                                <!-- Return Date -->
                                <div class="mb-3">
                                    <label for="returnDate" class="form-label">Return Date</label>
                                    <input type="date" class="form-control" id="returnDate" name="return_date" readonly>
                                </div>

                                <!-- Status -->
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <input type="text" class="form-control" id="status" name="status" value="borrowed" placeholder="Borrowed" readonly>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
    <!-- Bootstrap Script -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>