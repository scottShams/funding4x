<?php
require_once '../functions/auth.php';
checkAdminAuth();
require_once '../../database.php';
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

    // Save template if requested (using subject as template name)
    if ($saveTemplate) {
        // Use subject as template name, update if exists or insert new
        $stmt = $pdo->prepare("INSERT INTO email_templates (name, subject, body, created_at) VALUES (?, ?, ?, NOW())
                               ON DUPLICATE KEY UPDATE subject = VALUES(subject), body = VALUES(body), created_at = NOW()");
        $stmt->execute([$subject, $subject, $body]);
    }

    // Send email using PHPMailer with SMTP configuration
    $emailSent = sendCustomEmail($user['email'], $user['name'], $subject, $body);

    if ($emailSent) {
        // Log the email sent
        $stmt = $pdo->prepare("INSERT INTO email_logs (user_id, subject, body, sent_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$userId, $subject, $body]);

        echo json_encode(['success' => true, 'message' => 'Email sent successfully and saved as template']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send email']);
    }
    exit;
}

// Custom email sending function using PHPMailer
function sendCustomEmail($to, $name, $subject, $body) {
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

        // Create HTML email template
        $htmlBody = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>{$subject}</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #4f009d;'>{$subject}</h2>
                <div style='white-space: pre-line;'>
                    {$body}
                </div>
                <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                <p style='font-size: 12px; color: #666;'>
                    This email was sent by Funding4x Admin.<br>
                    If you have any questions, please contact our support team.
                </p>
            </div>
        </body>
        </html>
        ";

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
        $mail->setFrom('noreply@funding4x.com', 'Funding4x');
        $mail->addAddress($to, $name);
        $mail->addReplyTo('support@funding4x.com', 'Funding4x Support');

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = strip_tags($body); // Plain text alternative

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