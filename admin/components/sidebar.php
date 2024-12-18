<?php
// Determine the current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>
<ul class="nav flex-column">
    <li class="nav-item">
        <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?> text-white" href="dashboard.php">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $current_page === 'products.php' ? 'active' : ''; ?> text-white" href="products.php">
            <i class="bi bi-box"></i> Products
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $current_page === 'reports.php' ? 'active' : ''; ?> text-white" href="reports.php">
            <i class="bi bi-graph-up"></i> Reports
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $current_page === 'settings.php' ? 'active' : ''; ?> text-white" href="settings.php">
            <i class="bi bi-gear"></i> Settings
        </a>
    </li>
    <li class="nav-item mt-3">
        <a class="nav-link text-danger" href="../logout.php">
            <i class="bi bi-box-arrow-left"></i> Logout
        </a>
    </li>
</ul>
