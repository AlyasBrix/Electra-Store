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

<div class="container">
    <div style="display: flex; gap: 2rem;">
        <!-- Sidebar -->
        <aside style="width: 250px;">
            <h3>Categories</h3>
            <ul style="list-style: none; padding: 0;">
                <li style="margin-bottom: 0.5rem;">
                    <a href="/electrastore/products/index.php" style="text-decoration: none; color: <?php echo $category_id == 0 ? '#e67e22' : '#333'; ?>">
                        All Products
                    </a>
                </li>
                <?php foreach ($categories as $cat): ?>
                <li style="margin-bottom: 0.5rem;">
                    <a href="?category=<?php echo $cat['category_id']; ?>" 
                       style="text-decoration: none; color: <?php echo $category_id == $cat['category_id'] ? '#e67e22' : '#333'; ?>">
                        <?php echo htmlspecialchars($cat['category_name']); ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </aside>
        
        <!-- Products -->
        <main style="flex: 1;">
            <h2>Products</h2>
            <?php if (count($products) == 0): ?>
                <p>No products found.</p>
            <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <img src="/electrastore/assets/images/products/<?php echo $product['image_url'] ?: 'placeholder.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                             class="product-image">
                        <div class="product-info">
                            <h3 class="product-title"><?php echo htmlspecialchars($product['product_name']); ?></h3>
                            <p class="product-price">₱<?php echo number_format($product['price'], 2); ?></p>
                            <p class="product-brand"><?php echo htmlspecialchars($product['brand']); ?></p>
                            <?php if ($product['stock'] > 0): ?>
                                <?php if (isLoggedIn()): ?>
                                    <button onclick="addToCart(<?php echo $product['product_id']; ?>)" 
                                            class="add-to-cart">Add to Cart</button>
                                <?php else: ?>
                                    <a href="/electrastore/user/login.php" class="add-to-cart" style="display: block; text-align: center;">Login to Buy</a>
                                <?php endif; ?>
                            <?php else: ?>
                                <button class="add-to-cart" style="background-color: #95a5a6;" disabled>Out of Stock</button>
                            <?php endif; ?>
                            <a href="details.php?id=<?php echo $product['product_id']; ?>" 
                               style="display: block; text-align: center; margin-top: 0.5rem;">View Details</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>