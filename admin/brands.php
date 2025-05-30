<?php
require_once __DIR__ . '/../includes/config.php';

// Get database connection
$mysqli = get_db_connection();
require_once '../includes/header.php';

// Handle brand deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete']) && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $brand_id = (int)($_POST['brand_id'] ?? 0);
    
    $stmt = $mysqli->prepare("DELETE FROM tblbrand WHERE brand_id = ?");
    $stmt->bind_param("i", $brand_id);
    
    if ($stmt->execute()) {
        set_flash_message('success', 'Brand deleted successfully');
    } else {
        set_flash_message('error', 'Failed to delete brand');
    }
    
    header('Location: ' . SITE_URL . '/admin/brands.php');
    exit();
}

// Handle brand addition/editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save']) && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $brand_name = sanitize_input($_POST['brand_name'] ?? '');
    $brand_id = (int)($_POST['brand_id'] ?? 0);
    
    // Validate input
    $errors = [];
    if (empty($brand_name)) $errors[] = 'Brand name is required';
    
    if (empty($errors)) {
        if ($brand_id > 0) {
            // Update existing brand
            $stmt = $mysqli->prepare("UPDATE tblbrand SET brand_name = ? WHERE brand_id = ?");
            $stmt->bind_param("si", $brand_name, $brand_id);
        } else {
            // Add new brand
            $stmt = $mysqli->prepare("INSERT INTO tblbrand (brand_name) VALUES (?)");
            $stmt->bind_param("s", $brand_name);
        }
        
        if ($stmt->execute()) {
            set_flash_message('success', 'Brand ' . ($brand_id > 0 ? 'updated' : 'added') . ' successfully');
        } else {
            set_flash_message('error', 'Failed to ' . ($brand_id > 0 ? 'update' : 'add') . ' brand');
        }
        
        header('Location: ' . SITE_URL . '/admin/brands.php');
        exit();
    }
}

// Get all brands
$brands = $mysqli->query("
    SELECT b.*, 
           COUNT(p.product_id) as product_count
    FROM tblbrand b
    LEFT JOIN tblproducts p ON b.brand_id = p.brand_id
    GROUP BY b.brand_id
    ORDER BY b.brand_name
")->fetch_all(MYSQLI_ASSOC);

// Get brand for editing if ID is provided
$edit_brand = null;
if (isset($_GET['edit'])) {
    $brand_id = (int)$_GET['edit'];
    $stmt = $mysqli->prepare("SELECT * FROM tblbrand WHERE brand_id = ?");
    $stmt->bind_param("i", $brand_id);
    $stmt->execute();
    $edit_brand = $stmt->get_result()->fetch_assoc();
}
?>

<div class="max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-800">Manage Brands</h2>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="mb-4 p-4 rounded <?php echo $_SESSION['flash_type'] === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'; ?>">
            <?php 
            echo $_SESSION['flash_message'];
            unset($_SESSION['flash_message'], $_SESSION['flash_type']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="mb-4 p-4 rounded bg-red-100 text-red-700">
            <ul class="list-disc list-inside">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">
            <?php echo $edit_brand ? 'Edit Brand' : 'Add New Brand'; ?>
        </h3>
        
        <form method="POST" action="" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <?php if ($edit_brand): ?>
                <input type="hidden" name="brand_id" value="<?php echo $edit_brand['brand_id']; ?>">
            <?php endif; ?>
            
            <div>
                <label for="brand_name" class="block text-sm font-medium text-gray-700">Brand Name</label>
                <input type="text" id="brand_name" name="brand_name" required
                    value="<?php echo $edit_brand ? htmlspecialchars($edit_brand['brand_name']) : ''; ?>"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
            </div>

            <div class="flex justify-end space-x-4">
                <?php if ($edit_brand): ?>
                    <a href="brands.php" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">
                        Cancel
                    </a>
                <?php endif; ?>
                <button type="submit" name="save" 
                    class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                    <?php echo $edit_brand ? 'Update Brand' : 'Add Brand'; ?>
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Brand Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Products</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($brands as $brand): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($brand['brand_name']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo $brand['product_count']; ?> products
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="?edit=<?php echo $brand['brand_id']; ?>" 
                                class="text-green-600 hover:text-green-900 mr-4">Edit</a>
                            
                            <form method="POST" action="" class="inline" onsubmit="return confirm('Are you sure you want to delete this brand?');">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                <input type="hidden" name="brand_id" value="<?php echo $brand['brand_id']; ?>">
                                <button type="submit" name="delete" class="text-red-600 hover:text-red-900">
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

<?php require_once '../includes/footer.php'; ?> 