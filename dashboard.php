<?php
// Include any necessary files like database connection if needed
include 'config/db.php';
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
</head>
<body>

<!-- Main Container -->
<div class="main-container">

    <!-- Centered Content -->
    <div class="content">
        <h1 class="page-title">Library Management System</h1>

        <!-- Navigation Links -->
        <div class="nav-center">
            <a href="user/index.php" class="dashboard-link">User Management</a>
            <a href="book_manage/index.php" class="dashboard-link">Book Management</a>
            <a href="book_manage/media.php" class="dashboard-link">Media Management</a>
            <a href="book_manage/periodic.php" class="dashboard-link">Periodicals Management</a>
            <a href="book_manage/book.php" class="dashboard-link">Search here</a>
            <a href="#" class="dashboard-link">Reports</a>
            <a href="#" class="dashboard-link">Manage Books</a>
        </div>


</body>
</html>
