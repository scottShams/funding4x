<!-- Sidebar -->
<nav id="sidebar" class="col-md-2 border-end">
    <div class="sidebar-sticky p-3">
        <!-- Brand Header -->
        <div class="d-flex align-items-center mb-4 mt-2">
            <i class="bi bi-shield-check text-primary me-2 fs-4"></i>
            <h5 class="mb-0 fw-semibold">Admin Panel</h5>
            <button class="btn btn-link d-md-none ms-auto" id="sidebarClose">
                <i class="bi bi-x-lg fs-4 text-muted"></i>
            </button>
        </div>

        <!-- Navigation Menu -->
        <ul class="nav flex-column gap-2">
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="bi bi-house-door me-2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>" href="users.php">
                    <i class="bi bi-people me-2"></i>
                    <span>Users</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center <?php echo basename($_SERVER['PHP_SELF']) == 'verified_users.php' ? 'active' : ''; ?>" href="verified_users.php">
                    <i class="bi bi-check-circle me-2"></i>
                    <span>Verified Users</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center <?php echo basename($_SERVER['PHP_SELF']) == 'quiz_pass_users.php' ? 'active' : ''; ?>" href="quiz_pass_users.php">
                    <i class="bi bi-trophy me-2"></i>
                    <span>Quiz Pass Users</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center <?php echo basename($_SERVER['PHP_SELF']) == 'referrals.php' ? 'active' : ''; ?>" href="referrals.php">
                    <i class="bi bi-diagram-3 me-2"></i>
                    <span>Referrals</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center <?php echo basename($_SERVER['PHP_SELF']) == 'mt5_details.php' ? 'active' : ''; ?>" href="mt5_details.php">
                    <i class="bi bi-briefcase me-2"></i>
                    <span>Test 1 MT5 Details</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center <?php echo basename($_SERVER['PHP_SELF']) == 'mt5_details_second.php' ? 'active' : ''; ?>" href="mt5_details_second.php">
                    <i class="bi bi-briefcase-fill me-2"></i>
                    <span>Test 2 MT5 Details</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center <?php echo basename($_SERVER['PHP_SELF']) == 'knowledge_tests.php' ? 'active' : ''; ?>" href="knowledge_tests.php">
                    <i class="bi bi-book me-2"></i>
                    <span>Knowledge Tests</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center <?php echo basename($_SERVER['PHP_SELF']) == 'payments.php' ? 'active' : ''; ?>" href="payments.php">
                    <i class="bi bi-credit-card me-2"></i>
                    <span>Payments</span>
                </a>
            </li>

            <!-- Logout Link -->
            <li class="nav-item mt-4">
                <a class="nav-link d-flex align-items-center text-danger" href="logout.php">
                    <i class="bi bi-box-arrow-right me-2"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>
</nav>