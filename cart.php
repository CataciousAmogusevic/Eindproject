<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Get database connection
$mysqli = get_db_connection();

// Initialize cart
$cart = new Cart($mysqli);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    $response = ['success' => false, 'message' => 'Invalid request'];
    
    if (!isset($_SESSION['customer_id'])) {
        echo json_encode(['success' => false, 'message' => 'Please log in to add items to cart']);
        exit();
    }
    
    if (isset($input['action'])) {
        error_log('Cart action received: ' . $input['action']);
        
        switch ($input['action']) {
            case 'update':
                if (isset($input['product_id']) && isset($input['quantity'])) {
                    error_log('Attempting to add/update product_id: ' . $input['product_id'] . ' with quantity: ' . $input['quantity']);
                    
                    $success = $cart->addItem((int)$input['product_id'], (int)$input['quantity']);
                    $errors = $cart->getErrors();
                    
                    error_log('Add/update result - Success: ' . ($success ? 'true' : 'false') . ', Errors: ' . implode(', ', $errors));
                    
                    $response = [
                        'success' => $success,
                        'message' => $success ? 'Cart updated successfully' : implode(', ', $errors),
                        'cart_count' => $cart->getItemCount()
                    ];
                }
                break;
                
            case 'remove':
                if (isset($input['product_id'])) {
                    error_log('Attempting to remove product_id: ' . $input['product_id']);
                    
                    $success = $cart->removeItem((int)$input['product_id']);
                    $errors = $cart->getErrors();
                    
                    error_log('Remove result - Success: ' . ($success ? 'true' : 'false') . ', Errors: ' . implode(', ', $errors));
                    
                    $response = [
                        'success' => $success,
                        'message' => $success ? 'Item removed from cart' : implode(', ', $errors),
                        'cart_count' => $cart->getItemCount()
                    ];
                }
                break;
                
            case 'get_count':
                $count = $cart->getItemCount();
                error_log('Getting cart count: ' . $count);
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

// Validate cart contents
$cart->validateCart();

// Get cart items and total
$cart_data = $cart->getItems();
$cart_items = $cart_data['items'];
$total = $cart_data['total'];

// Include header after processing to avoid sending headers after output
include 'includes/header.php';
?>

<h2 class="text-2xl font-bold mb-6">Shopping Cart</h2>

<?php if (empty($cart_items)): ?>
    <div class="bg-white rounded-lg shadow-md p-6 text-center">
        <p class="text-gray-600">Your cart is empty</p>
        <a href="<?php echo SITE_URL; ?>/index.php" class="inline-block mt-4 bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700">
            Continue Shopping
        </a>
    </div>
<?php else: ?>
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($cart_items as $item): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <?php if (!empty($item['image_directory'])): ?>
                                    <img src="<?php echo htmlspecialchars($item['image_directory']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                         class="h-16 w-16 object-cover rounded">
                                <?php endif; ?>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($item['product_name']); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($item['brand_name']); ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">€<?php echo number_format($item['price'], 2); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center space-x-2">
                                <button onclick="updateQuantity(<?php echo $item['product_id']; ?>, <?php echo $item['quantity'] - 1; ?>)"
                                    class="text-gray-500 hover:text-gray-700">-</button>
                                <span class="text-sm text-gray-900"><?php echo $item['quantity']; ?></span>
                                <button onclick="updateQuantity(<?php echo $item['product_id']; ?>, <?php echo $item['quantity'] + 1; ?>)"
                                    class="text-gray-500 hover:text-gray-700">+</button>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">€<?php echo number_format($item['subtotal'], 2); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button onclick="removeFromCart(<?php echo $item['product_id']; ?>)"
                                class="text-red-600 hover:text-red-900">Remove</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
            <div class="flex justify-between items-center">
                <div class="text-lg font-medium text-gray-900">
                    Total: €<?php echo number_format($total, 2); ?>
                </div>
                <div class="space-x-4">
                    <a href="<?php echo SITE_URL; ?>/index.php" class="inline-block bg-gray-600 text-white py-2 px-4 rounded-md hover:bg-gray-700">
                        Continue Shopping
                    </a>
                    <a href="checkout.php" class="inline-block bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700">
                        Proceed to Checkout
                    </a>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
    function updateQuantity(productId, quantity) {
        if (quantity < 1) return;
        
        fetch('<?php echo SITE_URL; ?>/cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'update',
                product_id: productId,
                quantity: quantity
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Failed to update cart');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating the cart');
        });
    }

    function removeFromCart(productId) {
        if (!confirm('Are you sure you want to remove this item from your cart?')) {
            return;
        }

        fetch('<?php echo SITE_URL; ?>/cart.php', {
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
                alert(data.message || 'Failed to remove item');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while removing the item');
        });
    }

    // Update cart count on page load
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
    .then(data => {
        if (data.success) {
            document.getElementById('cart-count').textContent = data.count;
            document.getElementById('mobile-cart-count').textContent = data.count;
        }
    });
</script>

<?php include 'includes/footer.php'; ?>