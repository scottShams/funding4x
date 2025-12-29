<?php
require_once 'functions/auth.php';
checkAdminAuth();
require_once 'functions/audit.php';
require_once '../database.php';

// Get database connection
$pdo = getPDO();

// Handle status update action
if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
    header('Content-Type: application/json');

    $mt5Id = (int)$_POST['mt5_id'];
    $newStatus = $_POST['status'];

    // Validate status
    if (!in_array($newStatus, ['pass', 'fail', 'pending', 'running', 'under_review'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }

    // Handle fail reasons
    $failReasons = isset($_POST['fail_reasons']) ? $_POST['fail_reasons'] : [];

    // Get target user id for logging
    $userIdStmt = $pdo->prepare("SELECT user_id FROM mt5_details_second WHERE id = ?");
    $userIdStmt->execute([$mt5Id]);
    $targetUserRow = $userIdStmt->fetch(PDO::FETCH_ASSOC);
    $targetUserId = $targetUserRow['user_id'] ?? null;

    // Update status
    if ($newStatus === 'fail') {
        $failReasonJson = json_encode($failReasons);
        $stmt = $pdo->prepare("UPDATE mt5_details_second SET status = ?, fail_reason = ?, status_updated_at = NOW() WHERE id = ?");
        $success = $stmt->execute([$newStatus, $failReasonJson, $mt5Id]);
        if (!$success) {
            error_log("Failed to update mt5_details_second for fail status. MT5 ID: $mt5Id, Error: " . implode(", ", $stmt->errorInfo()));
        }
    } else {
        $stmt = $pdo->prepare("UPDATE mt5_details_second SET status = ?, status_updated_at = NOW() WHERE id = ?");
        $success = $stmt->execute([$newStatus, $mt5Id]);
        if (!$success) {
            error_log("Failed to update mt5_details_second for $newStatus status. MT5 ID: $mt5Id, Error: " . implode(", ", $stmt->errorInfo()));
        }
    }

    if ($success) {
        // Send email if status is "running", "fail", or "pass"
        $emailSent = true;
        if ($newStatus === 'running') {
            // Get user email for sending notification
            $userStmt = $pdo->prepare("SELECT u.email, u.name FROM mt5_details_second m JOIN waitlist_users u ON m.user_id = u.id WHERE m.id = ?");
            $userStmt->execute([$mt5Id]);
            $userData = $userStmt->fetch(PDO::FETCH_ASSOC);

            if ($userData) {
                require_once '../email_verification.php';
                $emailSent = EmailVerification::sendAccountReadyEmail($userData['email'], $userData['name']);
            }
        } elseif ($newStatus === 'fail') {
            // Get user email and fail reasons for sending notification
            $userStmt = $pdo->prepare("SELECT u.email, u.name, m.fail_reason FROM mt5_details_second m JOIN waitlist_users u ON m.user_id = u.id WHERE m.id = ?");
            $userStmt->execute([$mt5Id]);
            $userData = $userStmt->fetch(PDO::FETCH_ASSOC);

            if ($userData) {
                require_once '../email_verification.php';
                $failReasons = json_decode($userData['fail_reason'], true) ?: [];

                // Handle file upload
                $attachmentPath = null;
                if (isset($_FILES['failFile']) && $_FILES['failFile']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = __DIR__ . '/testResults/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $fileName = uniqid() . '_' . basename($_FILES['failFile']['name']);
                    $filePath = $uploadDir . $fileName;
                    if (move_uploaded_file($_FILES['failFile']['tmp_name'], $filePath)) {
                        $attachmentPath = $filePath;
                    }
                }

                $emailSent = EmailVerification::sendFailEmail($userData['email'], $userData['name'], $failReasons, $attachmentPath);
            }
        } elseif ($newStatus === 'pass') {
            // Get user email for sending pass notification
            $userStmt = $pdo->prepare("SELECT u.email, u.name FROM mt5_details_second m JOIN waitlist_users u ON m.user_id = u.id WHERE m.id = ?");
            $userStmt->execute([$mt5Id]);
            $userData = $userStmt->fetch(PDO::FETCH_ASSOC);

            if ($userData) {
                require_once '../email_verification.php';

                // Handle pass certificate file upload
                $attachmentPath = null;
                if (isset($_FILES['passCertificateFile']) && $_FILES['passCertificateFile']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = __DIR__ . '/testResults/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $fileName = uniqid() . '_pass_' . basename($_FILES['passCertificateFile']['name']);
                    $filePath = $uploadDir . $fileName;
                    if (move_uploaded_file($_FILES['passCertificateFile']['tmp_name'], $filePath)) {
                        $attachmentPath = $filePath;
                    }
                }

                $emailSent = EmailVerification::sendPassEmail($userData['email'], $userData['name'], $attachmentPath);
            }
        } elseif ($newStatus === 'under_review') {
            // Get user email for sending under review notification
            $userStmt = $pdo->prepare("SELECT u.email, u.name FROM mt5_details_second m JOIN waitlist_users u ON m.user_id = u.id WHERE m.id = ?");
            $userStmt->execute([$mt5Id]);
            $userData = $userStmt->fetch(PDO::FETCH_ASSOC);

            if ($userData) {
                require_once '../email_verification.php';

                // Handle under review file upload (multiple files)
                $attachmentPaths = [];
                if (isset($_FILES['underReviewFile'])) {
                    $uploadDir = __DIR__ . '/testResults/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $files = $_FILES['underReviewFile'];
                    $fileCount = count($files['name']);
                    for ($i = 0; $i < $fileCount; $i++) {
                        if ($files['error'][$i] === UPLOAD_ERR_OK) {
                            $fileName = uniqid() . '_under_review_' . basename($files['name'][$i]);
                            $filePath = $uploadDir . $fileName;
                            if (move_uploaded_file($files['tmp_name'][$i], $filePath)) {
                                $attachmentPaths[] = $filePath;
                            }
                        }
                    }
                }

                // Send under review email with attachments
                $emailSent = EmailVerification::sendUnderReviewEmail($userData['email'], $userData['name'], $attachmentPaths);
            }
        }

        // Record audit
        $adminId = $_SESSION['admin_id'] ?? null;
        $details = ['mt5_id' => $mt5Id, 'new_status' => $newStatus];
        if ($newStatus === 'fail') {
            $details['fail_reasons'] = $failReasons;
        }
        if ($newStatus === 'pass') {
            $details['attachments'] = isset($attachmentPath) ? 1 : 0;
        }
        if ($newStatus === 'under_review') {
            $details['attachments'] = isset($attachmentPaths) ? count($attachmentPaths) : 0;
        }
        recordAdminAction($pdo, $adminId, 'update_status', $targetUserId, $details);

        echo json_encode([
            'success' => true,
            'message' => 'Status updated successfully',
            'email_sent' => $emailSent
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }
    exit;
}

// Handle add credit action
if (isset($_POST['action']) && $_POST['action'] === 'add_credit') {
    header('Content-Type: application/json');

    $userId = (int)$_POST['user_id'];

    // Increment user_credit by 1 and mark as manual update
    $stmt = $pdo->prepare("UPDATE waitlist_users SET user_credit = user_credit + 1, manual_credit_update = 1, credit_updated_at = NOW() WHERE id = ?");
    $success = $stmt->execute([$userId]);

    if ($success) {
        // Get new credit value
        $stmt = $pdo->prepare("SELECT user_credit FROM waitlist_users WHERE id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Record audit
        $adminId = $_SESSION['admin_id'] ?? null;
        recordAdminAction($pdo, $adminId, 'add_credit', $userId, ['new_credit' => $result['user_credit']]);

        echo json_encode(['success' => true, 'message' => 'Credit added successfully', 'new_credit' => $result['user_credit']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add credit']);
    }
    exit;
}

// Handle remove credit action
if (isset($_POST['action']) && $_POST['action'] === 'remove_credit') {
    header('Content-Type: application/json');

    $userId = (int)$_POST['user_id'];

    // Decrement user_credit by 1, but not below 0 and mark as manual update
    $stmt = $pdo->prepare("UPDATE waitlist_users SET user_credit = GREATEST(0, user_credit - 1), manual_credit_update = 1, credit_updated_at = NOW() WHERE id = ?");
    $success = $stmt->execute([$userId]);

    if ($success) {
        // Get new credit value
        $stmt = $pdo->prepare("SELECT user_credit FROM waitlist_users WHERE id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Record audit
        $adminId = $_SESSION['admin_id'] ?? null;
        recordAdminAction($pdo, $adminId, 'remove_credit', $userId, ['new_credit' => $result['user_credit']]);

        echo json_encode(['success' => true, 'message' => 'Credit removed successfully', 'new_credit' => $result['user_credit']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove credit']);
    }
    exit;
}

// Handle test type update action
if (isset($_POST['action']) && $_POST['action'] === 'update_test_type') {
    header('Content-Type: application/json');

    $mt5Id = (int)$_POST['mt5_id'];
    $newTestType = $_POST['test_type'];

    // Validate test type
    $validTestTypes = ['50:50 F4x', '20:80 F4x', '10:90 F4x', '80:20 F4x', '70:30 F4x', '60:40 F4x'];
    if (!in_array($newTestType, $validTestTypes)) {
        echo json_encode(['success' => false, 'message' => 'Invalid test type']);
        exit;
    }

    // Get target user id for logging
    $userIdStmt = $pdo->prepare("SELECT user_id FROM mt5_details_second WHERE id = ?");
    $userIdStmt->execute([$mt5Id]);
    $targetUserRow = $userIdStmt->fetch(PDO::FETCH_ASSOC);
    $targetUserId = $targetUserRow['user_id'] ?? null;

    // Update test type
    $stmt = $pdo->prepare("UPDATE mt5_details_second SET test_type = ? WHERE id = ?");
    $success = $stmt->execute([$newTestType, $mt5Id]);

    if ($success) {
        // Record audit
        $adminId = $_SESSION['admin_id'] ?? null;
        recordAdminAction($pdo, $adminId, 'update_test_type', $targetUserId, ['mt5_id' => $mt5Id, 'new_test_type' => $newTestType]);

        echo json_encode(['success' => true, 'message' => 'Test type updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update test type']);
    }
    exit;
}

// Handle make payment action
if (isset($_POST['action']) && $_POST['action'] === 'make_payment') {
    header('Content-Type: application/json');

    $userId = (int)$_POST['user_id'];
    $amount = (float)$_POST['amount'];
    $paymentDate = $_POST['payment_date'];
    $transactionHash = $_POST['transaction_hash'];
    $description = $_POST['description'] ?? '';

    // Validate required fields
    if (empty($amount) || empty($paymentDate) || empty($transactionHash)) {
        echo json_encode(['success' => false, 'message' => 'Amount, payment date, and transaction hash are required']);
        exit;
    }

    try {
        // Insert payment record
        $stmt = $pdo->prepare("INSERT INTO payments (
            user_id, payment_method, amount, currency, status, crypto_type, crypto_network,
            transaction_hash, notes, payment_source, created_by_admin_id, created_at
        ) VALUES (?, 'crypto', ?, 'USD', 'completed', 'USDC', 'Tron', ?, ?, 'admin', ?, ?)");

        $adminId = $_SESSION['admin_id'] ?? null;
        $success = $stmt->execute([
            $userId,
            $amount,
            $transactionHash,
            $description,
            $adminId,
            $paymentDate
        ]);

        if ($success) {
            
            $stmt = $pdo->prepare("UPDATE waitlist_users SET paid_user = 1 WHERE id = ?");
            $stmt->execute([$userId]);
            
            // Record audit
            $adminId = $_SESSION['admin_id'] ?? null;
            recordAdminAction($pdo, $adminId, 'make_payment', $userId, [
                'amount' => $amount,
                'transaction_hash' => $transactionHash,
                'description' => $description
            ]);

            echo json_encode(['success' => true, 'message' => 'Payment recorded successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to record payment']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle send MT5 issue email action
if (isset($_POST['action']) && $_POST['action'] === 'send_mt5_issue_email') {
header('Content-Type: application/json');

    $mt5Id = (int)$_POST['mt5_id'];
    $challengeId = $_POST['challenge_id'];
    $issueType = $_POST['issue_type'];

    // Get user email and user_id
    $userStmt = $pdo->prepare("SELECT u.email, u.name, m.user_id FROM mt5_details_second m JOIN waitlist_users u ON m.user_id = u.id WHERE m.id = ?");
    $userStmt->execute([$mt5Id]);
    $userData = $userStmt->fetch(PDO::FETCH_ASSOC);

    if ($userData) {
        require_once '../email_verification.php';
        if($issueType === 'login_problem') {
            $emailSent = EmailVerification::sendMt5LoginProblemEmail($userData['email'], $userData['name'], $challengeId, 'mt5_details_second.php');
        } elseif($issueType === 'wrong_balance') {
            $emailSent = EmailVerification::sendMt5IssueEmail($userData['email'], $userData['name'], $challengeId, 'mt5_details_second.php');
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid issue type']);
            exit;
        }

        // Record audit
        $adminId = $_SESSION['admin_id'] ?? null;
        recordAdminAction($pdo, $adminId, 'send_mt5_issue_email', $userData['user_id'], ['mt5_id' => $mt5Id, 'challenge_id' => $challengeId]);

        echo json_encode(['success' => true, 'message' => 'Email sent successfully', 'email_sent' => $emailSent]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
    exit;
}

// ----------------------
// CSV EXPORT
// ----------------------
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Prepare query to fetch all MT5 details second
    $export_query = "
        SELECT
            m.*,
            u.name,
            u.email,
            u.user_credit
        FROM mt5_details_second m
        JOIN waitlist_users u ON m.user_id = u.id
        ORDER BY m.submitted_at DESC
    ";
    $export_stmt = $pdo->prepare($export_query);
    $export_stmt->execute();
    $export_mt5_details = $export_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Set CSV headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=mt5_details_second_' . date('Y-m-d_H-i-s') . '.csv');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Open output stream
    $output = fopen('php://output', 'w');

    // Add UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // CSV headers
    fputcsv($output, ['ID', 'User Name', 'User Email', 'MT5 Username', 'MT5 Password', 'Server', 'Instrument', 'Status', 'Submitted At']);

    // Loop through MT5 details and write rows
    foreach ($export_mt5_details as $detail) {
        fputcsv($output, [
            $detail['id'] ?? 'N/A',
            $detail['name'] ?? 'N/A',
            $detail['email'] ?? 'N/A',
            $detail['username'] ?? 'N/A',
            $detail['password'] ?? 'N/A',
            $detail['server'] ?? 'N/A',
            $detail['instrument'] ?? 'N/A',
            $detail['status'] ?? 'pending',
            !empty($detail['submitted_at'])
                ? date('d/m/Y H:i:s', strtotime($detail['submitted_at']))
                : 'N/A'
        ]);
    }

    fclose($output);
    exit;
}

// Get MT5 status counts
$mt5Stats = $pdo->query("
    SELECT
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
        SUM(CASE WHEN status = 'running' THEN 1 ELSE 0 END) AS running_count,
        SUM(CASE WHEN status = 'pass' THEN 1 ELSE 0 END) AS pass_count,
        SUM(CASE WHEN status = 'fail' THEN 1 ELSE 0 END) AS fail_count,
        COUNT(*) AS total_count
    FROM mt5_details_second
")->fetch(PDO::FETCH_ASSOC);

// Get MT5 details with user info
$query = "
    SELECT
        m.*,
        u.name,
        u.email,
        u.user_credit
    FROM mt5_details_second m
    JOIN waitlist_users u ON m.user_id = u.id
    ORDER BY m.submitted_at DESC
";
$stmt = $pdo->prepare($query);
$stmt->execute();
$mt5_details = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php
ob_start();
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Trading Test 2 MT5 Details Summary</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="?export=csv" class="btn btn-sm btn-success">
                <i class="fas fa-download"></i> Export to CSV
            </a>
        </div>
    </div>
</div>

<!-- MT5 Status Cards -->
<div class="row mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card text-white bg-warning mb-3">
            <div class="card-body">
                <h5 class="card-title">Pending</h5>
                <h2><?php echo $mt5Stats['pending_count'] ?? 0; ?></h2>
                <small>Waiting for review</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card text-white bg-primary mb-3">
            <div class="card-body">
                <h5 class="card-title">Running</h5>
                <h2><?php echo $mt5Stats['running_count'] ?? 0; ?></h2>
                <small>Currently active</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card text-white bg-success mb-3">
            <div class="card-body">
                <h5 class="card-title">Pass</h5>
                <h2><?php echo $mt5Stats['pass_count'] ?? 0; ?></h2>
                <small>Successfully completed</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card text-white bg-danger mb-3">
            <div class="card-body">
                <h5 class="card-title">Fail</h5>
                <h2><?php echo $mt5Stats['fail_count'] ?? 0; ?></h2>
                <small>Did not meet requirements</small>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="mt5Table" class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User Name</th>
                        <th>User Email</th>
                        <th>Challenge ID</th>
                        <th>Credits</th>
                        <th>MT5 Username</th>
                        <th>MT5 Password</th>
                        <th>Server</th>
                        <th>Test Type</th>
                        <th>Status</th>
                        <th>Submitted At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mt5_details as $detail): ?>
                    <tr>
                        <td><?php echo $detail['id']; ?></td>
                        <td><?php echo htmlspecialchars($detail['name']); ?></td>
                        <td><?php echo htmlspecialchars($detail['email']); ?></td>
                        <td><?php echo htmlspecialchars($detail['challenge_id']); ?></td>
                        <td>
                            <span id="credit-<?php echo $detail['user_id']; ?>"><?php echo $detail['user_credit'] ?? 0; ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($detail['username']); ?></td>
                        <td><?php echo htmlspecialchars($detail['password']); ?></td>
                        <td><?php echo htmlspecialchars($detail['server']); ?></td>
                        <td>
                            <span class="badge bg-info" id="test-type-<?php echo $detail['id']; ?>"><?php echo htmlspecialchars($detail['test_type'] ?? '50:50 F4x'); ?></span>
                        </td>
                        <td>
                            <?php
                            $status = $detail['status'] ?? 'pending';
                            $badgeClass = 'bg-warning';
                            $statusText = 'Pending';
                            if ($status === 'pass') {
                                $badgeClass = 'bg-success';
                                $statusText = 'Pass';
                            } elseif ($status === 'fail') {
                                $badgeClass = 'bg-danger';
                                $statusText = 'Fail';
                            } elseif ($status === 'running') {
                                $badgeClass = 'bg-primary';
                                $statusText = 'Running';
                            } elseif ($status === 'under_review') {
                                $badgeClass = 'bg-info';
                                $statusText = 'Under Review';
                            } elseif ($status === 'updated') {
                                $badgeClass = 'bg-secondary';
                                $statusText = 'Updated';
                            }
                            ?>
                            <span class="badge <?php echo $badgeClass; ?>"><?php echo $statusText; ?></span>
                        </td>
                        <td><?php echo date('M d, Y H:i', strtotime($detail['submitted_at'])); ?></td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton<?php echo $detail['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                    Actions
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton<?php echo $detail['id']; ?>">
                                    <li><a class="dropdown-item" href="#" onclick="openEmailModal(<?php echo $detail['user_id']; ?>, '<?php echo htmlspecialchars($detail['name']); ?>', '<?php echo htmlspecialchars($detail['email']); ?>')">
                                        <i class="bi bi-envelope me-2"></i>Send Mail
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="#" onclick="addCredit(<?php echo $detail['user_id']; ?>, '<?php echo htmlspecialchars($detail['name']); ?>')">
                                        <i class="bi bi-plus-circle me-2"></i>Add Credit
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="removeCredit(<?php echo $detail['user_id']; ?>, '<?php echo htmlspecialchars($detail['name']); ?>')">
                                        <i class="bi bi-dash-circle me-2"></i>Remove Credit
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="#" onclick="changeTestType(<?php echo $detail['id']; ?>, '<?php echo htmlspecialchars($detail['name']); ?>', '<?php echo htmlspecialchars($detail['test_type'] ?? '50:50 F4x'); ?>')">
                                        <i class="bi bi-gear me-2"></i>Change Test Type
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="#" onclick="makePayment(<?php echo $detail['user_id']; ?>, '<?php echo htmlspecialchars($detail['name']); ?>')">
                                        <i class="bi bi-credit-card me-2"></i>Make Payment
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-success" href="#" onclick="updateStatus(<?php echo $detail['id']; ?>, 'pass', '<?php echo htmlspecialchars($detail['name']); ?>')">
                                        <i class="bi bi-check-circle me-2"></i>Mark as Pass
                                    </a></li>
                                    <li><a class="dropdown-item text-primary" href="#" onclick="updateStatus(<?php echo $detail['id']; ?>, 'running', '<?php echo htmlspecialchars($detail['name']); ?>', '<?php echo htmlspecialchars($detail['email']); ?>')">
                                        <i class="bi bi-play-circle me-2"></i>Mark as Running
                                    </a></li>
                                    <li><a class="dropdown-item text-danger" href="#" onclick="updateStatus(<?php echo $detail['id']; ?>, 'fail', '<?php echo htmlspecialchars($detail['name']); ?>')">
                                        <i class="bi bi-x-circle me-2"></i>Mark as Fail
                                    </a></li>
                                    <li><a class="dropdown-item text-info" href="#" onclick="updateStatus(<?php echo $detail['id']; ?>, 'under_review', '<?php echo htmlspecialchars($detail['name']); ?>')">
                                        <i class="bi bi-clock me-2"></i>Mark as Under Review
                                    </a></li>
                                    <li><a class="dropdown-item text-warning" href="#" onclick="updateStatus(<?php echo $detail['id']; ?>, 'pending', '<?php echo htmlspecialchars($detail['name']); ?>')">
                                        <i class="bi bi-clock me-2"></i>Mark as Pending
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="#" onclick="sendMt5IssueEmail(<?php echo $detail['id']; ?>, '<?php echo htmlspecialchars($detail['name']); ?>', '<?php echo htmlspecialchars($detail['email']); ?>', '<?php echo htmlspecialchars($detail['challenge_id']); ?>', 'login_problem')">
                                        <i class="bi bi-envelope me-2"></i>MT5 Login Problem
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="sendMt5IssueEmail(<?php echo $detail['id']; ?>, '<?php echo htmlspecialchars($detail['name']); ?>', '<?php echo htmlspecialchars($detail['email']); ?>', '<?php echo htmlspecialchars($detail['challenge_id']); ?>', 'wrong_balance')">
                                        <i class="bi bi-envelope me-2"></i>MT5 Wrong Balance
                                    </a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Fail Reason Modal -->
<div class="modal fade" id="failReasonModal" tabindex="-1" aria-labelledby="failReasonModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="failReasonModalLabel">Select Fail Reasons</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="failReasonForm">
                    <div class="mb-3">
                        <label class="form-label">Select the reasons for failure:</label>
                        <div class="form-check">
                            <input class="form-check-input fail-reason" type="checkbox" value="You hit maximum daily drawn down of 5%" id="reason1">
                            <label class="form-check-label" for="reason1">
                                1. You hit maximum daily drawn down of 5%
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input fail-reason" type="checkbox" value="You had positions that had more than 1% risk" id="reason2">
                            <label class="form-check-label" for="reason2">
                                2. You had positions that had more than 1% risk
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input fail-reason" type="checkbox" value="You didn't set a Stop Loss or Target Price" id="reason3">
                            <label class="form-check-label" for="reason3">
                                3. You didn't set a Stop Loss or Target Price
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input fail-reason" type="checkbox" value="You Opened a single position that is larger than 0.1 lots" id="reason4">
                            <label class="form-check-label" for="reason4">
                                4. You Opened a single position that is larger than 0.1 lots
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input fail-reason" type="checkbox" value="Your Total open positions is larger than 0.5 lots" id="reason5">
                            <label class="form-check-label" for="reason5">
                                5. Your Total open positions is larger than 0.5 lots
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input fail-reason" type="checkbox" value="You hit the Maximum Drawn Down limit of 10%." id="reason6">
                            <label class="form-check-label" for="reason6">
                                6. You hit the Maximum Drawn Down limit of 10%.
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input fail-reason" type="checkbox" value="You did EA/robot trading" id="reason7">
                            <label class="form-check-label" for="reason7">
                                7. You did EA/robot trading
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input fail-reason" type="checkbox" value="You did Copy Trading" id="reason8">
                            <label class="form-check-label" for="reason8">
                                8. You did Copy Trading
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input fail-reason" type="checkbox" value="You Traded during critical News Time" id="reason9">
                            <label class="form-check-label" for="reason9">
                                9. You Traded during critical News Time
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input fail-reason" type="checkbox" value="You Traded something other than Forex and Metals" id="reason10">
                            <label class="form-check-label" for="reason10">
                                10. You Traded something other than Forex and Metals
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input fail-reason" type="checkbox" value="Your positions were held overnight, that is past 6pm New York Time" id="reason11">
                            <label class="form-check-label" for="reason11">
                                11. Your positions were held overnight, that is past 6pm New York Time
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="failFile" class="form-label">Attach a file (optional - PDF, DOC, DOCX, JPG, PNG, CSV, XLS, XLSX):</label>
                        <input type="file" class="form-control" id="failFile" name="failFile" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.csv,.xls,.xlsx">
                        <small class="form-text text-muted">Max file size: 5MB</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="submitFail">Mark as Fail</button>
            </div>
        </div>
    </div>
</div>

<!-- Pass Certificate Modal -->
<div class="modal fade" id="passCertificateModal" tabindex="-1" aria-labelledby="passCertificateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="passCertificateModalLabel">Upload Passing Certificate</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="passCertificateForm">
                    <div class="mb-3">
                        <label class="form-label">Please upload Passing Certificate:</label>
                        <input type="file" class="form-control" id="passCertificateFile" name="passCertificateFile" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.csv,.xls,.xlsx" required>
                        <small class="form-text text-muted">Max file size: 5MB. Accepted formats: PDF, DOC, DOCX, JPG, PNG, CSV, XLS, XLSX</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="submitPass">Mark as Pass</button>
            </div>
        </div>
    </div>
</div>

<!-- Under Review Modal -->
<div class="modal fade" id="underReviewModal" tabindex="-1" aria-labelledby="underReviewModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="underReviewModalLabel">Upload Documents</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="underReviewForm">
                    <div class="mb-3">
                        <label class="form-label">Please upload documents related to this review:</label>
                        <div class="input-group mb-2">
                            <input type="file" class="form-control" id="underReviewFile" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.csv,.xls,.xlsx">
                            <button class="btn btn-outline-primary" type="button" id="addUnderReviewFileBtn">Add File</button>
                        </div>
                        <small class="form-text text-muted">Max file size: 5MB. Accepted formats: PDF, DOC, DOCX, JPG, PNG, CSV, XLS, XLSX</small>
                    </div>
                    <div id="selectedUnderReviewFilesContainer" class="mb-3" style="display: none;">
                        <label class="form-label">Selected Files:</label>
                        <div id="selectedUnderReviewFilesList" class="border rounded p-2" style="min-height: 60px;">
                            <!-- Selected files will be displayed here -->
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-info" id="submitUnderReview">Mark as Under Review</button>
            </div>
        </div>
    </div>
</div>

<!-- Test Type Modal -->
<div class="modal fade" id="testTypeModal" tabindex="-1" aria-labelledby="testTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="testTypeModalLabel">Change Test Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="testTypeForm">
                    <div class="mb-3">
                        <label class="form-label">Select Test Type:</label>
                        <select class="form-select" id="newTestType" required>
                            <option value="50:50 F4x">50:50 F4x</option>
                            <option value="20:80 F4x">20:80 F4x</option>
                            <option value="10:90 F4x">10:90 F4x</option>
                            <option value="80:20 F4x">80:20 F4x</option>
                            <option value="70:30 F4x">70:30 F4x</option>
                            <option value="60:40 F4x">60:40 F4x</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitTestType">Update Test Type</button>
            </div>
        </div>
    </div>
</div>

// Make Payment Modal -->
<div class="modal fade" id="makePaymentModal" tabindex="-1" aria-labelledby="makePaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="makePaymentModalLabel">Make Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="makePaymentForm">
                    <div class="mb-3">
                        <input type="hidden" class="form-control" id="paymentUserName" readonly>
                        <input type="hidden" id="paymentUserId">
                    </div>
                    
                    <div class="mb-3">
                        <label for="paymentAmount" class="form-label">Amount (USD) *</label>
                        <input type="number" class="form-control" id="paymentAmount" step="0.01" min="0" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="paymentDate" class="form-label">Payment Date *</label>
                        <input type="datetime-local" class="form-control" id="paymentDate" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="transactionHash" class="form-label">Transaction Hash *</label>
                        <input type="text" class="form-control" id="transactionHash" placeholder="0xabc123..." required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="paymentDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="paymentDescription" rows="3" placeholder="Optional description"></textarea>
                        <small class="form-text text-muted">Optional notes about this payment</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitPayment">Record Payment</button>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include 'layout/app.php';
?>

<style>
/* DataTables Custom Styling */
.dataTables_wrapper .dataTables_length select {
    min-width: 80px;
    display: inline-block;
    margin: 0 10px;
}

.dataTables_wrapper .dataTables_filter input {
    margin-left: 10px;
    min-width: 250px;
}

.dataTables_wrapper .dataTables_info {
    padding-top: 1rem;
}

.dataTables_wrapper .dataTables_paginate {
    padding-top: 0.75rem;
}

.dataTables_wrapper .dataTables_paginate .paginate_button {
    padding: 0.375rem 0.75rem;
    margin: 0 2px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    background-color: #fff;
}

.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background-color: #e9ecef;
    border-color: #dee2e6;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background-color: #0d6efd;
    border-color: #0d6efd;
    color: #fff !important;
}
</style>

<script>
$(document).ready(function() {
    var table = $('#mt5Table').DataTable({
        dom: '<"row mb-3"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search Trading Test 2 MT5 details...",
            lengthMenu: "_MENU_ entries per page",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "No entries found",
            infoFiltered: "(filtered from _MAX_ total entries)"
        },
        pageLength: 10,
        lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
        ordering: true,
        order: [],
        responsive: true,
        columnDefs: [
            {
                targets: [10], // Actions column (now 10th due to Test Type column)
                orderable: false
            }
        ],
        initComplete: function () {
            $('.dataTables_length select').addClass('form-select form-select-sm');
            $('.dataTables_filter input').addClass('form-control form-control-sm');
        }
    });
});

// Update status function
function updateStatus(mt5Id, newStatus, userName, userEmail = null) {
    if (newStatus === 'fail') {
        // Show fail reason modal
        $('#failReasonModalLabel').text('Select Fail Reasons for ' + userName);
        $('#failReasonModal').modal('show');
        // Clear previous selections
        $('.fail-reason').prop('checked', false);
        // Store current data
        window.currentMt5Id = mt5Id;
        window.currentUserName = userName;
        return;
    }

    if (newStatus === 'pass') {
        // Show pass certificate modal
        $('#passCertificateModalLabel').text('Upload Passing Certificate for ' + userName);
        $('#passCertificateModal').modal('show');
        // Clear file input
        $('#passCertificateFile').val('');
        // Store current data
        window.currentMt5Id = mt5Id;
        window.currentUserName = userName;
        return;
    }

    if (newStatus === 'under_review') {
        // Show under review modal
        $('#underReviewModalLabel').text('Upload Documents for ' + userName);
        $('#underReviewModal').modal('show');
        // Clear file input and selected files
        $('#underReviewFile').val('');
        // Store current data
        window.currentMt5Id = mt5Id;
        window.currentUserName = userName;
        return;
    }

    const statusMap = {
        pass: { text: 'Pass', color: '#28a745' },
        fail: { text: 'Fail', color: '#dc3545' },
        running: { text: 'Running', color: '#0d6efd' },
        under_review: { text: 'Under Review', color: '#fd7e14' },
        pending: { text: 'Pending', color: '#ffc107' }
    };

    const { text: statusText, color: confirmColor } = statusMap[newStatus] || statusMap.pending;

    Swal.fire({
        title: `Mark as ${statusText}`,
        text: `Are you sure you want to mark "${userName}" as ${statusText.toLowerCase()}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: confirmColor,
        cancelButtonColor: '#6c757d',
        confirmButtonText: `Yes, mark as ${statusText.toLowerCase()}`,
        cancelButtonText: 'Cancel',
        customClass: {
            confirmButton: 'btn btn-primary',
            cancelButton: 'btn btn-secondary'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'Updating...',
                text: 'Please wait while we update the status',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Send AJAX request
            $.ajax({
                url: 'mt5_details_second.php',
                type: 'POST',
                data: {
                    action: 'update_status',
                    mt5_id: mt5Id,
                    status: newStatus
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Update the status badge in the same row
                        // Find the row containing this dropdown
                        const dropdownButton = document.querySelector(`#dropdownMenuButton${mt5Id}`);
                        const row = dropdownButton.closest('tr');
                        const statusCell = row.querySelector('td:nth-child(9) .badge'); // Status is 8th column

                        // Update badge class and text
                        let badgeClass = 'bg-warning';
                        let badgeText = 'Pending';
                        if (newStatus === 'pass') {
                            badgeClass = 'bg-success';
                            badgeText = 'Pass';
                        } else if (newStatus === 'fail') {
                            badgeClass = 'bg-danger';
                            badgeText = 'Fail';
                        } else if (newStatus === 'running') {
                            badgeClass = 'bg-primary';
                            badgeText = 'Running';
                        } else if (newStatus === 'under_review') {
                            badgeClass = 'bg-info';
                            badgeText = 'Under Review';
                        }

                        statusCell.className = 'badge ' + badgeClass;
                        statusCell.textContent = badgeText;

                        // Check if email was sent (for running, fail, or pass status)
                        if (newStatus === 'running' || newStatus === 'fail' || newStatus === 'pass') {
                            if (response.email_sent) {
                                Swal.fire({
                                    title: 'Success!',
                                    text: 'Status updated and email sent successfully.',
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            } else {
                                Swal.fire({
                                    title: 'Status Updated',
                                    text: 'Status updated but email sending failed.',
                                    icon: 'warning',
                                    confirmButtonText: 'OK'
                                });
                            }
                        } else {
                            if (response.email_sent) {
                                Swal.fire({
                                    title: 'Success!',
                                    text: 'Status updated and email sent successfully.',
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            } else {
                                Swal.fire({
                                    title: 'Status Updated',
                                    text: 'Status updated but email sending failed.',
                                    icon: 'warning',
                                    confirmButtonText: 'OK'
                                });
                            }
                        }
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: response.message || 'Failed to update status.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        title: 'Error!',
                        text: 'An error occurred while updating the status.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            });
        }
    });
}

// Handle fail reason submission
$(document).ready(function() {
    $('#submitFail').on('click', function() {
        const checkedReasons = [];
        $('.fail-reason:checked').each(function() {
            checkedReasons.push($(this).val());
        });
        if (checkedReasons.length === 0) {
            Swal.fire({
                title: 'No reasons selected',
                text: 'Please select at least one fail reason.',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
            return;
        }
        // Hide modal
        $('#failReasonModal').modal('hide');
        // Show loading
        Swal.fire({
            title: 'Updating...',
            text: 'Please wait while we update the status',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Prepare FormData for file upload
        const formData = new FormData();
        formData.append('action', 'update_status');
        formData.append('mt5_id', window.currentMt5Id);
        formData.append('status', 'fail');
        checkedReasons.forEach(reason => {
            formData.append('fail_reasons[]', reason);
        });

        // Add file if selected
        const fileInput = document.getElementById('failFile');
        if (fileInput.files.length > 0) {
            formData.append('failFile', fileInput.files[0]);
        }

        // Send AJAX request with FormData
        $.ajax({
            url: 'mt5_details_second.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update the status badge in the same row
                    const dropdownButton = document.querySelector(`#dropdownMenuButton${window.currentMt5Id}`);
                    const row = dropdownButton.closest('tr');
                    const statusCell = row.querySelector('td:nth-child(9) .badge'); // Status is 8th column
                    statusCell.className = 'badge bg-danger';
                    statusCell.textContent = 'Fail';

                    // Clear file input
                    $('#failFile').val('');

                    // Check if email was sent
                    if (response.email_sent) {
                        Swal.fire({
                            title: 'Success!',
                            text: 'Status updated and email sent successfully.',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            title: 'Status Updated',
                            text: 'Status updated but email sending failed.',
                            icon: 'warning',
                            confirmButtonText: 'OK'
                        });
                    }
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: response.message || 'Failed to update status.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred while updating the status.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    });
});

// Handle pass certificate submission
$(document).ready(function() {
    $('#submitPass').on('click', function() {
        const fileInput = document.getElementById('passCertificateFile');
        if (fileInput.files.length === 0) {
            Swal.fire({
                title: 'No file selected',
                text: 'Please select a passing certificate file.',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
            return;
        }
        // Hide modal
        $('#passCertificateModal').modal('hide');
        // Show loading
        Swal.fire({
            title: 'Updating...',
            text: 'Please wait while we update the status',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Prepare FormData for file upload
        const formData = new FormData();
        formData.append('action', 'update_status');
        formData.append('mt5_id', window.currentMt5Id);
        formData.append('status', 'pass');
        formData.append('passCertificateFile', fileInput.files[0]);

        // Send AJAX request with FormData
        $.ajax({
            url: 'mt5_details_second.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update the status badge in the same row
                    const dropdownButton = document.querySelector(`#dropdownMenuButton${window.currentMt5Id}`);
                    const row = dropdownButton.closest('tr');
                    const statusCell = row.querySelector('td:nth-child(9) .badge'); // Status is 8th column
                    statusCell.className = 'badge bg-success';
                    statusCell.textContent = 'Pass';

                    // Clear file input
                    $('#passCertificateFile').val('');

                    // Check if email was sent
                    if (response.email_sent) {
                        Swal.fire({
                            title: 'Success!',
                            text: 'Status updated and email sent successfully.',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            title: 'Status Updated',
                            text: 'Status updated but email sending failed.',
                            icon: 'warning',
                            confirmButtonText: 'OK'
                        });
                    }
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: response.message || 'Failed to update status.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred while updating the status.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    });
});


// Handle under review submission
$(document).ready(function() {
    let selectedUnderReviewFiles = [];

    // Handle add file button
    $('#addUnderReviewFileBtn').on('click', function() {
        const fileInput = document.getElementById('underReviewFile');
        if (fileInput.files.length === 0) {
            Swal.fire({
                title: 'No file selected',
                text: 'Please select a file first.',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
            return;
        }

        const file = fileInput.files[0];

        // Check if file already exists
        const fileExists = selectedUnderReviewFiles.some(f => f.name === file.name && f.size === file.size);
        if (fileExists) {
            Swal.fire({
                title: 'File already added',
                text: 'This file has already been added.',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
            return;
        }

        // Add file to selected files
        selectedUnderReviewFiles.push(file);

        // Clear input
        fileInput.value = '';

        // Update display
        updateUnderReviewSelectedFilesDisplay();
    });

    // Function to update selected files display
    function updateUnderReviewSelectedFilesDisplay() {
        const container = document.getElementById('selectedUnderReviewFilesContainer');
        const list = document.getElementById('selectedUnderReviewFilesList');

        if (selectedUnderReviewFiles.length > 0) {
            container.style.display = 'block';
            list.innerHTML = '';

            selectedUnderReviewFiles.forEach((file, index) => {
                const fileItem = document.createElement('div');
                fileItem.className = 'd-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded';
                fileItem.innerHTML = `
                    <span class="me-2">
                        <i class="fas fa-file me-2"></i>${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeUnderReviewFile(${index})">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                list.appendChild(fileItem);
            });
        } else {
            container.style.display = 'none';
        }
    }

    // Function to remove file (will be available globally)
    window.removeUnderReviewFile = function(index) {
        selectedUnderReviewFiles.splice(index, 1);
        updateUnderReviewSelectedFilesDisplay();
    };

    $('#submitUnderReview').on('click', function() {
        if (selectedUnderReviewFiles.length === 0) {
            Swal.fire({
                title: 'No files selected',
                text: 'Please add at least one file for under review certificate file.',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
            return;
        }
        // Hide modal
        $('#underReviewModal').modal('hide');
        // Show loading
        Swal.fire({
            title: 'Updating...',
            text: 'Please wait while we update the status',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Prepare FormData for file upload
        const formData = new FormData();
        formData.append('action', 'update_status');
        formData.append('mt5_id', window.currentMt5Id);
        formData.append('status', 'under_review');
        selectedUnderReviewFiles.forEach(file => {
            formData.append('underReviewFile[]', file);
        });

        // Send AJAX request with FormData
        $.ajax({
            url: 'mt5_details_second.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update the status badge in the same row
                    const dropdownButton = document.querySelector(`#dropdownMenuButton${window.currentMt5Id}`);
                    const row = dropdownButton.closest('tr');
                    const statusCell = row.querySelector('td:nth-child(9) .badge'); // Status is 8th column
                    statusCell.className = 'badge bg-info';
                    statusCell.textContent = 'Under Review';

                    // Clear selected files
                    selectedUnderReviewFiles = [];
                    updateUnderReviewSelectedFilesDisplay();
                    $('#underReviewFile').val('');

                    // Check if email was sent
                    if (response.email_sent) {
                        Swal.fire({
                            title: 'Success!',
                            text: 'Status updated and email sent successfully.',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            title: 'Status Updated',
                            text: 'Status updated successfully.',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        });
                    }
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: response.message || 'Failed to update status.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred while updating the status.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    });

    // Clear files when modal is shown or closed
    $('#underReviewModal').on('show.bs.modal', function() {
        selectedUnderReviewFiles = [];
        updateUnderReviewSelectedFilesDisplay();
        $('#underReviewFile').val('');
    });

    $('#underReviewModal').on('hidden.bs.modal', function() {
        selectedUnderReviewFiles = [];
        updateUnderReviewSelectedFilesDisplay();
        $('#underReviewFile').val('');
    });
});

// Add credit function
function addCredit(userId, userName) {
    Swal.fire({
        title: 'Add Credit',
        text: `Add 1 credit to "${userName}"?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, add credit!',
        cancelButtonText: 'Cancel',
        customClass: {
            confirmButton: 'btn btn-success',
            cancelButton: 'btn btn-secondary'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Send AJAX request to add credit
            $.ajax({
                url: 'mt5_details_second.php',
                type: 'POST',
                data: {
                    action: 'add_credit',
                    user_id: userId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Update the credit display
                        $('#credit-' + userId).text(response.new_credit);
                        Swal.fire({
                            title: 'Success!',
                            text: 'Credit added successfully.',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: response.message || 'Failed to add credit.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        title: 'Error!',
                        text: 'An error occurred while adding credit.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            });
        }
    });
}

// Remove credit function
function removeCredit(userId, userName) {
    Swal.fire({
        title: 'Remove Credit',
        text: `Remove 1 credit from "${userName}"?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, remove credit!',
        cancelButtonText: 'Cancel',
        customClass: {
            confirmButton: 'btn btn-danger',
            cancelButton: 'btn btn-secondary'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Send AJAX request to remove credit
            $.ajax({
                url: 'mt5_details_second.php',
                type: 'POST',
                data: {
                    action: 'remove_credit',
                    user_id: userId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Update the credit display
                        $('#credit-' + userId).text(response.new_credit);
                        Swal.fire({
                            title: 'Success!',
                            text: 'Credit removed successfully.',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: response.message || 'Failed to remove credit.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        title: 'Error!',
                        text: 'An error occurred while removing credit.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            });
        }
    });
}

// Change test type function
function changeTestType(mt5Id, userName, currentTestType) {
    $('#testTypeModalLabel').text('Change Test Type for ' + userName);
    $('#newTestType').val(currentTestType);
    $('#testTypeModal').modal('show');
    // Store current data
    window.currentMt5Id = mt5Id;
}

// Handle test type submission
$(document).ready(function() {
    $('#submitTestType').on('click', function() {
        const newTestType = $('#newTestType').val();
        
        // Hide modal
        $('#testTypeModal').modal('hide');
        // Show loading
        Swal.fire({
            title: 'Updating...',
            text: 'Please wait while we update the test type',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Send AJAX request
        $.ajax({
            url: 'mt5_details_second.php',
            type: 'POST',
            data: {
                action: 'update_test_type',
                mt5_id: window.currentMt5Id,
                test_type: newTestType
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update the test type display in the same row
                    const dropdownButton = document.querySelector(`#dropdownMenuButton${window.currentMt5Id}`);
                    const row = dropdownButton.closest('tr');
                    const testTypeCell = row.querySelector(`#test-type-${window.currentMt5Id}`);
                    if (testTypeCell) {
                        testTypeCell.textContent = newTestType;
                    }

                    Swal.fire({
                        title: 'Success!',
                        text: 'Test type updated successfully.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: response.message || 'Failed to update test type.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred while updating the test type.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    });
});

// Make payment function
function makePayment(userId, userName) {
    $('#makePaymentModalLabel').text('Make Payment for ' + userName);
    $('#paymentUserName').val(userName);
    $('#paymentUserId').val(userId);
    
    // Set current date and time
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const currentDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
    $('#paymentDate').val(currentDateTime);
    
    // Clear other fields
    $('#paymentAmount').val('');
    $('#transactionHash').val('');
    $('#paymentDescription').val('');
    
    $('#makePaymentModal').modal('show');
}

// Handle payment submission
$(document).ready(function() {
    $('#submitPayment').on('click', function() {
        const userId = $('#paymentUserId').val();
        const amount = $('#paymentAmount').val();
        const paymentDate = $('#paymentDate').val();
        const transactionHash = $('#transactionHash').val();
        const description = $('#paymentDescription').val();
        
        // Validate required fields
        if (!amount || !paymentDate || !transactionHash) {
            Swal.fire({
                title: 'Validation Error',
                text: 'Please fill in all required fields.',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
            return;
        }
        
        // Hide modal
        $('#makePaymentModal').modal('hide');
        // Show loading
        Swal.fire({
            title: 'Recording Payment...',
            text: 'Please wait while we record the payment',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Send AJAX request
        $.ajax({
            url: 'mt5_details_second.php',
            type: 'POST',
            data: {
                action: 'make_payment',
                user_id: userId,
                amount: amount,
                payment_date: paymentDate,
                transaction_hash: transactionHash,
                description: description
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Payment recorded successfully.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: response.message || 'Failed to record payment.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred while recording the payment.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    });
});

// Send MT5 issue email function
function sendMt5IssueEmail(mt5Id, userName, userEmail, challengeId, issueType) {
    const issueText = issueType === 'login_problem' ? 'MT5 Login Problem' : 'MT5 Wrong Balance';
    Swal.fire({
        title: 'Send ' + issueText + ' Email',
        text: `Send email to "${userName}" about ${issueText.toLowerCase()}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#007bff',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, send email',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'mt5_details_second.php',
                type: 'POST',
                data: {
                    action: 'send_mt5_issue_email',
                    mt5_id: mt5Id,
                    challenge_id: challengeId,
                    issue_type: issueType
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Success!',
                            text: 'Email sent successfully.',
                            icon: 'success',
                            timer: 2000
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: response.message || 'Failed to send email.',
                            icon: 'error'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        title: 'Error!',
                        text: 'An error occurred while sending email.',
                        icon: 'error'
                    });
                }
            });
        }
    });
}
</script>