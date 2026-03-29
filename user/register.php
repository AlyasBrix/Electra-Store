<?php
require_once '../config/database.php';

if (isLoggedIn()) {
    header('Location: /app/electrastore/index.php');
    exit();
}

$error = '';
$success = '';

// Function to validate Gmail address
function isValidGmail($email) {
    if (strpos($email, '@gmail.com') === false) {
        return false;
    }
    
    $username = str_replace('@gmail.com', '', $email);
    
    if (preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9\.]{4,28}[a-zA-Z0-9])?$/', $username)) {
        if (strpos($username, '..') !== false) {
            return false;
        }
        return true;
    }
    
    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    if (empty($full_name) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields';
    } elseif (!isValidGmail($email)) {
        $error = 'Please use a valid Gmail address (e.g., username@gmail.com)';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } else {
        $conn = getConnection();
        
        // Check if email exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error = 'Email already registered';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, phone, address, role, status, is_verified) 
                                    VALUES (?, ?, ?, ?, ?, 'user', 'active', 1)");
            
            if ($stmt->execute([$full_name, $email, $hashed_password, $phone, $address])) {
                // Auto-verify the user immediately (no email needed)
                $success = 'Registration successful! You can now login to your account.';
                // Clear form
                $full_name = $email = $phone = $address = '';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - ElectraStore</title>
    <link rel="stylesheet" href="/app/electrastore/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #f8fafc 100%);
            min-height: 100vh;
        }
        
        .form-container {
            max-width: 500px;
            margin: 3rem auto;
            padding: 2.5rem;
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .form-container h2 {
            text-align: center;
            margin-bottom: 2rem;
            color: #1e2a3e;
            font-size: 1.8rem;
            font-weight: 700;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #1e2a3e;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.875rem;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-family: inherit;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #4361ee;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-group small {
            display: block;
            margin-top: 0.5rem;
            font-size: 0.7rem;
            color: #64748b;
        }
        
        .password-field {
            position: relative;
        }
        
        .password-input-wrapper {
            position: relative;
            width: 100%;
        }
        
        .password-input-wrapper input {
            width: 100%;
            padding-right: 45px;
        }
        
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #64748b;
            transition: color 0.3s ease;
        }
        
        .password-toggle:hover {
            color: #4361ee;
        }
        
        .error-message {
            background: #fee2e2;
            color: #dc2626;
            border-left: 4px solid #dc2626;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .success-message {
            background: #d1fae5;
            color: #059669;
            border-left: 4px solid #059669;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-primary {
            width: 100%;
            padding: 0.875rem;
            background: linear-gradient(135deg, #4361ee, #7209b7);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(67, 97, 238, 0.3);
        }
        
        .login-link {
            text-align: center;
            margin-top: 1rem;
            color: #64748b;
        }
        
        .login-link a {
            color: #4361ee;
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Create an Account</h2>
        
        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="full_name" value="<?php echo htmlspecialchars($full_name ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Email * (Gmail only)</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required 
                       placeholder="username@gmail.com">
                <small><i class="fas fa-info-circle"></i> Only Gmail addresses are allowed</small>
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
                <input type="tel" name="phone" value="<?php echo htmlspecialchars($phone ?? ''); ?>" placeholder="e.g., 09123456789">
            </div>
            
            <div class="form-group">
                <label>Address</label>
                <textarea name="address" placeholder="Your complete address"><?php echo htmlspecialchars($address ?? ''); ?></textarea>
            </div>
            
            <button type="submit" class="btn-primary">
                <i class="fas fa-user-plus"></i> Register
            </button>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
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
    
    // Real-time email validation
    document.getElementById('email').addEventListener('input', function() {
        const email = this.value;
        if (email && !email.includes('@gmail.com')) {
            this.style.borderColor = '#dc2626';
        } else {
            this.style.borderColor = '#e2e8f0';
        }
    });
    </script>
</body>
</html>