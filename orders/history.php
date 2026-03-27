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

<div class="orders-wrapper">
    <div class="container">
        <!-- Page Header -->
        <div class="orders-header">
            <h1>My Orders</h1>
            <p>Track and manage your orders</p>
        </div>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <span>Order placed successfully! Your order has been confirmed.</span>
            </div>
        <?php endif; ?>
        
        <?php if (count($orders) == 0): ?>
            <div class="empty-orders">
                <div class="empty-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <h3>No Orders Yet</h3>
                <p>You haven't placed any orders yet. Start shopping to see your orders here.</p>
                <a href="/electrastore/products/index.php" class="btn btn-primary">
                    <i class="fas fa-store"></i> Start Shopping
                </a>
            </div>
        <?php else: ?>
            <div class="orders-list">
                <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <!-- Order Header -->
                    <div class="order-header">
                        <div class="order-info">
                            <div class="order-number">
                                <i class="fas fa-receipt"></i>
                                <span>Order #<?php echo $order['order_id']; ?></span>
                            </div>
                            <div class="order-date">
                                <i class="fas fa-calendar-alt"></i>
                                <span><?php echo date('F j, Y', strtotime($order['order_date'])); ?></span>
                            </div>
                        </div>
                        <div class="order-status">
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
                        </div>
                    </div>
                    
                    <!-- Order Items -->
                    <div class="order-items">
                        <?php
                        $stmt = $conn->prepare("SELECT oi.*, p.product_name, p.image_url 
                                                FROM order_items oi 
                                                JOIN products p ON oi.product_id = p.product_id 
                                                WHERE oi.order_id = ?");
                        $stmt->execute([$order['order_id']]);
                        $items = $stmt->fetchAll();
                        ?>
                        
                        <table class="order-items-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td class="product-info-cell">
                                        <div class="order-product-info">
                                            <img src="/electrastore/assets/images/products/<?php echo htmlspecialchars($item['image_url'] ?: 'placeholder.jpg'); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                                 class="order-product-image">
                                            <div class="order-product-details">
                                                <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="quantity-cell">
                                        <span class="quantity-badge">x<?php echo $item['quantity']; ?></span>
                                    </td>
                                    <td class="price-cell">₱<?php echo number_format($item['price'], 2); ?></td>
                                    <td class="subtotal-cell">₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="total-row">
                                    <td colspan="3" class="total-label">Total Amount</td>
                                    <td class="total-amount">₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <!-- Order Footer -->
                    <div class="order-footer">
                        <div class="shipping-info">
                            <i class="fas fa-truck"></i>
                            <div>
                                <strong>Shipping Address</strong>
                                <p><?php echo htmlspecialchars($order['shipping_address']); ?></p>
                            </div>
                        </div>
                        <div class="payment-info">
                            <i class="fas fa-credit-card"></i>
                            <div>
                                <strong>Payment Method</strong>
                                <p><?php echo strtoupper($order['payment_method']); ?></p>
                            </div>
                        </div>
                        <div class="order-actions">
                            <button class="btn-outline-small" onclick="toggleOrderDetails(<?php echo $order['order_id']; ?>)">
                                <i class="fas fa-chevron-down"></i> View Details
                            </button>
                        </div>
                    </div>
                    
                    <!-- Extended Details (Hidden by default) -->
                    <div class="order-extended-details" id="order-details-<?php echo $order['order_id']; ?>" style="display: none;">
                        <div class="extended-content">
                            <h4><i class="fas fa-info-circle"></i> Order Information</h4>
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">Order Date:</span>
                                    <span class="info-value"><?php echo date('F j, Y g:i A', strtotime($order['order_date'])); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Order Status:</span>
                                    <span class="info-value status-<?php echo strtolower($order['order_status']); ?>">
                                        <?php echo $order['order_status']; ?>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Payment Method:</span>
                                    <span class="info-value"><?php echo strtoupper($order['payment_method']); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Total Amount:</span>
                                    <span class="info-value">₱<?php echo number_format($order['total_amount'], 2); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.orders-wrapper {
    padding: 2rem 0;
    background: linear-gradient(135deg, #f5f7fa 0%, #f8fafc 100%);
    min-height: 100vh;
}

.orders-header {
    text-align: center;
    margin-bottom: 3rem;
}

.orders-header h1 {
    font-size: 2.5rem;
    font-weight: 800;
    background: linear-gradient(135deg, #4361ee, #7209b7);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    margin-bottom: 0.5rem;
}

.orders-header p {
    color: #64748b;
    font-size: 1rem;
}

/* Empty State */
.empty-orders {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 20px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.empty-icon {
    font-size: 4rem;
    color: #cbd5e1;
    margin-bottom: 1rem;
}

.empty-orders h3 {
    font-size: 1.2rem;
    color: #1e2a3e;
    margin-bottom: 0.5rem;
}

.empty-orders p {
    color: #64748b;
    margin-bottom: 1.5rem;
}

/* Orders List */
.orders-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.order-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.order-card:hover {
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

/* Order Header */
.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    background: linear-gradient(135deg, #f8fafc, #ffffff);
    border-bottom: 1px solid #e2e8f0;
}

.order-info {
    display: flex;
    gap: 2rem;
}

.order-number, .order-date {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #64748b;
    font-size: 0.9rem;
}

.order-number i, .order-date i {
    color: #4361ee;
}

.order-number span {
    font-weight: 600;
    color: #1e2a3e;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-size: 0.85rem;
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

/* Order Items Table */
.order-items {
    padding: 1.5rem;
}

.order-items-table {
    width: 100%;
    border-collapse: collapse;
}

.order-items-table th {
    text-align: left;
    padding: 0.75rem;
    background: #f8fafc;
    font-weight: 600;
    color: #1e2a3e;
    border-bottom: 2px solid #e2e8f0;
}

.order-items-table td {
    padding: 1rem 0.75rem;
    border-bottom: 1px solid #e2e8f0;
}

.product-info-cell {
    padding: 1rem 0.75rem;
}

.order-product-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.order-product-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 10px;
    background: #f1f5f9;
}

.order-product-details strong {
    color: #1e2a3e;
    font-size: 0.95rem;
}

.quantity-cell {
    text-align: center;
}

.quantity-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background: #f1f5f9;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    color: #4361ee;
}

.price-cell, .subtotal-cell {
    text-align: right;
    font-weight: 500;
}

.total-row {
    background: #f8fafc;
}

.total-label {
    text-align: right;
    font-weight: 700;
    color: #1e2a3e;
    font-size: 1.1rem;
}

.total-amount {
    text-align: right;
    font-weight: 800;
    color: #4361ee;
    font-size: 1.2rem;
}

/* Order Footer */
.order-footer {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    padding: 1.5rem;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
}

.shipping-info, .payment-info {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.shipping-info i, .payment-info i {
    font-size: 1.2rem;
    color: #4361ee;
    margin-top: 0.25rem;
}

.shipping-info div, .payment-info div {
    flex: 1;
}

.shipping-info strong, .payment-info strong {
    display: block;
    font-size: 0.8rem;
    color: #64748b;
    margin-bottom: 0.25rem;
}

.shipping-info p, .payment-info p {
    color: #1e2a3e;
    font-size: 0.9rem;
    margin: 0;
}

.order-actions {
    display: flex;
    justify-content: flex-end;
    align-items: center;
}

.btn-outline-small {
    padding: 0.5rem 1rem;
    background: transparent;
    border: 1px solid #4361ee;
    color: #4361ee;
    border-radius: 10px;
    cursor: pointer;
    font-size: 0.85rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-outline-small:hover {
    background: #4361ee;
    color: white;
}

/* Extended Details */
.order-extended-details {
    padding: 1.5rem;
    background: #f1f5f9;
    border-top: 1px solid #e2e8f0;
}

.extended-content h4 {
    font-size: 1rem;
    margin-bottom: 1rem;
    color: #1e2a3e;
}

.extended-content h4 i {
    color: #4361ee;
    margin-right: 8px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.info-item {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem;
    background: white;
    border-radius: 10px;
}

.info-label {
    color: #64748b;
    font-size: 0.85rem;
}

.info-value {
    font-weight: 600;
    color: #1e2a3e;
}

/* Responsive */
@media (max-width: 768px) {
    .orders-wrapper {
        padding: 1rem 0;
    }
    
    .orders-header h1 {
        font-size: 1.8rem;
    }
    
    .order-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .order-info {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .order-items-table {
        font-size: 0.85rem;
    }
    
    .order-product-image {
        width: 40px;
        height: 40px;
    }
    
    .order-footer {
        grid-template-columns: 1fr;
    }
    
    .order-actions {
        justify-content: flex-start;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .order-items-table thead {
        display: none;
    }
    
    .order-items-table tbody tr {
        display: block;
        margin-bottom: 1rem;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 1rem;
    }
    
    .order-items-table td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
        border: none;
    }
    
    .order-items-table td::before {
        content: attr(data-label);
        font-weight: 600;
        color: #64748b;
    }
    
    .product-info-cell {
        flex-direction: column;
    }
    
    .quantity-cell, .price-cell, .subtotal-cell {
        text-align: left;
    }
}
</style>

<script>
function toggleOrderDetails(orderId) {
    const detailsDiv = document.getElementById('order-details-' + orderId);
    const button = event.target.closest('.btn-outline-small');
    const icon = button.querySelector('i');
    
    if (detailsDiv.style.display === 'none') {
        detailsDiv.style.display = 'block';
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
    } else {
        detailsDiv.style.display = 'none';
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
    }
}
</script>

<?php
require_once '../includes/footer.php';
?>