<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once __DIR__ . '/config.php';

// Check session timeout
if (isLoggedIn()) {
    checkSessionTimeout();
}

$isHomepageHeader = !empty($isHomepageHeader);
$homepageCategories = function_exists('getCategories') ? getCategories() : [];
$searchPlaceholder = $isHomepageHeader
    ? 'Search dishes, chefs, locations, or categories'
    : 'Enter item or Home you are looking for';
$searchScope = isset($_GET['search_in']) ? (string) $_GET['search_in'] : 'all';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container header-container">
            <div class="logo">
                <a href="index.php">
                    <img class="hero_logo" src="assets/images/logo.png" alt="<?php echo SITE_NAME; ?>">
                </a>
            </div>

            <div class="search-bar<?php echo $isHomepageHeader ? ' search-bar-home' : ''; ?>">
                <form class="search-form<?php echo $isHomepageHeader ? ' search-form-home' : ''; ?>" action="index.php" method="GET">
                    <input
                        type="text"
                        name="search"
                        placeholder="<?php echo htmlspecialchars($searchPlaceholder); ?>"
                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                    >
                    <?php if ($isHomepageHeader): ?>
                        <select name="search_in" aria-label="Search in">
                            <option value="all" <?php echo $searchScope === 'all' ? 'selected' : ''; ?>>All</option>
                            <option value="dish" <?php echo $searchScope === 'dish' ? 'selected' : ''; ?>>Dishes</option>
                            <option value="location" <?php echo $searchScope === 'location' ? 'selected' : ''; ?>>Locations</option>
                            <option value="category" <?php echo $searchScope === 'category' ? 'selected' : ''; ?>>Category</option>
                        </select>
                        <button type="submit" class="header-search-btn">Discover</button>
                    <?php endif; ?>
                </form>

                <?php if ($isHomepageHeader): ?>
                    <div class="homepage-nav-pills">
                        <a href="index.php#top-selling-section">Top Meals</a>
                        <a href="index.php?search_in=location&search=Dhaka#top-selling-section">Dhaka Kitchens</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="user-actions">
                <?php if (isLoggedIn()): ?>
                    <?php if (isUserType('customer')): ?>
                        <a href="customer/order-history.php" class="btn btn-outline">Order History</a>
                    <?php elseif (isUserType('chef')): ?>
                        <a href="chef/dashboard.php" class="btn btn-outline">Dashboard</a>
                    <?php endif; ?>
                    
                    <div class="user-profile">
                        <span><?php echo $_SESSION['user_name']; ?></span>
                        <a href="logout.php" class="btn btn-dark">Log Out</a>
                    </div>
                <?php else: ?>
                    <a href="customer/login.php" class="btn btn-outline">Customer Login</a>
                    <a href="chef/login.php" class="btn">Chef Login</a>
                <?php endif; ?>
            </div>
        </div>
    </header>
    
    <!-- Flash Messages -->
    <div class="container">
        <?php displayFlashMessage(); ?>
    </div>
