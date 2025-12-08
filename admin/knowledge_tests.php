<?php
require_once 'functions/auth.php';
checkAdminAuth();
require_once '../database.php';

// Get database connection
$pdo = getPDO();

// Handle reset action
if (isset($_POST['action']) && $_POST['action'] === 'reset_test') {
    header('Content-Type: application/json');

    $userId = (int)$_POST['user_id'];

    // Update knowledge_test_result to null
    $stmt = $pdo->prepare("UPDATE waitlist_users SET knowledge_test_result = NULL WHERE id = ?");
    $success = $stmt->execute([$userId]);

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Knowledge test result reset successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to reset knowledge test result']);
    }
    exit;
}

// Handle add credit action
if (isset($_POST['action']) && $_POST['action'] === 'add_credit') {
    header('Content-Type: application/json');

    $userId = (int)$_POST['user_id'];

    // Increment user_credit by 1
    $stmt = $pdo->prepare("UPDATE waitlist_users SET user_credit = user_credit + 1, credit_updated_at = NOW() WHERE id = ?");
    $success = $stmt->execute([$userId]);

    if ($success) {
        // Get new credit value
        $stmt = $pdo->prepare("SELECT user_credit FROM waitlist_users WHERE id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
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

    // Decrement user_credit by 1, but not below 0
    $stmt = $pdo->prepare("UPDATE waitlist_users SET user_credit = GREATEST(0, user_credit - 1), credit_updated_at = NOW() WHERE id = ?");
    $success = $stmt->execute([$userId]);

    if ($success) {
        // Get new credit value
        $stmt = $pdo->prepare("SELECT user_credit FROM waitlist_users WHERE id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'message' => 'Credit removed successfully', 'new_credit' => $result['user_credit']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove credit']);
    }
    exit;
}

// Handle approve knowledge test action
if (isset($_POST['action']) && $_POST['action'] === 'approve_test') {
    header('Content-Type: application/json');

    $userId = (int)$_POST['user_id'];

    // Get admin user ID from session email
    $adminEmail = $_SESSION['admin_email'] ?? '';
    $adminId = null;

    if (!empty($adminEmail)) {
        $adminStmt = $pdo->prepare("SELECT id FROM waitlist_users WHERE email = ?");
        $adminStmt->execute([$adminEmail]);
        $adminUser = $adminStmt->fetch(PDO::FETCH_ASSOC);
        $adminId = $adminUser ? $adminUser['id'] : null;
    }

    try {
        // Update or insert approval status
        if ($adminId) {
            $stmt = $pdo->prepare("
                INSERT INTO knowledge_test_approvals (user_id, approval_status, approved_by, approved_at)
                VALUES (?, 'approved', ?, NOW())
                ON DUPLICATE KEY UPDATE
                approval_status = 'approved',
                approved_by = VALUES(approved_by),
                approved_at = VALUES(approved_at),
                declined_reason = NULL
            ");
            $success = $stmt->execute([$userId, $adminId]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO knowledge_test_approvals (user_id, approval_status, approved_at)
                VALUES (?, 'approved', NOW())
                ON DUPLICATE KEY UPDATE
                approval_status = 'approved',
                approved_at = VALUES(approved_at),
                declined_reason = NULL
            ");
            $success = $stmt->execute([$userId]);
        }

        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Knowledge test approved successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to approve knowledge test']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle decline knowledge test action
if (isset($_POST['action']) && $_POST['action'] === 'decline_test') {
    header('Content-Type: application/json');

    $userId = (int)$_POST['user_id'];

    // Get admin user ID from session email
    $adminEmail = $_SESSION['admin_email'] ?? '';
    $adminId = null;

    if (!empty($adminEmail)) {
        $adminStmt = $pdo->prepare("SELECT id FROM waitlist_users WHERE email = ?");
        $adminStmt->execute([$adminEmail]);
        $adminUser = $adminStmt->fetch(PDO::FETCH_ASSOC);
        $adminId = $adminUser ? $adminUser['id'] : null;
    }

    try {
        // Update or insert approval status
        if ($adminId) {
            $stmt = $pdo->prepare("
                INSERT INTO knowledge_test_approvals (user_id, approval_status, approved_by)
                VALUES (?, 'declined', ?)
                ON DUPLICATE KEY UPDATE
                approval_status = 'declined',
                approved_by = VALUES(approved_by),
                approved_at = NULL,
                declined_reason = NULL
            ");
            $success = $stmt->execute([$userId, $adminId]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO knowledge_test_approvals (user_id, approval_status)
                VALUES (?, 'declined')
                ON DUPLICATE KEY UPDATE
                approval_status = 'declined',
                approved_at = NULL,
                declined_reason = NULL
            ");
            $success = $stmt->execute([$userId]);
        }

        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Knowledge test declined successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to decline knowledge test']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// ----------------------
// CSV EXPORT
// ----------------------
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Prepare query to fetch all knowledge test users
    $export_query = "
        SELECT
            wu.id,
            wu.name,
            wu.email,
            wu.credits,
            wu.knowledge_test_result,
            wu.user_credit,
            wu.created_at,
            COALESCE(referral_counts.completed_referrals, 0) AS completed_referrals,
            m.status AS mt5_status,
            COALESCE(kta.approval_status, 'pending') AS approval_status,
            kta.declined_reason
        FROM waitlist_users wu
        LEFT JOIN (
            SELECT
                child.parent_user_id,
                COUNT(*) AS completed_referrals
            FROM waitlist_users child
            JOIN waitlist_users parent
                ON parent.id = child.parent_user_id
            WHERE child.parent_user_id IS NOT NULL
            AND child.email_verified = 1
            AND child.quiz_result IS NOT NULL
            AND child.user_ip != parent.user_ip
            GROUP BY child.parent_user_id
        ) referral_counts
        ON wu.id = referral_counts.parent_user_id
        LEFT JOIN mt5_details m ON wu.id = m.user_id
        LEFT JOIN knowledge_test_approvals kta ON wu.id = kta.user_id
        WHERE wu.knowledge_test_result IS NOT NULL
        ORDER BY wu.created_at DESC
    ";
    $export_stmt = $pdo->prepare($export_query);
    $export_stmt->execute();
    $export_users = $export_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Set CSV headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=knowledge_tests_' . date('Y-m-d_H-i-s') . '.csv');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Open output stream
    $output = fopen('php://output', 'w');

    // Add UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // CSV headers
    fputcsv($output, ['ID', 'Name', 'Email', 'Credits', 'Total Referrals', 'Completed Referrals', 'Approval Status', 'Decline Reason', 'MT5 Status', 'Created At']);

    // Loop through users and write rows
    foreach ($export_users as $user) {
        $result = json_decode($user['knowledge_test_result'], true);
        $completed_at = $result['completed_at'] ?? 'N/A';
        if ($completed_at !== 'N/A') {
            $completed_at = date('d/m/Y H:i:s', strtotime($completed_at));
        }

        fputcsv($output, [
            $user['id'] ?? 'N/A',
            $user['name'] ?? 'N/A',
            $user['email'] ?? 'N/A',
            $user['credits'] ?? 0,
            $user['credits'] ?? 0,
            $user['completed_referrals'] ?? 0,
            ucfirst($user['approval_status'] ?? 'pending'),
            $user['declined_reason'] ?? 'N/A',
            $user['mt5_status'] ?? 'Not Submitted',
            !empty($user['created_at'])
                ? date('d/m/Y H:i:s', strtotime($user['created_at']))
                : 'N/A'
        ]);
    }

    fclose($output);
    exit;
}

// Pagination settings
$limit = 50;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Get total count for pagination
$total_query = "SELECT COUNT(*) FROM waitlist_users WHERE knowledge_test_result IS NOT NULL";
$total_stmt = $pdo->query($total_query);
$total_users = $total_stmt->fetchColumn();
$total_pages = ceil($total_users / $limit);

// Get users who have completed knowledge test with pagination
$query = "
    SELECT
        wu.id,
        wu.name,
        wu.email,
        wu.credits,
        wu.knowledge_test_result,
        wu.user_credit,
        wu.created_at,
        COALESCE(referral_counts.completed_referrals, 0) AS completed_referrals,
        m.status AS mt5_status,
        COALESCE(kta.approval_status, 'pending') AS approval_status,
        kta.declined_reason,
        kta.approved_at
    FROM waitlist_users wu
    LEFT JOIN (
        SELECT
            child.parent_user_id,
            COUNT(*) AS completed_referrals
        FROM waitlist_users child
        JOIN waitlist_users parent
            ON parent.id = child.parent_user_id
        WHERE child.parent_user_id IS NOT NULL
        AND child.email_verified = 1
        AND child.quiz_result IS NOT NULL
        AND child.user_ip != parent.user_ip
        GROUP BY child.parent_user_id
    ) referral_counts
    ON wu.id = referral_counts.parent_user_id
    LEFT JOIN mt5_details m ON wu.id = m.user_id
    LEFT JOIN knowledge_test_approvals kta ON wu.id = kta.user_id
    WHERE wu.knowledge_test_result IS NOT NULL
    ORDER BY wu.created_at DESC
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php
ob_start();
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Knowledge Tests Summary</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="?export=csv" class="btn btn-sm btn-success">
                <i class="fas fa-download"></i> Export to CSV
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="knowledgeTable" class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Credits</th>
                        <th>Total Referrals</th>
                        <th>Completed Referrals</th>
                        <th>% Referred</th>
                        <th>Approval Status</th>
                        <th>Created At</th>
                        <th>MT5 Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user):
                        $result = json_decode($user['knowledge_test_result'], true);
                        $completed_at = $result['completed_at'] ?? 'N/A';
                        if ($completed_at !== 'N/A') {
                            $completed_at = date('M d, Y H:i', strtotime($completed_at));
                        }
                    ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><a href="user_referral_details.php?id=<?php echo $user['id']; ?>" class="text-decoration-none"><?php echo ucfirst(htmlspecialchars($user['name'])); ?></a></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <span id="credit-<?php echo $user['id']; ?>"><?php echo $user['user_credit'] ?? 0; ?></span>
                        </td>
                        <td><?php echo $user['credits']; ?></td>
                        <td><?php echo $user['completed_referrals']; ?></td>
                        <td>
                            <?php
                                $total_referrals = $user['credits'] ?? 0;
                                $completed_referrals = $user['completed_referrals'] ?? 0;
                                
                                // Calculate percentage; if total is 0, percentage is 0.
                                $percentage = ($total_referrals > 0) 
                                    ? round(($completed_referrals / $total_referrals) * 100, 2) 
                                    : 0;
                                    
                                echo $percentage . '%';
                            ?>
                        </td>
                        <td>
                            <?php
                            $approval_status = $user['approval_status'] ?? 'pending';
                            $badgeClass = 'bg-warning';
                            $statusText = 'Pending';

                            if ($approval_status === 'approved') {
                                $badgeClass = 'bg-success';
                                $statusText = 'Approved';
                            } elseif ($approval_status === 'declined') {
                                $badgeClass = 'bg-danger';
                                $statusText = 'Declined';
                            }

                            echo "<span class='badge $badgeClass'>$statusText</span>";
                            ?>
                        </td>
                        <td><?php echo date('M d, Y H:i', strtotime($user['created_at'])); ?></td>
                        <td>
                            <?php
                            $mt5_status = $user['mt5_status'] ?? null;
                            if ($mt5_status) {
                                $badgeClass = 'bg-warning';
                                $statusText = 'Pending';
                                if ($mt5_status === 'pass') {
                                    $badgeClass = 'bg-success';
                                    $statusText = 'Pass';
                                } elseif ($mt5_status === 'fail') {
                                    $badgeClass = 'bg-danger';
                                    $statusText = 'Fail';
                                } elseif ($mt5_status === 'running') {
                                    $badgeClass = 'bg-primary';
                                    $statusText = 'Running';
                                }
                                echo "<span class='badge $badgeClass'>$statusText</span>";
                            } else {
                                echo '<span class="text-muted">Not Submitted</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton<?php echo $user['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                    Actions
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton<?php echo $user['id']; ?>">
                                    <li><a class="dropdown-item" href="#" onclick="openEmailModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['name']); ?>', '<?php echo htmlspecialchars($user['email']); ?>')">
                                        <i class="bi bi-envelope me-2"></i>Send Mail
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <?php if (($user['approval_status'] ?? 'pending') !== 'approved'): ?>
                                    <li><a class="dropdown-item text-success" href="#" onclick="approveTest(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['name']); ?>')">
                                        <i class="bi bi-check-circle me-2"></i>Approve Test
                                    </a></li>
                                    <?php endif; ?>
                                    <?php if (($user['approval_status'] ?? 'pending') !== 'declined'): ?>
                                    <li><a class="dropdown-item text-danger" href="#" onclick="declineTest(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['name']); ?>')">
                                        <i class="bi bi-x-circle me-2"></i>Decline Test
                                    </a></li>
                                    <?php endif; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="#" onclick="addCredit(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['name']); ?>')">
                                        <i class="bi bi-plus-circle me-2"></i>Add Credit
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="removeCredit(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['name']); ?>')">
                                        <i class="bi bi-dash-circle me-2"></i>Remove Credit
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-warning" href="#" onclick="resetTest(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['name']); ?>')">
                                        <i class="bi bi-arrow-counterclockwise me-2"></i>Reset Test
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

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
<div class="d-flex justify-content-center mt-4">
    <nav aria-label="Page navigation">
        <ul class="pagination">
            <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);

            if ($start_page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=1">1</a>
                </li>
                <?php if ($start_page > 2): ?>
                    <li class="page-item disabled">
                        <span class="page-link">...</span>
                    </li>
                <?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>

            <?php if ($end_page < $total_pages): ?>
                <?php if ($end_page < $total_pages - 1): ?>
                    <li class="page-item disabled">
                        <span class="page-link">...</span>
                    </li>
                <?php endif; ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $total_pages; ?>"><?php echo $total_pages; ?></a>
                </li>
            <?php endif; ?>

            <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</div>
<?php endif; ?>

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

.btn-action {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    border-radius: 0.25rem;
}
</style>

<script>
$(document).ready(function() {
    var table = $('#knowledgeTable').DataTable({
        dom: '<"row mb-3"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search knowledge tests...",
            lengthMenu: "_MENU_ entries per page",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "No entries found",
            infoFiltered: "(filtered from _MAX_ total entries)"
        },
        paging: false,
        ordering: true,
        order: [[0, 'desc']],
        responsive: true,
        columnDefs: [
            {
                targets: [9], // Actions column
                orderable: false
            }
        ],
        initComplete: function () {
            $('.dataTables_length select').addClass('form-select form-select-sm');
            $('.dataTables_filter input').addClass('form-control form-control-sm');
        }
    });
});

// Reset test function with SweetAlert confirmation
function resetTest(userId, userName) {
    Swal.fire({
        title: 'Are you sure?',
        text: `You want to reset the knowledge test for "${userName}"? This will allow them to retake the test.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, reset it!',
        cancelButtonText: 'Cancel',
        customClass: {
            confirmButton: 'btn btn-warning',
            cancelButton: 'btn btn-secondary'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'Resetting...',
                text: 'Please wait while we reset the knowledge test',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Send AJAX request to reset
            $.ajax({
                url: 'knowledge_tests.php',
                type: 'POST',
                data: {
                    action: 'reset_test',
                    user_id: userId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Reset!',
                            text: 'Knowledge test has been reset successfully.',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            // Reload the page to reflect changes
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: response.message || 'Failed to reset knowledge test.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        title: 'Error!',
                        text: 'An error occurred while resetting the knowledge test.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            });
        }
    });
}

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
                url: 'knowledge_tests.php',
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
                url: 'knowledge_tests.php',
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

// Approve test function
function approveTest(userId, userName) {
    Swal.fire({
        title: 'Approve Knowledge Test',
        text: `Are you sure you want to approve the knowledge test for "${userName}"?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, approve!',
        cancelButtonText: 'Cancel',
        customClass: {
            confirmButton: 'btn btn-success',
            cancelButton: 'btn btn-secondary'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'Approving...',
                text: 'Please wait while we approve the knowledge test',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Send AJAX request to approve
            $.ajax({
                url: 'knowledge_tests.php',
                type: 'POST',
                data: {
                    action: 'approve_test',
                    user_id: userId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Approved!',
                            text: 'Knowledge test has been approved successfully.',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            // Reload the page to reflect changes
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: response.message || 'Failed to approve knowledge test.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        title: 'Error!',
                        text: 'An error occurred while approving the knowledge test.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            });
        }
    });
}

// Decline test function
function declineTest(userId, userName) {
    Swal.fire({
        title: 'Decline Knowledge Test',
        text: `Are you sure you want to decline the knowledge test for "${userName}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, decline!',
        cancelButtonText: 'Cancel',
        customClass: {
            confirmButton: 'btn btn-danger',
            cancelButton: 'btn btn-secondary'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'Declining...',
                text: 'Please wait while we decline the knowledge test',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Send AJAX request to decline
            $.ajax({
                url: 'knowledge_tests.php',
                type: 'POST',
                data: {
                    action: 'decline_test',
                    user_id: userId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Declined!',
                            text: 'Knowledge test has been declined successfully.',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            // Reload the page to reflect changes
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: response.message || 'Failed to decline knowledge test.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        title: 'Error!',
                        text: 'An error occurred while declining the knowledge test.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            });
        }
    });
}
</script>