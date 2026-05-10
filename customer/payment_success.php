<?php
session_start();

// Debug: Check if autoload file exists
$autoloadPath = realpath('../vendor/autoload.php');
if (!$autoloadPath) {
    die("Autoload file not found at: " . realpath('../vendor/autoload.php') . ". Please run 'composer install' in " . __DIR__ . "/..");
}
require_once '../vendor/autoload.php';
require_once '../includes/env.php';
require_once '../includes/db.php';

// Ensure the user is authenticated and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    http_response_code(403);
    echo 'Unauthorized access';
    exit;
}

$stripeSecretKey = app_env('STRIPE_SECRET_KEY');
if (!$stripeSecretKey) {
    die("Stripe secret key not set.");
}

\Stripe\Stripe::setApiKey($stripeSecretKey);

$publicHomeUrl = 'dashboard.php';
$paymentPageUrl = 'make_payment.php';

// Initialize variables
$orderId = null;
$errorMessage = null;

// Check for Stripe Checkout Session ID (for Stripe payments)
$sessionId = $_GET['session_id'] ?? null;
if ($sessionId) {
    try {
        // Retrieve the Checkout Session
        $checkoutSession = \Stripe\Checkout\Session::retrieve($sessionId);

        // Verify payment status
        if ($checkoutSession->payment_status !== 'paid') {
            $_SESSION['payment_error'] = 'Payment not completed.';
            header('Location: make_payment.php?error=' . urlencode('Payment not completed.'));
            exit;
        }

        $customerId = $checkoutSession->metadata->customer_id;
        $deliveryAddress = $checkoutSession->metadata->delivery_address;
        $foodId = $checkoutSession->metadata->food_id;
        $quantity = (int)$checkoutSession->metadata->quantity;
        $chefId = $checkoutSession->metadata->chef_id ?: null;
        $price = (float)$checkoutSession->metadata->price;

        // Verify the customer ID matches the session
        if ($customerId != $_SESSION['user_id']) {
            $_SESSION['payment_error'] = 'Customer ID mismatch.';
            header('Location: make_payment.php?error=' . urlencode('Customer ID mismatch.'));
            exit;
        }

        // Validate item
        if (!$foodId || $quantity <= 0) {
            $_SESSION['payment_error'] = 'Invalid food item or quantity.';
            header('Location: make_payment.php?error=' . urlencode('Invalid food item or quantity.'));
            exit;
        }

        // Fetch price from database if not provided
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

            // Insert order item
            $orderItemInsertQuery = "INSERT INTO order_items (order_id, food_item_id, quantity, price) 
                                    VALUES (?, ?, ?, ?)";
            $db->insert($orderItemInsertQuery, [$orderId, $foodId, $quantity, $price]);

            // Insert payment
            $paymentQuery = "INSERT INTO payments (order_id, payment_method, transaction_id, created_at) 
                            VALUES (?, 'stripe', ?, NOW())";
            $db->insert($paymentQuery, [$orderId, $checkoutSession->id]);

            // Remove the item from the cart
            $db->delete("DELETE FROM cart WHERE customer_id = ? AND food_id = ?", [$customerId, $foodId]);

            // Commit transaction
            $db->commit();

            // Set success message
            $_SESSION['order_success'] = 'Your Stripe order has been placed successfully.';
            $_SESSION['order_id'] = $orderId; // Store order ID for display
        } catch (Exception $e) {
            // Rollback transaction on error
            $db->rollback();
            $errorMessage = 'Failed to process Stripe order: ' . $e->getMessage();
            $_SESSION['payment_error'] = $errorMessage;
            header('Location: make_payment.php?error=' . urlencode($errorMessage));
            exit;
        }
    } catch (\Stripe\Exception\ApiErrorException $e) {
        $errorMessage = 'Stripe error: ' . $e->getMessage();
        $_SESSION['payment_error'] = $errorMessage;
        header('Location: make_payment.php?error=' . urlencode($errorMessage));
        exit;
    }
} else {
    // Handle non-Stripe payments (e.g., from confirm_order.php)
    if (!isset($_SESSION['order_success'])) {
        $errorMessage = 'Invalid access or no payment processed.';
        $_SESSION['payment_error'] = $errorMessage;
        header('Location: make_payment.php?error=' . urlencode($errorMessage));
        exit;
    }
    $orderId = $_SESSION['order_id'] ?? null; // Get order ID from session
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success - Homemade Food Delivery</title>
    <style>
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
            padding: 40px 20px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .container {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            max-width: 600px;
            width: 100%;
            padding: 30px;
            text-align: center;
            border: 2px solid #66bb6a;
            position: relative;
            animation: slideUp 1s ease-in-out;
        }

        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(90deg, #2e7d32, #66bb6a);
        }

        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        h2 {
            font-size: 2em;
            color: #2e7d32;
            margin-bottom: 20px;
            font-weight: 600;
        }

        p {
            font-size: 1.1em;
            margin-bottom: 20px;
            color: #333;
        }

        .error {
            color: #d32f2f;
            font-size: 1em;
            margin-bottom: 20px;
        }

        a {
            display: inline-block;
            padding: 10px 20px;
            background: linear-gradient(45deg, #2e7d32, #66bb6a);
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        a:hover {
            background: linear-gradient(45deg, #66bb6a, #2e7d32);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        @media (max-width: 480px) {
            body {
                padding: 20px 15px;
            }

            .container {
                padding: 20px;
            }

            h2 {
                font-size: 1.8em;
            }

            p {
                font-size: 1em;
            }

            a {
                padding: 8px 16px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($_SESSION['order_success'])): ?>
            <h2>Payment Successful!</h2>
            <?php if ($orderId): ?>
                <p>Your order (ID: <?php echo htmlspecialchars($orderId); ?>) has been placed successfully.</p>
            <?php else: ?>
                <p>Your order has been placed successfully.</p>
            <?php endif; ?>
            <p><?php echo htmlspecialchars($_SESSION['order_success']); ?></p>
            <a href="<?php echo $publicHomeUrl; ?>">Back to Home</a>
            <?php unset($_SESSION['order_success'], $_SESSION['order_id']); ?>
        <?php elseif ($errorMessage): ?>
            <h2>Payment Error</h2>
            <p class="error"><?php echo htmlspecialchars($errorMessage); ?></p>
            <a href="<?php echo $paymentPageUrl; ?>">Try Again</a>
        <?php else: ?>
            <h2>Payment Success</h2>
            <p>Your order has been placed successfully.</p>
            <a href="<?php echo $publicHomeUrl; ?>">Back to Home</a>
            <?php unset($_SESSION['order_success'], $_SESSION['order_id']); ?>
        <?php endif; ?>
    </div>
</body>
</html>
