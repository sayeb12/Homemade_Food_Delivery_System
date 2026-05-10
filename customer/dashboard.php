<?php
session_start();
if (isset($_SESSION['order_success'])) {
    echo '<p style="color: #2e7d32; text-align: center; font-size: 1.2em; margin-top: 20px;">' . htmlspecialchars($_SESSION['order_success']) . '</p>';
    unset($_SESSION['order_success']);
}

$pageTitle = 'Customer Dashboard';
$includeCart = true;

require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Check if user is logged in and is a customer
if (!isLoggedIn() || !isUserType('customer')) {
    header('Location: login.php');
    exit;
}

// Get user data
$userId = $_SESSION['user_id'];
$user = getUserData($userId);

$profileImage = getProfileImageUrl($_SESSION['profile_image'] ?? '', '..');

$customerName = $_SESSION['user_name'] ?? 'Customer';
$customerLocation = $_SESSION['user_location'] ?? '';

// Fetch all chefs
$chefs = $db->select("SELECT id, name FROM users WHERE user_type = ?", ['chef']);
if ($chefs === false) {
    $chefs = [];
}

// Fetch categories
$categories = getCategories();

// Fetch confirmed orders with item details for the customer
try {
    $confirmedOrders = $db->select(
        "SELECT o.id, o.total_amount, o.status, o.delivery_address, o.created_at 
         FROM orders o
         WHERE o.customer_id = ? AND o.status = 'confirmed'
         ORDER BY o.created_at DESC",
        [$userId]
    );
    if ($confirmedOrders === false) {
        $confirmedOrders = [];
    }
} catch (Exception $e) {
    error_log("Error fetching confirmed orders: " . $e->getMessage());
    $confirmedOrders = [];
}

// Fetch detailed order items for confirmed orders
$orderItems = [];
if (!empty($confirmedOrders)) {
    try {
        $orderIds = array_column($confirmedOrders, 'id');
        $items = $db->select(
            "SELECT oi.order_id, oi.food_item_id, oi.quantity, fi.name, fi.image, fi.chef_id,
                    COALESCE(u.name, 'Chef') AS chef_name, u.profile_image AS chef_profile_image
             FROM order_items oi
             LEFT JOIN food_items fi ON oi.food_item_id = fi.id
             LEFT JOIN users u ON fi.chef_id = u.id
             WHERE oi.order_id IN (" . implode(',', array_fill(0, count($orderIds), '?')) . ")",
            $orderIds
        );
        if ($items !== false) {
            foreach ($items as $item) {
                $orderItems[$item['order_id']][] = $item;
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching order items: " . $e->getMessage());
    }
}

// Fetch cart items for the customer
try {
    $cartItems = $db->select(
        "SELECT c.id, c.quantity, c.added_at, fi.id as food_id, fi.name, fi.price, fi.image, fi.quantity AS available_quantity, fi.is_available,
                u.name as chef_name
         FROM cart c
         JOIN food_items fi ON c.food_id = fi.id
         JOIN users u ON c.chef_id = u.id
         WHERE c.customer_id = ?",
        [$userId]
    );
    if ($cartItems === false) {
        $cartItems = [];
    }
} catch (Exception $e) {
    error_log("Error fetching cart items: " . $e->getMessage());
    $cartItems = [];
}

// AJAX: Handle "Add to Cart"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_cart_quantity') {
    $cartId = isset($_POST['cart_id']) ? (int) $_POST['cart_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 0;

    if ($cartId <= 0 || $quantity <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid cart item or quantity']);
        exit;
    }

    try {
        $cartItem = $db->selectOne(
            "SELECT c.id, c.customer_id, c.food_id, fi.price, fi.quantity AS available_quantity, fi.is_available
             FROM cart c
             JOIN food_items fi ON c.food_id = fi.id
             WHERE c.id = ? AND c.customer_id = ?",
            [$cartId, $userId]
        );

        if (!$cartItem) {
            echo json_encode(['status' => 'error', 'message' => 'Cart item not found']);
            exit;
        }

        if ((int) $cartItem['is_available'] !== 1 || (int) $cartItem['available_quantity'] <= 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'This item is not available right now',
                'available_quantity' => (int) ($cartItem['available_quantity'] ?? 0),
                'is_available' => false
            ]);
            exit;
        }

        if ($quantity > (int) $cartItem['available_quantity']) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Requested quantity is not available',
                'available_quantity' => (int) $cartItem['available_quantity'],
                'is_available' => true
            ]);
            exit;
        }

        $db->update(
            "UPDATE cart SET quantity = ?, added_at = NOW() WHERE id = ? AND customer_id = ?",
            [$quantity, $cartId, $userId]
        );

        echo json_encode([
            'status' => 'success',
            'message' => 'Cart quantity updated',
            'quantity' => $quantity,
            'unit_price' => (float) $cartItem['price'],
            'item_total' => (float) $cartItem['price'] * $quantity,
            'available_quantity' => (int) $cartItem['available_quantity']
        ]);
    } catch (Exception $e) {
        error_log("Error updating cart quantity: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Failed to update cart quantity']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
    $foodId = isset($_POST['food_id']) ? (int)$_POST['food_id'] : 0;
    $chefId = isset($_POST['chef_id']) ? (int)$_POST['chef_id'] : 0;

    // Validate input
    if ($foodId <= 0 || $chefId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid food or chef ID']);
        exit;
    }

    try {
        // Check if the item already exists in the cart
        $existingItem = $db->select(
            "SELECT id, quantity FROM cart WHERE customer_id = ? AND food_id = ? AND chef_id = ?",
            [$userId, $foodId, $chefId]
        );

        if ($existingItem && count($existingItem) > 0) {
            // Update quantity if the item already exists
            $newQuantity = $existingItem[0]['quantity'] + 1;
            $db->update(
                "UPDATE cart SET quantity = ?, added_at = NOW() WHERE id = ?",
                [$newQuantity, $existingItem[0]['id']]
            );
        } else {
            // Insert new item into the cart
            $db->insert(
                "INSERT INTO cart (customer_id, food_id, chef_id, quantity, added_at) VALUES (?, ?, ?, 1, NOW())",
                [$userId, $foodId, $chefId]
            );
        }

        echo json_encode(['status' => 'success', 'message' => 'Item added to cart successfully']);
    } catch (Exception $e) {
        error_log("Error adding to cart: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Error adding to cart']);
    }
    exit;
}

// AJAX: Fetch foods
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'fetch_foods') {
    $chefId = isset($_GET['chef_id']) ? (int)$_GET['chef_id'] : null;
    $category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : '';
    $location = isset($_GET['location']) ? trim(sanitizeInput($_GET['location'])) : '';
    $searchName = isset($_GET['search']) ? trim(sanitizeInput($_GET['search'])) : '';

    $query = "SELECT f.*, u.name AS chef_name, u.address AS chef_location, u.profile_image AS chef_profile_image
              FROM food_items f 
              JOIN users u ON f.chef_id = u.id 
              WHERE f.is_available = 1 AND f.quantity > 0";
    $params = [];

    if ($chefId) {
        $query .= " AND f.chef_id = ?";
        $params[] = $chefId;
    } elseif ($location && $location !== '') {
        $query .= " AND LOWER(u.address) LIKE LOWER(?)";
        $params[] = "%$location%";
    }

    if ($category) {
        $query .= " AND f.category = ?";
        $params[] = $category;
    }

    if ($searchName) {
        $query .= " AND LOWER(f.name) LIKE LOWER(?)";
        $params[] = "%$searchName%";
    }

    $countQuery = "SELECT COUNT(*) as total 
                   FROM food_items f 
                   JOIN users u ON f.chef_id = u.id 
                   WHERE f.is_available = 1 AND f.quantity > 0";
    $countParams = [];

    if ($chefId) {
        $countQuery .= " AND f.chef_id = ?";
        $countParams[] = $chefId;
    } elseif ($location && $location !== '') {
        $countQuery .= " AND LOWER(u.address) LIKE LOWER(?)";
        $countParams[] = "%$location%";
    }

    if ($category) {
        $countQuery .= " AND f.category = ?";
        $countParams[] = $category;
    }

    if ($searchName) {
        $countQuery .= " AND LOWER(f.name) LIKE LOWER(?)";
        $countParams[] = "%$searchName%";
    }

    try {
        $totalResult = $db->select($countQuery, $countParams);
        $totalAvailableItems = $totalResult[0]['total'] ?? 0;

        $query .= " ORDER BY f.created_at DESC, f.id DESC";

        $foodItems = $db->select($query, $params);
        if ($foodItems === false) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to fetch food items']);
            exit;
        }

        foreach ($foodItems as &$foodItem) {
            $foodItem['chef_location'] = !empty($foodItem['chef_location']) ? $foodItem['chef_location'] : 'Not specified';
            $foodItem['chef_image_url'] = getProfileImageUrl($foodItem['chef_profile_image'] ?? '', '..');
        }
        unset($foodItem);

        echo json_encode([
            'status' => 'success',
            'foods' => $foodItems,
            'total_items' => $totalAvailableItems
        ]);
    } catch (Exception $e) {
        error_log("Error fetching foods: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Server error fetching food items']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Homemade Food Delivery</title>
    <style>
        /* Existing styles unchanged */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(rgba(255, 255, 255, 0.5), rgba(255, 255, 255, 0.5)), 
                        url('https://images.unsplash.com/photo-1504674900247-087ca5f5c2f0?ixlib=rb-4.0.3&auto=format&fit=crop&w=1350&q=80') no-repeat center center fixed;
            background-size: cover;
            color: #333;
            line-height: 1.6;
            overflow-x: hidden;
            animation: fadeIn 1s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @keyframes scaleIn {
            from { transform: scale(0.95); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        header {
            background: linear-gradient(90deg, #2e7d32, #66bb6a);
            padding: 15px 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
            animation: fadeIn 0.8s ease-in-out;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1300px;
            margin: 0 auto;
        }

        .logo {
            transition: transform 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.05);
        }

        .logo img {
            height: 50px;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
        }

        .search-bar {
            flex-grow: 1;
            margin: 0 30px;
        }

        .search-bar input {
            width: 100%;
            padding: 12px 20px;
            border: 1px solid #a5d6a7;
            border-radius: 30px;
            font-size: 1em;
            outline: none;
            background: #f1f8e9;
            transition: all 0.3s ease;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        .search-bar input:focus {
            border-color: #388e3c;
            box-shadow: 0 0 8px rgba(56, 142, 60, 0.3);
            background: #fff;
        }

        .user {
            display: flex;
            align-items: center;
            gap: 15px;
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 15px;
            border-radius: 30px;
            transition: all 0.3s ease;
        }

        .user:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.02);
        }

        .user img {
            height: 45px;
            width: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #a5d6a7;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
            transition: transform 0.3s ease;
        }

        .user img:hover {
            transform: rotate(10deg);
        }

        .user div {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }

        .user div strong {
            font-size: 1.1em;
            color: #fff;
            font-weight: 500;
        }

        .user div span {
            font-size: 0.9em;
            color: #f5f5f5;
        }

        .btn {
            padding: 10px 20px;
            background: linear-gradient(45deg, #2e7d32, #66bb6a);
            color: #fff;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 0.95em;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
        }

        .btn:hover {
            background: linear-gradient(45deg, #66bb6a, #2e7d32);
            transform: translateY(-2px);
            box-shadow: 0 5px 12px rgba(0, 0, 0, 0.2);
            animation: pulse 0.5s infinite;
        }

        .btn-dark {
            background: linear-gradient(45deg, #dc3545, #e74c3c);
        }

        .btn-dark:hover {
            background: linear-gradient(45deg, #e74c3c, #dc3545);
            transform: translateY(-2px);
            box-shadow: 0 5px 12px rgba(0, 0, 0, 0.2);
            animation: pulse 0.5s infinite;
        }

        .main-container {
            display: flex;
            max-width: 1300px;
            margin: 30px auto;
            gap: 30px;
        }

        .sidebar {
            width: 280px;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
            padding: 25px;
            animation: slideUp 0.5s ease-in-out;
            border: 1px solid #a5d6a7;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40"><path d="M20 5c-5 0-9 4-9 9s4 9 9 9 9-4 9-9-4-9-9-9zm0 2c3.9 0 7 3.1 7 7s-3.1 7-7 7-7-3.1-7-7 3.1-7 7-7zm-1 2c-1.7 0-3 1.3-3 3s1.3 3 3 3 3-1.3 3-3-1.3-3-3-3z" fill="%2366bb6a" fill-opacity="0.1"/></svg>');
        }

        .sidebar h3 {
            font-size: 1.8em;
            color: #2e7d32;
            margin-bottom: 20px;
            font-weight: 600;
            position: relative;
        }

        .sidebar h3::after {
            content: '';
            width: 50px;
            height: 3px;
            background: linear-gradient(90deg, #2e7d32, #66bb6a);
            position: absolute;
            bottom: -5px;
            left: 0;
            border-radius: 2px;
        }

        .chef-select {
            width: 100%;
            padding: 12px;
            border: 1px solid #a5d6a7;
            border-radius: 8px;
            font-size: 1em;
            color: #2d3436;
            background: #f1f8e9 url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12"><path d="M6 9l-4-4h8z" fill="%232e7d32"/></svg>') no-repeat right 10px center;
            appearance: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        .chef-select:focus {
            border-color: #388e3c;
            box-shadow: 0 0 8px rgba(56, 142, 60, 0.3);
            background: #fff;
            outline: none;
        }

        .chef-select option {
            padding: 10px;
            background: #fff;
            color: #2d3436;
        }

        .filter-container {
            background: #fff;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
            display: flex;
            gap: 20px;
            align-items: center;
            animation: slideUp 0.5s ease-in-out;
            border: 1px solid #a5d6a7;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40"><path d="M20 5c-5 0-9 4-9 9s4 9 9 9 9-4 9-9-4-9-9-9zm0 2c3.9 0 7 3.1 7 7s-3.1 7-7 7-7-3.1-7-7 3.1-7 7-7zm-1 2c-1.7 0-3 1.3-3 3s1.3 3 3 3 3-1.3 3-3-1.3-3-3-3z" fill="%2366bb6a" fill-opacity="0.1"/></svg>');
        }

        .filter-container label {
            font-size: 1em;
            color: #2e7d32;
            font-weight: 500;
        }

        .filter-container select {
            padding: 10px;
            border: 1px solid #a5d6a7;
            border-radius: 8px;
            font-size: 0.95em;
            outline: none;
            background: #f1f8e9 url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12"><path d="M6 9l-4-4h8z" fill="%232e7d32"/></svg>') no-repeat right 10px center;
            transition: all 0.3s ease;
            flex: 1;
            appearance: none;
        }

        .filter-container select:focus {
            border-color: #388e3c;
            box-shadow: 0 0 8px rgba(56, 142, 60, 0.3);
            background: #fff;
        }

        .filter-container input[type="text"] {
            padding: 10px;
            border: 1px solid #a5d6a7;
            border-radius: 8px;
            font-size: 0.95em;
            outline: none;
            background: #f1f8e9;
            transition: all 0.3s ease;
            flex: 1;
        }

        .filter-container input[type="text"]:focus {
            border-color: #388e3c;
            box-shadow: 0 0 8px rgba(56, 142, 60, 0.3);
            background: #fff;
        }

        .location-toggle {
            padding: 8px 16px;
            background: linear-gradient(45deg, #388e3c, #a5d6a7);
            color: #fff;
            border: none;
            border-radius: 25px;
            font-size: 0.9em;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        .location-toggle:hover {
            background: linear-gradient(45deg, #a5d6a7, #388e3c);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }

        .available-items {
            background: #fff;
            padding: 10px 20px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            text-align: center;
            font-size: 1.1em;
            color: #2e7d32;
            font-weight: 500;
            border: 1px solid #a5d6a7;
            animation: slideUp 0.5s ease-in-out;
        }

        .content {
            flex: 1;
        }

        .section-title {
            text-align: center;
            margin-bottom: 40px;
            animation: fadeIn 1s ease-in-out;
        }

        .section-title h2 {
            font-size: 2.5em;
            color: #2e7d32;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
        }

        .section-title h2::after {
            content: '';
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, #2e7d32, #66bb6a);
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            border-radius: 2px;
        }

        .food-grid, .order-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
        }

        .food-card, .order-card {
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 16px 34px rgba(39, 78, 42, 0.12);
            transition: transform 0.4s ease, box-shadow 0.4s ease, border-color 0.35s ease;
            overflow: hidden;
            border: 1px solid #a5d6a7;
            animation: slideUp 0.5s ease forwards;
            animation-delay: calc(var(--delay) * 0.1s);
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40"><path d="M20 5c-5 0-9 4-9 9s4 9 9 9 9-4 9-9-4-9-9-9zm0 2c3.9 0 7 3.1 7 7s-3.1 7-7 7-7-3.1-7-7 3.1-7 7-7zm-1 2c-1.7 0-3 1.3-3 3s1.3 3 3 3 3-1.3 3-3-1.3-3-3-3z" fill="%2366bb6a" fill-opacity="0.1"/></svg>');
            position: relative;
        }

        .food-card::before, .order-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #2e7d32, #66bb6a);
            transition: all 0.3s ease;
        }

        .food-card:hover, .order-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 26px 48px rgba(39, 78, 42, 0.18);
            border-color: #388e3c;
        }

        .food-card:hover::before, .order-card:hover::before {
            height: 100%;
            opacity: 0.1;
        }

        .food-card-image {
            height: 220px;
            overflow: hidden;
            position: relative;
        }

        .food-card-image::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(27, 52, 29, 0.5), rgba(27, 52, 29, 0.02) 55%);
            pointer-events: none;
        }

        .food-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .food-card:hover .food-card-image img {
            transform: scale(1.1);
        }

        .food-card-content, .order-card-content {
            padding: 22px;
        }

        .food-card-chef-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
        }

        .chef-avatar {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(102, 187, 106, 0.45);
            box-shadow: 0 8px 18px rgba(46, 125, 50, 0.16);
            transition: transform 0.35s ease;
        }

        .food-card:hover .chef-avatar {
            transform: rotate(-5deg) scale(1.04);
        }

        .chef-copy {
            display: flex;
            flex-direction: column;
            gap: 3px;
            min-width: 0;
        }

        .food-card-title, .order-card-title {
            font-size: 1.5em;
            color: #2d3436;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .food-card-chef {
            color: #636e72;
            font-size: 0.95em;
            margin-bottom: 0;
            font-weight: 600;
        }

        .food-card-location {
            color: #636e72;
            font-size: 0.85em;
            margin-bottom: 14px;
            font-style: italic;
        }

        .food-card-description {
            color: #5f6d63;
            font-size: 0.95em;
            margin-bottom: 15px;
            min-height: 48px;
        }

        .food-card-meta, .order-card-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            gap: 10px;
            flex-wrap: wrap;
        }

        .food-card-price, .order-card-price {
            font-weight: bold;
            color: #388e3c;
            font-size: 1.2em;
        }

        .food-card-time {
            color: #636e72;
            font-size: 0.95em;
        }

        .food-card-details {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 18px;
        }

        .detail-pill {
            padding: 10px 12px;
            border-radius: 14px;
            background: rgba(241, 248, 233, 0.92);
            color: #2d3436;
            font-size: 0.9em;
            border: 1px solid rgba(165, 214, 167, 0.55);
        }

        .order-card-items {
            color: #636e72;
            font-size: 0.95em;
            margin-bottom: 12px;
        }

        .order-card-address, .order-card-date {
            color: #636e72;
            font-size: 0.9em;
            margin-bottom: 8px;
        }

        .add-to-cart {
            width: 100%;
            padding: 12px;
            background: linear-gradient(45deg, #2e7d32, #66bb6a);
            color: #fff;
            border: none;
            border-radius: 25px;
            font-size: 1em;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
            position: relative;
            overflow: hidden;
        }

        .add-to-cart:hover {
            background: linear-gradient(45deg, #66bb6a, #2e7d32);
            transform: translateY(-2px);
            box-shadow: 0 5px 12px rgba(0, 0, 0, 0.2);
            animation: pulse 0.5s infinite;
        }

        .add-to-cart::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(120deg, transparent 15%, rgba(255, 255, 255, 0.25), transparent 65%);
            transform: translateX(-120%);
            transition: transform 0.55s ease;
        }

        .add-to-cart:hover::after {
            transform: translateX(120%);
        }

        .proceed-to-payment, .feedback-btn {
            display: inline-block;
            padding: 10px 20px;
            background: linear-gradient(45deg, #2e7d32, #66bb6a);
            color: #fff;
            border: none;
            border-radius: 25px;
            font-size: 0.95em;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
            cursor: pointer;
        }

        .proceed-to-payment:hover, .feedback-btn:hover {
            background: linear-gradient(45deg, #66bb6a, #2e7d32);
            transform: translateY(-2px);
            box-shadow: 0 5px 12px rgba(0, 0, 0, 0.2);
            animation: pulse 0.5s infinite;
        }

        .feedback-btn:disabled {
            background: #dfe6e9;
            color: #b2bec3;
            cursor: not-allowed;
            box-shadow: none;
        }

        .cart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 22px;
        }

        .cart-item-image, .order-card-image {
            height: 100%;
            min-height: 170px;
            overflow: hidden;
            position: relative;
            margin-bottom: 0;
            border-radius: 18px;
        }

        .cart-item-image::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(27, 52, 29, 0.24), rgba(27, 52, 29, 0.02) 55%);
            pointer-events: none;
        }

        .cart-item-image img, .order-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.35s ease;
        }

        .order-history-section {
            background: #fff;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 40px;
            border: 1px solid #a5d6a7;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .tab-button {
            padding: 10px 20px;
            background: #e0e0e0;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .tab-button.active {
            background: #2e7d32;
            color: #fff;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .cart-item {
            display: grid;
            grid-template-columns: 140px 1fr;
            gap: 18px;
            align-items: stretch;
            padding: 18px;
            border: 1px solid rgba(165, 214, 167, 0.85);
            border-radius: 22px;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(241, 248, 233, 0.9));
            box-shadow: 0 16px 32px rgba(39, 78, 42, 0.12);
            transition: transform 0.35s ease, box-shadow 0.35s ease, border-color 0.3s ease;
            overflow: hidden;
            position: relative;
        }

        .cart-item::before {
            content: '';
            position: absolute;
            inset: 0 auto 0 0;
            width: 5px;
            background: linear-gradient(180deg, #2e7d32, #66bb6a);
        }

        .cart-item:hover {
            transform: translateY(-6px);
            box-shadow: 0 24px 40px rgba(39, 78, 42, 0.16);
            border-color: #66bb6a;
        }

        .cart-item:hover .cart-item-image img {
            transform: scale(1.06);
        }

        .cart-item-content {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 14px;
            min-width: 0;
        }

        .cart-item-header {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .cart-item h3 {
            color: #2e7d32;
            font-size: 1.25em;
            line-height: 1.2;
        }

        .cart-chef {
            color: #5e6d60;
            font-size: 0.96em;
            font-weight: 500;
        }

        .cart-meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .cart-meta > p {
            display: none;
        }

        .cart-meta-pill {
            padding: 10px 12px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.88);
            border: 1px solid rgba(165, 214, 167, 0.65);
            color: #2d3436;
            font-size: 0.9em;
        }

        .cart-meta-pill strong {
            color: #2e7d32;
        }

        .cart-added-on {
            color: #6b7c6d;
            font-size: 0.88em;
        }

        .cart-quantity-row {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .cart-quantity-input {
            width: 110px;
            padding: 10px 12px;
            border: 1px solid rgba(165, 214, 167, 0.75);
            border-radius: 12px;
            background: #fff;
            font-size: 0.95em;
        }

        .cart-status {
            display: inline-flex;
            align-items: center;
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 0.85em;
            font-weight: 600;
            width: fit-content;
        }

        .cart-status.available {
            background: rgba(46, 125, 50, 0.12);
            color: #2e7d32;
        }

        .cart-status.unavailable {
            background: rgba(211, 47, 47, 0.12);
            color: #c62828;
        }

        .cart-helper {
            color: #7b8b7d;
            font-size: 0.84em;
        }

        .pagination-bar {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 24px;
        }

        .pagination-bar button {
            padding: 10px 14px;
            border-radius: 12px;
            border: 1px solid rgba(165, 214, 167, 0.8);
            background: rgba(255, 255, 255, 0.92);
            color: #2e7d32;
            cursor: pointer;
            font-weight: 600;
        }

        .pagination-bar button.active {
            background: linear-gradient(45deg, #2e7d32, #66bb6a);
            color: #fff;
            border-color: transparent;
        }

        .pagination-bar button:disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }

        .modal {
            display: none;
            position: fixed;
            inset: 0;
            padding: 24px;
            background: rgba(15, 23, 12, 0.72);
            backdrop-filter: blur(8px);
            justify-content: center;
            align-items: center;
            z-index: 1000;
            animation: fadeIn 0.35s ease-in-out;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            width: min(760px, 100%);
            max-height: min(92vh, 860px);
            overflow-y: auto;
            padding: 0;
            border-radius: 28px;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.28);
            animation: scaleIn 0.28s ease-in-out;
            border: 1px solid rgba(165, 214, 167, 0.75);
            background:
                linear-gradient(145deg, rgba(255, 255, 255, 0.96), rgba(241, 248, 233, 0.95));
            scrollbar-width: none;
        }

        .modal-content::-webkit-scrollbar {
            width: 0;
            height: 0;
        }

        .feedback-shell {
            display: grid;
            grid-template-columns: minmax(220px, 280px) minmax(0, 1fr);
            min-height: 100%;
        }

        .feedback-hero {
            position: relative;
            padding: 28px;
            color: #fff;
            background:
                linear-gradient(180deg, rgba(46, 125, 50, 0.88), rgba(56, 142, 60, 0.76)),
                url('https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=900&q=80') center/cover;
            overflow: hidden;
        }

        .feedback-hero::after {
            content: '';
            position: absolute;
            inset: auto -40px -60px auto;
            width: 180px;
            height: 180px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.32), rgba(255, 255, 255, 0));
        }

        .feedback-kicker {
            display: inline-flex;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.25);
            font-size: 0.82em;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            margin-bottom: 18px;
        }

        .feedback-hero h3 {
            font-size: 1.9em;
            line-height: 1.2;
            margin-bottom: 12px;
        }

        .feedback-hero p {
            color: rgba(255, 255, 255, 0.92);
            margin-bottom: 18px;
            font-size: 0.98em;
        }

        .feedback-hero-points {
            display: grid;
            gap: 10px;
        }

        .feedback-hero-points span {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: rgba(255, 255, 255, 0.95);
            font-size: 0.92em;
        }

        .feedback-body {
            padding: 28px;
        }

        .feedback-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 20px;
        }

        .feedback-head-copy h4 {
            font-size: 1.55em;
            color: #245c2a;
            margin-bottom: 6px;
        }

        .feedback-head-copy p {
            color: #617462;
            font-size: 0.95em;
        }

        .feedback-close {
            border: none;
            background: rgba(102, 187, 106, 0.18);
            color: #2e7d32;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.3em;
            transition: all 0.25s ease;
        }

        .feedback-close:hover {
            background: rgba(46, 125, 50, 0.16);
            transform: rotate(90deg);
        }

        .feedback-summary {
            display: grid;
            grid-template-columns: 112px minmax(0, 1fr);
            gap: 18px;
            align-items: center;
            padding: 18px;
            border-radius: 22px;
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(165, 214, 167, 0.6);
            box-shadow: 0 14px 30px rgba(46, 125, 50, 0.1);
            margin-bottom: 22px;
        }

        .feedback-dish-image {
            width: 112px;
            height: 112px;
            border-radius: 22px;
            object-fit: cover;
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.16);
        }

        .feedback-summary-main {
            display: flex;
            flex-direction: column;
            gap: 12px;
            min-width: 0;
        }

        .feedback-order-pill {
            display: inline-flex;
            width: fit-content;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(46, 125, 50, 0.1);
            color: #2e7d32;
            font-size: 0.82em;
            font-weight: 600;
        }

        .feedback-item-name {
            font-size: 1.25em;
            color: #1f3523;
            font-weight: 700;
        }

        .feedback-chef-row {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .feedback-chef-avatar {
            width: 54px;
            height: 54px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(165, 214, 167, 0.65);
            box-shadow: 0 10px 18px rgba(46, 125, 50, 0.18);
        }

        .feedback-chef-copy {
            min-width: 0;
        }

        .feedback-chef-copy strong,
        .feedback-chef-copy span {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .feedback-chef-copy strong {
            color: #254d2a;
            font-size: 1em;
        }

        .feedback-chef-copy span {
            color: #6f816f;
            font-size: 0.9em;
        }

        .feedback-form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .feedback-field label {
            display: block;
            color: #2e7d32;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .rating-grid {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .rating-chip {
            border: 1px solid rgba(165, 214, 167, 0.8);
            background: rgba(255, 255, 255, 0.92);
            color: #2e7d32;
            min-width: 66px;
            padding: 12px 14px;
            border-radius: 16px;
            cursor: pointer;
            font-weight: 700;
            transition: all 0.24s ease;
        }

        .rating-chip:hover,
        .rating-chip.active {
            background: linear-gradient(45deg, #2e7d32, #66bb6a);
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 10px 18px rgba(46, 125, 50, 0.22);
            border-color: transparent;
        }

        .feedback-helper {
            color: #738272;
            font-size: 0.88em;
            margin-top: -4px;
        }

        .feedback-form textarea {
            width: 100%;
            min-height: 140px;
            padding: 14px 16px;
            border: 1px solid rgba(165, 214, 167, 0.85);
            border-radius: 18px;
            font-size: 0.96em;
            background: rgba(255, 255, 255, 0.92);
            transition: all 0.28s ease;
            resize: vertical;
            color: #2d3436;
        }

        .feedback-form textarea:focus {
            border-color: #388e3c;
            box-shadow: 0 0 0 4px rgba(102, 187, 106, 0.18);
            outline: none;
            background: #fff;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 6px;
        }

        .form-actions button {
            padding: 13px 22px;
            border-radius: 999px;
            font-size: 0.96em;
            min-width: 140px;
        }

        .form-actions button.cancel-btn {
            background: rgba(223, 230, 233, 0.88);
            color: #2d3436;
        }

        .form-actions button.cancel-btn:hover {
            background: rgba(178, 190, 195, 0.95);
            transform: translateY(-2px);
            box-shadow: 0 5px 12px rgba(0, 0, 0, 0.14);
        }

        footer {
            background: linear-gradient(90deg, #2e7d32, #66bb6a);
            color: #fff;
            padding: 40px 20px;
            margin-top: 60px;
            text-align: center;
            animation: fadeIn 1s ease-in-out;
        }

        .footer-content {
            max-width: 1300px;
            margin: 0 auto;
        }

        .footer-logo img {
            height: 40px;
            margin-bottom: 15px;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
        }

        .footer-text {
            color: #f1f8e9;
            margin-bottom: 20px;
            font-size: 1.1em;
        }

        .footer-social a {
            color: #fff;
            margin: 0 15px;
            font-size: 1.5em;
            transition: all 0.3s ease;
        }

        .footer-social a:hover {
            color: #a5d6a7;
            transform: scale(1.2);
        }

        .footer-contact {
            margin-top: 15px;
            color: #f1f8e9;
            font-size: 1em;
        }

        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 15px;
            }

            .search-bar {
                margin: 0;
                width: 100%;
            }

            .user {
                width: 100%;
                justify-content: center;
            }

            .main-container {
                flex-direction: column;
                margin: 20px;
            }

            .sidebar {
                width: 100%;
            }

            .filter-container {
                flex-direction: column;
                align-items: stretch;
                padding: 15px;
            }

            .food-grid, .order-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            }

            .cart-grid {
                grid-template-columns: 1fr;
            }

            .cart-item {
                grid-template-columns: 1fr;
            }

            .cart-item-image {
                min-height: 210px;
            }

            .cart-meta {
                grid-template-columns: 1fr;
            }

            .tabs {
                flex-direction: column;
            }

            .tab-button {
                width: 100%;
                text-align: left;
            }

            .feedback-shell {
                grid-template-columns: 1fr;
            }

            .feedback-hero,
            .feedback-body {
                padding: 22px;
            }

            .feedback-summary {
                grid-template-columns: 1fr;
            }

            .feedback-dish-image {
                width: 100%;
                height: 190px;
            }

            .form-actions {
                flex-direction: column-reverse;
            }

            .form-actions button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo">
                <a href="../index.php">
                    <img src="../assets/images/logo.png" alt="Homemade Food Delivery">
                </a>
            </div>
            
            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="Enter item or Home you are looking for">
            </div>
            
            <div class="user">
                <img src="<?php echo htmlspecialchars($profileImage); ?>" alt="<?php echo htmlspecialchars($customerName); ?>">
                <div>
                    <strong><?php echo htmlspecialchars($customerName); ?></strong>
                    <span><?php echo htmlspecialchars($customerLocation); ?></span>
                </div>
                <button class="btn" onclick="toggleOrderHistory()">Order History</button>
                <a href="../logout.php" class="btn btn-dark">Log Out</a>
            </div>
        </div>
    </header>

    <div class="main-container">
        <div class="sidebar">
            <h3>Chefs</h3>
            <select class="chef-select" id="chefSelect" onchange="selectChef(this.value)">
                <option value="0">All Chefs</option>
                <?php foreach ($chefs as $chef): ?>
                    <option value="<?php echo $chef['id']; ?>"><?php echo htmlspecialchars($chef['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="content">
            <div id="orderHistorySection" class="order-history-section" style="display: none;">
                <div class="section-title">
                    <h2>Your Order History</h2>
                </div>
                <div class="tabs">
                    <button class="tab-button active" onclick="openTab('cart')">Cart Items</button>
                    <button class="tab-button" onclick="openTab('confirmed')">Confirmed Orders</button>
                </div>

                <div id="cart" class="tab-content active">
                    <?php if (empty($cartItems)): ?>
                        <p style="text-align: center; color: #2e7d32; font-weight: 500;">No items in your cart.</p>
                    <?php else: ?>
                        <div class="cart-grid" id="cartGrid">
                        <?php foreach ($cartItems as $item): ?>
                            <?php
                            $isCartItemAvailable = (int) ($item['is_available'] ?? 0) === 1 && (int) ($item['available_quantity'] ?? 0) > 0;
                            $currentCartQuantity = min((int) $item['quantity'], max((int) ($item['available_quantity'] ?? 0), 1));
                            ?>
                            <div class="cart-item">
                                <div class="cart-item-image">
                                    <img src="<?php echo htmlspecialchars(getDishImageUrl($item['image'] ?? '', '..')); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                </div>
                                <div class="cart-item-content">
                                    <div class="cart-item-header">
                                        <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                        <p class="cart-chef">Chef: <?php echo htmlspecialchars($item['chef_name']); ?></p>
                                    </div>
                                    <div class="cart-meta">
                                        <div class="cart-meta-pill"><strong>Unit Price:</strong> ৳<?php echo number_format($item['price'], 2); ?></div>
                                <p>Price: ৳<?php echo number_format($item['price'], 2); ?></p>
                                        <div class="cart-meta-pill"><strong>Price:</strong> ৳<?php echo number_format($item['price'], 2); ?></div>
                                    </div>
                                    <div class="cart-quantity-row">
                                        <input
                                            type="number"
                                            min="1"
                                            max="<?php echo max((int) ($item['available_quantity'] ?? 0), 1); ?>"
                                            value="<?php echo $currentCartQuantity; ?>"
                                            class="cart-quantity-input"
                                            id="cartQty-<?php echo $item['id']; ?>"
                                            data-price="<?php echo htmlspecialchars($item['price']); ?>"
                                            data-available="<?php echo (int) ($item['available_quantity'] ?? 0); ?>"
                                            onchange="updateCartQuantity(<?php echo $item['id']; ?>)"
                                            oninput="refreshCartItemTotal(<?php echo $item['id']; ?>)"
                                            <?php echo $isCartItemAvailable ? '' : 'disabled'; ?>
                                        >
                                        <span class="cart-status <?php echo $isCartItemAvailable ? 'available' : 'unavailable'; ?>" id="cartStatus-<?php echo $item['id']; ?>">
                                            <?php echo $isCartItemAvailable ? 'Available' : 'Not Available'; ?>
                                        </span>
                                        <span class="cart-meta-pill" id="cartDynamicTotal-<?php echo $item['id']; ?>"><strong>Total:</strong> ৳<?php echo number_format($item['price'] * $currentCartQuantity, 2); ?></span>
                                    </div>
                                    <p class="cart-helper">Available to order: <?php echo (int) ($item['available_quantity'] ?? 0); ?></p>
                                    <p class="cart-added-on">Added on: <?php echo htmlspecialchars(date('d M Y, H:i', strtotime($item['added_at']))); ?></p>
                                    <button class="proceed-to-payment" onclick="proceedToPayment(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>', <?php echo $item['price']; ?>, '<?php echo htmlspecialchars($item['image'] ?? ''); ?>', <?php echo $item['food_id']; ?>, document.getElementById('cartQty-<?php echo $item['id']; ?>').value)" <?php echo $isCartItemAvailable ? '' : 'disabled'; ?>>Proceed to Payment</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                        <div class="pagination-bar" id="cartPagination"></div>
                    <?php endif; ?>
                </div>

                <div id="confirmed" class="tab-content">
                    <?php if (empty($confirmedOrders)): ?>
                        <p style="text-align: center; grid-column: 1 / -1; color: #2e7d32; font-weight: 500;">No confirmed orders yet.</p>
                    <?php else: ?>
                        <div class="order-grid" id="orderGrid">
                            <?php foreach ($confirmedOrders as $index => $order): ?>
                                <?php
                                $items = $orderItems[$order['id']] ?? [];
                                $itemsList = '';
                                foreach ($items as $item) {
                                    $itemsList .= htmlspecialchars($item['name'] ?? 'Unknown Item') . ' (Qty: ' . htmlspecialchars($item['quantity'] ?? 0) . '), ';
                                }
                                $itemsList = rtrim($itemsList, ', ') ?: 'N/A';
                                $primaryItem = $items[0] ?? [];
                                $displayImage = !empty($items)
                                    ? getDishImageUrl($items[0]['image'] ?? '', '..')
                                    : '../assets/images/placeholder.jpg';
                                $displayName = !empty($items) && !empty($items[0]['name']) 
                                    ? htmlspecialchars($items[0]['name']) 
                                    : 'Order Image';
                                $chefName = !empty($primaryItem['chef_name']) ? $primaryItem['chef_name'] : 'Chef';
                                $chefImage = getProfileImageUrl($primaryItem['chef_profile_image'] ?? '', '..');
                                $feedbackItemName = !empty($primaryItem['name']) ? $primaryItem['name'] : 'Order item';
                                ?>
                                <div class="order-card" style="--delay: <?php echo $index; ?>">
                                    <div class="order-card-content">
                                        <h3 class="order-card-title">Order #<?php echo htmlspecialchars($order['id']); ?></h3>
                                        <div class="order-card-image">
                                            <img src="<?php echo $displayImage; ?>" alt="<?php echo $displayName; ?>">
                                        </div>
                                        <p class="order-card-items">Items: <?php echo $itemsList; ?></p>
                                        <p class="order-card-address">Delivery Address: <?php echo htmlspecialchars($order['delivery_address']); ?></p>
                                        <p class="order-card-date">Ordered On: <?php echo htmlspecialchars(date('d M Y, H:i', strtotime($order['created_at']))); ?></p>
                                        <div class="order-card-meta">
                                            <div class="order-card-price">৳<?php echo number_format($order['total_amount'], 2); ?></div>
                                            <button
                                                class="feedback-btn"
                                                data-order-id="<?php echo $order['id']; ?>"
                                                data-chef-name="<?php echo htmlspecialchars($chefName); ?>"
                                                data-chef-image="<?php echo htmlspecialchars($chefImage); ?>"
                                                data-item-name="<?php echo htmlspecialchars($feedbackItemName); ?>"
                                                data-item-image="<?php echo htmlspecialchars($displayImage); ?>"
                                                disabled
                                            >Give Feedback</button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="pagination-bar" id="confirmedPagination"></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="available-items" id="availableItems">
                Total Available Items: <span id="totalItemsCount">0</span>
            </div>

            <div class="filter-container">
                <label for="categorySelect">Filter by Category:</label>
                <select id="categorySelect">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $key => $label): ?>
                        <option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($label); ?></option>
                    <?php endforeach; ?>
                </select>

                <button id="locationToggle" class="location-toggle" onclick="toggleLocationInput()">
                    Use Custom Location
                </button>
                
                <div id="locationInputContainer" style="display: none; flex: 1;">
                    <label for="locationInput">Custom Location:</label>
                    <input type="text" id="locationInput" placeholder="Enter a location">
                </div>
            </div>

            <div class="section-title">
                <h2>Explore Homemade Foods</h2>
            </div>
            
            <div class="food-grid" id="foodGrid"></div>
            <div class="pagination-bar" id="foodPagination"></div>
        </div>
    </div>

    <!-- Feedback Modal -->
    <div id="feedbackModal" class="modal">
        <div class="modal-content">
            <div class="feedback-shell">
                <div class="feedback-hero">
                    <span class="feedback-kicker">Share Your Taste</span>
                    <h3>Tell us how this homemade meal felt.</h3>
                    <p>Your feedback helps chefs improve their dishes and gives other customers more confidence before ordering.</p>
                    <div class="feedback-hero-points">
                        <span>Fresh food experience</span>
                        <span>Chef service and presentation</span>
                        <span>Honest rating that really helps</span>
                    </div>
                </div>
                <div class="feedback-body">
                    <div class="feedback-head">
                        <div class="feedback-head-copy">
                            <h4>Submit Feedback</h4>
                            <p>Review the chef and the dish from this confirmed order.</p>
                        </div>
                        <button type="button" class="feedback-close" onclick="closeFeedbackModal()" aria-label="Close feedback form">&times;</button>
                    </div>

                    <div class="feedback-summary">
                        <img id="feedbackDishImage" class="feedback-dish-image" src="../assets/images/placeholder.jpg" alt="Ordered item">
                        <div class="feedback-summary-main">
                            <span class="feedback-order-pill" id="feedbackOrderLabel">Order</span>
                            <div class="feedback-item-name" id="feedbackItemName">Selected item</div>
                            <div class="feedback-chef-row">
                                <img id="feedbackChefImage" class="feedback-chef-avatar" src="../assets/images/default-profile.jpg" alt="Chef">
                                <div class="feedback-chef-copy">
                                    <strong id="feedbackChefName">Chef Name</strong>
                                    <span>Chef behind this order</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form id="feedbackForm" class="feedback-form">
                        <input type="hidden" name="order_id">
                        <input type="hidden" name="rating" id="feedbackRatingInput" value="5">

                        <div class="feedback-field">
                            <label>How would you rate this order?</label>
                            <div class="rating-grid">
                                <button type="button" class="rating-chip" data-rating="1">1 Star</button>
                                <button type="button" class="rating-chip" data-rating="2">2 Stars</button>
                                <button type="button" class="rating-chip" data-rating="3">3 Stars</button>
                                <button type="button" class="rating-chip" data-rating="4">4 Stars</button>
                                <button type="button" class="rating-chip active" data-rating="5">5 Stars</button>
                            </div>
                            <p class="feedback-helper">Choose the rating that best matches your experience.</p>
                        </div>

                        <div class="feedback-field">
                            <label for="feedbackComment">Comment</label>
                            <textarea id="feedbackComment" name="comment" rows="5" placeholder="Share what you liked about the taste, freshness, packaging, or chef service..."></textarea>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="cancel-btn" onclick="closeFeedbackModal()">Cancel</button>
                            <button type="submit">Submit Feedback</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        let selectedChefId = 0;
        let useCustomLocation = false;

        function toggleOrderHistory() {
            const orderHistorySection = document.getElementById('orderHistorySection');
            orderHistorySection.style.display = orderHistorySection.style.display === 'none' ? 'block' : 'none';
            if (orderHistorySection.style.display === 'block' && document.getElementById('confirmed').classList.contains('active')) {
                checkFeedbackStatus();
            }
        }

        function openTab(tabName) {
            document.getElementById('cart').classList.toggle('active', tabName === 'cart');
            document.getElementById('confirmed').classList.toggle('active', tabName === 'confirmed');
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.toggle('active', btn.textContent.trim() === (tabName === 'cart' ? 'Cart Items' : 'Confirmed Orders'));
            });
            if (tabName === 'confirmed') {
                checkFeedbackStatus();
            }
        }

        function toggleLocationInput() {
            useCustomLocation = !useCustomLocation;
            const locationInputContainer = document.getElementById('locationInputContainer');
            const locationToggle = document.getElementById('locationToggle');

            if (useCustomLocation) {
                locationInputContainer.style.display = 'flex';
                locationToggle.textContent = 'Clear Location Filter';
                document.getElementById('locationInput').focus();
            } else {
                locationInputContainer.style.display = 'none';
                locationToggle.textContent = 'Use Custom Location';
                document.getElementById('locationInput').value = '';
            }

            loadFoods();
        }

        function selectChef(chefId) {
            selectedChefId = parseInt(chefId);
            document.getElementById('chefSelect').value = chefId;
            loadFoods();
        }

        function refreshCartItemTotal(cartId) {
            const quantityInput = document.getElementById(`cartQty-${cartId}`);
            const totalElement = document.getElementById(`cartDynamicTotal-${cartId}`);
            if (!quantityInput || !totalElement) {
                return;
            }

            const maxAvailable = parseInt(quantityInput.dataset.available || '0', 10);
            let quantity = parseInt(quantityInput.value || '1', 10);
            if (!quantity || quantity < 1) {
                quantity = 1;
            }
            if (maxAvailable > 0 && quantity > maxAvailable) {
                quantity = maxAvailable;
            }
            quantityInput.value = quantity;

            const unitPrice = parseFloat(quantityInput.dataset.price || '0');
            totalElement.innerHTML = `<strong>Total:</strong> ৳${(unitPrice * quantity).toFixed(2)}`;
        }

        function updateCartQuantity(cartId) {
            const quantityInput = document.getElementById(`cartQty-${cartId}`);
            const statusElement = document.getElementById(`cartStatus-${cartId}`);
            if (!quantityInput) {
                return;
            }

            const requestedQuantity = parseInt(quantityInput.value || '1', 10);
            const body = new URLSearchParams({
                action: 'update_cart_quantity',
                cart_id: cartId,
                quantity: requestedQuantity
            });

            fetch('dashboard.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: body.toString()
            })
                .then(res => {
                    if (!res.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return res.json();
                })
                .then(data => {
                    if (data.status === 'success') {
                        quantityInput.value = data.quantity;
                        quantityInput.dataset.available = data.available_quantity;
                        refreshCartItemTotal(cartId);
                        if (statusElement) {
                            statusElement.textContent = 'Available';
                            statusElement.className = 'cart-status available';
                        }
                    } else {
                        if (data.available_quantity !== undefined) {
                            quantityInput.dataset.available = data.available_quantity;
                            if (data.available_quantity > 0) {
                                quantityInput.value = Math.min(data.available_quantity, Math.max(1, requestedQuantity));
                            }
                        }
                        refreshCartItemTotal(cartId);
                        if (statusElement && data.is_available === false) {
                            statusElement.textContent = 'Not Available';
                            statusElement.className = 'cart-status unavailable';
                            quantityInput.disabled = true;
                        }
                        alert(data.message || 'Unable to update quantity');
                    }
                })
                .catch(error => {
                    console.error('Error updating cart quantity:', error);
                    alert('Error updating cart quantity. Please try again.');
                });
        }

        function paginateCollection(containerId, itemSelector, paginationId, itemsPerPage = 6) {
            const container = document.getElementById(containerId);
            const pagination = document.getElementById(paginationId);
            if (!container || !pagination) {
                return;
            }

            const items = Array.from(container.querySelectorAll(itemSelector));
            pagination.innerHTML = '';

            if (items.length <= itemsPerPage) {
                items.forEach(item => item.style.display = '');
                return;
            }

            const totalPages = Math.ceil(items.length / itemsPerPage);
            let currentPage = 1;

            function renderPage(page) {
                currentPage = page;
                items.forEach((item, index) => {
                    const start = (page - 1) * itemsPerPage;
                    const end = start + itemsPerPage;
                    item.style.display = index >= start && index < end ? '' : 'none';
                });

                Array.from(pagination.querySelectorAll('button[data-page]')).forEach(button => {
                    button.classList.toggle('active', parseInt(button.dataset.page, 10) === page);
                });
            }

            const prev = document.createElement('button');
            prev.textContent = 'Previous';
            prev.addEventListener('click', () => {
                if (currentPage > 1) {
                    renderPage(currentPage - 1);
                }
            });
            pagination.appendChild(prev);

            for (let page = 1; page <= totalPages; page++) {
                const button = document.createElement('button');
                button.textContent = page;
                button.dataset.page = page;
                button.addEventListener('click', () => renderPage(page));
                pagination.appendChild(button);
            }

            const next = document.createElement('button');
            next.textContent = 'Next';
            next.addEventListener('click', () => {
                if (currentPage < totalPages) {
                    renderPage(currentPage + 1);
                }
            });
            pagination.appendChild(next);

            renderPage(1);
        }

        function loadFoods() {
            const category = document.getElementById('categorySelect').value;
            let location = '';

            if (useCustomLocation) {
                location = document.getElementById('locationInput').value.trim();
                if (!location) {
                    location = '';
                }
            }

            const searchInput = document.getElementById('searchInput');
            const searchVal = searchInput ? searchInput.value.trim() : '';

            const params = new URLSearchParams({
                action: 'fetch_foods',
                chef_id: selectedChefId,
                category: category,
                location: location,
                search: searchVal
            });

            fetch(`dashboard.php?${params.toString()}`)
                .then(res => {
                    if (!res.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return res.json();
                })
                .then(data => {
                    if (data.status === 'error') {
                        throw new Error(data.message);
                    }

                    const foodGrid = document.getElementById('foodGrid');
                    foodGrid.innerHTML = '';

                    const totalItemsCount = document.getElementById('totalItemsCount');
                    totalItemsCount.textContent = data.total_items || 0;

                    if (!data.foods || data.foods.length === 0) {
                        foodGrid.innerHTML = '<p style="text-align: center; grid-column: 1 / -1; color: #2e7d32; font-weight: 500;">No food items available.</p>';
                        return;
                    }

                    data.foods.forEach((item, index) => {
                        const card = document.createElement('div');
                        card.className = 'food-card';
                        card.style.setProperty('--delay', index);
                        let cardContent = `
                            <div class="food-card-image">
                                <img src="${item.image ? '../uploads/dishes/' + encodeURIComponent(item.image).replace(/%2F/g, '/') : '../assets/images/placeholder.jpg'}" alt="${item.name}">
                            </div>
                            <div class="food-card-content">
                                <h3 class="food-card-title">${item.name}</h3>
                                <div class="food-card-chef-row">
                                    <img src="${item.chef_image_url || '../assets/images/default-profile.jpg'}" alt="${item.chef_name}" class="chef-avatar">
                                    <div class="chef-copy">
                                        <p class="food-card-chef">By ${item.chef_name}</p>
                                        <p class="food-card-location">Kitchen: ${item.chef_location || 'Not specified'}</p>
                                    </div>
                                </div>
                                <p class="food-card-description">${item.description || 'Fresh homemade food prepared with care and ready for delivery.'}</p>
                        `;

                        if (selectedChefId === 0) {
                            cardContent += `
                                <div class="food-card-meta">
                                    <div class="food-card-price">৳${parseFloat(item.price).toFixed(2)}</div>
                                    <div class="food-card-time">${item.preparation_time} Mins</div>
                                </div>
                            `;
                        } else {
                            cardContent += `
                                <div class="food-card-meta">
                                    <div class="food-card-price">৳${parseFloat(item.price).toFixed(2)}</div>
                                </div>
                            `;
                        }

                        cardContent += `
                                <div class="food-card-details">
                                    <div class="detail-pill">Available Qty: ${item.quantity}</div>
                                    <div class="detail-pill">Category: ${item.category || 'Chef Special'}</div>
                                </div>
                                <button class="add-to-cart" 
                                    data-id="${item.id}" 
                                    data-name="${item.name}" 
                                    data-price="${item.price}" 
                                    data-image="${item.image ? '../uploads/dishes/' + encodeURIComponent(item.image).replace(/%2F/g, '/') : '../assets/images/placeholder.jpg'}"
                                    data-chef="${item.chef_id}">
                                    Add To Cart
                                </button>
                            </div>
                        `;
                        card.innerHTML = cardContent;
                        foodGrid.appendChild(card);
                    });

                    attachAddToCartListeners();
                    paginateCollection('foodGrid', '.food-card', 'foodPagination', 6);
                })
                .catch(err => {
                    console.error('Error loading foods:', err);
                    document.getElementById('foodGrid').innerHTML = 
                        '<p style="text-align: center; grid-column: 1 / -1; color: #dc3545; font-weight: 500;">Error loading food items. Please try again later.</p>';
                });
        }

        function attachAddToCartListeners() {
            document.querySelectorAll('.add-to-cart').forEach(button => {
                button.addEventListener('click', () => {
                    const foodId = button.dataset.id;
                    const name = button.dataset.name;
                    const price = button.dataset.price;
                    const image = button.dataset.image;
                    const chefId = button.dataset.chef;

                    const params = new URLSearchParams({
                        action: 'add_to_cart',
                        food_id: foodId,
                        chef_id: chefId
                    });

                    fetch('dashboard.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: params.toString()
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(result => {
                        if (result.status === 'success') {
                            alert(result.message);
                            // Show the cart section and refresh it
                            const orderHistorySection = document.getElementById('orderHistorySection');
                            if (orderHistorySection.style.display !== 'block') {
                                toggleOrderHistory();
                            }
                            // Ensure the cart tab is active
                            if (!document.getElementById('cart').classList.contains('active')) {
                                openTab('cart');
                            }
                            // Refresh the page to update the cart items
                            window.location.reload();
                        } else {
                            alert(result.message || 'Error adding to cart');
                        }
                    })
                    .catch(error => {
                        console.error('Error adding to cart:', error);
                        alert('Error adding to cart. Please try again.');
                    });
                });
            });
        }

        function proceedToPayment(cartItemId, name, price, image, foodId, quantity) {
            window.location.href = `make_payment.php?cart_item_id=${cartItemId}&name=${encodeURIComponent(name)}&price=${price}&image=${encodeURIComponent(image)}&food_id=${foodId}&quantity=${quantity}`;
        }

        function setFeedbackRating(rating) {
            const ratingInput = document.getElementById('feedbackRatingInput');
            if (!ratingInput) {
                return;
            }

            ratingInput.value = rating;
            document.querySelectorAll('.rating-chip').forEach(chip => {
                chip.classList.toggle('active', parseInt(chip.dataset.rating || '0', 10) === rating);
            });
        }

        function openFeedbackModal(orderId, chefName, chefImage, itemName, itemImage) {
            const form = document.getElementById('feedbackForm');
            form.reset();
            form.order_id.value = orderId;
            document.getElementById('feedbackOrderLabel').textContent = `Order #${orderId}`;
            document.getElementById('feedbackChefName').textContent = chefName || 'Chef';
            document.getElementById('feedbackChefImage').src = chefImage || '../assets/images/default-profile.jpg';
            document.getElementById('feedbackItemName').textContent = itemName || 'Ordered item';
            document.getElementById('feedbackDishImage').src = itemImage || '../assets/images/placeholder.jpg';
            setFeedbackRating(5);
            document.getElementById('feedbackModal').classList.add('show');
        }

        function closeFeedbackModal() {
            document.getElementById('feedbackModal').classList.remove('show');
        }

        function checkFeedbackStatus() {
            document.querySelectorAll('.feedback-btn').forEach(button => {
                const orderId = button.dataset.orderId;
                fetch(`feedback.php?action=check_feedback&order_id=${orderId}`)
                    .then(res => {
                        if (!res.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return res.json();
                    })
                    .then(data => {
                        if (data.status === 'success') {
                            button.disabled = data.has_feedback;
                            button.textContent = data.has_feedback ? 'Feedback Submitted' : 'Give Feedback';
                            if (!data.has_feedback) {
                                button.onclick = () => openFeedbackModal(
                                    orderId,
                                    button.dataset.chefName,
                                    button.dataset.chefImage,
                                    button.dataset.itemName,
                                    button.dataset.itemImage
                                );
                            }
                        } else {
                            console.error('Error checking feedback:', data.message);
                            button.disabled = true;
                            button.textContent = 'Error';
                        }
                    })
                    .catch(err => {
                        console.error('Error checking feedback status:', err);
                        button.disabled = true;
                        button.textContent = 'Error';
                    });
            });
        }

        document.getElementById('feedbackForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'submit_feedback');

            fetch('feedback.php', {
                method: 'POST',
                body: formData
            })
            .then(res => {
                if (!res.ok) {
                    throw new Error('Network response was not ok');
                }
                return res.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    closeFeedbackModal();
                    checkFeedbackStatus();
                    alert(data.message);
                } else {
                    alert(data.message || 'Error submitting feedback');
                }
            })
            .catch(err => {
                console.error('Error submitting feedback:', err);
                alert('Error submitting feedback. Please try again.');
            });
        });

        document.addEventListener('DOMContentLoaded', () => {
            loadFoods();
            paginateCollection('cartGrid', '.cart-item', 'cartPagination', 6);
            paginateCollection('orderGrid', '.order-card', 'confirmedPagination', 6);
            document.querySelectorAll('.rating-chip').forEach(chip => {
                chip.addEventListener('click', () => {
                    setFeedbackRating(parseInt(chip.dataset.rating || '5', 10));
                });
            });
        });

        document.getElementById('categorySelect').addEventListener('change', () => {
            loadFoods();
        });

        document.getElementById('locationInput')?.addEventListener('input', () => {
            loadFoods();
        });

        document.getElementById('searchInput')?.addEventListener('input', () => {
            loadFoods();
        });
    </script>
<?php
$footerBasePath = '..';
$footerTheme = 'green';
require '../includes/footer.php';
?>

