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

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    http_response_code(403);
    echo 'Unauthorized access';
    exit;
}

$customerId = $_SESSION['user_id'];

$stripeSecretKey = app_env('STRIPE_SECRET_KEY');
if (!$stripeSecretKey) {
    die("Stripe secret key not set.");
}

\Stripe\Stripe::setApiKey($stripeSecretKey);

$requestScheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$appBasePath = str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/customer/make_payment.php')));
$appBasePath = rtrim($appBasePath, '/');
$appBaseUrl = $requestScheme . '://' . $host . $appBasePath;
$customerDashboardUrl = 'dashboard.php';
$publicHomeUrl = '../index.php';

// Get item details from POST or GET (from the "Pay" button)
$foodId = $_POST['food_id'] ?? $_GET['food_id'] ?? null;
$quantity = (int)($_POST['quantity'] ?? $_GET['quantity'] ?? 1);
$chefId = $_POST['chef_id'] ?? $_GET['chef_id'] ?? null; // Optional
$price = (float)($_POST['price'] ?? $_GET['price'] ?? 0.0); // Optional, will fetch from DB if not provided

// Validate item inputs
if (!$foodId || $quantity <= 0) {
    $_SESSION['payment_error'] = 'Invalid food item or quantity.';
    header('Location: ' . $customerDashboardUrl . '?error=' . urlencode('Invalid food item or quantity.'));
    exit;
}

// Fetch item details from database if price not provided
if ($price <= 0) {
    $itemQuery = "SELECT name, price, quantity, is_available FROM food_items WHERE id = ?";
    $item = $db->selectOne($itemQuery, [$foodId]);
    if (!$item) {
        $_SESSION['payment_error'] = 'Food item not found.';
        header('Location: ' . $customerDashboardUrl . '?error=' . urlencode('Food item not found.'));
        exit;
    }
    if ((int) ($item['is_available'] ?? 0) !== 1 || (int) ($item['quantity'] ?? 0) < $quantity) {
        $_SESSION['payment_error'] = 'This item is no longer available in the requested quantity.';
        header('Location: ' . $customerDashboardUrl . '?error=' . urlencode('This item is no longer available in the requested quantity.'));
        exit;
    }
    $price = $item['price'];
    $itemName = $item['name'];
} else {
    // Fetch name for Stripe if price provided
    $itemQuery = "SELECT name, quantity, is_available FROM food_items WHERE id = ?";
    $item = $db->selectOne($itemQuery, [$foodId]);
    if (!$item || (int) ($item['is_available'] ?? 0) !== 1 || (int) ($item['quantity'] ?? 0) < $quantity) {
        $_SESSION['payment_error'] = 'This item is no longer available in the requested quantity.';
        header('Location: ' . $customerDashboardUrl . '?error=' . urlencode('This item is no longer available in the requested quantity.'));
        exit;
    }
    $itemName = $item['name'] ?? 'Food Item';
}

// Function to prepare single item for Stripe Checkout
function prepareSingleItemForCheckout($itemName, $price, $quantity): array {
    return [
        [
            "quantity" => $quantity,
            "price_data" => [
                "currency" => "bdt",
                "unit_amount" => $price * 100, // Convert to smallest unit (cents/paisa)
                "product_data" => [
                    "name" => $itemName
                ]
            ]
        ]
    ];
}

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_method'])) {
    $paymentMethod = $_POST['payment_method'];
    $transactionId = $_POST['payment_method'] === 'stripe' ? '' : ($_POST['transaction_id'] ?? '');
    $deliveryAddress = $_POST['delivery_address'] ?? '';

    // Validate payment inputs
    if (empty($deliveryAddress)) {
        $_SESSION['payment_error'] = 'Delivery address is required.';
        header('Location: make_payment.php?food_id=' . urlencode($foodId) . '&quantity=' . urlencode($quantity) . '&chef_id=' . urlencode($chefId ?? '') . '&price=' . urlencode($price) . '&error=' . urlencode('Delivery address is required.'));
        exit;
    }
    if ($paymentMethod !== 'stripe' && empty($transactionId)) {
        $_SESSION['payment_error'] = 'Transaction ID is required for ' . ucfirst($paymentMethod) . '.';
        header('Location: make_payment.php?food_id=' . urlencode($foodId) . '&quantity=' . urlencode($quantity) . '&chef_id=' . urlencode($chefId ?? '') . '&price=' . urlencode($price) . '&error=' . urlencode('Transaction ID is required for ' . ucfirst($paymentMethod) . '.'));
        exit;
    }

    $_SESSION['payment_method'] = $paymentMethod;
    $_SESSION['transaction_id'] = $transactionId;
    $_SESSION['delivery_address'] = $deliveryAddress;
    $_SESSION['food_id'] = $foodId;
    $_SESSION['quantity'] = $quantity;
    $_SESSION['chef_id'] = $chefId;
    $_SESSION['price'] = $price;

    if ($paymentMethod === 'stripe') {
        try {
            $lineItems = prepareSingleItemForCheckout($itemName, $price, $quantity);
            $checkoutSession = \Stripe\Checkout\Session::create([
                "mode" => "payment",
                "success_url" => $appBaseUrl . "/customer/payment_success.php?session_id={CHECKOUT_SESSION_ID}",
                "cancel_url" => $appBaseUrl . "/customer/make_payment.php?food_id=" . urlencode($foodId) . "&quantity=" . urlencode($quantity) . "&chef_id=" . urlencode($chefId ?? '') . "&price=" . urlencode($price) . "&error=canceled",
                "locale" => "auto",
                "line_items" => $lineItems,
                "metadata" => [
                    "customer_id" => $customerId,
                    "delivery_address" => $deliveryAddress,
                    "food_id" => $foodId,
                    "quantity" => $quantity,
                    "chef_id" => $chefId ?? '',
                    "price" => $price
                ]
            ]);

            http_response_code(303);
            header("Location: " . $checkoutSession->url);
            exit;
        } catch (Exception $e) {
            $_SESSION['payment_error'] = $e->getMessage();
            header('Location: make_payment.php?food_id=' . urlencode($foodId) . '&quantity=' . urlencode($quantity) . '&chef_id=' . urlencode($chefId ?? '') . '&price=' . urlencode($price) . '&error=' . urlencode($e->getMessage()));
            exit;
        }
    } else {
        // Non-Stripe payment methods (Bkash, Nagad, Rocket)
        try {
            // Directly include confirm_order.php to process the payment
            require_once 'confirm_order.php';
            // confirm_order.php handles the redirect to payment_success.php or back to make_payment.php with an error
            exit;
        } catch (Exception $e) {
            $_SESSION['payment_error'] = $e->getMessage();
            header('Location: make_payment.php?food_id=' . urlencode($foodId) . '&quantity=' . urlencode($quantity) . '&chef_id=' . urlencode($chefId ?? '') . '&price=' . urlencode($price) . '&error=' . urlencode($e->getMessage()));
            exit;
        }
    }
}

// Handle errors passed via query string
$errorMessage = isset($_GET['error']) ? urldecode($_GET['error']) : ($_SESSION['payment_error'] ?? '');
unset($_SESSION['payment_error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make Payment - Homemade Food Delivery</title>
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
            animation: fadeIn 1s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { transform: translateY(30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        h2 {
            font-size: 2.5em;
            color: #2e7d32;
            margin-bottom: 30px;
            font-weight: 600;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
            position: relative;
            animation: slideUp 0.8s ease-in-out;
        }

        h2::after {
            content: '';
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, #2e7d32, #66bb6a);
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            border-radius: 2px;
        }

        .payment-container {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            max-width: 450px;
            width: 100%;
            padding: 30px;
            border: 2px solid #66bb6a;
            position: relative;
            overflow: hidden;
            animation: slideUp 1s ease-in-out;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40"><path d="M20 5c-5 0-9 4-9 9s4 9 9 9 9-4 9-9-4-9-9-9zm0 2c3.9 0 7 3.1 7 7s-3.1 7-7 7-7-3.1-7-7 3.1-7 7-7zm-1 2c-1.7 0-3 1.3-3 3s1.3 3 3 3 3-1.3 3-3-1.3-3-3-3z" fill="%2366bb6a" fill-opacity="0.1"/></svg>');
            background-color: rgba(255, 255, 255, 0.95);
        }

        .payment-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(90deg, #2e7d32, #66bb6a);
        }

        label {
            display: block;
            font-size: 1.1em;
            color: #2e7d32;
            font-weight: 500;
            margin-bottom: 10px;
        }

        select, input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #a5d6a7;
            border-radius: 8px;
            font-size: 1em;
            background: #f1f8e9;
            transition: all 0.3s ease;
            margin-bottom: 20px;
            appearance: none;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12"><path d="M6 9l-4-4h8z" fill="%232e7d32"/></svg>');
            background-repeat: no-repeat;
            background-position: right 10px center;
        }

        select:focus, input[type="text"]:focus {
            outline: none;
            border-color: #388e3c;
            box-shadow: 0 0 8px rgba(56, 142, 60, 0.3);
            background: #fff;
        }

        button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(45deg, #2e7d32, #66bb6a);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            animation: pulse 1.5s infinite;
        }

        button:hover {
            background: linear-gradient(45deg, #66bb6a, #2e7d32);
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
        }

        .error {
            color: #d32f2f;
            font-size: 0.9em;
            margin-top: 10px;
            display: none;
            text-align: center;
        }

        .error.visible {
            display: block;
        }

        .hidden {
            display: none;
        }

        @media (max-width: 480px) {
            body {
                padding: 20px 15px;
            }

            h2 {
                font-size: 2em;
            }

            .payment-container {
                padding: 20px;
            }

            select, input[type="text"] {
                padding: 10px;
            }

            button {
                padding: 12px;
                font-size: 1em;
            }
        }
    </style>
</head>
<body>
    <h2>Make Payment</h2>

    <div class="payment-container">
        <form method="post" action="make_payment.php" id="payment-form">
            <input type="hidden" name="food_id" value="<?php echo htmlspecialchars($foodId); ?>">
            <input type="hidden" name="quantity" value="<?php echo htmlspecialchars($quantity); ?>">
            <input type="hidden" name="chef_id" value="<?php echo htmlspecialchars($chefId ?? ''); ?>">
            <input type="hidden" name="price" value="<?php echo htmlspecialchars($price); ?>">
            
            <label>Select Payment Method:</label>
            <select name="payment_method" required>
                <option value="stripe">Stripe</option>
                <option value="bkash">Bkash</option>
                <option value="nagad">Nagad</option>
                <option value="rocket">Rocket</option>
            </select>
            
            <label>Delivery Address:</label>
            <input type="text" name="delivery_address" value="<?php echo htmlspecialchars($_SESSION['user_address'] ?? 'Matuail'); ?>" required>

            <label>Transaction ID (for non-Stripe methods):</label>
            <input type="text" name="transaction_id" class="hidden" required>

            <div id="payment-errors" class="error <?php echo $errorMessage ? 'visible' : ''; ?>">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>

            <button type="submit">Pay</button>
        </form>
    </div>

    <script>
        const form = document.getElementById('payment-form');
        const transactionInput = document.querySelector('input[name="transaction_id"]');
        const paymentErrors = document.getElementById('payment-errors');

        form.querySelector('select[name="payment_method"]').addEventListener('change', (e) => {
            const isStripe = e.target.value === 'stripe';
            transactionInput.classList.toggle('hidden', isStripe);
            if (isStripe) {
                transactionInput.removeAttribute('required');
            } else {
                transactionInput.setAttribute('required', '');
            }
        });

        form.addEventListener('submit', (event) => {
            const paymentMethod = form.querySelector('select[name="payment_method"]').value;
            if (paymentMethod !== 'stripe' && !transactionInput.value) {
                event.preventDefault();
                paymentErrors.textContent = 'Please enter a Transaction ID for non-Stripe payment methods.';
                paymentErrors.classList.add('visible');
            }
        });

        // Set initial state based on default payment method
        if (form.querySelector('select[name="payment_method"]').value !== 'stripe') {
            transactionInput.classList.remove('hidden');
        } else {
            transactionInput.classList.add('hidden');
            transactionInput.removeAttribute('required');
        }
    </script>
</body>
</html>
