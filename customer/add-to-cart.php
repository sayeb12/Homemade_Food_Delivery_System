<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    http_response_code(403);
    echo 'Unauthorized';
    exit;
}

$customerId = $_SESSION['user_id'];
$foodId = $_POST['food_id'] ?? null;
$chefId = $_POST['chef_id'] ?? null;

if ($foodId && $chefId && $customerId) {
    // Check if item already exists in cart
    $existing = $db->selectOne("SELECT id FROM cart WHERE customer_id = ? AND food_id = ?", [$customerId, $foodId]);

    if ($existing) {
        // Update quantity
        $db->update("UPDATE cart SET quantity = quantity + 1 WHERE customer_id = ? AND food_id = ?", [$customerId, $foodId]);
    } else {
        // Insert new row
        $db->insert("INSERT INTO cart (customer_id, food_id, chef_id, quantity) VALUES (?, ?, ?, 1)", [$customerId, $foodId, $chefId]);
    }

    echo 'Item added to cart';
} else {
    echo 'Missing required data';
}
?>
