<?php
require_once '../includes/header.php';
requireAdmin();

$conn = getConnection();

// Get products
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
        <a href="/app/electrastore/admin/index.php">Dashboard</a>
        <a href="/app/electrastore/admin/products.php">Manage Products</a>
        <a href="/app/electrastore/admin/categories.php">Manage Categories</a>
        <a href="/app/electrastore/admin/orders.php">Manage Orders</a>
        <a href="/app/electrastore/admin/users.php">Manage Users</a>
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
    
    <div style="margin: 1rem 0;">
        <a href="add_product.php" class="btn btn-primary">Add New Product</a>
    </div>
    
    <?php if (count($products) == 0): ?>
        <p>No products found. Click "Add New Product" to get started.</p>
    <?php else: ?>
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
                        <?php 
                        $image_path = !empty($product['image_url']) ? $product['image_url'] : 'placeholder.jpg';
                        ?>
                        <img src="/app/electrastore/assets/images/products/<?php echo htmlspecialchars($image_path); ?>" 
                             style="width: 50px; height: 50px; object-fit: cover;"
                             onerror="this.src='/app/electrastore/assets/images/products/placeholder.jpg'">
                    </td>
                    <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                    <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                    <td>₱<?php echo number_format($product['price'], 2); ?></td>
                    <td><?php echo $product['stock']; ?></td>
                    <td>
                        <a href="edit_product.php?id=<?php echo $product['product_id']; ?>" class="edit-btn">Edit</a>
                        <a href="delete_product.php?id=<?php echo $product['product_id']; ?>" 
                           class="delete-btn">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php
require_once '../includes/footer.php';
?>