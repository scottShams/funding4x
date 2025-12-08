<?php
session_start();
require_once 'database.php';
require_once 'env_loader.php';
require_once 'email_verification.php';

header('Content-Type: application/json');

try {
    $pdo = getPDO();

    // Get POST data
    $email = trim($_POST['email'] ?? '');

    // Validate required fields
    if (empty($email)) {
        throw new Exception('Email is required');
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address');
    }

    // Check if user exists
    $stmt = $pdo->prepare("SELECT id, name, email FROM waitlist_users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // For security, don't reveal if email exists or not
        echo json_encode([
            'status' => 'success',
            'message' => 'If an account with that email exists, a password reset link has been sent.'
        ]);
        exit;
    }

    // Generate password reset token
    $resetToken = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour')); // 1 hour expiry

    // Store token in database
    $stmt = $pdo->prepare("
        UPDATE waitlist_users
        SET password_reset_token = ?, password_reset_expires = ?
        WHERE id = ?
    ");
    $stmt->execute([$resetToken, $expiresAt, $user['id']]);

    // Send password reset email
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $resetLink = $protocol . '://' . $host . '/reset_password.php?token=' . urlencode($resetToken);
    $emailSent = EmailVerification::sendPasswordResetEmail($user['email'], $user['name'], $resetLink);

    if (!$emailSent) {
        throw new Exception('Failed to send password reset email. Please try again.');
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Password reset link has been sent to your email.'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

?>