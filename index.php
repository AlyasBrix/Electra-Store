<?php
require_once 'includes/header.php';

$conn = getConnection();
$stmt = $conn->prepare("SELECT * FROM products ORDER BY created_at DESC LIMIT 6");
$stmt->execute();
$products = $stmt->fetchAll();
?>

<!-- Hero Section -->
<section class="hero-modern">
    <div class="hero-background"></div>
    <div class="container">
        <div class="hero-content-modern">
            <div class="hero-badge">
                <i class="fas fa-bolt"></i> Welcome to ElectraStore
            </div>
            <h1 class="hero-title">
                Your Premier Destination for<br>
                <span class="gradient-text">Electronic Gadgets</span>
            </h1>
            <p class="hero-description">
                Discover the latest electronics and appliances in Cagayan de Oro City. 
                Quality products, competitive prices, and excellent service.
            </p>
            <div class="hero-buttons">
                <a href="/app/electrastore/products/index.php" class="btn-hero-primary">
                    <i class="fas fa-shopping-bag"></i> Shop Now!
                </a>
            </div>
            <div class="hero-stats">
                <div class="stat">
                    <span class="stat-number">500+</span>
                    <span class="stat-label">Products</span>
                </div>
                <div class="stat">
                    <span class="stat-number">50+</span>
                    <span class="stat-label">Brands</span>
                </div>
                <div class="stat">
                    <span class="stat-number">1000+</span>
                    <span class="stat-label">Happy Customers</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section">
    <div class="container">
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-truck"></i>
                </div>
                <h3>Free Shipping</h3>
                <p>On orders over ₱2,500</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3>Secure Payment</h3>
                <p>100% secure transactions</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-undo-alt"></i>
                </div>
                <h3>7-Day Returns</h3>
                <p>Easy return policy</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h3>24/7 Support</h3>
                <p>Customer service always ready</p>
            </div>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<section class="featured-section">
    <div class="container">
        <div class="section-header">
            <div class="section-tag">Featured Collection</div>
            <h2 class="section-title">Popular Products</h2>
            <p class="section-description">Discover our most loved electronics and gadgets</p>
        </div>
        
        <div class="products-grid-modern">
            <?php foreach ($products as $product): ?>
            <div class="product-card-modern">
                <div class="product-image-modern">
                    <img src="/app/electrastore/assets/images/products/<?php echo $product['image_url'] ?: 'placeholder.jpg'; ?>" 
                         alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                    <div class="product-actions">
                        <a href="/app/electrastore/products/details.php?id=<?php echo $product['product_id']; ?>" class="action-btn quick-view-btn">
                            <i class="fas fa-eye"></i>
                        </a>
                        <?php if (isLoggedIn() && $product['stock'] > 0): ?>
                            <button onclick="addToCart(<?php echo $product['product_id']; ?>)" class="action-btn add-to-cart-btn">
                                <i class="fas fa-shopping-cart"></i>
                            </button>
                        <?php elseif (!isLoggedIn()): ?>
                            <a href="/app/electrastore/user/login.php" class="action-btn login-btn">
                                <i class="fas fa-lock"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="product-info-modern">
                    <div class="product-category"><?php echo htmlspecialchars($product['brand'] ?: 'Generic'); ?></div>
                    <h3 class="product-title-modern">
                        <a href="/app/electrastore/products/details.php?id=<?php echo $product['product_id']; ?>">
                            <?php echo htmlspecialchars($product['product_name']); ?>
                        </a>
                    </h3>
                    <div class="product-price-modern">
                        <span class="current-price">₱<?php echo number_format($product['price'], 2); ?></span>
                    </div>
                    <?php if ($product['stock'] > 0): ?>
                        <?php if (isLoggedIn()): ?>
                            <button onclick="addToCart(<?php echo $product['product_id']; ?>)" class="add-to-cart-modern">
                                <i class="fas fa-shopping-cart"></i> Add to Cart
                            </button>
                        <?php else: ?>
                            <a href="/app/electrastore/user/login.php" class="add-to-cart-modern login-to-buy">
                                <i class="fas fa-lock"></i> Login to Buy
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <button class="add-to-cart-modern disabled" disabled>
                            <i class="fas fa-times-circle"></i> Out of Stock
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="view-all-container">
            <a href="/app/electrastore/products/index.php" class="btn-view-all">
                View All Products <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<style>
/* Hero Section Modern */
.hero-modern {
    position: relative;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 5rem 0;
    overflow: hidden;
}

.hero-background {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image: url('/app/electrastore/assets/images/hero-bg.jpg');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    opacity: 0.2;
    filter: brightness(0.8);
}

.hero-content-modern {
    position: relative;
    z-index: 2;
    text-align: center;
    max-width: 800px;
    margin: 0 auto;
}

.hero-badge {
    display: inline-block;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-size: 0.85rem;
    margin-bottom: 1.5rem;
    color: white;
}

.hero-badge i {
    margin-right: 5px;
}

.hero-title {
    font-size: 3rem;
    font-weight: 800;
    color: white;
    margin-bottom: 1rem;
    line-height: 1.2;
}

.gradient-text {
    background: linear-gradient(135deg, #fff, #e0e7ff);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}

.hero-description {
    font-size: 1rem;
    color: rgba(255, 255, 255, 0.9);
    margin-bottom: 2rem;
    line-height: 1.6;
}

.hero-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-bottom: 3rem;
}

.btn-hero-primary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 1rem 2rem;
    background: white;
    color: #4361ee;
    text-decoration: none;
    border-radius: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-hero-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
}

.hero-stats {
    display: flex;
    justify-content: center;
    gap: 3rem;
}

.stat {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 1.5rem;
    font-weight: 800;
    color: white;
}

.stat-label {
    font-size: 0.8rem;
    color: rgba(255, 255, 255, 0.7);
}

/* Features Section */
.features-section {
    padding: 3rem 0;
    background: white;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
}

.feature-card {
    text-align: center;
    padding: 1.5rem;
}

.feature-icon {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, #f5f7fa, #ffffff);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.feature-icon i {
    font-size: 1.8rem;
    color: #4361ee;
}

.feature-card h3 {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #1e2a3e;
}

.feature-card p {
    font-size: 0.85rem;
    color: #64748b;
}

/* Featured Section */
.featured-section {
    padding: 4rem 0;
    background: linear-gradient(135deg, #f5f7fa 0%, #f8fafc 100%);
}

.section-header {
    text-align: center;
    margin-bottom: 3rem;
}

.section-tag {
    display: inline-block;
    background: linear-gradient(135deg, #4361ee, #7209b7);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.section-title {
    font-size: 2rem;
    font-weight: 800;
    color: #1e2a3e;
    margin-bottom: 0.5rem;
}

.section-description {
    color: #64748b;
    font-size: 0.9rem;
}

/* Products Grid Modern */
.products-grid-modern {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 2rem;
}

.product-card-modern {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.product-card-modern:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.product-image-modern {
    position: relative;
    overflow: hidden;
    aspect-ratio: 1 / 1;
}

.product-image-modern img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.product-card-modern:hover .product-image-modern img {
    transform: scale(1.1);
}

.product-actions {
    position: absolute;
    bottom: -50px;
    left: 0;
    right: 0;
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    padding: 1rem;
    background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);
    transition: bottom 0.3s ease;
}

.product-card-modern:hover .product-actions {
    bottom: 0;
}

.action-btn {
    width: 40px;
    height: 40px;
    background: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    color: #1e2a3e;
    transition: all 0.3s ease;
    cursor: pointer;
    border: none;
}

.action-btn:hover {
    background: #4361ee;
    color: white;
    transform: scale(1.1);
}

.product-info-modern {
    padding: 1.5rem;
}

.product-category {
    font-size: 0.7rem;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.5rem;
}

.product-title-modern {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.product-title-modern a {
    color: #1e2a3e;
    text-decoration: none;
    transition: color 0.3s ease;
}

.product-title-modern a:hover {
    color: #4361ee;
}

.product-price-modern {
    margin-bottom: 1rem;
}

.current-price {
    font-size: 1.2rem;
    font-weight: 700;
    color: #4361ee;
}

.add-to-cart-modern {
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

.add-to-cart-modern:hover:not(.disabled) {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(67, 97, 238, 0.3);
}

.add-to-cart-modern.disabled {
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

.view-all-container {
    text-align: center;
    margin-top: 3rem;
}

.btn-view-all {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 0.875rem 2rem;
    background: white;
    color: #4361ee;
    text-decoration: none;
    border-radius: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.btn-view-all:hover {
    background: #4361ee;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(67, 97, 238, 0.3);
}

/* Newsletter Section */
.newsletter-section {
    padding: 4rem 0;
    background: linear-gradient(135deg, #1e2a3e, #0f172a);
}

.newsletter-content {
    text-align: center;
    max-width: 600px;
    margin: 0 auto;
}

.newsletter-text i {
    font-size: 2rem;
    color: #4361ee;
    margin-bottom: 1rem;
}

.newsletter-text h3 {
    font-size: 1.3rem;
    color: white;
    margin-bottom: 0.5rem;
}

.newsletter-text p {
    color: #94a3b8;
    margin-bottom: 1.5rem;
}

.newsletter-form {
    display: flex;
    gap: 1rem;
}

.newsletter-form input {
    flex: 1;
    padding: 0.875rem;
    border: none;
    border-radius: 12px;
    font-size: 0.9rem;
}

.newsletter-form input:focus {
    outline: none;
}

.newsletter-form button {
    padding: 0.875rem 1.5rem;
    background: linear-gradient(135deg, #4361ee, #7209b7);
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.newsletter-form button:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(67, 97, 238, 0.3);
}

/* Responsive */
@media (max-width: 768px) {
    .hero-title {
        font-size: 2rem;
    }
    
    .hero-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .hero-stats {
        gap: 1.5rem;
        flex-wrap: wrap;
    }
    
    .features-grid {
        gap: 1rem;
    }
    
    .products-grid-modern {
        gap: 1rem;
    }
    
    .section-title {
        font-size: 1.5rem;
    }
    
    .newsletter-form {
        flex-direction: column;
    }
    
    .hero-stats {
        display: none;
    }
}

@media (max-width: 480px) {
    .hero-title {
        font-size: 1.5rem;
    }
    
    .hero-description {
        font-size: 0.9rem;
    }
    
    .btn-hero-primary {
        padding: 0.75rem 1.5rem;
        font-size: 0.9rem;
    }
}
</style>

<?php
require_once 'includes/footer.php';
?>