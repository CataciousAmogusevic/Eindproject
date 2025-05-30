<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Get database connection
$mysqli = get_db_connection();

// Initialize cart and wishlist to clear them
$cart = new Cart($mysqli);
$wishlist = new Wishlist($mysqli);

// Clear cart and wishlist
$cart->clear();
$wishlist->clear();

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Start a new session for the guest user
session_start();
$_SESSION['flash_message'] = 'You have been successfully logged out.';
$_SESSION['flash_type'] = 'success';

// Redirect to home page
header('Location: ' . SITE_URL . '/index.php');
exit();
?>