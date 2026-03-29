<?php
// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /app/electrastore/user/login.php');
    exit();
}

require_once '../config/database.php';

$conn = getConnection();
$error = '';
$success = '';

// Get categories
$cat_stmt = $conn->query("SELECT * FROM categories ORDER BY category_name");
$categories = $cat_stmt->fetchAll();

// Initialize variables
$category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
$product_name = '';
$description = '';
$price = 0;
$stock = 0;
$brand = '';
$warranty = '';
$image_url = '';

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
            $stmt = $conn->prepare("INSERT INTO products (category_id, product_name, description, price, stock, brand, warranty, image_url) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$category_id, $product_name, $description, $price, $stock, $brand, $warranty, $image_url])) {
                $success = 'Product added successfully!';
                // Clear form
                $product_name = $description = $brand = $warranty = $image_url = '';
                $category_id = 0;
                $price = 0;
                $stock = 0;
            } else {
                $error = 'Failed to add product';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Now include header AFTER all redirect logic
require_once '../includes/header.php';
requireAdmin();
?>

<div class="add-product-admin">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-left">
                <h1>Add New Product</h1>
                <p>Create a new product for your store</p>
            </div>
            <div class="header-right">
                <a href="products.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Products
                </a>
            </div>
        </div>

        <!-- Admin Navigation -->
        <div class="admin-nav">
            <a href="/app/electrastore/admin/index.php" class="nav-link">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="/app/electrastore/admin/products.php" class="nav-link">
                <i class="fas fa-box"></i> Products
            </a>
            <a href="/app/electrastore/admin/categories.php" class="nav-link">
                <i class="fas fa-tags"></i> Categories
            </a>
            <a href="/app/electrastore/admin/orders.php" class="nav-link">
                <i class="fas fa-shopping-cart"></i> Orders
            </a>
            <a href="/app/electrastore/admin/users.php" class="nav-link">
                <i class="fas fa-users"></i> Users
            </a>
        </div>

        <!-- Messages -->
        <?php if ($error): ?>
            <div class="alert-message error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert-message success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Add Product Form -->
        <div class="form-card">
            <form method="POST" action="" id="addProductForm">
                <div class="form-grid">
                    <!-- Left Column -->
                    <div class="form-column">
                        <div class="form-group">
                            <label>
                                <i class="fas fa-tag"></i>
                                Category Name *
                            </label>
                            <select name="category_id" required class="form-control">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['category_id']; ?>" <?php echo $category_id == $cat['category_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>
                                <i class="fas fa-box"></i>
                                Product Name *
                            </label>
                            <input type="text" name="product_name" value="<?php echo htmlspecialchars($product_name); ?>" required class="form-control" placeholder="Enter product name">
                        </div>

                        <div class="form-group">
                            <label>
                                <i class="fas fa-align-left"></i>
                                Description
                            </label>
                            <textarea name="description" rows="6" class="form-control" placeholder="Enter product description..."><?php echo htmlspecialchars($description); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>
                                <i class="fas fa-building"></i>
                                Brand
                            </label>
                            <input type="text" name="brand" value="<?php echo htmlspecialchars($brand); ?>" class="form-control" placeholder="e.g., Apple, Samsung, Sony">
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="form-column">
                        <div class="form-group">
                            <label>
                                <i class="fas fa-dollar-sign"></i>
                                Price (₱) *
                            </label>
                            <input type="number" step="0.01" name="price" value="<?php echo $price; ?>" required class="form-control" placeholder="0.00">
                        </div>

                        <div class="form-group">
                            <label>
                                <i class="fas fa-cubes"></i>
                                Stock Quantity *
                            </label>
                            <input type="number" name="stock" value="<?php echo $stock; ?>" required class="form-control" placeholder="0">
                        </div>

                        <div class="form-group">
                            <label>
                                <i class="fas fa-shield-alt"></i>
                                Warranty
                            </label>
                            <input type="text" name="warranty" value="<?php echo htmlspecialchars($warranty); ?>" class="form-control" placeholder="e.g., 1 Year, 6 Months">
                        </div>

                        <div class="form-group">
                            <label>
                                <i class="fas fa-image"></i>
                                Image URL
                            </label>
                            <input type="text" name="image_url" id="image_url" value="<?php echo htmlspecialchars($image_url); ?>" class="form-control" placeholder="filename.jpg">
                            <small class="form-hint">Place images in <strong>/assets/images/products/</strong> folder</small>
                        </div>

                        <div class="image-preview" id="imagePreview" style="display: none;">
                            <label>Image Preview</label>
                            <img id="previewImg" src="" alt="Product preview">
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> Add Product
                    </button>
                    <a href="products.php" class="btn-cancel">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>

        <!-- Tips Section -->
        <div class="tips-card">
            <h4>
                <i class="fas fa-lightbulb"></i>
                Product Creation Tips
            </h4>
            <div class="tips-grid">
                <div class="tip-item">
                    <i class="fas fa-tag"></i>
                    <div>
                        <strong>Clear Category</strong>
                        <p>Select the most relevant category for better discoverability</p>
                    </div>
                </div>
                <div class="tip-item">
                    <i class="fas fa-align-left"></i>
                    <div>
                        <strong>Detailed Description</strong>
                        <p>Provide comprehensive product information to help customers decide</p>
                    </div>
                </div>
                <div class="tip-item">
                    <i class="fas fa-image"></i>
                    <div>
                        <strong>Quality Images</strong>
                        <p>Use clear, high-quality images for better product presentation</p>
                    </div>
                </div>
                <div class="tip-item">
                    <i class="fas fa-tags"></i>
                    <div>
                        <strong>Competitive Pricing</strong>
                        <p>Research market prices to set competitive pricing</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.add-product-admin {
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

/* Admin Navigation */
.admin-nav {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    background: white;
    padding: 0.5rem;
    border-radius: 16px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.admin-nav .nav-link {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 0.75rem 1.25rem;
    text-decoration: none;
    color: #64748b;
    border-radius: 12px;
    transition: all 0.3s ease;
    font-weight: 500;
}

.admin-nav .nav-link i {
    font-size: 1rem;
}

.admin-nav .nav-link:hover {
    background: rgba(67, 97, 238, 0.08);
    color: #4361ee;
}

.admin-nav .nav-link.active {
    background: linear-gradient(135deg, #4361ee, #7209b7);
    color: white;
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

.alert-message.success {
    background: #d1fae5;
    color: #059669;
    border-left: 4px solid #059669;
}

.alert-message.error {
    background: #fee2e2;
    color: #dc2626;
    border-left: 4px solid #dc2626;
}

/* Form Card */
.form-card {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
}

.form-column {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-group {
    margin-bottom: 0;
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
    width: 18px;
}

.form-control {
    width: 100%;
    padding: 0.8rem 1rem;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    font-family: inherit;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.form-control:focus {
    outline: none;
    border-color: #4361ee;
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
}

select.form-control {
    cursor: pointer;
    background-color: white;
}

textarea.form-control {
    resize: vertical;
    min-height: 120px;
}

.form-hint {
    display: block;
    margin-top: 0.5rem;
    font-size: 0.7rem;
    color: #64748b;
}

.form-hint strong {
    color: #4361ee;
}

/* Image Preview */
.image-preview {
    margin-top: 1rem;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 12px;
    text-align: center;
}

.image-preview label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #1e2a3e;
    font-size: 0.8rem;
}

.image-preview img {
    max-width: 100%;
    max-height: 150px;
    border-radius: 10px;
    object-fit: cover;
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e2e8f0;
}

.btn-submit {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 0.8rem 2rem;
    background: linear-gradient(135deg, #4361ee, #7209b7);
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(67, 97, 238, 0.3);
}

.btn-cancel {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 0.8rem 2rem;
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

.tips-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1rem;
}

.tip-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 0.75rem;
    background: #f8fafc;
    border-radius: 12px;
    transition: all 0.3s ease;
}

.tip-item:hover {
    background: #f1f5f9;
    transform: translateX(5px);
}

.tip-item i {
    font-size: 1.2rem;
    color: #4361ee;
    margin-top: 2px;
}

.tip-item strong {
    display: block;
    font-size: 0.85rem;
    color: #1e2a3e;
    margin-bottom: 0.25rem;
}

.tip-item p {
    font-size: 0.75rem;
    color: #64748b;
    margin: 0;
}

/* Responsive */
@media (max-width: 1024px) {
    .form-grid {
        gap: 1.5rem;
    }
}

@media (max-width: 768px) {
    .add-product-admin {
        padding: 1rem 0;
    }
    
    .header-left h1 {
        font-size: 1.5rem;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .form-card {
        padding: 1.5rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn-submit,
    .btn-cancel {
        justify-content: center;
    }
    
    .tips-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .form-group label {
        font-size: 0.8rem;
    }
    
    .form-control {
        padding: 0.7rem;
        font-size: 0.85rem;
    }
}
</style>

<script>
// Image preview functionality
document.getElementById('image_url').addEventListener('input', function() {
    const imageUrl = this.value;
    const previewDiv = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    
    if (imageUrl) {
        previewImg.src = '/app/electrastore/assets/images/products/' + imageUrl;
        previewDiv.style.display = 'block';
        
        previewImg.onerror = function() {
            previewImg.src = '/app/electrastore/assets/images/products/placeholder.jpg';
        };
    } else {
        previewDiv.style.display = 'none';
    }
});
</script>

<?php
require_once '../includes/footer.php';
?>