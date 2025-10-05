

<?php
// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configuration
require_once __DIR__ . '/../config.php';

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JDSystem</title>

    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/style.css?v=<?= time(); ?>">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/footer.css?v=<?= time(); ?>">

    <!-- Font Awesome (for icons) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- JS -->
    <script src="<?php echo SITE_URL; ?>js/script.js?v=<?= time(); ?>" defer></script>
</head>

<body>
<!-- Footer -->
<footer class="site-footer">
    <div class="footer-main">
        <div class="container">
            <div class="footer-grid">
                <!-- Company Info -->
                <div class="footer-column">
                    <div class="footer-logo">
                        <a href="<?php echo SITE_URL; ?>index.php">
                            <img src="<?php echo SITE_URL; ?>uploads/logo.jpg" alt="Jolly Dolly Kids Wear">
                        </a>
                    </div>
                    <p class="footer-description">
                        Creating timeless, quality clothing for your little ones. 
                        Where precious moments meet elegant style.
                    </p>
                    <div class="social-links">
                        <a href="#" class="social-link" aria-label="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="social-link" aria-label="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="social-link" aria-label="Pinterest">
                            <i class="fab fa-pinterest-p"></i>
                        </a>
                        <a href="#" class="social-link" aria-label="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="footer-column">
                    <h4>Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="<?php echo SITE_URL; ?>index.php">Home</a></li>
                        <li><a href="<?php echo SITE_URL; ?>pages/new.php?category=all">New Arrivals</a></li>
                        <li><a href="<?php echo SITE_URL; ?>pages/kid.php">Kids Collection</a></li>
                        <li><a href="<?php echo SITE_URL; ?>pages/baby.php">Baby Collection</a></li>
                        <li><a href="<?php echo SITE_URL; ?>pages/sale.php">Sale</a></li>
                    </ul>
                </div>

                <!-- Customer Service -->
                <div class="footer-column">
                    <h4>Customer Service</h4>
                    <ul class="footer-links">
                        <li><a href="<?php echo SITE_URL; ?>pages/contact.php">Contact Us</a></li>
                        <li><a href="<?php echo SITE_URL; ?>pages/shipping.php">Shipping Info</a></li>
                        <li><a href="<?php echo SITE_URL; ?>pages/returns.php">Returns & Exchanges</a></li>
                        <li><a href="<?php echo SITE_URL; ?>pages/size-guide.php">Size Guide</a></li>
                        <li><a href="<?php echo SITE_URL; ?>pages/faq.php">FAQ</a></li>
                    </ul>
                </div>

                <!-- Contact & Newsletter -->
                <div class="footer-column">
                    <h4>Stay Connected</h4>
                    <div class="contact-info">
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <span>hello@jollydolly.com</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <span>+1 (555) 123-4567</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-clock"></i>
                            <span>Mon-Fri: 9AM-6PM EST</span>
                        </div>
                    </div>
                    
                    <div class="footer-newsletter">
                        <p>Get updates on new arrivals and special offers</p>
                        <form class="newsletter-form" action="<?php echo SITE_URL; ?>auth/newsletter.php" method="POST">
                            <input type="email" name="email" placeholder="Your email address" required>
                            <button type="submit" class="btn-newsletter">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Bottom -->
    <div class="footer-bottom">
        <div class="container">
            <div class="footer-bottom-content">
                <div class="copyright">
                    <p>&copy; <?php echo date('Y'); ?> Jolly Dolly Kids Wear. All rights reserved.</p>
                </div>
                <div class="footer-legal">
                    <a href="<?php echo SITE_URL; ?>pages/privacy.php">Privacy Policy</a>
                    <a href="<?php echo SITE_URL; ?>pages/terms.php">Terms of Service</a>
                    <a href="<?php echo SITE_URL; ?>pages/cookies.php">Cookie Policy</a>
                </div>
                <div class="payment-methods">
                    <div class="payment-icons">
                        <i class="fab fa-cc-visa" title="Visa"></i>
                        <i class="fab fa-cc-mastercard" title="Mastercard"></i>
                        <i class="fab fa-cc-amex" title="American Express"></i>
                        <i class="fab fa-cc-paypal" title="PayPal"></i>
                        <i class="fab fa-apple-pay" title="Apple Pay"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Back to Top Button -->
<button class="back-to-top" id="backToTop" aria-label="Back to top">
    <i class="fas fa-chevron-up"></i>
</button>

</body>
</html>