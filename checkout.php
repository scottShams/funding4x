<?php
    session_start();
    // Include database connection
    require_once 'database.php';

    // Get database connection
    $pdo = getPDO();
    $email = $_SESSION['user_email'];
    // Get price from URL parameter, default to 59 if not set
    $checkoutPrice = isset($_SESSION['checkout_price']) ? (int)$_SESSION['checkout_price'] : 59;
    if(empty($email)){
        header("Location: index.php");
        exit;
    }
    $stmt = $pdo->prepare("SELECT * FROM waitlist_users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if user has already made a payment
    $stmt = $pdo->prepare("SELECT id FROM payments WHERE user_id = ? AND status = 'completed'");
    $stmt->execute([$user['id']]);
    $hasPayment = $stmt->fetch(PDO::FETCH_ASSOC);

    // Handle payment submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
        $paymentMethod = $_POST['payment_method'];
        $amount = (float)$_POST['amount'];

        try {
            if ($paymentMethod === 'crypto') {
                // Handle crypto payment
                $cryptoType = $_POST['crypto_type'] ?? 'USDC';
                $cryptoNetwork = $_POST['crypto_network'] ?? 'Tron';
                $walletAddress = $_POST['wallet_address'];
                $transactionHash = $_POST['transaction_hash'];

                $stmt = $pdo->prepare("INSERT INTO payments (
                    user_id, payment_method, amount, currency, status,
                    crypto_type, crypto_network, wallet_address, transaction_hash,
                    created_at
                ) VALUES (?, ?, ?, 'USD', 'completed', ?, ?, ?, ?, NOW())");

                $stmt->execute([
                    $user['id'],
                    $paymentMethod,
                    $amount,
                    $cryptoType,
                    $cryptoNetwork,
                    $walletAddress,
                    $transactionHash
                ]);

            } elseif ($paymentMethod === 'credit_card') {
                // Handle credit card payment (in real app, this would be processed by payment gateway)
                $cardName = $_POST['card_name'];
                // Note: In production, card details should be encrypted and processed securely
                $cardNumber = $_POST['card_number']; // This should be tokenized/encrypted
                $cardExpiry = $_POST['card_expiry']; // This should be encrypted
                $cardCvc = $_POST['card_cvc']; // This should be encrypted

                $stmt = $pdo->prepare("INSERT INTO payments (
                    user_id, payment_method, amount, currency, status,
                    card_name, card_number_encrypted, card_expiry_encrypted, card_cvc_encrypted,
                    payment_gateway, gateway_transaction_id, created_at
                ) VALUES (?, ?, ?, 'USD', 'completed', ?, ?, ?, ?, 'Demo Gateway', CONCAT('demo_', UNIX_TIMESTAMP()), NOW())");

                $stmt->execute([
                    $user['id'],
                    $paymentMethod,
                    $amount,
                    $cardName,
                    $cardNumber, // In production: encrypt this
                    $cardExpiry, // In production: encrypt this
                    $cardCvc, // In production: encrypt this
                ]);
            }

            // Return success response
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Payment processed successfully']);
            exit;

        } catch (Exception $e) {
            // Return error response
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Payment processing failed: ' . $e->getMessage()]);
            exit;
        }
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | Global Forex Trader Cup</title>
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
        .header-bg {
            background-color: #240046;
        }
        .payment-option {
            transition: all 0.2s;
        }
        .payment-option:hover {
            box-shadow: 0 4px 15px rgba(79, 0, 157, 0.1);
        }
        /* Custom radio button styling */
        input[type="radio"]:checked + label .check-icon {
            display: block;
            background-color: #4f009d; /* primary-purple */
            border-color: #4f009d;
        }
        input[type="radio"]:checked + label {
            border-color: #b49852; /* trophy-gold */
            box-shadow: 0 0 0 2px rgba(180, 152, 82, 0.5);
        }
        .check-icon {
            border: 2px solid #ccc;
            background-color: #fff;
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
            display: none;
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
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
    <header class="sticky top-0 z-20 bg-header-dark shadow-xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo Section -->
                <div class="flex items-center">
                    <img src="assets/logo.png" alt="Funding4X Logo" class="h-10 w-10 mr-3 rounded-lg">
                    <h1 class="text-2xl font-extrabold tracking-tight text-trophy-gold">Funding4x</h1>
                </div>
                <!-- Desktop Nav -->
                <nav class="hidden md:flex space-x-8">
                    <a href="home.php" class="text-trophy-gold font-bold transition duration-150 border-b-2 border-trophy-gold">Home</a>

                    <a href="rule.php" class="text-gray-300 hover:text-trophy-gold transition duration-150 font-medium">Rules</a>
                    <a href="pricing.php" class="text-gray-300 hover:text-trophy-gold transition duration-150 font-medium">Login</a>
                    <button onclick="window.location.href='pricing.php'" class="bg-primary-indigo hover:bg-indigo-700 text-white font-semibold py-1 px-3 text-sm rounded transition duration-300 shadow-md">
                        Start Trading
                    </button>
                </nav>
                <!-- Mobile Menu Button (Hamburger) -->
                <button id="menu-button" class="md:hidden text-gray-300 hover:text-trophy-gold focus:outline-none">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                </button>
            </div>
        </div>
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden bg-header-dark pb-3 px-2 pt-2 space-y-1 sm:px-3">
            <a href="home.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:bg-secondary-purple">Home</a>
            <a href="rule.php" class="block px-3 py-2 rounded-md text-base font-medium text-trophy-gold hover:bg-secondary-purple">Rules</a>
            <a href="pricing.php" class="block px-3 py-2 rounded-md text-base font-medium bg-primary-purple text-white mt-2">Register Now</a>
        </div>
    </header>

    <!-- Main Content: Checkout Grid -->
    <main class="flex-grow flex justify-center p-4 sm:p-8">
        <div class="w-full max-w-5xl"> <!-- Main content container -->
            
            <!-- NEW HERO SECTION: Motivational Banner -->
            <div class="mb-10 p-8 sm:p-12 rounded-2xl bg-primary-purple text-white shadow-2xl transform hover:scale-[1.01] transition duration-300">
                <div class="max-w-4xl mx-auto text-center">
                    <h2 class="text-4xl sm:text-5xl font-extrabold tracking-tight mb-4 text-white">
                        Secure Your Seat at the Elite Table
                    </h2>
                    <p class="text-xl font-medium mb-8 text-purple-200">
                        This isn't just an entry fee—it's your bridge to a funded career in proprietary trading with a $5,000 Trading Account, and earning up to 50:50 profit split.
                    </p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-header-dark">
                        <!-- Benefit 1 -->
                        <div class="bg-trophy-gold p-4 rounded-xl shadow-lg border-b-4 border-yellow-700">
                            <p class="font-bold text-lg mb-1">📈 Private Funding</p>
                            <p class="text-sm">Top performers are offered $5,000 capital to manage.</p>
                        </div>
                        <!-- Benefit 2 -->
                        <div class="bg-trophy-gold p-4 rounded-xl shadow-lg border-b-4 border-yellow-700">
                            <p class="font-bold text-lg mb-1">🏆 Prestigious Recognition</p>
                            <p class="text-sm">Certificate to confirm passing Trading Tests, to showcase your capability.</p>
                        </div>
                        <!-- Benefit 3 -->
                        <div class="bg-trophy-gold p-4 rounded-xl shadow-lg border-b-4 border-yellow-700">
                            <p class="font-bold text-lg mb-1">💡 Exclusive Club</p>
                            <p class="text-sm">Join our Team as a Prop Trader, be part of our exclusive club. </p>
                        </div>
                    </div>

                    <br />
                    <p class="text-xl font-medium mb-8 text-purple-200">
                        How much Profit could you make every month on a $5,000 Funded Account? Think about it.
                    </p>
                </div>
            </div>
            <!-- END NEW HERO SECTION -->
            
            <!-- ORIGINAL CHECKOUT GRID -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
                <!-- LEFT COLUMN: Order Summary -->
                <div class="lg:col-span-1 bg-card-white p-6 rounded-xl shadow-xl border-t-4 border-trophy-gold h-fit lg:sticky lg:top-24">
                    <h2 class="text-2xl font-bold text-primary-purple mb-4 border-b pb-2">Order Summary</h2>

                    <div class="space-y-3">
                        <!-- Item 1 -->
                        <div class="flex justify-between text-gray-700">
                            <span class="text-sm">Competition Entry Fee</span>
                            <span class="font-semibold">$<?php echo number_format($checkoutPrice, 2); ?></span>
                        </div>
                      

                        
                        <div class="border-t border-dashed my-4 pt-3"></div>

                        <!-- Subtotal
                        <div class="flex justify-between text-base">
                            <span>Subtotal:</span>
                            <span class="font-medium">$148.00</span>
                        </div>
                    -->

                        <!-- Tax/Fees
                        <div class="flex justify-between text-sm text-gray-500">
                            <span>Platform Fee (2.5%):</span>
                            <span class="font-medium">$3.70</span>
                        </div>
                    -->

                        <div class="border-t mt-4 pt-4"></div>

                        <!-- Total -->
                        <div class="flex justify-between items-center text-xl font-extrabold text-header-dark">
                            <span>Total Due:</span>
                            <span class="text-primary-purple">$<?php echo number_format($checkoutPrice, 2); ?></span>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: Payment Methods -->
                <div class="lg:col-span-2 space-y-8">
                    <h2 class="text-3xl font-extrabold text-header-dark">Select Payment Method</h2>
                    
                    <!-- Payment Method Selection -->
                    <div class="space-y-4">
                        
                        <!-- Credit Card Option (Hidden) -->
                        <input type="radio" id="pay-cc" name="payment_method" value="credit_card" class="hidden" onchange="togglePaymentForm(this.value)" style="display: none;">
                        <label for="pay-cc" class="payment-option flex items-center p-5 rounded-xl shadow-md cursor-pointer border-2 border-primary-purple/5 bg-card-white relative hidden">
                            <div class="flex items-center flex-grow">
                                <!-- Card Icon (SVG from Lucide) -->
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#4f009d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 mr-4"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
                                <span class="text-lg font-bold text-header-dark">Credit/Debit Card</span>
                            </div>
                            <div class="flex space-x-2 mr-6">
                                <!-- Placeholder Icons for Card Types -->
                                <img src="https://placehold.co/40x25/000055/ffffff?text=VISA" alt="Visa" class="rounded border border-gray-200">
                                <img src="https://placehold.co/40x25/AA0000/ffffff?text=MC" alt="Mastercard" class="rounded border border-gray-200">
                            </div>
                            <div class="check-icon"></div>
                        </label>

                        <!-- Crypto Option -->
                        <input type="radio" id="pay-crypto" name="payment_method" value="crypto" class="hidden" checked onchange="togglePaymentForm(this.value)">
                        <label for="pay-crypto" class="payment-option flex items-center p-5 rounded-xl shadow-md cursor-pointer border-2 border-primary-purple/5 bg-card-white relative">
                            <div class="flex items-center flex-grow">
                                <!-- Crypto Icon (SVG from Lucide) -->
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#4f009d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 mr-4"><path d="M10.7 13.1c1.24 1.24 2.5 2.22 3.8 2.22 1.45 0 2.2-.82 2.2-2.3 0-1.07-.65-1.74-1.85-2.2-.8-.28-1.7-.53-2.3-.77-1.12-.48-1.57-.88-1.57-1.55 0-.7.6-1.13 1.5-1.13.9 0 1.6.43 2.1 1.05m-2.1-1.05v-2m0 6v2m-3.3 0h-2m-3.4 0H5m-2-6h2m-2-6h2M3 3h18v18H3z"/></svg>
                                <span class="text-lg font-bold text-header-dark">Cryptocurrency</span>
                            </div>
                            <div class="flex space-x-2 mr-6">
                                <!-- Placeholder Icons for Crypto Types (Simplified Emojis/Text for visual) -->
                                <span class="text-xl">₿</span> <!-- Bitcoin -->
                                <span class="text-xl text-blue-500">Ξ</span> <!-- Ethereum -->
                                <span class="text-xs font-mono bg-gray-200 p-1 rounded">USDC</span>
                            </div>
                            <div class="check-icon"></div>
                        </label>
                    </div>
                    
                    <!-- Payment Forms Container -->
                    <div id="payment-forms-container">

                        <!-- 1. Credit Card Form (Visible by default) -->
                        <div id="credit_card_form" class="bg-card-white p-8 rounded-xl shadow-2xl border-t-4 border-primary-purple">
                            <h3 class="text-xl font-bold text-primary-purple mb-6">Enter Card Details</h3>
                            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" onsubmit="handlePayment(event, 'Credit Card')">
                                
                                <div class="mb-4">
                                    <label for="card_number" class="block text-sm font-medium text-gray-700 mb-1">Card Number</label>
                                    <input type="text" id="card_number" required placeholder="XXXX XXXX XXXX XXXX" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-trophy-gold focus:border-trophy-gold">
                                </div>

                                <div class="grid grid-cols-3 gap-4 mb-6">
                                    <div class="col-span-2">
                                        <label for="expiry" class="block text-sm font-medium text-gray-700 mb-1">Expiry Date (MM/YY)</label>
                                        <input type="text" id="expiry" required placeholder="05/26" 
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-trophy-gold focus:border-trophy-gold">
                                    </div>
                                    <div>
                                        <label for="cvc" class="block text-sm font-medium text-gray-700 mb-1">CVC</label>
                                        <input type="text" id="cvc" required placeholder="123" 
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-trophy-gold focus:border-trophy-gold">
                                    </div>
                                </div>
                                
                                <div class="mb-6">
                                    <label for="card_name" class="block text-sm font-medium text-gray-700 mb-1">Cardholder Name</label>
                                    <input type="text" id="card_name" required placeholder="John Doe" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-trophy-gold focus:border-trophy-gold">
                                </div>

                                <button type="submit" class="w-full px-4 py-4 bg-trophy-gold text-header-dark font-bold rounded-lg shadow-lg hover:bg-cta-hover hover:text-white transition duration-300 uppercase tracking-wide">
                                    Pay $<?php echo number_format($checkoutPrice, 2); ?>
                                </button>

                                <div class="mt-4 text-center">
                                    <a href="referral_dashboard.php" class="inline-block px-6 py-3 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 transition duration-300">
                                        ← Back to Dashboard
                                    </a>
                                </div>
                            </form>
                        </div>

                        <!-- 2. Crypto Payment Details (Hidden by default) -->
                        <div id="crypto_form" class="bg-card-white p-8 rounded-xl shadow-2xl border-t-4 border-secondary-purple hidden">
                            <h3 class="text-xl font-bold text-primary-purple mb-6">Pay with Crypto (USDC)</h3>
                            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" onsubmit="handlePayment(event, 'Crypto')">
                                <p class="text-gray-600 mb-6">
                                    Please send exactly <span class="font-bold text-lg text-primary-purple"><?php echo number_format($checkoutPrice, 2); ?> USDC</span> to the address below.
                                </p>

                                
								<div class="mb-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">USDT TRC20 Wallet Address (Tron Network)</label>
                                    <div class="flex items-center space-x-3">
                                        <input type="text" value="TRTrqYNy2DwjJiQ15AcmJDMyyh39gqai17" readonly id="crypto-address"
                                            class="flex-grow font-mono text-sm px-3 py-2 border border-gray-300 rounded-lg bg-white cursor-text">
                                        <button type="button" onclick="copyAddress()" 
                                                class="p-2 bg-primary-purple text-white rounded-lg hover:bg-header-dark transition duration-150 text-xs font-semibold">
                                            Copy Address
                                        </button>
                                    </div>
                                    <p id="copy-status" class="text-xs text-green-600 mt-2 hidden">Address copied!</p>
                                </div>
                                
                                <div class="mb-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">USDC Wallet Address (Arbitrium Network)</label>
                                    <div class="flex items-center space-x-3">
                                        <input type="text" value="0xE1985A1d88552020152d16f86D6581c71E9D148f" readonly id="crypto-address"
                                            class="flex-grow font-mono text-sm px-3 py-2 border border-gray-300 rounded-lg bg-white cursor-text">
                                        <button type="button" onclick="copyAddress()" 
                                                class="p-2 bg-primary-purple text-white rounded-lg hover:bg-header-dark transition duration-150 text-xs font-semibold">
                                            Copy Address
                                        </button>
                                    </div>
                                    <p id="copy-status" class="text-xs text-green-600 mt-2 hidden">Address copied!</p>
                                </div>

                                <div class="mb-6">
                                    <label for="tx_hash" class="block text-sm font-medium text-gray-700 mb-1">Transaction Hash / ID</label>
                                    <input type="text" id="tx_hash" required placeholder="0xabc123..." 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-trophy-gold focus:border-trophy-gold">
                                </div>
                                
                                <button type="submit" class="w-full px-4 py-4 bg-primary-purple text-white font-bold rounded-lg shadow-lg hover:bg-header-dark transition duration-300 uppercase tracking-wide">
                                    Confirm Crypto Payment ($<?php echo number_format($checkoutPrice, 2); ?>)
                                </button>

                                <div class="mt-4 text-center">
                                    <a href="referral_dashboard.php" class="inline-block px-6 py-3 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 transition duration-300">
                                        ← Back to Dashboard
                                    </a>
                                </div>
                            </form>
                        </div>

                        <!-- Payment Status Message Box -->
                        <div id="payment-status-box" class="mt-8 p-4 rounded-lg text-sm text-center hidden font-medium"></div>

                    </div>

                </div>
            </div>
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

                <h2 class="text-3xl font-extrabold text-primary-purple mb-4">Payment Successful!</h2>
                <p class="text-gray-700 mb-8 leading-relaxed text-lg">
                    Thank You for your Payment of <strong>$<?php echo number_format($checkoutPrice, 2); ?></strong> by using Crypto Currency. We will review your payment shortly to ensure this has been received Safely. You will receive an email to confirm receipt.
                </p>

                <div class="flex justify-center space-x-4">
                    <a href="referral_dashboard.php" class="px-8 py-4 bg-primary-purple text-white font-bold rounded-xl shadow-lg hover:bg-header-dark transition-all duration-300 transform hover:scale-105">
                        Continue to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Already Paid Modal -->
    <!-- <div id="already-paid-modal" class="fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm flex items-center justify-center z-50 <?php echo $hasPayment ? '' : 'hidden'; ?> animate-fade-in">
        <div class="bg-white p-10 rounded-3xl shadow-2xl max-w-lg mx-4 border-t-4 border-primary-purple transform scale-95 animate-modal-appear">
            <div class="text-center relative">

                <button onclick="closeAlreadyPaidModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition duration-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>

                <div class="mx-auto w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>

                <h2 class="text-3xl font-extrabold text-primary-purple mb-4">Payment Already Completed</h2>
                <p class="text-gray-700 mb-8 leading-relaxed text-lg">
                    Hello <?php echo htmlspecialchars($user['name']); ?>, you have already completed your payment for the competition entry.
                    Our team is currently processing your payment. Please check back later or contact support if you need assistance.
                </p>

                <div class="flex justify-center space-x-4">
                    <a href="referral_dashboard.php" class="px-8 py-4 bg-primary-purple text-white font-bold rounded-xl shadow-lg hover:bg-header-dark transition-all duration-300 transform hover:scale-105">
                        ← Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div> -->

    <!-- Footer -->
    <footer class="bg-header-dark text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 text-center">
            <p class="text-sm">&copy; 2024 Funding4x. All rights reserved. | Powered by Referrals.</p>
        </div>
    </footer>

    <script>
        // Function to toggle between payment forms
        function togglePaymentForm(method) {
            const ccForm = document.getElementById('credit_card_form');
            const cryptoForm = document.getElementById('crypto_form');

            if (method === 'credit_card') {
                ccForm.classList.remove('hidden');
                cryptoForm.classList.add('hidden');
            } else if (method === 'crypto') {
                ccForm.classList.add('hidden');
                cryptoForm.classList.remove('hidden');
            }
        }

        // Function to handle form submission
        function handlePayment(event, method) {
            event.preventDefault();
            const statusBox = document.getElementById('payment-status-box');
            statusBox.classList.remove('hidden', 'bg-red-100', 'text-red-800', 'bg-green-100', 'text-green-800');
            statusBox.classList.add('bg-blue-100', 'text-blue-800', 'block');
            statusBox.innerHTML = `Processing payment via ${method}... Please wait.`;

            // Prepare form data
            const formData = new FormData(event.target);
            formData.append('process_payment', '1');
            formData.append('payment_method', method.toLowerCase().replace(' ', '_'));
            formData.append('amount', '<?php echo $checkoutPrice; ?>');

            // Add specific fields based on payment method
            if (method.toLowerCase() === 'crypto') {
                const transactionHash = document.getElementById('tx_hash').value;
                formData.append('transaction_hash', transactionHash);

                // For demo purposes, we'll use the Tron wallet as default
                // In a real app, you'd detect which wallet the user actually used
                formData.append('crypto_type', 'USDC');
                formData.append('crypto_network', 'Tron');
                formData.append('wallet_address', 'TRTrqYNy2DwjJiQ15AcmJDMyyh39gqai17');
            }

            // Send AJAX request
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                statusBox.classList.remove('bg-blue-100', 'text-blue-800');

                if (data.success) {
                    // Show success modal
                    document.getElementById('success-modal').classList.remove('hidden');
                    event.target.reset(); // Clear form fields
                } else {
                    statusBox.classList.add('bg-red-100', 'text-red-800');
                    statusBox.innerHTML = `
                        <span class="text-lg block mb-1">❌ Payment Failed</span>
                        ${data.message}
                    `;
                }
            })
            .catch(error => {
                statusBox.classList.remove('bg-blue-100', 'text-blue-800');
                statusBox.classList.add('bg-red-100', 'text-red-800');
                statusBox.innerHTML = `
                    <span class="text-lg block mb-1">❌ Error</span>
                    An error occurred while processing your payment. Please try again.
                `;
                console.error('Payment error:', error);
            });
        }

        // Function to copy Crypto Address (using deprecated but reliable execCommand in iframes)
        function copyAddress() {
            const addressInput = document.getElementById('crypto-address');
            const status = document.getElementById('copy-status');
            
            // Select the text field content
            addressInput.select();
            addressInput.setSelectionRange(0, 99999); // For mobile devices

            try {
                // Copy the text inside the text field
                document.execCommand('copy');
                status.classList.remove('hidden');
                setTimeout(() => status.classList.add('hidden'), 2000);
            } catch (err) {
                console.error('Could not copy text: ', err);
                // Fallback message if copying failed
                status.innerHTML = 'Copy failed. Please manually copy the address.';
                status.classList.remove('hidden');
                status.classList.remove('text-green-600');
                status.classList.add('text-red-600');
                setTimeout(() => status.classList.add('hidden'), 3000);
            }
        }
        
        // Function to close already paid modal
        function closeAlreadyPaidModal() {
            document.getElementById('already-paid-modal').classList.add('hidden');
        }

        // Ensure the correct payment form is visible on load based on default radio
        window.onload = () => {
              // Initial check to apply styling to the default selected radio button
            const defaultChecked = document.querySelector('input[name="payment_method"]:checked');
            if (defaultChecked) {
                togglePaymentForm(defaultChecked.value);
            }
        };
    </script>
</body>
</html>