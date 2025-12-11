<?php
// require_once 'functions/auth.php';
// checkAdminAuth();
require_once '../database.php';

// Get database connection
$pdo = getPDO();

// Admin details
$name = 'Admin';
$email = 'admin@gmail.com';
$country = 'Admin';
$password_hash = password_hash('admin123', PASSWORD_DEFAULT);

// Role for initial account (super admin)
$role = 'superadmin';

// Check if admin already exists
$stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    $message = 'Admin account already exists.';
} else {
    try {
        // Insert admin. If the `role` column doesn't exist yet, fall back to inserting without it.
        try {
            $stmt = $pdo->prepare("INSERT INTO admins (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $password_hash, $role]);
        } catch (PDOException $inner) {
            // Fallback for older schema: try without role column
            $stmt = $pdo->prepare("INSERT INTO admins (name, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$name, $email, $password_hash]);
        }

        $message = 'Admin account created successfully!<br>Email: ' . $email . '<br>Password: admin123';
    } catch (PDOException $e) {
        $message = 'Error creating admin account: ' . $e->getMessage();
    }
}
?>

<?php
ob_start();
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Setup Admin Account</h1>
</div>

<div class="card">
    <div class="card-body">
        <?php if (isset($message)): ?>
            <div class="alert alert-info" role="alert">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="text-center">
            <a href="login.php" class="btn btn-primary">Go to Login</a>
            <a href="index.php" class="btn btn-outline-light ms-2">Back to Dashboard</a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'layout/app.php';
?>