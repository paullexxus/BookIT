<?php
// Check Password Hash in Database
include 'config/db.php';

echo "<h2>Password Hash Check</h2>";

// Get admin user from database
$admin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE email = 'admin@bookit.com'"));

if ($admin) {
    echo "<h3>Admin User Found:</h3>";
    echo "<p><strong>Email:</strong> " . $admin['email'] . "</p>";
    echo "<p><strong>Full Name:</strong> " . $admin['full_name'] . "</p>";
    echo "<p><strong>Role:</strong> " . $admin['role'] . "</p>";
    echo "<p><strong>Active:</strong> " . ($admin['is_active'] ? 'Yes' : 'No') . "</p>";
    echo "<p><strong>Password Hash:</strong> " . $admin['password'] . "</p>";
    
    echo "<hr>";
    echo "<h3>Password Verification Test:</h3>";
    
    $testPasswords = [
        'admin123',
        'password', 
        'admin',
        '123456',
        'Password123',
        'ADMIN123'
    ];
    
    foreach ($testPasswords as $testPassword) {
        echo "<p>Testing password: <strong>$testPassword</strong> - ";
        if (password_verify($testPassword, $admin['password'])) {
            echo "<span style='color: green;'>✓ MATCH!</span></p>";
        } else {
            echo "<span style='color: red;'>✗ No match</span></p>";
        }
    }
    
    echo "<hr>";
    echo "<h3>Reset Password:</h3>";
    echo "<form method='POST'>";
    echo "<p>New Password: <input type='password' name='new_password' value='admin123' required></p>";
    echo "<button type='submit' name='reset_password' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px;'>Reset Password</button>";
    echo "</form>";
    
    if (isset($_POST['reset_password'])) {
        $newPassword = $_POST['new_password'];
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $updateSql = "UPDATE users SET password = ? WHERE email = 'admin@bookit.com'";
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param("s", $hashedPassword);
        
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✓ Password updated successfully!</p>";
            echo "<p><strong>New credentials:</strong></p>";
            echo "<p>Email: admin@bookit.com</p>";
            echo "<p>Password: $newPassword</p>";
            echo "<p><a href='public/login.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Try Login Now</a></p>";
        } else {
            echo "<p style='color: red;'>✗ Failed to update password: " . mysqli_error($conn) . "</p>";
        }
        $stmt->close();
    }
    
} else {
    echo "<p style='color: red;'>✗ No admin user found with email: admin@bookit.com</p>";
    
    // Show all users
    echo "<h3>All Users in Database:</h3>";
    $users = mysqli_query($conn, "SELECT email, full_name, role FROM users");
    if (mysqli_num_rows($users) > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Email</th><th>Name</th><th>Role</th></tr>";
        while ($user = mysqli_fetch_assoc($users)) {
            echo "<tr>";
            echo "<td>" . $user['email'] . "</td>";
            echo "<td>" . $user['full_name'] . "</td>";
            echo "<td>" . $user['role'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>No users found in database</p>";
    }
}

echo "<hr>";
echo "<h3>Quick Actions:</h3>";
echo "<p><a href='setup_database.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Setup Database</a></p>";
echo "<p><a href='public/login.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;'>Go to Login</a></p>";
?>


