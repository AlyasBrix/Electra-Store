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
    <link rel="stylesheet" href="/electrastore/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="container">
                <div class="logo">
                    <a href="/electrastore/index.php">
                        <i class="fas fa-bolt"></i>
                        <span>ElectraStore</span>
                    </a>
                </div>
                
                <div class="search-bar">
                    <form action="/electrastore/products/search.php" method="GET">
                        <input type="text" name="q" placeholder="Search products...">
                        <button type="submit"><i class="fas fa-search"></i></button>
                    </form>
                </div>
                
                <div class="nav-links">
                    <a href="/electrastore/index.php" class="nav-link">
                        <i class="fas fa-home"></i>
                        <span>Home</span>
                    </a>
                    <a href="/electrastore/products/index.php" class="nav-link">
                        <i class="fas fa-store"></i>
                        <span>Shop</span>
                    </a>
                    
                    <?php if (isLoggedIn()): ?>
                        <?php if (isAdmin()): ?>
                            <a href="/electrastore/admin/index.php" class="nav-link">
                                <i class="fas fa-tachometer-alt"></i>
                                <span>Admin</span>
                            </a>
                        <?php endif; ?>
                        <a href="/electrastore/orders/history.php" class="nav-link">
                            <i class="fas fa-box"></i>
                            <span>Orders</span>
                        </a>
                        <a href="/electrastore/cart/index.php" class="nav-link cart-link">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Cart</span>
                            <?php if ($cart_count > 0): ?>
                                <span class="cart-badge"><?php echo $cart_count; ?></span>
                            <?php endif; ?>
                        </a>
                        <div class="user-menu">
                            <button class="user-menu-btn">
                                <i class="fas fa-user-circle"></i>
                                <span><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="user-dropdown">
                                <a href="/electrastore/user/profile.php">
                                    <i class="fas fa-user"></i> Profile
                                </a>
                                <a href="/electrastore/user/logout.php">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="/electrastore/user/login.php" class="nav-link btn-login">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>Login</span>
                        </a>
                        <a href="/electrastore/user/register.php" class="nav-link btn-register">
                            <i class="fas fa-user-plus"></i>
                            <span>Register</span>
                        </a>
                    <?php endif; ?>
                </div>
                
                <button class="mobile-menu-btn" id="mobileMenuBtn">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </nav>
    </header>
    
    <main>