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

    <?php include 'header.php'; ?>

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
                    <a href="login.php" class="w-full py-3 bg-primary-purple text-card-white font-extrabold text-lg rounded-lg shadow-lg hover:bg-secondary-purple transition duration-300 text-center block">
                        Log In to Get Started
                    </a>

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
                    <a href="login.php" class="w-full py-3 bg-trophy-gold text-header-dark font-extrabold text-lg rounded-lg shadow-lg hover:bg-cta-hover transition duration-300 text-center block">
                        Log In Now
                    </a>
                    
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

    </script>
</body>
</html>