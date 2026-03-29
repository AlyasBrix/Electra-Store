<?php
require_once '../includes/header.php';
requireAdmin();

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

// Delete user (soft delete - set inactive)
if (isset($_GET['delete'])) {
    $user_id = (int)$_GET['delete'];
    
    // Don't allow deleting own account
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['message'] = "You cannot delete your own account!";
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

// Get user for editing
$edit_user = null;
if (isset($_GET['edit'])) {
    $user_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $edit_user = $stmt->fetch();
}
?>

<div class="container">
    <h2>Manage Users</h2>
    
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
    
    <!-- Add User Form -->
    <div style="background: white; border-radius: 10px; padding: 1.5rem; margin-bottom: 2rem;">
        <h3>Add New User</h3>
        <form method="POST" action="" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="full_name" required>
            </div>
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Password *</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label>Role *</label>
                <select name="role" required>
                    <option value="user">Customer</option>
                    <option value="staff">Staff</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone">
            </div>
            <div class="form-group">
                <label>Address</label>
                <textarea name="address" rows="2"></textarea>
            </div>
            <div style="grid-column: span 2;">
                <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
            </div>
        </form>
    </div>
    
    <!-- Edit User Modal -->
    <?php if ($edit_user): ?>
    <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; display: flex; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 10px; padding: 2rem; max-width: 500px; width: 90%;">
            <h3>Edit User</h3>
            <form method="POST" action="">
                <input type="hidden" name="user_id" value="<?php echo $edit_user['user_id']; ?>">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($edit_user['full_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($edit_user['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role">
                        <option value="user" <?php echo isset($edit_user['role']) && $edit_user['role'] == 'user' ? 'selected' : ''; ?>>Customer</option>
                        <option value="staff" <?php echo isset($edit_user['role']) && $edit_user['role'] == 'staff' ? 'selected' : ''; ?>>Staff</option>
                        <option value="admin" <?php echo isset($edit_user['role']) && $edit_user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($edit_user['phone'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" rows="3"><?php echo htmlspecialchars($edit_user['address'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="active" <?php echo isset($edit_user['status']) && $edit_user['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo isset($edit_user['status']) && $edit_user['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div style="display: flex; gap: 1rem;">
                    <button type="submit" name="edit_user" class="btn btn-primary">Update</button>
                    <a href="users.php" class="btn">Cancel</a>
                </div>
            </form>
            
            <!-- Reset Password -->
            <hr style="margin: 1rem 0;">
            <h4>Reset Password</h4>
            <form method="POST" action="">
                <input type="hidden" name="user_id" value="<?php echo $edit_user['user_id']; ?>">
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" required>
                </div>
                <button type="submit" name="reset_password" class="btn" style="background-color: #f39c12;">Reset Password</button>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Users Table -->
    <h3>All Users</h3>
    <?php if (count($users) == 0): ?>
        <p>No users found.</p>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo $user['user_id']; ?></td>
                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td>
                        <span style="background: <?php 
                            echo $user['role'] == 'admin' ? '#e74c3c' : ($user['role'] == 'staff' ? '#f39c12' : '#3498db'); 
                        ?>; color: white; padding: 0.2rem 0.5rem; border-radius: 3px;">
                            <?php echo ucfirst($user['role']); ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                    <td>
                        <span style="color: <?php echo ($user['status'] ?? 'active') == 'active' ? 'green' : 'red'; ?>;">
                            <?php echo ucfirst($user['status'] ?? 'active'); ?>
                        </span>
                    </td>
                    <td><?php echo isset($user['last_login']) && $user['last_login'] ? date('M j, Y', strtotime($user['last_login'])) : 'Never'; ?></td>
                    <td>
                        <a href="?edit=<?php echo $user['user_id']; ?>" class="edit-btn">Edit</a>
                        <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                            <?php if (($user['status'] ?? 'active') == 'active'): ?>
                                <a href="?delete=<?php echo $user['user_id']; ?>" class="delete-btn" onclick="return confirm('Deactivate this user?')">Deactivate</a>
                            <?php else: ?>
                                <a href="?activate=<?php echo $user['user_id']; ?>" class="edit-btn" style="background-color: #27ae60;">Activate</a>
                            <?php endif; ?>
                        <?php endif; ?>
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