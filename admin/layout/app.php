<?php include __DIR__ . '/../includes/header.php'; ?>

<!-- Mobile Toggle Button -->
<button class="btn btn-link d-md-none position-fixed top-0 start-0 mt-2 ms-2 z-3" id="sidebarToggle" type="button">
    <i class="bi bi-list fs-2 text-primary"></i>
</button>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay d-md-none" id="sidebarOverlay"></div>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <main id="main-content" class="col-md-10 ms-sm-auto px-md-4">
            <?php echo $content; ?>
        </main>
    </div>
</div>

<style>
.sidebar-sticky {
    position: relative;
    top: 0;
    height: calc(100vh - 48px);
    padding-top: .5rem;
    overflow-x: hidden;
    overflow-y: auto;
}

#main-content {
    margin-left: 0;
    padding-top: 20px;
}

.sidebar .nav-link {
    font-weight: 500;
    color: #333;
}

.sidebar .nav-link.active {
    color: #007bff;
}

.sidebar .nav-link:hover {
    color: #007bff;
}

@media (max-width: 768px) {
    #sidebar {
        position: fixed;
        top: 0;
        bottom: 0;
        left: -250px;
        z-index: 100;
        width: 250px;
        background: white;
        box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        transition: left 0.3s ease;
        padding: 48px 0 0;
    }
    body.sidebar-active #sidebar {
        left: 0;
    }
    .sidebar-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 99;
        display: none;
    }
    body.sidebar-active .sidebar-overlay {
        display: block;
    }
    #main-content {
        margin-left: 0;
    }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>