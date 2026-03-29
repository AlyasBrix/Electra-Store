<?php
require_once '../includes/header.php';

if (isLoggedIn()) {
    header('Location: /app/electrastore/index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    if (empty($full_name) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        $conn = getConnection();
        
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error = 'Email already registered';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, phone, address) VALUES (?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$full_name, $email, $hashed_password, $phone, $address])) {
                $success = 'Registration successful! You can now login.';
                $full_name = $email = $phone = $address = '';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>

<div class="form-container">
    <h2>Create an Account</h2>
    
    <?php if ($error): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label>Full Name *</label>
            <input type="text" name="full_name" value="<?php echo htmlspecialchars($full_name ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label>Email *</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
        </div>
        
        <div class="form-group password-field">
            <label>Password *</label>
            <div class="password-input-wrapper">
                <input type="password" name="password" id="password" required>
                <span class="password-toggle" onclick="togglePassword('password')">
                    <i class="fas fa-eye"></i>
                </span>
            </div>
            <small>At least 6 characters</small>
        </div>
        
        <div class="form-group password-field">
            <label>Confirm Password *</label>
            <div class="password-input-wrapper">
                <input type="password" name="confirm_password" id="confirm_password" required>
                <span class="password-toggle" onclick="togglePassword('confirm_password')">
                    <i class="fas fa-eye"></i>
                </span>
            </div>
        </div>
        
        <div class="form-group">
            <label>Phone Number</label>
            <input type="tel" name="phone" value="<?php echo htmlspecialchars($phone ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label>Address</label>
            <textarea name="address"><?php echo htmlspecialchars($address ?? ''); ?></textarea>
        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%; display: flex; align-items: center; justify-content: center;">Register</button>
    </form>
    
    <p style="text-align: center; margin-top: 1rem;">
        Already have an account? <a href="login.php">Login here</a>
    </p>
</div>

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