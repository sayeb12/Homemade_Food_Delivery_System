<?php
session_start();
require_once '../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    die("Unauthorized access");
}

// Fetch POST data from cart.php
$food_id = $_POST['food_id'] ?? null;
$chef_id = $_POST['chef_id'] ?? null;
$food_name = $_POST['food_name'] ?? '';
$food_image = $_POST['food_image'] ?? '';
$chef_name = $_POST['chef_name'] ?? '';
$price = $_POST['price'] ?? 0;
$quantity = $_POST['quantity'] ?? 1;
$delivery_address = $_POST['delivery_address'] ?? '';

// Calculate total price
$total_price = $price * $quantity;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Your Order - Homemade Food Delivery</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            /* Food background image with 50% transparency white overlay */
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

        /* Keyframes for animations */
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
            color: #2e7d32; /* Forest green for greenery theme */
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
            background: linear-gradient(90deg, #2e7d32, #66bb6a); /* Green gradient */
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            border-radius: 2px;
        }

        .order-container {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            max-width: 450px;
            width: 100%;
            padding: 30px;
            margin-bottom: 30px;
            border: 2px solid #66bb6a; /* Light green border */
            position: relative;
            overflow: hidden;
            animation: slideUp 1s ease-in-out;
            /* Subtle leaf pattern in background */
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40"><path d="M20 5c-5 0-9 4-9 9s4 9 9 9 9-4 9-9-4-9-9-9zm0 2c3.9 0 7 3.1 7 7s-3.1 7-7 7-7-3.1-7-7 3.1-7 7-7zm-1 2c-1.7 0-3 1.3-3 3s1.3 3 3 3 3-1.3 3-3-1.3-3-3-3z" fill="%2366bb6a" fill-opacity="0.1"/></svg>');
        }

        .order-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(90deg, #2e7d32, #66bb6a); /* Green gradient */
        }

        .order-image {
            display: block;
            margin: 0 auto 20px;
            width: 200px;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
            border: 3px solid #a5d6a7; /* Soft green border */
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .order-image:hover {
            transform: scale(1.05);
        }

        .order-details p {
            font-size: 1.1em;
            margin-bottom: 12px;
            color: #2d3436;
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #e8f5e9; /* Very light green */
            padding-bottom: 8px;
        }

        .order-details strong {
            color: #2e7d32; /* Forest green */
            font-weight: 500;
        }

        .order-details .total-price {
            font-size: 1.3em;
            font-weight: 600;
            color: #388e3c; /* Darker green */
            text-align: center;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 2px solid #66bb6a; /* Light green */
        }

        form {
            max-width: 450px;
            width: 100%;
            animation: slideUp 1.2s ease-in-out;
            /* Subtle leaf pattern in background */
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40"><path d="M20 5c-5 0-9 4-9 9s4 9 9 9 9-4 9-9-4-9-9-9zm0 2c3.9 0 7 3.1 7 7s-3.1 7-7 7-7-3.1-7-7 3.1-7 7-7zm-1 2c-1.7 0-3 1.3-3 3s1.3 3 3 3 3-1.3 3-3-1.3-3-3-3z" fill="%2366bb6a" fill-opacity="0.1"/></svg>');
            padding: 20px;
            border-radius: 15px;
            border: 2px solid #66bb6a; /* Light green border */
            background-color: rgba(255, 255, 255, 0.95);
        }

        label {
            display: block;
            font-size: 1em;
            color: #2e7d32; /* Forest green */
            font-weight: 500;
            margin-bottom: 8px;
        }

        input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #a5d6a7; /* Soft green */
            border-radius: 8px;
            font-size: 1em;
            background: #f1f8e9; /* Very light green background */
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        input[type="text"]:focus {
            outline: none;
            border-color: #388e3c; /* Darker green */
            box-shadow: 0 0 8px rgba(56, 142, 60, 0.3);
            background: #fff;
        }

        button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(45deg, #2e7d32, #66bb6a); /* Green gradient */
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

        @media (max-width: 480px) {
            body {
                padding: 20px 15px;
            }

            h2 {
                font-size: 2em;
            }

            .order-container, form {
                padding: 20px;
            }

            .order-image {
                width: 150px;
                height: 150px;
            }

            .order-details p {
                font-size: 1em;
            }

            button {
                padding: 12px;
                font-size: 1em;
            }
        }
    </style>
</head>
<body>
    <h2>Review Your Order</h2>

    <div class="order-container">
        <img src="../uploads/dishes/<?php echo htmlspecialchars($food_image); ?>" alt="<?php echo htmlspecialchars($food_name); ?>" class="order-image">

        <div class="order-details">
            <p><strong>Item:</strong> <?php echo htmlspecialchars($food_name); ?></p>
            <p><strong>Chef:</strong> <?php echo htmlspecialchars($chef_name); ?></p>
            <p><strong>Price per Unit:</strong> <?php echo number_format($price, 2); ?></p>
            <p><strong>Quantity:</strong> <?php echo htmlspecialchars($quantity); ?></p>
            <p class="total-price"><strong>Total Price:</strong> <?php echo number_format($total_price, 2); ?></p>
        </div>
    </div>

    <form action="make_payment.php" method="POST">
        <!-- Hidden fields to pass data forward -->
        <input type="hidden" name="food_id" value="<?php echo htmlspecialchars($food_id); ?>">
        <input type="hidden" name="chef_id" value="<?php echo htmlspecialchars($chef_id); ?>">
        <input type="hidden" name="food_name" value="<?php echo htmlspecialchars($food_name); ?>">
        <input type="hidden" name="price" value="<?php echo htmlspecialchars($price); ?>">
        <input type="hidden" name="quantity" value="<?php echo htmlspecialchars($quantity); ?>">
        <input type="hidden" name="total_price" value="<?php echo htmlspecialchars($total_price); ?>">

        <label>Delivery Address:<br>
            <input type="text" name="delivery_address" value="<?php echo htmlspecialchars($delivery_address); ?>" required>
        </label>

        <label>Phone Number:<br>
            <input type="text" name="phone" required>
        </label>

        <button type="submit">Make Payment</button>
    </form>

    <script>
        // Optional: Add a subtle scroll reveal effect for the elements
        document.addEventListener('DOMContentLoaded', () => {
            const elements = document.querySelectorAll('.order-container, form');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, { threshold: 0.1 });

            elements.forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(30px)';
                el.style.transition = 'all 0.8s ease-in-out';
                observer.observe(el);
            });
        });
    </script>
</body>
</html>