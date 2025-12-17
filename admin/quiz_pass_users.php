<?php
require_once 'functions/auth.php';
checkAdminAuth();
require_once 'functions/audit.php';
require_once '../database.php';

// Get database connection
$pdo = getPDO();

// Handle AJAX status update
if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
    header('Content-Type: application/json');

    $userId = (int)$_POST['user_id'];
    $newStatus = $_POST['status'];

    // Validate status
    if (!in_array($newStatus, ['active', 'inactive'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }

    // Prevent changing admin status
    $stmt = $pdo->prepare("SELECT email FROM waitlist_users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['email'] === 'admin@gmail.com') {
        echo json_encode(['success' => false, 'message' => 'Cannot change admin status']);
        exit;
    }

    // Update status
    $updateStmt = $pdo->prepare("UPDATE waitlist_users SET status = ? WHERE id = ?");
    $success = $updateStmt->execute([$newStatus, $userId]);

    if ($success) {
        // Record audit for status change
        $adminId = $_SESSION['admin_id'] ?? null;
        recordAdminAction($pdo, $adminId, 'update_user_status', $userId, ['new_status' => $newStatus]);

        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }
    exit;
}

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $userId = (int) $_GET['delete'];

    // Prevent deleting main admin
    $stmt = $pdo->prepare("SELECT name, email FROM waitlist_users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['email'] !== 'admin@gmail.com') {
        // Record audit BEFORE deleting so FK reference is valid
        $adminId = $_SESSION['admin_id'] ?? null;
        try {
            recordAdminAction($pdo, $adminId, 'delete_user', $userId, ['email' => $user['email'] ?? null, 'name' => $user['name'] ?? null]);
        } catch (Exception $e) {
            error_log('Failed to record admin delete action: ' . $e->getMessage());
        }

        // Delete the user
        $deleteStmt = $pdo->prepare("DELETE FROM waitlist_users WHERE id = ?");
        $deleteStmt->execute([$userId]);

        // Build redirect URL with preserved search parameters
        $redirectUrl = 'quiz_pass_users.php?deleted=1';
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $redirectUrl .= '&search=' . urlencode($_GET['search']);
        }
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $redirectUrl .= '&page=' . (int)$_GET['page'];
        }

        // Redirect back to avoid resubmission
        header('Location: ' . $redirectUrl);
        exit;
    }
}

// Pagination settings
$limit = 50;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Get total count for pagination
$total_query = "SELECT COUNT(*) FROM waitlist_users WHERE email != 'admin@gmail.com' AND quiz_result IS NOT NULL";
$total_stmt = $pdo->query($total_query);
$total_users = $total_stmt->fetchColumn();
$total_pages = ceil($total_users / $limit);

// Get all quiz pass users except admin with their referral counts and pagination
$query = "
    SELECT
        u.*,
        COUNT(r.id) as referral_count
    FROM waitlist_users u
    LEFT JOIN waitlist_users r ON u.id = r.parent_user_id
    WHERE u.email != 'admin@gmail.com' AND u.quiz_result IS NOT NULL
    GROUP BY u.id
    ORDER BY u.created_at DESC
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ----------------------
// CSV EXPORT
// ----------------------
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Prepare query to fetch all quiz pass users with referral counts
    $export_query = "
        SELECT
            u.*,
            COUNT(r.id) as referral_count
        FROM waitlist_users u
        LEFT JOIN waitlist_users r ON u.id = r.parent_user_id
        WHERE u.email != 'admin@gmail.com' AND u.quiz_result IS NOT NULL
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ";
    $export_stmt = $pdo->prepare($export_query);
    $export_stmt->execute();
    $export_users = $export_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Set CSV headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=quiz_pass_users_' . date('Y-m-d_H-i-s') . '.csv');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Open output stream
    $output = fopen('php://output', 'w');

    // Add UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // CSV headers
    fputcsv($output, ['Name', 'Email', 'Country', 'Ip Address', 'Email Verified', 'Referrals', 'Created At']);

    // Loop through users and write rows
    foreach ($export_users as $user) {
        fputcsv($output, [
            $user['name'] ?? 'N/A',
            $user['email'] ?? 'N/A',
            !empty($user['country']) ? $user['country'] : 'N/A',
            !empty($user['user_ip']) ? $user['user_ip'] : 'N/A',
            ($user['email_verified'] == 1) ? 'Yes' : 'No',
            $user['referral_count'] ?? 0,
            !empty($user['created_at'])
                ? date('d/m/Y H:i:s', strtotime($user['created_at']))
                : 'N/A'
        ]);
    }

    fclose($output);
    exit;
}
?>

<?php
ob_start();
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Quiz Pass Users Management</h1>
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
            <table id="usersTable" class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Country</th>
                        <th>IP Address</th>
                        <th>Email Verified</th>
                        <th>Referrals</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user):
                        $referral_count = $user['referral_count'] ?? 0;
                        $referral_class = 'low';
                        if ($referral_count >= 5) {
                            $referral_class = 'high';
                        } elseif ($referral_count >= 1) {
                            $referral_class = 'medium';
                        }
                    ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm bg-light rounded-circle me-2 d-flex align-items-center justify-content-center">
                                    <i class="bi bi-person text-primary"></i>
                                </div>
                                <span><a href="user_referral_dashboard.php?id=<?php echo $user['id']; ?>" target="_blank"><?php echo htmlspecialchars($user['name']); ?></a></span>
                            </div>
                        </td>
                        <td><a href="user_referral_dashboard.php?id=<?php echo $user['id']; ?>" target="_blank"><?php echo htmlspecialchars($user['email']); ?></a></td>
                        <td><?php echo htmlspecialchars($user['country'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($user['user_ip'] ?? 'N/A'); ?></td>
                        <td>
                            <?php if ($user['email_verified'] == 1): ?>
                                <span class="verification-badge verified-badge">
                                    <i class="bi bi-check-circle me-1"></i>Verified
                                </span>
                            <?php else: ?>
                                <span class="verification-badge unverified-badge">
                                    <i class="bi bi-exclamation-circle me-1"></i>Unverified
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="referral-badge <?php echo $referral_class; ?>">
                                <i class="bi bi-people me-1"></i><?php echo $referral_count; ?>
                            </span>
                        </td>
                        <td>
                            <select class="form-select form-select-sm status-dropdown"
                                    data-user-id="<?php echo $user['id']; ?>"
                                    data-user-name="<?php echo htmlspecialchars($user['name']); ?>"
                                    onchange="changeUserStatus(this)">
                                <option value="active" <?php echo ($user['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($user['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </td>

                        <td><?php echo date('M d, Y H:i', strtotime($user['created_at'])); ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-danger btn-action" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['name']); ?>')" title="Delete User">
                                    <i class="bi bi-trash"></i>
                                </button>
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
                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);

            if ($start_page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=1<?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?>">1</a>
                </li>
                <?php if ($start_page > 2): ?>
                    <li class="page-item disabled">
                        <span class="page-link">...</span>
                    </li>
                <?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>

            <?php if ($end_page < $total_pages): ?>
                <?php if ($end_page < $total_pages - 1): ?>
                    <li class="page-item disabled">
                        <span class="page-link">...</span>
                    </li>
                <?php endif; ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?>"><?php echo $total_pages; ?></a>
                </li>
            <?php endif; ?>

            <?php if ($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?>" aria-label="Next">
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

.avatar-sm {
    width: 32px;
    height: 32px;
    font-size: 0.875rem;
}

.verification-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.5rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
}

.verified-badge {
    background-color: #dcfce7;
    color: #166534;
}

.unverified-badge {
    background-color: #fef3c7;
    color: #92400e;
}

.referral-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.5rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    background-color: #e0e7ff;
    color: #3730a3;
}

.referral-badge.high {
    background-color: #dcfce7;
    color: #166534;
}

.referral-badge.medium {
    background-color: #fef3c7;
    color: #92400e;
}

.referral-badge.low {
    background-color: #fee2e2;
    color: #dc2626;
}

.action-buttons {
    display: flex;
    gap: 0.25rem;
}

.btn-action {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    border-radius: 0.25rem;
}

.status-dropdown {
    min-width: 100px;
    cursor: pointer;
}

.status-dropdown option[value="active"] {
    color: #198754;
}

.status-dropdown option[value="inactive"] {
    color: #dc3545;
}
</style>

<script>
$(document).ready(function() {
    var table = $('#usersTable').DataTable({
        dom: '<"row mb-3"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search quiz pass users...",
            lengthMenu: "_MENU_ users per page",
            info: "Showing _START_ to _END_ of _TOTAL_ users",
            infoEmpty: "No quiz pass users found",
            infoFiltered: "(filtered from _MAX_ total users)"
        },
        paging: false,
        ordering: true,
        order: [[0, 'desc']], // Sort by ID in descending order
        responsive: {
            details: {
                display: $.fn.dataTable.Responsive.display.modal({
                    header: function(row) {
                        var data = row.data();
                        return 'Details for ' + data[1];
                    }
                }),
                renderer: $.fn.dataTable.Responsive.renderer.tableAll()
            }
        },
        columnDefs: [
            {
                targets: [7], // Actions column
                orderable: false
            }
        ],
        buttons: [
            {
                extend: 'copy',
                className: 'btn btn-sm btn-primary'
            },
            {
                extend: 'excel',
                className: 'btn btn-sm btn-primary'
            },
            {
                extend: 'pdf',
                className: 'btn btn-sm btn-primary'
            },
            {
                extend: 'print',
                className: 'btn btn-sm btn-primary'
            }
        ],
        initComplete: function () {
            // Add Bootstrap classes to DataTables elements
            $('.dataTables_length select').addClass('form-select form-select-sm');
            $('.dataTables_filter input').addClass('form-control form-control-sm');

            // Create button container
            var buttonContainer = $('<div class="text-end mb-3"></div>');
            table.buttons().container().appendTo(buttonContainer);
            $('.dataTables_length').parent().after(buttonContainer);
        }
    });
});

// Delete user function with SweetAlert confirmation
function deleteUser(userId, userName) {
    Swal.fire({
        title: 'Are you sure?',
        text: `You want to delete user "${userName}"? This action cannot be undone!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete user!',
        cancelButtonText: 'Cancel',
        customClass: {
            confirmButton: 'btn btn-danger',
            cancelButton: 'btn btn-secondary'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Get current search parameters to preserve them after deletion
            const urlParams = new URLSearchParams(window.location.search);
            let deleteUrl = `quiz_pass_users.php?delete=${userId}`;

            // Preserve search parameter if exists
            if (urlParams.has('search')) {
                deleteUrl += `&search=${encodeURIComponent(urlParams.get('search'))}`;
            }

            // Preserve page parameter if exists
            if (urlParams.has('page')) {
                deleteUrl += `&page=${urlParams.get('page')}`;
            }

            // Redirect to delete URL with preserved parameters
            window.location.href = deleteUrl;
        }
    });
}

// Show success message if user was deleted
<?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
$(document).ready(function() {
    Swal.fire({
        title: 'Deleted!',
        text: 'User has been deleted successfully.',
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
    });
});
<?php endif; ?>

// Change user status function with SweetAlert confirmation
function changeUserStatus(selectElement) {
    const userId = selectElement.getAttribute('data-user-id');
    const userName = selectElement.getAttribute('data-user-name');
    const newStatus = selectElement.value;
    const oldStatus = newStatus === 'active' ? 'inactive' : 'active';

    // Store the old value in case user cancels
    const previousValue = oldStatus;

    Swal.fire({
        title: 'Confirm Status Change',
        html: `Are you sure you want to change <strong>${userName}</strong>'s status to <strong>${newStatus.toUpperCase()}</strong>?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: newStatus === 'active' ? '#198754' : '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, change it!',
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

            // Send AJAX request to update status
            $.ajax({
                url: 'quiz_pass_users.php',
                type: 'POST',
                data: {
                    action: 'update_status',
                    user_id: userId,
                    status: newStatus
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Updated!',
                            text: 'User status has been updated successfully.',
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
                            text: response.message || 'Failed to update status.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                        // Revert the dropdown to previous value
                        selectElement.value = previousValue;
                    }
                },
                error: function() {
                    Swal.fire({
                        title: 'Error!',
                        text: 'An error occurred while updating the status.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                    // Revert the dropdown to previous value
                    selectElement.value = previousValue;
                }
            });
        } else {
            // User cancelled, revert the dropdown to previous value
            selectElement.value = previousValue;
        }
    });
}
</script>