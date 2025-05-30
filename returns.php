<?php
ob_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Get database connection
$mysqli = get_db_connection();

include 'includes/header.php';
?>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h2 class="text-2xl font-semibold text-gray-800 mb-4">Returns Policy</h2>
    <div class="text-gray-700">
        <p class="mb-4">At Pet & Garden Shop, we want you to be completely satisfied with your purchase. If for any reason you are not satisfied, we are happy to offer returns under the following conditions:</p>
        <ul class="list-disc pl-5 space-y-2 mb-4">
            <li>Items must be returned within 30 days of purchase.</li>
            <li>Items must be unused, in their original packaging, and in the same condition as when you received them.</li>
            <li>A receipt or proof of purchase is required for all returns.</li>
            <li>Refunds will be issued to the original payment method within 7-10 business days after we receive and process the returned item.</li>
        </ul>
        <p class="mb-4">To initiate a return, please contact our customer service team at support@petandgarden.com or through our <a href="<?php echo SITE_URL; ?>/contact.php" class="text-green-600 hover:underline">contact page</a>.</p>
        <p>Please note that shipping costs for returns are the responsibility of the customer unless the item is defective or incorrect.</p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>