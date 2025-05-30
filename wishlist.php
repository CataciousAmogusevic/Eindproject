<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Get database connection
$mysqli = get_db_connection();

// Initialize wishlist
$wishlist = new Wishlist($mysqli);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    $response = ['success' => false, 'message' => 'Invalid request'];
    
    if (!isset($_SESSION['customer_id'])) {
        echo json_encode(['success' => false, 'message' => 'Please log in to manage wishlist']);
        exit();
    }
    
    if (isset($input['action'])) {
        switch ($input['action']) {
            case 'add':
                if (isset($input['product_id'])) {
                    $success = $wishlist->addItem((int)$input['product_id']);
                    $errors = $wishlist->getErrors();
                    
                    $response = [
                        'success' => $success,
                        'message' => $success ? 'Item added to wishlist' : implode(', ', $errors),
                        'wishlist_count' => $wishlist->getItemCount()
                    ];
                }
                break;
                
            case 'remove':
                if (isset($input['product_id'])) {
                    $success = $wishlist->removeItem((int)$input['product_id']);
                    $errors = $wishlist->getErrors();
                    
                    $response = [
                        'success' => $success,
                        'message' => $success ? 'Item removed from wishlist' : implode(', ', $errors),
                        'wishlist_count' => $wishlist->getItemCount()
                    ];
                }
                break;
                
            case 'get_count':
                $count = $wishlist->getItemCount();
                $response = [
                    'success' => true,
                    'count' => $count
                ];
                break;
        }
    }
    
    echo json_encode($response);
    exit();
}

// Get wishlist items
$wishlist_items = $wishlist->getItems();

// Include header after processing to avoid sending headers after output
include 'includes/header.php';
?>

<h2 class="text-2xl font-bold mb-6">My Wishlist</h2>

<?php if (empty($wishlist_items)): ?>
    <div class="bg-white rounded-lg shadow-md p-6 text-center">
        <p class="text-gray-600">Your wishlist is empty</p>
        <a href="<?php echo SITE_URL; ?>/index.php" class="inline-block mt-4 bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700">
            Continue Shopping
        </a>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <?php foreach ($wishlist_items as $item): ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="relative">
                    <?php if (!empty($item['image_directory'])): ?>
                        <img src="<?php echo htmlspecialchars($item['image_directory']); ?>" 
                             alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                             class="w-full h-48 object-cover">
                    <?php endif; ?>
                    <button onclick="removeFromWishlist(<?php echo $item['product_id']; ?>)"
                            class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-2 hover:bg-red-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="p-4">
                    <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($item['product_name']); ?></h3>
                    <p class="text-sm text-gray-600 mb-2"><?php echo htmlspecialchars($item['brand_name']); ?></p>
                    <p class="text-green-600 font-semibold mb-4">€<?php echo number_format($item['price'], 2); ?></p>
                    <div class="flex justify-between items-center">
                        <a href="product.php?id=<?php echo $item['product_id']; ?>" 
                           class="text-blue-600 hover:text-blue-800">View Details</a>
                        <button onclick="addToCart(<?php echo $item['product_id']; ?>)"
                                class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                            Add to Cart
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
    function removeFromWishlist(productId) {
        fetch('<?php echo SITE_URL; ?>/wishlist.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'remove',
                product_id: productId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Failed to remove item from wishlist');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while removing the item');
        });
    }

    function addToCart(productId) {
        fetch('<?php echo SITE_URL; ?>/cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'update',
                product_id: productId,
                quantity: 1
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Item added to cart successfully');
                // Optionally remove from wishlist after adding to cart
                removeFromWishlist(productId);
            } else {
                alert(data.message || 'Failed to add item to cart');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while adding the item to cart');
        });
    }
</script>

<?php include 'includes/footer.php'; ?> 