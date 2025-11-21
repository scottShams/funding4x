<?php

// referral_dashboard.php

session_start();

// Include database connection
require_once 'database.php';

// Get database connection
$pdo = getPDO();

// Initialize variables
$user = null;
$referrals = [];
$showEmailModal = false;
$emailError = '';

// Check if email is provided via POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lookup_email'])) {
    $lookupEmail = trim($_POST['lookup_email']);
    
    if (!empty($lookupEmail)) {
        // Look up user by email
        $stmt = $pdo->prepare("SELECT * FROM waitlist_users WHERE email = ?");
        $stmt->execute([$lookupEmail]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Check if user is already verified
            if ($user['email_verified'] == 1) {
                // Store referral code in session
                $_SESSION['user_referral_code'] = $user['referral_code'];
                // Set flag to hide modal since user is verified
                $showEmailModal = false;
            } else {
                // User exists but not verified - check verification tokens
                $tokenMissing = empty($user['verification_token']) || empty($user['verification_token_expires']);
                $tokenExpired = (!empty($user['verification_token_expires']) &&
                                strtotime($user['verification_token_expires']) < time());

                if ($tokenMissing || $tokenExpired) {
                    // Need to create new verification token and send email
                    require_once __DIR__ . "/email_verification.php";
                    
                    // Create new token
                    $verificationToken = EmailVerification::createVerificationToken($user['id'], $pdo);

                    // Send email
                    $emailSent = EmailVerification::sendVerificationEmail(
                        $user['email'],
                        $user['name'],
                        $verificationToken
                    );
                }
                
                // Set flag to show verification modal
                $showEmailModal = true;
                $emailVerificationNeeded = true;
                $userEmail = $user['email'];
                $userName = $user['name'];
            }
        } else {
            $emailError = 'Email not found. Please check your email address or sign up first.';
        }
    } else {
        $emailError = 'Please enter a valid email address.';
    }
}

// If no email lookup was performed, check for referral code
if (!$user) {
    $referralCode = $_GET['user'] ?? $_SESSION['user_referral_code'] ?? '';
    
    if (!empty($referralCode)) {
        // Get current user data
        $stmt = $pdo->prepare("SELECT * FROM waitlist_users WHERE referral_code = ?");
        $stmt->execute([$referralCode]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Store in session for future visits
            $_SESSION['user_referral_code'] = $referralCode;
        }
    }
}

// If still no user found, show email modal
if (!$user) {
    $showEmailModal = true;
} else {
    // Get list of referrals (users who were referred by this user) with email verification status
    $stmt = $pdo->prepare("
        SELECT name, country, created_at, email_verified
        FROM waitlist_users 
        WHERE parent_user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $referrals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate verified vs pending referrals
    $totalReferrals = count($referrals);
    $verifiedReferrals = 0;
    $pendingReferrals = 0;
    
    foreach ($referrals as $referral) {
        if ($referral['email_verified'] == 1) {
            $verifiedReferrals++;
        } else {
            $pendingReferrals++;
        }
    }
}

// Generate referral link
if ($user) {
    // -----------------------------
    // SEND FIRST-TIME REFERRAL EMAIL
    // -----------------------------
    if ($user['referral_dashboard_mail_sent'] == 0) {
        require_once __DIR__ . "/email_verification.php";

        $sent = EmailVerification::sendReferralDashboardEmail(
            $user['email'],
            $user['name'],
            $user['referral_code']
        );

        if ($sent) {
            // Mark email as sent
            $update = $pdo->prepare("UPDATE waitlist_users SET referral_dashboard_mail_sent = 1 WHERE id = ?");
            $update->execute([$user['id']]);
        }
    }

    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $referralLink = $protocol . '://' . $host . '/index.php?ref=' . urlencode($user['referral_code']);

    // Calculate progress based on VERIFIED referrals only
    $credits = $verifiedReferrals; // Only count verified users for credits
    $goalCredits = 5;
    $progressPercentage = min(($credits / $goalCredits) * 100, 100);
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Referral Dashboard - Get Funded for Free</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon-16x16.png">
    <link rel="manifest" href="assets/site.webmanifest">

    <!-- Font Awesome -->

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <!-- Load Tailwind CSS -->

    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Load SweetAlert2 -->

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Configure Tailwind for Inter font and prestigious purple colors (Royal Purple/Gold Theme) -->

    <script>

        tailwind.config = {

            theme: {

                extend: {

                    fontFamily: {

                        sans: ['Inter', 'sans-serif'],

                    },

                    colors: {

                        // PRESTIGIOUS PURPLE PALETTE

                        'primary-purple': '#4f009d', // Deep Royal Purple

                        'trophy-gold': '#b49852', // Classic, muted Gold for prestige/success

                        'fomo-red': '#ef4444', // Bright Red for high-contrast urgency (CTA/Alerts)

                        'bg-light': '#f9fafb', // Very light background

                        'header-dark': '#240046', // Very Dark Purple/Black for the sticky header/footer

                    }

                }

            }

        }

    </script>

    <style>

        /* Custom styles */

        body {

            font-family: 'Inter', sans-serif;

            background-color: #f9fafb;

        }

        .header-bg {

            background-color: #240046;

        }

        .card-glow {

            box-shadow: 0 5px 20px rgba(79, 0, 157, 0.2);

            transition: transform 0.3s ease;

        }

        .card-glow:hover {

            transform: translateY(-3px);

        }

        .copy-btn {

            transition: all 0.3s ease;

        }

        .copy-btn.copied {

            background-color: #ef4444;

            color: white;

        }

        .pie-chart {
            width: 300px;
            height: 300px;
            margin: 0 auto;
        }

        .verification-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .verified-badge {
            background-color: #dcfce7;
            color: #166534;
        }

        .pending-badge {
            background-color: #fef3c7;
            color: #92400e;
        }

    </style>

    <script async src="https://www.googletagmanager.com/gtag/js?id=G-4F50HDQBDE"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', 'G-4F50HDQBDE');
    </script>

</head>

<body>

    <!-- Email Modal - NON-CLOSABLE when email verification is needed -->
    <?php if ($showEmailModal): ?>
    <div id="email-modal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4" 
         style="display: flex; <?php echo (isset($emailVerificationNeeded) && $emailVerificationNeeded) ? 'pointer-events: auto;' : ''; ?>">
        <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-md w-full">
            <div class="text-center mb-6">
                <h2 class="text-2xl font-bold text-primary-purple mb-2">
                    <?php if (isset($emailVerificationNeeded) && $emailVerificationNeeded): ?>
                        Email Verification Required
                    <?php else: ?>
                        Access Your Dashboard
                    <?php endif; ?>
                </h2>
                <p class="text-gray-600">
                    <?php if (isset($emailVerificationNeeded) && $emailVerificationNeeded): ?>
                        You're already registered, but need to verify your email address first.
                    <?php else: ?>
                        Enter your email address to view your referral dashboard
                    <?php endif; ?>
                </p>
            </div>
            
            <?php if (!empty($emailError)): ?>
            <div class="bg-red-100 border border-red-300 rounded-lg p-3 mb-4">
                <p class="text-red-700 text-sm"><?php echo htmlspecialchars($emailError); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if (isset($emailVerificationNeeded) && $emailVerificationNeeded): ?>
                <!-- Email verification message -->
                <div class="bg-orange-100 border border-orange-300 rounded-lg p-4 mb-4">
                    <div class="text-center">
                        <h4 class="font-bold text-orange-800 mb-2">📧 Check Your Email</h4>
                        <p class="text-orange-700 text-sm mb-3">
                            Please check your email inbox (and spam folder) for a verification link.
                            Click the link to activate your account and access the referral dashboard.
                        </p>
                        <p class="text-sm text-gray-600">
                            Didn't receive an email? Contact our support team.
                        </p>
                    </div>
                </div>
                
                <div class="flex space-x-3">
                    <!-- No buttons - just waiting state -->
                    <div class="flex-1 bg-gray-100 text-gray-500 font-semibold py-3 px-4 rounded-lg text-center">
                        Waiting for Email Verification...
                    </div>
                </div>
            <?php else: ?>
                <!-- Normal email lookup form -->
                <form method="POST" class="space-y-4">
                    <div>
                        <label for="lookup_email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                        <input type="email" 
                               id="lookup_email" 
                               name="lookup_email" 
                               required
                               class="w-full p-3 border-2 border-gray-300 rounded-lg focus:ring-primary-purple focus:border-primary-purple transition duration-200"
                               placeholder="Enter your email address">
                    </div>
                    
                    <div class="flex space-x-3">
                        <a href="index.php" 
                           class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-3 px-4 rounded-lg transition duration-300 text-center">
                            Back to Home
                        </a>
                        <button type="submit" 
                                class="flex-1 bg-primary-purple hover:bg-purple-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-300">
                            Access Dashboard
                        </button>
                    </div>
                </form>
                
                <p class="text-xs text-gray-500 mt-4 text-center">
                    Don't have an account yet? <a href="index.php" class="text-primary-purple hover:underline">Join our waitlist here</a>
                </p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Dashboard Content (only show if user is authenticated) -->
    <?php if ($user): ?>

    <!-- Header & Navigation -->
    <header class="header-bg text-white shadow-2xl sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
            <!-- Logo Section -->
            <div class="flex items-center">
                <img src="assets/logo.png" alt="Funding4X Logo" class="h-10 w-10 mr-3 rounded-lg">
                <h1 class="text-2xl font-extrabold tracking-tight text-trophy-gold">REFERRAL DASHBOARD</h1>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-300">Welcome, <?php echo htmlspecialchars($user['name']); ?></span>
                <a href="logout.php" class="text-sm text-white hover:text-trophy-gold transition duration-300">
                    ← Logout
                </a>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="py-16 sm:py-24 bg-primary-purple text-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <span class="text-trophy-gold text-sm font-semibold uppercase tracking-widest block mb-4">
                Thank you <?php echo htmlspecialchars($user['name']); ?>, we added you to the Waiting List for the $5000 Funded Account </br> As soon as everything is ready we will email you so make sure you stay up to date. </br></br> Whilst you are waiting, you can share your Referral link below to Earn Credits, which will give you unlimited Free Retry for the Trader Programme
            </span>
            <!-- Telegram Button -->
            <div class="flex justify-center mt-6">
                <a href="https://t.me/funding4x" target="_blank" rel="noopener noreferrer"
                    class="inline-flex items-center px-6 py-3 bg-blue-500 text-white font-semibold rounded-lg hover:bg-blue-600 transition duration-300 shadow-md">
                    <i class="fab fa-telegram-plane text-xl mr-2"></i>
                    Join us on Telegram for Instant Updates & Competitions
                </a>
            </div>
            <br>
            <span class="text-trophy-gold text-sm font-semibold uppercase tracking-widest block mb-4">The Ultimate Partner Program</span>
            <h2 class="text-4xl sm:text-6xl font-extrabold tracking-tighter leading-tight mb-4">
                5 Referrals = <span class="text-trophy-gold">$5,000</span> Funded Account
            </h2>
            <p class="mt-4 text-xl text-gray-200">
                Share your unique link with other passionate traders. For every successful referral who joins the competition and verifies their email, you earn **1 Credit**. Collect five credits to bypass the competition and get FREE Entry for the Test to get your Funded Account  (usually costs $59)!
                <br/><br/>
                <strong>NOTE: You must only Refer people you know who are Forex Traders - everyone will be Tested for their Skill</strong>
            </p>
        </div>
    </section>

    <!-- Referral Link, Tracker, and Status Table Section -->
    <section class="py-16 bg-bg-light">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Referral Link and Credit Tracker Box -->
            <div class="bg-white p-8 sm:p-12 rounded-2xl shadow-xl border-t-4 border-trophy-gold mb-8">
                <!-- Referral Link -->
                <h3 class="text-2xl font-bold text-primary-purple mb-2">Your Unique Referral Link</h3>
                <p class="text-gray-600 text-sm mb-2">
                
                    Refer 5 other Forex Traders and get FREE ENTRY to The Trader Programme 
                    (<del class="text-red-600">normally $59</del>),
                    FREE with 5 real Referrals
                </p>
                <div class="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-3 mb-8">
                    <input type="text" id="referral-link" value="<?php echo htmlspecialchars($referralLink); ?>" readonly 
                           class="flex-grow p-3 border-2 border-gray-300 rounded-lg bg-gray-50 text-gray-700 font-mono text-sm">
                    <button onclick="nativeShare('referral-link')" 
                            class="bg-gray-200 hover:bg-gray-300 text-violet-700 py-3 px-4 rounded-lg font-bold shadow-md flex items-center justify-center space-x-2">
                        <i class="fas fa-share-alt text-xl"></i>
                        <span>Share</span>
                    </button>
                    <button onclick="copyToClipboard('referral-link')" 
                            class="copy-btn px-6 py-3 bg-trophy-gold text-header-dark font-semibold rounded-lg hover:bg-yellow-700 transition duration-300 shadow-md">
                        Copy Link
                    </button>
                </div>

                <!-- Telegram Button -->
                <div class="flex justify-center mt-6">
                    <a href="https://t.me/funding4x" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center px-6 py-3 bg-blue-500 text-white font-semibold rounded-lg hover:bg-blue-600 transition duration-300 shadow-md">
                        <i class="fab fa-telegram-plane text-xl mr-2"></i>
                        Join us on Telegram for Instant Updates & Competitions
                    </a>
                </div>
                <!-- Social Buttons Section -->
                <div class="mt-8 flex flex-col sm:flex-row sm:justify-center sm:space-x-4 space-y-3 sm:space-y-0">

                    <!-- Facebook -->
                    <a href="https://www.facebook.com/naaz.miah" target="_blank"
                    class="flex items-center bg-white border border-gray-200 rounded-full shadow-sm hover:shadow-md transition px-4 py-2 w-full sm:w-auto">
                        <div class="flex items-center justify-center bg-blue-600 text-white rounded-full w-8 h-8">
                            <i class="fab fa-facebook-f text-base"></i>
                        </div>
                        <div class="ml-3 leading-tight">
                            <p class="text-gray-500 text-[10px] tracking-wide">FOLLOW US ON</p>
                            <p class="text-gray-900 text-sm font-bold">Facebook</p>
                        </div>
                    </a>

                    <!-- TikTok -->
                    <a href="http://tiktok.com/@agent47.money" target="_blank"
                    class="flex items-center bg-white border border-gray-200 rounded-full shadow-sm hover:shadow-md transition px-4 py-2 w-full sm:w-auto">
                        <div class="flex items-center justify-center bg-black text-white rounded-full w-8 h-8">
                            <i class="fab fa-tiktok text-base"></i>
                        </div>
                        <div class="ml-3 leading-tight">
                            <p class="text-gray-500 text-[10px] tracking-wide">FOLLOW US ON</p>
                            <p class="text-gray-900 text-sm font-bold">TikTok</p>
                        </div>
                    </a>

                    <!-- Twitter X -->
                    <a href="https://x.com/NasirFXTrader" target="_blank"
                    class="flex items-center bg-white border border-gray-200 rounded-full shadow-sm hover:shadow-md transition px-4 py-2 w-full sm:w-auto">
                        <div class="flex items-center justify-center bg-black text-white rounded-full w-8 h-8">
                            <i class="fab fa-x-twitter text-base"></i>
                        </div>
                        <div class="ml-3 leading-tight">
                            <p class="text-gray-500 text-[10px] tracking-wide">FOLLOW US ON</p>
                            <p class="text-gray-900 text-sm font-bold">Twitter X</p>
                        </div>
                    </a>

                </div>



                <!-- Credit Tracker -->
                <div class="mt-10">
                
                   <p class="text-sm text-gray-600 mt-3 text-center">
                   Get Unlimited FREE Entry for Trading Test. (<del class="text-red-600">no need $59 payment per Entry</del>) <br />
                    5/5 referrals  = ONE FREE Entry for Trading Test <br />
                    10/5 referrals  = TWO FREE Entry for Trading Test <br />
                    
                    Only Refer other Real Forex Traders.
                    <br /> <br />
                   </p>
                                        
                                        
                    <h3 class="text-2xl font-bold text-primary-purple mb-4">
                        Your Credit Progress: <span id="credit-count" class="text-fomo-red"><?php echo $credits; ?> / <?php echo $goalCredits; ?></span>
                        <span class="text-sm text-gray-600">(Based on Verified Referrals Only)</span>
                    </h3>
                    
                 
                    <div class="w-full bg-gray-200 rounded-full h-8 overflow-hidden shadow-inner">
                        <div id="progress-bar" class="h-8 bg-primary-purple rounded-full transition-all duration-700 ease-out" 
                             style="width: <?php echo $progressPercentage; ?>%;">
                            <span class="text-white font-bold pl-4 leading-8 text-sm">
                                <?php echo round($progressPercentage); ?>% Complete (<?php echo $credits; ?> Credits)
                            </span>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 mt-3 text-center">
                        <?php if ($credits >= $goalCredits): ?>
                            <span class="text-green-600 font-bold">🎉 Congratulations! You've earned a $5,000 Funded Account!</span>
                        <?php else: ?>
                            You are <strong><?php echo ($goalCredits - $credits); ?></strong> successful referral(s) away from a $5,000 Funded Account!
                        <?php endif; ?>
                    </p>
                </div>

                <!-- Pie Chart Section -->
                <?php if ($totalReferrals > 0): ?>
                <div class="mt-10">
                    <h3 class="text-2xl font-bold text-primary-purple mb-6 text-center">Referral Status Overview</h3>
                    <div class="flex flex-col md:flex-row items-center justify-center space-y-6 md:space-y-0 md:space-x-8">
                        <!-- Pie Chart -->
                        <div class="pie-chart">
                            <canvas id="referralPieChart"></canvas>
                        </div>
                        
                        <!-- Legend -->
                        <div class="space-y-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-4 h-4 bg-green-500 rounded"></div>
                                <span class="text-lg font-semibold text-gray-700">
                                    Completed: <?php echo $verifiedReferrals; ?>
                                </span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div class="w-4 h-4 bg-yellow-500 rounded"></div>
                                <span class="text-lg font-semibold text-gray-700">
                                    Pending: <?php echo $pendingReferrals; ?>
                                </span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div class="w-4 h-4 bg-primary-purple rounded"></div>
                                <span class="text-lg font-semibold text-gray-700">
                                    Total: <?php echo $totalReferrals; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <p class="text-sm text-gray-500 mt-6 text-center italic">
                        Only verified referrals count towards your credits. Pending referrals need to verify their email to earn you credits.
                    </p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Referral Status Table Box -->
            <div class="bg-white p-8 sm:p-10 rounded-2xl shadow-xl border-t-4 border-primary-purple">
                <h3 class="text-2xl font-bold text-primary-purple mb-6">Your Referrals (<?php echo $totalReferrals; ?>)</h3>
                
                <?php if (empty($referrals)): ?>
                    <!-- No referrals yet -->
                    <div class="text-center py-12">
                        <div class="text-6xl mb-4">👥</div>
                        <h4 class="text-xl font-bold text-gray-600 mb-2">No Referrals Yet</h4>
                        <p class="text-gray-500 mb-6">Share your unique link above to start earning credits!</p>
                        <button onclick="copyToClipboard('referral-link')" 
                                class="copy-btn px-6 py-3 bg-primary-purple text-white font-semibold rounded-lg hover:bg-purple-700 transition duration-300">
                            Copy & Share Your Link
                        </button>
                    </div>
                <?php else: ?>
                    <!-- Has referrals -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 rounded-xl overflow-hidden">
                            <thead class="bg-primary-purple">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-trophy-gold uppercase tracking-wider">
                                        Referred Trader
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-trophy-gold uppercase tracking-wider">
                                        Country
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-trophy-gold uppercase tracking-wider">
                                        Joined On
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-trophy-gold uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-trophy-gold uppercase tracking-wider">
                                        Credit
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($referrals as $index => $referral): ?>
                                    <?php 
                                        $isVerified = ($referral['email_verified'] == 1);
                                    ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($referral['name']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($referral['country']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('M j, Y', strtotime($referral['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                            <?php if ($isVerified): ?>
                                                <span class="verification-badge verified-badge">
                                                    <i class="fas fa-check-circle mr-1"></i>
                                                    Completed
                                                </span>
                                            <?php else: ?>
                                                <span class="verification-badge pending-badge">
                                                    <i class="fas fa-clock mr-1"></i>
                                                    Pending
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                            <?php if ($isVerified): ?>
                                                <span class="text-lg text-green-600 font-bold">✓</span>
                                            <?php else: ?>
                                                <span class="text-lg text-yellow-600 font-bold">⏳</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <p class="mt-6 text-sm text-gray-600 italic border-t pt-4">
                    <strong>Status Definition:</strong> Each successful referral who registers using your link and verifies their email earns you **1 Credit**. Once you reach 5 credits, you'll get a FREE Entry to the Test for a $5,000 Funded Account!
                </p>
            </div>
        </div>
    </section>

    <!-- How It Works Section (Steps) -->
    <section class="py-16 sm:py-24 bg-bg-light">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-4xl font-extrabold text-center text-gray-900 mb-12">
                It's Simple: Get Funded by Sharing
            </h2>
            <div class="grid md:grid-cols-3 gap-8 text-center">
                <!-- Step 1 -->
                <div class="card-glow p-8 bg-white rounded-xl shadow-xl border-b-4 border-primary-purple">
                    <div class="text-4xl font-extrabold text-primary-purple mb-3">1</div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Share Your Link</h3>
                    <p class="text-gray-600">
                        Copy your unique URL and share it with friends, groups, and your social network.
                    </p>
                </div>

                <!-- Step 2 -->
                <div class="card-glow p-8 bg-white rounded-xl shadow-xl border-b-4 border-trophy-gold">
                    <div class="text-4xl font-extrabold text-trophy-gold mb-3">2</div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">They Join & Verify</h3>
                    <p class="text-gray-600">
                        When a new trader registers and verifies their email using your link, you instantly earn **1 Credit**.
                    </p>
                </div>

                <!-- Step 3 -->
                <div class="card-glow p-8 bg-white rounded-xl shadow-xl border-b-4 border-fomo-red">
                    <div class="text-4xl font-extrabold text-fomo-red mb-3">3</div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Claim Your Prize!</h3>
                    <p class="text-gray-600">
                        Reach **5 Credits** and we'll give you a FREE Entry for the Test for a $5,000 Funded Account, No Test Fees needed!
                    </p>
                </div>
            </div>
            
            <div class="text-center mt-12">
                <a href="#rules" class="text-primary-purple font-semibold hover:text-trophy-gold transition">View detailed referral terms and conditions →</a>
            </div>
        </div>
    </section>

    <!-- FAQ / Rules -->
    <section id="rules" class="py-16 bg-primary-purple text-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-4xl font-extrabold text-center text-white mb-10">
                Referral FAQs
            </h2>
            <div class="space-y-6">
                <!-- FAQ Item 1 -->
                <div class="bg-header-dark p-6 rounded-xl shadow-lg">
                    <h4 class="text-xl font-bold text-trophy-gold mb-2">What qualifies as a successful referral?</h4>
                    <p class="text-gray-300">
                        A successful referral is a user who clicks your unique link, completes registration, and verifies their email address. <strong>Only verified referrals earn you credits.</strong>
                    </p>
                </div>
                <!-- FAQ Item 2 -->
                <div class="bg-header-dark p-6 rounded-xl shadow-lg">
                    <h4 class="text-xl font-bold text-trophy-gold mb-2">Do my credits expire?</h4>
                    <p class="text-gray-300">
                        No, your earned credits are yours to keep. You can earn as many Credits as you want by Referring as many people as you like. Credits are only awarded for verified referrals.
                    </p>
                </div>
                <!-- FAQ Item 3 -->
                <div class="bg-header-dark p-6 rounded-xl shadow-lg">
                    <h4 class="text-xl font-bold text-trophy-gold mb-2">What happens to pending referrals?</h4>
                    <p class="text-gray-300">
                        Pending referrals haven't verified their email yet. They can still verify later and will then count towards your credits. We track both completed and pending referrals for your transparency.
                    </p>
                </div>
                <!-- FAQ Item 4 -->
                <div class="bg-header-dark p-6 rounded-xl shadow-lg">
                    <h4 class="text-xl font-bold text-trophy-gold mb-2">Is there any Benefit to Accumulate More than 5 Referral Credits?</h4>
                    <p class="text-gray-300">
                                        
                    Yes there is actually... you will get more chances to Pass the Trading Test... for FREE.<br />
                    if you have 5/5 referrals means you can get 1 Free Entry for the Trading Test (no need $59 payment)<br />
                    if you have 10/5 means you get 2 Free Entries for the Trading Test so you saved ($118)... and so on....<br />
                    You can have unlimited Free Entry for Trading Tests.<br />
                    
                    So you can collect as many credits as you like by doing referrals of real people who are also forex traders, to give you more chances for passing the trading test. 
                    </p>
                </div>
                
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-header-dark text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 text-center">
            <p class="text-sm">&copy; 2024 Funding4x. All rights reserved. | Powered by Referrals.</p>
        </div>
    </footer>

    <?php endif; ?>

    <!-- JavaScript for Clipboard Functionality and Pie Chart -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Simple loader - NO SWEETALERT, JUST HTML LOADER
        <?php if (isset($emailVerificationNeeded) && $emailVerificationNeeded): ?>
        document.addEventListener('DOMContentLoaded', function() {
            // Add loading spinner to the modal
            const modalContent = document.querySelector('#email-modal .bg-white');
            if (modalContent) {
                const loader = document.createElement('div');
                loader.innerHTML = `
                    <div style="text-align: center; margin-top: 20px;">
                        <div class="spinner" style="border: 4px solid #f3f3f3; border-top: 4px solid #4f009d; border-radius: 50%; width: 40px; height: 40px; margin: 0 auto; animation: spin 1s linear infinite;"></div>
                        <p style="margin-top: 10px; color: #6b7280;">Waiting for email verification...</p>
                    </div>
                `;
                modalContent.appendChild(loader);
                
                // Add CSS animation
                const style = document.createElement('style');
                style.textContent = `
                    @keyframes spin {
                        0% { transform: rotate(0deg); }
                        100% { transform: rotate(360deg); }
                    }
                `;
                document.head.appendChild(style);
            }
            
            // Also ensure the HTML modal cannot be closed
            const emailModal = document.getElementById('email-modal');
            if (emailModal) {
                // Prevent closing by clicking outside
                emailModal.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                });
                
                // Block escape key globally
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        e.preventDefault();
                        e.stopPropagation();
                        return false;
                    }
                });
                
                // Prevent right-click context menu
                document.addEventListener('contextmenu', function(e) {
                    e.preventDefault();
                    return false;
                });
                
                // Block F5, Ctrl+R, Ctrl+F5 refresh attempts
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'F5' || (e.ctrlKey && e.key === 'r') || (e.ctrlKey && e.shiftKey && e.key === 'R')) {
                        e.preventDefault();
                        return false;
                    }
                });
                
                // Silent prevention - remove event listener to prevent browser dialog
                window.onbeforeunload = function() {
                    // Completely silent - no browser dialog
                    return undefined;
                };
                
                // Override all links to prevent navigation
                const links = document.querySelectorAll('a[href]');
                links.forEach(link => {
                    if (link.getAttribute('href') !== '#' && link.getAttribute('href') !== 'javascript:void(0)') {
                        link.addEventListener('click', function(e) {
                            e.preventDefault();
                            return false;
                        });
                    }
                });
            }
            
            // Periodic email verification check - just show loader, no alerts
            let checkCount = 0;
            setInterval(function() {
                checkCount++;
                
                // Show simple loading indicator only
                const existingLoader = document.querySelector('.verification-loader');
                if (existingLoader) {
                    existingLoader.remove();
                }
                
                const loader = document.createElement('div');
                loader.className = 'verification-loader';
                loader.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: #f97316;
                    color: white;
                    padding: 10px 20px;
                    border-radius: 5px;
                    font-size: 14px;
                    z-index: 10000;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
                `;
                loader.innerHTML = `Checking verification status... (Attempt ${checkCount})`;
                document.body.appendChild(loader);
                
                // Remove loader after 2 seconds and refresh
                setTimeout(() => {
                    if (loader.parentNode) {
                        loader.parentNode.removeChild(loader);
                    }
                    window.location.reload();
                }, 2000);
            }, 5000); // Check every 5 seconds
        });
        <?php endif; ?>

        // Functions disabled to prevent modal closing
        function resendVerificationEmail() {
            // No function - modal must remain open
            return false;
        }

        function resetFormToOriginalState() {
            // Do NOT reset form - keep modal open
            console.log('Form reset disabled - modal must remain open');
        }

        function ensureNoLoaderRemains() {
            // Minimal cleanup function
            console.log('Minimal loader cleanup completed');
        }

        // Email verification status checking is handled by page reload

        function nativeShare(elementId) {

            const linkInput = document.getElementById(elementId);

            const referralURL = linkInput.value; // Get actual URL string

            console.log(referralURL);

            const message = "🚀 Want a $5,000 funded trading account?\n\n" +

                "Use my link to get started and claim your funded account:\n\n💸 " + referralURL + "\n\n" +
				
                "Hurry, they will stop taking people soon. Good luck with your Trading!!";

            if (navigator.share) {

                navigator.share({

                    title: 'Fintech App',

                    text: message,

                    url: referralURL

                }).then(() => {

                    console.log('Successfully shared');

                }).catch((error) => {

                    console.error('Error sharing:', error);

                });

            } else {

                alert('Sharing not supported on this browser. Copy the link manually.');

            }

        }

        // Function to copy text to clipboard

        function copyToClipboard(elementId) {

            const copyText = document.getElementById(elementId);

            // Use navigator.clipboard API if available, fallback to execCommand

            if (navigator.clipboard && window.isSecureContext) {

                navigator.clipboard.writeText(copyText.value).then(() => {

                    showCopyConfirmation();

                }).catch(err => {

                    fallbackCopy(copyText);

                });

            } else {

                fallbackCopy(copyText);

            }

        }

        function fallbackCopy(copyText) {

            try {

                copyText.select();

                copyText.setSelectionRange(0, 99999); // For mobile devices

                document.execCommand('copy');

                showCopyConfirmation();

            } catch (err) {

                console.error('Could not copy text: ', err);

            }

        }

        function showCopyConfirmation() {

            const button = document.querySelector('.copy-btn');

            const originalText = button.textContent;

            button.textContent = 'Copied!';

            button.classList.add('copied');

            setTimeout(() => {

                button.textContent = originalText;

                button.classList.remove('copied');

                button.classList.add('bg-trophy-gold', 'text-header-dark');

            }, 1500);

        }

        // Initialize Pie Chart
        <?php if ($user && $totalReferrals > 0): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('referralPieChart').getContext('2d');
            const chart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: ['Completed (Verified)', 'Pending'],
                    datasets: [{
                        data: [<?php echo $verifiedReferrals; ?>, <?php echo $pendingReferrals; ?>],
                        backgroundColor: [
                            '#10b981', // Green for completed
                            '#f59e0b'  // Yellow for pending
                        ],
                        borderColor: [
                            '#059669',
                            '#d97706'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        });
        <?php endif; ?>

        // Update progress bar on page load
        <?php if ($user): ?>
        document.addEventListener('DOMContentLoaded', () => {

            const credits = <?php echo $credits; ?>;

            const goalCredits = <?php echo $goalCredits; ?>;

            const percentage = (credits / goalCredits) * 100;

            const progressBar = document.getElementById('progress-bar');

            const creditCount = document.getElementById('credit-count');

            if (progressBar) {

                progressBar.style.width = `${Math.min(percentage, 100)}%`;

            }

            if (creditCount) {

                creditCount.textContent = `${credits} / ${goalCredits}`;

            }

        });
        <?php endif; ?>

    </script>

    <!--Start of Tawk.to Script-->
    <script type="text/javascript">
        var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
        (function(){
        var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
        s1.async=true;
        s1.src='https://embed.tawk.to/691d6280a5da4b195b532b2a/1jadchhls';
        s1.charset='UTF-8';
        s1.setAttribute('crossorigin','*');
        s0.parentNode.insertBefore(s1,s0);
        })();
    </script>

</body>

</html>
