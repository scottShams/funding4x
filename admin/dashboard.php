<?php
require_once 'functions/auth.php';
checkAdminAuth();
require_once '../database.php';

// Get database connection
$pdo = getPDO();

// Get stats from waitlist_users table (excluding admin)
$userCount = $pdo->query("SELECT COUNT(*) FROM waitlist_users WHERE email != 'admin@gmail.com'")->fetchColumn();
$verifiedCount = $pdo->query("SELECT COUNT(*) FROM waitlist_users WHERE email_verified = 1 AND email != 'admin@gmail.com'")->fetchColumn();
$referralCount = $pdo->query("SELECT COUNT(*) FROM waitlist_users WHERE parent_user_id IS NOT NULL")->fetchColumn();

// Get last 10 users
$stmt = $pdo->prepare("SELECT id, name, email, email_verified, created_at FROM waitlist_users WHERE email != 'admin@gmail.com' ORDER BY created_at DESC LIMIT 10");
$stmt->execute();
$recentUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php
ob_start();
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Dashboard</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group mr-2">
            <span class="navbar-text">
                Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>
            </span>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="row">
    <div class="col-md-4">
        <div class="card text-white bg-primary mb-3">
            <div class="card-body">
                <h5 class="card-title">Total Users</h5>
                <h2><?php echo $userCount; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-success mb-3">
            <div class="card-body">
                <h5 class="card-title">Verified Users</h5>
                <h2><?php echo $verifiedCount; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-info mb-3">
            <div class="card-body">
                <h5 class="card-title">Total Referrals</h5>
                <h2><?php echo $referralCount; ?></h2>
            </div>
        </div>
    </div>
    
</div>

<!-- Recent Users -->
<div class="mt-4">
    <h3 class="mb-3">Recent Users</h3>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="recentUsersTable" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Verified</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentUsers as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-light rounded-circle me-2 d-flex align-items-center justify-content-center">
                                        <i class="bi bi-person text-primary"></i>
                                    </div>
                                    <span><a href="../referral_dashboard.php" target="_blank"><?php echo htmlspecialchars($user['name']); ?></a></span>
                                </div>
                            </td>
                            <td><a href="../referral_dashboard.php" target="_blank"><?php echo htmlspecialchars($user['email']); ?></a></td>
                            <td>
                                <?php if ($user['email_verified']): ?>
                                    <span class="badge bg-success-subtle text-success px-2 py-1">
                                        <i class="bi bi-check-circle me-1"></i>Verified
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning px-2 py-1">
                                        <i class="bi bi-exclamation-circle me-1"></i>Unverified
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M d, Y H:i', strtotime($user['created_at'])); ?></td>
                            
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="text-end mt-3">
                <a href="users.php" class="btn btn-primary">Show All Users</a>
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

.avatar-sm {
    width: 32px;
    height: 32px;
    font-size: 0.875rem;
}
</style>

<script>
$(document).ready(function() {
    var table = $('#recentUsersTable').DataTable({
        dom: '<"row mb-3"<"col-sm-12"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row mt-3"<"col-sm-12 col-md-5"i>>',
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search users...",
            info: "Showing _START_ to _END_ of _TOTAL_ users",
            infoEmpty: "No users found",
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
            $('.dataTables_filter input').addClass('form-control form-control-sm');

            // Create button container
            var buttonContainer = $('<div class="text-end mb-3"></div>');
            table.buttons().container().appendTo(buttonContainer);
            $('.dataTables_filter').parent().after(buttonContainer);
        }
    });
});
</script>