<?php
// save_user.php
header('Content-Type: application/json');

session_start();

// Load environment variables
require_once __DIR__ . '/env_loader.php';

// Include database connection
require_once 'database.php';
require_once 'email_verification.php';

// Get database connection
$pdo = getPDO();

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
$country = trim($data['country'] ?? '');
$referral_code = trim($data['ref'] ?? ''); // Get referral code from URL parameter

$recaptcha = $data['recaptcha'] ?? '';

$secretKey = EnvLoader::get('RECAPTCHA_SECRET_KEY', 'your_recaptcha_secret_key_here');

// Use cURL instead of file_get_contents for better compatibility
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'secret' => $secretKey,
    'response' => $recaptcha
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For development, remove in production
$response = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo json_encode([
        'status' => 'error',
        'message' => 'reCAPTCHA verification failed: ' . $curlError
    ]);
    exit;
}

$responseData = json_decode($response, true);

if (!$responseData || !$responseData['success']) {
    echo json_encode([
        'status' => 'error',
        'message' => 'reCAPTCHA verification failed.'
    ]);
    exit;
}

// Validate
if (empty($name) || empty($email) || empty($password) || empty($country)) {
    echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields']);
    exit;
}

// Validate password
if (strlen($password) < 8) {
    echo json_encode(['status' => 'error', 'message' => 'Password must be at least 8 characters long']);
    exit;
}

// Generate unique referral code for the new user
function generateReferralCode($pdo) {
    $prefix = 'REF';
    $unique = false;
    $code = '';

    while (!$unique) {
        $random = strtoupper(substr(uniqid(), -6));
        $code = $prefix . $random;

        // Check if code already exists
        $stmt = $pdo->prepare("SELECT id FROM waitlist_users WHERE referral_code = ?");
        $stmt->execute([$code]);
        if (!$stmt->fetch()) {
            $unique = true;
        }
    }

    return $code;
}

function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // May contain multiple IPs, return first
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}


// Save to DB with referral tracking
try {
    $pdo->beginTransaction();
    $userIP = getUserIP();

    // Check if this IP is blocked
    $checkBlocked = $pdo->prepare("SELECT id FROM blocked_ips WHERE ip_address = ?");
    $checkBlocked->execute([$userIP]);

    if ($checkBlocked->fetch()) {
        echo json_encode([
            'status' => 'ip_blocked',
            'message' => 'it seems you have already tried to sign up. You cannot create a new account',
        ]);
        exit;
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id, name, email, referral_code, email_verified, verification_token, verification_token_expires, status FROM waitlist_users WHERE email = ?");
    $stmt->execute([$email]);
    $existingUser = $stmt->fetch();

    if ($existingUser) {
        $pdo->rollBack();

        // Check if user status is inactive
        if (isset($existingUser['status']) && $existingUser['status'] === 'inactive') {
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $redirectUrl = $protocol . '://' . $host . '/index.php?error=inactive';

            echo json_encode([
                'status' => 'inactive_user',
                'message' => 'Your account is currently inactive. Please contact support for assistance.',
                'redirect_url' => $redirectUrl
            ]);
            exit;
        }

        // NEW: update IP for existing user
        $updateIP = $pdo->prepare("UPDATE waitlist_users SET user_ip = ? WHERE id = ?");
        $updateIP->execute([$userIP, $existingUser['id']]);

        // Check if user is already verified
        if ($existingUser['email_verified']) {
            // Return existing user's referral code for direct redirect
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $existingReferralLink = $protocol . '://' . $host . '/referral_dashboard.php?user=' . urlencode($existingUser['referral_code']);

            echo json_encode([
                'status' => 'existing_user',
                'message' => 'You\'re already registered!',
                'referral_code' => $existingUser['referral_code'],
                'referral_link' => $existingReferralLink
            ]);
        } else {
            $tokenMissing = empty($existingUser['verification_token']) || empty($existingUser['verification_token_expires']);
            $tokenExpired = (!empty($existingUser['verification_token_expires']) &&
                            strtotime($existingUser['verification_token_expires']) < time());

            if ($tokenMissing || $tokenExpired) {

                // Create new token
                $verificationToken = EmailVerification::createVerificationToken($existingUser['id'], $pdo);

                // Send email
                $emailSent = EmailVerification::sendVerificationEmail(
                    $existingUser['email'],
                    $existingUser['name'],
                    $verificationToken
                );
            }
            // User exists but not verified
            echo json_encode([
                'status' => 'email_not_verified',
                'email_sent' => $emailSent,
                'message' => 'Please check your email and verify your account to continue.',
                'referral_code' => $existingUser['referral_code']
            ]);
        }

        exit;
    }


    // Insert new user with referral code
    $userReferralCode = generateReferralCode($pdo);
    $parentUserId = null;
    // If referral code provided, find parent user and assign credits
    if (!empty($referral_code)) {
        $stmt = $pdo->prepare("SELECT id, credits FROM waitlist_users WHERE referral_code = ?");
        $stmt->execute([$referral_code]);
        $parentUser = $stmt->fetch();

        if ($parentUser) {
            $parentUserId = $parentUser['id'];

            // Increment parent's credits
            $stmt = $pdo->prepare("UPDATE waitlist_users SET credits = credits + 1 WHERE id = ?");
            $stmt->execute([$parentUserId]);
        }
    }

    // Check if this IP already exists in user_ip column
    $checkExistingIP = $pdo->prepare("SELECT id FROM waitlist_users WHERE user_ip = ?");
    $checkExistingIP->execute([$userIP]);

    if ($checkExistingIP->fetch()) {
        echo json_encode([
            'status' => 'duplicate_ip',
            'message' => 'It looks like you`re creating fake referrals. Please invite real people before you get blocked.',
        ]);
        exit;
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Check if user is paid
    // $paidUser = isset($_COOKIE['paid_user']) && $_COOKIE['paid_user'] == '1' ? 1 : 0;


    // Insert new user
    $stmt = $pdo->prepare("
        INSERT INTO waitlist_users (name, email, password, country, user_ip, referral_code, parent_user_id, email_verified)
        VALUES (?, ?, ?, ?, ?, ?, ?, 0)
    ");
    $stmt->execute([$name, $email, $hashedPassword, $country, $userIP, $userReferralCode, $parentUserId]);

    $userId = $pdo->lastInsertId();

    // Create email verification token and send email
    $verificationToken = EmailVerification::createVerificationToken($userId, $pdo);

    // Send verification email
    $emailSent = EmailVerification::sendVerificationEmail($email, $name, $verificationToken);

    // Generate referral dashboard URL
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $referralLink = $protocol . '://' . $host . '/referral_dashboard.php?user=' . urlencode($userReferralCode);

    $pdo->commit();

    // Set session variables
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name'] = $name;

    echo json_encode([
        'status' => 'success',
        'message' => 'Registration successful! Please check your email to verify your account.',
        'email_sent' => $emailSent,
        'referral_code' => $userReferralCode,
        'referral_link' => $referralLink
    ]);

} catch (Exception $e) {
    $pdo->rollBack();

    // More specific error messages
    if ($e instanceof PDOException) {
        if ($e->getCode() == 23000) { // duplicate email
            echo json_encode(['status' => 'error', 'message' => 'You\'re already registered!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
    }
}
?>
