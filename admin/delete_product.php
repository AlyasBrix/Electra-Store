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

// Handle deletion confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    try {
        // Check if product exists in any orders
        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = ?");
        $check_stmt->execute([$product_id]);
        $order_count = $check_stmt->fetchColumn();
        
        if ($order_count > 0) {
            $error = "Cannot delete this product because it has been ordered by customers. You may want to mark it as inactive instead.";
        } else {
            // Delete the product
            $delete_stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
            if ($delete_stmt->execute([$product_id])) {
                $success = "Product deleted successfully!";
                // Redirect after 2 seconds
                header("refresh:2;url=products.php");
            } else {
                $error = "Failed to delete product. Please try again.";
            }
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>

<div class="form-container" style="max-width: 600px;">
    <h2>Delete Product</h2>
    
    <div class="admin-menu">
        <a href="/electrastore/admin/index.php">Dashboard</a>
        <a href="/electrastore/admin/products.php">Manage Products</a>
        <a href="/electrastore/admin/orders.php">Manage Orders</a>
        <a href="/electrastore/admin/add_product.php">Add Product</a>
    </div>
    
    <?php if ($error): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
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
                    <td style="padding: 0.5rem 0;"><?php echo htmlspecialchars($product['product_name']); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #ddd;">
                    <th style="padding: 0.5rem 0; text-align: left;">Category:</th>
                    <td style="padding: 0.5rem 0;"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #ddd;">
                    <th style="padding: 0.5rem 0; text-align: left;">Price:</th>
                    <td style="padding: 0.5rem 0;">₱<?php echo number_format($product['price'], 2); ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #ddd;">
                    <th style="padding: 0.5rem 0; text-align: left;">Stock:</th>
                    <td style="padding: 0.5rem 0;"><?php echo $product['stock']; ?></td>
                </tr>
                <tr style="border-bottom: 1px solid #ddd;">
                    <th style="padding: 0.5rem 0; text-align: left;">Brand:</th>
                    <td style="padding: 0.5rem 0;"><?php echo htmlspecialchars($product['brand'] ?? 'N/A'); ?></td>
                </tr>
                <?php if (!empty($product['image_url'])): ?>
                <tr>
                    <th style="padding: 0.5rem 0; text-align: left;">Image:</th>
                    <td style="padding: 0.5rem 0;">
                        <img src="/electrastore/assets/images/<?php echo htmlspecialchars($product['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($product['product_name']); ?>" 
                             style="max-width: 100px; max-height: 100px;">
                    </td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        
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
    <?php endif; ?>
</div>

<?php
require_once '../includes/footer.php';
?>