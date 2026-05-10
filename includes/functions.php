<?php
require_once 'db.php';
require_once dirname(__DIR__) . '/mailer.php';

function generateVerificationCode($length = 6) {
    return str_pad(mt_rand(0, 999999), $length, '0', STR_PAD_LEFT);
}

function uploadImage($file, $uploadDir, $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], $maxSize = 5 * 1024 * 1024) {
    if ($file['error'] !== 0) {
        return ['success' => false, 'message' => 'File upload error'];
    }

    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => 'Only JPG, PNG, GIF, and WEBP images are allowed'];
    }

    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'Image size should not exceed 5MB'];
    }

    // Verify image content
    if (!getimagesize($file['tmp_name'])) {
        return ['success' => false, 'message' => 'Invalid image file'];
    }

    $fileName = uniqid() . '_' . basename($file['name']);
    $uploadPath = $uploadDir . $fileName;

    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return ['success' => true, 'fileName' => $fileName];
    } else {
        return ['success' => false, 'message' => 'Failed to upload image'];
    }
}

function getUploadImageUrl($fileName, $type, $basePrefix = '..', $fallback = null) {
    $type = trim((string) $type, "/\\");
    $fileName = trim((string) $fileName);
    $normalizedPrefix = rtrim((string) $basePrefix, '/');

    if ($fallback === null) {
        $fallback = $normalizedPrefix . '/assets/images/placeholder.jpg';
    }

    if ($fileName === '' || $type === '') {
        return $fallback;
    }

    $absolutePath = dirname(__DIR__) . '/uploads/' . $type . '/' . $fileName;
    if (!file_exists($absolutePath)) {
        return $fallback;
    }

    return $normalizedPrefix . '/uploads/' . $type . '/' . str_replace('%2F', '/', rawurlencode($fileName));
}

function getProfileImageUrl($fileName, $basePrefix = '..', $fallback = null) {
    if ($fallback === null) {
        $fallback = rtrim((string) $basePrefix, '/') . '/assets/images/default-profile.jpg';
    }

    return getUploadImageUrl($fileName, 'profiles', $basePrefix, $fallback);
}

function getDishImageUrl($fileName, $basePrefix = '..', $fallback = null) {
    if ($fallback === null) {
        $fallback = rtrim((string) $basePrefix, '/') . '/assets/images/placeholder.jpg';
    }

    return getUploadImageUrl($fileName, 'dishes', $basePrefix, $fallback);
}

function isStrongPassword($password) {
    if (strlen($password) < 8) {
        return ['valid' => false, 'message' => 'Password must be at least 8 characters long'];
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain at least one uppercase letter'];
    }
    if (!preg_match('/[a-z]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain at least one lowercase letter'];
    }
    if (!preg_match('/[0-9]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain at least one number'];
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain at least one special character'];
    }
    return ['valid' => true, 'message' => 'Password is strong'];
}

function sendVerificationEmail($email, $name, $code) {
    return sendPHPMailerVerificationEmail($email, $name, $code);
}

function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    // Trim input and return empty string if null
    $input = trim($input ?? '');
    if ($input === '') {
        return '';
    }
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && checkSessionTimeout();
}

function isUserType($type) {
    return isLoggedIn() && isset($_SESSION['user_type']) && $_SESSION['user_type'] === $type;
}

function redirectWithMessage($url, $message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    header("Location: $url");
    exit;
}

function displayFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'success';
        echo "<div class='alert alert-$type'>$message</div>";
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
    }
}

function getUserData($userId) {
    global $db;
    return $db->selectOne("SELECT * FROM users WHERE id = ?", [$userId]);
}

function getFoodItemsByChef($chefId) {
    global $db;
    return $db->select("SELECT f.*, u.name as chef_name FROM food_items f JOIN users u ON f.chef_id = u.id WHERE f.chef_id = ? AND f.is_available = 1 AND f.quantity > 0", [$chefId]);
}

function getNearbyFoodItems($limit = 10) {
    global $db;
    return $db->select("SELECT f.*, u.name as chef_name FROM food_items f JOIN users u ON f.chef_id = u.id WHERE f.is_available = 1 AND f.quantity > 0 ORDER BY RAND() LIMIT ?", [$limit]);
}

function getOrderDetails($orderId) {
    global $db;
    $order = $db->selectOne("SELECT o.*, u.name as customer_name FROM orders o JOIN users u ON o.customer_id = u.id WHERE o.id = ?", [$orderId]);
    if ($order) {
        $order['items'] = $db->select("SELECT oi.*, f.name as food_name FROM order_items oi JOIN food_items f ON oi.food_item_id = f.id WHERE oi.order_id = ?", [$orderId]);
    }
    return $order;
}

function getTopRatedFoodItems($limit = 5) {
    global $db;
    return $db->select(
        "SELECT f.*, u.name as chef_name, AVG(r.rating) as avg_rating 
         FROM food_items f 
         JOIN users u ON f.chef_id = u.id 
         LEFT JOIN reviews r ON f.id = r.food_item_id 
         WHERE f.is_available = 1 AND f.quantity > 0
         GROUP BY f.id 
         ORDER BY avg_rating DESC 
         LIMIT ?",
        [$limit]
    );
}

function getCategories() {
    return [
        'veg' => 'Vegetarian',
        'nonveg' => 'Non-Vegetarian',
        'rice' => 'Rice & Polao',
        'biryani' => 'Biryani',
        'curry' => 'Curry',
        'fish' => 'Fish',
        'meat' => 'Meat',
        'bhorta' => 'Bhorta',
        'snacks' => 'Snacks',
        'breakfast' => 'Breakfast',
        'dessert' => 'Dessert',
        'bakery' => 'Bakery',
        'juicy' => 'Juice & Drinks',
        'others' => 'Others'
    ];
}

function formatPrice($price) {
    return CURRENCY_SYMBOL . number_format($price, 2);
}
?>
