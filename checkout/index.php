<?php
require_once '../includes/header.php';

if (!isLoggedIn()) {
    header('Location: /app/electrastore/user/login.php');
    exit();
}

$conn = getConnection();
$cart_id = getUserCartId($_SESSION['user_id']);

// Get cart items
$stmt = $conn->prepare("SELECT ci.*, p.product_name, p.price 
                        FROM cart_items ci 
                        JOIN products p ON ci.product_id = p.product_id 
                        WHERE ci.cart_id = ?");
$stmt->execute([$cart_id]);
$cart_items = $stmt->fetchAll();

if (count($cart_items) == 0) {
    header('Location: /app/electrastore/cart/index.php');
    exit();
}

// Calculate total
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipping_address = trim($_POST['shipping_address'] ?? '');
    $payment_method = $_POST['payment_method'] ?? 'Cash on Delivery';
    
    if (empty($shipping_address)) {
        $error = 'Please enter shipping address';
    } else {
        $conn->beginTransaction();
        
        try {
            // Create order
            $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, shipping_address, payment_method) 
                                    VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $total, $shipping_address, $payment_method]);
            $order_id = $conn->lastInsertId();
            
            // Add order items and update stock
            foreach ($cart_items as $item) {
                // Add order item
                $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) 
                                        VALUES (?, ?, ?, ?)");
                $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
                
                // Update stock
                $stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE product_id = ?");
                $stmt->execute([$item['quantity'], $item['product_id']]);
            }
            
            // Clear cart
            $stmt = $conn->prepare("DELETE FROM cart_items WHERE cart_id = ?");
            $stmt->execute([$cart_id]);
            
            $conn->commit();
            $success = true;
            
        } catch (Exception $e) {
            $conn->rollBack();
            $error = 'Order processing failed: ' . $e->getMessage();
        }
    }
    
    if ($success) {
        header('Location: /app/electrastore/orders/history.php?success=1');
        exit();
    }
}
?>

<div class="form-container" style="max-width: 800px;">
    <h2>Checkout</h2>
    
    <?php if ($error): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <h3>Order Summary</h3>
    <table style="width: 100%; margin-bottom: 2rem;">
        <thead>
            <tr style="border-bottom: 1px solid #ddd;">
                <th style="text-align: left;">Product</th>
                <th style="text-align: center;">Quantity</th>
                <th style="text-align: right;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cart_items as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                <td style="text-align: center;"><?php echo $item['quantity']; ?></td>
                <td style="text-align: right;">₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
            <tr style="border-top: 2px solid #ddd;">
                <td colspan="2" style="text-align: right; font-weight: bold;">Total:</td>
                <td style="text-align: right; font-weight: bold;">₱<?php echo number_format($total, 2); ?></td>
            </tr>
        </tbody>
    </table>
    
    <form method="POST" action="">
        <div class="form-group">
            <label>Shipping Address *</label>
            <textarea name="shipping_address" required 
                      placeholder="Enter your complete shipping address"><?php echo htmlspecialchars($_SESSION['address'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-group">
            <label>Payment Method</label>
            <select name="payment_method">
                <option value="Cash on Delivery">Cash on Delivery</option>
            </select>
            <small>Note: Online payment integration coming soon</small>
        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%;">Place Order</button>
    </form>
</div>

<?php
require_once '../includes/footer.php';
?>