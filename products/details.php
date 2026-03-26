<?php
require_once '../includes/header.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header('Location: index.php');
    exit();
}

$conn = getConnection();
$stmt = $conn->prepare("SELECT p.*, c.category_name FROM products p 
                        LEFT JOIN categories c ON p.category_id = c.category_id 
                        WHERE p.product_id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: index.php');
    exit();
}
?>

<div class="container">
    <div style="display: flex; gap: 2rem; margin: 2rem 0;">
        <div style="flex: 1;">
            <img src="/electrastore/assets/images/products/<?php echo $product['image_url'] ?: 'placeholder.jpg'; ?>" 
                 alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                 style="width: 100%; max-width: 400px; border-radius: 10px;">
        </div>
        <div style="flex: 2;">
            <h1><?php echo htmlspecialchars($product['product_name']); ?></h1>
            <p style="color: #7f8c8d;">Category: <?php echo htmlspecialchars($product['category_name']); ?></p>
            <p style="color: #7f8c8d;">Brand: <?php echo htmlspecialchars($product['brand']); ?></p>
            <p style="color: #7f8c8d;">Warranty: <?php echo htmlspecialchars($product['warranty']); ?></p>
            <p style="font-size: 1.5rem; color: #e67e22; font-weight: bold; margin: 1rem 0;">
                ₱<?php echo number_format($product['price'], 2); ?>
            </p>
            <p style="color: <?php echo $product['stock'] > 0 ? '#27ae60' : '#e74c3c'; ?>;">
                Stock: <?php echo $product['stock']; ?> units available
            </p>
            <p style="margin: 1rem 0;">
                <?php echo nl2br(htmlspecialchars($product['description'])); ?>
            </p>
            
            <?php if ($product['stock'] > 0): ?>
                <?php if (isLoggedIn()): ?>
                    <button onclick="addToCart(<?php echo $product['product_id']; ?>)" 
                            class="btn btn-primary" style="padding: 0.8rem 2rem;">Add to Cart</button>
                <?php else: ?>
                    <a href="/electrastore/user/login.php" class="btn btn-primary">Login to Buy</a>
                <?php endif; ?>
            <?php else: ?>
                <button class="btn" style="background-color: #95a5a6;" disabled>Out of Stock</button>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>