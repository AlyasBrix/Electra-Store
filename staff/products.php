<?php
// Start output buffering to prevent header issues
ob_start();

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

// Staff can update stock (but not add/delete products)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $product_id = (int)$_POST['product_id'];
    $new_stock = (int)$_POST['stock'];
    
    $stmt = $conn->prepare("UPDATE products SET stock = ? WHERE product_id = ?");
    if ($stmt->execute([$new_stock, $product_id])) {
        $_SESSION['message'] = "Stock updated successfully!";
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = "Failed to update stock!";
        $_SESSION['message_type'] = 'error';
    }
    
    // Clear buffer and redirect
    ob_end_clean();
    header('Location: products.php');
    exit();
}

// Get products
$stmt = $conn->prepare("SELECT p.*, c.category_name FROM products p 
                        LEFT JOIN categories c ON p.category_id = c.category_id 
                        ORDER BY p.created_at DESC");
$stmt->execute();
$products = $stmt->fetchAll();

// Get low stock count
$stmt = $conn->prepare("SELECT COUNT(*) as low_stock FROM products WHERE stock < 10 AND stock > 0");
$stmt->execute();
$low_stock_count = $stmt->fetch()['low_stock'] ?? 0;

// Get out of stock count
$stmt = $conn->prepare("SELECT COUNT(*) as out_stock FROM products WHERE stock = 0");
$stmt->execute();
$out_stock_count = $stmt->fetch()['out_stock'] ?? 0;

// Get total products count
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM products");
$stmt->execute();
$total_products = $stmt->fetch()['total'] ?? 0;
?>

<div class="staff-products">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-left">
                <h1>Product Inventory</h1>
                <p>View and update product stock levels</p>
            </div>
            <div class="header-right">
                <div class="info-badge">
                    <i class="fas fa-info-circle"></i>
                    Staff can only update stock quantities
                </div>
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
            <a href="/app/electrastore/staff/products.php" class="nav-link active">
                <i class="fas fa-box"></i> Products
            </a>
            <a href="/app/electrastore/staff/customers.php" class="nav-link">
                <i class="fas fa-users"></i> Customers
            </a>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-products-grid">
            <div class="stat-card-product total">
                <div class="stat-icon-product">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="stat-details-product">
                    <span class="stat-value"><?php echo $total_products; ?></span>
                    <span class="stat-label">Total Products</span>
                </div>
            </div>
            <div class="stat-card-product low-stock">
                <div class="stat-icon-product">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-details-product">
                    <span class="stat-value"><?php echo $low_stock_count; ?></span>
                    <span class="stat-label">Low Stock</span>
                </div>
            </div>
            <div class="stat-card-product out-stock">
                <div class="stat-icon-product">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-details-product">
                    <span class="stat-value"><?php echo $out_stock_count; ?></span>
                    <span class="stat-label">Out of Stock</span>
                </div>
            </div>
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

        <!-- Products Table -->
        <div class="products-table-wrapper">
            <table class="products-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Update Stock</th>
                    </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td class="product-id">#<?php echo str_pad($product['product_id'], 5, '0', STR_PAD_LEFT); ?></td>
                        <td class="product-image-cell">
                            <div class="product-image-wrapper">
                                <img src="/app/electrastore/assets/images/products/<?php echo $product['image_url'] ?: 'placeholder.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                                     class="product-thumbnail"
                                     onerror="this.src='/app/electrastore/assets/images/products/placeholder.jpg'">
                            </div>
                        </td>
                        <td class="product-name">
                            <strong><?php echo htmlspecialchars($product['product_name']); ?></strong>
                            <?php if (!empty($product['brand'])): ?>
                                <small><?php echo htmlspecialchars($product['brand']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="product-category">
                            <span class="category-badge"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></span>
                        </td>
                        <td class="product-price">₱<?php echo number_format($product['price'], 2); ?></td>
                        <td class="product-stock">
                            <?php if ($product['stock'] <= 0): ?>
                                <span class="stock-badge out-of-stock">Out of Stock</span>
                            <?php elseif ($product['stock'] < 10): ?>
                                <span class="stock-badge low-stock"><?php echo $product['stock']; ?> units left</span>
                            <?php else: ?>
                                <span class="stock-badge in-stock"><?php echo $product['stock']; ?> units</span>
                            <?php endif; ?>
                        </td>
                        <td class="product-update">
                            <form method="POST" action="" class="update-stock-form">
                                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                <div class="stock-update-wrapper">
                                    <input type="number" name="stock" value="<?php echo $product['stock']; ?>" 
                                           class="stock-input" 
                                           min="0"
                                           required>
                                    <button type="submit" name="update_stock" class="update-btn">
                                        <i class="fas fa-save"></i> Update
                                    </button>
                                </div>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.staff-products {
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

.info-badge {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 0.5rem 1rem;
    background: #fef3c7;
    color: #d97706;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 500;
}

.info-badge i {
    font-size: 1rem;
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
.stats-products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card-product {
    background: white;
    border-radius: 20px;
    padding: 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
}

.stat-card-product:hover {
    transform: translateY(-4px);
}

.stat-icon-product {
    width: 55px;
    height: 55px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
}

.stat-card-product.total .stat-icon-product {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
}

.stat-card-product.low-stock .stat-icon-product {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
}

.stat-card-product.out-stock .stat-icon-product {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

.stat-details-product {
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

/* Products Table */
.products-table-wrapper {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.products-table {
    width: 100%;
    border-collapse: collapse;
}

.products-table th,
.products-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
}

.products-table th {
    background: #f8fafc;
    font-weight: 600;
    color: #1e2a3e;
    font-size: 0.85rem;
}

.products-table tr:hover {
    background: #f8fafc;
}

.product-id {
    font-weight: 600;
    color: #4361ee;
    font-family: monospace;
}

.product-image-cell {
    width: 70px;
}

.product-image-wrapper {
    width: 55px;
    height: 55px;
    border-radius: 10px;
    overflow: hidden;
    background: #f1f5f9;
}

.product-thumbnail {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-name strong {
    display: block;
    color: #1e2a3e;
    font-size: 0.95rem;
    margin-bottom: 0.25rem;
}

.product-name small {
    font-size: 0.7rem;
    color: #64748b;
}

.category-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background: #dbeafe;
    color: #2563eb;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
}

.product-price {
    font-weight: 700;
    color: #4361ee;
    font-size: 0.95rem;
}

.stock-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.stock-badge.in-stock {
    background: #d1fae5;
    color: #059669;
}

.stock-badge.low-stock {
    background: #fef3c7;
    color: #d97706;
}

.stock-badge.out-of-stock {
    background: #fee2e2;
    color: #dc2626;
}

/* Stock Update Form */
.update-stock-form {
    display: inline-block;
}

.stock-update-wrapper {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.stock-input {
    width: 90px;
    padding: 0.5rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    text-align: center;
    font-size: 0.85rem;
}

.stock-input:focus {
    outline: none;
    border-color: #4361ee;
}

.update-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 0.5rem 0.8rem;
    background: #dbeafe;
    color: #2563eb;
    border: none;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.update-btn:hover {
    background: #2563eb;
    color: white;
}

/* Responsive */
@media (max-width: 1024px) {
    .stats-products-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .staff-products {
        padding: 1rem 0;
    }
    
    .header-left h1 {
        font-size: 1.5rem;
    }
    
    .stats-products-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
    }
    
    .stat-card-product {
        padding: 1rem;
    }
    
    .stat-icon-product {
        width: 45px;
        height: 45px;
        font-size: 1rem;
    }
    
    .stat-value {
        font-size: 1.3rem;
    }
    
    .products-table {
        font-size: 0.85rem;
    }
    
    .products-table th,
    .products-table td {
        padding: 0.75rem;
    }
    
    .stock-update-wrapper {
        flex-direction: column;
    }
    
    .page-header {
        flex-direction: column;
        align-items: flex-start;
    }
}

@media (max-width: 480px) {
    .stats-products-grid {
        grid-template-columns: 1fr;
    }
    
    .products-table thead {
        display: none;
    }
    
    .products-table tbody tr {
        display: block;
        margin-bottom: 1rem;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 0.75rem;
    }
    
    .products-table td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
        border: none;
    }
    
    .products-table td::before {
        content: attr(data-label);
        font-weight: 600;
        color: #64748b;
        font-size: 0.75rem;
    }
    
    .product-image-cell {
        width: auto;
    }
    
    .stock-update-wrapper {
        flex-direction: row;
        width: 100%;
    }
    
    .stock-input {
        flex: 1;
    }
}
</style>

<?php
require_once '../includes/footer.php';
?>