<?php
require_once 'functions/auth.php';
checkAdminAuth();
require_once '../database.php';

// Get database connection
$pdo = getPDO();

// Handle status toggle action
if (isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    header('Content-Type: application/json');

    $mt5Id = (int)$_POST['mt5_id'];
    $newStatus = $_POST['status'];

    // Validate status
    if (!in_array($newStatus, ['pass', 'fail'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }

    // Update status
    $stmt = $pdo->prepare("UPDATE mt5_details SET status = ? WHERE id = ?");
    $success = $stmt->execute([$newStatus, $mt5Id]);

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }
    exit;
}

// Get MT5 details with user info
$query = "
    SELECT
        m.*,
        u.name,
        u.email
    FROM mt5_details m
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
    <h1 class="h2">MT5 Details Summary</h1>
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
                        <th>MT5 Username</th>
                        <th>MT5 Password</th>
                        <th>Server</th>
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
                        <td><?php echo htmlspecialchars($detail['username']); ?></td>
                        <td><?php echo htmlspecialchars($detail['password']); ?></td>
                        <td><?php echo htmlspecialchars($detail['server']); ?></td>
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
                            }
                            ?>
                            <span class="badge <?php echo $badgeClass; ?>"><?php echo $statusText; ?></span>
                        </td>
                        <td><?php echo date('M d, Y H:i', strtotime($detail['submitted_at'])); ?></td>
                        <td>
                            <div class="btn-group" role="group">
                                <?php
                                $buttonText = $status === 'pass' ? 'Mark as Fail' : 'Mark as Pass';
                                $buttonClass = $status === 'pass' ? 'btn-danger' : 'btn-success';
                                ?>
                                <button class="btn btn-sm <?php echo $buttonClass; ?> toggle-status-btn"
                                        data-mt5-id="<?php echo $detail['id']; ?>"
                                        data-current-status="<?php echo $status; ?>"
                                        data-user-name="<?php echo htmlspecialchars($detail['name']); ?>"
                                        onclick="toggleStatus(this)">
                                    <?php echo $buttonText; ?>
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
            searchPlaceholder: "Search MT5 details...",
            lengthMenu: "_MENU_ entries per page",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "No entries found",
            infoFiltered: "(filtered from _MAX_ total entries)"
        },
        pageLength: 10,
        lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
        ordering: true,
        order: [[0, 'desc']],
        responsive: true,
        columnDefs: [
            {
                targets: [8], // Actions column
                orderable: false
            }
        ],
        initComplete: function () {
            $('.dataTables_length select').addClass('form-select form-select-sm');
            $('.dataTables_filter input').addClass('form-control form-control-sm');
        }
    });
});

// Toggle status function
function toggleStatus(button) {
    const mt5Id = button.getAttribute('data-mt5-id');
    const currentStatus = button.getAttribute('data-current-status');
    const userName = button.getAttribute('data-user-name');

    // Determine new status
    const newStatus = currentStatus === 'pass' ? 'fail' : 'pass';
    const actionText = newStatus === 'pass' ? 'Pass' : 'Fail';

    Swal.fire({
        title: `Mark as ${actionText}`,
        text: `Are you sure you want to mark "${userName}" as ${actionText.toLowerCase()}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: newStatus === 'pass' ? '#28a745' : '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: `Yes, mark as ${actionText.toLowerCase()}`,
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
                url: 'mt5_details.php',
                type: 'POST',
                data: {
                    action: 'toggle_status',
                    mt5_id: mt5Id,
                    status: newStatus
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Update button text, class, and data attributes
                        button.setAttribute('data-current-status', newStatus);
                        button.textContent = newStatus === 'pass' ? 'Mark as Fail' : 'Mark as Pass';

                        // Update button class
                        button.classList.remove('btn-success', 'btn-danger');
                        button.classList.add(newStatus === 'pass' ? 'btn-danger' : 'btn-success');

                        // Update the status badge in the same row
                        const row = button.closest('tr');
                        const statusCell = row.querySelector('td:nth-child(7) .badge'); // Status is 7th column
                        statusCell.className = 'badge ' + (newStatus === 'pass' ? 'bg-success' : 'bg-danger');
                        statusCell.textContent = newStatus === 'pass' ? 'Pass' : 'Fail';

                        Swal.fire({
                            title: 'Success!',
                            text: 'Status updated successfully.',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        });
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
</script>