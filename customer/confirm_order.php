<?php
session_start();

// Include dependencies
require_once '../includes/db.php';

// Ensure the user is authenticated and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    http_response_code(403);
    echo 'Unauthorized access';
    exit;
}

$customerId = $_SESSION['user_id'];

// Handle POST request from make_payment.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentMethod = $_POST['payment_method'] ?? '';
    $transactionId = $_POST['transaction_id'] ?? '';
    $deliveryAddress = trim($_POST['delivery_address'] ?? '');
    $foodId = $_POST['food_id'] ?? null;
    $quantity = (int)($_POST['quantity'] ?? 1);
    $chefId = $_POST['chef_id'] ?? null; // Optional
    $price = (float)($_POST['price'] ?? 0.0);

    // Validate inputs
    if (!in_array($paymentMethod, ['bkash', 'nagad', 'rocket'])) {
        $_SESSION['payment_error'] = 'Invalid payment method.';
        header('Location: make_payment.php?error=' . urlencode('Invalid payment method.'));
        exit;
    }

    if (empty($transactionId)) {
        $_SESSION['payment_error'] = 'Transaction ID is required for ' . ucfirst($paymentMethod) . '.';
        header('Location: make_payment.php?error=' . urlencode('Transaction ID is required for ' . ucfirst($paymentMethod) . '.'));
        exit;
    }

    if (empty($deliveryAddress)) {
        $_SESSION['payment_error'] = 'Delivery address is required.';
        header('Location: make_payment.php?error=' . urlencode('Delivery address is required.'));
        exit;
    }

    if (!$foodId || $quantity <= 0) {
        $_SESSION['payment_error'] = 'Invalid food item or quantity.';
        header('Location: make_payment.php?error=' . urlencode('Invalid food item or quantity.'));
        exit;
    }

    // Fetch item details if price not provided
    if ($price <= 0) {
        $itemQuery = "SELECT price, quantity, is_available FROM food_items WHERE id = ?";
        $item = $db->selectOne($itemQuery, [$foodId]);
        if (!$item) {
            $_SESSION['payment_error'] = 'Food item not found.';
            header('Location: make_payment.php?error=' . urlencode('Food item not found.'));
            exit;
        }
        if ((int) ($item['is_available'] ?? 0) !== 1 || (int) ($item['quantity'] ?? 0) < $quantity) {
            $_SESSION['payment_error'] = 'This item is no longer available in the requested quantity.';
            header('Location: make_payment.php?error=' . urlencode('This item is no longer available in the requested quantity.'));
            exit;
        }
        $price = $item['price'];
    }

    $totalAmount = $price * $quantity;

    try {
        // Start a database transaction
        $db->begin_transaction();

        try {
            $stockUpdated = $db->update(
                "UPDATE food_items
                 SET quantity = quantity - ?,
                     is_available = CASE WHEN quantity - ? <= 0 THEN 0 ELSE is_available END
                 WHERE id = ? AND is_available = 1 AND quantity >= ?",
                [$quantity, $quantity, $foodId, $quantity]
            );
            if ($stockUpdated === 0) {
                throw new Exception("This item is no longer available in the requested quantity.");
            }

            // Insert order
            $orderInsertQuery = "INSERT INTO orders (customer_id, total_amount, status, delivery_address, created_at, updated_at) 
                                VALUES (?, ?, 'confirmed', ?, NOW(), NOW())";
            $orderId = $db->insert($orderInsertQuery, [$customerId, $totalAmount, $deliveryAddress]);
            if ($orderId === false) {
                throw new Exception("Failed to insert order.");
            }

            // Insert order item
            $orderItemInsertQuery = "INSERT INTO order_items (order_id, food_item_id, quantity, price) 
                                    VALUES (?, ?, ?, ?)";
            $result = $db->insert($orderItemInsertQuery, [$orderId, $foodId, $quantity, $price]);
            if ($result === false) {
                throw new Exception("Failed to insert order item.");
            }

            // Insert payment
            $paymentQuery = "INSERT INTO payments (order_id, payment_method, transaction_id, created_at) 
                            VALUES (?, ?, ?, NOW())";
            $result = $db->insert($paymentQuery, [$orderId, $paymentMethod, $transactionId]);
            if ($result === false) {
                throw new Exception("Failed to insert payment.");
            }

            // Remove the item from the cart
            $db->delete("DELETE FROM cart WHERE customer_id = ? AND food_id = ?", [$customerId, $foodId]);

            // Commit transaction
            $db->commit();

            // Set success message
            $_SESSION['order_success'] = 'Your ' . ucfirst($paymentMethod) . ' order has been placed successfully.';
            $_SESSION['order_id'] = $orderId; // Store order ID for display
            header('Location: payment_success.php');
            exit;
        } catch (Exception $e) {
            // Rollback transaction on error
            $db->rollback();
            $_SESSION['payment_error'] = 'Failed to process ' . ucfirst($paymentMethod) . ' order: ' . $e->getMessage();
            header('Location: make_payment.php?error=' . urlencode('Failed to process ' . ucfirst($paymentMethod) . ' order: ' . $e->getMessage()));
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['payment_error'] = 'Error processing ' . ucfirst($paymentMethod) . ' payment: ' . $e->getMessage();
        header('Location: make_payment.php?error=' . urlencode('Error processing ' . ucfirst($paymentMethod) . ' payment: ' . $e->getMessage()));
        exit;
    }
} else {
    // Invalid access
    $_SESSION['payment_error'] = 'Invalid request method.';
    header('Location: make_payment.php?error=' . urlencode('Invalid request method.'));
    exit;
}
?>
