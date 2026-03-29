<?php
require_once '../includes/header.php';

if (!isLoggedIn()) {
    header('Location: /app/electrastore/user/login.php');
    exit();
}

if (!isAdmin() && $_SESSION['role'] != 'staff') {
    header('Location: /app/electrastore/index.php');
    exit();
}

$conn = getConnection();

// Get all customers (users with role 'user')
$stmt = $conn->prepare("SELECT * FROM users WHERE role = 'user' ORDER BY full_name");
$stmt->execute();
$customers = $stmt->fetchAll();

// Get total customers count
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
$stmt->execute();
$result = $stmt->fetch();
$total_customers = isset($result['total']) ? $result['total'] : 0;

// Get active buyers (customers who ordered in last 30 days)
$stmt = $conn->prepare("SELECT COUNT(DISTINCT user_id) as total FROM orders WHERE order_date > DATE_SUB(NOW(), INTERVAL 30 DAY)");
$stmt->execute();
$result = $stmt->fetch();
$active_buyers = isset($result['total']) ? $result['total'] : 0;
?>

<div class="container">
    <h2>Customer Management</h2>
    
    <div class="staff-menu" style="margin-bottom: 2rem;">
        <a href="/app/electrastore/staff/index.php" style="display: inline-block; padding: 0.5rem 1rem; background: #34495e; color: white; text-decoration: none; border-radius: 5px; margin-right: 0.5rem;">Dashboard</a>
        <a href="/app/electrastore/staff/orders.php" style="display: inline-block; padding: 0.5rem 1rem; background: #34495e; color: white; text-decoration: none; border-radius: 5px; margin-right: 0.5rem;">Manage Orders</a>
        <a href="/app/electrastore/staff/products.php" style="display: inline-block; padding: 0.5rem 1rem; background: #34495e; color: white; text-decoration: none; border-radius: 5px; margin-right: 0.5rem;">View Products</a>
        <a href="/app/electrastore/staff/customers.php" style="display: inline-block; padding: 0.5rem 1rem; background: #3498db; color: white; text-decoration: none; border-radius: 5px;">View Customers</a>
    </div>
    
    <!-- Stats Cards -->
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 2rem;">
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.5rem; border-radius: 10px;">
            <h3><?php echo $total_customers; ?></h3>
            <p>Total Registered Customers</p>
        </div>
        <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 1.5rem; border-radius: 10px;">
            <h3><?php echo $active_buyers; ?></h3>
            <p>Active Buyers (Last 30 Days)</p>
        </div>
    </div>
    
    <h3>All Customers</h3>
    
    <?php if (count($customers) == 0): ?>
        <p>No customers registered yet.</p>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Registered Date</th>
                    <th>Last Login</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $customer): ?>
                <tr>
                    <td><?php echo $customer['user_id']; ?></td>
                    <td><?php echo htmlspecialchars($customer['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($customer['email']); ?></td>
                    <td><?php echo htmlspecialchars($customer['phone'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars(substr($customer['address'] ?? '', 0, 30)) . (strlen($customer['address'] ?? '') > 30 ? '...' : ''); ?></td>
                    <td><?php echo date('M j, Y', strtotime($customer['created_at'])); ?></td>
                    <td><?php echo $customer['last_login'] ? date('M j, Y', strtotime($customer['last_login'])) : 'Never'; ?></td>
                    <td>
                        <span style="color: <?php echo ($customer['status'] ?? 'active') == 'active' ? 'green' : 'red'; ?>;">
                            <?php echo ucfirst($customer['status'] ?? 'active'); ?>
                        </span>
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