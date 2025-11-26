<?php
require_once 'functions/auth.php';
checkAdminAuth();
require_once '../database.php';

// Get database connection
$pdo = getPDO();

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
                        <th>Submitted At</th>
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
                        <td><?php echo date('M d, Y H:i', strtotime($detail['submitted_at'])); ?></td>
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
        initComplete: function () {
            $('.dataTables_length select').addClass('form-select form-select-sm');
            $('.dataTables_filter input').addClass('form-control form-control-sm');
        }
    });
});
</script>