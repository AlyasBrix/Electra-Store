<?php
require_once '../includes/header.php';

// Check if user is logged in and is staff or admin
if (!isLoggedIn()) {
    header('Location: /app/electrastore/user/login.php');
    exit();
}

if (!isAdmin() && $_SESSION['role'] != 'staff') {
    header('Location: /app/electrastore/index.php');
    exit();
}

$conn = getConnection();

// Get stats for staff dashboard
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM orders");
$stmt->execute();
$total_orders = $stmt->fetch()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE order_status = 'Pending'");
$stmt->execute();
$pending_orders = $stmt->fetch()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM products WHERE stock < 10");
$stmt->execute();
$low_stock = $stmt->fetch()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
$stmt->execute();
$total_customers = $stmt->fetch()['total'];

// Get recent orders
$stmt = $conn->prepare("SELECT o.*, u.full_name FROM orders o 
                        JOIN users u ON o.user_id = u.user_id 
                        ORDER BY o.order_date DESC LIMIT 10");
$stmt->execute();
$recent_orders = $stmt->fetchAll();

// Get low stock products
$stmt = $conn->prepare("SELECT * FROM products WHERE stock < 10 ORDER BY stock ASC LIMIT 5");
$stmt->execute();
$low_stock_products = $stmt->fetchAll();
?>

<div class="container">
    <h2>Staff Dashboard</h2>
    
    <div class="staff-menu" style="margin-bottom: 2rem;">
        <a href="/app/electrastore/staff/index.php" style="display: inline-block; padding: 0.5rem 1rem; background: #3498db; color: white; text-decoration: none; border-radius: 5px; margin-right: 0.5rem;">Dashboard</a>
        <a href="/app/electrastore/staff/orders.php" style="display: inline-block; padding: 0.5rem 1rem; background: #34495e; color: white; text-decoration: none; border-radius: 5px; margin-right: 0.5rem;">Manage Orders</a>
        <a href="/app/electrastore/staff/products.php" style="display: inline-block; padding: 0.5rem 1rem; background: #34495e; color: white; text-decoration: none; border-radius: 5px; margin-right: 0.5rem;">View Products</a>
        <a href="/app/electrastore/staff/customers.php" style="display: inline-block; padding: 0.5rem 1rem; background: #34495e; color: white; text-decoration: none; border-radius: 5px;">View Customers</a>
    </div>
    
    <!-- Stats Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.5rem; border-radius: 10px;">
            <h3><?php echo $total_orders; ?></h3>
            <p>Total Orders</p>
        </div>
        <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 1.5rem; border-radius: 10px;">
            <h3><?php echo $pending_orders; ?></h3>
            <p>Pending Orders</p>
        </div>
        <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 1.5rem; border-radius: 10px;">
            <h3><?php echo $low_stock; ?></h3>
            <p>Low Stock Items</p>
        </div>
        <div style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; padding: 1.5rem; border-radius: 10px;">
            <h3><?php echo $total_customers; ?></h3>
            <p>Total Customers</p>
        </div>
    </div>
    
    <!-- Recent Orders -->
    <div style="background: white; border-radius: 10px; padding: 1.5rem; margin-bottom: 2rem;">
        <h3>Recent Orders</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid #ddd;">
                    <th style="padding: 0.5rem; text-align: left;">Order ID</th>
                    <th style="padding: 0.5rem; text-align: left;">Customer</th>
                    <th style="padding: 0.5rem; text-align: left;">Total</th>
                    <th style="padding: 0.5rem; text-align: left;">Status</th>
                    <th style="padding: 0.5rem; text-align: left;">Date</th>
                    <th style="padding: 0.5rem; text-align: left;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_orders as $order): ?>
                <tr>
                    <td style="padding: 0.5rem;">#<?php echo $order['order_id']; ?></td>
                    <td style="padding: 0.5rem;"><?php echo htmlspecialchars($order['full_name']); ?></td>
                    <td style="padding: 0.5rem;">₱<?php echo number_format($order['total_amount'], 2); ?></td>
                    <td style="padding: 0.5rem;">
                        <span style="background: <?php 
                            echo $order['order_status'] == 'Pending' ? '#f39c12' : 
                                ($order['order_status'] == 'Processing' ? '#3498db' : 
                                ($order['order_status'] == 'Completed' ? '#27ae60' : '#e74c3c')); 
                        ?>; color: white; padding: 0.2rem 0.5rem; border-radius: 3px;">
                            <?php echo $order['order_status']; ?>
                        </span>
                    </td>
                    <td style="padding: 0.5rem;"><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                    <td style="padding: 0.5rem;">
                        <a href="orders.php?view=<?php echo $order['order_id']; ?>" class="edit-btn">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Low Stock Alert -->
    <?php if (count($low_stock_products) > 0): ?>
    <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 10px; padding: 1.5rem;">
        <h3 style="color: #856404;">⚠️ Low Stock Alert</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid #ffc107;">
                    <th style="padding: 0.5rem; text-align: left;">Product</th>
                    <th style="padding: 0.5rem; text-align: left;">Current Stock</th>
                    <th style="padding: 0.5rem; text-align: left;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($low_stock_products as $product): ?>
                <tr>
                    <td style="padding: 0.5rem;"><?php echo htmlspecialchars($product['product_name']); ?></td>
                    <td style="padding: 0.5rem; color: #e74c3c; font-weight: bold;"><?php echo $product['stock']; ?> units</td>
                    <td style="padding: 0.5rem;">
                        <a href="/app/electrastore/staff/products.php?edit=<?php echo $product['product_id']; ?>" class="edit-btn">Update Stock</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php
require_once '../includes/footer.php';
?>