<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Get database connection
$mysqli = get_db_connection();
include 'includes/header.php';

// Handle customer deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete']) && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $customer_id = (int)($_POST['customer_id'] ?? 0);
    
    $stmt = $mysqli->prepare("DELETE FROM tblcustomer WHERE customer_id = ?");
    $stmt->bind_param("i", $customer_id);
    
    if ($stmt->execute()) {
        $_SESSION['flash_message'] = 'Customer deleted successfully';
        $_SESSION['flash_type'] = 'success';
    } else {
        $_SESSION['flash_message'] = 'Failed to delete customer';
        $_SESSION['flash_type'] = 'error';
    }
    
    header('Location: ' . SITE_URL . '/customers.php');
    exit();
}

// Handle customer addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add']) && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $customer_name = trim($_POST['customer_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);
    $type_id = (int)($_POST['type_id'] ?? 2); // Default to customer type (2)
    $loyalty_points = 0;
    $mail_subscription = isset($_POST['mail_subscription']) ? 1 : 0;
    
    $stmt = $mysqli->prepare("INSERT INTO tblcustomer (customer_name, email, phone_number, password, loyalty_points, type_id, mail_subscription) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssiii", $customer_name, $email, $phone_number, $password, $loyalty_points, $type_id, $mail_subscription);
    
    if ($stmt->execute()) {
        $_SESSION['flash_message'] = 'Customer added successfully';
        $_SESSION['flash_type'] = 'success';
    } else {
        $_SESSION['flash_message'] = 'Failed to add customer';
        $_SESSION['flash_type'] = 'error';
    }
    
    header('Location: ' . SITE_URL . '/customers.php');
    exit();
}

// Handle customer editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit']) && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $customer_id = (int)($_POST['customer_id'] ?? 0);
    $customer_name = trim($_POST['customer_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $type_id = (int)($_POST['type_id'] ?? 2); // Default to customer type (2)
    $mail_subscription = isset($_POST['mail_subscription']) ? 1 : 0;
    
    $stmt = $mysqli->prepare("UPDATE tblcustomer SET customer_name = ?, email = ?, phone_number = ?, type_id = ?, mail_subscription = ? WHERE customer_id = ?");
    $stmt->bind_param("sssiii", $customer_name, $email, $phone_number, $type_id, $mail_subscription, $customer_id);
    
    if ($stmt->execute()) {
        $_SESSION['flash_message'] = 'Customer updated successfully';
        $_SESSION['flash_type'] = 'success';
    } else {
        $_SESSION['flash_message'] = 'Failed to update customer';
        $_SESSION['flash_type'] = 'error';
    }
    
    header('Location: ' . SITE_URL . '/customers.php');
    exit();
}

// Get all customers with order statistics
$customers = $mysqli->query("
    SELECT c.*, 
           COUNT(DISTINCT o.order_id) as order_count,
           SUM(o.total_amount) as total_spent
    FROM tblcustomer c
    LEFT JOIN tblorders o ON c.customer_id = o.customer_id
    GROUP BY c.customer_id
    ORDER BY c.customer_name
")->fetch_all(MYSQLI_ASSOC);

// Get customer details for edit form
$edit_customer = null;
if (isset($_GET['edit'])) {
    $customer_id = (int)$_GET['edit'];
    $stmt = $mysqli->prepare("SELECT * FROM tblcustomer WHERE customer_id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $edit_customer = $stmt->get_result()->fetch_assoc();
}
?>

<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-semibold text-gray-800">Manage Customers</h2>
    <a href="?add=new" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Add Customer</a>
</div>

<?php if (isset($_SESSION['flash_message'])): ?>
    <div class="mb-4 p-4 rounded <?php echo $_SESSION['flash_type'] === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'; ?>">
        <?php 
        echo $_SESSION['flash_message'];
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['add']) || isset($_GET['edit'])): ?>
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h3 class="text-xl font-semibold mb-4"><?php echo isset($_GET['add']) ? 'Add New Customer' : 'Edit Customer'; ?></h3>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <?php if (isset($_GET['edit'])): ?>
                <input type="hidden" name="customer_id" value="<?php echo $edit_customer['customer_id']; ?>">
            <?php endif; ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="customer_name" class="block text-gray-700 font-medium mb-1">Name</label>
                    <input type="text" id="customer_name" name="customer_name" value="<?php echo isset($edit_customer) ? htmlspecialchars($edit_customer['customer_name']) : ''; ?>" class="w-full p-2 border rounded" required>
                </div>
                <div>
                    <label for="email" class="block text-gray-700 font-medium mb-1">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo isset($edit_customer) ? htmlspecialchars($edit_customer['email']) : ''; ?>" class="w-full p-2 border rounded" required>
                </div>
                <div>
                    <label for="phone_number" class="block text-gray-700 font-medium mb-1">Phone Number</label>
                    <input type="text" id="phone_number" name="phone_number" value="<?php echo isset($edit_customer) ? htmlspecialchars($edit_customer['phone_number']) : ''; ?>" class="w-full p-2 border rounded">
                </div>
                <?php if (isset($_GET['add'])): ?>
                    <div>
                        <label for="password" class="block text-gray-700 font-medium mb-1">Password</label>
                        <input type="password" id="password" name="password" class="w-full p-2 border rounded" required>
                    </div>
                <?php endif; ?>
                <div>
                    <label for="type_id" class="block text-gray-700 font-medium mb-1">Type</label>
                    <select id="type_id" name="type_id" class="w-full p-2 border rounded">
                        <option value="1" <?php echo isset($edit_customer) && $edit_customer['type_id'] == 1 ? 'selected' : ''; ?>>Admin</option>
                        <option value="2" <?php echo isset($edit_customer) && $edit_customer['type_id'] != 1 ? 'selected' : ''; ?>>Customer</option>
                    </select>
                </div>
            </div>
            <div class="mb-4">
                <label class="flex items-center">
                    <input type="checkbox" name="mail_subscription" <?php echo isset($edit_customer) && $edit_customer['mail_subscription'] ? 'checked' : ''; ?>>
                    <span class="ml-2 text-gray-700">Subscribe to newsletter</span>
                </label>
            </div>
            <div class="flex justify-end">
                <a href="customers.php" class="bg-gray-400 text-white px-4 py-2 rounded mr-2 hover:bg-gray-500">Cancel</a>
                <button type="submit" name="<?php echo isset($_GET['add']) ? 'add' : 'edit'; ?>" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700"><?php echo isset($_GET['add']) ? 'Add Customer' : 'Save Changes'; ?></button>
            </div>
        </form>
    </div>
<?php endif; ?>

<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Password</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Orders</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Spent</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php foreach ($customers as $customer): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        <?php echo htmlspecialchars($customer['customer_name']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php echo htmlspecialchars($customer['email']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php echo htmlspecialchars($customer['phone_number']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php echo htmlspecialchars(substr($customer['password'], 0, 10) . '...'); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php echo $customer['type_id'] == 1 ? 'Admin' : 'Customer'; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php echo $customer['order_count']; ?> orders
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        €<?php echo number_format($customer['total_spent'] ?? 0, 2); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="?edit=<?php echo $customer['customer_id']; ?>" 
                            class="text-blue-600 hover:text-blue-900 mr-4">
                            Edit
                        </a>
                        <form method="POST" action="" class="inline" onsubmit="return confirm('Are you sure you want to delete this customer?');">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <input type="hidden" name="customer_id" value="<?php echo $customer['customer_id']; ?>">
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

<?php include 'includes/footer.php'; ?>