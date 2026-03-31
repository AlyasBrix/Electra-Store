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

// Get new customers this month
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'user' AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())");
$stmt->execute();
$result = $stmt->fetch();
$new_this_month = isset($result['total']) ? $result['total'] : 0;

// Get total orders from customers
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM orders");
$stmt->execute();
$result = $stmt->fetch();
$total_orders = isset($result['total']) ? $result['total'] : 0;
?>

<div class="customers-admin">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-left">
                <h1>Customer Management</h1>
                <p>View and manage all registered customers</p>
            </div>
        </div>

        <!-- Staff Navigation -->
        <div class="staff-nav">
            <a href="/app/electrastore/staff/index.php" class="nav-link">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="/app/electrastore/staff/orders.php" class="nav-link">
                <i class="fas fa-shopping-cart"></i> Orders
            </a>
            <a href="/app/electrastore/staff/products.php" class="nav-link">
                <i class="fas fa-box"></i> Products
            </a>
            <a href="/app/electrastore/staff/customers.php" class="nav-link active">
                <i class="fas fa-users"></i> Customers
            </a>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-customers-grid">
            <div class="stat-card-customer total">
                <div class="stat-icon-customer">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-details-customer">
                    <span class="stat-value"><?php echo $total_customers; ?></span>
                    <span class="stat-label">Total Customers</span>
                </div>
            </div>
            <div class="stat-card-customer active">
                <div class="stat-icon-customer">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="stat-details-customer">
                    <span class="stat-value"><?php echo $active_buyers; ?></span>
                    <span class="stat-label">Active Buyers (30 days)</span>
                </div>
            </div>
            <div class="stat-card-customer new">
                <div class="stat-icon-customer">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="stat-details-customer">
                    <span class="stat-value"><?php echo $new_this_month; ?></span>
                    <span class="stat-label">New This Month</span>
                </div>
            </div>
            <div class="stat-card-customer orders">
                <div class="stat-icon-customer">
                    <i class="fas fa-receipt"></i>
                </div>
                <div class="stat-details-customer">
                    <span class="stat-value"><?php echo $total_orders; ?></span>
                    <span class="stat-label">Total Orders</span>
                </div>
            </div>
        </div>

        <!-- Customers Table -->
        <div class="customers-section">
            <div class="section-header">
                <h2>
                    <i class="fas fa-user-friends"></i>
                    All Customers
                </h2>
                <div class="customer-count">
                    <span class="count-badge"><?php echo $total_customers; ?> customers</span>
                </div>
            </div>

            <?php if (count($customers) == 0): ?>
                <div class="empty-state">
                    <i class="fas fa-users-slash"></i>
                    <h3>No Customers Found</h3>
                    <p>There are no registered customers yet.</p>
                </div>
            <?php else: ?>
                <div class="customers-table-wrapper">
                    <table class="customers-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Contact</th>
                                <th>Address</th>
                                <th>Registered</th>
                                <th>Last Login</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td class="customer-id">#<?php echo str_pad($customer['user_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                <td class="customer-info">
                                    <div class="customer-avatar">
                                        <i class="fas fa-user-circle"></i>
                                    </div>
                                    <div class="customer-details">
                                        <strong><?php echo htmlspecialchars($customer['full_name']); ?></strong>
                                        <small><?php echo htmlspecialchars($customer['email']); ?></small>
                                    </div>
                                </td>
                                <td class="customer-contact">
                                    <?php if (!empty($customer['phone'])): ?>
                                        <div><i class="fas fa-phone"></i> <?php echo htmlspecialchars($customer['phone']); ?></div>
                                    <?php else: ?>
                                        <div><i class="fas fa-phone"></i> <span class="na-text">N/A</span></div>
                                    <?php endif; ?>
                                </td>
                                <td class="customer-address">
                                    <?php echo htmlspecialchars(substr($customer['address'] ?? '', 0, 40)) . (strlen($customer['address'] ?? '') > 40 ? '...' : ''); ?>
                                    <?php if (empty($customer['address'])): ?>
                                        <span class="na-text">No address provided</span>
                                    <?php endif; ?>
                                </td>
                                <td class="registered-date">
                                    <i class="fas fa-calendar-alt"></i>
                                    <?php echo date('M j, Y', strtotime($customer['created_at'])); ?>
                                </td>
                                <td class="last-login">
                                    <i class="fas fa-clock"></i>
                                    <?php echo $customer['last_login'] ? date('M j, Y', strtotime($customer['last_login'])) : 'Never'; ?>
                                </td>
                                <td class="customer-status">
                                    <?php $status = $customer['status'] ?? 'active'; ?>
                                    <span class="status-badge status-<?php echo $status; ?>">
                                        <i class="fas <?php echo $status == 'active' ? 'fa-check-circle' : 'fa-ban'; ?>"></i>
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.customers-admin {
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
.stats-customers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card-customer {
    background: white;
    border-radius: 20px;
    padding: 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
}

.stat-card-customer:hover {
    transform: translateY(-4px);
}

.stat-icon-customer {
    width: 55px;
    height: 55px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
}

.stat-card-customer.total .stat-icon-customer {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
}

.stat-card-customer.active .stat-icon-customer {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.stat-card-customer.new .stat-icon-customer {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
}

.stat-card-customer.orders .stat-icon-customer {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
    color: white;
}

.stat-details-customer {
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

/* Customers Section */
.customers-section {
    background: white;
    border-radius: 20px;
    padding: 1.5rem;
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

.customer-count {
    display: flex;
    align-items: center;
}

.count-badge {
    background: #f1f5f9;
    color: #4361ee;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

/* Customers Table */
.customers-table-wrapper {
    overflow-x: auto;
}

.customers-table {
    width: 100%;
    border-collapse: collapse;
}

.customers-table th,
.customers-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
}

.customers-table th {
    background: #f8fafc;
    font-weight: 600;
    color: #1e2a3e;
    font-size: 0.85rem;
}

.customers-table tr:hover {
    background: #f8fafc;
}

.customer-id {
    font-weight: 600;
    color: #4361ee;
    font-family: monospace;
}

.customer-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.customer-avatar {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #4361ee, #7209b7);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.customer-avatar i {
    font-size: 1.2rem;
    color: white;
}

.customer-details strong {
    display: block;
    color: #1e2a3e;
    font-size: 0.9rem;
}

.customer-details small {
    font-size: 0.7rem;
    color: #64748b;
}

.customer-contact {
    font-size: 0.85rem;
    color: #1e2a3e;
}

.customer-contact i {
    color: #64748b;
    width: 20px;
}

.customer-address {
    font-size: 0.85rem;
    color: #64748b;
    max-width: 250px;
}

.na-text {
    color: #94a3b8;
    font-style: italic;
}

.registered-date,
.last-login {
    font-size: 0.85rem;
    color: #64748b;
}

.registered-date i,
.last-login i {
    margin-right: 5px;
}

.customer-status {
    text-align: center;
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

.status-badge.status-active {
    background: #d1fae5;
    color: #059669;
}

.status-badge.status-inactive {
    background: #fee2e2;
    color: #dc2626;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 4rem;
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

/* Responsive */
@media (max-width: 1024px) {
    .stats-customers-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .customers-admin {
        padding: 1rem 0;
    }
    
    .header-left h1 {
        font-size: 1.5rem;
    }
    
    .stats-customers-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
    }
    
    .stat-card-customer {
        padding: 1rem;
    }
    
    .stat-icon-customer {
        width: 45px;
        height: 45px;
        font-size: 1rem;
    }
    
    .stat-value {
        font-size: 1.3rem;
    }
    
    .customers-table thead {
        display: none;
    }
    
    .customers-table tbody tr {
        display: block;
        margin-bottom: 1rem;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 1rem;
    }
    
    .customers-table td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
        border: none;
    }
    
    .customers-table td::before {
        content: attr(data-label);
        font-weight: 600;
        color: #64748b;
        font-size: 0.75rem;
    }
    
    .customer-address {
        max-width: none;
    }
}

@media (max-width: 480px) {
    .stats-customers-grid {
        grid-template-columns: 1fr;
    }
    
    .stat-card-customer {
        padding: 0.75rem;
    }
    
    .stat-value {
        font-size: 1.2rem;
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