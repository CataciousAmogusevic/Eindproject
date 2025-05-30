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

// Handle stock update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $stock_id = (int)$_POST['stock_id'];
    $stock = (int)$_POST['stock'];
    
    $stmt = $mysqli->prepare("UPDATE tblstock SET stock = ? WHERE stock_id = ?");
    $stmt->bind_param("ii", $stock, $stock_id);
    
    if ($stmt->execute()) {
        $_SESSION['flash_message'] = 'Stock updated successfully.';
        $_SESSION['flash_type'] = 'success';
    } else {
        $_SESSION['flash_message'] = 'Failed to update stock.';
        $_SESSION['flash_type'] = 'error';
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Get all stock with product details
$stock_query = $mysqli->query("
    SELECT s.*, p.product_name, v.image_directory
    FROM tblstock s
    LEFT JOIN tblproducts p ON s.product_id = p.product_id
    LEFT JOIN tblvariant v ON s.product_id = v.product_id AND s.variant_id = v.variant_id
    ORDER BY p.product_name, s.size
");
$stocks = $stock_query->fetch_all(MYSQLI_ASSOC);
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">Stock Management</h1>
        <a href="<?php echo SITE_URL; ?>/admin/admin-panel.php" 
           class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
            Back to Admin Panel
        </a>
    </div>
    
    <!-- Stock List -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Size</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Current Stock</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($stocks as $stock): ?>
                        <tr>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <?php if ($stock['image_directory']): ?>
                                        <img src="<?php echo SITE_URL . '/' . $stock['image_directory']; ?>" 
                                             alt="<?php echo htmlspecialchars($stock['product_name']); ?>"
                                             class="h-10 w-10 object-cover rounded-full mr-3">
                                    <?php endif; ?>
                                    <div class="text-sm text-gray-900">
                                        <?php echo htmlspecialchars($stock['product_name']); ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?php echo htmlspecialchars($stock['size']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?php echo $stock['stock']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <form method="POST" action="" class="inline-flex space-x-2">
                                    <input type="hidden" name="stock_id" value="<?php echo $stock['stock_id']; ?>">
                                    <input type="number" 
                                           name="stock" 
                                           value="<?php echo $stock['stock']; ?>"
                                           min="0"
                                           class="w-20 text-sm border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                                    <button type="submit" 
                                            name="update_stock"
                                            class="bg-green-600 text-white px-3 py-1 rounded-md text-sm hover:bg-green-700">
                                        Update
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 