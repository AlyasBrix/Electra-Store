<?php
require_once '../includes/header.php';
requireAdmin();

$conn = getConnection();

// Handle Add Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name']);
    $description = trim($_POST['description']);
    
    if (empty($category_name)) {
        $_SESSION['message'] = 'Category name is required';
        $_SESSION['message_type'] = 'error';
    } else {
        try {
            // Check if category already exists
            $check = $conn->prepare("SELECT category_id FROM categories WHERE category_name = ?");
            $check->execute([$category_name]);
            
            if ($check->fetch()) {
                $_SESSION['message'] = 'Category already exists!';
                $_SESSION['message_type'] = 'error';
            } else {
                $stmt = $conn->prepare("INSERT INTO categories (category_name, description) VALUES (?, ?)");
                if ($stmt->execute([$category_name, $description])) {
                    $_SESSION['message'] = "Category added successfully!";
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = 'Failed to add category';
                    $_SESSION['message_type'] = 'error';
                }
            }
        } catch (PDOException $e) {
            $_SESSION['message'] = 'Database error: ' . $e->getMessage();
            $_SESSION['message_type'] = 'error';
        }
    }
    header('Location: categories.php');
    exit();
}

// Handle Edit Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    $category_id = (int)$_POST['category_id'];
    $category_name = trim($_POST['category_name']);
    $description = trim($_POST['description']);
    
    if (empty($category_name)) {
        $_SESSION['message'] = 'Category name is required';
        $_SESSION['message_type'] = 'error';
    } else {
        try {
            // Check if another category has the same name
            $check = $conn->prepare("SELECT category_id FROM categories WHERE category_name = ? AND category_id != ?");
            $check->execute([$category_name, $category_id]);
            
            if ($check->fetch()) {
                $_SESSION['message'] = 'Another category with this name already exists!';
                $_SESSION['message_type'] = 'error';
            } else {
                $stmt = $conn->prepare("UPDATE categories SET category_name = ?, description = ? WHERE category_id = ?");
                if ($stmt->execute([$category_name, $description, $category_id])) {
                    $_SESSION['message'] = "Category updated successfully!";
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['message'] = 'Failed to update category';
                    $_SESSION['message_type'] = 'error';
                }
            }
        } catch (PDOException $e) {
            $_SESSION['message'] = 'Database error: ' . $e->getMessage();
            $_SESSION['message_type'] = 'error';
        }
    }
    header('Location: categories.php');
    exit();
}

// Handle Delete Category
if (isset($_GET['delete'])) {
    $category_id = (int)$_GET['delete'];
    
    try {
        // Check if category has products
        $check = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
        $check->execute([$category_id]);
        $result = $check->fetch();
        $product_count = isset($result['count']) ? $result['count'] : 0;
        
        if ($product_count > 0) {
            $_SESSION['message'] = "Cannot delete category. It has $product_count product(s) assigned to it. Please reassign or delete those products first.";
            $_SESSION['message_type'] = 'error';
        } else {
            $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
            if ($stmt->execute([$category_id])) {
                $_SESSION['message'] = "Category deleted successfully!";
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'Failed to delete category';
                $_SESSION['message_type'] = 'error';
            }
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = 'Database error: ' . $e->getMessage();
        $_SESSION['message_type'] = 'error';
    }
    header('Location: categories.php');
    exit();
}

// Get all categories with product count
$stmt = $conn->prepare("SELECT c.*, COUNT(p.product_id) as product_count 
                        FROM categories c 
                        LEFT JOIN products p ON c.category_id = p.category_id 
                        GROUP BY c.category_id 
                        ORDER BY c.category_name");
$stmt->execute();
$categories = $stmt->fetchAll();

// Get category for editing
$edit_category = null;
if (isset($_GET['edit'])) {
    $category_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM categories WHERE category_id = ?");
    $stmt->execute([$category_id]);
    $edit_category = $stmt->fetch();
}
?>

<div class="container">
    <h2>Manage Categories</h2>
    
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
    
    <!-- Add Category Button -->
    <div style="margin: 1rem 0;">
        <button onclick="openAddModal()" class="btn btn-primary">+ Add New Category</button>
    </div>
    
    <!-- Categories Table -->
    <?php if (count($categories) == 0): ?>
        <p>No categories found. Click "Add New Category" to get started.</p>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Category Name</th>
                    <th>Description</th>
                    <th>Products</th>
                    <th>Created Date</th>
                    <th>Actions</th>
                 </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                 <tr>
                    <td><?php echo $category['category_id']; ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($category['category_name']); ?></strong>
                    </td>
                    <td><?php echo htmlspecialchars($category['description'] ?? 'No description'); ?></td>
                    <td>
                        <a href="/app/electrastore/admin/products.php?category=<?php echo $category['category_id']; ?>" 
                           style="color: #3498db;">
                            <?php echo $category['product_count']; ?> products
                        </a>
                    </td>
                    <td><?php echo date('M j, Y', strtotime($category['created_at'] ?? 'now')); ?></td>
                    <td>
                        <a href="?edit=<?php echo $category['category_id']; ?>" class="edit-btn">Edit</a>
                        <?php if ($category['product_count'] == 0): ?>
                            <a href="?delete=<?php echo $category['category_id']; ?>" 
                               class="delete-btn" 
                               onclick="return confirm('Are you sure you want to delete this category? This action cannot be undone.')">Delete</a>
                        <?php else: ?>
                            <span class="delete-btn-disabled" style="background-color: #95a5a6; color: white; padding: 0.2rem 0.5rem; border-radius: 3px; cursor: not-allowed;" 
                                  title="Cannot delete category with products">Cannot Delete</span>
                        <?php endif; ?>
                    </td>
                 </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    
    <!-- Category Stats -->
    <div style="margin-top: 2rem; background: white; border-radius: 10px; padding: 1.5rem;">
        <h3>Category Statistics</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <div style="text-align: center;">
                <h4>Total Categories</h4>
                <p style="font-size: 2rem; color: #3498db;"><?php echo count($categories); ?></p>
            </div>
            <div style="text-align: center;">
                <h4>Categories with Products</h4>
                <p style="font-size: 2rem; color: #27ae60;">
                    <?php 
                    $active_categories = 0;
                    foreach ($categories as $cat) {
                        if ($cat['product_count'] > 0) $active_categories++;
                    }
                    echo $active_categories;
                    ?>
                </p>
            </div>
            <div style="text-align: center;">
                <h4>Empty Categories</h4>
                <p style="font-size: 2rem; color: #e74c3c;">
                    <?php echo count($categories) - $active_categories; ?>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div id="addCategoryModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 10px; padding: 2rem; max-width: 500px; width: 90%;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2 style="margin: 0;">Add New Category</h2>
            <button onclick="closeAddModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Category Name *</label>
                <input type="text" name="category_name" required placeholder="e.g., Smartphones, Laptops, Accessories">
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3" placeholder="Optional: Describe what this category includes"></textarea>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
                <button type="button" onclick="closeAddModal()" class="btn">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Category Modal -->
<?php if ($edit_category): ?>
<div id="editCategoryModal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; display: flex; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 10px; padding: 2rem; max-width: 500px; width: 90%;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2 style="margin: 0;">Edit Category</h2>
            <a href="categories.php" style="background: none; border: none; font-size: 1.5rem; text-decoration: none; cursor: pointer;">&times;</a>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="category_id" value="<?php echo $edit_category['category_id']; ?>">
            
            <div class="form-group">
                <label>Category Name *</label>
                <input type="text" name="category_name" value="<?php echo htmlspecialchars($edit_category['category_name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3"><?php echo htmlspecialchars($edit_category['description'] ?? ''); ?></textarea>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                <button type="submit" name="edit_category" class="btn btn-primary">Update Category</button>
                <a href="categories.php" class="btn">Cancel</a>
            </div>
        </form>
        
        <?php 
        // Check if category has products
        $check = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
        $check->execute([$edit_category['category_id']]);
        $result = $check->fetch();
        $product_count = isset($result['count']) ? $result['count'] : 0;
        ?>
        
        <?php if ($product_count > 0): ?>
        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #ddd;">
            <p style="color: #e74c3c; font-size: 0.9rem;">
                <strong>Note:</strong> This category has <?php echo $product_count; ?> product(s). Changing the category name will affect these products.
            </p>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<script>
function openAddModal() {
    document.getElementById('addCategoryModal').style.display = 'flex';
}

function closeAddModal() {
    document.getElementById('addCategoryModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const addModal = document.getElementById('addCategoryModal');
    if (event.target == addModal) {
        addModal.style.display = 'none';
    }
}
</script>

<?php
require_once '../includes/footer.php';
?>