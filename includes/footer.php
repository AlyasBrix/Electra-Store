<?php
// No closing PHP tag at the beginning
?>
    </main>
    
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="footer-logo">
                        <i class="fas fa-bolt"></i>
                        <span>ElectraStore</span>
                    </div>
                    <p>Your trusted source for premium electronic gadgets and appliances in Cagayan de Oro City.</p>
                    <div class="social-links">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="/app/electrastore/index.php"><i class="fas fa-chevron-right"></i> Home</a></li>
                        <li><a href="/app/electrastore/products/index.php"><i class="fas fa-chevron-right"></i> Shop</a></li>
                        <li><a href="/app/electrastore/about.php"><i class="fas fa-chevron-right"></i> About Us</a></li>
                        <li><a href="/app/electrastore/contact.php"><i class="fas fa-chevron-right"></i> Contact</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Customer Service</h3>
                    <ul>
                        <li><a href="/app/electrastore/faq.php"><i class="fas fa-chevron-right"></i> FAQ</a></li>
                        <li><a href="/app/electrastore/returns.php"><i class="fas fa-chevron-right"></i> Returns Policy</a></li>
                        <li><a href="/app/electrastore/shipping.php"><i class="fas fa-chevron-right"></i> Shipping Info</a></li>
                        <li><a href="/app/electrastore/privacy.php"><i class="fas fa-chevron-right"></i> Privacy Policy</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Contact Info</h3>
                    <div class="contact-info">
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Cagayan de Oro City, Philippines</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <span>(088) 123-4567</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <span>support@electrastore.com</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="footer-bottom-content">
                    <p>&copy; <?php echo date('Y'); ?> ElectraStore. All rights reserved.</p>
                    <div class="payment-methods-footer">
                        <i class="fab fa-cc-visa"></i>
                        <i class="fab fa-cc-mastercard"></i>
                        <i class="fab fa-cc-paypal"></i>
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    
    <script src="/app/electrastore/assets/js/script.js"></script>
    
    <style>
    /* Footer Styles */
    footer {
        background: linear-gradient(135deg, #1e2a3e, #0f172a);
        color: white;
        padding: 3rem 0 1.5rem;
        margin-top: 4rem;
    }
    
    .footer-content {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 2rem;
        margin-bottom: 2rem;
    }
    
    .footer-logo {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 1.3rem;
        font-weight: 800;
        margin-bottom: 1rem;
    }
    
    .footer-logo i {
        font-size: 1.5rem;
        color: #4361ee;
    }
    
    .footer-section p {
        color: #94a3b8;
        line-height: 1.6;
        margin-bottom: 1rem;
    }
    
    .social-links {
        display: flex;
        gap: 1rem;
        margin-top: 1rem;
    }
    
    .social-links a {
        width: 36px;
        height: 36px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        transition: all 0.3s ease;
    }
    
    .social-links a:hover {
        background: #4361ee;
        transform: translateY(-3px);
    }
    
    .footer-section h3 {
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 1rem;
        color: white;
        position: relative;
        padding-bottom: 0.5rem;
    }
    
    .footer-section h3::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 40px;
        height: 2px;
        background: linear-gradient(135deg, #4361ee, #7209b7);
        border-radius: 2px;
    }
    
    .footer-section ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .footer-section ul li {
        margin-bottom: 0.5rem;
    }
    
    .footer-section ul li a {
        color: #94a3b8;
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 0.85rem;
    }
    
    .footer-section ul li a i {
        font-size: 0.7rem;
        transition: transform 0.3s ease;
    }
    
    .footer-section ul li a:hover {
        color: #4361ee;
    }
    
    .footer-section ul li a:hover i {
        transform: translateX(5px);
    }
    
    .contact-info {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .contact-item {
        display: flex;
        align-items: center;
        gap: 12px;
        color: #94a3b8;
        font-size: 0.85rem;
    }
    
    .contact-item i {
        width: 20px;
        color: #4361ee;
    }
    
    .footer-bottom {
        padding-top: 1.5rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .footer-bottom-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .footer-bottom-content p {
        color: #94a3b8;
        font-size: 0.8rem;
        margin: 0;
    }
    
    .payment-methods-footer {
        display: flex;
        gap: 1rem;
        font-size: 1.2rem;
        color: #94a3b8;
    }
    
    /* Mobile Responsive */
    @media (max-width: 768px) {
        footer {
            padding: 2rem 0 1rem;
        }
        
        .footer-content {
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
        
        .footer-bottom-content {
            flex-direction: column;
            text-align: center;
        }
        
        .payment-methods-footer {
            justify-content: center;
        }
        
        .footer-section h3::after {
            left: 50%;
            transform: translateX(-50%);
        }
        
        .footer-section {
            text-align: center;
        }
        
        .social-links {
            justify-content: center;
        }
        
        .contact-item {
            justify-content: center;
        }
        
        .footer-section ul li a {
            justify-content: center;
        }
    }
    </style>
</body>
</html>
<?php
