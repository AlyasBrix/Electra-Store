<?php
require_once '../includes/header.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: /electrastore/user/login.php');
    exit();
}

$conn = getConnection();
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get user current information
$stmt = $conn->prepare("SELECT full_name, email, phone, address FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    if (empty($full_name)) {
        $error = 'Full name is required';
    } else {
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, phone = ?, address = ? WHERE user_id = ?");
        if ($stmt->execute([$full_name, $phone, $address, $user_id])) {
            $_SESSION['full_name'] = $full_name;
            $success = 'Profile updated successfully!';
            // Refresh user data
            $user['full_name'] = $full_name;
            $user['phone'] = $phone;
            $user['address'] = $address;
        } else {
            $error = 'Failed to update profile';
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Verify current password
    $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch();
    
    if (!password_verify($current_password, $user_data['password'])) {
        $error = 'Current password is incorrect';
    } elseif (strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match';
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        if ($stmt->execute([$hashed_password, $user_id])) {
            $success = 'Password changed successfully!';
        } else {
            $error = 'Failed to change password';
        }
    }
}
?>

<div class="profile-container">
    <div class="profile-header">
        <h2>My Profile</h2>
        <p>Manage your account information</p>
    </div>
    
    <div class="profile-grid">
        <!-- Profile Information Section -->
        <div class="profile-card">
            <div class="profile-card-header">
                <i class="fas fa-user-circle"></i>
                <h3>Profile Information</h3>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                    <small>Email cannot be changed</small>
                </div>
                
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                </div>
                
                <button type="submit" name="update_profile" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Profile
                </button>
            </form>
        </div>
        
        <!-- Change Password Section -->
        <div class="profile-card">
            <div class="profile-card-header">
                <i class="fas fa-lock"></i>
                <h3>Change Password</h3>
            </div>
            
            <form method="POST" action="">
                <div class="form-group password-field">
                    <label>Current Password</label>
                    <div class="password-input-wrapper">
                        <input type="password" name="current_password" id="current_password" required>
                        <span class="password-toggle" onclick="togglePassword('current_password')">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                </div>
                
                <div class="form-group password-field">
                    <label>New Password</label>
                    <div class="password-input-wrapper">
                        <input type="password" name="new_password" id="new_password" required>
                        <span class="password-toggle" onclick="togglePassword('new_password')">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                    <small>At least 6 characters</small>
                </div>
                
                <div class="form-group password-field">
                    <label>Confirm New Password</label>
                    <div class="password-input-wrapper">
                        <input type="password" name="confirm_password" id="confirm_password" required>
                        <span class="password-toggle" onclick="togglePassword('confirm_password')">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                </div>
                
                <button type="submit" name="change_password" class="btn btn-primary">
                    <i class="fas fa-key"></i> Change Password
                </button>
            </form>
        </div>
        
        <!-- Order History Summary -->
        <div class="profile-card">
            <div class="profile-card-header">
                <i class="fas fa-shopping-bag"></i>
                <h3>Recent Orders</h3>
            </div>
            
            <?php
            $stmt = $conn->prepare("SELECT order_id, total_amount, order_status, order_date FROM orders WHERE user_id = ? ORDER BY order_date DESC LIMIT 5");
            $stmt->execute([$user_id]);
            $recent_orders = $stmt->fetchAll();
            ?>
            
            <?php if (count($recent_orders) > 0): ?>
                <div class="orders-list">
                    <?php foreach ($recent_orders as $order): ?>
                    <div class="order-item">
                        <div class="order-info">
                            <span class="order-id">Order #<?php echo $order['order_id']; ?></span>
                            <span class="order-date"><?php echo date('M j, Y', strtotime($order['order_date'])); ?></span>
                        </div>
                        <div class="order-details">
                            <span class="order-amount">₱<?php echo number_format($order['total_amount'], 2); ?></span>
                            <span class="order-status status-<?php echo strtolower($order['order_status']); ?>">
                                <?php echo $order['order_status']; ?>
                            </span>
                        </div>
                        <a href="/electrastore/orders/history.php?view=<?php echo $order['order_id']; ?>" class="view-order">View Details →</a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <a href="/electrastore/orders/history.php" class="view-all-orders">View All Orders →</a>
            <?php else: ?>
                <p class="no-orders">You haven't placed any orders yet.</p>
                <a href="/electrastore/products/index.php" class="btn btn-outline" style="margin-top: 1rem;">Start Shopping</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.profile-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1rem;
}

.profile-header {
    text-align: center;
    margin-bottom: 2rem;
}

.profile-header h2 {
    font-size: 2rem;
    color: var(--dark);
    margin-bottom: 0.5rem;
}

.profile-header p {
    color: var(--gray);
}

.profile-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 2rem;
}

.profile-card {
    background: var(--white);
    border-radius: 20px;
    padding: 1.5rem;
    box-shadow: var(--shadow-md);
}

.profile-card-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #e2e8f0;
}

.profile-card-header i {
    font-size: 1.5rem;
    color: var(--primary);
}

.profile-card-header h3 {
    font-size: 1.2rem;
    color: var(--dark);
    margin: 0;
}

.orders-list {
    max-height: 300px;
    overflow-y: auto;
}

.order-item {
    padding: 1rem;
    border-bottom: 1px solid #e2e8f0;
    transition: background 0.3s ease;
}

.order-item:hover {
    background: var(--light-gray);
}

.order-info {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.order-id {
    font-weight: 600;
    color: var(--dark);
}

.order-date {
    font-size: 0.85rem;
    color: var(--gray);
}

.order-details {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.order-amount {
    font-weight: bold;
    color: var(--primary);
}

.order-status {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-pending {
    background: #fef3c7;
    color: #d97706;
}

.status-processing {
    background: #dbeafe;
    color: #2563eb;
}

.status-completed {
    background: #d1fae5;
    color: #059669;
}

.status-cancelled {
    background: #fee2e2;
    color: #dc2626;
}

.view-order {
    font-size: 0.85rem;
    color: var(--primary);
    text-decoration: none;
    transition: color 0.3s ease;
}

.view-order:hover {
    color: var(--secondary);
    text-decoration: underline;
}

.view-all-orders {
    display: block;
    text-align: center;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e2e8f0;
    color: var(--primary);
    text-decoration: none;
    font-weight: 500;
}

.view-all-orders:hover {
    color: var(--secondary);
}

.no-orders {
    text-align: center;
    color: var(--gray);
    padding: 2rem 0;
}

.btn-outline {
    display: inline-block;
    width: 100%;
    text-align: center;
    background: transparent;
    border: 1px solid var(--primary);
    color: var(--primary);
}

.btn-outline:hover {
    background: var(--primary);
    color: white;
}

@media (max-width: 768px) {
    .profile-grid {
        grid-template-columns: 1fr;
    }
    
    .profile-card {
        padding: 1rem;
    }
}
</style>

<script>
function togglePassword(inputId) {
    const passwordInput = document.getElementById(inputId);
    const eyeIcon = passwordInput.nextElementSibling.querySelector('i');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.classList.remove('fa-eye');
        eyeIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        eyeIcon.classList.remove('fa-eye-slash');
        eyeIcon.classList.add('fa-eye');
    }
}
</script>

<?php
require_once '../includes/footer.php';
?>