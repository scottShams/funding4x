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
    <title>Exclusive Access Countdown</title>
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
<body class="bg-gray-900 text-white min-h-screen flex items-center justify-center p-4 font-sans">

    <div class="max-w-4xl w-full bg-gray-800 p-8 md:p-12 rounded-2xl shadow-2xl border-t-8 border-primary-accent transform transition duration-500 hover:scale-[1.01]">
        <!-- Headline & FOMO Hook -->
        <header class="text-center mb-10">
            <h1 class="text-4xl md:text-6xl font-extrabold mb-3 leading-tight text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-primary-accent">
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
                    <span class="font-bold text-yellow-300">Rexlaed Rules:</span>No secret rules or tricks like Prop Firms. Easy process.
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

            <p class="text-sm text-gray-500 mt-4">
                *Limited slots remaining. Act before the timer expires.*
            </p>
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
                    Swal.fire({
                        icon: 'warning',
                        title: 'Missing Information',
                        text: 'Please fill in your name, email, and country.',
                        confirmButtonColor: '#f97316'
                    });
                    return;
                }

                try {
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

                    if (result.status === 'success' || result.status === 'existing_user') {
                        grecaptcha.reset();

                        // Store referral information for dashboard access
                        if (result.referral_code) {
                            sessionStorage.setItem('user_referral_code', result.referral_code);
                            sessionStorage.setItem('referral_link', result.referral_link);
                        }
                        
                        // Direct redirect to referral dashboard (no SweetAlert for existing users)
                        if (result.status === 'existing_user') {
                            // For existing users, redirect immediately without showing error
                            window.location.href = 'referral_dashboard.php?user=' + encodeURIComponent(result.referral_code);
                        } else {
                            // For new users, show success message and redirect
                            Swal.fire({
                                icon: 'success',
                                title: '🎉 Welcome to the Program!',
                                text: 'You\'ve been successfully registered. Redirecting to your referral dashboard...',
                                confirmButtonColor: '#f97316',
                                timer: 2000,
                                timerProgressBar: true,
                                willClose: () => {
                                    // Redirect to referral dashboard
                                    window.location.href = 'referral_dashboard.php?user=' + encodeURIComponent(result.referral_code);
                                }
                            });
                        }
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops!',
                            text: result.message || 'Something went wrong. Please try again.',
                            confirmButtonColor: '#f97316'
                        });
                    }

                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Connection Error',
                        text: 'We couldn\'t submit your request. Please check your connection and try again.',
                        confirmButtonColor: '#f97316'
                    });
                }
            });
        });

        // Using a custom alert function as per guidelines
        function alert(message) {
            const container = document.body;

            // Create Modal Backdrop
            const backdrop = document.createElement('div');
            backdrop.className = 'fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center transition-opacity duration-300 ease-out opacity-0';
            backdrop.id = 'custom-alert-backdrop';

            // Create Modal Dialog
            const dialog = document.createElement('div');
            dialog.className = 'bg-gray-800 p-8 rounded-xl shadow-2xl max-w-sm w-full transform -translate-y-4 opacity-0 transition-all duration-300 ease-out';

            // Message
            const messageElement = document.createElement('p');
            messageElement.className = 'text-lg text-white mb-6 text-center';
            messageElement.textContent = message;

            // Close Button
            const closeButton = document.createElement('button');
            closeButton.className = 'w-full bg-primary-accent hover:bg-yellow-600 text-gray-900 font-bold py-3 rounded-lg transition duration-200';
            closeButton.textContent = 'Got It';
            
            const closeModal = () => {
                dialog.classList.remove('translate-y-0', 'opacity-100');
                backdrop.classList.remove('opacity-100');
                setTimeout(() => {
                    backdrop.remove();
                }, 300);
            };

            closeButton.onclick = closeModal;
            backdrop.onclick = (e) => {
                if (e.target === backdrop) {
                    closeModal();
                }
            };

            dialog.appendChild(messageElement);
            dialog.appendChild(closeButton);
            backdrop.appendChild(dialog);
            container.appendChild(backdrop);

            // Animate in
            setTimeout(() => {
                backdrop.classList.add('opacity-100');
                dialog.classList.add('translate-y-0', 'opacity-100');
            }, 10);
        }
    </script>
</body>
</html>
