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
    <title>Funding4x - Exclusive Access Countdown</title>
    
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

    <style>
        /* Custom styles for the countdown digits */
        .countdown-digit {
            font-family: 'Inter', sans-serif;
            font-weight: 800;
            text-shadow: 0 4px 15px rgba(249, 115, 22, 0.5); /* Subtle orange glow */
        }
    </style>
    <style>
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
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-accent': '#f97316', /* Tailwind orange-500 */
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-900 text-white min-h-screen p-4 font-sans">

    <!-- Navigation Bar -->
    <nav class="bg-gray-800 mb-5 shadow-lg border-b-4 border-primary-accent">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <!-- Logo Section
                <div class="flex items-center">
                    <img src="assets/logo.png" alt="Funding4X Logo" class="h-10 w-10 mr-3 rounded-lg">
                    <span class="text-xl font-bold text-primary-accent">Funding4X</span>
                </div>
               --> 
                <!-- Navigation Links -->
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                        <a href="referral_dashboard.php" class="bg-primary-accent hover:bg-yellow-600 text-gray-900 font-bold px-4 py-2 rounded-lg text-sm transition duration-300">My Referral Dashboard</a>
                    </div>
                </div>
                
                <!-- Mobile menu button -->
                <div class="md:hidden">
                    <button type="button" class="text-gray-300 hover:text-white focus:outline-none focus:text-white" onclick="toggleMobileMenu()">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Mobile menu -->
            <div class="md:hidden hidden" id="mobile-menu">
                <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                    <a href="referral_dashboard.php" class="text-primary-accent hover:text-yellow-400 block px-3 py-2 rounded-md text-base font-medium">My Referral Dashboard</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content Centered -->
    <div class="flex items-center justify-center min-h-[calc(100vh-80px)]">
        <div class="max-w-4xl w-full bg-gray-800 p-8 md:p-12 rounded-2xl shadow-2xl border-t-8 border-primary-accent transform transition duration-500 hover:scale-[1.01]">
            <!-- Headline & FOMO Hook -->
            <header class="text-center mb-10">
            
            
            
            
                <h1 class="text-4xl md:text-6xl font-extrabold mb-3 leading-tight text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-primary-accent">
                <img src="assets/logo.png" alt="Funding4X Logo" >
                    LIMITED TIME ACCESS ONLY. DON'T MISS OUT!
                </h1>
                <p class="text-xl md:text-2xl text-gray-300 font-light">
                    We need skilled Forex Traders to Trade $5000 accounts for us. <br />We have a total of $200,000 to give to begin with. 
                </p>
            </header>

            <!-- NEW: Why Join Today Section (FOMO Points) -->
            <div class="mb-10 p-5 bg-gray-900 rounded-xl border border-red-700/50 shadow-inner">
                <h2 class="text-2xl font-bold text-center text-red-400 mb-4 uppercase tracking-widest">
                    Why Join us with your Forex Trading Skills??
                </h2>
                <ul class="text-left space-y-3 text-lg md:text-xl font-medium list-none p-0">
                    <li class="flex items-start text-gray-200">
                        <svg class="w-6 h-6 mr-3 mt-1 text-primary-accent flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span class="font-bold text-yellow-300">Private Prop Fund:</span> You Trade our Money.
                    </li>
                    <li class="flex items-start text-gray-200">
                        <svg class="w-6 h-6 mr-3 mt-1 text-primary-accent flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span class="font-bold text-yellow-300">50:50 Profit Share:</span> Trade Safely and we will share the Profits end of each month.
                    </li>
                    <li class="flex items-start text-gray-200">
                        <svg class="w-6 h-6 mr-3 mt-1 text-primary-accent flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span class="font-bold text-yellow-300">Relaxed Rules:</span>No secret rules or tricks like Prop Firms. Easy process.
                    </li>
                    <li class="flex items-start text-gray-200">
                        <svg class="w-6 h-6 mr-3 mt-1 text-primary-accent flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span class="font-bold text-yellow-300">Simple Trading Test:</span>Show us your Forex Trading Skills by passing the Tests with easy to follow rules which are Fair and Clear.
                    </li>
                </ul>
            </div>
            <!-- END FOMO POINTS -->

            <!-- Countdown Timer Section -->
            <div id="countdown-container" class="flex justify-center space-x-3 md:space-x-6 mb-10">
                <!-- Days Block -->
                <div class="text-center p-3 md:p-5 bg-gray-700 rounded-xl shadow-lg w-20 md:w-28 transition hover:bg-gray-600">
                    <div id="days" class="countdown-digit text-4xl md:text-6xl text-primary-accent">00</div>
                    <div class="text-xs md:text-sm uppercase text-gray-400 font-medium">Days</div>
                </div>

                <!-- Hours Block -->
                <div class="text-center p-3 md:p-5 bg-gray-700 rounded-xl shadow-lg w-20 md:w-28 transition hover:bg-gray-600">
                    <div id="hours" class="countdown-digit text-4xl md:text-6xl text-primary-accent">00</div>
                    <div class="text-xs md:text-sm uppercase text-gray-400 font-medium">Hours</div>
                </div>

                <!-- Minutes Block -->
                <div class="text-center p-3 md:p-5 bg-gray-700 rounded-xl shadow-lg w-20 md:w-28 transition hover:bg-gray-600">
                    <div id="minutes" class="countdown-digit text-4xl md:text-6xl text-primary-accent">00</div>
                    <div class="text-xs md:text-sm uppercase text-gray-400 font-medium">Minutes</div>
                </div>

                <!-- Seconds Block -->
                <div class="text-center p-3 md:p-5 bg-gray-700 rounded-xl shadow-lg w-20 md:w-28 transition hover:bg-gray-600">
                    <div id="seconds" class="countdown-digit text-4xl md:text-6xl text-primary-accent">00</div>
                    <div class="text-xs md:text-sm uppercase text-gray-400 font-medium">Seconds</div>
                </div>
            </div>
            
            <!-- Actionable Content and Waitlist Form -->
            <div class="text-center">
                <p class="text-lg md:text-xl text-red-400 font-semibold mb-6">
                    Enter your email to be the first to get Notified when we go Live!
                </p>

                <form id="waitlist-form" class="space-y-4 max-w-lg mx-auto">
                    <input type="text" id="name-input" name="name" placeholder="Enter your Name" required
                        class="w-full p-4 text-gray-100 bg-gray-700 border border-gray-600 rounded-lg focus:ring-primary-accent focus:border-primary-accent transition duration-200 placeholder-gray-400 text-lg">
                    <input type="email" id="email-input" name="email" placeholder="Enter your email address" required
                        class="w-full p-4 text-gray-100 bg-gray-700 border border-gray-600 rounded-lg focus:ring-primary-accent focus:border-primary-accent transition duration-200 placeholder-gray-400 text-lg">
                    
                    <!-- Country selector -->
                    <select name="country" id="country-select" required
                        class="w-full p-4 text-gray-100 bg-gray-700 border border-gray-600 rounded-lg focus:ring-primary-accent focus:border-primary-accent transition duration-200 text-lg">
                        <option value="">Select your Country</option>
                    </select>
                    
                    <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($recaptchaSiteKey); ?>"></div>

                    <button type="submit"
                        class="w-full bg-primary-accent hover:bg-yellow-600 text-gray-900 font-bold py-4 rounded-lg text-xl md:text-2xl uppercase tracking-wider shadow-2xl shadow-primary-accent/50 transition duration-300 ease-in-out transform hover:scale-105 active:scale-95">
                        Join Waitlist Now!
                    </button>
                </form>

                <!-- Referral Dashboard Button -->
                <div class="mt-6 max-w-lg mx-auto">
                    <a href="referral_dashboard.php"
                       id="dashboard-link"
                       class="w-full block bg-gray-700 hover:bg-gray-600 text-white font-bold py-3 px-6 rounded-lg text-lg md:text-xl uppercase tracking-wider shadow-lg transition duration-300 ease-in-out transform hover:scale-105 active:scale-95 text-center">
                        View Referral Dashboard
                    </a>
                    <p class="text-xs text-gray-400 mt-2 text-center">
                        Already joined? Check your referral status and earnings here
                    </p>
                </div>

                <p class="text-sm text-gray-500 mt-4">
                    *Limited slots remaining. Act before the timer expires.*
                </p>
            </div>

        </div>
    </div>

    <script>
        const countdownTimer = (function() {
            // Calculate the target date 20 days from now
            // const twentyDays = 20 * 24 * 60 * 60 * 1000;
            // const targetDate = new Date().getTime() + twentyDays;
             
            // FIXED start date for global countdown (set once)
            const startDate = new Date('2025-11-20T00:00:00').getTime();

            // Add 20 days to it (constant for everyone)
            const targetDate = startDate + (20 * 24 * 60 * 60 * 1000);

            // Get DOM elements
            const $days = document.getElementById('days');
            const $hours = document.getElementById('hours');
            const $minutes = document.getElementById('minutes');
            const $seconds = document.getElementById('seconds');
            const $container = document.getElementById('countdown-container');

            function updateCountdown() {
                const now = new Date().getTime();
                const distance = targetDate - now;

                // Time calculations for days, hours, minutes and seconds
                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                // Helper to pad numbers with a leading zero
                const pad = (num) => String(num).padStart(2, '0');

                if (distance < 0) {
                    clearInterval(x);
                    $container.innerHTML = `<div class="text-center text-4xl font-bold text-red-500">TIME'S UP! The offer has expired.</div>`;
                    // Disable form submission when time is up
                    const formButton = document.querySelector('#waitlist-form button');
                    if (formButton) {
                        formButton.disabled = true;
                        formButton.textContent = 'Waitlist Closed';
                        formButton.classList.remove('bg-primary-accent', 'hover:bg-yellow-600');
                        formButton.classList.add('bg-gray-500', 'cursor-not-allowed');
                    }
                } else {
                    $days.textContent = pad(days);
                    $hours.textContent = pad(hours);
                    $minutes.textContent = pad(minutes);
                    $seconds.textContent = pad(seconds);
                }
            }

            // Update the countdown immediately, then every second
            updateCountdown();
            const x = setInterval(updateCountdown, 1000);
        })();

        document.addEventListener('DOMContentLoaded', async function () {
            const waitlistForm = document.getElementById('waitlist-form');
            const countrySelect = document.getElementById('country-select');

            // Detect referral code from URL parameter
            const urlParams = new URLSearchParams(window.location.search);
            const referralCode = urlParams.get('ref');
            
            if (referralCode) {
                // Store referral code in session storage for form submission
                sessionStorage.setItem('referral_code', referralCode);
                console.log('Referred by code:', referralCode);
            }

            // Step 1: Load all countries into dropdown
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

            // 👉 Add default "Select your Country" placeholder FIRST
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

            // Step 2: Handle form submission
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
                        
                        // Show email verification message and keep user on index page
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
                                // Ensure no loader remains after alert is closed
                                hideEmailVerificationLoader();
                                ensureNoLoaderRemains();
                                console.log('User confirmed email verification message');
                            }
                        });

                    } else if (result.status === 'existing_user') {
                        // User exists and is verified - redirect to dashboard
                        // Hide loader before redirect
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
                                // Reset form completely and ensure clean state
                                resetFormToOriginalState();
                                
                                // Additional cleanup to ensure no lingering elements
                                setTimeout(() => {
                                    // Remove any SweetAlert elements that might persist
                                    const swalElements = document.querySelectorAll('.swal2-container');
                                    swalElements.forEach(el => el.remove());
                                    
                                    // Remove any custom modal backdrops
                                    const customBackdrop = document.getElementById('custom-alert-backdrop');
                                    if (customBackdrop) {
                                        customBackdrop.remove();
                                    }
                                    
                                    // Ensure no loader overlay remains
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
                                // Ensure no loader remains after alert is closed
                                hideEmailVerificationLoader();
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
                            // Double check no loader remains
                            hideEmailVerificationLoader();
                            ensureNoLoaderRemains();
                        }
                    });
                }
            });
        });

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
            const waitlistForm = document.getElementById('waitlist-form');
            const countrySelect = document.getElementById('country-select');
            
            // Reset form inputs
            waitlistForm.querySelector('input[name="name"]').value = '';
            waitlistForm.querySelector('input[name="email"]').value = '';
            countrySelect.selectedIndex = 0;
            
            // Reset country dropdown to default state
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
            
            // Clear any remaining loader elements
            hideEmailVerificationLoader();
            
            // Clear session storage related to the form
            sessionStorage.removeItem('user_referral_code');
            sessionStorage.removeItem('referral_link');
            
            console.log('Form reset to original state');
        }

        // Mobile menu toggle function
        function toggleMobileMenu() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        }
    </script>

    <!-- Email Verification Loader -->
    <div id="email-verification-loader" style="display: none;"></div>
</body>
</html>
