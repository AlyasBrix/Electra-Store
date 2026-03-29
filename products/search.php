<?php
require_once '../includes/header.php';

$search = isset($_GET['q']) ? trim($_GET['q']) : '';

$products = [];
if (!empty($search)) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT p.*, c.category_name FROM products p 
                            LEFT JOIN categories c ON p.category_id = c.category_id 
                            WHERE p.product_name LIKE ? OR p.description LIKE ? OR p.brand LIKE ?
                            ORDER BY p.created_at DESC");
    $search_term = "%$search%";
    $stmt->execute([$search_term, $search_term, $search_term]);
    $products = $stmt->fetchAll();
}
?>

<div class="container">
    <div style="margin: 2rem 0;">
        <form method="GET" action="" style="display: flex; gap: 0.5rem;">
            <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="Search for products..." 
                   style="flex: 1; padding: 0.6rem; border: 1px solid #ddd; border-radius: 5px;">
            <button type="submit" class="btn">Search</button>
        </form>
    </div>
    
    <h2>Search Results for "<?php echo htmlspecialchars($search); ?>"</h2>
    
    <?php if (empty($search)): ?>
        <p>Please enter a search term.</p>
    <?php elseif (count($products) == 0): ?>
        <p>No products found matching your search.</p>
    <?php else: ?>
        <div class="products-grid">
            <?php foreach ($products as $product): ?>
            <div class="product-card">
                <img src="/app/electrastore/assets/images/products/<?php echo $product['image_url'] ?: 'placeholder.jpg'; ?>" 
                     alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                     class="product-image">
                <div class="product-info">
                    <h3 class="product-title"><?php echo htmlspecialchars($product['product_name']); ?></h3>
                    <p class="product-price">₱<?php echo number_format($product['price'], 2); ?></p>
                    <p class="product-brand"><?php echo htmlspecialchars($product['brand']); ?></p>
                    <?php if (isLoggedIn()): ?>
                        <button onclick="addToCart(<?php echo $product['product_id']; ?>)" 
                                class="add-to-cart">Add to Cart</button>
                    <?php endif; ?>
                    <a href="details.php?id=<?php echo $product['product_id']; ?>" 
                       style="display: block; text-align: center; margin-top: 0.5rem;">View Details</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
require_once '../includes/footer.php';
?>