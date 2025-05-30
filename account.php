<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Get database connection
$mysqli = get_db_connection();

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    $_SESSION['flash_message'] = 'Please log in to access your account.';
    $_SESSION['flash_type'] = 'error';
    header('Location: ' . SITE_URL . '/account_stuff/login.php');
    exit();
}

// Handle account deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
    // Get user's orders to check if they have any
    $orders_query = $mysqli->prepare("SELECT COUNT(*) as order_count FROM tblorder WHERE customer_id = ?");
    $orders_query->bind_param("i", $_SESSION['customer_id']);
    $orders_query->execute();
    $order_result = $orders_query->get_result()->fetch_assoc();
    
    if ($order_result['order_count'] > 0) {
        $_SESSION['flash_message'] = 'Cannot delete account: You have existing orders. Please contact support for assistance.';
        $_SESSION['flash_type'] = 'error';
    } else {
        // Begin transaction
        $mysqli->begin_transaction();
        
        try {
            // Delete from wishlist
            $mysqli->query("DELETE FROM tblwishlist WHERE customer_id = " . $_SESSION['customer_id']);
            
            // Delete from cart
            $mysqli->query("DELETE FROM tblcart WHERE customer_id = " . $_SESSION['customer_id']);
            
            // Delete from website reviews
            $mysqli->query("DELETE FROM tblwebsite_reviews WHERE customer_id = " . $_SESSION['customer_id']);
            
            // Delete the customer
            $delete_query = $mysqli->prepare("DELETE FROM tblcustomer WHERE customer_id = ?");
            $delete_query->bind_param("i", $_SESSION['customer_id']);
            
            if ($delete_query->execute()) {
                $mysqli->commit();
                
                // Set success message before destroying session
                session_start();
                $_SESSION['flash_message'] = 'Your account has been successfully deleted.';
                $_SESSION['flash_type'] = 'success';
                
                // Destroy session and redirect to home
                session_destroy();
                header('Location: ' . SITE_URL . '/index.php');
                exit();
            } else {
                throw new Exception("Failed to delete account");
            }
        } catch (Exception $e) {
            $mysqli->rollback();
            $_SESSION['flash_message'] = 'Failed to delete account. Please try again or contact support.';
            $_SESSION['flash_type'] = 'error';
        }
    }
}

// Fetch user information
$user_query = $mysqli->prepare("
    SELECT c.*, t.type 
    FROM tblcustomer c 
    JOIN tbltypes t ON c.type_id = t.type_id 
    WHERE c.customer_id = ?
");
$user_query->bind_param("i", $_SESSION['customer_id']);
$user_query->execute();
$user = $user_query->get_result()->fetch_assoc();

// Fetch user's order count
$order_count_query = $mysqli->prepare("SELECT COUNT(*) as total FROM tblorder WHERE customer_id = ?");
$order_count_query->bind_param("i", $_SESSION['customer_id']);
$order_count_query->execute();
$order_count = $order_count_query->get_result()->fetch_assoc()['total'];

// Include header after all potential redirects
include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-8">My Account</h1>
        
        <!-- Account Information -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Account Information</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-gray-600 mb-2">Name</p>
                    <p class="font-medium"><?php echo htmlspecialchars($user['customer_name']); ?></p>
                </div>
                <div>
                    <p class="text-gray-600 mb-2">Email</p>
                    <p class="font-medium"><?php echo htmlspecialchars($user['customer_email']); ?></p>
                </div>
                <div>
                    <p class="text-gray-600 mb-2">Phone</p>
                    <p class="font-medium"><?php echo htmlspecialchars($user['customer_phone']); ?></p>
                </div>
                <div>
                    <p class="text-gray-600 mb-2">Account Type</p>
                    <p class="font-medium"><?php echo htmlspecialchars($user['type']); ?></p>
                </div>
                <div>
                    <p class="text-gray-600 mb-2">Newsletter Subscription</p>
                    <p class="font-medium"><?php echo $user['mail_subscription'] ? 'Subscribed' : 'Not subscribed'; ?></p>
                </div>
                <div>
                    <p class="text-gray-600 mb-2">Total Orders</p>
                    <p class="font-medium"><?php echo $order_count; ?></p>
                </div>
            </div>
        </div>
        
        <!-- Quick Links -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Quick Links</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <a href="<?php echo SITE_URL; ?>/customers.php" 
                   class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-user-edit mr-2"></i>
                    Edit Profile
                </a>
                <a href="<?php echo SITE_URL; ?>/orders.php" 
                   class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-shopping-bag mr-2"></i>
                    View Orders
                </a>
                <a href="<?php echo SITE_URL; ?>/wishlist.php" 
                   class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-heart mr-2"></i>
                    My Wishlist
                </a>
                <a href="<?php echo SITE_URL; ?>/cart.php" 
                   class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-shopping-cart mr-2"></i>
                    My Cart
                </a>
            </div>
        </div>
        
        <!-- Delete Account -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4 text-red-600">Danger Zone</h2>
            <p class="text-gray-600 mb-4">
                Warning: Deleting your account is permanent and cannot be undone. 
                All your personal information will be removed from our system.
                <?php if ($order_count > 0): ?>
                    <br><strong>Note: You cannot delete your account while you have existing orders.</strong>
                <?php endif; ?>
            </p>
            <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete your account? This action cannot be undone.');">
                <button type="submit" 
                        name="delete_account"
                        <?php echo $order_count > 0 ? 'disabled' : ''; ?>
                        class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 <?php echo $order_count > 0 ? 'opacity-50 cursor-not-allowed' : ''; ?>">
                    <i class="fas fa-trash-alt mr-2"></i>
                    Delete Account
                </button>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 