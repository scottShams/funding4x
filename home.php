<?php
// Load environment variables
require_once __DIR__ . '/env_loader.php';

if (isset($_GET['ref'])) {
    $ref = $_GET['ref'];

    // Save to session
    $_SESSION['referral_code'] = $ref;

    // Save to cookie for 30 days
    setcookie("referral_code", $ref, time() + (30 * 24 * 60 * 60), "/");
}

// Get reCAPTCHA site key from environment
$recaptchaSiteKey = EnvLoader::get('RECAPTCHA_SITE_KEY', 'your_recaptcha_site_key_here');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
      <!-- Primary SEO Meta Tags -->
      <title>Funding4x - Free Prop Firm Challenge - Exclusive Access Countdown</title>
      <meta name="description" content="Funding4x is a forex prop firm that offers funded trading accounts. Join our trial or evaluation account, pass the challenge, and trade company capital.">
      <meta name="keywords" content="funded trading account, prop firm, forex prop firm, funded trader program, free trial trading account, pass prop firm challenge, trading with company capital, forex trading">
      <!-- Author -->
      <meta name="author" content="Funding4x">
      <!-- Robots -->
      <meta name="robots" content="index, follow">
        
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
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-4F50HDQBDE"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', 'G-4F50HDQBDE');
    </script>

<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-1954105902455354"
     crossorigin="anonymous"></script>

     
     
</head>
<body class="bg-gray-900 text-white min-h-screen p-4 font-sans">

    

    <!-- Main Content Centered -->
    <div class="flex items-center justify-center min-h-[calc(100vh-80px)]">
        <div class="max-w-4xl w-full bg-gray-800 p-8 md:p-12 rounded-2xl shadow-2xl border-t-8 border-primary-accent transform transition duration-500 hover:scale-[1.01]">
            <!-- Headline & FOMO Hook -->
            <header class="text-center mb-10">
            
            
            
             <h1 class="text-4xl md:text-6xl font-extrabold mb-3 leading-tight text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-primary-accent">
                <img src="assets/logo.png" alt="Funding4X Logo" >
                   ARE YOU A FOREX TRADER?<br />JOIN US.
                </h1>
                
                <p class="text-xl md:text-2xl text-gray-300 font-light">
                    We need skilled Forex Traders to Trade $5000 accounts for us. <br />We have a total of $200,000 to give to begin with. 
                </p>
                <h1 class="text-4xl md:text-6xl font-extrabold mb-3 leading-tight text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-primary-accent">
               
                    LIMITED TIME ACCESS ONLY. DON'T MISS OUT!
                </h1>
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
            
            <!-- Actionable Content and Login Button -->
            <div class="text-center">
                <p class="text-lg md:text-xl text-red-400 font-semibold mb-6">
                    Ready to start your trading journey?
                </p>

                <div class="max-w-lg mx-auto">
                    <!-- login BUTTON -->
                    <a href="login.php"
                    class="mb-3 w-full block bg-gray-800 hover:bg-gray-700 text-white font-bold py-4 px-6 rounded-lg text-xl md:text-2xl uppercase tracking-wider shadow-xl transition duration-300 ease-in-out transform hover:scale-105 active:scale-95 text-center border border-gray-700">
                        Log In to Your Account
                    </a>
                    <!-- SIGNUP BUTTON -->
                    <a href="signup.php"
                       class="w-full block bg-primary-accent hover:bg-yellow-600 text-gray-900 font-bold py-4 px-6 rounded-lg text-xl md:text-2xl uppercase tracking-wider shadow-2xl shadow-primary-accent/50 transition duration-300 ease-in-out transform hover:scale-105 active:scale-95 text-center">
                        Sign Up
                    </a>
                    <p class="text-sm text-gray-300 mt-4">
                        Access your dashboard and start trading with Funding4x
                    </p>
                </div>

                <p class="text-sm text-gray-500 mt-6">
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
            const startDate = new Date('2025-11-27T00:00:00').getTime();

            // Add 20 days to it (constant for everyone)
            const targetDate = startDate + (12 * 24 * 60 * 60 * 1000);

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



        // Mobile menu toggle function
        function toggleMobileMenu() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        }
    </script>

</body>
</html>
