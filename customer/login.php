<?php
$pageTitle = 'Customer Login';
$includeAuth = true;

require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Enable error reporting (for development)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

// Check for success message from registration
$successMessage = isset($_GET['message']) ? $_GET['message'] : '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailOrPhone = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $verificationCode = isset($_POST['verification_code']) ? sanitizeInput($_POST['verification_code']) : '';
    
    // Validate inputs
    $errors = [];
    
    if (empty($emailOrPhone)) {
        $errors[] = 'Email or Phone is required';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    }
    
    // Process verification code if provided
    if (!empty($verificationCode)) {
        if (isset($_SESSION['user_id_for_verification'])) {
            $verifyResult = verifyUser($_SESSION['user_id_for_verification'], $verificationCode);
            
            if ($verifyResult['success']) {
                // Redirect to dashboard
                header('Location: dashboard.php');
                exit;
            } else {
                $errors[] = $verifyResult['message'];
            }
        } elseif (isset($_SESSION['user_id_for_2fa'])) {
            $verify2FAResult = verify2FA($_SESSION['user_id_for_2fa'], $verificationCode);
            
            if ($verify2FAResult['success']) {
                // Redirect to dashboard
                header('Location: dashboard.php');
                exit;
            } else {
                $errors[] = $verify2FAResult['message'];
            }
        }
    }

    // Only attempt login if no verification was processed or verification failed
    if (empty($errors) && empty($verificationCode)) {
        // Modify loginUser to check email or phone
        global $db;
        $user = $db->selectOne("SELECT id, email, name, password, is_verified, verification_code, user_type 
                                FROM users 
                                WHERE email = ? OR phone = ?", 
                               [$emailOrPhone, $emailOrPhone]);
        if ($user && password_verify($password, $user['password'])) {
            if ($user['user_type'] !== 'customer') {
                $errors[] = 'No customer account found with this email. Please log in through the ' . ucfirst($user['user_type']) . ' portal.';
            } elseif (!$user['is_verified']) {
                $newCode = generateVerificationCode();
                $db->update("UPDATE users SET verification_code = ? WHERE id = ?", [$newCode, $user['id']]);
                sendVerificationEmail($user['email'], $user['name'], $newCode);
                $_SESSION['user_id_for_verification'] = $user['id'];
                $needsVerification = true;
                $verificationMessage = "Account not verified. Please enter the verification code sent to your email.";
                $verificationCode = $newCode; // For development
            } else {
                // Direct login bypass for 2FA
                $fullUser = $db->selectOne("SELECT profile_image, address FROM users WHERE id = ?", [$user['id']]);
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['profile_image'] = $fullUser['profile_image'];
                $_SESSION['user_location'] = $fullUser['address'];
                $_SESSION['last_activity'] = time();
                header('Location: dashboard.php');
                exit;
            }
        } else {
            $errors[] = 'Invalid email/phone or password';
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
        
        .form-section {
            flex: 1;
            padding: 40px;
            background: #fff;
        }
        
        .image-section {
            flex: 1;
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .image-section img {
            max-width: 100%;
            height: auto;
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .form-header h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .form-header .logo {
            margin-bottom: 20px;
        }
        
        .form-header .logo img {
            height: 60px;
        }
        
        .form-header h3 {
            font-size: 20px;
            color: #333;
            margin-bottom: 5px;
            text-transform: uppercase;
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

        .password-field {
            position: relative;
        }

        .password-field .form-control {
            padding-right: 88px;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: #e67e22;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            padding: 4px 6px;
            transition: color 0.2s ease, transform 0.2s ease;
        }

        .toggle-password:hover {
            color: #ca6f1e;
            transform: translateY(-50%) scale(1.04);
        }

        .loginImage {
            height: 400px;
        }
        .btn {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: #222;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #000;
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
        
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .password-requirements {
            margin-top: 10px;
            font-size: 14px;
            color: #666;
        }
        
        .password-requirements ul {
            list-style-type: disc;
            margin-left: 20px;
        }
        
        .verification-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .text-right {
            text-align: right;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: #fff;
            padding: 40px;
            border-radius: 10px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        
        .modal-content .form-header {
            margin-bottom: 20px;
        }
        
        .modal-content .form-group {
            margin-bottom: 20px;
        }
        
        .modal-content .btn {
            margin-top: 10px;
        }
        
        .modal-content .btn.cancel {
            background-color: #666;
        }
        
        .modal-content .btn.cancel:hover {
            background-color: #555;
        }
        
        .modal-content .alert {
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                max-width: 500px;
            }
            
            .image-section {
                display: none;
            }
            
            .modal-content {
                max-width: 90%;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-section">
            <div class="form-header">
                <h2>Welcome Back!</h2>
                <div class="logo">
                    <a href="../index.php">
                        <img src="../assets/images/logo.png" alt="Homemade Food Delivery">
                    </a>
                </div>
                <h3>CUSTOMER LOG IN</h3>
            </div>
            
            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success">
                    <?php echo $successMessage; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($errors) && !empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (isset($needsVerification) && $needsVerification): ?>
                <div class="alert alert-info">
                    <?php echo $verificationMessage; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($needs2FA) && $needs2FA): ?>
                <div class="alert alert-info">
                    <?php echo $twoFactorMessage; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email/Phone</label>
                    <input type="text" id="email" name="email" class="form-control" placeholder="Enter your email/phone" value="<?php echo isset($emailOrPhone) ? $emailOrPhone : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-field">
                        <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
                        <button type="button" class="toggle-password" data-target="password">Show</button>
                    </div>
                    <div class="text-right">
                        <a href="#" onclick="showForgotPasswordModal('email')">Forgot Password</a>
                    </div>
                </div>
                
                <?php if (isset($needsVerification) || isset($needs2FA)): ?>
                <div class="form-group">
                    <label for="verification_code">Verification Code</label>
                    <input type="text" id="verification_code" name="verification_code" class="form-control" placeholder="Enter verification code" maxlength="6">
                </div>
                <?php endif; ?>
                
                <button type="submit" class="btn">Sign In</button>
                
                <div class="form-footer">
                    <p><a href="register.php">Create Account</a></p>
                </div>
            </form>
        </div>
        
        <div class="image-section">
            <img src="../assets/images/loginImage.jfif" alt="Chef Hat" class="loginImage">
        </div>
    </div>
    
    <!-- Forgot Password Modals -->
    <div id="forgotPasswordEmailModal" class="modal">
        <div class="modal-content">
            <div class="form-header">
                <h3>Reset Password</h3>
            </div>
            <div id="emailError" class="alert alert-danger" style="display: none;"></div>
            <form id="forgotPasswordEmailForm">
                <div class="form-group">
                    <label for="reset_email">Email</label>
                    <input type="email" id="reset_email" name="email" class="form-control" placeholder="Enter your registered email" required>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn">Continue</button>
                    <button type="button" class="btn cancel" onclick="closeModal('forgotPasswordEmailModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="forgotPasswordResetModal" class="modal">
        <div class="modal-content">
            <div class="form-header">
                <h3>Set New Password</h3>
            </div>
            <div id="resetError" class="alert alert-danger" style="display: none;"></div>
            <form id="forgotPasswordResetForm">
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <div class="password-field">
                        <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Enter new password" required>
                        <button type="button" class="toggle-password" data-target="new_password">Show</button>
                    </div>
                    <div class="password-requirements">
                        <p>Password must:</p>
                        <ul>
                            <li>Be at least 8 characters long</li>
                            <li>Contain at least one uppercase letter</li>
                            <li>Contain at least one lowercase letter</li>
                            <li>Contain at least one number</li>
                            <li>Contain at least one special character (e.g., !@#$%^&*)</li>
                        </ul>
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <div class="password-field">
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Confirm new password" required>
                        <button type="button" class="toggle-password" data-target="confirm_password">Show</button>
                    </div>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn">Set Password</button>
                    <button type="button" class="btn cancel" onclick="closeModal('forgotPasswordResetModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const target = document.getElementById(this.dataset.target);
                if (!target) {
                    return;
                }

                const isHidden = target.type === 'password';
                target.type = isHidden ? 'text' : 'password';
                this.textContent = isHidden ? 'Hide' : 'Show';
            });
        });

        function showForgotPasswordModal(step) {
            document.getElementById('forgotPasswordEmailModal').classList.remove('show');
            document.getElementById('forgotPasswordResetModal').classList.remove('show');
            document.getElementById('emailError').style.display = 'none';
            document.getElementById('resetError').style.display = 'none';

            if (step === 'email') {
                document.getElementById('forgotPasswordEmailModal').classList.add('show');
            } else if (step === 'reset') {
                document.getElementById('forgotPasswordResetModal').classList.add('show');
            }
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }

        // Client-side password validation
        function validatePasswordClient(password) {
            const minLength = password.length >= 8;
            const hasUppercase = /[A-Z]/.test(password);
            const hasLowercase = /[a-z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasSpecialChar = /[!@#$%^&*()_+\-=\[\]{};:'\",.<>?]/.test(password);

            if (!minLength) return "Password must be at least 8 characters long";
            if (!hasUppercase) return "Password must contain at least one uppercase letter";
            if (!hasLowercase) return "Password must contain at least one lowercase letter";
            if (!hasNumber) return "Password must contain at least one number";
            if (!hasSpecialChar) return "Password must contain at least one special character (e.g., !@#$%^&*)";
            return true;
        }

        // Handle Email Submission
        document.getElementById('forgotPasswordEmailForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const email = this.querySelector('input[name="email"]').value;

            fetch('../forgot-password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'check_email', email, user_type: 'customer' })
            })
            .then(res => {
                console.log('Response status:', res.status);
                res.clone().text().then(text => console.log('Raw response:', text));
                if (!res.ok) throw new Error('Network response was not ok: ' + res.statusText);
                return res.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.status === 'success') {
                    closeModal('forgotPasswordEmailModal');
                    showForgotPasswordModal('reset');
                } else {
                    document.getElementById('emailError').innerHTML = data.message;
                    document.getElementById('emailError').style.display = 'block';
                }
            })
            .catch(err => {
                console.error('Fetch error:', err);
                document.getElementById('emailError').innerHTML = 'Error: ' + err.message;
                document.getElementById('emailError').style.display = 'block';
            });
        });

        // Handle Password Reset
        document.getElementById('forgotPasswordResetForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const newPassword = this.querySelector('input[name="new_password"]').value;
            const confirmPassword = this.querySelector('input[name="confirm_password"]').value;

            // Client-side validation
            const passwordValidation = validatePasswordClient(newPassword);
            if (passwordValidation !== true) {
                document.getElementById('resetError').innerHTML = passwordValidation;
                document.getElementById('resetError').style.display = 'block';
                return;
            }

            if (newPassword !== confirmPassword) {
                document.getElementById('resetError').innerHTML = 'Passwords do not match.';
                document.getElementById('resetError').style.display = 'block';
                return;
            }

            fetch('../forgot-password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'reset_password', new_password: newPassword, user_type: 'customer' })
            })
            .then(res => {
                console.log('Response status:', res.status);
                res.clone().text().then(text => console.log('Raw response:', text));
                if (!res.ok) throw new Error('Network response was not ok: ' + res.statusText);
                return res.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.status === 'success') {
                    closeModal('forgotPasswordResetModal');
                    window.location.href = 'login.php?message=' + encodeURIComponent(data.message);
                } else {
                    document.getElementById('resetError').innerHTML = data.message;
                    document.getElementById('resetError').style.display = 'block';
                }
            })
            .catch(err => {
                console.error('Fetch error:', err);
                document.getElementById('resetError').innerHTML = 'Error: ' + err.message;
                document.getElementById('resetError').style.display = 'block';
            });
        });
    </script>
</body>
</html>
