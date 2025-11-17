<?php
include_once "../includes/auth.php";
include_once "../includes/functions.php";

$error = '';
$success = '';

if (isset($_POST['register'])) {
    // FIXED: Use proper validation function from functions.php
    $fullname = sanitize_input($_POST['fullname'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Security validation failed. Please try again.";
    }
    // Input validation using the validation function
    else if (!empty($error = registerValidation($fullname, $email, $password, $confirm_password, $phone))) {
        // Error already set by registerValidation
    }
    else {
        // Check if email exists using prepared statement
        $check_query = "SELECT user_id FROM users WHERE email = ? LIMIT 1";
        $existing_user = get_single_result($check_query, [$email]);
        
        if ($existing_user) {
            $error = "Email already registered! <a href='login.php' class='alert-link'>Login here</a> or use a different email.";
        }
        else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            // FIXED: Use prepared statement for user insertion
            $insert_query = "INSERT INTO users (full_name, email, password, phone, role, is_active) VALUES (?, ?, ?, ?, 'renter', 1)";
            $result = execute_query($insert_query, [$fullname, $email, $hashed_password, $phone]);
            
            if ($result) {
                $success = "Account created successfully! <a href='login.php' class='alert-link'>Click here to login</a>";
                // Clear form fields on success
                $fullname = '';
                $email = '';
                $phone = '';
            }
            else {
                $error = "Registration failed. Please try again or contact support.";
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
    <title>Create Account - BookIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/public/register.css">
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="row g-0">
                <!-- Left Side - Registration Form -->
                <div class="col-lg-6">
                    <div class="register-body">
                        <div class="text-center mb-4">
                            <h2 class="fw-bold text-primary">
                                <i class="fas fa-user-plus"></i> Create Account
                            </h2>
                            <p class="text-muted">Join BookIT and start your rental journey</p>
                        </div>

                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <?php if(isset($success)): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                            </div>
                        <?php endif; ?>

                        <form action="" method="POST" id="registerForm">
                            <!-- CSRF Token for security -->
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="fullname" name="fullname" 
                                       placeholder="Full Name" required value="<?php echo isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : ''; ?>">
                                <label for="fullname">
                                    <i class="fas fa-user"></i> Full Name
                                </label>
                                <small class="form-text text-muted d-block mt-1">Must be at least 2 characters</small>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="email" class="form-control" id="email" name="email" 
                                       placeholder="Email Address" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                <label for="email">
                                    <i class="fas fa-envelope"></i> Email Address
                                </label>
                                <small class="form-text text-muted d-block mt-1">Enter a valid email address</small>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       placeholder="Phone Number" required pattern="[0-9\+\s\-\(\)]{10,}" 
                                       title="Enter a valid phone number (10+ digits)" 
                                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                                <label for="phone">
                                    <i class="fas fa-phone"></i> Phone Number
                                </label>
                                <small class="form-text text-muted d-block mt-1">Format: +63xxxxxxxxxx or 0xxxxxxxxxx</small>
                            </div>

                            <!-- Password Field with Toggle -->
                            <div class="form-floating mb-3 password-input-group">
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Password" required>
                                <label for="password">
                                    <i class="fas fa-lock"></i> Password
                                </label>
                                <button type="button" class="password-toggle" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>

                            <!-- Password Requirements -->
                            <div class="password-requirements">
                                <h6 class="mb-3">Password Requirements:</h6>
                                <div class="requirement invalid" id="req-length">
                                    <i class="fas fa-times"></i>
                                    <span>At least 8 characters long</span>
                                </div>
                                <div class="requirement invalid" id="req-uppercase">
                                    <i class="fas fa-times"></i>
                                    <span>One uppercase letter</span>
                                </div>
                                <div class="requirement invalid" id="req-lowercase">
                                    <i class="fas fa-times"></i>
                                    <span>One lowercase letter</span>
                                </div>
                                <div class="requirement invalid" id="req-number">
                                    <i class="fas fa-times"></i>
                                    <span>One number</span>
                                </div>
                                <div class="requirement invalid" id="req-special">
                                    <i class="fas fa-times"></i>
                                    <span>One special character</span>
                                </div>
                            </div>

                            <!-- Confirm Password Field with Toggle -->
                            <div class="form-floating mb-3 password-input-group">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       placeholder="Confirm Password" required>
                                <label for="confirm_password">
                                    <i class="fas fa-lock"></i> Confirm Password
                                </label>
                                <button type="button" class="password-toggle" id="toggleConfirmPassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>

                            <div class="d-grid">
                                <button type="submit" name="register" class="btn btn-primary btn-register btn-lg">
                                    <i class="fas fa-user-plus"></i> Create Account
                                </button>
                            </div>
                        </form>

                        <div class="text-center mt-4">
                            <p class="mb-0">Already have an account? 
                                <a href="login.php" class="login-link">Login Here</a>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Right Side - Features -->
                <div class="col-lg-6" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                    <div class="p-5 h-100 d-flex flex-column justify-content-center">
                        <h3 class="fw-bold text-primary mb-4">
                            <i class="fas fa-building"></i> Why Choose BookIT?
                        </h3>
                        <ul class="feature-list">
                            <li>
                                <i class="fas fa-check"></i>
                                Browse available units across multiple branches
                            </li>
                            <li>
                                <i class="fas fa-check"></i>
                                Real-time availability and pricing
                            </li>
                            <li>
                                <i class="fas fa-check"></i>
                                Secure online payments
                            </li>
                            <li>
                                <i class="fas fa-check"></i>
                                Book amenities like pools and gyms
                            </li>
                            <li>
                                <i class="fas fa-check"></i>
                                Automated notifications
                            </li>
                            <li>
                                <i class="fas fa-check"></i>
                                24/7 customer support
                            </li>
                        </ul>
                        
                        <div class="mt-4">
                            <a href="index.php" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left"></i> Back to Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/public/register.js"></script>
</body>
</html>