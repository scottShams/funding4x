<?php
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
        /* Custom feature list styling */
        .feature-list li {
            padding: 0.5rem 0;
            display: flex;
            align-items: center;
        }
        .feature-list svg {
            margin-right: 0.5rem;
        }
        /* Card timer style */
        .timer-card {
            background-color: #f04040; /* Dark background for visibility */
            color: #f7f6f4; /* Trophy Gold text */
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
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

    <!-- Main Content: Pricing Tables -->
    <main class="flex-grow flex flex-col items-center p-4 sm:p-8">
        <div class="w-full max-w-5xl">
            
            <h2 class="text-4xl sm:text-5xl font-extrabold text-header-dark text-center mb-4">
                Choose Your Path to a Funded Account
            </h2>
            <p class="text-lg text-gray-600 text-center mb-6">
                Start your evaluation today—either by referring others or by purchasing instant access to Phase 1.
            </p>

            <!-- GLOBAL PROMINENT TIMER SECTION (Card Style) -->
            <div id="timerContainerWrapper" class="flex justify-center mb-12 hidden">
                <div id="timerContainer" class="font-bold text-center w-full max-w-lg p-2 sm:p-4 transition-all duration-300 ease-in-out">
                    <p class="text-lg sm:text-xl uppercase tracking-widest mb-3 text-red-700 font-extrabold">LIMITED OFFER ENDS IN:</p>
                    
                    <!-- Timer Cards Structure -->
                    <div class="flex justify-center space-x-3 sm:space-x-4">
                        <!-- Days Card -->
                        <div class="timer-card flex-1 max-w-[120px]">
                            <div id="timerDays" class="text-5xl sm:text-6xl font-extrabold leading-none">00</div>
                            <div class="text-xs sm:text-sm font-semibold mt-1 text-white/80">DAYS</div>
                        </div>

                        <!-- Hours Card -->
                        <div class="timer-card flex-1 max-w-[120px]">
                            <div id="timerHours" class="text-5xl sm:text-6xl font-extrabold leading-none">00</div>
                            <div class="text-xs sm:text-sm font-semibold mt-1 text-white/80">HOURS</div>
                        </div>

                        <!-- Minutes Card -->
                        <div class="timer-card flex-1 max-w-[120px]">
                            <div id="timerMinutes" class="text-5xl sm:text-6xl font-extrabold leading-none">00</div>
                            <div class="text-xs sm:text-sm font-semibold mt-1 text-white/80">MINUTES</div>
                        </div>

                        <!-- Seconds Card -->
                        <div class="timer-card flex-1 max-w-[120px]">
                            <div id="timerSeconds" class="text-5xl sm:text-6xl font-extrabold leading-none">00</div>
                            <div class="text-xs sm:text-sm font-semibold mt-1 text-white/80">SECONDS</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pricing Grid (Instant Access is the first column) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                
                <!-- Card 1 (New Position): PAID Option (Instant Access) -->
                <div id="instantAccessCard" class="bg-card-white p-6 sm:p-8 rounded-xl shadow-2xl transition duration-500 border-4 border-trophy-gold transform scale-[1.03] lg:scale-100 lg:hover:scale-[1.05]">
                    
                    <!-- Badge/Highlight: Displays 38% OFF -->
                    <div class="text-center mb-4" id="discountBadgeContainer">
                        <span class="inline-block bg-success-green text-card-white text-lg font-bold px-4 py-1 rounded-full uppercase tracking-wider shadow-md animate-pulse">
                            LIMITED TIME: 38% OFF!
                        </span>
                    </div>
                    
                    <h3 class="text-3xl font-extrabold text-header-dark mb-4 text-center">
                        Instant Access
                    </h3>

                    <!-- Discounted Price Block -->
                    <div class="flex flex-col items-center mb-6">
                        <p class="text-lg font-medium text-red-700 line-through" id="originalPriceDisplay">
                            Normal Price: $59
                        </p>
                        <p class="text-6xl font-extrabold text-primary-purple leading-none" id="currentPriceDisplay">
                            $36<span class="text-3xl font-semibold">.58</span>
                        </p>
                        <p class="text-2xl text-success-green font-bold mt-2" id="saveAmountDisplay">
                            (Save $22.42 instantly!)
                        </p>
                    </div>
                    
                    <!-- Key Feature Highlight -->
                    <div class="bg-trophy-gold/20 p-4 rounded-lg mb-8 border border-trophy-gold" id="limitedOfferHighlight">
                        <p class="text-lg font-bold text-header-dark text-center">
                            INSTANT ADMISSION: <br/ >Start Phase 1 Immediately.
                        </p>
                        <p class="text-sm text-gray-600 text-center mt-1">
                            Your evaluation starts the moment your payment is processed.
                        </p>
                    </div>

                    <!-- Features -->
                    <ul class="feature-list text-gray-700 mb-8">
                        <li>
                            <svg class="w-5 h-5 text-success-green" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Full access to Phase 1 Trading Evaluation
                        </li>
                        <li>
                            <svg class="w-5 h-5 text-success-green" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            $5,000 Starting Account Balance
                        </li>
                        <li>
                            <svg class="w-5 h-5 text-success-green" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Free Retry
                        </li>
                        <li>
                            <svg class="w-5 h-5 text-success-green" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Priority Support
                        </li>
                    </ul>

                    <!-- CTA Button -->
                    <button id="ctaButton" class="w-full py-3 bg-primary-purple text-card-white font-extrabold text-lg rounded-lg shadow-lg hover:bg-secondary-purple transition duration-300"
                        onclick="showSignupModal()">
                        Get My Account Now!
                    </button>

                </div>


                <!-- Card 2 (New Position): FREE Option (Community Access) -->
                <div class="bg-card-white p-6 sm:p-8 rounded-xl shadow-lg transition duration-300 border border-border-light lg:hover:shadow-xl lg:hover:border-primary-purple">
                    
                    <h3 class="text-3xl font-extrabold text-primary-purple mb-4 text-center">
                        Community Access
                    </h3>
                    
                    <!-- Discounted Price Block -->
                    <div class="flex flex-col items-center mb-6">
                        
                        <p class="text-6xl font-extrabold text-primary-purple leading-none" id="currentPriceDisplay">
                            $0<span class="text-3xl font-semibold">.00</span>
                        </p>
                        
                    </div>
                    <p class="text-base text-gray-500 font-semibold mb-6 text-center">
                        Unlock Phase 1 by growing our community.
                    </p>

                    <!-- Key Feature Highlight -->
                    <div class="bg-primary-purple/10 p-4 rounded-lg mb-8 border border-primary-purple">
                        <p class="text-lg font-bold text-primary-purple text-center">
                            Requirement: Refer 5 Forex Traders
                        </p>
                        <p class="text-sm text-gray-600 text-center mt-1">
                            Your referred traders must successfully register to grant you a Free Trading Challenge.
                        </p>
                    </div>

                    <!-- Features -->
                    <ul class="feature-list text-gray-700 mb-8">
                        <li>
                            <svg class="w-5 h-5 text-success-green" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Full access to Phase 1 Trading Evaluation
                        </li>
                        <li>
                            <svg class="w-5 h-5 text-success-green" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            $5,000 Starting Account balance
                        </li>
                        <li class="opacity-70">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            No free Retry
                        </li>
                        <li class="opacity-70">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            No Instant Access (Requires Referrals)
                        </li>
                    </ul>

                    <!-- CTA Button -->
                    <button class="w-full py-3 bg-trophy-gold text-header-dark font-extrabold text-lg rounded-lg shadow-lg hover:bg-cta-hover transition duration-300"
                        onclick="showSignupModal()">
                        Sign Up Now
                    </button>
                    
                </div>

            </div>
            
            <p class="text-center text-sm text-gray-500 mt-8">*All evaluations are subject to Funding4x terms and conditions.</p>

        </div>
    </main>

    <!-- Footer (reusing the dark header style for visual consistency) -->
    <footer class="bg-header-dark text-white mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 text-center">
            <p class="text-sm">&copy; 2024 Funding4x. All rights reserved.</p>
        </div>
    </footer>

    <!-- Signup Modal -->
    <div id="signupModal" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center p-4 hidden z-50">
        <div class="bg-white p-8 rounded-xl shadow-2xl max-w-md w-full relative">
            <h3 class="text-2xl font-bold text-primary-purple mb-4">Join Funding4x</h3>
            <p class="text-gray-700 mb-6">Enter your details below to start your trading journey.</p>

            <form id="signupForm" onsubmit="event.preventDefault();">
                <!-- Name Field -->
                <input type="text" id="signup-name" name="name" placeholder="Enter your Name" required
                    class="w-full mb-2 p-4 text-gray-900 bg-gray-100 border border-gray-300 rounded-lg focus:ring-primary-purple focus:border-primary-purple transition duration-200 placeholder-gray-500 text-lg">

                <!-- Email Field -->
                <input type="email" id="signup-email" name="email" placeholder="Enter your email address" required
                    class="w-full mb-2 p-4 text-gray-900 bg-gray-100 border border-gray-300 rounded-lg focus:ring-primary-purple focus:border-primary-purple transition duration-200 placeholder-gray-500 text-lg">

                <!-- Country selector -->
                <select name="country" id="signup-country-select" required
                    class="w-full mb-2 p-4 text-gray-900 bg-gray-100 border border-gray-300 rounded-lg focus:ring-primary-purple focus:border-primary-purple transition duration-200 text-lg">
                    <option value="">Select your Country</option>
                </select>

                <!-- reCAPTCHA -->
                <div class="g-recaptcha mb-4" data-sitekey="<?php echo htmlspecialchars($recaptchaSiteKey); ?>"></div>

                <!-- Agreement Checkbox -->
                <div class="mb-4 mt-2">
                    <label class="flex items-start text-sm">
                        <input type="checkbox" id="signup-agreeTerms" name="agreeTerms" required class="mr-2 h-4 w-4 text-amber-500 bg-gray-700 border-gray-600 rounded focus:ring-amber-500 focus:ring-2 mt-0.5">
                        <span class="text-gray-900">
                            I agree to the
                            <a href="pages/terms-disclaimer.php" target="_blank" class="text-amber-400 hover:text-amber-300 underline">Terms and Conditions</a>
                            and
                            <a href="pages/privacy-policy.php" target="_blank" class="text-amber-400 hover:text-amber-300 underline">Privacy Policy</a>.
                        </span>
                    </label>
                </div>

                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeSignupModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary-purple text-white rounded-lg hover:bg-secondary-purple transition">Join Waitlist Now!</button>
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


    <script>
        // --- Environment Variables (Required for Canvas Compliance) ---
        // Note: For this specific timer feature, localStorage is used for client-side persistence.
        const appId = typeof __app_id !== 'undefined' ? __app_id : 'default-app-id';
        const firebaseConfig = JSON.parse(typeof __firebase_config !== 'undefined' ? __firebase_config : '{}');
        const initialAuthToken = typeof __initial_auth_token !== 'undefined' ? __initial_auth_token : null;

        // --- Timer and Price Logic Constants ---
        const ORIGINAL_PRICE = 59.00;
        const DISCOUNTED_PRICE = 36.58;

        // Use same timer logic as home.php countdown
        const startDate = new Date('2025-11-31T00:00:00').getTime();
        const expirationTime = startDate + (30 * 24 * 60 * 60 * 1000); // 30 days like home.php

        let offerEndTime;
        let timerInterval;

        // Function to handle custom UI alerts instead of window.alert()
        function alertMessage(title, message) {
            const container = document.createElement('div');
            container.className = 'fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center z-50';
            
            container.innerHTML = `
                <div class="bg-white p-6 rounded-lg shadow-2xl w-full max-w-sm transform transition-all scale-100 duration-300">
                    <h4 class="text-xl font-bold text-primary-purple mb-3">${title}</h4>
                    <p class="text-gray-700 mb-6">${message}</p>
                    <button onclick="document.body.removeChild(this.parentNode.parentNode)" class="w-full py-2 bg-primary-purple text-white rounded-lg font-semibold hover:bg-trophy-gold hover:text-header-dark transition">
                        Close
                    </button>
                </div>
            `;
            document.body.appendChild(container);
        }

        // Updates all visual elements based on whether the discount is active
        function updatePriceDisplay(isDiscounted) {
            const instantAccessCard = document.getElementById('instantAccessCard');
            const currentPriceDisplay = document.getElementById('currentPriceDisplay');
            const originalPriceDisplay = document.getElementById('originalPriceDisplay');
            const saveAmountDisplay = document.getElementById('saveAmountDisplay');
            const discountBadgeContainer = document.getElementById('discountBadgeContainer');
            const limitedOfferHighlight = document.getElementById('limitedOfferHighlight');
            const timerContainerWrapper = document.getElementById('timerContainerWrapper');
            const ctaButton = document.getElementById('ctaButton');

            // Classes for the prominent discounted state
            const promoClasses = ['shadow-2xl', 'border-4', 'border-trophy-gold', 'transform', 'scale-[1.03]', 'lg:scale-100', 'lg:hover:scale-[1.05]'];
            // Classes for the neutral (full price) state - mirroring the free card look
            const neutralClasses = ['shadow-lg', 'border', 'border-border-light', 'lg:hover:shadow-xl', 'lg:hover:border-primary-purple'];


            if (isDiscounted) {
                // Discounted State: $36.58
                currentPriceDisplay.innerHTML = `$${DISCOUNTED_PRICE.toFixed(2).split('.')[0]}<span class="text-3xl font-semibold">.${DISCOUNTED_PRICE.toFixed(2).split('.')[1]}</span>`;
                originalPriceDisplay.classList.remove('hidden');
                saveAmountDisplay.classList.remove('hidden');
                discountBadgeContainer.classList.remove('hidden');
                limitedOfferHighlight.classList.remove('hidden');
                timerContainerWrapper.classList.remove('hidden'); // Show global timer

                // Card Styling: Apply promo styles, remove neutral styles
                promoClasses.forEach(cls => instantAccessCard.classList.add(cls));
                neutralClasses.forEach(cls => instantAccessCard.classList.remove('border-border-light')); // Remove specific conflicting class
                neutralClasses.forEach(cls => instantAccessCard.classList.remove('shadow-lg')); // Remove specific conflicting class


                ctaButton.textContent = 'Purchase Phase 1 Access Now!';
                ctaButton.onclick = () => alertMessage('Checkout Initiated', 'You are being redirected to a secure payment gateway to purchase instant access for the discounted price of $36.58.');

            } else {
                // Full Price State: $59
                currentPriceDisplay.innerHTML = `$${ORIGINAL_PRICE.toFixed(0)}`;
                originalPriceDisplay.classList.add('hidden');
                saveAmountDisplay.classList.add('hidden');
                discountBadgeContainer.classList.add('hidden');
                limitedOfferHighlight.classList.add('hidden');
                timerContainerWrapper.classList.add('hidden'); // Hide global timer

                // Card Styling: Remove promo styles, apply neutral styles
                promoClasses.forEach(cls => instantAccessCard.classList.remove(cls));
                neutralClasses.forEach(cls => instantAccessCard.classList.add(cls));


                ctaButton.textContent = 'Purchase Phase 1 Access';
                ctaButton.onclick = () => alertMessage('Checkout Initiated', 'You are being redirected to a secure payment gateway to purchase instant access for the full price of $59.');
            }
        }

        // Runs every second to check time remaining (same logic as home.php countdown)
        function updateTimer() {
            const now = new Date().getTime();
            const distance = offerEndTime - now;

            const timerHours = document.getElementById('timerHours');
            const timerMinutes = document.getElementById('timerMinutes');
            const timerSeconds = document.getElementById('timerSeconds');

            if (distance < 0) {
                // Offer expired - same behavior as home.php
                clearInterval(timerInterval);
                const timerContainer = document.getElementById('timerContainer');
                if (timerContainer) {
                    timerContainer.innerHTML = '<span class="text-3xl font-extrabold text-red-700">PROMOTION ENDED!</span>';
                }
                updatePriceDisplay(false); // Revert to original price
                return;
            }

            // Calculate time remaining (Days, Hours, Minutes, Seconds)
            const days = Math.floor(distance / (1000 * 60 * 60 * 24)).toString().padStart(2, '0');
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)).toString().padStart(2, '0');
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60)).toString().padStart(2, '0');
            const seconds = Math.floor((distance % (1000 * 60)) / 1000).toString().padStart(2, '0');

            // Update card displays
            const timerDays = document.getElementById('timerDays');
            if (timerDays) timerDays.textContent = days;
            if (timerHours) timerHours.textContent = hours;
            if (timerMinutes) timerMinutes.textContent = minutes;
            if (timerSeconds) timerSeconds.textContent = seconds;

            updatePriceDisplay(true); // Ensure discounted state while timer is running
        }

        // Initializes the timer logic on page load (same as home.php countdown)
        function startCountdown() {
            // Use fixed expiration time like home.php (no localStorage persistence)
            offerEndTime = expirationTime;

            // Set up the interval
            if (timerInterval) clearInterval(timerInterval);
            updateTimer();
            timerInterval = setInterval(updateTimer, 1000);
        }

        window.onload = function() {
            startCountdown();
        };

        // Signup modal functionality
        function initializeSignupModal() {
            const signupForm = document.getElementById('signupForm');
            const countrySelect = document.getElementById('signup-country-select');

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
            signupForm.addEventListener('submit', async function (e) {
                e.preventDefault();

                const recaptchaResponse = grecaptcha.getResponse(); // Get reCAPTCHA token
                if (!recaptchaResponse) {
                    alertMessage('Please Verify', 'Please complete the reCAPTCHA to continue.');
                    return;
                }

                const name = signupForm.querySelector('input[name="name"]').value.trim();
                const email = signupForm.querySelector('input[name="email"]').value.trim();
                const country = countrySelect.value;
                const referralCode = sessionStorage.getItem('referral_code') || '';

                if (!name || !email || !country) {
                    alertMessage('Missing Information', 'Please fill in your name, email, and country.');
                    return;
                }

                try {
                    // Show loader
                    showSignupLoader();

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
                    hideSignupLoader();
                    ensureNoSignupLoaderRemains();

                    if (result.status === 'success') {
                        // Store referral information for dashboard access
                        if (result.referral_code) {
                            sessionStorage.setItem('user_referral_code', result.referral_code);
                            sessionStorage.setItem('referral_link', result.referral_link);
                        }

                        // Show success message and close modal
                        alertMessage('Welcome to Funding4x!', `You've been successfully registered! Check your email for verification instructions.`);

                    } else if (result.status === 'existing_user') {
                        // User exists and is verified - redirect to dashboard
                        hideSignupLoader();
                        ensureNoSignupLoaderRemains();
                        window.location.href = 'referral_dashboard.php?user=' + encodeURIComponent(result.referral_code);

                    } else if (result.status === 'email_not_verified') {
                        // User exists but hasn't verified email
                        alertMessage('Email Verification Required', 'You\'re already registered, but need to verify your email address first. Please check your email inbox.');

                    } else {
                        alertMessage('Oops!', result.message || 'Something went wrong. Please try again.');
                    }

                } catch (error) {
                    // Hide loader in case of network errors
                    hideSignupLoader();
                    ensureNoSignupLoaderRemains();

                    alertMessage('Connection Error', 'We couldn\'t submit your request. Please check your connection and try again.');
                }
            });
        }

        function closeSignupModal() {
            document.getElementById('signupModal').classList.add('hidden');
        }

        function showSignupModal() {
            document.getElementById('signupModal').classList.remove('hidden');
        }

        // Signup loader
        function showSignupLoader() {
            const loaderHtml = `
                <div id="signup-loader" class="fixed inset-0 loader-overlay z-50 flex items-center justify-center">
                    <div class="bg-gray-800 p-8 rounded-2xl shadow-2xl border-t-8 border-trophy-gold text-center max-w-md">
                        <div class="spinner mb-4"></div>
                        <h3 class="text-xl font-bold text-white mb-2">Creating Your Account...</h3>
                        <p class="text-gray-300 mb-4">Please wait while we set up your exclusive access and send verification email.</p>
                        <div class="flex items-center justify-center space-x-2 text-sm text-gray-400">
                            <div class="w-2 h-2 bg-trophy-gold rounded-full animate-pulse"></div>
                            <div class="w-2 h-2 bg-trophy-gold rounded-full animate-pulse" style="animation-delay: 0.2s;"></div>
                            <div class="w-2 h-2 bg-trophy-gold rounded-full animate-pulse" style="animation-delay: 0.4s;"></div>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', loaderHtml);
        }

        function hideSignupLoader() {
            const loader = document.getElementById('signup-loader');
            if (loader) {
                loader.remove();
            }
        }

        function ensureNoSignupLoaderRemains() {
            // Remove all possible loader elements
            const loaderSelectors = [
                '#signup-loader',
                '.loader-overlay',
                '[id*="signup"]',
                '[class*="loader"]'
            ];

            loaderSelectors.forEach(selector => {
                const elements = document.querySelectorAll(selector);
                elements.forEach(element => {
                    if (element && element.id !== 'signup-loader') {
                        element.remove();
                    }
                });
            });

            // Also check for any div with loader classes
            const allLoaders = document.querySelectorAll('.spinner');
            allLoaders.forEach(spinner => {
                const parent = spinner.closest('.fixed, .absolute');
                if (parent && parent.id !== 'signup-loader') {
                    // Remove the parent container if it looks like a loader overlay
                    const parentClasses = parent.className;
                    if (parentClasses.includes('loader-overlay') || parentClasses.includes('flex items-center justify-center')) {
                        parent.remove();
                    }
                }
            });

            console.log('Signup loader cleanup completed');
        }

        // Initialize signup modal when page loads
        document.addEventListener('DOMContentLoaded', () => {
            initializeSignupModal();
        });
    </script>
</body>
</html>