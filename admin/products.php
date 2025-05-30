<?php
require_once __DIR__ . '/../includes/config.php';

// Get database connection
$mysqli = get_db_connection();

// Check if user is admin
if (!isset($_SESSION['type']) || $_SESSION['type'] !== 'admin') {
    $_SESSION['flash_message'] = 'Access denied. Admin privileges required.';
    $_SESSION['flash_type'] = 'error';
    header('Location: ' . SITE_URL . '/index.php');
    exit();
}

// Handle product deletion
if (isset($_POST['delete_product'])) {
    $product_id = (int)$_POST['product_id'];
    $stmt = $mysqli->prepare("DELETE FROM tblproducts WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    
    if ($stmt->execute()) {
        $_SESSION['flash_message'] = 'Product deleted successfully.';
        $_SESSION['flash_type'] = 'success';
    } else {
        $_SESSION['flash_message'] = 'Failed to delete product.';
        $_SESSION['flash_type'] = 'error';
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Handle product addition/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    $product_name = $_POST['product_name'];
    $price = (float)$_POST['price'];
    $brand_id = (int)$_POST['brand_id'];
    $category_id = (int)$_POST['category_id'];
    $cost_price = (float)$_POST['cost_price'];
    
    if (isset($_POST['product_id'])) {
        // Update existing product
        $stmt = $mysqli->prepare("
            UPDATE tblproducts 
            SET product_name = ?, price = ?, brand_id = ?, category_id = ?, cost_price = ?
            WHERE product_id = ?
        ");
        $product_id = (int)$_POST['product_id'];
        $stmt->bind_param("sdiidd", $product_name, $price, $brand_id, $category_id, $cost_price, $product_id);
    } else {
        // Add new product
        $stmt = $mysqli->prepare("
            INSERT INTO tblproducts (product_name, price, brand_id, category_id, cost_price)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sdiid", $product_name, $price, $brand_id, $category_id, $cost_price);
    }
    
    if ($stmt->execute()) {
        $_SESSION['flash_message'] = 'Product saved successfully.';
        $_SESSION['flash_type'] = 'success';
    } else {
        $_SESSION['flash_message'] = 'Failed to save product.';
        $_SESSION['flash_type'] = 'error';
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Get all products with brand and category names
$products_query = $mysqli->query("
    SELECT p.*, b.brand_name, c.category_name 
    FROM tblproducts p
    LEFT JOIN tblbrand b ON p.brand_id = b.brand_id
    LEFT JOIN tblcategory c ON p.category_id = c.category_id
    ORDER BY p.product_id DESC
");
$products = $products_query->fetch_all(MYSQLI_ASSOC);

// Get brands and categories for dropdowns
$brands = $mysqli->query("SELECT * FROM tblbrand ORDER BY brand_name")->fetch_all(MYSQLI_ASSOC);
$categories = $mysqli->query("SELECT * FROM tblcategory ORDER BY category_name")->fetch_all(MYSQLI_ASSOC);

// Include header after all potential redirects
include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">Product Management</h1>
        <a href="<?php echo SITE_URL; ?>/admin/admin-panel.php" 
           class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
            Back to Admin Panel
        </a>
    </div>
    
    <!-- Add/Edit Product Form -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">Add New Product</h2>
        
        <form method="POST" action="" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="product_name" class="block text-sm font-medium text-gray-700 mb-1">
                        Product Name
                    </label>
                    <input type="text" 
                           id="product_name" 
                           name="product_name" 
                           required
                           class="w-full border border-gray-300 rounded-md shadow-sm p-2">
                </div>
                
                <div>
                    <label for="price" class="block text-sm font-medium text-gray-700 mb-1">
                        Price
                    </label>
                    <input type="number" 
                           id="price" 
                           name="price" 
                           step="0.01" 
                           required
                           class="w-full border border-gray-300 rounded-md shadow-sm p-2">
                </div>
                
                <div>
                    <label for="brand_id" class="block text-sm font-medium text-gray-700 mb-1">
                        Brand
                    </label>
                    <select id="brand_id" 
                            name="brand_id" 
                            required
                            class="w-full border border-gray-300 rounded-md shadow-sm p-2">
                        <?php foreach ($brands as $brand): ?>
                            <option value="<?php echo $brand['brand_id']; ?>">
                                <?php echo htmlspecialchars($brand['brand_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">
                        Category
                    </label>
                    <select id="category_id" 
                            name="category_id" 
                            required
                            class="w-full border border-gray-300 rounded-md shadow-sm p-2">
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['category_id']; ?>">
                                <?php echo htmlspecialchars($category['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="cost_price" class="block text-sm font-medium text-gray-700 mb-1">
                        Cost Price
                    </label>
                    <input type="number" 
                           id="cost_price" 
                           name="cost_price" 
                           step="0.01" 
                           required
                           class="w-full border border-gray-300 rounded-md shadow-sm p-2">
                </div>
            </div>
            
            <div class="flex justify-end">
                <button type="submit" 
                        name="save_product"
                        class="bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700">
                    Add Product
                </button>
            </div>
        </form>
    </div>
    
    <!-- Products List -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4">All Products</h2>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Brand</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cost</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo $product['product_id']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($product['product_name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($product['brand_name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($product['category_name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">€<?php echo number_format($product['price'], 2); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">€<?php echo number_format($product['cost_price'], 2); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap space-x-2">
                                <form method="POST" action="" class="inline">
                                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                    <button type="submit" 
                                            name="delete_product"
                                            onclick="return confirm('Are you sure you want to delete this product?')"
                                            class="text-red-600 hover:text-red-900">
                                        Delete
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