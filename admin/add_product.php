<?php
require_once '../includes/header.php';
requireAdmin();

$conn = getConnection();
$error = '';
$success = '';

// Get categories
$cat_stmt = $conn->query("SELECT * FROM categories ORDER BY category_name");
$categories = $cat_stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = (int)$_POST['category_id'];
    $product_name = trim($_POST['product_name']);
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $brand = trim($_POST['brand']);
    $warranty = trim($_POST['warranty']);
    $image_url = trim($_POST['image_url']);
    
    if (empty($product_name) || $price <= 0) {
        $error = 'Please fill in all required fields';
    } else {
        $stmt = $conn->prepare("INSERT INTO products (category_id, product_name, description, price, stock, brand, warranty, image_url) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        if ($stmt->execute([$category_id, $product_name, $description, $price, $stock, $brand, $warranty, $image_url])) {
            $success = 'Product added successfully!';
            // Clear form
            $product_name = $description = $brand = $warranty = $image_url = '';
            $category_id = $price = $stock = 0;
        } else {
            $error = 'Failed to add product';
        }
    }
}
?>

<div class="form-container" style="max-width: 800px;">
    <h2>Add New Product</h2>
    
    <div class="admin-menu">
        <a href="/electrastore/admin/index.php">Dashboard</a>
        <a href="/electrastore/admin/products.php">Manage Products</a>
        <a href="/electrastore/admin/orders.php">Manage Orders</a>
    </div>
    
    <?php if ($error): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label>Category *</label>
            <select name="category_id" required>
                <option value="">Select Category</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['category_id']; ?>" <?php echo isset($category_id) && $category_id == $cat['category_id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat['category_name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>Product Name *</label>
            <input type="text" name="product_name" value="<?php echo htmlspecialchars($product_name ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label>Description</label>
            <textarea name="description"><?php echo htmlspecialchars($description ?? ''); ?></textarea>
        </div>
        
        <div class="form-group">
            <label>Price (₱) *</label>
            <input type="number" step="0.01" name="price" value="<?php echo $price ?? 0; ?>" required>
        </div>
        
        <div class="form-group">
            <label>Stock Quantity *</label>
            <input type="number" name="stock" value="<?php echo $stock ?? 0; ?>" required>
        </div>
        
        <div class="form-group">
            <label>Brand</label>
            <input type="text" name="brand" value="<?php echo htmlspecialchars($brand ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label>Warranty</label>
            <input type="text" name="warranty" value="<?php echo htmlspecialchars($warranty ?? ''); ?>" placeholder="e.g., 1 Year">
        </div>
        
        <div class="form-group">
            <label>Image URL</label>
            <input type="text" name="image_url" value="<?php echo htmlspecialchars($image_url ?? ''); ?>" placeholder="filename.jpg">
            <small>Place images in /assets/images/ folder</small>
        </div>
        
        <button type="submit" class="btn btn-primary">Add Product</button>
        <a href="products.php" class="btn">Cancel</a>
    </form>
</div>

<?php
require_once '../includes/footer.php';
?>