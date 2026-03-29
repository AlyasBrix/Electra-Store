<?php
require_once '../includes/header.php';
requireAdmin();

$conn = getConnection();

$error = '';
$success = '';
$product = null;

// Get product ID
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    $_SESSION['message'] = "Invalid product ID";
    $_SESSION['message_type'] = 'error';
    header('Location: products.php');
    exit();
}

// Get product details
$stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    $_SESSION['message'] = "Product not found";
    $_SESSION['message_type'] = 'error';
    header('Location: products.php');
    exit();
}

// Get categories
$cat_stmt = $conn->query("SELECT * FROM categories ORDER BY category_name");
$categories = $cat_stmt->fetchAll();

// Handle form submission
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
        try {
            $stmt = $conn->prepare("UPDATE products SET 
                                    category_id = ?, 
                                    product_name = ?, 
                                    description = ?, 
                                    price = ?, 
                                    stock = ?, 
                                    brand = ?, 
                                    warranty = ?, 
                                    image_url = ? 
                                    WHERE product_id = ?");
            
            if ($stmt->execute([$category_id, $product_name, $description, $price, $stock, $brand, $warranty, $image_url, $product_id])) {
                $_SESSION['message'] = "Product updated successfully!";
                $_SESSION['message_type'] = 'success';
                header("Location: products.php");
                exit();
            } else {
                $error = 'Failed to update product';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<div class="form-container" style="max-width: 800px;">
    <h2>Edit Product</h2>
    
    <div class="admin-menu">
        <a href="/app/electrastore/admin/index.php">Dashboard</a>
        <a href="/app/electrastore/admin/products.php">Manage Products</a>
        <a href="/app/electrastore/admin/categories.php">Manage Categories</a>
        <a href="/app/electrastore/admin/orders.php">Manage Orders</a>
        <a href="/app/electrastore/admin/users.php">Manage Users</a>
    </div>
    
    <?php if ($error): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label>Category *</label>
            <select name="category_id" required>
                <option value="">Select Category</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['category_id']; ?>" <?php echo $product['category_id'] == $cat['category_id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat['category_name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>Product Name *</label>
            <input type="text" name="product_name" value="<?php echo htmlspecialchars($product['product_name']); ?>" required>
        </div>
        
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="5"><?php echo htmlspecialchars($product['description']); ?></textarea>
        </div>
        
        <div class="form-group">
            <label>Price (₱) *</label>
            <input type="number" step="0.01" name="price" value="<?php echo $product['price']; ?>" required>
        </div>
        
        <div class="form-group">
            <label>Stock Quantity *</label>
            <input type="number" name="stock" value="<?php echo $product['stock']; ?>" required>
        </div>
        
        <div class="form-group">
            <label>Brand</label>
            <input type="text" name="brand" value="<?php echo htmlspecialchars($product['brand']); ?>">
        </div>
        
        <div class="form-group">
            <label>Warranty</label>
            <input type="text" name="warranty" value="<?php echo htmlspecialchars($product['warranty']); ?>" placeholder="e.g., 1 Year">
        </div>
        
        <div class="form-group">
            <label>Image URL</label>
            <input type="text" name="image_url" value="<?php echo htmlspecialchars($product['image_url']); ?>" placeholder="filename.jpg">
            <small>Place images in /assets/images/products/ folder</small>
        </div>
        
        <div style="display: flex; gap: 1rem;">
            <button type="submit" class="btn btn-primary">Update Product</button>
            <a href="products.php" class="btn">Cancel</a>
        </div>
    </form>
</div>

<?php
require_once '../includes/footer.php';
?>