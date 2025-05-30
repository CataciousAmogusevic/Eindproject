<?php
ob_start();
require_once __DIR__ . '/includes/config.php';
include 'includes/functions.php';
include 'includes/header.php';

// Get database connection
$mysqli = get_db_connection();

// Fetch categories and brands for filters
$categories_query = $mysqli->query("SELECT * FROM tblcategory ORDER BY category_name");
$categories = $categories_query->fetch_all(MYSQLI_ASSOC);

$brands_query = $mysqli->query("SELECT * FROM tblbrand ORDER BY brand_name");
$brands = $brands_query->fetch_all(MYSQLI_ASSOC);

// Initialize filter variables
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$brand_filter = isset($_GET['brand']) ? (int)$_GET['brand'] : 0;

// Build the product query with filters
$query = "SELECT p.*, b.brand_name, c.category_name, v.image_directory 
          FROM tblproducts p
          LEFT JOIN tblbrand b ON p.brand_id = b.brand_id
          LEFT JOIN tblcategory c ON p.category_id = c.category_id
          LEFT JOIN tblvariant v ON p.product_id = v.product_id AND v.variant_id = 1";

$conditions = [];
$params = [];
$types = '';

if ($category_filter > 0) {
    $conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
    $types .= 'i';
}

if ($brand_filter > 0) {
    $conditions[] = "p.brand_id = ?";
    $params[] = $brand_filter;
    $types .= 'i';
}

if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$stmt = $mysqli->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Initialize customer type for logged in users or set as guest
if(isset($_SESSION['customer_id'])){
    $sql = "SELECT c.type_id, t.type_id, t.type FROM tblcustomer c, tbltypes t WHERE customer_id = ? AND c.type_id = t.type_id";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $_SESSION['customer_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $_SESSION['type'] = $row['type'];
} else {
    $_SESSION['type'] = "guest";
}

maintenanceMode($mysqli);
announcement($mysqli);
?>

<!-- Hero Section -->
<div class="bg-green-800 text-white py-16 mb-8">
    <div class="container mx-auto px-4 text-center">
        <h1 class="text-4xl font-bold mb-4">Welcome to Pet & Garden</h1>
        <p class="text-lg mb-6">Your one-stop shop for premium pet supplies and gardening tools.</p>
        <a href="#products" class="inline-block bg-white text-green-800 px-6 py-3 rounded-lg font-medium hover:bg-gray-100 transition">Shop Now</a>
    </div>
</div>

<!-- Filter Form -->
<div class="bg-white rounded-lg shadow-md p-6 mb-8">
    <h2 class="text-xl font-semibold mb-4">Filter Products</h2>
    <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
            <select id="category" name="category" class="w-full border border-gray-300 rounded p-2 focus:ring-green-500 focus:border-green-500">
                <option value="0">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['category_id']; ?>" <?php echo $category_filter == $cat['category_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['category_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="brand" class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
            <select id="brand" name="brand" class="w-full border border-gray-300 rounded p-2 focus:ring-green-500 focus:border-green-500">
                <option value="0">All Brands</option>
                <?php foreach ($brands as $brd): ?>
                    <option value="<?php echo $brd['brand_id']; ?>" <?php echo $brand_filter == $brd['brand_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($brd['brand_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex items-end">
            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 w-full md:w-auto">Apply Filters</button>
        </div>
    </form>
</div>

<!-- Products Section -->
<div id="products" class="mb-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Featured Products</h2>
    <?php if (empty($products)): ?>
        <p class="text-gray-500">No products found matching your criteria.</p>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php foreach ($products as $product): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden transition transform hover:-translate-y-1 hover:shadow-xl">
                    <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                        <?php if (!empty($product['image_directory'])): ?>
                            <img src="<?php echo htmlspecialchars($product['image_directory']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['product_name']); ?>" 
                                 class="w-full h-full object-cover">
                        <?php else: ?>
                            <span class="text-gray-500">No image</span>
                        <?php endif; ?>
                    </div>
                    <div class="p-4">
                        <h3 class="font-bold text-lg mb-2 text-gray-800"><?php echo htmlspecialchars($product['product_name']); ?></h3>
                        <p class="text-gray-600 mb-2">Brand: <?php echo htmlspecialchars($product['brand_name']); ?></p>
                        <p class="text-gray-600 mb-2">Category: <?php echo htmlspecialchars($product['category_name']); ?></p>
                        <p class="text-green-700 font-semibold">€<?php echo number_format($product['price'], 2); ?></p>
                        <div class="mt-3 flex justify-between items-center">
                            <a href="product.php?id=<?php echo $product['product_id']; ?>" class="text-green-600 hover:text-green-800 font-medium">View Details</a>
                            <button onclick="addToCart(<?php echo $product['product_id']; ?>)" class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700">Add to Cart</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    function addToCart(id) {
        let cart = JSON.parse(localStorage.getItem('cartItems')) || [];
        const item = cart.find(i => i.id === id);
        if (item) item.quantity++;
        else cart.push({ id, quantity: 1 });
        localStorage.setItem('cartItems', JSON.stringify(cart));
        updateCartCount();
        alert('Product added to cart!');
    }
    
    function updateCartCount() {
        const cart = JSON.parse(localStorage.getItem('cartItems')) || [];
        const count = cart.reduce((acc, item) => acc + item.quantity, 0);
        document.getElementById('cart-count').textContent = count;
        const mobileCartCount = document.getElementById('mobile-cart-count');
        if (mobileCartCount) mobileCartCount.textContent = count;
    }
    
    document.addEventListener('DOMContentLoaded', updateCartCount);
</script>

<?php include 'includes/footer.php'; ?>
