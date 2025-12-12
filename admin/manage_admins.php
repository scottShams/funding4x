<?php
require_once 'functions/auth.php';
checkAdminAuth();
checkSuperAdmin();

require_once '../database.php';

$message = '';
$success = false;

// Get database connection
$pdo = getPDO();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create_admin'])) {
        // Create new admin
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($name) || empty($email) || empty($password)) {
            $message = 'All fields are required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Please enter a valid email address';
        } elseif (strlen($password) < 6) {
            $message = 'Password must be at least 6 characters long';
        } else {
            try {
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $message = 'An admin with this email already exists';
                } else {
                    // Create admin
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO admins (name, email, password, role) VALUES (?, ?, ?, 'admin')");
                    $stmt->execute([$name, $email, $password_hash]);
                    $message = 'Admin account created successfully!';
                    $success = true;
                }
            } catch (PDOException $e) {
                $message = 'Database error occurred: ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['delete_admin'])) {
        // Delete admin
        $admin_id = (int)$_POST['admin_id'];

        // Don't allow deleting super admin or current admin
        if ($admin_id === $_SESSION['admin_id']) {
            $message = 'You cannot delete your own account';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT role FROM admins WHERE id = ?");
                $stmt->execute([$admin_id]);
                $admin = $stmt->fetch();

                if ($admin && $admin['role'] === 'super_admin') {
                    $message = 'Cannot delete super admin account';
                } else {
                    $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ? AND role != 'super_admin'");
                    $stmt->execute([$admin_id]);
                    $message = 'Admin account deleted successfully!';
                    $success = true;
                }
            } catch (PDOException $e) {
                $message = 'Database error occurred: ' . $e->getMessage();
            }
        }
    }
}

// Get all admins
try {
    $stmt = $pdo->prepare("SELECT id, name, email, role, created_at FROM admins ORDER BY created_at DESC");
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $admins = [];
}
?>

<?php
ob_start();
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Manage Admins</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAdminModal">
        <i class="bi bi-plus-circle"></i> Create New Admin
    </button>
</div>

<?php if ($message): ?>
<div class="alert <?php echo $success ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show" role="alert">
    <?php echo htmlspecialchars($message); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $admin): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($admin['name']); ?></td>
                        <td><?php echo htmlspecialchars($admin['email']); ?></td>
                        <td>
                            <span class="badge <?php echo $admin['role'] === 'super_admin' ? 'bg-danger' : 'bg-secondary'; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $admin['role'])); ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($admin['created_at'])); ?></td>
                        <td>
                            <?php if ($admin['role'] !== 'super_admin' && $admin['id'] !== $_SESSION['admin_id']): ?>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this admin?')">
                                <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                <button type="submit" name="delete_admin" class="btn btn-sm btn-danger">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </form>
                            <?php else: ?>
                            <span class="text-muted">Cannot delete</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Admin Modal -->
<div class="modal fade" id="createAdminModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Admin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required minlength="6">
                        <div class="form-text">Password must be at least 6 characters long</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_admin" class="btn btn-primary">Create Admin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'layout/app.php';
?>