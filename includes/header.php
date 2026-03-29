<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

$cart_count = 0;
if (isLoggedIn()) {
    $cart_count = getCartItemCount($_SESSION['user_id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ElectraStore - Premium Electronics</title>
    <link rel="stylesheet" href="/app/electrastore/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="container">
                <div class="logo">
                    <a href="/app/electrastore/index.php">
                        <i class="fas fa-bolt"></i>
                        <span>ElectraStore</span>
                    </a>
                </div>
                
                <div class="search-bar">
                    <form action="/app/electrastore/products/search.php" method="GET">
                        <input type="text" name="q" placeholder="Search products...">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                </div>
                
                <div class="nav-links">
                    <a href="/app/electrastore/index.php">Home</a>
                    <a href="/app/electrastore/products/index.php">Products</a>
                    
                    <?php if (isLoggedIn()): ?>
                        <?php if (isAdmin()): ?>
                            <a href="/app/electrastore/admin/index.php">Admin Dashboard</a>
                        <?php elseif ($_SESSION['role'] == 'staff'): ?>
                            <a href="/app/electrastore/staff/index.php">Staff Dashboard</a>
                        <?php endif; ?>
                        <a href="/app/electrastore/orders/history.php">My Orders</a>
                        <a href="/app/electrastore/cart/index.php" class="cart-link">
                            Cart 
                            <?php if ($cart_count > 0): ?>
                                <span class="cart-badge"><?php echo $cart_count; ?></span>
                            <?php endif; ?>
                        </a>
                        <span class="user-greeting">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                        <a href="/app/electrastore/user/logout.php" class="logout-btn">Logout</a>
                    <?php else: ?>
                        <a href="/app/electrastore/user/login.php">Login</a>
                        <a href="/app/electrastore/user/register.php">Register</a>
                    <?php endif; ?>
                </div>
                
                <button class="mobile-menu-btn" id="mobileMenuBtn">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </nav>
    </header>
    
    <main>