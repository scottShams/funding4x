<?php
require_once 'functions/auth.php';
checkAdminAuth();
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();

$userId = $_GET['id'] ?? null;
if (!$userId || !is_numeric($userId)) {
    header('Location: users.php');
    exit;
}

// Get user details
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: users.php');
    exit;
}

// Get user's uploads (bank statements)
$stmt = $db->prepare("SELECT * FROM uploads WHERE user_id = ? ORDER BY upload_date DESC");
$stmt->execute([$userId]);
$uploads = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php
ob_start();
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">User Details: <?php echo htmlspecialchars($user['name']); ?></h1>
    <a href="users.php" class="btn btn-secondary">Back to Users</a>
</div>

<!-- User Information -->
<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5>User Information</h5>
            </div>
            <div class="card-body">
                <p><strong>ID:</strong> <?php echo $user['id']; ?></p>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><strong>Verified:</strong>
                    <?php if ($user['email_verified']): ?>
                        <span class="badge bg-success">Yes</span>
                    <?php else: ?>
                        <span class="badge bg-warning">No</span>
                    <?php endif; ?>
                </p>
                <p><strong>Income:</strong> $<?php echo number_format($user['income'], 2); ?></p>
                <p><strong>Age:</strong> <?php echo $user['age'] ?? 'Not provided'; ?></p>
                <p><strong>Country:</strong> <?php echo $user['country'] ?? 'Not provided'; ?></p>
                <p><strong>Occupation:</strong> <?php echo htmlspecialchars($user['occupation'] ?? 'Not provided'); ?></p>
                <p><strong>Gender:</strong> <?php echo htmlspecialchars($user['gender'] ?? 'Not provided'); ?></p>
                <p><strong>Motivation:</strong> <?php echo htmlspecialchars($user['motivation'] ?? 'Not provided'); ?></p>
                <p><strong>Blueprint Unlocked:</strong> <?php echo $user['blueprint_unlocked'] ? 'Yes' : 'No'; ?></p>
                <p><strong>Analysis Count:</strong> <?php echo $user['analysis_count']; ?></p>
                <p><strong>Additional Credits:</strong> <?php echo $user['additional_credits']; ?></p>
                <p><strong>Created:</strong> <?php echo date('Y-m-d H:i:s', strtotime($user['created_at'])); ?></p>
                <p><strong>Last Login:</strong> <?php echo $user['last_login'] ? date('Y-m-d H:i:s', strtotime($user['last_login'])) : 'Never'; ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5>Bank Statements Summary</h5>
            </div>
            <div class="card-body">
                <?php if (empty($uploads)): ?>
                    <p class="text-muted">No bank statements uploaded yet.</p>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($uploads as $upload): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6 class="card-title"><?php echo htmlspecialchars(basename($upload['filename'])); ?></h6>
                                    <p class="card-text">
                                        <small class="text-muted">
                                            Uploaded: <?php echo date('M d, Y H:i', strtotime($upload['upload_date'])); ?>
                                        </small>
                                    </p>
                                    <div class="mb-2">
                                        <span class="badge bg-info">Analysis: <?php echo $upload['analysis_result'] ? 'Completed' : 'Pending'; ?></span>
                                    </div>
                                    <div class="mb-2">
                                        <span class="badge bg-success">Blueprint: <?php echo $upload['blueprint_result'] ? 'Generated' : 'Not Generated'; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include 'layout/app.php';
?>