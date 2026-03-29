<?php
require_once '../includes/header.php';
requireAdmin();

$conn = getConnection();

// Get filter parameters
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$stock_filter = isset($_GET['stock']) ? $_GET['stock'] : '';

// Build query
$query = "SELECT p.*, c.category_name FROM products p 
          LEFT JOIN categories c ON p.category_id = c.category_id 
          WHERE 1=1";
$params = [];

if ($category_filter > 0) {
    $query .= " AND p.category_id = ?";
    $params[] = $category_filter;
}

if ($stock_filter == 'low') {
    $query .= " AND p.stock < 10 AND p.stock > 0";
} elseif ($stock_filter == 'out') {
    $query .= " AND p.stock = 0";
}

$query .= " ORDER BY p.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories for filter
$cat_stmt = $conn->query("SELECT * FROM categories ORDER BY category_name");
$categories = $cat_stmt->fetchAll();

// Get statistics
$total_products = count($products);
$low_stock_count = 0;
$out_of_stock_count = 0;

foreach ($products as $product) {
    if ($product['stock'] < 10 && $product['stock'] > 0) $low_stock_count++;
    if ($product['stock'] == 0) $out_of_stock_count++;
}
?>

<div class="products-admin">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-left">
                <h1>Manage Products</h1>
                <p>View, edit, and manage your product inventory</p>
            </div>
            <div class="header-right">
                <a href="add_product.php" class="btn-add-product">
                    <i class="fas fa-plus"></i> Add New Product
                </a>
            </div>
        </div>

        <!-- Admin Navigation -->
        <div class="admin-nav">
            <a href="/app/electrastore/admin/index.php" class="nav-link">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="/app/electrastore/admin/products.php" class="nav-link active">
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
                    <span class="stat-value"><?php echo $out_of_stock_count; ?></span>
                    <span class="stat-label">Out of Stock</span>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-section">
            <div class="filter-group">
                <label>Filter by Category:</label>
                <select id="categoryFilter" onchange="applyFilters()" class="filter-select">
                    <option value="0">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['category_id']; ?>" <?php echo $category_filter == $cat['category_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['category_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Filter by Stock:</label>
                <select id="stockFilter" onchange="applyFilters()" class="filter-select">
                    <option value="">All Stock</option>
                    <option value="low" <?php echo $stock_filter == 'low' ? 'selected' : ''; ?>>Low Stock (&lt;10)</option>
                    <option value="out" <?php echo $stock_filter == 'out' ? 'selected' : ''; ?>>Out of Stock</option>
                </select>
            </div>
            <div class="filter-actions">
                <?php if ($category_filter > 0 || $stock_filter != ''): ?>
                    <a href="products.php" class="clear-filters">
                        <i class="fas fa-times"></i> Clear Filters
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Products Table -->
        <?php if (count($products) == 0): ?>
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <h3>No Products Found</h3>
                <p>Click "Add New Product" to get started with your inventory.</p>
                <a href="add_product.php" class="btn btn-primary">Create First Product</a>
            </div>
        <?php else: ?>
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
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td class="product-id">#<?php echo str_pad($product['product_id'], 5, '0', STR_PAD_LEFT); ?></td>
                            <td class="product-image-cell">
                                <div class="product-image-wrapper">
                                    <img src="/app/electrastore/assets/images/products/<?php echo htmlspecialchars($product['image_url'] ?: 'placeholder.jpg'); ?>" 
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
                                    <span class="stock-badge low-stock"><?php echo $product['stock']; ?> left</span>
                                <?php else: ?>
                                    <span class="stock-badge in-stock"><?php echo $product['stock']; ?> units</span>
                                <?php endif; ?>
                            </td>
                            <td class="product-actions">
                                <a href="edit_product.php?id=<?php echo $product['product_id']; ?>" class="action-btn edit-product" title="Edit Product">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="delete_product.php?id=<?php echo $product['product_id']; ?>" 
                                   class="action-btn delete-product" 
                                   onclick="return confirm('Are you sure you want to delete this product? This action cannot be undone.')"
                                   title="Delete Product">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                                <a href="/app/electrastore/products/details.php?id=<?php echo $product['product_id']; ?>" 
                                   class="action-btn view-product" 
                                   target="_blank"
                                   title="View Product">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.products-admin {
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

.btn-add-product {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, #4361ee, #7209b7);
    color: white;
    text-decoration: none;
    border-radius: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-add-product:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(67, 97, 238, 0.3);
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

.admin-nav .nav-link.active {
    background: linear-gradient(135deg, #4361ee, #7209b7);
    color: white;
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

/* Statistics Cards */
.stats-products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card-product {
    background: white;
    border-radius: 16px;
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

/* Filters Section */
.filters-section {
    background: white;
    border-radius: 16px;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
    display: flex;
    gap: 1.5rem;
    align-items: flex-end;
    flex-wrap: wrap;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-group label {
    font-size: 0.8rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.filter-select {
    padding: 0.6rem 1rem;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    font-size: 0.9rem;
    background: white;
    cursor: pointer;
    min-width: 180px;
}

.filter-select:focus {
    outline: none;
    border-color: #4361ee;
}

.clear-filters {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 0.6rem 1rem;
    background: #f1f5f9;
    color: #64748b;
    text-decoration: none;
    border-radius: 10px;
    font-size: 0.85rem;
    transition: all 0.3s ease;
}

.clear-filters:hover {
    background: #e2e8f0;
    color: #4361ee;
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

.product-actions {
    display: flex;
    gap: 0.5rem;
}

.action-btn {
    width: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.3s ease;
}

.edit-product {
    background: #dbeafe;
    color: #2563eb;
}

.edit-product:hover {
    background: #2563eb;
    color: white;
    transform: translateY(-2px);
}

.delete-product {
    background: #fee2e2;
    color: #dc2626;
}

.delete-product:hover {
    background: #dc2626;
    color: white;
    transform: translateY(-2px);
}

.view-product {
    background: #f1f5f9;
    color: #64748b;
}

.view-product:hover {
    background: #4361ee;
    color: white;
    transform: translateY(-2px);
}

/* Empty State */
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
    margin-bottom: 1.5rem;
}

/* Responsive */
@media (max-width: 1024px) {
    .products-table {
        font-size: 0.85rem;
    }
    
    .products-table th,
    .products-table td {
        padding: 0.75rem;
    }
}

@media (max-width: 768px) {
    .products-admin {
        padding: 1rem 0;
    }
    
    .header-left h1 {
        font-size: 1.5rem;
    }
    
    .stats-products-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .filters-section {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-select {
        width: 100%;
    }
    
    .filter-actions {
        text-align: right;
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
    
    .product-actions {
        justify-content: flex-end;
    }
}

@media (max-width: 480px) {
    .stats-products-grid {
        grid-template-columns: 1fr;
    }
    
    .stat-card-product {
        padding: 1rem;
    }
    
    .stat-value {
        font-size: 1.3rem;
    }
}
</style>

<script>
function applyFilters() {
    const category = document.getElementById('categoryFilter').value;
    const stock = document.getElementById('stockFilter').value;
    
    let url = 'products.php';
    let params = [];
    
    if (category && category != '0') {
        params.push('category=' + category);
    }
    if (stock) {
        params.push('stock=' + stock);
    }
    
    if (params.length > 0) {
        url += '?' + params.join('&');
    }
    
    window.location.href = url;
}
</script>

<?php
require_once '../includes/footer.php';
?>