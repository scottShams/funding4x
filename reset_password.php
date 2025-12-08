<?php
    session_start();

    // Check if user is already logged in
    if (isset($_SESSION['user_id']) || isset($_SESSION['user_email'])) {
        header('Location: referral_dashboard.php');
        exit;
    }

    // Get token from URL
    $token = $_GET['token'] ?? '';

    if (empty($token)) {
        header('Location: login.php');
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
    <title>Reset Password - Funding4x</title>

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

    <!-- Main Content: Reset Password Form -->
    <main class="flex-grow flex flex-col items-center p-4 sm:p-8">
        <div class="w-full max-w-md">
            <div class="bg-card-white p-8 rounded-xl shadow-2xl">

                <h2 class="text-3xl font-extrabold text-header-dark mb-6 text-center">
                    Reset Your Password
                </h2>
                <p class="text-gray-600 text-center mb-8">
                    Enter your new password below.
                </p>

                <form id="resetPasswordForm" onsubmit="event.preventDefault();">
                    <!-- New Password Field -->
                    <input type="password" id="new-password" name="password" placeholder="Enter new password (min 8 characters)" required
                        class="w-full mb-4 p-4 text-gray-900 bg-gray-100 border border-gray-300 rounded-lg focus:ring-primary-purple focus:border-primary-purple transition duration-200 placeholder-gray-500 text-lg">

                    <!-- Confirm Password Field -->
                    <input type="password" id="confirm-password" name="confirm_password" placeholder="Confirm new password" required
                        class="w-full mb-6 p-4 text-gray-900 bg-gray-100 border border-gray-300 rounded-lg focus:ring-primary-purple focus:border-primary-purple transition duration-200 placeholder-gray-500 text-lg">

                    <div class="flex justify-center">
                        <button type="submit" class="w-64 px-6 py-3 bg-primary-purple text-white font-semibold text-lg rounded-lg shadow-lg hover:bg-secondary-purple transition duration-300">Update Password</button>
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
        // Reset password form functionality
        document.addEventListener('DOMContentLoaded', () => {
            const resetPasswordForm = document.getElementById('resetPasswordForm');

            // Handle form submission
            resetPasswordForm.addEventListener('submit', async function (e) {
                e.preventDefault();

                const password = resetPasswordForm.querySelector('input[name="password"]').value.trim();
                const confirmPassword = resetPasswordForm.querySelector('input[name="confirm_password"]').value.trim();

                if (!password || !confirmPassword) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Missing Information',
                        text: 'Please fill in both password fields.',
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

                if (password !== confirmPassword) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Passwords Don\'t Match',
                        text: 'Please make sure both passwords match.',
                        confirmButtonColor: '#4f009d'
                    });
                    return;
                }

                try {
                    // Show loader
                    showResetPasswordLoader();

                    const formData = new FormData();
                    formData.append('token', '<?php echo htmlspecialchars($token); ?>');
                    formData.append('password', password);

                    const response = await fetch(window.location.origin + '/reset_password_process.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    // Hide loader immediately and ensure complete cleanup
                    hideResetPasswordLoader();
                    ensureNoResetPasswordLoaderRemains();

                    if (result.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Password Updated!',
                            text: 'Your password has been successfully updated. You can now log in with your new password.',
                            confirmButtonColor: '#4f009d'
                        }).then(() => {
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
                    hideResetPasswordLoader();
                    ensureNoResetPasswordLoaderRemains();

                    Swal.fire({
                        icon: 'error',
                        title: 'Connection Error',
                        text: 'We couldn\'t submit your request. Please check your connection and try again.',
                        confirmButtonColor: '#4f009d'
                    });
                }
            });
        });


        // Reset password loader
        function showResetPasswordLoader() {
            // Remove any existing loader first
            hideResetPasswordLoader();

            const loaderDiv = document.createElement('div');
            loaderDiv.id = 'reset-password-loader';
            loaderDiv.className = 'fixed inset-0 loader-overlay z-50 flex items-center justify-center';
            loaderDiv.innerHTML = `
                <div class="bg-gray-800 p-8 rounded-2xl shadow-2xl border-t-8 border-trophy-gold text-center max-w-md">
                    <div class="spinner mb-4"></div>
                    <h3 class="text-xl font-bold text-white mb-2">Updating Password...</h3>
                    <p class="text-gray-300 mb-4">Please wait while we update your password.</p>
                    <div class="flex items-center justify-center space-x-2 text-sm text-gray-400">
                        <div class="w-2 h-2 bg-trophy-gold rounded-full animate-pulse"></div>
                        <div class="w-2 h-2 bg-trophy-gold rounded-full animate-pulse" style="animation-delay: 0.2s;"></div>
                        <div class="w-2 h-2 bg-trophy-gold rounded-full animate-pulse" style="animation-delay: 0.4s;"></div>
                    </div>
                </div>
            `;
            document.body.appendChild(loaderDiv);
        }

        function hideResetPasswordLoader() {
            const loader = document.getElementById('reset-password-loader');
            if (loader) {
                loader.remove();
            }
            // Also remove any other loader overlays
            const overlays = document.querySelectorAll('.loader-overlay');
            overlays.forEach(overlay => overlay.remove());
        }

        function ensureNoResetPasswordLoaderRemains() {
            // Additional cleanup if needed
            const remainingLoaders = document.querySelectorAll('.loader-overlay, #reset-password-loader');
            remainingLoaders.forEach(loader => loader.remove());
            console.log('Reset password loader cleanup completed');
        }
    </script>
</body>
</html>