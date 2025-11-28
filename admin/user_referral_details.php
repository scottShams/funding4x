<?php
require_once 'functions/auth.php';
checkAdminAuth();
require_once '../database.php';

// Get database connection
$pdo = getPDO();

// Get user ID from URL parameter
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$userId) {
    header('Location: knowledge_tests.php');
    exit;
}

// Get user details
$stmt = $pdo->prepare("SELECT * FROM waitlist_users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: knowledge_tests.php');
    exit;
}

// Get list of referrals (users who were referred by this user) with email verification status
$stmt = $pdo->prepare("
    SELECT 
        id,
        name,
        country,
        user_ip,
        status,
        quiz_result,
        user_credit,
        created_at,
        email_verified,
        (SELECT COUNT(*) FROM waitlist_users AS c WHERE c.parent_user_id = waitlist_users.id)
        AS child_count

    FROM waitlist_users
    WHERE parent_user_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$user['id']]);
$referrals = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Calculate verified vs pending referrals
$totalReferrals = count($referrals);
$verifiedReferrals = 0;
$pendingReferrals = 0;

foreach ($referrals as $referral) {
    if ($referral['email_verified'] == 1 && $referral['quiz_result'] != null && $referral['user_ip'] !== $user['user_ip']) {
        $verifiedReferrals++;
    } else {
        $pendingReferrals++;
    }
}
?>

<?php
ob_start();
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Referral Details for <?php echo htmlspecialchars($user['name']); ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="knowledge_tests.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Knowledge Tests
        </a>
    </div>
</div>

<!-- User Info Card -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h5 class="card-title">User Information</h5>
                <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
                <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p class="mb-1"><strong>Country:</strong> <?php echo htmlspecialchars($user['country'] ?? 'N/A'); ?></p>
                <p class="mb-1"><strong>Status:</strong>
                    <span class="badge <?php echo $user['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                        <?php echo ucfirst($user['status']); ?>
                    </span>
                </p>
            </div>
            <div class="col-md-6">
                <h5 class="card-title">Referral Statistics</h5>
                <p class="mb-1"><strong>Total Referrals:</strong> <?php echo $totalReferrals; ?></p>
                <p class="mb-1"><strong>Verified Referrals:</strong> <span class="text-success"><?php echo $verifiedReferrals; ?></span></p>
                <p class="mb-1"><strong>Pending Referrals:</strong> <span class="text-warning"><?php echo $pendingReferrals; ?></span></p>
                <p class="mb-1"><strong>User Credits:</strong> <?php echo $user['user_credit'] ?? 0; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Referral Status Table -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Referral List (<?php echo $totalReferrals; ?>)</h5>
    </div>
    <div class="card-body">
        <?php if (empty($referrals)): ?>
            <!-- No referrals yet -->
            <div class="text-center py-5">
                <div class="text-6xl mb-4">👥</div>
                <h4 class="text-xl font-bold text-gray-600 mb-2">No Referrals Yet</h4>
                <p class="text-gray-500">This user hasn't referred anyone yet.</p>
            </div>
        <?php else: ?>
            <!-- Has referrals -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Referred Trader</th>
                            <th>Country</th>
                            <th>Is Trader</th>
                            <th>Is Real</th>
                            <th>Is Verified</th>
                            <th>Status</th>
                            <th>Total Referrals</th>
                            <th>Credit</th>
                            <th>Joined Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($referrals as $referral): ?>
                            <?php
                                $isVerified = ($referral['email_verified'] == 1 && $referral['quiz_result'] != null && $referral['user_ip'] !== $user['user_ip']);
                            ?>
                            <tr>
                                <!-- Name -->
                                <td><a href="user_referral_details.php?id=<?php echo $referral['id']; ?>" class="text-decoration-none"><?php echo ucfirst(htmlspecialchars($referral['name'])); ?></a></td>
                                <td><?php echo htmlspecialchars($referral['country']); ?></td>

                                <!-- Trader / Non Trader -->
                                <td>
                                    <?php if (!empty($referral['quiz_result'])): ?>
                                        <span class="badge bg-success">Trader</span>
                                    <?php elseif(empty($referral['quiz_result']) && $referral['status'] === 'inactive'): ?>
                                        <span class="badge bg-danger">Non Trader</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Unknown</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Fake / Real -->
                                <td>
                                    <?php if ($referral['user_ip'] === $user['user_ip']): ?>
                                        <span class="badge bg-danger">Fake</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Real</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Verification Status -->
                                <td>
                                    <?php if ($isVerified): ?>
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle me-1"></i>Verified
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">
                                            <i class="bi bi-x me-1"></i>Unverified
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Status -->
                                <td>
                                    <?php if ($referral['status'] === 'active' && !empty($referral['quiz_result'])): ?>
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle me-1"></i>Completed
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">
                                            <i class="bi bi-clock me-1"></i>Pending
                                        </span>
                                    <?php endif; ?>
                                </td>
                                
                                <!-- Total Referrals -->
                                <td><?php echo $referral['child_count']; ?></td>
                                
                                <!-- Credit -->
                                <td><?php echo htmlspecialchars($referral['user_credit']); ?></td>

                                <!-- Joined Date -->
                                <td><?php echo date('M d, Y', strtotime($referral['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="mt-4">
            <p class="text-sm text-muted">
                <strong>Status Definition:</strong> Each successful referral who registers using the link and verifies their email earns the referrer 1 Credit.
                Only verified referrals count towards credits.
            </p>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'layout/app.php';
?>

<style>
.table th {
    vertical-align: middle;
}
.table td {
    vertical-align: middle;
}
</style>