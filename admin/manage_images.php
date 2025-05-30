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

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_image'])) {
    $product_id = (int)$_POST['product_id'];
    $variant_id = 1; // Default variant
    
    // Create uploads directory if it doesn't exist
    $upload_dir = __DIR__ . '/../uploads/products/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Handle file upload
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === 0) {
        $file = $_FILES['product_image'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($file_extension, $allowed_extensions)) {
            $_SESSION['flash_message'] = 'Invalid file type. Please upload an image (JPG, PNG, or GIF).';
            $_SESSION['flash_type'] = 'error';
        } else {
            // Generate unique filename
            $new_filename = 'product_' . $product_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Update database
                $image_path = 'uploads/products/' . $new_filename;
                $stmt = $mysqli->prepare("UPDATE tblvariant SET image_directory = ? WHERE product_id = ? AND variant_id = ?");
                $stmt->bind_param("sii", $image_path, $product_id, $variant_id);
                
                if ($stmt->execute()) {
                    $_SESSION['flash_message'] = 'Image uploaded successfully.';
                    $_SESSION['flash_type'] = 'success';
                } else {
                    $_SESSION['flash_message'] = 'Failed to update database.';
                    $_SESSION['flash_type'] = 'error';
                }
            } else {
                $_SESSION['flash_message'] = 'Failed to upload image.';
                $_SESSION['flash_type'] = 'error';
            }
        }
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch all products
$products_query = $mysqli->query("
    SELECT p.*, v.image_directory, b.brand_name 
    FROM tblproducts p 
    LEFT JOIN tblvariant v ON p.product_id = v.product_id AND v.variant_id = 1
    LEFT JOIN tblbrand b ON p.brand_id = b.brand_id
    ORDER BY p.product_name
");
$products = $products_query->fetch_all(MYSQLI_ASSOC);

// Include header after all potential redirects
include '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Manage Product Images</h1>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($products as $product): ?>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="font-bold text-lg mb-4"><?php echo htmlspecialchars($product['product_name']); ?></h3>
                
                <div class="mb-4">
                    <?php if (!empty($product['image_directory'])): ?>
                        <img src="<?php echo SITE_URL . '/' . $product['image_directory']; ?>" 
                             alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                             class="w-full h-48 object-cover rounded-lg">
                    <?php else: ?>
                        <div class="w-full h-48 bg-gray-100 flex items-center justify-center rounded-lg">
                            <span class="text-gray-400">No image</span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Upload New Image
                        </label>
                        <input type="file" 
                               name="product_image" 
                               accept="image/*"
                               required
                               class="w-full text-sm text-gray-500
                                      file:mr-4 file:py-2 file:px-4
                                      file:rounded-full file:border-0
                                      file:text-sm file:font-semibold
                                      file:bg-green-50 file:text-green-700
                                      hover:file:bg-green-100">
                    </div>
                    
                    <button type="submit" 
                            name="upload_image" 
                            class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                        Upload Image
                    </button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 