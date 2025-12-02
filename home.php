<?php
    // Load environment variables
    require_once __DIR__ . '/env_loader.php';

    // Get reCAPTCHA site key from environment
    $recaptchaSiteKey = EnvLoader::get('RECAPTCHA_SITE_KEY', 'your_recaptcha_site_key_here');

    // Get current users count from mt5_details table
    require_once 'database.php';
    try {
        $pdo = getPDO();
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM mt5_details");
        $result = $stmt->fetch();
        $current_users = $result['count'];
    } catch (Exception $e) {
        $current_users = 100; // fallback to hardcoded value
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Funding4x – Get Funded to Trade Forex | Prop Firm</title>
    <meta name="description" content="Funding4x offers funded trading accounts. Join our trial, pass the evaluation, and trade company capital with our forex prop firm.">
    <meta name="keywords" content="funded trading account, forex prop firm, prop firm trial, forex funded account, prop firm challenge, trade company capital">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon-16x16.png">
    <link rel="manifest" href="assets/site.webmanifest">

    <!-- Load Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Configure Tailwind for Inter font and custom colors -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        'primary-indigo': '#4f46e5', // A vibrant flat color
                        'success-green': '#10b981', // A bright flat green for profit/success
                        'fomo-red': '#ef4444', // A striking red for urgency
                        'bg-light': '#f9fafb', // Very light background
                        'primary-accent': '#f97316', // Tailwind orange-500 for consistency
                        'primary-purple': '#4f009d', // Deep Royal Purple
                        'secondary-purple': '#7b2cbf', // Lighter accent purple
                        'trophy-gold': '#b49852', // Classic, muted Gold
                        'header-dark': '#240046', // Dark background
                        'bg-light': '#f3f4f6',
                        'cta-hover': '#9d7c49',
                    }
                }
            }
        }
    </script>
    <style>
        /* Custom styles for modern flat look and responsiveness */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f9fafb;
        }
        .header-bg {
            background-color: #1f2937; /* Dark flat header */
        }
        .fomo-glow {
            box-shadow: 0 0 15px rgba(239, 68, 68, 0.7);
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #f97316;
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
    </style>
</head>

<body>

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

    <!-- Hero Section -->
    <section class="py-16 sm:py-24 bg-bg-light">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            
            <div style="display: flex; justify-content: center;"><img src="assets/logo.png" alt="Funding4X Logo" ></div>
            
            <!-- Headline: Energetic & FOMO -->
            <h2 class="text-4xl sm:text-6xl font-extrabold tracking-tighter text-gray-900 leading-tight mb-4">
                <span class="block text-success-green">STOP WAITING. START TRADING.</span>
                Get <span class="text-primary-indigo">$5,000 Funded</span> & Keep 50% of the Profit!
            </h2>
            <p class="mt-4 text-xl text-gray-600 max-w-3xl mx-auto">
                Prove your skills through our transparent 3-stage evaluation and unlock serious capital. No tricks, just limits designed for success.
            </p>

            <!-- KEY HIGHLIGHTS SECTION (New - Above the Fold) -->
            <div class="mt-8 max-w-4xl mx-auto grid grid-cols-1 sm:grid-cols-3 gap-6">
                <!-- Highlight 1: Free Retry -->
                <div class="p-4 bg-white rounded-xl shadow-lg border-b-4 border-success-green text-center">
                    <span class="text-3xl font-extrabold text-success-green block mb-1">FREE RETRY!</span>
                    <p class="text-sm font-medium text-gray-600">Fail Phase 1? Try again instantly, no extra cost.</p>
                </div>
                <!-- Highlight 2: Profit Split -->
                <div class="p-4 bg-white rounded-xl shadow-lg border-b-4 border-primary-indigo text-center">
                    <span class="text-3xl font-extrabold text-primary-indigo block mb-1">50% Profit Share</span>
                    <p class="text-sm font-medium text-gray-600">Keep half of all your gains from day one.</p>
                </div>
                <!-- Highlight 3: No Time Limit -->
                <div class="p-4 bg-white rounded-xl shadow-lg border-b-4 border-fomo-red text-center">
                    <span class="text-3xl font-extrabold text-fomo-red block mb-1">No Time Limits</span>
                    <p class="text-sm font-medium text-gray-600">Trade at your pace. Consistency over speed.</p>
                </div>
            </div>
            <!-- END KEY HIGHLIGHTS SECTION -->

            <!-- FOMO Counter, Countdown, & Progress Bar -->
            <div class="mt-10 p-6 bg-white border-2 border-fomo-red rounded-xl shadow-2xl max-w-lg mx-auto fomo-glow">
                <p class="text-2xl font-bold text-fomo-red uppercase tracking-widest animate-pulse">
                    <span id="fomo-count">380</span> / 200 SPOTS TAKEN!
                </p>
                <p class="text-sm text-gray-500 mt-1 mb-3 font-semibold">
                    <span class="text-lg font-extrabold text-success-green">FREE ACCESS ENDING IN:</span>
                </p>

                <!-- Countdown Timer Display -->
                <div id="countdown" class="flex justify-center space-x-4 mt-3 mb-4">
                    <!-- Timer segments will be inserted here by JavaScript -->
                    <div class="bg-fomo-red text-white p-3 rounded-lg shadow-lg">
                        <span id="days" class="text-3xl font-extrabold block">00</span>
                        <span class="text-xs font-medium uppercase">Days</span>
                    </div>
                    <div class="bg-fomo-red text-white p-3 rounded-lg shadow-lg">
                        <span id="hours" class="text-3xl font-extrabold block">00</span>
                        <span class="text-xs font-medium uppercase">Hours</span>
                    </div>
                    <div class="bg-fomo-red text-white p-3 rounded-lg shadow-lg">
                        <span id="minutes" class="text-3xl font-extrabold block">00</span>
                        <span class="text-xs font-medium uppercase">Minutes</span>
                    </div>
                    <div class="bg-fomo-red text-white p-3 rounded-lg shadow-lg">
                        <span id="seconds" class="text-3xl font-extrabold block">00</span>
                        <span class="text-xs font-medium uppercase">Seconds</span>
                    </div>
                </div>

                <div class="w-full bg-gray-200 rounded-full h-3.5">
                    <div id="progress-bar" class="bg-fomo-red h-3.5 rounded-full transition-all duration-1000" style="width: 38%;"></div>
                </div>
                <p class="text-sm text-gray-500 mt-3 font-semibold">
                     (The price goes up to $59 after the timer runs out or spots are claimed!)
                </p>
            </div>

            <!-- Primary CTA -->
            <div id="cta" class="mt-12">
                <button onclick="window.location.href='pricing.php'"
                        class="px-12 py-4 text-2xl font-bold text-white bg-fomo-red rounded-xl shadow-lg hover:bg-red-600 transition duration-300 transform hover:scale-105 active:scale-95 uppercase tracking-wider border-b-4 border-red-700">
                    Start My Challenge Now!
                </button>
                <!--<p class="mt-3 text-sm text-gray-500 font-medium">100% Free for the next <span class="text-fomo-red">620 traders!</span></p>-->
            </div>
        </div>
    </section>

    <!-- Video & Transparency Section -->
    <section class="py-16 sm:py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid md:grid-cols-2 gap-12 items-center">
            
            <!-- Video Placeholder -->
            <div class="aspect-video bg-gray-200 rounded-xl shadow-2xl overflow-hidden">
                <div class="flex items-center justify-center h-full">
                    <svg class="w-16 h-16 text-gray-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path></svg>
                    <p class="ml-4 text-gray-500 font-semibold">Video Coming Soon</p>
                </div>
            </div>

            <!-- Value Proposition -->
            <div class="text-left">
                <h3 class="text-3xl font-bold text-gray-900 mb-4">
                    Why Traders Choose <span class="text-primary-indigo">FUNDING<span class="text-trophy-gold">4X</span></span>
                </h3>
                <p class="text-lg text-gray-600 mb-6">
                    Watch this short message from our founder explaining why we believe in your success. We are not looking for complex rules to trip you up—we are looking for <span class="font-extrabold text-success-green">consistent risk management</span>.
                </p>
                <ul class="space-y-3">
                    <li class="flex items-start">
                        <svg class="flex-shrink-0 w-6 h-6 text-success-green mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.108a8 8 0 11-15.116 0 8 8 0 0115.116 0z"></path></svg>
                        <span class="ml-3 text-gray-700 font-medium">Zero Hidden Fees or Tricky Rules.</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="flex-shrink-0 w-6 h-6 text-success-green mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.108a8 8 0 11-15.116 0 8 8 0 0115.116 0z"></path></svg>
                        <span class="ml-3 text-gray-700 font-medium">Clear, defined trading limits (Maximum Drawdown).</span>
                    </li>
                    <li class="flex items-start">
                        <svg class="flex-shrink-0 w-6 h-6 text-success-green mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.108a8 8 0 11-15.116 0 8 8 0 0115.116 0z"></path></svg>
                        <span class="ml-3 text-gray-700 font-medium">A dedicated path to becoming a professional, funded trader.</span>
                    </li>
                </ul>
            </div>
        </div>
    </section>

    <!-- How It Works Section (3 Stages) -->
    <section class="py-16 sm:py-24 bg-bg-light">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-4xl font-extrabold text-center text-gray-900 mb-16">
                The 3-Stage Path to a <span class="text-primary-indigo">Funded Account</span>
            </h2>

            <div class="grid md:grid-cols-3 gap-8">
                
                <!-- Stage 1 -->
                <div class="bg-white p-8 rounded-xl shadow-xl transition duration-500 hover:shadow-2xl hover:scale-[1.02] border-t-4 border-success-green">
                    <div class="text-4xl font-extrabold text-success-green mb-3">01</div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Phase 1: Skill Check</h3>
                    <p class="text-gray-600">
                        Prove your trading competency by hitting a modest profit target while strictly adhering to the risk management rules (drawdown limits). This is your practice round.
                    </p>
                    <p class="mt-4 text-sm font-semibold text-primary-indigo">Time Limit: None. Take your time!</p>
                </div>

                <!-- Stage 2 -->
                <div class="bg-white p-8 rounded-xl shadow-xl transition duration-500 hover:shadow-2xl hover:scale-[1.02] border-t-4 border-primary-indigo">
                    <div class="text-4xl font-extrabold text-primary-indigo mb-3">02</div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Phase 2: Consistency Test</h3>
                    <p class="text-gray-600">
                        Repeat your performance from Phase 1. We look for consistent, repeatable results under the same fair conditions. Show us you can manage risk day-in, day-out.
                    </p>
                    <p class="mt-4 text-sm font-semibold text-primary-indigo">Goal: Replicate Phase 1 success.</p>
                </div>

                <!-- Stage 3 (The Funded Part) -->
                <div class="bg-white p-8 rounded-xl shadow-xl transition duration-500 hover:shadow-2xl hover:scale-[1.02] border-t-4 border-fomo-red">
                    <div class="text-4xl font-extrabold text-fomo-red mb-3">03</div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-3">Phase 3: The Funding!</h3>
                    <p class="text-gray-600 font-semibold">
                        <span class="text-fomo-red">CONGRATULATIONS!</span> You are now trading with a real $5,000 funded account. Generate profit and we split it 50:50. Your success is our success.
                    </p>
                    <p class="mt-4 text-sm font-semibold text-primary-indigo">Reward: $5,000 Capital & 50% Profit Share.</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Testimonial Section -->
    <section id="trader-testimonials" class="py-16 sm:py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-4xl font-extrabold text-center text-gray-900 mb-12">
                Real Traders. Real Success.
            </h2>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Testimonial 1 -->
                <div class="bg-bg-light p-6 rounded-xl shadow-lg border-t-4 border-primary-indigo">
                    <p class="italic text-gray-700 mb-4">
                        "The three-stage system made me focus purely on risk management. I passed Phase 3 in just 6 weeks and got my first profit split this month. This fund is the real deal."
                    </p>
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-primary-indigo flex items-center justify-center text-white font-bold text-lg mr-3">J</div>
                        <div>
                            <p class="font-bold text-gray-900">Jake S.</p>
                            <p class="text-sm text-gray-500">Funded Trader</p>
                        </div>
                    </div>
                </div>

                <!-- Testimonial 2 -->
                <div class="bg-bg-light p-6 rounded-xl shadow-lg border-t-4 border-success-green">
                    <p class="italic text-gray-700 mb-4">
                        "I love the transparency. No hidden rules. They actually want you to succeed. The free access offer was the perfect kickstart I needed to test my strategy."
                    </p>
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-success-green flex items-center justify-center text-white font-bold text-lg mr-3">A</div>
                        <div>
                            <p class="font-bold text-gray-900">Anna R.</p>
                            <p class="text-sm text-gray-500">Passed Phase 2</p>
                        </div>
                    </div>
                </div>

                <!-- Testimonial 3 -->
                <div class="bg-bg-light p-6 rounded-xl shadow-lg border-t-4 border-fomo-red">
                    <p class="italic text-gray-700 mb-4">
                        "The 50/50 profit split is incredibly motivating. It aligns our goals perfectly. Finally, a prop firm focused on long-term partnership, not tricky challenge hurdles."
                    </p>
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full bg-fomo-red flex items-center justify-center text-white font-bold text-lg mr-3">T</div>
                        <div>
                            <p class="font-bold text-gray-900">Tom W.</p>
                            <p class="text-sm text-gray-500">Earning Profit Splits</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Trading Tips Guide Section -->
    <section id="trading-tips" class="py-16 sm:py-24 bg-bg-light">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-4xl font-extrabold text-center text-gray-900 mb-12">
                Quick Start: Your Top 3 Success Tips
            </h2>

            <div class="grid md:grid-cols-3 gap-8">
                
                <!-- Tip 1 -->
                <div class="bg-white p-6 rounded-xl shadow-lg hover:shadow-xl transition duration-300 border-l-4 border-success-green">
                    <h3 class="text-xl font-bold text-gray-900 mb-3 flex items-center"><span class="text-success-green text-3xl mr-2">1.</span> Master Your Drawdown</h3>
                    <p class="text-gray-600">
                        The drawdown limit is the single most important rule. Treat it as a maximum loss, not a target. Smaller position sizes mean higher success rates in the long run.
                    </p>
                </div>

                <!-- Tip 2 -->
                <div class="bg-white p-6 rounded-xl shadow-lg hover:shadow-xl transition duration-300 border-l-4 border-primary-indigo">
                    <h3 class="text-xl font-bold text-gray-900 mb-3 flex items-center"><span class="text-primary-indigo text-3xl mr-2">2.</span> Consistency Over Volume</h3>
                    <p class="text-gray-600">
                        Our goal is to find consistent traders. Don't overtrade just to hit the profit target faster. Focus on quality setups and maintain steady performance across both phases.
                    </p>
                </div>

                <!-- Tip 3 -->
                <div class="bg-white p-6 rounded-xl shadow-lg hover:shadow-xl transition duration-300 border-l-4 border-fomo-red">
                    <h3 class="text-xl font-bold text-gray-900 mb-3 flex items-center"><span class="text-fomo-red text-3xl mr-2">3.</span> Take Mental Breaks</h3>
                    <p class="text-gray-600">
                        Trading psychology is key. If you have a loss, step away. The challenge has no time limit for Phase 1 or 2, so take a day off to reset your mindset and come back sharper.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Profit Split Callout -->
    <section class="py-16 bg-primary-indigo">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-white">
            <p class="text-4xl sm:text-5xl font-extrabold tracking-tight">
                Your Share: <span class="text-success-green">50%</span>
            </p>
            <p class="mt-4 text-xl font-medium">
                We believe in fair compensation. Every dollar you earn above the initial funding is split equally.
                <span class="font-bold block mt-2">No maintenance fees. No withdrawal hassle.</span>
            </p>
        </div>
    </section>

    <!-- Final CTA Section (Repeating FOMO) -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
             <div class="p-8 bg-bg-light rounded-2xl shadow-inner border border-gray-200">
                <h3 class="text-3xl sm:text-4xl font-extrabold text-gray-900 mb-6">
                    Don't Miss Out on FREE ACCESS!
                </h3>
                <p class="text-xl text-gray-600 mb-8">
                    The next <span class="text-fomo-red font-extrabold"><?php echo 200 - (int)$current_users; ?> traders</span> get to prove their skills completely FREE. Secure your place before the counter hits 1,000!
                </p>
                <button onclick="window.location.href='pricing.php'"
                        class="px-12 py-4 text-2xl font-bold text-white bg-success-green rounded-xl shadow-lg hover:bg-emerald-600 transition duration-300 transform hover:scale-105 active:scale-95 uppercase tracking-wider border-b-4 border-emerald-700">
                    Get Your Trading Challenge Now
                </button>
             </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-header-dark text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col sm:flex-row justify-between items-center text-center sm:text-left">
            <p class="text-sm">Funding4x &copy; 2024</p>
            <div class="flex space-x-4 mt-4 sm:mt-0">
                <a href="pages/privacy-policy.php" class="text-gray-400 hover:text-trophy-gold transition duration-150">Privacy Policy</a>
                <a href="pages/term-conditions.php" class="text-gray-400 hover:text-trophy-gold transition duration-150">Terms of Service</a>
            </div>
        </div>
    </footer>


    <!-- JavaScript for FOMO Counter, Countdown Timer, and Comprehensive Form Handling -->
    <script>
        // Data for the counter (dynamic current users out of 1000 free slots)
        const CURRENT_USERS = <?php echo $current_users; ?>;
        const FREE_CAP = 200;
        
        const startDate = new Date('2025-11-31T00:00:00').getTime();

        // Add 30 days to it (constant for everyone)
        const expirationTime = startDate + (30 * 24 * 60 * 60 * 1000);
        // In a real application, this time would be fetched from a server
        // const expirationTime = new Date().getTime() + (48 * 60 * 60 * 1000); 

        // Function to update the countdown timer
        function updateCountdown() {
            const now = new Date().getTime();
            const distance = expirationTime - now;

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            // Format numbers to always be two digits
            const pad = (num) => num.toString().padStart(2, '0');

            document.getElementById('days').textContent = pad(days);
            document.getElementById('hours').textContent = pad(hours);
            document.getElementById('minutes').textContent = pad(minutes);
            document.getElementById('seconds').textContent = pad(seconds);

            if (distance < 0) {
                clearInterval(countdownInterval);
                document.getElementById('countdown').innerHTML = '<span class="text-3xl font-extrabold text-fomo-red">PROMOTION ENDED!</span>';
                // In a real app, you'd disable the CTA here and change the price text.
            }
        }

        let countdownInterval;

        document.addEventListener('DOMContentLoaded', () => {
            const countElement = document.getElementById('fomo-count');
            const progressBar = document.getElementById('progress-bar');
            
            // Calculate progress percentage
            const percentage = (CURRENT_USERS / FREE_CAP) * 100;

            // Set the visual elements
            if (countElement) {
                 // Set the initial count display
                 countElement.textContent = CURRENT_USERS;
            }
            if (progressBar) {
                // Animate the progress bar width
                progressBar.style.width = percentage + '%';
            }

            // Start the countdown timer
            updateCountdown(); // Initial call to display immediately
            countdownInterval = setInterval(updateCountdown, 1000);
        });
    </script>
</body>
</html>
