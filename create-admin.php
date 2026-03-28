<?php
require_once 'includes/functions.php';

// Admin user details
$name = 'padelgod';
$email = 'padelgod@example.com'; // Need an email for the user system
$password = 'iluvpadel';
$isAdmin = true;

echo "<h2>Creating Admin User</h2>";

try {
    // Check if user already exists
    $existingUser = db()->fetch("SELECT * FROM users WHERE email = ? OR name = ?", [$email, $name]);
    
    if ($existingUser) {
        echo "<p style='color: orange;'>⚠️ User already exists. Updating to admin status...</p>";
        
        // Update existing user to admin
        $updated = db()->update('users', [
            'name' => $name,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'is_admin' => true
        ], 'email = ?', [$email]);
        
        if ($updated !== false) {
            echo "<p style='color: green;'>✅ User updated successfully!</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to update user</p>";
        }
    } else {
        // Create new admin user
        $userId = db()->insert('users', [
            'name' => $name,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'country_code' => 'XX',
            'is_admin' => true
        ]);
        
        if ($userId) {
            echo "<p style='color: green;'>✅ Admin user created successfully!</p>";
            echo "<p><strong>User ID:</strong> {$userId}</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to create user</p>";
        }
    }
    
    echo "<div style='background: #f0f9ff; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>Admin Login Details:</h3>";
    echo "<p><strong>Email:</strong> {$email}</p>";
    echo "<p><strong>Username:</strong> {$name}</p>";
    echo "<p><strong>Password:</strong> {$password}</p>";
    echo "<p><strong>Admin Status:</strong> YES</p>";
    echo "</div>";
    
    echo "<div style='margin: 20px 0;'>";
    echo "<a href='login.php' style='background: #5a9fd4; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin-right: 12px;'>Login Now</a>";
    echo "<a href='admin/dashboard.php' style='background: #10b981; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin-right: 12px;'>Admin Dashboard</a>";
    echo "<a href='index.php' style='background: #6b7280; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px;'>Back to Site</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr style='margin: 40px 0;'>";
echo "<p style='color: #6b7280; font-size: 14px;'>⚠️ <strong>Security Note:</strong> Delete this file after use for security reasons.</p>";
echo "<p style='color: #6b7280; font-size: 14px;'>🗑️ <code>rm create-admin.php</code></p>";
?>