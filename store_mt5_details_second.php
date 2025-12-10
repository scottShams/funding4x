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

    // Check first mt5 details (Test 1)
    $stmt = $pdo->prepare("SELECT id, status FROM mt5_details WHERE user_id = ?");
    $stmt->execute([$userId]);
    $firstPhase = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$firstPhase || $firstPhase['status'] !== 'pass') {
        http_response_code(403);
        echo json_encode(['error' => 'You must pass Trading Test 1 first']);
        exit;
    }

    // Define mt5_details_id
    $mt5DetailsId = $firstPhase['id'];

    // Check if already submitted Test 2
    $stmt = $pdo->prepare("SELECT id FROM mt5_details_second WHERE user_id = ?");
    $stmt->execute([$userId]);
    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        http_response_code(409);
        echo json_encode(['error' => 'Trading Test 2 details already submitted']);
        exit;
    }

    // Insert Trading Test 2
    $stmt = $pdo->prepare("
        INSERT INTO mt5_details_second (user_id, mt5_details_id, username, password, server)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $mt5DetailsId, $mt5Details['username'], $mt5Details['password'], $mt5Details['server']]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
