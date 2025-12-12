<?php
function checkAdminAuth() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['admin_logged_in'])) {
        header('Location: login.php');
        exit;
    }
}

function checkSuperAdmin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'super_admin') {
        header('Location: dashboard.php');
        exit;
    }
}

function logout() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    session_destroy();
    header('Location: login.php');
    exit;
}
?>