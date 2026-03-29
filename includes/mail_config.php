<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/src/Exception.php';
require_once __DIR__ . '/src/PHPMailer.php';
require_once __DIR__ . '/src/SMTP.php';

function sendOrderEmail($to_email, $to_name, $order_id, $order_total, $order_items) {
    $mail = new PHPMailer(true);
    
    try {
        // Enable verbose debug output for testing (change to DEBUG_OFF after working)
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;  // Shows detailed errors
        $mail->Debugoutput = 'html';
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'kd.aligsao@gmail.com';  // YOUR GMAIL - CHANGE THIS
        $mail->Password   = 'bpgh yobz nyec fimj';     // YOUR APP PASSWORD - CHANGE THIS
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Timeout settings
        $mail->Timeout = 30;
        
        // Recipients
        $mail->setFrom('kd.aligsao@gmail.com', 'ElectraStore');  // Same as username
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
        return true;
    } catch (Exception $e) {
        error_log("Email failed to send: " . $mail->ErrorInfo);
        return false;
    }
}
?>