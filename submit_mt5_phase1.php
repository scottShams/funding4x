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
    $check = $pdo->prepare("
        SELECT id 
        FROM challenges 
        WHERE id = ? AND user_id = ?
    ");
    $check->execute([$challengeId, $_SESSION['user_id']]);

    if (!$check->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized challenge access']);
        exit;
    }

    // Check if phase 1 already exists
    $stmt = $pdo->prepare("SELECT id, status FROM mt5_details WHERE challenge_id = ?");
    $stmt->execute([$challengeId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    // Server-side allowUpdate — only grant update if user previously visited the admin REF link for this challenge and the session flag is valid
    $allowUpdate = isset($_SESSION['allow_mt5_update']) && (int)$_SESSION['allow_mt5_update'] === $challengeId && (!empty($_SESSION['allow_mt5_update_expires']) && time() < $_SESSION['allow_mt5_update_expires']);

    if ($existing) {

        // Only allow update if:
        // - admin REF link is used
        // - status is pending
        if ($allowUpdate && $existing['status'] === 'pending') {

            $update = $pdo->prepare("
                UPDATE mt5_details 
                SET username = ?, password = ?, server = ?, instrument = ?, test_type = ?, 
                    status = 'updated', status_updated_at = NOW()
                WHERE id = ?
            ");
            $update->execute([
                $username,
                $password,
                $server,
                $instrument,
                $test_type,
                (int)$existing['id']
            ]);

            $stmt = $pdo->prepare("
                SELECT id, user_id, challenge_id, username, server, instrument, status, submitted_at, test_type 
                FROM mt5_details 
                WHERE id = ?
            ");
            $stmt->execute([(int)$existing['id']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            // Clear the temporary session permission after a successful update
            unset($_SESSION['allow_mt5_update'], $_SESSION['allow_mt5_update_expires']);

            echo json_encode(['success' => true, 'mt5' => $row, 'updated' => true]);
            exit;
        }

        // Block everything else
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => "Sorry! You cannot update this test because its status is '{$existing['status']}'."
        ]);
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
