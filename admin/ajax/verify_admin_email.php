<?php
/**
 * Admin Email Verification Handler
 * Verifies if admin exists and generates reset token
 */

require_once '../../database.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $email = $data['email'] ?? '';

    // Validate email
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        exit;
    }

    $pdo = getPDO();

    // Check if admin exists
    $stmt = $pdo->prepare("SELECT id, name, email FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if (!$admin) {
        // Don't reveal if email exists for security
        http_response_code(200);
        echo json_encode(['success' => false, 'message' => 'Email not found']);
        exit;
    }

    // Generate reset token
    $resetToken = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

    // Store token in database
    $stmt = $pdo->prepare("
        UPDATE admins 
        SET password_reset_token = ?, password_reset_expires = ? 
        WHERE id = ?
    ");
    $stmt->execute([$resetToken, $expiresAt, $admin['id']]);

    // Build reset link
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $resetLink = $protocol . '://' . $host . '/admin/reset_password.php?token=' . urlencode($resetToken);

    echo json_encode([
        'success' => true,
        'name' => $admin['name'],
        'email' => $admin['email'],
        'resetLink' => $resetLink
    ]);
    exit;

} catch (PDOException $e) {
    error_log("Admin Email Verification Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
} catch (Exception $e) {
    error_log("Admin Email Verification Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
    exit;
}
?>
