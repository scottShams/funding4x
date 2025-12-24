<?php
    session_start();
    // Include database connection
    require_once 'database.php';
    $allowUpdate = $userId = $email = null;
    // Check if update is allowed via REF=mt5ts parameter (admin link for Test 2). Server-side permission will be set once we know the challenge_id.
    $allowUpdate = isset($_GET['REF']) && $_GET['REF'] === 'mt5ts';

    // Get database connection
    $pdo = getPDO();
    $email = $_SESSION['user_email'] ?? '';
    $userId = $_SESSION['user_id'] ?? '';
    if (empty($email) && empty($userId)) {
        // If user arrived with a GET (e.g. ?REF=...) store the full request URI
        // so we can redirect them back here after login. Keep it for 1 hour.
        if (!empty($_SERVER['REQUEST_URI'])) {
            $requestUri = $_SERVER['REQUEST_URI'];
            if (strpos($requestUri, '?') !== false) {
                setcookie('intended_url', $requestUri, time() + 3600, '/');
            } else {
                // Also set cookie if REF param exists explicitly in $_GET even without full query
                if (!empty($_GET['REF'])) {
                    setcookie('intended_url', $requestUri, time() + 3600, '/');
                }
            }
        }
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Please Login</title>
            <script src="https://cdn.tailwindcss.com"></script>
            <script>
                tailwind.config = {
                    theme: {
                        extend: {
                            colors: {
                                'primary-purple': '#4f009d',
                                'header-dark': '#240046',
                            }
                        }
                    }
                }
            </script>
        </head>
        <body class="min-h-screen flex items-center justify-center bg-gray-100">
            <div class="fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm flex items-center justify-center z-50">
                <div class="bg-white p-10 rounded-3xl shadow-2xl max-w-lg mx-4 border-t-4 border-primary-purple">
                    <div class="text-center">
                        <h2 class="text-3xl font-extrabold text-primary-purple mb-4">Please Login First</h2>
                        <p class="text-gray-700 mb-8 leading-relaxed text-lg">
                            You need to be logged in to access this page.
                        </p>
                        <div class="flex justify-center space-x-4">
                            <a href="login.php?intended=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="px-8 py-4 bg-primary-purple text-white font-bold rounded-xl shadow-lg hover:bg-header-dark transition-all duration-300 transform hover:scale-105">
                                Login Now
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
    $stmt = $pdo->prepare("SELECT * FROM waitlist_users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if MT5 details are already submitted for this challenge
    $challengeId = isset($_GET['challenge_id']) ? (int)$_GET['challenge_id'] : null;

    // If admin REF link and challenge_id present, set short-lived server-side permission for Test 2 updates
    if ($allowUpdate && $challengeId) {
        $_SESSION['allow_mt5_update_second'] = $challengeId;
        $_SESSION['allow_mt5_update_second_expires'] = time() + 3600; // valid for 1 hour
    }

    if ($challengeId) {
        $stmt = $pdo->prepare("SELECT * FROM mt5_details_second WHERE user_id = ? AND challenge_id = ?");
        $stmt->execute([$user['id'], $challengeId]);
        $mt5DetailsSecond = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM mt5_details_second WHERE user_id = ? ORDER BY submitted_at DESC LIMIT 1");
        $stmt->execute([$user['id']]);
        $mt5DetailsSecond = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    $hasMT5DetailsSecond = $mt5DetailsSecond ? true : false;
    // Only enable update mode if the admin REF link was used and a challenge-specific record exists
    $isUpdateModeSecond = ($allowUpdate && $hasMT5DetailsSecond);

    // If update mode is enabled but the status is not pending, show blocked notice on page load
    $blockedUpdateSecond = $isUpdateModeSecond && (!empty($mt5DetailsSecond['status']) && $mt5DetailsSecond['status'] !== 'pending');

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Funding4x Choose a Broker – Forex Prop Firm</title>
    <meta name="description" content="Select the best broker to trade with your Funding4x funded account. Compare brokers, see spreads, and start trading with company capital.">
    <meta name="keywords" content="forex broker, best broker for prop firm, funded account broker, choose broker, forex trading with company capital">


	<!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon-16x16.png">
    <link rel="manifest" href="assets/site.webmanifest">
    <!-- Load Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
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
                        'secondary-purple': '#7b2cbf', // Lighter accent purple
                        'trophy-gold': '#b49852', // Classic, muted Gold
                        'header-dark': '#240046', // Very Dark Purple/Black
                        'bg-light': '#f3f4f6',
                        'cta-hover': '#9d7c49', // Darker gold for hover state
                        'card-white': '#ffffff',
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
            color: #240046;
        }
        /* Custom styles */
        .header-bg {
            background-color: #240046;
        }
        .broker-card {
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid #e5e7eb;
        }
        .broker-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(79, 0, 157, 0.15); /* Purple shadow */
            border-color: #b49852; /* Gold border on hover */
        }
        .step-circle {
            flex-shrink: 0;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            background-color: #4f009d; /* primary-purple */
            color: white;
            font-weight: bold;
            margin-right: 16px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
    </style>
</head>

<body class="min-h-screen flex flex-col">

    <?php include 'header.php'; ?>

    <!-- Main Content -->
    <main class="flex-grow p-4 sm:p-8 bg-bg-light relative">

        <!-- Decorative Background Elements -->
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden opacity-5 pointer-events-none">
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-primary-purple rounded-full blur-3xl"></div>
            <div class="absolute top-1/2 -left-24 w-72 h-72 bg-trophy-gold rounded-full blur-3xl"></div>
        </div>

        <div class="w-full max-w-6xl mx-auto">

            <!-- INSTRUCTIONS BLOCK -->
            <div class="bg-card-white p-6 md:p-10 rounded-2xl shadow-xl mb-12 border-t-4 border-trophy-gold">
                <div class="text-center mb-10">
                    <h2 class="text-3xl font-extrabold text-primary-purple mb-3">Trading Test 2 Setup Checklist</h2>
                    <p class="text-gray-600">Follow these 4 steps to Setup your Trading and Connect with us for monitoring.</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-12">
                    
                    <!-- Steps 1 & 2 -->
                    <div class="space-y-8">
                        <div class="flex items-start">
                            <div class="step-circle">1</div>
                            <div>
                                <h3 class="font-bold text-xl text-header-dark mb-2">Use the Same Account Again</h3>
                                <p class="text-gray-600 leading-relaxed">
                                    Login to your Broker Dashboard again and do the following Steps. The same broker which you Registered for in Trading Test 1
                                    
                                </p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="step-circle">2</div>
                            <div>
                                <h3 class="font-bold text-xl text-header-dark mb-2">Create a NEW Demo Account</h3>
                                <div class="text-gray-600 leading-relaxed">
                                    In your broker portal, Create a New <strong>Demo Account</strong> with:
                                    <ul class="mt-2 space-y-1 text-sm bg-gray-50 p-3 rounded-lg border border-gray-200">
                                        <li class="flex items-center"><span class="w-2 h-2 bg-trophy-gold rounded-full mr-2"></span>Balance: <strong>$5,000</strong></li>
                                        <li class="flex items-center"><span class="w-2 h-2 bg-trophy-gold rounded-full mr-2"></span>Leverage: <strong>1:100</strong></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Steps 3 & 4 -->
                    <div class="space-y-8">
                        <div class="flex items-start">
                            <div class="step-circle">3</div>
                            <div>
                                <h3 class="font-bold text-xl text-header-dark mb-2">Prepare MT5 Platform</h3>
                                <p class="text-gray-600 leading-relaxed">
                                    Download and install MT5. Log in with your new Demo credentials. 
                                    <br>
                                    <span class="font-bold text-primary-purple">Do not trade yet!</span> Wait for our confirmation email before you start Trading.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div class="step-circle">4</div>
                            <div>
                                <h3 class="font-bold text-xl text-header-dark mb-2">Submit Your Details</h3>
                                <p class="text-gray-600 leading-relaxed">
                                    Enter your Demo Account details (Login, Password, Server) in the form on the right so we can verify and connect our monitoring software.
                                </p>
                                <br />
                                
                                
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            
        
            
            <!-- CONTENT GRID -->
            <div class="grid grid-cols-1 lg:grid-cols-1 gap-8 items-start">
                
                

                <!-- RIGHT COLUMN: MT5 Login Form (Step 4) -->
                <div class="bg-white p-8 rounded-2xl shadow-2xl border-2 border-primary-purple h-fit lg:sticky lg:top-24">
                    <h2 class="text-2xl font-bold text-primary-purple mb-2">Step 4: <?php echo $isUpdateModeSecond ? 'Update Account Details' : 'Account Submission'; ?></h2>
                    <p class="text-sm text-gray-600 mb-6">
                        Enter the **Demo Account** details you created in Step 2. We will set up the trade monitoring for the competition.
                    </p>

                    <form id="mt5-form" onsubmit="handleFormSubmit(event)">
                        <input type="hidden" name="challenge_id" value="<?php echo isset($challengeId) ? (int)$challengeId : ''; ?>">
                        
                        <div class="mb-4">
                            <label for="username" class="block text-sm font-semibold text-gray-700 mb-1">MT5 Login ID</label>
                            <input type="text" id="username" name="username" required 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-primary-purple focus:border-primary-purple transition duration-150 ease-in-out bg-gray-50"
                                   placeholder="e.g. 50123456"
                                   value="<?php echo htmlspecialchars($mt5DetailsSecond['username'] ?? ''); ?>">
                        </div>

                        <div class="mb-4">
                            <label for="password" class="block text-sm font-semibold text-gray-700 mb-1">Trader Password</label>
                            <input type="password" id="password" name="password" required 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-primary-purple focus:border-primary-purple transition duration-150 ease-in-out bg-gray-50"
                                   placeholder="********" value="<?php echo htmlspecialchars($mt5DetailsSecond['password'] ?? ''); ?>">
                        </div>

                        <div class="mb-6">
                            <label for="server" class="block text-sm font-semibold text-gray-700 mb-1">Broker Server Name</label>
                            <input type="text" id="server" name="server" required 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-primary-purple focus:border-primary-purple transition duration-150 ease-in-out bg-gray-50"
                                   placeholder="e.g., Exness-Trial9"
                                   value="<?php echo htmlspecialchars($mt5DetailsSecond['server'] ?? ''); ?>">
                        </div>

                        <button type="submit" 
                                class="w-full px-4 py-4 bg-primary-purple text-white font-bold rounded-lg shadow-lg hover:bg-header-dark transition duration-300 uppercase tracking-wide">
                            Submit for Monitoring
                        </button>
                    </form>

                    <!-- Success/Error Message Box -->
                    <div id="message-box" class="mt-4 p-4 rounded-lg text-sm text-center hidden font-medium"></div>

                </div>
                
            </div>

            <!-- Footer -->
            <footer class="mt-16 border-t border-gray-200 pt-8 text-center">
                <p class="text-sm text-gray-500">
                    <span class="font-bold text-primary-purple">Compliance Note:</span> Please ensure you use the affiliate buttons above. Accounts not linked to our partner ID cannot be monitored for the competition.
                </p>
                <p class="text-xs text-gray-400 mt-2">
                    Trading financial markets involves risk. &copy; 2024 Global Forex Trader Cup.
                </p>
            </footer>

        </div>
    </main>

    <!-- Success Modal -->
    <div id="success-modal" class="fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm flex items-center justify-center z-50 hidden animate-fade-in">
        <div class="bg-white p-10 rounded-3xl shadow-2xl max-w-lg mx-4 border-t-4 border-trophy-gold transform scale-95 animate-modal-appear">
            <div class="text-center">
                <div class="mx-auto w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>

                <h2 class="text-3xl font-extrabold text-primary-purple mb-4"><?php echo $isUpdateModeSecond ? 'Details Updated' : 'Thank You'; ?> <?php echo htmlspecialchars($user['name']); ?>!</h2>
                <p class="text-gray-700 mb-8 leading-relaxed text-lg">
                    <?php if ($isUpdateModeSecond): ?>
                        Your MT5 details have been updated successfully. Our team will review the changes and update you by email within a few hours.
                    <?php else: ?>
                        We got your details. Please wait for us to setup your monitoring. We will update you by Email within few hours. We are excited to see you in action!
                    <?php endif; ?>
                </p>

                <div class="flex justify-center space-x-4">
                    <a href="referral_dashboard.php" class="px-8 py-4 bg-primary-purple text-white font-bold rounded-xl shadow-lg hover:bg-header-dark transition-all duration-300 transform hover:scale-105">
                        Go to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Already Submitted Modal -->
    <div id="already-submitted-modal" class="fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm flex items-center justify-center z-50 <?php echo ($hasMT5DetailsSecond && !$isUpdateModeSecond) ? '' : 'hidden'; ?> animate-fade-in">
        <div class="bg-white p-10 rounded-3xl shadow-2xl max-w-lg mx-4 border-t-4 border-primary-purple transform scale-95 animate-modal-appear">
            <div class="text-center">
                <div class="mx-auto w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 20h.01"></path>
                    </svg>
                </div>

                <h2 class="text-3xl font-extrabold text-primary-purple mb-4">Details Already Submitted</h2>
                <p class="text-gray-700 mb-8 leading-relaxed text-lg">
                    Hello <?php echo htmlspecialchars($user['name']); ?>, you have already submitted your Trading Test 2 details for monitoring.
                    Our team is currently setting up your account. Please check back later or contact support if you need assistance.
                </p>

                <div class="flex justify-center space-x-4">
                    <a href="referral_dashboard.php" class="px-8 py-4 bg-primary-purple text-white font-bold rounded-xl shadow-lg hover:bg-header-dark transition-all duration-300 transform hover:scale-105">
                        Go to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Blocked Update Modal (pass/fail) -->
    <div id="blocked-update-modal-second" class="fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm flex items-center justify-center z-50 <?php echo $blockedUpdateSecond ? '' : 'hidden'; ?> animate-fade-in">
        <div class="bg-white p-8 rounded-2xl shadow-2xl max-w-lg mx-4 border-t-4 border-primary-purple transform scale-95 animate-modal-appear">
            <div class="text-center">
                <h3 class="text-2xl font-semibold text-primary-purple mb-4">Update Not Allowed</h3>
                <p id="blocked-update-text-second" class="text-gray-700 mb-6"><?php echo $blockedUpdateSecond ? "Sorry! You cannot update this test because its status is '{$mt5DetailsSecond['status']}'." : 'Your test status is final and cannot be changed.'; ?></p>
                <div class="flex justify-center">
                    <button onclick="document.getElementById('blocked-update-modal-second').classList.add('hidden')" class="px-6 py-3 bg-primary-purple text-white rounded-lg font-bold">OK</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        async function handleFormSubmit(event) {
            event.preventDefault(); 
            const form = document.getElementById('mt5-form');
            const username = form.username.value;
            const password = form.password ? form.password.value : '';
            const server = form.server.value;
            const messageBox = document.getElementById('message-box');

            // Build payload
            const challengeId = '<?php echo isset($challengeId) ? (int)$challengeId : ''; ?>';
            const payload = {
                mt5_details: { username, password, server },
                allow_update: <?php echo $isUpdateModeSecond ? 'true' : 'false'; ?>,
                challenge_id: challengeId
            };

            try {
                const res = await fetch('submit_mt5_phase2.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                    body: JSON.stringify(payload)
                });

                const data = await res.json().catch(() => ({}));

                if (res.ok && data.success) {
                    // Show success modal
                    document.getElementById('success-modal').classList.remove('hidden');
                    form.reset();

                    // Update dashboard card if present
                    const id = challengeId;
                    const loginEl = document.getElementById('login-p2-' + id);
                    if (loginEl && data.mt5_second) loginEl.textContent = data.mt5_second.username;
                } else {
                    // Handle specific errors
                    if (res.status === 403) {
                        const msg = data.message || data.error || 'Update not allowed';
                        const modalMessageEl = document.getElementById('blocked-update-text-second');
                        if (modalMessageEl) modalMessageEl.textContent = msg;
                        document.getElementById('blocked-update-modal-second').classList.remove('hidden');
                    } else if (res.status === 409) {
                        // Already submitted
                        document.getElementById('already-submitted-modal').classList.remove('hidden');
                    } else {
                        messageBox.innerHTML = `<span class="text-red-600">Error: ${data.error || data.message || 'An error occurred'}</span>`;
                        messageBox.className = 'mt-4 p-4 rounded-lg text-sm text-center bg-red-50 text-red-800 border border-red-200 block';
                        messageBox.classList.remove('hidden');
                    }
                }
            } catch (err) {
                console.error(err);
                messageBox.innerHTML = `<span class="text-red-600">Network error. Please try again.</span>`;
                messageBox.className = 'mt-4 p-4 rounded-lg text-sm text-center bg-red-50 text-red-800 border border-red-200 block';
                messageBox.classList.remove('hidden');
            }
        }
    </script>
</body>
</html>