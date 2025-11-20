<?php
// Include database connection
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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Admin Account</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-white">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6">
                <div class="card bg-secondary">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4">Setup Admin Account</h2>
                        
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
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>