<?php
require_once 'functions/auth.php';
checkAdminAuth();
require_once '../database.php';

// Get database connection
$pdo = getPDO();

// Admin details
$name = 'Admin';
$email = 'admin@gmail.com';
$country = 'Admin';
$password_hash = password_hash('admin123', PASSWORD_DEFAULT);

// Check if admin already exists
$stmt = $pdo->prepare("SELECT id FROM waitlist_users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    $message = 'Admin account already exists.';
} else {
    try {
        // Insert admin
        $stmt = $pdo->prepare("INSERT INTO waitlist_users (name, email, country, password) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $country, $password_hash]);
        
        // Generate referral code for admin
        $adminId = $pdo->lastInsertId();
        $referralCode = 'ADMIN' . str_pad($adminId, 6, '0', STR_PAD_LEFT);
        
        $stmt = $pdo->prepare("UPDATE waitlist_users SET referral_code = ? WHERE id = ?");
        $stmt->execute([$referralCode, $adminId]);
        
        $message = 'Admin account created successfully!<br>Email: ' . $email . '<br>Password: admin123<br>Referral Code: ' . $referralCode;
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