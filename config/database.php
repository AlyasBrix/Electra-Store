<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'electrastore1');

// Create connection
function getConnection() {
    try {
        $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->exec("set names utf8");
        return $conn;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Get user's cart ID
function getUserCartId($user_id) {
    $conn = getConnection();
    
    $stmt = $conn->prepare("SELECT cart_id FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $cart = $stmt->fetch();
    
    if ($cart) {
        return $cart['cart_id'];
    }
    
    $stmt = $conn->prepare("INSERT INTO cart (user_id) VALUES (?)");
    $stmt->execute([$user_id]);
    return $conn->lastInsertId();
}

// Get cart item count
function getCartItemCount($user_id) {
    $cart_id = getUserCartId($user_id);
    $conn = getConnection();
    
    $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart_items WHERE cart_id = ?");
    $stmt->execute([$cart_id]);
    $result = $stmt->fetch();
    return $result['total'] ?? 0;
}

// ============ ADD THIS EMAIL FUNCTION ============
function sendOrderEmail($to_email, $to_name, $order_id, $order_total, $order_items) {
    // Start output buffering to catch any debug output
    ob_start();
    
    // Check if PHPMailer exists
    $phpmailer_path = __DIR__ . '/../includes/src/PHPMailer.php';
    if (!file_exists($phpmailer_path)) {
        ob_end_clean();
        error_log("PHPMailer not found at: " . $phpmailer_path);
        return false;
    }
    
    require_once $phpmailer_path;
    require_once __DIR__ . '/../includes/src/SMTP.php';
    require_once __DIR__ . '/../includes/src/Exception.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Turn OFF all debugging
        $mail->SMTPDebug = 0; // 0 = OFF
        $mail->Debugoutput = function($str, $level) {
            // Do nothing - suppress all output
        };
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'kd.aligsao@gmail.com';
        $mail->Password   = 'bpghyobznyecfimj'; // Your App Password (no spaces)
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->Timeout    = 30;
        
        // Recipients
        $mail->setFrom('kd.aligsao@gmail.com', 'ElectraStore');
        $mail->addAddress($to_email, $to_name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "Order #$order_id Completed - ElectraStore";
        
        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #4361ee, #7209b7); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f8fafc; padding: 20px; border: 1px solid #e2e8f0; }
                .order-details { background: white; padding: 15px; border-radius: 8px; margin: 15px 0; }
                table { width: 100%; border-collapse: collapse; }
                th, td { padding: 10px; text-align: left; border-bottom: 1px solid #e2e8f0; }
                th { background: #f1f5f9; }
                .total { font-size: 1.2rem; font-weight: bold; text-align: right; margin-top: 15px; padding-top: 10px; border-top: 2px solid #e2e8f0; }
                .footer { background: #1e2a3e; color: white; padding: 15px; text-align: center; border-radius: 0 0 10px 10px; font-size: 0.8rem; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>🎉 Order Completed! 🎉</h2>
                    <p>Thank you for shopping at ElectraStore</p>
                </div>
                <div class='content'>
                    <p>Dear <strong>" . htmlspecialchars($to_name) . "</strong>,</p>
                    <p>Great news! Your order <strong>#$order_id</strong> has been completed successfully.</p>
                    
                    <div class='order-details'>
                        <h3>Order Summary</h3>
                        <table>
                            <thead>
                                 <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                 </tr>
                            </thead>
                            <tbody>";
        
        foreach ($order_items as $item) {
            $body .= "
                                 <tr>
                                    <td>" . htmlspecialchars($item['product_name']) . "</td>
                                    <td>x" . $item['quantity'] . "</td>
                                    <td>₱" . number_format($item['price'], 2) . "</td>
                                 </tr>";
        }
        
        $body .= "
                            </tbody>
                        </table>
                        <div class='total'>
                            <strong>Total Amount: ₱" . number_format($order_total, 2) . "</strong>
                        </div>
                    </div>
                    
                    <p>Thank you for choosing ElectraStore!</p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " ElectraStore. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $mail->Body = $body;
        $mail->AltBody = strip_tags($body);
        
        $mail->send();
        
        // Clear output buffer
        ob_end_clean();
        return true;
    } catch (Exception $e) {
        ob_end_clean();
        error_log("Order email failed: " . $mail->ErrorInfo);
        return false;
    }
}
?>