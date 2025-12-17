<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../database.php';

/**
 * Record an admin action to the audit log.
 * @param PDO $pdo
 * @param int|null $adminId
 * @param string $actionType
 * @param int|null $targetUserId
 * @param mixed $details (array|string)
 * @return void
 */
function recordAdminAction(PDO $pdo, $adminId, $actionType, $targetUserId = null, $details = null) {
    if ($adminId === null) {
        // Try session
        if (session_status() === PHP_SESSION_NONE) session_start();
        $adminId = $_SESSION['admin_id'] ?? null;
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $detailsStr = null;
    if (is_array($details) || is_object($details)) {
        $detailsStr = json_encode($details);
    } else if ($details !== null) {
        $detailsStr = (string)$details;
    }

    $stmt = $pdo->prepare("INSERT INTO admin_action_logs (admin_id, action_type, target_user_id, details, ip_address, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    try {
        $stmt->execute([$adminId, $actionType, $targetUserId, $detailsStr, $ip]);
    } catch (PDOException $e) {
        // Don't allow audit logging failure to break admin workflows
        error_log('Admin action log failed: ' . $e->getMessage());
    }
}

function fetchAdminActionLogs(PDO $pdo, $limit = 200) {
    $stmt = $pdo->prepare("SELECT l.*, a.name as admin_name, u.email as user_email, u.name as user_name FROM admin_action_logs l LEFT JOIN admins a ON l.admin_id = a.id LEFT JOIN waitlist_users u ON l.target_user_id = u.id ORDER BY l.created_at DESC LIMIT ?");
    $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>
