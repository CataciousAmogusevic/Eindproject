<?php
require_once __DIR__ . '/../includes/config.php';

// Get database connection
$mysqli = get_db_connection();
require_once '../includes/header.php';

// Handle customer status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status']) && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $customer_id = (int)($_POST['customer_id'] ?? 0);
    $type_id = (int)($_POST['type_id'] ?? 0);
    
    $stmt = $mysqli->prepare("UPDATE tblcustomer SET type_id = ? WHERE customer_id = ?");
    $stmt->bind_param("ii", $type_id, $customer_id);
    
    if ($stmt->execute()) {
        set_flash_message('success', 'Customer status updated successfully');
    } else {
        set_flash_message('error', 'Failed to update customer status');
    }
    
    header('Location: ' . SITE_URL . '/admin/customers.php');
    exit();
}

// Get all customer types
$customer_types = $mysqli->query("SELECT * FROM tbltypes ORDER BY type_name")->fetch_all(MYSQLI_ASSOC);

// Get all customers with their details
$customers = $mysqli->query("
    SELECT c.*, t.type_name,
           COUNT(DISTINCT o.order_id) as total_orders,
           SUM(oi.quantity * oi.price) as total_spent
    FROM tblcustomer c
    LEFT JOIN tbltypes t ON c.type_id = t.type_id
    LEFT JOIN tblorders o ON c.customer_id = o.customer_id
    LEFT JOIN tblorderitems oi ON o.order_id = oi.order_id
    GROUP BY c.customer_id
    ORDER BY c.customer_name
")->fetch_all(MYSQLI_ASSOC);

// Get customer for editing if ID is provided
$edit_customer = null;
if (isset($_GET['edit'])) {
    $customer_id = (int)$_GET['edit'];
    $stmt = $mysqli->prepare("
        SELECT c.*, a.address, p.postcode, co.country_name
        FROM tblcustomer c
        LEFT JOIN tbladdress a ON c.customer_id = a.customer_id
        LEFT JOIN tblpostcode p ON a.postcode_id = p.postcode_id
        LEFT JOIN tblcountry co ON a.country_id = co.country_id
        WHERE c.customer_id = ?
    ");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $edit_customer = $stmt->get_result()->fetch_assoc();
}
?>

<div class="max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-800">Manage Customers</h2>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="mb-4 p-4 rounded <?php echo $_SESSION['flash_type'] === 'error' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'; ?>">
            <?php 
            echo $_SESSION['flash_message'];
            unset($_SESSION['flash_message'], $_SESSION['flash_type']);
            ?>
        </div>
    <?php endif; ?>

    <?php if ($edit_customer): ?>
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Customer Details</h3>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Name</p>
                    <p class="mt-1"><?php echo htmlspecialchars($edit_customer['customer_name']); ?></p>
                </div>
                
                <div>
                    <p class="text-sm font-medium text-gray-500">Email</p>
                    <p class="mt-1"><?php echo htmlspecialchars($edit_customer['email']); ?></p>
                </div>
                
                <div>
                    <p class="text-sm font-medium text-gray-500">Phone</p>
                    <p class="mt-1"><?php echo htmlspecialchars($edit_customer['phone_number']); ?></p>
                </div>
                
                <div>
                    <p class="text-sm font-medium text-gray-500">Loyalty Points</p>
                    <p class="mt-1"><?php echo $edit_customer['loyalty_points']; ?></p>
                </div>
                
                <div>
                    <p class="text-sm font-medium text-gray-500">Address</p>
                    <p class="mt-1">
                        <?php 
                        echo htmlspecialchars($edit_customer['address'] ?? 'N/A');
                        if (isset($edit_customer['postcode'])) {
                            echo ', ' . htmlspecialchars($edit_customer['postcode']);
                        }
                        if (isset($edit_customer['country_name'])) {
                            echo ', ' . htmlspecialchars($edit_customer['country_name']);
                        }
                        ?>
                    </p>
                </div>
                
                <div>
                    <p class="text-sm font-medium text-gray-500">Email Subscription</p>
                    <p class="mt-1"><?php echo $edit_customer['mail_subscription'] ? 'Subscribed' : 'Not Subscribed'; ?></p>
                </div>
            </div>
            
            <div class="mt-6">
                <form method="POST" action="" class="flex items-end space-x-4">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="customer_id" value="<?php echo $edit_customer['customer_id']; ?>">
                    
                    <div class="flex-1">
                        <label for="type_id" class="block text-sm font-medium text-gray-700">Customer Type</label>
                        <select id="type_id" name="type_id" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                            <?php foreach ($customer_types as $type): ?>
                                <option value="<?php echo $type['type_id']; ?>" 
                                    <?php echo $type['type_id'] === $edit_customer['type_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type['type_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" name="update_status" 
                        class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                        Update Status
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
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
                            <?php echo htmlspecialchars($customer['type_name']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo $customer['total_orders']; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            €<?php echo number_format($customer['total_spent'] ?? 0, 2); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="?edit=<?php echo $customer['customer_id']; ?>" 
                                class="text-green-600 hover:text-green-900">View Details</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 