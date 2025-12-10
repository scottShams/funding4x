<?php
require_once 'functions/auth.php';
checkAdminAuth();
require_once '../database.php';

// Get database connection
$pdo = getPDO();

// ----------------------
// CSV EXPORT
// ----------------------
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Prepare query to fetch all referrals
    $export_query = "
        SELECT
            u.id as referral_id,
            u.created_at,
            ru.name as referrer_name,
            ru.email as referrer_email,
            u.name as referred_name,
            u.email as referred_email,
            u.email_verified as referred_verified
        FROM waitlist_users u
        JOIN waitlist_users ru ON u.parent_user_id = ru.id
        WHERE u.parent_user_id IS NOT NULL
        AND u.email != 'admin@gmail.com'
        ORDER BY u.created_at DESC
    ";
    $export_stmt = $pdo->prepare($export_query);
    $export_stmt->execute();
    $export_referrals = $export_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Set CSV headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=referrals_' . date('Y-m-d_H-i-s') . '.csv');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Open output stream
    $output = fopen('php://output', 'w');

    // Add UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // CSV headers
    fputcsv($output, ['Referral ID', 'Referrer Name', 'Referrer Email', 'Referred Name', 'Referred Email', 'Referred Verified', 'Created At']);

    // Loop through referrals and write rows
    foreach ($export_referrals as $referral) {
        fputcsv($output, [
            $referral['referral_id'] ?? 'N/A',
            $referral['referrer_name'] ?? 'N/A',
            $referral['referrer_email'] ?? 'N/A',
            $referral['referred_name'] ?? 'N/A',
            $referral['referred_email'] ?? 'N/A',
            ($referral['referred_verified'] == 1) ? 'Yes' : 'No',
            !empty($referral['created_at'])
                ? date('d/m/Y H:i:s', strtotime($referral['created_at']))
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
$total_query = "SELECT COUNT(*) FROM waitlist_users u JOIN waitlist_users ru ON u.parent_user_id = ru.id WHERE u.parent_user_id IS NOT NULL AND u.email != 'admin@gmail.com'";
$total_stmt = $pdo->query($total_query);
$total_referrals = $total_stmt->fetchColumn();
$total_pages = ceil($total_referrals / $limit);

// Get all referrals with user details (using parent_user_id relationship) and pagination
$stmt = $pdo->prepare("
    SELECT
        u.id as referral_id,
        u.created_at,
        ru.name as referrer_name,
        ru.email as referrer_email,
        u.name as referred_name,
        u.email as referred_email,
        u.email_verified as referred_verified
    FROM waitlist_users u
    JOIN waitlist_users ru ON u.parent_user_id = ru.id
    WHERE u.parent_user_id IS NOT NULL
    AND u.email != 'admin@gmail.com'
    ORDER BY u.created_at DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute();
$referrals = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php
ob_start();
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Referral Management</h1>
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
            <table id="referralsTable" class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Referrer</th>
                        <th>Referred User</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($referrals as $referral): ?>
                    <tr>
                        <td><?php echo $referral['referral_id']; ?></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm bg-primary-subtle rounded-circle me-2 d-flex align-items-center justify-content-center">
                                    <i class="bi bi-person text-primary"></i>
                                </div>
                                <div>
                                    <div class="fw-medium"><?php echo htmlspecialchars($referral['referrer_name']); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($referral['referrer_email']); ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm bg-success-subtle rounded-circle me-2 d-flex align-items-center justify-content-center">
                                    <i class="bi bi-person-check text-success"></i>
                                </div>
                                <div>
                                    <div class="fw-medium"><?php echo htmlspecialchars($referral['referred_name']); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($referral['referred_email']); ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if ($referral['referred_verified'] == 1): ?>
                                <span class="badge bg-success-subtle text-success px-2 py-1">
                                    <i class="bi bi-check-circle me-1"></i>Verified
                                </span>
                            <?php else: ?>
                                <span class="badge bg-warning-subtle text-warning px-2 py-1">
                                    <i class="bi bi-clock me-1"></i>Pending
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="text-muted" title="<?php echo date('Y-m-d H:i:s', strtotime($referral['created_at'])); ?>">
                                <?php echo date('M d, Y H:i', strtotime($referral['created_at'])); ?>
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

.avatar-sm {
    width: 32px;
    height: 32px;
    font-size: 0.875rem;
}

/* Modal Styles for Responsive View */
.dtr-bs-modal .modal-body {
    padding: 20px;
}

.dtr-bs-modal .table {
    margin-bottom: 0;
}
</style>

<script>
$(document).ready(function() {
    var table = $('#referralsTable').DataTable({
        dom: '<"row mb-3"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search referrals...",
            lengthMenu: "_MENU_ referrals per page",
            info: "Showing _START_ to _END_ of _TOTAL_ referrals",
            infoEmpty: "No referrals found",
            infoFiltered: "(filtered from _MAX_ total referrals)"
        },
        paging: false,
        ordering: true,
        order: [[0, 'desc']], // Sort by ID in descending order
        responsive: {
            details: {
                display: $.fn.dataTable.Responsive.display.modal({
                    header: function(row) {
                        var data = row.data();
                        return 'Referral Details';
                    }
                }),
                renderer: $.fn.dataTable.Responsive.renderer.tableAll({
                    tableClass: 'table'
                })
            }
        },
        columnDefs: [
            {
                targets: [1, 2], // Referrer and Referred User columns
                orderable: true,
                render: function(data, type, row) {
                    if (type === 'sort' || type === 'type') {
                        return $(data).find('div.fw-medium').text();
                    }
                    return data;
                }
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
</script>