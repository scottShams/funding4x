<?php
header('Content-Type: application/json');

require_once 'database.php';
require_once 'email_verification.php';

$pdo = getPDO();

$email = trim($_POST['email'] ?? '');

if (empty($email)) {
    echo json_encode(['status' => 'error', 'message' => 'Email is required']);
    exit;
}

$stmt = $pdo->prepare("SELECT id, name, email_verified, verification_token, verification_token_expires FROM waitlist_users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['status' => 'error', 'message' => 'User not found']);
    exit;
}

if ($user['email_verified']) {
    echo json_encode(['status' => 'error', 'message' => 'Email already verified']);
    exit;
}

// Always create new token, or check if expired
$tokenMissing = empty($user['verification_token']) || empty($user['verification_token_expires']);
$tokenExpired = (!empty($user['verification_token_expires']) && strtotime($user['verification_token_expires']) < time());

if ($tokenMissing || $tokenExpired) {
    $verificationToken = EmailVerification::createVerificationToken($user['id'], $pdo);
} else {
    $verificationToken = $user['verification_token'];
}

$emailSent = EmailVerification::sendVerificationEmail($user['email'], $user['name'], $verificationToken);

echo json_encode([
    'status' => 'success',
    'message' => 'Verification email sent. Please check your email.',
    'email_sent' => $emailSent
]);
?>