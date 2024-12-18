<?php
session_start();

// Check if the user is logged in and is a staff member
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
        header("Location: ../../login/login.php");
        exit();
}

// Include the database connection
include '../../config/db.php';

// Fetch login history for admin and staff
$sql = "SELECT ulh.id, u.first_name, u.middle_name, u.last_name, u.suffix, ulh.email, ulh.user_type, ulh.status, ulh.attempt_time 
        FROM user_login_history ulh
        JOIN users u ON ulh.user_id = u.id
        WHERE ulh.user_type IN ('admin', 'staff') 
        ORDER BY ulh.attempt_time DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$login_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">Admin and Staff Login History</h2>
        <table class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>User Type</th>
                    <th>Status</th>
                    <th>Attempt Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($login_history as $index => $log): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= htmlspecialchars($log['first_name'] . ' ' . $log['last_name']) ?></td>
                        <td><?= htmlspecialchars($log['email']) ?></td>
                        <td><?= htmlspecialchars($log['user_type']) ?></td>
                        <td><?= htmlspecialchars($log['status']) ?></td>
                        <td><?= htmlspecialchars($log['attempt_time']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="../admin/view.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
    </div>
</body>
</html>
