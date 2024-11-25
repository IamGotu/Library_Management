<?php
// Start the session to store user info
session_start();

// Include database connection
include '../config/db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Error</title>
</head>
<body>
    <div class="wrapper">
        <form method="POST" action="function.php">
            <h2>Login</h2>
            
            <div class="input-field">
                <input type="email" name="email" required>
                <label>Enter your email</label>
            </div>
            
            <div class="input-field">
                <input type="password" name="password" required>
                <label>Enter your password</label>
            </div>
            
    
            
            <button type="submit" name="login">Log In</button>
            
            

            <?php if (isset($error_message)): ?>
                <p style="color: red;"><?php echo $error_message; ?></p>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>
