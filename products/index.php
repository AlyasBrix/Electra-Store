<?php
require_once '../includes/header.php';

$conn = getConnection();

// Get category filter
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Build query
if ($category_id > 0) {
    $stmt = $conn->prepare("SELECT p.*, c.category_name FROM products p 
                            LEFT JOIN categories c ON p.category_id = c.category_id 
                            WHERE p.category_id = ? 
                            ORDER BY p.created_at DESC");
    $stmt->execute([$category_id]);
} else {
    $stmt = $conn->prepare("SELECT p.*, c.category_name FROM products p 
                            LEFT JOIN categories c ON p.category_id = c.category_id 
                            ORDER BY p.created_at DESC");
    $stmt->execute();
}
$products = $stmt->fetchAll();

// Get categories for sidebar
$cat_stmt = $conn->prepare("SELECT * FROM categories ORDER BY category_name");
$cat_stmt->execute();
$categories = $cat_stmt->fetchAll();
?>

<div class="shop-wrapper">
    <div class="container">
        <!-- Page Header -->
        <div class="shop-header">
            <h1> Collection</h1>
            <p>Discover the latest electronics and gadgets</p>
        </div>
        
        <div class="shop-content">
            <!-- Sidebar with Categories -->
            <aside class="shop-sidebar">
                <div class="category-card">
                    <div class="category-header">
                        <i class="fas fa-th-large"></i>
                        <h3>Categories</h3>
                    </div>
                    <ul class="category-list">
                        <li>
                            <a href="/app/electrastore/products/index.php" class="<?php echo $category_id == 0 ? 'active' : ''; ?>">
                                <i class="fas fa-store"></i>
                                <span>All Products</span>
                                <span class="count"><?php echo count($products); ?></span>
                            </a>
                        </li>
                        <?php foreach ($categories as $cat): 
                            // Count products in this category
                            $count_stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
                            $count_stmt->execute([$cat['category_id']]);
                            $product_count = $count_stmt->fetchColumn();
                        ?>
                        <li>
                            <a href="?category=<?php echo $cat['category_id']; ?>" 
                               class="<?php echo $category_id == $cat['category_id'] ? 'active' : ''; ?>">
                                <i class="fas fa-tag"></i>
                                <span><?php echo htmlspecialchars($cat['category_name']); ?></span>
                                <span class="count"><?php echo $product_count; ?></span>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </aside>
            
            <!-- Products Main Content -->
            <main class="shop-main">
                <!-- Products Header -->
                <div class="products-header">
                    <div class="products-title">
                        <h2>
                            <?php if ($category_id > 0): ?>
                                <?php 
                                    $cat_stmt = $conn->prepare("SELECT category_name FROM categories WHERE category_id = ?");
                                    $cat_stmt->execute([$category_id]);
                                    $current_cat = $cat_stmt->fetch();
                                    echo htmlspecialchars($current_cat['category_name']);
                                ?>
                            <?php else: ?>
                                All Products
                            <?php endif; ?>
                        </h2>
                        <span class="product-count"><?php echo count($products); ?> items</span>
                    </div>
                    
                    <?php if ($category_id > 0): ?>
                        <a href="/app/electrastore/products/index.php" class="clear-filter">
                            <i class="fas fa-times"></i> Clear Filter
                        </a>
                    <?php endif; ?>
                </div>
                
                <?php if (count($products) == 0): ?>
                    <div class="empty-state">
                        <i class="fas fa-box-open"></i>
                        <h3>No products found</h3>
                        <p>Try adjusting your filter or check back later for new items.</p>
                        <a href="/app/electrastore/products/index.php" class="btn btn-primary">View All Products</a>
                    </div>
                <?php else: ?>
                    <div class="products-grid-enhanced">
                        <?php foreach ($products as $product): ?>
                        <div class="product-card-enhanced">
                            <div class="product-image-wrapper">
                                <img src="/app/electrastore/assets/images/products/<?php echo $product['image_url'] ?: 'placeholder.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                                     class="product-image-enhanced">
                                <?php if ($product['stock'] <= 0): ?>
                                    <div class="product-stock-badge out-of-stock">Out of Stock</div>
                                <?php endif; ?>
                                <div class="product-overlay">
                                    <a href="details.php?id=<?php echo $product['product_id']; ?>" class="quick-view">
                                        <i class="fas fa-eye"></i> Quick View
                                    </a>
                                </div>
                            </div>
                            <div class="product-info-enhanced">
                                <div class="product-category">
                                    <i class="fas fa-tag"></i>
                                    <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                                </div>
                                <h3 class="product-title-enhanced">
                                    <a href="details.php?id=<?php echo $product['product_id']; ?>">
                                        <?php echo htmlspecialchars($product['product_name']); ?>
                                    </a>
                                </h3>
                                <div class="product-brand-enhanced">
                                    <i class="fas fa-building"></i>
                                    <?php echo htmlspecialchars($product['brand'] ?: 'Generic'); ?>
                                </div>
                                <div class="product-price-enhanced">
                                    ₱<?php echo number_format($product['price'], 2); ?>
                                </div>
                                <?php if ($product['stock'] > 0): ?>
                                    <?php if (isLoggedIn()): ?>
                                        <button onclick="addToCart(<?php echo $product['product_id']; ?>)" 
                                                class="add-to-cart-enhanced">
                                            <i class="fas fa-shopping-cart"></i> Add to Cart
                                        </button>
                                    <?php else: ?>
                                        <a href="/app/electrastore/user/login.php" class="add-to-cart-enhanced login-to-buy">
                                            <i class="fas fa-lock"></i> Login to Buy
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <button class="add-to-cart-enhanced disabled" disabled>
                                        <i class="fas fa-times-circle"></i> Out of Stock
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
</div>

<style>
.shop-wrapper {
    padding: 2rem 0;
    background: linear-gradient(135deg, #f5f7fa 0%, #f8fafc 100%);
    min-height: 100vh;
}

/* Shop Header */
.shop-header {
    text-align: center;
    margin-bottom: 3rem;
}

.shop-header h1 {
    font-size: 2.5rem;
    font-weight: 800;
    background: linear-gradient(135deg, #4361ee, #7209b7);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    margin-bottom: 0.5rem;
}

.shop-header p {
    color: #64748b;
    font-size: 1rem;
}

/* Shop Layout */
.shop-content {
    display: flex;
    gap: 2rem;
}

/* Sidebar Styles */
.shop-sidebar {
    flex: 0 0 280px;
}

.category-card {
    background: white;
    border-radius: 20px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    position: sticky;
    top: 100px;
}

.category-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #e2e8f0;
}

.category-header i {
    font-size: 1.3rem;
    color: #4361ee;
}

.category-header h3 {
    font-size: 1.2rem;
    font-weight: 600;
    color: #1e2a3e;
    margin: 0;
}

.category-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.category-list li {
    margin-bottom: 0.5rem;
}

.category-list a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0.75rem 1rem;
    text-decoration: none;
    color: #64748b;
    border-radius: 12px;
    transition: all 0.3s ease;
}

.category-list a i {
    font-size: 1rem;
    width: 20px;
}

.category-list a span:first-of-type {
    flex: 1;
}

.category-list a .count {
    font-size: 0.75rem;
    color: #94a3b8;
    background: #f1f5f9;
    padding: 2px 8px;
    border-radius: 20px;
}

.category-list a:hover {
    background: #f8fafc;
    color: #4361ee;
}

.category-list a.active {
    background: linear-gradient(135deg, #4361ee, #7209b7);
    color: white;
}

.category-list a.active .count {
    background: rgba(255, 255, 255, 0.2);
    color: white;
}

/* Main Content */
.shop-main {
    flex: 1;
}

.products-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.products-title {
    display: flex;
    align-items: baseline;
    gap: 1rem;
}

.products-title h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e2a3e;
    margin: 0;
}

.product-count {
    color: #64748b;
    font-size: 0.9rem;
}

.clear-filter {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 0.5rem 1rem;
    background: #f1f5f9;
    color: #64748b;
    text-decoration: none;
    border-radius: 10px;
    font-size: 0.85rem;
    transition: all 0.3s ease;
}

.clear-filter:hover {
    background: #e2e8f0;
    color: #4361ee;
}

/* Enhanced Products Grid */
.products-grid-enhanced {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 2rem;
}

.product-card-enhanced {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    position: relative;
}

.product-card-enhanced:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.product-image-wrapper {
    position: relative;
    overflow: hidden;
    background: #f8fafc;
}

.product-image-enhanced {
    width: 100%;
    height: 240px;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.product-card-enhanced:hover .product-image-enhanced {
    transform: scale(1.1);
}

.product-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(67, 97, 238, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.product-card-enhanced:hover .product-overlay {
    opacity: 1;
}

.quick-view {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 0.75rem 1.5rem;
    background: white;
    color: #4361ee;
    text-decoration: none;
    border-radius: 50px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.quick-view:hover {
    transform: scale(1.05);
    background: #f8fafc;
}

.product-stock-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
    z-index: 1;
}

.product-stock-badge.out-of-stock {
    background: #ef4444;
    color: white;
}

.product-info-enhanced {
    padding: 1.25rem;
}

.product-category {
    font-size: 0.7rem;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 5px;
}

.product-title-enhanced {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.product-title-enhanced a {
    color: #1e2a3e;
    text-decoration: none;
    transition: color 0.3s ease;
}

.product-title-enhanced a:hover {
    color: #4361ee;
}

.product-brand-enhanced {
    font-size: 0.8rem;
    color: #94a3b8;
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 5px;
}

.product-price-enhanced {
    font-size: 1.3rem;
    font-weight: 700;
    color: #4361ee;
    margin-bottom: 1rem;
}

.add-to-cart-enhanced {
    width: 100%;
    padding: 0.75rem;
    background: linear-gradient(135deg, #4361ee, #7209b7);
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-decoration: none;
}

.add-to-cart-enhanced:hover:not(.disabled) {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(67, 97, 238, 0.3);
}

.add-to-cart-enhanced.disabled {
    background: #cbd5e1;
    cursor: not-allowed;
}

.login-to-buy {
    background: #f1f5f9;
    color: #4361ee;
}

.login-to-buy:hover {
    background: #e2e8f0;
    transform: translateY(-2px);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
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
    .shop-content {
        flex-direction: column;
    }
    
    .shop-sidebar {
        flex: auto;
    }
    
    .category-card {
        position: static;
    }
    
    .category-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 0.5rem;
    }
    
    .category-list li {
        margin-bottom: 0;
    }
}

@media (max-width: 768px) {
    .products-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .products-title {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .products-grid-enhanced {
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 1rem;
    }
    
    .shop-header h1 {
        font-size: 1.8rem;
    }
}
</style>

<?php
require_once '../includes/footer.php';
?>