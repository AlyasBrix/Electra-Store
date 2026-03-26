<?php
require_once 'includes/header.php';
?>

<section class="hero" style="background-image: url('/electrastore/assets/images/hero-bg.jpg'); background-size: cover; background-position: center;">
    <div class="container" style="background-color: rgba(0,0,0,0.6); padding: 4rem; border-radius: 10px;">
        <h1>Welcome to ElectraStore</h1>
        <p>Your premier destination for electronic gadgets and appliances in Cagayan de Oro City</p>
        <a href="/electrastore/products/index.php" class="btn btn-primary">Shop Now</a>
    </div>
</section>

<section class="featured-products">
    <div class="container">
        <h2>Featured Products</h2>
        <div class="products-grid">
            <?php
            $conn = getConnection();
            $stmt = $conn->prepare("SELECT * FROM products LIMIT 6");
            $stmt->execute();
            $products = $stmt->fetchAll();
            
            foreach ($products as $product):
            ?>
            <div class="product-card">
                <img src="/electrastore/assets/images/products/<?php echo $product['image_url'] ?: 'placeholder.jpg'; ?>" 
                     alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                     class="product-image">
                <div class="product-info">
                    <h3 class="product-title"><?php echo htmlspecialchars($product['product_name']); ?></h3>
                    <p class="product-price">₱<?php echo number_format($product['price'], 2); ?></p>
                    <p class="product-brand"><?php echo htmlspecialchars($product['brand']); ?></p>
                    <?php if (isLoggedIn()): ?>
                        <button onclick="addToCart(<?php echo $product['product_id']; ?>)" 
                                class="add-to-cart">Add to Cart</button>
                    <?php else: ?>
                        <a href="/electrastore/user/login.php" class="add-to-cart" style="display: block; text-align: center;">Login to Buy</a>
                    <?php endif; ?>
                    <a href="/electrastore/products/details.php?id=<?php echo $product['product_id']; ?>" 
                       style="display: block; text-align: center; margin-top: 0.5rem;">View Details</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php
require_once 'includes/footer.php';
?>