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
    // $recaptcha = $_POST['recaptcha'] ?? '';

    // Validate required fields
    if (empty($email) || empty($password)) {
        throw new Exception('Email and password are required');
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address');
    }

    // Verify reCAPTCHA
    // $recaptchaSecret = EnvLoader::get('RECAPTCHA_SECRET_KEY', 'your_recaptcha_secret_key_here');
    // $recaptchaUrl = "https://www.google.com/recaptcha/api/siteverify?secret=$recaptchaSecret&response=$recaptcha";
    // $recaptchaResult = file_get_contents($recaptchaUrl);
    // $recaptchaResponse = json_decode($recaptchaResult, true);

    // if (!$recaptchaResponse['success']) {
    //     throw new Exception('reCAPTCHA verification failed');
    // }

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

    // Determine redirect based on user type and cookies
    if (isset($user['paid_user']) && (int)$user['paid_user'] === 1) {
        $redirect = 'my_dashboard.php';
    } else {
        // If there's an active checkout price, force checkout redirect to prioritize purchase flow
        if (isset($_COOKIE['checkout_price'])) {
            $redirect = 'checkout.php';
        } else {
            $redirect = 'referral_dashboard.php';
        }
    }

    // If an intended URL was stored (e.g., user arrived at a page with REF and was sent to login),
    // prefer redirecting back there only when the checkout flow is NOT active.
    if (empty($_COOKIE['checkout_price']) && !empty($_COOKIE['intended_url'])) {
        $intended = $_COOKIE['intended_url'];
        if (is_string($intended) && strpos($intended, '/') === 0 && strpos($intended, "\n") === false && strpos($intended, "\r") === false) {
            $redirect = $intended;
            // Clear the cookie after use
            setcookie('intended_url', '', time() - 3600, '/');
            unset($_COOKIE['intended_url']);
        }
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Login successful!',
        'redirect' => $redirect
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>