<?php
session_start();
include '../config/db.php';

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Query to fetch user details
    $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    // Check if user exists
    if ($user) {
        $isPasswordValid = password_verify($password, $user['password']);
        $user_type = $user['user_type'];

        // Log the attempt for admin and staff
        if ($user_type === 'admin' || $user_type === 'staff') {
            $status = $isPasswordValid ? 'success' : 'failed';
            $log_sql = "INSERT INTO user_login_history (user_id, email, user_type, status) VALUES (:user_id, :email, :user_type, :status)";
            $log_stmt = $pdo->prepare($log_sql);
            $log_stmt->execute([
                'user_id'   => $user['id'],
                'email'     => $email,
                'user_type' => $user_type,
                'status'    => $status
            ]);
        }

        // Handle successful login
        if ($isPasswordValid) {
            $_SESSION['user_id'] = $user['membership_id'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name'] . ' ' . $user['suffix'];
            $_SESSION['user_type'] = $user['user_type'];

            // Redirect user based on their role (user_type)
            switch ($user['user_type']) {
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
        } else {
            $_SESSION['password_error'] = "Invalid password.";
            header("Location: login.php");
            exit();
        }
    } else {
        // If user doesn't exist
        $_SESSION['email_error'] = "Invalid email address.";
        header("Location: login.php");
        exit();
    }
}
?>
