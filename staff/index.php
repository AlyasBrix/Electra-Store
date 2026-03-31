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

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE order_status = 'Processing'");
$stmt->execute();
$processing_orders = $stmt->fetch()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM products WHERE stock < 10");
$stmt->execute();
$low_stock = $stmt->fetch()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM products WHERE stock = 0");
$stmt->execute();
$out_of_stock = $stmt->fetch()['total'];

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

// Get today's orders
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE DATE(order_date) = CURDATE()");
$stmt->execute();
$today_orders = $stmt->fetch()['total'];

// Get this month's revenue
$stmt = $conn->prepare("SELECT SUM(total_amount) as total FROM orders WHERE MONTH(order_date) = MONTH(CURDATE()) AND YEAR(order_date) = YEAR(CURDATE()) AND order_status = 'Completed'");
$stmt->execute();
$monthly_revenue = $stmt->fetch()['total'] ?? 0;
?>

<div class="staff-dashboard">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-left">
                <h1>Staff Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</p>
            </div>
            <div class="header-right">
                <div class="date-display">
                    <i class="fas fa-calendar-alt"></i>
                    <span><?php echo date('F j, Y'); ?></span>
                </div>
            </div>
        </div>

        <!-- Staff Navigation -->
        <div class="staff-nav">
            <a href="/app/electrastore/staff/index.php" class="nav-link active">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="/app/electrastore/staff/orders.php" class="nav-link">
                <i class="fas fa-shopping-cart"></i> Orders
            </a>
            <a href="/app/electrastore/staff/products.php" class="nav-link">
                <i class="fas fa-box"></i> Products
            </a>
            <a href="/app/electrastore/staff/customers.php" class="nav-link">
                <i class="fas fa-users"></i> Customers
            </a>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-staff-grid">
            <div class="stat-card-staff orders">
                <div class="stat-icon-staff">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-details-staff">
                    <span class="stat-value"><?php echo $total_orders; ?></span>
                    <span class="stat-label">Total Orders</span>
                </div>
                <div class="stat-trend">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
            <div class="stat-card-staff pending">
                <div class="stat-icon-staff">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-details-staff">
                    <span class="stat-value"><?php echo $pending_orders; ?></span>
                    <span class="stat-label">Pending Orders</span>
                </div>
                <div class="stat-trend">
                    <span class="pending-badge">Need attention</span>
                </div>
            </div>
            <div class="stat-card-staff processing">
                <div class="stat-icon-staff">
                    <i class="fas fa-spinner"></i>
                </div>
                <div class="stat-details-staff">
                    <span class="stat-value"><?php echo $processing_orders; ?></span>
                    <span class="stat-label">Processing</span>
                </div>
            </div>
            <div class="stat-card-staff revenue">
                <div class="stat-icon-staff">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-details-staff">
                    <span class="stat-value">₱<?php echo number_format($monthly_revenue, 2); ?></span>
                    <span class="stat-label">This Month's Revenue</span>
                </div>
            </div>
        </div>

        <!-- Secondary Stats -->
        <div class="secondary-stats-staff">
            <div class="secondary-card-staff low-stock">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong><?php echo $low_stock; ?></strong> Products Low in Stock
                </div>
                <a href="products.php?filter=low-stock">View Details →</a>
            </div>
            <div class="secondary-card-staff out-stock">
                <i class="fas fa-times-circle"></i>
                <div>
                    <strong><?php echo $out_of_stock; ?></strong> Products Out of Stock
                </div>
                <a href="products.php?filter=out-of-stock">View Details →</a>
            </div>
            <div class="secondary-card-staff today-orders">
                <i class="fas fa-calendar-day"></i>
                <div>
                    <strong><?php echo $today_orders; ?></strong> Orders Today
                </div>
                <a href="orders.php?filter=today">View Details →</a>
            </div>
            <div class="secondary-card-staff customers">
                <i class="fas fa-users"></i>
                <div>
                    <strong><?php echo $total_customers; ?></strong> Total Customers
                </div>
                <a href="customers.php">View All →</a>
            </div>
        </div>

        <!-- Recent Orders Section -->
        <div class="recent-section">
            <div class="section-header">
                <h2>
                    <i class="fas fa-clock"></i>
                    Recent Orders
                </h2>
                <a href="orders.php" class="view-all-link">
                    View All Orders <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <?php if (count($recent_orders) == 0): ?>
                <div class="empty-state">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>No Orders Found</h3>
                    <p>There are no recent orders to display.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="staff-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
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
                                    <a href="orders.php?view=<?php echo $order['order_id']; ?>" class="view-btn">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Low Stock Alert Section -->
        <?php if (count($low_stock_products) > 0): ?>
        <div class="alert-section">
            <div class="section-header">
                <h2>
                    <i class="fas fa-exclamation-triangle"></i>
                    Low Stock Alert
                </h2>
                <a href="products.php?filter=low-stock" class="view-all-link">
                    View All <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            
            <div class="alert-card">
                <div class="low-stock-table-wrapper">
                    <table class="low-stock-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Current Stock</th>
                                <th>Status</th>
                                <th>Action</th>
                            </thead>
                        <tbody>
                            <?php foreach ($low_stock_products as $product): ?>
                            <tr>
                                <td class="product-name">
                                    <div class="product-info-staff">
                                        <img src="/app/electrastore/assets/images/products/<?php echo $product['image_url'] ?: 'placeholder.jpg'; ?>" 
                                             alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                                             class="product-thumb">
                                        <span><?php echo htmlspecialchars($product['product_name']); ?></span>
                                    </div>
                                </td>
                                <td class="stock-count <?php echo $product['stock'] == 0 ? 'out' : ($product['stock'] < 5 ? 'critical' : 'low'); ?>">
                                    <strong><?php echo $product['stock']; ?></strong> units
                                </td>
                                <td>
                                    <?php if ($product['stock'] == 0): ?>
                                        <span class="status-badge status-cancelled">
                                            <i class="fas fa-times-circle"></i> Out of Stock
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge status-pending">
                                            <i class="fas fa-exclamation-triangle"></i> Low Stock
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="/app/electrastore/staff/products.php?edit=<?php echo $product['product_id']; ?>" class="update-btn">
                                        <i class="fas fa-edit"></i> Update Stock
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.staff-dashboard {
    padding: 2rem 0;
    background: linear-gradient(135deg, #f5f7fa 0%, #f8fafc 100%);
    min-height: 100vh;
}

/* Page Header */
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

.date-display {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 0.5rem 1rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    color: #64748b;
    font-size: 0.9rem;
}

.date-display i {
    color: #4361ee;
}

/* Staff Navigation */
.staff-nav {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    background: white;
    padding: 0.5rem;
    border-radius: 16px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.staff-nav .nav-link {
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

.staff-nav .nav-link i {
    font-size: 1rem;
}

.staff-nav .nav-link:hover {
    background: rgba(67, 97, 238, 0.08);
    color: #4361ee;
}

.staff-nav .nav-link.active {
    background: linear-gradient(135deg, #4361ee, #7209b7);
    color: white;
}

/* Statistics Cards */
.stats-staff-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-card-staff {
    background: white;
    border-radius: 20px;
    padding: 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
}

.stat-card-staff:hover {
    transform: translateY(-4px);
}

.stat-icon-staff {
    width: 55px;
    height: 55px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
}

.stat-card-staff.orders .stat-icon-staff {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
}

.stat-card-staff.pending .stat-icon-staff {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
}

.stat-card-staff.processing .stat-icon-staff {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
    color: white;
}

.stat-card-staff.revenue .stat-icon-staff {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.stat-details-staff {
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

.stat-trend {
    font-size: 0.85rem;
}

.pending-badge {
    background: #fef3c7;
    color: #d97706;
    padding: 0.25rem 0.5rem;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
}

/* Secondary Stats */
.secondary-stats-staff {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.secondary-card-staff {
    background: white;
    border-radius: 12px;
    padding: 1rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    border-left: 4px solid #f97316;
}

.secondary-card-staff i {
    font-size: 1.2rem;
    color: #f97316;
}

.secondary-card-staff div {
    flex: 1;
    font-size: 0.85rem;
    color: #64748b;
}

.secondary-card-staff strong {
    color: #1e2a3e;
    font-size: 1rem;
}

.secondary-card-staff a {
    color: #4361ee;
    text-decoration: none;
    font-size: 0.8rem;
    font-weight: 500;
    transition: color 0.3s ease;
}

.secondary-card-staff a:hover {
    color: #7209b7;
}

.secondary-card-staff.low-stock {
    border-left-color: #f97316;
}

.secondary-card-staff.low-stock i {
    color: #f97316;
}

.secondary-card-staff.out-stock {
    border-left-color: #dc2626;
}

.secondary-card-staff.out-stock i {
    color: #dc2626;
}

.secondary-card-staff.today-orders {
    border-left-color: #3b82f6;
}

.secondary-card-staff.today-orders i {
    color: #3b82f6;
}

.secondary-card-staff.customers {
    border-left-color: #10b981;
}

.secondary-card-staff.customers i {
    color: #10b981;
}

/* Recent Orders Section */
.recent-section {
    background: white;
    border-radius: 20px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.section-header h2 {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1e2a3e;
    display: flex;
    align-items: center;
    gap: 8px;
}

.section-header h2 i {
    color: #4361ee;
}

.view-all-link {
    color: #4361ee;
    text-decoration: none;
    font-size: 0.85rem;
    font-weight: 500;
    transition: gap 0.3s ease;
    display: flex;
    align-items: center;
    gap: 5px;
}

.view-all-link:hover {
    gap: 10px;
}

/* Staff Table */
.table-responsive {
    overflow-x: auto;
}

.staff-table {
    width: 100%;
    border-collapse: collapse;
}

.staff-table th,
.staff-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
}

.staff-table th {
    background: #f8fafc;
    font-weight: 600;
    color: #1e2a3e;
    font-size: 0.85rem;
}

.staff-table tr:hover {
    background: #f8fafc;
}

.order-id {
    font-weight: 600;
    color: #4361ee;
    font-family: monospace;
}

.customer-name i {
    color: #64748b;
    margin-right: 5px;
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
    padding: 0.25rem 0.75rem;
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

.view-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 0.4rem 0.8rem;
    background: #f1f5f9;
    color: #4361ee;
    text-decoration: none;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.view-btn:hover {
    background: #4361ee;
    color: white;
}

/* Alert Section */
.alert-section {
    background: white;
    border-radius: 20px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.alert-card {
    margin-top: 1rem;
}

.low-stock-table-wrapper {
    overflow-x: auto;
}

.low-stock-table {
    width: 100%;
    border-collapse: collapse;
}

.low-stock-table th,
.low-stock-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
}

.low-stock-table th {
    background: #fef3c7;
    font-weight: 600;
    color: #92400e;
}

.product-info-staff {
    display: flex;
    align-items: center;
    gap: 12px;
}

.product-thumb {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 8px;
    background: #f1f5f9;
}

.stock-count {
    font-weight: 600;
}

.stock-count.low {
    color: #f97316;
}

.stock-count.critical {
    color: #dc2626;
}

.stock-count.out {
    color: #dc2626;
}

.update-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 0.4rem 0.8rem;
    background: #fef3c7;
    color: #d97706;
    text-decoration: none;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.update-btn:hover {
    background: #d97706;
    color: white;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 3rem;
}

.empty-state i {
    font-size: 4rem;
    color: #cbd5e1;
    margin-bottom: 1rem;
}

.empty-state h3 {
    font-size: 1rem;
    color: #1e2a3e;
    margin-bottom: 0.25rem;
}

.empty-state p {
    font-size: 0.85rem;
    color: #64748b;
}

/* Responsive */
@media (max-width: 1024px) {
    .stats-staff-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .staff-dashboard {
        padding: 1rem 0;
    }
    
    .header-left h1 {
        font-size: 1.5rem;
    }
    
    .stats-staff-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
    }
    
    .stat-card-staff {
        padding: 1rem;
    }
    
    .stat-icon-staff {
        width: 45px;
        height: 45px;
        font-size: 1rem;
    }
    
    .stat-value {
        font-size: 1.3rem;
    }
    
    .secondary-stats-staff {
        grid-template-columns: 1fr;
    }
    
    .staff-table {
        font-size: 0.85rem;
    }
    
    .staff-table th,
    .staff-table td {
        padding: 0.75rem;
    }
}

@media (max-width: 480px) {
    .stats-staff-grid {
        grid-template-columns: 1fr;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .product-info-staff {
        flex-direction: column;
        text-align: center;
    }
    
    .low-stock-table thead {
        display: none;
    }
    
    .low-stock-table tbody tr {
        display: block;
        margin-bottom: 1rem;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 1rem;
    }
    
    .low-stock-table td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
        border: none;
    }
    
    .low-stock-table td::before {
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