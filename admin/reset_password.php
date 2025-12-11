<?php
// Set session lifetime to 7 hours (25200 seconds)
ini_set('session.gc_maxlifetime', 25200);
session_set_cookie_params(25200);
session_start();
require_once '../database.php';

$message = '';
$message_type = ''; // 'success' or 'danger'
$token = $_GET['token'] ?? '';
$admin = null;
$show_form = false;

// Validate token if provided
if ($token) {
    try {
        $pdo = getPDO();

        // Check if token exists and is not expired
        $stmt = $pdo->prepare("
            SELECT id, name, email, password_reset_expires
            FROM admins 
            WHERE password_reset_token = ?
        ");
        $stmt->execute([$token]);
        $admin = $stmt->fetch();

        if (!$admin) {
            $message = 'Invalid or expired reset link. Please request a new password reset.';
            $message_type = 'danger';
        } elseif (strtotime($admin['password_reset_expires']) < time()) {
            $message = 'Password reset link has expired. Please request a new one.';
            $message_type = 'danger';
        } else {
            $show_form = true;
        }
    } catch (PDOException $e) {
        $message = 'Database error occurred. Please try again later.';
        $message_type = 'danger';
        error_log("Reset Password Token Check Error: " . $e->getMessage());
    }
} else {
    $message = 'Invalid reset link. Please request a new password reset.';
    $message_type = 'danger';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $show_form) {
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validate passwords
    if (empty($password) || empty($password_confirm)) {
        $message = 'Both password fields are required.';
        $message_type = 'danger';
    } elseif (strlen($password) < 8) {
        $message = 'Password must be at least 8 characters long.';
        $message_type = 'danger';
    } elseif ($password !== $password_confirm) {
        $message = 'Passwords do not match. Please try again.';
        $message_type = 'danger';
    } else {
        try {
            $pdo = getPDO();

            // Verify token is still valid
            $stmt = $pdo->prepare("
                SELECT id, password_reset_expires
                FROM admins 
                WHERE password_reset_token = ? AND id = ?
            ");
            $stmt->execute([$token, $admin['id']]);
            $verification = $stmt->fetch();

            if (!$verification || strtotime($verification['password_reset_expires']) < time()) {
                $message = 'Password reset link has expired. Please request a new one.';
                $message_type = 'danger';
                $show_form = false;
            } else {
                // Update password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("
                    UPDATE admins 
                    SET password = ?, password_reset_token = NULL, password_reset_expires = NULL
                    WHERE id = ?
                ");
                $stmt->execute([$hashedPassword, $admin['id']]);

                $message = 'Password has been reset successfully! Redirecting to login...';
                $message_type = 'success';
                $show_form = false;

                // Redirect to login page after 3 seconds
                header('refresh:3;url=login.php');
            }
        } catch (PDOException $e) {
            $message = 'Database error occurred. Please try again later.';
            $message_type = 'danger';
            error_log("Reset Password Update Error: " . $e->getMessage());
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-header bg-primary text-white text-center">
                    <h4>Reset Password</h4>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>

                    <?php if ($show_form): ?>
                        <p class="text-muted small mb-4">Enter your new password below. Password must be at least 8 characters long.</p>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Enter new password" required>
                                <small class="form-text text-muted">Minimum 8 characters</small>
                            </div>
                            <div class="mb-3">
                                <label for="password_confirm" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="password_confirm" name="password_confirm" placeholder="Confirm new password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                        </form>
                    <?php else: ?>
                        <div class="text-center mt-3">
                            <a href="login.php" class="btn btn-primary">Go to Login</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
