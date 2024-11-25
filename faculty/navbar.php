<!-- Sidebar -->
<nav class="sidebar">
    <a class="navbar-brand" href="#">Library Management</a>
    <ul class="nav flex-column">
        <!-- User Name and Profile -->
        <li class="nav-item">                 
            <a class="nav-link" href="#"><?php echo htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8'); ?></a>
        </li>
        <!-- Navigation Links -->
        <li class="nav-item">
            <a class="nav-link" href="view.php">Borrow Resources</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="transact.php">Borrowed Resources And Fines</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="borrow_history.php">Borrow History</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="../login/logout.php">Logout</a>
        </li>
    </ul>
</nav>