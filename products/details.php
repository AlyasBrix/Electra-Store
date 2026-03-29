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

// Get related products from same category
$related_stmt = $conn->prepare("SELECT * FROM products WHERE category_id = ? AND product_id != ? LIMIT 4");
$related_stmt->execute([$product['category_id'], $product_id]);
$related_products = $related_stmt->fetchAll();
?>

<div class="product-detail-wrapper">
    <div class="container">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="/app/electrastore/index.php">Home</a>
            <i class="fas fa-chevron-right"></i>
            <a href="/app/electrastore/products/index.php">Products</a>
            <i class="fas fa-chevron-right"></i>
            <a href="/app/electrastore/products/index.php?category=<?php echo $product['category_id']; ?>"><?php echo htmlspecialchars($product['category_name']); ?></a>
            <i class="fas fa-chevron-right"></i>
            <span><?php echo htmlspecialchars($product['product_name']); ?></span>
        </div>
        
        <!-- Product Main Section -->
        <div class="product-main">
            <!-- Product Gallery -->
            <div class="product-gallery">
                <div class="main-image">
                    <img src="/app/electrastore/assets/images/products/<?php echo $product['image_url'] ?: 'placeholder.jpg'; ?>" 
                         alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                         id="mainProductImage">
                </div>
                <?php if ($product['image_url']): ?>
                <div class="thumbnail-list">
                    <div class="thumbnail active">
                        <img src="/app/electrastore/assets/images/products/<?php echo $product['image_url']; ?>" 
                             alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                             onclick="changeImage(this.src)">
                    </div>
                    <!-- Add more thumbnails if you have multiple images -->
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Product Info -->
            <div class="product-info-detail">
                <div class="product-category-badge">
                    <?php echo htmlspecialchars($product['category_name']); ?>
                </div>
                <h1 class="product-title-detail"><?php echo htmlspecialchars($product['product_name']); ?></h1>
                
                <div class="product-meta">
                    <div class="meta-item">
                        <i class="fas fa-tag"></i>
                        <span>Brand: <strong><?php echo htmlspecialchars($product['brand'] ?: 'Generic'); ?></strong></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-shield-alt"></i>
                        <span>Warranty: <strong><?php echo htmlspecialchars($product['warranty'] ?: '1 Year'); ?></strong></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-box"></i>
                        <span>SKU: <strong>#<?php echo str_pad($product['product_id'], 6, '0', STR_PAD_LEFT); ?></strong></span>
                    </div>
                </div>
                
                <div class="product-price-detail">
                    <span class="current-price">₱<?php echo number_format($product['price'], 2); ?></span>
                    <?php if ($product['stock'] > 0): ?>
                        <span class="stock-badge in-stock">
                            <i class="fas fa-check-circle"></i> In Stock
                        </span>
                    <?php else: ?>
                        <span class="stock-badge out-of-stock-badge">
                            <i class="fas fa-times-circle"></i> Out of Stock
                        </span>
                    <?php endif; ?>
                </div>
                
                <div class="stock-info">
                    <i class="fas fa-<?php echo $product['stock'] > 0 ? 'check-circle' : 'times-circle'; ?>"></i>
                    <span class="stock-count"><?php echo $product['stock']; ?> units available</span>
                    <?php if ($product['stock'] > 0 && $product['stock'] < 10): ?>
                        <span class="stock-warning-detail">(Only <?php echo $product['stock']; ?> left - order soon!)</span>
                    <?php endif; ?>
                </div>
                
                <div class="product-description">
                    <h3>Product Description</h3>
                    <p><?php echo nl2br(htmlspecialchars($product['description'] ?: 'No description available.')); ?></p>
                </div>
                
                <div class="product-actions-detail">
                    <?php if ($product['stock'] > 0): ?>
                        <?php if (isLoggedIn()): ?>
                            <div class="quantity-selector">
                                <label>Quantity:</label>
                                <div class="quantity-control-detail">
                                    <button class="qty-btn" onclick="decrementQty()">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" id="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>">
                                    <button class="qty-btn" onclick="incrementQty(<?php echo $product['stock']; ?>)">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <button onclick="addToCartWithQuantity(<?php echo $product['product_id']; ?>)" 
                                    class="btn-add-to-cart-detail">
                                <i class="fas fa-shopping-cart"></i> Add to Cart
                            </button>
                        <?php else: ?>
                            <a href="/app/electrastore/user/login.php" class="btn-add-to-cart-detail login-required">
                                <i class="fas fa-lock"></i> Login to Purchase
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <button class="btn-add-to-cart-detail disabled" disabled>
                            <i class="fas fa-times-circle"></i> Out of Stock
                        </button>
                    <?php endif; ?>
                </div>
                
                <div class="shipping-info-detail">
                    <div class="info-item">
                        <i class="fas fa-truck"></i>
                        <div>
                            <strong>Free Shipping</strong>
                            <p>On orders over ₱2,500</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-undo-alt"></i>
                        <div>
                            <strong>Easy Returns</strong>
                            <p>7-day return policy</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-headset"></i>
                        <div>
                            <strong>24/7 Support</strong>
                            <p>Customer service available</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Related Products Section -->
        <?php if (count($related_products) > 0): ?>
        <div class="related-products">
            <h3 class="related-title">You May Also Like</h3>
            <div class="related-grid">
                <?php foreach ($related_products as $related): ?>
                <div class="related-card">
                    <a href="details.php?id=<?php echo $related['product_id']; ?>">
                        <img src="/app/electrastore/assets/images/products/<?php echo $related['image_url'] ?: 'placeholder.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($related['product_name']); ?>">
                        <h4><?php echo htmlspecialchars($related['product_name']); ?></h4>
                        <p class="related-price">₱<?php echo number_format($related['price'], 2); ?></p>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.product-detail-wrapper {
    padding: 2rem 0;
    background: linear-gradient(135deg, #f5f7fa 0%, #f8fafc 100%);
    min-height: 100vh;
}

/* Breadcrumb */
.breadcrumb {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 2rem;
    font-size: 0.85rem;
    color: #64748b;
    flex-wrap: wrap;
}

.breadcrumb a {
    color: #4361ee;
    text-decoration: none;
    transition: color 0.3s ease;
}

.breadcrumb a:hover {
    color: #7209b7;
}

.breadcrumb i {
    font-size: 0.7rem;
    color: #94a3b8;
}

.breadcrumb span {
    color: #1e2a3e;
}

/* Product Main */
.product-main {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
    background: white;
    border-radius: 24px;
    padding: 2rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    margin-bottom: 3rem;
}

/* Product Gallery */
.product-gallery {
    position: sticky;
    top: 100px;
}

.main-image {
    width: 100%;
    aspect-ratio: 1 / 1;
    background: #f8fafc;
    border-radius: 20px;
    overflow: hidden;
    margin-bottom: 1rem;
    border: 1px solid #e2e8f0;
}

.main-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.thumbnail-list {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
}

.thumbnail {
    width: 80px;
    height: 80px;
    border-radius: 12px;
    overflow: hidden;
    cursor: pointer;
    border: 2px solid transparent;
    transition: all 0.3s ease;
}

.thumbnail.active {
    border-color: #4361ee;
}

.thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.thumbnail:hover:not(.active) {
    border-color: #94a3b8;
}

/* Product Info */
.product-info-detail {
    padding: 0;
}

.product-category-badge {
    display: inline-block;
    background: linear-gradient(135deg, #4361ee, #7209b7);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.product-title-detail {
    font-size: 2rem;
    font-weight: 800;
    color: #1e2a3e;
    margin-bottom: 1rem;
    line-height: 1.3;
}

.product-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    color: #64748b;
}

.meta-item i {
    color: #4361ee;
    width: 18px;
}

.meta-item strong {
    color: #1e2a3e;
}

.product-price-detail {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.current-price {
    font-size: 2rem;
    font-weight: 800;
    color: #4361ee;
}

.stock-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-size: 0.8rem;
    font-weight: 600;
}

.stock-badge.in-stock {
    background: #d1fae5;
    color: #059669;
}

.stock-badge.out-of-stock-badge {
    background: #fee2e2;
    color: #dc2626;
}

.stock-info {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 1.5rem;
    padding: 0.75rem;
    background: #f8fafc;
    border-radius: 12px;
}

.stock-info i {
    font-size: 1rem;
    color: #059669;
}

.stock-count {
    font-weight: 600;
    color: #1e2a3e;
}

.stock-warning-detail {
    color: #f97316;
    font-size: 0.8rem;
}

.product-description {
    margin-bottom: 2rem;
}

.product-description h3 {
    font-size: 1rem;
    font-weight: 600;
    color: #1e2a3e;
    margin-bottom: 0.75rem;
}

.product-description p {
    color: #64748b;
    line-height: 1.6;
}

/* Product Actions */
.product-actions-detail {
    margin-bottom: 2rem;
}

.quantity-selector {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.quantity-selector label {
    font-weight: 600;
    color: #1e2a3e;
}

.quantity-control-detail {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.qty-btn {
    width: 36px;
    height: 36px;
    border: 1px solid #e2e8f0;
    background: white;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.qty-btn:hover {
    background: #f1f5f9;
    border-color: #4361ee;
}

#quantity {
    width: 60px;
    height: 36px;
    text-align: center;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    font-size: 1rem;
}

#quantity:focus {
    outline: none;
    border-color: #4361ee;
}

.btn-add-to-cart-detail {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    padding: 1rem;
    background: linear-gradient(135deg, #4361ee, #7209b7);
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
}

.btn-add-to-cart-detail:hover:not(.disabled) {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(67, 97, 238, 0.3);
}

.btn-add-to-cart-detail.disabled {
    background: #cbd5e1;
    cursor: not-allowed;
}

.login-required {
    background: #f1f5f9;
    color: #4361ee;
}

.login-required:hover {
    background: #e2e8f0;
    transform: translateY(-2px);
}

/* Shipping Info */
.shipping-info-detail {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e2e8f0;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 10px;
}

.info-item i {
    font-size: 1.2rem;
    color: #4361ee;
}

.info-item div strong {
    display: block;
    font-size: 0.8rem;
    color: #1e2a3e;
}

.info-item div p {
    font-size: 0.7rem;
    color: #64748b;
    margin: 0;
}

/* Related Products */
.related-products {
    margin-top: 2rem;
}

.related-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: #1e2a3e;
    margin-bottom: 1.5rem;
    position: relative;
    padding-bottom: 0.5rem;
}

.related-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 50px;
    height: 3px;
    background: linear-gradient(135deg, #4361ee, #7209b7);
    border-radius: 3px;
}

.related-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1.5rem;
}

.related-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.related-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}

.related-card a {
    text-decoration: none;
}

.related-card img {
    width: 100%;
    height: 180px;
    object-fit: cover;
}

.related-card h4 {
    font-size: 0.9rem;
    font-weight: 600;
    color: #1e2a3e;
    margin: 0.75rem;
    line-height: 1.4;
}

.related-price {
    font-size: 1rem;
    font-weight: 700;
    color: #4361ee;
    margin: 0 0.75rem 0.75rem;
}

/* Responsive */
@media (max-width: 1024px) {
    .product-main {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .product-gallery {
        position: static;
        max-width: 500px;
        margin: 0 auto;
    }
    
    .shipping-info-detail {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .product-detail-wrapper {
        padding: 1rem;
    }
    
    .product-main {
        padding: 1.5rem;
    }
    
    .product-title-detail {
        font-size: 1.5rem;
    }
    
    .current-price {
        font-size: 1.5rem;
    }
    
    .product-meta {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .shipping-info-detail {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }
    
    .related-grid {
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 1rem;
    }
    
    .related-card img {
        height: 140px;
    }
}

@media (max-width: 480px) {
    .breadcrumb {
        font-size: 0.7rem;
    }
    
    .product-title-detail {
        font-size: 1.2rem;
    }
    
    .quantity-selector {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .related-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<script>
// Quantity controls
function incrementQty(maxStock) {
    const qtyInput = document.getElementById('quantity');
    let currentValue = parseInt(qtyInput.value);
    if (currentValue < maxStock) {
        qtyInput.value = currentValue + 1;
    }
}

function decrementQty() {
    const qtyInput = document.getElementById('quantity');
    let currentValue = parseInt(qtyInput.value);
    if (currentValue > 1) {
        qtyInput.value = currentValue - 1;
    }
}

// Add to cart with quantity
function addToCartWithQuantity(productId) {
    const quantity = document.getElementById('quantity').value;
    
    fetch('/app/electrastore/cart/add.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'product_id=' + productId + '&quantity=' + quantity
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart badge
            const cartBadge = document.querySelector('.cart-badge');
            if (cartBadge) {
                cartBadge.textContent = data.cart_count;
                cartBadge.style.transform = 'scale(1.2)';
                setTimeout(() => {
                    cartBadge.style.transform = 'scale(1)';
                }, 300);
            } else if (data.cart_count > 0) {
                const cartLink = document.querySelector('.cart-link');
                if (cartLink) {
                    const badge = document.createElement('span');
                    badge.className = 'cart-badge';
                    badge.textContent = data.cart_count;
                    cartLink.appendChild(badge);
                }
            }
            
            showMessage('Product added to cart successfully!', 'success');
        } else {
            showMessage(data.message || 'Error adding to cart', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Error adding to cart', 'error');
    });
}

// Change main image
function changeImage(src) {
    document.getElementById('mainProductImage').src = src;
    
    // Update active thumbnail
    const thumbnails = document.querySelectorAll('.thumbnail');
    thumbnails.forEach(thumb => {
        thumb.classList.remove('active');
        if (thumb.querySelector('img').src === src) {
            thumb.classList.add('active');
        }
    });
}

// Show message function
function showMessage(message, type) {
    const messageDiv = document.createElement('div');
    messageDiv.className = type === 'success' ? 'success-message' : 'error-message';
    messageDiv.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i> ${message}`;
    
    const container = document.querySelector('.container');
    if (container) {
        container.insertBefore(messageDiv, container.firstChild);
        setTimeout(() => {
            messageDiv.remove();
        }, 3000);
    }
}
</script>

<?php
require_once '../includes/footer.php';
?>