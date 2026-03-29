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

// Get pending orders count
$stmt = $conn->query("SELECT COUNT(*) as total FROM orders WHERE order_status = 'Pending'");
$result = $stmt->fetch();
$pending_orders = isset($result['total']) ? $result['total'] : 0;

// Get recent orders
$stmt = $conn->query("SELECT o.*, u.full_name FROM orders o 
                      JOIN users u ON o.user_id = u.user_id 
                      ORDER BY o.order_date DESC LIMIT 5");
$recent_orders = $stmt->fetchAll();

// Get low stock products
$stmt = $conn->query("SELECT COUNT(*) as total FROM products WHERE stock < 10 AND stock > 0");
$result = $stmt->fetch();
$low_stock = isset($result['total']) ? $result['total'] : 0;

// Get out of stock products
$stmt = $conn->query("SELECT COUNT(*) as total FROM products WHERE stock = 0");
$result = $stmt->fetch();
$out_of_stock = isset($result['total']) ? $result['total'] : 0;
?>

<div class="admin-dashboard">
    <div class="container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="dashboard-title">
                <h1>Admin Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</p>
            </div>
            <div class="dashboard-date">
                <i class="fas fa-calendar-alt"></i>
                <span><?php echo date('F j, Y'); ?></span>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon products-icon">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($product_count); ?></h3>
                    <p>Total Products</p>
                </div>
                <div class="stat-trend positive">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon orders-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($order_count); ?></h3>
                    <p>Total Orders</p>
                </div>
                <div class="stat-trend">
                    <span class="pending-badge"><?php echo $pending_orders; ?> pending</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon revenue-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-info">
                    <h3>₱<?php echo number_format($revenue, 2); ?></h3>
                    <p>Total Revenue</p>
                </div>
                <div class="stat-trend positive">
                    <i class="fas fa-arrow-up"></i>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon customers-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($user_count); ?></h3>
                    <p>Total Customers</p>
                </div>
                <div class="stat-trend">
                    <i class="fas fa-user-plus"></i>
                </div>
            </div>
        </div>

        <!-- Secondary Stats -->
        <div class="secondary-stats">
            <div class="secondary-card">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong><?php echo $low_stock; ?></strong> Products Low in Stock
                </div>
                <a href="products.php?filter=low-stock">View Details →</a>
            </div>
            <div class="secondary-card warning">
                <i class="fas fa-times-circle"></i>
                <div>
                    <strong><?php echo $out_of_stock; ?></strong> Products Out of Stock
                </div>
                <a href="products.php?filter=out-of-stock">View Details →</a>
            </div>
            <div class="secondary-card info">
                <i class="fas fa-clock"></i>
                <div>
                    <strong><?php echo $pending_orders; ?></strong> Pending Orders
                </div>
                <a href="orders.php?filter=pending">View Details →</a>
            </div>
        </div>

        <!-- Admin Navigation Menu -->
        <div class="admin-nav">
            <a href="/app/electrastore/admin/index.php" class="nav-card active">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="/app/electrastore/admin/products.php" class="nav-card">
                <i class="fas fa-box"></i>
                <span>Products</span>
            </a>
            <a href="/app/electrastore/admin/categories.php" class="nav-card">
                <i class="fas fa-tags"></i>
                <span>Categories</span>
            </a>
            <a href="/app/electrastore/admin/orders.php" class="nav-card">
                <i class="fas fa-shopping-cart"></i>
                <span>Orders</span>
            </a>
            <a href="/app/electrastore/admin/users.php" class="nav-card">
                <i class="fas fa-users"></i>
                <span>Users</span>
            </a>
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
                    <p>There are no orders to display at this time.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="admin-table-modern">
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
                                <td class="order-id">#<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?></td>
                                <td class="customer-name">
                                    <i class="fas fa-user-circle"></i>
                                    <?php echo htmlspecialchars($order['full_name']); ?>
                                </td>
                                <td class="order-total">₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($order['order_status']); ?>">
                                        <?php
                                        $status_icon = '';
                                        switch($order['order_status']) {
                                            case 'Pending':
                                                $status_icon = 'fa-clock';
                                                break;
                                            case 'Processing':
                                                $status_icon = 'fa-spinner';
                                                break;
                                            case 'Completed':
                                                $status_icon = 'fa-check-circle';
                                                break;
                                            case 'Cancelled':
                                                $status_icon = 'fa-times-circle';
                                                break;
                                            default:
                                                $status_icon = 'fa-info-circle';
                                        }
                                        ?>
                                        <i class="fas <?php echo $status_icon; ?>"></i>
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

        <!-- Quick Actions -->
        <div class="quick-actions">
            <div class="section-header">
                <h2>
                    <i class="fas fa-bolt"></i>
                    Quick Actions
                </h2>
            </div>
            <div class="actions-grid">
                <a href="/app/electrastore/admin/add_product.php" class="action-card">
                    <i class="fas fa-plus-circle"></i>
                    <span>Add New Product</span>
                    <p>Add a new product to your store</p>
                </a>
                <a href="/app/electrastore/admin/categories.php?action=add" class="action-card">
                    <i class="fas fa-folder-plus"></i>
                    <span>Add New Category</span>
                    <p>Create a new product category</p>
                </a>
                <a href="/app/electrastore/admin/orders.php" class="action-card">
                    <i class="fas fa-truck"></i>
                    <span>Process Orders</span>
                    <p>Review and process pending orders</p>
                </a>
                <a href="/app/electrastore/admin/users.php" class="action-card">
                    <i class="fas fa-user-shield"></i>
                    <span>Manage Staff</span>
                    <p>Add or manage staff accounts</p>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.admin-dashboard {
    padding: 2rem 0;
    background: linear-gradient(135deg, #f5f7fa 0%, #f8fafc 100%);
    min-height: 100vh;
}

/* Dashboard Header */
.dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.dashboard-title h1 {
    font-size: 2rem;
    font-weight: 800;
    background: linear-gradient(135deg, #4361ee, #7209b7);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    margin-bottom: 0.25rem;
}

.dashboard-title p {
    color: #64748b;
    font-size: 0.9rem;
}

.dashboard-date {
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

.dashboard-date i {
    color: #4361ee;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    background: white;
    border-radius: 20px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.products-icon {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
}

.orders-icon {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
}

.revenue-icon {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.customers-icon {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
    color: white;
}

.stat-info {
    flex: 1;
    text-align: center;
}

.stat-info h3 {
    font-size: 1.8rem;
    font-weight: 800;
    color: #1e2a3e;
    margin-bottom: 0.25rem;
}

.stat-info p {
    font-size: 0.8rem;
    color: #64748b;
}

.stat-trend {
    font-size: 0.85rem;
}

.stat-trend.positive {
    color: #10b981;
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
.secondary-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.secondary-card {
    background: white;
    border-radius: 12px;
    padding: 1rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    border-left: 4px solid #f97316;
}

.secondary-card i {
    font-size: 1.2rem;
    color: #f97316;
}

.secondary-card div {
    flex: 1;
    font-size: 0.85rem;
    color: #64748b;
}

.secondary-card strong {
    color: #1e2a3e;
    font-size: 1rem;
}

.secondary-card a {
    color: #4361ee;
    text-decoration: none;
    font-size: 0.8rem;
    font-weight: 500;
    transition: color 0.3s ease;
}

.secondary-card a:hover {
    color: #7209b7;
}

.secondary-card.warning {
    border-left-color: #dc2626;
}

.secondary-card.warning i {
    color: #dc2626;
}

.secondary-card.info {
    border-left-color: #3b82f6;
}

.secondary-card.info i {
    color: #3b82f6;
}

/* Admin Navigation */
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

.nav-card {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 0.75rem 1.5rem;
    text-decoration: none;
    color: #64748b;
    border-radius: 12px;
    transition: all 0.3s ease;
    font-weight: 500;
}

.nav-card i {
    font-size: 1rem;
}

.nav-card:hover {
    background: rgba(67, 97, 238, 0.08);
    color: #4361ee;
}

.nav-card.active {
    background: linear-gradient(135deg, #4361ee, #7209b7);
    color: white;
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

/* Admin Table */
.table-responsive {
    overflow-x: auto;
}

.admin-table-modern {
    width: 100%;
    border-collapse: collapse;
}

.admin-table-modern th,
.admin-table-modern td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
}

.admin-table-modern th {
    background: #f8fafc;
    font-weight: 600;
    color: #1e2a3e;
    font-size: 0.85rem;
}

.admin-table-modern tr:hover {
    background: #f8fafc;
}

.order-id {
    font-weight: 600;
    color: #4361ee;
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

/* Quick Actions */
.quick-actions {
    background: white;
    border-radius: 20px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.action-card {
    display: block;
    padding: 1.25rem;
    background: #f8fafc;
    border-radius: 16px;
    text-decoration: none;
    transition: all 0.3s ease;
    border: 1px solid #e2e8f0;
}

.action-card:hover {
    transform: translateY(-4px);
    border-color: #4361ee;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

.action-card i {
    font-size: 1.5rem;
    color: #4361ee;
    margin-bottom: 0.75rem;
    display: block;
}

.action-card span {
    display: block;
    font-weight: 600;
    color: #1e2a3e;
    margin-bottom: 0.25rem;
}

.action-card p {
    font-size: 0.75rem;
    color: #64748b;
    margin: 0;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 3rem;
}

.empty-state i {
    font-size: 3rem;
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
@media (max-width: 768px) {
    .admin-dashboard {
        padding: 1rem 0;
    }
    
    .dashboard-title h1 {
        font-size: 1.5rem;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
    
    .stat-card {
        padding: 1rem;
    }
    
    .stat-icon {
        width: 45px;
        height: 45px;
        font-size: 1.2rem;
    }
    
    .stat-info h3 {
        font-size: 1.3rem;
    }
    
    .secondary-stats {
        grid-template-columns: 1fr;
    }
    
    .admin-nav {
        overflow-x: auto;
        flex-wrap: nowrap;
    }
    
    .nav-card {
        white-space: nowrap;
    }
    
    .actions-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<?php
require_once '../includes/footer.php';
?>