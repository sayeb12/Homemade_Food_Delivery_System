<?php
session_start();
$pageTitle = 'Order History';
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

// Fetch cart items for the customer
$cartItems = $db->select(
    "SELECT c.id, c.food_id, c.quantity, c.added_at, f.name, f.price, f.image, u.name AS chef_name, u.id AS chef_id 
     FROM cart c 
     JOIN food_items f ON c.food_id = f.id 
     JOIN users u ON c.chef_id = u.id 
     WHERE c.customer_id = ?",
    [$userId]
);

// Fetch confirmed orders for the customer
$confirmedOrders = $db->select(
    "SELECT oi.id, oi.order_id, oi.food_item_id AS food_id, oi.quantity, oi.price, o.created_at AS order_date, 
            f.name, f.image, u.name AS chef_name 
     FROM orders o 
     JOIN order_items oi ON o.id = oi.order_id 
     JOIN food_items f ON oi.food_item_id = f.id 
     JOIN users u ON f.chef_id = u.id 
     WHERE o.customer_id = ? AND o.status = 'confirmed' 
     ORDER BY o.created_at DESC",
    [$userId]
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Homemade Food Delivery</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;
        }

        body {
            background: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }

        header {
            background: #fff;
            padding: 15px 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        .logo img {
            height: 50px;
        }

        .user {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user img {
            height: 40px;
            width: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .user div {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }

        .user div strong {
            font-size: 1em;
            color: #333;
        }

        .user div span {
            font-size: 0.9em;
            color: #666;
        }

        .btn {
            padding: 8px 15px;
            background: #3498db;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.95em;
            font-weight: 500;
            text-decoration: none;
            transition: background 0.3s;
        }

        .btn:hover {
            background: #2980b9;
        }

        .btn-dark {
            background: #c62828;
        }

        .btn-dark:hover {
            background: #960d0d;
        }

        .main-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }

        .section-title {
            text-align: center;
            margin-bottom: 30px;
        }

        .section-title h2 {
            font-size: 2.2em;
            color: #2c3e50;
            font-weight: 600;
        }

        .tabs {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
        }

        .tab {
            padding: 10px 25px;
            font-size: 1.1em;
            font-weight: 500;
            color: #666;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .tab.active {
            background: #3498db;
            color: #fff;
            border-color: #3498db;
        }

        .tab:hover:not(.active) {
            background: #f0f0f0;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .order-list {
            display: grid;
            gap: 20px;
        }

        .order-item {
            display: flex;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            overflow: hidden;
        }

        .order-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
        }

        .order-image {
            width: 150px;
            height: 150px;
            flex-shrink: 0;
        }

        .order-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 12px 0 0 12px;
        }

        .order-details {
            padding: 15px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .order-details h3 {
            font-size: 1.4em;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .order-meta {
            font-size: 0.95em;
            color: #666;
            margin-bottom: 10px;
        }

        .order-meta span {
            margin-right: 10px;
        }

        .order-price {
            font-weight: bold;
            color: #e67e22;
            font-size: 1.1em;
        }

        .order-date {
            font-size: 0.9em;
            color: #666;
        }

        .order-form {
            margin-top: 15px;
        }

        .order-form label {
            display: block;
            font-size: 0.95em;
            color: #555;
            margin-bottom: 5px;
        }

        .order-form input[type="number"],
        .order-form input[type="text"] {
            width: 100%;
            max-width: 300px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1em;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .order-form input[type="number"]:focus,
        .order-form input[type="text"]:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
        }

        .order-form button {
            margin-top: 15px;
            padding: 10px 20px;
            background: #3498db;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 1em;
            cursor: pointer;
            transition: background 0.3s;
        }

        .order-form button:hover {
            background: #2980b9;
        }

        .no-items {
            text-align: center;
            font-size: 1.2em;
            color: #666;
            padding: 40px 0;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        footer {
            background: #2c3e50;
            color: #fff;
            padding: 30px 20px;
            margin-top: 50px;
            text-align: center;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-logo img {
            height: 40px;
            margin-bottom: 15px;
        }

        .footer-text {
            color: #bdc3c7;
            margin-bottom: 20px;
        }

        .footer-social a {
            color: #fff;
            margin: 0 10px;
            font-size: 1.2em;
            transition: color 0.3s;
        }

        .footer-social a:hover {
            color: #3498db;
        }

        .footer-contact {
            margin-top: 10px;
        }

        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 15px;
            }

            .order-item {
                flex-direction: column;
            }

            .order-image {
                width: 100%;
                height: 200px;
                border-radius: 12px 12px 0 0;
            }

            .order-details {
                padding: 15px;
            }

            .tabs {
                flex-direction: column;
                gap: 10px;
            }

            .tab {
                width: 100%;
                text-align: center;
            }

            .order-form input[type="number"],
            .order-form input[type="text"] {
                max-width: 100%;
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
            <div class="user">
                <img src="<?php echo htmlspecialchars($profileImage); ?>" alt="<?php echo htmlspecialchars($customerName); ?>">
                <div>
                    <strong><?php echo htmlspecialchars($customerName); ?></strong>
                    <span><?php echo htmlspecialchars($customerLocation); ?></span>
                </div>
                <a href="dashboard.php" class="btn">Back to Dashboard</a>
                <a href="../logout.php" class="btn btn-dark">Log Out</a>
            </div>
        </div>
    </header>

    <div class="main-container">
        <div class="section-title">
            <h2>Your Order History</h2>
        </div>

        <div class="tabs">
            <div class="tab active" onclick="showTab('cart-items')">Cart Items</div>
            <div class="tab" onclick="showTab('confirmed-orders')">Confirmed Orders</div>
        </div>

        <!-- Cart Items Tab -->
        <div id="cart-items" class="tab-content active">
            <?php if (empty($cartItems)): ?>
                <div class="no-items">No items in your cart.</div>
            <?php else: ?>
                <div class="order-list">
                    <?php foreach ($cartItems as $item): ?>
                        <div class="order-item">
                            <div class="order-image">
                                <img src="<?php echo $item['image'] ? '../uploads/dishes/' . htmlspecialchars($item['image']) : '/placeholder.svg?height=150&width=150'; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                            </div>
                            <div class="order-details">
                                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                <div class="order-meta">
                                    <span>Chef: <?php echo htmlspecialchars($item['chef_name']); ?></span>
                                    <span>Quantity in Cart: <?php echo htmlspecialchars($item['quantity']); ?></span>
                                </div>
                                <div class="order-price"><?php echo CURRENCY_SYMBOL; ?><?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                                <div class="order-date">Added on: <?php echo date('d M Y, H:i', strtotime($item['added_at'])); ?></div>

                                <form action="place_order.php" method="POST" class="order-form">
                                    <input type="hidden" name="food_id" value="<?php echo htmlspecialchars($item['food_id']); ?>">
                                    <input type="hidden" name="chef_id" value="<?php echo htmlspecialchars($item['chef_id']); ?>">
                                    <input type="hidden" name="food_name" value="<?php echo htmlspecialchars($item['name']); ?>">
                                    <input type="hidden" name="food_image" value="<?php echo htmlspecialchars($item['image']); ?>">
                                    <input type="hidden" name="price" value="<?php echo htmlspecialchars($item['price']); ?>">

                                    <label>Order Quantity:
                                        <input type="number" name="quantity" value="<?php echo htmlspecialchars($item['quantity']); ?>" min="1" required>
                                    </label>
                                    <label>Delivery Address:
                                        <input type="text" name="delivery_address" placeholder="Enter delivery address" value="<?php echo isset($_SESSION['user_address']) ? htmlspecialchars($_SESSION['user_address']) : ''; ?>" required>
                                    </label>

                                    <button type="submit">Place Your Order</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Confirmed Orders Tab -->
        <div id="confirmed-orders" class="tab-content">
            <?php if (empty($confirmedOrders)): ?>
                <div class="no-items">No confirmed orders yet.</div>
            <?php else: ?>
                <div class="order-list">
                    <?php foreach ($confirmedOrders as $order): ?>
                        <div class="order-item">
                            <div class="order-image">
                                <img src="<?php echo $order['image'] ? '../uploads/dishes/' . htmlspecialchars($order['image']) : '/placeholder.svg?height=150&width=150'; ?>" alt="<?php echo htmlspecialchars($order['name']); ?>">
                            </div>
                            <div class="order-details">
                                <h3><?php echo htmlspecialchars($order['name']); ?></h3>
                                <div class="order-meta">
                                    <span>Chef: <?php echo htmlspecialchars($order['chef_name']); ?></span>
                                    <span>Quantity: <?php echo htmlspecialchars($order['quantity']); ?></span>
                                </div>
                                <div class="order-price"><?php echo CURRENCY_SYMBOL; ?><?php echo number_format($order['price'] * $order['quantity'], 2); ?></div>
                                <div class="order-date">Ordered on: <?php echo date('d M Y, H:i', strtotime($order['order_date'])); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function showTab(tabId) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });

            // Remove active class from all tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });

            // Show selected tab content and set tab as active
            document.getElementById(tabId).classList.add('active');
            document.querySelector(`.tab[onclick="showTab('${tabId}')"]`).classList.add('active');
        }
    </script>
<?php
$footerBasePath = '..';
$footerTheme = 'green';
require '../includes/footer.php';
?>

