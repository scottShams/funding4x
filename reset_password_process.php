<?php
session_start();
require_once 'database.php';
require_once 'env_loader.php';

header('Content-Type: application/json');

try {
    $pdo = getPDO();

    // Get POST data
    $token = trim($_POST['token'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate required fields
    if (empty($token) || empty($password)) {
        throw new Exception('Token and password are required');
    }

    // Validate password
    if (strlen($password) < 8) {
        throw new Exception('Password must be at least 8 characters long');
    }

    // Find user by reset token
    $stmt = $pdo->prepare("
        SELECT id, name, email, password_reset_expires
        FROM waitlist_users
        WHERE password_reset_token = ?
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('Invalid or expired reset token');
    }

    // Check if token has expired
    if (strtotime($user['password_reset_expires']) < time()) {
        throw new Exception('Reset token has expired. Please request a new password reset.');
    }

    // Hash the new password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Update password and clear reset token
    $stmt = $pdo->prepare("
        UPDATE waitlist_users
        SET password = ?, password_reset_token = NULL, password_reset_expires = NULL
        WHERE id = ?
    ");
    $stmt->execute([$hashedPassword, $user['id']]);

    echo json_encode([
        'status' => 'success',
        'message' => 'Password updated successfully'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>