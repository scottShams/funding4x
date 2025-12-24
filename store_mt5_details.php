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

    $challengeId = isset($data['challenge_id']) && is_numeric($data['challenge_id']) ? (int)$data['challenge_id'] : null;

    // Check if MT5 details already exist for this challenge (if provided) otherwise fall back to user-wide
    if ($challengeId) {
        $stmt = $pdo->prepare("SELECT id, status FROM mt5_details WHERE user_id = ? AND challenge_id = ?");
        $stmt->execute([$userId, $challengeId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->prepare("SELECT id, status FROM mt5_details WHERE user_id = ? ORDER BY submitted_at DESC LIMIT 1");
        $stmt->execute([$userId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    $isUpdate = false;
    if ($existing) {
        // Block updates when the existing record is final
        if (in_array($existing['status'], ['pass', 'fail'])) {
            http_response_code(403);
            echo json_encode(['error' => "Cannot update Phase 1 because test status is '{$existing['status']}'"]);
            exit;
        }

        // Update existing MT5 details
        $id = $existing['id'];
        $stmt = $pdo->prepare("UPDATE mt5_details SET username = ?, password = ?, server = ?, instrument = ?, status = 'updated', status_updated_at = NOW() WHERE id = ?");
        $stmt->execute([$mt5Details['username'], $mt5Details['password'], $mt5Details['server'], $mt5Details['instrument'], $id]);
        $isUpdate = true;
    } else {
        // Insert new MT5 details (attach to challenge if provided)
        if ($challengeId) {
            $stmt = $pdo->prepare("INSERT INTO mt5_details (user_id, challenge_id, username, password, server, instrument) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $challengeId, $mt5Details['username'], $mt5Details['password'], $mt5Details['server'], $mt5Details['instrument']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO mt5_details (user_id, username, password, server, instrument) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $mt5Details['username'], $mt5Details['password'], $mt5Details['server'], $mt5Details['instrument']]);
        }
        $id = (int)$pdo->lastInsertId();

        // NOTE: previous placeholder creation for mt5_details_second has been removed to avoid creating empty placeholder records
    }

    // Send email to support if it was an update
    // if ($isUpdate) {
    //     EmailVerification::sendMT5UpdateEmail($mt5Details['username']);
    // }

    echo json_encode([
        'success' => true,
        'message' => $isUpdate ? 'MT5 details updated successfully' : 'MT5 details saved successfully'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>