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

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$params = [];

// EXCLUDE ADMIN ACCOUNT ALWAYS
$search_condition = "WHERE email != 'admin@gmail.com'";

// Apply search filter if provided
if ($search) {
    $search_condition .= " AND (name LIKE ? OR email LIKE ? OR country LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}

// ----------------------
// Get total records
// ----------------------
$total_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM waitlist_users $search_condition");
$total_stmt->execute($params);
$total_records = $total_stmt->fetch()['total'];
$total_pages = ceil($total_records / $limit);

$params_for_data = $params; // duplicate to avoid export conflict

// ----------------------
// Get paginated records
// ----------------------
$query = "SELECT * FROM waitlist_users $search_condition ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params_for_data);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ----------------------
// CSV EXPORT
// ----------------------
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Copy params without limit/offset for export
    $export_params = $params ?? [];

    // Prepare query to fetch all users
    $export_query = "SELECT * FROM waitlist_users $search_condition ORDER BY id DESC";
    $export_stmt = $pdo->prepare($export_query);
    $export_stmt->execute($export_params);
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
    fputcsv($output, ['Name', 'Email', 'Country', 'Created At']);

    // Loop through users and write rows
    foreach ($export_users as $user) {
        fputcsv($output, [
            $user['name'] ?? 'N/A',
            $user['email'] ?? 'N/A',
            !empty($user['country']) ? $user['country'] : 'N/A',
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
    <title>Waitlist Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">Admin Panel</a>
        <div class="navbar-nav ms-auto">
            <a class="nav-link" href="logout.php">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h1>Waitlist Users</h1>

    <!-- Export + Total -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="?export=csv<?php echo $search ? '&search=' . urlencode($search) : ''; ?>"
           class="btn btn-success">
            <i class="fas fa-download"></i> Export to CSV
        </a>

        <span class="text-muted">Total: <?php echo $total_records; ?> users</span>
    </div>

    <!-- Search -->
    <form method="GET" class="mb-4">
        <div class="input-group">
            <input type="text"
                   class="form-control"
                   name="search"
                   placeholder="Search by name, email, or country"
                   value="<?php echo htmlspecialchars($search); ?>">

            <button class="btn btn-outline-secondary" type="submit">Search</button>

            <?php if ($search): ?>
                <a href="index.php" class="btn btn-outline-danger">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Table -->
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Country</th>
                <th>Created At</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><?php echo htmlspecialchars($user['country'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link"
                           href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">
                            Previous
                        </a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link"
                           href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link"
                           href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">
                            Next
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
