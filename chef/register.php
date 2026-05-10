<?php
$pageTitle = 'Chef Registration';
$includeAuth = true;

require_once '../includes/config.php';
require_once '../includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: menu-management.php');
    exit;
}

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $address = sanitizeInput($_POST['address']);
    $password = $_POST['password'];
    
    // Validate inputs
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Name is required';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format. Please enter a valid email address.';
    }
    
    if (empty($phone)) {
        $errors[] = 'Phone number is required';
    } elseif (!preg_match('/^0\d{10}$/', $phone)) {
        $errors[] = 'Phone number must start with 0 and contain exactly 11 digits.';
    }
    
    if (empty($address)) {
        $errors[] = 'Address is required';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    } else {
        $passwordCheck = isStrongPassword($password);
        if (!$passwordCheck['valid']) {
            $errors[] = $passwordCheck['message'];
        }
    }
    
    // Handle profile image upload
    $profileImage = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['profile_image']['type'], $allowedTypes)) {
            $errors[] = 'Only JPG, PNG, and GIF images are allowed';
        } elseif ($_FILES['profile_image']['size'] > $maxSize) {
            $errors[] = 'Image size should not exceed 5MB';
        } else {
            $profileImage = uniqid() . '_' . basename($_FILES['profile_image']['name']);
            $uploadPath = $uploadDirs['profiles'] . $profileImage;
            
            if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadPath)) {
                $errors[] = 'Failed to upload image. Please try again.';
                $profileImage = null;
            }
        }
    }
    
    if (empty($errors)) {
        // Register user
        $result = registerUser($name, $email, $phone, $address, $password, 'chef', $profileImage);
        
        if ($result['success']) {
            // For development, we'll show the verification code
            $verificationCode = $result['verification_code'];
            
            // Store user ID for verification
            $_SESSION['user_id_for_verification'] = $result['user_id'];
            
            // Show success message
            $successMessage = "Registration successful! Please check your email for verification code.";
            
            // Redirect to login page
            header("Location: login.php?message=" . urlencode($successMessage));
            exit;
        } else {
            $errors[] = $result['message'];
            
            // If registration failed but image was uploaded, delete it
            if ($profileImage && file_exists($uploadDirs['profiles'] . $profileImage)) {
                unlink($uploadDirs['profiles'] . $profileImage);
            }
        }
    }
}
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f9f9f9;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .container {
            display: flex;
            width: 100%;
            max-width: 1000px;
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        
        .logo-section {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .logo {
            width: 350px;
            height: 350px;
            margin-bottom: 20px;
        }
        
        .form-section {
            flex: 1;
            padding: 40px;
            background: #fff;
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 30px;
            background-color: #e67e22;
            padding: 21px;
            border-radius: 23px;
        }
        
        .centerCustom {
            text-align: center;
        }
        
        .form-header p {
            font-size: 18px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            border-color: #e67e22;
            outline: none;
        }
        
        .btn {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: #e67e22;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #d35400;
        }
        
        .form-footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        
        .form-footer a {
            color: #e67e22;
            text-decoration: none;
        }
        
        .form-footer a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .password-strength {
            margin-top: 5px;
            font-size: 14px;
        }
        
        .password-weak {
            color: #dc3545;
        }
        
        .password-medium {
            color: #ffc107;
        }
        
        .password-strong {
            color: #28a745;
        }
        
        .image-preview {
            margin-top: 10px;
            text-align: center;
        }
        
        .image-preview img {
            max-width: 150px;
            max-height: 150px;
            border-radius: 50%;
            border: 2px solid #e67e22;
            display: none;
        }
        
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                max-width: 500px;
            }
            
            .logo-section {
                padding: 20px;
            }
            
            .form-section {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-section">
            <img src="../assets/images/logo.png" alt="Homemade Food Delivery" class="logo">
        </div>
        <div class="form-section">
            <h3 class="centerCustom">Chef Account</h3>
            <div class="form-header">
                <h3>Sign up</h3>
                <p>Be a Member of our Family</p>
            </div>
            
            <?php if (isset($errors) && !empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form id="registration-form" method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="Enter Your Name" value="<?php echo isset($name) ? $name : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" value="<?php echo isset($email) ? $email : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" class="form-control" placeholder="Enter Your Address" value="<?php echo isset($address) ? $address : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" class="form-control" placeholder="Enter your phone number (start with 0, 11 digits)" value="<?php echo isset($phone) ? $phone : ''; ?>" required pattern="^0\d{10}$">
                    <small>Phone number must start with 0 and contain exactly 11 digits</small>
                </div>
                
                <div class="form-group">
                    <label for="profile_image">Profile Picture</label>
                    <input type="file" id="profile_image" name="profile_image" class="form-control" accept="image/jpeg, image/png, image/gif">
                    <div class="image-preview">
                        <img id="preview-image" src="#" alt="Profile Preview">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter a strong password" required>
                    <div id="password-strength" class="password-strength"></div>
                </div>
                
                <button type="submit" class="btn">Sign Up</button>
                
                <div class="form-footer">
                    <p>Already have an account? <a href="login.php">Log In</a></p>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const passwordStrength = document.getElementById('password-strength');
            const phoneInput = document.getElementById('phone');
            
            if (passwordInput && passwordStrength) {
                passwordInput.addEventListener('input', function() {
                    const password = this.value;
                    let score = 0;
                    let message = '';
                    
                    // Check length
                    if (password.length < 8) {
                        message = 'Password must be at least 8 characters long';
                    } else {
                        score++;
                    }
                    
                    // Check for uppercase letters
                    if (!/[A-Z]/.test(password)) {
                        if (!message) message = 'Password must contain at least one uppercase letter';
                    } else {
                        score++;
                    }
                    
                    // Check for lowercase letters
                    if (!/[a-z]/.test(password)) {
                        if (!message) message = 'Password must contain at least one lowercase letter';
                    } else {
                        score++;
                    }
                    
                    // Check for numbers
                    if (!/[0-9]/.test(password)) {
                        if (!message) message = 'Password must contain at least one number';
                    } else {
                        score++;
                    }
                    
                    // Check for special characters
                    if (!/[^A-Za-z0-9]/.test(password)) {
                        if (!message) message = 'Password must contain at least one special character';
                    } else {
                        score++;
                    }
                    
                    // Update strength indicator
                    passwordStrength.className = 'password-strength';
                    
                    if (password.length === 0) {
                        passwordStrength.textContent = '';
                    } else if (score <= 2) {
                        passwordStrength.textContent = 'Weak password';
                        passwordStrength.classList.add('password-weak');
                        if (message) passwordStrength.textContent += ': ' + message;
                    } else if (score <= 4) {
                        passwordStrength.textContent = 'Medium password';
                        passwordStrength.classList.add('password-medium');
                    } else {
                        passwordStrength.textContent = 'Strong password';
                        passwordStrength.classList.add('password-strong');
                    }
                });
            }
            
            // Phone number validation
            if (phoneInput) {
                phoneInput.addEventListener('input', function() {
                    const phone = this.value;
                    const isValid = /^0\d{0,10}$/.test(phone);
                    
                    if (!isValid) {
                        this.value = phone.replace(/[^0-9]/g, '');
                        if (this.value.length > 0 && this.value[0] !== '0') {
                            this.value = '0' + this.value.substring(1);
                        }
                        if (this.value.length > 11) {
                            this.value = this.value.substring(0, 11);
                        }
                    }
                });
            }
            
            // Image preview functionality
            const profileImageInput = document.getElementById('profile_image');
            const previewImage = document.getElementById('preview-image');
            
            if (profileImageInput && previewImage) {
                profileImageInput.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        
                        reader.onload = function(e) {
                            previewImage.src = e.target.result;
                            previewImage.style.display = 'block';
                        }
                        
                        reader.readAsDataURL(this.files[0]);
                    } else {
                        previewImage.style.display = 'none';
                    }
                });
            }
        });
    </script>
</body>
</html>