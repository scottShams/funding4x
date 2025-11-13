<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Database config
$host = 'localhost';
$dbname = 'funding4x';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Search setup
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_condition = '';
$search_params = [];

if ($search) {
    $search_condition = "AND (name LIKE ? OR email LIKE ? OR country LIKE ?)";
    $search_params = ["%$search%", "%$search%", "%$search%"];
}

// --- Count total users (excluding admin) ---
$total_query = "SELECT COUNT(*) as total FROM waitlist_users WHERE email != 'admin@admin.com' $search_condition";
$total_stmt = $pdo->prepare($total_query);
$total_stmt->execute($search_params);
$total_records = (int)$total_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// --- Fetch user records (excluding admin) ---
// NOTE: You cannot bind LIMIT/OFFSET directly in MySQL PDO, so we inject validated integers.
$query = "SELECT * FROM waitlist_users 
          WHERE email != 'admin@admin.com' 
          $search_condition 
          ORDER BY created_at DESC 
          LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($search_params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Admin Panel</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1>Waitlist Users</h1>

        <!-- Search Form -->
        <form method="GET" class="mb-4">
            <div class="input-group">
                <input type="text" class="form-control" name="search" placeholder="Search by name, email, or country"
                       value="<?php echo htmlspecialchars($search); ?>">
                <button class="btn btn-outline-secondary" type="submit">Search</button>
                <?php if ($search): ?>
                    <a href="index.php" class="btn btn-outline-danger">Clear</a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Users Table -->
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Country</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($users) > 0): ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['country'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">No users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>

        <p class="mt-3 fw-semibold">Total users: <?php echo $total_records; ?></p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
