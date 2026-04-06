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

// Handle user actions
$action = isset($_GET['action']) ? $_GET['action'] : '';
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Add new user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    $error = '';
    
    // Validate
    if (empty($full_name) || empty($email) || empty($password)) {
        $_SESSION['message'] = 'Please fill in all required fields';
        $_SESSION['message_type'] = 'error';
    } else {
        // Check if email exists
        $check = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $_SESSION['message'] = 'Email already exists';
            $_SESSION['message_type'] = 'error';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role, phone, address, status) 
                                    VALUES (?, ?, ?, ?, ?, ?, 'active')");
            if ($stmt->execute([$full_name, $email, $hashed_password, $role, $phone, $address])) {
                $_SESSION['message'] = "User added successfully!";
                $_SESSION['message_type'] = 'success';
                header('Location: users.php');
                exit();
            } else {
                $_SESSION['message'] = 'Failed to add user';
                $_SESSION['message_type'] = 'error';
            }
        }
    }
}

// Edit user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $user_id = (int)$_POST['user_id'];
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, role = ?, phone = ?, address = ?, status = ? WHERE user_id = ?");
    if ($stmt->execute([$full_name, $email, $role, $phone, $address, $status, $user_id])) {
        $_SESSION['message'] = "User updated successfully!";
        $_SESSION['message_type'] = 'success';
        header('Location: users.php');
        exit();
    } else {
        $_SESSION['message'] = "Failed to update user";
        $_SESSION['message_type'] = 'error';
    }
}

// Reset password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $user_id = (int)$_POST['user_id'];
    $new_password = $_POST['new_password'];
    
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    if ($stmt->execute([$hashed_password, $user_id])) {
        $_SESSION['message'] = "Password reset successfully!";
        $_SESSION['message_type'] = 'success';
        header('Location: users.php');
        exit();
    } else {
        $_SESSION['message'] = "Failed to reset password";
        $_SESSION['message_type'] = 'error';
    }
}

// Soft delete (deactivate) - set inactive
if (isset($_GET['deactivate'])) {
    $user_id = (int)$_GET['deactivate'];
    
    // Don't allow deactivating own account
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['message'] = "You cannot deactivate your own account!";
        $_SESSION['message_type'] = 'error';
    } else {
        $stmt = $conn->prepare("UPDATE users SET status = 'inactive' WHERE user_id = ?");
        if ($stmt->execute([$user_id])) {
            $_SESSION['message'] = "User deactivated successfully!";
            $_SESSION['message_type'] = 'success';
        }
    }
    header('Location: users.php');
    exit();
}

// Permanent delete (hard delete) - completely remove from database
if (isset($_GET['permanent_delete'])) {
    $user_id = (int)$_GET['permanent_delete'];
    
    // Don't allow deleting own account
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['message'] = "You cannot delete your own account!";
        $_SESSION['message_type'] = 'error';
    } else {
        // Check if user has orders
        $check_orders = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?");
        $check_orders->execute([$user_id]);
        $order_result = $check_orders->fetch();
        $order_count = isset($order_result['count']) ? $order_result['count'] : 0;
        
        if ($order_count > 0) {
            $_SESSION['message'] = "Cannot permanently delete user. This user has $order_count order(s). Deactivate instead.";
            $_SESSION['message_type'] = 'error';
        } else {
            // Delete from cart_items first (if any)
            $delete_cart = $conn->prepare("DELETE ci FROM cart_items ci 
                                           INNER JOIN cart c ON ci.cart_id = c.cart_id 
                                           WHERE c.user_id = ?");
            $delete_cart->execute([$user_id]);
            
            // Delete cart
            $delete_cart = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $delete_cart->execute([$user_id]);
            
            // Finally delete the user
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
            if ($stmt->execute([$user_id])) {
                $_SESSION['message'] = "User permanently deleted successfully!";
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = "Failed to delete user";
                $_SESSION['message_type'] = 'error';
            }
        }
    }
    header('Location: users.php');
    exit();
}

// Activate user
if (isset($_GET['activate'])) {
    $user_id = (int)$_GET['activate'];
    $stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE user_id = ?");
    if ($stmt->execute([$user_id])) {
        $_SESSION['message'] = "User activated successfully!";
        $_SESSION['message_type'] = 'success';
    }
    header('Location: users.php');
    exit();
}

// Get all users
$stmt = $conn->prepare("SELECT * FROM users ORDER BY role, full_name");
$stmt->execute();
$users = $stmt->fetchAll();

// Get user for editing - ONLY when edit parameter is present
$edit_user = null;
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $user_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $edit_user = $stmt->fetch();
}

// Get statistics
$total_users = count($users);
$admin_count = 0;
$staff_count = 0;
$customer_count = 0;
$active_count = 0;

foreach ($users as $user) {
    if ($user['role'] == 'admin') $admin_count++;
    if ($user['role'] == 'staff') $staff_count++;
    if ($user['role'] == 'user') $customer_count++;
    if (($user['status'] ?? 'active') == 'active') $active_count++;
}

// Now include header AFTER all redirect logic
require_once '../includes/header.php';
requireAdmin();
?>

<div class="users-admin">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-left">
                <h1>Manage Users</h1>
                <p>Manage customer, staff, and admin accounts</p>
            </div>
            <div class="header-right">
                <button onclick="openAddUserModal()" class="btn-add-user">
                    <i class="fas fa-user-plus"></i> Add New User
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
            <a href="/app/electrastore/admin/categories.php" class="nav-link">
                <i class="fas fa-tags"></i> Categories
            </a>
            <a href="/app/electrastore/admin/orders.php" class="nav-link">
                <i class="fas fa-shopping-cart"></i> Orders
            </a>
            <a href="/app/electrastore/admin/users.php" class="nav-link active">
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

        <!-- Statistics Cards -->
        <div class="stats-users-grid">
            <div class="stat-card-user total">
                <div class="stat-icon-user">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-details-user">
                    <span class="stat-value"><?php echo $total_users; ?></span>
                    <span class="stat-label">Total Users</span>
                </div>
            </div>
            <div class="stat-card-user active">
                <div class="stat-icon-user">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-details-user">
                    <span class="stat-value"><?php echo $active_count; ?></span>
                    <span class="stat-label">Active Users</span>
                </div>
            </div>
            <div class="stat-card-user customers">
                <div class="stat-icon-user">
                    <i class="fas fa-user"></i>
                </div>
                <div class="stat-details-user">
                    <span class="stat-value"><?php echo $customer_count; ?></span>
                    <span class="stat-label">Customers</span>
                </div>
            </div>
            <div class="stat-card-user staff">
                <div class="stat-icon-user">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="stat-details-user">
                    <span class="stat-value"><?php echo $staff_count; ?></span>
                    <span class="stat-label">Staff</span>
                </div>
            </div>
            <div class="stat-card-user admin">
                <div class="stat-icon-user">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="stat-details-user">
                    <span class="stat-value"><?php echo $admin_count; ?></span>
                    <span class="stat-label">Admins</span>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <?php if (count($users) == 0): ?>
            <div class="empty-state">
                <i class="fas fa-users-slash"></i>
                <h3>No Users Found</h3>
                <p>Click "Add New User" to create your first user account.</p>
            </div>
        <?php else: ?>
            <div class="users-table-wrapper">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Role</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td class="user-id">#<?php echo str_pad($user['user_id'], 4, '0', STR_PAD_LEFT); ?></td>
                            <td class="user-info">
                                <div class="user-avatar">
                                    <i class="fas fa-user-circle"></i>
                                </div>
                                <div class="user-details">
                                    <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                    <small><?php echo htmlspecialchars($user['email']); ?></small>
                                </div>
                            </td>
                            <td class="user-role">
                                <span class="role-badge role-<?php echo $user['role']; ?>">
                                    <i class="fas <?php 
                                        echo $user['role'] == 'admin' ? 'fa-shield-alt' : 
                                            ($user['role'] == 'staff' ? 'fa-user-tie' : 'fa-user'); 
                                    ?>"></i>
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td class="user-contact">
                                <?php if (!empty($user['phone'])): ?>
                                    <div><i class="fas fa-phone"></i> <?php echo htmlspecialchars($user['phone']); ?></div>
                                <?php else: ?>
                                    <div><i class="fas fa-phone"></i> <span class="na-text">N/A</span></div>
                                <?php endif; ?>
                            </td>
                            <td class="user-status">
                                <?php $status = $user['status'] ?? 'active'; ?>
                                <span class="status-badge status-<?php echo $status; ?>">
                                    <i class="fas <?php echo $status == 'active' ? 'fa-check-circle' : 'fa-ban'; ?>"></i>
                                    <?php echo ucfirst($status); ?>
                                </span>
                            </td>
                            <td class="last-login">
                                <i class="fas fa-clock"></i>
                                <?php echo isset($user['last_login']) && $user['last_login'] ? date('M j, Y', strtotime($user['last_login'])) : 'Never'; ?>
                            </td>
                            <td class="user-actions">
                                <a href="?edit=<?php echo $user['user_id']; ?>" class="action-btn edit-user" title="Edit User">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                    <?php if (($user['status'] ?? 'active') == 'active'): ?>
                                        <a href="?deactivate=<?php echo $user['user_id']; ?>" class="action-btn deactivate-user" 
                                           onclick="return confirm('Deactivate this user? The user can be activated later.')" title="Deactivate User">
                                            <i class="fas fa-user-slash"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="?activate=<?php echo $user['user_id']; ?>" class="action-btn activate-user" 
                                           title="Activate User">
                                            <i class="fas fa-user-check"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="?permanent_delete=<?php echo $user['user_id']; ?>" class="action-btn delete-user" 
                                       onclick="return confirm('⚠️ PERMANENT DELETE: This will completely remove this user from the database. This action cannot be undone! Are you sure?')" 
                                       title="Permanently Delete User">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add User Modal -->
<div id="addUserModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>
                <i class="fas fa-user-plus"></i>
                Add New User
            </h2>
            <button onclick="closeAddUserModal()" class="modal-close">&times;</button>
        </div>
        
        <form method="POST" action="">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>
                            <i class="fas fa-user"></i>
                            Full Name *
                        </label>
                        <input type="text" name="full_name" required placeholder="Enter full name">
                    </div>
                    <div class="form-group">
                        <label>
                            <i class="fas fa-envelope"></i>
                            Email *
                        </label>
                        <input type="email" name="email" required placeholder="Enter email address">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>
                            <i class="fas fa-lock"></i>
                            Password *
                        </label>
                        <input type="password" name="password" required placeholder="Enter password">
                    </div>
                    <div class="form-group">
                        <label>
                            <i class="fas fa-tag"></i>
                            Role *
                        </label>
                        <select name="role" required>
                            <option value="user">Customer</option>
                            <option value="staff">Staff</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>
                            <i class="fas fa-phone"></i>
                            Phone
                        </label>
                        <input type="text" name="phone" placeholder="Enter phone number">
                    </div>
                    <div class="form-group">
                        <label>
                            <i class="fas fa-map-marker-alt"></i>
                            Address
                        </label>
                        <textarea name="address" rows="2" placeholder="Enter address"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="add_user" class="btn-submit">
                    <i class="fas fa-save"></i> Add User
                </button>
                <button type="button" onclick="closeAddUserModal()" class="btn-cancel">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal - Only show when edit_user exists -->
<?php if ($edit_user): ?>
<div id="editUserModal" class="modal show">
    <div class="modal-content">
        <div class="modal-header">
            <h2>
                <i class="fas fa-user-edit"></i>
                Edit User
            </h2>
            <a href="users.php" class="modal-close">&times;</a>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="user_id" value="<?php echo $edit_user['user_id']; ?>">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label>
                            <i class="fas fa-user"></i>
                            Full Name
                        </label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($edit_user['full_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>
                            <i class="fas fa-envelope"></i>
                            Email
                        </label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($edit_user['email']); ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>
                            <i class="fas fa-tag"></i>
                            Role
                        </label>
                        <select name="role">
                            <option value="user" <?php echo ($edit_user['role'] ?? '') == 'user' ? 'selected' : ''; ?>>Customer</option>
                            <option value="staff" <?php echo ($edit_user['role'] ?? '') == 'staff' ? 'selected' : ''; ?>>Staff</option>
                            <option value="admin" <?php echo ($edit_user['role'] ?? '') == 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>
                            <i class="fas fa-toggle-on"></i>
                            Status
                        </label>
                        <select name="status">
                            <option value="active" <?php echo ($edit_user['status'] ?? 'active') == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($edit_user['status'] ?? 'active') == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>
                            <i class="fas fa-phone"></i>
                            Phone
                        </label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($edit_user['phone'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>
                            <i class="fas fa-map-marker-alt"></i>
                            Address
                        </label>
                        <textarea name="address" rows="2"><?php echo htmlspecialchars($edit_user['address'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <div class="password-reset-section">
                    <hr>
                    <h4><i class="fas fa-key"></i> Reset Password</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="new_password" id="new_password" placeholder="Enter new password">
                        </div>
                        <div class="form-group">
                            <label>Confirm Password</label>
                            <input type="password" id="confirm_password" placeholder="Confirm new password">
                        </div>
                    </div>
                    <button type="submit" name="reset_password" class="btn-reset-password" onclick="return validatePassword()">
                        <i class="fas fa-sync-alt"></i> Reset Password
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="edit_user" class="btn-submit">
                    <i class="fas fa-save"></i> Update User
                </button>
                <a href="users.php" class="btn-cancel">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<style>
.users-admin {
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

.btn-add-user {
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

.btn-add-user:hover {
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

.stats-users-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card-user {
    background: white;
    border-radius: 16px;
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
}

.stat-card-user:hover {
    transform: translateY(-4px);
}

.stat-icon-user {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.stat-card-user.total .stat-icon-user {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
}

.stat-card-user.active .stat-icon-user {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.stat-card-user.customers .stat-icon-user {
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
    color: white;
}

.stat-card-user.staff .stat-icon-user {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
}

.stat-card-user.admin .stat-icon-user {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

.stat-details-user {
    flex: 1;
}

.stat-value {
    display: block;
    font-size: 1.5rem;
    font-weight: 800;
    color: #1e2a3e;
}

.stat-label {
    font-size: 0.7rem;
    color: #64748b;
}

.users-table-wrapper {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.users-table {
    width: 100%;
    border-collapse: collapse;
}

.users-table th,
.users-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
}

.users-table th {
    background: #f8fafc;
    font-weight: 600;
    color: #1e2a3e;
    font-size: 0.85rem;
}

.users-table tr:hover {
    background: #f8fafc;
}

.user-id {
    font-weight: 600;
    color: #4361ee;
    font-family: monospace;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-avatar {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #4361ee, #7209b7);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.user-avatar i {
    font-size: 1.2rem;
    color: white;
}

.user-details strong {
    display: block;
    color: #1e2a3e;
    font-size: 0.9rem;
}

.user-details small {
    font-size: 0.7rem;
    color: #64748b;
}

.role-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.role-badge.role-admin {
    background: #fee2e2;
    color: #dc2626;
}

.role-badge.role-staff {
    background: #fef3c7;
    color: #d97706;
}

.role-badge.role-user {
    background: #dbeafe;
    color: #2563eb;
}

.user-contact {
    font-size: 0.8rem;
    color: #64748b;
}

.user-contact i {
    width: 20px;
}

.na-text {
    color: #94a3b8;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-badge.status-active {
    background: #d1fae5;
    color: #059669;
}

.status-badge.status-inactive {
    background: #fee2e2;
    color: #dc2626;
}

.last-login {
    font-size: 0.8rem;
    color: #64748b;
}

.last-login i {
    margin-right: 5px;
}

.user-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
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

.edit-user {
    background: #dbeafe;
    color: #2563eb;
}

.edit-user:hover {
    background: #2563eb;
    color: white;
    transform: translateY(-2px);
}

.deactivate-user {
    background: #fee2e2;
    color: #dc2626;
}

.deactivate-user:hover {
    background: #dc2626;
    color: white;
    transform: translateY(-2px);
}

.activate-user {
    background: #d1fae5;
    color: #059669;
}

.activate-user:hover {
    background: #059669;
    color: white;
    transform: translateY(-2px);
}

.delete-user {
    background: #fef3c7;
    color: #d97706;
}

.delete-user:hover {
    background: #d97706;
    color: white;
    transform: translateY(-2px);
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
}

/* Modal Styles */
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
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
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
    position: sticky;
    top: 0;
    background: white;
}

.modal-header h2 {
    font-size: 1.2rem;
    font-weight: 600;
    color: #1e2a3e;
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
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
    border-top: 1px solid #e2e8f0;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.form-group {
    margin-bottom: 0;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #1e2a3e;
    font-size: 0.8rem;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 0.7rem;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    font-family: inherit;
    transition: all 0.3s ease;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #4361ee;
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
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
    display: inline-flex;
    align-items: center;
    gap: 8px;
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
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-cancel:hover {
    background: #e2e8f0;
    color: #1e2a3e;
}

.btn-reset-password {
    padding: 0.6rem 1.2rem;
    background: #fef3c7;
    color: #d97706;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 0.5rem;
}

.btn-reset-password:hover {
    background: #f59e0b;
    color: white;
}

.password-reset-section {
    margin-top: 1rem;
}

.password-reset-section hr {
    margin: 1rem 0;
    border: none;
    border-top: 1px solid #e2e8f0;
}

.password-reset-section h4 {
    font-size: 0.9rem;
    color: #1e2a3e;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

@media (max-width: 1024px) {
    .stats-users-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 768px) {
    .users-admin {
        padding: 1rem 0;
    }
    
    .header-left h1 {
        font-size: 1.5rem;
    }
    
    .stats-users-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .users-table thead {
        display: none;
    }
    
    .users-table tbody tr {
        display: block;
        margin-bottom: 1rem;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 0.75rem;
    }
    
    .users-table td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
        border: none;
    }
    
    .users-table td::before {
        content: attr(data-label);
        font-weight: 600;
        color: #64748b;
        font-size: 0.75rem;
    }
    
    .user-actions {
        justify-content: flex-end;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .modal-content {
        width: 95%;
    }
}

@media (max-width: 480px) {
    .stats-users-grid {
        grid-template-columns: 1fr;
    }
    
    .stat-card-user {
        padding: 0.75rem;
    }
    
    .stat-value {
        font-size: 1.2rem;
    }
}
</style>

<script>
function openAddUserModal() {
    document.getElementById('addUserModal').style.display = 'flex';
}

function closeAddUserModal() {
    document.getElementById('addUserModal').style.display = 'none';
}

function validatePassword() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (newPassword !== confirmPassword) {
        alert('Passwords do not match!');
        return false;
    }
    
    if (newPassword.length < 6) {
        alert('Password must be at least 6 characters long!');
        return false;
    }
    
    return true;
}

window.onclick = function(event) {
    const addModal = document.getElementById('addUserModal');
    if (event.target == addModal) {
        addModal.style.display = 'none';
    }
}
</script>

<?php
require_once '../includes/footer.php';
?>