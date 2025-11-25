<?php
session_start();
// Include database connection
require_once 'database.php';

// Get database connection
$pdo = getPDO();

// Deactivate user
if (isset($_SESSION['user_email'])) {

    $uemail = $_SESSION['user_email'];

    // First get user info (so we can read user_ip)
    $getUser = $pdo->prepare("SELECT user_ip FROM waitlist_users WHERE email = ?");
    $getUser->execute([$uemail]);
    $userData = $getUser->fetch(PDO::FETCH_ASSOC);

    if ($userData) {

        $userIp = $userData['user_ip'];

        // Deactivate this user
        $update = $pdo->prepare("UPDATE waitlist_users SET status = 'inactive' WHERE email = ?");
        $update->execute([$uemail]);

        // Block this user's IP
        $stmt = $pdo->prepare("INSERT IGNORE INTO blocked_ips (ip_address) VALUES (?)");
        $stmt->execute([$userIp]);
    }
}


session_destroy();

header("Location: index.php");
exit;
?>
