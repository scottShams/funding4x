<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Include database connection
require_once '../database.php';

// Get database connection
$pdo = getPDO();

// Get all users except admin with their referral counts
$query = "
    SELECT 
        u.*,
        COUNT(r.id) as referral_count
    FROM waitlist_users u
    LEFT JOIN waitlist_users r ON u.id = r.parent_user_id
    WHERE u.email != 'admin@gmail.com'
    GROUP BY u.id
    ORDER BY u.created_at DESC
";
$stmt = $pdo->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ----------------------
// CSV EXPORT
// ----------------------
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Prepare query to fetch all users with referral counts
    $export_query = "
        SELECT 
            u.*,
            COUNT(r.id) as referral_count
        FROM waitlist_users u
        LEFT JOIN waitlist_users r ON u.id = r.parent_user_id
        WHERE u.email != 'admin@gmail.com'
        GROUP BY u.id
        ORDER BY u.id DESC
    ";
    $export_stmt = $pdo->prepare($export_query);
    $export_stmt->execute();
    $export_users = $export_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Set CSV headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=waitlist_users_' . date('Y-m-d_H-i-s') . '.csv');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Open output stream
    $output = fopen('php://output', 'w');

    // Add UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // CSV headers
    fputcsv($output, ['ID', 'Name', 'Email', 'Country', 'Email Verified', 'Referrals', 'Created At']);

    // Loop through users and write rows
    foreach ($export_users as $user) {
        fputcsv($output, [
            $user['id'] ?? 'N/A',
            $user['name'] ?? 'N/A',
            $user['email'] ?? 'N/A',
            !empty($user['country']) ? $user['country'] : 'N/A',
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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Waitlist Users - Admin Panel</title>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

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
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="../assets/logo.png" alt="Funding4X Logo" class="me-2" style="height: 32px; width: 32px;">
            <span>Admin Panel</span>
        </a>
        <div class="navbar-nav ms-auto">
            <a class="nav-link" href="logout.php">Logout</a>
        </div>
    </div>
</nav>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Waitlist Users Management</h1>
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
                            <th>Email Verified</th>
                            <th>Referrals</th>
                            <th>Created</th>
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
                                    <span><?php echo htmlspecialchars($user['name']); ?></span>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['country'] ?? 'N/A'); ?></td>
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
                            <td><?php echo date('M d, Y H:i', strtotime($user['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function() {
    var table = $('#usersTable').DataTable({
        dom: '<"row mb-3"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search users...",
            lengthMenu: "_MENU_ users per page",
            info: "Showing _START_ to _END_ of _TOTAL_ users",
            infoEmpty: "No users found",
            infoFiltered: "(filtered from _MAX_ total users)"
        },
        pageLength: 10,
        lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
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
                targets: -1,
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
</script>

</body>
</html>
