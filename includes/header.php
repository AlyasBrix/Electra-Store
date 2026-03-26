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
    <title>ElectraStore - Electronic Gadgets & Appliances</title>
    <link rel="stylesheet" href="/electrastore/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="container">
                <div class="logo">
                    <a href="/electrastore/index.php">
                        <h1>ElectraStore</h1>
                    </a>
                </div>
                <div class="nav-links">
                    <a href="/electrastore/index.php">Home</a>
                    <a href="/electrastore/products/index.php">Products</a>
                    
                    <?php if (isLoggedIn()): ?>
                        <?php if (isAdmin()): ?>
                            <a href="/electrastore/admin/index.php">Admin Dashboard</a>
                        <?php endif; ?>
                        <a href="/electrastore/orders/history.php">My Orders</a>
                        <a href="/electrastore/cart/index.php" class="cart-link">
                            Cart 
                            <?php if ($cart_count > 0): ?>
                                <span class="cart-badge"><?php echo $cart_count; ?></span>
                            <?php endif; ?>
                        </a>
                        <span class="user-greeting">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                        <a href="/electrastore/user/logout.php" class="logout-btn">Logout</a>
                    <?php else: ?>
                        <a href="/electrastore/user/login.php">Login</a>
                        <a href="/electrastore/user/register.php">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </header>
    <main>