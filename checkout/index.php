<?php
// Start output buffering to prevent header issues
ob_start();

require_once '../includes/header.php';

if (!isLoggedIn()) {
    header('Location: /app/electrastore/user/login.php');
    exit();
}

$conn = getConnection();
$cart_id = getUserCartId($_SESSION['user_id']);

// Get cart items
$stmt = $conn->prepare("SELECT ci.*, p.product_name, p.price, p.image_url 
                        FROM cart_items ci 
                        JOIN products p ON ci.product_id = p.product_id 
                        WHERE ci.cart_id = ?");
$stmt->execute([$cart_id]);
$cart_items = $stmt->fetchAll();

if (count($cart_items) == 0) {
    header('Location: /app/electrastore/cart/index.php');
    exit();
}

// Calculate total
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipping_address = trim($_POST['shipping_address'] ?? '');
    $payment_method = $_POST['payment_method'] ?? 'Cash on Delivery';
    
    if (empty($shipping_address)) {
        $error = 'Please enter shipping address';
    } else {
        $conn->beginTransaction();
        
        try {
            // Create order
            $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, shipping_address, payment_method) 
                                    VALUES (?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $total, $shipping_address, $payment_method]);
            $order_id = $conn->lastInsertId();
            
            // Add order items and update stock
            foreach ($cart_items as $item) {
                // Add order item
                $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) 
                                        VALUES (?, ?, ?, ?)");
                $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
                
                // Update stock
                $stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE product_id = ?");
                $stmt->execute([$item['quantity'], $item['product_id']]);
            }
            
            // Clear cart
            $stmt = $conn->prepare("DELETE FROM cart_items WHERE cart_id = ?");
            $stmt->execute([$cart_id]);
            
            $conn->commit();
            $success = true;
            
        } catch (Exception $e) {
            $conn->rollBack();
            $error = 'Order processing failed: ' . $e->getMessage();
        }
    }
    
    if ($success) {
        ob_end_clean();
        header('Location: /app/electrastore/orders/history.php?success=1');
        exit();
    }
}
?>

<div class="checkout-wrapper">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-left">
                <h1>Checkout</h1>
                <p>Complete your order to proceed with payment</p>
            </div>
            <div class="header-right">
                <a href="/app/electrastore/cart/index.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Cart
                </a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert-message error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="checkout-content">
            <!-- Order Summary Section -->
            <div class="order-summary-section">
                <div class="summary-card">
                    <h3>
                        <i class="fas fa-shopping-bag"></i>
                        Order Summary
                    </h3>
                    
                    <div class="order-items-list">
                        <?php foreach ($cart_items as $item): ?>
                        <div class="order-item">
                            <div class="item-image">
                                <img src="/app/electrastore/assets/images/products/<?php echo $item['image_url'] ?: 'placeholder.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                            </div>
                            <div class="item-details">
                                <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                <div class="item-meta">
                                    <span class="item-quantity">Qty: <?php echo $item['quantity']; ?></span>
                                    <span class="item-price">₱<?php echo number_format($item['price'], 2); ?> each</span>
                                </div>
                            </div>
                            <div class="item-subtotal">
                                ₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="summary-totals">
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span>₱<?php echo number_format($total, 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Shipping</span>
                            <span class="free-ship">Free</span>
                        </div>
                        <div class="summary-total">
                            <span>Total</span>
                            <span>₱<?php echo number_format($total, 2); ?></span>
                        </div>
                    </div>
                    
                    <div class="secure-notice">
                        <i class="fas fa-lock"></i>
                        <span>Your payment information is secure</span>
                    </div>
                </div>
            </div>
            
            <!-- Checkout Form Section -->
            <div class="checkout-form-section">
                <div class="form-card">
                    <h3>
                        <i class="fas fa-truck"></i>
                        Shipping Information
                    </h3>
                    
                    <form method="POST" action="" id="checkoutForm">
                        <div class="form-group">
                            <label>
                                <i class="fas fa-map-marker-alt"></i>
                                Shipping Address *
                            </label>
                            <textarea name="shipping_address" rows="4" required 
                                      placeholder="Enter your complete shipping address (House/Unit #, Street, Barangay, City, Province, Zip Code)"><?php echo htmlspecialchars($_SESSION['address'] ?? ''); ?></textarea>
                            <small class="form-hint">
                                <i class="fas fa-info-circle"></i>
                                Please provide a complete address for accurate delivery
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <i class="fas fa-credit-card"></i>
                                Payment Method
                            </label>
                            <div class="payment-options">
                                <label class="payment-option">
                                    <input type="radio" name="payment_method" value="Cash on Delivery" checked>
                                    <div class="payment-option-content">
                                        <i class="fas fa-money-bill-wave"></i>
                                        <div>
                                            <strong>Cash on Delivery</strong>
                                            <p>Pay when you receive your order</p>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <small class="form-hint">
                                <i class="fas fa-info-circle"></i>
                                More payment options coming soon (GCash, Credit Card, PayPal)
                            </small>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn-place-order">
                                <i class="fas fa-check-circle"></i> Place Order
                            </button>
                            <a href="/app/electrastore/cart/index.php" class="btn-cancel">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
                
                <!-- Order Tips -->
                <div class="tips-card">
                    <h4>
                        <i class="fas fa-lightbulb"></i>
                        Order Tips
                    </h4>
                    <div class="tips-list">
                        <div class="tip-item">
                            <i class="fas fa-check-circle"></i>
                            <span>Double-check your shipping address to avoid delivery delays</span>
                        </div>
                        <div class="tip-item">
                            <i class="fas fa-clock"></i>
                            <span>Orders are processed within 1-2 business days</span>
                        </div>
                        <div class="tip-item">
                            <i class="fas fa-phone-alt"></i>
                            <span>Contact us at (088) 123-4567 for any questions</span>
                        </div>
                        <div class="tip-item">
                            <i class="fas fa-undo-alt"></i>
                            <span>Free returns within 7 days of delivery</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.checkout-wrapper {
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

.btn-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 0.6rem 1.2rem;
    background: white;
    color: #4361ee;
    text-decoration: none;
    border-radius: 10px;
    font-weight: 500;
    transition: all 0.3s ease;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.btn-back:hover {
    gap: 12px;
    background: #f1f5f9;
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

.alert-message.error {
    background: #fee2e2;
    color: #dc2626;
    border-left: 4px solid #dc2626;
}

/* Checkout Content */
.checkout-content {
    display: grid;
    grid-template-columns: 1fr 1.5fr;
    gap: 2rem;
}

/* Order Summary Section */
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
    display: flex;
    align-items: center;
    gap: 10px;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #e2e8f0;
}

.summary-card h3 i {
    color: #4361ee;
}

.order-items-list {
    max-height: 400px;
    overflow-y: auto;
    margin-bottom: 1rem;
}

.order-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 0;
    border-bottom: 1px solid #e2e8f0;
}

.order-item:last-child {
    border-bottom: none;
}

.item-image {
    width: 60px;
    height: 60px;
    border-radius: 10px;
    overflow: hidden;
    background: #f1f5f9;
}

.item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.item-details {
    flex: 1;
}

.item-name {
    font-weight: 600;
    color: #1e2a3e;
    margin-bottom: 0.25rem;
}

.item-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.7rem;
    color: #64748b;
}

.item-subtotal {
    font-weight: 700;
    color: #4361ee;
    font-size: 0.9rem;
}

.summary-totals {
    padding-top: 1rem;
    margin-top: 1rem;
    border-top: 1px solid #e2e8f0;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.75rem;
    color: #64748b;
    font-size: 0.9rem;
}

.free-ship {
    color: #059669;
    font-weight: 600;
}

.summary-total {
    display: flex;
    justify-content: space-between;
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 2px solid #e2e8f0;
    font-weight: 700;
    font-size: 1.1rem;
    color: #1e2a3e;
}

.summary-total span:last-child {
    color: #4361ee;
    font-size: 1.2rem;
}

.secure-notice {
    margin-top: 1rem;
    padding: 0.75rem;
    background: #f8fafc;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 0.75rem;
    color: #64748b;
}

.secure-notice i {
    color: #059669;
}

/* Checkout Form Section */
.form-card {
    background: white;
    border-radius: 20px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.form-card h3 {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1e2a3e;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 10px;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #e2e8f0;
}

.form-card h3 i {
    color: #4361ee;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #1e2a3e;
    font-size: 0.85rem;
}

.form-group label i {
    color: #4361ee;
}

.form-group textarea {
    width: 100%;
    padding: 0.8rem;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    font-family: inherit;
    transition: all 0.3s ease;
    font-size: 0.9rem;
    resize: vertical;
}

.form-group textarea:focus {
    outline: none;
    border-color: #4361ee;
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
}

.form-hint {
    display: block;
    margin-top: 0.5rem;
    font-size: 0.7rem;
    color: #64748b;
}

.form-hint i {
    margin-right: 4px;
}

/* Payment Options */
.payment-options {
    margin-bottom: 0.5rem;
}

.payment-option {
    display: block;
    cursor: pointer;
    margin-bottom: 0.5rem;
}

.payment-option input {
    display: none;
}

.payment-option-content {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 1rem;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    transition: all 0.3s ease;
}

.payment-option input:checked + .payment-option-content {
    border-color: #4361ee;
    background: rgba(67, 97, 238, 0.05);
}

.payment-option-content i {
    font-size: 1.5rem;
    color: #4361ee;
}

.payment-option-content strong {
    display: block;
    font-size: 0.9rem;
    color: #1e2a3e;
}

.payment-option-content p {
    font-size: 0.7rem;
    color: #64748b;
    margin: 0;
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
}

.btn-place-order {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 0.875rem;
    background: linear-gradient(135deg, #4361ee, #7209b7);
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.95rem;
}

.btn-place-order:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(67, 97, 238, 0.3);
}

.btn-cancel {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 0.875rem 1.5rem;
    background: #f1f5f9;
    color: #64748b;
    text-decoration: none;
    border-radius: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-cancel:hover {
    background: #e2e8f0;
    color: #1e2a3e;
}

/* Tips Card */
.tips-card {
    background: white;
    border-radius: 20px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.tips-card h4 {
    font-size: 1rem;
    font-weight: 600;
    color: #1e2a3e;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

.tips-card h4 i {
    color: #f59e0b;
}

.tips-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.tip-item {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.8rem;
    color: #64748b;
}

.tip-item i {
    font-size: 0.9rem;
    color: #10b981;
}

/* Responsive */
@media (max-width: 1024px) {
    .checkout-content {
        grid-template-columns: 1fr;
    }
    
    .order-summary-section {
        position: static;
    }
}

@media (max-width: 768px) {
    .checkout-wrapper {
        padding: 1rem 0;
    }
    
    .header-left h1 {
        font-size: 1.5rem;
    }
    
    .page-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .order-item {
        flex-wrap: wrap;
    }
    
    .item-subtotal {
        width: 100%;
        text-align: right;
        padding-top: 0.5rem;
        border-top: 1px dashed #e2e8f0;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn-place-order,
    .btn-cancel {
        width: 100%;
    }
}

@media (max-width: 480px) {
    .order-item {
        flex-direction: column;
        text-align: center;
    }
    
    .item-image {
        margin: 0 auto;
    }
    
    .item-subtotal {
        text-align: center;
    }
    
    .payment-option-content {
        padding: 0.75rem;
    }
}
</style>

<?php
require_once '../includes/footer.php';
?>