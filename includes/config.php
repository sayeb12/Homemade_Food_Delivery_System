<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'homemade_food_delivery');

// Email Configuration (Update these for production)
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'your-email@gmail.com'); // Replace with actual email
define('MAIL_PASSWORD', 'your-app-password');    // Replace with actual app password
define('MAIL_FROM', 'your-email@gmail.com');     // Replace with actual email
define('MAIL_FROM_NAME', 'Homemade Food Delivery');

// Site Configuration
define('SITE_URL', 'http://localhost'); // Update for production
define('SITE_NAME', 'Homemade Food Delivery');

// Session Configuration
define('SESSION_TIMEOUT', 30 * 60); // 30 minutes

// Security Configuration
define('HASH_COST', 10);

// Currency Configuration
define('CURRENCY_SYMBOL', '৳'); // Bangladeshi Taka

// Upload Directories
$uploadDirs = [
    'profiles' => '../uploads/profiles/',
    'dishes' => '../uploads/dishes/'
];

// Create upload directories if they don't exist
foreach ($uploadDirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true); // More secure permissions
    }
}
?>
