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
    <style>
        /* Add spacing above and below the navbar */
        body {
            padding-top: 0;
            margin-top: 0;
        }
        
        .navbar {
            padding-top: 0.75rem !important;
            padding-bottom: 0.75rem !important;
        }
        
        .nav-container {
            padding-top: 0.5rem !important;
            padding-bottom: 0.5rem !important;
        }
        
        /* Adjust the container padding for better spacing */
        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1rem 2rem !important;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 2rem;
        }
        
        /* Make the logo area have proper spacing */
        .logo a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
        }
        
        /* Adjust search bar height */
        .search-bar input {
            padding: 0.85rem 1rem !important;
        }
        
        /* Adjust nav items spacing */
        .nav-item {
            padding: 0.65rem 1rem !important;
        }
        
        /* User menu button spacing */
        .user-menu-btn {
            padding: 0.65rem 1rem !important;
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="nav-container">
                <!-- Logo Section -->
                <div class="logo">
                    <a href="/app/electrastore/index.php">
                        <div class="logo-icon">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <div class="logo-text">
                            <span class="logo-main">ElectraStore</span>
                            <span class="logo-tagline">Premium Electronics</span>
                        </div>
                    </a>
                </div>
                
                <!-- Search Bar Section -->
                <div class="search-bar">
                    <form action="/app/electrastore/products/search.php" method="GET">
                        <input type="text" name="q" placeholder="Search products...">
                        <button type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                
                <!-- Navigation Links Section -->
                <div class="nav-links">
                    <!-- Main Navigation -->
                    <a href="/app/electrastore/index.php" class="nav-item">
                        <i class="fas fa-home"></i>
                        <span>Home</span>
                    </a>
                    <a href="/app/electrastore/products/index.php" class="nav-item">
                        <i class="fas fa-store"></i>
                        <span>Products</span>
                    </a>
                    
                    <!-- Logged In User Navigation -->
                    <?php if (isLoggedIn()): ?>
                        <!-- Admin/Staff Dashboard -->
                        <?php if (isAdmin()): ?>
                            <a href="/app/electrastore/admin/index.php" class="nav-item admin-link">
                                <i class="fas fa-tachometer-alt"></i>
                                <span>Admin</span>
                            </a>
                        <?php elseif ($_SESSION['role'] == 'staff'): ?>
                            <a href="/app/electrastore/staff/index.php" class="nav-item staff-link">
                                <i class="fas fa-clipboard-list"></i>
                                <span>Staff</span>
                            </a>
                        <?php endif; ?>
                        
                        <!-- User Navigation -->
                        <a href="/app/electrastore/orders/history.php" class="nav-item">
                            <i class="fas fa-box"></i>
                            <span>Orders</span>
                        </a>
                        <a href="/app/electrastore/cart/index.php" class="nav-item cart-link">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Cart</span>
                            <?php if ($cart_count > 0): ?>
                                <span class="cart-badge"><?php echo $cart_count; ?></span>
                            <?php endif; ?>
                        </a>
                        
                        <!-- User Dropdown Menu -->
                        <div class="user-menu">
                            <button class="user-menu-btn">
                                <i class="fas fa-user-circle"></i>
                                <span><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="user-dropdown">
                                <a href="/app/electrastore/user/profile.php">
                                    <i class="fas fa-user"></i> My Profile
                                </a>
                                <a href="/app/electrastore/orders/history.php">
                                    <i class="fas fa-shopping-bag"></i> My Orders
                                </a>
                                <div class="dropdown-divider"></div>
                                <a href="/app/electrastore/user/logout.php" class="logout-dropdown">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </div>
                        </div>
                    
                    <!-- Guest Navigation -->
                    <?php else: ?>
                        <a href="/app/electrastore/user/login.php" class="nav-item btn-login">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>Login</span>
                        </a>
                        <a href="/app/electrastore/user/register.php" class="nav-item btn-register">
                            <i class="fas fa-user-plus"></i>
                            <span>Register</span>
                        </a>
                    <?php endif; ?>
                </div>
                
                <!-- Mobile Menu Button -->
                <button class="mobile-menu-btn" id="mobileMenuBtn">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </nav>
    </header>
    
    <main>