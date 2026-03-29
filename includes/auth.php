<?php
require_once __DIR__ . '/../config/database.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /app/electrastore/user/login.php');
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /app/electrastore/index.php');
        exit();
    }
}

function getUserCartId($user_id) {
    $conn = getConnection();
    
    // Check if user has a cart
    $stmt = $conn->prepare("SELECT cart_id FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $cart = $stmt->fetch();
    
    if ($cart) {
        return $cart['cart_id'];
    }
    
    // Create new cart
    $stmt = $conn->prepare("INSERT INTO cart (user_id) VALUES (?)");
    $stmt->execute([$user_id]);
    return $conn->lastInsertId();
}

function getCartItemCount($user_id) {
    $cart_id = getUserCartId($user_id);
    $conn = getConnection();
    
    $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart_items WHERE cart_id = ?");
    $stmt->execute([$cart_id]);
    $result = $stmt->fetch();
    return $result['total'] ?? 0;
}
?>