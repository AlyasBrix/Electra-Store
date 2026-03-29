<?php
require_once '../config/database.php';

$conn = getConnection();
$message = '';
$message_type = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Check if token exists and is not expired
    $stmt = $conn->prepare("SELECT user_id, full_name, email FROM users 
                            WHERE verification_token = ? 
                            AND is_verified = 0 
                            AND token_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Update user as verified
        $update = $conn->prepare("UPDATE users SET is_verified = 1, verification_token = NULL, token_expires = NULL, status = 'active' WHERE user_id = ?");
        if ($update->execute([$user['user_id']])) {
            $message = "Email verified successfully! You can now login to your account.";
            $message_type = "success";
        } else {
            $message = "Failed to verify email. Please try again.";
            $message_type = "error";
        }
    } else {
        $message = "Invalid or expired verification link. Please register again.";
        $message_type = "error";
    }
} else {
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - ElectraStore</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .verify-container {
            max-width: 500px;
            margin: 2rem;
            padding: 2.5rem;
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .verify-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .verify-icon.success {
            color: #059669;
        }
        
        .verify-icon.error {
            color: #dc2626;
        }
        
        .verify-container h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #1e2a3e;
        }
        
        .verify-container p {
            color: #64748b;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #4361ee, #7209b7);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(67, 97, 238, 0.3);
        }
    </style>
</head>
<body>
    <div class="verify-container">
        <?php if ($message_type == 'success'): ?>
            <div class="verify-icon success">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2>Email Verified!</h2>
            <p><?php echo $message; ?></p>
            <a href="login.php" class="btn">
                <i class="fas fa-sign-in-alt"></i> Login Now
            </a>
        <?php else: ?>
            <div class="verify-icon error">
                <i class="fas fa-times-circle"></i>
            </div>
            <h2>Verification Failed</h2>
            <p><?php echo $message; ?></p>
            <a href="register.php" class="btn">
                <i class="fas fa-user-plus"></i> Register Again
            </a>
        <?php endif; ?>
    </div>
</body>
</html>