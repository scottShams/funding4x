<?php
session_start();
// Include database connection
require_once 'database.php';

// Get database connection
$pdo = getPDO();

// Deactivate user
if (isset($_SESSION['user_email'])) {
    $uemail = $_SESSION['user_email'];
    $update = $pdo->prepare("UPDATE waitlist_users SET status = 'inactive' WHERE email = ?");
    $update->execute([$uemail]);
}

session_destroy();

header("Location: index.php");
exit;
?>
