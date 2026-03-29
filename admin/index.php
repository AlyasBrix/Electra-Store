<?php
require_once '../includes/header.php';
requireAdmin();

$conn = getConnection();

// Get counts with isset checks
$stmt = $conn->query("SELECT COUNT(*) as total FROM products");
$result = $stmt->fetch();
$product_count = isset($result['total']) ? $result['total'] : 0;

$stmt = $conn->query("SELECT COUNT(*) as total FROM orders");
$result = $stmt->fetch();
$order_count = isset($result['total']) ? $result['total'] : 0;

$stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
$result = $stmt->fetch();
$user_count = isset($result['total']) ? $result['total'] : 0;

$stmt = $conn->query("SELECT SUM(total_amount) as total FROM orders");
$result = $stmt->fetch();
$revenue = isset($result['total']) ? $result['total'] : 0;

// Get recent orders
$stmt = $conn->query("SELECT o.*, u.full_name FROM orders o 
                      JOIN users u ON o.user_id = u.user_id 
                      ORDER BY o.order_date DESC LIMIT 5");
$recent_orders = $stmt->fetchAll();
?>

<div class="container">
    <h2>Admin Dashboard</h2>
    
    <div class="admin-menu">
        <a href="/app/electrastore/admin/index.php">Dashboard</a>
        <a href="/app/electrastore/admin/products.php">Manage Products</a>
        <a href="/app/electrastore/admin/categories.php">Manage Categories</a>
        <a href="/app/electrastore/admin/orders.php">Manage Orders</a>
        <a href="/app/electrastore/admin/users.php">Manage Users</a>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <div style="background: white; padding: 1rem; border-radius: 10px; text-align: center;">
            <h3><?php echo $product_count; ?></h3>
            <p>Products</p>
        </div>
        <div style="background: white; padding: 1rem; border-radius: 10px; text-align: center;">
            <h3><?php echo $order_count; ?></h3>
            <p>Orders</p>
        </div>
        <div style="background: white; padding: 1rem; border-radius: 10px; text-align: center;">
            <h3><?php echo $user_count; ?></h3>
            <p>Customers</p>
        </div>
        <div style="background: white; padding: 1rem; border-radius: 10px; text-align: center;">
            <h3>₱<?php echo number_format($revenue, 2); ?></h3>
            <p>Revenue</p>
        </div>
    </div>
    
    <h3>Recent Orders</h3>
    <?php if (count($recent_orders) == 0): ?>
        <p>No orders found.</p>
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
                <?php foreach ($recent_orders as $order): ?>
                <tr>
                    <td>#<?php echo $order['order_id']; ?></td>
                    <td><?php echo htmlspecialchars($order['full_name']); ?></td>
                    <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                    <td>
                        <span style="background: <?php 
                            echo $order['order_status'] == 'Pending' ? '#f39c12' : 
                                ($order['order_status'] == 'Processing' ? '#3498db' : '#27ae60'); 
                        ?>; color: white; padding: 0.2rem 0.5rem; border-radius: 3px;">
                            <?php echo $order['order_status']; ?>
                        </span>
                    </td>
                    <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                    <td>
                        <a href="orders.php?view=<?php echo $order['order_id']; ?>" class="edit-btn">View</a>
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