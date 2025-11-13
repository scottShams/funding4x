<?php
// save_waitlist.php
header('Content-Type: application/json');

// Database config
$host = 'localhost';
$dbname = 'funding4x';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$country = trim($data['country'] ?? '');
$referral_code = trim($data['ref'] ?? ''); // Get referral code from URL parameter

// Validate
if (empty($name) || empty($email) || empty($country)) {
    echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields']);
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

// Save to DB with referral tracking
try {
    $pdo->beginTransaction();
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id, referral_code FROM waitlist_users WHERE email = ?");
    $stmt->execute([$email]);
    $existingUser = $stmt->fetch();
    
    if ($existingUser) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'You’re already on the waitlist!']);
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
    
    // Insert new user
    $stmt = $pdo->prepare("
        INSERT INTO waitlist_users (name, email, country, referral_code, parent_user_id) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$name, $email, $country, $userReferralCode, $parentUserId]);
    
    $userId = $pdo->lastInsertId();
    
    // Generate referral dashboard URL
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $referralLink = $protocol . '://' . $host . '/referral_dashboard.php?user=' . urlencode($userReferralCode);
    
    $pdo->commit();
    
    echo json_encode([
        'status' => 'success', 
        'message' => 'You have been added to the waitlist!',
        'referral_code' => $userReferralCode,
        'referral_link' => $referralLink
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    
    if ($e->getCode() == 23000) { // duplicate email
        echo json_encode(['status' => 'error', 'message' => 'You\'re already on the waitlist!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Something went wrong. Try again later.']);
    }
}
?>
