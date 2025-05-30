<?php
require_once __DIR__ . '/../includes/config.php';

// Get database connection
$mysqli = get_db_connection();
require_once '../includes/header.php';

// Handle category deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete']) && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $category_id = (int)($_POST['category_id'] ?? 0);
    
    $stmt = $mysqli->prepare("DELETE FROM tblcategory WHERE category_id = ?");
    $stmt->bind_param("i", $category_id);
    
    if ($stmt->execute()) {
        set_flash_message('success', 'Category deleted successfully');
    } else {
        set_flash_message('error', 'Failed to delete category');
    }
    
    header('Location: ' . SITE_URL . '/admin/categories.php');
    exit();
}

// Handle category addition/editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save']) && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $category_name = sanitize_input($_POST['category_name'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    
    // Validate input
    $errors = [];
    if (empty($category_name)) $errors[] = 'Category name is required';
    
    if (empty($errors)) {
        if ($category_id > 0) {
            // Update existing category
            $stmt = $mysqli->prepare("UPDATE tblcategory SET category_name = ? WHERE category_id = ?");
            $stmt->bind_param("si", $category_name, $category_id);
        } else {
            // Add new category
            $stmt = $mysqli->prepare("INSERT INTO tblcategory (category_name) VALUES (?)");
            $stmt->bind_param("s", $category_name);
        }
        
        if ($stmt->execute()) {
            set_flash_message('success', 'Category ' . ($category_id > 0 ? 'updated' : 'added') . ' successfully');
        } else {
            set_flash_message('error', 'Failed to ' . ($category_id > 0 ? 'update' : 'add') . ' category');
        }
        
        header('Location: ' . SITE_URL . '/admin/categories.php');
        exit();
    }
}

// Get all categories
$categories = $mysqli->query("
    SELECT c.*, 
           COUNT(p.product_id) as product_count
    FROM tblcategory c
    LEFT JOIN tblproducts p ON c.category_id = p.category_id
    GROUP BY c.category_id
    ORDER BY c.category_name
")->fetch_all(MYSQLI_ASSOC);

// Get category for editing if ID is provided
$edit_category = null;
if (isset($_GET['edit'])) {
    $category_id = (int)$_GET['edit'];
    $stmt = $mysqli->prepare("SELECT * FROM tblcategory WHERE category_id = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $edit_category = $stmt->get_result()->fetch_assoc();
}
?>

<div class="max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-800">Manage Categories</h2>
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
            <?php echo $edit_category ? 'Edit Category' : 'Add New Category'; ?>
        </h3>
        
        <form method="POST" action="" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <?php if ($edit_category): ?>
                <input type="hidden" name="category_id" value="<?php echo $edit_category['category_id']; ?>">
            <?php endif; ?>
            
            <div>
                <label for="category_name" class="block text-sm font-medium text-gray-700">Category Name</label>
                <input type="text" id="category_name" name="category_name" required
                    value="<?php echo $edit_category ? htmlspecialchars($edit_category['category_name']) : ''; ?>"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
            </div>

            <div class="flex justify-end space-x-4">
                <?php if ($edit_category): ?>
                    <a href="categories.php" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">
                        Cancel
                    </a>
                <?php endif; ?>
                <button type="submit" name="save" 
                    class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                    <?php echo $edit_category ? 'Update Category' : 'Add Category'; ?>
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Products</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($categories as $category): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($category['category_name']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo $category['product_count']; ?> products
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="?edit=<?php echo $category['category_id']; ?>" 
                                class="text-green-600 hover:text-green-900 mr-4">Edit</a>
                            
                            <form method="POST" action="" class="inline" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                <input type="hidden" name="category_id" value="<?php echo $category['category_id']; ?>">
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