<?php
    // Load environment variables
    require_once __DIR__ . '/env_loader.php';

    // Get reCAPTCHA site key from environment
    $recaptchaSiteKey = EnvLoader::get('RECAPTCHA_SITE_KEY', 'your_recaptcha_site_key_here');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Funded Trader Challenge - Claim Your FREE Spot Now!</title>
    <!-- Load Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
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
    <header class="header-bg text-white shadow-xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
            <h1 class="text-2xl font-extrabold tracking-tight text-success-green">PROP<span class="text-white">FUND</span></h1>
            <nav class="hidden sm:flex items-center space-x-4">
                <a href="rule.php" class="text-white hover:text-success-green font-semibold py-2 px-4 rounded-lg transition duration-300">
                    Rule
                </a>
                <button onclick="document.getElementById('modal').classList.remove('hidden')" class="bg-primary-indigo hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-300 shadow-md">
                    Start Trading
                </button>
            </nav>
            <!-- Mobile Menu -->
            <div class="sm:hidden flex items-center space-x-2">
                <a href="rule.php" class="text-white hover:text-success-green font-semibold py-1 px-2 text-sm rounded transition duration-300">
                    Rule
                </a>
                <button onclick="document.getElementById('modal').classList.remove('hidden')" class="bg-primary-indigo hover:bg-indigo-700 text-white font-semibold py-1 px-3 text-sm rounded transition duration-300 shadow-md">
                    Start Trading
                </button>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="py-16 sm:py-24 bg-bg-light">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            
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
                    <span id="fomo-count">380</span> / 1000 SPOTS TAKEN!
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
                     (The price goes up to $47 after the timer runs out or spots are claimed!)
                </p>
            </div>

            <!-- Primary CTA -->
            <div id="cta" class="mt-12">
                <button onclick="document.getElementById('modal').classList.remove('hidden')" 
                        class="px-12 py-4 text-2xl font-bold text-white bg-fomo-red rounded-xl shadow-lg hover:bg-red-600 transition duration-300 transform hover:scale-105 active:scale-95 uppercase tracking-wider border-b-4 border-red-700">
                    Claim Your Free Spot Now
                </button>
                <p class="mt-3 text-sm text-gray-500 font-medium">100% Free for the next <span class="text-fomo-red">620 traders!</span></p>
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
                    <p class="ml-4 text-gray-500 font-semibold">Your CEO Explainer Video Placeholder</p>
                </div>
            </div>

            <!-- Value Proposition -->
            <div class="text-left">
                <h3 class="text-3xl font-bold text-gray-900 mb-4">
                    Why Traders Choose <span class="text-primary-indigo">PROP<span class="text-success-green">FUND</span></span>
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
                    The next <span class="text-fomo-red font-extrabold">620 traders</span> get to prove their skills completely FREE. Secure your place before the counter hits 1,000!
                </p>
                <button onclick="document.getElementById('modal').classList.remove('hidden')" 
                        class="px-12 py-4 text-2xl font-bold text-white bg-success-green rounded-xl shadow-lg hover:bg-emerald-600 transition duration-300 transform hover:scale-105 active:scale-95 uppercase tracking-wider border-b-4 border-emerald-700">
                    Join the Free Challenge Now
                </button>
             </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 text-center">
            <p class="text-sm">&copy; 2024 PROP FUND Trading. All rights reserved. | <a href="#" class="hover:text-success-green transition">Terms of Service</a> | <a href="#" class="hover:text-success-green transition">Risk Disclosure</a></p>
            <p class="mt-2 text-xs text-gray-500">Trading involves significant risk. Past performance is not indicative of future results.</p>
        </div>
    </footer>

    <!-- Simple Modal for Sign-up (Contact Form) -->
    <div id="modal" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center p-4 hidden z-50">
        <div class="bg-white p-8 rounded-xl shadow-2xl max-w-md w-full relative">
            <h3 class="text-2xl font-bold text-primary-indigo mb-4">Secure Your Free Spot!</h3>
            <p class="text-gray-700 mb-6">Enter your details below to instantly secure your free challenge access and receive the Phase 1 instructions.</p>
            
            <form id="waitlist-form-modal" onsubmit="event.preventDefault();">
                <!-- Name Field -->
                <input type="text" id="modal-name" name="name" placeholder="Enter your Name" required
                    class="w-full mb-2 p-4 text-gray-900 bg-gray-100 border border-gray-300 rounded-lg focus:ring-primary-indigo focus:border-primary-indigo transition duration-200 placeholder-gray-500 text-lg">
                
                <!-- Email Field -->
                <input type="email" id="modal-email" name="email" placeholder="Enter your email address" required
                    class="w-full mb-2 p-4 text-gray-900 bg-gray-100 border border-gray-300 rounded-lg focus:ring-primary-indigo focus:border-primary-indigo transition duration-200 placeholder-gray-500 text-lg">
                
                <!-- Country selector -->
                <select name="country" id="modal-country-select" required
                    class="w-full mb-2 p-4 text-gray-900 bg-gray-100 border border-gray-300 rounded-lg focus:ring-primary-indigo focus:border-primary-indigo transition duration-200 text-lg">
                    <option value="">Select your Country</option>
                </select>
                
                <!-- reCAPTCHA -->
                <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($recaptchaSiteKey); ?>"></div>

                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="document.getElementById('modal').classList.add('hidden')" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary-indigo text-white rounded-lg hover:bg-indigo-700 transition">Join Waitlist Now!</button>
                </div>
            </form>

            <!-- Referral Dashboard Link -->
            <div class="mt-6">
                <a href="referral_dashboard.php"
                   id="dashboard-link-modal"
                   class="w-full block bg-gray-100 hover:bg-gray-200 text-gray-900 font-bold py-3 px-6 rounded-lg text-lg uppercase tracking-wider shadow-lg transition duration-300 ease-in-out transform hover:scale-105 active:scale-95 text-center">
                    View Referral Dashboard
                </a>
                <p class="text-xs text-gray-500 mt-2 text-center">
                    Already joined? Check your referral status and earnings here
                </p>
            </div>
        </div>
    </div>

    <!-- JavaScript for FOMO Counter, Countdown Timer, and Comprehensive Form Handling -->
    <script>
        // Data for the counter (380 current users out of 1000 free slots)
        const CURRENT_USERS = 380;
        const FREE_CAP = 1000;
        
        // Target expiration time: 48 hours from script load
        // In a real application, this time would be fetched from a server
        const expirationTime = new Date().getTime() + (48 * 60 * 60 * 1000); 

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

            // Initialize modal form functionality
            initializeModalForm();
        });

        // Initialize modal form functionality
        function initializeModalForm() {
            const waitlistForm = document.getElementById('waitlist-form-modal');
            const countrySelect = document.getElementById('modal-country-select');

            // Detect referral code from URL parameter
            const urlParams = new URLSearchParams(window.location.search);
            const referralCode = urlParams.get('ref');
            
            if (referralCode) {
                // Store referral code in session storage for form submission
                sessionStorage.setItem('referral_code', referralCode);
                console.log('Referred by code:', referralCode);
            }

            // Load all countries into dropdown
            const countries = [
                "Afghanistan","Albania","Algeria","Andorra","Angola","Argentina","Armenia","Australia","Austria",
                "Azerbaijan","Bahamas","Bahrain","Bangladesh","Barbados","Belarus","Belgium","Belize","Benin",
                "Bhutan","Bolivia","Bosnia and Herzegovina","Botswana","Brazil","Brunei","Bulgaria","Burkina Faso",
                "Burundi","Cambodia","Cameroon","Canada","Chile","China","Colombia","Comoros","Congo",
                "Costa Rica","Croatia","Cuba","Cyprus","Czech Republic","Denmark","Dominican Republic",
                "Ecuador","Egypt","El Salvador","Estonia","Ethiopia","Fiji","Finland","France","Gabon","Gambia",
                "Georgia","Germany","Ghana","Greece","Guatemala","Honduras","Hong Kong","Hungary","Iceland",
                "India","Indonesia","Iran","Iraq","Ireland","Israel","Italy","Jamaica","Japan","Jordan","Kazakhstan",
                "Kenya","Kuwait","Kyrgyzstan","Laos","Latvia","Lebanon","Lesotho","Liberia","Libya","Lithuania",
                "Luxembourg","Madagascar","Malawi","Malaysia","Maldives","Mali","Malta","Mauritius","Mexico",
                "Moldova","Monaco","Mongolia","Montenegro","Morocco","Mozambique","Myanmar","Namibia","Nepal",
                "Netherlands","New Zealand","Nicaragua","Niger","Nigeria","North Korea","Norway","Oman","Pakistan",
                "Palestine","Panama","Paraguay","Peru","Philippines","Poland","Portugal","Qatar","Romania",
                "Russia","Rwanda","Saudi Arabia","Senegal","Serbia","Singapore","Slovakia","Slovenia","South Africa",
                "South Korea","Spain","Sri Lanka","Sudan","Sweden","Switzerland","Syria","Taiwan","Tanzania",
                "Thailand","Togo","Trinidad and Tobago","Tunisia","Turkey","Uganda","Ukraine","United Arab Emirates",
                "United Kingdom","United States","Uruguay","Uzbekistan","Venezuela","Vietnam","Yemen","Zambia","Zimbabwe"
            ];

            // Add default "Select your Country" placeholder FIRST
            const defaultOption = document.createElement('option');
            defaultOption.value = "";
            defaultOption.textContent = "Select your Country";
            defaultOption.disabled = true;
            defaultOption.selected = true;
            defaultOption.hidden = true;
            countrySelect.appendChild(defaultOption);

            // Then add all countries
            countries.forEach(country => {
                const option = document.createElement('option');
                option.value = country;
                option.textContent = country;
                countrySelect.appendChild(option);
            });

            // Handle form submission
            waitlistForm.addEventListener('submit', async function (e) {
                e.preventDefault();

                const recaptchaResponse = grecaptcha.getResponse(); // Get reCAPTCHA token
                if (!recaptchaResponse) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Please Verify',
                        text: 'Please complete the reCAPTCHA to continue.',
                        confirmButtonColor: '#f97316'
                    });
                    return;
                }
                
                const name = waitlistForm.querySelector('input[name="name"]').value.trim();
                const email = waitlistForm.querySelector('input[name="email"]').value.trim();
                const country = countrySelect.value;
                const referralCode = sessionStorage.getItem('referral_code') || '';

                if (!name || !email || !country) {
                    // Hide any existing loader first
                    hideEmailVerificationLoader();
                    ensureNoLoaderRemains();
                    
                    Swal.fire({
                        icon: 'warning',
                        title: 'Missing Information',
                        text: 'Please fill in your name, email, and country.',
                        confirmButtonColor: '#f97316',
                        didClose: () => {
                            // Ensure no loader remains after alert is closed
                            hideEmailVerificationLoader();
                            ensureNoLoaderRemains();
                        }
                    });
                    return;
                }

                try {
                    // Show loader
                    showEmailVerificationLoader();

                    const requestData = {
                        name,
                        email,
                        country,
                        recaptcha: recaptchaResponse
                    };
                    if (referralCode) {
                        requestData.ref = referralCode;
                    }

                    const response = await fetch('save_waitlist.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(requestData)
                    });

                    const result = await response.json();

                    // Hide loader immediately and ensure complete cleanup
                    hideEmailVerificationLoader();
                    ensureNoLoaderRemains();

                    if (result.status === 'success') {
                        // Store referral information for dashboard access
                        if (result.referral_code) {
                            sessionStorage.setItem('user_referral_code', result.referral_code);
                            sessionStorage.setItem('referral_link', result.referral_link);
                        }
                        
                        // Show email verification message and close modal
                        Swal.fire({
                            icon: 'success',
                            title: '🎉 Welcome to the Program!',
                            html: `
                                <div class="text-left">
                                    <p class="mb-3">You've been successfully registered!</p>
                                    <div class="bg-blue-100 border border-blue-300 rounded-lg p-3 mb-3">
                                        <h4 class="font-bold text-blue-800 mb-2">📧 Next Step: Verify Your Email</h4>
                                        <p class="text-blue-700 text-sm">
                                            We've sent a verification link to <strong>${email}</strong>.
                                            Please check your inbox (and spam folder) and click the link to activate your account.
                                        </p>
                                    </div>
                                    <p class="text-sm text-gray-600">
                                        Once verified, you'll be redirected to your exclusive dashboard.
                                    </p>
                                </div>
                            `,
                            confirmButtonColor: '#f97316',
                            confirmButtonText: 'Got it!',
                            width: '500px',
                            showConfirmButton: true,
                            allowOutsideClick: false,
                            didClose: () => {
                                // Close modal and ensure no loader remains
                                document.getElementById('modal').classList.add('hidden');
                                hideEmailVerificationLoader();
                                ensureNoLoaderRemains();
                                console.log('User confirmed email verification message');
                            }
                        });

                    } else if (result.status === 'existing_user') {
                        // User exists and is verified - redirect to dashboard
                        hideEmailVerificationLoader();
                        ensureNoLoaderRemains();
                        window.location.href = 'referral_dashboard.php?user=' + encodeURIComponent(result.referral_code);
                        
                    } else if (result.status === 'email_not_verified') {
                        // User exists but hasn't verified email
                        Swal.fire({
                            icon: 'warning',
                            title: 'Email Verification Required',
                            html: `
                                <div class="text-left">
                                    <p class="mb-3">You're already registered, but need to verify your email address first.</p>
                                    <div class="bg-orange-100 border border-orange-300 rounded-lg p-3 mb-3">
                                        <h4 class="font-bold text-orange-800 mb-2">📧 Check Your Email</h4>
                                        <p class="text-orange-700 text-sm">
                                            Please check your email inbox (and spam folder) for a verification link.
                                            Click the link to activate your account and access the referral dashboard.
                                        </p>
                                    </div>
                                    <p class="text-sm text-gray-600">
                                        Didn't receive an email? Contact our support team.
                                    </p>
                                </div>
                            `,
                            confirmButtonColor: '#f97316',
                            confirmButtonText: 'Check Email',
                            width: '500px',
                            showConfirmButton: true,
                            allowOutsideClick: false,
                            didClose: () => {
                                // Close modal and reset form
                                document.getElementById('modal').classList.add('hidden');
                                resetFormToOriginalState();
                                
                                // Additional cleanup
                                setTimeout(() => {
                                    const swalElements = document.querySelectorAll('.swal2-container');
                                    swalElements.forEach(el => el.remove());
                                    
                                    const customBackdrop = document.getElementById('custom-alert-backdrop');
                                    if (customBackdrop) {
                                        customBackdrop.remove();
                                    }
                                    
                                    ensureNoLoaderRemains();
                                }, 100);
                            }
                        });
                        
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops!',
                            text: result.message || 'Something went wrong. Please try again.',
                            confirmButtonColor: '#f97316',
                            showConfirmButton: true,
                            allowOutsideClick: false,
                            didClose: () => {
                                ensureNoLoaderRemains();
                            }
                        });
                    }

                } catch (error) {
                    // Hide loader in case of network errors
                    hideEmailVerificationLoader();
                    ensureNoLoaderRemains();
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Connection Error',
                        text: 'We couldn\'t submit your request. Please check your connection and try again.',
                        confirmButtonColor: '#f97316',
                        didClose: () => {
                            ensureNoLoaderRemains();
                        }
                    });
                }
            });
        }

        // Email verification loader
        function showEmailVerificationLoader() {
            const loaderHtml = `
                <div id="email-verification-loader" class="fixed inset-0 loader-overlay z-50 flex items-center justify-center">
                    <div class="bg-gray-800 p-8 rounded-2xl shadow-2xl border-t-8 border-primary-accent text-center max-w-md">
                        <div class="spinner mb-4"></div>
                        <h3 class="text-xl font-bold text-white mb-2">Creating Your Account...</h3>
                        <p class="text-gray-300 mb-4">Please wait while we set up your exclusive access and send verification email.</p>
                        <div class="flex items-center justify-center space-x-2 text-sm text-gray-400">
                            <div class="w-2 h-2 bg-primary-accent rounded-full animate-pulse"></div>
                            <div class="w-2 h-2 bg-primary-accent rounded-full animate-pulse" style="animation-delay: 0.2s;"></div>
                            <div class="w-2 h-2 bg-primary-accent rounded-full animate-pulse" style="animation-delay: 0.4s;"></div>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', loaderHtml);
        }

        function hideEmailVerificationLoader() {
            const loader = document.getElementById('email-verification-loader');
            if (loader) {
                loader.remove();
            }
        }

        function ensureNoLoaderRemains() {
            // Remove all possible loader elements
            const loaderSelectors = [
                '#email-verification-loader',
                '.loader-overlay',
                '[id*="email-verification"]',
                '[class*="loader"]'
            ];
            
            loaderSelectors.forEach(selector => {
                const elements = document.querySelectorAll(selector);
                elements.forEach(element => {
                    if (element && element.id !== 'email-verification-loader') {
                        element.remove();
                    }
                });
            });
            
            // Also check for any div with loader classes
            const allLoaders = document.querySelectorAll('.spinner');
            allLoaders.forEach(spinner => {
                const parent = spinner.closest('.fixed, .absolute');
                if (parent && parent.id !== 'email-verification-loader') {
                    // Remove the parent container if it looks like a loader overlay
                    const parentClasses = parent.className;
                    if (parentClasses.includes('loader-overlay') || parentClasses.includes('flex items-center justify-center')) {
                        parent.remove();
                    }
                }
            });
            
            console.log('Loader cleanup completed');
        }

        function resetFormToOriginalState() {
            const waitlistForm = document.getElementById('waitlist-form-modal');
            const countrySelect = document.getElementById('modal-country-select');
            
            // Reset form inputs
            if (waitlistForm) {
                waitlistForm.querySelector('input[name="name"]').value = '';
                waitlistForm.querySelector('input[name="email"]').value = '';
            }
            if (countrySelect) {
                countrySelect.selectedIndex = 0;
            }
            
            // Reset country dropdown to default state
            if (countrySelect) {
                const defaultOption = document.createElement('option');
                defaultOption.value = "";
                defaultOption.textContent = "Select your Country";
                defaultOption.disabled = true;
                defaultOption.selected = true;
                defaultOption.hidden = true;
                
                // Clear existing options and re-add default
                countrySelect.innerHTML = '';
                countrySelect.appendChild(defaultOption);
                
                // Re-populate countries
                const countries = [
                    "Afghanistan","Albania","Algeria","Andorra","Angola","Argentina","Armenia","Australia","Austria",
                    "Azerbaijan","Bahamas","Bahrain","Bangladesh","Barbados","Belarus","Belgium","Belize","Benin",
                    "Bhutan","Bolivia","Bosnia and Herzegovina","Botswana","Brazil","Brunei","Bulgaria","Burkina Faso",
                    "Burundi","Cambodia","Cameroon","Canada","Chile","China","Colombia","Comoros","Congo",
                    "Costa Rica","Croatia","Cuba","Cyprus","Czech Republic","Denmark","Dominican Republic",
                    "Ecuador","Egypt","El Salvador","Estonia","Ethiopia","Fiji","Finland","France","Gabon","Gambia",
                    "Georgia","Germany","Ghana","Greece","Guatemala","Honduras","Hong Kong","Hungary","Iceland",
                    "India","Indonesia","Iran","Iraq","Ireland","Israel","Italy","Jamaica","Japan","Jordan","Kazakhstan",
                    "Kenya","Kuwait","Kyrgyzstan","Laos","Latvia","Lebanon","Lesotho","Liberia","Libya","Lithuania",
                    "Luxembourg","Madagascar","Malawi","Malaysia","Maldives","Mali","Malta","Mauritius","Mexico",
                    "Moldova","Monaco","Mongolia","Montenegro","Morocco","Mozambique","Myanmar","Namibia","Nepal",
                    "Netherlands","New Zealand","Nicaragua","Niger","Nigeria","North Korea","Norway","Oman","Pakistan",
                    "Palestine","Panama","Paraguay","Peru","Philippines","Poland","Portugal","Qatar","Romania",
                    "Russia","Rwanda","Saudi Arabia","Senegal","Serbia","Singapore","Slovakia","Slovenia","South Africa",
                    "South Korea","Spain","Sri Lanka","Sudan","Sweden","Switzerland","Syria","Taiwan","Tanzania",
                    "Thailand","Togo","Trinidad and Tobago","Tunisia","Turkey","Uganda","Ukraine","United Arab Emirates",
                    "United Kingdom","United States","Uruguay","Uzbekistan","Venezuela","Vietnam","Yemen","Zambia","Zimbabwe"
                ];
                
                countries.forEach(country => {
                    const option = document.createElement('option');
                    option.value = country;
                    option.textContent = country;
                    countrySelect.appendChild(option);
                });
            }
            
            // Clear any remaining loader elements
            hideEmailVerificationLoader();
            
            // Clear session storage related to the form
            sessionStorage.removeItem('user_referral_code');
            sessionStorage.removeItem('referral_link');
            
            console.log('Modal form reset to original state');
        }
    </script>
</body>
</html>
