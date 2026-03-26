<?php
require_once '../includes/header.php';

if (!isLoggedIn()) {
    header('Location: /electrastore/user/login.php');
    exit();
}

$conn = getConnection();

$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();
?>

<div class="container">
    <h2>My Orders</h2>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="success-message">Order placed successfully!</div>
    <?php endif; ?>
    
    <?php if (count($orders) == 0): ?>
        <p>You haven't placed any orders yet.</p>
        <a href="/electrastore/products/index.php" class="btn">Start Shopping</a>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
        <div style="background: white; border-radius: 10px; padding: 1.5rem; margin-bottom: 1.5rem;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                <div>
                    <strong>Order #<?php echo $order['order_id']; ?></strong><br>
                    <small>Date: <?php echo date('F j, Y', strtotime($order['order_date'])); ?></small>
                </div>
                <div>
                    <span style="background: <?php 
                        echo $order['order_status'] == 'Pending' ? '#f39c12' : 
                            ($order['order_status'] == 'Processing' ? '#3498db' : '#27ae60'); 
                    ?>; color: white; padding: 0.2rem 0.8rem; border-radius: 5px;">
                        <?php echo $order['order_status']; ?>
                    </span>
                </div>
            </div>
            
            <?php
            $stmt = $conn->prepare("SELECT oi.*, p.product_name 
                                    FROM order_items oi 
                                    JOIN products p ON oi.product_id = p.product_id 
                                    WHERE oi.order_id = ?");
            $stmt->execute([$order['order_id']]);
            $items = $stmt->fetchAll();
            ?>
            
            <table style="width: 100%;">
                <thead>
                    <tr style="border-bottom: 1px solid #ddd;">
                        <th style="text-align: left;">Product</th>
                        <th style="text-align: center;">Quantity</th>
                        <th style="text-align: right;">Price</th>
                        <th style="text-align: right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td style="text-align: center;"><?php echo $item['quantity']; ?></td>
                        <td style="text-align: right;">₱<?php echo number_format($item['price'], 2); ?></td>
                        <td style="text-align: right;">₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="border-top: 2px solid #ddd;">
                        <td colspan="3" style="text-align: right; font-weight: bold;">Total:</td>
                        <td style="text-align: right; font-weight: bold;">₱<?php echo number_format($order['total_amount'], 2); ?></td>
                    </tr>
                </tfoot>
            </table>
            
            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #eee;">
                <small>Shipping to: <?php echo htmlspecialchars($order['shipping_address']); ?></small><br>
                <small>Payment: <?php echo $order['payment_method']; ?></small>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
require_once '../includes/footer.php';
?>