<?php
require_once '../functions/auth.php';
checkAdminAuth();
require_once '../../database.php';
require_once '../functions/audit.php';
require_once '../../email_verification.php';
require_once '../../env_loader.php';
require_once '../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Get database connection
$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_email') {
    header('Content-Type: application/json');

    $userId = (int)$_POST['user_id'];
    $subject = trim($_POST['subject']);
    $body = trim($_POST['body']);
    $saveTemplate = isset($_POST['save_template']) && $_POST['save_template'] === '1';
    $templateName = trim($_POST['template_name'] ?? '');

    // Validate required fields
    if (empty($userId) || empty($subject) || empty($body)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    // Get user information
    $stmt = $pdo->prepare("SELECT name, email FROM waitlist_users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    // Save template if requested (using template name if provided, otherwise use subject)
    if ($saveTemplate) {
        $templateName = !empty($templateName) ? $templateName : $subject;
        // Update if exists or insert new
        $stmt = $pdo->prepare("INSERT INTO email_templates (name, subject, body, created_at) VALUES (?, ?, ?, NOW())
                               ON DUPLICATE KEY UPDATE subject = VALUES(subject), body = VALUES(body), created_at = NOW()");
        $stmt->execute([$templateName, $subject, $body]);
    }

    // Handle file attachment
    $attachment = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $attachment = $_FILES['attachment'];
    }

    // Send email using the existing EmailVerification::sendCustomEmail method
    $emailSent = EmailVerification::sendCustomEmail($user['email'], $user['name'], $subject, $body, $attachment);

    if ($emailSent) {
        // Log the email sent
        $stmt = $pdo->prepare("INSERT INTO email_logs (user_id, subject, body, sent_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$userId, $subject, $body]);

        // Record admin action (send_email)
        $adminId = $_SESSION['admin_id'] ?? null;
        recordAdminAction($pdo, $adminId, 'send_email', $userId, ['subject' => $subject, 'saved_template' => $saveTemplate ? 1 : 0]);

        echo json_encode(['success' => true, 'message' => 'Email sent successfully' . ($saveTemplate ? ' and saved as template' : '')]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send email']);
    }
    exit;
}

?>