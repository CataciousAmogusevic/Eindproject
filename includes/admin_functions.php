<?php
require_once __DIR__ . '/config.php';

function is_admin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

function get_total_products($mysqli) {
    $result = $mysqli->query("SELECT COUNT(*) as count FROM tblproducts");
    return $result->fetch_assoc()['count'];
}

function get_low_stock_products($mysqli) {
    $stmt = $mysqli->prepare("
        SELECT p.*, s.stock 
        FROM tblproducts p 
        JOIN tblstock s ON p.product_id = s.product_id 
        WHERE s.stock < 10
    ");
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function get_recent_orders($mysqli) {
    $stmt = $mysqli->prepare("
        SELECT o.*, c.customer_name 
        FROM tblorders o 
        JOIN tblcustomer c ON o.customer_id = c.customer_id 
        ORDER BY o.order_date DESC 
        LIMIT 5
    ");
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function get_total_customers($mysqli) {
    $result = $mysqli->query("SELECT COUNT(*) as count FROM tblcustomer WHERE type_id = 2");
    return $result->fetch_assoc()['count'];
}

function get_status_color($status) {
    $colors = [
        'pending' => 'bg-yellow-100 text-yellow-800',
        'processing' => 'bg-blue-100 text-blue-800',
        'shipped' => 'bg-purple-100 text-purple-800',
        'delivered' => 'bg-green-100 text-green-800',
        'cancelled' => 'bg-red-100 text-red-800'
    ];
    return $colors[$status] ?? 'bg-gray-100 text-gray-800';
}

function handle_add_product($mysqli) {
    $name = sanitize_input($_POST['name'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $brand_id = (int)($_POST['brand_id'] ?? 0);
    $category_id = (int)($_POST['category_id'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    
    $stmt = $mysqli->prepare("
        INSERT INTO tblproducts (product_name, price, brand_id, category_id, stock)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sdiis", $name, $price, $brand_id, $category_id, $stock);
    
    if ($stmt->execute()) {
        set_flash_message('success', 'Product added successfully');
    } else {
        set_flash_message('error', 'Failed to add product');
    }
}

function handle_update_product($mysqli) {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $name = sanitize_input($_POST['name'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $brand_id = (int)($_POST['brand_id'] ?? 0);
    $category_id = (int)($_POST['category_id'] ?? 0);
    
    $stmt = $mysqli->prepare("
        UPDATE tblproducts 
        SET product_name = ?, price = ?, brand_id = ?, category_id = ?
        WHERE product_id = ?
    ");
    $stmt->bind_param("sdiis", $name, $price, $brand_id, $category_id, $product_id);
    
    if ($stmt->execute()) {
        set_flash_message('success', 'Product updated successfully');
    } else {
        set_flash_message('error', 'Failed to update product');
    }
}

function handle_delete_product($mysqli) {
    $product_id = (int)($_POST['product_id'] ?? 0);
    
    $stmt = $mysqli->prepare("DELETE FROM tblproducts WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    
    if ($stmt->execute()) {
        set_flash_message('success', 'Product deleted successfully');
    } else {
        set_flash_message('error', 'Failed to delete product');
    }
}

function handle_update_stock($mysqli) {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    
    $stmt = $mysqli->prepare("
        UPDATE tblstock 
        SET stock = ? 
        WHERE product_id = ?
    ");
    $stmt->bind_param("ii", $stock, $product_id);
    
    if ($stmt->execute()) {
        set_flash_message('success', 'Stock updated successfully');
    } else {
        set_flash_message('error', 'Failed to update stock');
    }
}

function set_flash_message($type, $message) {
    $_SESSION['flash_type'] = $type;
    $_SESSION['flash_message'] = $message;
}

function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'];
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return ['type' => $type, 'message' => $message];
    }
    return null;
}

/**
 * Handle product management operations
 */
function handle_product_action($mysqli, $action, $data) {
    switch ($action) {
        case 'add_product':
            return add_product($mysqli, $data);
        case 'update_product':
            return update_product($mysqli, $data);
        case 'delete_product':
            return delete_product($mysqli, $data);
        default:
            return ['success' => false, 'message' => 'Invalid action'];
    }
}

/**
 * Add a new product
 */
function add_product($mysqli, $data) {
    try {
        $mysqli->begin_transaction();

        // Insert product
        $stmt = $mysqli->prepare("
            INSERT INTO tblproducts (product_name, price, brand_id, category_id, cost_price, view_count, added_to_cart, purchased_count) 
            VALUES (?, ?, ?, ?, ?, 0, 0, 0)
        ");
        $cost_price = $data['price'] * 0.7; // Set cost price as 70% of selling price
        $stmt->bind_param("sdiid", 
            $data['name'],
            $data['price'],
            $data['brand_id'],
            $data['category_id'],
            $cost_price
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Error adding product: " . $stmt->error);
        }
        
        $product_id = $mysqli->insert_id;

        // Insert stock
        $stmt = $mysqli->prepare("
            INSERT INTO tblstock (product_id, variant_id, size, stock) 
            VALUES (?, 1, 'Standard', ?)
        ");
        $stmt->bind_param("ii", $product_id, $data['stock']);
        
        if (!$stmt->execute()) {
            throw new Exception("Error adding stock: " . $stmt->error);
        }

        $mysqli->commit();
        return ['success' => true, 'message' => 'Product added successfully'];
    } catch (Exception $e) {
        $mysqli->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Update an existing product
 */
function update_product($mysqli, $data) {
    try {
        $mysqli->begin_transaction();

        // Update product
        $stmt = $mysqli->prepare("
            UPDATE tblproducts 
            SET product_name = ?, price = ?, brand_id = ?, category_id = ?, cost_price = ?
            WHERE product_id = ?
        ");
        $cost_price = $data['price'] * 0.7; // Update cost price as 70% of selling price
        $stmt->bind_param("sdiidi", 
            $data['name'],
            $data['price'],
            $data['brand_id'],
            $data['category_id'],
            $cost_price,
            $data['product_id']
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Error updating product: " . $stmt->error);
        }

        // Update stock
        $stmt = $mysqli->prepare("
            UPDATE tblstock 
            SET stock = ?
            WHERE product_id = ? AND variant_id = 1
        ");
        $stmt->bind_param("ii", $data['stock'], $data['product_id']);
        
        if (!$stmt->execute()) {
            throw new Exception("Error updating stock: " . $stmt->error);
        }

        $mysqli->commit();
        return ['success' => true, 'message' => 'Product updated successfully'];
    } catch (Exception $e) {
        $mysqli->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Delete a product
 */
function delete_product($mysqli, $data) {
    try {
        $mysqli->begin_transaction();

        // Delete stock records first (foreign key constraint)
        $stmt = $mysqli->prepare("DELETE FROM tblstock WHERE product_id = ?");
        $stmt->bind_param("i", $data['product_id']);
        
        if (!$stmt->execute()) {
            throw new Exception("Error deleting product stock: " . $stmt->error);
        }

        // Delete the product
        $stmt = $mysqli->prepare("DELETE FROM tblproducts WHERE product_id = ?");
        $stmt->bind_param("i", $data['product_id']);
        
        if (!$stmt->execute()) {
            throw new Exception("Error deleting product: " . $stmt->error);
        }

        $mysqli->commit();
        return ['success' => true, 'message' => 'Product deleted successfully'];
    } catch (Exception $e) {
        $mysqli->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Get product details with stock information
 */
function get_product_details($mysqli, $product_id) {
    $stmt = $mysqli->prepare("
        SELECT p.*, b.brand_name, c.category_name, s.stock
        FROM tblproducts p
        JOIN tblbrand b ON p.brand_id = b.brand_id
        JOIN tblcategory c ON p.category_id = c.category_id
        JOIN tblstock s ON p.product_id = s.product_id
        WHERE p.product_id = ?
    ");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Get all products with their brands and categories
 */
function get_all_products($mysqli) {
    $stmt = $mysqli->prepare("
        SELECT p.*, b.brand_name, c.category_name, s.stock
        FROM tblproducts p
        JOIN tblbrand b ON p.brand_id = b.brand_id
        JOIN tblcategory c ON p.category_id = c.category_id
        JOIN tblstock s ON p.product_id = s.product_id
        ORDER BY p.product_id DESC
    ");
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} 