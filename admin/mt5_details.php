<?php
require_once 'functions/auth.php';
checkAdminAuth();
require_once '../database.php';

// Get database connection
$pdo = getPDO();

// Handle status update action
if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
    header('Content-Type: application/json');

    $mt5Id = (int)$_POST['mt5_id'];
    $newStatus = $_POST['status'];

    // Validate status
    if (!in_array($newStatus, ['pass', 'fail', 'pending'])) {
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
                            <div class="dropdown">
                                <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton<?php echo $detail['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                    Actions
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton<?php echo $detail['id']; ?>">
                                    <li><a class="dropdown-item text-success" href="#" onclick="updateStatus(<?php echo $detail['id']; ?>, 'pass', '<?php echo htmlspecialchars($detail['name']); ?>')">
                                        <i class="bi bi-check-circle me-2"></i>Mark as Pass
                                    </a></li>
                                    <li><a class="dropdown-item text-danger" href="#" onclick="updateStatus(<?php echo $detail['id']; ?>, 'fail', '<?php echo htmlspecialchars($detail['name']); ?>')">
                                        <i class="bi bi-x-circle me-2"></i>Mark as Fail
                                    </a></li>
                                    <li><a class="dropdown-item text-warning" href="#" onclick="updateStatus(<?php echo $detail['id']; ?>, 'pending', '<?php echo htmlspecialchars($detail['name']); ?>')">
                                        <i class="bi bi-clock me-2"></i>Mark as Pending
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

// Update status function
function updateStatus(mt5Id, newStatus, userName) {
    const statusText = newStatus === 'pass' ? 'Pass' : (newStatus === 'fail' ? 'Fail' : 'Pending');
    const confirmColor = newStatus === 'pass' ? '#28a745' : (newStatus === 'fail' ? '#dc3545' : '#ffc107');

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
                url: 'mt5_details.php',
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
                        const statusCell = row.querySelector('td:nth-child(7) .badge'); // Status is 7th column

                        // Update badge class and text
                        let badgeClass = 'bg-warning';
                        let badgeText = 'Pending';
                        if (newStatus === 'pass') {
                            badgeClass = 'bg-success';
                            badgeText = 'Pass';
                        } else if (newStatus === 'fail') {
                            badgeClass = 'bg-danger';
                            badgeText = 'Fail';
                        }

                        statusCell.className = 'badge ' + badgeClass;
                        statusCell.textContent = badgeText;

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