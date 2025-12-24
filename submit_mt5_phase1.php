<?php
session_start();
require_once 'database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

if (empty($data['challenge_id']) || empty($data['mt5_details'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$challengeId = (int)$data['challenge_id'];
$mt5 = $data['mt5_details'];
$username = trim($mt5['username'] ?? '');
$password = trim($mt5['password'] ?? '');
$server = trim($mt5['server'] ?? '');
$instrument = trim($mt5['instrument'] ?? '');
$test_type = trim($mt5['test_type'] ?? '50:50 F4x');

if ($username === '' || $server === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Username and server are required']);
    exit;
}

$pdo = getPDO();
try {
    // Check challenge exists and belongs to user
    $stmt = $pdo->prepare("SELECT id, user_id FROM challenges WHERE id = ?");
    $stmt->execute([$challengeId]);
    $challenge = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$challenge || (int)$challenge['user_id'] !== $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid challenge or not owned by user']);
        exit;
    }

    // Check if phase 1 already exists
    $stmt = $pdo->prepare("SELECT id, status FROM mt5_details WHERE challenge_id = ?");
    $stmt->execute([$challengeId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    $allowUpdate = !empty($data['allow_update']);
    if ($existing) {
        // Block updates when status is pass or fail
        if (in_array($existing['status'], ['pass', 'fail'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => "Sorry! You cannot update this as you have already '{$existing['status']}' this Test"]);
            exit;
        }

        if ($allowUpdate) {
            // Safe update path
            $update = $pdo->prepare("UPDATE mt5_details SET username = ?, password = ?, server = ?, instrument = ?, test_type = ?, status = 'updated', status_updated_at = NOW() WHERE id = ?");
            $update->execute([$username, $password, $server, $instrument, $test_type, (int)$existing['id']]);

            $stmt = $pdo->prepare("SELECT id, user_id, challenge_id, username, server, instrument, status, submitted_at, test_type FROM mt5_details WHERE id = ?");
            $stmt->execute([(int)$existing['id']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'mt5' => $row, 'updated' => true]);
            exit;
        }

        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Phase 1 already submitted for this challenge']);
        exit;
    }

    // Insert phase 1
    $insert = $pdo->prepare("INSERT INTO mt5_details (user_id, challenge_id, username, password, server, instrument, test_type, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
    $insert->execute([$userId, $challengeId, $username, $password, $server, $instrument, $test_type]);
    $mt5Id = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare("SELECT id, user_id, challenge_id, username, server, instrument, status, submitted_at, test_type FROM mt5_details WHERE id = ?");
    $stmt->execute([$mt5Id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'mt5' => $row]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
