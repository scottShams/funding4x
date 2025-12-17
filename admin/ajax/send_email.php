<?php
require_once '../functions/auth.php';
checkAdminAuth();
require_once '../../database.php';require_once 'functions/audit.php';require_once '../../email_verification.php';
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

    // Save template if requested (using subject as template name)
    if ($saveTemplate) {
        // Use subject as template name, update if exists or insert new
        $stmt = $pdo->prepare("INSERT INTO email_templates (name, subject, body, created_at) VALUES (?, ?, ?, NOW())
                               ON DUPLICATE KEY UPDATE subject = VALUES(subject), body = VALUES(body), created_at = NOW()");
        $stmt->execute([$subject, $subject, $body]);
    }

    // Handle file attachment
    $attachment = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $attachment = $_FILES['attachment'];
    }

    // Send email using PHPMailer with SMTP configuration
    $emailSent = sendCustomEmail($user['email'], $user['name'], $subject, $body, $attachment);

    if ($emailSent) {
        // Log the email sent
        $stmt = $pdo->prepare("INSERT INTO email_logs (user_id, subject, body, sent_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$userId, $subject, $body]);

        // Record admin action (send_email)
        $adminId = $_SESSION['admin_id'] ?? null;
        recordAdminAction($pdo, $adminId, 'send_email', $userId, ['subject' => $subject, 'saved_template' => $saveTemplate ? 1 : 0]);

        echo json_encode(['success' => true, 'message' => 'Email sent successfully and saved as template']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send email']);
    }
    exit;
}

// Custom email sending function using PHPMailer
function sendCustomEmail($to, $name, $subject, $body, $attachment = null) {
    try {
        // Get SMTP configuration from .env file (same as EmailVerification)
        $smtpHost = EnvLoader::get('SMTP_HOST', 'localhost');
        $smtpUsername = EnvLoader::get('SMTP_USERNAME', '');
        $smtpPassword = EnvLoader::get('SMTP_PASSWORD', '');
        $smtpPort = EnvLoader::get('SMTP_PORT', 587);
        $smtpEncryption = EnvLoader::get('SMTP_ENCRYPTION', 'tls');

        // Replace placeholders in body
        $body = str_replace('{name}', htmlspecialchars($name), $body);
        $body = str_replace('{email}', htmlspecialchars($to), $body);

        // Load HTML email template
        $htmlBody = file_get_contents('../../email_templates/custom_email_template.html');
        $htmlBody = str_replace(['{$subject}', '{$body}'], [$subject, $body], $htmlBody);

        // Create PHPMailer instance
        $mail = new PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->SMTPAuth = !empty($smtpUsername);
        $mail->Username = $smtpUsername;
        $mail->Password = $smtpPassword;
        $mail->SMTPSecure = $smtpEncryption;
        $mail->Port = (int)$smtpPort;
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        // Recipients
        $mail->setFrom(' support@funding4x.com', 'Funding4x Support');
        $mail->addAddress($to, $name);
        $mail->addReplyTo('noreply@funding4x.com', 'Funding4x');
        $mail->addBCC('admin@funding4x.com');

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = strip_tags($body); // Plain text alternative

        // Handle attachment
        if ($attachment !== null) {
            // Validate file size (max 10MB)
            if ($attachment['size'] > 10 * 1024 * 1024) {
                throw new Exception('Attachment file size exceeds 10MB limit');
            }

            // Validate file type
            $allowedTypes = [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'text/plain',
                'image/jpeg',
                'image/jpg',
                'image/png',
                'image/gif'
            ];

            $fileType = mime_content_type($attachment['tmp_name']);
            if (!in_array($fileType, $allowedTypes)) {
                throw new Exception('Invalid file type. Only PDF, DOC, DOCX, TXT, JPG, JPEG, PNG, GIF files are allowed');
            }

            // Add attachment
            $mail->addAttachment($attachment['tmp_name'], $attachment['name']);
        }

        // Send email
        $sent = $mail->send();

        return $sent;

    } catch (Exception $e) {
        // Log error for debugging
        error_log("Admin Email failed for $to: " . $e->getMessage());

        // Also log SMTP config for debugging
        $smtpHost = EnvLoader::get('SMTP_HOST', 'N/A');
        $smtpUsername = EnvLoader::get('SMTP_USERNAME', 'N/A');
        $smtpPort = EnvLoader::get('SMTP_PORT', 'N/A');
        error_log("SMTP Config - Host: $smtpHost, Username: $smtpUsername, Port: $smtpPort");

        return false;
    }
}
?>