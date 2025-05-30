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

// Get date range from query parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get sales data
$sales_query = $mysqli->prepare("
    SELECT 
        DATE(o.delivery_date) as sale_date,
        COUNT(DISTINCT o.order_id) as total_orders,
        SUM(o.total_amount) as total_sales,
        SUM(o.quantity) as total_items
    FROM tblorders o
    WHERE o.delivery_date BETWEEN ? AND ?
    GROUP BY DATE(o.delivery_date)
    ORDER BY sale_date DESC
");
$sales_query->bind_param("ss", $start_date, $end_date);
$sales_query->execute();
$sales_result = $sales_query->get_result();
$sales_data = $sales_result->fetch_all(MYSQLI_ASSOC);

// Calculate totals
$total_orders = 0;
$total_sales = 0;
$total_items = 0;
foreach ($sales_data as $day) {
    $total_orders += $day['total_orders'];
    $total_sales += $day['total_sales'];
    $total_items += $day['total_items'];
}

// Get top selling products
$top_products_query = $mysqli->prepare("
    SELECT 
        p.product_name,
        SUM(o.quantity) as total_quantity,
        SUM(o.total_amount) as total_revenue
    FROM tblorders o
    JOIN tblproducts p ON o.product_id = p.product_id
    WHERE o.delivery_date BETWEEN ? AND ?
    GROUP BY p.product_id
    ORDER BY total_quantity DESC
    LIMIT 5
");
$top_products_query->bind_param("ss", $start_date, $end_date);
$top_products_query->execute();
$top_products = $top_products_query->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">Sales Report</h1>
        <a href="<?php echo SITE_URL; ?>/admin/admin-panel.php" 
           class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
            Back to Admin Panel
        </a>
    </div>
    
    <!-- Date Range Filter -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <form method="GET" action="" class="flex space-x-4">
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">
                    Start Date
                </label>
                <input type="date" 
                       id="start_date" 
                       name="start_date" 
                       value="<?php echo $start_date; ?>"
                       class="border border-gray-300 rounded-md shadow-sm p-2">
            </div>
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">
                    End Date
                </label>
                <input type="date" 
                       id="end_date" 
                       name="end_date" 
                       value="<?php echo $end_date; ?>"
                       class="border border-gray-300 rounded-md shadow-sm p-2">
            </div>
            <div class="flex items-end">
                <button type="submit"
                        class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                    Filter
                </button>
            </div>
        </form>
    </div>
    
    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-2">Total Orders</h3>
            <p class="text-3xl font-bold text-green-600"><?php echo $total_orders; ?></p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-2">Total Sales</h3>
            <p class="text-3xl font-bold text-green-600">€<?php echo number_format($total_sales, 2); ?></p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-2">Total Items Sold</h3>
            <p class="text-3xl font-bold text-green-600"><?php echo $total_items; ?></p>
        </div>
    </div>
    
    <!-- Top Selling Products -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">Top Selling Products</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity Sold</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Revenue</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($top_products as $product): ?>
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?php echo htmlspecialchars($product['product_name']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?php echo $product['total_quantity']; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                €<?php echo number_format($product['total_revenue'], 2); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Daily Sales Table -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4">Daily Sales</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Orders</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Items Sold</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Sales</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($sales_data as $day): ?>
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?php echo date('M j, Y', strtotime($day['sale_date'])); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?php echo $day['total_orders']; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?php echo $day['total_items']; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                €<?php echo number_format($day['total_sales'], 2); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 