<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Get database connection
$mysqli = get_db_connection();

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    $_SESSION['flash_message'] = 'Please log in to submit a review.';
    $_SESSION['flash_type'] = 'error';
    header('Location: ' . SITE_URL . '/account_stuff/login.php');
    exit();
}

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $rating = (int)$_POST['rating'];
    $text = trim($_POST['review_text']);
    $customer_id = $_SESSION['customer_id'];
    
    // Validate input
    if ($rating < 1 || $rating > 5) {
        $_SESSION['flash_message'] = 'Please select a rating between 1 and 5.';
        $_SESSION['flash_type'] = 'error';
    } elseif (empty($text)) {
        $_SESSION['flash_message'] = 'Please enter your review text.';
        $_SESSION['flash_type'] = 'error';
    } else {
        // Insert review
        $stmt = $mysqli->prepare("INSERT INTO tblwebsite_reviews (customer_id, rating, text) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $customer_id, $rating, $text);
        
        if ($stmt->execute()) {
            $_SESSION['flash_message'] = 'Thank you for your review! It will be visible after approval.';
            $_SESSION['flash_type'] = 'success';
            header('Location: ' . SITE_URL . '/index.php');
            exit();
        } else {
            $_SESSION['flash_message'] = 'Failed to submit review. Please try again.';
            $_SESSION['flash_type'] = 'error';
        }
    }
}

// Fetch approved website reviews
$reviews_query = $mysqli->query("
    SELECT wr.*, c.customer_name, wr.created_at
    FROM tblwebsite_reviews wr
    JOIN tblcustomer c ON wr.customer_id = c.customer_id
    WHERE wr.approved = 1
    ORDER BY wr.created_at DESC
    LIMIT 10
");
$reviews = $reviews_query->fetch_all(MYSQLI_ASSOC);

// Include header after all potential redirects
include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-8">Website Reviews</h1>
        
        <!-- Review Form -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Write a Review</h2>
            <form method="POST" action="" class="space-y-4">
                <div>
                    <label for="rating" class="block text-sm font-medium text-gray-700 mb-1">Rating</label>
                    <div class="flex items-center space-x-2">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <label class="inline-flex items-center">
                                <input type="radio" 
                                       name="rating" 
                                       value="<?php echo $i; ?>" 
                                       class="form-radio text-green-600"
                                       required>
                                <span class="ml-1"><?php echo $i; ?></span>
                            </label>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <div>
                    <label for="review_text" class="block text-sm font-medium text-gray-700 mb-1">Your Review</label>
                    <textarea id="review_text" 
                              name="review_text" 
                              rows="4" 
                              required
                              class="w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-green-500 focus:border-green-500"
                              placeholder="Share your experience with our website..."></textarea>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" 
                            name="submit_review"
                            class="bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700">
                        Submit Review
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Existing Reviews -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Recent Reviews</h2>
            
            <?php if (empty($reviews)): ?>
                <p class="text-gray-500">No reviews yet. Be the first to review!</p>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($reviews as $review): ?>
                        <div class="border-b border-gray-200 pb-6 last:border-b-0 last:pb-0">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center">
                                    <span class="font-medium text-gray-900">
                                        <?php echo htmlspecialchars($review['customer_name']); ?>
                                    </span>
                                    <span class="mx-2 text-gray-500">•</span>
                                    <span class="text-yellow-500">
                                        <?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?>
                                    </span>
                                </div>
                                <span class="text-sm text-gray-500">
                                    <?php echo date('M j, Y', strtotime($review['created_at'])); ?>
                                </span>
                            </div>
                            <p class="text-gray-700"><?php echo htmlspecialchars($review['text']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 