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

// Get product statistics
$products_query = $mysqli->query("
    SELECT 
        p.*,
        b.brand_name,
        c.category_name,
        COALESCE(SUM(o.quantity), 0) as total_sold,
        COALESCE(SUM(o.total_amount), 0) as total_revenue,
        (SELECT COALESCE(SUM(stock), 0) FROM tblstock WHERE product_id = p.product_id) as current_stock,
        (SELECT COUNT(*) FROM tblreviews WHERE product_id = p.product_id) as review_count,
        (SELECT AVG(rating) FROM tblreviews WHERE product_id = p.product_id) as avg_rating
    FROM tblproducts p
    LEFT JOIN tblbrand b ON p.brand_id = b.brand_id
    LEFT JOIN tblcategory c ON p.category_id = c.category_id
    LEFT JOIN tblorders o ON p.product_id = o.product_id
    GROUP BY p.product_id
    ORDER BY total_sold DESC
");
$products = $products_query->fetch_all(MYSQLI_ASSOC);

// Calculate profit margins
foreach ($products as &$product) {
    $product['profit_margin'] = $product['price'] > 0 ? 
        (($product['price'] - $product['cost_price']) / $product['price']) * 100 : 0;
}

// Get category performance
$category_query = $mysqli->query("
    SELECT 
        c.category_name,
        COUNT(DISTINCT p.product_id) as product_count,
        COALESCE(SUM(o.quantity), 0) as total_sold,
        COALESCE(SUM(o.total_amount), 0) as total_revenue
    FROM tblcategory c
    LEFT JOIN tblproducts p ON c.category_id = p.category_id
    LEFT JOIN tblorders o ON p.product_id = o.product_id
    GROUP BY c.category_id
    ORDER BY total_revenue DESC
");
$categories = $category_query->fetch_all(MYSQLI_ASSOC);
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">Product Statistics</h1>
        <a href="<?php echo SITE_URL; ?>/admin/admin-panel.php" 
           class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
            Back to Admin Panel
        </a>
    </div>
    
    <!-- Category Performance -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">Category Performance</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Products</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Sold</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Revenue</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?php echo htmlspecialchars($category['category_name']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?php echo $category['product_count']; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?php echo $category['total_sold']; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                €<?php echo number_format($category['total_revenue'], 2); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Product Performance -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4">Product Performance</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Brand</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Margin</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sold</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Revenue</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rating</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?php echo htmlspecialchars($product['product_name']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?php echo htmlspecialchars($product['brand_name']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?php echo htmlspecialchars($product['category_name']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                €<?php echo number_format($product['price'], 2); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?php echo number_format($product['profit_margin'], 1); ?>%
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?php echo $product['current_stock']; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?php echo $product['total_sold']; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                €<?php echo number_format($product['total_revenue'], 2); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?php 
                                if ($product['review_count'] > 0) {
                                    echo number_format($product['avg_rating'], 1) . ' (' . $product['review_count'] . ')';
                                } else {
                                    echo 'No reviews';
                                }
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