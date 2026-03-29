<?php
// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /app/electrastore/user/login.php');
    exit();
}

require_once '../config/database.php';

// Try to include mail config, but don't fail if it's not there
$mail_available = false;
if (file_exists('../includes/mail_config.php')) {
    require_once '../includes/mail_config.php';
    $mail_available = true;
}

$conn = getConnection();

// Update order status with email notification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['order_status'];
    
    // Get old status before update
    $old_status_stmt = $conn->prepare("SELECT order_status FROM orders WHERE order_id = ?");
    $old_status_stmt->execute([$order_id]);
    $old_status = $old_status_stmt->fetchColumn();
    
    $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE order_id = ?");
    $stmt->execute([$status, $order_id]);
    
    // Send email notification if status changed to Completed
    if ($status == 'Completed' && $old_status != 'Completed') {
        if ($mail_available && function_exists('sendOrderEmail')) {
            // Get user details
            $user_stmt = $conn->prepare("SELECT o.*, u.email, u.full_name 
                                         FROM orders o 
                                         JOIN users u ON o.user_id = u.user_id 
                                         WHERE o.order_id = ?");
            $user_stmt->execute([$order_id]);
            $order_data = $user_stmt->fetch();
            
            // Get order items
            $items_stmt = $conn->prepare("SELECT oi.*, p.product_name, p.image_url 
                                          FROM order_items oi 
                                          JOIN products p ON oi.product_id = p.product_id 
                                          WHERE oi.order_id = ?");
            $items_stmt->execute([$order_id]);
            $order_items = $items_stmt->fetchAll();
            
            // Send email using PHPMailer
            try {
                if (sendOrderEmail($order_data['email'], $order_data['full_name'], $order_id, $order_data['total_amount'], $order_items)) {
                    $_SESSION['message'] = "Order status updated to Completed! Email notification sent to customer.";
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = "Order status updated to Completed, but email notification failed to send. Please check mail configuration.";
                    $_SESSION['message_type'] = 'warning';
                }
            } catch (Exception $e) {
                $_SESSION['message'] = "Order status updated to Completed, but email failed: " . $e->getMessage();
                $_SESSION['message_type'] = 'warning';
            }
        } else {
            $_SESSION['message'] = "Order status updated to Completed! (Email notification not configured)";
            $_SESSION['message_type'] = 'success';
        }
    } else {
        $_SESSION['message'] = "Order status updated successfully!";
        $_SESSION['message_type'] = 'success';
    }
    
    header('Location: orders.php?view=' . $order_id);
    exit();
}

// Get all orders with counts
$stmt = $conn->prepare("SELECT o.*, u.full_name, u.email, u.phone 
                        FROM orders o 
                        JOIN users u ON o.user_id = u.user_id 
                        ORDER BY o.order_date DESC");
$stmt->execute();
$orders = $stmt->fetchAll();

// Get order statistics
$stmt = $conn->prepare("SELECT 
    SUM(CASE WHEN order_status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN order_status = 'Processing' THEN 1 ELSE 0 END) as processing,
    SUM(CASE WHEN order_status = 'Completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN order_status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled,
    COUNT(*) as total
    FROM orders");
$stmt->execute();
$stats = $stmt->fetch();

// View single order
$view_order = null;
$order_items = [];
if (isset($_GET['view'])) {
    $order_id = (int)$_GET['view'];
    $stmt = $conn->prepare("SELECT o.*, u.full_name, u.email, u.phone 
                            FROM orders o 
                            JOIN users u ON o.user_id = u.user_id 
                            WHERE o.order_id = ?");
    $stmt->execute([$order_id]);
    $view_order = $stmt->fetch();
    
    if ($view_order) {
        $stmt = $conn->prepare("SELECT oi.*, p.product_name, p.image_url 
                                FROM order_items oi 
                                JOIN products p ON oi.product_id = p.product_id 
                                WHERE oi.order_id = ?");
        $stmt->execute([$order_id]);
        $order_items = $stmt->fetchAll();
    }
}

// Now include header AFTER all redirect logic
require_once '../includes/header.php';
requireAdmin();
?>

<div class="orders-admin">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-left">
                <h1>Manage Orders</h1>
                <p>View and process customer orders</p>
            </div>
        </div>

        <!-- Admin Navigation -->
        <div class="admin-nav">
            <a href="/app/electrastore/admin/index.php" class="nav-link">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="/app/electrastore/admin/products.php" class="nav-link">
                <i class="fas fa-box"></i> Products
            </a>
            <a href="/app/electrastore/admin/categories.php" class="nav-link">
                <i class="fas fa-tags"></i> Categories
            </a>
            <a href="/app/electrastore/admin/orders.php" class="nav-link active">
                <i class="fas fa-shopping-cart"></i> Orders
            </a>
            <a href="/app/electrastore/admin/users.php" class="nav-link">
                <i class="fas fa-users"></i> Users
            </a>
        </div>

        <!-- Messages -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert-message <?php echo $_SESSION['message_type']; ?>">
                <i class="fas <?php echo $_SESSION['message_type'] == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <?php 
                echo htmlspecialchars($_SESSION['message']);
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>

        <?php if ($view_order): ?>
            <!-- Order Details View -->
            <div class="order-details">
                <div class="order-details-header">
                    <div class="back-link">
                        <a href="orders.php" class="btn-back">
                            <i class="fas fa-arrow-left"></i> Back to Orders
                        </a>
                    </div>
                    <div class="order-title">
                        <h2>Order #<?php echo str_pad($view_order['order_id'], 6, '0', STR_PAD_LEFT); ?></h2>
                        <span class="order-status-badge status-<?php echo strtolower($view_order['order_status']); ?>">
                            <i class="fas <?php 
                                echo $view_order['order_status'] == 'Pending' ? 'fa-clock' : 
                                    ($view_order['order_status'] == 'Processing' ? 'fa-spinner' : 
                                    ($view_order['order_status'] == 'Completed' ? 'fa-check-circle' : 'fa-times-circle')); 
                            ?>"></i>
                            <?php echo $view_order['order_status']; ?>
                        </span>
                    </div>
                </div>

                <div class="order-details-grid">
                    <!-- Customer Information -->
                    <div class="info-card">
                        <div class="info-card-header">
                            <i class="fas fa-user"></i>
                            <h3>Customer Information</h3>
                        </div>
                        <div class="info-card-body">
                            <div class="info-row">
                                <span class="info-label">Full Name:</span>
                                <span class="info-value"><?php echo htmlspecialchars($view_order['full_name']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Email:</span>
                                <span class="info-value"><?php echo htmlspecialchars($view_order['email']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Phone:</span>
                                <span class="info-value"><?php echo htmlspecialchars($view_order['phone']); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Order Information -->
                    <div class="info-card">
                        <div class="info-card-header">
                            <i class="fas fa-info-circle"></i>
                            <h3>Order Information</h3>
                        </div>
                        <div class="info-card-body">
                            <div class="info-row">
                                <span class="info-label">Order Date:</span>
                                <span class="info-value"><?php echo date('F j, Y g:i A', strtotime($view_order['order_date'])); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Payment Method:</span>
                                <span class="info-value"><?php echo strtoupper($view_order['payment_method']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Shipping Address:</span>
                                <span class="info-value"><?php echo htmlspecialchars($view_order['shipping_address']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Update Status Form -->
                <div class="status-update-card">
                    <div class="status-update-header">
                        <i class="fas fa-sync-alt"></i>
                        <h3>Update Order Status</h3>
                    </div>
                    <form method="POST" action="" class="status-form">
                        <input type="hidden" name="order_id" value="<?php echo $view_order['order_id']; ?>">
                        <div class="status-select-wrapper">
                            <select name="order_status" class="status-select">
                                <option value="Pending" <?php echo $view_order['order_status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="Processing" <?php echo $view_order['order_status'] == 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="Completed" <?php echo $view_order['order_status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="Cancelled" <?php echo $view_order['order_status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                            <button type="submit" name="update_status" class="btn-update-status">
                                <i class="fas fa-save"></i> Update Status
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Order Items Table -->
                <div class="order-items-card">
                    <div class="order-items-header">
                        <i class="fas fa-boxes"></i>
                        <h3>Order Items</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="order-items-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td class="product-cell">
                                        <div class="product-info-order">
                                            <div class="product-image-order">
                                                <img src="/app/electrastore/assets/images/products/<?php echo htmlspecialchars($item['image_url'] ?: 'placeholder.jpg'); ?>" 
                                                     alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                            </div>
                                            <span><?php echo htmlspecialchars($item['product_name']); ?></span>
                                        </div>
                                    </td>
                                    <td class="quantity-cell">x<?php echo $item['quantity']; ?></td>
                                    <td class="price-cell">₱<?php echo number_format($item['price'], 2); ?></td>
                                    <td class="subtotal-cell">₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="total-row">
                                    <td colspan="3" class="total-label">Total Amount</td>
                                    <td class="total-amount">₱<?php echo number_format($view_order['total_amount'], 2); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Orders List View -->
            <?php if (count($orders) == 0): ?>
                <div class="empty-state">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>No Orders Found</h3>
                    <p>There are no orders to display at this time.</p>
                </div>
            <?php else: ?>
                <!-- Order Statistics -->
                <div class="stats-orders-grid">
                    <div class="stat-card-order pending">
                        <div class="stat-icon-order">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-details-order">
                            <span class="stat-value"><?php echo $stats['pending'] ?? 0; ?></span>
                            <span class="stat-label">Pending</span>
                        </div>
                    </div>
                    <div class="stat-card-order processing">
                        <div class="stat-icon-order">
                            <i class="fas fa-spinner"></i>
                        </div>
                        <div class="stat-details-order">
                            <span class="stat-value"><?php echo $stats['processing'] ?? 0; ?></span>
                            <span class="stat-label">Processing</span>
                        </div>
                    </div>
                    <div class="stat-card-order completed">
                        <div class="stat-icon-order">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-details-order">
                            <span class="stat-value"><?php echo $stats['completed'] ?? 0; ?></span>
                            <span class="stat-label">Completed</span>
                        </div>
                    </div>
                    <div class="stat-card-order cancelled">
                        <div class="stat-icon-order">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-details-order">
                            <span class="stat-value"><?php echo $stats['cancelled'] ?? 0; ?></span>
                            <span class="stat-label">Cancelled</span>
                        </div>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="orders-table-wrapper">
                    <table class="orders-table">
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
                                <td class="order-id">#<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?></td>
                                <td class="customer-name">
                                    <i class="fas fa-user-circle"></i>
                                    <?php echo htmlspecialchars($order['full_name']); ?>
                                </td>
                                <td class="order-total">₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($order['order_status']); ?>">
                                        <i class="fas <?php 
                                            echo $order['order_status'] == 'Pending' ? 'fa-clock' : 
                                                ($order['order_status'] == 'Processing' ? 'fa-spinner' : 
                                                ($order['order_status'] == 'Completed' ? 'fa-check-circle' : 'fa-times-circle')); 
                                        ?>"></i>
                                        <?php echo $order['order_status']; ?>
                                    </span>
                                </td>
                                <td class="order-date">
                                    <i class="fas fa-calendar-alt"></i>
                                    <?php echo date('M j, Y', strtotime($order['order_date'])); ?>
                                </td>
                                <td>
                                    <a href="?view=<?php echo $order['order_id']; ?>" class="view-order-btn">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.orders-admin {
    padding: 2rem 0;
    background: linear-gradient(135deg, #f5f7fa 0%, #f8fafc 100%);
    min-height: 100vh;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.header-left h1 {
    font-size: 2rem;
    font-weight: 800;
    background: linear-gradient(135deg, #4361ee, #7209b7);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    margin-bottom: 0.25rem;
}

.header-left p {
    color: #64748b;
    font-size: 0.9rem;
}

.admin-nav {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    background: white;
    padding: 0.5rem;
    border-radius: 16px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.admin-nav .nav-link {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 0.75rem 1.25rem;
    text-decoration: none;
    color: #64748b;
    border-radius: 12px;
    transition: all 0.3s ease;
    font-weight: 500;
}

.admin-nav .nav-link i {
    font-size: 1rem;
}

.admin-nav .nav-link:hover {
    background: rgba(67, 97, 238, 0.08);
    color: #4361ee;
}

.admin-nav .nav-link.active {
    background: linear-gradient(135deg, #4361ee, #7209b7);
    color: white;
}

.alert-message {
    padding: 1rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-message.success {
    background: #d1fae5;
    color: #059669;
    border-left: 4px solid #059669;
}

.alert-message.error {
    background: #fee2e2;
    color: #dc2626;
    border-left: 4px solid #dc2626;
}

.alert-message.warning {
    background: #fef3c7;
    color: #d97706;
    border-left: 4px solid #d97706;
}

.stats-orders-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card-order {
    background: white;
    border-radius: 16px;
    padding: 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
}

.stat-card-order:hover {
    transform: translateY(-4px);
}

.stat-icon-order {
    width: 55px;
    height: 55px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
}

.stat-card-order.pending .stat-icon-order {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
}

.stat-card-order.processing .stat-icon-order {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
}

.stat-card-order.completed .stat-icon-order {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.stat-card-order.cancelled .stat-icon-order {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

.stat-details-order {
    flex: 1;
}

.stat-value {
    display: block;
    font-size: 1.8rem;
    font-weight: 800;
    color: #1e2a3e;
}

.stat-label {
    font-size: 0.75rem;
    color: #64748b;
}

.orders-table-wrapper {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.orders-table {
    width: 100%;
    border-collapse: collapse;
}

.orders-table th,
.orders-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
}

.orders-table th {
    background: #f8fafc;
    font-weight: 600;
    color: #1e2a3e;
    font-size: 0.85rem;
}

.orders-table tr:hover {
    background: #f8fafc;
}

.order-id {
    font-weight: 600;
    color: #4361ee;
    font-family: monospace;
}

.customer-name i {
    color: #64748b;
    margin-right: 8px;
}

.order-total {
    font-weight: 600;
    color: #1e2a3e;
}

.order-date {
    font-size: 0.85rem;
    color: #64748b;
}

.order-date i {
    margin-right: 5px;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-pending {
    background: #fef3c7;
    color: #d97706;
}

.status-processing {
    background: #dbeafe;
    color: #2563eb;
}

.status-completed {
    background: #d1fae5;
    color: #059669;
}

.status-cancelled {
    background: #fee2e2;
    color: #dc2626;
}

.view-order-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 0.5rem 1rem;
    background: #f1f5f9;
    color: #4361ee;
    text-decoration: none;
    border-radius: 8px;
    font-size: 0.8rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.view-order-btn:hover {
    background: #4361ee;
    color: white;
}

.order-details {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.order-details-header {
    margin-bottom: 1.5rem;
}

.back-link {
    margin-bottom: 1rem;
}

.btn-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 0.5rem 1rem;
    background: white;
    color: #4361ee;
    text-decoration: none;
    border-radius: 10px;
    transition: all 0.3s ease;
}

.btn-back:hover {
    gap: 12px;
    background: #f1f5f9;
}

.order-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
}

.order-title h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e2a3e;
    margin: 0;
}

.order-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 0.5rem 1.2rem;
    border-radius: 50px;
    font-size: 0.85rem;
    font-weight: 600;
}

.order-status-badge.status-pending {
    background: #fef3c7;
    color: #d97706;
}

.order-status-badge.status-processing {
    background: #dbeafe;
    color: #2563eb;
}

.order-status-badge.status-completed {
    background: #d1fae5;
    color: #059669;
}

.order-status-badge.status-cancelled {
    background: #fee2e2;
    color: #dc2626;
}

.order-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.info-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.info-card-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 1rem 1.5rem;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.info-card-header i {
    font-size: 1.1rem;
    color: #4361ee;
}

.info-card-header h3 {
    font-size: 1rem;
    font-weight: 600;
    color: #1e2a3e;
    margin: 0;
}

.info-card-body {
    padding: 1.5rem;
}

.info-row {
    display: flex;
    margin-bottom: 0.75rem;
}

.info-row:last-child {
    margin-bottom: 0;
}

.info-label {
    width: 130px;
    font-weight: 500;
    color: #64748b;
    font-size: 0.85rem;
}

.info-value {
    flex: 1;
    color: #1e2a3e;
    font-size: 0.85rem;
}

.status-update-card {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.status-update-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 1rem;
}

.status-update-header i {
    font-size: 1.1rem;
    color: #4361ee;
}

.status-update-header h3 {
    font-size: 1rem;
    font-weight: 600;
    color: #1e2a3e;
    margin: 0;
}

.status-form {
    display: flex;
    align-items: center;
}

.status-select-wrapper {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}

.status-select {
    padding: 0.75rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    font-size: 0.9rem;
    background: white;
    cursor: pointer;
}

.status-select:focus {
    outline: none;
    border-color: #4361ee;
}

.btn-update-status {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, #4361ee, #7209b7);
    color: white;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-update-status:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
}

.order-items-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.order-items-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 1rem 1.5rem;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.order-items-header i {
    font-size: 1.1rem;
    color: #4361ee;
}

.order-items-header h3 {
    font-size: 1rem;
    font-weight: 600;
    color: #1e2a3e;
    margin: 0;
}

.order-items-table {
    width: 100%;
    border-collapse: collapse;
}

.order-items-table th,
.order-items-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
}

.order-items-table th {
    background: #f8fafc;
    font-weight: 600;
    color: #1e2a3e;
    font-size: 0.85rem;
}

.product-cell {
    min-width: 200px;
}

.product-info-order {
    display: flex;
    align-items: center;
    gap: 12px;
}

.product-image-order {
    width: 45px;
    height: 45px;
    border-radius: 8px;
    overflow: hidden;
    background: #f1f5f9;
}

.product-image-order img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.quantity-cell {
    text-align: center;
}

.price-cell {
    text-align: right;
}

.subtotal-cell {
    text-align: right;
    font-weight: 600;
    color: #1e2a3e;
}

.total-row {
    background: #f8fafc;
}

.total-label {
    text-align: right;
    font-weight: 700;
    font-size: 1rem;
    color: #1e2a3e;
}

.total-amount {
    text-align: right;
    font-weight: 800;
    font-size: 1.2rem;
    color: #4361ee;
}

.empty-state {
    text-align: center;
    padding: 4rem;
    background: white;
    border-radius: 20px;
}

.empty-state i {
    font-size: 4rem;
    color: #cbd5e1;
    margin-bottom: 1rem;
}

.empty-state h3 {
    font-size: 1.2rem;
    color: #1e2a3e;
    margin-bottom: 0.5rem;
}

.empty-state p {
    color: #64748b;
}

@media (max-width: 768px) {
    .orders-admin {
        padding: 1rem 0;
    }
    
    .header-left h1 {
        font-size: 1.5rem;
    }
    
    .stats-orders-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .order-title {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .order-details-grid {
        grid-template-columns: 1fr;
    }
    
    .status-select-wrapper {
        flex-direction: column;
        align-items: stretch;
    }
    
    .orders-table {
        font-size: 0.85rem;
    }
    
    .orders-table th,
    .orders-table td {
        padding: 0.75rem;
    }
    
    .product-info-order {
        flex-direction: column;
        text-align: center;
    }
}

@media (max-width: 480px) {
    .stats-orders-grid {
        grid-template-columns: 1fr;
    }
    
    .info-row {
        flex-direction: column;
    }
    
    .info-label {
        width: 100%;
        margin-bottom: 0.25rem;
    }
    
    .orders-table thead {
        display: none;
    }
    
    .orders-table tbody tr {
        display: block;
        margin-bottom: 1rem;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 0.75rem;
    }
    
    .orders-table td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
        border: none;
    }
    
    .orders-table td::before {
        content: attr(data-label);
        font-weight: 600;
        color: #64748b;
        font-size: 0.75rem;
    }
}
</style>

<?php
require_once '../includes/footer.php';
?>