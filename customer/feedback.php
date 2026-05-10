<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Ensure JSON response
header('Content-Type: application/json');

// Submit feedback
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_feedback') {
    if (!isLoggedIn() || !isUserType('customer')) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    $orderId = (int)($_POST['order_id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 0);
    $comment = sanitizeInput($_POST['comment'] ?? '');
    $customerId = $_SESSION['user_id'];

    // Validate inputs
    if ($orderId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid order ID']);
        exit;
    }
    if ($rating < 1 || $rating > 5) {
        echo json_encode(['status' => 'error', 'message' => 'Rating must be between 1 and 5']);
        exit;
    }

    try {
        // Verify order exists, is confirmed, and belongs to customer
        $order = $db->selectOne(
            "SELECT o.id, oi.food_item_id, fi.chef_id 
             FROM orders o 
             JOIN order_items oi ON o.id = oi.order_id 
             JOIN food_items fi ON oi.food_item_id = fi.id 
             WHERE o.id = ? AND o.customer_id = ? AND o.status = 'confirmed'",
            [$orderId, $customerId]
        );
        if (!$order) {
            echo json_encode(['status' => 'error', 'message' => 'Order not found or not confirmed']);
            exit;
        }

        // Check if feedback already exists
        $existingFeedback = $db->selectOne(
            "SELECT id FROM feedback WHERE order_id = ? AND customer_id = ?",
            [$orderId, $customerId]
        );
        if ($existingFeedback) {
            echo json_encode(['status' => 'error', 'message' => 'Feedback already submitted for this order']);
            exit;
        }

        // Insert feedback
        $db->insert(
            "INSERT INTO feedback (order_id, customer_id, chef_id, rating, comment) VALUES (?, ?, ?, ?, ?)",
            [$orderId, $customerId, $order['chef_id'], $rating, $comment]
        );

        echo json_encode(['status' => 'success', 'message' => 'Feedback submitted successfully']);
    } catch (Exception $e) {
        error_log("Error submitting feedback: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Server error submitting feedback']);
    }
    exit;
}

// Fetch feedback for chef
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'fetch_feedback') {
    if (!isLoggedIn() || !isUserType('chef')) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    $chefId = $_SESSION['user_id'];

    try {
        $feedback = $db->select(
            "SELECT f.id, f.order_id, f.rating, f.comment, f.created_at, 
                    COALESCE(u.name, 'Unknown Customer') AS customer_name, 
                    GROUP_CONCAT(COALESCE(fi.name, 'Unknown Item')) AS item_names
             FROM feedback f
             LEFT JOIN users u ON f.customer_id = u.id
             LEFT JOIN order_items oi ON f.order_id = oi.order_id
             LEFT JOIN food_items fi ON oi.food_item_id = fi.id
             WHERE f.chef_id = ?
             GROUP BY f.id
             ORDER BY f.created_at DESC",
            [$chefId]
        );

        if ($feedback === false) {
            $feedback = [];
        }

        echo json_encode(['status' => 'success', 'feedback' => $feedback]);
    } catch (Exception $e) {
        error_log("Error fetching feedback: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Server error fetching feedback']);
    }
    exit;
}

// Check if feedback exists for an order
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'check_feedback') {
    if (!isLoggedIn() || !isUserType('customer')) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    $orderId = (int)($_GET['order_id'] ?? 0);
    $customerId = $_SESSION['user_id'];

    if ($orderId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid order ID']);
        exit;
    }

    try {
        $feedback = $db->selectOne(
            "SELECT id FROM feedback WHERE order_id = ? AND customer_id = ?",
            [$orderId, $customerId]
        );

        echo json_encode(['status' => 'success', 'has_feedback' => !!$feedback]);
    } catch (Exception $e) {
        error_log("Error checking feedback status: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Server error checking feedback status']);
    }
    exit;
}
?>