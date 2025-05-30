<?php
ob_start();
require_once __DIR__ . '/../includes/config.php';

// Get database connection
$mysqli = get_db_connection();
require_once __DIR__ . '/../includes/config.php';

// Get database connection
$mysqli = get_db_connection();
include '../includes/header.php';

// Check if user is admin
if (!isAdmin()) {
    $_SESSION['flash_message'] = 'Access denied. Admin privileges required.';
    $_SESSION['flash_type'] = 'error';
    header('Location: ' . SITE_URL . '/index.php');
    exit();
}

// Handle review actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['review_id'])) {
        $review_id = (int)$_POST['review_id'];
        $review_type = $_POST['review_type'];
        $table = $review_type === 'website' ? 'tblwebsite_reviews' : 'tblreviews';
        
        if ($_POST['action'] === 'approve') {
            $stmt = $mysqli->prepare("UPDATE $table SET approved = 1 WHERE review_id = ?");
            $stmt->bind_param("i", $review_id);
            if ($stmt->execute()) {
                $_SESSION['flash_message'] = 'Review approved successfully';
                $_SESSION['flash_type'] = 'success';
            } else {
                $_SESSION['flash_message'] = 'Failed to approve review';
                $_SESSION['flash_type'] = 'error';
            }
        } elseif ($_POST['action'] === 'delete') {
            $stmt = $mysqli->prepare("DELETE FROM $table WHERE review_id = ?");
            $stmt->bind_param("i", $review_id);
            if ($stmt->execute()) {
                $_SESSION['flash_message'] = 'Review deleted successfully';
                $_SESSION['flash_type'] = 'success';
            } else {
                $_SESSION['flash_message'] = 'Failed to delete review';
                $_SESSION['flash_type'] = 'error';
            }
        }
        header('Location: ' . SITE_URL . '/admin/reviews.php');
        exit();
    }
}

// Get active tab
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'product';

// Fetch product reviews
$product_reviews_query = $mysqli->prepare("
    SELECT r.*, p.product_name, c.customer_name
    FROM tblreviews r
    JOIN tblproducts p ON r.product_id = p.product_id
    JOIN tblcustomer c ON r.customer_id = c.customer_id
    ORDER BY r.review_id DESC
");
$product_reviews_query->execute();
$product_reviews = $product_reviews_query->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch website reviews
$website_reviews_query = $mysqli->prepare("
    SELECT wr.*, c.customer_name, wr.created_at
    FROM tblwebsite_reviews wr
    JOIN tblcustomer c ON wr.customer_id = c.customer_id
    ORDER BY wr.review_id DESC
");
$website_reviews_query->execute();
$website_reviews = $website_reviews_query->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">Manage Reviews</h1>
        <a href="<?php echo SITE_URL; ?>/admin/admin-panel.php" 
           class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
            Back to Admin Panel
        </a>
    </div>

    <!-- Tabs -->
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <a href="?tab=product" 
               class="<?php echo $active_tab === 'product' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Product Reviews
            </a>
            <a href="?tab=website" 
               class="<?php echo $active_tab === 'website' ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                Website Reviews
            </a>
        </nav>
    </div>

    <!-- Reviews Table -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <?php if ($active_tab === 'product'): ?>
            <?php if (empty($product_reviews)): ?>
                <p class="text-gray-500">No product reviews found.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rating</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Review</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($product_reviews as $review): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $review['review_id']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($review['product_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($review['customer_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $review['rating']; ?>/5</td>
                                    <td class="px-6 py-4 text-sm text-gray-500"><?php echo htmlspecialchars(substr($review['text'], 0, 100)) . (strlen($review['text']) > 100 ? '...' : ''); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $review['approved'] ? 'Approved' : 'Pending'; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <?php if (!$review['approved']): ?>
                                            <form method="POST" action="" class="inline-block">
                                                <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                                <input type="hidden" name="review_type" value="product">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="text-green-600 hover:text-green-900 mr-3">Approve</button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="POST" action="" class="inline-block">
                                            <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                            <input type="hidden" name="review_type" value="product">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this review?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <?php if (empty($website_reviews)): ?>
                <p class="text-gray-500">No website reviews found.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rating</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Review</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($website_reviews as $review): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $review['review_id']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($review['customer_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $review['rating']; ?>/5</td>
                                    <td class="px-6 py-4 text-sm text-gray-500"><?php echo htmlspecialchars(substr($review['text'], 0, 100)) . (strlen($review['text']) > 100 ? '...' : ''); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('M j, Y', strtotime($review['created_at'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $review['approved'] ? 'Approved' : 'Pending'; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <?php if (!$review['approved']): ?>
                                            <form method="POST" action="" class="inline-block">
                                                <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                                <input type="hidden" name="review_type" value="website">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="text-green-600 hover:text-green-900 mr-3">Approve</button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="POST" action="" class="inline-block">
                                            <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                            <input type="hidden" name="review_type" value="website">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this review?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 