<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Get database connection
$mysqli = get_db_connection();

// Initialize cart and wishlist
$cart = new Cart($mysqli);
$wishlist = new Wishlist($mysqli);

$product_id = (int)($_GET['id'] ?? 0);

if ($product_id <= 0) {
    header('Location: ' . SITE_URL);
    exit();
}

// Get product details
$stmt = $mysqli->prepare("
    SELECT p.*, b.brand_name, c.category_name, v.image_directory,
           (SELECT AVG(rating) FROM tblreviews WHERE product_id = p.product_id) as average_rating,
           (SELECT COUNT(*) FROM tblreviews WHERE product_id = p.product_id) as review_count
    FROM tblproducts p
    LEFT JOIN tblbrand b ON p.brand_id = b.brand_id
    LEFT JOIN tblcategory c ON p.category_id = c.category_id
    LEFT JOIN tblvariant v ON p.product_id = v.product_id AND v.variant_id = 1
    WHERE p.product_id = ?
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header('Location: ' . SITE_URL);
    exit();
}

// Check if product is in wishlist
$isInWishlist = $wishlist->isInWishlist($product_id);

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isset($_SESSION['customer_id'])) {
        $_SESSION['flash_message'] = 'Please log in to submit a review';
        $_SESSION['flash_type'] = 'error';
        header('Location: ' . SITE_URL . '/product.php?id=' . $product_id);
        exit();
    }
    
    $rating = (int)($_POST['rating'] ?? 0);
    $comment = $_POST['comment'] ?? '';
    $customer_id = $_SESSION['customer_id'];
    
    $errors = [];
    if ($rating < 1 || $rating > 5) $errors[] = 'Please select a valid rating';
    if (empty($comment)) $errors[] = 'Please enter a review comment';
    
    if (empty($errors)) {
        $stmt = $mysqli->prepare("
            INSERT INTO tblreviews (product_id, customer_id, rating, text, approved)
            VALUES (?, ?, ?, ?, 0)
        ");
        $stmt->bind_param("iiis", $product_id, $customer_id, $rating, $comment);
        
        if ($stmt->execute()) {
            $_SESSION['flash_message'] = 'Review submitted successfully';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Failed to submit review';
            $_SESSION['flash_type'] = 'error';
        }
        
        header('Location: ' . SITE_URL . '/product.php?id=' . $product_id);
        exit();
    } else {
        $_SESSION['flash_message'] = implode(', ', $errors);
        $_SESSION['flash_type'] = 'error';
        header('Location: ' . SITE_URL . '/product.php?id=' . $product_id);
        exit();
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_to_cart':
                $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
                if ($cart->addItem($product_id, $quantity)) {
                    $_SESSION['flash_message'] = 'Product added to cart successfully!';
                    $_SESSION['flash_type'] = 'success';
                } else {
                    $_SESSION['flash_message'] = 'Failed to add product to cart.';
                    $_SESSION['flash_type'] = 'error';
                }
                break;

            case 'add_to_wishlist':
                if ($wishlist->addItem($product_id)) {
                    $_SESSION['flash_message'] = 'Product added to wishlist successfully!';
                    $_SESSION['flash_type'] = 'success';
                } else {
                    $_SESSION['flash_message'] = implode(', ', $wishlist->getErrors());
                    $_SESSION['flash_type'] = 'error';
                }
                break;
        }
        
        // Redirect to prevent form resubmission
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit();
    }
}

include 'includes/header.php';

// Get product reviews
$stmt = $mysqli->prepare("
    SELECT r.*, c.customer_name, r.created_at as review_date
    FROM tblreviews r
    JOIN tblcustomer c ON r.customer_id = c.customer_id
    WHERE r.product_id = ? AND r.approved = 1
    ORDER BY r.created_at DESC
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 p-6">
        <!-- Product Images -->
        <div>
            <?php if (!empty($product['image_directory'])): ?>
                <div class="relative">
                    <div class="aspect-w-1 aspect-h-1">
                        <img src="<?php echo htmlspecialchars($product['image_directory']); ?>" 
                             alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                             class="object-cover w-full h-96 rounded-lg">
                    </div>
                </div>
            <?php else: ?>
                <div class="aspect-w-1 aspect-h-1 bg-gray-100 rounded-lg flex items-center justify-center">
                    <span class="text-gray-400">No image available</span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Product Details -->
        <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                <?php echo htmlspecialchars($product['product_name']); ?>
            </h1>
            
            <div class="flex items-center mb-4">
                <?php if ($product['average_rating']): ?>
                    <div class="flex items-center">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <svg class="w-5 h-5 <?php echo $i <= round($product['average_rating']) ? 'text-yellow-400' : 'text-gray-300'; ?>" 
                                fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        <?php endfor; ?>
                        <span class="ml-2 text-sm text-gray-500">
                            <?php echo number_format($product['average_rating'], 1); ?> 
                            (<?php echo $product['review_count']; ?> reviews)
                        </span>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="text-2xl font-bold text-gray-900 mb-4">
                €<?php echo number_format($product['price'], 2); ?>
            </div>
            
            <div class="prose prose-sm text-gray-500 mb-6">
                <?php echo nl2br(htmlspecialchars($product['description'] ?? '')); ?>
            </div>
            
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <p class="text-sm font-medium text-gray-500">Brand</p>
                    <p class="mt-1"><?php echo htmlspecialchars($product['brand_name']); ?></p>
                </div>
                
                <div>
                    <p class="text-sm font-medium text-gray-500">Category</p>
                    <p class="mt-1"><?php echo htmlspecialchars($product['category_name']); ?></p>
                </div>
            </div>
            
            <div class="flex items-end space-x-4">
                <div class="flex-1">
                    <label for="quantity" class="block text-sm font-medium text-gray-700">Quantity</label>
                    <input type="number" id="quantity" name="quantity" value="1" min="1" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                </div>
                
                <button onclick="addToCart(<?php echo $product['product_id']; ?>)" 
                    class="bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700">
                    Add to Cart
                </button>
            </div>

            <!-- Add to Wishlist Form -->
            <form method="POST">
                <input type="hidden" name="action" value="add_to_wishlist">
                <button type="submit" 
                        class="w-full border-2 border-green-600 text-green-600 py-2 px-4 rounded-md hover:bg-green-50"
                        <?php echo $isInWishlist ? 'disabled' : ''; ?>>
                    <?php echo $isInWishlist ? 'Already in Wishlist' : 'Add to Wishlist'; ?>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Reviews Section -->
<div class="mt-8">
    <h2 class="text-2xl font-bold text-gray-900 mb-6">Customer Reviews</h2>
    
    <?php if (isset($_SESSION['customer_id'])): ?>
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Write a Review</h3>
            
            <form method="POST" action="" class="space-y-4">
                <div>
                    <label for="rating" class="block text-sm font-medium text-gray-700">Rating</label>
                    <select id="rating" name="rating" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                        <option value="">Select rating</option>
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?> stars</option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div>
                    <label for="comment" class="block text-sm font-medium text-gray-700">Review</label>
                    <textarea id="comment" name="comment" rows="4" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"></textarea>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" name="submit_review" 
                        class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                        Submit Review
                    </button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <p class="text-gray-500">
                Please <a href="account_stuff/login.php" class="text-green-600 hover:text-green-700">log in</a> to write a review.
            </p>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($reviews)): ?>
        <div class="space-y-6">
            <?php foreach ($reviews as $review): ?>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="font-medium text-gray-900"><?php echo htmlspecialchars($review['customer_name']); ?></p>
                            <p class="text-sm text-gray-500">
                                <?php echo $review['review_date'] ? date('F j, Y', strtotime($review['review_date'])) : 'Date not available'; ?>
                            </p>
                        </div>
                        
                        <div class="flex items-center">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <svg class="w-5 h-5 <?php echo $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-300'; ?>" 
                                    fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <div class="text-gray-700">
                        <?php echo nl2br(htmlspecialchars($review['text'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow-md p-6">
            <p class="text-gray-500">No reviews yet. Be the first to review this product!</p>
        </div>
    <?php endif; ?>
</div>

<script>
    function addToCart(id) {
        const quantityInput = document.getElementById('quantity');
        const quantity = quantityInput ? parseInt(quantityInput.value) : 1;
        if (quantity < 1) return;
        
        fetch('<?php echo SITE_URL; ?>/cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'update',
                product_id: id,
                quantity: quantity
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update cart count
                fetch('<?php echo SITE_URL; ?>/cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'get_count'
                    })
                })
                .then(response => response.json())
                .then(countData => {
                    if (countData.success) {
                        document.getElementById('cart-count').textContent = countData.count;
                        document.getElementById('mobile-cart-count').textContent = countData.count;
                    }
                });
                alert('Product added to cart!');
            } else {
                alert(data.message || 'Failed to add product to cart');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while adding the product to cart');
        });
    }
</script>

<?php include 'includes/footer.php'; ?> 