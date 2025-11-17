<?php
// BookIT Login Debug Script
// Use this to debug login issues

include 'config/db.php';

echo "<h2>BookIT Login Debug</h2>";

// Test database connection
if ($conn->connect_error) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $conn->connect_error . "</p>";
    exit();
} else {
    echo "<p style='color: green;'>✓ Database connection successful</p>";
}

// Check if users table exists
$result = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
if (mysqli_num_rows($result) > 0) {
    echo "<p style='color: green;'>✓ Users table exists</p>";
} else {
    echo "<p style='color: red;'>✗ Users table does not exist</p>";
    exit();
}

// Check admin user
$admin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE role = 'admin'"));
if ($admin) {
    echo "<p style='color: green;'>✓ Admin user exists</p>";
    echo "<p>Email: " . $admin['email'] . "</p>";
    echo "<p>Full Name: " . $admin['full_name'] . "</p>";
    echo "<p>Role: " . $admin['role'] . "</p>";
    echo "<p>Active: " . ($admin['is_active'] ? 'Yes' : 'No') . "</p>";
    echo "<p>Password Hash: " . substr($admin['password'], 0, 20) . "...</p>";
    
    // Test password verification
    $testPassword = 'admin123';
    if (password_verify($testPassword, $admin['password'])) {
        echo "<p style='color: green;'>✓ Password verification works</p>";
    } else {
        echo "<p style='color: red;'>✗ Password verification failed</p>";
        echo "<p>Testing with different passwords...</p>";
        
        // Try common passwords
        $testPasswords = ['admin123', 'password', 'admin', '123456'];
        foreach ($testPasswords as $pwd) {
            if (password_verify($pwd, $admin['password'])) {
                echo "<p style='color: green;'>✓ Password found: $pwd</p>";
                break;
            }
        }
    }
} else {
    echo "<p style='color: red;'>✗ No admin user found</p>";
}

// List all users
echo "<hr><h3>All Users:</h3>";
$users = mysqli_query($conn, "SELECT user_id, full_name, email, role, is_active FROM users");
if (mysqli_num_rows($users) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Active</th></tr>";
    while ($user = mysqli_fetch_assoc($users)) {
        echo "<tr>";
        echo "<td>" . $user['user_id'] . "</td>";
        echo "<td>" . $user['full_name'] . "</td>";
        echo "<td>" . $user['email'] . "</td>";
        echo "<td>" . $user['role'] . "</td>";
        echo "<td>" . ($user['is_active'] ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>No users found in database</p>";
}

echo "<hr>";
echo "<h3>Quick Actions:</h3>";
echo "<p><a href='setup_database.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Setup Database</a></p>";
echo "<p><a href='public/login.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;'>Go to Login</a></p>";

// Test login with admin credentials
echo "<hr>";
echo "<h3>Test Login:</h3>";
echo "<form method='POST' style='background: #f8f9fa; padding: 20px; border-radius: 5px;'>";
echo "<p><strong>Test Admin Login:</strong></p>";
echo "<p>Email: admin@bookit.com</p>";
echo "<p>Password: admin123</p>";
echo "<input type='hidden' name='test_email' value='admin@bookit.com'>";
echo "<input type='hidden' name='test_password' value='admin123'>";
echo "<button type='submit' name='test_login' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px;'>Test Login</button>";
echo "</form>";

if (isset($_POST['test_login'])) {
    $email = $_POST['test_email'];
    $password = $_POST['test_password'];
    
    $query = "SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        if (password_verify($password, $user['password'])) {
            echo "<p style='color: green;'>✓ Login test successful!</p>";
        } else {
            echo "<p style='color: red;'>✗ Password verification failed</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ User not found or inactive</p>";
    }
    $stmt->close();
}
?>


