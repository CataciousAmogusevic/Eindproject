<?php
// if (!isset($_SESSION)) {  // Removed this redundant session_start()
//     session_start();
// }
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// Get database connection if not already available
if (!isset($mysqli)) {
    $mysqli = get_db_connection();
}


if (!isset($cart)) {
    $cart = new Cart($mysqli);
}
if (!isset($wishlist)) {
    $wishlist = new Wishlist($mysqli);
}

// Get cart and wishlist counts
$cartCount = $cart->getItemCount();
$wishlistCount = $wishlist->getItemCount();

// Initialize customer type for logged in users or set as guest
if(isset($_SESSION['customer_id'])) {
    $sql = "SELECT c.type_id, t.type_id, t.type, c.mail_subscription FROM tblcustomer c, tbltypes t WHERE customer_id = ? AND c.type_id = t.type_id";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $_SESSION['customer_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $_SESSION['type'] = $row['type'];
    $_SESSION['mail_subscription'] = $row['mail_subscription'];
} else {
    $_SESSION['type'] = "guest";
}

// Handle newsletter subscription toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_subscription']) && isset($_SESSION['customer_id'])) {
    $new_status = $_SESSION['mail_subscription'] ? 0 : 1;
    $stmt = $mysqli->prepare("UPDATE tblcustomer SET mail_subscription = ? WHERE customer_id = ?");
    $stmt->bind_param("ii", $new_status, $_SESSION['customer_id']);
    if ($stmt->execute()) {
        $_SESSION['mail_subscription'] = $new_status;
        $_SESSION['flash_message'] = $new_status ? 'Successfully subscribed to newsletter' : 'Successfully unsubscribed from newsletter';
        $_SESSION['flash_type'] = 'success';
    }
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit();
}

// Check for maintenance mode and announcements
maintenanceMode($mysqli);

// Function to check if user is admin
function isAdmin() {
    return isset($_SESSION['type']) && $_SESSION['type'] === 'admin';
}

// Redirect non-admin users from admin pages
if (strpos($_SERVER['PHP_SELF'], '/admin/') !== false && !isAdmin()) {
    $_SESSION['flash_message'] = "You're not an admin!";
    $_SESSION['flash_type'] = 'error';
    header('Location: ' . (SITE_URL ?? '') . '/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Shop premium pet supplies & garden tools at Pet & Garden. Your one-stop shop for all pet and gardening needs.">
    
    <!-- Open Graph Tags -->
    <meta property="og:title" content="Pet & Garden Shop">
    <meta property="og:description" content="Your one-stop shop for pet food, accessories, and garden tools.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo SITE_URL ?? ''; ?>">
    
    <title>Pet & Garden Shop – Premium Pet Supplies & Gardening Tools</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Mobile Menu Script -->
    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        }
    </script>
</head>
<body class="min-h-screen bg-gray-50">
    <!-- Skip to content link -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:p-4 focus:bg-green-700 focus:text-white">
        Skip to content
    </a>

    <!-- Navigation -->
    <header class="bg-green-700 text-white shadow-lg" role="banner">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-3">
                <!-- Logo -->
                <a href="http://localhost/eindproject_test/index.php" class="flex items-center space-x-2" aria-label="Pet & Garden Shop Home">
                    <span class="text-2xl" aria-hidden="true">🌿</span>
                    <span class="text-xl font-bold">Pet & Garden</span>
                </a>

                <!-- Mobile menu button -->
                <button class="md:hidden focus:outline-none focus:ring-2 focus:ring-green-200 rounded p-2" 
                        onclick="toggleMobileMenu()" 
                        aria-expanded="false"
                        aria-controls="mobile-menu"
                        aria-label="Toggle navigation menu">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>

                <!-- Desktop Navigation -->
                <nav class="hidden md:flex space-x-6" role="navigation">
                    <a href="http://localhost/eindproject_test/index.php" class="hover:text-green-200 transition focus:outline-none focus:ring-2 focus:ring-green-200 rounded px-2 py-1" role="menuitem">Home</a>
                    <a href="http://localhost/eindproject_test/website_review.php" class="hover:text-green-200 transition focus:outline-none focus:ring-2 focus:ring-green-200 rounded px-2 py-1" role="menuitem">Reviews</a>
                    <?php if (isset($_SESSION['type']) && $_SESSION['type'] === 'admin'): ?>
                        <a href="http://localhost/eindproject_test/admin/admin-panel.php" class="hover:text-green-200 transition focus:outline-none focus:ring-2 focus:ring-green-200 rounded px-2 py-1" role="menuitem">Admin Panel</a>
                    <?php endif; ?>
                </nav>

                <!-- User Navigation -->
                <div class="flex items-center space-x-4">
                    <?php if (isset($_SESSION['customer_id'])): ?>
                        <form method="POST" action="" class="inline-flex items-center">
                            <button type="submit" 
                                    name="toggle_subscription" 
                                    class="flex items-center hover:text-green-200 transition focus:outline-none focus:ring-2 focus:ring-green-200 rounded px-2 py-1"
                                    title="<?php echo $_SESSION['mail_subscription'] ? 'Unsubscribe from newsletter' : 'Subscribe to newsletter'; ?>">
                                <i class="fas <?php echo $_SESSION['mail_subscription'] ? 'fa-envelope' : 'fa-envelope-open'; ?>"></i>
                            </button>
                        </form>
                    <?php endif; ?>
                    <a href="http://localhost/eindproject_test/wishlist.php" 
                       class="flex items-center hover:text-green-200 transition focus:outline-none focus:ring-2 focus:ring-green-200 rounded px-2 py-1"
                       aria-label="Wishlist">
                        <i class="fas fa-heart"></i>
                        <?php if ($wishlistCount > 0): ?>
                            <span id="wishlist-count" class="ml-1" aria-live="polite"><?php echo $wishlistCount; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="http://localhost/eindproject_test/cart.php" 
                       class="flex items-center hover:text-green-200 transition focus:outline-none focus:ring-2 focus:ring-green-200 rounded px-2 py-1"
                       aria-label="Shopping Cart">
                        <i class="fas fa-shopping-cart"></i>
                        <span id="cart-count" class="ml-1" aria-live="polite"><?php echo $cartCount; ?></span>
                    </a>
                    <?php if (isset($_SESSION['customer_id'])): ?>
                        <a href="http://localhost/eindproject_test/account.php" class="hover:text-green-200 transition focus:outline-none focus:ring-2 focus:ring-green-200 rounded px-2 py-1">Account</a>
                        <a href="http://localhost/eindproject_test/account_stuff/logout.php" class="hover:text-green-200 transition focus:outline-none focus:ring-2 focus:ring-green-200 rounded px-2 py-1">Logout</a>
                    <?php else: ?>
                        <a href="http://localhost/eindproject_test/account_stuff/login.php" class="hover:text-green-200 transition focus:outline-none focus:ring-2 focus:ring-green-200 rounded px-2 py-1">Login</a>
                        <a href="http://localhost/eindproject_test/account_stuff/register.php" class="hover:text-green-200 transition focus:outline-none focus:ring-2 focus:ring-green-200 rounded px-2 py-1">Register</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div id="mobile-menu" class="hidden md:hidden py-4 border-t border-green-600" role="menu">
                <a href="http://localhost/eindproject_test/index.php" class="block py-2 hover:text-green-200 transition" role="menuitem">Home</a>
                <a href="http://localhost/eindproject_test/product.php" class="block py-2 hover:text-green-200 transition" role="menuitem">Products</a>
                <a href="http://localhost/eindproject_test/contact.php" class="block py-2 hover:text-green-200 transition" role="menuitem">Contact</a>
                <a href="http://localhost/eindproject_test/website_review.php" class="block py-2 hover:text-green-200 transition" role="menuitem">Reviews</a>
                <?php if (isset($_SESSION['type']) && $_SESSION['type'] === 'admin'): ?>
                    <a href="http://localhost/eindproject_test/admin/admin-panel.php" class="block py-2 hover:text-green-200 transition" role="menuitem">Admin Panel</a>
                <?php endif; ?>
                <?php if (isset($_SESSION['customer_id'])): ?>
                    <form method="POST" action="" class="block py-2">
                        <button type="submit" 
                                name="toggle_subscription" 
                                class="text-left w-full hover:text-green-200 transition">
                            <?php echo $_SESSION['mail_subscription'] ? 'Unsubscribe from Newsletter' : 'Subscribe to Newsletter'; ?>
                        </button>
                    </form>
                <?php endif; ?>
                <?php if (isset($_SESSION['customer_id'])): ?>
                    <a href="http://localhost/eindproject_test/account.php" class="block py-2 hover:text-green-200 transition" role="menuitem">Account</a>
                    <a href="http://localhost/eindproject_test/account_stuff/logout.php" class="block py-2 hover:text-green-200 transition" role="menuitem">Logout</a>
                <?php else: ?>
                    <a href="http://localhost/eindproject_test/account_stuff/login.php" class="block py-2 hover:text-green-200 transition" role="menuitem">Login</a>
                    <a href="http://localhost/eindproject_test/account_stuff/register.php" class="block py-2 hover:text-green-200 transition" role="menuitem">Register</a>
                <?php endif; ?>
                <a href="http://localhost/eindproject_test/wishlist.php" class="block py-2 hover:text-green-200 transition" role="menuitem">Wishlist (<span id="mobile-wishlist-count"><?php echo $wishlistCount; ?></span>)</a>
                <a href="http://localhost/eindproject_test/cart.php" class="block py-2 hover:text-green-200 transition" role="menuitem">Cart (<span id="mobile-cart-count"><?php echo $cartCount; ?></span>)</a>
            </div>
        </div>
    </header>

    <!-- Flash Messages -->
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="container mx-auto px-4 mt-4" role="alert">
            <div class="p-4 rounded-lg <?php echo $_SESSION['flash_type'] === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'; ?>">
                <?php 
                if (is_array($_SESSION['flash_message'])) {
                    echo implode('<br>', $_SESSION['flash_message']);
                } else {
                    echo $_SESSION['flash_message'];
                }
                unset($_SESSION['flash_message'], $_SESSION['flash_type']);
                ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main id="main-content" class="container mx-auto px-4 py-8" role="main">