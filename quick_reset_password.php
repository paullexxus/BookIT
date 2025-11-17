<?php
// Quick Password Reset Script
// Run this from command line or browser to quickly reset manager password

include 'config/db.php';

echo "<h2>BookIT Quick Password Reset</h2>";

// Check if manager exists
$managers = mysqli_query($conn, "SELECT user_id, full_name, email FROM users WHERE role = 'manager'");

if (mysqli_num_rows($managers) == 0) {
    echo "<p style='color: red;'>No manager accounts found!</p>";
    exit();
}

echo "<h3>Available Managers:</h3>";
while ($manager = mysqli_fetch_assoc($managers)) {
    echo "<p><strong>ID:</strong> {$manager['user_id']} | <strong>Name:</strong> {$manager['full_name']} | <strong>Email:</strong> {$manager['email']}</p>";
}

// Set new password (change this to your desired password)
$new_password = "manager123"; // Change this password
$manager_email = "manager@bookit.com"; // Change this to the manager's email

echo "<h3>Resetting Password...</h3>";
echo "<p><strong>Manager Email:</strong> $manager_email</p>";
echo "<p><strong>New Password:</strong> $new_password</p>";

// Hash the new password
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Update the password
$update_sql = "UPDATE users SET password = '$hashed_password' WHERE email = '$manager_email' AND role = 'manager'";

if (mysqli_query($conn, $update_sql)) {
    if (mysqli_affected_rows($conn) > 0) {
        echo "<p style='color: green;'>✓ Password updated successfully!</p>";
        echo "<p><strong>You can now login with:</strong></p>";
        echo "<p>Email: <strong>$manager_email</strong></p>";
        echo "<p>Password: <strong>$new_password</strong></p>";
        echo "<p><a href='public/login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login</a></p>";
    } else {
        echo "<p style='color: red;'>✗ No manager found with email: $manager_email</p>";
        echo "<p>Please check the email address above and try again.</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Error updating password: " . mysqli_error($conn) . "</p>";
}

echo "<hr>";
echo "<h3>Instructions:</h3>";
echo "<ol>";
echo "<li>If the password reset was successful, use the credentials above to login</li>";
echo "<li>After logging in, you can change the password to something more secure</li>";
echo "<li>Delete this file (quick_reset_password.php) for security after use</li>";
echo "</ol>";
?>
