<?php
// BookIT Login System
// Multi-branch Condo Rental Reservation System

// CRITICAL: Set error handlers FIRST before anything else
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    echo "PHP ERROR [$errno]: $errstr in $errfile:$errline\n";
    return true;
});

// Ensure constants (session name, security headers) are set before sessions start
require_once __DIR__ . '/../config/constants.php';
session_start();

include_once '../config/db.php';
include_once '../includes/security.php';
clearLoginPageCache();

require_once '../includes/components/form_errors.php';
require_once '../includes/functions.php';

$error = '';
$success = '';

// Google OAuth Configuration
$oauth_config = include '../config/OAuth.php';
$google_client_id = $oauth_config['google']['client_id'];
$google_client_secret = $oauth_config['google']['client_secret'];
$google_redirect_uri = 'http://localhost/BookIT/public/login.php';

// Handle Forgot Password Request
if (isset($_POST['forgot_password'])) {
    $email = trim($_POST['forgot_email']);
    
    if (empty($email)) {
        $error = "Please enter your email address.";
    } else {
        // Check if email exists and is active
        $query = "SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            
            // Generate reset token
            $reset_token = bin2hex(random_bytes(32));
            $token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store token in database
            $update_query = "UPDATE users SET reset_token = ?, token_expiry = ? WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("ssi", $reset_token, $token_expiry, $user['user_id']);
            
            if ($update_stmt->execute()) {
                // Send reset email (in a real application)
                $reset_link = "http://localhost/BookIT/public/reset_password.php?token=" . $reset_token;
                
                // For demo purposes, we'll show the link instead of sending email
                $success = "Password reset link has been generated. <a href='$reset_link' class='alert-link'>Click here to reset your password</a>. This link will expire in 1 hour.";
            } else {
                $error = "Error generating reset token. Please try again.";
            }
            $update_stmt->close();
        } else {
            $error = "No active account found with that email address.";
        }
        $stmt->close();
    }
}

// Handle Login
if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // FIXED: Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Security validation failed. Please try again.";
    }
    // Validate inputs
    else if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        // Check if user exists and is active
        $query = "SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Start secure session
                session_regenerate_id(true);
                
                // Store session data
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['fullname'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];
                
                // Redirect by role
                if ($user['role'] == 'admin') {
                    header("Location: ../admin/admin_dashboard.php");
                } elseif ($user['role'] == 'manager' || $user['role'] == 'host') {
                    header("Location: ../host/host_dashboard.php");
                } elseif ($user['role'] == 'renter') {
                    header("Location: index.php");
                } else {
                    header("Location: login.php");
                }
                exit();
            } else {
                $error = "Incorrect password.";
            }
        } else {
            $error = "No account found with that email or account is inactive.";
        }
        $stmt->close();
    }
}

// Handle Google OAuth callback
if (isset($_GET['code'])) {
    $code = $_GET['code'];
    
    // Exchange code for access token
    $token_url = "https://oauth2.googleapis.com/token";
    $token_data = [
        'code' => $code,
        'client_id' => $google_client_id,
        'client_secret' => $google_client_secret,
        'redirect_uri' => $google_redirect_uri,
        'grant_type' => 'authorization_code'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $token_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($token_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $token_response = curl_exec($ch);
    curl_close($ch);
    
    $token_data = json_decode($token_response, true);
    
    if (isset($token_data['access_token'])) {
        // Get user info from Google
        $user_info_url = "https://www.googleapis.com/oauth2/v2/userinfo";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $user_info_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token_data['access_token']
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $user_info_response = curl_exec($ch);
        curl_close($ch);
        
        $user_info = json_decode($user_info_response, true);
        
        if (isset($user_info['email'])) {
            $google_email = $user_info['email'];
            $google_name = $user_info['name'] ?? 'Google User';
            $google_picture = $user_info['picture'] ?? '';
            
            // Check if user exists in database
            $query = "SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $google_email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && mysqli_num_rows($result) == 1) {
                // User exists, log them in
                $user = mysqli_fetch_assoc($result);
                
                // Start secure session
                session_regenerate_id(true);
                
                // Store session data
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['fullname'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['login_method'] = 'google';
                
                // Redirect by role
                if ($user['role'] == 'admin') {
                    header("Location: ../admin/admin_dashboard.php");
                } elseif ($user['role'] == 'manager' || $user['role'] == 'host') {
                    header("Location: ../host/host_dashboard.php");
                } elseif ($user['role'] == 'renter') {
                    header("Location: index.php");
                } else {
                    header("Location: login.php");
                }
                exit();
            } else {
                // User doesn't exist, create new account as renter
                // Check if email already exists (inactive account)
                $check_query = "SELECT user_id FROM users WHERE email = ?";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bind_param("s", $google_email);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    // Email exists but account is inactive, activate it
                    $update_query = "UPDATE users SET is_active = 1, full_name = ? WHERE email = ?";
                    $update_stmt = $conn->prepare($update_query);
                    $update_stmt->bind_param("ss", $google_name, $google_email);
                    
                    if ($update_stmt->execute()) {
                        // Get the updated user
                        $query = "SELECT * FROM users WHERE email = ? LIMIT 1";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("s", $google_email);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $user = mysqli_fetch_assoc($result);
                        
                        // Start secure session
                        session_regenerate_id(true);
                        
                        // Store session data
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['fullname'] = $user['full_name'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['login_method'] = 'google';
                        
                        header("Location: index.php");
                        exit();
                    }
                    $update_stmt->close();
                } else {
                    // Create new user
                    $default_password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
                    $phone = ''; // Google doesn't provide phone number
                    
                    $insert_query = "INSERT INTO users (full_name, email, password, phone, role, is_active, created_at) 
                                   VALUES (?, ?, ?, ?, 'renter', 1, NOW())";
                    $stmt = $conn->prepare($insert_query);
                    $stmt->bind_param("ssss", $google_name, $google_email, $default_password, $phone);
                    
                    if ($stmt->execute()) {
                        $new_user_id = $stmt->insert_id;
                        
                        // Start secure session
                        session_regenerate_id(true);
                        
                        // Store session data
                        $_SESSION['user_id'] = $new_user_id;
                        $_SESSION['fullname'] = $google_name;
                        $_SESSION['role'] = 'renter';
                        $_SESSION['email'] = $google_email;
                        $_SESSION['login_method'] = 'google';
                        
                        header("Location: index.php");
                        exit();
                    } else {
                        $error = "Failed to create account with Google login.";
                    }
                }
                $check_stmt->close();
            }
            $stmt->close();
        } else {
            $error = "Failed to get user information from Google.";
        }
    } else {
        $error = "Failed to authenticate with Google.";
    }
}

// Generate Google OAuth URL properly
$google_oauth_params = [
    'client_id' => $google_client_id,
    'redirect_uri' => $google_redirect_uri,
    'response_type' => 'code',
    'scope' => 'email profile',
    'access_type' => 'online',
    'prompt' => 'select_account'
];
$google_oauth_url = "https://accounts.google.com/o/oauth2/auth?" . http_build_query($google_oauth_params);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookIT | Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/public/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h2><i class="fas fa-building"></i> BookIT</h2>
            <p>Multi-Branch Condo Rental and Reservation Management</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <!-- Google Login Button - Only show if credentials are configured -->
        <?php if ($google_client_id !== 'YOUR_GOOGLE_CLIENT_ID_HERE'): ?>
        <a href="<?php echo $google_oauth_url; ?>" class="btn btn-google">
            <i class="fab fa-google me-2"></i> Login with Google
        </a>

        <div class="divider">
            <span class="divider-text">OR</span>
        </div>
        <?php endif; ?>

        <!-- Regular Login Form -->
        <form method="POST" id="loginForm">
            <!-- CSRF Token for security -->
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" class="form-control" name="email" 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                       placeholder="Enter your email" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" class="form-control" name="password" 
                       placeholder="Enter your password" required>
            </div>
            
            <div class="mb-3 text-end">
                <a class="forgot-password-link" onclick="toggleForgotPassword()">
                    <i class="fas fa-key me-1"></i> Forgot Password?
                </a>
            </div>
            
            <button type="submit" name="login" class="btn btn-login">
                <i class="fas fa-sign-in-alt"></i> Login with Email
            </button>
        </form>

        <!-- Forgot Password Form -->
        <form method="POST" id="forgotPasswordForm" class="forgot-password-form">
            <div class="mb-3">
                <label class="form-label">Enter your email address</label>
                <input type="email" class="form-control" name="forgot_email" 
                       value="<?php echo isset($_POST['forgot_email']) ? htmlspecialchars($_POST['forgot_email']) : ''; ?>" 
                       placeholder="Enter your registered email" required>
                <div class="form-text">We'll send you a link to reset your password.</div>
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" name="forgot_password" class="btn btn-primary">
                    <i class="fas fa-paper-plane me-2"></i> Send Reset Link
                </button>
                <button type="button" class="btn btn-outline-secondary" onclick="toggleForgotPassword()">
                    <i class="fas fa-arrow-left me-2"></i> Back to Login
                </button>
            </div>
        </form>

        <div class="text-center mt-4">
            <p class="mb-0">Don't have an account? 
                <a href="register.php" class="text-decoration-none">
                    <strong>Register here</strong>
                </a>
            </p>
        </div>
        
        <div class="text-center mt-3">
            <a href="index.php" class="text-decoration-none">
                <i class="fas fa-home"></i> Back to Home
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleForgotPassword() {
            const loginForm = document.getElementById('loginForm');
            const forgotForm = document.getElementById('forgotPasswordForm');
            
            if (forgotForm.style.display === 'none' || forgotForm.style.display === '') {
                loginForm.style.display = 'none';
                forgotForm.style.display = 'block';
            } else {
                loginForm.style.display = 'block';
                forgotForm.style.display = 'none';
            }
        }

        // Show forgot password form if there's an error with it
        <?php if (isset($_POST['forgot_password'])): ?>
            document.addEventListener('DOMContentLoaded', function() {
                toggleForgotPassword();
            });
        <?php endif; ?>
    </script>
</body>
</html>