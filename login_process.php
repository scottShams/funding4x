<?php
session_start();
require_once 'database.php';
require_once 'env_loader.php';

header('Content-Type: application/json');

try {
    $pdo = getPDO();

    // Get POST data
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate required fields
    if (empty($email) || empty($password)) {
        throw new Exception('Email and password are required');
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address');
    }

    // Verify reCAPTCHA
    $recaptchaSecret = getenv('RECAPTCHA_SECRET_KEY') ?: 'your_recaptcha_secret_key_here';
    $recaptchaUrl = 'https://www.google.com/recaptcha/api/siteverify';
    $recaptchaData = [
        'secret' => $recaptchaSecret,
        'response' => $recaptcha
    ];

    $recaptchaOptions = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($recaptchaData)
        ]
    ];

    $recaptchaContext = stream_context_create($recaptchaOptions);
    $recaptchaResult = file_get_contents($recaptchaUrl, false, $recaptchaContext);
    $recaptchaResponse = json_decode($recaptchaResult, true);

    if (!$recaptchaResponse['success']) {
        throw new Exception('reCAPTCHA verification failed');
    }

    // Check if user exists and is verified
    $stmt = $pdo->prepare("SELECT id, name, email, password, email_verified FROM waitlist_users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('Invalid email or password');
    }

    if (!$user['email_verified']) {
        throw new Exception('Please verify your email address before logging in');
    }

    // Verify password
    if (!password_verify($password, $user['password'])) {
        throw new Exception('Invalid email or password');
    }

    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['name'];

    echo json_encode([
        'status' => 'success',
        'message' => 'Login successful!',
        'redirect' => 'referral_dashboard.php'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>