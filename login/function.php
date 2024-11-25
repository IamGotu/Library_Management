<?php
// Include database connection
include '../config/db.php';

// Start the session to store error messages
session_start();

// Handle form submission
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Prepare SQL query to check if the user exists
    $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    // Check if the user exists
    if ($user) {
        if (password_verify($password, $user['password'])) {
            // User is authenticated, store user details in session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'] . ' ' . $user['suffix'];
            $_SESSION['user_type'] = $user['user_type'];

            // Redirect user based on their role (user_type)
            switch ($user['user_type']) {
                case 'student':
                    header("Location: ../student/dashboard.php");
                    break;
                case 'faculty':
                    header("Location: ../faculty/dashboard.php");
                    break;
                case 'staff':
                    header("Location: ../staff/dashboard.php");
                    break;
                default:
                    header("Location: default_dashboard.php");
                    break;
            }
            exit(); // Prevent further code execution after redirect
        } else {
            // Incorrect password
            $_SESSION['password_error'] = "Invalid password.";
            header("Location: login.php");
            exit();
        }
    } else {
        // Incorrect email
        $_SESSION['email_error'] = "Invalid email address.";
        header("Location: login.php");
        exit();
    }
}
?>