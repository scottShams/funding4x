<?php
/**
 * Admin Password Reset Email Handler
 * Handles sending password reset emails for admin accounts
 */

require_once '../../database.php';
require_once '../../env_loader.php';
require_once '../../vendor/autoload.php';
require_once '../../email_verification.php';

header('Content-Type: application/json');

try {
    // Check if request is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    $email = $data['email'] ?? '';
    $resetLink = $data['resetLink'] ?? '';
    $adminName = $data['adminName'] ?? '';

    // Validate input
    if (empty($email) || empty($resetLink) || empty($adminName)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    // Delegate sending to the shared EmailVerification class to keep behavior consistent
    $sent = EmailVerification::sendPasswordResetEmail($email, $adminName, $resetLink);

    if ($sent) {
        echo json_encode(['success' => true, 'message' => 'Password reset email sent successfully']);
        exit;
    } else {
        error_log("Admin Password Reset: EmailVerification::sendPasswordResetEmail returned false for $email");
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to send email. Please try again later.']);
        exit;
    }

} catch (Exception $e) {
    error_log("Admin Password Reset Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while sending the email']);
    exit;
} catch (PDOException $e) {
    error_log("Admin Password Reset DB Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    exit;
}
?>
