<?php
// Temporarily commented out due to missing vendor folder
// require 'vendor/autoload.php';
// require 'connect.php'; // This line was causing the circular dependency
use PHPMailer\PHPMailer\PHPMailer;

// Site configuration
define('SITE_URL', 'http://localhost/eindproject_test');  // Updated with correct path
define('SITE_NAME', 'Pet & Garden Shop');
define('SITE_EMAIL', 'info@petandgarden.com');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pet_and_garden');

// File upload paths
define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('PRODUCT_IMAGE_DIR', UPLOAD_DIR . '/products');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session settings (only set if session hasn't started)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
    session_start();
}

// Database connection
function get_db_connection() {
    static $mysqli = null;
    
    if ($mysqli === null) {
        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($mysqli->connect_errno) {
            error_log("Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
            throw new Exception("Database connection failed");
        }
        
        $mysqli->set_charset("utf8mb4");
    }
    
    return $mysqli;
}
?>