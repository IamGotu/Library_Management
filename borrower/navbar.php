<!-- Navigation Bar -->
<nav class="navbar">
    <a class="navbar-brand" href="#">Library Management</a>
    <ul class="nav">
        <!-- User Name and Profile -->
        <li class="nav-item">
            <a class="nav-link" href="profile.php"><?php echo htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8'); ?></a>
        </li>
        <!-- Navigation Links -->
        <li class="nav-item">
            <a class="nav-link" href="view.php">Search Resources</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="transact.php">Borrowing and Overdue Fines</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="borrow_history.php">Borrow History</a>
        </li>
        <li class="nav-item">
            <a class="nav-link logout-link" href="../login/logout.php">Logout</a>
        </li>
    </ul>
</nav>