<!-- Navigation Bar -->
<nav class="navbar">
    <a class="navbar-brand" href="#">Library Management</a>
    <ul class="nav">
        <!-- Navigation Links -->
        <li class="nav-item">
            <a class="nav-link" href="/staff/view.php">Search Resources</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="/staff/transactions/borrowed_resources.php">Borrowed Resources</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="/staff/transactions/fine.php">Overdue Fine</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="borrow_history.php">Borrow History</a>
        </li>
        <!-- User Name and Profile -->
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <?php echo htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
            <ul class="dropdown-menu" aria-labelledby="userDropdown">
                <li><a class="dropdown-item" href="/staff/profile/profile.php">Profile</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="../../login/logout.php">Logout</a></li>
            </ul>
        </li>
    </ul>
</nav>