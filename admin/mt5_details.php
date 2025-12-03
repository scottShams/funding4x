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
    if (!in_array($newStatus, ['pass', 'fail', 'pending', 'running'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }

    // Handle fail reasons
    $failReasons = isset($_POST['fail_reasons']) ? $_POST['fail_reasons'] : [];

    // Update status
    if ($newStatus === 'fail') {
        $failReasonJson = json_encode($failReasons);
        $stmt = $pdo->prepare("UPDATE mt5_details SET status = ?, fail_reason = ? WHERE id = ?");
        $success = $stmt->execute([$newStatus, $failReasonJson, $mt5Id]);
    } else {
        $stmt = $pdo->prepare("UPDATE mt5_details SET status = ? WHERE id = ?");
        $success = $stmt->execute([$newStatus, $mt5Id]);
    }

    if ($success) {
        // Send email if status is "running" or "fail"
        $emailSent = true;
        if ($newStatus === 'running') {
            // Get user email for sending notification
            $userStmt = $pdo->prepare("SELECT u.email, u.name FROM mt5_details m JOIN waitlist_users u ON m.user_id = u.id WHERE m.id = ?");
            $userStmt->execute([$mt5Id]);
            $userData = $userStmt->fetch(PDO::FETCH_ASSOC);

            if ($userData) {
                require_once '../email_verification.php';
                $emailSent = EmailVerification::sendAccountReadyEmail($userData['email'], $userData['name']);
            }
        } elseif ($newStatus === 'fail') {
            // Get user email and fail reasons for sending notification
            $userStmt = $pdo->prepare("SELECT u.email, u.name, m.fail_reason FROM mt5_details m JOIN waitlist_users u ON m.user_id = u.id WHERE m.id = ?");
            $userStmt->execute([$mt5Id]);
            $userData = $userStmt->fetch(PDO::FETCH_ASSOC);

            if ($userData) {
                require_once '../email_verification.php';
                $failReasons = json_decode($userData['fail_reason'], true) ?: [];
                $emailSent = EmailVerification::sendFailEmail($userData['email'], $userData['name'], $failReasons);
            }
        }

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
                            } elseif ($status === 'running') {
                                $badgeClass = 'bg-primary';
                                $statusText = 'Running';
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
                                    <li><a class="dropdown-item text-success" href="#" onclick="updateStatus(<?php echo $detail['id']; ?>, 'pass', '<?php echo htmlspecialchars($detail['name']); ?>')">
                                        <i class="bi bi-check-circle me-2"></i>Mark as Pass
                                    </a></li>
                                    <li><a class="dropdown-item text-primary" href="#" onclick="updateStatus(<?php echo $detail['id']; ?>, 'running', '<?php echo htmlspecialchars($detail['name']); ?>', '<?php echo htmlspecialchars($detail['email']); ?>')">
                                        <i class="bi bi-play-circle me-2"></i>Mark as Running
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
                            <input class="form-check-input fail-reason" type="checkbox" value="You didn't set a Stop Loss or Target Price" id="reason1">
                            <label class="form-check-label" for="reason1">
                                1. You didn't set a Stop Loss or Target Price
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input fail-reason" type="checkbox" value="You Opened a single position that is larger than 0.1 lots" id="reason2">
                            <label class="form-check-label" for="reason2">
                                2. You Opened a single position that is larger than 0.1 lots
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input fail-reason" type="checkbox" value="Your Total open positions is larger than 0.5 lots" id="reason3">
                            <label class="form-check-label" for="reason3">
                                3. Your Total open positions is larger than 0.5 lots
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input fail-reason" type="checkbox" value="You hit the Maximum Drawn Down limit of 10%." id="reason4">
                            <label class="form-check-label" for="reason4">
                                4. You hit the Maximum Drawn Down limit of 10%.
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input fail-reason" type="checkbox" value="You did EA/robot trading" id="reason5">
                            <label class="form-check-label" for="reason5">
                                5. You did EA/robot trading
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input fail-reason" type="checkbox" value="You did Copy Trading" id="reason6">
                            <label class="form-check-label" for="reason6">
                                6. You did Copy Trading
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input fail-reason" type="checkbox" value="You Traded during critical News Time" id="reason7">
                            <label class="form-check-label" for="reason7">
                                7. You Traded during critical News Time
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input fail-reason" type="checkbox" value="You Traded something other than Forex and Metals" id="reason8">
                            <label class="form-check-label" for="reason8">
                                8. You Traded something other than Forex and Metals
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input fail-reason" type="checkbox" value="Your positions were held overnight, that is past 6pm New York Time" id="reason9">
                            <label class="form-check-label" for="reason9">
                                9. Your positions were held overnight, that is past 6pm New York Time
                            </label>
                        </div>
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

    const statusText = newStatus === 'pass' ? 'Pass' : (newStatus === 'fail' ? 'Fail' : (newStatus === 'running' ? 'Running' : 'Pending'));
    const confirmColor = newStatus === 'pass' ? '#28a745' : (newStatus === 'fail' ? '#dc3545' : (newStatus === 'running' ? '#0d6efd' : '#ffc107'));

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
                        } else if (newStatus === 'running') {
                            badgeClass = 'bg-primary';
                            badgeText = 'Running';
                        }

                        statusCell.className = 'badge ' + badgeClass;
                        statusCell.textContent = badgeText;

                        // Check if email was sent (for running or fail status)
                        if (newStatus === 'running' || newStatus === 'fail') {
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
        // Send AJAX request
        $.ajax({
            url: 'mt5_details.php',
            type: 'POST',
            data: {
                action: 'update_status',
                mt5_id: window.currentMt5Id,
                status: 'fail',
                fail_reasons: checkedReasons
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update the status badge in the same row
                    const dropdownButton = document.querySelector(`#dropdownMenuButton${window.currentMt5Id}`);
                    const row = dropdownButton.closest('tr');
                    const statusCell = row.querySelector('td:nth-child(7) .badge'); // Status is 7th column
                    statusCell.className = 'badge bg-danger';
                    statusCell.textContent = 'Fail';
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
    });
});
</script>