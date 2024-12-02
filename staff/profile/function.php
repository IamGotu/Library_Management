<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'You need to be logged in.']);
    exit();
}

include '../../config/db.php';

$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['current_password'], $_POST['new_password'], $_POST['confirm_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if ($new_password !== $confirm_password) {
            echo json_encode(['status' => 'error', 'message' => 'New password and confirm password do not match.']);
            exit();
        }

        if (password_verify($current_password, $user['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            $update_sql = "UPDATE users SET password = ? WHERE id = ?";
            $update_stmt = $pdo->prepare($update_sql);
            if ($update_stmt->execute([$hashed_password, $user_id])) {
                // Optionally reset the session or perform a re-login
                echo json_encode(['status' => 'success', 'message' => 'Password updated successfully!']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Password update failed.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Current password is incorrect.']);
        }
    }
}
?>