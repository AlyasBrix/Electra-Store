<?php
require_once '../includes/header.php';

if (!isLoggedIn()) {
    header('Location: /app/electrastore/user/login.php');
    exit();
}

if (!isAdmin() && $_SESSION['role'] != 'staff') {
    header('Location: /app/electrastore/index.php');
    exit();
}

$conn = getConnection();

// Staff can update stock (but not add/delete products)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $product_id = (int)$_POST['product_id'];
    $new_stock = (int)$_POST['stock'];
    
    $stmt = $conn->prepare("UPDATE products SET stock = ? WHERE product_id = ?");
    if ($stmt->execute([$new_stock, $product_id])) {
        $_SESSION['message'] = "Stock updated successfully!";
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = "Failed to update stock!";
        $_SESSION['message_type'] = 'error';
    }
    header('Location: products.php');
    exit();
}

// Get products
$stmt = $conn->prepare("SELECT p.*, c.category_name FROM products p 
                        LEFT JOIN categories c ON p.category_id = c.category_id 
                        ORDER BY p.created_at DESC");
$stmt->execute();
$products = $stmt->fetchAll();
?>

<div class="container">
    <h2>Product Inventory</h2>
    
    <div class="staff-menu" style="margin-bottom: 2rem;">
        <a href="/app/electrastore/staff/index.php" style="display: inline-block; padding: 0.5rem 1rem; background: #34495e; color: white; text-decoration: none; border-radius: 5px; margin-right: 0.5rem;">Dashboard</a>
        <a href="/app/electrastore/staff/orders.php" style="display: inline-block; padding: 0.5rem 1rem; background: #34495e; color: white; text-decoration: none; border-radius: 5px; margin-right: 0.5rem;">Manage Orders</a>
        <a href="/app/electrastore/staff/products.php" style="display: inline-block; padding: 0.5rem 1rem; background: #3498db; color: white; text-decoration: none; border-radius: 5px; margin-right: 0.5rem;">View Products</a>
        <a href="/app/electrastore/staff/customers.php" style="display: inline-block; padding: 0.5rem 1rem; background: #34495e; color: white; text-decoration: none; border-radius: 5px;">View Customers</a>
    </div>
    
    <?php if (isset($_SESSION['message'])): ?>
        <div class="<?php echo $_SESSION['message_type'] == 'success' ? 'success-message' : 'error-message'; ?>">
            <?php 
            echo htmlspecialchars($_SESSION['message']);
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
            ?>
        </div>
    <?php endif; ?>
    
    <div style="margin-bottom: 1rem;">
        <p>Note: Staff can only update stock quantities. To add/edit/delete products, contact an Administrator.</p>
    </div>
    
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Image</th>
                <th>Name</th>
                <th>Category</th>
                <th>Price</th>
                <th>Current Stock</th>
                <th>Update Stock</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
            <tr>
                <td><?php echo $product['product_id']; ?></td>
                <td>
                    <img src="/app/electrastore/assets/images/<?php echo $product['image_url'] ?: 'placeholder.jpg'; ?>" 
                         style="width: 50px; height: 50px; object-fit: cover;">
                </td>
                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                <td>₱<?php echo number_format($product['price'], 2); ?></td>
                <td style="color: <?php echo $product['stock'] < 10 ? '#e74c3c' : '#27ae60'; ?>; font-weight: bold;">
                    <?php echo $product['stock']; ?> units
                    <?php if ($product['stock'] < 10): ?>
                        <span style="color: #e74c3c;">⚠️ Low Stock</span>
                    <?php endif; ?>
                </td>
                <td>
                    <form method="POST" action="" style="display: flex; gap: 0.5rem;">
                        <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                        <input type="number" name="stock" value="<?php echo $product['stock']; ?>" 
                               style="width: 80px; padding: 0.3rem;" required>
                        <button type="submit" name="update_stock" class="edit-btn">Update</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
require_once '../includes/footer.php';
?>