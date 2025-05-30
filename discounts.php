<?php
require_once __DIR__ . '/../includes/config.php';

// Get database connection
$mysqli = get_db_connection();

// Handle discount code deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete']) && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $discount_id = (int)($_POST['discount_id'] ?? 0);
    
    $stmt = $mysqli->prepare("DELETE FROM tbldiscount WHERE discount_id = ?");
    $stmt->bind_param("i", $discount_id);
    
    if ($stmt->execute()) {
        set_flash_message('success', 'Discount code deleted successfully');
    } else {
        set_flash_message('error', 'Failed to delete discount code');
    }
    
    header('Location: ' . SITE_URL . '/discounts.php');
    exit();
}

// Handle discount code addition/editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save']) && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $code = sanitize_input($_POST['code'] ?? '');
    $discount_type = sanitize_input($_POST['discount_type'] ?? '');
    $discount_value = (float)($_POST['discount_value'] ?? 0);
    $min_order = (float)($_POST['min_order'] ?? 0);
    $max_discount = (float)($_POST['max_discount'] ?? 0);
    $start_date = sanitize_input($_POST['start_date'] ?? '');
    $end_date = sanitize_input($_POST['end_date'] ?? '');
    $discount_id = (int)($_POST['discount_id'] ?? 0);
    
    // Validate input
    $errors = [];
    if (empty($code)) $errors[] = 'Discount code is required';
    if (empty($discount_type)) $errors[] = 'Discount type is required';
    if ($discount_value <= 0) $errors[] = 'Discount value must be greater than 0';
    if ($discount_type === 'percentage' && $discount_value > 100) $errors[] = 'Percentage cannot exceed 100%';
    if (empty($start_date)) $errors[] = 'Start date is required';
    if (empty($end_date)) $errors[] = 'End date is required';
    if (strtotime($end_date) <= strtotime($start_date)) $errors[] = 'End date must be after start date';
    
    if (empty($errors)) {
        if ($discount_id > 0) {
            // Update existing discount code
            $stmt = $mysqli->prepare("
                UPDATE tbldiscount 
                SET code = ?, discount_type = ?, discount_value = ?, min_order = ?, max_discount = ?, 
                    start_date = ?, end_date = ?
                WHERE discount_id = ?
            ");
            $stmt->bind_param("ssddsssi", $code, $discount_type, $discount_value, $min_order, $max_discount, 
                $start_date, $end_date, $discount_id);
        } else {
            // Add new discount code
            $stmt = $mysqli->prepare("
                INSERT INTO tbldiscount (code, discount_type, discount_value, min_order, max_discount, start_date, end_date)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("ssddsss", $code, $discount_type, $discount_value, $min_order, $max_discount, 
                $start_date, $end_date);
        }
        
        if ($stmt->execute()) {
            set_flash_message('success', 'Discount code ' . ($discount_id > 0 ? 'updated' : 'added') . ' successfully');
        } else {
            set_flash_message('error', 'Failed to ' . ($discount_id > 0 ? 'update' : 'add') . ' discount code');
        }
        
        header('Location: ' . SITE_URL . '/discounts.php');
        exit();
    }
}

// Get all discount codes
$discounts = $mysqli->query("
    SELECT d.*, 
           COUNT(DISTINCT o.order_id) as usage_count,
           SUM(oi.quantity * oi.price) as total_discount
    FROM tbldiscount d
    LEFT JOIN tblorders o ON d.discount_id = o.discount_id
    LEFT JOIN tblorderitems oi ON o.order_id = oi.order_id
    GROUP BY d.discount_id
    ORDER BY d.start_date DESC
")->fetch_all(MYSQLI_ASSOC);

// Get discount code for editing if ID is provided
$edit_discount = null;
if (isset($_GET['edit'])) {
    $discount_id = (int)$_GET['edit'];
    $stmt = $mysqli->prepare("SELECT * FROM tbldiscount WHERE discount_id = ?");
    $stmt->bind_param("i", $discount_id);
    $stmt->execute();
    $edit_discount = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Discount Codes - Pet & Garden Shop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" href="images/icon/favicon.png">
</head>
<body class="bg-green-50 text-gray-800">
    <header class="bg-green-700 text-white p-4 flex justify-between items-center">
        <h1 class="text-2xl font-bold">🌿 Pet & Garden</h1>
        <nav class="space-x-4">
            <a href="index.php" class="hover:underline">Home</a>
            <a href="products.php" class="hover:underline">Products</a>
            <a href="cart.php" class="hover:underline">Cart 🛒 <span id="cart-count">0</span></a>
            <a href="customers.php" class="hover:underline">Account</a>
        </nav>
    </header>

    <main class="max-w-7xl mx-auto p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold text-gray-800">Manage Discount Codes</h2>
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
                <?php echo $edit_discount ? 'Edit Discount Code' : 'Add New Discount Code'; ?>
            </h3>
            
            <form method="POST" action="" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <?php if ($edit_discount): ?>
                    <input type="hidden" name="discount_id" value="<?php echo $edit_discount['discount_id']; ?>">
                <?php endif; ?>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="code" class="block text-sm font-medium text-gray-700">Discount Code</label>
                        <input type="text" id="code" name="code" required
                            value="<?php echo $edit_discount ? htmlspecialchars($edit_discount['code']) : ''; ?>"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                    </div>

                    <div>
                        <label for="discount_type" class="block text-sm font-medium text-gray-700">Discount Type</label>
                        <select id="discount_type" name="discount_type" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                            <option value="">Select type</option>
                            <option value="percentage" <?php echo $edit_discount && $edit_discount['discount_type'] === 'percentage' ? 'selected' : ''; ?>>
                                Percentage
                            </option>
                            <option value="fixed" <?php echo $edit_discount && $edit_discount['discount_type'] === 'fixed' ? 'selected' : ''; ?>>
                                Fixed Amount
                            </option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label for="discount_value" class="block text-sm font-medium text-gray-700">Discount Value</label>
                        <input type="number" id="discount_value" name="discount_value" step="0.01" min="0" required
                            value="<?php echo $edit_discount ? $edit_discount['discount_value'] : ''; ?>"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                    </div>

                    <div>
                        <label for="min_order" class="block text-sm font-medium text-gray-700">Minimum Order (€)</label>
                        <input type="number" id="min_order" name="min_order" step="0.01" min="0"
                            value="<?php echo $edit_discount ? $edit_discount['min_order'] : '0'; ?>"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                    </div>

                    <div>
                        <label for="max_discount" class="block text-sm font-medium text-gray-700">Maximum Discount (€)</label>
                        <input type="number" id="max_discount" name="max_discount" step="0.01" min="0"
                            value="<?php echo $edit_discount ? $edit_discount['max_discount'] : '0'; ?>"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date</label>
                        <input type="date" id="start_date" name="start_date" required
                            value="<?php echo $edit_discount ? $edit_discount['start_date'] : ''; ?>"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                    </div>

                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700">End Date</label>
                        <input type="date" id="end_date" name="end_date" required
                            value="<?php echo $edit_discount ? $edit_discount['end_date'] : ''; ?>"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                    </div>
                </div>

                <div class="flex justify-end space-x-4">
                    <?php if ($edit_discount): ?>
                        <a href="discounts.php" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">
                            Cancel
                        </a>
                    <?php endif; ?>
                    <button type="submit" name="save" 
                        class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                        <?php echo $edit_discount ? 'Update Discount Code' : 'Add Discount Code'; ?>
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valid Period</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usage</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($discounts as $discount): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($discount['code']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo ucfirst($discount['discount_type']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php 
                                if ($discount['discount_type'] === 'percentage') {
                                    echo $discount['discount_value'] . '%';
                                } else {
                                    echo '€' . number_format($discount['discount_value'], 2);
                                }
                                ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php 
                                echo date('M j, Y', strtotime($discount['start_date'])) . ' - ' . 
                                     date('M j, Y', strtotime($discount['end_date']));
                                ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $discount['usage_count']; ?> times
                                <?php if ($discount['total_discount']): ?>
                                    <br>
                                    <span class="text-xs">
                                        Total: €<?php echo number_format($discount['total_discount'], 2); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="?edit=<?php echo $discount['discount_id']; ?>" 
                                    class="text-green-600 hover:text-green-900 mr-4">Edit</a>
                                
                                <form method="POST" action="" class="inline" onsubmit="return confirm('Are you sure you want to delete this discount code?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                    <input type="hidden" name="discount_id" value="<?php echo $discount['discount_id']; ?>">
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
    </main>

    <footer class="bg-green-700 text-white text-center p-4 mt-10">
        <p>&copy; <?php echo date('Y'); ?> Pet & Garden. All rights reserved.</p>
    </footer>

    <script src="js/custom.js"></script>
    <script>
        function updateCartCount() {
            const cart = JSON.parse(localStorage.getItem('cartItems')) || [];
            const count = cart.reduce((a,b) => a + b.quantity, 0);
            document.getElementById('cart-count').textContent = count;
        }

        // Initialize
        updateCartCount();
    </script>
</body>
</html>