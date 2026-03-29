<?php
// Authentication helper functions
// Note: isLoggedIn() and database functions are in config/database.php

// Check if current user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Check if current user is staff
function isStaff() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'staff';
}

// Check if current user is customer
function isCustomer() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'user';
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /app/electrastore/user/login.php');
        exit();
    }
}

// Require admin access
function requireAdmin() {
    if (!isLoggedIn() || !isAdmin()) {
        header('Location: /app/electrastore/user/login.php');
        exit();
    }
}

// Get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Get current user name
function getCurrentUserName() {
    return $_SESSION['full_name'] ?? 'Guest';
}
?>