<?php
require_once '../config/database.php';

if (isLoggedIn()) {
    header('Location: /app/electrastore/index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter email and password';
    } else {
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT user_id, full_name, email, password, role, is_verified, status FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Check if email is verified
            if ($user['is_verified'] == 0) {
                $error = 'Please verify your email address first. Check your inbox for the verification link.';
            } elseif ($user['status'] == 'inactive') {
                $error = 'Your account has been deactivated. Please contact support.';
            } else {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                if ($user['role'] === 'admin') {
                    header('Location: /app/electrastore/admin/index.php');
                } else {
                    header('Location: /app/electrastore/index.php');
                }
                exit();
            }
        } else {
            $error = 'Invalid email or password';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ElectraStore</title>
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
        
        .form-group input {
            width: 100%;
            padding: 0.875rem;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-family: inherit;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #4361ee;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
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
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(67, 97, 238, 0.3);
        }
        
        .register-link {
            text-align: center;
            margin-top: 1rem;
            color: #64748b;
        }
        
        .register-link a {
            color: #4361ee;
            text-decoration: none;
            font-weight: 500;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Login to ElectraStore</h2>
        
        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            
            <div class="form-group password-field">
                <label>Password</label>
                <div class="password-input-wrapper">
                    <input type="password" name="password" id="password" required>
                    <span class="password-toggle" onclick="togglePassword('password')">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
            </div>
            
            <button type="submit" class="btn-primary">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>
        
        <div class="register-link">
            Don't have an account? <a href="register.php">Register here</a>
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
    </script>
</body>
</html>