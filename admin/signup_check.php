<?php
// Set session lifetime to 7 hours (25200 seconds)
ini_set('session.gc_maxlifetime', 25200);
session_set_cookie_params(25200);
session_start();
require_once '../database.php';

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        $message = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Invalid email format';
    } elseif (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters long';
    } elseif ($password !== $confirm_password) {
        $message = 'Passwords do not match';
    } else {
        try {
            // Get database connection
            $pdo = getPDO();

            // Check if admin already exists
            $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $message = 'Admin account with this email already exists';
            } else {
                // Hash password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                // Insert admin
                $stmt = $pdo->prepare("INSERT INTO admins (name, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$name, $email, $password_hash]);

                $message = 'Admin account created successfully! <a href="login.php">Click here to login</a>.';
                $success = true;
            }
        } catch (PDOException $e) {
            $message = 'Database error occurred';
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white text-center">
                    <h4>Admin Signup</h4>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert <?php echo $success ? 'alert-success' : 'alert-danger'; ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" required
                                   value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Create Admin Account</button>
                    </form>

                    <div class="text-center mt-3">
                        <a href="login.php">Already have an account? Login here</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>