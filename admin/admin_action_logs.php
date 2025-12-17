<?php
require_once 'functions/auth.php';
checkAdminAuth();
require_once 'functions/audit.php';
require_once '../database.php';

$pdo = getPDO();
$logs = fetchAdminActionLogs($pdo, 500);

ob_start();
?>
<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Admin Action Logs</h1>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="adminActionLogsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Admin</th>
                        <th>Action</th>
                        <th>Target User</th>
                        <th>Details</th>
                        <th>IP</th>
                        <th>When</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo $log['id']; ?></td>
                        <td><?php echo htmlspecialchars($log['admin_name'] ?? ('#' . $log['admin_id'])); ?></td>
                        <td><?php echo htmlspecialchars($log['action_type']); ?></td>
                        <td><?php echo htmlspecialchars($log['user_name'] ?? $log['user_email'] ?? 'N/A'); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($log['details'])); ?></td>
                        <td><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></td>
                        <td><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
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

.avatar-sm {
    width: 32px;
    height: 32px;
    font-size: 0.875rem;
}
</style>

<script>
$(document).ready(function() {
    var table = $('#adminActionLogsTable').DataTable({
        dom: '<"row mb-3"<"col-sm-12"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search logs...",
            info: "Showing _START_ to _END_ of _TOTAL_ logs",
            infoEmpty: "No logs found",
            infoFiltered: "(filtered from _MAX_ total logs)"
        },
        paging: true,
        pageLength: 25,
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
