<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/env.php';

$db = Database::getInstance()->getConnection();

// Admin credentials
$adminEmail = 'admin@unspend.com';
$adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
$adminName = 'Admin User';

// Check if admin already exists
$stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$adminEmail]);
$existingAdmin = $stmt->fetch();

if (!$existingAdmin) {
    // Insert admin user
    $stmt = $db->prepare("INSERT INTO users (email, name, password, income, email_verified, created_at) VALUES (?, ?, ?, 0, 1, NOW())");
    $stmt->execute([$adminEmail, $adminName, $adminPassword]);

    echo "Admin user created successfully!\n";
    echo "Email: $adminEmail\n";
    echo "Password: admin123\n";
} else {
    echo "Admin user already exists.\n";
}
?>