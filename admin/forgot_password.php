<?php
// Set session lifetime to 7 hours (25200 seconds)
ini_set('session.gc_maxlifetime', 25200);
session_set_cookie_params(25200);
session_start();
require_once '../database.php';

// Generate a simple token for the form (CSRF protection)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-header bg-primary text-white text-center">
                    <h4>Forgot Password</h4>
                </div>
                <div class="card-body">
                    <div id="message-container"></div>

                    <p class="text-muted small mb-4">Enter your email address and we'll send you a link to reset your password.</p>

                    <form id="forgot-password-form" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="admin@example.com" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100" id="submit-btn">Send Reset Link</button>
                    </form>

                    <div class="text-center mt-3">
                        <a href="login.php">Back to Login</a>
                    </div>
                </div>

                <script>
                document.getElementById('forgot-password-form').addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const email = document.getElementById('email').value;
                    const submitBtn = document.getElementById('submit-btn');
                    const messageContainer = document.getElementById('message-container');
                    const csrfToken = document.querySelector('input[name="csrf_token"]').value;
                    
                    // Validate email
                    if (!email || !email.includes('@')) {
                        messageContainer.innerHTML = '<div class="alert alert-danger">Please enter a valid email address</div>';
                        return;
                    }
                    
                    // Disable button and show loading state
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Sending...';
                    messageContainer.innerHTML = '';
                    
                    try {
                        // First, verify the admin exists and get their details
                        const response = await fetch('ajax/verify_admin_email.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ email: email })
                        });
                        
                        const result = await response.json();
                        
                        if (!result.success) {
                            messageContainer.innerHTML = '<div class="alert alert-success">If an account exists with this email, you will receive a password reset link shortly.</div>';
                            setTimeout(() => {
                                window.location.href = 'login.php';
                            }, 3000);
                            return;
                        }
                        
                        // If admin exists, send the reset email
                        const sendEmailResponse = await fetch('ajax/send_password_reset.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                email: email,
                                adminName: result.name,
                                resetLink: result.resetLink
                            })
                        });
                        
                        const emailResult = await sendEmailResponse.json();
                        
                        if (emailResult.success) {
                            messageContainer.innerHTML = '<div class="alert alert-success">Password reset link has been sent to your email address. Please check your inbox and follow the instructions.</div>';
                            document.getElementById('forgot-password-form').reset();
                            setTimeout(() => {
                                window.location.href = 'login.php';
                            }, 3000);
                        } else {
                            messageContainer.innerHTML = '<div class="alert alert-danger">' + (emailResult.message || 'Unable to send reset email. Please try again later.') + '</div>';
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        messageContainer.innerHTML = '<div class="alert alert-danger">An error occurred. Please try again later.</div>';
                    } finally {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = 'Send Reset Link';
                    }
                });
                </script>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
