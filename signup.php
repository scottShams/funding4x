<?php
    session_start();

    // Check if user is already logged in
    if (isset($_SESSION['user_id']) || isset($_SESSION['user_email'])) {
        header('Location: referral_dashboard.php');
        exit;
    }

    // Load environment variables
    require_once __DIR__ . '/env_loader.php';

    // Get reCAPTCHA site key from environment
    $recaptchaSiteKey = EnvLoader::get('RECAPTCHA_SITE_KEY', 'your_recaptcha_site_key_here');

    // Get referral code from session or cookie
    $referralCode = $_SESSION['referral_code'] ?? $_COOKIE['referral_code'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Funding4x</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon.ico">
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

    <!-- Main Content: Signup Form -->
    <main class="flex-grow flex flex-col items-center p-4 sm:p-8">
        <div class="w-full max-w-md">
            <div class="bg-card-white p-8 rounded-xl shadow-2xl">

                <h2 class="text-3xl font-extrabold text-header-dark mb-6 text-center">
                    Create Your Account
                </h2>
                <p class="text-gray-600 text-center mb-8">
                    Join Funding4x and start your trading journey.
                </p>

                <form id="signupForm" onsubmit="event.preventDefault();">
                    <!-- Name Field -->
                    <input type="text" id="signup-name" name="name" placeholder="Enter your Name" required
                        class="w-full mb-4 p-4 text-gray-900 bg-gray-100 border border-gray-300 rounded-lg focus:ring-primary-purple focus:border-primary-purple transition duration-200 placeholder-gray-500 text-lg">

                    <!-- Email Field -->
                    <input type="email" id="signup-email" name="email" placeholder="Enter your email address" required
                        class="w-full mb-4 p-4 text-gray-900 bg-gray-100 border border-gray-300 rounded-lg focus:ring-primary-purple focus:border-primary-purple transition duration-200 placeholder-gray-500 text-lg">

                    <!-- Password Field -->
                    <input type="password" id="signup-password" name="password" placeholder="Enter your password (min 8 characters)" required
                        class="w-full mb-4 p-4 text-gray-900 bg-gray-100 border border-gray-300 rounded-lg focus:ring-primary-purple focus:border-primary-purple transition duration-200 placeholder-gray-500 text-lg">

                    <!-- Country selector -->
                    <select name="country" id="signup-country-select" required
                        class="w-full mb-4 p-4 text-gray-900 bg-gray-100 border border-gray-300 rounded-lg focus:ring-primary-purple focus:border-primary-purple transition duration-200 text-lg">
                        <option value="">Select your Country</option>
                    </select>

                    <!-- reCAPTCHA -->
                    <div class="g-recaptcha mb-4" data-sitekey="<?php echo htmlspecialchars($recaptchaSiteKey); ?>"></div>

                    <!-- Agreement Checkbox -->
                    <div class="mb-6 mt-2">
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

                    <div class="flex justify-center">
                        <button type="submit" class="w-64 px-6 py-3 bg-primary-purple text-white font-semibold text-lg rounded-lg shadow-lg hover:bg-secondary-purple transition duration-300">Create Account</button>
                    </div>
                </form>

                <p class="text-center text-sm text-gray-500 mt-6">
                    Already have an account? <a href="login.php" class="text-primary-purple hover:text-secondary-purple underline">Log in here</a>
                </p>

            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-header-dark text-white mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 text-center">
            <p class="text-sm">&copy; 2024 Funding4x. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Signup form functionality
        document.addEventListener('DOMContentLoaded', () => {
            const signupForm = document.getElementById('signupForm');
            const countrySelect = document.getElementById('signup-country-select');

            // Get referral code from PHP (session/cookie)
            const referralCode = '<?php echo htmlspecialchars($referralCode); ?>';

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
                    Swal.fire({
                        icon: 'warning',
                        title: 'Please Verify',
                        text: 'Please complete the reCAPTCHA to continue.',
                        confirmButtonColor: '#4f009d'
                    });
                    return;
                }

                const name = signupForm.querySelector('input[name="name"]').value.trim();
                const email = signupForm.querySelector('input[name="email"]').value.trim();
                const password = signupForm.querySelector('input[name="password"]').value.trim();
                const country = countrySelect.value;
                const referralCodeStored = sessionStorage.getItem('referral_code') || '';

                if (!name || !email || !password || !country) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Missing Information',
                        text: 'Please fill in all fields.',
                        confirmButtonColor: '#4f009d'
                    });
                    return;
                }

                if (password.length < 8) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Password Too Short',
                        text: 'Password must be at least 8 characters long.',
                        confirmButtonColor: '#4f009d'
                    });
                    return;
                }

                try {
                    // Show loader
                    showSignupLoader();

                    const requestData = {
                        name,
                        email,
                        password,
                        country,
                        recaptcha: recaptchaResponse
                    };
                    if (referralCodeStored) {
                        requestData.ref = referralCodeStored;
                    }

                    const response = await fetch('save_user.php', {
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

                        Swal.fire({
                            icon: 'success',
                            title: 'Welcome to Funding4x!',
                            text: 'Account created successfully! Please check your email to verify your account.',
                            confirmButtonColor: '#4f009d'
                        }).then(() => {
                            const hasCheckoutPrice = document.cookie.split(';').some(c => c.trim().startsWith('checkout_price='));
                            window.location.href = hasCheckoutPrice ? 'checkout.php' : 'referral_dashboard.php';
                        });

                    } else if (result.status === 'existing_user') {
                        // User exists and is verified
                        Swal.fire({
                            icon: 'info',
                            title: 'Already Registered',
                            text: 'You are already registered and verified. You can log in now.',
                            confirmButtonColor: '#4f009d'
                        }).then(() => {
                            window.location.href = 'login.php';
                        });

                    } else if (result.status === 'email_not_verified') {
                        // User exists but hasn't verified email
                        Swal.fire({
                            icon: 'warning',
                            title: 'Email Verification Required',
                            text: 'You are already registered, but need to verify your email address first. Please check your email inbox.',
                            confirmButtonColor: '#4f009d'
                        });

                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops!',
                            text: result.message || 'Something went wrong. Please try again.',
                            confirmButtonColor: '#4f009d'
                        });
                    }

                } catch (error) {
                    // Hide loader in case of network errors
                    hideSignupLoader();
                    ensureNoSignupLoaderRemains();

                    Swal.fire({
                        icon: 'error',
                        title: 'Connection Error',
                        text: 'We couldn\'t submit your request. Please check your connection and try again.',
                        confirmButtonColor: '#4f009d'
                    });
                }
            });
        });


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
    </script>
</body>
</html>