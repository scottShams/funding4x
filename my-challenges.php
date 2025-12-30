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

        // Load challenges for user (each challenge contains Phase 1 & Phase 2 mt5 records)
        $challengeStmt = $pdo->prepare("SELECT * FROM challenges WHERE user_id = ? ORDER BY created_at DESC");
        $challengeStmt->execute([$user['id']]);
        $challenges = $challengeStmt->fetchAll(PDO::FETCH_ASSOC);

        // Build challenge data including Phase 1 and Phase 2 rows
        $challengeData = [];
        $mtStmt = $pdo->prepare("SELECT * FROM mt5_details WHERE challenge_id = ?");
        $mtStmt2 = $pdo->prepare("SELECT * FROM mt5_details_second WHERE challenge_id = ?");
        foreach ($challenges as $c) {
            $mtStmt->execute([$c['id']]);
            $p1 = $mtStmt->fetch(PDO::FETCH_ASSOC);
            $mtStmt2->execute([$c['id']]);
            $p2 = $mtStmt2->fetch(PDO::FETCH_ASSOC);
            $challengeData[] = [
                'challenge' => $c,
                'phase1' => $p1,
                'phase2' => $p2
            ];
        }

        // Load user's mt5 details (if any) to preserve legacy checks and UI
        $mt_user_stmt = $pdo->prepare("SELECT * FROM mt5_details WHERE user_id = ?");
        $mt_user_stmt->execute([$user['id']]);
        $mt5_details = $mt_user_stmt->fetch(PDO::FETCH_ASSOC);

        $mt_user_stmt2 = $pdo->prepare("SELECT * FROM mt5_details_second WHERE user_id = ?");
        $mt_user_stmt2->execute([$user['id']]);
        $mt5_details_second = $mt_user_stmt2->fetch(PDO::FETCH_ASSOC);

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

    function getStatusBadgePHP($status) {
        $bgClass = 'bg-trophy-gold';
        $textClass = 'text-header-dark';
        $text = ucfirst(str_replace('_', ' ', $status));
        switch($status) {
            case 'pending':
                $bgClass = 'bg-gray-500';
                $textClass = 'text-white';
                break;
            case 'active':
                $bgClass = 'bg-success-green';
                $textClass = 'text-white';
                break;
            case 'completed':
                $bgClass = 'bg-blue-500';
                $textClass = 'text-white';
                break;
            default:
                break;
        }
        return "<span class=\"$bgClass $textClass text-sm font-bold px-3 py-1 rounded-full\">$text</span>";
    }

    function getPhasStatusBadgePHP($status) {
        $bgClass = 'bg-trophy-gold';
        $textClass = 'text-header-dark';
        $text = ucfirst(str_replace('_', ' ', $status));
        switch($status) {
            case 'pending':
                $bgClass = 'bg-orange-500';
                $textClass = 'text-white';
                break;
            case 'under_review':
                $bgClass = 'bg-yellow-500';
                $textClass = 'text-white';
                break;
            case 'pass':
                $bgClass = 'bg-success-green';
                $textClass = 'text-white';
                break;
            case 'running':
                $bgClass = 'bg-blue-500';
                $textClass = 'text-white';
                break;
            case 'fail':
                $bgClass = 'bg-fail-red';
                $textClass = 'text-white';
                break;
            case 'updated':
                $bgClass = 'bg-gray-500';
                $textClass = 'text-white';
                break;
            default:
                break;
        }
        return "<span class=\"$bgClass $textClass text-sm font-bold px-3 py-1 rounded-full\">$text</span>";
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>

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
            navigator.clipboard.writeText(text).then(() => {
                const type = buttonId.includes('login') ? 'Login' : 'Password';

                Swal.fire({
                    icon: 'success',
                    title: `${type} copied!`,
                    text: `${type} has been copied to your clipboard.`,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true
                });

            }).catch(err => {
                console.error('Copy failed:', err);

                Swal.fire({
                    icon: 'error',
                    title: 'Copy failed',
                    text: 'Unable to copy to clipboard.',
                });
            });
        }

        function downloadAttachment(tableName, rowId) {
            const url = `download_attachment.php?table=${encodeURIComponent(tableName)}&id=${rowId}`;
            window.location.href = url;
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
                
                <!-- Credit Notification -->
                <?php if ($user && isset($user['user_credit']) && $user['user_credit'] >= 1): ?>
                <div class="card">
                    <div class="bg-trophy-gold text-white p-4 rounded-xl shadow-lg mb-4 border-l-4 border-trophy-gold">
                        <div class="flex items-center justify-center">
                            <i class="fas fa-star text-trophy-gold mr-2"></i>
                            <span class="text-lg font-semibold">Congratulations!  You have <?php echo $user['user_credit']; ?> Credit<?php echo $user['user_credit'] > 1 ? 's' : ''; ?>. <br /> Go Ahead and Start your Trading!</span>
                            <i class="fas fa-star text-trophy-gold ml-2"></i>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                <!-- <div class="card">
                    <h3 class="text-xl font-bold text-primary-purple mb-4 flex items-center">
                        <i class="fas fa-link mr-2"></i> Your Referral Link
                    </h3>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <input type="text" readonly
                            value="<?php echo htmlspecialchars($referralLink); ?>"
                            class="w-full bg-gray-100 text-sm text-gray-700 font-mono p-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary-purple"
                            onclick="this.select(); document.execCommand('copy'); alert('Referral link copied to clipboard!');"
                        >
                        <p class="text-xs text-gray-500 mt-2">
                            Click the box to copy your referral link to clipboard.
                        </p>
                    </div>
                </div> -->
                <!-- New Challenge Button -->
                <div class="card">
                    <div class="max-w-4xl mx-auto text-center">
                        <button id="new-challenge-btn" data-user-credit="<?php echo (int)($user['user_credit'] ?? 0); ?>" onclick="createChallenge()" class="bg-trophy-gold p-4 rounded-xl shadow-lg border-b-4 border-yellow-700 cursor-pointer">
                            <p class="font-bold text-lg mb-1">New Challenge</p>
                        </button>
                    </div>
                </div>
                <!-- Buy Now Section (Checkout CTA) -->
                <div class="card mt-4">
                    <div class="max-w-4xl mx-auto text-center">
                        <h4 class="text-lg font-bold text-primary-purple mb-2">Buy Your Funded Account Test</h4>
                        <p class="text-sm text-gray-500 mb-4">Try the paid route anytime — discounted for referrals.</p>
                        <button onclick="window.location.href='checkout.php'" class="bg-trophy-gold p-4 rounded-xl shadow-lg border-b-4 border-yellow-700 cursor-pointer">
                            <p class="font-bold text-lg mb-1">Buy Now - 38% Off</p>
                            <p class="text-sm"><del>Normally $59</del>, now only $<?php echo $checkoutPrice; ?> for First Comers</p>
                        </button>
                    </div>
                </div>

                <!-- Offer panel - show if any challenge has a failed phase -->
                <?php
                    $hasAnyFail = false;
                    if (!empty($challengeData)) {
                        foreach ($challengeData as $cd) {
                            if (($cd['phase1'] && isset($cd['phase1']['status']) && $cd['phase1']['status'] === 'fail') ||
                                ($cd['phase2'] && isset($cd['phase2']['status']) && $cd['phase2']['status'] === 'fail')) {
                                $hasAnyFail = true; break;
                            }
                        }
                    }
                    if ($hasAnyFail):
                ?>
                    <div class="card mt-4 bg-primary-purple text-white">
                        <div class="p-6 text-center">
                            <h4 class="text-xl font-extrabold">Special Offer for Referrals!</h4>
                            <p class="text-sm mt-2">As a valued referrer, enjoy an exclusive discount on your funded account test. Use code <strong>REFERRAL20</strong> at checkout for 20% off!</p>
                            <div class="mt-4">
                                <button onclick="window.location.href='checkout.php'" class="bg-trophy-gold text-header-dark px-4 py-2 rounded-md font-semibold">Go to Checkout</button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Challenges list -->
                <!-- <div class="card">
                    <h3 class="text-xl font-bold text-primary-purple mb-4 flex items-center">
                        <i class="fas fa-flag-checkered mr-2"></i> Trading Accounts (Challenges)
                    </h3>
                    <?php if (!empty($challengeData)): ?>
                        <div class="grid gap-6">
                            <?php foreach ($challengeData as $cd): $ch = $cd['challenge']; $p1 = $cd['phase1']; $p2 = $cd['phase2']; ?>
                                <div id="challenge-<?php echo $ch['id']; ?>" class="p-4 border rounded-lg bg-white">
                                    <div class="flex justify-between items-center mb-2">
                                        <div>
                                            <strong><?php echo htmlspecialchars($ch['challenge_name'] ?: 'Challenge #' . $ch['challenge_number']); ?></strong>
                                            <div class="text-xs text-gray-500">Created: <?php echo $ch['created_at']; ?></div>
                                        </div>
                                        <div>
                                            <a href="choose-broker.php?challenge_id=<?php echo $ch['id']; ?>" class="px-3 py-1 bg-primary-purple text-white rounded">Phase 1</a>
                                            <a href="choose-broker-second.php?challenge_id=<?php echo $ch['id']; ?>" class="px-3 py-1 bg-primary-purple text-white rounded ml-2">Phase 2</a>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="p-3 border rounded">
                                            <h4 class="font-bold">Phase 1</h4>
                                            <?php if ($p1): ?>
                                                <p class="text-sm">Status: <?php echo htmlspecialchars($p1['status']); ?></p>
                                                <p class="text-sm">Server: <?php echo htmlspecialchars($p1['server'] ?? '--'); ?></p>
                                            <?php else: ?>
                                                <p class="text-sm text-gray-500">Not submitted</p>
                                            <?php endif; ?>
                                        </div>

                                        <div class="p-3 border rounded">
                                            <h4 class="font-bold">Phase 2</h4>
                                            <?php if ($p2): ?>
                                                <p class="text-sm">Status: <?php echo htmlspecialchars($p2['status']); ?></p>
                                                <p class="text-sm">Server: <?php echo htmlspecialchars($p2['server'] ?? '--'); ?></p>
                                            <?php else: ?>
                                                <p class="text-sm text-gray-500">Not submitted</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500">You haven't created any challenges yet — click New Challenge to get started.</p>
                    <?php endif; ?>
                </div> -->
                
            </div>

            <!-- RIGHT COLUMN (Trading Accounts) -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Trading Accounts Card -->
                <div class="card">
                    <h3 class="text-xl font-bold text-primary-purple mb-6 flex items-center">
                        <i class="fas fa-briefcase mr-2"></i> Trading Accounts & Evaluations
                    </h3>

                    <!-- Render challenges dynamically -->
                    <div id="challenge-list">
                        <?php if (!empty($challengeData)): ?>
                            <?php foreach ($challengeData as $cdIndex => $cd): $ch = $cd['challenge']; $p1 = $cd['phase1']; $p2 = $cd['phase2']; ?>
                                <div id="challenge-card-<?php echo $ch['id']; ?>" class="border border-gray-200 rounded-xl mb-6 overflow-hidden">
                                    <div class="bg-primary-purple text-white p-4 flex justify-between items-center">
                                        <h4 class="text-lg font-semibold"><?php echo htmlspecialchars($ch['challenge_name'] ?: 'Challenge #' . $ch['challenge_number']); ?></h4>
                                        <?php echo getStatusBadgePHP($ch['status']); ?>
                                    </div>

                                    <!-- Phase 2 -->
                                    <div class="p-4 border-b border-gray-100 <?php echo ($p2 && $p2['status'] === 'pass') ? 'bg-success-green/5' : ''; ?>">
                                        <div class="w-full flex justify-between items-center text-left">
                                            <a href="choose-broker-second.php?challenge_id=<?php echo $ch['id']; ?>" class="flex-1 text-left">
                                                <span class="font-bold text-header-dark flex items-center">
                                                    <?php if ($p2 && $p2['status'] === 'pass'): ?>
                                                        <i class="fas fa-check-circle text-success-green mr-3"></i>
                                                        Phase 2: <?php echo getPhasStatusBadgePHP($p2['status']); ?>
                                                    <?php else: ?>
                                                        <i class="fas fa-clock text-gray-500 mr-3"></i>
                                                        Phase 2:
                                                        <?php echo ($p2 ? getPhasStatusBadgePHP($p2['status']) : 'Not submitted'); ?>
                                                    <?php endif; ?>
                                                </span>
                                            </a>
                                            <div class="flex items-center">
                                                <span class="text-sm text-success-green font-bold mr-3"><?php echo (!empty($p2['gain']) ? htmlspecialchars($p2['gain']) : '--'); ?></span>
                                                <button onclick="toggleDetails('mt5-details-p2-<?php echo $ch['id']; ?>', 'icon-p2-<?php echo $ch['id']; ?>')" class="text-header-dark">
                                                    <i id="icon-p2-<?php echo $ch['id']; ?>" class="fas fa-chevron-down transition-transform duration-300"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <?php if ($p2): ?>
                                        <div id="mt5-details-p2-<?php echo $ch['id']; ?>" class="mt-4 pt-3 border-t border-gray-200 hidden">
                                            <p class="font-semibold text-primary-purple mb-2">MT5 Account Details (Phase 2)</p>
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 bg-gray-50 p-3 rounded-lg text-sm">
                                                <div><span class="font-medium">Server:</span> <code class="font-mono"><?php echo htmlspecialchars($p2['server'] ?? '--'); ?></code></div>
                                                <div><span class="font-medium">Login:</span> <code class="font-mono" id="login-p2-<?php echo $ch['id']; ?>"><?php echo htmlspecialchars($p2['username'] ?? '--'); ?></code></div>
                                                <div><span class="font-medium">Password:</span> <code class="font-mono" id="password-p2-<?php echo $ch['id']; ?>"><?php echo htmlspecialchars($p2['password'] ?? '--'); ?></code></div>
                                            </div>
                                            <div class="mt-3 flex justify-end space-x-2">
                                                <?php if (!empty($p2['attachment_paths'])): ?>
                                                    <?php
                                                        $attachmentPaths = json_decode($p2['attachment_paths'], true);
                                                        if (is_array($attachmentPaths) && !empty($attachmentPaths)):
                                                    ?>
                                                    <button
                                                        onclick="downloadAttachment('mt5_details_second',<?php echo $p2['id']; ?>)"
                                                        class="text-xs bg-success-green text-white px-3 py-1 rounded-full hover:bg-opacity-80 transition">
                                                        <i class="fas fa-download"></i> Download Certificate
                                                    </button>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                <button
                                                    id="btn-login-p2-<?php echo $ch['id']; ?>"
                                                    onclick="copyToClipboard(
                                                        <?php echo htmlspecialchars(json_encode($p2['username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>,
                                                        'btn-login-p2-<?php echo $ch['id']; ?>'
                                                    )"
                                                    class="text-xs bg-primary-purple text-white px-3 py-1 rounded-full hover:bg-opacity-80 transition">
                                                    <i class="fas fa-copy"></i> Copy Login
                                                </button>

                                                <button
                                                    id="btn-password-p2-<?php echo $ch['id']; ?>"
                                                    onclick="copyToClipboard(
                                                        <?php echo htmlspecialchars(json_encode($p2['password'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>,
                                                        'btn-password-p2-<?php echo $ch['id']; ?>'
                                                    )"
                                                    class="text-xs bg-primary-purple text-white px-3 py-1 rounded-full hover:bg-opacity-80 transition">
                                                    <i class="fas fa-copy"></i> Copy Password
                                                </button>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Phase 1 -->
                                    <div class="p-4 border-b border-gray-100 <?php echo ($p1 && $p1['status'] === 'pass') ? 'bg-success-green/5' : ''; ?>">
                                        <div class="w-full flex justify-between items-center text-left">
                                            <a href="choose-broker.php?challenge_id=<?php echo $ch['id']; ?>" class="flex-1 text-left">
                                                <span class="font-bold text-header-dark flex items-center">
                                                    <?php if ($p1 && $p1['status'] === 'pass'): ?>
                                                        <i class="fas fa-check-circle text-success-green mr-3"></i>
                                                        Phase 1: <?php echo getPhasStatusBadgePHP($p1['status']); ?>
                                                    <?php else: ?>
                                                        <i class="fas fa-clock text-gray-500 mr-3"></i>
                                                        Phase 1:
                                                        <?php echo ($p1 ? getPhasStatusBadgePHP($p1['status']) : 'Not submitted'); ?>
                                                    <?php endif; ?>
                                                </span>
                                            </a>
                                            <div class="flex items-center">
                                                <span class="text-sm text-success-green font-bold mr-3"><?php echo (!empty($p1['gain']) ? htmlspecialchars($p1['gain']) : '--'); ?></span>
                                                <button onclick="toggleDetails('mt5-details-p1-<?php echo $ch['id']; ?>', 'icon-p1-<?php echo $ch['id']; ?>')" class="text-header-dark">
                                                    <i id="icon-p1-<?php echo $ch['id']; ?>" class="fas fa-chevron-down transition-transform duration-300"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <?php if ($p1): ?>
                                        <div id="mt5-details-p1-<?php echo $ch['id']; ?>" class="mt-4 pt-3 border-t border-gray-200 hidden">
                                            <p class="font-semibold text-primary-purple mb-2">MT5 Account Details (Phase 1)</p>
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 bg-gray-50 p-3 rounded-lg text-sm">
                                                <div><span class="font-medium">Server:</span> <code class="font-mono"><?php echo htmlspecialchars($p1['server'] ?? '--'); ?></code></div>
                                                <div><span class="font-medium">Login:</span> <code class="font-mono" id="login-p1-<?php echo $ch['id']; ?>"><?php echo htmlspecialchars($p1['username'] ?? '--'); ?></code></div>
                                                <div><span class="font-medium">Password:</span> <code class="font-mono" id="password-p1-<?php echo $ch['id']; ?>"><?php echo htmlspecialchars($p1['password'] ?? '--'); ?></code></div>
                                            </div>
                                            <div class="mt-3 flex justify-end space-x-2">
                                                <?php if (!empty($p1['attachment_paths'])): ?>
                                                    <?php
                                                        $attachmentPaths = json_decode($p1['attachment_paths'], true);
                                                        if (is_array($attachmentPaths) && !empty($attachmentPaths)):
                                                    ?>
                                                    <button
                                                        onclick="downloadAttachment('mt5_details',<?php echo $p1['id']; ?>)"
                                                        class="text-xs bg-success-green text-white px-3 py-1 rounded-full hover:bg-opacity-80 transition">
                                                        <i class="fas fa-download"></i> Download Certificate
                                                    </button>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                <button
                                                    id="btn-login-p2-<?php echo $ch['id']; ?>"
                                                    onclick="copyToClipboard(
                                                        <?php echo htmlspecialchars(json_encode($p1['username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>,
                                                        'btn-login-p2-<?php echo $ch['id']; ?>'
                                                    )"
                                                    class="text-xs bg-primary-purple text-white px-3 py-1 rounded-full hover:bg-opacity-80 transition">
                                                    <i class="fas fa-copy"></i> Copy Login
                                                </button>

                                                <button
                                                    id="btn-password-p2-<?php echo $ch['id']; ?>"
                                                    onclick="copyToClipboard(
                                                        <?php echo htmlspecialchars(json_encode($p1['password'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>,
                                                        'btn-password-p2-<?php echo $ch['id']; ?>'
                                                    )"
                                                    class="text-xs bg-primary-purple text-white px-3 py-1 rounded-full hover:bg-opacity-80 transition">
                                                    <i class="fas fa-copy"></i> Copy Password
                                                </button>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div id="no-challenges-msg" class="border border-gray-200 rounded-xl mb-6 overflow-hidden">
                                <div class="p-6 text-center text-gray-500">You haven't created any Trading Accounts yet — click <strong>New Challenge</strong> to get started.</div>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>

                <!-- Credits & Referral Link -->
                <div class="card">
                    <h3 class="text-xl font-bold text-primary-purple mb-4 flex items-center">
                        <i class="fas fa-gift mr-2"></i> Credits & Referral Link
                    </h3>
                    <div class="grid grid-cols-1 gap-4">
                        <div class="bg-gray-50 p-4 rounded-lg text-center">
                            <p class="text-sm text-gray-500">Referral Credits</p>
                            <p class="text-3xl font-extrabold text-primary-purple mt-2"><?php echo (int)$credits; ?> / <?php echo (int)$goalCredits; ?></p>
                            <div class="mt-3">
                                <div class="w-full bg-gray-200 rounded-full h-3">
                                    <div class="h-3 rounded-full bg-success-green" style="width: <?php echo (int)$progressPercentage; ?>%;"></div>
                                </div>
                                <p class="text-xs text-gray-500 mt-2"><?php echo (int)$progressPercentage; ?>% to FREE entry</p>
                            </div>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-bold text-sm text-primary-purple mb-2">Your Unique Referral Link</h4>
                            <div class="flex items-center space-x-2">
                                <input type="text" id="referralLink" value="<?php echo htmlspecialchars($referralLink); ?>" readonly class="flex-grow bg-white p-2 rounded-md border border-gray-200 font-mono text-sm" onclick="this.select()">
                                <button onclick="copyReferralLink()" class="px-3 py-2 bg-primary-purple text-white rounded-md text-sm"><i class="fas fa-copy mr-2"></i>Copy</button>
                                <button onclick="shareReferral()" class="px-3 py-2 bg-trophy-gold text-header-dark rounded-md text-sm"><i class="fas fa-share-alt mr-2"></i>Share</button>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">Refer 5 real traders (verified) to get a free funded account test.</p>
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
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
                        <div class="text-center">
                            <canvas id="referralPieChart" width="200" height="200"></canvas>
                        </div>
                        <div class="text-center">
                            <p class="text-sm text-gray-500">Total Referral Earnings:</p>
                            <p class="text-3xl font-extrabold text-trophy-gold mt-1"><?php echo '$' . number_format($referralEarnings, 2); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Knowledge Test Result -->
                <!-- <?php if (!empty($user['knowledge_test_result'])): ?>
                <div class="card">
                    <h3 class="text-xl font-bold text-primary-purple mb-4 flex items-center">
                        <i class="fas fa-brain mr-2"></i> Knowledge Test Result
                    </h3>
                    <?php
                        $result = json_decode($user['knowledge_test_result'], true);
                        if ($result && isset($result['answers'])) {
                            $answers = $result['answers'];
                            $totalQuestions = $result['total_questions'] ?? count($answers);
                            $correctCount = 0;
                            $incorrectCount = 0;
                            
                            foreach ($answers as $answer) {
                                if ($answer === 'correct') {
                                    $correctCount++;
                                } else {
                                    $incorrectCount++;
                                }
                            }
                            
                            $completionDate = $result['completed_at'] ?? null;
                            $formattedDate = $completionDate ? date('F j, Y \a\t g:i A', strtotime($completionDate)) : 'Unknown date';
                    ?>
                    <div class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-gradient-to-br from-success-green to-green-600 text-white p-6 rounded-xl shadow-lg">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-green-100 text-sm font-medium">CORRECT ANSWERS</p>
                                        <p class="text-3xl font-bold mt-1"><?php echo $correctCount; ?></p>
                                    </div>
                                    <i class="fas fa-check-circle text-4xl opacity-80"></i>
                                </div>
                                <div class="mt-2 text-green-100 text-sm">
                                    <?php echo round(($correctCount / $totalQuestions) * 100, 1); ?>% of total
                                </div>
                            </div>
                            
                            <div class="bg-gradient-to-br from-fail-red to-red-600 text-white p-6 rounded-xl shadow-lg">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-red-100 text-sm font-medium">INCORRECT ANSWERS</p>
                                        <p class="text-3xl font-bold mt-1"><?php echo $incorrectCount; ?></p>
                                    </div>
                                    <i class="fas fa-times-circle text-4xl opacity-80"></i>
                                </div>
                                <div class="mt-2 text-red-100 text-sm">
                                    <?php echo round(($incorrectCount / $totalQuestions) * 100, 1); ?>% of total
                                </div>
                            </div>
                            
                            <div class="bg-gradient-to-br from-primary-purple to-purple-600 text-white p-6 rounded-xl shadow-lg">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-purple-100 text-sm font-medium">TOTAL QUESTIONS</p>
                                        <p class="text-3xl font-bold mt-1"><?php echo $totalQuestions; ?></p>
                                    </div>
                                    <i class="fas fa-question-circle text-4xl opacity-80"></i>
                                </div>
                                <div class="mt-2 text-purple-100 text-sm">
                                    Completed on <?php echo $formattedDate; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gray-100 rounded-full h-4 overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-success-green to-green-500" style="width: <?php echo round(($correctCount / $totalQuestions) * 100, 1); ?>%"></div>
                        </div>
                        <p class="text-center text-sm text-gray-600 font-medium">
                            Overall Score: <?php echo round(($correctCount / $totalQuestions) * 100, 1); ?>%
                        </p>
                        
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="font-bold text-primary-purple mb-3">Detailed Results</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                <?php foreach ($answers as $question => $status): ?>
                                    <div class="flex items-center justify-between p-3 bg-white rounded-lg border-l-4 <?php echo $status === 'correct' ? 'border-success-green' : 'border-fail-red'; ?>">
                                        <div>
                                            <p class="font-medium text-sm"><?php echo ucfirst(str_replace('_', ' ', $question)); ?></p>
                                            <p class="text-xs text-gray-500"><?php echo ucfirst(str_replace('_', ' ', $question)); ?></p>
                                        </div>
                                        <div class="text-2xl">
                                            <?php echo $status === 'correct' ? '<i class="fas fa-check-circle text-success-green"></i>' : '<i class="fas fa-times-circle text-fail-red"></i>'; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php } else { ?>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-sm text-gray-700">Unable to parse test results.</p>
                    </div>
                    <?php } ?>
                </div>
                <?php endif; ?> -->
            </div>
        </div>

        <!-- Referral History Table -->
        <div class="mt-8">
            <div class="card p-0 overflow-hidden">
                <h3 class="text-xl font-bold text-primary-purple p-4 flex items-center border-b border-gray-100">
                    <i class="fas fa-users mr-2"></i> Full Referral History
                </h3>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 rounded-xl overflow-hidden">
                        <thead class="bg-primary-purple">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-trophy-gold uppercase tracking-wider rounded-tl-xl">
                                    Referred Trader
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-trophy-gold uppercase tracking-wider">
                                    IsTrader
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-trophy-gold uppercase tracking-wider">
                                    IsReal
                                </th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-trophy-gold uppercase tracking-wider">
                                    IsVerified
                                </th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-trophy-gold uppercase tracking-wider">
                                    Status
                                </th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-trophy-gold uppercase tracking-wider rounded-tr-xl">
                                    Credit
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (!empty($referrals)): ?>
                                <?php foreach ($referrals as $r): ?>
                                    <?php
                                        $isVerified = ($r['email_verified'] == 1 && $r['quiz_result'] != null && $r['user_ip'] !== $user['user_ip']);
                                    ?>
                                    <tr class="hover:bg-gray-50">
                                        <!-- Name -->
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($r['name'] ?: ($r['email'] ?? '—')); ?>
                                        </td>

                                        <!-- Trader / Non Trader -->
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php if (!empty($r['quiz_result'])): ?>
                                                <span style="background:#d1fae5; color:#065f46; padding:3px 8px; border-radius:6px; font-weight:600;">
                                                    Trader
                                                </span>
                                            <?php elseif(empty($r['quiz_result']) && $r['status'] === 'inactive'): ?>
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
                                            <?php if ($r['user_ip'] === $user['user_ip']): ?>
                                                <span style="background:#fee2e2; color:#991b1b; padding:3px 8px; border-radius:6px; font-weight:600;">
                                                    Fake
                                                </span>
                                            <?php else: ?>
                                                <span style="background:#d1fae5; color:#065f46; padding:3px 8px; border-radius:6px; font-weight:600;">
                                                    Real
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Verification Completed / Pending -->
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

                                        <!-- Status Completed / Pending -->
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                            <?php if ($r['status'] === 'active' && !empty($r['quiz_result'])): ?>
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
                                            <?php if ($isVerified): ?>
                                                <span class="text-lg text-green-600 font-bold">✓</span>
                                            <?php else: ?>
                                                <span class="text-lg text-yellow-600 font-bold">⏳</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr class="table-row">
                                    <td colspan="6" class="px-6 py-4 text-center">No referrals yet.</td>
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
        // Mobile menu toggle
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.getElementById('mobileMenu').classList.toggle('hidden');
        });

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
            }).catch(() => {
                // Fallback using older API
                const el = document.createElement('textarea'); el.value = link; document.body.appendChild(el); el.select(); try { document.execCommand('copy'); alert('Referral link copied to clipboard'); } catch (e) { alert('Please copy the link manually'); } document.body.removeChild(el);
            });
        }

        // Use native sharing when available
        function shareReferral() {
            const link = document.getElementById('referralLink').value;
            if (navigator.share) {
                navigator.share({ title: 'Join Funding4x', text: 'Join Funding4x using my referral link!', url: link })
                    .catch(err => console.error('Share failed:', err));
            } else {
                copyReferralLink();
                alert('Referral link copied to clipboard. Share it with your friends!');
            }
        }
    </script>
    <script>
        async function createChallenge(){
            const btn = document.getElementById('new-challenge-btn');
            if (!btn) return;

            // Client-side quick check: if user_credit < 1, send them to checkout immediately
            const userCredit = parseInt(btn.getAttribute('data-user-credit') || '0', 10);
            if (userCredit < 1) {
                
                window.location.href = 'checkout.php';
                return;
            }

            btn.disabled = true;
            const original = btn.innerHTML;
            btn.innerHTML = '<p class="font-bold text-lg mb-1">Creating...</p>';
            try {
                const res = await fetch('create_challenge.php', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } });

                // If server responds with a non-200 status and a JSON redirect, handle it
                if (res.status === 403) {
                    try {
                        const errData = await res.json();
                        if (errData.redirect) {
                            alert(errData.message || 'Insufficient credits');
                            window.location.href = errData.redirect;
                            return;
                        }
                    } catch (e) {
                        // Fall through
                    }
                }

                const data = await res.json();
                if (data.success && data.challenge) {
                    insertChallengeCard(data.challenge, data.phase1 || null, data.phase2 || null);
                    const el = document.getElementById('challenge-card-' + data.challenge.id);
                    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    btn.disabled = false;
                    btn.innerHTML = original;
                } else if (data && data.redirect) {
                    alert(data.message || 'Redirecting to Checkout');
                    window.location.href = data.redirect;
                } else {
                    alert('Failed to create challenge: ' + (data.message || 'Unknown'));
                    btn.disabled = false;
                    btn.innerHTML = original;
                }
            } catch (err) {
                console.error(err);
                alert('Network error. Please try again.');
                btn.disabled = false;
                btn.innerHTML = original;
            }
        }

        function escapeHtml(str) {
            if (str === null || typeof str === 'undefined') return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function getStatusBadge(status) {
            let bgClass = 'bg-trophy-gold';
            let textClass = 'text-header-dark';
            let text = status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' ');
            switch(status) {
                case 'pending':
                    bgClass = 'bg-gray-500';
                    textClass = 'text-white';
                    break;
                case 'under_review':
                    bgClass = 'bg-yellow-500';
                    textClass = 'text-white';
                    break;
                case 'pass':
                    bgClass = 'bg-success-green';
                    textClass = 'text-white';
                    break;
                case 'running':
                    bgClass = 'bg-blue-500';
                    textClass = 'text-white';
                    break;
                case 'fail':
                    bgClass = 'bg-fail-red';
                    textClass = 'text-white';
                    break;
                case 'updated':
                    bgClass = 'bg-purple-500';
                    textClass = 'text-white';
                    break;
                default:
                    break;
            }
            return `<span class="${bgClass} ${textClass} text-sm font-bold px-3 py-1 rounded-full">${text}</span>`;
        }

        function insertChallengeCard(ch, p1, p2) {
            const list = document.getElementById('challenge-list');
            if (!list) return;

            const noMsg = document.getElementById('no-challenges-msg');
            if (noMsg) noMsg.remove();

            const id = ch.id;
            console.log(ch.status);
            const name = escapeHtml(ch.challenge_name || ('Challenge #' + ch.challenge_number));
            const p1server = escapeHtml((p1 && p1.server) ? p1.server : '--');
            const p1user = escapeHtml((p1 && p1.username) ? p1.username : '--');
            const p1pass = escapeHtml((p1 && p1.password) ? p1.password : '--');
            const p2server = escapeHtml((p2 && p2.server) ? p2.server : '--');
            const p2user = escapeHtml((p2 && p2.username) ? p2.username : '--');
            const p2pass = escapeHtml((p2 && p2.password) ? p2.password : '--');
            const p1Submitted = p1 ? true : false;
            const p2Submitted = p2 ? true : false;

            const card = document.createElement('div');
            card.id = 'challenge-card-' + id;
            card.className = 'border border-gray-200 rounded-xl mb-6 overflow-hidden';
            card.innerHTML = `
                <div class="bg-primary-purple text-white p-4 flex justify-between items-center">
                    <h4 class="text-lg font-semibold">${name}</h4>
                    ${getStatusBadge(ch.status || 'pending')}
                </div>

                <div class="p-4 border-b border-gray-100">
                    <div class="w-full flex justify-between items-center text-left">
                        <a href="choose-broker-second.php?challenge_id=${id}" class="flex-1 text-left">
                            <span class="font-bold text-header-dark flex items-center">
                                <i class="fas fa-clock text-gray-500 mr-3"></i> ${p2Submitted ? 'Phase 2: Submitted' : 'Phase 2: Not submitted'}
                            </span>
                        </a>
                        <div class="flex items-center">
                            <span class="text-sm text-success-green font-bold mr-3">${p2Submitted ? 'Submitted' : '--'}</span>
                            <button onclick="toggleDetails('mt5-details-p2-${id}', 'icon-p2-${id}')" class="text-header-dark">
                                <i id="icon-p2-${id}" class="fas fa-chevron-down transition-transform duration-300"></i>
                            </button>
                        </div>
                    </div>

                    <div id="mt5-details-p2-${id}" class="mt-4 pt-3 border-t border-gray-200 hidden">
                        <p class="font-semibold text-primary-purple mb-2">MT5 Account Details (Phase 2)</p>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 bg-gray-50 p-3 rounded-lg text-sm">
                            <div><span class="font-medium">Server:</span> <code class="font-mono">${p2server}</code></div>
                            <div><span class="font-medium">Login:</span> <code class="font-mono" id="login-p2-${id}">${p2user}</code></div>
                            <div><span class="font-medium">Password:</span> <code class="font-mono" id="password-p2-${id}">${p2pass}</code></div>
                        </div>
                        <div class="mt-3 flex justify-end space-x-2">
                            ${p2Submitted ? (`<button id="btn-login-p2-${id}" onclick="copyToClipboard(${JSON.stringify(p2user)}, 'btn-login-p2-${id}')" class="text-xs bg-primary-purple text-white px-3 py-1 rounded-full hover:bg-opacity-80 transition"><i class="fas fa-copy"></i> Copy Login</button>
                            <button id="btn-password-p2-${id}" onclick="copyToClipboard(${JSON.stringify(p2pass)}, 'btn-password-p2-${id}')" class="text-xs bg-primary-purple text-white px-3 py-1 rounded-full hover:bg-opacity-80 transition"><i class="fas fa-copy"></i> Copy Password</button>`) : ''}
                        </div>
                    </div>
                </div>

                <div class="p-4 border-b border-gray-100">
                    <div class="w-full flex justify-between items-center text-left">
                        <a href="choose-broker.php?challenge_id=${id}" class="flex-1 text-left">
                            <span class="font-bold text-header-dark flex items-center">
                                <i class="fas fa-clock text-gray-500 mr-3"></i> ${p1Submitted ? 'Phase 1: Submitted' : 'Phase 1: Not submitted'}
                            </span>
                        </a>
                        <div class="flex items-center">
                            <span class="text-sm text-success-green font-bold mr-3">${p1Submitted ? 'Submitted' : '--'}</span>
                            <button onclick="toggleDetails('mt5-details-p1-${id}', 'icon-p1-${id}')" class="text-header-dark">
                                <i id="icon-p1-${id}" class="fas fa-chevron-down transition-transform duration-300"></i>
                            </button>
                        </div>
                    </div>

                    <div id="mt5-details-p1-${id}" class="mt-4 pt-3 border-t border-gray-200 hidden">
                        <p class="font-semibold text-primary-purple mb-2">MT5 Account Details (Phase 1)</p>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 bg-gray-50 p-3 rounded-lg text-sm">
                            <div><span class="font-medium">Server:</span> <code class="font-mono">${p1server}</code></div>
                            <div><span class="font-medium">Login:</span> <code class="font-mono" id="login-p1-${id}">${p1user}</code></div>
                            <div><span class="font-medium">Password:</span> <code class="font-mono" id="password-p1-${id}">${p1pass}</code></div>
                        </div>
                        <div class="mt-3 flex justify-end space-x-2">
                            ${p1Submitted ? (`<button id="btn-login-p1-${id}" onclick="copyToClipboard(${JSON.stringify(p1user)}, 'btn-login-p1-${id}')" class="text-xs bg-primary-purple text-white px-3 py-1 rounded-full hover:bg-opacity-80 transition"><i class="fas fa-copy"></i> Copy Login</button>
                            <button id="btn-password-p1-${id}" onclick="copyToClipboard(${JSON.stringify(p1pass)}, 'btn-password-p1-${id}')" class="text-xs bg-primary-purple text-white px-3 py-1 rounded-full hover:bg-opacity-80 transition"><i class="fas fa-copy"></i> Copy Password</button>`) : ''}
                        </div>
                    </div>
                </div>
            `;

            // Insert at top
            list.insertBefore(card, list.firstChild);
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