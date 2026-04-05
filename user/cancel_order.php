<?php
// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /app/electrastore/user/login.php');
    exit();
}

require_once '../config/database.php';

$conn = getConnection();

// Check if order ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['cancel_message'] = "Invalid order ID.";
    $_SESSION['cancel_message_type'] = 'error';
    header('Location: /app/electrastore/orders/history.php');
    exit();
}

$order_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Verify the order belongs to this user and get its status
$stmt = $conn->prepare("SELECT order_status, total_amount FROM orders WHERE order_id = ? AND user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    $_SESSION['cancel_message'] = "Order not found.";
    $_SESSION['cancel_message_type'] = 'error';
    header('Location: /app/electrastore/orders/history.php');
    exit();
}

// Check if order can be cancelled (only Pending or Processing)
if ($order['order_status'] == 'Completed') {
    $_SESSION['cancel_message'] = "Cannot cancel a completed order.";
    $_SESSION['cancel_message_type'] = 'error';
    header('Location: /app/electrastore/orders/history.php');
    exit();
}

if ($order['order_status'] == 'Cancelled') {
    $_SESSION['cancel_message'] = "This order has already been cancelled.";
    $_SESSION['cancel_message_type'] = 'error';
    header('Location: /app/electrastore/orders/history.php');
    exit();
}

// Proceed with cancellation
try {
    $conn->beginTransaction();
    
    // Get order items to restore stock
    $stmt = $conn->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll();
    
    // Restore product stock
    foreach ($order_items as $item) {
        $stmt = $conn->prepare("UPDATE products SET stock = stock + ? WHERE product_id = ?");
        $stmt->execute([$item['quantity'], $item['product_id']]);
    }
    
    // Update order status to Cancelled
    $stmt = $conn->prepare("UPDATE orders SET order_status = 'Cancelled' WHERE order_id = ?");
    $stmt->execute([$order_id]);
    
    $conn->commit();
    
    $_SESSION['cancel_message'] = "Order #$order_id has been cancelled successfully.";
    $_SESSION['cancel_message_type'] = 'success';
    
} catch (Exception $e) {
    $conn->rollBack();
    $_SESSION['cancel_message'] = "Failed to cancel order: " . $e->getMessage();
    $_SESSION['cancel_message_type'] = 'error';
}

header('Location: /app/electrastore/orders/history.php');
exit();
?>