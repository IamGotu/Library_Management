<!-- Navigation Bar -->
<nav class="navbar">
    <a class="navbar-brand" href="#">Library Management</a>
    <ul class="nav">
        <!-- Navigation Links -->
        <li class="nav-item">
            <a class="nav-link" href="/admin/view.php">Library Resource</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="/admin/users/user_manage.php">User Management</a>
        </li>   
        <li class="nav-item">
            <a class="nav-link" href="/admin/fines_transactions/fine.php">Overdue Fines Logs</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="/admin/borrow_transactions/borrowed_resources.php">Borrowed Resources</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="/admin/borrow_history.php">Borrow Logs</a>
        </li>
        <!-- User Name and Profile -->
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <?php echo htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
            <ul class="dropdown-menu" aria-labelledby="userDropdown">
                <li><a class="dropdown-item" href="/admin/profile/profile.php">Profile</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="../../login/logout.php">Logout</a></li>
            </ul>
        </li>
    </ul>
</nav>