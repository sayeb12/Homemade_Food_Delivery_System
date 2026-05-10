<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'includes/auth.php';
require_once 'includes/config.php';

$userType = $_SESSION['user_type'] ?? 'customer';
logoutUser();
error_log("User logged out successfully");
$redirectUrl = $userType === 'chef' ? 'chef/login.php' : 'customer/login.php';
header('Location: ' . $redirectUrl . '?message=' . urlencode('You have been logged out successfully.'));
exit;
?>
