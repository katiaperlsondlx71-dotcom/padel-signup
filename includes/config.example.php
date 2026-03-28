<?php
// Database configuration
// Copy this file to config.php and fill in your values
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');

// Application settings
define('APP_NAME', 'Padel Tournament Registration');
define('APP_URL', 'https://yourdomain.com');
define('TIMEZONE', 'Asia/Bangkok');

// Email settings
define('EMAIL_FROM_DOMAIN', 'yourdomain.com');
define('EMAIL_FROM_NAME', 'Play Padel with Us');
define('EMAIL_REPLY_TO', 'support@yourdomain.com');
define('EMAIL_NOREPLY', 'noreply@yourdomain.com');

// Global timezone support
define('DEFAULT_TIMEZONE', 'Asia/Bangkok');
define('DETECT_USER_TIMEZONE', true);

// Session settings (in seconds)
// 7200 = 2 hours for regular sessions
// Note: "remember me" functionality still uses 30-day tokens via cookies
define('SESSION_LIFETIME', 7200); 

// Security settings
define('PASSWORD_MIN_LENGTH', 8);
define('HASH_ALGORITHM', PASSWORD_DEFAULT);

// Set timezone
date_default_timezone_set(TIMEZONE);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
