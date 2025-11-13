<?php
// Database config
$host = 'localhost';
$dbname = 'funding4x';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// Admin details
$name = 'Admin';
$email = 'admin@gmail.com';
$country = 'Admin';
$password_hash = password_hash('admin123', PASSWORD_DEFAULT);

// Check if admin already exists
$stmt = $pdo->prepare("SELECT id FROM waitlist_users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    echo 'Admin account already exists.';
    exit;
}

// Insert admin
$stmt = $pdo->prepare("INSERT INTO waitlist_users (name, email, country, password) VALUES (?, ?, ?, ?)");
$stmt->execute([$name, $email, $country, $password_hash]);

echo 'Admin account created successfully. Email: admin@gmail.com, Password: admin123';
?>