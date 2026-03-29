<?php
require_once '../includes/header.php';

if (!isLoggedIn()) {
    header('Location: /app/electrastore/user/login.php');
    exit();
}

$conn = getConnection();
$cart_id = getUserCartId($_SESSION['user_id']);

// Get cart items
$stmt = $conn->prepare("SELECT ci.*, p.product_name, p.price, p.image_url, p.stock
                        FROM cart_items ci 
                        JOIN products p ON ci.product_id = p.product_id 
                        WHERE ci.cart_id = ?");
$stmt->execute([$cart_id]);
$cart_items = $stmt->fetchAll();

// Calculate total
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>

<div class="cart-wrapper">
    <div class="container">
        <!-- Page Header -->
        <div class="cart-header">
            <h1>Shopping Cart</h1>
            <p>Review your items before checkout</p>
        </div>
        
        <?php if (count($cart_items) == 0): ?>
            <div class="empty-cart">
                <div class="empty-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h3>Your Cart is Empty</h3>
                <p>Looks like you haven't added any items to your cart yet.</p>
                <a href="/app/electrastore/products/index.php" class="btn btn-primary">
                    <i class="fas fa-store"></i> Start Shopping
                </a>
            </div>
        <?php else: ?>
            <div class="cart-content">
                <!-- Cart Items Section -->
                <div class="cart-items-section">
                    <div class="cart-items-header">
                        <div class="cart-header-product">Product</div>
                        <div class="cart-header-price">Price</div>
                        <div class="cart-header-quantity">Quantity</div>
                        <div class="cart-header-subtotal">Subtotal</div>
                        <div class="cart-header-action"></div>
                    </div>
                    
                    <div class="cart-items-list">
                        <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item" id="cart-item-<?php echo $item['cart_item_id']; ?>">
                            <div class="cart-item-product">
                                <div class="cart-item-image">
                                    <img src="/app/electrastore/assets/images/products/<?php echo htmlspecialchars($item['image_url'] ?: 'placeholder.jpg'); ?>" 
                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                </div>
                                <div class="cart-item-details">
                                    <h4><?php echo htmlspecialchars($item['product_name']); ?></h4>
                                    <?php if ($item['stock'] < 10 && $item['stock'] > 0): ?>
                                        <span class="stock-warning">
                                            <i class="fas fa-exclamation-triangle"></i> Only <?php echo $item['stock']; ?> left
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="cart-item-price">
                                ₱<?php echo number_format($item['price'], 2); ?>
                            </div>
                            <div class="cart-item-quantity">
                                <input type="number" 
                                       class="quantity-input" 
                                       value="<?php echo $item['quantity']; ?>" 
                                       min="1"
                                       max="<?php echo $item['stock']; ?>"
                                       onchange="updateQuantity(<?php echo $item['cart_item_id']; ?>, this.value)">
                            </div>
                            <div class="cart-item-subtotal">
                                ₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                            </div>
                            <div class="cart-item-remove">
                                <button onclick="removeItem(<?php echo $item['cart_item_id']; ?>)" class="remove-btn" title="Remove item">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Continue Shopping Link -->
                    <div class="continue-shopping">
                        <a href="/app/electrastore/products/index.php" class="continue-link">
                            <i class="fas fa-arrow-left"></i> Continue Shopping
                        </a>
                    </div>
                </div>
                
                <!-- Order Summary Section -->
                <div class="order-summary-section">
                    <div class="summary-card">
                        <h3>Order Summary</h3>
                        
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span>₱<?php echo number_format($total, 2); ?></span>
                        </div>
                        
                        <div class="summary-row">
                            <span>Shipping</span>
                            <span>
                                <?php if ($total >= 2500): ?>
                                    <span class="free-ship">Free</span>
                                <?php else: ?>
                                    ₱150.00
                                <?php endif; ?>
                            </span>
                        </div>
                        
                        <?php if ($total < 2500): ?>
                        <div class="shipping-note">
                            <i class="fas fa-truck"></i>
                            Add ₱<?php echo number_format(2500 - $total, 2); ?> more to get free shipping
                        </div>
                        <?php else: ?>
                        <div class="free-shipping-badge">
                            <i class="fas fa-gift"></i>
                            You qualify for free shipping!
                        </div>
                        <?php endif; ?>
                        
                        <div class="summary-total">
                            <span>Total</span>
                            <span>
                                ₱<?php 
                                    $shipping = ($total >= 2500) ? 0 : 150;
                                    echo number_format($total + $shipping, 2); 
                                ?>
                            </span>
                        </div>
                        
                        <a href="/app/electrastore/checkout/index.php" class="checkout-btn">
                            <i class="fas fa-lock"></i> Proceed to Checkout
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.cart-wrapper {
    padding: 2rem 0;
    background: linear-gradient(135deg, #f5f7fa 0%, #f8fafc 100%);
    min-height: 100vh;
}

.cart-header {
    text-align: center;
    margin-bottom: 3rem;
}

.cart-header h1 {
    font-size: 2.5rem;
    font-weight: 800;
    background: linear-gradient(135deg, #4361ee, #7209b7);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    margin-bottom: 0.5rem;
}

.cart-header p {
    color: #64748b;
    font-size: 1rem;
}

/* Empty Cart */
.empty-cart {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 20px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.empty-icon {
    font-size: 4rem;
    color: #cbd5e1;
    margin-bottom: 1rem;
}

.empty-cart h3 {
    font-size: 1.2rem;
    color: #1e2a3e;
    margin-bottom: 0.5rem;
}

.empty-cart p {
    color: #64748b;
    margin-bottom: 1.5rem;
}

/* Cart Content */
.cart-content {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 2rem;
}

/* Cart Items Section */
.cart-items-section {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

/* Cart Header Row */
.cart-items-header {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr 60px;
    padding: 1rem 1.5rem;
    background: linear-gradient(135deg, #f8fafc, #ffffff);
    border-bottom: 2px solid #e2e8f0;
    font-weight: 600;
    color: #1e2a3e;
}

.cart-header-product {
    text-align: left;
}

.cart-header-price {
    text-align: right;
}

.cart-header-quantity {
    text-align: center;
}

.cart-header-subtotal {
    text-align: right;
}

.cart-header-action {
    text-align: center;
}

/* Cart Item Row */
.cart-items-list {
    padding: 0;
}

.cart-item {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr 60px;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    transition: background 0.3s ease;
}

.cart-item:hover {
    background: #f8fafc;
}

.cart-item-product {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.cart-item-image img {
    width: 70px;
    height: 70px;
    object-fit: cover;
    border-radius: 12px;
    background: #f1f5f9;
}

.cart-item-details h4 {
    font-size: 0.95rem;
    font-weight: 600;
    color: #1e2a3e;
    margin-bottom: 0.25rem;
}

.stock-warning {
    display: inline-block;
    font-size: 0.7rem;
    color: #f97316;
    margin-top: 0.25rem;
}

.stock-warning i {
    font-size: 0.65rem;
}

.cart-item-price {
    text-align: right;
    font-weight: 600;
    color: #1e2a3e;
}

.cart-item-quantity {
    text-align: center;
}

.quantity-input {
    width: 80px;
    height: 40px;
    text-align: center;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    font-size: 0.9rem;
    padding: 0.5rem;
}

.quantity-input:focus {
    outline: none;
    border-color: #4361ee;
    box-shadow: 0 0 0 2px rgba(67, 97, 238, 0.1);
}

.cart-item-subtotal {
    text-align: right;
    font-weight: 700;
    color: #4361ee;
    font-size: 1rem;
}

.cart-item-remove {
    text-align: center;
}

.remove-btn {
    background: none;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 8px;
    transition: all 0.3s ease;
    font-size: 1.1rem;
}

.remove-btn:hover {
    color: #dc2626;
    background: #fee2e2;
}

/* Continue Shopping */
.continue-shopping {
    padding: 1rem 1.5rem 1.5rem;
    border-top: 1px solid #e2e8f0;
}

.continue-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #4361ee;
    text-decoration: none;
    font-size: 0.9rem;
    transition: gap 0.3s ease;
}

.continue-link:hover {
    gap: 12px;
}

/* Order Summary */
.order-summary-section {
    position: sticky;
    top: 100px;
}

.summary-card {
    background: white;
    border-radius: 20px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.summary-card h3 {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1e2a3e;
    margin-bottom: 1.5rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #e2e8f0;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 1rem;
    color: #64748b;
}

.summary-total {
    display: flex;
    justify-content: space-between;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 2px solid #e2e8f0;
    font-weight: 700;
    font-size: 1.1rem;
    color: #1e2a3e;
}

.summary-total span:last-child {
    color: #4361ee;
    font-size: 1.2rem;
}

.free-ship {
    color: #059669;
    font-weight: 600;
}

.shipping-note {
    background: #fef3c7;
    padding: 0.75rem;
    border-radius: 10px;
    font-size: 0.75rem;
    color: #d97706;
    margin: 1rem 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.free-shipping-badge {
    background: #d1fae5;
    padding: 0.75rem;
    border-radius: 10px;
    font-size: 0.75rem;
    color: #059669;
    margin: 1rem 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.checkout-btn {
    display: block;
    text-align: center;
    background: linear-gradient(135deg, #4361ee, #7209b7);
    color: white;
    text-decoration: none;
    padding: 1rem;
    border-radius: 12px;
    font-weight: 600;
    margin-top: 1rem;
    transition: all 0.3s ease;
}

.checkout-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(67, 97, 238, 0.3);
}

/* Mobile Responsive */
@media (max-width: 1024px) {
    .cart-content {
        grid-template-columns: 1fr;
    }
    
    .order-summary-section {
        position: static;
    }
}

@media (max-width: 768px) {
    .cart-header h1 {
        font-size: 1.8rem;
    }
    
    .cart-items-header {
        display: none;
    }
    
    .cart-item {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        padding: 1rem;
    }
    
    .cart-item-product {
        width: 100%;
    }
    
    .cart-item-price {
        width: 100%;
        text-align: left;
        display: flex;
        justify-content: space-between;
        border-top: 1px solid #e2e8f0;
        padding-top: 0.75rem;
    }
    
    .cart-item-price::before {
        content: "Price:";
        font-weight: 600;
        color: #64748b;
    }
    
    .cart-item-quantity {
        width: 100%;
        text-align: left;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .cart-item-quantity::before {
        content: "Quantity:";
        font-weight: 600;
        color: #64748b;
    }
    
    .quantity-input {
        width: 80px;
    }
    
    .cart-item-subtotal {
        width: 100%;
        text-align: left;
        display: flex;
        justify-content: space-between;
        border-top: 1px solid #e2e8f0;
        padding-top: 0.75rem;
    }
    
    .cart-item-subtotal::before {
        content: "Subtotal:";
        font-weight: 600;
        color: #64748b;
    }
    
    .cart-item-remove {
        width: 100%;
        text-align: right;
    }
}
</style>

<script>
// Update quantity function
function updateQuantity(cartItemId, quantity) {
    if (quantity < 1) {
        removeItem(cartItemId);
        return;
    }
    
    fetch('/app/electrastore/cart/update.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'cart_item_id=' + cartItemId + '&quantity=' + quantity
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            showMessage(data.message, 'error');
        }
    });
}

// Remove item function
function removeItem(cartItemId) {
    if (confirm('Are you sure you want to remove this item from your cart?')) {
        fetch('/app/electrastore/cart/remove.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'cart_item_id=' + cartItemId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('Item removed from cart', 'success');
                setTimeout(() => {
                    location.reload();
                }, 500);
            }
        });
    }
}

// Clear entire cart
function clearCart() {
    if (confirm('Are you sure you want to remove all items from your cart?')) {
        const cartItems = document.querySelectorAll('.cart-item');
        cartItems.forEach(item => {
            const removeBtn = item.querySelector('.remove-btn');
            if (removeBtn) removeBtn.click();
        });
    }
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