<?php
session_start();

// Check if the user is logged in and is a staff member
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'staff') {
    header("Location: ../login/login.php");
    exit();
}

// Include any necessary files like database connection if needed
include '../config/db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>

    <!-- External References -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet"> <!-- Google Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"> <!-- FontAwesome -->

    <!-- Link to Custom CSS -->
    <link rel="stylesheet" href="/style/style.css"> <!-- Custom CSS inside the 'style' folder -->
    <style>
        /* Styling the Dropdown Button and Menu */
        .dropdown {
            position: relative;
            display: inline-block;
        }
        
        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            min-width: 160px;
            box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.2);
            z-index: 1;
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }

        .dropdown-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }

        .dropdown-content a:hover {
            background-color: #f1f1f1;
        }
    </style>
</head>
<body>
    <!-- Main Container -->
    <div class="main-container">

        <!-- Centered Content -->
        <div class="content">
            <h1 class="page-title">Library Management System</h1>

            <!-- Navigation Links -->
            <div class="nav-center">
                <a href="user_manage.php" class="dashboard-link">User Management</a>
                <a href="book.php" class="dashboard-link">Book Management</a>
                <a href="media.php" class="dashboard-link">Media Management</a>
                <a href="periodic.php" class="dashboard-link">Periodicals Management</a>
                <a href="search.php" class="dashboard-link">Search here</a>
                <a href="return.php" class="dashboard-link">Returns</a>
                <a href="report.php" class="dashboard-link">reports </a>
                <a href="payment.php" class="dashboard-link">Fines and Payments </a>
                <a href="../login/logout.php" class="dashboard-link">Logout</a>
            </div>
        </div>
    </div>
</body>
</html>