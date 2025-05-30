<?php
require_once __DIR__ . '/../includes/config.php';

// Get database connection
$mysqli = get_db_connection();

// Redirect if already logged in
if (isset($_SESSION['customer_id'])) {
    header('Location: ' . SITE_URL . '/index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $stmt = $mysqli->prepare("
            SELECT c.*, t.type 
            FROM tblcustomer c 
            JOIN tbltypes t ON c.type_id = t.type_id 
            WHERE c.email = ?
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['customer_id'];
                $_SESSION['user_name'] = $user['customer_name'];
                $_SESSION['user_type'] = $user['type'];
                $_SESSION['customer_id'] = $user['customer_id'];
                $_SESSION['flash_message'] = 'Login successful! Welcome, ' . $user['customer_name'] . '!';
                $_SESSION['flash_type'] = 'success';
                
                // Redirect based on user type
                if ($user['type'] === 'admin') {
                    header('Location: ' . SITE_URL . '/admin/admin-panel.php');
                } else {
                    header('Location: ' . SITE_URL . '/index.php');
                }
            }
        }
        $error = 'Invalid email or password';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6">
    <h2 class="text-2xl font-bold mb-6 text-center">Login</h2>
    
    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="" class="space-y-4">
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input type="email" id="email" name="email" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
            <input type="password" id="password" name="password" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
        </div>

        <div>
            <button type="submit" 
                class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                Login
            </button>
        </div>
    </form>

    <div class="mt-4 text-center">
        <p class="text-sm text-gray-600">
            Don't have an account? 
            <a href="register.php" class="text-green-600 hover:text-green-500">Register here</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>