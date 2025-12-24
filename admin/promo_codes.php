<?php
require_once 'functions/auth.php';
checkAdminAuth();
require_once __DIR__ . '/../database.php';

$pdo = getPDO();
$message = '';
$successMessage = '';

// Prepare old values for repopulation on validation failure
$old_code = '';
$old_type = 'percent';
$old_value = '';
$old_expires_at = '';
$showPromoModal = false;

// Edit modal state
$edit_showModal = false;
$edit_old = ['id' => '', 'code' => '', 'type' => 'percent', 'value' => '', 'expires_at' => ''];

// Handle create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $old_code = trim($_POST['code'] ?? '');
    $old_type = ($_POST['type'] === 'amount') ? 'amount' : 'percent';
    $old_value = $_POST['value'] ?? '';
    $old_expires_at = $_POST['expires_at'] ?? '';

    $code = $old_code;
    $type = $old_type;
    $value = (float)($old_value ?? 0);
    // Convert HTML datetime-local format to MySQL DATETIME (store in UTC)
    $expires_at = !empty($old_expires_at) ? date('Y-m-d H:i:s', strtotime($old_expires_at) - 6*3600) : null;

    if ($code === '' || $value <= 0) {
        $message = 'Code and non-zero value are required.';
        $showPromoModal = true;
    } else {
        $stmt = $pdo->prepare("INSERT INTO promo_codes (code, `type`, `value`, expires_at) VALUES (?, ?, ?, ?)");
        try {
            $stmt->execute([$code, $type, $value, $expires_at]);
            $successMessage = 'Promo code created.';
        } catch (Exception $e) {
            $message = 'Error creating promo code: ' . $e->getMessage();
            $showPromoModal = true;
        }
    }
}

// Handle edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = (int)($_POST['id'] ?? 0);
    $edit_old['id'] = $id;
    $edit_old['code'] = trim($_POST['code'] ?? '');
    $edit_old['type'] = ($_POST['type'] === 'amount') ? 'amount' : 'percent';
    $edit_old['value'] = $_POST['value'] ?? '';
    $edit_old['expires_at'] = $_POST['expires_at'] ?? '';

    if ($id <= 0) {
        $message = 'Invalid promo ID.';
        $edit_showModal = true;
    } else {
        $code = $edit_old['code'];
        $type = $edit_old['type'];
        $value = (float)$edit_old['value'];
        $expires_at = !empty($edit_old['expires_at']) ? date('Y-m-d H:i:s', strtotime($edit_old['expires_at']) - 6*3600) : null;

        if ($code === '' || $value <= 0) {
            $message = 'Code and non-zero value are required.';
            $edit_showModal = true;
        } else {
            $update = $pdo->prepare("UPDATE promo_codes SET code = ?, `type` = ?, `value` = ?, expires_at = ? WHERE id = ?");
            try {
                $update->execute([$code, $type, $value, $expires_at, $id]);
                $successMessage = 'Promo code updated.';
            } catch (Exception $e) {
                $message = 'Error updating promo code: ' . $e->getMessage();
                $edit_showModal = true;
            }
        }
    }
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare('DELETE FROM promo_codes WHERE id = ?');
        $stmt->execute([$id]);
        $successMessage = 'Promo code deleted.';
    }
}

$codes = [];
$stmt = $pdo->query('SELECT * FROM promo_codes ORDER BY created_at DESC');
$codes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php
ob_start();
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Promo Codes</h1>
</div>

<?php if ($message): ?>
    <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<div class="card mb-4">
  <div class="card-body d-flex justify-content-between align-items-center">
    <div>
      <h5 class="mb-0">Create and manage promo codes</h5>
      <div class="text-muted small">Use promo codes to provide discounts at checkout.</div>
    </div>
    <div>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#promoModal">
        <i class="bi bi-plus-lg"></i> New Promo
      </button>
    </div>
  </div>
</div>

<!-- Promo Modal -->
<div class="modal fade" id="promoModal" tabindex="-1" aria-labelledby="promoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="promoModalLabel">Create Promo Code</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="post" id="promoForm">
          <input type="hidden" name="action" value="create">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Code</label>
              <input class="form-control" name="code" required placeholder="SUMMER50" value="<?php echo htmlspecialchars($old_code); ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Type</label>
              <select name="type" class="form-select">
                <option value="percent" <?php echo $old_type === 'percent' ? 'selected' : ''; ?>>Percent</option>
                <option value="amount" <?php echo $old_type === 'amount' ? 'selected' : ''; ?>>Fixed Amount</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Value</label>
              <input class="form-control" name="value" type="number" step="0.01" required value="<?php echo htmlspecialchars($old_value); ?>">
            </div>

            <div class="col-12">
              <label class="form-label">Expires At (optional)</label>
              <input class="form-control" name="expires_at" type="datetime-local" value="<?php echo htmlspecialchars($old_expires_at); ?>">
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="document.getElementById('promoForm').submit();">Create Promo Code</button>
      </div>
    </div>
  </div>
</div>

<div class="card">
    <div class="card-body">
        <h5 class="card-title">Existing Codes</h5>
        <div class="table-responsive">
          <table id="promoTable" class="table table-striped table-hover">
            <thead>
              <tr>
                <th>Code</th>
                <th>Type</th>
                <th>Value</th>
                <th>Expires At</th>
                <th>Created</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($codes as $c): ?>
              <tr>
                <td><?php echo htmlspecialchars($c['code']); ?></td>
                <td><?php echo htmlspecialchars(ucfirst($c['type'])); ?></td>
                <td><?php echo htmlspecialchars($c['value']); ?><?php echo $c['type'] === 'percent' ? '%' : '$'; ?></td>
                <td><?php echo $c['expires_at'] ? date('M d, Y H:i', strtotime($c['expires_at']) + 6*3600) : '-'; ?></td>
                <td><?php echo date('M d, Y H:i', strtotime($c['created_at'])); ?></td>
                <td>
                  <div class="btn-group" role="group">
                    <button class="btn btn-sm btn-outline-secondary" onclick="copyCode('<?php echo htmlspecialchars($c['code']); ?>')" title="Copy code">
                        <i class="bi bi-clipboard"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="openEditModal(this)"
                            data-id="<?php echo (int)$c['id']; ?>"
                            data-code="<?php echo htmlspecialchars($c['code']); ?>"
                            data-type="<?php echo htmlspecialchars($c['type']); ?>"
                            data-value="<?php echo htmlspecialchars($c['value']); ?>"
                            data-expires_at="<?php echo htmlspecialchars($c['expires_at']); ?>"
                            title="Edit">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <form method="post" style="display:inline" onsubmit="return confirm('Delete this promo?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo (int)$c['id']; ?>">
                        <button class="btn btn-sm btn-danger">Delete</button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
    </div>
</div>


<!-- Edit Promo Modal -->
<div class="modal fade" id="editPromoModal" tabindex="-1" aria-labelledby="editPromoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editPromoModalLabel">Edit Promo Code</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form method="post" id="editPromoForm">
          <input type="hidden" name="action" value="edit">
          <input type="hidden" name="id" value="" />
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Code</label>
              <input class="form-control" name="code" required placeholder="SUMMER50" value="">
            </div>
            <div class="col-md-3">
              <label class="form-label">Type</label>
              <select name="type" class="form-select">
                <option value="percent">Percent</option>
                <option value="amount">Fixed Amount</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Value</label>
              <input class="form-control" name="value" type="number" step="0.01" required value="">
            </div>

            <div class="col-12">
              <label class="form-label">Expires At (optional)</label>
              <input class="form-control" name="expires_at" type="datetime-local" value="">
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" onclick="document.getElementById('editPromoForm').submit();">Save Changes</button>
      </div>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include 'layout/app.php';
?>

<style>
    /* DataTables Custom Styling (match other admin pages) */
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

    .promo-copy-btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.9rem;
    }
</style>

<script>
    $(document).ready(function() {
        var table = $('#promoTable').DataTable({
            dom: '<"row mb-3"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                '<"row"<"col-sm-12"tr>>' +
                '<"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search codes...",
                lengthMenu: "_MENU_ entries per page",
                info: "Showing _START_ to _END_ of _TOTAL_ codes",
                infoEmpty: "No codes found",
                infoFiltered: "(filtered from _MAX_ total codes)"
            },
            pageLength: 10,
            lengthMenu: [[5, 10, 25, 50, -1], [5,10,25,50,'All']],
            ordering: true,
            order: [],
            responsive: true,
            columnDefs: [
                { targets: [5], orderable: false }
            ],
            initComplete: function () {
                $('.dataTables_length select').addClass('form-select form-select-sm');
                $('.dataTables_filter input').addClass('form-control form-control-sm');
            }
        });

        // Auto-open modal if there was a create attempt with validation error
        var showPromoModal = <?php echo $showPromoModal ? 'true' : 'false'; ?>;
        if (showPromoModal) {
            var promoModalEl = document.getElementById('promoModal');
            var promoModal = new bootstrap.Modal(promoModalEl);
            promoModal.show();
        }

        // Auto-open edit modal if server-side requested it (e.g., edit validation error)
        var editShow = <?php echo $edit_showModal ? 'true' : 'false'; ?>;
        if (editShow) {
            var editModalEl = document.getElementById('editPromoModal');
            var editModal = new bootstrap.Modal(editModalEl);
            // populate fields with server-side values
            <?php if ($edit_showModal): ?>
                (function(){
                    var form = document.getElementById('editPromoForm');
                    form.elements['id'].value = <?php echo json_encode($edit_old['id']); ?>;
                    form.elements['code'].value = <?php echo json_encode($edit_old['code']); ?>;
                    form.elements['type'].value = <?php echo json_encode($edit_old['type']); ?>;
                    form.elements['value'].value = <?php echo json_encode($edit_old['value']); ?>;
                    form.elements['expires_at'].value = <?php echo json_encode($edit_old['expires_at']); ?>;
                })();
            <?php endif; ?>
            editModal.show();
        }
    });

    function copyCode(code) {
        navigator.clipboard.writeText(code).then(function() {
            Swal.fire({
                title: 'Copied',
                text: 'Promo code copied to clipboard',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        }).catch(function(err) {
            console.error('Copy failed', err);
            Swal.fire({ title: 'Error', text: 'Could not copy code', icon: 'error' });
        });
    }

    function openEditModal(btn) {
        var el = btn;
        var id = el.getAttribute('data-id');
        var code = el.getAttribute('data-code');
        var type = el.getAttribute('data-type');
        var value = el.getAttribute('data-value');
        var expires_at = el.getAttribute('data-expires_at');

        var form = document.getElementById('editPromoForm');
        form.elements['id'].value = id;
        form.elements['code'].value = code;
        form.elements['type'].value = type || 'percent';
        form.elements['value'].value = value;
        if (expires_at && expires_at !== '') {
            // convert UTC format to datetime-local (local time)
            var dt = new Date(expires_at + 'Z'); // treat as UTC
            if (!isNaN(dt.getTime())) {
                var local = dt.toISOString().slice(0,16);
                form.elements['expires_at'].value = local;
            } else {
                form.elements['expires_at'].value = expires_at;
            }
        } else {
            form.elements['expires_at'].value = '';
        }

        var editModalEl = document.getElementById('editPromoModal');
        var editModal = new bootstrap.Modal(editModalEl);
        editModal.show();
    }

    // Show success message with SweetAlert
    <?php if ($successMessage): ?>
        Swal.fire({
            title: 'Success',
            text: '<?php echo addslashes($successMessage); ?>',
            icon: 'success',
            timer: 3000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    <?php endif; ?>
</script>

