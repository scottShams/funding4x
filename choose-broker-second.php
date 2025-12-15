<?php
    session_start();
    // Include database connection
    require_once 'database.php';

    // Get database connection
    $pdo = getPDO();
    $email = $_SESSION['user_email'];
    if(empty($email)){
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
                            <a href="login.php" class="px-8 py-4 bg-primary-purple text-white font-bold rounded-xl shadow-lg hover:bg-header-dark transition-all duration-300 transform hover:scale-105">
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

    // // Check if knowledge test is already completed
    // if(empty($user['knowledge_test_result'])){
    //     header("Location: knowledge-test.php");
    //     exit;
    // }

    // Check if MT5 details are already submitted
    // $stmt = $pdo->prepare("SELECT id FROM mt5_details WHERE user_id = ?");
    // $stmt->execute([$user['id']]);
    // if($stmt->fetch(PDO::FETCH_ASSOC)){
    //     header("Location: referral_dashboard.php");
    //     exit;
    // }
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
                            <input type="password" id="password" name="password" required 
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:ring-primary-purple focus:border-primary-purple transition duration-150 ease-in-out bg-gray-50"
                                   placeholder="********">
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

    <script>
        function handleFormSubmit(event) {
            event.preventDefault(); 
            
            const form = document.getElementById('mt5-form');
            const username = form.username.value;
            const server = form.server.value;
            const messageBox = document.getElementById('message-box');

            // Simulate submission
            console.log("Details Submitted:", { username, server });

            messageBox.innerHTML = `
                <span class="text-lg block mb-1">✅ Details Received!</span>
                Monitoring setup for account <strong>${username}</strong> on <strong>${server}</strong> initiated. 
                <br>Please wait for email confirmation before taking your first trade.
            `;
            messageBox.className = 'mt-4 p-4 rounded-lg text-sm text-center bg-green-50 text-green-800 border border-green-200 block shadow-inner';
            
            form.reset();

            setTimeout(() => {
                messageBox.classList.add('hidden');
            }, 8000);
        }
    </script>
</body>
</html>