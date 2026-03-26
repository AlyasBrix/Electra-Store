<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false]);
    exit();
}

$cart_item_id = isset($_POST['cart_item_id']) ? (int)$_POST['cart_item_id'] : 0;

if ($cart_item_id > 0) {
    $conn = getConnection();
    $stmt = $conn->prepare("DELETE FROM cart_items WHERE cart_item_id = ?");
    $stmt->execute([$cart_item_id]);
}

echo json_encode(['success' => true]);
?>