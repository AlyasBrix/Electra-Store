<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /app/electrastore/user/login.php');
    exit();
}

$conn = getConnection();

// Get cart items
$cart_id = getUserCartId($_SESSION['user_id']);

$stmt = $conn->prepare("SELECT ci.*, p.product_name, p.price, p.stock 
                        FROM cart_items ci 
                        JOIN products p ON ci.product_id = p.product_id 
                        WHERE ci.cart_id = ?");
$stmt->execute([$cart_id]);
$cart_items = $stmt->fetchAll();

// Check if cart is empty
if (count($cart_items) == 0) {
    header('Location: /app/electrastore/cart/index.php');
    exit();
}

// Calculate total
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Add shipping (free for orders over ₱2500)
$shipping = ($total >= 2500) ? 0 : 150;
$grand_total = $total + $shipping;

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $zip_code = trim($_POST['zip_code'] ?? '');
    $payment_method = $_POST['payment_method'] ?? '';
    
    // Validation
    $errors = [];
    
    if (empty($full_name)) $errors[] = 'Full name is required';
    if (empty($email)) $errors[] = 'Email is required';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format';
    if (empty($phone)) $errors[] = 'Phone number is required';
    if (empty($address)) $errors[] = 'Address is required';
    if (empty($city)) $errors[] = 'City is required';
    if (empty($zip_code)) $errors[] = 'Zip code is required';
    if (empty($payment_method)) $errors[] = 'Payment method is required';
    
    // Check stock availability
    foreach ($cart_items as $item) {
        $stmt = $conn->prepare("SELECT stock FROM products WHERE product_id = ?");
        $stmt->execute([$item['product_id']]);
        $product = $stmt->fetch();
        
        if ($product['stock'] < $item['quantity']) {
            $errors[] = "Not enough stock for {$item['product_name']}. Available: {$product['stock']}";
        }
    }
    
    if (empty($errors)) {
        try {
            $conn->beginTransaction();
            
            // Format full address
            $shipping_address = "$address, $city, $zip_code";
            
            // Insert order
            $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, shipping_address, payment_method, order_status) 
                                    VALUES (?, ?, ?, ?, 'Pending')");
            $stmt->execute([$_SESSION['user_id'], $grand_total, $shipping_address, $payment_method]);
            $order_id = $conn->lastInsertId();
            
            // Insert order items and update stock
            foreach ($cart_items as $item) {
                // Insert order item
                $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) 
                                        VALUES (?, ?, ?, ?)");
                $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
                
                // Update product stock
                $stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE product_id = ?");
                $stmt->execute([$item['quantity'], $item['product_id']]);
            }
            
            // Clear cart
            $stmt = $conn->prepare("DELETE FROM cart_items WHERE cart_id = ?");
            $stmt->execute([$cart_id]);
            
            $conn->commit();
            
            // Store order info in session for success page
            $_SESSION['last_order_id'] = $order_id;
            $_SESSION['last_order_total'] = $grand_total;
            
            // Redirect to success page
            header('Location: /app/electrastore/checkout/success.php');
            exit();
            
        } catch (Exception $e) {
            $conn->rollBack();
            $error = "Failed to process order: " . $e->getMessage();
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

// If there's an error or GET request, show the checkout page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - ElectraStore</title>
    <link rel="stylesheet" href="/app/electrastore/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <div class="checkout-wrapper">
        <div class="container">
            <!-- Header -->
            <div class="checkout-header">
                <a href="/app/electrastore/cart/index.php" class="back-to-cart">
                    <i class="fas fa-arrow-left"></i> Back to Cart
                </a>
                <h1>Checkout</h1>
                <p>Complete your order information</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="checkout-content">
                <!-- Billing Form -->
                <div class="checkout-form">
                    <div class="form-card">
                        <h3>
                            <i class="fas fa-user"></i>
                            Shipping Information
                        </h3>
                        
                        <form method="POST" action="" id="checkout-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Full Name *</label>
                                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Email *</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Phone Number *</label>
                                    <input type="tel" name="phone" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Zip Code *</label>
                                    <input type="text" name="zip_code" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Address *</label>
                                <input type="text" name="address" placeholder="Street address" required>
                            </div>
                            
                            <div class="form-group">
                                <label>City *</label>
                                <input type="text" name="city" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Order Notes (Optional)</label>
                                <textarea name="notes" rows="3" placeholder="Special instructions for delivery..."></textarea>
                            </div>
                            
                            <h3 class="section-title">
                                <i class="fas fa-credit-card"></i>
                                Payment Method
                            </h3>
                            
                            <div class="payment-options">
                                <label class="payment-option">
                                    <input type="radio" name="payment_method" value="Credit Card" required>
                                    <div class="payment-option-content">
                                        <i class="fab fa-cc-visa"></i>
                                        <i class="fab fa-cc-mastercard"></i>
                                        <span>Credit / Debit Card</span>
                                    </div>
                                </label>
                                
                                <label class="payment-option">
                                    <input type="radio" name="payment_method" value="PayPal" required>
                                    <div class="payment-option-content">
                                        <i class="fab fa-paypal"></i>
                                        <span>PayPal</span>
                                    </div>
                                </label>
                                
                                <label class="payment-option">
                                    <input type="radio" name="payment_method" value="GCash" required>
                                    <div class="payment-option-content">
                                        <i class="fas fa-mobile-alt"></i>
                                        <span>GCash</span>
                                    </div>
                                </label>
                                
                                <label class="payment-option">
                                    <input type="radio" name="payment_method" value="Cash on Delivery" required>
                                    <div class="payment-option-content">
                                        <i class="fas fa-money-bill-wave"></i>
                                        <span>Cash on Delivery</span>
                                    </div>
                                </label>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Order Summary -->
                <div class="checkout-summary">
                    <div class="summary-card">
                        <h3>Order Summary</h3>
                        
                        <div class="order-items">
                            <?php foreach ($cart_items as $item): ?>
                            <div class="order-item">
                                <div class="item-info">
                                    <span class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></span>
                                    <span class="item-quantity">x<?php echo $item['quantity']; ?></span>
                                </div>
                                <span class="item-price">₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="summary-details">
                            <div class="summary-row">
                                <span>Subtotal</span>
                                <span>₱<?php echo number_format($total, 2); ?></span>
                            </div>
                            <div class="summary-row">
                                <span>Shipping</span>
                                <span id="shipping-display">
                                    <?php if ($shipping == 0): ?>
                                        <span class="free">Free</span>
                                    <?php else: ?>
                                        ₱<?php echo number_format($shipping, 2); ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="summary-row total">
                                <span>Total</span>
                                <span>₱<?php echo number_format($grand_total, 2); ?></span>
                            </div>
                        </div>
                        
                        <button type="submit" form="checkout-form" class="place-order-btn">
                            <i class="fas fa-check-circle"></i> Place Order
                        </button>
                        
                        <div class="secure-notice">
                            <i class="fas fa-lock"></i>
                            Your information is secure and encrypted
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
    
    .checkout-header {
        text-align: center;
        margin-bottom: 2rem;
        position: relative;
    }
    
    .back-to-cart {
        position: absolute;
        left: 0;
        top: 0;
        color: #4361ee;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: gap 0.3s ease;
    }
    
    .back-to-cart:hover {
        gap: 12px;
    }
    
    .checkout-header h1 {
        font-size: 2rem;
        font-weight: 800;
        background: linear-gradient(135deg, #4361ee, #7209b7);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        margin-bottom: 0.5rem;
    }
    
    .checkout-header p {
        color: #64748b;
    }
    
    .checkout-content {
        display: grid;
        grid-template-columns: 1fr 380px;
        gap: 2rem;
    }
    
    .form-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
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
    }
    
    .form-card h3 i {
        color: #4361ee;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }
    
    .form-group {
        margin-bottom: 1rem;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: #1e2a3e;
        font-size: 0.85rem;
    }
    
    .form-group input,
    .form-group textarea {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-family: inherit;
        transition: all 0.3s ease;
    }
    
    .form-group input:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #4361ee;
        box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
    }
    
    .section-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1e2a3e;
        margin: 1.5rem 0 1rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .payment-options {
        display: grid;
        gap: 0.75rem;
    }
    
    .payment-option {
        display: block;
        cursor: pointer;
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
        font-size: 1.2rem;
        color: #4361ee;
    }
    
    .payment-option-content span {
        font-size: 0.9rem;
        font-weight: 500;
        color: #1e2a3e;
    }
    
    /* Order Summary */
    .summary-card {
        background: white;
        border-radius: 20px;
        padding: 1.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        position: sticky;
        top: 100px;
    }
    
    .summary-card h3 {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1e2a3e;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .order-items {
        max-height: 300px;
        overflow-y: auto;
        margin-bottom: 1rem;
    }
    
    .order-item {
        display: flex;
        justify-content: space-between;
        padding: 0.75rem 0;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .item-info {
        display: flex;
        gap: 8px;
        align-items: center;
    }
    
    .item-name {
        font-size: 0.85rem;
        color: #1e2a3e;
    }
    
    .item-quantity {
        font-size: 0.75rem;
        color: #64748b;
    }
    
    .item-price {
        font-weight: 600;
        color: #1e2a3e;
    }
    
    .summary-details {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #e2e8f0;
    }
    
    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.75rem;
        color: #64748b;
        font-size: 0.9rem;
    }
    
    .summary-row.total {
        margin-top: 0.75rem;
        padding-top: 0.75rem;
        border-top: 2px solid #e2e8f0;
        font-weight: 700;
        font-size: 1.1rem;
        color: #1e2a3e;
    }
    
    .summary-row.total span:last-child {
        color: #4361ee;
    }
    
    .free {
        color: #059669;
        font-weight: 600;
    }
    
    .place-order-btn {
        width: 100%;
        padding: 1rem;
        background: linear-gradient(135deg, #4361ee, #7209b7);
        color: white;
        border: none;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
        margin-top: 1rem;
        transition: all 0.3s ease;
    }
    
    .place-order-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(67, 97, 238, 0.3);
    }
    
    .secure-notice {
        text-align: center;
        margin-top: 1rem;
        font-size: 0.7rem;
        color: #94a3b8;
    }
    
    .secure-notice i {
        margin-right: 5px;
    }
    
    /* Responsive */
    @media (max-width: 1024px) {
        .checkout-content {
            grid-template-columns: 1fr;
        }
        
        .summary-card {
            position: static;
        }
    }
    
    @media (max-width: 768px) {
        .checkout-header {
            margin-bottom: 2rem;
        }
        
        .back-to-cart {
            position: static;
            display: inline-flex;
            margin-bottom: 1rem;
        }
        
        .form-row {
            grid-template-columns: 1fr;
        }
        
        .form-card {
            padding: 1.5rem;
        }
        
        .checkout-header h1 {
            font-size: 1.5rem;
        }
    }
    </style>
    
    <script>
    // Auto-calculate shipping
    document.addEventListener('DOMContentLoaded', function() {
        const total = <?php echo $total; ?>;
        const shippingDisplay = document.getElementById('shipping-display');
        
        if (total >= 2500) {
            shippingDisplay.innerHTML = '<span class="free">Free</span>';
        }
    });
    </script>
</body>
</html>