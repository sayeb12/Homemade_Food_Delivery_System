<?php
$orders = $db->select("SELECT o.*, u.name AS customer_name FROM orders o JOIN users u ON o.customer_id = u.id WHERE chef_id = ?", [$_SESSION['user_id']]);

foreach ($orders as $order) {
    echo "Order for {$order['customer_name']}<br>";
    echo "Status: {$order['status']}<br>";
    echo "<form method='POST' action='update_status.php'>
        <input type='hidden' name='order_id' value='{$order['id']}'>
        <button name='status' value='Received'>Mark as Received</button>
        <button name='status' value='Not Received'>Mark as Not Received</button>
    </form><hr>";
}
?>
