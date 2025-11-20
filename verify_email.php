<?php
/**
 * Email Verification Handler
 * Handles verification when users click the link in their email
 */

require_once __DIR__ . '/env_loader.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/email_verification.php';

// Get token from URL
$token = $_GET['token'] ?? '';

// Check if token is provided
if (empty($token)) {
    $error = "No verification token provided.";
} else {
    try {
        $pdo = getPDO();
        
        // Verify token
        $user = EmailVerification::verifyToken($token, $pdo);
        
        if ($user === false) {
            $error = "Invalid or expired verification token. Please request a new verification email.";
        } elseif ($user['email_verified']) {
            // Already verified - redirect to dashboard
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            
            // Get user's referral code
            $stmt = $pdo->prepare("SELECT referral_code FROM waitlist_users WHERE id = ?");
            $stmt->execute([$user['id']]);
            $userData = $stmt->fetch();
            
            if ($userData) {
                header("Location: referral_dashboard.php?user=" . urlencode($userData['referral_code']));
                exit;
            }
            $error = "Your email is already verified. Please contact support if you need assistance.";
        } else {
            // Mark as verified
            EmailVerification::markAsVerified($user['id'], $pdo);
            
            $success = true;
            $userName = $user['name'];
            
            // Get user's referral code for redirect
            $stmt = $pdo->prepare("SELECT referral_code FROM waitlist_users WHERE id = ?");
            $stmt->execute([$user['id']]);
            $userData = $stmt->fetch();
            $referralCode = $userData['referral_code'] ?? '';
        }
        
    } catch (Exception $e) {
        $error = "An error occurred during verification. Please try again later.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - Funding4x</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .verification-success {
            background: linear-gradient(135deg, #10b981, #059669);
            animation: pulse-gentle 2s infinite;
        }
        
        @keyframes pulse-gentle {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen flex items-center justify-center p-4 font-sans">

<?php if (isset($success) && $success): ?>
    <!-- Success Page -->
    <div class="max-w-lg w-full verification-success p-8 md:p-10 rounded-2xl shadow-2xl border-t-8 border-white transform transition duration-500 hover:scale-[1.01] text-center">
        
        <!-- Success Icon -->
        <div class="mb-6">
            <div class="w-20 h-20 mx-auto bg-white rounded-full flex items-center justify-center">
                <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
        </div>
        
        <!-- Success Message -->
        <h1 class="text-3xl md:text-4xl font-extrabold mb-4 text-white">
            Email Verified Successfully! 🎉
        </h1>
        
        <p class="text-xl mb-6 text-green-100">
            Welcome to the Funding4x waitlist, <?php echo htmlspecialchars($userName); ?>!
        </p>
        
        <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6 mb-6">
            <h3 class="text-lg font-bold mb-3 text-white">✅ What's Next?</h3>
            <ul class="text-left space-y-2 text-green-100">
                <li class="flex items-start">
                    <span class="mr-2">🎯</span>
                    <span>Your account is now verified</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2">📧</span>
                    <span>You'll receive updates about when we go live</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2">💰</span>
                    <span>Get ready to start trading with our $5000 accounts</span>
                </li>
               
            </ul>
        </div>
        
        <button id="redirect-btn" 
            class="w-full bg-white text-green-600 hover:bg-green-50 font-bold py-4 rounded-lg text-xl md:text-2xl uppercase tracking-wider shadow-2xl transition duration-300 ease-in-out transform hover:scale-105 active:scale-95">
            Go to My Dashboard
        </button>
        
        <p class="text-sm text-green-200 mt-4">
            * You'll be redirected to your personal referral dashboard*
        </p>
    </div>

    <script>
        document.getElementById('redirect-btn').addEventListener('click', function() {
            window.location.href = 'referral_dashboard.php?user=<?php echo urlencode($referralCode); ?>';
        });
        
        // Auto-redirect after 5 seconds
        setTimeout(function() {
            window.location.href = 'referral_dashboard.php?user=<?php echo urlencode($referralCode); ?>';
        }, 10000);
    </script>

<?php else: ?>
    <!-- Error Page -->
    <div class="max-w-lg w-full bg-gray-800 p-8 md:p-10 rounded-2xl shadow-2xl border-t-8 border-red-500 transform transition duration-500 hover:scale-[1.01] text-center">
        
        <!-- Error Icon -->
        <div class="mb-6">
            <div class="w-20 h-20 mx-auto bg-red-500 rounded-full flex items-center justify-center">
                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </div>
        </div>
        
        <!-- Error Message -->
        <h1 class="text-3xl md:text-4xl font-extrabold mb-4 text-red-400">
            Verification Failed
        </h1>
        
        <p class="text-xl mb-6 text-gray-300">
            <?php echo htmlspecialchars($error ?? 'An unknown error occurred.'); ?>
        </p>
        
        <div class="bg-red-900/20 border border-red-700/50 rounded-xl p-6 mb-6">
            <h3 class="text-lg font-bold mb-3 text-red-400">What can you do?</h3>
            <ul class="text-left space-y-2 text-gray-300">
                <li class="flex items-start">
                    <span class="mr-2">🔄</span>
                    <span>Check that you copied the complete verification link</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2">⏰</span>
                    <span>Verification links expire after 24 hours</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2">📧</span>
                    <span>Check your spam/junk folder for the verification email</span>
                </li>
                <li class="flex items-start">
                    <span class="mr-2">📞</span>
                    <span>Contact our support team for assistance</span>
                </li>
            </ul>
        </div>
        
        <div class="space-y-3">
            <a href="index.php" 
                class="block w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-4 rounded-lg text-xl md:text-2xl uppercase tracking-wider shadow-2xl transition duration-300 ease-in-out transform hover:scale-105 active:scale-95 text-center">
                Return to Waitlist
            </a>
            
            <button id="resend-btn" 
                class="w-full bg-gray-700 hover:bg-gray-600 text-gray-100 font-bold py-3 rounded-lg text-lg transition duration-200">
                Resend Verification Email
            </button>
        </div>
    </div>

    <script>
        document.getElementById('resend-btn').addEventListener('click', function() {
            // This would typically make an AJAX call to resend the verification email
            // For now, we'll just show a message
            Swal.fire({
                icon: 'info',
                title: 'Email Resent',
                text: 'Please check your email inbox (and spam folder) for the verification link.',
                confirmButtonColor: '#f97316',
                timer: 3000,
                timerProgressBar: true
            });
        });
    </script>
<?php endif; ?>

</body>
</html>