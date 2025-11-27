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

// Check if email is provided via POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lookup_email'])) {
    $lookupEmail = trim($_POST['lookup_email']);
    
    if (!empty($lookupEmail)) {
        $userIP = getUserIP();
        // Look up user by email
        $stmt = $pdo->prepare("SELECT * FROM waitlist_users WHERE email = ?");
        $stmt->execute([$lookupEmail]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // NEW: update IP for existing user
        $updateIP = $pdo->prepare("UPDATE waitlist_users SET user_ip = ? WHERE id = ?");
        $updateIP->execute([$userIP, $user['id']]);
        
        if ($user) {
            // Check if user status is inactive
            if (isset($user['status']) && $user['status'] === 'inactive') {
                $emailError = 'Your account is currently inactive. Please contact support for assistance.';
                $user = null; // Reset user to show modal with error
            }
            // Check if user is already verified
            elseif ($user['email_verified'] == 1) {
                // Store referral code in session
                $_SESSION['user_referral_code'] = $user['referral_code'];
                $_SESSION['user_email'] = $user['email'];
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
            // Check if user status is inactive
            if (isset($user['status']) && $user['status'] === 'inactive') {
                $emailError = 'Your account is currently inactive. Please contact support for assistance.';
                $user = null; // Reset user to show modal with error
            } else {
                // Store in session for future visits
                $_SESSION['user_referral_code'] = $referralCode;
                $_SESSION['user_email'] = $user['email'];
            }
        }
    }
}

// If still no user found, show email modal
if (!$user) {
    $showEmailModal = true;
} else {
    // Get list of referrals (users who were referred by this user) with email verification status
    $stmt = $pdo->prepare("
        SELECT name, country, user_ip, status, quiz_result, user_credit, created_at, email_verified
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
        if ($referral['email_verified'] == 1 && $referral['quiz_result'] != null && $referral['user_ip'] !== $user['user_ip']) {
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

                        'success-green': '#10b981', // Tailwind green-500 for completion

                        'border-light': '#e5e7eb', // Gray-200

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

        /* Checklist completion styles */
        .topic-item {
            cursor: default;
            transition: all 0.2s ease;
        }
        .checkmark-icon {
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .completed .checkmark-icon {
            opacity: 1;
        }
        .completed {
            background-color: #f0fdf4; /* Light green background */
        }

    </style>

    <script async src="https://www.googletagmanager.com/gtag/js?id=G-4F50HDQBDE"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', 'G-4F50HDQBDE');
    </script>

    <!-- adsense code-->
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-8844859089842671"
         crossorigin="anonymous"></script>
     
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

    <!-- Knowledge Quiz Modal (Green) -->
    <?php if ($user && empty($user['quiz_result']) && !$showEmailModal): ?>
    <div id="quiz-modal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4" style="display: none;">
        <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-md w-full border-t-4 border-green-500 relative">
            <!-- Close Button -->
            <button onclick="closeQuizModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition duration-200">
                <i class="fas fa-times text-2xl"></i>
            </button>
            
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-graduation-cap text-3xl text-green-600"></i>
                </div>
                <h2 class="text-2xl font-bold text-green-600 mb-4">Knowledge Quiz</h2>
                <p class="text-gray-700 text-lg leading-relaxed">
                    <strong><?php echo htmlspecialchars($user['name']); ?></strong>, We want to make sure you are a Real Forex Trader. You <span class="text-red-600 font-bold">NEED</span> to do a quick Knowledge Quiz. It will only take 2 minutes.
                </p>
            </div>
            
            <div class="flex justify-center">
                <a href="quiz.php" 
                   class="bg-green-500 hover:bg-green-600 text-white font-bold py-4 px-8 rounded-lg transition duration-300 shadow-lg text-lg flex items-center space-x-2">
                    <i class="fas fa-play-circle"></i>
                    <span>Go to Quiz</span>
                </a>
            </div>
            
            <p class="text-xs text-gray-500 mt-6 text-center">
                This helps us verify you're a genuine forex trader
            </p>
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
                Thank you <?php echo htmlspecialchars($user['name']); ?>, we added you to the Waiting List for the $5000 Funded Account. 
            </span>
            <!-- Telegram Button -->
            <div class="flex justify-center mt-6">
                <a href="https://t.me/funding4x" target="_blank" rel="noopener noreferrer"
                    class="inline-flex items-center px-6 py-3 bg-blue-500 text-white font-semibold rounded-lg hover:bg-blue-600 transition duration-300 shadow-md">
                    <i class="fab fa-telegram-plane text-xl mr-2"></i>
                    Join on Telegram for Updates
                </a>
                
                
                    
            </div>
            
            
            <br />
            <p>Subscribe on YouTube latest Tutorial & Updates</p>
            <br />
            <script src="https://apis.google.com/js/platform.js"></script>

            <div class="g-ytsubscribe" data-channelid="UCkosETo_p1wOaAx2g2B0jLA" data-layout="full" data-count="hidden"></div>


            <br /><br />
            <span class="text-trophy-gold text-sm font-semibold uppercase tracking-widest block mb-4">The Ultimate Partner Program</span>
            <h2 class="text-4xl sm:text-6xl font-extrabold tracking-tighter leading-tight mb-4">
                5 Referrals = <span class="text-trophy-gold">$5,000</span> Funded Account
            </h2>
            <p class="mt-4 text-xl text-gray-200">
                Share your unique link with other passionate <strong>Forex Traders only</strong>. For every successful referral who joins the competition and verifies their email, you earn **1 Credit**. Collect five credits to bypass the competition and get FREE Entry for the Test to get your Funded Account  (usually costs $59)!
                <br/><br/>
                
            </p>
        </div>
    </section>
    
    <br />
    <!-- Checklist and Referral Link Content -->
    <section class="flex-grow p-4 sm:p-8">
        <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-12 gap-8">
            <!-- Checklist Content - col-4 on medium+ screens, full width on mobile -->
            <div class="col-span-1 md:col-span-4">
                <!-- Title Block -->
                <div class="mb-8 p-4 bg-white rounded-xl shadow-lg border-l-4 border-primary-purple">
                    <h2 class="text-3xl font-extrabold text-primary-purple mb-2">
                        Next Steps...
                    </h2>
                    <p class="text-gray-600">
                        Complete these Steps to reach the $5000 Funded Account.
                    </p>
                    <!-- Progress Bar -->
                    <div class="mt-4">
                        <div class="text-sm font-medium text-gray-700 mb-1 flex justify-between">
                            <span>Progress</span>
                            <span id="progress-text">0/10 Topics Completed</span>
                        </div>
                        <div class="w-full bg-border-light rounded-full h-3">
                            <div id="progress-bar" class="h-3 rounded-full bg-success-green transition-all duration-500" style="width: 0%"></div>
                        </div>
                    </div>
                </div>

                <!-- Checklist Grid -->
                <div id="topic-checklist" class="grid grid-cols-1 gap-4">
                    <!-- Topic Item Template -->
                    <!-- The JS will populate this with data from the 'topics' array -->
                </div>

                <!-- Buy Now Section -->
                <div class="mt-8 mb-10 p-8 sm:p-12 rounded-2xl bg-primary-purple text-white shadow-2xl transform hover:scale-[1.01] transition duration-300">
                    <div class="max-w-4xl mx-auto text-center">
                        <h2 class="text-4xl sm:text-5xl font-extrabold tracking-tight mb-4 text-white">
                            Get Your Funded Account Test Now
                        </h2>
                        <?php if ($user && $verifiedReferrals < 5): ?>
                            <!-- Benefit 1 -->
                            <button onclick="document.getElementById('modal').classList.remove('hidden')" class="bg-trophy-gold p-4 rounded-xl shadow-lg border-b-4 border-yellow-700 cursor-pointer">
                                <p class="font-bold text-lg mb-1">Buy Now - 38% Off</p>
                                <p class="text-sm"><del>Normally $59</del>, now only $36 for First Comers</p>
                            </button>
                        <?php endif; ?>

                    </div>
                </div>
            </div>

            <!-- Referral Link Content - col-8 on medium+ screens, full width on mobile -->
            <div class="col-span-1 md:col-span-8">
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

                        
                        
                    
                    <div class="mt-10 p-6 bg-white border-2 border-fomo-red rounded-xl shadow-2xl max-w-lg mx-auto fomo-glow">
                        
                        <!-- Telegram Button -->
                                <div class="flex justify-center mt-6">
                                    <a href="https://t.me/funding4x" target="_blank" rel="noopener noreferrer"
                                    class="inline-flex items-center px-6 py-3 bg-blue-500 text-white font-semibold rounded-lg hover:bg-blue-600 transition duration-300 shadow-md">
                                        <i class="fab fa-telegram-plane text-xl mr-2"></i>
                                        Live Updates on Telegram
                                    </a>
                                </div>
                                
                                <br /><br />
                                
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
                            
                            <br /><br />
                                
                                <p>Subscribe on YouTube for Forex Trading Ideas, Forex Trading Strategies, Forex Lessons, Forex Market Updates and more...</p>
                                <br />
                                <script src="https://apis.google.com/js/platform.js"></script>
            
                                <div class="g-ytsubscribe" data-channelid="UCkosETo_p1wOaAx2g2B0jLA" data-layout="full" data-count="hidden"></div>
                                
                            <br />
                            
                            

                        </div> 

                        <!-- Credit Tracker -->
                        <div class="mt-10">
                        
                        <!--
                        <p class="text-sm text-gray-600 mt-3 text-center">
                        Get Unlimited FREE Entry for Trading Test. (<del class="text-red-600">no need $59 payment per Entry</del>) <br />
                            5/5 referrals  = ONE FREE Entry for Trading Test <br />
                            10/5 referrals  = TWO FREE Entry for Trading Test <br />
                            
                            Only Refer other Real Forex Traders.
                            <br /> <br />
                        </p>
                        -->
                                                
                                                
                            <h3 class="text-2xl font-bold text-primary-purple mb-4">
                                Your Credit Progress: <span id="credit-count" class="text-fomo-red"><?php echo $credits; ?> / <?php echo $goalCredits; ?></span>
                                <br /><span class="text-sm text-gray-600">(Based on Verified Referrals Only)</span>
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
                                    <div class="bg-white p-8 rounded-2xl shadow-2xl border-2 border-primary-purple h-fit lg:sticky lg:top-24">
                                        <h2 class="text-2xl font-bold text-primary-purple mb-2">Congratulations! 1 Free Trading Test Unlocked<del class="text-red-600"> (no need to pay $59)</del></h2>
                                        <p class="text-sm text-gray-600 mb-6">
                                            You've earned a Free Trading Test for the $5,000 Funded Account!
                                        
                                        <br /><br />
                                    Thank for referring other Forex Traders. To stay up to date with the Next Steps, go ahead and join the telegram group where we will give live updates.
                                        
                                        <br /><br />                    
                                        <strong>You can also keep inviting more people to get more Credits.
                                        More credits = More free Trading Tests for you.</strong>
                                        <br />                    
                                        Thank you for being patient with us.</p>
                    
                                        <!-- Success/Error Message Box -->
                                        <div id="message-box" class="mt-4 p-4 rounded-lg text-sm text-center hidden font-medium"></div>
                    
                                    </div>
                                    
                                    
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
                                                IsTrader
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-trophy-gold uppercase tracking-wider">
                                                IsReal
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-trophy-gold uppercase tracking-wider">
                                                IsVerified
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
                                                $isVerified = ($referral['email_verified'] == 1 && $referral['quiz_result'] != null && $referral['user_ip'] !== $user['user_ip']);
                                            ?>
                                            <tr class="hover:bg-gray-50">
                                                <!-- Name -->
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($referral['name']); ?>
                                                </td>

                                                <!-- Trader / Non Trader -->
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?php if (!empty($referral['quiz_result'])): ?>
                                                        <span style="background:#d1fae5; color:#065f46; padding:3px 8px; border-radius:6px; font-weight:600;">
                                                            Trader
                                                        </span>
                                                    <?php elseif(empty($referral['quiz_result']) && $referral['status'] === 'inactive'): ?>
                                                        <span style="background:#fee2e2; color:#991b1b; padding:3px 8px; border-radius:6px; font-weight:600;">
                                                            Non Trader
                                                        </span>
                                                    <?php else: ?>
                                                        <span style="background:#fee2e2; color:#991b1b; padding:3px 8px; border-radius:6px; font-weight:600;">
                                                            Unknown
                                                        </span>
                                                    <?php endif; ?>
                                                </td>

                                                <!-- Fake / Real -->
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?php if ($referral['user_ip'] === $user['user_ip']): ?>
                                                        <span style="background:#fee2e2; color:#991b1b; padding:3px 8px; border-radius:6px; font-weight:600;">
                                                            Fake
                                                        </span>
                                                    <?php else: ?>
                                                        <span style="background:#d1fae5; color:#065f46; padding:3px 8px; border-radius:6px; font-weight:600;">
                                                            Real
                                                        </span>
                                                    <?php endif; ?>
                                                </td>

                                                <!-- varification Completed / Pending -->
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                                    <?php if ($isVerified): ?>
                                                        <span style="background:#d1fae5; color:#065f46; padding:4px 10px; border-radius:8px; font-weight:600;">
                                                            <i class="fas fa-check-circle mr-1"></i> Verified
                                                        </span>
                                                    <?php else: ?>
                                                        <span style="background:#fef3c7; color:#92400e; padding:4px 10px; border-radius:8px; font-weight:600;">
                                                            <i class="fas fa-x mr-1"></i> Unverified
                                                        </span>
                                                    <?php endif; ?>
                                                </td>

                                                <!-- status Completed / Pending -->
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                                    <?php if ($referral['status'] === 'active' && !empty($referral['quiz_result'])): ?>
                                                        <span style="background:#d1fae5; color:#065f46; padding:4px 10px; border-radius:8px; font-weight:600;">
                                                            <i class="fas fa-check-circle mr-1"></i> Completed
                                                        </span>
                                                    <?php else: ?>
                                                        <span style="background:#fef3c7; color:#92400e; padding:4px 10px; border-radius:8px; font-weight:600;">
                                                            <i class="fas fa-clock mr-1"></i> Pending
                                                        </span>
                                                    <?php endif; ?>
                                                </td>

                                                <!-- Icon (tick or pending) -->
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                                    <?php echo htmlspecialchars($referral['user_credit']); ?>
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
            </div>
        </section>
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
                    <h4 class="text-xl font-bold text-trophy-gold mb-2">If I have 10 referral credits, will I have 2 trials accounts at once?</h4>
                    <p class="text-gray-300">
                                        
                    With 10 verified referrals you get 2 tests for Free. Normally that would cost $118, which you don’t need to pay.<br />

                    You will get ONE account first, which you must use for your Trading Test. <br />
                    If you fail the test you can use your credits to do it again for Free. <br />
                    
                    If you Pass, in that case you can use the credits for a New Second Account.<br />
                    
                    We won’t be giving multiple accounts unless they are Passed accounts, because we need to ensure it is always capable traders that are getting the Account, afterall we don’t want to lose our Real Money.
                    
                    </p>
                </div>
                
                <!-- FAQ Item 5 -->
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
    
    <!-- Gorgeous Modal for Buy Option -->
    <div id="modal" class="fixed inset-0 bg-gradient-to-br from-primary-purple/90 via-purple-900/90 to-header-dark/90 backdrop-blur-sm flex items-center justify-center p-4 hidden z-50">
        <div class="bg-gradient-to-br from-white via-gray-50 to-white p-8 rounded-3xl shadow-2xl max-w-lg w-full relative border border-white/20 transform transition-all duration-300 scale-100 hover:scale-[1.02]">
            <!-- Decorative Elements -->
            <div class="absolute -top-4 -left-4 w-8 h-8 bg-trophy-gold rounded-full opacity-20"></div>
            <div class="absolute -top-6 -right-6 w-6 h-6 bg-primary-purple rounded-full opacity-30"></div>
            <div class="absolute -bottom-4 -right-4 w-10 h-10 bg-fomo-red rounded-full opacity-10"></div>

            <!-- Header with Icon -->
            <div class="text-center mb-6">
                <div class="w-20 h-20 bg-gradient-to-br from-trophy-gold to-yellow-600 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <i class="fas fa-shopping-cart text-3xl text-white"></i>
                </div>
                <h2 class="text-2xl font-bold bg-gradient-to-r from-primary-purple to-trophy-gold bg-clip-text text-transparent mb-2">
                    Premium Access
                </h2>
                <div class="w-16 h-1 bg-gradient-to-r from-primary-purple to-trophy-gold rounded-full mx-auto"></div>
            </div>

            <!-- Content -->
            <div class="text-center mb-8">
                <p class="text-gray-700 text-lg leading-relaxed mb-4 font-medium">
                    🚀 <strong>Exciting News!</strong> Our Premium Buy Option is coming soon!
                </p>
                <p class="text-gray-600 leading-relaxed">
                    For now, we're focused on processing all the <span class="text-primary-purple font-semibold">FREE Entries</span> as promised.
                    Check back soon for the enhanced premium experience!
                </p>
            </div>

            <!-- Action Button -->
            <div class="flex justify-center">
                <button type="button"
                        onclick="document.getElementById('modal').classList.add('hidden')"
                        class="px-8 py-3 bg-gradient-to-r from-primary-purple to-purple-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200 border border-white/20">
                    <i class="fas fa-times mr-2"></i>
                    Got it, thanks!
                </button>
            </div>

            <!-- Footer Note -->
            <div class="mt-6 text-center">
                <p class="text-xs text-gray-500 italic">
                    Stay tuned for updates on our Telegram channel
                </p>
            </div>
        </div>
    </div>
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

        // Knowledge Quiz Modal - Show after 20 seconds
        <?php if ($user && empty($user['quiz_result']) && !$showEmailModal): ?>
        document.addEventListener('DOMContentLoaded', function() {
            // Show quiz modal after 20 seconds
            setTimeout(function() {
                const quizModal = document.getElementById('quiz-modal');
                if (quizModal) {
                    quizModal.style.display = 'flex';
                    
                    // Add fade-in animation
                    quizModal.style.opacity = '0';
                    quizModal.style.transition = 'opacity 0.5s ease-in-out';
                    setTimeout(() => {
                        quizModal.style.opacity = '1';
                    }, 10);
                }
            }, 10000); // 20 seconds
        });
        
        // Function to close quiz modal
        function closeQuizModal() {
            const quizModal = document.getElementById('quiz-modal');
            if (quizModal) {
                // Add fade-out animation
                quizModal.style.opacity = '0';
                setTimeout(() => {
                    quizModal.style.display = 'none';
                }, 500); // Wait for fade-out animation to complete
            }
        }
        <?php endif; ?>

    </script>
    <script>
        const USER_EMAIL_VERIFIED = <?php echo ($user && $user['email_verified'] == 1) ? 'true' : 'false'; ?>;
        const USER_QUIZ_COMPLETED = <?php echo ($user && !empty($user['quiz_result'])) ? 'true' : 'false'; ?>;

        const topics = [
            { id: 1, name: "1. Verify your Email Address", isCompleted: <?php echo ($user && $user['email_verified'] == 1) ? 'true' : 'false'; ?> },
            { id: 2, name: "2. Refer 5 Forex Traders (optional)", isCompleted: <?php echo ($user && $verifiedReferrals >= 5) ? 'true' : 'false'; ?> },
            { id: 3, name: "3. Complete the Knowledge Check", redirectTo: "knowledge-test.php", isCompleted: <?php echo ($user && !empty($user['knowledge_test_result'])) ? 'true' : 'false'; ?> },
            { id: 4, name: "4. Pass the Trading Test 1", redirectTo: "quiz.php", isCompleted: false },
            { id: 5, name: "5. Pass the Trading Test 2", redirectTo: "knowledge-test.php", isCompleted: false },
            { id: 6, name: "6. Get your $5000 Funded Account", isCompleted: false }
        ];

        const checklistContainer = document.getElementById('topic-checklist');
        const progressBar = document.getElementById('progress-bar');
        const progressText = document.getElementById('progress-text');
        const completeButton = document.getElementById('complete-button');
        
        function renderChecklist() {
            checklistContainer.innerHTML = topics.map(topic => `
                <div id="topic-${topic.id}" data-id="${topic.id}"
                    class="topic-item p-4 rounded-xl shadow-md flex items-center justify-between 
                            border border-border-light 
                            ${topic.isCompleted ? 'completed border-green-500' : 'bg-white'}
                            cursor-pointer transition duration-200 
                            hover:bg-gray-100 hover:shadow-lg hover:scale-[1.01]"
                    onclick="toggleCompletion(${topic.id})">
                    
                    <span class="text-lg font-medium ${topic.isCompleted ? 'text-success-green line-through' : 'text-header-dark'}">
                        ${topic.name}
                    </span>

                    <!-- Checkmark Icon -->
                    <div class="w-8 h-8 rounded-full flex items-center justify-center transition duration-300
                                ${topic.isCompleted ? 'bg-success-green' : 'bg-gray-200'}">
                        <svg class="w-5 h-5 text-white checkmark-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                             style="${topic.isCompleted ? 'opacity: 1;' : 'opacity: 0;'}">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </div>
            `).join('');
            
            updateProgress();
        }

        function toggleCompletion(id) {
            const topic = topics.find(t => t.id === id);

            if (id === 1 || id === 2) {
                showNoModifyModal();
                return;
            }

            if (id === 3) {
                if (!USER_EMAIL_VERIFIED || !USER_QUIZ_COMPLETED) {
                    showNotReadyModal();
                    return;
                }
                window.location.href = topic.redirectTo;
                return;
            }
            // else if (id === 4) {
            //     if (!USER_EMAIL_VERIFIED) {
            //         showNotReadyModal();
            //         return;
            //     }
            //     window.location.href = topic.redirectTo;
            //     return;
            // } 
            else {
                // Already completed → you decide (block)
                showNoModifyModalForSpecificID();
                return;
            }

            // Fallback
            showNoModifyModal();
        }

        function showNoModifyModal() {
            const container = document.createElement('div');
            container.className = 'fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center z-50';
            container.innerHTML = `
                <div class="bg-white p-6 rounded-lg shadow-2xl w-full max-w-sm transform transition-all scale-100 duration-300">
                    <h4 class="text-xl font-bold text-primary-purple mb-3">Cannot Modify</h4>
                    <p class="text-gray-700 mb-6">You cannot modify the checklist items. They are automatically updated based on your progress.</p>
                    <button onclick="document.body.removeChild(this.parentNode.parentNode)" class="w-full py-2 bg-primary-purple text-white rounded-lg font-semibold hover:bg-trophy-gold hover:text-header-dark transition">
                        Close
                    </button>
                </div>
            `;
            document.body.appendChild(container);
        }

        function showNoModifyModalForSpecificID() {
            const container = document.createElement('div');
            container.className = 'fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center z-50';
            container.innerHTML = `
                <div class="bg-white p-6 rounded-lg shadow-2xl w-full max-w-sm transform transition-all scale-100 duration-300">
                    <h4 class="text-xl font-bold text-primary-purple mb-3">Upcoming..</h4>
                    <p class="text-gray-700 mb-6">This is not ready yet. We will inform you in the Telegram group when it's Ready.</p>
                    <button onclick="document.body.removeChild(this.parentNode.parentNode)" class="w-full py-2 bg-primary-purple text-white rounded-lg font-semibold hover:bg-trophy-gold hover:text-header-dark transition">
                        Close
                    </button>
                </div>
            `;
            document.body.appendChild(container);
        }

        function showNotReadyModal() {
            const container = document.createElement('div');
            container.className = 'fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center z-50';
            container.innerHTML = `
                <div class="bg-white p-6 rounded-lg shadow-2xl w-full max-w-sm">
                    <h4 class="text-xl font-bold text-red-600 mb-3">Not Ready</h4>
                    <p class="text-gray-700 mb-6">Sorry, you are not ready for this yet.<br>
                    Please complete the required steps above.</p>
                    <button onclick="document.body.removeChild(this.parentNode.parentNode)" 
                            class="w-full py-2 bg-red-600 text-white rounded-lg font-semibold hover:bg-red-700 transition">
                        Close
                    </button>
                </div>
            `;
            document.body.appendChild(container);
        }

        function updateProgress() {
            const completedCount = topics.filter(t => t.isCompleted).length;
            const percentage = (completedCount / topics.length) * 100;
            
            // Update Progress Bar
            progressBar.style.width = `${percentage}%`;
            
            // Update Text
            progressText.textContent = `${completedCount}/${topics.length} Topics Completed`;

            // Update Completion Button
            if (completedCount === topics.length) {
                completeButton.disabled = false;
                completeButton.classList.remove('opacity-50', 'cursor-not-allowed');
                completeButton.classList.add('bg-success-green', 'text-white', 'hover:bg-green-600');
                completeButton.onclick = () => alertMessage("Section 2 Complete!", "All topics are finished. You are ready to proceed to Advanced Concepts.");
            } else {
                completeButton.disabled = true;
                completeButton.classList.add('opacity-50', 'cursor-not-allowed');
                completeButton.classList.remove('bg-success-green', 'text-white', 'hover:bg-green-600');
                completeButton.onclick = null;
            }
        }

        // Custom alert/message function (since we cannot use window.alert)
        function alertMessage(title, message) {
            const container = document.createElement('div');
            container.className = 'fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center z-50';
            
            container.innerHTML = `
                <div class="bg-white p-6 rounded-lg shadow-2xl w-full max-w-sm transform transition-all scale-100 duration-300">
                    <h4 class="text-xl font-bold text-primary-purple mb-3">${title}</h4>
                    <p class="text-gray-700 mb-6">${message}</p>
                    <button onclick="document.body.removeChild(this.parentNode.parentNode)" class="w-full py-2 bg-primary-purple text-white rounded-lg font-semibold hover:bg-trophy-gold hover:text-header-dark transition">
                        Close
                    </button>
                </div>
            `;
            document.body.appendChild(container);
        }

        // Initial render
        document.addEventListener('DOMContentLoaded', renderChecklist);
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
