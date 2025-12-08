<?php
    session_start();

    // Check if user is already logged in
    if (isset($_SESSION['user_id']) || isset($_SESSION['user_email'])) {
        header('Location: referral_dashboard.php');
        exit;
    }

    // Set paid user cookie if coming from pricing page
    if (isset($_GET['paid']) && $_GET['paid'] == '1') {
        setcookie('paid_user', '1', time() + (1 * 24 * 60 * 60), '/'); // 1 days
    }

    // Load environment variables
    require_once __DIR__ . '/env_loader.php';

    // Get reCAPTCHA site key from environment
    // $recaptchaSiteKey = EnvLoader::get('RECAPTCHA_SITE_KEY', 'your_recaptcha_site_key_here');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In - Funding4x</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon.ico">
    <link rel="manifest" href="assets/site.webmanifest">

    <!-- Load Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- <script src="https://www.google.com/recaptcha/api.js" async defer></script> -->
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

    <!-- Main Content: Login Form -->
    <main class="flex-grow flex flex-col items-center p-4 sm:p-8">
        <div class="w-full max-w-md">
            <div class="bg-card-white p-8 rounded-xl shadow-2xl">

                <h2 class="text-3xl font-extrabold text-header-dark mb-6 text-center">
                    Welcome Back
                </h2>
                <p class="text-gray-600 text-center mb-8">
                    Log in to your Funding4x account.
                </p>

                <form id="loginForm" onsubmit="event.preventDefault();">
                    <!-- Email Field -->
                    <input type="email" id="login-email" name="email" placeholder="Enter your email address" required
                        class="w-full mb-4 p-4 text-gray-900 bg-gray-100 border border-gray-300 rounded-lg focus:ring-primary-purple focus:border-primary-purple transition duration-200 placeholder-gray-500 text-lg">

                    <!-- Password Field -->
                    <input type="password" id="login-password" name="password" placeholder="Enter your password" required
                        class="w-full mb-4 p-4 text-gray-900 bg-gray-100 border border-gray-300 rounded-lg focus:ring-primary-purple focus:border-primary-purple transition duration-200 placeholder-gray-500 text-lg">

                    <!-- reCAPTCHA -->
                    <!-- <div class="g-recaptcha mb-4" data-sitekey="<?php echo htmlspecialchars($recaptchaSiteKey); ?>"></div> -->

                    <div class="flex justify-center">
                        <button type="submit" class="w-64 px-6 py-3 bg-primary-purple text-white font-semibold text-lg rounded-lg shadow-lg hover:bg-secondary-purple transition duration-300">Log In</button>
                    </div>
                </form>

                <p class="text-center text-sm text-gray-500 mt-6">
                    Don't have an account? <a href="signup.php" class="text-primary-purple hover:text-secondary-purple underline">Sign up here</a>
                </p>

                <p class="text-center text-sm text-gray-500 mt-2">
                    <a href="forgot_password.php" class="text-primary-purple hover:text-secondary-purple underline">Forgot your password?</a>
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
        // Login form functionality
        document.addEventListener('DOMContentLoaded', () => {
            const loginForm = document.getElementById('loginForm');

            // Handle form submission
            loginForm.addEventListener('submit', async function (e) {
                e.preventDefault();

                // const recaptchaResponse = grecaptcha.getResponse(); // Get reCAPTCHA token
                // if (!recaptchaResponse) {
                //     Swal.fire({
                //         icon: 'warning',
                //         title: 'Please Verify',
                //         text: 'Please complete the reCAPTCHA to continue.',
                //         confirmButtonColor: '#4f009d'
                //     });
                //     return;
                // }

                const email = loginForm.querySelector('input[name="email"]').value.trim();
                const password = loginForm.querySelector('input[name="password"]').value.trim();

                if (!email || !password) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Missing Information',
                        text: 'Please fill in your email and password.',
                        confirmButtonColor: '#4f009d'
                    });
                    return;
                }

                try {
                    // Show loader
                    showLoginLoader();

                    const formData = new FormData();
                    formData.append('email', email);
                    formData.append('password', password);
                    // formData.append('recaptcha', recaptchaResponse);

                    const response = await fetch(window.location.origin + '/login_process.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    // Hide loader immediately and ensure complete cleanup
                    hideLoginLoader();
                    ensureNoLoginLoaderRemains();

                    if (result.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Login Successful!',
                            text: 'Welcome back to Funding4x!',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = result.redirect || 'referral_dashboard.php';
                        });

                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Login Failed',
                            text: result.message || 'Invalid email or password.',
                            confirmButtonColor: '#4f009d'
                        });
                        // grecaptcha.reset();
                    }

                } catch (error) {
                    // Hide loader in case of network errors
                    hideLoginLoader();
                    ensureNoLoginLoaderRemains();

                    Swal.fire({
                        icon: 'error',
                        title: 'Connection Error',
                        text: 'We couldn\'t submit your request. Please check your connection and try again.',
                        confirmButtonColor: '#4f009d'
                    });
                    // grecaptcha.reset();
                }
            });
        });


        // Login loader
        function showLoginLoader() {
            // Remove any existing loader first
            hideLoginLoader();

            const loaderDiv = document.createElement('div');
            loaderDiv.id = 'login-loader';
            loaderDiv.className = 'fixed inset-0 loader-overlay z-50 flex items-center justify-center';
            loaderDiv.innerHTML = `
                <div class="bg-gray-800 p-8 rounded-2xl shadow-2xl border-t-8 border-trophy-gold text-center max-w-md">
                    <div class="spinner mb-4"></div>
                    <h3 class="text-xl font-bold text-white mb-2">Logging You In...</h3>
                    <p class="text-gray-300 mb-4">Please wait while we verify your credentials.</p>
                    <div class="flex items-center justify-center space-x-2 text-sm text-gray-400">
                        <div class="w-2 h-2 bg-trophy-gold rounded-full animate-pulse"></div>
                        <div class="w-2 h-2 bg-trophy-gold rounded-full animate-pulse" style="animation-delay: 0.2s;"></div>
                        <div class="w-2 h-2 bg-trophy-gold rounded-full animate-pulse" style="animation-delay: 0.4s;"></div>
                    </div>
                </div>
            `;
            document.body.appendChild(loaderDiv);
        }

        function hideLoginLoader() {
            const loader = document.getElementById('login-loader');
            if (loader) {
                loader.remove();
            }
            // Also remove any other loader overlays
            const overlays = document.querySelectorAll('.loader-overlay');
            overlays.forEach(overlay => overlay.remove());
        }

        function ensureNoLoginLoaderRemains() {
            // Additional cleanup if needed
            const remainingLoaders = document.querySelectorAll('.loader-overlay, #login-loader');
            remainingLoaders.forEach(loader => loader.remove());
            console.log('Login loader cleanup completed');
        }
    </script>
</body>
</html>
