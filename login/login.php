<?php
// Start session
session_start();

// Redirect logged-in users to their respective dashboards
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['user_type']) {
        case 'student':
            header("Location: ../borrower/view.php");
            break;
        case 'faculty':
            header("Location: ../borrower/view.php");
            break;
        case 'staff':
            header("Location: ../staff/view.php");
            break;
        case 'admin':
            header("Location: ../admin/view.php");
            break;
        default:
            header("Location: default_dashboard.php");
            break;
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../components/css/login.css">
    <link rel="icon" href="../components/image/book.png" type="image/x-icon">
</head>
<body>
    <div class="form-container">
        <form method="POST" action="function.php">
            <h2>Library Login</h2>
            <div class="mb-3">
                <label for="email" class="form-label">Enter your email</label>
                <input type="email" class="form-control" id="email" name="email" required>
                <?php if (isset($_SESSION['email_error'])): ?>
                    <div class="error-message" style="color: red;">
                        <?php echo $_SESSION['email_error']; ?>
                    </div>
                    <?php unset($_SESSION['email_error']); ?> <!-- Clear the error after displaying -->
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Enter your password</label>
                <input type="password" class="form-control" id="password" name="password" required>
                <?php if (isset($_SESSION['password_error'])): ?>
                    <div class="error-message" style="color: red;">
                        <?php echo $_SESSION['password_error']; ?>
                    </div>
                    <?php unset($_SESSION['password_error']); ?> <!-- Clear the error after displaying -->
                <?php endif; ?>
            </div>
            <button type="submit" name="login" class="btn btn-primary w-100">Log In</button>
        </form>
    </div>

    <!-- Bootstrap JS (optional, for interactive elements) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>