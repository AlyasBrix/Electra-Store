<?php
require_once '../includes/header.php';

if (!isLoggedIn()) {
    header('Location: /electrastore/user/login.php');
    exit();
}

$conn = getConnection();
$cart_id = getUserCartId($_SESSION['user_id']);

// Get cart items
$stmt = $conn->prepare("SELECT ci.*, p.product_name, p.price, p.image_url 
                        FROM cart_items ci 
                        JOIN products p ON ci.product_id = p.product_id 
                        WHERE ci.cart_id = ?");
$stmt->execute([$cart_id]);
$cart_items = $stmt->fetchAll();

// Calculate total
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>

<div class="cart-container">
    <h2>Shopping Cart</h2>
    
    <?php if (count($cart_items) == 0): ?>
        <p>Your cart is empty.</p>
        <a href="/electrastore/products/index.php" class="btn">Continue Shopping</a>
    <?php else: ?>
        <table class="cart-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cart_items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                    <td>₱<?php echo number_format($item['price'], 2); ?></td>
                    <td>
                        <input type="number" 
                               class="cart-item-quantity" 
                               value="<?php echo $item['quantity']; ?>" 
                               min="1"
                               onchange="updateQuantity(<?php echo $item['cart_item_id']; ?>, this.value)">
                    </td>
                    <td>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                    <td>
                        <a href="#" onclick="removeItem(<?php echo $item['cart_item_id']; ?>)" 
                           class="remove-item">Remove</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="cart-total">
            <h3>Total: ₱<?php echo number_format($total, 2); ?></h3>
        </div>
        
        <div style="display: flex; gap: 1rem; justify-content: flex-end;">
            <a href="/electrastore/products/index.php" class="btn">Continue Shopping</a>
            <a href="/electrastore/checkout/index.php" class="btn btn-primary">Proceed to Checkout</a>
        </div>
    <?php endif; ?>
</div>

<?php
require_once '../includes/footer.php';
?>