<?php
// reset_password.php
session_start();
include_once '../config/db.php';

$error = '';
$success = '';

if (isset($_POST['reset_password'])) {
    $token = $_POST['token'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($new_password) || empty($confirm_password)) {
        $error = "Please fill in all fields.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        // Check if token is valid and not expired
        $query = "SELECT * FROM users WHERE reset_token = ? AND token_expiry > NOW() LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            
            // Update password and clear reset token
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password = ?, reset_token = NULL, token_expiry = NULL WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("si", $hashed_password, $user['user_id']);
            
            if ($update_stmt->execute()) {
                $success = "Password has been reset successfully. You can now <a href='login.php'>login</a> with your new password.";
            } else {
                $error = "Error resetting password. Please try again.";
            }
            $update_stmt->close();
        } else {
            $error = "Invalid or expired reset token. Please request a new reset link.";
        }
        $stmt->close();
    }
}

// Check if token is provided via GET
$token = $_GET['token'] ?? '';
if ($token) {
    // Verify token is valid
    $query = "SELECT * FROM users WHERE reset_token = ? AND token_expiry > NOW() LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result || mysqli_num_rows($result) == 0) {
        $error = "Invalid or expired reset token. Please request a new reset link.";
        $token = ''; // Clear invalid token
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - BookIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .reset-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="text-center mb-4">
            <h2><i class="fas fa-key"></i> Reset Password</h2>
            <p class="text-muted">Enter your new password</p>
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

        <?php if ($token && !$success): ?>
            <form method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input type="password" class="form-control" name="new_password" 
                           placeholder="Enter new password" required minlength="6">
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" class="form-control" name="confirm_password" 
                           placeholder="Confirm new password" required minlength="6">
                </div>
                
                <button type="submit" name="reset_password" class="btn btn-primary w-100">
                    <i class="fas fa-save me-2"></i> Reset Password
                </button>
            </form>
        <?php elseif (!$token && !$success): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                No valid reset token found. Please request a new password reset link from the login page.
            </div>
        <?php endif; ?>

        <div class="text-center mt-3">
            <a href="login.php" class="text-decoration-none">
                <i class="fas fa-arrow-left me-2"></i> Back to Login
            </a>
        </div>
    </div>
</body>
</html>