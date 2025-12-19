<?php

    // referral_dashboard.php

    session_start();

    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
        header('Location: login.php');
        exit;
    }

    // Include database connection
    require_once 'database.php';

    // Get database connection
    $pdo = getPDO();

    // Initialize variables
    $user = null;
    $referrals = [];
    $showEmailModal = false;
    $showPasswordSetupModal = false;
    $showPasswordModal = false;
    $emailError = '';
    $passwordError = '';
    $passwordSuccess = '';
    $userEmail = '';
    $userName = '';

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
                    // User is verified, proceed normally
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

    function sendTemplateEmail($templateId, $user)
    {
        // Fetch template
        global $pdo;

        $templateStmt = $pdo->prepare("
            SELECT name, subject, body 
            FROM email_templates 
            WHERE id = ?
        ");
        $templateStmt->execute([$templateId]);
        $template = $templateStmt->fetch(PDO::FETCH_ASSOC);

        // Send email if template found
        if ($template) {
            require_once __DIR__ . "/email_verification.php";

            EmailVerification::sendCustomEmail(
                $user['email'],
                $user['name'],
                $template['subject'],
                $template['body']
            );
        }
    }

    // Get current user data from session
    $userId = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT * FROM waitlist_users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        if (isset($user['paid_user']) && (int)$user['paid_user'] === 1) {
            header('Location: my_dashboard.php');
            exit;
        }
        // Check if user status is inactive
        if (isset($user['status']) && $user['status'] === 'inactive') {
            $emailError = 'Your account is currently inactive. Please contact support for assistance.';
            $user = null; // Reset user to show modal with error
            $showEmailModal = true;
        }
    } else {
        // User not found, redirect to login
        header('Location: login.php');
        exit;
    }

    if ($user) {

        // Load mt5_details
        $mt_stmt = $pdo->prepare("SELECT * FROM mt5_details WHERE user_id = ?");
        $mt_stmt->execute([$user['id']]);
        $mt5_details = $mt_stmt->fetch(PDO::FETCH_ASSOC);

        // Load mt5_details_second
        $mt_stmt_second = $pdo->prepare("SELECT * FROM mt5_details_second WHERE user_id = ?");
        $mt_stmt_second->execute([$user['id']]);
        $mt5_details_second = $mt_stmt_second->fetch(PDO::FETCH_ASSOC);

        // Fetch direct referrals
        $stmt = $pdo->prepare("
            SELECT id, name, country, user_ip, status, quiz_result, user_credit, knowledge_test_result, created_at, email_verified
            FROM waitlist_users 
            WHERE parent_user_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$user['id']]);
        $referrals = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // First level counts
        $totalReferrals = count($referrals);
        $verifiedReferrals = 0;
        $pendingReferrals = 0;

        // Fetch payments for the user (Payment History)
        $paymentsStmt = $pdo->prepare("SELECT * FROM payments WHERE user_id = ? ORDER BY created_at DESC");
        $paymentsStmt->execute([$user['id']]);
        $payments = $paymentsStmt->fetchAll(PDO::FETCH_ASSOC);

        $congratulationsAwarded = false;
        // Array of verified referral IDs (your 5 verified users)
        $verifiedReferralIDs = [];

        foreach ($referrals as $referral) {
            if ($referral['email_verified'] == 1 &&
                $referral['quiz_result'] != null &&
                $referral['user_ip'] !== $user['user_ip']
            ) {
                $verifiedReferrals++;
                $verifiedReferralIDs[] = [
                    'id' => $referral['id'],
                    'user_ip' => $referral['user_ip']
                ];
            } else {
                $pendingReferrals++;
            }
        }

        // ONLY EXECUTE LOGIC FIRST TIME
        if ($user['user_credit'] <= 0 && $user['manual_credit_update'] == false) {

            $secondLevelVerifiedCount = 0;
            $hasAnySecondLevelChild = false;

            if (!$mt5_details && $verifiedReferrals >= 5) {

                $verifiedCheckStmt = $pdo->prepare("
                    SELECT
                        email_verified,
                        quiz_result,
                        user_ip,
                        parent_user_id
                    FROM waitlist_users
                    WHERE parent_user_id = ?
                ");

                foreach ($verifiedReferralIDs as $verifiedUser) {
                    $verifiedCheckStmt->execute([$verifiedUser['id']]);
                    $childRefs = $verifiedCheckStmt->fetchAll(PDO::FETCH_ASSOC);

                    // Track ANY second-level children
                    if (!empty($childRefs)) {
                        $hasAnySecondLevelChild = true;
                    }

                    foreach ($childRefs as $child) {
                        if (
                            $child['email_verified'] == 1 &&
                            $child['quiz_result'] != null &&
                            $verifiedUser['user_ip'] !== $child['user_ip']
                        ) {
                            $secondLevelVerifiedCount++;
                        }
                    }
                }

                if($secondLevelVerifiedCount == 0) {
                    $hasAnySecondLevelChild = true;
                }
            }

            // APPROVE — only first time
            if ($secondLevelVerifiedCount >= 1) {

                $pdo->prepare("UPDATE waitlist_users SET user_credit = 1 WHERE id = ?")
                    ->execute([$user['id']]);

                // APPROVE knowledge test
                $pdo->prepare("
                    INSERT INTO knowledge_test_approvals (user_id, approval_status, approved_at)
                    VALUES (?, 'approved', NOW())
                    ON DUPLICATE KEY UPDATE approval_status = 'approved', approved_at = NOW(), declined_reason = NULL
                ")->execute([$user['id']]);

                sendTemplateEmail(2, $user);
                $congratulationsAwarded = true;
            }

            // DECLINE — only first time
            else if ($user['user_credit'] == 0 && $verifiedReferrals >= 5 && $hasAnySecondLevelChild) {

                $pdo->prepare("UPDATE waitlist_users SET user_credit = -1 WHERE id = ?")
                    ->execute([$user['id']]);

                $pdo->prepare("
                    INSERT INTO knowledge_test_approvals (user_id, approval_status, declined_reason)
                    VALUES (?, 'declined', 'User has no second-level referrals')
                    ON DUPLICATE KEY UPDATE approval_status = 'declined', declined_reason = 'User has no second-level referrals', approved_at = NULL
                ")->execute([$user['id']]);

                sendTemplateEmail(24, $user);

                $congratulationsAwarded = false;
            } else {
                // Do nothing, wait for more referrals
                $congratulationsAwarded = false;
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

        // Dynamic pricing for checkout
        $checkoutPrice = 36;

        $_SESSION['checkout_price'] = $checkoutPrice;
    }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Fnding4x User Dashboard – Get Funded Account for Free</title>
    <meta name="description" content="Access your Funding4x account dashboard. Track your trial, evaluations, and funded trading progress.">
    <meta name="keywords" content="Funding4x dashboard, funded account dashboard, trading progress, prop firm account">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon-16x16.png">

    <link rel="manifest" href="assets/site.webmanifest">
    <!-- Load Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <!-- Icon library for symbols -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <style>
        /* Configure Tailwind for Inter font and prestigious purple colors */
        :root {
            --primary-purple: #4f009d; /* Deep Royal Purple */
            --header-dark: #240046; /* Very Dark Purple/Black */
            --trophy-gold: #b49852; /* Muted Gold for prestige */
            --success-green: #10b981; /* Green for success/funded status */
            --fail-red: #ef4444; /* Red for failure */
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6; /* Light gray background for contrast */
        }
        .header-bg {
            background-color: var(--header-dark);
        }
        .card {
            background-color: white;
            border-radius: 1rem; /* Rounded corners */
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            transition: transform 0.3s ease;
        }
        .card:hover {
             /* Light hover effect only on smaller cards */
            /* transform: translateY(-2px); */
        }
        
        /* Custom ring for the profile picture */
        .profile-ring {
            border: 4px solid var(--trophy-gold);
            padding: 2px;
            box-shadow: 0 0 15px rgba(180, 152, 82, 0.5);
        }
        
        /* Gradient for the header banner */
        .banner-bg {
            background-image: linear-gradient(135deg, var(--primary-purple) 0%, var(--header-dark) 100%);
        }

        /* Table specific styles for better look */
        .table-header {
            background-color: var(--primary-purple);
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .table-row:nth-child(even) {
            background-color: #f9fafb;
        }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        'primary-purple': '#4f009d',
                        'trophy-gold': '#b49852',
                        'header-dark': '#240046',
                        'success-green': '#10b981',
                        'fail-red': '#ef4444',
                    },
                }
            }
        }
        
        /**
         * Toggles the visibility of a detailed content block (e.g., MT5 credentials).
         * @param {string} targetId The ID of the div to toggle (e.g., 'mt5-details-1').
         * @param {string} iconId The ID of the icon to rotate (e.g., 'icon-1').
         */
        function toggleDetails(targetId, iconId) {
            const target = document.getElementById(targetId);
            const icon = document.getElementById(iconId);
            
            if (target.classList.contains('hidden')) {
                target.classList.remove('hidden');
                icon.classList.add('rotate-180');
            } else {
                target.classList.add('hidden');
                icon.classList.remove('rotate-180');
            }
        }

        /**
         * Copies text to the clipboard.
         * @param {string} text The text to copy.
         * @param {string} buttonId The ID of the button to provide feedback on.
         */
        function copyToClipboard(text, buttonId) {
            const button = document.getElementById(buttonId);
            
            // Create a temporary input element
            const tempInput = document.createElement('input');
            tempInput.value = text;
            document.body.appendChild(tempInput);
            
            // Select the text
            tempInput.select();
            tempInput.setSelectionRange(0, 99999); // For mobile devices
            
            // Copy the text
            try {
                document.execCommand('copy');
                button.innerHTML = '<i class="fas fa-check"></i> Copied!';
                button.classList.add('bg-success-green');
                button.classList.remove('bg-primary-purple');
            } catch (err) {
                console.error('Copy failed:', err);
                button.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error';
            }
            
            // Clean up and reset button text
            document.body.removeChild(tempInput);
            setTimeout(() => {
                button.innerHTML = '<i class="fas fa-copy"></i> Copy';
                button.classList.remove('bg-success-green');
                button.classList.add('bg-primary-purple');
            }, 2000);
        }
    </script>
</head>

<body>
    <!-- Email Modal - NON-CLOSABLE when email verification is needed -->
    <?php if ($showEmailModal): ?>
    <div id="email-modal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4"
          style="display: flex; pointer-events: auto;">
        <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-md w-full">
            <div class="text-center mb-6">
                <h2 class="text-2xl font-bold text-primary-purple mb-2">
                    Email Verification Required
                </h2>
                <p class="text-gray-600">
                    You need to verify your email address to access the dashboard.
                </p>
            </div>

            <?php if (!empty($emailError)): ?>
            <div class="bg-red-100 border border-red-300 rounded-lg p-3 mb-4">
                <p class="text-red-700 text-sm"><?php echo htmlspecialchars($emailError); ?></p>
            </div>
            <?php endif; ?>

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
        </div>
    </div>
    <?php endif; ?>


    <!-- Knowledge Quiz Modal (Green) -->
    <?php if ($user && empty($user['quiz_result']) && !$showEmailModal && !$showPasswordModal && !$showPasswordSetupModal): ?>
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

    <!-- HEADER -->
    <header class="header-bg bg-gradient-to-br from-primary-purple/90 via-purple-900/90 to-header-dark/90 text-white shadow-2xl sticky top-0 z-20 backdrop-blur-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- TOP BAR -->
            <div class="flex justify-between items-center py-4">

                <!-- LOGO -->
                <div class="flex items-center space-x-3">
                    <img src="assets/logo.png" class="h-10 w-10 rounded-lg" alt="Logo">
                    <h1 class="text-xl font-extrabold tracking-tight text-trophy-gold">
                        REFERRAL DASHBOARD
                    </h1>
                </div>

                <!-- WELCOME TEXT (DESKTOP ONLY) -->
                <div class="hidden md:flex items-center space-x-6">
                    <span class="text-sm text-gray-200">
                        Welcome, <?php echo htmlspecialchars($user['name']); ?>
                    </span>
                </div>

                <!-- MOBILE HAMBURGER -->
                <button id="menuToggle" 
                    class="md:hidden p-2 rounded-lg border border-white/20 bg-white/10 backdrop-blur-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>

            <!-- NAVIGATION LINKS -->
            <nav class="border-t border-white/20">
                
                <!-- DESKTOP MENU -->
                <ul class="hidden md:flex justify-center space-x-10 py-3">
                    <li>
                        <a href="referral_dashboard.php" 
                        class="text-sm hover:text-trophy-gold transition font-medium">
                        Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="rule.php" 
                        class="text-sm hover:text-trophy-gold transition font-medium">
                        Rules
                        </a>
                    </li>
                    <li>
                        <a href="logout.php" 
                        class="text-sm text-red-300 hover:text-red-200 transition font-medium">
                        Logout
                        </a>
                    </li>
                </ul>

                <!-- MOBILE MENU -->
                <ul id="mobileMenu"
                    class="md:hidden hidden flex-col py-3 space-y-2 bg-gradient-to-br from-primary-purple via-purple-900 to-header-dark border-t border-white/10 rounded-b-xl">
                    
                    <li>
                        <a href="referral_dashboard.php" 
                        class="block py-2 px-4 hover:bg-white/10 rounded-lg">
                        Dashboard
                        </a>
                    </li>

                    <li>
                        <a href="rule.php" 
                        class="block py-2 px-4 hover:bg-white/10 rounded-lg">
                        Rules
                        </a>
                    </li>

                    <li class="pt-3 border-t border-white/10">
                        <a href="logout.php" 
                        class="block py-2 px-4 text-red-300 hover:bg-red-500/20 rounded-lg">
                        Logout
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12">

        <!-- Banner with Profile Card -->
        <div class="banner-bg h-40 rounded-t-xl relative mb-16">
            <div class="absolute -bottom-16 left-1/2 transform -translate-x-1/2 w-full max-w-4xl px-4">
                
                <!-- Profile Card -->
                <div class="card p-6 flex flex-col md:flex-row items-center md:items-start text-center md:text-left">
                    
                    <!-- Profile Picture and Ring -->
                    <div class="mb-4 md:mb-0 md:mr-6 flex-shrink-0">
                        <?php
                            $nameDisplay = htmlspecialchars($user['name'] ?? ($user['email'] ?? 'User'));
                            $initials = '';
                            if (!empty($user['name'])) {
                                $parts = preg_split('/\s+/', trim($user['name']));
                                foreach ($parts as $p) {
                                    if ($p !== '') $initials .= strtoupper(mb_substr($p,0,1));
                                    if (strlen($initials) >= 2) break;
                                }
                            } else {
                                $initials = strtoupper(substr($user['email'] ?? 'U',0,2));
                            }
                            $initials = $initials ?: 'U';
                            $avatarUrl = 'https://placehold.co/100x100/4f009d/ffffff?text=' . urlencode($initials);
                        ?>
                        <img src="<?php echo $avatarUrl; ?>" alt="Profile Avatar" class="h-24 w-24 rounded-full profile-ring object-cover">
                    </div>

                    <!-- User Info -->
                    <div class="flex-grow">
                        <h2 class="text-3xl font-extrabold text-header-dark">
                            <?php echo $nameDisplay; ?>
                        </h2>
                        <p class="text-lg text-gray-500 mt-1">
                            Trader ID: <span class="font-mono text-sm bg-gray-100 p-1 rounded-md"><?php echo htmlspecialchars($user['referral_code'] ?? $user['id']); ?></span>
                        </p>
                        <p class="text-sm text-gray-400 mt-2">
                            Member Since: <?php echo (!empty($user['created_at'])) ? date('F j, Y', strtotime($user['created_at'])) : '—'; ?>
                        </p>
                    </div>

                    <!-- Status Badge (Dynamic) -->
                    <div class="md:ml-auto mt-4 md:mt-0 flex-shrink-0">
                        <?php
                            $badge = '<span class="inline-flex items-center px-4 py-1.5 rounded-full text-sm font-bold bg-primary-purple/10 text-primary-purple shadow-lg"><i class="fas fa-user mr-2"></i> MEMBER</span>';
                            if (!empty($mt5_details) || !empty($mt5_details_second) || (isset($user['paid_user']) && (int)$user['paid_user'] === 1)) {
                                $badge = '<span class="inline-flex items-center px-4 py-1.5 rounded-full text-sm font-bold bg-success-green text-white shadow-lg"><i class="fas fa-check-circle mr-2"></i> FUNDED TRADER</span>';
                            } elseif (isset($user['status']) && $user['status'] === 'inactive') {
                                $badge = '<span class="inline-flex items-center px-4 py-1.5 rounded-full text-sm font-bold bg-fail-red text-white shadow-lg"><i class="fas fa-times-circle mr-2"></i> INACTIVE</span>';
                            } elseif (empty($user['quiz_result'])) {
                                $badge = '<span class="inline-flex items-center px-4 py-1.5 rounded-full text-sm font-bold bg-primary-purple text-white shadow-lg"><i class="fas fa-graduation-cap mr-2"></i> TAKE QUIZ</span>';
                            }
                            echo $badge;
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- LEFT COLUMN (Bio & Referrals Stats) -->
            <div class="lg:col-span-1 space-y-8">
                
                <!-- Bio & Goals Card -->
                <div class="card">
                    <h3 class="text-xl font-bold text-primary-purple mb-4 flex items-center">
                        <i class="fas fa-user-circle mr-2"></i> Trader Bio & Goals
                    </h3>
                    <p class="text-gray-700 leading-relaxed">
                        Enthusiastic part-time trader focused on EUR/USD and XAU/USD pairs. I believe in strict risk management and technical analysis. My goal is to achieve the $100k funded level by the end of the year.
                    </p>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-sm text-gray-500 font-medium">Favorite Pairs:</p>
                        <div class="mt-1 space-x-2">
                            <span class="bg-primary-purple/10 text-primary-purple text-xs font-semibold px-3 py-1 rounded-full">EUR/USD</span>
                            <span class="bg-trophy-gold/10 text-trophy-gold text-xs font-semibold px-3 py-1 rounded-full">XAU/USD (Gold)</span>
                        </div>
                    </div>
                </div>
                
                <!-- Referral Stats Card -->
                <div class="card">
                    <h3 class="text-xl font-bold text-primary-purple mb-4 flex items-center">
                        <i class="fas fa-share-alt mr-2"></i> Referral Network Summary
                    </h3>
<?php
                        // Calculate referral earnings from payments notes (simple heuristic)
                        $referralEarnings = 0;
                        if (!empty($payments)) {
                            foreach ($payments as $p) {
                                if (!empty($p['notes']) && (stripos($p['notes'], 'referral') !== false || stripos($p['notes'], 'commission') !== false)) {
                                    $referralEarnings += (float)$p['amount'];
                                }
                            }
                        }
                    ?>
                    <div class="grid grid-cols-2 gap-4 text-center">
                        <div class="p-4 bg-gray-50 rounded-lg">
                            <p class="text-3xl font-extrabold text-primary-purple"><?php echo (int)$totalReferrals; ?></p>
                            <p class="text-sm text-gray-500 mt-1">Total Referrals</p>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-lg">
                            <p class="text-3xl font-extrabold text-success-green"><?php echo (int)$verifiedReferrals; ?></p>
                            <p class="text-sm text-gray-500 mt-1">Funded Referrals</p>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100 text-center">
                        <p class="text-sm text-gray-500">Total Referral Earnings:</p>
                        <p class="text-3xl font-extrabold text-trophy-gold mt-1"><?php echo '$' . number_format($referralEarnings, 2); ?></p>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN (Trading Accounts) -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Trading Accounts Card -->
                <div class="card">
                    <h3 class="text-xl font-bold text-primary-purple mb-6 flex items-center">
                        <i class="fas fa-briefcase mr-2"></i> Trading Accounts & Evaluations
                    </h3>

                    <!-- Account Container (Start of $100K Challenge) -->
                    <div class="border border-gray-200 rounded-xl mb-6 overflow-hidden">
                        <div class="bg-primary-purple text-white p-4 flex justify-between items-center">
                            <h4 class="text-lg font-semibold">$100,000 Apex Challenge</h4>
                            <span class="bg-trophy-gold text-header-dark text-sm font-bold px-3 py-1 rounded-full">LIVE FUNDED</span>
                        </div>

                        <!-- Phase 2 (Completed/Funded) -->
                        <div class="p-4 border-b border-gray-100 bg-success-green/5">
                            <button onclick="toggleDetails('mt5-details-1', 'icon-1')" class="w-full flex justify-between items-center text-left">
                                <span class="font-bold text-header-dark flex items-center">
                                    <i class="fas fa-check-circle text-success-green mr-3"></i> Phase 2: Verification Complete
                                </span>
                                <span class="text-sm text-success-green font-bold">8.1% Gain</span>
                                <i id="icon-1" class="fas fa-chevron-down text-header-dark transition-transform duration-300"></i>
                            </button>
                            
                            <!-- MT5 Details Content -->
                            <div id="mt5-details-1" class="mt-4 pt-3 border-t border-gray-200 hidden">
                                <p class="font-semibold text-primary-purple mb-2">MT5 Account Details (Funded)</p>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 bg-gray-50 p-3 rounded-lg text-sm">
                                    <div><span class="font-medium">Server:</span> <code class="font-mono">Funding4x-Live</code></div>
                                    <div><span class="font-medium">Login:</span> <code class="font-mono" id="login-1">601934</code></div>
                                    <div><span class="font-medium">Password:</span> <code class="font-mono" id="password-1">ATrade!2025</code></div>
                                </div>
                                <div class="mt-3 flex justify-end space-x-2">
                                    <button id="btn-login-1" onclick="copyToClipboard('601934', 'btn-login-1')" class="text-xs bg-primary-purple text-white px-3 py-1 rounded-full hover:bg-opacity-80 transition"><i class="fas fa-copy"></i> Copy Login</button>
                                    <button id="btn-password-1" onclick="copyToClipboard('ATrade!2025', 'btn-password-1')" class="text-xs bg-primary-purple text-white px-3 py-1 rounded-full hover:bg-opacity-80 transition"><i class="fas fa-copy"></i> Copy Password</button>
                                </div>
                            </div>
                        </div>

                        <!-- Phase 1 (Passed) -->
                        <div class="p-4 border-b border-gray-100 bg-success-green/5">
                            <button onclick="toggleDetails('mt5-details-2', 'icon-2')" class="w-full flex justify-between items-center text-left">
                                <span class="font-bold text-header-dark flex items-center">
                                    <i class="fas fa-check-circle text-success-green mr-3"></i> Phase 1: Qualification Passed
                                </span>
                                <span class="text-sm text-success-green font-bold">10.3% Gain</span>
                                <i id="icon-2" class="fas fa-chevron-down text-header-dark transition-transform duration-300"></i>
                            </button>
                            
                            <!-- MT5 Details Content -->
                            <div id="mt5-details-2" class="mt-4 pt-3 border-t border-gray-200 hidden">
                                <p class="font-semibold text-primary-purple mb-2">MT5 Account Details (Phase 1)</p>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 bg-gray-50 p-3 rounded-lg text-sm">
                                    <div><span class="font-medium">Server:</span> <code class="font-mono">Funding4x-Demo</code></div>
                                    <div><span class="font-medium">Login:</span> <code class="font-mono" id="login-2">501934</code></div>
                                    <div><span class="font-medium">Password:</span> <code class="font-mono" id="password-2">P1-Verify</code></div>
                                </div>
                                <div class="mt-3 flex justify-end space-x-2">
                                    <button id="btn-login-2" onclick="copyToClipboard('501934', 'btn-login-2')" class="text-xs bg-primary-purple text-white px-3 py-1 rounded-full hover:bg-opacity-80 transition"><i class="fas fa-copy"></i> Copy Login</button>
                                    <button id="btn-password-2" onclick="copyToClipboard('P1-Verify', 'btn-password-2')" class="text-xs bg-primary-purple text-white px-3 py-1 rounded-full hover:bg-opacity-80 transition"><i class="fas fa-copy"></i> Copy Password</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End of $100K Challenge Account -->

                    <!-- Account Container (Start of $50K Test) -->
                    <div class="border border-gray-200 rounded-xl overflow-hidden">
                        <div class="bg-header-dark text-white p-4 flex justify-between items-center">
                            <h4 class="text-lg font-semibold">$50,000 Quick Test</h4>
                            <span class="bg-primary-purple text-white text-sm font-bold px-3 py-1 rounded-full">IN PROGRESS</span>
                        </div>

                        <!-- Phase 1 (In Progress) -->
                        <div class="p-4 border-b border-gray-100 bg-primary-purple/5">
                            <button onclick="toggleDetails('mt5-details-3', 'icon-3')" class="w-full flex justify-between items-center text-left">
                                <span class="font-bold text-header-dark flex items-center">
                                    <i class="fas fa-spinner fa-spin text-primary-purple mr-3"></i> Phase 1: Qualification (45%)
                                </span>
                                <span class="text-sm text-primary-purple font-bold">4.5% Gain</span>
                                <i id="icon-3" class="fas fa-chevron-down text-header-dark transition-transform duration-300"></i>
                            </button>
                            
                            <!-- MT5 Details Content -->
                            <div id="mt5-details-3" class="mt-4 pt-3 border-t border-gray-200 hidden">
                                <p class="font-semibold text-primary-purple mb-2">MT5 Account Details (Active)</p>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 bg-gray-50 p-3 rounded-lg text-sm">
                                    <div><span class="font-medium">Server:</span> <code class="font-mono">Funding4x-Demo</code></div>
                                    <div><span class="font-medium">Login:</span> <code class="font-mono" id="login-3">408712</code></div>
                                    <div><span class="font-medium">Password:</span> <code class="font-mono" id="password-3">QuickTest!</code></div>
                                </div>
                                <div class="mt-3 flex justify-end space-x-2">
                                    <button id="btn-login-3" onclick="copyToClipboard('408712', 'btn-login-3')" class="text-xs bg-primary-purple text-white px-3 py-1 rounded-full hover:bg-opacity-80 transition"><i class="fas fa-copy"></i> Copy Login</button>
                                    <button id="btn-password-3" onclick="copyToClipboard('QuickTest!', 'btn-password-3')" class="text-xs bg-primary-purple text-white px-3 py-1 rounded-full hover:bg-opacity-80 transition"><i class="fas fa-copy"></i> Copy Password</button>
                                </div>
                            </div>
                        </div>

                        <!-- Phase 1 (Failed Attempt) -->
                        <div class="p-4 bg-fail-red/5">
                            <button onclick="toggleDetails('mt5-details-4', 'icon-4')" class="w-full flex justify-between items-center text-left">
                                <span class="font-bold text-header-dark flex items-center">
                                    <i class="fas fa-times-circle text-fail-red mr-3"></i> Phase 1: Initial Attempt Failed
                                </span>
                                <span class="text-sm text-fail-red font-bold">Max DD Breach</span>
                                <i id="icon-4" class="fas fa-chevron-down text-header-dark transition-transform duration-300"></i>
                            </button>
                            
                             <!-- MT5 Details Content (for review) -->
                            <div id="mt5-details-4" class="mt-4 pt-3 border-t border-gray-200 hidden">
                                <p class="font-semibold text-primary-purple mb-2">MT5 Account Details (Failed Attempt)</p>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 bg-gray-50 p-3 rounded-lg text-sm opacity-50">
                                    <div><span class="font-medium">Server:</span> <code class="font-mono">Funding4x-Demo</code></div>
                                    <div><span class="font-medium">Login:</span> <code class="font-mono">400010</code></div>
                                    <div><span class="font-medium">Password:</span> <code class="font-mono">Old-Test</code></div>
                                </div>
                                <p class="text-xs text-fail-red mt-2">This account is closed due to a rule breach (Max Daily Drawdown).</p>
                            </div>
                        </div>
                    </div>
                    <!-- End of $50K Test Account -->

                </div>
            </div>
        </div>

        <!-- Referral History Table -->
        <div class="mt-8">
            <div class="card p-0 overflow-hidden">
                <h3 class="text-xl font-bold text-primary-purple p-4 flex items-center border-b border-gray-100">
                    <i class="fas fa-users mr-2"></i> Full Referral History
                </h3>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-bold table-header rounded-tl-xl">Referral Name</th>
                                <th class="px-6 py-3 text-left text-xs font-bold table-header">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-bold table-header">Date Joined</th>
                                <th class="px-6 py-3 text-right text-xs font-bold table-header rounded-tr-xl">Commission Earned</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 text-gray-700">
<?php if (!empty($referrals)): ?>
    <?php foreach ($referrals as $r): ?>
        <tr class="table-row">
            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($r['name'] ?: ($r['email'] ?? '—')); ?></td>
            <td class="px-6 py-4 whitespace-nowrap"><?php
                $statusText = 'Pending';
                $statusClass = 'text-gray-600';
                if ($r['email_verified'] == 1 && $r['quiz_result'] != null && $r['user_ip'] !== $user['user_ip']) {
                    $statusText = 'Funded';
                    $statusClass = 'text-success-green';
                } else if ($r['quiz_result'] == null) {
                    $statusText = 'Phase 1';
                    $statusClass = 'text-primary-purple';
                } else {
                    $statusText = 'Failed/Retry';
                    $statusClass = 'text-fail-red';
                }
                echo "<span class=\"$statusClass font-medium\">$statusText</span>";
            ?></td>
            <td class="px-6 py-4 whitespace-nowrap"><?php echo date('M j, Y', strtotime($r['created_at'])); ?></td>
            <td class="px-6 py-4 whitespace-nowrap text-right"><?php echo '$' . number_format(($r['commission'] ?? 0), 2); ?></td>
        </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr class="table-row">
        <td colspan="4" class="px-6 py-4 text-center">No referrals yet.</td>
    </tr>
<?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Payment History Table -->
        <div class="mt-8">
            <div class="card p-0 overflow-hidden">
                <h3 class="text-xl font-bold text-primary-purple p-4 flex items-center border-b border-gray-100">
                    <i class="fas fa-receipt mr-2"></i> Payment History
                </h3>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-bold table-header rounded-tl-xl">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-bold table-header">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-bold table-header">Description</th>
                                <th class="px-6 py-3 text-right text-xs font-bold table-header">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-bold table-header rounded-tr-xl">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 text-gray-700">
<?php if (!empty($payments)): ?>
    <?php foreach ($payments as $p): ?>
        <tr class="table-row">
            <td class="px-6 py-4 whitespace-nowrap"><?php echo date('M j, Y', strtotime($p['created_at'])); ?></td>
            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $p['payment_method'] ?? ''))); ?></td>
            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($p['notes'] ?? ($p['payment_gateway'] ?? '')); ?></td>
            <td class="px-6 py-4 whitespace-nowrap text-right <?php echo (($p['amount'] < 0) ? 'text-fail-red' : 'text-success-green'); ?>"><?php echo (($p['amount'] < 0) ? '-' : '') . '$' . number_format(abs($p['amount']), 2); ?></td>
            <td class="px-6 py-4 whitespace-nowrap"><span class="<?php echo (($p['status'] == 'completed') ? 'text-success-green' : 'text-fail-red'); ?> font-medium"><?php echo ucfirst($p['status']); ?></span></td>
        </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr class="table-row">
        <td colspan="5" class="px-6 py-4 text-center">No payment history yet.</td>
    </tr>
<?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pie Chart Section -->
            <!-- <div class="lg:col-span-1">
                <div class="card">
                    <h3 class="text-xl font-bold text-primary-purple mb-4 flex items-center">
                        <i class="fas fa-chart-pie mr-2"></i> Referrals Breakdown
                    </h3>
                    <?php if ($totalReferrals > 0): ?>
                        <div class="pie-chart">
                            <canvas id="referralPieChart"></canvas>
                        </div>
                        <div class="mt-4 text-center text-sm text-gray-600">
                            <div>Completed: <?php echo $verifiedReferrals; ?></div>
                            <div>Total: <?php echo $totalReferrals; ?></div>
                        </div>
                    <?php else: ?>
                        <p class="text-sm text-gray-500 text-center">No referrals yet to analyze.</p>
                    <?php endif; ?>
                </div>
            </div> -->
        </div>

    </main>

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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        <?php if ($user && $totalReferrals > 0): ?>
            const ctx = document.getElementById('referralPieChart').getContext('2d');
            const chart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Completed', 'Pending'],
                    datasets: [{
                        data: [<?php echo $verifiedReferrals; ?>, <?php echo $pendingReferrals; ?>],
                        backgroundColor: ['#28a745', '#6b7280']
                    }]
                },
                options: {
                    responsive: true,
                    cutout: '70%'
                }
            });
        <?php endif; ?>

        // Clipboard copy for referral link
        function copyReferralLink() {
            const link = document.getElementById('referralLink').value;
            navigator.clipboard.writeText(link).then(() => {
                alert('Referral link copied to clipboard');
            });
        }
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