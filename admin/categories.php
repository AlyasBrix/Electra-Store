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

// Handle Add Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name']);
    $description = trim($_POST['description']);
    
    if (empty($category_name)) {
        $_SESSION['message'] = 'Category name is required';
        $_SESSION['message_type'] = 'error';
    } else {
        try {
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

// Now include header AFTER all redirect logic
require_once '../includes/header.php';
requireAdmin();

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

<div class="categories-admin">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-left">
                <h1>Manage Categories</h1>
                <p>Organize your products into categories</p>
            </div>
            <div class="header-right">
                <button onclick="openAddModal()" class="btn-add-category">
                    <i class="fas fa-plus"></i> Add New Category
                </button>
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
            <a href="/app/electrastore/admin/categories.php" class="nav-link active">
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
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert-message <?php echo $_SESSION['message_type']; ?>">
                <i class="fas <?php echo $_SESSION['message_type'] == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <?php 
                echo htmlspecialchars($_SESSION['message']);
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Categories Table -->
        <?php if (count($categories) == 0): ?>
            <div class="empty-state">
                <i class="fas fa-folder-open"></i>
                <h3>No Categories Found</h3>
                <p>Click "Add New Category" to get started with organizing your products.</p>
                <button onclick="openAddModal()" class="btn btn-primary">Create First Category</button>
            </div>
        <?php else: ?>
            <div class="categories-table-wrapper">
                <table class="categories-table">
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
                            <td class="category-id">#<?php echo str_pad($category['category_id'], 3, '0', STR_PAD_LEFT); ?></td>
                            <td class="category-name">
                                <div class="category-name-wrapper">
                                    <div class="category-icon">
                                        <i class="fas fa-tag"></i>
                                    </div>
                                    <strong><?php echo htmlspecialchars($category['category_name']); ?></strong>
                                </div>
                            </td>
                            <td class="category-description">
                                <?php echo htmlspecialchars($category['description'] ?? '—'); ?>
                            </td>
                            <td class="product-count">
                                <a href="/app/electrastore/admin/products.php?category=<?php echo $category['category_id']; ?>" class="product-count-link">
                                    <i class="fas fa-box"></i>
                                    <?php echo $category['product_count']; ?> product(s)
                                </a>
                            </td>
                            <td class="created-date">
                                <i class="fas fa-calendar-alt"></i>
                                <?php echo date('M j, Y', strtotime($category['created_at'] ?? 'now')); ?>
                            </td>
                            <td class="actions">
                                <a href="?edit=<?php echo $category['category_id']; ?>" class="action-btn edit-btn" title="Edit Category">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($category['product_count'] == 0): ?>
                                    <a href="?delete=<?php echo $category['category_id']; ?>" 
                                       class="action-btn delete-btn" 
                                       onclick="return confirm('Are you sure you want to delete this category? This action cannot be undone.')"
                                       title="Delete Category">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="action-btn disabled-btn" title="Cannot delete category with products">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Category Statistics -->
        <div class="stats-section">
            <h3>
                <i class="fas fa-chart-pie"></i>
                Category Statistics
            </h3>
            <div class="stats-grid-categories">
                <div class="stat-card-category">
                    <div class="stat-icon total">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="stat-details">
                        <span class="stat-value"><?php echo count($categories); ?></span>
                        <span class="stat-label">Total Categories</span>
                    </div>
                </div>
                <div class="stat-card-category">
                    <div class="stat-icon active">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-details">
                        <span class="stat-value">
                            <?php 
                            $active_categories = 0;
                            foreach ($categories as $cat) {
                                if ($cat['product_count'] > 0) $active_categories++;
                            }
                            echo $active_categories;
                            ?>
                        </span>
                        <span class="stat-label">Categories with Products</span>
                    </div>
                </div>
                <div class="stat-card-category">
                    <div class="stat-icon empty">
                        <i class="fas fa-folder-open"></i>
                    </div>
                    <div class="stat-details">
                        <span class="stat-value"><?php echo count($categories) - $active_categories; ?></span>
                        <span class="stat-label">Empty Categories</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div id="addCategoryModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>
                <i class="fas fa-plus-circle"></i>
                Add New Category
            </h2>
            <button onclick="closeAddModal()" class="modal-close">&times;</button>
        </div>
        
        <form method="POST" action="">
            <div class="modal-body">
                <div class="form-group">
                    <label>
                        <i class="fas fa-tag"></i>
                        Category Name *
                    </label>
                    <input type="text" name="category_name" required placeholder="e.g., Smartphones, Laptops, Accessories">
                </div>
                
                <div class="form-group">
                    <label>
                        <i class="fas fa-align-left"></i>
                        Description
                    </label>
                    <textarea name="description" rows="4" placeholder="Optional: Describe what this category includes"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="add_category" class="btn-submit">
                    <i class="fas fa-save"></i> Add Category
                </button>
                <button type="button" onclick="closeAddModal()" class="btn-cancel">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Category Modal -->
<?php if ($edit_category): ?>
<div id="editCategoryModal" class="modal show">
    <div class="modal-content">
        <div class="modal-header">
            <h2>
                <i class="fas fa-edit"></i>
                Edit Category
            </h2>
            <a href="categories.php" class="modal-close">&times;</a>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="category_id" value="<?php echo $edit_category['category_id']; ?>">
            
            <div class="modal-body">
                <div class="form-group">
                    <label>
                        <i class="fas fa-tag"></i>
                        Category Name *
                    </label>
                    <input type="text" name="category_name" value="<?php echo htmlspecialchars($edit_category['category_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>
                        <i class="fas fa-align-left"></i>
                        Description
                    </label>
                    <textarea name="description" rows="4"><?php echo htmlspecialchars($edit_category['description'] ?? ''); ?></textarea>
                </div>
                
                <?php 
                $check = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
                $check->execute([$edit_category['category_id']]);
                $result = $check->fetch();
                $product_count = isset($result['count']) ? $result['count'] : 0;
                ?>
                
                <?php if ($product_count > 0): ?>
                <div class="warning-note">
                    <i class="fas fa-info-circle"></i>
                    This category has <strong><?php echo $product_count; ?> product(s)</strong>. Changing the category name will affect these products.
                </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="submit" name="edit_category" class="btn-submit">
                    <i class="fas fa-save"></i> Update Category
                </button>
                <a href="categories.php" class="btn-cancel">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<style>
.categories-admin {
    padding: 2rem 0;
    background: linear-gradient(135deg, #f5f7fa 0%, #f8fafc 100%);
    min-height: 100vh;
}

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

.btn-add-category {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, #4361ee, #7209b7);
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-add-category:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(67, 97, 238, 0.3);
}

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

.categories-table-wrapper {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    margin-bottom: 2rem;
}

.categories-table {
    width: 100%;
    border-collapse: collapse;
}

.categories-table th,
.categories-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
}

.categories-table th {
    background: #f8fafc;
    font-weight: 600;
    color: #1e2a3e;
    font-size: 0.85rem;
}

.categories-table tr:hover {
    background: #f8fafc;
}

.category-id {
    font-weight: 600;
    color: #4361ee;
    font-family: monospace;
}

.category-name-wrapper {
    display: flex;
    align-items: center;
    gap: 10px;
}

.category-icon {
    width: 32px;
    height: 32px;
    background: linear-gradient(135deg, #4361ee, #7209b7);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.category-icon i {
    font-size: 0.8rem;
    color: white;
}

.product-count-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 0.25rem 0.75rem;
    background: #f1f5f9;
    color: #4361ee;
    text-decoration: none;
    border-radius: 20px;
    font-size: 0.8rem;
    transition: all 0.3s ease;
}

.product-count-link:hover {
    background: #4361ee;
    color: white;
}

.created-date {
    font-size: 0.85rem;
    color: #64748b;
}

.created-date i {
    margin-right: 5px;
}

.actions {
    display: flex;
    gap: 0.5rem;
}

.action-btn {
    width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.3s ease;
}

.edit-btn {
    background: #dbeafe;
    color: #2563eb;
}

.edit-btn:hover {
    background: #2563eb;
    color: white;
    transform: translateY(-2px);
}

.delete-btn {
    background: #fee2e2;
    color: #dc2626;
}

.delete-btn:hover {
    background: #dc2626;
    color: white;
    transform: translateY(-2px);
}

.disabled-btn {
    background: #e2e8f0;
    color: #94a3b8;
    cursor: not-allowed;
}

.stats-section {
    background: white;
    border-radius: 20px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.stats-section h3 {
    font-size: 1rem;
    font-weight: 600;
    color: #1e2a3e;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

.stats-section h3 i {
    color: #4361ee;
}

.stats-grid-categories {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.stat-card-category {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 16px;
    transition: all 0.3s ease;
}

.stat-card-category:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
}

.stat-icon.total {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
}

.stat-icon.active {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.stat-icon.empty {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
}

.stat-details {
    flex: 1;
}

.stat-value {
    display: block;
    font-size: 1.5rem;
    font-weight: 800;
    color: #1e2a3e;
}

.stat-label {
    font-size: 0.75rem;
    color: #64748b;
}

.empty-state {
    text-align: center;
    padding: 4rem;
    background: white;
    border-radius: 20px;
}

.empty-state i {
    font-size: 4rem;
    color: #cbd5e1;
    margin-bottom: 1rem;
}

.empty-state h3 {
    font-size: 1.2rem;
    color: #1e2a3e;
    margin-bottom: 0.5rem;
}

.empty-state p {
    color: #64748b;
    margin-bottom: 1.5rem;
}

.modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal.show {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 24px;
    max-width: 500px;
    width: 90%;
    animation: modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
}

.modal-header h2 {
    font-size: 1.2rem;
    font-weight: 600;
    color: #1e2a3e;
    display: flex;
    align-items: center;
    gap: 8px;
}

.modal-header h2 i {
    color: #4361ee;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: #64748b;
    text-decoration: none;
}

.modal-close:hover {
    color: #1e2a3e;
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    padding: 1rem 1.5rem 1.5rem;
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
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
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 0.7rem;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    font-family: inherit;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: #4361ee;
}

.btn-submit {
    padding: 0.7rem 1.5rem;
    background: linear-gradient(135deg, #4361ee, #7209b7);
    color: white;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
}

.btn-cancel {
    padding: 0.7rem 1.5rem;
    background: #f1f5f9;
    color: #64748b;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
}

.btn-cancel:hover {
    background: #e2e8f0;
    color: #1e2a3e;
}

.warning-note {
    background: #fef3c7;
    padding: 0.75rem;
    border-radius: 10px;
    font-size: 0.8rem;
    color: #d97706;
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 1rem;
}

@media (max-width: 768px) {
    .categories-admin {
        padding: 1rem 0;
    }
    
    .header-left h1 {
        font-size: 1.5rem;
    }
    
    .categories-table {
        font-size: 0.85rem;
    }
    
    .categories-table th,
    .categories-table td {
        padding: 0.75rem;
    }
    
    .stats-grid-categories {
        grid-template-columns: 1fr;
    }
    
    .modal-content {
        width: 95%;
    }
}
</style>

<script>
function openAddModal() {
    document.getElementById('addCategoryModal').style.display = 'flex';
}

function closeAddModal() {
    document.getElementById('addCategoryModal').style.display = 'none';
}

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