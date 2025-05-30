<?php
require_once __DIR__ . '/../includes/config.php';

// Get database connection
$mysqli = get_db_connection();
require_once '../includes/header.php';

// Get date range from query parameters or default to last 30 days
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Get sales statistics
$sales_stats = $mysqli->query("
    SELECT 
        COUNT(DISTINCT o.order_id) as total_orders,
        SUM(oi.quantity * oi.price) as total_revenue,
        AVG(oi.quantity * oi.price) as average_order_value,
        COUNT(DISTINCT o.customer_id) as unique_customers
    FROM tblorders o
    JOIN tblorderitems oi ON o.order_id = oi.order_id
    WHERE o.order_date BETWEEN '$start_date' AND '$end_date'
")->fetch_assoc();

// Get best-selling products
$best_selling_products = $mysqli->query("
    SELECT 
        p.product_id,
        p.product_name,
        b.brand_name,
        c.category_name,
        SUM(oi.quantity) as total_quantity,
        SUM(oi.quantity * oi.price) as total_revenue
    FROM tblproducts p
    JOIN tblorderitems oi ON p.product_id = oi.product_id
    JOIN tblorders o ON oi.order_id = o.order_id
    LEFT JOIN tblbrand b ON p.brand_id = b.brand_id
    LEFT JOIN tblcategory c ON p.category_id = c.category_id
    WHERE o.order_date BETWEEN '$start_date' AND '$end_date'
    GROUP BY p.product_id
    ORDER BY total_quantity DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Get sales by category
$sales_by_category = $mysqli->query("
    SELECT 
        c.category_name,
        COUNT(DISTINCT o.order_id) as order_count,
        SUM(oi.quantity * oi.price) as total_revenue
    FROM tblcategory c
    JOIN tblproducts p ON c.category_id = p.category_id
    JOIN tblorderitems oi ON p.product_id = oi.product_id
    JOIN tblorders o ON oi.order_id = o.order_id
    WHERE o.order_date BETWEEN '$start_date' AND '$end_date'
    GROUP BY c.category_id
    ORDER BY total_revenue DESC
")->fetch_all(MYSQLI_ASSOC);

// Get daily sales for chart
$daily_sales = $mysqli->query("
    SELECT 
        DATE(o.order_date) as date,
        COUNT(DISTINCT o.order_id) as order_count,
        SUM(oi.quantity * oi.price) as total_revenue
    FROM tblorders o
    JOIN tblorderitems oi ON o.order_id = oi.order_id
    WHERE o.order_date BETWEEN '$start_date' AND '$end_date'
    GROUP BY DATE(o.order_date)
    ORDER BY date
")->fetch_all(MYSQLI_ASSOC);
?>

<div class="max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-800">Sales Statistics</h2>
        
        <form method="GET" action="" class="flex space-x-4">
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
            </div>
            
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
            </div>
            
            <div class="flex items-end">
                <button type="submit" 
                    class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                    Update
                </button>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-2">Total Orders</h3>
            <p class="text-3xl font-bold text-gray-900"><?php echo number_format($sales_stats['total_orders']); ?></p>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-2">Total Revenue</h3>
            <p class="text-3xl font-bold text-gray-900">€<?php echo number_format($sales_stats['total_revenue'], 2); ?></p>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-2">Average Order Value</h3>
            <p class="text-3xl font-bold text-gray-900">€<?php echo number_format($sales_stats['average_order_value'], 2); ?></p>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-2">Unique Customers</h3>
            <p class="text-3xl font-bold text-gray-900"><?php echo number_format($sales_stats['unique_customers']); ?></p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Best-Selling Products</h3>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Brand</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($best_selling_products as $product): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($product['product_name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($product['category_name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($product['brand_name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo number_format($product['total_quantity']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    €<?php echo number_format($product['total_revenue'], 2); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Sales by Category</h3>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Orders</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($sales_by_category as $category): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo number_format($category['order_count']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    €<?php echo number_format($category['total_revenue'], 2); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Daily Sales</h3>
        
        <canvas id="salesChart" class="w-full h-64"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('salesChart').getContext('2d');
const salesData = <?php echo json_encode($daily_sales); ?>;

new Chart(ctx, {
    type: 'line',
    data: {
        labels: salesData.map(item => item.date),
        datasets: [{
            label: 'Revenue',
            data: salesData.map(item => item.total_revenue),
            borderColor: 'rgb(34, 197, 94)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '€' + value.toLocaleString();
                    }
                }
            }
        }
    }
});
</script>

<?php require_once '../includes/footer.php'; ?> 