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

$conn = getConnection();

$error = '';
$success = '';
$product = null;

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "Invalid product ID";
    $_SESSION['message_type'] = 'error';
    header('Location: products.php');
    exit();
}

$product_id = (int)$_GET['id'];

// Get product details
$stmt = $conn->prepare("SELECT p.*, c.category_name 
                        FROM products p 
                        LEFT JOIN categories c ON p.category_id = c.category_id 
                        WHERE p.product_id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    $_SESSION['message'] = "Product not found";
    $_SESSION['message_type'] = 'error';
    header('Location: products.php');
    exit();
}

// Check if product exists in orders before showing delete form
$check_orders = $conn->prepare("SELECT COUNT(*) as count FROM order_items WHERE product_id = ?");
$check_orders->execute([$product_id]);
$order_result = $check_orders->fetch();
$order_count = isset($order_result['count']) ? $order_result['count'] : 0;

$check_cart = $conn->prepare("SELECT COUNT(*) as count FROM cart_items WHERE product_id = ?");
$check_cart->execute([$product_id]);
$cart_result = $check_cart->fetch();
$cart_count = isset($cart_result['count']) ? $cart_result['count'] : 0;

// Handle deletion confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    try {
        // Double-check orders again before deletion
        $check_orders->execute([$product_id]);
        $order_result = $check_orders->fetch();
        $order_count = isset($order_result['count']) ? $order_result['count'] : 0;
        
        if ($order_count > 0) {
            $error = "Cannot delete this product because it has been ordered by customers (found in $order_count orders).";
        } else {
            // Delete cart items first (if any)
            if ($cart_count > 0) {
                $delete_cart = $conn->prepare("DELETE FROM cart_items WHERE product_id = ?");
                $delete_cart->execute([$product_id]);
            }
            
            // Now delete the product
            $delete_stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
            if ($delete_stmt->execute([$product_id])) {
                $_SESSION['message'] = "Product deleted successfully!";
                $_SESSION['message_type'] = 'success';
                header("Location: products.php");
                exit();
            } else {
                $error = "Failed to delete product. Please try again.";
            }
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Alternative: Soft delete (set stock to 0) if product has orders
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['soft_delete'])) {
    try {
        $stmt = $conn->prepare("UPDATE products SET stock = 0 WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $_SESSION['message'] = "Product has been marked as out of stock (hidden from customers).";
        $_SESSION['message_type'] = 'success';
        header("Location: products.php");
        exit();
    } catch (PDOException $e) {
        $error = "Failed to update product: " . $e->getMessage();
    }
}

// Now include header AFTER all redirect logic
require_once '../includes/header.php';
requireAdmin();
?>

<div class="delete-product-admin">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-left">
                <h1>Delete Product</h1>
                <p>Remove product from your inventory</p>
            </div>
            <div class="header-right">
                <a href="products.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Products
                </a>
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
            <a href="/app/electrastore/admin/orders.php" class="nav-link">
                <i class="fas fa-shopping-cart"></i> Orders
            </a>
            <a href="/app/electrastore/admin/users.php" class="nav-link">
                <i class="fas fa-users"></i> Users
            </a>
        </div>

        <!-- Messages -->
        <?php if ($error): ?>
            <div class="alert-message error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert-message success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (!$error && !$success): ?>
            <!-- Warning Card -->
            <div class="warning-card">
                <div class="warning-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="warning-content">
                    <h3>Warning: This action cannot be undone!</h3>
                    <p>Deleting a product will permanently remove it from your database. Please review the product details below before proceeding.</p>
                </div>
            </div>

            <!-- Product Details Card -->
            <div class="product-details-card">
                <h3>
                    <i class="fas fa-box"></i>
                    Product Details
                </h3>
                
                <div class="details-grid">
                    <div class="detail-item">
                        <span class="detail-label">Product Name:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($product['product_name']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Category:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Price:</span>
                        <span class="detail-value price">₱<?php echo number_format($product['price'], 2); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Stock:</span>
                        <span class="detail-value stock <?php echo $product['stock'] <= 0 ? 'out' : ($product['stock'] < 10 ? 'low' : 'in'); ?>">
                            <?php echo $product['stock']; ?> units
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Brand:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($product['brand'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Warranty:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($product['warranty'] ?? 'N/A'); ?></span>
                    </div>
                    <?php if ($order_count > 0): ?>
                    <div class="detail-item warning">
                        <span class="detail-label">Order History:</span>
                        <span class="detail-value">
                            <i class="fas fa-exclamation-circle"></i> 
                            Appears in <?php echo $order_count; ?> customer order(s)
                        </span>
                    </div>
                    <?php endif; ?>
                    <?php if ($cart_count > 0): ?>
                    <div class="detail-item warning">
                        <span class="detail-label">Active Carts:</span>
                        <span class="detail-value">
                            <i class="fas fa-exclamation-circle"></i> 
                            In <?php echo $cart_count; ?> customer cart(s)
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($product['image_url'])): ?>
                <div class="product-image-preview">
                    <label>Product Image:</label>
                    <img src="/app/electrastore/assets/images/products/<?php echo htmlspecialchars($product['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                         onerror="this.src='/app/electrastore/assets/images/products/placeholder.jpg'">
                </div>
                <?php endif; ?>
            </div>

            <!-- Action Buttons -->
            <?php if ($order_count == 0): ?>
                <div class="action-card delete-action">
                    <div class="action-content">
                        <i class="fas fa-trash-alt"></i>
                        <div>
                            <h4>Permanently Delete Product</h4>
                            <p>This will completely remove the product from your database. This action cannot be reversed.</p>
                        </div>
                    </div>
                    <form method="POST" action="" onsubmit="return confirm('Are you sure you want to permanently delete this product? This action cannot be undone.');">
                        <button type="submit" name="confirm_delete" class="btn-delete">
                            <i class="fas fa-trash-alt"></i> Yes, Delete Product
                        </button>
                        <a href="products.php" class="btn-cancel-action">Cancel</a>
                    </form>
                </div>
            <?php else: ?>
                <!-- Soft Delete Option -->
                <div class="action-card soft-delete-action">
                    <div class="action-content">
                        <i class="fas fa-eye-slash"></i>
                        <div>
                            <h4>Cannot Delete - Product Has Orders</h4>
                            <p>This product has existing orders and cannot be permanently deleted. You can hide it from customers instead.</p>
                        </div>
                    </div>
                    <form method="POST" action="" onsubmit="return confirm('Mark this product as out of stock? It will be hidden from customers but kept for order records.');">
                        <button type="submit" name="soft_delete" class="btn-soft-delete">
                            <i class="fas fa-eye-slash"></i> Mark as Out of Stock
                        </button>
                        <a href="products.php" class="btn-cancel-action">Back to Products</a>
                    </form>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.delete-product-admin {
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
    background: linear-gradient(135deg, #dc2626, #ef4444);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    margin-bottom: 0.25rem;
}

.header-left p {
    color: #64748b;
    font-size: 0.9rem;
}

.btn-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 0.6rem 1.2rem;
    background: white;
    color: #4361ee;
    text-decoration: none;
    border-radius: 10px;
    font-weight: 500;
    transition: all 0.3s ease;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.btn-back:hover {
    gap: 12px;
    background: #f1f5f9;
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

/* Alert Message */
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

/* Warning Card */
.warning-card {
    background: linear-gradient(135deg, #fef3c7, #fffbeb);
    border: 1px solid #fcd34d;
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.warning-icon {
    width: 60px;
    height: 60px;
    background: #fef3c7;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.warning-icon i {
    font-size: 1.8rem;
    color: #d97706;
}

.warning-content h3 {
    font-size: 1rem;
    font-weight: 700;
    color: #92400e;
    margin-bottom: 0.25rem;
}

.warning-content p {
    font-size: 0.85rem;
    color: #b45309;
    margin: 0;
}

/* Product Details Card */
.product-details-card {
    background: white;
    border-radius: 20px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.product-details-card h3 {
    font-size: 1rem;
    font-weight: 600;
    color: #1e2a3e;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 8px;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #e2e8f0;
}

.product-details-card h3 i {
    color: #4361ee;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: #f8fafc;
    border-radius: 10px;
}

.detail-item.warning {
    background: #fef3c7;
    grid-column: span 2;
}

.detail-label {
    font-size: 0.8rem;
    font-weight: 500;
    color: #64748b;
}

.detail-value {
    font-size: 0.85rem;
    font-weight: 600;
    color: #1e2a3e;
}

.detail-value.price {
    color: #4361ee;
    font-size: 1rem;
}

.detail-value.stock.in {
    color: #059669;
}

.detail-value.stock.low {
    color: #d97706;
}

.detail-value.stock.out {
    color: #dc2626;
}

.product-image-preview {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e2e8f0;
}

.product-image-preview label {
    display: block;
    font-size: 0.8rem;
    font-weight: 500;
    color: #64748b;
    margin-bottom: 0.5rem;
}

.product-image-preview img {
    max-width: 150px;
    max-height: 150px;
    object-fit: cover;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}

/* Action Card */
.action-card {
    background: white;
    border-radius: 20px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.action-card.delete-action {
    border-left: 4px solid #dc2626;
}

.action-card.soft-delete-action {
    border-left: 4px solid #f59e0b;
}

.action-content {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.action-content i {
    font-size: 2rem;
}

.delete-action .action-content i {
    color: #dc2626;
}

.soft-delete-action .action-content i {
    color: #f59e0b;
}

.action-content h4 {
    font-size: 1rem;
    font-weight: 600;
    color: #1e2a3e;
    margin-bottom: 0.25rem;
}

.action-content p {
    font-size: 0.8rem;
    color: #64748b;
    margin: 0;
}

.btn-delete {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 0.8rem 1.5rem;
    background: #dc2626;
    color: white;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-right: 1rem;
}

.btn-delete:hover {
    background: #b91c1c;
    transform: translateY(-2px);
}

.btn-soft-delete {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 0.8rem 1.5rem;
    background: #f59e0b;
    color: white;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-right: 1rem;
}

.btn-soft-delete:hover {
    background: #d97706;
    transform: translateY(-2px);
}

.btn-cancel-action {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 0.8rem 1.5rem;
    background: #f1f5f9;
    color: #64748b;
    text-decoration: none;
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-cancel-action:hover {
    background: #e2e8f0;
    color: #1e2a3e;
}

/* Responsive */
@media (max-width: 768px) {
    .delete-product-admin {
        padding: 1rem 0;
    }
    
    .header-left h1 {
        font-size: 1.5rem;
    }
    
    .warning-card {
        flex-direction: column;
        text-align: center;
    }
    
    .details-grid {
        grid-template-columns: 1fr;
    }
    
    .detail-item.warning {
        grid-column: span 1;
    }
    
    .action-content {
        flex-direction: column;
        text-align: center;
    }
    
    .btn-delete, .btn-soft-delete, .btn-cancel-action {
        display: block;
        width: 100%;
        text-align: center;
        margin: 0.5rem 0;
    }
}

@media (max-width: 480px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .detail-item {
        flex-direction: column;
        text-align: center;
        gap: 0.5rem;
    }
}
</style>

<?php
require_once '../includes/footer.php';
?>