<?php
require_once 'functions/auth.php';
checkAdminAuth();
require_once '../database.php';

// Get database connection
$pdo = getPDO();

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

// Get payments data with user information
$query = "
    SELECT 
        p.id AS payment_id,
        p.user_id,
        p.payment_method,
        p.amount,
        p.currency,
        p.status,
        p.crypto_type,
        p.crypto_network,
        p.wallet_address,
        p.transaction_hash,
        p.created_at AS payment_date,
        wu.name,
        wu.email,
        wu.user_credit
    FROM payments p
    LEFT JOIN waitlist_users wu ON p.user_id = wu.id
    INNER JOIN (
        SELECT user_id, MAX(created_at) AS latest_payment
        FROM payments
        WHERE status = 'completed'
        GROUP BY user_id
    ) latest ON p.user_id = latest.user_id AND p.created_at = latest.latest_payment
    WHERE p.status = 'completed'
    ORDER BY p.created_at DESC;
";

$stmt = $pdo->prepare($query);
$stmt->execute();
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php
ob_start();
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Payments Summary</h1>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="paymentsTable" class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>User Credits</th>
                        <th>Amount</th>
                        <th>Transaction Hash</th>
                        <th>Payment Method</th>
                        <th>Payment Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $loop = 0; 
                    foreach ($payments as $payment): ?>
                    <tr>
                        <td><?= ++$loop ?></td>
                        <td><a href="user_referral_details.php?id=<?php echo $payment['user_id']; ?>" class="text-decoration-none"><?php echo ucfirst(htmlspecialchars($payment['name'])); ?></a></td>
                        <td><?php echo htmlspecialchars($payment['email']); ?></td>
                        <td>
                            <span id="credit-<?php echo $payment['user_id']; ?>"><?php echo $payment['user_credit'] ?? 0; ?></span>
                        </td>
                        <td>$<?php echo number_format($payment['amount'], 2); ?> <?php echo htmlspecialchars($payment['currency']); ?></td>
                        <td>
                            <?php if (!empty($payment['transaction_hash'])): ?>
                                <span class="font-monospace small"><?php echo htmlspecialchars(substr($payment['transaction_hash'], 0, 20) . '...'); ?></span>
                                <button class="btn btn-sm btn-outline-secondary ms-1" onclick="copyToClipboard('<?php echo htmlspecialchars($payment['transaction_hash']); ?>')" title="Copy full hash">
                                    <i class="fa fa-copy"></i>
                                </button>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            if ($payment['payment_method'] === 'crypto') {
                                echo '<span class="badge bg-info">Crypto (' . htmlspecialchars($payment['crypto_type']) . ')</span>';
                            } elseif ($payment['payment_method'] === 'credit_card') {
                                echo '<span class="badge bg-primary">Credit Card</span>';
                            } else {
                                echo '<span class="badge bg-secondary">' . htmlspecialchars($payment['payment_method']) . '</span>';
                            }
                            ?>
                        </td>
                        <td><?php echo date('M d, Y H:i', strtotime($payment['payment_date'])); ?></td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton<?php echo $payment['payment_id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                    Actions
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton<?php echo $payment['payment_id']; ?>">
                                    <li><a class="dropdown-item" href="#" onclick="openEmailModal(<?php echo $payment['user_id']; ?>, '<?php echo htmlspecialchars($payment['name']); ?>', '<?php echo htmlspecialchars($payment['email']); ?>')">
                                        <i class="bi bi-envelope me-2"></i>Send Mail
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="#" onclick="addCredit(<?php echo $payment['user_id']; ?>, '<?php echo htmlspecialchars($payment['name']); ?>')">
                                        <i class="bi bi-plus-circle me-2"></i>Add Credit
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="removeCredit(<?php echo $payment['user_id']; ?>, '<?php echo htmlspecialchars($payment['name']); ?>')">
                                        <i class="bi bi-dash-circle me-2"></i>Remove Credit
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

.btn-action {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    border-radius: 0.25rem;
}

/* SweetAlert Toast Styling */
.colored-toast {
    background-color: #28a745 !important;
    color: white !important;
}
</style>

<script>
$(document).ready(function() {
    var table = $('#paymentsTable').DataTable({
        dom: '<"row mb-3"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search payments...",
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

// Copy to clipboard function
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        // Show success message using SweetAlert
        Swal.fire({
            title: 'Copied!',
            text: 'Transaction hash copied to clipboard',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end',
            customClass: {
                popup: 'colored-toast'
            }
        });
    }).catch(function(err) {
        console.error('Could not copy text: ', err);
        Swal.fire({
            title: 'Error!',
            text: 'Failed to copy transaction hash',
            icon: 'error',
            timer: 2000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
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
                url: 'payments.php',
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
                url: 'payments.php',
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
</script>