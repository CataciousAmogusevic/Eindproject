<?php

// Define constants for file upload and password hashing
defined('HASH_COST') || define('HASH_COST', 12);
defined('ALLOWED_FILE_TYPES') || define('ALLOWED_FILE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);
defined('MAX_FILE_SIZE') || define('MAX_FILE_SIZE', 5242880); // 5MB in bytes

require_once __DIR__ . '/config.php';

// Security functions
if (!function_exists('sanitize_input')) {
    function sanitize_input($data) {
        if (is_array($data)) {
            return array_map('sanitize_input', $data);
        }
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
}

if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('validate_email')) {
    function validate_email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}

if (!function_exists('validate_password')) {
    function validate_password($password) {
        // At least 8 characters, 1 uppercase, 1 lowercase, 1 number
        return strlen($password) >= 8 && 
               preg_match('/[A-Z]/', $password) && 
               preg_match('/[a-z]/', $password) && 
               preg_match('/[0-9]/', $password);
    }
}

if (!function_exists('hash_password')) {
    function hash_password($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
    }
}

if (!function_exists('verify_password')) {
    function verify_password($password, $hash) {
        return password_verify($password, $hash);
    }
}

// Authentication functions
if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('is_admin')) {
    function is_admin() {
        return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
    }
}

if (!function_exists('require_login')) {
    function require_login() {
        if (!is_logged_in()) {
            set_flash_message('error', 'Please log in to access this page.');
            header('Location: ' . SITE_URL . '/login.php');
            exit();
        }
    }
}

if (!function_exists('require_admin')) {
    function require_admin() {
        if (!is_admin()) {
            set_flash_message('error', 'You do not have permission to access this page.');
            header('Location: ' . SITE_URL . '/index.php');
            exit();
        }
    }
}

// Cart functions
if (!function_exists('get_cart')) {
    function get_cart() {
        return isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
    }
}

if (!function_exists('add_to_cart')) {
    function add_to_cart($product_id, $quantity = 1) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = $quantity;
        }
    }
}

if (!function_exists('remove_from_cart')) {
    function remove_from_cart($product_id) {
        if (isset($_SESSION['cart'][$product_id])) {
            unset($_SESSION['cart'][$product_id]);
        }
    }
}

if (!function_exists('update_cart_quantity')) {
    function update_cart_quantity($product_id, $quantity) {
        if ($quantity <= 0) {
            remove_from_cart($product_id);
        } else {
            $_SESSION['cart'][$product_id] = $quantity;
        }
    }
}

if (!function_exists('get_cart_total')) {
    function get_cart_total() {
        global $mysqli;
        $total = 0;
        
        foreach (get_cart() as $product_id => $quantity) {
            $stmt = $mysqli->prepare("SELECT price FROM tblproducts WHERE product_id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $total += $row['price'] * $quantity;
            }
        }
        
        return $total;
    }
}

// Product functions
if (!function_exists('get_product')) {
    function get_product($product_id) {
        global $mysqli;
        $stmt = $mysqli->prepare("
            SELECT p.*, b.brand_name, c.category_name 
            FROM tblproducts p 
            JOIN tblbrand b ON p.brand_id = b.brand_id 
            JOIN tblcategory c ON p.category_id = c.category_id 
            WHERE p.product_id = ?
        ");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}

if (!function_exists('get_all_products')) {
    function get_all_products($limit = null, $offset = 0) {
        global $mysqli;
        $sql = "
            SELECT p.*, b.brand_name, c.category_name 
            FROM tblproducts p 
            JOIN tblbrand b ON p.brand_id = b.brand_id 
            JOIN tblcategory c ON p.category_id = c.category_id
        ";
        
        if ($limit !== null) {
            $sql .= " LIMIT ? OFFSET ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("ii", $limit, $offset);
        } else {
            $stmt = $mysqli->prepare($sql);
        }
        
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// Discount code functions
if (!function_exists('validate_discount_code')) {
    function validate_discount_code($code) {
        global $mysqli;
        $stmt = $mysqli->prepare("
            SELECT * FROM tbldiscount_codes 
            WHERE discount_code = ? AND end_date >= CURDATE()
        ");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}

// Flash messages
if (!function_exists('set_flash_message')) {
    function set_flash_message($type, $message) {
        $_SESSION['flash_message'] = [
            'type' => $type,
            'message' => $message
        ];
    }
}

if (!function_exists('get_flash_message')) {
    function get_flash_message() {
        if (isset($_SESSION['flash_message'])) {
            $message = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']);
            return $message;
        }
        return null;
    }
}

// File handling functions
if (!function_exists('validate_file_upload')) {
    function validate_file_upload($file, $allowed_types = ALLOWED_FILE_TYPES, $max_size = MAX_FILE_SIZE) {
        $errors = [];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload failed';
            return $errors;
        }
        
        if ($file['size'] > $max_size) {
            $errors[] = 'File is too large';
        }
        
        $file_type = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = 'Invalid file type';
        }
        
        return $errors;
    }
}

if (!function_exists('generate_unique_filename')) {
    function generate_unique_filename($original_name) {
        $extension = pathinfo($original_name, PATHINFO_EXTENSION);
        return uniqid() . '_' . time() . '.' . $extension;
    }
}

// Logging functions
if (!function_exists('log_error')) {
    function log_error($message, $context = []) {
        $log_entry = date('Y-m-d H:i:s') . ' - ' . $message;
        if (!empty($context)) {
            $log_entry .= ' - Context: ' . json_encode($context);
        }
        error_log($log_entry);
    }
}

if (!function_exists('log_user_action')) {
    function log_user_action($user_id, $action, $details = []) {
        global $mysqli;
        $stmt = $mysqli->prepare("
            INSERT INTO tbluser_logs (user_id, action, details, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $details_json = json_encode($details);
        $stmt->bind_param("iss", $user_id, $action, $details_json);
        $stmt->execute();
    }
}

// Website functions
// Function to check maintenance mode
if (!function_exists('maintenanceMode')) {
    function maintenanceMode($mysqli)
    {
       $sql = "SELECT setting_value FROM tbladmin WHERE setting_name = 'site_maintenance'";
       $result = $mysqli->query($sql);
       $row = $result->fetch_assoc();
       if ($row) {
          $sql4 = "SELECT c.type_id, t.type_id, t.type FROM customers c, types t WHERE customer_id = ? AND c.type_id = t.type_id";
          $stmt4 = $mysqli->prepare($sql4);
          $stmt4->bind_param("i", $_SESSION['customer_id']);
          $stmt4->execute();
          $result4 = $stmt4->get_result();
          $row4 = $result4->fetch_assoc();
          if ($row4 && $row["setting_value"] == 1 && $row4['type'] == "customer") {
             header("Location: maintenance.php");
          }
       }
    }
}

// Function to check customer
if (!function_exists('checkCustomer')) {
    function checkCustomer($mysqli)
    {
       $sql = "SELECT c.type_id, t.type_id, t.type FROM customers c, types t WHERE customer_id = ? AND c.type_id = t.type_id";
       $stmt = $mysqli->prepare($sql);
       $stmt->bind_param("i", $_SESSION['customer_id']);
       $stmt->execute();
       $result = $stmt->get_result();
       $row = $result->fetch_assoc();
       $type = $row['type'];

       if ((!($type == "customer") && !($type == "admin")) || !isset($_SESSION['customer_id'])) {
          header("Location: logout.php");
       }
    }
}

if (!function_exists('checkAdmin')) {
    function checkAdmin($mysqli)
    {
       $sql = "SELECT c.type_id, t.type_id, t.type FROM customers c, types t WHERE customer_id = ? AND c.type_id = t.type_id";
       $stmt = $mysqli->prepare($sql);
       $stmt->bind_param("i", $_SESSION['customer_id']);
       $stmt->execute();
       $result = $stmt->get_result();
       $row = $result->fetch_assoc();
       $type = $row['type'];

       if ((!($type == "admin")) || !isset($_SESSION['customer_id'])) {
          if ($type == "customer") {
             header("Location: index.php");
             exit();
          } else {
             header("Location: logout.php");
             exit();
          }
       }
    }
}

if (!function_exists('getUserType')) {
    function getUserType($mysqli)
    {
       session_start();
       $sql = "SELECT c.type_id, t.type_id, t.type FROM customers c, types t WHERE customer_id = ? AND c.type_id = t.type_id";
       $stmt = $mysqli->prepare($sql);
       $stmt->bind_param("i", $_SESSION['customer_id']);
       $stmt->execute();
       $result = $stmt->get_result();
       $row = $result->fetch_assoc();
       $type = $row['type'];
       return $type;
    }
}

// Function to process payment
if (!function_exists('processPayPalPayment')) {
    function processPayPalPayment($amount)
    {
       session_start();
       $_SESSION['payment_method'] = "PayPal";
       $amount = str_replace(",", "", $amount);
       // Redirect to PayPal payment page
       $paypalUrl = "https://sandbox.paypal.com";
       $businessEmail = "sb-business@example.com";
       $currency = "EUR";

       // Redirect to PayPal with required fields
       header("Location: $paypalUrl?cmd=_xclick&business=$businessEmail&amount=$amount&currency_code=$currency&return=https://petandgarden.com/success_payment&cancel_return=https://petandgarden.com/cancel_payment");
       exit();
    }
}

/* Temporarily commented out due to missing Stripe library
function processStripePayment($amount, $mysqli)
{
   session_start();
   $_SESSION['payment_method'] = "Stripe";
   require_once('stripe-php/init.php');

   $amount = str_replace(',', '', $amount);
   $amount = preg_replace('/\s+/', '', $amount);
   $amount = intval($amount);
   $amount = $amount * 100;

   $success_url = "https://petandgarden.com/success_payment";
   $cancel_url = "https://petandgarden.com/cancel_payment";

   //get from database
   $sql = "SELECT * FROM payment_methods WHERE method_name = 'Stripe'";
   $result = $mysqli->query($sql);
   $row = $result->fetch_assoc();
   $stripe_secret_key = $row['api_key'];

   try {
      \Stripe\Stripe::setApiKey($stripe_secret_key);
      $checkout_session = \Stripe\Checkout\Session::create([
         'mode' => "payment",
         'success_url' => $success_url,
         'cancel_url' => $cancel_url,
         'line_items' => [
            [
               "quantity" => 1,
               "price_data" => [
                  "currency" => "eur",
                  "unit_amount" => $amount, // in cents
                  "product_data" => [
                     "name" => "Pet & Garden Products",
                  ],
               ],
            ],
         ],
      ]);

      http_response_code(303);
      header('Location: ' . $checkout_session->url);
      exit();
   } catch (Exception $e) {
      echo 'Caught exception: ',  $e->getMessage(), "\n";
   }
}
*/

// Function to display announcements
if (!function_exists('announcement')) {
    function announcement($mysqli)
    {
       $sql = "SELECT * FROM tblannouncement ORDER BY announcement_id DESC LIMIT 1";
       $result = $mysqli->query($sql);
       if ($result && $result->num_rows > 0) {
          $row = $result->fetch_assoc();
          echo '<div class="bg-orange-500 text-white text-center p-2 font-bold">'; 
          echo htmlspecialchars($row['announcement']);
          echo '</div>';
       }
    }
}

// Function to get stock status
if (!function_exists('getStockStatus')) {
    function getStockStatus($product_id, $mysqli)
    {
       $sql = "SELECT stock FROM products WHERE product_id = ?";
       $stmt = $mysqli->prepare($sql);
       $stmt->bind_param("i", $product_id);
       $stmt->execute();
       $result = $stmt->get_result();
       $row = $result->fetch_assoc();
       return $row['stock'];
    }
}

// Function to get brand name
if (!function_exists('getBrandName')) {
    function getBrandName($brand_id, $mysqli)
    {
       $sql = "SELECT brand_name FROM brands WHERE brand_id = ?";
       $stmt = $mysqli->prepare($sql);
       $stmt->bind_param("i", $brand_id);
       $stmt->execute();
       $result = $stmt->get_result();
       $row = $result->fetch_assoc();
       return $row['brand_name'];
    }
}

// Function to get category name
if (!function_exists('getCategoryName')) {
    function getCategoryName($category_id, $mysqli)
    {
       $sql = "SELECT category_name FROM categories WHERE category_id = ?";
       $stmt = $mysqli->prepare($sql);
       $stmt->bind_param("i", $category_id);
       $stmt->execute();
       $result = $stmt->get_result();
       $row = $result->fetch_assoc();
       return $row['category_name'];
    }
}

// Function to check stock
if (!function_exists('stockCheck')) {
    function stockCheck($mysqli)
    {
       $sql = "SELECT * FROM products WHERE stock <= 5";
       $result = $mysqli->query($sql);
       if ($result->num_rows > 0) {
          echo '<div class="alert alert-warning">';
          echo '<h4>Low Stock Alert</h4>';
          echo '<ul>';
          while ($row = $result->fetch_assoc()) {
             echo '<li>' . $row['product_name'] . ' - ' . $row['stock'] . ' left in stock</li>';
          }
          echo '</ul>';
          echo '</div>';
       }
    }
}

// Function to get order status
if (!function_exists('getOrderStatus')) {
    function getOrderStatus($order_id, $mysqli) {
       $sql = "SELECT status FROM orders WHERE order_id = ?";
       $stmt = $mysqli->prepare($sql);
       $stmt->bind_param("i", $order_id);
       $stmt->execute();
       $result = $stmt->get_result();
       $row = $result->fetch_assoc();
       return $row['status'];
    }
}

// Function to update order status
if (!function_exists('updateOrderStatus')) {
    function updateOrderStatus($order_id, $new_status, $mysqli) {
       $sql = "UPDATE orders SET status = ? WHERE order_id = ?";
       $stmt = $mysqli->prepare($sql);
       $stmt->bind_param("si", $new_status, $order_id);
       return $stmt->execute();
    }
}

// Function to get all orders
if (!function_exists('getAllOrders')) {
    function getAllOrders($mysqli) {
       $sql = "SELECT o.*, c.first_name, c.last_name FROM orders o JOIN customers c ON o.customer_id = c.customer_id ORDER BY order_date DESC";
       $result = $mysqli->query($sql);
       return $result;
    }
}

// Function to get username
if (!function_exists('getUsername')) {
    function getUsername($customer_id, $mysqli)
    {
       $sql = "SELECT first_name, last_name FROM customers WHERE customer_id = ?";
       $stmt = $mysqli->prepare($sql);
       $stmt->bind_param("i", $customer_id);
       $stmt->execute();
       $result = $stmt->get_result();
       $row = $result->fetch_assoc();
       return $row['first_name'] . ' ' . $row['last_name'];
    }
}

// Function to get all customer orders
if (!function_exists('getCustomerOrders')) {
    function getCustomerOrders($customer_id, $mysqli) {
       $sql = "SELECT * FROM orders WHERE customer_id = ? ORDER BY order_date DESC";
       $stmt = $mysqli->prepare($sql);
       $stmt->bind_param("i", $customer_id);
       $stmt->execute();
       $result = $stmt->get_result();
       return $result;
    }
}

// Function to calculate customer lifetime value
if (!function_exists('calculateCustomerLifetimeValue')) {
    function calculateCustomerLifetimeValue($customer_id, $mysqli) {
       $sql = "SELECT SUM(total_amount) as lifetime_value FROM orders WHERE customer_id = ? AND status = 'completed'";
       $stmt = $mysqli->prepare($sql);
       $stmt->bind_param("i", $customer_id);
       $stmt->execute();
       $result = $stmt->get_result();
       $row = $result->fetch_assoc();
       return $row['lifetime_value'] ?: 0;
    }
}

// Function to get most purchased products
if (!function_exists('getMostPurchasedProducts')) {
    function getMostPurchasedProducts($start_date, $end_date, $mysqli) {
       $sql = "SELECT p.product_id, p.product_name, SUM(oi.quantity) as total_quantity 
               FROM order_items oi 
               JOIN products p ON oi.product_id = p.product_id 
               JOIN orders o ON oi.order_id = o.order_id 
               WHERE o.order_date BETWEEN ? AND ? 
               GROUP BY p.product_id 
               ORDER BY total_quantity DESC 
               LIMIT 10";
       $stmt = $mysqli->prepare($sql);
       $stmt->bind_param("ss", $start_date, $end_date);
       $stmt->execute();
       $result = $stmt->get_result();
       return $result;
    }
}

// Base class for product lists (Cart and Wishlist)
class ProductList {
    protected $mysqli;
    protected $customer_id;
    protected $errors = [];
    protected $table_name;

    public function __construct($mysqli, $table_name) {
        $this->mysqli = $mysqli;
        $this->customer_id = isset($_SESSION['customer_id']) ? $_SESSION['customer_id'] : null;
        $this->table_name = $table_name;
    }

    protected function getProductDetails($product_id) {
        try {
            $stmt = $this->mysqli->prepare("
                SELECT p.*, b.brand_name, c.category_name, v.image_directory
                FROM tblproducts p 
                JOIN tblbrand b ON p.brand_id = b.brand_id 
                JOIN tblcategory c ON p.category_id = c.category_id 
                LEFT JOIN tblvariant v ON p.product_id = v.product_id AND v.variant_id = 1
                WHERE p.product_id = ?
            ");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            error_log("Error in getProductDetails: " . $e->getMessage());
            return null;
        }
    }

    public function getErrors() {
        return $this->errors;
    }

    public function getItemCount() {
        if (!$this->customer_id) {
            return 0;
        }

        try {
            $stmt = $this->mysqli->prepare("
                SELECT COUNT(*) as total 
                FROM " . $this->table_name . " 
                WHERE customer_id = ?
            ");
            $stmt->bind_param("i", $this->customer_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            return $result['total'] ?? 0;
        } catch (Exception $e) {
            error_log("Error in getItemCount: " . $e->getMessage());
            return 0;
        }
    }

    public function clear() {
        if (!$this->customer_id) {
            return false;
        }

        try {
            $stmt = $this->mysqli->prepare("
                DELETE FROM " . $this->table_name . " 
                WHERE customer_id = ?
            ");
            $stmt->bind_param("i", $this->customer_id);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error in clear: " . $e->getMessage());
            $this->errors[] = 'Failed to clear items';
            return false;
        }
    }
}

// Cart class extending ProductList
class Cart extends ProductList {
    public function __construct($mysqli) {
        parent::__construct($mysqli, 'tblcart');
    }

    public function validateCart() {
        if (!$this->customer_id) {
            return false;
        }

        try {
            $stmt = $this->mysqli->prepare("
                SELECT c.*, p.stock 
                FROM " . $this->table_name . " c
                JOIN tblproducts p ON c.product_id = p.product_id
                WHERE c.customer_id = ? AND (c.quantity > p.stock OR p.stock = 0)
            ");
            $stmt->bind_param("i", $this->customer_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                if ($row['stock'] == 0) {
                    $this->removeItem($row['product_id']);
                    $this->errors[] = "Product removed from cart due to being out of stock";
                } else {
                    $this->updateQuantity($row['product_id'], $row['stock']);
                    $this->errors[] = "Product quantity adjusted to match available stock";
                }
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error in validateCart: " . $e->getMessage());
            $this->errors[] = 'Failed to validate cart';
            return false;
        }
    }

    public function getItems() {
        if (!$this->customer_id) {
            $this->errors[] = 'User not logged in';
            return ['items' => [], 'total' => 0];
        }

        try {
            $stmt = $this->mysqli->prepare("
                SELECT c.*, p.product_name, p.price, b.brand_name, c.category_name, v.image_directory
                FROM " . $this->table_name . " c
                JOIN tblproducts p ON c.product_id = p.product_id
                JOIN tblbrand b ON p.brand_id = b.brand_id
                JOIN tblcategory cat ON p.category_id = cat.category_id
                LEFT JOIN tblvariant v ON p.product_id = v.product_id AND v.variant_id = 1
                WHERE c.customer_id = ?
            ");
            
            $stmt->bind_param("i", $this->customer_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $items = [];
            $total = 0;

            while ($row = $result->fetch_assoc()) {
                $row['subtotal'] = $row['price'] * $row['quantity'];
                $total += $row['subtotal'];
                $items[] = $row;
            }

            return [
                'items' => $items,
                'total' => $total
            ];
        } catch (Exception $e) {
            error_log("Error in getItems: " . $e->getMessage());
            $this->errors[] = 'Failed to retrieve cart items';
            return ['items' => [], 'total' => 0];
        }
    }

    public function addItem($product_id, $quantity = 1) {
        if (!$this->customer_id) {
            $this->errors[] = 'Please log in to add items to cart';
            return false;
        }

        try {
            $product = $this->getProductDetails($product_id);
            if (!$product) {
                $this->errors[] = 'Product not found';
                return false;
            }

            $this->mysqli->begin_transaction();

            $stmt = $this->mysqli->prepare("
                SELECT id, quantity FROM " . $this->table_name . "
                WHERE customer_id = ? AND product_id = ?
            ");
            $stmt->bind_param("ii", $this->customer_id, $product_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $new_quantity = $row['quantity'] + $quantity;
                
                $stmt = $this->mysqli->prepare("
                    UPDATE " . $this->table_name . "
                    SET quantity = ? 
                    WHERE id = ?
                ");
                $stmt->bind_param("ii", $new_quantity, $row['id']);
            } else {
                $stmt = $this->mysqli->prepare("
                    INSERT INTO " . $this->table_name . " (customer_id, product_id, quantity)
                    VALUES (?, ?, ?)
                ");
                $stmt->bind_param("iii", $this->customer_id, $product_id, $quantity);
            }

            if ($stmt->execute()) {
                $this->mysqli->commit();
                return true;
            } else {
                throw new Exception("Failed to execute statement: " . $stmt->error);
            }
        } catch (Exception $e) {
            $this->mysqli->rollback();
            error_log("Error in addItem: " . $e->getMessage());
            $this->errors[] = 'Failed to add item to cart';
            return false;
        }
    }

    public function updateQuantity($product_id, $quantity) {
        if (!$this->customer_id) {
            $this->errors[] = 'Please log in to update cart';
            return false;
        }

        try {
            if ($quantity < 1) {
                return $this->removeItem($product_id);
            }

            $stmt = $this->mysqli->prepare("
                UPDATE " . $this->table_name . "
                SET quantity = ? 
                WHERE customer_id = ? AND product_id = ?
            ");
            $stmt->bind_param("iii", $quantity, $this->customer_id, $product_id);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error in updateQuantity: " . $e->getMessage());
            $this->errors[] = 'Failed to update quantity';
            return false;
        }
    }

    public function removeItem($product_id) {
        if (!$this->customer_id) {
            $this->errors[] = 'Please log in to remove items';
            return false;
        }

        try {
            $stmt = $this->mysqli->prepare("
                DELETE FROM " . $this->table_name . "
                WHERE customer_id = ? AND product_id = ?
            ");
            $stmt->bind_param("ii", $this->customer_id, $product_id);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error in removeItem: " . $e->getMessage());
            $this->errors[] = 'Failed to remove item';
            return false;
        }
    }
}

// Wishlist class extending ProductList
class Wishlist extends ProductList {
    public function __construct($mysqli) {
        parent::__construct($mysqli, 'tblwishlist');
    }

    public function getItems() {
        if (!$this->customer_id) {
            $this->errors[] = 'User not logged in';
            return [];
        }

        try {
            $stmt = $this->mysqli->prepare("
                SELECT w.*, p.product_name, p.price, b.brand_name, c.category_name, v.image_directory
                FROM " . $this->table_name . " w
                JOIN tblproducts p ON w.product_id = p.product_id
                JOIN tblbrand b ON p.brand_id = b.brand_id
                JOIN tblcategory c ON p.category_id = c.category_id
                LEFT JOIN tblvariant v ON p.product_id = v.product_id AND v.variant_id = 1
                WHERE w.customer_id = ?
                ORDER BY w.created_at DESC
            ");
            
            $stmt->bind_param("i", $this->customer_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $items = [];
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
            
            return $items;
        } catch (Exception $e) {
            error_log("Error in getItems: " . $e->getMessage());
            $this->errors[] = 'Failed to retrieve wishlist items';
            return [];
        }
    }

    public function addItem($product_id) {
        if (!$this->customer_id) {
            $this->errors[] = 'Please log in to add items to wishlist';
            return false;
        }

        try {
            $product = $this->getProductDetails($product_id);
            if (!$product) {
                $this->errors[] = 'Product not found';
                return false;
            }

            if ($this->isInWishlist($product_id)) {
                $this->errors[] = 'Item already in wishlist';
                return false;
            }

            $stmt = $this->mysqli->prepare("
                INSERT INTO " . $this->table_name . " (customer_id, product_id)
                VALUES (?, ?)
            ");
            $stmt->bind_param("ii", $this->customer_id, $product_id);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error in addItem: " . $e->getMessage());
            $this->errors[] = 'Failed to add item to wishlist';
            return false;
        }
    }

    public function isInWishlist($product_id) {
        if (!$this->customer_id) {
            return false;
        }

        try {
            $stmt = $this->mysqli->prepare("
                SELECT id FROM " . $this->table_name . "
                WHERE customer_id = ? AND product_id = ?
            ");
            $stmt->bind_param("ii", $this->customer_id, $product_id);
            $stmt->execute();
            return $stmt->get_result()->num_rows > 0;
        } catch (Exception $e) {
            error_log("Error in isInWishlist: " . $e->getMessage());
            return false;
        }
    }

    public function removeItem($product_id) {
        if (!$this->customer_id) {
            $this->errors[] = 'Please log in to remove items';
            return false;
        }

        try {
            $stmt = $this->mysqli->prepare("
                DELETE FROM " . $this->table_name . "
                WHERE customer_id = ? AND product_id = ?
            ");
            $stmt->bind_param("ii", $this->customer_id, $product_id);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error in removeItem: " . $e->getMessage());
            $this->errors[] = 'Failed to remove item from wishlist';
            return false;
        }
    }
}
?>