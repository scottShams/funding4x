<?php
    session_start();
    // Include database connection
    require_once 'database.php';

    // Get database connection
    $pdo = getPDO();
    $email = $_SESSION['user_email'];
    if(empty($email)){
        header("Location: index.php");
        exit;
    }
    $stmt = $pdo->prepare("SELECT * FROM waitlist_users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if knowledge test is already completed
    if(empty($user['knowledge_test_result'])){
        header("Location: knowledge-test.php");
        exit;
    }

    // Check if MT5 details are already submitted
    $stmt = $pdo->prepare("SELECT id FROM mt5_details WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $hasMT5Details = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Broker Setup | Global Forex Trader Cup</title>
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

        /* Modal Animations */
        @keyframes fade-in {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes modal-appear {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        .animate-fade-in {
            animation: fade-in 0.3s ease-out;
        }

        .animate-modal-appear {
            animation: modal-appear 0.4s ease-out;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col">

    <!-- Header & Navigation -->
    <header class="header-bg text-white shadow-2xl sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
            <!-- Logo Section -->
            <div class="flex items-center">
                <img src="assets/logo.png" alt="Funding4X Logo" class="h-10 w-10 mr-3 rounded-lg">
                <h1 class="text-2xl font-extrabold tracking-tight text-trophy-gold">Funding4x</h1>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-300">Welcome, <?php echo htmlspecialchars($user['name']); ?></span>
                <a href="logout.php" class="text-sm text-white hover:text-trophy-gold transition duration-300">
                    ← Logout
                </a>
            </div>

        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow flex items-center justify-center p-4 sm:p-8 bg-bg-light relative overflow-hidden">
        
        <!-- Decorative Background Elements -->
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden opacity-5 pointer-events-none">
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-primary-purple rounded-full blur-3xl"></div>
            <div class="absolute top-1/2 -left-24 w-72 h-72 bg-trophy-gold rounded-full blur-3xl"></div>
        </div>

        <div class="w-full max-w-6xl relative">

            <!-- INSTRUCTIONS BLOCK -->
            <div class="bg-card-white p-6 md:p-10 rounded-2xl shadow-xl mb-12 border-t-4 border-trophy-gold">
                <div class="text-center mb-10">
                    <h2 class="text-3xl font-extrabold text-primary-purple mb-3">Your Trading Setup Checklist</h2>
                    <h3>Congratulations <?php echo htmlspecialchars($user['name']); ?> in Getting this Far! We want to see your Trading capability now.</h3>
                    <br/>
                    <p class="text-gray-600">Follow these 4 steps to Setup your Trading and Connect with us for monitoring.</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-12">
                    
                    <!-- Steps 1 & 2 -->
                    <div class="space-y-8">
                        <div class="flex items-start">
                            <div class="step-circle">1</div>
                            <div>
                                <h3 class="font-bold text-xl text-header-dark mb-2">Open an Account</h3>
                                <p class="text-gray-600 leading-relaxed">
                                    Click the <span class="font-bold text-trophy-gold">Register Now</span> button for your chosen broker below. 
                                    <br><span class="text-sm text-red-500 font-semibold italic">Important: You must use these buttons to ensure your account is linked to our tracking system.</span>
                                </p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="step-circle">2</div>
                            <div>
                                <h3 class="font-bold text-xl text-header-dark mb-2">Create a Demo Account</h3>
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
                                
                                 <button type="submit" 
                                class="w-full px-4 py-4 bg-primary-purple text-white font-bold rounded-lg shadow-lg hover:bg-header-dark transition duration-300 uppercase tracking-wide">
                            Send Details to Us
                        </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- CONTENT GRID -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
                
                <!-- LEFT COLUMN: Broker List -->
                <div>
                    <header class="mb-6 pl-2">
                        <h2 class="text-2xl font-bold text-primary-purple">
                            Choose Your Broker
                        </h2>
                        <p class="text-gray-500">Select a partner to begin Step 1.</p>
                    </header>

                    <div class="space-y-5">
                        <!-- Broker 1: Exness -->
                        <div class="broker-card bg-white p-6 rounded-xl shadow-lg flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div class="flex items-center w-full sm:w-auto">
                                <div class="p-3 bg-purple-50 rounded-lg mr-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-primary-purple" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-header-dark">Exness</h3>
                                    <p class="text-xs text-gray-500 font-medium">Instant Withdrawals</p>
                                </div>
                            </div>
                            <a href="https://one.exnessonelink.com/a/c_otpof6c7l3" target="_blank" class="w-full sm:w-auto px-6 py-3 bg-trophy-gold text-header-dark font-bold rounded-lg shadow-md hover:bg-cta-hover hover:text-white transition duration-300 text-center">
                                Register Now
                            </a>
                        </div>

                        <!-- Broker 2: IC Markets -->
                        <div class="broker-card bg-white p-6 rounded-xl shadow-lg flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div class="flex items-center w-full sm:w-auto">
                                <div class="p-3 bg-purple-50 rounded-lg mr-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-primary-purple" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-header-dark">IC Markets</h3>
                                    <p class="text-xs text-gray-500 font-medium">Raw Spread ECN</p>
                                </div>
                            </div>
                            <a href="https://icmarkets.com/?camp=57170" target="_blank" class="w-full sm:w-auto px-6 py-3 bg-trophy-gold text-header-dark font-bold rounded-lg shadow-md hover:bg-cta-hover hover:text-white transition duration-300 text-center">
                                Register Now
                            </a>
                        </div>

                        <!-- Broker 3: Just Markets -->
                        <div class="broker-card bg-white p-6 rounded-xl shadow-lg flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div class="flex items-center w-full sm:w-auto">
                                <div class="p-3 bg-purple-50 rounded-lg mr-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-primary-purple" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-header-dark">Just Markets</h3>
                                    <p class="text-xs text-gray-500 font-medium">Low Deposit Start</p>
                                </div>
                            </div>
                            <a href="https://one.justmarkets.link/a/tru4620nge" target="_blank" class="w-full sm:w-auto px-6 py-3 bg-trophy-gold text-header-dark font-bold rounded-lg shadow-md hover:bg-cta-hover hover:text-white transition duration-300 text-center">
                                Register Now
                            </a>
                        </div>

                        <!-- Broker 4: OctaFX -->
                        <div class="broker-card bg-white p-6 rounded-xl shadow-lg flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div class="flex items-center w-full sm:w-auto">
                                <div class="p-3 bg-purple-50 rounded-lg mr-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-primary-purple" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-header-dark">XM</h3>
                                    <p class="text-xs text-gray-500 font-medium">Copy Trading Ready</p>
                                </div>
                            </div>
                            <a href="https://www.xmglobal.com/referral?token=p69Ai74ZriAsN0_3g9hT0w" target="_blank" class="w-full sm:w-auto px-6 py-3 bg-trophy-gold text-header-dark font-bold rounded-lg shadow-md hover:bg-cta-hover hover:text-white transition duration-300 text-center">
                                Register Now
                            </a>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: MT5 Login Form (Step 4) -->
                <div class="bg-white p-8 rounded-2xl shadow-2xl border-2 border-primary-purple h-fit lg:sticky lg:top-24">
                    <h2 class="text-2xl font-bold text-primary-purple mb-2">Step 4: Account Submission</h2>
                    <p class="text-sm text-gray-600 mb-6">
                        Enter the **Demo Account** details you created in Step 2. We will set up the trade monitoring for the competition.
                    </p>

                    <form id="mt5-form" onsubmit="handleFormSubmit(event)">
                        
                        <div class="mb-4">
                            <label for="username" class="block text-sm font-semibold text-gray-700 mb-1">MT5 Login ID</label>
                            <input type="text" id="username" name="username" required 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-primary-purple focus:border-primary-purple transition duration-150 ease-in-out bg-gray-50"
                                   placeholder="e.g. 50123456">
                        </div>

                        <div class="mb-4">
                            <label for="password" class="block text-sm font-semibold text-gray-700 mb-1">Trader Password</label>
                            <input type="text" id="password" name="password" required 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-primary-purple focus:border-primary-purple transition duration-150 ease-in-out bg-gray-50"
                                   placeholder="password">
                        </div>

                        <div class="mb-6">
                            <label for="server" class="block text-sm font-semibold text-gray-700 mb-1">Broker Server Name</label>
                            <input type="text" id="server" name="server" required 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-primary-purple focus:border-primary-purple transition duration-150 ease-in-out bg-gray-50"
                                   placeholder="e.g., Exness-Trial9">
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
                <!-- Success Icon -->
                <div class="mx-auto w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>

                <h2 class="text-3xl font-extrabold text-primary-purple mb-4">Thank You <?php echo htmlspecialchars($user['name']); ?>!</h2>
                <p class="text-gray-700 mb-8 leading-relaxed text-lg">
                    We got your details. Please wait for us to setup your account on our Servers. We will update you by Email within few hours. We are so Excited to see you in action!
                </p>

                <div class="flex justify-center space-x-4">
                    <a href="referral_dashboard.php" class="px-8 py-4 bg-primary-purple text-white font-bold rounded-xl shadow-lg hover:bg-header-dark transition-all duration-300 transform hover:scale-105">
                        Continue to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Already Submitted Modal -->
    <div id="already-submitted-modal" class="fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm flex items-center justify-center z-50 <?php echo $hasMT5Details ? '' : 'hidden'; ?> animate-fade-in">
        <div class="bg-white p-10 rounded-3xl shadow-2xl max-w-lg mx-4 border-t-4 border-primary-purple transform scale-95 animate-modal-appear">
            <div class="text-center">
                <!-- Info Icon -->
                <div class="mx-auto w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>

                <h2 class="text-3xl font-extrabold text-primary-purple mb-4">Details Already Submitted</h2>
                <p class="text-gray-700 mb-8 leading-relaxed text-lg">
                    Hello <?php echo htmlspecialchars($user['name']); ?>, you have already submitted your MT5 account details for monitoring.
                    Our team is currently setting up your account. Please check back later or contact support if you need assistance.
                </p>

                <div class="flex justify-center space-x-4">
                    <a href="referral_dashboard.php" class="px-8 py-4 bg-primary-purple text-white font-bold rounded-xl shadow-lg hover:bg-header-dark transition-all duration-300 transform hover:scale-105">
                        ← Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function handleFormSubmit(event) {
            event.preventDefault();

            const form = document.getElementById('mt5-form');
            const username = form.username.value;
            const password = form.password.value;
            const server = form.server.value;
            const messageBox = document.getElementById('message-box');

            const mt5Details = {
                username: username,
                password: password,
                server: server,
                submitted_at: new Date().toISOString()
            };

            // Send to server
            fetch('store_mt5_details.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ mt5_details: mt5Details }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show modal
                    document.getElementById('success-modal').classList.remove('hidden');
                    form.reset();

                    // setTimeout(() => {
                    //     window.location.href = "referral_dashboard.php";
                    // }, 5000);
                } else {
                    // Show error
                    messageBox.innerHTML = `<span class="text-red-600">Error: ${data.error}</span>`;
                    messageBox.className = 'mt-4 p-4 rounded-lg text-sm text-center bg-red-50 text-red-800 border border-red-200 block';
                    messageBox.classList.remove('hidden');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                messageBox.innerHTML = `<span class="text-red-600">Network error. Please try again.</span>`;
                messageBox.className = 'mt-4 p-4 rounded-lg text-sm text-center bg-red-50 text-red-800 border border-red-200 block';
                messageBox.classList.remove('hidden');
            });
        }

        function closeModal() {
            document.getElementById('success-modal').classList.add('hidden');
        }
    </script>
</body>
</html>