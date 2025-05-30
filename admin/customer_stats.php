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

// Get customer statistics
$customers_query = $mysqli->query("
    SELECT 
        c.*,
        COUNT(DISTINCT o.order_id) as total_orders,
        COALESCE(SUM(o.total_amount), 0) as total_spent,
        COUNT(DISTINCT r.review_id) as total_reviews,
        MAX(o.delivery_date) as last_order_date
    FROM tblcustomer c
    LEFT JOIN tblorders o ON c.customer_id = o.customer_id
    LEFT JOIN tblreviews r ON c.customer_id = r.customer_id
    WHERE c.type_id = 2
    GROUP BY c.customer_id
    ORDER BY total_spent DESC
");
$customers = $customers_query->fetch_all(MYSQLI_ASSOC);

// Calculate average order value
$total_customers = count($customers);
$total_orders = 0;
$total_revenue = 0;
foreach ($customers as $customer) {
    $total_orders += $customer['total_orders'];
    $total_revenue += $customer['total_spent'];
}
$avg_order_value = $total_orders > 0 ? $total_revenue / $total_orders : 0;

// Get customer loyalty distribution
$loyalty_query = $mysqli->query("
    SELECT 
        CASE 
            WHEN loyalty_points >= 100 THEN 'Gold'
            WHEN loyalty_points >= 50 THEN 'Silver'
            ELSE 'Bronze'
        END as tier,
        COUNT(*) as count
    FROM tblcustomer
    WHERE type_id = 2
    GROUP BY tier
    ORDER BY MIN(loyalty_points) DESC
");
$loyalty_tiers = $loyalty_query->fetch_all(MYSQLI_ASSOC);

// Get newsletter subscription stats
$newsletter_query = $mysqli->query("
    SELECT 
        mail_subscription,
        COUNT(*) as count
    FROM tblcustomer
    WHERE type_id = 2
    GROUP BY mail_subscription
");
$newsletter_stats = $newsletter_query->fetch_all(MYSQLI_ASSOC);
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">Customer Analytics</h1>
        <a href="<?php echo SITE_URL; ?>/admin/admin-panel.php" 
           class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
            Back to Admin Panel
        </a>
    </div>
    
    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-2">Total Customers</h3>
            <p class="text-3xl font-bold text-green-600"><?php echo $total_customers; ?></p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-2">Total Orders</h3>
            <p class="text-3xl font-bold text-green-600"><?php echo $total_orders; ?></p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-2">Total Revenue</h3>
            <p class="text-3xl font-bold text-green-600">€<?php echo number_format($total_revenue, 2); ?></p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-2">Avg. Order Value</h3>
            <p class="text-3xl font-bold text-green-600">€<?php echo number_format($avg_order_value, 2); ?></p>
        </div>
    </div>
    
    <!-- Loyalty Distribution -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">Customer Loyalty Distribution</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php foreach ($loyalty_tiers as $tier): ?>
                <div class="text-center">
                    <h3 class="text-lg font-medium mb-2"><?php echo $tier['tier']; ?> Tier</h3>
                    <p class="text-3xl font-bold"><?php echo $tier['count']; ?></p>
                    <p class="text-sm text-gray-500">
                        <?php echo number_format(($tier['count'] / $total_customers) * 100, 1); ?>%
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Newsletter Stats -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">Newsletter Subscription</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php foreach ($newsletter_stats as $stat): ?>
                <div class="text-center">
                    <h3 class="text-lg font-medium mb-2">
                        <?php echo $stat['mail_subscription'] ? 'Subscribed' : 'Not Subscribed'; ?>
                    </h3>
                    <p class="text-3xl font-bold"><?php echo $stat['count']; ?></p>
                    <p class="text-sm text-gray-500">
                        <?php echo number_format(($stat['count'] / $total_customers) * 100, 1); ?>%
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Customer List -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4">Customer Details</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Orders</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Spent</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reviews</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Loyalty Points</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Order</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">
                                    <?php echo htmlspecialchars($customer['customer_name']); ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?php echo htmlspecialchars($customer['email']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?php echo $customer['total_orders']; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                €<?php echo number_format($customer['total_spent'], 2); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?php echo $customer['total_reviews']; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?php echo $customer['loyalty_points']; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?php 
                                echo $customer['last_order_date'] ? 
                                    date('M j, Y', strtotime($customer['last_order_date'])) : 
                                    'Never';
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 