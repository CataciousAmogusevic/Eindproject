<?php
require_once __DIR__ . '/../includes/config.php';

// Get database connection
$mysqli = get_db_connection();
require_once __DIR__ . '/../includes/config.php';

// Get database connection
$mysqli = get_db_connection();
include '../includes/header.php';

// Check if user is admin
if (!isset($_SESSION['type']) || $_SESSION['type'] !== 'admin') {
    $_SESSION['flash_message'] = 'Access denied. Admin privileges required.';
    $_SESSION['flash_type'] = 'error';
    header('Location: ' . SITE_URL . '/index.php');
    exit();
}

// Fetch quick statistics
$stats = [];

// Total Orders
$result = $mysqli->query("SELECT COUNT(*) as count FROM tblorders");
$stats['orders'] = $result->fetch_assoc()['count'];

// Total Products
$result = $mysqli->query("SELECT COUNT(*) as count FROM tblproducts");
$stats['products'] = $result->fetch_assoc()['count'];

// Total Customers
$result = $mysqli->query("SELECT COUNT(*) as count FROM tblcustomer WHERE type_id = 2");
$stats['customers'] = $result->fetch_assoc()['count'];

// Total Reviews
$result = $mysqli->query("SELECT COUNT(*) as count FROM tblreviews");
$stats['reviews'] = $result->fetch_assoc()['count'];
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Admin Panel</h1>
    
    <!-- Quick Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-2">Total Orders</h3>
            <p class="text-3xl font-bold text-green-600"><?php echo $stats['orders']; ?></p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-2">Total Products</h3>
            <p class="text-3xl font-bold text-green-600"><?php echo $stats['products']; ?></p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-2">Total Customers</h3>
            <p class="text-3xl font-bold text-green-600"><?php echo $stats['customers']; ?></p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-2">Total Reviews</h3>
            <p class="text-3xl font-bold text-green-600"><?php echo $stats['reviews']; ?></p>
        </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Orders Management -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Orders Management</h2>
            <ul class="space-y-2">
                <li>
                    <a href="<?php echo SITE_URL; ?>/admin/orders.php" 
                       class="text-green-600 hover:text-green-800">
                        View All Orders
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/admin/pending_orders.php" 
                       class="text-green-600 hover:text-green-800">
                        Pending Orders
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Product Management -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Product Management</h2>
            <ul class="space-y-2">
                <li>
                    <a href="<?php echo SITE_URL; ?>/admin/products.php" 
                       class="text-green-600 hover:text-green-800">
                        Manage Products
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/admin/manage_images.php" 
                       class="text-green-600 hover:text-green-800">
                        Manage Product Images
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/admin/stock.php" 
                       class="text-green-600 hover:text-green-800">
                        Manage Stock
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Customer Management -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Customer Management</h2>
            <ul class="space-y-2">
                <li>
                    <a href="<?php echo SITE_URL; ?>/admin/customers.php" 
                       class="text-green-600 hover:text-green-800">
                        View All Customers
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/admin/reviews.php" 
                       class="text-green-600 hover:text-green-800">
                        Manage Reviews
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Catalog Management -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Catalog Management</h2>
            <ul class="space-y-2">
                <li>
                    <a href="<?php echo SITE_URL; ?>/admin/categories.php" 
                       class="text-green-600 hover:text-green-800">
                        Manage Categories
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/admin/brands.php" 
                       class="text-green-600 hover:text-green-800">
                        Manage Brands
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Communication -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Communication</h2>
            <ul class="space-y-2">
                <li>
                    <a href="<?php echo SITE_URL; ?>/admin/newsletter.php" 
                       class="text-green-600 hover:text-green-800">
                        Send Newsletter
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/admin/announcements.php" 
                       class="text-green-600 hover:text-green-800">
                        Manage Announcements
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Statistics & Reports -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Statistics & Reports</h2>
            <ul class="space-y-2">
                <li>
                    <a href="<?php echo SITE_URL; ?>/admin/sales_report.php" 
                       class="text-green-600 hover:text-green-800">
                        Sales Reports
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/admin/product_stats.php" 
                       class="text-green-600 hover:text-green-800">
                        Product Statistics
                    </a>
                </li>
                <li>
                    <a href="<?php echo SITE_URL; ?>/admin/customer_stats.php" 
                       class="text-green-600 hover:text-green-800">
                        Customer Analytics
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 