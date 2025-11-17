<?php
// BookIT Manager Password Reset - MASTER FILE (consolidates reset_manager_password.php, quick_reset_password.php, and check_password.php)
// Complete password management for manager account

include 'config/db.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $manager_email = $_POST['manager_email'];
    
    // Validate inputs
    if (empty($new_password) || empty($confirm_password) || empty($manager_email)) {
        $error = "Please fill in all fields.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        // Check if manager exists with prepared statement
        $check_manager = get_single_result("SELECT * FROM users WHERE email = ? AND role = 'manager'", [$manager_email]);
        
        if ($check_manager) {
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update the password with prepared statement
            if (execute_query("UPDATE users SET password = ? WHERE email = ? AND role = 'manager'", [$hashed_password, $manager_email])) {
                $success = "Password updated successfully! You can now login with the new password.";
            } else {
                $error = "Failed to update password. Please try again.";
            }
        } else {
            $error = "Manager account not found with that email address.";
        }
    }
}

// Get all managers for reference
$managers = get_multiple_results("SELECT user_id, full_name, email, branch_id FROM users WHERE role = 'manager'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Manager Password - BookIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .reset-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .reset-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 600px;
            width: 100%;
        }
        .reset-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .reset-body {
            padding: 2rem;
        }
        .form-floating {
            margin-bottom: 1.5rem;
        }
        .form-floating > .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 1rem 0.75rem;
        }
        .form-floating > .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-reset {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }
        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        .manager-list {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
        }
        .manager-item {
            padding: 0.5rem 0;
            border-bottom: 1px solid #dee2e6;
        }
        .manager-item:last-child {
            border-bottom: none;
        }
        .password-check {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            margin-top: 1rem;
        }
        .password-test {
            margin: 0.5rem 0;
            padding: 0.5rem;
            background: white;
            border-left: 3px solid #007bff;
            border-radius: 4px;
        }
        .password-success {
            border-left-color: #28a745;
            color: #28a745;
        }
        .password-error {
            border-left-color: #dc3545;
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-card">
            <div class="reset-header">
                <h2 class="mb-0">
                    <i class="fas fa-key"></i> Reset Manager Password
                </h2>
                <p class="mb-0">Reset or manage password for manager accounts</p>
            </div>
            
            <div class="reset-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        <div class="mt-3">
                            <a href="public/login.php" class="btn btn-success">
                                <i class="fas fa-sign-in-alt"></i> Go to Login
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <form method="POST">
                        <div class="form-floating">
                            <input type="email" class="form-control" id="manager_email" name="manager_email" 
                                   placeholder="Manager Email" required>
                            <label for="manager_email">
                                <i class="fas fa-envelope"></i> Manager Email
                            </label>
                        </div>

                        <div class="form-floating">
                            <input type="password" class="form-control" id="new_password" name="new_password" 
                                   placeholder="New Password" required minlength="6">
                            <label for="new_password">
                                <i class="fas fa-lock"></i> New Password
                            </label>
                        </div>

                        <div class="form-floating">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   placeholder="Confirm Password" required minlength="6">
                            <label for="confirm_password">
                                <i class="fas fa-lock"></i> Confirm Password
                            </label>
                        </div>

                        <div class="d-grid">
                            <button type="submit" name="reset_password" class="btn btn-primary btn-reset btn-lg">
                                <i class="fas fa-key"></i> Reset Password
                            </button>
                        </div>
                    </form>

                    <!-- Show existing managers -->
                    <?php if (!empty($managers) && is_array($managers) && count($managers) > 0): ?>
                        <div class="manager-list">
                            <h6><i class="fas fa-users"></i> Existing Managers:</h6>
                            <?php foreach ($managers as $manager): ?>
                                <div class="manager-item">
                                    <strong><?php echo htmlspecialchars($manager['full_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($manager['email']); ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Password Check Section -->
                <div class="password-check">
                    <h6><i class="fas fa-clipboard-check"></i> Test Password Verification</h6>
                    <p class="text-muted small">Common test credentials to verify against database:</p>
                    <?php 
                    $admin = get_single_result("SELECT * FROM users WHERE email = ?", ['admin@bookit.com']);
                    if ($admin) {
                        $testPasswords = ['admin123', 'password', 'admin', '123456', 'Password123'];
                        foreach ($testPasswords as $testPassword) {
                            $match = password_verify($testPassword, $admin['password']) ? 'yes' : 'no';
                            $class = $match === 'yes' ? 'password-success' : 'password-error';
                            $icon = $match === 'yes' ? '✓' : '✗';
                            echo "<div class='password-test $class'><strong>$icon $testPassword:</strong> " . ($match === 'yes' ? 'MATCH' : 'No match') . "</div>";
                        }
                    }
                    ?>
                </div>

                <div class="text-center mt-4">
                    <a href="public/login.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Login
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
        });
    </script>
</body>
</html>
