<?php
    session_start();

    // Check if user is already logged in
    if (isset($_SESSION['user_id']) || isset($_SESSION['user_email'])) {
        header('Location: referral_dashboard.php');
        exit;
    }

    // Load environment variables
    require_once __DIR__ . '/env_loader.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Funding4x</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon.ico">
    <link rel="manifest" href="assets/site.webmanifest">

    <!-- Load Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

    <!-- Main Content: Forgot Password Form -->
    <main class="flex-grow flex flex-col items-center p-4 sm:p-8">
        <div class="w-full max-w-md">
            <div class="bg-card-white p-8 rounded-xl shadow-2xl">

                <h2 class="text-3xl font-extrabold text-header-dark mb-6 text-center">
                    Reset Your Password
                </h2>
                <p class="text-gray-600 text-center mb-8">
                    Enter your email address and we'll send you a link to reset your password.
                </p>

                <form id="forgotPasswordForm" onsubmit="event.preventDefault();">
                    <!-- Email Field -->
                    <input type="email" id="forgot-email" name="email" placeholder="Enter your email address" required
                        class="w-full mb-6 p-4 text-gray-900 bg-gray-100 border border-gray-300 rounded-lg focus:ring-primary-purple focus:border-primary-purple transition duration-200 placeholder-gray-500 text-lg">

                    <div class="flex justify-center">
                        <button type="submit" class="w-64 px-6 py-3 bg-primary-purple text-white font-semibold text-lg rounded-lg shadow-lg hover:bg-secondary-purple transition duration-300">Send Reset Link</button>
                    </div>
                </form>

                <p class="text-center text-sm text-gray-500 mt-6">
                    Remember your password? <a href="login.php" class="text-primary-purple hover:text-secondary-purple underline">Log in here</a>
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
        // Forgot password form functionality
        document.addEventListener('DOMContentLoaded', () => {
            const forgotPasswordForm = document.getElementById('forgotPasswordForm');

            // Handle form submission
            forgotPasswordForm.addEventListener('submit', async function (e) {
                e.preventDefault();

                const email = forgotPasswordForm.querySelector('input[name="email"]').value.trim();

                if (!email) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Missing Information',
                        text: 'Please enter your email address.',
                        confirmButtonColor: '#4f009d'
                    });
                    return;
                }

                try {
                    // Show loader
                    showForgotPasswordLoader();

                    const formData = new FormData();
                    formData.append('email', email);

                    const response = await fetch(window.location.origin + '/forgot_password_process.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    // Hide loader immediately and ensure complete cleanup
                    hideForgotPasswordLoader();
                    ensureNoForgotPasswordLoaderRemains();

                    if (result.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Reset Link Sent!',
                            text: 'Please check your email for password reset instructions.',
                            confirmButtonColor: '#4f009d'
                        }).then(() => {
                            // Optionally redirect to login
                            window.location.href = 'login.php';
                        });

                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: result.message || 'Something went wrong. Please try again.',
                            confirmButtonColor: '#4f009d'
                        });
                    }

                } catch (error) {
                    // Hide loader in case of network errors
                    hideForgotPasswordLoader();
                    ensureNoForgotPasswordLoaderRemains();

                    Swal.fire({
                        icon: 'error',
                        title: 'Connection Error',
                        text: 'We couldn\'t submit your request. Please check your connection and try again.',
                        confirmButtonColor: '#4f009d'
                    });
                }
            });
        });


        // Forgot password loader
        function showForgotPasswordLoader() {
            // Remove any existing loader first
            hideForgotPasswordLoader();

            const loaderDiv = document.createElement('div');
            loaderDiv.id = 'forgot-password-loader';
            loaderDiv.className = 'fixed inset-0 loader-overlay z-50 flex items-center justify-center';
            loaderDiv.innerHTML = `
                <div class="bg-gray-800 p-8 rounded-2xl shadow-2xl border-t-8 border-trophy-gold text-center max-w-md">
                    <div class="spinner mb-4"></div>
                    <h3 class="text-xl font-bold text-white mb-2">Sending Reset Link...</h3>
                    <p class="text-gray-300 mb-4">Please wait while we process your request.</p>
                    <div class="flex items-center justify-center space-x-2 text-sm text-gray-400">
                        <div class="w-2 h-2 bg-trophy-gold rounded-full animate-pulse"></div>
                        <div class="w-2 h-2 bg-trophy-gold rounded-full animate-pulse" style="animation-delay: 0.2s;"></div>
                        <div class="w-2 h-2 bg-trophy-gold rounded-full animate-pulse" style="animation-delay: 0.4s;"></div>
                    </div>
                </div>
            `;
            document.body.appendChild(loaderDiv);
        }

        function hideForgotPasswordLoader() {
            const loader = document.getElementById('forgot-password-loader');
            if (loader) {
                loader.remove();
            }
            // Also remove any other loader overlays
            const overlays = document.querySelectorAll('.loader-overlay');
            overlays.forEach(overlay => overlay.remove());
        }

        function ensureNoForgotPasswordLoaderRemains() {
            // Additional cleanup if needed
            const remainingLoaders = document.querySelectorAll('.loader-overlay, #forgot-password-loader');
            remainingLoaders.forEach(loader => loader.remove());
            console.log('Forgot password loader cleanup completed');
        }
    </script>
</body>
</html>