<?php
    session_start();
    // Load environment variables
    require_once __DIR__ . '/env_loader.php';

    // Get reCAPTCHA site key from environment
    $recaptchaSiteKey = EnvLoader::get('RECAPTCHA_SITE_KEY', 'your_recaptcha_site_key_here');

    // Get current users count from mt5_details table
    require_once 'database.php';
    
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Funding4x Trader Account Options</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon-16x16.png">
    <link rel="manifest" href="assets/site.webmanifest">
    
    <!-- Load Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <script>
        // Theme configuration reused for consistency
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        // PRESTIGIOUS PURPLE PALETTE
                        'primary-purple': '#4f009d', // Deep Royal Purple
                        'secondary-purple': '#7b2cbf',
                        'trophy-gold': '#b49852', // Classic, muted Gold
                        'header-dark': '#240046', // Dark background
                        'bg-light': '#f3f4f6',
                        'success-green': '#10b981', 
                        'cta-hover': '#9d7c49',
                        'card-white': '#ffffff',
                    },
                    keyframes: {
                        pulse: {
                            '0%, 100%': { opacity: 1 },
                            '50%': { opacity: .75 },
                        }
                    },
                    animation: {
                        pulse: 'pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }
        .header-bg {
            background-color: #240046;
        }
        .pricing-card {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            min-height: 550px;
            display: flex;
            flex-direction: column;
        }
        .pricing-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 30px -10px rgba(0, 0, 0, 0.15);
        }
        .spinner {
            border: 4px solid #f3f4f6;
            border-top: 4px solid #b49852;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loader-overlay {
            background-color: rgba(17, 24, 39, 0.95);
            backdrop-filter: blur(5px);
        }
        .tab-button.active {
            background-color: #4f009d;
            color: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .tab-button:not(.active) {
            background-color: #e5e7eb;
            color: #240046;
        }
        .tab-nav-container {
            display: flex;
            overflow-x: auto;
            white-space: nowrap;
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        .tab-nav-container::-webkit-scrollbar {
            display: none;
        }
        .tab-button {
            flex-shrink: 0;
            min-width: 50%;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col">

    <?php include 'header.php'; ?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-16">

        <h2 class="text-4xl md:text-5xl font-extrabold text-center text-header-dark mb-4">
            Choose Your Path to Funding
        </h2>
        <p class="text-center text-lg text-gray-600 max-w-4xl mx-auto mb-12">
            We offer plans for every trader: start for free, try a small challenge, or go straight for serious capital. Simple rules, up to 90% profit split.
        </p>

        <!-- Tab Navigation (Mobile Only) -->
        <div class="md:hidden tab-nav-container justify-start p-1 bg-gray-200 rounded-xl max-w-full mx-auto mb-6 shadow-inner">
            <button id="tab-fast" data-target="card-fast" class="tab-button py-2 text-xs font-bold rounded-lg transition duration-200 active">
                Fast Access
            </button>
            <button id="tab-slow" data-target="card-slow" class="tab-button py-2 text-xs font-bold rounded-lg transition duration-200">
                Slow Access
            </button>
        </div>

        <!-- Pricing Cards Container -->
        <div id="pricing-container" class="grid md:grid-cols-2 gap-6 mx-auto">

            <!-- Card 1: FAST Access -->
            <div id="card-fast" class="card-item pricing-card w-full bg-white p-6 md:p-8 rounded-xl border-t-8 border-trophy-gold hidden md:flex">
                <div class="text-center">
                    <p class="text-sm font-semibold text-trophy-gold uppercase tracking-widest">Fast Access</p>
                    <h3 class="mt-2 text-4xl font-extrabold text-header-dark">$5,000</h3>
                    <p class="mt-1 text-2xl font-bold text-gray-900">
                        $36.58
                        <span class="text-base font-normal text-gray-500">one-time fee</span>
                    </p>
                </div>

                <ul role="list" class="mt-8 space-y-4 text-gray-600 flex-grow">
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-success-green mt-1 mr-3 flex-shrink-0"></i>
                        <span><strong class="text-gray-900">Trading Challenge Phase 1</strong></span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-success-green mt-1 mr-3 flex-shrink-0"></i>
                        <span><strong class="text-gray-900">Start Trading within 1 hour</strong></span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-success-green mt-1 mr-3 flex-shrink-0"></i>
                        <span><strong class="text-gray-900">$5,000 Starting Account Balance</strong></span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-success-green mt-1 mr-3 flex-shrink-0"></i>
                        <span><strong class="text-gray-900">1 Free Retry (if you fail)</strong></span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-success-green mt-1 mr-3 flex-shrink-0"></i>
                        <span><strong class="text-gray-900">2 Chances to Continue if you break Soft Rules</strong></span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-success-green mt-1 mr-3 flex-shrink-0"></i>
                        <span><strong class="text-gray-900">50:50 Profit Share</strong></span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-success-green mt-1 mr-3 flex-shrink-0"></i>
                        <span><strong class="text-gray-900">Priority Support - No Long Waiting</strong></span>
                    </li>
                </ul>

                <a href="login.php?paid=1&price=36.58" class="mt-auto block w-full text-center py-3 px-6 rounded-full text-white font-bold bg-trophy-gold hover:bg-trophy-gold/80 transition duration-300">
                    Get My Account Now
                </a>
            </div>

            <!-- Card 2: SLOW Access -->
            <div id="card-slow" class="card-item pricing-card w-full bg-white p-6 md:p-8 rounded-xl border-t-8 border-success-green hidden md:flex">
                <div class="text-center">
                    <p class="text-sm font-semibold text-success-green uppercase tracking-widest">Slow Access</p>
                    <h3 class="mt-2 text-4xl font-extrabold text-header-dark">$5,000</h3>
                    <p class="mt-1 text-2xl font-bold text-gray-900">
                        FREE
                        <span class="text-base font-normal text-gray-500">with 5 referrals</span>
                    </p>
                </div>

                <ul role="list" class="mt-8 space-y-4 text-gray-600 flex-grow">
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-success-green mt-1 mr-3 flex-shrink-0"></i>
                        <span><strong class="text-gray-900">Trading Challenge Phase 1</strong></span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-success-green mt-1 mr-3 flex-shrink-0"></i>
                        <span><strong class="text-gray-900">Start Trading AFTER you have 5 completed Referrals</strong></span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-success-green mt-1 mr-3 flex-shrink-0"></i>
                        <span><strong class="text-gray-900">$5,000 Starting Account balance</strong></span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-success-green mt-1 mr-3 flex-shrink-0"></i>
                        <span><strong class="text-gray-900">2 Chances to Continue if you break Soft Rules</strong></span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-success-green mt-1 mr-3 flex-shrink-0"></i>
                        <span><strong class="text-gray-900">50:50 Profit Share</strong></span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-times-circle text-red-500 mt-1 mr-3 flex-shrink-0"></i>
                        <span><strong class="text-gray-900">No free Retry</strong></span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-times-circle text-red-500 mt-1 mr-3 flex-shrink-0"></i>
                        <span><strong class="text-gray-900">Long Que for Approval</strong></span>
                    </li>
                </ul>

                <a href="login.php" class="mt-auto block w-full text-center py-3 px-6 rounded-full text-white font-bold bg-success-green hover:bg-success-green/80 transition duration-300">
                    Start Free Trial
                </a>
            </div>

        </div>

    </main>

    <!-- Footer (reusing the dark header style for visual consistency) -->
    <footer class="bg-header-dark text-white mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 text-center">
            <p class="text-sm">&copy; 2024 Funding4x. All rights reserved.</p>
        </div>
    </footer>

    <!-- JavaScript for Tab Switching -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tabButtons = document.querySelectorAll('.tab-button');
            const cardItems = document.querySelectorAll('.card-item');

            // Function to handle tab clicks
            function switchTab(targetId) {
                // 1. Reset all button states
                tabButtons.forEach(btn => {
                    btn.classList.remove('active');
                });

                // 2. Hide all cards (only necessary on mobile)
                cardItems.forEach(card => {
                    if (window.innerWidth < 768) {
                        card.classList.add('hidden');
                    }
                });

                // 3. Set active button state
                const activeButton = document.querySelector(`[data-target="${targetId}"]`);
                if (activeButton) {
                    activeButton.classList.add('active');
                }

                // 4. Show the targeted card (only necessary on mobile)
                const targetCard = document.getElementById(targetId);
                if (targetCard) {
                    if (window.innerWidth < 768) {
                        targetCard.classList.remove('hidden');
                    }
                }
            }

            // Add event listeners to buttons
            tabButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const targetId = button.getAttribute('data-target');
                    switchTab(targetId);
                });
            });

            // Initial setup: If on mobile, ensure only the first card (Fast Access) is visible.
            if (window.innerWidth < 768) {
                cardItems.forEach(card => card.classList.add('hidden'));
                switchTab('card-fast');
            }
        });
    </script>

    <script>
        // Ensure we set the checkout cookie when the user clicks the paid CTA.
        document.addEventListener('DOMContentLoaded', function() {
            const paidCta = document.querySelector('a[href*="login.php?paid=1&price=36.58"]');
            if (paidCta) {
                paidCta.addEventListener('click', function () {
                    try {
                        const price = '36.58';
                        const expires = new Date(Date.now() + (24 * 60 * 60 * 1000)); // 1 day
                        document.cookie = `checkout_price=${price}; path=/; expires=${expires.toUTCString()}`;
                        // Also set a short-lived flag to indicate this came from pricing CTA
                        const flagExpires = new Date(Date.now() + (60 * 60 * 1000)); // 1 hour
                        document.cookie = `checkout_from_pricing=1; path=/; expires=${flagExpires.toUTCString()}`;
                    } catch (e) {
                        // fail silently – server will also set cookie from URL param
                    }
                });
            }
        });
    </script>



</body>
</html>