<?php
require_once '../includes/header.php';
requireAdmin();

$conn = getConnection();

// Delete product
if (isset($_GET['delete'])) {
    $product_id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    header('Location: products.php?deleted=1');
    exit();
}

$stmt = $conn->prepare("SELECT p.*, c.category_name FROM products p 
                        LEFT JOIN categories c ON p.category_id = c.category_id 
                        ORDER BY p.created_at DESC");
$stmt->execute();
$products = $stmt->fetchAll();

// Get categories for filter
$cat_stmt = $conn->query("SELECT * FROM categories ORDER BY category_name");
$categories = $cat_stmt->fetchAll();
?>

<div class="container">
    <h2>Manage Products</h2>
    
    <div class="admin-menu">
        <a href="/electrastore/admin/index.php">Dashboard</a>
        <a href="/electrastore/admin/products.php">Manage Products</a>
        <a href="/electrastore/admin/orders.php">Manage Orders</a>
    </div>
    
    <?php if (isset($_GET['deleted'])): ?>
        <div class="success-message">Product deleted successfully!</div>
    <?php endif; ?>
    
    <div style="margin: 1rem 0;">
        <a href="add_product.php" class="btn btn-primary">Add New Product</a>
    </div>
    
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Image</th>
                <th>Name</th>
                <th>Category</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
            <tr>
                <td><?php echo $product['product_id']; ?></td>
                <td>
                    <img src="/electrastore/assets/images/products/<?php echo $product['image_url'] ?: 'placeholder.jpg'; ?>" 
                         style="width: 50px; height: 50px; object-fit: cover;">
                </td>
                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                <td>₱<?php echo number_format($product['price'], 2); ?></td>
                <td><?php echo $product['stock']; ?></td>
                <td>
                    <a href="edit_product.php?id=<?php echo $product['product_id']; ?>" class="edit-btn">Edit</a>
                    <a href="?delete=<?php echo $product['product_id']; ?>" class="delete-btn" onclick="return confirmDelete()">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
require_once '../includes/footer.php';
?>