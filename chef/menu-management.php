<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Authentication check
if (!isLoggedIn() || !isUserType('chef')) {
    header('Location: login.php');
    exit;
}

// Get chef information
$chef_id = $_SESSION['user_id'];

// Create uploads directory if it doesn't exist
if (!file_exists($uploadDirs['dishes'])) {
    mkdir($uploadDirs['dishes'], 0755, true);
}

$profile_picture = getProfileImageUrl($_SESSION['profile_image'] ?? '', '..');

function getChefDashboardStats($chefId, $db) {
    $stats = [
        'total_dishes' => 0,
        'available_dishes' => 0,
        'unavailable_dishes' => 0,
        'confirmed_orders' => 0,
        'avg_rating' => 0
    ];

    try {
        $dishStats = $db->selectOne(
            "SELECT COUNT(*) AS total_dishes,
                    SUM(CASE WHEN is_available = 1 AND quantity > 0 THEN 1 ELSE 0 END) AS available_dishes,
                    SUM(CASE WHEN is_available = 0 OR quantity <= 0 THEN 1 ELSE 0 END) AS unavailable_dishes
             FROM food_items
             WHERE chef_id = ?",
            [$chefId]
        );

        $orderStats = $db->selectOne(
            "SELECT COUNT(DISTINCT o.id) AS confirmed_orders,
                    COALESCE(AVG(f.rating), 0) AS avg_rating
             FROM orders o
             LEFT JOIN feedback f ON o.id = f.order_id
             WHERE o.id IN (
                 SELECT DISTINCT oi.order_id
                 FROM order_items oi
                 JOIN food_items fi ON oi.food_item_id = fi.id
                 WHERE fi.chef_id = ?
             ) AND o.status = 'confirmed'",
            [$chefId]
        );

        if ($dishStats) {
            $stats['total_dishes'] = (int) ($dishStats['total_dishes'] ?? 0);
            $stats['available_dishes'] = (int) ($dishStats['available_dishes'] ?? 0);
            $stats['unavailable_dishes'] = (int) ($dishStats['unavailable_dishes'] ?? 0);
        }

        if ($orderStats) {
            $stats['confirmed_orders'] = (int) ($orderStats['confirmed_orders'] ?? 0);
            $stats['avg_rating'] = round((float) ($orderStats['avg_rating'] ?? 0), 1);
        }
    } catch (Exception $e) {
        error_log('Error loading chef dashboard stats: ' . $e->getMessage());
    }

    return $stats;
}

$chefStats = getChefDashboardStats($chef_id, $db);

// AJAX: fetch dishes
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'list') {
    $search = $_GET['search'] ?? '';
    $category = $_GET['category'] ?? '';
    $statusFilter = $_GET['status'] ?? 'available';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $itemsPerPage = 6;
    $offset = ($page - 1) * $itemsPerPage;

    $query = "SELECT * FROM food_items WHERE chef_id = ?";
    $params = [$chef_id];

    if ($search) {
        $query .= " AND name LIKE ?";
        $params[] = "%$search%";
    }
    if ($category) {
        $query .= " AND category = ?";
        $params[] = $category;
    }
    if ($statusFilter === 'available') {
        $query .= " AND is_available = 1 AND quantity > 0";
    } elseif ($statusFilter === 'unavailable') {
        $query .= " AND (is_available = 0 OR quantity <= 0)";
    }

    // Get total count for pagination
    $totalQuery = "SELECT COUNT(*) as total FROM food_items WHERE chef_id = ?";
    $totalParams = [$chef_id];
    if ($search) {
        $totalQuery .= " AND name LIKE ?";
        $totalParams[] = "%$search%";
    }
    if ($category) {
        $totalQuery .= " AND category = ?";
        $totalParams[] = $category;
    }
    if ($statusFilter === 'available') {
        $totalQuery .= " AND is_available = 1 AND quantity > 0";
    } elseif ($statusFilter === 'unavailable') {
        $totalQuery .= " AND (is_available = 0 OR quantity <= 0)";
    }
    try {
        $totalResult = $db->selectOne($totalQuery, $totalParams);
        $totalItems = $totalResult['total'];
        $totalPages = ceil($totalItems / $itemsPerPage);

        $query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $itemsPerPage;
        $params[] = $offset;

        $dishes = $db->select($query, $params);
        if ($dishes === false) {
            $dishes = [];
        }
        echo json_encode(['dishes' => $dishes, 'totalPages' => $totalPages, 'currentPage' => $page]);
    } catch (Exception $e) {
        error_log("Error fetching dishes: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Server error fetching dishes']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'summary') {
    echo json_encode(['status' => 'success', 'summary' => getChefDashboardStats($chef_id, $db)]);
    exit;
}

// AJAX: fetch order history
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'order_history') {
    try {
        $query = "SELECT o.id, o.customer_id, o.total_amount, o.delivery_address, o.created_at, 
                         oi.food_item_id, oi.quantity, oi.price, 
                         f.name AS food_name, f.image AS food_image, 
                         COALESCE(u.name, 'Unknown Customer') AS customer_name,
                         u.profile_image AS customer_profile_image,
                         p.payment_method, p.transaction_id
                  FROM orders o
                  JOIN order_items oi ON o.id = oi.order_id
                  JOIN food_items f ON oi.food_item_id = f.id
                  LEFT JOIN users u ON o.customer_id = u.id
                  JOIN payments p ON o.id = p.order_id
                  WHERE f.chef_id = ? AND o.status = 'confirmed'
                  ORDER BY o.created_at DESC";
        $orders = $db->select($query, [$chef_id]);
        if ($orders === false) {
            $orders = [];
        }
        foreach ($orders as &$order) {
            $order['customer_image_url'] = getProfileImageUrl($order['customer_profile_image'] ?? '', '..');
        }
        unset($order);
        echo json_encode(['orders' => $orders]);
    } catch (Exception $e) {
        error_log("Error fetching order history: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Server error fetching order history']);
    }
    exit;
}

// AJAX: fetch feedback
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'fetch_feedback') {
    try {
        $feedback = $db->select(
            "SELECT f.id, f.order_id, f.rating, f.comment, f.created_at, 
                    COALESCE(u.name, 'Unknown Customer') AS customer_name,
                    u.profile_image AS customer_profile_image,
                    GROUP_CONCAT(COALESCE(fi.name, 'Unknown Item')) AS item_names
             FROM feedback f
             LEFT JOIN users u ON f.customer_id = u.id
             LEFT JOIN order_items oi ON f.order_id = oi.order_id
             LEFT JOIN food_items fi ON oi.food_item_id = fi.id
             WHERE f.chef_id = ?
             GROUP BY f.id
             ORDER BY f.created_at DESC",
            [$chef_id]
        );
        if ($feedback === false) {
            $feedback = [];
        }
        foreach ($feedback as &$feedbackItem) {
            $feedbackItem['customer_image_url'] = getProfileImageUrl($feedbackItem['customer_profile_image'] ?? '', '..');
        }
        unset($feedbackItem);
        echo json_encode(['status' => 'success', 'feedback' => $feedback]);
    } catch (Exception $e) {
        error_log("Error fetching feedback: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Server error fetching feedback']);
    }
    exit;
}

// AJAX: fetch one dish for editing
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    try {
        $dish = $db->selectOne("SELECT * FROM food_items WHERE id = ? AND chef_id = ?", [$_GET['id'], $chef_id]);
        if ($dish) {
            echo json_encode($dish);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Dish not found']);
        }
    } catch (Exception $e) {
        error_log("Error fetching dish: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Server error fetching dish']);
    }
    exit;
}

// AJAX: add/edit/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isLoggedIn()) {
        echo json_encode(['status' => 'error', 'message' => 'Session expired. Please log in again.']);
        exit;
    }

    if ($_POST['action'] === 'delete') {
        try {
            $dish = $db->selectOne("SELECT image FROM food_items WHERE id = ? AND chef_id = ?", [$_POST['id'], $chef_id]);
            if ($dish && $dish['image'] && file_exists($uploadDirs['dishes'] . $dish['image'])) {
                unlink($uploadDirs['dishes'] . $dish['image']);
            }

            $db->delete("DELETE FROM food_items WHERE id = ? AND chef_id = ?", [$_POST['id'], $chef_id]);
            echo json_encode(['status' => 'success', 'message' => 'Dish deleted successfully']);
        } catch (Exception $e) {
            error_log("Error deleting dish: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Server error deleting dish']);
        }
        exit;
    }

    if ($_POST['action'] === 'update_availability') {
        $dishId = (int) ($_POST['id'] ?? 0);
        $isAvailable = (int) ($_POST['is_available'] ?? 0);

        try {
            $dish = $db->selectOne("SELECT id, quantity FROM food_items WHERE id = ? AND chef_id = ?", [$dishId, $chef_id]);
            if (!$dish) {
                echo json_encode(['status' => 'error', 'message' => 'Dish not found']);
                exit;
            }

            if ($isAvailable === 1 && (int) $dish['quantity'] <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Increase quantity before making this dish available']);
                exit;
            }

            $db->update("UPDATE food_items SET is_available = ? WHERE id = ? AND chef_id = ?", [$isAvailable, $dishId, $chef_id]);
            echo json_encode(['status' => 'success', 'message' => 'Availability updated successfully']);
        } catch (Exception $e) {
            error_log("Error updating availability: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Failed to update availability']);
        }
        exit;
    }

    if ($_POST['action'] === 'increase_quantity') {
        $dishId = (int) ($_POST['id'] ?? 0);
        $quantityIncrement = (int) ($_POST['quantity_increment'] ?? 0);

        if ($quantityIncrement <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Increase amount must be greater than zero']);
            exit;
        }

        try {
            $dish = $db->selectOne("SELECT id, quantity FROM food_items WHERE id = ? AND chef_id = ?", [$dishId, $chef_id]);
            if (!$dish) {
                echo json_encode(['status' => 'error', 'message' => 'Dish not found']);
                exit;
            }

            $newQuantity = (int) $dish['quantity'] + $quantityIncrement;
            $db->update(
                "UPDATE food_items SET quantity = ?, is_available = 1 WHERE id = ? AND chef_id = ?",
                [$newQuantity, $dishId, $chef_id]
            );

            echo json_encode([
                'status' => 'success',
                'message' => 'Quantity updated successfully',
                'quantity' => $newQuantity
            ]);
        } catch (Exception $e) {
            error_log("Error increasing quantity: " . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Failed to increase quantity']);
        }
        exit;
    }

    $id = $_POST['id'] ?? '';
    $name = sanitizeInput($_POST['name'] ?? '');
    $desc = sanitizeInput($_POST['description'] ?? '');
    $peptime = (int)($_POST['preparation_time'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $qty = (int)($_POST['quantity'] ?? 0);
    $cat = sanitizeInput($_POST['category'] ?? '');
    $available = (int)($_POST['is_available'] ?? 0);
    $imageName = null;

    if ($qty === 0) {
        $available = 0;
    }

    // Validate inputs
    if ($name === '') {
        echo json_encode(['status' => 'error', 'message' => 'Dish name is required']);
        exit;
    }
    if ($price <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Price must be greater than 0']);
        exit;
    }
    if ($qty < 0) {
        echo json_encode(['status' => 'error', 'message' => 'Quantity cannot be negative']);
        exit;
    }
    if ($peptime <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Preparation time must be greater than 0']);
        exit;
    }
    if (!in_array($cat, array_keys(getCategories()))) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid category']);
        exit;
    }

    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $uploadResult = uploadImage($_FILES['image'], $uploadDirs['dishes']);
        if ($uploadResult['success']) {
            $imageName = $uploadResult['fileName'];
        } else {
            echo json_encode(['status' => 'error', 'message' => $uploadResult['message']]);
            exit;
        }
    }

    try {
        if ($id) {
            // Update existing dish
            $dish = $db->selectOne("SELECT id, image FROM food_items WHERE id = ? AND chef_id = ?", [$id, $chef_id]);
            if (!$dish) {
                echo json_encode(['status' => 'error', 'message' => 'Unauthorized action']);
                exit;
            }

            $query = "UPDATE food_items SET name=?, description=?, price=?, quantity=?, category=?, is_available=?, preparation_time=?";
            $params = [$name, $desc, $price, $qty, $cat, $available, $peptime];

            if ($imageName) {
                if ($dish['image'] && file_exists($uploadDirs['dishes'] . $dish['image'])) {
                    unlink($uploadDirs['dishes'] . $dish['image']);
                }
                $query .= ", image=?";
                $params[] = $imageName;
            }

            $query .= " WHERE id=? AND chef_id=?";
            $params[] = $id;
            $params[] = $chef_id;

            $db->update($query, $params);
            echo json_encode(['status' => 'success', 'message' => 'Dish updated successfully']);
        } else {
            // Add new dish
            $query = "INSERT INTO food_items (chef_id, name, description, price, quantity, category, is_available, preparation_time, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [$chef_id, $name, $desc, $price, $qty, $cat, $available, $peptime, $imageName];

            $db->insert($query, $params);
            echo json_encode(['status' => 'success', 'message' => 'Dish added successfully']);
        }
    } catch (Exception $e) {
        error_log("Error saving dish: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Server error saving dish']);
    }
    exit;
}

// Fetch categories for form
$categories = getCategories();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Management - Homemade Food Delivery</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        :root {
            --green-700: #2e7d32;
            --green-500: #66bb6a;
            --green-200: #c8e6c9;
            --green-100: #f1f8e9;
            --ink-900: #1f2a1f;
            --ink-700: #516051;
            --surface: rgba(255, 255, 255, 0.88);
            --shadow-soft: 0 18px 45px rgba(34, 64, 40, 0.12);
        }

        body {
            background:
                linear-gradient(rgba(255, 255, 255, 0.52), rgba(255, 255, 255, 0.58)),
                url('https://images.unsplash.com/photo-1504674900247-087ca5f5c2f0') no-repeat center center fixed;
            background-size: cover;
            color: #333;
            line-height: 1.6;
            overflow-x: hidden;
            animation: fadeIn 1s ease-in-out;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(circle at 15% 20%, rgba(102, 187, 106, 0.2), transparent 24%),
                radial-gradient(circle at 85% 18%, rgba(46, 125, 50, 0.18), transparent 22%),
                radial-gradient(circle at 50% 100%, rgba(165, 214, 167, 0.24), transparent 26%);
            pointer-events: none;
            z-index: -1;
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

        .logo img {
            height: 50px;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
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
        }

        .user img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff;
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
        }

        .main-container {
            max-width: 1300px;
            margin: 34px auto;
            padding: 0 20px;
            display: flex;
            flex-direction: column;
        }

        .dashboard-hero {
            position: relative;
            display: grid;
            grid-template-columns: 1.25fr 0.9fr;
            gap: 24px;
            padding: 32px;
            margin-bottom: 26px;
            border-radius: 28px;
            background:
                linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(241, 248, 233, 0.86)),
                linear-gradient(120deg, rgba(255, 255, 255, 0.28), rgba(255, 255, 255, 0.05));
            border: 1px solid rgba(165, 214, 167, 0.8);
            box-shadow: var(--shadow-soft);
            overflow: hidden;
            animation: slideUp 0.6s ease-in-out;
        }

        .dashboard-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at top right, rgba(102, 187, 106, 0.24), transparent 24%),
                linear-gradient(90deg, rgba(255,255,255,0.02), rgba(255,255,255,0));
            pointer-events: none;
        }

        .hero-copy,
        .hero-side {
            position: relative;
            z-index: 1;
        }

        .hero-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 15px;
            border-radius: 999px;
            background: rgba(46, 125, 50, 0.1);
            color: var(--green-700);
            font-size: 0.84em;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 16px;
        }

        .hero-copy h1 {
            font-size: clamp(2.3rem, 4vw, 3.4rem);
            line-height: 1.05;
            color: var(--green-700);
            margin-bottom: 14px;
        }

        .hero-copy p {
            max-width: 640px;
            color: var(--ink-700);
            font-size: 1.02em;
            margin-bottom: 24px;
        }

        .hero-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .hero-badges span {
            padding: 10px 14px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.78);
            border: 1px solid rgba(165, 214, 167, 0.78);
            color: var(--green-700);
            font-weight: 500;
            box-shadow: 0 8px 18px rgba(46, 125, 50, 0.08);
            animation: badgeFloat 6s ease-in-out infinite;
        }

        .hero-badges span:nth-child(2) {
            animation-delay: -2s;
        }

        .hero-badges span:nth-child(3) {
            animation-delay: -4s;
        }

        @keyframes badgeFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        .hero-side {
            display: flex;
            align-items: stretch;
        }

        .hero-panel {
            width: 100%;
            padding: 24px;
            border-radius: 24px;
            background:
                linear-gradient(160deg, rgba(46, 125, 50, 0.92), rgba(102, 187, 106, 0.86)),
                url('https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=900&q=80') center/cover;
            color: #fff;
            box-shadow: 0 20px 40px rgba(46, 125, 50, 0.2);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 100%;
        }

        .hero-panel h3 {
            font-size: 1.55em;
            margin-bottom: 10px;
        }

        .hero-panel p {
            color: rgba(255, 255, 255, 0.92);
        }

        .hero-panel-list {
            display: grid;
            gap: 12px;
            margin-top: 22px;
        }

        .hero-panel-item {
            padding: 12px 14px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 18px;
            margin-bottom: 26px;
            order: 3;
        }

        .summary-card {
            padding: 18px 18px;
            border-radius: 22px;
            background: var(--surface);
            border: 1px solid rgba(165, 214, 167, 0.8);
            box-shadow: var(--shadow-soft);
            backdrop-filter: blur(10px);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: slideUp 0.55s ease-in-out;
        }

        .summary-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 24px 45px rgba(34, 64, 40, 0.16);
        }

        .summary-label {
            color: var(--ink-700);
            font-size: 0.92em;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .summary-value {
            font-size: 1.55rem;
            line-height: 1;
            color: var(--green-700);
            font-weight: 700;
            margin-bottom: 8px;
        }

        .summary-note {
            color: #6f806f;
            font-size: 0.92em;
        }

        .tabs {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            padding: 12px;
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.72);
            border: 1px solid rgba(165, 214, 167, 0.82);
            box-shadow: var(--shadow-soft);
            backdrop-filter: blur(10px);
            order: 1;
        }

        .tab-button {
            padding: 13px 22px;
            background: transparent;
            border: 1px solid transparent;
            border-radius: 18px;
            cursor: pointer;
            font-weight: 600;
            color: var(--ink-700);
            transition: all 0.3s ease;
        }

        .tab-button.active {
            background: linear-gradient(135deg, #2e7d32, #66bb6a);
            color: #fff;
            box-shadow: 0 12px 26px rgba(46, 125, 50, 0.2);
        }

        .tab-button:hover {
            border-color: rgba(165, 214, 167, 0.9);
            background: rgba(241, 248, 233, 0.9);
        }

        .tab-content {
            display: none;
            background: rgba(255, 255, 255, 0.8);
            padding: 24px;
            border-radius: 28px;
            box-shadow: var(--shadow-soft);
            border: 1px solid rgba(165, 214, 167, 0.85);
            backdrop-filter: blur(12px);
            animation: slideUp 0.5s ease-in-out;
            order: 2;
        }

        .tab-content.active {
            display: block;
        }

        .section-title {
            text-align: center;
            margin-bottom: 20px;
        }

        .section-title h2 {
            font-size: 2.5em;
            color: #2e7d32;
            font-weight: 700;
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
        }

        .filter-container {
            display: flex;
            gap: 16px;
            margin-bottom: 24px;
            flex-wrap: wrap;
            padding: 18px;
            border-radius: 22px;
            background: rgba(255, 255, 255, 0.72);
            border: 1px solid rgba(165, 214, 167, 0.75);
        }

        .dish-status-switcher {
            display: inline-flex;
            gap: 10px;
            padding: 8px;
            border-radius: 18px;
            background: rgba(241, 248, 233, 0.9);
            border: 1px solid rgba(165, 214, 167, 0.8);
        }

        .status-filter-btn {
            padding: 10px 16px;
            border: none;
            border-radius: 14px;
            background: transparent;
            color: var(--green-700);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .status-filter-btn.active {
            background: linear-gradient(135deg, #2e7d32, #66bb6a);
            color: #fff;
            box-shadow: 0 10px 20px rgba(46, 125, 50, 0.18);
        }

        .filter-container input, .filter-container select {
            padding: 13px 14px;
            border: 1px solid #a5d6a7;
            border-radius: 16px;
            font-size: 0.95em;
            background: rgba(241, 248, 233, 0.85);
            flex: 1;
            min-width: 180px;
        }

        .filter-container select {
            background: #f1f8e9 url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12"><path d="M6 9l-4-4h8z" fill="%232e7d32"/></svg>') no-repeat right 10px center;
            appearance: none;
        }

        .filter-container input:focus, .filter-container select:focus {
            border-color: #388e3c;
            box-shadow: 0 0 8px rgba(56, 142, 60, 0.3);
            background: #fff;
        }

        .dish-grid, .order-grid, .feedback-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .dish-card, .order-card, .feedback-card {
            background: rgba(255, 255, 255, 0.92);
            border-radius: 24px;
            box-shadow: var(--shadow-soft);
            overflow: hidden;
            border: 1px solid rgba(165, 214, 167, 0.8);
            transition: all 0.3s ease;
            position: relative;
        }

        .dish-card:hover, .order-card:hover, .feedback-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 26px 44px rgba(34, 64, 40, 0.16);
        }

        .dish-image, .order-image {
            height: 200px;
            overflow: hidden;
        }

        .dish-image img, .order-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .dish-card:hover .dish-image img, .order-card:hover .order-image img {
            transform: scale(1.1);
        }

        .dish-content, .order-content, .feedback-content {
            padding: 20px;
        }

        .dish-content h3, .order-content h3, .feedback-content h3 {
            font-size: 1.5em;
            color: #2d3436;
            margin-bottom: 10px;
        }

        .dish-content p, .order-content p, .feedback-content p {
            color: #636e72;
            margin-bottom: 8px;
        }

        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: end;
            gap: 18px;
            margin-bottom: 22px;
        }

        .panel-title h3 {
            color: var(--green-700);
            font-size: 1.5em;
            margin-bottom: 6px;
        }

        .panel-title p {
            color: var(--ink-700);
        }

        .empty-state {
            padding: 44px 20px;
            text-align: center;
            border-radius: 20px;
            color: var(--ink-700);
            background: rgba(241, 248, 233, 0.72);
            border: 1px dashed rgba(102, 187, 106, 0.6);
        }

        .dish-badge,
        .status-badge {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            padding: 7px 12px;
            border-radius: 999px;
            font-size: 0.82em;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .dish-badge {
            background: rgba(46, 125, 50, 0.1);
            color: var(--green-700);
        }

        .status-badge {
            background: rgba(102, 187, 106, 0.18);
            color: var(--green-700);
        }

        .status-badge.off {
            background: rgba(220, 53, 69, 0.14);
            color: #b02a37;
        }

        .dish-meta-grid,
        .order-meta-grid,
        .feedback-meta-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-top: 14px;
        }

        .meta-pill {
            padding: 10px 12px;
            border-radius: 14px;
            background: rgba(241, 248, 233, 0.9);
            color: var(--ink-900);
            font-size: 0.92em;
        }

        .order-items {
            display: grid;
            gap: 8px;
            margin-top: 14px;
        }

        .order-item-pill {
            padding: 10px 12px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.78);
            border: 1px solid rgba(165, 214, 167, 0.68);
            color: var(--ink-900);
            font-size: 0.92em;
        }

        .feedback-rating {
            display: flex;
            gap: 2px;
            margin: 12px 0;
            font-size: 1.2em;
            color: #f4b400;
        }

        .feedback-comment {
            padding: 14px;
            border-radius: 16px;
            background: rgba(241, 248, 233, 0.75);
            color: var(--ink-700);
            margin-bottom: 14px;
        }

        .customer-snippet {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.76);
            border: 1px solid rgba(165, 214, 167, 0.6);
            margin-bottom: 14px;
        }

        .customer-avatar {
            width: 54px;
            height: 54px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(102, 187, 106, 0.38);
            box-shadow: 0 10px 18px rgba(34, 64, 40, 0.14);
            flex: 0 0 54px;
        }

        .customer-copy {
            min-width: 0;
        }

        .customer-copy strong,
        .customer-copy span {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .customer-copy strong {
            color: var(--ink-900);
            font-size: 1em;
        }

        .customer-copy span {
            color: var(--ink-700);
            font-size: 0.88em;
        }

        .order-card .order-content,
        .feedback-card .feedback-content {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .feedback-card {
            background:
                linear-gradient(160deg, rgba(255, 255, 255, 0.96), rgba(241, 248, 233, 0.92));
        }

        .feedback-card::before {
            content: '';
            position: absolute;
            inset: 0 0 auto 0;
            height: 5px;
            background: linear-gradient(90deg, #2e7d32, #a5d6a7, #66bb6a);
        }

        .feedback-card:hover {
            transform: translateY(-10px) scale(1.01);
        }

        .feedback-card .feedback-content {
            position: relative;
        }

        .feedback-card .feedback-content::after {
            content: '';
            position: absolute;
            right: 18px;
            top: 18px;
            width: 72px;
            height: 72px;
            border-radius: 20px;
            background: radial-gradient(circle, rgba(102, 187, 106, 0.22), rgba(102, 187, 106, 0));
            pointer-events: none;
        }

        .feedback-stars {
            display: flex;
            gap: 6px;
            margin-top: -2px;
        }

        .feedback-star {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 193, 7, 0.14);
            color: rgba(244, 180, 0, 0.45);
            font-size: 0.92em;
            font-weight: 700;
        }

        .feedback-star.filled {
            background: rgba(244, 180, 0, 0.16);
            color: #f4b400;
            box-shadow: 0 8px 14px rgba(244, 180, 0, 0.18);
        }

        .feedback-topline {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
        }

        .feedback-order-label {
            display: inline-flex;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(46, 125, 50, 0.12);
            color: var(--green-700);
            font-size: 0.82em;
            font-weight: 700;
        }

        .feedback-card .meta-pill,
        .order-card .meta-pill {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .feedback-card .meta-pill strong,
        .order-card .meta-pill strong {
            color: var(--green-700);
            font-size: 0.8em;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .dish-actions, .order-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .dish-controls {
            display: grid;
            gap: 12px;
            margin-top: 16px;
        }

        .dish-controls-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .dish-controls-row select,
        .dish-controls-row input {
            flex: 1;
            min-width: 130px;
            padding: 11px 12px;
            border: 1px solid rgba(165, 214, 167, 0.9);
            border-radius: 12px;
            background: rgba(241, 248, 233, 0.9);
        }

        .modal {
            display: none;
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            padding: 24px;
            background:
                radial-gradient(circle at top right, rgba(102, 187, 106, 0.2), transparent 30%),
                radial-gradient(circle at bottom left, rgba(46, 125, 50, 0.2), transparent 28%),
                rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(12px);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            position: relative;
            width: min(920px, 100%);
            max-height: min(88vh, 920px);
            padding: 0;
            overflow-y: auto;
            border-radius: 28px;
            background:
                linear-gradient(135deg, rgba(255, 255, 255, 0.92), rgba(241, 248, 233, 0.88)),
                url('https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=1200&q=80') center/cover;
            border: 1px solid rgba(165, 214, 167, 0.9);
            box-shadow: 0 28px 70px rgba(0, 0, 0, 0.3);
            animation: modalFloatIn 0.45s ease;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .modal-content::-webkit-scrollbar {
            display: none;
        }

        @keyframes modalFloatIn {
            from {
                opacity: 0;
                transform: translateY(28px) scale(0.96);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-shell {
            display: grid;
            grid-template-columns: 0.95fr 1.2fr;
            min-height: 680px;
        }

        .modal-visual {
            position: relative;
            padding: 36px 28px;
            color: #fff;
            background:
                linear-gradient(180deg, rgba(46, 125, 50, 0.25), rgba(22, 58, 24, 0.72)),
                linear-gradient(135deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.02));
            overflow: hidden;
        }

        .modal-visual::before,
        .modal-visual::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: floatOrb 9s ease-in-out infinite;
        }

        .modal-visual::before {
            width: 220px;
            height: 220px;
            top: -60px;
            right: -70px;
        }

        .modal-visual::after {
            width: 160px;
            height: 160px;
            bottom: 18px;
            left: -45px;
            animation-delay: -3s;
        }

        @keyframes floatOrb {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(14px); }
        }

        .modal-visual-content {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
        }

        .modal-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            width: fit-content;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.16);
            border: 1px solid rgba(255, 255, 255, 0.2);
            font-size: 0.85em;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            backdrop-filter: blur(8px);
        }

        .modal-visual h3 {
            margin: 18px 0 14px;
            font-size: 2.2em;
            line-height: 1.1;
            color: #fff;
        }

        .modal-visual p {
            max-width: 280px;
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.98em;
        }

        .food-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 26px;
        }

        .food-badges span {
            padding: 10px 14px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(6px);
            animation: badgeDrift 6s ease-in-out infinite;
        }

        .food-badges span:nth-child(2) {
            animation-delay: -2s;
        }

        .food-badges span:nth-child(3) {
            animation-delay: -4s;
        }

        @keyframes badgeDrift {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-6px); }
        }

        .modal-form-panel {
            position: relative;
            padding: 30px;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.86), rgba(255, 255, 255, 0.74));
            backdrop-filter: blur(14px);
        }

        .modal-form-header {
            margin-bottom: 22px;
        }

        .modal-form-header h3 {
            color: #2e7d32;
            margin-bottom: 6px;
            text-align: left;
            font-size: 1.85em;
        }

        .modal-form-header p {
            color: #5f6f60;
            font-size: 0.96em;
        }

        .dish-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-group label {
            display: block;
            color: #2e7d32;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.94em;
        }

        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid rgba(165, 214, 167, 0.95);
            border-radius: 14px;
            font-size: 0.95em;
            background: rgba(241, 248, 233, 0.8);
            box-sizing: border-box;
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease, background 0.25s ease;
        }

        .form-group select {
            background: rgba(241, 248, 233, 0.8) url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12"><path d="M6 9l-4-4h8z" fill="%232e7d32"/></svg>') no-repeat right 14px center;
            appearance: none;
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-group input[type="file"] {
            padding: 12px;
            cursor: pointer;
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #78907a;
        }

        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: #388e3c;
            box-shadow: 0 0 0 4px rgba(56, 142, 60, 0.12);
            background: rgba(255, 255, 255, 0.95);
            transform: translateY(-1px);
            outline: none;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 24px;
            padding-top: 18px;
            border-top: 1px solid rgba(165, 214, 167, 0.9);
            position: sticky;
            bottom: 0;
            background: linear-gradient(180deg, rgba(255,255,255,0), rgba(255,255,255,0.82) 26%, rgba(255,255,255,0.96));
            backdrop-filter: blur(10px);
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination button {
            padding: 8px 16px;
            border: 1px solid #a5d6a7;
            border-radius: 8px;
            background: #f1f8e9;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .pagination button:hover {
            background: #a5d6a7;
            color: #fff;
        }

        .pagination button:disabled {
            background: #e0e0e0;
            cursor: not-allowed;
        }

        footer {
            background: linear-gradient(90deg, #2e7d32, #66bb6a);
            color: #fff;
            padding: 20px;
            text-align: center;
            margin-top: 40px;
        }

        @media (max-width: 768px) {
            .dashboard-hero,
            .summary-grid {
                grid-template-columns: 1fr;
            }

            .filter-container {
                flex-direction: column;
            }

            .dish-grid, .order-grid, .feedback-grid {
                grid-template-columns: 1fr;
            }

            .modal-content {
                width: 100%;
                max-height: 92vh;
            }

            .modal-shell {
                grid-template-columns: 1fr;
                min-height: auto;
            }

            .modal-visual {
                min-height: 220px;
                padding: 24px 22px;
            }

            .modal-form-panel {
                padding: 22px 18px;
            }

            .dish-form-grid {
                grid-template-columns: 1fr;
            }

            .tabs {
                flex-direction: column;
            }

            .tab-button {
                width: 100%;
                text-align: center;
            }

            .panel-header {
                flex-direction: column;
                align-items: stretch;
            }

            .dish-meta-grid,
            .order-meta-grid,
            .feedback-meta-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo">
                <a href="../index.php"><img src="../assets/images/logo.png" alt="Homemade Food Delivery"></a>
            </div>
            <div class="user">
                <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile Picture" onerror="this.src='../assets/images/default-profile.jpg';">
                <div>
                    <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Chef'); ?></strong>
                    <span><?php echo htmlspecialchars($_SESSION['user_location'] ?? ''); ?></span>
                </div>
                <a href="../logout.php" class="btn btn-dark">Log Out</a>
            </div>
        </div>
    </header>

    <div class="main-container">
        <div class="section-title">
            <h2>Menu Management</h2>
        </div>

        <section class="dashboard-hero">
            <div class="hero-copy">
                <span class="hero-tag">Bangla Kitchen Studio</span>
                <h1>Bring your homemade menu to life with warmth, craft, and flavor.</h1>
                <p>Manage dishes, confirmed orders, and guest feedback from one polished chef workspace inspired by the comfort of Bengali home cooking and the energy of a busy kitchen.</p>
                <div class="hero-badges">
                    <span>Shobji to Shorshe</span>
                    <span>Freshly Cooked</span>
                    <span>Chef's Signature Menu</span>
                </div>
            </div>
            <div class="hero-side">
                <div class="hero-panel">
                    <div>
                        <h3>Today's Kitchen Mood</h3>
                        <p>Keep your offerings clear, visual, and ready for hungry customers looking for authentic homemade flavor.</p>
                    </div>
                    <div class="hero-panel-list">
                        <div class="hero-panel-item">Curate dishes with rich details and balanced presentation.</div>
                        <div class="hero-panel-item">Track confirmed orders and stay organized through every rush.</div>
                        <div class="hero-panel-item">Read customer feedback and sharpen your next signature plate.</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="summary-grid">
            <div class="summary-card">
                <div class="summary-label">Total Dishes</div>
                <div class="summary-value" id="summaryTotalDishes"><?php echo $chefStats['total_dishes']; ?></div>
                <div class="summary-note">Items currently listed on your menu.</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Available Now</div>
                <div class="summary-value" id="summaryAvailableDishes"><?php echo $chefStats['available_dishes']; ?></div>
                <div class="summary-note">Dishes customers can order right now.</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Unavailable</div>
                <div class="summary-value" id="summaryUnavailableDishes"><?php echo $chefStats['unavailable_dishes']; ?></div>
                <div class="summary-note">Paused or sold-out dishes waiting to return.</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Confirmed Orders</div>
                <div class="summary-value" id="summaryConfirmedOrders"><?php echo $chefStats['confirmed_orders']; ?></div>
                <div class="summary-note">Completed order confirmations from your kitchen.</div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Average Rating</div>
                <div class="summary-value" id="summaryAvgRating"><?php echo number_format($chefStats['avg_rating'], 1); ?></div>
                <div class="summary-note">Guest feedback across confirmed experiences.</div>
            </div>
        </section>

        <div class="tabs">
            <button class="tab-button active" onclick="openTab('dishes')">Dishes</button>
            <button class="tab-button" onclick="openTab('orders')">Order History</button>
            <button class="tab-button" onclick="openTab('feedback')">Feedback</button>
        </div>

        <!-- Dishes Tab -->
        <div id="dishes" class="tab-content active">
            <div class="panel-header">
                <div class="panel-title">
                    <h3>Menu Curation</h3>
                    <p>Search, filter, and refine every dish to keep your kitchen menu looking premium.</p>
                </div>
                <div class="dish-status-switcher">
                    <button type="button" class="status-filter-btn active" data-status="available" onclick="setDishStatusFilter('available')">Available</button>
                    <button type="button" class="status-filter-btn" data-status="unavailable" onclick="setDishStatusFilter('unavailable')">Unavailable</button>
                </div>
            </div>
            <div class="filter-container">
                <input type="text" id="searchInput" placeholder="Search dishes...">
                <select id="categoryFilter">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $key => $label): ?>
                        <option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn" onclick="openModal()">Add New Dish</button>
            </div>

            <div class="dish-grid" id="dishGrid"></div>

            <div class="pagination" id="pagination"></div>
        </div>

        <!-- Orders Tab -->
        <div id="orders" class="tab-content">
            <div class="panel-header">
                <div class="panel-title">
                    <h3>Confirmed Orders</h3>
                    <p>Review what customers ordered and keep delivery details easy to scan.</p>
                </div>
            </div>
            <div class="order-grid" id="orderGrid"></div>
        </div>

        <!-- Feedback Tab -->
        <div id="feedback" class="tab-content">
            <div class="panel-header">
                <div class="panel-title">
                    <h3>Customer Feedback</h3>
                    <p>Listen to guest reactions and improve the next plate with confidence.</p>
                </div>
            </div>
            <div class="feedback-grid" id="feedbackGrid"></div>
        </div>
    </div>

    <!-- Modal for Add/Edit Dish -->
    <div id="dishModal" class="modal">
        <div class="modal-content">
            <div class="modal-shell">
                <div class="modal-visual">
                    <div class="modal-visual-content">
                        <div>
                            <span class="modal-kicker">Fresh Kitchen</span>
                            <h3>Craft a menu item that feels as good as it tastes.</h3>
                            <p>Present your dish with a clean setup, clear details, and a polished chef experience that matches the rest of the dashboard.</p>
                        </div>
                        <div class="food-badges">
                            <span>Seasonal Flavor</span>
                            <span>Chef Crafted</span>
                            <span>Ready To Serve</span>
                        </div>
                    </div>
                </div>
                <div class="modal-form-panel">
                    <div class="modal-form-header">
                        <h3 id="modalTitle">Add New Dish</h3>
                        <p>Fill in the essentials and keep your menu looking professional.</p>
                    </div>
                    <form id="dishForm" enctype="multipart/form-data">
                <input type="hidden" name="id" id="dishId">
                <input type="hidden" name="action" value="save">
                <div class="dish-form-grid">
                <div class="form-group full-width">
                    <label for="name">Dish Name</label>
                    <input type="text" name="name" id="name" placeholder="Enter your signature dish name" required>
                </div>
                <div class="form-group full-width">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" placeholder="Describe the taste, ingredients, and what makes it special"></textarea>
                </div>
                <div class="form-group">
                    <label for="price">Price (৳)</label>
                    <input type="number" step="0.01" name="price" id="price" required>
                </div>
                <div class="form-group">
                    <label for="quantity">Quantity</label>
                    <input type="number" name="quantity" id="quantity" placeholder="Available portions" required>
                </div>
                <div class="form-group">
                    <label for="category">Category</label>
                    <select name="category" id="category" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $key => $label): ?>
                            <option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="preparation_time">Preparation Time (minutes)</label>
                    <input type="number" name="preparation_time" id="preparation_time" placeholder="e.g. 25" required>
                </div>
                <div class="form-group">
                    <label for="is_available">Availability</label>
                    <select name="is_available" id="is_available">
                        <option value="1">Available</option>
                        <option value="0">Not Available</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="image">Dish Image</label>
                    <input type="file" name="image" id="image" accept="image/*">
                </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-dark" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn">Save</button>
                </div>
            </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentPage = 1;
        let currentDishStatus = 'available';

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function openTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            document.getElementById(tabName).classList.add('active');
            document.querySelector(`.tab-button[onclick="openTab('${tabName}')"]`).classList.add('active');

            if (tabName === 'dishes') {
                loadDishes();
            } else if (tabName === 'orders') {
                loadOrders();
            } else if (tabName === 'feedback') {
                loadFeedback();
            }
        }

        function setDishStatusFilter(status) {
            currentDishStatus = status;
            currentPage = 1;
            document.querySelectorAll('.status-filter-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.status === status);
            });
            loadDishes();
        }

        function loadSummary() {
            fetch('menu-management.php?action=summary')
                .then(res => res.json())
                .then(data => {
                    if (data.status !== 'success') {
                        return;
                    }
                    document.getElementById('summaryTotalDishes').textContent = data.summary.total_dishes ?? 0;
                    document.getElementById('summaryAvailableDishes').textContent = data.summary.available_dishes ?? 0;
                    document.getElementById('summaryUnavailableDishes').textContent = data.summary.unavailable_dishes ?? 0;
                    document.getElementById('summaryConfirmedOrders').textContent = data.summary.confirmed_orders ?? 0;
                    document.getElementById('summaryAvgRating').textContent = Number(data.summary.avg_rating ?? 0).toFixed(1);
                })
                .catch(err => console.error('Error loading summary:', err));
        }

        function loadDishes() {
            const search = document.getElementById('searchInput').value;
            const category = document.getElementById('categoryFilter').value;
            fetch(`menu-management.php?action=list&page=${currentPage}&search=${encodeURIComponent(search)}&category=${encodeURIComponent(category)}&status=${encodeURIComponent(currentDishStatus)}`)
                .then(res => res.json())
                .then(data => {
                    const dishGrid = document.getElementById('dishGrid');
                    dishGrid.innerHTML = '';
                    if (data.error) {
                        dishGrid.innerHTML = `<div class="empty-state" style="color:#b02a37;">${data.error}</div>`;
                        return;
                    }
                    if (data.dishes.length === 0) {
                        dishGrid.innerHTML = '<div class="empty-state">No dishes found. Add a new signature item to start shaping your menu.</div>';
                        return;
                    }
                    data.dishes.forEach(dish => {
                        const card = document.createElement('div');
                        const categoryLabel = dish.category ? dish.category.replace(/_/g, ' ').replace(/\b\w/g, char => char.toUpperCase()) : 'Chef Special';
                        const statusOption = currentDishStatus === 'unavailable' ? `
                            <div class="dish-controls-row">
                                <input type="number" min="1" value="1" id="increaseQty-${dish.id}" placeholder="Add quantity">
                                <button class="btn" type="button" onclick="increaseDishQuantity(${dish.id})">Restock</button>
                            </div>
                        ` : '';
                        card.className = 'dish-card';
                        card.innerHTML = `
                            <div class="dish-image">
                                <img src="${dish.image ? '../uploads/dishes/' + encodeURIComponent(dish.image).replace(/%2F/g, '/') : '../assets/images/placeholder.jpg'}" alt="${dish.name}">
                            </div>
                            <div class="dish-content">
                                <h3>${dish.name}</h3>
                                <span class="dish-badge">${categoryLabel}</span>
                                <p>${dish.description || 'A comforting homemade preparation ready for your guests.'}</p>
                                <p>Price: ৳${parseFloat(dish.price).toFixed(2)}</p>
                                <p>Quantity: ${dish.quantity}</p>
                                <p>Prep Time: ${dish.preparation_time} mins</p>
                                <p>Status: ${dish.is_available && Number(dish.quantity) > 0 ? 'Available' : 'Unavailable'}</p>
                                <div class="dish-controls">
                                    <div class="dish-controls-row">
                                        <select onchange="updateDishAvailability(${dish.id}, this.value)">
                                            <option value="1" ${dish.is_available && Number(dish.quantity) > 0 ? 'selected' : ''}>Available</option>
                                            <option value="0" ${!dish.is_available || Number(dish.quantity) <= 0 ? 'selected' : ''}>Unavailable</option>
                                        </select>
                                    </div>
                                    ${statusOption}
                                </div>
                                <div class="dish-actions">
                                    <button class="btn" onclick="editDish(${dish.id})">Edit</button>
                                    <button class="btn btn-dark" onclick="deleteDish(${dish.id})">Delete</button>
                                </div>
                            </div>
                        `;
                        dishGrid.appendChild(card);
                    });

                    // Update pagination
                    const pagination = document.getElementById('pagination');
                    pagination.innerHTML = '';
                    if (data.totalPages > 1) {
                        const prev = document.createElement('button');
                        prev.textContent = 'Previous';
                        prev.disabled = currentPage === 1;
                        prev.onclick = () => { if (currentPage > 1) { currentPage--; loadDishes(); } };
                        pagination.appendChild(prev);

                        for (let i = 1; i <= data.totalPages; i++) {
                            const pageBtn = document.createElement('button');
                            pageBtn.textContent = i;
                            pageBtn.disabled = i === currentPage;
                            pageBtn.onclick = () => { currentPage = i; loadDishes(); };
                            pagination.appendChild(pageBtn);
                        }

                        const next = document.createElement('button');
                        next.textContent = 'Next';
                        next.disabled = currentPage === data.totalPages;
                        next.onclick = () => { if (currentPage < data.totalPages) { currentPage++; loadDishes(); } };
                        pagination.appendChild(next);
                    }
                })
                .catch(err => {
                    console.error('Error loading dishes:', err);
                    document.getElementById('dishGrid').innerHTML = '<div class="empty-state" style="color:#b02a37;">Failed to load dishes.</div>';
                });
        }

        function updateDishAvailability(id, value) {
            const body = new URLSearchParams({ action: 'update_availability', id, is_available: value });
            fetch('menu-management.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString()
            })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.status === 'success') {
                        loadDishes();
                        loadSummary();
                    }
                })
                .catch(err => {
                    console.error('Error updating availability:', err);
                    alert('Failed to update availability.');
                });
        }

        function increaseDishQuantity(id) {
            const input = document.getElementById(`increaseQty-${id}`);
            const quantityIncrement = parseInt(input?.value || '0', 10);
            if (!quantityIncrement || quantityIncrement <= 0) {
                alert('Enter a valid quantity to add.');
                return;
            }

            const body = new URLSearchParams({ action: 'increase_quantity', id, quantity_increment: quantityIncrement });
            fetch('menu-management.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString()
            })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.status === 'success') {
                        setDishStatusFilter('available');
                        loadSummary();
                    }
                })
                .catch(err => {
                    console.error('Error increasing quantity:', err);
                    alert('Failed to increase quantity.');
                });
        }

        function loadOrders() {
            fetch('menu-management.php?action=order_history')
                .then(res => res.json())
                .then(data => {
                    const orderGrid = document.getElementById('orderGrid');
                    orderGrid.innerHTML = '';
                    if (data.error) {
                        orderGrid.innerHTML = `<p style="color: #dc3545; text-align: center;">${data.error}</p>`;
                        return;
                    }
                    if (data.orders.length === 0) {
                        orderGrid.innerHTML = '<p style="color: #2e7d32; text-align: center;">No orders found.</p>';
                        return;
                    }
                    const ordersById = {};
                    data.orders.forEach(order => {
                        if (!ordersById[order.id]) {
                            ordersById[order.id] = {
                                ...order,
                                items: []
                            };
                        }
                        ordersById[order.id].items.push({
                            food_name: order.food_name,
                            quantity: order.quantity,
                            price: order.price,
                            food_image: order.food_image
                        });
                    });
                    Object.values(ordersById).forEach(order => {
                        const card = document.createElement('div');
                        card.className = 'order-card';
                        const itemsHtml = order.items.map(item => `
                            <p>${item.food_name} x${item.quantity} - ৳${parseFloat(item.price).toFixed(2)}</p>
                        `).join('');
                        card.innerHTML = `
                            <div class="order-image">
                                <img src="${order.items[0].food_image ? '../uploads/dishes/' + encodeURIComponent(order.items[0].food_image).replace(/%2F/g, '/') : '../assets/images/placeholder.jpg'}" alt="Order Item">
                            </div>
                            <div class="order-content">
                                <h3>Order #${order.id}</h3>
                                <p>Customer: ${order.customer_name}</p>
                                <p>Total: ৳${parseFloat(order.total_amount).toFixed(2)}</p>
                                <p>Address: ${order.delivery_address}</p>
                                <p>Payment: ${order.payment_method} (${order.transaction_id})</p>
                                <p>Date: ${new Date(order.created_at).toLocaleString()}</p>
                                <div>Items: ${itemsHtml}</div>
                            </div>
                        `;
                        card.innerHTML = `
                            <div class="order-image">
                                <img src="${order.items[0].food_image ? '../uploads/dishes/' + encodeURIComponent(order.items[0].food_image).replace(/%2F/g, '/') : '../assets/images/placeholder.jpg'}" alt="Order Item">
                            </div>
                            <div class="order-content">
                                <h3>Order #${order.id}</h3>
                                <div class="customer-snippet">
                                    <img class="customer-avatar" src="${order.customer_image_url || '../assets/images/default-profile.jpg'}" alt="${escapeHtml(order.customer_name)}">
                                    <div class="customer-copy">
                                        <strong>${escapeHtml(order.customer_name)}</strong>
                                        <span>Confirmed customer order</span>
                                    </div>
                                </div>
                                <div class="order-meta-grid">
                                    <div class="meta-pill"><strong>Total</strong><span>&#2547;${parseFloat(order.total_amount).toFixed(2)}</span></div>
                                    <div class="meta-pill"><strong>Payment</strong><span>${escapeHtml(order.payment_method)} (${escapeHtml(order.transaction_id)})</span></div>
                                    <div class="meta-pill"><strong>Address</strong><span>${escapeHtml(order.delivery_address)}</span></div>
                                    <div class="meta-pill"><strong>Date</strong><span>${escapeHtml(new Date(order.created_at).toLocaleString())}</span></div>
                                </div>
                                <div class="order-items">${itemsHtml}</div>
                            </div>
                        `;
                        orderGrid.appendChild(card);
                    });
                })
                .catch(err => {
                    console.error('Error loading orders:', err);
                    document.getElementById('orderGrid').innerHTML = '<p style="color: #dc3545; text-align: center;">Failed to load orders.</p>';
                });
        }

        function loadFeedback() {
            fetch('menu-management.php?action=fetch_feedback')
                .then(res => res.json())
                .then(data => {
                    const feedbackGrid = document.getElementById('feedbackGrid');
                    feedbackGrid.innerHTML = '';
                    if (data.status === 'error') {
                        feedbackGrid.innerHTML = `<p style="color: #dc3545; text-align: center;">${data.message}</p>`;
                        return;
                    }
                    if (data.feedback.length === 0) {
                        feedbackGrid.innerHTML = '<p style="color: #2e7d32; text-align: center;">No feedback found.</p>';
                        return;
                    }
                    data.feedback.forEach(fb => {
                        const card = document.createElement('div');
                        card.className = 'feedback-card';
                        card.innerHTML = `
                            <div class="feedback-content">
                                <h3>Order #${fb.order_id}</h3>
                                <p>Customer: ${fb.customer_name}</p>
                                <p>Rating: ${'★'.repeat(fb.rating)}${'☆'.repeat(5 - fb.rating)}</p>
                                <p>Comment: ${fb.comment || 'No comment'}</p>
                                <p>Items: ${fb.item_names}</p>
                                <p>Date: ${new Date(fb.created_at).toLocaleString()}</p>
                            </div>
                        `;
                        const stars = Array.from({ length: 5 }, (_, index) =>
                            `<span class="feedback-star ${index < Number(fb.rating) ? 'filled' : ''}">&#9733;</span>`
                        ).join('');
                        card.innerHTML = `
                            <div class="feedback-content">
                                <div class="feedback-topline">
                                    <h3>Customer Feedback</h3>
                                    <span class="feedback-order-label">Order #${fb.order_id}</span>
                                </div>
                                <div class="customer-snippet">
                                    <img class="customer-avatar" src="${fb.customer_image_url || '../assets/images/default-profile.jpg'}" alt="${escapeHtml(fb.customer_name)}">
                                    <div class="customer-copy">
                                        <strong>${escapeHtml(fb.customer_name)}</strong>
                                        <span>${escapeHtml(new Date(fb.created_at).toLocaleString())}</span>
                                    </div>
                                </div>
                                <div class="feedback-stars">${stars}</div>
                                <div class="feedback-comment">${escapeHtml(fb.comment || 'No comment shared yet.')}</div>
                                <div class="feedback-meta-grid">
                                    <div class="meta-pill"><strong>Items</strong><span>${escapeHtml(fb.item_names || 'N/A')}</span></div>
                                    <div class="meta-pill"><strong>Rating</strong><span>${escapeHtml(String(fb.rating))}/5</span></div>
                                </div>
                            </div>
                        `;
                        feedbackGrid.appendChild(card);
                    });
                })
                .catch(err => {
                    console.error('Error loading feedback:', err);
                    document.getElementById('feedbackGrid').innerHTML = '<p style="color: #dc3545; text-align: center;">Failed to load feedback.</p>';
                });
        }

        function openModal() {
            document.getElementById('dishModal').classList.add('show');
            document.getElementById('modalTitle').textContent = 'Add New Dish';
            document.getElementById('dishForm').reset();
            document.getElementById('dishId').value = '';
        }

        function closeModal() {
            document.getElementById('dishModal').classList.remove('show');
        }

        function editDish(id) {
            fetch(`menu-management.php?id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }
                    document.getElementById('dishId').value = data.id;
                    document.getElementById('name').value = data.name;
                    document.getElementById('description').value = data.description || '';
                    document.getElementById('price').value = data.price;
                    document.getElementById('quantity').value = data.quantity;
                    document.getElementById('category').value = data.category;
                    document.getElementById('preparation_time').value = data.preparation_time;
                    document.getElementById('is_available').value = data.is_available;
                    document.getElementById('modalTitle').textContent = 'Edit Dish';
                    document.getElementById('dishModal').classList.add('show');
                })
                .catch(err => {
                    console.error('Error fetching dish:', err);
                    alert('Failed to load dish data.');
                });
        }

        function deleteDish(id) {
            if (confirm('Are you sure you want to delete this dish?')) {
                fetch('menu-management.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=delete&id=${id}`
                })
                    .then(res => res.json())
                    .then(data => {
                        alert(data.message);
                        if (data.status === 'success') {
                            loadDishes();
                            loadSummary();
                        }
                    })
                    .catch(err => {
                        console.error('Error deleting dish:', err);
                        alert('Failed to delete dish.');
                    });
            }
        }

        document.getElementById('dishForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('menu-management.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.status === 'success') {
                        closeModal();
                        loadDishes();
                        loadSummary();
                    }
                })
                .catch(err => {
                    console.error('Error saving dish:', err);
                    alert('Failed to save dish.');
                });
        });

        document.getElementById('searchInput').addEventListener('input', () => {
            currentPage = 1;
            loadDishes();
        });

        document.getElementById('categoryFilter').addEventListener('change', () => {
            currentPage = 1;
            loadDishes();
        });

        // Initial load
        loadSummary();
        loadDishes();
    </script>
<?php
$footerBasePath = '..';
$footerTheme = 'green';
require '../includes/footer.php';
?>

