<?php
require_once 'config/database.php';

// Create admin user
$email = 'admin@findajob.ng';
$password = 'password';
$first_name = 'Super';
$last_name = 'Admin';

// Hash password
$password_hash = password_hash($password, PASSWORD_DEFAULT);

try {
    // Check if admin already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        echo "Admin user already exists!\n";
        echo "Email: $email\n";
        echo "Password: $password\n";
    } else {
        // Insert admin user
        $stmt = $pdo->prepare("
            INSERT INTO users (user_type, email, password_hash, first_name, last_name, email_verified, is_active)
            VALUES ('admin', ?, ?, ?, ?, 1, 1)
        ");
        $stmt->execute([$email, $password_hash, $first_name, $last_name]);
        
        echo "✅ Admin user created successfully!\n\n";
        echo "Login at: http://localhost/findajob/admin/login.php\n\n";
        echo "Credentials:\n";
        echo "Email: $email\n";
        echo "Password: $password\n\n";
        echo "⚠️ Please change this password after first login!\n";
    }
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
