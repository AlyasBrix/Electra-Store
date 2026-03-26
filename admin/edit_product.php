<?php
require_once '../includes/header.php';
requireAdmin();

$conn = getConnection();

// Update order status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['order_status'];
    
    $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
    $stmt->execute([$status, $order_id]);
}

// Get all orders
$stmt = $conn->prepare("SELECT o.*, u.full_name, u.email, u.phone 
                        FROM orders o 
                        JOIN users u ON o.user_id = u.user_id 
                        ORDER BY o.order_date DESC");
$stmt->execute();
$orders = $stmt->fetchAll();

// View single order
$view_order = null;
if (isset($_GET['view'])) {
    $order_id = (int)$_GET['view'];
    $stmt = $conn->prepare("SELECT o.*, u.full_name, u.email, u.phone 
                            FROM orders o 
                            JOIN users u ON o.user_id = u.user_id 
                            WHERE o.order_id = ?");
    $stmt->execute([$order_id]);
    $view_order = $stmt->fetch();
    
    if ($view_order) {
        $stmt = $conn->prepare("SELECT oi.*, p.product_name 
                                FROM order_items oi 
                                JOIN products p ON oi.product_id = p.product_id 
                                WHERE oi.order_id = ?");
        $stmt->execute([$order_id]);
        $order_items = $stmt->fetchAll();
    }
}
?>

<div class="container">
    <h2>Manage Orders</h2>
    
    <div class="admin-menu">
        <a href="/electrastore/admin/index.php">Dashboard</a>
        <a href="/electrastore/admin/products.php">Manage Products</a>
        <a href="/electrastore/admin/orders.php">Manage Orders</a>
    </div>
    
    <?php if ($view_order): ?>
        <div style="margin-bottom: 1rem;">
            <a href="orders.php" class="btn">← Back to Orders</a>
        </div>
        
        <div style="background: white; border-radius: 10px; padding: 1.5rem;">
            <h3>Order #<?php echo $view_order['order_id']; ?></h3>
            
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1.5rem;">
                <div>
                    <strong>Customer:</strong> <?php echo htmlspecialchars($view_order['full_name']); ?><br>
                    <strong>Email:</strong> <?php echo htmlspecialchars($view_order['email']); ?><br>
                    <strong>Phone:</strong> <?php echo htmlspecialchars($view_order['phone']); ?>
                </div>
                <div>
                    <strong>Order Date:</strong> <?php echo date('F j, Y g:i A', strtotime($view_order['order_date'])); ?><br>
                    <strong>Shipping Address:</strong> <?php echo htmlspecialchars($view_order['shipping_address']); ?><br>
                    <strong>Payment Method:</strong> <?php echo $view_order['payment_method']; ?>
                </div>
            </div>
            
            <form method="POST" action="" style="margin-bottom: 1.5rem;">
                <input type="hidden" name="order_id" value="<?php echo $view_order['order_id']; ?>">
                <label><strong>Update Status:</strong></label>
                <select name="order_status" style="margin: 0 1rem;">
                    <option value="Pending" <?php echo $view_order['order_status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="Processing" <?php echo $view_order['order_status'] == 'Processing' ? 'selected' : ''; ?>>Processing</option>
                    <option value="Completed" <?php echo $view_order['order_status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="Cancelled" <?php echo $view_order['order_status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
                <button type="submit" name="update_status" class="btn">Update</button>
            </form>
            
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td>₱<?php echo number_format($item['price'], 2); ?></td>
                        <td>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="border-top: 2px solid #ddd;">
                        <td colspan="3" style="text-align: right; font-weight: bold;">Total:</td>
                        <td style="font-weight: bold;">₱<?php echo number_format($view_order['total_amount'], 2); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td>#<?php echo $order['order_id']; ?></td>
                    <td><?php echo htmlspecialchars($order['full_name']); ?></td>
                    <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                    <td>
                        <span style="background: <?php 
                            echo $order['order_status'] == 'Pending' ? '#f39c12' : 
                                ($order['order_status'] == 'Processing' ? '#3498db' : 
                                ($order['order_status'] == 'Completed' ? '#27ae60' : '#e74c3c')); 
                        ?>; color: white; padding: 0.2rem 0.5rem; border-radius: 3px;">
                            <?php echo $order['order_status']; ?>
                        </span>
                    </td>
                    <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                    <td>
                        <a href="?view=<?php echo $order['order_id']; ?>" class="edit-btn">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php
require_once '../includes/footer.php';
?>