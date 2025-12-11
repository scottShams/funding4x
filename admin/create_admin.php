<?php
// Create Admin - accessible only to superadmin
ini_set('session.gc_maxlifetime', 25200);
session_set_cookie_params(25200);
session_start();
require_once 'functions/auth.php';
require_once '../database.php';

checkAdminAuth();

$pdo = getPDO();
$currentAdminId = $_SESSION['admin_id'] ?? null;

// Fetch current admin role
$isSuper = false;
if ($currentAdminId) {
    try {
        $stmt = $pdo->prepare("SELECT role FROM admins WHERE id = ?");
        $stmt->execute([$currentAdminId]);
        $row = $stmt->fetch();
        if ($row && isset($row['role']) && $row['role'] === 'super_admin') {
            $isSuper = true;
        }
    } catch (PDOException $e) {
        error_log('Create Admin: failed to fetch admin role: ' . $e->getMessage());
    }
}

if (!$isSuper) {
    // Not authorized
    header('Location: index.php');
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = in_array($_POST['role'] ?? 'admin', ['admin','super_admin']) ? $_POST['role'] : 'admin';

    // Basic validation
    if (empty($name) || empty($email) || empty($password)) {
        $message = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please provide a valid email address.';
    } elseif (strlen($password) < 8) {
        $message = 'Password must be at least 8 characters long.';
    } else {
        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $message = 'An admin with that email already exists.';
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO admins (name, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, $hashed, $role]);
                $message = 'New admin created successfully.';
            }
        } catch (PDOException $e) {
            error_log('Create Admin Error: ' . $e->getMessage());
            $message = 'Database error occurred. Please try again later.';
        }
    }
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 mx-auto mt-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4>Create Admin</h4>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                            <small class="form-text text-muted">Minimum 8 characters</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select">
                                <option value="admin">Admin</option>
                                <option value="super_admin">Super Admin</option>
                            </select>
                        </div>
                        <button class="btn btn-primary">Create Admin</button>
                        <a href="dashboard.php" class="btn btn-secondary ms-2">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<?php
// end create_admin.php
?>
