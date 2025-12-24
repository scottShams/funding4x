<?php
session_start();
require_once 'database.php';

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

    // Check challenge ID (if provided)
    $challengeId = isset($data['challenge_id']) && is_numeric($data['challenge_id']) ? (int)$data['challenge_id'] : null;

    // Check first mt5 details (Test 1) for this challenge (or fallback)
    if ($challengeId) {
        $stmt = $pdo->prepare("SELECT id, status FROM mt5_details WHERE user_id = ? AND challenge_id = ?");
        $stmt->execute([$userId, $challengeId]);
        $firstPhase = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->prepare("SELECT id, status FROM mt5_details WHERE user_id = ? ORDER BY submitted_at DESC LIMIT 1");
        $stmt->execute([$userId]);
        $firstPhase = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$firstPhase) {
        http_response_code(403);
        echo json_encode(['error' => 'You must submit Phase 1 for this challenge first']);
        exit;
    }

    // Define mt5_details_id
    $mt5DetailsId = $firstPhase['id'];

    // Check if already submitted Test 2 (for this challenge if provided)
    if ($challengeId) {
        $stmt = $pdo->prepare("SELECT id, status FROM mt5_details_second WHERE user_id = ? AND challenge_id = ?");
        $stmt->execute([$userId, $challengeId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->prepare("SELECT id, status FROM mt5_details_second WHERE user_id = ? ORDER BY submitted_at DESC LIMIT 1");
        $stmt->execute([$userId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Allow re-submits/updates per-challenge

    if ($existing) {
        // Block updates when the existing record is final
        if (in_array($existing['status'], ['pass', 'fail'])) {
            http_response_code(403);
            echo json_encode(['error' => "Cannot update Phase 2 because test status is '{$existing['status']}'"]);
            exit;
        }

        // Update existing Test 2 details (by id)
        $id = $existing['id'];
        $stmt = $pdo->prepare("UPDATE mt5_details_second SET username = ?, password = ?, server = ?, status = 'updated', status_updated_at = NOW() WHERE id = ?");
        $stmt->execute([$mt5Details['username'], $mt5Details['password'], $mt5Details['server'], $id]);

        echo json_encode(['success' => true, 'message' => 'MT5 Test 2 details updated successfully']);
        exit;
    }

    // Insert Trading Test 2 (attach to challenge if provided)
    if ($challengeId) {
        $stmt = $pdo->prepare("INSERT INTO mt5_details_second (user_id, mt5_details_id, username, password, server, challenge_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $mt5DetailsId, $mt5Details['username'], $mt5Details['password'], $mt5Details['server'], $challengeId]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO mt5_details_second (user_id, mt5_details_id, username, password, server) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $mt5DetailsId, $mt5Details['username'], $mt5Details['password'], $mt5Details['server']]);
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
