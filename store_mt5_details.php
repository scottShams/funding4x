<?php
session_start();
require_once 'database.php';
require_once 'email_verification.php';
require_once 'env_loader.php';
require_once __DIR__ . '/vendor/autoload.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$email = $_SESSION['user_email'] ?? '';
if (empty($email)) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$mt5Details = $data['mt5_details'] ?? null;

if (!$mt5Details) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data']);
    exit;
}

try {
    $pdo = getPDO();

    // Get user ID
    $stmt = $pdo->prepare("SELECT id FROM waitlist_users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    $userId = $user['id'];

    // Check if MT5 details already exist
    $stmt = $pdo->prepare("SELECT id FROM mt5_details WHERE user_id = ?");
    $stmt->execute([$userId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    $isUpdate = false;
    if ($existing) {
        // Update existing MT5 details
        $stmt = $pdo->prepare("UPDATE mt5_details SET username = ?, password = ?, server = ?, instrument = ? WHERE user_id = ?");
        $stmt->execute([$mt5Details['username'], $mt5Details['password'], $mt5Details['server'], $mt5Details['instrument'], $userId]);
        $isUpdate = true;
    } else {
        // Insert new MT5 details
        $stmt = $pdo->prepare("INSERT INTO mt5_details (user_id, username, password, server, instrument) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $mt5Details['username'], $mt5Details['password'], $mt5Details['server'], $mt5Details['instrument']]);
    }

    // Send email to support if it was an update
    if ($isUpdate) {
        $supportEmail = 'support@funding4x.com';
        $subject = 'MT5 Details Updated by User';
        $body = 'MT5 details updated by user, MT5 account number: ' . $mt5Details['username'];

        // Use PHPMailer to send email
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        // SMTP configuration
        $smtpHost = EnvLoader::get('SMTP_HOST', 'localhost');
        $smtpUsername = EnvLoader::get('SMTP_USERNAME', '');
        $smtpPassword = EnvLoader::get('SMTP_PASSWORD', '');
        $smtpPort = EnvLoader::get('SMTP_PORT', 587);
        $smtpEncryption = EnvLoader::get('SMTP_ENCRYPTION', 'tls');

        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->SMTPAuth = !empty($smtpUsername);
        $mail->Username = $smtpUsername;
        $mail->Password = $smtpPassword;
        $mail->SMTPSecure = $smtpEncryption;
        $mail->Port = (int)$smtpPort;

        $mail->setFrom('noreply@funding4x.com', 'Funding4x');
        $mail->addAddress($supportEmail, 'Support Team');

        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body = $body;

        try {
            $mail->send();
        } catch (Exception $e) {
            // Log error but don't fail the request
            error_log('Failed to send MT5 update email: ' . $e->getMessage());
        }
    }

    echo json_encode([
        'success' => true,
        'message' => $isUpdate ? 'MT5 details updated successfully' : 'MT5 details saved successfully'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>