<?php
require_once '../includes/header.php';
requireAdmin();

$conn = getConnection();

$error = '';
$success = '';
$product = null;

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: products.php');
    exit;
}

$product_id = (int)$_GET['id'];

// Get product details
$stmt = $conn->prepare("SELECT p.*, c.category_name 
                        FROM products p 
                        LEFT JOIN categories c ON p.category_id = c.category_id 
                        WHERE p.product_id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: products.php');
    exit;
}

// Check if product exists in orders before showing delete form
$check_orders = $conn->prepare("SELECT COUNT(*) as count FROM order_items WHERE product_id = ?");
$check_orders->execute([$product_id]);
$order_result = $check_orders->fetch();
$order_count = isset($order_result['count']) ? $order_result['count'] : 0;

$check_cart = $conn->prepare("SELECT COUNT(*) as count FROM cart_items WHERE product_id = ?");
$check_cart->execute([$product_id]);
$cart_result = $check_cart->fetch();
$cart_count = isset($cart_result['count']) ? $cart_result['count'] : 0;

// Handle deletion confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    try {
        // Double-check orders again before deletion
        $check_orders->execute([$product_id]);
        $order_result = $check_orders->fetch();
        $order_count = isset($order_result['count']) ? $order_result['count'] : 0;
        
        if ($order_count > 0) {
            $error = "Cannot delete this product because it has been ordered by customers (found in $order_count orders).";
        } else {
            // Delete cart items first (if any)
            if ($cart_count > 0) {
                $delete_cart = $conn->prepare("DELETE FROM cart_items WHERE product_id = ?");
                $delete_cart->execute([$product_id]);
            }
            
            // Now delete the product
            $delete_stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
            if ($delete_stmt->execute([$product_id])) {
                $_SESSION['message'] = "Product deleted successfully!";
                $_SESSION['message_type'] = 'success';
                header("Location: products.php");
                exit();
            } else {
                $error = "Failed to delete product. Please try again.";
            }
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Alternative: Soft delete (set stock to 0) if product has orders
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['soft_delete'])) {
    try {
        $stmt = $conn->prepare("UPDATE products SET stock = 0 WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $_SESSION['message'] = "Product has been marked as out of stock (hidden from customers).";
        $_SESSION['message_type'] = 'success';
        header("Location: products.php");
        exit();
    } catch (PDOException $e) {
        $error = "Failed to update product: " . $e->getMessage();
    }
}
?>

<div class="form-container" style="max-width: 600px;">
    <h2>Delete Product</h2>
    
    <div class="admin-menu">
        <a href="/app/electrastore/admin/index.php">Dashboard</a>
        <a href="/app/electrastore/admin/products.php">Manage Products</a>
        <a href="/app/electrastore/admin/categories.php">Manage Categories</a>
        <a href="/app/electrastore/admin/orders.php">Manage Orders</a>
        <a href="/app/electrastore/admin/users.php">Manage Users</a>
    </div>
    
    <?php if ($error): ?>
        <div class="error-message">
            <?php echo htmlspecialchars($error); ?>
        </div>
        
        <?php if ($order_count > 0): ?>
            <div class="warning-message" style="background-color: #fff3cd; border: 1px solid #ffc107; color: #856404; padding: 1rem; border-radius: 5px; margin: 1rem 0;">
                <strong>Alternative Solution:</strong><br>
                Since this product has existing orders, you cannot delete it completely. However, you can:<br>
                • Mark it as out of stock (hide from customers)<br>
                • Keep it for order history records<br><br>
                <form method="POST" action="" style="margin-top: 10px;">
                    <button type="submit" name="soft_delete" class="btn" style="background-color: #f39c12; color: white;">
                        Mark as Out of Stock (Hide Product)
                    </button>
                    <a href="products.php" class="btn">Cancel</a>
                </form>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 1rem;">
            <a href="products.php" class="btn">Back to Products</a>
        </div>
        
    <?php elseif ($success): ?>
        <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <div style="margin-top: 1rem;">
            <a href="products.php" class="btn">Back to Products</a>
        </div>
        
    <?php else: ?>
        <div class="warning-message" style="background-color: #fff3cd; border: 1px solid #ffc107; color: #856404; padding: 1rem; border-radius: 5px; margin-bottom: 1.5rem;">
            <strong>Warning!</strong> This action cannot be undone.
        </div>
        
        <div style="background: white; border-radius: 10px; padding: 1.5rem; margin-bottom: 1.5rem;">
            <h3>Product Details</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr style="border-bottom: 1px solid #ddd;">
                    <th style="padding: 0.5rem 0; text-align: left;">Product Name:</th>
                    <td style="padding: 0.5rem 0;"><?php echo htmlspecialchars($product['product_name']); ?><\/td>
                <\/tr>
                <tr style="border-bottom: 1px solid #ddd;">
                    <th style="padding: 0.5rem 0; text-align: left;">Category:</th>
                    <td style="padding: 0.5rem 0;"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?><\/td>
                <\/tr>
                <tr style="border-bottom: 1px solid #ddd;">
                    <th style="padding: 0.5rem 0; text-align: left;">Price:</th>
                    <td style="padding: 0.5rem 0;">₱<?php echo number_format($product['price'], 2); ?><\/td>
                <\/tr>
                <tr style="border-bottom: 1px solid #ddd;">
                    <th style="padding: 0.5rem 0; text-align: left;">Stock:</th>
                    <td style="padding: 0.5rem 0;"><?php echo $product['stock']; ?><\/td>
                <\/tr>
                <tr style="border-bottom: 1px solid #ddd;">
                    <th style="padding: 0.5rem 0; text-align: left;">Brand:</th>
                    <td style="padding: 0.5rem 0;"><?php echo htmlspecialchars($product['brand'] ?? 'N/A'); ?><\/td>
                <\/tr>
                <?php if ($order_count > 0): ?>
                <tr style="border-bottom: 1px solid #ffc107;">
                    <th style="padding: 0.5rem 0; text-align: left; color: #856404;">Order History:</th>
                    <td style="padding: 0.5rem 0; color: #856404;">
                        <strong>⚠️ This product appears in <?php echo $order_count; ?> customer order(s).</strong>
                    <\/td>
                <\/tr>
                <?php endif; ?>
                <?php if ($cart_count > 0): ?>
                <tr style="border-bottom: 1px solid #ffc107;">
                    <th style="padding: 0.5rem 0; text-align: left; color: #856404;">Active Carts:</th>
                    <td style="padding: 0.5rem 0; color: #856404;">
                        <strong>⚠️ This product is in <?php echo $cart_count; ?> customer cart(s).</strong>
                    <\/td>
                <\/tr>
                <?php endif; ?>
            <\/table>
        <\/div>
        
        <?php if ($order_count == 0): ?>
            <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this product? This action cannot be undone.');">
                <p style="color: #e74c3c; margin-bottom: 1rem;">
                    <strong>Are you sure you want to delete this product?</strong>
                </p>
                
                <div style="display: flex; gap: 1rem;">
                    <button type="submit" name="confirm_delete" class="btn" style="background-color: #e74c3c; color: white;">
                        Yes, Delete Product
                    </button>
                    <a href="products.php" class="btn">Cancel</a>
                </div>
            </form>
        <?php else: ?>
            <div class="error-message">
                <strong>Cannot delete this product.</strong><br>
                This product has existing orders. Please use the "Mark as Out of Stock" option above instead.
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
require_once '../includes/footer.php';
?>