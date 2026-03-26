<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$cart_item_id = isset($_POST['cart_item_id']) ? (int)$_POST['cart_item_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

if ($cart_item_id <= 0 || $quantity < 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

$conn = getConnection();

// Get product stock
$stmt = $conn->prepare("SELECT ci.product_id, p.stock 
                        FROM cart_items ci 
                        JOIN products p ON ci.product_id = p.product_id 
                        WHERE ci.cart_item_id = ?");
$stmt->execute([$cart_item_id]);
$item = $stmt->fetch();

if (!$item) {
    echo json_encode(['success' => false, 'message' => 'Item not found']);
    exit();
}

if ($quantity > $item['stock']) {
    echo json_encode(['success' => false, 'message' => 'Not enough stock']);
    exit();
}

if ($quantity <= 0) {
    // Remove item
    $stmt = $conn->prepare("DELETE FROM cart_items WHERE cart_item_id = ?");
    $stmt->execute([$cart_item_id]);
} else {
    // Update quantity
    $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?");
    $stmt->execute([$quantity, $cart_item_id]);
}

echo json_encode(['success' => true]);
?>