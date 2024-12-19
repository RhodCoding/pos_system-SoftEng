<?php
// includes/header_employee.php
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $page_title ?? 'Bakery POS'; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <a class="navbar-brand" href="#">Bakery POS</a>
        <?php if (isLoggedIn()): ?>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/pos">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/pos/current_orders.php">Current Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/pos/products.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/pos/sales_history.php">Sales History</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/pos/settings.php">Settings</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <span class="nav-link">Welcome, <?php echo $_SESSION['username']; ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/pos_system/logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        <?php endif; ?>
    </nav>
</body>
</html>
