<?php
// Start the session to store user info
session_start();

// Include database connection
include '../config/db.php';

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
    if ($user && password_verify($password, $user['password'])) {
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
        $error_message = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Error</title>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Open+Sans:wght@200;300;400;500;600;700&display=swap");

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: "Open Sans", sans-serif;
}

body {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  width: 100%;
  padding: 0 10px;
}

body::before {
  content: "";
  position: absolute;
  width: 100%;
  height: 100%;
  background: url("https://www.codingnepalweb.com/demos/create-glassmorphism-login-form-html-css/hero-bg.jpg"), #000;
  background-position: center;
  background-size: cover;
}

.wrapper {
  width: 400px;
  border-radius: 8px;
  padding: 30px;
  text-align: center;
  border: 1px solid rgba(255, 255, 255, 0.5);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
}

form {
  display: flex;
  flex-direction: column;
}

h2 {
  font-size: 2rem;
  margin-bottom: 20px;
  color: #fff;
}

.input-field {
  position: relative;
  border-bottom: 2px solid #ccc;
  margin: 15px 0;
}

.input-field label {
  position: absolute;
  top: 50%;
  left: 0;
  transform: translateY(-50%);
  color: #fff;
  font-size: 16px;
  pointer-events: none;
  transition: 0.15s ease;
}

.input-field input {
  width: 100%;
  height: 40px;
  background: transparent;
  border: none;
  outline: none;
  font-size: 16px;
  color: #fff;
}

.input-field input:focus~label,
.input-field input:valid~label {
  font-size: 0.8rem;
  top: 10px;
  transform: translateY(-120%);
}

.forget {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin: 25px 0 35px 0;
  color: #fff;
}

#remember {
  accent-color: #fff;
}

.forget label {
  display: flex;
  align-items: center;
}

.forget label p {
  margin-left: 8px;
}

.wrapper a {
  color: #efefef;
  text-decoration: none;
}

.wrapper a:hover {
  text-decoration: underline;
}

button {
  background: #fff;
  color: #000;
  font-weight: 600;
  border: none;
  padding: 12px 20px;
  cursor: pointer;
  border-radius: 3px;
  font-size: 16px;
  border: 2px solid transparent;
  transition: 0.3s ease;
}

button:hover {
  color: #fff;
  border-color: #fff;
  background: rgba(255, 255, 255, 0.15);
}

.register {
  text-align: center;
  margin-top: 30px;
  color: #fff;
}

    </style>
    </head>
</head>
<body>
    <div class="wrapper">
        <form method="POST" action="login.php">
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
