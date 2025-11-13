<?php
// referral_dashboard.php
session_start();

// Database config
$host = 'localhost';
$dbname = 'funding4x';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed");
}

// Get user referral code from URL parameter or session
$referralCode = $_GET['user'] ?? $_SESSION['user_referral_code'] ?? '';
$user = null;
$referrals = [];

if (!empty($referralCode)) {
    // Get current user data
    $stmt = $pdo->prepare("SELECT * FROM waitlist_users WHERE referral_code = ?");
    $stmt->execute([$referralCode]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Store in session for future visits
        $_SESSION['user_referral_code'] = $referralCode;
        
        // Get list of referrals (users who were referred by this user)
        $stmt = $pdo->prepare("
            SELECT name, country, created_at 
            FROM waitlist_users 
            WHERE parent_user_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$user['id']]);
        $referrals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// If no user found, redirect to home
if (!$user) {
    header('Location: index.php');
    exit;
}

// Generate referral link
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$referralLink = $protocol . '://' . $host . '/index.php?ref=' . urlencode($referralCode);

// Calculate progress
$credits = $user['credits'];
$goalCredits = 5;
$progressPercentage = min(($credits / $goalCredits) * 100, 100);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Referral Dashboard - Get Funded for Free</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Load Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
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
    </style>
</head>

<body>

    <!-- Header & Navigation -->
    <header class="header-bg text-white shadow-2xl sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
            <h1 class="text-2xl font-extrabold tracking-tight text-trophy-gold">REFERRAL DASHBOARD</h1>
            <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-300">Welcome, <?php echo htmlspecialchars($user['name']); ?></span>
                <a href="index.php" class="text-sm text-white hover:text-trophy-gold transition duration-300">
                    ← Back to Main Page
                </a>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="py-16 sm:py-24 bg-primary-purple text-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <span class="text-trophy-gold text-sm font-semibold uppercase tracking-widest block mb-4">The Ultimate Partner Program</span>
            <h2 class="text-4xl sm:text-6xl font-extrabold tracking-tighter leading-tight mb-4">
                5 Referrals = <span class="text-trophy-gold">$5,000</span> Funded Account
            </h2>
            <p class="mt-4 text-xl text-gray-200">
                Share your unique link with other passionate traders. For every successful referral who joins the competition, you earn **1 Credit**. Collect five credits to bypass the competition and get instant funding!
            </p>
        </div>
    </section>

    <!-- Referral Link, Tracker, and Status Table Section -->
    <section class="py-16 bg-bg-light">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Referral Link and Credit Tracker Box -->
            <div class="bg-white p-8 sm:p-12 rounded-2xl shadow-xl border-t-4 border-trophy-gold mb-8">
                
                <!-- Referral Link -->
                <h3 class="text-2xl font-bold text-primary-purple mb-4">Your Unique Referral Link</h3>
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

                <!-- Credit Tracker -->
                <div class="mt-10">
                    <h3 class="text-2xl font-bold text-primary-purple mb-4">
                        Your Credit Progress: <span id="credit-count" class="text-fomo-red"><?php echo $credits; ?> / <?php echo $goalCredits; ?></span>
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
            </div>

            <!-- Referral Status Table Box -->
            <div class="bg-white p-8 sm:p-10 rounded-2xl shadow-xl border-t-4 border-primary-purple">
                <h3 class="text-2xl font-bold text-primary-purple mb-6">Your Referrals (<?php echo count($referrals); ?>)</h3>
                
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
                                        Credit
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($referrals as $index => $referral): ?>
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
                                            <span class="text-lg text-trophy-gold font-bold">✓</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <p class="mt-6 text-sm text-gray-600 italic border-t pt-4">
                    **Status Definition:** Each successful referral who registers using your link earns you **1 Credit**. Once you reach 5 credits, you'll receive a $5,000 Funded Account instantly!
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
                    <h3 class="text-xl font-bold text-gray-800 mb-3">They Join the Cup</h3>
                    <p class="text-gray-600">
                        When a new trader registers for the competition using your link, you instantly earn **1 Credit**.
                    </p>
                </div>

                <!-- Step 3 -->
                <div class="card-glow p-8 bg-white rounded-xl shadow-xl border-b-4 border-fomo-red">
                    <div class="text-4xl font-extrabold text-fomo-red mb-3">3</div>
                    <h3 class="text-xl font-bold text-gray-800 mb-3">Claim Your Prize!</h3>
                    <p class="text-gray-600">
                        Reach **5 Credits** and we'll grant you a $5,000 Funded Account instantly, no trading competition required!
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
                        A successful referral is a user who clicks your unique link and completes the registration process for the Global Forex Trader Cup.
                    </p>
                </div>
                <!-- FAQ Item 2 -->
                <div class="bg-header-dark p-6 rounded-xl shadow-lg">
                    <h4 class="text-xl font-bold text-trophy-gold mb-2">Do my credits expire?</h4>
                    <p class="text-gray-300">
                        No, your earned credits are yours to keep until you reach the goal of 5.
                    </p>
                </div>
                <!-- FAQ Item 3 -->
                <div class="bg-header-dark p-6 rounded-xl shadow-lg">
                    <h4 class="text-xl font-bold text-trophy-gold mb-2">What are the rules for the $5,000 funded account?</h4>
                    <p class="text-gray-300">
                        The account granted through the referral program follows the same fair rules as the competition winners: 50% profit split and adherence to daily/overall risk limits.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-header-dark text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 text-center">
            <p class="text-sm">&copy; 2024 Global Trader Cup. All rights reserved. | Powered by Referrals.</p>
        </div>
    </footer>

    <!-- JavaScript for Clipboard Functionality -->
    <script>
        function nativeShare(elementId) {
            const linkInput = document.getElementById(elementId);
            const referralURL = linkInput.value; // Get actual URL string
            console.log(referralURL);

            const message = "🚀 Want a $5,000 funded trading account?\n\n" +
                "Join the Global Trader Cup and get instant credits when your friends sign up using your referral link.\n\n" +
                "Use my link to get started and claim your funded account:\n\n💸 " + referralURL + "\n\n" +
                "Share now and start earning credits!";

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

        // Update progress bar on page load
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
    </script>
</body>
</html>
